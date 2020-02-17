<style>
    {if isset($custom_css)}
        {$custom_css nofilter} {*Variable contains css content, escape not required*}
    {/if}
</style>    

<script>
    var display_end_message = "{$display_end_message}";
    var end_page_message = "{l s='That\'s All Folks!!' mod='infinitescroll'}";
    var display_loading_message = "{$display_loading_message}";
    var loading_message = "{l s='Loading...' mod='infinitescroll'}";
    var scroll_type = "{$scroll_type}";
    var load_more_link_frequency = "{$load_more_link_frequency}";
    var load_more_link_page = "{l s='Load More' mod='infinitescroll'}";
    var background_color_top_link = "{$background_color_top_link nofilter}"; {*Variable contains URL, escape not required*}
    var image_url = "{$img_path nofilter}"; {*Variable contains URL, escape not required*}
    var background_color = "{$background_color_message_box}";
    var text_color = "{$text_color_message_box}";
    var border_color = "{$border_color_message_box}";
    var selector_item = "{$selector_item}";
    var selector_container = "{$selector_container}";
    var selector_next = "{$selector_next}";
    var selector_pagination = "{$selector_pagination}";
    var version = "{$version}";
    var ismobile = "{$ismobile}";
    
    /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
    {if isset($custom_js)}
        document.addEventListener("DOMContentLoaded", function(event) {
            {$custom_js nofilter} {*Variable contains js content, escape not required*}
        });
    {/if}
    

</script>
{if $display_top_link == 1}
    <div><a href="javascript:void(0)" class="back-to-top" style="background: url({$img_path nofilter}top-link.png) 50% 43% no-repeat {$background_color_top_link nofilter}"></a></div> {*Variable contains URL, escape not required*}
{/if}
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