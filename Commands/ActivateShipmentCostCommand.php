<?php

namespace Mond1SWR5\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Mond1SWR5\Services\ShopwareShipmentService;

class ActivateShipmentCostCommand extends ShopwareCommand
{
    /**
     * @var ShopwareShipmentService
     */
    private $shopwareShipmentService;

    public function __construct(
        ShopwareShipmentService $shopwareShipmentService
    )
    {
        parent::__construct();

        $this->shopwareShipmentService = $shopwareShipmentService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sw:Mond1SWR5:activate:shipment:cost')
            ->setDescription('Activates Mondu as a payment method in shipment costs.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> is activating Mondu as a payment method in first 20 shipment costs.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shipmentCosts = $this->shopwareShipmentService->getShipmentCosts();
        
        $shipmentPaymentMethods = $this->shopwareShipmentService->getShipmentPaymentMethods();
        $monduShipmentPaymentMethods = array_filter($shipmentPaymentMethods, function ($paymentMethod) {
            return str_contains($paymentMethod["name"], "mondu");
        });
        
        foreach ($shipmentCosts as &$shipmentCost) {
            $shouldUpdate = false;
            echo "Checking mondu payments in {$shipmentCost["name"]}\n";
            foreach ($monduShipmentPaymentMethods as &$monduePaymentMethod) {
                if (
                    $this->array_any(function ($shipmentPaymentMethod) use(&$monduePaymentMethod) {
                        return $shipmentPaymentMethod["name"] === $monduePaymentMethod["name"];
                 }, $shipmentCost["payments"])) {
                    continue;
                }
                $shouldUpdate = true;
                echo "Adding {$monduePaymentMethod["name"]} to {$shipmentCost["name"]}\n";
                array_push($shipmentCost["payments"], $monduePaymentMethod);
            }
            if ($shouldUpdate) {
                $message = $this->shopwareShipmentService->updateShippingCost($shipmentCost);
                echo $message;
            } else {
                echo "Inactive mondu payment methods were not found in {$shipmentCost["name"]}\n";
            }
        }
    }

    private function array_any(callable $fn, array $array) {
        foreach ($array as $value) {
            if($fn($value)) {
                return true;
            }
        }
        return false;
    }
}
