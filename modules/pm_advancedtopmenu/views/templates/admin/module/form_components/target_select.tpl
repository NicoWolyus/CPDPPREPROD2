<label>{l s='Target' mod='pm_advancedtopmenu'}</label>
<div class="margin-form">
    <select name="target">
    {foreach from=$link_targets key=target item=value}
        <option value="{$target|escape:'htmlall':'UTF-8'}"{if $selected === $target} selected="selected"{/if}>{$value|escape:'htmlall':'UTF-8'}</option>
    {/foreach}
    </select>
</div>