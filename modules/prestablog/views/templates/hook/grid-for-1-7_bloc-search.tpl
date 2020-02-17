{*
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 *}

<!-- Module Presta Blog -->
<div class="block-categories">
	<h4 class="title_block">{l s='Search on blog' mod='prestablog'}</h4>
	<div class="block_content">
		<form action="{PrestaBlogUrl}" method="post" id="prestablog_bloc_search">
			<input id="prestablog_search" class="search_query form-control ac_input" type="text" value="{$prestablog_search_query|escape:'htmlall':'UTF-8'}" placeholder="{l s='Search on blog' mod='prestablog'}" name="prestablog_search" autocomplete="off">
			<button class="btn btn-default button-search" type="submit">
				<span>{l s='Search on blog' mod='prestablog'}</span>
			</button>
			<div class="clear"></div>
		</form>
	</div>
</div>
<!-- /Module Presta Blog -->
