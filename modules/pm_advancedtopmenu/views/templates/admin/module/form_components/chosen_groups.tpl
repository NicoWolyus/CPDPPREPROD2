<div class="privacy chosen_groups"{if $object && $object->privacy == 3}{else} style="display:none;"{/if}>
    <label>{l s='Groups' mod='pm_advancedtopmenu'}</label>
    <div class="margin-form">
        <div style="padding-left: 186px;">
        {foreach from=$groups item=group}
            <input type="checkbox" name="chosen_groups[]" value="{$group.id_group|intval}" {if $object && is_array($object->chosen_groups) && in_array((int)$group['id_group'], $object->chosen_groups)} checked="checked"{/if} />&nbsp;{$group.name|escape:'htmlall':'UTF-8'}
            <br/>
        {/foreach}
        </div>
    </div>
</div>