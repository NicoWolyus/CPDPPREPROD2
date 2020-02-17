<label>{l s='Supplier' mod='pm_advancedtopmenu'}</label>
<div class="margin-form">
    <select name="id_supplier">
        <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
    {foreach from=$supplierList item=supplier}
        <option value="{$supplier.id_supplier|intval}"{if $selected == $supplier.id_supplier} selected="selected"{/if}>
            {$supplier.name|escape:'htmlall':'UTF-8'}
        </option>
    {/foreach}
    </select>
</div>