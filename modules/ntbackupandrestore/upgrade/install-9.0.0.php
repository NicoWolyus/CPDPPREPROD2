<?php
/**
* 2013-2019 2N Technologies
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@2n-tech.com so we can send you a copy immediately.
*
* @author    2N Technologies <contact@2n-tech.com>
* @copyright 2013-2019 2N Technologies
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_9_0_0($module)
{
    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_ftp`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_dropbox`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_owncloud`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_webdav`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_googledrive`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_onedrive`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_hubic`;')) {
        return false;
    }

    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ntbr_aws`;')) {
        return false;
    }

    $now = date('Y-m-d H:i:s');

    $create_table_ntbr_ftp = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_ftp` (
            `id_ntbr_ftp`       int(10)         unsigned    NOT NULL    auto_increment,
            `active`            tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`              varchar(255)                NOT NULL,
            `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `sftp`              tinyint(1)                  NOT NULL    DEFAULT "0",
            `ssl`               tinyint(1)                  NOT NULL    DEFAULT "0",
            `passive_mode`      tinyint(1)                  NOT NULL    DEFAULT "0",
            `server`            varchar(255)                NOT NULL,
            `login`             varchar(255)                NOT NULL,
            `password`          varchar(255)                NOT NULL,
            `port`              int(10)         unsigned    NOT NULL    DEFAULT "21",
            `directory`         varchar(255)                NOT NULL    DEFAULT "/",
            `date_add`          datetime,
            `date_upd`          datetime,
            PRIMARY KEY (`id_ntbr_ftp`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_ftp) {
        return false;
    }

    $send_ftp           = Configuration::get('SEND_FTP');
    $nb_keep_backup_ftp = Configuration::get('NB_KEEP_BACKUP_FTP');
    $send_sftp          = Configuration::get('SEND_SFTP');
    $ftp_ssl            = Configuration::get('FTP_SSL');
    $ftp_pasv           = Configuration::get('FTP_PASV');
    $ftp_server         = Configuration::get('FTP_SERVER');
    $ftp_login          = Configuration::get('FTP_LOGIN');
    $ftp_pass           = Configuration::get('FTP_PASS');
    $ftp_port           = Configuration::get('FTP_PORT');
    $ftp_dir            = Configuration::get('FTP_DIR');

    if ($ftp_login && $ftp_login != '') {
        $insert_into_table_ntbr_ftp = Db::getInstance()->execute('
            INSERT INTO '._DB_PREFIX_.'ntbr_ftp (`active`, `name`, `nb_backup`, `sftp`, `ssl`, `passive_mode`, `server`, `login`, `password`, `port`, `directory`, `date_add`, `date_upd`)
            VALUES ('.(int)$send_ftp.', "FTP 1", '.(int)$nb_keep_backup_ftp.', '.(int)$send_sftp.', '.(int)$ftp_ssl.', '.(int)$ftp_pasv.', "'.pSQL($ftp_server).'", "'.pSQL($ftp_login).'", "'.pSQL($ftp_pass).'", '.(int)$ftp_port.', "'.pSQL($ftp_dir).'", "'.pSQL($now).'", "'.pSQL($now).'")
        ');

        if (!$insert_into_table_ntbr_ftp) {
            return false;
        }
    }

    $create_table_ntbr_dropbox = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_dropbox` (
            `id_ntbr_dropbox`   int(10)         unsigned    NOT NULL    auto_increment,
            `active`            tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`              varchar(255)                NOT NULL,
            `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `directory`         varchar(255)                NOT NULL    DEFAULT "",
            `token`             text                        NOT NULL,
            `date_add`          datetime,
            `date_upd`          datetime,
            PRIMARY KEY (`id_ntbr_dropbox`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_dropbox) {
        return false;
    }

    $send_dropbox           = Configuration::get('SEND_DROPBOX');
    $nb_keep_backup_dropbox = Configuration::get('NB_KEEP_BACKUP_DROPBOX');
    $dropbox_dir            = Configuration::get('DROPBOX_DIR');
    $dropbox_access_token   = Configuration::get('DROPBOX_ACCESS_TOKEN');

    if ($dropbox_access_token && $dropbox_access_token != '') {
        $insert_into_table_ntbr_dropbox = Db::getInstance()->execute('
            INSERT INTO '._DB_PREFIX_.'ntbr_dropbox (`active`, `name`, `nb_backup`, `directory`, `token`, `date_add`, `date_upd`)
            VALUES ('.(int)$send_dropbox.', "Dropbox 1", '.(int)$nb_keep_backup_dropbox.', "'.pSQL($dropbox_dir).'", "'. pSQL($dropbox_access_token).'", "'.pSQL($now).'", "'.pSQL($now).'")
        ');

        if (!$insert_into_table_ntbr_dropbox) {
            return false;
        }
    }

    $create_table_ntbr_owncloud = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_owncloud` (
            `id_ntbr_owncloud`  int(10)         unsigned    NOT NULL    auto_increment,
            `active`            tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`              varchar(255)                NOT NULL,
            `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `login`             varchar(255)                NOT NULL,
            `password`          varchar(255)                NOT NULL,
            `server`            varchar(255)                NOT NULL,
            `directory`         varchar(255)                NOT NULL    DEFAULT "",
            `date_add`          datetime,
            `date_upd`          datetime,
            PRIMARY KEY (`id_ntbr_owncloud`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_owncloud) {
        return false;
    }

    $send_owncloud              = Configuration::get('SEND_OWNCLOUD');
    $nb_keep_backup_owncloud    = Configuration::get('NB_KEEP_BACKUP_OWNCLOUD');
    $owncloud_user              = Configuration::get('OWNCLOUD_USER');
    $owncloud_pass              = Configuration::get('OWNCLOUD_PASS');
    $owncloud_server            = Configuration::get('OWNCLOUD_SERVER');
    $owncloud_dir               = Configuration::get('OWNCLOUD_DIR');

    if ($owncloud_user && $owncloud_user != '') {
        $insert_into_table_ntbr_owncloud = Db::getInstance()->execute('
            INSERT INTO '._DB_PREFIX_.'ntbr_owncloud (`active`, `name`, `nb_backup`, `login`, `password`, `server`, `directory`, `date_add`, `date_upd`)
            VALUES ('.(int)$send_owncloud.', "ownCloud 1", '.(int)$nb_keep_backup_owncloud.', "'.pSQL($owncloud_user).'", "'. pSQL($owncloud_pass).'", "'. pSQL($owncloud_server).'", "'. pSQL($owncloud_dir).'", "'.pSQL($now).'", "'.pSQL($now).'")
        ');

        if (!$insert_into_table_ntbr_owncloud) {
            return false;
        }
    }

    $create_table_ntbr_webdav = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_webdav` (
            `id_ntbr_webdav`    int(10)         unsigned    NOT NULL    auto_increment,
            `active`            tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`              varchar(255)                NOT NULL,
            `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `login`             varchar(255)                NOT NULL,
            `password`          varchar(255)                NOT NULL,
            `server`            varchar(255)                NOT NULL,
            `directory`         varchar(255)                NOT NULL    DEFAULT "",
            `date_add`          datetime,
            `date_upd`          datetime,
            PRIMARY KEY (`id_ntbr_webdav`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_webdav) {
        return false;
    }

    $create_table_ntbr_googledrive = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_googledrive` (
            `id_ntbr_googledrive`   int(10)         unsigned    NOT NULL    auto_increment,
            `active`                tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`                  varchar(255)                NOT NULL,
            `nb_backup`             int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`        int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`        int(10)         unsigned    NOT NULL    DEFAULT "0",
            `directory_key`         varchar(255)                NOT NULL,
            `directory_path`        varchar(255)                NOT NULL    DEFAULT "",
            `token`                 text                        NOT NULL,
            `date_add`              datetime,
            `date_upd`              datetime,
            PRIMARY KEY (`id_ntbr_googledrive`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_googledrive) {
        return false;
    }

    $send_googledrive           = Configuration::get('SEND_GOOGLEDRIVE');
    $nb_keep_backup_googledrive = Configuration::get('NB_KEEP_BACKUP_GOOGLEDRIVE');
    $googledrive_dir            = Configuration::get('GOOGLEDRIVE_DIR');
    $googledrive_dir_path       = Configuration::get('GOOGLEDRIVE_DIR_PATH');
    $googledrive_access_token   = Configuration::get('GOOGLEDRIVE_ACCESS_TOKEN');

    if ($googledrive_access_token && $googledrive_access_token != '') {
        $insert_into_table_ntbr_googledrive = Db::getInstance()->execute('
            INSERT INTO '._DB_PREFIX_.'ntbr_googledrive (`active`, `name`, `nb_backup`, `directory_key`, `directory_path`, `token`, `date_add`, `date_upd`)
            VALUES ('.(int)$send_googledrive.', "Google Drive 1", '.(int)$nb_keep_backup_googledrive.', "'.pSQL($googledrive_dir).'", "'. pSQL($googledrive_dir_path).'", "'. pSQL($googledrive_access_token).'", "'.pSQL($now).'", "'.pSQL($now).'")
        ');

        if (!$insert_into_table_ntbr_googledrive) {
            return false;
        }
    }

    $create_table_ntbr_onedrive = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_onedrive` (
            `id_ntbr_onedrive`  int(10)         unsigned    NOT NULL    auto_increment,
            `active`            tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`              varchar(255)                NOT NULL,
            `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
            `directory_key`     varchar(255)                NOT NULL,
            `directory_path`    varchar(255)                NOT NULL    DEFAULT "",
            `token`             text                        NOT NULL,
            `date_add`          datetime,
            `date_upd`          datetime,
            PRIMARY KEY (`id_ntbr_onedrive`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_onedrive) {
        return false;
    }

    $send_onedrive              = Configuration::get('SEND_ONEDRIVE');
    $nb_keep_backup_onedrive    = Configuration::get('NB_KEEP_BACKUP_ONEDRIVE');
    $onedrive_dir               = Configuration::get('ONEDRIVE_DIR');
    $onedrive_dir_path          = Configuration::get('ONEDRIVE_DIR_PATH');
    $onedrive_access_token      = Configuration::get('ONEDRIVE_ACCESS_TOKEN');

    if ($onedrive_access_token && $onedrive_access_token != '') {
        $insert_into_table_ntbr_onedrive = Db::getInstance()->execute('
            INSERT INTO '._DB_PREFIX_.'ntbr_onedrive (`active`, `name`, `nb_backup`, `directory_key`, `directory_path`, `token`, `date_add`, `date_upd`)
            VALUES ('.(int)$send_onedrive.', "OneDrive 1", '.(int)$nb_keep_backup_onedrive.', "'.pSQL($onedrive_dir).'", "'. pSQL($onedrive_dir_path).'", "'. pSQL($onedrive_access_token).'", "'.pSQL($now).'", "'.pSQL($now).'")
        ');

        if (!$insert_into_table_ntbr_onedrive) {
            return false;
        }
    }

    $create_table_ntbr_hubic = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_hubic` (
            `id_ntbr_hubic`         int(10)         unsigned    NOT NULL    auto_increment,
            `active`                tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`                  varchar(255)                NOT NULL,
            `nb_backup`             int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`        int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`        int(10)         unsigned    NOT NULL    DEFAULT "0",
            `directory`             varchar(255)                NOT NULL    DEFAULT "",
            `token`                 text                        NOT NULL,
            `credential`            text                        NOT NULL,
            `date_add`              datetime,
            `date_upd`              datetime,
            PRIMARY KEY (`id_ntbr_hubic`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_hubic) {
        return false;
    }

    $create_table_ntbr_aws = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_aws` (
            `id_ntbr_aws`   		int(10)         unsigned    NOT NULL    auto_increment,
            `active`           		tinyint(1)                  NOT NULL    DEFAULT "0",
            `name`              	varchar(255)                NOT NULL,
            `nb_backup`         	int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_file`    	int(10)         unsigned    NOT NULL    DEFAULT "0",
            `nb_backup_base`    	int(10)         unsigned    NOT NULL    DEFAULT "0",
            `access_key_id`         varchar(255)                NOT NULL    DEFAULT "",
            `secret_access_key`     varchar(255)                NOT NULL    DEFAULT "",
            `region`                varchar(255)                NOT NULL    DEFAULT "",
            `bucket`                varchar(255)                NOT NULL    DEFAULT "",
            `directory_key`         varchar(255)                NOT NULL    DEFAULT "",
            `directory_path`        varchar(255)                NOT NULL    DEFAULT "",
            `date_add`          	datetime,
            `date_upd`          	datetime,
            PRIMARY KEY (`id_ntbr_aws`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_aws) {
        return false;
    }

    $create_table_ntbr_comments = Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ntbr_comments` (
            `id_ntbr_comments`      int(10)         unsigned    NOT NULL    auto_increment,
            `backup_name`           varchar(255)                NOT NULL    DEFAULT "",
            `comment`              	text                        NOT NULL,
            `date_add`          	datetime,
            `date_upd`          	datetime,
            PRIMARY KEY (`id_ntbr_comments`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;
    ');

    if (!$create_table_ntbr_comments) {
        return false;
    }

    $shops = Shop::getShops();

    // Update string crypt with mcrypt by string crypt by openssl
    foreach ($shops as $shop) {
        if (!Configuration::updateValue('NB_KEEP_BACKUP_FILE', 1, false, $shop['id_shop_group'], $shop['id_shop'])) {
            return false;
        }

        if (!Configuration::updateValue('NB_KEEP_BACKUP_BASE', 1, false, $shop['id_shop_group'], $shop['id_shop'])) {
            return false;
        }
    }

    return $module;
}
