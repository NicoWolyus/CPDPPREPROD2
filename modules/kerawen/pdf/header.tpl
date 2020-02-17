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


<table style="width: 100%">
<tr>
	<td style="width: 50%">
		{if $logo_path}
			<img src="{$logo_path}" style="width:{$width_logo}px; height:{$height_logo}px;" />
		{/if}
	</td>
	<td style="width: 50%; text-align: right;">
		<table style="width: 100%">
{if $kerawen.settings.invoice_disp_barcode && isset($kerawen.invoice.reference)}
	{assign var=style value=['position'=>'R', 'border'=>false, 'padding'=>0, 'fgcolor'=>[0,0,0], 'bgcolor'=>[255,255,255], 'text'=>false, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4]}
	{assign var=barcodeParams value=[$kerawen.invoice.reference, 'C39', '', '', 70, 11, 0.4, $style, 'N']}
			<tr>
				<td>
					<TCPDF method="write1DBarcode" params="{($barcodeParams|serialize|urlencode)}" />
				</td>
			</tr>
{/if}	
			<tr>
				<td style="font-weight: bold; font-size: 10pt; color: #444; width: 100%;">{if isset($header)}{$header|escape:'html':'UTF-8'|upper}{/if}</td>
			</tr>
			<tr>
				<td style="font-size: 10pt; color: #9E9F9E">{if $kerawen.settings.header_date}{dateFormat date=$kerawen.invoice.invoice_date full=1}{else}{dateFormat date=$kerawen.invoice.receipt_date full=1}{/if}</td>
			</tr>
			<tr>
				<td style="font-size: 10pt; color: #9E9F9E">{$kerawen.invoice.number}</td>
			</tr>
		</table>
	</td>
</tr>
</table>
