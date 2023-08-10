<?php

namespace Mond1SWR5\Helpers;

use Doctrine\Common\Cache\Cache;
use Mond1SWR5\Components\MonduApi\Service\MonduClient;
use Mond1SWR5\Enum\PaymentMethods;

class PaymentHelper {
    /**
     * @var MonduClient
     */
    private $monduClient;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(
        MonduClient $monduClient,
        Cache $cache
    ) {
        $this->monduClient = $monduClient;
        $this->cache = $cache;
    }

    private function getPaymentMethods(): array
    {
        return PaymentMethods::getNames();
    }

    private function getAllowedPaymentMethods(): array
    {
        $mapping = PaymentMethods::MAPPING;
        try {
            if($this->cache->contains('mondu_payment_methods')) {
                return $this->cache->fetch('mondu_payment_methods');
            }
            $paymentMethods = $this->monduClient->getPaymentMethods();
            $result = [];

            foreach ($paymentMethods as $value) {
                $result[] = $mapping[$value['identifier']] ?? '';
            }
            $this->cache->save('mondu_payment_methods', $result, 3600);
            return $result;
        } catch (\Exception $e) {
            $this->cache->save('mondu_payment_methods', [], 3600);
            return [];
        }
    }

    public function filterPaymentMethods($paymentMethods): array
    {
        $result = [];
        $allowed = $this->getAllowedPaymentMethods();
        foreach ($paymentMethods as $key => $value) {
            if (!PaymentMethods::getMethod($value['name'])) {
                $result[$key] = $value;
                continue;
            }

            if(in_array($value['name'], $allowed)) {
               $result[$key] = $value;
            }
        }
        return $result;
    }
}
