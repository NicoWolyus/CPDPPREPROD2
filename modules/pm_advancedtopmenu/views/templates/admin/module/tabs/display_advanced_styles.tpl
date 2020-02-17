<form action="{$base_config_url|atm_nofilter}#config-4" id="formAdvancedStyles_{$module_name|escape:'htmlall':'UTF-8'}" name="formAdvancedStyles_{$module_name|escape:'htmlall':'UTF-8'}" method="post" class="width3">
    <fieldset>
        <h3>{l s='Advanced styles' mod='pm_advancedtopmenu'}</h3>
        {foreach from=$fieldsOptions item=field key=key}
            {module->outputFormItem key=$key field=$field}
            {if $key == 'ATM_CONT_CLASSES' && version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
            <p style="margin-left: 20px">
                <small>
                    <em>{l s='Recommended value when using displayTop hook:' mod='pm_advancedtopmenu'}</em> <code>col-lg-8 col-md-7</code><br />
                    <em>{l s='Recommended value when using displayNavFullWidth hook:' mod='pm_advancedtopmenu'}</em> <code>container</code><br /><br />
                </small>
            </p>
            {/if}
        {/foreach}
        <div class="dynamicTextarea">
            <textarea name="advancedConfig" id="advancedConfig" cols="120" rows="30">{$advancedStylesContent|atm_nofilter}</textarea>
        </div>
        <br />
        <center>
            <input type="submit" value="{l s='   Save   ' mod='pm_advancedtopmenu'}" name="submitAdvancedConfig" class="button" />
        </center>
    </fieldset>
</form>
<script type="text/javascript">
    var editor = CodeMirror.fromTextArea(document.getElementById("advancedConfig"), {
        lineNumbers: true
    });
</script>