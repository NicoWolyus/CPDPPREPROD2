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



{if $page.page_name == 'index'}
    [creativeslider id="3"]
    [creativeslider id="2"]
{/if}
{hook h='displayNav1'}


{block name='header_nav'}
    <nav class="header-nav"{if $page.page_name == 'category'}{if $category.id == '329' || $category.id == '324' || $category.id == '325' || $category.id == '327' || $category.id == '326' || $category.id == '328' || $category.id == '330' || $category.id == '331' || $category.id == '332' || $category.id == '333' || $category.id == '334' || $category.id == '335' || $category.id == '336' || $category.id == '334' || $category.id == '337' || $category.id == '343' ||$category.id == '345' || $category.id == '346' || $category.id == '347'}id="whitepart"{/if}{/if}>

    {hook h='displayNavFullWidth'}
    <div class="container p-0">

    {block name='header_top'}
        <div class="header-top">

            <div class="col-md-3" id="_desktop_logo">

                {if $page.page_name == 'index'}
                    <h1>
                        <a href="{$urls.base_url}">
                            <img class="logo img-responsive" src="{$shop.logo}" alt="{$shop.name}">
                        </a>
                    </h1>
                {elseif $page.page_name == 'category'}
                    {if $category.id == '329' || $category.id == '324' || $category.id == '325' || $category.id == '327' || $category.id == '326' || $category.id == '328' || $category.id == '330' || $category.id == '331' || $category.id == '332' || $category.id == '333' || $category.id == '334' || $category.id == '335' || $category.id == '336' || $category.id == '334' || $category.id == '337' || $category.id == '343' ||$category.id == '345' || $category.id == '346' || $category.id == '347'}
                        <a href="{$urls.base_url}">
                            <img class="logo img-responsive" src="{$shop.logo}" alt="{$shop.name}">
                        </a>
                    {else}
                        <a href="{$urls.base_url}">
                            <img class="logo img-responsive" src="{$urls.img_url}logonoir.png" alt="{$shop.name}">
                        </a>
                    {/if}
                {else}
                    <a href="{$urls.base_url}">
                        <img class="logo img-responsive" src="{$urls.img_url}logonoir.png" alt="{$shop.name}">
                    </a>
                {/if}

            </div>
            {*    <div class="col-md-1 col-sm-12 position-static">
                    {hook h='displayTop'}
                    <div class="clearfix"></div>
                </div>*}


            <div class="col-md-3 right-nav">
                {hook h='displayNav2'}
            </div>
        </div>
        </div>

        </div>

        </nav>

    {/block}





    </div>

{/block}


