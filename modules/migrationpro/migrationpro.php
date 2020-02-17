<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from MigrationPro
* Use, copy, modification or distribution of this source file without written
* license agreement from the MigrationPro is strictly forbidden.
* In order to obtain a license, please contact us: contact@migration-pro.com
*
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe MigrationPro
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la MigrationPro est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter la MigrationPro a l'adresse: contact@migration-pro.com
*
* @author    MigrationPro
* @copyright Copyright (c) 2012-2019 MigrationPro
* @license   Commercial license
* @package   MigrationPro: Prestashop Upgrade and Migrate tool
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

@ini_set('max_execution_time', 0);
@ini_set('error_reporting', 1);
@ini_set('memory_limit', '-1');


require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProMapping.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProSaveMapping.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProProcess.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProData.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProMigratedData.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/EDClient.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/EDQuery.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/EDImport.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProPassLog.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProMappingCreator.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/MigrationProTimeRemapping.php');


class MigrationPro extends Module
{
    protected $wizard_steps;

    public function __construct()
    {
        $this->name = 'migrationpro';
        $this->tab = 'migration_tools';
        $this->version = '6.0.3';
        $this->author = 'MigrationPro';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '9581b42794aee77c5be55b1342e37671';
        $this->author_address = '0x24cA4dE04f3EC79296742139589b4f9A9892E1ec';
        
        parent::__construct();

        $this->displayName = $this->l('Prestashop Upgrade and Migrate tool');
        $this->description = $this->l('Upgrade Prestashop 1.4, 1.5 or 1.6 to Prestashop 1.7 for an instant!');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }
    
    public function install()
    {
        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminMigrationPro';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'MigrationPro';
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;

        include(dirname(__FILE__) . '/sql/install.php');

        Configuration::updateValue('migrationpro_module_path', $this->local_path);
        Configuration::updateValue('migrationpro_token_is_generated', '0');
        Configuration::updateValue('migrationpro_pause', false);
        Configuration::updateValue('migrationpro_step_status', 1);
        
        if (!$tab->add() ||
            !parent::install() ||
            !$this->registerHook('header') ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('backOfficeFooter') ||
            !$this->registerHook('actionBeforeAuthentication')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        Configuration::deleteByName('migrationpro_module_path');

        $id_tab = (int)Tab::getIdFromClassName('AdminMigrationPro');

        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/index.tpl');


        return $output;
    }

    public function hookActionBeforeAuthentication()
    {

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $mail = Tools::getValue('email');
            $pass = Tools::getValue('password');
        } else {
            $mail = Tools::getValue('email');
            $pass = Tools::getValue('passwd');
        }
        if (Validate::isEmail($mail)) {
            $result = MigrationProPassLog::getUser($mail);
            if (!empty($result)) {
                $hashpass = end($result)['passwd'];
                $id_customer = end($result)['id_customer'];
                if (self::encrypt($pass) == $hashpass) {
                    $customer = new Customer($id_customer);
                    $customer->passwd = Tools::encrypt($pass);
                    if ($customer->save()) {
                        MigrationProPassLog::deleteUserById($id_customer);
                    }
                }
            }
        }
    }

    public static function encrypt($passwd)
    {
        return self::hash($passwd);
    }

