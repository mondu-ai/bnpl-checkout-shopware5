<?php

namespace Mond1SWR5\Services;

use Mond1SWR5\Enum\PaymentMethods;
use Shopware\Models\Payment\Payment;

class PaymentService
{
    /**
     * Validate payment data based on legal form. If successful return true,
     * otherwise return an array with errorflags and messages.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return array|bool
     */
    public function validate(array $fields, \Enlight_Controller_Request_Request $request)
    {
        // Error Bags
        $errorMessages = [];
        $errorFlag = [];

        // validate Fields and return error if there are any
        foreach ($fields as $field) {
            if (!$request->has($field) || empty(trim($request->getParam($field)))) {
                $errorFlag[$field] = true;
            }
        }

        if (count($errorFlag)) {
            $errorMessages[] = Shopware()->Snippets()->getNamespace('frontend/account/internalMessages')
                ->get('ErrorFillIn', 'Please fill in all red fields');

            return [
                'errorFlag' => $errorFlag,
                'messages' => $errorMessages,
            ];
        }

        return true;
    }

    /**
     * Check if payment id belongs to mondu payment.
     *
     * @return bool
     */
    public function isMonduPayment(array $payment)
    {
        // Check by name if $payment is a mondu payment
        if (array_key_exists('name', $payment) && PaymentMethods::exists($payment['name'])) {
            return true;
        }

        // Check by id if $payment is a mondu payment
        if (array_key_exists('id', $payment)) {
            // Build filter for payment names
            $filters = [];
            foreach (PaymentMethods::getNames() as $name) {
                $filters[] = ['property' => 'name', 'value' => $name, 'operator' => 'or'];
            }
            unset($filters[0]['operator']);

            // Query all payments for their ids
            $paymentMean = Shopware()->Models()
                ->getRepository(Payment::class)
                ->getActivePaymentsQuery($filters)
                ->getArrayResult();

            return $paymentMean && in_array($payment['id'], array_column($paymentMean, 'id'));
        }

        return false;
    }
}
