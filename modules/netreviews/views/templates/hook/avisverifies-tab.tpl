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
<li>
    <a href="#netreviews_reviews_tab" class="avisverifies_tab" data-toggle="tab" id="tab_avisverifies" >
        {if $avisverifies_rename_tag}
            {$avisverifies_rename_tag|escape:'htmlall':'UTF-8'}
            ({$count_reviews|escape:'htmlall':'UTF-8'})
        {else}
            {$count_reviews|escape:'htmlall':'UTF-8'}
                {if $count_reviews > 1}
                    {l s='reviews' mod='netreviews'}
                {else}
                    {l s='review' mod='netreviews'}
                {/if}
        {/if}
    </a>
</li>