<?php

use Mond1SWR5\Enum\PaymentMethods;
use Mond1SWR5\Components\MonduApi\Service\MonduClient;
use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Helpers\OrderHelper;
use Mond1SWR5\Services\SessionService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Frontend_Mondu extends Shopware_Controllers_Frontend_Payment
{
    /**
     * Payment Status Paid Code.
     *
     * @var int
     */
    const PAYMENTSTATUSPAID = 12;

    /**
     * @var SessionService
     */
    private $sessionService;
    /**
     * @var MonduClient
     */
    private $monduClient;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var ConfigService
     */
    private $configService;

    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);
        $this->monduClient = $this->container->get(MonduClient::class);
        $this->orderHelper = $this->container->get(OrderHelper::class);
        $this->sessionService = $this->container->get(SessionService::class);
        $this->configService = $this->container->get(ConfigService::class);
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     * @throws Exception
     */
    public function indexAction()
    {
        if (!PaymentMethods::exists($this->getPaymentShortName())) {
            $this->handleError('Mondu: Unknown Payment method');
            return;
        }

        $this->redirect(['action' => 'direct']);
    }

    public function directAction()
    {
        $mapping = array_flip(PaymentMethods::MAPPING);
        $paymentMethod = $mapping[$this->getPaymentShortName()];

        [ $checkoutUrl ] = $this->sessionService->createCheckoutSession(
            $this->getReturnUrl($this->persistBasket()),
            $this->getCancelUrl(),
            $this->getDeclineUrl(),
            $paymentMethod
        );

        if(!$checkoutUrl) {
            $this->handleError('Mondu: Error during checkout, please try again');
        }

        $this->redirect($checkoutUrl);
    }

    public function cancelAction() {
        $this->sessionService->unsetData();

        $this->handleError('Mondu: Order cancelled');
    }

    public function declineAction() {
        $this->sessionService->unsetData();

        $this->handleError('Mondu: Order has been declined');
    }


    /**
     * @return void
     * @throws \Shopware\Components\HttpClient\RequestException
     *
     * @throws Exception
     */
    public function returnAction()
    {
        $signature = $this->request->signature;

        $isSignatureValid = $this->validateSignature($signature);
        if(!$isSignatureValid) {
            $this->handleError('Mondu: Unable to confirm the Order, signature mismatch');
            return;
        }

        $orderUid = $this->request->get('order_uuid');
        $orderNumber = $this->saveOrder($orderUid, $orderUid, Status::PAYMENT_STATE_OPEN);
        $monduOrder = $this->monduClient->confirmMonduOrder($orderUid, $orderNumber);

        if (!$monduOrder || !$this->isMonduOrderSuccessful($monduOrder['state'])) {
            $this->handleError('Mondu: Unable to confirm the Order');
            return;
        }

        switch ($monduOrder['state']) {
            case PaymentMethods::MONDU_STATE_CONFIRMED:
                $this->savePaymentStatus($orderUid, $orderUid, Status::PAYMENT_STATE_COMPLETELY_PAID);
                break;
            default:
                $this->savePaymentStatus($orderUid, $orderUid, Status::PAYMENT_STATE_REVIEW_NECESSARY);
        }

        $monduOrder = $this->orderHelper->getMonduOrder($orderUid);
        $repo = $this->getModelManager()->getRepository(Order::class);
        $order = $repo->findOneBy(['number' => $orderNumber]);

        $this->updateShopwareOrder($order,
            [
                'uuid' => $orderUid,
                'state' => $monduOrder['state'],
                'payment_method' => $monduOrder['payment_method'],
                'iban' => $monduOrder['bank_account']['iban'],
                'company_name' => $monduOrder['merchant']['company_name'] ?? '',
                'authorized_net_term' => $monduOrder['authorized_net_term']
            ]
        );

        $this->redirect([
            'module' => 'frontend',
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $orderUid,
        ]);
    }

    private function getReturnUrl($signature): string
    {
        return $this->Front()->Router()->assemble(['action' => 'return', 'forceSecure' => true, 'signature' => $signature]);
    }

    private function getCancelUrl(): string
    {
        return $this->Front()->Router()->assemble(['action' => 'cancel', 'forceSecure' => true]);
    }

    private function getDeclineUrl(): string
    {
        return $this->Front()->Router()->assemble(['action' => 'decline', 'forceSecure' => true]);
    }

    /**
     * @throws Exception
     */
    private function handleError($code = '_UnknownError')
    {
        $this->redirect(['controller' => 'checkout', 'action' => 'confirm', 'errorCode' => $code]);
    }

    private function validateSignature($signature): bool {
        try {
            $basket = $this->loadBasketFromSignature($signature);
            $this->verifyBasketSignature($signature, $basket);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $order
     * @param $monduOrder
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateShopwareOrder($order, $monduOrder) {
        $orderAttribute = $order->getAttribute();
        $orderAttribute->setMonduReferenceId($monduOrder['uuid']);
        $orderAttribute->setMonduState($monduOrder['state']);
        $orderAttribute->setMonduPaymentMethod($monduOrder['payment_method']);
        $orderAttribute->setMonduInvoiceIban($monduOrder['iban']);
        $orderAttribute->setMonduMerchantCompanyName($monduOrder['company_name']);
        $orderAttribute->setMonduAuthorizedNetTerm($monduOrder['authorized_net_term']);
        $this->getModelManager()->flush($orderAttribute);
    }

    /**
     * @param string $state
     * @return bool
     */
    private function isMonduOrderSuccessful(string $state): bool
    {
        return in_array($state, [PaymentMethods::MONDU_STATE_PENDING, PaymentMethods::MONDU_STATE_CONFIRMED]);
    }
}
