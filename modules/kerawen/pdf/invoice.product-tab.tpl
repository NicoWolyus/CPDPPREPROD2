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


{assign var="prdWidth" value=66}
{assign var="taxWidth" value=0}
{assign var="taxDetailWidth" value=0}
{assign var="priceVatIncl" value=0}
{assign var="priceTotalVatIncl" value=0}
{assign var="refWidth" value=0}

{*NO TAB BELLOW!!!!*}

{if $kerawen.settings.invoice_tax}

{if $kerawen.settings.invoice_disp_unit_vat}
{assign var="priceVatIncl" value=10}
{assign var="prdWidth" value=$prdWidth-$priceVatIncl}
{/if}
{if $kerawen.settings.invoice_disp_total_vat}
{assign var="priceTotalVatIncl" value=10}
{assign var="prdWidth" value=$prdWidth-$priceTotalVatIncl}
{/if}

{if $kerawen.settings.invoice_disp_tax}
{assign var="taxDetailWidth" value=10}
{assign var="prdWidth" value=$prdWidth-$taxDetailWidth}
{else}
{assign var="taxWidth" value=4}
{assign var="prdWidth" value=$prdWidth-$taxWidth}
{/if}

{/if}

{if $kerawen.settings.invoice_ref_col}
{assign var="refWidth" value=15}
{assign var="prdWidth" value=$prdWidth-$refWidth}
{/if}
{counter assign=cnt start=0 print=false}

<table class="product" width="100%" cellpadding="4" cellspacing="0">

	<thead>
	<tr>
	
		{if $refWidth > 0}
		<th class="product header small" width="{$refWidth}%">{l s='Ref' mod='kerawen'}</th>
		{/if}	
		<th class="product header small" width="{$prdWidth}%">{l s='Product' mod='kerawen'}</th>
		<th class="product header-right small" width="13%">{if $kerawen.settings.invoice_tax}{l s='Unit. VAT excl.' mod='kerawen'}{else}{l s='Unit.' mod='kerawen'}{/if}</th>
		<th class="product header small" width="8%">{l s='Qty' mod='kerawen'}</th>	
		<th class="product header-right small" width="13%">{if $kerawen.settings.invoice_tax}{l s='Total VAT excl.' mod='kerawen'}{else}{l s='Total' mod='kerawen'}{/if}</th>

		{if $taxDetailWidth > 0}
		<th class="product header-right small" width="{$taxDetailWidth}%">{l s='VAT' mod='kerawen'}</th>
		{/if}

		{if $priceVatIncl > 0}
		<th class="product header-right small" width="{$priceVatIncl}%">{l s='Unit. VAT incl.' mod='kerawen'}</th>
		{/if}

		{if $priceTotalVatIncl > 0}
		<th class="product header-right small" width="{$priceVatIncl}%">{l s='Total VAT incl.' mod='kerawen'}</th>
		{/if}

		{if $taxWidth > 0}
		<th class="product header small" width="{$taxWidth}%">T</th>
		{/if}
	</tr>
	</thead>

	<tbody>

	<!-- PRODUCTS KERAWEN -->
	
	{foreach $kerawen.invoice.order as $detail}
		{counter}
		
		{assign var="unit_ti" value=$detail.unit_ti}
		{assign var="unit_te" value=$detail.unit_te}
		{assign var="discount_ti" value=$detail.discount_ti}
		{assign var="discount_te" value=$detail.discount_te}
		{assign var="measureTxt" value=false}
		
		{if ($detail.measure_unit != null || $detail.measure != null && $detail.measure != 1)}
			{assign var="unit_ti" value=$unit_ti*$detail.measure}
			{assign var="unit_te" value=$unit_te*$detail.measure}
			{assign var="discount_ti" value=$discount_ti*$detail.measure}
			{assign var="discount_te" value=$discount_te*$detail.measure}
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
		
		{* IMPORTANT: Format total before unit to keep real number *}
		{assign var="total_ti" value=(($unit_ti*$detail.quantity)|number_format:2)}
		{assign var="unit_ti" value=($unit_ti|number_format:2)}
		
		{* vat_margin *}
		{if ($detail.margin_vat|intval)}
			{if $priceVatIncl > 0  || $priceTotalVatIncl > 0}
				{*assign var="unit" value="-"*}
				{*assign var="total" value="-"*}
			{/if}
		{/if}
		
		
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product left" width="{$refWidth}%">{$detail.ref}</td>
			{/if}

			<td class="product left">
				{$detail.item_name}
				{if $detail.version_name != ''} - {$detail.version_name}{/if}
				{if $measureTxt && $detail.measure != 0}
					<br />{$detail.measure|number_format:$detail.measure_precision}{$detail.measure_unit} x {($unit/$detail.measure)|number_format:2}
				{/if}
				{if $detail.ref && $refWidth == 0}<br/ >{$detail.ref}{/if}
				{if $detail.note && $kerawen.settings.prod_note}<br />{$detail.note|nl2br}{/if}
			</td>

			<td class="product right">
				{$unit}
				{if $ecotax > 0}<br /><small>Ecotax: {$ecotax}</small>{/if}
			</td>

			<td class="product center">{$detail.quantity}</td>

			<td  class="product right">{$total}</td>

			{if $taxDetailWidth > 0}
			<td class="product right" width="{$taxDetailWidth}%">{if ($detail.margin_vat|intval)}-{else}{$detail.tax_rate|number_format:2}%{/if}</td>
			{/if}

			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">{$unit_ti}</td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceTotalVatIncl}%">{$total_ti}</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%">
			{if $kerawen.invoice.tax_amount != 0}
				{$detail.id_tax}
			{/if}
			</td>
			{/if}
		</tr>
		
		{if $discount_ti != 0}
		
			{if $kerawen.settings.invoice_tax}
				{assign var="discount" value=$discount_te}
			{else}
				{assign var="discount" value=$discount_ti}
			{/if}
		
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product right" width="{$refWidth}%"></td>
			{/if}	

			<td class="product left">
				{l s='Discount' mod='kerawen'}
				{if $detail.discount_percent > 0}{$detail.discount_percent|number_format}%{/if}
			</td>
			
			<td class="product right">{(-$discount)|number_format:2}</td>
			
			<td class="product right"></td>
			
			<td class="product right">{(-$discount*$detail.quantity)|number_format:2}</td>
			{if $taxDetailWidth > 0}
			<td class="product right" width="{$taxDetailWidth}%"></td>
			{/if}

			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">{(-$discount_ti)|number_format:2}</td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceTotalVatIncl}%">{(-$discount_ti*$detail.quantity)|number_format:2}</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%"></td>
			{/if}
			
		</tr>
		{/if}
		
	{/foreach}


	{if $kerawen.settings.invoice_disp_shipping}
	{foreach $kerawen.invoice.carrier as $detail}
		{counter}
		
		{if $kerawen.settings.invoice_tax}
			{assign var="unit" value=$detail.unit_te}
		{else}
			{assign var="unit" value=$detail.unit_ti}
		{/if}
		{assign var="total" value=$unit*$detail.quantity}
		
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product right" width="{$refWidth}%"></td>
			{/if}	

			<td class="product left">
				{$detail.item_name} - {$detail.version_name}
			</td>

			<td  class="product right">				
				{$unit|number_format:2}
			</td>

			<td class="product center">{$detail.quantity}</td>

			<td  class="product right">				
				{$total|number_format:2}
			</td>

			{if $taxDetailWidth > 0}
			<td class="product right" width="{$taxDetailWidth}%">{$detail.tax_rate|number_format:2}%</td>
			{/if}

			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">{$detail.unit_ti|number_format:2}</td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceTotalVatIncl}%">{($detail.unit_ti*$detail.quantity)|number_format:2}</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%">
			{if $kerawen.invoice.tax_amount != 0}
				{$detail.id_tax}
			{/if}
			</td>
			{/if}
		</tr>
				
	{/foreach}
	{/if}


