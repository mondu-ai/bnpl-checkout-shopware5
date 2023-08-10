<?php

namespace Mond1SWR5\Subscriber\Cron;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Mond1SWR5\Bootstrap\PaymentMethods;
use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Helpers\DocumentHelper;
use Mond1SWR5\Helpers\OrderHelper;
use Monolog\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware_Components_Cron_CronJob;

class OrderStatusCron implements SubscriberInterface
{
    const CRON_ACTION_NAME = 'Shopware_CronJob_MonduOrderProcessing';

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        Logger $logger,
        DocumentHelper $documentHelper,
        OrderHelper $orderHelper,
        ConfigService $configService
    ) {
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->logger = $logger;
        $this->documentHelper = $documentHelper;
        $this->orderHelper = $orderHelper;
        $this->configService = $configService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            self::CRON_ACTION_NAME => 'watchHistory',
        ];
    }

    public function watchHistory(Shopware_Components_Cron_CronJob $job)
    {
        if(!$this->configService->isCronEnabled()) return 'Cron job is not enabled in the plugin configuration';

        $startFrom = $this->getLastRunDateTime();
        $orderIds = $this->getMonduOrderIds($startFrom);

        foreach($orderIds as $key => $value) {
            $order = $this->modelManager->find(Order::class, $value['id']);
            if($order === null) continue;

            try {
                switch ($value['status']) {
                    case $this->orderHelper->getInvoiceCreateState():
                        $monduState = $order->getAttribute()->getMonduState();
                        if($monduState === 'shipped') break;

                        if ($this->configService->getValidateInvoice()) {
                            $invoiceNumber = $this->documentHelper->getInvoiceNumberForOrder($order);
                            if(!$invoiceNumber) {
                                $this->logger->error('Error creating an invoice order: transactionid - '. $order->getTransactionId(). ' Invoice number missing ');
                                break;
                            }
                        }

                        $invoice = $this->orderHelper->shipOrder($order);

                        if(!$invoice) {
                            $this->logger->error('Error creating an invoice order: transactionid - '. $order->getTransactionId());
                            break;
                        }

                        $this->logger->info('Created invoice for order: transactionid - '. $order->getTransactionId());
                        break;
                    case Status::ORDER_STATE_CANCELLED_REJECTED:
                        $monduState = $order->getAttribute()->getMonduState();
                        if($monduState === 'canceled') break;

                        $response = $this->orderHelper->cancelOrder($order);

                        if($response !== 'canceled') {
                            $this->logger->error('Error canceling order: transactionid - '. $order->getTransactionId());
                            break;
                        }

                        $this->logger->info('Cancelled order: '. $order->getTransactionId());
                        break;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error processing order: transactionid - '.$order->getTransactionId().' '.$e->getMessage());
            }
        }

        return 'processed order(s) starting from '. $startFrom;
    }

    private function getMonduOrderIds($startFrom) {
        $allowedOrderStates = [
            $this->orderHelper->getInvoiceCreateState(),
            Status::ORDER_STATE_CANCELLED_REJECTED
        ];

        $paymentMethods = \Mond1SWR5\Enum\PaymentMethods::LOCAL_MONDU_PAYMENT_METHODS;

        $query = $this->db->select()
            ->from(['orders' => 's_order'])
            ->joinLeft(['payment' => 's_core_paymentmeans'], 'orders.paymentID = payment.id', null)
            ->where('orders.changed >= :updated_at')
            ->where('orders.status IN ('. implode(',', $allowedOrderStates). ')')
            ->where("payment.name IN ('" . implode("','", $paymentMethods) . "')")
            ->bind([
                'updated_at' => $startFrom,
            ]);

        return $this->db->fetchAll($query);
    }

    private function getLastRunDateTime()
    {
        $query = 'SELECT `next`, `interval`, `start` FROM s_crontab WHERE `action` = ?';
        $row = $this->db->fetchRow($query, [self::CRON_ACTION_NAME]);
        if (isset($row['start'])) {
            $date = new \DateTime($row['start']);
        } else {
            $date = new \DateTime();
        }

        $date->sub(new \DateInterval('PT' . $row['interval'] . 'S'));

        return $date->format('Y-m-d H:i:s');
    }
}
