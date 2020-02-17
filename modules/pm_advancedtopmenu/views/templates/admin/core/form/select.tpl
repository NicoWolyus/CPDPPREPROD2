<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        <select id="{$key|escape:'htmlall':'UTF-8'}" name="{$key|escape:'htmlall':'UTF-8'}"{if isset($field.onchange) && $field.onchange} onchange="{$field.onchange|atm_nofilter}"{/if}>
        {foreach from=$field.list item=value}
            <option value="{$value[$field.identifier]|escape:'htmlall':'UTF-8'}"{if $value.is_selected} selected="selected"{/if}>{$value.name|escape:'htmlall':'UTF-8'}</option>             
        {/foreach}
        </select>
        {if isset($field['onchange']) && $field['onchange']}
        <script type="text/javascript">
        $(document).ready(function() {
            $("select#{$key|escape:'htmlall':'UTF-8'}").trigger("change");
        });
        </script>
        {/if}
        {include file='./tips.tpl'}
    </div>
</div>
