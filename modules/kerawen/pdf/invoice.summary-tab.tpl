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
<table id="summary-tab" width="100%">
	<tr>
		<th class="header small" valign="middle">{l s='Invoice Number' mod='kerawen'}</th>
		<th class="header small" valign="middle">{l s='Invoice Date' mod='kerawen'}</th>
		<th class="header small" valign="middle">{l s='Order Reference' mod='kerawen'}</th>
		<th class="header small" valign="middle">{l s='Order date' mod='kerawen'}</th>
		{if $kerawen.settings.invoice_num_order}<th class="header small" valign="middle">{l s='Order num.' mod='kerawen'}</th>{/if}
		{if $kerawen.settings.invoice_num_cart}<th class="header small" valign="middle">{l s='Cart num.' mod='kerawen'}</th>{/if}
	</tr>
	<tr>
		<td class="center small white">{$kerawen.invoice.number}</td>
		<td class="center small white">{dateFormat date=$kerawen.invoice.invoice_date full=1}</td>
		<td class="center small white">{$kerawen.invoice.reference}</td>
		<td class="center small white">{dateFormat date=$kerawen.invoice.receipt_date full=1}</td>
		{if $kerawen.settings.invoice_num_order}<td class="center small white">{$kerawen.invoice.ps_order}</td>{/if}
		{if $kerawen.settings.invoice_num_cart}<td class="center small white">{$kerawen.invoice.id_cart}</td>{/if}
	</tr>
</table>
