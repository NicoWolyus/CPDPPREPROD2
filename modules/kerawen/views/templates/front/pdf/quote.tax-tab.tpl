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

{if $quote_tax}

{if ($tax_breakdowns.product_tax|count) > 0}
	<table id="tax-tab" width="92%">
		<thead>
			<tr>
				<th class="header small">{if !$quote_disp_tax}{l s='Code' mod='kerawen'}{/if}</th>
				<th class="header-right small">{l s='Rate' mod='kerawen'}</th>
				<th class="header header-right small">{l s='Tax excl.' mod='kerawen'}</th>
				<th class="header-right small">{l s='VAT' mod='kerawen'}</th>
				<th class="header-right small">{l s='Tax incl.' mod='kerawen'}</th>
			</tr>
		</thead>
		<tbody>
{foreach $tax_breakdowns.product_tax as $product_tax}
<tr>
	<td>{if !$quote_disp_tax}({$product_tax.taxKey}){/if}</td>
	<td class="right">{if $product_tax.vat_margin}-{else}{$product_tax.rate|number_format:2}{/if}</td>
	<td class="right">{$product_tax.total_tax_excl|number_format:2}</td>
	<td class="right">{if $product_tax.vat_margin}-{else}{$product_tax.total_tax|number_format:2}{/if}</td>
	<td class="right">{$product_tax.total_tax_incl|number_format:2}</td>
</tr>
{/foreach}
<tr>
	<td>Total</td>
	<td class="right"></td>
	<td class="right">{$footer.total_paid_tax_excl|number_format:2}</td>
	<td class="right">{if $full_vat_margin}-{else}{$footer.total_taxes|number_format:2}{/if}</td>
	<td class="right">{$footer.total_paid_tax_incl|number_format:2}</td>
</tr>
		</tbody>
	</table>
	<br><br>
{/if}


{/if}


{if $order->employee}
{l s='Your contact' mod='kerawen'} {$order->employee}
{/if}

<p>{$thanks_text|escape:'html':'UTF-8'|nl2br}</p>
