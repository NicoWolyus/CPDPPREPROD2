<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        {if $val !== false && $val == ''}
            <span>N/A&nbsp;&nbsp;</span>
        {elseif ($val === false && isset($field.default) && $field.default) || is_array($val) && count($val)}
            <img src="{if $val === false && isset($field.default) && $field.default}{$field.default|atm_nofilter}{else}{$val|atm_nofilter}{/if}" value="{$val|atm_nofilter}" />
        {/if}
        <input type="file" name="{$key|escape:'htmlall':'UTF-8'}" />
        &nbsp; <input type="checkbox" name="{$key|escape:'htmlall':'UTF-8'}_delete" /> &nbsp; {l s='Delete old image' mod='pm_advancedtopmenu'}
        {include file='./tips.tpl'}
    </div>
</div>
