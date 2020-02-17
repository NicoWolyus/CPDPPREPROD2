{**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<div id="js-product-list-top" class="row products-selection">
  <div class="col-md-6 hidden-sm-down total-products">
    {if $listing.pagination.total_items > 1}
      <p>{l s='There are %product_count% products.' d='Shop.Theme.Catalog' sprintf=['%product_count%' => $listing.pagination.total_items]}</p>
    {elseif $listing.pagination.total_items > 0}
      <p>{l s='There is 1 product.' d='Shop.Theme.Catalog'}</p>
    {/if}
  </div>
  <div class="col-md-6">
    <div class="row sort-by-row">

      {block name='sort_by'}
        {include file='catalog/_partials/sort-orders.tpl' sort_orders=$listing.sort_orders}
      {/block}

      {if !empty($listing.rendered_facets)}
        <div class="col-sm-3 col-xs-4 hidden-md-up filter-button">
          <button id="search_filter_toggler" class="btn btn-secondary">
            {l s='Filter' d='Shop.Theme.Actions'}
          </button>
        </div>
      {/if}
    </div>
  </div>
  <div class="col-sm-12 hidden-md-up text-sm-center showing">
    {l s='Showing %from%-%to% of %total% item(s)' d='Shop.Theme.Catalog' sprintf=[
    '%from%' => $listing.pagination.items_shown_from ,
    '%to%' => $listing.pagination.items_shown_to,
    '%total%' => $listing.pagination.total_items
    ]}
  </div>
</div>



<!--
	name="{l s='BubbleGum Category10' mod='m4pdf'}"
	data="product"
-->
{assign var='imageType' value='medium_default'}		 {* choose image type (Preferences > Images) *}
{assign var='sheetWidth' value='210'}									{* in mm *}
{assign var='sheetHeight' value='297'}								{* in mm *}
{assign var='leftRightMargin' value='15'}							{* in mm *}
{assign var='topBottomMargin' value='30'}							{* in mm *}
{assign var='labelsHorizontal' value='4'}
{assign var='labelsVertical' value='4'}
{assign var='relativeProductImageHeigh' value='1'}	{* between 0 and 1 *}
{assign var='productDescriptionFontSize' value='18'}		{* in px *}
{assign var='categoryHeaderFontSize' value='16'}			{* in px *}
{assign var='backgroundImageName' value=''}						{* save the image to /m4pdf/tpl directory *}

<head>
    {literal}
    <style>
        @page {
            sheet-size: A4;
            margin-header: 6mm;
            margin-footer: 15mm;
            margin-top: 30mm;
            margin-bottom: 30mm;
        {/literal}{if $backgroundImageName}
            background-image: url('tpl/{$backgroundImageName}');
            background-image-opacity: 0.1;
            background-image-resize: 3;
        {/if}{literal}
        }
        @page header {
            odd-header-name: html_header1;
            even-header-name: html_header1;
        }
        @page toc {
            sheet-size: A4;
            margin-top: 40mm;
            margin-bottom: 15mm;
            margin-left: 2mm;
            margin-right: 2mm;
            margin-header: 2mm;
            margin-footer: 2mm;
        }
        body {
            font-size: 15px;
            font-family: sans;
            font-weight: normal;
            color: black;
            background-color: white;
        }
        h1, h2, h3, h4, h5, h6, p, div, table, th, td {
            vertical-align: top;
        }
        h1 {
            font-size: 26px;
            text-align: center;
            clear: both;
            margin-bottom: 4mm;
        }
        .header {
            width: 100%;
            vertical-align: top;
            font-family: sans;
            font-size: 11px;
            color: #656565;
        }
        .footer {
            width: 100%;
            vertical-align: top;
            font-family: sans;
            font-size: 11px;
            color: #656565;
        }
        .catalog {
            font-family: sans;
            font-size: 34px;
            font-weight: bold;
            color: #FFBA69;
            text-align: right;
            padding-top: 40mm;
            padding-bottom: 60mm;
        }
        .name {
            font-family: sans;
            font-size: 16px;
            font-weight: bold;
            background-color: #CED5DA;
            text-align: left;
            padding: 0.7mm;
        }
        .photo {
            margin: 0 auto;
        }
        .left {float: left;}
        .right {float: right;}
        .align-left {text-align: left;}
        .align-center {text-align: center;}
        .align-right {text-align: right;}
        .clear {clear: both;}
        .clear-left {clear: left;}
        .clear-right {clear: right;}
        .zero {margin: 0; padding: 0;}
        .normal {font-weight: auto;}
        .bold {font-weight: bold;}
        .italic {font-style: italic;}
        .smaller {font-size: 80%;}
        .bigger {font-size: 120%;}
        .bigger-more {font-size: 150%;}
        .w5 {width: 5%;}
        .w10 {width: 10%;}
        .w20 {width: 20%;}
        .w25 {width: 25%;}
        .w30 {width: 30%;}
        .w40 {width: 40%;}
        .w50 {width: 50%;}
        .w60 {width: 60%;}
        .w70 {width: 70%;}
        .w75 {width: 75%;}
        .w80 {width: 80%;}
        .w90 {width: 90%;}
        .w100 {width: 100%;}
        .w_img {width: 16mm;}
        .w_text {width: 30mm;}
        .h50 {height: 50%;}
        .h100 {height: 100%;}
        .logo{width:5%;}
        table.border {
            border-collapse: collapse;
        }
        table.border th, table.border td {
            border: 0.1mm solid black;
            font-size: 80%;
            text-align: center;
            padding: 1mm;
        }
        table.border th {
            height: 7.5mm;
        }
        .barcode {
            margin: 0;
            color: black;
        }
        .cell {
            position: absolute;
            margin: 1mm;
            vertical-align: top;
            font-size: {/literal}{$productDescriptionFontSize}{literal}px;
            font-family: sans;
            /* uncomment for displaying label borders */
            /* border: 0.1mm solid black; */
        }
        .category_header {
            position: absolute;
            overflow: hidden;
            padding-top: 15mm;
            /* uncomment for displaying label borders */
            /* border: 0.1mm solid black; */
        }
        .category_header h1 {
            font-size: {/literal}{$categoryHeaderFontSize}{literal}px;
            text-align: center;
            clear: both;
        }

        /* For Table of Contents */
        div.mpdf_toc {
            font-size: 11px;
        }
        a.mpdf_toc_a {
            text-decoration: none;
            color: black;
        }
        div.mpdf_toc_level_0 {		/* Whole line level 0 */
            line-height: 1.5;
            margin-left: 0;
            padding-right: 2em;	/* should match e.g <dottab outdent="2em" /> 0 is default */
        }
        span.mpdf_toc_t_level_0 {	/* Title level 0 - may be inside <a> */
            font-weight: bold;
        }
        span.mpdf_toc_p_level_0 {	/* Page no. level 0 - may be inside <a> */
        }
        div.mpdf_toc_level_1 {		/* Whole line level 1 */
            margin-left: 2em;
            text-indent: -2em;
            padding-right: 2em;	/* should match <dottab outdent="2em" /> 2em is default */
        }
        span.mpdf_toc_t_level_1 {	/* Title level 1 */
            font-style: italic;
            font-weight: bold;
        }
        span.mpdf_toc_p_level_1  {	/* Page no. level 1 - may be inside <a> */
        }
        div.mpdf_toc_level_2 {		/* Whole line level 2 */
            margin-left: 4em;
            text-indent: -2em;
            padding-right: 2em;	/* should match <dottab outdent="2em" /> 2em is default */
        }
        span.mpdf_toc_t_level_2 {	/* Title level 2 */
        }
        span.mpdf_toc_p_level_2 {	/* Page no. level 2 - may be inside <a> */
        }
    </style>
    {/literal}
</head>
<body>

{if $conf.cloud}
    {assign var="force_protocol" value="http"}
{else}
    {assign var="force_protocol" value=""}
{/if}

<!--mpdf
	<htmlpageheader name="header1">
		<div class="header zero">
			<img src="{$conf.logo} class="logo" />
		</div>
	</htmlpageheader>

	<htmlpagefooter name="footer1">
		<div class="left w80">{$conf.shop_name}{if $conf.shop_addr1} | {$conf.shop_addr1}{/if}{if $conf.shop_city} | {$conf.shop_city}{/if}{if $conf.shop_www} | {$conf.shop_www}{/if}</div>
		<div class="right align-right w10">{l s='Page' mod='m4pdf'} {ldelim}PAGENO{rdelim}</div>
	</htmlpagefooter>

	<htmlpagefooter name="footerToc">
		<div style="color: white">.</div>
	</htmlpagefooter>
mpdf-->

<img src="file:///{$conf.logo}" />
<div class="right align-right">
    <h1 class="catalog">{l s='Catalogue B2B' mod='m4pdf'}</h1>
    <h3 class="bigger">{$conf.shop_name}</h3>
    <p>{$conf.shop_addr1}<br />
        {$conf.shop_city}</p>
    <p>{$conf.shop_www}<br />
        {$conf.shop_email}<br />
        {$conf.shop_phone}</p>
</div>

<pagebreak page-selector="header" resetpagenum="1" />

<tocpagebreak
        toc-page-selector="toc" font="sans" font-size="10" indent="5"
        paging="on" links="on" suppress="off" pagenumstyle="1"
        toc-preHTML="&lt;h2&gt;{l s='Contents' mod='m4pdf'}&lt;/h2&gt;"
/>

<!--mpdf
	<sethtmlpagefooter name="footer1" page="ALL" value="on" show-this-page="1" />
mpdf-->


{math equation="(sw - 2 * lrm) / lh" sw=$sheetWidth lrm=$leftRightMargin lh=$labelsHorizontal assign='cellWidth'}
{math equation="(sh - 2 * tbm) / lv" sh=$sheetHeight tbm=$topBottomMargin lv=$labelsVertical assign='cellHeight'}
{math equation="(ch * rih)" ch=$cellHeight rih=$relativeProductImageHeigh assign='imageHeight'}

{assign var="parent" value="-1"}
{assign var="index" value="-1"}

{foreach from=$product|@sortby:"parents_categories_reversed,name" item=r_product name=r_product}
    {assign var="index" value="`$index+1`"}
    {math equation="floor(i/lh) % lv" i=$index lh=$labelsHorizontal lv=$labelsVertical assign='row'}
    {assign var="column" value="`$index%$labelsHorizontal`"}
    {assign var="columnRight" value="`$labelsHorizontal-$column-1`"}
    {assign var="rowBottom" value="`$labelsVertical-$row-1`"}
    {math equation="lrm + c * cw" lrm=$leftRightMargin c=$column cw=$cellWidth assign='left'}
    {math equation="lrm + cr * cw" lrm=$leftRightMargin cr=$columnRight cw=$cellWidth assign='right'}
    {math equation="tbm + r * ch" tbm=$topBottomMargin r=$row ch=$cellHeight assign='top'}
    {math equation="tbm + rb * ch" tbm=$topBottomMargin rb=$rowBottom ch=$cellHeight assign='bottom'}
    {if $index != 0 && $index%($labelsHorizontal*$labelsVertical) == 0}
        <pagebreak />
        <sethtmlpagefooter name="footer1" page="ALL" value="on" show-this-page="1" />
    {/if}

    {if $parent != $r_product.parents_categories_reversed}
        {assign var="parent" value="`$r_product.parents_categories_reversed`"}
        {eval assign="parentCleaned" var=$parent|replace:'Home | ':''|replace:'Home':''|replace:'|':'>>'}

        <!-- blank cells -->
        {math equation="(lh - c) % lh" c=$column lh=$labelsHorizontal assign='remainingCells'}
        {if $labelsVertical - $row == 2}{assign var="remainingCells" value="`$remainingCells+$labelsHorizontal`"}{/if} {* extra line to not have the header as an orphan *}
        {section name=foo start=0 loop=$remainingCells}
            <div class="cell" style="left: {$left}mm; right: {$right}mm; top: {$top}mm; bottom: {$bottom}mm;">&nbsp;</div>
            {assign var="index" value="`$index+1`"}
        {/section}
        {math equation="floor(i/lh) % lv" i=$index lh=$labelsHorizontal lv=$labelsVertical assign='row'}
        {assign var="column" value="`$index%$labelsHorizontal`"}
        {assign var="columnRight" value="`$labelsHorizontal-$column-1`"}
        {assign var="rowBottom" value="`$labelsVertical-$row-1`"}
        {math equation="lrm + c * cw" lrm=$leftRightMargin c=$column cw=$cellWidth assign='left'}
        {math equation="lrm + cr * cw" lrm=$leftRightMargin cr=$columnRight cw=$cellWidth assign='right'}
        {math equation="tbm + r * ch" tbm=$topBottomMargin r=$row ch=$cellHeight assign='top'}
        {math equation="tbm + rb * ch" tbm=$topBottomMargin rb=$rowBottom ch=$cellHeight assign='bottom'}
        {if $index != 0 && $index%($labelsHorizontal*$labelsVertical) == 0}
            <pagebreak />
            <sethtmlpagefooter name="footer1" page="ALL" value="on" show-this-page="1" />
        {/if}

        <!-- category header cells -->
        <div class="category_header" style="left: {$leftRightMargin}mm; right: {$leftRightMargin}mm; top: {$top}mm; bottom: {$bottom}mm;">
            <tocentry content="{$parentCleaned|escape:'htmlall':'UTF-8'}" level="0" />
            <h1 class="align-center">{$parentCleaned|escape:'htmlall':'UTF-8'}</h1>
        </div>
        {assign var="index" value="`$index+$labelsHorizontal`"}
        {math equation="floor(i/lh) % lv" i=$index lh=$labelsHorizontal lv=$labelsVertical assign='row'}
        {assign var="column" value="`$index%$labelsHorizontal`"}
        {assign var="columnRight" value="`$labelsHorizontal-$column-1`"}
        {assign var="rowBottom" value="`$labelsVertical-$row-1`"}
        {math equation="lrm + c * cw" lrm=$leftRightMargin c=$column cw=$cellWidth assign='left'}
        {math equation="lrm + cr * cw" lrm=$leftRightMargin cr=$columnRight cw=$cellWidth assign='right'}
        {math equation="tbm + r * ch" tbm=$topBottomMargin r=$row ch=$cellHeight assign='top'}
        {math equation="tbm + rb * ch" tbm=$topBottomMargin rb=$rowBottom ch=$cellHeight assign='bottom'}
        {if $index != 0 && $index%($labelsHorizontal*$labelsVertical) == 0}
            <pagebreak />
            <sethtmlpagefooter name="footer1" page="ALL" value="on" show-this-page="1" />
        {/if}
    {/if}

    <!-- product cell -->
    <div class="cell" style="left: {$left}mm; right: {$right}mm; top: {$top}mm; bottom: {$bottom}mm;">
        <div class="w100 zero clear align-center" style="padding-bottom: 2mm; height: {$imageHeight}mm;">
            {assign var="id_product_id_image" value="`$r_product.id_product`-`$r_product.image.id_image`"}
            <img class="photo" src="{if $r_product.image.id_image}{productCoverImageLink product_id=$r_product.id_product force_protocol=$force_protocol type=$imageType}{else}{$conf.img_prod_dir}{$conf.pdf_iso_lang}.jpg{/if}" style="height: {$imageHeight}mm" />
        </div>
        <div class="w100 zero clear align-center bold">
            {$r_product.name|escape:'htmlall':'UTF-8'|truncate:32}
            <tocentry content="{$r_product.name|escape:'htmlall':'UTF-8'}" level="1" />
        </div>
        <div class="w100 zero clear align-center">
            <span class="normal">{l s='Reference:' mod='m4pdf'}</span> {if $r_product.reference != ''}{$r_product.reference|escape:'htmlall':'UTF-8'}{else}-{/if}
        </div>
        <div class="w100 zero clear align-center">
            {assign var="tax" value=$r_product.default_country_tax.rate/100+1}
            {assign var="price_wt" value=$r_product.price*$tax}
            <span class="normal">{l s='Price:' mod='m4pdf'}</span> {displayPrice price=$r_product.price currency=$conf.currency_current}

            {assign var="changed_product_attribute" value=0}
            {if $r_product.product_attribute}
                <br />
                <table autosize="1" class="w100" style="page-break-inside:avoid">
                    {foreach from=$r_product.product_attribute item=product_attribute name=product_attribute}
                        {* end of the row - other than last iterations, use $saved *}
                        {if $changed_product_attribute != $product_attribute.id_product_attribute && !$smarty.foreach.product_attribute.first}
                            {if $saved.reference}
                                <span>{l s='Reference:' mod='m4pdf'} {$saved.reference}</span>,
                            {/if}
                            <span><strong> {displayPrice price=$r_product.price+$saved.price currency=$conf.currency_current}</strong></span>
                            </td>
                            </tr>
                        {/if}

                        {* start of the row *}
                        {if $changed_product_attribute != $product_attribute.id_product_attribute}
                            <tr>
                            <td style="padding-top: 1mm">
                        {/if}

                        {if $changed_product_attribute != $product_attribute.id_product_attribute}
                            {assign var="changed_product_attribute" value=$product_attribute.id_product_attribute}
                        {/if}

                        {* attributes and groups *}
                        <span>{$product_attribute.attribute_group.name} : {$product_attribute.attribute.name}</span><br/>,
                        {assign var="saved" value=$product_attribute}

                        {* end of the row - last iteration *}
                        {if $smarty.foreach.product_attribute.last}
                            {if $product_attribute.reference}
                                <span>{l s='Reference:' mod='m4pdf'} {$product_attribute.reference}</span>,
                            {/if}
                            <span><strong> {displayPrice price=$r_product.price+$product_attribute.price currency=$conf.currency_current}</strong></span>
                            </td>
                            </tr>
                        {/if}
                    {/foreach}
                </table>
            {/if}
            {* end product attributes *}
        </div>
    </div>
{/foreach}

</body>

