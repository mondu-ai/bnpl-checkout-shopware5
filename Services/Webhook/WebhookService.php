<?php

namespace Mond1SWR5\Services\Webhook;

use Mond1SWR5\Services\Webhook\WebhookHandler;

class WebhookService
{
    /**
     * @var (WebhookHandler|null)[]
     */
    private $registeredWebhooks;

    /**
     * @throws WebhookException
     */
    public function registerWebhook(WebhookHandler $webhook)
    {
        if ($this->registeredWebhooks[$webhook->getEventType()] !== null) {
            throw new \Exception('The specified even is already registered.');
        }

        $this->registeredWebhooks[$webhook->getEventType()] = $webhook;
    }

    /**
     * @param WebhookHandler[] $webhooks
     */
    public function registerWebhooks(array $webhooks)
    {
        foreach ($webhooks as $webhook) {
            $this->registerWebhook($webhook);
        }
    }

    /**
     * @see WebhookEventTypes
     *
     * @param string $eventType
     *
     * @throws \Exception
     *
     * @return WebhookHandler
     */
    public function getWebhookHandler($eventType)
    {
        if ($this->registeredWebhooks[$eventType] === null) {
            throw new \Exception('Invalid topic');
        }

        return $this->registeredWebhooks[$eventType];
    }

    /**
     * @see WebhookEventTypes
     *
     * @param string $eventType
     *
     * @return bool
     */
    public function handlerExists($eventType)
    {
        return $this->registeredWebhooks[$eventType] !== null;
    }

    /**
     * @return WebhookHandler[]
     */
    public function getWebhookHandlers()
    {
        return $this->registeredWebhooks;
    }
}
