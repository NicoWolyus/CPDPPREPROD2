<div class="content">
	<div class="row">
		<section id="center_column">		
			{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">{l s='My account' mod='kerawen'}</a><span class="navigation-pipe">{$navigationPipe|escape:'javascript':'UTF-8'}</span>{l s='Gift cards' mod='kerawen'}{/capture}
			{include file="$tpl_dir./errors.tpl"}
			
			<h1>{l s='Gift cards' mod='kerawen'}</h1>

			<div class="block-center" id="block-history">
				{if $giftcards && count($giftcards)}
				<table class="discount table table-bordered footab">
                    <thead>
                        <tr>
                            <th class="item">{l s='Code' mod='kerawen'}</th>
							<th class="item">{l s='Order date' mod='kerawen'}</th>
                            <th class="item">{l s='Order  Id' mod='kerawen'}</th>
                            <th class="item">{l s='Amount' mod='kerawen'}</th>
                            <th class="item">{l s='Expiry date' mod='kerawen'}</th>
                            <th class="item">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$giftcards item=giftcard name=myLoop}
                        <tr class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{/if}">
							<td>{if $giftcard.status}{$giftcard.barcode}{else}<del>{$giftcard.barcode}</del>{/if}</td>
                            <td>{dateFormat date=$giftcard.date_upd full=0}</td>
                            <td>{$giftcard.id_order}</td>
                            <td>{displayPrice currency=$giftcard.id_currency price=$giftcard.amount}</td>
                            <td>{dateFormat date=$giftcard.expiry full=0}</td>
                            <td style="width: 15px;">{if $giftcard.status}<i class="icon-check"></i>{else}<i class="icon-close"></i>{/if}</td>
                        </tr>
                    {/foreach}
                    </tbody>
				</table>
				<div id="block-order-detail" class="hidden">&nbsp;</div>
				{else}
					<p class="warning">{l s='No gift cards found' mod='kerawen'}</p>
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