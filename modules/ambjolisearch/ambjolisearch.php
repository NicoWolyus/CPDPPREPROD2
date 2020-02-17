<?php
/**
 *   AmbJoliSearch Module : Search for prestashop
 *
 *   @author    Ambris Informatique
 *   @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *   @license   Commercial license
 *   @module     Advanced search (AmbJoliSearch)
 *   @file       ambjolisearch.php
 *   @subject    script principal pour gestion du module (install/config/hook)
 *   Support by mail: support@ambris.com
 */

if (!class_exists('AmbModule')) {
    require_once 'classes/AmbModule.php';
}

if (!defined('PS_SEARCH_START')) {
    define('PS_SEARCH_START', 'PS_SEARCH_START');
}

define('AJS_MAX_ITEMS_KEY', 'AAJJS_MAX_ITEMS');
define('AJS_MAX_MANUFACTURERS_KEY', 'AAJJS_MAX_MANUFACTURERS');
define('AJS_MAX_CATEGORIES_KEY', 'AAJJS_MAX_CATEGORIES');
define('AJS_PRODUCTS_PRIORITY_KEY', 'AAJJS_PRODUCTS_PRIORITY');
define('AJS_MANUFACTURERS_PRIORITY_KEY', 'AAJJS_MANUFACTURERS_PRIORITY');
define('AJS_CATEGORIES_PRIORITY_KEY', 'AAJJS_CATEGORIES_PRIORITY');
define('AJS_INSTALLATION_COMPLETE', 'AAJJSS_INSTALLATION_COMPLETE');
define('AJS_APPROXIMATIVE_SEARCH', 'AAJJSS_APPROXIMATIVE_SEARCH');
define('AJS_MORE_RESULTS_STRING', 'AAJJSS_MORE_RESULTS_STRING');
define('AJS_MORE_RESULTS_CONFIG', 'AAJJSS_MORE_RESULTS_CONFIG');
define('AJS_SHOW_PRICES', 'AAJJSS_SHOW_PRICES');
define('AJS_SHOW_FEATURES', 'AAJJSS_SHOW_FEATURES');
define('AJS_SHOW_CATEGORIES', 'AAJJSS_SHOW_CATEGORIES');
define('AJS_SHOW_CAT_DESC', 'AAJJSS_SHOW_CAT_DESC');
define('AJS_ENABLE_AC_PHONE', 'AAJJSS_ENABLE_AC_PHONE');
define('AJS_DISABLE_AC', 'AAJJSS_DISABLE_AC');
define('AJS_MULTILANG_SEARCH', 'AAJJSS_MULTILANG_SEARCH');

require_once _PS_ROOT_DIR_ . '/modules/ambjolisearch/classes/definitions.php';

require_once _PS_ROOT_DIR_ . '/modules/ambjolisearch/src/Amb_ProductSearchProvider.php';


class AmbJoliSearch extends AmbModule
{
    const INSTALL_SQL_FUNCTIONS_FILE = 'functions.sql';

    const INSTALL_SQL_TABLES_FILE = 'tables.sql';

    public function __construct()
    {
        $this->name = 'ambjolisearch';

        $this->tab = 'search_filter';
        $this->version = '3.0.11';
        $this->author = 'Ambris Informatique';
        $this->module_key = '2642eb17142e5a9c9bad308c9c642f2c';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.7');

        parent::__construct();

        $this->displayName = $this->l('JoliSearch : Improved Search');
        $this->description = $this->l('Improves instant search displays and handles approximative searches');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->use_jolisearch_tpl = false;
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        //$this->installSQLTables();
        if ($this->installSQLFunctions() && $this->installSQLTables()) {
            Configuration::updateValue(AJS_INSTALLATION_COMPLETE, 1);
            Configuration::updateValue(AJS_APPROXIMATIVE_SEARCH, 1);
            $install_ok = true;
        } else {
            Configuration::updateValue(AJS_INSTALLATION_COMPLETE, 0);
            Configuration::updateValue(AJS_APPROXIMATIVE_SEARCH, 0);
            $install_ok = false;
        }

        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('productSearchProvider') &&
        $this->installMeta('jolisearch') &&
        Configuration::updateValue(AJS_MAX_ITEMS_KEY, 10) &&
        Configuration::updateValue(AJS_MAX_MANUFACTURERS_KEY, 3) &&
        Configuration::updateValue(AJS_MAX_CATEGORIES_KEY, 0) &&
        Configuration::updateValue(AJS_PRODUCTS_PRIORITY_KEY, 3) &&
        Configuration::updateValue(AJS_MANUFACTURERS_PRIORITY_KEY, 2) &&
        Configuration::updateValue(AJS_CATEGORIES_PRIORITY_KEY, 1) &&
        Configuration::updateValue(AJS_MORE_RESULTS_CONFIG, 1) &&
        Configuration::updateValue(AJS_SHOW_PRICES, 1) &&
        Configuration::updateValue(AJS_SHOW_CATEGORIES, 2) &&
        Configuration::updateValue(AJS_SHOW_FEATURES, 1) &&
        Configuration::updateValue(AJS_SHOW_CAT_DESC, 0) &&
            $install_ok;
    }

