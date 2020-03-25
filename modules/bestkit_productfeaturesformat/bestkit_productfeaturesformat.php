<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bestkit_productfeaturesformat/includer.php';

class bestkit_productfeaturesformat extends Module
{
    const PREFIX = 'bestkit_pfeatures_';
    const PAGINATION_LIMIT = 100;

    protected $_hooks = array(
        'header',
        'footer',
        //'displayFooterProduct',
        'displayProductAdditionalInfo',
    );

    protected $_moduleParams = array();

    protected $_tabs = array();

    protected $search = '';
    protected $page = 1;
    protected $pagination = array();
    protected $color_features = array();
    protected $color_feature_values = array();

    public function __construct()
    {
        $this->name = 'bestkit_productfeaturesformat';
        $this->tab = 'front_office_features';
        $this->version = '1.7.1';
        $this->author = 'best-kit';
        $this->need_instance = 0;
        $this->module_key = '83c2ee1fe0d2c79d3c24dd7eca769d26';
        $this->bootstrap = TRUE;

        parent::__construct();
//$this->registerHook('displayProductAdditionalInfo');

        $this->displayName = $this->l('Product Featuresformat');
        $this->description = $this->l('Display links to related products using features. Working with Color and Size attributes as features');
    }

    public function install()
    {
        if (parent::install()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return FALSE;
                }
            }

            foreach ($this->_moduleParams as $param => $value) {
                if (!Configuration::updateValue(self::PREFIX . $param, $value)) {
                    return FALSE;
                }
            }

            $sql = array();

            $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestkit_pfeaturesformat` (
                          `id_feature` int(10) unsigned NOT NULL,
                          `is_color` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `active` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
                          `label` text,
                          `date_add` datetime NOT NULL,
                          PRIMARY KEY  (`id_feature`)
                        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

            $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestkit_pfeatures_colorformat` (
                          `id_feature_value` int(10) unsigned NOT NULL,
                          `hex_value` varchar(15) NOT NULL,
                          `date_add` datetime NOT NULL,
                          PRIMARY KEY  (`id_feature_value`)
                        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

            foreach ($sql as $_sql) {
                Db::getInstance()->Execute($_sql);
            }

            $languages = Language::getLanguages();
            foreach ($this->_tabs as $tab) {
                $_tab = new Tab();
                $_tab->class_name = $tab['class_name'];
                $_tab->id_parent = Tab::getIdFromClassName($tab['parent']);
                if (empty($_tab->id_parent)) {
                    $_tab->id_parent = 0;
                }

                $_tab->module = $this->name;
                foreach ($languages as $language) {
                    $_tab->name[$language['id_lang']] = $this->l($tab['name']);
                }

                $_tab->add();
            }

            return TRUE;
        }

