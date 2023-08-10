<?php

namespace Mond1SWR5\Services;

use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class PaymentStatusService
{
    /**
     * @var ModelManager
     */
    private $modelManager;


    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param string $orderIdentifier
     * @param int $stateId
     * @param string $group
     * @param null $orderState
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updatePaymentStatus($orderIdentifier, $stateId, $group = 'payment', $orderState = null)
    {
        /** @var Order|null $orderModel */
        $orderModel = $this->modelManager->getRepository(Order::class)->findOneBy(['number' => $orderIdentifier]);

        if(!($orderModel instanceof Order)) {
            $orderModel = $this->modelManager->getRepository(Order::class)->findOneBy(['transactionId' => $orderIdentifier]);
        }

        if (!($orderModel instanceof Order)) {
            $message = \sprintf('Could not find order with search parameter "%s" and value "%s"', 'temporaryId', $orderIdentifier);
            throw new \RuntimeException($message);
        }

        /** @var Status|null $orderStatusModel */
        $orderStatusModel = $this->modelManager->getRepository(Status::class)->find($stateId);

        if($group === Status::GROUP_PAYMENT) {
            $orderModel->setPaymentStatus($orderStatusModel);

            if ($stateId === Status::PAYMENT_STATE_COMPLETELY_PAID
                || $stateId === Status::PAYMENT_STATE_PARTIALLY_PAID
            ) {
                $orderModel->setClearedDate(new DateTime());
            }
        } elseif($group === Status::GROUP_STATE) {
            $orderModel->setOrderStatus($orderStatusModel);
            $orderModel->setClearedDate(new DateTime());
        }
        if($orderState) {
            $orderAttrs = $orderModel->getAttribute();
            $orderAttrs->setMonduState($orderState);
            $this->modelManager->flush($orderAttrs);
        }

        $this->modelManager->flush($orderModel);
    }
}
