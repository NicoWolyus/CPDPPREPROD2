{if count($bestkit_pfeatures.products.split_by_features)}
    <div id="bestkit_pfeatures_wrapper" class="bestkit_pfeatures_wrapper">
        {foreach $bestkit_pfeatures.products.split_by_features as $id_feature => $bestkit_pfeatures_items}
            {if count($bestkit_pfeatures_items)}
                <p id="bestkit_pfeature_label_{$id_feature|intval}"
                   class="bestkit_pfeature_label">{$bestkit_pfeatures_items[0].label|escape:'htmlall':'UTF-8'}</p>
                <ul id="bestkit_pfeature_{$id_feature|intval}"
                    class="bestkit_pfeature{if $bestkit_pfeatures_items[0].is_color} is_color{/if}">
                    {foreach $bestkit_pfeatures_items as $bestkit_pfeatures_item}
                        <li id="bestkit_pitem_{$id_feature|intval}_{$bestkit_pfeatures_item.id_product|intval}"
                            class="bestkit_pitem">
                            <a href="{$bestkit_pfeatures_item.link|escape:'htmlall':'UTF-8'}"
                               title="{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}"
                               {if !empty($bestkit_pfeatures_item.hex_value)}style="background-color:{$bestkit_pfeatures_item.hex_value|escape:'htmlall':'UTF-8'}!important"{/if}>

                                {if ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Verveine Fraîche"}

                                    <div style="background:url('../../themes/classic-child/assets/img/verveine-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Méditerranée"}

                                    <div style="background:url('../../themes/classic-child/assets/img/mediterranee-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Rose Sauvage"}

                                    <div style="background:url('../../themes/classic-child/assets/img/rose-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Figue de Provence"}

                                    <div style="background:url('../../themes/classic-child/assets/img/figue-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Lavande Aromatique"}
                                    <div style="background:url('../../themes/classic-child/assets/img/encens-lavande-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Fleur d'Oranger"}
                                    <div style="background:url('../../themes/classic-child/assets/img/fleur-oranger-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Pamplemousse Rose"}
                                    <div style="background:url('../../themes/classic-child/assets/img/pamplemousse-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Anis Patchouli"}
                                    <div style="background:url('../../themes/classic-child/assets/img/anis-patchouli-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Jasmin Noir"}
                                    <div style="background:url('../../themes/classic-child/assets/img/jasmin-noir-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Fleur de Coton"}
                                    <div style="background:url('../../themes/classic-child/assets/img/fleur-coton-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Sans Parfum"}
                                    <div style="background:url('../../themes/classic-child/assets/img/sans-parfum-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Thé Noir"}
                                    <div style="background:url('../../themes/classic-child/assets/img/the-noir-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Thé Blanc"}
                                    <div style="background:url('../..//themes/classic-child/assets/img/the-blanc-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Bois d'Olivier"}
                                    <div style="background:url('../../themes/classic-child/assets/img/bois-olivier-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Musc Blanc"}
                                    <div style="background:url('../../themes/classic-child/assets/img/musc-blanc-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Verveine Verte"}
                                    <div style="background:url('../../themes/classic-child/assets/img/verveine-verte-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Laurier Rose"}
                                    <div style="background:url('../../themes/classic-child/assets/img/laurier-rose-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Amande Douce"}
                                    <div style="background:url('../../themes/classic-child/assets/img/amande-douce-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Fleur de Mimosa"}
                                    <div style="background:url('../../themes/classic-child/assets/img/mimosa-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Grand Air"}
                                    <div style="background:url('../../themes/classic-child/assets/img/grand-air-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Karité"}
                                    <div style="background:url('../../themes/classic-child/assets/img/absolu-karite-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Delicate"}
                                    <div style="background:url('../../themes/classic-child/assets/img/delicate-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Cashmere"}
                                    <div style="background:url('../../themes/classic-child/assets/img/cashmere-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Agrumes Pétillants"}
                                    <div style="background:url('../../themes/classic-child/assets/img/agrumes-petillants-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Grooming for Men"}
                                    <div style="background:url('../../themes/classic-child/assets/img/grooming-for-men-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Mandarine Aromatique"}
                                    <div style="background:url('../../themes/classic-child/assets/img/the-noir-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "A l'eau de rosée"}
                                    <div style="background:url('../../themes/classic-child/assets/img/the-noir-compagnie-provence.png');"
                                         class="sentsicon"></div>
                                {/if}
                                <span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            </a>
                        </li>
                    {/foreach}
                </ul>
            {/if}
        {/foreach}
    </div>
{/if}