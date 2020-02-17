{extends file='page.tpl'}

{block name='page_title'}
  {l s='Gift card' mod='kerawen'}
{/block}

{block name='page_content'}

<div class="content">
	<div class="row">
		<section id="center_column">		

				{if $giftcards && count($giftcards)}
				<table class="table table-striped table-bordered hidden-sm-down">
                    <thead class="thead-default">
                        <tr>
                        	<th class="item">{l s='Id' mod='kerawen'}</th>
                            <th class="item">{l s='Code' mod='kerawen'}</th>
							<th class="item">{l s='Order date' mod='kerawen'}</th>
                            <th class="item">{l s='Amount' mod='kerawen'}</th>
                            <th class="item">{l s='Expiry date' mod='kerawen'}</th>
                            <th class="item">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$giftcards item=giftcard name=myLoop}
                        <tr class="">
                        	<td>{$giftcard.id_order}</td>
							<td>{if $giftcard.status}{$giftcard.barcode}{else}<del>{$giftcard.barcode}</del>{/if}</td>
                            <td>{dateFormat date=$giftcard.date_upd full=0}</td>
                            <td>{$giftcard.amount}</td>
                            <td>{dateFormat date=$giftcard.expiry full=0}</td>
                            <td>{if $giftcard.status}<i class="material-icons">&#xE876;</i>{else}<i class="material-icons">&#xE5CD;</i>{/if}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                 </table>
				{else}
					<p class="warning">{l s='No gift card found' mod='kerawen'}</p>
				{/if}
		</section>
	</div>
</div>
{/block}