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
{block name='header_banner'}
{hook h='displayBanner'}
  {if $displayedFacets|count}

    <div id="search_filters">
      {block name='facets_title'}
        <p class="text-uppercase h6 hidden-sm-down">{l s='Filter By' d='Shop.Theme.Actions'}</p>
      {/block}
      <div id="js-product-list-top" class="row products-selection">

        <div class="col-md-12">
          <div class="row sort-by-row">

            {block name='sort_by'}
              {include file='catalog/_partials/sort-orders.tpl' sort_orders=$listing.sort_orders}
            {/block}

            {if !empty($listing.rendered_facets)}
              <div class="col-sm-3 col-xs-4 hidden-md-up filter-button">
                <button id="search_filter_toggler" class="btn btn-secondary">
                  {l s='Filter' d='Shop.Theme.Actions'}
                </button>
              </div>
            {/if}
          </div>
        </div>
        <div class="col-sm-12 hidden-md-up text-sm-center showing">
          {l s='Showing %from%-%to% of %total% item(s)' d='Shop.Theme.Catalog' sprintf=[
          '%from%' => $listing.pagination.items_shown_from ,
          '%to%' => $listing.pagination.items_shown_to,
          '%total%' => $listing.pagination.total_items
          ]}
        </div>
      </div>
      {block name='facets_clearall_button'}
        {if $activeFilters|count}
          <div id="_desktop_search_filters_clear_all" class="hidden-sm-down clear-all-wrapper">
            <button data-search-url="{$clear_all_link}" class="btn btn-tertiary js-search-filters-clear-all">

              {l s='Clear all' d='Shop.Theme.Actions'}
            </button>
          </div>
        {/if}
      {/block}

      {foreach from=$displayedFacets item="facet"}
        <section class="facet clearfix">
          <p class="h6 facet-title hidden-sm-down">{$facet.label}</p>
          {assign var=_expand_id value=10|mt_rand:100000}
          {assign var=_collapse value=true}
          {foreach from=$facet.filters item="filter"}
            {if $filter.active}{assign var=_collapse value=false}{/if}
          {/foreach}

          <div class="title hidden-md-up" data-target="#facet_{$_expand_id}" data-toggle="collapse"{if !$_collapse} aria-expanded="true"{/if}>
            <p class="h6 facet-title">{$facet.label}</p>
            <span class="navbar-toggler collapse-icons">
            <i class="material-icons add">&#xE313;</i>
            <i class="material-icons remove">&#xE316;</i>
          </span>
          </div>

          {if in_array($facet.widgetType, ['radio', 'checkbox'])}
            {block name='facet_item_other'}
              <ul id="facet_{$_expand_id}" class="collapse{if !$_collapse} in{/if}">
                {foreach from=$facet.filters key=filter_key item="filter"}
                  {if !$filter.displayed}
                    {continue}
                  {/if}

                  <li>
                    <label class="facet-label{if $filter.active} active {/if}" for="facet_input_{$_expand_id}_{$filter_key}">
                      {if $facet.multipleSelectionAllowed}
                        <span class="custom-checkbox">
                        <input
                                id="facet_input_{$_expand_id}_{$filter_key}"
                                data-search-url="{$filter.nextEncodedFacetsURL}"
                                type="checkbox"
                                {if $filter.active }checked{/if}
                        >
                        {if isset($filter.properties.color)}
                          <span class="color" style="background-color:{$filter.properties.color}"></span>
                        {elseif isset($filter.properties.texture)}
                          <span class="color texture" style="background-image:url({$filter.properties.texture})"></span>
                        {else}
                          <span {if !$js_enabled} class="ps-shown-by-js" {/if}><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                        {/if}
                      </span>
                      {else}
                        <span class="custom-radio">
                        <input
                                id="facet_input_{$_expand_id}_{$filter_key}"
                                data-search-url="{$filter.nextEncodedFacetsURL}"
                                type="radio"
                                name="filter {$facet.label}"
                                {if $filter.active }checked{/if}
                        >
                        <span {if !$js_enabled} class="ps-shown-by-js" {/if}></span>
                      </span>
                      {/if}

                      <a
                              href="{$filter.nextEncodedFacetsURL}"
                              class="_gray-darker search-link js-search-link"
                              rel="nofollow"
                      >
                        {$filter.label}
                        {if $filter.magnitude and $show_quantities}
                          <span class="magnitude">({$filter.magnitude})</span>
                        {/if}
                      </a>
                    </label>
                  </li>
                {/foreach}
              </ul>
            {/block}

          {elseif $facet.widgetType == 'dropdown'}
            {block name='facet_item_dropdown'}
              <ul id="facet_{$_expand_id}" class="collapse{if !$_collapse} in{/if}">
                <li>
                  <div class="col-sm-12 col-xs-12 col-md-12 facet-dropdown dropdown">
                    <a class="select-title" rel="nofollow" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      {$active_found = false}
                      <span>
                      {foreach from=$facet.filters item="filter"}
                        {if $filter.active}
                          {$filter.label}
                          {if $filter.magnitude and $show_quantities}
                            ({$filter.magnitude})
                          {/if}
                          {$active_found = true}
                        {/if}
                      {/foreach}
                        {if !$active_found}
                          {l s='(no filter)' d='Shop.Theme.Global'}
                        {/if}
                    </span>
                      <i class="material-icons float-xs-right">&#xE5C5;</i>
                    </a>
                    <div class="dropdown-menu">
                      {foreach from=$facet.filters item="filter"}
                        {if !$filter.active}
                          <a
                                  rel="nofollow"
                                  href="{$filter.nextEncodedFacetsURL}"
                                  class="select-list"
                          >
                            {$filter.label}
                            {if $filter.magnitude and $show_quantities}
                              ({$filter.magnitude})
                            {/if}
                          </a>
                        {/if}
                      {/foreach}
                    </div>
                  </div>
                </li>
              </ul>
            {/block}

          {elseif $facet.widgetType == 'slider'}
            {block name='facet_item_slider'}
              {foreach from=$facet.filters item="filter"}
                <ul id="facet_{$_expand_id}"
                    class="faceted-slider collapse{if !$_collapse} in{/if}"
                    data-slider-min="{$facet.properties.min}"
                    data-slider-max="{$facet.properties.max}"
                    data-slider-id="{$_expand_id}"
                    data-slider-values="{$filter.value|@json_encode}"
                    data-slider-unit="{$facet.properties.unit}"
                    data-slider-label="{$facet.label}"
                    data-slider-specifications="{$facet.properties.specifications|@json_encode}"
                    data-slider-encoded-url="{$filter.nextEncodedFacetsURL}"
                >
                  <li>
                    <p id="facet_label_{$_expand_id}">
                      {$filter.label}
                    </p>

                    <div id="slider-range_{$_expand_id}"></div>
                  </li>
                </ul>
              {/foreach}
            {/block}
          {/if}
        </section>
      {/foreach}

    </div>

  {/if}
{/block}
<div id="js-product-list">

  <div class="products row">
    {foreach from=$listing.products item="product"}
      {block name='product_miniature'}
       {* {if $product.dwf_pined}{include file='catalog/_partials/miniatures/productpinned.tpl' product=$product}{else}*}
        {include file='catalog/_partials/miniatures/product.tpl' product=$product}
        {*{/if}*}
      {/block}
    {/foreach}
  </div>

  {block name='pagination'}
    {include file='_partials/pagination.tpl' pagination=$listing.pagination}
  {/block}

  <div class="hidden-md-up text-xs-right up">
    <a href="#header" class="btn btn-secondary">
      {l s='Back to top' d='Shop.Theme.Actions'}
      <i class="material-icons">&#xE316;</i>
    </a>
  </div>
</div>
