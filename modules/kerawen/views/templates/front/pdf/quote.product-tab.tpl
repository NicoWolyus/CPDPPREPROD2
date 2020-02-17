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


{assign var="prdWidth" value=66}
{assign var="taxWidth" value=0}
{assign var="taxDetailWidth" value=0}
{assign var="priceVatIncl" value=0}
{assign var="priceTotalVatIncl" value=0}
{assign var="refWidth" value=0}

{if $quote_tax}

{if $quote_disp_unit_vat}
{assign var="priceVatIncl" value=10}
{assign var="prdWidth" value=$prdWidth-$priceVatIncl}
{/if}
{if $quote_disp_total_vat}
{assign var="priceTotalVatIncl" value=10}
{assign var="prdWidth" value=$prdWidth-$priceTotalVatIncl}
{/if}

{if $quote_disp_tax}
{assign var="taxDetailWidth" value=10}
{assign var="prdWidth" value=$prdWidth-$taxDetailWidth}
{else}
{assign var="taxWidth" value=4}
{assign var="prdWidth" value=$prdWidth-$taxWidth}
{/if}

{/if}

{if $quote_ref_col}
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
		<th class="product header-right small" width="13%">{if $quote_tax}{l s='Unit. VAT excl.' mod='kerawen'}{else}{l s='Unit.' mod='kerawen'}{/if}</th>
		<th class="product header small" width="8%">{l s='Qty' mod='kerawen'}</th>	
		<th class="product header-right small" width="13%">{if $quote_tax}{l s='Total VAT excl.' mod='kerawen'}{else}{l s='Total' mod='kerawen'}{/if}</th>

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

	<!-- PRODUCTS -->
	{foreach $order_details as $order_detail}
		{counter}
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product left" width="{$refWidth}%">
				{$order_detail.product_reference}
			</td>
			{/if}
			
			<td class="product left" width="{$prdWidth}%">
				{if $display_product_images && isset($order_detail.image_tag)}
					<table width="100%">
						<tr>
							<td width="15%">
								{if isset($order_detail.image_tag)}
									{$order_detail.image_tag}
								{/if}
							</td>
							<td width="5%">&nbsp;</td>
							<td width="80%">
								{$order_detail.product_name}
								{if !$quote_ref_col}<br>{$order_detail.product_reference}{/if}
								{if $order_detail.note != ''}<br />{$order_detail.note}{/if}
							</td>
						</tr>
					</table>
				{else}
					{$order_detail.product_name}
					{if !$quote_ref_col}<br>{$order_detail.product_reference}{/if}
					{if $order_detail.note != ''}<br />{$order_detail.note}{/if}
				{/if}
			</td>

			<td class="product right" width="13%">
				{$order_detail.unit_init_tax_excl|number_format:2}
				
				{if $order_detail.ecotax_tax_excl > 0}
					<br>
					<small>{{$order_detail.ecotax_tax_excl|number_format:2}|string_format:{l s='ecotax: %s' mod='kerawen'}}</small>
				{/if}
			</td>
			<td class="product center" width="8%">
				{$order_detail.product_quantity}
			</td>
			<td  class="product right" width="13%">
				{$order_detail.price_init_tax_excl|number_format:2}
			</td>
			
			{if $taxDetailWidth > 0}
			<td class="product center" width="{$taxDetailWidth}%">				
				{if ($order_detail.vat_margin|intval)}-{else}{$order_detail.order_detail_tax_label|number_format:2}%{/if}
			</td>			
			{/if}
			
			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">{$order_detail.unit_init_tax_incl|number_format:2}</td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">{$order_detail.price_init_tax_incl|number_format:2}</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%">{$order_detail.taxKey}</td>
			{/if}

			
		</tr>

	{if $order_detail.discount}
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product left" width="{$refWidth}%"></td>
			{/if}

			<td class="product left" width="{$prdWidth}%">	
				{l s='Discount' mod='kerawen'} {if $order_detail.discount_type == 'amount'} {else} {$order_detail.discount|number_format:2} % {/if}		
			</td>
			
			<td class="product right" width="13%"></td>

			<td class="product center" width="8%"></td>

			<td  class="product right" width="13%">
				- {$order_detail.price_discount_tax_excl|number_format:2}
			</td>

			{if $taxDetailWidth > 0}
			<td class="product center" width="{$taxDetailWidth}%"></td>		
			{/if}
			
			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%"></td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">
				- {$order_detail.price_discount_tax_incl|number_format:2}
			</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%"></td>
			{/if}
		</tr>
	{/if}


	{/foreach}
	<!-- END PRODUCTS -->

	<!-- SHIPPING -->
	{foreach $tax_breakdowns.shipping_tax as $shipping_tax}
		{counter}
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product right" width="{$refWidth}%"></td>
			{/if}	

			<td class="product left" width="{$prdWidth}%">
				{$shipping_tax.name}	
			</td>
			
			<td class="product right" width="13%"></td>

			<td class="product center" width="8%"></td>

			<td  class="product right" width="13%">
				{$shipping_tax.total_tax_excl|number_format:2}
			</td>
			
			{if $taxDetailWidth > 0}
			<td class="product center" width="{$taxDetailWidth}%">{$shipping_tax.rate|number_format:2}%</td>			
			{/if}
			
			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%"></td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">
				{$shipping_tax.total_tax_incl|number_format:2}
			</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%">{$shipping_tax.taxKey}</td>		
			{/if}
		</tr>
	{/foreach}
	<!-- END SHIPPING -->


	<!-- CART RULES -->

	{foreach from=$cart_rules item=cart_rule name="cart_rules_loop"}
		{counter}
		{if $smarty.foreach.cart_rules_loop.first}
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">
			
			{if $refWidth > 0}
			<td class="product left" width="{$refWidth}%"></td>
			{/if}			
		
			<td class="product left" width="{$prdWidth}%"></td>
			<td  class="product right" width="13%"></td>
			<td class="product center" width="8%"></td>
			<td  class="product right" width="13%">	</td>

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
		{/if}
		<tr class="product {if $cnt%2}color_line_even{else}color_line_odd{/if}">

			{if $refWidth > 0}
			<td class="product right" width="{$refWidth}%"></td>
			{/if}	

			<td class="product left" width="{$prdWidth}%">
				{$cart_rule.name}	
			</td>
			
			<td class="product right" width="13%"></td>

			<td class="product center" width="8%"></td>

			<td  class="product right" width="13%">
				- {$cart_rule.value_tax_excl|number_format:2}
			</td>
			
			{if $taxDetailWidth > 0}
			<td class="product center" width="{$taxDetailWidth}%"></td>			
			{/if}
			
			{if $priceVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%"></td>
			{/if}

			{if $priceTotalVatIncl > 0}
			<td class="product right" width="{$priceVatIncl}%">
				- {$cart_rule.value_tax_incl|number_format:2}
			</td>
			{/if}

			{if $taxWidth > 0}
			<td class="product center" width="{$taxWidth}%"></td>		
			{/if}

		</tr>
	{/foreach}

	</tbody>

</table>
