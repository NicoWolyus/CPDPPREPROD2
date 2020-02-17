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
<a href="{$urls.base_url}wishlist/" id="wish-block">
<div id="wishlist">

<img src="{$urls.img_url}wish.png" id="wish-icon" class="icon-top">

</div>

</a>



<div id="_desktop_user_info">
    <div class="user-info">
        {if $logged}
            <a
                    class="logout hidden-sm-down"
                    href="{$logout_url}"
                    rel="nofollow"
            >
                <img src="{$urls.img_url}account.png" id="search-icon" class="icon-top">

            </a>
            <a
                    class="account"
                    href="{$my_account_url}"
                    title="{l s='View my customer account' d='Shop.Theme.Customeraccount'}"
                    rel="nofollow"
            >
                <img src="{$urls.img_url}account.png" id="search-icon" class="icon-top">
                <span class="hidden-sm-down">{$customerName}</span>
            </a>
        {else}
            <a
                    href="{$my_account_url}"
                    title="{l s='Log in to your customer account' d='Shop.Theme.Customeraccount'}"
                    rel="nofollow"
            >
                <img src="{$urls.img_url}account.png" id="search-icon" class="icon-top">

            </a>
        {/if}
    </div>
</div>
