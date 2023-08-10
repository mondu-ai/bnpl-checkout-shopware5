<?php

namespace Mond1SWR5\Helpers;

class CartHelper
{
    /**
     * @var CustomerHelper
     */
    private $customerHelper;

    public function __construct(CustomerHelper $customerHelper)
    {
        $this->customerHelper = $customerHelper;
    }

    /**
     * @return string
     */
    public function getTotalAmount(array $cart, array $customer)
    {
        // Case 1: Show gross prices in shopware and don't exclude country tax
        if ($this->customerHelper->usesGrossPrice($customer) && !$this->customerHelper->hasNetPriceCaluclationIndicator($customer)) {
            return $cart['AmountNumeric'];
        }

        // Case 2: Show net prices in shopware and don't exclude country tax
        if (!$this->customerHelper->usesGrossPrice($customer) && !$this->customerHelper->hasNetPriceCaluclationIndicator($customer)) {
            if ($this->customerHelper->chargeVat($customer)) {
                return $cart['AmountWithTaxNumeric'];
            }

            return $cart['AmountNetNumeric'];
        }

        // Case 3: No tax handling at all, just use the net amounts.
        return $cart['AmountNetNumeric'];
    }

    public function getShippingAmount(array $cart, array $customer)
    {
        if ($this->customerHelper->usesGrossPrice($customer) && !$this->customerHelper->hasNetPriceCaluclationIndicator($customer)) {
            return $cart['sShippingcostsWithTax'];
        }

        if (!$this->customerHelper->usesGrossPrice($customer) && !$this->customerHelper->hasNetPriceCaluclationIndicator($customer)) {
            if ($this->customerHelper->chargeVat($customer)) {
                return $cart['sShippingcostsWithTax'];
            } else {
                return $cart['sShippingcostsNet'];
            }
        }

        return $cart['sShippingcostsNet'];
    }
}
