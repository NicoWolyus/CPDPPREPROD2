{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="email_subscription col-md-6" id="blockEmailSubscription_{$hookName}">
  <h4>Comme nous, exigez le<br/> meilleur !</h4>
  <span class="notice-subnews">
    Inscrivez-vous pour recevoir nos offres du moment, nos conseils,<br/> nos actualités … La compagnie de Provence vous offre 10% sur</br> votre première commande
  </span>

  {if $msg}
    <p class="notification {if $nw_error}notification-error{else}notification-success{/if}">{$msg}</p>
  {/if}
  <input type="button" id="newsletter"  value="C'est parti" onClick="open('https://qa.adelya.com/Adelyaview/newslettercdp/signup/Newsletter.html?lang={$language.iso_code}&goto=confirm','other','top=100,left=700,width=480,height=800,status=no,scrollbars=yes').focus();" />
<div class="up-be"></div>
</div>

<div class="col-md-6 ask-us">
  <h4>Besoin d’un conseil, ou<br/> simplement une question ?</h4>
  <span class="we-help">
Chez Compagnie de Provence nous pensons qu’il est important<br/> d’être là quand vous en avez besoin, notre équipe située à Aix en<br/> Provence est là pour vous aider. Contactez-nous par mail, téléphone<br/> ou chat messenger.

  </span>
<a href="#">Contactez-nous !</a>
  <div class="up-beg"></div>

</div>