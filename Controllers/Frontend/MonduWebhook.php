<?php

use Mond1SWR5\Components\Exceptions\OrderNotFoundException;
use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Services\Webhook\Handlers\InvoicePaid;
use Mond1SWR5\Services\Webhook\Handlers\OrderCanceled;
use Mond1SWR5\Services\Webhook\Handlers\OrderConfirmed;
use Mond1SWR5\Services\Webhook\Handlers\OrderDeclined;
use Mond1SWR5\Services\Webhook\WebhookStruct as Webhook;
use Shopware\Components\CSRFWhitelistAware;
use Mond1SWR5\Services\Webhook\WebhookService;
use Mond1SWR5\Services\Webhook\Handlers\OrderPending;

class Shopware_Controllers_Frontend_MonduWebhook extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @var WebhookService
     */
    private $webhookService;

    /**
     * @var ConfigService
     */
    private $configService;
    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions() {
        return [
            'execute',
        ];
    }

    /**
     * @return void
     */
    public function preDispatch() {
        $this->webhookService = $this->get('Mond1SWR5\Services\Webhook\WebhookService');
        $paymentStatusService = $this->get('Mond1SWR5\Services\PaymentStatusService');
        $orderHelper = $this->get('Mond1SWR5\Helpers\OrderHelper');

        $this->configService = $this->get('Mond1SWR5\Components\PluginConfig\Service\ConfigService');

        $this->webhookService->registerWebhooks([
            new OrderPending($paymentStatusService),
            new OrderCanceled($paymentStatusService),
            new OrderDeclined($paymentStatusService),
            new OrderConfirmed($paymentStatusService, $orderHelper),
            new InvoicePaid($paymentStatusService)
        ]);
    }

    /**
     * @return void
     */
    public function executeAction() {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $postData = $this->request->getRawBody();

        if (!\is_string($postData) || $postData === '') {
            $this->responseWithError(422, 'Unprocessed entity');
            return;
        }

        $postData = \json_decode($postData, true);
        $webhookData = Webhook::fromArray($postData);

        if ($postData === null) {
            $this->responseWithError(422, 'Unprocessed entity');
            return;
        }

        if (!$this->validateSignature($this->request)) {
            $this->responseWithError(400, 'Signature Mismatch');
            return;
        }

        $topic = $postData['topic'];

        if (!$this->webhookService->handlerExists($topic)) {
            $this->responseWithError(200, 'Not implemented');
            return;
        }

        try {
            $this->webhookService->getWebhookHandler($topic)->invoke($webhookData);
            $this->response->setBody(\json_encode(['error' => 0, 'message' => 'ok']));
        } catch (OrderNotFoundException $e) {
            $this->responseWithError(404, 'Order not found');
            return;
        } catch (RuntimeException $e) {
            $this->responseWithError(404, 'Order not found');
            return;
        }
    }

    private function validateSignature(Enlight_Controller_Request_RequestHttp $request) {
        $monduSignature = $request->getHeader('X-Mondu-Signature');
        $webhookSecret = $this->configService->getWebhookSecret();
        $content = $request->getRawBody();
        $localSignature = hash_hmac('sha256', $content, $webhookSecret);
        if($monduSignature !== $localSignature) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    private function responseWithError($code = 400, $message = 'Bad Request') {
        $this->response->setHttpResponseCode($code);
        $this->response->setBody(json_encode(
            [
                'error' => 1,
                'message' => $message
            ]
        ));
        $this->response->setHeader('Content-Type', 'Application/json');
    }
}
