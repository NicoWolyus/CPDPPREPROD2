{*
* 2007-2015 PrestaShop
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
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}


{if !$kerawen.settings.invoice_disp_shipping}


<table id="payment-tab" width="100%">
	<tr>
		<td class="payment left small grey bold" width="44%">{l s='Carrier' mod='kerawen'}</td>
		<td class="payment left white" width="56%">
			<table width="100%" border="0">
{foreach $kerawen.invoice.carrier as $detail}
	<tr>
		<td class="shipping left small white">{$detail.version_name}</td>
		<td class="shipping right small white">{$detail.total_ti|number_format:2}</td>
	</tr>
{/foreach}
			</table>
		</td>
	</tr>
</table>

{/if}