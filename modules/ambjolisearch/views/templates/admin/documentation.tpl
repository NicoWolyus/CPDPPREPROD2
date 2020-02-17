{*
* (c) 2013 Ambris Informatique
*
* @module       All ambris modules
* @file         documentation.tpl
* @subject      template for documentations
* @copyright    Copyright (c) 2013 Ambris Informatique SARL (http://www.ambris.com/)
* @author       Richard Stefan (@RicoStefan)
* @license      Commercial license
* Support by mail: support@ambris.com
*}

<div class="panel">
    <div class="panel-heading">
       {l s='Documentation' mod='ambjolisearch'}
    </div><div class="row">
    <div class="col-xs-12">
    <a target="_blank" href="{$documentation_link|escape:'htmlall':'UTF-8'}">{l s='Check out our documentation here' mod='ambjolisearch'}</a>
    </div>
    </div>
</div>
<div class="panel">
    <div class="panel-heading">
        {l s='Discover our other modules' mod='ambjolisearch'}
    </div>
    <div class="row">
        <div class="col-xs-4">
        <a href="{l s='http://addons.prestashop.com/en/search-filters/11066-jolisearch-advanced-visual-search.html' mod='ambjolisearch'}" target="_blank">
            <img class="lazy" data-original="http://medias2.prestastore.com/img/p/../pico/11066.jpg" alt="JoliSearch : recherche visuelle avancée" height="57" width="57" src="http://medias2.prestastore.com/img/p/../pico/11066.jpg" style="display: inline-block;"> {l s='JoliSearch : advanced visual search' mod='ambjolisearch'}
        </a>
        </div>
        <div class="col-xs-4">
        <a href="{l s='http://addons.prestashop.com/en/administrative-tools/21217-bo-customizer-survey-and-access-all-your-data.html' mod='ambjolisearch'}" target="_blank">
            <img class="lazy" data-original="http://medias2.prestastore.com/img/p/../pico/21217.jpg" alt="BO Customizer : consulter toutes vos données" height="57" width="57" src="http://medias2.prestastore.com/img/p/../pico/21217.jpg" style="display: inline-block;"> {l s='BO Customizer : survey and access all your data' mod='ambjolisearch'}
        </a>
        </div>
        <div class="col-xs-4">
        <a href="{l s='http://addons.prestashop.com/en/analytics-statistics/14973-sales-and-taxes-summary.html' mod='ambjolisearch'}" target="_blank">
            <img class="lazy" data-original="http://medias2.prestastore.com/img/p/../pico/14973.jpg" alt="BO Customizer : consulter toutes vos données" height="57" width="57" src="http://medias2.prestastore.com/img/p/../pico/14973.jpg" style="display: inline-block;"> {l s='Sales and Taxes Summary' mod='ambjolisearch'}
        </a>
        </div>
        <div class="col-xs-12 text-center"><br /><br /><a href="{l s='https://addons.prestashop.com/en/114_ambris-informatique' mod='ambjolisearch'}">{l s='Find out about the other services Ambris can provide you' mod='ambjolisearch'}</a></div>
    </div>

</div>