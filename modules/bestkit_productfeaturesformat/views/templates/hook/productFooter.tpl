{if count($bestkit_pfeatures.products.split_by_features)}
    <div id="bestkit_pfeatures_wrapper" class="bestkit_pfeatures_wrapper">
        {foreach $bestkit_pfeatures.products.split_by_features as $id_feature => $bestkit_pfeatures_items}
            {if count($bestkit_pfeatures_items)}
                <p id="bestkit_pfeature_label_{$id_feature|intval}" class="bestkit_pfeature_label">{$bestkit_pfeatures_items[0].label|escape:'htmlall':'UTF-8'}</p>
                <ul id="bestkit_pfeature_{$id_feature|intval}" class="bestkit_pfeature{if $bestkit_pfeatures_items[0].is_color} is_color{/if}">
                    {foreach $bestkit_pfeatures_items as $bestkit_pfeatures_item}
                        <li id="bestkit_pitem_{$id_feature|intval}_{$bestkit_pfeatures_item.id_product|intval}" class="bestkit_pitem">
                            <a href="{$bestkit_pfeatures_item.link|escape:'htmlall':'UTF-8'}" title="{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}" {if !empty($bestkit_pfeatures_item.hex_value)}style="background-color:{$bestkit_pfeatures_item.hex_value|escape:'htmlall':'UTF-8'}!important"{/if}>
                                <span>{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            </a>
                        </li>
                    {/foreach}
                </ul>
            {/if}
        {/foreach}
    </div>
{/if}