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

require_once(dirname(__FILE__).'/ntbr.php');

class NtbrChild extends NtbrCore
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the module type (empty if full)
     *
     * @return  String  Light or empty
     */
    public function getTypeModule()
    {
        return '';
    }

    /**
     * Start local restore
     *
     * @param   string  $backup_name    Name of the backup
     * @param   string  $type_backup    Type of the backup
     *
     * @return  String|bool             Options of the restoration or false if failure
     */
    public function startLocalRestore($backup_name, $type_backup)
    {
        $old_restore            = $this->restore_file;
        $new_restore            = _PS_ROOT_DIR_.'/'.self::NEW_RESTORE_NAME;
        $list_old_new_backup    = array();
        $restore_backup_files   = array();
        $options_restore        = '';
        $backup_files           = $this->findOldBackups();

        foreach ($backup_files as $b_file) {
            if (strpos($b_file['name'], $backup_name) !== false) {
                $restore_backup_files = $b_file['part'];
            }
        }

        if (!file_exists($old_restore)) {
            return false;
        }

        foreach ($restore_backup_files as $old_backup_files) {
            if (!file_exists($this->module_path_physic.'backup/'.$old_backup_files['name'])) {
                return false;
            }

            $list_old_new_backup[] = array(
                'old' => $this->module_path_physic.'backup/'.$old_backup_files['name'],
                'new' => _PS_ROOT_DIR_.'/'.$old_backup_files['name']
            );
        }

        // Move restore and backup file to the root of the website
        if (!copy($old_restore, $new_restore)) {
            return false;
        }

        foreach ($list_old_new_backup as $old_new_backup) {
            if (!rename($old_new_backup['old'], $old_new_backup['new'])) {
                return false;
            }
        }

        $options_restore .= 'from_module=true';
        $options_restore .= '&db_server='._DB_SERVER_;
        $options_restore .= '&db_name='._DB_NAME_;
        $options_restore .= '&db_user='._DB_USER_;
        $options_restore .= '&db_passwd='._DB_PASSWD_;

        if ($type_backup == $this->type_backup_base) {
            $options_restore .= '&do_not_restore_files=true';
        } elseif ($type_backup == $this->type_backup_file) {
            $options_restore .= '&do_not_restore_database=true';
        }

        if (!$this->getConfig('NTBR_DISABLE_REFRESH')) {
            $options_restore .= '&activate_refresh=true&refresh_time='.$this->getConfig('NTBR_TIME_BETWEEN_REFRESH');
        }

        if (!$this->getConfig('NTBR_DISABLE_SERVER_TIMEOUT')) {
            $options_restore .= '&disable_time_limit=true';
        }

        return $options_restore;
    }

    /**
     * Download a file
     *
     * @param   String  $path       Path of the file to download
     * @param   String  $mime       Type/mime of the file to download
     * @param   String  $filename   New name of the file to download (optional)
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

        $xsendfile = Tools::apacheModExists('xsendfile') && $this->getConfig('ACTIVATE_XSENDFILE');

        if ($xsendfile) {//Use XSendFile module
            header('X-Sendfile: '.$path); //Apache
            header('X-Accel-Redirect: '.$path); //Nginx
            header('Content-Type: '.$mime);
            header('Content-Disposition: attachment; filename="'.$filename.'"');
        } else {//Using php
            parent::downloadFile($path, $mime, $filename);
        }
    }

    /**
     * Ignore product image in the backup
     *
     * @param   String  $current_normalized_file    Path of the file to check
     * @param   String  $filename                   Name of the file to check
     *
     * @return  bool                                If the file must be ignore or not
     */
    public function ignoreProductImage($current_normalized_file, $filename)
    {
        $ignore_this_file = false;

        //Check if it is a product image
        if ($this->getConfig('IGNORE_PRODUCT_IMAGE')
            && strpos($current_normalized_file, 'img/p/index.php') === false
            && (
                strpos($current_normalized_file, 'img/p/') !== false
                || strpos($current_normalized_file, 'img/tmp/product_mini_') !== false
            )
        ) {
            $ignore_this_file = true;
        }

        return $ignore_this_file;
    }

    /**
     * Get backup total size
     *
     * @return  float   Total size of the backup
     */
    public function getBackupTotalSize()
    {
        if ($this->getConfig('IGNORE_COMPRESSION')) {
            return $this->getFileSize($this->backup_folder.$this->tar_file);
        } else {
            return $this->getFileSize($this->backup_folder.$this->compressed_file);
        }
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
        if ($this->getConfig('IGNORE_COMPRESSION')) {
            return true;
        } else {
            return parent::deleteOldTar();
        }
    }

    /**
     * findOldBackups()
     *
     * Find old backups files
     *
     * @param   String  $test_type_backup   Type of backup to search for
     * @param   String  $test_extension     Test to do on the extension to find correct kind of backup
     *
     * @return  array                       Old backup sorted by date, older last
     *
     */
    public function findOldBackups($test_type_backup = '.', $test_extension = '.tar.')
    {
        if ($this->getConfig('IGNORE_COMPRESSION')) {
            $test_extension = '.tar';
        } else {
            $test_extension = '.tar.';
        }

        return parent::findOldBackups($test_type_backup, $test_extension);
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
        if ($this->getConfig('IGNORE_COMPRESSION')) {
            $this->total_size = $this->getFileSize($this->tar_file);
            return true;
        }

        return parent::compressBackup();
    }

    /**
     * Delete local backup if backup is sent away
     *
     * @return  boolean     Success or failure of the operation
     *
     */
    protected function deleteLocalBackup()
    {
        $list_active_ftp_accounts           = FTP::getListActiveFtpAccounts();
        $list_active_dropbox_accounts       = Dropbox::getListActiveDropboxAccounts();
        $list_active_owncloud_accounts      = Owncloud::getListActiveOwncloudAccounts();
        $list_active_webdav_accounts        = Webdav::getListActiveWebdavAccounts();
        $list_active_googledrive_accounts   = Googledrive::getListActiveGoogledriveAccounts();
        $list_active_onedrive_accounts      = Onedrive::getListActiveOnedriveAccounts();
        $list_active_hubic_accounts         = Hubic::getListActiveHubicAccounts();
        $list_active_aws_accounts           = Aws::getListActiveAwsAccounts();

        if ($this->getConfig('NTBR_DELETE_LOCAL_BACKUP')
            && (
                count($list_active_ftp_accounts)
                || count($list_active_dropbox_accounts)
                || count($list_active_owncloud_accounts)
                || count($list_active_webdav_accounts)
                || count($list_active_onedrive_accounts)
                || count($list_active_googledrive_accounts)
                || count($list_active_hubic_accounts)
                || count($list_active_aws_accounts)
            )
        ) {
            foreach ($this->part_list as $part) {
                if (file_exists($part)) {
                    if (!$this->fileDelete($part)) {
                        $this->log($this->l('Delete local backup file failed:').' '.$part);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Initialize SFTP
     */
    public function initForSFTP()
    {
        if (!extension_loaded('openssl') || !function_exists('hash_equals')) {
            return false;
        }

        $phpseclib_path = dirname(__FILE__).'/../lib/phpseclib/';
        set_include_path(get_include_path().PATH_SEPARATOR.$phpseclib_path);
        require_once($phpseclib_path.'autoload.php');
    }

    /**
     * Connect to Dropbox
     *
     * @param   String  $access_token   Dropbox token (optional)
     *
     * @return  object                  new DropboxLib
     */
    public function connectToDropbox($access_token = '')
    {
        $sdk_uri = $this->module_path.'lib/dropbox/';
        $physic_sdk_uri = $this->module_path_physic.'lib/dropbox/';

        if (!empty($access_token)) {
            return new DropboxLib($this, $sdk_uri, $physic_sdk_uri, $access_token);
        } else {
            return new DropboxLib($this, $sdk_uri, $physic_sdk_uri);
        }
    }

    /**
     * Connect to ownCloud
     *
     * @param   String  $server     ownCloud server
     * @param   String  $user       ownCloud user
     * @param   String  $pass       ownCloud password
     *
     * @return  object                  new OwncloudLib
     */
    public function connectToOwncloud($server, $user, $pass)
    {
        $sdk_uri = $this->module_path.'lib/owncloud/';
        $physic_sdk_uri = $this->module_path_physic.'lib/owncloud/';

        return new OwncloudLib($this, $server, $user, $pass, $sdk_uri, $physic_sdk_uri);
    }

    /**
     * Connect to WebDAV
     *
     * @param   String  $url    WebDAV url
     * @param   String  $user   WebDAV user
     * @param   String  $pass   WebDAV password
     *
     * @return  object          new WebdavLib
     */
    public function connectToWebdav($url, $user, $pass)
    {
        $sdk_uri = $this->module_path.'lib/webdav/';
        $physic_sdk_uri = $this->module_path_physic.'lib/webdav/';

        return new WebdavLib($this, $url, $user, $pass, $sdk_uri, $physic_sdk_uri);
    }

    /**
     * Connect to AWS
     *
     * @param   String  $aws_id_key     AWS ID key
     * @param   String  $aws_key        AWS secret key
     * @param   String  $aws_region     AWS region
     * @param   String  $aws_bucket     AWS bucket
     *
     * @return  object                  new AwsLib
     */
    public function connectToAws($aws_id_key, $aws_key, $aws_region, $aws_bucket)
    {
        $sdk_uri = $this->module_path.'lib/aws/';
        $physic_sdk_uri = $this->module_path_physic.'lib/aws/';

        return new AwsLib($this, $aws_id_key, $aws_key, $aws_region, $aws_bucket, $sdk_uri, $physic_sdk_uri);
    }

    /**
     * Connect to Openstack
     *
     * @param   String  $access_token   Openstack token
     * @param   String  $end_point      Openstack end point
     * @param   String  $account_type   Openstack account type
     *
     * @return  object                  new OpenstackLib
     */
    public function connectToOpenstack($access_token, $end_point, $account_type)
    {
        $sdk_uri = $this->module_path.'lib/openstack/';
        $physic_sdk_uri = $this->module_path_physic.'lib/openstack/';

        return new OpenstackLib($this, $sdk_uri, $physic_sdk_uri, $access_token, $end_point, $account_type);
    }

    /**
     * Connect to Google Drive
     *
     * @param   String  $access_token   Google Drive token (optional)
     *
     * @return  object                  new GoogledriveLib
     */
    public function connectToGoogledrive($access_token = '')
    {
        $access_right = GoogledriveLib::DRIVE;
        $sdk_uri = $this->module_path.'lib/googledrive/';
        $physic_sdk_uri = $this->module_path_physic.'lib/googledrive/';

        if (!empty($access_token)) {
            $decode_token = Tools::jsonDecode($access_token, true);

            // If token expire in 30 minutes
            $expired = ($decode_token['created'] + ($decode_token['expires_in'] - 1800)) < time();

            if ($expired) {
                $access_token = $this->getGoogledriveRefreshToken($decode_token['refresh_token']);

                if ($access_token !== false) {
                    $decode_token = Tools::jsonDecode($access_token, true);
                }
            }

            return new GoogledriveLib($this, $access_right, $sdk_uri, $physic_sdk_uri, $decode_token['access_token']);
        } else {
            return new GoogledriveLib($this, $access_right, $sdk_uri, $physic_sdk_uri);
        }
    }

    /**
     * Connect to OneDrive
     *
     * @param   String      $access_token       OneDrive token (optional)
     * @param   integer     $id_ntbr_onedrive   ID OneDrive account (optional)
     *
     * @return  object                          new OnedriveLib
     */
    public function connectToOnedrive($access_token = '', $id_ntbr_onedrive = 0)
    {
        $sdk_uri = $this->module_path.'lib/onedrive/';
        $physic_sdk_uri = $this->module_path_physic.'lib/onedrive/';

        if (!empty($access_token)) {
            $decode_token = Tools::jsonDecode($access_token, true);

            // If token expire in 30 minutes
            $expired = ($decode_token['created'] + ($decode_token['expires_in'] - 1800)) < time();

            if ($expired) {
                $access_token = $this->getOnedriveRefreshToken($decode_token['refresh_token']);

                if ($id_ntbr_onedrive) {
                    $onedrive = new Onedrive($id_ntbr_onedrive);
                    $onedrive->token = $access_token;
                    $onedrive->update();
                }

                $decode_token = Tools::jsonDecode($access_token, true);
            }

            return new OnedriveLib($this, $sdk_uri, $physic_sdk_uri, $decode_token['access_token']);
        } else {
            return new OnedriveLib($this, $sdk_uri, $physic_sdk_uri);
        }
    }

    /**
     * Connect to Hubic
     *
     * @param   integer     $id_hubic_account   ID Hubic account (optional)
     *
     * @return  object                          new HubicLib
     */
    public function connectToHubic($id_hubic_account = '0')
    {
        $sdk_uri        = $this->module_path.'lib/hubic/';
        $physic_sdk_uri = $this->module_path_physic.'lib/hubic/';
        $access_token   = '';
        $credential     = '';

        if ($id_hubic_account) {
            $hubic          = new Hubic($id_hubic_account);
            $access_token   = $hubic->token;
            $credential     = $hubic->credential;
        }

        if (!empty($access_token)) {
            $decode_token = Tools::jsonDecode($access_token, true);

            // If token expire in 30 minutes
            if (($decode_token['created'] + ($decode_token['expires_in'] - 1800)) < time()) {
                $connect_infos = $this->getHubicRefreshToken($decode_token['refresh_token']);

                if (!is_array($connect_infos)|| !isset($connect_infos['token'])|| !isset($connect_infos['credential'])) {
                    $this->log($this->l('Error while getting Hubic refresh token'));
                    return false;
                }

                $decode_token   = Tools::jsonDecode($connect_infos['token'], true);
                $credential     = $connect_infos['credential'];

                $hubic->token       = $connect_infos['token'];
                $hubic->credential  = $connect_infos['credential'];

                if (!$hubic->update()) {
                    $this->log($this->l('Error while updating Hubic token and credentials'));
                    return false;
                }
            }

            $hubic_lib          = new HubicLib($this, $sdk_uri, $physic_sdk_uri, $decode_token['access_token']);
            $decode_credential  = Tools::jsonDecode($credential, true);

            // If credential expired or no credential
            if (!isset($decode_credential['expires']) || strtotime($decode_credential['expires']) <= time()) {
                $new_credential     = $hubic_lib->getCredential();
                $hubic->credential  = Tools::jsonEncode($new_credential);

                if (!$hubic->update()) {
                    $this->log($this->l('Error while updating Hubic credentials'));
                    return false;
                }
            } else {
                $hubic_lib->setCredential($decode_credential['token'], $decode_credential['endpoint']);
            }

            return $hubic_lib;
        } else {
            return new HubicLib($this, $sdk_uri, $physic_sdk_uri);
        }

        return false;
    }

    /**
     * Test connection to Dropbox
     *
     * @param   String      $token  Dropbox token
     *
     * @return  boolean             The success or failure of the connection
     */
    public function testDropboxConnection($token)
    {
        $dropbox_lib = $this->connectToDropbox($token);
        return (bool)$dropbox_lib->testConnection();
    }

    /**
     * Test connection to ownCloud
     *
     * @param   String      $server     ownCloud server
     * @param   String      $user       ownCloud user
     * @param   String      $pass       ownCloud password
     *
     * @return  boolean                 The success or failure of the connection
     */
    public function testOwncloudConnection($server, $user, $pass)
    {
        $owncloud_lib = $this->connectToOwncloud($server, $user, $pass);
        return (bool)$owncloud_lib->testConnection();
    }

    /**
     * Test connection to WebDAV
     *
     * @param   String      $url    WebDAV url
     * @param   String      $user   WebDAV user
     * @param   String      $pass   WebDAV password
     *
     * @return  boolean             The success or failure of the connection
     */
    public function testWebdavConnection($url, $user, $pass)
    {
        $webdav_lib = $this->connectToWebdav($url, $user, $pass);
        return (bool)$webdav_lib->testConnection();
    }

    /**
     * Test connection to Google Drive
     *
     * @param   String      $token  Google Drive token
     *
     * @return  boolean             The success or failure of the connection
     */
    public function testGoogledriveConnection($token)
    {
        $googledrive_lib = $this->connectToGoogledrive($token);
        return (bool)$googledrive_lib->testConnection();
    }

    /**
     * Test connection to OneDrive
     *
     * @param   String      $token              OneDrive token
     * @param   integer     $id_ntbr_onedrive   ID OneDrive account
     *
     * @return  boolean                         The success or failure of the connection
     */
    public function testOnedriveConnection($token, $id_ntbr_onedrive)
    {
        $onedrive_lib = $this->connectToOnedrive($token, $id_ntbr_onedrive);
        return (bool)$onedrive_lib->testConnection();
    }

    /**
     * Test connection to Hubic
     *
     * @param   integer     $id_hubic_account   ID Hubic account
     *
     * @return  boolean                         The success or failure of the connection
     */
    public function testHubicConnection($id_hubic_account)
    {
        $hubic_lib = $this->connectToHubic($id_hubic_account);

        return (bool)$hubic_lib->testConnection();
    }

    /**
     * Test connection to AWS
     *
     * @param   String     $aws_id_key      AWS ID key
     * @param   String     $aws_key         AWS secret key
     * @param   String     $aws_region      AWS region
     * @param   String     $aws_bucket      AWS bucket
     *
     * @return  boolean                     The success or failure of the connection
     */
    public function testAwsConnection($aws_id_key, $aws_key, $aws_region, $aws_bucket)
    {
        $aws_lib = $this->connectToAws($aws_id_key, $aws_key, $aws_region, $aws_bucket);
        return (bool)$aws_lib->testConnection();
    }

    /**
     * Test connection to FTP
     *
     * @param   String      $ftp_server     FTP server
     * @param   String      $ftp_login      FTP login
     * @param   String      $ftp_pass       FTP password
     * @param   integer     $ftp_port       FTP port
     * @param   boolean     $ssl            FTP ssl (enable or disable)
     * @param   boolean     $pasv           FTP passive mode (enable or disable)
     *
     * @return  boolean                     The success or failure of the connection
     */
    public function testFTP($ftp_server, $ftp_login, $ftp_pass, $ftp_port, $ssl = false, $pasv = false)
    {
        if ($ssl) {
            $connection = ftp_ssl_connect($ftp_server, (int) $ftp_port, self::FTP_TIMEOUT);
        } else {
            // Beware of the warning from php if failure
            $connection = @ftp_connect($ftp_server, (int) $ftp_port, self::FTP_TIMEOUT);
        }

        if (!$connection) {
            return false;
        }

        // Beware of the warning from php if failure
        $return = @ftp_login($connection, $ftp_login, $ftp_pass);

        if ($return) {
            $return = ftp_pasv($connection, $pasv);
        }

        ftp_close($connection);

        return $return;
    }

    /**
     * Test connection to SFTP
     *
     * @param   String      $ftp_server     SFTP server
     * @param   String      $ftp_login      SFTP login
     * @param   String      $ftp_pass       SFTP password
     * @param   integer     $ftp_port       SFTP port
     *
     * @return  boolean                     The success or failure of the connection
     */
    public function testSFTP($ftp_server, $ftp_login, $ftp_pass, $ftp_port)
    {
        $return = true;

        $this->initForSFTP();

        $sftp_lib = new \phpseclib\Net\SFTP($ftp_server, $ftp_port);

        // Beware of the warning from php if failure
        if (!@$sftp_lib->login($ftp_login, $ftp_pass)) {
            $return = false;
        }

        // The closing of the ftp can sometime bug so we can't test it
        if (!$this->closeSFTP($sftp_lib)) {
            //$return = false;
        }

        return $return;
    }

    /**
     * Send a file on a Dropbox account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToDropbox()
    {
        $dropbox        = new Dropbox($this->dropbox_account_id);
        $access_token   = $dropbox->token;

        if ($this->next_step == $this->step_send['dropbox'] && (!isset($this->dropbox_nb_part) || $this->dropbox_nb_part == 1)) {
            $this->log($this->l('Connect to your Dropbox account...'));
        }

        $dropbox_lib = $this->connectToDropbox($access_token);

        if ($this->next_step == $this->step_send['dropbox'] && (!isset($this->dropbox_nb_part) || $this->dropbox_nb_part == 1)) {
            $this->dropbox_dir = $dropbox->directory;

            //Dropbox dir should start with a "/" except for root
            if ($this->dropbox_dir != '' && $this->dropbox_dir[0] !== '/') {
                $this->dropbox_dir = '/'.$this->dropbox_dir;
            }

            $temp_directory = $this->dropbox_dir;

            //Dropbox dir should end with a "/" except when testing if exist
            if (Tools::substr($this->dropbox_dir, -1) != '/') {
                $temp_directory .= '/';
            }

            // If file already on Dropbox
            foreach ($this->part_list as $part) {
                $file_path          = $part;
                $file_destination   = $temp_directory.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                if ($dropbox_lib->checkExists($file_destination)) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($dropbox_lib->deleteFile($file_destination) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                    }
                }
            }

            // Delete old backup
            if (!$this->deleteDropboxOldBackup($access_token, $temp_directory)) {
                $this->log('WAR'.$this->l('Sending backup to Dropbox account:').' '.$this->l('Error while deleting old backup'));
                return false;
            }

            // Check available space
            $available_space = $dropbox_lib->getAvailableQuota();

            if ($this->getConfig('SEND_RESTORE')) {
                $total_file_size = $this->getFileSize($this->restore_file);
                $available_space -= $total_file_size;
            }

            if ($available_space <= $this->total_size) {
                $this->log('WAR'.$this->l('Sending backup to Dropbox account:').' '.$this->l('Not enough space available'));
                return false;
            }

            $this->log($this->l('Sending backup to Dropbox account:').' '.$this->l('Check the path of your backup'));

            // Check if the folder we want to use exists. If not we create it.
            if ($dropbox_lib->checkExists($this->dropbox_dir) === false) {
                $this->log($this->l('Sending backup to Dropbox account:').' '.$this->l('Create the directory').' "'.$this->dropbox_dir.'"');
                if ($dropbox_lib->createFolder($this->dropbox_dir) === false) {
                    $this->log('WAR'.$this->l('Sending backup to Dropbox account:').' '.$this->l('Error while creating the directory').' "'.$this->dropbox_dir.'"');
                    return false;
                }
            }

            //Dropbox dir should end with a "/" except when testing if exist
            $this->dropbox_dir = $temp_directory;

            $this->log($this->l('Sending backup to Dropbox account...'));

            $this->dropbox_nb_part = 1;
            $this->dropbox_position = 0;
        }

        $nb_part    = 1;

        foreach ($this->part_list as $part) {
            if ($nb_part == $this->dropbox_nb_part) {
                $file_path          = $part;
                $file_destination   = $this->dropbox_dir.basename($part);

                // Upload the file
                if ($this->next_step == $this->step_send['dropbox']) {
                    $this->next_step = $this->step_send['dropbox_resume'];
                    if ($dropbox_lib->uploadFile($file_path, $file_destination, $this->dropbox_nb_part, $this->total_nb_part) === false) {
                        return false;
                    }
                } else { // Resume the upload
                    if ($dropbox_lib->resumeUploadFile($file_path, $file_destination, $this->dropbox_upload_id, $this->dropbox_position, $this->dropbox_nb_part, $this->total_nb_part) === false) {
                        return false;
                    }
                }

                $this->dropbox_nb_part++;
                // New part, so back to init values
                $this->next_step = $this->step_send['dropbox'];
                $this->dropbox_position = 0;
            }
            $nb_part++;
        }

        $this->next_step = $this->step_send['dropbox_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            $this->log($this->l('Sending restore file to Dropbox account...'));
            // Upload the file
            if ($dropbox_lib->uploadFile($this->restore_file, $this->dropbox_dir.self::NEW_RESTORE_NAME) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Connect to a FTP server
     *
     * @param   String      $ftp_server     SFTP server
     * @param   String      $ftp_login      SFTP login
     * @param   String      $ftp_pass       SFTP password
     * @param   integer     $ftp_port       SFTP port
     * @param   boolean     $ftp_ssl        SFTP SSL
     * @param   boolean     $ftp_pasv       SFTP passive mode
     * @param   String      $ftp_dir        SFTP directory
     *
     * @return  ressource   The FTP connexion
     */
    private function connectFtp($ftp_server, $ftp_login, $ftp_pass, $ftp_port, $ftp_ssl, $ftp_pasv, $ftp_dir = '')
    {
        if ($ftp_ssl) {
            $connection = ftp_ssl_connect($ftp_server, (int)$ftp_port, self::FTP_TIMEOUT);
        } else {
            // Beware of the warning from php if failure
            $connection = @ftp_connect($ftp_server, $ftp_port, self::FTP_TIMEOUT);
        }

        if (!$connection) {
            $this->log('WAR'.$this->l('Unable to connect to the FTP server, please verify your data'));
            return false;
        }

        // Beware of the warning from php if failure
        $login = @ftp_login($connection, $ftp_login, $ftp_pass);

        if (!$login) {
            ftp_close($connection);
            $this->log('WAR'.$this->l('Unable to log in the FTP server, please verify your credentials'));
            return false;
        }

        ftp_pasv($connection, $ftp_pasv);

        if ($ftp_dir != '') {
            ftp_chdir($connection, $ftp_dir);
        }

        return $connection;
    }

    /**
     * Send a file on a FTP account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToFTP()
    {
        $ftp        = new Ftp($this->ftp_account_id);
        $ftp_server = $ftp->server;
        $ftp_login  = $ftp->login;
        $ftp_pass   = $this->decrypt($ftp->password);
        $ftp_port   = $ftp->port;
        $ftp_ssl    = $ftp->ssl;
        $ftp_pasv   = $ftp->passive_mode;

        if ($this->next_step == $this->step_send['ftp'] && (!isset($this->ftp_nb_part) || $this->ftp_nb_part == 1)) {
            $this->ftp_dir      = $ftp->directory;
            $this->ftp_nb_part  = 1;

            //FTP dir should start and end with a /
            $this->ftp_dir = rtrim($this->normalizePath($this->ftp_dir), '/').'/';
            if ($this->ftp_dir[0] !== '/') {
                $this->ftp_dir = '/'.$this->ftp_dir;
            }
        }

        $connection = $this->connectFtp($ftp_server, $ftp_login, $ftp_pass, (int)$ftp_port, $ftp_ssl, $ftp_pasv);

        if (!$connection) {
            return false;
        }

        if ($this->next_step == $this->step_send['ftp']) {
            $ftp_current_directory = ftp_pwd($connection);

            if (!isset($this->ftp_nb_part) || $this->ftp_nb_part == 1) {
                if ($ftp_current_directory != '/') {
                    $this->ftp_dir = $ftp_current_directory.$this->ftp_dir;
                }
            }
        }

        ftp_chdir($connection, $this->ftp_dir);

        if ($this->next_step == $this->step_send['ftp'] && (!isset($this->ftp_nb_part) || $this->ftp_nb_part == 1)) {
            // If file already on FTP
            foreach ($this->part_list as $part) {
                $file_destination   = $this->ftp_dir.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                if (ftp_size($connection, basename($part)) != -1) { // -1 == error. If file is bigger than 2GB we can have negative number, so be carefull
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if (ftp_delete($connection, basename($part)) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                    }
                }
            }

            if (!$this->deleteFTPOldBackup($connection)) {
                ftp_close($connection);
                return false;
            }

            $this->ftp_position = 0;
        }

        $nb_part = 1;
        foreach ($this->part_list as $part) {
            if ($this->ftp_nb_part == $nb_part) {
                //Restart connection for each file to avoid server disconnection problems
                ftp_close($connection);
                $connection = $this->connectFtp($ftp_server, $ftp_login, $ftp_pass, (int)$ftp_port, $ftp_ssl, $ftp_pasv, $this->ftp_dir);

                if (!$connection) {
                    $this->log('WAR'.$this->l('An error occured while uploading your backup to the FTP server, connection to ftp server was shutdown'));
                    return false;
                }

                $total_file_size = $this->getFileSize($part);
                $ftp_file_path = basename($part);
                $last_percent = 0;

                $file = fopen($part, "r+");

                if ($file === false) {
                    $this->log('WAR'.$this->l('Unable to access backup file in order to send it by FTP, please check file rights'));
                    return false;
                }

                if ($this->next_step == $this->step_send['ftp_resume']) {
                    $position = $this->ftp_position;

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
                } else {
                    $this->next_step = $this->step_send['ftp_resume'];
                }

                $byte_offset = $this->ftp_position;

                //Send the file
                $ftp_upload = ftp_nb_fput($connection, $ftp_file_path, $file, FTP_BINARY, $this->ftp_position);

                while ($ftp_upload == FTP_MOREDATA) {
                    $this->checkStopScript();
                    $byte_offset = ftell($file);

                    $percent = (int)(($byte_offset/$total_file_size) * 100);
                    if ($percent >= ($last_percent + 1)) {
                        if ($this->total_nb_part > 1) {
                            $this->log($this->l('Sending backup to FTP account:').' '.$this->ftp_nb_part.'/'.$this->total_nb_part.$this->l(':').' '.(int)$percent.'%');
                        } else {
                            $this->log($this->l('Sending backup to FTP account:').' '.$percent.'%');
                        }

                        $last_percent = $percent;
                    }
                    $ftp_upload = ftp_nb_continue($connection);

                    if ($this->validRefresh(true)) {
                        //$this->log($this->l('Close connection to FTP'));

                        ftp_close($connection);

                        if ($ftp_ssl) {
                            $connection = ftp_ssl_connect($ftp_server, (int) $ftp_port, self::FTP_TIMEOUT);
                        } else {
                            // Beware of the warning from php if failure
                            $connection = @ftp_connect($ftp_server, $ftp_port, self::FTP_TIMEOUT);
                        }

                        if (!$connection) {
                            $this->log('WAR'.$this->l('Unable to connect to the FTP server, please verify your data'));
                            return false;
                        }

                        // Beware of the warning from php if failure
                        $login = @ftp_login($connection, $ftp_login, $ftp_pass);
                        if (!$login) {
                            ftp_close($connection);
                            $this->log('WAR'.$this->l('Unable to log in the FTP server, please verify your credentials'));
                            return false;
                        }

                        ftp_raw($connection, 'TYPE I');
                        $response =  ftp_raw($connection, 'SIZE '.$this->ftp_dir.$ftp_file_path);
                        $response_code = Tools::substr($response[0], 0, 3);
                        $response_msg = Tools::substr($response[0], 4);

                        if ($response_code == '213') {
                            $this->ftp_position = $response_msg;
                        } else {
                            $this->log('WAR'.$response_msg);
                            return false;
                        }

                        //refresh
                        $this->refreshBackup(true);
                    }
                }

                if ($ftp_upload != FTP_FINISHED) {
                    $byte_offset = ftell($file);

                    if ($byte_offset != $total_file_size) {
                        ftp_close($connection);
                        $this->log('WAR'.$this->l('An error occured while uploading your backup to the FTP server, please check your FTP server log and retry'));

                        if (!$ftp_pasv) {
                            $this->log('WAR'.$this->l('Check if passive mode is needed'));
                        }

                        return false;
                    }
                }

                $this->ftp_nb_part++;
                // New part, so back to init values
                $this->next_step = $this->step_send['ftp'];
                $this->ftp_position = 0;
            }

            $nb_part++;
        }

        $this->next_step = $this->step_send['ftp_resume'];

        // Send restore file if needed
        if ($this->getConfig('SEND_RESTORE')) {
            // Test if the connexion is still open
            if (ftp_pwd($connection) === false) {
                $this->log($this->l('Try to connect to the FTP server. The connexion was lost.'));

                if ($ftp_ssl) {
                    $connection = ftp_ssl_connect($ftp_server, (int) $ftp_port, self::FTP_TIMEOUT);
                } else {
                    // Beware of the warning from php if failure
                    $connection = @ftp_connect($ftp_server, $ftp_port, self::FTP_TIMEOUT);
                }

                if (!$connection) {
                    $this->log('WAR'.$this->l('Unable to connect to the FTP server, please verify your data'));
                    return false;
                }

                // Beware of the warning from php if failure
                $login = @ftp_login($connection, $ftp_login, $ftp_pass);
                if (!$login) {
                    ftp_close($connection);
                    $this->log('WAR'.$this->l('Unable to log in the FTP server, please verify your credentials'));
                    return false;
                }

                ftp_pasv($connection, $ftp_pasv);

                ftp_chdir($connection, $this->ftp_dir);
            }

            $file_destination   = $this->ftp_dir.basename($this->restore_file);
            $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
            if (ftp_size($connection, basename($this->restore_file)) != -1) { // -1 == error. If file is bigger than 2GB we can have negative number, so be carefull
                $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                if (ftp_delete($connection, basename($this->restore_file)) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                }
            }

            $this->log($this->l('Sending restore file to FTP account...'));
            $total_file_size = $this->getFileSize($this->restore_file);
            $ftp_file_path = self::NEW_RESTORE_NAME;
            $last_percent = 0;

            $file = fopen($this->restore_file, "r+");

            if ($file === false) {
                $this->log('WAR'.$this->l('Unable to access restoration script in order to send it by FTP, please check file rights'));
            } else {
                //Send the file
                $upload = ftp_nb_fput($connection, $ftp_file_path, $file, FTP_BINARY);

                while ($upload == FTP_MOREDATA) {
                    $this->checkStopScript();
                    $byte_offset = ftell($file);
                    $percent = (int)(($byte_offset/$total_file_size) * 100);
                    if ($percent >= ($last_percent + 1)) {
                        $this->log($this->l('Sending restore to FTP account:').' '.$percent.'%');

                        $last_percent = $percent;
                    }
                    $upload = ftp_nb_continue($connection);
                }

                if ($upload != FTP_FINISHED) {
                    ftp_close($connection);
                    $this->log('WAR'.$this->l('An error occured while uploading your restore to the FTP server, please check your FTP server log and retry'));
                    return false;
                }
            }
        }

        ftp_close($connection);
        return true;
    }

    /**
     * Send a file on a SFTP account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToSFTP()
    {
        $ftp        = new Ftp($this->ftp_account_id);
        $ftp_server = $ftp->server;
        $ftp_login  = $ftp->login;
        $ftp_pass   = $this->decrypt($ftp->password);
        $ftp_port   = $ftp->port;

        if ($this->next_step == $this->step_send['ftp'] && (!isset($this->ftp_nb_part) || $this->ftp_nb_part == 1)) {
            $this->ftp_dir    = $ftp->directory;

            //SFTP dir should start and end with a /
            $this->ftp_dir = rtrim($this->normalizePath($this->ftp_dir), '/').'/';
            if ($this->ftp_dir[0] !== '/') {
                $this->ftp_dir = '/'.$this->ftp_dir;
            }
        }

        $this->initForSFTP();

        $sftp_lib = new \phpseclib\Net\SFTP($ftp_server, $ftp_port);

        // Beware of the warning from php if failure
        if (!@$sftp_lib->login($ftp_login, $ftp_pass)) {
            $this->log('WAR'.$this->l('Unable to connect to the SFTP server, please verify your credentials'));
            return false;
        }

        if ($this->next_step == $this->step_send['ftp'] && (!isset($this->ftp_nb_part) || $this->ftp_nb_part == 1)) {
            if ($this->ftp_dir[0] !== '/') {
                $this->ftp_dir = '/'.$this->ftp_dir;
            }

            $this->ftp_dir = $sftp_lib->pwd().$this->ftp_dir;

            // If file already on SFTP
            foreach ($this->part_list as $part) {
                $file_destination = $this->ftp_dir.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                if ($sftp_lib->file_exists($file_destination)) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($sftp_lib->delete($file_destination) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                    }
                }
            }

            if (!$this->deleteSFTPOldBackup($sftp_lib, $this->ftp_dir)) {
                $this->closeSFTP($sftp_lib);
                return false;
            }

            $this->ftp_nb_part = 1;
            $this->ftp_position = 0;
        }

        $sftp_lib->chdir($this->ftp_dir);

        $nb_part = 1;
        foreach ($this->part_list as $part) {
            if ($this->ftp_nb_part == $nb_part) {
                $total_file_size = $this->getFileSize($part);
                $byte_offset = $this->ftp_position;
                $last_percent = 0;
                $file = fopen($part, "r+");

                if ($this->next_step == $this->step_send['ftp_resume'] && $this->ftp_position > 0) {
                    // Go to the last position in the file
                    $file = $this->goToPositionInFile($file, $this->ftp_position);
                }

                $this->next_step = $this->step_send['ftp_resume'];

                while (!feof($file)) {
                    $this->checkStopScript();
                    $part_file = fread($file, self::MAX_FILE_UPLOAD_SIZE);
                    $byte_offset += self::MAX_FILE_UPLOAD_SIZE;
                    $this->ftp_position = $byte_offset;

                    if ($total_file_size == 0) {
                        $this->log('WAR'.$this->l('Your file seems to have an issue. Please check it.').' '.$part);
                        return false;
                    }

                    $percent = (int)(($byte_offset/$total_file_size) * 100);

                    // if self::MAX_FILE_UPLOAD_SIZE > than what is left to upload
                    if ($percent > 100) {
                        $percent = 100;
                    }

                    if ($percent >= ($last_percent + 1)) {
                        if ($this->total_nb_part > 1) {
                            $this->log($this->l('Sending backup to SFTP account:').' '.$nb_part.'/'.$this->total_nb_part.$this->l(':').' '.(int)$percent.'%');
                        } else {
                            $this->log($this->l('Sending backup to SFTP account:').' '.$percent.'%');
                        }

                        $last_percent = $percent;
                    }

                    if (!$sftp_lib->put(basename($part), $part_file, \phpseclib\Net\SFTP::RESUME)) {
                        $this->log('WAR'.$this->l('An error occured while uploading your backup to the SFTP server, please check your SFTP server log and retry'));
                        return false;
                    }

                    //refresh
                    $this->refreshBackup(true);
                }

                $this->ftp_nb_part++;
                // New part, so back to init values
                $this->next_step = $this->step_send['ftp'];
                $this->ftp_position = 0;
            }

            $nb_part++;
        }

        $this->next_step = $this->step_send['ftp_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            $this->log($this->l('Sending restore file to SFTP account...'));
            $total_file_size = $this->getFileSize($this->restore_file);
            $byte_offset = 0;
            $last_percent = 0;
            $file = fopen($this->restore_file, "r+");

            while (!feof($file)) {
                $this->checkStopScript();
                $part_file = fread($file, self::MAX_FILE_UPLOAD_SIZE);
                $byte_offset += self::MAX_FILE_UPLOAD_SIZE;

                if ($total_file_size == 0) {
                    $this->log('WAR'.$this->l('Your file seems to have an issue. Please check it.').' '.$this->restore_file);
                    return false;
                }

                $percent = (int)(($byte_offset/$total_file_size) * 100);

                // if self::MAX_FILE_UPLOAD_SIZE > than what is left to upload
                if ($percent > 100) {
                    $percent = 100;
                }

                if ($percent >= ($last_percent + 1)) {
                    $this->log($this->l('Sending restore file to SFTP account:').' '.$percent.'%');
                    $last_percent = $percent;
                }

                if (!$sftp_lib->put(self::NEW_RESTORE_NAME, $part_file, \phpseclib\Net\SFTP::RESUME)) {
                    $this->log('WAR'.$this->l('An error occured while uploading your restore file to the SFTP server, please check your SFTP server log and retry'));
                    return false;
                }
            }
        }

        $this->closeSFTP($sftp_lib);

        return true;
    }

    /**
     * Send a file on a OneDrive account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToOnedrive()
    {
        $onedrive       = new Onedrive($this->onedrive_account_id);
        $access_token   = $onedrive->token;
        $onedrive_dir   = $onedrive->directory_key;

        //if ($this->next_step == $this->step_send['onedrive'] || $this->next_step == $this->step_send['onedrive_resume']) {

        if ($access_token !== false) {
            if ($this->next_step == $this->step_send['onedrive'] && (!isset($this->onedrive_nb_part) || $this->onedrive_nb_part == 1)) {
                $this->log($this->l('Connect to your OneDrive account...'));
            }

            $onedrive_lib = $this->connectToOnedrive($access_token, $onedrive->id);

            if ($this->next_step == $this->step_send['onedrive'] && (!isset($this->onedrive_nb_part) || $this->onedrive_nb_part == 1)) {
                if (Tools::substr($onedrive->directory_path, -1) != '/') {
                    $onedrive->directory_path .= '/';
                }

                // If file already on OneDrive
                foreach ($this->part_list as $part) {
                    $file_destination = $onedrive->directory_path.basename($part);

                    $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                    $id_file = $onedrive_lib->checkExists(basename($part), $onedrive_dir);

                    if ($id_file !== false) {
                        $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                        if ($onedrive_lib->deleteItem($id_file) === false) {
                            $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                        }
                    }
                }

                // Delete old backup
                if (!$this->deleteOnedriveOldBackup($onedrive_lib, $onedrive_dir)) {
                    $this->log('WAR'.$this->l('Sending backup to OneDrive account:').' '.$this->l('Error while deleting old backup'));
                    return false;
                }

                // Check available space
                $available_space = $onedrive_lib->fetchQuota();

                if ($this->getConfig('SEND_RESTORE')) {
                    $total_file_size = $this->getFileSize($this->restore_file);
                    $available_space -= $total_file_size;
                }

                if ($available_space <= $this->total_size) {
                    $this->log('WAR'.$this->l('Sending backup to OneDrive account:').' '.$this->l('Not enough space available'));
                    return false;
                }

                $this->log($this->l('Sending backup to OneDrive account...'));
                $this->onedrive_nb_part = 1;
                $this->onedrive_position = 0;
            }

            $nb_part    = 1;

            // Upload the file
            foreach ($this->part_list as $part) {
                if ($nb_part == $this->onedrive_nb_part) {
                    $file_name = basename($part);
                    $file_path = $part;

                    if ($this->next_step == $this->step_send['onedrive']) {
                        $this->next_step = $this->step_send['onedrive_resume'];
                        // Upload the file
                        if ($onedrive_lib->createFile($file_name, $file_path, $onedrive_dir, $this->onedrive_nb_part, $this->total_nb_part) === false) {
                            return false;
                        }
                    } else {
                        // Resume upload of the file
                        if ($onedrive_lib->resumeCreateFile($file_path, $this->onedrive_session, $this->onedrive_position, $this->onedrive_nb_part, $this->total_nb_part) === false) {
                            return false;
                        }
                    }

                    $this->onedrive_nb_part++;
                    // New part, so back to init values
                    $this->next_step = $this->step_send['onedrive'];
                    $this->onedrive_position = 0;
                }
                $nb_part++;
            }

            $this->next_step = $this->step_send['onedrive_resume'];

            if ($this->getConfig('SEND_RESTORE')) {
                $this->log($this->l('Sending restore file to OneDrive account...'));
                // Upload the file
                if ($onedrive_lib->createFile(self::NEW_RESTORE_NAME, $this->restore_file, $onedrive_dir) === false) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Send a file on a ownCloud account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToOwncloud()
    {
        $owncloud           = new Owncloud($this->owncloud_account_id);
        $owncloud_server    = $owncloud->server;
        $owncloud_user      = $owncloud->login;
        $owncloud_pass      = $this->decrypt($owncloud->password);
        $owncloud_dir       = $owncloud->directory;

        if ($this->next_step == $this->step_send['owncloud'] && (!isset($this->owncloud_nb_part) || $this->owncloud_nb_part == 1)) {
            $this->log($this->l('Connect to your ownCloud account...'));
        }

        $owncloud_lib = $this->connectToOwncloud($owncloud_server, $owncloud_user, $owncloud_pass);

        //ownCloud dir should end with a "/" except when testing if exist
        if (Tools::substr($owncloud_dir, -1) != '/') {
            $owncloud_dir .= '/';
        }

        if ($this->next_step == $this->step_send['owncloud'] && (!isset($this->owncloud_nb_part) || $this->owncloud_nb_part == 1)) {
            // If file already on ownCloud
            foreach ($this->part_list as $part) {
                $file_destination   = $owncloud_dir.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                if ($owncloud_lib->fileExists($file_destination)) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($owncloud_lib->deleteFile($file_destination) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                    }
                }
            }

            // Delete old backup
            if (!$this->deleteOwncloudOldBackup($owncloud_lib, $owncloud->directory)) {
                $this->log('WAR'.$this->l('Sending backup to ownCloud account:').' '.$this->l('Error while deleting old backup'));
                return false;
            }

            // Check available space
            $available_space = $owncloud_lib->getAvailableQuota();

            if ($available_space >= 0) { // Negative value probably mean unlimitted space
                if ($this->getConfig('SEND_RESTORE')) {
                    $total_file_size = $this->getFileSize($this->restore_file);
                    $available_space -= $total_file_size;
                }

                if ($available_space <= $this->total_size) {
                    $this->log('WAR'.$this->l('Sending backup to ownCloud account:').' '.$this->l('Not enough space available'));
                    return false;
                }
            }

            $this->log($this->l('Sending backup to ownCloud account:').' '.$this->l('Check the path of your backup'));

            // Check if the folder we want to use exists. If not we create it.
            if ($owncloud_lib->folderExists($owncloud->directory) === false) {
                $this->log($this->l('Sending backup to ownCloud account:').' '.$this->l('Create the directory').' "'.$owncloud->directory.'"');
                if ($owncloud_lib->createFolder($owncloud->directory) === false) {
                    $this->log('WAR'.$this->l('Sending backup to ownCloud account:').' '.$this->l('Error while creating the directory').' "'.$owncloud->directory.'"');
                    return false;
                }
            }

            $this->log($this->l('Sending backup to ownCloud account...'));

            $this->owncloud_session     = rand();
            $this->owncloud_nb_part     = 1;
            $this->owncloud_nb_chunk    = 0;
            $this->owncloud_position    = 0;
        }

        $nb_part = 1;

        // Upload the file
        foreach ($this->part_list as $part) {
            if ($nb_part == $this->owncloud_nb_part) {
                $file_name = basename($part);
                $file_path = $part;

                if ($this->next_step == $this->step_send['owncloud']) {
                    $this->next_step = $this->step_send['owncloud_resume'];
                }

                // Upload the file
                if ($owncloud_lib->uploadFile($file_path, $owncloud_dir, $file_name, $this->owncloud_position, $this->owncloud_nb_part, $this->total_nb_part) === false) {
                    return false;
                }

                $this->owncloud_nb_part++;
                // New part, so back to init values
                $this->next_step            = $this->step_send['owncloud'];
                $this->owncloud_position    = 0;
                $this->owncloud_nb_chunk    = 0;
                $this->owncloud_session     = rand();
            }
            $nb_part++;
        }

        $this->next_step = $this->step_send['owncloud_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            $this->log($this->l('Sending restore file to ownCloud account...'));
            // Upload the file
            if ($owncloud_lib->uploadFile($this->restore_file, $owncloud_dir, self::NEW_RESTORE_NAME) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send a file on a WebDAV account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToWebdav()
    {
        $webdav         = new Webdav($this->webdav_account_id);
        $webdav_server  = $webdav->server;
        $webdav_user    = $webdav->login;
        $webdav_pass    = $this->decrypt($webdav->password);
        $webdav_dir     = $webdav->directory;

        if ($this->next_step == $this->step_send['webdav'] && (!isset($this->webdav_nb_part) || $this->webdav_nb_part == 1)) {
            $this->log($this->l('Connect to your WebDAV account...'));
        }

        $webdav_lib = $this->connectToWebdav($webdav_server, $webdav_user, $webdav_pass);

        //WebDAV dir should end with a "/" except when testing if exist
        if (Tools::substr($webdav_dir, -1) != '/') {
            $webdav_dir .= '/';
        }

        if ($this->next_step == $this->step_send['webdav'] && (!isset($this->webdav_nb_part) || $this->webdav_nb_part == 1)) {
            // If file already on WebDAV
            foreach ($this->part_list as $part) {
                $file_destination   = $webdav_dir.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                if ($webdav_lib->fileExists($file_destination)) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($webdav_lib->deleteFile($file_destination) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$part);
                    }
                }
            }

            // Delete old backup
            if (!$this->deleteWebdavOldBackup($webdav_lib, $webdav_dir)) {
                $this->log('WAR'.$this->l('Sending backup to WebDAV account:').' '.$this->l('Error while deleting old backup'));
                return false;
            }

            // Check available space
            $available_space = $webdav_lib->getAvailableQuota();

            if ($available_space != -1) {
                if ($this->getConfig('SEND_RESTORE')) {
                    $total_file_size = $this->getFileSize($this->restore_file);
                    $available_space -= $total_file_size;
                }

                if ($available_space <= $this->total_size) {
                    $this->log('WAR'.$this->l('Sending backup to WebDAV account:').' '.$this->l('Not enough space available'));
                    return false;
                }
            }

            $this->log($this->l('Sending backup to WebDAV account:').' '.$this->l('Check the path of your backup'));

            // Check if the folder we want to use exists. If not we create it.
            if ($webdav_lib->folderExists($webdav_dir) === false) {
                $this->log($this->l('Sending backup to WebDAV account:').' '.$this->l('Create the directory').' "'.$webdav->directory.'"');
                if ($webdav_lib->createFolder($webdav_dir) === false) {
                    $this->log('WAR'.$this->l('Sending backup to WebDAV account:').' '.$this->l('Error while creating the directory').' "'.$webdav->directory.'"');
                    return false;
                }
            }

            $this->log($this->l('Sending backup to WebDAV account...'));

            $this->webdav_session     = rand();
            $this->webdav_nb_part     = 1;
            $this->webdav_nb_chunk    = 0;
            $this->webdav_position    = 0;
        }

        $nb_part = 1;

        // Upload the file
        foreach ($this->part_list as $part) {
            if ($nb_part == $this->webdav_nb_part) {
                $file_name = basename($part);
                $file_path = $part;

                if ($this->next_step == $this->step_send['webdav']) {
                    $this->next_step = $this->step_send['webdav_resume'];
                }

                // Upload the file
                if ($webdav_lib->uploadFile($file_path, $webdav_dir, $file_name, $this->webdav_position, $this->webdav_nb_part, $this->total_nb_part) === false) {
                    return false;
                }

                $this->webdav_nb_part++;
                // New part, so back to init values
                $this->next_step            = $this->step_send['webdav'];
                $this->webdav_position    = 0;
                $this->webdav_nb_chunk    = 0;
                $this->webdav_session     = rand();
            }
            $nb_part++;
        }

        $this->next_step = $this->step_send['webdav_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            $this->log($this->l('Sending restore file to WebDAV account...'));
            // Upload the file
            if ($webdav_lib->uploadFile($this->restore_file, $webdav_dir, self::NEW_RESTORE_NAME) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send a file on a Google Drive account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToGoogledrive()
    {
        $googledrive        = new Googledrive($this->googledrive_account_id);
        $access_token       = $googledrive->token;
        $googledrive_dir    = $googledrive->directory_key;

        if ($this->next_step == $this->step_send['googledrive'] && (!isset($this->googledrive_nb_part) || $this->googledrive_nb_part == 1)) {
            $this->log($this->l('Connect to your Google Drive account...'));
        }

        $googledrive_lib = $this->connectToGoogledrive($access_token);

        if ($this->next_step == $this->step_send['googledrive'] && (!isset($this->googledrive_nb_part) || $this->googledrive_nb_part == 1)) {
            if (Tools::substr($googledrive->directory_path, -1) != '/') {
                $googledrive->directory_path .= '/';
            }

            // If file already on Google Drive
            foreach ($this->part_list as $part) {
                $file_destination = $googledrive->directory_path.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                $id_file = $googledrive_lib->checkExists(basename($part), $googledrive_dir);

                if ($id_file !== false) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($googledrive_lib->deleteFile($id_file) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                    }
                }
            }

            // Delete old backup
            if (!$this->deleteGoogledriveOldBackup($googledrive_lib, $googledrive_dir)) {
                $this->log('WAR'.$this->l('Sending backup to Google Drive account:').' '.$this->l('Error while deleting old backup'));
                return false;
            }

            // Check available space
            $available_space = $googledrive_lib->getAvailableQuota();

            if ($available_space != '-1') {
                if ($this->getConfig('SEND_RESTORE')) {
                    $total_file_size = $this->getFileSize($this->restore_file);
                    $available_space -= $total_file_size;
                }

                if ($available_space <= $this->total_size) {
                    $this->log('WAR'.$this->l('Sending backup to Google Drive account:').' '.$this->l('Not enough space available'));
                    return false;
                }
            }

            $this->log($this->l('Sending backup to Google Drive account...'));
            $this->googledrive_nb_part = 1;
            $this->googledrive_position = 0;
        }

        $nb_part = 1;
        // Upload the file
        foreach ($this->part_list as $part) {
            if ($nb_part == $this->googledrive_nb_part) {
                if ($this->next_step == $this->step_send['googledrive']) {
                    $this->next_step = $this->step_send['googledrive_resume'];
                    // Upload the file
                    if ($googledrive_lib->uploadFile($part, $googledrive_dir, '', $this->googledrive_nb_part, $this->total_nb_part) === false) {
                        return false;
                    }
                } else {
                    // Resume upload of the file
                    if ($googledrive_lib->resumeUploadFile($part, $this->googledrive_nb_part, $this->total_nb_part) === false) {
                        return false;
                    }
                }
                $this->googledrive_nb_part++;
                // New part, so back to init values
                $this->next_step = $this->step_send['googledrive'];
                $this->googledrive_position = 0;
            }

            $nb_part++;
        }

        $this->next_step = $this->step_send['googledrive_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            if (Tools::substr($googledrive->directory_path, -1) != '/') {
                $googledrive->directory_path .= '/';
            }

            $file_destination = $googledrive->directory_path.self::NEW_RESTORE_NAME;
            $id_file = $googledrive_lib->checkExists(self::NEW_RESTORE_NAME, $googledrive_dir);

            if ($id_file !== false) {
                $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                if ($googledrive_lib->deleteFile($id_file) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                }
            }

            $this->log($this->l('Sending restore file to Google Drive account...'));
            // Upload the file
            if ($googledrive_lib->uploadFile($this->restore_file, $googledrive_dir, self::NEW_RESTORE_NAME) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send a file on a Hubic account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToHubic()
    {
        $hubic  = new Hubic($this->hubic_account_id);

        if ($this->next_step == $this->step_send['hubic'] && (!isset($this->hubic_nb_part) || $this->hubic_nb_part == 1)) {
            $this->log($this->l('Connect to your Hubic account...'));
        }

        $hubic_lib = $this->connectToHubic($this->hubic_account_id);

        if ($this->next_step == $this->step_send['hubic'] && (!isset($this->hubic_nb_part) || $this->hubic_nb_part == 1)) {
            // Hubic dir should end with a "/" if not empty
            if (Tools::substr($hubic->directory, -1) != '/' && $hubic->directory != '') {
                $this->hubic_dir = $hubic->directory.'/';
            }

            // If file already on Hubic
            foreach ($this->part_list as $part) {
                $file_destination   = $this->hubic_dir.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                if ($hubic_lib->checkExists($file_destination)) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($hubic_lib->deleteFile($file_destination) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$part);
                    }
                }
            }

            // Delete old backup
            if (!$this->deleteHubicOldBackup($hubic_lib)) {
                $this->log('WAR'.$this->l('Sending backup to Hubic account:').' '.$this->l('Error while deleting old backup'));
                return false;
            }

            // Check available space
            $available_space = $hubic_lib->fetchQuota();

            if ($this->getConfig('SEND_RESTORE')) {
                $total_file_size = $this->getFileSize($this->restore_file);
                $available_space -= $total_file_size;
            }

            if ($available_space <= $this->total_size) {
                $this->log('WAR'.$this->l('Sending backup to Hubic account:').' '.$this->l('Not enough space available'));
                return false;
            }

            $this->log($this->l('Sending backup to Hubic account...'));
            $this->hubic_nb_part    = 1;
            $this->hubic_nb_chunk   = 1;
            $this->hubic_position   = 0;
        }

        $nb_part = 1;
        // Upload the file
        foreach ($this->part_list as $part) {
            if ($nb_part == $this->hubic_nb_part) {
                if ($this->next_step == $this->step_send['hubic'] || $this->hubic_nb_chunk == 1) {
                    $this->next_step = $this->step_send['hubic_resume'];
                    // Upload the file
                    if ($hubic_lib->createFile($part, $this->hubic_dir.basename($part), $this->hubic_nb_part, $this->total_nb_part) === false) {
                        return false;
                    }
                } else {
                    // Resume upload of the file
                    if ($hubic_lib->resumeCreateFile($part, $this->hubic_dir.basename($part), $this->hubic_nb_part, $this->total_nb_part, $this->hubic_position, $this->hubic_nb_chunk) === false) {
                        return false;
                    }
                }
                $this->hubic_nb_part++;
                // New part, so back to init values
                $this->next_step = $this->step_send['hubic'];
                $this->hubic_position   = 0;
                $this->hubic_nb_chunk   = 1;
            }

            $nb_part++;
        }

        $this->next_step = $this->step_send['hubic_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            $this->log($this->l('Sending restore file to Hubic account...'));
            // Upload the file
            if ($hubic_lib->createFile($this->restore_file, $this->hubic_dir.basename($this->restore_file)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send a file on a AWS account
     *
     * @return  boolean     The success or failure of the operation
     */
    protected function sendFileToAws()
    {
        $aws                = new Aws($this->aws_account_id);
        $aws_directory_key  = $aws->directory_key;
        $prefix             = $this->correctFileName($this->getConfig('PS_SHOP_NAME'));

        if (false === $aws->bucket || '' == $aws->bucket) {
            $this->log('WAR'.$this->l('No bucket configured'));
            return false;
        }

        if ($this->next_step == $this->step_send['aws'] && (!isset($this->aws_nb_part) || $this->aws_nb_part == 1)) {
            $this->log($this->l('Connect to your AWS account...'));
        }

        $aws_lib = $this->connectToAws($aws->access_key_id, $aws->secret_access_key, $aws->region, $aws->bucket);

        if ($this->next_step == $this->step_send['aws'] && (!isset($this->aws_nb_part) || $this->aws_nb_part == 1)) {
            if (Tools::substr($aws_directory_key, -1) != '/') {
                $aws_directory_key .= '/';
            }

            // Check if directory exists
            $dir_exists = $aws_lib->checkDirectoryExists($aws->directory_key);

            if ($dir_exists === false) {
                $this->log('WAR'.$this->l('Error while creating your file: directory unknow').' ('.$aws->directory_path.')');
                return false;
            }

            // If file already on Aws
            foreach ($this->part_list as $part) {
                $file_destination = $aws_directory_key.basename($part);

                $this->log($this->l('Check if there is a previous version of the file:').' '.$file_destination);
                $file_exists = $aws_lib->checkFileExists(basename($part), $aws_directory_key);

                if ($file_exists !== false) {
                    $this->log($this->l('Delete previous version of the file:').' '.$file_destination);
                    if ($aws_lib->deleteFile($file_destination) === false) {
                        $this->log($this->l('Error while deleting the file:').' '.$file_destination);
                    }
                }
            }

            // Delete old backup
            if (!$this->deleteAwsOldBackup($aws_lib)) {
                $this->log('WAR'.$this->l('Sending backup to AWS account:').' '.$this->l('Error while deleting old backup'));
                return false;
            }

            $this->log($this->l('Sending backup to AWS account...'));
            $this->aws_nb_part      = 1;
            $this->aws_upload_part  = 1;
            $this->aws_position     = 0;
            $this->aws_etag         = array();
        } else {
            if (!$this->aws_upload_id || $this->aws_upload_id == '') {
                $this->log('WAR'.$this->l('Sending backup to AWS account:').' '.$this->l('Invalid upload ID'));
                return false;
            }
        }

        $nb_part = 1;

        // Upload the file
        foreach ($this->part_list as $part) {
            if ($nb_part == $this->aws_nb_part) {
                $file_name = basename($part);
                $file_path = $part;

                if ($this->next_step == $this->step_send['aws']) {
                    $this->next_step = $this->step_send['aws_resume'];
                    // Upload the file
                    if ($aws_lib->uploadFile($file_name, $file_path, $aws->directory_key, $this->aws_upload_part, $this->aws_nb_part, $this->total_nb_part, $prefix) === false) {
                        $this->log('WAR'.$this->l('Sending backup to AWS account:').' '.$this->l('Upload failed'));
                        return false;
                    }
                } else {
                    // Resume upload of the file
                    if ($aws_lib->resumeUploadFile($this->aws_upload_id, $file_name, $aws->directory_key, $file_path, $this->aws_upload_part, $this->aws_nb_part, $this->total_nb_part, $this->aws_position) === false) {
                        $this->log('WAR'.$this->l('Sending backup to AWS account:').' '.$this->l('Upload failed'));
                        return false;
                    }
                }
                $this->aws_nb_part++;
                // New part, so back to init values
                $this->next_step        = $this->step_send['aws'];
                $this->aws_upload_part  = 1;
                $this->aws_position     = 0;
                $this->aws_etag         = array();
            }
            $nb_part++;
        }

        $this->next_step = $this->step_send['aws_resume'];

        if ($this->getConfig('SEND_RESTORE')) {
            $this->aws_nb_part      = 1;
            $nb_part_total          = 1;
            $this->aws_upload_part  = 1;
            $this->aws_position     = 0;
            $this->aws_etag         = array();
            $this->log($this->l('Sending restore file to AWS account...'));
            // Upload the file
            if ($aws_lib->uploadFile(self::NEW_RESTORE_NAME, $this->restore_file, $aws->directory_key, $this->aws_upload_part, $this->aws_nb_part, $nb_part_total, self::NEW_RESTORE_NAME) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete old backup files on a Dropbox account
     *
     * @param   String      $access_token   Dropbox token
     * @param   String      $dropbox_dir    Dropbox directory
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteDropboxOldBackup($access_token, $dropbox_dir)
    {
        $dropbox            = new Dropbox($this->dropbox_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $dropbox->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $dropbox->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $dropbox->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to Dropbox account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to Dropbox account:').' '.$this->l('Deleting old backup'));

        $dropbox_lib = $this->connectToDropbox($access_token);

        //Find all old backups
        // Get informations on the directory and his children
        $folder_children    = $dropbox_lib->listFolderChildren($dropbox_dir);
        $children           = $folder_children['entries']; // get contents of the directory
        $old_backups        = array();

        if (is_array($children)) {
            foreach ($children as $child) {
                $child      = (array)$child;
                $infos_file = pathinfo($child['path_lower']);

                if ($child['.tag'] == 'file') {
                    if ($infos_file['extension'] == 'tar'
                        || strpos($child['path_lower'], '.tar.') !== false
                        || (
                            $this->getConfig('SEND_RESTORE')
                            && strpos($child['path_lower'], self::NEW_RESTORE_NAME) !== false
                        )
                    ) {
                        if (strpos($child['path_lower'], $test_type_backup) !== false) {
                            $old_backups[] = $child['path_lower'];
                        }
                    }
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);
        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                if ($dropbox_lib->deleteFile($part['name']) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                }
            }

            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a ownCloud account
     *
     * @param   object      $owncloud_lib   ownCloud to use
     * @param   String      $owncloud_dir   ownCloud directory
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteOwncloudOldBackup($owncloud_lib, $owncloud_dir)
    {
        $owncloud           = new Owncloud($this->owncloud_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $owncloud->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $owncloud->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $owncloud->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to ownCloud account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to ownCloud account:').' '.$this->l('Deleting old backup'));

        //Find all old backups
        $children = $owncloud_lib->getFileChildren($owncloud_dir); // get the files of the directory
        $old_backups = array();

        if (is_array($children)) {
            foreach ($children as $child) {
                $infos_file = pathinfo($child);
                if ($infos_file['extension'] == 'tar'
                    || strpos($child, '.tar.') !== false
                    || (
                        $this->getConfig('SEND_RESTORE')
                        && strpos($child, self::NEW_RESTORE_NAME) !== false
                        )
                ) {
                    if (strpos($child, $test_type_backup) !== false) {
                        if ($owncloud_dir == '') {
                            $old_backups[] = str_replace('/remote.php/webdav/', '', $child);
                        } else {
                            if (Tools::substr($owncloud_dir, -1) != '/') {
                                $owncloud_dir .= '/';
                            }
                            $old_backups[] = $owncloud_dir.$child;
                        }
                    }
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);
        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                if ($owncloud_lib->deleteFile($part['name']) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                }
            }

            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a WebDAV account
     *
     * @param   object      $webdav_lib     WebDAV to use
     * @param   String      $webdav_dir     WebDAV directory
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteWebdavOldBackup($webdav_lib, $webdav_dir)
    {
        $webdav             = new Webdav($this->webdav_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $webdav->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $webdav->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $webdav->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to WebDAV account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to WebDAV account:').' '.$this->l('Deleting old backup'));

        //Find all old backups
        $children = $webdav_lib->getFileChildren($webdav_dir); // get the files of the directory

        $old_backups = array();

        if (is_array($children)) {
            foreach ($children as $child) {
                $infos_file = pathinfo($child);
                if ($infos_file['extension'] == 'tar'
                    || strpos($child, '.tar.') !== false
                    || (
                        $this->getConfig('SEND_RESTORE')
                        && strpos($child, self::NEW_RESTORE_NAME) !== false
                        )
                ) {
                    if (strpos($child, $test_type_backup) !== false) {
                        if ($webdav_dir == '') {
                            $old_backups[] = str_replace('/remote.php/webdav/', '', $child);
                        } else {
                            if (Tools::substr($webdav_dir, -1) != '/') {
                                $webdav_dir .= '/';
                            }
                            $old_backups[] = $webdav_dir.$child;
                        }
                    }
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);
        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                if ($webdav_lib->deleteFile($part['name']) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                }
            }

            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a Google Drive account
     *
     * @param   object      $googledrive_lib    Google Drive to use
     * @param   String      $googledrive_dir    Google Drive directory
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteGoogledriveOldBackup($googledrive_lib, $googledrive_dir)
    {
        $googledrive        = new Googledrive($this->googledrive_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $googledrive->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $googledrive->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $googledrive->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        $old_backups        = array();
        $old_backups_sort   = array();

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to Google Drive account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to Google Drive account:').' '.$this->l('Deleting old backup'));

        $children = $googledrive_lib->getChildrenFiles($googledrive_dir);

        foreach ($children as $child) {
            $infos_file = pathinfo($child['name']);
            if ($infos_file['extension'] == 'tar'
                || strpos($child['name'], '.tar.') !== false
                || (
                    $this->getConfig('SEND_RESTORE')
                    && strpos($child['name'], self::NEW_RESTORE_NAME) !== false
                    )
            ) {
                if (strpos($child['name'], $test_type_backup) !== false) {
                    $old_backups[$child['id']] = $child['name'];
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);

        // We need to know the id of each files (by name so we can get it easier)
        foreach ($old_backups as $id_child => $name_child) {
            $old_backups_sort[$name_child] = $id_child;
        }

        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                // We delete the file by the ID link to the its name
                if ($googledrive_lib->deleteFile($old_backups_sort[$part['name']]) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                }
            }
            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a Hubic account
     *
     * @param   object      $hubic_lib      Hubic to use
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteHubicOldBackup($hubic_lib)
    {
        $hubic              = new Hubic($this->hubic_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $hubic->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $hubic->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $hubic->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to Hubic account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to Hubic account:').' '.$this->l('Deleting old backup'));

        //Find all old backups
        $children       = array_merge($hubic_lib->listFiles($hubic->directory));
        $old_backups    = array();

        foreach ($children as $child) {
            $infos_file = pathinfo($child['name']);

            if (!isset($infos_file['extension']) || strpos($child['content_type'], 'hubic/') !== false) {
                continue;
            }

            if ($infos_file['extension'] == 'tar'
                || strpos($child['name'], '.tar.') !== false
                || (
                    $this->getConfig('SEND_RESTORE')
                    && strpos($child['name'], self::NEW_RESTORE_NAME) !== false
                    )
            ) {
                if (strpos($child['name'], $test_type_backup) !== false) {
                    $old_backups[] = $child['name'];
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);
        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                $this->log('Delete '.$part['name']);
                // We can now delete the manifest (or the file if it was small enough)
                if ($hubic_lib->deleteFile($part['name']) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                }
            }

            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a OneDrive account
     *
     * @param   object      $onedrive_lib   OneDrive to use
     * @param   String      $id_directory   OneDrive directory ID
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteOnedriveOldBackup($onedrive_lib, $id_directory)
    {
        $onedrive           = new Onedrive($this->onedrive_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $onedrive->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $onedrive->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $onedrive->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to OneDrive account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to OneDrive account:').' '.$this->l('Deleting old backup'));

        //$access_token = OneDrive::getOnedriveAccessToken($onedrive_code);

        $children = $onedrive_lib->getListChildren($id_directory);

        if ($children === false) {
            return true; // No child to delete
        }

        $old_backups        = array();
        $old_backups_sort   = array();

        foreach ($children as $child) {
            $infos_file = pathinfo($child['name']);

            if (!$child['is_folder']) {
                if ($infos_file['extension'] == 'tar'
                    || strpos($child['name'], '.tar.') !== false
                    || (
                        $this->getConfig('SEND_RESTORE')
                        && strpos($child['name'], self::NEW_RESTORE_NAME) !== false
                        )
                ) {
                    if (strpos($child['name'], $test_type_backup) !== false) {
                        $old_backups[$child['id']] = $child['name'];
                    }
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);

        foreach ($old_backups as $id_child => $name_child) {
            $old_backups_sort[$name_child] = $id_child;
        }

        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                if (!isset($old_backups_sort[$part['name']])) {
                    $this->log($this->l('Error unknown file:').' '.$part['name']);
                } else {
                    if (!$onedrive_lib->deleteItem($old_backups_sort[$part['name']])) {
                        $this->log($this->l('Error while deleting the file:').' '.$part['name']);
                    }
                }
            }
            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a AWS account
     *
     * @param   object      $aws_lib    AWS to use
     *
     * @return  boolean                 The success or failure of the connection
     */
    public function deleteAwsOldBackup($aws_lib)
    {
        $aws                = new Aws($this->aws_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $aws->nb_backup;
        $aws_directory_key  = $aws->directory_key;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $aws->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $aws->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to AWS account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to AWS account:').' '.$this->l('Deleting old backup'));

        if (Tools::substr($aws_directory_key, -1) != '/') {
            $aws_directory_key .= '/';
        }

        if ($aws_directory_key != $aws->bucket) {
            $children = $aws_lib->getListFiles($aws_directory_key);
        } else {
            $children = $aws_lib->getListFiles();
        }

        if ($children === false) {
            return true; // No child to delete
        }

        $old_backups    = array();

        foreach ($children as $child) {
            $infos_file = pathinfo($child['name']);

            if ($infos_file['extension'] == 'tar'
                || strpos($child['name'], '.tar.') !== false
                || (
                    $this->getConfig('SEND_RESTORE')
                    && strpos($child['name'], self::NEW_RESTORE_NAME) !== false
                    )
            ) {
                if (strpos($child['name'], $test_type_backup) !== false) {
                    $old_backups[] = $child['name'];
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);
        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                // We can now delete the file
                if ($aws_lib->deleteFile($aws_directory_key.$part['name']) === false) {
                    $this->log($this->l('Error while deleting the file:').' '.$aws_directory_key.$part['name']);
                }
            }

            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a FTP account
     *
     * @param   ressource   $connection     FTP connection
     *
     * @return  boolean                     The success or failure of the connection
     */
    protected function deleteFTPOldBackup($connection)
    {
        $ftp = new Ftp($this->ftp_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $ftp->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $ftp->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $ftp->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to FTP account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to FTP account:').' '.$this->l('Deleting old backup'));

        //Find all old backups
        $files = ftp_nlist($connection, '');

        if ($files === false) {
            $this->log($this->l('Error while listing old FTP files'));
            return false;
        }

        $old_backups    = array();

        foreach ($files as $file) {
            $infos_file = pathinfo($file);
            if ($infos_file['extension'] == 'tar'
                || strpos($file, '.tar.') !== false
                || (
                    $this->getConfig('SEND_RESTORE')
                    && strpos($file, self::NEW_RESTORE_NAME) !== false
                    )
            ) {
                if (strpos($file, $test_type_backup) !== false) {
                    $old_backups[] = $file;
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);
        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                if (!ftp_delete($connection, basename($part['name']))) {
                    $this->log($this->l('Delete old backup file failed:').basename($part['name']));
                    return false;
                }
            }
            $nb_files--;
        }

        return true;
    }

    /**
     * Delete old backup files on a SFTP account
     *
     * @param   object      $sftp_lib   SFTP to use
     * @param   String      $ftp_dir    SFTP directory
     *
     * @return  boolean                 The success or failure of the connection
     */
    protected function deleteSFTPOldBackup($sftp_lib, $ftp_dir)
    {
        $ftp                = new Ftp($this->ftp_account_id);
        $test_type_backup   = $this->suffix_backup;
        $nb_backup_to_keep  = $ftp->nb_backup;

        if (strpos($this->tar_file, $this->suffix_backup_base) !== false) {
            $nb_backup_to_keep  = $ftp->nb_backup_base;
            $test_type_backup   = $this->suffix_backup_base;
        } elseif (strpos($this->tar_file, $this->suffix_backup_file) !== false) {
            $nb_backup_to_keep  = $ftp->nb_backup_file;
            $test_type_backup   = $this->suffix_backup_file;
        }

        //Do we need to delete old backups
        if ($nb_backup_to_keep == 0) {
            $this->log($this->l('Sending backup to SFTP account:').' '.$this->l('Keep all old backup'));
            return true;
        }

        $this->log($this->l('Sending backup to SFTP account:').' '.$this->l('Deleting old backup'));

        //Find all old backups
        $files          = $sftp_lib->nlist($ftp_dir);
        $old_backups    = array();

        if (is_array($files)) {
            foreach ($files as $file) {
                $infos_file = pathinfo($file);
                if ($infos_file['extension'] == 'tar'
                    || strpos($file, '.tar.') !== false
                    || (
                        $this->getConfig('SEND_RESTORE')
                        && strpos($file, self::NEW_RESTORE_NAME) !== false
                        )
                ) {
                    if (strpos($file, $test_type_backup) !== false) {
                        $old_backups[] = $file;
                    }
                }
            }
        }

        $clean_list_old_backups = $this->cleanListBackup($old_backups);

        $nb_files = count($clean_list_old_backups);

        //Do we really need to delete old backups
        if ($nb_files < $nb_backup_to_keep) {
            return true;
        }

        //Yes we have to delete old backups
        while ($nb_files >= $nb_backup_to_keep) {
            foreach ($clean_list_old_backups[$nb_files]['part'] as $part) {
                if (!$sftp_lib->delete($ftp_dir.basename($part['name']))) {
                    $this->log($this->l('Delete old backup file failed:').basename($part['name']));
                    return false;
                }
            }
            $nb_files--;
        }

        return true;
    }

    /**
     * Get Dropbox access token
     *
     * @param   String  $dropbox_code   Code that will give the token
     *
     * @return  String                  The token
     */
    public function getDropboxAccessToken($dropbox_code)
    {
        $dropbox_lib = $this->connectToDropbox();
        $access_token = $dropbox_lib->getToken($dropbox_code);

        return $access_token;
    }

    /**
     * Get Google Drive access token
     *
     * @param   String  $googledrive_code   Code that will give the token
     *
     * @return  String                      The token
     */
    public function getGoogledriveAccessToken($googledrive_code)
    {
        $googledrive_lib = $this->connectToGoogledrive();
        $access_token = $googledrive_lib->getToken($googledrive_code);

        if (empty($access_token) || !$access_token) {
            return false;
        }

        return Tools::jsonEncode($access_token);
    }

    /**
     * Get OneDrive access token
     *
     * @param   String  $onedrive_code      Code that will give the token
     *
     * @return  String                      The token
     */
    public function getOnedriveAccessToken($onedrive_code)
    {
        $onedrive_lib = $this->connectToOnedrive();
        $access_token = $onedrive_lib->getToken($onedrive_code);

        if (empty($access_token) || !$access_token) {
            return false;
        }

        return Tools::jsonEncode($access_token);
    }

    /**
     * Get Hubic access token
     *
     * @param   String  $hubic_code     Code that will give the token
     *
     * @return  String                  The token
     */
    public function getHubicAccessToken($hubic_code)
    {
        $hubic_lib      = $this->connectToHubic();
        $access_token   = $hubic_lib->getToken($hubic_code);

        if (empty($access_token) || !$access_token) {
            return false;
        }

        $credential = $hubic_lib->getCredential();

        if (!is_array($credential)) {
            return false;
        }

        return array(
            'token'         => Tools::jsonEncode($access_token),
            'credential'    => Tools::jsonEncode($credential),
        );
    }

    /**
     * Get Google Drive refresh token
     *
     * @param   String  $refresh_token  Code that will give the refresh token
     *
     * @return  String                  The token
     */
    public function getGoogledriveRefreshToken($refresh_token)
    {
        $googledrive_lib = $this->connectToGoogledrive();
        $access_token = Tools::jsonEncode($googledrive_lib->getRefreshToken($refresh_token));

        return $access_token;
    }

    /**
     * Get OneDrive refresh token
     *
     * @param   String  $refresh_token  Code that will give the refresh token
     *
     * @return  String                  The token
     */
    public function getOnedriveRefreshToken($refresh_token)
    {
        $onedrive_lib = $this->connectToOnedrive();
        $access_token = $onedrive_lib->getRefreshToken($refresh_token);

        return Tools::jsonEncode($access_token);
    }

    /**
     * Get Hubic refresh token
     *
     * @param   String  $refresh_token  Code that will give the refresh token
     *
     * @return  String                  The token
     */
    public function getHubicRefreshToken($refresh_token)
    {
        $hubic_lib = $this->connectToHubic();

        $access_token = $hubic_lib->getRefreshToken($refresh_token);

        if (empty($access_token) || !$access_token) {
            return false;
        }

        $access_token['refresh_token'] = $refresh_token;
        $credential = $hubic_lib->getCredential();

        if (!is_array($credential)) {
            return false;
        }

        return array(
            'token'         => Tools::jsonEncode($access_token),
            'credential'    => Tools::jsonEncode($credential),
        );
    }

    /**
     * Get OneDrive access token url
     *
     * @param   object  $onedrive_lib   OneDrive that we need the url from
     *
     * @return  String                  The url that will give the access token
     */
    public function getOnedriveAccessTokenUrl($onedrive_lib)
    {
        $url = $onedrive_lib->getLogInUrl();

        if ($url === false) {
            return false;
        }

        return $url;
    }

    /**
     * Close SFTP
     *
     * @param   object  $sftp_lib   SFTP to close
     *
     * @return  boolean             The success or failure of the operation
     */
    public function closeSFTP($sftp_lib)
    {
        if ($sftp_lib->exec('exit;') === false) {
            return false;
        }

        return true;
    }

    /**
     * Get a list of all directories of the Google Drive account
     *
     * @param   object  $googledrive_dir    Google Drive directory
     *
     * @return  String                      The tree of Google Drive directories
     */
    public function getGoogledriveTree($googledrive_dir)
    {
        $level = 1;
        $select = '';
        $tree = '<ul id="googledrive_dir" class="googledrive_tree">';

        $id_parent = self::GOOGLEDRIVE_ROOT_ID;
        $parent_name = $this->l('Home');
        $path = $parent_name;

        if ($googledrive_dir == $id_parent) {
            $select = 'checked="checked"';
        }

        $tree .= '<li class="level-'.$level.'">';
            $tree .= '<span>';
                $tree .= '<input type="radio" class="googledrive_dir" name="googledrive_dir" value="'.$id_parent.'" '.$select.' id="'.$id_parent.'"/>';
                $tree .= '<input type="hidden" name="googledrive_path" value="'.$path.'"/>';
                $tree .= '<label for="'.$id_parent.'">'.$parent_name.'</label>';
                $tree .= '<i class="far fa-plus-square" onclick="getGoogledriveTreeChildren(\''.$id_parent.'\', \''.$googledrive_dir.'\', \''.$level.'\', \''.$path.'\', this)"></i>';
            $tree .= '</span>';
        $tree .= '</li>';

        $tree .= '</ul>';

        return $tree;
    }

    /**
     * Get a list of all children directories of the parent directory of the Google Drive account
     *
     * @param   String  $access_token       Google Drive token
     * @param   String  $id_parent          Google Drive ID parent directory
     * @param   String  $googledrive_dir    Google Drive ID selected directory
     * @param   integer $level              Google Drive level of the directories
     * @param   String  $parent_path        Google Drive path parent directory
     *
     * @return  String                      The children directories tree of the given parent directory
     */
    public function getGoogledriveTreeChildren($access_token, $id_parent, $googledrive_dir, $level, $parent_path)
    {
        $googledrive_lib = $this->connectToGoogledrive($access_token);
        $level++;
        $tree_children = '';
        $children = $googledrive_lib->getChildrenTree($id_parent);

        if ($children === false || !count($children)) {
            return $tree_children;
        }

        $tree_children .= '<ul class="googledrive_tree_child">';

        foreach ($children as $child) {
            $select = '';
            $path = $parent_path.'/'.$child['name'];

            if ($googledrive_dir == $child['id']) {
                $select = 'checked="checked"';
            }

            $tree_children .= '<li class="level-'.$level.'">';
                $tree_children .= '<span>';
                    $tree_children .= '<input type="radio" class="googledrive_dir" name="googledrive_dir" value="'.$child['id'].'" '.$select.' id="'.$child['id'].'"/>';
                    $tree_children .= '<input type="hidden" name="googledrive_path" value="'.$path.'"/>';
                    $tree_children .= '<label for="'.$child['id'].'">'.$child['name'].'</label>';
                    $tree_children .= '<i class="far fa-plus-square" onclick="getGoogledriveTreeChildren(\''.$child['id'].'\', \''.$googledrive_dir.'\', \''.$level.'\', \''.$path.'\', this)"></i>';
                $tree_children .= '</span>';
            $tree_children .= '</li>';
        }

        $tree_children .= '</ul>';

        return $tree_children;
    }

    /**
     * Get a list of all directories of the OneDrive account
     *
     * @param   String      $access_token       OneDrive token
     * @param   String      $onedrive_dir       OneDrive ID selected directory
     * @param   integer     $id_ntbr_onedrive   OneDrive account ID
     *
     * @return  String                          The tree of OneDrive directories
     */
    public function getOnedriveTree($access_token, $onedrive_dir, $id_ntbr_onedrive)
    {
        $onedrive_lib = $this->connectToOnedrive($access_token, $id_ntbr_onedrive);

        $level = 1;
        $select = '';
        $tree = '<ul id="onedrive_dir" class="onedrive_tree">';

        $id_parent = $onedrive_lib->getRootID();
        $parent_name = $this->l('Home');
        $path = $parent_name;

        if ($onedrive_dir == $id_parent) {
            $select = 'checked="checked"';
        }

        $tree .= '<li class="level-'.$level.'">';
            $tree .= '<span>';
                $tree .= '<input type="radio" class="onedrive_dir" name="onedrive_dir" value="'.$id_parent.'" '.$select.' id="'.$id_parent.'"/>';
                $tree .= '<input type="hidden" name="onedrive_path" value="'.$path.'"/>';
                $tree .= '<label for="'.$id_parent.'">'.$parent_name.'</label>';
                $tree .= '<i class="far fa-plus-square" onclick="getOnedriveTreeChildren(\''.$id_parent.'\', \''.$onedrive_dir.'\', \''.$level.'\', \''.$path.'\', this)"></i>';
            $tree .= '</span>';
        $tree .= '</li>';

        $tree .= '</ul>';

        return $tree;
    }

    /**
     * Get a list of all children directories of the parent directory of the OneDrive account
     *
     * @param   String      $access_token       OneDrive token
     * @param   String      $onedrive_dir       OneDrive ID selected directory
     * @param   String      $id_parent          OneDrive ID parent directory
     * @param   integer     $level              OneDrive level of the directories
     * @param   String      $parent_path        OneDrive path parent directory
     * @param   integer     $id_ntbr_onedrive   OneDrive account ID
     *
     * @return  String                          The children directories tree of the given parent directory
     */
    public function getOnedriveTreeChildren($access_token, $onedrive_dir, $id_parent, $level, $parent_path, $id_ntbr_onedrive)
    {
        $onedrive_lib = $this->connectToOnedrive($access_token, $id_ntbr_onedrive);
        $level++;
        $tree_children = '';
        $display_tree = false; // We add the list only if there is at least one folder

        $children = $onedrive_lib->getListChildren($id_parent);

        if ($children === false) {
            return $tree_children;
        }

        $tree_children .= '<ul class="onedrive_tree_child">';

        foreach ($children as $child) {
            if ($child['is_folder']) {
                $display_tree = true;
                $select = '';
                $path = $parent_path.'/'.$child['name'];

                if ($onedrive_dir == $child['id']) {
                    $select = 'checked="checked"';
                }

                $tree_children .= '<li class="level-'.$level.'">';
                    $tree_children .= '<span>';
                        $tree_children .= '<input type="radio" class="onedrive_dir" name="onedrive_dir" value="'.$child['id'].'" '.$select.' id="'.$child['id'].'"/>';
                        $tree_children .= '<input type="hidden" name="onedrive_path" value="'.$path.'"/>';
                        $tree_children .= '<label for="'.$child['id'].'">'.$child['name'].'</label>';
                    $tree_children .= '<i class="far fa-plus-square" onclick="getOnedriveTreeChildren(\''.$child['id'].'\', \''.$onedrive_dir.'\', \''.$level.'\', \''.$path.'\', this)"></i>';
                    $tree_children .= '</span>';
                $tree_children .= '</li>';
            }
        }

        $tree_children .= '</ul>';

        if (!$display_tree) {
            $tree_children = '';
        }

        return $tree_children;
    }

    /**
     * Get a list of all directories of the AWS account
     *
     * @param   integer     $id_ntbr_aws    AWS account ID
     *
     * @return  String                      The tree of AWS directories
     */
    public function getAwsTree($id_ntbr_aws)
    {
        $aws = new Aws($id_ntbr_aws);

        $level = 1;
        $select = '';
        $tree = '<ul id="aws_dir" class="aws_tree">';

        if ($aws->directory_key == $aws->bucket) {
            $select = 'checked="checked"';
        }

        // Root level (bucket)
        $tree .= '<li class="level-'.$level.'">';
            $tree .= '<span>';
                $tree .= '<input type="radio" class="aws_dir_key" name="aws_dir_key" value="'.$aws->bucket.'" '.$select.' id="'.$aws->bucket.'"/>';
                $tree .= '<input type="hidden" name="aws_dir_path" value="'.$aws->bucket.'"/>';
                $tree .= '<label for="'.$aws->bucket.'">'.$aws->bucket.'</label>';
                $tree .= '<i class="far fa-plus-square" onclick="getAwsTreeChildren(\''.$aws->bucket.'\', \''.$level.'\', \''.$aws->bucket.'\', this)"></i>';
            $tree .= '</span>';
        $tree .= '</li>';

        $tree .= '</ul>';

        return $tree;
    }

    /**
     * Get a list of all children directories of the parent directory of the AWS account
     *
     * @param   String      $id_parent      AWS name parent directory
     * @param   integer     $level          AWS level of the directories
     * @param   String      $parent_path    AWS path parent directory
     * @param   integer     $id_ntbr_aws    AWS account ID
     *
     * @return  String                      The children directories tree of the given parent directory
     */
    public function getAwsTreeChildren($id_parent, $level, $parent_path, $id_ntbr_aws)
    {
        $aws = new Aws($id_ntbr_aws);
        $aws_lib = $this->connectToAws($aws->access_key_id, $aws->secret_access_key, $aws->region, $aws->bucket);
        $level++;
        $tree_children = '';
        $display_tree = false; // We add the list only if there is at least one folder

        if ($id_parent == $aws->bucket) {
            // Only directory must be use as parent. No need if it is the bucket
            $children = $aws_lib->getListDirectories();
        } else {
            $children = $aws_lib->getListDirectories($id_parent);
        }

        if ($children === false) {
            return $tree_children;
        }

        $tree_children .= '<ul class="aws_tree_child">';

        foreach ($children as $child) {
            $display_tree = true;
            $select = '';
            $path = $parent_path.'/'.$child['name'];

            if ($aws->directory_key == $child['key']) {
                $select = 'checked="checked"';
            }

            $tree_children .= '<li class="level-'.$level.'">';
                $tree_children .= '<span>';
                    $tree_children .= '<input type="radio" class="aws_dir_key" name="aws_dir_key" value="'.$child['key'].'" '.$select.' id="'.$child['key'].'"/>';
                    $tree_children .= '<input type="hidden" name="aws_dir_path" value="'.$path.'"/>';
                    $tree_children .= '<label for="'.$child['key'].'">'.$child['name'].'</label>';
                $tree_children .= '<i class="far fa-plus-square" onclick="getAwsTreeChildren(\''.$child['key'].'\', \''.$level.'\', \''.$path.'\', this)"></i>';
                $tree_children .= '</span>';
            $tree_children .= '</li>';
        }

        $tree_children .= '</ul>';

        if (!$display_tree) {
            $tree_children = '';
        }

        return $tree_children;
    }

    /**
     * Send backup away
     */
    protected function sendBackupAway()
    {
        if ($this->next_step == self::STEP_SEND_AWAY) {
            $this->next_step = $this->step_send['ftp'];
        }

        $this->total_nb_part = count($this->part_list);

        if ($this->next_step == $this->step_send['ftp'] || $this->next_step == $this->step_send['ftp_resume']) {
            // Get all ftp accounts
            $ftp_accounts = Ftp::getListActiveFtpAccounts();

            if (count($ftp_accounts)) {
                foreach ($ftp_accounts as $ftp_account) {
                    if (!$this->ftp_account_id) {
                        // If we have no account id save we save the current ftp account id
                        $this->ftp_account_id = $ftp_account['id_ntbr_ftp'];
                    } elseif ($this->ftp_account_id != $ftp_account['id_ntbr_ftp']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    if ($ftp_account['sftp']) {
                        //Send backup to SFTP server
                        if ($this->next_step == $this->step_send['ftp']) {
                            $this->log($this->l('Sending backup to SFTP server').' '.$ftp_account['name'].'...');
                        }

                        if (!$this->sendFileToSFTP()) {
                            $this->log('WAR'.$this->l('Unable to send backup file to SFTP server').' '.$ftp_account['name']);
                        }
                    } else {
                        //Send backup to FTP server
                        if ($this->next_step == $this->step_send['ftp']) {
                            $this->log($this->l('Sending backup to FTP server').' '.$ftp_account['name'].'...');
                        }

                        if (!$this->sendFileToFTP()) {
                            $this->log('WAR'.$this->l('Unable to send backup file to FTP server').' '.$ftp_account['name']);
                        }
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->ftp_account_id = 0;
                    // Next step is to send to a new ftp (if there is still one)
                    $this->next_step = $this->step_send['ftp'];
                }

                // If we send to all ftp account, then we can go to the next step
                $this->next_step = $this->step_send['dropbox'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['dropbox'];
            }
        }

        if ($this->next_step == $this->step_send['dropbox'] || $this->next_step == $this->step_send['dropbox_resume']) {
            // Get all dropbox accounts
            $dropbox_accounts = Dropbox::getListActiveDropboxAccounts();

            if (count($dropbox_accounts)) {
                foreach ($dropbox_accounts as $dropbox_account) {
                    if (!$this->dropbox_account_id) {
                        // If we have no account id save we save the current dropbox account id
                        $this->dropbox_account_id = $dropbox_account['id_ntbr_dropbox'];
                    } elseif ($this->dropbox_account_id != $dropbox_account['id_ntbr_dropbox']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to Dropbox account
                    if ($this->next_step == $this->step_send['dropbox']) {
                        $this->log($this->l('Sending backup to Dropbox account').' '.$dropbox_account['name'].'...');
                    }

                    if (!$this->sendFileToDropbox()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to Dropbox account').' '.$dropbox_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->dropbox_account_id = 0;
                    // Next step is to send to a new dropbox (if there is still one)
                    $this->next_step = $this->step_send['dropbox'];
                }

                // If we send to all dropbox account, then we can go to the next step
                $this->next_step = $this->step_send['owncloud'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['owncloud'];
            }
        }

        if ($this->next_step == $this->step_send['owncloud'] || $this->next_step == $this->step_send['owncloud_resume']) {
            // Get all ownCloud accounts
            $owncloud_accounts = Owncloud::getListActiveOwncloudAccounts();

            if (count($owncloud_accounts)) {
                foreach ($owncloud_accounts as $owncloud_account) {
                    if (!$this->owncloud_account_id) {
                        // If we have no account id save we save the current ownCloud account id
                        $this->owncloud_account_id = $owncloud_account['id_ntbr_owncloud'];
                    } elseif ($this->owncloud_account_id != $owncloud_account['id_ntbr_owncloud']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to ownCloud account
                    if ($this->next_step == $this->step_send['owncloud']) {
                        $this->log($this->l('Sending backup to ownCloud account').' '.$owncloud_account['name'].'...');
                    }

                    if (!$this->sendFileToOwncloud()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to ownCloud account').' '.$owncloud_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->owncloud_account_id = 0;
                    // Next step is to send to a new ownCloud (if there is still one)
                    $this->next_step = $this->step_send['owncloud'];
                }

                // If we send to all ownCloud account, then we can go to the next step
                $this->next_step = $this->step_send['webdav'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['webdav'];
            }
        }

        if ($this->next_step == $this->step_send['webdav'] || $this->next_step == $this->step_send['webdav_resume']) {
            // Get all WebDAV accounts
            $webdav_accounts = Webdav::getListActiveWebdavAccounts();

            if (count($webdav_accounts)) {
                foreach ($webdav_accounts as $webdav_account) {
                    if (!$this->webdav_account_id) {
                        // If we have no account id save we save the current WebDAV account id
                        $this->webdav_account_id = $webdav_account['id_ntbr_webdav'];
                    } elseif ($this->webdav_account_id != $webdav_account['id_ntbr_webdav']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to WebDAV account
                    if ($this->next_step == $this->step_send['webdav']) {
                        $this->log($this->l('Sending backup to WebDAV account').' '.$webdav_account['name'].'...');
                    }

                    if (!$this->sendFileToWebdav()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to WebDAV account').' '.$webdav_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->webdav_account_id = 0;
                    // Next step is to send to a new WebDAV (if there is still one)
                    $this->next_step = $this->step_send['webdav'];
                }

                // If we send to all WebDAV account, then we can go to the next step
                $this->next_step = $this->step_send['googledrive'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['googledrive'];
            }
        }

        if ($this->next_step == $this->step_send['googledrive'] || $this->next_step == $this->step_send['googledrive_resume']) {
            // Get all Google Drive accounts
            $googledrive_accounts = Googledrive::getListActiveGoogledriveAccounts();

            if (count($googledrive_accounts)) {
                foreach ($googledrive_accounts as $googledrive_account) {
                    if (!$this->googledrive_account_id) {
                        // If we have no account id save we save the current Google Drive account id
                        $this->googledrive_account_id = $googledrive_account['id_ntbr_googledrive'];
                    } elseif ($this->googledrive_account_id != $googledrive_account['id_ntbr_googledrive']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to Google Drive account
                    if ($this->next_step == $this->step_send['googledrive']) {
                        $this->log($this->l('Sending backup to Google Drive account').' '.$googledrive_account['name'].'...');
                    }

                    if (!$this->sendFileToGoogledrive()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to Google Drive account').' '.$googledrive_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->googledrive_account_id = 0;
                    // Next step is to send to a new Google Drive (if there is still one)
                    $this->next_step = $this->step_send['googledrive'];
                }

                // If we send to all Google Drive account, then we can go to the next step
                $this->next_step = $this->step_send['onedrive'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['onedrive'];
            }
        }

        if ($this->next_step == $this->step_send['onedrive'] || $this->next_step == $this->step_send['onedrive_resume']) {
            // Get all OneDrive accounts
            $onedrive_accounts = Onedrive::getListActiveOnedriveAccounts();

            if (count($onedrive_accounts)) {
                foreach ($onedrive_accounts as $onedrive_account) {
                    if (!$this->onedrive_account_id) {
                        // If we have no account id save we save the current OneDrive account id
                        $this->onedrive_account_id = $onedrive_account['id_ntbr_onedrive'];
                    } elseif ($this->onedrive_account_id != $onedrive_account['id_ntbr_onedrive']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to OneDrive account
                    if ($this->next_step == $this->step_send['onedrive']) {
                        $this->log($this->l('Sending backup to OneDrive account').' '.$onedrive_account['name'].'...');
                    }

                    if (!$this->sendFileToOnedrive()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to OneDrive account').' '.$onedrive_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->onedrive_account_id = 0;
                    // Next step is to send to a new OneDrive (if there is still one)
                    $this->next_step = $this->step_send['onedrive'];
                }

                // If we send to all OneDrive account, then we can go to the next step
                $this->next_step = $this->step_send['hubic'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['hubic'];
            }
        }

        if ($this->next_step == $this->step_send['hubic'] || $this->next_step == $this->step_send['hubic_resume']) {
            // Get all Hubic accounts
            $hubic_accounts = Hubic::getListActiveHubicAccounts();

            if (count($hubic_accounts)) {
                foreach ($hubic_accounts as $hubic_account) {
                    if (!$this->hubic_account_id) {
                        // If we have no account id save we save the current Hubic account id
                        $this->hubic_account_id = $hubic_account['id_ntbr_hubic'];
                    } elseif ($this->hubic_account_id != $hubic_account['id_ntbr_hubic']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to Hubic account
                    if ($this->next_step == $this->step_send['hubic']) {
                        $this->log($this->l('Sending backup to Hubic account').' '.$hubic_account['name'].'...');
                    }

                    if (!$this->sendFileToHubic()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to Hubic account').' '.$hubic_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->hubic_account_id = 0;
                    // Next step is to send to a new Hubic (if there is still one)
                    $this->next_step = $this->step_send['hubic'];
                }

                // If we send to all hubic account, then we can go to the next step
                $this->next_step = $this->step_send['aws'];

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = $this->step_send['aws'];
            }
        }

        if ($this->next_step == $this->step_send['aws'] || $this->next_step == $this->step_send['aws_resume']) {
            // Get all AWS accounts
            $aws_accounts = Aws::getListActiveAwsAccounts();

            if (count($aws_accounts)) {
                foreach ($aws_accounts as $aws_account) {
                    if (!$this->aws_account_id) {
                        // If we have no account id save we save the current AWS account id
                        $this->aws_account_id = $aws_account['id_ntbr_aws'];
                    } elseif ($this->aws_account_id != $aws_account['id_ntbr_aws']) {
                        // If we have an id save we need to find the right account to pursue the sending to that account
                        continue;
                    }

                    //Send backup to AWS account
                    if ($this->next_step == $this->step_send['aws']) {
                        $this->log($this->l('Sending backup to AWS account').' '.$aws_account['name'].'...');
                    }

                    if (!$this->sendFileToAws()) {
                        $this->log('WAR'.$this->l('Unable to send backup file to AWS account').' '.$aws_account['name']);
                    }

                    // We reset the id so that we will save the id in the next iteration of the loop
                    $this->aws_account_id = 0;
                    // Next step is to send to a new AWS (if there is still one)
                    $this->next_step = $this->step_send['aws'];
                }

                // If we send to all AWS account, then we can go to the next step
                $this->next_step = self::STEP_FINISH;

                //refresh
                $this->refreshBackup();
            } else {
                $this->next_step = self::STEP_FINISH;
            }
        }
    }
}
