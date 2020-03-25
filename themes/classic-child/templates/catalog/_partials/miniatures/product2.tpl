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


{block name='product_miniature_item'}
    <div class="product-miniature js-product-miniature swiper-slide" data-id-product="{$product.id_product}"
             data-id-product-attribute="{$product.id_product_attribute}" itemscope itemtype="http://schema.org/Product">
        <div class="thumbnail-container"> {if $product.dwf_originredient}
            <div class="box-origine-list">
                <div class="origin-number">{$product.dwf_originredient}%</div>
                <div class="origin-text">d'ingrédients d'origine naturelle</div>
            </div>
            {/if}<!-- @todo: use include file='catalog/_partials/product-flags.tpl'} -->
            {if $product.dwf_nouveau}
                <div class="newlist">Nouveau</div>
            {/if} {block name='product_flags'}
                <ul class="product-flags">
                    {foreach from=$product.flags item=flag}
                        <li class="product-flag {$flag.type}">{$flag.label}</li>
                    {/foreach}
                </ul>
            {/block}
            {block name='product_thumbnail'}
                {if $product.cover}
                    <a href="{$product.url}" class="thumbnail product-thumbnail">
                        <img
                                src="{$product.cover.bySize.home_default.url}"
                                alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:30:'...'}{/if}"
                                data-full-size-image-url="{$product.cover.large.url}"
                        />
                    </a>
                {else}
                    <a href="{$product.url}" class="thumbnail product-thumbnail">
                        <img src="{$urls.no_picture_image.bySize.home_default.url}"/>
                    </a>
                {/if}
            {/block}


            <div class="product-description">
                <div class="decli-list">
                    {if $product.features }

                       {foreach from=$product.features item=feature name=features}
                          {if $feature.name == "Format"}

                    <span class="defaultdecli">



                                  {if $product.dwf_imgformat}<img
                                      src="{$product.dwf_imgformat}" />
                                  {/if}<span class="featlist"> {$feature.value|escape:'html':'UTF-8'}</span></span>
                          {/if}

                       {/foreach}
                    {/if}

                    <span class="autresdecli"> {if $product.dwf_senteursnumber}+ {$product.dwf_senteursnumber} autres senteurs{/if}</span>

                </div>

                {block name='product_name'}
                    {if $page.page_name == 'index'}
                        <h3 class="h3 product-title" itemprop="name"><a
                                    href="{$product.url}"> {$product.name|truncate:80:'...'}</a></h3>
                        {if $product.features }
                            {foreach from=$product.features item=feature name=features}
                                {if $feature.name == "Senteur"}
                                    <div class="catdef">{$feature.value|escape:'html':'UTF-8'}<br/></div>
                                {/if}
                            {/foreach}
                        {/if}
                    {else}
                        <h2 class="h3 product-title" itemprop="name"><a
                                    href="{$product.url}"> {$product.name|truncate:80:'...'}</a></h2>
                        {if $product.features }
                            {foreach from=$product.features item=feature name=features}
                                {if $feature.name == "Senteur"}
                                    <div class="catdef">{$feature.value|escape:'html':'UTF-8'}<br/></div>
                                {/if}
                            {/foreach}
                        {/if}
                    {/if}
                {/block}

                {block name='product_price_and_shipping'}
                    {if $product.show_price}
                        <div class="product-price-and-shipping">


                            {hook h='displayProductPriceBlock' product=$product type="before_price"}

                            <span class="sr-only">{l s='Price' d='Shop.Theme.Catalog'}</span>
                            <span itemprop="price" class="price {if $product.discount_type === 'percentage' || $product.discount_type === 'amount'}greenprice{/if} ">{$product.price}</span>
                            {if $product.has_discount}
                                {hook h='displayProductPriceBlock' product=$product type="old_price"}
                                <span class="sr-only">{l s='Regular price' d='Shop.Theme.Catalog'}</span>
                                <span class="regular-price">{$product.regular_price}</span>
                                {if $product.discount_type === 'percentage'}
                                    <span class="discount-percentage discount-product">{$product.discount_percentage}</span>
                                {elseif $product.discount_type === 'amount'}
                                    <span class="discount-amount discount-product">{$product.discount_amount_to_display}</span>
                                {/if}
                            {/if}
                            {hook h='displayProductPriceBlock' product=$product type='unit_price'}

                            {hook h='displayProductPriceBlock' product=$product type='weight'}
                        </div>
                    {/if}
                {/block}

                {block name='product_reviews'}
                    {hook h='displayProductListReviews' product=$product}
                {/block}
            </div>


            <div class="highlighted-informations{if !$product.main_variants} no-variants{/if} hidden-sm-down">
                {block name='quick_view'}
                    <a class="quick-view" href="#" data-link-action="quickview">
                        <a href="#" class="quick-wish-block"> <img class="quick-wish" src="{$urls.img_url}wish.png"
                                                                   alt="add to wishlist"></a>
                        <a href="{$product.url}" class="quick-cart-block"> <img class="quick-cart"
                                                                                src="{$urls.img_url}gocart.png"
                                                                                alt="add to cart"></a>
                    </a>
                {/block}

                {block name='product_variants'}
                    {if $product.main_variants}
                        {include file='catalog/_partials/variant-links.tpl' variants=$product.main_variants}
                    {/if}
                {/block}
            </div>
        </div>
    </div>
{/block}

