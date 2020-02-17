{*
* 20013-2015 Ambris
*
*   @author    Ambris Informatique
*   @copyright Copyright (c) 2013-2014 Ambris Informatique SARL
*   @license   Commercial license
*  International Registered Trademark & Property of PrestaShop SA
*}

{extends file='catalog/listing/search.tpl'}
{block 'product_list_header' append}
<div class="categories">
    {foreach $categories as $category}
    <a href="{$category.url|escape:'html':'UTF-8'}">
    <div class="thumbnail-container category_box">
        <div class="block-category card card-block hidden-sm-down">
          <h1 class="h1">{$category.name|escape:'html':'UTF-8'}</h1>
          {if $category.description && $show_cat_desc==1}
            <div id="category-description" class="text-muted">{$category.description|escape:'html':'UTF-8'}</div>
          {/if}
            <div class="category-cover">
              <img src="{$category.image.large.url|escape:'html':'UTF-8'}" alt="{$category.image.legend|escape:'html':'UTF-8'}">
            </div>
        </div>
        <div class="text-xs-center hidden-md-up">
          <h1 class="h1">{$category.name|escape:'html':'UTF-8'}</h1>
        </div>
    </div>
    </a>
    {/foreach}
</div>
{/block}

