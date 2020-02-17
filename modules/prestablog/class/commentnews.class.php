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

class CommentNewsClass extends ObjectModel
{
    public $id;
    public $news;
    public $date;
    public $name;
    public $url;
    public $comment;
    public $actif = 0;

    protected $table = 'prestablog_commentnews';
    protected $identifier = 'id_prestablog_commentnews';

    protected static $table_static = 'prestablog_commentnews';
    protected static $identifier_static = 'id_prestablog_commentnews';

    public static $definition = array(
        'table' => 'prestablog_commentnews',
        'primary' => 'id_prestablog_commentnews',
        'fields' => array(
            'date' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true),
            'news' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'actif' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255),
            'url' => array('type' => self::TYPE_STRING, 'validate' => 'isUrlOrEmpty', 'size' => 255),
            'comment' => array('type' => self::TYPE_HTML, 'validate' => 'isString'),
        )
    );

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
            `news` int(10) unsigned NOT null,
            `date` datetime NOT null,
            `name` varchar(255) NOT null,
            `url` varchar(255),
            `comment` text NOT null,
            `actif` int(1) NOT null DEFAULT \'-1\',
            PRIMARY KEY (`'.bqSQL($this->identifier).'`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_abo` (
            `'.bqSQL($this->identifier).'_abo` int(10) unsigned NOT null auto_increment,
            `news` int(10) unsigned NOT null,
            `id_customer` int(10) unsigned NOT null,
            PRIMARY KEY (`'.bqSQL($this->identifier).'_abo`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_commentnews`
            ADD KEY `news` (`news`),
            ADD KEY `actif` (`actif`)')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_commentnews_abo`
            ADD KEY `news` (`news`),
            ADD KEY `id_customer` (`id_customer`)')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            INSERT INTO `'.bqSQL(_DB_PREFIX_.$this->table).'`
            (
                `'.bqSQL($this->identifier).'`,
                `news`,
                `date`,
                `name`,
                `url`,
                `comment`,
                `actif`
            )
            VALUES
            (1, 1, NOW(), \'Lorem ipsum\', \'http://www.prestashop.com\', \'Aliquam eu porttitor justo.
            Etiam a dui aliquam, ultricies purus sed, pharetra odio. Etiam sit amet tempus lacus.\r\n\r\nSuspendisse
            ac eros ut erat luctus hendrerit id sit amet nulla. Sed molestie, leo sit amet imperdiet vulputate,
            tortor ligula viverra ipsum, eu molestie magna elit et tortor. \r\n\r\nCurabitur nulla leo, auctor id
            dapibus sed, varius sit amet mauris. Maecenas pulvinar nunc id risus sagittis, non convallis nisl pharetra.
            Sed eu leo luctus, ornare nunc vel, mattis nisi.\', 1),
            (2, 1, NOW(), \'Lorem ipsum\', \'\', \'Class aptent taciti sociosqu ad litora torquent per conubia nostra,
            per inceptos himenaeos. Nunc placerat sollicitudin nibh, eget pulvinar nunc pellentesque non. Vivamus
            posuere est at ipsum mattis, ac tincidunt tortor accumsan. Sed suscipit nisi orci,\', -1)')) {
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
            DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'_abo`')) {
            return false;
        }

        return true;
    }

    public static function getCountListeAll($only_actif = null, $only_news = null)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND n.`id_shop` = '.(int)$context->shop->id;

        $actif = '';
        if ((int)$only_actif > -2) {
            $actif = 'AND cn.`actif` = '.(int)$only_actif;
        }
        $news = '';
        if ($only_news) {
            $news = 'AND cn.`news` = '.(int)$only_news;
        }

        $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
            SELECT count(cn.`'.bqSQL(self::$identifier_static).'`) as `count`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` cn
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` n ON (cn.`news` = n.`id_prestablog_news`)
            WHERE 1=1
            '.$multiboutique_filtre.'
            '.$news.'
            '.$actif.'
            ORDER BY cn.`date` DESC');

        return $value['count'];
    }

    public static function getListe($only_actif = null, $only_news = null)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND n.`id_shop` = '.(int)$context->shop->id;

        $actif = '';
        if ((int)$only_actif > -2) {
            $actif = 'AND cn.`actif` = '.(int)$only_actif;
        }
        $news = '';
        if ($only_news) {
            $news = 'AND cn.`news` = '.(int)$only_news;
        }

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT cn.*
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` cn
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` n ON (cn.`news` = n.`id_prestablog_news`)
            WHERE 1=1
            '.$multiboutique_filtre.'
            '.$news.'
            '.$actif.'
            ORDER BY cn.`date` DESC');

        return $liste;
    }

    public static function getListeNavigate($only_actif = null, $limit_start = 0, $limit_stop = null)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND n.`id_shop` = '.(int)$context->shop->id;

        $actif = '';
        if ((int)$only_actif > -2) {
            $actif = 'AND cn.`actif` = '.(int)$only_actif;
        }
        $limit = '';
        if (!empty($limit_stop)) {
            $limit = 'LIMIT '.(int)$limit_start.', '.(int)$limit_stop;
        }

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT cn.*
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` cn
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` n ON (cn.`news` = n.`id_prestablog_news`)
            WHERE 1=1
            '.$multiboutique_filtre.'
            '.$actif.'
            ORDER BY cn.`date` DESC
            '.$limit);

        return $liste;
    }

    public static function getNewsFromComment($id_comment)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT    c.`news`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` c
            WHERE c.`'.bqSQL(self::$identifier_static).'`='.(int)$id_comment);

        return $row['news'];
    }

    public static function getListeNonLu($only_news = null)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND n.`id_shop` = '.(int)$context->shop->id;
        $news = '';
        if ($only_news) {
            $news = 'AND c.`news` = '.(int)$only_news;
        }

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT c.*
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` c
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` n ON (c.`news` = n.`id_prestablog_news`)
            WHERE c.`actif` = -1
            '.$multiboutique_filtre.'
            '.$news.'
            ORDER BY c.`date` DESC');

        return $liste;
    }

    public static function getListeDisabled($only_news = null)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND n.`id_shop` = '.(int)$context->shop->id;
        $news = '';
        if ($only_news) {
            $news = 'AND c.`news` = '.(int)$only_news;
        }

        $liste = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT c.*
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` c
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` n ON (c.`news` = n.`id_prestablog_news`)
            WHERE c.`actif` = 0
            '.$multiboutique_filtre.'
            '.$news.'
            ORDER BY c.`date` DESC');

        return $liste;
    }

    public static function insertComment($news, $date, $name, $url, $comment, $actif = -1)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            INSERT INTO `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
                (
                    `news`,
                    `date`,
                    `name`,
                    `url`,
                    `comment`,
                    `actif`
                )
            VALUES
                (
                    '.(int)$news.',
                    \''.pSQL($date).'\',
                    \''.pSQL($name).'\',
                    \''.pSQL($url).'\',
                    \''.pSQL($comment).'\',
                    '.(int)$actif.'
                )');
    }

    public static function listeCommentAbo($news = null)
    {
        $context = Context::getContext();
        $multiboutique_filtre = 'AND n.`id_shop` = '.(int)$context->shop->id;

        $where_news = '';

        if (isset($news)) {
            $where_news = 'AND a.`news` = '.(int)$news;
        }

        $liste = Db::getInstance()->ExecuteS('
            SELECT a.`id_customer`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_abo` a
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` n ON (a.`news` = n.`id_prestablog_news`)
            WHERE 1=1
            '.$multiboutique_filtre.'
            '.$where_news);

        $liste2 = array();
        if (count($liste) > 0) {
            foreach ($liste as $value) {
                $liste2[] = $value['id_customer'];
            }
        }

        return $liste2;
    }

    public static function listeCommentMailAbo($news = null)
    {
        $where_news = '';

        if (isset($news)) {
            $where_news = 'WHERE A.`news` = '.(int)$news;
        }

        $liste = Db::getInstance()->ExecuteS('
            SELECT    DISTINCT A.`id_customer`, C.`email`
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_abo` AS A
            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'customer` AS C
                ON (A.`id_customer` = C.`id_customer`)
            '.$where_news);

        $liste2 = array();
        if (count($liste) > 0) {
            foreach ($liste as $value) {
                $liste2[$value['id_customer']] = $value['email'];
            }
        }

        return $liste2;
    }

    public static function insertCommentAbo($news, $id_customer)
    {
        $abo = Db::getInstance()->ExecuteS('
            SELECT    *
            FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_abo`
            WHERE `news` = '.(int)$news.'
            AND `id_customer` = '.(int)$id_customer);

        if (!count($abo)) {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                INSERT INTO `'.bqSQL(_DB_PREFIX_.self::$table_static).'_abo` (`news`,`id_customer`)
                VALUES ('.(int)$news.', '.(int)$id_customer.')');
        }
    }

    public static function deleteCommentAbo($news, $id_customer)
    {
        return Db::getInstance()->Execute('
            DELETE FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'_abo`
            WHERE `news` = '.(int)$news.'
            AND `id_customer` = '.(int)$id_customer);
    }

    public function changeEtat($field, $force_value)
    {
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            UPDATE `'.bqSQL(_DB_PREFIX_.$this->table).'` SET `'.pSQL($field).'`='.(int)$force_value.'
            WHERE `'.bqSQL($this->identifier).'`='.(int)$this->id)) {
            return false;
        }
        return true;
    }
}
