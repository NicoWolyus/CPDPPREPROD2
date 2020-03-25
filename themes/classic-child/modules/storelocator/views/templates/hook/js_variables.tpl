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

<script type="text/javascript" src="{$protocol_link|escape:'htmlall':'UTF-8'}maps.googleapis.com/maps/api/js?{if isset($api_key) && $api_key}key={$api_key|strip:' '|escape:'htmlall':'UTF-8'}&{/if}region={$region|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript">
    // <![CDATA[
    var map;
    var infoWindow;
    var markers = [];
    var maxDate = "{$maxDate|escape:'htmlall':'UTF-8'}";
    var calYear = parseInt("{$calYear|intval|escape:'htmlall':'UTF-8'}");
    var prevNav = '<i class="material-icons">keyboard_arrow_left</i>';
    var nextNav = '<i class="material-icons">keyboard_arrow_right</i>';
    var locationSelect = document.getElementById('locationSelect');
    var defaultLat = '{$defaultLat|escape:'htmlall':'UTF-8'}';
    var defaultLong = '{$defaultLong|escape:'htmlall':'UTF-8'}';
    var default_store = '{$default_store|intval|escape:'htmlall':'UTF-8'}';
    var sl_carrier = '{$default_carrier|intval|escape:'htmlall':'UTF-8'}';
    var defaultZoom = {$fmm_sl_zoom|escape:'htmlall':'UTF-8'};
    var hasStoreIcon = '{$hasStoreIcon|escape:'htmlall':'UTF-8'}';
    var distance_unit = '{$distance_unit|escape:'htmlall':'UTF-8'}';
    var img_store_dir = '{$img_store_dir|escape:'htmlall':'UTF-8'}';
    var img_ps_dir = '{$img_ps_dir|escape:'htmlall':'UTF-8'}';
    var searchUrl = '{$searchUrl|escape:'htmlall':'UTF-8'}';
    var logo_store = '{$logo_store|escape:'htmlall':'UTF-8'}';
    var autolocateUser = {$FMESL_USER|escape:'htmlall':'UTF-8'};
    var CurrentUrl = '{$smarty.server.HTTP_HOST|escape:'htmlall':'UTF-8'}{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}';
    CurrentUrl = location.search.split('goforstore=')[1];
    var search_link = "{$link->getPageLink('search')|addslashes|escape:'htmlall':'UTF-8'}";
    var FMESL_STORE_EMAIL = parseInt("{$FMESL_STORE_EMAIL|escape:'htmlall':'UTF-8'}");
    var FMESL_STORE_FAX = parseInt("{$FMESL_STORE_FAX|escape:'htmlall':'UTF-8'}");
    var FMESL_STORE_NOTE = parseInt("{$FMESL_STORE_NOTE|escape:'htmlall':'UTF-8'}");
    var FMESL_STORE_GLOBAL_ICON = parseInt("{$FMESL_GLOBAL_ICON|escape:'htmlall':'UTF-8'}");
    var FMESL_LAYOUT_THEME = parseInt("{$FMESL_LAYOUT_THEME|escape:'htmlall':'UTF-8'}");
    var FMESL_MAP_LINK = parseInt("{$FMESL_MAP_LINK|escape:'htmlall':'UTF-8'}");
    var FMESL_PICKUP_STORE = parseInt("{$FMESL_PICKUP_STORE|escape:'htmlall':'UTF-8'}");
    var st_page = "{$st_page|escape:'htmlall':'UTF-8'}";
    var locale = "{$iso_lang|escape:'htmlall':'UTF-8'}";
    var preselectedPickupTime = "{$preselectedPickupTime|escape:'htmlall':'UTF-8'}";
    var preselectedPickupDate = "{$preselectedPickupDate|escape:'htmlall':'UTF-8'}";

    // multilingual labels
    var translation_1 = '{l s='No stores were found. Please try selecting a wider radius.' js=1 mod='storelocator'}';
    var translation_2 = '{l s='store found -- see details:' js=1 mod='storelocator'}';
    var translation_3 = '{l s='stores found -- view all results:' js=1 mod='storelocator'}';
    var translation_4 = '{l s='Phone:' js=1 mod='storelocator'}';
    var translation_5 = '{l s='Get directions' js=1 mod='storelocator'}';
    var translation_6 = '{l s='Not found' js=1 mod='storelocator'}';
    var translation_7 = '{l s='Email:' js=1 mod='storelocator'}';
    var translation_8 = '{l s='Fax:' js=1 mod='storelocator'}';
    var translation_9 = '{l s='Note:' js=1 mod='storelocator'}';
    var translation_10 = '{l s='Distance:' js=1 mod='storelocator'}';
    var translation_11 = '{l s='View' js=1 mod='storelocator'}';
    var translation_01 = '{l s='Unable to find your location' js=1 mod='storelocator'}';
    var translation_02 = '{l s='Permission denied' js=1 mod='storelocator'}';
    var translation_03 = '{l s='Your location unknown' js=1 mod='storelocator'}';
    var translation_04 = '{l s='Timeout error' js=1 mod='storelocator'}';
    var translation_05 = '{l s='Location detection not supported in browser' js=1 mod='storelocator'}';
    var translation_06 = '{l s='Your current Location' js=1 mod='storelocator'}';
    var translation_07 = '{l s='You are near this location' js=1 mod='storelocator'}';
    var translation_store_sel = '{l s='Select Store' js=1 mod='storelocator'}';
    var available_date_label = '{l s='Available Dates' js=1 mod='storelocator'}';
    var disabled_date_label = '{l s='Unavailable Dates' js=1 mod='storelocator'}';
    var invalid_pickupdate_label = '{l s='Please enter a valid date.' js=1 mod='storelocator'}';
    var invalid_pickuptime_label = '{l s='Please enter a valid time.' js=1 mod='storelocator'}';
    var store_page_error_label = '{l s='Please select a pickup store.' js=1 mod='storelocator'}';
    //]]>
</script>
