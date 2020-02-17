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
<section class="footer-block col-xs-12 col-sm-2">
   <h4>{l s='Last blog articles' mod='prestablog'}</h4>
   <ul class="toggle-footer">
      {if $ListeBlocLastNews}
         {foreach from=$ListeBlocLastNews item=Item name=myLoop}
            <li>{dateFormat date=$Item.date full=1}<br/>
               {if isset($Item.link_for_unique)}<a href="{PrestaBlogUrl id=$Item.id_prestablog_news seo=$Item.link_rewrite titre=$Item.title}">{/if}
                  <strong>{$Item.title|escape:'htmlall':'UTF-8'}</strong>
                  {if $prestablog_config.prestablog_footlastnews_intro}<br /><span>{$Item.paragraph_crop|escape:'htmlall':'UTF-8'}</span>{/if}
               {if isset($Item.link_for_unique)}</a>{/if}
            </li>
            {if !$smarty.foreach.myLoop.last}{/if}
         {/foreach}
      {else}
         <li>{l s='No news' mod='prestablog'}</li>
      {/if}
      {if $prestablog_config.prestablog_footlastnews_showall}
         <li>
            <a href="{PrestaBlogUrl}" class="button_large">{l s='See all' mod='prestablog'}</a>
         </li>
      {/if}
   </ul>
</section>
<!-- /Module Presta Blog -->
