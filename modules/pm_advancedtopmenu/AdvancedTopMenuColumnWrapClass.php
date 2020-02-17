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

class AdvancedTopMenuColumnWrapClass extends ObjectModel
{
    public $id;
    public $id_menu;
    public $internal_name;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $width;
    public $privacy;
    public $chosen_groups;
    public $position;
    public $value_over;
    public $value_under;
    public $bg_color;
    public $txt_color_column;
    public $txt_color_column_over;
    public $txt_color_element;
    public $txt_color_element_over;
    public $id_menu_depend;
    protected $tables = array(
        'pm_advancedtopmenu_columns_wrap',
        'pm_advancedtopmenu_columns_wrap_lang',
    );
    protected $fieldsRequired = array(
        'active',
        'id_menu',
    );
    protected $fieldsSize = array(
        'active' => 1,
    );
    protected $fieldsValidate = array(
        'active' => 'isBool',
    );
    protected $fieldsRequiredLang = array();
    protected $fieldsSizeLang = array();
    protected $fieldsValidateLang = array(
        'value_over' => 'isString',
        'value_under' => 'isString',
    );
    protected $table = 'pm_advancedtopmenu_columns_wrap';
    protected $identifier = 'id_wrap';
    public static $definition = array(
        'table' => 'pm_advancedtopmenu_columns_wrap',
        'primary' => 'id_wrap',
        'multishop' => false,
        'multilang_shop' => false,
        'multilang' => true,
        'fields' => array(
            'value_over' =>  array('type' => 3, 'lang' => true, 'required' => false),
            'value_under' => array('type' => 3, 'lang' => true, 'required' => false),
        ),
    );
    public function __construct($id_wrap = null, $id_lang = null)
    {
        parent::__construct($id_wrap, $id_lang);
        $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);
    }
    public function getFields()
    {
        parent::validateFields();
        $fields = array();
        if (isset($this->id)) {
            $fields['id_wrap'] = (int)$this->id;
        }
        $fields['internal_name'] = pSQL($this->internal_name);
        $fields['id_menu'] = (int)$this->id_menu;
        $fields['active'] = (int)$this->active;
        $fields['active_desktop'] = (int)$this->active_desktop;
        $fields['active_mobile'] = (int)$this->active_mobile;
        $fields['id_menu_depend'] = (int)$this->id_menu_depend;
        $fields['privacy'] = (int)$this->privacy;
        $fields['chosen_groups'] = pSQL($this->chosen_groups);
        $fields['position'] = (int)$this->position;
        $fields['width'] = (int)$this->width;
        $fields['bg_color'] = pSQL($this->bg_color);
        $fields['txt_color_column'] = pSQL($this->txt_color_column);
        $fields['txt_color_column_over'] = pSQL($this->txt_color_column_over);
        $fields['txt_color_element'] = pSQL($this->txt_color_element);
        $fields['txt_color_element_over'] = pSQL($this->txt_color_element_over);
        return $fields;
    }
    public function getTranslationsFieldsChild()
    {
        parent::validateFieldsLang();
        $fields = array();
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $fields[$language['id_lang']]['id_lang'] = $language['id_lang'];
            $fields[$language['id_lang']][$this->identifier] = (int)$this->id;
            $fields[$language['id_lang']]['value_over'] = (isset($this->value_over[$language['id_lang']])) ? pSQL($this->value_over[$language['id_lang']], true) : '';
            $fields[$language['id_lang']]['value_under'] = (isset($this->value_under[$language['id_lang']])) ? pSQL($this->value_under[$language['id_lang']], true) : '';
        }
        return $fields;
    }
    public function delete()
    {
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap` WHERE `id_wrap`=' . (int)$this->id);
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap_lang` WHERE `id_wrap`=' . (int)$this->id);
        $columns = AdvancedTopMenuColumnClass::getColumnIds((int)$this->id);
        foreach ($columns as $id_column) {
            $obj = new AdvancedTopMenuColumnClass($id_column);
            $obj->delete();
        }
        return true;
    }
    public static function getMenuColumnsWrap($id_menu, $id_lang, $active = true)
    {
        $sql = 'SELECT atmcw.*, atmcwl.*
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap` atmcw
                LEFT JOIN `'._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap_lang` atmcwl ON (atmcw.`id_wrap` = atmcwl.`id_wrap` AND atmcwl.`id_lang` = '.(int)$id_lang.')
                WHERE '.($active?' atmcw.`active` = 1 AND (atmcw.`active_desktop` = 1 || atmcw.`active_mobile` = 1) AND':'').' atmcw.`id_menu` = '.(int)$id_menu.'
                ORDER BY atmcw.`position`';
        return Db::getInstance()->ExecuteS($sql);
    }
    public static function getMenusColumnsWrap($menus, $id_lang)
    {
        $columnWrap = array();
        if (is_array($menus) && count($menus)) {
            foreach ($menus as $menu) {
                $columnWrap[$menu['id_menu']] = self::getMenuColumnsWrap($menu['id_menu'], $id_lang);
            }
        }
        return $columnWrap;
    }
    public static function getColumnsWrap($id_lang = false, $active = true)
    {
        $sql = 'SELECT atmcw.* '.($id_lang?',atmcwl.*':'').'
                FROM `'._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap` atmcw
                '.($id_lang?'LEFT JOIN `'._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap_lang` atmcwl ON (atmcw.`id_wrap` = atmcwl.`id_wrap` AND atmcwl.`id_lang` = '.(int)$id_lang.')':'').'
                WHERE 1 '.($active?'AND atmcw.`active` = 1 AND (atmcw.`active_desktop` = 1 || atmcw.`active_mobile` = 1)':'').'
                ORDER BY atmcw.`position`';
        return Db::getInstance()->ExecuteS($sql);
    }
    public static function getColumnWrapIds($ids_menu)
    {
        $result =  Db::getInstance()->ExecuteS('
        SELECT `id_wrap`
        FROM '._DB_PREFIX_.'pm_advancedtopmenu_columns_wrap
        WHERE `id_menu` IN('.pSQL($ids_menu).')');
        $columnsWrap = array();
        foreach ($result as $row) {
            $columnsWrap[] = $row['id_wrap'];
        }
        return $columnsWrap;
    }
}
