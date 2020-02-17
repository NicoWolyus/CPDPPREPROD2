<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DisplaySlider extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'displayslider';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'WebshopWorks';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Display Slider');
        $this->description = $this->l('This module can display a slider');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        Configuration::updateValue('DISPLAYSLIDER_ID', 0);
        return parent::install() && $this->registerHook('displaySlider');
    }

    public function uninstall()
    {
        Configuration::deleteByName('DISPLAYSLIDER_ID');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitDisplaysliderModule')) {
            $this->postProcess();
        }
        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDisplaysliderModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $options = array(
            array('name' => '- None -', 'id' => '0')
        );
        $table = _DB_PREFIX_.'layerslider';
        $rows = Db::getInstance()->executeS("SELECT CONCAT('#', id, ' ', name) AS name, id FROM $table WHERE flag_deleted = 0");
        if ($rows) {
            $options += $rows;
        }
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select a slider'),
                        'name' => 'DISPLAYSLIDER_ID',
                        'options' => array(
                            'name' => 'name',
                            'id' => 'id',
                            'query' => $options,
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array('DISPLAYSLIDER_ID' => Configuration::get('DISPLAYSLIDER_ID', 0));
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookDisplaySlider()
    {
        $html = '';
        $config = $this->getConfigFormValues();
        $layerslider = Module::getInstanceByName('layerslider');

        if ($layerslider && !empty($config['DISPLAYSLIDER_ID'])) {
            require_once _PS_MODULE_DIR_.'layerslider/helper.php';
            require_once _PS_MODULE_DIR_.'layerslider/base/layerslider.php';
            $html = $layerslider->generateSlider($config['DISPLAYSLIDER_ID']);
        }
        return $html;
    }
}
