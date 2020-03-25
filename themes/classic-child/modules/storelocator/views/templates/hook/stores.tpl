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

<div id="stores" class="card card-block block">
  <h1 class="title_block">{l s='Our stores' mod='storelocator'}</h1>
  <script type="text/javascript">
    // <![CDATA[
    var map_theme = {if isset($map_theme) AND $map_theme}JSON.parse('{$map_theme nofilter}'){else}''{/if}; //html content
    //]]>
  </script>
  <style type="text/css">{literal}.store_map{min-height: 500px!important;}{/literal}</style>
    {include file="./store_search_box.tpl"}
    {if isset($show_cal) AND $show_cal AND $pickupDate AND $hook_type == 'carrier'}
      {include file="./store_calander.tpl"}
    {/if}
  <div id="map{if isset($hook_type) AND $hook_type}-{$hook_type|escape:'htmlall':'UTF-8'}{/if}" class="store_map" style="min-height: 500px:"></div>

    <table cellpadding="0" cellspacing="0" border="0" id="stores-table" class="table_block table table-striped table-bordered">
      <tr>
        <th>{l s='#' mod='storelocator'}</th>
        <th>{l s='Store' mod='storelocator'}</th>
        <th>{l s='Address' mod='storelocator'}</th>
        <th>{l s='Distance' mod='storelocator'}</th>
      </tr>
    </table>
</div>