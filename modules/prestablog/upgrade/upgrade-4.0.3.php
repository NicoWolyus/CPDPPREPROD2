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

function upgrade_module_4_0_3()
{
    /* installation de la liaison lookbook dans news */
    if (!Configuration::get('prestablog_nb_car_min_linklb')) {
        Configuration::updateValue('prestablog_nb_car_min_linklb', 2);
    }
    if (!Configuration::get('prestablog_nb_list_linklb')) {
        Configuration::updateValue('prestablog_nb_list_linklb', 5);
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_).'prestablog_news_lookbook` (
        `id_prestablog_news_lookbook` int(10) unsigned NOT null auto_increment,
        `id_prestablog_news` int(10) unsigned NOT null,
        `id_prestablog_lookbook` int(10) unsigned NOT null,
        PRIMARY KEY (`id_prestablog_news_lookbook`))
        ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
        return false;
    }

    Tools::clearCache();

    return true;
}
