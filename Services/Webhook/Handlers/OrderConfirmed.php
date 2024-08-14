<?php

namespace Mond1SWR5\Services\Webhook\Handlers;

use Mond1SWR5\Helpers\OrderHelper;
use Mond1SWR5\Services\PaymentStatusService;
use Mond1SWR5\Services\Webhook\WebhookStruct as Webhook;
use Mond1SWR5\Services\Webhook\WebhookHandler;
use Shopware\Models\Order\Status;

class OrderConfirmed implements WebhookHandler
{
    private $paymentStatusService;
    private $orderHelper;

    public function __construct(PaymentStatusService $paymentStatusService, OrderHelper $orderHelper)
    {
        $this->paymentStatusService = $paymentStatusService;
        $this->orderHelper = $orderHelper;
    }

    public function getEventType()
    {
        return 'order/confirmed';
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(Webhook $webhook)
    {
        $this->paymentStatusService->updatePaymentStatus(
            $webhook->getOrderUid(),
            Status::ORDER_STATE_IN_PROCESS,
            Status::GROUP_STATE
        );
        $this->paymentStatusService->updatePaymentStatus(
            $webhook->getOrderUid(),
            Status::PAYMENT_STATE_COMPLETELY_PAID,
            Status::GROUP_PAYMENT,
            $webhook->getOrderState()
        );
        $this->orderHelper->setOrderViban($webhook->getExternalReferenceId(), $webhook->getOrderUid(), $webhook->getViban());
        $this->orderHelper->setOrderStatus($webhook->getOrderUid());
    }
}
