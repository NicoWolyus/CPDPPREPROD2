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

function upgrade_module_4_1_0($module)
{
    //We initialize the configuration for all shops
    $shops = Shop::getShops();

    foreach ($shops as $shop) {
        $id_shop = $shop['id_shop'];
        $id_shop_group = $shop['id_shop_group'];

        if (!Configuration::updateValue('NTBR_DELETE_LOCAL_BACKUP', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NB_KEEP_BACKUP_FTP', 1, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NB_KEEP_BACKUP_DROPBOX', 1, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NB_KEEP_BACKUP_OWNCLOUD', 1, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NB_KEEP_BACKUP_GOOGLEDRIVE', 1, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NB_KEEP_BACKUP_ONEDRIVE', 1, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NTBR_IGNORE_DIRECTORIES', '', false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NTBR_IGNORE_FILES_TYPES', '', false, $id_shop_group, $id_shop)) {
            return false;
        }
    }

    return $module;
}
