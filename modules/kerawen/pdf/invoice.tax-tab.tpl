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

{if $tax_exempt}{/if}
{if $kerawen.settings.invoice_tax}
<!-- TAX DETAILS KERAWEN -->

	<table id="tax-tab" width="100%">
		<thead>
			<tr>
				<th class="header small">{if !$kerawen.settings.invoice_disp_tax}{l s='Code' mod='kerawen'}{/if}</th>
				<th class="header-right small">{l s='Rate' mod='kerawen'}</th>
				<th class="header header-right small">{l s='Tax excl.' mod='kerawen'}</th>
				<th class="header-right small">{l s='VAT' mod='kerawen'}</th>
				<th class="header-right small">{l s='Tax incl.' mod='kerawen'}</th>
			</tr>
		</thead>
		<tbody>
{if $kerawen.invoice.tax_amount != 0}
	{foreach $kerawen.invoice.taxes as $taxe}
			<tr>
				<td>{if !$kerawen.settings.invoice_disp_tax}({$taxe.id_tax}){/if}</td>
				<td class="right">{if ($taxe.tax_rate|is_numeric)}{$taxe.tax_rate|number_format:2}{else}{$taxe.tax_rate}{/if}</td>
				<td class="right">{$taxe.total_te|number_format:2}</td>
				<td class="right">{if ($taxe.tax_amount|is_numeric)}{$taxe.tax_amount|number_format:2}{else}{$taxe.tax_amount}{/if}</td>
				<td class="right">{$taxe.total_ti|number_format:2}</td>
			</tr>
	{/foreach}
{/if}
			<tr>
				<td>Total</td>
				<td></td>
				<td class="right">{$kerawen.invoice.total_te|number_format:2}</td>
				<td class="right">{$kerawen.invoice.tax_amount|number_format:2}</td>
				<td class="right">{$kerawen.invoice.total_ti|number_format:2}</td>
			</tr>

		</tbody>
	</table>
<!-- / TAX DETAILS KERAWEN -->	
{/if}