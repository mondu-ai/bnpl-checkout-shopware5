<?php

namespace Mond1SWR5\Services\Webhook\Handlers;

use Mond1SWR5\Services\PaymentStatusService;
use Mond1SWR5\Services\Webhook\WebhookStruct as Webhook;
use Mond1SWR5\Services\Webhook\WebhookHandler;
use Shopware\Models\Order\Status;

class InvoicePaid implements WebhookHandler
{
    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    /**
     * @param PaymentStatusService $paymentStatusService
     */
    public function __construct(PaymentStatusService $paymentStatusService)
    {
        $this->paymentStatusService = $paymentStatusService;
    }

    public function getEventType()
    {
        return 'invoice/paid';
    }

    public function invoke(Webhook $webhook)
    {
        $this->paymentStatusService->updatePaymentStatus(
            $webhook->getOrderUid(),
            Status::PAYMENT_STATE_COMPLETELY_PAID,
            STATUS::GROUP_PAYMENT,
            $webhook->getOrderState()
        );
    }
}
