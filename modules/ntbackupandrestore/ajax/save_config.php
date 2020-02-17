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
$page = 'save_config';

/*d($ntbr->secure_key);*/

if (!Tools::isSubmit('secure_key') || Tools::getValue('secure_key') != $ntbr->secure_key) {
    die($ntbr->l('Your secure key is unvalid', $page));
}

if (!Module::isInstalled($ntbr->name)) {
    die('Your module is not installed');
}

$result = false;
$errors = array();

if (Tools::isSubmit('activate_log')
    && Tools::isSubmit('nb_keep_backup')
    && Tools::isSubmit('nb_keep_backup_file')
    && Tools::isSubmit('nb_keep_backup_base')
    && Tools::isSubmit('ignore_directories')
    && Tools::isSubmit('ignore_files_types')
    && Tools::isSubmit('ignore_tables')
    && Tools::isSubmit('mail_backup')
    && Tools::isSubmit('dump_low_interest_table')
    && Tools::isSubmit('disable_refresh')
    && Tools::isSubmit('disable_server_timeout')
    && Tools::isSubmit('increase_server_memory')
    && Tools::isSubmit('increase_server_memory_value')
    && Tools::isSubmit('activate_xsendfile')
    && Tools::isSubmit('send_email')
    && Tools::isSubmit('email_only_error')
    && Tools::isSubmit('send_restore')
    && Tools::isSubmit('ignore_product_image')
    && Tools::isSubmit('ignore_files_count')
    && Tools::isSubmit('ignore_compression')
    && Tools::isSubmit('maintenance')
    && Tools::isSubmit('delete_local_backup')
    && Tools::isSubmit('encrypt_backup')
    && Tools::isSubmit('part_size')
    && Tools::isSubmit('max_file_to_backup')
    && Tools::isSubmit('time_between_backups')
    && Tools::isSubmit('time_between_refresh')
    && Tools::isSubmit('time_pause_between_refresh')
    && Tools::isSubmit('time_between_progress_refresh')
) {
    $activate_log                   = Tools::getValue('activate_log');
    $nb_keep_backup                 = Tools::getValue('nb_keep_backup');
    $nb_keep_backup_file            = Tools::getValue('nb_keep_backup_file');
    $nb_keep_backup_base            = Tools::getValue('nb_keep_backup_base');
    $ignore_directories             = Tools::getValue('ignore_directories');
    $ignore_files_types             = Tools::getValue('ignore_files_types');
    $ignore_tables                  = Tools::getValue('ignore_tables');
    $mail_backup                    = Tools::getValue('mail_backup');
    $dump_low_interest_table        = Tools::getValue('dump_low_interest_table');
    $disable_refresh                = Tools::getValue('disable_refresh');
    $disable_server_timeout         = Tools::getValue('disable_server_timeout');
    $increase_server_memory         = Tools::getValue('increase_server_memory');
    $increase_server_memory_value   = Tools::getValue('increase_server_memory_value');
    $activate_xsendfile             = Tools::getValue('activate_xsendfile');
    $send_email                     = Tools::getValue('send_email');
    $email_only_error               = Tools::getValue('email_only_error');
    $send_restore                   = Tools::getValue('send_restore');
    $ignore_product_image           = Tools::getValue('ignore_product_image');
    $ignore_files_count             = Tools::getValue('ignore_files_count');
    $ignore_compression             = Tools::getValue('ignore_compression');
    $maintenance                    = Tools::getValue('maintenance');
    $delete_local_backup            = Tools::getValue('delete_local_backup');
    $encrypt_backup                 = Tools::getValue('encrypt_backup');
    $part_size                      = Tools::getValue('part_size');
    $max_file_to_backup             = Tools::getValue('max_file_to_backup');
    $time_between_backups           = Tools::getValue('time_between_backups');
    $time_between_refresh           = Tools::getValue('time_between_refresh');
    $time_pause_between_refresh     = Tools::getValue('time_pause_between_refresh');
    $time_between_progress_refresh  = Tools::getValue('time_between_progress_refresh');
    $id_shop_group                  = Tools::getValue('id_shop_group');
    $id_shop                        = Tools::getValue('id_shop');

    $ntbr->setConfig('ACTIVATE_LOG', (int)(bool)$activate_log, $id_shop_group, $id_shop);
    $ntbr->setConfig('NB_KEEP_BACKUP', (int)$nb_keep_backup, $id_shop_group, $id_shop);
    $ntbr->setConfig('NB_KEEP_BACKUP_FILE', (int)$nb_keep_backup_file, $id_shop_group, $id_shop);
    $ntbr->setConfig('NB_KEEP_BACKUP_BASE', (int)$nb_keep_backup_base, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_IGNORE_DIRECTORIES', $ignore_directories, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_IGNORE_FILES_TYPES', $ignore_files_types, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_IGNORE_TABLES', $ignore_tables, $id_shop_group, $id_shop);
    $ntbr->setConfig('MAIL_BACKUP', $mail_backup, $id_shop_group, $id_shop);
    $ntbr->setConfig('DUMP_LOW_INTEREST_TABLES', (int)(bool)$dump_low_interest_table, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_DISABLE_REFRESH', (int)(bool)$disable_refresh, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_DISABLE_SERVER_TIMEOUT', (int)(bool)$disable_server_timeout, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_INCREASE_SERVER_MEMORY', (int)(bool)$increase_server_memory, $id_shop_group, $id_shop);
    $ntbr->setConfig('IGNORE_PRODUCT_IMAGE', (int)(bool)$ignore_product_image, $id_shop_group, $id_shop);
    $ntbr->setConfig('IGNORE_FILES_COUNT', (int)(bool)$ignore_files_count, $id_shop_group, $id_shop);
    $ntbr->setConfig('IGNORE_COMPRESSION', (int)(bool)$ignore_compression, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_MAINTENANCE', (int)(bool)$maintenance, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_DELETE_LOCAL_BACKUP', (int)(bool)$delete_local_backup, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_ENCRYPT_BACKUP', (int)(bool)$encrypt_backup, $id_shop_group, $id_shop);

    if ($part_size) {
        if ($part_size != (int)$part_size) {
            $errors[] = $ntbr->l('The size max for your backup files must be in integers.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }
    }

    if ($max_file_to_backup) {
        if ($max_file_to_backup != (int)$max_file_to_backup) {
            $errors[] = $ntbr->l('The size max for the files to backup must be in integers.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }
    }

    if ($time_between_backups) {
        if ($time_between_backups != (int)$time_between_backups) {
            $errors[] = $ntbr->l('The security duration between your backups must be a whole number.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }
    }

    if ($time_between_refresh) {
        if ($time_between_refresh != (int)$time_between_refresh) {
            $errors[] = $ntbr->l('The duration of intermedial renewal must be a whole number.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }
    }

    if ($time_pause_between_refresh) {
        if ($time_pause_between_refresh != (int)$time_pause_between_refresh) {
            $errors[] = $ntbr->l('The duration of the pause between two intermediate renewal must be a whole number.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }

        if ($time_pause_between_refresh >= $time_between_refresh) {
            $errors[] = $ntbr->l('The duration of the pause between two intermediate renewal must inferior to the intermedial renewal value.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }
    }

    if ($time_between_progress_refresh) {
        if ($time_between_progress_refresh != (int)$time_between_progress_refresh) {
            $errors[] = $ntbr->l('The duration of progress refresh must be a whole number.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }

        if (!$time_between_progress_refresh) {
            $time_between_progress_refresh = 1;
        }
    }

    if ($increase_server_memory_value) {
        if ($increase_server_memory_value != (int)$increase_server_memory_value) {
            $errors[] = $ntbr->l('The new server limit must be a whole number.', $page);
            die(Tools::jsonEncode(array('result' => false, 'errors' => $errors)));
        }
    }

    $ntbr->setConfig('NTBR_PART_SIZE', (int)$part_size, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_MAX_FILE_TO_BACKUP', (int)$max_file_to_backup, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_TIME_BETWEEN_BACKUPS', (int)$time_between_backups, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_TIME_BETWEEN_REFRESH', (int)$time_between_refresh, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_TIME_PAUSE_BETWEEN_REFRESH', (int)$time_pause_between_refresh, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_TIME_BETWEEN_PROGRESS_REFRESH', (int)$time_between_progress_refresh, $id_shop_group, $id_shop);
    $ntbr->setConfig('NTBR_SERVER_MEMORY_VALUE', (int)$increase_server_memory_value, $id_shop_group, $id_shop);
    $ntbr->setConfig('ACTIVATE_XSENDFILE', (int)(bool)$activate_xsendfile, $id_shop_group, $id_shop);
    $ntbr->setConfig('SEND_EMAIL', (int)(bool)$send_email, $id_shop_group, $id_shop);
    $ntbr->setConfig('EMAIL_ONLY_ERROR', (int)(bool)$email_only_error, $id_shop_group, $id_shop);
    $ntbr->setConfig('SEND_RESTORE', (int)(bool)$send_restore, $id_shop_group, $id_shop);

    if ($ntbr->getConfig('ACTIVATE_LOG', $id_shop_group, $id_shop) == $activate_log
        && $ntbr->getConfig('NB_KEEP_BACKUP', $id_shop_group, $id_shop) == $nb_keep_backup
        && $ntbr->getConfig('NB_KEEP_BACKUP_FILE', $id_shop_group, $id_shop) == $nb_keep_backup_file
        && $ntbr->getConfig('NB_KEEP_BACKUP_BASE', $id_shop_group, $id_shop) == $nb_keep_backup_base
        && $ntbr->getConfig('NTBR_IGNORE_DIRECTORIES', $id_shop_group, $id_shop) == $ignore_directories
        && $ntbr->getConfig('NTBR_IGNORE_FILES_TYPES', $id_shop_group, $id_shop) == $ignore_files_types
        && $ntbr->getConfig('NTBR_IGNORE_TABLES', $id_shop_group, $id_shop) == $ignore_tables
        && $ntbr->getConfig('MAIL_BACKUP', $id_shop_group, $id_shop) == $mail_backup
        && $ntbr->getConfig('DUMP_LOW_INTEREST_TABLES', $id_shop_group, $id_shop) == $dump_low_interest_table
        && $ntbr->getConfig('NTBR_DISABLE_REFRESH', $id_shop_group, $id_shop) == $disable_refresh
        && $ntbr->getConfig('NTBR_DISABLE_SERVER_TIMEOUT', $id_shop_group, $id_shop) == $disable_server_timeout
        && $ntbr->getConfig('NTBR_INCREASE_SERVER_MEMORY', $id_shop_group, $id_shop) == $increase_server_memory
        && $ntbr->getConfig('NTBR_SERVER_MEMORY_VALUE', $id_shop_group, $id_shop) == $increase_server_memory_value
        && $ntbr->getConfig('ACTIVATE_XSENDFILE', $id_shop_group, $id_shop) == $activate_xsendfile
        && $ntbr->getConfig('SEND_EMAIL', $id_shop_group, $id_shop) == $send_email
        && $ntbr->getConfig('EMAIL_ONLY_ERROR', $id_shop_group, $id_shop) == $email_only_error
        && $ntbr->getConfig('IGNORE_PRODUCT_IMAGE', $id_shop_group, $id_shop) == $ignore_product_image
        && $ntbr->getConfig('IGNORE_FILES_COUNT', $id_shop_group, $id_shop) == $ignore_files_count
        && $ntbr->getConfig('IGNORE_COMPRESSION', $id_shop_group, $id_shop) == $ignore_compression
        && $ntbr->getConfig('NTBR_MAINTENANCE', $id_shop_group, $id_shop) == $maintenance
        && $ntbr->getConfig('NTBR_DELETE_LOCAL_BACKUP', $id_shop_group, $id_shop) == $delete_local_backup
        && $ntbr->getConfig('NTBR_ENCRYPT_BACKUP', $id_shop_group, $id_shop) == $encrypt_backup
        && $ntbr->getConfig('NTBR_PART_SIZE', $id_shop_group, $id_shop) == $part_size
        && $ntbr->getConfig('NTBR_MAX_FILE_TO_BACKUP', $id_shop_group, $id_shop) == $max_file_to_backup
        && $ntbr->getConfig('NTBR_TIME_BETWEEN_BACKUPS', $id_shop_group, $id_shop) == $time_between_backups
        && $ntbr->getConfig('NTBR_TIME_BETWEEN_REFRESH', $id_shop_group, $id_shop) == $time_between_refresh
        && $ntbr->getConfig('NTBR_TIME_PAUSE_BETWEEN_REFRESH', $id_shop_group, $id_shop) == $time_pause_between_refresh
        && $ntbr->getConfig('NTBR_TIME_BETWEEN_PROGRESS_REFRESH', $id_shop_group, $id_shop) == $time_between_progress_refresh
        && $ntbr->getConfig('SEND_RESTORE', $id_shop_group, $id_shop) == $send_restore
    ) {
        $result = true;
    } else {
        $errors[] = $ntbr->l('Error during the saving of your configuration.', $page);
    }
}

die(Tools::jsonEncode(array('result' => $result, 'errors' => $errors)));
