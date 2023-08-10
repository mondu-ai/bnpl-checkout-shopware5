{namespace name="backend/mondu_overview/order"}
{extends file="parent:backend/_base/mondu_layout.tpl"}

{block name="content/main"}
<div>
    <div>
        <h2>Order {$shopwareOrder->getNumber()}</h2>
    </div>

    <form>

        <h3>Information</h3>
        <div class="mondu-group">
            <label style="padding-top: 0" for="state" class="mondu-column-small">State</label>
            <div class="col-sm-10">
                <b class="state" id="mondu-order-state">
                    {$monduOrder.state}
                </b>
            </div>
        </div>

        <hr />

        <h3>{s name="order/amount" namespace="backend/mondu_overview/index"}Amount{/s}</h3>
        <div class="mondu-group">
            <label style="padding-top: 0" class="mondu-column-small">Shopware {s name="order/amount" namespace="backend/mondu_overview/order"}Amount{/s}</label>
            <div>
                <span>{$shopwareOrder->getInvoiceAmountNet()|string_format:"%.2f"} {s name="order/amount/net"}Net Amount{/s}</span><br>
                <span>{$shopwareOrder->getInvoiceAmount()|string_format:"%.2f"} {s name="order/amount/gross"}Gross Amount{/s}</span>
            </div>
        </div>
        <div class="mondu-group">
            <label style="padding-top: 0" class="mondu-column-small">Mondu amount</label>
            <div>
                <span>{($monduOrder.real_price_cents / 100)|string_format:"%.2f"} {s name="order/amount/gross"}Gross Amount{/s}</span>
            </div>
        </div>
        {if !empty($monduInvoices)}
            <hr />
            <h3 style="margin-top:0">Mondu invoices</h3>
            {foreach $monduInvoices as $invoice}
                <h4>
                    <b>
                        <span class="mondu-column-small">Invoice No {$invoice['invoice_number']}</span>
                    </b>
                </h4>
                <div class="mondu-group">
                    <label class="mondu-column-small">Details</label>
                    <div>
                        <div>Gross Amount: {($invoice['gross_amount_cents']/100)|string_format:"%.2f"}</div>
                        <div>State: <b>{$invoice['state']}</b></div>
                    </div>
                    <div>
                        <a style="margin-left: 1rem;" class="mondu-button primary small" href="{url controller="MonduOverview" action="invoice" invoice_id="{$invoice['uuid']}" order_id="{$shopwareOrder->getId()}"}">
                            {s name="order/edit" namespace="backend/mondu_overview/index"}Edit{/s}
                        </a>
                    </div>
                </div>

            {/foreach}
        {/if}
        <hr />
        {if $monduOrder.state !== 'canceled'}
            <button
                class="cancel-order"
                data-order_id="{$shopwareOrder->getId()}"
                data-action="{url controller="MonduOverview" action="cancelOrder"}"
            >
                {s name="order/cancel"}Cancel Order{/s}
            </button>
        {/if}
    </form>

    <p>
        <a class="mondu-button small" href="{url controller="MonduOverview" action="index" __csrf_token=$csrfToken}">
            <small>
                {s name="order/back"}Back{/s}
            </small>
        </a>
    </p>
</div>
{/block}
