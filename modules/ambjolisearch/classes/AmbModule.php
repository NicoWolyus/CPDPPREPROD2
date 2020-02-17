<?php
/**
 *   AmbModule
 *
 *   @author    Ambris Informatique
 *   @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *   @license   Commercial license
 *   @module    All ambris modules
 *   @file      AmbModule.php
 *   @subject   Parent for all ambris modules, with standard cross-methods
 *   Support by mail: support@ambris.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!defined('AMB_DEBUG')) {
    define('AMB_DEBUG', 'AMB_DEBUG');
}
if (!defined('AMB_CONSOLE_LOGGING')) {
    define('AMB_CONSOLE_LOGGING', 'AMB_CONSOLE_LOGGING');
}

class AmbModule extends Module
{

    protected $log_file;
    public $debug;
    public static $jquery_loaded = false;

    public function __construct()
    {
        $this->debug = Configuration::get(AMB_DEBUG);
        $this->console_logging = Configuration::get(AMB_CONSOLE_LOGGING);
        $this->compat = version_compare(_PS_VERSION_, 1.6, '>=') ? false : true;
        $this->bootstrap = !$this->compat;
        parent::__construct();
    }

    public function install()
    {

        if (!parent::install()
            || !$this->registerHook('displayBackofficeHeader')
            || !$this->registerHook('displayBackofficeFooter')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function installModuleTab($tabClass, $tabName, $idTabParent)
    {

        //Check if tab is already installed
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            return true;
        }

        @copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 't/' . $tabClass . '.gif');
        $tab = new Tab();
        $tab->name = $tabName;
        $tab->class_name = $tabClass;
        $tab->module = $this->name;
        $tab->id_parent = $idTabParent;

        if (!$tab->save()) {
            return false;
        }

        return true;
    }

    public function uninstallModuleTab($tabClass)
    {
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();
            return true;
        }
        return false;
    }

    public function hookDisplayBackofficeHeader($params)
    {
        if ($this->compat && method_exists($this->context->controller, 'addJS')) {
            if (!self::$jquery_loaded) {
                $this->context->controller->addJS($this->_path . 'views/js/jquery/jquery-1.11.2.min.js');
                $this->context->controller->addJS($this->_path . 'views/js/jquery/jquery-fix-compatibility.js');
                self::$jquery_loaded = true;
            }
        }
    }

    public function hookDisplayBackofficeFooter($params)
    {

        $content = '<link rel="stylesheet"
        href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.9.1/styles/default.min.css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.9.1/highlight.min.js"></script>
        <script>hljs.initHighlightingOnLoad();</script>';

        $content .= '<script type="text/javascript" src="' . $this->_path . 'views/js/amb_module.js"></script>';
        $content .= '<script type="text/javascript" src="' . $this->_path . 'views/js/debug.js"></script>';

        return $content;
    }

    public function log($data, $o_file = '', $o_method = '', $o_line = '', $description = '')
    {
        if (!$this->debug) {
            return false;
        }


        $from = '';
        if ($o_file != '' || $o_method != '' || $o_line != '') {
            $from = 'Logged from : ' . $o_file . ' > ' . $o_method . ' : ' . $o_line;
        }

        if ($this->console_logging) {
            $this->logC($data, $from);
            return false;
        }

        $module_path = _PS_MODULE_DIR_ . $this->name;
        $dir = $module_path . '/logs/';
        if (!file_exists($dir)) {
            mkdir($module_path . '/logs', 0777);
        }

        $filename = $dir . 'logs';
        $file = fopen($filename, 'a');

        fwrite($file, '[' . date('Y-m-d H:i:s') . '] ' . $description . "\n");

        if ($from != '') {
            fwrite($file, $from . "\n");
        }

        fwrite($file, $data);
        fwrite($file, "\n");
        fclose($file);
    }

    public function logC($data, $from)
    {
        if ($from != '') {
            $from = $from . '<br />';
        }
        if (is_array($data)) {
            error_log($from . print_r($data, true));
        } else {
            error_log($from . $data);
        }
    }

    public function getLogs()
    {
        $module_path = _PS_MODULE_DIR_ . $this->name;
        $dir = $module_path . '/logs/';
        if (!file_exists($dir)) {
            mkdir($module_path . '/logs', 0777);
        }

        $filename = $dir . 'logs';
        $handle = fopen($filename, "a+");
        $count = 0;
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                echo str_replace("\n", "<br />", $line);
                $count++;
            }
            fclose($handle);
        } else {
            //File missing
        }

        if ($count == 0) {
            echo 'No logs at the moment.';
        }
    }

    public function deleteLogs()
    {
        $module_path = _PS_MODULE_DIR_ . $this->name;
        $dir = $module_path . '/logs/';
        if (!file_exists($dir)) {
            mkdir($module_path . '/logs', 0777);
        }

        $filename = $dir . 'logs';
        $handle = fopen($filename, "w");
        fclose($handle);
    }

    public function getDebug()
    {
        $this->context->smarty->assign('amb', $this);
        if (Tools::getValue('debug', 0) || Configuration::get(AMB_DEBUG)) {
            $this->context->smarty->assign(
                'amb_module_config_url',
                $this->context->link->getAdminLink('AdminModules')
                . '&configure=' . $this->name . '&module_name=' . $this->name
            );
            $this->context->smarty->assign('compat', $this->compat);
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/admin/debug.tpl');
        } else {
            return '';
        }
    }

    public function ajaxProcessDebugReset()
    {
        $this->debugReset();
        die(__METHOD__);
    }

    public function ajaxProcessOptimizeTables()
    {
        Db::getInstance()->execute('OPTIMIZE TABLE ' . _DB_PREFIX_ . 'search_index');
        Db::getInstance()->execute('OPTIMIZE TABLE ' . _DB_PREFIX_ . 'search_word');
        die(__METHOD__);
    }

    public function debugReset()
    {
        $this->log('Debug reset has been used');
    }
    public function getContent()
    {
        if (Tools::getValue('ajax', 0) == 1 && Tools::getValue('action', null) != null) {
            $this->dispatchAjaxCalls(Tools::getValue('action'));
            die('AjaxCall');
        }
    }

    public function dispatchAjaxCalls($func_name)
    {
        $method_name = 'ajaxProcess' . $func_name;
        if (method_exists($this, $method_name)) {
            return $this->$method_name();
        }
    }

    public function ajaxProcessSwitchDebug()
    {
        $active = Tools::getValue('value', 0);
        Configuration::updateValue(AMB_DEBUG, $active);
        die(__METHOD__);
    }

    public function ajaxProcessSwitch()
    {
        $active = Tools::getValue('value', 0);
        $configuration = Tools::getValue('configuration');
        Configuration::updateValue(Tools::strtoupper($configuration), $active);
        die(__METHOD__);
    }

    public function ajaxProcessGetLog()
    {
        die($this->getLogs());
    }

    public function ajaxProcessDeleteLog()
    {
        $this->deleteLogs();
        die(__METHOD__);
    }

    public static function generateAjaxSwitch($conf_name)
    {
        $conf_value = Configuration::get($conf_name);
        return '<span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" class="amb_switch" data-configuration="' . $conf_name . '" name="switch_'
            . $conf_name . '" id="switch_' . $conf_name . '_on" value="1" '
            . ($conf_value ? 'checked="checked"' : '') . '><label for="switch_'
            . $conf_name . '_on" class="radioCheck">Yes</label><input type="radio"
                    class="amb_switch" data-configuration="' . $conf_name . '" name="switch_' . $conf_name
            . '" id="switch_' . $conf_name . '_off" value="0" ' . (!$conf_value ? 'checked="checked"' : '')
            . '><label for="switch_' . $conf_name . '_off" class="radioCheck">No</label>
                    <a class="slide-button btn"></a>
                </span>';
    }

    public function isPhoneDevice()
    {

        if (!class_exists('Mobile_Detect')) {
            require_once(_PS_ROOT_DIR_ . '/modules/ambjolisearch/classes/Mobile_Detect.php');
        }

        $this->mobile_detect = new Mobile_Detect();
        $this->phone_device = false;
        if ($this->mobile_detect->isMobile() && !$this->mobile_detect->isTablet()) {
            $this->phone_device = true;
        }

        // * Debug *
        //if ($this->phone_device == true) echo "This is a Phone or a SmartPhone";
        //if ($this->mobile_detect->isTablet()) echo "This is a Tablet";

        return $this->phone_device;
    }

    public function installMeta($controller_name)
    {
        $result = true;
        if (version_compare(_PS_VERSION_, '1.6.0.2', '>=')) {
            $controllers = array($controller_name);
            $languages = Language::getLanguages(false, false, true);

            foreach ($controllers as $controller) {
                $page = 'module-' . $this->name . '-' . $controller;
                $tmp = Db::getInstance()->getValue('SELECT * FROM ' . _DB_PREFIX_ . 'meta WHERE page="' . pSQL($page) . '"');
                if ((int) $tmp > 0) {
                    continue;
                }

                $meta = new Meta();
                $meta->page = $page;
                $meta->url_rewrite = array();
                foreach ($languages as $language) {
                    $meta->title[$language] = 'jolisearch';
                    $meta->url_rewrite[$language] = 'jolisearch';
                }

                $meta->configurable = 1;

                $meta->save();
            }
        }
        return $result;
    }

    public function uninstallMeta($controller_name)
    {
        $result = true;
        if (version_compare(_PS_VERSION_, '1.6.0.2', '>=')) {
            $controllers = array($controller_name);

            foreach ($controllers as $controller) {
                $page = 'module-' . $this->name . '-' . $controller;
                $meta = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'meta WHERE page="' . pSQL($page) . '"');
                if (!is_null($meta) && count($meta) > 0) {
                    $meta = new Meta($meta['id_meta']);
                    $meta->delete();
                }
            }
        }
        return $result;
    }
}
