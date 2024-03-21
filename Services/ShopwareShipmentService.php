<?php

namespace Mond1SWR5\Services;

use Shopware\Models\Category\Category;
use Shopware\Models\Country\Country;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\Holiday;
use Shopware\Models\Payment\Payment;
use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\ModelManager;
use Doctrine\Common\Collections\ArrayCollection;

use Shopware_Components_Translation;

class ShopwareShipmentService
{
     /**
     * @var Shopware\Models\Dispatch\Repository
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
        $this->repository = Shopware()->Models()->getRepository(Dispatch::class);
    }

    /**
     * Returns all Shipping Costs
     *
     * @return void
     */
    public function getShipmentCosts()
    {
        $dispatchID = null;
        $limit = 20;
        $offset = 0;
        $sort = [['property' => 'dispatch.name', 'direction' => 'ASC']];

        $filter = null;

        $query = $this->repository->getShippingCostsQuery($dispatchID, $filter, $sort, $limit, $offset);
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = Shopware()->Container()->get(ModelManager::class)->createPaginator($query);
        // Returns the total count of the query
        $totalResult = $paginator->count();
        $shippingCosts = $paginator->getIterator()->getArrayCopy();
        $shippingCosts = $this->convertShippingCostsDates($shippingCosts);

        // Translate dispatch methods
        // The standard $translationComponent->translateDispatches can not be used here since the
        // name and the description may not be overridden. Both fields are edible and if the translation is
        // shown in the edit field, there is a high chance of a user saving the translation as name.
        $translator = $this->translation->getObjectTranslator('config_dispatch');
        $shippingCosts = array_map(static function ($dispatchMethod) use ($translator) {
            $translatedDispatchMethod = $translator->translateObjectProperty($dispatchMethod, 'dispatch_name', 'translatedName', $dispatchMethod['name']);

            return $translator->translateObjectProperty($translatedDispatchMethod, 'dispatch_description', 'translatedDescription', $dispatchMethod['description']);
        }, $shippingCosts);

        return $shippingCosts;
    }

