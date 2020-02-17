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

require_once(dirname(__FILE__).'/../ntbackupandrestore.php');

abstract class NtbrCore extends NtBackupAndRestore
{
    /* Data type */
    const DATA_BOOL = 0;
    const DATA_CHAR = 1;
    const DATA_OTHER = 2;
    const DATA_INT = 3;
    const DATA_BLOB = 4;

    const CLE_CRYPTAGE = 'D_T+rW*H`0b84ra.YIen(X|>_Ot&|va;9odG:Gkk3meU=y5kBf3}Yuim'; // The cryptage key
    const CIPHER_CRYPTAGE = 'aes-256-cbc'; // The cipher
    const GOOGLEDRIVE_ROOT_ID = 'root'; // Google Drive root ID.
    const STOP_FILE = 'stop.txt';
    const DUMP_LINES_LIMIT = 25000; // Max line number for each database access during dump. Higher number means higher memory use
    const DUMP_MAX_VALUES = 100; // Max values line per dump line. Lower number means more disk access
    const DUMP_MAX_LINE_WRITE = 500; // Max line to keep in memory before writing dump. Lower number means more disk access
    const FILE_MAX_LINE_WRITE = 500; // Max line to keep in memory before writing the file's file. Lower number means more disk access
    const MAX_LINE_BEFORE_ADD_TO_TAR = 500; // Max line to keep in memory before adding them to the tar. Lower number means more disk access
    const MIN_TIME_NEW_BACKUP = 600; // 10 minutes
    const MAX_TIME_BEFORE_REFRESH = 25; // in seconds
    const MAX_TIME_BEFORE_PROGRESS_REFRESH = 1; // in seconds
    const BACKUP_FOLDER = 'backup';
    const LOG_DATE_FORMAT = 'd/m/Y H:i:s';
    const MAX_FILE_UPLOAD_SIZE = 10485760; // 10 Mo (10 * 1024 * 1024 = 10 485 760)
    const MIN_TIME_BEFORE_REFRESH = 1; // in seconds
    const SET_TIME_LIMIT = 6000;
    const SET_MEMORY_LIMIT = 128;
    const MAX_SEEK_SIZE = 2147483646; // The max size to seek in a file is just under 2GB
    const MAX_READ_SIZE = 100663296; // The max size to read in a file is about 96 MB
    const URL_SERVICE_IP_EXTERNE = 'http://rkx.fr/ip.php';
    const PERM_DIR = '0755';
    const PERM_FILE = '0644';
    const IPV4_NTCRON = '188.165.241.158';
    const IPV6_NTCRON = '2001:41d0:2:bc9e::';
    const NEW_RESTORE_NAME = 'restore.php';
    const FTP_TIMEOUT = 5;
    const URL_VERSION = 'https://version.2n-tech.com/ntbr.txt';
    const STEP_DUMP_GET_TABLES = 1;
    const STEP_DUMP_GET_VALUES = 2;
    const STEP_DUMP_GET_VALUES_CONTINUE = 3;
    const STEP_LIST_FILES = 4;
    const STEP_LIST_FILES_CONTINUE = 5;
    const STEP_BACKUP_FILES = 6;
    const STEP_COMPRESS = 7;
    const STEP_COMPRESS_CONTINUE = 8;
    const STEP_SEND_AWAY = 9;
    protected $step_send = array(
        'ftp'                   => 10,
        'ftp_resume'            => 11,
        'dropbox'               => 12,
        'dropbox_resume'        => 13,
        'owncloud'              => 14,
        'owncloud_resume'       => 15,
        'webdav'                => 16,
        'webdav_resume'         => 17,
        'googledrive'           => 18,
        'googledrive_resume'    => 19,
        'onedrive'              => 20,
        'onedrive_resume'       => 21,
        'hubic'                 => 22,
        'hubic_resume'          => 23,
        'aws'                   => 24,
        'aws_resume'            => 25,
    );
    const STEP_FINISH = 26;

    protected $typeint_mysql = array('UNSIGNED',
        'TINYINT',
        'BIT',
        'BOOL',
        'BOOLEAN',
        'SMALLINT',
        'MEDIUMINT',
        'INT',
        'INTEGER',
        'BIGINT',
        'FLOAT',
        'DOUBLE',
        'DOUBLE PRECISION',
        'REAL',
        'DECIMAL',
        'DEC',
        'NUMERIC',
        'FIXED'
    );
    protected $typeblob_mysql = array('TINYBLOB',
        'BLOB',
        'MEDIUMBLOB',
        'LONGBLOB'
    );

    public $errors = array();
    public $type_backup_complete    = 'complete';
    public $type_backup_file        = 'file';
    public $type_backup_base        = 'dump';
    protected $suffix_backup          = '.complete';
    protected $suffix_backup_file     = '.file';
    protected $suffix_backup_base     = '.dump';
    protected $ftp_account_id         = 0;
    protected $dropbox_account_id     = 0;
    protected $owncloud_account_id    = 0;
    protected $webdav_account_id      = 0;
    protected $googledrive_account_id = 0;
    protected $onedrive_account_id    = 0;
    protected $hubic_account_id       = 0;
    protected $aws_account_id         = 0;
    protected $pause_refresh          = 1;
    public $resume_ingoing = 0;
    public $log_file;
    protected $lastlog_file;
    protected $list_files_file;
    protected $handle_file_list_file;
    protected $handle_tar_file;
    protected $handle_list_dir_file;
    protected $handle_gz_file;
    protected $handle_bz_file;
    protected $backup_folder;
    protected $dump_file;
    protected $tar_file;
    protected $tar_files_size;
    protected $compressed_file;
    protected $bzip2;
    protected $id_shop;
    protected $id_shop_group;
    protected $date_format;
    protected $hour_format;
    protected $date_start;
    protected $hour_start;
    protected $total_files;
    protected $files_done;
    protected $old_percent;
    protected $base_length;
    protected $backup_name;
    protected $backup_name_date;
    protected $norm_backup_file;
    protected $norm_tar_file;
    protected $norm_compressed_file;
    protected $norm_log_file;
    protected $norm_lastlog_file;
    protected $source_dir;
    protected $part_file;
    protected $part_size;
    protected $part_number;
    public $part_list;
    protected $total_nb_part;
    protected $total_size;
    protected $ps_shop_enable;
    protected $next_step;
    protected $cron;
    protected $total_time;
    public $module_path;
    public $module_path_physic;
    public $warnings;
    public $refresh;
    public $tar_time;
    protected $list_dir;
    protected $list_dir_file;
    protected $num_file_to_compress;
    protected $config_file;
    protected $dump_tables;
    protected $dump_percent_lines;
    protected $dump_total_lines;
    protected $dump_table_total_lines;
    protected $dump_table_total_lines_done;
    protected $dump_total_tables_done;
    protected $dump_tables_to_ignore;
    protected $compress_size_done;
    protected $compress_total_size;
    protected $compress_tar_position;
    protected $list_files_to_add;
    protected $array_files_to_add;
    protected $nb_file_in_list_to_add;
    protected $position_list_file;
    protected $restore_file;
    public $dropbox_upload_id;
    public $dropbox_position;
    protected $dropbox_dir;
    protected $dropbox_nb_part;
    public $onedrive_session;
    public $onedrive_position;
    protected $onedrive_nb_part;
    public $owncloud_session;
    public $owncloud_position;
    public $owncloud_nb_part;
    public $owncloud_nb_chunk;
    public $webdav_session;
    public $webdav_position;
    public $webdav_nb_part;
    public $webdav_nb_chunk;
    protected $ftp_dir;
    protected $ftp_nb_part;
    protected $ftp_position;
    public $googledrive_session;
    public $googledrive_position;
    public $googledrive_mime_type;
    protected $googledrive_nb_part;
    protected $hubic_nb_part;
    public $hubic_nb_chunk;
    public $hubic_position;
    protected $hubic_dir;
    public $aws_nb_part;
    public $aws_upload_id;
    public $aws_upload_part;
    public $aws_position;
    public $aws_etag;

    abstract protected function ignoreProductImage($current_normalized_file, $filename);
    abstract protected function getBackupTotalSize();
    abstract protected function deleteLocalBackup();
    abstract protected function initForSFTP();
    abstract protected function connectToDropbox($access_token = '');
    abstract protected function connectToOwncloud($server, $user, $pass);
    abstract protected function connectToWebdav($url, $user, $pass);
    abstract protected function connectToAws($aws_id_key, $aws_key, $aws_region, $aws_bucket);
    abstract protected function connectToOpenstack($access_token, $end_point, $account_type);
    abstract protected function connectToGoogledrive($access_token = '');
    abstract protected function connectToOnedrive($access_token = '', $id_ntbr_onedrive = 0);
    abstract protected function connectToHubic($id_hubic_account = '0');
    abstract protected function testDropboxConnection($token);
    abstract protected function testOwncloudConnection($server, $user, $pass);
    abstract protected function testWebdavConnection($url, $user, $pass);
    abstract protected function testGoogledriveConnection($token);
    abstract protected function testOnedriveConnection($token, $id_ntbr_onedrive);
    abstract protected function testHubicConnection($id_hubic_account);
    abstract protected function testAwsConnection($aws_id_key, $aws_key, $aws_region, $aws_bucket);
    abstract protected function testFTP($ftp_server, $ftp_login, $ftp_pass, $ftp_port, $ssl = false, $pasv = false);
    abstract protected function testSFTP($ftp_server, $ftp_login, $ftp_pass, $ftp_port);
    abstract protected function sendFileToDropbox();
    abstract protected function sendFileToFTP();
    abstract protected function sendFileToSFTP();
    abstract protected function sendFileToOnedrive();
    abstract protected function sendFileToOwncloud();
    abstract protected function sendFileToWebdav();
    abstract protected function sendFileToGoogledrive();
    abstract protected function sendFileToAws();
    abstract protected function deleteDropboxOldBackup($access_token, $dropbox_dir);
    abstract protected function deleteOwncloudOldBackup($owncloud_lib, $owncloud_dir);
    abstract protected function deleteWebdavOldBackup($webdav_lib, $webdav_dir);
    abstract protected function deleteGoogledriveOldBackup($googledrive_lib, $googledrive_dir);
    abstract protected function deleteHubicOldBackup($hubic_lib);
    abstract protected function deleteOnedriveOldBackup($onedrive_lib, $id_directory);
    abstract protected function deleteAwsOldBackup($aws_lib);
    abstract protected function deleteFTPOldBackup($connection);
    abstract protected function deleteSFTPOldBackup($sftp_lib, $ftp_dir);
    abstract protected function getDropboxAccessToken($dropbox_code);
    abstract protected function getGoogledriveAccessToken($googledrive_code);
    abstract protected function getOnedriveAccessToken($onedrive_code);
    abstract protected function getHubicAccessToken($hubic_code);
    abstract protected function getGoogledriveRefreshToken($refresh_token);
    abstract protected function getOnedriveRefreshToken($refresh_token);
    abstract protected function getHubicRefreshToken($refresh_token);
    abstract protected function getOnedriveAccessTokenUrl($onedrive_lib);
    abstract protected function closeSFTP($sftp_lib);
    abstract protected function getGoogledriveTree($googledrive_dir);
    abstract protected function getGoogledriveTreeChildren($access_token, $id_parent, $googledrive_dir, $level, $parent_path);
    abstract protected function getOnedriveTree($access_token, $onedrive_dir, $id_ntbr_onedrive);
    abstract protected function getOnedriveTreeChildren($access_token, $onedrive_dir, $id_parent, $level, $parent_path, $id_ntbr_onedrive);
    abstract protected function getAwsTree($id_ntbr_aws);
    abstract protected function getAwsTreeChildren($id_parent, $level, $parent_path, $id_ntbr_aws);
    abstract protected function sendBackupAway();

    public function __construct()
    {
        parent::__construct();
        $this->setNames();
    }

    /**
     * Set default files and folders names
     *
     * @param   array   $suffix     Suffix to add to the backup name.
     */
    protected function setNames($suffix = '')
    {
        $date_format_lite = $this->context->language->date_format_lite;
        $this->date_format = $date_format_lite ? $date_format_lite : 'Y/m/d';
        $this->hour_format = 'H:i:s';
        $this->date_start = date($this->date_format);
        $this->hour_start = date($this->hour_format);

        $shop_domain = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost();
        $url_modules = $shop_domain.__PS_BASE_URI__.'modules'.DIRECTORY_SEPARATOR;
        $physic_path_modules = realpath(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'modules').DIRECTORY_SEPARATOR;
        $this->module_path = $url_modules.$this->name.DIRECTORY_SEPARATOR;
        $this->module_path_physic = $physic_path_modules.$this->name.DIRECTORY_SEPARATOR;

        //$module_dir = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->name.DIRECTORY_SEPARATOR;
        $this->backup_folder = $this->module_path_physic.self::BACKUP_FOLDER.DIRECTORY_SEPARATOR;
        $this->id_shop = (int)Configuration::get('PS_SHOP_DEFAULT');
        $this->id_shop_group = Shop::getGroupFromShop($this->id_shop);

        $this->backup_name = $this->correctFileName($this->getConfig('PS_SHOP_NAME').$suffix);
        //Check if backup name won't make problems later.
        //Backup name should not be in other files name present in backup folder
        if (strpos('dump.sql', $this->backup_name) !== false
            || strpos('.tar', $this->backup_name) !== false
            || strpos('.bz2', $this->backup_name) !== false
            || strpos('.gz', $this->backup_name) !== false
            || strpos('log.txt', $this->backup_name) !== false
            || strpos('lastlog.txt', $this->backup_name) !== false
            || strpos('.htaccess', $this->backup_name) !== false
            || strpos('index.php', $this->backup_name) !== false) {
            $this->backup_name = 'backup0'.$suffix;
        }

        $this->backup_name_date = $this->correctFileName($this->backup_name.'.'.date('Ymd').'.'.date('His'));
        $this->dump_file = $this->backup_folder.'dump.sql';
        $this->tar_file = $this->backup_folder.$this->backup_name_date.'.tar';

        // bz2 files can only be open with "a" or "w" mode. Refresh need "a".
        /*if ($this->getConfig('NTBR_DISABLE_REFRESH')) {
            $this->bzip2 = extension_loaded('bz2');
        } else {
            $this->bzip2 = false;
        }*/

        $this->bzip2 = false;

        if ($this->bzip2) {
            $this->compressed_file = $this->tar_file.'.bz2';
        } else {
            $this->compressed_file = $this->tar_file.'.gz';
        }

        $this->part_size = $this->getConfig('NTBR_PART_SIZE')*1024*1024;
        $this->part_file = $this->backup_folder.$this->backup_name_date;
        $this->part_number = 1;
        $this->total_size = 0;
        $this->part_list = array($this->tar_file);
        $this->refresh = false;
        $this->cron = false;
        $this->ps_shop_enable = array();
        $this->next_step = 0;

        $this->log_file = $this->backup_folder.'log.txt';
        $this->lastlog_file = $this->backup_folder.'lastlog.txt';
        $this->list_files_file = $this->backup_folder.'list_files.txt';
        $this->list_dir_file = $this->backup_folder.'list_directories.txt';
        $this->config_file = $this->backup_folder.'config.txt';
        $this->num_file_to_compress = 1;
        $this->restore_file = $this->module_path_physic.'restore.txt';
    }

    /**
     * Set IP in maintenance mode
     */
    public function setMaintenanceIP()
    {
        //Find IP
        $ip = $_SERVER['REMOTE_ADDR'];

        /*if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }*/

        //Is IP already in the list ?
        $shops = Shop::getShops();

        //$this->log($shops);

        foreach ($shops as $shop) {
            $ip_list = $this->getConfig('PS_MAINTENANCE_IP', $shop['id_shop_group'], $shop['id_shop']);
            $array_ip_list = ($ip_list)?explode(',', $ip_list):array();

            if (!in_array($ip, $array_ip_list)) {
                $array_ip_list[] = $ip;
            }

            if (!in_array(self::IPV4_NTCRON, $array_ip_list)) {
                $array_ip_list[] = self::IPV4_NTCRON;
            }

            if (!in_array(self::IPV6_NTCRON, $array_ip_list)) {
                $array_ip_list[] = self::IPV6_NTCRON;
            }

            //We need to add IP
            $new_list = implode(',', $array_ip_list);
            $this->setConfig('PS_MAINTENANCE_IP', $new_list, $shop['id_shop_group'], $shop['id_shop']);
        }
    }

    /**
     * Set the maintenance on the shop
     */
    protected function setMaintenance()
    {
        if ($this->getConfig('NTBR_MAINTENANCE')) {
            $this->log($this->l('Put the shop in maintenance'));
            $shops = Shop::getShops();

            foreach ($shops as $shop) {
                $ps_shop_enable = (int)$this->getConfig('PS_SHOP_ENABLE', $shop['id_shop_group'], $shop['id_shop']);
                $this->ps_shop_enable[$shop['id_shop_group']][$shop['id_shop']] = $ps_shop_enable;
                $this->setConfig('PS_SHOP_ENABLE', 0, $shop['id_shop_group'], $shop['id_shop']);
            }
        }
    }

    /**
     * Reset the maintenance to its original value
     */
    protected function resetMaintenance($error = false)
    {
        if ($this->getConfig('NTBR_MAINTENANCE')) {
            if (!$error) {
                $this->log($this->l('Remove the maintenance'));
            }
            $shops = Shop::getShops();

            foreach ($shops as $shop) {
                if (isset($this->ps_shop_enable[$shop['id_shop_group']][$shop['id_shop']])) {
                    $ps_shop_enable = (int)$this->ps_shop_enable[$shop['id_shop_group']][$shop['id_shop']];
                    $this->setConfig('PS_SHOP_ENABLE', $ps_shop_enable, $shop['id_shop_group'], $shop['id_shop']);
                }
            }
        }
    }

