{if count($bestkit_pfeatures.products.split_by_features)}
    <div id="bestkit_pfeatures_wrapper" class="bestkit_pfeatures_wrapper">
        {if $product.features }
            {foreach from=$product.features item=feature name=features}
                {if $feature.name == "Senteur"}
                <div id="getsent">
                {if $feature.value == "Bois d'Olivier"}
                            <div style="background:url('../../themes/classic-child/assets/img/bois-olivier-compagnie-provence.png')"
                                 class="sentsicon"></div>



                        <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span> <img src="{$urls.img_url}chevrontbot.png" id="chevbottom"> <div class="descri-sent">{l s='Des notes de bois chauffé au soleil exaltées par une touche de fraîcheur hespéridée et camphrée pour le plus sensuel des parfums.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Verveine Fraîche"}
                    <div style="background:url('../../themes/classic-child/assets/img/verveine-compagnie-provence.png');"
                         class="sentsicon"></div>



                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span> <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un parfum pétillant et acidulé aux notes de verveine fraîche délicieusement citronnée.' d='Shop.Theme.Special'}</div>
                    </div>

                {elseif $feature.value == "Méditerranée"}
                    <div style="background:url('../../themes/classic-child/assets/img/mediterranee-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Prenez un véritable bol d\'air de Méditerranée avec ce parfum qui allie la fraîcheur des embruns aux senteurs délicates de fleurs ensoleillées.' d='Shop.Theme.Special'}</div>
                    </div>

                {elseif $feature.value == "Rose Sauvage"}
                    <div style="background:url('../../themes/classic-child/assets/img/rose-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Véritable symbole d\'amour, on se plonge avec délices dans ce parfum volupteux et sensuel aux notes de roses fraîchement coupées.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Figue de Provence"}
                    <div style="background:url('../../themes/classic-child/assets/img/figue-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='La figue de Provence se raconte aux travers de ce parfum suave aux notes de fruits gourmands.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Lavande Aromatique"}
                    <div style="background:url('../../themes/classic-child/assets/img/encens-lavande-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='La lavande est une odeur familière qui vous accompagne depuis toujours elle se dévoile ici dans un parfum aromatique tout en élégance.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Fleur d'Oranger"}
                    <div style="background:url('../../themes/classic-child/assets/img/fleur-oranger-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Parfum sensuel et féminin par excellence, laissez-vous envouter par les notes délicatement amères de fleur d\'oranger.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Pamplemousse Rose"}
                    <div style="background:url('../../themes/classic-child/assets/img/pamplemousse-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Gorgé de soleil et plein de vivacité, on fond pour ce parfum de fruits frais aux notes fraîches de pamplemousse rose.' d='Shop.Theme.Special'}</div>
                    </div>

                {elseif $feature.value == "Anis Patchouli"}
                   <div style="background:url('../../themes/classic-child/assets/img/anis-patchouli-compagnie-provence.png');"
                          class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Laissez-vous captiver par un parfum où la fraîcheur aromatique de l\'anis contraste avec la sensualité du patchouli.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Jasmin Noir"}
                    <div style="background:url('../../themes/classic-child/assets/img/jasmin-noir-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un parfum envoûtant où le jasmin solaire illumine les notes épicées de poivre noir.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Fleur de Coton"}
                    <div style="background:url('../../themes/classic-child/assets/img/fleur-coton-compagnie-provence.png');"
                         class="sentsicon"></div>
                        <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                        <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                        <div class="descri-sent">{l s='Fleuri, délicat et poudré, ce parfum évoque toute la douceur et le confort du coton.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Sans Parfum"}
                    <div style="background:url('../../themes/classic-child/assets/img/sans-parfum-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Une formule sans parfum particulièrement douce et adaptée aux peaux délicates.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Thé Noir"}
                    <div style="background:url('../../themes/classic-child/assets/img/the-noir-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Mettez-vous à l\'heure du thé grâce à la puissance d\'un parfum doux qui associe notes profondes de thé noir et notes fruités de mûre.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Thé Blanc"}
                    <div style="background:url('../..//themes/classic-child/assets/img/the-blanc-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Retrouvez l\'authenticité du thé blanc dans un parfum raffiné où les notes pures d\'infusion se mêlent à la fraîcheur des notes hespéridées.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Musc Blanc"}
                    <div style="background:url('../../themes/classic-child/assets/img/musc-blanc-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='La délicatesse des draps séchant au soleil a inspiré ce parfum où la douceur de l\'amande se mêle aux notes poudrées du musc blanc.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Verveine Verte"}
                    <div style="background:url('../../themes/classic-child/assets/img/verveine-verte-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un parfum de fraîcheur où la sensation vivifiante du citron contraste avec la note pétillante d\'une verveine herbacée.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Laurier Rose"}
                    <div style="background:url('../../themes/classic-child/assets/img/laurier-rose-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un parfum où le délicat effet poudré de la fleur de laurier de méditerranée se marie parfaitement à la note végétale de ses feuilles.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Amande Douce"}
                    <div style="background:url('../../themes/classic-child/assets/img/amande-douce-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Tout le réconfort d\'un parfum aux notes gourmandes d\'amande mêlées aux senteurs délicates de fleurs blanches de jasmin et d\'amandier.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Fleur de Mimosa"}
                    <div style="background:url('../../themes/classic-child/assets/img/mimosa-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Plongez-vous dans les souvenirs d\'une balade en Provence avec cette senteur solaire et optimiste aux notes fleuries de mimosa d\'hiver.' d='Shop.Theme.Special'}</div>
                    </div>

                {elseif $feature.value == "Grand Air"}
                    <div style="background:url('../../themes/classic-child/assets/img/grand-air-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un parfum qui mêle la fraîcheur des notes de l’aloe vera à la douceur des notes de fleurs de lin. Un vrai coup de frais dans la maison !' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Karité"}
                    <div style="background:url('../../themes/classic-child/assets/img/absolu-karite-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='On rêve de ce parfum cocon qui apaise les sens grâce à ses notes délicates de fleurs blanches sur un fond doux musqué.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Delicate"}
                    <div style="background:url('../../themes/classic-child/assets/img/delicate-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Le raffinement d\'un parfum frais où se retrouvent arômes boisés et notes hespéridées.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Cashmere"}
                     <div style="background:url('../../themes/classic-child/assets/img/cashmere-compagnie-provence.png');"
                               class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Confort, luxe et élégance se retrouvent dans ce parfum chaleureux aux notes boisées et épicées dans lequel on n\'a qu\'une envie : se lover.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Agrumes Pétillants"}
                    <div style="background:url('../../themes/classic-child/assets/img/agrumes-petillants-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Solaire et joyeux, ce parfum aux notes d\'agrumes pétillants est un véritable souffle d\'optimisme.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Grooming for Men"}
                    <div style="background:url('../../themes/classic-child/assets/img/grooming-for-men-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un parfum aromatique vivifiant que dynamisent des notes de bois épicés.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "Mandarine Aromatique"}
                    <div style="background:url('../../themes/classic-child/assets/img/mandarine-aromatique-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Un jaillissement d\'optimisme dans un parfum qui associe l\’huile essentielle de mandarine acidulée à la fraîcheur aromatique de l’huile essentielle de romarin.' d='Shop.Theme.Special'}</div>
                    </div>
                {elseif $feature.value == "A l'eau de rosée"}
                    <div style="background:url('../../themes/classic-child/assets/img/rose-compagnie-provence.png');"
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                    <div class="descri-sent">{l s='Des agrumes jaillissants en tête puis une jolie sensation de fraîcheur apportée par un accord surprenant de légèreté, pivoine-basilic.' d='Shop.Theme.Special'}</div>
                    </div>
                {else}
                    <div style=""
                         class="sentsicon"></div>
                    <span class="text-sent" id="current-sent">{$feature.value|escape:'html':'UTF-8'} </span>
                    <img src="{$urls.img_url}chevrontbot.png" id="chevbottom">
                   </div>

                {/if}





                {/if}
            {/foreach}
        {/if}


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
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                                <span class="descri-sent">{l s='' d='Shop.Theme.Special'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Méditerranée"}
                                <div style="background:url('../../themes/classic-child/assets/img/mediterranee-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                                <span class="descri-sent">{l s='' d='Shop.Theme.Special'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Rose Sauvage"}
                                <div style="background:url('../../themes/classic-child/assets/img/rose-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Figue de Provence"}
                                <div style="background:url('../../themes/classic-child/assets/img/figue-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Lavande Aromatique"}
                                <div style="background:url('../../themes/classic-child/assets/img/encens-lavande-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Fleur d'Oranger"}
                                <div style="background:url('../../themes/classic-child/assets/img/fleur-oranger-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Pamplemousse Rose"}
                                <div style="background:url('../../themes/classic-child/assets/img/pamplemousse-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Anis Patchouli"}
                                <div style="background:url('../../themes/classic-child/assets/img/anis-patchouli-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Jasmin Noir"}
                                <div style="background:url('../../themes/classic-child/assets/img/jasmin-noir-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Fleur de Coton"}
                                <div style="background:url('../../themes/classic-child/assets/img/fleur-coton-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Sans Parfum"}
                                <div style="background:url('../../themes/classic-child/assets/img/sans-parfum-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Thé Noir"}
                                <div style="background:url('../../themes/classic-child/assets/img/the-noir-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Thé Blanc"}
                                <div style="background:url('../..//themes/classic-child/assets/img/the-blanc-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Bois d'Olivier"}
                                <div style="background:url('../../themes/classic-child/assets/img/bois-olivier-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Musc Blanc"}
                                <div style="background:url('../../themes/classic-child/assets/img/musc-blanc-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Verveine Verte"}
                                <div style="background:url('../../themes/classic-child/assets/img/verveine-verte-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Laurier Rose"}
                                <div style="background:url('../../themes/classic-child/assets/img/laurier-rose-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Amande Douce"}
                                <div style="background:url('../../themes/classic-child/assets/img/amande-douce-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Fleur de Mimosa"}
                                <div style="background:url('../../themes/classic-child/assets/img/mimosa-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Grand Air"}
                                <div style="background:url('../../themes/classic-child/assets/img/grand-air-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Karité"}
                                <div style="background:url('../../themes/classic-child/assets/img/absolu-karite-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Delicate"}
                                <div style="background:url('../../themes/classic-child/assets/img/delicate-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Cashmere"}
                                <div style="background:url('../../themes/classic-child/assets/img/cashmere-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Agrumes Pétillants"}
                                <div style="background:url('../../themes/classic-child/assets/img/agrumes-petillants-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Grooming for Men"}
                                <div style="background:url('../../themes/classic-child/assets/img/grooming-for-men-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "Mandarine Aromatique"}
                                <div style="background:url('../../themes/classic-child/assets/img/mandarine-aromatique-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {elseif ($bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8') == "A l'eau de rosée"}
                                <div style="background:url('../../themes/classic-child/assets/img/rose-compagnie-provence.png');"
                                     class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                                {else}

                            <div style=""
                            class="sentsicon"></div><span class="text-sent">{$bestkit_pfeatures_item.value|escape:'htmlall':'UTF-8'}</span>
                            {/if}

                        </a>
                    </li>
                {/foreach}
            </ul>
        {/if}
    {/foreach}
    </div>
{/if}
<script>
    document.querySelector("#getsent").onclick = function () {
        if (window.getComputedStyle(document.querySelector('#bestkit_pfeature_1')).display == 'none') {
            document.querySelector("#bestkit_pfeature_1").style.display = "block";
            document.querySelector(".add").style.display = "none";
        } else {
            document.querySelector("#bestkit_pfeature_1").style.display = "none";
            document.querySelector(".add").style.display = "block";
        }
    }


</script>
