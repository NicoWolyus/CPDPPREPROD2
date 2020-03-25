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

{capture name=path}{l s='Our stores' mod='storelocator'}{/capture}
<h1>{if empty($fmm_sl_pageheading)}{l s='Our stores' mod='storelocator'}{else}{$fmm_sl_pageheading|escape:'htmlall':'UTF-8'}{/if}</h1>
{if $simplifiedStoresDiplay}
  {if $stores|@count}
  <p>{l s='Here you can find our store locations. Please feel free to contact us:' mod='storelocator'}</p>
    {foreach $stores as $store}
      <div class="store-small grid_2">
        {if $store.has_picture}
          <p>
            <img src="{$img_store_dir|escape:'htmlall':'UTF-8'}{$store.id_store|escape:'htmlall':'UTF-8'}-medium_default.jpg" alt="" width="{$mediumSize.width|escape:'htmlall':'UTF-8'}" height="{$mediumSize.height|escape:'htmlall':'UTF-8'}" />
          </p>
        {/if}
        
        <p>
          <b>{$store.name|escape:'htmlall':'UTF-8'}</b><br />
          {$store.address1|escape:'htmlall':'UTF-8'}<br />
          {if $store.address2}{$store.address2|escape:'htmlall':'UTF-8'}{/if}<br />
          {$store.postcode|escape:'htmlall':'UTF-8'} {$store.city|escape:'htmlall':'UTF-8'}{if $store.state}, {$store.state|escape:'htmlall':'UTF-8'}{/if}<br />
          {$store.country|escape:'htmlall':'UTF-8'}<br />
          {if $store.phone}{l s='Phone:' mod='storelocator'} {$store.phone|escape:'htmlall':'UTF-8'}{/if}
        </p>
        {if isset($store.working_hours)}{$store.working_hours|escape:'htmlall':'UTF-8'}{/if}
      </div>
    {/foreach}
  {/if}
{else}

<script type="text/javascript">
  // <![CDATA[
    var map_theme = {if isset($map_theme) AND $map_theme}JSON.parse('{$map_theme nofilter}'){else}''{/if}; //html content
  //]]>
</script>

<style type="text/css">
  {if $FMESL_LAYOUT == 1}
  {literal}
    body#stores #right_column {
      display:none !important
    }
    body#stores #center_column, body#stores #map {
      width:740px !important;
      margin-right:0px !important
    }
  {/literal}
  {elseif $FMESL_LAYOUT == 2}
    body#stores #left_column {
      display:none !important
    }
    body#stores #center_column,
    body#stores #map {
      width:758px !important;
    }
  {elseif $FMESL_LAYOUT == 3}
  {else}
    body#stores #left_column, body#stores #right_column {
      display:none !important
    }
    body#stores #center_column, body#stores #map {
      width:100% !important;
    }
  {/if}
  {literal}
    #fmeStorePage p {
      display:inline-block;
      padding-right:10px
    }
    #fmeStorePage p.clearfix {
      padding-bottom:0;
      vertical-align:middle
    }
    .fmeSearchbyProduct {
      border: 1px solid #CCCCCC;
      padding: 2px 5px;
      width: 190px;
    }
    #fmm_sl_oldversions td {
      padding:1px !important;
    }
  {/literal}
</style>

<p>{l s='Enter a location (e.g. zip/postal code, address, city or country) in order to find the nearest stores.' mod='storelocator'}</p>
<div id="fmeStorePage">
{if $FMESL_SBP >= 1}
<p class="form-group">
    <label for="fmeSearchProduct">{l s='Search By Product:' mod='storelocator'}</label>
    <input type="text" id="fmeSearchProduct" value="{l s='Product Name etc. iPod nano' mod='storelocator'}" onclick="this.value='';this.name='';" class="fmeSearchbyProduct" name="" />
  </p>
{/if}
  <p class="form-group">
    <label for="addressInput">{l s='Your location:' mod='storelocator'}</label>
    <input class="form-control" type="text" name="location" id="addressInput" value="{l s='Address, zip / postal code, city, state or country' mod='storelocator'}" onclick="this.value='';" />
  </p>
  <p class="form-group">
    <label for="radiusSelect">{l s='Radius:' mod='storelocator'}</label>
    <select name="radius" id="radiusSelect">
      <option value="6371">any</option>
      <option value="15">15 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
      <option value="25">25 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
      <option value="50">50 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
      <option value="100">100 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
      <option value="500">500 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
      <option value="1000">1000 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
    </select>
    <img src="{$img_ps_dir|escape:'htmlall':'UTF-8'}loader.gif" class="middle" alt="" id="stores_loader" /> </p>
  <p class="clearfix">
    <input type="button" class="button" onclick="searchLocations();" value="{l s='Search' mod='storelocator'}" style="display: inline;" />
  </p>
  {if $FMESL_RESET == 1}
   <p class="clearfix">
    <input type="button" class="button" onclick="ResetMap();" value="{l s='Reset' mod='storelocator'}" style="display: inline;" />
  </p>
  {/if}
  <div>
    <select id="locationSelect" style="visibility:visible">
      {if $stores|@count}
        <option value="none">{$stores|@count|escape:'htmlall':'UTF-8'} {l s='Stores Found' mod='storelocator'}</option>
        {foreach from=$stores key=j item=str}
            <option value="{$j|escape:'htmlall':'UTF-8'}" label="{$str.id_store|escape:'htmlall':'UTF-8'}">{$str.id_store|escape:'htmlall':'UTF-8'}-{$str.name|escape:'htmlall':'UTF-8'}</option>
          {/foreach}
        {else}
        <option>-</option>
      {/if}
    </select>
  </div>
</div>
<div id="map" class="store_map"></div>
<table cellpadding="0" cellspacing="0" border="0" id="stores-table" class="table_block">
  <tr>
    <th>{l s='#' mod='storelocator'}</th>
    <th>{l s='Store' mod='storelocator'}</th>
    <th>{l s='Address' mod='storelocator'}</th>
    <th>{l s='Distance' mod='storelocator'}</th>
  </tr>
  {if $stores|@count}
  {foreach $stores as $store}
  <tr class="node">
    <td class="num">{$store.id_store|escape:'htmlall':'UTF-8'}</td>
    <td><b>{$store.name|escape:'htmlall':'UTF-8'}</b></td>
    <td>{$store.address1|escape:'htmlall':'UTF-8'}<br />
      {$store.city|escape:'htmlall':'UTF-8'}, {$store.iso_code|escape:'htmlall':'UTF-8'} {$store.postcode|escape:'htmlall':'UTF-8'}</td>
    <td class="distance" style="text-align:center"> - </td>
  </tr>
  {/foreach}
  {/if}
</table>
{/if} 