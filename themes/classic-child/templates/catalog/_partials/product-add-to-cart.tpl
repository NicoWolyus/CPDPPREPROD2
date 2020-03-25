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
<div class="product-additional-info">
    {hook h='displayProductAdditionalInfo' product=$product}


</div>

<div class="product-add-to-cart">
    {if !$configuration.is_catalog}
    <span class="control-label">{l s='Quantity' d='Shop.Theme.Catalog'}</span>
    {block name='product_quantity'}
        <div class="product-quantity clearfix">
            <div class="qty">
                <input
                        type="number"
                        name="qty"
                        id="quantity_wanted"
                        value="{$product.quantity_wanted}"
                        class="input-group"
                        min="{$product.minimal_quantity}"
                        aria-label="{l s='Quantity' d='Shop.Theme.Actions'}"
                >
            </div>

            <div class="add">
                <button
                        class="btn btn-primary add-to-cart"
                        data-button-action="add-to-cart"
                        type="submit"
                        {if !$product.add_to_cart_url}
                            disabled
                        {/if}
                >
                    <img class="adcartproduct" src="{$urls.img_url}gocart.png" alt="ajouter au panier">
                    {l s='Add to cart' d='Shop.Theme.Actions'} !
                </button>

            </div>


            {hook h='displayProductActions' product=$product}
        </div>
    {/block}
    <div class="reaprod">


        {if $product.dwf_biodeg301 || $product.dwf_biodeg302}
        <div class="stock-product">
            <div class="imgchrono"><img src="{$urls.img_url}picto-biodegradable.png" class="chrono-product"></div>
        <div class="chronotest">
            <div class="title-stock">{l s ='Formule Biodégradable' d='Shop.Theme.Special'}</div>
            {if $product.dwf_biodeg301}
                <div class="text-stock">{l s ='Selon la norme OCDE 301F' d='Shop.Theme.Special'}</div>{/if}
            {if $product.dwf_biodeg302}
                <div class="text-stock">{l s ='Selon la norme OCDE 302B' d='Shop.Theme.Special'}</div>{/if}
        </div>
        </div>
    {/if}

    {block name='product_availability'}
        <span id="product-availability">

            {if $product.availability == 'available'}

                    <div class="stock-product">
                    <div class="imgchrono"><img src="{$urls.img_url}chrono.png" class="chrono-product"></div>
                    <div class="chronotest">
                        <div class="title-stock"> {l s ='En Stock' d='Shop.Theme.Special'}
                        <div class="text-stock">Expédié sous 48h</div>
                    </div>
                </div>


{elseif $product.availability == 'last_remaining_items'}
  <div class="stock-product">
                <div class="imgchrono"><img src="{$urls.img_url}outofstock.png " class="chrono-product"></div>
                      <div class="title-stock laststock">  {$product.availability_message}</div>

  </div>

{else}



                        {/if}


      </span>
    {/block}
</div>
    {block name='product_minimal_quantity'}
        <p class="product-minimal-quantity">
            {if $product.minimal_quantity > 1}
                {l
                s='The minimum purchase order quantity for the product is %quantity%.'
                d='Shop.Theme.Checkout'
                sprintf=['%quantity%' => $product.minimal_quantity]
                }
            {/if}
        </p>
    {/block}
{/if}

