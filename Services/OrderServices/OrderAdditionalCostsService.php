<?php

namespace Mond1SWR5\Services\OrderServices;

class OrderAdditionalCostsService extends AbstractOrderAdditionalCostsService {
    /**
     * {@inheritDoc}
     */
    public function getAdditionalCostsCentsFromOrderVariables($sOrderVariables): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getAdditionalCostsCentsFromOrder($order): int
    {
        /**
         * Check if $order instanceof Order and get the additional costs associated with the order
         */
        return 0;
    }
}
