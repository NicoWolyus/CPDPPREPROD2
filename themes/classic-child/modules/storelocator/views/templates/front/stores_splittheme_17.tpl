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
  <h1>{if empty($fmm_sl_pageheading)}{l s='Our stores' mod='storelocator'}{else}{$fmm_sl_pageheading|escape:'htmlall':'UTF-8'}{/if}</h1>
  <script type="text/javascript">
      // <![CDATA[
      var map_theme = {if isset($map_theme) AND $map_theme}JSON.parse('{$map_theme nofilter}'){else}''{/if}; //html content
      //]]>
  </script>
  <style type="text/css">
    {if $FMESL_LAYOUT == 1}{literal}body#stores #right_column { display:none !important} body#stores #center_column, body#stores #map { width:740px !important; margin-right:0px !important} {/literal} {elseif $FMESL_LAYOUT ==2}body#stores #left_column { display:none !important} body#stores #center_column, body#stores #map { width:758px !important;}{elseif $FMESL_LAYOUT ==3} {else} body#stores #left_column, body#stores #right_column { display:none !important} body#stores #center_column, body#stores #map { width:100% !important;}{/if}{literal}
    #fmeStorePage p { display:inline-block; padding-right:10px} #fmeStorePage p.clearfix { padding-bottom:0; vertical-align:middle} .fmeSearchbyProduct {border: 1px solid #CCCCCC;padding: 2px 5px;}{/literal}
  </style>

  <p>{l s='Enter a location (e.g. zip/postal code, address, city or country) in order to find the nearest stores.' mod='storelocator'}</p>

  <div id="fmeStorePage" class="clearfix card card-block">
      {if $FMESL_SBP == 1}
        <div class="col-lg-4 form-group">
          <label for="fmeSearchProduct">{l s='Search By Product:' mod='storelocator'}</label>
          <input id="fmeSearchProduct" type="text" placeholder="{l s='Product Name etc. iPod nano' mod='storelocator'}" onclick="this.value='';this.name='';" class="fmeSearchbyProduct form-control" name="" />
        </div>
      {/if}

    <div class="col-lg-4 form-group pl-0">
      <label for="addressInput">{l s='Your location:' mod='storelocator'}</label>
      <div>
        <input type="text" name="location" id="addressInput" value="{l s='Address, zip / postal code, city, state or country' mod='storelocator'}" onclick="this.value='';" class="form-control"/>
      </div>
    </div>

    <div class="col-lg-2 pl-0 pr-0 form-group">
      <label for="radiusSelect">{l s='Radius:' mod='storelocator'}</label>
      <div>
        <div class="col-lg-10 pl-0">
          <select name="radius" id="radiusSelect" class="form-control">
            <option value="6371">{l s='any' mod='storelocator'}</option>
            <option value="15">15 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
            <option value="25">25 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
            <option value="50">50 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
            <option value="100">100 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
            <option value="500">500 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
            <option value="1000">1000 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
          </select>
        </div>

      </div>
    </div>

    <div class="col-lg-4 pl-0 searchonstore">
      <p>

        <img src="{$urls.img_url}chev-right.png" id="chev-search"><input type="button" class="button btn btn-primary" onclick="searchLocations();" value="{l s='Search' mod='storelocator'}" style="display: inline;" />
      </p>

        {if $FMESL_RESET == 1}
          <p>
            <input type="button" class="button btn btn-warning" onclick="ResetMap();" value="{l s='Reset' mod='storelocator'}" style="display: inline;" />
          </p>
        {/if}
    </div>
    <div class="col-lg-1">
      <img src="{$img_ps_dir|escape:'htmlall':'UTF-8'}loader.gif" class="middle" alt="" id="stores_loader" />
    </div>
  </div>

<div class="col-md-12 maplist">
<div class="col-lg-4">
  <div id="fmmsl_split_list">
    {if !empty($stores)}
    <ul>
    </ul>
    {/if}
  </div>
</div>
<div class="col-lg-8">
  <div id="map" class="store_map"></div>
</div>
</div>
{/block}