<?php

use Mond1SWR5\Enum\PaymentMethods;
use Doctrine\ORM\AbstractQuery;
use Mond1SWR5\Helpers\OrderHelper;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\HttpClient\RequestException;
use Shopware\Models\Order\Order;

/**
 * Backend Controller for lightweight backend module.
 * Manager Mondu Order Details and States.
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Shopware_Controllers_Backend_MonduOverview extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);

        $this->orderHelper = $this->container->get(OrderHelper::class);
        $this->snippetManager = $this->container->get('snippets');
    }

    /**
     * Index action displays list with orders paid with mondu.ai.
     *
     * @return void
     */
    public function indexAction()
    {
        // Build Filters
        $filters = [];
        foreach (PaymentMethods::getNames() as $name) {
            $filters[] = ['property' => 'payment.name', 'value' => $name, 'operator' => 'or'];
        }
        unset($filters[0]['operator']);

        $sort = [['property' => 'orders.orderTime', 'direction' => 'DESC']];
        $currentPage = intval($this->Request()->getParam('page', 1));
        $maxPerPage = 25;
        // Load Orders
        $builder = $this->getModelManager()->createQueryBuilder();
        $builder->select(['orders, attribute', 'billing'])
            ->from(Order::class, 'orders')
            ->leftJoin('orders.attribute', 'attribute')
            ->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.billing', 'billing')
            ->addFilter($filters)
            ->andWhere('orders.number != 0')
            ->andWhere('orders.status != -1')
            ->addOrderBy($sort)
            ->setFirstResult(($currentPage - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);
        // Get Query and paginator
        $query = $builder->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);
        $paginator = $this->getModelManager()->createPaginator($query);
        // Assign view data
        $this->View()->assign('errorCode', $this->Request()->getParam('errorCode'));
        $this->View()->assign([
            'orders' => $paginator->getIterator()->getArrayCopy(),
            'total' => $paginator->count(),
            'totalPages' => ceil($paginator->count() / $maxPerPage),
            'page' => $currentPage,
            'perPage' => $maxPerPage,
        ]);

        $this->View()->assign([
            'statusClasses' => [
                'created' => 'info',
                'declined' => 'danger',
                'shipped' => 'success',
                'paid_out' => 'success',
                'late' => 'warning',
                'complete' => 'success',
                'canceled' => 'danger',
            ],
        ]);
    }
    /**
     * Retrieves the current order status from mondu.
     *
     * @return void
     */
    public function invoiceAction()
    {
        $invoiceId = $this->Request()->getParam('invoice_id');
        $orderId = $this->Request()->getParam('order_id');
        $shopwareOrder = $this->getModelManager()->find(Order::class, $orderId);

        if ($shopwareOrder === null) {
            $this->redirect(['controller' => 'MonduOverview', 'action' => 'index']);

            return;
        }
        try {
            $invoice = $this->orderHelper->getMonduOrderInvoice($shopwareOrder->getTransactionId(), $invoiceId);
        } catch (\Exception $e) {
            $this->redirect(['controller' => 'MonduOverview', 'action' => 'index', 'errorCode' => 404]);

            return;
        }
        $this->View()->assign('monduInvoice', $invoice);
        $this->View()->assign('shopwareOrder', $shopwareOrder);
    }
    /**
     * Retrieves the current order status from mondu.
     *
     * @return void
     */
    public function orderAction()
    {
        $orderId = $this->Request()->getParam('order_id');
        $shopwareOrder = $this->getModelManager()->find(Order::class, $orderId);

        if ($shopwareOrder === null) {
            $this->redirect(['controller' => 'MonduOverview', 'action' => 'index']);

            return;
        }

        try {
            $order = $this->orderHelper->getMonduOrder($shopwareOrder->getTransactionId());
            if(!$order) {
                throw new \Exception('not found');
            }
            $invoices = $this->orderHelper->getMonduOrderInoices($shopwareOrder->getTransactionId());
        } catch (\Exception $e) {
            $this->redirect(['controller' => 'MonduOverview', 'action' => 'index', 'errorCode' => '404']);
            return;
        }

        $order['real_price'] = $order['real_price'] / 100;
        $this->View()->assign('monduOrder', $order);
        $this->View()->assign('monduInvoices', $invoices);
        $this->View()->assign('shopwareOrder', $shopwareOrder);
    }

    /**
     * Mark current order as shipped.
     *
     * @return void
     */
    public function shipOrderAction()
    {
        //not implemented
    }

    public function refundOrderAction()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $orderId = $this->Request()->getParam('order_id');
        $invoiceId = $this->Request()->getParam('invoice_id');
        $amount = floatval(str_replace(',', '.', $this->Request()->getParam('amount'))) * 100;

        $order = $this->getModelManager()->find(Order::class, $orderId);

        try {
            $this->orderHelper->createCreditMemo($invoiceId, $amount, substr(md5(mt_rand()), 0, 7));
            $success = true;
            $partly = true;
            $message = 'Credit memo created';
        } catch (RequestException $e) {
            $success = false;
            $partly = true;
            $message = 'Couldnt create a credit note';
        }

        // Return result message
        $this->View()->assign([
            'success' => $success,
            'partly' => $partly,
            'message' => $message
        ]);
    }

    public function cancelInvoiceAction() {
        $this->Front()->Plugins()->Json()->setRenderer();
        $invoiceId = $this->Request()->getParam('invoice_id');
        $orderId = $this->Request()->getParam('order_id');

        try {
            $this->orderHelper->cancelInvoice($orderId, $invoiceId);
            $success = true;
            $message = 'Order canceled';
        } catch (\Exception $e) {
            $success = false;
            $message = 'Could not cancel the invoice';
        }

        $this->View()->assign([
            'success' => $success,
            'message' => $message
        ]);
    }
    /**
     * Full Cancelation of an order in mondu.
     *
     * @return void
     */
    public function cancelOrderAction()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $orderId = $this->Request()->getParam('order_id');
        $order = $this->getModelManager()->find(Order::class, $orderId);
        try {
            $response = $this->orderHelper->cancelOrder($order);

            $success = true;
            $message = 'Order canceled';

        } catch (\Exception $e) {
            $success = false;
            $message = 'Something went wrong';
        }

        $this->View()->assign([
            'success' => $success,
            'message' => $message
        ]);
    }

    /**
     * Whitelisted CSRF actions.
     *
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index', 'order', 'invoice'];
    }
}
