{*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*}

{extends file='page.tpl'}

{block name="page_content"}
<script type="text/javascript" src="{$protocol_link|escape:'htmlall':'UTF-8'}maps.googleapis.com/maps/api/js?{if isset($api_key) && $api_key}key={$api_key|strip:' '|escape:'htmlall':'UTF-8'}&{/if}region={$region|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript">
var map;
var markers = [];
var translation_5 = '{l s='Get directions' js=1 mod='storelocator'}';
var translation_store_sel = '{l s='Select Store' js=1 mod='storelocator'}';
var _current_store_id = parseInt("{$id_store|escape:'htmlall':'UTF-8'}");
var defaultLat = parseFloat("{$store.latitude|escape:'htmlall':'UTF-8'}");
var defaultLong = parseFloat("{$store.longitude|escape:'htmlall':'UTF-8'}");
var hasStoreIcon = 1;
var defaultZoom = {$def_zoom|escape:'htmlall':'UTF-8'};
var searchUrl = '{$searchUrl|escape:'htmlall':'UTF-8'}';
var img_ps_dir = '{$img_ps_dir|escape:'htmlall':'UTF-8'}';
var logo_store = '{$logo_store|escape:'htmlall':'UTF-8'}';
var FMESL_STORE_GLOBAL_ICON = parseInt("{$FMESL_GLOBAL_ICON|escape:'htmlall':'UTF-8'}");
var FMMSL_RELATED_ITEMS_EXISTS = {if isset($store.related_products) && !empty($store.related_products)}parseInt("1"){else}parseInt("0"){/if};
</script>
<!--<pre>{*$store|@print_r*}</pre>-->
<h1 class="page-heading bottom-indent">{if isset($store.name) && !empty($store.name)}{$store.name|escape:'htmlall':'UTF-8'}{else}{l s='Store number' mod='storelocator'} {$store.id_store|escape:'htmlall':'UTF-8'}{/if}</h1>
<div class="row" id="fmmsl_single_store_content">
	{if $store.has_picture > 0}
	<div class="col-lg-4">
		<img alt="{$store.name|escape:'htmlall':'UTF-8'}" src="{$store.picture|escape:'htmlall':'UTF-8'}" />
	</div>
	{/if}
	<div class="col-lg-{if $store.has_picture > 0}8{else}12{/if}">
		<div class="fmmsl_subwrap">
			<h2>{l s='Store Details' mod='storelocator'}</h2>
			<table class="table table-striped table-bordered">
				<tbody>
					<tr>
						<td>{l s='Address' mod='storelocator'}</td>
						<td>{$store.address|escape:'htmlall':'UTF-8'}</td>
					</tr>
					{if !empty($store.phone)}
					<tr>
						<td>{l s='Phone' mod='storelocator'}</td>
						<td>{$store.phone|escape:'htmlall':'UTF-8'}</td>
					</tr>
					{/if}
					{if !empty($store.fax)}
					<tr>
						<td>{l s='Fax' mod='storelocator'}</td>
						<td>{$store.fax|escape:'htmlall':'UTF-8'}</td>
					</tr>
					{/if}
					{if !empty($store.email)}
					<tr>
						<td>{l s='Email' mod='storelocator'}</td>
						<td>{$store.email|escape:'htmlall':'UTF-8'}</td>
					</tr>
					{/if}
					{if !empty($store.note)}
					<tr>
						<td>{l s='Information' mod='storelocator'}</td>
						<td>{$store.note|escape:'htmlall':'UTF-8'}</td>
					</tr>
					{/if}
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="row" id="fmmsl_single_store_maparea">
	<div class="col-lg-{if isset($store.working_hours)}9{else}12{/if}">
		<div id="store_single_map">
		</div>
	</div>
	{if isset($store.working_hours)}
	<div class="col-lg-3 fmmsl_store_hours_details">
		<div class="fmmsl_wh_wrap">
			<h2>{l s='Working Hours' mod='storelocator'}</h2>
			{$store.working_hours nofilter} {* html content *}
		</div>
	</div>
	{/if}
</div>
{if isset($store.related_products) && !empty($store.related_products)}
<div class="row" id="fmmsl_single_store_productarea">
	<div class="col-lg-12">
		<div class="fmmsl_subwrap">
			<h2>{l s='Products by store' mod='storelocator'}</h2>
			<div class="owl-carousel">
				{foreach from=$store.related_products key=z item=_item}
				<div>
					<a href="{$link->getProductLink($_item)|escape:'htmlall':'UTF-8'}" title="{$_item->name|escape:'htmlall':'UTF-8'}"><img src="{$link->getImageLink($_item->link_rewrite, $_item->id_image, 'home_default')|escape:'htmlall':'UTF-8'}"/>
					<h5>{$_item->name|escape:'htmlall':'UTF-8'}</h5>
					</a>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>
{/if}
{/block}
