<!--
* 2012-2018 NetReviews
*
*  @author    NetReviews SAS <contact@avis-verifies.com>
*  @copyright 2018 NetReviews SAS
*  @version   Release: $Revision: 7.7.0
*  @license   NetReviews
*  @date      05/12/2018
*  International Registered Trademark & Property of NetReviews SAS
-->
{if ($snippets_complete == "1")}
   <script type="application/ld+json">
      {
      "@context": "http://schema.org/",
      "@type": "Product",
      "@id": "{$product_url|escape:'htmlall':'UTF-8'}",
      "name": "{$product_name|escape:'htmlall':'UTF-8'}",
      "description": "{$product_description|strip_tags|escape:'htmlall':'UTF-8'}",
      "offers":
      [
         {
            "@type": "Offer",
            "priceCurrency": "EUR",
            "price": "{$product_price|escape:'htmlall':'UTF-8'}",
            "availability": "http://schema.org/InStock",
            "name": "{$product_name|escape:'htmlall':'UTF-8'}",
            "url": "{$product_url|escape:'htmlall':'UTF-8'}"
         }
      ] 
      {if ($av_nb_reviews && $av_rate) || $url_image || $product_url || $brand_name || $product_id || $sku || $mpn || $gtin_ean || $gtin_upc},{/if}
      {if $url_image} 
         "image": "{$url_image|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $product_url || $brand_name || $product_id || $sku || $mpn || $gtin_ean || $gtin_upc},{/if}
      {/if}
      {if $product_url} 
         "url": "{$product_url|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $brand_name || $product_id || $sku || $mpn || $gtin_ean || $gtin_upc},{/if}
      {/if}
      {if $brand_name}
         "brand": "{$brand_name|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $product_id || $sku || $mpn || $gtin_ean || $gtin_upc},{/if}
      {/if}
      {if $product_id} 
         "productID": "{$product_id|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $product_id || $sku || $mpn || $gtin_ean || $gtin_upc},{/if}
      {/if}
      {if $sku} 
         "sku": "{$sku|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $mpn || $gtin_ean || $gtin_upc},{/if}
      {/if}
      {if $mpn} 
         "mpn": "{$mpn|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $gtin_ean || $gtin_upc},{/if}
      {/if}
      {if $gtin_ean} 
         "gtin8": "{$gtin_ean|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate) || $gtin_upc},{/if}
      {/if}
      {if $gtin_upc} 
         "gtin12": "{$gtin_upc|escape:'htmlall':'UTF-8'}"{if ($av_nb_reviews && $av_rate)},{/if}
      {/if} 
      {if $av_nb_reviews && $av_rate}
         "aggregateRating": { 
         "@type": "AggregateRating", 
         "ratingValue": "{$av_rate|escape:'htmlall':'UTF-8'}", 
         "reviewCount": "{$av_nb_reviews|escape:'htmlall':'UTF-8'}",
         "worstRating": "1", 
         "bestRating": "5"
         } 
      {/if}
      }
   </script>
{/if}

{if $snippets_complete == '0'}
   <script type="application/ld+json">
   {
   "@context": "http://schema.org/",
   "@type": "Product",
   "@id": "{$product_url|escape:'htmlall':'UTF-8'}",
   "name": "{$product_name|escape:'htmlall':'UTF-8'}",
   {if $av_nb_reviews && $av_rate}
      "aggregateRating": { 
      "@type": "AggregateRating", 
      "ratingValue": "{$av_rate|escape:'htmlall':'UTF-8'}", 
      "reviewCount": "{$av_nb_reviews|escape:'htmlall':'UTF-8'}",
      "worstRating": "1", 
      "bestRating": "5"
      } 
   {/if}
   }
   </script>
{/if}

{foreach from=$detailsReviews item=review}
   <script type="application/ld+json">
   {
   "@context": "http://schema.org/",
   "@type": "Product",
   "@id": "{$product_url|escape:'htmlall':'UTF-8'}",
   "name": "{$product_name|escape:'htmlall':'UTF-8'}",
   {if $av_nb_reviews && $av_rate}
   "review" : {
      "@type": "Review",
      "reviewRating": {
            "@type": "Rating",
            "ratingValue": "{$review.rate|escape:'htmlall':'UTF-8'}"
            },
      "author": {
            "@type": "Person",
            "name": "{$review.customer_name|escape:'htmlall':'UTF-8'}"
            },
      "datePublished": "{$review.horodate|date_format:"%Y/%m/%d"}",
      "reviewBody": "{$review.review|escape:'htmlall':'UTF-8'}"
   }
   {/if}
   }
   </script>               
{/foreach} 
