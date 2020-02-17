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
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/loggers/MigrationProDBWarningLogger.php');
require_once(_PS_MODULE_DIR_ . 'migrationpro/classes/loggers/MigrationProDBErrorLogger.php');

class AdminMigrationProController extends AdminController
{
    // --- response vars:
    public $errors;
    protected $response;
    // --- request vars:
    protected $stepNumber;
    protected $toStepNumber;
    // -- dynamic vars:
    protected $forceIds = true;
    protected $ps_validation_errors = true;
    protected $truncate = false;
    // --- source cart vars:
    protected $url_cart;
    protected $server_path = '/modules/migrationproserver/server.php';
    //    protected $server_path = '/migration_pro/server.php';
    protected $token_cart;
    protected $cms;
    protected $image_category;
    protected $image_carrier;
    protected $image_product;
    protected $image_manufacturer;
    protected $image_supplier;
    protected $image_employee;
    protected $table_prefix;
    protected $version;
    protected $charset;
    protected $blowfish_key;
    protected $cookie_key;
    protected $mapping;
    protected $languagesForQuery;
    protected $speed;
    protected $module;
    // --- helper objects
    protected $query;
    protected $client;
    protected $recent_data;
    private $autoMap;
    private $time_remapping;

    public function __construct()
    {

        $this->display = 'edit';
        parent::__construct();
        $this->controller_type = 'moduleadmin';
        //instead of AdminController’s admin
        $tab = new Tab($this->id);
        // an instance with your tab is created; if the tab is not attached to the module, the exception will be thrown
        if (!$tab->module) {
            throw new PrestaShopException('Admin tab ' . get_class($this) . ' is not a module tab');
        }
        $this->module = Module::getInstanceByName($tab->module);
        if (!$this->module->id) {
            throw new PrestaShopException("Module {$tab->module} not found");
        }
        // handle when update module to the version 6.x
        if (!Configuration::get('migrationpro_new_version')) {
            $this->module->registerHook('backOfficeFooter');
            Configuration::updateValue('migrationpro_new_version', 1);
        }
        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminMigrationPro'));
        $this->stepNumber = (int)Tools::getValue('step_number');
        $this->toStepNumber = (int)Tools::getValue('to_step_number');
        $this->initParams();
        if ($this->stepNumber > 1 || self::isEmpty($this->stepNumber)) {
            $this->initHelperObjects();
        }
        if (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP) {
            $allShops = Shop::getCompleteListOfShopsID();
            Shop::setContext(Shop::CONTEXT_SHOP, $allShops[0]);
        }

        $this->time_remapping = new MigrationProTimeRemapping();
    }

    private function initHelperObjects()
    {
        $this->mapping = MigrationProMapping::listMapping(true, true);
        if (isset($this->mapping['mapping']['languages'])) {
            // -- unset language where value = 0
            if (($key = array_search(0, $this->mapping['mapping']['languages'])) !== false) {
                unset($this->mapping['mapping']['languages'][$key]);
            }
            $keys = array_keys($this->mapping['mapping']['languages']);
            $this->languagesForQuery = implode(',', $keys);
        }
        if (!self::isEmpty($this->url_cart) && !self::isEmpty($this->token_cart)) {
            $this->client = new EDClient($this->url_cart . $this->server_path, $this->token_cart);
            if (Configuration::get($this->module->name . '_debug_mode')) {
                $this->client->debugOn();
            } else {
                $this->client->debugOff();
            }
        }
        $this->query = new EDQuery();
        $this->query->setVersion($this->version);
        $this->query->setCart($this->cms);
        $this->query->setPrefix($this->table_prefix);
        $this->query->setLanguages($this->languagesForQuery);
        $this->query->setRowCount($this->speed);
        $this->query->setRecentData($this->recent_data);
    }

    // --- request processes
    public function postProcess()
    {
        parent::postProcess();
    }

    public function clearSmartyCache()
    {
        Tools::enableCache();
        Tools::clearCache($this->context->smarty);
        Tools::restoreCacheSettings();
    }

    public function ajaxProcessGenerateZip()
    {
        if (!Configuration::get('migrationpro_token')) {
            $token = md5(Configuration::get('PS_SHOP_DOMAIN') . end(explode('\\', _PS_ADMIN_DIR_)));
            Configuration::updateValue('migrationpro_token', $token);
        }
        $token =  Configuration::get('migrationpro_token');
        $txt_file = Tools::file_get_contents(_PS_MODULE_DIR_ . '/migrationpro/views/templates/admin/server.tpl');

        $txt_file = str_replace("[[[[[[sample_token]]]]]]]", $token, $txt_file);
        $txt_file = str_replace("[[[[[[old_domain]]]]]]]", Configuration::get('PS_SHOP_DOMAIN'), $txt_file);
        file_put_contents(_PS_MODULE_DIR_ . '/migrationpro/assets/server.php', $txt_file);
        $zip = new ZipArchive;

        if ($zip->open(_PS_MODULE_DIR_ . 'migrationpro/assets/migrationpro_bridge_connector_module.zip')) {
            // Add file to the zip file
            $zip->addFile(_PS_MODULE_DIR_ . '/migrationpro/assets/server.php', 'migrationproserver/server.php');
            // All files are added, so close the zip file.
            $zip->close();
        }
    }

