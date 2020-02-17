<?php
/**
 *   AmbJoliSearch Module : Search for prestashop
 *
 *   @author    Ambris Informatique
 *   @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *   @license   Commercial license
 *   @module     Advanced Search (AmbJoliSearch)
 *   @file       jolisearch.php
 *   @subject    main controller
 *   Support by mail: support@ambris.com
 */

require_once _PS_ROOT_DIR_ . '/modules/ambjolisearch/classes/definitions.php';
require_once _PS_ROOT_DIR_ . '/modules/ambjolisearch/classes/AmbSearch.php';

use PrestaShop\PrestaShop\Adapter\Search\SearchProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class AmbjolisearchjolisearchModuleFrontController extends ProductListingFrontControllerCore
{
    public $no_image_path;
    public $priorities;
    public $max_items;
    public $allow;

    public $php_self = 'module-ambjolisearch-jolisearch';

    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setSortOrder(new SortOrder('product', Tools::getProductsOrder('by', 'position'), Tools::getProductsOrder('way', 'desc')))
            ->setSearchString($this->search_string)
            ->setSearchTag($this->search_tag)
        ;

        return $query;
    }

    protected function getDefaultProductSearchProvider()
    {
        return new SearchProductSearchProvider(
            $this->getTranslator()
        );
    }

    public function getListingLabel()
    {
        return $this->getTranslator()->trans('Search results', array(), 'Shop.Theme.Catalog');
    }

    public function init()
    {
        parent::init();

        $this->search_string = Tools::getValue('s');
        if (!$this->search_string) {
            $this->search_string = Tools::getValue('search_query');
        }
        if (!$this->search_string) {
            $this->search_string = Tools::getValue('q');
        }
        $this->search_tag = Tools::getValue('tag');

        if (Tools::getValue('ajax', false) === false) {
            $this->context->smarty->assign(array(
                'search_string' => $this->search_string,
                'search_tag' => $this->search_tag,
            ));

            $this->doProductSearch('../../../modules/ambjolisearch/views/templates/front/search-1.7.tpl', array('entity' => 'jolisearch'));
        }
    }

    public function run()
    {
        if (Tools::getValue('ajax', false) == true) {
            // to respond using the same protocol as the caller page
            $this->ssl = Tools::usingSecureMode();
            $this->init();
            if ($this->checkAccess()) {
                $this->displayAjax();
            }
        } else {
            parent::run();
        }
    }

    public function displayAjax()
    {
        $iso_code = $this->context->language->iso_code;

        $this->module = Module::getInstanceByName('ambjolisearch');
        $this->searcher = new AmbSearch(true, $this->context, $this->module);

        $this->no_image_path = array();
        if (Tools::file_exists_cache(_PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/img/no-image.png')) {
            $img_path = _PS_THEME_URI_
            . 'modules/' . $this->module->name . '/views/img/no-image.png';
            $this->no_image_path['p'] = $img_path;
            $this->no_image_path['m'] = $img_path;
            $this->no_image_path['c'] = $img_path;
        } elseif (Tools::file_exists_cache(_PS_MODULE_DIR_ . $this->module->name . '/views/img/no-image.png')) {
            $img_path = _MODULE_DIR_
            . $this->module->name . '/views/img/no-image.png';
            $this->no_image_path['p'] = $img_path;
            $this->no_image_path['m'] = $img_path;
            $this->no_image_path['c'] = $img_path;
        } else {
            $small = 'small';
            $default = 'default';
            $small_default = $small . '_' . $default . '.jpg';
            $this->no_image_path['p'] = _PS_IMG_ . "p/$iso_code-default-" . $small_default;
            $this->no_image_path['m'] = _PS_IMG_ . "m/$iso_code-default-" . $small_default;
            $this->no_image_path['c'] = _PS_IMG_ . "c/$iso_code-default-" . $small_default;
        }

        $this->allow = (int) Configuration::get('PS_REWRITING_SETTINGS');

        $this->max_items = array();
        $this->max_items['all'] = (int) Configuration::get(AJS_MAX_ITEMS_KEY);
        $this->max_items['manufacturers'] = (int) Configuration::get(AJS_MAX_MANUFACTURERS_KEY);
        $this->max_items['categories'] = (int) Configuration::get(AJS_MAX_CATEGORIES_KEY);

        $this->priorities = array();
        $this->priorities['products'] = (int) Configuration::get(AJS_PRODUCTS_PRIORITY_KEY);
        $this->priorities['manufacturers'] = (int) Configuration::get(AJS_MANUFACTURERS_PRIORITY_KEY);
        $this->priorities['categories'] = (int) Configuration::get(AJS_CATEGORIES_PRIORITY_KEY);
        asort($this->priorities);

        $real_query = urldecode($this->search_string);
        $query = Tools::replaceAccentedChars(urldecode($this->search_string));
        $id_lang = Tools::getValue('id_lang', $this->context->language->id);

        $this->searcher->search(
            $id_lang,
            $query,
            1,
            $this->max_items['all'],
            'position',
            'desc'
        );

        $search_results = $this->searcher->getResults(true);
        $total = $this->searcher->getTotal();
        $sr_categories = $this->searcher->getCategories();

        //$search_results = Product::getProductsProperties((int)$id_lang, $search_results);

        if ($total == 0) {
            die(Tools::jsonEncode(array(
                array(
                    'type' => 'no-results-found',
                ))));
        }

        $manufacturers = array();
        $categories = array();

        $price_display = Product::getTaxCalculationMethod();
        $show_price = (bool) Configuration::get(AJS_SHOW_PRICES)
            && (!(bool) Configuration::get('PS_CATALOG_MODE') && (bool) Group::getCurrent()->show_prices);
        $small = 'small';
        $default = 'default';

        foreach ($search_results as &$product) {
            $link = $this->context->link->getProductLink(
                $product['id_product'],
                $product['prewrite'],
                $product['crewrite']
            );
            $product['link'] = $link . '?search_query=' . $query . '&fast_search=fs';

            if (isset($product['imgid']) || $product['imgid'] != null) {
                $product['img'] = $this->context->link->getImageLink(
                    $product['prewrite'],
                    $product['imgid'],
                    $small . '_' . $default
                );
            } else {
                $product['img'] = $this->no_image_path['p'];
            }

            $product['type'] = 'product';

            $feats = array();

            if (pSQL(Configuration::get(AJS_SHOW_FEATURES, 0))) {
                foreach ($product['features'] as $feature) {
                    $feats[] = $feature['name'] . ': ' . $feature['value'];
                }
            }

            $product['feats'] = implode(', ', $feats);

            if ($show_price && isset($product['show_price']) && $product['show_price']) {
                if (!$price_display) {
                    $product['price'] = Tools::displayPrice(
                        $product['price'],
                        (int) $this->context->cookie->id_currency
                    );
                } else {
                    $product['price'] = Tools::displayPrice(
                        $product['price_tax_exc'],
                        (int) $this->context->cookie->id_currency
                    );
                }
            } else {
                $product['price'] = '';
            }

            if (isset($product['mname'])) {
                $manufacturers[$product['manid']] = $product['mname'];
            }

            if (!empty($sr_categories)) {
                foreach ($sr_categories as $category) {
                    $categories[$category['id_category']] = $category['name'];
                }
            }
        }

        $search_manufacturers = array();
        foreach ($manufacturers as $manid => $mname) {
            $manu = new Manufacturer();
            $manu->id = $manid;
            $search_manufacturers[] = array('type' => 'manufacturer',
                'man_id' => $manid,
                'man_name' => $mname,
                'img' => $this->getManufacturerImage($manu),
                'link' => $this->context->link->getManufacturerLink($manu, Tools::link_rewrite($mname))
                . '?search_query=' . $real_query . '&fast_search=fs');
            //'link' => $this->context->link->getManufacturerLink($manu->id));
        }

        $search_categories = array();
        foreach ($categories as $catid => $cname) {
            $cat = new Category($catid, $id_lang);

            $search_categories[] = array('type' => 'category',
                'cat_id' => $catid,
                'cat_name' => $cname,
                'img' => $this->getCategoryImage($cat, $id_lang),
                'link' => $this->context->link->getCategoryLink($cat, $cat->link_rewrite, $id_lang)
                . '?search_query=' . $real_query . '&fast_search=fs',
            );
        }

        $search = array(
            'products' => array(),
            'manufacturers' => array(),
            'suppliers' => array(),
            'categories' => array(),
        );
        if (count($search_manufacturers) > 0) {
            $search['manufacturers'] = array_slice($search_manufacturers, 0, $this->max_items['manufacturers']);
        }

        if (count($search_categories) > 0) {
            $search['categories'] = array_slice($search_categories, 0, $this->max_items['categories']);
        }

        if (count($search_results) + count($search['manufacturers'])
             + count($search['categories']) > $this->max_items['all']) {
            $search['products'] = array_slice(
                $search_results,
                0,
                $this->max_items['all'] - count($search['manufacturers']) - count($search['categories'])
            );
        } else {
            $search['products'] = $search_results;
        }

        if (Configuration::get(AJS_MORE_RESULTS_CONFIG)) {
            $params = array('s' => $real_query,
                'orderby' => 'position',
                'orderway' => 'desc',
                'p' => 1,
            );

            $joli_link = new JoliLink($this->context->link);
            $action = $joli_link->getModuleLink('ambjolisearch', 'jolisearch', $params);

            $this->priorities['more-results'] = 999;
            $search['more-results'] = array(array(
                'type' => 'more-results',
                'link' => $action,
            ));
        }

        $search_results = array();
        foreach (array_keys($this->priorities) as $key) {
            $search_results = array_merge($search_results, $search[$key]);
        }

        die(Tools::jsonEncode($search_results));
    }


    private function getManufacturerImage($manufacturer)
    {
        $small = 'small';
        $default = 'default';
        $uri_path = '';
        if (Tools::file_exists_cache(
            _PS_IMG_DIR_ . 'm/' . $manufacturer->id . '-' . $small . '_' . $default . '.jpg'
        )) {
            return $this->context->link->protocol_content
            . Tools::getMediaServer($uri_path) . _PS_IMG_ . 'm/'
            . $manufacturer->id . '-' . $small . '_' . $default . '.jpg';
        } else {
            return $this->no_image_path['m'];
        }
    }

    private function getCategoryImage($category, $id_lang)
    {
        $small = 'small';
        $default = 'default';
        $id_image = file_exists(_PS_CAT_IMG_DIR_ . $category->id . '.jpg') ?
        (int) $category->id : Language::getIsoById($id_lang) . '-default';
        return $this->context->link->getCatImageLink($category->link_rewrite, $id_image, $small . '_' . $default);
    }

    public function setMedia()
    {
        parent::setMedia();

        if (Configuration::get('PS_COMPARATOR_MAX_ITEM')) {
            $this->addJS(_THEME_JS_DIR_ . 'products-comparison.js');
        }
    }
}
