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
	<div class="fmesl_TabStores">

    {if $stores|@count > 0}
	<h3 class="h5 text-uppercase">{if empty($FMESL_TABHEAD)}{l s='Related Stores' mod='storelocator'}{else}{$FMESL_TABHEAD|escape:'htmlall':'UTF-8'}{/if}</h3>
    	{if !empty($FMESL_TABHEAD_SUB)}<p><strong>{$FMESL_TABHEAD_SUB|escape:'htmlall':'UTF-8'}</strong></p>{/if}
		<ol>
        
        {foreach from=$stores key=i item=store}

        <li{if $i>=3} style="display:none"{/if}><span>{$store.name|escape:'htmlall':'UTF-8'}<br />
        {$store.address1|escape:'htmlall':'UTF-8'}<br />
      {if !empty($store.address2)}  {$store.address2|escape:'htmlall':'UTF-8'}<br />{/if}
       {if !empty($store.phone)} {$store.phone|escape:'htmlall':'UTF-8'}{/if} {if !empty($store.email)}|  {$store.email|escape:'htmlall':'UTF-8'}<br />{/if} <a href="{$link->getPageLink('stores')|escape:'htmlall':'UTF-8'}?goforstore={$store.id_store|escape:'htmlall':'UTF-8'}">{l s='Get Directions' mod='storelocator'}</a></span>
      {assign var=fmm_img_path value="{if $force_ssl == 1}{$base_dir_ssl}{else}{$base_dir}{/if}img/st/{$store.id_store}-stores_default.jpg"}
         <img src="{$fmm_img_path|escape:'htmlall':'UTF-8'}" width="180" /></li>
        {/foreach}
        </ol>
        {if $stores|@count > 3}<a href="javascript:void(0);" id="fmesl_showmore" onclick="fmeslShowmore();">{l s='Show More' mod='storelocator'} &rsaquo;&rsaquo;</a>{/if}
        {else}
        <p>{l s='No Related Stores' mod='storelocator'}</p>
        {/if}
        {literal}
        <style type="text/css">
			.fmesl_TabStores { padding: 15px 0}
		.fmesl_TabStores ol { list-style:none; padding:0; margin:0 !important;}
		.fmesl_TabStores ol li { display:block; padding: 2% 3%; border:1px solid #CCC; background: #fff; margin-top:10px}
		.fmesl_TabStores ol li span { float:left;font-size:14px;line-height:25px; }
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

