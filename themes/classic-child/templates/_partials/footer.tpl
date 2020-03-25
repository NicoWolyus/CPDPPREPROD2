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

<div class="footer-reas">
    <div class="container">
        <div class="row">
    <div class="col-md-3 rea-block">
     <div class="reaimg">   <img class="rea-footer" src="{$urls.img_url}rea-livraison.png"></div>
<div class="reatext">
        <div class="rea-title">Livraison soignée</div>
        <div class="rea-des">Emaballée avec amour</div>

    </div></div>
    <div class="col-md-3 rea-block">

        <div class="reaimg">   <img class="rea-footer" src="{$urls.img_url}rea-shop.png"></div>
        <div class="reatext">
        <div class="rea-title">Venez nous rencontrer</div>
        <div class="rea-des">Dans l'une de nos boutiques</div>

        </div></div>
    <div class="col-md-3 rea-block">

        <div class="reaimg">   <img class="rea-footer" src="{$urls.img_url}rea-sample.png"></div>
        <div class="reatext">
        <div class="rea-title">Échantillons offerts</div>
        <div class="rea-des">Choisissez vos favoris</div>
        </div>
    </div>
    <div class="col-md-3 rea-block">
        <div class="reaimg">   <img class="rea-footer" src="{$urls.img_url}rea-provence.png"></div>
        <div class="reatext">
        <div class="rea-title">Designed in provence</div>
        <div class="rea-des">avec le sourire</div>
        </div>

    </div>
        </div>
    </div>

   {* <div class="container">
        <div class="row">
            {block name='hook_footer_before'}
                {hook h='displayFooterBefore'}
            {/block}

        </div>
    </div>*}
    <div class="footer-container">
        <div class="container">
            <div class="row">
                {block name='hook_footer'}
                    {hook h='displayFooter'}
                {/block}

            </div>


            <div class="row">
                {block name='hook_footer_after'}
                    {hook h='displayFooterAfter'}
                {/block}
            </div>
        </div>
            <div class="row">
                <div class="col-md-12 footer-copy">
                    <p class="text-sm-center">
                        {block name='copyright_link'}
                            <a class="_blank" href="" target="_blank" rel="nofollow" id="copy">
                                Compagnie de Provence 2019 - Tous droits reservés
                           </a>

                        {/block}
                    </p>

                </div>
            </div>
        </div>