    public function installSQLTables()
    {
        if (!file_exists(dirname(__FILE__) . '/sql/' . self::INSTALL_SQL_TABLES_FILE)) {
            return (false);
        } elseif (!$sql = Tools::file_get_contents(dirname(__FILE__) . '/sql/' . self::INSTALL_SQL_TABLES_FILE)) {
            return (false);
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        if (version_compare(_PS_VERSION_, 1.4, '>=')) {
            $sql = str_replace('MYSQL_ENGINE', _MYSQL_ENGINE_, $sql);
        } else {
            $sql = str_replace('MYSQL_ENGINE', 'MyISAM', $sql);
        }

        $sql = preg_split('/;\s*[\r\n]+/', $sql);

        foreach ($sql as $query) {
            if (preg_match('/(^#.*$)|(^[\s\t]*$)/', $query)) {
                continue;
            }

            if (!Db::getInstance()->Execute(trim($query))) {
                $html = '<ul><li>Erreur SQL : '
                . Db::getInstance()->getNumberError() . ' : ' . Db::getInstance()->getMsgError() . '<br /><br />
                            <pre>' . $query . '</pre></li></ul>';
                return $this->displayError($html);
            }
        }
        return true;
    }
    public function installSQLFunctions()
    {
        if (!file_exists(dirname(__FILE__) . '/sql/' . self::INSTALL_SQL_FUNCTIONS_FILE)) {
            return (false);
        } elseif (!$sql = Tools::file_get_contents(dirname(__FILE__) . '/sql/' . self::INSTALL_SQL_FUNCTIONS_FILE)) {
            return (false);
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        if (version_compare(_PS_VERSION_, 1.4, '>=')) {
            $sql = str_replace('MYSQL_ENGINE', _MYSQL_ENGINE_, $sql);
        } else {
            $sql = str_replace('MYSQL_ENGINE', 'MyISAM', $sql);
        }

        //$sql = preg_split('/END;\s*[\r\n]+/', $sql);

        $this->uninstallSQL();

        if (!Db::getInstance()->Execute(trim($sql))) {
            return (false);
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->uninstallSQL() ||
            !$this->uninstallMeta('jolisearch') ||
            !Configuration::deleteByName(AJS_MAX_ITEMS_KEY) ||
            !Configuration::deleteByName(AJS_MAX_MANUFACTURERS_KEY) ||
            !Configuration::deleteByName(AJS_MAX_CATEGORIES_KEY) ||
            !Configuration::deleteByName(AJS_PRODUCTS_PRIORITY_KEY) ||
            !Configuration::deleteByName(AJS_MANUFACTURERS_PRIORITY_KEY) ||
            !Configuration::deleteByName(AJS_CATEGORIES_PRIORITY_KEY) ||
            !Configuration::deleteByName(AJS_SHOW_CAT_DESC)) {
            return false;
        }

        return true;
    }

    public function debugReset()
    {
        $this->log($this->installSQLFunctions(), __FILE__, __METHOD__, __LINE__, 'installSQLFunctions (true or false)');
        $this->log($this->installSQLTables(), __FILE__, __METHOD__, __LINE__, 'installSQLTables (true or false)');

        parent::debugReset();
    }

    public function uninstallSQL()
    {
        $query = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'ambjolisearch_synonyms;';
        $query .= 'DROP FUNCTION IF EXISTS `amb_levenshtein`;';
        if (!Db::getInstance()->Execute(trim($query))) {
            return (false);
        }

        return true;
    }

    protected function checkInstallation()
    {
        $return = '';
        $error_count = 0;
        if (!$this->checkRoutines()) {
            $error_count++;
            $return .= $this->displayError($this->l('Levenshtein routine has not been installed.'));
        }

        if (!$this->checkTables()) {
            $error_count++;
            $return .= $this->displayError(
                $this->l('Table') . ' ' . _DB_PREFIX_ . 'ambjolisearch_synonyms '
                . $this->l('does not exist.')
            );
        }

        if ($error_count == 0) {
            Configuration::updateValue(AJS_INSTALLATION_COMPLETE, 1);
        } else {
            Configuration::updateValue(AJS_INSTALLATION_COMPLETE, 0);
            $return .= $this->displayError(
                $this->l(
                    'Approximative search doesn\'t work at the moment.
                    This may be due to access restrictions in your database configuration.
                    Make sure the database user prestashop uses has the privileges « CREATE ROUTINE »
                    and « EXECUTE ». These may be activated under the tab privileges in phpmyadmin.
                    Afterwards, you should reset the module for everything to work properly.'
                )
            );
        }

        return $return;
    }

    protected function checkRoutines()
    {
        $query = 'SHOW FUNCTION STATUS WHERE name="amb_levenshtein" AND db="' . _DB_NAME_ . '"';
        $result = Db::getInstance()->ExecuteS($query);
        if (count($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    protected function checkTables()
    {
        $query = '
        SELECT *
        FROM information_schema.tables
        WHERE table_schema = "' . _DB_NAME_ . '"
            AND table_name = "' . _DB_PREFIX_ . 'ambjolisearch_synonyms"
        LIMIT 1;';
        $result = Db::getInstance()->ExecuteS($query);
        if (count($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function hookDisplayHeader()
    {
        if (Module::isEnabled('ps_searchbar')) {
            $this->context->controller->unregisterJavascript('modules-searchbar');
        } else {
            $this->context->controller->addJqueryUI('ui.autocomplete');
        }

        //$this->context->controller->addCSS($this->_path . 'views/css/ambjolisearch-1.7.css', 'all');
        $this->context->controller->registerJavascript('modules-ambjolisearch', 'modules/'.$this->name.'/views/js/ambjolisearch.js', array('position' => 'bottom', 'priority' => 1000));

        //$this->context->controller->addJS($this->_path . 'views/js/ambjolisearch.js');
        $this->context->controller->addCSS($this->_path . 'views/css/jolisearch-autocomplete.css', 'all');

        $this->addAdvancedSearch4Support();
        $this->assignJolisearchVars();
    }

    public function addAdvancedSearch4Support()
    {
        if (class_exists('PM_AdvancedSearch4')) {
            if (Tools::getValue('search_query') != null) {
                $search_query = Tools::getValue('search_query');
            } elseif (Tools::getValue('productFilterListSource') != null) {
                /* call from ajax page loading when criteria has been selected in pm_advancedsearch4 */
                $filter_source = Tools::getValue('productFilterListSource');
                if ($filter_source == 'jolisearch') {
                    $search_query = As4SearchEngine::$productFilterListData;
                } else {
                    $arr = explode('|', $filter_source);
                    if ($arr[0] == 'jolisearch') {
                        $search_query = $arr[1];
                    }
                }
            }
            if (isset($search_query) && !empty($search_query)) {
                $this->setAdvancedSearch4Results($search_query);
            }
        }
    }

    public function setAdvancedSearch4Results($search_query)
    {
        if (!class_exists('AmbSearch')) {
            require_once _PS_ROOT_DIR_ . '/modules/ambjolisearch/classes/AmbSearch.php';
        }

        $searcher = new AmbSearch(false, $this->context, $this);
        $searcher->search($this->context->language->id, $search_query, 1, -1, 'position', 'desc');
        //Charge la liste des ids produit correspondant aux critères
        $search_results = $searcher->getResultIds();

        // Advanced Search 4 >= 4.11
        As4SearchEngine::$productFilterListQuery = implode(',', $search_results);
        As4SearchEngine::$productFilterListSource = 'jolisearch';
    }

    public function void($param)
    {
        return $param;
    }

    protected function getConfigFormValues()
    {
        return array(
            'AJS_MAX_ITEMS_KEY' => Configuration::get(AJS_MAX_ITEMS_KEY),
            'AJS_MAX_MANUFACTURERS_KEY' => Configuration::get(AJS_MAX_MANUFACTURERS_KEY),
            'AJS_MAX_CATEGORIES_KEY' => Configuration::get(AJS_MAX_CATEGORIES_KEY),
            'AJS_PRODUCTS_PRIORITY_KEY' => Configuration::get(AJS_PRODUCTS_PRIORITY_KEY),
            'AJS_MANUFACTURERS_PRIORITY_KEY' => Configuration::get(AJS_MANUFACTURERS_PRIORITY_KEY),
            'AJS_CATEGORIES_PRIORITY_KEY' => Configuration::get(AJS_CATEGORIES_PRIORITY_KEY),
            'AJS_APPROXIMATIVE_SEARCH' => Configuration::get(AJS_APPROXIMATIVE_SEARCH),
            'AJS_MORE_RESULTS_CONFIG' => Configuration::get(AJS_MORE_RESULTS_CONFIG),
            'AJS_SHOW_PRICES' => Configuration::get(AJS_SHOW_PRICES),
            'AJS_SHOW_FEATURES' => Configuration::get(AJS_SHOW_FEATURES),
            'AJS_SHOW_CATEGORIES' => Configuration::get(AJS_SHOW_CATEGORIES),
            'AJS_SHOW_CAT_DESC' => Configuration::get(AJS_SHOW_CAT_DESC),
            'AJS_ENABLE_AC_PHONE' => Configuration::get(AJS_ENABLE_AC_PHONE),
            'AJS_DISABLE_AC' => Configuration::get(AJS_DISABLE_AC),
            'AJS_MULTILANG_SEARCH' => Configuration::get(AJS_MULTILANG_SEARCH),
            'PS_SEARCH_START' => Configuration::get(PS_SEARCH_START),
        );
    }

    protected function getConfigFormTypes()
    {
        return array(
            'AJS_MAX_ITEMS_KEY' => 'Int',
            'AJS_MAX_MANUFACTURERS_KEY' => 'Int',
            'AJS_MAX_CATEGORIES_KEY' => 'Int',
            'AJS_PRODUCTS_PRIORITY_KEY' => 'Int',
            'AJS_MANUFACTURERS_PRIORITY_KEY' => 'Int',
            'AJS_CATEGORIES_PRIORITY_KEY' => 'Int',
            'AJS_APPROXIMATIVE_SEARCH' => 'Bool',
            'AJS_MORE_RESULTS_CONFIG' => 'Bool',
            'AJS_SHOW_PRICES' => 'Bool',
            'AJS_SHOW_FEATURES' => 'Bool',
            'AJS_SHOW_CATEGORIES' => 'Int',
            'AJS_SHOW_CAT_DESC' => 'Bool',
            'AJS_ENABLE_AC_PHONE' => 'Bool',
            'AJS_DISABLE_AC' => 'Bool',
            'AJS_MULTILANG_SEARCH' => 'Bool',
            'PS_SEARCH_START' => 'Bool',
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        $form_types = $this->getConfigFormTypes();
        $errors = array();

        foreach (array_keys($form_values) as $key) {
            if (Tools::getIsset($key)) {
                $value = Tools::getValue($key);

                if ($form_types[$key] == 'Int') {
                    if (!Validate::isInt($value)) {
                        $errors[] = $this->l('Invalid values');
                    }
                } elseif ($form_types[$key] == 'Bool') {
                    if (!Validate::isBool((bool)$value)) {
                        $errors[] = $this->l('Invalid values');
                    }
                }
                if (count($errors)) {
                    $this->_html .= $this->displayError(implode('<br />', $errors));
                    $errors = array();
                    continue;
                }


                Configuration::updateValue(constant($key), Tools::getValue($key));
            }
        }
    }

    public function getContent()
    {
        $this->_html = '';
        $output = array('pre' => '', 'post' => '');
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitAmbJoliSearchModule')) == true) {
            $this->postProcess();
        }

        if (Tools::isSubmit('submitResetSynonyms')) {
            Db::getInstance()->Execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'ambjolisearch_synonyms');
            $output['pre'] .= $this->displayConfirmation($this->l('Synonyms have been reset'));
        }

        if (!(bool) Configuration::get(AJS_INSTALLATION_COMPLETE)) {
            $output['pre'] .= $this->checkInstallation();
        }

        $this->context->smarty->assign('documentation_link', $this->_path.'docs/'.$this->l('readme_en.pdf'));

        $output['pre'] .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/documentation.tpl');

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('nbSynonyms', $this->getNbSynonyms());
        $this->context->smarty->assign('request_uri', Tools::safeOutput($_SERVER['REQUEST_URI']));
        $this->context->smarty->assign('path', $this->_path);
        $this->context->smarty->assign('compat', $this->compat);

        $output['post'] .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $this->_html.$output['pre'] . $this->renderForm() . $output['post'];
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAmbJoliSearchModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
    }

    public function getConfigForm()
    {
        $is_correctly_installed = (bool) Configuration::get(AJS_INSTALLATION_COMPLETE);

        $arr =
        array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Display settings'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_MAX_ITEMS_KEY',
                            'label' => $this->l('Maximum of items to display'),
                        ),
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_MAX_MANUFACTURERS_KEY',
                            'label' => $this->l('Maximum of manufacturers to display'),
                        ),
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_MAX_CATEGORIES_KEY',
                            'label' => $this->l('Maximum of categories to display'),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Activate "Show more results" option'),
                            'name' => 'AJS_MORE_RESULTS_CONFIG',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Show prices during instant searches'),
                            'name' => 'AJS_SHOW_PRICES',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Show product features during instant searches'),
                            'name' => 'AJS_SHOW_FEATURES',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Disable instant searches on all devices (also on phones)'),
                            'name' => 'AJS_DISABLE_AC',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Activate instant searches on mobile phones'),
                            'name' => 'AJS_ENABLE_AC_PHONE',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_SHOW_CATEGORIES',
                            'label' => $this->l('Show categories on top of search page'),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Show category description'),
                            'name' => 'AJS_SHOW_CAT_DESC',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        )
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),

            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Priority settings'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_PRODUCTS_PRIORITY_KEY',
                            'label' => $this->l('Products priority'),
                        ),
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_MANUFACTURERS_PRIORITY_KEY',
                            'label' => $this->l('Manufacturers priority'),
                        ),
                        array(
                            'col' => 3,
                            'type' => 'text',
                            'name' => 'AJS_CATEGORIES_PRIORITY_KEY',
                            'label' => $this->l('Categories priority'),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),

            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Approximate Search Settings'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'condition' => $is_correctly_installed,
                            'type' => 'switch',
                            'label' => $this->l('Use approximate Search'),
                            'name' => 'AJS_APPROXIMATIVE_SEARCH',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Search within word'),
                            'name' => 'PS_SEARCH_START',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Search in all languages'),
                            'name' => 'AJS_MULTILANG_SEARCH',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ),
                            ),
                        )
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),

            ),
        );


        return $arr;
    }

    private function getNbSynonyms()
    {
        $query = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'ambjolisearch_synonyms';
        return Db::getInstance()->getValue($query, false);
    }

    public function hookProductSearchProvider($params)
    {
        if (Tools::getValue('s', false) !== false) {
            return new AmbProductSearchProvider($this);
        }
    }

    protected function assignJolisearchVars()
    {
        $joli_link = new JoliLink($this->context->link);
        $action = $joli_link->getModuleLink('ambjolisearch', 'jolisearch', array(), Tools::usingSecureMode());
        $link = $joli_link->getModuleLink('ambjolisearch', 'jolisearch', array(), Tools::usingSecureMode());
        $controller_name = 'jolisearch';

        $ga_acc = !Configuration::get('GANALYTICS_ID') ? 0 : Configuration::get('GANALYTICS_ID');

        if (Configuration::get(AJS_DISABLE_AC)) {
            $use_autocomplete = 0;
        } elseif (Configuration::get(AJS_ENABLE_AC_PHONE)) {
            $use_autocomplete = 2;
        } else {
            $use_autocomplete = 1;
        }

        $templateVars = array(
            'amb_joli_search_action' => $action,
            'amb_joli_search_link' => $link,
            'amb_joli_search_controller' => $controller_name,
            'blocksearch_type' => 'top',
            'show_cat_desc' => (int) Configuration::get(AJS_SHOW_CAT_DESC),
            'ga_acc' => $ga_acc,
            'id_lang' => $this->context->language->id,
            'url_rewriting' => $joli_link->isUrlRewriting(),
            'use_autocomplete' => $use_autocomplete,
            'minwordlen' => (int) Configuration::get('PS_SEARCH_MINWORDLEN'),
            'l_products' => $this->l('Products'),
            'l_manufacturers' => $this->l('Manufacturers'),
            'l_categories' => $this->l('Categories'),
            'l_no_results_found' => $this->l('No results found'),
            'l_more_results' => $this->l('More results »'),
            'ENT_QUOTES' => ENT_QUOTES,
            'search_ssl' => Tools::usingSecureMode(),
            'self' => dirname(__FILE__),
        );

        $this->context->smarty->assign($templateVars);
        Media::addJsDef(array('jolisearch' => $templateVars));
    }
}
