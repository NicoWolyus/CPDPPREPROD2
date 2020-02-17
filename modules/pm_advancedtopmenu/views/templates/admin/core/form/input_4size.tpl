<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        {l s='top' mod='pm_advancedtopmenu'} <input size="3" type="text" name="{$key|escape:'htmlall':'UTF-8'}[]" value="{if isset($borders_size_tab) && is_array($borders_size_tab)}{$borders_size_tab.0|intval}{else}0{/if}" /> &nbsp; 
        {l s='right' mod='pm_advancedtopmenu'} <input size="3" type="text" name="{$key|escape:'htmlall':'UTF-8'}[]" value="{if isset($borders_size_tab) && is_array($borders_size_tab)}{$borders_size_tab.1|intval}{else}0{/if}" /> &nbsp; 
        {l s='bottom' mod='pm_advancedtopmenu'} <input size="3" type="text" name="{$key|escape:'htmlall':'UTF-8'}[]" value="{if isset($borders_size_tab) && is_array($borders_size_tab)}{$borders_size_tab.2|intval}{else}0{/if}" /> &nbsp; 
        {l s='left' mod='pm_advancedtopmenu'} <input size="3" type="text" name="{$key|escape:'htmlall':'UTF-8'}[]" value="{if isset($borders_size_tab) && is_array($borders_size_tab)}{$borders_size_tab.3|intval}{else}0{/if}" />
        {include file='./tips.tpl'}
    </div>
</div>
