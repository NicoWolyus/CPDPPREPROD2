<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        {foreach from=$languages item=language}
            <div id="{$key|escape:'htmlall':'UTF-8'}_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
              <input size="{$field.size|intval}" type="text" name="{$key|escape:'htmlall':'UTF-8'}_{$language.id_lang|intval}" value="{$lang_values[intval($language.id_lang)]|atm_nofilter}" />
            </div>
        {/foreach}
        {module->displayFlags languages=$languages default_language=$default_language ids={$key|escape:'htmlall':'UTF-8'} id={$key|escape:'htmlall':'UTF-8'} return=true}
        <div class="clear"></div>
        {include file='./tips.tpl'}
    </div>
</div>
