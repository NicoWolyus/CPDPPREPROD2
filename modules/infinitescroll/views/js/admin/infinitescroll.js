/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 * We offer the best and most useful modules PrestaShop and modifications for your online store.
 *
 * @author    knowband.com <support@knowband.com>
 * @copyright 2017 Knowband
 * @license   see file: LICENSE.txt
 * @category  PrestaShop Module
 *
 *
 * Description
 *
 * Updates quantity in the cart
 */

var h = 0;
$(document).ready(function()
{
    if (enable_sandbox_setting == 0) {
        h = 1;
    } else {
        h = 0;
    }
    
    $(".validation_infinite_scroll").click( function() {
        if(form_validation() == false){
            return false;
        }
        /*Knowband button validation start*/
        $('.validation_infinite_scroll').attr("disabled", "disabled");
        /*Knowband button validation end*/
        $("input[name='infinite_scroll[enable]']").closest('form').submit();
    });
    
    $('#configuration_form').addClass('col-lg-10 col-md-9');
    $('label').css('margin-top', '0px');
    $('.optn_general').closest('.form-group ').show();
    $("[name='infinite_scroll[enable]']").closest('.form-group ').show();
    $('.optn_advance').closest('.form-group ').hide();
    $("[name='infinite_scroll[display_top_link]']").closest('.form-group ').hide();
    $("[name='infinite_scroll[display_end_message]']").closest('.form-group ').hide();
    $("[name='infinite_scroll[display_loading_message]']").closest('.form-group ').hide();
    $("[name='infinite_scroll[enable_sandbox_setting]']").closest('.form-group ').hide();
    $("[name='infinite_scroll[load_more_link_frequency]']").closest('.input-group ').css('width', '20%');
    $('.optn_display').closest('.form-group ').parent().closest('.form-group ').hide();
    $('.optn_display').closest('.form-group ').hide();
    $('.optn_selector').closest('.form-group ').hide();
    
    $('.widget').insertAfter($("[name='infinite_scroll[background_color_top_link]']").closest('.form-group ').parent().closest('.form-group '));
    $('.widget').hide();
    $('.alert-info').hide();
    $("[name='infinite_scroll[display_end_message]']").on('click', function() {
        if ($(this).val() == 0) {
            $("[name='infinite_scroll[end_page_message]']").closest('.form-group ').slideUp(function() {

            });
        } else {
            $("[name='infinite_scroll[end_page_message]']").closest('.form-group ').slideDown(function() {

            });
        }
    });
    $("[name='infinite_scroll[display_loading_message]']").on('click', function() {
        if ($(this).val() == 0) {
            $("[name='infinite_scroll[loading_message]']").closest('.form-group ').slideUp(function() {

            });
        } else {
            $("[name='infinite_scroll[loading_message]']").closest('.form-group ').slideDown(function() {

            });
        }
    });
    $("[name='infinite_scroll[enable_sandbox_setting]']").on('click', function() {
        if ($(this).val() == 0) {
            h = 1;
            $("[name='infinite_scroll[add_ip]']").closest('.form-group ').slideUp(function() {

            });
        } else {
            h = 0;
            $("[name='infinite_scroll[add_ip]']").closest('.form-group ').slideDown(function() {

            });
        }
    });
    $("[name='infinite_scroll[add_ip]']").next('.input-group-addon').css('background', 'white');
    $("[name='infinite_scroll[add_ip]']").next('.input-group-addon').css('cursor', 'pointer');
    $("[name='infinite_scroll[add_ip]']").next('.input-group-addon').on('click', function() {
        
        var add_ips = $("input[name='infinite_scroll[add_ip]']").val().trim().split(",");
        
        var return_flag = 0;
        add_ips.forEach(function(element, index){
            element = element.trim();
            if(return_flag == 0) {
                if(element == my_ip_address) {
                    $('.app_id_error').remove();
                                       
                    $("input[name='infinite_scroll[add_ip]']").parent().after($('<p class="app_id_error ip_already_exist"></p>'));
                    $('.app_id_error').html(ip_already_exist);

                    return_flag = 1;
                }
            }
        });
        
        if(return_flag == 0){
            
            var val = $("[name='infinite_scroll[add_ip]']").val().trim();
            if(val.endsWith(",") == true){
                val = val + my_ip_address;
            }else if (val != '') {
                val = val + ',' + my_ip_address;
            }else {
                $('.app_id_error').remove();
                $("input[name='infinite_scroll[add_ip]']").removeClass('error_field');
                val = my_ip_address;
            }
            $("[name='infinite_scroll[add_ip]']").val(val);
        }
        
        
    });
});

