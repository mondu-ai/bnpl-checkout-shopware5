<?php

namespace Mond1SWR5\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Enum\PaymentMethods;
use Mond1SWR5\Helpers\PaymentHelper;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;

class PaymentFilterSubscriber implements SubscriberInterface
{
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(
        Enlight_Components_Session_Namespace $session,
        ModelManager $modelManager,
        PaymentHelper $paymentHelper,
        ConfigService $configService
    )
    {
        $this->session = $session;
        $this->modelManager = $modelManager;
        $this->paymentHelper = $paymentHelper;
        $this->configService = $configService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPayments',
        ];
    }

    public function onFilterPayments(Enlight_Event_EventArgs $args)
    {
        $paymentMethods = $args->getReturn();

        if(!$this->configService->getB2BEnabled()) {
            return $this->paymentHelper->filterPaymentMethods($paymentMethods);
        }
        $userData = $this->session->get('sOrderVariables')['sUserData'];
        $billingAddress = $userData['billingaddress'];

        if (!$billingAddress && $userId = $this->session->get('sUserId')) {
            $customer = $this->modelManager->find(Customer::class, $userId);
            if(!is_array($billingAddress)) {
                $billingAddress = [];
            }
            $billingAddress['company'] = $customer->getDefaultBillingAddress()->getCompany();
        }

        if (empty($billingAddress['company'])) {
            // remove mondu payment methods because it is not a B2B customer
            foreach (PaymentMethods::getNames() as $name) {
                foreach ($paymentMethods as $i => $paymentMethod) {
                    if ($name == $paymentMethod['name']) {
                        unset($paymentMethods[$i]);
                    }
                }
            }
        }
        $filtered = $this->paymentHelper->filterPaymentMethods($paymentMethods);

        return $filtered;
    }
}
