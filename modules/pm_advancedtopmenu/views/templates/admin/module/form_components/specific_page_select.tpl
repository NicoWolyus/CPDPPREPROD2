<label>{l s='Specific page' mod='pm_advancedtopmenu'}</label>
<div class="margin-form">
    <select name="id_specific_page">
        <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
    {foreach from=$pagesList item=page}
        <option value="{$page.page|escape:'htmlall':'UTF-8'}"{if $selected == $page.page} selected="selected"{/if}>
        	{if !empty($page.title)}{$page.title|escape:'htmlall':'UTF-8'}{else}{$page.page|escape:'htmlall':'UTF-8'}{/if}
        </option>
    {/foreach}
    </select>
</div>