function change_tab(a, b)
{
    if (b == 1) {
        $("[id^='fieldset'] h3").html(general_settings);
        $(".panel-heading").html(general_settings);
        $('.optn_general').closest('.form-group ').show();
        $("[name='infinite_scroll[enable]']").closest('.form-group ').show();
        $('.optn_advance').closest('.form-group ').hide();
        $('.optn_display').closest('.form-group ').parent().closest('.form-group ').hide();
        $('.optn_display').closest('.form-group ').hide();
        $('.optn_selector').closest('.form-group ').hide();
        $("[name='infinite_scroll[display_top_link]']").closest('.form-group ').hide();
        $("[name^='infinite_scroll[display_end_message]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[display_loading_message]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[enable_sandbox_setting]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[custom_css]']").closest('.form-group ').show();
        $("[name='infinite_scroll[custom_js]']").closest('.form-group ').show();
                
        $('.widget').hide();
        $('.alert-info').hide();
    } else if (b == 2) {
        $("[id^='fieldset'] h3").html(advance_settings);
        $(".panel-heading").html(advance_settings);
        $('.optn_general').closest('.form-group ').hide();
        $("[name='infinite_scroll[enable]']").closest('.form-group ').hide();
        $('.optn_advance').closest('.form-group ').show();
        $('.optn_display').closest('.form-group ').parent().closest('.form-group ').hide();
        $('.optn_display').closest('.form-group ').hide();
        $('.optn_selector').closest('.form-group ').hide();
        $("[name='infinite_scroll[display_top_link]']").closest('.form-group ').show();
        $("[name='infinite_scroll[display_end_message]']").closest('.form-group ').show();
        $("[name='infinite_scroll[display_loading_message]']").closest('.form-group ').show();
        $("[name='infinite_scroll[enable_sandbox_setting]']").closest('.form-group ').show();
        $("[name='infinite_scroll[custom_css]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[custom_js]']").closest('.form-group ').hide();
        
        if($("input[name='infinite_scroll[display_end_message]']:checked").val() == 1) {
            $("[name='infinite_scroll[end_page_message]']").closest('.form-group ').show();
        } else {
            $("[name='infinite_scroll[end_page_message]']").closest('.form-group ').hide();
        }
        
        if($("input[name='infinite_scroll[display_loading_message]']:checked").val() == 1) {
            $("[name='infinite_scroll[loading_message]']").closest('.form-group ').show();
        } else {
            $("[name='infinite_scroll[loading_message]']").closest('.form-group ').hide();
        }
        
        if($("select[name='infinite_scroll[scroll_type]']").val() == 1) {
            $("[name='infinite_scroll[load_more_link_page]']").closest('.form-group ').show();
            $("[name='infinite_scroll[load_more_link_frequency]']").closest('.form-group ').show();
        } else {
            $("[name='infinite_scroll[load_more_link_page]']").closest('.form-group ').hide();
            $("[name='infinite_scroll[load_more_link_frequency]']").closest('.form-group ').hide();
        }
        
        if($("input[name='infinite_scroll[enable_sandbox_setting]']:checked").val() == 1) {
            $("[name='infinite_scroll[add_ip]']").closest('.form-group ').show();
        } else {
            $("[name='infinite_scroll[add_ip]']").closest('.form-group ').hide();
        }

        $('.widget').hide();
        $('.alert-info').hide();
    } else if (b == 3) {
        $("[id^='fieldset'] h3").html(display_settings);
        $(".panel-heading").html(display_settings);
        $('.optn_general').closest('.form-group ').hide();
        $("[name='infinite_scroll[enable]']").closest('.form-group ').hide();
        $('.optn_advance').closest('.form-group ').hide();
        $('.optn_display').closest('.form-group ').parent().closest('.form-group ').show();
        $('.optn_display').closest('.form-group ').show();
        $('.optn_selector').closest('.form-group ').hide();
        $("[name='infinite_scroll[display_top_link]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[display_end_message]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[display_loading_message]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[enable_sandbox_setting]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[custom_css]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[custom_js]']").closest('.form-group ').hide();
        $('.widget').show();
        $('.alert-info').hide();
    } else if (b == 4) {
        $("[id^='fieldset'] h3").html(selector_settings);
        $(".panel-heading").html(selector_settings);
        $('.optn_general').closest('.form-group ').hide();
        $("[name='infinite_scroll[enable]']").closest('.form-group ').hide();
        $('.optn_advance').closest('.form-group ').hide();
        $('.optn_display').closest('.form-group ').parent().closest('.form-group ').hide();
        $('.optn_display').closest('.form-group ').hide();
        $('.optn_selector').closest('.form-group ').show();
        $("[name='infinite_scroll[display_top_link]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[display_end_message]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[display_loading_message]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[enable_sandbox_setting]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[custom_css]']").closest('.form-group ').hide();
        $("[name='infinite_scroll[custom_js]']").closest('.form-group ').hide();
        $('.widget').hide();
        $('.alert-info').show();
    }
    $('.list-group-item').attr('class', 'list-group-item');
    $(a).attr('class', 'list-group-item active');
}

