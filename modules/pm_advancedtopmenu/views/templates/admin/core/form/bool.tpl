<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        <label class="t" for="{$key|escape:'htmlall':'UTF-8'}_on">
            <img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" />
        </label>
        <input type="radio" name="{$key|escape:'htmlall':'UTF-8'}" id="{$key|escape:'htmlall':'UTF-8'}_on" value="1"{if $val} checked="checked"{/if}{if isset($field.disable) && $field.disable} disabled="disabled"{/if} />
        <label class="t" for="{$key|escape:'htmlall':'UTF-8'}_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
        <label class="t" for="{$key|escape:'htmlall':'UTF-8'}_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
        <input type="radio" name="{$key|escape:'htmlall':'UTF-8'}" id="{$key|escape:'htmlall':'UTF-8'}_off" value="0"{if !$val} checked="checked"{/if}{if isset($field.disable) && $field.disable} disabled="disabled"{/if} />
        <label class="t" for="{$key|escape:'htmlall':'UTF-8'}_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        {include file='./tips.tpl'}
    </div>
</div>
