{if $quote_active}
<li>
	<a href="{$link->getModuleLink('kerawen','quotelist')|escape:'htmlall':'UTF-8'}" title="{l s='Quotation' mod='kerawen'}">
		<i class="icon-list-alt"></i>
		<span>{l s='My quotes' mod='kerawen'}</span>
	</a>
</li>
{/if}
{if $giftcard_total > 0}
<li>
	<a href="{$link->getModuleLink('kerawen','giftcard')|escape:'htmlall':'UTF-8'}" title="{l s='Gift card' mod='kerawen'}">
		<i class="icon-gift"></i>
		<span>{l s='Gift card' mod='kerawen'}</span>
	</a>
</li>
{/if}