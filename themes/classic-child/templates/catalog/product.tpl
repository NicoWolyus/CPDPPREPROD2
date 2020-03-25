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
{extends file=$layout}

{block name='head_seo' prepend}
    <link rel="canonical" href="{$product.canonical_url}">
{/block}

{block name='head' append}
    <meta property="og:type" content="product">
    <meta property="og:url" content="{$urls.current_url}">
    <meta property="og:title" content="{$page.meta.title}">
    <meta property="og:site_name" content="{$shop.name}">
    <meta property="og:description" content="{$page.meta.description}">
    <meta property="og:image" content="{$product.cover.large.url}">
    {if $product.show_price}
        <meta property="product:pretax_price:amount" content="{$product.price_tax_exc}">
        <meta property="product:pretax_price:currency" content="{$currency.iso_code}">
        <meta property="product:price:amount" content="{$product.price_amount}">
        <meta property="product:price:currency" content="{$currency.iso_code}">
    {/if}
    {if isset($product.weight) && ($product.weight != 0)}
        <meta property="product:weight:value" content="{$product.weight}">
        <meta property="product:weight:units" content="{$product.weight_unit}">
    {/if}
{/block}

{block name='content'}
    <section id="main" itemscope itemtype="https://schema.org/Product">

        {block name='product_images'}
            <div class="thumlistproduct js-qv-mask mask">
                <ul class="product-images js-qv-product-images">
                    {foreach from=$product.images item=image}
                        <li class="thumb-container">
                            <img
                                    class="thumb js-thumb {if $image.id_image == $product.cover.id_image} selected {/if}"
                                    data-image-medium-src="{$image.bySize.medium_default.url}"
                                    data-image-large-src="{$image.bySize.large_default.url}"
                                    src="{$image.bySize.home_default.url}"
                                    alt="{$image.legend}"
                                    title="{$image.legend}"
                                    width="100"
                                    itemprop="image"
                            >
                        </li>
                    {/foreach}
                </ul>
            </div>
        {/block}
        <meta itemprop="url" content="{$product.url}">

        <div class="row">

            <div class="col-md-6 col-xs-12">
                {block name='page_content_container'}
                    <section class="page-content" id="content"> {if $product.dwf_originredient}
                            <div class="box-origine-list">
                            <div class="origin-number">{$product.dwf_originredient}%</div>
                            <div class="origin-text">{l s='d\'ingrédients' d='Shop.Theme.Special'}</div>
                            <div class="origin-text origin-text2">{l s='d\'origine' d='Shop.Theme.Special'}</div>
                            <div class="origin-text origin-text3">{l s='naturelle' d='Shop.Theme.Special'}</div>
                            </div>{/if}
                        {if $product.dwf_nouveau}
                            <div class="new">Nouveau</div>
                        {/if}
                        {block name='page_content'}
                            <!-- @todo: use include file='catalog/_partials/product-flags.tpl'} -->
                            {block name='product_flags'}
                                <ul class="product-flags">
                                    {foreach from=$product.flags item=flag}
                                        <li class="product-flag {$flag.type}">{$flag.label}</li>
                                    {/foreach}
                                </ul>
                            {/block}

                            {block name='product_cover_thumbnails'}
                                {include file='catalog/_partials/product-cover-thumbnails.tpl'}
                            {/block}
                            <div class="scroll-box-arrows">
                                <i class="material-icons left"><img src="{$urls.img_url}scrollthumb.png"></i>
                                <i class="material-icons right"><img src="{$urls.img_url}scrollthumb.png"></i>
                            </div>
                        {/block}
                        {if $product.dwf_miniphrase}
                            <div class="miniphrase"> {$product.dwf_miniphrase nofilter}</div>{/if}
                    </section>
                {/block}
            </div>
            <div class="col-md-6 col-xs-12" id="blocksoumobile">
                {block name='page_header_container'}
                    {block name='page_header'}
                        <h1 class="h1"
                            itemprop="name">{block name='page_title'}{$product.name}{/block}</h1>{if $product.dwf_bestsell}
                        <span class="bestselproduct">Best-seller</span>
                    {/if}
                        {if $product.features }
                            {foreach from=$product.features item=feature name=features}
                                {if $feature.name == "Senteur"}
                                    <span class="defaultdecli">{$feature.value|escape:'html':'UTF-8'}<br/></span>
                                {/if}
                            {/foreach}
                        {/if}
                    {/block}
                {/block}
                {block name='product_prices'}
                    {include file='catalog/_partials/product-prices.tpl'}
                {/block}

                <div class="product-information">
                    {*   {block name='product_description_short'}
                         <div id="product-description-short-{$product.id}" itemprop="description">{$product.description_short nofilter}</div>
                       {/block}*}

                    {if $product.is_customizable && count($product.customizations.fields)}
                        {block name='product_customization'}
                            {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}
                        {/block}
                    {/if}

                    <div class="product-actions">
                        {block name='product_buy'}
                            <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">
                                <input type="hidden" name="token" value="{$static_token}">
                                <input type="hidden" name="id_product" value="{$product.id}"
                                       id="product_page_product_id">
                                <input type="hidden" name="id_customization" value="{$product.id_customization}"
                                       id="product_customization_id">

                                {block name='product_variants'}
                                    {include file='catalog/_partials/product-variants.tpl'}
                                {/block}

                                {block name='product_pack'}
                                    {if $packItems}
                                        <section class="product-pack">
                                            <p class="h4">{l s='This pack contains' d='Shop.Theme.Catalog'}</p>
                                            {foreach from=$packItems item="product_pack"}
                                                {block name='product_miniature'}
                                                    {include file='catalog/_partials/miniatures/pack-product.tpl' product=$product_pack}
                                                {/block}
                                            {/foreach}
                                        </section>
                                    {/if}
                                {/block}

                                {block name='product_discounts'}
                                    {include file='catalog/_partials/product-discounts.tpl'}
                                {/block}

                                {block name='product_add_to_cart'}
                                    {include file='catalog/_partials/product-add-to-cart.tpl'}

                                {/block}



                                {* Input to refresh product HTML removed, block kept for compatibility with themes *}
                                {block name='product_refresh'}{/block}
                            </form>
                        {/block}

                    </div>

                    {block name='hook_display_reassurance'}
                        {hook h='displayReassurance'}
                    {/block}
                </div>
            </div>
        </div>
    </section>
    </div></div></section>
    <div class="row sousprod">
        <div class="col-md-12">
