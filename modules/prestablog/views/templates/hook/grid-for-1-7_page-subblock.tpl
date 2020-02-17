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
<div class="intro-blog-home">
    <div class="col-md-12"><div class="container"><div
                    class="row">
    Des <a href="#">conseils</a> d’utilisations aux <a href="#">portraits</a> de <a href="#">nos producteurs locaux</a> en passant par de chouettes<br/> <a href="#">collab</a>, on vous invite dans notre univers.
    Cliquez, vous êtes en bonne Compagnie.
</div></div>
</div></div>

</span>
{if sizeof($news)}
    <ul id="blog_list_1-7">
    {foreach from=$news item=news_item name=NewsName}
        <li class="tiers blog-grid col-md-6">
            <div class="block_cont">
                <div class="block_top col-md-6">

    {if isset($news_item.image_presente)}
                    {if isset($news_item.link_for_unique)}<a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" title="{$news_item.title|escape:'htmlall':'UTF-8'}">{/if}
                        <img src="{$prestablog_theme_upimg|escape:'html':'UTF-8'}thumb_{$news_item.id_prestablog_news|intval}.jpg?{$md5pic|escape:'htmlall':'UTF-8'}" alt="{$news_item.title|escape:'htmlall':'UTF-8'}" />
                    {if isset($news_item.link_for_unique)}</a>{/if}
                {/if}
                </div>
                <div class="block_bas col-md-6">

          {*<span class="date_blog-cat">{l s='Published :' mod='prestablog'}
                            {dateFormat date=$news_item.date full=1}*}

                                {foreach from=$news_item.categories item=categorie key=key name=current}
                                    <a href="{PrestaBlogUrl c=$key titre=$categorie.link_rewrite}" class="categorie_blog">{$categorie.title|escape:'htmlall':'UTF-8'}</a>
                                    {if !$smarty.foreach.current.last},{/if}
                                {/foreach}
                    <h3 class="blog-title">
                        {if isset($news_item.link_for_unique)}<a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" title="{$news_item.title|escape:'htmlall':'UTF-8'}">{/if}{$news_item.title|escape:'htmlall':'UTF-8'}{if isset($news_item.link_for_unique)}</a>{/if}

                    </h3>
                      <p class="blog_desc">
                        {if $news_item.paragraph_crop!=''}
                            {$news_item.paragraph_crop|escape:'htmlall':'UTF-8'}
                        {/if}
                        <a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" class="link_toplist"></a>
                    </p>
                </div>
                <div class="prestablog_more">
                    {if isset($news_item.link_for_unique)}
                        <a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}" class="blog_link">{l s='Read it now' mod='prestablog'}</a>
                        <div class="up-orange-blog col-md-6"></div>

                      {*  {if $prestablog_config.prestablog_comment_actif==1}
                            <a href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}#comment" class="comments"><i class="material-icons">comment</i> {$news_item.count_comments|intval}</a>
                        {/if}
                        {if $prestablog_config.prestablog_commentfb_actif==1}
                            <a
                                href="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}#comment"
                                id="showcomments{$news_item.id_prestablog_news|intval}"
                                class="comments"
                                data-commentsurl="{PrestaBlogUrl id=$news_item.id_prestablog_news seo=$news_item.link_rewrite titre=$news_item.title}"
                                data-commentsidnews="{$news_item.id_prestablog_news|intval}"
                                ><i class="material-icons">comment</i> {$news_item.count_comments|intval}
                            </a>
                        {/if}*}
                    {/if}
                </div>
            </div>
        </li>
    {/foreach}

    </ul>
{/if}
</section>
<!-- /Module Presta Blog -->
