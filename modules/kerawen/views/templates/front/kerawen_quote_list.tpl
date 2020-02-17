<div class="content">
	<div class="row">
		<section id="center_column">		
			{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">{l s='My account' mod='kerawen'}</a><span class="navigation-pipe">{$navigationPipe|escape:'javascript':'UTF-8'}</span>{l s='Quotes' mod='kerawen'}{/capture}
			{include file="$tpl_dir./errors.tpl"}
			
			<h1>{l s='Quotes' mod='kerawen'}</h1>

			<div class="block-center" id="block-history">
				{if $quotes && count($quotes)}
				<table class="discount table table-bordered footab">
                    <thead>
                        <tr>
                            <th class="item" style="width: 50%;">{l s='id' mod='kerawen'}</th>
                            <th class="item">{l s='Expiry date' mod='kerawen'}</th>
                            <th class="item">{l s='total' mod='kerawen'}</th>
                            <th class="item">&nbsp;</th>
                            <th class="item">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$quotes item=quote name=myLoop}
                        <tr class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{/if}">
                            <td>{$quote.quote_title|escape:'htmlall':'UTF-8'}</td>
                            <td>{dateFormat date=$quote.quote_expiry full=0}</td>
                            <td>{displayPrice currency=$id_currency price=$quote.total}</td>
                            <td>{if $quote.quote_active == 1}<a href="{$link->getModuleLink('kerawen', quotenext, ['id_cart'=>$quote.id_cart, 'action'=>'display'])|escape:'htmlall':'UTF-8'}" class="btn btn-default button button-small"><span>{l s='Order now' mod='kerawen'}<i class="icon-chevron-right right"></i></span>{else}{l s='Expired' mod='kerawen'}{/if}</a></td>
                        	<td><a href="{$link->getModuleLink('kerawen', quotenext, ['id_cart'=>$quote.id_cart, 'action'=>'download'])|escape:'htmlall':'UTF-8'}" class="btn btn-default button button-small"><span>{l s='Download' mod='kerawen'}<i class="icon-chevron-right right"></i></span></a></td>
                        </tr>
                    {/foreach}
                    </tbody>
				</table>
				<div id="block-order-detail" class="hidden">&nbsp;</div>
				{else}
					<p class="warning">{l s='No quotes found' mod='kerawen'}</p>
				{/if}


			<ul class="footer_links clearfix">
				<li>
					<a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}" class="btn btn-default button button-small">
						<span><i class="icon-chevron-left"></i> {l s='Back to Your Account' mod='kerawen'}</span>
					</a>
				</li>
				<li class="f_right">
					<a href="{$base_dir|escape:'htmlall':'UTF-8'}" class="btn btn-default button button-small">
						<span><i class="icon-chevron-left"></i> {l s='Home' mod='kerawen'}</span>
					</a>
				</li>
			</ul>
            
            
		</section>
	</div>
</div>