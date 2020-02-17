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
	<h4 class="title_block">{l s='Last blog articles' mod='prestablog'}</h4>
	<div class="block_content" id="prestablog_lastliste">
		{if $ListeBlocLastNews}
			{foreach from=$ListeBlocLastNews item=Item name=myLoop}
				<p>
					{if isset($Item.link_for_unique)}<a href="{PrestaBlogUrl id=$Item.id_prestablog_news seo=$Item.link_rewrite titre=$Item.title}">{/if}
						{if isset($Item.image_presente) && $prestablog_config.prestablog_lastnews_showthumb}
							<img src="{$prestablog_theme_upimg|escape:'html':'UTF-8'}adminth_{$Item.id_prestablog_news|intval}.jpg?{$md5pic|escape:'htmlall':'UTF-8'}" alt="{$Item.title|escape:'htmlall':'UTF-8'}" class="lastlisteimg" />
						{/if}
						{$Item.title|escape:'htmlall':'UTF-8'}
						{if $prestablog_config.prestablog_lastnews_showintro}<br /><span>{$Item.paragraph_crop|escape:'htmlall':'UTF-8'}</span>{/if}
					{if isset($Item.link_for_unique)}</a>{/if}
				</p>
				{if !$smarty.foreach.myLoop.last}{/if}
			{/foreach}
		{else}
			<p>{l s='No news' mod='prestablog'}</p>
		{/if}

		{if $prestablog_config.prestablog_lastnews_showall}<div class="clearblog"></div><a href="{PrestaBlogUrl}" class="btn-primary">{l s='See all' mod='prestablog'}</a>{/if}
	</div>
</div>
<!-- /Module Presta Blog -->