    /**
     * Action to do in case of timeout detected
     */
    public function shutdown()
    {
        $error = error_get_last();
        $connection_status = connection_status(); //http://php.net/manual/fr/features.connection-handling.php
        if (isset($error['type']) && $error['type'] === E_ERROR && ($connection_status == 2 || $connection_status == 3)) {
            $this->refreshBackup();
            $time = time() - $this->total_time;
            $this->log('ERR'.sprintf($this->l('Maximum runtime of your server reached (%d s). Please increase this time on your server for the backup to complete. Most of the time, you need to increase PHP max_execution_time. If you do not automate the backup, you can enable the "Intermediate renewal" option to bypass this limitation.'), $time));
        }
    }

    /**
     * End backup with error
     */
    public function endWithError()
    {
        // Reset to previous maintenance setup
        $this->resetMaintenance(true);

        return false;
    }

    /**
     * backup database and files
     *
     * @return bool
     */
    public function backup($refresh = false, $cron = false, $step = false, $backup_name = false, $part_list = array())
    {
        if ($this->getConfig('NTBR_DISABLE_SERVER_TIMEOUT')) {
            set_time_limit(self::SET_TIME_LIMIT);
        }

        if ($this->getConfig('NTBR_INCREASE_SERVER_MEMORY') && $this->getConfig('NTBR_SERVER_MEMORY_VALUE')) {
            ini_set('memory_limit', $this->getConfig('NTBR_SERVER_MEMORY_VALUE').'M');
        }

        $this->refresh = $refresh;
        $this->cron = $cron;
        $this->setConfig('NTBR_ONGOING', time());
        $this->total_time = time();
        register_shutdown_function(array($this, 'shutdown'));

        if ($this->refresh) {
            // We need a log so that the "REFRESH" log won't trigger a new refresh before it's time
            $this->log('RESUME');

            $this->resume_ingoing = 1;

            // If we need to retrieve the previous values of this
            if (!isset($this->next_step) || !$this->next_step) {
                //$ntbr_values = Tools::jsonDecode($this->getConfig('NTBR_NTBACKUPANDRESTORE_VALUES'), true);
                if (!($handle_config_file = fopen($this->config_file, 'a+'))) {
                    $this->log('ERR'.$this->l('The config file cannot be opened'));
                }

                // Make sur the file has the correct right
                if (chmod($this->config_file, octdec(self::PERM_FILE)) !== true) {
                    $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->config_file, self::PERM_FILE));
                }

                if (!rewind($handle_config_file)) {
                    $this->log('ERR'.$this->l('The config file cannot be rewind'));
                }
                $ntbr_values = unserialize(fgets($handle_config_file));
                fclose($handle_config_file);

                foreach ($ntbr_values as &$value) {
                    $value = Tools::jsonDecode($value, true);
                }

                $set_all_value = $this->setAllValues($ntbr_values);

                if ($set_all_value < 0) {
                    die();
                } elseif (!$set_all_value) {
                    $this->log('ERR'.$this->l('There is an error with the "Intermediate renewal" option'));
                    return $this->endWithError();
                }
            }

            // Save new config (with resume_ongoing = 1)
            $this->writeAllValues(false);

            $pause_between_refresh = $this->getConfig('NTBR_TIME_PAUSE_BETWEEN_REFRESH');

