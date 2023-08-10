{namespace name="backend/mondu_overview/order"}
{extends file="parent:backend/_base/mondu_layout.tpl"}

{block name="content/main"}
    <div>
        <div>
            <h2>Invoice {$monduInvoice['invoice_number']}</h2>
        </div>
    </div>
    <form>

        <h3>State details</h3>
        <div class="mondu-group">
            <label style="padding-top: 0" for="state" class="mondu-column-small">State</label>
            <div>
                <span class="state">
                    {$monduInvoice.state}
                </span>
            </div>
            <div style="margin-left: 1rem;">
                <button
                    class="refund-order small"
                    data-order_id="{$shopwareOrder->getId()}"
                    data-invoice_id="{$monduInvoice['uuid']}"
                    data-action="{url controller="MonduOverview" action="refundOrder"}"
                >
                    Create a credit note
                </button>
                <button
                    class="cancel-invoice small"
                    data-order_id="{$monduInvoice['order']['uuid']}"
                    data-invoice_id="{$monduInvoice['uuid']}"
                    data-action="{url controller="MonduOverview" action="cancelInvoice"}"
                >
                    Cancel
                </button>
            </div>
        </div>


        <hr />
        <h3>Invoice amount</h3>
        <div class="mondu-group">
            <label style="padding-top: 0" class="mondu-column-small">Invoice amount</label>
            <div class="col-sm-10">
                <span>{($monduInvoice['gross_amount_cents'] / 100)|string_format:"%.2f"} {s name="order/amount/gross"}{/s}</span><br>
            </div>
        </div>
        {if !empty($monduInvoice['credit_notes'])}
            <div class="mondu-group">
                <label style="padding-top: 0" class="mondu-column-small">Credit notes</label>
                <div>

                    {foreach $monduInvoice['credit_notes'] as $note}
                        <div style="margin-bottom: 1rem;">
                            <b>Mondu id:</b> {$note['uuid']} <br />
                            <b>Amount:</b> {($note['gross_amount_cents']/100)|string_format:"%.2f"}
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    </form>
    <p>
        <a class="mondu-button small" href="{url controller="MonduOverview" action="order" order_id="{$shopwareOrder->getId()}" __csrf_token=$csrfToken}">
            <small>Back to order</small>
        </a>
        <a class="mondu-button small" href="{url controller="MonduOverview" action="index" __csrf_token=$csrfToken}">
            <small>Back to overview</small>
        </a>
    </p>
{/block}
