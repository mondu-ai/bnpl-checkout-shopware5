<?php

namespace Mond1SWR5\Bootstrap\Assets;

use Mond1SWR5\Enum\PaymentMethods;

final class Translations
{
    const CONFIG_PAYMENT_TRANSLATIONS = [
        'en_GB' => [
            PaymentMethods::PAYMENT_MONDU_1 => [
                'description' => 'Mondu Pay later via bank transfer',
                'additionalDescription' => 'Information on the processing of your personal data by Mondu GmbH can be found <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer/" target="_blank">here.</a>'
            ],
            PaymentMethods::PAYMENT_MONDU_2 => [
                'description' => 'Mondu Pay later via SEPA Direct Debit	',
                'additionalDescription' => 'Information on the processing of your personal data by Mondu GmbH can be found <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer/" target="_blank">here.</a>'
            ],
            PaymentMethods::PAYMENT_MONDU_3 => [
                'description' => 'Mondu Split Payments',
                'additionalDescription' => 'Information on the processing of your personal data by Mondu GmbH can be found <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer/" target="_blank">here.</a>'
            ]
        ],
        'de_DE' => [
            PaymentMethods::PAYMENT_MONDU_1 => [
                'description' => 'Mondu Rechnungskauf - jetzt kaufen, später bezahlen',
                'additionalDescription' => 'Hinweise zur Verarbeitung Ihrer personenbezogenen Daten durch die Mondu GmbH finden Sie <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer" target="_blank">hier</a>.'
            ],
            PaymentMethods::PAYMENT_MONDU_2 => [
                'description' => 'Mondu SEPA-Lastschrift - jetzt kaufen, später per Bankeinzug bezahlen',
                'additionalDescription' => 'Hinweise zur Verarbeitung Ihrer personenbezogenen Daten durch die Mondu GmbH finden Sie <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer" target="_blank">hier</a>.'
            ],
            PaymentMethods::PAYMENT_MONDU_3 => [
                'description' => 'Mondu Ratenzahlung - Bequem in Raten per Bankeinzug zahlen',
                'additionalDescription' => 'Hinweise zur Verarbeitung Ihrer personenbezogenen Daten durch die Mondu GmbH finden Sie <a href="https://www.mondu.ai/de/datenschutzgrundverordnung-kaeufer" target="_blank">hier</a>.'
            ]
        ],
        'nl_NL' => [
            PaymentMethods::PAYMENT_MONDU_1 => [
                'description' => 'Mondu Aankoop op rekening - nu kopen, later betalen',
                'additionalDescription' => 'Informatie over de verwerking van uw persoonsgegevens door Mondu GmbH vindt u <a href="https://www.mondu.ai/nl/information-nach-art-13-datenschutzgrundverordnung-fur-kaufer/" target="_blank">hier</a>.'
            ],
            PaymentMethods::PAYMENT_MONDU_2 => [
                'description' => 'Mondu SEPA automatische incasso - nu kopen, later betalen',
                'additionalDescription' => 'Informatie over de verwerking van uw persoonsgegevens door Mondu GmbH vindt u <a href="https://www.mondu.ai/nl/information-nach-art-13-datenschutzgrundverordnung-fur-kaufer/" target="_blank">hier</a>.'
            ],
            PaymentMethods::PAYMENT_MONDU_3 => [
                'description' => 'Mondu Gespreid betalen, betaal gemakkelijk in termijnen via automatische incasso',
                'additionalDescription' => 'Informatie over de verwerking van uw persoonsgegevens door Mondu GmbH vindt u <a href="https://www.mondu.ai/nl/information-nach-art-13-datenschutzgrundverordnung-fur-kaufer/" target="_blank">hier</a>.'
            ]
        ],
        'fr_FR' => [
            PaymentMethods::PAYMENT_MONDU_1 => [
                'description' => 'Mondu Payez plus tard par virement',
                'additionalDescription' => 'Des informations sur la façon dont Mondu GmbH traite vos données personnelles peuvent être trouvées <a href=\"https://mondu.ai/fr/gdpr-notification-for-buyers\" target=\"_blank\">ici</a>.'
            ],
            PaymentMethods::PAYMENT_MONDU_2 => [
                'description' => 'Mondu SEPA - Payer plus tard par prélèvement SEPA',
                'additionalDescription' => 'Des informations sur la façon dont Mondu GmbH traite vos données personnelles peuvent être trouvées <a href=\"https://mondu.ai/fr/gdpr-notification-for-buyers\" target=\"_blank\">ici</a>.'
            ],
            PaymentMethods::PAYMENT_MONDU_3 => [
                'description' => 'Mondu Paiements Fractionnés - Payer facilement en plusieurs fois par prélèvement automatique',
                'additionalDescription' => 'Des informations sur la façon dont Mondu GmbH traite vos données personnelles peuvent être trouvées <a href=\"https://mondu.ai/fr/gdpr-notification-for-buyers\" target=\"_blank\">ici</a>.'
            ]
        ]
    ];

    private function __construct()
    {
    }
}