<div class="container descriall">
            {block name='product_tabs'}
                <div class="des">
                    {if $product.dwf_biod}<img src="{$product.dwf_biod}" class="top-description-img" />{/if}
                    {* <ul class="nav nav-tabs" role="tablist">*}
                    {if $product.description}

                          <div class="title-descr"> {l s='En quelques mots' d='Shop.Theme.Catalog'}</div>

                        {block name='product_description'}
                                    <div class="product-description">{$product.description nofilter}</div>
                                {/block}
                            </div>
{/if}
                        {*    {block name='product_details'}
                                {include file='catalog/_partials/product-details.tpl'}
                            {/block}*}




                </div>          </div>
                </div>
            {/block}



    <div class="sousli">
        <div class="container" id="suballprod">

            <div class="blocks-subprod">

                {if $product.dwf_aloe|| ($product.dwf_absokarite) || $product.dwf_beurrekarite || $product.dwf_beurrekaribio || $product.dwf_karitefair || $product.dwf_bleuetbio || $product.dwf_cireabeille || $product.dwf_citronvita || $product.dwf_eauxfruit || $product.dwf_fleurderosier || $product.dwf_nenupharbio || $product.dwf_extraithym || $product.dwf_figuemonteux || $product.dwf_figuesodives ||  $product.dwf_glycenat || $product.dwf_grenadier || $product.dwf_huileamande ||$product.dwf_huileamandevalen || $product.dwf_camediva || $product.dwf_huilecoco || $product.dwf_grignon || $product.dwf_noyauabri || $product.dwf_pepinraisin || $product.dwf_huilericin || $product.dwf_huilesesame || $product.dwf_huilesonriz || $product.dwf_huiletournesol || $product.dwf_huileodivve ||$product.dwf_huileodivvebio || $product.dwf_huilevirant || $product.dwf_huilevirannobio || $product.dwf_calophy || $product.dwf_huimacada || $product.dwf_huilesesam || $product.dwf_mielhaute || $product.dwf_abriconoyau ||$product.dwf_odivbassin ||$product.dwf_popudraloe || $product.dwf_vitae}
                    <div class="block-compo">
                        <div class="title-compo1">  {l s='Les' d='Shop.Theme.Special'}</div>
                        <div class="title-compo2">{l s='Ingrédients stars' d='Shop.Theme.Special'}</div>

                        {if $product.dwf_compoprod}
                            <button id="btnPopup"
                                    class="btnPopup link-compo">{l s='Voir la composition du produit' d='Shop.Theme.special'}</button>
                            <div class="up-orange"></div>
                            <div id="overlay" class="overlay">
                                <div id="popup" class="popup">
                                    <h2 class="title-compo1">
                                        {l s=' Les ingrédients stars' d='Shop.Theme.Special'}
                                        <span id="btnClose" class="btnClose">&times;</span>
                                    </h2>
                                    <div>
                                        {$product.dwf_compoprod nofilter}
                                    </div>
                                </div>
                            </div>
                        <script>
                            var btnPopup = document.getElementById('btnPopup');
                            var overlay = document.getElementById('overlay');
                            btnPopup.addEventListener('click',openMoadl);
                            function openMoadl() {
                                overlay.style.display='block';
                            }
                            var btnClose = document.getElementById('btnClose');
                            btnClose.addEventListener('click',closeModal);
                            function closeModal() {
                                overlay.style.display='none';
                            }</script>
                        {/if}
                    </div>
                    {include file='catalog/ingredients.tpl'}

                {/if}
            </div>

                <div class="reuptri">
                    {if $product.dwf_howork}
                        <div class="how-work col-md-6">
                            <div class="how-text">
                                <div class="work-title">  {l s='Et comment ça' d='Shop.theme.Special'}
                                    <br/>{l s='marche ?' d='Shop.theme.Special'}</div>
                                <div class="work-description">
                                    {$product.dwf_howork nofilter}

                                </div>
                            </div>
                            {if $product.dwf_imghowit}
                                <div class="how-img">
                                    <img src="{$product.dwf_imghowit}"/>

                                </div>
                            {else}
                                <div class="how-img">
                                    <img src="{$urls.img_url}howork.png""/>

                                </div>
                            {/if}
                        </div>
                    {/if}

                    <div class="col-md-6 right-blocs">
                        {if $product.dwf_testresult}
                            <div class="clinifull">
                                <div class="clinic-test-block col-md-6">
                                    <div class="clinic-thetext">
                                        <div class="clinic-title">
                                            {l s='Résultats' d='Shop.Theme.Special'}<br/>
                                            {l s='des tests' d='Shop.Theme.Special'}<br/>
                                            {l s='cliniques' d='Shop.Theme.Special'}</div>
                                        <button id="btnPopupclinic"
                                                class="btnPopupclinic link-test">{l s='En savoir +' d='Shop.Theme.Special'}</button>
                                        <div class="up-orange"></div>
                                        <div id="overlayclinic" class="overlayclinic">
                                            <div id="popupclinic" class="popupclinic">
                                                <h2 class="title-compo1">
                                                    {l s='Résultats des tests cliniques' d='Shop.Theme.Special'}
                                                    <span id="btnCloseclinic" class="btnCloseclinic">&times;</span>
                                                </h2>
                                                <div>
                                                    {$product.dwf_testresult nofilter}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="clinicimg col-md-6">
                                    <img src="{$urls.img_url}clinictest.png"></div>

                            </div><script>
                        var btnPopupclinic = document.getElementById('btnPopupclinic');
                        var overlayclinic = document.getElementById('overlayclinic');
                        btnPopupclinic.addEventListener('click',openMoadlclinic);
                        function openMoadlclinic() {
                        overlayclinic.style.display='block';
                        }
                        var btnCloseclinic = document.getElementById('btnCloseclinic');
                        btnCloseclinic.addEventListener('click',closeModalclinic);
                        function closeModalclinic() {
                        overlayclinic.style.display='none';
                        }



                        </script>
                        {/if}
                        {if $product.dwf_producteurs}
                            <div class="blocoilproduct">
                                <div class="bg-green">
                                    <div class="pl-3 main-oli">   {l s='À la' d='Shop.Theme.Special'}
                                        <br/> {l s='Rencontre' d='Shop.Theme.Special'}
                                        <br/> {l s='de nos' d='Shop.Theme.Special'}
                                        <br/> {l s='producteurs' d='Shop.Theme.Special'}</div>
                                    <div class="best-compo ml-3">   {l s="D'où viennent nos ingrédients" d='Shop.Theme.Special'}</div>
                                    <span class="up-brown"></span>

                                </div>
                                <img src="{$urls.img_url}jeromeoliverproduct.png"
                                     class="oli-pic-product"/>
                            </div>
                        {/if}
                    </div>
                </div>

            </div>
            <div class="clearfix" style="background:#ffffff;"></div>

            {if $product.dwf_avisprod}
                <div class="avis-product container">

                    <div class="avis-produit"><img src="{$urls.img_url}etoilepleine.png" alt="" width="36"
                                                   height="34"/><img
                                src="{$urls.img_url}etoilepleine.png" alt="" width="36" height="34"/><img
                                src="{$urls.img_url}etoilepleine.png" alt="" width="36" height="34"/><img
                                src="{$urls.img_url}etoilepleine.png" alt="" width="36" height="34"/><img
                                src="{$urls.img_url}etoilepleine.png" alt="" width="36" height="34"/>
                        <div class="avis">{$product.dwf_avisprod nofilter}
                        </div>
                        <div class="auteur-avis">{if $product.dwf_auteuravis}{$product.dwf_auteuravis nofilter}{/if}
                            . {$product.name} {if $product.features }
                                {foreach from=$product.features item=feature name=features}
                                    {if $feature.name == "Senteur"}
                                        - .s {$feature.value|escape:'html':'UTF-8'}
                                    {/if}
                                {/foreach}
                            {/if}</div>
                        <a href="#">
                            <div class="lienavisproduit"> {l s="Lire les autres avis" d='Shop.Theme.Special'}</div>
                        </a>
                        <div class="up-orange-avis-cat"></div>
                    </div>

                </div>
            {/if}    </div>


        <div id="white-accessoiries">
            {block name='product_accessories'}
                {if $accessories}
                    <div class="product-accessories">
                        <p class="h5 text-uppercase">{l s='Vous allez craquer !' d='Shop.Theme.Special'}</p>
                        <div class="swiper-container products">
                            <div class="swiper-wrapper">
                                {foreach from=$accessories item="product_accessory"}
                                    {block name='product_miniature'}
                                        {include file='catalog/_partials/miniatures/product2.tpl' product=$product_accessory}
                                    {/block}
                                {/foreach}
                            </div>


                            <!-- If we need navigation buttons -->
                            <div class="swiper-button-prev swiper-button-prev-acc"></div>
                            <div class="swiper-button-next swiper-button-next-acc"></div>
                        </div>
                    </div>
                    <!-- Swiper JS -->

                    <!-- Initialize Swiper -->
                    <script>
                        var swiper = new Swiper('.swiper-container', {
                            slidesPerView: 3,
                            spaceBetween: 20,


                            loop: true,

                            pagination: {
                                el: '.swiper-pagination',
                                clickable: true,
                            },

                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            },
                        });
                    </script>


                {/if}
            {/block}
        </div>
    {block name='product_footer'}
        {hook h='displayFooterProduct' product=$product category=$category}
    {/block}

    {block name='product_images_modal'}
        {include file='catalog/_partials/product-images-modal.tpl'}
    {/block}

    {block name='page_footer_container'}
        <footer class="page-footer">
            {block name='page_footer'}
                <!-- Footer content -->
            {/block}
        </footer>
    {/block}


{/block}


