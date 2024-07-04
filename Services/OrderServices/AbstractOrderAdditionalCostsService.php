<?php

namespace Mond1SWR5\Services\OrderServices;

use Shopware\Models\Order\Order;

abstract class AbstractOrderAdditionalCostsService {

    /**
     * Additional costs associated with order in cents from sOrderVariables (during checkout)
     *
     * @param $sOrderVariables mixed
     * @return int
     */
    abstract public function getAdditionalCostsCentsFromOrderVariables($sOrderVariables): int;

    /**
     * Additional costs associated with order in cents from order (in admin panel)
     *
     * @param null|mixed|Order $order
     * @return int
     */
    abstract public function getAdditionalCostsCentsFromOrder($order): int;
}
