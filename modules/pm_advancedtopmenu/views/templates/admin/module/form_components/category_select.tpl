<label>{l s='Category' mod='pm_advancedtopmenu'}</label>
<div class="margin-form">
    <select name="id_category">
        <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
    {foreach from=$categoryList item=categoryInformations}
        <option value="{$categoryInformations.id_category|intval}"{if $selected == $categoryInformations.id_category} selected="selected"{/if}>
            {str_repeat('- ', $categoryInformations.level_depth - 1)|escape:'htmlall':'UTF-8'} {$categoryInformations.name|escape:'htmlall':'UTF-8'}
        </option>
    {/foreach}
    </select>
</div>