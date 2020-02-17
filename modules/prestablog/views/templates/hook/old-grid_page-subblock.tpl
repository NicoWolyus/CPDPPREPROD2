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
<section class="clearfix prestablog">
<h2 class="title">{$subblocks.title|escape:'htmlall':'UTF-8'}</h2>

{if sizeof($news)}
    <ul id="blog_list">
    {foreach from=$news item=news_item name=NewsName}
        <li class="tiers blog-grid">
            <div class="block_cont">
                <div class="block_top">

    {if isset($news_item.image_presente)}
                    {if isset($news_item.link_for_unique)}<a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" title="{$news_item.title|escape:'htmlall':'UTF-8'}">{/if}
                        <img src="{$prestablog_theme_upimg|escape:'html':'UTF-8'}thumb_{$news_item.id_prestablog_news|intval}.jpg?{$md5pic|escape:'htmlall':'UTF-8'}" alt="{$news_item.title|escape:'htmlall':'UTF-8'}" />
                    {if isset($news_item.link_for_unique)}</a>{/if}
                {/if}
                </div>
               <div class="block_bas">
                    <h3>
                        {if isset($news_item.link_for_unique)}<a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" title="{$news_item.title|escape:'htmlall':'UTF-8'}">{/if}{$news_item.title|escape:'htmlall':'UTF-8'}{if isset($news_item.link_for_unique)}</a>{/if}
                    <br /><span class="date_blog-cat">{l s='Published :' mod='prestablog'}
                            {dateFormat date=$news_item.date full=1}
                            {if sizeof($news_item.categories)} | {l s='Categories :' mod='prestablog'}
                                {foreach from=$news_item.categories item=categorie key=key name=current}
                                    <a href="{PrestaBlogUrl c=$key titre=$categorie.link_rewrite}" class="categorie_blog">{$categorie.title|escape:'htmlall':'UTF-8'}</a>
                                    {if !$smarty.foreach.current.last},{/if}
                                {/foreach}
                            {/if}</span>
                    </h3>
                    <p class="blog_desc">
                        {if $news_item.paragraph_crop!=''}
                            {$news_item.paragraph_crop|escape:'htmlall':'UTF-8'}
                        {/if}
                    </p>
                    {if isset($news_item.link_for_unique)}
                        <a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" class="blog_link">{l s='Read more' mod='prestablog'}</a>
                        {if $prestablog_config.prestablog_comment_actif==1 && $news_item.count_comments>0}
                            <a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}#comment" class="comments"> {$news_item.count_comments|intval}</a>
                        {/if}
                        {if $prestablog_config.prestablog_commentfb_actif==1}
                            <a
                                href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}#comment"
                                id="showcomments{$news_item.id_prestablog_news|intval}"
                                class="comments"
                                data-commentsurl="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}"
                                data-commentsidnews="{$news_item.id_prestablog_news|intval}"
                                > {$news_item.count_comments|intval}
                            </a>
                        {/if}
                    {/if}
                </div>
            </div>
        </li>
    {/foreach}
    </ul>
{/if}
</section>
<!-- /Module Presta Blog -->
