<script type="text/javascript">id_language = {$default_language|intval};</script>
{if $shopFeatureActive}
    <div class="warning warn clear">
        {l s='Configuration can not be different by shop. It will be applied to all shop. However, you can create a menu for a particular shop.' mod='pm_advancedtopmenu'}
    </div>
{/if}

<form action="{$base_config_url|atm_nofilter}" method="post" id="menuform_{$module_name|escape:'htmlall':'UTF-8'}" name="menuform_{$module_name|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data" class="width3">
    <div id="blocMenuForm">
    {if $ObjAdvancedTopMenuClass}
        <input type="hidden" name="id_menu" value="{$ObjAdvancedTopMenuClass->id|intval}" />
        <br />
        <a href="{$base_config_url|atm_nofilter}"><img src="../img/admin/arrow2.gif" />{l s='Back' mod='pm_advancedtopmenu'}</a>
        <br class="clear" />
        <br />
    {/if}
        <h3>{l s='General settings' mod='pm_advancedtopmenu'}</h3>
        <label>{l s='Type' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="type" id="type_menu">
                <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
                <option value="1"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 1} selected="selected"{/if}>{l s='CMS' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 2} selected="selected"{/if}>{l s='Link' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 3} selected="selected"{/if}>{l s='Category' mod='pm_advancedtopmenu'}</option>
                <option value="4"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 4} selected="selected"{/if}>{l s='Manufacturer' mod='pm_advancedtopmenu'}</option>
                <option value="5"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 5} selected="selected"{/if}>{l s='Supplier' mod='pm_advancedtopmenu'}</option>
                <option value="6"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 6} selected="selected"{/if}>{l s='Search' mod='pm_advancedtopmenu'}</option>
                <option value="7"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 7} selected="selected"{/if}>{l s='Only image or icon' mod='pm_advancedtopmenu'}</option>
                <option value="9"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 9} selected="selected"{/if}>{l s='Specific page' mod='pm_advancedtopmenu'}</option>
            </select>
        </div>
        <script type="text/javascript">
        $(document).ready(function() {
            $("#type_menu").change(function() {
                showMenuType($(this),"menu");
            });
        });
        </script>

        {if $ObjAdvancedTopMenuClass && in_array($ObjAdvancedTopMenuClass->type, $rebuildable_type)}
            <label>{l s='Rebuild tree' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="rebuild_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="rebuild" id="rebuild_on" value="1" />
                <label class="t" for="rebuild_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="rebuild_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="rebuild" id="rebuild_off" value="0" checked=checked />
                <label class="t" for="rebuild_off"> {l s='No' mod='pm_advancedtopmenu'}</label><br />{l s='Caution, this may change the appearance of your menu !' mod='pm_advancedtopmenu'}
            </div>
        {/if}

        <div class="add_category menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 3}{else} style="display:none;"{/if}>
            {module->outputCategoriesSelect object=$ObjAdvancedTopMenuClass}
            <label>{l s='Include Subcategories' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="menu_subcats_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="include_subs" id="menu_subcats_on" value="1"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 3 && $haveDepend} checked="checked"{/if} />
                <label class="t" for="menu_subcats_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="menu_subcats_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="include_subs" id="menu_subcats_off" value="0" {if !$ObjAdvancedTopMenuClass || ($ObjAdvancedTopMenuClass->type == 3 && !$haveDepend)} checked="checked"{/if} />
                <label class="t" for="menu_subcats_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>
        </div>

        <div class="add_cms menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 1}{else} style="display:none;"{/if}>
            {module->outputCmsSelect cms=$cms object=$ObjAdvancedTopMenuClass}
        </div>

        <div class="add_manufacturer menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 4}{else} style="display:none;"{/if}>
            <label>{l s='All manufacturers' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="menu_submenu_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="include_subs_manu" id="menu_submenu_on" value="1"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 4 && $haveDepend} checked="checked"{/if} />
                <label class="t" for="menu_submenu_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="menu_submenu_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="include_subs_manu" id="menu_submenu_off" value="0" {if !$ObjAdvancedTopMenuClass || ($ObjAdvancedTopMenuClass->type == 4 && !$haveDepend)} checked="checked"{/if} />
                <label class="t" for="menu_submenu_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>
            <script type="text/javascript">
            $("#menu_submenu_on, #menu_submenu_off").click(function() {
                hideNextIfTrue($(this));
            });
            </script>

            <div class="hideNextIfTrue"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 4 && $haveDepend} style="display:none"{/if}>
                {module->outputManufacturerSelect manufacturers=$manufacturer object=$ObjAdvancedTopMenuClass}
            </div>
        </div>

        <div class="add_supplier menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 5}{else} style="display:none;"{/if}>
            <label>{l s='All suppliers' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="menu_subsuppl_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="include_subs_suppl" id="menu_subsuppl_on" value="1"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 5 && $haveDepend} checked="checked"{/if} />
                <label class="t" for="menu_subsuppl_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="menu_subsuppl_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="include_subs_suppl" id="menu_subsuppl_off" value="0" {if !$ObjAdvancedTopMenuClass || ($ObjAdvancedTopMenuClass->type == 5 && !$haveDepend)} checked="checked"{/if} />
                <label class="t" for="menu_subsuppl_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>
            <script type="text/javascript">
            $("#menu_subsuppl_on, #menu_subsuppl_off").click(function() {
                hideNextIfTrue($(this));
            });
            </script>
            <div class="hideNextIfTrue"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 5 && $haveDepend} style="display:none"{/if}>
                {module->outputSupplierSelect suppliers=$supplier object=$ObjAdvancedTopMenuClass}
            </div>
        </div>

        <div class="add_specific_page menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 9}{else} style="display:none;"{/if}>
            {module->outputSpecificPageSelect object=$ObjAdvancedTopMenuClass}
        </div>

        <div class="add_link menu_element"{if $ObjAdvancedTopMenuClass && ($ObjAdvancedTopMenuClass->type != 2 || $ObjAdvancedTopMenuClass->type != 7)} style="display:none;"{/if}>
            <label>{l s='Link' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="menulink_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <input size="20" type="text" name="link_{$language.id_lang|intval}" class="adtmInputLink" value="{if $ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->link[$language['id_lang']])}{$ObjAdvancedTopMenuClass->link[$language['id_lang']]|escape:'htmlall':'UTF-8'}{/if}" maxlength="255" />
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menulink' return=true}
                <div class="clear"></div>
            </div>
        </div>

        <label>{l s='Prevent click on link' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input type="checkbox" name="clickable" id="menu_clickable" value="1" {if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->link[$default_language] == '#'} checked="checked"}{/if} />
            <small>{l s='add a # in the link field, do not remove' mod='pm_advancedtopmenu'}</small>
        </div>
        <script type="text/javascript">
        $("#menu_clickable").click(function() {
            setUnclickable($(this));
        });
        </script>

        {module->outputTargetSelect object=$ObjAdvancedTopMenuClass}

        <div class="add_title menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 7} style="display:none;"{/if}>
            <label>{l s='Title' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="menuname_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <textarea cols="20" rows="2" name="name_{$language.id_lang|intval}" maxlength="255">{if $ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->name[$language['id_lang']])}{$ObjAdvancedTopMenuClass->name[$language['id_lang']]|atm_nofilter}{/if}</textarea>
                    <br />{l s='(if filled, will replace original title)' mod='pm_advancedtopmenu'}
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menuname' return=true}
                <div class="clear"></div>
            </div>
        </div>

        <label>{l s='Active' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="menu_active_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_menu" id="menu_active_on" value="1"{if !$ObjAdvancedTopMenuClass || ($ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->active)} checked="checked"{/if} />
            <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="menu_active_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_menu" id="menu_active_off" value="0"{if $ObjAdvancedTopMenuClass && !$ObjAdvancedTopMenuClass->active} checked="checked"{/if} />
            <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Active on desktop' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="menu_active_desktop_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_desktop_menu" id="menu_active_desktop_on" value="1"{if !$ObjAdvancedTopMenuClass || ($ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->active_desktop)} checked="checked"{/if} />
            <label class="t" for="active_desktop_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="menu_active_desktop_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_desktop_menu" id="menu_active_desktop_off" value="0"{if $ObjAdvancedTopMenuClass && !$ObjAdvancedTopMenuClass->active_desktop} checked="checked"{/if} />
            <label class="t" for="active_desktop_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Active on mobile' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="menu_active_mobile_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_mobile_menu" id="menu_active_mobile_on" value="1"{if !$ObjAdvancedTopMenuClass || ($ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->active_mobile)} checked="checked"{/if} />
            <label class="t" for="active_mobile_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="menu_active_mobile_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_mobile_menu" id="menu_active_mobile_off" value="0"{if $ObjAdvancedTopMenuClass && !$ObjAdvancedTopMenuClass->active_mobile} checked="checked"{/if} />
            <label class="t" for="active_mobile_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Privacy Options' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="privacy">
                <option value="0"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->privacy == 0} selected="selected"{/if}>{l s='For all' mod='pm_advancedtopmenu'}</option>
                <option value="1"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->privacy == 1} selected="selected"{/if}>{l s='Only for visitors' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->privacy == 2} selected="selected"{/if}>{l s='Only for registered users' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->privacy == 3} selected="selected"{/if}>{l s='Only for groups of customers' mod='pm_advancedtopmenu'}</option>
            </select>
        </div>

        {module->outputChosenGroups object=$ObjAdvancedTopMenuClass}

        {if !$imgIconMenuDirIsWritable}
            <div class="warning warn clear">{l s='To upload an icon, please assign CHMOD 777 to the directory:' mod='pm_advancedtopmenu'} {$moduleRootDirectory|escape:'htmlall':'UTF-8'}/menu_icons</div>
        {/if}

        <label>{l s='Icon or image' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            {foreach from=$languages item=language}
            <div id="menuimage_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                <input type="file" name="icon_{$language.id_lang|intval}" size="20"{if !$imgIconMenuDirIsWritable} disabled="disabled"{/if} />
                {if $ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->have_icon[$language['id_lang']]) && $ObjAdvancedTopMenuClass->have_icon[$language['id_lang']]}
                    <input type="hidden" name="have_icon_{$language.id_lang|intval}" value="{$ObjAdvancedTopMenuClass->have_icon[$language['id_lang']]|intval}" /><br />
                    <img src="{$module_path|atm_nofilter}menu_icons/{$ObjAdvancedTopMenuClass->id|intval}-{$language.iso_code|escape:'htmlall':'UTF-8'}.{if isset($ObjAdvancedTopMenuClass->image_type[$language['id_lang']])}{$ObjAdvancedTopMenuClass->image_type[$language['id_lang']]|escape:'htmlall':'UTF-8'}{else}jpg{/if}?{uniqid()}" /><br />
                    <input type="checkbox" name="unlink_icon_{$language.id_lang|intval}" value="1" /> &nbsp; {l s='Delete this image' mod='pm_advancedtopmenu'}
                {/if}
                <small>({l s='gif, jpg, png' mod='pm_advancedtopmenu'})</small>
            </div>
            {/foreach}
            {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menuimage' return=true}
            <div class="clear"></div>
        </div>

        <label>{l s='Image legend' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            {foreach from=$languages item=language}
            <div id="menuimagelegend_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                <input type="text" maxlength="255" name="image_legend_{$language.id_lang|intval}" value="{if $ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->image_legend[$language['id_lang']])}{$ObjAdvancedTopMenuClass->image_legend[$language['id_lang']]|escape:'htmlall':'UTF-8'}{/if}" />
                <br />{l s='(if empty, title will be used)' mod='pm_advancedtopmenu'}
            </div>
            {/foreach}
            {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menuimagelegend' return=true}
            <div class="clear"></div>
        </div>

        <h3>{l s='Style settings' mod='pm_advancedtopmenu'} <small>({l s='if empty, the global styles are used' mod='pm_advancedtopmenu'})</small></h3>

        <div class="add_title menu_element"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->type == 7} style="display:none;"{/if}>
            <label>{l s='Text color' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <input size="20" type="text" name="txt_color_menu_tab" class="pm_colorpicker" value="{if $ObjAdvancedTopMenuClass}{$ObjAdvancedTopMenuClass->txt_color_menu_tab|escape:'htmlall':'UTF-8'}{/if}" />
            </div>
            <label>{l s='Text color over' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <input size="20" type="text" name="txt_color_menu_tab_hover" class="pm_colorpicker" value="{if $ObjAdvancedTopMenuClass}{$ObjAdvancedTopMenuClass->txt_color_menu_tab_hover|escape:'htmlall':'UTF-8'}{/if}" />
            </div>
        </div>

        <label>{l s='Background color' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="fnd_color_menu_tab[0]" id="fnd_color_menu_tab_0" class="pm_colorpicker" value="{if  $fnd_color_menu_tab_color1}{$fnd_color_menu_tab_color1|escape:'htmlall':'UTF-8'}{/if}" size="20" />
            &nbsp; <span{if isset($fnd_color_menu_tab_color2) && $fnd_color_menu_tab_color2}{else} style="display:none"{/if} id="fnd_color_menu_tab_gradient"><input size="20" type="text" class="pm_colorpicker" name="fnd_color_menu_tab[1]" id="fnd_color_menu_tab_1" value="{if !isset($fnd_color_menu_tab_color2) || ! $fnd_color_menu_tab_color2}{else}{$fnd_color_menu_tab_color2|escape:'htmlall':'UTF-8'}{/if}" size="20" /></span>
            &nbsp; <input type="checkbox" name="fnd_color_menu_tab_gradient" value="1"{if isset($fnd_color_menu_tab_color2) && $fnd_color_menu_tab_color2} checked="checked"{/if} /> &nbsp; {l s='Make a gradient' mod='pm_advancedtopmenu'}
        </div>
        <script type="text/javascript">
        $("input[name=fnd_color_menu_tab_gradient]").click(function() {
            showSpanIfChecked($(this), "#fnd_color_menu_tab_gradient");
        });
        </script>

        <label>{l s='Background color over' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="fnd_color_menu_tab_over[0]" id="fnd_color_menu_tab_over_0" class="pm_colorpicker" value="{if  $fnd_color_menu_tab_over_color1}{$fnd_color_menu_tab_over_color1|escape:'htmlall':'UTF-8'}{/if}" size="20" />
            &nbsp; <span{if isset($fnd_color_menu_tab_over_color2) && $fnd_color_menu_tab_over_color2}{else} style="display:none"{/if} id="fnd_color_menu_tab_over_gradient"><input size="20" type="text" class="pm_colorpicker" name="fnd_color_menu_tab_over[1]" id="fnd_color_menu_tab_over_1" value="{if !isset($fnd_color_menu_tab_over_color2) || ! $fnd_color_menu_tab_over_color2}{else}{$fnd_color_menu_tab_over_color2|escape:'htmlall':'UTF-8'}{/if}" size="20" /></span>
            &nbsp; <input type="checkbox" name="fnd_color_menu_tab_over_gradient" value="1"{if isset($fnd_color_menu_tab_over_color2) && $fnd_color_menu_tab_over_color2} checked="checked"{/if} /> &nbsp; {l s='Make a gradient' mod='pm_advancedtopmenu'}
        </div>
        <script type="text/javascript">
        $("input[name=fnd_color_menu_tab_over_gradient]").click(function() {
            showSpanIfChecked($(this), "#fnd_color_menu_tab_over_gradient");
        });
        </script>

        <label>{l s='Border size' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            {l s='top' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_tab[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_tab !== '0' && isset($borders_size_tab[0])}{$borders_size_tab.0|intval}{/if}" /> &nbsp;
            {l s='right' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_tab[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_tab !== '0' && isset($borders_size_tab[1])}{$borders_size_tab.1|intval}{/if}" /> &nbsp;
            {l s='bottom' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_tab[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_tab !== '0' && isset($borders_size_tab[2])}{$borders_size_tab.2|intval}{/if}" /> &nbsp;
            {l s='left' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_tab[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_tab !== '0' && isset($borders_size_tab[3])}{$borders_size_tab.3|intval}{/if}" />
        </div>

        <label>{l s='Border color' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="border_color_tab" class="pm_colorpicker" value="{if $ObjAdvancedTopMenuClass}{$ObjAdvancedTopMenuClass->border_color_tab|escape:'htmlall':'UTF-8'}{/if}" />
        </div>

        <h4>{l s='Submenu settings' mod='pm_advancedtopmenu'}</h4>

        <label>{l s='Width (px)' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="width_submenu" value="{if $ObjAdvancedTopMenuClass}{$ObjAdvancedTopMenuClass->width_submenu|escape:'htmlall':'UTF-8'}{/if}" />
            <small>({l s='Put 0 for automatic width' mod='pm_advancedtopmenu'})</small>
        </div>

        <label>{l s='Minimal height (px)' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="minheight_submenu" value="{if $ObjAdvancedTopMenuClass}{$ObjAdvancedTopMenuClass->minheight_submenu|escape:'htmlall':'UTF-8'}{/if}" />
            <small>({l s='Put 0 for automatic height' mod='pm_advancedtopmenu'})</small>
        </div>

        <label>{l s='Position' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="position_submenu">
                <option value=""{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->position_submenu == 0} selected="selected"{/if}>{l s='Use global styles' mod='pm_advancedtopmenu'}</option>
                <option value="1"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->position_submenu == 1} selected="selected"{/if}>{l s='Left-aligned current menu' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->position_submenu == 3} selected="selected"{/if}>{l s='Right-aligned current menu' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->position_submenu == 2} selected="selected"{/if}>{l s='Left-aligned global menu' mod='pm_advancedtopmenu'}</option>
            </select> &nbsp; <span></span>
        </div>

        <label>{l s='Background color' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="fnd_color_submenu[0]" id="fnd_color_submenu_0" class="pm_colorpicker" value="{if  $fnd_color_submenu_color1}{$fnd_color_submenu_color1|escape:'htmlall':'UTF-8'}{/if}" size="20" />
            &nbsp; <span{if isset($fnd_color_submenu_color2) && $fnd_color_submenu_color2}{else} style="display:none"{/if} id="fnd_color_submenu_gradient"><input size="20" type="text" class="pm_colorpicker" name="fnd_color_submenu[1]" id="fnd_color_submenu_1" value="{if !isset($fnd_color_submenu_color2) || ! $fnd_color_submenu_color2}{else}{$fnd_color_submenu_color2|escape:'htmlall':'UTF-8'}{/if}" size="20" /></span>
            &nbsp; <input type="checkbox" name="fnd_color_submenu_gradient" value="1"{if isset($fnd_color_submenu_color2) && $fnd_color_submenu_color2} checked="checked"{/if} /> &nbsp; {l s='Make a gradient' mod='pm_advancedtopmenu'}
        </div>
        <script type="text/javascript">
        $("input[name=fnd_color_submenu_gradient]").click(function() {
            showSpanIfChecked($(this),"#fnd_color_submenu_gradient");
        });
        </script>

        <label>{l s='Border size' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            {l s='top' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_submenu[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_submenu !== '0' && isset($borders_size_submenu[0])}{$borders_size_submenu.0|intval}{/if}" /> &nbsp;
            {l s='right' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_submenu[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_submenu !== '0' && isset($borders_size_submenu[1])}{$borders_size_submenu.1|intval}{/if}" /> &nbsp;
            {l s='bottom' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_submenu[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_submenu !== '0' && isset($borders_size_submenu[2])}{$borders_size_submenu.2|intval}{/if}" /> &nbsp;
            {l s='left' mod='pm_advancedtopmenu'} <input size="3" type="text" name="border_size_submenu[]" value="{if $ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->border_size_submenu !== '0' && isset($borders_size_submenu[3])}{$borders_size_submenu.3|intval}{/if}" />
        </div>

        <label>{l s='Border color' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <input size="20" type="text" name="border_color_submenu" class="pm_colorpicker" value="{if $ObjAdvancedTopMenuClass}{$ObjAdvancedTopMenuClass->border_color_submenu|escape:'htmlall':'UTF-8'}{/if}" />
        </div>

        <label>{l s='Show additionnal text settings' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="tinymce_container_toggle_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="tinymce_container_toggle_menu" id="tinymce_container_toggle_on" value="1"{if $hasAdditionnalText} checked="checked"{/if} />
            <label class="t" for="tinymce_container_toggle_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="tinymce_container_toggle_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="tinymce_container_toggle_menu" id="tinymce_container_toggle_off" value="0"{if !$hasAdditionnalText} checked="checked"{/if} />
            <label class="t" for="tinymce_container_toggle_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <div class="tinymce_container"{if $hasAdditionnalText} style="display: block"{/if}>
            <label>{l s='Text displayed above columns' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                    <div id="menu_value_over_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                        {* HTML *}<textarea class="rte" cols="100" rows="10" name="value_over_{$language.id_lang|intval}">{if $ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->value_over[$language['id_lang']])}{htmlentities(Tools::stripslashes($ObjAdvancedTopMenuClass->value_over[$language['id_lang']]), $smarty.const.ENT_COMPAT, 'UTF-8')}{/if}</textarea>
                    </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menu_value_over' return=true}
                <div class="clear"></div>
            </div>

            <label>{l s='Text displayed below columns' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                    <div id="menu_value_under_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                        {* HTML *}<textarea class="rte" cols="100" rows="10"  name="value_under_{$language.id_lang|intval}">{if $ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->value_under[$language['id_lang']])}{htmlentities(Tools::stripslashes($ObjAdvancedTopMenuClass->value_under[$language['id_lang']]), $smarty.const.ENT_COMPAT, 'UTF-8')}{/if}</textarea>
                    </div>
                {/foreach}
            </div>
            {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menu_value_under' return=true}
            <div class="clear"></div>
        </div><!-- .tinymce_container -->

        <center>
            <input type="submit" value="{l s='   Save   ' mod='pm_advancedtopmenu'}" name="submitMenu" class="button" />
        </center>
    </div><!-- #blocMenuForm -->
</form>
{if $ObjAdvancedTopMenuClass}
    <script type="text/javascript">
    $(function(){
        showMenuType($("#type_menu"), "menu");
    });
    </script>
{/if}