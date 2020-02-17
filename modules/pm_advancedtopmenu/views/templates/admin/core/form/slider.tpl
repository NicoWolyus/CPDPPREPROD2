<div id="{$key|strtolower|escape:'htmlall':'UTF-8'}-field" {if isset($field.class) && $field.class}class="{$field.class|escape:'htmlall':'UTF-8'}"{/if}>
    <label>{$field.title|escape:'htmlall':'UTF-8'}</label>
    <div class="margin-form">
        <div class="pm_slider">
            <input type="hidden" id="{$key|escape:'htmlall':'UTF-8'}" name="{$key|escape:'htmlall':'UTF-8'}" value="{if $val === false && isset($field.default)}{$field.default|atm_nofilter}{else}{$val|atm_nofilter}{/if}" />
            <div id="slider-{$key|escape:'htmlall':'UTF-8'}" style="display: inline-block; width:200px"></div>&nbsp;&nbsp;&nbsp;&nbsp;<em id="slider-suffix-{$key|escape:'htmlall':'UTF-8'}">{if $val === false && isset($field.default)}{$field.default|atm_nofilter}{else}{$val|atm_nofilter}{/if}{if isset($field.suffix)}{$field.suffix|atm_nofilter}{/if}</em>
            <script type="text/javascript">
                $(function() {
                    $("#slider-{$key|escape:'htmlall':'UTF-8'}").slider({
                        range: false,
                        min: {$field.min|floatval},
                        max: {$field.max|floatval},
                        step: {$field.step|floatval},
                        value: $("#{$key|escape:'htmlall':'UTF-8'}").val(),
                        slide: function(event, ui) {
                            $("#{$key|escape:'htmlall':'UTF-8'}").val(ui.value);
                            $("#slider-suffix-{$key|escape:'htmlall':'UTF-8'}").html(ui.value + " {$field.suffix|atm_nofilter}");
                        }
                    });
                    $("#slider-{$key|escape:'htmlall':'UTF-8'}").slider("value", $("#{$key|escape:'htmlall':'UTF-8'}").val());
                });
            </script>
        </div>
        {include file='./tips.tpl'}
    </div>
</div>
