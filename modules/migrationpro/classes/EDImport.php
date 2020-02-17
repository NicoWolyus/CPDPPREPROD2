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

require_once 'EDClient.php';
require_once "loggers/MigrationProLogger.php";
require_once "Validator.php";

class EDImport
{
    const UNFRIENDLY_ERROR = false;
    // --- Objects, Option & response vars:

    private $logger;
    private $validator;

    protected $obj;
    protected $module;
    protected $process;
    protected $client;
    protected $query;
    protected $url;
    protected $force_ids;
    protected $regenerate;
    protected $image_path;
    protected $image_supplier_path;
    protected $version;
    protected $shop_is_feature_active;
    protected $mapping;
    protected $ps_validation_errors = true;
    protected $migrate_recent_data = false;

    protected $error_msg;
    protected $warning_msg;
    protected $response;
    protected $NotFoundImages = array();

    // --- Constructor / destructor:

    public function __construct(
        MigrationProProcess $process,
        $version,
        $url_cart,
        $force_ids,
        Module $module,
        EDClient $client = null,
        EDQuery $query = null
    ) {
        $this->regenerate = false;
        $this->process = $process;
        $this->version = $version;
        $this->url = $url_cart;
        $this->force_ids = $force_ids;
        $this->module = $module;
        $this->client = $client;
        $this->query = $query;
        $this->mapping = MigrationProMapping::listMapping(true, true);
        $this->shop_is_feature_active = Shop::isFeatureActive();
        $this->logger = new MigrationProLogger();
        $this->validator = new Validator();
    }

    // --- Configuration methods:

    public function setImagePath($string)
    {
        $this->image_path = $string;
    }

    public function setImageSupplierPath($string)
    {
        $this->image_supplier_path = $string;
    }

    public function setRecentData($bool)
    {
        $this->migrate_recent_data = $bool;
    }

    public function setPsValidationErrors($bool)
    {
        $this->ps_validation_errors = $bool;
        $this->validator->allowSettingDefaultValue(!$bool);
    }

    public function preserveOn()
    {
        $this->force_ids = true;
    }

    public function preserveOff()
    {
        $this->force_ids = false;
    }

    // --- After object methods:

    public function getErrorMsg()
    {
        return $this->error_msg;
    }


    public function getWarningMsg()
    {
        return $this->warning_msg;
    }

    public function getResponse()
    {
        return $this->response;
    }

    // --- Import methods:

    /**
     * @param $taxRulesGroups
     * @param $taxRules
     * @param $taxLangCountryLangState
     */
    public function taxes($taxRulesGroups)
    {
        // import country
        foreach ($taxRulesGroups['country'] as $country) {
            if ($countryObject = $this->createObjectModel('Country', $country['id_country'])) {
                $countryObject->id_zone = $country['id_zone'];
                $countryObject->id_currency = self::getCurrencyID($country['id_currency']);
                $countryObject->call_prefix = $country['call_prefix'];
                $countryObject->iso_code = $country['iso_code'];
                $countryObject->active = $country['active'];
                $countryObject->contains_states = $country['contains_states'];
                $countryObject->need_identification_number = $country['need_identification_number'];
                $countryObject->need_zip_code = $country['need_zip_code'];
                $countryObject->zip_code_format = $country['zip_code_format'];
                $countryObject->display_tax_label = (isset($country['display_tax_label'])) ? (bool)$country['display_tax_label'] : true;
                //language fields
                foreach ($taxRulesGroups['country_lang'][$country['id_country']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $countryObject->name[$lang['id_lang']] = $lang['name'];
                }
                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $country['id_shop_list']), '');
                $countryObject->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';

                $this->validator->setObject($countryObject);
                $this->validator->checkFields();
                $country_error_tmp = $this->validator->getValidationMessages();
                if ($countryObject->id && Country::existsInDatabase($countryObject->id, 'country')) {
                    try {
                        $res = $countryObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $countryObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Country (ID: %1$s) cannot be saved. %2$s')), (isset($country['id_country']) && !self::isEmpty($country['id_country'])) ? Tools::safeOutput($country['id_country']) : 'No ID', $err_tmp), 'Country');
                } else {
                    self::addLog('Country', $country['id_country'], $countryObject->id);
                }
                $this->showMigrationMessageAndLog($country_error_tmp, 'Country');
            }
        }

        // import state
        foreach ($taxRulesGroups['state'] as $state) {
            if ($stateObject = $this->createObjectModel('State', $state['id_state'])) {
                $stateObject->id_country = self::getLocalID('country', $state['id_country'], 'data');
                $stateObject->id_zone = $state['id_zone'];
                $stateObject->iso_code = $state['iso_code'];
                $stateObject->active = $state['active'];
                $stateObject->name = $state['name'];

                $res = false;
                $err_tmp = '';

                $this->validator->setObject($stateObject);
                $this->validator->checkFields();
                $state_error_tmp = $this->validator->getValidationMessages();
                if ($stateObject->id && State::existsInDatabase($stateObject->id, 'state')) {
                    try {
                        $res = $stateObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $stateObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('State (ID: %1$s) cannot be saved. %2$s')), (isset($state['id_state']) && !self::isEmpty($state['id_state'])) ? Tools::safeOutput($state['id_state']) : 'No ID', $err_tmp), 'State');
                } else {
                    self::addLog('State', $state['id_state'], $stateObject->id);
                }
                $this->showMigrationMessageAndLog($state_error_tmp, 'State');
            }
        }


        #endregion
        $count = 0;
        // import tax
        foreach ($taxRulesGroups['tax'] as $tax) {
            if ($taxObject = $this->createObjectModel('Tax', $tax['id_tax'])) {
                $taxObject->rate = $tax['rate'];
                $taxObject->active = $tax['active'];
                $taxObject->name = $tax['name'];
                if ($this->version >= 1.5) {
                    $taxObject->deleted = $tax['deleted'];
                }
                foreach ($taxRulesGroups['tax_lang'][$tax['id_tax']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $taxObject->name[$lang['id_lang']] = $lang['name'];
                }
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($taxObject);
                $this->validator->checkFields();
                $tax_error_tmp = $this->validator->getValidationMessages();
                if ($taxObject->id && Tax::existsInDatabase($taxObject->id, 'tax')) {
                    try {
                        $res = $taxObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $taxObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Tax (ID: %1$s) cannot be saved. %2$s')), (isset($tax['id_tax']) && !self::isEmpty($tax['id_tax'])) ? Tools::safeOutput($tax['id_tax']) : 'No ID', $err_tmp), 'Tax');
                } else {
                    self::addLog('Tax', $tax['id_tax'], $taxObject->id);
                }
                $this->showMigrationMessageAndLog($tax_error_tmp, 'Tax');
            }
        }
        // import tax rules group
        foreach ($taxRulesGroups['tax_rules_group'] as $taxRulesGroup) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($taxRulesGroupModel = $this->createObjectModel('TaxRulesGroup', $taxRulesGroup['id_tax_rules_group'])) {
                $taxRulesGroupModel->name = $taxRulesGroup['name'];
                if (self::isEmpty($taxRulesGroupModel->date_add) || $taxRulesGroupModel->date_add == '0000-00-00 00:00:00') {
                    $taxRulesGroupModel->date_add = date('Y-m-d H:i:s');
                }
                if (self::isEmpty($taxRulesGroupModel->date_upd) || $taxRulesGroupModel->date_upd == '0000-00-00 00:00:00') {
                    $taxRulesGroupModel->date_upd = date('Y-m-d H:i:s');
                }
                $taxRulesGroupModel->active = $taxRulesGroup['active'];
                $id_shop_list = $this->getChangedIdShop(explode(',', $taxRulesGroup['id_shop_list']), '');
                $taxRulesGroupModel->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($taxRulesGroupModel);
                $this->validator->checkFields();
                $tax_rule_group_error_tmp = $this->validator->getValidationMessages();
                if ($taxRulesGroupModel->id && TaxRulesGroup::existsInDatabase($taxRulesGroupModel->id, 'tax_rules_group')) {
                    try {
                        $res = $taxRulesGroupModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $taxRulesGroupModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Tax Rules Group (ID: %1$s) cannot be saved. %2$s')), (isset($taxRulesGroup['id_tax_rules_group']) && !self::isEmpty($taxRulesGroup['id_tax_rules_group'])) ? Tools::safeOutput($taxRulesGroup['id_tax_rules_group']) : 'No ID', $err_tmp), 'TaxRulesGroup');
                } else {
                    // import tax rules for this group
                    foreach ($taxRulesGroups['tax_rule'][$taxRulesGroup['id_tax_rules_group']] as $taxRule) {
                        if ($taxRuleModel = $this->createObjectModel('TaxRule', $taxRule['id_tax_rule'])) {
                            $taxRuleModel->id_tax_rules_group = $taxRule['id_tax_rules_group'];
                            $taxRuleModel->id_country = $taxRule['id_country'];
                            $taxRuleModel->id_state = $taxRule['id_state'];
                            $taxRuleModel->id_tax = $taxRule['id_tax'];
                            $taxRuleModel->zipcode_from = 0;
                            $taxRuleModel->zipcode_to = 0;
                            $taxRuleModel->behavior = 0;
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($taxRuleModel);
                            $this->validator->checkFields();
                            $tax_rule_error_tmp = $this->validator->getValidationMessages();
                            if ($taxRuleModel->id && TaxRule::existsInDatabase($taxRuleModel->id, 'tax_rule')) {
                                try {
                                    $res = $taxRuleModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $taxRuleModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Tax Rule (ID: %1$s) cannot be saved. %2$s')), (isset($taxRule['id_tax_rule']) && !self::isEmpty($taxRule['id_tax_rule'])) ? Tools::safeOutput($taxRule['id_tax_rule']) : 'No ID', $err_tmp), 'TaxRule');
                            } else {
                                self::addLog('TaxRule', $taxRule['id_tax_rule'], $taxRuleModel->id);
                            }
                            $this->showMigrationMessageAndLog($tax_rule_error_tmp, 'TaxRule');
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        self::addLog('TaxRulesGroup', $taxRulesGroup['id_tax_rules_group'], $taxRulesGroupModel->id);
                    }
                }
                $this->showMigrationMessageAndLog($tax_rule_group_error_tmp, 'TaxRulesGroup');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $manufacturers
     * @param $manufacturersAdditionalSecond
     */
    public function manufacturers($manufacturers)
    {
        //Load images for manufacturers to temporary dir
        $this->loadImagesToLocal($manufacturers['manufactures'], 'id_manufacturer', 'manufacturers', $this->url, $this->image_path);
        $count = 0;
        foreach ($manufacturers['manufactures'] as $manufacturer) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($manufacturerObj = $this->createObjectModel('Manufacturer', $manufacturer['id_manufacturer'])) {
                $manufacturerObj->name = $manufacturer['name'];
                $manufacturerObj->date_add = $manufacturer['date_add'];
                $manufacturerObj->date_upd = $manufacturer['date_upd'];
                $manufacturerObj->active = $manufacturer['active'];
                foreach ($manufacturers['manufactures_lang'][$manufacturer['id_manufacturer']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $manufacturerObj->description[$lang['id_lang']] = $lang['description'];
                    $manufacturerObj->short_description[$lang['id_lang']] = $lang['short_description'];
                    $manufacturerObj->meta_title[$lang['id_lang']] = $lang['meta_title'];
                    $manufacturerObj->meta_description[$lang['id_lang']] = $lang['meta_description'];
                    $manufacturerObj->meta_keywords[$lang['id_lang']] = $lang['meta_keywords'];
                }
                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $manufacturer['id_shop_list']), '');
                $manufacturerObj->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($manufacturerObj);
                $this->validator->checkFields();
                $manufacturer_error_tmp = $this->validator->getValidationMessages();
                if ($manufacturerObj->id && $manufacturerObj->manufacturerExists($manufacturerObj->id)) {
                    try {
                        $res = $manufacturerObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $manufacturerObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Manufacturer (ID: %1$s) cannot be saved. %2$s')), (isset($manufacturer['id_manufacturer']) && !self::isEmpty($manufacturer['id_manufacturer'])) ? Tools::safeOutput($manufacturer['id_manufacturer']) : 'No ID', $err_tmp), 'Manufacturer');
                } else {
                    $url = $this->url . $this->image_path . $manufacturer['id_manufacturer'] . '.jpg';

                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/manufacturers/' . $manufacturer['id_manufacturer'] . '.jpg';

                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($manufacturerObj->id, null, $FilePath, 'manufacturers', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Manufacturer', true);
                    }
                    //@TODO Associate manufacturers to shop
                    self::addLog('Manufacturer', $manufacturer['id_manufacturer'], $manufacturerObj->id);
                }
                $this->showMigrationMessageAndLog($manufacturer_error_tmp, 'Manufacturer');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $categories
     * @param $categoriesAdditionalSecond
     * @param bool $innerMethodCall
     */
    public function categories($categories, $innerMethodCall = false)
    {
        //Load images for categories to temporary dir
        $this->loadImagesToLocal($categories['category'], 'id_category', 'categories', $this->url, $this->image_path);
        $count = 0;
        foreach ($categories['category'] as $category) {
            if ($this->module->isPaused()) {
                break;
            }

            $categories_home_root = array(
                Configuration::get('migrationpro_source_root_cat'),
                Configuration::get('migrationpro_source_home_cat')
            );
            if ($this->force_ids) {
                if ($category['id_category'] == Configuration::get('PS_HOME_CATEGORY')) {
                    Configuration::updateValue('migrationpro_category_edited', 'yes');
                    $cat_id = Configuration::get('migrationpro_source_max_cat') + 1;
                    $category['id_category'] = $cat_id;
                    if (isset($categories['category_lang'][Configuration::get('PS_HOME_CATEGORY')])) {
                        $categories['category_lang'][$cat_id] = $categories['category_lang'][Configuration::get('PS_HOME_CATEGORY')];
                    }
                    if (isset($categories['category_group'][Configuration::get('PS_HOME_CATEGORY')])) {
                        $categories['category_group'][$cat_id] = $categories['category_group'][Configuration::get('PS_HOME_CATEGORY')];
                    }
                }
                if ($category['id_parent'] == Configuration::get('PS_HOME_CATEGORY') && Configuration::get('migrationpro_category_edited') == 'yes') {
                    $cat_id = Configuration::get('migrationpro_source_max_cat') + 1;
                    $category['id_parent'] = $cat_id;
                }
            }
            $lastMigratedCategoryId = Configuration::get('migrationpro_last_migrated_cat_id');
            if (!self::isEmpty($lastMigratedCategoryId)) {
                if ($category['id_parent'] == Configuration::get('migrationpro_last_migrated_cat_id') && $category['id_category'] == Configuration::get('migrationpro_last_migrated_parent_id')) {
                    $category['id_parent'] = Configuration::get('PS_HOME_CATEGORY');
                }
            }
            Configuration::updateValue('migrationpro_last_migrated_cat_id', $category['id_category']);
            Configuration::updateValue('migrationpro_last_migrated_parent_id', $category['id_parent']);

            if (isset($category['id_category']) && in_array((int)$category['id_category'], $categories_home_root)) {
                $this->showMigrationMessageAndLog(self::displayError($this->module->l('The category ID cannot be the same as the Root category ID or the Home category ID.')), 'Category');
                continue;
            }
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($categoryObj = $this->createObjectModel('Category', $category['id_category'])) {
                $categoryObj->active = $category['active'];

                if (isset($category['id_parent']) && !in_array((int)$category['id_parent'], $categories_home_root) && (int)$category['id_parent'] != 0) {
                    if (!Category::categoryExists(self::getLocalId('category', (int)$category['id_parent'], 'data'))) {
                        // -- if parent category not exist create it
                        $this->client->serializeOn();
                        $this->client->setPostData($this->query->parentCategory((int)$category['id_parent']));
                        if ($this->client->query()) {
                            $parentCategory = $this->client->getContent();
                            $this->categories($parentCategory, true);
                        } else {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t execute query to source Shop. ' . $this->client->getMessage()), 'Category');
                        }
                    }
                    $categoryObj->id_parent = self::getLocalId('category', (int)$category['id_parent'], 'data');
                } else {
                    $categoryObj->id_parent = Configuration::get('PS_HOME_CATEGORY');
                }
                $categoryObj->id_parent = $categoryObj->id_parent ? $categoryObj->id_parent : Configuration::get('PS_HOME_CATEGORY');
                $categoryObj->position = $category['position'];
                $categoryObj->date_add = $category['date_add'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $category['date_add'];
                $categoryObj->date_upd = $category['date_upd'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $category['date_upd'];
                foreach ($categories['category_lang'][$category['id_category']] as $lang) {
                    if ($this->force_ids) {
                        if ($lang['id_category'] == Configuration::get('PS_HOME_CATEGORY')) {
                            $cat_id = Configuration::get('migrationpro_source_max_cat') + 1;
                            $lang['id_category'] = $cat_id;
                        }
                    }
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $categoryObj->name[$lang['id_lang']] = $lang['name'];
                    $categoryObj->link_rewrite[$lang['id_lang']] = $lang['link_rewrite'];
                    if (isset($categoryObj->link_rewrite[$lang['id_lang']]) && !self::isEmpty($categoryObj->link_rewrite[$lang['id_lang']])) {
                        $valid_link = Validate::isLinkRewrite($categoryObj->link_rewrite[$lang['id_lang']]);
                    } else {
                        $valid_link = false;
                    }
                    if (!$valid_link) {
                        $categoryObj->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($categoryObj->name[$lang['id_lang']]);

                        if ($categoryObj->link_rewrite[$lang['id_lang']] == '') {
                            $categoryObj->link_rewrite[$lang['id_lang']] = 'friendly-url-autogeneration-failed';
                            $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $categoryObj->name[$lang['id_lang']]), 'Category');
                        }

                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $lang['link_rewrite'], (isset($category['id_category']) && !self::isEmpty($category['id_category'])) ? $category['id_category'] : 'null', $categoryObj->link_rewrite[$lang['id_lang']]), 'Category', true);
                    }
                    $categoryObj->description[$lang['id_lang']] = $lang['description'];
                    $categoryObj->meta_title[$lang['id_lang']] = $lang['meta_title'];
                    $categoryObj->meta_description[$lang['id_lang']] = $lang['meta_description'];
                    $categoryObj->meta_keywords[$lang['id_lang']] = $lang['meta_keywords'];
                }
                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $category['id_shop_list']), '');
                $categoryObj->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($categoryObj);
                $this->validator->checkFields();
                $category_error_tmp = $this->validator->getValidationMessages();
                if ($categoryObj->id && $categoryObj->id == $categoryObj->id_parent) {
                    $this->showMigrationMessageAndLog(self::displayError($this->module->l('A category cannot be its own parent category.')), 'Category');
                    continue;
                }
                if ($categoryObj->id == Configuration::get('PS_ROOT_CATEGORY')) {
                    $this->showMigrationMessageAndLog(self::displayError($this->module->l('The root category cannot be modified.')), 'Category');
                    continue;
                }
                /* No automatic nTree regeneration for import */
                $categoryObj->doNotRegenerateNTree = true;
                // If id category AND id category already in base, trying to update
                if ($categoryObj->id && $categoryObj->categoryExists($categoryObj->id) && !in_array($categoryObj->id, $categories_home_root)) {
                    try {
                        $res = $categoryObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                // If no id_category or update failed
                if (!$res) {
                    try {
                        $res = $categoryObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                $this->showMigrationMessageAndLog($category_error_tmp, 'Category');
                // If both failed, mysql error
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Category (ID: %1$s) cannot be saved. %2$s')), (isset($category['id_category']) && !self::isEmpty($category['id_category'])) ? Tools::safeOutput($category['id_category']) : 'No ID', $err_tmp), 'Category');
                } else {
                    $url = $this->url . $this->image_path . $category['id_category'] . '.jpg';
                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/categories/' . $category['id_category'] . '.jpg';
                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($categoryObj->id, null, $FilePath, 'categories', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Category', true);
                    }
                    //import Category_Group
                    $sql_values = array();
                    foreach ($categories['category_group'][$category['id_category']] as $group) {
                        if (self::getCustomerGroupID($group['id_group']) != "0") {
                            $sql_values[] = '(' . $categoryObj->id . ', ' . self::getCustomerGroupID($group['id_group']) . ')';
                        }
                    }
                    Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'category_group` WHERE id_category = ' . $categoryObj->id);
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'category_group` (`id_category`, `id_group`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError($this->module->l('Can\'t add category_group. ' . Db::getInstance()->getMsgError())), 'Category');
                        }
                    }
                    //@TODO Associate category to shop
                    self::addLog('Category', $category['id_category'], $categoryObj->id);
                    //update multistore language fields
                    if (!version_compare($this->version, '1.5', '<')) {
                        if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                            foreach ($categories['category_lang'][$category['id_category']] as $lang) {
                                $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                $lang['id_category'] = $categoryObj->id;
                                self::updateMultiStoreLang('category', $lang);
                            }
                        }
                    }
                }
                if ($category['id_category'] != Configuration::get('PS_HOME_CATEGORY') && $category['id_category'] != Configuration::get('PS_ROOT_CATEGORY')) {
                    if (version_compare($this->version, '1.5', '>')) {
                        if (self::isEmpty($categoryObj->id_shop_list)) {
                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'category_shop WHERE id_category = ' .
                                (int)$category['id_category'] . ' ');
                        } else {
                            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'category_shop WHERE id_category = ' .
                                (int)$category['id_category'] . ' AND id_shop NOT IN (' . implode(",", $categoryObj->id_shop_list) . ')');
                        }
                    }
                }
            }
        }
        if (!$innerMethodCall) {
            $this->updateProcess($count);
        }
        Category::regenerateEntireNtree();
    }

    /**
     * @param $carriers
     * @param $carriersAdditionalSecond
     */
    public function carriers($carriers)
    {
        //Load images for carriers to temporary dir
        $this->loadImagesToLocal($carriers['carrier'], 'id_carrier', 'carriers', $this->url, $this->image_path);
        $count = 0;
        // Add zones
        //Zones is static table without relations,
        foreach ($carriers['all_zones'] as $all_zone) {
            if ($zoneObject = $this->createObjectModel('Zone', $all_zone['id_zone'])) {
                $zoneObject->active = $all_zone['active'];
                $zoneObject->name = $all_zone['name'];
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($zoneObject);
                $this->validator->checkFields();
                $zone_error_tmp = $this->validator->getValidationMessages();
                if ($zoneObject->id && Zone::existsInDatabase($zoneObject->id, 'zone')) {
                    try {
                        $res = $zoneObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $zoneObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError('Zone (ID: %1$s) cannot be saved. %2$s'), (isset($all_zone['id_zone']) && !self::isEmpty($all_zone['id_zone'])) ? Tools::safeOutput($all_zone['id_zone']) : 'No ID', $err_tmp), 'Zone');
                } else {
                    MigrationProData::import('Zone', $all_zone['id_zone'], $zoneObject->id);
                    MigrationProMigratedData::import('Zone', $all_zone['id_zone'], $zoneObject->id);
                }
                $this->showMigrationMessageAndLog($zone_error_tmp, 'Zone');
            }
        }

        foreach ($carriers['carrier'] as $carrier) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($carrierObj = $this->createObjectModel('Carrier', $carrier['id_carrier'])) {
                $carrierObj->id_tax_rules_group = self::getLocalID('taxRulesGroup', $carrier['id_tax_rules_group'], 'data');
                $carrierObj->name = $carrier['name'];
                $carrierObj->url = $carrier['url'];
                $carrierObj->active = $carrier['active'];
                $carrierObj->deleted = $carrier['deleted'];
                $carrierObj->shipping_handling = $carrier['shipping_handling'];
                $carrierObj->range_behavior = $carrier['range_behavior'];
                $carrierObj->is_module = $carrier['is_module'];
                $carrierObj->is_free = $carrier['is_free'];
                $carrierObj->shipping_external = $carrier['shipping_external'];
                $carrierObj->need_range = $carrier['need_range'];
                $carrierObj->external_module_name = $carrier['external_module_name'];
                $carrierObj->shipping_method = $carrier['shipping_method'];
                $carrierObj->position = $carrier['position'];
                $carrierObj->max_width = $carrier['max_width'];
                $carrierObj->max_height = $carrier['max_height'];
                $carrierObj->max_depth = $carrier['max_depth'];
                $carrierObj->max_weight = $carrier['max_weight'];
                $carrierObj->grade = $carrier['grade'];
                foreach ($carriers['carrier_lang'][$carrier['id_carrier']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $carrierObj->delay[$lang['id_lang']] = $lang['delay'];
                    if (self::isEmpty($carrierObj->delay[$lang['id_lang']])) {
                        $carrierObj->delay[$lang['id_lang']] = 'Empty';
                    }
                }
                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $carrier['id_shop_list']), '');
                $carrierObj->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($carrierObj);
                $this->validator->checkFields();
                $carrier_error_tmp = $this->validator->getValidationMessages();
                if ($carrierObj->id && Carrier::existsInDatabase($carrierObj->id, 'carrier')) {
                    try {
                        $res = $carrierObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $carrierObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Carrier (ID: %1$s) cannot be saved. %2$s')), (isset($carrier['id_carrier']) && !self::isEmpty($carrier['id_carrier'])) ? Tools::safeOutput($carrier['id_carrier']) : 'No ID', $err_tmp), 'Carrier');
                } else {
                    // Import Carrier Group
                    $sql_values = array();
                    foreach ($carriers['carrier_group'][$carrier['id_carrier']] as $carrierGroup) {
                        $sql_values[] = '(' . (int)$carrierObj->id . ', ' . self::getCustomerGroupID($carrierGroup['id_group']) . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'carrier_group` (`id_carrier`, `id_group`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add carrier_group. ' . Db::getInstance()->getMsgError()), 'Carrier');
                        }
                    }
                    // Range_price
                    foreach ($carriers['range_price'][$carrier['id_carrier']] as $rangePrice) {
                        if ($rangePriceObject = $this->createObjectModel('RangePrice', $rangePrice['id_range_price'])) {
                            $rangePriceObject->id_carrier = self::getLocalID('carrier', $rangePrice['id_carrier'], 'data');
                            $rangePriceObject->delimiter1 = $rangePrice['delimiter1'];
                            $rangePriceObject->delimiter2 = $rangePrice['delimiter2'];
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($rangePriceObject);
                            $this->validator->checkFields();
                            $range_price_error_tmp = $this->validator->getValidationMessages();
                            if ($rangePriceObject->id && RangePrice::existsInDatabase($rangePriceObject->id, 'range_price')) {
                                try {
                                    $res = $rangePriceObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $rangePriceObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError('Range price (ID: %1$s) cannot be saved. %2$s'), (isset($rangePrice['id_range_price']) && !self::isEmpty($rangePrice['id_range_price'])) ? Tools::safeOutput($rangePrice['id_range_price']) : 'No ID', $err_tmp), 'RangePrice');
                            } else {
                                MigrationProData::import('RangePrice', $rangePrice['id_range_price'], $rangePriceObject->id);
                                MigrationProMigratedData::import('RangePrice', $rangePrice['id_range_price'], $rangePriceObject->id);
                            }
                            $this->showMigrationMessageAndLog($range_price_error_tmp, 'RangePrice');
                        }
                    }

