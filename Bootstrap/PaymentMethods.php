<?php

namespace Mond1SWR5\Bootstrap;

use Mond1SWR5\Bootstrap\Assets\Translations;
use Mond1SWR5\Enum\PaymentMethods as PaymentMethodsEnum;
use Mond1SWR5\Bootstrap\TranslationTransformer;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Country\Country;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Payment\Repository;
use Shopware_Components_Translation;

class PaymentMethods extends AbstractBootstrap
{
    /**
     * @var PaymentInstaller
     */
    private $paymentMethodInstaller;

    /**
     * @var Repository
     */
    private $paymentMethodRepo;

    /**
     * @var TranslationTransformer
     */
    private $translationTransformer;

    /**
     * @var Shopware_Components_Translation
     */
    private $translation;

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->paymentMethodInstaller = $this->container->get('shopware.plugin_payment_installer');
        $this->paymentMethodRepo = $this->modelManager->getRepository(Payment::class);
        $this->translationTransformer = new TranslationTransformer($this->container->get('models'));
        $this->translation = $this->container->get('translation');
    }

    public function preInstall()
    {
    }

    public function install()
    {
        foreach (PaymentMethodsEnum::PAYMENTS as $options) {
            $payment = $this->paymentMethodRepo->findOneBy(['name' => $options['name']]);
            if ($payment !== null) {
                unset(
                    $options['active'],
                    $options['position'],
                    $options['description'],
                    $options['additionalDescription']
                );
            }
            $countryRepository = $this->modelManager->getRepository(Country::class);
            $allowedCountries = $countryRepository->findBy(['iso' => $options['mondu_config']['allowed_in_countries'] ?? null]);
            $options['countries'] = $allowedCountries;
            $this->paymentMethodInstaller->createOrUpdate($this->installContext->getPlugin()->getName(), $options);
        }
        $this->translation->writeBatch(
            $this->translationTransformer->getTranslations('config_payment', Translations::CONFIG_PAYMENT_TRANSLATIONS),
            true
        );
    }

    public function update()
    {
        $this->install();
    }

    public function preUpdate()
    {
        $this->preInstall();
    }

    public function uninstall($keepUserData = false)
    {
        $this->setActiveFlag(false);
    }

    public function activate()
    {
        $this->setActiveFlag(true, true);
    }

    public function deactivate()
    {
        $this->setActiveFlag(false);
    }

    /**
     * @param $flag bool
     * @param bool $onlyFirst
     */
    private function setActiveFlag($flag, $onlyFirst = false)
    {
        $methods = $this->installContext->getPlugin()->getPayments();
        if ($onlyFirst) {
            $methods = [$methods->first()];
        } else {
            $methods = $methods->toArray();
        }
        /** @var Payment $payment */
        foreach ($methods as $payment) {
            $payment->setActive($flag);
        }
        $this->modelManager->flush($methods);
    }
}
