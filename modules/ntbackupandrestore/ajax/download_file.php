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

require_once(dirname(__FILE__).'/../autoload.php');

$ntbr = new NtbrChild();
$page = 'download_file';

if (!Module::isInstalled($ntbr->name)) {
    die('Your module is not installed');
}

if (Tools::isSubmit('secure_key')) {
    $secure_key = Tools::getValue('secure_key');
    $id_shop_group = Tools::getValue('id_shop_group');
    $id_shop = Tools::getValue('id_shop');

    $secure_key_test = hash(
        'sha512',
        $secure_key.$ntbr->secure_key.$ntbr->getConfig('NTBR_SEL', $id_shop_group, $id_shop)
    );
    $secure_key_test_temp = hash(
        'sha512',
        $secure_key.$ntbr->secure_key.$ntbr->getConfig('NTBR_SEL_TEMP', $id_shop_group, $id_shop)
    );

    if ($secure_key_test_temp != $ntbr->getConfig('NTBR_HASH_TEMP', $id_shop_group, $id_shop)
        && $secure_key_test != $ntbr->getConfig('NTBR_HASH', $id_shop_group, $id_shop)
    ) {
        sleep(5); /*Limit brute force*/
        die($ntbr->l('Forbidden', $page));
    }
} else {
    die($ntbr->l('Forbidden', $page));
}

if (Tools::isSubmit('backup')) {
    if (!Tools::isSubmit('nb')) {
        die(Tools::jsonEncode(array('result' => false)));
    }
    $old_backups = $ntbr->findOldBackups();
    $nb_file = Tools::getValue('nb');
    $nb_detail = explode('.', $nb_file);
    $backup = '';

    if (!isset($nb_detail[0])) {
        die($ntbr->l('Error', $page));
    }

    if (!isset($old_backups[$nb_detail[0]])) {
        die($ntbr->l('Error', $page));
    }

    // If file is only a part of the backup
    if (isset($nb_detail[1])) {
        if (!isset($old_backups[$nb_detail[0]]['part'][$nb_file]['name'])) {
            die($ntbr->l('Error', $page));
        }

        $backup = $old_backups[$nb_detail[0]]['part'][$nb_file]['name'];
    } else {
        if (!isset($old_backups[$nb_detail[0]]['name'])) {
            die($ntbr->l('Error', $page));
        }

        $backup = $old_backups[$nb_detail[0]]['name'];
    }

    $ntbr->downloadFile(_PS_ROOT_DIR_.'/modules/'.$ntbr->name.'/backup/'.$backup, 'application/x-tar');
} elseif (Tools::isSubmit('log')) {
    $log_file = _PS_ROOT_DIR_.'/modules/'.$ntbr->name.'/backup/log.txt';
    if (file_exists($log_file)) {
        $ntbr->downloadFile($log_file, 'text/plain');
    } else {
        die($ntbr->l('No log file available', $page));
    }
} elseif (Tools::isSubmit('restore')) {
    $ntbr->downloadFile(_PS_ROOT_DIR_.'/modules/'.$ntbr->name.'/restore.txt', 'text/plain', 'restore.php');
} else {
    die($ntbr->l('Error', $page));
}
