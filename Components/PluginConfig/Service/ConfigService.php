<?php

namespace Mond1SWR5\Components\PluginConfig\Service;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Components\Plugin\ConfigWriter;
use Shopware\Components\Routing\Context;
use Shopware\Components\Routing\Router;
use Shopware\Models\Plugin\Plugin;
use Shopware\Models\Shop\Shop;
use Zend_Cache_Core as Cache;

class ConfigService {
    const API_URL = 'https://api.mondu.ai/api/v1';
    const WIDGET_URL = 'https://checkout.mondu.ai/widget.js';
    const SANDBOX_API_URL = 'https://api.demo.mondu.ai/api/v1';
    const SANDBOX_WIDGET_URL = 'https://checkout.demo.mondu.ai/widget.js';

    /**
     * @var CachedConfigReader
     */
    private $configReader;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var Cache
     */

    private $cache;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var $swConfig
     */
    private $swConfig;

    /**
     * @param CachedConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param Cache $cache
     * @param ModelManager $modelManager
     * @param Router $router
     * @param \Shopware_Components_Config $swConfig
     */
    public function __construct(
        CachedConfigReader $configReader,
        ConfigWriter $configWriter,
        Cache $cache,
        ModelManager $modelManager,
        Router $router,
        \Shopware_Components_Config $swConfig
    )
    {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->cache = $cache;
        $this->modelManager = $modelManager;
        $this->router = $router;
        $this->swConfig = $swConfig;
    }

    public function isSandbox(): bool
    {
        $config = $this->getPluginConfiguration();
        return $config['mondu/mode/sandbox'] ?? false;
    }

    public function extendInvoiceTemplate(): bool
    {
        $config = $this->getPluginConfiguration();
        return $config['mondu/mode/extend_invoice_template'] ?? false;
    }

    public function getInvoiceCreateState(): string
    {
        $config = $this->getPluginConfiguration();

        return $config['mondu/mode/invoice_create_state'] ?? '';
    }

    public function getWebhookSecret(): string
    {
        $config = $this->getPluginConfiguration();

        return $config['mondu/credentials/webhook_secret'] ?? '';
    }

    public function getB2BEnabled():bool
    {
        $config = $this->getPluginConfiguration();

        return $config['mondu/mode/b2b'] ?? false;
    }

    public function getApiToken(): string
    {
        $config = $this->getPluginConfiguration();

        return $config['mondu/credentials/api_token'] ?? '';
    }

    public function getBaseApiUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_API_URL : self::API_URL;
    }

    public function getWidgetUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_WIDGET_URL : self::WIDGET_URL;
    }

    public function getApiUrl($url): string
    {
        return $this->getBaseApiUrl().'/'.$url;
    }

    public function getTokenUrl(): string
    {
        return Shopware()->Front()->Router()->assemble([
            'controller' => 'MonduToken',
            'action' => 'token'
        ]);
    }

    public function getUnsetUrl(): string
    {
        return Shopware()->Front()->Router()->assemble([
            'controller' => 'MonduToken',
            'action' => 'unset'
        ]);
    }

    public function getWebhookUrl(): string
    {
        $defaultShop = $this->modelManager->getRepository(Shop::class)->getDefault();
        $oldContext = $this->router->getContext();
        $this->router->setContext(Context::createFromShop($defaultShop, $this->swConfig));

        $route =  Shopware()->Front()->Router()->assemble([
            'controller' => 'MonduWebhook',
            'action' => 'execute'
        ]);

        $this->router->setContext($oldContext);

        return $route;
    }

    public function getValidateInvoice(): bool
    {
        $config = $this->getPluginConfiguration();

        return $config['mondu/mode/validate_invoice'] ?? true;
    }

    private function getPluginConfiguration(): array
    {
        return $this->configReader->getByPluginName('Mond1SWR5') ?: [];
    }

    public function isCronEnabled() {
        $config = $this->getPluginConfiguration();

        return $config['mondu/mode/cron'] ?? false;
    }

    public function getAdditionalInvoiceDocuments(): array
    {
        $config = $this->getPluginConfiguration();

        $additionalInvoiceDocuments = $config['mondu/mode/additional_documents'];

        return $additionalInvoiceDocuments ? array_map('trim', explode(',', $additionalInvoiceDocuments)) : [];
    }

    public function setWebhookSecret($secret) {
        try {
            $pluginRepository = $this->modelManager->getRepository(Plugin::class);

            /** @var Plugin|null $plugin */
            $plugin = $pluginRepository->findOneBy(['name' => 'Mond1SWR5']);
            $shopRepository = $this->modelManager->getRepository(Shop::class);

            /**
             * @var Shop
             */
            $shop = $shopRepository->find($shopRepository->getActiveDefault()->getId());

            $this->configWriter->saveConfigElement($plugin, 'mondu/credentials/webhook_secret', $secret, $shop);
        } catch (\Exception $e) {
            //fail silently
            return;
        }
    }
}
