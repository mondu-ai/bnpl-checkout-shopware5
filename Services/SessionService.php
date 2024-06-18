<?php

namespace Mond1SWR5\Services;

use Enlight_Components_Session_Namespace;
use Mond1SWR5\Components\MonduApi\Service\MonduClient;
use Mond1SWR5\Helpers\OrderHelper;

class SessionService {
    
    private $orderHelper;
    private $monduClient;
    private $session;

    public function __construct(OrderHelper $orderHelper, MonduClient $monduClient, Enlight_Components_Session_Namespace $session)
    {
        $this->orderHelper = $orderHelper;
        $this->monduClient = $monduClient;
        $this->session = $session;
    }

    public function createCheckoutSession($returnUrl, $cancelUrl, $declineUrl, $paymentMethod = 'invoice'): array
    {
        try {
            $this->reserveOrderNumber();
            $monduOrder = $this->monduClient->createOrder(
                $this->getOrder(),
                $returnUrl,
                $cancelUrl,
                $declineUrl,
                $paymentMethod
            );

            $checkoutUrl = $monduOrder['hosted_checkout_url'];
            $monduToken = $monduOrder['token'];

            return [$checkoutUrl, $monduToken];
        } catch(\Exception $e) {
            return [null, null];
        }
    }

    private function reserveOrderNumber() {
        if($this->session->offsetExists('sOrderVariables')) {
            $variables = $this->session->offsetGet('sOrderVariables');
            if(empty($variables['sOrderNumber'])) {
                $orderNumber = uniqid('M_SW5_');
                $variables['sOrderNumber'] = $orderNumber;
                $this->session->offsetSet('sOrderVariables', $variables);
            }
        }
        return $orderNumber;
    }

    public function getOrder() {
        $orderVariables = Shopware()->Session()->get('sOrderVariables');
        return $this->orderHelper->getOrderFromOrderVariables($orderVariables);
    }

    public function setData($key, $value = null)
    {
        $session = $this->session->get('Mondu', []);
        if ($value) {
            $session[$key] = $value;
        } else {
            unset($session[$key]);
        }
        $this->session->offsetSet('Mondu', $session);
    }

    public function getData($key, $default = null)
    {
        $session = $this->session->get('Mondu');

        return $session[$key] ?? $default;
    }

    public function unsetData() {
        $this->setData('token');
        $this->setData('checkoutUrl');
        $this->setData('cartHash');
    }
}