            if ($this->pause_refresh && $pause_between_refresh) {
                $this->pause_refresh = 0;
                sleep($pause_between_refresh);
                $this->refreshBackup(false, false);
            } else {
                $this->pause_refresh = 1;
            }
        } else {
            // Init the big file value. It will change to true if needed when it's compressing.
            $this->setConfig('NTBR_BIG_WEBSITE', 0);

            $this->setNames($this->suffix_backup);
            $this->cron = $cron;// SetName reset $this->cron to false

            //If needed, put in maintenance
            $this->setMaintenance();

            if (!$step) {
                $this->next_step = self::STEP_DUMP_GET_TABLES;
            } else {
                $this->next_step    = $step;

                // Only if we have a step or we should create it normally
                if ($backup_name != '' && $backup_name) {
                    $this->compressed_file  = $backup_name;
                    $compressed_ext         = strrchr($backup_name, '.');
                    $this->tar_file         = str_replace($compressed_ext, '', $backup_name);

                    $this->part_list = array();
                    if (is_array($part_list) && count($part_list) > 0) {
                        foreach ($part_list as $part) {
                            $this->part_list[] = $this->backup_folder.$part;
                        }
                    } else {
                        $this->part_list    = array($this->backup_folder.$backup_name);
                    }

                    $this->total_size   = $this->getBackupTotalSize();
                }
            }

            if (!$step || !$backup_name || $backup_name == '') {
                //Delete old backup files
                $this->deleteBackup();
            }

            //refresh
            $this->refreshBackup();
        }

        if ($this->next_step == self::STEP_DUMP_GET_TABLES || $this->next_step == self::STEP_DUMP_GET_VALUES || $this->next_step == self::STEP_DUMP_GET_VALUES_CONTINUE) {
            //Dump database
            if (!$this->dump()) {
                return $this->endWithError();
            }

            $this->next_step = self::STEP_LIST_FILES;

            //refresh
            $this->refreshBackup();
        }

        if ($this->next_step == self::STEP_LIST_FILES
            || $this->next_step == self::STEP_LIST_FILES_CONTINUE
            || $this->next_step == self::STEP_BACKUP_FILES
        ) {
            //Backup files
            if (!$this->tar()) {
                return $this->endWithError();
            }
            $this->next_step = self::STEP_COMPRESS;

            //refresh
            $this->refreshBackup();
        }

        if (!$this->backupCommonStep()) {
            return $this->endWithError();
        }

        if (file_exists($this->config_file)) {
            $this->fileDelete($this->config_file);
        }

        return $this->total_size;
    }

    /**
     * backup files only
     *
     * @return bool
     */
    public function backupFilesOnly($refresh = false, $cron = true)
    {
        if ($this->getConfig('NTBR_DISABLE_SERVER_TIMEOUT')) {
            set_time_limit(self::SET_TIME_LIMIT);
        }

        if ($this->getConfig('NTBR_INCREASE_SERVER_MEMORY') && $this->getConfig('NTBR_SERVER_MEMORY_VALUE')) {
            ini_set('memory_limit', $this->getConfig('NTBR_SERVER_MEMORY_VALUE').'M');
        }

        $this->refresh = $refresh;
        $this->cron = $cron;
        $this->setConfig('NTBR_ONGOING', time());
        $this->total_time = time();
        register_shutdown_function(array($this, 'shutdown'));

        if ($this->refresh) {
            // We need a log so that the "REFRESH" log won't trigger a new refresh before it's time
            $this->log('RESUME');

            $this->resume_ingoing = 1;

            // If we need to retrieve the previous values of this
            if (!isset($this->next_step) || !$this->next_step) {
                //$ntbr_values = Tools::jsonDecode($this->getConfig('NTBR_NTBACKUPANDRESTORE_VALUES'), true);
                if (!($handle_config_file = fopen($this->config_file, 'a+'))) {
                    $this->log('ERR'.$this->l('The config file cannot be opened'));
                }

                // Make sur the file has the correct right
                if (chmod($this->config_file, octdec(self::PERM_FILE)) !== true) {
                    $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->config_file, self::PERM_FILE));
                }

                if (!rewind($handle_config_file)) {
                    $this->log('ERR'.$this->l('The config file cannot be rewind'));
                }
                $ntbr_values = unserialize(fgets($handle_config_file));
                fclose($handle_config_file);

                foreach ($ntbr_values as &$value) {
                    $value = Tools::jsonDecode($value, true);
                }

                $set_all_value = $this->setAllValues($ntbr_values);

                if ($set_all_value < 0) {
                    die();
                } elseif (!$set_all_value) {
                    $this->log('ERR'.$this->l('There is an error with the "Intermediate renewal" option'));
                    return $this->endWithError();
                }
            }

            // Save new config (with resume_ongoing = 1)
            $this->writeAllValues(false);

            $pause_between_refresh = $this->getConfig('NTBR_TIME_PAUSE_BETWEEN_REFRESH');

            if ($this->pause_refresh && $pause_between_refresh) {
                $this->pause_refresh = 0;
                sleep($pause_between_refresh);
                $this->refreshBackup(false, false);
            } else {
                $this->pause_refresh = 1;
            }
        } else {
            // Init the big file value. It will change to true if needed when it's compressing.
            $this->setConfig('NTBR_BIG_WEBSITE', 0);

            $this->setNames($this->suffix_backup_file);
            $this->cron = $cron;// SetName reset $this->cron to false

            //If needed, put in maintenance
            $this->setMaintenance();

            //Delete old backup files
            $this->deleteBackup();

            $this->next_step = self::STEP_LIST_FILES;

            //refresh
            $this->refreshBackup();
        }

        if ($this->next_step == self::STEP_LIST_FILES
            || $this->next_step == self::STEP_LIST_FILES_CONTINUE
            || $this->next_step == self::STEP_BACKUP_FILES
        ) {
            //Backup files
            if (!$this->tar()) {
                return $this->endWithError();
            }
            $this->next_step = self::STEP_COMPRESS;

            //refresh
            $this->refreshBackup();
        }

        if (!$this->backupCommonStep()) {
            return $this->endWithError();
        }

        if (file_exists($this->config_file)) {
            $this->fileDelete($this->config_file);
        }

        return $this->total_size;
    }

    /**
     * backup database only
     *
     * @return bool
     */
    public function backupDatabaseOnly($refresh = false, $cron = true)
    {
        if ($this->getConfig('NTBR_DISABLE_SERVER_TIMEOUT')) {
            set_time_limit(self::SET_TIME_LIMIT);
        }

        if ($this->getConfig('NTBR_INCREASE_SERVER_MEMORY') && $this->getConfig('NTBR_SERVER_MEMORY_VALUE')) {
            ini_set('memory_limit', $this->getConfig('NTBR_SERVER_MEMORY_VALUE').'M');
        }

        $this->refresh = $refresh;
        $this->cron = $cron;
        $this->setConfig('NTBR_ONGOING', time());
        $this->total_time = time();
        register_shutdown_function(array($this, 'shutdown'));

        if ($this->refresh) {
            // We need a log so that the "REFRESH" log won't trigger a new refresh before it's time
            $this->log('RESUME');

            $this->resume_ingoing = 1;

            // If we need to retrieve the previous values of this
            if (!isset($this->next_step) || !$this->next_step) {
                //$ntbr_values = Tools::jsonDecode($this->getConfig('NTBR_NTBACKUPANDRESTORE_VALUES'), true);
                if (!($handle_config_file = fopen($this->config_file, 'a+'))) {
                    $this->log('ERR'.$this->l('The config file cannot be opened'));
                }

                // Make sur the file has the correct right
                if (chmod($this->config_file, octdec(self::PERM_FILE)) !== true) {
                    $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->config_file, self::PERM_FILE));
                }

                if (!rewind($handle_config_file)) {
                    $this->log('ERR'.$this->l('The config file cannot be rewind'));
                }
                $ntbr_values = unserialize(fgets($handle_config_file));
                fclose($handle_config_file);

                foreach ($ntbr_values as &$value) {
                    $value = Tools::jsonDecode($value, true);
                }

                $set_all_value = $this->setAllValues($ntbr_values);

                if ($set_all_value < 0) {
                    die();
                } elseif (!$set_all_value) {
                    $this->log('ERR'.$this->l('There is an error with the "Intermediate renewal" option'));
                    return $this->endWithError();
                }
            }

            // Save new config (with resume_ongoing = 1)
            $this->writeAllValues(false);

            $pause_between_refresh = $this->getConfig('NTBR_TIME_PAUSE_BETWEEN_REFRESH');

            if ($this->pause_refresh && $pause_between_refresh) {
                $this->pause_refresh = 0;
                sleep($pause_between_refresh);
                $this->refreshBackup(false, false);
            } else {
                $this->pause_refresh = 1;
            }
        } else {
            // Init the big file value. It will change to true if needed when it's compressing.
            $this->setConfig('NTBR_BIG_WEBSITE', 0);

            $this->setNames($this->suffix_backup_base);
            $this->cron = $cron;// SetName reset $this->cron to false

            //If needed, put in maintenance
            $this->setMaintenance();

            //Delete old backup files
            $this->deleteBackup();

            $this->next_step = self::STEP_DUMP_GET_TABLES;

            //refresh
            $this->refreshBackup();
        }

        if ($this->next_step == self::STEP_DUMP_GET_TABLES || $this->next_step == self::STEP_DUMP_GET_VALUES || $this->next_step == self::STEP_DUMP_GET_VALUES_CONTINUE) {
            //Dump database
            if (!$this->dump()) {
                return $this->endWithError();
            }

            $this->next_step = self::STEP_LIST_FILES;

            //refresh
            $this->refreshBackup();
        }

        if ($this->next_step == self::STEP_LIST_FILES
            || $this->next_step == self::STEP_LIST_FILES_CONTINUE
            || $this->next_step == self::STEP_BACKUP_FILES
        ) {
            //Backup files
            if (!$this->tar($this->dump_file)) {
                return $this->endWithError();
            }

            $this->next_step = self::STEP_COMPRESS;

            //refresh
            $this->refreshBackup();
        }

        if (!$this->backupCommonStep()) {
            return $this->endWithError();
        }

        if (file_exists($this->config_file)) {
            $this->fileDelete($this->config_file);
        }

        return $this->total_size;
    }

    protected function backupCommonStep()
    {
        if ($this->next_step == self::STEP_COMPRESS || $this->next_step == self::STEP_COMPRESS_CONTINUE) {
            // If we do want to compress the backup
            if (!$this->compressBackup()) {
                return false;
            }

            $this->next_step = self::STEP_SEND_AWAY;
            //refresh
            $this->refreshBackup();
        }

        // If we do want to send the backup somewhere else
        $this->sendBackupAway();

        // If we do not want to keep the local backup
        $this->deleteLocalBackup();

        // Reset to previous maintenance setup
        $this->resetMaintenance();

        $this->log('END'.$this->l('Success'));

        return true;
    }

    /**
     * sendReport()
     *
     * Send a report by mail
     *
     * @return void
     *
     */
    protected function sendReport($message = '')
    {
        $date_end = date($this->date_format);
        $hour_end = date($this->hour_format);
        $success = true;

        if ($message == '' || !$message) {
            $message = Tools::substr(Tools::file_get_contents($this->lastlog_file), 3);
        } else {
            if (Tools::substr($message, 0, 3) == 'ERR') {
                $success = false;
            }

            $message = Tools::substr($message, 3);
        }

        if (isset($this->warnings) && is_array($this->warnings) && count($this->warnings)) {
            $success = false;
            $message .= "\r\n";
            foreach ($this->warnings as $warning) {
                $message .= "\n".$warning;
            }
        }

        if ($this->getConfig('EMAIL_ONLY_ERROR') && $success) {
            return true;
        }

        if ($this->getConfig('SEND_EMAIL')) {
            //Send a report by mail
            $this->sendBackupResultEmail($this->date_start, $this->hour_start, $date_end, $hour_end, $message);
        }
    }

    /**
     * Send an email with the result of the backup
     */
    protected function sendBackupResultEmail($date_start, $hours_start, $date_end, $hours_end, $result)
    {
        $template_vars = array(
            '{date_start}'    => $date_start,
            '{hours_start}'   => $hours_start,
            '{date_end}'      => $date_end,
            '{hours_end}'     => $hours_end,
            '{backup_result}' => Tools::nl2br(Tools::stripslashes($result)),
        );

        $id_lang = $this->context->language->id ? $this->context->language->id : Configuration::get('PS_LANG_DEFAULT');

        $iso = Language::getIsoById((int)$id_lang);

        $theme_path = _PS_THEME_DIR_;

        if (!file_exists($theme_path.'modules/'.$this->name.'/mails/'.$iso.'/backup_result.html')
            && !file_exists($theme_path.'modules/'.$this->name.'/mails/'.$iso.'/backup_result.txt')
            && !file_exists(_PS_MODULE_DIR_.$this->name.'/mails/'.$iso.'/backup_result.html')
            && !file_exists(_PS_MODULE_DIR_.$this->name.'/mails/'.$iso.'/backup_result.txt')
        ) {
            $id_lang = Language::getIdByIso('en');
        }

        Mail::Send($id_lang, 'backup_result', Mail::l('Backup result', $id_lang), $template_vars, $this->getConfig('MAIL_BACKUP'), null, null, null, null, null, dirname(__FILE__).'/../mails/', false, $this->id_shop);
    }

    /**
     * goToPositionInFile()
     *
     * Go to a given position in a file
     *
     * @param   ressource   $file       An open file
     * @param   int         $position   Position to go in the file
     * @return  ressource   The file with the pointer in position
     *
     */
    public function goToPositionInFile($file, $position)
    {
        // Go to the last position in the file
        $max_seek = $position;

        // If the file is really big
        if ($position > self::MAX_SEEK_SIZE) {
            $max_seek = self::MAX_SEEK_SIZE;
        }

        // Set where we were in the file
        if (fseek($file, $max_seek) == -1) {
            $this->log('ERR'.$this->ntbr->l('The file is no longer seekable'));
            return false;
        }

        $position -= $max_seek;

        $max_read = self::MAX_READ_SIZE;
        while ($position > 0) {
            if ($position >= $max_read) {
                $size_to_read = $max_read;
            } else {
                $size_to_read = $position;
            }

            if (fread($file, $size_to_read) === false) {
                $this->log('ERR'.$this->ntbr->l('The file is no longer readable.'));
                return false;
            }

            $position -= $size_to_read;
        }

        return $file;
    }

    /**
     * Backup database
     *
     * @return bool
     */
    protected function dump()
    {
        $db = Db::getInstance();
        if ($this->next_step == self::STEP_DUMP_GET_TABLES) {
            $this->log($this->l('Backuping database...'));
            //Remove old dump
            $this->fileDelete($this->dump_file);

            //Count how many lines to backup (roughly)
            $req_total_lines = "SELECT SUM(TABLE_ROWS) as total
                                FROM INFORMATION_SCHEMA.TABLES
                                WHERE TABLE_SCHEMA = '".pSQL(_DB_NAME_)."' ";
            // Get tables to ignore
            $tables_to_ignore       = str_replace(' ', '', $this->getConfig('NTBR_IGNORE_TABLES'));
            $low_interest_tables    = array(
                _DB_PREFIX_.'connections',
                _DB_PREFIX_.'connections_page',
                _DB_PREFIX_.'connections_source',
                _DB_PREFIX_.'statssearch',
                _DB_PREFIX_.'pm_cachemanager_cache',
                _DB_PREFIX_.'pm_cachemanager_cache_content',
                _DB_PREFIX_.'jm_pagecache',
                _DB_PREFIX_.'jm_pagecache_bl',
                _DB_PREFIX_.'jm_pagecache_mods',
                _DB_PREFIX_.'jm_pagecache_sp',
                _DB_PREFIX_.'smarty_cache',
                _DB_PREFIX_.'smarty_last_flush',
                _DB_PREFIX_.'smarty_lazy_cache',
                _DB_PREFIX_.'search_index',
                _DB_PREFIX_.'search_word',
                _DB_PREFIX_.'pos_search_index',
                _DB_PREFIX_.'pos_search_word',
                _DB_PREFIX_.'denjean_log',
                _DB_PREFIX_.'guest',
            );
            $this->dump_tables_to_ignore  = array();

            if ($tables_to_ignore != '') {
                $this->dump_tables_to_ignore  = explode(',', $tables_to_ignore);
            }

            if (!$this->getConfig('DUMP_LOW_INTEREST_TABLES')) {
                $this->dump_tables_to_ignore = array_merge($this->dump_tables_to_ignore, $low_interest_tables);
            }

            if (count($this->dump_tables_to_ignore)) {
                $clean_tables_to_ignore = "'".implode("','", $this->dump_tables_to_ignore)."'";
                $req_total_lines        .= 'AND table_name NOT IN ('.$clean_tables_to_ignore.')';
            }

            $this->dump_total_lines = $db->executeS($req_total_lines, true, false);
            $this->dump_total_lines = $this->dump_total_lines[0]['total'];
            $this->dump_percent_lines = 0;
            $this->old_percent = 0;

            //Begin dump
            $dump = "\n".'-- --------------------------------------------------------';
            $dump .= "\n".'-- Database Dump';
            $dump .= "\n".'-- ';
            $dump .= "\n".'-- '.date('Y-m-d H:i:s');
            $dump .= "\n".'-- ';
            $dump .= "\n".'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
            $dump .= "\n".'SET FOREIGN_KEY_CHECKS = 0;';
            $dump .= "\n".'-- ';
            $dump .= "\n".'-- Database : '._DB_NAME_;
            $dump .= "\n";

            //UTF-8 Database
            $db->execute("SET NAMES 'utf8'");
            /////////////////////////////
            //Tables
            /////////////////////////////
            $dump .= "\n".'-- --------------------------------------------------------';
            $dump .= "\n".'-- TABLES';
            $dump .= "\n".'-- --------------------------------------------------------';

            if (!$this->writeDump($dump)) {
                return false;
            }
            $this->dump_tables = $db->executeS('SHOW TABLES', true, false);
            $this->dump_total_tables_done = 0;

            $this->next_step = self::STEP_DUMP_GET_VALUES;
        }

        if ($this->next_step == self::STEP_DUMP_GET_VALUES || $this->next_step == self::STEP_DUMP_GET_VALUES_CONTINUE) {
            $nb_table_current   = 0;

            //$db = Db::getInstance();
            foreach ($this->dump_tables as $key => $table) {
                //Current table
                $dump_current_table = $table['Tables_in_'._DB_NAME_];
                $this->dump_total_tables_done++;

                if ($this->dump_total_tables_done == ($nb_table_current + 10)) {
                    $this->log(sprintf($this->l('Backuping database... %d tables left'), count($this->dump_tables)));
                    $nb_table_current = $nb_table_current + 10;
                }
                /////////////////////////////
                //Table structure
                /////////////////////////////
                $structure = $db->executeS('SHOW CREATE TABLE `'.pSQL($dump_current_table).'`', true, false);

                if (!isset($this->dump_table_total_lines[$dump_current_table])) {
                    $dump  = "\n";
                    $dump .= "\n".'-- Table : '.$dump_current_table;
                    $dump .= "\n";
                    $dump .= "\n".'DROP TABLE IF EXISTS `'.$dump_current_table.'`;';
                    $dump .= "\n";
                    $dump .= "\n".$structure[0]['Create Table'].';';
                    $dump .= "\n";
                    $dump .= "\n";

                    if (!$this->writeDump($dump)) {
                        return false;
                    }
                }

                $this->next_step = self::STEP_DUMP_GET_VALUES_CONTINUE;

                /////////////////////////////
                //Fields type
                /////////////////////////////
                $types      = $db->executeS('DESCRIBE `'.pSQL($dump_current_table).'`', true, false);
                $dump_field_type = array();
                foreach ($types as $type) {//Get field type to know how to proceed data later
                    if (($size = strpos($type['Type'], '(')) === false) {
                        if (($size = strpos($type['Type'], ' ')) === false) {
                            $size = Tools::strlen($type['Type']);
                        }
                    }
                    $compare_type = Tools::strtoupper($this->left($type['Type'], $size));
                    if ($this->in($compare_type, $this->typeint_mysql)) {
                        $dump_field_type[$type['Field']] = self::DATA_INT;
                    } elseif ($this->in($compare_type, $this->typeblob_mysql)) {
                        $dump_field_type[$type['Field']] = self::DATA_BLOB;
                    } else {
                        $dump_field_type[$type['Field']] = self::DATA_OTHER;
                    }
                }

                /////////////////////////////
                //Table datas
                /////////////////////////////
                //Some tables may be very huge and with very low interest so no need to backup their data if not wanted
                //Some tables are in the list of tables to ignore in the configuration
                if (count($this->dump_tables_to_ignore) && in_array($dump_current_table, $this->dump_tables_to_ignore)) {
                    continue;
                }

                // Count how many lines there is in the table
                if (!isset($this->dump_table_total_lines[$dump_current_table])) {
                    $this->dump_table_total_lines[$dump_current_table] = $db->getValue('SELECT COUNT(*) FROM `'.pSQL($dump_current_table).'`');
                }

                // init values for the table
                $dump = '';
                $num_line = 0;
                $num_values = 0;

                if (!isset($this->dump_table_total_lines_done[$dump_current_table])) {
                    $this->dump_table_total_lines_done[$dump_current_table] = 0;
                }

                // While there is still some lines to get
                while ($this->dump_table_total_lines_done[$dump_current_table] < $this->dump_table_total_lines[$dump_current_table]) {
                    // Get a number max of lines
                    $db->query('
                        SELECT *
                        FROM `'.pSQL($dump_current_table).'`
                            LIMIT '.$this->dump_table_total_lines_done[$dump_current_table].', '.self::DUMP_LINES_LIMIT);
                    $this->dump_table_total_lines_done[$dump_current_table]+= self::DUMP_LINES_LIMIT;

                    while ($line = $db->nextRow()) {
                        $this->checkStopScript();
                        $fields = '';
                        $values = '';
                        foreach ($line as $field => $value) {
                            $fields .= '`'.$field.'`, ';
                            if (is_null($value)) {
                                $values .= 'NULL, ';
                            } else {
                                switch ($dump_field_type[$field]) {
                                    case self::DATA_BLOB:
                                        $values .= '0x'.bin2hex($value).', ';
                                        break;
                                    case self::DATA_OTHER:
                                        $values .= str_replace("\n", '\n', str_replace("\r", '\r', "'".addslashes($value)."', "));
                                        break;
                                    default:
                                        $values .= $value.', ';
                                }
                            }
                        }

                        $fields = $this->cutRight($fields, 2);
                        $values = $this->cutRight($values, 2);

                        //No more values on 1 line. Mysql server will go away if too many values per line
                        if ($num_values == self::DUMP_MAX_VALUES) {
                            $num_values = 0;
                            $dump = $this->cutRight($dump, 1).';'."\n";
                        }

                        $this->dump_percent_lines++;
                        $num_line++;
                        if ($num_line > self::DUMP_MAX_LINE_WRITE) {//It's time to write dump file
                            if (!$this->writeDump($dump)) {
                                return false;
                            }
                            $num_line = 0;
                            $dump     = '';
                            //Compute rough percentage progression
                            if ($this->dump_total_lines != 0) {
                                $percent = ($this->dump_percent_lines * 100) / $this->dump_total_lines;
                                if ($percent > 100) { //May happen because total line count is rough
                                    $percent = 100;
                                }
                                if ($percent >= $this->old_percent + 1) {
                                    $this->old_percent = round($percent, 0);
                                    $this->log($this->l('Backuping database:').' '.round($percent, 0).'%');
                                }
                            }
                        }
                        $num_values++;

                        //Check if a INSERT line is needed
                        if ($num_values == 1) {
                            $dump .= 'INSERT INTO `'.$dump_current_table.'` ('.$fields.') VALUES ';
                        }
                        $dump .= '('.$values.'),';
                    }

                    if ($this->validRefresh(true)) {
                        //Remove last comma and end line (only if there is at least one value
                        if ($num_values > 0) {
                            $dump = $this->cutRight($dump, 1).';'."\n";
                        }

                        if (!$this->writeDump($dump)) {
                            return false;
                        }

                        //refresh
                        $this->refreshBackup(true, false);
                    }
                }

                //Remove last comma and end line (only if there is at least one value
                if ($num_values > 0) {
                    $dump = $this->cutRight($dump, 1).';'."\n";
                }

                if (!$this->writeDump($dump)) {
                    return false;
                }

                unset($this->dump_tables[$key]);
                unset($this->dump_table_total_lines[$dump_current_table]);
                unset($this->dump_table_total_lines_done[$dump_current_table]);

                //refresh
                $this->refreshBackup(true);
            }

            ////////////////////////////////
            //PROCEDURES
            ////////////////////////////////
    //        $dump .= "\n";
    //        $dump .= "\n".'SET foreign_key_checks = 1;';
    //        $dump .= "\n";
            $dump = "\n".'-- --------------------------------------------------------';
            $dump .= "\n".'-- PROCEDURES';
            $dump .= "\n".'-- --------------------------------------------------------';

            $procedures = $db->executeS("SHOW PROCEDURE STATUS WHERE db = '".pSQL(_DB_NAME_)."'", true, false);

            foreach ($procedures as $procedure) {
                $structures = $db->executeS('SHOW CREATE PROCEDURE `'.pSQL(_DB_NAME_.'`.`'.$procedure['Name']).'`', true, false);

                $dump .= "\n";
                $dump .= "\n".'-- Procedure : '.$procedure['Name'];
                $dump .= "\n";
                $dump .= "\n";
                $dump .= "\n".'DROP PROCEDURE IF EXISTS `'.$procedure['Name'].'`;';
                $dump .= "\n";
                //Delete create begin to take out the definer
                $position_procedure = strpos($structures[0]['Create Procedure'], 'PROCEDURE');
                $creation           = Tools::substr($structures[0]['Create Procedure'], $position_procedure, Tools::strlen($structures[0]['Create Procedure']) - $position_procedure);
                //Recreation of create in one line so no 'delimiter'
                $dump .= 'CREATE '.str_replace("\n", ' ', $creation).';';
                $dump .= "\n";
            }

            if (!$this->writeDump($dump)) {
                return false;
            }

            ////////////////////////////////
            //FUNCTIONS
            ////////////////////////////////
            $dump = "\n".'-- --------------------------------------------------------';
            $dump .= "\n".'-- FUNCTIONS';
            $dump .= "\n".'-- --------------------------------------------------------';

            $functions = $db->executeS("SHOW FUNCTION STATUS WHERE db = '".pSQL(_DB_NAME_)."'", true, false);

            foreach ($functions as $function) {
                $structures = $db->executeS('SHOW CREATE FUNCTION `'.pSQL(_DB_NAME_.'`.`'.$function['Name']).'`', true, false);

                $dump .= "\n";
                $dump .= "\n".'-- Function : '.$function['Name'];
                $dump .= "\n";
                $dump .= "\n";
                $dump .= "\n".'DROP FUNCTION IF EXISTS `'.$function['Name'].'`;';
                $dump .= "\n";
                //Delete create begin to take out the definer
                $position_function = strpos($structures[0]['Create Function'], 'FUNCTION');
                $creation          = Tools::substr($structures[0]['Create Function'], $position_function, Tools::strlen($structures[0]['Create Function']) - $position_function);
                //Recreation of create in one line so no 'delimiter'
                $dump .= 'CREATE '.str_replace("\n", ' ', $creation).';';
                $dump .= "\n";
            }

            if (!$this->writeDump($dump)) {
                return false;
            }

            return true;
        }
    }

    /**
     * writeDump()
     *
     * Append the dump file with a part of the dump
     *
     * @param string $dump the dump part to write
     * @return boolean False if error
     *
     */
    protected function writeDump($dump)
    {
        if ($dump && !$this->fileWrite($this->dump_file, $dump, 'a+')) {
            $this->log('ERR'.$this->l('Error while writing dump file'));
            return false;
        }
        return true;
    }

    /**
     * directoryCreate()
     *
     * Create a directory and all its parents if necessary
     *
     * @param string $path Path of the folder
     * @param integer $rights Rights of the folder
     * @return boolean True if directory created
     *
     */
    protected function directoryCreate($path, $rights = 0777)
    {
        if (is_dir($path)) {
            return true;
        }

        $directory = array($path);

        while (!is_dir(dirname(end($directory)))
                && dirname(end($directory)) != '/'
                && dirname(end($directory)) != '.'
                && dirname(end($directory)) != '') {
            array_push($directory, dirname(end($directory)));
        }

        while ($parent_directory = array_pop($directory)) {
            if (!mkdir($parent_directory, $rights)) {
                return false;
            }
        }

        return true;
    }

    /**
     * in()
     *
     * Test if a value is in the compare array
     * Can be use like this :
     * in($value, array('v1', 'v2', v3, v4))
     *
     * @param string $value Value to compare
     * @param array $compare Compare array
     * @param boolean $strict True for strict comparaison (===)
     * @param boolean $difference True for a differrence (!=) instead of equality (==)
     * @param integer $equal_nb Number of equality (or differences) to get before returning true (default 1)
     * @return boolean True if value is in compare array
     *
     */
    protected function in($value, $compare, $strict = false, $difference = false, $equal_nb = 1)
    {
        if (!is_array($compare) || !count($compare)) {
            return false;
        }

        $nb_same = 0;
        foreach ($compare as $val) {
            if ($strict) {
                if ($difference) {
                    if ($value !== $val) {
                        $nb_same++;
                    }
                } else {
                    if ($value === $val) {
                        $nb_same++;
                    }
                }
            } else {
                if ($difference) {
                    if ($value != $val) {
                        $nb_same++;
                    }
                } else {
                    if ($value == $val) {
                        $nb_same++;
                    }
                }
            }

            if ($nb_same >= $equal_nb) {
                return true;
            }
        }
        return false;
    }

    /**
     * left()
     *
     * Return the left part of the string
     *
     * @param string $string The string
     * @param integer $size Size to get back
     * @return string Left part of the string
     *
     */
    protected function left($string, $size)
    {
        $string = Tools::substr($string, 0, $size);
        if ($string === false) {
            return '';
        }
        return $string;
    }

    /**
     * cutRight()
     *
     * Return string without $size characters at its end
     *
     * @param string $string
     * @param integer $size
     * @return string the string without end
     *
     */
    protected function cutRight($string, $size)
    {
        $string = Tools::substr($string, 0, ($size * -1));
        if ($string === false) {
            return '';
        }
        return $string;
    }

    /**
     * findThisBackup()
     *
     * Find a backup file
     *
     * @return array|bool  The files or false if failure
     *
     */
    public function findThisBackup($nb)
    {
        // Find all old backups
        $old_backups = $this->findOldBackups();

        $nb_detail = explode('.', $nb);
        if (!isset($nb_detail[0])) {
            return false;
        }

        if (!isset($old_backups[$nb_detail[0]])) {
            return false;
        }

        // If file is only a part of the backup
        if (isset($nb_detail[1])) {
            // Check if the file exists
            if (!isset($old_backups[$nb_detail[0]]['part'][$nb]['name'])) {
                $this->log($this->l('The backup file does not exists:').' '.$nb);
                return false;
            }
        } else {
            // Check if the file exists
            if (!isset($old_backups[$nb_detail[0]]['name'])) {
                $this->log($this->l('The backup file does not exists:').' '.$nb);
                return false;
            }
        }

        return $old_backups[$nb_detail[0]]['part'];
    }

    /**
     * deleteThisBackup()
     *
     * Delete a backup file
     *
     * @return boolean
     *
     */
    public function deleteThisBackup($nb)
    {
        $result = array(
            'success'       =>  1,
            'update_list'   =>  '-',
        );

        // Find the backups
        $files_to_delete = $this->findThisBackup($nb);

        if (!is_array($files_to_delete)) {
            $result['success'] = 0;
            return $result;
        }

        // Delete the files
        foreach ($files_to_delete as $file) {
            if (!$this->fileDelete($this->backup_folder.$file['name'])) {
                $this->log($this->l('Delete backup file failed:').' '.$file['name']);
                $result['success'] = 0;
            }
        }

        // Delete the comment
        $comment_infos = Comments::getBackupCommentInfos($files_to_delete[$nb.'.1']['name']);
        if (isset($comment_infos['id_ntbr_comments']) && (int)$comment_infos['id_ntbr_comments'] > 0) {
            $comment = new Comments($comment_infos['id_ntbr_comments']);
            if (!$comment->delete()) {
                $this->log($this->l('Delete backup comment failed:').' '.$files_to_delete[$nb.'.1']['name']);
                $result['success'] = 0;
            }
        }

        $result['update_list'] = $this->updateBackupList();

        return $result;
    }

    public function getBackupPart($backup_file, $list_files)
    {
        $backup_parts = array();
        $matches = array();
        $nb_part = array();

        if (strpos($backup_file, '.part.') !== false) {
            preg_match('/(.*)\.[0-9]*\.part/', $backup_file, $matches);

            if (isset($matches[1])) {
                foreach ($list_files as $nb_file => $old_backup) {
                    if ($matches[1][0] == '/' && $old_backup[0] != '/') {
                        $old_backup = '/'.$old_backup;
                    } elseif ($matches[1][0] != '/' && $old_backup[0] == '/') {
                        $matches[1] = '/'.$matches[1];
                    }

                    if (strpos($old_backup, $matches[1]) !== false) {
                        preg_match('/.*\.([0-9]*)\.part/', $old_backup, $nb_part);

                        if ($old_backup[0] === '/') {
                            $old_backup = Tools::substr($old_backup, 1);
                        }

                        if (isset($nb_part[1])) {
                            $backup_parts[$nb_part[1]] = $old_backup;
                        } else {
                            $backup_parts[$nb_file] = $old_backup;
                        }
                    }
                }
            }
        }

        if (!count($backup_parts)) {
            $backup_parts[1] = $backup_file;
        }

        return $backup_parts;
    }

    /**
     * deleteBackup()
     *
     * Delete too old backups
     *
     * @return boolean
     *
     */
    public function deleteBackup()
    {
        //Remove old dump
        if (file_exists($this->dump_file)) {
            if (!$this->fileDelete($this->dump_file)) {
                $this->log($this->l('Delete old dump file failed'));
                $return = false;
            }
        }
        //Remove old tar backup
        if (file_exists($this->tar_file)) {
            if (!$this->fileDelete($this->tar_file)) {
                $this->log($this->l('Delete old tar file failed'));
                $return = false;
            }
        }

        $return = $this->deleteOldTar();

        $test_type_backup   = $this->suffix_backup;
        $nb_file_to_keep    = $this->getConfig('NB_KEEP_BACKUP');

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_file_to_keep    = $this->getConfig('NB_KEEP_BACKUP_BASE');
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_file_to_keep    = $this->getConfig('NB_KEEP_BACKUP_FILE');
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Find all old backups
        $old_backups    = $this->findOldBackups($test_type_backup);
        $nb_files       = count($old_backups);

        if ($nb_file_to_keep == 0 || $nb_files < $nb_file_to_keep) {
            return $return;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_file_to_keep) {
            foreach ($old_backups[$nb_files]['part'] as $part) {
                if (file_exists($this->backup_folder.$part['name'])) {
                    if (!$this->fileDelete($this->backup_folder.$part['name'])) {
                        $this->log($this->l('Delete old backup file failed:').$part['name']);
                        $return = false;
                    }
                }
            }
            $nb_files--;
        }
        return $return;
    }

    /**
     * findOldBackups()
     *
     * Find old backups files
     *
     * @param   String  $test_type_backup   Type of backup to search for
     * @param   String  $test_extension     Test to do on the extension to find correct kind of backup
     *
     * @return array Old backup sorted by date, older last
     *
     */
    public function findOldBackups($test_type_backup = '.', $test_extension = '.tar.')
    {
        $old_backups    = array();

        if (($dir = opendir($this->backup_folder)) !== false) {
            while (($file = readdir($dir)) !== false) {
                if ($file == '.' || $file == '..' || is_dir($this->backup_folder.$file)) {
                    continue;
                }

                if (strpos($file, $test_type_backup) === false) {
                    continue;
                }

                if (strpos($file, $test_extension) !== false) {
                    $old_backups[] = $file;
                }
            }
            closedir($dir);
        }

        return $this->cleanListBackup($old_backups);
    }

    public function cleanListBackup($list_backup)
    {
        $clean_list_backup = array();
        $name = '';
        $nb_backup = 0;
        $physic_path_modules = realpath(_PS_ROOT_DIR_.'/modules').'/';

        rsort($list_backup);

        foreach ($list_backup as $backup) {
            $backup_parts = array();
            if (strpos($backup, '.1.part') !== false) {
                $matches = array();

                if (!isset($this->next_step) || ($this->next_step > self::STEP_SEND_AWAY && $this->next_step < self::STEP_FINISH)) {
                    if ($backup[0] !== '/') {
                        $backup = '/'.$backup;
                    }

                    // Search the file name without part
                    preg_match('/.*\/(.*)\.[0-9]*\.part/', $backup, $matches);

                    if (isset($matches[1])) {
                        // If the current file is a part of the found file, we do not delete it (in case we want to send only one part and keep the others)
                        if (strpos($this->compressed_file, $matches[1]) !== false) {
                            continue;
                        }
                    }
                }

                $backup_parts = $this->getBackupPart($backup, $list_backup);
                ksort($backup_parts);
                $name_temp = str_replace('.1.part', '', $backup_parts[1]);
            } elseif (strpos($backup, '.part.')) {
                continue;
            } else {
                $backup_parts[1] = $backup;
                $name_temp = $backup;
            }

            if ($name_temp[0] === '/') {
                $name_temp = Tools::substr($name_temp, 1);
            }

            if ($name != $name_temp) {
                $name = $name_temp;
                $nb_backup++;
            }

            if (!isset($clean_list_backup[$nb_backup])) {
                $clean_list_backup[$nb_backup]['name'] = $name;
                // If list of local file (not FTP, Dropbox...)
                if (file_exists($physic_path_modules.$this->name.'/backup/'.$backup)) {
                    // Search the file date
                    $matches = array();
                    preg_match('/.*([0-9]{4})([0-9]{2})([0-9]{2})\.([0-9]{2})([0-9]{2})([0-9]{2}).*/', $backup, $matches);

                    if (isset($matches[1]) && isset($matches[6])) {
                        $clean_list_backup[$nb_backup]['date'] = $matches[3].'/'.$matches[2].'/'.$matches[1].' '.$matches[4].':'.$matches[5].':'.$matches[6];
                    } else {
                        $clean_list_backup[$nb_backup]['date'] = date("d/m/Y H:i:s", filectime($physic_path_modules.$this->name.'/backup/'.$backup));
                    }
                } else {
                    $clean_list_backup[$nb_backup]['date'] = date("d/m/Y H:i:s");
                }

                if (!isset($clean_list_backup[$nb_backup]['size'])) {
                    $clean_list_backup[$nb_backup]['size'] = 0;
                }

                foreach ($backup_parts as $nb_part => $part) {
                    $clean_list_backup[$nb_backup]['part'][$nb_backup.'.'.$nb_part]['name'] = $part;
                    $bytes = 0;

                    // If list of local file (not FTP, Dropbox...)
                    if (file_exists($physic_path_modules.$this->name.'/backup/'.$backup)) {
                        $bytes = $this->getFileSize($physic_path_modules.$this->name.'/backup/'.$part);
                        $clean_list_backup[$nb_backup]['part'][$nb_backup.'.'.$nb_part]['size'] = $this->readableSize($bytes);
                    } else {
                        $clean_list_backup[$nb_backup]['part'][$nb_backup.'.'.$nb_part]['size'] = 0;
                    }

                    $clean_list_backup[$nb_backup]['size'] += $bytes;
                }

                $clean_list_backup[$nb_backup]['size'] = $this->readableSize($clean_list_backup[$nb_backup]['size']);
            }
        }

        return $clean_list_backup;
    }

    /**
     * deleteOldTar()
     *
     * Delete old tar files
     *
     * @return boolean
     *
     */
    public function deleteOldTar()
    {
        $return = true;

        if (($dir = opendir($this->backup_folder)) !== false) {
            while (($file = readdir($dir)) !== false) {
                if ($file == '.' || $file == '..' || is_dir($this->backup_folder.$file)) {
                    continue;
                }

                if (Tools::strtolower(Tools::substr($file, -3)) === 'tar' && file_exists($this->backup_folder.$file)) {
                    if (!$this->fileDelete($this->backup_folder.$file)) {
                        $this->log($this->l('Delete old tar file failed:').$this->backup_folder.$file);
                        $return = false;
                    }
                }
            }
            closedir($dir);
        }

        return $return;
    }

    /**
     * deleteLog()
     *
     * Remove old log
     *
     * @return boolean
     *
     */
    public function deleteLog()
    {
        //Remove old log file
        if (file_exists($this->log_file)) {
            return $this->fileDelete($this->log_file);
        }

        return true;
    }

    /**
     * compressBackup()
     *
     * Compress backup
     *
     * @return  boolean     Success or failure of the operation
     *
     */
    protected function compressBackup()
    {
        if ($this->next_step == self::STEP_COMPRESS) {
            if ($this->num_file_to_compress == 1) {
                $this->fileDelete($this->dump_file);

                $this->log($this->l('Compressing backup...'));
            }
        }

        if ($this->bzip2) {
            if (!$this->compressbz2()) {
                $this->log('ERR'.$this->l('Error while compressing backup.'));
                return false;
            }
        } else {
            if (!$this->compressgz()) {
                $this->log('ERR'.$this->l('Error while compressing backup.'));
                return false;
            }
        }
        $this->fileDelete($this->tar_file);
        $this->next_step = $this->step_send['ftp'];

        return true;
    }

    /**
    * compress tar file to a tar.bz2 file
    *
    * @return bool
    */
    protected function compressbz2()
    {
        if ($this->next_step == self::STEP_COMPRESS) {
            if ($this->num_file_to_compress == 1) {
                if (file_exists($this->compressed_file)) {
                    $this->fileDelete($this->compressed_file);
                }

                // Remove all files from the list
                $this->part_list = array();
            }
        }

        if ($this->num_file_to_compress <= $this->part_number) {
            if ($this->next_step == self::STEP_COMPRESS) {
                if ($this->part_number == 1) {
                    $this->tar_file = $this->part_file.'.tar';
                } else {
                    $this->tar_file = $this->part_file.'.'.$this->num_file_to_compress.'.part.tar';
                }

                $this->compressed_file = $this->tar_file.'.bz2';

                //Open bz file (only "r" or "w" method are accepted by bzopen)
                if (($this->handle_bz_file = bzopen($this->compressed_file, 'w')) === false) {
                    $this->log('ERR'.$this->l('The bz file cannot be opened').' ('.$this->compressed_file.')');
                    return false;
                }

                //Open tar file
                if (($this->handle_tar_file = fopen($this->tar_file, 'rb')) === false) {
                    $this->log('ERR'.$this->l('The tar file cannot be opened').' ('.$this->tar_file.')');
                    return false;
                }

                $this->compress_tar_position = 0;
                //$this->compress_total_size = $this->getFileSize($this->tar_file);
                $this->old_percent = 0;
                $this->compress_size_done = 0;

                $this->next_step = self::STEP_COMPRESS_CONTINUE;
            }

            if ($this->next_step == self::STEP_COMPRESS_CONTINUE) {
                if ($this->num_file_to_compress > 1) {
                    $new_tar_file = $this->part_file.'.'.$this->num_file_to_compress.'.part.tar';

                    if ($this->tar_file != $new_tar_file) {
                        if (is_resource($this->handle_tar_file)) {
                            fclose($this->handle_tar_file);
                        }

                        if (is_resource($this->handle_bz_file)) {
                            bzclose($this->handle_bz_file);
                        }

                        $this->tar_file = $new_tar_file;
                        $this->compressed_file = $this->tar_file.'.bz2';

                        //Open bz file
                        if (($this->handle_bz_file = bzopen($this->compressed_file, 'w')) === false) {
                            $this->log('ERR'.$this->l('The bz file cannot be opened').' ('.$this->compressed_file.')');
                            return false;
                        }

                        //Open tar file
                        if (($this->handle_tar_file = fopen($this->tar_file, 'rb')) === false) {
                            $this->log('ERR'.$this->l('The tar file cannot be opened').' ('.$this->tar_file.')');
                            return false;
                        }

                        $this->compress_tar_position = 0;
                        //$this->compress_total_size = $this->getFileSize($this->tar_file);
                        $this->old_percent = 0;
                        $this->compress_size_done = 0;
                    }
                }

                $max_seek = $this->compress_tar_position;
                // If the file is really big
                if ($this->compress_tar_position > self::MAX_SEEK_SIZE) {
                    $max_seek = self::MAX_SEEK_SIZE;
                }

                // Set where we were in the file
                if (fseek($this->handle_tar_file, $max_seek) == -1) {
                    $this->log('ERR'.$this->l('The tar file is no longer seekable'));
                    return false;
                }

                $this->compress_tar_position -= $max_seek;

                $max_read = self::MAX_READ_SIZE;
                while ($this->compress_tar_position > 0) {
                    if ($this->compress_tar_position >= $max_read) {
                        $size_to_read = $max_read;
                    } else {
                        $size_to_read = $this->compress_tar_position;
                    }

                    if (fread($this->handle_tar_file, $size_to_read) === false) {
                        $this->log('ERR'.$this->l('The tar file is no longer readable.'));
                        return false;
                    }

                    $this->compress_tar_position -= $size_to_read;
                }

                //Compress to bz file
                while (!feof($this->handle_tar_file)) {
                    $read = fread($this->handle_tar_file, 1048576);

                    if ($read === false) {
                        $this->log('ERR'.$this->l('The tar file is no longer readable.'));
                        return false;
                    }

                    bzwrite($this->handle_bz_file, $read);
                    $this->compress_size_done += 1048576;
                    //Compute percentage progression
                    //$percent = ($this->compress_size_done * 100) / $this->compress_total_size;
                    if ($this->tar_files_size[$this->num_file_to_compress] > 0) {
                        $percent = ($this->compress_size_done * 100) / $this->tar_files_size[$this->num_file_to_compress];
                    } else {
                        $percent = 0;
                    }
                    if ($percent > 100) {
                        $percent = 100;
                    }
                    if ($percent >= $this->old_percent + 1) {
                        $this->old_percent = round($percent, 0);
                        if ($this->part_number == 1) {
                            $this->log($this->l('Compressing files:').' '.round($percent, 0).'%');
                        } else {
                            $this->log($this->l('Compressing files:').' '.$this->num_file_to_compress.'/'.$this->part_number.$this->l(':').' '.round($percent, 0).'%');
                        }
                    }

                    // Get where we are in the file
                    $this->compress_tar_position = $this->compress_size_done;

                    //refresh
                    $this->refreshBackup(true);
                }

                //Close tar file
                if (!fclose($this->handle_tar_file)) {
                    $this->log('WAR'.$this->l('The tar file was not closed'));
                    return false;
                }

                //Close bz file
                if (!bzclose($this->handle_bz_file)) {
                    $this->log('WAR'.$this->l('The bz file was not closed'));
                    return false;
                }

                $this->part_list[] = $this->compressed_file;
                //$this->total_size += filesize($this->compressed_file);
                $this->total_size += $this->tar_files_size[$this->num_file_to_compress];
                $this->fileDelete($this->tar_file);
                $this->num_file_to_compress++;

                //refresh
                $this->refreshBackup();

                // There is still some files to compress
                if ($this->num_file_to_compress <= $this->part_number) {
                    return $this->compressbz2();
                }
            }
        }

        return true;
    }

    /**
    * compress tar file to a tar.gz file
    *
    * @return bool
    */
    protected function compressgz()
    {
        if ($this->next_step == self::STEP_COMPRESS) {
            if ($this->num_file_to_compress == 1) {
                if (file_exists($this->compressed_file)) {
                    $this->fileDelete($this->compressed_file);
                }

                // Remove all files from the list
                $this->part_list = array();
            }
        }

        if ($this->num_file_to_compress <= $this->part_number) {
            if ($this->next_step == self::STEP_COMPRESS) {
                if ($this->part_number == 1) {
                    $this->tar_file = $this->part_file.'.tar';
                } else {
                    $this->tar_file = $this->part_file.'.'.$this->num_file_to_compress.'.part.tar';
                }

                $this->compressed_file = $this->tar_file.'.gz';

                //Open gz file
                if (($this->handle_gz_file = gzopen($this->compressed_file, 'wb9')) === false) {
                    $this->log('ERR'.$this->l('The gz file cannot be opened').' ('.$this->compressed_file.')');
                    return false;
                }

                //Open tar file
                if (($this->handle_tar_file = fopen($this->tar_file, 'rb')) === false) {
                    $this->log('ERR'.$this->l('The tar file cannot be opened').' ('.$this->tar_file.')');
                    return false;
                }

                $this->compress_tar_position = 0;
                //$this->compress_total_size = $this->getFileSize($this->tar_file);
                $this->old_percent = 0;
                $this->compress_size_done = 0;

                $this->next_step = self::STEP_COMPRESS_CONTINUE;
            }

            if ($this->next_step == self::STEP_COMPRESS_CONTINUE) {
                if ($this->num_file_to_compress > 1) {
                    $new_tar_file = $this->part_file.'.'.$this->num_file_to_compress.'.part.tar';

                    if ($this->tar_file != $new_tar_file) {
                        if (is_resource($this->handle_tar_file)) {
                            fclose($this->handle_tar_file);
                        }

                        if (is_resource($this->handle_gz_file)) {
                            gzclose($this->handle_gz_file);
                        }

                        $this->tar_file = $new_tar_file;
                        $this->compressed_file = $this->tar_file.'.gz';

                        //Open gz file
                        if (($this->handle_gz_file = gzopen($this->compressed_file, 'wb9')) === false) {
                            $this->log('ERR'.$this->l('The gz file cannot be opened').' ('.$this->compressed_file.')');
                            return false;
                        }

                        //Open tar file
                        if (($this->handle_tar_file = fopen($this->tar_file, 'rb')) === false) {
                            $this->log('ERR'.$this->l('The tar file cannot be opened').' ('.$this->tar_file.')');
                            return false;
                        }

                        $this->compress_tar_position = 0;
                        //$this->compress_total_size = $this->getFileSize($this->tar_file);
                        $this->old_percent = 0;
                        $this->compress_size_done = 0;
                    }
                }

                $max_seek = $this->compress_tar_position;
                if ($this->compress_tar_position > self::MAX_SEEK_SIZE) {
                    $max_seek = self::MAX_SEEK_SIZE;

                    $refresh = !$this->getConfig('NTBR_DISABLE_REFRESH');
                    $time_refresh = $this->getConfig('NTBR_TIME_BETWEEN_REFRESH');
                    $part_size = $this->getConfig('NTBR_PART_SIZE')*1024*1024;

                    // if the refresh is activated and its <= to the default value and there is no multipart small enough
                    if ($refresh && $time_refresh <= self::MAX_TIME_BEFORE_REFRESH
                        && ($part_size <= 0 || $part_size >= self::MAX_SEEK_SIZE)
                    ) {
                        $this->setConfig('NTBR_BIG_WEBSITE', 1);
                    }
                }

                // Set where we were in the file
                if (fseek($this->handle_tar_file, $max_seek) == -1) {
                    $this->log('ERR'.$this->l('The tar file is no longer seekable'));
                    return false;
                }

                $this->compress_tar_position -= $max_seek;

                $max_read = self::MAX_READ_SIZE;
                while ($this->compress_tar_position > 0) {
                    if ($this->compress_tar_position >= $max_read) {
                        $size_to_read = $max_read;
                    } else {
                        $size_to_read = $this->compress_tar_position;
                    }

                    if (fread($this->handle_tar_file, $size_to_read) === false) {
                        $this->log('ERR'.$this->l('The tar file is no longer readable.'));
                        return false;
                    }

                    $this->compress_tar_position -= $size_to_read;
                }

                //Compress to gz file
                while (!feof($this->handle_tar_file)) {
                    $read = fread($this->handle_tar_file, 1048576);

                    if ($read === false) {
                        $this->log('ERR'.$this->l('The tar file is no longer readable.'));
                        return false;
                    }

                    gzwrite($this->handle_gz_file, $read);
                    $this->compress_size_done += 1048576;
                    //Compute percentage progression
                    //$percent = ($this->compress_size_done * 100) / $this->compress_total_size;
                    if ($this->tar_files_size[$this->num_file_to_compress] > 0) {
                        $percent = ($this->compress_size_done * 100) / $this->tar_files_size[$this->num_file_to_compress];
                    } else {
                        $percent = 0;
                    }

                    if ($percent > 100) {
                        $percent = 100;
                    }
                    if ($percent >= $this->old_percent + 1) {
                        $this->old_percent = round($percent, 0);
                        if ($this->part_number == 1) {
                            $this->log($this->l('Compressing files:').' '.round($percent, 0).'%');
                        } else {
                            $this->log($this->l('Compressing files:').' '.$this->num_file_to_compress.'/'.$this->part_number.$this->l(':').' '.round($percent, 0).'%');
                        }
                    }

                    // Get where we are in the file
                    $this->compress_tar_position = $this->compress_size_done;

                    //refresh
                    $this->refreshBackup(true);
                }

                //Close tar file
                if (!fclose($this->handle_tar_file)) {
                    $this->log('WAR'.$this->l('The tar file was not closed'));
                    return false;
                }

                //Close gz file
                if (!gzclose($this->handle_gz_file)) {
                    $this->log('WAR'.$this->l('The gz file was not closed'));
                    return false;
                }

                $this->part_list[] = $this->compressed_file;
                //$this->total_size += filesize($this->compressed_file);
                $this->total_size += $this->tar_files_size[$this->num_file_to_compress];
                $this->fileDelete($this->tar_file);
                $this->log($this->l('Delete file').' '.$this->tar_file);
                $this->num_file_to_compress++;

                //refresh
                $this->refreshBackup();

                // There is still some files to compress
                if ($this->num_file_to_compress <= $this->part_number) {
                    return $this->compressgz();
                }
            }
        }

        return true;
    }

    /**
    * Pad a string with 0
    *
    * @param string $str String to pad
    * @param int $length Length of the final string
    * @return string The written string
    */
    protected function w($str, $length)
    {
        $strw = str_pad($str, $length, "\0");

        /*if (!is_resource($this->handle_tar_file)) {
            //Open tar file
            if (($this->handle_tar_file = fopen($this->tar_file, 'a+b')) === false) {
                $this->log('ERR'.$this->l('The tar file cannot be opened').' ('.$this->tar_file.')');
                die();
            }
        }*/

        if (fwrite($this->handle_tar_file, $strw) === false) {
            if (!is_resource($this->handle_tar_file)) {
                $this->log('ERR'.$this->l('Error while backuping files, unable to write data. The backup file is not valid.'));
            } elseif (!is_string($strw)) {
                $this->log('ERR'.$this->l('Error while backuping files, unable to write data. The content to add is not valid.'));
            } else {
                $this->log('ERR'.$this->l('Error while backuping files, unable to write data. Please check space available.'));
            }

            die();
        }
        if (!isset($this->tar_files_size[$this->part_number])) {
            $this->tar_files_size[$this->part_number] = $length;
        } else {
            $this->tar_files_size[$this->part_number] += $length;
        }

        return $strw;
    }

    /**
    * Normalize a path with / instead of \
    *
    * @param string $path Path
    * @return string Normalized path
    */
    protected function normalizePath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
    * Get the string length
    *
    * @param string $str String
    * @return int String length
    */
    protected function getLength($str)
    {
        return count(str_split($str));
    }

    /**
    * Get a part of a string
    *
    * @param string $str String
    * @return int String length
    */
    protected function getPart($str, $start, $length = null)
    {
        $stra = str_split($str);
        $stra = array_slice($stra, $start, $length);
        return implode($stra);
    }

    public function setAllValues($values)
    {
        if ($values['resume_ingoing']) {
            $this->log($this->l('A backup is already in progress'));
            return -1;
        }

        //$mode_ouverture_tar = 'ab';

        if ($values['handle_bz_file']) {
            // .bz have only two mode "w" and "r"
            if (!($this->handle_bz_file = bzopen($values['compressed_file'], 'r'))) {
                $this->log('ERR'.$this->l('Error while creating the bz file'));
                return false;
            }
            //$mode_ouverture_tar = 'rb';
        }

        if ($values['handle_gz_file']) {
            if (!($this->handle_gz_file = gzopen($values['compressed_file'], 'ab9'))) {
                $this->log('ERR'.$this->l('Error while creating the gz file'));
                return false;
            }
            //$mode_ouverture_tar = 'rb';
        }

        if ($values['handle_tar_file']) {
            if (!($this->handle_tar_file = fopen($values['tar_file'], 'a+b'))) {
                $this->log('ERR'.$this->l('Error while creating the tar file'));
                return false;
            }
        }

        if ($values['handle_file_list_file']) {
            if (!($this->handle_file_list_file = fopen($values['list_files_file'], 'a+'))) {
                $this->log('ERR'.$this->l('Error while creating the list of files'));
                return false;
            }
        }

        if ($values['handle_list_dir_file']) {
            if (!($this->handle_list_dir_file = fopen($values['list_dir_file'], 'r+'))) {
                $this->log('ERR'.$this->l('Error while creating the list of directories'));
                return false;
            }

            $this->list_dir = unserialize(fgets($this->handle_list_dir_file));
        }

        $this->log_file                     = $values['log_file'];
        $this->lastlog_file                 = $values['lastlog_file'];
        $this->list_files_file              = $values['list_files_file'];
        $this->list_dir_file                = $values['list_dir_file'];
        $this->backup_folder                = $values['backup_folder'];
        $this->dump_file                    = $values['dump_file'];
        $this->tar_file                     = $values['tar_file'];
        $this->tar_files_size               = $values['tar_files_size'];
        $this->compressed_file              = $values['compressed_file'];
        $this->bzip2                        = $values['bzip2'];
        $this->id_shop                      = $values['id_shop'];
        $this->id_shop_group                = $values['id_shop_group'];
        $this->date_format                  = $values['date_format'];
        $this->hour_format                  = $values['hour_format'];
        $this->date_start                   = $values['date_start'];
        $this->hour_start                   = $values['hour_start'];
        $this->total_files                  = $values['total_files'];
        $this->files_done                   = $values['files_done'];
        $this->old_percent                  = $values['old_percent'];
        $this->base_length                  = $values['base_length'];
        $this->backup_name                  = $values['backup_name'];
        $this->backup_name_date             = $values['backup_name_date'];
        $this->norm_backup_file             = $values['norm_backup_file'];
        $this->norm_tar_file                = $values['norm_tar_file'];
        $this->norm_compressed_file         = $values['norm_compressed_file'];
        $this->norm_log_file                = $values['norm_log_file'];
        $this->norm_lastlog_file            = $values['norm_lastlog_file'];
        $this->source_dir                   = $values['source_dir'];
        $this->part_file                    = $values['part_file'];
        $this->part_size                    = $values['part_size'];
        $this->part_number                  = $values['part_number'];
        $this->part_list                    = $values['part_list'];
        $this->total_size                   = $values['total_size'];
        $this->module_path                  = $values['module_path'];
        $this->module_path_physic           = $values['module_path_physic'];
        $this->warnings                     = $values['warnings'];
        $this->ps_shop_enable               = $values['ps_shop_enable'];
        $this->next_step                    = $values['next_step'];
        $this->num_file_to_compress         = $values['num_file_to_compress'];
        $this->dump_tables                  = $values['dump_tables'];
        $this->dump_percent_lines           = $values['dump_percent_lines'];
        $this->dump_total_lines             = $values['dump_total_lines'];
        $this->dump_table_total_lines       = $values['dump_table_total_lines'];
        $this->dump_table_total_lines_done  = $values['dump_table_total_lines_done'];
        $this->dump_total_tables_done       = $values['dump_total_tables_done'];
        $this->dump_tables_to_ignore        = $values['dump_tables_to_ignore'];
        $this->compress_tar_position        = $values['compress_tar_position'];
        $this->compress_size_done           = $values['compress_size_done'];
        $this->compress_total_size          = $values['compress_total_size'];
        $this->list_files_to_add            = $values['list_files_to_add'];
        $this->nb_file_in_list_to_add       = $values['nb_file_in_list_to_add'];
        $this->position_list_file           = $values['position_list_file'];
        $this->array_files_to_add           = $values['array_files_to_add'];
        $this->dropbox_upload_id            = $values['dropbox_upload_id'];
        $this->dropbox_position             = $values['dropbox_position'];
        $this->dropbox_dir                  = $values['dropbox_dir'];
        $this->dropbox_nb_part              = $values['dropbox_nb_part'];
        $this->dropbox_account_id           = $values['dropbox_account_id'];
        $this->onedrive_session             = $values['onedrive_session'];
        $this->onedrive_position            = $values['onedrive_position'];
        $this->onedrive_nb_part             = $values['onedrive_nb_part'];
        $this->onedrive_account_id          = $values['onedrive_account_id'];
        $this->owncloud_session             = $values['owncloud_session'];
        $this->owncloud_position            = $values['owncloud_position'];
        $this->owncloud_nb_part             = $values['owncloud_nb_part'];
        $this->owncloud_nb_chunk            = $values['owncloud_nb_chunk'];
        $this->owncloud_account_id          = $values['owncloud_account_id'];
        $this->webdav_session               = $values['webdav_session'];
        $this->webdav_position              = $values['webdav_position'];
        $this->webdav_nb_part               = $values['webdav_nb_part'];
        $this->webdav_nb_chunk              = $values['webdav_nb_chunk'];
        $this->webdav_account_id            = $values['webdav_account_id'];
        $this->ftp_dir                      = $values['ftp_dir'];
        $this->ftp_nb_part                  = $values['ftp_nb_part'];
        $this->ftp_position                 = $values['ftp_position'];
        $this->ftp_account_id               = $values['ftp_account_id'];
        $this->googledrive_session          = $values['googledrive_session'];
        $this->googledrive_position         = $values['googledrive_position'];
        $this->googledrive_nb_part          = $values['googledrive_nb_part'];
        $this->googledrive_mime_type        = $values['googledrive_mime_type'];
        $this->googledrive_account_id       = $values['googledrive_account_id'];
        $this->hubic_nb_part                = $values['hubic_nb_part'];
        $this->hubic_nb_chunk               = $values['hubic_nb_chunk'];
        $this->hubic_position               = $values['hubic_position'];
        $this->hubic_dir                    = $values['hubic_dir'];
        $this->aws_account_id               = $values['aws_account_id'];
        $this->aws_etag                     = $values['aws_etag'];
        $this->aws_nb_part                  = $values['aws_nb_part'];
        $this->aws_position                 = $values['aws_position'];
        $this->aws_upload_id                = $values['aws_upload_id'];
        $this->aws_upload_part              = $values['aws_upload_part'];
        $this->pause_refresh                = $values['pause_refresh'];
        $this->context->language            = new Language($values['id_lang']);

        return true;
    }

    public function getAllValues($close_handle_files)
    {
        //$this->log($this->l('Save values'));

        // Save list of directories
        if (is_resource($this->handle_list_dir_file)) {
            //$this->log($this->l('Save directories'));
            // Empty the file
            ftruncate($this->handle_list_dir_file, 0);
            // Go back to the begining of the file
            rewind($this->handle_list_dir_file);
            // Write new content
            fwrite($this->handle_list_dir_file, serialize($this->list_dir));
        }

        $handle_file_list_file = false;
        $handle_list_dir_file = false;
        $handle_tar_file = false;
        $handle_gz_file = false;
        $handle_bz_file = false;

        if (is_resource($this->handle_file_list_file)) {
            if ($close_handle_files) {
                fclose($this->handle_file_list_file);
            }

            $handle_file_list_file = true;
        }

        if (is_resource($this->handle_list_dir_file)) {
            if ($close_handle_files) {
                fclose($this->handle_list_dir_file);
            }

            $handle_list_dir_file = true;
        }

        if (is_resource($this->handle_tar_file)) {
            if ($close_handle_files) {
                fclose($this->handle_tar_file);
            }

            $handle_tar_file = true;
        }

        if (is_resource($this->handle_gz_file)) {
            if ($close_handle_files) {
                fclose($this->handle_gz_file);
            }

            $handle_gz_file = true;
        }

        if (is_resource($this->handle_bz_file)) {
            if ($close_handle_files) {
                fclose($this->handle_bz_file);
            }

            $handle_bz_file = true;
        }

        //$this->log($this->l('Values saved'));

        $values = array(
            'log_file'                      => $this->log_file,
            'lastlog_file'                  => $this->lastlog_file,
            'list_files_file'               => $this->list_files_file,
            'handle_file_list_file'         => $handle_file_list_file,
            'list_dir_file'                 => $this->list_dir_file,
            'handle_list_dir_file'          => $handle_list_dir_file,
            'backup_folder'                 => $this->backup_folder,
            'dump_file'                     => $this->dump_file,
            'tar_file'                      => $this->tar_file,
            'handle_tar_file'               => $handle_tar_file,
            'tar_files_size'                => $this->tar_files_size,
            'compressed_file'               => $this->compressed_file,
            'bzip2'                         => $this->bzip2,
            'id_shop'                       => $this->id_shop,
            'id_shop_group'                 => $this->id_shop_group,
            'date_format'                   => $this->date_format,
            'hour_format'                   => $this->hour_format,
            'date_start'                    => $this->date_start,
            'hour_start'                    => $this->hour_start,
            'total_files'                   => $this->total_files,
            'files_done'                    => $this->files_done,
            'old_percent'                   => $this->old_percent,
            'base_length'                   => $this->base_length,
            'backup_name'                   => $this->backup_name,
            'backup_name_date'              => $this->backup_name_date,
            'norm_backup_file'              => $this->norm_backup_file,
            'norm_tar_file'                 => $this->norm_tar_file,
            'norm_compressed_file'          => $this->norm_compressed_file,
            'norm_log_file'                 => $this->norm_log_file,
            'norm_lastlog_file'             => $this->norm_lastlog_file,
            'source_dir'                    => $this->source_dir,
            'part_file'                     => $this->part_file,
            'part_size'                     => $this->part_size,
            'part_number'                   => $this->part_number,
            'part_list'                     => $this->part_list,
            'total_size'                    => $this->total_size,
            'module_path'                   => $this->module_path,
            'module_path_physic'            => $this->module_path_physic,
            'warnings'                      => $this->warnings,
            'ps_shop_enable'                => $this->ps_shop_enable,
            'next_step'                     => $this->next_step,
            'num_file_to_compress'          => $this->num_file_to_compress,
            'dump_tables'                   => $this->dump_tables,
            'dump_percent_lines'            => $this->dump_percent_lines,
            'dump_total_lines'              => $this->dump_total_lines,
            'dump_table_total_lines'        => $this->dump_table_total_lines,
            'dump_table_total_lines_done'   => $this->dump_table_total_lines_done,
            'dump_total_tables_done'        => $this->dump_total_tables_done,
            'dump_tables_to_ignore'         => $this->dump_tables_to_ignore,
            'handle_gz_file'                => $handle_gz_file,
            'handle_bz_file'                => $handle_bz_file,
            'compress_tar_position'         => $this->compress_tar_position,
            'compress_size_done'            => $this->compress_size_done,
            'compress_total_size'           => $this->compress_total_size,
            'list_files_to_add'             => $this->list_files_to_add,
            'nb_file_in_list_to_add'        => $this->nb_file_in_list_to_add,
            'position_list_file'            => $this->position_list_file,
            'array_files_to_add'            => $this->array_files_to_add,
            'dropbox_upload_id'             => $this->dropbox_upload_id,
            'dropbox_position'              => $this->dropbox_position,
            'dropbox_dir'                   => $this->dropbox_dir,
            'dropbox_nb_part'               => $this->dropbox_nb_part,
            'dropbox_account_id'            => $this->dropbox_account_id,
            'onedrive_session'              => $this->onedrive_session,
            'onedrive_position'             => $this->onedrive_position,
            'onedrive_nb_part'              => $this->onedrive_nb_part,
            'onedrive_account_id'           => $this->onedrive_account_id,
            'owncloud_session'              => $this->owncloud_session,
            'owncloud_position'             => $this->owncloud_position,
            'owncloud_nb_part'              => $this->owncloud_nb_part,
            'owncloud_nb_chunk'             => $this->owncloud_nb_chunk,
            'owncloud_account_id'           => $this->owncloud_account_id,
            'webdav_session'                => $this->webdav_session,
            'webdav_position'               => $this->webdav_position,
            'webdav_nb_part'                => $this->webdav_nb_part,
            'webdav_nb_chunk'               => $this->webdav_nb_chunk,
            'webdav_account_id'             => $this->webdav_account_id,
            'ftp_dir'                       => $this->ftp_dir,
            'ftp_nb_part'                   => $this->ftp_nb_part,
            'ftp_position'                  => $this->ftp_position,
            'ftp_account_id'                => $this->ftp_account_id,
            'googledrive_session'           => $this->googledrive_session,
            'googledrive_position'          => $this->googledrive_position,
            'googledrive_nb_part'           => $this->googledrive_nb_part,
            'googledrive_mime_type'         => $this->googledrive_mime_type,
            'googledrive_account_id'        => $this->googledrive_account_id,
            'hubic_nb_part'                 => $this->hubic_nb_part,
            'hubic_nb_chunk'                => $this->hubic_nb_chunk,
            'hubic_position'                => $this->hubic_position,
            'hubic_dir'                     => $this->hubic_dir,
            'aws_account_id'                => $this->aws_account_id,
            'aws_etag'                      => $this->aws_etag,
            'aws_nb_part'                   => $this->aws_nb_part,
            'aws_position'                  => $this->aws_position,
            'aws_upload_id'                 => $this->aws_upload_id,
            'aws_upload_part'               => $this->aws_upload_part,
            'pause_refresh'                 => $this->pause_refresh,
            'id_lang'                       => $this->context->language->id,
            'resume_ingoing'                => $this->resume_ingoing,
        );

        return $values;
    }

    public function writeAllValues($close_handle_files = true)
    {
        $values = $this->getAllValues($close_handle_files);

        foreach ($values as &$value) {
            $value = Tools::jsonEncode($value);
        }

        if (!($handle_config_file = fopen($this->config_file, 'w+'))) {
            $this->log('ERR'.$this->l('The config file cannot be opened'));
        }

        if (!fwrite($handle_config_file, serialize($values))) {
            $this->log('ERR'.$this->l('The config file cannot be written'));
        }

        fclose($handle_config_file);
    }

    /**
    * Join files in a TAR file
    *
    * @param string $source Source file path, a directory or a single file.
    * @return bool
    */
    protected function tar($source = _PS_ROOT_DIR_)
    {
        $this->tar_time = time();

        if (!$this->refresh || $this->next_step == self::STEP_LIST_FILES) {
            // Delete the file with the list of directories to check
            if (file_exists($this->list_dir_file)) {
                $this->fileDelete($this->list_dir_file);
            }

            // Delete the file with the list of files to tar
            if (file_exists($this->list_files_file)) {
                $this->fileDelete($this->list_files_file);
            }

            // Delete the tar file
            if (file_exists($this->tar_file)) {
                $this->fileDelete($this->tar_file);
            }

            $this->source_dir = $this->normalizePath($source);

            //Create file to list all directories to check
            if (!($this->handle_list_dir_file = fopen($this->list_dir_file, 'a+'))) {
                $this->log('ERR'.$this->l('Error while listing files, unable to create directory file'));
                return false;
            }

            if (chmod($this->list_dir_file, octdec(self::PERM_FILE)) !== true) {
                $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->list_dir_file, self::PERM_FILE));
            }

            if (is_dir($source)) {
                $this->list_dir[] = $source;
                $this->base_length = $this->getLength($source);
            } else {
                $this->base_length = $this->getLength(dirname($source));
            }

            //Create file to list all the files to tar
            if (!($this->handle_file_list_file = fopen($this->list_files_file, 'a+'))) {
                $this->log('ERR'.$this->l('Error while listing files, unable to create listing file'));
                return false;
            }

            if (chmod($this->list_files_file, octdec(self::PERM_FILE)) !== true) {
                $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->list_files_file, self::PERM_FILE));
            }

            //Create tar file
            if (!($this->handle_tar_file = fopen($this->tar_file, 'wb'))) {
                $this->log('ERR'.$this->l('Error while backuping files, unable to create tar file'));
                return false;
            }

            if (chmod($this->tar_file, octdec(self::PERM_FILE)) !== true) {
                $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->tar_file, self::PERM_FILE));
            }

            $this->next_step = self::STEP_LIST_FILES_CONTINUE;
            $this->log($this->l('Listing files...'));
            $this->list_files_to_add = '';
            $this->position_list_file = 0;
            $this->array_files_to_add = array();
        }

        if (!$this->refresh || $this->next_step == self::STEP_LIST_FILES_CONTINUE) {
            $list_suffix = array(
                $this->suffix_backup,
                $this->suffix_backup_base,
                $this->suffix_backup_file
            );

            $this->norm_tar_file = str_replace($list_suffix, '', $this->normalizePath($this->tar_file));
            $this->norm_backup_file = str_replace($list_suffix, '', $this->normalizePath($this->backup_folder.$this->backup_name));
            $this->norm_compressed_file = str_replace($list_suffix, '', $this->normalizePath($this->compressed_file));
            $this->norm_log_file = $this->normalizePath($this->log_file);
            $this->norm_lastlog_file = $this->normalizePath($this->lastlog_file);

            if (count($this->list_dir)) {
                $this->countAllFiles();
            }

            if (!is_dir($source)) {
                fwrite($this->handle_file_list_file, $source."\n");
                $this->total_files = 1;
                $this->log($this->l('Listing files...').' '.$this->total_files.' '.$this->l('found'));
            }

            $this->files_done = 0;
            $this->old_percent = 0;

            $this->log($this->l('Backuping files...'));
        }

        $this->next_step = self::STEP_BACKUP_FILES;

        if ($this->next_step == self::STEP_BACKUP_FILES) {
            //Tar all files
            $this->tarAllFiles();
        }

        fclose($this->handle_list_dir_file);
        fclose($this->handle_file_list_file);

        //End of archive
        $this->w('', 1024);

        //Close tar file
        fclose($this->handle_tar_file);

        // Delete the file with the list of files to tar
        if (file_exists($this->list_files_file)) {
            $this->fileDelete($this->list_files_file);
        }

        // Delete the file with the list of directories to check
        if (file_exists($this->list_dir_file)) {
            $this->fileDelete($this->list_dir_file);
        }

        return true;
    }

    /**
     * tarAllFiles()
     *
     * Tar all files of a directory and its subdirectories
     *
     * @param string $directory Base directory
     * @return void
     *
     */
    protected function tarAllFiles()
    {
        if (!count($this->array_files_to_add)) {
            $this->getFilesList();
        }

        $count_nb_file = count($this->array_files_to_add);
        if ($count_nb_file) {
            foreach ($this->array_files_to_add as $key => $file) {
                if ($this->tarThisFile($file)) {
                    unset($this->array_files_to_add[$key]);

                    $time_between_refresh = $this->getConfig('NTBR_TIME_BETWEEN_REFRESH');
                    if ($time_between_refresh <= 0) {
                        $time_between_refresh = self::MAX_TIME_BEFORE_REFRESH;
                    }

                    if (!$this->getConfig('NTBR_DISABLE_REFRESH')
                        && (time() - $this->tar_time >= $time_between_refresh)
                    ) {
                        $this->refreshBackup();
                    } else {
                        $this->checkStopScript();
                    }
                }
            }
            $this->array_files_to_add = array();
            $this->tarAllFiles();
        }

        return true;
    }

    public function getFilesList()
    {
        if ($this->getFileSize($this->list_files_file) <= 0) {
            return false;
        }

        if (!is_resource($this->handle_file_list_file)) {
            return false;
        }

        fseek($this->handle_file_list_file, $this->position_list_file);
        while (!feof($this->handle_file_list_file)) {
            $file = rtrim(fgets($this->handle_file_list_file));

            if ($file != '') {
                $this->array_files_to_add[] = $file;
                $this->nb_file_in_list_to_add++;
            }

            if ($this->nb_file_in_list_to_add >= self::MAX_LINE_BEFORE_ADD_TO_TAR) {
                $this->nb_file_in_list_to_add = 0;
                $this->position_list_file = ftell($this->handle_file_list_file);
                return true;
            }
        }

        $this->nb_file_in_list_to_add = 0;
        $this->position_list_file = ftell($this->handle_file_list_file);

        return true;
    }

    public function getLastLine()
    {
        if ($this->getFileSize($this->list_files_file) <= 0) {
            return false;
        }

        if (!$this->handle_file_list_file) {
            return false;
        }

        // Ignore symbol end of file
        $pos = -2;
        $line = '';
        $c = '';
        do {
            $line = $c.$line;
            fseek($this->handle_file_list_file, $pos--, SEEK_END);
            $c = fgetc($this->handle_file_list_file);
            $current_pos = ftell($this->handle_file_list_file);
        } while ($c != "\n" && $current_pos > 1);

        if ($current_pos == 1) {
            $line = $c.$line;
            $current_pos--;
        }

        ftruncate($this->handle_file_list_file, $current_pos);

        return trim($line);
    }

    /**
     * tarThisFile()
     *
     * Add a file to a tar archive
     *
     * @param string $current_file The file to tar
     * @return void
     *
     */
    protected function tarThisFile($current_file)
    {
        $this->files_done++;

        //Compute percentage progression
        //if (!$this->getConfig('IGNORE_FILES_COUNT')) {
        $percent = ($this->files_done * 100) / $this->total_files;
        if ($percent >= $this->old_percent + 1) {
            $this->old_percent = round($percent, 0);
            $this->log($this->l('Backuping files:').' '.round($percent, 0).'%');
        }
        //}

        //Normalize path
        $current_normalized_file = $this->normalizePath($current_file);
        //Find relative filename
        $filename = ltrim($this->getPart($current_normalized_file, $this->base_length), '/');

        //Open the file
        if (($file_read = fopen($current_file, 'rb')) === false) {
            $this->log($this->l('File').' '.$current_file.' '.$this->l('ignored because the module can not open it, please check its rights and user owner'));
            return true;
        }

        //File information
        $filename_length = $this->getLength($filename);
        $info = $this->tarFileInfo($current_file);

        if ($info['size'] > 8589934591) {//77777777777 in octal
            $this->log('ERR'.$this->l('Tar file cannot contain file larger than 8 GB:').$current_file);
            return false;
        }

        if (!isset($this->tar_files_size[$this->part_number])) {
            $this->tar_files_size[$this->part_number] = 0;
        }

        //Check if future tar file size bigger than authorized
        if ($this->part_size > 0) {
            //Tar file should not be bigger than part_size
            if (($this->tar_files_size[$this->part_number] + $info['size']) > $this->part_size) {
                //The tar file will be too big, we need to close it and use a new one
                $this->w('', 1024);
                //Close tar file
                fclose($this->handle_tar_file);
                if ($this->part_number == 1) {
                    rename($this->tar_file, $this->part_file.'.1.part.tar');
                    $this->part_list = array($this->part_file.'.1.part.tar');
                }
                $this->part_number++;
                if (!isset($this->tar_files_size[$this->part_number])) {
                    $this->tar_files_size[$this->part_number] = 0;
                }
                $this->tar_file = $this->part_file.'.'.$this->part_number.'.part.tar';
                $this->part_list[] = $this->tar_file;
                //Create tar file
                if (!($this->handle_tar_file = fopen($this->tar_file, 'ab'))) {
                    $this->log('ERR'.$this->l('Error while backuping files, unable to create tar file'));
                    return false;
                }

                // Make sur the file has the correct right
                if (chmod($this->tar_file, octdec(self::PERM_FILE)) !== true) {
                    $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $this->tar_file, self::PERM_FILE));
                }
            }
        }

        //A long filename has at least 2 blocks before normal size filename
        if ($filename_length > 100) {
            $header = $this->w('././@LongLink', 100); //name
            $header .= $this->w($info['mode'], 8);
            $header .= $this->w($info['uid'], 8);
            $header .= $this->w($info['gid'], 8);
            $header .= $this->w(str_pad(decoct($filename_length + 1), 11, ' ', STR_PAD_LEFT).' ', 12);
            $header .= $this->w($info['mtime'], 12);
            $checksum = $this->tarChecksum($header.'        '.'L');
            $this->w($checksum, 8);
            $this->w('L', 1); //It's a long name
            $this->w('', 355); //Not supported headers

            //Long name data
            $rest = $filename;
            do {
                $this->w($this->getPart($rest, 0, 512), 512);
                if ($this->getLength($rest) < 512) {
                    break;
                }
                $rest = $this->getPart($rest, 512);
            } while ($this->getLength($rest) > 0);
        }

        //Normal size filename
        $header = $this->w($this->getPart($filename, 0, 100), 100); //Filename
        $header .= $this->w($info['mode'], 8);
        $header .= $this->w($info['uid'], 8);
        $header .= $this->w($info['gid'], 8);
        //Max file size inside tar is 7.999 GB
        $header .= $this->w(str_pad(base_convert($info['size'], 10, 8), 11, ' ', STR_PAD_LEFT).' ', 12);
        $header .= $this->w($info['mtime'], 12);
        $checksum = $this->tarChecksum($header.'        '.'0');
        $this->w($checksum, 8);
        $this->w('0', 1); //It's a file
        $this->w('', 355); //Not supported headers

        if ($info['size'] > 0) {
            //Data of the file
            $leftsize = $info['size'];
            $blocksize = 512;
            do {
                //Read data
                $leftsize -= $blocksize;
                if ($leftsize < 0) {
                    $blocksize += $leftsize;
                }
                if (($block = fread($file_read, $blocksize)) === false) {
                    $this->log('ERR'.$this->l('The module was unable to read the file').' '.$current_file.', '.$this->l('please check its rights and user owner'));
                    fclose($file_read);
                    fclose($this->handle_tar_file);
                    return false;
                }
                //Write data
                if ($block !== '') {
                    $this->w($block, 512);
                }
            } while ($leftsize > 0);
        }

        //Close file
        fclose($file_read);

        return true;
    }

    /**
     * tarFileInfo()
     *
     * Return information on file for tar
     *
     * @param string $path Path of the file or directory
     * @return array Information
     *
     */
    protected function tarFileInfo($path, $dir = false)
    {
        $info = array();
        $stat = stat($path);
        if ($stat === false) {//Unable to detect file information, so we use default value
            $info['uid'] = '     0 ';
            $info['gid'] = '     0 ';
            if ($dir) {
                $info['mode'] = '000755 ';
                $info['size'] = 0;
            } else {
                $info['mode'] = '000644 ';
                $info['size'] = $this->getFileSize($path);
            }
            $info['mtime'] = decoct(time()).' ';
        } else {
            $info['mode'] = decoct($stat['mode']).' ';
            $info['uid'] = str_pad((int)$stat['uid'], 6, ' ', STR_PAD_LEFT).' ';
            if (Tools::strlen($info['uid']) > 7) {
                $info['uid'] = '     0 ';
            }
            $info['gid'] = str_pad((int)$stat['gid'], 6, ' ', STR_PAD_LEFT).' ';
            if (Tools::strlen($info['gid']) > 7) {
                $info['gid'] = '     0 ';
            }
            if ($dir) {
                $info['size'] = 0;
            } else {
                if ($stat['size'] > 0) {
                    $info['size'] = $stat['size'];
                } else {//Probably a big file
                    $info['size'] = $this->getFileSize($path);
                }
            }
            $info['mtime'] = decoct($stat['mtime']).' ';
        }

        return $info;
    }

    /**
     * tarChecksum()
     *
     * Calculate checksum for the tar current file
     *
     * @param string $header Header of the current file
     * @return string Checksum
     *
     */
    protected function tarChecksum($header)
    {
        $header_split = str_split($header);
        $sum = 0;
        foreach ($header_split as $char) {
            $sum += ord($char);
        }
        return str_pad(decoct($sum), 6, ' ', STR_PAD_LEFT).' ';
    }

    /**
     * countAllFiles()
     *
     * Count all files of a directory and its subdirectories
     *
     * @param string $directory Base directory
     * @return int Number of files found
     *
     */
    protected function countAllFiles()
    {
        $old_total_files = $this->total_files;
        $max_file_to_backup = $this->getConfig('NTBR_MAX_FILE_TO_BACKUP')*1024*1024;
        // Get list of directories not done yet.
        $temp_array = $this->list_dir;

        foreach ($temp_array as $key => $directory) {
            $ignore_folder = false;

            $time_between_refresh = $this->getConfig('NTBR_TIME_BETWEEN_REFRESH');
            if ($time_between_refresh <= 0) {
                $time_between_refresh = self::MAX_TIME_BEFORE_REFRESH;
            }

            if (!$this->getConfig('NTBR_DISABLE_REFRESH') && (time() - $this->tar_time >= $time_between_refresh)) {
                $this->list_dir = $temp_array;

                // If there is some files to add, do it before the refresh
                if ($this->list_files_to_add) {
                    fwrite($this->handle_file_list_file, $this->list_files_to_add);
                    $this->nb_file_in_list_to_add = 0;
                    $this->list_files_to_add = '';
                }

                $this->refreshBackup();
            } else {
                $this->checkStopScript();
            }

            $directory = rtrim($this->normalizePath($directory), '/').'/';

            $directories_to_ignore = $this->getConfig('NTBR_IGNORE_DIRECTORIES');

            if (trim($directories_to_ignore) != '') {
                $list_directories_to_ignore = explode(',', $directories_to_ignore);

                foreach ($list_directories_to_ignore as $directory_to_ignore) {
                    if ($directory == $this->source_dir.'/'.trim($directory_to_ignore).'/') {
                        $ignore_folder = true;
                        continue;
                    }
                }
            }

            if ($ignore_folder) {
                unset($temp_array[$key]);
                continue;
            }

            if (($dir = opendir($directory)) !== false) {
                $nb_file = 0;
                // Get content of the directory
                while (($file = readdir($dir)) !== false) {
                    $ignore_this_file = false;

                    //Normalize path
                    $current_normalized_file = $this->normalizePath($directory.$file);
                    //Find relative filename
                    $filename = ltrim($this->getPart($current_normalized_file, $this->base_length), '/');
                    // Find admin directory
                    $admin_dir = $this->getConfig('NTBR_ADMIN_DIR');

                    $files_types_to_ignore = $this->getConfig('NTBR_IGNORE_FILES_TYPES');
                    $file_extension = strrchr($file, '.');

                    if (trim($files_types_to_ignore) != '') {
                        $list_files_types_to_ignore = explode(',', $files_types_to_ignore);

                        foreach ($list_files_types_to_ignore as $type_to_ignore) {
                            if ($file_extension == trim($type_to_ignore)) {
                                $this->log($this->l('File').' '.$filename.' '.$this->l('ignored because its extension is in the list to ignore').' ('.$file_extension.')');
                                $ignore_this_file = true;
                                continue;
                            }
                        }
                    }

                    // If file is a temporary file (.nfs...) we do not backup it
                    if (Tools::substr(Tools::strtolower($file_extension), 0, 4) === '.nfs') {
                        $this->log($this->l('File').' '.$filename.' '.$this->l('ignored because it is a temporary file (.nfs)'));
                        $ignore_this_file = true;
                    }

                    //Ignore some files
                    $matches = array();
                    // If file name content only "."
                    preg_match('/^\.*$/', basename($current_normalized_file), $matches);

                    if (count($matches)) {
                        $ignore_this_file = true;
                    } elseif ((
                        strpos($current_normalized_file, 'index.php') === false
                        && strpos($current_normalized_file, '.htaccess') === false
                        )
                        && (
                            $current_normalized_file == $this->norm_tar_file
                            || preg_match('/'.$this->name.'\/'.self::BACKUP_FOLDER.'\/.*\.tar(\.gz)?$/i', $current_normalized_file)
                            || stripos($current_normalized_file, $this->norm_backup_file) !== false
                            || $current_normalized_file == $this->norm_compressed_file
                            || $current_normalized_file == $this->norm_log_file
                            || $current_normalized_file == $this->norm_lastlog_file
                            || $current_normalized_file == $this->source_dir.'/lastlog.txt'
                            || $current_normalized_file == $this->source_dir.'/log.txt'
                            || $current_normalized_file == $this->source_dir.'/restore.php'
                            || $current_normalized_file == $this->source_dir.'/modules/iziflux/log/logExport.txt'
                            || $current_normalized_file == $this->source_dir.'/modules/iziflux/log.txt'
                            || $current_normalized_file == $this->source_dir.'/modules/totshippingpreview/log.txt'
                            || $current_normalized_file == $this->normalizePath($this->list_files_file)
                            || strpos($current_normalized_file, $this->source_dir.'/var/cache/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/app/cache/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/cache/cachefs/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/cache/pagecache/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/modules/advancedexportwodp/backups/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/modules/prestabackupwodp/backups/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/modules/blocklayered/cache/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/cache/smarty/cache/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/cache/smarty/compile/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/img/tmp/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/upload/') !== false
                            || strpos($current_normalized_file, $this->source_dir.'/test_dossier/') !== false
                            || strpos($current_normalized_file, 'autoupgrade/backup/') !== false
                            || strpos($current_normalized_file, 'autoupgrade/download/') !== false
                            || strpos($current_normalized_file, 'autoupgrade/latest/') !== false
                            || strpos($current_normalized_file, 'autoupgrade/tmp/') !== false
                            || strpos($current_normalized_file, $admin_dir.'/backups/') !== false
                            || preg_match('/'. str_replace('/', '\/', $this->source_dir).'\/themes\/[^\/]*\/cache\//', $current_normalized_file) !== 0
                        )
                    ) {
                        $ignore_this_file = true;
                    }

                    //Check if it is a product image
                    if ($this->ignoreProductImage($current_normalized_file, $filename)) {
                        //It is a product image
                        if ($this->getConfig('ACTIVATE_LOG')) {
                            $this->log($this->l('File').' '.$filename.' '.$this->l('ignored because it is a product image'));
                        }
                        $ignore_this_file = true;
                    }

                    // Check if the file size is equal or larger that max file size to backup
                    if ($max_file_to_backup) {
                        $file_size = $this->getFileSize($directory.$file);
                        if ($file_size >= $max_file_to_backup) {
                            $this->log($this->l('File').' '.$filename.' '.$this->l('ignored because it is equal or larger than max file size to backup').' ('.$file_size.')');
                            $ignore_this_file = true;
                        }
                    }

                    if ($file == '.' || $file == '..' || $ignore_this_file) {
                        continue;
                    }

                    if (is_dir($directory.$file)) {
                        $temp_array[] = $directory.$file;
                    } else {
                        $nb_file++;
                        $this->list_files_to_add .= $directory.$file."\n";
                        $this->nb_file_in_list_to_add++;

                        if ($this->nb_file_in_list_to_add >= self::FILE_MAX_LINE_WRITE) {
                            fwrite($this->handle_file_list_file, $this->list_files_to_add);
                            $this->nb_file_in_list_to_add = 0;
                            $this->list_files_to_add = '';
                        }

                        $this->total_files++;
                        if ($old_total_files + 1000 < $this->total_files) {
                            $old_total_files = $this->total_files;
                            $this->log($this->l('Listing files...').' '.$this->total_files.' '.$this->l('found'));
                        }
                    }
                }
                closedir($dir);

                if ($nb_file <= 0) {
                    $res_touch = @touch($directory.'/index.php');

                    if (!$res_touch) {
                        $this->log('WAR'.$this->l('Unable to write in this directory, please check rights:').' '.$directory);
                    }
                }
            } else {
                if ($this->getConfig('ACTIVATE_LOG')) {
                    $this->log($this->l('Directory').' '.$directory.' '.$this->l('ignored because the module can not open it, please check its rights and user owner'));
                }
            }

            // We have list the content of the directory. We remove it from of list of directories to do
            unset($temp_array[$key]);
        }

        $this->list_dir = $temp_array;

        if (count($this->list_dir)) {
            $this->countAllFiles();
        }

        // If there is still some files (but less than self::FILE_MAX_LINE_WRITE)
        if ($this->list_files_to_add) {
            fwrite($this->handle_file_list_file, $this->list_files_to_add);
            $this->nb_file_in_list_to_add = 0;
            $this->list_files_to_add = '';
        }

        return true;
    }

    /**
     * fileDelete()
     *
     * Delete a file
     *
     * @param string $path Path of the file to delete
     * @return boolean
     *
     */
    protected function fileDelete($path)
    {
        if (is_file($path)) {
            $unlink = unlink($path);

            if (!$unlink) {
                $this->log('WAR'.$this->l('The following file was not deleted').' '.$path);
            }

            return $unlink;
        } else {
            return false;
        }
    }

    /**
     * readableFileSize()
     *
     * Return a human readable file size
     *
     * @param string $path Path of the file
     * @return string
     *
     */
    public function readableFileSize($path)
    {
        $bytes = $this->getFileSize($path);
        $kb    = pow(2, 10);
        $mb    = $kb * pow(2, 10);
        $gb    = $mb * pow(2, 10);

        if ($bytes >= $gb) {
            $filesize = number_format($bytes / $gb, 2).' '.$this->l('GB');
        } elseif ($bytes >= $mb) {
            $filesize = number_format($bytes / $mb, 2).' '.$this->l('MB');
        } else {
            $filesize = number_format($bytes / $kb, 2).' '.$this->l('KB');
        }

        return $filesize;
    }

    /**
     * readableSize()
     *
     * Return a human readable size
     *
     * @param float $bytes Size we want to read
     * @return string
     *
     */
    public function readableSize($bytes)
    {
        $kb    = pow(2, 10);
        $mb    = $kb * pow(2, 10);
        $gb    = $mb * pow(2, 10);

        if ($bytes >= $gb) {
            $filesize = number_format($bytes / $gb, 2).' '.$this->l('GB');
        } elseif ($bytes >= $mb) {
            $filesize = number_format($bytes / $mb, 2).' '.$this->l('MB');
        } else {
            $filesize = number_format($bytes / $kb, 2).' '.$this->l('KB');
        }

        return $filesize;
    }

    public function getMimeType($file_path)
    {
        $name = basename($file_path);
        $extension = Tools::substr(strrchr($name, '.'), 1);

        $mime_type = '';

        switch ($extension) {
            case 'tar':
                $mime_type = 'application/x-tar';
                break;
            case 'gz':
                $mime_type = 'application/x-gzip';
                break;
            case 'bz2':
                $mime_type = 'application/x-bzip';
                break;
            case 'php':
                $mime_type = 'application/x-php';
                break;
            default:
                if (function_exists("finfo_file")) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file_path);
                    finfo_close($finfo);
                }
        }

        return $mime_type;
    }

    /**
     * Based on https://github.com/jkuchar/BigFileTools/blob/master/class/BigFileTools.php
     * Return file size (even for file > 2 Gb)
     *
     * @param string $path Path of the file
     * @return mixed File size or false if error
     */
    public function getFileSize($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $real_path = realpath($path);

        if (!$real_path) {
            return false;
        }

        if (function_exists("curl_init")) {
            $ch = curl_init('file://'.rawurlencode($real_path));
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $data = curl_exec($ch);
            curl_close($ch);
            if ($data !== false && preg_match('/Content-Length: (\d+)/', $data, $matches)) {
                return $matches[1];
            }
        }

        $size = filesize($real_path);

        if (!($file = fopen($real_path, 'rb'))) {
            return false;
        }

        if ($size >= 0) {
            //Check if it really is a small file (< 2 GB)
            if (fseek($file, 0, SEEK_END) === 0) {
                //It really is a small file
                fclose($file);
                return $size;
            }
        }

        //From now on, we are sure this is a big file
        //Quickly jump to the first 2 GB with fseek. After that fseek is not working on 32 bit php (it uses int internally)
        $size = PHP_INT_MAX - 1;
        if (fseek($file, PHP_INT_MAX - 1) !== 0) {
            fclose($file);
            return false;
        }

        $length = 1024 * 1024;
        while (!feof($file)) {
            //Read the file until end
            $read = fread($file, $length);
            $size = bcadd($size, $length);
        }
        $size = bcsub($size, $length);
        $size = bcadd($size, Tools::strlen($read));

        fclose($file);
        return $size;
    }

    /**
     * Download a file
     *
     * @param   String  $path       Path of the file to download
     * @param   String  $mime       Type/mime of the file to download
     * @param   String  $filename   New name of the file to download (optional)
     *
     */
    public function downloadFile($path, $mime, $filename = '')
    {
        //check if file exists
        if (is_dir($path) || !file_exists($path)) {
            header('HTTP/1.0 404 Not Found');
            die('404 Not Found');
        }

        if ($filename == '') {
            $filename = basename($path);
        }

        $filesize = $this->getFileSize($path);

        //Disable the compression else Content-Length won't be use
        //apache_setenv('no-gzip', 1);
        ini_set('zlib.output_compression', 0);
        //Prepare headers for the download
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0, no-store, no-cache, public, max-age=0');
        header('Pragma: no-cache, public');
        header('Expires: 0');
        header('Content-Length: '.$filesize);
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Transfer-Encoding: binary');

        $done = 0;
        $to_read = 1024 * 8;

        //Read the file content and send it on the standart output
        flush();
        $file = fopen($path, 'rb');
        if ($file) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            while (!feof($file)) { //Read part of the file
                print (fread($file, $to_read));
                // flush the content to the browser
                flush();
                $done += $to_read;

                if ($done > $filesize) {
                    $done = $filesize;
                }
                //We check if the client is still downloading the file or if he stop and close his browser
                //There is no more connection, it's pointless to pursue, we stop the script
                if (connection_status() != 0) {
                    fclose($file);
                    $this->log($this->l('Download interrupted for the file:').' '.$filename.' ('.$done.'/'.$filesize.')');
                    die();
                }
            }
            fclose($file);
            $this->log($this->l('File downloaded:').' '.$filename.' ('.$done.'/'.$filesize.')');
        }
    }

    public function validRefresh($loop)
    {
        if ($this->getConfig('NTBR_DISABLE_REFRESH')) {
            return false;
        }

        $time_spend = time() - $this->total_time;

        if ($time_spend <= self::MIN_TIME_BEFORE_REFRESH) {
            //$this->log('RESUME');
            return false;
        }

        $time_between_refresh = $this->getConfig('NTBR_TIME_BETWEEN_REFRESH');
        if ($time_between_refresh <= 0) {
            $time_between_refresh = self::MAX_TIME_BEFORE_REFRESH;
        }

        // If we are in a loop we only do backup if enough time has passed since the last backup
        if ($loop && $time_spend < $time_between_refresh) {
            return false;
        }

        return true;
    }

    public function stopScript()
    {
        $handle = fopen(_PS_MODULE_DIR_.$this->name.'/'.self::STOP_FILE, 'w');
        fclose($handle);
    }

    protected function checkStopScript()
    {
        if (file_exists(_PS_MODULE_DIR_.$this->name.'/'.self::STOP_FILE)) {
            $this->log('ERR'.$this->l('The backup was stopped manually'));
            $this->fileDelete(_PS_MODULE_DIR_.$this->name.'/'.self::STOP_FILE);
            exit();
        }
    }

    public function refreshBackup($loop = false, $check_validity = true)
    {
        $this->checkStopScript();

        // Check if we should do the refresh
        if ($check_validity) {
            if (!$this->validRefresh($loop)) {
                return false;
            }
        }

        $this->resume_ingoing = 0;

        $this->writeAllValues();

        //$this->setConfig('NTBR_NTBACKUPANDRESTORE_VALUES', Tools::jsonEncode($values));
        if ($this->cron) {
            $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            if (strpos($url, '&refresh=true') === false) {
                $url .= '&refresh=true';
            }

            Tools::redirect($url, __PS_BASE_URI__, null, array('HTTP/1.1 303 See other'));

            //header('HTTP/1.1 303 See other');
            //header('Location: ' . $url);
        }

        $this->log('REFRESH');
        exit();
        die();
    }

    /**
     * generateUrls()
     *
     * Return the generate URLs
     *
     * @return String
     *
     */
    public function generateUrls($temp = false, $id_shop_group = null, $id_shop = null)
    {
        $sel_dyn    = Tools::passwdGen(25);
        $secure_key = Tools::passwdGen(50);
        $hash       = hash('sha512', $secure_key.$this->secure_key.$sel_dyn);

        if ($id_shop_group == null) {
            $id_shop_group = $this->id_shop_group;
        }

        if ($id_shop == null) {
            $id_shop = $this->id_shop;
        }

        $shop_domain = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost();
        $physic_path_modules = realpath(_PS_ROOT_DIR_.'/modules').'/';
        $url_modules = $shop_domain.__PS_BASE_URI__.'modules/';
        $url_ajax = $url_modules.$this->name.'/ajax';

        $dir = $physic_path_modules.$this->name.'/ajax';
        $list_files = scandir($dir);

        if ($temp) {
            $this->setConfig('NTBR_SEL_TEMP', $sel_dyn, $id_shop_group, $id_shop);
            $this->setConfig('NTBR_HASH_TEMP', $hash, $id_shop_group, $id_shop);

            return array(
                'link' => $url_ajax.'/download_file.php?secure_key='.$secure_key,
            );
        } else {
            $this->setConfig('NTBR_SEL', $sel_dyn, $id_shop_group, $id_shop);
            $this->setConfig('NTBR_HASH', $hash, $id_shop_group, $id_shop);

            foreach ($list_files as $file) {
                if (stripos($file, 'download_file_') !== false) {
                    unlink($physic_path_modules.$this->name.'/ajax/'.$file);
                }
            }

            $cron_download_file_backup = fopen($physic_path_modules.$this->name.'/ajax/download_file_backup_'.$secure_key.'.php', 'w+');
            fwrite($cron_download_file_backup, '<?php header("Location: '.$url_ajax.'/download_file.php?secure_key='.$secure_key.'&backup&nb=0&id_shop_group='.$id_shop_group.'&id_shop='.$id_shop.'"); exit();');
            fclose($cron_download_file_backup);

            $cron_download_file_log = fopen($physic_path_modules.$this->name.'/ajax/download_file_log_'.$secure_key.'.php', 'w+');
            fwrite($cron_download_file_log, '<?php header("Location: '.$url_ajax.'/download_file.php?secure_key='.$secure_key.'&log&id_shop_group='.$id_shop_group.'&id_shop='.$id_shop.'"); exit();');
            fclose($cron_download_file_log);

            return array(
                'backup' => $url_ajax.'/download_file_backup_'.$secure_key.'.php',
                'log' => $url_ajax.'/download_file_log_'.$secure_key.'.php'
            );
        }
        //$url_ajax.'/download_file_'.$secure_key.'.php';
    }

    /**
     * Decode a XML in array
     *
     * @param   String  $xml    The xml to convert in array.
     *
     * @return  array   The decoded xml.
     */
    public function decodeXml($xml)
    {
        $xml_clean  = preg_replace('~(</?|\s)([a-z0-9_]+):~is', '$1$2_', $xml);
        return Tools::jsonDecode(Tools::jsonEncode((array)simplexml_load_string($xml_clean)), 1);
    }

    /**
     * Return a readable message for a HTTP code
     *
     * @param   int     $http_code  The HTTP code to translate.
     *
     * @return  String   The message of the code HTTP.
     */
    public function getHttpReponseMessage($http_code)
    {
        switch ($http_code) {
            case 400:
                return 'Bad Request';
            case 401:
                return 'Unauthorized';
            case 403:
                return 'Forbidden';
            case 404:
                return 'Not Found';
            case 408:
                return 'Request Time-out';
            case 410:
                return 'Ressource Not Available Anymore';
            case 413:
                return 'Request Entity Too Large';
            case 414:
                return 'Request-URI Too Long';
            case 422:
                return 'Unprocessable entity';
            case 423:
                return 'Locked	WebDAV';
            case 424:
                return 'Method failure WebDAV';
            case 429:
                return 'Too Many Requests';
            case 456:
                return 'Unrecoverable Error WebDAV';
            case 500:
                return 'Internal Server Error';
            case 502:
                return 'Bad Gateway';
            case 503:
                return 'Service Unavailable';
            case 504:
                return 'Gateway Time-out';
            case 507:
                return 'Insufficient storage WebDAV';
            case 508:
                return 'Loop detected WebDAV';
            case 509:
                return 'Bandwidth Limit Exceeded';
        }

        return '';
    }

    /**
     * Execute a curl and return it's result
     *
     * @param   resource    $curl       The curl to execute.
     *
     * @return  array       The result of the execution of the curl.
     */
    public function execCurl($curl)
    {
        $result = array(
            'success'   => true,
            'result'    => '',
            'code_http'      => '',
        );

        $result_curl = curl_exec($curl);

        $result['code_http'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        //if ($result['code_http'] >= 400) {
            //$this->log(curl_getinfo($curl));
            //$this->log($result_curl);
        //}

        if ($result_curl === false) {
            $result['success'] = false;
            $this->log($this->l('Error while executing the curl:').' '.curl_error($curl).'. '.$this->l('Errno:').' '.curl_errno($curl));
        } else {
            if (!is_string($result_curl)) {
                $string_result = print_r($result_curl, true);
                $this->log($string_result);
                $result_curl = $string_result;
            }

            // If response is in xml
            if (strpos($result_curl, '<?xml') !== false) {
                $decoded    = array();
                $array_xml  = $this->decodeXml($result_curl);

                //$this->log($result_curl);
                //$this->log($array_xml);

                if (array_key_exists('s_exception', $array_xml)) {
                    $decoded['error'] = $this->l('Error');

                    if (array_key_exists('s_message', $array_xml)) {
                        $decoded['error'] = $array_xml['s_message'];
                    }
                } else {
                    $decoded = $array_xml;
                }
            } else {
                $decoded = Tools::jsonDecode($result_curl, true);
            }

            if ((empty($decoded) || $decoded == false) && !empty($result_curl)) {
                $decoded = $result_curl;
            }

            if (isset($decoded['error']) || isset($decoded['d_error'])) {
                if (isset($decoded['error_description'])) {
                    $result['result'] = $decoded['error_description'];
                } elseif (isset($decoded['error']['message']) || isset($decoded['error']['d_message'])) {
                    $result['result'] = $decoded['error']['message'];
                } else {
                    $result['result'] = $decoded['error'];
                }

                $result['success'] = false;
                $this->log($decoded['error']);
            } elseif ($result['code_http'] >= 400) {
                $result['success'] = false;
                /*$message_http_code = $this->getHttpReponseMessage($result['code_http']);

                if ($message_http_code != '') {
                    $this->log($message_http_code);
                } else {
                    $this->log($result_curl);
                }*/
            } else {
                $result['result'] = $decoded;
            }
        }

        curl_close($curl);

        return $result;
    }

    /**
     * Return a correct name for a file
     *
     * @param string $name File name
     * @param string $replacement Replacement character for the forbidden one
     *
     * @return string Correct filename
     *
     */
    protected function correctFileName($name, $replacement = '_')
    {
        return preg_replace('/[^a-zA-Z0-9-._]/i', $replacement, $this->replaceAccents($name));
    }

    /**
     * Replace accents
     *
     * @param string $string String
     * @param string $charset Charset used. Default is utf-8
     *
     * @return string String without accents
     */
    protected function replaceAccents($string, $charset = 'utf-8')
    {
        $string = htmlentities($string, ENT_NOQUOTES, $charset);

        $string = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $string);
        $string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string);
        $string = preg_replace('#&[^;]+;#', '', $string);

        return $string;
    }

    public function updateBackupList()
    {
        $backup_files = $this->findOldBackups();

        foreach ($backup_files as &$backup) {
            $backup['nb_part'] = count($backup['part']);
        }

        return $backup_files;
    }

    public function listDirectoryContent($dir)
    {
        $return = array();
        $list_files = array_diff(scandir($dir), array('..', '.'));

        foreach ($list_files as $file) {
            $path_file = $dir.DIRECTORY_SEPARATOR.$file;

            $return[] = array(
                'file'  => $file,
                'path'  => $path_file,
                'perm'  => decoct(fileperms($path_file) & 0777)
            );

            if (is_dir($path_file)) {
                $return = array_merge($return, $this->listDirectoryContent($path_file));
            }
        }

        return $return;
    }

    public function encrypt($pure_string)
    {
        if (!extension_loaded('openssl') || !function_exists('hash_equals')) {
            return false;
        }

        $iv_size = openssl_cipher_iv_length(self::CIPHER_CRYPTAGE);
        $iv = openssl_random_pseudo_bytes($iv_size);
        $encrypted_string = openssl_encrypt($pure_string, self::CIPHER_CRYPTAGE, self::CLE_CRYPTAGE, OPENSSL_RAW_DATA, $iv);

        if ($encrypted_string === false) {
            while ($msg = openssl_error_string()) {
                $this->log($msg);
            }
            return false;
        }

        $hmac = hash_hmac('sha256', $encrypted_string, self::CLE_CRYPTAGE, true);

        return base64_encode($iv.$hmac.$encrypted_string);
    }

    public function decrypt($encrypted_string)
    {
        if (!extension_loaded('openssl') || !function_exists('hash_equals')) {
            return false;
        }

        if ($encrypted_string == '') {
            return $encrypted_string;
        }

        $decode_string = base64_decode($encrypted_string);
        $iv_size = openssl_cipher_iv_length(self::CIPHER_CRYPTAGE);
        $iv = Tools::substr($decode_string, 0, $iv_size, '8bit');
        $hmac = Tools::substr($decode_string, $iv_size, $sha2len = 32, '8bit');
        $ciphertext_raw = Tools::substr($decode_string, $iv_size+$sha2len, false, '8bit');
        $decrypted_string = openssl_decrypt($ciphertext_raw, self::CIPHER_CRYPTAGE, self::CLE_CRYPTAGE, OPENSSL_RAW_DATA, $iv);

        if ($decrypted_string === false) {
            while ($msg = openssl_error_string()) {
                $this->log($msg);
            }
            return false;
        }

        $calcmac = hash_hmac('sha256', $ciphertext_raw, self::CLE_CRYPTAGE, true);

        if (hash_equals($hmac, $calcmac)) {//PHP 5.6+ timing attack safe comparison
            return $decrypted_string;
        }

        return false;
    }

    /**
     * ip_in_range.php - Function to determine if an IP is located in a
     *                   specific range as specified via several alternative
     *                   formats.
     *
     * Network ranges can be specified as:
     * 1. Wildcard format:     1.2.3.*
     * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
     * 3. Start-End IP format: 1.2.3.0-1.2.3.255
     *
     * Return value BOOLEAN : ip_in_range($ip, $range);
     *
     * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
     * 10 January 2008
     * Version: 1.2
     *
     * Source website: http://www.pgregg.com/projects/php/ip_in_range/
     * Version 1.2
     *
     * This software is Donationware - if you feel you have benefited from
     * the use of this tool then please consider a donation. The value of
     * which is entirely left up to your discretion.
     * http://www.pgregg.com/donate/
     *
     * Please do not remove this header, or source attibution from this file.
     */

    // ip_in_range
    // This function takes 2 arguments, an IP address and a "range" in several
    // different formats.
    // Network ranges can be specified as:
    // 1. Wildcard format:     1.2.3.*
    // 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
    // 3. Start-End IP format: 1.2.3.0-1.2.3.255
    // The function will return true if the supplied IP is within the range.
    // Note little validation is done on the range inputs - it expects you to
    // use one of the above 3 formats.
    public static function ipInRange($ip, $range)
    {
        $range_without_mask = explode('/', $range);
        // If the range AND the ip to test are ipv6
        if (isset($range_without_mask[0])
            && filter_var($range_without_mask[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
        ) {
            return false;// The ipv6 test is not working for noaw
            //return self::ipv6InRange($ip, $range);
        // If the range OR the ip to test are ipv6
        } elseif ((isset($range_without_mask[0])
                && filter_var($range_without_mask[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            )
            || (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        ) {
            return false;
        }

        if (strpos($range, '/') !== false) {
            // $range is in IP/NETMASK format
            list($range, $netmask) = explode('/', $range, 2);
            if (strpos($netmask, '.') !== false) {
                // $netmask is a 255.255.0.0 format
                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);
                return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
            } else {
                // $netmask is a CIDR size block
                // fix the range argument
                $x = explode('.', $range);
                while (count($x)<4) {
                    $x[] = '0';
                }
                list($a, $b, $c, $d) = $x;
                $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b, empty($c)?'0':$c, empty($d)?'0':$d);
                $range_dec = ip2long($range);
                $ip_dec = ip2long($ip);

                # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
                #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

                # Strategy 2 - Use math to create it
                $wildcard_dec = pow(2, (32-$netmask)) - 1;
                $netmask_dec = ~ $wildcard_dec;

                return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
            }
        } else {
            // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
            if (strpos($range, '*') !== false) { // a.b.*.* format
                // Just convert to A-B format by setting * to 0 for A and 255 for B
                $lower = str_replace('*', '0', $range);
                $upper = str_replace('*', '255', $range);
                $range = "$lower-$upper";
            }

            if (strpos($range, '-')!==false) { // A-B format
                list($lower, $upper) = explode('-', $range, 2);
                $lower_dec = (float)sprintf("%u", ip2long($lower));
                $upper_dec = (float)sprintf("%u", ip2long($upper));
                $ip_dec = (float)sprintf("%u", ip2long($ip));
                return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
            }

            //echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';
            if ($ip == $range) {
                return true;
            }

            return false;
        }
    }

    // Determine whether the IPV6 address is within range.
    // $ip is the IPV6 address in decimal format to check if its within the IP range created by the cloudflare IPV6 address, $range_ip.
    // $ip and $range_ip are converted to full IPV6 format.
    // Returns true if the IPV6 address, $ip,  is within the range from $range_ip.  False otherwise.
    public static function ipv6InRange($ip, $range_ip)
    {
        $pieces = explode("/", $range_ip, 2);
        $left_piece = isset($pieces[0])?$pieces[0]:'';
        //$right_piece = isset($pieces[1])?$pieces[1]:'';
        // Extract out the main IP pieces
        $ip_pieces = explode("::", $left_piece, 2);
        $main_ip_piece = isset($ip_pieces[0])?$ip_pieces[0]:'';
        $last_ip_piece = isset($ip_pieces[1])?$ip_pieces[1]:'';
        // Pad out the shorthand entries.
        $main_ip_pieces = explode(":", $main_ip_piece);
        foreach ($main_ip_pieces as $key => $val) {
            $val = $val; // Prevent warning "Unused variable" from validator
            $main_ip_pieces[$key] = str_pad($main_ip_pieces[$key], 4, "0", STR_PAD_LEFT);
        }
        // Create the first and last pieces that will denote the IPV6 range.
        $first = $main_ip_pieces;
        $last = $main_ip_pieces;
        // Check to see if the last IP block (part after ::) is set
        $last_piece = "";
        $size = count($main_ip_pieces);
        if (trim($last_ip_piece) != "") {
            $last_piece = str_pad($last_ip_piece, 4, "0", STR_PAD_LEFT);

            // Build the full form of the IPV6 address considering the last IP block set
            for ($i = $size; $i < 7; $i++) {
                $first[$i] = "0000";
                $last[$i] = "ffff";
            }
            $main_ip_pieces[7] = $last_piece;
        } else {
            // Build the full form of the IPV6 address
            for ($i = $size; $i < 8; $i++) {
                $first[$i] = "0000";
                $last[$i] = "ffff";
            }
        }
        // Rebuild the final long form IPV6 address
        $first = self::ip2long6(implode(":", $first));
        $last = self::ip2long6(implode(":", $last));
        $in_range = ($ip >= $first && $ip <= $last);
        return $in_range;
    }

    public static function ip2long6($ip)
    {
        if (substr_count($ip, '::')) {
            $ip = str_replace('::', str_repeat(':0000', 8 - substr_count($ip, ':')) . ':', $ip);
        }

        $ip = explode(':', $ip);
        $r_ip = '';
        foreach ($ip as $v) {
            $r_ip .= str_pad(base_convert($v, 16, 2), 16, 0, STR_PAD_LEFT);
        }

        return base_convert($r_ip, 2, 10);
    }

     /**
     * getConfig()
     *
     * Get a configuration value from its name (the shop is optional)
     *
     * @param string $name Name of the configuration object
     * @param int $id_shop_group Id shop group (optional)
     * @param int $id_shop  Id shop (optional)
     * @return string Value of the configuration object
     *
     */
    public function getConfig($name, $id_shop_group = null, $id_shop = null)
    {
        if ($id_shop_group == null) {
            $id_shop_group = $this->id_shop_group;
        }

        if ($id_shop == null) {
            $id_shop = $this->id_shop;
        }

        //return Db::getInstance()->getValue($req, false);
        return Configuration::get($name, null, $id_shop_group, $id_shop);
    }

     /**
     * setConfig()
     *
     * Set a configuration value from its name (the shop is optional)
     *
     * @param string $name Name of the configuration object
     * @param mixed $value Value to set
     * @param int $id_shop_group Id shop group (optional)
     * @param int $id_shop  Id shop (optional)
     * @return bool Result
     *
     */
    public function setConfig($name, $value, $id_shop_group = null, $id_shop = null)
    {
        if ($id_shop_group == null) {
            $id_shop_group = $this->id_shop_group;
        }

        if ($id_shop == null) {
            $id_shop = $this->id_shop;
        }

        return Configuration::updateValue($name, $value, false, $id_shop_group, $id_shop);
    }

    /**
     * Log()
     *
     * Log message to file
     *
     * @param string $message Message to log
     * @return void
     *
     */
    public function log($message)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        } elseif (Tools::substr($message, 0, 3) == 'ERR' || Tools::substr($message, 0, 3) == 'END') {
            $this->sendReport($message);
        } elseif (Tools::substr($message, 0, 3) == 'WAR') {
            $message = html_entity_decode($message, ENT_COMPAT, 'UTF-8');
            $this->warnings[] = Tools::substr($message, 3);
        }

        if ($this->getConfig('ACTIVATE_LOG')) {
            $this->fileWrite($this->log_file, date(self::LOG_DATE_FORMAT).' '.$message."\n", 'a+');
        }
        $this->fileWrite($this->lastlog_file, $message, 'w+');
    }

    /**
     * fileWrite()
     *
     * Write a file
     *
     * @param string $path File path
     * @param string $content Content of the file
     * @param string $write_mode Write mode (r, r+, w, w+, a, a+, x, x+)
     * @return boolean True if file written
     *
     */
    protected function fileWrite($path, $content, $write_mode = 'w+')
    {
        $this->directoryCreate(dirname($path));

        if (!($file = fopen($path, $write_mode))) {
            return false;
        }

        // Make sur the file has the correct right
        if (chmod($path, octdec(self::PERM_FILE)) !== true) {
            $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $path, self::PERM_FILE));
        }

        if (!fwrite($file, $content)) {
            return false;
        }
        if (!fclose($file)) {
            return false;
        }
        return true;
    }
}
