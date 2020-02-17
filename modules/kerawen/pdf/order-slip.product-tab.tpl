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

{counter assign=cnt start=0 print=false}

<table class="product" width="100%" cellpadding="4" cellspacing="0">

	<thead>
	<tr>
		<th class="product header small" width="62%">{l s='Product' mod='kerawen'}</th>

		<th class="product header-right small" width="13%">{l s='Unit Price' mod='kerawen'}</th>
	
		<th class="product header small" width="8%">{l s='Qty' mod='kerawen'}</th>	
	
		<th class="product header-right small" width="13%">{l s='Total' mod='kerawen'}</th>

		<th class="product header small" width="4%">T</th>
	</tr>
	</thead>

	<tbody>

	{foreach $kerawen.invoice.slip as $detail}
		{counter}

		{assign var="unit_ti" value=$detail.unit_ti}
		{assign var="unit_te" value=$detail.unit_te}
		{assign var="measureTxt" value=false}
		
		{if ($detail.measure_unit != null || $detail.measure != null && $detail.measure != 1)}
			{assign var="unit_ti" value=$unit_ti*$detail.measure}
			{assign var="unit_te" value=$unit_te*$detail.measure}
			{assign var="measureTxt" value=true}
		{/if}
		
		{if $kerawen.settings.invoice_tax}
			{assign var="unit" value=($unit_te|number_format:2)}
			{assign var="ecotax" value=($detail.ecotax_te|number_format:2)}
			{assign var="total" value=(($unit_te*$detail.quantity)|number_format:2)}
		{else}
			{assign var="unit" value=($unit_ti|number_format:2)}
			{assign var="ecotax" value=($detail.ecotax_ti|number_format:2)}
			{assign var="total" value=(($unit_ti*$detail.quantity)|number_format:2)}
		{/if}
		
		
		{assign var="unit_ti" value=($unit_ti|number_format:2)}
		{assign var="total_ti" value=(($unit_ti*$detail.quantity)|number_format:2)}
		
		{* vat_margin *}
		{if ($detail.margin_vat|intval)}
			{if $priceVatIncl > 0  || $priceTotalVatIncl > 0}
				{assign var="unit" value="-"}
				{assign var="total" value="-"}
			{/if}
		{/if}

		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			<td class="product left">
				{$detail.item_name}
				{if $measureTxt && $detail.measure != 0}
					<br />{$detail.measure|number_format:$detail.measure_precision}{$detail.measure_unit} x {($unit/$detail.measure)|number_format:2}
				{/if}
				{if $detail.ref}<br/ >{$detail.ref}{/if}
				{if $detail.note && $kerawen.settings.prod_note}<br />{$detail.note|nl2br}{/if}
			</td>

			<td class="product right">
				{$unit}
				{if $ecotax > 0}<br /><small>Ecotax: {$ecotax}</small>{/if}
			</td>

			<td class="product center">{$detail.quantity}</td>

			<td  class="product right">{$total}</td>

			<td class="product center">
			{if $kerawen.invoice.tax_amount != 0}
				{$detail.id_tax}
			{/if}
			</td>

		</tr>
				
	{/foreach}

	</tbody>

</table>
