<script type="text/javascript">id_language = {$default_language|intval};</script>
{if $shopFeatureActive}
    <div class="warning warn clear">
        {l s='Configuration can not be different by shop. It will be applied to all shop.' mod='pm_advancedtopmenu'}
    </div>
{/if}

<form action="{$base_config_url|atm_nofilter}" method="post" id="formElement_{$module_name|escape:'htmlall':'UTF-8'}" name="formElement_{$module_name|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data" class="width3">
    <div id="blocElementForm">
    {if $ObjAdvancedTopMenuElementClass}
        <input type="hidden" name="id_element" value="{$ObjAdvancedTopMenuElementClass->id|intval}" />
        <br />
        <a href="{$base_config_url|atm_nofilter}"><img src="../img/admin/arrow2.gif" />{l s='Back' mod='pm_advancedtopmenu'}</a>
        <br class="clear" />
        <br />
    {/if}
        <h3>{l s='General settings' mod='pm_advancedtopmenu'}</h3>

        <label>{l s='Parent tab' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="id_menu" id="id_menu_select">
                {if isset($menus) && is_array($menus) && count($menus) > 1}
                    <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
                {/if}
                {foreach from=$menus item=menu}
                    <option value="{$menu.id_menu|intval}"{if $ObjAdvancedTopMenuElementClass && AdvancedTopMenuColumnClass::getIdMenuByIdColumn($ObjAdvancedTopMenuElementClass->id_column) == $menu.id_menu} selected="selected"{/if}>{module->getAdminOutputNameValue row=$menu withExtra=false}</option>
                {/foreach}
            </select>
        </div>

        <label>{l s='Parent group' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form" id="column_select">
        {if Validate::isLoadedObject($ObjAdvancedTopMenuElementClass)}
            {module->outputSelectColumns id_menu=AdvancedTopMenuColumnClass::getIdMenuByIdColumn($ObjAdvancedTopMenuElementClass->id_column) column_selected=$ObjAdvancedTopMenuElementClass->id_column}
        {elseif !Validate::isLoadedObject($ObjAdvancedTopMenuElementClass) && isset($menus) && is_array($menus) && count($menus) == 1}
            {module->outputSelectColumns id_menu=$menus[0]['id_menu']}
        {else}
            <div class="error inline-alert">
                <strong><u>{l s='Please select a parent tab!' mod='pm_advancedtopmenu'}</u></strong>
            </div>
            <script type="text/javascript">
            $(document).ready(function() {
                $('input[name="submitElement"]').attr('disabled', 'disabled').prop('disabled', true);
            });
            </script>
        {/if}
        </div>
        <script type="text/javascript">
            $(document).ready(function() {
                $("#id_menu_select").change(function() {
                    showColumnSelect($(this));
                });
            });
        </script>

        <label>{l s='Type' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="type" id="type_element">
                <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
                <option value="1"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 1} selected="selected"{/if}>{l s='CMS' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 2} selected="selected"{/if}>{l s='Link' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 3} selected="selected"{/if}>{l s='Category' mod='pm_advancedtopmenu'}</option>
                <option value="4"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 4} selected="selected"{/if}>{l s='Manufacturer' mod='pm_advancedtopmenu'}</option>
                <option value="5"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 5} selected="selected"{/if}>{l s='Supplier' mod='pm_advancedtopmenu'}</option>
                <option value="6"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 6} selected="selected"{/if}>{l s='Search' mod='pm_advancedtopmenu'}</option>
                <option value="7"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 7} selected="selected"{/if}>{l s='Only image or icon' mod='pm_advancedtopmenu'}</option>
                <option value="9"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 9} selected="selected"{/if}>{l s='Specific page' mod='pm_advancedtopmenu'}</option>
            </select>
        </div>
        <script type="text/javascript">
        $(document).ready(function() {
            $("#type_element").change(function() {
                showMenuType($(this),"element");
            });
        });
        </script>

        <div class="add_category menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 3}{else} style="display:none;"{/if}>
            {module->outputCategoriesSelect object=$ObjAdvancedTopMenuElementClass}
        </div>

        <div class="add_cms menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 1}{else} style="display:none;"{/if}>
            {module->outputCmsSelect cms=$cms object=$ObjAdvancedTopMenuElementClass}
        </div>

        <div class="add_manufacturer menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 4}{else} style="display:none;"{/if}>
            {module->outputManufacturerSelect manufacturers=$manufacturer object=$ObjAdvancedTopMenuElementClass}
        </div>

        <div class="add_supplier menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 5}{else} style="display:none;"{/if}>
            {module->outputSupplierSelect suppliers=$supplier object=$ObjAdvancedTopMenuElementClass}
        </div>

        <div class="add_specific_page menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 9}{else} style="display:none;"{/if}>
            {module->outputSpecificPageSelect object=$ObjAdvancedTopMenuElementClass}
        </div>

        <div class="add_link menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type != 2} style="display:none;"{/if}>
            <label>{l s='Link' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="elementlink_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <input size="20" type="text" name="link_{$language.id_lang|intval}" class="adtmInputLink" value="{if $ObjAdvancedTopMenuElementClass && isset($ObjAdvancedTopMenuElementClass->link[$language['id_lang']])}{$ObjAdvancedTopMenuElementClass->link[$language['id_lang']]|escape:'htmlall':'UTF-8'}{/if}" maxlength="255" />
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='elementlink' return=true}
                <div class="clear"></div>
            </div>
        </div>

        <label>{l s='Prevent click on link' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input type="checkbox" name="clickable" id="element_clickable" value="1" {if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->link[$default_language] == '#'} checked="checked"}{/if} />
            <small>{l s='add a # in the link field, do not remove' mod='pm_advancedtopmenu'}</small>
        </div>
        <script type="text/javascript">
        $("#element_clickable").click(function() {
            setUnclickable($(this));
        });
        </script>

        {module->outputTargetSelect object=$ObjAdvancedTopMenuElementClass}

        <div class="add_title menu_element"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->type == 7} style="display:none;"{/if}>
            <label>{l s='Title' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="elementname_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <textarea cols="20" rows="2" name="name_{$language.id_lang|intval}" maxlength="255">{if $ObjAdvancedTopMenuElementClass && isset($ObjAdvancedTopMenuElementClass->name[$language['id_lang']])}{$ObjAdvancedTopMenuElementClass->name[$language['id_lang']]|atm_nofilter}{/if}</textarea>
                    <br />{l s='(if filled, will replace original title)' mod='pm_advancedtopmenu'}
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='elementname' return=true}
                <div class="clear"></div>
            </div>
        </div>

        <label>{l s='Active' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="element_active_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_element" id="element_active_on" value="1"{if !$ObjAdvancedTopMenuElementClass || ($ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->active)} checked="checked"{/if} />
            <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="element_active_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_element" id="element_active_off" value="0"{if $ObjAdvancedTopMenuElementClass && !$ObjAdvancedTopMenuElementClass->active} checked="checked"{/if} />
            <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Active on desktop' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="element_active_desktop_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_desktop_element" id="element_active_desktop_on" value="1"{if !$ObjAdvancedTopMenuElementClass || ($ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->active_desktop)} checked="checked"{/if} />
            <label class="t" for="active_desktop_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="element_active_desktop_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_desktop_element" id="element_active_desktop_off" value="0"{if $ObjAdvancedTopMenuElementClass && !$ObjAdvancedTopMenuElementClass->active_desktop} checked="checked"{/if} />
            <label class="t" for="active_desktop_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Active on mobile' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="element_active_mobile_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_mobile_element" id="element_active_mobile_on" value="1"{if !$ObjAdvancedTopMenuElementClass || ($ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->active_mobile)} checked="checked"{/if} />
            <label class="t" for="active_mobile_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="element_active_mobile_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_mobile_element" id="element_active_mobile_off" value="0"{if $ObjAdvancedTopMenuElementClass && !$ObjAdvancedTopMenuElementClass->active_mobile} checked="checked"{/if} />
            <label class="t" for="active_mobile_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Privacy Options' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="privacy">
                <option value="0"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->privacy == 0} selected="selected"{/if}>{l s='For all' mod='pm_advancedtopmenu'}</option>
                <option value="1"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->privacy == 1} selected="selected"{/if}>{l s='Only for visitors' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->privacy == 2} selected="selected"{/if}>{l s='Only for registered users' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuElementClass && $ObjAdvancedTopMenuElementClass->privacy == 3} selected="selected"{/if}>{l s='Only for groups of customers' mod='pm_advancedtopmenu'}</option>
            </select>
        </div>

        {module->outputChosenGroups object=$ObjAdvancedTopMenuElementClass}

        {if !$imgIconElementDirIsWritable}
            <div class="warning warn clear">{l s='To upload an icon, please assign CHMOD 777 to the directory:' mod='pm_advancedtopmenu'} {$moduleRootDirectory|escape:'htmlall':'UTF-8'}/element_icons</div>
        {/if}

        <label>{l s='Icon or image' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            {foreach from=$languages item=language}
            <div id="elementimage_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                <input type="file" name="icon_{$language.id_lang|intval}" size="20"{if !$imgIconElementDirIsWritable} disabled="disabled"{/if} />
                {if $ObjAdvancedTopMenuElementClass && isset($ObjAdvancedTopMenuElementClass->have_icon[$language['id_lang']]) && $ObjAdvancedTopMenuElementClass->have_icon[$language['id_lang']]}
                    <input type="hidden" name="have_icon_{$language.id_lang|intval}" value="{$ObjAdvancedTopMenuElementClass->have_icon[$language['id_lang']]|intval}" /><br />
                    <img src="{$module_path|atm_nofilter}menu_icons/{$ObjAdvancedTopMenuElementClass->id|intval}-{$language.iso_code|escape:'htmlall':'UTF-8'}.{if isset($ObjAdvancedTopMenuElementClass->image_type[$language['id_lang']])}{$ObjAdvancedTopMenuElementClass->image_type[$language['id_lang']]|escape:'htmlall':'UTF-8'}{else}jpg{/if}?{uniqid()}" /><br />
                    <input type="checkbox" name="unlink_icon_{$language.id_lang|intval}" value="1" /> &nbsp; {l s='Delete this image' mod='pm_advancedtopmenu'}
                {/if}
                <small>({l s='gif, jpg, png' mod='pm_advancedtopmenu'})</small>
            </div>
            {/foreach}
            {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='elementimage' return=true}
            <div class="clear"></div>
        </div>

        <label>{l s='Image legend' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            {foreach from=$languages item=language}
            <div id="elementimagelegend_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                <input type="text" maxlength="255" name="image_legend_{$language.id_lang|intval}" value="{if $ObjAdvancedTopMenuElementClass && isset($ObjAdvancedTopMenuElementClass->image_legend[$language['id_lang']])}{$ObjAdvancedTopMenuElementClass->image_legend[$language['id_lang']]|escape:'htmlall':'UTF-8'}{/if}" />
                <br />{l s='(if empty, title will be used)' mod='pm_advancedtopmenu'}
            </div>
            {/foreach}
            {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='elementimagelegend' return=true}
            <div class="clear"></div>
        </div>

        <center>
            <input type="submit" value="{l s='   Save   ' mod='pm_advancedtopmenu'}" name="submitElement" class="button" />
        </center>
    </div><!-- #blocElementForm -->
</form>
{if $ObjAdvancedTopMenuElementClass}
    <script type="text/javascript">
    $(function(){
        showMenuType($("#type_element"), "element");
    });
    </script>
{/if}