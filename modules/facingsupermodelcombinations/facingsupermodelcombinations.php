<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * Class Facingsupermodelcombinations
 */
class Facingsupermodelcombinations extends Module
{

    /**
     *
     */
    public function __construct()
    {
        $this->name = 'facingsupermodelcombinations';
        $this->tab = 'advertising_marketing';
        $this->version = '1.1.3';
        $this->module_key = 'b53bfd83ec88a52a3c3abe9f190b4d9f';
        $this->author = 'Evolutive Group';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Supermodel: products grouping on product sheet');
        $this->description = $this->l('Associate unitary products as if they were combinations \(color, weight, sizes ...\)');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * @return bool
     */
    public function install()
    {
        Configuration::updateValue('PS_FACING_FEATURE', '');
        Configuration::updateValue('PS_FACING_ATTRIBUTE', '');
        Configuration::updateValue('PS_FACING_ACTIVE_FEATURE', '');
        if (!parent::install() || !$this->registerHook('header')) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('PS_FACING_FEATURE');
        Configuration::deleteByName('PS_FACING_ATTRIBUTE');
        Configuration::deleteByName('PS_FACING_ACTIVE_FEATURE');
        return parent::uninstall();
    }

    /**
     * @return HelperForm
     */
    public function initHelperForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

        return $helper;
    }

