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
                <div class="social-media">
                    <a href="https://www.facebook.com/compagniedeprovence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}facebook.png" alt="facebook"> </a>
                    <a href="https://www.instagram.com/compagniedeprovence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}instagram.png" alt="instagram"> </a>
                    <a href="https://www.pinterest.fr/compagniedeprovence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}pinterest.png" alt="pinterest"> </a>
                    <a href="https://www.linkedin.com/company/la-compagnie-de-provence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}linkedin.png" alt="linkedin"> </a>



                </div>
            </div>

            <div class="custom-footer">

                <div class="col-md-12 p-0">
                    <div class="col-md-2 pl-0">
                        <div class="tittle-footer">
                            E-shop
                        </div>
                        <ul class="category-footer">
                          <a href="./324-savon-de-marseille">  <li>Savon de Marseille</li></a>
                            <a href="./329-soin-du-visage">    <li>Soin du visage</li></a>
                                <a href="./333-soin-des-mains">   <li>Savon des mains</li></a>
                                    <a href="./337-soin-du-corps">  <li>Soin du corps</li></a>
                                        <a href="./343-maison">   <li>Maison</li></a>
                        </ul>
                    </div>
                    <div class="col-md-2 pl-0">
                        <div class="tittle-footer">
                            La Marque
                        </div>
                        <ul class="category-footer">
                            <a href="./lamarque.php">   <li>Qui sommes-nous ?</li></a>

                            <li>Recrutement</li>

                        </ul>
                    </div>

                    <div class="col-md-2 pl-0">
                        <div class="tittle-footer">
                            Nos boutiques
                        </div>
                        <ul class="category-footer">
                            <li>Points de vente</li>

                        </ul>
                        <div class="tittle-footer">
                            Le journal
                        </div>
                        <ul class="category-footer">
                            <a href="./blog"><li>Le journal</li></a>

                        </ul>

                    </div>
                    <div class="col-md-2 pl-0">
                        <a href="{$urls.pages.contact}"> <li>Contactez-nous</li></a>
                        <a href="/content/16-cgv">   <li>CGV</li></a>
                        <a href="./notre-programme-fidelite"><li>Programme de fidélité</li></a>
                       <a href="./faqs"><li>FAQ</li></a>

                    </div>
                    <div class="col-md-4">
                        <a href="./espace-presse">   <li>Presse</li>
                            <a href="./devenir-revendeur-ou-distributeur"> <li>Devenir revendeur ou distributeur</li></a>
                        <div class="eco-disclaim col-md-12">
                            <img class="logo img-responsive col-md-2 p-0" src="{$urls.img_url}eco.png" alt="{$shop.name}">
                            <div class="eco-slaim col-md-10">
Pensez à trier vos emballages ! Pour en savoir plus sur les consignes de tri en France www.consignesdetri.fr


                </div>


                        </div>

                    </div>


                </div>


            </div>
            <div class="mobile-footer custom-footer">

                <div class="col-md-12 p-0">
                    <div class="col-md-6 pl-0">
                        <div class="tittle-footer">
                            E-shop
                        </div>
                        <ul class="category-footer">
                            <a href="./324-savon-de-marseille">  <li>Savon de Marseille</li></a>
                            <a href="./329-soin-du-visage">    <li>Soin du visage</li></a>
                            <a href="./333-soin-des-mains">   <li>Savon des mains</li></a>
                            <a href="./337-soin-du-corps">  <li>Soin du corps</li></a>
                            <a href="./343-maison">   <li>Maison</li></a>
                        </ul>
                    </div>
                    <div class="col-md-6 pl-0">
                        <div class="tittle-footer">
                            La Marque
                        </div>
                        <ul class="category-footer">
                            <a href="./lamarque.php">   <li>Qui sommes-nous ?</li></a>

                            <li>Recrutement</li>

                        </ul>
                    </div>
                    <div class="col-md-6 pl-0">
                        <div class="tittle-footer">
                            Le journal
                        </div>
                        <ul class="category-footer">
                            <a href="./blog"><li>Le journal</li></a>

                        </ul>
                    </div>
                    <div class="col-md-6 pl-0">
                        <div class="tittle-footer">
                            Nos boutiques
                        </div>
                        <ul class="category-footer">
                            <li>Points de vente</li>

                        </ul>  </div>

                </div>
                    <div class="col-md-12 pl-0 endlinks">
                        <a href="{$urls.pages.contact}"> <li>Contactez-nous</li></a> -
                        <a href="./cgv">   <li>CGV</li></a> -
                        <a href="./notre-programme-fidelite"><li>Programme de fidélité</li></a> -
                            <a href="./faqs"><li>FAQ</li></a> -
                        <a href="./espace-presse">   <li>Presse</li></a> -
                            <a href="./devenir-revendeur-ou-distributeur"> <li>Devenir revendeur ou distributeur</li></a>
                    </div>
                                <div class="eco-disclaim col-md-12">
                                    <img class="col-md-2 p-0 pl-1" src="{$urls.img_url}eco.png">
                                    <div class="eco-slaim col-md-9">
                                        Pensez à trier vos emballages ! Pour en savoir plus sur les consignes de tri en France www.consignesdetri.fr


                                    </div>


                                </div>



                </div>


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
