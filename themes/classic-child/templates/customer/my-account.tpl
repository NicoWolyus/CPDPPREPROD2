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
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Your account' d='Shop.Theme.Customeraccount'}
{/block}


{block name='page_content'}
  <div class="sous-account">{l s='Bienvenue sur votre page d\'accueil.' d='Shop.Theme.Special'}</div>
  <div class="sous-account-min">{l s='Vous pouvez y gérer vos informations personnelles ainsi que vos commandes.' d='Shop.Theme.Special'}</div>

    <div class="col-md-3" id="list-old-account">
        <div class="acc-my-account">  {l s='Your account' d='Shop.Theme.Customeraccount'}</div>
        <ul>
           <a href="{$urls.base_url}"> <li>{l s ='Accueil' d='Shop.Theme.Special'}</li></a>
            <a href="{$urls.base_url}"> <li>{l s ='Historique de mes commandes' d='Shop.Theme.Special'}</li></a>
            <a href="{$urls.base_url}"> <li>{l s ='Mes adresses' d='Shop.Theme.Special'}</li></a>
            <a href="{$urls.base_url}"> <li>{l s ='Mes informations personelles' d='Shop.Theme.Special'}</li></a>
            <a href="{$urls.base_url}"> <li>{l s ='Mes bon de réductions' d='Shop.Theme.Special'}</li></a>
            <a href="{$urls.base_url}"> <li>{l s ='Mes favoris' d='Shop.Theme.Special'}</li></a>
            <a href="{$urls.base_url}"> <li>{l s ='RGPD - Données personelles' d='Shop.Theme.Special'}</li></a>
        </ul>
        <div class="">
            <a href="{$logout_url}" >
                {l s='Sign out' d='Shop.Theme.Actions'}
            </a>
        </div>
    </div>
  <div class="row">
    <div class="links">

      {if !$configuration.is_catalog}
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="history-link" href="{$urls.pages.history}">
          <span class="link-item">
            <img src="{$urls.img_url}picto-colis.png" class="icon-account">
              <span class="text-item">
            {l s='Order history and details' d='Shop.Theme.Customeraccount'}</span>
          </span>
        </a>
      {/if}


      {if $customer.addresses|count}
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="addresses-link" href="{$urls.pages.addresses}">
          <span class="link-item">
             <img src="{$urls.img_url}picto-map.png" class="icon-account">
              <span class="text-item">  {l s='Addresses' d='Shop.Theme.Customeraccount'}</span>
          </span>
        </a>
      {else}
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="address-link" href="{$urls.pages.address}">
          <span class="link-item">
             <img src="{$urls.img_url}picto-map.png" class="icon-account">
              <span class="text-item">    {l s='Add first address' d='Shop.Theme.Customeraccount'}</span>
          </span>
        </a>
      {/if}

      <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="identity-link" href="{$urls.pages.identity}">
        <span class="link-item">
            <img src="{$urls.img_url}picto-account.png" class="icon-account">
            <span class="text-item">   {l s='Information' d='Shop.Theme.Customeraccount'}</span>
        </span>
      </a>




      {if !$configuration.is_catalog}
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="order-slips-link" href="{$urls.pages.order_slip}">
          <span class="link-item">
           <img src="{$urls.img_url}picto-pourcent.png" class="icon-account">
              <span class="text-item">   {l s='Credit slips' d='Shop.Theme.Customeraccount'}</span>
          </span>
        </a>
      {/if}

      {if $configuration.voucher_enabled && !$configuration.is_catalog}
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="discounts-link" href="{$urls.pages.discount}">
          <div class="link-item" id="wishlist">
           <img src="{$urls.img_url}picto-pourcent.png" class="icon-account icon-top" id="wish-icon">
              <span class="text-item">   {l s='Vouchers' d='Shop.Theme.Customeraccount'}</span>
          </div>
        </a>
      {/if}

      {if $configuration.return_enabled && !$configuration.is_catalog}
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="returns-link" href="{$urls.pages.order_follow}">
          <span class="link-item">
            <i class="material-icons">&#xE860;</i>
              <span class="text-item">  {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}</span>
          </span>
        </a>
      {/if}

      {block name='display_customer_account'}
        {hook h='displayCustomerAccount'}
      {/block}

    </div>
  </div>
{/block}


{block name='page_footer'}
  {block name='my_account_links'}

  {/block}
{/block}
