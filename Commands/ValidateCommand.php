<?php

namespace Mond1SWR5\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Mond1SWR5\Helpers\WebhookHelper;
use Shopware\Components\Plugin\CachedConfigReader;

class ValidateCommand extends ShopwareCommand
{
    /**
     * @var DBALConfigReader
     */
    private $configReader;

    /**
     * @var WebhookHelper
     */
    private $webhookHelper;

    public function __construct(
        WebhookHelper $webhookHelper, 
        CachedConfigReader $configReader
    )
    {
        parent::__construct();

        $this->webhookHelper = $webhookHelper;
        $this->configReader = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sw:Mond1SWR5:validate')
            ->setDescription('Validate api_key and get webhook token.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> does validation call to bnpl API and sets received webhooks.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = "";
        $config = $this->configReader->getByPluginName("Mond1SWR5");

        $isSandbox = $config['mondu/mode/sandbox'] ?? null;
        $apiToken = $config['mondu/credentials/api_token'] ?? null;

        if ($isSandbox === null || $apiToken === null) {
            $message = 'Please save configuration before testing';
        } else {
            try {
                $this->webhookHelper->getWebhookSecret();
                $this->webhookHelper->registerWebhooksIfNotRegistered();
                $message = 'Credentials are valid. Successfully registered webhooks';
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
        }
        echo "$message\n";
    }
}
