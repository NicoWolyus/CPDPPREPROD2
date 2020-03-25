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
<div class="clearfix"></div>
<section class="featured-products">
    <h2 class="h2 products-section-title text-uppercase">
        {l s='Notre sélection du moment' d='Shop.Theme.Catalog'}
    </h2>

    <div class="products swiper-container" id="mobilefeat">
        <div class="swiper-wrapper" id="homefeatu">
            {foreach from=$products item="product"}
                {include file="catalog/_partials/miniatures/product2.tpl" product=$product}
            {/foreach}


        </div>
        <!-- If we need pagination -->
        <div class="swiper-pagination" id="homepagi"></div>

        <!-- If we need navigation buttons -->
        <div class="swiper-button-prev" id="homeleft"></div>
        <div class="swiper-button-next" id="homeright"></div>


    </div>
    <div class="products" id="desktopfeat">

            {foreach from=$products item="product"}
                {include file="catalog/_partials/miniatures/product.tpl" product=$product}
            {/foreach}


          </div>



    <script>
        var mySwiper = new Swiper('.swiper-container', {
            // Optional parameters
            direction: 'horizontal',
            loop: true,

            // If we need pagination
            pagination: {
                el: '.swiper-pagination',
            },

            // Navigation arrows
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },


        })
    </script>

</section>
<div class="clearfix"></div>

{*<div class="col-md-12 oli-block">

    <div class="col-md-6 bg-green pl-3">
        <span class="intro-oli">Chez la Compagnie de Provence</span>
        <div class="main-oli">Nous avons à <br/>coeur de<br/> privilégier les <br/> producteurs locaux</div>
        <span class="best-compo">Découvrir nos supers ingrédients</span>
        <span class="up-brown"></span>


    </div>
    <div class="col-md-6 pic-oli">
        <img src="{$urls.img_url}jeromeoliver.png" class="oli-pic">
        <span class="legend-oli">Chez Jérôme, producteur d'olivier <strong>.</strong> 06130 Grasse</span>
    </div>


</div>*}





