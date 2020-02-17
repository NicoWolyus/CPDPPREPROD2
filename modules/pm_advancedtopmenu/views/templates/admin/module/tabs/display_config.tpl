{if $shopFeatureActive}
    <div class="info">
        <p>{l s='This configuration can be made by shop.' mod='pm_advancedtopmenu'}</p>
    </div>
{/if}
<form action="{$base_config_url|atm_nofilter}#config-2" id="formGlobal_{$module_name|escape:'htmlall':'UTF-8'}" name="form_{$module_name|escape:'htmlall':'UTF-8'}" method="post" class="width3" enctype="multipart/form-data">
    <fieldset>
        <h3>{l s='General settings' mod='pm_advancedtopmenu'}</h3>
        {foreach from=$fieldsOptions item=field key=key}
            {if $key == 'ATM_MENU_CONT_PADDING'}
                <h3>{l s='Menu container settings' mod='pm_advancedtopmenu'} <small>(#adtm_menu)</small></h3>
            {elseif $key == 'ATM_MENU_GLOBAL_ACTIF'}
                <h3>{l s='Navigation bar settings' mod='pm_advancedtopmenu'} <small>(#adtm_menu_inner)</small></h3>
            {elseif $key == 'ATM_MENU_PADDING'}
                <h3>{l s='Tabs settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATM_SUBMENU_WIDTH'}
                <h3>{l s='Submenus settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATM_COLUMNWRAP_PADDING'}
                <h3>{l s='Columns settings' mod='pm_advancedtopmenu'}</h3>
            {elseif $key == 'ATM_COLUMN_PADDING'}
                <h3>{l s='Items group settings' mod='pm_advancedtopmenu'}</h3>
                <h4>{l s='Container settings' mod='pm_advancedtopmenu'}</h4>
            {elseif $key == 'ATM_COLUMNTITLE_PADDING'}
                <h4>{l s='Title settings' mod='pm_advancedtopmenu'}</h4>
            {elseif $key == 'ATM_COLUMN_ITEM_PADDING'}
                <h3>{l s='Items settings' mod='pm_advancedtopmenu'}</h3>
            {/if}
            {module->outputFormItem key=$key field=$field}
        {/foreach}
        <center>
            <input type="submit" value="{l s='   Save   ' mod='pm_advancedtopmenu'}" name="submitATMOptions" class="button" />
        </center>
    </fieldset>
</form>