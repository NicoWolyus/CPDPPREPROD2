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

class LookBookClass extends ObjectModel
{
    public $id;
    public $id_shop = 1;
    public $title;
    public $description;
    public $actif = 1;

    public $image_presente = false;
    public $group = array();

    protected $table = 'prestablog_lookbook';
    protected $identifier = 'id_prestablog_lookbook';

    protected static $table_static = 'prestablog_lookbook';
    protected static $identifier_static = 'id_prestablog_lookbook';

    public static $definition = array(
        'table' => 'prestablog_lookbook',
        'primary' => 'id_prestablog_lookbook',
        'multilang' => true,
        'fields' => array(
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'actif' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => 255),
            'description' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString'),
        )
    );

    public static function isTableInstalled()
    {
        $table = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SHOW TABLES LIKE \''.bqSQL(_DB_PREFIX_.self::$table_static).'%\'
            ');

        if (count($table) > 0) {
            return true;
        }
        return false;
    }

    public static function getListe($id_lang = null, $only_actif = 0)
    {
        $actif = '';
        if ($only_actif) {
            $actif = 'AND c.`actif` = 1';
        }

        if (empty($id_lang)) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $context = Context::getContext();
        $multiboutique_filtre = 'AND lb.`id_shop` = '.(int)$context->shop->id;

        $filtre_groupes = PrestaBlog::getFiltreGroupes('lb.`id_prestablog_lookbook`', 'lookbook');

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT    lb.*, lbl.*, lb.`'.bqSQL(self::$identifier_static).'` as `id`,
            LEFT(lbl.`title`, '.(int)Configuration::get('prestablog_lb_title_length').') as title
        FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` lb
        JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'_lang` lbl
            ON (lb.`'.bqSQL(self::$identifier_static).'` = lbl.`'.bqSQL(self::$identifier_static).'`)
        WHERE lbl.`id_lang` = '.(int)$id_lang.'
        AND 1=1
        '.$multiboutique_filtre.'
        '.$actif.'
        '.$filtre_groupes);

        if (count($liste) > 0) {
            foreach ($liste as $key => $value) {
                $liste[$key]['description_crop'] = trim(strip_tags(html_entity_decode($value['description'])));

                if (Tools::strlen(trim($liste[$key]['description_crop']))
                    > (int)Configuration::get('prestablog_lb_intro_length')) {
                    $liste[$key]['description_crop'] = PrestaBlog::cleanCut(
                        $liste[$key]['description_crop'],
                        (int)Configuration::get('prestablog_lb_intro_length'),
                        ' [...]'
                    );
                }

                if (file_exists(PrestaBlog::imgUpPath().'/lookbook/'.$value[self::$identifier_static].'.jpg')) {
                    $liste[$key]['image_presente'] = 1;
                }
            }
        }

        return $liste;
    }

    public function delete()
    {
        self::delProductsShapeFromLookBook((int)$this->id);
        self::delAllGroupsLookbook((int)$this->id);

        $module = new PrestaBlog();
        $module->deleteAllImagesThemesLookbook((int)$this->id);

        return parent::delete();
    }

    public static function delProductsShapeFromLookBook($id_prestablog_lookbook)
    {
        // ceci ne supprime pas le lookbook mais juste des produits asssociés
        // à la suite d'un suppression de l'image par exemple
        return Db::getInstance()->Execute('
                DELETE FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_product`
                WHERE `id_prestablog_lookbook` = '.(int)$id_prestablog_lookbook);
    }

    public static function delProductShape($id_prestablog_lookbook_product)
    {
        return Db::getInstance()->Execute('
                DELETE FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_product`
                WHERE `id_prestablog_lookbook_product` = '.(int)$id_prestablog_lookbook_product);
    }

    public static function addProductShape($id_prestablog_lookbook, $id_product, $shape_ed, $shape)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            INSERT INTO `'.bqSQL(_DB_PREFIX_.self::$table_static).'_product`
                (
                    `id_prestablog_lookbook`,
                    `id_product`,
                    `shape_ed`,
                    `shape`
                )
            VALUES
                (
                    '.(int)$id_prestablog_lookbook.',
                    '.(int)$id_product.',
                    \''.pSQL($shape_ed).'\',
                    \''.pSQL($shape).'\'
                )');
    }


    public static function getLookBookProducts($id_prestablog_lookbook)
    {
        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT    l.*, lp.*
        FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_product` lp
        LEFT JOIN `'.bqSQL(_DB_PREFIX_.self::$table_static).'` l
            ON (l.`id_prestablog_lookbook` = lp.`id_prestablog_lookbook`)
        WHERE lp.`id_prestablog_lookbook` = '.(int)$id_prestablog_lookbook);

        return $liste;
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
            `id_shop` int(10) unsigned NOT null,
            `actif` tinyint(1) NOT null DEFAULT \'1\',
            PRIMARY KEY (`'.bqSQL($this->identifier).'`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_lang` (
            `'.bqSQL($this->identifier).'` int(10) unsigned NOT null,
            `id_lang` int(10) unsigned NOT null,
            `title` varchar(255) NOT null,
            `description` text,
            PRIMARY KEY (`'.bqSQL($this->identifier).'`, `id_lang`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_group` (
                `'.bqSQL($this->identifier).'` int(10) unsigned NOT NULL,
                `id_group` int(10) unsigned NOT NULL
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_product` (
            `'.bqSQL($this->identifier).'_product` int(10) unsigned NOT null auto_increment,
            `'.bqSQL($this->identifier).'` int(10) unsigned NOT null,
            `id_product` int(10) unsigned NOT null,
            `shape_ed` text,
            `shape` text,
            PRIMARY KEY (`'.bqSQL($this->identifier).'_product`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
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
            DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_group`
            ')) {
            return false;
        }
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_product`
            ')) {
            return false;
        }

        return true;
    }

    public static function isCustomerPermissionGroups($lookbook)
    {
        $context = Context::getContext();

        $customer = new Customer((int)$context->customer->id);

        $sql_cat_perm = 'SELECT id_prestablog_lookbook
            FROM `'.bqSQL(_DB_PREFIX_).'prestablog_lookbook_group`
            WHERE `id_group` IN ('.implode(',', array_map('intval', $customer->getGroups())).')';

        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql_cat_perm) as $value) {
            if (in_array((int)$value['id_prestablog_lookbook'], $lookbook)) {
                return true;
            }
        }

        return false;
    }

    public static function getGroupsFromLookbook($lookbook)
    {
        $return1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT    DISTINCT `id_group`
        FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_group`
        WHERE `id_prestablog_lookbook` = '.(int)$lookbook);

        $return2 = array();
        foreach ($return1 as $value) {
            $return2[] = $value['id_group'];
        }

        return $return2;
    }

    public static function injectGroupsInLookbook($active_group, $lookbook)
    {
        self::delAllGroupsLookbook((int)$lookbook);

        if (count($active_group) > 0) {
            foreach ($active_group as $group) {
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                    INSERT INTO `'.bqSQL(_DB_PREFIX_.self::$table_static).'_group`
                        (`id_prestablog_lookbook`, `id_group`)
                    VALUES ('.(int)$lookbook.', '.(int)$group.')');
            }
        }

        return true;
    }

    public static function delAllGroupsLookbook($lookbook)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DELETE FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_group`
            WHERE `id_prestablog_lookbook`='.(int)$lookbook);
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
