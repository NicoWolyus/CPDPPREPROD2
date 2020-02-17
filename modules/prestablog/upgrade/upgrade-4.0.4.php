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

function upgrade_module_4_0_4()
{
    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_antispam`
        ADD KEY `id_shop` (`id_shop`),
        ADD KEY `actif` (`actif`)')) {
        return false;
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_categorie`
        ADD KEY `id_shop` (`id_shop`),
        ADD KEY `actif` (`actif`),
        ADD KEY `parent` (`parent`)')) {
        return false;
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_categorie_group`
        ADD KEY `id_group` (`id_group`),
        ADD KEY `id_prestablog_categorie` (`id_prestablog_categorie`)')) {
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
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_correspondancecategorie`
        ADD KEY `categorie` (`categorie`),
        ADD KEY `news` (`news`)')) {
        return false;
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_news`
        ADD KEY `id_shop` (`id_shop`),
        ADD KEY `actif` (`actif`)')) {
        return false;
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_news_newslink`
        ADD KEY `id_prestablog_news` (`id_prestablog_news`),
        ADD KEY `id_prestablog_newslink` (`id_prestablog_newslink`)')) {
        return false;
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_news_product`
        ADD KEY `id_prestablog_news` (`id_prestablog_news`),
        ADD KEY `id_product` (`id_product`)')) {
        return false;
    }

    if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('
        ALTER TABLE `'.bqSQL(_DB_PREFIX_).'prestablog_subblock`
        ADD KEY `id_shop` (`id_shop`),
        ADD KEY `actif` (`actif`)')) {
        return false;
    }

    Tools::clearCache();

    return true;
}
