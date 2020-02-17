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
 *
 * CHANGELOG
 *
 * Version 9.1.1 :
 *      - Provide default names for send away accounts
 *      - Ignore theme cache folder
 * Version 9.1.0 :
 *      - Fix rare multiple simultaneous renewals
 * Version 9.0.5 :
 *      - Ignore files with extension that start with .nfs
 * Version 9.0.4 :
 *      - Add option to ignore files equal or larger than max size
 * Version 9.0.3 :
 *      - Add option to pause between intermediate renewal. Help with limited server
 * Version 9.0.2 :
 *      - Fix issue with tables to ignore
 *      - Add intermediate renewal in the dump of database values
 * Version 9.0.1 :
 *      - Add option to ignore unwanted tables
 *      - Add option to choose time between progress refresh
 *      - Add option to choose new memory limit if attempt to increase it is enable
 * Version 9.0.0 :
 *      - Simple automation is now based on user timezone and not CET
 *      - Possibility to add a comment to backup
 *      - Possibility to choose the number of backup by type (complete, just database or just files)
 *      - Possibility to send backup to Hubic, Amazon AWS S3 and WebDAV
 *      - UI improvements for send options
 *      - Add restoration menu into the module, restoration script is still available
 *      - Possibility to send backup again by clicking a button
 *      - Possibility to send backup to more than one same type server
 *      - Sending backup is now using intermediate renewals
 *      - Update phpseclib to 2.0.10
 *      - Gz compression is now preferred over bz
 * Version 8.0.7 :
 *      - Check ftp connection aliveness before sending restoration script
 *      - Fix ftp error detection
 *      - Fix corrupt tar in a particular case
 * Version 8.0.6 :
 *      - Fix security bug
 * Version 8.0.5 :
 *      - Display warning if an empty directory has not enough rights
 * Version 8.0.4 :
 *      - Fix writing multipart backup
 * Version 8.0.3 :
 *      - Fix for Google G Suite
 * Version 8.0.2 :
 *      - Fix upgrade from previous version
 * Version 8.0.1 :
 *      - Display a warning if PHP < 5.6 detected for security functions
 *      - Change the way backups dates are found
 *      - Fix intermediate renewal for database only backup
 * Version 8.0.0 :
 *      - Display last version available if an update is available
 *      - Update PHPSecLib to 2.0
 *      - Better way to get external server IP
 *      - Fix bz compression
 *      - Disable bz compression when intermediate renewal is active
 *      - Random cron time between 2h and 5h at install
 *      - Add option to send the restoration script with backup on remote location
 *      - Add option SSL FTP connection
 *      - Add option Passive FTP connection
 *      - Fix possible issues while login on some ftp servers
 *      - Check files and directories rights and update them if needed
 *      - Replace mcrypt (deprecated in PHP 7.1) by openssl
 *      - Ignore smarty compile cache
 *      - Add 2N IPv6 to maintenance allowed IP
 *      - Check the server IPs are in the maintenance IP list
 * Version 7.1.1 :
 *      - Add option to received email only if there was an error or warning
 *      - Do not save smarty cache
 * Version 7.1.0 :
 *      - Add security option to manually stop running backup
 *      - Add option to display progress of a running backup (useful to see an automated backup progress)
 *      - Add option to customize security duration between backups
 *      - Improve big files (> 2GB) handling
 *      - Fix progress return for some servers
 *      - Fix compress multipart backup regression
 * Version 7.0.0 :
 *      - Improve advanced option Intermediate Renewal to works in cron mode
 *      - Intermediate Renewal is now enabled by default
 *      - Disk usage optimisation. The module now uses much less I/O
 *      - Memory optimisation. The module now needs less than 96 MB RAM in most cases
 *      - In advanced cron, display usual examples of self automation usage : URL, WGet, cURL, PHP Script
 *      - Add Simplified Chinese internationalization thanks to Bai Shijun
 * Version 6.1.4 :
 *      - Add Traditional Chinese internationalization thanks to Bryant Kang
 * Version 6.1.3 :
 *      - Optimize listing file progress log
 * Version 6.1.2 :
 *      - Do not backup previous restore script log files
 * Version 6.1.1 :
 *      - Fix configuration values encoding
 *      - Prevent problem with SFTP ending connection
 *      - Fix part list gz compression
 * Version 6.1.0 :
 *      - Various bugs fixing
 * Version 6.0.4 :
 *      - Change listing files
 * Version 6.0.3 :
 *      - Prevent simultaneous backup
 *      - Change ip check method
 *      - Disable tab install on Prestashop 1.7.1
 * Version 6.0.2 :
 *      - Display a warning if the script was stopped because of a server timeout
 *      - Fix js cache problem on new version
 * Version 6.0.1 :
 *      - Disabling set_time_limit which can be forbidden on some servers
 *      - Use a variable array instead of a const array which is not available on very old php version
 * Version 6.0.0 :
 *      - Add an option to do intermediate renewal on manual backup. Useful on limited server with a small timeout.
 *      - Remove the count files option (useless with the intermediate renewal option)
 * Version 5.0.1 :
 *      - Change the way FTP/SFTP old files' are found
 *      - Change the way the mail language is chosen
 * Version 5.0.0 :
 *      - Add option to split de backup in parts
 *      - Scroll to the top of the page after saving the configuration
 *      - Fix sometimes false local server detection
 * Version 4.1.5 :
 *      - Fix delete backup file option if compression disabled
 *      - Fix distant drive upload if compression disabled
 * Version 4.1.4 :
 *      - Simple automation time is CET time
 *      - Add a warning when mcrypt is not enabled
 *      - Display some errors as warning to not stop the backup
 * Version 4.1.3 :
 *      - Fix mysql blob dump
 * Version 4.1.2 :
 *      - Add a warning when the config domain on the shop is not the one used
 * Version 4.1.1 :
 *      - Fix issue with Google Drive
 * Version 4.1.0 :
 *      - Add option to ignore unwanted directories
 *      - Add option to ignore unwanted types of file
 *      - Add option to choose how many backup to keep for each location
 *      - When backup is sent elsewhere, the number of local backup is not forced to 1 anymore
 *      - Add option to delete the local file when the backup is sent elsewhere
 *      - Add information about simple automation
 *      - Update Google Drive API
 * Version 4.0.5 :
 *      - Add percentage count display for SFTP and FTP
 *      - Change chunk size sent to Dropbox and OneDrive for better percentage count display
 *      - Fix issue with large SFTP files
 * Version 4.0.4 :
 *      - Fix special names for dump of mysql procedures and functions
 *      - Fix issue with OneDrive
 *      - Fix issue with SFTP
 * Version 4.0.3 :
 *      - Fix setting automation return value
 * Version 4.0.2 :
 *      - Fix missing last character in 100 bytes length file name
 *      - Compatibility with Prestashop 1.7.0.0
 * Version 4.0.1 :
 *      - Fix getting file info on some system
 * Version 4.0.0 :
 *      - Add a "Request feature" button
 *      - Add an "Advanced" part in the config
 *      - Add an option to prevent counting files (small performance server optimisation)
 *      - Add an option to prevent compressing the files (small performance server optimisation)
 *      - Delete old tar files (if compression active)
 *      - Add an option to put the shop in maintenance while creating the backup
 *      - Fix empty folders not being recreated during restoration
 *      - Cron menu become the advanced option of the new Automation menu
 *      - Add an "Advanced" part in the automation
 *      - Compatibility with SFTP server
 *      - Add extra simple automation to run daily backup
 *      - Fix dump with reserved keyword tables name
 * Version 3.0.12 :
 *      - Download optimization
 *      - Ignore upload folder
 * Version 3.0.11 :
 *      - Compatibility with Prestashop 1.7.0.0-rc0
 *      - Update to Dropbox api v2
 *      - Fix issue with OneDrive listing children from the root folder
 *      - Fix issue with OneDrive refresh token
 *      - Fix issue with ownCloud deleting files
 *      - Fix issue with ownCloud when trying to list children from an empty folder
 *      - Css fix for Prestashop 1.5
 * Version 3.0.10 :
 *      - Fix issue with Google Drive backup
 *      - Clean the code field after it's been save, so it is not register twice
 * Version 3.0.9 :
 *      - Fix dump of mysql procedures and functions
 * Version 3.0.8 :
 *      - Log the error of Dropbox, OneDrive, ownCloud and Google Drive in the log file of the module
 * Version 3.0.7 :
 *      - Log an error if not enough space available during backup
 * Version 3.0.6 :
 *      - Do not backup temporary thumbnails
 * Version 3.0.5 :
 *      - Update Swedish mail
 * Version 3.0.4 :
 *      - Better CRON compatibility with no parameter in url
 * Version 3.0.3 :
 *      - Update Swedish and Danish translations
 * Version 3.0.2 :
 *      - Small fixes
 * Version 3.0.1 :
 *      - Swedish translation by Roy Sohlander
 * Version 3.0.0 :
 *      - Allow to send the backup to Dropbox, OneDrive, ownCloud and Google Drive
 * Version 2.1.0 :
 *      - Enable changing the email receiving the notification result of the backup
 *      - New way to call cron so the variable don't cause any issues
 * Version 2.0.2 :
 *      - Fix Undefined backup_exist
 *      - Danish translation by Nick Andersen (dinprestashop.dk)
 * Version 2.0.1 :
 *      - Fix generate secure URL not working
 * Version 2.0.0 :
 *      - Better UI
 *      - Enable keeping more than one backup file
 *      - Automatically remove old backups
 *      - Can automatically remove old backups on FTP server
 *      - Fix percentage count display for database dump
 *      - Fix ignore low interest table if not using default db prefix
 *      - Can backup only files
 *      - Can backup only database
 *      - Fix wrong file name length with long link in tar file
 *      - Add support of Cache Manager module
 *      - Add support of Page Cache module
  * Version 1.2.9 :
 *      - Ignore cachefs folder
 * Version 1.2.8 :
 *      - Maintain FTP connexion for slow sending
 * Version 1.2.7 :
 *      - Fix forein key order issue
 * Version 1.2.6 :
 *      - Fix missing stats tables
 * Version 1.2.5 :
 *      - Fix FTP dir
 * Version 1.2.4 :
 *      - Memory optimisation for big shop
 *      - Fix file discovering if bad rights
 * Version 1.2.3 :
 *      - Dump optimizations for big databases
 * Version 1.2.2 :
 *      - Fix dump values error
 * Version 1.2.1 :
 *      - Memory optimization during database dump
 *      - Smaller dump file
 * Version 1.2.0 :
 *      - Fix ajax path
 *      - Add option to send backup result by mail
 *      - Add option to send the backup on a FTP server
 *      - Rewrite tar and compress algorythms
 *      - Memory managment greatly improved
 *      - File access number greatly reduced
 * Version 1.1.0 :
 *      - Fix backup filesize for big file
 *      - Add option to not backup product images
 * Version 1.0.2 :
 *      - Ignore some files due to forbidden character in their name
 *      - Ignore some files due to bad rights or bad owner
 * Version 1.0.1 :
 *      - Exclude this module file if demo
 * Version 1.0.0 :
 *      - First version for prestashop 1.5 and 1.6
 *      - PHP 5.3 minimum
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/classes/Aws.php';
require_once dirname(__FILE__).'/classes/Comments.php';
require_once dirname(__FILE__).'/classes/Dropbox.php';
require_once dirname(__FILE__).'/classes/Ftp.php';
require_once dirname(__FILE__).'/classes/Googledrive.php';
require_once dirname(__FILE__).'/classes/Hubic.php';
require_once dirname(__FILE__).'/classes/Onedrive.php';
require_once dirname(__FILE__).'/classes/Owncloud.php';
require_once dirname(__FILE__).'/classes/Webdav.php';

