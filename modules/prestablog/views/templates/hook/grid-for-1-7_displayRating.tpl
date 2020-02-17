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
<div id="prestablog-rating">
{if ($isLogged)}
	{if ({$validate}) && ({$validate} == 'true')}
		<form action="{$LinkReal|escape:'html':'UTF-8'}&id={$news->id|intval}" method="post" class="rating">
 <input type="radio" id="star5" name="rate" value="5" /><label class = "full" for="star5" title="5 stars"></label>
    <input type="radio" id="star4" name="rate" value="4" /><label class = "full" for="star4" title="4 stars"></label>
    <input type="radio" id="star3" name="rate" value="3" /><label class = "full" for="star3" title="3 stars"></label>
    <input type="radio" id="star2" name="rate" value="2" /><label class = "full" for="star2" title="2 stars"></label>
    <input type="radio" id="star1" name="rate" value="1" /><label class = "full" for="star1" title="1 star"></label>
				<p class="submit">
					<input type="submit" class="btn-primary" name="submitRating" id="submitRating" value="{l s='Rate the article' mod='prestablog'}" />
				</p>
</form>
{else}
{l s='You have already rated this article' mod='prestablog'}
{/if}
{else}
{l s='Please log in to rate this article' mod='prestablog'}

{/if}
</div>
  <div class="clearfix" style="margin-bottom:30px;"></div>

<!-- /Module Presta Blog -->
