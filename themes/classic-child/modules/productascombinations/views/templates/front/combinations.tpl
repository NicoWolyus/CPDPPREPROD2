{*
* Modulo Product Combinations
*
* @author    Giuseppe Tripiciano <admin@areaunix.org>
* @copyright Copyright (c) 2018 Giuseppe Tripiciano
* @license   You cannot redistribute or resell this code.
*
*}

{if $product.dwf_nbformat} <div class="nb-formats">{l s='Existe en' d='Shop.Theme.Special'} {$product.dwf_nbformat} {l s='formats' d='Shop.Theme.Special'}</div>{/if}

<div id="bollinicontainer" class="clearfix">
    <span class="control-label">{l s='Variants:' mod='productascombinations'}</span>
    <div id="bollinitabs">
            <div id="bollinitab" class="clearfix">
                {foreach $pc_combs as $comb}

                    <div class="bordobollino imgsel{$comb->id} {if $comb->id==$product['id']}bollinoselected{else}bollinounselected{/if}"
                         data-url="{$link->getProductLink($comb->id, $comb->link_rewrite, null, null, null, null, $comb->cache_default_attribute)}">
                        <div class="{if $comb->id == $product['id']}selectedtriangle{/if}{$comb->id}"></div>
                        <img width="75" height="75" src="{$comb->combimage}" alt="{$comb->name}"/>
                    </div>
                {/foreach}
            </div>
    </div>
</div>