require_once dirname(__FILE__).'/lib/aws/Aws.php';
require_once dirname(__FILE__).'/lib/dropbox/Dropbox.php';
require_once dirname(__FILE__).'/lib/googledrive/Googledrive.php';
require_once dirname(__FILE__).'/lib/hubic/Hubic.php';
require_once dirname(__FILE__).'/lib/onedrive/OneDrive.php';
require_once dirname(__FILE__).'/lib/openstack/Openstack.php';
require_once dirname(__FILE__).'/lib/owncloud/Owncloud.php';
require_once dirname(__FILE__).'/lib/webdav/Webdav.php';

class NtBackupAndRestore extends Module
{
    const TAB_2NT = 'NTModules';
    const NAME_TAB_2NT = 'NT Modules';
    const TAB_MODULE = 'AdminNtbackupandrestore';
    const NAME_TAB = 'NtBackupAndRestore';

    const INSTALL_SQL_FILE = 'sql/install.sql';
    const UNINSTALL_SQL_FILE = 'sql/uninstall.sql';

    public function __construct()
    {
        $this->name          = 'ntbackupandrestore';
        $this->tab           = 'administration';
        $this->version       = '9.1.1';
        $this->author        = '2N Technologies';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7');
        $this->module_key    = '652f1358e0ab9984c886ef5fceac8675';
        $this->secure_key    = Tools::encrypt($this->name);

        parent::__construct();

        $this->displayName = $this->l('2N Technologies Backup And Restore');
        $this->description = $this->l('Backup your prestashop site and easily restore it wherever you want');

        $this->tabs[] = array(
            'parent_class'  =>  self::TAB_2NT,
            'parent_name'   =>  self::NAME_TAB_2NT,
            'tab_class'     =>  self::TAB_MODULE,
            'tab_name'      =>  self::NAME_TAB,
        );

        if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
            $this->tabs[] = array(
                'parent_class'  =>  'AdminAdvancedParameters',
                'parent_name'   =>  self::NAME_TAB_2NT,
                'tab_class'     =>  self::TAB_MODULE.'Tab',
                'tab_name'      =>  self::NAME_TAB,
            );
        } else {
            $this->tabs[] = array(
                'parent_class'  =>  'AdminTools',
                'parent_name'   =>  self::NAME_TAB_2NT,
                'tab_class'     =>  self::TAB_MODULE.'Tab',
                'tab_name'      =>  self::NAME_TAB,
            );
        }
    }

    /**
     * Execute a SQL file
     *
     * @param   String  $file_path  The path of the SQL file
     *
     * @return  boolean             Success or failure of the operation
     */
    public function executeFile($file_path)
    {
        // Check if the file exists
        if (!file_exists($file_path)) {
            return Tools::displayError('Error : no sql file !');
        } elseif (!$sql = Tools::file_get_contents($file_path)) {// Get file content
            return Tools::displayError('Error : there is a problem with your install sql file !');
        }

        $sql_replace = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
        $sql = preg_split("/;\s*[\r\n]+/", trim($sql_replace));

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute(trim($query))) {
                return Tools::displayError('Error : this query doesn\'t work ! '.$query);
            }
        }

        return true;
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        require_once(dirname(__FILE__).'/classes/ntbr.php');

        //We initialize the configuration for all shops
        $shops = Shop::getShops();

        foreach ($shops as $shop) {
            $id_shop = $shop['id_shop'];
            $id_shop_group = $shop['id_shop_group'];

            if (!Configuration::updateValue('NB_KEEP_BACKUP', 1, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_IGNORE_DIRECTORIES', '', false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_IGNORE_FILES_TYPES', '', false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_IGNORE_TABLES', '', false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('DUMP_LOW_INTEREST_TABLES', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_DISABLE_REFRESH', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_DISABLE_SERVER_TIMEOUT', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_INCREASE_SERVER_MEMORY', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_SERVER_MEMORY_VALUE', NtbrCore::SET_MEMORY_LIMIT, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('ACTIVATE_LOG', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('SEND_EMAIL', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('EMAIL_ONLY_ERROR', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('IGNORE_PRODUCT_IMAGE', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('ACTIVATE_XSENDFILE', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('IGNORE_FILES_COUNT', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('IGNORE_COMPRESSION', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_MAINTENANCE', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_DELETE_LOCAL_BACKUP', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_PART_SIZE', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_MAX_FILE_TO_BACKUP', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_TIME_BETWEEN_BACKUPS', NtbrCore::MIN_TIME_NEW_BACKUP, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_TIME_BETWEEN_REFRESH', NtbrCore::MAX_TIME_BEFORE_REFRESH, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_TIME_PAUSE_BETWEEN_REFRESH', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_TIME_BETWEEN_PROGRESS_REFRESH', NtbrCore::MAX_TIME_BEFORE_PROGRESS_REFRESH, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_AUTOMATION_2NT_HOURS', mt_rand(2, 5), false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_AUTOMATION_2NT_MINUTES', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('MAIL_BACKUP', Configuration::get('PS_SHOP_EMAIL'), false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('SEND_RESTORE', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_ONGOING', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_ADMIN_DIR', '', false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_BIG_WEBSITE_HIDE', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NTBR_ENCRYPT_BACKUP', 0, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NB_KEEP_BACKUP_FILE', 1, false, $id_shop_group, $id_shop)) {
                return false;
            }
            if (!Configuration::updateValue('NB_KEEP_BACKUP_BASE', 1, false, $id_shop_group, $id_shop)) {
                return false;
            }
        }

        $install_on_tab = true;
        /* Install on tab */
        foreach ($this->tabs as $tab) {
            if (!$this->installOnTab($tab['tab_class'], $tab['tab_name'], $tab['parent_class'], $tab['parent_name'])) {
                $install_on_tab = false;
            }
        }

        if (!$install_on_tab) {
            return false;
        }

        // Create new data base table
        $this->executeFile(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE);

        /* Create file with all the varibles need for crons */
        $physic_path_modules = realpath(_PS_ROOT_DIR_.'/modules').'/';
        $shop_domain = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost();
        $url_modules = $shop_domain.__PS_BASE_URI__.'modules/';
        $url_ajax = $url_modules.$this->name.'/ajax';
        $physic_path_ajax = $physic_path_modules.$this->name.'/ajax';
        $param_secure_key = 'secure_key='.$this->secure_key;

        $redirect_cron = array(
            'backup',
            'backupfilesonly',
            'backupdatabaseonly'
        );

        foreach ($redirect_cron as $cron) {
            $file_path = $physic_path_ajax.'/'.$cron.'_'.$this->secure_key.'.php';
            if (!file_exists($file_path)) {
                $file = fopen($file_path, 'w+');
                fwrite($file, '<?php header("Location: '.$url_ajax.'/'.$cron.'.php?'.$param_secure_key.'"); exit();');
                fclose($file);

                if (chmod($file_path, octdec(NtbrCore::PERM_FILE)) !== true) {
                    $this->log(sprintf($this->l('The file "%s" permission cannot be updated to %d'), $file_path, NtbrCore::PERM_FILE));
                }
            }
        }

        return parent::install();
    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        /* Delete Back-office tab */
        foreach ($this->tabs as $tab) {
            $this->uninstallTab($tab['tab_class']);
        }

        /* Delete the database table */
        //$this->executeFile(dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE);

        return parent::uninstall();
    }

    public function uninstallTab($tab_class)
    {
        $img_tab_path = _PS_ROOT_DIR_.'/img/t/';
        $module_path = _PS_MODULE_DIR_.'/'.$this->name.'/';
        $id_tab = Tab::getIdFromClassName($tab_class);

        if ($id_tab) {
            $tab = new Tab((int)$id_tab);
            $id_parent = $tab->id_parent;
            $parent_tab = new Tab((int)$id_parent);

            if (file_exists($img_tab_path.$tab->class_name.'.gif')) {
                unlink($img_tab_path.$tab->class_name.'.gif');
            }

            $tab->delete();

            if (Tab::getNbTabs($id_parent) <= 0 && $parent_tab->class_name == self::TAB_2NT) {
                $tab_parent = new Tab((int)$id_parent);
                $img = $tab_parent->class_name.'.gif';

                if (file_exists($img_tab_path.$img)) {
                    unlink($img_tab_path.$img);
                }

                if (version_compare(_PS_VERSION_, '1.6', '<') && file_exists($module_path.$img)) {
                    unlink($module_path.$img);
                }

                $tab_parent->delete();
            }
        }
    }

    /**
    * Install the module in a tab
    *
    * @param string $tab_class Tab class
    * @param string $tab_name Tab name
    * @param string $tab_parent_class Tab parent's class
    * @param string $tab_parent_name Tab parent's name
    * @return bool
    */
    public function installOnTab($tab_class, $tab_name, $tab_parent_class, $tab_parent_name = '')
    {
        $img_tab_path = _PS_ROOT_DIR_.'/img/t/';
        $module_path = _PS_MODULE_DIR_.'/'.$this->name.'/';

        if (version_compare(_PS_VERSION_, '1.6', '>')) {
            $logo_path = _PS_MODULE_DIR_.'/'.$this->name.'/views/img/tab_logo_grey.png';
        } else {
            $logo_path = _PS_MODULE_DIR_.'/'.$this->name.'/views/img/tab_logo_color.png';
        }

        $id_tab_parent = Tab::getIdFromClassName($tab_parent_class);

        /* If the parent tab does not exist yet, create it */
        if (!$id_tab_parent) {
            $tab_parent = new Tab();
            $tab_parent->class_name = $tab_parent_class;
            $tab_parent->module = $this->name;
            $tab_parent->id_parent = 0;

            foreach (Language::getLanguages(false) as $lang) {
                $tab_parent->name[(int)$lang['id_lang']] = $tab_parent_name;
            }

            if (!$tab_parent->save()) {
                $this->_errors[] = (sprintf($this->l('Unable to create the "%s" tab'), $tab_parent_class));
                return false;
            }

            $id_tab_parent = $tab_parent->id;

            if (!file_exists($img_tab_path.$tab_parent_class.'.gif')) {
                if (!Tools::copy($logo_path, $img_tab_path.$tab_parent_class.'.gif')) {
                    $this->_errors[] = (sprintf($this->l('Unable to copy logo.gif in %s'), $img_tab_path));
                    return false;
                }
            }

            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                if (!file_exists($module_path.$tab_parent_class.'.gif')) {
                    if (!Tools::copy($logo_path, $module_path.$tab_parent_class.'.gif')) {
                        $this->_errors[] = (sprintf($this->l('Unable to copy logo.gif in %s'), $module_path));
                        return false;
                    }
                }
            }
        }

        /* If the tab does not exist yet, create it */
        if (!Tab::getIdFromClassName($tab_class)) {
            $tab = new Tab();
            $tab->class_name = $tab_class;
            $tab->module = $this->name;
            $tab->id_parent = (int)$id_tab_parent;

            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int)$lang['id_lang']] = $tab_name;
            }

            if (!$tab->save()) {
                $this->_errors[] = (sprintf($this->l('Unable to create the "%s" tab'), $tab_class));
                return false;
            }

            if (file_exists($logo_path)) {
                if (!file_exists($img_tab_path.$tab_class.'.gif')) {
                    if (!Tools::copy($logo_path, $img_tab_path.$tab_class.'.gif')) {
                        $this->_errors[] = (sprintf($this->l('Unable to copy logo.gif in %s'), $img_tab_path));
                        return false;
                    }
                }

                if (version_compare(_PS_VERSION_, '1.6', '<')) {
                    if (!file_exists($module_path.$tab_class.'.gif')) {
                        if (!Tools::copy($logo_path, $module_path.$tab_class.'.gif')) {
                            $this->_errors[] = (sprintf($this->l('Unable to copy logo.gif in %s'), $module_path));
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminNtbackupandrestore'));
    }
}
