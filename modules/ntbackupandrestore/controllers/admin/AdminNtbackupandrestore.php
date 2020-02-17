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

if (file_exists(_PS_ROOT_DIR_.'/modules/ntbackupandrestore/classes/ntbrfull.php')) {
    require_once(dirname(__FILE__).'/../../classes/ntbrfull.php');
} elseif (file_exists(_PS_ROOT_DIR_.'/modules/ntbackupandrestore/classes/ntbrlight.php')) {
    require_once(dirname(__FILE__).'/../../classes/ntbrlight.php');
} else {
    die('Missing override');
}

class AdminNtbackupandrestoreController extends ModuleAdminController
{
    private $id_shop;
    private $id_shop_group;

    public function __construct()
    {
        $this->display = 'view';
        $this->bootstrap = true;
        $this->multishop_context = Shop::CONTEXT_ALL;
        $this->context = Context::getContext();

        parent::__construct();

        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $this->meta_title = array($this->l('2NT Backup and Restore'));
        } else {
            $this->meta_title = $this->l('2NT Backup and Restore');
        }

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $ntbr = new NtbrChild();
        $module_path = $this->module->getPathUri();

        $version_script = '1.5';
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $version_script = '1.6';
        }

        $this->addCSS(array(
            $module_path.'views/css/style_'.$version_script.'.css',
        ));

        $this->addCSS(array(
            $module_path.'views/css/style.css',
        ));

        $this->addCSS(array(
            $module_path.'views/css/fontawesome-all.css',
        ));

        $this->addJS(array(
            $module_path.'views/js/script_'.$version_script.'.js',
        ));

        $this->addJS(array(
            $module_path.'views/js/script.'.$ntbr->version.'.js',
        ));

        return true;
    }

    public function renderView()
    {
        $ntbr                   = new NtbrChild();
        $type_module            = $ntbr->getTypeModule();
        $light                  = ($type_module=='')?0:1;
        $context                = Context::getContext();
        $shop                   = $context->shop;
        $physic_path_modules    = realpath(_PS_ROOT_DIR_.'/modules').'/';
        $fct_crypt_exists       = true;
        $os_windows             = false;
        $curl_exists            = true;
        $param_secure_key       = 'secure_key='.$ntbr->secure_key;

        if (Tools::isSubmit('display_ftp_account')) {
            $this->displayFtpAccount();
        } elseif (Tools::isSubmit('display_dropbox_account')) {
            $this->displayDropboxAccount();
        } elseif (Tools::isSubmit('display_owncloud_account')) {
            $this->displayOwncloudAccount();
        } elseif (Tools::isSubmit('display_webdav_account')) {
            $this->displayWebdavAccount();
        } elseif (Tools::isSubmit('display_googledrive_account')) {
            $this->displayGoogledriveAccount();
        } elseif (Tools::isSubmit('display_onedrive_account')) {
            $this->displayOnedriveAccount();
        } elseif (Tools::isSubmit('display_hubic_account')) {
            $this->displayHubicAccount();
        } elseif (Tools::isSubmit('display_aws_account')) {
            $this->displayAwsAccount();
        } elseif (Tools::isSubmit('save_ftp')) {
            $this->saveFtp();
        } elseif (Tools::isSubmit('save_dropbox')) {
            $this->saveDropbox();
        } elseif (Tools::isSubmit('save_owncloud')) {
            $this->saveOwncloud();
        } elseif (Tools::isSubmit('save_webdav')) {
            $this->saveWebdav();
        } elseif (Tools::isSubmit('save_googledrive')) {
            $this->saveGoogledrive();
        } elseif (Tools::isSubmit('save_onedrive')) {
            $this->saveOnedrive();
        } elseif (Tools::isSubmit('save_hubic')) {
            $this->saveHubic();
        } elseif (Tools::isSubmit('save_aws')) {
            $this->saveAws();
        } elseif (Tools::isSubmit('check_connection_ftp')) {
            $this->checkConnectionFtp();
        } elseif (Tools::isSubmit('check_connection_dropbox')) {
            $this->checkConnectionDropbox();
        } elseif (Tools::isSubmit('check_connection_owncloud')) {
            $this->checkConnectionOwncloud();
        } elseif (Tools::isSubmit('check_connection_webdav')) {
            $this->checkConnectionWebdav();
        } elseif (Tools::isSubmit('check_connection_googledrive')) {
            $this->checkConnectionGoogledrive();
        } elseif (Tools::isSubmit('check_connection_onedrive')) {
            $this->checkConnectionOnedrive();
        } elseif (Tools::isSubmit('check_connection_hubic')) {
            $this->checkConnectionHubic();
        } elseif (Tools::isSubmit('check_connection_aws')) {
            $this->checkConnectionAws();
        } elseif (Tools::isSubmit('delete_ftp')) {
            $this->deleteFtp();
        } elseif (Tools::isSubmit('delete_dropbox')) {
            $this->deleteDropbox();
        } elseif (Tools::isSubmit('delete_owncloud')) {
            $this->deleteOwncloud();
        } elseif (Tools::isSubmit('delete_webdav')) {
            $this->deleteWebdav();
        } elseif (Tools::isSubmit('delete_googledrive')) {
            $this->deleteGoogledrive();
        } elseif (Tools::isSubmit('delete_onedrive')) {
            $this->deleteOnedrive();
        } elseif (Tools::isSubmit('delete_hubic')) {
            $this->deleteHubic();
        } elseif (Tools::isSubmit('delete_aws')) {
            $this->deleteAws();
        } elseif (Tools::isSubmit('display_googledrive_tree')) {
            $this->displayGoogledriveTree();
        } elseif (Tools::isSubmit('display_googledrive_tree_child')) {
            $this->displayGoogledriveTreeChild();
        } elseif (Tools::isSubmit('display_onedrive_tree')) {
            $this->displayOnedriveTree();
        } elseif (Tools::isSubmit('display_onedrive_tree_child')) {
            $this->displayOnedriveTreeChild();
        } elseif (Tools::isSubmit('display_aws_tree')) {
            $this->displayAwsTree();
        } elseif (Tools::isSubmit('display_aws_tree_child')) {
            $this->displayAwsTreeChild();
        } elseif (Tools::isSubmit('send_backup')) {
            $this->sendBackupAway();
        } elseif (Tools::isSubmit('add_comment_backup')) {
            $this->addCommentBackup();
        } elseif (Tools::isSubmit('restore_backup')) {
            $this->restoreBackup();
        }

        if (Tools::isSubmit('hide_big_site')) {
            $ntbr->setConfig('NTBR_BIG_WEBSITE_HIDE', 1);
        }

        $http_context = stream_context_create(
            array('http'=>
                array(
                    'timeout' => 1,
                )
            )
        );

        $available_version = Tools::file_get_contents(NtbrCore::URL_VERSION, false, $http_context, 1);

        //Add IP for maintenance mode
        $ntbr->setMaintenanceIP();
        $domain_use = Tools::getHttpHost();
        $protocol = Tools::getCurrentUrlProtocolPrefix();
        $shop_domain = $protocol.$domain_use;
        $base_uri = $shop->getBaseURI();
        if ($base_uri == '/') {
            $base_uri = '';
        }
        $module_controller_link = $context->link->getAdminLink('AdminNtbackupandrestore');

        if (Configuration::get('PS_SSL_ENABLED')) {
            $domain_config = ShopUrl::getMainShopDomainSSL();
        } else {
            $domain_config = ShopUrl::getMainShopDomain();
        }

        $current_address = $_SERVER['PHP_SELF'];
        $admin_directory = str_replace($base_uri, '', str_replace('index.php', '', $current_address));
        $ntbr->setConfig('NTBR_ADMIN_DIR', str_replace('/', '', $admin_directory), $shop->id_shop_group, $shop->id);
        $module_address_use = $protocol.$domain_use.$base_uri.$admin_directory.$module_controller_link;
        $module_address_config = $protocol.$domain_config.$base_uri.$admin_directory.$module_controller_link;

        $url_modules = $shop_domain.__PS_BASE_URI__.'modules/';
        $url_ajax = $url_modules.$ntbr->name.'/ajax';
        $physic_path_ajax = $physic_path_modules.$ntbr->name.'/ajax';
        $documentation = $url_modules.$ntbr->name.'/readme_en.pdf';
        $ajax_loader = $url_modules.$ntbr->name.'/views/img/ajax-loader.gif';
        $documentation_name = 'readme_en.pdf';
        $this->id_shop = (int)Configuration::get('PS_SHOP_DEFAULT');
        $this->id_shop_group = Shop::getGroupFromShop($this->id_shop);

        clearstatcache();
        $list_module_content = $ntbr->listDirectoryContent($physic_path_modules.$ntbr->name);

        foreach ($list_module_content as $file) {
            if ($file['perm'] != NtbrCore::PERM_FILE || $file['perm'] != NtbrCore::PERM_DIR) {
                if (is_dir($file['path'])) {
                    if (chmod($file['path'], octdec(NtbrCore::PERM_DIR)) !== true) {
                        $msg = sprintf($ntbr->l('The directory "%s" permission cannot be updated to %d'), $file['path'], NtbrCore::PERM_DIR);
                        $ntbr->log($msg);
                        $ntbr->errors[] = $msg;
                    }
                } else {
                    if (chmod($file['path'], octdec(NtbrCore::PERM_FILE)) !== true) {
                        $msg = sprintf($ntbr->l('The file "%s" permission cannot be updated to %d'), $file['path'], NtbrCore::PERM_FILE);
                        $ntbr->log($msg);
                        $ntbr->errors[] = $msg;
                    }
                }
            }
        }
//p($ntbr->errors);
//d($list_module_content);

        if (stripos(PHP_OS, 'win') !== false) {
            $os_windows = true;
        }

        if (!extension_loaded('openssl') || !function_exists('hash_equals')) {
            $fct_crypt_exists = false;
            Owncloud::deactiveAllOwncloud();
            Webdav::deactiveAllWebdav();
            FTP::deactiveAllSftp();
        }

        if (!extension_loaded('curl')) {
            $curl_exists = false;
        }

        if ($os_windows || !$fct_crypt_exists) {
            // for ftp_ssl_connect() to be available on Windows you must compile your own PHP binaries
            Ftp::removeSSL();
        }

        /*****************************************/
        // FTP
        $ftp_port_default           = '21';
        $ftp_directory_default      = '/';
        $ftp_accounts               = Ftp::getListFtpAccounts();
        $ftp_default                = Ftp::getDefaultValues();
        $ftp_default['nb_account']  = (count($ftp_accounts)+1);

        foreach ($ftp_accounts as &$ftp_account) {
            $ftp_account['password_decrypt'] = $ntbr->decrypt($ftp_account['password']);
        }

        // Dropbox
        $dropbox_accounts               = Dropbox::getListDropboxAccounts();
        $dropbox_default                = Dropbox::getDefaultValues();
        $dropbox_default['nb_account']  = (count($dropbox_accounts)+1);

        if ($light) {
            $dropbox_authorizeUrl   = '';
        } else {
            $dropbox                = $ntbr->connectToDropbox();
            $dropbox_authorizeUrl   = $dropbox->getLogInUrl();
        }

        // OneDrive
        $onedrive_accounts              = Onedrive::getListOnedriveAccounts();
        $onedrive_default               = Onedrive::getDefaultValues();
        $onedrive_default['nb_account'] = (count($onedrive_accounts)+1);

        if ($light) {
            $onedrive_authorizeUrl  = '';
        } else {
            $onedrive               = $ntbr->connectToOnedrive();
            $onedrive_authorizeUrl  = $ntbr->getOnedriveAccessTokenUrl($onedrive);
        }

        // Google Drive
        $googledrive_accounts               = Googledrive::getListGoogledriveAccounts();
        $googledrive_default                = Googledrive::getDefaultValues();
        $googledrive_default['nb_account']  = (count($googledrive_accounts)+1);

        if ($light) {
            $googledrive_authorizeUrl   = '';
        } else {
            $googledrive                = $ntbr->connectToGoogledrive();
            $googledrive_authorizeUrl   = $googledrive->getLogInUrl();
        }

        // ownCloud
        $owncloud_accounts              = Owncloud::getListOwncloudAccounts();
        $owncloud_default               = Owncloud::getDefaultValues();
        $owncloud_default['nb_account'] = (count($owncloud_accounts)+1);
        $update_owncloud_pass           = 1;

        // If something is register in ownCloud
        if (count($owncloud_accounts)) {
            foreach ($owncloud_accounts as &$owncloud_account) {
                $owncloud_account['password_decrypt'] = $ntbr->decrypt($owncloud_account['password']);
            }
        }

        // WebDAV
        $webdav_accounts                = Webdav::getListWebdavAccounts();
        $webdav_default                 = Webdav::getDefaultValues();
        $webdav_default['nb_account']   = (count($webdav_accounts)+1);

        // If something is register in WebDAV
        if (count($webdav_accounts)) {
            foreach ($webdav_accounts as &$webdav_account) {
                $webdav_account['password_decrypt'] = $ntbr->decrypt($webdav_account['password']);
            }
        }

        // Hubic
        $hubic_accounts                 = Hubic::getListHubicAccounts();
        $hubic_default                  = Hubic::getDefaultValues();
        $hubic_default['nb_account']    = (count($hubic_accounts)+1);

        if ($light) {
            $hubic_authorizeUrl = '';
        } else {
            $hubic              = $ntbr->connectToHubic();
            $hubic_authorizeUrl = $hubic->getLogInUrl();
        }

        // AWS
        $aws_accounts               = Aws::getListAwsAccounts();
        $aws_default                = Aws::getDefaultValues();
        $aws_default['nb_account']  = (count($aws_accounts)+1);

        /*****************************************/

        if (Tools::file_exists_cache(
            $physic_path_modules.$ntbr->name.'/readme_'.$this->context->language->iso_code.'.pdf'
        )
        ) {
            $documentation = $url_modules.$ntbr->name.'/readme_'.$this->context->language->iso_code.'.pdf';
            $documentation_name = 'readme_'.$this->context->language->iso_code.'.pdf';
        }

        $display_translate_tab = true;
        $translate_lng = array();

        $translate_files = glob($physic_path_modules.$ntbr->name.'/translations/*.php');

        foreach ($translate_files as $trslt_file) {
            $translate_lng[] = basename($trslt_file, '.php');
        }

        if (in_array($this->context->language->iso_code, $translate_lng)) {
            $display_translate_tab = false;
        }

        $backup_files = $ntbr->findOldBackups();
        $restore_backup_files_complete = array();
        $restore_backup_files_file = array();
        $restore_backup_files_base = array();

        foreach ($backup_files as $b_file) {
            if (strpos($b_file['name'], $ntbr->type_backup_complete) !== false) {
                $restore_backup_files_complete[] = $b_file;
            } elseif (strpos($b_file['name'], $ntbr->type_backup_file) !== false) {
                $restore_backup_files_file[] = $b_file;
            } elseif (strpos($b_file['name'], $ntbr->type_backup_base) !== false) {
                $restore_backup_files_base[] = $b_file;
            }
        }

        $download_files_links = $ntbr->generateUrls(true);

        $redirect_cron = array(
            'backup',
            'backupfilesonly',
            'backupdatabaseonly'
        );

        foreach ($redirect_cron as $cron) {
            if (!file_exists($physic_path_ajax.'/'.$cron.'_'.$ntbr->secure_key.'.php')) {
                $file = fopen($physic_path_ajax.'/'.$cron.'_'.$ntbr->secure_key.'.php', 'w+');
                fwrite($file, '<?php header("Location: '.$url_ajax.'/'.$cron.'.php?'.$param_secure_key.'"); exit();');
                fclose($file);
            }
        }

        if ($this->context->language->iso_code == 'fr') {
            $link_contact = 'https://addons.prestashop.com/fr/ecrire-au-developpeur?id_product=20130';
            $link_full_version = 'https://addons.prestashop.com/fr/migration-donnees-sauvegarde/20130-nt-sauvegarde-et-restaure.html';
        } else {
            $link_contact = 'https://addons.prestashop.com/en/write-to-developper?id_product=20130';
            $link_full_version = 'https://addons.prestashop.com/en/data-migration-backup/20130-nt-backup-and-restore.html';
        }

        $activate_2nt_automation = true;

        $list_comments = Comments::getListBackupComment();

        $ip = $domain_use;
        // If the domain is not an IP, find the IP of the domain
        if (!(filter_var($domain_use, FILTER_VALIDATE_IP))) {
            //$ip = gethostbyname($domain_use);

            if (strpos($ip, 'localhost') === false) {
                $ip = filter_var(Tools::file_get_contents(NtbrCore::URL_SERVICE_IP_EXTERNE), FILTER_VALIDATE_IP);
                if ($ip === false) {
                    $ip = false;
                }
            } else {
                $ip = false;
            }
        }

        // The IP of the server running the script
        //$ip = $_SERVER['SERVER_ADDR'];

        $special_ip_range = array(
            '0.0.0.0/8',
            '10.0.0.0/8',
            '100.64.0.0/10',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '172.16.0.0/12',
            '192.0.0.0/24',
            '192.0.2.0/24',
            '192.88.99.0/24',
            '192.168.0.0/16',
            '198.18.0.0/15',
            '198.51.100.0/24',
            '203.0.113.0/24',
            '224.0.0.0/4',
            '240.0.0.0/4',
            '255.255.255.255/32',
            '::/128',
            '::1/128',
            '::ffff:0:0/96',
            '0100::/64',
            '2000::/3',
            '2001::/32',
            '2001:2::/48',
            '2001:10::/28',
            '2001:db8::/32',
            '2002::/16',
            'fc00::/7',
            'fe80::/10',
            'ff00::/8',
        );

        if ($ip) {
            foreach ($special_ip_range as $range) {
                $is_ip_in_range = NtbrCore::ipInRange($ip, $range);
                if ($is_ip_in_range !== false) {
                    $activate_2nt_automation = false;
                    break;
                }
            }
        } else {
            $activate_2nt_automation = false;
        }

        $activate_log = $ntbr->getConfig('ACTIVATE_LOG');

        $big_website = 0;

        if (!$ntbr->getConfig('NTBR_BIG_WEBSITE_HIDE')) {
            $big_website = (int)$ntbr->getConfig('NTBR_BIG_WEBSITE');
        }

        $this->tpl_view_vars = array(
            'light'                             => $light,
            'link_full_version'                 => $link_full_version,
            'create_backup_ajax'                => $url_ajax.'/backup_ajax.php?'.$param_secure_key,
            'refresh_backup_ajax'               => $url_ajax.'/refresh_backup_ajax.php?'.$param_secure_key,
            'create_backup_cron'                => $url_ajax.'/backup_'.$ntbr->secure_key.'.php',
            'create_fileonly_backup'            => $url_ajax.'/backupfilesonly_'.$ntbr->secure_key.'.php',
            'create_databaseonly_backup'        => $url_ajax.'/backupdatabaseonly_'.$ntbr->secure_key.'.php',
            'delete_backup'                     => $url_ajax.'/delete.php?'.$param_secure_key,
            'save_config'                       => $url_ajax.'/save_config.php?'.$param_secure_key,
            'save_automation'                   => $url_ajax.'/save_automation.php?'.$param_secure_key,
            'backup_progress'                   => $url_ajax.'/backup_progress.php?'.$param_secure_key,
            'generate_urls'                     => $url_ajax.'/generate_urls.php?'.$param_secure_key,
            'stop_backup'                       => $url_ajax.'/stop_backup.php?'.$param_secure_key,
            'link_restore_file'                 => $shop_domain.__PS_BASE_URI__.NtbrCore::NEW_RESTORE_NAME,
            'restore_lastlog'                   => $shop_domain.__PS_BASE_URI__.'lastlog.txt',
            'download_file'                     => $download_files_links['link'],
            'backup_files'                      => $backup_files,
            'documentation'                     => $documentation,
            'documentation_name'                => $documentation_name,
            'display_translate_tab'             => $display_translate_tab,
            'nb_keep_backup'                    => (int)$ntbr->getConfig('NB_KEEP_BACKUP'),
            'nb_keep_backup_file'               => (int)$ntbr->getConfig('NB_KEEP_BACKUP_FILE'),
            'nb_keep_backup_base'               => (int)$ntbr->getConfig('NB_KEEP_BACKUP_BASE'),
            'ignore_directories'                => $ntbr->getConfig('NTBR_IGNORE_DIRECTORIES'),
            'ignore_files_types'                => $ntbr->getConfig('NTBR_IGNORE_FILES_TYPES'),
            'ignore_tables'                     => $ntbr->getConfig('NTBR_IGNORE_TABLES'),
            'mail_backup'                       => $ntbr->getConfig('MAIL_BACKUP'),
            'activate_log'                      => $activate_log,
            'dump_low_interest_table'           => $ntbr->getConfig('DUMP_LOW_INTEREST_TABLES'),
            'disable_refresh'                   => $ntbr->getConfig('NTBR_DISABLE_REFRESH'),
            'disable_server_timeout'            => $ntbr->getConfig('NTBR_DISABLE_SERVER_TIMEOUT'),
            'increase_server_memory'            => $ntbr->getConfig('NTBR_INCREASE_SERVER_MEMORY'),
            'increase_server_memory_value'      => $ntbr->getConfig('NTBR_SERVER_MEMORY_VALUE'),
            'activate_xsendfile'                => $ntbr->getConfig('ACTIVATE_XSENDFILE'),
            'send_email'                        => $ntbr->getConfig('SEND_EMAIL'),
            'email_only_error'                  => $ntbr->getConfig('EMAIL_ONLY_ERROR'),
            'ignore_product_image'              => $ntbr->getConfig('IGNORE_PRODUCT_IMAGE'),
            'ignore_files_count'                => $ntbr->getConfig('IGNORE_FILES_COUNT'),
            'ignore_compression'                => $ntbr->getConfig('IGNORE_COMPRESSION'),
            'maintenance'                       => $ntbr->getConfig('NTBR_MAINTENANCE'),
            'delete_local_backup'               => $ntbr->getConfig('NTBR_DELETE_LOCAL_BACKUP'),
            'encrypt_backup'                    => $ntbr->getConfig('NTBR_ENCRYPT_BACKUP'),
            'part_size'                         => $ntbr->getConfig('NTBR_PART_SIZE'),
            'max_file_to_backup'                => $ntbr->getConfig('NTBR_MAX_FILE_TO_BACKUP'),
            'time_between_backups'              => $ntbr->getConfig('NTBR_TIME_BETWEEN_BACKUPS'),
            'time_between_refresh'              => $ntbr->getConfig('NTBR_TIME_BETWEEN_REFRESH'),
            'time_pause_between_refresh'        => $ntbr->getConfig('NTBR_TIME_PAUSE_BETWEEN_REFRESH'),
            'time_between_progress_refresh'     => $ntbr->getConfig('NTBR_TIME_BETWEEN_PROGRESS_REFRESH'),
            'automation_2nt'                    => $ntbr->getConfig('NTBR_AUTOMATION_2NT'),
            'automation_2nt_hours'              => $ntbr->getConfig('NTBR_AUTOMATION_2NT_HOURS'),
            'automation_2nt_minutes'            => $ntbr->getConfig('NTBR_AUTOMATION_2NT_MINUTES'),
            'activate_2nt_automation'           => $activate_2nt_automation,
            'ftp_port_default'                  => $ftp_port_default,
            'ftp_directory_default'             => $ftp_directory_default,
            'update_owncloud_pass'              => $update_owncloud_pass,
            'send_restore'                      => $ntbr->getConfig('SEND_RESTORE'),
            'xsendfile_detected'                => Tools::apacheModExists('xsendfile'),
            'id_shop_group'                     => $this->id_shop_group,
            'id_shop'                           => $this->id_shop,
            'version'                           => $ntbr->version.$type_module,
            'available_version'                 => $available_version,
            'dropbox_authorizeUrl'              => $dropbox_authorizeUrl,
            'onedrive_authorizeUrl'             => $onedrive_authorizeUrl,
            'googledrive_authorizeUrl'          => $googledrive_authorizeUrl,
            'hubic_authorizeUrl'                => $hubic_authorizeUrl,
            'ajax_loader'                       => $ajax_loader,
            'link_contact'                      => $link_contact,
            'module_address_use'                => $module_address_use,
            'module_address_config'             => $module_address_config,
            'fct_crypt_exists'                  => $fct_crypt_exists,
            'os_windows'                        => $os_windows,
            'curl_exists'                       => $curl_exists,
            'memory_limit'                      => ini_get('memory_limit'),
            'max_execution_time'                => ini_get('max_execution_time'),
            'min_memory_limit'                  => NtbrCore::SET_MEMORY_LIMIT,
            'min_time_new_backup'               => NtbrCore::MIN_TIME_NEW_BACKUP,
            'max_time_before_refresh'           => NtbrCore::MAX_TIME_BEFORE_REFRESH,
            'max_time_before_progress_refresh'  => NtbrCore::MAX_TIME_BEFORE_PROGRESS_REFRESH,
            'big_website'                       => $big_website,
            'ftp_accounts'                      => $ftp_accounts,
            'dropbox_accounts'                  => $dropbox_accounts,
            'googledrive_accounts'              => $googledrive_accounts,
            'onedrive_accounts'                 => $onedrive_accounts,
            'owncloud_accounts'                 => $owncloud_accounts,
            'webdav_accounts'                   => $webdav_accounts,
            'hubic_accounts'                    => $hubic_accounts,
            'aws_accounts'                      => $aws_accounts,
            'ftp_default'                       => $ftp_default,
            'dropbox_default'                   => $dropbox_default,
            'googledrive_default'               => $googledrive_default,
            'onedrive_default'                  => $onedrive_default,
            'owncloud_default'                  => $owncloud_default,
            'webdav_default'                    => $webdav_default,
            'hubic_default'                     => $hubic_default,
            'aws_default'                       => $aws_default,
            'list_comments'                     => $list_comments,
            'backup_type_complete'              => $ntbr->type_backup_complete,
            'backup_type_file'                  => $ntbr->type_backup_file,
            'backup_type_base'                  => $ntbr->type_backup_base,
            'restore_backup_files_complete'     => $restore_backup_files_complete,
            'restore_backup_files_file'         => $restore_backup_files_file,
            'restore_backup_files_base'         => $restore_backup_files_base,
            'restore_backup_finish'             => Tools::substr($ntbr->l('FINISH'), 0, 5),
            'restore_backup_error'              => Tools::substr($ntbr->l('Error'), 0, 5),
        );

        return parent::renderView();
    }

    public function displayFtpAccount()
    {
        $ntbr = new NtbrChild();

        $id_ntbr_ftp = (int)Tools::getValue('id_ntbr_ftp');
        $ftp_account = Ftp::getFtpAccountById($id_ntbr_ftp);

        if (isset($ftp_account['password'])) {
            $ftp_account['password_decrypt'] = $ntbr->decrypt($ftp_account['password']);
        }

        die(Tools::jsonEncode(array('ftp_account' => $ftp_account)));
    }

    public function displayDropboxAccount()
    {
        $id_ntbr_dropbox = (int)Tools::getValue('id_ntbr_dropbox');
        $dropbox_account = Dropbox::getDropboxAccountById($id_ntbr_dropbox);

        die(Tools::jsonEncode(array('dropbox_account' => $dropbox_account)));
    }

    public function displayOwncloudAccount()
    {
        $ntbr = new NtbrChild();

        $id_ntbr_owncloud = (int)Tools::getValue('id_ntbr_owncloud');
        $owncloud_account = Owncloud::getOwncloudAccountById($id_ntbr_owncloud);

        if (isset($owncloud_account['password'])) {
            $owncloud_account['password_decrypt'] = $ntbr->decrypt($owncloud_account['password']);
        }

        die(Tools::jsonEncode(array('owncloud_account' => $owncloud_account)));
    }

    public function displayWebdavAccount()
    {
        $ntbr = new NtbrChild();

        $id_ntbr_webdav = (int)Tools::getValue('id_ntbr_webdav');
        $webdav_account = Webdav::getWebdavAccountById($id_ntbr_webdav);

        if (isset($webdav_account['password'])) {
            $webdav_account['password_decrypt'] = $ntbr->decrypt($webdav_account['password']);
        }

        die(Tools::jsonEncode(array('webdav_account' => $webdav_account)));
    }

    public function displayGoogledriveAccount()
    {
        $id_ntbr_googledrive = (int)Tools::getValue('id_ntbr_googledrive');
        $googledrive_account = Googledrive::getGoogledriveAccountById($id_ntbr_googledrive);

        die(Tools::jsonEncode(array('googledrive_account' => $googledrive_account)));
    }

    public function displayOnedriveAccount()
    {
        $id_ntbr_onedrive = (int)Tools::getValue('id_ntbr_onedrive');
        $onedrive_account = Onedrive::getOnedriveAccountById($id_ntbr_onedrive);

        die(Tools::jsonEncode(array('onedrive_account' => $onedrive_account)));
    }

    public function displayHubicAccount()
    {
        $id_ntbr_hubic = (int)Tools::getValue('id_ntbr_hubic');
        $hubic_account = Hubic::getHubicAccountById($id_ntbr_hubic);

        die(Tools::jsonEncode(array('hubic_account' => $hubic_account)));
    }

    public function displayAwsAccount()
    {
        $id_ntbr_aws = (int)Tools::getValue('id_ntbr_aws');
        $aws_account = Aws::getAwsAccountById($id_ntbr_aws);

        die(Tools::jsonEncode(array('aws_account' => $aws_account)));
    }

    public function saveFtp()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_ftp    = (int)Tools::getValue('id_ntbr_ftp');
        $name           = Tools::getValue('name');
        $active         = (int)(bool)Tools::getValue('active');
        $sftp           = (int)(bool)Tools::getValue('sftp');
        $ssl            = (int)(bool)Tools::getValue('ssl');
        $passive_mode   = (int)(bool)Tools::getValue('passive_mode');
        $nb_backup      = (int)Tools::getValue('nb_backup');
        $nb_backup_file = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base = (int)Tools::getValue('nb_backup_base');
        $server         = Tools::getValue('server');
        $login          = Tools::getValue('login');
        $password       = Tools::getValue('password');
        $port           = (int)Tools::getValue('port');
        $directory      = Tools::getValue('directory');

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        if (!$server || trim($server) == '') {
            $result['errors'][] = $ntbr->l('The server is required.');
        }

        if (!$login || trim($login) == '') {
            $result['errors'][] = $ntbr->l('The login is required.');
        }

        if (!$password || trim($password) == '') {
            $result['errors'][] = $ntbr->l('The password is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name)) {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Ftp::getIdByName($name);

            if ($name_exists_id && $id_ntbr_ftp != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if ($sftp && $ssl) {
            $result['errors'][] = $ntbr->l('SFTP cannot use SSL');
        }

        if ($sftp && $passive_mode) {
            $result['errors'][] = $ntbr->l('SFTP cannot use passive mode');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        if ($sftp) {
            // Connect with SFTP
            if (!$ntbr->testSFTP($server, $login, $password, $port)) {
                $result['errors'][] = $ntbr->l('Unable to connect to the SFTP server, please verify your credentials.');
            }
        } else {
            // Connect with FTP
            if (!$ntbr->testFTP($server, $login, $password, $port, $ssl, $passive_mode)) {
                $result['errors'][] = $ntbr->l('Unable to connect to the FTP server, please verify your credentials.');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_ftp) {
            $ftp = new Ftp($id_ntbr_ftp);
        } else {
            $ftp = new Ftp();
        }

        $ftp->name              = $name;
        $ftp->active            = $active;
        $ftp->sftp              = $sftp;
        $ftp->ssl               = $ssl;
        $ftp->passive_mode      = $passive_mode;
        $ftp->nb_backup         = $nb_backup;
        $ftp->nb_backup_file    = $nb_backup_file;
        $ftp->nb_backup_base    = $nb_backup_base;
        $ftp->server            = $server;
        $ftp->login             = $login;
        $ftp->password          = $ntbr->encrypt($password);
        $ftp->port              = $port;
        $ftp->directory         = $directory;

        if (!$ftp->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_ftp' => $ftp->id)));
    }

    public function saveDropbox()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_dropbox    = (int)Tools::getValue('id_ntbr_dropbox');
        $name               = Tools::getValue('name');
        $active             = (int)(bool)Tools::getValue('active');
        $nb_backup          = (int)Tools::getValue('nb_backup');
        $nb_backup_file     = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base     = (int)Tools::getValue('nb_backup_base');
        $code               = Tools::getValue('code');
        $directory          = Tools::getValue('directory');
        $token              = '';

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Dropbox::getIdByName($name);

            if ($name_exists_id && $id_ntbr_dropbox != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = true;

        if ($code != '') {
            // Get new token
            $token = $ntbr->getDropboxAccessToken($code);
        } else {
            // Get current token
            $token = Dropbox::getDropboxTokenById($id_ntbr_dropbox);
            if ($token && $token != '') {
                $connection = $ntbr->testDropboxConnection($token);
            }
        }

        if (!$token || $token == '') {
            $connection = false;
        }

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your Dropbox account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_dropbox) {
            $dropbox = new Dropbox($id_ntbr_dropbox);
        } else {
            $dropbox = new Dropbox();
        }

        $dropbox->name              = $name;
        $dropbox->active            = $active;
        $dropbox->nb_backup         = $nb_backup;
        $dropbox->nb_backup_file    = $nb_backup_file;
        $dropbox->nb_backup_base    = $nb_backup_base;
        $dropbox->directory         = $directory;
        $dropbox->token             = $token;

        if (!$dropbox->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_dropbox' => $dropbox->id)));
    }

    public function saveOwncloud()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_owncloud   = (int)Tools::getValue('id_ntbr_owncloud');
        $name               = Tools::getValue('name');
        $active             = (int)(bool)Tools::getValue('active');
        $nb_backup          = (int)Tools::getValue('nb_backup');
        $nb_backup_file     = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base     = (int)Tools::getValue('nb_backup_base');
        $login              = Tools::getValue('login');
        $password           = Tools::getValue('password');
        $server             = Tools::getValue('server');
        $directory          = Tools::getValue('directory');

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        if (!$server || trim($server) == '') {
            $result['errors'][] = $ntbr->l('The server is required.');
        }

        if (!$login || trim($login) == '') {
            $result['errors'][] = $ntbr->l('The login is required.');
        }

        if (!$password || trim($password) == '') {
            $result['errors'][] = $ntbr->l('The password is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Owncloud::getIdByName($name);

            if ($name_exists_id && $id_ntbr_owncloud != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = $ntbr->testOwncloudConnection($server, $login, $password);

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your ownCloud account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_owncloud) {
            $owncloud = new Owncloud($id_ntbr_owncloud);
        } else {
            $owncloud = new Owncloud();
        }

        $owncloud->name             = $name;
        $owncloud->active           = $active;
        $owncloud->nb_backup        = $nb_backup;
        $owncloud->nb_backup_file   = $nb_backup_file;
        $owncloud->nb_backup_base   = $nb_backup_base;
        $owncloud->login            = $login;
        $owncloud->password         = $ntbr->encrypt($password);
        $owncloud->server           = $server;
        $owncloud->directory        = $directory;

        if (!$owncloud->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_owncloud' => $owncloud->id)));
    }

    public function saveWebdav()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_webdav     = (int)Tools::getValue('id_ntbr_webdav');
        $name               = Tools::getValue('name');
        $active             = (int)(bool)Tools::getValue('active');
        $nb_backup          = (int)Tools::getValue('nb_backup');
        $nb_backup_file     = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base     = (int)Tools::getValue('nb_backup_base');
        $login              = Tools::getValue('login');
        $password           = Tools::getValue('password');
        $server             = Tools::getValue('server');
        $directory          = Tools::getValue('directory');

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        if (!$server || trim($server) == '') {
            $result['errors'][] = $ntbr->l('The URL is required.');
        }

        if (!$login || trim($login) == '') {
            $result['errors'][] = $ntbr->l('The login is required.');
        }

        if (!$password || trim($password) == '') {
            $result['errors'][] = $ntbr->l('The password is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Webdav::getIdByName($name);

            if ($name_exists_id && $id_ntbr_webdav != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = $ntbr->testWebdavConnection($server, $login, $password);

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your WebDAV account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_webdav) {
            $webdav = new Webdav($id_ntbr_webdav);
        } else {
            $webdav = new Webdav();
        }

        $webdav->name           = $name;
        $webdav->active         = $active;
        $webdav->nb_backup      = $nb_backup;
        $webdav->nb_backup_file = $nb_backup_file;
        $webdav->nb_backup_base = $nb_backup_base;
        $webdav->login          = $login;
        $webdav->password       = $ntbr->encrypt($password);
        $webdav->server         = $server;
        $webdav->directory      = $directory;

        if (!$webdav->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_webdav' => $webdav->id)));
    }

    public function saveGoogledrive()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_googledrive    = (int)Tools::getValue('id_ntbr_googledrive');
        $name                   = Tools::getValue('name');
        $active                 = (int)(bool)Tools::getValue('active');
        $nb_backup              = (int)Tools::getValue('nb_backup');
        $nb_backup_file         = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base         = (int)Tools::getValue('nb_backup_base');
        $code                   = Tools::getValue('code');
        $directory_path         = Tools::getValue('directory_path');
        $directory_key          = Tools::getValue('directory_key');
        $token                  = '';

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Googledrive::getIdByName($name);

            if ($name_exists_id && $id_ntbr_googledrive != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = true;

        if ($code != '') {
            // Get new token
            $token = $ntbr->getGoogledriveAccessToken($code);
        } else {
            // Get current token
            $token = Googledrive::getGoogledriveTokenById($id_ntbr_googledrive);
            if ($token && $token != '') {
                $connection = $ntbr->testGoogledriveConnection($token);
            }
        }

        if (!$token || $token == '') {
            $connection = false;
        }

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your Google Drive account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_googledrive) {
            $googledrive = new Googledrive($id_ntbr_googledrive);
        } else {
            $googledrive = new Googledrive();
        }

        if (!$directory_key || $directory_key == '') {
            $directory_key = NtbrCore::GOOGLEDRIVE_ROOT_ID;
        }

        if (!$directory_path || $directory_path == '') {
            $directory_path = $ntbr->l('Home');
        }

        $googledrive->name              = $name;
        $googledrive->active            = $active;
        $googledrive->nb_backup         = $nb_backup;
        $googledrive->nb_backup_file    = $nb_backup_file;
        $googledrive->nb_backup_base    = $nb_backup_base;
        $googledrive->directory_path    = $directory_path;
        $googledrive->directory_key     = $directory_key;
        $googledrive->token             = $token;

        if (!$googledrive->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_googledrive' => $googledrive->id)));
    }

    public function saveOnedrive()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_onedrive       = (int)Tools::getValue('id_ntbr_onedrive');
        $name                   = Tools::getValue('name');
        $active                 = (int)(bool)Tools::getValue('active');
        $nb_backup              = (int)Tools::getValue('nb_backup');
        $nb_backup_file         = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base         = (int)Tools::getValue('nb_backup_base');
        $code                   = Tools::getValue('code');
        $directory_path         = Tools::getValue('directory_path');
        $directory_key          = Tools::getValue('directory_key');
        $token                  = '';

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Onedrive::getIdByName($name);

            if ($name_exists_id && $id_ntbr_onedrive != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = true;

        if ($code != '') {
            // Get new token
            $token = $ntbr->getOnedriveAccessToken($code);
        } else {
            // Get current token
            $token = Onedrive::getOnedriveTokenById($id_ntbr_onedrive);
            if ($token && $token != '') {
                $connection = $ntbr->testOnedriveConnection($token, $id_ntbr_onedrive);
            }
        }

        if (!$token || $token == '') {
            $connection = false;
        }

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your OneDrive account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_onedrive) {
            $onedrive = new Onedrive($id_ntbr_onedrive);
        } else {
            $onedrive = new Onedrive();
        }

        if (!$directory_key || $directory_key == '') {
            $onedrive_lib = $ntbr->connectToOnedrive($token, $id_ntbr_onedrive);
            $directory_key = $onedrive_lib->getRootID();
        }

        if (!$directory_path || $directory_path == '') {
            $directory_path = $ntbr->l('Home');
        }

        $onedrive->name              = $name;
        $onedrive->active            = $active;
        $onedrive->nb_backup         = $nb_backup;
        $onedrive->nb_backup_file    = $nb_backup_file;
        $onedrive->nb_backup_base    = $nb_backup_base;
        $onedrive->directory_path    = $directory_path;
        $onedrive->directory_key     = $directory_key;
        $onedrive->token             = $token;

        if (!$onedrive->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_onedrive' => $onedrive->id)));
    }

    public function saveHubic()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_hubic          = (int)Tools::getValue('id_ntbr_hubic');
        $name                   = Tools::getValue('name');
        $active                 = (int)(bool)Tools::getValue('active');
        $nb_backup              = (int)Tools::getValue('nb_backup');
        $nb_backup_file         = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base         = (int)Tools::getValue('nb_backup_base');
        $code                   = Tools::getValue('code');
        $directory              = Tools::getValue('directory');
        $connect_infos          = array();

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Hubic::getIdByName($name);

            if ($name_exists_id && $id_ntbr_hubic != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = true;

        if ($code != '') {
            // Get new connection infos
            $connect_infos = $ntbr->getHubicAccessToken($code);
        } elseif ($id_ntbr_hubic) {
            $connection     = $ntbr->testHubicConnection($id_ntbr_hubic);
            $connect_infos  = Hubic::getHubicConnectionInfosById($id_ntbr_hubic);
        }

        if (!is_array($connect_infos) || !isset($connect_infos['token']) || !isset($connect_infos['credential'])) {
            $connection = false;
        }

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your Hubic account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if ($id_ntbr_hubic) {
            $hubic = new Hubic($id_ntbr_hubic);
        } else {
            $hubic = new Hubic();
        }

        $hubic->name                    = $name;
        $hubic->active                  = $active;
        $hubic->nb_backup               = $nb_backup;
        $hubic->nb_backup_file          = $nb_backup_file;
        $hubic->nb_backup_base          = $nb_backup_base;
        $hubic->directory               = $directory;
        $hubic->token                   = $connect_infos['token'];
        $hubic->credential              = $connect_infos['credential'];

        if (!$hubic->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_hubic' => $hubic->id)));
    }

    public function saveAws()
    {
        $ntbr   = new NtbrChild();
        $result = array(
            'success'   => 1,
            'errors'    => array()
        );

        $id_ntbr_aws        = (int)Tools::getValue('id_ntbr_aws');
        $name               = Tools::getValue('name');
        $active             = (int)(bool)Tools::getValue('active');
        $nb_backup          = (int)Tools::getValue('nb_backup');
        $nb_backup_file     = (int)Tools::getValue('nb_backup_file');
        $nb_backup_base     = (int)Tools::getValue('nb_backup_base');
        $access_key_id      = Tools::getValue('access_key_id');
        $secret_access_key  = Tools::getValue('secret_access_key');
        $region             = Tools::getValue('region');
        $bucket             = Tools::getValue('bucket');
        $directory_key      = Tools::getValue('directory_key');
        $directory_path     = Tools::getValue('directory_path');

        // Check values

        // Required data
        if (!$name || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The name is required.');
        }

        if (!$id_ntbr_aws) {
            $aws = new Aws();

            if (!$access_key_id || trim($access_key_id) == '') {
                $result['errors'][] = $ntbr->l('The access key ID is required.');
            }

            if (!$secret_access_key || trim($secret_access_key) == '') {
                $result['errors'][] = $ntbr->l('The secret access key is required.');
            }
        } else {
            $aws = new Aws($id_ntbr_aws);

            if (!$access_key_id || trim($access_key_id) == '') {
                $access_key_id = $aws->access_key_id;
            }

            if (!$secret_access_key || trim($secret_access_key) == '') {
                $secret_access_key = $aws->secret_access_key;
            }
        }

        if (!$region || trim($region) == '') {
            $result['errors'][] = $ntbr->l('The region is required.');
        }

        if (!$bucket || trim($bucket) == '') {
            $result['errors'][] = $ntbr->l('The bucket is required.');
        }

        // Data validity
        if (!Validate::isGenericName($name) || trim($name) == '') {
            $result['errors'][] = $ntbr->l('The account name is not valid. Please do not use those characters').' "<>={}"';
        } else {
            $name_exists_id = Aws::getIdByName($name);

            if ($name_exists_id && $id_ntbr_aws != $name_exists_id) {
                $result['errors'][] = $ntbr->l('The account name is already used');
            }
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Check connection
        $connection = $ntbr->testAwsConnection($access_key_id, $secret_access_key, $region, $bucket);

        if (!$connection) {
            $result['errors'][] = $ntbr->l('Unable to connect to your AWS account');
        }

        if (count($result['errors'])) {
            $result['success'] = 0;
            die(Tools::jsonEncode(array('result' => $result)));
        }

        // Save data
        if (!$directory_key) {
            $directory_key = $bucket;
        }

        if (!$directory_path) {
            $directory_path = $bucket;
        }

        $aws->name              = $name;
        $aws->active            = $active;
        $aws->nb_backup         = $nb_backup;
        $aws->nb_backup_file    = $nb_backup_file;
        $aws->nb_backup_base    = $nb_backup_base;
        $aws->access_key_id     = $access_key_id;
        $aws->secret_access_key = $secret_access_key;
        $aws->region            = $region;
        $aws->bucket            = $bucket;
        $aws->directory_key     = $directory_key;
        $aws->directory_path    = $directory_path;

        if (!$aws->save()) {
            $result['success'] = 0;
        }

        die(Tools::jsonEncode(array('result' => $result, 'id_ntbr_aws' => $aws->id)));
    }

    public function checkConnectionFtp()
    {
        $ntbr           = new NtbrChild();
        $success        = 0;
        $id_ntbr_ftp    = (int)Tools::getValue('id_ntbr_ftp');

        if (!$id_ntbr_ftp) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        $ftp = new Ftp($id_ntbr_ftp);
        $password = $ntbr->decrypt($ftp->password);

        if ($ftp->sftp) {
            // Connect with SFTP
            if ($ntbr->testSFTP($ftp->server, $ftp->login, $password, $ftp->port)) {
                $success = 1;
            }
        } else {
            // Connect with FTP
            if ($ntbr->testFTP($ftp->server, $ftp->login, $password, $ftp->port, $ftp->ssl, $ftp->passive_mode)) {
                $success = 1;
            }
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionDropbox()
    {
        $ntbr               = new NtbrChild();
        $success            = 0;
        $id_ntbr_dropbox    = (int)Tools::getValue('id_ntbr_dropbox');

        if (!$id_ntbr_dropbox) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        $token = Dropbox::getDropboxTokenById($id_ntbr_dropbox);
        if ($token && $token != '') {
            $success = (int)(bool)$ntbr->testDropboxConnection($token);
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionOwncloud()
    {
        $ntbr               = new NtbrChild();
        $success            = 0;
        $id_ntbr_owncloud   = (int)Tools::getValue('id_ntbr_owncloud');

        if (!$id_ntbr_owncloud) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        $owncloud   = new Owncloud($id_ntbr_owncloud);
        $password   = $ntbr->decrypt($owncloud->password);

        if ($ntbr->testOwncloudConnection($owncloud->server, $owncloud->login, $password)) {
            $success = 1;
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionWebdav()
    {
        $ntbr           = new NtbrChild();
        $success        = 0;
        $id_ntbr_webdav = (int)Tools::getValue('id_ntbr_webdav');

        if (!$id_ntbr_webdav) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        $webdav   = new Webdav($id_ntbr_webdav);
        $password   = $ntbr->decrypt($webdav->password);

        if ($ntbr->testWebdavConnection($webdav->server, $webdav->login, $password)) {
            $success = 1;
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionGoogledrive()
    {
        $ntbr                   = new NtbrChild();
        $success                = 0;
        $id_ntbr_googledrive    = (int)Tools::getValue('id_ntbr_googledrive');

        if (!$id_ntbr_googledrive) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        $token = Googledrive::getGoogledriveTokenById($id_ntbr_googledrive);
        if ($token && $token != '') {
            $success = (int)(bool)$ntbr->testGoogledriveConnection($token);
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionOnedrive()
    {
        $ntbr                   = new NtbrChild();
        $success                = 0;
        $id_ntbr_onedrive    = (int)Tools::getValue('id_ntbr_onedrive');

        if (!$id_ntbr_onedrive) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        $token = Onedrive::getOnedriveTokenById($id_ntbr_onedrive);
        if ($token && $token != '') {
            $success = (int)(bool)$ntbr->testOnedriveConnection($token, $id_ntbr_onedrive);
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionHubic()
    {
        $ntbr           = new NtbrChild();
        $success        = 0;
        $id_ntbr_hubic  = (int)Tools::getValue('id_ntbr_hubic');

        if (!$id_ntbr_hubic) {
            die(Tools::jsonEncode(array('success' => $success)));
        }

        if ($id_ntbr_hubic) {
            $success = (int)(bool)$ntbr->testHubicConnection($id_ntbr_hubic);
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function checkConnectionAws()
    {
        $ntbr           = new NtbrChild();
        $id_ntbr_aws    = (int)Tools::getValue('id_ntbr_aws');

        if (!$id_ntbr_aws) {
            die(Tools::jsonEncode(array('success' => 0)));
        }

        $aws        = new Aws($id_ntbr_aws);
        $success    = (int)(bool)$ntbr->testAwsConnection($aws->access_key_id, $aws->secret_access_key, $aws->region, $aws->bucket);

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteFtp()
    {
        $success        = 0;
        $id_ntbr_ftp    = (int)Tools::getValue('id_ntbr_ftp');

        if ($id_ntbr_ftp) {
            $ftp = new Ftp($id_ntbr_ftp);
            $success = $ftp->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteDropbox()
    {
        $success            = 0;
        $id_ntbr_dropbox    = (int)Tools::getValue('id_ntbr_dropbox');

        if ($id_ntbr_dropbox) {
            $dropbox = new Dropbox($id_ntbr_dropbox);
            $success = $dropbox->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteOwncloud()
    {
        $success            = 0;
        $id_ntbr_owncloud   = (int)Tools::getValue('id_ntbr_owncloud');

        if ($id_ntbr_owncloud) {
            $owncloud = new Owncloud($id_ntbr_owncloud);
            $success = $owncloud->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteWebdav()
    {
        $success            = 0;
        $id_ntbr_webdav   = (int)Tools::getValue('id_ntbr_webdav');

        if ($id_ntbr_webdav) {
            $webdav = new Webdav($id_ntbr_webdav);
            $success = $webdav->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteGoogledrive()
    {
        $success                = 0;
        $id_ntbr_googledrive    = (int)Tools::getValue('id_ntbr_googledrive');

        if ($id_ntbr_googledrive) {
            $googledrive = new Googledrive($id_ntbr_googledrive);
            $success = $googledrive->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteOnedrive()
    {
        $success            = 0;
        $id_ntbr_onedrive   = (int)Tools::getValue('id_ntbr_onedrive');

        if ($id_ntbr_onedrive) {
            $onedrive = new Onedrive($id_ntbr_onedrive);
            $success = $onedrive->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteHubic()
    {
        $success            = 0;
        $id_ntbr_hubic    = (int)Tools::getValue('id_ntbr_hubic');

        if ($id_ntbr_hubic) {
            $hubic = new Hubic($id_ntbr_hubic);
            $success = $hubic->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function deleteAws()
    {
        $success            = 0;
        $id_ntbr_aws   = (int)Tools::getValue('id_ntbr_aws');

        if ($id_ntbr_aws) {
            $aws        = new Aws($id_ntbr_aws);
            $success    = $aws->delete();
        }

        die(Tools::jsonEncode(array('success' => $success)));
    }

    public function displayGoogledriveTree()
    {
        $ntbr                   = new NtbrChild();
        $tree                   = $ntbr->l('You need to register a valid authorization code to choose a directory');
        $id_ntbr_googledrive    = (int)Tools::getValue('id_ntbr_googledrive');

        if ($id_ntbr_googledrive) {
            $googledrive    = new Googledrive($id_ntbr_googledrive);
            $tree           = $ntbr->getGoogledriveTree($googledrive->directory_key);
        } else {
            $tree = $ntbr->l('Unknown account');
        }

        die(Tools::jsonEncode(array('tree' => $tree)));
    }

    public function displayGoogledriveTreeChild()
    {
        $ntbr                   = new NtbrChild();
        $tree                   = $ntbr->l('You need to register a valid authorization code to choose a directory');
        $id_ntbr_googledrive    = (int)Tools::getValue('id_ntbr_googledrive');
        $id_parent              = Tools::getValue('id_parent');
        $googledrive_dir        = Tools::getValue('googledrive_dir');
        $level                  = Tools::getValue('level');
        $path                   = Tools::getValue('path');

        if ($id_ntbr_googledrive) {
            $googledrive = new Googledrive($id_ntbr_googledrive);
            $tree = $ntbr->getGoogledriveTreeChildren($googledrive->token, $id_parent, $googledrive_dir, $level, $path);
        } else {
            $tree = $ntbr->l('Unknown account');
        }

        die(Tools::jsonEncode(array('tree' => $tree)));
    }

    public function displayOnedriveTree()
    {
        $ntbr               = new NtbrChild();
        $tree               = $ntbr->l('You need to register a valid authorization code to choose a directory');
        $id_ntbr_onedrive   = (int)Tools::getValue('id_ntbr_onedrive');

        if ($id_ntbr_onedrive) {
            $onedrive = new Onedrive($id_ntbr_onedrive);
            $tree = $ntbr->getOnedriveTree($onedrive->token, $onedrive->directory_key, $id_ntbr_onedrive);
        } else {
            $tree = $ntbr->l('Unknown account');
        }

        die(Tools::jsonEncode(array('tree' => $tree)));
    }

    public function displayOnedriveTreeChild()
    {
        $ntbr               = new NtbrChild();
        $tree               = $ntbr->l('You need to register a valid authorization code to choose a directory');
        $id_ntbr_onedrive   = (int)Tools::getValue('id_ntbr_onedrive');
        $id_parent          = Tools::getValue('id_parent');
        $onedrive_dir       = Tools::getValue('onedrive_dir');
        $level              = Tools::getValue('level');
        $path               = Tools::getValue('path');

        if ($id_ntbr_onedrive) {
            $onedrive = new Onedrive($id_ntbr_onedrive);
            $tree = $ntbr->getOnedriveTreeChildren($onedrive->token, $onedrive_dir, $id_parent, $level, $path, $id_ntbr_onedrive);
        } else {
            $tree = $ntbr->l('Unknown account');
        }

        die(Tools::jsonEncode(array('tree' => $tree)));
    }

    public function displayAwsTree()
    {
        $ntbr           = new NtbrChild();
        $tree           = $ntbr->l('You need to register a valid account to choose a directory');
        $id_ntbr_aws    = (int)Tools::getValue('id_ntbr_aws');

        if ($id_ntbr_aws) {
            $tree   = $ntbr->getAwsTree($id_ntbr_aws);
        } else {
            $tree = $ntbr->l('Unknown account');
        }

        die(Tools::jsonEncode(array('tree' => $tree)));
    }

    public function displayAwsTreeChild()
    {
        $ntbr           = new NtbrChild();
        $tree           = $ntbr->l('You need to register a valid account to choose a directory');
        $id_ntbr_aws    = (int)Tools::getValue('id_ntbr_aws');
        $directory_key  = Tools::getValue('directory_key');
        $directory_path = Tools::getValue('directory_path');
        $level          = Tools::getValue('level');

        if ($id_ntbr_aws) {
            $tree = $ntbr->getAwsTreeChildren($directory_key, $level, $directory_path, $id_ntbr_aws);
        } else {
            $tree = $ntbr->l('Unknown account');
        }

        die(Tools::jsonEncode(array('tree' => $tree)));
    }

    public function sendBackupAway()
    {
        $ntbr                   = new NtbrChild();
        $nb                     = Tools::getValue('nb');
        $current_time           = time();
        $time_between_backups   = $ntbr->getConfig('NTBR_TIME_BETWEEN_BACKUPS');
        $ntbr_ongoing           = $ntbr->getConfig('NTBR_ONGOING');

        if ($time_between_backups <= 0) {
            $time_between_backups = NtbrCore::MIN_TIME_NEW_BACKUP;
        }

        if ($current_time - $ntbr_ongoing >= $time_between_backups) {
            $backups    = $ntbr->findThisBackup($nb);

            if (strpos($nb, '.') === false) {
                // We dowload all the files
                if (is_array($backups)) {
                    $first_backup = reset($backups);
                }

                if (!is_array($first_backup) || !isset($first_backup['name'])) {
                    $ntbr->log('ERR'.$ntbr->l('The backup was not found'));
                    die();
                }

                $backup_name = $first_backup['name'];
                $backup_list = array();

                foreach ($backups as $backup) {
                    $backup_list[] = $backup['name'];
                }
            } else {
                // We download only one file
                if (!is_array($backups) || !isset($backups[$nb]) || !isset($backups[$nb]['name'])) {
                    $ntbr->log('ERR'.$ntbr->l('The backup was not found'));
                    die();
                }

                $backup_name = $backups[$nb]['name'];
                $backup_list = array($backups[$nb]['name']);
            }

            $ntbr->setConfig('NTBR_ONGOING', time());
            $result = $ntbr->backup(false, false, NtbrCore::STEP_SEND_AWAY, $backup_name, $backup_list);

            if ($result) {
                $update = $ntbr->updateBackupList();
                die(Tools::jsonEncode(array('backuplist' => $update, 'warnings' => $ntbr->warnings)));
            }
        } else {
            $time_to_wait = $time_between_backups - ($current_time - $ntbr_ongoing);
            $ntbr->log('ERR'.sprintf($ntbr->l('For security reason, some time is needed between two backups. Please wait %d seconds'), $time_to_wait));
        }

        die();
    }

    public function addCommentBackup()
    {
        $ntbr           = new NtbrChild();
        $backup_name    = Tools::getValue('backup_name');
        $backup_comment = Tools::getValue('backup_comment');

        if (!$backup_name || $backup_name == '') {
            die(Tools::jsonEncode(array('result' => '0')));
        }

        $infos = Comments::getBackupCommentInfos($backup_name);

        if (isset($infos['id_ntbr_comments'])) {
            // Already exists
            $comment = new Comments($infos['id_ntbr_comments']);
        } else {
            // Do not exist yet
            $comment = new Comments();
            $comment->backup_name = $backup_name;
        }
        $comment->comment = $backup_comment;

        if ($comment->save()) {
            die(Tools::jsonEncode(array('result' => '1')));
        }

        die(Tools::jsonEncode(array('result' => '0')));
    }

    public function restoreBackup()
    {
        $ntbr               = new NtbrChild();
        $backup_name        = Tools::getValue('backup');
        $type_backup        = Tools::getValue('type_backup');

        if (!$backup_name || $backup_name == '' || !$type_backup || $type_backup == '') {
            die(Tools::jsonEncode(array('result' => '0')));
        }

        $options_restore = $ntbr->startLocalRestore($backup_name, $type_backup);

        if ($options_restore === false) {
            die(Tools::jsonEncode(array('result' => '0')));
        }

        die(Tools::jsonEncode(array('result' => '1', 'options' => $options_restore)));
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title = $this->l('2NT Backup and Restore');
    }

    /**
     * assign default action in page_header_toolbar_btn smarty var, if they are not set.
     * uses override to specifically add, modify or remove items
     *
     */
    public function initPageHeaderToolbar()
    {
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            if ($this->display == 'view') {
                    $this->page_header_toolbar_btn['save'] = array(
                        'href' => '#',
                        'desc' => $this->l('Save')
                    );
            }
            parent::initPageHeaderToolbar();
        }
    }

    /**
     * assign default action in toolbar_btn smarty var, if they are not set.
     * uses override to specifically add, modify or remove items
     *
     */
    public function initToolbar()
    {
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') !== true) {
            if ($this->display == 'view') {
                // Default save button - action dynamically handled in javascript
                $this->toolbar_btn['save'] = array(
                    'href' => '#',
                    'desc' => $this->l('Save')
                );
            }
        }
    }
}
