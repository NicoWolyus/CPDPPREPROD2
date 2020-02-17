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

class SubBlocksClass extends ObjectModel
{
    public $id;
    public $id_shop = 1;
    public $langues;
    public $hook_name = 'displayHome';
    public $template = '';
    public $title;
    public $select_type = 1;
    public $nb_list = 3;
    public $random = 0;
    public $position = 0;
    public $title_length = 80;
    public $intro_length = 200;
    public $use_date_start = 0;
    public $date_start;
    public $use_date_stop = 0;
    public $date_stop;
    public $blog_link = 0;
    public $actif = 1;
    public $blog_categories = array();

    protected $table = 'prestablog_subblock';
    protected $identifier = 'id_prestablog_subblock';

    protected static $table_static = 'prestablog_subblock';
    protected static $identifier_static = 'id_prestablog_subblock';

    public static $definition = array(
        'table' => 'prestablog_subblock',
        'primary' => 'id_prestablog_subblock',
        'multilang' => true,
        'fields' => array(
            'hook_name' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
            'template' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'langues' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'select_type' =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'nb_list' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'random' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'title_length' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'intro_length' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'use_date_start' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_start' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'use_date_stop' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_stop' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'blog_link' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'actif' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => 255),
        )
    );

    public function l($string)
    {
        $module = new PrestaBlog;
        return Translate::getModuleTranslation($module, $string, basename(__FILE__, '.php'));
    }

    public function getListeSelectType()
    {
        return array(
            1 => $this->l('Latest news'),
            2 => $this->l('Oldest news'),
            3 => $this->l('Most commented news'),
            4 => $this->l('Least commented news'),
            5 => $this->l('Most read news'),
            6 => $this->l('Least read news'),
        );
    }

    public function getListeHook()
    {
        $displayCustomHook = 'displayCustomHook - '.$this->l('Custom hook on your tpl file');
        $displayCustomHook .= ' - '.$this->l('FrontOffice');

        $displayModuleBoard = 'displayModuleBoard - '.$this->l('Module home configuration');
        $displayModuleBoard .= ' - '.$this->l('BackOffice');

        return array(
            'displayHome' => 'displayHome - '.$this->l('Home page').' - '.$this->l('FrontOffice'),
            'displayCustomHook' => $displayCustomHook,
            'displayModuleBoard' => $displayModuleBoard
        );
    }

    public function copyFromPost()
    {
        $object = $this;
        $table = $this->table;

        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $object) && $key != 'id_'.$table) {
                if ($key == 'passwd' && Tools::getValue('id_'.$table) && empty($value)) {
                    continue;
                }
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::encrypt($value);
                }
                $object->{$key} = Tools::getValue($key);
            }
        }

        $rules = call_user_func(array(get_class($object), 'getValidationRules'), get_class($object));
        if (count($rules['validateLang'])) {
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (Tools::getIsset($field.'_'.(int)$language['id_lang'])) {
                        $object->{$field}[(int)$language['id_lang']] = Tools::getValue(
                            $field.'_'.(int)$language['id_lang']
                        );
                    }
                }
            }
        }
    }

    public function registerTablesBdd()
    {
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'` (
            `'.bqSQL($this->identifier).'` int(10) unsigned NOT null auto_increment,
            `hook_name` varchar(255) NOT null,
            `template` varchar(255) NOT null,
            `id_shop` int(10) unsigned NOT null,
            `langues` text NOT null,
            `select_type` int(10) unsigned NOT null,
            `nb_list` int(10) unsigned NOT null,
            `random` tinyint(1) NOT null DEFAULT \'0\',
            `position` int(10) unsigned NOT null,
            `title_length` int(10) unsigned NOT null,
            `intro_length` int(10) unsigned NOT null,
            `use_date_start` tinyint(1) NOT null DEFAULT \'0\',
            `date_start` datetime NOT null,
            `use_date_stop` tinyint(1) NOT null DEFAULT \'0\',
            `date_stop` datetime NOT null,
            `blog_link` tinyint(1) NOT null DEFAULT \'0\',
            `actif` tinyint(1) NOT null DEFAULT \'1\',
            `homepage` tinyint(1) NOT null DEFAULT \'0\',
            PRIMARY KEY (`'.bqSQL($this->identifier).'`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_lang` (
            `'.bqSQL($this->identifier).'` int(10) unsigned NOT null,
            `id_lang` int(10) unsigned NOT null,
            `title` varchar(255) NOT null,
            PRIMARY KEY (`'.bqSQL($this->identifier).'`, `id_lang`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_categories` (
            `'.bqSQL($this->identifier).'` int(10) unsigned NOT null,
            `categorie` int(10) unsigned NOT null,
            PRIMARY KEY (`'.bqSQL($this->identifier).'`, `categorie`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_subblock`
            ADD KEY `id_shop` (`id_shop`),
            ADD KEY `actif` (`actif`)')) {
            return false;
        }

        return true;
    }

    public function deleteTablesBdd()
    {
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'`
            ')) {
            return false;
        }
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_lang`
            ')) {
            return false;
        }
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_categories`
            ')) {
            return false;
        }

        return true;
    }

    public static function getHookListe($id_lang = null, $only_actif = 0)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND sb.`id_shop` = '.(int)$context->shop->id;

        $actif = '';
        if ($only_actif) {
            $actif = 'AND sb.`actif` = 1';
        }

        if (empty($id_lang)) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT    DISTINCT(sb.`hook_name`)
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` sb
            JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'_lang` sbl
                ON (sb.`'.bqSQL(self::$identifier_static).'` = sbl.`'.bqSQL(self::$identifier_static).'`)
            WHERE sbl.`id_lang` = '.(int)$id_lang.'
                '.$multiboutique_filtre.'
                '.$actif.'
            ORDER BY sb.`hook_name`');

        $liste2 = array();
        if (count($liste) > 0) {
            foreach ($liste as $value) {
                $liste2[] = $value['hook_name'];
            }
        }

        return $liste2;
    }

    public static function getTitleSubBlock($id, $id_lang)
    {

        if (empty($id_lang)) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
            SELECT sbl.`title`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` sb
            JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'_lang` sbl
                ON (sb.`'.bqSQL(self::$identifier_static).'` = sbl.`'.bqSQL(self::$identifier_static).'`)
            WHERE
                sbl.`id_lang` = '.(int)$id_lang.'
            AND    sb.`'.bqSQL(self::$identifier_static).'` = '.(int)$id);

        return $value['title'];
    }

