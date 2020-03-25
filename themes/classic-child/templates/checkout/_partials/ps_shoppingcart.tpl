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
<div id="_desktop_cart">
  <div class="blockcart cart-preview {if $cart.products_count > 0}active{else}inactive{/if}" data-refresh-url="{$refresh_url}">
    <div class="header">

        <a rel="nofollow" href="{$urls.pages.order}">

            {if $page.page_name == 'index'}
                <img src="{$urls.img_url}cart.png" id="wish-icon" class="icon-top">
            {elseif  $page.page_name == 'category'}
                {if $category.id == '329' || $category.id == '324' || $category.id == '325' || $category.id == '326' || $category.id == '327' || $category.id == '328' || $category.id == '330' || $category.id == '331' || $category.id == '332' || $category.id == '333' || $category.id == '334' || $category.id == '335' || $category.id == '336' || $category.id == '334' || $category.id == '337' || $category.id == '343' ||$category.id == '345' || $category.id == '346' || $category.id == '347'}

                    <img src="{$urls.img_url}cart.png" id="wish-icon" class="icon-top">
                {elseif $category.id != '329' || $category.id != '324' || $category.id != '333' || $category.id != '337' || $category.id != '343'}
                    <img src="{$urls.img_url}cartblack.png" id="wish-icon" class="icon-top">
                {/if}
            {else}
                <img src="{$urls.img_url}cartblack.png" id="wish-icon" class="icon-top">
            {/if}

        <span class="cart-products-count">{$cart.products_count}</span>

        </a>

    </div>
  </div>
</div>
