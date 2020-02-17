<!--
* 2012-2018 NetReviews
*
*  @author    NetReviews SAS <contact@avis-verifies.com>
*  @copyright 2017 NetReviews SAS
*  @version   Release: $Revision: 7.7.4
*  @license   NetReviews
*  @date      11/03/2019
*  International Registered Trademark & Property of NetReviews SAS
-->
{assign var="av_star_type" value="widget"}
<div itemscope itemtype="http://schema.org/Product" id="av_snippets_block">
   <div>
      <span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
         <meta itemprop="priceCurrency" content="EUR" />
         <meta itemprop="price" content="{$price_average|escape:'htmlall':'UTF-8'}" />
         <link itemprop="availability" href="http://schema.org/InStock" />
      </span>

    <div id="netreviews_category_review">
      <!-- <img width="100" src="{$modules_dir|escape:'htmlall':'UTF-8'}netreviews/views/img/logo_full_{$logo_lang|escape:'htmlall':'UTF-8'}.png"> -->
      <div>
        <meta itemprop="name" content="{$nom_category|escape:'htmlall':'UTF-8'}" />
        <meta itemprop="description" content="{$description_category|escape:'htmlall':'UTF-8'}" />
        <meta itemprop="brand" content="{$brand|escape:'htmlall':'UTF-8'}" />
    <div class="netreviews_stars_light" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating" >
        <!-- <a href="javascript:av_widget_click()" id="AV_button"> -->
           <div id="top">
            <!--   <div class="netreviews_review_rate_and_stars">
                {*include file=$stars_dir*}  
              </div>  -->
              <div id="slide">
                 <meta itemprop="ratingValue" content="{$average_rate|escape:'htmlall':'UTF-8'}" />
                 <meta itemprop="worstRating" content="1" />
                 <meta itemprop="bestRating" content="5" />
                 <meta itemprop="reviewCount" content="{$count_reviews|escape:'htmlall':'UTF-8'}" />
            <!--      {if $av_nb_reviews > 1}
                 {l s='reviews' mod='netreviews'}
                 {else}
                 {l s='review' mod='netreviews'}
                 {/if} -->
              </div>
           </div>
        <!-- </a> -->
    </div>

        <!-- {$average_rate|escape:'htmlall':'UTF-8'} /5 -->
      </div>
    </div>
</div>