        return FALSE;
    }

    public function uninstall()
    {
        $sql = array();
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bestkit_pfeaturesformat`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bestkit_pfeatures_colorformat`';
        foreach ($sql as $_sql) {
            Db::getInstance()->Execute($_sql);
        }

        return parent::uninstall();
    }

    private function printConfigForm() {
        $this->context->smarty->assign(array(
            'bestkit_pfeatures_submit' => $this->context->link->getAdminLink('AdminModules') . '&configure=bestkit_productfeaturesformat',
            'features' => Feature::getFeatures($this->context->language->id),
        ));

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/bestkit_productfeaturesformat/views/templates/admin/configForm.tpl');
    }

    public function getContent() {
        $this->postProcess();

        if (isset($this->context->cookie->pfeatures_error_message) && !empty($this->context->cookie->pfeatures_error_message)) {
            $this->context->smarty->assign('error_message', $this->context->cookie->pfeatures_error_message);
            unset($this->context->cookie->pfeatures_error_message);
        }

        return $this->printConfigForm();
    }

    public function hookDisplayHeader() {
        if ($this->context->controller instanceof ProductController) {
            $this->context->controller->addCSS(($this->_path).'views/css/front.css', 'all');
        }
    }

    public function getPFeatures()
    {
        return Db::getInstance()->ExecuteS('
                    SELECT pf.*, fl.`name`
                    FROM `'._DB_PREFIX_.'bestkit_pfeaturesformat` pf
                    JOIN `'._DB_PREFIX_.'feature_lang` fl ON (pf.`id_feature` = fl.`id_feature` AND fl.`id_lang` = '.(int)$this->context->language->id.')
                    ORDER BY pf.`date_add` ASC
                ');
    }

    public function getPFeatureColors()
    {
        return Db::getInstance()->ExecuteS('
                    SELECT pfc.*, fvl.`value`
                    FROM `'._DB_PREFIX_.'bestkit_pfeatures_colorformat` pfc
                    JOIN `'._DB_PREFIX_.'feature_value_lang` fvl ON (pfc.`id_feature_value` = fvl.`id_feature_value` AND fvl.`id_lang` = '.(int)$this->context->language->id.')
                ');
    }

    public function getFeatureValuesWithLang($id_lang, $id_feature, $custom = false)
    {
        return Db::getInstance()->ExecuteS('
            SELECT v.*, vl.*, fl.name as feature_name, pfc.`hex_value`
            FROM `'._DB_PREFIX_.'feature_value` v
            LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` vl ON (v.`id_feature_value` = vl.`id_feature_value` AND vl.`id_lang` = '.(int)$id_lang.')
            LEFT JOIN `'._DB_PREFIX_.'feature_lang` fl ON (v.`id_feature` = fl.`id_feature` AND vl.`id_lang` = '.(int)$id_lang.')
            LEFT JOIN `'._DB_PREFIX_.'bestkit_pfeatures_colorformat` pfc ON (pfc.`id_feature_value` = v.`id_feature_value`)
            WHERE v.`id_feature` = '.(int)$id_feature.' AND vl.value IS NOT NULL '.(!$custom ? 'AND (v.`custom` IS NULL OR v.`custom` = 0)' : '').'
            GROUP BY v.`id_feature_value`
            ORDER BY vl.`value` ASC
        ');
    }

    protected function setMiscVariables() {
        $this->context->smarty->assign(array(
            'bestkit_pfeatures_admin_tpl' => dirname(__file__) . '/views/templates/admin/',
            'bestkit_productfeatures_search' => $this->search,
            'bestkit_pfeatures_pagination' => array(
                'list_id' => 'bestkit_pfeatures',
                'page' => $this->page,
                'total_pages' => ceil(count($this->color_feature_values) / self::PAGINATION_LIMIT),
                //'total_pages' => ceil(Db::getInstance()->getValue('select count(*) from `' . _DB_PREFIX_ . 'bestkit_pfeatures_color`') / self::PAGINATION_LIMIT),
            ),
        ));
    }

    protected function setColorFeatureValues() {
        $pfeatures = $this->getPFeatures();
        foreach ($pfeatures as $pfeature) {
            if ($pfeature['is_color']) {
                $this->color_features[] = $pfeature['id_feature'];
            }
        }
        unset($pfeature);
        foreach ($this->color_features as $color_feature) {
            $tmp_color_feature_values = $this->getFeatureValuesWithLang($this->context->language->id, $color_feature, true);

            if (!empty($this->search)) {
                foreach ($tmp_color_feature_values as $tmp_color_feature_value) {
                    if (
                        /*strpos($tmp_color_feature_value['feature_name'], $this->search) !== false ||
                        strpos($tmp_color_feature_value['hex_value'], $this->search) !== false ||
                        strpos($tmp_color_feature_value['id_feature_value'], $this->search) !== false ||*/
                        stripos($tmp_color_feature_value['value'], $this->search) !== false
                    ) {
                        $this->color_feature_values[] = $tmp_color_feature_value;
                    }
                }
                unset($tmp_color_feature_value);
            } else {
                $this->color_feature_values = array_merge($this->color_feature_values, $tmp_color_feature_values);
            }
        }
        unset($color_feature);
/*print_r($this->search);
print_r($this->color_feature_values);
print_r($pfeatures); die;*/
        $this->context->smarty->assign(array(
            'pfeatures' => $pfeatures,
        ));
    }

    protected function setPagination() {
        /*pagination*/
        $i = 0;
        $page = 1;
        foreach ($this->color_feature_values as $color_feature_value) {
            $this->pagination[$page][] = $color_feature_value;

            $i++;
            if (($i / $page) > self::PAGINATION_LIMIT) {
                $page++;
            }
        }
		
        $this->context->smarty->assign(array(
            'color_feature_values' => isset($this->pagination[$this->page]) ? $this->pagination[$this->page] : array(),
            'pagination_pages' => count($this->pagination),
        ));
    }

    protected function postProcess() {
        if (Tools::isSubmit('submitUpdate') || Tools::isSubmit('submitUpdateAndStay')) {
            if (Tools::isSubmit('main_feature')) {
                Configuration::updateValue(self::PREFIX . 'formatmain_feature', Tools::getValue('formatmain_feature'));
            }

            if (Tools::isSubmit('new_feature')) {
                $new_feature = Tools::getValue('formatnew_feature');
                if ($new_feature['id_feature']) {
                    try {
                        Db::getInstance()->insert(
                            'bestkit_pfeaturesformat',
                            array(
                                'id_feature' => (int)$new_feature['id_feature'],
                                'is_color' => isset($new_feature['is_color']) ? (int)$new_feature['is_color'] : 0,
                                'active' => 1,
                                //'label' => pSql($new_feature['label']),
                                'date_add' => pSQL(date('Y-m-d H:i:s')),
                            )
                        );
                    } catch (Exception $e) {
                        $this->context->cookie->pfeatures_error_message = $e->getMessage();
                    }
                }
            }

            if (Tools::isSubmit('color')) {
                $feature_colors = Tools::getValue('color');
                foreach ($feature_colors as $id_feature_value => $hex_value) {
                    Db::getInstance()->insert(
                        'bestkit_pfeatures_colorformat',
                        array(
                            'id_feature_value' => (int)$id_feature_value,
                            'hex_value' => pSQL($hex_value),
                            'date_add' => pSQL(date('Y-m-d H:i:s')),
                        ),
                        FALSE,
                        TRUE,
                        Db::REPLACE
                    );
                }
            }
        }

        /*if (Tools::isSubmit('submitUpdate')) {
            Tools::redirect($this->context->link->getAdminLink('AdminModules'));
            exit;
        }*/

        if (Tools::isSubmit('delete_pfeature')) {
            $delete_pfeature = Tools::getValue('delete_pfeature');
            Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'bestkit_pfeaturesformat` WHERE id_feature = ' . (int)$delete_pfeature);
        }

        /*setup variables*/
        $this->setColorFeatureValues();
        /*pagination*/
        $this->setPagination();
        /*setup smarty variables*/
        $this->setMiscVariables();

        $this->context->smarty->assign(array(
            'formatmain_feature' => Configuration::get(self::PREFIX . 'formatmain_feature'),
        ));
    }

    public function getPFeaturesByProduct($id_product)
    {
        if (_PS_CACHE_ENABLED_) {
            $key = __METHOD__.'--'.sha1(serialize(array($id_product)));
            $result = Cache::getInstance()->get($key);
            if ($result) {
                return $result;
            }
        }

        $pfeatures = Db::getInstance()->ExecuteS('
            select fp.id_feature_value, fp.id_feature
            from `'._DB_PREFIX_.'feature_product` fp
            join `'._DB_PREFIX_.'bestkit_pfeaturesformat` pf ON (fp.`id_feature` = pf.`id_feature`)
            where id_product = '.(int)$id_product.'
            ORDER BY pf.`date_add` ASC
        ');

        $split_by_features = array();
        foreach ($pfeatures as $pfeature) {
            $split_by_features[$pfeature['id_feature']] = $pfeature['id_feature_value'];
        }

        if (_PS_CACHE_ENABLED_) {
            Cache::getInstance()->set($key, $split_by_features, 7200);
        }

        return $split_by_features;
    }

    public function getProducts($id_product)
    {
        if (_PS_CACHE_ENABLED_) {
            $key = __METHOD__.'--'.sha1(serialize(array($id_product, $this->context->language->id)));
            $result = Cache::getInstance()->get($key);
            if ($result) {
                return $result;
            }
        }

        $products = Db::getInstance()->ExecuteS('
            select pf.*, fp.*, fvl.value, pfc.hex_value, fl.name as `label`
            from `'._DB_PREFIX_.'bestkit_pfeaturesformat` pf
            join `'._DB_PREFIX_.'feature_product` fp ON (pf.`id_feature` = fp.`id_feature`)
            join `'._DB_PREFIX_.'product` p ON (p.`id_product` = fp.`id_product` AND p.`active` = 1)
            join `'._DB_PREFIX_.'feature_value_lang` fvl ON (fvl.`id_feature_value` = fp.`id_feature_value` AND fvl.`id_lang` = '.(int)$this->context->language->id.')
            left join `'._DB_PREFIX_.'bestkit_pfeatures_colorformat` pfc ON (pfc.`id_feature_value` = fp.`id_feature_value`)
			join `'._DB_PREFIX_.'feature_lang` fl ON (fl.id_feature = pf.`id_feature` AND fl.`id_lang` = '.(int)$this->context->language->id.')
            join (
                select id_product
                from `'._DB_PREFIX_.'feature_product` fp
                where fp.`id_feature_value` = (
                    select id_feature_value
                    from `'._DB_PREFIX_.'feature_product`
                    where id_feature = '.(int)Configuration::get(self::PREFIX . 'formatmain_feature').'
                    AND id_product = '.(int)$id_product.'
                )
            ) as similar_products ON (similar_products.`id_product` = fp.`id_product`)
            where fp.`id_product` != '.(int)$id_product.'
        ');

        $product_fv = $this->getPFeaturesByProduct($id_product);

        $result = array(
            'split_by_products' => array(),
            'split_by_features' => array(),
        );
        foreach (array_keys($product_fv) as $p_fv_id)
            $result['split_by_features'][$p_fv_id] = array(); //for keep sorting
        unset($p_fv_id);
        foreach ($products as &$product) {
            foreach (array_keys($product_fv) as $p_fv_id) {
                if ($p_fv_id == $product['id_feature']) {
                    $result['split_by_products'][$product['id_product']][$p_fv_id] = array(
                        'label' => $product['label'],
                        'id_feature_value' => $product['id_feature_value'],
                        'value' => $product['value'],
                        'is_color' => $product['is_color'],
                        'hex_value' => $product['hex_value'],
                        'link' => $this->context->link->getProductLink($product['id_product']),
                        'pf_date_add' => $product['date_add'], //needs for sorting
                    );
                }
            }
            unset($p_fv_id);
        }
        unset($product);

        //fill cycle
        foreach ($result['split_by_products'] as $id_product => $product_features) {
            if (count($product_fv) > 1) { //loc for remove duplicated links
                $flag_skip = array();
                foreach ($product_fv as $id_feature => $id_feature_value) {
/*print_r($product_fv);
print_r($product_features);
print_r($product_features[$id_feature]['id_feature_value'] .'=='. $id_feature_value . chr(10));
print_r('--------------------' . chr(10));*/
                    if (isset($product_features[$id_feature]['id_feature_value']) && $product_features[$id_feature]['id_feature_value'] == $id_feature_value) {
                        $flag_skip[] = array(
                            'id_feature' => $id_feature,
                        );
                    }
                }
                unset($id_feature);
                unset($id_feature_value);

                if (count($flag_skip)) {
                    foreach ($product_fv as $id_feature => $id_feature_value) {
                        foreach ($flag_skip as $flag) {
                            if ($id_feature != $flag['id_feature'] && isset($product_features[$id_feature])) {
                                $result['split_by_features'][$id_feature][] = array_merge(array('id_product' => $id_product), $product_features[$id_feature]);
                            }
                        }
                        unset($flag);
                    }
                    unset($id_feature);
                    unset($id_feature_value);
                }
            } else {
                foreach ($product_fv as $id_feature => $id_feature_value) {
                    $result['split_by_features'][$id_feature][] = array_merge(array('id_product' => $id_product), $product_features[$id_feature]);
                }
                unset($id_feature);
                unset($id_feature_value);
            }
        }
        unset($id_product);
        unset($product_features);
/*
print_r($product_fv);
print_r($result['split_by_features']);
print_r($result); die;
*/
        if (_PS_CACHE_ENABLED_) {
            Cache::getInstance()->set($key, $result, 7200);
        }

        return $result;
    }

    public function renderRelatedProducts($id_product)
    {
        $this->context->smarty->assign('bestkit_pfeaturesformat', array(
            'products' => $this->getProducts($id_product),
        ));
        return $this->display(__FILE__, 'productFooter.tpl');
    }

    public function hookDisplayFooterProduct($params)
    {
        return $this->renderRelatedProducts($params['product']->id);
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        return $this->renderRelatedProducts(Tools::getValue('id_product'));
    }

    public function ajaxProcessProductfeaturesPagination()
    {
        $this->page = Tools::getValue('page', 1);
        $this->search = Tools::getValue('search');


        /*setup variables*/
        $this->setColorFeatureValues();
        /*pagination*/
        $this->setPagination();
        /*setup smarty variables*/
        $this->setMiscVariables();


        $html = '';
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/bestkit_productfeaturesformat/views/templates/admin/pfeatures.tpl');
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/bestkit_productfeaturesformat/views/templates/admin/list_footer.tpl');
//print_r($html); die;
        die(Tools::jsonEncode(array(
            'html' => $html,
        )));
    }

    public function ajaxProcessProductfeaturesSearch()
    {
        return $this->ajaxProcessProductfeaturesPagination();
    }
}
