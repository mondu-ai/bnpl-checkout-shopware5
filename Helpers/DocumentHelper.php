<?php

namespace Mond1SWR5\Helpers;

use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Routing\Context;
use Shopware\Components\Routing\Router;
use Shopware\Models\Order\Document\Document;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

class DocumentHelper
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(
        ModelManager $modelManager,
        Router $router,
        \Shopware_Components_Config $config,
        ConfigService $configService
    ) {
        $this->modelManager = $modelManager;
        $this->router = $router;
        $this->config = $config;
        $this->configService = $configService;
    }

    public function getInvoiceUrlForOrder(Order $order)
    {
        /** @var Document $document */
        foreach ($order->getDocuments() as $document) {
            if ($this->isInvoiceDocument($document)) {
                return $this->getInvoiceUrl($document);
            }
        }

        return null;
    }

    public function getInvoiceNumberForOrder(Order $order)
    {
        /** @var Document $document */
        foreach ($order->getDocuments() as $document) {
            if ($this->isInvoiceDocument($document)) {
                return $document->getDocumentId();
            }
        }

        return null;
    }

    private function getInvoiceUrl(Document $document)
    {
        $defaultShop = $this->modelManager->getRepository(Shop::class)->getDefault();

        // fetch current context to restore it after generating url
        $oldContext = $this->router->getContext();

        // create default-storefront context
        $this->router->setContext(Context::createFromShop($defaultShop, $this->config));

        // generate url
        $url = $this->router->assemble([
            'controller' => 'MonduInvoice',
            'action' => 'invoice',
            'module' => 'frontend',
            'type' => $document->getType()->getKey(),
            'orderId' => $document->getOrder()->getId(),
            'documentId' => $document->getDocumentId(),
            'hash' => $document->getHash(),
        ]);

        // restore old context
        $this->router->setContext($oldContext);

        return $url;
    }

    private function isInvoiceDocument(Document $document)
    {
        $additionalDocuments = $this->configService->getAdditionalInvoiceDocuments();
        return $document->getType()->getKey() === 'invoice' ||
            in_array($document->getType()->getKey(), $additionalDocuments);
    }
}
