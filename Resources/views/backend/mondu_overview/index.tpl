{namespace name="backend/mondu_overview/index"}
{extends file="parent:backend/_base/mondu_layout.tpl"}

{block name="content/main"}
    <h2>Orders</h2>

    <div>
        <table>
            <thead>
            <tr>
                <th>{s name="order/date"}{/s}</th>
                <th>{s name="order/number"}{/s}</th>
                <th>{s name="order/payment_method"}{/s}</th>
                <th>{s name="order/mondu_id"}{/s}</th>
                <th>{s name="order/amount"}{/s}</th>
                <th>{s name="order/company"}{/s}</th>
                <th>{s name="order/mondu_state"}{/s}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            {foreach $orders as $order}
                {$state = $order.attribute.monduState}
                <tr>
                    <td>{$order.orderTime|date:DATE_SHORT}</td>
                    <td>{$order.number}</td>
                    <td>
                        {if $order.attribute.monduPaymentMethod eq 'invoice'}
                            Rechnungskauf
                        {elseif $order.attribute.monduPaymentMethod eq 'direct_debit'}
                            SEPA Direct Debit
                        {elseif $order.attribute.monduPaymentMethod eq 'installment'}
                            Ratenzahlung
                        {else}
                            {$order.attribute.monduPaymentMethod}
                        {/if}
                    </td>
                    <td style="max-width: 300px; text-overflow: ellipsis; overflow: hidden" title="{$order.transactionId}">
                        <span>
                            {$order.transactionId}
                        </span>
                    </td>
                    <td>{$order.invoiceAmount}{$order.currency}</td>
                    <td>{$order.billing.company}</td>
                    <td class="state">
                        {$order.attribute.monduState}
                    </td>
                    <td>
                        <a class="mondu-button primary" href="{url controller="MonduOverview" action="order" order_id="{$order.id}"}">
                            {s name="order/edit"}Edit{/s}
                        </a>
                        <button
                            id="mondu-cancel-order"
                            class="cancel-order"
                            {if $order.attribute.monduState eq 'canceled' or $order.attribute.monduState eq 'complete'}disabled="disabled"{/if}
                            data-order_id="{$order.id}"
                            data-action="{url controller="MonduOverview" action="cancelOrder"}"
                        >
                            {s name="order/cancel"}Cancel{/s}
                        </button>
                    </td>
                </tr>
            {/foreach}
            </tbody>
            <tfoot>
            <tr>
                <td colspan="2">
                    <strong>{s name="order/order_count"}Order count{/s}</strong> {$total}
                </td>
                <td colspan="6">
                    <nav aria-label="navigation">
                        <ul>
                            {if $page > 1}
                                <li>
                                    <a href="{url controller="MonduOverview" action="index" page=($page -1)}" aria-label="back">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            {/if}
                            {for $curr=1 to $totalPages step 1}
                                <li {if $curr == $page}class="active"{/if}>
                                    <a href="{url controller="MonduOverview" action="index" page=$curr}">
                                        {$curr}
                                    </a>
                                </li>
                            {/for}
                            {if $page < $totalPages}
                                <li>
                                    <a href="{url controller="MonduOverview" action="index" page=($page + 1)}" aria-label="next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            {/if}
                        </ul>
                    </nav>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
{/block}
