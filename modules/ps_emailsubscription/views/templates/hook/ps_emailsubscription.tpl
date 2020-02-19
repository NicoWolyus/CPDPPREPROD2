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

<div class="email_subscription" id="blockEmailSubscription_{$hookName}">
  <h4>{l s='Newsletter' d='Modules.Emailsubscription.Shop'}</h4>
  {if $msg}
    <p class="notification {if $nw_error}notification-error{else}notification-success{/if}">{$msg}</p>
  {/if}
  <input type="button" style="cursor: pointer;background-image: -webkit-linear-gradient(top,#f8f8f8,#f1f1f1);background-image:    -moz-linear-gradient(top,#f8f8f8,#f1f1f1);background-image:     -ms-linear-gradient(top,#f8f8f8,#f1f1f1);background-image:      -o-linear-gradient(top,#f8f8f8,#f1f1f1);background-image:         linear-gradient(top,#f8f8f8,#f1f1f1);border: 1px solid rgba(0,0,0,0.1);color: #444;-webkit-border-radius: 2px;border-radius: 2px;font-size: 11px;font-weight: bold;text-align: center;white-space: nowrap;height: 27px;line-height: 27px;min-width: 54px;outline: 0;padding: 0 8px;" value="Abonnement" onClick="open('https://qa.adelya.com/Adelyaview/newslettercdp/signup/Newsletter.html?lang=fr&goto=confirm','other','top=100,left=700,width=480,height=800,status=no,scrollbars=yes').focus();" />
</div>
