{*
* Modulo Product Combinations
*
* @author    Giuseppe Tripiciano <admin@areaunix.org>
* @copyright Copyright (c) 2018 Giuseppe Tripiciano
* @license   You cannot redistribute or resell this code.
*
*}

<div id="bollinicontainer" class="clearfix">
    <span class="control-label">{l s='Variants:' mod='productascombinations'}</span>
    <div id="bollinitabs">
            <div id="bollinitab" class="clearfix">
                {foreach $pc_combs as $comb}
                    <div class="bordobollino imgsel{$comb->id} {if $comb->id==$product['id']}bollinoselected{else}bollinounselected{/if}"
                         data-url="{$link->getProductLink($comb->id, $comb->link_rewrite, null, null, null, null, $comb->cache_default_attribute)}">
                        <div class="{if $comb->id == $product['id']}selectedtriangle{/if} triangle{$comb->id}"></div>
                        <img width="48" height="48" src="{$comb->combimage}" alt="{$comb->name}"/>
                    </div>
                {/foreach}
            </div>
    </div>
</div>