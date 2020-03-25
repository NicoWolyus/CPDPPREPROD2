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
<div id="blockcart-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><span class="material-icons close-modal"></span></span>
                </button>
                <h1 class="h1">{l s='Shopping Cart' d='Shop.Theme.Checkout'}</h1>{*<h4 class="modal-title h6 text-sm-center" id="myModalLabel"><i class="material-icons rtl-no-flip">&#xE876;</i>{l s='Product successfully added to your shopping cart' d='Shop.Theme.Checkout'}</h4>*}
      <div class="freeship-top">
        <div class="freeship-top-advice">{l s='Livraison offerte à partir de 55€ d\'achat' d='Shop.Theme.Special'}</div>

          {if (($cart.totals.total.amount)+($cart.subtotals.tax.amount))<55}

              <div class="freeship-top-amount">{l s ='Plus que' d='Shop.Theme.Special'} {55-(($cart.totals.total.amount)+($cart.subtotals.tax.amount))}€ {l s ='pour en profiter' d='Shop.Theme.Special'}
              </div>
          {/if}


      </div>

            </div>
            <div class="modal-body">
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


                <section id="main">
                    <div class="cart-grid row">

                        <!-- Left Block: cart product informations & shpping -->
                        <div class="cart-grid-body col-xs-12 col-lg-12">

                            <!-- cart products detailed -->
                            <div class="card cart-container">
                                <div class="card-block">

                                </div>

                                {block name='cart_overview'}
                                    {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
                                {/block}
                            </div>


                            <!-- shipping informations -->
                            {block name='hook_shopping_cart_footer'}
                                {hook h='displayShoppingCartFooter'}
                            {/block}
                        </div>

                        <!-- Right Block: cart subtotal & cart total -->
                        <div class="col-xs-12 col-lg-12">

                            {block name='cart_summary'}
                                <div class="card cart-summary">

                                    {block name='hook_shopping_cart'}
                                        {hook h='displayShoppingCart'}
                                    {/block}

                                    {block name='cart_totals'}
                                        {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$cart}
                                    {/block}
                                    <div class="end-cart">
                                    {block name='continue_shopping'}
                                        <a class="label" id="ke" href="{$urls.pages.index}">    <div class="keep-shopping">

                                            <i class="back-to-buy"></i>
                                                <span class="text-keep-shopping">{l s='Poursuivre' d='Shop.Theme.Actions'}<br/>
                                                    {l s='Mes achats' d='Shop.Theme.Actions'}</span>
                                    </div>    </a>
                                    {/block}
                                    {block name='cart_actions'}
                                        {include file='checkout/_partials/cart-detailed-actions.tpl' cart=$cart}
                                    {/block}

                                </div>
                                </div>
                            {/block}

                            {block name='hook_reassurance'}
                                {hook h='displayReassurance'}
                            {/block}

                        </div>

                    </div>
                </section>


            </div>
        </div>
    </div>
</div>
