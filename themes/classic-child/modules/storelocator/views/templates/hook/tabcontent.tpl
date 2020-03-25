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

{if $FMESL_TABSTATE > 0}
	<div id="idTab565" class="fmesl_TabStores">

    {if $stores|@count > 0}
    {if !empty($FMESL_TABHEAD)}<h6>{$FMESL_TABHEAD|escape:'htmlall':'UTF-8'}</h6>{/if}
    	<ol>
        
        {foreach from=$stores key=i item=store}

        <li{if $i>=3} style="display:none"{/if}><span>{$store.name|escape:'htmlall':'UTF-8'}<br />
        {$store.address1|escape:'htmlall':'UTF-8'}<br />
      {if !empty($store.address2)}  {$store.address2|escape:'htmlall':'UTF-8'}<br />{/if}
       {if !empty($store.phone)} {$store.phone|escape:'htmlall':'UTF-8'}{/if} {if !empty($store.email)}|  {$store.email|escape:'htmlall':'UTF-8'}<br />{/if} <a href="{$link->getPageLink('stores')|escape:'htmlall':'UTF-8'}?goforstore={$store.id_store|escape:'htmlall':'UTF-8'}">{l s='Get Directions' mod='storelocator'}</a></span>
      
         <img src="{$base_dir|escape:'htmlall':'UTF-8'}img/st/{$store.id_store|escape:'htmlall':'UTF-8'}-medium_default.jpg" /></li>
        {/foreach}
        </ol>
        {if $stores|@count > 3}<a href="javascript:void(0);" id="fmesl_showmore" onclick="fmeslShowmore();">{l s='Show More' mod='storelocator'} &rsaquo;&rsaquo;</a>{/if}
        {else}
        <p>{l s='No Related Stores' mod='storelocator'}</p>
        {/if}
        {literal}
        <style type="text/css">
		.fmesl_TabStores ol { list-style:none; padding:0; margin:0 !important;}
		.fmesl_TabStores ol li { display:block; padding:3px 5px; border:1px solid #CCC; font-size:12px; line-height:22px; margin-top:10px}
		.fmesl_TabStores ol li span { float:left}
		.fmesl_TabStores ol li img { float:right}
		.fmesl_TabStores ol li:after {content: '.';display: block;height: 0;width: 0;clear: both;visibility: hidden}
		#fmesl_showmore { line-height:27px}
		</style>
        <script type="text/javascript">
			function fmeslShowmore() {
				$('#idTab565 li').next().show(); $('#fmesl_showmore').hide();
				}
		</script>
        {/literal}
    </div>
{/if}

