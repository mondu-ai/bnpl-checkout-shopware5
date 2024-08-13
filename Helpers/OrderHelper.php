<?php

namespace Mond1SWR5\Helpers;

use Mond1SWR5\Components\PluginConfig\Service\ConfigService;
use Mond1SWR5\Enum\PaymentMethods;
use Mond1SWR5\Services\OrderServices\AbstractOrderAdditionalCostsService;
use Monolog\Logger;
use Shopware\Components\HttpClient\RequestException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Mond1SWR5\Components\MonduApi\Service\MonduClient;

class OrderHelper
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var DocumentHelper
     */
    private $documentHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var CartHelper
     */
    private $customerHelper;

    /**
     * @var AbstractOrderAdditionalCostsService
     */
    private $orderAdditionalCostsService;

    public function __construct(
        ModelManager                        $modelManager,
        DocumentHelper                      $documentHelper,
        Logger                              $logger,
        ConfigService                       $configService,
        CartHelper                          $cartHelper,
        CustomerHelper                      $customerHelper,
        AbstractOrderAdditionalCostsService $orderAdditionalCostsService
    ) {
        $this->modelManager                = $modelManager;
        $this->documentHelper              = $documentHelper;
        $this->logger                      = $logger;
        $this->configService               = $configService;
        $this->cartHelper                  = $cartHelper;
        $this->customerHelper              = $customerHelper;
        $this->orderAdditionalCostsService = $orderAdditionalCostsService;
    }

    public function canShipOrder($order): bool
    {
        if (!$this->configService->getValidateInvoice()) {
            return true;
        }

        $invoiceId = $this->documentHelper->getInvoiceNumberForOrder($order);

        return (bool)$invoiceId;
    }

    /**
     * @param Order $order
     * @throws RequestException
     */
    public function shipOrder($order)
    {
        /**
         * @var $client MonduClient
         */
        $client = Shopware()->Container()->get(MonduClient::class);

        $invoiceData = $this->getInvoiceData($order);
        $invoice     = $client->createOrderInvoice($order->getTransactionId(), $invoiceData);

        $this->setOrderMonduState($order, 'shipped');

        return $invoice;
    }

    /**
     * @param $orderId
     * @return mixed
     * @throws RequestException
     */
    public function getMonduOrder($orderId)
    {
        /**
         * @var MonduClient $client
         */
        $client = Shopware()->Container()->get(MonduClient::class);

        return $client->getMonduOrder($orderId);
    }

    /**
     * @param $orderId
     * @param $invoiceId
     * @return mixed
     * @throws RequestException
     */
    public function getMonduOrderInvoice($orderId, $invoiceId)
    {
        $client = Shopware()->Container()->get(MonduClient::class);

        return $client->getMonduOrderInvoice($orderId, $invoiceId);
    }

    /**
     * @param $orderId
     * @return mixed
     * @throws RequestException
     */
    public function getMonduOrderInoices($orderId)
    {
        /**
         * @var MonduClient $client
         */
        $client = Shopware()->Container()->get(MonduClient::class);

        return $client->getMonduOrderInvoices($orderId);
    }

    /**
     * @param $invoiceId
     * @return mixed
     */
    public function getInvoiceMemos($invoiceId)
    {
        /**
         * @var MonduClient $client
         */
        $client = Shopware()->Container()->get(MonduClient::class);

        return $client->getMonduInvoiceMemos($invoiceId);
    }

    /**
     * @param $invoiceId
     * @param $amount
     * @param $reference
     * @return mixed
     * @throws RequestException
     */
    public function createCreditMemo($invoiceId, $amount, $reference)
    {
        /**
         * @var MonduClient $client
         */
        $client = Shopware()->Container()->get(MonduCLient::class);

        return $client->createCreditMemo($invoiceId, $amount, $reference);
    }

    /**
     * @param Order $order
     * @throws RequestException
     */
    public function cancelOrder($order)
    {
        /**
         * @var MonduClient $client
         */
        $client          = Shopware()->Container()->get(MonduClient::class);
        $cancelOrderData = $client->cancelOrder($order->getTransactionId());
        $this->setOrderMonduState($order, 'canceled');

        return $cancelOrderData;
    }

    /**
     * @param $orderId
     * @param $invoiceId
     * @return mixed
     * @throws RequestException
     */
    public function cancelInvoice($orderId, $invoiceId)
    {
        $client = Shopware()->Container()->get(MonduClient::class);

        return $client->cancelOrderInvoice($orderId, $invoiceId);
    }

    /**
     * @param $orderId
     * @param $invoiceId
     * @return mixed
     * @throws RequestException
     */
    public function updateOrder($order)
    {
        /**
         * @var MonduClient $client
         */
        $client          = Shopware()->Container()->get(MonduClient::class);
        $updateOrderData = $this->getOrderAdjustment($order);
        $newOrder        = $client->updateOrder($updateOrderData, $order->getTransactionId());
        if ($newOrder) {
            $this->setOrderMonduState($order, $newOrder['state']);
        }

        return $newOrder;
    }

    /**
     * @param $order
     * @return mixed
     * @throws RequestException
     */
    public function updateExternalInfoOrder($order)
    {
        /**
         * @var MonduClient $client
         */
        $client          = Shopware()->Container()->get(MonduClient::class);
        $updateOrderData = [
            'external_reference_id' => $order->getNumber()
        ];

        return $client->updateExternalInfoOrder($order->getTransactionId(), $updateOrderData);
    }

    /**
     * @param $orderVariables
     * @return mixed
     */
    public function getOrderFromOrderVariables($orderVariables)
    {
        $userData = $orderVariables['sUserData'];
        $basket   = $orderVariables['sBasket'];
        $content  = $basket['content'];

        $totalAmount    = $this->cartHelper->getTotalAmount($orderVariables['sBasket'], $orderVariables['sUserData']);
        $shippingAmount = $this->cartHelper->getShippingAmount(
            $orderVariables['sBasket'],
            $orderVariables['sUserData']
        );
        $chargeVat      = $this->customerHelper->chargeVat($orderVariables['sUserData']);

        return [
            'currency'              => $basket['sCurrencyName'],
            'state_flow'            => PaymentMethods::AUTHORIZATION_STATE_FLOW,
            'external_reference_id' => (string)$orderVariables['sOrderNumber'],
            'gross_amount_cents'    => round($totalAmount * 100),
            'buyer'                 => $this->getBuyerParams($userData),
            'billing_address'       => $this->getBillingAddress($userData),
            'shipping_address'      => $this->getShippingAddress($userData),
            'lines'                 => [
                [
                    'discount_cents'       => $this->getTotalDiscount($content, $chargeVat),
                    'buyer_fee_cents'      => $this->orderAdditionalCostsService->getAdditionalCostsCentsFromOrderVariables(
                        $orderVariables
                    ),
                    'shipping_price_cents' => round($shippingAmount * 100),
                    'line_items'           => $this->removeDuplicateSwReferenceIds(
                        $this->getLineItems($content, $chargeVat)
                    )
                ]
            ]
        ];
    }

    /**
     * @param $order
     * @return mixed
     * @throws RequestException
     */
    public function getOrderAdjustment($order)
    {
        $lineitems          = [];
        $totalDiscount      = 0;
        $totalDiscountGross = 0;

        foreach ($order->getDetails() as $detail) {
            if ($detail->getPrice() > 0) {
                $lineitems = $this->getLineItemsFromDetail($detail, $order, $lineitems);
            } else {
                [$total, $net] = $this->getAmountsFromDetail($detail, $detail->getQuantity(), $order->getNet());
                $totalDiscount      += round(abs($net) * 100);
                $totalDiscountGross += round(abs($total) * 100);
            }
        }

        if (!$order->getTaxFree()) {
            $taxDiscount = round($totalDiscountGross - $totalDiscount);
        } else {
            $taxDiscount        = 0;
            $totalDiscountGross = $totalDiscount;
        }

        $amountNet   = $order->getInvoiceAmountNet() - $order->getInvoiceShippingNet();
        $amountTax   = ($order->getInvoiceAmount() - $order->getInvoiceAmountNet()) - ($order->getInvoiceShipping(
                ) - $order->getInvoiceShippingNet());
        $amountGross = $order->getInvoiceAmount();

        return [
            'currency'              => $order->getCurrency(),
            'external_reference_id' => $order->getNumber(),
            'amount'                => [
                'net_price_cents'    => round($amountNet * 100) + $totalDiscount,
                'tax_cents'          => round($amountTax * 100) + $taxDiscount,
                'gross_amount_cents' => round($amountGross * 100)
            ],
            'lines'                 => [
                [
                    'discount_cents'       => $totalDiscountGross,
                    'buyer_fee_cents'      => $this->orderAdditionalCostsService->getAdditionalCostsCentsFromOrder(
                        $order
                    ),
                    'shipping_price_cents' => round($order->getInvoiceShipping() * 100),
                    'line_items'           => $this->removeDuplicateSwReferenceIds($lineitems)
                ]
            ]
        ];
    }

    /**
     * @param $content
     * @param $chargeVat
     * @return mixed
     */
    public function getLineItems($content, $chargeVat): array
    {
        $lineItems = [];
        foreach ($content as $item) {
            if ($item['amountNumeric'] <= 0) {
                continue;
            }

            $lineItems[] = $this->getLineItem($item, $chargeVat);
        }

        return $lineItems;
    }

    /**
     * @param $content
     * @param $chargeVat
     * @return int
     */
    public function getTotalDiscount($content, $chargeVat)
    {
        $discount = 0;
        foreach ($content as $item) {
            if ($item['amountNumeric'] > 0) {
                continue;
            }
            if ($chargeVat) {
                $amountNumeric = abs($item['amountWithTax'] ?? $item['amountNumeric']);
            } else {
                $amountNumeric = abs($item['amountnetNumeric']);
            }
            $discount += round($amountNumeric * 100);
        }

        return $discount;
    }

    /**
     * @param $item
     * @param $chargeVat
     * @return array
     */
    public function getLineItem($item, $chargeVat)
    {
        $itemAmountNet  = $item['netprice'];
        $totalAmountNet = $item['amountnetNumeric'];
        $taxAmount      = str_replace(',', '.', $item['tax']);

        return [
            'external_reference_id'    => $item['ordernumber'],
            'title'                    => $item['articlename'],
            'net_price_cents'          => round($totalAmountNet * 100),
            'net_price_per_item_cents' => round($itemAmountNet * 100),
            'tax_cents'                => $chargeVat ? round($taxAmount * 100) : 0,
            'quantity'                 => (int)$item['quantity'],
            'product_id'               => $item['articleID']
        ];
    }

    /**
     * @param $order
     * @param $state
     */
    public function setOrderMonduState($order, $state)
    {
        $orderAttributes = $order->getAttribute();
        $orderAttributes->setMonduState($state);
        $this->modelManager->flush($orderAttributes);
    }

    /**
     * @param $order
     * @return array
     */
    public function getOrderLineItems($order)
    {
        $lineItems = [];
        foreach ($order->getDetails() as $detail) {
            if ($detail->getPrice() > 0) {
                $lineItems = $this->getLineItemsFromDetail($detail, $order, $lineItems);
            }
        }

        return $lineItems;
    }

    /**
     * @param $orderNumber
     * @param $orderUid
     * @param $viban
     */
    public function setOrderViban($orderNumber, $orderUid, $viban)
    {
        $repo  = $this->modelManager->getRepository(Order::class);
        $order = $repo->findOneBy(['number' => $orderNumber]);
        if (!$order) {
            $order = $repo->findOneBy(['transactionId' => $orderUid]);
        }

        $attributes = $order->getAttribute();
        $attributes->setMonduInvoiceIban($viban);
        $this->modelManager->flush($attributes);
    }

    /**
     * @param $order
     * @param $operation
     * @param $e
     */
    public function logOrderError($order, $operation, $e)
    {
        $this->logger->error($e->getMessage(), [
            'code'        => $e->getCode(),
            'orderId'     => $order->getId(),
            'referenceId' => $order->getTransactionId(),
            'operation'   => $operation
        ]);
    }

    /**
     * @return mixed
     */
    public function getInvoiceCreateState()
    {
        switch ($this->configService->getInvoiceCreateState()) {
            case 'completed':
                return Status::ORDER_STATE_COMPLETED;
            default:
                return Status::ORDER_STATE_COMPLETELY_DELIVERED;
        }
    }

    /**
     * @param $userData
     * @return array
     */
    private function getBuyerParams($userData)
    {
        $user    = $userData['additional']['user'];
        $billing = $userData['billingaddress'];

        return [
            'email'         => $user['email'],
            'first_name'    => $user['firstname'],
            'last_name'     => $user['lastname'],
            'company_name'  => $billing['company'],
            'phone'         => !$billing['phone'] ? null : (trim($billing['phone']) ?: null),
            'is_registered' => (bool)$user['userID'],
            'salutation'    => $billing['salutation'],
            'created_at'    => $user['firstlogin'] . ' 00:00:00',
            'updated_at'    => $user['changed'],
            'address_line1' => $billing['street']
        ];
    }

    /**
     * @param $userData
     * @return array
     */
    private function getBillingAddress($userData)
    {
        $billing = $userData['billingaddress'];

        return [
            'country_code'  => $userData['additional']['country']['countryiso'],
            'city'          => $billing['city'],
            'state'         => $userData['additional']['state']['name'],
            'address_line1' => $billing['street'],
            'zip_code'      => $billing['zipcode']
        ];
    }

    /**
     * @param $userData
     * @return array
     */
    private function getShippingAddress($userData)
    {
        $shipping = $userData['shippingaddress'];

        return [
            'country_code'  => $userData['additional']['countryShipping']['countryiso'],
            'city'          => $shipping['city'],
            'state'         => $userData['additional']['stateShipping']['name'],
            'address_line1' => $shipping['street'],
            'zip_code'      => $shipping['zipcode']
        ];
    }

    /**
     * @param $order
     * @return array
     */
    private function getInvoiceData($order)
    {
        $invoiceNumber = $this->documentHelper->getInvoiceNumberForOrder($order);
        $invoiceUrl    = $this->documentHelper->getInvoiceUrlForOrder($order);

        if (!$this->configService->getValidateInvoice()) {
            $invoiceNumber = $invoiceNumber ?: $order->getNumber();
            $invoiceUrl    = $invoiceUrl ?: 'https://not.available';
        }

        return [
            'external_reference_id' => $invoiceNumber,
            'invoice_url'           => $invoiceUrl,
            'gross_amount_cents'    => round($order->getInvoiceAmount() * 100),
            'line_items'            => $this->removeDuplicateSwReferenceIds($this->getOrderLineItems($order))
        ];
    }

    # Copied from https://github.com/shopware/shopware/blob/v5.6.4/engine/Shopware/Models/Order/Order.php#L1097

    /**
     * @param $detail
     * @param $quantity
     * @param $net
     * @return array
     */
    private function getAmountsFromDetail($detail, $quantity, $net)
    {
        $price = round($detail->getPrice(), 2);
        $amount = $price * $quantity;
        $tax = $detail->getTax();
        $taxValue = $detail->getTaxRate();

        // Additional tax checks required for sw-2238, sw-2903 and sw-3164
        if ($tax && $tax->getId() !== 0 && $tax->getId() !== null && $tax->getTax() !== null) {
            $taxValue = (float)$tax->getTax();
        }

        if ($net) {
            $amountGross = Shopware()->Container()->get('shopware.cart.net_rounding')->round(
                $price,
                $taxValue,
                $quantity
            );

            return [$amountGross, $amount];
        } else {
            $amountNet = round(($price * $quantity) / (100 + $taxValue) * 100, 2);

            return [$amount, $amountNet];
        }
    }

    /**
     * @param       $detail
     * @param       $order
     * @param array $lineItems
     * @return array
     */
    private function getLineItemsFromDetail($detail, $order, array $lineItems): array
    {
        [$totalAmount, $totalAmountNet] = $this->getAmountsFromDetail(
            $detail,
            $detail->getQuantity(),
            $order->getNet()
        );
        [$itemAmount, $itemAmountNet] = $this->getAmountsFromDetail($detail, 1, $order->getNet());
        $chargeVat   = !$order->getTaxFree();
        $lineItems[] = [
            'external_reference_id'    => $detail->getArticleNumber(),
            'title'                    => $detail->getArticleName(),
            'net_price_cents'          => round($totalAmountNet * 100),
            'net_price_per_item_cents' => round($itemAmountNet * 100),
            'tax_cents'                => $chargeVat ? round(($totalAmount - $totalAmountNet) * 100) : 0,
            'quantity'                 => $detail->getQuantity(),
            'product_id'               => (string)$detail->getArticleId()
        ];

        return $lineItems;
    }

    /**
     * This is a hotfix for an issue with duplicate line_item external reference id - sw-payment
     *
     * @param array $lineItems
     * @return array
     */
    private function removeDuplicateSwReferenceIds(array $lineItems): array
    {
        $referenceIds = ['sw-payment', 'sw-payment-absolute', 'sw-discount', 'sw-discount-absolute'];

        $lineItemReferenceIds = [];
        foreach ($lineItems as $lineItem) {
            if (in_array($lineItem['external_reference_id'], $referenceIds)) {
                $lineItemReferenceIds[] = $lineItem['external_reference_id'];
            }
        }

        if (count($lineItemReferenceIds) === count(array_unique($lineItemReferenceIds))) {
            return $lineItems;
        }

        $newLineItems = [];
        foreach ($lineItems as $key => $lineItem) {
            if (in_array($lineItem['external_reference_id'], $referenceIds)) {
                $lineItem['external_reference_id'] .= '-' . $key;
            }

            $newLineItems[] = $lineItem;
        }

        return $newLineItems;
    }

    /**
     * Set order status by order id
     *
     * @param int         $orderId
     * @param int         $orderStatusId
     * @param string|null $comment
     */
    public function setOrderStatus($orderId, $orderStatusId = 0, $comment = null)
    {
        /** @var Order|null $orderModel */
        $orderModel = $this->modelManager->getRepository(Order::class)->findOneBy(['number' => $orderId]);

        if(!($orderModel instanceof Order)) {
            $orderModel = $this->modelManager->getRepository(Order::class)->findOneBy(['transactionId' => $orderId]);
        }

        if (!($orderModel instanceof Order)) {
            $message = \sprintf('Could not find order with search parameter "%s" and value "%s"', 'temporaryId', $orderId);
            throw new \RuntimeException($message);
        }

        $previousStatusId = $this->getOrderStatus($orderModel->getId());

        if ($orderStatusId == $previousStatusId) {
            return;
        }

        $this->db->executeUpdate(
            'UPDATE s_order SET status = :status WHERE id = :orderId;',
            [':status' => $orderStatusId, ':orderId' => $orderModel->getId()]
        );

        $sql = '
           INSERT INTO s_order_history (
              orderID, userID, previous_order_status_id, order_status_id,
              previous_payment_status_id, payment_status_id, comment, change_date )
            SELECT id, NULL, :previousStatus, :currentStatus, cleared, cleared, :comment, NOW() FROM s_order WHERE id = :orderId
        ';

        $this->db->executeUpdate($sql, [
            ':previousStatus' => $previousStatusId,
            ':currentStatus'  => $orderStatusId,
            ':comment'        => $comment,
            ':orderId'        => $orderModel->getId(),
        ]);
    }

    /**
     * Helper function which returns the current order status of the passed order
     * id.
     *
     * @param int $orderId
     *
     * @return string
     */
    private function getOrderStatus($orderId)
    {
        return $this->db->fetchOne(
            'SELECT status FROM s_order WHERE id= :orderId;',
            [':orderId' => $orderId]
        );
    }
}
