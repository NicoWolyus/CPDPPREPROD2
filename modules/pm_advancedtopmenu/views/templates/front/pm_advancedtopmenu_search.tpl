<form method="get" action="{$atm_form_action_link|escape:'htmlall':'UTF-8'}" class="searchboxATM">
	<label for="{$atm_search_id|escape:'htmlall':'UTF-8'}">{if $atm_withExtra && $atm_have_icon}<img src="{$atm_icon_image_source|escape:'htmlall':'UTF-8'}" alt="" title="" class="adtm_menu_icon" />{/if}</label>
	<input type="hidden" name="controller" value="search" />
	<input type="hidden" name="orderby" value="position" />
	<input type="hidden" name="orderway" value="desc" />
	<input type="text" class="{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>=')}form-control {/if}search_query_atm" id="{$atm_search_id|escape:'htmlall':'UTF-8'}" name="search_query" value="{$atm_search_value|escape:'htmlall':'UTF-8'}" {if isset($atm_search_value) && !empty($atm_search_value)}onfocus="javascript:if(this.value=='{$atm_search_value|escape:'htmlall':'UTF-8'}')this.value='';" onblur="javascript:if(this.value=='')this.value='{$atm_search_value|escape:'htmlall':'UTF-8'}';"{/if} />
	{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
	<button type="submit" name="submit_search" class="adtm_search_submit_button">
		<i class="material-icons search"></i>
	</button>
	{else}
	<input type="submit" name="submit_search" value="{l s='OK' mod='pm_advancedtopmenu'}" class="{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '<')}button_mini{else}btn btn-default{/if}" />
	{/if}
</form>
{if $atm_is_autocomplete_search}
	<script type="text/javascript">
		$("#{$atm_search_id|escape:'htmlall':'UTF-8'}").autocomplete(
			"{$atm_pagelink_search|escape:'htmlall':'UTF-8'}", {ldelim}
				minChars: 3,
				max: 10,
				width: 500,
				selectFirst: false,
				scroll: false,
				dataType: "json",
				formatItem: function(data, i, max, value, term) {ldelim} return value;	{rdelim},
				parse: function(data) {ldelim}
					var mytab = new Array();
					for (var i = 0; i < data.length; i++)
					mytab[mytab.length] = {ldelim} data: data[i], value: data[i].cname + ' > ' + data[i].pname {rdelim};
					return mytab;
				{rdelim},
				extraParams: {ldelim}
					ajaxSearch: 1,
					id_lang: {$atm_cookie_id_lang|intval}
				{rdelim}
			{rdelim}
		).result(function(event, data, formatted) {ldelim}
			$('{$atm_search_id|escape:'htmlall':'UTF-8'}').val(data.pname);
			document.location.href = data.product_link;
		{rdelim});
	</script>
{/if}