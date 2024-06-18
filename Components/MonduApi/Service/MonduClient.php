<?php

namespace Mond1SWR5\Components\MonduApi\Service;
use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Helpers\ModuleHelper;
use Shopware\Components\HttpClient\GuzzleFactory;
use Shopware\Components\HttpClient\RequestException;
use Shopware\Components\HttpClient\GuzzleHttpClient;
use Shopware\Components\HttpClient\HttpClientInterface;
use Shopware\Components\Logger;

class MonduClient {
    private $config;
    private $logger;
    /**
     * @var HttpClientInterface
     */
    private $restClient;

    /**
     * @var ModuleHelper
     */
    private $moduleHelper;

    public function __construct(
        ConfigService $configService,
        GuzzleFactory $factory,
        Logger $logger,
        ModuleHelper $moduleHelper
    ) {
        $this->config = $configService;
        $this->restClient = new GuzzleHttpClient($factory);
        $this->logger = $logger;
        $this->moduleHelper = $moduleHelper;
    }

    /**
     * @throws RequestException
     */
    public function createOrder($order, $returnUrl, $cancelUrl, $declineUrl, $paymentMethod) {
        $order['payment_method'] = $paymentMethod;
        $order['success_url'] = $returnUrl;
        $order['cancel_url'] = $cancelUrl;
        $order['declined_url'] = $declineUrl;

        $body = json_encode($order);

        $response = $this->sendRequest('post', 'orders', $body, 'CREATE_ORDER');
        return $response['order'];
    }

    /**
     * @param $orderUid
     * @return array|null
     * @throws RequestException
     */
    public function confirmMonduOrder($orderUid, $orderNumber = null): ?array {
        $body = null;
        if ($orderNumber) {
            $body = [
                'external_reference_id' => $orderNumber
            ];
        }
        $response = $this->sendRequest('post', 'orders/'. $orderUid . '/confirm', json_encode($body), 'CONFIRM_ORDER');
        return $response['order'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function getMonduOrder($orderUid): ?array {
        $response = $this->sendRequest('get', 'orders/' . $orderUid, null, 'GET_ORDER');
        return $response['order'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function getOrders() {
        return $this->sendRequest('get', 'orders', null, 'GET_ORDERS');
    }

    /**
     * @throws RequestException
     */
    public function cancelOrder($orderUid): ?string {
        $response = $this->sendRequest('post', 'orders/' . $orderUid .'/cancel', null, 'CANCEL_ORDER');

        return $response['order']['state'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function updateOrder($order, $orderUid) {
        $body = json_encode($order);
        $response = $this->sendRequest('post', 'orders/' . $orderUid . '/adjust', $body, 'ADJUST_ORDER');
        return $response['order'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function updateExternalInfoOrder($orderUid, $payload) {
        $body = json_encode($payload);
        $response = $this->sendRequest('post', 'orders/' . $orderUid . '/update_external_info', $body, 'UPDATE_EXTERNAL_INFO');
        return $response['order'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function createOrderInvoice($orderUid, $payload) {
        $body = json_encode($payload);
        $response = $this->sendRequest('post', 'orders/' . $orderUid . '/invoices', $body, 'CREATE_INVOICE');
        return $response['invoice'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function getMonduOrderInvoices($orderUid) {
        $response = $this->sendRequest('get', 'orders/' . $orderUid . '/invoices', null, 'GET_ORDER_INVOICE');
        return $response['invoices'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function getMonduOrderInvoice($orderUid, $invoiceUid) {
        $response = $this->sendRequest('get', 'orders/' . $orderUid . '/invoices/' . $invoiceUid, null, 'GET_ORDER_INVOICE');
        return $response['invoice'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function cancelOrderInvoice($orderUid, $invoiceUid) {
        $response = $this->sendRequest('post', 'orders/'. $orderUid . '/invoices/' . $invoiceUid . '/cancel', null, 'CANCEL_ORDER_INVOICE');
        return $response['invoice'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function createCreditMemo($invoiceId, $amount, $reference) {
        $payload = json_encode([
            'gross_amount_cents' => $amount,
            'external_reference_id' => $reference
        ]);

        return $this->sendRequest('post', 'invoices/' . $invoiceId . '/credit_notes', $payload, 'CREATE_CREDIT_NOTE');
    }

    public function getMonduInvoiceMemos($invoiceId) {
        $response = $this->sendRequest('get', 'invoices/' . $invoiceId . '/credit_notes', null, 'GET_INVOICE_CREDIT_NOTES');
        return $response['credit_notes'] ?? null;
    }

    public function getWebhooks() {
        $response = $this->sendRequest('get', 'webhooks', null, 'GET_WEBHOOKS');
        return $response['webhooks'] ?? null;
    }

    public function registerWebhooks($topic) {
        $payload = json_encode([
            'topic' => $topic,
            'address' => $this->config->getWebhookUrl()
        ]);

        $response = $this->sendRequest('post', 'webhooks', $payload, 'REGISTER_WEBHOOK');

        return $response['webhook'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function deleteWebhook($webhookUid) {
        return $this->sendRequest('delete', 'webhooks/'.$webhookUid, null, 'DELETE_WEBHOOK');
    }

    /**
     * @throws RequestException
     */
    public function getPaymentMethods() {
        $response = $this->sendRequest('get', 'payment_methods', null, 'GET_PAYMENT_METHODS');
        return $response['payment_methods'] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function getWebhookSecret() {
        $response = $this->sendRequest('get', 'webhooks/keys', null, 'GET_WEBHOOK_SECRET');
        return $response['webhook_secret'] ?? null;
    }

    private function getHeaders(): array {
        return [
            'Content-Type' => 'application/json',
            'Api-Token' => $this->config->getApiToken(),
            'x-plugin-name' => $this->moduleHelper->getModuleNameForApi(),
            'x-plugin-version' => $this->moduleHelper->getModuleVersion()
        ];
    }

    /**
     * @throws RequestException
     */
    private function sendRequest($method, $url, $body = null, $originEvent = '', $sendEvents = true) {
        try {
            $response = null;
            switch ($method) {
                case 'post':
                    $response = $this->restClient->post($this->config->getApiUrl($url), $this->getHeaders(), $body)->getBody();
                    break;
                case 'get':
                    $response = $this->restClient->get($this->config->getApiUrl($url), $this->getHeaders())->getBody();
                    break;
                case 'delete':
                    $response = $this->restClient->delete($this->config->getApiUrl($url), $this->getHeaders())->getBody();
                    break;
                default:
                    throw new \RuntimeException('An unsupported request method was provided. The method was: ' . $method);
            }

            return \json_decode($response, true);
        } catch (RequestException $e) {
            if($sendEvents) {
                $this->sendErrorEvent($body, $e->getCode(), $e, $originEvent);
            }
            $this->logger->addRecord(400, "Exception: url: ". $this->config->getApiUrl($url) . ' body: ' . $body . ' response: ' . $e->getBody());
            throw $e;
        }
    }

    private function sendErrorEvent($body, $status, $e, $originEvent = '') {
        try {
            $envInformation = $this->moduleHelper->getEnvironmentInformation();
            $errorBody = array_merge($envInformation, [
                'response_status' => (string) $status,
                'response_body' => json_decode($e->getBody(), true) ?? [],
                'request_body' => json_decode($body, true) ?? [],
                'origin_event' => $originEvent,
                'error_trace' => $e->getTraceAsString(),
                'error_message' => $e->getMessage()
            ]);
            $this->sendRequest('post', 'plugin/events', json_encode($errorBody), '', false);
        } catch(\Error $e) {
            //fail silently
        }
    }
}
