{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_error_messages"}
    {$smarty.block.parent}
    {if $smarty.get.errorCode}
        {include file="frontend/_includes/messages.tpl" type="error" content=$smarty.get.errorCode|snippet:$errorCode:'frontend/mondu/errors'}
    {/if}
    {include file="frontend/_includes/messages.tpl" type="error mondu-notify" content=''|snippet:'_DefaultErrorMessage':'frontend/mondu/errors' visible=false}
{/block}

