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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_1_6()
{
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_).'prestablog_color` (
            `id` int(10) unsigned NOT null auto_increment,
            `menu_color` varchar(255) NOT null DEFAULT \'0\',
            `menu_hover` varchar(255) NOT null DEFAULT \'0\',
            `read_color` varchar(255) NOT null DEFAULT \'0\',
            `hover_color` varchar(255) NOT null DEFAULT \'0\',
            `title_color` varchar(255) NOT null DEFAULT \'0\',
            `text_color` varchar(255) NOT null DEFAULT \'0\',
            `menu_link` varchar(255) NOT null DEFAULT \'0\',
            `link_read` varchar(255) NOT null DEFAULT \'0\',
            `article_title` varchar(255) NOT null DEFAULT \'0\',
            `article_text` varchar(255) NOT null DEFAULT \'0\',
            `block_categories` varchar(255) NOT null DEFAULT \'0\',
            `block_categories_link` varchar(255) NOT null DEFAULT \'0\',
            `block_title` varchar(255) NOT null DEFAULT \'0\',
            `block_btn` varchar(255) NOT null DEFAULT \'0\',
            `categorie_block_background` varchar(255) NOT null DEFAULT \'0\',
            `article_background` varchar(255) NOT null DEFAULT \'0\',
            `article_comment_background` varchar(255) NOT null DEFAULT \'0\',
            `categorie_block_background_hover` varchar(255) NOT null DEFAULT \'0\',
            `block_btn_hover` varchar(255) NOT null DEFAULT \'0\',
            `id_shop` int(10) NOT null DEFAULT \'1\',
            PRIMARY KEY (`id`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
    }
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
    	ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_news`
		ADD COLUMN `average_rating` decimal(10,1) AFTER `url_redirect`')) {
        return false;
    }
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
    	ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_news`
		ADD COLUMN `number_rating` int(10) AFTER `average_rating`')) {
        return false;
    }
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
    	ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_subblock`
		ADD COLUMN `homepage` tinyint(1) NOT null DEFAULT \'0\' AFTER `actif`')) {
        return false;
    }
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
            CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_).'prestablog_rate` (
            `id` int(10) unsigned NOT null auto_increment,
            `id_prestablog_news` int(10) unsigned NOT null,
            `id_session` int(10) unsigned NOT null,
            PRIMARY KEY (`id`))
            ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }
     $hook = new Hook();
$hook->name = 'displayRating';
$hook->title = 'displayRating';
$hook->description = 'displayRating hook';
$hook->add();
$prestablog = new PrestaBlog();
$hook->registerHook($prestablog, 'displayRating');
    Tools::clearCache();

    return true;
}
