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

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

include_once(dirname(__FILE__).'/class/news.class.php');
include_once(dirname(__FILE__).'/class/categories.class.php');
include_once(dirname(__FILE__).'/class/correspondancescategories.class.php');
include_once(dirname(__FILE__).'/class/commentnews.class.php');
include_once(dirname(__FILE__).'/class/antispam.class.php');
include_once(dirname(__FILE__).'/class/subblocks.class.php');
include_once(dirname(__FILE__).'/class/lookbook.class.php');
include_once(dirname(__FILE__).'/class/displayslider.class.php');
include_once(dirname(__FILE__).'/class/popup.class.php');
$layerslider = Module::getInstanceByName('layerslider');
if ($layerslider){
include_once(dirname(__FILE__).'/../layerslider/layerslider.php');
}

class PrestaBlog extends Module implements WidgetInterface
{
    /****************************/
    /******* DEMO MODE **********/
    /****************************/
    /*
    * true or false,  false, all upload files
    * and critical hoster informations are disable
    * feature @PrestaBlog demo reserved
    */
    protected $demo_mode = false;
    /****************************/

    public $html_out = '';
    public $module_path = '';
    public $mois_langue = array();
    public $rss_langue = array();

    private $checksum = '';

    protected $check_slide;
    protected $check_active;

    protected $check_comment_state = -2;

    protected $normal_image_size_width = 1024;
    protected $normal_image_size_height = 1024;

    protected $admin_crop_image_size_width = 400;
    protected $admin_crop_image_size_height = 400;

    protected $lb_crop_image_size_width = 700;
    protected $lb_crop_image_size_height = 700;

    protected $admin_thumb_image_size_width = 40;
    protected $admin_thumb_image_size_height = 40;

    protected $max_image_size = 25510464;
    protected $default_theme = 'grid-for-1-7';

    protected $confpath;

    public $layout_blog = array(
        0 => 'layouts/layout-full-width.tpl',
        1 => 'layouts/layout-both-columns.tpl',
        2 => 'layouts/layout-left-column.tpl',
        3 => 'layouts/layout-right-column.tpl',
        4 => 'layouts/layout-content-only.tpl',
    );

    public static function httpS()
    {
        return (Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http');
    }

    public static function urlSRoot()
    {
        $module_shop_domain = self::getContextShopDomain(true).__PS_BASE_URI__;
        if (Configuration::get('PS_SSL_ENABLED')) {
            $module_shop_domain = self::getContextShopDomainSsl(true).__PS_BASE_URI__;
        };
        return $module_shop_domain;
    }

    public static function accurl()
    {

        if ((int)Configuration::get('PS_REWRITING_SETTINGS')
            && (int)Configuration::get('prestablog_rewrite_actif')) {
            return '?';
        } else {
            return '&';
        }
    }

    public static function getT()
    {
        return Configuration::get('prestablog_theme');
    }
    public static function getP()
    {
        return Configuration::get('prestablog_popup');
    }

    public static function imgPath()
    {
        return dirname(__FILE__).'/views/img/';
    }

    public static function imgPathFO()
    {
        return '../modules/prestablog/views/img/';
    }

    public static function imgPathBO()
    {
        return '/../modules/prestablog/views/img/';
    }

    public static function imgUpPath()
    {
        return self::imgPath().self::getT().'/up-img';
    }

    public static function getPathRootForExternalLink()
    {
        return Tools::getShopDomainSsl(true).__PS_BASE_URI__;
    }

    /****************************************/
    /************** WIDGET 1.7 **************/
    /****************************************/
    public function renderWidget($hookname = null, array $configuration = array())
    {
        // dans l'attente d'evolution de la part des widgets de PrestaShop
        // Est-ce qu'il y aura un dossier widget pour les tpl ?
        // $template_file = 'module:prestablog/views/templates/widget/'.self::getT().'_'.$hookname.'.tpl';
        $template_file = 'module:prestablog/views/templates/hook/'.self::getT().'_'.$hookname.'.tpl';

        $this->smarty->assign($this->getWidgetVariables($hookname, $configuration));

        return $this->fetch($template_file, $this->getCacheId());
    }

    public function getWidgetVariables($hookname = null, array $configuration = array())
    {
        $hookname = $hookname;
        $configuration = $configuration;
        return null;
    }
    /****************************************/
    /************ / WIDGET 1.7 **************/
    /****************************************/
    public function renderWidgetPopup($hookname = null, array $configuration = array())
    {
        // dans l'attente d'evolution de la part des widgets de PrestaShop
        // Est-ce qu'il y aura un dossier widget pour les tpl ?
        // $template_file = 'module:prestablog/views/templates/widget/'.self::getT().'_'.$hookname.'.tpl';
        $template_file_popup = 'module:prestablog/views/templates/hook/'.$hookname.'.tpl';

        $this->smarty->assign($this->getWidgetVariables($hookname, $configuration));

        return $this->fetch($template_file_popup, $this->getCacheId());
    }

    public function getWidgetVariablesPopup($hookname = null, array $configuration = array())
    {
        $hookname = $hookname;
        $configuration = $configuration;
        return null;
    }
    /****************************************/
    /************ / WIDGET 1.7 **************/
    /****************************************/


    public static function isPSVersion($compare, $version)
    {
        return version_compare(_PS_VERSION_, $version, $compare);
    }

    public static function getModuleDataBaseVersion()
    {
        $module = Db::getInstance()->getRow('
        SELECT `version` FROM `'.bqSQL(_DB_PREFIX_).'module`
        WHERE `name` = \'prestablog\'');
        return $module['version'];
    }

    public function loadJsForTiny()
    {
        $this->context->controller->addJs(array(
            _PS_JS_DIR_.'tiny_mce/tiny_mce.js',
            _PS_JS_DIR_.'admin/tinymce.inc.js',
            _PS_JS_DIR_.'admin/tinymce_loader.js'
        ));
    }

    public function __construct()
    {
        $this->name = 'prestablog';
        $this->tab = 'front_office_features';
        $this->version = '4.2.2';
        $this->author = 'Prestablog';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '7aafe030447c17f08629e0319107b62b';

        parent::__construct();

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->displayName = $this->l('PrestaBlog');
        $this->description = $this->l('A module to add a blog on your web store.');

        $this->confirmUninstall = $this->l('Are you sure you want to delete this module ?');

        $this->confpath = 'index.php?tab=AdminModules&configure=prestablog&token='.Tools::getValue('token');
        $this->ctrblog = '?fc=module&module=prestablog&controller=blog';
 		$this->cc = 'configure=prestablog';
        $this->pp_conf = $this->confpath;
        $this->langue_default_store = (int)Configuration::get('PS_LANG_DEFAULT');

        $path = dirname(__FILE__);
        if (strpos(__FILE__, 'Module.php') !== false) {
            $path .= '/../modules/'.$this->name;
        }
        $this->configurations = array(
            $this->name.'_token' => md5($this->genererMDP(32)._COOKIE_KEY_),
        );

        $this->mois_langue = array(
            1 => $this->l('January'),
            2 => $this->l('February'),
            3 => $this->l('March'),
            4 => $this->l('April'),
            5 => $this->l('May'),
            6 => $this->l('June'),
            7 => $this->l('July'),
            8 => $this->l('August'),
            9 => $this->l('September'),
            10 => $this->l('October'),
            11 => $this->l('November'),
            12 => $this->l('December')
        );

        $this->module_path = $path;

        $this->message_call_back = array (
            'Blog' => $this->l('Blog'),
            'no_result_found' => $this->l('No result found'),
            'no_result_listed' => $this->l('No result listed'),
            'total_results' => $this->l('Total results'),
            'next_results' => $this->l('Next'),
            'prev_results' => $this->l('Previous'),
            'blocRss' => $this->l('Block Rss all news'),
            'blocDateListe' => $this->l('Block date news'),
            'blocLastListe' => $this->l('Block last news'),
            'blocCatListe' => $this->l('Block categories news'),
            'blocSearch' => $this->l('Block search news'),
            'Yu3Tr9r7' => $this->l('The import XML was successfull'),
            '2yt6wEK7' => $this->l('No import selected'),
        );

        $this->default_theme = 'grid-for-1-7';

        $this->configurations = array(
            /** Thèmes et slide **************************/
            /**Popup*/
            $this->name.'_popuphome_actif' => 0,
            $this->name.'_popup_general' => 0,
            // Thème
            $this->name.'_theme' => $this->default_theme,
            // layouts/layout-left-column.tpl
            $this->name.'_layout_blog' => 2,
            //displayRating
            $this->name.'_rating_actif' => 0,
            // Slideshow
            $this->name.'_homenews_actif' => 0,
            $this->name.'_pageslide_actif' => 1,
            $this->name.'_homenews_limit' => 5,
            $this->name.'_slide_picture_width' => 555,
            $this->name.'_slide_picture_height' => 236,
            $this->name.'_slide_title_length' => 80,
            $this->name.'_slide_intro_length' => 160,
            /** /Thèmes et slide *************************/


            /** Blocs ************************************/
            // Bloc derniers articles
            $this->name.'_lastnews_limit' => 5,
            $this->name.'_lastnews_showall' => 1,
            $this->name.'_lastnews_actif' => 0,
            $this->name.'_lastnews_showintro' => 0,
            $this->name.'_lastnews_showthumb' => 1,
            $this->name.'_lastnews_title_length' => 80,
            $this->name.'_lastnews_intro_length' => 120,
            // Bloc d'articles par date
            $this->name.'_datenews_order' => 'desc',
            $this->name.'_datenews_showall' => 0,
            $this->name.'_datenews_actif' => 0,
            // Bloc Rss pour tous les articles
            $this->name.'_allnews_rss' => 0,
            $this->name.'_rss_title_length' => 80,
            $this->name.'_rss_intro_length' => 200,
            // Bloc Search
            $this->name.'_blocsearch_actif' => 0,
            $this->name.'_search_filtrecat' => 1,
            // Dernières actualités en footer
            $this->name.'_footlastnews_actif' => 0,
            $this->name.'_footlastnews_limit' => 3,
            $this->name.'_footlastnews_showall' => 1,
            $this->name.'_footlastnews_intro' => 0,
            $this->name.'_footer_title_length' => 80,
            $this->name.'_footer_intro_length' => 120,
            // Ordre des blocs dans les colonnes
            $this->name.'_sbr' => serialize(
                array(
                    0 => ''
                )
            ),
            $this->name.'_sbl' => serialize(
                array(
                    0 => 'blocRss',
                    1 => 'blocLastListe',
                    2 => 'blocCatListe',
                    3 => 'blocDateListe',
                    4 => 'blocSearch'
                )
            ),
            /** /Blocs ***********************************/

            /** SubBlocs *********************************/
            $this->name.'_subblocks_actif' => 1,
            /** /SubBlocs ********************************/

            /** Commentaires *****************************/
            $this->name.'_comment_actif' => 1,
            $this->name.'_comment_only_login' => 0,
            $this->name.'_comment_auto_actif' => 0,
            $this->name.'_comment_nofollow' => 1,
            $this->name.'_comment_alert_admin' => 0,
            $this->name.'_comment_admin_mail' => Configuration::get('PS_SHOP_EMAIL'),
            $this->name.'_captcha_actif' => 0,
            $this->name.'_captcha_public_key' => '',
            $this->name.'_captcha_private_key' => '',
            $this->name.'_comment_subscription' => 1,
            /** /Commentaires ****************************/

            /** Commentaires Facebook ********************/
            $this->name.'_commentfb_actif' => 0,
            $this->name.'_commentfb_nombre' => 5,
            $this->name.'_commentfb_apiId' => '',
            $this->name.'_commentfb_modosId' => '',
            /** /Commentaires Facebook *******************/

            /** Categorie ********************************/
            // Menu catégories dans la page du blog
            $this->name.'_menu_cat_blog_index' => 1,
            $this->name.'_menu_cat_blog_list' => 0,
            $this->name.'_menu_cat_blog_article' => 0,
            $this->name.'_menu_cat_blog_empty' => 0,
            $this->name.'_menu_cat_home_link' => 1,
            $this->name.'_menu_cat_home_img' => 1,
            // $this->name.'_menu_cat_blog_rss' => 0,
            $this->name.'_menu_cat_blog_nbnews' => 0,
            // Bloc de catégories d'article
            $this->name.'_catnews_showall' => 0,
            $this->name.'_catnews_rss' => 0,
            $this->name.'_catnews_actif' => 0,
            $this->name.'_catnews_empty' => 0,
            $this->name.'_catnews_tree' => 1,
            // page liste d'articles
            $this->name.'_catnews_shownbnews' => 1,
            $this->name.'_catnews_showthumb' => 1,
            $this->name.'_catnews_showintro' => 1,
            // liste des categories
            $this->name.'_thumb_cat_width' => 150,
            $this->name.'_thumb_cat_height' => 150,
            $this->name.'_full_cat_width' => 535,
            $this->name.'_full_cat_height' => 236,
            $this->name.'_cat_title_length' => 80,
            $this->name.'_cat_intro_length' => 120,
            /** /Categorie *******************************/

            /** Lookbook ********************************/
            $this->name.'_lb_title_length' => 80,
            $this->name.'_lb_intro_length' => 120,
            /** /Lookbook *******************************/

            /** Globales *********************************/
            // Configuration du rewrite
            $this->name.'_rewrite_actif' => (int)Configuration::get('PS_REWRITING_SETTINGS'),
            // Configuration générale du front-office
            $this->name.'_nb_liste_page' => 8,
            $this->name.'_article_page' => 2,
            $this->name.'_producttab_actif' => 1,
            $this->name.'_socials_actif' => 1,
            $this->name.'_s_facebook' => 1,
            $this->name.'_s_twitter' => 1,
            $this->name.'_s_linkedin' => 1,
            $this->name.'_s_email' => 1,
            $this->name.'_s_pinterest' => 0,
            $this->name.'_s_pocket' => 0,
            $this->name.'_s_tumblr' => 0,
            $this->name.'_s_reddit' => 0,
            $this->name.'_s_hackernews' => 0,
            $this->name.'_uniqnews_rss' => 0,
            $this->name.'_view_cat_desc' => 1,
            $this->name.'_view_cat_thumb' => 0,
            $this->name.'_view_cat_img' => 1,
            $this->name.'_view_news_img' => 0,
            // liste des produits liés
            $this->name.'_thumb_linkprod_width' => 100,
            // liste d'articles
            $this->name.'_thumb_picture_width' => 129,
            $this->name.'_thumb_picture_height' => 129,
            $this->name.'_news_title_length' => 80,
            $this->name.'_news_intro_length' => 200,
            // Configuration globale de l'administration
            $this->name.'_nb_car_min_linkprod' => 2,
            $this->name.'_nb_list_linkprod' => 5,
            $this->name.'_nb_car_min_linknews' => 2,
            $this->name.'_nb_list_linknews' => 5,
            $this->name.'_nb_car_min_linklb' => 2,
            $this->name.'_nb_list_linklb' => 5,
            $this->name.'_nb_news_pl' => 20,
            $this->name.'_nb_comments_pl' => 20,
            $this->name.'_comment_div_visible' => 0,
            /** /Globales ********************************/

            /** Outils ***********************************/
            // Anitspam
            $this->name.'_antispam_actif' => 0,
             // Sitemap
            $this->name.'_sitemap_actif' => 0,
            $this->name.'_sitemap_articles' => 1,
            $this->name.'_sitemap_categories' => 1,
            $this->name.'_sitemap_limit' => 5000,
            $this->name.'_sitemap_older' => 12,
            $this->name.'_sitemap_token' => $this->genererMDP(8),
            // Importation depuis un XML de WordPress
            $this->name.'_import_xml' => '',
            /** /Outils **********************************/
            $this->name.'_token' => md5($this->genererMDP(32)._COOKIE_KEY_),
        );

        $this->context->smarty->assign(
            array(
                'prestablog_config' => Configuration::getMultiple(array_keys($this->configurations)),
                'md5pic' => md5(time()),
                'prestablog_theme_dir' => _MODULE_DIR_.$this->name.'/views/',
                'prestablog_theme_upimg' => _MODULE_DIR_.$this->name.'/views/img/'.self::getT().'/up-img/'
            )
        );
    }

    private function registerHookPosition($hook_name, $position)
    {
        if ($this->registerHook($hook_name)) {
            $this->updatePosition((int)Hook::getIdByName($hook_name), 0, (int)$position);
        } else {
            return false;
        }
        return true;
    }

    private function registerMetaAndColumnForEachThemes()
    {
        // insertion du meta pour prestablog
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            INSERT INTO `'.bqSQL(_DB_PREFIX_).'meta`
                (`page`, `configurable`)
            VALUES
                (\'module-prestablog-blog\', 1)')) {
            return false;
        }

        $id_meta = (int)Db::getInstance()->Insert_ID();

        if (!Configuration::get('prestablog_id_meta')) {
            Configuration::updateValue('prestablog_id_meta', (int)$id_meta);
        }

        // instertion des meta_lang
        foreach (array_keys(Shop::getShops()) as $id_shop) {
            foreach (Language::getLanguages() as $lang) {
                if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                    INSERT INTO `'.bqSQL(_DB_PREFIX_).'meta_lang`
                        (`id_meta`, `id_shop`, `id_lang`, `title`, `description`, `url_rewrite`)
                    VALUES
                        (
                            '.(int)$id_meta.',
                            '.(int)$id_shop.',
                            '.(int)$lang['id_lang'].',
                            \'PrestaBlog\',
                            \'Blog\',
                            \'module-blog\')')) {
                    return false;
                }
            }
        }

        return true;
    }

    private function deleteMetaAndColumnForEachThemes($id_meta)
    {
        if ((int)$id_meta > 0) {
            if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                DELETE FROM `'.bqSQL(_DB_PREFIX_).'meta`
                WHERE `id_meta` = '.(int)$id_meta)) {
                return false;
            }

            if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                DELETE FROM `'.bqSQL(_DB_PREFIX_).'meta_lang`
                WHERE `id_meta` = '.(int)$id_meta)) {
                return false;
            }

            if (!Configuration::deleteByName('prestablog_id_meta')) {
                return false;
            }
        }

        return true;
    }

    public function initLangueModule($id_lang)
    {
        $this->rss_langue['id_lang'] = $id_lang;
        $this->rss_langue['channel_title'] = Configuration::get('PS_SHOP_NAME').' '.$this->l('news feed');
    }

    public function registerAdminAjaxTab()
    {
        // Prepare tab AdminPrestaBlogAjaxController
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminPrestaBlogAjax';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'PrestaBlogAjax';
        }

        $tab->id_parent = (int)Tab::getCurrentTabId();
        $tab->module = $this->name;

        return $tab->add();
    }

    public function deleteAdminAjaxTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminPrestaBlogAjax');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
    }

    public function registerAdminTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminPrestaBlog';
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'PrestaBlog';
        }

        $tab->id_parent = (int)Tab::getIdFromClassName('AdminTools');
        $tab->module = 'prestablog';
        $tab->icon = 'library_books';

        return $tab->save();
    }

    public function deleteAdminTab()
    {
        foreach (array('AdminPrestaBlog') as $tab_name) {
            $id_tab = (int)Tab::getIdFromClassName($tab_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }


    public function hookDisplayTop()
    {
        $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
		$this->news = new NewsClass((int)Tools::getValue('id'), (int)$this->context->cookie->id_lang);
        if ($id_prestablog_popup = $this->isOkDisplay($this->news->id)) {
        	$id_prestablog_popup = (int)PopupClass::getIdFrontPopupNewsPreFiltered($this->news->id);
            return $this->displayPopup((int)$this->context->language->id, (int)$id_prestablog_popup);
        }
        if ($id_prestablog_popup = $this->isOkDisplayCate($this->categories->id)) {
        	$id_prestablog_popup = (int)PopupClass::getIdFrontPopupCatePreFiltered($this->categories->id);
            return $this->displayPopup((int)$this->context->language->id, (int)$id_prestablog_popup);
        }
        if ($id_prestablog_popup = $this->isOkDisplayHome()) {
        	$popuplink = PopupClass::getPopupActifHome();
    $id_prestablog_popup = $popuplink[0]['id_prestablog_popup'];
            return $this->displayPopup((int)$this->context->language->id, (int)$id_prestablog_popup);
        }
    }
    }

        public function hookDisplayBeforeBodyClosingTag()
    {
    	$prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
        $this->news = new NewsClass((int)Tools::getValue('id'), (int)$this->context->cookie->id_lang);
        if ($id_prestablog_popup = $this->isOkDisplay($this->news->id)) {
        	$id_prestablog_popup = (int)PopupClass::getIdFrontPopupNewsPreFiltered($this->news->id);
            return $this->displayPopup((int)$this->context->language->id, (int)$id_prestablog_popup);
        }
        if ($id_prestablog_popup = $this->isOkDisplayCate($this->categories->id)) {
        	$id_prestablog_popup = (int)PopupClass::getIdFrontPopupCatePreFiltered($this->categories->id);
            return $this->displayPopup((int)$this->context->language->id, (int)$id_prestablog_popup);
        }
        if ($id_prestablog_popup = $this->isOkDisplayHome()) {
        	$popuplink = PopupClass::getPopupActifHome();
    $id_prestablog_popup = $popuplink[0]['id_prestablog_popup'];
            return $this->displayPopup((int)$this->context->language->id, (int)$id_prestablog_popup);
        }
    }
}
    private static function unlinkFile($file)
    {
        if (file_exists($file)) {
            return unlink($file);
        }
    }

    private static function readDirectory($directory)
    {
        return readdir($directory);
    }

    private static function makeDirectory($directory)
    {
        return mkdir($directory);
    }

    public function install()
    {
       // $this->uninstall();

        /*
        * si multiboutique, alors activer le contexte pour installe le module
        * sur toutes les boutiques
        */
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $news = new NewsClass();
        $categories = new CategoriesClass();
        $correspondances_categories = new CorrespondancesCategoriesClass();
        $comment_news = new CommentNewsClass();
        $antispam = new AntiSpamClass();
        $sub_blocks = new SubBlocksClass();
        $lookbook = new LookBookClass();
        $popup = new PopupClass();
        //$author = new AuthorClass();

        $this->installQuickAccess();

        if (!parent::install()
            // ACCROCHES TEMPLATE
            || !$this->registerHookPosition('displayHeader', 1)
            || !$this->registerHookPosition('displayHome', 1)
            || !$this->registerHook('displayTop')
            || !$this->registerHook('displaySlider')
            || !$this->registerHook('displayRating')
            || !$this->registerHookPosition('displayRightColumn', 1)
            || !$this->registerHookPosition('displayLeftColumn', 1)
            || !$this->registerHook('displayFooter')
            || !$this->registerHook('ModuleRoutes')
            || !$this->registerHook('displayPrestaBlogList')
            || !$this->registerHook('displayBeforeBodyClosingTag')

            // ACCROCHES TEMPLATE PRESTASHOP 1.7
            || !$this->installHookPS17()
            // CONFIGURATION & INTEGRATION BASE DE DONNEES
            || !$this->updateConfiguration('add')
            || !$this->metaTitlePageBlog('add')
            // STRUCTURE BASE DE DONNEES
            || !$news->registerTablesBdd()
            || !$categories->registerTablesBdd()
            || !$correspondances_categories->registerTablesBdd()
            || !$comment_news->registerTablesBdd()
            || !$antispam->registerTablesBdd()
            || !$sub_blocks->registerTablesBdd()
            || !$lookbook->registerTablesBdd()
            || !$popup->createTables()
            //|| !$author->createTables()

            // ADMIN CONTROLLERS
            || !$this->registerAdminTab()
            || !$this->registerAdminAjaxTab()
            // META LANG & THEME
            || !$this->registerMetaAndColumnForEachThemes()

            //|| !$this->registerHook('displayTop')
            //|| !$this->registerHook('displayBackOfficeHeader')
        ) {
            return false;
        }

        Tools::clearCache();

        return true;
    }

        public static function createDynInstance($class, $params = array())
    {
        $reflection_class = new ReflectionClass($class);
        return $reflection_class->newInstanceArgs($params);
    }


    public function displayContent()
    {


        if (Tools::getValue('class') && in_array(Tools::getValue('class'), $this->class_used['table'])) {

            $current_class = Tools::getValue('class');
            if (!class_exists($current_class)) {
                $this->html_out .= $this->displayError($this->l('This class doesn\'t exists: ').$current_class);

            } else {
                $object_model = self::createDynInstance($current_class, array());

                if (is_object($object_model)) {
                    $definition_lang = $object_model->definitionLang();
                    if (!Tools::isSubmit('add')
                        && !Tools::isSubmit('edit')
                        && !Tools::getIsset('add'.$definition_lang['tableName'])
                        && !Tools::getIsset('update'.$definition_lang['tableName'])
                    ) {
                        $this->html_out .= $object_model->displayList();
                    }
                    if (Tools::getIsset('add'.$definition_lang['tableName']) || Tools::isSubmit('add')) {
                        $this->html_out .= $object_model->displayForm('add');
                    }
                    if (Tools::getIsset('update'.$definition_lang['tableName']) || Tools::isSubmit('edit')) {
                        $this->html_out .= $object_model->displayForm('edit');
                    }
                }
            }
        }
    }

public function isOkDisplay($news)
    {
    	$prestaboost = Module::getInstanceByName('prestaboost');
        if (!$prestaboost) {
                $id_prestablog_popup = (int)PopupClass::getIdFrontPopupNewsPreFiltered($news);

        if ((int)$id_prestablog_popup > 0) {
            $popup = new PopupClass((int)$id_prestablog_popup, (int)$this->context->language->id);

            $popup_hash = md5(
                $popup->id
                .$popup->date_start
                .$popup->date_stop
                .$popup->delay
                .$popup->expire
                .$popup->expire_ratio
                .$popup->theme
                .$popup->restriction_rules
                .$popup->restriction_pages
                .$popup->footer
                .$popup->title
                .$popup->content
                .$popup->pop_colorpicker_content
                .$popup->pop_colorpicker_modal
                .$popup->pop_colorpicker_btn
                .$popup->pop_colorpicker_btn_border
                .$popup->pop_opacity_content
                .$popup->pop_opacity_modal
                .$popup->pop_opacity_btn
            );

            if (!isset($_COOKIE['PopupCookie'.$popup_hash])) {
                setcookie(
                    'PopupCookie'.$popup_hash,
                    '1',
                    (time() + ((int)$popup->expire_ratio * (int)$popup->expire))
                );
                parent::_clearCache('header.tpl');
                return (int)$id_prestablog_popup;
            }
        }
    }
}


public function isOkDisplayCate($categorie)
    {

    	$prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
                $id_prestablog_popup = (int)PopupClass::getIdFrontPopupCatePreFiltered($categorie);

        if ((int)$id_prestablog_popup > 0) {
            $popup = new PopupClass((int)$id_prestablog_popup, (int)$this->context->language->id);

            $popup_hash = md5(
                $popup->id
                .$popup->date_start
                .$popup->date_stop
                .$popup->delay
                .$popup->expire
                .$popup->expire_ratio
                .$popup->theme
                .$popup->restriction_rules
                .$popup->restriction_pages
                .$popup->footer
                .$popup->title
                .$popup->content
                .$popup->pop_colorpicker_content
                .$popup->pop_colorpicker_modal
                .$popup->pop_colorpicker_btn
                .$popup->pop_colorpicker_btn_border
                .$popup->pop_opacity_content
                .$popup->pop_opacity_modal
                .$popup->pop_opacity_btn
            );

            if (!isset($_COOKIE['PopupCookie'.$popup_hash])) {
                setcookie(
                    'PopupCookie'.$popup_hash,
                    '1',
                    (time() + ((int)$popup->expire_ratio * (int)$popup->expire))
                );
                parent::_clearCache('header.tpl');
                return (int)$id_prestablog_popup;
            }
        }
    }
}

public function isOkDisplayHome()
    {
        $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
    $popuplink = PopupClass::getPopupActifHome();

if (isset($popuplink[0])) {
    $id_prestablog_popup = $popuplink[0]['id_prestablog_popup'];

        if ((int)$id_prestablog_popup > 0) {
            $popup = new PopupClass((int)$id_prestablog_popup, (int)$this->context->language->id);

            $popup_hash = md5(
                $popup->id
                .$popup->date_start
                .$popup->date_stop
                .$popup->delay
                .$popup->expire
                .$popup->expire_ratio
                .$popup->theme
                .$popup->restriction_rules
                .$popup->restriction_pages
                .$popup->footer
                .$popup->title
                .$popup->content
                .$popup->pop_colorpicker_content
                .$popup->pop_colorpicker_modal
                .$popup->pop_colorpicker_btn
                .$popup->pop_colorpicker_btn_border
                .$popup->pop_opacity_content
                .$popup->pop_opacity_modal
                .$popup->pop_opacity_btn
            );

            if (!isset($_COOKIE['PopupCookie'.$popup_hash])) {
                setcookie(
                    'PopupCookie'.$popup_hash,
                    '1',
                    (time() + ((int)$popup->expire_ratio * (int)$popup->expire))
                );
                parent::_clearCache('header.tpl');
                return (int)$id_prestablog_popup;
            }
        }
    }
}
}
 public static function isNotRestrictionHome()
    {

        $url_current = Tools::getCurrentUrlProtocolPrefix().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url_split = array_slice(explode('/', $url_current), -1)[0];
        $return = null;
                if ($url_split == 'blog') {
                    $return = true;
                } else {
                    $return = false;
                }

        return $return;
    }

 public static function isNotRestrictionCate($cate)
    {

        $url_current = Tools::getCurrentUrlProtocolPrefix().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url_split = explode('-',$url_current);
    $cate_id = (int)str_replace('c', '', $url_split[count($url_split) -1]);


        $return = null;


                if ($cate_id == $cate) {
                    $return = true;
                } else {
                    $return = false;
                }

        return $return;
    }

 public static function isNotRestrictionNews($news)
    {

        $url_current = Tools::getCurrentUrlProtocolPrefix().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url_split = explode('-',$url_current);
        $id = str_replace('n', '', $url_split[count($url_split) -1]);


        $return = null;


                if ($id == $news) {
                    $return = true;
                } else {
                    $return = false;
                }

        return $return;
    }

    public static function isNotRestrictionPage($restriction_rules, $restriction_pages)
    {
        $urls = preg_split("/\r/", $restriction_pages);
        $urls_ok = array();
        $restriction_rules = (int)$restriction_rules;

        $url_current = Tools::getCurrentUrlProtocolPrefix().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        foreach ($urls as $url) {
            $url = trim($url);
            if (preg_match('/^(http|https):\/\/*/i', $url)) {
                $urls_ok[] = $url;
            }
        }

        $return = null;

        switch ($restriction_rules) {
            case 0: // on all pages
                $return = true;
                break;

            case 1: // on all pages, except urls_ok
                if (in_array($url_current, $urls_ok)) {
                    $return = false;
                } else {
                    $return = true;
                }
                break;

            case 2: // only on pages in urls_ok
                if (in_array($url_current, $urls_ok)) {
                    $return = true;
                } else {
                    $return = false;
                }
                break;
        }
        return $return;
    }

    public function displayFixJSConfirmDuplicate()
    {
        $pathM = AdminController::$currentIndex.'&configure=prestablog&token='.Tools::getAdminTokenLite('AdminModules');
        $urlSupp = '&class=PopupClass&duplicatepopup';
        return '
            <script type="text/javascript">
                $(function () {
                    var onclickBefore = $("ul.dropdown-menu li a[title='.$this->l('Duplicate').']").attr("onclick");
                    var id_prestablog_popup = onclickBefore.match(/\d+/)[0];

                    var onclickAfter = "if (confirm(
                        \''.$this->l('Confirm duplicate this popup?').'\'
                    )) {
                        document.location = \''.$pathM.$urlSupp.'&id_prestablog_popup=\'+id_prestablog_popup;
                    } else {
                        return false;
                    }";

                    $("ul.dropdown-menu li a[title='.$this->l('Duplicate').']").attr("onclick", onclickAfter);
                });
            </script>';
    }
    public static function popupContent($params)
    {
        return $params['return'];
    }

    public $actions_form = array('addpopupsubmit', 'editpopupsubmit');

    public $class_used = array(
        'table' => array(
            'PopupClass'
        ),
        'dashboard' => array(),
        'config' => array(),
    );

    public function popupProcess()
    {
        $update_process = false;
        $errors = array();
        $warnings = array();


        if ((int)Tools::getIsset('success')) {
            $this->html_out .= '
                <script type="text/javascript">
                    $(function () {
                        showSuccessMessage(\''.$this->l('Settings updated successfully').'\');
                    });
                </script>';

        }
        if ((int)Tools::getIsset('undo') || (int)Tools::getIsset('error')) {
            $this->html_out .= '
                <script type="text/javascript">
                    $(function () {
                        showErrorMessage(\''.$this->l('The current action was canceled').'\');
                    });
                </script>';
        }
        foreach ($this->class_used['table'] as $object_model) {

            $pp = $this->pp_conf.'&class='.$object_model;
            if (Tools::getValue('class') == $object_model) {
                $definition = ObjectModel::getDefinition($object_model);
                if (Tools::isSubmit('statuspopup')) {
                    $process_model = self::createDynInstance($object_model);
                    if (!$process_model->changeState((int)Tools::getValue($definition['primary']))) {
                        $errors[] = $this->l('Could not change status.');
                    } else {
                        Tools::redirectAdmin($pp.'&displayContent&success');
                    }
                    $update_process = true;
                }


                if (Tools::isSubmit('deletepopup')) {

                    $process_model = self::createDynInstance(
                        $object_model,
                        array((int)Tools::getValue($definition['primary']))
                    );
                    if (!$process_model->deletepopup()) {
                        $errors[] = $this->l('Could not delete.');
                    } else {
                        Tools::redirectAdmin($pp.'&displayContent&success');
                    }
                    $update_process = true;
                }
                if (Tools::isSubmit('submitBulkdeletepopup')) {
                    if (Tools::getValue($definition['table'].'Box')) {
                        foreach (Tools::getValue($definition['table'].'Box') as $id) {
                            $process_model = self::createDynInstance($object_model, array((int)$id));
                            if (!$process_model->deletepopup()) {
                                $errors[] = sprintf($this->l('Could not delete %1$s'), $id);
                            }
                        }
                        if (!count($errors) > 0) {
                            Tools::redirectAdmin($pp.'&displayContent&success');
                        }
                    }
                    $update_process = true;
                }

                foreach ($this->actions_form as $action) {
                    if (Tools::isSubmit($action)) {
                        switch ($action) {
                            case 'addpopupsubmit':
                                $process_model = self::createDynInstance($object_model);
                                $process_model->copyFromPost();
                                if ($process_model->add()) {
                                    Tools::redirectAdmin($pp.'&displayContent&success');
                                }
                                break;

                            case 'editpopupsubmit':
                                $process_model = self::createDynInstance(
                                    $object_model,
                                    array((int)Tools::getValue($definition['primary']))
                                );
                                $process_model->copyFromPost();
                                if ($process_model->update()) {
                                    Tools::redirectAdmin(
                                        $pp.'&'.$definition['primary'].'='.(int)Tools::getValue(
                                            $definition['primary']
                                        ).'&displayContent&success'
                                    );
                                }
                                break;
                        }
                        $update_process = true;
                    }
                }
            }
        }

        if (count($errors) > 0) {
            $this->html_out .= $this->displayError($errors);
        }
        if (count($warnings) > 0) {
            $this->html_out .= $this->displayWarning($warnings);
        }
        if ($this->html_out != '') {
            return $this->html_out;
        }
        if ($update_process) {
            return $this->displayConfirmation($this->l('Settings updated successfully'));
        } else {
            return null;
        }
    }


    public function displayPopup($id_lang, $id_prestablog_popup = 0)
    {
$prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
        $popup = new PopupClass((int)$id_prestablog_popup, (int)$id_lang);

        $this->context->controller->addCSS($this->_path.'views/css/theme-'.$popup->theme.'.css', 'all');

        $this->smarty->assign(array(
            'adminPreview' => true,
            'id_lang' => $id_lang,
            'Popup' => $popup
        ));

        // permet d'échaper tout le contenu html / js
        if (!isset($this->context->smarty->registered_plugins['function']['PopupContent'])) {
            smartyRegisterFunction(
                $this->context->smarty,
                'function',
                'PopupContent',
                array('PopupClass', 'popupContent')
            );
        }

        parent::_clearCache($popup->theme.'.tpl');
        return $this->display(__FILE__, $popup->theme.'.tpl');
    }
}

    public static function scanThemeTpl()
    {
        $return = array();

        foreach (glob(dirname(__FILE__).'/views/templates/hook/*.{tpl}', GLOB_BRACE) as $file) {
            if (!is_dir($file)) {
            	if (($file == dirname(__FILE__).'/views/templates/hook/colorpicker.tpl') || ($file == dirname(__FILE__).'/views/templates/hook/lite-popup.tpl')) {
                $return[] = array(
                    'id' => basename($file, '.tpl'),
                    'name' => basename($file, '.tpl')
                );
            }
            }
        }

        return $return;
    }
    public function installHookPS17()
    {
        if (!$this->registerHook('displayNav')
            || !$this->registerHook('displayNav2')
            || !$this->registerHook('displayFooterProduct')
            || !$this->registerHook('displayBackOfficeHeader')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        $news = new NewsClass();
        $categories = new CategoriesClass();
        $correspondances_categories = new CorrespondancesCategoriesClass();
        $comment_news = new CommentNewsClass();
        $antispam = new AntiSpamClass();
        $sub_blocks = new SubBlocksClass();
        $lookbook = new LookBookClass();
        $popup = new PopupClass();


        $this->uninstallQuickAccess();

        if (!parent::uninstall()
            // META LANG & THEME
            || !$this->deleteMetaAndColumnForEachThemes((int)Configuration::get('prestablog_id_meta'))
            // CONFIGURATION & INTEGRATION BASE DE DONNEES
            || !$this->updateConfiguration('del')
            || !$this->metaTitlePageBlog('del')
            // STRUCTURE BASE DE DONNEES
            || !$news->deleteTablesBdd()
            || !$categories->deleteTablesBdd()
            || !$correspondances_categories->deleteTablesBdd()
            || !$comment_news->deleteTablesBdd()
            || !$antispam->deleteTablesBdd()
            || !$sub_blocks->deleteTablesBdd()
            || !$lookbook->deleteTablesBdd()
            || !$popup->dropTables()
            // ADMIN CONTROLLERS
            || !$this->deleteAdminTab()
            || !$this->deleteAdminAjaxTab()
            // SITEMAPS
            || !$this->deleteAllSitemap()

        ) {
            return false;
        }

        Tools::clearCache();

        return true;
    }

    public function installQuickAccess()
    {
        $qa = new QuickAccess;
        foreach (Language::getLanguages(true) as $language) {
            $qa->name[(int)$language['id_lang']] = $this->displayName;
        }
        $qa->link = 'index.php?controller=AdminModules&configure=prestablog&module_name='.$this->name;
        $qa->new_window = 0;
        $qa->Add();
        Configuration::updateValue($this->name.'_QuickAccess', $qa->id);
        return true;
    }

    public function uninstallQuickAccess()
    {
        $qa = new QuickAccess((int)Configuration::get($this->name.'_QuickAccess'));
        $qa->delete();
        Configuration::deleteByName($this->name.'_QuickAccess');
        return true;
    }

    private function deleteAllSitemap()
    {
        $shops = Shop::getShops();
        foreach (array_keys($shops) as $key_shop) {
            $this->deleteSitemapFromShop((int)$key_shop);
        }

        return true;
    }

    private function updateConfiguration($action)
    {
        switch ($action) {
            case 'add':
                $shops = Shop::getShops();
                foreach (array_keys($shops) as $key_shop) {
                    foreach ($this->configurations as $configuration_key => $configuration_value) {
                        Configuration::updateValue($configuration_key, $configuration_value, false, null, $key_shop);
                    }
                }
                foreach ($this->configurations as $configuration_key => $configuration_value) {
                    Configuration::updateValue($configuration_key, $configuration_value);
                }
                break;

            case 'del':
                foreach ($this->configurations as $configuration_key => $configuration_value) {
                    Configuration::deleteByName($configuration_key);
                }
                break;
        }
        return true;
    }

    private function checkConfiguration()
    {
        foreach ($this->configurations as $configuration_key => $configuration_value) {
            if (!Configuration::getIdByName($configuration_key, null, (int)$this->context->shop->id)) {
                Configuration::updateValue(
                    $configuration_key,
                    $configuration_value,
                    false,
                    null,
                    (int)$this->context->shop->id
                );
            }
            if (!Configuration::getIdByName($configuration_key)) {
                Configuration::updateValue($configuration_key, $configuration_value);
            }
        }
    }

    private function metaTitlePageBlog($action)
    {
        $languages = Language::getLanguages(true);

        switch ($action) {
            case 'add':
                $languages = Language::getLanguages(true);

                $meta_title_config_lang = array();
                $meta_description_config_lang = array();
                $title_h1_config_lang = array();

                foreach ($languages as $language) {
                    $meta_title_config_lang[(int)$language['id_lang']] = $this->l('Blog');
                    $meta_description_config_lang[(int)$language['id_lang']] = '';
                    $title_h1_config_lang[(int)$language['id_lang']] = '';
                }

                Configuration::updateValue($this->name.'_titlepageblog', $meta_title_config_lang);
                Configuration::updateValue($this->name.'_descpageblog', $meta_description_config_lang);
                Configuration::updateValue($this->name.'_h1pageblog', $title_h1_config_lang);

                break;

            case 'del':
                $languages = Language::getLanguages();
                foreach ($languages as $language) {
                    Configuration::deleteByName($this->name.'_titlepageblog');
                    Configuration::deleteByName($this->name.'_descpageblog');
                    Configuration::deleteByName($this->name.'_h1pageblog');
                }
                break;
        }
        return true;
    }


    /**
     * Create the form that will be displayed in the configuration of your module.
     */
  protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDisplaysliderModule';
        $helper->currentIndex = $this->confpath ;
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));

    }
    /**
     * Create the structure of your form.
     */
   protected function getConfigForm()
    {


        $table = _DB_PREFIX_.'layerslider';
        $rows = Db::getInstance()->executeS("SELECT CONCAT('#', id, ' ', name) AS name, id FROM $table WHERE flag_deleted = 0");
        for ($i = 0; isset($rows[$i]); $i++) {
        	$options[0] = array('name' => '- None -', 'id' => '0');
            $options[$i+1] = $rows[$i];
        }
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings of Creative slider\'s slides'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select a slider'),
                        'desc' => $this->l('If you select a slider from Creative slider, please switch off the Prestablog\'s slideshow on the right side.'),
                        'name' => 'DISPLAYSLIDER_ID',
                        'options' => array(
                            'name' => 'name',
                            'id' => 'id',
                            'query' => $options,
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),

            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array('DISPLAYSLIDER_ID' => Configuration::get('DISPLAYSLIDER_ID', 0));
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }



    private function postForm()
    {

        $errors = array();
        $post_en_cours = false;
        $config_theme = $this->getConfigXmlTheme(self::getT());
        $languages = Language::getLanguages();

        $this->check_slide = 0;
        $this->check_active = 0;

        if (Tools::getValue('submitFiltreNews')) {
            if (Tools::getValue('slide')) {
                $this->check_slide = 1;
            } else {
                $this->check_slide = 0;
            }
            if (Tools::getValue('activeNews')) {
                $this->check_active = 1;
            } else {
                $this->check_active = 0;
            }
        } else {
            if (Tools::getValue('slideget') == 1) {
                $this->check_slide = 1;
            } else {
                $this->check_slide = 0;
            }
            if (Tools::getValue('activeget') == 1) {
                $this->check_active = 1;
            } else {
                $this->check_active = 0;
            }
        }

        if (Tools::getValue('submitFiltreComment')) {
            $this->check_comment_state = Tools::getValue('activeComment');
        } else {
            if (Tools::getValue('activeCommentget')) {
                $this->check_comment_state = Tools::getValue('activeCommentget');
            } else {
                $this->check_comment_state = -2;
            }
        }

        $this->confpath .= '&activeget='.$this->check_active;
        $this->confpath .= '&slideget='.$this->check_slide;
        $this->confpath .= '&activeCommentget='.$this->check_comment_state;

        if (Tools::isSubmit('deleteNews') && Tools::getValue('idN')) {
            $post_en_cours = true;
            $news = new NewsClass((int)Tools::getValue('idN'));
            if (!$news->delete()) {
                $errors[] = $this->l('An error occurred while delete news.');
            } else {
                $this->deleteAllImagesThemes((int)$news->id);
                CorrespondancesCategoriesClass::delAllCategoriesNews((int)$news->id);
                Tools::redirectAdmin($this->confpath.'&newsListe');
            }
        } elseif (Tools::isSubmit('deleteCat') && Tools::getValue('idC')) {
            $post_en_cours = true;
            $categorie = new CategoriesClass((int)Tools::getValue('idC'));
            if (!$categorie->delete()) {
                $errors[] = $this->l('An error occurred while delete categorie.');
            } else {
                $this->deleteAllImagesThemesCat((int)$categorie->id);
                CorrespondancesCategoriesClass::delAllCorrespondanceNewsAfterDelCat((int)$categorie->id);
                SubBlocksClass::delAllCorrespondanceAfterDelCat((int)$categorie->id);
                Tools::redirectAdmin($this->confpath.'&catListe');
            }
        } elseif (Tools::isSubmit('deleteLookBook') && Tools::getValue('idLB')) {
            $post_en_cours = true;
            $lookbook = new LookBookClass((int)Tools::getValue('idLB'));
            if (!$lookbook->delete()) {
                $errors[] = $this->l('An error occurred while delete Lookbook.');
            } else {
                if (!LookBookClass::delProductsShapeFromLookBook((int)$lookbook->id)) {
                    $errors[] = $this->l('An error occurred while delete products to lookbook.');
                }
                $this->deleteAllImagesThemesLookbook((int)$lookbook->id);
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&lookbookListe');
            }
        } elseif (Tools::isSubmit('deleteAntiSpam') && Tools::getValue('idAS')) {
            $post_en_cours = true;
            $antispam = new AntiSpamClass((int)Tools::getValue('idAS'));
            if (!$antispam->delete()) {
                $errors[] = $this->l('An error occurred while delete antispam question.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configAntiSpam');
            }
        } elseif (Tools::isSubmit('etatNews') && Tools::getValue('idN')) {
            $post_en_cours = true;
            $news = new NewsClass((int)Tools::getValue('idN'));
            if (!$news->changeEtat('actif')) {
                $errors[] = $this->l('An error occurred while change status of news.');
            } else {
                Tools::redirectAdmin($this->confpath.'&newsListe');
            }
        } elseif (Tools::isSubmit('etatLookbook') && Tools::getValue('idLB')) {
            $post_en_cours = true;
            $lookbook = new LookBookClass((int)Tools::getValue('idLB'));
            if (!$lookbook->changeEtat('actif')) {
                $errors[] = $this->l('An error occurred while change status of news.');
            } else {
                Tools::redirectAdmin($this->confpath.'&lookbookListe');
            }
        } elseif (Tools::isSubmit('slideNews') && Tools::getValue('idN')) {
            $post_en_cours = true;
            $news = new NewsClass((int)Tools::getValue('idN'));
            if (!$news->changeEtat('slide')) {
                $errors[] = $this->l('An error occurred while change status of slide.');
            } else {
                Tools::redirectAdmin($this->confpath.'&newsListe');
            }
        } elseif (Tools::isSubmit('etatCat') && Tools::getValue('idC')) {
            $post_en_cours = true;
            $categories = new CategoriesClass((int)Tools::getValue('idC'));
            if (!$categories->changeEtat('actif')) {
                $errors[] = Tools::displayError('An error occurred while change status object.');
            } else {
                Tools::redirectAdmin($this->confpath.'&catListe');
            }
        } elseif (Tools::isSubmit('etatAntiSpam') && Tools::getValue('idAS')) {
            $post_en_cours = true;
            $antispam = new AntiSpamClass((int)Tools::getValue('idAS'));
            if (!$antispam->changeEtat('actif')) {
                $errors[] = $this->l('An error occurred while change status of antispam question.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configAntiSpam');
            }
        } elseif (Tools::isSubmit('submitAddNews')) {
            $post_en_cours = true;

            if (!count(Tools::getValue('languesup'))) {
                $errors[] = $this->l('You must activate at least one language');
            } else if (!Tools::getValue('categories')) {
                $errors[] = $this->l('You must choose at least one categorie');
            } else {
                foreach ($languages as $language) {
                    if (!Tools::getValue('title_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The title must be specified');
                    }
                    if (!Tools::getValue('link_rewrite_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The url rewrite must be specified');
                    }

                    $summary = Tools::getValue('paragraph_'.$language['id_lang']);
                    $content = Tools::getValue('content_'.$language['id_lang']);
                   /* $layerslider = Module::getInstanceByName('layerslider');
                    if ($layerslider) {
                    $content = $layerslider->filterShortcode($content); //  $content should contain the blog post content
                    $html = $layerslider->generateSlider($id_slider);
                    }*/

                    if (!$summary
                        && !$content
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The content or introduction must be specified');
                    }
                }
            }

            if (!count($errors)) {
                $news = new NewsClass();
                $news->id_shop = (int)$this->context->shop->id;
                $news->copyFromPost();
                $news->langues = serialize(Tools::getValue('languesup'));
                if (!$news->add()) {
                    $errors[] = $this->l('An error occurred while add object.');
                }

                NewsClass::removeAllProductsLinkNews((int)$news->id);
                if (Tools::getValue('productsLink')) {
                    foreach (Tools::getValue('productsLink') as $product_link) {
                        NewsClass::updateProductLinkNews((int)$news->id, (int)$product_link);
                    }
                }

                NewsClass::removeAllArticlesLinkNews((int)$news->id);
                if (Tools::getValue('articlesLink')) {
                    foreach (Tools::getValue('articlesLink') as $article_link) {
                        NewsClass::updateArticleLinkNews((int)$news->id, (int)$article_link);
                    }
                }



                NewsClass::removeAllLookBooksLinkNews((int)$news->id);
                if (Tools::getValue('lookbooksLink')) {
                    foreach (Tools::getValue('lookbooksLink') as $lookbook_link) {
                        NewsClass::updateLookbookLinkNews((int)$news->id, (int)$lookbook_link);
                    }
                }
                NewsClass::removeAllPopupLinkNews((int)$news->id);

                if (Tools::getValue('popupLink')) {
                        NewsClass::updatePopupLinkNews((int)$news->id, Tools::getValue('popupLink'));
                }

                $news->razEtatLangue((int)$news->id);
                foreach ($languages as $language) {
                    if (in_array($language['id_lang'], Tools::getValue('languesup'))) {
                        $news->changeActiveLangue((int)$news->id, (int)$language['id_lang']);
                    }
                }
                if (!$this->demo_mode) {
                    if ($_FILES['homepage_logo']['name']) {
                        if (!$this->uploadImage(
                            $_FILES['homepage_logo'],
                            $news->id,
                            $this->normal_image_size_width,
                            $this->normal_image_size_height
                        )) {
                            $errors[] = $this->l('An error occurred while upload image.');
                        } else {
                            foreach (self::scanListeThemes() as $theme) {
                                $config_theme = $this->getConfigXmlTheme($theme);
                                $this->imageResize(
                                    self::imgPath().$theme.'/up-img/'.$news->id.'.jpg',
                                    self::imgPath().$theme.'/up-img/admincrop_'.$news->id.'.jpg',
                                    (int)$this->admin_crop_image_size_width,
                                    (int)$this->admin_crop_image_size_height
                                );

                                $this->autocropImage(
                                    $news->id.'.jpg',
                                    self::imgPath().$theme.'/up-img/',
                                    self::imgPath().$theme.'/up-img/',
                                    (int)$this->admin_thumb_image_size_width,
                                    (int)$this->admin_thumb_image_size_height,
                                    'adminth_',
                                    null
                                );

                                $config_theme_array = PrestaBlog::objectToArray($config_theme);
                                foreach ($config_theme_array['images'] as $key_theme_array => $value_theme_array) {
                                    $this->autocropImage(
                                        $news->id.'.jpg',
                                        self::imgPath().$theme.'/up-img/',
                                        self::imgPath().$theme.'/up-img/',
                                        (int)$value_theme_array['width'],
                                        (int)$value_theme_array['height'],
                                        $key_theme_array.'_',
                                        null
                                    );
                                }
                            }
                        }
                    }
                }

                if (!count($errors)) {
                    if (!Tools::getValue('categories')) {
                        CorrespondancesCategoriesClass::delAllCategoriesNews($news->id);
                    } else {
                        CorrespondancesCategoriesClass::delAllCategoriesNews($news->id);
                        CorrespondancesCategoriesClass::updateCategoriesNews(Tools::getValue('categories'), $news->id);
                    }
                    Tools::redirectAdmin($this->confpath.'&newsListe');
                }
            }
        } elseif (Tools::isSubmit('submitAddCat')) {
            $post_en_cours = true;

            if (!Tools::getValue('title_'.$this->langue_default_store)) {
                $errors[] = '
                    <img
                        src="'._PS_IMG_.'l/'.$this->langue_default_store.'.jpg"
                    /> '.$this->l('The title must be specified');
            }

            $categories = new CategoriesClass();
            $categories->id_shop = (int)$this->context->shop->id;
            $categories->copyFromPost();
            $categories->position = (int)$categories->getLastPosition();

            if (!count($errors)) {
                if (!$categories->add()) {
                    $errors[] = $this->l('An error occurred while add object.');
                } else {
                    if (!CategoriesClass::injectGroupsInCategorie(Tools::getValue('groupBox'), (int)$categories->id)) {
                        $errors[] = $this->l('An error occurred while update object.').' - '.$this->l('Groups');
                    }

                    if (!$this->demo_mode) {
                        if ($_FILES['imageCategory']['name']) {
                            if (!$this->uploadImage(
                                $_FILES['imageCategory'],
                                $categories->id,
                                $this->normal_image_size_width,
                                $this->normal_image_size_height,
                                'c'
                            )) {
                                $errors[] = $this->l('An error occurred while upload image.');
                            } else {
                                foreach (self::scanListeThemes() as $theme) {
                                    $this->imageResize(
                                        self::imgPath().$theme.'/up-img/c/'.$categories->id.'.jpg',
                                        self::imgPath().$theme.'/up-img/c/admincrop_'.$categories->id.'.jpg',
                                        (int)$this->admin_crop_image_size_width,
                                        (int)$this->admin_crop_image_size_height
                                    );

                                    $this->autocropImage(
                                        $categories->id.'.jpg',
                                        self::imgPath().$theme.'/up-img/c/',
                                        self::imgPath().$theme.'/up-img/c/',
                                        (int)$this->admin_thumb_image_size_width,
                                        (int)$this->admin_thumb_image_size_height,
                                        'adminth_',
                                        null
                                    );

                                    $config_theme_array = PrestaBlog::objectToArray($config_theme);
                                    foreach ($config_theme_array['categories'] as $kta => $vta) {
                                        $this->autocropImage(
                                            $categories->id.'.jpg',
                                            self::imgPath().$theme.'/up-img/c/',
                                            self::imgPath().$theme.'/up-img/c/',
                                            (int)$vta['width'],
                                            (int)$vta['height'],
                                            $kta.'_',
                                            null
                                        );
                                    }
                                }
                            }
                        }
                    }
                     CategoriesClass::removeAllPopupLinkCategorie((int)$categories->id);
                if (Tools::getValue('popupLinkCate')) {

                        CategoriesClass::updatePopupLinkCategorie((int)$categories->id, (int)Tools::getValue('popupLinkCate'));
                }
                }
            }

            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&catListe');
            }
        } elseif (Tools::isSubmit('submitAddAntiSpam')) {
            $post_en_cours = true;

            if (!Tools::getValue('question_'.$this->langue_default_store)) {
                $errors[] = '
                    <img
                        src="'._PS_IMG_.'l/'.$this->langue_default_store.'.jpg"
                    /> '.$this->l('The question must be specified');
            }
            if (!Tools::getValue('reply_'.$this->langue_default_store)) {
                $errors[] = '
                    <img
                        src="'._PS_IMG_.'l/'.$this->langue_default_store.'.jpg"
                    /> '.$this->l('The reply must be specified');
            }

            if (!count($errors)) {
                $antispam = new AntiSpamClass();
                $antispam->id_shop = (int)$this->context->shop->id;
                $antispam->copyFromPost();

                if (!$antispam->add()) {
                    $errors[] = $this->l('An error occurred while add object.');
                } else {
                    $antispam->reloadChecksum();
                    Tools::redirectAdmin($this->confpath.'&configAntiSpam');
                }
            }
        } elseif (Tools::isSubmit('etatSubBlock') && Tools::getValue('idSB')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSB'));
            if (!$sub_blocks->changeEtat('actif')) {
                $errors[] = $this->l('An error occurred while change status of custom articles list.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('randSubBlock') && Tools::getValue('idSB')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSB'));
            if (!$sub_blocks->changeEtat('random')) {
                $errors[] = $this->l('An error occurred while change random status of custom articles list.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('blog_linkSubBlock') && Tools::getValue('idSB')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSB'));
            if (!$sub_blocks->changeEtat('blog_link')) {
                $errors[] = $this->l('An error occurred while change random status of custom articles list.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('etatSubBlockFront') && Tools::getValue('idSBF')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSBF'));
            if (!$sub_blocks->changeEtat('actif')) {
                $errors[] = $this->l('An error occurred while change status of custom articles list.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('randSubBlockFront') && Tools::getValue('idSBF')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSBF'));
            if (!$sub_blocks->changeEtat('random')) {
                $errors[] = $this->l('An error occurred while change random status of custom articles list.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('blog_linkSubBlockFront') && Tools::getValue('idSBF')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSBF'));
            if (!$sub_blocks->changeEtat('blog_link')) {
                $errors[] = $this->l('An error occurred while change random status of custom articles list.');
            } else {
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('submitAddSubBlock')) {
            $post_en_cours = true;

            if (!count(Tools::getValue('languesup'))) {
                $errors[] = $this->l('You must activate at least one language');
            } else {
                foreach ($languages as $language) {
                    if (!Tools::getValue('title_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The title must be specified');
                    }
                }
            }

            if (!count($errors)) {

                $sub_blocks = new SubBlocksClass();
                $sub_blocks->id_shop = (int)$this->context->shop->id;
                $sub_blocks->copyFromPost();
                $sub_blocks->langues = serialize(Tools::getValue('languesup'));

                $sub_blocks->position = (int)$sub_blocks->getLastPosition();

                if (!$sub_blocks->add()) {
                    $errors[] = $this->l('An error occurred while add object.');
                } else {
                    if (!Tools::getValue('categories')) {
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                    } else {
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                        SubBlocksClass::updateCategories(Tools::getValue('categories'), $sub_blocks->id);
                    }
                    Tools::redirectAdmin($this->confpath.'&configSubBlocks');
                }
            }
        } elseif (Tools::isSubmit('submitAddSubBlockFront')) {
            $post_en_cours = true;

            if (!count(Tools::getValue('languesup'))) {
                $errors[] = $this->l('You must activate at least one language');
            } else {
                foreach ($languages as $language) {
                    if (!Tools::getValue('title_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The title must be specified');
                    }
                }
            }

            if (!count($errors)) {
                $sub_blocks = new SubBlocksClass();
                $sub_blocks->id_shop = (int)$this->context->shop->id;
                $sub_blocks->copyFromPost();
                $sub_blocks->langues = serialize(Tools::getValue('languesup'));
                $sub_blocks->position = (int)$sub_blocks->getLastPosition();

                if (!$sub_blocks->add()) {
                    $errors[] = $this->l('An error occurred while add object.');
                } else {
                    if (!Tools::getValue('categories')) {
SubBlocksClass::updateSubBlock($sub_blocks->id);
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                    } else {
SubBlocksClass::updateSubBlock($sub_blocks->id);
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                        SubBlocksClass::updateCategories(Tools::getValue('categories'), $sub_blocks->id);
                    }
                    Tools::redirectAdmin($this->confpath.'&pageBlog');
                }
            }
        } elseif (Tools::isSubmit('submitUpdateSubBlock') && Tools::getValue('idSB')) {
            $post_en_cours = true;

            if (!count(Tools::getValue('languesup'))) {
                $errors[] = $this->l('You must activate at least one language');
            } else {
                foreach ($languages as $language) {
                    if (!Tools::getValue('title_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The title must be specified');
                    }
                }
            }

            if (!count($errors)) {
                $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSB'));
                $sub_blocks->copyFromPost();
                $sub_blocks->langues = serialize(Tools::getValue('languesup'));

                if (!$sub_blocks->update()) {
                    $errors[] = $this->l('An error occurred while update object.');
                }

                if (!count($errors)) {
                    if (!Tools::getValue('categories')) {
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                    } else {
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                        SubBlocksClass::updateCategories(Tools::getValue('categories'), $sub_blocks->id);
                    }
                }
            }
        } elseif (Tools::isSubmit('submitUpdateSubBlockFront') && Tools::getValue('idSBF')) {
            $post_en_cours = true;

            if (!count(Tools::getValue('languesup'))) {
                $errors[] = $this->l('You must activate at least one language');
            } else {
                foreach ($languages as $language) {
                    if (!Tools::getValue('title_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The title must be specified');
                    }
                }
            }

            if (!count($errors)) {
                $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSBF'));
                $sub_blocks->copyFromPost();
                $sub_blocks->langues = serialize(Tools::getValue('languesup'));

                if (!$sub_blocks->update()) {
                    $errors[] = $this->l('An error occurred while update object.');
                }

                if (!count($errors)) {
                    if (!Tools::getValue('categories')) {
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                    } else {
                        SubBlocksClass::delAllCategories($sub_blocks->id);
                        SubBlocksClass::updateCategories(Tools::getValue('categories'), $sub_blocks->id);
                    }
                }
            }
        } elseif (Tools::isSubmit('deleteSubBlock') && Tools::getValue('idSB')) {
            $post_en_cours = true;
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSB'));
            if (!$sub_blocks->delete()) {
                $errors[] = $this->l('An error occurred while delete object.');
            } else {
                SubBlocksClass::delAllCategories((int)$sub_blocks->id);
                Tools::redirectAdmin($this->confpath.'&configSubBlocks');
            }
        } elseif (Tools::isSubmit('addProductLink') && Tools::getValue('idN') && Tools::getValue('idP')) {
            $post_en_cours = true;

            NewsClass::updateProductLinkNews((int)Tools::getValue('idN'), (int)Tools::getValue('idP'));

            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'#productLinkTable');
            }
        } elseif (Tools::isSubmit('removeProductLink') && Tools::getValue('idN') && Tools::getValue('idP')) {
            $post_en_cours = true;

            NewsClass::removeProductLinkNews((int)Tools::getValue('idN'), (int)Tools::getValue('idP'));

            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'#productLinkTable');
            }
        } elseif (Tools::isSubmit('addPopupLink') && Tools::getValue('idN') && Tools::getValue('idP')) {
            $post_en_cours = true;

            NewsClass::updatePopupLinkNews((int)Tools::getValue('idN'), (int)Tools::getValue('idP'));

            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'#popupLinkTable');
            }
        } elseif (Tools::isSubmit('removePopupLink') && Tools::getValue('idN') && Tools::getValue('idP')) {
            $post_en_cours = true;

            NewsClass::removePopupLinkNews((int)Tools::getValue('idN'), (int)Tools::getValue('idP'));

            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'#popupLinkTable');
            }
        } elseif (Tools::isSubmit('submitUpdateNews') && Tools::getValue('idN')) {
            $post_en_cours = true;

            if (!count(Tools::getValue('languesup'))) {
                $errors[] = $this->l('You must activate at least one language');
            } else {
                foreach ($languages as $language) {
                    if (!Tools::getValue('title_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The title must be specified');
                    }
                    if (!Tools::getValue('link_rewrite_'.$language['id_lang'])
                        && in_array($language['id_lang'], Tools::getValue('languesup'))
                    ) {
                        $errors[] = '
                            <img
                                src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg"
                            /> '.$this->l('The url rewrite must be specified');
                    }

                    $summary = Tools::getValue('paragraph_'.$language['id_lang']);
                    $content = Tools::getValue('content_'.$language['id_lang']);

                    if (!$summary && !$content && in_array($language['id_lang'], Tools::getValue('languesup'))) {
                        $errors[] = '<img src="'._PS_IMG_.'l/'.$language['id_lang'].'.jpg" /> '
                        .$this->l('The content or introduction must be specified');
                    }
                }
            }

            if (!Validate::isAbsoluteUrl(Tools::getValue('url_redirect'))) {
                $errors[] = sprintf(
                    $this->l('The field %1$s is not a correct.'),
                    '<strong>'.$this->l('Permanent redirect url').'</strong>'
                );
            }

            if (!count($errors)) {
                $news = new NewsClass((int)Tools::getValue('idN'));
                $news->id_shop = (int)$this->context->shop->id;
                $news->copyFromPost();
                $news->langues = serialize(Tools::getValue('languesup'));
                if (!$news->update()) {
                    $errors[] = $this->l('An error occurred while update object.');
                }

                NewsClass::removeAllProductsLinkNews((int)$news->id);
                if (Tools::getValue('productsLink')) {
                    foreach (Tools::getValue('productsLink') as $product_link) {
                        NewsClass::updateProductLinkNews((int)$news->id, (int)$product_link);
                    }
                }

                NewsClass::removeAllArticlesLinkNews((int)$news->id);
                if (Tools::getValue('articlesLink')) {
                    foreach (Tools::getValue('articlesLink') as $article_link) {
                        NewsClass::updateArticleLinkNews((int)$news->id, (int)$article_link);
                    }
                }

                NewsClass::removeAllLookbooksLinkNews((int)$news->id);
                if (Tools::getValue('lookbooksLink')) {
                    foreach (Tools::getValue('lookbooksLink') as $lookbook_link) {
                        NewsClass::updateLookbookLinkNews((int)$news->id, (int)$lookbook_link);
                    }
                }
 NewsClass::removeAllPopupLinkNews((int)$news->id);
                if (Tools::getValue('popupLink')) {

                        NewsClass::updatePopupLinkNews((int)$news->id, (int)Tools::getValue('popupLink'));
                }
                $news->razEtatLangue((int)$news->id);
                foreach ($languages as $language) {
                    if (in_array($language['id_lang'], Tools::getValue('languesup'))) {
                        $news->changeActiveLangue((int)$news->id, (int)$language['id_lang']);
                    }
                }

                if (!$this->demo_mode) {
                    if ($_FILES['homepage_logo']['name']) {
                        if (!$this->uploadImage(
                            $_FILES['homepage_logo'],
                            Tools::getValue('idN'),
                            $this->normal_image_size_width,
                            $this->normal_image_size_height
                        )) {
                            $errors[] = $this->l('An error occurred while upload image.');
                        } else {
                            foreach (self::scanListeThemes() as $value_theme) {
                                $config_theme = $this->getConfigXmlTheme($value_theme);
                                $this->imageResize(
                                    self::imgPath().$value_theme.'/up-img/'.Tools::getValue('idN').'.jpg',
                                    self::imgPath().$value_theme.'/up-img/admincrop_'.Tools::getValue('idN').'.jpg',
                                    (int)$this->admin_crop_image_size_width,
                                    (int)$this->admin_crop_image_size_height
                                );

                                $this->autocropImage(
                                    Tools::getValue('idN').'.jpg',
                                    self::imgPath().$value_theme.'/up-img/',
                                    self::imgPath().$value_theme.'/up-img/',
                                    (int)$this->admin_thumb_image_size_width,
                                    (int)$this->admin_thumb_image_size_height,
                                    'adminth_',
                                    null
                                );

                                $config_theme_array = PrestaBlog::objectToArray($config_theme);
                                foreach ($config_theme_array['images'] as $kta => $vta) {
                                    $this->autocropImage(
                                        Tools::getValue('idN').'.jpg',
                                        self::imgPath().$value_theme.'/up-img/',
                                        self::imgPath().$value_theme.'/up-img/',
                                        (int)$vta['width'],
                                        (int)$vta['height'],
                                        $kta.'_',
                                        null
                                    );
                                }
                            }
                        }
                    }
                }

                if (!count($errors)) {
                    if (!Tools::getValue('categories')) {
                        CorrespondancesCategoriesClass::delAllCategoriesNews((int)Tools::getValue('idN'));
                    } else {
                        CorrespondancesCategoriesClass::delAllCategoriesNews((int)Tools::getValue('idN'));
                        CorrespondancesCategoriesClass::updateCategoriesNews(
                            Tools::getValue('categories'),
                            (int)Tools::getValue('idN')
                        );
                    }
                }
            }
        } elseif (Tools::isSubmit('submitUpdateCat') && Tools::getValue('idC')) {
            $post_en_cours = true;

            $categories = new CategoriesClass((int)Tools::getValue('idC'));
            $categories->id_shop = (int)$this->context->shop->id;
            $categories->copyFromPost();

            if (!CategoriesClass::injectGroupsInCategorie(Tools::getValue('groupBox'), (int)$categories->id)) {
                $errors[] = $this->l('An error occurred while update object.').' - '.$this->l('Groups');
            }

            if (!$categories->update()) {
                $errors[] = $this->l('An error occurred while update object.');
            }

            if (!$this->demo_mode) {
                if ($_FILES['imageCategory']['name']) {
                    if (!$this->uploadImage(
                        $_FILES['imageCategory'],
                        $categories->id,
                        $this->normal_image_size_width,
                        $this->normal_image_size_height,
                        'c'
                    )) {
                        $errors[] = $this->l('An error occurred while upload image.');
                    } else {
                        foreach (self::scanListeThemes() as $value_theme) {
                            $this->imageResize(
                                self::imgPath().$value_theme.'/up-img/c/'.$categories->id.'.jpg',
                                self::imgPath().$value_theme.'/up-img/c/admincrop_'.$categories->id.'.jpg',
                                (int)$this->admin_crop_image_size_width,
                                (int)$this->admin_crop_image_size_height
                            );

                            $this->autocropImage(
                                $categories->id.'.jpg',
                                self::imgPath().$value_theme.'/up-img/c/',
                                self::imgPath().$value_theme.'/up-img/c/',
                                (int)$this->admin_thumb_image_size_width,
                                (int)$this->admin_thumb_image_size_height,
                                'adminth_',
                                null
                            );

                            $config_theme_array = PrestaBlog::objectToArray($config_theme);
                            foreach ($config_theme_array['categories'] as $key_theme_array => $value_theme_array) {
                                $this->autocropImage(
                                    $categories->id.'.jpg',
                                    self::imgPath().$value_theme.'/up-img/c/',
                                    self::imgPath().$value_theme.'/up-img/c/',
                                    (int)$value_theme_array['width'],
                                    (int)$value_theme_array['height'],
                                    $key_theme_array.'_',
                                    null
                                );
                            }
                        }
                    }
                }
            }
                                 CategoriesClass::removeAllPopupLinkCategorie((int)$categories->id);
                if (Tools::getValue('popupLinkCate')) {

                        CategoriesClass::updatePopupLinkCategorie((int)$categories->id, (int)Tools::getValue('popupLinkCate'));
                }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&catListe');
            }
        } elseif (Tools::isSubmit('submitAddLookbook')) {
            $post_en_cours = true;

            if (!Tools::getValue('title_'.$this->langue_default_store)) {
                $errors[] = '
                    <img
                        src="'._PS_IMG_.'l/'.$this->langue_default_store.'.jpg"
                    /> '.$this->l('The title must be specified');
            }

            $lookbook = new LookBookClass();
            $lookbook->id_shop = (int)$this->context->shop->id;
            $lookbook->copyFromPost();

            if (!count($errors)) {
                if (!$lookbook->add()) {
                    $errors[] = $this->l('An error occurred while add object.');
                } else {
                    if (!LookBookClass::injectGroupsInLookbook(Tools::getValue('groupBox'), (int)$lookbook->id)) {
                        $errors[] = $this->l('An error occurred while update object.').' - '.$this->l('Groups');
                    }

                    if (!$this->demo_mode) {
                        if ($_FILES['imageLookbook']['name']) {
                            if (!$this->uploadImage(
                                $_FILES['imageLookbook'],
                                $lookbook->id,
                                $this->normal_image_size_width,
                                $this->normal_image_size_height,
                                'lookbook'
                            )) {
                                $errors[] = $this->l('An error occurred while upload image.');
                            } else {
                                foreach (self::scanListeThemes() as $value_theme) {
                                    $this->imageResize(
                                        self::imgPath().$value_theme.'/up-img/lookbook/'.$lookbook->id.'.jpg',
                                        self::imgPath().$value_theme.'/up-img/lookbook/lbcrop_'.$lookbook->id.'.jpg',
                                        (int)$this->lb_crop_image_size_width,
                                        (int)$this->lb_crop_image_size_height
                                    );

                                    $this->autocropImage(
                                        $lookbook->id.'.jpg',
                                        self::imgPath().$value_theme.'/up-img/lookbook/',
                                        self::imgPath().$value_theme.'/up-img/lookbook/',
                                        (int)$this->admin_thumb_image_size_width,
                                        (int)$this->admin_thumb_image_size_height,
                                        'adminth_',
                                        null
                                    );
                                }
                            }
                        }
                    }
                }
            }

            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&lookbookListe');
            }
        } elseif (Tools::isSubmit('submitUpdateLookbook') && Tools::getValue('idLB')) {
            $post_en_cours = true;

            $lookbook = new LookBookClass((int)Tools::getValue('idLB'));
            $lookbook->id_shop = (int)$this->context->shop->id;
            $lookbook->copyFromPost();

            if (!LookBookClass::injectGroupsInLookbook(Tools::getValue('groupBox'), (int)$lookbook->id)) {
                $errors[] = $this->l('An error occurred while update object.').' - '.$this->l('Groups');
            }

            if (!$lookbook->update()) {
                $errors[] = $this->l('An error occurred while update object.');
            }

            if (!$this->demo_mode) {
                if ($_FILES['imageLookbook']['name']) {
                    if (!$this->uploadImage(
                        $_FILES['imageLookbook'],
                        $lookbook->id,
                        $this->normal_image_size_width,
                        $this->normal_image_size_height,
                        'lookbook'
                    )) {
                        $errors[] = $this->l('An error occurred while upload image.');
                    } else {
                        foreach (self::scanListeThemes() as $value_theme) {
                            $this->imageResize(
                                self::imgPath().$value_theme.'/up-img/lookbook/'.$lookbook->id.'.jpg',
                                self::imgPath().$value_theme.'/up-img/lookbook/lbcrop_'.$lookbook->id.'.jpg',
                                (int)$this->lb_crop_image_size_width,
                                (int)$this->lb_crop_image_size_height
                            );

                            $this->autocropImage(
                                $lookbook->id.'.jpg',
                                self::imgPath().$value_theme.'/up-img/lookbook/',
                                self::imgPath().$value_theme.'/up-img/lookbook/',
                                (int)$this->admin_thumb_image_size_width,
                                (int)$this->admin_thumb_image_size_height,
                                'adminth_',
                                null
                            );
                        }
                    }
                }
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editLookBook&idLB='.(int)Tools::getValue('idLB'));
            }
        } elseif (Tools::isSubmit('submitUpdateAntiSpam') && Tools::getValue('idAS')) {
            $post_en_cours = true;

            if (!count($errors)) {
                $antispam = new AntiSpamClass((int)Tools::getValue('idAS'));
                $antispam->id_shop = (int)$this->context->shop->id;
                $antispam->copyFromPost();

                if (!$antispam->update()) {
                    $errors[] = $this->l('An error occurred while update object.');
                } else {
                    $antispam->reloadChecksum();
                    Tools::redirectAdmin($this->confpath.'&configAntiSpam');
                }
            }
        } elseif (Tools::isSubmit('submitUpdateComment') && Tools::getValue('idC')) {
            $post_en_cours = true;
            if (!Tools::getValue('name')) {
                $errors[] = $this->l('The name must be specified');
            }

            if (!count($errors)) {
                $comment = new CommentNewsClass((int)Tools::getValue('idC'));
                $comment->copyFromPost();

                if (!$comment->update()) {
                    $errors[] = $this->l('An error occurred while update object.');
                }
            }
        } elseif (Tools::isSubmit('deleteComment') && Tools::getValue('idC')) {
            $post_en_cours = true;
            $comment_news = new CommentNewsClass((int)Tools::getValue('idC'));
            if (!$comment_news->delete()) {
                $errors[] = $this->l('An error occurred while delete object.');
            } else {
                if (Tools::getValue('idN')) {
                    Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'&showComments');
                } else {
                    Tools::redirectAdmin($this->confpath.'&commentListe');
                }
            }
        } elseif (Tools::isSubmit('deleteAllComment')) {
            foreach(Tools::getValue('AllidCToDelete') as $valeur)
                {
                 $post_en_cours = true;
                 $comment_news = new CommentNewsClass($valeur);

                if (!$comment_news->delete()) {
                    $errors[] = $this->l('An error occurred while delete object.');
                } else {
                    if (Tools::getValue('idN')) {
                       // Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'&showComments');
                    } else {
                     //   Tools::redirectAdmin($this->confpath.'&commentListe');
                    }
                }
            }
            Tools::redirectAdmin($this->confpath.'&commentListe');
        } elseif (Tools::isSubmit('enabledComment') && Tools::getValue('idC')) {
            $post_en_cours = true;
            $comment_news = new CommentNewsClass((int)Tools::getValue('idC'));
            if (!$comment_news->changeEtat('actif', 1)) {
                $errors[] = $this->l('An error occurred while update object.');
            } else {
                $liste_abo = CommentNewsClass::listeCommentMailAbo((int)$comment_news->news);

                if (Configuration::get($this->name.'_comment_subscription') && count($liste_abo)) {
                    $news = new NewsClass((int)$comment_news->news, $this->langue_default_store);

                    foreach ($liste_abo as $value_abo) {
                        $pre_url = Tools::getShopDomainSsl(true).__PS_BASE_URI__.$this->ctrblog;

                        Mail::Send(
                            $this->langue_default_store,
                            'feedback-subscribe',
                            $this->l('New comment').' / '.$news->title,
                            array(
                                '{news}' => (int)$news->id_prestablog_news,
                                '{title_news}' => $news->title,
                                '{url_news}' => $pre_url.'&id='.(int)$comment_news->news,
                                '{url_desabonnement}' => $pre_url.'&d='.(int)$comment_news->news
                            ),
                            $value_abo,
                            null,
                            (Configuration::get('PS_SHOP_EMAIL')),
                            (Configuration::get('PS_SHOP_NAME')),
                            null,
                            null,
                            dirname(__FILE__).'/mails/'
                        );
                    }
                }

                if (Tools::getValue('idN')) {
                    Tools::redirectAdmin($this->confpath.'&editNews&idN='.(int)Tools::getValue('idN').'&showComments');
                } else {
                    Tools::redirectAdmin($this->confpath.(Tools::isSubmit('commentListe') ? '&commentListe' : ''));
                }
            }
        } elseif (Tools::isSubmit('pendingComment') && Tools::getValue('idC')) {
            $post_en_cours = true;
            $comment_news = new CommentNewsClass((int)Tools::getValue('idC'));
            if (!$comment_news->changeEtat('actif', -1)) {
                $errors[] = $this->l('An error occurred while update object.');
            } else {
                Tools::redirectAdmin($this->confpath.(Tools::isSubmit('commentListe') ? '&commentListe' : ''));
            }
        } elseif (Tools::isSubmit('disabledComment') && Tools::getValue('idC')) {
            $post_en_cours = true;
            $comment_news = new CommentNewsClass((int)Tools::getValue('idC'));
            if (!$comment_news->changeEtat('actif', 0)) {
                $errors[] = $this->l('An error occurred while update object.');
            } else {
                if (Tools::getValue('idN')) {
                    Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN').'&showComments');
                } else {
                    Tools::redirectAdmin($this->confpath.(Tools::isSubmit('commentListe') ? '&commentListe' : ''));
                }
            }
        } elseif (Tools::isSubmit('deleteImageBlog') && Tools::getValue('idN')) {
            $post_en_cours = true;
            if (!file_exists(self::imgUpPath().'/'.Tools::getValue('idN').'.jpg')) {
                $errors[] = $this->l('This action cannot be taken.');
            } else {
                $this->deleteAllImagesThemes(Tools::getValue('idN'));
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editNews&idN='.Tools::getValue('idN'));
            }
        } elseif (Tools::isSubmit('deleteImageBlog') && Tools::getValue('idC')) {
            $post_en_cours = true;
            if (!file_exists(self::imgUpPath().'/c/'.Tools::getValue('idC').'.jpg')) {
                $errors[] = $this->l('This action cannot be taken.');
            } else {
                $this->deleteAllImagesThemesCat((int)Tools::getValue('idC'));
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editCat&idC='.Tools::getValue('idC'));
            }
        } elseif (Tools::isSubmit('addLookbookProduct') && Tools::getValue('idLB')) {
            $post_en_cours = true;
            if (!LookBookClass::addProductShape(
                (int)Tools::getValue('idLB'),
                (int)Tools::getValue('id_product'),
                Tools::getValue('lookbook_shape_ed'),
                Tools::getValue('lookbook_shape')
            )) {
                $errors[] = $this->l('An error occurred while add product to lookbook.');
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editLookBook&idLB='.Tools::getValue('idLB'));
            }
        } elseif (Tools::isSubmit('deleteProductLookbook') && Tools::getValue('idLB') && Tools::getValue('idLBP')) {
            $post_en_cours = true;
            if (!LookBookClass::delProductShape((int)Tools::getValue('idLBP'))) {
                $errors[] = $this->l('An error occurred while delete product to lookbook.');
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editLookBook&idLB='.Tools::getValue('idLB'));
            }
        } elseif (Tools::isSubmit('deleteImageLookbook') && Tools::getValue('idLB')) {
            $post_en_cours = true;
            if (!file_exists(self::imgUpPath().'/lookbook/'.Tools::getValue('idLB').'.jpg')) {
                $errors[] = $this->l('This action cannot be taken.');
            } else {
                if (!LookBookClass::delProductsShapeFromLookBook((int)Tools::getValue('idLB'))) {
                    $errors[] = $this->l('An error occurred while delete products to lookbook.');
                }
                $this->deleteAllImagesThemesLookbook((int)Tools::getValue('idLB'));
            }
            if (!count($errors)) {
                Tools::redirectAdmin($this->confpath.'&editLookBook&idLB='.Tools::getValue('idLB'));
            }
        } elseif (Tools::isSubmit('submitCrop') && Tools::getValue('idN')) {
            $post_en_cours = true;
            if (!file_exists(self::imgUpPath().'/admincrop_'.Tools::getValue('idN').'.jpg')) {
                $errors[] = $this->l('This action cannot be taken.');
            } else {
                $config_theme = $this->getConfigXmlTheme(self::getT());
                $config_theme_array = PrestaBlog::objectToArray($config_theme);

                list($w_image_base, $h_image_base) = getimagesize(
                    self::imgUpPath().'/admincrop_'.Tools::getValue('idN').'.jpg'
                );

                $this->cropImage(
                    Tools::getValue('idN').'.jpg',
                    self::imgUpPath().'/',
                    self::imgUpPath().'/',
                    (int)$w_image_base,
                    (int)$h_image_base,
                    (int)$config_theme_array['images'][Tools::getValue('pfx')]['width'],
                    (int)$config_theme_array['images'][Tools::getValue('pfx')]['height'],
                    (int)Tools::getValue('x'),
                    (int)Tools::getValue('y'),
                    (int)Tools::getValue('w'),
                    (int)Tools::getValue('h'),
                    Tools::getValue('pfx').'_',
                    null
                );

                if (Tools::getValue('pfx') == 'thumb') {
                    $this->autocropImage(
                        Tools::getValue('idN').'.jpg',
                        self::imgUpPath().'/',
                        self::imgUpPath().'/',
                        (int)$this->admin_thumb_image_size_width,
                        (int)$this->admin_thumb_image_size_height,
                        'adminth_',
                        null
                    );
                }
            }
            if (!count($errors)) {
                $url_redir = $this->confpath.'&editNews';
                $url_redir .= '&idN='.Tools::getValue('idN');
                $url_redir .= '&pfx='.Tools::getValue('pfx');
                Tools::redirectAdmin($url_redir);
            }
        } elseif (Tools::isSubmit('submitCrop') && Tools::getValue('idC')) {
            $post_en_cours = true;
            if (!file_exists(self::imgUpPath().'/c/admincrop_'.Tools::getValue('idC').'.jpg')) {
                $errors[] = $this->l('This action cannot be taken.');
            } else {
                $config_theme = $this->getConfigXmlTheme(self::getT());
                $config_theme_array = PrestaBlog::objectToArray($config_theme);

                $img_crop = self::imgUpPath().'/c/admincrop_'.Tools::getValue('idC').'.jpg';

                list($w_image_base, $h_image_base) = getimagesize($img_crop);

                $this->cropImage(
                    Tools::getValue('idC').'.jpg',
                    self::imgUpPath().'/c/',
                    self::imgUpPath().'/c/',
                    (int)$w_image_base,
                    (int)$h_image_base,
                    (int)$config_theme_array['categories'][Tools::getValue('pfx')]['width'],
                    (int)$config_theme_array['categories'][Tools::getValue('pfx')]['height'],
                    (int)Tools::getValue('x'),
                    (int)Tools::getValue('y'),
                    (int)Tools::getValue('w'),
                    (int)Tools::getValue('h'),
                    Tools::getValue('pfx').'_',
                    null
                );

                if (Tools::getValue('pfx') == 'thumb') {
                    $this->autocropImage(
                        Tools::getValue('idC').'.jpg',
                        self::imgUpPath().'/c/',
                        self::imgUpPath().'/c/',
                        (int)$this->admin_thumb_image_size_width,
                        (int)$this->admin_thumb_image_size_height,
                        'adminth_',
                        null
                    );
                }
            }
            if (!count($errors)) {
                $url_redir = $this->confpath.'&editCat';
                $url_redir .= '&idC='.Tools::getValue('idC');
                $url_redir .= '&pfx='.Tools::getValue('pfx');
                Tools::redirectAdmin($url_redir);
            }
        } elseif (Tools::isSubmit('submitAntiSpamConfig')) {
            if (is_numeric(Tools::getValue($this->name.'_antispam_actif'))) {
                Configuration::updateValue(
                    $this->name.'_antispam_actif',
                    (int)Tools::getValue($this->name.'_antispam_actif')
                );
            }
            Tools::redirectAdmin($this->confpath.'&configAntiSpam');
        } elseif (Tools::isSubmit('submitSitemapConfig')) {
            if (is_numeric(Tools::getValue($this->name.'_sitemap_actif'))) {
                Configuration::updateValue(
                    $this->name.'_sitemap_actif',
                    (int)Tools::getValue($this->name.'_sitemap_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_sitemap_articles'))) {
                Configuration::updateValue(
                    $this->name.'_sitemap_articles',
                    (int)Tools::getValue($this->name.'_sitemap_articles')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_sitemap_categories'))) {
                Configuration::updateValue(
                    $this->name.'_sitemap_categories',
                    (int)Tools::getValue($this->name.'_sitemap_categories')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_sitemap_limit'))) {
                Configuration::updateValue(
                    $this->name.'_sitemap_limit',
                    (int)Tools::getValue($this->name.'_sitemap_limit')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_sitemap_older'))) {
                Configuration::updateValue(
                    $this->name.'_sitemap_older',
                    (int)Tools::getValue($this->name.'_sitemap_older')
                );
            }
            Tools::redirectAdmin($this->confpath.'&sitemap');
        } elseif (Tools::isSubmit('submitSitemapGenerate')) {
            $this->createTheShopSitemap();
            Tools::redirectAdmin($this->confpath.'&sitemap');
        } elseif (Tools::isSubmit('deleteSitemap')) {
            $this->deleteSitemapFromShop((int)$this->context->shop->id);
            Tools::redirectAdmin($this->confpath.'&sitemap');
        } elseif (Tools::isSubmit('submitSubBlocksConfig')) {
            if (is_numeric(Tools::getValue($this->name.'_subblocks_actif'))) {
                Configuration::updateValue(
                    $this->name.'_subblocks_actif',
                    (int)Tools::getValue($this->name.'_subblocks_actif')
                );
            }
            Tools::redirectAdmin($this->confpath.'&configSubBlocks');
        } elseif (Tools::isSubmit('submitTheme')) {
            Configuration::updateValue($this->name.'_theme', Tools::getValue('theme'));
            Tools::redirectAdmin($this->confpath.'&configTheme');
        } elseif (Tools::isSubmit('selectLayout')) {
            Configuration::updateValue(
                $this->name.'_layout_blog',
                (int)Tools::getValue($this->name.'_layout_blog')
            );
            Tools::redirectAdmin($this->confpath.'&configTheme');

        } elseif (Tools::isSubmit('submitWizard')) {
            Tools::redirectAdmin($this->confpath.'&configWizard');
        } elseif (Tools::isSubmit('submitPageBlog')) {
            if (is_numeric(Tools::getValue($this->name.'_pageslide_actif'))) {
                Configuration::updateValue(
                    $this->name.'_pageslide_actif',
                    (int)Tools::getValue($this->name.'_pageslide_actif')
                );
            }

            $languages = Language::getLanguages(true);

            $title_cfg_lang = array();
            $desc_cfg_lang = array();
            $title_h1_cfg_lang = array();

            foreach ($languages as $language) {
                $title_cfg_lang[(int)$language['id_lang']] = Tools::getValue('meta_title_'.$language['id_lang']);
                $desc_cfg_lang[(int)$language['id_lang']] = Tools::getValue('meta_description_'.$language['id_lang']);
                $title_h1_cfg_lang[(int)$language['id_lang']] = Tools::getValue('title_h1_'.$language['id_lang']);
            }

            Configuration::updateValue($this->name.'_titlepageblog', $title_cfg_lang);
            Configuration::updateValue($this->name.'_descpageblog', $desc_cfg_lang);
            Configuration::updateValue($this->name.'_h1pageblog', $title_h1_cfg_lang);

            Tools::redirectAdmin($this->confpath.'&pageBlog');

        } elseif (Tools::isSubmit('submitPopupGeneral')) {

                if (is_numeric(Tools::getValue($this->name.'_popup_general'))) {
                Configuration::updateValue(
                    $this->name.'_popup_general',
                    (int)Tools::getValue($this->name.'_popup_general')
                );
            }
            Tools::redirectAdmin($this->confpath.'&configModule');

            } elseif (Tools::isSubmit('submitPopupHome')) {
                PopupClass::updateValuePopuphome(
                    Tools::getValue('popupLink')
                );
                PopupClass::DeleteAllValue(
					Tools::getValue('popupLink')
                );
                if (is_numeric(Tools::getValue($this->name.'_popuphome_actif'))) {
                Configuration::updateValue(
                    $this->name.'_popuphome_actif',
                    (int)Tools::getValue($this->name.'_popuphome_actif')
                );
            }
                            Tools::redirectAdmin($this->confpath.'&pageBlog');

            } elseif (Tools::isSubmit('submitConfSlideNews')) {
            if (is_numeric(Tools::getValue($this->name.'_homenews_limit'))) {
                Configuration::updateValue(
                    $this->name.'_homenews_limit',
                    (int)Tools::getValue($this->name.'_homenews_limit')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_homenews_actif'))) {
                Configuration::updateValue(
                    $this->name.'_homenews_actif',
                    (int)Tools::getValue($this->name.'_homenews_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_pageslide_actif'))) {
                Configuration::updateValue(
                    $this->name.'_pageslide_actif',
                    (int)Tools::getValue($this->name.'_pageslide_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_slide_title_length'))) {
                Configuration::updateValue(
                    $this->name.'_slide_title_length',
                    (int)Tools::getValue($this->name.'_slide_title_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_slide_intro_length'))) {
                Configuration::updateValue(
                    $this->name.'_slide_intro_length',
                    (int)Tools::getValue($this->name.'_slide_intro_length')
                );
            }

            $xml = Tools::file_get_contents(_PS_MODULE_DIR_.'prestablog/views/config/'.self::getT().'.xml');
            $config_theme = $this->getConfigXmlTheme(self::getT());
            $config_theme_array = PrestaBlog::objectToArray($config_theme);

            $remplacement = '
                <thumb> <!--Image prevue pour les miniatures dans les listes -->
                    <width>'.(int)$config_theme_array['images']['thumb']['width'].'</width>
                    <height>'.(int)$config_theme_array['images']['thumb']['height'].'</height>
                </thumb>
                <slide> <!--Image prevue pour les slides -->
                    <width>'.Tools::getValue('slide_picture_width').'</width>
                    <height>'.Tools::getValue('slide_picture_height').'</height>
                </slide>';

            $xml = preg_replace('#<images[^>]*>.*?</images>#si', '<images>'.$remplacement.'</images>', $xml);

            if (is_writable(_PS_MODULE_DIR_.$this->name.'/views/config/')) {
                file_put_contents(_PS_MODULE_DIR_.'prestablog/views/config/'.self::getT().'.xml', utf8_encode($xml));
                Tools::redirectAdmin($this->confpath.'&configTheme');
            }
        } elseif (Tools::isSubmit('submitConfListeArticles')) {
            if (is_numeric(Tools::getValue($this->name.'_news_title_length'))) {
                Configuration::updateValue(
                    $this->name.'_news_title_length',
                    (int)Tools::getValue($this->name.'_news_title_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_news_intro_length'))) {
                Configuration::updateValue(
                    $this->name.'_news_intro_length',
                    (int)Tools::getValue($this->name.'_news_intro_length')
                );
            }

            $xml = Tools::file_get_contents(_PS_MODULE_DIR_.'prestablog/views/config/'.self::getT().'.xml');
            $config_theme = $this->getConfigXmlTheme(self::getT());
            $config_theme_array = PrestaBlog::objectToArray($config_theme);

            $remplacement = '
                <thumb> <!--Image prevue pour les miniatures dans les listes -->
                    <width>'.Tools::getValue('thumb_picture_width').'</width>
                    <height>'.Tools::getValue('thumb_picture_height').'</height>
                </thumb>
                <slide> <!--Image prevue pour les slides -->
                    <width>'.(int)$config_theme_array['images']['slide']['width'].'</width>
                    <height>'.(int)$config_theme_array['images']['slide']['height'].'</height>
                </slide>';

            $xml = preg_replace('#<images[^>]*>.*?</images>#si', '<images>'.$remplacement.'</images>', $xml);

            if (is_writable(_PS_MODULE_DIR_.$this->name.'/views/config/')) {
                file_put_contents(_PS_MODULE_DIR_.$this->name.'/views/config/'.self::getT().'.xml', utf8_encode($xml));
                Tools::redirectAdmin($this->confpath.'&configCategories');
            }
        } elseif (Tools::isSubmit('submitConfBlocSearch')) {
            if (is_numeric(Tools::getValue($this->name.'_blocsearch_actif'))) {
                Configuration::updateValue(
                    $this->name.'_blocsearch_actif',
                    (int)Tools::getValue($this->name.'_blocsearch_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_search_filtrecat'))) {
                Configuration::updateValue(
                    $this->name.'_search_filtrecat',
                    (int)Tools::getValue($this->name.'_search_filtrecat')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configBlocs');
        } elseif (Tools::isSubmit('submitConfBlocRss')) {
            if (is_numeric(Tools::getValue($this->name.'_allnews_rss'))) {
                Configuration::updateValue(
                    $this->name.'_allnews_rss',
                    (int)Tools::getValue($this->name.'_allnews_rss')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_rss_title_length'))) {
                Configuration::updateValue(
                    $this->name.'_rss_title_length',
                    (int)Tools::getValue($this->name.'_rss_title_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_rss_intro_length'))) {
                Configuration::updateValue(
                    $this->name.'_rss_intro_length',
                    (int)Tools::getValue($this->name.'_rss_intro_length')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configBlocs');
        } elseif (Tools::isSubmit('submitConfBlocLastNews')) {
            if (is_numeric(Tools::getValue($this->name.'_lastnews_limit'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_limit',
                    (int)Tools::getValue($this->name.'_lastnews_limit')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_lastnews_limit'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_actif',
                    (int)Tools::getValue($this->name.'_lastnews_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_lastnews_showintro'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_showintro',
                    (int)Tools::getValue($this->name.'_lastnews_showintro')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_lastnews_showthumb'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_showthumb',
                    (int)Tools::getValue($this->name.'_lastnews_showthumb')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_lastnews_showall'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_showall',
                    (int)Tools::getValue($this->name.'_lastnews_showall')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_lastnews_title_length'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_title_length',
                    (int)Tools::getValue($this->name.'_lastnews_title_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_lastnews_intro_length'))) {
                Configuration::updateValue(
                    $this->name.'_lastnews_intro_length',
                    (int)Tools::getValue($this->name.'_lastnews_intro_length')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configBlocs');
        } elseif (Tools::isSubmit('submitConfFooterLastNews')) {
            if (is_numeric(Tools::getValue($this->name.'_footlastnews_limit'))) {
                Configuration::updateValue(
                    $this->name.'_footlastnews_limit',
                    (int)Tools::getValue($this->name.'_footlastnews_limit')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_footlastnews_actif'))) {
                Configuration::updateValue(
                    $this->name.'_footlastnews_actif',
                    (int)Tools::getValue($this->name.'_footlastnews_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_footlastnews_showall'))) {
                Configuration::updateValue(
                    $this->name.'_footlastnews_showall',
                    (int)Tools::getValue($this->name.'_footlastnews_showall')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_footlastnews_intro'))) {
                Configuration::updateValue(
                    $this->name.'_footlastnews_intro',
                    (int)Tools::getValue($this->name.'_footlastnews_intro')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_footer_title_length'))) {
                Configuration::updateValue(
                    $this->name.'_footer_title_length',
                    (int)Tools::getValue($this->name.'_footer_title_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_footer_intro_length'))) {
                Configuration::updateValue(
                    $this->name.'_footer_intro_length',
                    (int)Tools::getValue($this->name.'_footer_intro_length')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configBlocs');
        } elseif (Tools::isSubmit('submitConfBlocDateNews')) {
            if (is_numeric(Tools::getValue($this->name.'_datenews_actif'))) {
                Configuration::updateValue(
                    $this->name.'_datenews_actif',
                    (int)Tools::getValue($this->name.'_datenews_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_datenews_showall'))) {
                Configuration::updateValue(
                    $this->name.'_datenews_showall',
                    (int)Tools::getValue($this->name.'_datenews_showall')
                );
            }
            Configuration::updateValue($this->name.'_datenews_order', Tools::getValue($this->name.'_datenews_order'));

            Tools::redirectAdmin($this->confpath.'&configBlocs');
        } elseif (Tools::isSubmit('submitConfBlocCatNews')) {
            if (is_numeric(Tools::getValue($this->name.'_catnews_actif'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_actif',
                    (int)Tools::getValue($this->name.'_catnews_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_showall'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_showall',
                    (int)Tools::getValue($this->name.'_catnews_showall')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_empty'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_empty',
                    (int)Tools::getValue($this->name.'_catnews_empty')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_tree'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_tree',
                    (int)Tools::getValue($this->name.'_catnews_tree')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_shownbnews'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_shownbnews',
                    (int)Tools::getValue($this->name.'_catnews_shownbnews')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_showthumb'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_showthumb',
                    (int)Tools::getValue($this->name.'_catnews_showthumb')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_showintro'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_showintro',
                    (int)Tools::getValue($this->name.'_catnews_showintro')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_cat_title_length'))) {
                Configuration::updateValue(
                    $this->name.'_cat_title_length',
                    (int)Tools::getValue($this->name.'_cat_title_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_cat_intro_length'))) {
                Configuration::updateValue(
                    $this->name.'_cat_intro_length',
                    (int)Tools::getValue($this->name.'_cat_intro_length')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_catnews_rss'))) {
                Configuration::updateValue(
                    $this->name.'_catnews_rss',
                    (int)Tools::getValue($this->name.'_catnews_rss')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configCategories');
        } elseif (Tools::isSubmit('submitConfRewrite')) {
            if (is_numeric(Tools::getValue($this->name.'_rewrite_actif'))) {
                Configuration::updateValue(
                    $this->name.'_rewrite_actif',
                    (int)Tools::getValue($this->name.'_rewrite_actif')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configModule');
        } elseif (Tools::isSubmit('submitConfGobalFront')) {
            if (is_numeric(Tools::getValue($this->name.'_nb_liste_page'))) {
                Configuration::updateValue(
                    $this->name.'_nb_liste_page',
                    (int)Tools::getValue($this->name.'_nb_liste_page')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_rating_actif'))) {
                Configuration::updateValue(
                    $this->name.'_rating_actif',
                    (int)Tools::getValue($this->name.'_rating_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_article_page'))) {
                Configuration::updateValue(
                    $this->name.'_article_page',
                    (int)Tools::getValue($this->name.'_article_page')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_producttab_actif'))) {
                Configuration::updateValue(
                    $this->name.'_producttab_actif',
                    (int)Tools::getValue($this->name.'_producttab_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_thumb_linkprod_width'))) {
                Configuration::updateValue(
                    $this->name.'_thumb_linkprod_width',
                    (int)Tools::getValue($this->name.'_thumb_linkprod_width')
                );
            }

            if (is_numeric(Tools::getValue($this->name.'_socials_actif'))) {
                Configuration::updateValue(
                    $this->name.'_socials_actif',
                    (int)Tools::getValue($this->name.'_socials_actif')
                );
            }

            if (is_numeric(Tools::getValue($this->name.'_s_facebook'))) {
                Configuration::updateValue(
                    $this->name.'_s_facebook',
                    (int)Tools::getValue($this->name.'_s_facebook')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_twitter'))) {
                Configuration::updateValue(
                    $this->name.'_s_twitter',
                    (int)Tools::getValue($this->name.'_s_twitter')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_linkedin'))) {
                Configuration::updateValue(
                    $this->name.'_s_linkedin',
                    (int)Tools::getValue($this->name.'_s_linkedin')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_email'))) {
                Configuration::updateValue(
                    $this->name.'_s_email',
                    (int)Tools::getValue($this->name.'_s_email')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_pinterest'))) {
                Configuration::updateValue(
                    $this->name.'_s_pinterest',
                    (int)Tools::getValue($this->name.'_s_pinterest')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_pocket'))) {
                Configuration::updateValue(
                    $this->name.'_s_pocket',
                    (int)Tools::getValue($this->name.'_s_pocket')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_tumblr'))) {
                Configuration::updateValue(
                    $this->name.'_s_tumblr',
                    (int)Tools::getValue($this->name.'_s_tumblr')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_reddit'))) {
                Configuration::updateValue(
                    $this->name.'_s_reddit',
                    (int)Tools::getValue($this->name.'_s_reddit')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_s_hackernews'))) {
                Configuration::updateValue(
                    $this->name.'_s_hackernews',
                    (int)Tools::getValue($this->name.'_s_hackernews')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_uniqnews_rss'))) {
                Configuration::updateValue(
                    $this->name.'_uniqnews_rss',
                    (int)Tools::getValue($this->name.'_uniqnews_rss')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_view_news_img'))) {
                Configuration::updateValue(
                    $this->name.'_view_news_img',
                    (int)Tools::getValue($this->name.'_view_news_img')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configModule');
        } elseif (Tools::isSubmit('submitConfCategory')) {
            if (is_numeric(Tools::getValue($this->name.'_view_cat_desc'))) {
                Configuration::updateValue(
                    $this->name.'_view_cat_desc',
                    (int)Tools::getValue($this->name.'_view_cat_desc')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_view_cat_thumb'))) {
                Configuration::updateValue(
                    $this->name.'_view_cat_thumb',
                    (int)Tools::getValue($this->name.'_view_cat_thumb')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_view_cat_img'))) {
                Configuration::updateValue(
                    $this->name.'_view_cat_img',
                    (int)Tools::getValue($this->name.'_view_cat_img')
                );
            }

            $xml = Tools::file_get_contents(_PS_MODULE_DIR_.'prestablog/views/config/'.self::getT().'.xml');

            $remplacement = '
                <thumb> <!--Image prevue pour les miniatures dans les listes -->
                    <width>'.Tools::getValue('thumb_cat_width').'</width>
                    <height>'.Tools::getValue('thumb_cat_height').'</height>
                </thumb>
                <full> <!--Image prevue pour la description de la categorie en liste 1ere page -->
                    <width>'.Tools::getValue('full_cat_width').'</width>
                    <height>'.Tools::getValue('full_cat_height').'</height>
                </full>';

            $xml = preg_replace(
                '#<categories[^>]*>.*?</categories>#si',
                '<categories>'.$remplacement.'</categories>',
                $xml
            );

            if (is_writable(_PS_MODULE_DIR_.$this->name.'/views/config/')) {
                file_put_contents(_PS_MODULE_DIR_.'prestablog/views/config/'.self::getT().'.xml', utf8_encode($xml));
                Tools::redirectAdmin($this->confpath.'&configCategories');
            }
        } elseif (Tools::isSubmit('submitConfGobalAdmin')) {
            if (is_numeric(Tools::getValue($this->name.'_nb_car_min_linkprod'))) {
                Configuration::updateValue(
                    $this->name.'_nb_car_min_linkprod',
                    (int)Tools::getValue($this->name.'_nb_car_min_linkprod')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_list_linkprod'))) {
                Configuration::updateValue(
                    $this->name.'_nb_list_linkprod',
                    (int)Tools::getValue($this->name.'_nb_list_linkprod')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_car_min_linknews'))) {
                Configuration::updateValue(
                    $this->name.'_nb_car_min_linknews',
                    (int)Tools::getValue($this->name.'_nb_car_min_linknews')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_list_linknews'))) {
                Configuration::updateValue(
                    $this->name.'_nb_list_linknews',
                    (int)Tools::getValue($this->name.'_nb_list_linknews')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_car_min_linklb'))) {
                Configuration::updateValue(
                    $this->name.'_nb_car_min_linklb',
                    (int)Tools::getValue($this->name.'_nb_car_min_linklb')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_list_linklb'))) {
                Configuration::updateValue(
                    $this->name.'_nb_list_linklb',
                    (int)Tools::getValue($this->name.'_nb_list_linklb')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_news_pl'))) {
                Configuration::updateValue(
                    $this->name.'_nb_news_pl',
                    (int)Tools::getValue($this->name.'_nb_news_pl')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_nb_comments_pl'))) {
                Configuration::updateValue(
                    $this->name.'_nb_comments_pl',
                    (int)Tools::getValue($this->name.'_nb_comments_pl')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_comment_div_visible'))) {
                Configuration::updateValue(
                    $this->name.'_comment_div_visible',
                    (int)Tools::getValue($this->name.'_comment_div_visible')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configModule');
        } elseif (Tools::isSubmit('submitConfMenuCatBlog')) {
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_blog_index'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_blog_index',
                    (int)Tools::getValue($this->name.'_menu_cat_blog_index')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_blog_list'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_blog_list',
                    (int)Tools::getValue($this->name.'_menu_cat_blog_list')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_blog_article'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_blog_article',
                    (int)Tools::getValue($this->name.'_menu_cat_blog_article')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_blog_empty'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_blog_empty',
                    (int)Tools::getValue($this->name.'_menu_cat_blog_empty')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_home_link'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_home_link',
                    (int)Tools::getValue($this->name.'_menu_cat_home_link')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_home_img'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_home_img',
                    (int)Tools::getValue($this->name.'_menu_cat_home_img')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_menu_cat_blog_nbnews'))) {
                Configuration::updateValue(
                    $this->name.'_menu_cat_blog_nbnews',
                    (int)Tools::getValue($this->name.'_menu_cat_blog_nbnews')
                );
            }

            Tools::redirectAdmin($this->confpath.'&configCategories');
        } elseif (Tools::isSubmit('submitColorBlog')) {
        	$menu_color = Tools::getValue('menu_color');
               $menu_hover = Tools::getValue('menu_hover');
$read_color = Tools::getValue('read_color');
$hover_color = Tools::getValue('hover_color');
$title_color = Tools::getValue('title_color');
$text_color = Tools::getValue('text_color');
$menu_link = Tools::getValue('menu_link');
$link_read = Tools::getValue('link_read');
$article_title = Tools::getValue('article_title');
$article_text = Tools::getValue('article_text');
$block_categories = Tools::getValue('block_categories');
$block_categories_link = Tools::getValue('block_categories_link');
$block_title = Tools::getValue('block_title');
$block_btn = Tools::getValue('block_btn');
$block_btn_hover = Tools::getValue('block_btn_hover');
$categorie_block_background = Tools::getValue('categorie_block_background');
$categorie_block_background_hover = Tools::getValue('categorie_block_background_hover');
$article_background = Tools::getValue('article_background');
$article_comment_background = Tools::getValue('article_comment_background');
$liste_colors = NewsClass::getColorHome((int)$this->context->shop->id);

if (!isset($liste_colors[0]) || $liste_colors[0] == "" || $liste_colors[0] == null) {
 if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
 	INSERT INTO `'.bqSQL(_DB_PREFIX_).'prestablog_color`
 	(`menu_color`,`read_color`,`hover_color`,`title_color`,`text_color`,`menu_hover`,`menu_link`,`link_read`,`article_title`,`article_text`,`block_categories`,`block_categories_link`,`block_title`,`block_btn`,`categorie_block_background`,`article_background`,`article_comment_background`,`categorie_block_background_hover`,`block_btn_hover`,`id_shop`)
 	VALUES
 	("'.$menu_color.'","'.$read_color.'","'.$hover_color.'","'.$title_color.'","'.$text_color.'","'.$menu_hover.'","'.$menu_link.'","'.$link_read.'","'.$article_title.'","'.$article_text.'","'.$block_categories.'","'.$block_categories_link.'","'.$block_title.'","'.$block_btn.'","'.$categorie_block_background.'","'.$article_background.'","'.$article_comment_background.'","'.$categorie_block_background_hover.'","'.$block_btn_hover.'","'.(int)$this->context->shop->id.'")')) {
                    return false;
                }
} else {
 if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                    UPDATE `'.bqSQL(_DB_PREFIX_).'prestablog_color`
                    SET menu_color="'.$menu_color.'", read_color="'.$read_color.'", hover_color="'.$hover_color.'", title_color="'.$title_color.'", text_color="'.$text_color.'", menu_hover="'.$menu_hover.'", menu_link="'.$menu_link.'", link_read="'.$link_read.'", article_title="'.$article_title.'", article_text="'.$article_text.'", block_categories="'.$block_categories.'", block_categories_link="'.$block_categories_link.'", block_title="'.$block_title.'", block_btn="'.$block_btn.'", categorie_block_background="'.$categorie_block_background.'", article_background="'.$article_background.'", article_comment_background="'.$article_comment_background.'", categorie_block_background_hover="'.$categorie_block_background_hover.'", block_btn_hover="'.$block_btn_hover.'"
                    WHERE id_shop='.(int)$this->context->shop->id)) {
                    return false;
                }
}

$liste_colors = NewsClass::getColorHome((int)$this->context->shop->id);
$menu_color_db = $liste_colors[0]["menu_color"];
$menu_hover_db = $liste_colors[0]["menu_hover"];
$read_color_db = $liste_colors[0]["read_color"];
$hover_color_db = $liste_colors[0]["hover_color"];
$title_color_db = $liste_colors[0]["title_color"];
$text_color_db = $liste_colors[0]["text_color"];
$menu_link_db = $liste_colors[0]["menu_link"];
$link_read_db = $liste_colors[0]["link_read"];
$article_title_db = $liste_colors[0]["article_title"];
$article_text_db = $liste_colors[0]["article_text"];
$block_categories_db = $liste_colors[0]["block_categories"];
$block_categories_link_db = $liste_colors[0]["block_categories_link"];
$block_title_db = $liste_colors[0]["block_title"];
$block_btn_db = $liste_colors[0]["block_btn"];
$block_btn_hover_db = $liste_colors[0]["block_btn_hover"];
$categorie_block_background_db = $liste_colors[0]["categorie_block_background"];
$categorie_block_background_hover_db = $liste_colors[0]["categorie_block_background_hover"];
$article_background_db = $liste_colors[0]["article_background"];
$article_comment_background_db = $liste_colors[0]["article_comment_background"];
$presta = '/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */
';
$color_menu = '#prestablog_menu_cat nav ul, img.logo_home {
    list-style: none;
    background: '.$menu_color.'!important;
}';
$hover_menu = '#prestablog_menu_cat nav ul li:hover {
    background: '.$menu_hover.'!important;
}';
$color_read = '#blog_list_1-7 .prestablog_more {
    display: block;
    background-color: '.$read_color.'!important;
}';
//.prestablog_more a: couleur texte
$color_hover = '#blog_list_1-7 a.blog_link:hover, #blog_list_1-7 .comments:hover, #blog_list_1-7 a.blog_link:hover::before, #blog_list_1-7 .comments:hover::before {
    background-color: '.$hover_color.'!important;
    color: #fff;
}';

//ajouter prestablogfronth2 blog_list h3 ...
$color_title = '#blog_list_1-7 .block_bas h3 a {
     color:'.$title_color.'!important;
}';
/**/
$color_text = '#blog_list_1-7 p {
    margin: 12px 0px;
    color: '.$text_color.'!important;
}';
$link_menu = '#prestablog_menu_cat nav ul li a {
	display: block;
	padding: 9px 20px;
	color: '.$menu_link.'!important;
	text-decoration: none;
	text-shadow: 1px 1px rgba(0, 0, 0, 0.2);
	font: 600 18px/22px "Open Sans",sans-serif;
}';
$read_link = '#blog_list_1-7 a.blog_link, #blog_list_1-7 a.comments {
	color: '.$link_read.'!important;
}';
$title_article = '#prestablogfont h1, #prestablogfont h2, #prestablogfont h3, #prestablogfont h4, #prestablogfont h5, #prestablogfont h6, #prestablog_article{
    color:'.$article_title.'!important;
}';
$text_article = '#prestablogfont p, #prestablogfont ul, #prestablogfont li {
	color: '.$article_text.'!important;
}';
$categories_block = '.block-categories {
	background: '.$block_categories.'!important;
}';
$categories_block_link = '.block-categories a {
	color: '.$block_categories_link.'!important;
}';
$title_block = '.title_block {
	color: '.$block_title.'!important;
}';
$btn_block = '#prestablog_lastliste a.btn-primary, #prestablog_catliste a.btn-primary, #prestablog_dateliste a.btn-primary, #prestablog_block_rss a {
	background-color: '.$block_btn.'!important;
}';
$btn_block_hover = '#prestablog_lastliste a.btn-primary:hover, #prestablog_catliste a.btn-primary:hover, #prestablog_dateliste a.btn-primary:hover, #prestablog_block_rss a:hover {
    background-color: '.$block_btn_hover.'!important;
}';
$comment_article = '#prestablog-fb-comments, #prestablog-comments  {
    background-color: '.$article_comment_background.'!important;
}';
$background_article = '#prestablogfront  {
    background-color: '.$article_background.'!important;
}';
$background_categorie_block = '#blog_list_1-7 .block_cont  {
    background-color: '.$categorie_block_background.'!important;
}';
$background_categorie_block_hover = '#blog_list_1-7 li:hover .block_cont  {
    background-color: '.$categorie_block_background_hover.'!important;
}';
$fp = fopen(_PS_MODULE_DIR_.'prestablog/views/css/blog'.(int)$this->context->shop->id.'.css', 'w');
fwrite($fp, $presta);
if ($menu_color_db != '0') {
    fwrite($fp, $color_menu);
}
if ($menu_hover_db != '0') {
    fwrite($fp, $hover_menu);
}
if ($read_color_db != '0') {
    fwrite($fp, $color_read);
}
if ($hover_color_db != '0') {
    fwrite($fp, $color_hover);
}
if ($title_color_db != '0') {
    fwrite($fp, $color_title);
}
if ($text_color_db != '0') {
    fwrite($fp, $color_text);
}
if ($menu_link_db != '0') {
    fwrite($fp, $link_menu);
}
if ($link_read_db != '0') {
    fwrite($fp, $read_link);
}
if ($article_title_db != '0') {
    fwrite($fp, $title_article);
}
if ($article_text_db != '0') {
    fwrite($fp, $text_article);
}
if ($block_categories_db != '0') {
    fwrite($fp, $categories_block);
}
if ($block_categories_link_db != '0') {
    fwrite($fp, $categories_block_link);
}
if ($block_title_db != '0') {
    fwrite($fp, $title_block);
}
if ($block_btn_db != '0') {
    fwrite($fp, $btn_block);
}
if ($article_comment_background_db != '0') {
    fwrite($fp, $comment_article);
}
if ($article_background_db != '0') {
    fwrite($fp, $background_article);
}
if ($categorie_block_background_db != '0') {
    fwrite($fp, $background_categorie_block);
}
if ($categorie_block_background_hover_db != '0') {
    fwrite($fp, $background_categorie_block_hover);
}
if ($block_btn_hover_db != '0') {
    fwrite($fp, $btn_block_hover);
}
fclose($fp);
                Tools::redirectAdmin($this->confpath.'&colorBlog');

        } elseif (Tools::isSubmit('submitConfComment')) {
            if (is_numeric(Tools::getValue($this->name.'_comment_actif'))) {
                Configuration::updateValue(
                    $this->name.'_comment_actif',
                    (int)Tools::getValue($this->name.'_comment_actif')
                );
                if ((int)Tools::getValue($this->name.'_comment_actif') == 1) {
                    Configuration::updateValue($this->name.'_commentfb_actif', 0);
                }
            }
            if (is_numeric(Tools::getValue($this->name.'_captcha_actif'))) {
                Configuration::updateValue(
                    $this->name.'_captcha_actif',
                    (int)Tools::getValue($this->name.'_captcha_actif')
                );
            }
            if (Tools::getValue($this->name.'_captcha_public_key')) {
                Configuration::updateValue(
                    $this->name.'_captcha_public_key',
                    Tools::getValue($this->name.'_captcha_public_key')
                );
            }
            if (Tools::getValue($this->name.'_captcha_private_key')) {
                Configuration::updateValue(
                    $this->name.'_captcha_private_key',
                    Tools::getValue($this->name.'_captcha_private_key')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_comment_only_login'))) {
                Configuration::updateValue(
                    $this->name.'_comment_only_login',
                    (int)Tools::getValue($this->name.'_comment_only_login')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_comment_auto_actif'))) {
                Configuration::updateValue(
                    $this->name.'_comment_auto_actif',
                    (int)Tools::getValue($this->name.'_comment_auto_actif')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_comment_nofollow'))) {
                Configuration::updateValue(
                    $this->name.'_comment_nofollow',
                    (int)Tools::getValue($this->name.'_comment_nofollow')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_comment_alert_admin'))) {
                Configuration::updateValue(
                    $this->name.'_comment_alert_admin',
                    (int)Tools::getValue($this->name.'_comment_alert_admin')
                );
            }
            if (is_numeric(Tools::getValue($this->name.'_comment_subscription'))) {
                Configuration::updateValue(
                    $this->name.'_comment_subscription',
                    (int)Tools::getValue($this->name.'_comment_subscription')
                );
            }

            Configuration::updateValue(
                $this->name.'_comment_admin_mail',
                Tools::getValue($this->name.'_comment_admin_mail')
            );

            Tools::redirectAdmin($this->confpath.'&configComments');
        } elseif (Tools::isSubmit('submitConfCss')) {
        	$css = Tools::getValue('content_css');
        	$fp = fopen(_PS_MODULE_DIR_.'prestablog/views/css/custom'.(int)$this->context->shop->id.'.css', 'w');
        	$presta = '/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */
';
fwrite($fp, $presta);
fclose($fp);
                file_put_contents(_PS_MODULE_DIR_.'prestablog/views/css/custom'.(int)$this->context->shop->id.'.css', utf8_encode($css));
                Tools::redirectAdmin($this->confpath.'&configCss');
        } elseif (Tools::isSubmit('submitConfCommentFB')) {
            if (is_numeric(Tools::getValue($this->name.'_commentfb_actif'))) {
                Configuration::updateValue(
                    $this->name.'_commentfb_actif',
                    (int)Tools::getValue($this->name.'_commentfb_actif')
                );
                if ((int)Tools::getValue($this->name.'_commentfb_actif') == 1) {
                    Configuration::updateValue($this->name.'_comment_actif', 0);
                }
            }
            if (is_numeric(Tools::getValue($this->name.'_commentfb_nombre'))) {
                Configuration::updateValue(
                    $this->name.'_commentfb_nombre',
                    (int)Tools::getValue($this->name.'_commentfb_nombre')
                );
            }

            if (is_numeric(Tools::getValue($this->name.'_commentfb_apiId'))) {
                Configuration::updateValue(
                    $this->name.'_commentfb_apiId',
                    Tools::getValue($this->name.'_commentfb_apiId')
                );
            }

            if (is_numeric(Tools::getValue($this->name.'_commentfb_modosId'))) {
                $list_fb_moderators = unserialize(Configuration::get($this->name.'_commentfb_modosId'));
                $list_fb_moderators[] = Tools::getValue($this->name.'_commentfb_modosId');
                $list_fb_moderators = array_unique($list_fb_moderators);
                Configuration::updateValue($this->name.'_commentfb_modosId', serialize($list_fb_moderators));
            }

            Tools::redirectAdmin($this->confpath.'&configComments');
        } elseif (Tools::isSubmit('deleteFacebookModerator')) {
            if (is_numeric(Tools::getValue('fb_moderator_id'))) {
                $list_fb_moderators = unserialize(Configuration::get($this->name.'_commentfb_modosId'));
                $list_fb_moderators = array_diff($list_fb_moderators, array(Tools::getValue('fb_moderator_id')));
                $list_fb_moderators = array_unique($list_fb_moderators);
                Configuration::updateValue($this->name.'_commentfb_modosId', serialize($list_fb_moderators));
            }

            Tools::redirectAdmin($this->confpath.'&configComments');
        } elseif (Tools::isSubmit('submitParseXml'))
        {
            include_once($this->module_path.'/class/Xml.php');
            $xml_string = trim(Tools::file_get_contents(_PS_UPLOAD_DIR_.Configuration::get($this->name.'_import_xml')));
            $xml_array = Xml::toArray(Xml::build($xml_string));

            if (count($xml_array['rss']['channel']['wp:category']) > 0)
            {
                $langue = (int)Tools::getValue('import_xml_langue');
                $modif_categories_parents = array();
                $categories_title = array();
                foreach ($xml_array['rss']['channel']['wp:category'] as $value)
                {
                    $categories_title[$value['wp:category_nicename']] = $value['wp:cat_name'];
                    $id_import_category = (int)CategoriesClass::isCategoriesExist((int)$langue, $value['wp:cat_name']);
                    if (!$id_import_category)
                    {
                        $categorie = new CategoriesClass();
                        $categorie->id_shop = (int)$this->context->shop->id;
                        $categorie->title[(int)$langue] = $value['wp:cat_name'];
                        $categorie->link_rewrite[(int)$langue] = PrestaBlog::prestablogFilter(Tools::link_rewrite($value['wp:cat_name']));
                        $categorie->add();
                        $id_import_category = $categorie->id;
                    }
                    if ($value['wp:category_parent'] != '')
                    {
                        $modif_categories_parents[$value['wp:category_nicename']]['id_import_category'] = $id_import_category;
                        $modif_categories_parents[$value['wp:category_nicename']]['parent'] = $value['wp:category_parent'];
                        $modif_categories_parents[$value['wp:category_nicename']]['title'] = $value['wp:cat_name'];
                    }
                }

                foreach ($modif_categories_parents as $value)
                {
                        $id_import_category = (int)CategoriesClass::isCategoriesExist((int)$langue, $value['title']);

                        $categorie = new CategoriesClass((int)$id_import_category);
                        $categorie->parent = (int)CategoriesClass::isCategoriesExist((int)$langue, $categories_title[$value['parent']]);

                        $categorie->save();
                }
            }

            if (count($xml_array['rss']['channel']['item']) > 0)
            {
                $liste_items = array();

                if (isset($xml_array['rss']['channel']['item']['title']))
                    $liste_items[0] = $xml_array['rss']['channel']['item'];
                else
                    $liste_items = $xml_array['rss']['channel']['item'];

                foreach ($liste_items as $v_item)
                {
                    if ($v_item['wp:post_type'] == 'post')
                    {
                        $post = new NewsClass();
                        $post->id_shop = (int)$this->context->shop->id;
                        $post->date = $v_item['wp:post_date'];
                        $post->langues = serialize(
                                                                array(
                                                                        0 => (int)Tools::getValue('import_xml_langue')
                                                                    )
                                                                );

                        if ($v_item['wp:status'] == 'publish')
                            $post->actif            = 1;
                        else
                            $post->actif            = 0;

                        $post->title[(int)Tools::getValue('import_xml_langue')]         = $v_item['title'];
                        $post->paragraph[(int)Tools::getValue('import_xml_langue')]     = $v_item['excerpt:encoded'];
                        $post->content[(int)Tools::getValue('import_xml_langue')]       = $v_item['content:encoded'];
                        $post->meta_title[(int)Tools::getValue('import_xml_langue')]    = $v_item['title'];

                        if (trim($v_item['wp:post_name']) == '')
                            $v_item['wp:post_name'] = PrestaBlog::prestablogFilter(Tools::link_rewrite($v_item['title']));
                        else
                            $v_item['wp:post_name'] = PrestaBlog::prestablogFilter(Tools::link_rewrite($v_item['wp:post_name']));

                        $post->link_rewrite[(int)Tools::getValue('import_xml_langue')]  = $v_item['wp:post_name'];

                        /** gestion des catégories et tags */
                        if (isset($v_item['category']) && count($v_item['category']) > 0)
                        {
                            $import_categories = array();
                            $import_categories_id = array();
                            if (isset($v_item['category']['@domain']))
                            {
                                /** gestion des catégories */
                                if ($v_item['category']['@domain'] == 'category')
                                    $import_categories[] = $v_item['category']['@'];

                                /** gestion des tags > keywords */
                                if ($v_item['category']['@domain'] == 'post_tag')
                                    $key_words = $v_item['category']['@'];
                            }
                            else
                            {
                                /** gestion des catégories */
                                if (count($v_item['category']) > 0)
                                {
                                    foreach ($v_item['category'] as $v_category)
                                        if ($v_category['@domain'] == 'category')
                                            $import_categories[] = $v_category['@'];
                                    $import_categories = array_unique($import_categories);
                                }

                                /** gestion des tags > keywords */
                                $import_tags = array();
                                if (count($v_item['category']) > 0)
                                {
                                    foreach ($v_item['category'] as $v_tag)
                                        if ($v_tag['@domain'] == 'post_tag')
                                            $import_tags[] = $v_tag['@'];
                                    $import_tags = array_unique($import_tags);
                                }
                                $key_words = '';
                                if (count($import_tags) > 0)
                                    foreach ($import_tags as $v_import_tag)
                                        $key_words .= $v_import_tag.', ';
                                $key_words = rtrim($key_words, ', ');
                            }

                            if (count($import_categories) > 0)
                            {
                                foreach ($import_categories as $v_import_categorie)
                                {
                                    if ($id_import_category = CategoriesClass::isCategoriesExist((int)Tools::getValue('import_xml_langue'),
                                            $v_import_categorie))
                                        $import_categories_id[] = $id_import_category;
                                    else
                                    {
                                        $categorie = new CategoriesClass();
                                        $categorie->id_shop = (int)$this->context->shop->id;
                                        $categorie->title[(int)Tools::getValue('import_xml_langue')] = $v_import_categorie;
                                        $categorie->link_rewrite[(int)Tools::getValue('import_xml_langue')] =
                                            PrestaBlog::prestablogFilter(Tools::link_rewrite($v_import_categorie));
                                        $categorie->add();
                                        $import_categories_id[] = $categorie->id;
                                    }
                                }
                            }

                            $post->meta_keywords[(int)Tools::getValue('import_xml_langue')] = Tools::substr($key_words, 0, 254);
                        }

                        $post->add();
                        if ($post->id)
                        {
                            $post->razEtatLangue((int)$post->id);
                            $post->changeActiveLangue((int)$post->id, (int)Tools::getValue('import_xml_langue'));

                            /** gestion des commentaires */
                            if (isset($v_item['wp:comment']) && count($v_item['wp:comment']) > O)
                            {
                                $comment = new CommentNewsClass();
                                if (isset($v_item['wp:comment']['wp:comment_author']))
                                {
                                    $comment->news      = $post->id;

                                    $v_item['wp:comment']['wp:comment_author'] = Tools::substr($v_item['wp:comment']['wp:comment_author'], 0, 254);
                                    $comment->name  = (trim($v_item['wp:comment']['wp:comment_author']) == '' ?
                                                            $this->l('Nobody') : $v_item['wp:comment']['wp:comment_author']);
                                    if (Validate::isUrlOrEmpty($v_item['wp:comment']['wp:comment_author_url']))
                                        $comment->url       = $v_item['wp:comment']['wp:comment_author_url'];

                                    $comment->comment   = $v_item['wp:comment']['wp:comment_content'];
                                    $comment->date      = $v_item['wp:comment']['wp:comment_date'];

                                    if ((int)$v_item['wp:comment']['wp:comment_approved'] == 1)
                                        $comment->actif     = 1;
                                    else
                                        $comment->actif     = 0;

                                    $comment->add();
                                }
                                else
                                {
                                    foreach ($v_item['wp:comment'] as $v_comment)
                                    {
                                        $comment = new CommentNewsClass();

                                        $comment->news      = $post->id;

                                        $v_comment['wp:comment_author'] = Tools::substr($v_comment['wp:comment_author'], 0, 254);
                                        $comment->name      = (trim($v_comment['wp:comment_author']) == '' ?
                                                    $this->l('Nobody') : $v_comment['wp:comment_author']);
                                        if (Validate::isUrlOrEmpty($v_comment['wp:comment_author_url']))
                                            $comment->url       = $v_comment['wp:comment_author_url'];

                                        $comment->comment   = $v_comment['wp:comment_content'];
                                        $comment->date      = $v_comment['wp:comment_date'];

                                        if ((int)$v_comment['wp:comment_approved'] == 1)
                                            $comment->actif     = 1;
                                        else
                                            $comment->actif     = 0;

                                        $comment->add();
                                    }
                                }
                            }
                            /** liaison des catégories aux articles */
                            if (count($import_categories_id) > 0)
                                CorrespondancesCategoriesClass::updateCategoriesNews($import_categories_id, $post->id);
                        }
                    }
                }
            }
            else
                $errors[] = $this->l('No items to import');

            if (!count($errors))
            {
                self::unlinkFile(_PS_UPLOAD_DIR_.Configuration::get($this->name.'_import_xml'));
                Configuration::updateValue($this->name.'_import_xml', null);
                Tools::redirectAdmin($this->path_module_conf.'&import&feedback=Yu3Tr9r7');
            }
        }
        elseif (Tools::isSubmit('submitImportXml'))
        {
            if (!$this->demo_mode)
            {
                if (isset($_FILES[$this->name.'_import_xml']) && is_uploaded_file($_FILES[$this->name.'_import_xml']['tmp_name']))
                {
                    if ($_FILES[$this->name.'_import_xml']['size'] > (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024 * 1024))
                        $errors[] = sprintf(
                            $this->l('The file is too large. Maximum size allowed is: %1$d kB. The file you\'re trying to upload is: %2$d kB.'),
                            (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024),
                            number_format(($_FILES[$this->name.'_import_xml']['size'] / 1024), 2, '.', '')
                        );
                    else
                    {
                        do $uniqid = sha1(microtime());
                        while (file_exists(_PS_UPLOAD_DIR_.$uniqid));
                        if (!self::copy($_FILES[$this->name.'_import_xml']['tmp_name'], _PS_UPLOAD_DIR_.$uniqid))
                            $errors[] = $this->l('File copy failed');

                        self::unlinkFile($_FILES[$this->name.'_import_xml']['tmp_name']);
                        self::unlinkFile(_PS_UPLOAD_DIR_.Configuration::get($this->name.'_import_xml'));
                        Configuration::updateValue($this->name.'_import_xml', $uniqid);
                    }
                }
                else
                    Tools::redirectAdmin($this->path_module_conf.'&import&feedback=2yt6wEK7');
            }

            if (!count($errors))
                Tools::redirectAdmin($this->path_module_conf.'&import');
        }

        if ($post_en_cours)
        {
            if (count($errors) > 0)
                $this->html_out .= $this->displayError(implode('<br />', $errors));
            else
                $this->html_out .= $this->displayConfirmation($this->l('Settings updated successfully'));
        }

    }

    public function displayError($error)
    {
        $output = '
            <div class="bootstrap">
                <div class="alert alert-danger">
                    <button class="close" data-dismiss="alert" type="button">×</button>
                    <strong>'.$this->l('Error').'</strong><br>
                    '.$error.'
                ‌</div>
            </div>';

        $this->error = true;
        return $output;
    }

    public function displayWarning($warn)
    {
        $output = '
            <div class="bootstrap">
                <div class="alert alert-warning">
                    <button class="close" data-dismiss="alert" type="button">×</button>
                    <strong>'.$this->l('Warning').'</strong><br/>
                    '.$warn.'
                ‌</div>
            </div>';

        return $output;
    }

    public function displayInfo($info)
    {

        $output = '
            <div class="bootstrap">
                <div class="alert alert-info">
                    <strong>'.$this->l('Information').'</strong><br/>
                    '.$info.'
                ‌</div>
            </div>';

        return $output;
    }

    private function moduleDatepicker($class, $time)
    {
        $return = '';
        if ($time) {
            $return = '
                var dateObj = new Date();
                var hours = dateObj.getHours();
                var mins = dateObj.getMinutes();
                var secs = dateObj.getSeconds();
                if (hours < 10) { hours = "0" + hours; }
                if (mins < 10) { mins = "0" + mins; }
                if (secs < 10) { secs = "0" + secs; }
                var time = " "+hours+":"+mins+":"+secs;';
        }
        $return .= '
            $(function() {
                $("#'.Tools::htmlentitiesUTF8($class).'").datepicker({
                    prevText:"",
                    nextText:"",
                    dateFormat:"yy-mm-dd"'.($time ? '+time' : '').'});
            });';

        return '<script type="text/javascript">'.$return.'</script>';
    }

    public function checkPresenceFoldersCritiques()
    {
        $errors = array();
        $success = array();

        if (!is_dir(_PS_MODULE_DIR_.$this->name.'/mails/en')) {
            $errors[] = $this->l('No existing the module\'s default "en" mails folder.');
            $is_extract = Tools::ZipExtract(
                _PS_MODULE_DIR_.$this->name.'/lost/mails/en.zip',
                _PS_MODULE_DIR_.$this->name.'/mails/'
            );
            if (!$is_extract) {
                $errors[] = $this->l('Error extract the module\'s default "en" mails folder.');
            } else {
                $success[] = $this->l('Restore the module\'s default "en" mails folder successfull.');
            }
        }

        if (Configuration::get($this->name.'_sitemap_actif')) {
            if (!is_dir(_PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$this->context->shop->id)) {
                $errors[] = sprintf(
                    $this->l('No existing the sitemap folder for %1$s'),
                    $this->context->shop->name
                );
                if (!self::makeDirectory(_PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$this->context->shop->id)) {
                    $errors[] = sprintf(
                        $this->l('Error creating the sitemap folder for %1$s.'),
                        $this->context->shop->name
                    );
                } else {
                    $success[] = sprintf(
                        $this->l('Creating sitemap folder for %1$s successfull'),
                        $this->context->shop->name
                    );
                }
            }
            if (!is_writable(_PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$this->context->shop->id)) {
                $errors[] = sprintf(
                    $this->l('The folder %1$s not have the write permissions.'),
                    '<strong>'._PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$this->context->shop->id.'</strong>'
                );
            }

            if (count($errors) > 0) {
                $this->html_out = $this->displayError(implode('<br />', $errors));
                if (count($success) > 0) {
                    $this->html_out .= $this->displayConfirmation(implode('<br />', $success));
                }
                $this->html_out .= '
                    <a
                        href="'.$this->confpath.'"
                        class="button"
                    >
                        <img src="'.self::imgPathFO().'refresh.png" />
                        &nbsp;'.$this->l('Refresh this page to enter again on the configuration of module.').'
                    </a>';

                return $this->html_out;
            }
        }
    }

    private function displayNavConfiguration()
    {
        $nav = '';
        $nav .= '
            <nav>
                <ul >
                    <li>
                        <a href="'.$this->confpath.'">
                            <img src="'.self::imgPathFO().'home.png" />
                            '.$this->l('Home').'
                        </a>
                    </li>';
        $nav .= '
                <li>
                    <a href="#">
                        <img src="'.self::imgPathFO().'content.png" />
                        '.$this->l('Manage content').'
                    </a>
                    <ul>
                        <li>
                            <a href="'.$this->confpath.'&newsListe">
                                <img src="'.self::imgPathFO().'copy_files.gif" />
                                '.$this->l('News').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&commentListe">
                                <img src="'.self::imgPathFO().'comments.png" />
                                '.$this->l('Comments').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&catListe">
                                <img src="'.self::imgPathFO().'categories.png" />
                                '.$this->l('Categories').'
                            </a>
                        </li>';


                         //<li>
                             //<a href="'.$this->confpath.'//&lookbookListe">
                               //  <img src="'.self::imgPathFO().'lookbook.png" />
                                 //'.$this->l('Lookbook').'
                             //</a>
                         //</li>


        $nav .= '                <li>
                        <a href="'.$this->confpath.'&configSubBlocks">
                            <img src="'.self::imgPathFO().'brick.png" />
                            '.$this->l('Customize news list').'
                        </a>
                    </li>';


                    $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
	$nav .='<li>
                        <a href="'.$this->confpath.'&class=PopupClass&displayContent">
                            <img src="'.self::imgPathFO().'brick.png" />
                            '.$this->l('Popup').'
                        </a>
                    </li>';
                }

        $nav .= '</ul>
                </li>';
        $nav .= '
                <li>
                    <a href="#">
                        <img src="'.self::imgPathFO().'tools.png" />
                        '.$this->l('Tools').'
                    </a>
                    <ul>
                        <li>
                            <a href="'.$this->confpath.'&configAntiSpam">
                                <img src="'.self::imgPathFO().'shield.png" />
                                '.$this->l('Anti-spam').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&import">
                                <img src="'.self::imgPathFO().'import.png" />
                                '.$this->l('Import WordPress XML').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&sitemap">
                                <img src="'.self::imgPathFO().'sitemap.png" />
                                '.$this->l('Sitemap').'
                            </a>
                        </li>';
        $nav .= '            </ul>
                </li>';

        $nav .= '
                <li>
                    <a href="#">
                        <img src="'.self::imgPathFO().'cog.gif" />
                        '.$this->l('Configuration').'
                    </a>
                    <ul>';

        $nav .= '                <li>
                            <a href="'.$this->confpath.'&configTheme">
                                <img src="'.self::imgPathFO().'theme.png" />
                                '.$this->l('Theme and slide').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&pageBlog">
                                <img src="'.self::imgPathFO().'blog.png" />
                                '.$this->l('Blog page').'
                            </a>
                        </li>';
        $nav .= '    <li>
                            <a href="'.$this->confpath.'&configCategories">
                                <img src="'.self::imgPathFO().'categories.png" />
                                '.$this->l('Categories').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&configBlocs">
                                <img src="'.self::imgPathFO().'blocs.png" />
                                '.$this->l('Blocks').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&configComments">
                                <img src="'.self::imgPathFO().'comments.png" />
                                '.$this->l('Comments').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&configModule">
                                <img src="'.self::imgPathFO().'globalconf.png" />
                                '.$this->l('Global').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&colorBlog">
                                <img src="'.self::imgPathFO().'design.png" />
                                '.$this->l('Design').'
                            </a>
                        </li>
                    </ul>
                </li>';
        $nav .= '
                <li>
                    <a href="#">
                    <img src="'.self::imgPathFO().'help.png" />&nbsp;'.$this->l('Help').'</a>
                    <ul>
                        <li>
                            <a href="'.$this->confpath.'&documentation">
                                <img src="'.self::imgPathFO().'tutoriel.png" />
                                '.$this->l('Documentation').'
                            </a>
                        </li>
                        <li>
                            <a href="'.$this->confpath.'&informations">
                                <img src="'.self::imgPathFO().'informations.png" />
                                '.$this->l('Informations').'
                            </a>
                        </li>
                    </ul>
                </li>';
                /*$nav .= '
                <li>
                    <a href="#">
                        <img src="'.self::imgPathFO().'tools.png" />
                        '.$this->l('Author').'
                    </a>
                    <ul>
                    <li>
                    <a href="'.$this->confpath.'&authorList">
                        <img src="'.self::imgPathFO().'tools.png" />
                        '.$this->l('Author List').'
                    </a>
                    </li>
                    <li>
                    <a href="'.$this->confpath.'&accountGest">
                        <img src="'.self::imgPathFO().'tools.png" />
                        '.$this->l('My account').'
                    </a>
                    </li>
                    </ul>
                    </li>';*/

        $demomodecall = '';
        if ($this->demo_mode) {
            $demomodecall = ' / '.$this->l('Demo mode');
        }
        $nav .= '
                    <li id="nav-version">
                        '.$this->l('Version').' : '.$this->version.$demomodecall.'
                    </li>
                    <li class="nav-extra-link">
                        <a href="'.self::prestablogUrl(array()).'" target="_blank">
                            <img src="'.self::imgPathFO().'frontoffice.png" />
                            '.$this->l('View blog page').'
                        </a>
                    </li>
                </ul>
            </nav>';
        return $nav;
    }

    public function checkIfUpdate16to17()
    {
        // Script FOR LOOKBBOOK AFTER UPDATE PS 1.7
        $udpate_for_look_book = '
            <p>
            '.$this->l('You just updated your prestashop from 1.6 to 1.7, please, click on "Upgrading PrestaBlog"').'
            </p>
            <p>
                <a href="'.$this->confpath.'&udpateForLookBook">
                    <i class="process-icon-refresh" style="float:left;"></i>&nbsp;'.$this->l('Upgrading PrestaBlog').'
                </a>
            </p>';

        if (!LookBookClass::isTableInstalled()) {
            if (Tools::isSubmit('udpateForLookBook')) {
                // Script FOR CONFIG AFTER UPDATE PS 1.7
                if (self::getT() != $this->default_theme) {
                    Configuration::updateValue($this->name.'_theme', $this->default_theme);
                }
                if (!Configuration::get($this->name.'_layout_blog')) {
                    Configuration::updateValue('prestablog_layout_blog', 2);
                }
                if (!Configuration::get($this->name.'_lb_title_length')) {
                    Configuration::updateValue('prestablog_lb_title_length', 80);
                }
                if (!Configuration::get($this->name.'_lb_intro_length')) {
                    Configuration::updateValue('prestablog_lb_intro_length', 120);
                }
                // /Script FOR CONFIG AFTER UPDATE PS 1.7

                $lookbook = new LookBookClass();
                if ($lookbook->registerTablesBdd() && $this->installHookPS17()) {
                    Tools::redirectAdmin($this->confpath);
                }
            }

            $this->html_out .= $this->displayWarning($udpate_for_look_book);
        }
        // /Script FOR LOOKBBOOK AFTER UPDATE PS 1.7
    }




    public function getContent()
    {

        $this->checkIfUpdate16to17();

        $this->backoffice_content = true;

        if (Tools::version_compare($this->version, self::getModuleDataBaseVersion(), '>')) {
            if (file_exists(_PS_MODULE_DIR_.$this->name.'/config.xml')) {
                self::unlinkFile(_PS_MODULE_DIR_.$this->name.'/config.xml');
            }

            foreach (glob(_PS_MODULE_DIR_.$this->name.'/config_[a-z][a-z].{xml}', GLOB_BRACE) as $config_module_file) {
                if (!is_dir($config_module_file)) {
                    self::unlinkFile($config_module_file);
                }
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }

        if ($error_critique = $this->checkPresenceFoldersCritiques()) {
            return $error_critique;
        }

        // $this->checkConfiguration();

        // $sub_blocks = new SubBlocksClass();
        // $sub_blocks->registerTablesBdd();

        $this->postForm();

        if (Tools::getValue('feedback')) {
            $this->html_out .= '
                <script type="text/javascript">
                    $(document).ready(function() {
                    jAlert("'.$this->message_call_back[Tools::getValue('feedback')].'", "'.$this->l('Information').'");
                    });
                </script>';
        }


        $this->context->controller->addJqueryUI('ui.datepicker');

        $this->context->controller->addCSS($this->_path.'views/css/admin.css');

        $this->html_out .= '
            <link
                type="text/css"
                rel="stylesheet"
                href="'.self::httpS().'://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"
            />'."\n";
            $this->html_out .='<script type="text/javascript" src="'.__PS_BASE_URI__.'js/jquery/plugins/jquery.colorpicker.js"></script>';
		$this->html_out .='<script type="text/javascript">
		$.fn.mColorPicker.init.replace = ".mColorPicker"</script>';

        $this->html_out .= '<div id="configuration_blog">';

        $this->html_out .= $this->displayNavConfiguration();

        $this->html_out .= '<div id="contenu_config_prestablog">';

        if (Tools::isSubmit('addNews')
            || Tools::isSubmit('editNews')
            || Tools::isSubmit('submitAddNews')
            || (Tools::isSubmit('submitUpdateNews') && Tools::getValue('idN'))
        ) {
            $this->displayFormNews();
        } elseif (Tools::isSubmit('addCat')
            || Tools::isSubmit('editCat')
            || Tools::isSubmit('submitAddCat')
            || (Tools::isSubmit('submitUpdateCat') && Tools::getValue('idC'))
        ) {
            $this->displayFormCategories();
        } elseif (Tools::isSubmit('addLookbook')
            || Tools::isSubmit('editLookBook')
            || Tools::isSubmit('submitAddLookbook')
            || (Tools::isSubmit('submitUpdateLookbook') && Tools::getValue('idLB'))
        ) {
            $this->displayFormLookbook();
        } elseif (Tools::isSubmit('orderCat')) {
            $this->displayOrderCategories();
        } elseif (Tools::isSubmit('submitOrderCat')) {
            if (Tools::getValue('newOrderCat')) {
                $new_order_cat = array();
                foreach (preg_split('/\&/', Tools::getValue('newOrderCat')) as $key => $value) {
                    $current_order_list = preg_split('/\=/', $value);

                    if (preg_match('/\d+/', $current_order_list[0], $match_id)) {
                        $id_prestablog_categorie = (int)$match_id[0];
                    } else {
                        $id_prestablog_categorie = 0;
                    }

                    $parent = (int)$current_order_list[1];

                    $new_order_cat[] = array(
                        'id_prestablog_categorie' => (int)$id_prestablog_categorie,
                        'parent' => (int)$parent,
                        'position' => (int)$key,
                    );
                }

                foreach ($new_order_cat as $value) {
                    CategoriesClass::updatePosition(
                        (int)$value['id_prestablog_categorie'],
                        (int)$value['parent'],
                        (int)$value['position']
                    );
                }
            }
            $this->displayOrderCategories();
        } elseif (Tools::isSubmit('addAntiSpam')
            || Tools::isSubmit('editAntiSpam')
            || Tools::isSubmit('submitAddAntiSpam')
            || (Tools::isSubmit('submitUpdateAntiSpam') && Tools::getValue('idAS'))
        ) {
            $this->displayFormAntiSpam();
        } elseif (Tools::isSubmit('editComment')
            || (Tools::isSubmit('submitUpdateComment') && Tools::getValue('idC'))
        ) {
            $this->displayFormComments();
        } elseif (Tools::isSubmit('addSubBlock')
            || Tools::isSubmit('editSubBlock')
            || Tools::isSubmit('submitAddSubBlock')
            || (Tools::isSubmit('submitUpdateSubBlock') && Tools::getValue('idSB'))
        ) {
            $this->displayFormSubBlocks();
        } elseif (Tools::isSubmit('submitAddSubBlockFront')
            || (Tools::isSubmit('submitUpdateSubBlockFront') && Tools::getValue('idSBF'))
        ) {
            $this->displayPageBlog();
        } else if (Tools::isSubmit('addpopup')
            || Tools::isSubmit('editpopup')
            || Tools::isSubmit('updatepopup')
            || Tools::isSubmit('addpopupsubmit')
            || Tools::isSubmit('editpopupsubmit')
            || (Tools::isSubmit('updatepopup') && Tools::getValue('idN'))
            || Tools::isSubmit('deletepopup')
            || Tools::isSubmit('statuspopup')
        ) {

        	$this->popupProcess();
            $this->getContentPopup();
        }elseif (Tools::isSubmit('pageBlog')) {
            $this->displayPageBlog();
        } elseif (Tools::isSubmit('configAntiSpam')) {
            $this->displayConfigAntiSpam();
        } elseif (Tools::isSubmit('sitemap')) {
            $this->displaySitemap();
        } elseif (Tools::isSubmit('configModule')) {
            $this->displayConf();
        } elseif (Tools::isSubmit('configTheme')) {
            $this->displayConfTheme();
        } elseif (Tools::isSubmit('configWizard')) {
            $this->displayConfWizard();
        } elseif (Tools::isSubmit('configSubBlocks')) {
            $this->displayListeSubBlocks();
        } elseif (Tools::isSubmit('displayContent')) {
        	$this->popupProcess();
			$this->getContentPopup();
        } elseif (Tools::isSubmit('lookbook')) {
            $this->displayListeLookBook();
        } elseif (Tools::isSubmit('configCategories')) {
            $this->displayConfCategories();
        } elseif (Tools::isSubmit('configBlocs')) {
            $this->displayConfBlocs();
        } elseif (Tools::isSubmit('configProductTab')) {
            $this->displayConfProductTab();
        } elseif (Tools::isSubmit('configComments')) {
            $this->displayConfComments();
        } elseif (Tools::isSubmit('colorBlog')) {
            $this->displayColorBlog();
        }/* elseif (Tools::isSubmit('authorList')) {
            $this->displayAuthorList();
        } elseif (Tools::isSubmit('accountGest')) {
            $this->displayAccount();
        }*/ elseif (Tools::isSubmit('debug')) {
            $this->displayDebug();
        } elseif (Tools::isSubmit('documentation')) {
            $this->displayDocumentation();
        } elseif (Tools::isSubmit('informations')) {
            $this->displayInformations();
        } elseif (Tools::isSubmit('import')) {
            $this->displayImport();
        } elseif (Tools::isSubmit('catListe')) {
            $this->displayListeCategories();
        } elseif (Tools::isSubmit('newsListe')) {
            $this->displayListeNews();
        } elseif (Tools::isSubmit('lookbookListe')) {
            $this->displayListeLookBook();
        } elseif (Tools::isSubmit('commentListe')) {
            $this->displayListeComments();
        } else if(Tools::isSubmit('submitDisplaysliderModule')) {
            $this->postProcess();
            Tools::redirectAdmin($this->confpath.'&configTheme');
        }

        else {
            $this->displayHome();
        }

        $this->html_out .= '</div>';
        $this->html_out .= '</div>';

        return $this->html_out;

    }


    public function getContentPopup()
    {
        $pathM = AdminController::$currentIndex.'&configure=prestablog&token='.Tools::getAdminTokenLite('AdminModules').'&displayContent';

        if (!Tools::getValue('class')) {
            Tools::redirectAdmin($pathM.'&class=PopupClass');
        }

		  $update_process = false;
        $errors = array();
        $warnings = array();


        if ((int)Tools::getIsset('success')) {
            $this->html_out .= '
                <script type="text/javascript">
                    $(function () {
                        showSuccessMessage(\''.$this->l('Settings updated successfully').'\');
                    });
                </script>';

        }
        if ((int)Tools::getIsset('undo') || (int)Tools::getIsset('error')) {
            $this->html_out .= '
                <script type="text/javascript">
                    $(function () {
                        showErrorMessage(\''.$this->l('The current action was canceled').'\');
                    });
                </script>';
        }
        foreach ($this->class_used['table'] as $object_model) {

            $pp = $this->pp_conf.'&class='.$object_model;
            if (Tools::getValue('class') == $object_model) {
                $definition = ObjectModel::getDefinition($object_model);
                if (Tools::isSubmit('statuspopup')) {
                    $process_model = self::createDynInstance($object_model);
                    if (!$process_model->changeState((int)Tools::getValue($definition['primary']))) {
                        $errors[] = $this->l('Could not change status.');
                    } else {
                        Tools::redirectAdmin($pp.'&displayContent&success');
                    }
                    $update_process = true;
                }
                if (Tools::isSubmit('deletepopup')) {

                    $process_model = self::createDynInstance(
                        $object_model,
                        array((int)Tools::getValue($definition['primary']))
                    );
                    if (!$process_model->deletepopup()) {
                        $errors[] = $this->l('Could not delete.');
                    } else {
                        Tools::redirectAdmin($pp.'&displayContent&success');
                    }
                    $update_process = true;
                }
                if (Tools::isSubmit('submitBulkdeletepopup')) {
                    if (Tools::getValue($definition['table'].'Box')) {
                        foreach (Tools::getValue($definition['table'].'Box') as $id) {
                            $process_model = self::createDynInstance($object_model, array((int)$id));
                            if (!$process_model->deletepopup()) {
                                $errors[] = sprintf($this->l('Could not delete %1$s'), $id);
                            }
                        }
                        if (!count($errors) > 0) {
                            Tools::redirectAdmin($pp.'&displayContent&success');
                        }
                    }
                    $update_process = true;
                }

                foreach ($this->actions_form as $action) {

                    if (Tools::isSubmit($action)) {

                        switch ($action) {
                            case 'addpopupsubmit':

                                $process_model = self::createDynInstance($object_model);
                                $process_model->copyFromPost();
                                if ($process_model->add()) {
                                    Tools::redirectAdmin($pp.'&displayContent&success');
                                }
                                break;

                            case 'editpopupsubmit':
                                $process_model = self::createDynInstance(
                                    $object_model,
                                    array((int)Tools::getValue($definition['primary']))
                                );
                                $process_model->copyFromPost();
                                if ($process_model->update()) {
                                    Tools::redirectAdmin(
                                        $pp.'&'.$definition['primary'].'='.(int)Tools::getValue(
                                            $definition['primary']
                                        ).'&displayContent&success'
                                    );
                                }
                                break;
                        }
                        $update_process = true;
                    }
                }
            }
        }


       if (Tools::getValue('class') && in_array(Tools::getValue('class'), $this->class_used['table'])) {

            $current_class = Tools::getValue('class');
            if (!class_exists($current_class)) {
                $this->html_out .= $this->displayError($this->l('This class doesn\'t exists: ').$current_class);

            } else {

                $object_model = self::createDynInstance($current_class, array());

                if (is_object($object_model)) {
                    $definition_lang = $object_model->definitionLang();

                    if (!Tools::isSubmit('add')
                        && !Tools::isSubmit('edit')
                        && !Tools::getIsset('add'.$definition_lang['tableName'])
                        && !Tools::getIsset('update'.$definition_lang['tableName'])
                    ) {

                        $this->html_out .= $object_model->displayList();
                    }

                    if (Tools::getIsset('add'.$definition_lang['tableName']) || Tools::isSubmit('addpopup')) {

                        $this->html_out .= $object_model->displayForm('add');
                    }

                    if (Tools::getIsset('update'.$definition_lang['tableName']) || Tools::isSubmit('updatepopup')) {
                        $this->html_out .= $object_model->displayForm('edit');
                    }
                }
            }
        }




        if (count($errors) > 0) {
            $this->html_out .= $this->displayError($errors);
        }
        if (count($warnings) > 0) {
            $this->html_out .= $this->displayWarning($warnings);
        }
        if ($this->html_out != '') {
            return $this->html_out;
        }
        if ($update_process) {
            return $this->displayConfirmation($this->l('Settings updated successfully'));
        } else {
            return null;
        }

    }

    private function displayHome()
    {

        if (Configuration::get('prestablog_commentfb_actif')) {
            $this->html_out .= '<div class="col-sm-4">'."\n";
            $this->html_out .= '
                <p>
                    <img src="'.self::imgPathFO().'facebook.png" />&nbsp;'.$this->l('Facebook comments').'
                </p>'."\n";
            $mod_com = $this->l('To moderate comments, go on the front office at bottom of each posts.');
            $this->html_out .= $this->displayInfo('<p>'.$mod_com.'</p>');
            $this->html_out .= '</div>';
        } else {
            $comments_non_lu = CommentNewsClass::getListeNonLu();
            $this->html_out .= '
                <div class="col-sm-4">
                    <p>
                        <img
                            src="'.self::imgPathFO().'question.gif"
                            alt="'.$this->l('Pending').'"
                        />&nbsp;'
                        .count($comments_non_lu).'&nbsp;'.sprintf(
                            $this->l('comment%1$s pending'),
                            (count($comments_non_lu) > 1 ? 's':'')
                        ).'
                    </p>'."\n";

            if (count($comments_non_lu) > 0) {
                foreach ($comments_non_lu as $value_c) {
                    $this->html_out .= '<div class="blocmodule">'."\n";
                    $news = new NewsClass((int)$value_c['news'], (int)$this->context->language->id);
                    $askconfirm = $this->l('Are you sure?');
                    $this->html_out .= '
                    <p>
                        <a
                            href="'.$this->confpath.'&deleteComment&idC='.$value_c['id_prestablog_commentnews'].'"
                            class="hrefComment"
                            onclick="return confirm(\''.$askconfirm.'\');"
                            style="float:right;"
                        >
                            <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                            <span style="display:none;">'.$this->l('Delete').'</span>
                        </a>
                        <a
                            href="'.$this->confpath.'&editComment&idC='.$value_c['id_prestablog_commentnews'].'"
                            class="hrefComment"
                            style="float:right;"
                        >
                            <img src="'.self::imgPathFO().'edit.gif" />
                            <span style="display:none;">'.$this->l('Edit').'</span>
                        </a>
                        <a href="'.$this->confpath.'&editNews&idN='.$value_c['news'].'">'.$news->title.'</a>
                    </p>'."\n";
                    $this->html_out .= '
                        <h4>
                            '.ToolsCore::displayDate($value_c['date'], null, true).', '.$this->l('by').'
                            <strong>'.$value_c['name'].'</strong>
                        </h4>'."\n";
                    if ($value_c['url'] != '') {
                        $this->html_out .= '
                            <h5>
                                <a href="'.$value_c['url'].'" target="_blank">'.$value_c['url'].'</a>
                            </h5>'."\n";
                    }
                    $this->html_out .= ' <p>'.$value_c['comment'].'</p>'."\n";
                    $this->html_out .= '
                        <p>&nbsp;
                            <a
                                href="'.$this->confpath.'&enabledComment&idC='.$value_c['id_prestablog_commentnews'].'"
                                class="hrefComment"
                                style="float:right;"
                            >
                                <img
                                    src="'.self::imgPathFO().'enabled.gif"
                                    alt="'.$this->l('Approuved').'"
                                />
                                <span style="display:none;">'.$this->l('Approuved').'</span>
                            </a>
                            <a
                                href="'.$this->confpath.'&disabledComment&idC='.$value_c['id_prestablog_commentnews'].'"
                                class="hrefComment"
                                style="float:right;"
                            >
                                <img
                                    src="'.self::imgPathFO().'disabled.gif"
                                    alt="'.$this->l('Disabled').'"
                                />
                                <span style="display:none;">'.$this->l('Disabled').'</span>
                            </a>
                        </p>'."\n";
                    $this->html_out .= '</div>'."\n";
                }
            }
            $this->html_out .= '</div>'."\n";
        }

        $liste_news = NewsClass::getListe(
            (int)$this->context->language->id,
            1,
            0,
            0,
            (int)Configuration::get($this->name.'_lastnews_limit'),
            'n.`date`',
            'desc',
            null,
            null,
            null,
            0,
            (int)Configuration::get('prestablog_news_title_length'),
            (int)Configuration::get('prestablog_news_intro_length')
        );

        $this->html_out .= '
            <div class="col-sm-8">
                <p>
                    <img src="'.self::imgPathFO().'lastnews.png" alt="'.$this->l('News').'" />&nbsp;'
                    .(int)Configuration::get($this->name.'_lastnews_limit').' '.$this->l('latest news').'
                </p>'."\n";

        if (count($liste_news) > 0) {
            foreach ($liste_news as $value_n) {
                $this->html_out .= '<div class="blocmodule">'."\n";
                if (file_exists(self::imgUpPath().'/adminth_'.$value_n['id_prestablog_news'].'.jpg')) {
                    $imgidreload = 'adminth_'.$value_n['id_prestablog_news'].'.jpg?'.md5(time());
                    $this->html_out .= '
                        <img
                            src="'.self::imgPathBO().self::getT().'/up-img/'.$imgidreload.'"
                            class="thumb"
                        />'."\n";
                }
                $this->html_out .= '
                    <p class="title">
                        <a
                            href="'.$this->confpath.'&editNews&idN='.$value_n['id_prestablog_news'].'"
                            class="hrefComment"
                            style="float:right;"
                        >
                            <img src="'.self::imgPathFO().'edit.gif" alt="'.$this->l('Edit').'" />
                            <span style="display:none;">'.$this->l('Edit').'</span>
                        </a>'.$value_n['title'].'
                    </p>'."\n";
                $this->html_out .= ' <p>'.ToolsCore::displayDate($value_n['date'], null, true).'</p>'."\n";
                $this->html_out .= ' <p>'."\n";

                if ($value_n['paragraph_crop']) {
                    $this->html_out .= $value_n['paragraph_crop'];
                } else {
                    $this->html_out .= '<span style="color:red">'.$this->l('... empty content ...').'</span>';
                }

                $this->html_out .= ' </p>'."\n";
                $this->html_out .= ' <div class="clear"></div>'."\n";
                $this->html_out .= '</div>'."\n";
            }
        }
        $this->html_out .= '</div>'."\n";
               $this->html_out .= '<div class="clearfix"></div>'."\n";

        $this->html_out .= '<div class="clearfix"></div>'."\n";
         $this->html_out .= '<div id ="suggestion_module" class="suggestion_module">';

         $layerslider = Module::getInstanceByName('layerslider');
         $prestaboost = Module::getInstanceByName('prestaboost');
                    if ($layerslider && $prestaboost)
                    {
                  $this->html_out .= '';
                    } else if ($layerslider) {
                         $this->html_out .= '
            <div id ="suggestion_title" class="suggestion_title">
                <h2>
                    '.$this->l('Suggested module for your store, fully compatible with Prestablog ! ').'
                </h2>
            </div>
        ';
        $this->html_out .= '
        <div id="suggestion_banner" class="suggestion_banner col-sm-4 blocmodule">
        <p class="title">Multi pop-up</p>
        <img class="thumb" src="'.self::imgPathFO().'multi.png" />
        <p>'.$this->l('This pop-up will perfectly suit to boost your sellings. This module allows you to install many pop-ups fully customizable.').'<br>
        </p>';
        $this->html_out .= ' <div class="clearfix"></div>'."\n";
        $this->html_out .= '
        <a class="btn btn-default link_button" href="https://addons.prestashop.com/fr/pop-up/624-responsive-multi-popup-prestaboost.html" target="_blank"> '.$this->l('Buy now').' </a>
        <a class="btn btn-default link_button2" href="https://addons.prestashop.com/demo/FO236.html" target="_blank"> Live Demo </a>
        </div>
        ';

    } else if (!$prestaboost) {
        $this->html_out .= '
            <div id ="suggestion_title" class="suggestion_title">
                <h2>
                    '.$this->l('Suggested module for your store, fully compatible with Prestablog ! ').'
                </h2>
            </div>
        ';
        $this->html_out .= '
        <div id="suggestion_banner" class="suggestion_banner col-sm-4 blocmodule">
        <p class="title">Creative slider</p>
        <img class="thumb" src="'.self::imgPathFO().'creative.jpg" />
        <p>'.$this->l('Creative Slider is a premium multi-purpose slider for creating image galleries, content sliders, and mind-blowing slideshows with must-see effects. Fully comaptible with the blog, you can even add the slide to your articles and the blog\'s home page!').'
        </p>';
        $this->html_out .= ' <div class="clearfix"></div>'."\n";
        $this->html_out .= '<a class="btn btn-default link_button" href="https://addons.prestashop.com/fr/sliders-galeries/19062-creative-slider-responsive-slideshow.html" target="_blank"> '.$this->l('Buy now').' </a>
        <a class="btn btn-default link_button2" href="https://addons.prestashop.com/demo/FO11013.html" target="_blank"> Live Demo </a>
        </div>
        ';
    } else {
         $this->html_out .= '
            <div id ="suggestion_title" class="suggestion_title">
                <h2>
                    '.$this->l('Suggested module for your store, fully compatible with Prestablog ! ').'
                </h2>
            </div>
        ';
        $this->html_out .= '
        <div id="suggestion_banner" class="suggestion_banner col-sm-4 blocmodule">
        <p class="title">Creative slider</p>
        <img class="thumb" src="'.self::imgPathFO().'creative.jpg" />
        <p>'.$this->l('Creative Slider is a premium multi-purpose slider for creating image galleries, content sliders, and mind-blowing slideshows with must-see effects. Fully comaptible with the blog, you can even add the slide to your articles and the blog\'s home page!').'
        </p>';
        $this->html_out .= ' <div class="clearfix"></div>'."\n";
        $this->html_out .= '<a class="btn btn-default link_button" href="https://addons.prestashop.com/fr/sliders-galeries/19062-creative-slider-responsive-slideshow.html" target="_blank"> '.$this->l('Buy now').' </a>
        <a class="btn btn-default link_button2" href="https://addons.prestashop.com/demo/FO11013.html" target="_blank"> Live Demo </a>
        </div>
        ';
        $this->html_out .= '
        <div id="suggestion_banner" class="suggestion_banner col-sm-4 blocmodule" style="margin-left : 10px;">
        <p class="title">Multi pop-up</p>
        <img class="thumb" src="'.self::imgPathFO().'multi.png" />
        <p>'.$this->l('This pop-up will perfectly suit to boost your sellings. This module allows you to install many pop-ups fully customizable.').'<br>
        </p>';
        $this->html_out .= ' <div class="clearfix"></div>'."\n";
        $this->html_out .= '
        <a class="btn btn-default link_button" href="https://addons.prestashop.com/fr/pop-up/624-responsive-multi-popup-prestaboost.html" target="_blank">'.$this->l('Buy now').' </a>
        <a class="btn btn-default link_button2" href="https://addons.prestashop.com/demo/FO236.html" target="_blank"> Live Demo </a>
        </div>
        ';
    }

    if (Shop::isFeatureActive()) {
    	$this->html_out .= '
        <div id="suggestion_banner" class="suggestion_banner col-sm-4">
        <p class="title">Multistore</p>
        <p>'.$this->l('If your multistore functionnality is on, please select one shop to configure your blog.').'<br>
        </p>';
   }
$this->html_out .= '</div><div class="clearfix"></div>';
        $this->html_out .= '
            <script type="text/javascript">
                $(document).ready(function() {
                    $("a.hrefComment").mouseenter(function() {
                        $("span:first", this).show(\'slow\');
                    }).mouseleave(function() {
                        $("span:first", this).hide();
                    });
                });
            </script>'."\n";
    }

    public function displayDashBoard()
    {
        $blocks_dash_board = SubBlocksClass::getListe((int)$this->context->language->id, 1, 'displayModuleBoard');

        $this->html_out .= ' <div id="dashboard" class="row">';
        if (count($blocks_dash_board) > 0) {
            $languages = Language::getLanguages(true);

            $languages_shop = array();
            foreach (Language::getLanguages() as $value) {
                $languages_shop[$value['id_lang']] = $value['iso_code'];
            }

            foreach ($blocks_dash_board as $v_block) {
                $news_liste = self::returnUniversalNewsListSubBlocks($v_block, unserialize($v_block['langues']));

                $this->html_out .= '
                    <div class="dashblock">
                        <h4>'.$v_block['title'].'</h4>';
                $this->html_out .= '<h5>';
                $lang_liste_news = unserialize($v_block['langues']);
                if (is_array($lang_liste_news) && count($lang_liste_news) > 0) {
                    foreach ($lang_liste_news as $val_langue) {
                        if ((count($languages) >= 1 && array_key_exists((int)$val_langue, $languages_shop))) {
                            $this->html_out .= '<img class="lang" src="../img/l/'.(int)$val_langue.'.jpg" />';
                        }
                    }
                } else {
                    $this->html_out .= $this->l('All languages');
                }
                $this->html_out .= '</h5>';

                if (count($news_liste) > 0) {
                    if ($v_block['random']) {
                        shuffle($news_liste);
                    }

                    foreach ($news_liste as $v_news) {
                        $this->html_out .= '<a class="dropdown-toggle notifs" data-toggle="dropdown" href="#">';
                        if (file_exists(self::imgUpPath().'/adminth_'.(int)$v_news['id_prestablog_news'].'.jpg')) {
                            $imgidreload = 'adminth_'.(int)$v_news['id_prestablog_news'].'.jpg?'.md5(time());
                            $this->html_out .= '
                                <img
                                    class="item"
                                    rel="'.(int)$v_news['id_prestablog_news'].'"
                                    src="'.self::imgPathBO().self::getT().'/up-img/'.$imgidreload.'"
                                />';
                        } else {
                            $this->html_out .= '
                                <div class="item" rel="'.(int)$v_news['id_prestablog_news'].'">
                                    <p>
                                        <i class="icon-eye-slash"></i><br/>#'.(int)$v_news['id_prestablog_news'].'
                                    </p>
                                </div>';
                        }
                        $this->html_out .= '
                            <div class="appear">
                                <h4>';
                        if (file_exists(self::imgUpPath().'/adminth_'.(int)$v_news['id_prestablog_news'].'.jpg')) {
                            $imgidreload = 'adminth_'.(int)$v_news['id_prestablog_news'].'.jpg?'.md5(time());
                            $this->html_out .= '
                                <img
                                    class="thumb"
                                    src="'.self::imgPathBO().self::getT().'/up-img/'.$imgidreload.'"
                                />';
                        }
                        $this->html_out .= $v_news['title'];
                        $this->html_out .= '</h4>';
                        $this->html_out .= '<div class="clearfix"></div>';
                        $total_read = 0;
                        $out_read = '';
                        foreach (Language::getLanguages() as $value) {
                            $cur_read = (int)NewsClass::getRead(
                                (int)$v_news['id_prestablog_news'],
                                (int)$value['id_lang']
                            );
                            $out_read .= '<img src="../img/l/'.(int)$value['id_lang'].'.jpg" /> '.$this->l('Read');
                            $out_read .= ' : <strong>'.$cur_read.'</strong> ';
                            $total_read += $cur_read;
                        }
                        $this->html_out .= '
                            <p class="rubrique">
                                '.$this->l('Total read').' : <strong>'.$total_read.'</strong>
                            </p>';
                        $this->html_out .= '
                            <p class="rubrique">
                                '.$this->l('Total read').' : <strong>'.$total_read.'</strong>
                            </p>';
                        $this->html_out .= '<p class="details">'.$out_read.'</p>';

                        $all_comments = count(CommentNewsClass::getListe(
                            -2,
                            (int)$v_news['id_prestablog_news']
                        ));
                        $all_comments_actives = count(CommentNewsClass::getListe(
                            1,
                            (int)$v_news['id_prestablog_news']
                        ));
                        $all_comments_pending = count(CommentNewsClass::getListe(
                            -1,
                            (int)$v_news['id_prestablog_news']
                        ));
                        $all_comments_inactives = count(CommentNewsClass::getListe(
                            0,
                            (int)$v_news['id_prestablog_news']
                        ));

                        $this->html_out .= '<p class="rubrique">'.$this->l('Total comments');
                        $this->html_out .= ' : <strong>'.$all_comments.'</strong></p>';
                        $this->html_out .= '<p class="details">'.$this->l('Activated');
                        $this->html_out .= ' : <strong>'.$all_comments_actives.'</strong></p>';
                        $this->html_out .= '<p class="details">'.$this->l('Pending');
                        $this->html_out .= ' : <strong>'.$all_comments_pending.'</strong></p>';
                        $this->html_out .= '<p class="details">'.$this->l('Desactivated');
                        $this->html_out .= ' : <strong>'.$all_comments_inactives.'</strong></p>';

                        $this->html_out .= '</div>';
                        $this->html_out .= '</a>';
                    }
                }

                $this->html_out .= '     </div>';
            }
        }
        $this->html_out .= '
            <a
                class="dashadd"
                href="'.$this->confpath.'&addSubBlock&preselecthook=displayModuleBoard"
                title="'.$this->l('Your custom list').'"
                style="display:none;"
            >
                <i class="icon-plus"></i><br/>
                '.sprintf($this->l('Custom%1$slist'), '<br/>').'
            </a>';
        $this->html_out .= ' </div>';
        $this->html_out .= '
            <script type="text/javascript">
                $(document).ready(function() {
                    $("#dashboard").mouseenter(function() {
                        $("a.dashadd", this).fadeIn(\'slow\');
                    }).mouseleave(function() {
                        $("a.dashadd", this).fadeOut();
                    });
                    $("a.notifs .item").click(function(){
                        location.href="'.$this->confpath.'&editNews&idN="+$(this).attr("rel");
                    });
                });
            </script>'."\n";
    }

   /* private function displayAuthorList()
    {
    	$id = $this->context->employee->id;
    	    $this->html_out .= '<table class="table_author" id="table_author" cellpadding="0" cellspacing="0" style="margin:auto;width:100%;">';
        $this->html_out .= ' <thead class="center">';
        $this->html_out .= '     <tr>';
        $this->html_out .= '         <th>Id</th>';
        $this->html_out .= '         <th>'.$this->l('Author').'</th>';
        $this->html_out .= '         <th>'.$this->l('Pseudo').'</th>';
        $this->html_out .= '         <th>'.$this->l('Date of creation').'</th>';
        $this->html_out .= '         <th>'.$this->l('Number of articles').'</th>';
        $this->html_out .= '         <th width="400px">'.$this->l('Most red article').'</th>';
        $this->html_out .= '         <th>'.$this->l('Total read').'</th>';
        $this->html_out .= '     </tr>';
        $this->html_out .= ' </thead>';
        $this->html_out .= '</table>';

    }
*/
    private function displayListeNews()
    {
        $languages_shop = array();
        foreach (Language::getLanguages() as $value) {
            $languages_shop[$value['id_lang']] = $value['iso_code'];
        }

        $nb_par_page = (int)Configuration::get($this->name.'_nb_news_pl');

        $tri_champ = 'n.`date`';
        $tri_ordre = 'desc';
        $languages = Language::getLanguages(true);

        if (Tools::getValue('c') && (int)Tools::getValue('c') > 0) {
            $categorie = (int)Tools::getValue('c');
            $this->confpath .= $this->confpath.'&c='.$categorie;
        } else {
            $categorie = null;
        }

        $count_liste = NewsClass::getCountListeAll(
            0,
            (int)$this->check_active,
            (int)$this->check_slide,
            null,
            null,
            $categorie,
            0
        );

        $liste = NewsClass::getListe(
            0,
            (int)$this->check_active,
            (int)$this->check_slide,
            (int)Tools::getValue('start'),
            $nb_par_page,
            $tri_champ,
            $tri_ordre,
            null,
            null,
            $categorie,
            0,
            (int)Configuration::get('prestablog_news_title_length'),
            (int)Configuration::get('prestablog_news_intro_length')
        );

        $pagination = self::getPagination(
            $count_liste,
            null,
            $nb_par_page,
            (int)Tools::getValue('start'),
            (int)Tools::getValue('p')
        );

        $categories = CategoriesClass::getListe((int)$this->context->language->id, 0);

        $this->html_out .= '
            <div class="blocmodule">
                <form method="post" action="'.$this->confpath.'&newsListe" enctype="multipart/form-data">
                    <fieldset>
                        <input type="hidden" name="submitFiltreNews" value="1" />
                        <div class="col-sm-3">
                            <a class="btn btn-primary" href="'.$this->confpath.'&addNews">
                                <i class="icon-plus"></i>
                                '.$this->l('Add a news').'
                            </a>
                        </div>
                        <div class="col-sm-2">

                           <input type="text" id="search_article" name="search_article" placeholder="'.$this->l('Search by articles').'" onkeyup="filter()"/>
                           <script type="text/javascript">
                    filter();
      function filter() {
          var search_article = $("#search_article").val();

          var filter_search_article, table, tr, td, i, td_search_article;
          filter_search_article = search_article.toLowerCase();
          table = document.getElementById("table_article");
          tr = table.getElementsByTagName("tr");

          for (i=0; i<tr.length;i++){
              td = tr[i].getElementsByTagName("td")[0];
              td_search_article = tr[i].getElementsByTagName("td") [4];

          if(td){
           if ( (td_search_article.innerHTML.toUpperCase().indexOf(filter_search_article.toUpperCase()) > -1))
           {
               tr[i].style.display = "";
           }else {
                      tr[i].style.display = "none";
                  }
                 }
                                    }
                     }
                    </script>
                       </div>
                        <div class="col-sm-2">
                            <img src="'.self::imgPathFO().'filter.png" />
                            '.$this->l('Filter list').' :
                        </div>'."\n";

        if (count($categories) > 0) {
            $categories = new CategoriesClass();
            $liste_categories = CategoriesClass::getListe((int)$this->context->language->id, 0);
            $this->html_out .= '<div class="col-sm-2">'."\n";
            $this->html_out .= $categories->displaySelectArboCategories(
                $liste_categories,
                0,
                0,
                $this->l('None'),
                'c',
                'form.submit();',
                (int)Tools::getValue('c')
            )."\n";
            $this->html_out .= '</div>'."\n";
        }
        $this->html_out .= '
            <div class="col-sm-2">
                <input
                    type="checkbox"
                    name="activeNews"
                    '.($this->check_active == 1 ? 'checked' : '').'
                    onchange="form.submit();"
                > '.$this->l('Active').'
            </div>'."\n";
        $this->html_out .= '
            <div class="col-sm-2">
                <input
                    type="checkbox"
                    name="slide"
                    '.($this->check_slide == 1 ? 'checked' : '').'
                    onchange="form.submit();"
                > '.$this->l('Slide').'
                ;
            </div>'."\n";


        $this->html_out .= '
                    </fieldset>
                </form>
            </div>';


        $this->html_out .= '<div class="blocmodule">';

        $this->html_out .= '<fieldset>';

        $this->html_out .= '
            <legend style="margin-bottom:10px;">'.$this->l('News').' :
                <span style="color: green;">'.($categorie ?
            sprintf(
                $this->l('%1$s currents items on %2$s'),
                $count_liste,
                CategoriesClass::getCategoriesName((int)$this->context->language->id, (int)$categorie)
            ) : sprintf($this->l('%1$s currents items'), $count_liste)).'
                </span>
            </legend>';

        $this->html_out .= '<table class="table_article" id="table_article" cellpadding="0" cellspacing="0" style="margin:auto;width:100%;">';
        $this->html_out .= ' <thead class="center">';
        $this->html_out .= '     <tr>';
        $this->html_out .= '         <th>Id</th>';
        $this->html_out .= '         <th>'.$this->l('Preview').'</th>';
        $this->html_out .= '         <th>'.$this->l('Date').'</th>';
        $this->html_out .= '         <th>'.$this->l('Image').'</th>';
        $this->html_out .= '         <th width="400px">'.$this->l('Title').'</th>';
        $this->html_out .= '         <th width="50px">'.$this->l('Read').'</th>';
        $this->html_out .= '         <th width="50px">'.$this->l('Rate/5').'</th>';
        $this->html_out .= '         <th>'.$this->l('Comments').'</th>';
        $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
        $this->html_out .= '         <th>'.$this->l('Popup').'</th>';
    }
        $this->html_out .= '         <th>'.$this->l('Products linked').'</th>';
        $this->html_out .= '         <th>'.$this->l('Slide').'</th>';
        $this->html_out .= '         <th>'.$this->l('Activate').'</th>';
        $this->html_out .= '         <th>'.$this->l('Actions').'</th>';
        $this->html_out .= '     </tr>';
        $this->html_out .= ' </thead>';
        if (count($liste) > 0) {
            foreach ($liste as $value) {
                $lang_liste_news = unserialize($value['langues']);

                $this->html_out .= ' <tr>';
                $this->html_out .= '     <td class="center">';
                $this->html_out .= $value['id_prestablog_news'];
                if (!empty($value['url_redirect']) && Validate::isAbsoluteUrl($value['url_redirect'])) {
                    $this->html_out .= '
                        <a
                            href="'.$value['url_redirect'].'"
                            target="_blank"
                            title="'.$this->l('Permanent redirect url').'"
                        >
                            <img src="'.self::imgPathFO().'rewrite.png" alt="'.$this->l('Permanent redirect url').'" />
                        </a>';
                }
                $this->html_out .= '     </td>';
                $this->html_out .= '     <td class="center">';
                foreach ($lang_liste_news as $val_langue) {
                    if (count($languages) >= 1 && array_key_exists((int)$val_langue, $languages_shop)) {
                        $news_tempo = new NewsClass((int)$value['id_prestablog_news']);
                        $this->html_out .= '
                            <img src="../img/l/'.(int)$val_langue.'.jpg" />
                            <a target="_blank" href="'.PrestaBlog::prestablogUrl(array(
                                'id' => (int)$news_tempo->id,
                                'seo' => $news_tempo->link_rewrite[(int)$val_langue],
                                'titre' => $news_tempo->title[(int)$val_langue],
                                'id_lang' => (int)$val_langue,
                            )).self::accurl().'preview='.$this->generateToken((int)$news_tempo->id).'">
                                <img src="'.self::imgPathFO().'preview.gif" />
                            </a>
                            <br />';
                    }
                }

                $this->html_out .= '     </td>';
                $this->html_out .= '     <td class="center">';
                if ((new DateTime($value['date'])) > (new DateTime())) {
                    $this->html_out .= '
                        <img
                            src="'.self::imgPathFO().'postdate.gif"
                            alt="'.$this->l('Post Date').'"
                        />';
                }
                $this->html_out .= ToolsCore::displayDate($value['date'], null, true);
                $this->html_out .= '    </td>';

                if (file_exists(self::imgUpPath().'/adminth_'.$value['id_prestablog_news'].'.jpg')) {
                    $imgidreload = 'adminth_'.$value['id_prestablog_news'].'.jpg?'.md5(time());
                    $this->html_out .= '
                        <td class="center">
                            <img src="'.self::imgPathBO().self::getT().'/up-img/'.$imgidreload.'" />
                        </td>';
                } else {
                    $this->html_out .= '     <td class="center">-</td>';
                }

                $this->html_out .= '     <td>';
                foreach ($lang_liste_news as $val_langue) {
                    if (count($languages) >= 1 && array_key_exists((int)$val_langue, $languages_shop)) {
                        $this->html_out .= '<img src="../img/l/'.(int)$val_langue.'.jpg" />';
                        $this->html_out .= ' '.NewsClass::getTitleNews(
                            (int)$value['id_prestablog_news'],
                            (int)$val_langue
                        ).'<br/>';
                    }
                }
                $this->html_out .= '     </td>';

                $this->html_out .= '     <td>';
                foreach ($lang_liste_news as $val_langue) {
                    if (count($languages) >= 1 && array_key_exists((int)$val_langue, $languages_shop)) {
                        $this->html_out .= '<img src="../img/l/'.(int)$val_langue.'.jpg" />';
                        $this->html_out .= ' '.NewsClass::getRead(
                            (int)$value['id_prestablog_news'],
                            (int)$val_langue
                        ).'<br/>';
                    }
                }
                $this->html_out .= '     </td>';
$this->html_out .= '     <td class="center">';
$rate = NewsClass::getRate((int)$value['id_prestablog_news']);
                $this->html_out .= $rate[0]['average_rating'].' ('.$rate[0]['number_rating'].')';
                $this->html_out .= '    </td>';

                $this->html_out .= '     <td class="center">';
                $comments_actif = CommentNewsClass::getListe(1, (int)$value['id_prestablog_news']);
                $comments_all = CommentNewsClass::getListe(-2, (int)$value['id_prestablog_news']);

                if (count($comments_all) > 0) {
                    $this->html_out .= count($comments_actif).' '.$this->l('of').' ';
                    $this->html_out .= count($comments_all).' '.$this->l('active');
                } else {
                    $this->html_out .= '-';
                }

                $this->html_out .= '     </td>';
                $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
                $this->html_out .= '     <td class="center">';
                $popuplink = NewsClass::getPopupLink($value['id_prestablog_news']);

                if (isset($popuplink)) {
                    $this->html_out .= 'Oui';
                } else {
                    $this->html_out .= 'Non';
                }

                $this->html_out .= '     </td>';
            }
                $this->html_out .= '     <td class="center">';

                $products_link = NewsClass::getProductLinkListe((int)$value['id_prestablog_news']);

                $this->html_out .= (count($products_link) > 0 ? count($products_link) : '-');

                $this->html_out .= '     </td>';

                $this->html_out .= '
                    <td class="center">
                        <a href="'.$this->confpath.'&slideNews&idN='.$value['id_prestablog_news'].'">';
                if ($value['slide']) {
                    $this->html_out .= '<img src="'.self::imgPathFO().'enabled.gif" />';
                } else {
                    $this->html_out .= '<img src="'.self::imgPathFO().'disabled.gif" />';
                }

                $this->html_out .= '
                        </a>
                    </td>';

                $this->html_out .= '
                    <td class="center">
                        <a href="'.$this->confpath.'&etatNews&idN='.$value['id_prestablog_news'].'">';
                if ($value['actif']) {
                    $this->html_out .= '<img src="'.self::imgPathFO().'enabled.gif" />';
                } else {
                    $this->html_out .= '<img src="'.self::imgPathFO().'disabled.gif" />';
                }

                $this->html_out .= '
                        </a>
                    </td>';

                $this->html_out .= '
                    <td class="center">
                        <a
                            href="'.$this->confpath.'&editNews&idN='.$value['id_prestablog_news'].'"
                            title="'.$this->l('Edit').'"
                        >
                            <img src="'.self::imgPathFO().'edit.gif" />
                        </a>
                        <a
                            href="'.$this->confpath.'&deleteNews&idN='.$value['id_prestablog_news'].'"
                            onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                        >
                            <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                        </a>
                    </td>';
                $this->html_out .= ' </tr>';
            }

            $page_type = 'newsListe';

            if ((int)$pagination['NombreTotalPages'] > 1) {
                $this->html_out .= '<tfooter>';
                $this->html_out .= ' <tr>';
                $this->html_out .= ' <td colspan="6">';
                $this->html_out .= '<div class="prestablog_pagination">'."\n";
                if ((int)$pagination['PageCourante'] > 1) {
                    $pageload = $page_type.'&start='.$pagination['StartPrecedent'].'&p='.$pagination['PagePrecedente'];
                    $this->html_out .= '<a href="'.$this->confpath.'&'.$pageload.'">&lt;&lt;</a>'."\n";
                } else {
                    $this->html_out .= '<span class="disabled">&lt;&lt;</span>'."\n";
                }

                if ($pagination['PremieresPages']) {
                    foreach ($pagination['PremieresPages'] as $key_page => $value_page) {
                        if (((int)Tools::getValue('p') == $key_page)
                            || ((Tools::getValue('p') == '')
                            && $key_page == 1
                        )) {
                            $this->html_out .= '<span class="current">'.$key_page.'</span>'."\n";
                        } else {
                            if ($key_page == 1) {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$page_type.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            } else {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$page_type.'&start='.$value_page.'&p='.$key_page.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            }
                        }
                    }
                }
                if (isset($pagination['Pages']) && $pagination['Pages']) {
                    $this->html_out .= '<span class="more">...</span>'."\n";

                    foreach ($pagination['Pages'] as $key_page => $value_page) {
                        if (!in_array($value_page, $pagination['PremieresPages'])) {
                            if (((int)Tools::getValue('p') == $key_page)
                                || ((Tools::getValue('p') == '')
                                && $key_page == 1
                            )) {
                                $this->html_out .= '<span class="current">'.$key_page.'</span>'."\n";
                            } else {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$page_type.'&start='.$value_page.'&p='.$key_page.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            }
                        }
                    }
                }
                if ($pagination['PageCourante'] < $pagination['NombreTotalPages']) {
                    $pageload = $page_type.'&start='.$pagination['StartSuivant'].'&p='.$pagination['PageSuivante'];
                    $this->html_out .= '<a href="'.$this->confpath.'&'.$pageload.'">&gt;&gt;</a>'."\n";
                } else {
                    $this->html_out .= '<span class="disabled">&gt;&gt;</span>'."\n";
                }

                $this->html_out .= '</div>'."\n";
                $this->html_out .= ' </td>';
                $this->html_out .= ' </tr>';
                $this->html_out .= '</tfooter>';
            }
        } else {
            $this->html_out .= '<tr><td colspan="8" class="center">'.$this->l('No content registered').'</td></tr>';
        }

        $this->html_out .= '</table>';
        $this->html_out .= '</fieldset>';

        $this->html_out .= '</div>';
    }

    private function displayListeComments()
    {
        $nb_par_page = (int)Configuration::get($this->name.'_nb_comments_pl');

        if (Tools::getValue('n') && (int)Tools::getValue('n') > 0) {
            $news = (int)Tools::getValue('n');
            $this->confpath .= $this->confpath.'&n='.$news;
        } else {
            $news = null;
        }

        $count_liste = CommentNewsClass::getCountListeAll($this->check_comment_state, $news);

        $liste = CommentNewsClass::getListeNavigate(
            $this->check_comment_state,
            (int)Tools::getValue('start'),
            $nb_par_page
        );

        $pagination = self::getPagination(
            $count_liste,
            null,
            $nb_par_page,
            (int)Tools::getValue('start'),
            (int)Tools::getValue('p')
        );

        $this->html_out .= '
            <div class="blocmodule">
                <form method="post" action="'.$this->confpath.'&commentListe" enctype="multipart/form-data">
                    <fieldset>
                        <input type="hidden" name="submitFiltreComment" value="1" />
                        <div class="col-sm-2">
                            <img src="'.self::imgPathFO().'filter.png" />
                            '.$this->l('Filter list').' :
                        </div>
                        <div class="col-sm-2">
                            <input
                                type="radio"
                                name="activeComment"
                                '.($this->check_comment_state == -2 ? 'checked' : '').'
                                onchange="form.submit();"
                                value="-2"
                            >
                            <img src="'.self::imgPathFO().'refresh.png" /> '.$this->l('All').'
                        </div>
                        <div class="col-sm-2">
                            <input
                                type="radio"
                                name="activeComment"
                                '.($this->check_comment_state == -1 ? 'checked' : '').'
                                onchange="form.submit();"
                                value="-1"
                            >
                            <img src="'.self::imgPathFO().'question.gif" /> '.$this->l('Pending').'
                        </div>
                        <div class="col-sm-2">
                            <input
                                type="radio"
                                name="activeComment"
                                '.($this->check_comment_state == 1 ? 'checked' : '').'
                                onchange="form.submit();"
                                value="1"
                            >
                            <img src="'.self::imgPathFO().'enabled.gif" /> '.$this->l('Enabled').'
                        </div>
                        <div class="col-sm-2">
                            <input
                                type="radio"
                                name="activeComment"
                                '.(is_numeric($this->check_comment_state)
                                    && ($this->check_comment_state == 0) ? 'checked' : '').'
                                onchange="form.submit();"
                                value="0"
                            >
                            <img src="'.self::imgPathFO().'disabled.gif" /> '.$this->l('Disabled').'
                        </div>
                        <div class="col-sm-2">
                        <div>
                    <input type="text" id="search_news" name="search_news" placeholder="'.$this->l('Search by articles').'" onkeyup="filter()"/>
                    </div>
                    <div>
                    <input type="text" id="search_comment" name="search_comment" placeholder="'.$this->l('Search by comments').'" onkeyup="filter()"/>
                    </div>
                    <script type="text/javascript">
                    filter();
      function filter() {
          var search_news = $("#search_news").val();
          var search_comment = $("#search_comment").val();

          var filter_search_news, filter_search_comment, table, tr, td, i, td_search_news, td_search_comment;
          filter_search_news = search_news.toLowerCase();
          filter_search_comment = search_comment.toLowerCase();
          table = document.getElementById("table_comment");
          tr = table.getElementsByTagName("tr");

          for (i=0; i<tr.length;i++){
              td = tr[i].getElementsByTagName("td")[0];
              td_search_news = tr[i].getElementsByTagName("td") [2];
              td_search_comment = tr[i].getElementsByTagName("td") [3];

          if(td){
           if ( (td_search_news.innerHTML.toLowerCase().indexOf(filter_search_news.toLowerCase()) > -1) && (td_search_comment.innerHTML.toLowerCase().indexOf(filter_search_comment.toLowerCase()) > -1))
           {
               tr[i].style.display = "";
           }else {
                      tr[i].style.display = "none";
                  }
                 }
                                    }
                     }
                    </script>
                        </div>
                    </fieldset>
                </form>
            </div>';

        $this->html_out .= '<div class="blocmodule">';

        $this->html_out .= '<fieldset>';
        $this->html_out .= '<legend style="margin-bottom:10px;">'.$this->l('Comments').' :</legend>';
        $this->html_out .= '<table class="table" id="table_comment" cellpadding="0" cellspacing="0" style="margin:auto;width:100%;">';
        $this->html_out .= ' <thead class="center">';
        $this->html_out .= '     <tr>';
        $this->html_out .= '         <th>Id</th>';
        $this->html_out .= '         <th>'.$this->l('Date').'</th>';
        $this->html_out .= '         <th>'.$this->l('News').'</th>';
        $this->html_out .= '         <th>'.$this->l('Name').'</th>';
        $this->html_out .= '         <th>'.$this->l('Url').'</th>';
        $this->html_out .= '         <th>'.$this->l('Comment').'</th>';
        $this->html_out .= '         <th class="center" style="width:70px;">'.$this->l('Status').'</th>';
        $this->html_out .= '         <th class="center">'.$this->l('Actions').'</th>';
        $this->html_out .= '     </tr>';
        $this->html_out .= ' </thead>';

        if (count($liste) > 0) {
            foreach ($liste as $value) {
                $this->html_out .= ' <tr>';
                $this->html_out .= '     <td class="center">'.($value['id_prestablog_commentnews']).'</td>';
                $this->html_out .= '
                    <td class="center">
                        '.ToolsCore::displayDate($value['date'], null, true).'
                    </td>';
                $title_news = NewsClass::getTitleNews((int)$value['news'], (int)$this->context->language->id);

                $this->html_out .= '
                    <td>
                        <a href="'.$this->confpath.'&editNews&idN='.$value['news'].'">
                            '.self::cleanCut($title_news, 40, '...').'
                        </a>
                    </td>';

                $this->html_out .= '     <td>'.$value['name'].'</td>';
                $this->html_out .= '     <td><a href="'.$value['url'].'" target="_blank">'.$value['url'].'</a></td>';
                $this->html_out .= '     <td><small>'.self::cleanCut($value['comment'], 120, '...').'</small></td>';
                $urlcmtliste = $this->confpath.'&commentListe&idC='.$value['id_prestablog_commentnews'];
                $this->html_out .= '
                    <td class="status">
                        <a class="enabled"
                            href="'.$urlcmtliste.'&enabledComment"
                            '.((int)$value['actif'] != 1 ? 'style="display:none;"' : 'rel="1"').'
                        >
                            <img class="imgrfsh enabled" src="'.self::imgPathFO().'enabled.gif" title="'.$this->l('Approuved').'" />
                        </a>
                        <a class="disabled"
                            href="'.$urlcmtliste.'&disabledComment"
                            '.((int)$value['actif'] != 0 ? 'style="display:none;"' : 'rel="1"').'
                        >
                            <img class="imgrfsh disabled"  src="'.self::imgPathFO().'disabled.gif" title="'.$this->l('Disabled').'" />
                        </a>
                        <a class="question"
                            href="'.$urlcmtliste.'&pendingComment"
                            '.((int)$value['actif'] != -1 ? 'style="display:none;"' : 'rel="1"').'>

                            <img class="imgrfsh" src="'.self::imgPathFO().'question.gif"'.$this->l('Pending').'" />
                        </a>
                    </td>
         <script language="javascript" type="text/javascript">
                        $(document).ready(function() {
                            $("td.status").mouseenter(function() {
                                $(this).find("a").fadeIn();
                            }).mouseleave(function() {
                                $(this).find("a").each(function() {
                                    if ($(this).attr(\'rel\') != 1) {
                                        $(this).fadeOut();
                                    }
                                });
                            });
                        });
                    </script>';


                $this->html_out .= '
<form method="post" action="'.$this->confpath.'&deleteAllComment&idC='.$value['id_prestablog_commentnews'].'">
                    <td class="center">
                        <a
                            href="'.$this->confpath.'&editComment&idC='.$value['id_prestablog_commentnews'].'"
                            title="'.$this->l('Edit').'"
                        >
                            <img src="'.self::imgPathFO().'edit.gif" />
                        </a>
                        <a
                            href="'.$this->confpath.'&deleteComment&idC='.$value['id_prestablog_commentnews'].'"
                            onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                        >
                            <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                        </a>
                        <input type="checkbox" name="AllidCToDelete[]" value="'.$value['id_prestablog_commentnews'].'" style="vertical-align: middle;margin-left: 2px;" />


                    </td>
                    ';
                $this->html_out .= ' </tr>';
            }

            $this->html_out .= '
                      <div class="col-sm-2" style="float:right;">
                                <input type="submit" id="deleteAllComment" name="deleteAllComment" value="'.$this->l('Delete checked comments').'" onclick="return confirm(\''.$this->l('Are you sure?').'\');" style="float:right;"/>
                      </div>

                </form>';


            if ((int)$pagination['NombreTotalPages'] > 1) {
                $this->html_out .= '<tfooter>';
                $this->html_out .= ' <tr>';
                $this->html_out .= ' <td colspan="6">';
                $this->html_out .= '<div class="prestablog_pagination">'."\n";
                if ((int)$pagination['PageCourante'] > 1) {
                    $pageload = $page_type.'&start='.$pagination['StartPrecedent'].'&p='.$pagination['PagePrecedente'];
                    $this->html_out .= '<a href="'.$this->confpath.'&'.$pageload.'">&lt;&lt;</a>'."\n";
                } else {
                    $this->html_out .= '<span class="disabled">&lt;&lt;</span>'."\n";
                }

                if ($pagination['PremieresPages']) {
                    foreach ($pagination['PremieresPages'] as $key_page => $value_page) {
                        if (((int)Tools::getValue('p') == $key_page)
                            || ((Tools::getValue('p') == '')
                            && $key_page == 1
                        )) {
                            $this->html_out .= '<span class="current">'.$key_page.'</span>'."\n";
                        } else {
                            if ($key_page == 1) {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            } else {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&start='.$value_page.'&p='.$key_page.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            }
                        }
                    }
                }
                if (isset($pagination['Pages']) && $pagination['Pages']) {
                    $this->html_out .= '<span class="more">...</span>'."\n";

                    foreach ($pagination['Pages'] as $key_page => $value_page) {
                        if (!in_array($value_page, $pagination['PremieresPages'])) {
                            if (((int)Tools::getValue('p') == $key_page)
                                || ((Tools::getValue('p') == '')
                                && $key_page == 1
                            )) {
                                $this->html_out .= '<span class="current">'.$key_page.'</span>'."\n";
                            } else {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$page_type.'&start='.$value_page.'&p='.$key_page.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            }
                        }
                    }
                }
                if ($pagination['PageCourante'] < $pagination['NombreTotalPages']) {
                    $pageload = '&start='.$pagination['StartSuivant'].'&p='.$pagination['PageSuivante'];
                    $this->html_out .= '<a href="'.$this->confpath.'&'.$pageload.'">&gt;&gt;</a>'."\n";
                } else {
                    $this->html_out .= '<span class="disabled">&gt;&gt;</span>'."\n";
                }

                $this->html_out .= '</div>'."\n";
                $this->html_out .= ' </td>';
                $this->html_out .= ' </tr>';
                $this->html_out .= '</tfooter>';
            }
        } else {
            $this->html_out .= '<tr><td colspan="8" class="center">'.$this->l('No content registered').'</td></tr>';
        }

        $this->html_out .= '</table>';
        $this->html_out .= '</fieldset>';
        $this->html_out .= '</div>';
    }

    private function displayListeLookBook()
    {
        $languages_shop = array();
        foreach (Language::getLanguages() as $value) {
            $languages_shop[$value['id_lang']] = $value['iso_code'];
        }

        //$nb_par_page = (int)Configuration::get($this->name.'_nb_lb_pl');
        $nb_par_page = 20;

        // $tri_champ = 'n.`date`';
        // $tri_ordre = 'desc';
        // $languages = Language::getLanguages(true);

        $liste = LookBookClass::getListe((int)$this->context->language->id, 0);

        $count_liste = count($liste);

        $pagination = self::getPagination(
            $count_liste,
            null,
            $nb_par_page,
            (int)Tools::getValue('start'),
            (int)Tools::getValue('p')
        );

        $this->html_out .= '
            <div class="blocmodule">
                <form method="post" action="'.$this->confpath.'&lookbookListe" enctype="multipart/form-data">
                    <fieldset>
                        <div class="col-sm-3">
                            <a class="btn btn-primary" href="'.$this->confpath.'&addLookbook">
                                <i class="icon-plus"></i>
                                '.$this->l('Add a lookbook').'
                            </a>
                        </div>'."\n";

        $this->html_out .= '
                </fieldset>
            </form>
        </div>';

        $this->html_out .= '<div class="blocmodule">';

        $this->html_out .= '<fieldset>';

        $this->html_out .= '
            <legend style="margin-bottom:10px;">
                '.$this->l('Lookbook').' : '.(int)$count_liste.'
            </legend>';
        $this->html_out .= '<table class="table" cellpadding="0" cellspacing="0" style="margin:auto;width:100%;">';
        $this->html_out .= ' <thead class="center">';
        $this->html_out .= '     <tr>';
        $this->html_out .= '         <th>Id</th>';
        $this->html_out .= '         <th>'.$this->l('Image').'</th>';
        $this->html_out .= '         <th width="400px">'.$this->l('Title').'</th>';
        $this->html_out .= '         <th>'.$this->l('Products linked').'</th>';
        $this->html_out .= '         <th>'.$this->l('Activate').'</th>';
        $this->html_out .= '         <th>'.$this->l('Actions').'</th>';
        $this->html_out .= '     </tr>';
        $this->html_out .= ' </thead>';

        if ((int)$count_liste > 0) {
            foreach ($liste as $value) {
                $this->html_out .= ' <tr>';

                $this->html_out .= '     <td class="center">';
                $this->html_out .= (int)$value['id_prestablog_lookbook'];
                $this->html_out .= '     </td>';

                if (file_exists(self::imgUpPath().'/lookbook/adminth_'.$value['id_prestablog_lookbook'].'.jpg')) {
                    $imgidreload = 'adminth_'.(int)$value['id_prestablog_lookbook'].'.jpg?'.md5(time());
                    $this->html_out .= '
                        <td class="center">
                            <img src="'.self::imgPathBO().self::getT().'/up-img/lookbook/'.$imgidreload.'" />
                        </td>';
                } else {
                    $this->html_out .= '     <td class="center">-</td>';
                }

                $this->html_out .= '     <td>'.$value['title'].'</td>';

                $this->html_out .= '     <td class="center">';
                $lookbook_products = LookBookClass::getLookBookProducts((int)$value['id_prestablog_lookbook']);
                $this->html_out .= (count($lookbook_products) > 0 ? count($lookbook_products) : '-');
                $this->html_out .= '     </td>';

                $this->html_out .= '
                    <td class="center">
                        <a href="'.$this->confpath.'&etatLookbook&idLB='.$value['id_prestablog_lookbook'].'">
                        '.($value['actif'] ? '<img src="'.self::imgPathFO().'enabled.gif" />'
                            : '<img src="'.self::imgPathFO().'disabled.gif" />').'
                        </a>
                    </td>';

                $this->html_out .= '
                    <td class="center">
                        <a
                            href="'.$this->confpath.'&editLookBook&idLB='.$value['id_prestablog_lookbook'].'"
                            title="'.$this->l('Edit').'"
                        >
                            <img src="'.self::imgPathFO().'edit.gif" />
                        </a>
                        <a
                            href="'.$this->confpath.'&deleteLookBook&idLB='.$value['id_prestablog_lookbook'].'"
                            onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                        >
                            <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                        </a>
                    </td>';

                $this->html_out .= ' </tr>';
            }
            $page_type = 'lookbookListe';

            if ((int)$pagination['NombreTotalPages'] > 1) {
                $this->html_out .= '<tfooter>';
                $this->html_out .= ' <tr>';
                $this->html_out .= ' <td colspan="6">';
                $this->html_out .= '<div class="prestablog_pagination">'."\n";
                if ((int)$pagination['PageCourante'] > 1) {
                    $pageload = $page_type.'&start='.$pagination['StartPrecedent'].'&p='.$pagination['PagePrecedente'];
                    $this->html_out .= '<a href="'.$this->confpath.'&'.$pageload.'">&lt;&lt;</a>'."\n";
                } else {
                    $this->html_out .= '<span class="disabled">&lt;&lt;</span>'."\n";
                }

                if ($pagination['PremieresPages']) {
                    foreach ($pagination['PremieresPages'] as $key_page => $value_page) {
                        if (((int)Tools::getValue('p') == $key_page)
                            || ((Tools::getValue('p') == '')
                            && $key_page == 1
                        )) {
                            $this->html_out .= '<span class="current">'.$key_page.'</span>'."\n";
                        } else {
                            if ($key_page == 1) {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$page_type.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            } else {
                                $pageload = $page_type.'&start='.$value_page.'&p='.$key_page;
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$pageload.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            }
                        }
                    }
                }
                if (isset($pagination['Pages']) && $pagination['Pages']) {
                    $this->html_out .= '<span class="more">...</span>'."\n";

                    foreach ($pagination['Pages'] as $key_page => $value_page) {
                        if (!in_array($value_page, $pagination['PremieresPages'])) {
                            if (((int)Tools::getValue('p') == $key_page)
                                || ((Tools::getValue('p') == '')
                                && $key_page == 1
                            )) {
                                $this->html_out .= '<span class="current">'.$key_page.'</span>'."\n";
                            } else {
                                $this->html_out .= '
                                    <a href="'.$this->confpath.'&'.$page_type.'&start='.$value_page.'&p='.$key_page.'">
                                        '.$key_page.'
                                    </a>'."\n";
                            }
                        }
                    }
                }
                if ($pagination['PageCourante'] < $pagination['NombreTotalPages']) {
                    $pageload = $page_type.'&start='.$pagination['StartSuivant'].'&p='.$pagination['PageSuivante'];
                    $this->html_out .= '<a href="'.$this->confpath.'&'.$pageload.'">&gt;&gt;</a>'."\n";
                } else {
                    $this->html_out .= '<span class="disabled">&gt;&gt;</span>'."\n";
                }

                $this->html_out .= '</div>'."\n";
                $this->html_out .= ' </td>';
                $this->html_out .= ' </tr>';
                $this->html_out .= '</tfooter>';
            }
        } else {
            $this->html_out .= '<tr><td colspan="8" class="center">'.$this->l('No content registered').'</td></tr>';
        }

        $this->html_out .= '</table>';
        $this->html_out .= '</fieldset>';

        $this->html_out .= '</div>';
    }

    private function displayListeSubBlocks()
    {
        $languages = Language::getLanguages(true);

        $languages_shop = array();
        foreach (Language::getLanguages() as $value) {
            $languages_shop[$value['id_lang']] = $value['iso_code'];
        }

       $this->html_out .= '<div class="bob" style="display:none;">';
        $this->html_out .= $this->displayFormOpen('icon-shield', $this->l('Customize articles list'), $this->confpath);
        $this->html_out .= $this->displayWarning('<p>'.$this->l('Advanced user only').'</p>');
        $this->html_out .= $this->displayInfo(
            '<p>
                '.$this->l('Insert your own lists of articles in your own hook.').'
                '.$this->l('These blocks will appear in the order you chose, for each hook.').'
            </p>'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-2',
            $this->l('News list general activation'),
            $this->name.'_subblocks_actif'
        );
        $this->html_out .= $this->displayFormSubmit(
            'submitSubBlocksConfig',
            'icon-save',
            $this->l('Update configuration')
        );
        $this->html_out .= $this->displayFormClose();
$this->html_out .= '</div>';

        $this->html_out .= '
            <div class="blocmodule">
                <fieldset>
                    <div class="col-sm-3">
                        <a class="btn btn-primary" href="'.$this->confpath.'&addSubBlock">
                            <i class="icon-plus"></i>
                            '.$this->l('Create a list').'
                        </a>
                    </div>
                </fieldset>
            </div>';

        $liste_hook = SubBlocksClass::getHookListe((int)$this->context->language->id, 0);

        if (count($liste_hook) > 0) {
            $this->html_out .= '
                <script src="'.self::httpS().'://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
                <script
                    type="text/javascript"
                    src="'.__PS_BASE_URI__.'modules/prestablog/views/js/jquery.mjs.nestedSortable.js"
                ></script>';

            foreach ($liste_hook as $hook_name) {
                $this->html_out .= '<div class="blocmodule">';

                $liste = SubBlocksClass::getListe(null, 0, $hook_name);

                $this->html_out .= '<fieldset>';
                $this->html_out .= '<legend style="margin-bottom:10px;">'.$this->l('Articles list for ').'
                <span class="label label-success">'.$hook_name.'</span></legend>';
                $this->html_out .= '
                    <table
                        class="table"
                        cellpadding="0"
                        cellspacing="0"
                        style="margin:auto;width:100%;"
                    >';
                $this->html_out .= ' <thead class="center">';
                $this->html_out .= '     <tr>';
                $this->html_out .= '         <th>Id</th>';

                if ($hook_name == 'displayCustomHook') {
                    $this->html_out .= '         <th>'.$this->l('Add this shortcode directly in your tpl').'</th>';
                } else {
                    $this->html_out .= '         <th>'.$this->l('Positions').'</th>';
                }

                $this->html_out .= '         <th>'.$this->l('Type').'</th>';
                $this->html_out .= '         <th>'.$this->l('Languages').'</th>';
                $this->html_out .= '         <th>'.$this->l('Categories').'</th>';
                $this->html_out .= '         <th>'.$this->l('Limit list').'</th>';
                $this->html_out .= '         <th>'.$this->l('Custom template').'</th>';
                $this->html_out .= '         <th class="center">'.$this->l('Random').'</th>';
                $this->html_out .= '         <th class="center">'.$this->l('Blog link').'</th>';
                $this->html_out .= '         <th class="center">'.$this->l('Activate').'</th>';
                $this->html_out .= '         <th class="center">'.$this->l('Actions').'</th>';
                $this->html_out .= '     </tr>';
                $this->html_out .= ' </thead>';

                if (count($liste) > 0) {
                    $sub_blocks = new SubBlocksClass();
                    if ($hook_name == 'displayCustomHook') {
                        $this->html_out .= ' <tbody>';
                    } else {
                        $this->html_out .= ' <tbody id="subblocks_positions_'.$liste[0]['hook_name'].'">';
                    }

                    foreach ($liste as $value) {
                        if ($hook_name == 'displayCustomHook') {
                            $this->html_out .= ' <tr>';
                        } else {
                            $this->html_out .= ' <tr class="odd" order-id="'.(int)$value['id_prestablog_subblock'].'">';
                        }

                        $this->html_out .= '     <td class="center">'.(int)$value['id_prestablog_subblock'].'</td>';

                        if ($hook_name == 'displayCustomHook') {
                            $idsb = (int)$value['id_prestablog_subblock'];
                            $this->html_out .= '
                                <td>
                                    <strong>
                                        {hook h=\'displayPrestaBlogList\' id=\''.$idsb.'\' mod=\'prestablog\'}
                                    </strong>
                                </td>';
                        } else {
                            $this->html_out .= '
                                <td class="center pointer" style="text-align:center;">
                                    <img src="'.self::imgPathFO().'move.png" />
                                </td>';
                        }

                        $select_type = $sub_blocks->getListeSelectType();
                        $this->html_out .= '     <td >'.$select_type[(int)$value['select_type']].'</td>';

                        $this->html_out .= '     <td>';
                        $lang_liste_news = unserialize($value['langues']);
                        if (is_array($lang_liste_news) && count($lang_liste_news) > 0) {
                            foreach ($lang_liste_news as $val_langue) {
                                if (count($languages) >= 1 && array_key_exists((int)$val_langue, $languages_shop)) {
                                    $gettsb = SubBlocksClass::getTitleSubBlock(
                                        (int)$value['id_prestablog_subblock'],
                                        (int)$val_langue
                                    );
                                    $this->html_out .= '<img src="../img/l/'.(int)$val_langue.'.jpg" /> '.$gettsb;
                                    $this->html_out .= '<br/>';
                                }
                            }
                        } else {
                            $this->html_out .= '-';
                        }
                        $this->html_out .= '     </td>';

                        $this->html_out .= '     <td >';
                        $cat_verbose = '';

                        if (is_array($value['blog_categories']) && count($value['blog_categories']) > 1) {
                            foreach ($value['blog_categories'] as $id_category) {
                                $category = new CategoriesClass(
                                    (int)$id_category,
                                    (int)$this->context->cookie->id_lang
                                );
                                $cat_verbose .= $category->title.', ';
                            }
                        } elseif (is_int($value['blog_categories'])) {
                            $category = new CategoriesClass(
                                (int)$value['blog_categories'],
                                (int)$this->context->cookie->id_lang
                            );
                            $cat_verbose .= $category->title;
                        } else {
                            $cat_verbose = '-';
                        }

                        $cat_verbose = rtrim(trim($cat_verbose), ',');
                        $this->html_out .= $cat_verbose;
                        $this->html_out .= '     </td>';
                        $this->html_out .= '     <td class="center">'.(int)$value['nb_list'].'</td>';
                        $this->html_out .= '
                            <td class="center">
                                '.($value['template'] != '' ? $value['template'] : '-').'
                            </td>';
                        $imgenable = '<img src="'.self::imgPathFO().'enabled.gif" />';
                        $imgdisable = '<img src="'.self::imgPathFO().'disabled.gif" />';
                        $id_sb = $value['id_prestablog_subblock'];
                        $this->html_out .= '
                            <td class="center">
                                <a href="'.$this->confpath.'&randSubBlock&idSB='.$id_sb.'">
                                '.($value['random'] ? $imgenable : $imgdisable).'
                                </a>
                            </td>';
                        $this->html_out .= '
                            <td class="center">
                                <a href="'.$this->confpath.'&blog_linkSubBlock&idSB='.$id_sb.'">
                                '.($value['blog_link'] ? $imgenable : $imgdisable).'
                                </a>
                            </td>';
                        $this->html_out .= '
                            <td class="center">
                                <a href="'.$this->confpath.'&etatSubBlock&idSB='.$id_sb.'">
                                '.($value['actif'] ? $imgenable : $imgdisable).'
                                </a>
                            </td>';
                        $this->html_out .= '
                            <td class="center">
                                <a
                                    href="'.$this->confpath.'&editSubBlock&idSB='.$id_sb.'"
                                    title="'.$this->l('Edit').'"
                                >
                                    <img src="'.self::imgPathFO().'edit.gif" />
                                </a>';
                        $this->html_out .= '
                            <a
                                href="'.$this->confpath.'&deleteSubBlock&idSB='.$id_sb.'"
                                onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                            >
                                <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                            </a>';
                        $this->html_out .= '     </td>';
                        $this->html_out .= ' </tr>';
                    }
                    $this->html_out .= ' </tbody>';
                    $this->html_out .= '
                        <script type="text/javascript">
                            $(function() {
                                $("#subblocks_positions_'.$value['hook_name'].'").sortable({
                                        axis: \'y\',
                                        placeholder: "ui-state-highlight",
                                        update: function(event, ui) {
                                            $.ajax({
                                                url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                                                type: "GET",
                                                data: {
                                                    action: \'prestablogrun\',
                                                    items: $(this).sortable(\'toArray\', { attribute: \'order-id\' }),
                                                    ajax: true,
                                                    do: \'sortSubBlocks\',
                                                    id_shop: \''.$this->context->shop->id.'\',
                                                    hook_name: \''.$value['hook_name'].'\'
                                                },
                                                success:function(data){}
                                            });
                                        }
                                }).disableSelection();
                            });
                        </script>';
                } else {
                    $this->html_out .= '
                        <tr>
                            <td colspan="5" class="center">'.$this->l('No content registered').'</td>
                        </tr>';
                }

                $this->html_out .= '</table>';
                $this->html_out .= '</fieldset>';
                $this->html_out .= '</div>';
            }
        }
    }

    private function displayListeCategories()
    {
        $liste = CategoriesClass::getListe((int)$this->context->language->id, 0);

        $this->html_out .= '
            <div class="blocmodule">
                <fieldset class="row">
                    <div class="col-sm-3">
                        <a class="btn btn-primary" href="'.$this->confpath.'&addCat">
                            <i class="icon-plus"></i>
                            '.$this->l('Add a category').'
                        </a>
                    </div>
                    <div class="col-sm-3">
                        <a class="btn btn-primary" href="'.$this->confpath.'&orderCat">
                            <i class="icon-sort-numeric-asc"></i>
                            '.$this->l('Order of categories').'
                        </a>
                    </div>
                </fieldset>
            </div>';

        $this->html_out .= '<div class="blocmodule">';

        $this->html_out .= '<fieldset>';
        $this->html_out .= '<legend style="margin-bottom:10px;">'.$this->l('Categories').'</legend>';
        $this->html_out .= '<table class="table" cellpadding="0" cellspacing="0" style="margin:auto;width:100%;">';
        $this->html_out .= ' <thead class="center">';
        $this->html_out .= '     <tr>';
        $this->html_out .= '         <th>Id</th>';
        $this->html_out .= '         <th>'.$this->l('Image').'</th>';
        $this->html_out .= '         <th>'.$this->l('Title').'</th>';
        $this->html_out .= '         <th>'.$this->l('Title Meta').'</th>';
        $this->html_out .= '
            <th>
                <img src="'.self::imgPathBO().'group.png">&nbsp;'.$this->l('Groups permissions').'
            </th>';$prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
                    $this->html_out .= '         <th>'.$this->l('Popup').'</th>';
}
        $this->html_out .= '         <th>'.$this->l('Use in articles').'</th>';
        $this->html_out .= '         <th class="center">'.$this->l('Activate').'</th>';
        $this->html_out .= '         <th class="center">'.$this->l('Actions').'</th>';
        $this->html_out .= '     </tr>';
        $this->html_out .= ' </thead>';

        if (count($liste) > 0) {
            $this->html_out .= $this->displayListeArborescenceCategories($liste);
        } else {
            $this->html_out .= '<tr><td colspan="5" class="center">'.$this->l('No content registered').'</td></tr>';
        }

        $this->html_out .= '</table>';
        $this->html_out .= '</fieldset>';

        $this->html_out .= '</div>';
    }

      private function displayOrderCategories()
    {
        $html_libre = '';
        $liste = CategoriesClass::getListe((int)$this->context->language->id, 0);
        $html_libre .= $this->displayOrderTreeCategories($liste);

        $js1 = '$(this).closest(\'li\').toggleClass(\'mjs-nestedSortable-collapsed\')';
        $js1 .= '.toggleClass(\'mjs-nestedSortable-expanded\');';

        $js2 = 'serialized = $(\'div#configuration_blog .treeordercat ol.sortable\')';
        $js2 .= '.nestedSortable(\'serialize\');';

        $this->html_out .= '
            <script src="'.self::httpS().'://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
            <script
                type="text/javascript"
                src="'.__PS_BASE_URI__.'modules/prestablog/views/js/jquery.mjs.nestedSortable.js"
            ></script>
            <script type="text/javascript">

                $(document).ready(function(){
                    $(\'div#configuration_blog .treeordercat ol.sortable\').nestedSortable({
                        forcePlaceholderSize: true,
                        handle: \'div\',
                        helper:  \'clone\',
                        items: \'li\',
                        opacity: .6,
                        placeholder: \'placeholder\',
                        revert: 250,
                        tabSize: 25,
                        tolerance: \'pointer\',
                        toleranceElement: \'> div\',
                        maxLevels: 10,
                        isTree: true,
                        expandOnHover: 700,
                        startCollapsed: true
                    });

                    $(\'div#configuration_blog .treeordercat .disclose\').on(\'click\', function() {
                        '.$js1.'
                    })

                    $(\'form[name=formOrderCat]\').submit(function() {
                        '.$js2.'
                        $(\'input[name=newOrderCat]\').val(serialized);
                    })
                });
            </script>';

        $this->html_out .= '
            <div class="blocmodule">
                <fieldset class="row">
                    <div class="col-sm-3">
                        <a class="btn btn-primary" href="'.$this->confpath.'&catListe">
                            <i class="icon-list"></i>
                            '.$this->l('Return to list of categories').'
                        </a>
                    </div>
                </fieldset>
            </div>';

        $this->html_out .= $this->displayFormOpen(
            'filter.png',
            $this->l('Order categories'),
            $this->confpath,
            'formOrderCat'
        );
        $this->html_out .= '<input type="hidden" name="newOrderCat" value="" />';
        $this->html_out .= $this->displayInfo($this->l('Change the order of categories with a simple drag&drop.'));
        $this->html_out .= $this->displayFormLibre('col-lg-2', null, $html_libre, 'col-lg-7 treeordercat');
        $this->html_out .= $this->displayFormSubmit('submitOrderCat', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
    }

    private function displayOrderTreeCategories($liste, &$count = 0)
    {
        $html_out = '';
        $html_out .= '<ol '.((int)$count == 0 ? 'class="sortable"' : '').'>';
        foreach ($liste as $value) {
            $count += 1;
            $html_out .= '
                <li id="list_'.(int)$value['id_prestablog_categorie'].'">
                    <div>
                        <span class="disclose">
                            <span></span>
                        </span>';

            if (file_exists(self::imgUpPath().'/c/adminth_'.$value['id_prestablog_categorie'].'.jpg')) {
                $imgthidc = self::imgPathBO().self::getT().'/up-img/c/';
                $imgthidc .= 'adminth_'.$value['id_prestablog_categorie'].'.jpg';
                $html_out .= '
                    <img
                        class="thumb"
                        src="'.$imgthidc.'?'.md5(time()).'"
                        style="float:none;"
                    />';
            }
            $html_out .= $value['title'];
            $html_out .= '</div>';
            if (count($value['children']) > 0) {
                $html_out .= $this->displayOrderTreeCategories($value['children'], $count);
            }
            $html_out .= '</li>';
        }
        $html_out .= '</ol>';
        return $html_out;
    }

    private function displayListeArborescenceCategoriesNews(
        $liste_cat,
        $decalage = 0,
        $liste_id_branch_deploy = array()
    ) {
        $html_out = '';
        $tmp_min = "";
        foreach ($liste_cat as $value) {
            $active = false;
            if ((Tools::getValue('idN')
                && in_array(
                    (int)$value['id_prestablog_categorie'],
                    CorrespondancesCategoriesClass::getCategoriesListe((int)Tools::getValue('idN'))
                )) || (Tools::getValue('categories') && in_array(
                    (int)$value['id_prestablog_categorie'],
                    Tools::getValue('categories')
                ))
            ) {
                $active = true;
            }

            $html_out .= '
                <tr
                    class="prestablog_branch'.($decalage > 0?' childs':'').($active?' alt_row':'').'"
                    rel="'.$value['branch'].'"
                    id="prestablog_categorie_'.(int)$value['id_prestablog_categorie'].'"
                >
                    <td>
                        <input
                            type="checkbox"';
                            if ($tmp_min == "" || $tmp_min > $value['id_prestablog_categorie']) {
                $tmp_min = $value['id_prestablog_categorie'];
            }
            if ((Tools::getIsset('addNews')) && ($value['id_prestablog_categorie']) == $tmp_min && ($value['parent'] == 0)) {
                $html_out .= 'checked';
            }
            $html_out .= '
                            name="categories[]"
                            value="'.(int)$value['id_prestablog_categorie'].'"
                            '.($active? 'checked=checked' : '').'
                        />
                    </td>
                    <td>'.$value['id_prestablog_categorie'].'</td>';

            if (file_exists(self::imgUpPath().'/c/adminth_'.$value['id_prestablog_categorie'].'.jpg')) {
                $imgthidc = self::imgPathBO().self::getT().'/up-img/c/';
                $imgthidc .= 'adminth_'.$value['id_prestablog_categorie'].'.jpg';
                $html_out .= '
                    <td class="center">
                        <img
                            src="'.$imgthidc.'?'.md5(time()).'"
                        />
                    </td>';
            } else {
                $html_out .= '<td class="center">-</td>';
            }

            $html_out .= '<td>';

            $liste_cat_lang = CategoriesClass::getListeNoArbo();
            $languages = Language::getLanguages(true);

            foreach ($languages as $language) {
                foreach ($liste_cat_lang as $cat_lang) {
                    if ((int)$cat_lang['id_prestablog_categorie'] == (int)$value['id_prestablog_categorie']
                        && (int)$cat_lang['id_lang'] == (int)$language['id_lang']
                    ) {
                        $ouidecalage = '';
                        if ($decalage > 0) {
                            $ouidecalage .= 'padding-left:'.($decalage * 20).'px;';
                            $ouidecalage .= 'background: url(../modules/prestablog/views/img/decalage.png) ';
                            $ouidecalage .= 'no-repeat right center;';
                        }
                        $html_out .= '
                            <div class="catlang" rel="'.(int)$language['id_lang'].'">
                                <span style="'.$ouidecalage.'"></span>';

                        if (count($value['children']) > 0
                            && in_array((int)$value['id_prestablog_categorie'], $liste_id_branch_deploy)
                        ) {
                            $html_out .= '
                                <img
                                    src="'.self::imgPathBO().'collapse.gif"
                                    class="expand-cat"
                                    rel="'.$value['branch'].'"
                                />';
                        } elseif (count($value['children']) > 0
                            && !in_array((int)$value['id_prestablog_categorie'], $liste_id_branch_deploy)
                        ) {
                            $html_out .= '
                                <img
                                    src="'.self::imgPathBO().'expand.gif"
                                    class="expand-cat"
                                    rel="'.$value['branch'].'"
                                />';
                        }

                        if ($active) {
                            $html_out .= '<strong>'.$cat_lang['title'].'</strong>';
                        } else {
                            $html_out .= $cat_lang['title'];
                        }

                        $liste_groupes_categorie = CategoriesClass::getGroupsFromCategorie(
                            (int)$value['id_prestablog_categorie']
                        );

                        if (count($liste_groupes_categorie) > 0) {
                            $html_out .= '<div><small>';
                            $html_out_loop = '<img src="'.self::imgPathBO().'group.png">&nbsp;';
                            foreach ($liste_groupes_categorie as $groupe) {
                                $group = new Group((int)$groupe, (int)$language['id_lang']);
                                $html_out_loop .= $group->name.', ';
                            }
                            $html_out_loop = rtrim(trim($html_out_loop), ',');
                            $html_out .= $html_out_loop.'</small></div>';
                        }

                        $html_out .= '</div>';
                    }
                }
            }
            $html_out .= '</td>
                </tr>';
            if (count($value['children']) > 0) {
                $html_out .= $this->displayListeArborescenceCategoriesNews(
                    $value['children'],
                    $decalage + 1,
                    $liste_id_branch_deploy
                );
            }
        }
        return $html_out;
    }

    private function displayListeArborescenceCategoriesSubBlocks(
        $liste_cat,
        $decalage = 0,
        $liste_id_branch_deploy = array()
    ) {

            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $html_out = '';
        foreach ($liste_cat as $value) {
            $active = false;
            if ((Tools::getValue('idSB')
                && in_array(
                    (int)$value['id_prestablog_categorie'],
                    SubBlocksClass::getCategories((int)Tools::getValue('idSB'), 0)
                )) || (Tools::getValue('categories')
                    && in_array((int)$value['id_prestablog_categorie'], Tools::getValue('categories')))
                ) {

                $active = true;
            }
            if ((SubBlocksClass::getIdSbHome($id_lang)
                && in_array(
                    (int)$value['id_prestablog_categorie'],
                    SubBlocksClass::getCategories((int)SubBlocksClass::getIdSbHome($id_lang), 0)
                )) || (Tools::getValue('categories')
                    && in_array((int)$value['id_prestablog_categorie'], Tools::getValue('categories')))
                ) {

                $active = true;
            }

            $html_out .= '
                <tr
                    class="prestablog_branch'.($decalage > 0?' childs':'').($active?' alt_row':'').'"
                    rel="'.$value['branch'].'"
                    id="prestablog_categorie_'.(int)$value['id_prestablog_categorie'].'"
                >
                    <td>
                        <input
                            type="checkbox"
                            name="categories[]"
                            value="'.(int)$value['id_prestablog_categorie'].'"
                            '.($active? 'checked=checked' : '').'
                        />
                    </td>
                    <td>'.$value['id_prestablog_categorie'].'</td>';

            if (file_exists(self::imgUpPath().'/c/adminth_'.$value['id_prestablog_categorie'].'.jpg')) {
                $imgthidc = self::imgPathBO().self::getT().'/up-img/c/';
                $imgthidc .= 'adminth_'.$value['id_prestablog_categorie'].'.jpg';
                $html_out .= '
                    <td class="center">
                        <img src="'.$imgthidc.'?'.md5(time()).'" />
                    </td>';
            } else {
                $html_out .= '<td class="center">-</td>';
            }

            $html_out .= '<td>';

            $liste_cat_lang = CategoriesClass::getListeNoArbo();
            $languages = Language::getLanguages(true);

            foreach ($languages as $language) {
                foreach ($liste_cat_lang as $cat_lang) {
                    if ((int)$cat_lang['id_prestablog_categorie'] == (int)$value['id_prestablog_categorie']
                        && (int)$cat_lang['id_lang'] == (int)$language['id_lang']
                    ) {
                        $ouidecalage = '';
                        if ($decalage > 0) {
                            $ouidecalage .= 'padding-left:'.($decalage * 20).'px;';
                            $ouidecalage .= 'background: url(../modules/prestablog/views/img/decalage.png) ';
                            $ouidecalage .= 'no-repeat right center;';
                        }
                        $html_out .= '
                            <div class="catlang" rel="'.(int)$language['id_lang'].'">
                                <span style="'.$ouidecalage.'"></span>';

                        if (count($value['children']) > 0
                            && in_array((int)$value['id_prestablog_categorie'], $liste_id_branch_deploy)
                        ) {
                            $html_out .= '
                                <img
                                    src="'.self::imgPathBO().'collapse.gif"
                                    class="expand-cat"
                                    rel="'.$value['branch'].'"
                                />';
                        } elseif (count($value['children']) > 0
                            && !in_array((int)$value['id_prestablog_categorie'], $liste_id_branch_deploy)
                        ) {
                            $html_out .= '
                                <img
                                    src="'.self::imgPathBO().'expand.gif"
                                    class="expand-cat"
                                    rel="'.$value['branch'].'"
                                />';
                        }

                        if ($active) {
                            $html_out .= '<strong>'.$cat_lang['title'].'</strong>';
                        } else {
                            $html_out .= $cat_lang['title'];
                        }

                        $html_out .= '</div>';
                    }
                }
            }
            $html_out .= '</td>
                </tr>';
            if (count($value['children']) > 0) {
                $html_out .= $this->displayListeArborescenceCategoriesSubBlocks(
                    $value['children'],
                    $decalage + 1,
                    $liste_id_branch_deploy
                );
            }
        }
        return $html_out;
    }

    private function displayListeArborescenceCategories($liste, $decalage = 0)
    {
        $html_out = '';
        foreach ($liste as $value) {
            $html_out .= ' <tr>';
            $html_out .= '     <td class="center">'.($value['id_prestablog_categorie']).'</td>';
            if (file_exists(self::imgUpPath().'/c/adminth_'.$value['id_prestablog_categorie'].'.jpg')) {
                $imgthidc = self::imgPathBO().self::getT().'/up-img/c/';
                $imgthidc .= 'adminth_'.$value['id_prestablog_categorie'].'.jpg';
                $html_out .= '
                    <td class="center">
                        <img
                            src="'.$imgthidc.'?'.md5(time()).'"
                        />
                    </td>';
            } else {
                $html_out .= '<td class="center">-</td>';
            }
            $ouidecalage = '';
            if ($decalage > 0) {
                $ouidecalage .= 'padding-left:'.($decalage * 20).'px;';
                $ouidecalage .= 'background: url(../modules/prestablog/views/img/decalage.png) ';
                $ouidecalage .= 'no-repeat right center;';
            }
            $html_out .= '
                <td>
                    <span
                        style="'.$ouidecalage.'"
                    >
                    </span>'.$value['title'].'
                </td>';

            if ($value['meta_title']) {
                $html_out .= '<td style="font-size:90%;">'.$value['meta_title'].'</td>';
            } else {
                $html_out .= '<td style="text-align:center;">-</td>';
            }

            $html_out .= '<td style="text-align:center;">';

            $liste_groupes_categorie = CategoriesClass::getGroupsFromCategorie((int)$value['id_prestablog_categorie']);

            if (count($liste_groupes_categorie) > 0) {
                $html_out .= '<div><small>';
                $html_out_loop = '';
                foreach ($liste_groupes_categorie as $groupe) {
                    $group = new Group((int)$groupe, (int)$this->context->language->id);
                    $html_out_loop .= $group->name.', ';
                }
                $html_out_loop = rtrim(trim($html_out_loop), ',');
                $html_out .= $html_out_loop.'</small></div>';
            } else {
                $html_out .= '-';
            }

            $html_out .= '</td>';
            $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
              $html_out .= '     <td class="center">';
                $popuplink = CategoriesClass::getPopupLink($value['id_prestablog_categorie']);
                if (isset($popuplink)) {
                    $html_out .= 'Oui';
                } else {
                    $html_out .= 'Non';
                }
}
                $html_out .= '     </td>';
            $html_out .= '
                <td style="text-align:center;">
                    '.CategoriesClass::getNombreNewsDansCat((int)$value['id_prestablog_categorie']).'
                </td>';
            $imgcatenable = '<img src="'.self::imgPathFO().'enabled.gif" />';
            $imgcatdisable = '<img src="'.self::imgPathFO().'disabled.gif" />';
            $html_out .= '
                <td class="center">
                    <a href="'.$this->confpath.'&etatCat&idC='.$value['id_prestablog_categorie'].'">
                        '.($value['actif']? $imgcatenable : $imgcatdisable).'
                    </a>
                </td>';
            $html_out .= '
                <td class="center">
                    <a
                        href="'.$this->confpath.'&editCat&idC='.$value['id_prestablog_categorie'].'"
                        title="'.$this->l('Edit').'"
                    >
                        <img src="'.self::imgPathFO().'edit.gif" />
                    </a>';

            $alertdel1 = $this->l(
                'You must add an new category before delete this last one !'
            );

            $alertdel2 = $this->l('For delete parent category, you should delete all child before !');

            if (!count($value['children'])) {
                if ((count($liste) > 1 && $decalage == 0) || $decalage > 0) {
                    $html_out .= '
                        <a
                            href="'.$this->confpath.'&deleteCat&idC='.$value['id_prestablog_categorie'].'"
                            onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                        >
                            <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                        </a>';
                } else {
                    $html_out .= '
                        <a
                            href="#"
                            onclick="return confirm(\''.$alertdel1.'\');"
                        >
                            <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                        </a>';
                }
            } else {
                $html_out .= '
                    <a
                        href="#"
                        onclick="return alert(\''.$alertdel2.'\');"
                    >
                        <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                    </a>';
            }

            $html_out .= '</td>
                </tr>';
            if (count($value['children']) > 0) {
                $html_out .= $this->displayListeArborescenceCategories($value['children'], $decalage + 1);
            }
        }
        return $html_out;
    }

    private function displayDebug()
    {
        $this->html_out .= '<fieldset style="margin:auto;">';
        $this->html_out .= '
            <legend style="margin-bottom:10px;">
                <img src="'.self::imgPathFO().'debug.png" /> '.$this->l('Debug module').'
            </legend>';
        $this->html_out .= $this->displayWarning(
            '<p>
                '.$this->l('This debug is expected to reveal the errors after an installation or upgrade.').'
            </p>
            <p>
                '.$this->l('It is based on the knowledge base of customer feedback, bugs or simple errors.').'
            </p>'
        );
        $this->html_out .= '</fieldset>';
    }

    private function displayInformations()
    {
        $informations = array(
            'host' => array(
                'version' => array(
                    'php' => phpversion(),
                    'server' => $_SERVER['SERVER_SOFTWARE'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'uname' => function_exists('php_uname') ? php_uname('s').' '.php_uname('v').' '.php_uname('m') : '',
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'display_errors' => ini_get('display_errors'),
                    'magic_quotes' => (_PS_MAGIC_QUOTES_GPC_ ? 'true' : 'false')
                ),
                'database' => array(
                    'version' => Db::getInstance()->getVersion(),
                    'prefix' => bqSQL(_DB_PREFIX_),
                    'engine' => _MYSQL_ENGINE_,
                    'ps_version' => Configuration::get('PS_VERSION_DB')
                )
            ),
            'prestashop' => array(
                'ps_version' => _PS_VERSION_,
                'ps_version_install' => Configuration::get('PS_INSTALL_VERSION'),
                'ps_ssl' => Configuration::get('PS_SSL_ENABLED'),
                'url_front' => Tools::getHttpHost(true).__PS_BASE_URI__,
                'url_admin' => $_SERVER['PHP_SELF'],
                'domain' => Configuration::get('PS_SHOP_DOMAIN'),
                'domain_ssl' => Configuration::get('PS_SHOP_DOMAIN_SSL'),
                'theme' => _THEME_NAME_,
                'mobile' => Configuration::get('PS_ALLOW_MOBILE_DEVICE'),
                'mail_method' => Configuration::get('PS_MAIL_METHOD'),
                'ps_rewrite' => Configuration::get('PS_REWRITING_SETTINGS'),
                'accented_chars_url' => Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'),
                'css_cache' => Configuration::get('PS_CSS_THEME_CACHE'),
                'js_cache' => Configuration::get('PS_JS_THEME_CACHE'),
                'html_compression' => Configuration::get('PS_HTML_THEME_COMPRESSION'),
                'js_html_compression' => Configuration::get('PS_JS_HTML_THEME_COMPRESSION'),
                'mode_dev' => (_PS_MODE_DEV_ ? 'true' : 'false'),
                'debug_sql' => (_PS_DEBUG_SQL_ ? 'true' : 'false'),
                'display_compatibility_warning' => (_PS_DISPLAY_COMPATIBILITY_WARNING_ ? 'true' : 'false'),
                'mode_demo' => (_PS_MODE_DEMO_ ? 'true' : 'false')
            ),
            'prestablog' => array(
                'core' => array(
                    'version' => $this->version,
                    'module_key' => $this->module_key
                )
            )
        );

        $shops = Shop::getShops();
        foreach ($shops as $key_shop => $value_shop) {
            foreach ($value_shop as $key_s => $value_s) {
                $informations['shop'.$key_shop][$key_s] = $value_s;
            }

            $configuration_mail = Configuration::getMultiple(
                array(
                    'PS_SHOP_EMAIL',
                    'PS_MAIL_METHOD',
                    'PS_MAIL_SERVER',
                    'PS_MAIL_USER',
                    'PS_SHOP_NAME',
                    'PS_MAIL_SMTP_ENCRYPTION',
                    'PS_MAIL_SMTP_PORT',
                    'PS_MAIL_TYPE'
                ),
                null,
                null,
                $key_shop
            );

            foreach ($configuration_mail as $key_conf_mail => $value_conf_mail) {
                $informations['shop'.$key_shop][$key_conf_mail] = $value_conf_mail;
            }

            foreach (array_keys($this->configurations) as $configuration_key) {
                $informations['shop'.$key_shop]['config'][$configuration_key] = Configuration::get(
                    $configuration_key,
                    false,
                    null,
                    $key_shop
                );
            }
        }

        $this->html_out .= $this->displayFormOpen('icon-info', $this->l('Informations'), $this->confpath);
        $infos = '';
        foreach ($informations as $kcore => $vcore) {
            if (is_array($vcore)) {
                foreach ($vcore as $kth => $vth) {
                    if (is_array($vth)) {
                        foreach ($vth as $kinfo => $vinfo) {
                            $infos .= $kcore.'_'.$kth.'_'.$kinfo.' : '.$vinfo."\n";
                        }
                    } else {
                        $infos .= $kcore.'_'.$kth.' : '.$vth."\n";
                    }
                }
            } else {
                $infos .= $kcore.' : '.$vcore."\n";
            }
            $infos .= "\n";
        }
        if (!$this->demo_mode) {
            $html_libre = '<textarea style="height:300px;">'.$infos.'</textarea></p>';
        } else {
            $html_libre = $this->displayWarning($this->l('Feature disabled on the demo mode'));
        }
        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Informations'), $html_libre, 'col-lg-7');
        $this->html_out .= $this->displayFormClose();
    }

    private function displayDocumentation()
    {
        $this->html_out .= $this->displayInfo(
            '<p>'.$this->l('To access the prestablog tutorial :').'</p>
            <ol>
                <li>'.$this->l('Visit your addons account').'</li>
                <li>'.$this->l('Click on the download tab').'</li>
                <li>'.$this->l('Then the blue icon "?" related to the module').'</li>
            </ol>'
        );
    }

    private function displayImport()
    {
        $languages = Language::getLanguages(true);

        if ($this->demo_mode) {
            $this->html_out .= $this->displayWarning($this->l('Feature disabled on the demo mode'));
        }

        $this->html_out .= $this->displayFormOpen(
            'icon-upload',
            $this->l('Import from Wordpress XML file'),
            $this->confpath
        );
        $this->html_out .= $this->displayWarning(
            $this->l('Be carefull ! Select only Articles exportation on your WordPress.')
        );
        $this->html_out .= $this->displayFormFile(
            'col-lg-2',
            $this->l('Upload file'),
            $this->name.'_import_xml',
            'col-lg-5',
            $this->l('Format:').' *.XML'
        );
        $this->html_out .= $this->displayFormSubmit('submitImportXml', 'icon-cloud-upload', $this->l('Send file'));
        $this->html_out .= $this->displayFormClose();

        if (Configuration::get($this->name.'_import_xml')) {
            if (!file_exists(_PS_UPLOAD_DIR_.Configuration::get($this->name.'_import_xml'))) {
                $errxml = $this->l('The XML file in the configuration is not locate in the ./download directory');
                $errxml .= '<br/>'.$this->l('You must upload a new import XML file.');
                $this->html_out .= $this->displayError($errxml);
            } else {
                $file_content = Tools::file_get_contents(_PS_UPLOAD_DIR_.Configuration::get($this->name.'_import_xml'));
                if (strpos($file_content, '<?xml') === false) {
                    $errxml = $this->l('The file is not an XML content');
                    $errxml .= '<br/>'.$this->l('You must upload a new import XML file.');
                    $this->html_out .= $this->displayError($errxml);
                } else {
                    $this->html_out .= $this->displayFormOpen(
                        'icon-gear',
                        $this->l('Chose the language where you want to import this xml'),
                        $this->confpath
                    );

                    $html_libre = $this->l('Current XML import file in configuration :').' ';
                    $html_libre .= Configuration::get($this->name.'_import_xml');
                    $this->html_out .= $this->displayFormLibre('col-lg-2', '', $html_libre, 'col-lg-7');

                    $html_libre = '';
                    foreach ($languages as $language) {
                        $html_libre .= '
                            <input
                                type="radio"
                                name="import_xml_langue"
                                value="'.(int)$language['id_lang'].'"
                                '.($this->langue_default_store == (int)$language['id_lang'] ? 'checked':'').'
                            ><img src="../img/l/'.(int)$language['id_lang'].'.jpg" />&nbsp;&nbsp;&nbsp;';
                    }
                    $this->html_out .= $this->displayFormLibre(
                        'col-lg-2',
                        $this->l('Select language'),
                        $html_libre,
                        'col-lg-7'
                    );
                    $this->html_out .= $this->displayFormSubmit(
                        'submitParseXml',
                        'icon-gears',
                        $this->l('Import the current file')
                    );
                    $this->html_out .= $this->displayFormClose();
                }
            }
        }
    }

    private function displayConfigAntiSpam()
    {
        $liste = AntiSpamClass::getListe((int)$this->context->language->id, 0);

        $this->html_out .= $this->displayFormOpen('icon-shield', $this->l('Antispam questions'), $this->confpath);
        $infoas = '
            <p>
                '.$this->l('This Antispam option can protect you to comments of spammers robots.').'
            </p>
            <p>
                '.$this->l('Will random in the comment form.').'
            </p>';

        $this->html_out .= $this->displayInfo($infoas);
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-2',
            $this->l('Antispam activation'),
            $this->name.'_antispam_actif'
        );
        $this->html_out .= $this->displayFormSubmit(
            'submitAntiSpamConfig',
            'icon-save',
            $this->l('Update the configuration')
        );
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '
            <div class="blocmodule">
                <fieldset>
                    <div class="col-sm-3">
                        <a class="btn btn-primary" href="'.$this->confpath.'&addAntiSpam">
                            <i class="icon-plus"></i>
                            '.$this->l('Add an antispam question').'
                        </a>
                    </div>
                </fieldset>
            </div>';

        $this->html_out .= '
            <div class="blocmodule">
                <table class="table" cellpadding="0" cellspacing="0" style="width:100%;margin:auto;">';
        $this->html_out .= ' <thead class="center">';
        $this->html_out .= '     <tr>';
        $this->html_out .= '         <th></th>';
        $this->html_out .= '         <th>'.$this->l('Question').'</th>';
        $this->html_out .= '         <th>'.$this->l('Expected reply').'</th>';
        $this->html_out .= '         <th class="center">'.$this->l('Activate').'</th>';
        $this->html_out .= '         <th class="center">'.$this->l('Actions').'</th>';
        $this->html_out .= '     </tr>';
        $this->html_out .= ' </thead>';

        $imgenable = '<img src="'.self::imgPathFO().'enabled.gif" />';
        $imgdisable = '<img src="'.self::imgPathFO().'disabled.gif" />';

        if (count($liste) > 0) {
            foreach ($liste as $value) {
                $this->html_out .= ' <tr>';
                $this->html_out .= '     <td class="center">'.$value['id_prestablog_antispam'].'</td>';
                $this->html_out .= '     <td>'.$value['question'].'</td>';
                $this->html_out .= '     <td>'.$value['reply'].'</td>';

                $this->html_out .= '
                    <td class="center">
                        <a href="'.$this->confpath.'&etatAntiSpam&idAS='.$value['id_prestablog_antispam'].'">
                            '.($value['actif']? $imgenable : $imgdisable).'
                        </a>
                    </td>';
                $this->html_out .= '     <td class="center">';
                $this->html_out .= '
                        <a
                            href="'.$this->confpath.'&editAntiSpam&idAS='.$value['id_prestablog_antispam'].'"
                            title="'.$this->l('Edit').'"
                        >
                            <img src="'.self::imgPathFO().'edit.gif" />
                        </a>';
                $this->html_out .= '
                    <a
                        href="'.$this->confpath.'&deleteAntiSpam&idAS='.$value['id_prestablog_antispam'].'"
                        onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                    >
                        <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                    </a>';
                $this->html_out .= '     </td>';
                $this->html_out .= ' </tr>';
            }
        } else {
            $this->html_out .= '<tr><td colspan="5" class="center">'.$this->l('No content registered').'</td></tr>';
        }

        $this->html_out .= '</table>';
        $this->html_out .= '</div>';
    }

    public function deleteSitemapFromShop($id_shop)
    {
        $directory_site_map = _PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$id_shop;

        foreach (glob($directory_site_map.'/*.{xml}', GLOB_BRACE) as $file) {
            if (!is_dir($file)) {
                self::unlinkFile($directory_site_map.'/'.basename($file));
            }
        }
    }
     public static function getRobotsContent()
    {
        $tab = array();

        // Special allow directives
        $tab['Allow'] = array(
            '*/modules/*.css',
            '*/modules/*.js',
            '*/modules/*.png',
            '*/modules/*.jpg',
            '*/themes/*/assets/cache/*.js',
            '*/themes/*/assets/cache/*.css',
            '*/themes/*/assets/css/*',
        );

        // Directories
        $tab['Directories'] = array('cache/', 'classes/', 'config/', 'controllers/',
            'css/', 'download/', 'js/', 'localization/', 'log/', 'mails/', 'modules/', 'override/',
            'pdf/', 'src/', 'tools/', 'translations/', 'upload/', 'vendor/', 'web/', 'webservice/');

        // Files
        $disallow_controllers = array(
            'addresses', 'address', 'authentication', 'cart', 'discount', 'footer',
            'get-file', 'header', 'history', 'identity', 'images.inc', 'init', 'my-account', 'order',
            'order-slip', 'order-detail', 'order-follow', 'order-return', 'order-confirmation', 'pagination', 'password',
            'pdf-invoice', 'pdf-order-return', 'pdf-order-slip', 'product-sort', 'search', 'statistics','attachment', 'guest-tracking'
        );

        // Rewrite files
        $tab['Files'] = array();
        if (Configuration::get('PS_REWRITING_SETTINGS')) {
            $sql = 'SELECT DISTINCT ml.url_rewrite, l.iso_code
                    FROM '._DB_PREFIX_.'meta m
                    INNER JOIN '._DB_PREFIX_.'meta_lang ml ON ml.id_meta = m.id_meta
                    INNER JOIN '._DB_PREFIX_.'lang l ON l.id_lang = ml.id_lang
                    WHERE l.active = 1 AND m.page IN (\''.implode('\', \'', $disallow_controllers).'\')';
            if ($results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)) {
                foreach ($results as $row) {
                    $tab['Files'][$row['iso_code']][] = $row['url_rewrite'];
                }
            }
        }

        $tab['GB'] = array(
            '?order=','?tag=','?id_currency=','?search_query=','?back=','?n=',
            '&order=','&tag=','&id_currency=','&search_query=','&back=','&n='
        );

        foreach ($disallow_controllers as $controller) {
            $tab['GB'][] = 'controller='.$controller;
        }

        return $tab;
    }


    public function createTheShopSitemap()
    {
        $this->deleteSitemapFromShop((int)$this->context->shop->id);

        $languages = Language::getLanguages(true, (int)$this->context->shop->id);

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
        $xml_feed = new SimpleXMLElement($xml);

        $list_sitemap = array();
        if (Configuration::get($this->name.'_sitemap_articles')) {
            $list_sitemap[] = 'articles';
        }
        if (Configuration::get($this->name.'_sitemap_categories')) {
            $list_sitemap[] = 'categories';
        }

        $all_urls_form_language = array();

        foreach ($languages as $lang) {
            foreach ($list_sitemap as $type_list) {
                switch ($type_list) {
                    case 'articles':
                        $all_urls_form_language = NewsClass::getListe(
                            (int)$lang['id_lang'],
                            1,
                            0,
                            0,
                            null,
                            'n.`date`',
                            'desc',
                            Date(
                                'Y-m-d H:i:s',
                                strtotime('-'.(int)Configuration::get($this->name.'_sitemap_older').' months')
                            ),
                            null,
                            null,
                            1,
                            (int)Configuration::get('prestablog_news_title_length'),
                            (int)Configuration::get('prestablog_news_intro_length')
                        );
                        break;

                    case 'categories':
                        $all_urls_form_language = CategoriesClass::getListeNoArbo(1, (int)$lang['id_lang']);
                        break;

                    default:
                        $all_urls_form_language = array();
                        break;
                }

                $xmls_urls = array_chunk(
                    $all_urls_form_language,
                    (int)Configuration::get($this->name.'_sitemap_limit')
                );
                foreach ($xmls_urls as $xml_key => $xml_urls) {
                    $location_file = $this->name.'/sitemap/';
                    $location_file .= (int)$this->context->shop->id.'/';
                    $location_file .= $type_list.'_'.$lang['iso_code'].'_'.(int)$xml_key.'.xml';

                    $this->createSplitSitemap($location_file, $xml_urls, $type_list);

                    $sitemap = $xml_feed->addChild('sitemap');
                    $sitemap->addAttribute('lang', $lang['iso_code']);
                    $sitemap->addAttribute('type', 'text/html');
                    $sitemap->addAttribute('charset', 'UTF-8');

                    $sitemap->addChild('loc', self::urlSRoot().'modules/'.$location_file);
                    $sitemap->addChild('lastmod', date('c'));
                }
            }
        }
        file_put_contents(
            _PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$this->context->shop->id.'/master.xml',
            $xml_feed->asXML()
        );


        return true;

//$robots_content = self::getRobotsContent();
//$test = 'Allow: */modules/prestablog/';
//if(strpos($robots_content, $test ) === false) {
 //   $robots_file = _PS_ROOT_DIR_.'/robots.txt';
//    $write_fd = @fopen($robots_file, 'w')
//fwrite($write_fd, "Allow: */modules/prestablog/\n");
//fclose($write_fd);
//}
   //     return true;
    }

    public function createSplitSitemap($location_file, $urls, $type_list = '')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        $xml .= '</urlset>';

        $xml_feed = new SimpleXMLElement($xml);

        foreach ($urls as $child) {
            switch ($type_list) {
                case 'articles':
                    $sitemap = $xml_feed->addChild('url');
                    $sitemap->addChild('priority', '0.9');
                    $sitemap->addChild('loc', self::prestablogUrl(
                        array(
                            'id' => (int)$child['id_prestablog_news'],
                            'seo' => $child['link_rewrite'],
                            'titre' => $child['title'],
                            'id_lang' => (int)$child['id_lang']
                        )
                    ));

                    $sitemap->addChild('lastmod', date('c', strtotime($child['date_modification'])));
                    $sitemap->addChild('changefreq', 'weekly');

                    if ($child['image_presente']) {
                        $imagechild = $sitemap->addChild(
                            'image:image',
                            null,
                            'http://www.google.com/schemas/sitemap-image/1.1'
                        );
                        $imgchild = self::urlSRoot().'modules/prestablog/views/img/';
                        $imgchild .= self::getT().'/up-img/'.$child['id_prestablog_news'].'.jpg';
                        $imagechild->addChild(
                            'image:loc',
                            $imgchild,
                            'http://www.google.com/schemas/sitemap-image/1.1'
                        );
                        $imagechild->addChild(
                            'image:title',
                            $child['title'],
                            'http://www.google.com/schemas/sitemap-image/1.1'
                        );
                    }
                    break;

                case 'categories':
                    if ($child['title'] != '') {
                        $sitemap = $xml_feed->addChild('url');
                        $sitemap->addChild('priority', '0.5');
                        $sitemap->addChild(
                            'loc',
                            self::prestablogUrl(array(
                                'c' => (int)$child['id_prestablog_categorie'],
                                'titre' => ($child['link_rewrite'] != '' ?$child['link_rewrite'] : $child['title']),
                                'id_lang' => (int)$child['id_lang']
                            ))
                        );

                        $sitemap->addChild('changefreq', 'yearly');

                        if ($child['image_presente']) {
                            $imagechild = $sitemap->addChild(
                                'image:image',
                                null,
                                'http://www.google.com/schemas/sitemap-image/1.1'
                            );
                            $imgchild = self::urlSRoot().'modules/prestablog/views/img/';
                            $imgchild .= self::getT().'/up-img/c/'.$child['id_prestablog_categorie'].'.jpg';
                            $imagechild->addChild(
                                'image:loc',
                                $imgchild,
                                'http://www.google.com/schemas/sitemap-image/1.1'
                            );
                            $imagechild->addChild(
                                'image:title',
                                $child['title'],
                                'http://www.google.com/schemas/sitemap-image/1.1'
                            );
                        }
                    }
                    break;
            }
        }

        file_put_contents(_PS_MODULE_DIR_.$location_file, $xml_feed->asXML());

        return true;
    }

    private function checkCurrentSitemap()
    {
        $directory_site_map = _PS_MODULE_DIR_.$this->name.'/sitemap/'.(int)$this->context->shop->id;

        $html_out = '
            <p>
                <a onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                class="btn btn-primary" href="'.$this->confpath.'&deleteSitemap">
                    <i class="icon-trash-o"></i>
                    '.$this->l('Delete all sitemap xml for this shop').'
                </a>
            </p>';

        $html_out .= '
            <p>
                '.$this->l('Use master sitemap to regroup all news :').'
            </p>';

        $liste_sitemap = glob($directory_site_map.'/master.{xml}', GLOB_BRACE);
        if (count($liste_sitemap) > 0) {
            $html_out .= '<ul>';
            foreach ($liste_sitemap as $file) {
                if (!is_dir($file)) {
                    $url_sitemap = self::urlSRoot().'modules/prestablog/sitemap/';
                    $url_sitemap .= (int)$this->context->shop->id.'/'.basename($file);
                    $html_out .= '<li><a href="'.$url_sitemap.'" target="_blank">'.$url_sitemap.'</a></li>';
                }
            }
            $html_out .= '</ul>';
        } else {
            $html_out = '<p>'.$this->l('no xml file available').'</p>';
        }

        $html_out .= '<hr/>';

        $html_out .= '<p>'.sprintf(
            $this->l('All sitemaps can be crawled individually for %1$s store :'),
            '<strong>'.$this->context->shop->name.'</strong>'
        ).'</p>';

        $liste_sitemap = glob($directory_site_map.'/*[^master]*.{xml}', GLOB_BRACE);
        if (count($liste_sitemap) > 0) {
            $html_out .= '<ul>';
            foreach ($liste_sitemap as $file) {
                if (!is_dir($file)) {
                    $url_sitemap = self::urlSRoot().'modules/prestablog/sitemap/';
                    $url_sitemap .= (int)$this->context->shop->id.'/'.basename($file);
                    $html_out .= '<li><a href="'.$url_sitemap.'" target="_blank">'.$url_sitemap.'</a></li>';
                }
            }
            $html_out .= '</ul>';
        } else {
            $html_out = '<p>'.$this->l('no xml file available').'</p>';
        }

        return $html_out;
    }

    private function displaySitemap()
    {
        $this->html_out .= '<div class="col-md-5">';

        $this->html_out .= $this->displayFormOpen(
            'icon-sitemap',
            $this->l('Sitemap configuration'),
            $this->confpath
        );
        $this->html_out .= $this->displayInfo(
            $this->l('Sitemap allow webmaster to inform crawlers search engine.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-4',
            $this->l('Sitemap activation'),
            $this->name.'_sitemap_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-4',
            $this->l('Articles'),
            $this->name.'_sitemap_articles'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-4',
            $this->l('Categories'),
            $this->name.'_sitemap_categories'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-4',
            $this->l('Limit url number per xml file'),
            $this->name.'_sitemap_limit',
            Configuration::get($this->name.'_sitemap_limit'),
            10,
            'col-lg-4',
            $this->l('Urls/xml')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-4',
            $this->l('Date since'),
            $this->name.'_sitemap_older',
            Configuration::get($this->name.'_sitemap_older'),
            10,
            'col-lg-3',
            $this->l('Month(s)'),
            $this->l('Since : ').date(
                'd/m/Y',
                strtotime('-'.(int)Configuration::get($this->name.'_sitemap_older').' months')
            )
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-4',
            $this->l('Token security for cron'),
            $this->name.'_sitemap_token',
            Configuration::get($this->name.'_sitemap_token'),
            10,
            'col-lg-6',
            null,
            $this->l('Locked')
        );
        $this->html_out .= $this->displayFormSubmit(
            'submitSitemapConfig',
            'icon-save',
            $this->l('Update the configuration')
        );
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
        $this->html_out .= '<div class="col-md-7">';

        $this->html_out .= $this->displayFormOpen('icon-cogs', $this->l('Sitemap manual'), $this->confpath);
        $this->html_out .= $this->displayWarning(sprintf(
            $this->l('This action will erase all current sitemaps for the shop %1$s'),
            '<strong>'.$this->context->shop->name.'</strong>'
        ));
        $this->html_out .= $this->displayFormSubmit('submitSitemapGenerate', 'icon-cog', $this->l('Generate sitemap'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'icon-cogs',
            $this->l('Sitemap automatic with cron url'),
            $this->confpath
        );
        $this->html_out .= $this->displayWarning(sprintf(
            $this->l('This action will erase all current sitemaps for the shop %1$s'),
            '<strong>'.$this->context->shop->name.'</strong>'
        ));
        $this->html_out .= '
            <p>
                '.$this->l('Add a "Cron task" to reload this url:').'
            </p>';

        $urlcron = self::urlSRoot().'index.php?fc=module&module=prestablog';
        $urlcron .= '&controller=sitemap&id_shop='.(int)$this->context->shop->id;
        $urlcron .= '&token='.Configuration::get($this->name.'_sitemap_token');

        $this->html_out .= '<p><strong>'.$urlcron.'</strong></p>';

        $this->html_out .= '<p>'.$this->l('It will automatically generate your XML Sitemaps.').'</p>';
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayInfo($this->checkCurrentSitemap());

        $this->html_out .= '</div>';
    }

    private function displayPageBlog()
    {
        $languages = Language::getLanguages(true);
        $div_lang_name = 'meta_title¤meta_description¤title_h1';

        $this->html_out .= '
            <script type="text/javascript">
                id_language = Number('.$this->langue_default_store.');
            </script>';

        $this->html_out .= $this->displayFormOpen('blog.png', $this->l('Blog page configuration'), $this->confpath);
        $info = '<p>'.$this->l('Use this link in your menu configuration:').'</p>';
        $info .= '<ul>';

        $multilang = (Language::countActiveLanguages() > 1);

        if ($multilang) {
            $languages = Language::getLanguages(true);
            foreach ($languages as $language) {
                if ((int)Configuration::get('prestablog_rewrite_actif')) {
                    if ((int)Configuration::get('PS_REWRITING_SETTINGS')) {
                        $url_page_blog = self::urlSRoot().Language::getIsoById((int)$language['id_lang']).'/blog';
                    } else {
                        $url_page_blog = self::urlSRoot().$this->ctrblog.'&id_lang='.(int)$language['id_lang'];
                    }
                } else {
                    if ((int)Configuration::get('PS_REWRITING_SETTINGS')) {
                        $url_page_blog = self::urlSRoot().Language::getIsoById((int)$language['id_lang']);
                        $url_page_blog .= '/'.$this->ctrblog;
                    } else {
                        $url_page_blog = self::urlSRoot().$this->ctrblog.'&id_lang='.(int)$language['id_lang'];
                    }
                }

                $info .= '
                    <li>
                        <img src="../../img/l/'.$language['id_lang'].'.jpg" style="vertical-align:middle;" />
                        <a href="'.$url_page_blog.'" target="_blank">'.$url_page_blog.'</a>
                    </li>';
            }
        } else {
            if ((int)Configuration::get('PS_REWRITING_SETTINGS')
                && (int)Configuration::get('prestablog_rewrite_actif')) {
                $url_page_blog = self::urlSRoot().'blog';
            } else {
                $url_page_blog = self::urlSRoot().$this->ctrblog;
            }

            $info .= '
                <li>
                    <a href="'.$url_page_blog.'" target="_blank">'.$url_page_blog.'</a>
                </li>';
        }
        $info .= '</ul>';
        $this->html_out .= $this->displayInfo($info);
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-2',
            $this->l('Slide on blogpage'),
            $this->name.'_pageslide_actif'
        );

        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="meta_title_'.$lid.'"
                    style="display: '.($lid == $this->langue_default_store ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_title_'.$lid.'"
                        id="meta_title_'.$lid.'"
                        value="'.Configuration::get($this->name.'_titlepageblog', (int)$lid).'"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Title Meta'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_title', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="meta_description_'.$lid.'"
                    style="display: '.($lid == $this->langue_default_store ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_description_'.$lid.'"
                        id="meta_description_'.$lid.'"
                        value="'.Configuration::get($this->name.'_descpageblog', (int)$lid).'"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Description Meta'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_description', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="titre_h1_'.$lid.'"
                    style="display: '.($lid == $this->langue_default_store ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="title_h1_'.$lid.'"
                        id="title_h1_'.$lid.'"
                        value="'.Configuration::get($this->name.'_h1pageblog', (int)$lid).'"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Title page H1'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('title_h1', $div_lang_name)
        );
        //***********************************************************

        $this->html_out .= $this->displayFormSubmit('submitPageBlog', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();


    $prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
      $this->html_out .= '<div class="col-md-12">';
    $this->html_out .= $this->displayFormOpen('slide.png', $this->l('Popup prestablog'), $this->confpath);
    $this->html_out .= '<a href="'.$this->confpath.'&class=PopupClass&displayContent">
                            <img src="'.self::imgPathFO().'brick.png" />
                            '.$this->l('Create a popup').'
                        </a>';
            $popup = new PopupClass();

    $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Popup on homepage'),
            $this->name.'_popuphome_actif',
            $this->l('The popup will be displayed in the blog\'s home page. For displaying the popup in articles or categories, please go directly to the creation of articles and categories')
        );
 $popups_link[0] = 'None';
        foreach (PopupClass::getListePopup((int)$this->context->language->id) as $popup_link) {
            $popups_link[$popup_link['id_prestablog_popup']] = $popup_link['title'];
        }

        $popuplink = PopupClass::getIdPopupActifHome();

if (isset($popuplink[0])) {
    $popuplink = $popuplink[0]['id_prestablog_popup'];

$this->html_out .= $this->displayFormSelect(
            'col-lg-5',
            $this->l('Choose the popup to display :'),
            'popupLink',
            $popuplink,
            $popups_link,
            null,
            'col-lg-5'
        );
} else {
    $this->html_out .= $this->displayFormSelect(
            'col-lg-5',
            $this->l('Choose the popup to display :'),
            'popupLink',
            self::getP(),
            $popups_link,
            null,
            'col-lg-5'
        );
}

            $this->html_out .= $this->displayFormSubmit('submitPopupHome', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
                $this->html_out .= '</div>';
}
/************************************************/
      $this->html_out .= '<div class="col-md-12">';
$this->html_out .= $this->displayFormSubBlocksFront();
       $this->html_out .= '</div>';
    }

    private function displayConfWizard()
    {
        $this->html_out .= $this->displayFormOpen('wizard.png', $this->l('Wizard templating'), $this->confpath);
        $this->html_out .= $this->displayFormSubmit('submitWizard', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
    }

    public static function scanListeThemes()
    {
        $liste = array();
        foreach (glob(_PS_MODULE_DIR_.'prestablog/views/config/*.{xml}', GLOB_BRACE) as $file) {
            if (!is_dir($file)) {
                $liste[] = rtrim(basename($file), '.xml');
            }
        }
        return $liste;
    }

       public static function scanListePopups()
    {
        $liste = array();
        foreach (glob(_PS_MODULE_DIR_.'prestablog/views/config/*.{xml}', GLOB_BRACE) as $file) {
            if (!is_dir($file)) {
                $liste[] = rtrim(basename($file), '.xml');
            }
        }
        return $liste;
    }





    public static function scanLayoutFolder()
    {
        $liste = array();
        foreach (glob(_PS_MODULE_DIR_.'prestablog/views/img/layout/*.{png}', GLOB_BRACE) as $file) {
            if (!is_dir($file)) {
                $liste[rtrim(basename($file), '.png')] = basename($file);
            }
        }
        return $liste;
    }

    private function displayConfTheme()
    {
        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen('theme.png', $this->l('Theme'), $this->confpath);
        $themes = array();
        foreach (self::scanListeThemes() as $value_theme) {
            $themes[$value_theme] = basename($value_theme);
        }

        $this->html_out .= $this->displayFormSelect(
            'col-lg-5',
            $this->l('Choose your module theme :'),
            'theme',
            self::getT(),
            $themes,
            null,
            'col-lg-5'
        );


        $this->html_out .= '
            <script language="javascript" type="text/javascript">
                $(document).ready(function() {
                    $("#theme").change(function() {
                        var src = $(this).val();
                        var issrc = "<img src=\'../modules/prestablog/views/img/" + src + "-preview.jpg\'>";
                        $("#imagePreview").hide();
                        $("#imagePreview").html(src ? issrc : "");
                        $("#imagePreview").fadeIn();
                    });
                });
            </script>

            <label>'.$this->l('Preview :').'</label>

            <div id="imagePreview">
                <img
                    src="'.self::imgPathFO().self::getT().'-preview.jpg"
                    width="400px"
                />
            </div>
            <div class="clear"></div>';

        $this->html_out .= $this->displayFormSubmit('submitTheme', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
        $layerslider = Module::getInstanceByName('layerslider');
        if ($layerslider) { } else {
        $this->html_out .= '
            <div id ="suggestion_title" class="suggestion_title">
                <h2>
                    '.$this->l('Suggested module for your store, fully compatible with Prestablog ! ').'
                </h2>
            </div>
        ';
        $this->html_out .= '
        <div id="suggestion_banner" class="suggestion_banner blocmodule">
        <p class="title">Creative slider</p>
        <img class="thumb" src="'.self::imgPathFO().'creative.jpg" />
        <p>'.$this->l('Creative Slider is a premium multi-purpose slider for creating image galleries, content sliders, and mind-blowing slideshows with must-see effects. It uses cutting edge technologies to provide the smoothest experience that’s possible, and it comes with more than 200 preset 2D and 3D slide transitions. Fully comaptible with the blog, you can even add the slide to your articles and the blog\'s home page!').'
        </p>';
        $this->html_out .= ' <div class="clearfix"></div>'."\n";
        $this->html_out .= '<a class="btn btn-default link_button" href="https://addons.prestashop.com/fr/sliders-galeries/19062-creative-slider-responsive-slideshow.html" target="_blank"> '.$this->l('Buy now').' </a>
        <a class="btn btn-default link_button2" href="https://addons.prestashop.com/demo/FO11013.html" target="_blank"> Live Demo </a>
        </div>
        '; }
        if ($layerslider) {
        	 $this->displayCreativeSlide();
             $this->html_out .= $this->displayInfo($this->l('The slide will be displayed in your blog\'s welcome page'));
        }

        $this->html_out .= '</div>';


        $this->html_out .= '<div class="col-md-6">';

        $this->displayConfLayout();

        $this->displayConfSlide();


        $this->html_out .= '</div>';
    }

    private function displayConfLayout()
    {
        $this->html_out .= $this->displayFormOpen(
            'slide.png',
            $this->l('Layout of your blog template'),
            $this->confpath
        );

        $layout_out = '';

        foreach (self::scanLayoutFolder() as $key => $layout) {
            if ((int)Configuration::get($this->name.'_layout_blog') == (int)$key) {
                $layout_out .= '
                    <div class="layout_preview" data-layoutref="'.(int)$key.'">
                        <img class="layout_select" src="'.self::imgPathFO().'check.png" />
                        <img src="'.self::imgPathFO().'layout/'.$layout.'" />
                    </div>';
            } else {
                $layout_out .= '
                    <div class="layout_preview" data-layoutref="'.(int)$key.'">
                        <a href="'.$this->confpath.'&selectLayout&prestablog_layout_blog='.(int)$key.'">
                            <img src="'.self::imgPathFO().'layout/'.$layout.'" />
                        </a>
                    </div>';
            }
        }

        $this->html_out .= '<div class="layouts_preview row">'.$layout_out.'</div>';

        $this->html_out .= $this->displayFormClose();
    }


    private function displayCreativeSlide()
    {
      $config_theme = $this->getConfigXmlTheme(self::getT());
$this->html_out .= $this->renderForm();

    }

    private function displayConfSlide()
    {
        $config_theme = $this->getConfigXmlTheme(self::getT());

        $this->html_out .= $this->displayFormOpen('slide.png', $this->l('Slideshow Prestablog'), $this->confpath);

        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Slide on homepage'),
            $this->name.'_homenews_actif',
            $this->l('The slide will be displayed in the center column of the home page of your shop.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Slide on blogpage'),
            $this->name.'_pageslide_actif',
            $this->l('The slide will be displayed in the top of first page articles list of blog.')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of slide to display'),
            $this->name.'_homenews_limit',
            Configuration::get($this->name.'_homenews_limit'),
            10,
            'col-lg-2'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Title length'),
            $this->name.'_slide_title_length',
            Configuration::get($this->name.'_slide_title_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Introduction length'),
            $this->name.'_slide_intro_length',
            Configuration::get($this->name.'_slide_intro_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Slide picture width'),
            'slide_picture_width',
            $config_theme->images->slide->width,
            10,
            'col-lg-2',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Slide picture height'),
            'slide_picture_height',
            $config_theme->images->slide->height,
            10,
            'col-lg-2',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfSlideNews', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
    }

    private function displayConfBlocs()
    {
        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen(
            'blocs.png',
            $this->l('Block last news'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_lastnews_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show introduction text'),
            $this->name.'_lastnews_showintro',
            $this->l('This option may penalize your SEO.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show thumb'),
            $this->name.'_lastnews_showthumb'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Title length'),
            $this->name.'_lastnews_title_length',
            Configuration::get($this->name.'_lastnews_title_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Introduction length'),
            $this->name.'_lastnews_intro_length',
            Configuration::get($this->name.'_lastnews_intro_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of news to display'),
            $this->name.'_lastnews_limit',
            Configuration::get($this->name.'_lastnews_limit'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Link "show all"'),
            $this->name.'_lastnews_showall'
        );
        $this->html_out .= $this->displayFormSubmit('submitConfBlocLastNews', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'blocs.png',
            $this->l('Block date news'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_datenews_actif'
        );
        $this->html_out .= $this->displayFormSelect(
            'col-lg-5',
            $this->l('Order news'),
            $this->name.'_datenews_order',
            Configuration::get($this->name.'_datenews_order'),
            array('desc' => $this->l('Desc'), 'asc' => $this->l('Asc')),
            null,
            'col-lg-5'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Link "show all"'),
            $this->name.'_datenews_showall'
        );
        $this->html_out .= $this->displayFormSubmit('submitConfBlocDateNews', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'blocs.png',
            $this->l('Block categories news'),
            $this->confpath
        );
        $this->html_out .= '
            <p class="center">
                <a class="button" href="'.$this->confpath.'&configCategories">
                    '.$this->l('Go to the categories configuration').'
                </a>
            </p>';
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'search.png',
            $this->l('Block search news'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_blocsearch_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Advance search in the top of results, with filter of categories'),
            $this->name.'_search_filtrecat'
        );
        $this->html_out .= $this->displayFormSubmit('submitConfBlocSearch', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'rss.png',
            $this->l('Block Rss all news'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_allnews_rss'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Title length'),
            $this->name.'_rss_title_length',
            Configuration::get($this->name.'_rss_title_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Introduction length'),
            $this->name.'_rss_intro_length',
            Configuration::get($this->name.'_rss_intro_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfBlocRss', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen(
            'order.png',
            $this->l('Order of the blocks on columns'),
            $this->confpath
        );

        $this->html_out .= '
            <ul id="sortblocLeft" class="connectedSortable">
                <li class="ui-state-default ui-state-disabled">'.$this->l('Left').'</li>';

        $sbl = unserialize(Configuration::get($this->name.'_sbl'));
        if (count($sbl) > 0) {
            foreach ($sbl as $vs) {
                if ($vs != '') {
                    $this->html_out .= '
                        <li rel="'.$vs.'" class="ui-state-default ui-move">
                            '.$this->message_call_back[$vs].'
                        </li>';
                }
            }
        }

        $this->html_out .= '
            </ul>
            <ul id="sortblocRight" class="connectedSortable">
                <li class="ui-state-default ui-state-disabled">'.$this->l('Right').'</li>';

        $sbr = unserialize(Configuration::get($this->name.'_sbr'));
        if (count($sbr) > 0) {
            foreach ($sbr as $vs) {
                if ($vs != '') {
                    $this->html_out .= '
                        <li rel="'.$vs.'" class="ui-state-default ui-move">
                            '.$this->message_call_back[$vs].'
                        </li>';
                }
            }
        }

        $this->html_out .= '
            </ul>
            <script src="'.self::httpS().'://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
            <script src="'.__PS_BASE_URI__.'modules/prestablog/views/js/jquery.mjs.nestedSortable.js"></script>

            <script type="text/javascript">
                $(function() {
                    $("#sortblocLeft, #sortblocRight").sortable({
                            placeholder: "ui-state-highlight",
                            connectWith: ".connectedSortable",
                            items: "li:not(.ui-state-disabled)",
                            update: function(event, ui) {
                                $.ajax({
                                    url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                                    type: "GET",
                                    data: {
                                        ajax: true,
                                        action: \'prestablogrun\',
                                        do: \'sortBlocs\',
                                        sortblocLeft: $("#sortblocLeft").sortable("toArray", { attribute: "rel" }),
                                        sortblocRight: $("#sortblocRight").sortable("toArray", { attribute: "rel" }),
                                        id_shop: \''.$this->context->shop->id.'\'
                                    },
                                    success:function(data){}
                                });
                            }
                    }).disableSelection();
                });
            </script>';

        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'blocs.png',
            $this->l('Footer last news'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_footlastnews_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show introduction text'),
            $this->name.'_footlastnews_intro',
            $this->l('This option may penalize your SEO.')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of news to display'),
            $this->name.'_footlastnews_limit',
            Configuration::get($this->name.'_footlastnews_limit'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Title length'),
            $this->name.'_footer_title_length',
            Configuration::get($this->name.'_footer_title_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Introduction length'),
            $this->name.'_footer_intro_length',
            Configuration::get($this->name.'_footer_intro_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Link "show all"'),
            $this->name.'_footlastnews_showall'
        );
        $this->html_out .= $this->displayFormSubmit(
            'submitConfFooterLastNews',
            'icon-save',
            $this->l('Update')
        );
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
    }

    private function displayConfCategories()
    {
        $config_theme = $this->getConfigXmlTheme(self::getT());

        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen(
            'blocs.png',
            $this->l('Block categories news'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_catnews_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('View empty categories'),
            $this->name.'_catnews_empty',
            $this->l('Supports the count of items in the categories recursive children.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show news count by category'),
            $this->name.'_catnews_shownbnews',
            $this->l('Does not display zero values.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Tree view'),
            $this->name.'_catnews_tree'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show thumb'),
            $this->name.'_catnews_showthumb'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show crop description'),
            $this->name.'_catnews_showintro'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Title length'),
            $this->name.'_cat_title_length',
            Configuration::get($this->name.'_cat_title_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Description length'),
            $this->name.'_cat_intro_length',
            Configuration::get($this->name.'_cat_intro_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Link "show all"'),
            $this->name.'_catnews_showall'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            '<img src="'.self::imgPathFO().'rss.png" align="absmiddle" /> '.$this->l('Rss feed'),
            $this->name.'_catnews_rss',
            $this->l('List only for selected category')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfBlocCatNews', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'categories.png',
            $this->l('Category menu in blog pages'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate menu on blog index'),
            $this->name.'_menu_cat_blog_index'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate menu on blog list'),
            $this->name.'_menu_cat_blog_list'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate menu on article'),
            $this->name.'_menu_cat_blog_article'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show blog link'),
            $this->name.'_menu_cat_home_link'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show blog image link'),
            $this->name.'_menu_cat_home_img',
            $this->l('Only if blog link is activated').'<br/>'.sprintf(
                $this->l('Show %1$s instead %2$s'),
                '<img
                    style="vertical-align:top;background-color:#383838;padding:4px;"
                    src="'._MODULE_DIR_.'prestablog/views/img/home.gif"
                />',
                '"<strong>'.$this->l('Blog').'</strong>"'
            )
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('View empty categories'),
            $this->name.'_menu_cat_blog_empty',
            $this->l('Supports the count of items in the categories recursive children.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show news count by category'),
            $this->name.'_menu_cat_blog_nbnews',
            $this->l('Does not display zero values.')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfMenuCatBlog', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen(
            'top-category.png',
            $this->l('Top of first page of category'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show description'),
            $this->name.'_view_cat_desc'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show thumbnail'),
            $this->name.'_view_cat_thumb'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show picture'),
            $this->name.'_view_cat_img'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Thumb picture width for categories'),
            'thumb_cat_width',
            $config_theme->categories->thumb->width,
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Thumb picture height for categories'),
            'thumb_cat_height',
            $config_theme->categories->thumb->height,
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Full size picture width for category'),
            'full_cat_width',
            $config_theme->categories->full->width,
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Full size picture height for category'),
            'full_cat_height',
            $config_theme->categories->full->height,
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfCategory', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'textoptions.png',
            $this->l('Options in articles list'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Thumb picture width for news'),
            'thumb_picture_width',
            $config_theme->images->thumb->width,
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Thumb picture height for news'),
            'thumb_picture_height',
            $config_theme->images->thumb->height,
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Title length'),
            $this->name.'_news_title_length',
            Configuration::get($this->name.'_news_title_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Description length'),
            $this->name.'_news_intro_length',
            Configuration::get($this->name.'_news_intro_length'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfListeArticles', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
    }

    private function displayConf()
    {
        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen(
            'rewrite.png',
            $this->l('Rewrite configuration'),
            $this->confpath
        );
        if (!Configuration::get('PS_REWRITING_SETTINGS') && Configuration::get('prestablog_rewrite_actif')) {
            $err_url = $this->l('The general rewrite option (Friendly URL) of your PrestaShop is not activate.');
            $err_url .= '<br />'.$this->l('You must enable this general option to it works.');
            $this->html_out .= $this->displayError($err_url);
        }

        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Enable rewrite (Friendly URL)'),
            $this->name.'_rewrite_actif',
            $this->l('Enable only if your server allows URL rewriting (recommended)')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfRewrite', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
        $this->html_out .= $this->displayFormOpen(
            'frontoffice.png',
            $this->l('Global front configuration'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of news per page'),
            $this->name.'_nb_liste_page',
            Configuration::get($this->name.'_nb_liste_page'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormSelect(
            'col-lg-5',
            $this->l('Article\'s display'),
            $this->name.'_article_page',
            Configuration::get($this->name.'_article_page'),
            array(
                '1' => $this->l('One column'),
                '2' => $this->l('Two columns'),
                '3' => $this->l('Three columns')
            ),
            null,
            'col-lg-5'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Ratings on news'),
            $this->name.'_rating_actif',
            $this->l('Activate rating.')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Show thumbnail image in article page'),
            $this->name.'_view_news_img'
        );

        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            '<img src="'.self::imgPathFO().'rss.png" align="absmiddle" /> '.$this->l('Rss link for categories news'),
            $this->name.'_uniqnews_rss',
            $this->l('Rss link for categories in the news page.')
        );

        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Add a new tab with associated blog posts directly in your product page'),
            $this->name.'_producttab_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Socials buttons share'),
            $this->name.'_socials_actif'
        );

        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Width of thumbnail in the product list linked'),
            $this->name.'_thumb_linkprod_width',
            Configuration::get($this->name.'_thumb_linkprod_width'),
            10,
            'col-lg-4',
            $this->l('px')
        );
        $this->html_out .= '<h2 style="text-align:center;">'.$this->l('Social share buttons').'</h2>';

        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Facebook'),
            $this->name.'_s_facebook'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Twitter'),
            $this->name.'_s_twitter'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Linkedin'),
            $this->name.'_s_linkedin'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Email'),
            $this->name.'_s_email'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Pinterest'),
            $this->name.'_s_pinterest'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Pocket'),
            $this->name.'_s_pocket'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Tumblr'),
            $this->name.'_s_tumblr'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Reddit'),
            $this->name.'_s_reddit'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Hackernews'),
            $this->name.'_s_hackernews'
        );

        $this->html_out .= $this->displayFormSubmit('submitConfGobalFront', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
        $this->html_out .= '<div class="col-md-6">';
    $this->html_out .= $this->displayFormOpen('slide.png', $this->l('Popup prestablog'), $this->confpath);
$this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('General activation of the popup'),
            $this->name.'_popup_general'
        );

        $this->html_out .= $this->displayFormSubmit('submitPopupGeneral', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();
        $this->html_out .= '</div>';
        $this->html_out .= '<div class="col-md-6">';
        $this->html_out .= $this->displayFormOpen(
            'backoffice.png',
            $this->l('Global admin configuration'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of characters to search on related products for article edited'),
            $this->name.'_nb_car_min_linkprod',
            Configuration::get($this->name.'_nb_car_min_linkprod'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of results in search off related products for article edited'),
            $this->name.'_nb_list_linkprod',
            Configuration::get($this->name.'_nb_list_linkprod'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of characters to search on related articles for article edited'),
            $this->name.'_nb_car_min_linknews',
            Configuration::get($this->name.'_nb_car_min_linknews'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of results in search off related articles for article edited'),
            $this->name.'_nb_list_linknews',
            Configuration::get($this->name.'_nb_list_linknews'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of characters to search on related lookbooks for article edited'),
            $this->name.'_nb_car_min_linknews',
            Configuration::get($this->name.'_nb_car_min_linklb'),
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of results in search off related lookbooks for article edited'),
            $this->name.'_nb_list_linknews',
            Configuration::get($this->name.'_nb_list_linklb'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('items/page on admin list news'),
            $this->name.'_nb_news_pl',
            Configuration::get($this->name.'_nb_news_pl'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('items/page on admin list comments'),
            $this->name.'_nb_comments_pl',
            Configuration::get($this->name.'_nb_comments_pl'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Always show comments in article edition'),
            $this->name.'_comment_div_visible'
        );
        $this->html_out .= $this->displayFormSubmit('submitConfGobalAdmin', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
    }
private function displayColorBlog()
    {
 $liste_colors = NewsClass::getColorHome((int)$this->context->shop->id);
 if (!isset($liste_colors[0]) || $liste_colors[0] == null)
 {
 	$menu_color = 0;
$menu_hover = 0;
$read_color = 0;
$hover_color = 0;
$title_color = 0;
$text_color = 0;
$menu_link = 0;
$link_read = 0;
$article_title = 0;
$article_text = 0;
$block_categories = 0;
$block_categories_link = 0;
$block_title = 0;
 $block_btn = 0;
$block_btn_hover = 0;
$categorie_block_background = 0;
$categorie_block_background_hover = 0;
$article_background = 0;
$article_comment_background = 0;
} else {
 $menu_color = $liste_colors[0]["menu_color"];
 $menu_hover = $liste_colors[0]["menu_hover"];
 $read_color = $liste_colors[0]["read_color"];
 $hover_color = $liste_colors[0]["hover_color"];
 $title_color = $liste_colors[0]["title_color"];
 $text_color = $liste_colors[0]["text_color"];
 $menu_link = $liste_colors[0]["menu_link"];
 $link_read = $liste_colors[0]["link_read"];
 $article_title = $liste_colors[0]["article_title"];
 $article_text = $liste_colors[0]["article_text"];
 $block_categories = $liste_colors[0]["block_categories"];
 $block_categories_link = $liste_colors[0]["block_categories_link"];
 $block_title = $liste_colors[0]["block_title"];
 $block_btn = $liste_colors[0]["block_btn"];
 $block_btn_hover = $liste_colors[0]["block_btn_hover"];
$categorie_block_background = $liste_colors[0]["categorie_block_background"];
$categorie_block_background_hover = $liste_colors[0]["categorie_block_background_hover"];
$article_background = $liste_colors[0]["article_background"];
$article_comment_background = $liste_colors[0]["article_comment_background"];
}

        $this->html_out .='<script type="text/javascript">
        $.fn.mColorPicker.init.replace = ".mColorPicker"</script>';
        $this->html_out .= '<div class="col-md-6">';
                $this->html_out .= $this->displayFormOpen(
            'comments.png',
            $this->l('Color of blog\'s element'),
            $this->confpath
        );
      $this->html_out .= '<div class="blocmodule col-md-12">
      <legend>Menu</legend>';

if($menu_color != '0' && $menu_color != NULL) {
$this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background'),
            'menu_color',
            $menu_color,
            'mColorPicker',
            'col-md-5'
        );
} else {
	$this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background'),
            'menu_color',
            '',
            'mColorPicker',
            'col-md-5'
        );
}

if($menu_hover != '0' && $menu_hover != NULL) {
$this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background hover'),
            'menu_hover',
            $menu_hover,
            'mColorPicker',
            'col-md-5'
        );
} else {
    $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background hover'),
            'menu_hover',
            '',
            'mColorPicker',
            'col-md-5'
        );
}

if($menu_link != '0' && $menu_link != NULL) {
$this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Links'),
            'menu_link',
            $menu_link,
            'mColorPicker',
            'col-md-5'
        );
} else {
    $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Links'),
            'menu_link',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
$this->html_out .= '</div>';
$this->html_out .= '<div class="blocmodule col-md-12">';
      $this->html_out .= '<legend>'.$this->l('Categories\' listing').'</legend>';
if($read_color != '0' && $read_color != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Read more background'),
            'read_color',
            $read_color,
            'mColorPicker',
            'col-md-5'
        );
} else {
	 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Read more background'),
            'read_color',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
        if($hover_color != '0' && $hover_color != NULL) {
   $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Read more hover'),
            'hover_color',
            $hover_color,
            'mColorPicker',
            'col-md-5'
        );
} else {
   $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Read more hover'),
            'hover_color',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
               if($title_color != '0' && $title_color != NULL) {
    $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Title'),
            'title_color',
            $title_color,
            'mColorPicker',
            'col-md-5'
        );
} else {
    $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Title'),
            'title_color',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($text_color != '0' && $text_color != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Text'),
            'text_color',
            $text_color,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Text'),
            'text_color',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($link_read != '0' && $link_read != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Links'),
            'link_read',
            $link_read,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Links'),
            'link_read',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($categorie_block_background != '0' && $categorie_block_background != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Articles background'),
            'categorie_block_background',
            $categorie_block_background,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Articles background'),
            'categorie_block_background',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($categorie_block_background_hover != '0' && $categorie_block_background_hover != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Articles background hover'),
            'categorie_block_background_hover',
            $categorie_block_background_hover,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Articles background hover'),
            'categorie_block_background_hover',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
             $this->html_out .= '</div>';
                   $this->html_out .= '<div class="blocmodule col-md-12">';

      $this->html_out .= '<legend>'.$this->l('Articles content').'</legend>';

                          if($article_title != '0' && $article_title != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Title'),
            'article_title',
            $article_title,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Title'),
            'article_title',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($article_text != '0' && $article_text != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Text'),
            'article_text',
            $article_text,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Text'),
            'article_text',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($article_background != '0' && $article_background != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background'),
            'article_background',
            $article_background,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background'),
            'article_background',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($article_comment_background != '0' && $article_comment_background != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Comments background'),
            'article_comment_background',
            $article_comment_background,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Comments background'),
            'article_comment_background',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
       $this->html_out .= '</div>';
                                $this->html_out .= '<div class="blocmodule col-md-12">';

      $this->html_out .= '<legend>'.$this->l('Blocks content').'</legend>';
                    if($block_categories != '0' && $block_categories != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background'),
            'block_categories',
            $block_categories,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Background'),
            'block_categories',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($block_categories_link != '0' && $block_categories_link != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Links'),
            'block_categories_link',
            $block_categories_link,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Links'),
            'block_categories_link',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($block_title != '0' && $block_title != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Titles'),
            'block_title',
            $block_title,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Titles'),
            'block_title',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($block_btn != '0' && $block_btn != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Buttons'),
            'block_btn',
            $block_btn,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Buttons'),
            'block_btn',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
                    if($block_btn_hover != '0' && $block_btn_hover != NULL) {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Buttons hover'),
            'block_btn_hover',
            $block_btn_hover,
            'mColorPicker',
            'col-md-5'
        );
} else {
 $this->html_out .= $this->displayFormInputColor(
            'col-lg-3',
            $this->l('Buttons hover'),
            'block_btn_hover',
            '',
            'mColorPicker',
            'col-md-5'
        );
}
       $this->html_out .= '</div>';
        $this->html_out .= $this->displayFormSubmit('submitColorBlog', 'icon-save', $this->l('Save'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
            	$css = Tools::file_get_contents(_PS_MODULE_DIR_.'prestablog/views/css/custom'.(int)$this->context->shop->id.'.css');
 $this->html_out .= '<div class="col-md-6">';
                $this->html_out .= $this->displayFormOpen(
            'comments.png',
            $this->l('Custom css'),
            $this->confpath
        );

                   $html_libre = '';
                   if ($css == '') {
            $html_libre .= '
                <div
                    style="display: block;"
                >
                    <textarea class="rte autoload_rte"
                        id="content_css"
                        name="content_css"
                        style="height: 350px;"
                    >/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */
 '.$css.'</textarea></div>';
                   } else {
            $html_libre .= '
                <div
                    style="display: block;"
                >
                    <textarea class="rte autoload_rte"
                        id="content_css"
                        name="content_css"
                        style="height: 350px;"
                    >'.$css.'</textarea></div>';
                   }

        $this->html_out .= $this->displayFormLibre(
            'col-md-2',
            $this->l('Custom css'),
            $html_libre,
            'col-md-10'
        );
        $this->html_out .= $this->displayFormSubmit('submitConfCss', 'icon-save', $this->l('Save'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
    }


    private function displayConfComments()
    {
        $infocom = $this->l('The classic comment system (on the left of your page) and its options cannot be applied to facebook comments (on the right of your page).');

        $this->html_out .= $this->displayInfo($infocom);

        $this->html_out .= '<div class="col-md-6">';
        $this->html_out .= $this->displayFormOpen(
            'comments.png',
            $this->l('Comments'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_comment_actif'
        );


        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Only registered users can publish a comment'),
            $this->name.'_comment_only_login'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Auto approve comments'),
            $this->name.'_comment_auto_actif'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Link href nofollow'),
            $this->name.'_comment_nofollow',
            $this->l('Indicates search engines not to follow the link')
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Inform admin by email for a new comment'),
            $this->name.'_comment_alert_admin'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Admin Mail'),
            $this->name.'_comment_admin_mail',
            Configuration::get($this->name.'_comment_admin_mail'),
            40,
            'col-lg-4',
            null,
            null,
            '<i class="icon-envelope-o"></i>'
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Captcha'),
            $this->name.'_captcha_actif'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Clé publique captcha google'),
            $this->name.'_captcha_public_key',
            Configuration::get($this->name.'_captcha_public_key'),
            40,
            'col-lg-4',
            null,
            null
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Clé privée captcha google'),
            $this->name.'_captcha_private_key',
            Configuration::get($this->name.'_captcha_private_key'),
            40,
            'col-lg-4',
            null,
            null
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Mail user subscription'),
            $this->name.'_comment_subscription',
            $this->l('Only registered users can subscribe')
        );
        $this->html_out .= $this->displayFormSubmit('submitConfComment', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
        $this->html_out .= '<div class="col-md-6">';

        $this->html_out .= $this->displayFormOpen(
            'facebook.png',
            $this->l('Facebook comments'),
            $this->confpath
        );
        $this->html_out .= $this->displayFormEnableItemConfiguration(
            'col-lg-5',
            $this->l('Activate'),
            $this->name.'_commentfb_actif'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Number of comments visible'),
            $this->name.'_commentfb_nombre',
            Configuration::get($this->name.'_commentfb_nombre'),
            10,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('API Facebook Id'),
            $this->name.'_commentfb_apiId',
            Configuration::get($this->name.'_commentfb_apiId'),
            20,
            'col-lg-7',
            '',
            $this->l('Optional').' - '.$this->l('You can manage comments directly on facebook application.')
        );

        $infofb = $this->l('Add a facebook accounts ID for beeing comments moderators.');
        $infofb .= ' '.$this->l('ID Can be found on');
        $infofb .= ' <a href="http://findmyfbid.com" target="_blank">http://findmyfbid.com</a>';

        $this->html_out .= $this->displayFormInput(
            'col-lg-5',
            $this->l('Add global moderator'),
            $this->name.'_commentfb_modosId',
            '',
            20,
            'col-lg-7',
            '',
            $infofb
        );

        $this->html_out .= $this->displayFormSubmit('submitConfCommentFB', 'icon-save', $this->l('Update'));
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= $this->displayFormOpen(
            'facebook.png',
            $this->l('Facebook moderators'),
            $this->confpath
        );
        $list_fb_moderators = unserialize(Configuration::get($this->name.'_commentfb_modosId'));
        if (is_array($list_fb_moderators) && count($list_fb_moderators) > 0) {
            foreach ($list_fb_moderators as $fb_moderator) {
                $this->html_out .= '<div class="blocmodule">';
                $this->html_out .= '<i class="icon-facebook"></i> '.$fb_moderator;
                $confirmmod = $this->l('This action will delete the moderator : ');
                $confirmmod .= $fb_moderator.'\r\n'.$this->l('Are you sure?');
                $this->html_out .= '
                    <a style="float:right;"
                        onclick="return confirm(\''.$confirmmod.'\');"
                        href="'.$this->confpath.'&deleteFacebookModerator&fb_moderator_id='.$fb_moderator.'">
                        <i class="icon-trash"></i> '.$this->l('Delete').'
                    </a>';

                $this->html_out .= '</div>';
            }
        } else {
            $this->html_out .= $this->l('No moderators configured');
        }
        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '</div>';
    }

    private function displayFormOpen(
        $icon_legend = 'cog.gif',
        $label_legend = 'New Form',
        $action = '',
        $name = 'formblog'
    ) {
        $ret = '';
        if (strpos($icon_legend, 'icon-') !== false) {
            $ret = '<i class="'.$icon_legend.'"></i>';
        } else {
            $ret = '<img src="'.self::imgPathFO().''.$icon_legend.'" />';
        }

        return '
            <div class="blocmodule">
                <fieldset>
                    <legend>'.$ret.'&nbsp;'.$label_legend.'</legend>
                    <form
                        method="post"
                        class="form-horizontal"
                        action="'.$action.'"
                        name="'.$name.'"
                        enctype="multipart/form-data"
                    >';
    }

    private function displayFormClose()
    {
        return '</form>
                </fieldset>
            </div>';
    }

    private function displayFormSelect(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $value = '',
        $options = array(),
        $sizecar = 20,
        $size_bootstrap = 'col-lg-5',
        $info_span = null,
        $help = null,
        $info_span_before = null
    ) {
        $select = '';

        $select .= '
            <div class="form-group ">
                <label class="control-label '.$label_bootstrap.'" for="'.$name_item.'">'.$label_text.'</label>
                <div class="'.$size_bootstrap.'">
                    <div class="input-group">
                        '.($info_span_before ? '<span class="input-group-addon">'.$info_span_before.'</span>' : '').'
                        <select
                            name="'.$name_item.'"
                            id="'.$name_item.'"
                            '.($sizecar ? 'size="'.$sizecar.'"' : '').'
                        >';

        if (count($options) > 0) {
            foreach ($options as $key => $val) {
                $select .= '<option value="'.$key.'" '.($value == $key ? ' selected' : '').'>'.$val.'</option>';
            }
        }

        $select .= '    </select>
                        '.($info_span ? '<span class="input-group-addon">'.$info_span.'</span>' : '').'
                    </div>
                    '.($help ? '<p class="help-block">'.$help.'</p>' : '').'
                </div>
            </div>';
        return $select;
    }

    private function displayFormSubmit($submit_name, $icon, $label)
    {
        return '
            <div class="form-actions col-md-4 col-lg-offset-4">
                <button class="btn btn-primary" name="'.$submit_name.'" type="submit">
                    <i class="'.$icon.'"></i>&nbsp;'.$label.'
                </button>
            </div>';
    }

    private function displayFormLibre(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $libre_html = '',
        $size_bootstrap = 'col-lg-5',
        $lang_flags = null
    ) {
        return '
            <div class="form-group">
                <label class="control-label '.$label_bootstrap.'">'.$label_text.'</label>
                <div class="'.$size_bootstrap.'">
                    '.$libre_html.'
                </div>
                '.($lang_flags ? '<div class="col-lg-1">'.$lang_flags.'</div>' : '').'
            </div>';
    }

    private function displayFormFile(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $size_bootstrap = 'col-lg-5',
        $help = null
    ) {
        return '
            <div class="form-group">
                <label class="control-label '.$label_bootstrap.'" for="'.$name_item.'">'.$label_text.'</label>
                <div class="'.$size_bootstrap.'">
                    <input id="'.$name_item.'" type="file" name="'.$name_item.'" class="hide" />
                    <div class="dummyfile input-group">
                        <span class="input-group-addon"><i class="icon-file"></i></span>
                        <input id="'.$name_item.'-name" type="text" class="disabled" name="filename" readonly />
                        <span class="input-group-btn">
                            <button
                                id="'.$name_item.'-selectbutton"
                                type="button"
                                name="submitAddAttachments"
                                class="btn btn-default"
                            >
                                <i class="icon-folder-open"></i> '.$this->l('Choose a file').'
                            </button>
                        </span>
                    </div>
                    '.($help ? '<p class="help-block">'.$help.'</p>' : '').'
                </div>
            </div>
            <script>
                $(document).ready(function(){
                    $(\'#'.$name_item.'-selectbutton\').click(function(e) {
                        $(\'#'.$name_item.'\').trigger(\'click\');
                    });

                    $(\'#'.$name_item.'-name\').click(function(e) {
                        $(\'#'.$name_item.'\').trigger(\'click\');
                    });

                    $(\'#'.$name_item.'-name\').on(\'dragenter\', function(e) {
                        e.stopPropagation();
                        e.preventDefault();
                    });

                    $(\'#'.$name_item.'-name\').on(\'dragover\', function(e) {
                        e.stopPropagation();
                        e.preventDefault();
                    });

                    $(\'#'.$name_item.'-name\').on(\'drop\', function(e) {
                        e.preventDefault();
                        var files = e.originalEvent.dataTransfer.files;
                        $(\'#'.$name_item.'\')[0].files = files;
                        $(this).val(files[0].name);
                    });

                    $(\'#'.$name_item.'\').change(function(e) {
                        if ($(this)[0].files !== undefined)
                        {
                            var files = $(this)[0].files;
                            var name  = \'\';

                            $.each(files, function(index, value) {
                                name += value.name+\', \';
                            });

                            $(\'#'.$name_item.'-name\').val(name.slice(0, -2));
                        } else // Internet Explorer 9 Compatibility
                        {
                            var name = $(this).val().split(/[\\/]/);
                            $(\'#'.$name_item.'-name\').val(name[name.length-1]);
                        }
                    });
                });
            </script>';
    }

    private function displayFormFileNoLabel($name_item = '', $size_bootstrap = 'col-lg-5', $help = null)
    {
        return '
            <input id="'.$name_item.'" type="file" name="'.$name_item.'" class="hide" />
            <div class="dummyfile input-group '.$size_bootstrap.'" >
                <span class="input-group-addon"><i class="icon-file"></i></span>
                <input id="'.$name_item.'-name" type="text" class="disabled" name="filename" readonly />
                <span class="input-group-btn">
                    <button
                        id="'.$name_item.'-selectbutton"
                        type="button"
                        name="submitAddAttachments"
                        class="btn btn-default"
                    >
                        <i class="icon-folder-open"></i> '.$this->l('Choose a file').'
                    </button>
                </span>
            </div>
            '.($help ? '<p class="help-block">'.$help.'</p>' : '').'
            <script>
                $(document).ready(function(){
                    $(\'#'.$name_item.'-selectbutton\').click(function(e) {
                        $(\'#'.$name_item.'\').trigger(\'click\');
                    });

                    $(\'#'.$name_item.'-name\').click(function(e) {
                        $(\'#'.$name_item.'\').trigger(\'click\');
                    });

                    $(\'#'.$name_item.'-name\').on(\'dragenter\', function(e) {
                        e.stopPropagation();
                        e.preventDefault();
                    });

                    $(\'#'.$name_item.'-name\').on(\'dragover\', function(e) {
                        e.stopPropagation();
                        e.preventDefault();
                    });

                    $(\'#'.$name_item.'-name\').on(\'drop\', function(e) {
                        e.preventDefault();
                        var files = e.originalEvent.dataTransfer.files;
                        $(\'#'.$name_item.'\')[0].files = files;
                        $(this).val(files[0].name);
                    });

                    $(\'#'.$name_item.'\').change(function(e) {
                        if ($(this)[0].files !== undefined)
                        {
                            var files = $(this)[0].files;
                            var name  = \'\';

                            $.each(files, function(index, value) {
                                name += value.name+\', \';
                            });

                            $(\'#'.$name_item.'-name\').val(name.slice(0, -2));
                        } else // Internet Explorer 9 Compatibility
                        {
                            var name = $(this).val().split(/[\\/]/);
                            $(\'#'.$name_item.'-name\').val(name[name.length-1]);
                        }
                    });
                });
            </script>';
    }

private function displayFormInputColor(
        $label_bootstrap = 'col-lg-2',
        $label_text = '',
        $name_item = '',
        $value = '',
        $class= '',
        $size_bootstrap = 'col-lg-5'
    ) {
        return '
        <div class="clearfix">
                <label class="control-label '.$label_bootstrap.'" for="'.$name_item.'">'.$label_text.'</label>
                <div class="'.$size_bootstrap.'">
                    <div class="form-group">
                        <input
                            id="'.$name_item.'"
                            type="text"
                            data-hex = "true"
                            class="'.$class.'"
                            value="'.$value.'"
                            name="'.$name_item.'"
                        >
                    </div>
                </div>
                </div>';
    }
    private function displayFormInput(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $value = '',
        $sizecar = 20,
        $size_bootstrap = 'col-lg-5',
        $info_span = null,
        $help = null,
        $info_span_before = null
    ) {
        return '
            <div class="form-group">
                <label class="control-label '.$label_bootstrap.'" for="'.$name_item.'">'.$label_text.'</label>
                <div class="'.$size_bootstrap.'">
                    <div class="input-group">
                        '.($info_span_before ? '<span class="input-group-addon">'.$info_span_before.'</span>' : '').'
                        <input
                            id="'.$name_item.'"
                            '.($sizecar ? 'size="'.$sizecar.'"' : '').'
                            type="text"
                            value="'.$value.'"
                            name="'.$name_item.'"
                        >
                        '.($info_span ? '<span class="input-group-addon">'.$info_span.'</span>':'').'
                    </div>
                    '.($help ? '<p class="help-block">'.$help.'</p>':'').'
                </div>
            </div>';
    }

    private function displayFormDate(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $value = '',
        $time = true
    ) {
        if (!$value) {
            if ($time) {
                $value = date('Y-m-d H:i:s');
            } else {
                $value = date('Y-m-d');
            }
        }

        return '
            <div class="form-group">
                <label class="control-label '.$label_bootstrap.'" for="'.$name_item.'">'.$label_text.'</label>
                <div class="'.($time ? 'col-lg-4' : 'col-lg-3').'">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="icon-calendar"></i></span>
                        <input
                            id="'.$name_item.'"
                            '.($time ? 'size="20"' : 'size="10"').'
                            type="text"
                            value="'.$value.'"
                            name="'.$name_item.'"
                        >
                    </div>
                    <p class="help-block">
                        '.$this->l('Format: YYYY-MM-DD').($time ? ' '.$this->l('HH:MM:SS') : '').'
                    </p>
                </div>
            </div>'.$this->moduleDatepicker($name_item, true);
    }

    private function displayFormDateWithActivation(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $value = '',
        $time = true,
        $name_item_activation = '',
        $value_activation = null
    ) {
        if (!$value) {
            if ($time) {
                $value = date('Y-m-d H:i:s');
            } else {
                $value = date('Y-m-d');
            }
        }

        return '
            <div class="form-group">
                <label class="control-label '.$label_bootstrap.'" for="'.$name_item.'">'.$label_text.'</label>
                <div class="'.($time ? 'col-lg-10' : 'col-lg-6').'">
                    <div class="input-group">
                        <span class="switch prestashop-switch fixed-width-lg" style="margin-right:5px;">
                            <input
                                name="'.$name_item_activation.'"
                                id="'.$name_item_activation.'_on"
                                value="1"
                                '.($value_activation ? 'checked="checked" ' : '').'
                                type="radio"
                            >
                            <label for="'.$name_item_activation.'_on">'.$this->l('Yes').'</label>
                            <input
                                name="'.$name_item_activation.'"
                                id="'.$name_item_activation.'_off"
                                value="0"
                                '.(!$value_activation ? 'checked="checked" ' : '').'
                                type="radio"
                            >
                            <label for="'.$name_item_activation.'_off">'.$this->l('No').'</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <span class="input-group-addon"><i class="icon-calendar"></i></span>
                        <input
                            id="'.$name_item.'"
                            '.($time ? 'size="20"' : 'size="10"').'
                            type="text"
                            value="'.$value.'"
                            name="'.$name_item.'"
                        >
                    </div>
                    <p class="help-block">
                        '.$this->l('Format: YYYY-MM-DD').($time ? ' '.$this->l('HH:MM:SS') : '').'
                    </p>
                </div>
            </div>'.$this->moduleDatepicker($name_item, true);
    }

    private function displayFormEnableItemConfiguration(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $help = null
    ) {
        $ni = $name_item;
        return '
            <div class="form-group">
                <label for="'.$ni.'" class="control-label '.$label_bootstrap.'">
                    <span>'.$label_text.'</span>
                </label>
                <div class="col-lg-7">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input
                            name="'.$ni.'"
                            id="'.$ni.'_on"
                            value="1"
                            '.(Tools::getValue($ni, Configuration::get($ni)) ? 'checked="checked"' : '').'
                            type="radio"
                        >
                        <label for="'.$ni.'_on">'.$this->l('Yes').'</label>
                        <input
                            name="'.$ni.'"
                            id="'.$ni.'_off"
                            value="0"
                            '.(!Tools::getValue($ni, Configuration::get($ni)) ? 'checked="checked"' : '').'
                            type="radio"
                        >
                        <label for="'.$ni.'_off">'.$this->l('No').'</label>
                        <a class="slide-button btn"></a>
                    </span>
                    '.($help ? '<p class="help-block">'.$help.'</p>' : '').'
                </div>
            </div>';
    }

    private function displayFormEnableItem(
        $label_bootstrap = 'col-lg-5',
        $label_text = '',
        $name_item = '',
        $value = null,
        $help = null
    ) {
        $ni = $name_item;
        return '
            <div class="form-group">
                <label for="'.$ni.'" class="control-label '.$label_bootstrap.'">
                    <span>'.$label_text.'</span>
                </label>
                <div class="col-lg-7">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input
                            name="'.$ni.'"
                            id="'.$ni.'_on"
                            value="1"
                            '.($value ? 'checked="checked" ' : '').'
                            type="radio"
                        >
                        <label for="'.$ni.'_on">'.$this->l('Yes').'</label>
                        <input
                            name="'.$ni.'"
                            id="'.$ni.'_off"
                            value="0"
                            '.(!$value ? 'checked="checked" ' : '').'
                            type="radio"
                        >
                        <label for="'.$ni.'_off">'.$this->l('No').'</label>
                        <a class="slide-button btn"></a>
                    </span>
                    '.($help ? '<p class="help-block">'.$help.'</p>' : '').'
                </div>
            </div>';
    }

    private function displayFlagsFor($item, $div_lang_name)
    {
        $languages = Language::getLanguages(true);
        return $this->displayFlags($languages, $this->langue_default_store, $div_lang_name, $item, true);
    }

    private function displayFormNews()
    {
        $html_libre = '';
        $config_theme = $this->getConfigXmlTheme(self::getT());

        $dl = $this->langue_default_store;
        $languages = Language::getLanguages(true);
        $iso = Language::getIsoById((int)$this->context->language->id);
        $div_lang_name = 'title¤link_rewrite¤meta_title¤meta_description¤meta_keywords¤cpara1¤cpara2';

        $legend_title = $this->l('Add news');
        if (Tools::getValue('idN')) {
            $news = new NewsClass((int)Tools::getValue('idN'));
            $lang_liste_news = unserialize($news->langues);
            $legend_title = $this->l('Edit news').' #'.$news->id;
        } else {
            $news = new NewsClass();
        }

        if (Tools::isSubmit('submitUpdateNews') || Tools::isSubmit('submitAddNews')) {
            $news->id_shop = (int)$this->context->shop->id;
            $news->copyFromPost();
        }

        $path_langs_iso_tinymce = _PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js';

        $this->loadJsForTiny();

        $iso_tiny_mce = (file_exists($path_langs_iso_tinymce) ? $iso : 'en');
        $ad = dirname($_SERVER['PHP_SELF']);

        $allow_accents_js = 'var PS_ALLOW_ACCENTED_CHARS_URL = 0;';
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            $allow_accents_js = 'var PS_ALLOW_ACCENTED_CHARS_URL = 1;';
        }

        $this->html_out .= '
            <script type="text/javascript">
                '.$allow_accents_js.'
                var iso = \''.$iso_tiny_mce.'\' ;
                var pathCSS = \''._THEME_CSS_DIR_.'\' ;
                var ad = \''.$ad.'\' ;
                id_language = Number('.$dl.');
            </script>';

        $js3 = '$(\'#slink_rewrite_\'+id_language)';
        $js3 .= '.val(str2url($(\'input#title_\'+id_language).val().replace(/^[0-9]+\./, \'\'), \'UTF-8\'));';

        $js4 = '$(\'#slink_rewrite_\'+id_language)';
        $js4 .= '.val(str2url($(\'#slink_rewrite_\'+id_language).val().replace(/^[0-9]+\./, \'\'), \'UTF-8\'));';

        $this->html_out .= '
            <script type="text/javascript">
                function copy2friendlyURLPrestaBlog() {
                    if (!$(\'#slink_rewrite_\'+id_language).attr(\'disabled\')) {
                        '.$js3.'
                    }
                }
                function updateFriendlyURLPrestaBlog() {
                    '.$js4.'
                }

                function RetourLangueCheckUp(ArrayCheckedLang, idLangEnCheck, idLangDefaut) {
                    if (ArrayCheckedLang.length > 0)
                        return ArrayCheckedLang[0];
                    else
                        return idLangDefaut;
                }

                $(function() { ';

        $chlang = 'changeTheLanguage';

        if (Tools::getValue('idN')) {
            if (!Tools::getValue('languesup') && count($lang_liste_news) == 1) {
                $this->html_out .= $chlang.'(\'title\', \''.$div_lang_name.'\', '.(int)$lang_liste_news[0].', \'\');';
            }
        } else {
            $array_check_lang = array();
            if (Tools::getValue('languesup')) {
                $array_check_lang = Tools::getValue('languesup');
            }

            if (count($array_check_lang) == 1) {
                $this->html_out .= $chlang.'(\'title\', \''.$div_lang_name.'\', '.(int)$array_check_lang[0].', \'\');';
            } else {
                $this->html_out .= $chlang.'(\'title\', \''.$div_lang_name.'\', '.(int)$dl.', \'\');';
            }
        }

        $retl = 'RetourLangueCheckUp(selectedL, this.value, '.$dl.')';

        $this->html_out .= '
            $(".catlang").hide();
            $(".catlang[rel="+id_language+"]").show();

            $("div.language_flags img, #check_lang_prestablog img").click(function() {
                $(".catlang").hide();
                $(".catlang[rel="+id_language+"]").show();
                $("#imgCatLang").attr("src", "../img/l/" + id_language + ".jpg");
            });

            $("input[name=\'languesup[]\']").click(function() {
                if (this.checked)
                    '.$chlang.'(\'title\', \''.$div_lang_name.'\', this.value, \'\');
                else {
                    selectedL = new Array();
                    $("input[name=\'languesup[]\']:checked").each(function() {selectedL.push($(this).val());});
                    '.$chlang.'(\'title\', \''.$div_lang_name.'\', '.$retl.', \'\');
                }
            });

            $("form[name=formWithSelectLang]").submit(function() {';

        foreach ($languages as $language) {
            $this->html_out .= '$(\'#slink_rewrite_'.$language['id_lang'].'\').removeAttr("disabled");';
        }

        $this->html_out .= '
                selectedLangues = new Array();
                $("input[name=\'languesup[]\']:checked").each(function() {selectedLangues.push($(this).val());});

                if (selectedLangues.length == 0) {
                    alert("'.$this->l('You must choose at least one language !').'");
                    $("html, body").animate({scrollTop: $("#menu_config_prestablog").offset().top}, 300);
                    $("#check_lang_prestablog").css("background-color", "#FFA300");
                    return false;
                } else return true;
            });

            $("#control").toggle(
                function () {
                    $(\'#slink_rewrite_\'+id_language).removeAttr("disabled");
                    $(\'#slink_rewrite_\'+id_language).css("background-color", "#fff");
                    $(\'#slink_rewrite_\'+id_language).css("color", "#000");
                    $(this).html("'.$this->l('Disable this rewrite').'");
                },
                function () {
                    $(\'#slink_rewrite_\'+id_language).attr("disabled", true);
                    $(\'#slink_rewrite_\'+id_language).css("background-color", "#e0e0e0");
                    $(\'#slink_rewrite_\'+id_language).css("color", "#7F7F7F");
                    $(this).html("'.$this->l('Enable this rewrite').'");
                }
            );';

        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $this->html_out .= '
                if ($("#slink_rewrite_'.$lid.'").val() == \'\') {
                    $("#slink_rewrite_'.$lid.'").removeAttr("disabled");
                    $("#slink_rewrite_'.$lid.'").css("background-color", "#fff");
                    $("#slink_rewrite_'.$lid.'").css("color", "#000");
                    $("#control").html("'.$this->l('Disable this rewrite').'");
                }

                $("#paragraph_'.$lid.'").keyup(function(){
                    var limit = parseInt($(this).attr("maxlength"));
                    var text = $(this).val();
                    var chars = text.length;
                    if (chars > limit){
                        var new_text = text.substr(0, limit);
                        $(this).val(new_text);
                    }
                    $("#compteur-texte-'.$lid.'").html(chars+" / "+limit);
                });';
        }

        $resultprodjs = '<tr><td colspan="4" class="center">'.$this->l('You must search before');
        $resultprodjs .= ' ('.(int)Configuration::get($this->name.'_nb_car_min_linkprod');
        $resultprodjs .= ' '.$this->l('caract. minimum').')</td></tr>';

        $resultarticlejs = '<tr><td colspan="4" class="center">'.$this->l('You must search before');
        $resultarticlejs .= ' ('.(int)Configuration::get($this->name.'_nb_car_min_linknews');
        $resultarticlejs .= ' '.$this->l('caract. minimum').')</td></tr>';

        $resultlookbookjs = '<tr><td colspan="4" class="center">'.$this->l('You must search before');
        $resultlookbookjs .= ' ('.(int)Configuration::get($this->name.'_nb_car_min_linklb');
        $resultlookbookjs .= ' '.$this->l('caract. minimum').')</td></tr>';

        $this->html_out .= '
                $("#productLinkSearch").bind("keyup click focusin", function() {
                    ReloadLinkedSearchProducts();
                });
                ReloadLinkedProducts();

                $("#articleLinkSearch").bind("keyup click focusin", function() {
                    ReloadLinkedSearchArticles();
                });
                ReloadLinkedArticles();

                $("#lookbookLinkSearch").bind("keyup click focusin", function() {
                    ReloadLinkedSearchLookbooks();
                });
                ReloadLinkedLookbooks();
            });

            function ReloadLinkedSearchProducts(start) {
                var listLinkedProducts = \'\';
                $("input[name^=productsLink]").each(function() {
                    listLinkedProducts += $(this).val() + ";";
                });

                if ($("#productLinkSearch").val() != \'\' && $("#productLinkSearch").val().length >= '
                    .(int)Configuration::get($this->name.'_nb_car_min_linkprod').') {
                    $.ajax({
                        url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                        type: "GET",
                        data: {
                            ajax: true,
                            action: \'prestablogrun\',
                            do: \'searchProducts\',
                            listLinkedProducts: listLinkedProducts,
                            start: start,
                            req: $("#productLinkSearch").attr("value"),
                            id_shop: \''.$this->context->shop->id.'\'
                        },
                        success:function(data){
                            $("#productLinkResult").empty();
                            $("#productLinkResult").append(data);
                        }
                    });
                } else {
                    $("#productLinkResult").empty();
                    $("#productLinkResult").append(\''.$resultprodjs.'\');
                }
            }

            function ReloadLinkedSearchArticles(start) {
                var listLinkedArticles = \'\';
                $("input[name^=articlesLink]").each(function() {
                    listLinkedArticles += $(this).val() + ";";
                });

                if ($("#articleLinkSearch").val() != \'\' && $("#articleLinkSearch").val().length >= '
                    .(int)Configuration::get($this->name.'_nb_car_min_linknews').') {
                    $.ajax({
                        url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                        type: "GET",
                        data: {
                            ajax: true,
                            action: \'prestablogrun\',
                            do: \'searchArticles\',
                            listLinkedArticles: listLinkedArticles,
                            start: start,
                            req: $("#articleLinkSearch").attr("value"),
                            id_shop: \''.$this->context->shop->id.'\'
                        },
                        success:function(data){
                            $("#articleLinkResult").empty();
                            $("#articleLinkResult").append(data);
                        }
                    });
                } else {
                    $("#articleLinkResult").empty();
                    $("#articleLinkResult").append(\''.$resultarticlejs.'\');
                }
            }

            function ReloadLinkedSearchLookbooks(start) {
                var listLinkedLookbooks = \'\';
                $("input[name^=lookbooksLink]").each(function() {
                    listLinkedLookbooks += $(this).val() + ";";
                });

                if ($("#lookbookLinkSearch").val() != \'\' && $("#lookbookLinkSearch").val().length >= '
                    .(int)Configuration::get($this->name.'_nb_car_min_linklb').') {
                    $.ajax({
                        url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                        type: "GET",
                        data: {
                            ajax: true,
                            action: \'prestablogrun\',
                            do: \'searchLookbooks\',
                            listLinkedLookbooks: listLinkedLookbooks,
                            start: start,
                            req: $("#lookbookLinkSearch").attr("value"),
                            id_shop: \''.$this->context->shop->id.'\'
                        },
                        success:function(data){
                            $("#lookbookLinkResult").empty();
                            $("#lookbookLinkResult").append(data);
                        }
                    });
                } else {
                    $("#lookbookLinkResult").empty();
                    $("#lookbookLinkResult").append(\''.$resultlookbookjs.'\');
                }
            }

            function ReloadLinkedProducts() {
                var req = \'\';
                $("input[name^=productsLink]").each(function() {
                    req += $(this).val() + ";";
                });
                $.ajax({
                    url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                    type: "GET",
                    data: {
                        ajax: true,
                        action: \'prestablogrun\',
                        do: \'loadProductsLink\',
                        req: req,
                        id_shop: \''.$this->context->shop->id.'\'
                    },
                    success:function(data){
                        $("#productLinked").empty();
                        $("#productLinked").append(data);
                    }
                });
            }

            function ReloadLinkedArticles() {
                var req = \'\';
                $("input[name^=articlesLink]").each(function() {
                    req += $(this).val() + ";";
                });
                $.ajax({
                    url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                    type: "GET",
                    data: {
                        ajax: true,
                        action: \'prestablogrun\',
                        do: \'loadArticlesLink\',
                        req: req,
                        id_shop: \''.$this->context->shop->id.'\'
                    },
                    success:function(data){
                        $("#articleLinked").empty();
                        $("#articleLinked").append(data);
                    }
                });
            }

            function ReloadLinkedLookbooks() {
                var req = \'\';
                $("input[name^=lookbooksLink]").each(function() {
                    req += $(this).val() + ";";
                });
                $.ajax({
                    url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                    type: "GET",
                    data: {
                        ajax: true,
                        action: \'prestablogrun\',
                        do: \'loadLookbooksLink\',
                        req: req,
                        id_shop: \''.$this->context->shop->id.'\'
                    },
                    success:function(data){
                        $("#lookbookLinked").empty();
                        $("#lookbookLinked").append(data);
                    }
                });
            }

            function changeTheLanguage(title, divLangName, id_lang, iso) {
                $("#imgCatLang").attr("src", "../img/l/" + id_lang + ".jpg");
                return changeLanguage(title, divLangName, id_lang, iso);
            }
            </script>';

        $this->html_out .= $this->displayFormOpen(
            'icon-edit',
            $legend_title,
            $this->confpath,
            'formWithSelectLang'
        );

        if (Tools::getValue('idN')) {
            $this->html_out .= '<input type="hidden" name="idN" value="'.Tools::getValue('idN').'" />';

            $languages_shop = array();
            foreach (Language::getLanguages() as $value) {
                $languages_shop[$value['id_lang']] = $value['iso_code'];
            }

            foreach ($lang_liste_news as $val_langue) {
                if (count($languages) >= 1 && array_key_exists((int)$val_langue, $languages_shop)) {
                    $html_libre .= '
                        <a target="_blank" href="'.PrestaBlog::prestablogUrl(array(
                            'id' => (int)$news->id,
                            'seo' => $news->link_rewrite[(int)$val_langue],
                            'titre' => $news->title[(int)$val_langue],
                            'id_lang' => (int)$val_langue,
                        )).self::accurl().'preview='.$this->generateToken((int)$news->id).'" class="indent-right">
                            <img src="../img/l/'.(int)$val_langue.'.jpg" />
                            <img src="'.self::imgPathFO().'preview.gif" />
                        </a>';
                }
            }
            $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Preview'), $html_libre, 'col-lg-7');
        }

        //***********************************************************
        $html_libre = '<span id="check_lang_prestablog">';
        if (!isset($languages[1])) {
            $html_libre .= '';
        } else {
            $html_libre .= '
                <input type="checkbox"
                    name="checkmelang"
                    class="noborder"
                    onclick="checkDelBoxes(this.form, \'languesup[]\', this.checked)" />
                    '.$this->l('All').' | ';
        }

        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '<input type="checkbox" name="languesup[]" value="'.$lid.'"';
            if ((Tools::getValue('idN') && in_array((int)$lid, $lang_liste_news))
                || (Tools::getValue('languesup')
                    && in_array((int)$lid, Tools::getValue('languesup')))
                || ((!Tools::getValue('idN') &&
                    !Tools::getValue('languesup'))
                    && ((int)$lid == (int)$dl))
            ) {
                $html_libre .= ' checked=checked';
            }

            $html_libre .= ' ';
            $html_libre .= (count($languages) == 1 ? 'style="display:none;"' : '');
            $html_libre .= ' />
                <img src="../img/l/'.(int)$lid.'.jpg"
                    class="pointer indent-right"
                    alt="'.$language['name'].'"
                    title="'.$language['name'].'"
                    onclick="changeTheLanguage(
                        \'title\',
                        \''.$div_lang_name.'\',
                        '.$lid.',
                        \''.$language['iso_code'].'\'
                    );"
                />';
        }

        $html_libre .= '</span>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Language'), $html_libre, 'col-lg-7');
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="title_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input type="text" name="title_'.$lid.'"
                        id="title_'.$lid.'"
                        maxlength="'.(int)Configuration::get('prestablog_news_title_length').'"
                        value="'.(isset($news->title[$lid]) ? $news->title[$lid] : '').'"
                        onkeyup="if (isArrowKey(event)) return; copy2friendlyURLPrestaBlog();"
                        onchange="copy2friendlyURLPrestaBlog();"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Main title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('title', $div_lang_name)
        );


        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem('col-lg-2', $this->l('Activate'), 'actif', $news->actif);
        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem('col-lg-2', $this->l('Prestablog slide'), 'slide', $news->slide);
        $layerslider = Module::GetInstanceByName('layerslider');
        if ($layerslider) {
      $this->html_out .= $this->displayInfo($this->l('In order to display the slides from Creative Slider anywhere you want in the article, please paste the shortcode of the slide you want from the Creative Slider module in your content.'));
  }
        //***********************************************************

        if (Tools::getValue('idN')) {
            $comments_actif = CommentNewsClass::getListe(1, $news->id);
            $comments_all = CommentNewsClass::getListe(-2, $news->id);
            $comments_non_lu = CommentNewsClass::getListe(-1, $news->id);
            $comments_disabled = CommentNewsClass::getListe(0, $news->id);

            $html_libre = '
                <div id="labelComments">
                    '.((count($comments_all) > 0) ? '<strong>'.count($comments_actif).'</strong>
                    '.$this->l('approuved').' '.$this->l('of').'
                    <strong>'.count($comments_all).'</strong>
                    ' : $this->l('No comment')).((count($comments_non_lu) > 0) ? '&nbsp;&mdash;-&nbsp;
                    <span style="color:green;font-weight:bold;">
                        '.count($comments_non_lu).' '.$this->l('Comments pending').'
                    </span>' : '').'<br />
                    '.((count($comments_all) > 0) ? '<span onclick="$(\'#comments\').slideToggle();"
                        style="cursor: pointer"
                        class="link">
                            <img src="'.self::imgPathFO().'cog.gif"
                                alt="'.$this->l('Comments').'"
                                title="'.$this->l('Comments').'"
                            />
                        '.$this->l('Click here to manage comments').'
                    </span>' : '').'
                </div>'."\n";

            $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Comments'), $html_libre, 'col-lg-10');

            if (count($comments_all) > 0) {
                $html_libre = '';
                if (Tools::isSubmit('showComments')) {
                    $html_libre .= '<div id="comments">'."\n";
                    $html_libre .= '
                        <script type="text/javascript">
                            $(document).ready(function() {
                                $("html, body").animate({scrollTop: $("#labelComments").offset().top}, 750);
                            });
                        </script>'."\n";
                } else {
                    $html_libre .= '
                        <div
                            id="comments"
                            style="'.(Configuration::get($this->name.'_comment_div_visible') ? '' : 'display: none;').'"
                        >'."\n";
                }

                $html_libre .= '
                    <div class="blocs col-sm-4">
                        <h3>
                            <img
                                src="'.self::imgPathFO().'question.gif"
                                alt="'.$this->l('Pending').'"
                                title="'.$this->l('Pending').'"
                            />
                            '.count($comments_non_lu).'&nbsp;'.$this->l('Comments pending').'
                        </h3>'."\n";

                if (count($comments_non_lu) > 0) {
                    $html_libre .= '<div class="wrap">'."\n";
                    foreach ($comments_non_lu as $value_c) {
                        $ur = '&idN='.Tools::getValue('idN').'&idC='.$value_c['id_prestablog_commentnews'];
                        $html_libre .= '<div>'."\n";
                        $html_libre .= '
                            <h4>
                                <a
                                    href="'.$this->confpath.'&deleteComment'.$ur.'"
                                    class="hrefComment"
                                    onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                                    style="float:right;"
                                >
                                    <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'" />
                                    <span style="display:none;">'.$this->l('Delete').'</span>
                                </a>
                                '.ToolsCore::displayDate($value_c['date'], null, true).'<br />'.$this->l('by').'
                                <strong>'.$value_c['name'].'</strong>
                            </h4>'."\n";

                        if ($value_c['url'] != '') {
                            $html_libre .= '
                                <h5>
                                    <a href="'.$value_c['url'].'" target="_blank">'.$value_c['url'].'</a>
                                </h5>'."\n";
                        }

                        $html_libre .= '  <p>'.$value_c['comment'].'</p>'."\n";
                        $html_libre .= '
                            <p class="center">
                                <a
                                    href="'.$this->confpath.'&enabledComment'.$ur.'"
                                    class="hrefComment"
                                >
                                    <img
                                        src="'.self::imgPathFO().'enabled.gif"
                                        alt="'.$this->l('Approuved').'"
                                    />
                                    <span style="display:none;">'.$this->l('Approuved').'</span>
                                </a>
                                <a
                                    href="'.$this->confpath.'&editComment&idC='.$value_c['id_prestablog_commentnews'].'"
                                    class="hrefComment"
                                >
                                    <img src="'.self::imgPathFO().'edit.gif" />
                                    <span style="display:none;">'.$this->l('Edit').'</span>
                                </a>
                                <a
                                    href="'.$this->confpath.'&disabledComment'.$ur.'"
                                    class="hrefComment"
                                >
                                    <img
                                        src="'.self::imgPathFO().'disabled.gif"
                                        alt="'.$this->l('Disabled').'"
                                    />
                                    <span style="display:none;">'.$this->l('Disabled').'</span>
                                </a>
                            </p>'."\n";
                        $html_libre .= '</div>'."\n";
                    }
                    $html_libre .= '</div>'."\n";
                }
                $html_libre .= '</div>'."\n";

                $html_libre .= '
                    <div class="blocs col-sm-4">
                        <h3>
                            <img src="'.self::imgPathFO().'enabled.gif"
                                alt="'.$this->l('Approuved').'" title="'.$this->l('Approuved').'"
                            />'.count($comments_actif).'&nbsp;'.$this->l('Comments approuved').'
                        </h3>'."\n";

             if (count($comments_actif) > 0) {
                    $html_libre .= '<div class="wrap">'."\n";
                    foreach ($comments_actif as $value_c) {
                        $ur = '&idN='.Tools::getValue('idN').'&idC='.$value_c['id_prestablog_commentnews'];
                        $html_libre .= '<div>'."\n";
                        $html_libre .= '
                            <h4>
                                <a
                                    href="'.$this->confpath.'&deleteComment'.$ur.'"
                                    class="hrefComment"
                                    onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                                    style="float:right;"
                                >
                                    <img src="'.self::imgPathFO().'delete.gif" alt="'.$this->l('Delete').'"/>
                                    <span style="display:none;">'.$this->l('Delete').'</span>
                                </a>'.ToolsCore::displayDate($value_c['date'], null, true).'<br />'.$this->l('by').'
                                <strong>'.$value_c['name'].'</strong>
                            </h4>'."\n";

                        if ($value_c['url'] != '') {
                            $html_libre .= '
                                <h5>
                                    <a href="'.$value_c['url'].'" target="_blank">'.$value_c['url'].'</a>
                                </h5>'."\n";
                        }

                        $html_libre .= '  <p>'.$value_c['comment'].'</p>'."\n";
                        $html_libre .= '
                            <p class="center">
                                <a
                                    href="'.$this->confpath.'&editComment&idC='.$value_c['id_prestablog_commentnews'].'"
                                    class="hrefComment"
                                >
                                    <img src="'.self::imgPathFO().'edit.gif" />
                                    <span style="display:none;">'.$this->l('Edit').'</span>
                                </a>
                                <a
                                    href="'.$this->confpath.'&disabledComment'.$ur.'"
                                    class="hrefComment"
                                >
                                    <img
                                        src="'.self::imgPathFO().'disabled.gif"
                                        alt="'.$this->l('Deleted').'"
                                    />
                                    <span style="display:none;">'.$this->l('Disabled').'</span>
                                </a>
                            </p>'."\n";
                        $html_libre .= '</div>'."\n";
                    }
                    $html_libre .= '</div>'."\n";
                }
                $html_libre .= '
                    </div>'."\n";

                $html_libre .= '
                    <div class="blocs col-sm-3">
                        <h3>
                            <img
                                src="'.self::imgPathFO().'disabled.gif" alt="'
                                .$this->l('Disabled').'" title="'.$this->l('Disabled').'"
                            />
                            '.count($comments_disabled).'&nbsp;'.$this->l('Comments disabled').'
                        </h3>'."\n";

                if (count($comments_disabled) > 0) {
                    $html_libre .= '<div class="wrap">'."\n";
                    foreach ($comments_disabled as $value_c) {
                        $ur = '&idN='.Tools::getValue('idN').'&idC='.$value_c['id_prestablog_commentnews'];
                        $html_libre .= '<div>'."\n";
                        $html_libre .= '
                            <h4>
                                <a
                                    href="'.$this->confpath.'&deleteComment'.$ur.'"
                                    class="hrefComment"
                                    onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                                    style="float:right;"
                                >
                                    <img
                                        src="'.self::imgPathFO().'delete.gif"
                                        alt="'.$this->l('Delete').'"
                                    />
                                    <span style="display:none;">'.$this->l('Delete').'</span>
                                </a>
                                '.ToolsCore::displayDate($value_c['date'], null, true).'<br />'.$this->l('by').'
                                <strong>'.$value_c['name'].'</strong>
                            </h4>'."\n";
                        if ($value_c['url'] != '') {
                            $html_libre .= '
                                <h5>
                                    <a href="'.$value_c['url'].'" target="_blank">'.$value_c['url'].'</a>
                                </h5>'."\n";
                        }
                        $html_libre .= '  <p>'.$value_c['comment'].'</p>'."\n";
                        $html_libre .= '
                            <p class="center">
                                <a
                                    href="'.$this->confpath.'&editComment&idC='.$value_c['id_prestablog_commentnews'].'"
                                    class="hrefComment"
                                >
                                    <img src="'.self::imgPathFO().'edit.gif" />
                                    <span style="display:none;">'.$this->l('Edit').'</span>
                                </a>
                                <a
                                    href="'.$this->confpath.'&enabledComment'.$ur.'"
                                    class="hrefComment"
                                >
                                    <img
                                        src="'.self::imgPathFO().'enabled.gif"
                                        alt="'.$this->l('Enabled').'"
                                    />
                                    <span style="display:none;">'.$this->l('Approuved').'</span>
                                </a>
                            </p>'."\n";
                        $html_libre .= '</div>'."\n";
                    }
                    $html_libre .= '</div>'."\n";
                }
                $html_libre .= '</div>'."\n";

                $html_libre .= '
                    </div>
                    <div class="clear"></div>'."\n";
                $html_libre .= '
                    <script type="text/javascript">
                        $(document).ready(function() {
                            $("a.hrefComment").mouseenter(function() {
                                $("span:first", this).show(\'slow\');
                            }).mouseleave(function() {
                                $("span:first", this).hide();
                            });
                        });
                    </script>'."\n";

                $this->html_out .= $this->displayFormLibre('col-lg-2', '', $html_libre, 'col-lg-10');
            }
        }
        //***********************************************************
        /* DEBUT SEO */
        $html_libre = '
            <span onclick="$(\'#seo\').slideToggle();" style="cursor: pointer" class="link">
                <img src="'.self::imgPathFO().'cog.gif"
                    alt="'.$this->l('SEO').'"
                    title="'.$this->l('SEO').'"
                />'
                .$this->l('Click here to improve SEO').'
            </span>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('SEO'), $html_libre, 'col-lg-7');

        $this->html_out .= '<div id="seo" style="display: none;">';
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="link_rewrite_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input type="text"
                        name="link_rewrite_'.$lid.'"
                        id="slink_rewrite_'.$lid.'"
                        value="'.(isset($news->link_rewrite[$lid]) ? $news->link_rewrite[$lid] : '').'"
                        onkeyup="if (isArrowKey(event)) return ;updateFriendlyURLPrestaBlog();"
                        onchange="updateFriendlyURLPrestaBlog();"
                        '.(isset($news->id) ? ' style="color:#7F7F7F;background-color:#e0e0e0;" disabled="true"' :'').'
                    />
                </div>';
        }
        $urlrw = $this->l('Url Rewrite').'<br/><a href="#" id="control" />';
        $urlrw .= (isset($news->id) ? $this->l('Enable this rewrite') : $this->l('Disable this rewrite')).'</a>';

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $urlrw,
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('link_rewrite', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="meta_title_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_title_'.$lid.'"
                        id="meta_title_'.$lid.'"
                        value="'.(isset($news->meta_title[$lid]) ? $news->meta_title[$lid] : '').'"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Meta Title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_title', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div id="meta_description_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_description_'.$lid.'"
                        id="meta_description_'.$lid.'"
                        value="'.(isset($news->meta_description[$lid]) ? $news->meta_description[$lid] : '').'"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Meta Description'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_description', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div id="meta_keywords_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_keywords_'.$lid.'"
                        id="meta_keywords_'.$lid.'"
                        value="'.(isset($news->meta_keywords[$lid]) ? $news->meta_keywords[$lid] : '').'"
                    />
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Meta Keywords'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_keywords', $div_lang_name)
        );
        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Permanent redirect url'),
            'url_redirect',
            $news->url_redirect,
            null,
            'col-lg-7',
            $this->l('Advanced user only'),
            $this->l('Completed url with http://').'<br/>'.sprintf(
                $this->l('This feature will redirect %1$s to this url with a redirect 301'),
                '<strong>'.$news->title[$lid].'</strong>'
            ),
            '<i class="icon-external-link"></i>'
        );
        //***********************************************************
        $this->html_out .= '</div>';
        /* FIN SEO */
        //***********************************************************

        //***********************************************************
        /* DEBUT IMAGE */
        $html_libre = '';
        if ($this->demo_mode) {
            $html_libre .= $this->displayWarning($this->l('Feature disabled on the demo mode'));
        }
        if (Tools::getValue('idN')
            && file_exists(
                self::imgUpPath().'/admincrop_'.Tools::getValue('idN').'.jpg'
            )
        ) {
            $html_libre .= '<span id="labelPicture"></span>';
            $config_theme_array = PrestaBlog::objectToArray($config_theme);
            if (Tools::getValue('pfx')) {
                $html_libre .= '
                    <script type="text/javascript">
                        $(document).ready(function() {
                            $("html, body").animate({scrollTop: $("#labelPicture").offset().top}, 750);
                        });
                    </script>'."\n";
            }

            $html_libre .= '
                <script src="'.__PS_BASE_URI__.'modules/prestablog/views/js/jquery.Jcrop.prestablog.js"></script>
                <link rel="stylesheet"
                    href="'.__PS_BASE_URI__.'modules/prestablog/views/css/jquery.Jcrop.css"
                    type="text/css"
                />'."\n";

            $html_libre .= '<script language="Javascript">'."\n";
            $html_libre .= '  var ratioValue = new Array();'."\n";
            foreach ($config_theme_array['images'] as $key_theme_array => $value_theme_array) {
                $html_libre .= '  ratioValue[\''.$key_theme_array.'\'] = ';
                $html_libre .= (int)$value_theme_array['width'] / (int)$value_theme_array['height'].';'."\n";
            }

            $html_libre .= '
                var monRatio;
                var monImage;
                $(function() {
                    $("div.togglePreview").hide();'."\n";

            if (Tools::getValue('pfx')) {
                $html_libre .= '
                    $(\'input[name$="imageChoix"]\').filter(
                        \'[value="'.Tools::getValue('pfx').'"]\'
                    ).attr(\'checked\', true);
                    $(\'input[name$="imageChoix"]\').filter(
                        \'[value="'.Tools::getValue('pfx').'"]\'
                    ).parent().next(1).slideDown();
                    $("#pfx").val(\''.Tools::getValue('pfx').'\');
                    $("#ratio").val(ratioValue[\''.Tools::getValue('pfx').'\']);
                    monRatio = ratioValue[\''.Tools::getValue('pfx').'\'];
                    $(\'#cropbox\').Jcrop({
                    	\'minSize\' : monRatio,
                        \'onSelect\' : updateCoords
                    });
                    nomImage = \''.$this->l('Resize').' '.Tools::getValue('pfx').'\';
                    $("#resizeText").html(nomImage);'."\n";
            }

            $html_libre .= '
                    $(\'input[name$="imageChoix"]\').change(function () {
                        $("div.togglePreview").slideUp();
                        $(this).parent().next().slideDown();
                        $("#pfx").val($(this).val());
                        $("#ratio").val(ratioValue[$(this).val()]);
                        monRatio = ratioValue[$(this).val()];
                        $(\'#cropbox\').Jcrop({
                            \'minSize\' : monRatio,
                            \'onSelect\' : updateCoords
                        });
                        nomImage = \''.$this->l('Resize').' \'+$("#pfx").val();
                        $("#resizeText").html(nomImage);
                    });
                });

                function updateCoords(c) {
                    $(\'#x\').val(c.x);
                    $(\'#y\').val(c.y);
                    $(\'#w\').val(c.w);
                    $(\'#h\').val(c.h);
                };
                function checkCoords() {
                    if (!$(\'input[name="imageChoix"]:checked\').val()) {
                        alert(\''.$this->l('Please select a picture to crop.').'\');
                        return false;
                    } else {
                        if (parseInt($(\'#w\').val()))
                            return true;
                        alert(\''.$this->l('Please select a crop region then press submit.').'\');
                        return false;
                    }
                };
            </script>';

            $imgcrop = self::imgPathBO().self::getT().'/up-img/';
            $imgcrop .= 'admincrop_'.Tools::getValue('idN').'.jpg?'.md5(time());
            $sizecrop = filesize(self::imgPath().self::getT().'/up-img/'.Tools::getValue('idN').'.jpg');

            $html_libre .= '
                    <div id="image" class="col-md-7">
                        <div class="blocmodule">
                            <img
                                id="cropbox"
                                src="'.$imgcrop.'"
                            />
                            <p align="center">
                                '.$this->l('Filesize').'
                                '.($sizecrop / 1000).'kb
                            </p>
                            <p>
                                <a
                                    href="'.$this->confpath.'&deleteImageBlog&idN='.Tools::getValue('idN').'"
                                    onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                                >
                                    <img
                                        src="'.self::imgPathFO().'delete.gif"
                                        alt="'.$this->l('Delete').'"
                                    />
                                    '.$this->l('Delete').'
                                </a>
                            </p>
                            <p>';
            $html_libre .= $this->displayFormFileNoLabel(
                'homepage_logo',
                'col-lg-10',
                $this->l('Format:').' .jpg .png'
            );
            $html_libre .= '
                            </p>
                        </div>
                    </div>';

            $html_libre .= '<div class="col-md-5">'."\n";

            foreach ($config_theme_array['images'] as $key_theme_array => $value_theme_array) {
                $width_force = '';
                if (file_exists(self::imgUpPath().'/'.$key_theme_array.'_'.Tools::getValue('idN').'.jpg')) {
                    $attrib_image = getimagesize(
                        self::imgUpPath().'/'.$key_theme_array.'_'.Tools::getValue('idN').'.jpg'
                    );
                    if ((int)$attrib_image[0] > 200) {
                        $width_force = 'width="200"';
                    }
                }

                $label_pic = $key_theme_array;
                switch ($key_theme_array) {
                    case 'thumb':
                        $label_pic = $this->l('thumb for articles list');
                        break;
                    case 'slide':
                        $label_pic = $this->l('slide picture (home / blog page)');
                        break;
                }

                $imgkt = self::imgPathBO().self::getT().'/up-img/';
                $imgkt .= $key_theme_array.'_'.Tools::getValue('idN').'.jpg?'.md5(time());

                $html_libre .= '
                    <div class="blocmodule">
                        <p>
                            <input type="radio" name="imageChoix" value="'.$key_theme_array.'" />&nbsp;'.$label_pic.'
                            <span style="font-size: 80%;">
                                ('.($width_force ? $this->l('Real size : ') : '').'
                                '.(int)$value_theme_array['width'].' * '.(int)$value_theme_array['height'].')
                            </span>
                        </p>
                        <div class="togglePreview" style="text-align:center;">
                            <img
                                style="border:1px solid #4D4D4D;padding:0px;"
                                src="'.$imgkt.'"
                                '.$width_force.'
                            />
                        </div>
                    </div>'."\n";
            }

            $html_libre .= '
                <div class="blocmodule">
                    <a class="btn btn-default" onclick="if (checkCoords()) {formCrop.submit();}" >
                        <i class="icon-crop"></i>&nbsp;<span id="resizeText">'.$this->l('Resize').'</span>
                    </a>
                </div>'."\n";
            $html_libre .= '</div>'."\n";
        } else {
            $html_libre .= $this->displayFormFileNoLabel(
                'homepage_logo',
                'col-lg-5',
                $this->l('Format:').' .jpg .png'
            );
        }

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Main picture'), $html_libre, 'col-lg-10');
        /* FIN IMAGE */
        //***********************************************************

        //***********************************************************
        /* DEBUT INTRO */
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="cpara1_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <textarea maxlength="'.(int)Configuration::get('prestablog_news_intro_length').'"id="paragraph_'.$lid.'"name="paragraph_'.$lid.'">'.(isset($news->paragraph[$lid]) ? $news->paragraph[$lid] : '').'</textarea>
                    <p>
                        '.$this->l('Caracters remaining').' :
                        <span id="compteur-texte-'.$lid.'" style="color:red;">
                            '.Tools::strlen($news->paragraph[$lid]).' /
                            '.(int)Configuration::get('prestablog_news_intro_length').'
                        </span>
                        <br/>
                        '.$this->l('Configure the max length in the general configuration of the module theme.').'
                    </p>
            </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Introduction'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('cpara1', $div_lang_name)
        );
        /* FIN INTRO */
        //***********************************************************

        //***********************************************************
        /* DEBUT CONTENU */
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div id="cpara2_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <textarea class="rte autoload_rte"
                        id="content_'.$lid.'"
                        name="content_'.$lid.'"
                    >
                        '.(isset($news->content[$lid]) ? $news->content[$lid] : '').'
                    </textarea>
                </div>';
        }
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Content'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('cpara2', $div_lang_name)
        );
        /* FIN CONTENU */
        //***********************************************************

        //***********************************************************
        /* DEBUT CATEGORIES */
        $html_libre = '';
        $html_libre .= '
            <div class="blocmodule">
                <table cellspacing="0" cellpadding="0" class="table">
                    <thead>
                        <tr>
                            <th style="width:20px;">
                                <input type="checkbox"
                                    name="checkme"
                                    class="noborder"
                                    onclick="checkDelBoxes(this.form, \'categories[]\', this.checked)"
                                />
                            </th>
                            <th style="width:20px;">'.$this->l('ID').'</th>
                            <th style="width:60px;">'.$this->l('Image').'</th>
                            <th>
                                '.$this->l('Name').'
                                <img id="imgCatLang"
                                    src="../img/l/'.$dl.'.jpg"
                                    style="vertical-align:middle;"
                                />
                            </th>
                        </tr>
                    </thead>';

        $liste_cat = CategoriesClass::getListe((int)$this->context->language->id, 0);
        $liste_cat_no_arbre = CategoriesClass::getListeNoArbo();
        $liste_cat_branches_actives = array();



        foreach (CorrespondancesCategoriesClass::getCategoriesListe((int)$news->id) as $value) {
            $liste_cat_branches_actives = array_unique(array_merge(
                $liste_cat_branches_actives,
                preg_split('/\./', CategoriesClass::getBranche((int)$value))
            ));
        }


        $html_libre .= $this->displayListeArborescenceCategoriesNews($liste_cat, 0, $liste_cat_branches_actives);

        $html_libre .= '
                </table>
            </div>';
        $html_libre .= '
            <script language="javascript" type="text/javascript">
                $(document).ready(function() {';

        foreach ($liste_cat_branches_actives as $value) {
            $html_libre .= '$("tr#prestablog_categorie_'.$value.'").show();';
        }

        foreach ($liste_cat_no_arbre as $value) {
            if (in_array((int)$value['parent'], $liste_cat_branches_actives)) {
                $html_libre .= '$("tr#prestablog_categorie_'.$value['id_prestablog_categorie'].'").show();';
            }
        }

        $html_libre .= '
            $("img.expand-cat").click(function() {
                BranchClick=$(this).attr("rel");
                BranchClickSplit = BranchClick.split(\'.\');
                fixBranchClickSplit = "0,"+BranchClickSplit.toString();

                switch ($(this).attr("src")) {
                    case "/../modules/prestablog/views/img/expand.gif":
                        $("tr.prestablog_branch").each(function() {
                            BranchParent = $(this).attr("rel");
                            BranchParentSplit = BranchParent.split(\'.\');
                            fixBranchParentSplit = "0,"+BranchParentSplit.toString();

                            if ($.isSubstring(fixBranchParentSplit, fixBranchClickSplit)
                                    && BranchClick != BranchParent
                                    && BranchClickSplit.length+1 == BranchParentSplit.length
                                ) {
                                    $(this).show();
                            }
                        });
                        $(this).attr("src", "/../modules/prestablog/views/img/collapse.gif");
                        break;

                    case "/../modules/prestablog/views/img/collapse.gif":
                        $("tr.prestablog_branch").each(function() {
                            BranchParent = $(this).attr("rel");
                            BranchParentSplit = BranchParent.split(\'.\');
                            fixBranchParentSplit = "0,"+BranchParentSplit.toString();

                            if ($.isSubstring(fixBranchParentSplit, fixBranchClickSplit)
                                    && BranchClick != BranchParent
                                ) {
                                    $(this).hide();
                                    $(this).find("img.expand-cat").each(function() {
                                        $(this).attr("src", "/../modules/prestablog/views/img/expand.gif");
                                    });
                            }
                        });
                        $(this).attr("src", "/../modules/prestablog/views/img/expand.gif");
                        break;
                }
            });
            /*
            $("#submitForm").click(function() {
                var nombre = $(\'input[name="categories[]"]:checked\').length > 0;
                if(nombre)
            });
            */
            ';

        $html_libre .= '
                });
                jQuery.isSubstring = function(haystack, needle) {
                     return haystack.indexOf(needle) !== -1;
                };
            </script>';
        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Categories'), $html_libre, 'col-lg-5');
        /* FIN CATEGORIES */
        //***********************************************************

        //***********************************************************
        /* DEBUT PRODUITS LIES */
        $html_libre = '';
        $html_libre .= '<div id="currentProductLink" style="display:none;">'."\n";
        if (Tools::getValue('idN')) {
            $products_link = NewsClass::getProductLinkListe((int)Tools::getValue('idN'));

            if (count($products_link) > 0) {
                foreach ($products_link as $product_link) {
                    $html_libre .= '
                        <input type="text"
                            name="productsLink[]"
                            value="'.(int)$product_link.'"
                            class="linked_'.(int)$product_link.'"
                        />'."\n";
                }
            }
        }
        if (Tools::getValue('productsLink') && !Tools::getValue('idN')) {
            foreach (Tools::getValue('productsLink') as $product_link) {
                $html_libre .= '
                    <input type="text"
                        name="productsLink[]"
                        value="'.(int)$product_link['id_product'].'"
                        class="linked_'.(int)$product_link['id_product'].'"
                    />'."\n";
            }
        }
        $html_libre .= '</div>';

        $html_libre .= '<div class="blocmodule col-sm-4">';
        $html_libre .= '
            <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                        <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                        <th class="center">'.$this->l('Name').'</th>
                        <th class="center" style="width:40px;">'.$this->l('Unlink').'</th>
                    </tr>
                </thead>
                <tbody id="productLinked">
                    <tr>
                        <td colspan="4" class="center">'.$this->l('No product linked').'</td>
                    </tr>
                </tbody>
            </table>';
        $html_libre .= '</div>';

        $html_libre .= '<div class="col-sm-1"></div>';
        $html_libre .= '<div class="blocmodule col-sm-5">';
        $html_libre .= '
            <p class="center">
                '.$this->l('Search').' :
                <input
                    type="text"
                    size="20"
                    id="productLinkSearch"
                    name="productLinkSearch"
                    placeholder="'.sprintf($this->l('Keywords from %1$s or #id'), $this->l('Name')).'"
                />
            </p>
            <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th class="center" style="width:40px;">'.$this->l('Link').'</th>
                        <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                        <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                        <th class="center">'.$this->l('Name').'</th>
                    </tr>
                </thead>
                <tbody id="productLinkResult">
                    <tr>
                        <td colspan="4" class="center">
                            '.$this->l('You must search before').'(
                            '.(int)Configuration::get($this->name.'_nb_car_min_linkprod').'
                            '.$this->l('caract. minimum').'
                            )
                        </td>
                    </tr>
                </tbody>
            </table>';

        $html_libre .= '</div>';

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Related products'),
            $html_libre,
            'col-lg-10'
        );
        /* FIN PRODUITS LIES */
        //***********************************************************

        //***********************************************************
        /* DEBUT ARTICLES LIES */
        $html_libre = '';
        $html_libre .= '<div id="currentArticleLink" style="display:none;">'."\n";

        if (Tools::getValue('idN')) {
            $articles_link = NewsClass::getArticleLinkListe((int)Tools::getValue('idN'));
            if (count($articles_link) > 0) {
                foreach ($articles_link as $article_link) {
                    $html_libre .= '
                        <input type="text"
                            name="articlesLink[]"
                            value="'.(int)$article_link.'"
                            class="linked_'.(int)$article_link.'"
                        />'."\n";
                }
            }
        }
        if (Tools::getValue('articlesLink') && !Tools::getValue('idN')) {
            foreach (Tools::getValue('articlesLink') as $article_link) {
                $html_libre .= '
                    <input type="text"
                        name="articlesLink[]"
                        value="'.(int)$article_link['id_prestablog_news'].'"
                        class="linked_'.(int)$article_link['id_prestablog_news'].'"
                    />'."\n";
            }
        }

        $html_libre .= '</div>';

        $html_libre .= '<div class="blocmodule col-sm-4">';

        $html_libre .= '
            <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                        <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                        <th class="center">'.$this->l('Title').'</th>
                        <th class="center" style="width:40px;">'.$this->l('Unlink').'</th>
                    </tr>
                </thead>
                <tbody id="articleLinked">
                    <tr>
                        <td colspan="4" class="center">'.$this->l('No article linked').'</td>
                    </tr>
                </tbody>
            </table>';

        $html_libre .= '</div>';
        $html_libre .= '<div class="col-sm-1"></div>';
        $html_libre .= '<div class="blocmodule col-sm-5">';

        $html_libre .= '
            <p class="center">
                '.$this->l('Search').' :
                <input
                    type="text"
                    size="20"
                    id="articleLinkSearch"
                    name="articleLinkSearch"
                    placeholder="'.sprintf($this->l('Keywords from %1$s or #id'), $this->l('Title')).'"
                />
            </p>
            <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th class="center" style="width:40px;">'.$this->l('Link').'</th>
                        <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                        <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                        <th class="center">'.$this->l('Title').'</th>
                    </tr>
                </thead>
                <tbody id="articleLinkResult">
                    <tr>
                        <td colspan="4" class="center">
                            '.$this->l('You must search before').'(
                            '.(int)Configuration::get($this->name.'_nb_car_min_linknews').'
                            '.$this->l('caract. minimum').'
                            )
                        </td>
                    </tr>
                </tbody>
            </table>';

        $html_libre .= '</div>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Related Posts'), $html_libre, 'col-lg-10');
        /* FIN ARTICLES LIES */
        //***********************************************************

        /*
        /***********************************************************
        // DEBUT LOOKBOOKS LIES
        $html_libre = '';
        $html_libre .= '<div id="currentLookbookLink" style="display:none;">'."\n";

        if (Tools::getValue('idN')) {
            $lookbooks_link = NewsClass::getLookbookLinkListe((int)Tools::getValue('idN'));
            if (count($lookbooks_link) > 0) {
                foreach ($lookbooks_link as $lookbook_link) {
                    $html_libre .= '
                        <input type="text"
                            name="lookbooksLink[]"
                            value="'.(int)$lookbook_link.'"
                            class="linked_'.(int)$lookbook_link.'"
                        />'."\n";
                }
            }
        }
        if (Tools::getValue('lookbooksLink') && !Tools::getValue('idN')) {
            foreach (Tools::getValue('lookbooksLink') as $lookbook_link) {
                $html_libre .= '
                    <input type="text"
                        name="lookbooksLink[]"
                        value="'.(int)$lookbook_link['id_prestablog_news'].'"
                        class="linked_'.(int)$lookbook_link['id_prestablog_news'].'"
                    />'."\n";
            }

        }

        $html_libre .= '</div>';

        $html_libre .= '<div class="blocmodule col-sm-4">';

        $html_libre .= '
            <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                        <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                        <th class="center">'.$this->l('Title').'</th>
                        <th class="center" style="width:40px;">'.$this->l('Unlink').'</th>
                    </tr>
                </thead>
                <tbody id="lookbookLinked">
                    <tr>
                        <td colspan="4" class="center">'.$this->l('No lookbook linked').'</td>
                    </tr>
                </tbody>
            </table>';

        $html_libre .= '</div>';
        $html_libre .= '<div class="col-sm-1"></div>';
        $html_libre .= '<div class="blocmodule col-sm-5">';

        $html_libre .= '
            <p class="center">
                '.$this->l('Search').' :
                <input
                    type="text"
                    size="20"
                    id="lookbookLinkSearch"
                    name="lookbookLinkSearch"
                    placeholder="'.sprintf($this->l('Keywords from %1$s or #id'), $this->l('Title')).'"
                />
            </p>
            <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th class="center" style="width:40px;">'.$this->l('Link').'</th>
                        <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                        <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                        <th class="center">'.$this->l('Title').'</th>
                    </tr>
                </thead>
                <tbody id="lookbookLinkResult">
                    <tr>
                        <td colspan="4" class="center">
                            '.$this->l('You must search before').'(
                            '.(int)Configuration::get($this->name.'_nb_car_min_linklb').'
                            '.$this->l('caract. minimum').'
                            )
                        </td>
                    </tr>
                </tbody>
            </table>';

        $html_libre .= '</div>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Related Lookbooks'), $html_libre, 'col-lg-10');
        // FIN LOOKBOOKS LIES
        //***********************************************************
        */

        $this->html_out .= $this->displayFormDate('col-lg-2', $this->l('Date'), 'date', $news->date, true);
//***********************************************************
//DEBUT POPUP
$prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
	$popups_link[0] = 'None';
        foreach (PopupClass::getListePopup($lid) as $popup_link) {

            $popups_link[$popup_link['id_prestablog_popup']] = $popup_link['title'];

        }
$popuplink = NewsClass::getPopupLink($news->id);

if (isset($popuplink)) {
$this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Add a popup to your article :'),
            'popupLink',
            $popuplink,
            $popups_link,
            null,
            'col-lg-5'
        );

} else {
    $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Add a popup to your article :'),
            'popupLink',
            self::getP(),
            $popups_link,
            null,
            'col-lg-5'
        );
}
}
//FIN POPUP
//***********************************************************

        $this->html_out .= '<div class="margin-form">';

        if (Tools::getValue('idN')) {
            $this->html_out .= '
                <button class="btn btn-primary"
                    id="submitForm"
                    name="submitUpdateNews"
                    type="submit"
                >
                    <i class="icon-save"></i>
                    '.$this->l('Update').'
                </button>';
        } else {
            $this->html_out .= '
                <button class="btn btn-primary"
                    id="submitForm"
                    name="submitAddNews"
                    type="submit"
                >
                    <i class="icon-plus"></i>
                    '.$this->l('Add content').'
                </button>';
        }

        $this->html_out .= '</div>';

        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '
            <form name="formCrop"
                id="formCrop"
                action="'.$this->confpath.'"
                method="post"
                onsubmit="return checkCoords();"
            >
                <input type="hidden" name="idN" value="'.Tools::getValue('idN').'" />
                <input type="hidden" id="pfx" name="pfx" value="'.Tools::getValue('pfx').'" />
                <input type="hidden" id="x" name="x" />
                <input type="hidden" id="y" name="y" />
                <input type="hidden" id="w" name="w" />
                <input type="hidden" id="h" name="h" />
                <input type="hidden" id="ratio" name="ratio" />
                <input type="hidden" name="submitCrop" value="submitCrop" />
            </form>';
    }

    private function displayFormCategories()
    {
        $config_theme = $this->getConfigXmlTheme(self::getT());

        $dl = $this->langue_default_store;
        $languages = Language::getLanguages(true);
        $iso = Language::getIsoById((int)$this->context->language->id);
        $div_lang_name = 'title¤link_rewrite¤meta_title¤meta_description¤meta_keywords¤cpara1';

        $legend_title = $this->l('Add a category');
        if (Tools::getValue('idC')) {
            $categories = new CategoriesClass((int)Tools::getValue('idC'));
            $legend_title = $this->l('Update the category').' #'.$categories->id;
        } else {
            $categories = new CategoriesClass();
        }

        if (Tools::isSubmit('submitUpdateCat') || Tools::isSubmit('submitAddCat')) {
            $categories->id_shop = (int)$this->context->shop->id;
            $categories->copyFromPost();
        }

        $this->loadJsForTiny();

        $iso_tiny_mce = (file_exists(_PS_ROOT_DIR_.'/js/tinymce/jscripts/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en');
        $ad = dirname($_SERVER['PHP_SELF']);

        $allow_accents_js = 'var PS_ALLOW_ACCENTED_CHARS_URL = 0;';
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            $allow_accents_js = 'var PS_ALLOW_ACCENTED_CHARS_URL = 1;';
        }

        $this->html_out .= '
            <script type="text/javascript">
                '.$allow_accents_js.'
                var iso = \''.$iso_tiny_mce.'\' ;
                var pathCSS = \''._THEME_CSS_DIR_.'\' ;
                var ad = \''.$ad.'\' ;
                id_language = Number('.$dl.');
            </script>';

        $this->html_out .= '
            <script type="text/javascript">
                function copy2friendlyURLPrestaBlog() {
                    if (!$(\'#slink_rewrite_\'+id_language).attr(\'disabled\')) {
                        $(\'#slink_rewrite_\'+id_language).val(
                            str2url($(\'input#title_\'+id_language).val().replace(/^[0-9]+\./, \'\'),
                            \'UTF-8\')
                        );
                    }
                }
                function updateFriendlyURLPrestaBlog() {
                    $(\'#slink_rewrite_\'+id_language).val(
                        str2url($(\'#slink_rewrite_\'+id_language).val().replace(/^[0-9]+\./, \'\'),
                        \'UTF-8\')
                    );
                }';

        $this->html_out .= '
            $(function() {
                $("#submitForm").click(function() {';
        foreach ($languages as $language) {
            $this->html_out .= '$(\'#slink_rewrite_'.$language['id_lang'].'\').removeAttr("disabled");';
        }
        $this->html_out .= '
            });';

        $this->html_out .= '
            $("#control").toggle(
                function () {
                    $(\'#slink_rewrite_\'+id_language).removeAttr("disabled");
                    $(\'#slink_rewrite_\'+id_language).css("background-color", "#fff");
                    $(\'#slink_rewrite_\'+id_language).css("color", "#000");
                    $(this).html("'.$this->l('Disable this rewrite').'");
                },
                function () {
                    $(\'#slink_rewrite_\'+id_language).attr("disabled", true);
                    $(\'#slink_rewrite_\'+id_language).css("background-color", "#e0e0e0");
                    $(\'#slink_rewrite_\'+id_language).css("color", "#7F7F7F");
                    $(this).html("'.$this->l('Enable this rewrite').'");
                }
            );';

        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $this->html_out .= '
                if ($("#slink_rewrite_'.$lid.'").val() == \'\') {
                    $("#slink_rewrite_'.$lid.'").removeAttr("disabled");
                    $("#slink_rewrite_'.$lid.'").css("background-color", "#fff");
                    $("#slink_rewrite_'.$lid.'").css("color", "#000");
                    $("#control").html("'.$this->l('Disable this rewrite').'");
                }';
        }

        $this->html_out .= '
                });
            </script>'."\n";

        $this->html_out .= $this->displayFormOpen('icon-edit', $legend_title, $this->confpath);
        if (Tools::getValue('idC')) {
            $this->html_out .= '<input type="hidden" name="idC" value="'.Tools::getValue('idC').'" />';
        }
        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem('col-lg-2', $this->l('Activate'), 'actif', $categories->actif);
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="title_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="title_'.$lid.'"
                        id="title_'.$lid.'"
                        maxlength="'.(int)Configuration::get('prestablog_news_title_length').'"
                        value="'.(isset($categories->title[$lid]) ? $categories->title[$lid] : '').'"
                        onkeyup="if (isArrowKey(event))
                        return; copy2friendlyURLPrestaBlog();"
                        onchange="copy2friendlyURLPrestaBlog();"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('title', $div_lang_name)
        );
        //***********************************************************
        $html_libre = $categories->displaySelectArboCategories(
            CategoriesClass::getListe((int)$this->context->language->id, 0),
            (int)$categories->parent,
            0,
            $this->l('Top level'),
            'parent'
        );

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Parent category'), $html_libre, 'col-lg-7');
        //***********************************************************

        //***********************************************************
        /* DEBUT SEO */
        $html_libre = '
            <span onclick="$(\'#seo\').slideToggle();" style="cursor: pointer" class="link">
                <img
                    src="'.self::imgPathFO().'cog.gif"
                    alt="'.$this->l('SEO').'"
                    title="'.$this->l('SEO').'"
                />
                '.$this->l('Click here to improve SEO').'
            </span>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('SEO'), $html_libre, 'col-lg-7');

        $this->html_out .= '<div id="seo" style="display: none;">';
        //***********************************************************
        $html_libre = '';
        $styleseo = ' style="color:#7F7F7F;background-color:#e0e0e0;" disabled="true"';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="link_rewrite_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="link_rewrite_'.$lid.'"
                        id="slink_rewrite_'.$lid.'"
                        value="'.(isset($categories->link_rewrite[$lid]) ? $categories->link_rewrite[$lid] : '').'"
                        onkeyup="if (isArrowKey(event)) return ;updateFriendlyURLPrestaBlog();"
                        onchange="updateFriendlyURLPrestaBlog();"
                        '.(isset($categories->id) ? $styleseo :'').'
                    />
                </div>';
        }

        $urlrw = $this->l('Url Rewrite').'<br/><a href="#" id="control" />';
        $urlrw .= (isset($categories->id) ? $this->l('Enable this rewrite') : $this->l('Disable this rewrite')).'</a>';

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $urlrw,
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('link_rewrite', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="meta_title_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_title_'.$lid.'"
                        id="meta_title_'.$lid.'"
                        value="'.(isset($categories->meta_title[$lid]) ? $categories->meta_title[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Meta Title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_title', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $valmeta = (isset($categories->meta_description[$lid]) ? $categories->meta_description[$lid] : '');
            $html_libre .= '
                <div
                    id="meta_description_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_description_'.$lid.'"
                        id="meta_description_'.$lid.'"
                        value="'.$valmeta.'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Meta Description'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_description', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="meta_keywords_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="meta_keywords_'.$lid.'"
                        id="meta_keywords_'.$lid.'"
                        value="'.(isset($categories->meta_keywords[$lid]) ? $categories->meta_keywords[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Meta Keywords'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('meta_keywords', $div_lang_name)
        );
        //***********************************************************
        $this->html_out .= '</div>';
        /* FIN SEO */
        //***********************************************************

        //***********************************************************
        /* DEBUT IMAGE */
        $html_libre = '';
        if ($this->demo_mode) {
            $html_libre .= $this->displayWarning($this->l('Feature disabled on the demo mode'));
        }

        if (Tools::getValue('idC')
            && file_exists(
                self::imgUpPath().'/c/admincrop_'.Tools::getValue('idC').'.jpg'
            )
        ) {
            $html_libre .= '<span id="labelPicture"></span>';
            $config_theme_array = PrestaBlog::objectToArray($config_theme);
            if (Tools::getValue('pfx')) {
                $html_libre .= '
                    <script type="text/javascript">
                        $(document).ready(function() {
                            $("html, body").animate({scrollTop: $("#labelPicture").offset().top}, 750);
                        });
                    </script>'."\n";
            }

            $html_libre .= '
                <script src="'.__PS_BASE_URI__.'modules/prestablog/views/js/jquery.Jcrop.prestablog.js"></script>
                <link
                    rel="stylesheet"
                    href="'.__PS_BASE_URI__.'modules/prestablog/views/css/jquery.Jcrop.css" type="text/css"
                />';

            $html_libre .= '<script language="Javascript">'."\n";
            $html_libre .= '
                var monRatio;
                var monImage;
                var ratioValue = new Array();
                '."\n";

            foreach ($config_theme_array['categories'] as $key_theme_array => $value_theme_array) {
                $html_libre .= '  ratioValue[\''.$key_theme_array.'\'] = ';
                $html_libre .= (int)$value_theme_array['width'] / (int)$value_theme_array['height'].';'."\n";
            }

            $html_libre .= '
                $(function(){
                    $("div.togglePreview").hide();'."\n";

            if (Tools::getValue('pfx')) {
                $pfx = Tools::getValue('pfx');
                $html_libre .= '
                    $(\'input[name$="imageChoix"]\').filter(\'[value="'.$pfx.'"]\').attr(\'checked\', true);
                    $(\'input[name$="imageChoix"]\').filter(\'[value="'.$pfx.'"]\').parent().next(1).slideDown();
                    $("#pfx").val(\''.$pfx.'\');
                    $("#ratio").val(ratioValue[\''.$pfx.'\']);
                    monRatio = ratioValue[\''.$pfx.'\'];
                    $(\'#cropbox\').Jcrop({
                        \'minSize\' : monRatio,
                        \'onSelect\' : updateCoords
                    });
                    nomImage = \''.$this->l('Resize').' '.$pfx.'\';
                    $("#resizeText").html(nomImage);'."\n";
            }

            $html_libre .= '
                $(\'input[name$="imageChoix"]\').change(function () {
                    $("div.togglePreview").slideUp();
                    $(this).parent().next().slideDown();
                    $("#pfx").val($(this).val());
                    $("#ratio").val(ratioValue[$(this).val()]);
                    monRatio = ratioValue[$(this).val()];
                    $(\'#cropbox\').Jcrop({
                        \'minSize\' : monRatio,
                        \'onSelect\' : updateCoords
                    });
                    nomImage = \''.$this->l('Resize').' \'+$("#pfx").val();
                    $("#resizeText").html(nomImage);
                });';

            $html_libre .= '
                });

                function updateCoords(c) {
                    $(\'#x\').val(c.x);
                    $(\'#y\').val(c.y);
                    $(\'#w\').val(c.w);
                    $(\'#h\').val(c.h);
                };
                function checkCoords() {
                    if (!$(\'input[name="imageChoix"]:checked\').val()) {
                        alert(\''.$this->l('Please select a picture to crop.').'\');
                        return false;
                    } else {
                        if (parseInt($(\'#w\').val()))
                            return true;
                        alert(\''.$this->l('Please select a crop region then press submit.').'\');
                        return false;
                    }
                };';
            $html_libre .= '</script>';

            $imgcrop = self::imgPathBO().self::getT().'/up-img/c/';
            $imgcrop .= 'admincrop_'.Tools::getValue('idC').'.jpg?'.md5(time());

            $html_libre .= '
                <div id="image" class="col-md-7">
                    <div class="blocmodule">
                        <img
                            id="cropbox"
                            src="'.$imgcrop.'"
                        />
                        <p align="center">
                            '.$this->l('Filesize').'
                            '.(filesize(self::imgUpPath().'/c/'.Tools::getValue('idC').'.jpg') / 1000).'kb
                        </p>
                        <p>
                            <a
                                href="'.$this->confpath.'&deleteImageBlog&idC='.Tools::getValue('idC').'"
                                onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                            >
                                <img
                                    src="'.self::imgPathFO().'delete.gif"
                                    alt="'.$this->l('Delete').'"
                                />
                                '.$this->l('Delete').'
                            </a>
                        </p>
                        <p>';
            $html_libre .= $this->displayFormFileNoLabel(
                'imageCategory',
                'col-lg-10',
                $this->l('Format:').' .jpg .png'
            );
            $html_libre .= '
                        </p>
                    </div>
                </div>
                <div class="col-md-5">'."\n";

            foreach ($config_theme_array['categories'] as $key_theme_array => $value_theme_array) {
                $width_force = '';
                if (file_exists(self::imgUpPath().'/c/'.$key_theme_array.'_'.Tools::getValue('idC').'.jpg')) {
                    $attrib_image = getimagesize(
                        self::imgUpPath().'/c/'.$key_theme_array.'_'.Tools::getValue('idC').'.jpg'
                    );
                    if ((int)$attrib_image[0] > 200) {
                        $width_force = 'width="200"';
                    }
                }

                $label_pic = $key_theme_array;
                switch ($key_theme_array) {
                    case 'thumb':
                        $label_pic = $this->l('thumb for category list');
                        break;
                    case 'full':
                        $label_pic = $this->l('full picture for description category list');
                        break;
                }
                $imgcrop = $this->_path.'views/img/'.self::getT().'/up-img/c/';
                $imgcrop .= $key_theme_array.'_'.Tools::getValue('idC').'.jpg?'.md5(time());
                $html_libre .= '
                    <div class="blocmodule">
                        <p>
                            <input type="radio" name="imageChoix" value="'.$key_theme_array.'" />'
                            .$label_pic.'
                            <span style="font-size: 80%;">
                                ('.($width_force ? $this->l('Real size : ') : '').'
                                '.(int)$value_theme_array['width'].' * '.(int)$value_theme_array['height'].')
                            </span>
                        </p>
                        <div class="togglePreview" style="text-align:center;">
                            <img
                                style="border:1px solid #4D4D4D;padding:0px;"
                                src="'.$imgcrop.'"
                                '.$width_force.'
                            />
                        </div>
                    </div>'."\n";
            }
            $html_libre .= '
                        <div class="blocmodule">
                            <a class="btn btn-default" onclick="if (checkCoords()) {formCrop.submit();}"  >
                                <i class="icon-crop"></i>&nbsp;<span id="resizeText">'.$this->l('Resize').'</span>
                            </a>
                        </div>
                </div>'."\n";
        } else {
            $html_libre .= $this->displayFormFileNoLabel('imageCategory', 'col-lg-5', $this->l('Format:').' .jpg .png');
        }

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Picture'), $html_libre, 'col-lg-10');
        /* FIN IMAGE */
        //***********************************************************

        //***********************************************************
        /* DEBUT description */
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="cpara1_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <textarea
                        class="rte
                        autoload_rte"
                        id="description_'.$lid.'"
                        name="description_'.$lid.'"
                    >
                        '.(isset($categories->description[$lid]) ? $categories->description[$lid] : '').'
                    </textarea>
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Description'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('cpara1', $div_lang_name)
        );
        /* FIN description */
        //***********************************************************

        //***********************************************************
        /* Groups */
        $active_group = array();
        if (Tools::getValue('idC')) {
            $active_group = CategoriesClass::getGroupsFromCategorie((int)Tools::getValue('idC'));
        }

        $html_libre = $this->displayFormGroups($active_group);
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Groups permissions'),
            $html_libre,
            'col-lg-7'
        );
        /* /Groups */
        //***********************************************************
//***********************************************************
//DEBUT POPUP
$prestaboost = Module::getInstanceByName('prestaboost');
if (!$prestaboost) {
	$popups_link[0] = 'None';
        foreach (PopupClass::getListePopup($lid) as $popup_link) {
            $popups_link[$popup_link['id_prestablog_popup']] = $popup_link['title'];

        }
$popuplink = CategoriesClass::getPopupLink($categories->id);

if (isset($popuplink)) {
$this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Add a popup to your categorie :'),
            'popupLinkCate',
            $popuplink,
            $popups_link,
            null,
            'col-lg-5'
        );

} else {
    $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Add a popup to your categorie :'),
            'popupLinkCate',
            self::getP(),
            $popups_link,
            null,
            'col-lg-5'
        );
}
}
//FIN POPUP
//***********************************************************
        $this->html_out .= '<div class="margin-form">';

        if (Tools::getValue('idC')) {
            $this->html_out .= '
                <button class="btn btn-primary" name="submitUpdateCat" type="submit">
                    <i class="icon-save"></i>&nbsp;'.$this->l('Update the category').'
                </button>';
        } else {
            $this->html_out .= '
                <button class="btn btn-primary" name="submitAddCat" type="submit">
                    <i class="icon-plus"></i>&nbsp;'.$this->l('Add the category').'
                </button>';
        }

        $this->html_out .= '</div>';

        $this->html_out .= $this->displayFormClose();

        $this->html_out .= '
            <form
                name="formCrop"
                id="formCrop"
                action="'.$this->confpath.'"
                method="post"
                onsubmit="return checkCoords();"
            >
                <input type="hidden" name="idC" value="'.Tools::getValue('idC').'" />
                <input type="hidden" id="pfx" name="pfx" value="'.Tools::getValue('pfx').'" />
                <input type="hidden" id="x" name="x" />
                <input type="hidden" id="y" name="y" />
                <input type="hidden" id="w" name="w" />
                <input type="hidden" id="h" name="h" />
                <input type="hidden" id="ratio" name="ratio" />
                <input type="hidden" name="submitCrop" value="submitCrop" />
            </form>';
    }

    private function displayFormLookbook()
    {
        //$config_theme = $this->getConfigXmlTheme(self::getT());

        $dl = $this->langue_default_store;
        $languages = Language::getLanguages(true);
        $iso = Language::getIsoById((int)$this->context->language->id);
        $div_lang_name = 'title¤cpara1';

        $legend_title = $this->l('Add a lookbook');
        if (Tools::getValue('idLB')) {
            $lookbook = new LookBookClass((int)Tools::getValue('idLB'));
            $legend_title = $this->l('Update the lookbook').' #'.$lookbook->id;
        } else {
            $lookbook = new LookBookClass();
        }

        if (Tools::isSubmit('submitUpdateLookbook') || Tools::isSubmit('submitAddLookbook')) {
            $lookbook->id_shop = (int)$this->context->shop->id;
            $lookbook->copyFromPost();
        }

        $this->loadJsForTiny();

        $iso_tiny_mce = (file_exists(_PS_ROOT_DIR_.'/js/tinymce/jscripts/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en');
        $ad = dirname($_SERVER['PHP_SELF']);

        $allow_accents_js = 'var PS_ALLOW_ACCENTED_CHARS_URL = 0;';
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            $allow_accents_js = 'var PS_ALLOW_ACCENTED_CHARS_URL = 1;';
        }

        $this->html_out .= '
            <script type="text/javascript">
                '.$allow_accents_js.'
                var iso = \''.$iso_tiny_mce.'\' ;
                var pathCSS = \''._THEME_CSS_DIR_.'\' ;
                var ad = \''.$ad.'\' ;
                id_language = Number('.$dl.');
            </script>'."\n";

        $this->html_out .= $this->displayFormOpen('icon-edit', $legend_title, $this->confpath);
        if (Tools::getValue('idLB')) {
            $this->html_out .= '<input type="hidden" name="idLB" value="'.(int)Tools::getValue('idLB').'" />';
        }
        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem('col-lg-2', $this->l('Activate'), 'actif', $lookbook->actif);
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="title_'.$lid.'"
                    style="display:
                    '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="title_'.$lid.'"
                        id="title_'.$lid.'"
                        maxlength="'.(int)Configuration::get('prestablog_lb_title_length').'"
                        value="'.(isset($lookbook->title[$lid]) ? $lookbook->title[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('title', $div_lang_name)
        );
        //***********************************************************

        //***********************************************************
        /* DEBUT description */
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="cpara1_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <textarea
                        class="rte autoload_rte"
                        id="description_'.$lid.'"
                        name="description_'.$lid.'"
                    >
                        '.(isset($lookbook->description[$lid]) ? $lookbook->description[$lid] : '').'
                    </textarea>
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Description'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('cpara1', $div_lang_name)
        );
        /* FIN description */
        //***********************************************************

        //***********************************************************
        /* DEBUT IMAGE */
        $lookbook_products = LookBookClass::getLookBookProducts((int)Tools::getValue('idLB'));

        $html_libre = '';

        if ($this->demo_mode) {
            $html_libre .= $this->displayWarning($this->l('Feature disabled on the demo mode'));
        }

        if (Tools::getValue('idLB')
            && file_exists(self::imgUpPath().'/lookbook/lbcrop_'.(int)Tools::getValue('idLB').'.jpg')
            && file_exists(self::imgUpPath().'/lookbook/'.(int)Tools::getValue('idLB').'.jpg')
        ) {
            $rootimglb = __PS_BASE_URI__.'modules/prestablog/views/img/'.self::getT().'/up-img/lookbook/';

            $html_libre .= '
                <script src="'.__PS_BASE_URI__.'modules/prestablog/views/js/jquery.canvasAreaDraw.blog.js"></script>
                <script language="Javascript">
                    (function( $ ){
                        var canvasedition = new Image();
                        var canvasfinal = new Image();
                        canvasedition.src = "'.$rootimglb.'lbcrop_'.(int)Tools::getValue('idLB').'.jpg";
                        canvasfinal.src = "'.$rootimglb.(int)Tools::getValue('idLB').'.jpg";

                        canvasedition.onload = function() { }
                        canvasfinal.onload = function() {
                            var cfw = $("#largeur-canvas-final").val(canvasfinal.width);
                            var cfh = $("#hauteur-canvas-final").val(canvasfinal.height);
                            var cew = $("#largeur-canvas-edition").val(canvasedition.width);
                            var ceh = $("#hauteur-canvas-edition").val(canvasedition.height);

                            $("#lookbook_shape_ed").data("ratiol", ( cfw / cew ) );
                            $("#lookbook_shape_ed").data("ratioh", ( cfh / ceh ) );
                        }
                    })( jQuery );

                    $(document).ready(function() {
                        $( "tr.searchlookbook" ).hover(
                            function() {
                                var id_product_hover = $(this).attr("rel");
                                $( "#image svg polygon" ).each(function() {
                                    if (id_product_hover == $(this).attr("rel")) {
                                        $(this).attr("class", "polygonhover");
                                    }
                                });
                            }, function() {
                                $( "#image svg polygon" ).removeAttr( "class" );
                            }
                        );
                    });
                </script>';

            list(
                $largeur_canvas_edition,
                $hauteur_canvas_edition
            ) = getimagesize(self::imgUpPath().'/lookbook/lbcrop_'.(int)Tools::getValue('idLB').'.jpg');

            list(
                $largeur_canvas_final,
                $hauteur_canvas_final
            ) = getimagesize(self::imgUpPath().'/lookbook/'.(int)Tools::getValue('idLB').'.jpg');

            $ratiol = ( (int)$largeur_canvas_final / (int)$largeur_canvas_edition );
            $ratioh = ( (int)$hauteur_canvas_final / (int)$hauteur_canvas_edition );

            $urlpic = $this->_path.'views/img/'.self::getT().'/up-img/';
            $urlpic .= 'lookbook/lbcrop_'.Tools::getValue('idLB').'.jpg';

            $html_libre .= '
                <div id="image" class="col-md-9">
                    <img
                        style="position:absolute;"
                        id="lookbook_shape_ed"
                        data-ratiol="'.$ratiol.'"
                        data-ratioh="'.$ratioh.'"
                        src="'.$urlpic.'"
                        class="canvas-area"
                        data-image-url="'.$urlpic.'"
                        data-ratiol="'.$ratiol.'"
                        data-ratioh="'.$ratioh.'"
                        data-lclear="'.$this->l('Clear points').'"
                        data-usemap="#usemaplookbook"
                        usemap="#usemaplookbook"
                    />';

            $html_libre .= '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    style="position:absolute;"
                    width="'.(int)$largeur_canvas_edition.'"
                    height="'.(int)$hauteur_canvas_edition.'"
                    pointer-events="visible"
                    id="svg-for-element"
                >';

            if (count($lookbook_products) > 0) {
                foreach ($lookbook_products as $product_lb) {
                    $product = new Product((int)$product_lb['id_product'], false, (int)$this->context->language->id);

                    $html_libre .= '
                        <polygon
                            points="'.$product_lb['shape_ed'].'"
                            rel="'.$product->id.'"
                        >
                            <title>'.$product->name.'</title>
                        </polygon>';
                }
            }

            $html_libre .= '</svg>';

            $html_libre .= '</div>
                <div class="col-md-3">';

            //***********************************************************
            /* DEBUT PRODUITS LIES */
            $loadprodjs = '<tr><td colspan="4" class="center">'.$this->l('You must search before');
            $loadprodjs .= ' ('.(int)Configuration::get($this->name.'_nb_car_min_linkprod');
            $loadprodjs .= ' '.$this->l('caract. minimum').')</td></tr>';

            $this->html_out .= '
                <script type="text/javascript">
                    $(function() {
                        $("#productLinkSearch").bind("keyup click focusin", function() {
                            ReloadLinkedSearchProducts();
                        });
                    });

                    function ReloadLinkedSearchProducts(start) {
                        var listLinkedProducts = \'\';
                        $("input[name^=productsLink]").each(function() {
                            listLinkedProducts += $(this).val() + ";";
                        });

                        if ($("#productLinkSearch").val() != \'\' && $("#productLinkSearch").val().length >= '
                            .(int)Configuration::get($this->name.'_nb_car_min_linkprod').') {
                            $.ajax({
                                url: \''.$this->context->link->getAdminLink('AdminPrestaBlogAjax').'\',
                                type: "GET",
                                data: {
                                    ajax: true,
                                    action: \'prestablogrun\',
                                    do: \'searchProductsLookbook\',
                                    listLinkedProducts: listLinkedProducts,
                                    start: start,
                                    req: $("#productLinkSearch").attr("value"),
                                    id_shop: \''.(int)$this->context->shop->id.'\',
                                    idLB: \''.(int)Tools::getValue('idLB').'\',
                                    urlReturn: \''.$this->confpath.'\'
                                },
                                success:function(data){
                                    $("#productLinkResult").empty();
                                    $("#productLinkResult").append(data);
                                }
                            });
                        } else {
                            $("#productLinkResult").empty();
                            $("#productLinkResult").append(\''.$loadprodjs.'\');
                        }
                    }
                </script>';

            $html_libre .= '
                <div class="blocmodule" id="searchlookbook">
                    <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                                <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                                <th class="center">'.$this->l('Name').'</th>
                                <th class="center" style="width:40px;">'.$this->l('Unlink').'</th>
                            </tr>
                        </thead>
                        <tbody id="productLinked">';

            if (count($lookbook_products) > 0) {
                foreach ($lookbook_products as $product_lb) {
                    $product = new Product((int)$product_lb['id_product'], false, (int)$this->context->language->id);

                    $product_cover = Image::getCover($product->id);
                    $image_product = new Image((int)$product_cover['id_image']);
                    $image_thumb_path = ImageManager::thumbnail(
                        _PS_IMG_DIR_.'p/'.$image_product->getExistingImgPath().'.jpg',
                        'product_mini_'.$product->id.'.jpg',
                        45,
                        'jpg'
                    );
                    $urldel = $this->confpath.'&deleteProductLookbook&idLB='.(int)Tools::getValue('idLB');
                    $urldel .= '&idLBP='.(int)$product_lb['id_prestablog_lookbook_product'];
                    $html_libre .= '
                        <tr class="searchlookbook" rel="'.(int)$product->id.'">
                            <td class="center">'.(int)$product->id.'</td>
                            <td class="center">'.$image_thumb_path.'</td>
                            <td class="center">'.$product->name.'</td>
                            <td class="center">
                                <a
                                    href="'.$urldel.'"
                                    onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                                >
                                    <img
                                        src="'.self::imgPathFO().'disabled.gif"
                                        rel="'.(int)$product_lb['id_prestablog_lookbook'].'"
                                        class="delinked"
                                    />
                                </a>
                            </td>
                        </tr>';
                }
            } else {
                $html_libre .= '<tr><td colspan="4" class="center">'.$this->l('No product linked').'</td></tr>';
            }

            $html_libre .= '
                        </tbody>
                    </table>
                </div>';

            $html_libre .= '
                <div class="blocmodule" id="blocklinktolookbook" style="display:none;">
                    <textarea style="display:none;" id="lookbook_shape" name="lookbook_shape"></textarea>
                    <p class="center">
                        '.$this->l('Search').' :
                        <input
                            type="text"
                            size="20"
                            id="productLinkSearch"
                            name="productLinkSearch"
                            placeholder="'.sprintf($this->l('Keywords from %1$s or #id'), $this->l('Name')).'"
                        />
                    </p>
                    <table cellspacing="0" cellpadding="0" class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="center" style="width:40px;">'.$this->l('Link').'</th>
                                <th class="center" style="width:30px;">'.$this->l('ID').'</th>
                                <th class="center" style="width:50px;">'.$this->l('Image').'</th>
                                <th class="center">'.$this->l('Name').'</th>
                            </tr>
                        </thead>
                        <tbody id="productLinkResult">
                            <tr>
                                <td colspan="4" class="center">
                                    '.$this->l('You must search before').'
                                    (
                                        '.(int)Configuration::get($this->name.'_nb_car_min_linkprod').'
                                        '.$this->l('caract. minimum').'
                                    )
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

            /* FIN PRODUITS LIES */
            //***********************************************************
            $html_libre .= '
                <div class="blocmodule">
                    <p>
                        <a
                            href="'.$this->confpath.'&deleteImageLookbook&idLB='.(int)Tools::getValue('idLB').'"
                            onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                        >
                            <img
                                src="'.self::imgPathFO().'delete.gif"
                                alt="'.$this->l('Delete').'"
                            />
                            '.$this->l('Delete').'
                        </a>
                    </p>
                </div>
                '."\n";

            $html_libre .= '</div>';

            $this->html_out .= $this->displayFormLibre('', $this->l('Lookbook image'), $html_libre, 'col-lg-12');
        } else {
            $html_libre .= $this->displayFormFileNoLabel(
                'imageLookbook',
                'col-lg-5',
                $this->l('Format:').' .jpg .png'
            );
            $this->html_out .= $this->displayFormLibre(
                'col-lg-2',
                $this->l('Lookbook image'),
                $html_libre,
                'col-lg-10'
            );
        }
        /* FIN IMAGE */
        //***********************************************************

        //***********************************************************
        /* Groups */
        $active_group = array();
        if (Tools::getValue('idLB')) {
            $active_group = LookBookClass::getGroupsFromLookbook((int)Tools::getValue('idLB'));
        }

        $html_libre = $this->displayFormGroups($active_group);
        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Groups permissions'),
            $html_libre,
            'col-lg-7'
        );
        /* /Groups */
        //***********************************************************

        $this->html_out .= '<div class="margin-form">';

        if (Tools::getValue('idLB')) {
            $this->html_out .= '
                <button class="btn btn-primary" name="submitUpdateLookbook" type="submit">
                    <i class="icon-save"></i>&nbsp;'.$this->l('Update the lookbook').'
                </button>';
        } else {
            $this->html_out .= '
                <button class="btn btn-primary" name="submitAddLookbook" type="submit">
                    <i class="icon-plus"></i>&nbsp;'.$this->l('Add the lookbook').'
                </button>';
        }

        $this->html_out .= '</div>';

        $this->html_out .= $this->displayFormClose();
    }

    public function displayFormGroups($active_group)
    {
        $html_out = '
        <div class="blocmodule">
            <table cellspacing="0" cellpadding="0" class="table">
                <thead>
                    <tr>
                        <th style="width:20px;"><input type="checkbox" name="checkme" class="noborder"
                        onclick="checkDelBoxes(this.form, \'groupBox[]\', this.checked)" /></th>
                        <th style="width:20px;">'.$this->l('ID').'</th>
                        <th>'.$this->l('Group name').'</th>
                    </tr>
                </thead>
                <tbody>';

        foreach (Group::getGroups((int)$this->context->language->id) as $group) {
            $html_out .= '
                    <tr>
                        <td>
                            <input
                                name="groupBox[]"
                                class="groupBox"
                                id="groupBox_'.(int)$group['id_group'].'"
                                value="'.(int)$group['id_group'].'"
                                '.(in_array((int)$group['id_group'], $active_group) ? 'checked="checked"' : '').'
                                type="checkbox" checked="checked"
                            >
                        </td>
                        <td>'.(int)$group['id_group'].'</td>
                        <td>
                            <label for="groupBox_'.(int)$group['id_group'].'">'.$group['name'].'</label>
                        </td>
                    </tr>';
        }
        $html_out .= '
                </tbody>
            </table>
        </div>';

        return $html_out;
    }

private function displayFormSubBlocksFront()
    {
    	                if (!isset($id_lang)) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }
if (SubBlocksClass::getIdSbHome($id_lang) != 0) {
	$id_front = SubBlocksClass::getIdSbHome($id_lang);
}

        $dl = $this->langue_default_store;
        $languages = Language::getLanguages(true);
        $div_lang_name = 'title';

        $legend_title = $this->l('Add your articles on home\'s frontpage');
        if (isset($id_front)) {
            $sub_blocks = new SubBlocksClass((int)$id_front);
            $lln = unserialize($sub_blocks->langues);
            if (!is_array($lln)) {
                $lln = array();
            }
            $legend_title = $this->l('Update the list');
        } else {
            $sub_blocks = new SubBlocksClass();
        }

        if (Tools::isSubmit('submitUpdateSubBlockFront') || Tools::isSubmit('submitAddSubBlockFront')) {
            $sub_blocks->id_shop = (int)$this->context->shop->id;
            $sub_blocks->copyFromPost();
        }

        $this->html_out .= '
            <script type="text/javascript">
                id_language = Number('.$dl.');

                function RetourLangueCheckUp(ArrayCheckedLang, idLangEnCheck, idLangDefaut) {
                    if (ArrayCheckedLang.length > 0)
                        return ArrayCheckedLang[0];
                    else
                        return idLangDefaut;
                }

                $(function() {
            ';

        if (isset($id_front)) {
            if (!Tools::getValue('languesup') && count($lln) == 1) {
                $this->html_out .= 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.(int)$lln[0].', \'\');';
            }
        } else {
            $acl = array();
            if (Tools::getValue('languesup')) {
                $acl = Tools::getValue('languesup');
            }


            if (count($acl) == 1) {
                $this->html_out .= 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.(int)$acl[0].', \'\');';
            } else {
                $this->html_out .= 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.(int)$dl.', \'\');';
            }
        }
        $this->html_out .= '
                $("input[name=\'languesup[]\']").click(function() {
                    if (this.checked)
                        changeTheLanguage(\'title\', \''.$div_lang_name.'\', this.value, \'\');
                    else {
                        selectedL = new Array();
                        $("input[name=\'languesup[]\']:checked").each(function() {selectedL.push($(this).val());});
                        changeTheLanguage(\'title\', \''.$div_lang_name.'\',
                            RetourLangueCheckUp(selectedL, this.value, '.$dl.'), \'\');
                    }
                });

                $("#submitForm").click(function( event ) {
                    test = 0;
                    $("input[name=\'languesup[]\']:checked").each(function() {
                        test += 1;
                    });
                    if(test == 0) {
                        $("input[name=\'languesup[]\'][value='.$dl.']").prop("checked","true");
                    }
                });
            });

            function changeTheLanguage(title, divLangName, id_lang, iso) {
                $("#imgCatLang").attr("src", "../img/l/" + id_lang + ".jpg");
                return changeLanguage(title, divLangName, id_lang, iso);
            }
        </script>';

        $this->html_out .= $this->displayFormOpen('icon-edit', $legend_title, $this->confpath, 'formWithSelectLang');
        if (isset($id_front)) {
            $this->html_out .= '<input type="hidden" name="idSBF" value="'.(int)$id_front.'" />';
            $this->html_out .= '<input type="hidden" name="position" value="'.(int)$sub_blocks->position.'" />';
        }

        //***********************************************************
        $html_libre = '<span id="check_lang_prestablog">';
        $checkmelang = '
            <input
                type="checkbox"
                name="checkmelang"
                class="noborder"
                onclick="checkDelBoxes(this.form, \'languesup[]\', this.checked)"
            />
            '.$this->l('All').' | ';

        $html_libre .= (count($languages) == 1 ? '' : $checkmelang);

        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '<input type="checkbox" name="languesup[]" value="'.$lid.'"';
            if ((SubBlocksClass::getIdSbHome($id_lang) && in_array((int)$lid, $lln))
                || (Tools::getValue('languesup') && in_array((int)$lid, Tools::getValue('languesup')))
            ) {
                $html_libre .= ' checked=checked';
            }
            $chlg = 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.$lid.', \''.$language['iso_code'].'\');';
            $html_libre .= ' '.(count($languages) == 1 ? 'style="display:none;"' : '').' />
                <img
                    src="../img/l/'.(int)$lid.'.jpg"
                    class="pointer"
                    alt="'.$language['name'].'"
                    title="'.$language['name'].'"
                    onclick="'.$chlg.'"
                />';
        }
        $html_libre .= '</span>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Language'), $html_libre, 'col-lg-7');
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="title_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="title_'.$lid.'"
                        id="title_'.$lid.'"
                        value="'.(isset($sub_blocks->title[$lid]) ? $sub_blocks->title[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('title', $div_lang_name)
        );
        //***********************************************************
        $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('List'),
            'select_type',
            $sub_blocks->select_type,
            $sub_blocks->getListeSelectType(),
            null,
            'col-lg-5'
        );
        //***********************************************************
        $this->html_out .= '<div style="display: none;">';
        $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Hook'),
            'hook_name',
            (Tools::getValue('preselecthook') ? Tools::getValue('preselecthook') : $sub_blocks->hook_name),
            $sub_blocks->getListeHook(),
            null,
            'col-lg-5'
        );

        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Template'),
            'template',
            $sub_blocks->template,
            60,
            'col-lg-6',
            null,
            sprintf(
                $this->l('Leave blank to use the default template %1$s'),
                '<strong>'.self::getT().'_page-subblock.tpl</strong>'
            )
        );
        $this->html_out .= '</div>';
        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Number of news to display'),
            'nb_list',
            $sub_blocks->nb_list,
            10,
            'col-lg-4'
        );
        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Title length'),
            'title_length',
            $sub_blocks->title_length,
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Description length'),
            'intro_length',
            $sub_blocks->intro_length,
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        //***********************************************************
        /* DEBUT PERIODE */
        $this->html_out .= '<div style="display: none;">';
        $html_libre = '<div class="blocmodule">';
        $html_libre .= $this->displayFormDateWithActivation(
            'col-lg-1',
            $this->l('From'),
            'date_start',
            $sub_blocks->date_start,
            true,
            'use_date_start',
            $sub_blocks->use_date_start
        );
        $html_libre .= $this->displayFormDateWithActivation(
            'col-lg-1',
            $this->l('To'),
            'date_stop',
            $sub_blocks->date_stop,
            true,
            'use_date_stop',
            $sub_blocks->use_date_stop
        );
        $html_libre .= '</div>';

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Use a period'),
            $html_libre,
            'col-lg-6'
        );
        $this->html_out .= '</div>';
        /* FIN PERIODE */
        //***********************************************************

        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem(
            'col-lg-2',
            $this->l('Random list'),
            'random',
            $sub_blocks->random,
            $this->l('This option will randomize your list.')
        );
        //***********************************************************

        //***********************************************************
        /* DEBUT CATEGORIES */
        $html_libre = '';
        $html_libre .= '
            <div class="blocmodule">
                <table cellspacing="0" cellpadding="0" class="table">
                    <thead>
                        <tr>
                            <th style="width:20px;"><input type="checkbox" name="checkme" class="noborder"
                            onclick="checkDelBoxes(this.form, \'categories[]\', this.checked)"/></th>
                            <th style="width:20px;">'.$this->l('ID').'</th>
                            <th style="width:60px;">'.$this->l('Image').'</th>
                            <th>'.$this->l('Name').'&nbsp;<img id="imgCatLang" src="../img/l/'.$dl.'.jpg"
                            style="vertical-align:middle;" /></th>
                        </tr>
                    </thead>';

        $liste_cat = CategoriesClass::getListe((int)$this->context->language->id, 0);
        $liste_cat_no_arbre = CategoriesClass::getListeNoArbo();
        $liste_cat_branches_actives = array();

        foreach (SubBlocksClass::getCategories($sub_blocks->id, 0) as $value) {
            $liste_cat_branches_actives = array_unique(
                array_merge(
                    $liste_cat_branches_actives,
                    preg_split('/\./', CategoriesClass::getBranche((int)$value))
                )
            );
        }

        $html_libre .= $this->displayListeArborescenceCategoriesSubBlocks($liste_cat, 0, $liste_cat_branches_actives);

        $html_libre .= '
            </table>
        </div>
        <script language="javascript" type="text/javascript">
            $(document).ready(function() {
                $(".catlang").hide();
                $(".catlang[rel="+id_language+"]").show();

                $("div.language_flags img, #check_lang_prestablog img").click(function() {
                    $(".catlang").hide();
                    $(".catlang[rel="+id_language+"]").show();
                    $("#imgCatLang").attr("src", "../img/l/" + id_language + ".jpg");
                });
                ';

        foreach ($liste_cat_branches_actives as $value) {
            $html_libre .= '$("tr#prestablog_categorie_'.$value.'").show();';
        }

        foreach ($liste_cat_no_arbre as $value) {
            if (in_array((int)$value['parent'], $liste_cat_branches_actives)) {
                $html_libre .= '$("tr#prestablog_categorie_'.$value['id_prestablog_categorie'].'").show();';
            }
        }

        $html_libre .= '
                $("img.expand-cat").click(function() {
                    BranchClick=$(this).attr("rel");
                    BranchClickSplit = BranchClick.split(\'.\');
                    fixBranchClickSplit = "0,"+BranchClickSplit.toString();

                    switch ($(this).attr("src")) {
                        case "/../modules/prestablog/views/img/expand.gif":
                            $("tr.prestablog_branch").each(function() {
                                BranchParent = $(this).attr("rel");
                                BranchParentSplit = BranchParent.split(\'.\');
                                fixBranchParentSplit = "0,"+BranchParentSplit.toString();

                                if ($.isSubstring(fixBranchParentSplit, fixBranchClickSplit)
                                        && BranchClick != BranchParent
                                        && BranchClickSplit.length+1 == BranchParentSplit.length
                                    ) {
                                        $(this).show();
                                }
                            });
                            $(this).attr("src", "/../modules/prestablog/views/img/collapse.gif");
                            break;

                        case "/../modules/prestablog/views/img/collapse.gif":
                            $("tr.prestablog_branch").each(function() {
                                BranchParent = $(this).attr("rel");
                                BranchParentSplit = BranchParent.split(\'.\');
                                fixBranchParentSplit = "0,"+BranchParentSplit.toString();

                                if ($.isSubstring(fixBranchParentSplit, fixBranchClickSplit)
                                        && BranchClick != BranchParent
                                    ) {
                                        $(this).hide();
                                        $(this).find("img.expand-cat").each(function() {
                                            $(this).attr("src", "/../modules/prestablog/views/img/expand.gif");
                                        });
                                }
                            });
                            $(this).attr("src", "/../modules/prestablog/views/img/expand.gif");
                            break;
                    }
                });
            });
            jQuery.isSubstring = function(haystack, needle) {
                 return haystack.indexOf(needle) !== -1;
            };
        </script>';
        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Categories'), $html_libre, 'col-lg-5');
        /* FIN CATEGORIES */
        //***********************************************************

        //***********************************************************
                $this->html_out .= '<div class="john" style="display:none;">';
        $this->html_out .= $this->displayFormEnableItem(
            'col-lg-2',
            $this->l('Blog link'),
            'blog_link',
            $sub_blocks->blog_link,
            $this->l('Show link to the blog')
        );
        $this->html_out .= '</div>';
        //***********************************************************

        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem(
            'col-lg-2',
            $this->l('Activate'),
            'actif',
            $sub_blocks->actif
        );
        //***********************************************************

        $this->html_out .= '<div class="margin-form">';

        if (isset($id_front)) {
            $this->html_out .= '
                <button class="btn btn-primary" id="submitForm" name="submitUpdateSubBlockFront">
                    <i class="icon-save"></i>&nbsp;'.$this->l('Update').'
                </button>';
        } else {
            $this->html_out .= '
                <button class="btn btn-primary" id="submitForm" name="submitAddSubBlockFront">
                    <i class="icon-plus"></i>&nbsp;'.$this->l('Add').'
                </button>';
        }

        $this->html_out .= '</div>';
        $this->html_out .= $this->displayFormClose();
    }

    private function displayFormSubBlocks()
    {
        $dl = $this->langue_default_store;
        $languages = Language::getLanguages(true);
        $div_lang_name = 'title';

        $legend_title = $this->l('Create a list');
        if (Tools::getValue('idSB')) {
            $sub_blocks = new SubBlocksClass((int)Tools::getValue('idSB'));
            $lln = unserialize($sub_blocks->langues);
            if (!is_array($lln)) {
                $lln = array();
            }
            $legend_title = $this->l('Update the list');
        } else {
            $sub_blocks = new SubBlocksClass();
        }

        if (Tools::isSubmit('submitUpdateSubBlock') || Tools::isSubmit('submitAddSubBlock')) {
            $sub_blocks->id_shop = (int)$this->context->shop->id;
            $sub_blocks->copyFromPost();
        }

        $this->html_out .= '
            <script type="text/javascript">
                id_language = Number('.$dl.');

                function RetourLangueCheckUp(ArrayCheckedLang, idLangEnCheck, idLangDefaut) {
                    if (ArrayCheckedLang.length > 0)
                        return ArrayCheckedLang[0];
                    else
                        return idLangDefaut;
                }

                $(function() {
            ';

        if (Tools::getValue('idSB')) {
            if (!Tools::getValue('languesup') && count($lln) == 1) {
                $this->html_out .= 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.(int)$lln[0].', \'\');';
            }
        } else {
            $acl = array();
            if (Tools::getValue('languesup')) {
                $acl = Tools::getValue('languesup');
            }


            if (count($acl) == 1) {
                $this->html_out .= 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.(int)$acl[0].', \'\');';
            } else {
                $this->html_out .= 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.(int)$dl.', \'\');';
            }
        }
        $this->html_out .= '
                $("input[name=\'languesup[]\']").click(function() {
                    if (this.checked)
                        changeTheLanguage(\'title\', \''.$div_lang_name.'\', this.value, \'\');
                    else {
                        selectedL = new Array();
                        $("input[name=\'languesup[]\']:checked").each(function() {selectedL.push($(this).val());});
                        changeTheLanguage(\'title\', \''.$div_lang_name.'\',
                            RetourLangueCheckUp(selectedL, this.value, '.$dl.'), \'\');
                    }
                });

                $("#submitForm").click(function( event ) {
                    test = 0;
                    $("input[name=\'languesup[]\']:checked").each(function() {
                        test += 1;
                    });
                    if(test == 0) {
                        $("input[name=\'languesup[]\'][value='.$dl.']").prop("checked","true");
                    }
                });
            });

            function changeTheLanguage(title, divLangName, id_lang, iso) {
                $("#imgCatLang").attr("src", "../img/l/" + id_lang + ".jpg");
                return changeLanguage(title, divLangName, id_lang, iso);
            }
        </script>';

        $this->html_out .= $this->displayFormOpen('icon-edit', $legend_title, $this->confpath, 'formWithSelectLang');
        if (Tools::getValue('idSB')) {
            $this->html_out .= '<input type="hidden" name="idSB" value="'.(int)Tools::getValue('idSB').'" />';
            $this->html_out .= '<input type="hidden" name="position" value="'.(int)$sub_blocks->position.'" />';
        }

        //***********************************************************
        $html_libre = '<span id="check_lang_prestablog">';
        $checkmelang = '
            <input
                type="checkbox"
                name="checkmelang"
                class="noborder"
                onclick="checkDelBoxes(this.form, \'languesup[]\', this.checked)"
            />
            '.$this->l('All').' | ';

        $html_libre .= (count($languages) == 1 ? '' : $checkmelang);

        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '<input type="checkbox" name="languesup[]" value="'.$lid.'"';
            if ((Tools::getValue('idSB') && in_array((int)$lid, $lln))
                || (Tools::getValue('languesup') && in_array((int)$lid, Tools::getValue('languesup')))
            ) {
                $html_libre .= ' checked=checked';
            }
            $chlg = 'changeTheLanguage(\'title\', \''.$div_lang_name.'\', '.$lid.', \''.$language['iso_code'].'\');';
            $html_libre .= ' '.(count($languages) == 1 ? 'style="display:none;"' : '').' />
                <img
                    src="../img/l/'.(int)$lid.'.jpg"
                    class="pointer"
                    alt="'.$language['name'].'"
                    title="'.$language['name'].'"
                    onclick="'.$chlg.'"
                />';
        }
        $html_libre .= '</span>';

        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Language'), $html_libre, 'col-lg-7');
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="title_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="title_'.$lid.'"
                        id="title_'.$lid.'"
                        value="'.(isset($sub_blocks->title[$lid]) ? $sub_blocks->title[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Title'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('title', $div_lang_name)
        );
        //***********************************************************
        $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('List'),
            'select_type',
            $sub_blocks->select_type,
            $sub_blocks->getListeSelectType(),
            null,
            'col-lg-5'
        );
        //***********************************************************
        $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Hook'),
            'hook_name',
            (Tools::getValue('preselecthook') ? Tools::getValue('preselecthook') : $sub_blocks->hook_name),
            $sub_blocks->getListeHook(),
            null,
            'col-lg-5'
        );
        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Template'),
            'template',
            $sub_blocks->template,
            60,
            'col-lg-6',
            null,
            sprintf(
                $this->l('Leave blank to use the default template %1$s'),
                '<strong>'.self::getT().'_page-subblock.tpl</strong>'
            )
        );
        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Number of news to display'),
            'nb_list',
            $sub_blocks->nb_list,
            10,
            'col-lg-4'
        );
        //***********************************************************
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Title length'),
            'title_length',
            $sub_blocks->title_length,
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Description length'),
            'intro_length',
            $sub_blocks->intro_length,
            10,
            'col-lg-4',
            $this->l('caracters')
        );
        //***********************************************************
        /* DEBUT PERIODE */
        $html_libre = '<div class="blocmodule">';
        $html_libre .= $this->displayFormDateWithActivation(
            'col-lg-1',
            $this->l('From'),
            'date_start',
            $sub_blocks->date_start,
            true,
            'use_date_start',
            $sub_blocks->use_date_start
        );
        $html_libre .= $this->displayFormDateWithActivation(
            'col-lg-1',
            $this->l('To'),
            'date_stop',
            $sub_blocks->date_stop,
            true,
            'use_date_stop',
            $sub_blocks->use_date_stop
        );
        $html_libre .= '</div>';

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Use a period'),
            $html_libre,
            'col-lg-6'
        );
        /* FIN PERIODE */
        //***********************************************************

        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem(
            'col-lg-2',
            $this->l('Random list'),
            'random',
            $sub_blocks->random,
            $this->l('This option will randomize your list.')
        );
        //***********************************************************

        //***********************************************************
        /* DEBUT CATEGORIES */
        $html_libre = '';
        $html_libre .= '
            <div class="blocmodule">
                <table cellspacing="0" cellpadding="0" class="table">
                    <thead>
                        <tr>
                            <th style="width:20px;"><input type="checkbox" name="checkme" class="noborder"
                            onclick="checkDelBoxes(this.form, \'categories[]\', this.checked)" /></th>
                            <th style="width:20px;">'.$this->l('ID').'</th>
                            <th style="width:60px;">'.$this->l('Image').'</th>
                            <th>'.$this->l('Name').'&nbsp;<img id="imgCatLang" src="../img/l/'.$dl.'.jpg"
                            style="vertical-align:middle;" /></th>
                        </tr>
                    </thead>';

        $liste_cat = CategoriesClass::getListe((int)$this->context->language->id, 0);
        $liste_cat_no_arbre = CategoriesClass::getListeNoArbo();
        $liste_cat_branches_actives = array();

        foreach (SubBlocksClass::getCategories($sub_blocks->id, 0) as $value) {
            $liste_cat_branches_actives = array_unique(
                array_merge(
                    $liste_cat_branches_actives,
                    preg_split('/\./', CategoriesClass::getBranche((int)$value))
                )
            );
        }

        $html_libre .= $this->displayListeArborescenceCategoriesSubBlocks($liste_cat, 0, $liste_cat_branches_actives);

        $html_libre .= '
            </table>
        </div>
        <script language="javascript" type="text/javascript">
            $(document).ready(function() {
                $(".catlang").hide();
                $(".catlang[rel="+id_language+"]").show();

                $("div.language_flags img, #check_lang_prestablog img").click(function() {
                    $(".catlang").hide();
                    $(".catlang[rel="+id_language+"]").show();
                    $("#imgCatLang").attr("src", "../img/l/" + id_language + ".jpg");
                });
                ';

        foreach ($liste_cat_branches_actives as $value) {
            $html_libre .= '$("tr#prestablog_categorie_'.$value.'").show();';
        }

        foreach ($liste_cat_no_arbre as $value) {
            if (in_array((int)$value['parent'], $liste_cat_branches_actives)) {
                $html_libre .= '$("tr#prestablog_categorie_'.$value['id_prestablog_categorie'].'").show();';
            }
        }

        $html_libre .= '
                $("img.expand-cat").click(function() {
                    BranchClick=$(this).attr("rel");
                    BranchClickSplit = BranchClick.split(\'.\');
                    fixBranchClickSplit = "0,"+BranchClickSplit.toString();

                    switch ($(this).attr("src")) {
                        case "/../modules/prestablog/views/img/expand.gif":
                            $("tr.prestablog_branch").each(function() {
                                BranchParent = $(this).attr("rel");
                                BranchParentSplit = BranchParent.split(\'.\');
                                fixBranchParentSplit = "0,"+BranchParentSplit.toString();

                                if ($.isSubstring(fixBranchParentSplit, fixBranchClickSplit)
                                        && BranchClick != BranchParent
                                        && BranchClickSplit.length+1 == BranchParentSplit.length
                                    ) {
                                        $(this).show();
                                }
                            });
                            $(this).attr("src", "/../modules/prestablog/views/img/collapse.gif");
                            break;

                        case "/../modules/prestablog/views/img/collapse.gif":
                            $("tr.prestablog_branch").each(function() {
                                BranchParent = $(this).attr("rel");
                                BranchParentSplit = BranchParent.split(\'.\');
                                fixBranchParentSplit = "0,"+BranchParentSplit.toString();

                                if ($.isSubstring(fixBranchParentSplit, fixBranchClickSplit)
                                        && BranchClick != BranchParent
                                    ) {
                                        $(this).hide();
                                        $(this).find("img.expand-cat").each(function() {
                                            $(this).attr("src", "/../modules/prestablog/views/img/expand.gif");
                                        });
                                }
                            });
                            $(this).attr("src", "/../modules/prestablog/views/img/expand.gif");
                            break;
                    }
                });
            });
            jQuery.isSubstring = function(haystack, needle) {
                 return haystack.indexOf(needle) !== -1;
            };
        </script>';
        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Categories'), $html_libre, 'col-lg-5');
        /* FIN CATEGORIES */
        //***********************************************************

        //***********************************************************
         $this->html_out .= '<div class="john" style="display:none;">';

        $this->html_out .= $this->displayFormEnableItem(
            'col-lg-2',
            $this->l('Blog link'),
            'blog_link',
            $sub_blocks->blog_link,
            $this->l('Show link to the blog')
        );
         $this->html_out .= '</div>';
        //***********************************************************

        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem(
            'col-lg-2',
            $this->l('Activate'),
            'actif',
            $sub_blocks->actif
        );
        //***********************************************************

        $this->html_out .= '<div class="margin-form">';

        if (Tools::getValue('idSB')) {
            $this->html_out .= '
                <button class="btn btn-primary" id="submitForm" name="submitUpdateSubBlock">
                    <i class="icon-save"></i>&nbsp;'.$this->l('Update').'
                </button>';
        } else {
            $this->html_out .= '
                <button class="btn btn-primary" id="submitForm" name="submitAddSubBlock">
                    <i class="icon-plus"></i>&nbsp;'.$this->l('Add').'
                </button>';
        }

        $this->html_out .= '</div>';
        $this->html_out .= $this->displayFormClose();
    }

    private function displayFormAntiSpam()
    {
        $dl = $this->langue_default_store;
        $languages = Language::getLanguages(true);
        $div_lang_name = 'question¤reply';

        $legend_title = $this->l('Add an AntiSpam question');
        if (Tools::getValue('idAS')) {
            $antispam = new AntiSpamClass((int)Tools::getValue('idAS'));
            $legend_title = $this->l('Update the AntiSpam question');
        } else {
            $antispam = new AntiSpamClass();
        }

        if (Tools::isSubmit('submitUpdateAntiSpam') || Tools::isSubmit('submitAddAntiSpam')) {
            $antispam->id_shop = (int)$this->context->shop->id;
            $antispam->copyFromPost();
        }

        $this->html_out .= '<script type="text/javascript">id_language = Number('.$dl.');</script>';

        $this->html_out .= $this->displayFormOpen('icon-edit', $legend_title, $this->confpath);

        if (Tools::getValue('idAS')) {
            $this->html_out .= '<input type="hidden" name="idAS" value="'.Tools::getValue('idAS').'" />';
        }
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="question_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="question_'.$lid.'"
                        id="question_'.$lid.'"
                        value="'.(isset($antispam->question[$lid]) ? $antispam->question[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Question'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('question', $div_lang_name)
        );
        //***********************************************************
        $html_libre = '';
        foreach ($languages as $language) {
            $lid = (int)$language['id_lang'];
            $html_libre .= '
                <div
                    id="reply_'.$lid.'"
                    style="display: '.($lid == $dl ? 'block' : 'none').';"
                >
                    <input
                        type="text"
                        name="reply_'.$lid.'"
                        id="question_'.$lid.'"
                        value="'.(isset($antispam->reply[$lid]) ? $antispam->reply[$lid] : '').'"
                    />
                </div>';
        }

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Expected reply'),
            $html_libre,
            'col-lg-7',
            $this->displayFlagsFor('reply', $div_lang_name)
        );
        //***********************************************************
        $this->html_out .= $this->displayFormEnableItem('col-lg-2', $this->l('Activate'), 'actif', $antispam->actif);
        //***********************************************************

        $this->html_out .= '<div class="margin-form">';

        if (Tools::getValue('idAS')) {
            $this->html_out .= '
                <button class="btn btn-primary" name="submitUpdateAntiSpam" type="submit">
                    <i class="icon-save"></i>&nbsp;'.$this->l('Update the AntiSpam question').'
                </button>';
        } else {
            $this->html_out .= '
                <button class="btn btn-primary" name="submitAddAntiSpam" type="submit">
                    <i class="icon-plus"></i>&nbsp;'.$this->l('Add the AntiSpam question').'
                </button>';
        }

        $this->html_out .= '</div>';
        $this->html_out .= $this->displayFormClose();
    }

    private function displayFormComments()
    {
        $legend_title = $this->l('Add a comment');
        if (Tools::getValue('idC')) {
            $legend_title = $this->l('Update the comment');
            $comment = new CommentNewsClass((int)Tools::getValue('idC'));
        } else {
            $comment = new CommentNewsClass();
            $comment->copyFromPost();
        }

        $this->html_out .= $this->displayFormOpen('icon-edit', $legend_title, $this->confpath);

        if (Tools::getValue('idC')) {
            $this->html_out .= '<input type="hidden" name="idC" value="'.Tools::getValue('idC').'" />';
        }

        $title_news = NewsClass::getTitleNews((int)$comment->news, (int)$this->context->language->id);

        $this->html_out .= $this->displayFormLibre(
            'col-lg-2',
            $this->l('Parent news'),
            '<a
                href="'.$this->confpath.'&editNews&idN='.$comment->news.'"
                onclick="return confirm(\''.$this->l('You will leave this page. Are you sure ?').'\');"
            >
                '.$title_news.'
            </a>',
            'col-lg-5'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Name'),
            'name',
            $comment->name,
            50,
            'col-lg-4'
        );
        $this->html_out .= $this->displayFormInput(
            'col-lg-2',
            $this->l('Url'),
            'url',
            $comment->url,
            80,
            'col-lg-6',
            null,
            null,
            '<i class="icon-external-link"></i>'
        );

        $html_libre = '<textarea id="comment" name="comment">'.$comment->comment.'</textarea>';
        $this->html_out .= $this->displayFormLibre('col-lg-2', $this->l('Comment'), $html_libre, 'col-lg-7');
        $this->html_out .= $this->displayFormDate('col-lg-2', $this->l('Date'), 'date', $comment->date, true);
        $this->html_out .= $this->displayFormSelect(
            'col-lg-2',
            $this->l('Status'),
            'actif',
            $comment->actif,
            array(
                '-1' => $this->l('Pending'),
                '1' => $this->l('Enabled'),
                '0' => $this->l('Disabled')
            ),
            null,
            'col-lg-3',
            null,
            null,
            '<i class="icon-eye"></i>'
        );

        $this->html_out .= '<div class="margin-form">';

        if (Tools::getValue('idC')) {
            $this->html_out .= '<div class="col-lg-3">';
            $this->html_out .= '
                <button class="btn btn-primary" name="submitUpdateComment" type="submit">
                    <i class="icon-save"></i>&nbsp;'.$this->l('Update the comment').'
                </button>';
            $this->html_out .= '</div>';
            $this->html_out .= '<div class="col-lg-2">';
            $this->html_out .= '
                <a
                    class="btn btn-default"
                    href="'.$this->confpath.'&deleteComment&idC='.$comment->id.'"
                    onclick="return confirm(\''.$this->l('Are you sure?').'\');"
                >
                    <i class="icon-trash-o"></i>&nbsp;'.$this->l('Delete the comment').'
                </a>';
            $this->html_out .= '</div>';
        } else {
            $this->html_out .= '<div class="col-lg-2">';
            $this->html_out .= '
                <button class="btn btn-primary" name="submitAddComment" type="submit">
                    <i class="icon-plus"></i>&nbsp;'.$this->l('Add the comment').'
                </button>';
            $this->html_out .= '</div>';
        }

        $this->html_out .= '</div>';

        $this->html_out .= $this->displayFormClose();
    }

    private function deleteAllImagesThemes($id)
    {
        foreach (self::scanListeThemes() as $value_theme) {
            $pathdel = _PS_MODULE_DIR_.$this->name.'/views/img/'.$value_theme.'/up-img/';
            $config_theme = $this->getConfigXmlTheme($value_theme);
            $config_theme_array = PrestaBlog::objectToArray($config_theme);
            foreach (array_keys($config_theme_array['images']) as $key_theme_array) {
                self::unlinkFile($pathdel.$key_theme_array.'_'.$id.'.jpg');
            }
            self::unlinkFile($pathdel.$id.'.jpg');
            self::unlinkFile($pathdel.'admincrop_'.$id.'.jpg');
            self::unlinkFile($pathdel.'adminth_'.$id.'.jpg');
        }

        return true;
    }

    public function deleteAllImagesThemesCat($id)
    {
        foreach (self::scanListeThemes() as $value_theme) {
            $pathdel = _PS_MODULE_DIR_.$this->name.'/views/img/'.$value_theme.'/up-img/';
            $config_theme = $this->getConfigXmlTheme($value_theme);
            $config_theme_array = PrestaBlog::objectToArray($config_theme);
            foreach (array_keys($config_theme_array['categories']) as $key_theme_array) {
                self::unlinkFile($pathdel.'/c/'.$key_theme_array.'_'.$id.'.jpg');
            }
            self::unlinkFile($pathdel.'c/'.$id.'.jpg');
            self::unlinkFile($pathdel.'c/admincrop_'.$id.'.jpg');
            self::unlinkFile($pathdel.'c/adminth_'.$id.'.jpg');
        }

        return true;
    }

    public function deleteAllImagesThemesLookbook($id)
    {
        foreach (self::scanListeThemes() as $value_theme) {
            $pathdel = _PS_MODULE_DIR_.$this->name.'/views/img/'.$value_theme.'/up-img/';
            self::unlinkFile($pathdel.'lookbook/'.$id.'.jpg');
            self::unlinkFile($pathdel.'lookbook/lbcrop_'.$id.'.jpg');
            self::unlinkFile($pathdel.'lookbook/adminth_'.$id.'.jpg');
        }

        return true;
    }

    private function uploadImage($file_image, $id, $w, $h, $folder = null)
    {
        if (isset($file_image) && isset($file_image['tmp_name']) && !empty($file_image['tmp_name'])) {
            $tmpname = false;
            Configuration::set('PS_IMAGE_GENERATION_METHOD', 1);
            if (ImageManager::validateUpload($file_image, $this->max_image_size)) {
                return false;
            } elseif (!$tmpname = tempnam(_PS_TMP_IMG_DIR_, 'PS')) {
                return false;
            } elseif (!move_uploaded_file($file_image['tmp_name'], $tmpname)) {
                return false;
            } else {
                foreach (self::scanListeThemes() as $value_theme) {
                    if (!$this->imageResize(
                        $tmpname,
                        self::imgPath().$value_theme.'/up-img/'.($folder ? $folder.'/' : '').$id.'.jpg',
                        $w,
                        $h
                    )) {
                        return false;
                    }
                }
            }

            if (isset($tmpname)) {
                unlink($tmpname);
            }
        }

        return true;
    }

    private function imageResize($fichier_avant, $fichier_apres, $dest_width, $dest_height)
    {
        list($image_width, $image_height, $type) = getimagesize($fichier_avant);
        $source_image = ImageManager::create($type, $fichier_avant);

        if ($image_width > $dest_width || $image_height > $dest_height) {
            $proportion = $dest_width / $image_width;
            $dest_height = $image_height * $proportion;
            $dest_width = $dest_width;
        } else {
            $dest_height = $image_height;
            $dest_width = $image_width;
        }
        $dest_image = imagecreatetruecolor($dest_width, $dest_height);
        imagecopyresampled(
            $dest_image,
            $source_image,
            0,
            0,
            0,
            0,
            $dest_width + 1,
            $dest_height + 1,
            $image_width,
            $image_height
        );
        return ImageManager::write('jpg', $dest_image, $fichier_apres);
        //return ImageManager::resize($fichier_avant, $fichier_apres, $dest_width, $dest_height);
    }

    public function scanDirectory($directory)
    {
        $output = array();

        if (is_dir($directory)) {
            $my_directory = opendir($directory);

            while ($entry = self::readDirectory($my_directory)) {
                if ($entry != '.' && $entry != '..') {
                    if (is_dir($directory.'/'.$entry)) {
                        $output[] = $entry;
                    }
                }
            }
            closedir($my_directory);
        }

        return $output;
    }

    public function scanFilesDirectory($directory, $expections = null)
    {
        $output = array();
        if (!is_dir($directory)) {
            return array();
        }

        $my_directory = opendir($directory);

        while ($entry = self::readDirectory($my_directory)) {
            if ($entry != '.' && $entry != '..') {
                if (count($expections) > 0) {
                    if (is_file($directory.'/'.$entry) && !in_array($entry, $expections)) {
                        $output[] = $entry;
                    }
                } elseif (is_file($directory.'/'.$entry)) {
                    $output[] = $entry;
                }
            }
        }
        closedir($my_directory);
        return $output;
    }

    public static function copyRecursive($source, $dest)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return self::copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            self::copyRecursive($source.'/'.$entry, $dest.'/'.$entry);
        }

        // Clean up
        $dir->close();
        return true;
    }

    public static function getConfigXmlTheme($theme)
    {
        $config_file = _PS_MODULE_DIR_.'prestablog/views/config/'.$theme.'.xml';
        $xml_exist = file_exists($config_file);

        if ($xml_exist) {
            return simplexml_load_file($config_file);
        } else {
            self::generateConfigXmlTheme($theme);
            return self::getConfigXmlTheme($theme);
        }
    }

    private function retourneTexteBalise($text, $debut, $fin)
    {
        $debut = strpos($text, $debut) + Tools::strlen($debut);
        $fin = strpos($text, $fin);
        return Tools::substr($text, $debut, $fin - $debut);
    }

    protected static function generateConfigXmlTheme($theme)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <theme>
                <images>
                    <thumb> <!--Image prevue pour les miniatures dans les listes -->
                        <width>'.Configuration::get('prestablog_thumb_picture_width').'</width>
                        <height>'.Configuration::get('prestablog_thumb_picture_height').'</height>
                    </thumb>
                    <slide> <!--Image prevue pour les slides -->
                        <width>'.Configuration::get('prestablog_slide_picture_width').'</width>
                        <height>'.Configuration::get('prestablog_slide_picture_height').'</height>
                    </slide>
                </images>
                <categories>
                    <thumb> <!--Image prevue pour les miniatures des categories -->
                        <width>'.Configuration::get('prestablog_thumb_cat_width').'</width>
                        <height>'.Configuration::get('prestablog_thumb_cat_height').'</height>
                    </thumb>
                    <full> <!--Image prevue pour la description de la categorie en liste 1ere page -->
                        <width>'.Configuration::get('prestablog_full_cat_width').'</width>
                        <height>'.Configuration::get('prestablog_full_cat_height').'</height>
                    </full>
                </categories>
            </theme>';
        if (is_writable(_PS_MODULE_DIR_.'prestablog/views/config/')) {
            file_put_contents(_PS_MODULE_DIR_.'prestablog/views/config/'.$theme.'.xml', utf8_encode($xml));
        }
    }

    private function cleanMetaKeywords($keywords)
    {
        if (!empty($keywords) && $keywords != '') {
            $out = array();
            $words = explode(',', $keywords);
            foreach ($words as $word_item) {
                $word_item = trim($word_item);
                if (!empty($word_item) && $word_item != '') {
                    $out[] = $word_item;
                }
            }
            return ((count($out) > 0) ? implode(',', $out) : '');
        } else {
            return '';
        }
    }

    public static function prestablogContent($params)
    {

        return $params['return'];
    }

    public static function prestablogUrl($params)
    {

        $base_url_blog = 'blog';
        //$base_url_blog = 'articles';

        $param = null;
        $ok_rewrite = '';
        $ok_rewrite_id = '';
        $ok_rewrite_do = '';
        $ok_rewrite_cat = '';
        $ok_rewrite_categorie = '';
        $ok_rewrite_page = '';
        $ok_rewrite_titre = '';
        $ok_rewrite_seo = '';
        $ok_rewrite_year = '';
        $ok_rewrite_month = '';

        $ko_rewrite = '';
        $ko_rewrite_id = '';
        $ko_rewrite_do = '';
        $ko_rewrite_cat = '';
        $ko_rewrite_page = '';
        $ko_rewrite_year = '';
        $ko_rewrite_month = '';

        if (isset($params['do']) && $params['do'] != '') {
            $ko_rewrite_do = 'do='.$params['do'];
            $ok_rewrite_do = $params['do'];
            $param += 1;
        }
        if (isset($params['id']) && $params['id'] != '') {
            $ko_rewrite_id = '&id='.$params['id'];
            $ok_rewrite_id = '-n'.$params['id'];
            $param += 1;
        }
        if (isset($params['c']) && $params['c'] != '') {
            $ko_rewrite_cat = '&c='.$params['c'];
            $ok_rewrite_cat = '-c'.$params['c'];
            $param += 1;
        }
        if (isset($params['start']) && isset($params['p']) && $params['start'] != '' && $params['p'] != '') {
            $ko_rewrite_page = '&start='.$params['start'].'&p='.$params['p'];
            $ok_rewrite_page = $params['start'].'p'.$params['p'];
            $param += 1;
        }
        if (isset($params['titre']) && $params['titre'] != '') {
            $ok_rewrite_titre = PrestaBlog::prestablogFilter(Tools::link_rewrite($params['titre']));
            $param += 1;
        }
        if (isset($params['categorie']) && $params['categorie'] != '') {
            $ok_rewrite_categorie = PrestaBlog::prestablogFilter(Tools::link_rewrite($params['categorie']));
            if (isset($params['start']) && isset($params['p']) && $params['start'] != '' && $params['p'] != '') {
                $ok_rewrite_categorie .=  '-';
            } else {
                $ok_rewrite_categorie .=  '';
            }
            $param += 1;
        }
        if (isset($params['seo']) && $params['seo'] != '') {
            $ok_rewrite_titre = PrestaBlog::prestablogFilter(Tools::link_rewrite($params['seo']));
            $param += 1;
        }
        if (isset($params['y']) && $params['y'] != '') {
            $ko_rewrite_year = '&y='.$params['y'];
            $ok_rewrite_year = 'y'.$params['y'];
            $param += 1;
        }
        if (isset($params['m']) && $params['m'] != '') {
            $ko_rewrite_month = '&m='.$params['m'];
            $ok_rewrite_month = '-m'.$params['m'];
            $param += 1;
        }
        if (isset($params['seo']) && $params['seo'] != '') {
            $ok_rewrite_seo = $params['seo'];
            $ok_rewrite_titre = '';
            $param += 1;
        }
        if (isset($params) && count($params) > 0 && !isset($params['rss'])) {
            $ok_rewrite = $base_url_blog.'/'.$ok_rewrite_do.$ok_rewrite_categorie.$ok_rewrite_page;
            $ok_rewrite .= $ok_rewrite_year.$ok_rewrite_month.$ok_rewrite_titre.$ok_rewrite_seo;
            $ok_rewrite .= $ok_rewrite_cat.$ok_rewrite_id;

            $ko_rewrite = '?fc=module&module=prestablog&controller=blog&'.ltrim(
                $ko_rewrite_do.$ko_rewrite_id.$ko_rewrite_cat.$ko_rewrite_page.$ko_rewrite_year.$ko_rewrite_month,
                '&'
            );
        } elseif (isset($params['rss'])) {
            if ($params['rss'] == 'all') {
                $ok_rewrite = 'rss';
                $ko_rewrite = '?fc=module&module=prestablog&controller=rss';
            } else {
                $ok_rewrite = 'rss/'.$params['rss'];
                $ko_rewrite = '?fc=module&module=prestablog&controller=rss&rss='.$params['rss'];
            }
        } else {
            $ok_rewrite = $base_url_blog;
            $ko_rewrite = '?fc=module&module=prestablog&controller=blog';
        }

        if (!isset($params['id_lang'])) {
            (int)$params['id_lang'] = null;
        }

        if ((int)Configuration::get('PS_REWRITING_SETTINGS') && (int)Configuration::get('prestablog_rewrite_actif')) {
            return self::getBaseUrlFront((int)$params['id_lang']).$ok_rewrite;
        } else {
            return self::getBaseUrlFront((int)$params['id_lang']).$ko_rewrite;
        }
    }

    public static function getBaseUrlFront($id_lang = null)
    {

        return self::urlSRoot().self::getLangLink($id_lang);
    }

    public static function getLangLink($id_lang = null)
    {
        $context = Context::getContext();

        if (!Configuration::get('PS_REWRITING_SETTINGS')) {
            return '';
        }
        if (Language::countActiveLanguages() <= 1) {
            return '';
        }
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }

        return Language::getIsoById((int)$id_lang).'/';
    }

    public static function prestablogFilter($retourne)
    {
        $search = array ('/--+/');
        $replace = array ('-');

        $retourne = Tools::strtolower(preg_replace($search, $replace, $retourne));

        $url_replace = array(
            '/А/' => 'A', '/а/' => 'a',
            '/Б/' => 'B', '/б/' => 'b',
            '/В/' => 'V', '/в/' => 'v',
            '/Г/' => 'G', '/г/' => 'g',
            '/Д/' => 'D', '/д/' => 'd',
            '/Е/' => 'E', '/е/' => 'e',
            '/Ж/' => 'J', '/ж/' => 'j',
            '/З/' => 'Z', '/з/' => 'z',
            '/И/' => 'I', '/и/' => 'i',
            '/Й/' => 'Y', '/й/' => 'y',
            '/К/' => 'K', '/к/' => 'k',
            '/Л/' => 'L', '/л/' => 'l',
            '/М/' => 'M', '/м/' => 'm',
            '/Н/' => 'N', '/н/' => 'n',
            '/О/' => 'O', '/о/' => 'o',
            '/П/' => 'P', '/п/' => 'p',
            '/Р/' => 'R', '/р/' => 'r',
            '/С/' => 'S', '/с/' => 's',
            '/Т/' => 'T', '/т/' => 't',
            '/У/' => 'U', '/у/' => 'u',
            '/Ф/' => 'F', '/ф/' => 'f',
            '/Х/' => 'H', '/х/' => 'h',
            '/Ц/' => 'C', '/ц/' => 'c',
            '/Ч/' => 'CH', '/ч/' => 'ch',
            '/Ш/' => 'SH', '/ш/' => 'sh',
            '/Щ/' => 'SHT', '/щ/' => 'sht',
            '/Ъ/' => 'A', '/ъ/' => 'a',
            '/Ь/' => 'X', '/ь/' => 'x',
            '/Ю/' => 'YU', '/ю/' => 'yu',
            '/Я/' => 'YA', '/я/' => 'ya',
        );

        $cyrillic_find = array_keys($url_replace);
        $cyrillic_replace = array_values($url_replace);

        $retourne = Tools::strtolower(preg_replace($cyrillic_find, $cyrillic_replace, $retourne));

        return $retourne;
    }

    public static function getPrestaBlogMetaTagsNewsOnly($id_lang, $id = null)
    {
        if ($id) {
            $row = array();

            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT `title`, `meta_title`, `meta_description`, `meta_keywords`
                FROM `'.bqSQL(_DB_PREFIX_).'prestablog_news_lang`
                WHERE id_lang = '.(int)$id_lang.' AND id_prestablog_news = '.(int)$id);
        }
        if ($row) {
            return self::completeMetaTags($row);
        }
    }

    public static function getPrestaBlogMetaTagsNewsCat($id_lang, $id = null)
    {
        if ($id) {
            $row = array();

            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT `title`, `meta_title`, `meta_description`, `meta_keywords`
                FROM `'.bqSQL(_DB_PREFIX_).'prestablog_categorie_lang`
                WHERE id_lang = '.(int)$id_lang.' AND id_prestablog_categorie = '.(int)$id);
        }
        if ($row) {
            return self::completeMetaTags($row);
        }
    }

    public static function getPrestaBlogMetaTagsPage($id_lang)
    {
        $row = array(
            'title' => Configuration::get('prestablog_titlepageblog', (int)$id_lang),
            'meta_title' => Configuration::get('prestablog_titlepageblog', (int)$id_lang),
            'meta_description' => Configuration::get('prestablog_descpageblog', (int)$id_lang)
        );
        return self::completeMetaTags($row);
    }

    public static function getPrestaBlogMetaTagsNewsDate()
    {
        return self::completeMetaTags(null);
    }

    public static function completeMetaTags($meta_tags)
    {
        $context = Context::getContext();

        $prestablog = new PrestaBlog();

        if (empty($meta_tags['meta_title'])) {
            $meta_tags['meta_title'] = $meta_tags['title'];
        }
        if (empty($meta_tags['meta_description'])) {
            $meta_tags['meta_description'] = '';
        }
        if (empty($meta_tags['meta_keywords'])) {
            $meta_tags['meta_keywords'] = '';
            if (Configuration::get('PS_META_KEYWORDS', (int)$context->language->id)) {
                $meta_tags['meta_keywords'] = Configuration::get('PS_META_KEYWORDS', (int)$context->language->id);
            }
        }

        $metatile = '';

        $metatile .= (Tools::getValue('p') ? ' - '.$prestablog->l('page').' '.Tools::getValue('p') : '');
        $metatile .= (Tools::getValue('y') ? ' - '.Tools::getValue('y') : '');
        $metatile .= (Tools::getValue('m') ? ' - '.$prestablog->mois_langue[Tools::getValue('m')] : '');

        $meta_tags['meta_title'] .= $metatile;

        $metadesc = '';
        $metadesc .= (Tools::getValue('p') ? ' - '.$prestablog->l('page').' '.Tools::getValue('p') : '');
        $metadesc .= (Tools::getValue('y') ? ' - '.Tools::getValue('y') : '');
        $metadesc .= (Tools::getValue('m') ? ' - '.$prestablog->mois_langue[Tools::getValue('m')] : '');

        $meta_tags['meta_description'] .= $metadesc;

        $meta_tags['meta_title'] = ltrim($meta_tags['meta_title'], ' - ');
        $meta_tags['meta_description'] = ltrim($meta_tags['meta_description'], ' - ');

        return $meta_tags;
    }

    public static function getPagination($count_liste, $entites_en_moins = 0, $end = 10, $start = 0, $p = 1)
    {
        $pagination = array();

        $pagination['NombreTotalEntites'] = ($count_liste - $entites_en_moins);

        $pagination['NombreTotalPages'] = ceil((int)$pagination['NombreTotalEntites'] / (int)$end);

        if ($pagination['NombreTotalEntites'] > 0) {
            if ($p) {
                $pagination['PageCourante'] = (int)$p;
                $pagination['PagePrecedente'] = (int)$p - 1;
                $pagination['PageSuivante'] = (int)$p + 1;
            } else {
                $pagination['PageCourante'] = 1;
                $pagination['PagePrecedente'] = 0;
                $pagination['PageSuivante'] = 2;
            }

            if ($start) {
                $pagination['StartCourant'] = (int)$start;
                $pagination['StartPrecedent'] = (int)$start - (int)$end;
                $pagination['StartSuivant'] = (int)$start + (int)$end;
            } else {
                $pagination['StartCourant'] = 0;
                $pagination['StartPrecedent'] = 0;
                $pagination['StartSuivant'] = (int)$end;
            }
            for ($icount = 1; $icount <= (int)$pagination['NombreTotalPages']; $icount++) {
                $pagination['Pages'][$icount] = ($icount - 1) * (int)$end;
            }

            if (count($pagination['Pages']) <= 5) {
                $pagination['PremieresPages'] = array_slice($pagination['Pages'], 0, 5, true);
                unset($pagination['Pages']);
            } else {
                $pagination['PremieresPages'] = array_slice($pagination['Pages'], 0, 1, true);
                if ($pagination['PageCourante'] == 1) {
                    $pagination['Pages'] = array_slice(
                        $pagination['Pages'],
                        $pagination['PageCourante'] - 1,
                        6,
                        true
                    );
                } else {
                    if ($pagination['PageCourante'] + 4 >= $pagination['NombreTotalPages']) {
                        $pagination['Pages'] = array_slice(
                            $pagination['Pages'],
                            $pagination['NombreTotalPages'] - 5,
                            5,
                            true
                        );
                    } else {
                        $pagination['Pages'] = array_slice(
                            $pagination['Pages'],
                            $pagination['PageCourante'] - 1,
                            5,
                            true
                        );
                    }
                }
            }
        }

        return $pagination;

    }

    public function autocropImage($image_source, $rep_source, $rep_dest, $tl, $th, $prefixe, $change_nom)
    {
        $tl = (int)$tl;
        $th = (int)$th;
        $tr = $tl / $th;

        $full_path = $rep_source.$image_source;
        $extensionsource = preg_replace('/.*\.([^.]+)$/', '\\1', $image_source);

        switch ($extensionsource) {
            case 'png':
                $imagesource = imagecreatefrompng($full_path);
                break;

            case 'jpg':
                $imagesource = imagecreatefromjpeg($full_path);
                break;

            case 'jpeg':
                $imagesource = imagecreatefromjpeg($full_path);
                break;

            default:
                break;
        }

        $sl = (int)imagesx($imagesource);
        $sh = (int)imagesy($imagesource);

        $sr = $sl / $sh;

        if ($sr > $tr) {
            $nh = $th;
            $nl = ($nh * $sl) / $sh;
        } elseif ($sr < $tr) {
            $nl = $tl;
            $nh = ($nl * $sh) / $sl;
        } elseif ($sr == $tr) {
            $nh = $th;
            $nl = $tl;
        }

        if ($tr > 1) {
            $nx = 0;
            $ny = ($nh - $th) / 2;
        } elseif ($tr < 1) {
            $ny = 0;
            $nx = ($nl - $tl) / 2;
        } elseif ($tr == 1) {
            if ($sr > 1) {
                $ny = 0;
                $nx = ($nl - $tl) / 2;
            } elseif ($sr < 1) {
                $nx = 0;
                $ny = ($nh - $th) / 2;
            } elseif ($sr == 1) {
                $nx = 0;
                $ny = 0;
            }
        }

        $image_avant_crop = imagecreatetruecolor($nl, $nh);

        imagecopyresampled(
            $image_avant_crop,
            $imagesource,
            0,
            0,
            0,
            0,
            $nl,
            $nh,
            $sl,
            $sh
        );

        $dest_crop = imagecreatetruecolor($tl, $th);

        imagecopyresampled(
            $dest_crop,
            $image_avant_crop,
            0,
            0,
            $nx,
            $ny,
            $tl,
            $th,
            $tl,
            $th
        );

        if ($change_nom) {
            $image_source = $change_nom.'.jpg';
        }

        switch ($extensionsource) {
            case 'png':
                imagepng($dest_crop, $rep_dest.$prefixe.$image_source, 100);
                break;

            case 'jpg':
                imagejpeg($dest_crop, $rep_dest.$prefixe.$image_source, 100);
                break;

            case 'jpeg':
                imagejpeg($dest_crop, $rep_dest.$prefixe.$image_source, 100);
                break;
        }
        imagedestroy($image_avant_crop);
        imagedestroy($dest_crop);
    }

    public function cropImage(
        $image_source,
        $rep_source,
        $rep_dest,
        $w_image_base,
        $h_image_base,
        $w_image_dest,
        $h_image_dest,
        $x_crop_base,
        $y_crop_base,
        $w_crop_base,
        $h_crop_base,
        $prefixe,
        $change_nom
    ) {
        $full_path = $rep_source.$image_source;
        $ext = preg_replace('/.*\.([^.]+)$/', '\\1', $image_source);
        $dst_r = ImageCreateTrueColor($w_image_dest, $h_image_dest);

        list($w_image_source, $h_image_source) = getimagesize($full_path);

        $w_ratio = $w_image_source / $w_image_base;
        $h_ratio = $h_image_source / $h_image_base;

        $x_crop_base = $w_ratio * $x_crop_base;
        $y_crop_base = $h_ratio * $y_crop_base;
        $w_crop_base = $w_ratio * $w_crop_base;
        $h_crop_base = $h_ratio * $h_crop_base;

        switch ($ext) {
            case 'png':
                $image = imagecreatefrompng($full_path);
                break;

            case 'jpg':
                $image = imagecreatefromjpeg($full_path);
                break;

            case 'jpeg':
                $image = imagecreatefromjpeg($full_path);
                break;

            default:
                break;
        }
        imagecopyresampled(
            $dst_r,
            $image,
            0,
            0,
            $x_crop_base,
            $y_crop_base,
            $w_image_dest,
            $h_image_dest,
            $w_crop_base,
            $h_crop_base
        );

        if ($change_nom) {
            $image_source = $change_nom.'.jpg';
        }

        switch ($ext) {
            case 'png':
                imagepng($dst_r, $rep_dest.$prefixe.$image_source, 100);
                break;

            case 'jpg':
                imagejpeg($dst_r, $rep_dest.$prefixe.$image_source, 100);
                break;

            case 'jpeg':
                imagejpeg($dst_r, $rep_dest.$prefixe.$image_source, 100);
                break;
        }
        imagedestroy($dst_r);
    }

    public function gestAntiSpam()
    {
        if ($this->checksum != '') {
            return AntiSpamClass::getAntiSpamByChecksum($this->checksum);
        } else {
            $liste = AntiSpamClass::getListe((int)$this->context->language->id, 1);
            if (count($liste) > 0) {
                return $liste[array_rand($liste, 1)];
            } else {
                return false;
            }
        }
    }
    public function newsRatingID($id_news, $id_session) {
    	if(isset($id_session)) {
      NewsClass::insertRateId($id_news, $id_session);
         return true;
     } else {
     	return false;
     }
}
    public function newsRating($id_news, $rate) {
    	if(isset($rate)) {
			NewsClass::insertRating($id_news, $rate);
         return true;
     } else {
     	return false;
     }
}

    public function gestComment($id_news)
    {
        if (!Configuration::get($this->name.'_comment_actif')) {
            return false;
        }


        $errors = array();
        $is_submit = true;
        $content_form = array(
            'news' => (int)$id_news,
            'name' => trim(Tools::getValue('name')),
            'url' => trim(Tools::getValue('url')),
            'comment' => trim(Tools::getValue('comment')),
            'date' => Date('Y-m-d H:i:s'),
            'actif' => (Configuration::get($this->name.'_comment_auto_actif') ? 1 : 0 - 1),
            'antispam_checksum' => '',
        );

        if (Tools::getValue('submitComment')) {
            if (Configuration::get('prestablog_antispam_actif')) {
                $liste_as = AntiSpamClass::getListe((int)$this->context->language->id, 1);
                if (count($liste_as) > 0) {
                    foreach ($liste_as as $value_as) {
                        if (Tools::getIsset($value_as['checksum'])) {
                            $content_form['antispam_checksum'] = Tools::getValue($value_as['checksum']);
                            $this->checksum = $value_as['checksum'];
                            if (Tools::getValue($value_as['checksum']) != $value_as['reply']) {
                                $errors[$value_as['checksum']] = $this->l('Your antispam reply is not correct.');
                            }
                        }
                    }
                }
            }

            $ereg_url = '#^\b(((http|https)\:\/\/)[^\s()]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))$#';
            if (Tools::strlen($content_form['name']) < 3) {
                $errors['name'] = $this->l('Your name cannot be empty or inferior at 3 characters.');
            }
            if (Tools::strlen($content_form['comment']) < 5) {
                $errors['comment'] = $this->l('Your comment cannot be empty or inferior at 5 characters.');
            }
            if (Tools::strlen($content_form['url']) != '' && !preg_match($ereg_url, $content_form['url'])) {
                $errors['url'] = $this->l('Make sure the url is correct.');
            }

            if (Configuration::get('prestablog_captcha_actif')) {
                if (!($gcaptcha = (int)(Tools::getValue('g-recaptcha-response')))) {
                $errors['url'] = $this->l('Make sure to validate the captcha.');
                }
            }

            if (count($errors) > 0) {
                $is_submit = false;
            } else {

                CommentNewsClass::insertComment(
                    $content_form['news'],
                    $content_form['date'],
                    $content_form['name'],
                    $content_form['url'],
                    $content_form['comment'],
                    $content_form['actif']
                );

                if (Configuration::get($this->name.'_comment_alert_admin')) {
                    $news = new NewsClass((int)$content_form['news'], $this->langue_default_store);
                    $content_form['title_news'] = $news->title;

                    $urlnews = Tools::getShopDomainSsl(true).__PS_BASE_URI__.$this->ctrblog;
                    $urlnews .= '&id='.$content_form['news'];

                    Mail::Send(
                        $this->langue_default_store,
                        'feedback-admin',
                        $this->l('New comment').' / '.$content_form['title_news'],
                        array(
                            '{news}' => $content_form['news'],
                            '{title_news}' => $content_form['title_news'],
                            '{date}' => ToolsCore::displayDate($content_form['date'], null, true),
                            '{name}' => $content_form['name'],
                            '{url}' => $content_form['url'],
                            '{comment}' => $content_form['comment'],
                            '{url_news}' => $urlnews,
                            '{actif}' => $content_form['actif']
                        ),
                        Configuration::get($this->name.'_comment_admin_mail'),
                        null,
                        Configuration::get('PS_SHOP_EMAIL'),
                        Configuration::get('PS_SHOP_NAME'),
                        null,
                        null,
                        dirname(__FILE__).'/mails/'
                    );
                }

                $liste_abo = CommentNewsClass::listeCommentMailAbo($content_form['news']);

                if (Configuration::get($this->name.'_comment_subscription')
                    && count($liste_abo) > 0
                    && Configuration::get($this->name.'_comment_auto_actif')
                ) {
                    $news = new NewsClass((int)$content_form['news'], $this->langue_default_store);
                    $content_form['title_news'] = $news->title;

                    $urlnews = Tools::getShopDomainSsl(true).__PS_BASE_URI__.$this->ctrblog;
                    $urlnews .= '&id='.$content_form['news'];
                    $urldesabo = $urlnews.'&d='.$content_form['news'];

                    foreach ($liste_abo as $value_abo) {
                        Mail::Send(
                            $this->langue_default_store,
                            'feedback-subscribe',
                            $this->l('New comment').' / '.$content_form['title_news'],
                            array(
                                '{news}' => $content_form['news'],
                                '{title_news}' => $content_form['title_news'],
                                '{url_news}' => $urlnews,
                                '{url_desabonnement}' => $urldesabo
                            ),
                            $value_abo,
                            null,
                            Configuration::get('PS_SHOP_EMAIL'),
                            Configuration::get('PS_SHOP_NAME'),
                            null,
                            null,
                            dirname(__FILE__).'/mails/'
                        );
                    }
                }

                $is_submit = true;

            }
        } else {
            $is_submit = false;
        }

        $this->context->smarty->assign(array(
            'isSubmit' => $is_submit,
            'errors' => $errors,
            'content_form' => $content_form,
            'comments' => CommentNewsClass::getListe(1, $id_news),
        ));

        return true;
    }


    public function blocDateListe()
    {
        $actif_filtre = 'n.`actif` = 1';
        $multiboutique_filtre = ' AND n.`id_shop` = '.(int)$this->context->shop->id;
        $langue_filtre = ' AND nl.`id_lang` = '.(int)$this->context->language->id;
        $actif_langue_filtre = ' AND nl.`actif_langue` = 1';

        $filtre_groupes = PrestaBlog::getFiltreGroupes('cc.`categorie`', 'categorie');

        $all_filtres = $actif_filtre.$multiboutique_filtre.$langue_filtre.$actif_langue_filtre.$filtre_groupes;

        if (Configuration::get($this->name.'_datenews_actif')) {
            $result_date_liste = array();

            $fin_reelle = 'TIMESTAMP(n.`date`) <= \''.Date('Y/m/d H:i:s').'\'';

            $result_annee = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
                SELECT    DISTINCT YEAR(n.`date`) AS `annee`
                FROM `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'_lang` as nl
                LEFT JOIN `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'` as n
                    ON (n.`id_prestablog_news` = nl.`id_prestablog_news`)
                LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_correspondancecategorie` cc
                    ON (n.`id_prestablog_news` = cc.`news`)
                WHERE '.$all_filtres.'
                AND '.$fin_reelle.'
                ORDER BY annee '.pSQL(Configuration::get($this->name.'_datenews_order')));

            if (count($result_annee) > 0) {
                foreach ($result_annee as $value_annee) {
                    $result_count_annee = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
                        SELECT COUNT(DISTINCT nl.`id_prestablog_news`) AS `value`
                        FROM `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'_lang` as nl
                        LEFT JOIN `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'` as n
                            ON (n.`id_prestablog_news` = nl.`id_prestablog_news`)
                        LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_correspondancecategorie` cc
                            ON (n.`id_prestablog_news` = cc.`news`)
                        WHERE '.$all_filtres.'
                        AND '.$fin_reelle.'
                        AND YEAR(n.`date`) = \''.pSQL($value_annee['annee']).'\'');

                    $result_date_liste[$value_annee['annee']]['nombre_news'] = $result_count_annee['value'];

                    $result_mois = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
                        SELECT    DISTINCT MONTH(n.`date`) AS `mois`
                        FROM `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'_lang` as nl
                        LEFT JOIN `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'` as n
                            ON (n.`id_prestablog_news` = nl.`id_prestablog_news`)
                        LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_correspondancecategorie` cc
                            ON (n.`id_prestablog_news` = cc.`news`)
                        WHERE '.$all_filtres.'
                        AND YEAR(n.`date`) = '.pSQL($value_annee['annee']).'
                        AND '.$fin_reelle.'
                        ORDER BY mois '.pSQL(Configuration::get($this->name.'_datenews_order')));

                    if (count($result_mois) > 0) {
                        foreach ($result_mois as $value_mois) {
                            $result_count_mois = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
                                SELECT COUNT(DISTINCT n.`id_prestablog_news`) AS `value`
                                FROM `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'_lang` as nl
                                LEFT JOIN `'.bqSQL(_DB_PREFIX_).NewsClass::$table_static.'` as n
                                    ON (n.`id_prestablog_news` = nl.`id_prestablog_news`)
                                LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_correspondancecategorie` cc
                                    ON (n.`id_prestablog_news` = cc.`news`)
                                WHERE '.$all_filtres.'
                                AND '.$fin_reelle.'
                                AND YEAR(n.`date`) = '.pSQL($value_annee['annee']).'
                                AND MONTH(n.`date`) = '.pSQL($value_mois['mois']));

                            $e1 = $result_count_mois['value'];
                            $e2 = $this->mois_langue[$value_mois['mois']];

                            $result_date_liste[$value_annee['annee']]['mois'][$value_mois['mois']]['nombre_news'] = $e1;
                            $result_date_liste[$value_annee['annee']]['mois'][$value_mois['mois']]['mois_value'] = $e2;
                        }
                    }
                }
            }

            $this->context->smarty->assign(array(
                'ResultDateListe' => $result_date_liste,
                'prestablog_annee' => Tools::getValue('prestablog_annee'),
            ));

            $this->context->controller->registerJavascript(
                'modules-prestablog-dateliste',
                'modules/prestablog/views/js/dateliste.js',
                array('position' => 'bottom', 'priority' => 200)
            );

            return $this->display(__FILE__, self::getT().'_bloc-dateliste.tpl');
        }
    }

    public function blocLastListe()
    {

        if (Configuration::get($this->name.'_lastnews_actif')) {
            $tri_champ = 'n.`date`';
            $tri_ordre = 'desc';
            $liste = NewsClass::getListe(
                (int)$this->context->language->id,
                1,
                0,
                0,
                (int)Configuration::get($this->name.'_lastnews_limit'),
                $tri_champ,
                $tri_ordre,
                null,
                Date('Y/m/d H:i:s'),
                null,
                1,
                (int)Configuration::get('prestablog_lastnews_title_length'),
                (int)Configuration::get('prestablog_lastnews_intro_length')
            );

            $this->context->smarty->assign(array('ListeBlocLastNews' => $liste));

            return $this->display(__FILE__, self::getT().'_bloc-lastliste.tpl');
        }
    }

    public function blocCatListe()
    {
        $tplcatlist = _PS_MODULE_DIR_.'prestablog/views/templates/hook/';
        $tplcatlist .= self::getT().'_bloc-catliste-tree-branch.tpl';

        if (Configuration::get($this->name.'_catnews_actif')) {
            $categorie_courante = new CategoriesClass(
                (int)Tools::getValue('c'),
                (int)$this->context->cookie->id_lang
            );
            $categorie_parente = new CategoriesClass(
                (int)$categorie_courante->parent,
                (int)$this->context->cookie->id_lang
            );

            if (!Configuration::get($this->name.'_catnews_tree')) {
                $liste = CategoriesClass::getListe((int)$this->context->language->id, 1, (int)$categorie_courante->id);
            } else {
                $this->context->controller->registerJavascript(
                    'modules-prestablog-treecat',
                    'modules/prestablog/views/js/treeCategories.js',
                    array('position' => 'bottom', 'priority' => 200)
                );

                $liste = CategoriesClass::getListe((int)$this->context->language->id, 1);
            }

            if (count($liste) > 0) {
                foreach ($liste as $key => $value) {
                    if (!Configuration::get($this->name.'_catnews_empty')
                        && (int)$value['nombre_news_recursif'] == 0) {
                        unset($liste[$key]);
                    } else {
                        $liste[$key]['nombre_news'] = (int)$value['nombre_news'];
                    }
                }

                $this->context->smarty->assign(array(
                    'prestablog_categorie_courante' => $categorie_courante,
                    'prestablog_categorie_parent' => $categorie_parente,
                    'ListeBlocCatNews' => $liste,
                    'isDhtml' => (Configuration::get('BLOCK_CATEG_DHTML') == 1 ? true : false),
                    'tree_branch_path' => $tplcatlist
                ));

                return $this->display(__FILE__, self::getT().'_bloc-catliste.tpl');
            }
        }
    }

    public function hookDisplayNav()
    {
        return $this->display(__FILE__, self::getT().'_nav-top.tpl', $this->getCacheId());
    }

    public function hookDisplayBackOfficeHeader()
    {
        $ctrl = $this->context->controller;
        if ($ctrl instanceof AdminController && method_exists($ctrl, 'addCss')) {
            $ctrl->addCss($this->_path.'views/css/prestablog-back-office.css');
        }

         $this->context->controller->addCSS(
            $this->_path.'views/css/admin.css',
            'all'
        );
    }




    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS(
            $this->_path.'views/css/'.self::getT().'-module.css',
            'all'
        );
        $this->context->controller->addCSS(
            $this->_path.'views/css/'.self::getT().'-module-widget.css',
            'all'
        );
        $this->context->controller->addCSS(
            $this->_path.'views/css/blog'.(int)$this->context->shop->id.'.css',
            'all'
        );
        $this->context->controller->addCSS(
            $this->_path.'views/css/custom'.(int)$this->context->shop->id.'.css',
            'all'
        );
        $this->news = new NewsClass((int)Tools::getValue('id'), (int)$this->context->cookie->id_lang);
		$this->categories = new CategoriesClass((int)Tools::getValue('c'));

  if (Configuration::get($this->name.'_popup_general') == 1 ) {
		        	$id_prestablog_popup_news = PopupClass::getIdFrontPopupNewsPreFiltered($this->news->id);
        	$id_prestablog_popup_cate = PopupClass::getIdFrontPopupCatePreFiltered($this->categories->id);
$popuplink = PopupClass::getPopupActifHome();
    if (isset($popuplink[0]['id_prestablog_popup']))
    $id_prestablog_popup_home = $popuplink[0]['id_prestablog_popup'];

        if (isset($id_prestablog_popup_news) && $id_prestablog_popup_news == $this->isOkDisplay($this->news->id)) {
            $popup = new PopupClass((int)$id_prestablog_popup_news, (int)$this->context->language->id);
            $this->context->controller->addCss($this->_path.'views/css/bootstrap-modal.css');
            $this->context->controller->addCSS($this->_path.'views/css/theme-'.$popup->theme.'.css', 'all');
            $this->context->controller->addJS($this->_path.'views/js/popup.js');
        }
        if (isset($id_prestablog_popup_cate) && $id_prestablog_popup_cate == $this->isOkDisplayCate($this->categories->id)) {
            $popup = new PopupClass((int)$id_prestablog_popup_cate, (int)$this->context->language->id);
            $this->context->controller->addCss($this->_path.'views/css/bootstrap-modal.css');
            $this->context->controller->addCSS($this->_path.'views/css/theme-'.$popup->theme.'.css', 'all');
            $this->context->controller->addJS($this->_path.'views/js/popup.js');
        }
        if (isset($id_prestablog_popup_home) && $id_prestablog_popup_home == $this->isOkDisplayHome()) {
            $popup = new PopupClass((int)$id_prestablog_popup_home, (int)$this->context->language->id);
            $this->context->controller->addCss($this->_path.'views/css/bootstrap-modal.css');
            $this->context->controller->addCSS($this->_path.'views/css/theme-'.$popup->theme.'.css', 'all');
            $this->context->controller->addJS($this->_path.'views/js/popup.js');
        }

}

        $this->context->controller->addJS('https://www.google.com/recaptcha/api.js');
               foreach (self::scanListeThemes() as $theme) {
               	if ($theme = 'grid-for-1-7') {
         $this->context->controller->addJS($this->_path.'views/js/imagesloaded.pkgd.min.js');
        $this->context->controller->addJS($this->_path.'views/js/masonry.pkgd.min.js');
    }
}
        /* That is a fix to solve the bug of other modules that duplicate the displayheader process */
        if (!isset($this->context->smarty->registered_plugins['function']['PrestaBlogUrl'])) {
            smartyRegisterFunction(
                $this->context->smarty,
                'function',
                'PrestaBlogUrl',
                array('PrestaBlog', 'prestablogUrl')
            );
        }

        /* permet d'échaper tout le contenu html / js */
        if (!isset($this->context->smarty->registered_plugins['function']['PrestaBlogContent'])) {
            smartyRegisterFunction(
                $this->context->smarty,
                'function',
                'PrestaBlogContent',
                array('PrestaBlog', 'prestablogContent')
            );
        }

        /* permettre de determiner les infos de partage pour facebook, */
        /* uniquement si on est sur une news */

        if (isset($this->context->controller->module->name)
            && $this->context->controller->module->name == $this->name
            && Tools::getValue('id')
        ) {
            $this->news = new NewsClass((int)Tools::getValue('id'), (int)$this->context->cookie->id_lang);
            if (file_exists(_PS_MODULE_DIR_.'prestablog/views/img/'.self::getT().'/up-img/'.$this->news->id.'.jpg')) {
                $news_image_url = self::urlSRoot().'modules/prestablog/views/img/';
                $news_image_url .= self::getT().'/up-img/'.$this->news->id.'.jpg';
            } else {
                $news_image_url = self::urlSRoot().'img/logo.jpg';
            }

            /* moderateurs commentaires facebook */
            $list_fb_moderators = array();
            if (Configuration::get($this->name.'_commentfb_actif')) {
                $list_fb_moderators = unserialize(Configuration::get($this->name.'_commentfb_modosId'));
            }
            /* /moderateurs commentaires facebook */

            $this->context->smarty->assign(array(
                'prestablog_news_meta' => $this->news,
                'prestablog_news_meta_img' => $news_image_url,
                'prestablog_news_meta_url' => PrestaBlog::prestablogUrl(array(
                    'id' => $this->news->id,
                    'seo' => $this->news->link_rewrite,
                    'titre' => $this->news->title
                )),
                'prestablog_fb_admins' => $list_fb_moderators,
                'prestablog_fb_appid' => Configuration::get('prestablog_commentfb_apiId')
            ));


            return $this->display(__FILE__, self::getT().'_header-meta-og.tpl');
        }
    }



public function hookDisplayRating()
    {
        $html_out = '';
        if (Configuration::get($this->name.'_rating_actif')) {
            $html_out .= $this->showRating();
        }
        return $html_out;
    }


    public function hookDisplaySlider()
    {
        $html = '';
        $config = $this->getConfigFormValues();
        $layerslider = Module::getInstanceByName('layerslider');

        if ($layerslider && !empty($config['DISPLAYSLIDER_ID'])) {
            require_once _PS_MODULE_DIR_.'layerslider/helper.php';
            require_once _PS_MODULE_DIR_.'layerslider/base/layerslider.php';
            $html = $layerslider->generateSlider($config['DISPLAYSLIDER_ID']);
        }
        return $html;
    }

    public function hookDisplayHome()
    {
        $html_out = '';
        if (Configuration::get($this->name.'_homenews_actif')) {
            $html_out .= $this->showSlide();
        }

        if (Configuration::get($this->name.'_subblocks_actif')) {
            $html_out .= $this->showSubBlocks('displayHome');
        }

        return $html_out;
    }

    public function hookDisplayPrestaBlogList($params)
    {
        // {hook h='displayPrestaBlogList' id='1' mod='prestablog'}
        $html_out = '';
        $liste_subblocks = SubBlocksClass::getListe((int)$this->context->language->id, 1, 'displayCustomHook');

        if (count($liste_subblocks) > 0) {
            foreach ($liste_subblocks as $value) {
                if ((int)$value['id_prestablog_subblock'] == (int)$params['id']) {
                    $news_liste = self::returnUniversalNewsListSubBlocks($value, (int)$this->context->language->id);

                    if (count($news_liste) > 0) {
                        if ($value['random']) {
                            shuffle($news_liste);
                        }

                        $this->context->smarty->assign(array(
                            'subblocks' => $value,
                            'news' =>    $news_liste,
                        ));

                        $template = self::getT().'_page-subblock.tpl';
                        if ($value['template'] != '') {
                            $template = $value['template'];
                        }

                        $html_out .= $this->display(__FILE__, $template);
                    }
                }
            }
        }

        return $html_out;
    }

    public static function returnUniversalNewsListSubBlocks($value, $id_lang)
    {
        $date_fin = Date('Y-m-d H:i:s');
        $liste = array();
        switch ((int)$value['select_type']) {
            case 1:
                $liste = NewsClass::getListe(
                    $id_lang,
                    1,
                    0,
                    0,
                    (int)$value['nb_list'],
                    'n.`date`',
                    'desc',
                    ($value['use_date_start'] ? $value['date_start'] : null),
                    ($value['use_date_stop'] ? $value['date_stop'] : $date_fin),
                    $value['blog_categories'],
                    1,
                    (int)$value['title_length'],
                    (int)$value['intro_length']
                );
                break;

            case 2:
                $liste = NewsClass::getListe(
                    $id_lang,
                    1,
                    0,
                    0,
                    (int)$value['nb_list'],
                    'n.`date`',
                    'asc',
                    ($value['use_date_start'] ? $value['date_start'] : null),
                    ($value['use_date_stop'] ? $value['date_stop'] : $date_fin),
                    $value['blog_categories'],
                    1,
                    (int)$value['title_length'],
                    (int)$value['intro_length']
                );
                break;

            case 3:
                $liste = NewsClass::getListe(
                    $id_lang,
                    1,
                    0,
                    0,
                    (int)$value['nb_list'],
                    '`count_comments`',
                    'desc',
                    ($value['use_date_start'] ? $value['date_start'] : null),
                    ($value['use_date_stop'] ? $value['date_stop'] : $date_fin),
                    $value['blog_categories'],
                    1,
                    (int)$value['title_length'],
                    (int)$value['intro_length']
                );
                break;

            case 4:
                $liste = NewsClass::getListe(
                    $id_lang,
                    1,
                    0,
                    0,
                    (int)$value['nb_list'],
                    '`count_comments`',
                    'asc',
                    ($value['use_date_start'] ? $value['date_start'] : null),
                    ($value['use_date_stop'] ? $value['date_stop'] : $date_fin),
                    $value['blog_categories'],
                    1,
                    (int)$value['title_length'],
                    (int)$value['intro_length']
                );
                break;

            case 5:
                $liste = NewsClass::getListe(
                    $id_lang,
                    1,
                    0,
                    0,
                    (int)$value['nb_list'],
                    'nl.`read`',
                    'desc',
                    ($value['use_date_start'] ? $value['date_start'] : null),
                    ($value['use_date_stop'] ? $value['date_stop'] : $date_fin),
                    $value['blog_categories'],
                    1,
                    (int)$value['title_length'],
                    (int)$value['intro_length']
                );
                break;

            case 6:
                $liste = NewsClass::getListe(
                    $id_lang,
                    1,
                    0,
                    0,
                    (int)$value['nb_list'],
                    'nl.`read`',
                    'asc',
                    ($value['use_date_start'] ? $value['date_start'] : null),
                    ($value['use_date_stop'] ? $value['date_stop'] : $date_fin),
                    $value['blog_categories'],
                    1,
                    (int)$value['title_length'],
                    (int)$value['intro_length']
                );
                break;

            default:
                $liste = array();
                break;
        }
        return $liste;
    }

    public function showSubBlocks($hook_name)
    {

        $html_out = '';
        $liste_subblocks = SubBlocksClass::getListe((int)$this->context->language->id, 1, $hook_name);

        if (count($liste_subblocks) > 0) {
            if (Configuration::get('prestablog_commentfb_actif')) {
                $this->context->controller->registerJavascript(
                    'modules-prestablog-facebook-count',
                    'modules/prestablog/views/js/facebook-count.js',
                    array('position' => 'bottom', 'priority' => 200)
                );
            }

            foreach ($liste_subblocks as $value) {
                $news_liste = self::returnUniversalNewsListSubBlocks($value, (int)$this->context->language->id);

                if (count($news_liste) > 0) {
                    if ($value['random']) {
                        shuffle($news_liste);
                    }

                    $this->context->smarty->assign(array(
                        'subblocks' => $value,
                        'news' => $news_liste,
                    ));

                    $template = self::getT().'_page-subblock.tpl';
                    if ($value['template'] != '') {
                        $template = $value['template'];
                    }

                    $html_out .= $this->display(__FILE__, $template);
                }
            }
        }

        return $html_out;
    }

  public function showdisplaySlider()
    {
    	$layerslider = Module::getInstanceByName('layerslider');
        if ($layerslider) {
            return $this->display(__FILE__, self::getT().'_displayTop.tpl');
        }
    }

	public function showRating()
    {
            return $this->display(__FILE__, self::getT().'_displayRating.tpl');
    }

    public function showSlide()
    {
        if ($this->slideNews()) {
            return $this->display(__FILE__, self::getT().'_slide.tpl');
        }
    }

    public function slideNews()
    {

        $liste = array();

        $liste = NewsClass::getListe(
            (int)$this->context->language->id,
            1,
            1,
            0,
            (int)Configuration::get($this->name.'_homenews_limit'),
            'n.`date`',
            'desc',
            null,
            Date('Y/m/d H:i:s'),
            null,
            1,
            (int)Configuration::get('prestablog_slide_title_length'),
            (int)Configuration::get('prestablog_slide_intro_length')
        );

        if (count($liste) > 0) {
            $this->context->smarty->assign(array(
                'ListeBlogNews' => $liste
            ));
            $this->context->controller->registerJavascript(
                'modules-prestablog-slide-hook',
                'modules/prestablog/views/js/slide.js',
                array('position' => 'bottom', 'priority' => 201)
            );
            return true;
        } else {
            return false;
        }

    }

    public function blocRss()
    {
        if (Configuration::get('prestablog_allnews_rss')) {
            return $this->display(__FILE__, self::getT().'_bloc-rss.tpl');
        }
    }

    public function blocSearch()
    {
        if (Configuration::get('prestablog_blocsearch_actif')) {
            $this->context->smarty->assign(array(
                'prestablog_search_query'  => trim(Tools::getValue('prestablog_search')),
            ));
            return $this->display(__FILE__, self::getT().'_bloc-search.tpl');
        }
    }

    public function hookDisplayLeftColumn()
    {
        $result = null;

        $sbl = unserialize(Configuration::get($this->name.'_sbl'));
        if (count($sbl) > 0) {
            foreach ($sbl as $vs) {
                if ($vs != '') {
                    $result .= $this->$vs();
                }
            }
        }

        return $result;
    }

    public function hookDisplayRightColumn()
    {
        $result = null;

        $sbr = unserialize(Configuration::get($this->name.'_sbr'));
        if (count($sbr) > 0) {
            foreach ($sbr as $vs) {
                if ($vs != '') {
                    $result .= $this->$vs();
                }
            }
        }

        return $result;
    }

    public function hookDisplayFooterProduct()
    {
        $liste_news_linked = NewsClass::getNewsProductLinkListe((int)Tools::getValue('id_product'), true);
        if (Configuration::get($this->name.'_producttab_actif') && count($liste_news_linked) > 0) {
            $returnliste = array();
            foreach ($liste_news_linked as $vnews) {
                $lang = (int)$this->context->language->id;
                $news = new NewsClass((int)$vnews);
                $lang_liste_news = unserialize($news->langues);

                if (in_array($lang, $lang_liste_news)) {
                    $paragraph = $paragraph_crop = $news->paragraph[$lang];

                    if ((Tools::strlen(trim($paragraph)) == 0)
                        && (Tools::strlen(trim(strip_tags($news->content[$lang]))) >= 1)) {
                        $paragraph_crop = trim(strip_tags($news->content[$lang]));
                    }

                    $imgexist = file_exists($this->module_path.'/views/img/'.self::getT().'/up-img/'.$news->id.'.jpg');
                    $returnliste[(int)$vnews] = array(
                        'id' =>  $news->id,
                        'url' => PrestaBlog::prestablogUrl(array(
                            'id' => $news->id,
                            'seo' => $news->link_rewrite[$lang],
                            'titre' => $news->title[$lang]
                        )),
                        'title' =>  $news->title[$lang],
                        'paragraph_crop' => PrestaBlog::cleanCut(
                            $paragraph_crop,
                            (int)Configuration::get('prestablog_news_intro_length'),
                            ' [...]'
                        ),
                        'image_presente' => $imgexist,
                    );
                }
            }
            $this->context->smarty->assign(array(
                'listeNewsLinked' => $returnliste
            ));
            return $this->display(__FILE__, self::getT().'_product-footer.tpl');
        }
    }

    public function hookDisplayFooter()
    {
        if (Configuration::get($this->name.'_footlastnews_actif')) {
            $tri_champ = 'n.`date`';
            $tri_ordre = 'desc';
            $liste = NewsClass::getListe(
                (int)$this->context->language->id,
                1,
                0,
                0,
                (int)Configuration::get($this->name.'_footlastnews_limit'),
                $tri_champ,
                $tri_ordre,
                null,
                Date('Y/m/d H:i:s'),
                null,
                1,
                (int)Configuration::get('prestablog_footer_title_length'),
                (int)Configuration::get('prestablog_footer_intro_length')
            );

            $this->context->smarty->assign(array(
                'ListeBlocLastNews' => $liste
            ));
            return $this->display(__FILE__, self::getT().'_footer-lastliste.tpl');
        }
    }

    public function generateToken($add_text = null)
    {
        return md5($add_text.$this->module_key._COOKIE_KEY_);
    }

    public function isPreviewMode($id_prestablog_news)
    {
        if (Tools::getValue('preview') == $this->generateToken($id_prestablog_news)) {
            return true;
        }
        return false;
    }

    public function hookModuleRoutes()
    {

        $base_url_blog = 'blog';

        $module_routes = array(
            'prestablog-blog-root' => array(
                'controller' => null,
                'rule' => '{controller}',
                'keywords' => array(
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-news' => array(
                'controller' => null,
                'rule' => '{controller}/{urlnews}-n{n}',
                'keywords' => array(
                    'urlnews' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                    'n' => array('regexp' => '[0-9]+', 'param' => 'id'),
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-date' => array(
                'controller' => null,
                'rule' => '{controller}/y{y}-m{m}',
                'keywords' => array(
                    'y' => array('regexp' => '[0-9]{4}', 'param' => 'y'),
                    'm' => array('regexp' => '[0-9]+', 'param' => 'm'),
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-date-pagignation' => array(
                'controller' =>    null,
                'rule' => '{controller}/{start}p{p}y{y}-m{m}',
                'keywords' => array(
                    'y' => array('regexp' => '[0-9]{4}', 'param' => 'y'),
                    'm' => array('regexp' => '[0-9]+', 'param' => 'm'),
                    'start' => array('regexp' => '[0-9]+', 'param' => 'start'),
                    'p' => array('regexp' => '[0-9]+', 'param' => 'p'),
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller')
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-pagignation' => array(
                'controller' => null,
                'rule' => '{controller}/{start}p{p}',
                'keywords' => array(
                    'start' => array('regexp' => '[0-9]+', 'param' => 'start'),
                    'p' => array('regexp' => '[0-9]+', 'param' => 'p'),
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller')
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-catpagination' => array(
                'controller' =>    null,
                'rule' => '{controller}/{urlcat}-{start}p{p}-c{c}',
                'keywords' => array(
                    'c' => array('regexp' => '[0-9]+', 'param' => 'c'),
                    'urlcat' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                    'start' => array('regexp' => '[0-9]+', 'param' => 'start'),
                    'p' => array('regexp' => '[0-9]+', 'param' => 'p'),
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller')
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-cat' => array(
                'controller' => null,
                'rule' => '{controller}/{urlcat}-c{c}',
                'keywords' => array(
                    'c' => array('regexp' => '[0-9]+', 'param' => 'c'),
                    'urlcat' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                    'controller' => array('regexp' => $base_url_blog, 'param' => 'controller')
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-rss-root' => array(
                'controller' => null,
                'rule' => '{controller}',
                'keywords' => array(
                    'controller' => array('regexp' => 'rss', 'param' => 'controller'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            ),
            'prestablog-blog-rss' => array(
                'controller' => null,
                'rule' => '{controller}/{rss}',
                'keywords' => array(
                    'rss' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'rss'),
                    'controller' => array('regexp' => 'rss', 'param' => 'controller')
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestablog'
                )
            )
        );

        return $module_routes;
    }

    public static function getImgFlagByIso($iso)
    {
        if ((int)Language::getIdByIso(Tools::strtolower($iso)) > 0) {
            return '<img src="../img/l/'.(int)Language::getIdByIso(Tools::strtolower($iso)).'.jpg" />';
        } elseif (file_exists(dirname(__FILE__).'/views/img/l/'.Tools::strtolower($iso).'.png')) {
            return '<img src="'.self::imgPathFO().'l/'.Tools::strtolower($iso).'.png" />';
        } else {
            return '<img src="../img/l/none.jpg" />';
        }
    }

    public static function cleanCut($string, $length, $cut_string = '...')
    {
        $string = strip_tags($string);
        if (Tools::strlen($string) <= $length) {
            return $string;
        }
        $str = Tools::substr($string, 0, $length - Tools::strlen($cut_string) + 1);
        return Tools::substr($str, 0, strrpos($str, ' ')).$cut_string;
    }

    public static function arrayDeleteValue($array, $search)
    {
        $temp = array();
        foreach ($array as $key => $value) {
            if ($value != $search) {
                $temp[$key] = $value;
            }
        }
        return $temp;
    }

    private function dirSize($directory)
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function rrmdir($directory)
    {
        foreach (glob($directory.'/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            }
        }
    }

    private function rcopy($src, $dst)
    {
        $dir = opendir($src);
        self::makeDirectory($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src.'/'.$file)) {
                    $this->rcopy($src.'/'.$file, $dst.'/'.$file);
                } else {
                    self::copy($src.'/'.$file, $dst.'/'.$file);
                }
            }
        }
        closedir($dir);
    }

    public static function copy($source, $destination, $stream_context = null)
    {
        return Tools::copy($source, $destination, $stream_context);
    }

    public function genererMDP($longueur = 16)
    {
        $mdp = '';
        $possible = 'abcdfghijklmnopqrstuvwxyz012346789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longueur_max = Tools::strlen($possible);
        if ($longueur > $longueur_max) {
            $longueur = $longueur_max;
        }
        $i = 0;
        while ($i < $longueur) {
            $mdp .= Tools::substr($possible, mt_rand(0, $longueur_max - 1), 1);
            $i++;
        }
        return $mdp;
    }

    public static function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }

        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        return array_map(array('PrestaBlog', 'objectToArray'), $object);
    }

    public static function createSqlFilterSearch($from_fields = array(), $search = '', $nb_car_per_item = 3)
    {
        if ($search != '') {
            $filtre_sujet2 = '';
            $filtre_cumul = '%';

            $filtre_search = 'AND ('."\n";

            foreach (preg_split('/ /', $search) as $value_keywords) {
                if (Tools::strlen($value_keywords) >= (int)$nb_car_per_item) {
                    $filtre_cumul .= $value_keywords.'%';
                    foreach ($from_fields as $field) {
                        $filtre_sujet2 .= 'OR '.$field.' LIKE \'%'.pSQL($value_keywords).'%\''."\n";
                    }
                }
            }
            if (($filtre_cumul != '%') && (strpos($filtre_sujet2, $filtre_cumul) === false)) {
                foreach ($from_fields as $field) {
                    $filtre_sujet2 .= 'OR '.$field.' LIKE \''.pSQL($filtre_cumul).'\''."\n";
                }
            }
            $filtre_sujet2 = trim(ltrim($filtre_sujet2, 'OR'));

            if ($filtre_sujet2) {
                $filtre_search .= $filtre_sujet2.')'."\n";
            }

            $filtre_search = trim(rtrim($filtre_search, 'AND ('."\n"));
            //$filtre_search = trim(ltrim($filtre_search, 'AND'));
            $filtre_search = trim(ltrim($filtre_search, 'OR'));

            return $filtre_search;
        }
        return '';
    }

    public static function getNavigationPipe()
    {
        $pipe = Configuration::get('PS_NAVIGATION_PIPE');
        if (empty($pipe)) {
            $pipe = '>';
        }

        $navigation_pipe = '<span class="navigation-pipe">'.$pipe.'</span>';

        return $navigation_pipe;
    }

    public static function getContextShopDomain($http = false, $entities = false)
    {
        if (!$domain = ShopUrl::getMainShopDomain((int)Context::getContext()->shop->id)) {
            $domain = Tools::getHttpHost();
        }
        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            $domain = 'http://'.$domain;
        }

        return $domain;
    }

    public static function getContextShopDomainSsl($http = false, $entities = false)
    {
        if (!$domain = ShopUrl::getMainShopDomainSSL((int)Context::getContext()->shop->id)) {
            $domain = Tools::getHttpHost();
        }
        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            $domain = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$domain;
        }

        return $domain;
    }

    public static function getFiltreGroupes(
        $parent_field_join = 'c.`id_prestablog_categorie`',
        $parent_class = 'categorie'
    ) {
        $context = Context::getContext();

        /* uniquement sur front office */
        if (isset($context->employee->id) && (int)$context->employee->id > 0) {
            return '';
        } else {
            $groups = FrontController::getCurrentCustomerGroups();
            return '
                AND EXISTS (
                    SELECT 1
                    FROM `'.bqSQL(_DB_PREFIX_).'prestablog_'.pSQL($parent_class).'` pc
                    JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_'.pSQL($parent_class).'_group` pcg
                        ON (
                            pc.id_prestablog_'.pSQL($parent_class).' = pcg.id_prestablog_'.pSQL($parent_class).'
                            AND pcg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
                        )
                    WHERE '.pSQL($parent_field_join).' = pc.`id_prestablog_categorie`
                )';
        }
    }
}

/*
    echo '<pre style="font-size:11px;text-align:left">';
        print_r();
    echo '</pre>';
*/
