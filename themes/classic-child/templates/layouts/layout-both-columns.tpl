{**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<!doctype html>
<html lang="{$language.iso_code}">

<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
</head>

<body id="{$page.page_name}" class="{$page.body_classes|classnames}">

{block name='hook_after_body_opening_tag'}
    {hook h='displayAfterBodyOpeningTag'}
{/block}

<main>
    {block name='product_activation'}
        {include file='catalog/_partials/product-activation.tpl'}
    {/block}

    <header id="header">
        {block name='header'}
            {include file='_partials/header.tpl'}
        {/block}
    </header>


    {block name='notifications'}
        {include file='_partials/notifications.tpl'}
    {/block}


    <section id="wrapper" {if $page.page_name == 'index'}class="index-reup"{/if}>
        {if $page.page_name == 'index'}
            <div class="swiper-container" id="menuslide">
                <div class="menuindex-mobile swiper-wrapper">
                    <div class="minindex swiper-slide"><img src="{$urls.img_url}minmarseille.png">
                        <div class="mintext">{l s='Savon de Marseille' d='Shop.Theme.Special'}</div>
                    </div>
                    <div class="minindex swiper-slide"><img src="{$urls.img_url}minsoinvisage.png">
                        <div class="mintext"> {l s='Soin du Visage' d='Shop.Theme.Special'}</div>
                    </div>
                    <div class="minindex swiper-slide"><img src="{$urls.img_url}minsoinducorps.png">
                        <div class="mintext"> {l s='Soin du Corps' d='Shop.Theme.Special'}</div>
                    </div>
                    <div class="minindex swiper-slide"><img src="{$urls.img_url}minsoinmains.png">
                        <div class="mintext"> {l s='Soin des Mains' d='Shop.Theme.Special'}</div>
                    </div>
                    <div class="minindex swiper-slide"><img src="{$urls.img_url}minmaison.png">
                        <div class="mintext"> {l s='Maison' d='Shop.Theme.Special'}</div>
                    </div>  <!-- If we need pagination -->


                    <!-- If we need navigation buttons -->
                </div>

            </div>
            <script>
                var mySwiper = new Swiper('.swiper-container', {
                    // Optional parameters
                    direction: 'horizontal',
loop:true,
                    centeredSlides: true,
                    slidesPerView: 5,
                    spaceBetween: 5,
                    centeredSlides: true,



                })
            </script>
        {/if}


        {hook h="displayWrapperTop"}
        <div class="container" id="cat-reup">
            {block name='breadcrumb'}
                {include file='_partials/breadcrumb.tpl'}
            {/block}

            {*   {block name="left_column"}
                 <div id="left-column" class="col-xs-12 col-sm-4 col-md-3">
                   {if $page.page_name == 'product'}
                     {hook h='displayLeftColumnProduct'}
                   {else}
                     {hook h="displayLeftColumn"}
                   {/if}
                 </div>
               {/block}
     *}
            {block name="content_wrapper"}
                <div id="content-wrapper" class="left-column right-column col-sm-4 col-md-6">

                    {hook h="displayContentWrapperTop"}
                    {block name="content"}
                        <p>Hello world! This is HTML5 Boilerplate.</p>
                    {/block}
                    {hook h="displayContentWrapperBottom"}
                </div>
            {/block}

            {block name="right_column"}
                <div id="right-column" class="col-xs-12 col-sm-4 col-md-3">
                    {if $page.page_name == 'product'}
                        {hook h='displayRightColumnProduct'}
                    {else}
                        {hook h="displayRightColumn"}
                    {/if}
                </div>
            {/block}
        </div>
        {hook h="displayWrapperBottom"}
    </section>

    <footer id="footer">
        {block name="footer"}
            {include file="_partials/footer.tpl"}
        {/block}
    </footer>

</main>

{block name='javascript_bottom'}
    {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
{/block}

{block name='hook_before_body_closing_tag'}
    {hook h='displayBeforeBodyClosingTag'}
{/block}
</body>

</html>