    /**
     * Return report file
     */
    public function processDownloadReport()
    {
        $attachment_location =  Configuration::get('migrationpro_module_path') . "classes/loggers/warning_logs.txt";
        // Add report log file if not exists
        if (!file_exists($attachment_location)) {
            $report_file = fopen($attachment_location, "w") or die("Have not permission for create 'Report log' file!");
            $empty_log_text = $this->module->l("All Your data successfully validated!", 'adminmigrationprocontroller');
            fwrite($report_file, $empty_log_text);
            fclose($report_file);
        }
        // Re-check existing report log file
        if (file_exists($attachment_location)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public");
            header("Content-Type: application/txt");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:".filesize($attachment_location));
            // [For PrestaShop Team] - header used  for  set response header attributes, but not redirect
            header("Content-Disposition: attachment; filename=migration_pro_report.txt");
            readfile($attachment_location);
            die();
        } else {
            die("Error: File not found.");
        }
    }

    public function ajaxProcessValidateStep()
    {
        if (!$this->tabAccess['edit']) {
            $this->errors[] = EDImport::displayError($this->module->l('You do not have permission to use this wizard.', 'adminmigrationprocontroller'));
        } else {
            // -- reset old data and response url to start new migration
            $this->response['pause'] = $module_path = Configuration::get($this->module->name . '_pause');
            Configuration::updateValue($this->module->name . '_pause', false);

            if (Tools::getValue('to_step_number') >= Tools::getValue('step_number')) {
                $this->validateStepFieldsValue();
            } elseif (Tools::getValue('to_step_number') < Tools::getValue('step_number')) {
                if ($this->truncateModuleTables()) {
                    $this->errors[] = $this->module->l('Access to truncate module tables is denied! Please contact our support team!', 'adminmigrationprocontroller');
                }
                $this->response['step_form'] = $this->module->jsonStepOne();
            }
        }
        if (count($this->errors)) {
            $this->response['has_error'] = true;
            $this->response['errors'] = $this->errors;
        } else {
            $this->response['has_error'] = false;
            $this->response['errors'] = null;
        }
        if (count($this->warnings)) {
            $this->response['has_warning'] = true;
            $this->response['warnings'] = $this->warnings;
        } else {
            $this->response['has_warning'] = false;
            $this->response['warnings'] = null;
        }
        
        if (count($this->warnings) || count($this->errors)) {
            $next_step = Tools::getValue('step_number');
        } else {
            $next_step = Tools::getValue('to_step_number');
        }
        Configuration::updateValue($this->module->name . '_step_status', $next_step);
        die(Tools::jsonEncode($this->response));
    }

