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

<div class="pw-fav-link" id="wishlist">
  <a href="{$link->getModuleLink('pwfavorites', 'favorites')}" rel="nofollow">
      {if $page.page_name == 'index'}
          <img class="quick-wish icon-top" src="{$urls.img_url}wish.png" id="wish-icon">
      {elseif $page.page_name == 'category'}
          {if $category.id == '329' || $category.id == '324' || $category.id == '325' || $category.id == '326' || $category.id == '327' || $category.id == '328' || $category.id == '330' || $category.id == '331' || $category.id == '332' || $category.id == '333' || $category.id == '334' || $category.id == '335' || $category.id == '336' || $category.id == '334' || $category.id == '337' || $category.id == '343' ||$category.id == '345' || $category.id == '346' || $category.id == '347'}
              <img class="quick-wish icon-top" src="{$urls.img_url}wish.png" id="wish-icon">
          {else}
              <img class="quick-wish icon-top" src="{$urls.img_url}wishblack.png" id="wish-icon">
          {/if}
      {else}
          <img class="quick-wish icon-top" src="{$urls.img_url}wishblack.png" id="wish-icon">
      {/if}





  </a>
</div>
