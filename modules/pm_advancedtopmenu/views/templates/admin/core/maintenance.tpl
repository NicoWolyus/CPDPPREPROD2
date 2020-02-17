<div id="maintenanceWarning" class="warning warn clear"{if !$pm_maintenance} style="display:none"{/if}>
    <center>
        <img src="{$module_path|atm_nofilter}views/img/warning.png" style="padding-right:1em;" />
        {if !$ip_maintenance || empty($ip_maintenance)}
            <strong>
                {l s='You must define a maintenance IP in your' mod='pm_advancedtopmenu'}
                <a href="{$maintenanceTabUrl|atm_nofilter}" style="text-decoration:underline;">
                    {l s='Preferences Panel.' mod='pm_advancedtopmenu'}
                </a>
            </strong>
            <br />
        {/if}
        {l s='Module is currently running in Maintenance Mode.' mod='pm_advancedtopmenu'}
    </center>
</div>

<a href="{$base_config_url|atm_nofilter}&activeMaintenance=1" title="{l s='Maintenance' mod='pm_advancedtopmenu'}" class="ajax_script_load ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" id="buttonMaintenance" style="padding-right:5px;">
    <span class="ui-icon ui-icon-wrench" style="float: left; margin-right: .3em;"></span>
    {l s='Maintenance' mod='pm_advancedtopmenu'}
    <span id="pmImgMaintenance" class="ui-icon ui-icon-{if $pm_maintenance}locked{else}unlocked{/if}" style="float: right; margin-left: .3em;"></span>
</a>