public static function getTitleSubBlockHome($id, $id_lang)
    {
        if (empty($id_lang)) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
            SELECT sbl.`title`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` sb
            JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'_lang` sbl
                ON (sb.`'.bqSQL(self::$identifier_static).'` = sbl.`'.bqSQL(self::$identifier_static).'`)
            WHERE
                sbl.`id_lang` = '.(int)$id_lang.'
            AND    sb.`'.bqSQL(self::$identifier_static).'` = '.(int)$id).'
            AND    sb.`homepage` = 1';

        return $value['title'];
    }

    public static function updateSubBlock($id)
    {
         if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                    UPDATE `'.bqSQL(_DB_PREFIX_).'prestablog_subblock`
                    SET homepage=1
                    WHERE id_prestablog_subblock='.$id)) {
                    return false;
                }
    }

public static function getIdSbHome($id_lang)
    {
        $context = Context::getContext();
        if (empty($id_lang)) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
            SELECT sbl.`id_prestablog_subblock`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` sb
            JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'_lang` sbl
                ON (sb.`'.bqSQL(self::$identifier_static).'` = sbl.`'.bqSQL(self::$identifier_static).'`)
            WHERE
                sbl.`id_lang` = '.(int)$id_lang.'
            AND    sb.`homepage` = 1
            AND sb.`id_shop` = '.(int)$context->shop->id);

if (isset($value) ) {
    return $value['id_prestablog_subblock'];
} else {
    $value = 0;
    return $value;
}

    }

    public static function getListe($id_lang = null, $only_actif = 0, $hook_name = 'displayHome')
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND sb.`id_shop` = '.(int)$context->shop->id;

        $actif = '';
        if ($only_actif) {
            $actif = 'AND sb.`actif` = 1';
        }

        $lang = '';
        if (empty($id_lang)) {
            $lang = ' AND sbl.`id_lang` = '.(int)Configuration::get('PS_LANG_DEFAULT');
        } elseif (is_array($id_lang)) {
            if (count($id_lang) > 0) {
                foreach ($id_lang as $lang_id) {
                    $lang = ' AND sbl.`id_lang` = '.(int)$lang_id.' ';
                }
            }
        } else {
            if ((int)$id_lang == 0) {
                $lang = '';
            } else {
                $lang = ' AND sbl.`id_lang` = '.(int)$id_lang;
            }
        }

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT    sb.*, sbl.*
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` sb
            JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'_lang` sbl
                ON (sb.`'.bqSQL(self::$identifier_static).'` = sbl.`'.bqSQL(self::$identifier_static).'`)
            WHERE 1=1
                AND sb.`hook_name` = \''.pSQL($hook_name).'\'
                '.$multiboutique_filtre.'
                '.$actif.'
                '.$lang.'
            ORDER BY sb.`position`');

        if (count($liste) > 0) {
            foreach ($liste as $key => $value) {
                $liste_cat = self::getCategories((int)$value['id_prestablog_subblock'], $only_actif);
                if (count($liste_cat) > 1) {
                    $liste[$key]['blog_categories'] = $liste_cat;
                } elseif (count($liste_cat) == 1) {
                    $liste[$key]['blog_categories'] = (int)$liste_cat[0];
                } else {
                    $liste[$key]['blog_categories'] = null;
                }
            }
        }

        return $liste;
    }

    public static function getCategories($id_subblock, $only_actif = 0)
    {
        $actif = '';
        if ($only_actif) {
            $actif = 'AND c.`actif` = 1';
        }

        $return1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT    sbc.`categorie`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_categories` as sbc
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_categorie` as c
                ON (sbc.`categorie` = c.`id_prestablog_categorie`)
            WHERE sbc.`id_prestablog_subblock` = '.(int)$id_subblock.'
                '.$actif.'
            ORDER BY sbc.`categorie`');

        $return2 = array();
        foreach ($return1 as $value) {
            $return2[] = $value['categorie'];
        }

        return $return2;
    }

    public static function getLastPosition()
    {
        $context = Context::getContext();
        $sql = Db::getInstance()->getValue('
            SELECT MAX(`position`) + 1
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
            WHERE  `id_shop` = '.(int)$context->shop->id);

        return $sql;
    }

    public static function delAllCategories($id)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DELETE FROM `'.bqSQL(_DB_PREFIX_).'prestablog_subblock_categories`
            WHERE `id_prestablog_subblock`='.(int)$id);
    }

    public static function delAllCorrespondanceAfterDelCat($cat)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DELETE FROM `'.bqSQL(_DB_PREFIX_).'prestablog_subblock_categories`
            WHERE `categorie`='.(int)$cat);
    }

    public static function updateCategories($categories, $id)
    {
        if (count($categories) > 0) {
            foreach ($categories as $value) {
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                    INSERT INTO `'.bqSQL(_DB_PREFIX_).'prestablog_subblock_categories`
                        (`id_prestablog_subblock`, `categorie`)
                    VALUES ('.(int)$id.', '.(int)$value.')');
            }
        }
    }

    public static function updatePositions($new_positions, $hook_name)
    {
        foreach ($new_positions as $position => $id) {
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                UPDATE `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
                    SET `position`='.(int)$position.'
                WHERE `'.bqSQL(self::$identifier_static).'`='.(int)$id.'
                    AND `hook_name`=\''.$hook_name.'\'');
        }
    }

    public function changeEtat($field)
    {
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            UPDATE `'.bqSQL(_DB_PREFIX_.$this->table).'`
            SET `'.pSQL($field).'`=CASE `'.pSQL($field).'` WHEN 1 THEN 0 WHEN 0 THEN 1 END
            WHERE `'.bqSQL($this->identifier).'`='.(int)$this->id)) {
            return false;
        }
        return true;
    }
}