//                        // Range_weight
                    foreach ($carriers['range_weight'][$carrier['id_carrier']] as $rangeWeight) {
                        if ($rangeWeightObject = $this->createObjectModel('RangeWeight', $rangeWeight['id_range_weight'])) {
                            $rangeWeightObject->id_carrier = self::getLocalID('carrier', $rangeWeight['id_carrier'], 'data');
                            $rangeWeightObject->delimiter1 = $rangeWeight['delimiter1'];
                            $rangeWeightObject->delimiter2 = $rangeWeight['delimiter2'];
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($rangeWeightObject);
                            $this->validator->checkFields();
                            $range_weight_error_tmp = $this->validator->getValidationMessages();
                            if ($rangeWeightObject->id && RangeWeight::existsInDatabase($rangeWeightObject->id, 'range_weight')) {
                                try {
                                    $res = $rangeWeightObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $rangeWeightObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError('Range weight (ID: %1$s) cannot be saved. %2$s'), (isset($rangeWeight['id_range_weight']) && !self::isEmpty($rangeWeight['id_range_weight'])) ? Tools::safeOutput($rangeWeight['id_range_weight']) : 'No ID', $err_tmp), 'RangeWeight');
                            } else {
                                MigrationProData::import('RangeWeight', $rangeWeight['id_range_weight'], $rangeWeightObject->id);
                                MigrationProMigratedData::import('RangeWeight', $rangeWeight['id_range_weight'], $rangeWeightObject->id);
                            }
                            $this->showMigrationMessageAndLog($range_weight_error_tmp, 'RangeWeight');
                        }
                    }

//                        // Delivery
                    foreach ($carriers['carrier_delivery'][$carrier['id_carrier']] as $delivery) {
                        if ($deliveryObject = $this->createObjectModel('Delivery', $delivery['id_delivery'])) {
                            $deliveryObject->id_carrier = self::getLocalID('carrier', $delivery['id_carrier'], 'data');
                            $deliveryObject->id_shop = 0;
                            $deliveryObject->id_shop_group = 0;
                            $deliveryObject->id_range_price = self::isEmpty($delivery['id_range_price']) ? 0 : $delivery['id_range_price'];
                            $deliveryObject->id_range_weight = self::isEmpty($delivery['id_range_weight']) ? 0 : $delivery['id_range_weight'];
                            $deliveryObject->id_zone = $delivery['id_zone'];
                            $deliveryObject->price = $delivery['price'];

                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($deliveryObject);
                            $this->validator->checkFields();
                            $delivery_error_tmp = $this->validator->getValidationMessages();
                            if ($deliveryObject->id && Delivery::existsInDatabase($deliveryObject->id, 'delivery')) {
                                try {
                                    $res = $deliveryObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $deliveryObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError('Delivery (ID: %1$s) cannot be saved. %2$s'), (isset($delivery['id_delivery']) && !self::isEmpty($delivery['id_delivery'])) ? Tools::safeOutput($delivery['id_delivery']) : 'No ID', $err_tmp), 'Delivery');
                            } else {
                                if ($deliveryObject->id_range_price == 0) {
                                    Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'delivery` set id_shop_group = null, id_shop = null, id_range_price = null WHERE id_range_price = 0');
                                } else {
                                    Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'delivery` set id_shop_group = null, id_shop = null, id_range_weight = null WHERE id_range_weight = 0');
                                }
                                MigrationProData::import('Delivery', $delivery['id_delivery'], $deliveryObject->id);
                                MigrationProMigratedData::import('Delivery', $delivery['id_delivery'], $deliveryObject->id);
                            }
                            $this->showMigrationMessageAndLog($delivery_error_tmp, 'Delivery');
                        }
                    }

                    // Import Carrier Tax Rules Group Shop
                    $sql_values = array();
                    foreach ($carriers['carrier_tax_rules_group_shop'][$carrier['id_carrier']] as $carrierTaxRulesGroupShop) {
                        $sql_values[] = '(' . (int)$carrierObj->id . ', ' . self::getLocalID('taxRulesGroup', ($carrierTaxRulesGroupShop['id_tax_rules_group']), 'data') . ', ' . self::getShopID($carrierTaxRulesGroupShop['id_shop']) . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop` (`id_carrier`, `id_tax_rules_group`, `id_shop`) VALUES
                                 ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add carrier_tax_rules_group_shop. ' . Db::getInstance()->getMsgError()), 'Carrier');
                        }
                    }

                    // Import Carrier Zone
                    $sql_values = array();
                    foreach ($carriers['carrier_zone'][$carrier['id_carrier']] as $carrierZone) {
                        $sql_values[] = '(' . (int)$carrierObj->id . ', ' . (int)$carrierZone['id_zone'] . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'carrier_zone` (`id_carrier`, `id_zone`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add carrier_zone. ' . Db::getInstance()->getMsgError()), 'Carrier');
                        }
                    }
                    $url = $this->url . $this->image_path . $carrier['id_carrier'] . '.jpg';
                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/carriers/' . $carrier['id_carrier'] . '.jpg';

                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($carrierObj->id, null, $FilePath, 'carriers', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Carrier', true);
                    }

                    self::addLog('Carrier', $carrier['id_carrier'], $carrierObj->id);

                    //update multistore language fields
                    if (!version_compare($this->version, '1.5', '<')) {
                        if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                            foreach ($carriers['carrier_lang'][$carrier['id_carrier']] as $lang) {
                                $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                $lang['id_carrier'] = $carrierObj->id;
                                self::updateMultiStoreLang('carrier', $lang);
                            }
                        }
                    }
                }
                $this->showMigrationMessageAndLog($carrier_error_tmp, 'Carrier');
            }
        }
        $this->updateProcess($count);
    }


    /**
     * @param array $products
     */

    public function products($products)
    {
        //Load images for products to temporary dir
        $this->loadImagesToLocal($products['image_ids'], 'id_image', 'products', $this->url, $this->image_path);
        //Load images for supliers to temporary dir
        $this->loadImagesToLocal($products['supplier'], 'id_supplier', 'suppliers', $this->url, $this->image_supplier_path, false);
        //Load images for attributes to temporary dir
        $this->loadImagesToLocal($products['attribute'], 'id_attribute', 'attributes', $this->url, '/img/co/', false);
        Module::setBatchMode(true);
        $count = 0;
        //@TODO create import function for each data type
        #region import supplier

        foreach ($products['supplier'] as $supplier) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($supplierObj = $this->createObjectModel('Supplier', $supplier['id_supplier'])) {
                $supplierObj->name = $supplier['name'];
                if (!Validate::isCatalogName($supplierObj->name)) {
                    $supplierObj->name = 'Empty supplier name';
                    $this->showMigrationMessageAndLog('Name of supplier with ID ' . $supplier['id_supplier'] . ' is empty. For that reason, the module set default name to this supplier', 'Supplier', true);
                }
                $supplierObj->active = $supplier['active'];
                $supplierObj->date_add = $supplier['date_add'];
                $supplierObj->date_upd = $supplier['date_upd'];
                //language fields
                foreach ($products['supplier_lang'][$supplier['id_supplier']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $supplierObj->description[$lang['id_lang']] = $lang['description'];
                    if (!ValidateCore::isCleanHtml($supplierObj->description[$lang['id_lang']])) {
                        $supplierObj->description[$lang['id_lang']] = '';
                    }

                    $supplierObj->meta_title[$lang['id_lang']] = $lang['meta_title'];
                    $supplierObj->meta_description[$lang['id_lang']] = $lang['meta_description'];
                    $supplierObj->meta_keywords[$lang['id_lang']] = $lang['meta_keywords'];
                }

                // Add to _shop relations

                $id_shop_list = $this->getChangedIdShop(explode(',', $supplier['id_shop_list']), '');
                $supplierObj->id_shop_list = $id_shop_list;


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($supplierObj);
                $this->validator->checkFields();
                $supplier_error_tmp = $this->validator->getValidationMessages();
                if ($supplierObj->id && Supplier::existsInDatabase($supplierObj->id, 'supplier')) {
                    try {
                        $res = $supplierObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $supplierObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Supplier (ID: %1$s) cannot be saved. %2$s')), (isset($supplier['id_supplier']) && !self::isEmpty($supplier['id_supplier'])) ? Tools::safeOutput($supplier['id_supplier']) : 'No ID', $err_tmp), 'Supplier');
                } else {
                    $url = $this->url . $this->image_supplier_path . $supplier['id_supplier'] . '.jpg';
                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/suppliers/' . $supplier['id_supplier'] . '.jpg';
                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($supplierObj->id, null, $FilePath, 'suppliers', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Supplier', true);
                    }
                    self::addLog('Supplier', $supplier['id_supplier'], $supplierObj->id);
                }
                $this->showMigrationMessageAndLog($supplier_error_tmp, 'Supplier');
            }
        }
        #endregion
        #region import attribute group
        foreach ($products['attribute_group'] as $attributeGroup) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($attributeGroupObj = $this->createObjectModel('AttributeGroup', $attributeGroup['id_attribute_group'])) {
                $attributeGroupObj->is_color_group = $attributeGroup['is_color_group'];
                if (isset($attributeGroup['group_type'])) {
                    $attributeGroupObj->group_type = $attributeGroup['group_type'];
                } else {
                    $attributeGroupObj->group_type = ($attributeGroup['is_color_group']) ? 'color' : 'select';
                }
                foreach ($products['attribute_group_lang'][$attributeGroup['id_attribute_group']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $attributeGroupObj->name[$lang['id_lang']] = $lang['name'];
                    $attributeGroupObj->public_name[$lang['id_lang']] = $lang['public_name'];
                }

                // Add to _shop relations

                $id_shop_list = $this->getChangedIdShop(explode(',', $attributeGroup['id_shop_list']), '');
                $attributeGroupObj->id_shop_list = $id_shop_list;


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($attributeGroupObj);
                $this->validator->checkFields();
                $attribute_group_error_tmp = $this->validator->getValidationMessages();
                if ($attributeGroupObj->id && AttributeGroup::existsInDatabase($attributeGroupObj->id, 'attribute_group')) {
                    try {
                        $res = $attributeGroupObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $attributeGroupObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('AttributeGroup (ID: %1$s) cannot be saved. %2$s')), (isset($attributeGroup['id_attribute_group']) && !self::isEmpty($attributeGroup['id_attribute_group'])) ? Tools::safeOutput($attributeGroup['id_attribute_group']) : 'No ID', $err_tmp), 'AttributeGroup');
                } else {
                    self::addLog('AttributeGroup', $attributeGroup['id_attribute_group'], $attributeGroupObj->id);
                }
                $this->showMigrationMessageAndLog($attribute_group_error_tmp, 'AttributeGroup');
            }
        }
        #endregion
        #region import attribute
        foreach ($products['attribute'] as $attribute) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($attributeObj = $this->createObjectModel('Attribute', $attribute['id_attribute'])) {
                $attributeObj->id_attribute_group = self::getLocalID('attributegroup', (int)$attribute['id_attribute_group'], 'data');
                $attributeObj->color = $attribute['color'];
                foreach ($products['attribute_lang'][$attribute['id_attribute']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $attributeObj->name[$lang['id_lang']] = $lang['name'];
                }

                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $attribute['id_shop_list']), '');
                $attributeObj->id_shop_list = $id_shop_list;


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($attributeObj);
                $this->validator->checkFields();
                $attribute_error_tmp = $this->validator->getValidationMessages();
                if ($attributeObj->id && Attribute::existsInDatabase($attributeObj->id, 'attribute')) {
                    try {
                        $res = $attributeObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $attributeObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Attribute (ID: %1$s) cannot be saved. %2$s')), (isset($attribute['id_attribute']) && !self::isEmpty($attribute['id_attribute'])) ? Tools::safeOutput($attribute['id_attribute']) : 'No ID', $err_tmp), 'Attribute');
                } else {
                    self::addLog('Attribute', $attribute['id_attribute'], $attributeObj->id);
                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/attributes/' . $attribute['id_attribute'] . '.jpg';
                    // import attribute texture image
                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$this->url . '/img/co/' . $attribute['id_attribute'] . '.jpg']) && !(EDImport::copyImg($attributeObj->id, null, $FilePath, 'attributes', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($this->url . '/img/co/' . $attribute['id_attribute'] . '.jpg' . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Attribute', true);
                    }
                }
                $this->showMigrationMessageAndLog($attribute_error_tmp, 'Attribute');
            }
        }
        #endregion
        #region import feature
        foreach ($products['feature'] as $feature) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($featureObj = $this->createObjectModel('Feature', $feature['id_feature'])) {
                if (isset($feature['position']) && !self::isEmpty($feature['position'])) {
                    $featureObj->position = (int)$feature['position'];
                } else {
                    $featureObj->position = Feature::getHigherPosition() + 1;
                }
                foreach ($products['feature_lang'][$feature['id_feature']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $featureObj->name[$lang['id_lang']] = $lang['name'];
                    if (self::isEmpty($featureObj->name[$lang['id_lang']])) {
                        $featureObj->name[$lang['id_lang']] = 'empty';
                    }
                }
                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $feature['id_shop_list']), '');
                $featureObj->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($featureObj);
                $this->validator->checkFields();
                $feature_error_tmp = $this->validator->getValidationMessages();
                if ($featureObj->id && Feature::existsInDatabase($featureObj->id, 'feature')) {
                    try {
                        $res = $featureObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $featureObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Feature (ID: %1$s) cannot be saved. %2$s')), (isset($feature['id_feature']) && !self::isEmpty($feature['id_feature'])) ? Tools::safeOutput($feature['id_feature']) : 'No ID', $err_tmp), 'Feature');
                } else {
                    self::addLog('Feature', $feature['id_feature'], $featureObj->id);
                }
                $this->showMigrationMessageAndLog($feature_error_tmp, 'Feature');
            }
        }
        #endregion

        #region import feature value
        foreach ($products['feature_value'] as $featureValue) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($featureValueObj = $this->createObjectModel('FeatureValue', $featureValue['id_feature_value'])) {
                $featureValueObj->id_feature = self::getLocalId('feature', $featureValue['id_feature'], 'data');
                $featureValueObj->custom = $featureValue['custom'];
                foreach ($products['feature_value_lang'][$featureValue['id_feature_value']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $featureValueObj->value[$lang['id_lang']] = (!self::isEmpty($lang['value']) ? $lang['value'] : ' ');
                }
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($featureValueObj);
                $this->validator->checkFields();
                $feature_value_error_tmp = $this->validator->getValidationMessages();
                if ($featureValueObj->id && FeatureValue::existsInDatabase($featureValueObj->id, 'feature_value')) {
                    try {
                        $res = $featureValueObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $featureValueObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('FeatureValue (ID: %1$s) cannot be saved. %2$s')), (isset($featureValue['id_feature_value']) && !self::isEmpty($featureValue['id_feature_value'])) ? Tools::safeOutput($featureValue['id_feature_value']) : 'No ID', $err_tmp), 'FeatureValue');
                } else {
                    self::addLog('FeatureValue', $featureValue['id_feature_value'], $featureValueObj->id);
                }
                $this->showMigrationMessageAndLog($feature_value_error_tmp, 'FeatureValue');
            }
        }
        #endregion
        #region import Tag
        foreach ($products['tag'] as $tag) {
            if ($this->module->isPaused()) {
                break;
            }
            //id-lang for version PS 1.4
            $tagPS14 = '';
            $tagPS14[$tag['id_tag']] = self::getLocalId('tag', $tag['id_lang'], 'data');
            if ($tagObject = $this->createObjectModel('Tag', $tag['id_tag'])) {
                $tagObject->id_lang = self::getLanguageID($tag['id_lang']);
                $tagObject->name = $tag['name'];
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($tagObject);
                $this->validator->checkFields();
                $tag_error_tmp = $this->validator->getValidationMessages();
                if ($tagObject->id && Tag::existsInDatabase($tagObject->id, 'tag')) {
                    try {
                        $res = $tagObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $tagObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Tag (ID: %1$s) cannot be saved. %2$s')), (isset($tag['id_tag']) && !self::isEmpty($tag['id_tag'])) ? Tools::safeOutput($tag['id_tag']) : 'No ID', $err_tmp), 'Tag');
                } else {
                    self::addLog('Tag', $tag['id_tag'], $tagObject->id);
                }
                $this->showMigrationMessageAndLog($tag_error_tmp, 'Tag');
            }
        }
        #endregion

        #region import Products
        foreach ($products['product'] as $product) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($productObj = $this->createObjectModel('Product', $product['id_product'])) {
                $productObj->id_manufacturer = self::getLocalId('manufacturer', $product['id_manufacturer'], 'data');
                $productObj->id_supplier = self::getLocalId('supplier', $product['id_supplier'], 'data');
                $productObj->reference = $product['reference'];
                $productObj->supplier_reference = $product['supplier_reference'];
                $productObj->location = $product['location'];
                $productObj->width = $product['width'];
                $productObj->height = $product['height'];
                $productObj->depth = $product['depth'];
                $productObj->weight = $product['weight'];
                $productObj->quantity_discount = $product['quantity_discount'];
                $productObj->ean13 = $product['ean13'];
                $productObj->upc = $product['upc'];
                $productObj->cache_is_pack = $product['cache_is_pack'];

                $productObj->cache_has_attachments = $product['cache_has_attachments'];
                if ($product['id_category_default'] == 1 || $product['id_category_default'] == 0 || $product['id_category_default'] == 2) {
                    $productObj->id_category_default = Configuration::get('PS_HOME_CATEGORY');
                } else {
                    $localDefaultCategoryId = self::getLocalId('category', $product['id_category_default'], 'data');
                    if (self::isEmpty($localDefaultCategoryId)) {
                        $productObj->id_category_default = Configuration::get('PS_HOME_CATEGORY');
                    } else {
                        $productObj->id_category_default = self::getLocalId('category', $product['id_category_default'], 'data');
                    }
                }

                $productObj->id_tax_rules_group = self::getLocalId('taxrulesgroup', $product['id_tax_rules_group'], 'data');
                $productObj->on_sale = $product['on_sale'];
                $productObj->online_only = $product['online_only'];
                $productObj->ecotax = $product['ecotax'];
                $productObj->minimal_quantity = $product['minimal_quantity'];
                $productObj->price = $product['price'];
                $productObj->wholesale_price = $product['wholesale_price'];
                $productObj->unity = $product['unity'];
                $productObj->unit_price_ratio = $product['unit_price_ratio'];
                $productObj->additional_shipping_cost = $product['additional_shipping_cost'];
                $productObj->customizable = $product['customizable'];
                $productObj->text_fields = $product['text_fields'];
                $productObj->uploadable_files = $product['uploadable_files'];
                $productObj->active = $product['active'];
                $productObj->available_for_order = $product['available_for_order'];
                $productObj->condition = $product['condition'];
                $productObj->show_price = $product['show_price'];
                $productObj->indexed = 0; // always zero for new PS $product['indexed'];
                $productObj->cache_default_attribute = $product['cache_default_attribute'];
                $productObj->date_add = $product['date_add'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $product['date_add'];
                $productObj->date_upd = $product['date_upd'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $product['date_upd'];
                $productObj->out_of_stock = $product['out_of_stock'];
//                $productObj->id_color_default = $product['id_color_default']; // @deprecated 1.5.0
                $productObj->quantity = $product['quantity'];
                if ($this->version >= 1.5) {
                    $productObj->id_shop_default = self::getShopID($product['id_shop_default']);
//                    $productObj->isbn = $product['isbn'];
                    $productObj->is_virtual = $product['is_virtual'];
                    $productObj->redirect_type = $product['redirect_type'];
                    $productObj->id_product_redirected = isset($product['id_product_redirected']) ? $product['id_product_redirected'] : 0;
                    $productObj->available_date = $product['available_date'];
//                    $productObj->show_condition = $product['show_condition'];
                    $productObj->visibility = $product['visibility'];
                    $productObj->advanced_stock_management = $product['advanced_stock_management'];
                }

                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $product['id_shop_list']), '');
                $productObj->id_shop_list = $id_shop_list;


                //language fields
                foreach ($products['product_lang'][$product['id_product']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $productObj->meta_description[$lang['id_lang']] = $lang['meta_description'];
                    $productObj->meta_keywords[$lang['id_lang']] = $lang['meta_keywords'];
                    $productObj->meta_title[$lang['id_lang']] = $lang['meta_title'];
                    $productObj->name[$lang['id_lang']] = $lang['name'];
                    $productObj->link_rewrite[$lang['id_lang']] = $lang['link_rewrite'];
                    if (isset($productObj->link_rewrite[$lang['id_lang']]) && !self::isEmpty($productObj->link_rewrite[$lang['id_lang']])) {
                        $valid_link = Validate::isLinkRewrite($productObj->link_rewrite[$lang['id_lang']]);
                    } else {
                        $valid_link = false;
                    }
                    if (!$valid_link) {
                        $productObj->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($productObj->name[$lang['id_lang']]);

                        if ($productObj->link_rewrite[$lang['id_lang']] == '') {
                            $productObj->link_rewrite[$lang['id_lang']] = 'friendly-url-autogeneration-failed';
                            $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $productObj->name[$lang['id_lang']]), 'Product');
                        }
                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $lang['link_rewrite'], (isset($product['id_product']) && !self::isEmpty($product['id_product'])) ? $product['id_product'] : 'null', $productObj->link_rewrite[$lang['id_lang']]), 'Product');
                    }
                    $productObj->description[$lang['id_lang']] = $lang['description'];
                    $productObj->description_short[$lang['id_lang']] = $lang['description_short'];
                    $productObj->available_now[$lang['id_lang']] = $lang['available_now'];
                    $productObj->available_later[$lang['id_lang']] = $lang['available_later'];
                }
                //@TODO get shop id from step-2
                if (!$this->shop_is_feature_active) {
                    $productObj->id_shop_default = (int)Configuration::get('PS_SHOP_DEFAULT');
                } else {
                    $productObj->id_shop_default = (int)Context::getContext()->shop->id;
                }
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($productObj);
                $this->validator->checkFields();
                $product_error_tmp = $this->validator->getValidationMessages();
                if ($productObj->id && Product::existsInDatabase((int)$productObj->id, 'product')) {
                    try {
                        $res = $productObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $productObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Product (ID: %1$s) cannot be saved. %2$s')), (isset($product['id_product']) && !self::isEmpty($product['id_product'])) ? Tools::safeOutput($product['id_product']) : 'No ID', $err_tmp), 'Product');
                } else {
                    foreach ($products['product_carrier'][$product['id_product']] as $productCarrier) {
                        $id_product = $productObj->id;
                        $id_carrier_reference = self::getCarrierReference($productCarrier['id_carrier']);
                        $id_shop = self::getShopID($productCarrier['id_shop']);

                        $result = Db::getInstance()->execute('INSERT IGNORE INTO ' . _DB_PREFIX_ . 'product_carrier (`id_product`, `id_carrier_reference`, `id_shop`) VALUES (' . (int)$id_product . ', ' . (int)$id_carrier_reference . ', ' . (int)$id_shop . ')');
                        if (!$result) {
                            if (!$this->ps_validation_errors) {
                                continue;
                            }
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t update product_carrier. ' . Db::getInstance()->getMsgError()), 'Product');
                        }
                    }

                    // set quantity to StockAvailable
                    if ($this->version >= 1.5) {
                        foreach ($products['stock_available'][$product['id_product']] as $stock_available) {
                            $id_shop = null;
                            if ($stock_available['id_product_attribute'] == 0) {
                                if (isset($stock_available['id_shop'])) {
                                    $id_shop = self::getShopID($stock_available['id_shop']);
                                }
                                StockAvailable::setQuantity($productObj->id, $stock_available['id_product_attribute'], $stock_available['quantity'], $id_shop);
                                StockAvailable::setProductDependsOnStock($productObj->id, $stock_available['depends_on_stock'], $id_shop);
                                StockAvailable::setProductOutOfStock($productObj->id, $stock_available['out_of_stock'], $id_shop);
                            }
                        }
                    } else {
                        StockAvailable::setQuantity($productObj->id, 0, $product['quantity']);
                    }

                    //update product activity for each shop
                    foreach ($products['product_shop'][$product['id_product']] as $productShop) {
                        $result = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'product_shop SET active = ' . (int)$productShop['active'] . ' WHERE id_product = ' . (int)$productObj->id . ' AND id_shop = ' . (int)self::getShopID($productShop['id_shop']));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t update product_shop. ' . Db::getInstance()->getMsgError()), 'Product');
                        }
                    }

                    //import Category_Product
                    $sql_values = array();
                    foreach ($products['category_product'][$product['id_product']] as $categoryProduct) {
                        if ((int)$categoryProduct['id_category'] == 2) {
                            if (version_compare($this->version, '1.5', '<')) {
                                $sql_values[] = '(' . self::getLocalID('category', (int)$categoryProduct['id_category'], 'data') . ', ' . (int)$productObj->id . ', ' . (int)$categoryProduct['position'] . ')';
                            } else {
                                $sql_values[] = '(' . (int)$categoryProduct['id_category'] . ', ' . (int)$productObj->id . ', ' . (int)$categoryProduct['position'] . ')';
                            }
                        } else {
                            $sql_values[] = '(' . self::getLocalID('category', (int)$categoryProduct['id_category'], 'data') . ', ' . (int)$productObj->id . ', ' . (int)$categoryProduct['position'] . ')';
                        }
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'category_product` (`id_category`, `id_product`, `position`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add category_product. ' . Db::getInstance()->getMsgError()), 'Product');
                        }
                    }

                    //import images
                    foreach ($products['image'][$product['id_product']] as $image) {
                        if ($imageObject = $this->createObjectModel('Image', $image['id_image'])) {
                            $imageObject->id_product = $productObj->id;
                            $imageObject->position = $image['position'];
                            $imageObject->cover = $image['cover'];
                            //language fields
                            foreach ($products['image_lang'][$image['id_image']] as $lang) {
                                $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                $imageObject->legend[$lang['id_lang']] = $lang['legend'];
                            }
                            // Add to _shop relations
                            $id_shop_list = $this->getChangedIdShop(explode(',', $image['id_shop_list']), '');
                            $imageObject->id_shop_list = $id_shop_list;
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($imageObject);
                            $this->validator->checkFields();
                            $image_error_tmp = $this->validator->getValidationMessages();
                            if ($imageObject->id && Image::existsInDatabase($imageObject->id, 'image')) {
                                try {
                                    $res = $imageObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                try {
                                    $res = $imageObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError('Image (ID: %1$s) cannot be saved. Product (ID: %2$s). %3$s'), (isset($image['id_image']) && !self::isEmpty($image['id_image'])) ? Tools::safeOutput($image['id_image']) : 'No ID', $productObj->id, $err_tmp), 'Image');
                            } else {
                                if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                                    $url = $this->url . $this->image_path . $product['id_product'] . '-' . $image['id_image'] . '.jpg';
                                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/products/' . $product['id_product'] . '-' . $image['id_image'] . '.jpg';
                                } else {
                                    $url = $this->url . $this->image_path . Image::getImgFolderStatic($image['id_image']) . (int)$image['id_image'] . '.jpg';
                                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/products/' . $image['id_image'] . '.jpg';
                                }
                                if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($productObj->id, $imageObject->id, $FilePath, 'products', $this->regenerate))) {
                                    $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Image', true);
                                }
                                self::addLog('Image', $image['id_image'], $imageObject->id);
                            }
                            $this->showMigrationMessageAndLog($image_error_tmp, 'Image');
                        }
                    }

                    //import Product Attribute
                    foreach ($products['product_attribute'][$product['id_product']] as $productAttribute) {
                        if ($combinationModel = $this->createObjectModel('Combination', $productAttribute['id_product_attribute'])) {
                            $combinationModel->id_product = $productObj->id;
                            $combinationModel->location = $productAttribute['location'];
                            $combinationModel->ean13 = $productAttribute['ean13'];
                            $combinationModel->upc = $productAttribute['upc'];
                            $combinationModel->quantity = $productAttribute['quantity'];
                            $combinationModel->reference = $productAttribute['reference'];
                            $combinationModel->supplier_reference = $productAttribute['supplier_reference'];
                            $combinationModel->wholesale_price = $productAttribute['wholesale_price'];
                            $combinationModel->price = $productAttribute['price'];
                            $combinationModel->ecotax = $productAttribute['ecotax'];
                            $combinationModel->weight = $productAttribute['weight'];
                            $combinationModel->unit_price_impact = $productAttribute['unit_price_impact'];
                            $combinationModel->minimal_quantity = (isset($productAttribute['minimal_quantity']) && !self::isEmpty($productAttribute['minimal_quantity'])) ? $productAttribute['minimal_quantity'] : 1;
                            $combinationModel->default_on = $productAttribute['default_on'];
                            if ($this->version >= 1.5) {
//                                        $combinationModel->isbn = $productAttribute['isbn'];
                                $combinationModel->available_date = $productAttribute['available_date'];
                            }
                            // Add to _shop relations
                            $id_shop_list = $this->getChangedIdShop(explode(',', $productAttribute['id_shop_list']), '');
                            $combinationModel->id_shop_list = $id_shop_list;


                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($combinationModel);
                            $this->validator->checkFields();
                            $combination_error_tmp = $this->validator->getValidationMessages();
                            if ($combinationModel->id && Combination::existsInDatabase($combinationModel->id, 'product_attribute')) {
                                try {
                                    $res = $combinationModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                try {
                                    $res = $combinationModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Product attribute (ID: %1$s) cannot be saved. %2$s')), (isset($productAttribute['id_product_attribute']) && !self::isEmpty($productAttribute['id_product_attribute'])) ? Tools::safeOutput($combinationModel->id) : 'No ID', $err_tmp), 'Combination');
                            } else {
                                self::addLog('Combination', $productAttribute['id_product_attribute'], $combinationModel->id);
                                // set quantity for Combination to StockAvailable
                                if ($this->version >= 1.5) {
                                    foreach ($products['stock_available'][$product['id_product']] as $stock_available) {
                                        if ($stock_available['id_product_attribute'] == $productAttribute['id_product_attribute']) {
                                            StockAvailable::setQuantity($combinationModel->id_product, $combinationModel->id, $stock_available['quantity']);
                                            StockAvailable::setProductDependsOnStock($combinationModel->id_product, $stock_available['depends_on_stock'], null, $combinationModel->id);
                                            StockAvailable::setProductOutOfStock($combinationModel->id_product, $stock_available['out_of_stock'], null, $combinationModel->id);
                                        }
                                    }
                                } else {
                                    StockAvailable::setQuantity($combinationModel->id_product, $combinationModel->id, $productAttribute['quantity']);
                                }
                                //import product_attribute_combination
                                $sql_values = array();
                                foreach ($products['product_attribute_combination'][$productAttribute['id_product_attribute']] as $productAttributeCombination) {
                                    $sql_values[] = '(' . self::getLocalID('attribute', (int)$productAttributeCombination['id_attribute'], 'data') . ', ' . self::getLocalID('combination', $productAttributeCombination['id_product_attribute'], 'data') . ')';
                                }
                                if (!self::isEmpty($sql_values)) {
                                    $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'product_attribute_combination` (`id_attribute`, `id_product_attribute`) VALUES ' . implode(',', $sql_values));
                                    if (!$result) {
                                        $this->showMigrationMessageAndLog(self::displayError('Can\'t add product_attribute_combination. ' . Db::getInstance()->getMsgError()), 'Combination');
                                    }
                                }

                                //import product_attribute_image
                                $sql_values = array();
                                foreach ($products['product_attribute_image'][$productAttribute['id_product_attribute']] as $productAttributeImage) {
                                    $sql_values[] = '(' . (int)$combinationModel->id . ', ' . self::getLocalID('image', (int)$productAttributeImage['id_image'], 'data') . ')';
                                }
                                if (!self::isEmpty($sql_values)) {
                                    $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'product_attribute_image` (`id_product_attribute`, `id_image`) VALUES ' . implode(',', $sql_values));
                                    if (!$result) {
                                        $this->showMigrationMessageAndLog(self::displayError('Can\'t add product_attribute_image. ' . Db::getInstance()->getMsgError()), 'Combination');
                                    }
                                }
                            }
                            $this->showMigrationMessageAndLog($combination_error_tmp, 'Combination');
                        }
                        //}
                    }

                    //import specific price
                    $second = 10;
                    foreach ($products['specific_price'][$product['id_product']] as $specificPrice) {
                        if ($specificPriceObj = $this->createObjectModel('SpecificPrice', $specificPrice['id_specific_price'])) {
                            $specificPriceObj->id_shop = (int)self::getShopID($specificPrice['id_shop']);
                            $specificPriceObj->id_product = $productObj->id;
                            $specificPriceObj->id_currency = self::getCurrencyID($specificPrice['id_currency']);
                            $specificPriceObj->id_country = self::getLocalId('country', $specificPrice['id_country'], 'data');
                            $specificPriceObj->id_group = self::getCustomerGroupID($specificPrice['id_group']);
//                                    $specificPriceObj->price = ((int)$specificPrice['price'] == 0) ? -1 : $specificPrice['price'];
                            $specificPriceObj->price = ($specificPrice['price'] <= 0) ? '-1' : (float)$specificPrice['price'];
                            $specificPriceObj->from_quantity = $specificPrice['from_quantity'];
                            $specificPriceObj->reduction = $specificPrice['reduction'];
                            $specificPriceObj->reduction_type = $specificPrice['reduction_type'];
                            $specificPriceObj->from = $specificPrice['from'];
                            $specificPriceObj->to = $specificPrice['to'];
//                                    $specificPriceObj->id_customer = (isset($specificPrice['id_customer']) && !self::isEmpty($specificPrice['id_customer'])) ? self::getLocalId('customer', $specificPrice['id_customer'], 'data') : 0;
                            $specificPriceObj->id_customer = (isset($specificPrice['id_customer']) && !self::isEmpty($specificPrice['id_customer'])) ? $specificPrice['id_customer'] : 0;
                            if ($this->version >= 1.5) {
                                $specificPriceObj->id_shop = self::getShopID($specificPrice['id_shop']);
                                $specificPriceObj->id_shop_group = Shop::getGroupFromShop($specificPriceObj->id_shop);

                                $specificPriceObj->id_cart = $specificPrice['id_cart'];
                                $specificPriceObj->id_product_attribute = self::getLocalId('combination', $specificPrice['id_product_attribute'], 'data');
                                $specificPriceObj->id_specific_price_rule = $specificPrice['id_specific_price_rule'];

                                $specificPriceObj->reduction_tax = (isset($specificPrice['reduction_tax']) && !self::isEmpty($specificPrice['reduction_tax'])) ? $specificPrice['reduction_tax'] : 1;
                            }
                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($specificPriceObj);
                            $this->validator->checkFields();
                            $specific_price_error_tmp = $this->validator->getValidationMessages();
                            if ($specificPriceObj->id && SpecificPrice::existsInDatabase($specificPriceObj->id, 'specific_price')) {
                                try {
                                    $res = $specificPriceObj->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                try {
                                    $res = $specificPriceObj->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            // check duplicate entry

                            if (Db::getInstance()->getNumberError() == 1062) {
                                $second++;
                                if ($second > 59) {
                                    $second = 10;
                                }
                                $specificPriceObj->from = substr_replace($specificPrice['from'], $second, Tools::strlen($specificPrice['from']) - 2, Tools::strlen($specificPrice['from']));
                                try {
                                    $res = $specificPriceObj->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $error_tmp = $err_tmp;
                                if (!Tools::isEmpty(Db::getInstance()->getMsgError())) {
                                    $error_tmp .= ' ' . Db::getInstance()->getMsgError();
                                }
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('SpecificPrice (ID: %1$s) cannot be saved. %2$s')), (isset($specificPrice['id_specific_price']) && !self::isEmpty($specificPrice['id_specific_price'])) ? Tools::safeOutput($specificPrice['id_specific_price']) : 'No ID', $err_tmp . ' ' . $error_tmp), 'SpecificPrice');
                            } else {
                                self::addLog('SpecificPrice', $specificPrice['id_specific_price'], $specificPriceObj->id);
                            }
                            $this->showMigrationMessageAndLog($specific_price_error_tmp, 'SpecificPrice');
                        }
                    }

                    // import product_download
                    foreach ($products['product_download'][$product['id_product']] as $productDownload) {
                        $changeDateExpiration = false;
                        if ($productDownloadObject = $this->createObjectModel('ProductDownload', $productDownload['id_product_download'])) {
                            $productDownloadObject->id_product = $productObj->id;
                            $productDownloadObject->display_filename = $productDownload['display_filename'];
                            $productDownloadObject->filename = $productDownload['filename'];
                            $productDownloadObject->date_add = $productDownload['date_add'];
                            if ($productDownload['date_expiration'] == '0000-00-00 00:00:00') {
                                $productDownloadObject->date_expiration = date('Y-m-d H:i:s');
                                $changeDateExpiration = true;
                            } else {
                                $productDownloadObject->date_expiration = $productDownload['date_expiration'];
                            }
                            $productDownloadObject->nb_days_accessible = $productDownload['nb_days_accessible'];
                            $productDownloadObject->nb_downloadable = $productDownload['nb_downloadable'];
                            $productDownloadObject->active = $productDownload['active'];
                            $productDownloadObject->is_shareable = $productDownload['is_shareable'];
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($productDownloadObject);
                            $this->validator->checkFields();
                            $product_download_error_tmp = $this->validator->getValidationMessages();
                            if ($productDownloadObject->id && ProductDownload::existsInDatabase($productDownloadObject->id, 'product_download')) {
                                try {
                                    $res = $productDownloadObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $productDownloadObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError('ProductDownload (ID: %1$s) cannot be saved. Product (ID: %2$s). %3$s'), (isset($productDownload['id_product_download']) && !self::isEmpty($productDownload['id_product_download'])) ? Tools::safeOutput($productDownload['id_product_download']) : 'No ID', $productObj->id, $err_tmp), 'ProductDownload');
                            } else {
                                $client = new EDClient($this->url . '/migration_pro/server.php', Configuration::get('migrationpro_token'));
                                $client->setPostData('download/' . $productDownload['filename']);
                                $client->setTimeout(999);
                                $client->query('file');
                                file_put_contents(getcwd() . '/../download/' . $productDownload['filename'], $client->getContent());
                                if ($changeDateExpiration) {
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'product_download SET date_expiration = \'' . pSQL($productDownload['date_expiration']) . '\' WHERE id_product_download = ' . (int)$productDownloadObject->id);
                                }
                                self::addLog('ProductDownload', $productDownload['id_product_download'], $productDownloadObject->id);
                            }
                            $this->showMigrationMessageAndLog($product_download_error_tmp, 'ProductDownload');
                        }
                    }

                    // import attachments
                    $sql_values = array();
                    foreach ($products['product_attachment'][$product['id_product']] as $productAttachment) {
                        foreach ($products['attachment'] as $attachment) {
                            if ($attachmentObject = $this->createObjectModel('Attachment', $attachment['id_attachment'])) {
                                $attachmentObject->file = $attachment['file'];
                                $fileName = "";
                                if (isset($attachment['filename'])) {
                                    $fileName = $attachment['filename'];
                                } elseif (isset($attachment['file_name'])) {
                                    $fileName = $attachment['file_name'];
                                }
                                $attachmentObject->file_name = $fileName;

                                $fileSize = 0;
                                if (isset($attachment['filesize'])) {
                                    $fileSize = $attachment['filesize'];
                                } elseif (isset($attachment['file_size'])) {
                                    $fileSize = $attachment['file_size'];
                                }
                                $attachmentObject->file_size = $fileSize;
                                $attachmentObject->mime = $attachment['mime'];
                                //language fields
                                foreach ($products['attachment_lang'][$attachment['id_attachment']] as $lang) {
                                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                    $attachmentObject->name[$lang['id_lang']] = $lang['name'];
                                    $attachmentObject->description[$lang['id_lang']] = $lang['description'];
                                }
                                $res = false;
                                $err_tmp = '';

                                $this->validator->setObject($attachmentObject);
                                $this->validator->checkFields();
                                $attachment_error_tmp = $this->validator->getValidationMessages();
                                if ($attachmentObject->id && Attachment::existsInDatabase($attachmentObject->id, 'attachment')) {
                                    try {
                                        $res = $attachmentObject->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }

                                if (!$res) {
                                    try {
                                        $res = $attachmentObject->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(self::displayError('Attachment (ID: %1$s) cannot be saved. Product (ID: %2$s). %3$s'), (isset($attachment['id_attachment']) && !self::isEmpty($attachment['id_attachment'])) ? Tools::safeOutput($attachment['id_attachment']) : 'No ID', $productObj->id, $err_tmp), 'Attachment');
                                } else {
                                    $client = new EDClient($this->url . '/modules/migrationproserver/server.php', Configuration::get('migrationpro_token'));
                                    $client->setPostData('download/' . $attachment['file']);
                                    $client->setTimeout(999);
                                    $client->query('file');
                                    $fileName = getcwd() . '/../download/' . $attachment['file'];
                                    file_put_contents($fileName, $client->getContent());

                                    if ($attachmentObject->file_size == 0) {
                                        $fileSize = filesize($fileName);
                                        $attachmentObject->file_size = self::isEmpty($fileSize) ? 0 : $fileSize;
                                        $attachmentObject->update();
                                    }

                                    self::addLog('Attachment', $attachment['id_attachment'], $attachmentObject->id);
                                }
                                $this->showMigrationMessageAndLog($attachment_error_tmp, 'Attachment');
                            }
                        }
                        //import product_attachments
                        $sql_values[] = '(' . $productObj->id . ', ' . self::getLocalID('attachment', $productAttachment['id_attachment'], 'data') . ')';

                        if (!self::isEmpty($sql_values)) {
                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'product_attachment` (`id_product`, `id_attachment`) VALUES ' . implode(',', $sql_values));
                            if (!$result) {
                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add product_attachment. ' . Db::getInstance()->getMsgError()), 'Attachment');
                            }
                        }
                    }

                    //import product_supplier
                    if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                        if ($productSupplierObject = $this->createObjectModel('ProductSupplier', $product['id_product'])) {
                            $productSupplierObject->id_product = $productObj->id;
                            $productSupplierObject->id_product_attribute = 0;
                            $productSupplierObject->id_supplier = self::getLocalID('supplier', $product['id_supplier'], 'data');
                            $productSupplierObject->product_supplier_price_te = 0;
                            $productSupplierObject->id_currency = 0;
                            $productSupplierObject->product_supplier_reference = $product['supplier_reference'];

                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($productSupplierObject);
                            $this->validator->checkFields();
                            $product_supplier_error_tmp = $this->validator->getValidationMessages();
                            if ($productSupplierObject->id && ProductSupplier::existsInDatabase($productSupplierObject->id, 'product_supplier')) {
                                try {
                                    $res = $productSupplierObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $productSupplierObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Product_Supplier (ID: %1$s) cannot be saved. %2$s')), (isset($product['id_product']) && !self::isEmpty($product['id_product'])) ? Tools::safeOutput($product['id_product']) : 'No ID', $err_tmp), 'ProductSupplier');
                            } else {
                                self::addLog('ProductSupplier', $product['id_product'], $productSupplierObject->id);
                            }
                            $this->showMigrationMessageAndLog($product_supplier_error_tmp, 'ProductSupplier');
                        }
                    } else {
                        foreach ($products['product_supplier'][$product['id_product']] as $productSupplier) {
                            if ($productSupplierObject = $this->createObjectModel('ProductSupplier', $productSupplier['id_product_supplier'])) {
                                $productSupplierObject->id_product = $productObj->id;
                                $productSupplierObject->id_product_attribute = self::getLocalID('combination', $productSupplier['id_product_attribute'], 'data');
                                $productSupplierObject->id_supplier = self::getLocalID('supplier', $productSupplier['id_supplier'], 'data');
                                $productSupplierObject->product_supplier_price_te = $productSupplier['product_supplier_price_te'];
                                $productSupplierObject->id_currency = self::getCurrencyID($productSupplier['id_currency']);
                                $productSupplierObject->product_supplier_reference = isset($productSupplier['product_supplier_reference']) ? $productSupplier['product_supplier_reference'] : $product['supplier_reference'];

                                $res = false;
                                $err_tmp = '';

                                $this->validator->setObject($productSupplierObject);
                                $this->validator->checkFields();
                                $product_supplier_error_tmp = $this->validator->getValidationMessages();
                                if ($productSupplierObject->id && ProductSupplier::existsInDatabase($productSupplierObject->id, 'product_supplier')) {
                                    try {
                                        $res = $productSupplierObject->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $productSupplierObject->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }

                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Product_Supplier (ID: %1$s) cannot be saved. %2$s')), (isset($productSupplier['id_product_supplier']) && !self::isEmpty($productSupplier['id_product_supplier'])) ? Tools::safeOutput($productSupplier['id_product_supplier']) : 'No ID', $err_tmp), 'ProductSupplier');
                                } else {
                                    self::addLog('ProductSupplier', $productSupplier['id_product_supplier'], $productSupplierObject->id);
                                }
                                $this->showMigrationMessageAndLog($product_supplier_error_tmp, 'ProductSupplier');
                            }
                        }
                    }
                    //import feature_product
                    $sql_values = array();
                    foreach ($products['feature_product'][$product['id_product']] as $featureProduct) {
                        Product::addFeatureProductImport($productObj->id, self::getLocalId('feature', (int)$featureProduct['id_feature'], 'data'), self::getLocalId('featurevalue', (int)$featureProduct['id_feature_value'], 'data'));
                    }
                    //import customization_field
                    foreach ($products['customization_field'][$product['id_product']] as $customizationField) {
                        if ($customizationFieldModel = $this->createObjectModel('CustomizationField', $customizationField['id_customization_field'])) {
                            $customizationFieldModel->id_product = $productObj->id;
                            $customizationFieldModel->type = $customizationField['type'];
                            $customizationFieldModel->required = $customizationField['required'];

                            foreach ($products['customization_field_lang'][$customizationField['id_customization_field']] as $lang) {
                                $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                $customizationFieldModel->name[$lang['id_lang']] = $lang['name'];
                                if (self::isEmpty($customizationFieldModel->name[$lang['id_lang']])) {
                                    $customizationFieldModel->name[$lang['id_lang']] = 'Empty';
                                }
                            }

                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($customizationFieldModel);
                            $this->validator->checkFields();
                            $customization_field_error_tmp = $this->validator->getValidationMessages();
                            if ($customizationFieldModel->id && CustomizationField::existsInDatabase($customizationFieldModel->id, 'customization_field')) {
                                try {
                                    $res = $customizationFieldModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $customizationFieldModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError('CustomizationField (ID: %1$s) from Product (ID: %2$s) cannot be saved. %3$s'), $productObj->id, (isset($customizationField['id_customization_field']) && !self::isEmpty($customizationField['id_customization_field'])) ? Tools::safeOutput($customizationField['id_customization_field']) : 'No ID', $err_tmp), 'CustomizationField');
                            } else {
                                self::addLog('CustomizationField', $customizationField['id_customization_field'], $customizationFieldModel->id);
                            }
                            $this->showMigrationMessageAndLog($customization_field_error_tmp, 'CustomizationField');
                        }
                    }
                    //import product_tag
