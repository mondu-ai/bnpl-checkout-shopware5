<?php

namespace Mond1SWR5;

use Mond1SWR5\Bootstrap\Attributes\OrderAttributes;
use Mond1SWR5\Compiler\FileLoggerPass;
use Mond1SWR5\Bootstrap\PaymentMethods;
use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Mond1SWR5 extends Plugin
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FileLoggerPass($this->getContainerPrefix()));
    }

    public function install(Plugin\Context\InstallContext $context)
    {
        $bootstrapClasses = $this->getBootstrapClasses($context);
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->preInstall();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->install();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->postInstall();
        }
        parent::install($context);
    }

    public function update(Plugin\Context\UpdateContext $context)
    {
        $bootstrapClasses = $this->getBootstrapClasses($context);
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->preUpdate();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->update();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->postUpdate();
        }
        parent::update($context);
    }

    public function uninstall(Plugin\Context\UninstallContext $context)
    {
        $bootstrapClasses = $this->getBootstrapClasses($context);
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->preUninstall();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->uninstall($context->keepUserData());
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->postUninstall();
        }
    }

    public function deactivate(Plugin\Context\DeactivateContext $context)
    {
        $bootstrapClasses = $this->getBootstrapClasses($context);
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->preDeactivate();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->deactivate();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->postDeactivate();
        }
        parent::deactivate($context);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    public function activate(Plugin\Context\ActivateContext $context)
    {
        $bootstrapClasses = $this->getBootstrapClasses($context);
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->preActivate();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->activate();
        }
        foreach ($bootstrapClasses as $bootstrap) {
            $bootstrap->postActivate();
        }
        parent::activate($context);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    protected function getBootstrapClasses(Plugin\Context\InstallContext $context)
    {
        $bootstrapper = [
            new PaymentMethods(),
            new OrderAttributes()
        ];

        // initialize all bootstraps
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->setContext($context);
            $bootstrap->setContainer($this->container);
            $bootstrap->setPluginDir($this->getPath());
        }

        return $bootstrapper;
    }
}
