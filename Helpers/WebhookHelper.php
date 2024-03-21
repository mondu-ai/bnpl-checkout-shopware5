<?php

namespace Mond1SWR5\Helpers;

use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Components\MonduApi\Service\MonduClient;

class WebhookHelper
{

    /**
     * @var MonduClient
     */
    private $monduClient;

    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(
        MonduClient $monduClient,
        ConfigService $configService
    )
    {
        $this->monduClient = $monduClient;
        $this->configService = $configService;
    }

    public function getWebhookSecret()
    {
        $secret = $this->monduClient->getWebhookSecret();
        $this->configService->setWebhookSecret($secret);
    }

    /**
     * @throws \Shopware\Components\HttpClient\RequestException
     */
    public function registerWebhooksIfNotRegistered()
    {
        $webhooks = $this->monduClient->getWebhooks();

        $registeredTopics = array_map(function ($webhook) {
            return $webhook['topic'];
        }, $webhooks);

        $requiredTopics = ['order', 'invoice'];
        foreach ($requiredTopics as $topic) {
            if (!in_array($topic, $registeredTopics)) {
                $this->monduClient->registerWebhooks($topic);
            }
        }
    }

}
