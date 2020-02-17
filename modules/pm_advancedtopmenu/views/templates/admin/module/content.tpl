{$css_js_assets|atm_nofilter}

<div id="pm_backoffice_wrapper" class="pm_bo_ps_{$ps_major_version|escape:'htmlall':'UTF-8'}">
    {module->_displayTitle text="{$module_display_name}"}

    {if !$permissions_errors|count}
        {if $module_is_up_to_date}
            {$rating_invite|atm_nofilter}

            {if $nativeMenuModulePresence}
                {module->showWarning text="
                    {l s='We\'ve detected that the native menu module "%s" is enabled. In order to avoid having 2 menus displayed at the same time, we recommend to disable it' sprintf=$nativeMenuModuleDisplayName mod='pm_advancedtopmenu'}
                "}
            {/if}

            {$display_maintenance|atm_nofilter}

            <hr class="pm_hr" />
            <div id="wrapConfigTab">
                <ul style="height: 30px;" id="configTab">
                    <li><a href="#config-1"><span><img src="{$module_path|atm_nofilter}logo.gif" /> {l s='Menu configuration' mod='pm_advancedtopmenu'}</span></a></li>
                    <li><a href="#config-2"><span><img src="{$module_path|atm_nofilter}views/img/cog.gif" /> {l s='General settings' mod='pm_advancedtopmenu'}</span></a></li>
                    <li><a href="#config-3"><span><img src="{$module_path|atm_nofilter}views/img/cog.gif" /> {l s='Mobile settings' mod='pm_advancedtopmenu'}</span></a></li>
                    <li><a href="#config-4"><span><img src="{$module_path|atm_nofilter}views/img/document-code.png" /> {l s='Advanced styles' mod='pm_advancedtopmenu'}</span></a></li>
                </ul>
                <div id="config-1">{$display_form|atm_nofilter}</div><!-- #config-1 -->
                <div id="config-2">{$display_config|atm_nofilter}</div><!-- #config-2 -->
                <div id="config-3">{$display_mobile_config|atm_nofilter}</div><!-- #config-3 -->
                <div id="config-4">{$display_advanced_styles|atm_nofilter}</div><!-- #config-4 -->
            </div>
        {else}
            {module->showWarning text="
                <p>{l s='We have detected that you installed a new version of the module on your shop' mod='pm_advancedtopmenu'}</p>
                <p style=\"text-align: center\">
                    <a href=\"{$base_config_url|atm_nofilter}&makeUpdate=1\" class=\"button\">
                        {l s='Please click here in order to finish the installation process' mod='pm_advancedtopmenu'}
                    </a>
                </p>
            "}
        {/if}
    {else}
        {module->showWarning text="
            {l s='Before being able to configure the module, make sure to set write permissions to files and folders listed below:' mod='pm_advancedtopmenu'}<br /><br />
            {$permissions_errors|implode:'<br />'|atm_nofilter}
        "}
    {/if}

    {module->_displaySupport}
</div>

{* Init color picker *}
<script type="text/javascript">
$(document).ready(function() {
    var currentColorPicker = false;
    $("input[class=pm_colorpicker]").ColorPicker({
        onSubmit: function(hsb, hex, rgb, el) {
            $(el).val("#"+hex);
            $(el).ColorPickerHide();
        },
        onBeforeShow: function () {
            currentColorPicker = $(this);
            $(this).ColorPickerSetColor(this.value);
        },
        onChange: function (hsb, hex, rgb) {
            $(currentColorPicker).val("#"+hex);
            $(currentColorPicker).css('borderLeft', '5px solid #'+hex);
        }
    }).bind("keyup", function(){
        $(this).ColorPickerSetColor(this.value);
    });

    $('.pm_colorpicker').each(function(i,e) {
        if ($(e).val().length > 0) {
            $(e).css('borderLeft', '5px solid '+$(e).val());
        }
    });
});
</script>