//                        Tag::deleteTagsForProduct($productObj->id);
                    $sql_values = array();
                    foreach ($products['product_tag'][$product['id_product']] as $productTag) {
                        // if ($productTag['id_product'] == $product['id_product']) {
//                                $idLangTag = (isset($tagPS14[$productTag['id_tag']]) && !self::isEmpty
//                                    ($tagPS14[$productTag['id_tag']])) ? self::getLocalID('tag', (int)$tagPS14[$productTag['id_tag']], 'data') : 0; //@TODO not id lang field on PS 1.4
                        $sql_values[] = '(' . (int)$productObj->id . ', ' . self::getLocalID('tag', (int)$productTag['id_tag'], 'data') . ', ' . self::getLanguageID($productTag['id_lang']) . ')';
                        //}
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'product_tag` (`id_product`, `id_tag`, `id_lang`)
                                VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add product_tag. ' . Db::getInstance()->getMsgError()), 'Product');
                        }
                    }


                    if (count($this->error_msg) == 0) {
                        self::addLog('Product', $product['id_product'], $productObj->id);

                        //update multistore language fields
                        if (!version_compare($this->version, '1.5', '<')) {
                            if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                                foreach ($products['product_lang'][$product['id_product']] as $lang) {
                                    $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                    $lang['id_product'] = $productObj->id;
                                    self::updateMultiStoreLang('product', $lang);
                                }
                            }
                        }

                        Module::processDeferedFuncCall();
                        Module::processDeferedClearCache();
                        Tag::updateTagCount();
                    }
                }
                $this->showMigrationMessageAndLog($product_error_tmp, 'Product');
            }
        }
        #endregion
        $this->updateProcess($count);
    }

    /**
     * @param $accessories
     */
    public function accessories($accessories)
    {
        $count = 0;
        foreach ($accessories as $accessory) {
            if (!Configuration::get($this->module->name . '_pause')) {
                $count++;

                $accessory_1 = self::getLocalID('product', $accessory['id_product_1'], 'data');
                $accessory_2 = self::getLocalID('product', $accessory['id_product_2'], 'data');


                if (!self::isEmpty($accessory_1) && !self::isEmpty($accessory_2)) {
                    $res = self::importAccessories($accessory);

                    if (!$res) {
                        $this->showMigrationMessageAndLog(self::displayError('Can\'t add accessory. ' . Db::getInstance()->getMsgError()), 'Product');
                    }
                } else {
                    if (self::isEmpty($accessory_1)) {
                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Accessory (ID: %1$s) not found in source store')), (isset($accessory['id_product_1']) && !self::isEmpty($accessory['id_product_1'])) ? Tools::safeOutput($accessory['id_product_1']) : 'No ID'), 'Product');
                    }
                    if (self::isEmpty($accessory_2)) {
                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Accessory (ID: %1$s) not found in source store')), (isset($accessory['id_product_2']) && !self::isEmpty($accessory['id_product_2'])) ? Tools::safeOutput($accessory['id_product_2']) : 'No ID'), 'Product');
                    }
                }
            }
        }

        $this->updateProcess($count);
    }

    /**
     * @param $specificPriceRules
     * @param $specificPriceRuleConditionGroups
     * @param $specificPriceRuleConditions
     */

    public function catalogPriceRules($specificPriceRules)
    {
        $count = 0;
        // Import Country
        foreach ($specificPriceRules['country'] as $country) {
            if ($countryModel = $this->createObjectModel('Country', $country['id_country'])) {
                $countryModel->id_zone = $country['id_zone'];
                $countryModel->id_currency = self::getCurrencyID($country['id_currency']);
                $countryModel->call_prefix = $country['call_prefix'];
                $countryModel->iso_code = $country['iso_code'];
                $countryModel->active = $country['active'];
                $countryModel->contains_states = $country['contains_states'];
                $countryModel->need_identification_number = $country['need_identification_number'];
                $countryModel->need_zip_code = $country['need_zip_code'];
                $countryModel->zip_code_format = $country['zip_code_format'];
                $countryModel->display_tax_label = (isset($country['display_tax_label'])) ? (bool)$country['display_tax_label'] : true;

                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $country['id_shop_list']), '');
                $countryModel->id_shop_list = $id_shop_list;


                // Language fields
                foreach ($specificPriceRules['country_lang'][$country['id_country']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $countryModel->name[$lang['id_lang']] = $lang['name'];
                }

                $res = false;
                $err_tmp = '';

                $this->validator->setObject($countryModel);
                $this->validator->checkFields();
                $country_error_tmp = $this->validator->getValidationMessages();
                if ($countryModel->id && Country::existsInDatabase($countryModel->id, 'country')) {
                    try {
                        $res = $countryModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $countryModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Country (ID: %1$s) cannot be saved. %2$s')), (isset($country['id_country']) && !self::isEmpty($country['id_country'])) ? Tools::safeOutput($country['id_country']) : 'No ID', $err_tmp), 'Country');
                } else {
                    self::addLog('Country', $country['id_country'], $countryModel->id);
                }
                $this->showMigrationMessageAndLog($country_error_tmp, 'Country');
            }
        }

        foreach ($specificPriceRules['specificPriceRule'] as $specificPriceRule) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($specificPriceRuleObj = $this->createObjectModel('SpecificPriceRule', $specificPriceRule['id_specific_price_rule'])) {
                $specificPriceRuleObj->name = $specificPriceRule['name'];
                $specificPriceRuleObj->id_shop = self::getShopID($specificPriceRule['id_shop']);
                $specificPriceRuleObj->id_currency = self::getCurrencyID($specificPriceRule['id_currency']);
                $specificPriceRuleObj->id_country = self::getLocalID('country', $specificPriceRule['id_country'], 'data');
                $specificPriceRuleObj->id_group = self::getCustomerGroupID($specificPriceRule['id_group']);
                $specificPriceRuleObj->from_quantity = $specificPriceRule['from_quantity'];
                $specificPriceRuleObj->price = $specificPriceRule['price'];
                $specificPriceRuleObj->reduction = $specificPriceRule['reduction'];
                if (self::isEmpty($specificPriceRule['reduction_tax'])) {
                    $specificPriceRuleObj->reduction_tax = 0;
                } else {
                    $specificPriceRuleObj->reduction_tax = $specificPriceRule['reduction_tax'];
                }
                $specificPriceRuleObj->reduction_type = $specificPriceRule['reduction_type'];
                $specificPriceRuleObj->from = $specificPriceRule['from'];
                $specificPriceRuleObj->to = $specificPriceRule['to'];

                $res = false;
                $err_tmp = '';

                $this->validator->setObject($specificPriceRuleObj);
                $this->validator->checkFields();
                $specific_price_rule_error_tmp = $this->validator->getValidationMessages();
                if ($specificPriceRuleObj->id && SpecificPriceRule::existsInDatabase($specificPriceRuleObj->id, 'specific_price_rule')) {
                    try {
                        $res = $specificPriceRuleObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $specificPriceRuleObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Specific price rule(ID: %1$s) cannot be saved. %2$s')), (isset($specificPriceRule['id_specific_price_rule']) && !self::isEmpty($specificPriceRule['id_specific_price_rule'])) ? Tools::safeOutput($specificPriceRule['id_specific_price_rule']) : 'No ID', $err_tmp), 'SpecificPriceRule');
                } else {
                    // Import Specific Price Rule Condition Groups
                    foreach ($specificPriceRules['specificPriceRuleConditionGroup'][$specificPriceRule['id_specific_price_rule']] as $specificPriceRuleConditionGroup) {
                        $sql_value = '';
                        if ($specificPriceRuleConditionGroup['id_specific_price_rule'] == $specificPriceRule['id_specific_price_rule']) {
                            $sql_value = '(' . (int)$specificPriceRuleObj->id . ')';
                        }
                        if (!self::isEmpty($sql_value)) {
                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'specific_price_rule_condition_group` (`id_specific_price_rule`)
                            VALUES ' . $sql_value);
                            if (!$result) {
                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add specific_price_rule_condition_group. ' . Db::getInstance()->getMsgError()), 'SpecificPriceRule');
                            } else {
                                $id_specific_price_rule_condition_group = Db::getInstance()->Insert_ID();

                                // Import Specific Price Rule Conditions
                                foreach ($specificPriceRules['specificPriceRuleCondition'][$specificPriceRuleConditionGroup['id_specific_price_rule_condition_group']] as $specificPriceRuleCondition) {
                                    $sql_value = '';
                                    if (preg_match('|category|', $specificPriceRuleCondition['type'])) {
                                        $value = self::getLocalID('category', $specificPriceRuleCondition['value'], 'data');
                                    } elseif (preg_match('|manufacturer|', $specificPriceRuleCondition['type'])) {
                                        $value = self::getLocalID('manufacturer', $specificPriceRuleCondition['value'], 'data');
                                    } elseif (preg_match('|supplier|', $specificPriceRuleCondition['type'])) {
                                        $value = self::getLocalID('supplier', $specificPriceRuleCondition['value'], 'data');
                                    } elseif (preg_match('|attribute|', $specificPriceRuleCondition['type'])) {
                                        $value = self::getLocalID('attribute', $specificPriceRuleCondition['value'], 'data');
                                    } elseif (preg_match('|feature|', $specificPriceRuleCondition['type'])) {
                                        $value = self::getLocalID('feature', $specificPriceRuleCondition['value'], 'data');
                                    }

                                    $sql_value = '(' . (int)$id_specific_price_rule_condition_group . ', \'' . pSQL($specificPriceRuleCondition['type']) . '\', ' . (int)$value . ')';

                                    if (!self::isEmpty($sql_value)) {
                                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'specific_price_rule_condition` (`id_specific_price_rule_condition_group`, `type`, `value`)
                            VALUES ' . $sql_value);
                                        if (!$result) {
                                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add specific_price_rule_condition. ' . Db::getInstance()->getMsgError()), 'specificPriceRuleCondition');
                                        } else {
                                            $d_specific_price_rule_condition = Db::getInstance()->Insert_ID();
                                            self::addLog('specificPriceRuleCondition', $specificPriceRuleCondition['id_specific_price_rule_condition'], $d_specific_price_rule_condition);
                                        }
                                    }
                                }
                                self::addLog('specificPriceRuleConditionGroup', $specificPriceRuleConditionGroup['id_specific_price_rule_condition_group'], $id_specific_price_rule_condition_group);
                            }
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        self::addLog('SpecificPriceRule', $specificPriceRule['id_specific_price_rule'], $specificPriceRuleObj->id);
                    }
                }
                $this->showMigrationMessageAndLog($specific_price_rule_error_tmp, 'SpecificPriceRule');
            }
        }

        $this->updateProcess($count);
    }

    /**
     * @param $employees
     * @param $employeesShop
     */
    public function employees($employees)
    {
        //Load images for employees to temporary dir
        $this->loadImagesToLocal($employees, 'id_employee', 'employees', $this->url, $this->image_path);
        $count = 0;

        foreach ($employees as $employee) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if (Employee::employeeExists($employee['email'])) {
                continue;
            }
            if ($employeeObject = $this->createObjectModel('Employee', $employee['id_employee'])) {
                $employeeObject->id_profile = $employee['id_profile'];
                $employeeObject->id_lang = self::getLanguageID($employee['id_lang']);
                $employeeObject->lastname = $employee['lastname'];
                $employeeObject->firstname = $employee['firstname'];
                $employeeObject->email = $employee['email'];
                $employeeObject->passwd = $employee['passwd'];
                $employeeObject->last_passwd_gen = $employee['last_passwd_gen'];
                $employeeObject->stats_date_from = $employee['stats_date_from'];
                $employeeObject->stats_date_to = $employee['stats_date_to'];
                $employeeObject->bo_color = $employee['bo_color'];
                $employeeObject->bo_theme = $employee['bo_theme'];
                $employeeObject->default_tab = $employee['default_tab'];
                $employeeObject->bo_width = $employee['bo_width'];
                $employeeObject->active = $employee['active'];
                $employeeObject->id_last_order = self::getLocalID('order', $employee['id_last_order'], 'data');
                $employeeObject->id_last_customer_message = self::getLocalID('customerMessage', $employee['id_last_customer_message'], 'data');
                $employeeObject->id_last_customer = self::getLocalID('customer', $employee['id_last_customer'], 'data');
                $employeeObject->preselect_date_range = $employee['preselect_date_range'];
                $employeeObject->bo_css = $employee['bo_css'];
                $employeeObject->bo_menu = $employee['bo_menu'];
                $employeeObject->optin = $employee['optin'];
                $employeeObject->last_connection_date = $employee['last_connection_date'];


                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $employee['id_shop_list']), '');
                $employeeObject->id_shop_list = $id_shop_list;


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($employeeObject);
                $this->validator->checkFields();
                $employee_error_tmp = $this->validator->getValidationMessages();
                if ($employeeObject->id && Employee::existsInDatabase($employeeObject->id, 'employee')) {
                    try {
                        $res = $employeeObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $employeeObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Employee (ID: %1$s) cannot be saved. %2$s')), (isset($employee['id_employee']) && !self::isEmpty($employee['id_employee'])) ? Tools::safeOutput($employee['id_employee']) : 'No ID', $err_tmp), 'Employee');
                } else {
                    $url = $this->url . $this->image_path . $employee['id_employee'] . '.jpg';

                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/employees/' . $employee['id_employee'] . '.jpg';

                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($employeeObject->id, null, $FilePath, 'employees', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'Employee', true);
                    }
                    self::addLog('Employee', $employee['id_employee'], $employeeObject->id);
                }
                $this->showMigrationMessageAndLog($employee_error_tmp, 'Employee');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $customers
     * @param $addresses
     * @param $carts
     * @param $cartProducts
     * @param $countryState
     */
    public function customers($customers)
    {
        $count = 0;
        foreach ($customers['customers'] as $customer) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($customerObject = $this->createObjectModel('Customer', $customer['id_customer'])) {
                $customerObject->secure_key = $customer['secure_key'];
                $customerObject->lastname = $customer['lastname'];
                $customerObject->firstname = $customer['firstname'];
                $customerObject->email = $customer['email'];
                $customerObject->passwd = $customer['passwd'];
                $customerObject->last_passwd_gen = $customer['last_passwd_gen'];
                $customerObject->id_gender = $customer['id_gender'];
                $customerObject->birthday = $customer['birthday'];
                $customerObject->newsletter = $customer['newsletter'];
                $customerObject->newsletter_date_add = $customer['newsletter_date_add'];
                $customerObject->optin = $customer['optin'];
                $customerObject->active = $customer['active'];
                $customerObject->deleted = $customer['deleted'];
                $customerObject->note = $customer['note'];
                $customerObject->is_guest = $customer['is_guest'];
                $customerObject->id_default_group = self::getCustomerGroupID($customer['id_default_group']);
                $customerObject->date_add = $customer['date_add'];
                $customerObject->date_upd = $customer['date_upd'];
                if ($this->version >= 1.5) {
                    $customerObject->ip_registration_newsletter = $customer['ip_registration_newsletter'];
                    $customerObject->website = $customer['website'];
                    $customerObject->company = $customer['company'];
                    $customerObject->siret = $customer['siret'];
                    $customerObject->ape = $customer['ape'];
                    $customerObject->outstanding_allow_amount = $customer['outstanding_allow_amount'];
                    $customerObject->show_public_prices = $customer['show_public_prices'];
                    $customerObject->id_risk = $customer['id_risk'];
                    $customerObject->max_payment_days = $customer['max_payment_days'];
                    $customerObject->id_shop = self::getShopID($customer['id_shop']);
                    $customerObject->id_shop_group = $customer['id_shop_group'];
                    $customerObject->id_lang = self::getLanguageID($customer['id_lang']);
                    $customerObject->reset_password_token = $customer['reset_password_token'];
                    $customerObject->reset_password_validity = $customer['reset_password_validity'];
                }
                Configuration::updateValue('PS_GUEST_CHECKOUT_ENABLED', 1);
                $res = false;
                $err_tmp = '';

                $this->validator->setObject($customerObject);
                $this->validator->checkFields();
                $customer_error_tmp = $this->validator->getValidationMessages();
                if ($customerObject->id && Customer::existsInDatabase($customerObject->id, 'customer')) {
                    try {
                        $res = $customerObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $customerObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Customer (ID: %1$s) cannot be saved. %2$s')), (isset($customer['id_customer']) && !self::isEmpty($customer['id_customer'])) ? Tools::safeOutput($customer['id_customer']) : 'No ID', $err_tmp), 'Customer');
                } else {
//                      import customer_groups
                    $sql_values = array();
                    foreach ($customers['customer_group'][$customer['id_customer']] as $customerGroup) {
                        $sql_values[] = '(' . (int)$customerObject->id . ', ' . self::getCustomerGroupID($customerGroup['id_group']) . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'customer_group` (`id_customer`, `id_group`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add customer_group. ' . Db::getInstance()->getMsgError()), 'Customer');
                        }
                    }
                    // Import Country
                    #region Import Countries and States

                    foreach ($customers['country'] as $country) {
                        if ($countryModel = $this->createObjectModel('Country', $country['id_country'])) {
                            $countryModel->id_zone = $country['id_zone'];
                            $countryModel->id_currency = self::getCurrencyID($country['id_currency']);
                            $countryModel->call_prefix = $country['call_prefix'];
                            $countryModel->iso_code = $country['iso_code'];
                            $countryModel->active = $country['active'];
                            $countryModel->contains_states = $country['contains_states'];
                            $countryModel->need_identification_number = $country['need_identification_number'];
                            $countryModel->need_zip_code = $country['need_zip_code'];
                            $countryModel->zip_code_format = $country['zip_code_format'];
                            $countryModel->display_tax_label = (isset($country['display_tax_label'])) ? (bool)$country['display_tax_label'] : true;

                            // Language fields
                            foreach ($customers['country_lang'][$country['id_country']] as $lang) {
                                $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                $countryModel->name[$lang['id_lang']] = $lang['name'];
                            }

                            // Add to _shop relations
                            $id_shop_list = $this->getChangedIdShop(explode(',', $country['id_shop_list']), '');
                            $countryModel->id_shop_list = $id_shop_list;

                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($countryModel);
                            $this->validator->checkFields();
                            $country_error_tmp = $this->validator->getValidationMessages();
                            if ($countryModel->id && Country::existsInDatabase($countryModel->id, 'country')) {
                                try {
                                    $res = $countryModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $countryModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Country (ID: %1$s) cannot be saved. %2$s')), (isset($country['id_country']) && !self::isEmpty($country['id_country'])) ? Tools::safeOutput($country['id_country']) : 'No ID', $err_tmp), 'Country');
                            } else {
                                self::addLog('Country', $country['id_country'], $countryModel->id);
                            }
                            $this->showMigrationMessageAndLog($country_error_tmp, 'Country');
                        }
                    }
                    // Import State
                    foreach ($customers['state'] as $state) {
                        if ($stateModel = $this->createObjectModel('State', $state['id_state'])) {
                            $stateModel->id_country = self::getLocalId('country', $state['id_country'], 'data');
                            $stateModel->id_zone = $state['id_zone'];
                            $stateModel->iso_code = $state['iso_code'];
                            $stateModel->active = $state['active'];
                            $stateModel->name = $state['name'];

                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($stateModel);
                            $this->validator->checkFields();
                            $state_error_tmp = $this->validator->getValidationMessages();
                            if ($stateModel->id && State::existsInDatabase($stateModel->id, 'state')) {
                                try {
                                    $res = $stateModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $stateModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('State (ID: %1$s) cannot be saved. %2$s')), (isset($state['id_state']) && !self::isEmpty($state['id_state'])) ? Tools::safeOutput($state['id_state']) : 'No ID', $err_tmp), 'State');
                            } else {
                                self::addLog('State', $state['id_state'], $stateModel->id);
                            }
                            $this->showMigrationMessageAndLog($state_error_tmp, 'State');
                        }
                    }


                    #endregion
                    // Import Address
                    foreach ($customers['address'][$customer['id_customer']] as $address) {
                        if ($addressObject = $this->createObjectModel('Address', $address['id_address'])) {
                            $addressObject->id_customer = $customerObject->id;
                            $addressObject->id_manufacturer = self::getLocalId('manufacturer', $address['id_manufacturer'], 'data');
                            $addressObject->id_supplier = self::getLocalId('supplier', $address['id_supplier'], 'data');
                            $addressObject->id_country = $address['id_country'];
                            $addressObject->id_state = $address['id_state'];
                            $addressObject->alias = $address['alias'];
                            $addressObject->company = $address['company'];
                            $addressObject->lastname = $address['lastname'];
                            $addressObject->firstname = $address['firstname'];
                            $addressObject->vat_number = $address['vat_number'];
                            $addressObject->address1 = $address['address1'];
                            $addressObject->address2 = $address['address2'];
                            $addressObject->postcode = $address['postcode'];
                            $addressObject->city = $address['city'];
                            $addressObject->other = $address['other'];
                            $addressObject->phone = $address['phone'];
                            $addressObject->phone_mobile = $address['phone_mobile'];
                            $addressObject->dni = $address['dni'];
                            $addressObject->deleted = $address['deleted'];
                            $addressObject->date_add = $address['date_add'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $address['date_add'];
                            $addressObject->date_upd = $address['date_upd'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $address['date_upd'];
//                                    if ($this->version >= 1.5) {
                            $addressObject->id_warehouse = (isset($address['id_warehouse']) && !self::isEmpty($address['id_warehouse'])) ? $address['id_warehouse'] : null;
//                                    }

                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($addressObject);
                            $this->validator->checkFields();
                            $address_error_tmp = $this->validator->getValidationMessages();
                            if ($addressObject->id && Address::existsInDatabase($addressObject->id, 'address')) {
                                try {
                                    $res = $addressObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $addressObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Address (ID: %1$s) cannot be saved. %2$s')), (isset($address['id_address']) && !self::isEmpty($address['id_address'])) ? Tools::safeOutput($address['id_address']) : 'No ID', $err_tmp), 'Address');
                            } else {
                                self::addLog('Address', $address['id_address'], $addressObject->id);
                            }
                            $this->showMigrationMessageAndLog($address_error_tmp, 'Address');
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        self::addLog('Customer', $customer['id_customer'], $customerObject->id);
                        MigrationProPassLog::storeCustomerPass($customerObject->id, $customer['email'], $customer['passwd']);
                    }
                }
                $this->showMigrationMessageAndLog($customer_error_tmp, 'Customer');
            }
        }

        // Import Cart
        foreach ($customers['cart'] as $cart) {
            if ($cartObject = $this->createObjectModel('Cart', $cart['id_cart'])) {
                $cartObject->id_shop = self::getShopID($cart['id_shop']);
                $cartObject->id_shop_group = Shop::getGroupFromShop($cartObject->id_shop);
                $cartObject->id_carrier = $cart['id_carrier'];
                $cartObject->delivery_option = $cart['delivery_option'];
                $cartObject->id_lang = self::getLanguageID($cart['id_lang']);
                $cartObject->id_address_delivery = self::getLocalID('address', $cart['id_address_delivery'], 'data');
                $cartObject->id_address_invoice = self::getLocalID('address', $cart['id_address_invoice'], 'data');
                $cartObject->id_currency = self::getCurrencyID($cart['id_currency']);
                $cartObject->id_customer = self::getLocalID('customer', $cart['id_customer'], 'data');
                $cartObject->id_guest = $cart['id_guest'];
                if (!self::isEmpty($cartObject->id_customer)) {
                    $customerObj = new Customer($cartObject->id_customer);
                    $cartObject->secure_key = $customerObj->secure_key;
                } else {
                    if (!self::isEmpty($cart['secure_key'])) {
                        $cartObject->secure_key = $cart['secure_key'];
                    } else {
                        $cartObject->secure_key = md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));
                        $this->showMigrationMessageAndLog('Secure key of cart with ID ' . $cart['id_cart'] ? $cart['id_cart'] : 'No ID' . ' is empty. For that reason, the module set default value as a secure key.', 'Cart', true);
                    }
                }
                $cartObject->recyclable = $cart['recyclable'];
                $cartObject->gift = $cart['gift'];
                $cartObject->gift_message = $cart['gift_message'];
                $cartObject->mobile_theme = isset($cart['mobile_theme']) ? $cart['mobile_theme'] : null;
                $cartObject->allow_seperated_package = isset($cart['allow_seperated_package ']) ? $cart['allow_seperated_package '] : null;
                $cartObject->date_add = $cart['date_add'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $cart['date_add'];
                $cartObject->date_upd = $cart['date_upd'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $cart['date_upd'];

                $res = false;
                $err_tmp = '';

                $this->validator->setObject($cartObject);
                $this->validator->checkFields();
                $cart_error_tmp = $this->validator->getValidationMessages();
                if ($cartObject->id && Cart::existsInDatabase($cartObject->id, 'cart')) {
                    try {
                        $res = $cartObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $cartObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Cart (ID: %1$s) cannot be saved. %2$s')), (isset($cart['id_cart']) && !self::isEmpty($cart['id_cart'])) ? Tools::safeOutput($cart['id_cart']) : 'No ID', $err_tmp), 'Cart');
                } else {
                    // Import Cart Product
                    $sql_values = array();
                    foreach ($customers['cart_product'][$cart['id_cart']] as $cartProduct) {
                        if (!self::isEmpty($cartProduct['id_shop'])) {
                            $shopIdOfCartProduct = self::getShopID($cartProduct['id_shop']);
                        } else {
                            $shopIdOfCartProduct = (int)Configuration::get('PS_SHOP_DEFAULT');
                        }
                        $sql_values[] = '(' . (int)$cartObject->id . ', ' . self::getLocalID('product', $cartProduct['id_product'], 'data') . ', ' . self::getLocalID('address', $cartProduct['id_address_delivery'], 'data') . ', ' . $shopIdOfCartProduct . ', ' . self::getLocalID('combination', $cartProduct['id_product_attribute'], 'data') . ' , ' . (int)$cartProduct['quantity'] . ', \'' . pSQL($cartProduct['date_add']) . '\')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_product` (`id_cart`, `id_product`,
                                                    `id_address_delivery`, `id_shop`, `id_product_attribute`,
                                                    `quantity`, `date_add`)
                                                    VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_product. ' . Db::getInstance()->getMsgError()), 'Cart');
                        }
                    }

                    // Import Cart Cart_Rule
                    $sql_values = array();
                    foreach ($customers['cart_cart_rule'][$cart['id_cart']] as $cartCartRule) {
                        $sql_values[] = '(' . (int)$cartObject->id . ', ' . self::getLocalID('cartRule', $cartCartRule['id_cart_rule'], 'data') . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_cart_rule` (`id_cart`, `id_cart_rule`)
                                                    VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_cart_rule. ' . Db::getInstance()->getMsgError()), 'Cart');
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        self::addLog('Cart', $cart['id_cart'], $cartObject->id);
                    }
                }
                $this->showMigrationMessageAndLog($cart_error_tmp, 'Cart');
            }
        }
        $this->updateProcess($count);
    }

    public function cartrules($cartRules)
    {
        $count = 0;
        // Import Cart Rule
        foreach ($cartRules['cartRule'] as $cartRule) {
            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $cartRuleid = $cartRule['id_discount'];
            } else {
                $cartRuleid = $cartRule['id_cart_rule'];
            }
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($cartRuleObj = $this->createObjectModel('CartRule', $cartRuleid)) {
                $cartRuleObj->id_customer = self::getLocalID('customer', $cartRule['id_customer'], 'data');
                $cartRuleObj->date_from = $cartRule['date_from'];
                $cartRuleObj->date_to = $cartRule['date_to'];
                $cartRuleObj->description = isset($cartRule['description']) ? $cartRule['description'] : '';
                $cartRuleObj->quantity = $cartRule['quantity'];
                $cartRuleObj->quantity_per_user = $cartRule['quantity_per_user'];
                $cartRuleObj->priority = isset($cartRule['priority']) ? $cartRule['priority'] : 1;
                $cartRuleObj->partial_use = isset($cartRule['partial_use']) ? $cartRule['partial_use'] : 0;
                $cartRuleObj->code = isset($cartRule['code']) ? $cartRule['code'] : 0;
                $cartRuleObj->minimum_amount = isset($cartRule['minimum_amount']) ? $cartRule['minimum_amount'] : 0;
                $cartRuleObj->minimum_amount_tax = isset($cartRule['minimum_amount_tax']) ? $cartRule['minimum_amount_tax'] : 0;
                $cartRuleObj->minimum_amount_currency = isset($cartRule['minimum_amount_currency']) ? self::getCurrencyID($cartRule['minimum_amount_currency']) : 0;
                $cartRuleObj->minimum_amount_shipping = isset($cartRule['minimum_amount_shipping']) ? $cartRule['minimum_amount_shipping'] : 0;
                $cartRuleObj->country_restriction = isset($cartRule['country_restriction']) ? $cartRule['country_restriction'] : 0;
                $cartRuleObj->carrier_restriction = isset($cartRule['carrier_restriction']) ? $cartRule['carrier_restriction'] : 0;
                $cartRuleObj->group_restriction = isset($cartRule['group_restriction']) ? $cartRule['group_restriction'] : 0;
                $cartRuleObj->cart_rule_restriction = isset($cartRule['cart_rule_restriction']) ? $cartRule['cart_rule_restriction'] : 0;
                $cartRuleObj->product_restriction = isset($cartRule['product_restriction']) ? $cartRule['product_restriction'] : 0;
                $cartRuleObj->shop_restriction = isset($cartRule['shop_restriction']) ? $cartRule['shop_restriction'] : 0;
                $cartRuleObj->free_shipping = isset($cartRule['free_shipping']) ? $cartRule['free_shipping'] : 0;
                $cartRuleObj->reduction_percent = isset($cartRule['reduction_percent']) ? $cartRule['reduction_percent'] : 0;
                $cartRuleObj->reduction_amount = isset($cartRule['reduction_amount']) ? $cartRule['reduction_amount'] : 0;
                $cartRuleObj->reduction_tax = isset($cartRule['reduction_tax']) ? $cartRule['reduction_tax'] : 0;
                $cartRuleObj->reduction_currency = isset($cartRule['reduction_currency']) ? self::getCurrencyID($cartRule['reduction_currency']) : 0;
                $cartRuleObj->reduction_product = isset($cartRule['reduction_product']) ? self::getLocalID('product', $cartRule['reduction_product'], 'data') : 0;
                $cartRuleObj->gift_product = isset($cartRule['gift_product']) ? self::getLocalID('product', $cartRule['gift_product'], 'data') : 0;
                $cartRuleObj->gift_product_attribute = isset($cartRule['gift_product_attribute']) ? self::getLocalID('combination', $cartRule['gift_product_attribute'], 'data') : 0;
                $cartRuleObj->highlight = isset($cartRule['highlight']) ? $cartRule['highlight'] : 0;
                $cartRuleObj->active = isset($cartRule['active']) ? $cartRule['active'] : 0;
                $cartRuleObj->date_add = $cartRule['date_add'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $cartRule['date_add'];
                $cartRuleObj->date_upd = $cartRule['date_upd'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $cartRule['date_upd'];
                if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                    foreach ($cartRules['cart_rule_langs'][$cartRuleid] as $lang) {
                        $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                        $cartRuleObj->name[$lang['id_lang']] = $lang['description'];
                    }
                } else {
                    foreach ($cartRules['cart_rule_langs'][$cartRuleid] as $lang) {
                        $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                        $cartRuleObj->name[$lang['id_lang']] = $lang['name'];
                    }
                }

                if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                    $cartRuleObj->priority = 1;
                    $cartRuleObj->partial_use = 1;
                    $cartRuleObj->code = $cartRule['name'];
                    $cartRuleObj->minimum_amount = $cartRule['minimal'];
                    $cartRuleObj->minimum_amount_tax = 0;
                    $cartRuleObj->minimum_amount_currency = Configuration::get('PS_CURRENCY_DEFAULT');
                    $cartRuleObj->minimum_amount_shipping = 0;
                    $cartRuleObj->country_restriction = 0;
                    $cartRuleObj->carrier_restriction = 0;
                    $cartRuleObj->group_restriction = 0;
                    $cartRuleObj->cart_rule_restriction = 0;
                    $cartRuleObj->product_restriction = 0;
                    $cartRuleObj->shop_restriction = 0;
                    $cartRuleObj->free_shipping = 0;
                    $cartRuleObj->reduction_percent = 0;
                    $cartRuleObj->reduction_amount = 0;
                    $cartRuleObj->reduction_tax = $cartRule['include_tax'];
                    if ($cartRule['id_discount_type'] == 3) {
                        $cartRuleObj->free_shipping = 1;
                    } elseif ($cartRule['id_discount_type'] == 1) {
                        $cartRuleObj->reduction_percent = $cartRule['value'];
                    } elseif ($cartRule['id_discount_type'] == 2) {
                        $cartRuleObj->reduction_amount = $cartRule['value'];
                    }
                    if ($cartRule['id_discount_type'] == 2 && $cartRule['include_tax'] != 0) {
                    } else {
                        $cartRuleObj->reduction_tax = 0;
                    }
                    $cartRuleObj->reduction_currency = self::getCurrencyID($cartRule['id_currency']);
                    $cartRuleObj->reduction_product = 0;
                    $cartRuleObj->gift_product = 0;
                    $cartRuleObj->gift_product_attribute = 0;
                    $cartRuleObj->highlight = 0;
                }

                $res = false;
                $err_tmp = '';
                $this->validator->setObject($cartRuleObj);
                $this->validator->checkFields();
                $cart_rule_error_tmp = $this->validator->getValidationMessages();
                if ($cartRuleObj->id && CartRule::existsInDatabase($cartRuleObj->id, 'cart_rule')) {
                    try {
                        $res = $cartRuleObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $cartRuleObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Cart rule (ID: %1$s) cannot be saved. %2$s')), (isset($cartRuleid) && !self::isEmpty($cartRuleid)) ? Tools::safeOutput($cartRuleid) : 'No ID', $err_tmp), 'CARTRULE', true);
                } else {
                    // Import Cart Rule Carrier
                    $sql_values = array();
                    foreach ($cartRules['cart_rule_carriers'][$cartRuleid] as $cartRuleCarrier) {
                        $sql_values[] = '(' . (int)$cartRuleObj->id . ', ' . self::getLocalID('carrier', $cartRuleCarrier['id_carrier'], 'data') . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_carrier` (`id_cart_rule`, `id_carrier`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_carrier. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                        }
                    }

                    // Import Cart Rule Country
                    $sql_values = array();
                    foreach ($cartRules['cart_rule_countries'][$cartRuleid] as $cartRuleCountry) {
                        // Import Country
                        foreach ($cartRules['country'] as $country) {
                            if ($country['id_country'] == $cartRuleCountry['id_country']) {
                                if ($countryModel = $this->createObjectModel('Country', $country['id_country'])) {
                                    $countryModel->id_zone = $country['id_zone'];
                                    $countryModel->id_currency = self::getCurrencyID($country['id_currency']);
                                    $countryModel->call_prefix = $country['call_prefix'];
                                    $countryModel->iso_code = $country['iso_code'];
                                    $countryModel->active = $country['active'];
                                    $countryModel->contains_states = $country['contains_states'];
                                    $countryModel->need_identification_number = $country['need_identification_number'];
                                    $countryModel->need_zip_code = $country['need_zip_code'];
                                    $countryModel->zip_code_format = $country['zip_code_format'];
                                    $countryModel->display_tax_label = (isset($country['display_tax_label'])) ? (bool)$country['display_tax_label'] : true;

                                    // Add to _shop relations
                                    $countriesShopsRelations = $this->getChangedIdShop($cartRules['country_shop'], 'id_country');
                                    if (array_key_exists($country['id_country'], $countriesShopsRelations)) {
                                        $countryModel->id_shop_list = array_values($countriesShopsRelations[$country['id_country']]);
                                    }


                                    //language fields
                                    foreach ($cartRules['country_lang'] as $lang) {
                                        if ($lang['id_country'] == $country['id_country']) {
                                            $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                            $countryModel->name[$lang['id_lang']] = $lang['name'];
                                        }
                                    }

                                    $res = false;
                                    $err_tmp = '';
                                    $this->validator->setObject($cartRuleObj);
                                    $this->validator->checkFields();
                                    $country_error_tmp = $this->validator->getValidationMessages();
                                    if ($countryModel->id && Country::existsInDatabase($countryModel->id, 'country')) {
                                        try {
                                            $res = $countryModel->update();
                                        } catch (PrestaShopException $e) {
                                            $err_tmp = $e->getMessage();
                                        }
                                    }
                                    if (!$res) {
                                        try {
                                            $res = $countryModel->add(false);
                                        } catch (PrestaShopException $e) {
                                            $err_tmp = $e->getMessage();
                                        }
                                    }

                                    if (!$res) {
                                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Country (ID: %1$s) cannot be saved. %2$s')), (isset($country['id_country']) && !self::isEmpty($country['id_country'])) ? Tools::safeOutput($country['id_country']) : 'No ID', $err_tmp), 'Country', true);
                                    } else {
                                        self::addLog('Country', $country['id_country'], $countryModel->id);
                                    }
                                    $this->showMigrationMessageAndLog($country_error_tmp, 'Country');
                                }
                            }
                        }
                        $sql_values[] = '(' . (int)$cartRuleObj->id . ', ' . self::getLocalID('country', $cartRuleCountry['id_country'], 'data') . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_country` (`id_cart_rule`, `id_country`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_country. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                        }
                    }

                    // Import Cart Rule Group
                    $sql_values = array();
                    foreach ($cartRules['cart_rule_groups'][$cartRuleid] as $cartRuleGroup) {
                        $sql_values[] = '(' . (int)$cartRuleObj->id . ', ' . self::getCustomerGroupID($cartRuleGroup['id_group']) . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_group` (`id_cart_rule`, `id_group`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_group. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                        }
                    }

                    // Import Cart Rule Product Rule Group
                    foreach ($cartRules['cart_rule_product_rule_groups'][$cartRuleid] as $cartRuleProductRuleGroup) {
                        $sql_value = '';
                        $sql_value = '(' . (int)$cartRuleObj->id . ', ' . (int)$cartRuleProductRuleGroup['quantity'] . ')';
                        if (!self::isEmpty($sql_value)) {
                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` (`id_cart_rule`,
                                    `quantity`)
                                VALUES ' . $sql_value);
                            if (!$result) {
                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_product_rule_group. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                            } else {
                                $id_product_rule_group = Db::getInstance()->Insert_ID();
                                // Import Cart Rule Product Rule
                                foreach ($cartRules['cart_rule_product_rule'][$cartRuleProductRuleGroup['id_product_rule_group']] as $cartRuleProductRule) {
                                    $sql_value = '';
                                    if ($cartRuleProductRule['id_product_rule_group'] == $cartRuleProductRuleGroup['id_product_rule_group']) {
                                        $sql_value = '(' . (int)$id_product_rule_group . ', \'' . pSQL($cartRuleProductRule['type']) . '\')';
                                        if (!self::isEmpty($sql_value)) {
                                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_product_rule` (`id_product_rule_group`, `type`)
                                VALUES ' . $sql_value);
                                            if (!$result) {
                                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_product_rule. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                                            } else {
                                                $id_product_rule = Db::getInstance()->Insert_ID();

                                                // Import Cart Rule Product Rule Value
                                                $sql_values = array();
                                                foreach ($cartRules['cart_rule_product_rule_value'][$cartRuleProductRule['id_product_rule']] as $cartRuleProductRuleValue) {
                                                    $id_item = 0;
                                                    if (preg_match('|products|', $cartRuleProductRule['type'])) {
                                                        $id_item = self::getLocalID('product', $cartRuleProductRuleValue['id_item'], 'data');
                                                    } elseif (preg_match('|attributes|', $cartRuleProductRule['type'])) {
                                                        $id_item = self::getLocalID('attribute', $cartRuleProductRuleValue['id_item'], 'data');
                                                    } elseif (preg_match('|categories|', $cartRuleProductRule['type'])) {
                                                        $id_item = self::getLocalID('category', $cartRuleProductRuleValue['id_item'], 'data');
                                                    } elseif (preg_match('|manufacturers|', $cartRuleProductRule['type'])) {
                                                        $id_item = self::getLocalID('manufacturer', $cartRuleProductRuleValue['id_item'], 'data');
                                                    } elseif (preg_match('|suppliers|', $cartRuleProductRule['type'])) {
                                                        $id_item = self::getLocalID('supplier', $cartRuleProductRuleValue['id_item'], 'data');
                                                    }
                                                    $sql_values[] = '(' . (int)$id_product_rule . ', ' . (int)$id_item . ')';
                                                    if (!self::isEmpty($sql_values)) {
                                                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_product_rule_value` (`id_product_rule`, `id_item`)
                                VALUES ' . implode(',', $sql_values));
                                                        if (!$result) {
                                                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_product_rule_value. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                                                        }
                                                    }
                                                }
                                                self::addLog('CARTRULEPRODUCTRULE', $cartRuleProductRule['id_product_rule'], $id_product_rule);
                                            }
                                        }
                                    }
                                }
                                self::addLog('CARTRULEPRODUCTRULEGROUP', $cartRuleProductRuleGroup['id_product_rule_group'], $id_product_rule_group);
                            }
                        }
                    }

                    // Import Cart Rule Shop
                    $sql_values = array();
                    foreach ($cartRules['cart_rule_shops'][$cartRuleid] as $cartRuleShop) {
                        $sql_values[] = '(' . (int)$cartRuleObj->id . ', ' . self::getShopID($cartRuleShop['id_shop']) . ')';
                    }
                    if (!self::isEmpty($sql_values)) {
                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_shop` (`id_cart_rule`, `id_shop`) VALUES ' . implode(',', $sql_values));
                        if (!$result) {
                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_shop. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
                        }
                    }

                    if (count($this->error_msg) == 0) {
                        self::addLog('CartRule', $cartRuleid, $cartRuleObj->id);
                    }
                }
                $this->showMigrationMessageAndLog($cart_rule_error_tmp, 'CARTRULE', true);
            }
        }

        // Import Cart Rule Combination
        $sql_values = array();
        foreach ($cartRules['cart_rule_combinations'] as $cartRuleCombination) {
            $sql_values[] = '(' . self::getLocalID('cartRule', $cartRuleCombination['id_cart_rule_1'], 'data') . ',
                        ' . self::getLocalID('cartRule', $cartRuleCombination['id_cart_rule_2'], 'data') . ')';
        }
        if (!self::isEmpty($sql_values)) {
            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) VALUES ' . implode(',', $sql_values));
            if (!$result) {
                $this->showMigrationMessageAndLog(self::displayError('Can\'t add cart_rule_combination. ' . Db::getInstance()->getMsgError()), 'CARTRULE');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $orders
     * @param $ordersAdditionalSecond
     * @param $ordersAdditionalThird
     */
    public function orders($orders)
    {
        $count = 0;
        $isMigrateRecentData = (int)Configuration::get($this->module->name . '_migrate_recent_data');
        foreach ($orders['order'] as $order) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($orderModel = $this->createObjectModel('Order', $order['id_order'], 'orders')) {
                $orderModel->id_address_delivery =  self::getLocalID('address', $order['id_address_delivery'], 'data');
                $orderModel->id_address_invoice =  self::getLocalID('address', $order['id_address_invoice'], 'data');
                $orderModel->id_cart = $order['id_cart'];
                $orderModel->id_currency = self::getCurrencyID($order['id_currency']);
                $orderModel->id_lang = self::getLanguageID($order['id_lang']);
                $orderModel->id_customer = self::getLocalId('customer', $order['id_customer'], 'data');
                $orderModel->id_carrier = $order['id_carrier'];
                if (!self::isEmpty($orderModel->id_customer)) {
                    $customerObj = new Customer($orderModel->id_customer);
                }
                if (!self::isEmpty($customerObj->secure_key)) {
                    $orderModel->secure_key = $customerObj->secure_key;
                } else {
                    $orderModel->secure_key = $order['secure_key'];
                }
                if (self::isEmpty($orderModel->secure_key)) {
                    $orderModel->secure_key = md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));
                    $this->showMigrationMessageAndLog('Secure key of order with ID ' . $order['id_order'] ? $order['id_order'] : 'No ID' . ' is empty. For that reason, the module set default value as a secure key.', 'Order', true);
                }
                $orderModel->payment = $order['payment'];
                $orderModel->module = (self::isEmpty($order['module'])) || !Validate::isModuleName($order['module']) ? 'cheque' : $order['module'];
                $orderModel->recyclable = $order['recyclable'];
                $orderModel->gift = $order['gift'];
                $orderModel->gift_message = $order['gift_message'];
                $orderModel->total_discounts = $order['total_discounts'];
                $orderModel->total_paid = $order['total_paid'];
                $orderModel->total_paid_real = $order['total_paid_real'];
                $orderModel->total_products = $order['total_products'];
                $orderModel->total_products_wt = $order['total_products_wt'];
                $orderModel->total_shipping = $order['total_shipping'];
                $orderModel->carrier_tax_rate = $order['carrier_tax_rate'];
                $orderModel->total_wrapping = $order['total_wrapping'];
                $orderModel->shipping_number = Validate::isTrackingNumber($order['shipping_number']) ? $order['shipping_number'] : 0;
                $orderModel->conversion_rate = self::defaultValue($order['conversion_rate'], 0);
                $orderModel->invoice_number = $order['invoice_number'];
                $orderModel->delivery_number = $order['delivery_number'];
                $orderModel->invoice_date = $order['invoice_date'];
                $orderModel->delivery_date = $order['delivery_date'];
                $orderModel->valid = $order['valid'];
                $orderModel->date_add = $order['date_add'];
                $orderModel->date_upd = $order['date_upd'];
                $orderTaxRate = 0;
                $taxName = '';
                foreach ($orders['order_detail'][$order['id_order']] as $orderDetail) {
                    $orderTaxRate = $orderDetail['tax_rate'];
                    $taxName = $orderDetail['tax_name'];
                    break;
                }
                if ($this->version >= 1.5) {
                    $orderModel->id_shop = self::getShopID($order['id_shop']);
                    $orderModel->id_shop_group = Shop::getGroupFromShop($orderModel->id_shop);
                    $orderModel->current_state = $order['current_state'];
                    $orderModel->mobile_theme = $order['mobile_theme'];
                    $orderModel->total_discounts_tax_incl = (float)Tools::ps_round($order['total_discounts_tax_incl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_discounts_tax_excl = (float)Tools::ps_round($order['total_discounts_tax_excl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_paid_tax_incl = (float)Tools::ps_round($order['total_paid_tax_incl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_paid_tax_excl = (float)Tools::ps_round($order['total_paid_tax_excl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_shipping_tax_incl = (float)Tools::ps_round($order['total_shipping_tax_incl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_shipping_tax_excl = (float)Tools::ps_round($order['total_shipping_tax_excl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_wrapping_tax_incl = (float)Tools::ps_round($order['total_wrapping_tax_incl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->total_wrapping_tax_excl = (float)Tools::ps_round($order['total_wrapping_tax_excl'], _PS_PRICE_DISPLAY_PRECISION_);
                    $orderModel->round_mode = $order['round_mode'];
                    $orderModel->round_type = $order['round_type'];
                    $orderModel->reference = $order['reference'];
                } else {
                    $orderModel->total_discounts_tax_incl = $orderModel->total_discounts;
                    $orderModel->total_discounts_tax_excl = $orderModel->total_discounts - ($orderTaxRate * $orderModel->total_discounts) / 100;
                    $orderModel->reference = Order::generateReference();
                    $orderModel->id_shop = (int)$this->mapping['multi_shops'];
                    $current_shop = Shop::getShop((int)$this->mapping['multi_shops']);
                    $orderModel->id_shop_group = $current_shop['id_shop_group'];
                    $orderModel->total_shipping_tax_incl = $order['total_shipping'];
                    $total_shipping_tax_excl = $order['total_shipping'] / (1 + $order['carrier_tax_rate'] / 100);
                    $orderModel->total_shipping_tax_excl = (float)Tools::ps_round($total_shipping_tax_excl, _PS_PRICE_COMPUTE_PRECISION_);
                    $orderModel->total_paid_tax_incl = $order['total_products_wt'] + $orderModel->total_shipping_tax_incl - $orderModel->total_discounts;
                    $orderModel->total_paid_tax_excl = $order['total_products'] + $orderModel->total_shipping_tax_excl - $orderModel->total_discounts_tax_excl;
                }
                $res = false;
                $err_tmp = '';

                $this->validator->setObject($orderModel);
                $this->validator->checkFields();
                $order_error_tmp = $this->validator->getValidationMessages();
                if ($orderModel->id && self::existsInDatabase($orderModel->id, 'orders', 'order')) {
                    try {
                        $res = $orderModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $orderModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order (ID: %1$s) cannot be saved. %2$s')), (isset($order['id_order']) && !self::isEmpty($order['id_order'])) ? Tools::safeOutput($order['id_order']) : 'No ID', $err_tmp), 'Order');
                } else {
                    // import Payment
                    $paymentIds = array();
                    if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                        if ($orderPaymentModel = $this->createObjectModel('OrderPayment', $order['id_order'])) {
                            $orderPaymentModel->order_reference = $orderModel->reference;
                            $orderPaymentModel->id_currency = $orderModel->id_currency;
                            $orderPaymentModel->amount = $orderModel->total_paid;
                            $orderPaymentModel->payment_method = $orderModel->payment;
                            $orderPaymentModel->conversion_rate = $orderModel->conversion_rate;
                            $orderPaymentModel->transaction_id = null;
                            $orderPaymentModel->card_number = null;
                            $orderPaymentModel->card_brand = null;
                            $orderPaymentModel->card_expiration = null;
                            $orderPaymentModel->card_holder = null;
                            $orderPaymentModel->date_add = $order['invoice_date'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $order['invoice_date'];

                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($orderPaymentModel);
                            $this->validator->checkFields();
                            $order_payment_error_tmp = $this->validator->getValidationMessages();
                            if ($orderPaymentModel->id && OrderPayment::existsInDatabase($orderPaymentModel->id, 'order_payment')) {
                                try {
                                    $res = $orderPaymentModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderPaymentModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Payment (ID: %1$s) cannot be saved. %2$s')), (isset($order['id_order']) && !self::isEmpty($order['id_order'])) ? Tools::safeOutput($order['id_order']) : 'No ID', $err_tmp), 'OrderPayment');
                            } else {
                                self::addLog('OrderPayment', $order['id_order'], $orderPaymentModel->id);
                                $paymentIds[] = $orderPaymentModel->id;
                            }
                            $this->showMigrationMessageAndLog($order_payment_error_tmp, 'OrderPayment');
                        }
                    } else {
                        foreach ($orders['order_payment'][$order['reference']] as $orderPayment) {
                            if ($orderPaymentModel = $this->createObjectModel('OrderPayment', $orderPayment['id_order_payment'])) {
                                $orderPaymentModel->order_reference = isset($orderPayment['order_reference']) ? $orderPayment['order_reference'] : $order['payment'];
                                $orderPaymentModel->id_currency = self::getCurrencyID($orderPayment['id_currency']);
                                $orderPaymentModel->amount = $orderPayment['amount'];
                                $orderPaymentModel->payment_method = isset($orderPayment['payment_method']) ? $orderPayment['payment_method'] : $order['payment'];
                                $orderPaymentModel->conversion_rate = $orderModel->conversion_rate;
                                $orderPaymentModel->transaction_id = $orderPayment['transaction_id'];
                                $orderPaymentModel->card_number = $orderPayment['card_number'];
                                $orderPaymentModel->card_brand = $orderPayment['card_brand'];
                                $orderPaymentModel->card_expiration = $orderPayment['card_expiration'];
                                $orderPaymentModel->card_holder = $orderPayment['card_holder'];
                                $orderPaymentModel->date_add = $orderPayment['date_add'];


                                $res = false;
                                $err_tmp = '';

                                $this->validator->setObject($orderPaymentModel);
                                $this->validator->checkFields();
                                $order_payment_error_tmp = $this->validator->getValidationMessages();
                                if ($orderPaymentModel->id && OrderPayment::existsInDatabase($orderPaymentModel->id, 'order_payment')) {
                                    try {
                                        $res = $orderPaymentModel->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $orderPaymentModel->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }

                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Payment (ID: %1$s) cannot be saved. %2$s')), (isset($orderPayment['id_order_payment']) && !self::isEmpty($orderPayment['id_order_payment'])) ? Tools::safeOutput($orderPayment['id_order_payment']) : 'No ID', $err_tmp), 'OrderPayment');
                                } else {
                                    self::addLog('OrderPayment', $orderPayment['id_order_payment'], $orderPaymentModel->id);
                                    $paymentIds[] = $orderPaymentModel->id;
                                }
                                $this->showMigrationMessageAndLog($order_payment_error_tmp, 'OrderPayment');
                            }
                        }
                    }
                    // import Invoice
                    if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                        if ($orderInvoiceModel = $this->createObjectModel('OrderInvoice', $order['id_order'])) {
                            $orderInvoiceModel->id_order = $orderModel->id;
                            $orderInvoiceModel->number = $order['invoice_number'];
                            $orderInvoiceModel->delivery_number = $order['delivery_number'];
                            $orderInvoiceModel->delivery_date = $order['delivery_date'];
                            $orderInvoiceModel->total_discount_tax_excl = $orderModel->total_discounts_tax_excl;
                            $orderInvoiceModel->total_discount_tax_incl = $orderModel->total_discounts_tax_incl;
                            $orderInvoiceModel->total_paid_tax_excl = $orderModel->total_paid_tax_excl;
                            $orderInvoiceModel->total_paid_tax_incl = $orderModel->total_paid_tax_incl;
                            $orderInvoiceModel->total_products = $orderModel->total_products;
                            $orderInvoiceModel->total_products_wt = $orderModel->total_products_wt;
                            $orderInvoiceModel->total_shipping_tax_excl = $orderModel->total_shipping_tax_excl;
                            $orderInvoiceModel->total_shipping_tax_incl = $orderModel->total_shipping_tax_incl;
                            $orderInvoiceModel->shipping_tax_computation_method = 0;
                            $orderInvoiceModel->total_wrapping_tax_excl = $orderModel->total_wrapping_tax_excl;
                            $orderInvoiceModel->total_wrapping_tax_incl = $orderModel->total_wrapping_tax_incl;
                            $orderInvoiceModel->invoice_date = $order['invoice_date'];
                            $orderInvoiceModel->invoice_address = $order['id_address_invoice'];
                            $orderInvoiceModel->delivery_address = $order['id_address_delivery'];
                            $orderInvoiceModel->note = '';
                            $orderInvoiceModel->date_add = date('Y-m-d H:i:s');


                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($orderInvoiceModel);
                            $this->validator->checkFields();
                            $order_invoice_error_tmp = $this->validator->getValidationMessages();
                            if ($orderInvoiceModel->id && OrderInvoice::existsInDatabase($orderInvoiceModel->id, 'order_invoice')) {
                                try {
                                    $res = $orderInvoiceModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderInvoiceModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Invoice (ID: %1$s) cannot be saved. %2$s')), (isset($order['id_order']) && !self::isEmpty($order['id_order'])) ? Tools::safeOutput($order['id_order']) : 'No ID', $err_tmp), 'OrderInvoice');
                            } else {
                                self::addLog('OrderInvoice', $order['id_order'], $orderInvoiceModel->id);
                            }
                            $this->showMigrationMessageAndLog($order_invoice_error_tmp, 'OrderInvoice');
                            //import Invoice_Tax
                            $invoice_taxsql_values = array();
                            $taxId = (int)Tax::getTaxIdByName($taxName);
                            $invoice_taxsql_values[] = '(' . (int)$orderInvoiceModel->id . ', \'' . 'tax' . '\', ' . (int)$taxId . ', ' . (float)($orderModel->total_paid_tax_incl - $orderModel->total_paid_tax_excl) . ')';
                            if (!self::isEmpty($invoice_taxsql_values)) {
                                $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'order_invoice_tax` (`id_order_invoice`, `type`, `id_tax`, `amount`) VALUES ' . implode(',', $invoice_taxsql_values));
                                if (!$result) {
                                    $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_invoice_tax. ' . Db::getInstance()->getMsgError()), 'OrderInvoice');
                                }
                            }
                            //import Invoice_Payment
                            $sql_values = array();
                            foreach ($paymentIds as $invoicePaymentId) {
                                $sql_values[] = '(' . (int)$orderInvoiceModel->id . ', ' . (int)$invoicePaymentId . ', ' . (int)$orderModel->id . ')';
                            }
                            if (!self::isEmpty($sql_values)) {
                                $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'order_invoice_payment` (`id_order_invoice`, `id_order_payment`, `id_order`) VALUES ' . implode(',', $sql_values));
                                if (!$result) {
                                    $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_invoice_payment. ' . Db::getInstance()->getMsgError()), 'OrderInvoice');
                                }
                            }
                        }
                    } else {
                        foreach ($orders['order_invoice'][$order['id_order']] as $orderInvoice) {
                            if ($orderInvoiceModel = $this->createObjectModel('OrderInvoice', $orderInvoice['id_order_invoice'])) {
                                $orderInvoiceModel->id_order = $orderModel->id;
                                $orderInvoiceModel->number = $orderModel->invoice_number;
                                $orderInvoiceModel->delivery_number = $orderModel->delivery_number;
                                $orderInvoiceModel->delivery_date = $orderModel->delivery_date;
                                $orderInvoiceModel->total_discount_tax_excl = $orderModel->total_discounts_tax_excl;
                                $orderInvoiceModel->total_discount_tax_incl = $orderModel->total_discounts_tax_incl;
                                $orderInvoiceModel->total_paid_tax_excl = $orderModel->total_paid_tax_excl;
                                $orderInvoiceModel->total_paid_tax_incl = $orderModel->total_paid_tax_incl;
                                $orderInvoiceModel->total_products = $orderModel->total_products;
                                $orderInvoiceModel->total_products_wt = $orderModel->total_products_wt;
                                $orderInvoiceModel->total_shipping_tax_excl = $orderModel->total_shipping_tax_excl;
                                $orderInvoiceModel->total_shipping_tax_incl = $orderModel->total_shipping_tax_incl;
                                $orderInvoiceModel->shipping_tax_computation_method = $orderInvoice['shipping_tax_computation_method'];
                                $orderInvoiceModel->total_wrapping_tax_excl = $orderModel->total_wrapping_tax_excl;
                                $orderInvoiceModel->total_wrapping_tax_incl = $orderModel->total_wrapping_tax_incl;
                                $orderInvoiceModel->invoice_date = $orderInvoice['invoice_date'];
                                $orderInvoiceModel->invoice_address = $orderInvoice['invoice_address'];
                                $orderInvoiceModel->delivery_address = $orderInvoice['delivery_address'];
                                $orderInvoiceModel->note = $orderInvoice['note'];
                                $orderInvoiceModel->date_add = $orderInvoice['date_add'];


                                $res = false;
                                $err_tmp = '';

                                $this->validator->setObject($orderInvoiceModel);
                                $this->validator->checkFields();
                                $order_invoice_error_tmp = $this->validator->getValidationMessages();
                                if ($orderInvoiceModel->id && OrderInvoice::existsInDatabase($orderInvoiceModel->id, 'order_invoice')) {
                                    try {
                                        $res = $orderInvoiceModel->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $orderInvoiceModel->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Invoice (ID: %1$s) cannot be saved. %2$s')), (isset($orderInvoice['id_order_invoice']) && !self::isEmpty($orderInvoice['id_order_invoice'])) ? Tools::safeOutput($orderInvoice['id_order_invoice']) : 'No ID', $err_tmp), 'OrderInvoice');
                                } else {
                                    self::addLog('OrderInvoice', $orderInvoice['id_order_invoice'], $orderInvoiceModel->id);
                                }
                                $this->showMigrationMessageAndLog($order_invoice_error_tmp, 'OrderInvoice');
                                //import Invoice_Tax
                                $sql_values = array();
                                foreach ($orders['invoice_tax'][$orderInvoice['id_order_invoice']] as $invoiceTax) {
                                    if ($invoiceTax['id_order_invoice'] == $orderInvoice['id_order_invoice']) {
                                        $sql_values[] = '(' . (int)$orderInvoiceModel->id . ', "' . pSQL($invoiceTax['type']) . '", ' . self::getLocalID('tax', $invoiceTax['id_tax'], 'data') . ',' . (float)$invoiceTax['amount'] . ')';
                                    }
                                }
                                if (!self::isEmpty($sql_values)) {
                                    $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'order_invoice_tax` (`id_order_invoice`, `type`, `id_tax`, `amount`) VALUES ' . implode(',', $sql_values));
                                    if (!$result) {
                                        $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_invoice_tax. ' . Db::getInstance()->getMsgError()), 'OrderInvoice');
                                    }
                                }
                                //import Invoice_Payment
                                $sql_values = array();
                                foreach ($orders['invoice_payment'][$order['id_order']] as $invoicePayment) {
                                    $sql_values[] = '(' . (int)$orderInvoiceModel->id . ', ' . self::getLocalID('orderpayment', (int)$invoicePayment['id_order_payment'], 'data') . ', ' . (int)$orderModel->id . ')';
                                }
                                if (!self::isEmpty($sql_values)) {
                                    $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'order_invoice_payment` (`id_order_invoice`, `id_order_payment`, `id_order`) VALUES ' . implode(',', $sql_values));

                                    if (!$result) {
                                        $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_invoice_payment. ' . Db::getInstance()->getMsgError()), 'OrderInvoice');
                                    }
                                }
                            }
                        }
                    }
                    // import Order Detail
                    foreach ($orders['order_detail'][$order['id_order']] as $orderDetail) {
                        if ($orderDetailModel = $this->createObjectModel('OrderDetail', $orderDetail['id_order_detail'])) {
                            $orderDetailModel->id_order = $orderModel->id;
                            $orderDetailModel->product_id = self::getLocalID('product', $orderDetail['product_id'], 'data');
                            $orderDetailModel->product_attribute_id = self::getLocalID('combination', $orderDetail['product_attribute_id'], 'data');
                            $orderDetailModel->product_name = $orderDetail['product_name'];
                            $orderDetailModel->product_quantity = $orderDetail['product_quantity'];
                            $orderDetailModel->product_quantity_in_stock = $orderDetail['product_quantity_in_stock'];
                            $orderDetailModel->product_quantity_return = $orderDetail['product_quantity_return'];
                            $orderDetailModel->product_quantity_refunded = $orderDetail['product_quantity_refunded'];
                            $orderDetailModel->product_quantity_reinjected = $orderDetail['product_quantity_reinjected'];
                            $orderDetailModel->product_price = $orderDetail['product_price'];
                            $orderDetailModel->reduction_percent = $orderDetail['reduction_percent'];
                            $orderDetailModel->reduction_amount = $orderDetail['reduction_amount'];
                            $orderDetailModel->group_reduction = $orderDetail['group_reduction'];
                            $orderDetailModel->product_quantity_discount = $orderDetail['product_quantity_discount'];
                            $orderDetailModel->product_ean13 = $orderDetail['product_ean13'];
                            $orderDetailModel->product_upc = $orderDetail['product_upc'];
                            $orderDetailModel->product_reference = $orderDetail['product_reference'];
                            $orderDetailModel->product_supplier_reference = $orderDetail['product_supplier_reference'];
                            $orderDetailModel->product_weight = $orderDetail['product_weight'];
                            $orderDetailModel->tax_name = $orderDetail['tax_name'];
                            $orderDetailModel->tax_rate = $orderDetail['tax_rate'];
                            $orderDetailModel->ecotax = $orderDetail['ecotax'];
                            $orderDetailModel->ecotax_tax_rate = $orderDetail['ecotax_tax_rate'];
                            $orderDetailModel->discount_quantity_applied = $orderDetail['discount_quantity_applied'];
                            $orderDetailModel->download_hash = $orderDetail['download_hash'];
                            $orderDetailModel->download_nb = $orderDetail['download_nb'];
                            $orderDetailModel->download_deadline = $orderDetail['download_deadline'];

                            $orderDetailModel->id_warehouse = (isset($orderDetail['id_warehouse']) && !self::isEmpty($orderDetail['id_warehouse'])) ? $orderDetail['id_warehouse'] : 0;

                            $orderDetailModel->id_shop = (isset($orderDetail['id_shop']) && !self::isEmpty($orderDetail['id_shop'])) ? self::getShopID($orderDetail['id_shop']) : Context::getContext()->shop->id;
                            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                                $orderDetailModel->id_order_invoice = self::getLocalID('orderinvoice', $order['id_order'], 'data');
                                $orderDetailModel->reduction_amount_tax_incl = (float)Tools::ps_round($orderDetail['reduction_amount'], _PS_PRICE_DISPLAY_PRECISION_);
                                $orderDetailModel->reduction_amount_tax_excl = (float)Tools::ps_round(($orderDetail['reduction_amount'] * 100 / (100 + $orderDetail['tax_rate'])), 6);
                                $orderDetailModel->product_isbn = $orderDetail['product_isbn'];
                                $orderDetailModel->tax_computation_method = $orderDetail['tax_computation_method'];
                                $orderDetailModel->id_tax_rules_group = self::getLocalId('taxrulesgroup', $orderDetail['id_tax_rules_group'], 'data');
                                $orderDetailModel->unit_price_tax_incl = (float)Tools::ps_round((($orderDetail['product_price'] * $orderDetail['tax_rate']) / 100 + $orderDetail['product_price']) - $orderDetailModel->reduction_amount_tax_incl, _PS_PRICE_DISPLAY_PRECISION_);
                                $orderDetailModel->unit_price_tax_excl = $orderDetail['product_price'] - $orderDetailModel->reduction_amount_tax_excl;
                                $orderDetailModel->total_price_tax_incl = $orderDetailModel->unit_price_tax_incl * $orderDetail['product_quantity'];
                                $orderDetailModel->total_price_tax_excl = $orderDetailModel->unit_price_tax_excl * $orderDetail['product_quantity'];
                                $orderDetailModel->total_shipping_price_tax_excl = $orderDetail['total_shipping_price_tax_excl'];
                                $orderDetailModel->total_shipping_price_tax_incl = $orderDetail['total_shipping_price_tax_incl'];
                                $orderDetailModel->purchase_supplier_price = $orderDetail['purchase_supplier_price'];
                                $orderDetailModel->original_product_price = $orderDetail['product_price'];
                                $orderDetailModel->original_wholesale_price = $orderDetail['original_wholesale_price'];
                            } else {
                                $orderDetailModel->id_order_invoice = self::getLocalID('orderinvoice', $orderDetail['id_order_invoice'], 'data');
                                $orderDetailModel->reduction_amount_tax_incl = $orderDetail['reduction_amount_tax_incl'];
                                $orderDetailModel->reduction_amount_tax_excl = $orderDetail['reduction_amount_tax_excl'];
                                $orderDetailModel->product_isbn = $orderDetail['product_isbn'];
                                $orderDetailModel->tax_computation_method = $orderDetail['tax_computation_method'];
                                $orderDetailModel->id_tax_rules_group = self::getLocalId('taxrulesgroup', $orderDetail['id_tax_rules_group'], 'data');
                                $orderDetailModel->unit_price_tax_incl = $orderDetail['unit_price_tax_incl'];
                                $orderDetailModel->unit_price_tax_excl = $orderDetail['unit_price_tax_excl'];
                                $orderDetailModel->total_price_tax_incl = $orderDetail['total_price_tax_incl'];
                                $orderDetailModel->total_price_tax_excl = $orderDetail['total_price_tax_excl'];
                                $orderDetailModel->total_shipping_price_tax_excl = $orderDetail['total_shipping_price_tax_excl'];
                                $orderDetailModel->total_shipping_price_tax_incl = $orderDetail['total_shipping_price_tax_incl'];
                                $orderDetailModel->purchase_supplier_price = $orderDetail['purchase_supplier_price'];
                                $orderDetailModel->original_product_price = $orderDetail['original_product_price'];
                                $orderDetailModel->original_wholesale_price = $orderDetail['original_wholesale_price'];
                            }


                            $res = false;
                            $err_tmp = '';


                            $this->validator->setObject($orderDetailModel);
                            $this->validator->checkFields();
                            $order_detail_error_tmp = $this->validator->getValidationMessages();
                            if ($orderDetailModel->id && OrderDetail::existsInDatabase($orderDetailModel->id, 'order_detail')) {
                                try {
                                    $res = $orderDetailModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderDetailModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Detail (ID: %1$s) cannot be saved. %2$s')), (isset($orderDetail['id_order_detail']) && !self::isEmpty($orderDetail['id_order_detail'])) ? Tools::safeOutput($orderDetail['id_order_detail']) : 'No ID', $err_tmp), 'OrderDetail');
                            } else {
                                self::addLog('OrderDetail', $orderDetail['id_order_detail'], $orderDetailModel->id);
                                //import Order Detail tax
                                if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                                    $sql_values = array();
                                    $sql_values[] = '(' . (int)$orderDetailModel->id . ', ' . (int)Tax::getTaxIdByName($taxName) . ', ' . (float)($orderDetailModel->unit_price_tax_incl - $orderDetailModel->unit_price_tax_excl) . ', ' . (float)($orderDetailModel->total_price_tax_incl - $orderDetailModel->total_price_tax_excl) . ')';
                                } else {
                                    $sql_values = array();
                                    foreach ($orders['order_detail_tax'][$orderDetail['id_order_detail']] as $orderDetailTax) {
                                        $sql_values[] = '(' . (int)$orderDetailModel->id . ', ' . (int)self::getLocalID('tax', $orderDetailTax['id_tax'], 'data') . ', ' . (float)$orderDetailTax['unit_amount'] . ', ' . (float)$orderDetailTax['total_amount'] . ')';
                                    }
                                }
                                if (!self::isEmpty($sql_values)) {
                                    $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'order_detail_tax` (`id_order_detail`, `id_tax`, `unit_amount`, `total_amount`) VALUES ' . implode(',', $sql_values));

                                    if (!$result) {
                                        $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_detail_tax. ' . Db::getInstance()->getMsgError()), 'OrderDetail');
                                    }
                                }

                                if ($isMigrateRecentData) {
                                    $productsOldQuantity = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'stock_available WHERE id_product = ' . $orderDetailModel->product_id . ' AND id_product_attribute = ' . $orderDetailModel->product_attribute_id . ' AND id_shop = ' . $orderDetailModel->id_shop);
                                    $productsNewQuantityAfterOrder = $productsOldQuantity - $orderDetail['product_quantity'];
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity = ' . (int)$productsNewQuantityAfterOrder . ' WHERE id_product = ' . (int)$orderDetailModel->product_id . ' AND id_product_attribute = ' . (int)$orderDetailModel->product_attribute_id . ' AND id_shop = ' . (int)$orderDetailModel->id_shop);
                                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'product SET quantity = ' . (int)$productsNewQuantityAfterOrder . ' WHERE id_product = ' . (int)$orderDetailModel->product_id);
                                    if ($orderDetailModel->product_attribute_id != 0) {
                                        $mainProductsCount = (int)Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'stock_available WHERE id_product = ' . (int)$orderDetailModel->product_id . ' AND id_product_attribute = 0 AND id_shop = ' . (int)$orderDetailModel->id_shop) - (int)$orderDetail['product_quantity'];
                                        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity = ' . (int)$mainProductsCount . ' WHERE id_product = ' . (int)$orderDetailModel->product_id . ' AND id_product_attribute = 0 AND id_shop = ' . (int)$orderDetailModel->id_shop);
                                    }
                                }
                            }
                            $this->showMigrationMessageAndLog($order_detail_error_tmp, 'OrderDetail');
                        }
                    }
                    // import Order Slip
                    $insertIdOrderSlipDetail = 1;
                    foreach ($orders['order_slip'][$order['id_order']] as $orderSlip) {
                        if ($orderSlipModel = $this->createObjectModel('OrderSlip', $orderSlip['id_order_slip'])) {
                            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                                $orderSlipModel->conversion_rate = self::isEmpty($orderSlip['conversion_rate']) ? 1 : $orderSlip['conversion_rate'];
                                $orderSlipModel->id_customer = self::getLocalID('customer', $orderSlip['id_customer'], 'data');
                                $orderSlipModel->id_order = $orderModel->id;
                                $orderSlipModel->shipping_cost = $orderSlip['shipping_cost'];
                                $importedOrderSlipDetails = array();
                                foreach ($orders['order_slip_detail'][$orderSlip['id_order_slip']] as $orderSlipDetails) {
                                    if (in_array($orderSlipDetails['id_order_detail'], $importedOrderSlipDetails)) {
                                        continue;
                                    }
                                    $orderDetailOfSlip = new OrderDetail(self::getLocalID('orderdetail', (int)$orderSlipDetails['id_order_detail'], 'data'));
//                                            if ($orderDetailOfSlip) {
                                    $importedOrderSlipDetails[] = $orderSlipDetails['id_order_detail'];
//                                            }
                                    $orderSlipModel->amount = $orderDetailOfSlip->product_price * $orderSlipDetails['product_quantity'];
                                    $orderSlipModel->total_products_tax_excl = $orderDetailOfSlip->unit_price_tax_excl;
                                    $orderSlipModel->total_products_tax_incl = $orderDetailOfSlip->total_price_tax_incl;
                                    $orderSlipModel->total_shipping_tax_excl = $orderDetailOfSlip->total_shipping_price_tax_excl;
                                    $orderSlipModel->total_shipping_tax_incl = $orderDetailOfSlip->total_shipping_price_tax_incl;
                                    $orderSlipModel->shipping_cost_amount = $orderDetailOfSlip->total_shipping_price_tax_incl;
                                    $orderSlipModel->partial = 0;
                                    $orderSlipModel->order_slip_type = 0;
                                }
                                if (self::isEmpty($orderSlipModel->total_products_tax_excl) || self::isEmpty($orderSlipModel->total_products_tax_incl)) {
                                    continue;
                                }
                                $orderSlipModel->date_add = $orderSlip['date_add'];
                                $orderSlipModel->date_upd = $orderSlip['date_upd'];
                            } else {
                                $orderSlipModel->conversion_rate = $orderSlip['conversion_rate'];
                                $orderSlipModel->id_customer = self::getLocalID('customer', $orderSlip['id_customer'], 'data');
                                $orderSlipModel->id_order = $orderModel->id;
                                $orderSlipModel->shipping_cost = $orderSlip['shipping_cost'];
                                $orderSlipModel->amount = $orderSlip['amount'];
                                $orderSlipModel->total_products_tax_excl = $orderSlip['total_products_tax_excl'];
                                $orderSlipModel->total_products_tax_incl = $orderSlip['total_products_tax_incl'];
                                $orderSlipModel->total_shipping_tax_excl = $orderSlip['total_shipping_tax_excl'];
                                $orderSlipModel->total_shipping_tax_incl = $orderSlip['total_shipping_tax_incl'];
                                $orderSlipModel->shipping_cost_amount = $orderSlip['shipping_cost_amount'];
                                $orderSlipModel->partial = $orderSlip['partial'];
                                $orderSlipModel->order_slip_type = $orderSlip['order_slip_type'];
                                $orderSlipModel->date_add = $orderSlip['date_add'];
                                $orderSlipModel->date_upd = $orderSlip['date_upd'];
                            }

                            if (self::isEmpty($orderSlipModel->total_products_tax_excl)) {
                                $orderSlipModel->total_products_tax_excl = $orderSlipModel->amount;
                            }
                            if (self::isEmpty($orderSlipModel->total_products_tax_incl)) {
                                $orderSlipModel->total_products_tax_incl = $orderSlipModel->amount;
                            }
                            if (self::isEmpty($orderSlipModel->total_shipping_tax_excl)) {
                                $orderSlipModel->total_shipping_tax_excl = $orderSlipModel->shipping_cost;
                            }
                            if (self::isEmpty($orderSlipModel->total_shipping_tax_incl)) {
                                $orderSlipModel->total_shipping_tax_incl = $orderSlipModel->shipping_cost;
                            }

                            $res = false;
                            $err_tmp = '';


                            $this->validator->setObject($orderSlipModel);
                            $this->validator->checkFields();
                            $order_slip_error_tmp = $this->validator->getValidationMessages();
                            if ($orderSlipModel->id && orderSlip::existsInDatabase($orderSlipModel->id, 'order_slip')) {
                                try {
                                    $res = $orderSlipModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderSlipModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Slip (ID: %1$s) cannot be saved. %2$s')), (isset($orderSlip['id_order_slip']) && !self::isEmpty($orderSlip['id_order_slip'])) ? Tools::safeOutput($orderSlip['id_order_slip']) : 'No ID', $err_tmp), 'orderSlip');
                            } else {
                                self::addLog('orderSlip', $orderSlip['id_order_slip'], $orderSlipModel->id);
                                //import Order Slip Detail
                                $sql_values = array();
                                $importedOrderSlipDetails = array();
                                if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                                    foreach ($orders['order_slip_detail'][$orderSlip['id_order_slip']] as $invoiceSlipDetail) {
                                        if (in_array($invoiceSlipDetail['id_order_detail'], $importedOrderSlipDetails)) {
                                            continue;
                                        } else {
                                            $importedOrderSlipDetails[] = $invoiceSlipDetail['id_order_detail'];
                                        }
                                        $slipIdOrderDetail = self::getLocalID('orderdetail', (int)$invoiceSlipDetail['id_order_detail'], 'data');
                                        $slipOrderDetailObj = new OrderDetail($slipIdOrderDetail);
                                        $sql_values = array();
                                        if ($slipOrderDetailObj->id && ($invoiceSlipDetail['id_order_slip'] == $orderSlip['id_order_slip'])) {
                                            $sql_values[] = '(' .
                                                $orderSlipModel->id . ', ' .
                                                $slipOrderDetailObj->id . ', ' .
                                                $invoiceSlipDetail['product_quantity'] . ', ' .
                                                $slipOrderDetailObj->unit_price_tax_excl . ', ' .
                                                $slipOrderDetailObj->unit_price_tax_incl . ', ' .
                                                $slipOrderDetailObj->total_price_tax_excl . ', ' .
                                                $slipOrderDetailObj->total_price_tax_incl . ', ' .
                                                $slipOrderDetailObj->total_price_tax_excl . ', ' .
                                                $slipOrderDetailObj->total_price_tax_incl . ')';
                                        }
                                        if (!self::isEmpty($sql_values)) {
                                            $dbInstance = Db::getInstance();
                                            $result = false;
                                            $result = $dbInstance->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'order_slip_detail` (
                                                `id_order_slip`,
                                                `id_order_detail`,
                                                `product_quantity`,
                                                `unit_price_tax_excl`,
                                                `unit_price_tax_incl`,
                                                `total_price_tax_excl`,
                                                `total_price_tax_incl`,
                                                `amount_tax_excl`,
                                                `amount_tax_incl`)
                                                VALUES ' . implode(',', $sql_values));
                                            if (!$result) {
                                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_slip_detail. ' . Db::getInstance()->getMsgError()), 'orderSlip');
                                            } else {
                                                $orderSlipDetailTaxName = Tax::getTaxIdByName($slipOrderDetailObj->tax_name);
                                                Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'order_slip_detail_tax` (id_order_slip_detail, id_tax, unit_amount, total_amount) VALUES (' .
                                                (int)$insertIdOrderSlipDetail++ . ', ' .
                                                !self::isEmpty($orderSlipDetailTaxName) ? $orderSlipDetailTaxName : 0 . ', ' . (float)$slipOrderDetailObj->unit_price_tax_incl . ', ' . (float)$slipOrderDetailObj->total_price_tax_incl . ')');
                                            }
                                        }
                                    }
                                } else {
                                    $sql_values = array();
                                    foreach ($orders['order_slip_detail'][$orderSlip['id_order_slip']] as $invoiceSlipDetail) {
                                        if (self::isEmpty($invoiceSlipDetail['product_quantity']) || self::isEmpty($invoiceSlipDetail['unit_price_tax_excl']) || self::isEmpty($invoiceSlipDetail['unit_price_tax_incl']) || self::isEmpty($invoiceSlipDetail['total_price_tax_excl']) || $invoiceSlipDetail['total_price_tax_incl']) {
                                            $slipIdOrderDetail = self::getLocalID('orderdetail', (int)$invoiceSlipDetail['id_order_detail'], 'data');
                                            $slipOrderDetailObj = new OrderDetail($slipIdOrderDetail);
                                            if ($slipOrderDetailObj->id) {
                                                $sql_values[] = '(' .
                                                    $orderSlipModel->id . ', ' .
                                                    $slipOrderDetailObj->id . ', ' .
                                                    $invoiceSlipDetail['product_quantity'] . ', ' .
                                                    $slipOrderDetailObj->unit_price_tax_excl . ', ' .
                                                    $slipOrderDetailObj->unit_price_tax_incl . ', ' .
                                                    $slipOrderDetailObj->total_price_tax_excl . ', ' .
                                                    $slipOrderDetailObj->total_price_tax_incl . ', ' .
                                                    $slipOrderDetailObj->total_price_tax_excl . ', ' .
                                                    $slipOrderDetailObj->total_price_tax_incl . ')';
                                            }
                                        } else {
                                            $sql_values[] = '(' . (int)$orderSlipModel->id . ', ' . self::getLocalID('orderDetail', $invoiceSlipDetail['id_order_detail'], 'data') . ', ' . (int)$invoiceSlipDetail['product_quantity'] . ', ' . (float)$invoiceSlipDetail['unit_price_tax_excl'] . ', ' . (float)$invoiceSlipDetail['unit_price_tax_incl'] . ', ' . (float)$invoiceSlipDetail['total_price_tax_excl'] . ', ' . (float)$invoiceSlipDetail['total_price_tax_incl'] . ', ' .
                                                (float)$invoiceSlipDetail['amount_tax_excl'] .
                                                ', ' . (float)$invoiceSlipDetail['amount_tax_incl'] . ')';
                                        }
                                    }
                                    if (!self::isEmpty($sql_values)) {
                                        $dbInstance = Db::getInstance();
                                        $result = false;
                                        $result = $dbInstance->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'order_slip_detail` (
                                                `id_order_slip`,
                                                `id_order_detail`,
                                                `product_quantity`,
                                                `unit_price_tax_excl`,
                                                `unit_price_tax_incl`,
                                                `total_price_tax_excl`,
                                                `total_price_tax_incl`,
                                                `amount_tax_excl`,
                                                `amount_tax_incl`)
                                                VALUES ' . implode(',', $sql_values));
                                        if (!$result) {
                                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_slip_detail. ' . Db::getInstance()->getMsgError()), 'orderSlip');
                                        } else {
                                            $sql_values = array();
                                            foreach ($orders['order_slip_detail_tax'] as $invoiceSlipDetailtax) {
                                                $sql_values[] = '(' . (int)$invoiceSlipDetailtax['id_order_slip_detail'] . ', ' . self::getLocalID('tax', $invoiceSlipDetailtax['id_tax'], 'data') . ', ' . (float)$invoiceSlipDetailtax['unit_amount'] . ', ' . (float)$invoiceSlipDetailtax['total_amount'] . ') ';
                                            }
                                            if (!self::isEmpty($sql_values)) {
                                                $dbInstance = Db::getInstance();
                                                $result = false;
                                                $result = $dbInstance->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'order_slip_detail_tax` (
                                                            `id_order_slip_detail`,
                                                            `id_tax`,
                                                            `unit_amount`,
                                                            `total_amount`)
                                                            VALUES ' . implode(',', $sql_values));
                                            }
                                            if (!$result) {
                                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_slip_detail_tax. ' . Db::getInstance()->getMsgError()), 'orderSlip');
                                            }
                                        }
                                    }
                                }
                            }
                            $this->showMigrationMessageAndLog($order_slip_error_tmp, 'orderSlip');
                        }
                    }
                    // import Order History
                    foreach ($orders['order_history'][$order['id_order']] as $orderHistory) {
                        if ($orderHistoryModel = $this->createObjectModel('OrderHistory', $orderHistory['id_order_history'])) {
                            $orderHistoryModel->id_order = $orderModel->id;
                            $orderHistoryModel->id_order_state = self::getOrderStateID($orderHistory['id_order_state']);
                            $orderHistoryModel->id_customer_thread = self::getLocalID('employee', $orderHistory['id_employee'], 'data');
                            $orderHistoryModel->date_add = $orderHistory['date_add'];
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($orderHistoryModel);
                            $this->validator->checkFields();
                            $order_history_error_tmp = $this->validator->getValidationMessages();
                            if ($orderHistoryModel->id && OrderHistory::existsInDatabase($orderHistoryModel->id, 'order_history')) {
                                try {
                                    $res = $orderHistoryModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderHistoryModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order History (ID: %1$s) cannot be saved. %2$s')), (isset($orderHistory['id_order_history']) && !self::isEmpty($orderHistory['id_order_history'])) ? Tools::safeOutput($orderHistory['id_order_history']) : 'No ID', $err_tmp), 'OrderHistory');
                            } else {
                                self::addLog('OrderHistory', $orderHistory['id_order_history'], $orderHistoryModel->id);
                            }
                            $this->showMigrationMessageAndLog($order_history_error_tmp, 'OrderHistory');
                        }
                    }
                    // import Order Return
                    foreach ($orders['order_return'][$order['id_order']] as $orderReturn) {
                        if ($orderReturnModel = $this->createObjectModel('OrderReturn', $orderReturn['id_order_return'])) {
                            $orderReturnModel->id_order = $orderModel->id;
                            $orderReturnModel->id_customer = self::getLocalID('customer', $orderReturn['id_customer'], 'data');
                            $orderReturnModel->state = self::getOrderStateID((int)$orderReturn['state']);
                            $orderReturnModel->date_add = $orderReturn['date_add'];
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($orderReturnModel);
                            $this->validator->checkFields();
                            $order_return_error_tmp = $this->validator->getValidationMessages();
                            if ($orderReturnModel->id && OrderReturn::existsInDatabase($orderReturnModel->id, 'order_return')) {
                                try {
                                    $res = $orderReturnModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderReturnModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Return (ID: %1$s) cannot be saved. %2$s')), (isset($orderReturn['id_order_return']) && !self::isEmpty($orderReturn['id_order_return'])) ? Tools::safeOutput($orderReturn['id_order_return']) : 'No ID', $err_tmp), 'OrderReturn');
                            } else {
                                self::addLog('OrderReturn', $orderReturn['id_order_return'], $orderReturnModel->id);
                                $sql_values = array();
                                foreach ($orders['order_return_detail'][$orderReturn['id_order_return']] as $orderDetailReturn) {
                                    $sql_values[] = '(' . (int)$orderReturnModel->id . ', ' . (int)self::getLocalID('orderDetail', $orderDetailReturn['id_order_detail'], 'data') . ', ' . (int)$orderDetailReturn['id_customization'] . ', ' . (int)$orderDetailReturn['product_quantity'] . ')';
                                }
                            }
                            if (!self::isEmpty($sql_values)) {
                                $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'order_return_detail` (`id_order_return`, `id_order_detail`, `id_customization`, `product_quantity`) VALUES ' . implode(',', $sql_values));
                                if (!$result) {
                                    $this->showMigrationMessageAndLog(self::displayError('Can\'t add order_return_detail. ' . Db::getInstance()->getMsgError()), 'OrderReturn');
                                }
                            }
                            $this->showMigrationMessageAndLog($order_return_error_tmp, 'OrderReturn');
                        }
                    }
                    // import Order Carrier
                    if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                        if ($orderCarrierModel = $this->createObjectModel('OrderCarrier', $order['id_order'])) {
                            $orderCarrierModel->id_order = $orderModel->id;
                            $orderCarrierModel->id_carrier = self::getLocalID('carrier', $order['id_carrier'], 'data');
                            $orderCarrierModel->id_order_invoice = self::getLocalID('orderInvoice', $order['id_order'], 'data');
//                                $orderCarrierModel->weight = $orderCarrier['weight'];
                            $orderCarrierModel->shipping_cost_tax_excl = $orderModel->total_shipping_tax_excl;
                            $orderCarrierModel->shipping_cost_tax_incl = $orderModel->total_shipping_tax_incl;
                            $orderCarrierModel->tracking_number = $orderModel->shipping_number;
                            $orderCarrierModel->date_add = $order['date_add'];

                            $res = false;
                            $err_tmp = '';


                            $this->validator->setObject($orderCarrierModel);
                            $this->validator->checkFields();
                            $order_carrier_error_tmp = $this->validator->getValidationMessages();
                            if ($orderCarrierModel->id && OrderCarrier::existsInDatabase($orderCarrierModel->id, 'order_carrier')) {
                                try {
                                    $res = $orderCarrierModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderCarrierModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Carrier of Order (ID: %1$s) cannot be saved. %2$s')), (isset($order['id_order']) && !self::isEmpty($order['id_order'])) ? Tools::safeOutput($order['id_order']) : 'No ID', $err_tmp), 'OrderCarrier');
                            } else {
                                self::addLog('OrderCarrier', $order['id_order'], $orderCarrierModel->id);
                            }
                            $this->showMigrationMessageAndLog($order_carrier_error_tmp, 'OrderCarrier');
                        }
                    } else {
                        foreach ($orders['order_carrier'][$order['id_order']] as $orderCarrier) {
                            if ($orderCarrierModel = $this->createObjectModel('OrderCarrier', $orderCarrier['id_order_carrier'])) {
                                $orderCarrierModel->id_order = $orderModel->id;
                                $orderCarrierModel->id_carrier = self::getLocalID('carrier', $orderCarrier['id_carrier'], 'data');
                                $orderCarrierModel->id_order_invoice = self::getLocalID('orderInvoice', $orderCarrier['id_order_invoice'], 'data');
                                $orderCarrierModel->weight = $orderCarrier['weight'];
                                $orderCarrierModel->shipping_cost_tax_excl = $orderCarrier['shipping_cost_tax_excl'];
                                $orderCarrierModel->shipping_cost_tax_incl = $orderCarrier['shipping_cost_tax_incl'];
                                $orderCarrierModel->tracking_number = $orderCarrier['tracking_number'];
                                $orderCarrierModel->date_add = $orderCarrier['date_add'];
                                $res = false;
                                $err_tmp = '';


                                $this->validator->setObject($orderCarrierModel);
                                $this->validator->checkFields();
                                $order_carrier_error_tmp = $this->validator->getValidationMessages();
                                if ($orderCarrierModel->id && OrderCarrier::existsInDatabase($orderCarrierModel->id, 'order_carrier')) {
                                    try {
                                        $res = $orderCarrierModel->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $orderCarrierModel->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Carrier (ID: %1$s) cannot be saved. %2$s')), (isset($orderCarrier['id_order_carrier']) && !self::isEmpty($orderCarrier['id_order_carrier'])) ? Tools::safeOutput($orderCarrier['id_order_carrier']) : 'No ID', $err_tmp), 'OrderCarrier');
                                } else {
                                    self::addLog('OrderCarrier', $orderCarrier['id_order_carrier'], $orderCarrierModel->id);
                                }
                                $this->showMigrationMessageAndLog($order_carrier_error_tmp, 'OrderCarrier');
                            }
                        }
                    }
                    // import Order Cart Rule
                    foreach ($orders['order_cart_rule'][$order['id_order']] as $orderCartRule) {
                        if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                            $orderCartRuleId = 'id_order_discount';
                        } else {
                            $orderCartRuleId = 'id_order_cart_rule';
                        }
                        if ($orderCartRuleModel = $this->createObjectModel('OrderCartRule', $orderCartRule[$orderCartRuleId])) {
                            $orderCartRuleModel->id_order = $orderModel->id;
                            $orderCartRuleModel->name = $orderCartRule['name'];
                            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                                $orderCartRuleModel->id_cart_rule = self::getLocalID('cartRule', $orderCartRule['id_discount'], 'data');
                                $orderCartRuleModel->id_order_invoice = self::getLocalID('orderInvoice', $orderCartRule['id_order'], 'data');
                                $orderCartRuleModel->free_shipping = 0;
                                $orderCartRuleModel->value = isset($orderCartRule['value']) ? $orderCartRule['value'] : $orderModel->total_discounts_tax_incl;
                                $orderCartRuleModel->value_tax_excl = isset($orderCartRule['value']) ? $orderCartRule['value'] : $orderModel->total_discounts_tax_excl;
                            } else {
                                $orderCartRuleModel->id_cart_rule = self::getLocalID('cartRule', $orderCartRule['id_cart_rule'], 'data');
                                $orderCartRuleModel->id_order_invoice = self::getLocalID('orderInvoice', $orderCartRule['id_order_invoice'], 'data');
                                $orderCartRuleModel->free_shipping = $orderCartRule['free_shipping'];
                                $orderCartRuleModel->value = $orderCartRule['value'];
                                $orderCartRuleModel->value_tax_excl = $orderCartRule['value_tax_excl'];
                            }
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($orderCartRuleModel);
                            $this->validator->checkFields();
                            $order_cart_rule_error_tmp = $this->validator->getValidationMessages();
                            if ($orderCartRuleModel->id && OrderCartRule::existsInDatabase($orderCartRuleModel->id, 'order_cart_rule')) {
                                try {
                                    $res = $orderCartRuleModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $orderCartRuleModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Cart Rule (ID: %1$s) cannot be saved. %2$s')), (isset($orderCartRule[$orderCartRuleId]) && !self::isEmpty($orderCartRule[$orderCartRuleId])) ? Tools::safeOutput($orderCartRule[$orderCartRuleId]) : 'No ID', $err_tmp), 'OrderCartRule');
                            } else {
                                self::addLog('OrderCartRule', $orderCartRule[$orderCartRuleId], $orderCartRuleModel->id);
                            }
                            $this->showMigrationMessageAndLog($order_cart_rule_error_tmp, 'OrderCartRule');
                        }
                    }
                    // import Address
                    foreach ($orders['address'][$order['id_address_delivery']] as $address) {
                        if ($addressModel = $this->createObjectModel('Address', $address['id_address'])) {
                            $addressModel->id_customer = self::getLocalId('customer', $address['id_customer'], 'data');
                            $addressModel->id_manufacturer = self::getLocalId('manufacturer', $address['id_manufacturer'], 'data');
                            $addressModel->id_supplier = self::getLocalId('supplier', $address['id_supplier'], 'data');
                            $addressModel->id_country = $address['id_country'];
                            $addressModel->id_state = $address['id_state'];
                            $addressModel->alias = $address['alias'];
                            $addressModel->company = $address['company'];
                            $addressModel->lastname = $address['lastname'];
                            $addressModel->firstname = $address['firstname'];
                            $addressModel->vat_number = $address['vat_number'];
                            $addressModel->address1 = $address['address1'];
                            $addressModel->address2 = $address['address2'];
                            $addressModel->postcode = $address['postcode'];
                            $addressModel->city = $address['city'];
                            $addressModel->other = $address['other'];
                            $addressModel->phone = $address['phone'];
                            $addressModel->phone_mobile = $address['phone_mobile'];
                            $addressModel->dni = $address['dni'];
                            $addressModel->deleted = $address['deleted'];
                            $addressModel->date_add = $address['date_add'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $address['date_add'];
                            $addressModel->date_upd = $address['date_upd'] == '0000-00-00 00:00:00' ? date('Y-m-d H:i:s') : $address['date_upd'];
                            $addressModel->id_warehouse = $address['id_warehouse'];
                            $res = false;
                            $err_tmp = '';
                            $this->validator->setObject($addressModel);
                            $this->validator->checkFields();
                            $address_error_tmp = $this->validator->getValidationMessages();
                            if ($addressModel->id && Address::existsInDatabase($addressModel->id, 'address')) {
                                try {
                                    $res = $addressModel->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $addressModel->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Address (ID: %1$s) cannot be saved. %2$s')), (isset($address['id_address']) && !self::isEmpty($address['id_address'])) ? Tools::safeOutput($address['id_address']) : 'No ID', $err_tmp), 'Address');
                            } else {
                                // import country
                                foreach ($orders['country'][$address['id_country']] as $country) {
                                    if ($countryModel = $this->createObjectModel('Country', $country['id_country'])) {
                                        $countryModel->id_zone = $country['id_zone'];
                                        $countryModel->id_currency = self::getCurrencyID($country['id_currency']);
                                        $countryModel->call_prefix = $country['call_prefix'];
                                        $countryModel->iso_code = $country['iso_code'];
                                        $countryModel->active = $country['active'];
                                        $countryModel->contains_states = $country['contains_states'];
                                        $countryModel->need_identification_number = $country['need_identification_number'];
                                        $countryModel->need_zip_code = $country['need_zip_code'];
                                        $countryModel->zip_code_format = $country['zip_code_format'];
                                        $countryModel->display_tax_label = (isset($country['display_tax_label'])) ? (bool)$country['display_tax_label'] : true;

                                        // Add to _shop relations
                                        $id_shop_list = $this->getChangedIdShop(explode(',', $country['id_shop_list']), '');
                                        $countryModel->id_shop_list = $id_shop_list;
                                        //language fields

                                        foreach ($orders['country_lang'][$country['id_country']] as $lang) {
                                            $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                            $countryModel->name[$lang['id_lang']] = $lang['name'];
                                        }

                                        $res = false;
                                        $err_tmp = '';
                                        $this->validator->setObject($countryModel);
                                        $this->validator->checkFields();
                                        $country_error_tmp = $this->validator->getValidationMessages();
                                        if ($countryModel->id && Country::existsInDatabase($countryModel->id, 'country')) {
                                            try {
                                                $res = $countryModel->update();
                                            } catch (PrestaShopException $e) {
                                                $err_tmp = $e->getMessage();
                                            }
                                        }
                                        if (!$res) {
                                            try {
                                                $res = $countryModel->add(false);
                                            } catch (PrestaShopException $e) {
                                                $err_tmp = $e->getMessage();
                                            }
                                        }
                                        if (!$res) {
                                            $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Country (ID: %1$s) cannot be saved. %2$s')), (isset($country['id_country']) && !self::isEmpty($country['id_country'])) ? Tools::safeOutput($country['id_country']) : 'No ID', $err_tmp), 'Country');
                                        } else {
                                            self::addLog('Country', $country['id_country'], $countryModel->id);
                                        }
                                        $this->showMigrationMessageAndLog($country_error_tmp, 'Country');
                                    }
                                }

                                // import State
                                foreach ($orders['state'][$address['id_state']] as $state) {
                                    if ($stateModel = $this->createObjectModel('State', $state['id_state'])) {
                                        $stateModel->id_country = self::getLocalId('country', $state['id_country'], 'data');
                                        $stateModel->id_zone = $state['id_zone'];
                                        $stateModel->iso_code = $state['iso_code'];
                                        $stateModel->active = $state['active'];
                                        $stateModel->name = $state['name'];
                                        $res = false;
                                        $err_tmp = '';
                                        $this->validator->setObject($stateModel);
                                        $this->validator->checkFields();
                                        $state_error_tmp = $this->validator->getValidationMessages();
                                        if ($stateModel->id && State::existsInDatabase($stateModel->id, 'state')) {
                                            try {
                                                $res = $stateModel->update();
                                            } catch (PrestaShopException $e) {
                                                $err_tmp = $e->getMessage();
                                            }
                                        }
                                        if (!$res) {
                                            try {
                                                $res = $stateModel->add(false);
                                            } catch (PrestaShopException $e) {
                                                $err_tmp = $e->getMessage();
                                            }
                                        }
                                        if (!$res) {
                                            $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('State (ID: %1$s) cannot be saved. %2$s')), (isset($state['id_state']) && !self::isEmpty($state['id_state'])) ? Tools::safeOutput($state['id_state']) : 'No ID', $err_tmp), 'State');
                                        } else {
                                            self::addLog('State', $state['id_state'], $stateModel->id);
                                        }
                                        $this->showMigrationMessageAndLog($state_error_tmp, 'State');
                                    }
                                }
                                if (count($this->error_msg) == 0) {
                                    self::addLog('Address', $address['id_address'], $addressModel->id);
                                }
                            }
                            $this->showMigrationMessageAndLog($address_error_tmp, 'Address');
                        }
                    }

                    foreach ($orders['message'][$order['id_order']] as $message) {
                        if ($message['id_order'] == $order['id_order']) {
                            if ($messageObject = $this->createObjectModel('Message', $message['id_message'])) {
                                $messageObject->id_cart = self::getLocalID('cart', $message['id_cart'], 'data');
                                $messageObject->id_customer = self::getLocalID('customer', $message['id_customer'], 'data');
                                $messageObject->id_employee = self::getLocalID('employee', $message['id_employee'], 'data');
                                $messageObject->id_order = $orderModel->id;
                                $messageObject->message = $message['message'];
                                $messageObject->private = $message['private'];
                                $messageObject->date_add = $message['date_add'];
                                if (self::isEmpty($message['date_add']) || $message['date_add'] == '0000-00-00 00:00:00') {
                                    $messageObject->date_add = date('Y-m-d H:i:s');
                                }
                                if (self::isEmpty($messageObject->message)) {
                                    $this->showMigrationMessageAndLog('Message with ID ' . $message['id_message'] . ' has not a message text and it is not allowed in PrestaShop. For that reason, the module skipped this unvalid message.', 'Message', true);
                                    continue;
                                }
                                $res = false;
                                $err_tmp = '';

                                $this->validator->setObject($messageObject);
                                $this->validator->checkFields();
                                $message_error_tmp = $this->validator->getValidationMessages();
                                if ($messageObject->id && Address::existsInDatabase($messageObject->id, 'message')) {
                                    try {
                                        $res = $messageObject->update();
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }
                                if (!$res) {
                                    try {
                                        $res = $messageObject->add(false);
                                    } catch (PrestaShopException $e) {
                                        $err_tmp = $e->getMessage();
                                    }
                                }

                                if (!$res) {
                                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Message (ID: %1$s) cannot be saved. %2$s')), (isset($message['id_address']) && !self::isEmpty($message['id_address'])) ? Tools::safeOutput($message['id_address']) : 'No ID', $err_tmp), 'Message');
                                } else {
                                    //import Message Readed
                                    $sql_values = array();
                                    foreach ($orders['message_readed'][$message['id_message']] as $messageReaded) {
                                        $sql_values[] = '(' . (int)$messageObject->id . ', ' . (int)self::getLocalID('employee', $messageReaded['id_employee'], 'data') . ', "' . pSQL($messageReaded['date_add']) . '")';
                                    }
                                    if (!self::isEmpty($sql_values)) {
                                        $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'message_readed` (`id_message`, `id_employee`, `date_add`) VALUES ' . implode(',', $sql_values));
                                        if (!$result) {
                                            $this->showMigrationMessageAndLog(self::displayError('Can\'t add message_readed. ' . Db::getInstance()->getMsgError()), 'Message');
                                        }
                                    }
                                    self::addLog('Message', $message['id_message'], $messageObject->id);
                                }
                                $this->showMigrationMessageAndLog($message_error_tmp, 'Message');
                            }
                        }
                    }

                    if (count($this->error_msg) == 0) {
                        self::addLog('Order', $order['id_order'], $orderModel->id);
                    }
                }
                $this->showMigrationMessageAndLog($order_error_tmp, 'Order');
            }
        }

        // Import Order Messages
        foreach ($orders['order_message'] as $orderMessage) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($orderMessageModel = $this->createObjectModel('OrderMessage', $orderMessage['id_order_message'])) {
                $orderMessageModel->date_add = $orderMessage['date_add'];
                foreach ($orders['order_message_lang'][$orderMessage['id_order_message']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $orderMessageModel->name[$lang['id_lang']] = $lang['name'];
                    $orderMessageModel->message[$lang['id_lang']] = $lang['message'];
                }

                $res = false;
                $err_tmp = '';
                $this->validator->setObject($orderMessageModel);
                $this->validator->checkFields();
                $order_message_error_tmp = $this->validator->getValidationMessages();
                if ($orderMessageModel->id && OrderMessage::existsInDatabase($orderMessageModel->id, 'order_message')) {
                    try {
                        $res = $orderMessageModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $orderMessageModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Order Message (ID: %1$s) cannot be saved. %2$s')), (isset($orderMessage['id_order_message']) && !self::isEmpty($orderMessage['id_order_message'])) ? Tools::safeOutput($orderMessage['id_order_message']) : 'No ID', $err_tmp), 'OrderMessage');
                } else {
                    self::addLog('OrderMessage', $orderMessage['id_order_message'], $orderMessageModel->id);
                }
                $this->showMigrationMessageAndLog($order_message_error_tmp, 'OrderMessage');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $customerMessages
     * @param $customerThreads
     */
    public function customerMessages($customerThreads)
    {
        // Import Customer Threads
        $count = 0;
        foreach ($customerThreads['customerThreads'] as $customerThread) {
            //            if ($order['id_order'] == $customerThread['id_order']) {
//                if (version_compare($this->version, '1.6', '<')) {
//                    $objId = $customerThread['id_order'];
//                } else {
            $objId = $customerThread['id_customer_thread'];
//                }
            $id_customer = self::getLocalID('customer', $customerThread['id_customer'], 'data');
//                $customer= new Customer($id_customer);
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($customerThreadObject = $this->createObjectModel('CustomerThread', $objId)) {
                if (version_compare($this->version, '1.6', '<')) {
                    $customerThreadObject->id_shop = Configuration::get('PS_SHOP_DEFAULT');
                    $customerThreadObject->id_lang = Configuration::get('PS_LANG_DEFAULT');
                    $customerThreadObject->id_contact = 0;
                    $customerThreadObject->id_customer = $id_customer;
                    $customerThreadObject->id_order = self::getLocalID('order', $customerThread['id_order'], 'data');
                    $customerThreadObject->id_product = self::getLocalID('product', $customerThread['id_product'], 'data');
//                        $customerThreadObject->status = $customer->active;
                    $customerThreadObject->status = $customerThread['status'];
//                        $customerThreadObject->email = $customer->email;
                    $customerThreadObject->email = $customerThread['email'];
//                        $customerThreadObject->token = md5($customerThread['id_message']);
                    $customerThreadObject->token = $customerThread['token'];
                    $customerThreadObject->date_add = $customerThread['date_add'];
                    $customerThreadObject->date_upd = $customerThread['date_upd'];
                } else {
                    $customerThreadObject->id_shop = self::getShopID($customerThread['id_shop']);
                    $customerThreadObject->id_lang = self::getLanguageID($customerThread['id_lang']);
                    $customerThreadObject->id_contact = $customerThread['id_contact'];
                    $customerThreadObject->id_customer = $id_customer;
                    $customerThreadObject->id_order = self::getLocalID('order', $customerThread['id_order'], 'data');
                    $customerThreadObject->id_product = self::getLocalID('product', $customerThread['id_product'], 'data');
                    $customerThreadObject->status = $customerThread['status'];
                    $customerThreadObject->email = $customerThread['email'];
                    $customerThreadObject->token = $customerThread['token'];
                    $customerThreadObject->date_add = $customerThread['date_add'];
                    $customerThreadObject->date_upd = $customerThread['date_upd'];
                }
                $res = false;
                $err_tmp = '';

                $this->validator->setObject($customerThreadObject);
                $this->validator->checkFields();
                $customer_thread_error_tmp = $this->validator->getValidationMessages();
                if ($customerThreadObject->id && CustomerThread::existsInDatabase($customerThreadObject->id, 'customer_thread')) {
                    try {
                        $res = $customerThreadObject->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $customerThreadObject->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Customer Thread (ID: %1$s) cannot be saved. %2$s')), (isset($objId) && !self::isEmpty($objId)) ? Tools::safeOutput($objId) : 'No ID', $err_tmp), 'CustomerThread');
                } else {
                    foreach ($customerThreads['customerMessages'][$customerThread['id_customer_thread']] as $customerMessage) {
//                                if (version_compare($this->version, '1.6', '<')) {
//                                    $key = 'id_order';
//                                    $objId= $customerMessage['id_message'];
//                                } else {
                        $msgObjId = $customerMessage['id_customer_message'];
//                                }
                        if ($customerMessageObject = $this->createObjectModel('CustomerMessage', $msgObjId)) {
                            if (version_compare($this->version, '1.6', '<')) {
                                $customerMessageObject->id_customer_thread = $customerThreadObject->id;
                                $customerMessageObject->id_employee = self::getLocalID('employee', $customerMessage['id_employee'], 'data');
                                $customerMessageObject->message = html_entity_decode($customerMessage['message']) ?: Tools::htmlentitiesDecodeUTF8($customerMessage['message']);
                                $customerMessageObject->file_name = $customerMessage['file_name'];
                                $customerMessageObject->ip_address = $customerMessage['ip_address'];
                                $customerMessageObject->user_agent = $customerMessage['user_agent'];
                                $customerMessageObject->date_add = $customerMessage['date_add'];
                                if (self::isEmpty($customerMessage['date_upd']) || $customerMessage['date_upd'] == '0000-00-00 00:00:00') {
                                    $customerMessageObject->date_upd = date('Y-m-d H:i:s');
                                } else {
                                    $customerMessageObject->date_upd = $customerMessage['date_upd'];
                                }
                                $customerMessageObject->private = $customerMessage['private'];
                            } else {
                                $customerMessageObject->id_customer_thread = $customerThreadObject->id;
                                $customerMessageObject->id_employee = self::getLocalID('employee', $customerMessage['id_employee'], 'data');
                                $customerMessageObject->message = $customerMessage['message'];
                                $customerMessageObject->file_name = $customerMessage['file_name'];
                                $customerMessageObject->ip_address = $customerMessage['ip_address'];
                                $customerMessageObject->user_agent = $customerMessage['user_agent'];
                                $customerMessageObject->date_add = $customerMessage['date_add'];
                                if (self::isEmpty($customerMessage['date_upd']) || $customerMessage['date_upd'] == '0000-00-00 00:00:00') {
                                    $customerMessageObject->date_upd = date('Y-m-d H:i:s');
                                } else {
                                    $customerMessageObject->date_upd = $customerMessage['date_upd'];
                                }
                            }


                            $res = false;
                            $err_tmp = '';

                            $this->validator->setObject($customerMessageObject);
                            $this->validator->checkFields();
                            $customer_message_error_tmp = $this->validator->getValidationMessages();
                            if ($customerMessageObject->id && Address::existsInDatabase($customerMessageObject->id, 'customer_message')) {
                                try {
                                    $res = $customerMessageObject->update();
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }
                            if (!$res) {
                                try {
                                    $res = $customerMessageObject->add(false);
                                } catch (PrestaShopException $e) {
                                    $err_tmp = $e->getMessage();
                                }
                            }

                            if (!$res) {
                                $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Customer message (ID: %1$s) cannot be saved. %2$s')), (isset($objId) && !self::isEmpty($objId)) ? Tools::safeOutput($objId) : 'No ID', $err_tmp), 'CustomerMessage');
                            } else {
                                self::addLog('CustomerMessage', $msgObjId, $customerMessageObject->id);
                            }
                            $this->showMigrationMessageAndLog($customer_message_error_tmp, 'CustomerMessage');
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        self::addLog('CustomerThread', $objId, $customerThreadObject->id);
                    }
                }
                $this->showMigrationMessageAndLog($customer_thread_error_tmp, 'CustomerThread');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $cmses
     * @param $cmsAdditionalSecond
     * @param $cmsAdditionalThird
     */
    public function cmses($cmses)
    {
        $count = 0;
        // Import CMS Category
        foreach ($cmses['cms_category'] as $cmsCategory) {
            if ($this->module->isPaused()) {
                break;
            }
            if ($cmsCategoryModel = $this->createObjectModel('CMSCategory', $cmsCategory['id_cms_category'])) {
                $cmsCategoryModel->id_parent = self::getLocalID('cmscategory', $cmsCategory['id_parent'], 'data');
                if (self::isEmpty($cmsCategoryModel->id_parent)) {
                    $cmsCategoryModel->id_parent = 0;
                }
                $cmsCategoryModel->active = $cmsCategory['active'];
                $cmsCategoryModel->date_add = $cmsCategory['date_add'];
                $cmsCategoryModel->date_upd = $cmsCategory['date_upd'];
                foreach ($cmses['cms_category_lang'][$cmsCategory['id_cms_category']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $cmsCategoryModel->name[$lang['id_lang']] = $lang['name'];
                    $cmsCategoryModel->description[$lang['id_lang']] = $lang['description'];
                    $cmsCategoryModel->meta_title[$lang['id_lang']] = $lang['meta_title'];
                    $cmsCategoryModel->meta_keywords[$lang['id_lang']] = $lang['meta_keywords'];
                    $cmsCategoryModel->meta_description[$lang['id_lang']] = $lang['meta_description'];
                    $cmsCategoryModel->link_rewrite[$lang['id_lang']] = $lang['link_rewrite'];

                    if (isset($cmsCategoryModel->link_rewrite[$lang['id_lang']]) && !self::isEmpty($cmsCategoryModel->link_rewrite[$lang['id_lang']])) {
                        $valid_link = Validate::isLinkRewrite($cmsCategoryModel->link_rewrite[$lang['id_lang']]);
                    } else {
                        $valid_link = false;
                    }
                    if (!$valid_link) {
                        $cmsCategoryModel->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($cmsCategoryModel->name[$lang['id_lang']]);
                        if ($cmsCategoryModel->link_rewrite[$lang['id_lang']] == '') {
                            $cmsCategoryModel->link_rewrite[$lang['id_lang']] = 'friendly-url-autogeneration-failed';
                            $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $cmsCategoryModel->name[$lang['id_lang']]), 'CMSCategory');
                        }
                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $lang['link_rewrite'], (isset($cmsCategory['id_cms_category']) && !self::isEmpty($cmsCategory['id_cms_category'])) ? $cmsCategory['id_cms_category'] : 'null', $cmsCategoryModel->link_rewrite[$lang['id_lang']]), 'CMSCategory');
                    }
                }

                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $cmsCategory['id_shop_list']), '');
                $cmsCategoryModel->id_shop_list = $id_shop_list;


                $res = false;
                $err_tmp = '';

                $this->validator->setObject($cmsCategoryModel);
                $this->validator->checkFields();
                $cms_category_error_tmp = $this->validator->getValidationMessages();
                if ($cmsCategoryModel->id && CMSCategory::existsInDatabase($cmsCategoryModel->id, 'cms_category')) {
                    try {
                        $res = $cmsCategoryModel->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    try {
                        $res = $cmsCategoryModel->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('CMS Category(ID: %1$s) cannot be saved. %2$s')), (isset($cmsCategory['id_cms_category']) && !self::isEmpty($cmsCategory['id_cms_category'])) ? Tools::safeOutput($cmsCategory['id_cms_category']) : 'No ID', $err_tmp), 'CMSCategory');
                } else {
                    // Import CMS Block
                    if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                        foreach ($cmses['cms_block'][$cmsCategory['id_cms_category']] as $cmsBlock) {
                            $sql_value = '';
                            $sql_value = '(' . (int)$cmsCategoryModel->id . ', ' . pSQL($cmsBlock['location']) . ', ' . (int)$cmsBlock['position'] . ', ' . pSQL($cmsBlock['display_store']) . ')';

                            if (!self::isEmpty($sql_value)) {
                                $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cms_block` (`id_cms_category`, `location`,
                                    `position`, `display_store`)
                                VALUES ' . $sql_value);
                                if (!$result) {
                                    $this->showMigrationMessageAndLog(self::displayError('Can\'t add cms_block. ' . Db::getInstance()->getMsgError()), 'CMS');
                                } else {
                                    $id_cms_block = Db::getInstance()->Insert_ID();

                                    // Import CMS Block Lang
                                    foreach ($cmses['cms_block_lang'][$cmsBlock['id_cms_block']] as $cmsBlockLang) {
                                        $sql_value = '';
                                        $sql_value = '(' . (int)$id_cms_block . ', ' . self::getLanguageID($cmsBlockLang['id_lang']) . ', \'' . pSQL($cmsBlockLang['name']) . '\')';
                                        if (!self::isEmpty($sql_value)) {
                                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cms_block_lang` (`id_cms_block`, `id_lang`, `name`)
                                                                VALUES ' . $sql_value);
                                            if (!$result) {
                                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add cms_block_lang. ' . Db::getInstance()->getMsgError()), 'CMS');
                                            }
                                        }
                                    }

                                    // Import CMS Block Shop explode(',', $cmsBlock['id_shop_list'])
                                    foreach ($id_shop_list as $cmsBlockShop) {
                                        $sql_value = '';
                                        if ($cmsBlockShop['id_cms_block'] == $cmsBlock['id_cms_block']) {
                                            $sql_value = '(' . (int)$id_cms_block . ', ' . self::getShopID($cmsBlockShop) . ')';
                                        }

                                        if (!self::isEmpty($sql_value)) {
                                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cms_block_shop` (`id_cms_block`, `id_shop`)
                                                                VALUES ' . $sql_value);
                                            if (!$result) {
                                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add cms_block_shop. ' . Db::getInstance()->getMsgError()), 'CMS');
                                            }
                                        }
                                    }
                                    self::addLog('CMSBLOCK', $cmsBlock['id_cms_block'], $id_cms_block);
                                }
                            }
                        }
                    }
                    if (count($this->error_msg) == 0) {
                        self::addLog('CMSCategory', $cmsCategory['id_cms_category'], $cmsCategoryModel->id);

                        //update multistore language fields
                        if (!version_compare($this->version, '1.6', '<')) {
                            if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                                foreach ($cmses['cms_category_lang'][$cmsCategory['id_cms_category']] as $lang) {
                                    $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                    $lang['id_cms_category'] = $cmsCategoryModel->id;
                                    self::updateMultiStoreLang('cms_category', $lang);
                                }
                            }
                        }
                    }
                }
                $this->showMigrationMessageAndLog($cms_category_error_tmp, 'CMSCategory');
            }
        }
        foreach ($cmses['cms'] as $cms) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($cmsObj = $this->createObjectModel('CMS', $cms['id_cms'])) {
                $cmsObj->id_cms_category = self::getLocalID('CMSCategory', $cms['id_cms_category'], 'data');
                if (self::isEmpty($cmsObj->id_cms_category)) {
                    $cmsObj->id_cms_category = 1;
                }
                $cmsObj->position = $cms['position'];
                $cmsObj->active = $cms['active'];
                $cmsObj->indexation = $cms['indexation'];
                foreach ($cmses['cms_lang'][$cms['id_cms']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $cmsObj->meta_title[$lang['id_lang']] = $lang['meta_title'];
                    $cmsObj->meta_description[$lang['id_lang']] = $lang['meta_description'];
                    $cmsObj->meta_keywords[$lang['id_lang']] = $lang['meta_title'];
                    $cmsObj->content[$lang['id_lang']] = $lang['content'];
                    $cmsObj->link_rewrite[$lang['id_lang']] = $lang['link_rewrite'];
                    if (isset($cmsObj->link_rewrite[$lang['id_lang']]) && !self::isEmpty($cmsObj->link_rewrite[$lang['id_lang']])) {
                        $valid_link = Validate::isLinkRewrite($cmsObj->link_rewrite[$lang['id_lang']]);
                    } else {
                        $valid_link = false;
                    }
                    if (!$valid_link) {
                        $cmsObj->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($cmsObj->name[$lang['id_lang']]);

                        if ($cmsObj->link_rewrite[$lang['id_lang']] == '') {
                            $cmsObj->link_rewrite[$lang['id_lang']] = 'friendly-url-autogeneration-failed';
                            $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('URL rewriting failed to auto-generate a friendly URL for: %s')), $cmsObj->name[$lang['id_lang']]), 'CMS');
                        }

                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('The link for %1$s (ID: %2$s) was re-written as %3$s.')), $lang['link_rewrite'], (isset($cms['id_cms']) && !self::isEmpty($cms['id_cms'])) ? $cms['id_cms'] : 'null', $cmsObj->link_rewrite[$lang['id_lang']]), 'CMS');
                    }
                }

                // Add to _shop relations
                $id_shop_list = $this->getChangedIdShop(explode(',', $cms['id_shop_list']), '');
                $cmsObj->id_shop_list = $id_shop_list;
                $res = false;
                $err_tmp = '';
                $this->validator->setObject($cmsObj);
                $this->validator->checkFields();
                $cms_error_tmp = $this->validator->getValidationMessages();
                if ($cmsObj->id && CMS::existsInDatabase($cmsObj->id, 'cms')) {
                    try {
                        $res = $cmsObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $cmsObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('CMS (ID: %1$s) cannot be saved. %2$s')), (isset($cms['id_cms']) && !self::isEmpty($cms['id_cms'])) ? Tools::safeOutput($cms['id_cms']) : 'No ID', $err_tmp), 'CMS');
                } else {
                    // Import CMS Role
                    if (!($this->version < 1.6)) {
                        foreach ($cmses['cms_role'][$cms['id_cms']] as $cmsRole) {
                            if ($cmsRole['id_cms'] == $cms['id_cms']) {
                                if ($cmsRoleModel = $this->createObjectModel('CMSRole', $cmsRole['id_cms_role'])) {
                                    foreach ($cmses['cms_role_lang'][$cmsRole['id_cms_role']] as $lang) {
                                        $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                        $cmsObj->name[$lang['id_lang']] = $lang['name'];
                                        $cmsObj->id_cms = $cmsObj->id;
                                    }
                                    $cmsRoleModel->name = $cmsRole['name'];
                                    $cmsRoleModel->id_cms = $cmsObj->id;

                                    $res = false;
                                    $err_tmp = '';

                                    $this->validator->setObject($cmsRoleModel);
                                    $this->validator->checkFields();
                                    $cms_rule_error_tmp = $this->validator->getValidationMessages();
                                    if ($cmsRoleModel->id && CMSRole::existsInDatabase($cmsRoleModel->id, 'cms_role')) {
                                        try {
                                            $res = $cmsRoleModel->update();
                                        } catch (PrestaShopException $e) {
                                            $err_tmp = $e->getMessage();
                                        }
                                    }
                                    if (!$res) {
                                        try {
                                            $res = $cmsRoleModel->add(false);
                                        } catch (PrestaShopException $e) {
                                            $err_tmp = $e->getMessage();
                                        }
                                    }

                                    if (!$res) {
                                        $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('CMS Role(ID: %1$s) cannot be saved. %2$s')), (isset($cmsRole['id_cms_role']) && !self::isEmpty($cmsRole['id_cms_role'])) ? Tools::safeOutput($cmsRole['id_cms_role']) : 'No ID', $err_tmp), 'CMSRole');
                                    } else {
                                        self::addLog('CMSRole', $cmsRole['id_cms_role'], $cmsRoleModel->id);
                                        //update multistore language fields
                                        if (!version_compare($this->version, '1.6', '<')) {
                                            if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                                                foreach ($cmses['cms_role_lang'][$cmsRole['id_cms_role']] as $lang) {
                                                    $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                                    $lang['id_cms_role'] = $cmsRoleModel->id;
                                                    self::updateMultiStoreLang('cms_role', $lang);
                                                }
                                            }
                                        }
                                    }
                                    $this->showMigrationMessageAndLog($cms_rule_error_tmp, 'CMSRole');
                                }
                            }
                            self::addLog('CMSRole', $cms['id_cms'], $cmsObj->id);
                        }
                    }

                    if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                        // Import CMS Block Page
                        $sql_values = array();
                        foreach ($cmses['cms_block_page'][$cms['id_cms']] as $cmsBlockPage) {
                            $sql_values[] = '(' . self::getLocalID('cmsBlock', $cmsBlockPage['id_cms_block'], 'data') . ', ' . (int)$cmsObj->id . ', ' . $cmsBlockPage['is_category'] . ')';
                        }

                        if (!self::isEmpty($sql_values)) {
                            $result = Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'cms_block_page` (`id_cms_block`, `id_cms`,
                                                                `is_category`)
                                                                VALUES ' . implode(',', $sql_values));
                            if (!$result) {
                                $this->showMigrationMessageAndLog(self::displayError('Can\'t add cms_block_page. ' . Db::getInstance()->getMsgError()), 'CMS');
                            }
                        }
                    }

                    if (count($this->error_msg) == 0) {
                        self::addLog('CMS', $cms['id_cms'], $cmsObj->id);
                        //update multistore language fields
                        if (!version_compare($this->version, '1.6', '<')) {
                            if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                                foreach ($cmses['cms_lang'][$cms['id_cms']] as $lang) {
                                    $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                    $lang['id_cms'] = $cmsObj->id;
                                    self::updateMultiStoreLang('cms', $lang);
                                }
                            }
                        }
                    }
                }
                $this->showMigrationMessageAndLog($cms_error_tmp, 'CMS');
            }
        }
        $this->updateProcess($count);
    }

    /**
     * @param $metas
     * @param $metaLang
     */
    public function metas($metas)
    {
        $count = 0;
        foreach ($metas['meta'] as $meta) {
            // if (in_array($meta['page'], Meta::getpages())) {
            if ($this->module->isPaused()) {
                break;
            }
            $count++;
            if ($metaObj = $this->createObjectModel('Meta', $meta['id_meta'])) {
                $metaObj->page = $meta['page'];
                $metaObj->configurable = $meta['configurable'];
                if (self::isEmpty($metaObj->configurable)) {
                    $metaObj->configurable = 1;
                }
                foreach ($metas['meta_lang'][$meta['id_meta']] as $lang) {
                    $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                    $metaObj->title[$lang['id_lang']] = $lang['title'];
                    $metaObj->description[$lang['id_lang']] = $lang['description'];
                    $metaObj->keywords[$lang['id_lang']] = $lang['keywords'];
                    $metaObj->url_rewrite[$lang['id_lang']] = $lang['url_rewrite'];

                    if (!ValidateCore::isLinkRewrite($metaObj->url_rewrite[$lang['id_lang']])) {
                        $metaObj->url_rewrite[$lang['id_lang']] = Tools::link_rewrite($lang['title']);
                    }
                }

                $res = false;
                $err_tmp = '';

                $this->validator->setObject($metaObj);
                $this->validator->checkFields();
                $meta_error_tmp = $this->validator->getValidationMessages();
                if (Db::getInstance()->getValue('SELECT * FROM ' . _DB_PREFIX_ . 'meta WHERE page = \'' . $meta['page'] . '\'') != 0) {
                    try {
                        $res = $metaObj->update();
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }

                if (!$res) {
                    try {
                        $res = $metaObj->add(false);
                    } catch (PrestaShopException $e) {
                        $err_tmp = $e->getMessage();
                    }
                }
                if (!$res) {
                    $this->showMigrationMessageAndLog(sprintf(self::displayError($this->module->l('Meta (ID: %1$s) cannot be saved. %2$s')), (isset($meta['id_meta']) && !self::isEmpty($meta['id_meta'])) ? Tools::safeOutput($meta['id_meta']) : 'No ID', $err_tmp), 'meta');
                } else {
                    $url = $this->url . $this->image_path . $meta['id_meta'] . '.jpg';

                    $FilePath = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/metas/' . $meta['id_meta'] . '.jpg';

                    if (file_exists($FilePath) && !isset($this->NotFoundImages[$url]) && !(EDImport::copyImg($metaObj->id, null, $FilePath, 'metas', $this->regenerate))) {
                        $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('cannot be copied.')), 'meta', true);
                    }

                    self::addLog('meta', $meta['id_meta'], $metaObj->id);

                    //update multistore language fields
                    if (!version_compare($this->version, '1.5', '<')) {
                        if (MigrationProMapping::getMapTypeCount('multi_shops') > 1) {
                            foreach ($metas['meta_lang'][$meta['id_meta']] as $lang) {
                                $lang['id_shop'] = self::getShopID($lang['id_shop']);
                                $lang['id_lang'] = self::getLanguageID($lang['id_lang']);
                                $lang['id_meta'] = $metaObj->id;
                                self::updateMultiStoreLang('meta', $lang);
                            }
                        }
                    }
                }
                $this->showMigrationMessageAndLog($meta_error_tmp, 'meta');
            }
            // }
        }
        $this->updateProcess($count);
    }

    // --- Internal helper methods:

    public function createObjectModel($className, $objectID, $table_name = '')
    {
        if (!MigrationProData::exist($className, $objectID)) {
            // -- if keep old IDs and if exists in DataBase
            // -- else  isset($objectID) 1&& (int)$objectID

            if (!self::isEmpty($table_name)) {
                $existInDataBase = self::existsInDatabase((int)$objectID, Tools::strtolower($table_name), Tools::strtolower($className));
            } else {
                $existInDataBase = $className::existsInDatabase((int)$objectID, $className::$definition['table']);
                // [For PrestaShop Team] - This code call class definition attribute extended from ObjectModel class
                // like Order::$definition
            }

            if ($existInDataBase && $this->force_ids) {
                $this->obj = new $className((int)$objectID);
            } else {
                $this->obj = new $className();
            }

            if ($this->force_ids) {
                $this->obj->force_id = true;
                $this->obj->id = $objectID;
            }
            return $this->obj;
        }
    }

    private function updateProcess($count)
    {
        if (!count($this->error_msg)) {
            $this->process->imported += $count;//@TODO count of item
            $this->response['error'] = '';
        } else {
            if (!$this->ps_validation_errors) {
                $this->error_msg[] = self::displayError($this->module->l('Something went wrong. Source server return with null'));
            }
            $this->response['error'] = self::displayError($this->module->l('Something went wrong. Source server return with null'));
        }
        $this->process->error_count = 0;
        if ($this->process->total <= $this->process->imported) {
            $this->process->finish = 1;
            $this->response['execute_time'] = number_format((time() - strtotime($this->process->time_start)), 3, '.', '');
        }
        $this->response['type'] = $this->process->type;
        $this->response['total'] = (int)$this->process->total;
        $this->response['imported'] = (int)$this->process->imported;
        if ($this->process->finish == 1) {
            $this->response['process'] = 'finish';
            $type = $this->response['type'] == 'taxes' ? 'tax' : $this->response['type'];
            $this->process->error_count = MigrationProDBWarningLogger::getWarningLogsCount($type);
        } else {
            $this->response['process'] = 'continue';
        }

        $this->process->save();
        if (!MigrationProProcess::getActiveProcessObject()) {
            $allWarningMessages = $this->logger->getAllWarnings();
            $this->warning_msg = $allWarningMessages;
        }
    }

    private static function existsInDatabase($id_entity, $table, $entity_name)
    {
        $row = Db::getInstance()->getRow('
			SELECT `id_' . bqSQL($entity_name) . '` as id
			FROM `' . _DB_PREFIX_ . bqSQL($table) . '` e
			WHERE e.`id_' . bqSQL($entity_name) . '` = ' . (int)$id_entity, false);

        return isset($row['id']);
    }

    /**
     * Loading images from source server to local with MultiThread method curl_multi_init()
     * @param array $ImageIds Array with IDs
     * @param string $Key Key of array where ID
     * @param string $Entity Sub directory in temporary directory
     * @param string $Host Host address
     * @param string $EndDir End directory  in host
     */
    protected function loadImagesToLocal($ImageIds, $Key, $Entity, $Host, $EndDir, $ClearTemporaryDirectory = true)
    {
        try {
            $urls = array();
            //Generating  urls from image IDs
            foreach ($ImageIds as $ImageId) {
                if ($Entity === 'products') {
                    if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                        $urls[] = $Host . $EndDir . $ImageId['id_image'] . '.jpg';
                    } else {
                        $urls[] = $Host . $EndDir . Image::getImgFolderStatic($ImageId[$Key]) . (int)$ImageId['id_image'] . '.jpg';
                    }
                } else {
                    $urls[] = $Host . $EndDir . (int)$ImageId[$Key] . '.jpg';
                }
            }
            $path = _PS_TMP_IMG_DIR_ . '/mp_temp_dir/' . $Entity;
            //Checking exist  temporary path on server
            if (!file_exists($path) && !is_dir($path)) {
                // Ckecking root temporary path on server
                if (!file_exists(_PS_TMP_IMG_DIR_ . '/mp_temp_dir') && !is_dir(_PS_TMP_IMG_DIR_ . '/mp_temp_dir')) {
                    mkdir(_PS_TMP_IMG_DIR_ . '/mp_temp_dir', 0777);
                }
                mkdir($path, 0777);
            }
            //Removing all temporary files
            if ($ClearTemporaryDirectory) {
                array_map('unlink', glob("$path/*.*"));
            }
            $curlArr = array();
            $i = 0;
            $master = curl_multi_init();
            //Options for  CURLs array
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_CONNECTTIMEOUT => 10000,
                CURLOPT_TIMEOUT => 10000,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
                CURL_HTTP_VERSION_1_1 => 1
            );
            //set options for each urls
            foreach ($urls as $url) {
                $curlArr[$i] = curl_init();
                curl_setopt($curlArr[$i], CURLOPT_URL, $url);
                curl_setopt_array($curlArr[$i], $options);
                curl_multi_add_handle($master, $curlArr[$i]);
                $i++;
            }
            //Beginning load all images
            $running = null;
            $i = 0;
            do {
                curl_multi_exec($master, $running);
                usleep(5);
            } while ($running > 0);

            //Copying  images to temporary dir, Closing  Curls & Removing curls from curl_multi
            foreach ($urls as $url) {
                $httpCode = curl_getinfo($curlArr[$i], CURLINFO_HTTP_CODE);
                //If image  found on source server start copy
                if ($httpCode === 200) {
                    $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);

                    $fileExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $filePath = $path . '/' . $filename . '.' . $fileExt;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    $file = fopen($filePath, 'x');

                    $contents = curl_multi_getcontent($curlArr[$i]);

                    fwrite($file, $contents);

                    fclose($file);
                } else if ($httpCode === 404) {
                    $this->NotFoundImages[$url] = false;
                } else {
                    $this->showMigrationMessageAndLog($url . ' ' . self::displayError($this->module->l('File Not Found in source server.')), 'Image', true);
                }
                curl_multi_remove_handle($master, $curlArr[$i]);
                curl_close($curlArr[$i]);

                $i++;
            }
            //Close curl_multi
            curl_multi_close($master);
        } catch (Exception $ex) {
            $this->showMigrationMessageAndLog('loadImagesToLocal  ' . self::displayError($this->module->l($ex->getMessage())), 'Image', true);
        }
    }

    /**
     * Copy images from temporary directory to original PrestaShop directory for all types
     * @param mixed $id_entity Image name
     * @param mixed $id_image Image name only for Products
     * @param mixed $FilePath Temmporary file path
     * @param mixed $entity Type of image
     * @param mixed $regenerate
     * @return boolean
     */
    private static function copyImg($id_entity, $id_image, $FilePath, $entity = 'products', $regenerate = false)
    {
        $tmpfile = $FilePath;

        if (self::isEmpty($id_image)) {
            $id_image = null;
        }
        switch ($entity) {
            default:
            case 'carriers':
                $path = _PS_SHIP_IMG_DIR_ . (int)$id_entity;
                break;
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;
                break;
            case 'employees':
                $path = _PS_EMPLOYEE_IMG_DIR_ . (int)$id_entity;
                break;
            case 'attributes':
                $path = _PS_COL_IMG_DIR_ . (int)$id_entity;
                break;
        }


        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
            @unlink($tmpfile);
            return false;
        }
        copy($tmpfile, $path . '.jpg');
        return true;
    }

    private function getLocalID($map_type, $sourceID, $table_type = 'map')
    {
        if ($table_type === "map") {
            $result = (isset($this->mapping['mapping'][$map_type][$sourceID]) && !self::isEmpty($this->mapping['mapping'][$map_type][$sourceID])) ? $this->mapping['mapping'][$map_type][$sourceID] : 0;
        } else {
            $result = MigrationProData::getLocalID($map_type, $sourceID);
            if (self::isEmpty($result)) {
                $result = MigrationProMigratedData::getLocalID($map_type, $sourceID);
            }
        }

        return (int)$result;
    }

    private function getCarrierReference($id_carrier)
    {
        return Db::getInstance()->getValue('SELECT id_reference FROM ' . _DB_PREFIX_ . 'carrier WHERE id_carrier = ' . (int)$id_carrier . '');
    }

    private function getLanguageID($source_lang_id)
    {
        return $this->getLocalID('languages', $source_lang_id);
    }

    private function getShopID($source_shop_id)
    {
        return $this->getLocalID('multi_shops', $source_shop_id);
    }

    private function getCurrencyID($source_currency_id)
    {
        return $this->getLocalID('currencies', $source_currency_id);
    }

    private function getOrderStateID($source_order_state_id)
    {
        return $this->getLocalID('order_states', $source_order_state_id);
    }

    private function getCustomerGroupID($source_customer_group_id)
    {
        return $this->getLocalID('customer_groups', $source_customer_group_id);
    }

    private static function defaultValue($input, $default)
    {
        if (isset($input) && !self::isEmpty($input)) {
            return $input;
        } else {
            return $default;
        }
    }

    private function getChangedIdShop($dataFromSourceCart, $idKeyName)
    {
        $result = array();

        foreach ($dataFromSourceCart as $data) {
            if (self::getShopID($data) != 0) {
                $result[] = self::getShopID($data);
            }
        }
        return $result;
    }

    public static function displayError($string = 'Fatal error', $htmlentities = false)
    {
        return $htmlentities ? Tools::htmlentitiesUTF8(Tools::stripslashes($string)) : $string;
    }

    public static function addLog($entity_type, $source_id, $local_id)
    {
        MigrationProData::import((string)$entity_type, (int)$source_id, (int)$local_id);
        MigrationProMigratedData::import((string)$entity_type, (int)$source_id, (int)$local_id);
    }

    public static function isEmpty($field)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return ($field === '' || $field === null || $field === array() || $field === 0 || $field === '0');
        } else {
            return empty($field);
        }
    }

    public function updateMultiStoreLang($entity, $properties)
    {
        $keys = self::quotaToProperty(array_keys($properties));
        $values = self::quotaToProperty(array_values($properties));
        $result = Db::getInstance()->execute("REPLACE INTO " . _DB_PREFIX_ . $entity . "_lang (" . implode(', ', $keys) . ") VALUES  ('" . implode("','", $values) . "')");
        return $result;
    }

    public function quotaToProperty($properties)
    {
        $result = array();

        foreach ($properties as $value) {
            $result[] = pSQL($value, true);
        }

        return $result;
    }

    public static function importAccessories($accessory)
    {
        $result = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'accessory (id_product_1, id_product_2) VALUES (' . (int)$accessory["id_product_1"] . ', ' . (int)$accessory["id_product_2"] . ')');
        return $result;
    }

    private function showMigrationMessageAndLog($log, $entityType, $showOnlyWarning = false)
    {
        if ($this->ps_validation_errors) {
            if ($showOnlyWarning) {
                if (is_array($log)) {
                    foreach ($log as $logIndex => $logText) {
                        $this->logger->addWarningLog($logText, $entityType);
                    }
                } else {
                    $this->logger->addWarningLog($log, $entityType);
                }
            } else {
                if (is_array($log)) {
                    foreach ($log as $logIndex => $logText) {
                        $this->logger->addErrorLog($logText, $entityType);
                        $this->error_msg[] = $logText;
                    }
                } else {
                    $this->logger->addErrorLog($log, $entityType);
                    $this->error_msg[] = $log;
                }
            }
        } else {
            if (is_array($log)) {
                foreach ($log as $logIndex => $logText) {
                    $this->logger->addWarningLog($logText, $entityType);
                }
            } else {
                $this->logger->addWarningLog($log, $entityType);
            }
        }
    }
}
