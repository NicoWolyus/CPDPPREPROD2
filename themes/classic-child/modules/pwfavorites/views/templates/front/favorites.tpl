{**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

{extends file='customer/page.tpl'}

{block name='page_content'}

  {block name='product_list_header'}
    <header class="page-header">
      <h1>{$listing.label}</h1>
    </header>
  {/block}

  <section id="products">
    {if $listing.products|count}

      <div id="js-product-list-top" class="row products-selection">
        <div class="col-md-6 hidden-sm-down total-products">
        </div>
        <div class="col-md-6">
          <div class="row sort-by-row">

            {block name='sort_by'}
              {include file='catalog/_partials/sort-orders.tpl' sort_orders=$listing.sort_orders}
            {/block}

            {if !empty($listing.rendered_facets)}
              <div class="col-sm-3 col-xs-4 hidden-md-up filter-button">
                <button id="search_filter_toggler" class="btn btn-secondary">
                  {l s='Filter' mod='pwfavorites'}
                </button>
              </div>
            {/if}
          </div>
        </div>
      </div>

      <div id="js-product-product-list">
        {block name='product_list'}
          {include file='catalog/_partials/products.tpl' listing=$listing}
        {/block}
      </div>

      <div id="js-product-list-bottom">
        {block name='product_list_bottom'}
          {include file='catalog/_partials/products-bottom.tpl' listing=$listing}
        {/block}
      </div>

      <section id="page-content" class="page-content page-not-found" style="display: none;">
        {block name='page_content'}
          <h4>{$listing.no_favorites_yet}</h4>
        {/block}
      </section>

    {else}
      <section id="page-content" class="page-content page-not-found">
        {block name='page_content'}
          <h4>{$listing.no_favorites_yet}</h4>
        {/block}
      </section>
    {/if}
  </section>

{/block}
