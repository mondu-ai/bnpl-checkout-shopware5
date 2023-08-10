<?php

namespace Mond1SWR5\Helpers;

class ModuleHelper
{
    public function getEnvironmentInformation() {
        return [
            'plugin' => $this->getModuleNameForApi(),
            'version' => $this->getModuleVersion(),
            'language_version' => 'PHP '. phpversion(),
            'shop_version' => $this->getShopVersion(),
        ];
    }

    public function getModuleNameForApi() {
        return 'shopware5';
    }

    public function getModuleVersion() {
        return Shopware()->Container()->getParameter('active_plugins')['Mond1SWR5'] ?? '';
    }

    public function getShopVersion() {
        $shopwareRelease = Shopware()->Container()->getParameter('shopware.release.version');
        return $shopwareRelease;
    }
}