    // --- validate functions
    private function validateStepFieldsValue()
    {
        if ($this->stepNumber == 1) {
            // validate url
            if ($this->module->translate()['step_1']['demo_mode']) {
                $source_shop_url = $this->module->translate()['step_1']['demo_url'];
            } else {
                $source_shop_url = Tools::getValue('source_shop_url');
            }
            
            if (!$source_shop_url) {
                $this->errors[] = $this->module->l('Source shop URL is required.', 'adminmigrationprocontroller');
            } elseif (!Validate::isAbsoluteUrl($source_shop_url)) {
                $this->errors[] = $this->module->l('Please enter a valid source PrestaShop URL.', 'adminmigrationprocontroller');
            } else {
                $this->checkConnectorInTarget($source_shop_url);
            }

            if ($this->truncateModuleTables()) {
                $this->errors[] = $this->module->l('Access to truncate module tables is denied! Please contact our support team!', 'adminmigrationprocontroller');
            }
            if (self::isEmpty($this->errors)) {
                $sourceURL = $source_shop_url . $this->server_path;
                $this->client = new EDClient($sourceURL, Configuration::get('migrationpro_token'));
                if (Configuration::get($this->module->name . '_debug_mode')) {
                    $this->client->debugOn();
                } else {
                    $this->client->debugOff();
                }
                if ($this->client->check()) {
                    $content = $this->client->getContent();
                    if (isset($content['cms']) && !self::isEmpty($content['cms'])) {
                        $this->saveParamsToConfiguration($content);
                        $this->initHelperObjects();
                        if ($this->requestToCartDetails()) {
                            $map = array();
                            // Get source data counts by entity type
                            //Create entities list as selected all
                            $map['entity']['taxes'] = array("1");
                            $map['entity']['manufactures'] =  array("1");
                            $map['entity']['categories'] = array("1");
                            $map['entity']['carriers'] =  array("1");
                            $map['entity']['catalog_price_rules'] =  array("1");
                            $map['entity']['employees'] =  array("1");
                            $map['entity']['products'] =  array("1");
                            $map['entity']['customers'] =  array("1");
                            $map['entity']['cart_rules'] = array("1");
                            $map['entity']['cart_rules'] = array("1");
                            $map['entity']['orders'] = array("1");
                            $map['entity']['orders'] = array("1");
                            $map['entity']['cms'] =  array("1");
                            $map['entity']['seo'] = array("1");
                            // Create process for second step
                            $this->createProcess($map);

                            $this->response['step_two'] = $this->module->jsonStepTwo();
                            $this->time_remapping->setMappingTime();
                        }
                    } else {
                        $context = Context::getContext();
//                        $module_path = Configuration::get('migrationpro_module_path');
                        // $context->smarty->assign('sourceURL', $sourceURL);
//                        $error_msg = $context->smarty->fetch($module_path . 'views/templates/admin/connection_error.tpl'); // FARID will cahnge new file
                            $this->errors = array($this->module->l('Connection with source shop was not established. Try these troubleshooting options:', 'adminmigrationprocontroller'),
                            $this->module->l('* Check the bridge connector module.', 'adminmigrationprocontroller').' <a href=\''.$sourceURL.'\' target=\'_blank\'>'.$this->module->l(' (Test now)', 'adminmigrationprocontroller').'</a>',
                            $this->module->l('* If you enabled an SSL certificate on your Source shop (old shop), please try with an HTTPS protocol.', 'adminmigrationprocontroller'),
                            $this->module->l('* Try URL with or without “www” prefix. Check your Apache or Nginx server redirection.', 'adminmigrationprocontroller'),
                            $this->module->l('* Disable maintenance mode or add (new) Target PrestaShop server IP address.', 'adminmigrationprocontroller'));
                    }
                } else {
                    $error_msg = $this->client->getMessage();
                    if (self::isEmpty($error_msg)) {
                        $context = Context::getContext();
//                        $module_path = Configuration::get('migrationpro_module_path');
                        $context->smarty->assign('sourceURL', $sourceURL);
//                        $error_msg = $context->smarty->fetch($module_path . 'views/templates/admin/connection_error.tpl');  // FARID will cahnge new file
                        $this->errors = array($this->module->l('Connection with source shop was not established. Try these troubleshooting options:', 'adminmigrationprocontroller'),
                        $this->module->l('* Check the bridge connector module.', 'adminmigrationprocontroller').' <a href=\''.$sourceURL.'\' target=\'_blank\'>'.$this->module->l(' (Test now)', 'adminmigrationprocontroller').'</a>',
                        $this->module->l('* If you enabled an SSL certificate on your Source shop (old shop), please try with an HTTPS protocol.', 'adminmigrationprocontroller'),
                        $this->module->l('* Try URL with or without “www” prefix. Check your Apache or Nginx server redirection.', 'adminmigrationprocontroller'),
                        $this->module->l('* Disable maintenance mode or add (new) Target PrestaShop server IP address.', 'adminmigrationprocontroller'));
                    } else {
                        $this->errors[] = EDImport::displayError($this->module->l('Please check the URL ', 'adminmigrationprocontroller') . ' - ' . $this->client->getMessage());
                    }
                }
            }
        } elseif ($this->stepNumber == 2) {
            $reset = Tools::getValue('reset') === 'true' ? true : false;
            if ($reset) {
                $map = array();
                /* Begin auto map */
                $auto_map = Tools::getValue('auto_map') === 'true'? true: false;
                $map['mapping'] = Tools::getValue('map');

                $map['entity'] = Tools::getValue('entity');
                $map['additional'] = Tools::getValue('additional');
                $map['speed'] = Tools::getValue('speed');
                if ($auto_map) {
                    //Load map data from source server
                    $this->client->serializeOn();
                    $this->client->setPostData($this->query->getOneClickMigrationData());
                    if ($this->client->query()) {
                        $mapData = $this->client->getContent();

                        if ($mapData) {
                            $this->autoMap = new MigrationProMappingCreator($mapData);
                            $this->autoMap->startAutoMapping();
                        }
                    }
                    $map['mapping'] = $this->autoMap->getMappingData();
                    $map['additional']['clear_data'][0] = 1;
                    $map['additional']['keep_id'][0] = 1;
                    if ($map['additional']['migrate_recent_data']) {
                        $map['additional']['migrate_recent_data'][0] = 0;
                    }
                }

                /* End auto map */

                $languageSumValue = array_sum($map['mapping']['languages']);
                $languageDiffResArray = array_diff_assoc($map['mapping']['languages'], array_unique($map['mapping']['languages']));
                if (!($languageSumValue > 0 && self::isEmpty($languageDiffResArray))  || isset($map['mapping']['languages']['undefined'])) {
                    $this->errors[] = $this->module->l('Select a different language for each Source language.', 'adminmigrationprocontroller');
                }

                $shopSumValue = array_sum($map['mapping']['multi_shops']);
                $shopDiffResArray = array_diff_assoc($map['mapping']['multi_shops'], array_unique($map['mapping']['multi_shops']));
                if (!($shopSumValue > 0 && self::isEmpty($shopDiffResArray)) || isset($map['mapping']['multi_shops']['undefined'])) {
                    $this->errors[] = $this->module->l('Select a different shop for each Source shop.', 'adminmigrationprocontroller');
                }
                Configuration::deleteByName('migrationpro_last_migrated_cat_id');
                Configuration::deleteByName('migrationpro_last_migrated_parent_id');
            }
            if (self::isEmpty($this->errors)) {
                if ($reset) {
                    $this->initHelperObjects();
                    Configuration::updateValue($this->module->name . '_ps_validation_errors', $map['additional']['validation_errors']);
                    Configuration::updateValue($this->module->name . '_clear_data', $map['additional']['clear_data']);
                    Configuration::updateValue($this->module->name . '_migrate_recent_data', $map['additional']['migrate_recent_data']);
                    Configuration::updateValue($this->module->name . '_forceIds', $map['additional']['keep_id']);
                    Configuration::updateValue($this->module->name . '_query_row_count', $map['speed']);
                    if ($this->createMapping($map) && $this->createProcess($map)) {
                        $this->saveMappingValues(MigrationProMapping::listMapping(true, true));
                        // turn on allow html iframe on
                        if (!Configuration::get('PS_ALLOW_HTML_IFRAME')) {
                            Configuration::updateValue('PS_ALLOW_HTML_IFRAME', 1);
                            Configuration::updateValue($this->module->name . '_allow_html_iframe', 1);
                        }
                    }
                }
                $this->response['step_form'] = $this->module->jsonStepThree();

                $this->response['time_remaining'] = $this->time_remapping->getTimeRemapping();
            }
        }
    }


