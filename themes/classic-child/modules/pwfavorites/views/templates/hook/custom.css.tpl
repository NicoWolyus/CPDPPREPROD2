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

.pw-fav-toggle, .pw-fav-toggle:hover, .pw-fav-toggle:focus {
  color: {$button_color_add};
}

.pw-fav-toggle.active {
  color: {$button_color_remove};
}

.pw-fav-toggle:hover i {
  text-shadow: 0 0 4px {$button_color_add};
}

.pw-fav-toggle.active:hover i {
  text-shadow: 0 0 4px {$button_color_remove};
}

{$product_miniature_selector} .pw-fav-toggle .pw-fav-btn-text {
  display: none;
}

{$product_miniature_selector} .pw-fav-toggle {
  position: absolute;
  right: 0;
  margin: 8px;
}

{$product_miniature_selector} .pw-fav-toggle {
{if $move_button && $product_miniature_selector && $product_thumbnail_selector}
  top: 0;
{else}
  bottom: 0;
{/if}
}
