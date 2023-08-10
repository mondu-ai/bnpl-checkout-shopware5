<?php

namespace Mond1SWR5\Subscriber;

use Enlight_Controller_ActionEventArgs;
use Mond1SWR5\Helpers\DocumentHelper;
use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Controller_Request_RequestHttp;
use Enlight_Event_EventArgs;
use Enlight_View_Default;
use Mond1SWR5\Helpers\OrderHelper;
use Mond1SWR5\Services\PaymentService;
use Mond1SWR5\Services\PaymentStatusService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware_Components_Snippet_Manager as SnippetManager;

class OrderSubscriber implements SubscriberInterface
{
    /**
     * @var Utils
     */
    private $utils;

    /**
     * @var Service
     */
    private $service;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var DocumentHelper
     */
    private $documentHelper;


    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    private $pluginDirectory;

    private $snippetManager;

    public function __construct(
        ModelManager $modelManager,
        OrderHelper $orderHelper,
        DocumentHelper $documentHelper,
        PaymentStatusService $paymentStatusService,
        SnippetManager $snippetManager,
        $pluginDirectory
    ) {
        $this->modelManager = $modelManager;
        $this->snippetManager = $snippetManager;
        $this->orderHelper = $orderHelper;
        $this->documentHelper = $documentHelper;
        $this->paymentStatusService = $paymentStatusService;
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Backend_Order' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'extendJs',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onSaveOrder'
        ];
    }
    public function onPreDispatch(Enlight_Event_EventArgs $args) {
        $dirs = $args->getReturn();
        $dirs[] = $this->pluginDirectory . '/Resources/views_documents/';
        $args->setReturn($dirs);
    }

    public function extendJs(Enlight_Controller_ActionEventArgs $args) {
        $controller = $args->getSubject();
        $view = $controller->View();
        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');
        if ($view->hasTemplate()) {
            $view->extendsTemplate('backend/mondu_extend_order/order.js');
        }
    }
    public function onSaveOrder(Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $view = $controller->View();
        $params = $request->getParams();
        switch ($request->getActionName()) {
            // Batch Process orders.
            case 'batchProcess':
                foreach ($params['orders'] as $order) {
                    $this->processOrder($request, $order, $view);
                }
                break;

            // Process Single Order.
            case 'save':
                $requestParams = $args->getSubject()->Request()->getParams();
                $initialOrderStatusId = $requestParams['orderStatus'][0]['id'];
                $this->processOrder($request, $params, $view, $initialOrderStatusId);
                break;
            case 'savePosition':
                $this->processPosition($request, $params, $view);
                break;
            case 'deletePosition':
                $this->processPosition($request, $params, $view);
        }
    }

    protected function processPosition(Enlight_Controller_Request_RequestHttp $request, array $positionArray, Enlight_View_Default $view)
    {
        if ($request->getActionName() === 'deletePosition') {
            $order = $this->modelManager->find(Order::class, $positionArray['orderId']);
        } else {
            $detailRepository = $this->modelManager->getRepository(Detail::class);
            $position = $detailRepository->find($positionArray['id']);

            if(!$position) {
                $order = $this->modelManager->find(Order::class, $positionArray['orderId']);
            } else {
                $order = $position->getOrder();
            }
        }

        $service = Shopware()->Container()->get(PaymentService::class);

        if (!$service->isMonduPayment(['id' => $order->getPayment()->getId()])) {
            return;
        }

        try {
            if($order->getAttribute()->getMonduState() === 'shipped') {
                $view->assign([
                    'success' => false,
                    'message' => $this->snippetManager->getNamespace('backend/mondu/order')->get(
                        'order/update_error',
                        "Mondu: Order not updated because it\'s already shipped"
                    )
                ]);
                return;
            }

            $response = $this->orderHelper->updateOrder($order);

            if(isset($response) && isset($response['state']) && $response['state'] === 'pending') {
                $view->assign([
                    'success' => false,
                    'message' =>  $this->snippetManager->getNamespace('backend/mondu/order')->get(
                        'order/pending',
                        'Mondu: Order status changed to pending, you will need to await manual verification before you can invoice the order'
                    ),
                ]);
            }
        } catch (\Exception $e) {
            $view->assign([
                'success' => false,
                'message' => 'Mondu: '.$e->getMessage()
            ]);
        }
    }

    /**
     * Process an order and call the respective api endpoints.
     *
     * @return void
     */
    protected function processOrder(Enlight_Controller_Request_RequestHttp $request, array $orderArray, Enlight_View_Default $view, $initialOrderStatusId = null)
    {
        /** @var PaymentService $service */
        $service = Shopware()->Container()->get(PaymentService::class);
        if (!$service->isMonduPayment(['id' => $orderArray['paymentId']])) {
            return;
        }
        /** @var Order $order */
        $order = $this->modelManager->find(Order::class, $orderArray['id']);
        if ($order === null) {
            return;
        }
        if($initialOrderStatusId === Status::ORDER_STATE_CANCELLED_REJECTED) {
            $this->handleOrderChangeFail(
                $view,
                $order,
                Status::ORDER_STATE_CANCELLED_REJECTED,
                $this->snippetManager->getNamespace('backend/mondu/order')->get(
                    'order/already_canceled',
                    "Mondu: cant change state from cancelled"
                )
            );
        }
        switch ($orderArray['status']) {
            case $this->orderHelper->getInvoiceCreateState():
                if ($order->getAttribute()->getMonduState() === 'shipped') {
                    $this->handleOrderChangeFail(
                        $view,
                        $order,
                        Status::ORDER_STATE_COMPLETELY_DELIVERED,
                        $this->snippetManager->getNamespace('backend/mondu/order')->get(
                            'order/already_shipped',
                            'Mondu: order is already in the shipped state'
                        )
                    );
                    break;
                }


                if ($this->orderHelper->canShipOrder($order)) {
                    try {
                        $response = $this->orderHelper->shipOrder($order);
                    } catch (\Exception $e) {
                        $this->handleOrderChangeFail(
                            $view,
                            $order,
                            $initialOrderStatusId,
                            $this->snippetManager->getNamespace('backend/mondu/order')->get(
                                'order/cant_ship',
                                'Mondu: can not ship current order'
                            )
                        );
                        $this->orderHelper->logOrderError($order, 'ship', $e);
                        break;
                    }
                } else {
                    $this->handleOrderChangeFail(
                        $view,
                        $order,
                        $initialOrderStatusId,
                        $this->snippetManager->getNamespace('backend/mondu/order')->get(
                            'order/invoice_first',
                            'Mondu: Please create an invoice first'
                        )
                    );
                }
                break;
            case Status::ORDER_STATE_CANCELLED_REJECTED:
                try {
                    $this->orderHelper->cancelOrder($order);
                    $view->assign([
                        'success' => true,
                        'message' => $this->snippetManager->getNamespace('backend/mondu/order')->get(
                            'order/canceled',
                            'Mondu: Order has been canceled'
                        )
                    ]);
                } catch(\Exception $e) {
                    $this->handleOrderChangeFail($view, $order, $initialOrderStatusId, $e->getMessage());
                }
                break;
            default:
                if($request->getActionName() === 'save' && in_array($order->getAttribute()->getMonduState(), ['pending', 'confirmed'])) {
                    try {
                        $response = $this->orderHelper->updateOrder($order);
                        $view->assign([
                            'success' => true,
                            'message' => $this->snippetManager->getNamespace('backend/mondu/order')->get(
                                'order/updated',
                                'Mondu: Order has been updated'
                            )
                        ]);
                    } catch (\Exception $e) {
                        $this->handleOrderChangeFail($view, $order, $initialOrderStatusId, $e->getMessage());
                        $this->orderHelper->logOrderError($order, 'adjust', $e);
                    }
                }
        }
    }

    private function handleOrderChangeFail(&$view, $order, $state, $message)
    {
        $orderNumber = $order->getNumber();
        $monduState = $order->getAttribute()->getMonduState();

        if($monduState === 'canceled') $state = Status::ORDER_STATE_CANCELLED_REJECTED;

        $view->assign([
            'success' => false,
            'message' => $message
        ]);

        $this->paymentStatusService->updatePaymentStatus(
            $orderNumber,
            $state,
            Status::GROUP_STATE
        );
    }
}