    public function getContent()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/facing.css');
        $output = '';
        if (Tools::isSubmit('submitFacing')) {
            $output .= $this->postProcessFacing();
        }
        $output .= $this->renderFacingForm();
        return $output;
    }

    /**
     * @return string
     */
    public function renderFacingForm()
    {
        $helper = $this->initHelperForm();
        $helper->submit_action = 'submitFacing';
        $helper->name_controller = 'facing';
        $fieldsValue = $this->getConfigFieldsValues();
        $helper->tpl_vars = array(
            'fields_value' => $fieldsValue,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        $features = Feature::getFeatures((int) Context::getContext()->cookie->id_lang, true);
        $select_feature = array();
        $select_feature['id_feature'] = '0';
        $select_feature['name'] = $this->l('FEATURES');
        array_unshift($features, $select_feature);
        $attributes = AttributeGroup::getAttributesGroups((int) Context::getContext()->cookie->id_lang);
        $select_attribute = array();
        $select_attribute['id_attribute_group'] = '0';
        $select_attribute['name'] = $this->l('ATTRIBUTES');
        array_unshift($attributes, $select_attribute);
        $form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('configure', 'facingsupermodelcombinations'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Please select the FEATURE which create the link between 2 or X products :', 'facingsupermodelcombinations'),
                        'name' => 'features',
                        'col' => '5',
                        'hint' => $this->l('info feature', 'facingsupermodelcombinations'),
                        'required' => true,
                        'options' => array(
                            'query' => $features,
                            'id' => 'id_feature',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Hide this feature on product sheet', 'facingsupermodelcombinations'),
                        'name' => 'active',
                        'is_bool' => true,
                        'col' => '5',
                        'hint' => $this->l('info show', 'facingsupermodelcombinations'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled', 'facingsupermodelcombinations'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled', 'facingsupermodelcombinations'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Please select the ATTRIBUTE which triggers the change of page :', 'facingsupermodelcombinations'),
                        'name' => 'attributes',
                        'col' => '5',
                        'required' => true,
                        'hint' => $this->l('info attribute', 'facingsupermodelcombinations'),
                        'options' => array(
                            'query' => $attributes,
                            'id' => 'id_attribute_group',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        $helperHtml = $helper->generateForm(array($form));

        return $helperHtml;
    }

    /**
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'attributes' => Configuration::get('PS_FACING_ATTRIBUTE'),
            'features' => Configuration::get('PS_FACING_FEATURE'),
            'active' => Configuration::get('PS_FACING_ACTIVE_FEATURE'),
        );
    }

    public function postProcessFacing()
    {
        $facing_feature = (int) Tools::getValue('features');
        $facing_attribute = (int) Tools::getValue('attributes');
        $active_feature = (int) Tools::getValue('active');
        Configuration::updateValue('PS_FACING_ACTIVE_FEATURE', $active_feature);
        if ($facing_feature == 0) {
            return $this->displayError($this->l('you must choose a feature'));
        } else {
            Configuration::updateValue('PS_FACING_FEATURE', $facing_feature);
        }
        if ($facing_attribute == 0) {
            return $this->displayError($this->l('you must choose an attribute'));
        } else {
            Configuration::updateValue('PS_FACING_ATTRIBUTE', $facing_attribute);
        }
        return $this->displayConfirmation($this->l('Successfully saved settings'));
    }

    public function displayFacing($id_product, $lang)
    {
        $facing_data = array();
        if (Configuration::get('PS_FACING_FEATURE') != '' && Configuration::get('PS_FACING_ATTRIBUTE') != '') {
            //get feature facing value
            $dbQuery = new DbQuery();
            $dbQuery->select('fp.id_feature_value');
            $dbQuery->from('feature_product', 'fp');
            $dbQuery->where('fp.id_feature = ' . (int) Configuration::get('PS_FACING_FEATURE'));
            $dbQuery->where('fp.id_product = ' . (int) $id_product);
            $feature = Db::getInstance(_PS_USE_SQL_SLAVE_)
                ->getValue($dbQuery);
            if ($feature != false) {
                // get all products has the same feature value
                $dbQueryProducts = new DbQueryCore();
                $dbQueryProducts->select('fp.id_product');
                $dbQueryProducts->from('feature_product', 'fp');
                $dbQueryProducts->leftJoin('product_shop', 'ps', 'ps.`id_product` = fp.`id_product`');
                $dbQueryProducts->where('fp.id_feature_value = ' . (int) $feature);
                $dbQueryProducts->where('ps.active = 1');
                $dbQueryProducts->where('ps.id_shop =' . (int) $this->context->shop->id);
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)
                    ->executeS($dbQueryProducts);

                if (count($result) > 1) {
                    $attributes = array();
                    $attributeGroup = new AttributeGroup((int) Configuration::get('PS_FACING_ATTRIBUTE'));
                    $typeGroup = $attributeGroup->group_type;
                    $facing_data['show_feature'] = (int) Configuration::get('PS_FACING_ACTIVE_FEATURE');
                    $facing_data['group'] = (int) Configuration::get('PS_FACING_ATTRIBUTE');
                    $facing_data['group_type'] = $typeGroup;
                    $facingFeature = new Feature((int) Configuration::get('PS_FACING_FEATURE'));
                    $facingFeatureName = $facingFeature->name[(int) $lang];
                    $facing_data['facingfeature'] = $facingFeatureName;
                    if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
                        $facing_data['version'] = "1.7";
                    } else {
                        $facing_data['version'] = "1.6";
                    }
                    foreach ($result as $row) {
                        $link = new Link();
                        $productobj = new Product((int) $row['id_product']);
                        $productLink = $link->getProductLink((int) $productobj->id, $productobj->link_rewrite[(int) $lang], $productobj->category, $productobj->ean13);

                        $facing_attributes = $this->hasAttributeFacing((int) $row['id_product'], (int) $lang);
                        if (!empty($facing_attributes)) {
                            foreach ($facing_attributes as $key => $ligne) {
                                $facing_attributes[$key]['link'] = $productLink;
                                if (file_exists(_PS_COL_IMG_DIR_ . $facing_attributes[$key]['id_attribute'] . '.jpg')) {
                                    $facing_attributes[$key]['img_color_exists'] = 1;
                                } else {
                                    $facing_attributes[$key]['img_color_exists'] = 0;
                                }
                            }
                            array_unshift($attributes, $facing_attributes);
                        }
                    }
                    if (count($attributes) > 1) {
                        $facing_data['products'] = $attributes;
                    } else {
                        $facing_data['products'] = array();
                    }
                }
            }
        }
        return $facing_data;
    }

    public function hasAttributeFacing($id_product, $id_lang)
    {

        $dbQuery = new DbQueryCore();
        $dbQuery->select('a.id_attribute, a.color,al.name, pa.id_product, a.id_attribute_group');
        $dbQuery->from('attribute', 'a');
        $dbQuery->leftJoin('attribute_lang', 'al', 'al.`id_attribute` = a.`id_attribute`');
        $dbQuery->leftJoin('product_attribute_combination', 'pac', 'pac.`id_attribute` = al.`id_attribute`');
        $dbQuery->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = pac.`id_product_attribute` ' . Shop::addSqlAssociation('product_attribute', 'pa') . '');
        $dbQuery->where('a.id_attribute_group = ' . (int) Configuration::get('PS_FACING_ATTRIBUTE'));
        $dbQuery->where('pa.id_product = ' . (int) $id_product);
        $dbQuery->where('al.id_lang = ' . (int) $id_lang);
        $dbQuery->groupBy('pac.id_attribute');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)
            ->executeS($dbQuery);
        return $result;
    }

    public function hookHeader($param)
    {
        /* display in quick view */
        if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
            $baseDir = __PS_BASE_URI__;
            $this->context->controller->addJS($this->_path . 'views/js/quickview.js');
            $info_declinations = $this->l('This product exists with other declinations :');
            Media::addJsDef(array('baseDir' => $baseDir, 'img_col_dir' => _THEME_COL_DIR_, 'info_declinations' => $info_declinations));
        }
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'product') {
            $product = $this->context->controller->getProduct();
            $lang = $param['cookie']->id_lang;
            $data = $this->displayFacing((int) $product->id, (int) $lang);

            if (!empty($data['products'])) {
                $this->context->controller->addJS($this->_path . 'views/js/facing.js');
                $this->context->controller->addCSS($this->_path . 'views/css/facing.css');
                Media::addJsDef(array('facingData' => $data, 'img_col_dir' => _THEME_COL_DIR_, 'product_id' => (int) $product->id));
            }
        }
    }
}
