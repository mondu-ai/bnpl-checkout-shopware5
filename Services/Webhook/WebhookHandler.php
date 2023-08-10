<?php

namespace Mond1SWR5\Services\Webhook;

use Mond1SWR5\Services\Webhook\WebhookStruct as Webhook;

interface WebhookHandler
{
    public function getEventType();

    public function invoke(Webhook $webhook);
}
