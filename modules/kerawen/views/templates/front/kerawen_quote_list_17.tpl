{extends file='page.tpl'}

{block name='page_title'}
  {l s='Quotes' mod='kerawen'}
{/block}

{block name='page_content'}

<h6>{l s='Your quotations' mod='kerawen'}</h6>

<div class="content">
	<div class="row">
		<section id="center_column">		

				{if $quotes && count($quotes)}
				<table class="table table-striped table-bordered hidden-sm-down">
                    <thead class="thead-default">
                        <tr>
                            <th class="item">{l s='Id' mod='kerawen'}</th>
                            <th class="item">{l s='Expiry date' mod='kerawen'}</th>
                            <th class="item">{l s='Total' mod='kerawen'}</th>
                            <th class="item">&nbsp;</th>
                            <th class="item">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$quotes item=quote name=myLoop}
                        <tr class="">
                            <td>{$quote.quote_title|escape:'htmlall':'UTF-8'}</td>
                            <td>{dateFormat date=$quote.quote_expiry full=0}</td>
                            <td>{$quote.total}</td>
                            <td>{if $quote.quote_active == 1}<a href="{url entity='module' name='kerawen' controller='quotenext' params = ['id_cart'=>$quote.id_cart, 'action'=>'display']}">{l s='Order now' mod='kerawen'}{else}{l s='Expired' mod='kerawen'}{/if}</a></td>
                        	<td><a href="{url entity='module' name='kerawen' controller='quotenext' params = ['id_cart'=>$quote.id_cart, 'action'=>'download']}">{l s='Download' mod='kerawen'}</a></td>
                        </tr>
                    {/foreach}
                    </tbody>
				</table>
				{else}
					<p class="warning">{l s='No quotes found' mod='kerawen'}</p>
				{/if}


				<!--div>Pour les unités : voir controller/front/DiscountController.php</div-->
				
		</section>
	</div>
</div>
{/block}