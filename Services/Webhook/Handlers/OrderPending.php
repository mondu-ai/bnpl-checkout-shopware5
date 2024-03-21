<?php

namespace Mond1SWR5\Services\Webhook\Handlers;

use Mond1SWR5\Helpers\OrderHelper;
use Mond1SWR5\Services\PaymentStatusService;
use Mond1SWR5\Services\Webhook\WebhookStruct as Webhook;
use Mond1SWR5\Services\Webhook\WebhookHandler;
use Shopware\Models\Order\Status;

class OrderPending implements WebhookHandler
{
    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    public function __construct(PaymentStatusService $paymentStatusService)
    {
        $this->paymentStatusService = $paymentStatusService;
    }

    public function getEventType()
    {
        return 'order/pending';
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(Webhook $webhook)
    {
        $this->paymentStatusService->updatePaymentStatus(
            $webhook->getExternalReferenceId(),
            Status::ORDER_STATE_IN_PROCESS,
            Status::GROUP_STATE
        );
        $this->paymentStatusService->updatePaymentStatus(
            $webhook->getExternalReferenceId(),
            Status::PAYMENT_STATE_REVIEW_NECESSARY,
            Status::GROUP_PAYMENT,
            $webhook->getOrderState()
        );
    }
}
