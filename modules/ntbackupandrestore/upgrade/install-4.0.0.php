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

function upgrade_module_4_0_0($module)
{
    //We initialize the configuration for all shops
    $shops = Shop::getShops();

    foreach ($shops as $shop) {
        $id_shop = $shop['id_shop'];
        $id_shop_group = $shop['id_shop_group'];

        if (!Configuration::updateValue('IGNORE_FILES_COUNT', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('IGNORE_COMPRESSION', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NTBR_MAINTENANCE', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('SEND_SFTP', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NTBR_AUTOMATION_2NT', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NTBR_AUTOMATION_2NT_HOURS', mt_rand(2, 5), false, $id_shop_group, $id_shop)) {
            return false;
        }
        if (!Configuration::updateValue('NTBR_AUTOMATION_2NT_MINUTES', 0, false, $id_shop_group, $id_shop)) {
            return false;
        }
    }

    return $module;
}
