<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        <input type="text" name="{$key|escape:'htmlall':'UTF-8'}[0]" id="{$key|escape:'htmlall':'UTF-8'}_0" value="{if !isset($color1) && isset($field.default) && $field.default}{$field.default|escape:'htmlall':'UTF-8'}{else}{if isset($color1)}{$color1|escape:'htmlall':'UTF-8'}{/if}{/if}" size="20" class="pm_colorpicker" />{if isset($field.suffix)}{$field.suffix|atm_nofilter}{/if}
        &nbsp; <span{if !isset($color2)} style="display:none"{/if} id="{$key|escape:'htmlall':'UTF-8'}_gradient"><input type="text" name="{$key|escape:'htmlall':'UTF-8'}[1]" id="{$key|escape:'htmlall':'UTF-8'}_1" value="{if isset($color2)}{$color2|escape:'htmlall':'UTF-8'}{/if}" size="20" class="pm_colorpicker" />{if isset($field.suffix)}{$field.suffix|atm_nofilter}{/if}</span>
        &nbsp; <input type="checkbox" name="{$key|escape:'htmlall':'UTF-8'}_gradient" value="1" {if isset($color2)} checked="checked"{/if} /> &nbsp; {l s='Make a gradient' mod='pm_advancedtopmenu'}
        {include file='./tips.tpl'}
    </div>
</div>
<script type="text/javascript">
$("input[name={$key|escape:'htmlall':'UTF-8'}_gradient]").click(function() {
	if (!$(this).is(':checked')) {
		$("#{$key|escape:'htmlall':'UTF-8'}_gradient input").val('');
	}
    showSpanIfChecked($(this), "#{$key|escape:'htmlall':'UTF-8'}_gradient");
});
</script>
