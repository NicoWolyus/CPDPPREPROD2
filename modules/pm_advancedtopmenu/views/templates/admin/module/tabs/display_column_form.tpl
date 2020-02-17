<script type="text/javascript">id_language = {$default_language|intval};</script>
{if $shopFeatureActive}
    <div class="warning warn clear">
        {l s='Configuration can not be different by shop. It will be applied to all shop.' mod='pm_advancedtopmenu'}
    </div>
{/if}

<form action="{$base_config_url|atm_nofilter}" method="post" id="formColumn_{$module_name|escape:'htmlall':'UTF-8'}" name="form_{$module_name|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data" class="width3">
    <div id="blocColumnForm">
    {if $ObjAdvancedTopMenuColumnClass}
        <input type="hidden" name="id_column" value="{$ObjAdvancedTopMenuColumnClass->id|intval}" />
        <br />
        <a href="{$base_config_url|atm_nofilter}"><img src="../img/admin/arrow2.gif" />{l s='Back' mod='pm_advancedtopmenu'}</a>
        <br class="clear" />
        <br />
    {/if}
        <h3>{l s='General settings' mod='pm_advancedtopmenu'}</h3>

        <label>{l s='Parent tab' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="id_menu" id="id_menu_select2">
                <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
                {foreach from=$menus item=menu}
                    <option value="{$menu.id_menu|intval}"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->id_menu == $menu.id_menu} selected="selected"{/if}>{module->getAdminOutputNameValue row=$menu withExtra=false}</option>
                {/foreach}
            </select>
        </div>

        <label>{l s='Parent column' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form" id="columnWrap_select">
        {if Validate::isLoadedObject($ObjAdvancedTopMenuColumnClass)}
            {module->outputSelectColumnsWrap id_menu=$ObjAdvancedTopMenuColumnClass->id_menu columnWrap_selected=$ObjAdvancedTopMenuColumnClass->id_wrap}
        {elseif !Validate::isLoadedObject($ObjAdvancedTopMenuColumnClass) && isset($menus) && is_array($menus) && count($menus) == 1}
            {module->outputSelectColumnsWrap id_menu=$menus[0]['id_menu']}
        {else}
            <div class="error inline-alert">
                <strong><u>{l s='Please select a parent tab!' mod='pm_advancedtopmenu'}</u></strong>
            </div>
            <script type="text/javascript">
            $(document).ready(function() {
                $('input[name="submitColumn"]').attr('disabled', 'disabled').prop('disabled', true);
            });
            </script>
        {/if}
        </div>
        <script type="text/javascript">
        $(document).ready(function() {
            $("#id_menu_select2").change(function() {
                showColumnWrapSelect($(this));
            });
        });
        </script>

        <label>{l s='Type' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="type" id="type_column">
                <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
                <option value="1"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 1} selected="selected"{/if}>{l s='CMS' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 2} selected="selected"{/if}>{l s='Link' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 3} selected="selected"{/if}>{l s='Category' mod='pm_advancedtopmenu'}</option>
                <option value="4"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 4} selected="selected"{/if}>{l s='Manufacturer' mod='pm_advancedtopmenu'}</option>
                <option value="5"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 5} selected="selected"{/if}>{l s='Supplier' mod='pm_advancedtopmenu'}</option>
                <option value="6"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 6} selected="selected"{/if}>{l s='Search' mod='pm_advancedtopmenu'}</option>
                <option value="7"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 7} selected="selected"{/if}>{l s='Only image or icon' mod='pm_advancedtopmenu'}</option>
                <option value="8"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 8} selected="selected"{/if}>{l s='Product' mod='pm_advancedtopmenu'}</option>
                <option value="9"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 9} selected="selected"{/if}>{l s='Specific page' mod='pm_advancedtopmenu'}</option>
            </select>
        </div>
        <script type="text/javascript">
        $(document).ready(function() {
            $("#type_column").change(function() {
                showMenuType($(this), "column");
            });
        });
        </script>

        <div class="add_category menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 3}{else} style="display:none;"{/if}>
            {module->outputCategoriesSelect object=$ObjAdvancedTopMenuColumnClass}
            <label>{l s='Include Subcategories' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="column_subcats_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="include_subs" id="column_subcats_on" value="1"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 3 && $haveDepend} checked="checked"{/if} />
                <label class="t" for="column_subcats_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="column_subcats_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="include_subs" id="column_subcats_off" value="0" {if !$ObjAdvancedTopMenuColumnClass || ($ObjAdvancedTopMenuColumnClass->type == 3 && !$haveDepend)} checked="checked"{/if} />
                <label class="t" for="column_subcats_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>
        </div>

        <div class="add_cms menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 1}{else} style="display:none;"{/if}>
            {module->outputCmsSelect cms=$cms object=$ObjAdvancedTopMenuColumnClass}
        </div>

        <div class="add_manufacturer menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 4}{else} style="display:none;"{/if}>
            <label>{l s='All manufacturers' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="column_submenu_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="include_subs_manu" id="column_submenu_on" value="1"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 4 && $haveDepend} checked="checked"{/if} />
                <label class="t" for="column_submenu_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="column_submenu_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="include_subs_manu" id="column_submenu_off" value="0" {if !$ObjAdvancedTopMenuColumnClass || ($ObjAdvancedTopMenuColumnClass->type == 4 && !$haveDepend)} checked="checked"{/if} />
                <label class="t" for="column_submenu_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>
            <script type="text/javascript">
            $("#column_submenu_on, #column_submenu_off").click(function() {
                hideNextIfTrue($(this));
            });
            </script>

            <div class="hideNextIfTrue"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 4 && $haveDepend} style="display:none"{/if}>
                {module->outputManufacturerSelect manufacturers=$manufacturer object=$ObjAdvancedTopMenuColumnClass}
            </div>
        </div>

        <div class="add_supplier menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 5}{else} style="display:none;"{/if}>
            <label>{l s='All suppliers' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="column_subsuppl_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="include_subs_suppl" id="column_subsuppl_on" value="1"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 5 && $haveDepend} checked="checked"{/if} />
                <label class="t" for="column_subsuppl_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="column_subsuppl_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="include_subs_suppl" id="column_subsuppl_off" value="0" {if !$ObjAdvancedTopMenuColumnClass || ($ObjAdvancedTopMenuColumnClass->type == 5 && !$haveDepend)} checked="checked"{/if} />
                <label class="t" for="column_subsuppl_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>
            <script type="text/javascript">
            $("#column_subsuppl_on, #column_subsuppl_off").click(function() {
                hideNextIfTrue($(this));
            });
            </script>
            <div class="hideNextIfTrue"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 5 && $haveDepend} style="display:none"{/if}>
                {module->outputSupplierSelect suppliers=$supplier object=$ObjAdvancedTopMenuColumnClass}
            </div>
        </div>

        <div class="add_specific_page menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 9}{else} style="display:none;"{/if}>
            {module->outputSpecificPageSelect object=$ObjAdvancedTopMenuColumnClass}
        </div>

        <div class="add_link menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type != 2} style="display:none;"{/if}>
            <label>{l s='Link' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="columnlink_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <input size="20" type="text" name="link_{$language.id_lang|intval}" class="adtmInputLink" value="{if $ObjAdvancedTopMenuColumnClass && isset($ObjAdvancedTopMenuColumnClass->link[$language['id_lang']])}{$ObjAdvancedTopMenuColumnClass->link[$language['id_lang']]|escape:'htmlall':'UTF-8'}{/if}" maxlength="255" />
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='columnlink' return=true}
                <div class="clear"></div>
            </div>
        </div>

        <div class="prevent_click menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 8} style="display:none;"{/if}>
            <label>{l s='Prevent click on link' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <input type="checkbox" name="clickable" id="group_clickable" value="1" {if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->link[$default_language] == '#'} checked="checked"}{/if} />
                <small>{l s='add a # in the link field, do not remove' mod='pm_advancedtopmenu'}</small>
            </div>
        </div>
        <script type="text/javascript">
        $("#group_clickable").click(function() {
            setUnclickable($(this));
        });
        </script>

        {module->outputTargetSelect object=$ObjAdvancedTopMenuColumnClass}

        {if $ObjAdvancedTopMenuColumnClass && in_array($ObjAdvancedTopMenuColumnClass->type, $rebuildable_type)}
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

        <div class="add_title menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 7} style="display:none;"{/if}>
            <label>{l s='Title' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="columnname_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <textarea cols="20" rows="2" name="name_{$language.id_lang|intval}" maxlength="255">{if $ObjAdvancedTopMenuColumnClass && isset($ObjAdvancedTopMenuColumnClass->name[$language['id_lang']])}{$ObjAdvancedTopMenuColumnClass->name[$language['id_lang']]|atm_nofilter}{/if}</textarea>
                    <br />{l s='(if filled, will replace original title)' mod='pm_advancedtopmenu'}
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='columnname' return=true}
                <div class="clear"></div>
            </div>
        </div>

        {* Product *}
        <div class="add_product_settings menu_element"{if !$ObjAdvancedTopMenuColumnClass || $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type != 8} style="display:none;"{/if}>
            <label>{l s='Product' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form" style="padding-left: 0">
                <input size="40" type="text" id="id_product_search" value="" placeholder="{l s='Enter your product name/ref here' mod='pm_advancedtopmenu'}" />&nbsp;
                <p class="adtm_current_product_name">
                    {l s='Current product:' mod='pm_advancedtopmenu'} <span id="current_product_name">{$currentProductName|escape:'htmlall':'UTF-8'} (ID: {if $ObjAdvancedTopMenuProductColumnClass && isset($ObjAdvancedTopMenuProductColumnClass->id_product)}{$ObjAdvancedTopMenuProductColumnClass->id_product|intval}{else}N/A{/if})</span>
                </p>
                <input size="20" type="hidden" name="id_product" value="{if $ObjAdvancedTopMenuProductColumnClass && isset($ObjAdvancedTopMenuProductColumnClass->id_product)}{$ObjAdvancedTopMenuProductColumnClass->id_product|intval}{/if}" />
            </div>

            <label>{l s='Show title' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="show_title_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="show_title" id="show_title_on" value="1"{if !$ObjAdvancedTopMenuProductColumnClass || ($ObjAdvancedTopMenuProductColumnClass && $ObjAdvancedTopMenuProductColumnClass->show_title)} checked="checked"{/if} />
                <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="show_title_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="show_title" id="show_title_off" value="0"{if $ObjAdvancedTopMenuProductColumnClass && !$ObjAdvancedTopMenuProductColumnClass->show_title} checked="checked"{/if} />
                <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>

            <label>{l s='Show price' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                <label class="t" for="show_price_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                <input type="radio" name="show_price" id="show_price_on" value="1"{if !$ObjAdvancedTopMenuProductColumnClass || ($ObjAdvancedTopMenuProductColumnClass && $ObjAdvancedTopMenuProductColumnClass->show_price)} checked="checked"{/if} />
                <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                <label class="t" for="show_price_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                <input type="radio" name="show_price" id="show_price_off" value="0"{if $ObjAdvancedTopMenuProductColumnClass && !$ObjAdvancedTopMenuProductColumnClass->show_price} checked="checked"{/if} />
                <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
            </div>

            {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<')}
                <label>{l s='Show "Add to cart" button' mod='pm_advancedtopmenu'}</label>
                <div class="margin-form">
                    <label class="t" for="show_add_to_cart_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                    <input type="radio" name="show_add_to_cart" id="show_add_to_cart_on" value="1"{if !$ObjAdvancedTopMenuProductColumnClass || ($ObjAdvancedTopMenuProductColumnClass && $ObjAdvancedTopMenuProductColumnClass->show_add_to_cart)} checked="checked"{/if} />
                    <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                    <label class="t" for="show_add_to_cart_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                    <input type="radio" name="show_add_to_cart" id="show_add_to_cart_off" value="0"{if $ObjAdvancedTopMenuProductColumnClass && !$ObjAdvancedTopMenuProductColumnClass->show_add_to_cart} checked="checked"{/if} />
                    <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
                </div>

                <label>{l s='Show "More" button' mod='pm_advancedtopmenu'}</label>
                <div class="margin-form">
                    <label class="t" for="show_more_info_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                    <input type="radio" name="show_more_info" id="show_more_info_on" value="1"{if !$ObjAdvancedTopMenuProductColumnClass || ($ObjAdvancedTopMenuProductColumnClass && $ObjAdvancedTopMenuProductColumnClass->show_more_info)} checked="checked"{/if} />
                    <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                    <label class="t" for="show_more_info_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                    <input type="radio" name="show_more_info" id="show_more_info_off" value="0"{if $ObjAdvancedTopMenuProductColumnClass && !$ObjAdvancedTopMenuProductColumnClass->show_more_info} checked="checked"{/if} />
                    <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
                </div>

                {if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>=')}
                    <label>{l s='Show "Quick view" button' mod='pm_advancedtopmenu'}</label>
                    <div class="margin-form">
                        <label class="t" for="show_quick_view_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
                        <input type="radio" name="show_quick_view" id="show_quick_view_on" value="1"{if !$ObjAdvancedTopMenuProductColumnClass || ($ObjAdvancedTopMenuProductColumnClass && $ObjAdvancedTopMenuProductColumnClass->show_quick_view)} checked="checked"{/if} />
                        <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
                        <label class="t" for="show_quick_view_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
                        <input type="radio" name="show_quick_view" id="show_quick_view_off" value="0"{if $ObjAdvancedTopMenuProductColumnClass && !$ObjAdvancedTopMenuProductColumnClass->show_quick_view} checked="checked"{/if} />
                        <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
                    </div>
                {/if}

                <label>{l s='Active' mod='pm_advancedtopmenu'}</label>
                <div class="margin-form">
                    <select name="p_image_type">
                        <!-- <option value="">-- {l s='Choose' mod='pm_advancedtopmenu'} --</option> -->
                        {foreach from=$productImagesTypes key=image_type_key item=image_type}
                        <option value="{$image_type_key|escape:'htmlall':'UTF-8'}"{if $ObjAdvancedTopMenuProductColumnClass && $ObjAdvancedTopMenuProductColumnClass->p_image_type == $image_type_key} selected="selected"{/if}>{$image_type|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            {/if}
        </div>

        <label>{l s='Active' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="column_active_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_column" id="column_active_on" value="1"{if !$ObjAdvancedTopMenuColumnClass || ($ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->active)} checked="checked"{/if} />
            <label class="t" for="active_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="column_active_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_column" id="column_active_off" value="0"{if $ObjAdvancedTopMenuColumnClass && !$ObjAdvancedTopMenuColumnClass->active} checked="checked"{/if} />
            <label class="t" for="active_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Active on desktop' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="column_active_desktop_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_desktop_column" id="column_active_desktop_on" value="1"{if !$ObjAdvancedTopMenuColumnClass || ($ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->active_desktop)} checked="checked"{/if} />
            <label class="t" for="active_desktop_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="column_active_desktop_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_desktop_column" id="column_active_desktop_off" value="0"{if $ObjAdvancedTopMenuColumnClass && !$ObjAdvancedTopMenuColumnClass->active_desktop} checked="checked"{/if} />
            <label class="t" for="active_desktop_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Active on mobile' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <label class="t" for="column_active_mobile_on"><img src="../img/admin/enabled.gif" alt="{l s='Yes' mod='pm_advancedtopmenu'}" title="{l s='Yes' mod='pm_advancedtopmenu'}" /></label>
            <input type="radio" name="active_mobile_column" id="column_active_mobile_on" value="1"{if !$ObjAdvancedTopMenuColumnClass || ($ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->active_mobile)} checked="checked"{/if} />
            <label class="t" for="active_mobile_on"> {l s='Yes' mod='pm_advancedtopmenu'}</label>
            <label class="t" for="column_active_mobile_off"><img src="../img/admin/disabled.gif" alt="{l s='No' mod='pm_advancedtopmenu'}" title="{l s='No' mod='pm_advancedtopmenu'}" style="margin-left: 10px;" /></label>
            <input type="radio" name="active_mobile_column" id="column_active_mobile_off" value="0"{if $ObjAdvancedTopMenuColumnClass && !$ObjAdvancedTopMenuColumnClass->active_mobile} checked="checked"{/if} />
            <label class="t" for="active_mobile_off"> {l s='No' mod='pm_advancedtopmenu'}</label>
        </div>

        <label>{l s='Privacy Options' mod='pm_advancedtopmenu'}</label>
        <div class="margin-form">
            <select name="privacy">
                <option value="0"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->privacy == 0} selected="selected"{/if}>{l s='For all' mod='pm_advancedtopmenu'}</option>
                <option value="1"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->privacy == 1} selected="selected"{/if}>{l s='Only for visitors' mod='pm_advancedtopmenu'}</option>
                <option value="2"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->privacy == 2} selected="selected"{/if}>{l s='Only for registered users' mod='pm_advancedtopmenu'}</option>
                <option value="3"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->privacy == 3} selected="selected"{/if}>{l s='Only for groups of customers' mod='pm_advancedtopmenu'}</option>
            </select>
        </div>

        {module->outputChosenGroups object=$ObjAdvancedTopMenuColumnClass}

        {if !$imgIconColumnDirIsWritable}
            <div class="warning warn clear">{l s='To upload an icon, please assign CHMOD 777 to the directory:' mod='pm_advancedtopmenu'} {$moduleRootDirectory|escape:'htmlall':'UTF-8'}/column_icons</div>
        {/if}

        <div class="add_image menu_element"{if $ObjAdvancedTopMenuColumnClass && $ObjAdvancedTopMenuColumnClass->type == 8} style="display:none;"{/if}>
            <label>{l s='Icon or image' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="columnimage_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <input type="file" name="icon_{$language.id_lang|intval}" size="20"{if !$imgIconColumnDirIsWritable} disabled="disabled"{/if} />
                    {if $ObjAdvancedTopMenuColumnClass && isset($ObjAdvancedTopMenuColumnClass->have_icon[$language['id_lang']]) && $ObjAdvancedTopMenuColumnClass->have_icon[$language['id_lang']]}
                        <input type="hidden" name="have_icon_{$language.id_lang|intval}" value="{$ObjAdvancedTopMenuColumnClass->have_icon[$language['id_lang']]|intval}" /><br />
                        <img src="{$module_path|atm_nofilter}column_icons/{$ObjAdvancedTopMenuColumnClass->id|intval}-{$language.iso_code|escape:'htmlall':'UTF-8'}.{if isset($ObjAdvancedTopMenuColumnClass->image_type[$language['id_lang']])}{$ObjAdvancedTopMenuColumnClass->image_type[$language['id_lang']]|escape:'htmlall':'UTF-8'}{else}jpg{/if}?{uniqid()}" /><br />
                        <input type="checkbox" name="unlink_icon_{$language.id_lang|intval}" value="1" /> &nbsp; {l s='Delete this image' mod='pm_advancedtopmenu'}
                    {/if}
                    <small>({l s='gif, jpg, png' mod='pm_advancedtopmenu'})</small>
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='columnimage' return=true}
                <div class="clear"></div>
            </div>

            <label>{l s='Image legend' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                <div id="columnimagelegend_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                    <input type="text" maxlength="255" name="image_legend_{$language.id_lang|intval}" value="{if $ObjAdvancedTopMenuColumnClass && isset($ObjAdvancedTopMenuColumnClass->image_legend[$language['id_lang']])}{$ObjAdvancedTopMenuColumnClass->image_legend[$language['id_lang']]|escape:'htmlall':'UTF-8'}{/if}" />
                    <br />{l s='(if empty, title will be used)' mod='pm_advancedtopmenu'}
                </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='columnimagelegend' return=true}
                <div class="clear"></div>
            </div>
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
            <label>{l s='Text displayed above group' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                    <div id="column_value_over_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                        {* HTML *}<textarea class="rte" cols="100" rows="10" name="value_over_{$language.id_lang|intval}">{if $ObjAdvancedTopMenuColumnClass && isset($ObjAdvancedTopMenuColumnClass->value_over[$language['id_lang']])}{htmlentities(Tools::stripslashes($ObjAdvancedTopMenuColumnClass->value_over[$language['id_lang']]), $smarty.const.ENT_COMPAT, 'UTF-8')}{/if}</textarea>
                    </div>
                {/foreach}
                {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menu_value_over' return=true}
                <div class="clear"></div>
            </div>

            <label>{l s='Text displayed below group' mod='pm_advancedtopmenu'}</label>
            <div class="margin-form">
                {foreach from=$languages item=language}
                    <div id="column_value_under_{$language.id_lang|intval}" style="display: {if $language.id_lang == $default_language}block{else}none{/if}; float: left;">
                        {* HTML *}<textarea class="rte" cols="100" rows="10"  name="value_under_{$language.id_lang|intval}">{if $ObjAdvancedTopMenuColumnClass && isset($ObjAdvancedTopMenuColumnClass->value_under[$language['id_lang']])}{htmlentities(Tools::stripslashes($ObjAdvancedTopMenuColumnClass->value_under[$language['id_lang']]), $smarty.const.ENT_COMPAT, 'UTF-8')}{/if}</textarea>
                    </div>
                {/foreach}
            </div>
            {module->displayFlags languages=$languages default_language=$default_language ids=$ids_lang id='menu_value_under' return=true}
            <div class="clear"></div>
        </div><!-- .tinymce_container -->

        <center>
            <input type="submit" value="{l s='   Save   ' mod='pm_advancedtopmenu'}" name="submitColumn" class="button" />
        </center>
    </div><!-- #blocColumnForm -->
</form>
{if $ObjAdvancedTopMenuColumnClass}
    <script type="text/javascript">
    $(function(){
        showMenuType($("#type_column"), "column");
    });
    </script>
{/if}