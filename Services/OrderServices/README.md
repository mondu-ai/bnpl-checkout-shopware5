## Services

### How to decorate a service

1. Create a class that extends the abstract class of the service you want to decorate

```php
<?php

namespace MonduExtendBuyerFees\Services;

use Mond1SWR5\Services\OrderServices\AbstractOrderAdditionalCostsService;

class OrderAdditionalCostsDecorator extends AbstractOrderAdditionalCostsService {
    /**
     * @var AbstractOrderAdditionalCostsService
     */
    protected $orderAdditionalCostsService;

    public function __construct(AbstractOrderAdditionalCostsService $orderAdditionalCostsService) {
        $this->orderAdditionalCostsService = $orderAdditionalCostsService;
    }

    /**
     * Get the additional costs associated with the order (during the checkout)
     *
     * @param $sOrderVariables
     * @return int
     */
    public function getAdditionalCostsCentsFromOrderVariables($sOrderVariables): int
    {
        // $original = $this->orderAdditionalCostsService->getAdditionalCostsCentsFromOrderVariables($sOrderVariables);
        return 0;
    }


    /**
     * Check if $order instanceof Order and get the additional costs associated with the order (in admin panels)
     */
    public function getAdditionalCostsCentsFromOrder($order): int
    {
        // $original = $this->orderAdditionalCostsService->getAdditionalCostsCentsFromOrder($order);
        return 0;
    }
}
```

2. Map the decorator class in services.xml

```xml
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
        <service
                id="MonduExtendBuyerFees\Services\OrderAdditionalCostsDecorator"
                decorates="Mond1SWR5\Services\OrderServices\AbstractOrderAdditionalCostsService">
            <argument type="service" id="MonduExtendBuyerFees\Services\OrderAdditionalCostsDecorator.inner" />
        </service>
    </services>
</container>

```

You can get the boilerplate plugin for the quickstart [here](https://github.com/tikohov20/MonduSWR5Extension).