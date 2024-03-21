<?php

namespace Mond1SWR5\Enum;

use Shopware\Models\Payment\Payment;

final class PaymentMethods extends Enum
{
    const AUTHORIZATION_STATE_FLOW = 'authorization_flow';

    const PAYMENT_MONDU_1 = 'mondu_payment';
    const PAYMENT_MONDU_2 = 'mondu_payment_sepa';
    const PAYMENT_MONDU_3 = 'mondu_payment_installment';

    const MONDU_INVOICE = 'invoice';
    const MONDU_SEPA = 'direct_debit';
    const MONDU_INSTALLMENT = 'installment';

    const LOCAL_MONDU_PAYMENT_METHODS = [self::PAYMENT_MONDU_1, self::PAYMENT_MONDU_2, self::PAYMENT_MONDU_3];
    const MONDU_PAYMENT_METHODS = [self::MONDU_INVOICE, self::MONDU_SEPA, self::MONDU_INSTALLMENT];

    const MAPPING = [
        self::MONDU_INVOICE => self::PAYMENT_MONDU_1,
        self::MONDU_SEPA => self::PAYMENT_MONDU_2,
        self::MONDU_INSTALLMENT => self::PAYMENT_MONDU_3
    ];

    const MONDU_STATE_CONFIRMED = 'confirmed';
    const MONDU_STATE_PENDING = 'pending';

    const PAYMENTS = [
        self::PAYMENT_MONDU_1 => [
            'name' => self::PAYMENT_MONDU_1,
            'description' => 'Mondu Rechnungskauf - jetzt kaufen, später bezahlen',
            'action' => 'Mondu',
            'active' => true,
            'position' => 0,
            'template' => 'mondu_change_payment.tpl',
            'additionalDescription' => 'Hinweise zur Verarbeitung Ihrer personenbezogenen Daten durch die Mondu GmbH finden Sie <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer" target="_blank">hier</a>.',
            'mondu_config' => [
                'allowed_in_countries' => ['DE'],
            ],
        ],
        self::PAYMENT_MONDU_2 => [
            'name' => self::PAYMENT_MONDU_2,
            'description' =>  'Mondu SEPA-Lastschrift - jetzt kaufen, später per Bankeinzug bezahlen',
            'action' => 'Mondu',
            'active' => true,
            'position' => 1,
            'template' => 'mondu_change_payment.tpl',
            'additionalDescription' => 'Hinweise zur Verarbeitung Ihrer personenbezogenen Daten durch die Mondu GmbH finden Sie <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer" target="_blank">hier</a>.',
            'mondu_config' => [
                'allowed_in_countries' => ['DE'],
            ],
        ],
        self::PAYMENT_MONDU_3 => [
            'name' => self::PAYMENT_MONDU_3,
            'description' =>  'Mondu Ratenzahlung - Bequem in Raten per Bankeinzug zahlen',
            'action' => 'Mondu',
            'active' => true,
            'position' => 2,
            'template' => 'mondu_change_payment.tpl',
            'additionalDescription' => 'Hinweise zur Verarbeitung Ihrer personenbezogenen Daten durch die Mondu GmbH finden Sie <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer" target="_blank">hier</a>.',
            'mondu_config' => [
                'allowed_in_countries' => ['DE'],
            ],
        ]
    ];

    public static function getNames()
    {
        return array_keys(self::PAYMENTS);
    }

    /**
     * @param string|Payment $paymentMethod
     *
     * @return bool
     */
    public static function exists($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;

        return $paymentMethod ? array_key_exists($paymentMethod, self::PAYMENTS) : false;
    }

    public static function getMethod($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;

        return self::exists($paymentMethod) ? self::PAYMENTS[$paymentMethod] : false;
    }
}