    public static function hash($passwd)
    {
        return md5(Configuration::get('migrationpro_cookie_key') . $passwd);
    }


    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJquery();
            $validate_url = $this->context->link->getAdminLink('AdminMigrationPro');
//            ddd($this->_path);
            $ps_module_url = $this->_path;
            $source_url = Configuration::get($this->name . '_url');
            $step_status = Configuration::get($this->name . '_step_status');
            $pause = MigrationProProcess::getActiveProcessObject() ? true : false;
            $this->context->controller->addCSS($this->_path . 'views/css/app.css');
            Media::addJsDef(array('validate_url' => $validate_url, 'source_url' => $source_url, 'step_status' => $step_status, 'pause' => $pause,));
            Media::addJsDef(array('translate' => self::translate()));
            Media::addJsDef(array('ps_module_url' => $ps_module_url));
            Media::addJsDef(array('current_step' => $this->checkLastStep()));
        }
    }


    public function hookBackOfficeFooter($param)
    {
        if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
            $html = '';
            $html .= '<script type="text/javascript" src="' . $this->_path . 'views/js/manifest.js" ></script>';
            $html .= '<script type="text/javascript" src="' . $this->_path . 'views/js/vendor.js" ></script>';
            $html .= '<script type="text/javascript" src="' . $this->_path . 'views/js/app.js" ></script>';
            return $html;
        }
    }

    public static function isEmpty($field)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return ($field === '' || $field === null || $field === array() || $field === 0 || $field === '0');
        } else {
            return empty($field);
        }
    }

    public function checkLastStep()
    {
        // $idShop=(int)Context::getContext()->shop->id;
        $step = Configuration::get($this->name . '_step_status');
        if ($step == 2) {
            return $this->jsonStepTwo();
        } else if ($step == 3) {
            $response = array();
            $response['step_form'] = $this->jsonStepThree();
            $response['time_remaining'] ='00:00:00';
            $response['errors'] = null;
            $response['has_error'] = false;
            $response['has_warning'] = false;
            $response['pause'] =$this->isPaused();
            $response['warnings'] = null;
            return $response;
        } else if ($step == 4) {
            return $this->jsonStepFour();
        } else {
            return $this->jsonStepOne();
        }
    }

    public function jsonStepOne()
    {
//
        return array('status' => true, 'source_url' => Configuration::get($this->name . '_url'));
    }

    //    step 2   -> mapping array
    public function jsonStepTwo()
    {
        $target = array();
        $mappings = array();

        $target['multi_shops'] = Shop::getShops();
        $target['order_states'] = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));
        $target['currencies'] = Currency::getCurrencies();
        $target['languages'] = Language::getLanguages();
        $target['customer_groups'] = Group::getGroups(Configuration::get('PS_LANG_DEFAULT'));

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('migrationpro_mapping');
        $rows = Db::getInstance()->executeS($sql);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if ($row['group'] == 'mapping') {
                    $target_loop = array();
                    $id_key = '';
                    switch ($row['type']) {
                        case 'multi_shops':
                            $id_key = 'id_shop';
                            break;
                        case 'order_states':
                            $id_key = 'id_order_state';
                            break;
                        case 'currencies':
                            $id_key = 'id_currency';
                            break;
                        case 'languages':
                            $id_key = 'id_lang';
                            break;
                        case 'customer_groups':
                            $id_key = 'id_group';
                            break;
                    }
                    $selected_value = array('target_id'=> $row['mapping'], 'source_id' => $row['source_id']);

                    foreach ($target[$row['type']] as $value) {
                        if (!self::isEmpty($value['name'])) {
                            $sub_value =array('target_id'=> $value[$id_key], 'source_id' => $row['source_id']);
                            $selected = false;
                            // if( $selected_value == json_encode($sub_value))
                            //     $selected = true;
                            
                            $target_loop[] = array(
                                'label' => $value['name'],
                                //'j_value' =>  json_encode($sub_value),
                                'value' => $sub_value,
                            );
                        }
                    }

                    $mappings[$row['group']][$row['type']][] = array(
                        'id_mapping' => $row['id_mapping'],
                        'source_id' => $row['source_id'],
                        'source_name' => $row['source_name'],
                        'mapping' => $selected_value, //$row['mapping'],
                        'target' => $target_loop
                    );
                } else {
                    $mappings[$row['group']][$row['type']][] = array(
                        'id_mapping' => $row['id_mapping'],
                        'mapping' => $row['mapping']
                    );
                }
            }
        }
        $mappings['source_shop_url'] = Configuration::get('migrationpro_url');
        $mappings['process'] = $this->jsonStepThree();
        return $mappings;
    }


    public function jsonStepThree()
    {
        $process = array();
        $texts = $this->translate()['step_2'];
        if (count($lastExecutingProcesses = MigrationProProcess::getAll())) {
            for ($i = 0; $i <= count($lastExecutingProcesses) - 1; $i++) {
                if ($lastExecutingProcesses[$i]['type'] == 'cart_rules') {
                    unset($lastExecutingProcesses[$i]);
                }
                if ('manufacturers' == $lastExecutingProcesses[$i]['type']) {
                    $lastExecutingProcesses[$i]['type'] = 'manufactures';
                } else if ('cart rules' == $lastExecutingProcesses[$i]['type']) {
                    $lastExecutingProcesses[$i]['type'] = 'cart_rules';
                }
                $process[$lastExecutingProcesses[$i]['type']] = $lastExecutingProcesses[$i];
            }
        }

        return $process;
    }

    public function jsonStepFour()
    {
        $process = array();
        $texts = $this->translate()['step_2'];

        if (count($lastExecutingProcesses = MigrationProProcess::getAll())) {
            foreach ($lastExecutingProcesses as $lastExecutingProcess) {
                if ('manufacturers' == $lastExecutingProcess['type']) {
                    $lastExecutingProcess['type'] = 'manufactures';
                } else if ('cart rules' == $lastExecutingProcess['type']) {
                    $lastExecutingProcess['type'] = 'cart_rules';
                }
                // Set repport data
                $process[] = array(
                    str_replace(" ", "_", Tools::strtolower($this->l('Entity'))) => $texts[$lastExecutingProcess['type']],
                    str_replace(" ", "_", Tools::strtolower($this->l('Migration'))) => $lastExecutingProcess['imported'],
                    str_replace(" ", "_", Tools::strtolower($this->l('Warning Count'))) => $lastExecutingProcess['error_count'],
                    str_replace(" ", "_", Tools::strtolower($this->l('Total'))) => $lastExecutingProcess['total'],);
            }
        }

        return $process;
    }

    public function translate()
    {
        $output = array();
        $output['header'] = array(
            'documentation' =>  $this->l('Documentation'),
            'documentation_path'=> 'documentation/documentation_en.pdf',
            'documentation_is_enabled'=> true,
            'tutorials' =>  $this->l('Tutorials'),
            'tutorials_url'=> 'https://www.youtube.com/watch?v=F479T2THM94',
            'tutorial_is_enabled'=> true,
            'support' =>  $this->l('Support'),
            'support_url'=> 'https://addons.prestashop.com/en/contact-us?id_product=8934',
            'support_is_enabled'=> true,
        );
        $report_download_url =  $this->context->link->getAdminLink('AdminMigrationPro', true, array(), array("action"=>"download_report"/*, "configure"=>"migrationpro"*/));
        $report_image_configuration_url =  $this->context->link->getAdminLink('AdminImages', true, array(), array());
        //step 1
        $output['step_1'] = array('header' => $this->l('Connection'),
            'advice' => $this->l('To migrate your Source data you need to establish a secure connection between Target shop and Source shop.'),
            'connection' => $this->l('Source shop setup:'),
            'todo' => $this->l('Download and install the bridge connector module to the Source shop. Please make sure you are uploading to the Source shop.'),
            'source_url_placeholder' => $this->l('http://source-prestashop-domain.com'),
            'download' => $this->l('Download bridge connector'),
            'documentation' => $this->l('Documentation'),
            'tutorials' => $this->l('Tutorials'),
            'support' => $this->l('Support'),
            'url' => $this->l('Source shop URL:'),
            'hint' => $this->l('Provide Source shop URL. Simply copy the URL of Source shop from the browser\'s address bar.'),
            'next' => $this->l('Connect'),
            'demo_mode' => false,
            'demo_url' => '',
        );

        //            step 2
        $output['step_2'] = array('header' => $this->l('Configuration'),
            'header_description' => $this->l('After selecting entities and mapping Source shop data you can start your automated migration process. 1-Click Upgrade gives you the opportunity to perform migration instantly via auto settings or you can go to advanced settings.'),
            'step_title' => $this->l('Source shop connected.'),
            'migration_info' => $this->l('Migration Data'),
            'migration_info_all' => $this->l('View All'),
            'more_options' => $this->l('Advanced Settings'),
            'multi_stores' => $this->l('Mapping Multistores'),
            'single_store' => $this->l('Mapping Store'),
            'currencies' => $this->l('Mapping Currencies'),
            'languages' => $this->l('Mapping Languages'),
            'customer_groups' => $this->l('Mapping Customer Groups'),
            'order_status' => $this->l('Mapping Order Statuses'),
            'entity' => $this->l('Entities to Migrate'),
            'entity_selected' => $this->l('Selected Entities to Migrate'),
            'entity_1click' => $this->l('Auto-selected entities to migrate'),
            'taxes' => $this->l('Taxes'),
            'manufactures' => $this->l('Manufactures'),
            'categories' => $this->l('Categories'),
            'carriers' => $this->l('Carriers'),
            'cart_rules' => $this->l('Cart rules'),
            'orders' => $this->l('Orders'),
            'message_threads' => $this->l('Message threads'),
            'cms' => $this->l('CMS'),
            'seo' => $this->l('SEO'),
            'products' => $this->l('Products'),
            'accessories' => $this->l('Accessories'),
            'catalog_price_rules' => $this->l('Catalog rules'),
            'employees' => $this->l('Employees'),
            'customers' => $this->l('Customers'),

            'additional' => $this->l('Additional Options:'),
            'additional_selected' => $this->l('Selected Additional Options'),
            'additional_1click' => $this->l('Auto-selected additional options'),
            'keep_id' => $this->l('Keep ID'),
            'keep_id_text' => $this->l('This option keeps IDs of Source shop entity data while migrating to Target shop (new shop). In a situation where this option is not chosen, the entity IDs in Target shop will differ from the ones in Source shop.'),
            'recent_data' => $this->l('Recent Data Migration'),
            'recent_data_text' => $this->l('You have new entities on your Source shop (old shop) and you want to make the migration to Target shop (new shop) by keeping entities migration process in order and not duplicating data. By choosing this option, your recent data (products, customers, orders) will be migrated from your Source shop to Target shop.'),
            'clean_data' => $this->l('Clear Current Data'),
            'clean_data_text' => $this->l('This option allows you to delete the current data (products, categories, etc.) in the Target shop (new shop) automatically before migrating data from Source shop.'),
            'speed' => $this->l('Migration Speed'),
            'sped_advice' => $this->l('Item Count per Request'),
            'low' => $this->l('Low'),
            'medium' => $this->l('Medium'),
            'high' => $this->l('High'),
            'sped_text' => $this->l('Select slow migration count, if your shop(s) is hosted on shared hosting or your server returns this error page: \'Internal Server Error\'.'),
            'back' => $this->l('Back'),
            'unselect_all' => $this->l('Un-select All'),
            'select_all' => $this->l('Select All'),
            'start_migration' => $this->l('Start Migration'),
            'one_click_upgrade' => $this->l('1-Click Upgrade'),
            'auto_setting_modal_header' => $this->l('Confirmation'),
            'auto_setting_modal_header_description' => $this->l('1-Click Upgrade gives you the opportunity for an instant automated migration process.'),
            'demo_mode' => false
        );

        //            step 3
        $output['step_3'] = array('header' => $this->l('Migration'),
            'header_description' => $this->l('This is an automated migration process. Data is being migrated according to selected entities and matched Source shop data.'),
            'play' => $this->l('Play'),
            'pause' => $this->l('Pause'),
            'stop' => $this->l('Stop'),
            'step_title' => $this->l('Migration Process'),
            'step_description' => $this->l('Entities are being automatically migrated in order and migration time is being calculated approximately according to speed of entities, amount being migrated, and server performance.'),
            'fatal_error' => $this->l('Something went wrong! Please activate debug mode to see errors. Don\'t hesitate to contact our support team.'),
            'migration_status' => $this->l('Migration Status'),
            'error_message_header' => $this->l('Message'),
            'header_stop_modal' => $this->l('Attention'),
            'description_stop_modal' => $this->l('If migration is stopped, the migration process will be lost. You will need to start migration process again.'),
            'continue_button' => $this->l('Continue'),
            'stop_button' => $this->l('Stop Migration'),
        );
        
        //            step 3
        $output['step_4'] = array('header' => $this->l('Reporting'),
            'fourth_header1' => $this->l('Well done!'),
            'fourth_header2' => $this->l('Migration process completed.'),
            'information' => $this->l('There are steps you can take to regenerate images.'),
            'fourth_header_description' => $this->l('Please check report and take a tour of new shop.  Due to PrestaShop thumbnail settings, some images may not be visible. Don\'t hesitate to contact our customer support if you need assistance.'),

            'link_1_description' =>  $this->l('Click here to navigate thumbnail settings.'),
            'link_href' => $report_image_configuration_url,

            'link_2_description' => $this->l('Open Thumbnail Settings'),
            'migration_report' => $this->l('Migration Report'),
            'report_download' => $this->l('Download Report'),
            'new_migration' => $this->l('New Migration'),
            'enjoy_new_shop' => $this->l('Enjoy Your New Shop!'),
            'enjoy_new_shop_url' => $this->context->link->getPageLink('index', true),
            'report_download_link' => $report_download_url
        );

        //            step 3
        $output['loading'] = array(
            'first_step_header' => $this->l('- Waiting for connection to Source shop.'),
            'first_step_description' => $this->l('1 of 2: Source shop connecting.'),
            'first_step_error' => $this->l('Connection Error!'),
            'first_step_done' => $this->l('2 of 2: Preparing configuring page.'),
            'second_step_header' => $this->l('- Preparing the migration process.'),
            'second_step_description1' => $this->l(''),
        );

        return $output;
    }
    
    /**
     * Return migration state
     */
    public function isPaused()
    {
        $value  = Db::getInstance()->getValue('SELECT value FROM `' . _DB_PREFIX_ . 'configuration` WHERE name=\'migrationpro_pause\'');
        if ($value) {
            if ($value == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
