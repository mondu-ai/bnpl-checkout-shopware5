<?php

namespace Mond1SWR5\Services\Webhook\Handlers;

use Mond1SWR5\Services\PaymentStatusService;
use Mond1SWR5\Services\Webhook\WebhookStruct as Webhook;
use Mond1SWR5\Services\Webhook\WebhookHandler;
use Shopware\Models\Order\Status;

class OrderDeclined implements WebhookHandler
{
    private $paymentStatusService;

    public function __construct(PaymentStatusService $paymentStatusService)
    {
        $this->paymentStatusService = $paymentStatusService;
    }

    public function getEventType()
    {
        return 'order/declined';
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(Webhook $webhook)
    {
        if (!$webhook->isTemporaryExternalReferenceId()) {
            $this->paymentStatusService->updatePaymentStatus(
                $webhook->getOrderUid(),
                Status::ORDER_STATE_CANCELLED_REJECTED,
                Status::GROUP_STATE,
                $webhook->getOrderState()
            );
        }
    }
}