{if ($kerawen.invoice.cart_rule|@count) > 0} 

		{counter}
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">
		
			{if $refWidth > 0}
			<td class="product left" width="{$refWidth}%"></td>
			{/if}			
		
			<td class="product left"></td>
			<td  class="product right"></td>
			<td class="product center"></td>
			<td  class="product right">	</td>

			{if $taxDetailWidth > 0}
			<td class="product right" width="{$taxDetailWidth}%"></td>
			{/if}
			
			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%"></td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceTotalVatIncl}%"></td>
			{/if}
	
			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%"></td>
			{/if}
		</tr>

	{foreach $kerawen.invoice.cart_rule as $detail}
		{counter}
		
		{if $kerawen.settings.invoice_tax}
			{assign var="total" value=$detail.total_te}
		{else}
			{assign var="total" value=$detail.total_ti}
		{/if}
		
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product left" width="{$refWidth}%"></td>
			{/if}	

			<td class="product left">
				{$detail.item_name}
				{if $detail.discount_percent > 0}{$detail.discount_percent|number_format}%{/if}
			</td>
						
			<td  class="product right">{$total|number_format:2}</td>

			<td class="product center"></td>

			<td  class="product right">				
				{$total|number_format:2}
			</td>

			{if $taxDetailWidth > 0}
			<td class="product right" width="{$taxDetailWidth}%"></td>
			{/if}

			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">{$detail.total_ti|number_format:2}</td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceTotalVatIncl}%">{$detail.total_ti|number_format:2}</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%"></td>
			{/if}

		</tr>
				
	{/foreach}
{/if}

	</tbody>


	<!-- END PRODUCTS KERAWEN -->


</table>
