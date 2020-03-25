{**  {if $page.page_name == 'index'}
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
{if $page.page_name == 'category'}
{if $category.id == '329' || $category.id == '324' || $category.id == '325' || $category.id == '326' || $category.id == '327' || $category.id == '328' || $category.id == '330' || $category.id == '331' || $category.id == '332' || $category.id == '333' || $category.id == '334' || $category.id == '335' || $category.id == '336' || $category.id == '334' || $category.id == '337' || $category.id == '343' ||$category.id == '345' || $category.id == '346' || $category.id == '347'}

  {block name='product_list_header'}
    {include file='catalog/_partials/category-header.tpl' listing=$listing category=$category}
  {/block}
{function name="categories" nodes=[] depth=0}
  {strip}
    {if $nodes|count}

      <ul class="category-sub-menu">
        {foreach from=$nodes item=node}
          <li data-depth="{$depth}">
            {if $depth===0}
              <a href="{$node.link}">{$node.name}</a>
              {if $node.children}
                <div class="navbar-toggler collapse-icons" data-toggle="collapse" data-target="#exCollapsingNavbar{$node.id}">
                  <i class="material-icons add">&#xE145;</i>
                  <i class="material-icons remove">&#xE15B;</i>
                </div>
                <div class="collapse" id="exCollapsingNavbar{$node.id}">
                  {categories nodes=$node.children depth=$depth+1}
                </div>
              {/if}
            {else}
              <a class="category-sub-link" href="{$node.link}">{$node.name}</a>
              {if $node.children}
                <span class="arrows" data-toggle="collapse" data-target="#exCollapsingNavbar{$node.id}">
                  <i class="material-icons arrow-right">&#xE315;</i>
                  <i class="material-icons arrow-down">&#xE313;</i>
                </span>
                <div class="collapse" id="exCollapsingNavbar{$node.id}">
                  {categories nodes=$node.children depth=$depth+1}
                </div>
              {/if}
            {/if}
          </li>
        {/foreach}
        <li class="custom-filters" id="cust-filt"><img src="{$urls.img_url}filter.png" alt="filters" class="filterscat" id="filtercat">+ de filtres</li>
      </ul>
    {/if}
  {/strip}
{/function}
    <div class="col-md-1 hidden-sm-down total-products">
        {if $listing.pagination.total_items > 1}
            <p>{l s='There are %product_count% products.' d='Shop.Theme.Catalog' sprintf=['%product_count%' => $listing.pagination.total_items]}</p>
        {elseif $listing.pagination.total_items > 0}
            <p>{l s='There is 1 product.' d='Shop.Theme.Catalog'}</p>
        {/if}
        <img src="{$urls.img_url}arrowbottom.png" id="arrowbotcategory">
    </div>
<div class="block-categories">
  <ul class="category-top-menu">

    <li>{categories nodes=$categories.children}</li>
  </ul>
</div>
{/if}
    <script>document.querySelector("#cust-filt").onclick = function () {
            if (window.getComputedStyle(document.querySelector('#search_filters')).display == 'none') {
                document.querySelector("#search_filters").style.display = "block";
            } else {
                document.querySelector("#search_filters").style.display = "none";
            }

        }
    </script>
{/if}
