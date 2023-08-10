<?php

namespace Mond1SWR5\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Mond1SWR5\Services\ShopwarePaymentService;

class ActivatePaymentCommand extends ShopwareCommand
{
    /**
     * @var ShopwarePaymentService
     */
    private $shopwarePaymentService;

    public function __construct(
        ShopwarePaymentService $shopwarePaymentService
    )
    {
        parent::__construct();

        $this->shopwarePaymentService = $shopwarePaymentService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sw:Mond1SWR5:activate:payment')
            ->setDescription('Activates Mondu as a payment method.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> is activating all mondu payment methods.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paymentMethods = $this->shopwarePaymentService->getPaymentMethods();
        $monduPaymentMethods = array_filter($paymentMethods, function ($paymentMethod) {
            return str_contains($paymentMethod["name"], "mondu");
        });

        foreach ($monduPaymentMethods as &$paymentMethod) {
            $paymentMethod["active"] = true;
            $this->shopwarePaymentService->updatePaymentMethod($paymentMethod);
            echo "Activated {$paymentMethod["name"]} successfully\n";
        }
    }
}
