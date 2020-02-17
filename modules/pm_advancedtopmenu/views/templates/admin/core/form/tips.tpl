{if isset($field) && is_array($field) && isset($field['desc']) && $field['desc']}
<img title="{$field.desc|escape:'htmlall':'UTF-8'}" id="{$key|escape:'htmlall':'UTF-8'}-tips" class="pm_tips" src="{$module_path|atm_nofilter}views/img/question.png" width="16px" height="16px" />
<script type="text/javascript">pm_initTips("#{$key|escape:'htmlall':'UTF-8'}")</script>
{/if}