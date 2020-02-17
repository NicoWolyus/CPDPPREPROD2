{* https://material.io/icons/ *}
{if $quote_active}
<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="returns2-link" href="{url entity='module' name='kerawen' controller='quotelist'}">
   <span class="link-item">
      <i class="material-icons">&#xE065;</i>
      {l s='My quotes' mod='kerawen'}
   </span>
</a>
{/if}
{if $giftcard_total > 0}
<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="returns-link" href="{url entity='module' name='kerawen' controller='giftcard'}">
  <span class="link-item">
    <i class="material-icons">&#xE8F6;</i>
    {l s='Gift card' mod='kerawen'}
  </span>
</a>
{/if}