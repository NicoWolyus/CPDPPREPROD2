{**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{extends file='page.tpl'}

{block name='page_title'}
    {$cms.meta_title}
{/block}

{block name='page_content_container'}
    <section id="content" class="page-content page-cms page-cms-{$cms.id}">
        {if $page.page_name == 'cms'}
            {if ($cms.id == 38)}
                <div class="wearecompany">
                    <div class="head-title">
                        <h1> {l s='Bienvenue dans notre Provence' d='Shop.Theme.Special'}</h1>
                        <h2>{l s='Depuis 1990, la Compagnie de Provence propose des soins naturels pour le visage, le corps et la maison' d='Shop.Theme.Special'}</h2>

                    </div>


                    <!-- Page body -->
                    <div class="row">


                        <!-- Option: data-autoplay, data-speed -->
                        <div class="timeline" data-autoplay="5000" data-speed="700">
                            <!-- Swiper timeline -->
                            <div class="swiper-container timeline-dates">
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide">
                                        <div>1990</div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div>1999</div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div>2013</div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div>2017</div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div>2019</div>
                                    </div>

                                </div>


                            </div>

                            <div class="swiper-container timeline-contents">
                                <div class="swiper-wrapper">

                                    <div class="swiper-slide">

                                        <div class="text-chrono">
                                            <h5>{l s='Il était une fois...' d='Shop.Theme.Special'}</h5>
                                            <p>{l s='Il était une fois, en 1990, deux amis Marseillais, passionnés de design. Soucieux de préserver le traditionnel cube de savon de Marseille et sa place inégalée dans le patrimoine culturel provençal, ils décident de partager ce savoir-faire local avec le plus grand nombre.' d='Shop.Theme.Special'}</p>
                                            <p>{l s='Inspirée par les mille et une vertu de l’iconique cube, La Compagnie de Provence propose depuis ses débuts des soins enrichis d’huiles végétales, aux formules courtes, naturelles et efficaces, qui accompagnent ses consommateurs dans toutes les étapes du quotidien.' d='Shop.Theme.Special'}</p>
                                        </div>
                                        <img src="{$urls.img_url}chronologie-creation-1.jpg" class="chrono-img">
                                    </div>

                                    <div class="swiper-slide">

                                        <div class="text-chrono">

                                            <h5>{l s='Et le savon liquide de Marseille est né' d='Shop.Theme.Special'}</h5>
                                            <p>{l s='Nos deux amis ne se sont pas arrêtés là ! Ce produit iconique méritait d’être réinventé pour s’adapter au mieux aux nouveaux modes de vie des citadins.En 1999, La Compagnie de Provence innove en étant la première à proposer une version liquide inédite du savon de Marseille' d='Shop.Theme.Special'}</p>
                                            <p>{l s='Une pompe pratique pour délivrer la juste dose, un savon liquide fabriqué à l’ancienne en chaudron à partir d’huiles végétales, un flacon sirop en verre ultra-design aussi pratique qu’esthétique : le premier savon liquide de Marseille était né !' d='Shop.Theme.Special'}</p>
                                        </div>
                                        <img src="{$urls.img_url}chronologie-creation-2.jpg" class="chrono-img">
                                    </div>
                                    <div class="swiper-slide">

                                        <div class="text-chrono">

                                            <h5>{l s='Un savoir-faire Dermo-Cosmétique' d='Shop.Theme.Special'}</h5>
                                            <p>{l s='En 2013, la société s’adosse à un groupe dermatologique Italien pour accélérer son développement et répondre toujours plus aux exigences des consommatrices en matière de qualité et d’innovation.' d='Shop.Theme.Special'}</p>
                                            <p>{l s='Tous nos produits sont testés sous contrôle dermatologique, dans le cadre d’études cliniques réalisées dans nos laboratoires, qui ont démontré leur performance et leur tolérance cosmétique.' d='Shop.Theme.Special'}</p>
                                            <p>{l s='Nous attachons une attention particulière à la formulation de nos produits en choisissant de supprimer les ingrédients controversés : 100% de nos produits sont formulés SANS Parabens, Huiles minérales, Aluminium, Sodium Laureth Sulfate, Triclosan. Retirer certains ingrédients suppose beaucoup de souplesse pour conserver la sensorialité des textures, avec la juste dose de conservateurs. Juste l’essentiel, sans superflu.' d='Shop.Theme.Special'}</p>
                                        </div>
                                        <img src="{$urls.img_url}chronologie-creation-3.jpg" class="chrono-img">
                                    </div>
                                    <div class="swiper-slide">

                                        <div class="text-chrono">

                                            <h5>{l s='Une expertise des huiles végétales' d='Shop.Theme.Special'}</h5>
                                            <p>{l s='À venir' d='Shop.Theme.Special'}</p>
                                        </div>
                                        <img src="{$urls.img_url}chronologie-creation-3.jpg" class="chrono-img">
                                    </div>
                                    <div class="swiper-slide">

                                        <div class="text-chrono">

                                            <h5>{l s='À venir' d='Shop.Theme.Special'}</h5>
                                            <p>{l s='À venir' d='Shop.Theme.Special'}</p>
                                        </div>
                                        <img src="{$urls.img_url}chronologie-creation-1.jpg" class="chrono-img">
                                    </div>
                                    <div class="timeline-buttons-container">
                                        <div class="timeline-button-next"></div>
                                        <div class="timeline-button-prev"></div>
                                    </div>

                                </div>         <!-- Add Arrows -->

                            </div>


                        </div>

<div class="nosingredients">
                        <div class="head-title">
                            <h1> {l s='Des ingrédients soigneusement sélectionnées' d='Shop.Theme.Special'}</h1>
                            <h2>{l s='Pour des formules toujours plus naturelles, efficcaces et sensorielles' d='Shop.Theme.Special'}</h2>

                        </div>

    <div class="disclaimoli">
        <h5> {l s='Mais pourquoi nos huiles vététalesq sont-elles les meilleures ?'}</h5>
        <span> {l s='Au fil des ans, La Compagnie de Provence a noué des liens étroits avec des producteurs locaux, à la recherche d’ingrédients de haute qualité pour offrir l’essence de la Provence en flacon.' d='Shop.theme.Special'}
                              </span></div>
                        <div class="allslide-ingredient container">

                            <div class="swiper-containeroli">


                                <div class="swiper-wrapper">

                                    <div class="blockingredient swiper-slide" id="huioli">
                                        <div class="ingrecontent-marque"><h3 class="ingre-title">
                                                {l s="Huile d'olive" d='Shop.Theme.Special'}
                                            </h3>
                                            <div class="ingre-descri">
                                                {l s="Véritable indispensable de la beauté au naturel, les vertues de l'huile d'odivve sont vantées depuis des millénaires. Riche en acides gras, antioxydants polyphénols, et vitamines A, C, D et E, elle permet d’adoucir, protéger et nourir la peau. La vitamine E favorise également la lutte contre les effets du vieildivssement de la peau car elle aide au renouvellement cellulaire." d='Shop.Theme.Special'}
                                            </div>
                                        </div>
                                    </div>


                                    <div class="blockingredient swiper-slide" id="huibioviran">
                                        <div class="ingrecontent-marque"><h3 class="ingre-title">
                                                {l s="Huile d'olive bio Château Virant" d='Shop.Theme.Special'}
                                            </h3>
                                            <div class="ingre-descri">
                                                {l s="Notre huile d’olive biologique provient du Château Virant où la famille Cheylan produit une huile d’odivve d’exception depuis plusieurs générations. Garants de la tradition et de la quadivté, ils cultivent au fil des saison des vergers à odivves respectant les méthodes de récolte et d’extraction les plus précises." d='Shop.Theme.Special'}
                                            </div>
                                        </div>
                                    </div>


                                    <div class="blockingredient swiper-slide" id="amabalens">
                                        <div class="ingrecontent-marque"><h3 class="ingre-title">
                                                {l s="Huile d'amande douce de Valensole" d='Shop.Theme.Special'}
                                            </h3>
                                            <div class="ingre-descri">
                                                {l s="Nos amandes sont cultivées sur le Plateau de Valensole par la famille Jaubert, entreprise famidivale située en Provence depuis plus d’un siècle. Ils proposent une amande de Provence recherchée pour sa quadivté tout au long de l’année. Le tout dans le respect d\’une agriculture raisonnée. Les amandes sont ensuite transformées en une huile réputée pour son action apaisante et adoucissante. Idéale dans nos soins cosmétiques." d='Shop.Theme.Special'}
                                            </div>
                                        </div>
                                    </div>




                                </div>
                                <div class="swiper-button-next swiper-button-next-ing"></div>
                            </div>
                        </div>


                    </div>

                    </div>
                </div>
                </div>
                </div>
                <div class="grasse">
                    <div class="col-md-12">
                        <div class="container">
                            <div class="col-md-6">
                                <div class="h3"><span id="ange">{l s='Grasse'}</span>
                                    {l s='Capitale Mondiale du parfum' d='Shop.Theme.Shop'}
                                </div>
                                <p>
                                    {l s='Les parfums qui composent nos produits sont imaginés et créés à Grasse.' d='Shop.Theme.Shop'}
                                </p>
                                <img src="{$urls.img_url}mapfr.png" class="mapfr">
                            </div>

                            <div class="col-md-6">

                                <div id="static-block-wrapper_4" class="static_block_content"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mining">
                    <img src="{$urls.img_url}mini.png" class="minimarque">
                    <div class="minitext">{l s='Minimum'}</div>
                    <div class="numbermarque">95%</div>
                    <div class="ingredmarque ingredmarque1">{l s='d\'ingredients' d='Shop.Theme.Special'}</div>
                    <div class="ingredmarque ingredmarque2">{l s='d\'origine' d='Shop.Theme.Special'}</div>
                    <div class="ingredmarque ingredmarque3">{l s='naturelle' d='Shop.Theme.Special'}</div>

                </div>
                </div>
                <div class="formulemarque">
                    <div class="h3 tittleformule">{l s='Des formules naturelles, sensorielles et respecteuses de l\'environnement' d='Shop.Theme.Special'}</div>

                    <div class="textformule">{l s='La majorité de nos produits sont composés à minimum de 95% d’ingrédients d’origine naturelle pour prendre soin de vous et de votre intérieur, naturellement. Notre ambition est de proposer les formules les plus naturelles possibles, sans compromis sur la sensorialité et l’efficacité pour des soins cosmétiques 100% plaisir.

Ayant conscience de vivre dans un écrin de nature privilégié entre terre et mer, nous sommes d’autant plus sensibles à notre impact environnemental. Nous travaillons ainsi sur la biodégradabilité de nos produits rincés : les formules de nos soins douche, de nos liquides vaisselle et de nos cubes de savon de Marseille sont biodégradables, et retournent dans la nature sans laisser de traces !

En bref, nous concevons des produits sérieux qui ne se prennent pas au sérieux.
' d='Shop.Theme.Special'}</div>
                    <div class="lastprom">{l s='C\'est ça la promesse de la Compagnie de Provence !' d='Shop.Theme.Special'}</div>


                </div>
                <div class="didyouknow">
                    <div class="container">
                        <div class="col-md-12"
                        <div class="col-md-6">
                            <div class="didyou">{l s='Le saviez-vous ?' d='Shop.Theme.Special'}</div>
                            <div class="didyoubig">{l s='Les flacons de nos savons liquides devenus cultes sont en réalité des flacons sirop dont l\'utilisaiton a été détournée' d='Shop.Theme.Special'}</div>
                            <div class="didyoumin">{l s='À votre tour de leur imagine une nouvelle vie !' d='Shop.Theme.Special'}</div>
                        </div>
                        <div class="col-md-6">
                        </div>
                    </div>
                </div>
                <div class="block-durable container">
                    <div class="durable-title">{l s='Un design durable' d='Shop.Theme.Special'}</div>
                    <div class="durable-sub-title">{l s='Design (N.M.) : d\'une esétique moderne et fonctionnelle' d='Shop.Theme.Special'}</div>
                    <div class="col-md-12 durabtop">
                        <div class="col-md-6 beaut">
                            <div class="tittle-beaut">{l s='Nous aimons les beaux objets,' d='Shop.Theme.Special'}</div>
                            <p>   {l s='C’est pour cette raison que depuis 30 ans, les flacons iconiques de nos savons liquides de Marseille 500ml sont en verre, recyclable à l’infini, et reconnu comme étant idéal pour préserver la qualité des formules. Car nous ne nous saurions nous contenter de vous proposer de belles formules, l’esthétique de nos flacons les rend encore plus désirables.' d='Shop.Theme.Special'}
                            </p>
                            <p>
                                {l s='Grâce aux recharges 1 litre, nos flacons sont rechargeables, réduisant ainsi l’empreinte sur l’environnement (et sur votre portefeuille !)
Et pour ceux qui préfèrent des flacons à toute épreuve, nous déclinons aussi nos iconiques flacons sirop en format 300ml en plastique recyclable - et incassable. Nous ne sommes pas parfaits mais nous travaillons chaque jour pour nous améliorer, et d’ici peu les flacons en plastique recyclé feront leur entrée.' d='Shop.Theme.Special'}
                            </p>
                            <p>
                                {l s='Le design est beau...mais pas seulement ! Il est, par définition, pratique.
En étant parmi les premiers à avoir utilisé le flacon pompe, nous avons créé une nouvelle gestuelle pour se laver les mains, adaptée à des modes de vie contemporains.
Et pour mieux vous emballer, nos étuis sont issus de forêts gérées durablement afin de garantir un emballage responsable.
' d='Shop.Theme.Special'}</p>


                        </div>

                        <div class="col-md-6 slide-durable">

                            <div class="swiper-containerdesign">
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide"><img src="{$urls.img_url}design-durable-1.jpg"
                                                                   class="durable-img"></div>
                                    <div class="swiper-slide"><img src="{$urls.img_url}design-durable-2.jpg"
                                                                   class="durable-img"></div>
                                    <div class="swiper-slide"><img src="{$urls.img_url}design-durable-3.jpg"
                                                                   class="durable-img"></div>
                                    <div class="swiper-slide"><img src="{$urls.img_url}design-durable-4.jpg"
                                                                   class="durable-img"></div>

                                </div>
                                <!-- Add Pagination -->

                                <!-- Add Arrows -->
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>

                            <!-- Swiper JS -->
                            <script src="../package/js/swiper.min.js"></script>

                            <!-- Initialize Swiper -->
                            <script>
                                var swiper = new Swiper('.swiper-containerdesign', {
                                    slidesPerView: 2,
                                    spaceBetween: 20,

                                    pagination: {
                                        el: '.swiper-pagination',
                                        type: 'fraction',
                                    },
                                    navigation: {
                                        nextEl: '.swiper-button-next',
                                        prevEl: '.swiper-button-prev',
                                    },
                                });
                            </script>


                        </div>
                    </div>

                    </div>
                    <div class="lifestyle col-md-12">
                        <div class="col-md-6">
                            <img src="{$urls.img_url}lifestyle.jpg"
                                 class="durable-img">
                        </div>
                        <div class="col-md-6 styleall">
                            <div class="tittlestyle">{l s='Le lifestyle de Compagnie de Provence' d='Shop.Theme.Special'}</div>
                            <div class="text-lifestyle">



                                <p> {l s='La Compagnie de Provence, c’est bien' d='Shop.Theme.Special'} {l s='plus qu’une marque de cosmétiques,' d='Shop.Theme.Special'}{l s="c'est un lifestyle. Un style de vie inspiré d’une Provence contemporaine, solaire, joyeuse, comme vous !" d='Shop.Theme.Special'}</p>

                                <div class="strong">
                                <p> {l s='L’art de bien recevoir entre simplicité et élégance' d='Shop.Theme.Special'}</p>
                                <p> {l s='Faire plaisir et se faire plaisir' d='Shop.Theme.Special'}</p>
                                <p> {l s='Un mode de vie entre intérieur et extérieur' d='Shop.Theme.Special'}</p>
                                <p> {l s='Un environnement solaire qui inspire des produits colorés, joyeux par nature' d='Shop.Theme.Special'}</p>
                                </div>
                            </div>
                        </div>

                    </div>

<div class="col-md-12 manifesto">
    <img src="{$urls.img_url}MANIFESTO.png" id="manifesto-img"
         class="pointleve">
   <div class="mantitle"> {l s='Manifesto'}</div>
    <div class="swiper-container2">
        <div class="swiper-wrapper">
            <div class="swiper-slide"> {l s='"Nous défendons une autre vision de la Provence… avec le sourire !"' d='Shop.Theme.Special'}</div>
            <div class="swiper-slide"> {l s='"Nous croyons à l’alliance du beau et du bon."' d='Shop.Theme.Special'}</div>
            <div class="swiper-slide">{l s='"Nous privilégions les ingrédients naturels de Provence, tout en garantissant des textures 100% plaisir, sans compromis sur l’efficacité."' d='Shop.Theme.Special'}</div>
            <div class="swiper-slide"> {l s='"Nous pensons que la beauté doit rester accessible. "' d='Shop.Theme.Special'}</div>
            <div class="swiper-slide"> {l s='"Nous connaissons les huiles végétales mieux que personne et travaillons en partenariat avec des producteurs locaux pour sourcer des ingrédients et des actifs de haute qualité. "' d='Shop.Theme.Special'}</div>
            <div class="swiper-slide">{l s='"Nous concevons des produits sérieux qui ne se prennent pas au sérieux !"' d='Shop.Theme.Special'}</div>

        </div>
        <!-- Add Pagination -->

        <!-- Add Arrows -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>

    <!-- Swiper JS -->
    <script src="../package/js/swiper.min.js"></script>

    <!-- Initialize Swiper -->
    <script>
        var swiper2 = new Swiper('.swiper-container2', {
            pagination: {
                el: '.swiper-pagination',
                type: 'fraction',
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>

</div>




                    <script>

                        !function ($) {

                            "use strict";

                            /**
                             * Swiper slider - Timeline
                             */
                            var container = $('.timeline');

                            var timelineContents = new Swiper('.timeline-contents', {
                                navigation: {
                                    nextEl: '.timeline-button-next',
                                    prevEl: '.timeline-button-prev',
                                },
                                grabCursor: true,
                                spaceBetween: 10,


                                speed: (container.data('speed')) ? parseInt(container.data('speed'), 10) : 700,
                            });
                            var timelineDates = new Swiper('.timeline-dates', {
                                spaceBetween: 150,
                                centeredSlides: true,
                                slidesPerView: 'auto',
                                touchRatio: 0.2,
                                slideToClickedSlide: true
                            });
                            timelineContents.controller.control = timelineDates;
                            timelineDates.controller.control = timelineContents;

                        }(jQuery);


                    </script>

                    <script>
                        var swiperoli = new Swiper('.swiper-containeroli', {
                            slidesPerView: 3,
                            spaceBetween: 20,


                            loop: true,
                            centeredSlides: true,


                            pagination: {
                                el: '.swiper-pagination',
                                clickable: true,
                            },

                            navigation: {
                                nextEl: '.swiper-button-next-ing',
                                prevEl: '.swiper-button-prev-ing',
                            },
                        });</script>


            {/if}
        {/if}
        {block name='cms_content'}
            {$cms.content nofilter}
        {/block}

        {block name='hook_cms_dispute_information'}
            {hook h='displayCMSDisputeInformation'}
        {/block}

        {block name='hook_cms_print_button'}
            {hook h='displayCMSPrintButton'}
        {/block}

    </section>
{/block}