function getscrolltype(a) {
    if ($(a).val() == 0) {
        $("[name='infinite_scroll[load_more_link_page]']").closest('.form-group ').slideUp();
        $("[name='infinite_scroll[load_more_link_frequency]']").closest('.form-group ').slideUp();
    } else {
        $("[name='infinite_scroll[load_more_link_page]']").closest('.form-group ').slideDown();
        $("[name='infinite_scroll[load_more_link_frequency]']").closest('.form-group ').slideDown();
    }
}

function form_validation(){
    
    $('.vel_error_msg').remove();
    $('.error_field').removeClass('error_field');
    $('.velsof_error_label').hide();
    $('.ip_already_exist').hide();
    
    var general_setting_tab = 0;
    var advance_setting_tab = 0;
    var display_setting_tab = 0;
    var selector_setting_tab = 0;
    
    var error = false;
    var errorMessage = '';
    
    /*Knowband validation start*/
    var custom_css_tag = velovalidation.checkHtmlTags($("textarea[name='infinite_scroll[custom_css]']"));
    if (custom_css_tag != true){
        error = true;
        $("textarea[name='infinite_scroll[custom_css]']").addClass('error_field');
        $("textarea[name='infinite_scroll[custom_css]']").after($('<p class="custom_css_tag vel_error_msg"></p>'));
        $('.custom_css_tag').html(custom_css_tag);
        general_setting_tab = 1;
    } else if($("textarea[name='infinite_scroll[custom_css]']").val().trim().length > 10000) {
        error = true;
        $("textarea[name='infinite_scroll[custom_css]']").addClass('error_field');
        $("textarea[name='infinite_scroll[custom_css]']").after($('<p class="custom_css_length vel_error_msg"></p>'));
        $('.custom_css_length').html(custom_css_length);
        general_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var custom_js_tag = velovalidation.checkTags($("textarea[name='infinite_scroll[custom_js]']"));
    if (custom_js_tag != true){
        error = true;
        $("textarea[name='infinite_scroll[custom_js]']").addClass('error_field');
        $("textarea[name='infinite_scroll[custom_js]']").after($('<p class="custom_js_tag vel_error_msg"></p>'));
        $('.custom_js_tag').html(custom_js_tag);
        general_setting_tab = 1;
    } else if($("textarea[name='infinite_scroll[custom_js]']").val().trim().length > 10000) {
        error = true;
        $("textarea[name='infinite_scroll[custom_js]']").addClass('error_field');
        $("textarea[name='infinite_scroll[custom_js]']").after($('<p class="custom_js_length vel_error_msg"></p>'));
        $('.custom_js_length').html(custom_js_length);
        general_setting_tab = 1;
    }
    /*Knowband validation end*/
        
    /*Knowband validation start*/
    if($("select[name='infinite_scroll[scroll_type]']").val() == 1) {
        var load_more_frequency_tag = velovalidation.checkMandatoryOnly($("input[name='infinite_scroll[load_more_link_frequency]']"));
        var load_more_frequency_numeric = velovalidation.isNumeric($("input[name='infinite_scroll[load_more_link_frequency]']"), false);
        if (load_more_frequency_tag != true){
            error = true;
            $("input[name='infinite_scroll[load_more_link_frequency]']").addClass('error_field');
            $("input[name='infinite_scroll[load_more_link_frequency]']").parent().after($('<p class="load_more_frequency_tag vel_error_msg"></p>'));
            $('.load_more_frequency_tag').html(load_more_frequency_tag);
            advance_setting_tab = 1;
        } else if(load_more_frequency_numeric != true) {
            error = true;
            $("input[name='infinite_scroll[load_more_link_frequency]']").addClass('error_field');
            $("input[name='infinite_scroll[load_more_link_frequency]']").parent().after($('<p class="load_more_frequency_numeric vel_error_msg"></p>'));
            $('.load_more_frequency_numeric').html(load_more_frequency_numeric);
            advance_setting_tab = 1;
        } else if($("input[name='infinite_scroll[load_more_link_frequency]']").val().trim() > 10000 || $("input[name='infinite_scroll[load_more_link_frequency]']").val().trim() <= 0){
            error = true;
            $("input[name='infinite_scroll[load_more_link_frequency]']").addClass('error_field');
            $("input[name='infinite_scroll[load_more_link_frequency]']").parent().after($('<p class="load_more_frequency_tag vel_error_msg"></p>'));
            $('.load_more_frequency_tag').html(greater_than_zero_msg);
            advance_setting_tab = 1;
        }
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    if($("input[name='infinite_scroll[enable_sandbox_setting]']:checked").val() == 1) {
        var add_ip_mand = velovalidation.checkMandatoryOnly($("input[name='infinite_scroll[add_ip]']"));
        if (add_ip_mand == true) {
            
            var testip = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            var add_ips = $("input[name='infinite_scroll[add_ip]']").val().trim().replace(/(^,)|(,$)/g, "").split(",");
            
            
            if (add_ips.length < 1000) {
                if(add_ips.length != $.unique(add_ips).length) {
                    error = true;
                    $("input[name='infinite_scroll[add_ip]']").addClass('error_field');
                    $("input[name='infinite_scroll[add_ip]']").parent().after($('<p class="app_ip_valid app_id_error vel_error_msg"></p>'));
                    $('.app_ip_valid').html(duplicate_ips);
                    advance_setting_tab = 1;
                }else{
                    var return_flag = 0;
                    add_ips.forEach(function(element, index){
                        element = element.trim();
                        if (element != '') {
                            if(!element.match(testip)){
                                if(return_flag == 0) {
                                    error = true;
                                    $("input[name='infinite_scroll[add_ip]']").addClass('error_field');
                                    $("input[name='infinite_scroll[add_ip]']").parent().after($('<p class="app_ip_valid app_id_error vel_error_msg"></p>'));
                                    $('.app_ip_valid').html(invalid_ip_msg);
                                    advance_setting_tab = 1;
                                    return_flag = 1;
                                }
                            }
                        }
                    });
                }
            } else {
                error = true;
                $("input[name='infinite_scroll[add_ip]']").addClass('error_field');
                $("input[name='infinite_scroll[add_ip]']").parent().after($('<p class="add_ip_length app_id_error vel_error_msg"></p>'));
                $('.add_ip_length').html(add_ip_length);
                advance_setting_tab = 1;
            }
        } else {
            error = true;
            $("input[name='infinite_scroll[add_ip]']").addClass('error_field');
            $("input[name='infinite_scroll[add_ip]']").parent().after($('<p class="add_ip_mand app_id_error vel_error_msg"></p>'));
            $('.add_ip_mand').html(add_ip_mand);
            advance_setting_tab = 1;
        }
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var background_color_mand = velovalidation.checkMandatoryOnly($("input[name='infinite_scroll[background_color]']"));
    var background_color_check = velovalidation.isColor($("input[name='infinite_scroll[background_color]']"));
    if (background_color_mand != true){
        error = true;
        $("input[name='infinite_scroll[background_color]']").addClass('error_field');
        $("input[name='infinite_scroll[background_color]']").closest('.form-group').after($('<p class="background_color_mand vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.background_color_mand').html(background_color_mand);
        display_setting_tab = 1;
    } else if (background_color_check != true){
        error = true;
        $("input[name='infinite_scroll[background_color]']").addClass('error_field');
        $("input[name='infinite_scroll[background_color]']").closest('.form-group').after($('<p class="background_color_check vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.background_color_check').html(background_color_check);
        display_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var text_color_mand = velovalidation.checkMandatoryOnly($("input[name='infinite_scroll[text_color]']"));
    var text_color_check = velovalidation.isColor($("input[name='infinite_scroll[text_color]']"));
    if (text_color_mand != true){
        error = true;
        $("input[name='infinite_scroll[text_color]']").addClass('error_field');
        $("input[name='infinite_scroll[text_color]']").closest('.form-group').after($('<p class="text_color_mand vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.text_color_mand').html(text_color_mand);
        display_setting_tab = 1;
    }else if (text_color_check != true){
        error = true;
        $("input[name='infinite_scroll[text_color]']").addClass('error_field');
        $("input[name='infinite_scroll[text_color]']").closest('.form-group').after($('<p class="text_color_check vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.text_color_check').html(text_color_check);
        display_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var border_color_mand = velovalidation.checkMandatoryOnly($("input[name='infinite_scroll[border_color]']"));
    var border_color_check = velovalidation.isColor($("input[name='infinite_scroll[border_color]']"));
    if (border_color_mand != true){
        error = true;
        $("input[name='infinite_scroll[border_color]']").addClass('error_field');
        $("input[name='infinite_scroll[border_color]']").closest('.form-group').after($('<p class="border_color_mand vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.border_color_mand').html(border_color_mand);
        display_setting_tab = 1;
    }else if (border_color_check != true){
        error = true;
        $("input[name='infinite_scroll[border_color]']").addClass('error_field');
        $("input[name='infinite_scroll[border_color]']").closest('.form-group').after($('<p class="border_color_check vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.border_color_check').html(border_color_check);
        display_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var background_color_top_mand = velovalidation.checkMandatoryOnly($("input[name='infinite_scroll[background_color_top_link]']"));
    var background_color_top_check = velovalidation.isColor($("input[name='infinite_scroll[background_color_top_link]']"));
    if (background_color_top_mand != true){
        error = true;
        $("input[name='infinite_scroll[background_color_top_link]']").addClass('error_field');
        $("input[name='infinite_scroll[background_color_top_link]']").closest('.form-group').after($('<p class="background_color_top_mand vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.background_color_top_mand').html(background_color_top_mand);
        display_setting_tab = 1;
    }
    if (background_color_top_check != true){
        error = true;
        $("input[name='infinite_scroll[background_color_top_link]']").addClass('error_field');
        $("input[name='infinite_scroll[background_color_top_link]']").closest('.form-group').after($('<p class="background_color_top_check vel_error_msg" style="margin-top: -10px;"></p>'));
        $('.background_color_top_check').html(background_color_top_check);
        display_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var selector_item_tag = velovalidation.checkTags($("input[name='infinite_scroll[selector_item]']"));
    if (selector_item_tag != true){
        error = true;
        $("input[name='infinite_scroll[selector_item]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_item]']").after($('<p class="selector_item_tag vel_error_msg"></p>'));
        $('.selector_item_tag').html(selector_item_tag);
        selector_setting_tab = 1;
    } else if ($("input[name='infinite_scroll[selector_item]']").val().trim().length > 1000) {
        error = true;
        $("input[name='infinite_scroll[selector_item]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_item]']").after($('<p class="selector_item_tag vel_error_msg"></p>'));
        $('.selector_item_tag').html(selector_length);
        selector_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var selector_container_tag = velovalidation.checkTags($("input[name='infinite_scroll[selector_container]']"));
    if (selector_container_tag != true){
        error = true;
        $("input[name='infinite_scroll[selector_container]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_container]']").after($('<p class="selector_container_tag vel_error_msg"></p>'));
        $('.selector_container_tag').html(selector_container_tag);
        selector_setting_tab = 1;
    } else if ($("input[name='infinite_scroll[selector_container]']").val().trim().length > 1000) {
        error = true;
        $("input[name='infinite_scroll[selector_container]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_container]']").after($('<p class="selector_container_tag vel_error_msg"></p>'));
        $('.selector_container_tag').html(selector_length);
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var selector_next_tag = velovalidation.checkTags($("input[name='infinite_scroll[selector_next]']"));
    if (selector_next_tag != true){
        error = true;
        $("input[name='infinite_scroll[selector_next]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_next]']").after($('<p class="selector_next_tag vel_error_msg"></p>'));
        $('.selector_next_tag').html(selector_next_tag);
        selector_setting_tab = 1;
    } else if ($("input[name='infinite_scroll[selector_next]']").val().trim().length > 1000) {
        error = true;
        $("input[name='infinite_scroll[selector_next]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_next]']").after($('<p class="selector_next_tag vel_error_msg"></p>'));
        $('.selector_next_tag').html(selector_length);
        selector_setting_tab = 1;
    }
    /*Knowband validation end*/
    
    /*Knowband validation start*/
    var selector_pagination_tag = velovalidation.checkTags($("input[name='infinite_scroll[selector_pagination]']"));
    if (selector_pagination_tag != true){
        error = true;
        $("input[name='infinite_scroll[selector_pagination]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_pagination]']").after($('<p class="selector_pagination_tag vel_error_msg"></p>'));
        $('.selector_pagination_tag').html(selector_pagination_tag);
        selector_setting_tab = 1;
    } else if ($("input[name='infinite_scroll[selector_next]']").val().trim().length > 1000) {
        error = true;
        $("input[name='infinite_scroll[selector_pagination]']").addClass('error_field');
        $("input[name='infinite_scroll[selector_pagination]']").after($('<p class="selector_pagination_tag vel_error_msg"></p>'));
        $('.selector_pagination_tag').html(selector_length);
    }
    /*Knowband validation end*/
    
    
    if(error == true){
        
        if(general_setting_tab == 1){
            $('#link-General_Settings').children('.velsof_error_label').show();
            $('#link-General_Settings').children().children('#velsof_error_icon').css('display','inline');
        }
        if(advance_setting_tab == 1){
            $('#link-Advance_Settings').children('.velsof_error_label').show();
            $('#link-Advance_Settings').children().children('#velsof_error_icon').css('display','inline');
        }
        if(display_setting_tab == 1){
            $('#link-Display_Settings').children('.velsof_error_label').show();
            $('#link-Display_Settings').children().children('#velsof_error_icon').css('display','inline');
        }
        if(selector_setting_tab == 1){
            $('#link-Selector_Settings').children('.velsof_error_label').show();
            $('#link-Selector_Settings').children().children('#velsof_error_icon').css('display','inline');
        }
        return false;
    }
}