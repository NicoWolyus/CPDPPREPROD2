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

    {hook h='displaySlider'}
<h1>
    {if isset($prestablog_title_h1)}
        {$prestablog_title_h1|escape:'htmlall':'UTF-8'}<br>
    {/if}
    <span>{$NbNews|intval}
    {if $NbNews <> 1}
        {l s='articles' mod='prestablog'}
    {else}
        {l s='article' mod='prestablog'}
    {/if}
    {if isset($prestablog_categorie_obj)}
        {l s='in the categorie' mod='prestablog'}&nbsp;{$prestablog_categorie_obj->title|escape:'htmlall':'UTF-8'}
    {/if}
    </span>
</h1>

{if sizeof($news)}
    {include file="$prestablog_pagination"}
    <ul id="blog_list">
    {foreach from=$news item=news_item name=NewsName}
        <li>
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
                                                <div class="star_content clearfix">
{if $prestablog_config.prestablog_rating_actif}
                        </div>
                             <div class="star_content clearfix">
{section name="i" start=0 loop=5 step=1}
    {if $smarty.section.i.index lt $news_item.average_rating}
                    <div class="fa fa-star checked"></div>
                        {elseif $news_item.average_rating == 5}
                        <div class="fa fa-star checked"></div>
    {else}
        <div class="fa fa-star"></div>
    {/if}
{/section}
    {/if}
</div>
                           {if $news_item.paragraph_crop!=''}
                         <p class="prestablog_desc">
                                {$news_item.paragraph_crop|escape:'htmlall':'UTF-8'}
                        </p>
                     {/if}
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
                                    >
                                </a>
                            {/if}
                    {/if}
                </div>
              </div>
        </li>
    {/foreach}
    </ul>
    {include file="$prestablog_pagination"}
{else}
    <p class="warning">{l s='Empty' mod='prestablog'}</p>

{/if}
<!-- /Module Presta Blog -->
