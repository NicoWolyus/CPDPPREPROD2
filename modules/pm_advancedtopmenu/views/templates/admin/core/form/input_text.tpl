<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        <input type="text" name="{$key|escape:'htmlall':'UTF-8'}" value="{if $val === false && isset($field.default) && $field.default}{$field.default|atm_nofilter}{else}{$val|atm_nofilter}{/if}" size="20" />{if isset($field.suffix)}{$field.suffix|atm_nofilter}{/if}
        {include file='./tips.tpl'}
    </div>
</div>
