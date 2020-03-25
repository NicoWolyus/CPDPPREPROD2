<?php
/**
 * Modulo Product Combinations
 *
 * @author    Giuseppe Tripiciano <admin@areaunix.org>
 * @copyright Copyright (c) 2018 Giuseppe Tripiciano
 * @license   You cannot redistribute or resell this code.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/classes/productcombinations.php');

class ProductAsCombinations extends Module
{
    public function __construct()
    {
        $this->name = 'productascombinations';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Giuseppe Tripiciano';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '8d32f7f293d8c735126d56ce3883dcc0';
        parent::__construct();

        $this->displayName = $this->l('Product as Combinations');
        $this->description = $this->l('Use products as combinations');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $sql = array();

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'productcombinations` (
                  `id_product` INT(11) UNSIGNED NOT NULL,
                  `image` BOOLEAN NOT NULL,
                  `combinations` TEXT NOT NULL,
                  PRIMARY KEY (`id_product`),
                  UNIQUE  `PROD_COMB_UNIQ` (  `id_product` )
                ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        if (!parent::install() ||
            !$this->registerHook('displayAdminProductsExtra') ||
            !$this->registerHook('actionProductUpdate') ||
            !$this->registerHook('actionAdminControllerSetMedia') ||
            !$this->registerHook('actionFrontControllerSetMedia') ||
            !$this->registerHook('displayProductAdditionalInfo') ||
            !$this->registerHook('displayProductCombinations') ||
            !$this->runSql($sql)
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        $sql = array();

        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'productcombinations`';

        if (!parent::uninstall() ||
            !$this->runSql($sql)
        ) {
            return false;
        }

        return true;
    }

    public function runSql($sql)
    {
        foreach ($sql as $s) {
            if (!Db::getInstance()->Execute($s)) {
                return false;
            }
        }

        return true;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        if ($this->context->controller->php_self == "AdminProducts") {
            $this->context->controller->addJS($this->_path.'/views/js/admin/select2.full.min.js');
            $this->context->controller->addCSS($this->_path.'/views/css/admin/select2.min.css');
        }
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller->php_self == "product") {
            $this->context->controller->addCSS($this->_path.'/views/css/front/combinations.css');
            $this->context->controller->addJS($this->_path.'/views/js/front/productcombinations.js');
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)$params['id_product'];
        $pc = ProductComb::loadByIdProduct($id_product);
        $products = Product::getProducts(Configuration::get('PS_LANG_DEFAULT'), 0, 0, 'id_product', 'asc');
        if ($pc->id_product !== null) {
            $this->context->smarty->assign(
                array(
                'pc_combs' => $pc->combinations,
                'pc_image' => $pc->image,
                )
            );
        }
        $this->context->smarty->assign(
            array(
                'products' => $products
            )
        );

        return $this->display(__FILE__, 'views/templates/admin/tab.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        if (Tools::getValue('pc_combs')) {
            $combs = Tools::getValue('pc_combs');
            array_unshift($combs, $id_product);
            $combs = array_unique($combs);
            foreach ($combs as $comb) {
                $pc = ProductComb::loadByIdProduct($comb);
                $pc->combinations = implode(',', $combs);
                $pc->image = Tools::getValue('pc_image');
                if ($pc->id_product !== null) {
                    $pc->update();
                } else {
                    $pc->id_product = (int)$comb;
                    $pc->add();
                }
            }
        } else {
            $pc = ProductComb::loadByIdProduct($id_product);
            if ($pc->id_product !== null) {
                $pc->delete();
            }
            $sets = Db::getInstance()->executeS('
            SELECT *
            FROM `'._DB_PREFIX_.'productcombinations`
            WHERE '.(int)$id_product.' IN (combinations)');
            foreach ($sets as $set) {
                $pcr = ProductComb::loadByIdProduct($set['id_product']);
                $combs = explode(',', $pcr->combinations);
                if (($key = array_search($id_product, $combs)) !== false) {
                    unset($combs[$key]);
                }
                $pcr->combinations = implode(',', $combs);
                $pcr->update();
            }
        }
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        if (Tools::getValue('action') == 'quickview') {
            return false;
        }
        $id_product = Tools::getValue('id_product');
        $pc = ProductComb::loadByIdProduct((int)$id_product);
        if ($pc->id_product !== null) {
            $products = array();

            $ids = explode(',', $pc->combinations);
            foreach ($ids as $id) {
                $p = new Product((int)$id, false, Configuration::get('PS_LANG_DEFAULT'));
                if ($pc->image) {
                    $images = $p->getImages(Configuration::get('PS_LANG_DEFAULT'));
                    $num = count($images);
                    $link = new Link();
                    $p->combimage = Tools::getShopProtocol().$link->getImageLink(
                        $p->link_rewrite,
                        $images[$num-1]['id_image'],
                        ImageType::getFormattedName('cart')
                    );
                } else {
                    $link = new Link();
                    $image = Product::getCover($p->id);
                    $p->combimage = Tools::getShopProtocol().$link->getImageLink(
                        $p->link_rewrite,
                        $image['id_image'],
                        ImageType::getFormattedName('cart')
                    );
                }
                if (!isset($p->combimage)) {
                    $p->combimage = null;
                }
                $products[] = $p;
            }
            $this->context->smarty->assign(
                array(
                    'pc_combs' => $products,
                    'pc_image' => $pc->image,
                )
            );

            return $this->display(__FILE__, 'views/templates/front/combinations.tpl');
        }
    }

    public function hookDisplayProductCombinations($params)
    {
        return $this->hookDisplayProductAdditionalInfo($params);
    }

    public function hookDisplayRightColumnProduct($params)
    {
        return $this->hookDisplayProductAdditionalInfo($params);
    }

    public function hookDisplayLeftColumnProduct($params)
    {
        return $this->hookDisplayProductAdditionalInfo($params);
    }
}
