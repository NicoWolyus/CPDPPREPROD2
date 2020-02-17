{extends file="helpers/form/form.tpl"}

{block name="defaultForm"}
    <script>
        var general_settings = "{$general_settings|escape:'htmlall':'UTF-8'}";
        var advance_settings = "{$advance_settings|escape:'htmlall':'UTF-8'}";
        var display_settings = "{$display_settings|escape:'htmlall':'UTF-8'}";
        var selector_settings = "{$selector_settings|escape:'htmlall':'UTF-8'}";
        var display_end_page_message = "{$display_end_page_message|escape:'htmlall':'UTF-8'}";
        var display_loading_message = "{$display_loading_message|escape:'htmlall':'UTF-8'}";
        var enable_sandbox_setting = "{$enable_sandbox_setting|escape:'htmlall':'UTF-8'}";
        var scroll_type = "{$scroll_type|escape:'htmlall':'UTF-8'}";
        var my_ip_address = "{$my_ip_address|escape:'htmlall':'UTF-8'}";
        var greater_than_zero_msg = "{l s='Field value must be from 1 to 10000.' mod='infinitescroll'}";
        var invalid_ip_msg = "{l s='Invalid IP format.' mod='infinitescroll'}";
        var custom_css_length = "{l s='Maximum 10000 characters allowed at Custom CSS.' mod='infinitescroll'}";
        var custom_js_length = "{l s='Maximum 10000 characters allowed at Custom JS.' mod='infinitescroll'}";
        var add_ip_length = "{l s='Maximum 1000 IP Address allowed.' mod='infinitescroll'}";
        var selector_length = "{l s='Maximum 1000 characters allowed.' mod='infinitescroll'}";
        var ip_already_exist = "{l s='IP already exist in the field.' mod='infinitescroll'}";
        var duplicate_ips = "{l s='Field contains duplicate IP address' mod='infinitescroll'}";
        
        //error messages for velovalidation.js
        velovalidation.setErrorLanguage({
            empty_fname: "{l s='Please enter First name.' mod='infinitescroll'}",
            maxchar_fname: "{l s='First name cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_fname: "{l s='First name cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_mname: "{l s='Please enter middle name.' mod='infinitescroll'}",
            maxchar_mname: "{l s='Middle name cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_mname: "{l s='Middle name cannot be less than {#} characters.' mod='infinitescroll'}",
            only_alphabet: "{l s='Only alphabets are allowed.' mod='infinitescroll'}",
            empty_lname: "{l s='Please enter Last name.' mod='infinitescroll'}",
            maxchar_lname: "{l s='Last name cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_lname: "{l s='Last name cannot be less than {#} characters.' mod='infinitescroll'}",
            alphanumeric: "{l s='Field should be alphanumeric.' mod='infinitescroll'}",
            empty_pass: "{l s='Please enter Password.' mod='infinitescroll'}",
            maxchar_pass: "{l s='Password cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_pass: "{l s='Password cannot be less than {#} characters.' mod='infinitescroll'}",
            specialchar_pass: "{l s='Password should contain atleast 1 special character.' mod='infinitescroll'}",
            alphabets_pass: "{l s='Password should contain alphabets.' mod='infinitescroll'}",
            capital_alphabets_pass: "{l s='Password should contain atleast 1 capital letter.' mod='infinitescroll'}",
            small_alphabets_pass: "{l s='Password should contain atleast 1 small letter.' mod='infinitescroll'}",
            digit_pass: "{l s='Password should contain atleast 1 digit.' mod='infinitescroll'}",
            empty_field: "{l s='Field cannot be empty.' mod='infinitescroll'}",
            number_field: "{l s='You can enter only numbers.' mod='infinitescroll'}",            
            positive_number: "{l s='Number should be greater than 0.' mod='infinitescroll'}",
            maxchar_field: "{l s='Field cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_field: "{l s='Field cannot be less than {#} character(s).' mod='infinitescroll'}",
            empty_email: "{l s='Please enter Email.' mod='infinitescroll'}",
            validate_email: "{l s='Please enter a valid Email.' mod='infinitescroll'}",
            empty_country: "{l s='Please enter country name.' mod='infinitescroll'}",
            maxchar_country: "{l s='Country cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_country: "{l s='Country cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_city: "{l s='Please enter city name.' mod='infinitescroll'}",
            maxchar_city: "{l s='City cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_city: "{l s='City cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_state: "{l s='Please enter state name.' mod='infinitescroll'}",
            maxchar_state: "{l s='State cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_state: "{l s='State cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_proname: "{l s='Please enter product name.' mod='infinitescroll'}",
            maxchar_proname: "{l s='Product cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_proname: "{l s='Product cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_catname: "{l s='Please enter category name.' mod='infinitescroll'}",
            maxchar_catname: "{l s='Category cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_catname: "{l s='Category cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_zip: "{l s='Please enter zip code.' mod='infinitescroll'}",
            maxchar_zip: "{l s='Zip cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_zip: "{l s='Zip cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_username: "{l s='Please enter Username.' mod='infinitescroll'}",
            maxchar_username: "{l s='Username cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_username: "{l s='Username cannot be less than {#} characters.' mod='infinitescroll'}",
            invalid_date: "{l s='Invalid date format.' mod='infinitescroll'}",
            maxchar_sku: "{l s='SKU cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_sku: "{l s='SKU cannot be less than {#} characters.' mod='infinitescroll'}",
            invalid_sku: "{l s='Invalid SKU format.' mod='infinitescroll'}",
            empty_sku: "{l s='Please enter SKU.' mod='infinitescroll'}",
            validate_range: "{l s='Number is not in the valid range. It should be betwen {##} and {###}' mod='infinitescroll'}",
            empty_address: "{l s='Please enter address.' mod='infinitescroll'}",
            minchar_address: "{l s='Address cannot be less than {#} characters.' mod='infinitescroll'}",
            maxchar_address: "{l s='Address cannot be greater than {#} characters.' mod='infinitescroll'}",
            empty_company: "{l s='Please enter company name.' mod='infinitescroll'}",
            minchar_company: "{l s='Company name cannot be less than {#} characters.' mod='infinitescroll'}",
            maxchar_company: "{l s='Company name cannot be greater than {#} characters.' mod='infinitescroll'}",
            invalid_phone: "{l s='Phone number is invalid.' mod='infinitescroll'}",
            empty_phone: "{l s='Please enter phone number.' mod='infinitescroll'}",
            minchar_phone: "{l s='Phone number cannot be less than {#} characters.' mod='infinitescroll'}",
            maxchar_phone: "{l s='Phone number cannot be greater than {#} characters.' mod='infinitescroll'}",
            empty_brand: "{l s='Please enter brand name.' mod='infinitescroll'}",
            maxchar_brand: "{l s='Brand name cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_brand: "{l s='Brand name cannot be less than {#} characters.' mod='infinitescroll'}",
            empty_shipment: "{l s='Please enter Shimpment.' mod='infinitescroll'}",
            maxchar_shipment: "{l s='Shipment cannot be greater than {#} characters.' mod='infinitescroll'}",
            minchar_shipment: "{l s='Shipment cannot be less than {#} characters.' mod='infinitescroll'}",
            invalid_ip: "{l s='Invalid IP format.' mod='infinitescroll'}",
            invalid_url: "{l s='Invalid URL format.' mod='infinitescroll'}",
            empty_url: "{l s='Please enter URL.' mod='infinitescroll'}",
            valid_amount: "{l s='Field should be numeric.' mod='infinitescroll'}",
            valid_decimal: "{l s='Field can have only upto two decimal values.' mod='infinitescroll'}",
            max_email: "{l s='Email cannot be greater than {#} characters.' mod='infinitescroll'}",
            specialchar_zip: "{l s='Zip should not have special characters.' mod='infinitescroll'}",
            specialchar_sku: "{l s='SKU should not have special characters.' mod='infinitescroll'}",
            max_url: "{l s='URL cannot be greater than {#} characters.' mod='infinitescroll'}",
            valid_percentage: "{l s='Percentage should be in number.' mod='infinitescroll'}",
            between_percentage: "{l s='Percentage should be between 0 and 100.' mod='infinitescroll'}",
            maxchar_size: "{l s='Size cannot be greater than {#} characters.' mod='infinitescroll'}",
            specialchar_size: "{l s='Size should not have special characters.' mod='infinitescroll'}",
            specialchar_upc: "{l s='UPC should not have special characters.' mod='infinitescroll'}",
            maxchar_upc: "{l s='UPC cannot be greater than {#} characters.' mod='infinitescroll'}",
            specialchar_ean: "{l s='EAN should not have special characters.' mod='infinitescroll'}",
            maxchar_ean: "{l s='EAN cannot be greater than {#} characters.' mod='infinitescroll'}",
            specialchar_bar: "{l s='Barcode should not have special characters.' mod='infinitescroll'}",
            maxchar_bar: "{l s='Barcode cannot be greater than {#} characters.' mod='infinitescroll'}",
            positive_amount: "{l s='Field should be positive.' mod='infinitescroll'}",
            maxchar_color: "{l s='Color could not be greater than {#} characters.' mod='infinitescroll'}",
            invalid_color: "{l s='Color is not valid.' mod='infinitescroll'}",
            specialchar: "{l s='Special characters are not allowed.' mod='infinitescroll'}",
            script: "{l s='Script tags are not allowed.' mod='infinitescroll'}",
            style: "{l s='Style tags are not allowed.' mod='infinitescroll'}",
            iframe: "{l s='Iframe tags are not allowed.' mod='infinitescroll'}",
            not_image: "{l s='Uploaded file is not an image.' mod='infinitescroll'}",
            image_size: "{l s='Uploaded file size must be less than {#}.' mod='infinitescroll'}",
            html_tags: "{l s='Field should not contain HTML tags.' mod='infinitescroll'}",
            number_pos: "{l s='You can enter only positive numbers.' mod='infinitescroll'}",
            invalid_separator:"{l s='Invalid comma ({#}) separated values.' mod='infinitescroll'}",
        });

    </script>
    {if $version == 6}
        <div class='row'>
            <div class="productTabs col-lg-2 col-md-3">
                <div class="list-group">
                    {$i=1}
                    {foreach $product_tabs key=numStep item=tab}
                        <a class="list-group-item {if $tab.selected|escape:'htmlall':'UTF-8'}active{/if}" id="link-{$tab.id|escape:'htmlall':'UTF-8'}" onclick="change_tab(this,{$i|escape:'htmlall':'UTF-8'});">{$tab.name|escape:'htmlall':'UTF-8'}
                            <label class="velsof_error_label"><img id='velsof_error_icon' class="velsof_error_tab_img"  style="display:none; position:absolute; right:10px; top:10px;" src="{$path|escape:'htmlall':'UTF-8'}views/img/admin/error_icon.gif"></label>
                        </a>
                        {$i=$i+1}
                    {/foreach}
                </div>
            </div>
            {$form} {*Variable contains html content, escape not required*}
            {$view} {*Variable contains html content, escape not required*}  
        </div>
    {else}
        <div class="productTabs col-lg-2 col-md-3 vss-pos" >
            <ul class="tab">
                {*todo href when nojs*}
                {$i=1}
                {foreach $product_tabs key=numStep item=tab}
                    <li class="tab-row">
                        <a class="tab-page {if $tab.selected|escape:'htmlall':'UTF-8'}selected{/if}" id="link-{$tab.id|escape:'htmlall':'UTF-8'}" onclick="change_tab(this,{$i|escape:'htmlall':'UTF-8'});">{$tab.name|escape:'htmlall':'UTF-8'}
                            <img id='velsof_error_icon' class="velsof_error_tab_img"  style="display:none; position:absolute; right:8px;" src="{$path|escape:'htmlall':'UTF-8'}views/img/admin/error_icon.gif">
                        </a>
                        {$i=$i+1}
                    </li>
                {/foreach}
            </ul>
        </div>
        {$form} {*Variable contains html content, escape not required*}  
        {$view} {*Variable contains html content, escape not required*}  
    {/if}
{/block}


{*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer tohttp://www.prestashop.com for more information.
* We offer the best and most useful modules PrestaShop and modifications for your online store.
*
* @category  PrestaShop Module
* @author    knowband.com <support@knowband.com>
* @copyright 2017 Knowband
* @license   see file: LICENSE.txt
*
* Description
*
* Admin tpl file
*}

