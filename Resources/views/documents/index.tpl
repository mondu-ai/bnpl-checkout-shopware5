{extends file="parent:documents/index.tpl"}

{block name="document_index_css"}
    {$smarty.block.parent}
    {block name="document_mondu_css"}
        .mondu-instructions {
            margin-bottom: 0;
        }
        .mondu-table td {
            padding: 0;
        }
        .mondu-table th {
            text-align: left;
            padding: 0 20px 0 0;
        }
        .mondu-payment-note {
            width: 500px;
        }
    {/block}
{/block}

{block name="document_index_info_net"}
    {$smarty.block.parent}
    {if $Document.key == 'invoice' && $Order._payment.name == 'mondu_payment' && isset($includeMonduSection) && $includeMonduSection}
        {block name="document_mondu_section"}
            <div class="mondu-payment-note">
                Diese Rechnung wurde abgetreten gemäß den Allgemeinen Bedingungen von <strong>{$monduMerchant}</strong> und <strong>Mondu GmbH</strong> zum
                Modell Kauf auf Rechnung. Wir bitten um schuldbefreiende Zahlung auf folgendes Konto:
                <br/>
            </div>
            <table class="mondu-table">
                <tr>
                    <th>Kontoinhaber:</th>
                    <td>Mondu Capital Sàrl</td>
                </tr>
                <tr>
                    <th>IBAN:</th>
                    <td>{$monduIban}</td>
                </tr>
                <tr>
                    <th>BIC:</th>
                    <td>HYVEDEMME40</td>
                </tr>
                <tr>
                    <th>Verwendungszweck:</th>
                    <td>Rechnungsnummer {$monduInvoiceNumber} {$monduMerchant}</td>
                </tr>
                <tr>
                    <th>Zahlungsziel:</th>
                    <td>{$monduNetTerm} Tage</td>
                </tr>
            </table>
        {/block}
    {/if}
    {if $Document.key == 'invoice' && $Order._payment.name == 'mondu_payment_sepa' && isset($includeMonduSection) && $includeMonduSection}
        <div class="mondu-payment-note">
            Diese Rechnung wurde abgetreten gemäß den Allgemeinen Bedingungen von <strong>{$monduMerchant}</strong> und <strong>Mondu GmbH</strong> zum Modell Kauf auf Rechnung.
            <br/>
            Da Sie die Zahlart Rechnungskauf mit Begleichung via SEPA-Lastschrift über Mondu gewählt haben, wird die Rechnungssumme am Fälligkeitstag von Ihrem Bankkonto abgebucht.
            <br/><br/>
            Bevor der Betrag von Ihrem Konto abgebucht wird, erhalten Sie eine Lastschriftankündigung. Bitte achten Sie auf eine ausreichende Kontodeckung.
        </div>
    {/if}
    {if $Document.key == 'invoice' && $Order._payment.name == 'mondu_payment_installment' && isset($includeMonduSection) && $includeMonduSection}
        <div class="mondu-payment-note">
            Diese Rechnung wurde abgetreten gemäß den Allgemeinen Bedingungen von <strong>{$monduMerchant}</strong> und <strong>Mondu GmbH</strong> zum Zahlungsmodell Ratenkauf abgetreten.
            <br/><br/>
            Da Sie die Zahlart Ratenkauf mit Begleichung via SEPA-Lastschrift über Mondu gewählt haben, werden die einzelnen Raten an ihrem jeweiligen Fälligkeitstag von Ihrem Bankkonto abgebucht.
            <br/><br/>
            Bevor die Beträge von Ihrem Konto abgebucht werden, erhalten Sie bezüglich der Lastschrift eine Vorankündigung. Bitte achten Sie auf eine ausreichende Kontodeckung. Im Falle von Änderungen an Ihrer Bestellung wird der Ratenplan an die neue Bestellsumme angepasst.        </div>
    {/if}
{/block}
