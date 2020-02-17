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

function upgrade_module_2_1_0($module)
{
    //We initialize the configuration for all shops
    $shops = Shop::getShops();

    foreach ($shops as $shop) {
        if (!Configuration::updateValue('MAIL_BACKUP', Configuration::get('PS_SHOP_EMAIL'), false, $shop['id_shop_group'], $shop['id_shop'])) {
            return false;
        }
    }

    // Create file with all the varibles need for crons
    $physic_path_modules = realpath(_PS_ROOT_DIR_.'/modules').'/';
    $shop_domain = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost();
    $url_modules = $shop_domain.__PS_BASE_URI__.'modules/';
    $url_ajax = $url_modules.$module->name.'/ajax';
    $physic_path_ajax = $physic_path_modules.$module->name.'/ajax';
    $param_secure_key = 'secure_key='.$module->secure_key;

    $redirect_cron = array(
        'backup',
        'backupfilesonly',
        'backupdatabaseonly'
    );

    foreach ($redirect_cron as $cron) {
        if (!file_exists($physic_path_ajax.'/'.$cron.'_'.$module->secure_key.'.php')) {
            $file = fopen($physic_path_ajax.'/'.$cron.'_'.$module->secure_key.'.php', 'w+');
            fwrite($file, '<?php header("Location: '.$url_ajax.'/'.$cron.'.php?'.$param_secure_key.'"); exit();');
            fclose($file);
        }
    }

    return $module;
}
