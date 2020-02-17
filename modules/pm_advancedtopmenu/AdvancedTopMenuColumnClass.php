<?php
/**
 * Advanced Top Menu
 *
 * @author    Presta-Module.com <support@presta-module.com> - http://www.presta-module.com
 * @copyright Presta-Module 2019 - http://www.presta-module.com
 * @license   Commercial
 *
 *           ____     __  __
 *          |  _ \   |  \/  |
 *          | |_) |  | |\/| |
 *          |  __/   | |  | |
 *          |_|      |_|  |_|
 */

class AdvancedTopMenuColumnClass extends ObjectModel
{
    public $id;
    public $id_wrap;
    public $id_menu;
    public $id_category;
    public $id_cms;
    public $id_supplier;
    public $id_manufacturer;
    public $id_specific_page;
    public $name;
    public $link;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $type;
    public $privacy;
    public $chosen_groups;
    public $have_icon;
    public $image_type;
    public $image_legend;
    public $value_over;
    public $value_under;
    public $id_menu_depend;
    public $target;
    public $is_column = true;
    public $position = 0;
    protected $tables = array(
        'pm_advancedtopmenu_columns',
        'pm_advancedtopmenu_columns_lang',
    );
    protected $fieldsRequired = array(
        'active',
        'type',
        'id_wrap',
        'id_menu',
    );
    protected $fieldsSize = array(
        'active' => 1,
    );
    protected $fieldsValidate = array(
        'active' => 'isBool',
    );
    protected $fieldsRequiredLang = array();
    protected $fieldsSizeLang = array(
        'name' => 255,
        'link' => 255,
    );
    protected $fieldsValidateLang = array(
        'name' => 'isCatalogName',
        'link' =>'isUrl',
        'have_icon' => 'isBool',
        'image_type' => 'isString',
        'image_legend' => 'isCatalogName',
        'value_over' => 'isString',
        'value_under' => 'isString',
    );
    protected $table = 'pm_advancedtopmenu_columns';
    protected $identifier = 'id_column';
    public static $definition = array(
        'table' => 'pm_advancedtopmenu_columns',
        'primary' => 'id_column',
        'multishop' => false,
        'multilang_shop' => false,
        'multilang' => true,
        'fields' => array(
            'name' =>         array('type' => 3, 'lang' => true, 'required' => false, 'size' => 255),
            'link' =>         array('type' => 3, 'lang' => true, 'required' => false, 'size' => 255),
            'value_over' =>   array('type' => 3, 'lang' => true, 'required' => false),
            'value_under' =>  array('type' => 3, 'lang' => true, 'required' => false),
            'have_icon' =>    array('type' => 3, 'lang' => true, 'required' => false),
            'image_type' =>   array('type' => 3, 'lang' => true, 'required' => false),
            'image_legend' => array('type' => 3, 'lang' => true, 'required' => false),
        ),
    );
    public function __construct($id_column = null, $id_lang = null)
    {
        parent::__construct($id_column, $id_lang);
        $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);
    }
    public function getFields()
    {
        parent::validateFields();
        $fields = array();
        if (isset($this->id)) {
            $fields['id_column'] = (int)$this->id;
        }
        $fields['id_wrap'] = (int)$this->id_wrap;
        $fields['id_menu'] = (int)$this->id_menu;
        $fields['active'] = (int)$this->active;
        $fields['active_desktop'] = (int)$this->active_desktop;
        $fields['active_mobile'] = (int)$this->active_mobile;
        $fields['id_category'] = (int)$this->id_category;
        $fields['id_cms'] = (int)$this->id_cms;
        $fields['id_supplier'] = (int)$this->id_supplier;
        $fields['id_manufacturer'] = (int)$this->id_manufacturer;
        $fields['id_specific_page'] = pSQL($this->id_specific_page);
        $fields['type'] = (int)$this->type;
        $fields['target'] = pSQL($this->target);
        $fields['id_menu_depend'] = (int)$this->id_menu_depend;
        $fields['privacy'] = (int)$this->privacy;
        $fields['chosen_groups'] = pSQL($this->chosen_groups);
        $fields['position'] = (int)$this->position;
        return $fields;
    }
    public function getTranslationsFieldsChild()
    {
        parent::validateFieldsLang();
        $fieldsArray = array('name', 'link', 'have_icon', 'image_type', 'image_legend');
        $fields = array();
        $languages = Language::getLanguages(false);
        $defaultLanguage = Configuration::get('PS_LANG_DEFAULT');
        foreach ($languages as $language) {
            $fields[$language['id_lang']]['id_lang'] = $language['id_lang'];
            $fields[$language['id_lang']][$this->identifier] = (int)$this->id;
            $fields[$language['id_lang']]['value_over'] = (isset($this->value_over[$language['id_lang']])) ? pSQL($this->value_over[$language['id_lang']], true) : '';
            $fields[$language['id_lang']]['value_under'] = (isset($this->value_under[$language['id_lang']])) ? pSQL($this->value_under[$language['id_lang']], true) : '';
            foreach ($fieldsArray as $field) {
                if (!Validate::isTableOrIdentifier($field)) {
                    die(Tools::displayError());
                }
                if (isset($this->{$field}[$language['id_lang']]) and !empty($this->{$field}[$language['id_lang']])) {
                    $fields[$language['id_lang']][$field] = pSQL($this->{$field}[$language['id_lang']]);
                } elseif (in_array($field, $this->fieldsRequiredLang)) {
                    $fields[$language['id_lang']][$field] = pSQL($this->{$field}[$defaultLanguage]);
                } else {
                    $fields[$language['id_lang']][$field] = '';
                }
            }
        }
        return $fields;
    }
    public function delete()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            if (file_exists(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/column_icons/' . (int)$this->id . '-' . $language['iso_code'] . '.' . ($this->image_type[$language['id_lang']] ? $this->image_type[$language['id_lang']] : 'jpg'))) {
                @unlink(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/column_icons/' . (int)$this->id . '-' . $language['iso_code'] . '.' . ($this->image_type[$language['id_lang']] ? $this->image_type[$language['id_lang']] : 'jpg'));
            }
        }
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns` WHERE `id_column`=' . (int)$this->id);
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns_lang` WHERE `id_column`=' . (int)$this->id);
        $elements = AdvancedTopMenuElementsClass::getElementIds((int)$this->id);
        foreach ($elements as $id_element) {
            $obj = new AdvancedTopMenuElementsClass($id_element);
            $obj->delete();
        }
        $productElementsObj = AdvancedTopMenuProductColumnClass::getByIdColumn($this->id);
        if (Validate::isLoadedObject($productElementsObj)) {
            $productElementsObj->delete();
        }
        return true;
    }
    public static function getIdColumnCategoryDepend($id_menu, $id_category)
    {
        $row = Db::getInstance()->getRow('SELECT `id_column`
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns`
                WHERE `id_menu_depend` = '.(int)$id_menu.' AND `id_category` = '.(int)$id_category);
        return (isset($row['id_column'])?$row['id_column']:false);
    }
    public static function getIdColumnCategoryDependEmptyColumn($id_menu, $id_category)
    {
        $row = Db::getInstance()->getRow('SELECT atmc.`id_column`
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns` as atmc
                WHERE atmc.`id_menu_depend` = '.(int)$id_menu.' AND atmc.`id_category` = '.(int)$id_category.'');
        return (isset($row['id_column'])?$row['id_column']:false);
    }
    public static function getIdColumnManufacturerDependEmptyColumn($id_menu, $id_manufacturer)
    {
        $row = Db::getInstance()->getRow('SELECT atmc.`id_column`
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns` as atmc
                LEFT JOIN `'._DB_PREFIX_.'pm_advancedtopmenu_elements` atme ON (atmc.`id_column` = atme.`id_column_depend`)
                WHERE atmc.`id_menu_depend` = '.(int)$id_menu.' AND atme.`id_manufacturer` = '.(int)$id_manufacturer.'');
        return (isset($row['id_column'])?$row['id_column']:false);
    }
    public static function getIdColumnSupplierDependEmptyColumn($id_menu, $id_supplier)
    {
        $row = Db::getInstance()->getRow('SELECT atmc.`id_column`
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns` as atmc
                LEFT JOIN `'._DB_PREFIX_.'pm_advancedtopmenu_elements` atme ON (atmc.`id_column` = atme.`id_column_depend`)
                WHERE atmc.`id_menu_depend` = '.(int)$id_menu.' AND atme.`id_supplier` = '.(int)$id_supplier.'');
        return (isset($row['id_column'])?$row['id_column']:false);
    }
    public static function getIdMenuByIdColumn($id_column)
    {
        $row = Db::getInstance()->getRow('SELECT `id_menu`
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns`
                WHERE `id_column` = '.(int)$id_column.'');
        return (isset($row['id_menu'])?$row['id_menu']:false);
    }
    public static function columnHaveDepend($id_column)
    {
        $sql = 'SELECT `id_column`
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_elements`
                WHERE `id_column_depend` = '.(int)$id_column;
        return Db::getInstance()->ExecuteS($sql);
    }
    public static function getMenuColums($id_wrap, $id_lang, $active = true, $groupRestrict = false)
    {
        $sql_groups_join = '';
        $sql_groups_where = '';
        if ($groupRestrict && Group::isFeatureActive()) {
            $groups = PM_AdvancedTopMenu::getCustomerGroups();
            if (count($groups)) {
                $sql_groups_join = 'LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = ca.`id_category`)';
                $sql_groups_where = 'AND IF (atmc.`id_category` IS NULL OR atmc.`id_category` = 0, 1, cg.`id_group` IN ('.implode(',', array_map('intval', $groups)).'))';
            }
        }
        $sql = 'SELECT atmc.*, atmcl.*,
                cl.link_rewrite, cl.meta_title,
                cal.link_rewrite as category_link_rewrite, cal.name as category_name,
                m.name as manufacturer_name,
                s.name as supplier_name
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns` atmc
                LEFT JOIN `'._DB_PREFIX_.'pm_advancedtopmenu_columns_lang` atmcl ON (atmc.`id_column` = atmcl.`id_column` AND atmcl.`id_lang` = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'cms c ON (c.id_cms = atmc.`id_cms`)
                '.Shop::addSqlAssociation('cms', 'c', false).'
                LEFT JOIN '._DB_PREFIX_.'cms_lang cl ON (c.id_cms = cl.id_cms AND cl.id_lang = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'category ca ON (ca.id_category = atmc.`id_category`)
                ' . $sql_groups_join . '
                LEFT JOIN '._DB_PREFIX_.'category_lang cal ON (ca.id_category = cal.id_category AND cal.id_lang = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cal').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (atmc.`id_manufacturer` = m.`id_manufacturer`)
                '.Shop::addSqlAssociation('manufacturer', 'm', false).'
                LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (atmc.`id_supplier` = s.`id_supplier`)
                '.Shop::addSqlAssociation('supplier', 's', false).'
                WHERE '.($active ? ' atmc.`active` = 1 AND (atmc.`active_desktop` = 1 || atmc.`active_mobile` = 1) AND ':'').' atmc.`id_wrap` = '.(int)$id_wrap.'
                '.($active ? 'AND ((atmc.`id_manufacturer` = 0 AND atmc.`id_supplier` = 0 AND atmc.`id_category` = 0 AND atmc.`id_cms` = 0)
                OR c.id_cms IS NOT NULL OR m.id_manufacturer IS NOT NULL OR ca.id_category IS NOT NULL OR s.`id_supplier` IS NOT NULL)':'')
                . $sql_groups_where . '
                GROUP BY atmc.`id_column`
                ORDER BY atmc.`position`';
        return Db::getInstance()->ExecuteS($sql);
    }
    public static function getMenuColumsByIdMenu($id_menu, $id_lang, $active = true, $groupRestrict = false)
    {
        $sql_groups_join = '';
        $sql_groups_where = '';
        if ($groupRestrict && Group::isFeatureActive()) {
            $groups = PM_AdvancedTopMenu::getCustomerGroups();
            if (count($groups)) {
                $sql_groups_join = 'LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = ca.`id_category`)';
                $sql_groups_where = 'AND IF (atmc.`id_category` IS NULL OR atmc.`id_category` = 0, 1, cg.`id_group` IN ('.implode(',', array_map('intval', $groups)).'))';
            }
        }
        $sql = 'SELECT atmc.*, atmcl.*,
                cl.link_rewrite, cl.meta_title,
                cal.link_rewrite as category_link_rewrite, cal.name as category_name,
                m.name as manufacturer_name,
                s.name as supplier_name
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns` atmc
                LEFT JOIN `'._DB_PREFIX_.'pm_advancedtopmenu_columns_lang` atmcl ON (atmc.`id_column` = atmcl.`id_column` AND atmcl.`id_lang` = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'cms c ON (c.id_cms = atmc.`id_cms`)
                '.Shop::addSqlAssociation('cms', 'c', false).'
                LEFT JOIN '._DB_PREFIX_.'cms_lang cl ON (c.id_cms = cl.id_cms AND cl.id_lang = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'category ca ON (ca.id_category = atmc.`id_category`)
                ' . $sql_groups_join . '
                LEFT JOIN '._DB_PREFIX_.'category_lang cal ON (ca.id_category = cal.id_category AND cal.id_lang = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cal').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (atmc.`id_manufacturer` = m.`id_manufacturer`)
                '.Shop::addSqlAssociation('manufacturer', 'm', false).'
                LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (atmc.`id_supplier` = s.`id_supplier`)
                '.Shop::addSqlAssociation('supplier', 's', false).'
                WHERE '.($active ? ' atmc.`active` = 1 AND (atmc.`active_desktop` = 1 || atmc.`active_mobile` = 1) AND':'').' atmc.`id_menu` = '.(int)$id_menu.'
                '.($active ? 'AND ((atmc.`id_manufacturer` = 0 AND atmc.`id_supplier` = 0 AND atmc.`id_category` = 0 AND atmc.`id_cms` = 0)
                OR c.id_cms IS NOT NULL OR m.id_manufacturer IS NOT NULL OR ca.id_category IS NOT NULL OR s.`id_supplier` IS NOT NULL)':'')
                . $sql_groups_where . '
                AND atmc.`type` != 8
                GROUP BY atmc.`id_column`
                ORDER BY atmc.`position`';
        return Db::getInstance()->ExecuteS($sql);
    }
    public static function getMenusColums($menus, $id_lang, $groupRestrict = false)
    {
        $columns = array();
        foreach ($menus as $columnsWrap) {
            foreach ($columnsWrap as $columnWrap) {
                $columnInfos = self::getMenuColums($columnWrap['id_wrap'], $id_lang, true, $groupRestrict);
                foreach ($columnInfos as &$columnInfo) {
                    if (isset($columnInfo['id_column']) && $columnInfo['type'] == 8) {
                        $productInfos = AdvancedTopMenuProductColumnClass::getByIdColumn($columnInfo['id_column']);
                        if (Validate::isLoadedObject($productInfos)) {
                            $productObj = new Product($productInfos->id_product, true, $id_lang);
                            if (Validate::isLoadedObject($productObj)) {
                                if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                                    $context = Context::getContext();
                                    $assembler = new ProductAssembler($context);
                                    $presenterFactory = new ProductPresenterFactory($context);
                                    $presentationSettings = $presenterFactory->getPresentationSettings();
                                    $presenter = new PrestaShop\PrestaShop\Core\Product\ProductListingPresenter(
                                        new PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
                                            $context->link
                                        ),
                                        $context->link,
                                        new PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                                        new PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever(),
                                        $context->getTranslator()
                                    );
                                    $productForTemplate = $presenter->present(
                                        $presentationSettings,
                                        $assembler->assembleProduct($productObj->getFields()),
                                        $context->language
                                    );
                                    $columnInfo['productInfos'] = $productForTemplate;
                                    $columnInfo['productSettings'] = $productInfos;
                                } else {
                                    $productArray = $productObj->getFields();
                                    $productArrayLang = array(
                                        'meta_description' => $productObj->meta_description,
                                        'meta_keywords' => $productObj->meta_keywords,
                                        'meta_title' => $productObj->meta_title,
                                        'link_rewrite' => $productObj->link_rewrite,
                                        'name' => $productObj->name,
                                        'description' => $productObj->description,
                                        'description_short' => $productObj->description_short,
                                        'available_now' => $productObj->available_now,
                                        'available_later' => $productObj->available_later,
                                    );
                                    $productArray = array_merge($productArray, $productArrayLang);
                                    if (isset($productObj->out_of_stock)) {
                                        $productArray['out_of_stock'] = $productObj->out_of_stock;
                                    }
                                    $productArray['id_image'] = Product::getCover($productObj->id);
                                    if (is_array($productArray['id_image'])) {
                                        $productArray['id_image'] = current($productArray['id_image']);
                                    } else {
                                        unset($productArray['id_image']);
                                    }
                                    $columnInfo['productInfos'] = Product::getProductProperties($id_lang, $productArray);
                                    $columnInfo['productSettings'] = $productInfos;
                                }
                            }
                        }
                    }
                }
                $columns[$columnWrap['id_wrap']] = $columnInfos;
            }
        }
        return $columns;
    }
    public static function getColumnIds($ids_wrap)
    {
        if (!is_array($ids_wrap)) {
            $ids_wrap = array((int)$ids_wrap);
        }
        $result =  Db::getInstance()->ExecuteS('
        SELECT `id_column`
        FROM '._DB_PREFIX_.'pm_advancedtopmenu_columns
        WHERE `id_wrap` IN(' . implode(',', array_map('intval', $ids_wrap)) . ')');
        $columns = array();
        foreach ($result as $row) {
            $columns[] = $row['id_column'];
        }
        return $columns;
    }
}
