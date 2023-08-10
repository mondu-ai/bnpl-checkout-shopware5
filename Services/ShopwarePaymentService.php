<?php

namespace Mond1SWR5\Services;


use Shopware\Models\Payment\Payment;
use Shopware\Models\Country\Country;
use Shopware\Models\Shop\Shop;

use Shopware_Components_Translation;

class ShopwarePaymentService
{
    /**
     * @var Shopware\Models\Payment\Repository
     */
    protected $repository;

    /**
     * @var Shopware_Components_Translation
     */
    protected $translation;

    public function __construct(
        Shopware_Components_Translation $translation
    )
    {
        $this->translation = $translation;
        $this->repository = Shopware()->Models()->getRepository(Payment::class);
    }

    /**
     * Function to update a payment with its countries, shops and surcharges
     * The mapping for the mapping-tables is automatically created
     * @param Payment $paymentMethod
     */
    public function updatePaymentMethod($paymentMethod)
    {
        try {
            $id = $paymentMethod['id'];
            /** @var Payment $payment */
            $payment = Shopware()->Models()->find(Payment::class, $id);
            $action = $payment->getAction();
            $data = $paymentMethod;
            $data['surcharge'] = str_replace(',', '.', $data['surcharge']);
            $data['debitPercent'] = str_replace(',', '.', $data['debitPercent']);

            $countries = new \Doctrine\Common\Collections\ArrayCollection();
            if (!empty($data['countries'])) {
                // Clear all countries, to save the old and new ones then
                $payment->getCountries()->clear();
                foreach ($data['countries'] as $country) {
                    $model = Shopware()->Models()->find(Country::class, $country['id']);
                    $countries->add($model);
                }
                $data['countries'] = $countries;
            }

            $shops = new \Doctrine\Common\Collections\ArrayCollection();
            if (!empty($data['shops'])) {
                // Clear all shops, to save the old and new ones then
                $payment->getShops()->clear();
                foreach ($data['shops'] as $shop) {
                    $model = Shopware()->Models()->find(Shop::class, $shop['id']);
                    $shops->add($model);
                }
                $data['shops'] = $shops;
            }
            $data['surchargeString'] = $this->filterSurchargeString($data['surchargeString'], $data['countries']);

            $payment->fromArray($data);

            // A default parameter "action" is sent
            // To prevent "updatePayment" written into the database
            if (empty($action)) {
                $payment->setAction('');
            } else {
                $payment->setAction($action);
            }

            // ExtJS transforms null to 0
            if ($payment->getSource() == 0) {
                $payment->setSource(null);
            }
            if ($payment->getPluginId() == 0) {
                $payment->setPluginId(null);
            }

            Shopware()->Models()->persist($payment);
            Shopware()->Models()->flush();

            if ($data['active']) {
                $data['iconCls'] = 'sprite-tick';
            } else {
                $data['iconCls'] = 'sprite-cross';
            }
        } catch (\Doctrine\ORM\ORMException $e) {
            $message = $e->getMessage();
            echo "errorMsg => $message";
        }
    }

    /**
     * Main-Method to get all payments and its countries and subshops
     * The data is additionally formatted, so additional-information are also given
     */
    public function getPaymentMethods()
    {
        $query = $this->repository->getListQuery(null, [
            ['property' => 'payment.active', 'direction' => 'DESC'],
            ['property' => 'payment.position'],
        ]);
        $results = $query->getArrayResult();

        // Translate payments
        $translator = $this->translation->getObjectTranslator('config_payment');
        $results = array_map(function ($payment) use ($translator) {
            return $translator->
                translateObjectProperty($payment, 'description', 'translatedDescription', $payment['description']);
        }, $results);

        $results = $this->formatResult($results);
        return $results;
    }

    /**
     * Helper method to
     * - set the correct icon
     * - match the surcharges to the countries
     *
     * @param array $results
     *
     * @return array
     */
    protected function formatResult($results)
    {
        $surchargeCollection = [];
        foreach ($results as &$result) {
            if ($result['active'] == 1) {
                $result['iconCls'] = 'sprite-tick-small';
            } else {
                $result['iconCls'] = 'sprite-cross-small';
            }
            $result['text'] = $result['translatedDescription'];
            $result['leaf'] = true;

            // Matches the surcharges with the countries
            if (!empty($result['surchargeString'])) {
                $surchargeString = $result['surchargeString'];
                $surcharges = explode(';', $surchargeString);
                $specificSurcharges = [];
                foreach ($surcharges as $surcharge) {
                    $specificSurcharges[] = explode(':', $surcharge);
                }
                $surchargeCollection[$result['name']] = $specificSurcharges;
            }
            if (empty($surchargeCollection[$result['name']])) {
                $surchargeCollection[$result['name']] = [];
            }
            foreach ($result['countries'] as &$country) {
                foreach ($surchargeCollection[$result['name']] as $singleSurcharge) {
                    if ($country['iso'] == $singleSurcharge[0]) {
                        $country['surcharge'] = $singleSurcharge[1];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @param string                             $surchargeString
     * @param Country[] $countries
     *
     * @return string
     */
    protected function filterSurchargeString($surchargeString, $countries)
    {
        $buffer = [];
        $surcharges = explode(';', $surchargeString);
        $isoCodes = [];

        foreach ($countries as $country) {
            $isoCodes[] = $country->getIso();
        }

        foreach ($surcharges as $surcharge) {
            $keys = explode(':', $surcharge);
            if (\in_array($keys[0], $isoCodes)) {
                $buffer[] = $surcharge;
            }
        }

        return implode(';', $buffer);
    }
}
