{if $shopFeatureActive}
    <div class="info">
        <p>{l s='This configuration can be made by shop.' mod='pm_advancedtopmenu'}</p>
    </div>
{/if}
<form action="{$base_config_url|atm_nofilter}#config-3" id="formMobileGlobal_{$module_name|escape:'htmlall':'UTF-8'}" name="form_mobile_{$module_name|escape:'htmlall':'UTF-8'}" method="post" class="width3" enctype="multipart/form-data">
    <fieldset>
        <h3>{l s='Responsive design settings' mod='pm_advancedtopmenu'}</h3>
        <h5>{l s='Enable only if your theme manage this behaviour' mod='pm_advancedtopmenu'}</h5>
        {foreach from=$fieldsOptions item=field key=key}
            {if $key == 'ATM_RESP_TOGGLE_ENABLED' && $ps_major_version >= '17'}
                <h3>{l s='Menu toggle settings' mod='pm_advancedtopmenu'}</h3>
                <h5>{l s='Enable only if your theme doesn\'t manage an "hamburger" icon' mod='pm_advancedtopmenu'}</h5>
            {elseif $key == 'ATM_RESP_TOGGLE_HEIGHT' && $ps_major_version == '16'}
                <h3>{l s='Menu toggle settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATM_RESP_MENU_PADDING'}
                <h3>{l s='Tabs settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATMR_SUBMENU_BGCOLOR'}
                <h3>{l s='Submenus settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATMR_COLUMNWRAP_PADDING'}
                <h3>{l s='Columns settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATMR_COLUMN_PADDING'}
                <h3>{l s='Items group settings' mod='pm_advancedtopmenu'}</h3>
                <h4>{l s='Container settings' mod='pm_advancedtopmenu'}</h4>
            {elseif $key == 'ATMR_COLUMNTITLE_PADDING'}
                <h4>{l s='Title settings' mod='pm_advancedtopmenu'}</h4>
            {elseif $key == 'ATMR_COLUMN_ITEM_PADDING'}
                <h3>{l s='Items settings' mod='pm_advancedtopmenu'}</h3>
            {/if}
            {module->outputFormItem key=$key field=$field}
        {/foreach}
        <center>
            <input type="submit" value="{l s='   Save   ' mod='pm_advancedtopmenu'}" name="submitATMMobileOptions" class="button" />
        </center>
    </fieldset>
</form>
