{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if $payment_mean.name|strpos:"mondu_" === 0}
        <div class="method--label is--first">
            <label for="payment_mean{$payment_mean.id}" class="method--name is--strong">
                <img style="display: inline-block; margin-right: 5px" width="60" height="16" src="https://checkout.mondu.ai/logo.svg" alt="Mondu Logo">
                {$payment_mean.description}
            </label>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Method Description *}
{block name="frontend_checkout_payment_fieldset_description"}
    {if $payment_mean.name|strpos:"mondu_" === 0}
        <div class="method--description is--last">
            {include file="string:{$payment_mean.additionaldescription}"}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
