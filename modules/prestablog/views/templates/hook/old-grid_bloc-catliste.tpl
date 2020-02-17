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
	<h4 class="title_block">
		{if $prestablog_categorie_courante->id}
			{if $prestablog_categorie_parent->id == 0 && $prestablog_categorie_courante->id != 0}
				<a href="{PrestaBlogUrl}">{l s='Blog' mod='prestablog'}</a>&nbsp;>
			{elseif $prestablog_categorie_parent->id > 0}
				<a href="{PrestaBlogUrl c=$prestablog_categorie_parent->id titre=$prestablog_categorie_parent->link_rewrite}">{$prestablog_categorie_parent->title|escape:'htmlall':'UTF-8'}</a>&nbsp;>
			{/if}
			{$prestablog_categorie_courante->title|escape:'htmlall':'UTF-8'}
		{else}
			{l s='Blog categories' mod='prestablog'}
		{/if}
	</h4>
	<div class="block_content" id="prestablog_catliste">
		{if sizeof($ListeBlocCatNews)}
			{if $prestablog_config.prestablog_catnews_tree}
				<ul class="prestablogtree {if $isDhtml}dhtml{/if}">
				{foreach from=$ListeBlocCatNews item=Item name=myLoop}
					<li>
						<p class="catblog_p">
							<a href="{PrestaBlogUrl c=$Item.id_prestablog_categorie titre=$Item.link_rewrite}">
								{if isset($Item.image_presente) && $prestablog_config.prestablog_catnews_showthumb}<img src="{$prestablog_theme_upimg|escape:'html':'UTF-8'}c/adminth_{$Item.id_prestablog_categorie|intval}.jpg?{$md5pic|escape:'htmlall':'UTF-8'}" alt="{$Item.link_rewrite|escape:'htmlall':'UTF-8'}" class="catblog_img lastlisteimg" />{/if}
								<strong class="catblog_title">{$Item.title|escape:'htmlall':'UTF-8'}</strong>
								{if $prestablog_config.prestablog_catnews_shownbnews && $Item.nombre_news_recursif > 0}&nbsp;<span class="catblog_nb_news">({$Item.nombre_news_recursif|intval})</span>{/if}
							</a>
							{if $prestablog_config.prestablog_catnews_rss}<a target="_blank" href="{PrestaBlogUrl rss=$Item.id_prestablog_categorie}"><img src="{$prestablog_theme_dir|escape:'html':'UTF-8'}/img/rss.png" alt="Rss feed" align="absmiddle" /></a>{/if}
							{if $prestablog_config.prestablog_catnews_showintro}
							<a class="catblog_desc" href="{PrestaBlogUrl c=$Item.id_prestablog_categorie titre=$Item.link_rewrite}"><br /><span>{$Item.description_crop|escape:'htmlall':'UTF-8'}</span></a>{/if}
						</p>
						{if $Item.children|@count > 0}
							{include file="$tree_branch_path" node=$Item.children}
						{/if}
					</li>
				{/foreach}
				</ul>
			{else}
				{foreach from=$ListeBlocCatNews item=Item name=myLoop}
					<p>
						<a href="{PrestaBlogUrl c=$Item.id_prestablog_categorie titre=$Item.link_rewrite}">
							{if isset($Item.image_presente) && $prestablog_config.prestablog_catnews_showthumb}<img src="{$prestablog_theme_upimg|escape:'html':'UTF-8'}c/adminth_{$Item.id_prestablog_categorie|intval}.jpg?{$md5pic|escape:'htmlall':'UTF-8'}" alt="{$Item.link_rewrite|escape:'htmlall':'UTF-8'}" class="lastlisteimg" />{/if}
							<strong>{$Item.title|escape:'htmlall':'UTF-8'}</strong>
							{if $prestablog_config.prestablog_catnews_shownbnews && $Item.nombre_news_recursif > 0}&nbsp;<span>({$Item.nombre_news_recursif|intval})</span>{/if}
						</a>
						{if $prestablog_config.prestablog_catnews_rss}<a target="_blank" href="{PrestaBlogUrl rss=$Item.id_prestablog_categorie}"><img src="{$prestablog_theme_dir|escape:'html':'UTF-8'}/img/rss.png" alt="Rss feed" align="absmiddle" /></a>{/if}
						{if $prestablog_config.prestablog_catnews_showintro}
						<a href="{PrestaBlogUrl c=$Item.id_prestablog_categorie titre=$Item.link_rewrite}"><br /><span>{$Item.description_crop|escape:'htmlall':'UTF-8'}</span></a>{/if}
					</p>
				{/foreach}
			{/if}
		{else}
			<p>{l s='No subcategories' mod='prestablog'}</p>
		{/if}
		{if $prestablog_config.prestablog_catnews_showall}<a href="{PrestaBlogUrl}" class="btn-primary">{l s='See all' mod='prestablog'}</a>{/if}
	</div>
</div>
<!-- /Module Presta Blog -->