    public function ajaxProcessClearCache()
    {
        if (Tools::getValue('clear_cache')) {
            ini_set('max_execution_time', 0);
            Tools::clearSmartyCache();
            Tools::clearXMLCache();
            Media::clearCache();
            Tools::generateIndex();
            Search::indexation(true);

            //Clear  temporary directory
            $path = _PS_TMP_IMG_DIR_ . '/mp_temp_dir';
            array_map('unlink', glob("$path/*.*"));

            $this->response['has_error'] = false;
            $this->response['has_warning'] = false;

            if (count($this->errors)) {
                $this->response['has_error'] = true;
                $this->response['errors'] = $this->errors;
            }
            if (count($this->warnings)) {
                $this->response['has_warning'] = true;
                $this->response['warnings'] = $this->warnings;
            }
            die(Tools::jsonEncode($this->response));
        }
    }

    public function ajaxProcessDebugOn()
    {
        if (Tools::getValue('turn') == 1) {
            Configuration::updateValue($this->module->name . '_debug_mode', 1);
        } else {
            Configuration::updateValue($this->module->name . '_debug_mode', 0);
        }
    }

    public function ajaxProcessSpeedUp()
    {
        if (!empty(Tools::getValue('speed'))) {
            Configuration::updateValue($this->module->name . '_query_row_count', Tools::getValue('speed'));
        }
    }

