<?php

namespace Mond1SWR5\Helpers;

use Doctrine\Common\Cache\Cache;
use Mond1SWR5\Components\MonduApi\Service\MonduClient;
use Mond1SWR5\Enum\PaymentMethods;

class CustomerHelper
{
    const CUSTOMER_GROUP_USE_GROSS_PRICES = 'customerGroupUseGrossPrices';

    public function usesGrossPrice(array $customer)
    {
        return (bool) $customer['additional']['show_net'];
    }

    public function chargeVat(array $customer)
    {
        return (bool) $customer['additional']['charge_vat'];
    }

    public function hasNetPriceCaluclationIndicator(array $customer)
    {
        if (!empty($customer['additional']['countryShipping']['taxfree'])) {
            return true;
        }

        if (empty($customer['additional']['countryShipping']['taxfree_ustid'])) {
            return false;
        }

        if (!empty($customer['shippingaddress']['ustid'])
            && !empty($customer['additional']['country']['taxfree_ustid'])) {
            return true;
        }

        if (empty($customer['shippingaddress']['ustid'])) {
            return false;
        }

        if ($customer[self::CUSTOMER_GROUP_USE_GROSS_PRICES]) {
            return false;
        }

        return true;
    }
}