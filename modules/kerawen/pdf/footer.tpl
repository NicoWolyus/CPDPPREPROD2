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

<table style="width: 100%;">
	<tr>
		<td style="text-align: center; font-size: 6pt; color: #444;  width:100%;">
			{if $available_in_your_account}
				{l s='An electronic version of this invoice is available in your account. To access it, log in to our website using your e-mail address and password (which you created when placing your first order).' mod='kerawen'}
				<br />
			{/if}

			{$kerawen.invoice.PS_SHOP_NAME}
			{if !empty($kerawen.invoice.PS_SHOP_ADDR1)} - {$kerawen.invoice.PS_SHOP_ADDR1}{/if}
			{if !empty($kerawen.invoice.PS_SHOP_ADDR2)} - {$kerawen.invoice.PS_SHOP_ADDR2}{/if}
			{if !empty($kerawen.invoice.PS_SHOP_CODE) || !empty($kerawen.invoice.PS_SHOP_CITY)} - {/if}
			{if !empty($kerawen.invoice.PS_SHOP_CODE)}{$kerawen.invoice.PS_SHOP_CODE}{/if}
			{if !empty($kerawen.invoice.PS_SHOP_CITY)} {$kerawen.invoice.PS_SHOP_CITY}{/if}
			{if !empty($kerawen.invoice.PS_SHOP_COUNTRY)} - {$kerawen.invoice.PS_SHOP_COUNTRY}{/if}

			{if !empty($kerawen.invoice.PS_SHOP_PHONE)}
				<br />{l s='For more assistance, contact Support:' mod='kerawen'}
				 {l s='Tel: %s' sprintf=[$kerawen.invoice.PS_SHOP_PHONE|escape:'html':'UTF-8'] mod='kerawen'}
			{/if}
			
			{assign var="sep" value="<br />"}
			{if $kerawen.invoice.KERAWEN_SHOP_SIRET != "NA"}
				{$sep} {l s='SIRET:' mod='kerawen'} {$kerawen.invoice.KERAWEN_SHOP_SIRET}
				{assign var="sep" value=" - "}
			{/if}
			{if $kerawen.invoice.KERAWEN_SHOP_NAF != "NA"}
				{$sep} {l s='NAF:' mod='kerawen'} {$kerawen.invoice.KERAWEN_SHOP_NAF}
				{assign var="sep" value=" - "}
			{/if}
			{if $kerawen.invoice.KERAWEN_SHOP_TVA_INTRA != "NA"}
				{$sep} {l s='VAT INTRA:' mod='kerawen'} {$kerawen.invoice.KERAWEN_SHOP_TVA_INTRA}
			{/if}
			
			{if isset($free_text)}
				<br /> {$free_text|escape:'html':'UTF-8'}
			{/if}
		</td>
	</tr>
</table>

