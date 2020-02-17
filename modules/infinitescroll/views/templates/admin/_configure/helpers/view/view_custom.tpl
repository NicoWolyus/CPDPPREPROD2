<style>
    .message-box {
        border: 1px solid;
        text-transform: uppercase;
        font-size: 16px;
        font-family: Raleway, 'Helvetica Neue', Verdana, Arial, sans-serif;
        padding: 5px;
    }
    .back-to-top{
        width: 50px;
        height: 50px;
        text-indent: -9999px;
        z-index: 999;
        right: 20px;
        bottom: 20px;
        border-radius: 30px;
    }
</style>
{if $version==5}
    <div id='vss_add_ip'>
        <button type='button'  name='vss_add_ip' value='add_ip' onclick='addip()'>{l s='Add Ip' mod='infinitescroll'}</button>
    </div>
{/if} 
<div class="widget" id="vss_layout_preview" style="margin: 15px 8px 5px 0px;">
    <div class="widget-head" >
        <h4 class="heading" style='margin: 0px; height: 0px;'>{l s='Message And Top Link Preview' mod='infinitescroll'}</h4>
    </div>
    <div class="widget-body" style="padding: 10px;">
        <div class="ias-noneleft message-box" style="text-align: center;background-color:{$background_color|escape:'htmlall':'UTF-8'};color:{$text_color|escape:'htmlall':'UTF-8'};border-color:{$border_color|escape:'htmlall':'UTF-8'}">{l s='That\'s All Folks!!' mod='infinitescroll'}</div>
        <div class="back-to-top" style="margin: 0px auto;margin-top: 2%;background: url({$img_path|escape:'htmlall':'UTF-8'}top-link.png) 50% 43% no-repeat {$background_color_top_link|escape:'htmlall':'UTF-8'}">></div>
    </div>
</div>
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