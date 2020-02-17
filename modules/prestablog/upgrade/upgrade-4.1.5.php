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

function upgrade_module_4_1_5()
{
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup` (
            `id_prestablog_popup` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `date_start` datetime NOT NULL,
            `date_stop` datetime NOT NULL,
            `height` int(11) NOT NULL,
            `width` int(11) NOT NULL,
            `delay` int(11) NOT NULL,
            `expire` int(11) NOT NULL,
            `expire_ratio` int(11) NOT NULL,
            `theme` varchar(255) NOT NULL,
            `restriction_rules` int(11) NOT NULL,
            `restriction_pages` text NOT NULL,
            `footer` tinyint(1) NOT NULL DEFAULT \'1\',
            `actif` tinyint(1) NOT NULL DEFAULT \'1\',
            `pop_colorpicker_content` varchar(255),
            `pop_colorpicker_modal` varchar(255),
            `pop_colorpicker_btn` varchar(255),
            `pop_colorpicker_btn_border` varchar(255),
            `pop_opacity_content` varchar(255),
            `pop_opacity_modal` varchar(255),
            `pop_opacity_btn` varchar(255),
            `actif_home` tinyint(1) NOT NULL DEFAULT \'0\',
            PRIMARY KEY (`id_prestablog_popup`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
    return false;
    }
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_lang` (
            `id_prestablog_popup` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_lang` int(10) unsigned NOT NULL ,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_lang`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_shop` (
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            `id_shop` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_shop`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }
        if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_group` (
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            `id_group` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_group`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }

     if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_group` (
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            `id_group` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_group`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_news_popuplink` (
            `id_prestablog_news_popuplink` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_prestablog_news` int(10) unsigned NOT NULL,
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_news_popuplink`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_categorie_popuplink` (
            `id_prestablog_categorie_popuplink` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_prestablog_categorie` int(10) unsigned NOT NULL,
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_categorie_popuplink`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }
    $hook = new Hook();
$prestablog = new Prestablog;
$hook->registerHook($prestablog, 'displayBeforeBodyClosingTag');
    Tools::clearCache();

    return true;
}
