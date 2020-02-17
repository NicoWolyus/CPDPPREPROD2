<label>{l s='Manufacturer' mod='pm_advancedtopmenu'}</label>
<div class="margin-form">
    <select name="id_manufacturer">
        <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
    {foreach from=$manufacturerList item=manufacturer}
        <option value="{$manufacturer.id_manufacturer|intval}"{if $selected == $manufacturer.id_manufacturer} selected="selected"{/if}>
            {$manufacturer.name|escape:'htmlall':'UTF-8'}
        </option>
    {/foreach}
    </select>
</div>