<?php

use Mond1SWR5\Helpers\WebhookHelper;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Plugin\DBALConfigReader;
use Symfony\Component\HttpFoundation\JsonResponse;

class Shopware_Controllers_Backend_MonduConfig extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @var DBALConfigReader
     */
    private $configReader;

    /**
     * @var string
     */
    private $pluginName;

    /**
     * @var Shopware_Components_Snippet_Manager|void|null
     */

    private $snippetManager;

     /**
     * @var WebhookHelper
    */
    private $webhookHelper;

    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);
        $this->configReader = $this->container->get('shopware.plugin.config_reader');
        $this->snippetManager = $this->container->get('snippets');
        $this->pluginName = $this->container->getParameter('mond1_s_w_r5.plugin_name');
        $this->webhookHelper = $this->container->get(WebhookHelper::class);
    }

    public function getWhitelistedCSRFActions()
    {
        return ['test'];
    }

    public function testAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        $messageNamespace = $this->snippetManager->getNamespace('backend/mondu/messages');

        $returnData = [];
        $config = $this->configReader->getByPluginName($this->pluginName);

        $isSandbox = $config['mondu/mode/sandbox'] ?? null;
        $apiToken = $config['mondu/credentials/api_token'] ?? null;
        if ($isSandbox === null || $apiToken === null) {
            $returnData['message'] = 'Please save configuration before testing';
            $returnData['success'] = false;
        } else {
            try {
                $this->webhookHelper->getWebhookSecret();
                $this->webhookHelper->registerWebhooksIfNotRegistered();
                $returnData['success'] = true;
                $returnData['message'] = 'Credentials are valid. Successfully registered webhooks';
            } catch (\Exception $e) {
                $returnData['success'] = false;
                $returnData['message'] = $e->getMessage();
            }
        }

        $returnData['statusText'] = $messageNamespace->get($returnData['success'] ? 'StatusSuccess' : 'StatusFailed');
        (new JsonResponse($returnData))->send();
    }
}
