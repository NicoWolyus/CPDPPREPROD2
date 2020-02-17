{*

* (c) 2013 Ambris Informatique

*

* @module       Recherche dynamique avancée (AmbJoliSearch)

* @file         ambjolisearch.tpl

* @subject      template pour champ recherche en haut de page sur le 'front office'

* @copyright    Copyright (c) 2013 Ambris Informatique SARL (http://www.ambris.com/)

* @author       Richard Stefan (@RicoStefan)

* @license      Commercial license

* Support by mail: support@ambris.com

*}

{if $url_rewriting}
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "WebSite",
    "url": "{$base_uri|escape:'htmlall':'UTF-8'}",
    "potentialAction":
    {
        "@type": "SearchAction",
        "target": "{$amb_joli_search_action|escape:'quotes':'UTF-8'}?search_query={literal}{search_query}{/literal}",
        "query-input": "required name=search_query"
    }
}
</script>
{/if}

<!-- Block search module TOP -->
<div id="search_block_top" class="jolisearch col-sm-4 clearfix">
    <form method="get" action="{$amb_joli_search_action|escape:'quotes':'UTF-8'}" id="searchbox">

        {if !$url_rewriting}
            {if $amb_joli_search_controller=='jolisearch'}
            <input type="hidden" name="controller" value="{$amb_joli_search_controller|escape:'quotes':'UTF-8'}" />
            <input type="hidden" name="module" value="ambjolisearch" />
            <input type="hidden" name="fc" value="module" />
            {else}
            <input type="hidden" name="controller" value="search" />
            {/if}

            <input type="hidden" name="orderby" value="position" />
            <input type="hidden" name="orderway" value="desc" />
            <input type="hidden" name="p" value="1" />
        {/if}
            <input class="search_query form-control ac_input" type="text" id="search_query_top" name="search_query" value="{if isset($smarty.get.search_query)}{$smarty.get.search_query|htmlentities:$ENT_QUOTES:'utf-8'|stripslashes}{/if}" data-autocomplete-mode="{$use_autocomplete|escape:'quotes':'UTF-8'}" data-autocomplete="{$amb_joli_search_link|escape:'quotes':'UTF-8'}" data-lang="{$id_lang|escape:'htmlall':'UTF-8'}" data-manufacturer="{l s='Manufacturers' mod='ambjolisearch'}" data-product="{l s='Products' mod='ambjolisearch'}" data-category="{l s='Categories' mod='ambjolisearch'}" data-minwordlen="{$minwordlen|escape:'quotes':'UTF-8'}" data-no-results-found="{l s='No results found' mod='ambjolisearch'}"  data-more-results="{l s='More results »' mod='ambjolisearch'}" placeholder="{l s='Search here...' mod='ambjolisearch'}"  />

            <!--<input type="submit" name="submit_search" value="{l s='Search' mod='ambjolisearch'}" class="button btn btn-default button-search" />-->

            <button type="submit" class="button btn btn-default button-search">
                <span>{l s='Search' mod='ambjolisearch'}</span>
            </button>

    </form>

</div>

<!-- /Block search module TOP -->

