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

{assign var="shippingTax" value=0}
{assign var="shippingTaxTotal" value=0}
{assign var="shippingTotal" value=0}

<table id="total-tab" width="100%">

	{if !$kerawen.settings.invoice_disp_shipping}
	{foreach $kerawen.invoice.carrier as $detail}
		
	{assign var="shippingTax" value=$detail.total_ti - $detail.total_te}
    {assign var="shippingTaxTotal" value=$shippingTaxTotal + $shippingTax}
    	
	{if $kerawen.settings.invoice_tax}
		{assign var="totaltax" value=$detail.total_te}
	{else}
		{assign var="totaltax" value=$detail.total_ti}
	{/if}
	
	{assign var="shippingTotal" value=$shippingTotal + $totaltax}	
		
	<tr class="bold">
		<td class="grey">
			{l s='Shipping' mod='kerawen'}
		</td>
		<td class="white">
			{displayPrice currency=$order->id_currency price=$totaltax}
		</td>
	</tr>
	
	{if $kerawen.settings.invoice_tax && $shippingTax > 0}
	<tr class="bold">
		<td class="grey">
			{l s='Shipping Tax' mod='kerawen'}
		</td>
		<td class="white">
			{displayPrice currency=$order->id_currency price=$shippingTax}
		</td>
	</tr>
	{/if}
			
	{/foreach}
	{/if}


	{if $kerawen.settings.invoice_tax}
		{assign var="totalprod" value=($kerawen.invoice.total_te - $shippingTotal)}
	{else}
		{assign var="totalprod" value=$kerawen.invoice.total_ti - $shippingTotal}
	{/if}


	{if !$kerawen.settings.invoice_disp_shipping || ($kerawen.settings.invoice_disp_shipping && $kerawen.settings.invoice_tax)}
	<tr class="bold">
		<td class="grey">
			{l s='Total product' mod='kerawen'}
		</td>
		<td class="white">
			{displayPrice currency=$order->id_currency price=$totalprod}
		</td>
	</tr>
	{/if}

	{if $kerawen.settings.invoice_tax && ($kerawen.invoice.tax_amount - $shippingTaxTotal) > 0}
	<tr class="bold">
		<td class="grey">
			{l s='Total product Tax' mod='kerawen'}
		</td>
		<td class="white">
			{displayPrice currency=$order->id_currency price=($kerawen.invoice.tax_amount - $shippingTaxTotal)}
		</td>
	</tr>
	{/if}

	<tr class="bold big">
		<td class="grey">
			{l s='Total' mod='kerawen'}
		</td>
		<td class="white">
			{displayPrice currency=$order->id_currency price=$kerawen.invoice.total_ti}
		</td>
	</tr>
</table>
{if ($kerawen.invoice.remain|number_format:2) > 0.01}
<br/>
<table id="total-tab" width="100%">
	<tr class="bold">
		<td class="white">
			{l s='Left to pay' mod='kerawen'}
		</td>
		<td class="white">			
			{displayPrice currency=$order->id_currency price=$kerawen.invoice.remain}<br />
			{$kerawen.invoice.payment}
		</td>
	</tr>
</table>
{/if}