    public function ajaxProcessImportProcess($die = true)
    {

        $this->response = array('has_error' => false, 'has_warning' => false);
        $status = Tools::getValue('status');
        if (isset($status) && $status == 'pause') {
            Configuration::updateValue($this->module->name . '_pause', true);
        } else if (isset($status) && $status == 'contiune') {
            Configuration::updateValue($this->module->name . '_pause', false);
        }
        if (!Configuration::get($this->module->name . '_pause')) {
//             $this->response['pause'] = Configuration::get($this->module->name . '_pause');
// //            die(Tools::jsonEncode($this->response));
//         } else 
        // {
            
            if (!$this->tabAccess['edit']) {
                $this->errors[] = EDImport::displayError($this->module->l('You do not have permission to use this wizard.', 'adminmigrationprocontroller'));
            } else {
                $activeProcess = MigrationProProcess::getActiveProcessObject();
                if (Validate::isLoadedObject($activeProcess)) {
                    $this->query->setOffset($activeProcess->imported);
                    if ($activeProcess->imported == 0) {
                        if ($this->truncate && !$this->truncateTables($activeProcess->type)) {
                            $this->errors[] = $this->module->l('Can\'t clear current data on Target shop.', 'adminmigrationprocontroller') . Db::getInstance()->getMsgError();
                        }
                        $activeProcess->time_start = date('Y-m-d H:i:s', time());
                        $activeProcess->save();
                    }

                    // Set time remapping active type
                    $this->time_remapping->setCurrentType($activeProcess->type);

                    if ($activeProcess->type == 'taxes') {
                        $this->importTaxes($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'manufacturers') {
                        $this->importManufacturers($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'categories') {
                        $this->importCategories($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'carriers') {
                        $this->importCarriers($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'products') {
                        $this->importProducts($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'accessories') {
                        $this->importAccessories($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'catalog price rules') {
                        $this->importCatalogPriceRules($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'employees') {
                        $this->importEmployees($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'customers') {
                        $this->importCustomers($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'cart rules') {
                        $this->importCartrules($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'orders') {
                        $this->importOrders($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'message_threads') {
                        $this->importMessageThreads($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'cms') {
                        $this->importCMS($activeProcess);
                        $this->clearSmartyCache();
                    } elseif ($activeProcess->type == 'seo') {
                        $this->importMeta($activeProcess);
                        $this->clearSmartyCache();
                    }
                } else {
                    die('no process.');
                }
            }

            
            if (MigrationProProcess::calculateImportedDataPercent() == 100) {
                $this->response['report'] = $this->module->jsonStepFour();
                // turn off allow html iframe feature
                if (Configuration::get($this->module->name . '_allow_html_iframe')) {
                    Configuration::updateValue('PS_ALLOW_HTML_IFRAME', 0, null, 0, 0);
                    Configuration::updateValue($this->module->name . '_allow_html_iframe', 0, null, 0, 0);
                }
            }
        }
            // Get time remapping value
        $this->response['time_remaining'] = $this->time_remapping->getTimeRemapping();
        $this->response['percent'] = $this->module->jsonStepThree();
        $this->response['pause'] = $this->module->isPaused();
        if (count($this->errors)) {
            $this->response['has_error'] = true;
            $this->response['errors'] = $this->errors;
        }
        if (count($this->warnings)) {
            $this->response['has_warning'] = true;
            $this->response['warnings'] = $this->warnings;
        }
        if ($die) {
            $this->response['pause'] = $this->module->isPaused();
            // Configuration::get($this->module->name . '_pause');
            die(Tools::jsonEncode($this->response));
        } else {
            return $this->response;
        }
    }

    // --- import functions
    private function importTaxes($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->taxRulesGroup());
        if ($this->client->query()) {
            $taxRulesGroups = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->taxes($taxRulesGroups);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importManufacturers($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->manufactures());
        if ($this->client->query()) {
            $manufacturers = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module);
            $import->setImagePath($this->image_manufacturer);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->manufacturers($manufacturers);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importCategories($process)
    {
        //@TODO find fix for PS 1.4 for category id 2 WHERE is ID 2 standart category from list
        $this->client->serializeOn();
        $this->client->setPostData($this->query->category());
        if ($this->client->query()) {
            $categories = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setImagePath($this->image_category);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->categories($categories);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importCarriers($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->carrier());
        if ($this->client->query()) {
            $carriers = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setImagePath($this->image_carrier);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->carriers($carriers);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }
    //    private function importWarehouse($process,$recent_data)
    //    {
    //        $this->client->setPostData($this->query->warehouses());
    //        if ($this->client->query()) {
    //            $warehouses = $this->client->getContent();
    //
    //            $this->client->serializeOn();
    //            $this->client->setPostData($this->query->warehousesSqlSecond(self::getCleanIDs($warehouses, 'id_warehouse'), self::getCleanIDs($warehouses, 'id_address')));
    //            if ($this->client->query()) {
    //                $warehousesAdditionalSecond = $this->client->getContent();
    //                $this->client->setPostData($this->query->countryState(self::getCleanIDs($warehousesAdditionalSecond['address'], 'id_country'), self::getCleanIDs($warehousesAdditionalSecond['address'], 'id_state')));
    //                if ($this->client->query()) {
    //                    $countryState = $this->client->getContent();
    //                    $this->client->serializeOff();
    //                    $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
    //                    $import->setPsValidationErrors($this->ps_validation_errors);
    //                    $import->setRecentData($recent_data);
    //                    $import->warehouses($warehouses, $warehousesAdditionalSecond, $countryState);
    //                    $this->errors = $import->getErrorMsg();
    //                    $this->warnings = $import->getWarningMsg();
    //                    $this->response = $import->getResponse();
    //                } else {
    //                    $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ') . $this->client->getMessage();
    //                }
    //            }
    //        } else {
    //            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ') . $this->client->getMessage();
    //        }
    //    }
    private function importProducts($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->product());
        if ($this->client->query()) {
            $products = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setImagePath($this->image_product);
            $import->setImageSupplierPath($this->image_supplier);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->products($products);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importAccessories($process)
    {
        $this->client->setPostData($this->query->accessories());
        if ($this->client->query()) {
            $accessories = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->accessories($accessories);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importCatalogPriceRules($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->specificPriceRule());
        if ($this->client->query()) {
            $specificPriceRules = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->catalogPriceRules($specificPriceRules);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importEmployees($process)
    {
        $this->client->setPostData($this->query->employee());
        if ($this->client->query()) {
            $employees = $this->client->getContent();

            $import = new EDImport($process, $this->version, $this->url_cart, false, $this->module, $this->client, $this->query);
            $import->setImagePath($this->image_employee);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->employees($employees);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importCustomers($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->customers());
        if ($this->client->query()) {
            $customers = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->customers($customers);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importCartrules($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->cartRule());
        if ($this->client->query()) {
            $cartRules = $this->client->getContent();

            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->cartrules($cartRules);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importOrders($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->order());
        if ($this->client->query()) {
            $orders = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->orders($orders);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importMessageThreads($process)
    {
        $customerThreads = null;
        $this->client->serializeOn();
        $this->client->setPostData($this->query->customerThreads());
        if ($this->client->query()) {
            $customerThreads = $this->client->getContent();
            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->customerMessages($customerThreads);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importCMS($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->cms());
        if ($this->client->query()) {
            $cmses = $this->client->getContent();

            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->cmses($cmses);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    private function importMeta($process)
    {
        $this->client->serializeOn();
        $this->client->setPostData($this->query->meta());
        if ($this->client->query()) {
            $metas = $this->client->getContent();

            $import = new EDImport($process, $this->version, $this->url_cart, $this->forceIds, $this->module, $this->client, $this->query);
            $import->setPsValidationErrors($this->ps_validation_errors);
            $import->metas($metas);
            $this->errors = $import->getErrorMsg();
            $this->warnings = $import->getWarningMsg();
            $this->response = $import->getResponse();
        } else {
            $this->errors[] = $this->module->l('Can\'t execute query to Source PrestaShop. ', 'adminmigrationprocontroller') . $this->client->getMessage();
        }
    }

    // --- Internal helper methods:
    public function saveMappingValues($mapping_values)
    {
        if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_save_mapping`')) {
            foreach ($mapping_values as $mappinggroup => $mappingType) {   //$mappinggroup = mapping , entity, additional
                foreach ($mappingType as $mappingType => $mappingObject) {   //  $mappingType = languages, order_states
                    foreach ($mappingObject as $source_id => $target_id) {
                        $mapping = new MigrationProSaveMapping();
                        $mapping->group = $mappinggroup;
                        $mapping->type = $mappingType;
                        $mapping->source_id = $source_id;
                        $mapping->source_name = $mappingType;
                        $mapping->mapping = $target_id;
                        if (!$mapping->save()) {
                            $this->errors[] = $this->module->l('Can\'t save to database mapping information.', 'adminmigrationprocontroller');
                        }
                    }
                }
            }
        }
    }

    private function truncateModuleTables()
    {
        $res = Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_data`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_process`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_mapping`');
        if (!$res) {
            return false;
        }
    }

    private function createMapping($maps)
    {
        $res = true;
 
        if ($this->autoMap) {
            Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_mapping`', 'adminmigrationprocontroller');
        }
        
        foreach ($maps as $mapkey => $mapval) {   //   $mapkey =   mapping, adittional, entity
            foreach ($mapval as $mapval_key => $mapval_val) {    //$mapval_key = multi_shops, currencies , order_states, tax, product, recent data ...
                foreach ($mapval_val as $key => $value) {
                    if ($mapval_key == 'currencies') {
                        $a = 9;
                    }
                    if ($this->autoMap) {
                        $mapping = new MigrationProMapping();
                        $mapping->source_name = $this->autoMap->getMappingText($mapval_key, $key);
                    } else {
                        $mapping_id = $this->getMapId($mapval_key, $key);
                        $mapping = new MigrationProMapping($mapping_id);
                    }
                    $mapping->group = $mapkey;
                    $mapping->type = $mapval_key;
                    $mapping->source_id = ($key != 'undefined') ? $key : 0;
                    $mapping->mapping =  ($value != 'undefined') ? $value : null;
                    $res &= $mapping->save();
                }
            }
        }
        return $res;
    }

    private function getMapId($type, $source_id)
    {
        $id_mapping = Db::getInstance()->getValue('SELECT id_mapping FROM ' . _DB_PREFIX_ .
                      'migrationpro_mapping WHERE source_id = \''. pSQL($source_id) .'\' AND type =\'' . pSQL($type) . '\';');
        return $id_mapping;
    }

    private function createProcess($map)
    {
        $res = Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_process`');
        $res &= Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_data`');
        MigrationProDBErrorLogger::removeErrorLogs();
        MigrationProDBWarningLogger::removeWarningLogs();
        MigrationProDBWarningLogger::removeLogFile();
        $this->client->setPostData($this->query->getCountInfo($map));
        $this->client->serializeOn();
        if ($this->client->query()) {
            $processes = $this->client->getContent();
            foreach ($processes as $processKey => $processCount) {
                if (isset($processCount[0]['c']) && !self::isEmpty($processCount[0]['c'])) {
                    $process = new MigrationProProcess();
                    if ($processKey == 'catalog_price_rules') {
                        $process->type = 'catalog price rules';
                    } else if ($processKey == 'cart_rules') {
                        $process->type = 'cart rules';
                    } else {
                        $process->type = $processKey;
                    }
                    $process->total = (int)$processCount[0]['c'];
                    $process->imported = 0;
                    $process->id_source = 0;
                    $process->error = 0;
                    $process->error_count = 0;
                    $process->point = 0;
                    $process->time_start = 0;
                    $process->finish = 0;
                    $res &= $process->add();
                }
            }
            return $res;
        }
        return false;
    }

    private function saveParamsToConfiguration($content)
    {
        if ($this->module->translate()['step_1']['demo_mode']) {
            $source_shop_url = $this->module->translate()['step_1']['demo_url'];
        } else {
            $source_shop_url = Tools::getValue('source_shop_url');
        }
        
        Configuration::updateValue($this->module->name . '_url', $source_shop_url);
        Configuration::updateValue($this->module->name . '_cms', $content['cms']);
        Configuration::updateValue($this->module->name . '_image_category', $content['image_category']);
        Configuration::updateValue($this->module->name . '_image_carrier', $content['image_carrier']);
        Configuration::updateValue($this->module->name . '_image_product', $content['image_product']);
        Configuration::updateValue($this->module->name . '_image_manufacturer', $content['image_manufacturer']);
        Configuration::updateValue($this->module->name . '_image_supplier', $content['image_supplier']);
        Configuration::updateValue($this->module->name . '_image_employee', $content['image_employee']);
        Configuration::updateValue($this->module->name . '_table_prefix', $content['table_prefix']);
        Configuration::updateValue($this->module->name . '_version', $content['version']);
        Configuration::updateValue($this->module->name . '_charset', $content['charset']);
        Configuration::updateValue($this->module->name . '_blowfish_key', $content['blowfish_key']);
        Configuration::updateValue($this->module->name . '_cookie_key', $content['cookie_key']);
        $this->initParams();
    }

    private function initParams()
    {
        if (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP) {
            //fix the osl php version issue
            $allShops = Shop::getCompleteListOfShopsID();
            Shop::setContext(Shop::CONTEXT_SHOP, $allShops[0]);
        }
        $this->url_cart = Configuration::get($this->module->name . '_url');
        $this->token_cart = Configuration::get($this->module->name . '_token');
        $this->cms = Configuration::get($this->module->name . '_cms');
        $this->image_category = Configuration::get($this->module->name . '_image_category');
        $this->image_carrier = Configuration::get($this->module->name . '_image_carrier');
        $this->image_product = Configuration::get($this->module->name . '_image_product');
        $this->image_manufacturer = Configuration::get($this->module->name . '_image_manufacturer');
        $this->image_supplier = Configuration::get($this->module->name . '_image_supplier');
        $this->image_employee = Configuration::get($this->module->name . '_image_employee');
        $this->table_prefix = Configuration::get($this->module->name . '_table_prefix');
        $this->version = Configuration::get($this->module->name . '_version');
        $this->charset = Configuration::get($this->module->name . '_charset');
        $this->blowfish_key = Configuration::get($this->module->name . '_blowfish_key');
        $this->cookie_key = Configuration::get($this->module->name . '_cookie_key');
        $this->ps_validation_errors = Configuration::get($this->module->name . '_ps_validation_errors');
        $this->truncate = Configuration::get($this->module->name . '_clear_data');
        $this->speed = Configuration::get($this->module->name . '_query_row_count');
        $this->recent_data = Configuration::get($this->module->name . '_migrate_recent_data');
        $this->forceIds = Configuration::get($this->module->name . '_forceIds');
    }

    private function requestToCartDetails()
    {
        // --- get default values from source cart
        $res = false;
        $this->client->setPostData($this->query->getDefaultShopValues());
        $this->client->serializeOn();
        $this->client->query();
        $resultDefaultShopValues = $this->client->getContent();
        $this->query->setVersion($this->version);
        $this->client->setPostData($this->query->getMappingInfo($resultDefaultShopValues['default_lang'][0]['value']));
        $this->client->query();
        $mappingInformation = $this->client->getContent();

        $mapping_value = MigrationProSaveMapping::listMapping(true, true);
        $source_default_lang_id = $resultDefaultShopValues['default_lang'][0]['value'];
        $target_default_lang_id = Configuration::get('PS_LANG_DEFAULT');
        $root_cat = $resultDefaultShopValues['root_home'][0]['root'] ? $resultDefaultShopValues['root_home'][0]['root'] : $resultDefaultShopValues['root_home_multiple_root'][0]['root'];
        $home_cat = $resultDefaultShopValues['root_home'][0]['home'] ? $resultDefaultShopValues['root_home'][0]['home'] : $resultDefaultShopValues['root_home_multiple_root'][0]['home'];
        Configuration::updateValue('migrationpro_source_root_cat', $root_cat);
        Configuration::updateValue('migrationpro_source_home_cat', $home_cat);
        Configuration::updateValue('migrationpro_source_default_lang', $source_default_lang_id);
        Configuration::updateValue('migrationpro_source_max_cat', $resultDefaultShopValues['get_max_cat'][0]['max_cat_id']);
        if (is_array($mappingInformation)) {
            if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'migrationpro_mapping`')) {
                foreach ($mappingInformation as $mappingType => $mappingObject) {
                    foreach ($mappingObject as $value) {
                        $mapping = new MigrationProMapping();
                        $mapping->group = 'mapping';
                        $mapping->type = $mappingType;
                        $mapping->source_id = $value['source_id'];
                        $mapping->source_name = $value['source_name'];
                        if ($mappingType == 'languages' && $value['source_id'] == $source_default_lang_id) {
                            $mapping->mapping = $target_default_lang_id;
                        } else {
                            if (!empty($mapping_value)) {
                                $mapping->mapping = $mapping_value['mapping'][$mappingType][$value['source_id']];
                            }
                        }
                        if (!$mapping->save()) {
                            $this->errors[] = $this->module->l('Can\'t save to database mapping information. ', 'adminmigrationprocontroller');
                        }
                    }
                }
                if (!empty($mapping_value)) {
                    foreach ($mapping_value as $key => $value) {
                        if ($key != 'mapping') {
                            foreach ($value as $k => $val) {
                                $mapping = new MigrationProMapping();
                                $mapping->group = $key;
                                $mapping->type = $k;
                                $mapping->source_id = 0;
                                $mapping->source_name = null;
                                $mapping->mapping = $val[0];
                                if (!$mapping->save()) {
                                    $this->errors[] = $this->module->l('Can\'t save to database mapping information. ', 'adminmigrationprocontroller');
                                }
                            }
                        }
                    }
                }
            } else {
                $this->errors[] = $this->module->l('Can\'t truncate mapping table.', 'adminmigrationprocontroller');
            }
        }
        if (self::isEmpty($this->errors)) {
            return true;
        }
        return false;
    }

    // check the connector bridge module in target shop
    private function checkConnectorInTarget($source_shop_url)
    {
        $check_source_shop_url = rtrim(preg_replace('/https?:\/\/(www\.)?/', '', $source_shop_url), '/').'/'.end(explode('/', str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'])));
        $check_target_shop_url = Configuration::get('PS_SHOP_DOMAIN') . $_SERVER['BASE'];
        if ($check_source_shop_url == $check_target_shop_url) {
            if (Module::getModuleIdByName('migrationproserver') != 0) {
                $this->errors[] = $this->module->l('You need to create a new PrestaShop', 'adminmigrationprocontroller');
                $this->errors[] = $this->module->l('Please follow the module documentation', 'adminmigrationprocontroller');
            } else {
                if (file_exists(_PS_MODULE_DIR_ . 'migrationproserver/server.php')) {
                    if (!unlink(_PS_MODULE_DIR_ . 'migrationproserver/server.php')) {
                        $this->errors[] = $this->module->l('The connector bridge module can\'t be in the target shop.', 'adminmigrationprocontroller');
                        $this->errors[] = $this->module->l('Delete "/modules/migrationproserver" directory from current server and try again.', 'adminmigrationprocontroller');
                    }
                }
            }
        }
    }
    protected function checkMappingIsEmpty($mapping)
    {
        if (self::isEmpty($mapping)) {
            return true;
        }
        return false;
    }

    protected function truncateTables($case)
    {
        $res = true;
        switch ($case) {
            case 'taxes':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_rule`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_rules_group`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'tax_rules_group_shop`');
                break;
            case 'manufacturers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'manufacturer`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'manufacturer_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'manufacturer_shop`');
                foreach (scandir(_PS_MANU_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_MANU_IMG_DIR_ . $d);
                    }
                }
                break;
            case 'categories':
                $res &= Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category`
                        WHERE id_category NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')');
                $res &= Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category_lang`
                        WHERE id_category NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')');
                $res &= Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category_shop`
                        WHERE `id_category` NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')');
                $res &= Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category_group`
                        WHERE `id_category` NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)Configuration::get('PS_ROOT_CATEGORY') . ')');
                $res &= Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'category` AUTO_INCREMENT = 3');
                foreach (scandir(_PS_CAT_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_CAT_IMG_DIR_ . $d);
                    }
                }
                break;
            case 'carriers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'carrier_group`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'carrier_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'carrier_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'carrier_zone`');
                if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'module_carrier`');
                }
                foreach (scandir(_PS_SHIP_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_SHIP_IMG_DIR_ . $d);
                    }
                }
                break;
            case 'warehouse':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse_carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse_product_location`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse_shop`');
                break;
            case 'products':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'feature_product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'category_product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_tag`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'image`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'image_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'image_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price_priority`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'pack`');
                if (count(Db::getInstance()->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'favorite_product\' '))) { //check if table exist
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'favorite_product`');
                }
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attachment`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'accessory`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_country_tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_download`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_group_reduction_cache`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_sale`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_supplier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse_product_location`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_available`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_mvt`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization_field`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization_field_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supply_order_detail`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_impact`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_combination`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_image`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'pack`');
                Image::deleteAllImages(_PS_PROD_IMG_DIR_);
                if (!file_exists(_PS_PROD_IMG_DIR_)) {
                    mkdir(_PS_PROD_IMG_DIR_);
                }
                //                break;
                //            case 'combinations':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_impact`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_group_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_group_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_shop`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_combination`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_image`');
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE`' . _DB_PREFIX_ . 'attribute`');
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE`' . _DB_PREFIX_ . 'attribute_group`');
                } else {
                    $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'attribute`');
                    $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'attribute_group`');
                }
                $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'stock_available` WHERE id_product_attribute != 0');
                //            case 'suppliers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supplier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supplier_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supplier_shop`');
                foreach (scandir(_PS_SUPP_IMG_DIR_) as $d) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
                        unlink(_PS_SUPP_IMG_DIR_ . $d);
                    }
                }
                //                break;
                break;
            case 'catalog price rules':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price_rule`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price_rule_condition_group`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price_rule_condition`');
                break;
            case 'customers':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer_group`');
                //                break;
                //            case 'addresses':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'address`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_product`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_combination`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_country`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_group`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_product_rule`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_rule_shop`');
                break;
            case 'orders':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'orders`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_detail`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_detail_tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_history`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_invoice`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_cart_rule`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_payment`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_invoice_payment`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_invoice_tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_carrier`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer_message`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer_thread`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_message`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_message_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_slip`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_slip_detail`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_slip_detail_tax`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_return`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'order_return_detail`');
                break;
            case 'cms':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_lang`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_shop`');
                $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'cms_category` WHERE id_cms_category != 1');
                $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'cms_category_lang` WHERE id_cms_category != 1');
                $res &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'cms_category_shop` WHERE id_cms_category != 1');
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_block`');
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_block_lang`');
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_block_page`');
                    $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_block_shop`');
                }
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_role`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cms_role_lang`');
                break;
            case 'seo':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'meta`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'meta_lang`');
                break;
            case 'message_threads':
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer_message`');
                $res &= Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customer_thread`');
                break;
        }
        Image::clearTmpDir();
        return $res;
    }

    // --- Static utility methods:
    public static function getCleanIDs($rows, $key)
    {
        $result = array();
        if (is_array($rows) && !self::isEmpty($rows)) {
            foreach ($rows as $row) {
                if (is_numeric($row[$key])) {
                    $result[] = $row[$key];
                } else {
                    $result[] = '\'' . $row[$key] . '\'';
                }
            }
            $result = array_unique($result);
            $result = implode(',', $result);
            return $result;
        } else {
            return 'null';
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
}
