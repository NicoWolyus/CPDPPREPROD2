<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

include_once(_PS_MODULE_DIR_.'prestablog/prestablog.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/news.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/categories.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/correspondancescategories.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/commentnews.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/antispam.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/lookbook.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/displayslider.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/popup.class.php');

class PrestaBlogBlogModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    private $assign_page = 0;
    private $prestablog;

    private $news = array();
    private $news_count_all;
    private $path;
    private $pagination = array();
    private $config_theme;
    private $breadcrumb_links_perso = array();

    public function getTemplatePathFix($template)
    {
        return 'module:prestablog/views/templates/front/'.$template;
    }

    public function setMedia()
    {
        parent::setMedia();

$this->addjqueryPlugin('imagesloaded.pkgd');
        $this->context->controller->registerJavascript(
                'modules-prestablog-imagesloaded.pkgd',
                'modules/prestablog/views/js/imagesloaded.pkgd.min.js',
                array('position' => 'bottom', 'priority' => 200)
            );

        $this->addjqueryPlugin('masonry-pkgd');
        $this->context->controller->registerJavascript(
                'modules-prestablog-masonry-pkgd',
                'modules/prestablog/views/js/masonry.pkgd.min.js',
                array('position' => 'bottom', 'priority' => 200)
            );

        $this->addjqueryPlugin('fancybox');
        $this->context->controller->registerJavascript(
            'modules-prestablog-fancybox',
            'modules/prestablog/views/js/fancybox.js',
            array('position' => 'bottom', 'priority' => 200)
        );

        if (Configuration::get('prestablog_socials_actif')) {
            $this->context->controller->registerStylesheet(
                'modules-prestablog-social-css',
                'modules/prestablog/views/css/rrssb.css',
                array('media' => 'all', 'priority' => 200)
            );
            $this->context->controller->registerJavascript(
                'modules-prestablog-social-js',
                'modules/prestablog/views/js/rrssb.min.js',
                array('position' => 'bottom', 'priority' => 200)
            );
        }
    }

    public function canonicalRedirectionCustomController($canonical_url = '')
    {


        $match_url = '';
        if (Configuration::get('PS_SSL_ENABLED') && ($this->ssl || Configuration::get('PS_SSL_ENABLED_EVERYWHERE'))) {
            $match_url .= 'https://';
        } else {
            $match_url .= 'http://';
        }
        $match_url .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $match_url = rawurldecode($match_url);

        if (!preg_match('/^'.Tools::pRegexp(rawurldecode($canonical_url), '/').'([&?].*)?$/', $match_url)) {
            $redirect_type = '301';

            $redirect_type = Configuration::get('PS_CANONICAL_REDIRECT') == 2 ? '301' : '302';

            header('HTTP/1.0 '.$redirect_type.' Moved');
            header('Cache-Control: no-cache');
            Tools::redirectLink($canonical_url);
        }
    }

    public function getBreadcrumbLinks()
    {

        $breadcrumb_links = parent::getBreadcrumbLinks();

        foreach ($this->breadcrumb_links_perso as $bclp) {
            $breadcrumb_links['links'][] = array(
                'title' => $bclp['title'],
                'url' => $bclp['url'],
            );
        }

        return $breadcrumb_links;
    }

    public function init()
    {

        // ajout du lien blog en tout début de breadcrumb
        $this->breadcrumb_links_perso[] = array(
            'title' => $this->l('Blog'),
            'url' => PrestaBlog::prestablogUrl(array()),
        );

        $id_prestablog_news = null;
        parent::init();

        $this->prestablog = new PrestaBlog();

        /* assignPage (1 = 1 news page, 2 = news listes, 0 = rien) */
        $this->context->smarty->assign(array(
            'isLogged' => Context::getContext()->customer->isLogged(),
            'bloprestag_config' => Configuration::getMultiple(array_keys($this->prestablog->configurations)),
            'prestablog_popup' => PrestaBlog::getP(),
            'prestablog_theme' => PrestaBlog::getT(),
            'prestablog_theme_dir' => _MODULE_DIR_.'prestablog/views/',
            'prestablog_root_url_path' => PrestaBlog::getPathRootForExternalLink(),
            'prestablog_theme_upimgnoslash' => 'modules/prestablog/views/img/'.PrestaBlog::getT().'/up-img/',
            'md5pic' => md5(time())
        ));

$this->context->smarty->assign(array(
     'prestablog_color' => NewsClass::getColorHome((int)$this->context->shop->id)
));

 if (Tools::getValue('submitRating')) {
    $id_session = Context::getContext()->customer->id;
    $id_news = (int)Tools::getValue('id');
    $rate = (int)Tools::getValue('rate');
    Prestablog::newsRatingID($id_news, $id_session);
    Prestablog::newsRating($id_news, $rate);
}
        if (Tools::getValue('id') && $id_prestablog_news = (int)Tools::getValue('id')) {
            $this->assign_page = 1;
            $this->news = new NewsClass($id_prestablog_news, (int)$this->context->cookie->id_lang);
            $this->news->categories = CorrespondancesCategoriesClass::getCategoriesListeName(
                (int)$this->news->id,
                (int)$this->context->cookie->id_lang,
                1
            );

            if (!$this->prestablog->isPreviewMode((int)$this->news->id)) {
                if (!$this->news->actif) {
                    Tools::redirect('404.php');
                }

                $listecat = CorrespondancesCategoriesClass::getCategoriesListe((int)$this->news->id);
                if (!CategoriesClass::isCustomerPermissionGroups($listecat)) {
                    Tools::redirect('404.php');
                }

                if (!empty($this->news->url_redirect) && Validate::isAbsoluteUrl($this->news->url_redirect)) {
                    Tools::redirect($this->news->url_redirect);
                }

                // fix for redirect if news is not able for the current language
                if (!in_array((int)$this->context->cookie->id_lang, unserialize($this->news->langues))) {
                    Tools::redirect(PrestaBlog::prestablogUrl(array()));
                }
            }

            $branche_lapluslongue = '';

            if (count($this->news->categories)) {
                $categories_branches = array();

                foreach ($this->news->categories as $categorie_id) {
                    $categories_branches[] = CategoriesClass::getBranche((int)$categorie_id['id_prestablog_categorie']);
                }

                asort($categories_branches);
                $branche_lapluslongue = $categories_branches[0];
                $branche_count = 0;

                foreach ($categories_branches as $branche) {
                    $branche_list = preg_split('/\./', $branche);
                    if (count($branche_list) > $branche_count) {
                        $branche_lapluslongue = $branche;
                        $branche_count = count($branche_list);
                    }
                }
            }

            foreach (CategoriesClass::getBreadcrumb($branche_lapluslongue) as $cat_branche) {
                $this->breadcrumb_links_perso[] = $cat_branche;
            }

            $this->breadcrumb_links_perso[] = array(
                'title' => $this->news->title,
                'url' => PrestaBlog::prestablogUrl(array(
                    'id' => $this->news->id,
                    'seo' => $this->news->link_rewrite,
                    'titre' => $this->news->title
                ))
            );
        } elseif (Tools::getValue('a') && Configuration::get('prestablog_comment_subscription')) {
            if (!Context::getContext()->customer->isLogged()) {
                $urlauth = urlencode('index.php?fc=module&module=prestablog&controller=blog&a='.Tools::getValue('a'));
                Tools::redirect('index.php?controller=authentication&back='.$urlauth);
            }

            $this->news = new NewsClass((int)Tools::getValue('a'), (int)$this->context->cookie->id_lang);

            if ($this->news->actif) {
                CommentNewsClass::insertCommentAbo((int)$this->news->id, (int)$this->context->cookie->id_customer);
            }

            Tools::redirect(PrestaBlog::prestablogUrl(array(
                'id' => $this->news->id,
                'seo' => $this->news->link_rewrite,
                'titre' => $this->news->title
            )));
        } elseif (Tools::getValue('d') && Configuration::get('prestablog_comment_subscription')) {
            if (Context::getContext()->customer->isLogged()) {
                $this->news = new NewsClass((int)Tools::getValue('d'), (int)$this->context->cookie->id_lang);
                if ($this->news->actif) {
                    CommentNewsClass::deleteCommentAbo((int)$this->news->id, (int)$this->context->cookie->id_customer);
                }
            }

            Tools::redirect(PrestaBlog::prestablogUrl(array(
                'id' => $this->news->id,
                'seo' => $this->news->link_rewrite,
                'titre' => $this->news->title
            )));
        } else {
            $this->assign_page = 2;
            $categorie = null;
            $year = null;
            $month = null;

            if (Tools::getValue('c')) {
                if (!CategoriesClass::isCustomerPermissionGroups(array((int)Tools::getValue('c')))) {
                    Tools::redirect('404.php');
                }

                $categorie = new CategoriesClass((int)Tools::getValue('c'), (int)$this->context->cookie->id_lang);

                $breadcrumbcat = CategoriesClass::getBreadcrumb(CategoriesClass::getBranche((int)$categorie->id));

                foreach ($breadcrumbcat as $cat_branche) {
                    $this->breadcrumb_links_perso[] = $cat_branche;
                }

                $lrw = ($categorie->link_rewrite != '' ? $categorie->link_rewrite : $categorie->title);
                $this->context->smarty->assign(array(
                    'prestablog_categorie' => $categorie->id,
                    'prestablog_categorie_name' => $categorie->title,
                    'prestablog_categorie_link_rewrite' => $lrw
                ));
            } else {
                $this->context->smarty->assign(array(
                    'prestablog_categorie'  => null,
                    'prestablog_categorie_name' => null,
                    'prestablog_categorie_link_rewrite' => null
                ));
            }

            if (trim(Tools::getValue('prestablog_search'))) {
                $this->breadcrumb_links_perso[] = array(
                    'title' => sprintf(
                        $this->l('Search %1$s in the blog'),
                        '"'.trim(Tools::getValue('prestablog_search')).'"'
                    ),
                    'url' => '#'
                );
            }

            if (Tools::getValue('y')) {
                $year = Tools::getValue('y');
                $this->breadcrumb_links_perso[] = array(
                    'title' => $year,
                    'url' => '#'
                );
            }

            if (Tools::getValue('m')) {
                $month = Tools::getValue('m');
                $this->breadcrumb_links_perso[] = array(
                    'title' => $this->prestablog->mois_langue[$month],
                    'url' => PrestaBlog::prestablogUrl(array(
                        'y' => $year,
                        'm' => $month
                    ))
                );
            }

            if (Tools::getValue('p')) {
                $this->breadcrumb_links_perso[] = array(
                    'title' => $this->l('Page').' '.Tools::getValue('p'),
                    'url' => '#'
                );
            }

            $this->context->smarty->assign(array(
                'prestablog_month' => $month,
                'prestablog_year' => $year
            ));

            if (Tools::getValue('m') && Tools::getValue('y')) {
                $date_debut = Date('Y-m-d H:i:s', mktime(0, 0, 0, $month, + 1, $year));
                $date_fin = Date('Y-m-d H:i:s', mktime(0, 0, 0, $month + 1, + 1, $year));
                if ($date_fin > Date('Y-m-d H:i:s')) {
                    $date_fin = Date('Y-m-d H:i:s');
                }
            } else {
                $date_debut = null;
                $date_fin = Date('Y-m-d H:i:s');
            }

            $categories_filtre = null;

            if (isset($categorie->id)) {
                $categories_filtre = (int)$categorie->id;
            } elseif (Tools::getValue('prestablog_search_array_cat')) {
                $categories_filtre = Tools::getValue('prestablog_search_array_cat');
            }

            $this->news_count_all = NewsClass::getCountListeAll(
                (int)$this->context->cookie->id_lang,
                1,
                0,
                $date_debut,
                $date_fin,
                $categories_filtre,
                1,
                Tools::getValue('prestablog_search')
            );

            $this->news = NewsClass::getListe(
                (int)$this->context->cookie->id_lang,
                1,
                0,
                (int)Tools::getValue('start'),
                (int)Configuration::get('prestablog_nb_liste_page'),
                'n.`date`',
                'desc',
                $date_debut,
                $date_fin,
                $categories_filtre,
                1,
                (int)Configuration::get('prestablog_news_title_length'),
                (int)Configuration::get('prestablog_news_intro_length'),
                Tools::getValue('prestablog_search')
            );

            /*
            * fix for redirect if news haven't got any news for the
            * current language and current start page on category list
            */


            if ((int)$this->news_count_all > 0 && count($this->news) == 0) {
                Tools::redirect(PrestaBlog::prestablogUrl(array(
                    'c' => (int)$categorie->id,
                    'titre' => $categorie->title
                )));
            }
            if ((int)$this->news_count_all == 0 && Tools::getValue('p')) {
                Tools::redirect(PrestaBlog::prestablogUrl(array()));
            }
        }

        if ($this->assign_page == 1) {
            $this->context->smarty->assign(PrestaBlog::getPrestaBlogMetaTagsNewsOnly(
                (int)$this->context->cookie->id_lang,
                (int)Tools::getValue('id')
            ));
        } elseif ($this->assign_page == 2 && Tools::getValue('c')) {
            $this->context->smarty->assign(PrestaBlog::getPrestaBlogMetaTagsNewsCat(
                (int)$this->context->cookie->id_lang,
                (int)Tools::getValue('c')
            ));
        } elseif ($this->assign_page == 2 && (Tools::getValue('y') || Tools::getValue('m'))) {
            $this->context->smarty->assign(PrestaBlog::getPrestaBlogMetaTagsNewsDate());
        } else {
            $this->context->smarty->assign(PrestaBlog::getPrestaBlogMetaTagsPage(
                (int)$this->context->cookie->id_lang
            ));
        }

        if (!$this->prestablog->isPreviewMode((int)$id_prestablog_news)) {
            $this->gestionRedirectionCanonical((int)$this->assign_page);
        }

    }

    private function gestionRedirectionCanonical($assign_page)
    {
        switch ($assign_page) {
            case 1:
                $news = new NewsClass((int)Tools::getValue('id'), (int)$this->context->cookie->id_lang);
                if (!Tools::getValue('submitComment')) {
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array(
                        'id' => $news->id,
                        'seo' => $news->link_rewrite,
                        'titre' => $news->title
                    )));
                }
                break;

            case 2:
                if (Tools::getValue('start')
                    && Tools::getValue('p')
                    && !Tools::getValue('c')
                    && !Tools::getValue('m')
                    && !Tools::getValue('y')
                ) {
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array(
                        'start' => (int)Tools::getValue('start'),
                        'p' => (int)Tools::getValue('p')
                    )));
                }
                if (Tools::getValue('c') && !Tools::getValue('start') && !Tools::getValue('p')) {
                    $categorie = new CategoriesClass((int)Tools::getValue('c'), (int)$this->context->cookie->id_lang);
                    $cat_link_rewrite = $categorie->link_rewrite;
                    if ($categorie->link_rewrite == '') {
                        $cat_link_rewrite = CategoriesClass::getCategoriesName(
                            (int)$this->context->cookie->id_lang,
                            (int)Tools::getValue('c')
                        );
                    }
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array(
                        'c' => $categorie->id,
                        'categorie' => $cat_link_rewrite
                    )));
                }
                if (Tools::getValue('c') && Tools::getValue('start') && Tools::getValue('p')) {
                    $categorie = new CategoriesClass((int)Tools::getValue('c'), (int)$this->context->cookie->id_lang);
                    $cat_link_rewrite = $categorie->link_rewrite;
                    if ($categorie->link_rewrite == '') {
                        $cat_link_rewrite = CategoriesClass::getCategoriesName(
                            (int)$this->context->cookie->id_lang,
                            (int)Tools::getValue('c')
                        );
                    }
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array(
                        'c' => $categorie->id,
                        'start' => (int)Tools::getValue('start'),
                        'p' => (int)Tools::getValue('p'),
                        'categorie' => $cat_link_rewrite,
                    )));
                }
                if (Tools::getValue('m')
                    && Tools::getValue('y')
                    && !Tools::getValue('start')
                    && !Tools::getValue('p')
                ) {
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array(
                        'y' => (int)Tools::getValue('y'),
                        'm' => (int)Tools::getValue('m')
                    )));
                }
                if (Tools::getValue('m') && Tools::getValue('y') && Tools::getValue('start') && Tools::getValue('p')) {
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array(
                        'y' => (int)Tools::getValue('y'),
                        'm' => (int)Tools::getValue('m'),
                        'start' => (int)Tools::getValue('start'),
                        'p' => (int)Tools::getValue('p')
                    )));
                }
                if (!Tools::getValue('m')
                    && !Tools::getValue('y')
                    && !Tools::getValue('c')
                    && !Tools::getValue('start')
                    && !Tools::getValue('p')
                ) {
                    $title_h1_index = trim(Configuration::get(
                        'prestablog_h1pageblog',
                        (int)$this->context->cookie->id_lang
                    ));
                    if ($title_h1_index != '') {
                        $this->context->smarty->assign('prestablog_title_h1', $title_h1_index);
                    }
                    $this->canonicalRedirectionCustomController(PrestaBlog::prestablogUrl(array()));
                }
                break;
        }
    }

    public function initContent()
    {
        parent::initContent();

        /// affichage du menu cat
        if ($this->assign_page == 1 && Configuration::get('prestablog_menu_cat_blog_article')) {
            $this->voirListeCatMenu();
        }
        // ne pas afficher le menu cat sur la page search
        if ($this->assign_page == 2 && !trim(Tools::getValue('prestablog_search'))) {
            if (Configuration::get('prestablog_menu_cat_blog_index')
                && !Tools::getValue('c')
                && !Tools::getValue('y')
                && !Tools::getValue('m')
                && !Tools::getValue('p')
            ) {
                $this->voirListeCatMenu();
            } elseif (Configuration::get('prestablog_menu_cat_blog_list')
                && (Tools::getValue('c')
                    || Tools::getValue('y')
                    || Tools::getValue('m')
                    || Tools::getValue('p')
                )
            ) {
                $this->voirListeCatMenu();
            }
        }



        // /affichage du menu cat

        // affichage du filtrage search
        if ($this->assign_page == 2
            && trim(Tools::getValue('prestablog_search'))
            && Configuration::get('prestablog_search_filtrecat')
        ) {
            $this->voirFiltrageSearch();
        }
        // /affichage du filtrage search

        if ($this->assign_page == 1) {
            // liaison produits
            $products_liaison = NewsClass::getProductLinkListe((int)$this->news->id, true);

            if (count($products_liaison) > 0) {
                foreach ($products_liaison as $product_link) {
                    $product = new Product((int)$product_link, false, (int)$this->context->cookie->id_lang);
                    $product_cover = Image::getCover($product->id);
                    $image_product = new Image((int)$product_cover['id_image']);
                    $image_thumb_path = ImageManager::thumbnail(
                        _PS_IMG_DIR_.'p/'.$image_product->getExistingImgPath().'.jpg',
                        'product_blog_mini_2_'.$product->id.'.jpg',
                        (int)Configuration::get('prestablog_thumb_linkprod_width'),
                        'jpg',
                        true,
                        true
                    );

                    $this->news->products_liaison[$product_link] = array(
                        'name' => $product->name,
                        'description_short' => $product->description_short,
                        'thumb' => $image_thumb_path,
                        'link' => $product->getLink($this->context)
                    );
                }
            }
            // /liaison produits

            // liaison articles
            $articles_liaison = NewsClass::getArticleLinkListe((int)$this->news->id, true);

            if (count($articles_liaison) > 0) {
                foreach ($articles_liaison as $article_liaison) {
                    $get_cat_liste = CorrespondancesCategoriesClass::getCategoriesListe((int)$article_liaison);
                    if (CategoriesClass::isCustomerPermissionGroups($get_cat_liste)) {
                        $article = new NewsClass((int)$article_liaison, (int)$this->context->cookie->id_lang);

                        $this->news->articles_liaison[$article_liaison] = array(
                            'title' => $article->title,
                            'date' => $article->date,
                            'link' => PrestaBlog::prestablogUrl(array(
                                'id' => $article->id,
                                'seo' => $article->link_rewrite,
                                'titre' => $article->title
                            ))
                        );
                    }
                }
            }
    $id_session = Context::getContext()->customer->id;
   $check = NewsClass::checkrate((int)Tools::getValue('id'), $id_session);

   $validate = 'true';
   $notvalidate = 'false';
   if ($check == true) {
    $this->context->smarty->assign(array(
                    'validate' => $validate
                ));
   } else {
    $this->context->smarty->assign(array(
                    'validate' => $notvalidate
                ));
   }

            // /liaison articles
$popup_liaison = NewsClass::getPopupLink((int)$this->news->id);
$id_cookie = (int)$this->context->cookie->id_lang;

 if (isset($popup_liaison) && $popup_liaison != 0 && $this->news->actif_popup = '1') {
    $popup = new PopupClass($popup_liaison, $id_cookie);

                        $this->prestablog->displayPopup($id_cookie,$popup_liaison);

            }

            $prestablog_current_url = PrestaBlog::prestablogUrl(array(
                'id' => $this->news->id,
                'seo' => $this->news->link_rewrite,
                'titre' => $this->news->title
            ));

            if (file_exists(PrestaBlog::imgUpPath().'/'.$this->news->id.'.jpg')) {
                $this->context->smarty->assign(
                    'news_Image',
                    'modules/prestablog/views/img/'.PrestaBlog::getT().'/up-img/'.$this->news->id.'.jpg'
                );
            }
            $this->context->smarty->assign(array(
                'LinkReal' => PrestaBlog::getBaseUrlFront().'?fc=module&module=prestablog&controller=blog',
                'news' => $this->news,
                'prestablog_current_url' => $prestablog_current_url
            ));

            // INCREMENT NEWS READ
            if (!$this->context->cookie->__isset('prestablog_news_read_'.(int)$this->context->cookie->id_lang)) {
                $this->news->incrementRead((int)$this->news->id, (int)$this->context->cookie->id_lang);
                $this->context->cookie->__set(
                    'prestablog_news_read_'.(int)$this->context->cookie->id_lang,
                    serialize(array((int)$this->news->id))
                );
            } else {
                $cookie_read_lang = 'prestablog_news_read_'.(int)$this->context->cookie->id_lang;
                $array_news_readed = unserialize($this->context->cookie->__get($cookie_read_lang));
                if (!in_array((int)$this->news->id, $array_news_readed)) {
                    $array_news_readed[] = (int)$this->news->id;
                    $this->news->incrementRead((int)$this->news->id, (int)$this->context->cookie->id_lang);
                    $this->context->cookie->__set(
                        'prestablog_news_read_'.(int)$this->context->cookie->id_lang,
                        serialize($array_news_readed)
                    );
                }
            }
            // /INCREMENT NEWS READ

            $this->context->smarty->assign(array(
                'tpl_unique' => $this->context->smarty->fetch(
                    $this->getTemplatePathFix(PrestaBlog::getT().'_page-unique.tpl')
                )
            ));

            if ($this->prestablog->gestComment($this->news->id)) {
                if (Configuration::get('prestablog_antispam_actif')) {
                    $anti_spam_load = $this->prestablog->gestAntiSpam();

                    if ($anti_spam_load != false) {
                        $this->context->smarty->assign(array(
                            'AntiSpam' => $anti_spam_load
                        ));
                    }
                }
                $this->context->smarty->assign(array(
                    'Is_Subscribe' => in_array(
                        $this->context->cookie->id_customer,
                        CommentNewsClass::listeCommentAbo($this->news->id)
                    )
                ));

                $this->context->controller->registerJavascript(
                    'modules-prestablog-comments',
                    'modules/prestablog/views/js/comments.js',
                    array('position' => 'bottom', 'priority' => 200)
                );

                $this->context->smarty->assign(array(
                    'tpl_comment' => $this->context->smarty->fetch(
                        $this->getTemplatePathFix(PrestaBlog::getT().'_page-comment.tpl')
                    )
                ));
            }
            if (Configuration::get('prestablog_commentfb_actif')) {
                $iso_code = $this->context->language->iso_code;
                $this->context->smarty->assign(array(
                    'fb_comments_url' => $prestablog_current_url,
                    'fb_comments_nombre' => (int)Configuration::get('prestablog_commentfb_nombre'),
                    'fb_comments_apiId' => Configuration::get('prestablog_commentfb_apiId'),
                    'fb_comments_iso' => Tools::strtolower($iso_code).'_'.Tools::strtoupper($iso_code)
                ));

                $this->context->controller->registerJavascript(
                    'modules-prestablog-facebook',
                    'modules/prestablog/views/js/facebook.js',
                    array('position' => 'bottom', 'priority' => 200)
                );

                $this->context->smarty->assign(array(
                    'tpl_comment_fb' => $this->context->smarty->fetch(
                        $this->getTemplatePathFix(PrestaBlog::getT().'_page-comment-fb.tpl')
                    )
                ));
            }
        } elseif ($this->assign_page == 2 && !trim(Tools::getValue('prestablog_search'))) {
            if (Configuration::get('prestablog_pageslide_actif')
                && !Tools::getValue('c')
                && !Tools::getValue('y')
                && !Tools::getValue('m')
                && !Tools::getValue('p')
            ) {
                if ($this->prestablog->slideNews()) {
                    $this->context->smarty->assign(array(
                        'tpl_slide' => $this->context->smarty->fetch(
                            $this->getTemplatePathFix(PrestaBlog::getT().'_slide.tpl')
                        )
                    ));
                }
            }
            if ((
                    Configuration::get('prestablog_view_cat_desc')
                    || Configuration::get('prestablog_view_cat_thumb')
                    || Configuration::get('prestablog_view_cat_img')
                )
                && Tools::getValue('c')
                && !Tools::getValue('y')
                && !Tools::getValue('m')
                && !Tools::getValue('p')
            ) {
                $obj_categorie = new CategoriesClass((int)Tools::getValue('c'), (int)$this->context->cookie->id_lang);
                if (file_exists(
                    _PS_MODULE_DIR_.'prestablog/views/img/'.PrestaBlog::getT().'/up-img/c/'.$obj_categorie->id.'.jpg'
                )) {
                    $obj_categorie->image_presente = true;
                } else {
                    $obj_categorie->image_presente = false;
                }

                $this->context->smarty->assign(array(
                    'prestablog_categorie_obj' => $obj_categorie
                ));

                $this->context->smarty->assign(array(
                    'tpl_cat' => $this->context->smarty->fetch(
                        $this->getTemplatePathFix(PrestaBlog::getT().'_category.tpl')
                    )
                ));
            }
        }
        if ($this->assign_page == 2) {
            $this->pagination = PrestaBlog::getPagination(
                $this->news_count_all,
                null,
                (int)Configuration::get('prestablog_nb_liste_page'),
                (int)Tools::getValue('start'),
                (int)Tools::getValue('p')
            );

            $prestablog_search_query = '';
            if (trim(Tools::getValue('prestablog_search'))) {
                if ((int)Configuration::get('PS_REWRITING_SETTINGS')
                    && (int)Configuration::get('prestablog_rewrite_actif')
                ) {
                    $prestablog_search_query = '?prestablog_search='.trim(Tools::getValue('prestablog_search'));
                } else {
                    $prestablog_search_query = '&prestablog_search='.trim(Tools::getValue('prestablog_search'));
                }
            }

            $this->context->smarty->assign(array(
                'prestablog_search_query' => $prestablog_search_query,
                'prestablog_pagination' => $this->getTemplatePathFix(
                    PrestaBlog::getT().'_page-pagination.tpl'
                ),
                'Pagination' => $this->pagination,
                'news' => $this->news,
                'NbNews' => $this->news_count_all
            ));

            if (Configuration::get('prestablog_commentfb_actif')) {
                $this->context->controller->registerJavascript(
                    'modules-prestablog-facebook-count',
                    'modules/prestablog/views/js/facebook-count.js',
                    array('position' => 'bottom', 'priority' => 200)
                );
            }

            $this->context->smarty->assign(array(
                'tpl_all' => $this->context->smarty->fetch(
                    $this->getTemplatePathFix(PrestaBlog::getT().'_page-all.tpl')
                )
            ));
        }

        $this->context->smarty->assign(array(
            'layout_blog' => $this->prestablog->layout_blog[(int)Configuration::get('prestablog_layout_blog')]
        ));


        $this->setTemplate($this->getTemplatePathFix(PrestaBlog::getT().'_page.tpl'));
                //check id_session


    }

    private function voirListeCatMenu()
    {
        $liste_cat = CategoriesClass::getListe((int)$this->context->cookie->id_lang, 1);

        if (count($liste_cat) > 0) {
            $this->context->smarty->assign(array(
                'MenuCatNews' => $this->displayMenuCategories($liste_cat)
            ));

            $this->context->controller->registerJavascript(
                'modules-prestablog-menucat',
                'modules/prestablog/views/js/menucat.js',
                array('position' => 'bottom', 'priority' => 202)
            );

            $this->context->smarty->assign(array(
                'tpl_menu_cat' => $this->context->smarty->fetch(
                    $this->getTemplatePathFix(PrestaBlog::getT().'_page-menucat.tpl')
                )
            ));
        }
    }

    private function voirFiltrageSearch()
    {
        $liste_cat = CategoriesClass::getListe((int)$this->context->cookie->id_lang, 1);

        if (count($liste_cat) > 0) {
            $html_out = '';
            $categories = new CategoriesClass();
            $liste_categories = CategoriesClass::getListe((int)$this->context->language->id, 0);
            $html_out .= ' <div id="categoriesForFilter">'."\n";
            if (Tools::getValue('prestablog_search_array_cat')
                && count(Tools::getValue('prestablog_search_array_cat')) > 0
            ) {
                foreach (Tools::getValue('prestablog_search_array_cat') as $cat_id) {
                    $categorie_filtre = new CategoriesClass((int)$cat_id, (int)$this->context->cookie->id_lang);
                    $html_out .= '
                        <div class="filtrecat" rel="'.(int)$cat_id.'">'.$categorie_filtre->title.'
                            <div class="deleteCat" rel="'.(int)$cat_id.'">X</div>
                        </div>'."\n";
                }
            }
            $html_out .= ' </div>'."\n";
            $html_out .= $categories->displaySelectArboCategories(
                $liste_categories,
                0,
                0,
                $this->l('Select a category'),
                'SelectCat',
                '',
                0
            )."\n";

            $search_array_cat = null;
            if (Tools::getValue('prestablog_search_array_cat')) {
                $search_array_cat = Tools::getValue('prestablog_search_array_cat');
            }
            $this->context->smarty->assign(array(
                'prestablog_search_query' => trim(Tools::getValue('prestablog_search')),
                'prestablog_search_array_cat' => $search_array_cat,
                'FiltrageCat' => $html_out
            ));

            $this->context->controller->registerJavascript(
                'modules-prestablog-filtrecat',
                'modules/prestablog/views/js/filtrecat.js',
                array('position' => 'bottom', 'priority' => 202)
            );

            $this->context->smarty->assign(array(
                'tpl_filtre_cat' => $this->context->smarty->fetch(
                    $this->getTemplatePathFix(PrestaBlog::getT().'_page-filtrecat.tpl')
                )
            ));
        }
    }

    public function displayMenuCategories($liste, $first = true, $child = false, $num = 0)
    {
 if ($child = true) {
     $html_out = '<ul class="sub-menu hidden">';
 } else {
         $html_out = '<ul>';
 }

        if ($first && Configuration::get('prestablog_menu_cat_home_link')) {
            $prestablog = new PrestaBlog();
            $menu_cat_home_img = $prestablog->message_call_back['Blog'];
            if (Configuration::get('prestablog_menu_cat_home_img')) {
                $menu_cat_home_img = '<img src="'._MODULE_DIR_.'prestablog/views/img/home.gif" alt="'.$this->l('Home').'"/>';
            }
            $html_out .= '
                <li>
                    <a href="'.PrestaBlog::prestablogUrl(array()).'" >
                        '.$menu_cat_home_img.'
                    </a>
                </li>';
            $first = false;
        }
        foreach ($liste as $value) {
            if (!Configuration::get('prestablog_menu_cat_blog_empty') && (int)$value['nombre_news_recursif'] == 0) {
                $html_out .= '';
            } else {
                $nombre_news_recursif = '';
                if (Configuration::get('prestablog_menu_cat_blog_nbnews') && (int)$value['nombre_news_recursif'] > 0) {
                    '&nbsp;<span>('.(int)$value['nombre_news_recursif'].')</span>';
                }
                $html_out .= '
                    <li>
                        <a href="'.PrestaBlog::prestablogUrl(array(
                                'c' => (int)$value['id_prestablog_categorie'],
                                'titre'  => ($value['link_rewrite'] != '' ? $value['link_rewrite'] : $value['title'])
                            )).'" '.(count($value['children']) > 0 ?'class="mparent"':'').'>
                            '.$value['title'].$nombre_news_recursif.'
                        </a>
						'.(count($value['children']) > 0 ? ($num > 0 ?'<i class="material-icons idtest2">keyboard_arrow_right</i>':'<i class="material-icons idtest">keyboard_arrow_down</i>'):'').'
						';

                if (count($value['children']) > 0) {
                    $num ++;
                    $child = true;
                    $html_out .= $this->displayMenuCategories($value['children'], $first, $child, $num);
                }
                $html_out .= ' </li>';
            }
        }
        $html_out .= '</ul>';

        return $html_out;

    }
}
