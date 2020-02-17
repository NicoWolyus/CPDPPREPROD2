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

class CorrespondancesCategoriesClass extends ObjectModel
{
    public $id;
    public $categorie;
    public $news;

    protected $table = 'prestablog_correspondancecategorie';
    protected $identifier = 'id_prestablog_correspondancecategorie';

    protected static $table_static = 'prestablog_correspondancecategorie';
    protected static $identifier_static = 'id_prestablog_correspondancecategorie';

    public static function getCategoriesListe($news)
    {
        $return1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT    `categorie`
        FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
        WHERE `news` = '.(int)$news);

        $return2 = array();
        foreach ($return1 as $value) {
            $return2[] = $value['categorie'];
        }

        return $return2;
    }

    public static function getCategoriesListeName($news, $lang, $only_actif = 0)
    {
        $actif = '';
        if ($only_actif) {
            $actif = 'AND c.`actif` = 1';
        }

        $filtre_groupes = PrestaBlog::getFiltreGroupes('cc.`categorie`', 'categorie');

        $return1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT    cl.`title`, cl.`link_rewrite`, cc.`categorie`
        FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'` as cc
        LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_categorie` as c
            ON (cc.`categorie` = c.`id_prestablog_categorie`)
        LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_categorie_lang` as cl
            ON (cc.`categorie` = cl.`id_prestablog_categorie`)
        WHERE cc.`news` = '.(int)$news.'
            AND cl.`id_lang` = '.(int)$lang.'
            '.$actif.'
            '.$filtre_groupes.'
        ORDER BY cl.`title`');

        $return2 = array();
        foreach ($return1 as $value) {
            $return2[$value['categorie']]['id_prestablog_categorie'] = (int)$value['categorie'];
            $return2[$value['categorie']]['title'] = $value['title'];
            if ($value['link_rewrite'] != '') {
                $return2[$value['categorie']]['link_rewrite'] = $value['link_rewrite'];
            } else {
                $return2[$value['categorie']]['link_rewrite'] = $value['title'];
            }
        }
        return $return2;
    }

    public static function delAllCategoriesNews($news)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DELETE FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
            WHERE `news`='.(int)$news);
    }

    public static function delAllCorrespondanceNewsAfterDelCat($cat)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            DELETE FROM `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
            WHERE `categorie`='.(int)$cat);
    }

    public static function updateCategoriesNews($categories, $news)
    {
        if (count($categories) > 0) {
            foreach ($categories as $value) {
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
                    INSERT INTO `'.bqSQL(_DB_PREFIX_.self::$table_static).'`
                        (`categorie`, `news`)
                    VALUES ('.(int)$value.', '.(int)$news.')');
            }
        }
    }

    public function registerTablesBdd()
    {
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.$this->table).'` (
            `'.bqSQL($this->identifier).'` int(10) unsigned NOT null auto_increment,
            `categorie` int(10) unsigned NOT null,
            `news` int(10) unsigned NOT null,
            PRIMARY KEY (`'.bqSQL($this->identifier).'`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_correspondancecategorie`
            ADD KEY `categorie` (`categorie`),
            ADD KEY `news` (`news`)')) {
            return false;
        }

        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            INSERT INTO `'.bqSQL(_DB_PREFIX_.$this->table).'`
                (`'.bqSQL($this->identifier).'`, `categorie`, `news`)
            VALUES
                (1, 1, 1)')) {
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

        return true;
    }
}
