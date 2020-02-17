<label>{l s='CMS' mod='pm_advancedtopmenu'}</label>
<div class="margin-form">
    <select name="id_cms">
        <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
    {foreach from=$cmsList item=cms}
        <option value="{$cms.id_cms|intval}"{if $selected == $cms.id_cms} selected="selected"{/if}>
            {$cms.meta_title|escape:'htmlall':'UTF-8'}
        </option>
    {/foreach}
    </select>
</div>