    /**
     * Saves the dispatch(shipping cost) to the database.
     */
    public function updateShippingCost($shippingCost)
    {
        $dispatchModel = null;
        $id = $shippingCost["id"];
        if ($id > 0) {
            $dispatchModel = $this->repository->find($id);
        }

        $message = "{$shippingCost["name"]} successfully updated\n";
        $payments = $shippingCost['payments'];
        $holidays = $shippingCost['holidays'];
        $countries = $shippingCost['countries'];
        $categories = $shippingCost['categories'];

        if (!isset($shippingCost['shippingFree']) || $shippingCost['shippingFree'] === '' || $shippingCost['shippingFree'] === '0') {
            $shippingCost['shippingFree'] = null;
        } else {
            $shippingCost['shippingFree'] = (float) str_replace(',', '.', $shippingCost['shippingFree']);
        }

        $shippingCost['payments'] = new ArrayCollection();
        $shippingCost['holidays'] = new ArrayCollection();
        $shippingCost['countries'] = new ArrayCollection();
        $shippingCost['categories'] = new ArrayCollection();

        $shippingCost['multiShopId'] = $this->cleanData($shippingCost['multiShopId']);
        $shippingCost['customerGroupId'] = $this->cleanData($shippingCost['customerGroupId']);
        $shippingCost['bindTimeFrom'] = $this->cleanData($shippingCost['bindTimeFrom']);
        $shippingCost['bindTimeTo'] = $this->cleanData($shippingCost['bindTimeTo']);
        $shippingCost['bindInStock'] = $this->cleanData($shippingCost['bindInStock']);
        $shippingCost['bindWeekdayFrom'] = $this->cleanData($shippingCost['bindWeekdayFrom']);
        $shippingCost['bindWeekdayTo'] = $this->cleanData($shippingCost['bindWeekdayTo']);
        $shippingCost['bindWeightFrom'] = $this->cleanData($shippingCost['bindWeightFrom']);
        $shippingCost['bindWeightTo'] = $this->cleanData($shippingCost['bindWeightTo']);
        $shippingCost['bindPriceFrom'] = $this->cleanData($shippingCost['bindPriceFrom']);
        $shippingCost['bindPriceTo'] = $this->cleanData($shippingCost['bindPriceTo']);
        $shippingCost['bindSql'] = $this->cleanData($shippingCost['bindSql']);
        $shippingCost['calculationSql'] = $this->cleanData($shippingCost['calculationSql']);

        if (!empty($shippingCost['bindTimeFrom'])) {
            $bindTimeFrom = new Zend_Date();
            $bindTimeFrom->set((string) $shippingCost['bindTimeFrom'], Zend_Date::TIME_SHORT);
            $bindTimeFrom = (int) $bindTimeFrom->get(Zend_Date::MINUTE) * 60 + (int) $bindTimeFrom->get(Zend_Date::HOUR) * 60 * 60;
            $shippingCost['bindTimeFrom'] = $bindTimeFrom;
        } else {
            $shippingCost['bindTimeFrom'] = null;
        }

        if (!empty($shippingCost['bindTimeTo'])) {
            $bindTimeTo = new Zend_Date();
            $bindTimeTo->set((string) $shippingCost['bindTimeTo'], Zend_Date::TIME_SHORT);
            $bindTimeTo = (int) $bindTimeTo->get(Zend_Date::MINUTE) * 60 + (int) $bindTimeTo->get(Zend_Date::HOUR) * 60 * 60;
            $shippingCost['bindTimeTo'] = $bindTimeTo;
        } else {
            $shippingCost['bindTimeTo'] = null;
        }

        // Convert params to model
        $dispatchModel->fromArray($shippingCost);

        // Convert the payment array to the payment model
        foreach ($payments as $paymentMethod) {
            if (empty($paymentMethod['id'])) {
                continue;
            }
            $paymentModel = Shopware()->Container()->get(ModelManager::class)->find(Payment::class, $paymentMethod['id']);
            if ($paymentModel instanceof Payment) {
                $dispatchModel->getPayments()->add($paymentModel);
            }
        }

        // Convert the countries to their country models
        foreach ($countries as $country) {
            if (empty($country['id'])) {
                continue;
            }
            $countryModel = Shopware()->Container()->get(ModelManager::class)->find(Country::class, $country['id']);
            if ($countryModel instanceof Country) {
                $dispatchModel->getCountries()->add($countryModel);
            }
        }

        foreach ($categories as $category) {
            if (empty($category['id'])) {
                continue;
            }

            $categoryModel = Shopware()->Container()->get(ModelManager::class)->find(Category::class, $category['id']);
            if ($categoryModel instanceof Category) {
                $dispatchModel->getCategories()->add($categoryModel);
            }
        }

        foreach ($holidays as $holiday) {
            if (empty($holiday['id'])) {
                continue;
            }

            $holidayModel = Shopware()->Container()->get(ModelManager::class)->find(Holiday::class, $holiday['id']);
            if ($holidayModel instanceof Holiday) {
                $dispatchModel->getHolidays()->add($holidayModel);
            }
        }

        try {
            Shopware()->Container()->get(ModelManager::class)->flush();
            $shippingCost['id'] = $dispatchModel->getId();
        } catch (Exception $e) {
            $message = "Failed to update {$shippingCost["name"]}, error message:" . $e->getMessage();
        }
        return $message;
    }

    /**
     * Get all used means of payment
     * @return void
     */
    public function getShipmentPaymentMethods()
    {
        $limit = 20;
        $offset = 0;
        $sort = [];
        $filter = [];

        $query = $this->repository->getPaymentQuery($filter, $sort, $limit, $offset);

        $result = $query->getArrayResult();
        return $result;
    }

    /**
     * Extends the database data with additional data for easier usage
     *
     * @param array<string, mixed> $shippingCosts
     *
     * @return array<string, mixed>
     */
    protected function convertShippingCostsDates(array $shippingCosts): array
    {
        foreach ($shippingCosts as $i => $shippingCost) {
            if ($shippingCost['bindTimeFrom'] !== null) {
                $shippingCosts[$i]['bindTimeFrom'] = gmdate('H:i', $shippingCost['bindTimeFrom']);
            }

            if ($shippingCost['bindTimeTo'] !== null) {
                $shippingCosts[$i]['bindTimeTo'] = gmdate('H:i', $shippingCost['bindTimeTo']);
            }
        }

        return $shippingCosts;
    }

    /**
     * @param int|float|string|null $inputValue
     *
     * @return int|float|string|null
     */
    protected function cleanData($inputValue)
    {
        if (empty($inputValue)) {
            return null;
        }

        return $inputValue;
    }
}
