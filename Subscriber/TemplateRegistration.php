<?php

namespace Mond1SWR5\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Mond1SWR5\Components\PluginConfig\Service\ConfigService;

/**
 * Subscriber to register the plugin template directory before dispatch.
 */
class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var ConfigService
     */
    private $configService;
    /**
     * @param string $pluginDirectory
     */
    public function __construct($pluginDirectory, ConfigService $configService)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->configService = $configService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'addMenuItem',
            'Theme_Inheritance_Template_Directories_Collected' => 'collectTemplateDirForDocuments',
            'Shopware_Components_Document::assignValues::after' => 'onBeforeRenderDocument',
        ];
    }

    public function onBeforeRenderDocument(\Enlight_Hook_HookArgs $args)
    {
        $document = $args->getSubject();
        $view = $document->_view;
        $invoiceIban = $args->getSubject()->_order->order->attributes['mondu_invoice_iban'] ?? '[Iban]';
        $monduMerchant = $args->getSubject()->_order->order->attributes['mondu_merchant_company_name'] ?? Shopware()->Config()->get('company');

        $invoiceNumber = $view->tpl_vars['Document']->value['id'];
        $netTerm = $args->getSubject()->_order->order->attributes['mondu_authorized_net_term'];
        $view->assign('includeMonduSection', $this->configService->extendInvoiceTemplate());
        $view->assign('monduMerchant', $monduMerchant);
        $view->assign('monduIban', $invoiceIban);
        $view->assign('monduInvoiceNumber', $invoiceNumber);
        $view->assign('monduNetTerm', $netTerm);
    }

    public function collectTemplateDirForDocuments(\Enlight_Event_EventArgs $args)
    {
        $dirs = $args->getReturn();
        $dirs[] = $this->pluginDirectory . '/Resources/views/';
        $args->setReturn($dirs);
    }

    /**
     * Add template dir prior dispatching views.
     */
    public function onPreDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();
        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }

    /**
     * Add Menu item sprite class.
     */
    public function addMenuItem(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();

        if ($view->hasTemplate()) {
            $view->extendsTemplate('backend/mondu_overview/menuitem.tpl');
        }
    }
}
