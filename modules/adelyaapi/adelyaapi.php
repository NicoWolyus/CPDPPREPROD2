<?php

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

if (!defined('_PS_VERSION_')) {
	exit;
}

include_once _PS_MODULE_DIR_ . 'adelyaapi/adelyaUtil.php';

class AdelyaAPI extends Module {

	public function __construct() {
		$this->name = 'adelyaapi';
		$this->tab = 'others';
		$this->version = '1.3.0';
		$this->author = 'Adelya SAS';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Loyalty Operator integration');
		$this->description = $this->l('This module add a link between Adelya Loyalty Operator and your prestashop');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall ? The link between Prestashop and Adelya will be lost');

		if (!Configuration::get('server_url')) {
			$this->warning = $this->l('No API Server url provided');
		}
		if (!Configuration::get('api_key')) {
			$this->warning = $this->l('No API Key provided');
		}
		if (!Configuration::get('user_login')) {
			$this->warning = $this->l('No API User Login provided');
		}
		if (!Configuration::get('user_password')) {
			$this->warning = $this->l('No API User Password provided');
		}

		$this->connectionArray = array(
			array(
				'id' => 'qa',
				'value' => 0,
				'label' => $this->l('QA (Test server)')
			),
			array(
				'id' => 'asp',
				'value' => 1,
				'label' => $this->l('ASP (Real server)')
			)
		);

		//if we are in debug mode add two more configuration for test
		if(_PS_MODE_DEV_){
			array_push($this->connectionArray, array(
				'id' => 'Demo',
				'value' => 90,
				'label' => $this->l('Demo')
			), array(
				'id' => 'local',
				'value' => 99,
				'label' => $this->l('local')
			));
		}
	}

	public function install() {
		if (!parent::install()
			|| !$this->_installSql()

			|| !$this->registerHook('actionCustomerAccountAdd')              //Executed when a customer create an account
			|| !$this->registerHook('actionCustomerAccountUpdate')        	//Executed when a customer update an account
			|| !$this->registerHook('actionObjectCustomerUpdateAfter')       //Executed after an object update
			|| !$this->registerHook('actionObjectCustomerDeleteAfter')       //Executed after a customer object deletion
			|| !$this->registerHook('actionCustomerFormBuilderModifier')     //modify customer formbuilder to add field
			|| !$this->registerHook('actionAfterUpdateCustomerFormHandler')  //customer form custom field management on update
			|| !$this->registerHook('actionAfterCreateCustomerFormHandler') 	//customer form custom field management on create
			|| !$this->registerHook('customerAccount')                    	//Used to display fidelity counters
			|| !$this->registerHook('actionAuthentication')               	//Executed when the user is login in
			|| !$this->registerHook('displayOrderConfirmation')
			|| !$this->registerHook('actionOrderStatusPostUpdate')
			|| !$this->registerHook('displayCustomerAccountForm')


			|| !$this->registerHook('additionalCustomerFormFields')                //add custom field to front form

			|| !Configuration::updateValue('server_url', 0)
			|| !Configuration::updateValue('adelyaapi_status', 1)
			|| !Configuration::updateValue('customer_sync', 1)
			|| !Configuration::updateValue('voucher_sync', 1)
			|| !Configuration::updateValue('fidelity_menu_text', $this->l('Fidelity'))
			|| !Configuration::updateValue('fidelity_counter', 0)
			|| !Configuration::updateValue('connection_timeout', '7')
			|| !Configuration::updateValue('request_timeout', '7')
			|| !Configuration::updateValue('log_level', 'DEBUG')
		)
			return false;

		return true;
	}

	/**
	 * Modifications sql du module
	 * @return boolean
	 */
	protected function _installSql() {
		$sqlInstall = "ALTER TABLE " . _DB_PREFIX_ . "customer 
							ADD fid_program_membership BOOLEAN DEFAULT false,
							ADD fid_program_membership_date DATE";
		return Db::getInstance()->execute($sqlInstall);
	}

	public function uninstall() {
		if (!parent::uninstall()
			|| !$this->_unInstallSql()
			|| !Configuration::deleteByName('api_key')
			|| !Configuration::deleteByName('user_login')
			|| !Configuration::deleteByName('user_password')
			|| !Configuration::deleteByName('server_url')
			|| !Configuration::deleteByName('adelyaapi_status')
			|| !Configuration::deleteByName('customer_sync')
			|| !Configuration::deleteByName('voucher_sync')
			|| !Configuration::deleteByName('specific_vouchers')
			|| !Configuration::deleteByName('fidelity_menu_text')
			|| !Configuration::deleteByName('fidelity_rules_text')
			|| !Configuration::deleteByName('fidelity_counter')
			|| !Configuration::deleteByName('connection_timeout')
			|| !Configuration::deleteByName('request_timeout')
			|| !Configuration::deleteByName('log_level')
		) {
			return false;
		}
		return true;
	}

	/**
	 * Suppression des modification sql du module
	 * @return boolean
	 */
	protected function _unInstallSql() {
		try{
			$sqlUnInstall = "ALTER TABLE " . _DB_PREFIX_ . "customer 
									DROP fid_program_membership,
									DROP fid_program_membership_date";
			return Db::getInstance()->execute($sqlUnInstall);
		}catch(Exception $ex){
			return true;
		}
	}

	public function getContent() {
		$output = '';

		if (Tools::isSubmit('submitConnectionForm')) {
			Configuration::updateValue('api_key', Tools::getValue('api_key'));
			Configuration::updateValue('user_login', Tools::getValue('user_login'));
			Configuration::updateValue('user_password', Tools::getValue('user_password'));
			Configuration::updateValue('server_url', Tools::getValue('server_url'));
			Configuration::updateValue('adelyaapi_status', (bool)Tools::getValue('adelyaapi_status'));
			$output .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Success'));
		} else if (Tools::isSubmit('submitConfigurationForm')) {
			Configuration::updateValue('customer_sync', Tools::getValue('customer_sync'));
			Configuration::updateValue('voucher_sync', Tools::getValue('voucher_sync'));
			Configuration::updateValue('specific_vouchers', Tools::getValue('specific_vouchers'));
			Configuration::updateValue('fidelity_menu_text', Tools::getValue('fidelity_menu_text'));
			Configuration::updateValue('fidelity_rules_text', Tools::getValue('fidelity_rules_text'), true);
			Configuration::updateValue('fidelity_counter', Tools::getValue('fidelity_counter'));
			$output .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Success'));
		} else if (Tools::isSubmit('submitDebugForm')) {
			Configuration::updateValue('connection_timeout', Tools::getValue('connection_timeout'));
			Configuration::updateValue('request_timeout', Tools::getValue('request_timeout'));
			Configuration::updateValue('log_level', Tools::getValue('log_level'));
			$output .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Success'));
		}
		$output .= $this->renderPluginStatus();
		$output .= $this->renderConnectionForm();
		$output .= $this->renderConfigurationForm();
		// Debug form only for DEBUG mode
		if(_PS_MODE_DEV_){
			$output .= $this->renderDebugForm();
		}
		
		return $output;
	}

	public function renderPluginStatus() {
		$output = '';
		//$output .= '<div class="panel"><img src="../modules/adelyaapi/views/img/banner.jpg" style="max-width:785px;" /></div>';
		$output .= '<div class="panel"><div class="panel-heading"><i class="icon-link"></i>&nbsp;' . $this->l('Module Status') . '</div><div class="form-wrapper">';
		//No API KEY
		if (!Configuration::get('api_key') || empty(Configuration::get('api_key'))) {
			$output .= '<div class="form-group"><label class="control-label"><span style="color:darkred">' . $this->l('API Key is missing') . '</span></label></div>';
		}
		//No user_login
		if (!Configuration::get('user_login') || empty(Configuration::get('user_login'))) {
			$output .= '<div class="form-group"><label class="control-label"><span style="color:darkred">' . $this->l('User Login is missing') . '</span></label></div>';
		}
		//No password
		if (!Configuration::get('user_password') || empty(Configuration::get('user_password'))) {
			$output .= '<div class="form-group"><label class="control-label"><span style="color:darkred">' . $this->l('User Password is missing') . '</span></label></div>';
		}
		//API CHECK
		if (Configuration::get('api_key') && Configuration::get('user_login') && Configuration::get('user_password') && !empty(Configuration::get('api_key')) && !empty(Configuration::get('user_login')) && !empty(Configuration::get('user_password'))) {
			$adelyaapi_active = (bool)Tools::getValue('adelyaapi_status', Configuration::get('adelyaapi_status'));
			if ($adelyaapi_active == true) {
				$adelyaUtil = new adelyaUtil();
				$apireturn = $adelyaUtil->testAPI();
				if ($apireturn && strpos($apireturn, '"code":"OK"') !== false) {
					$output .= '<div class="form-group">' . $this->l('API connection status') . ' : <span style="color:darkgreen;"><b>OK</b></span></div>';
				} else {
					$output .= '<div class="form-group">' . $this->l('API connection status') . ' : <span style="color:darkred;"><b>NOK</b></span></div>';

					if ($apireturn && strpos($apireturn, 'Invalid user access') !== false) {
						$output .= '<div class="form-group"><textarea>' . $this->l('Invalid user access (APIKEY, login or password are incorrect: no user found)') . '</textarea></div>';
					} else {
						$output .= '<div class="form-group"><textarea>' . $apireturn . '</textarea></div>';
					}
				}
			} else {
				$output .= '<div class="form-group">' . $this->l('API connection status') . ' : <span style="color:darkred;"><b>' . $this->l('INACTIVE (Check Prestashop<->Adelya link option)') . '</b></span></div>';
			}
		}
		$output .= '<div style="text-align:right;"><i class="icon-question-sign"></i><a target="_blank" href="https://asp.adelya.com/apiv1/doc/sample/prestashop/prestashop1.7/index.jsp">&nbsp;' . $this->l('Need help to configure the plugin ? Click here') . '</a></div>';
		$output .= '</div></div>';
		return $output;
	}

	public function renderConnectionForm() {
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Connection'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('API Key'),
						'name' => 'api_key',
						'desc' => $this->l('Your api key'),
						'size' => 50,
						'required' => true,
					),
					array(
						'type' => 'text',
						'label' => $this->l('API User Login'),
						'name' => 'user_login',
						'desc' => $this->l('Your login'),
						'size' => 50,
						'required' => true,
					),
					array(
						'type' => 'text',
						'label' => $this->l('API User Password'),
						'name' => 'user_password',
						'desc' => $this->l('Your password'),
						'size' => 50,
						'required' => true,
					),
					array(
						'type' => 'radio',
						'label' => $this->l('API Server'),
						'name' => 'server_url',
						'class' => 't',
						'hint' => $this->l('The API Server is the place where all informations will be sent'),
						'values' => $this->connectionArray
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Prestashop<->Adelya link'),
						'name' => 'adelyaapi_status',
						'is_bool' => true,
						'desc' => $this->l('Enable or disable data transfer between Prestashop and Adelya'),
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->trans('Enabled', array(), 'Admin.Global'),
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->trans('Disabled', array(), 'Admin.Global'),
							)
						),
					),
				),
				'submit' => array(
					'title' => $this->trans('Save', array(), 'Admin.Actions'),
				),
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitConnectionForm';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab
			. '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues() {
		return array(
			'api_key' => Tools::getValue('api_key', Configuration::get('api_key')),
			'user_login' => Tools::getValue('user_login', Configuration::get('user_login')),
			'user_password' => Tools::getValue('user_password', Configuration::get('user_password')),
			'server_url' => Tools::getValue('server_url', Configuration::get('server_url')),
			'adelyaapi_status' => (bool)Tools::getValue('adelyaapi_status', Configuration::get('adelyaapi_status')),
			'customer_sync' => Tools::getValue('customer_sync', Configuration::get('customer_sync')),
			'voucher_sync' => Tools::getValue('voucher_sync', Configuration::get('voucher_sync')),
			'fidelity_counter' => Tools::getValue('fidelity_counter', Configuration::get('fidelity_counter')),
			'specific_vouchers' => Tools::getValue('specific_vouchers', Configuration::get('specific_vouchers')),
			'fidelity_menu_text' => Tools::getValue('fidelity_menu_text', Configuration::get('fidelity_menu_text')),
			'fidelity_rules_text' => Tools::getValue('fidelity_rules_text', Configuration::get('fidelity_rules_text')),
			'connection_timeout' => Tools::getValue('connection_timeout', Configuration::get('connection_timeout')),
			'request_timeout' => Tools::getValue('request_timeout', Configuration::get('request_timeout')),
			'log_level' => Tools::getValue('log_level', Configuration::get('log_level'))
		);
	}

	public function renderConfigurationForm() {
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'radio',
						'label' => $this->l('Customer synchronization'),
						'name' => 'customer_sync',
						'class' => 't',
						'values' => array(
							array(
								'id' => 'fullsync',
								'value' => 0,
								'label' => $this->l('Full synchronization : Customer data will be updated from Adelya to Prestashop or from Prestashop to Adelya')
							),
							array(
								'id' => 'partialsync',
								'value' => 1,
								'label' => $this->l('Partial synchronization : Prestashop\'s customer data will never be updated by Adelya. Adelya will receive data updates from Prestashop')
							)
						)
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Voucher synchronization'),
						'name' => 'voucher_sync',
						'class' => 't',
						'values' => array(
							array(
								'id' => 'fullvsync',
								'value' => 0,
								'label' => $this->l('Full synchronization : Vouchers from Loyalty Operator will create a reduction in Prestashop, usable with a code in the cart to apply a discount')
							),
							array(
								'id' => 'novsync',
								'value' => 1,
								'label' => $this->l('No synchronization : Vouchers from Loyalty Operator will not be duplicated in Prestashop')
							)
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Specific vouchers'),
						'name' => 'specific_vouchers',
						'size' => 50,
						'required' => false,
						'placeholder' => $this->l('Ex : abc123,adfg852,gd201'),
						'desc' => $this->l('If you want to limit sync to specific vouchers, indicate here their unique id separated by ,')
					),
					array(
						'type' => 'text',
						'label' => $this->l('Fidelity menu title'),
						'name' => 'fidelity_menu_text',
						'size' => 50,
						'required' => false,
						'placeholder' => $this->l('Ex: Your fidelity'),
						'desc' => $this->l('Empty this field to hide the menu completely')
					),
					array(
						'type' => 'textarea',
						'lang' => false,
						'name' => 'fidelity_rules_text',
						'cols' => 40,
						'rows' => 20,
						'class' => 'rte',
						'autoload_rte' => true,
						'label' => $this->l('Message'),
						'required' => false,
						'desc' => $this->l('This field allow you to write text in the fidelity menu. This menu is visible in the customer account.')
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Fidelity counters'),
						'name' => 'fidelity_counter',
						'class' => 't',
						'hint' => $this->l('Show fidelity informations in the customer account page'),
						'values' => array(
							array(
								'id' => 'none',
								'value' => 0,
								'label' => $this->l('No counters')
							),
							array(
								'id' => 'nbPoint',
								'value' => 1,
								'label' => $this->l('Customer\'s points')
							),
							array(
								'id' => 'nbCredit',
								'value' => 2,
								'label' => $this->l('Customer\'s credits')
							),
							array(
								'id' => 'both',
								'value' => 3,
								'label' => $this->l('Customer\'s points & credits')
							)
						)
					),
				),
				'submit' => array(
					'title' => $this->trans('Save', array(), 'Admin.Actions'),
				),
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitConfigurationForm';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab
			. '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function renderDebugForm() {
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Debug'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Connection Timeout'),
						'name' => 'connection_timeout',
						'size' => 50,
						'required' => true,
						'placeholder' => $this->l('Default is 7,  use 0 for 300 sec timeout'),
						'desc' => $this->l('Set a maximum waiting time connexion to the api (in seconds)')
					),
					array(
						'type' => 'text',
						'label' => $this->l('Request Timeout'),
						'name' => 'request_timeout',
						'size' => 50,
						'required' => true,
						'placeholder' => $this->l('Default is 7,  use 0 for no timeout'),
						'desc' => $this->l('Set a maximum waiting time for apicalls (in seconds)')
					),
					array(
						'type' => 'select',
						'label' => $this->l('Log level'),
						'desc' => $this->l('Debug only : can be used to set the verbosity level of the module'),
						'name' => 'log_level',
						'required' => true,
						'options' => array(
							'query' => array(
								array(
									'id_option' => 'ERROR',
									'name' => $this->l('Log errors')
								),
								array(
									'id_option' => 'ERRORINFO',
									'name' => $this->l('Log errors and info')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					)
				),
				'submit' => array(
					'title' => $this->trans('Save', array(), 'Admin.Actions'),
				),
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitDebugForm';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab
			. '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}


	/**
	 * Hook function on customer creation event
	 * @param $params
	 * @throws Exception
	 */
	public function hookActionCustomerAccountAdd($params) {
		$adelyaUtil = new adelyaUtil();
		if ($this->context->customer && $this->context->customer->fid_program_membership == '1') {
			$adelyaUtil->addNewCustomerData($this->context->customer);
			$date = new DateTime();
			$this->context->customer->fid_program_membership_date = $date->format('Y-m-d H:i:s');
			$this->context->customer->save();
		}
	}

	/**
	 * after an update if the customer had set to enrol in fid_program, sync data in Adelya
	 * @param $params
	 * @throws PrestaShopException
	 */
	public function hookActionObjectCustomerUpdateAfter($params){
//		$adelyaUtil = new adelyaUtil();
//		var_dump($this->context->customer);
//		if($this->context->customer && $this->context->customer->fid_program_membership == '1'){
//			if($this->context->customer->fid_program_membership_date == null || $this->context->customer->fid_program_membership_date == '0000-00-00'){
//				$adelyaUtil->addNewCustomerData($this->context->customer);
//				$date = new DateTime();
//				$this->context->customer->fid_program_membership_date = $date->format('Y-m-d H:i:s');
//				$this->context->customer->save();
//			}else{
//				//$adelyaUtil->syncCustomerData($this->context->customer);
//			}
//		}
	}

	/**
	 * After the delete of a customer send deletion to adelya
	 * @param $params
	 */
	public function hookActionObjectCustomerDeleteAfter($params) {
		$adelyaUtil = new adelyaUtil();
		if ($params['object'] && $params['object'] instanceof Customer && $params['object']->fid_program_membership == '1') {
			$adelyaUtil->removeCustomerData($params['object']);
		}
	}

	/**
	 * Hook function on customer update in front
	 * @param $params
	 * @throws Exception
	 */
	public function hookActionCustomerAccountUpdate($params) {
		$adelyaUtil = new adelyaUtil();
		if ($this->context->customer) {
			if ($this->context->customer->fid_program_membership == '1') {
				//two option already sync or a new Customer to import, use membership date to select
				if ($this->context->customer->fid_program_membership_date == null  || $this->context->customer->fid_program_membership_date == '0000-00-00') {
					$adelyaUtil->addNewCustomerData($this->context->customer);
					$date = new DateTime();
					$this->context->customer->fid_program_membership_date = $date->format('Y-m-d H:i:s');
					$this->context->customer->save();
				} else {
					$adelyaUtil->syncCustomerData($this->context->customer);
				}
			} else if ($this->context->customer->fid_program_membership_date != null  && $this->context->customer->fid_program_membership_date != '0000-00-00') {
				// We deactivate the customer
				$this->context->customer->fid_program_membership_date = NULL;
				$this->context->customer->save();
				$adelyaUtil->deactiveCustomer($this->context->customer);
			}
		}
	}

	// This function is executed when someone is displaying "My account"
	public function hookCustomerAccount($params) {
		$adelyaUtil = new adelyaUtil();
		if ($this->context->customer) {
			$adelyaUtil->syncCoupons($this->context->customer->id, $this->context->language->id);
		}
		if ($adelyaUtil->pluginIsActive() == true && Tools::getValue('fidelity_menu_text', Configuration::get('fidelity_menu_text')) 
				&& trim(Tools::getValue('fidelity_menu_text', Configuration::get('fidelity_menu_text'))) != false
				&& $this->context->customer->fid_program_membership == '1' ) {
			$this->context->smarty->assign('HOOK_CUSTOMERACCOUNT_TITLE', $this->l(Tools::getValue('fidelity_menu_text', Configuration::get('fidelity_menu_text'))));
			return $this->display(__FILE__, 'my-account.tpl');
		}
		return '';
	}

	// This function is executed when someone is logging in
	public function hookActionAuthentication($params) {
		$adelyaUtil = new adelyaUtil();
		if ($this->context->customer && $this->context->customer->fid_program_membership == '1') {
			//Synchro coupons
			$adelyaUtil->syncCoupons($this->context->customer->id, $this->context->language->id);
			//Synchro customer
			$adelyaUtil->syncCustomerData($this->context->customer);
		}
	}

	// This function is executed when the order is completed
	public function hookDisplayOrderConfirmation($params) {
		$adelyaUtil = new adelyaUtil();
		$adelyaUtil->addCA($params);
	}

	public function hookActionOrderStatusPostUpdate($params) {
		$orderId = $params['id_order'];
		$order = new Order($orderId);
		if ($params['newOrderStatus']->id == 6 || $params['newOrderStatus']->id == 7) {
			//Order canceled
			$adelyaUtil = new adelyaUtil();
			$adelyaUtil->cancelCA($order);
		} else if ($params['newOrderStatus']->id == 2 || $params['newOrderStatus']->id == 11 || $params['newOrderStatus']->id == 5 || $params['newOrderStatus']->id == 4) {
			// Payment accepted, we transform an order into an addCA
			//Todo next version
		}
	}
	
	// This function is called when the cart is displayed
	// TODO not hooked
	public function hookDisplayShoppingCart($params) {
//		$adelyaUtil = new adelyaUtil();
//		$adelyaUtil->adelyalog("DEBUG hookDisplayShoppingCart", 1);
		if ($this->context->customer) {
			//Synchro coupons
			$adelyaUtil->syncCoupons($this->context->customer->id, $this->context->language->id);
		}
	}

	/**
	 * hook function to add field on customer creation form
	 * @param $params
	 * @return array
	 */
	public function hookAdditionalCustomerFormFields($params) {
		return [
			(new FormField)
				->setName('fid_program_membership')
				->setType('checkbox')
				->setLabel($this->l('fid_program_membership'))
		];
	}

	/**
	 * -------------------------------------
	 * Admin form customization
	 * -------------------------------------
	 */
	/**
	 * Modification du formulaire d'édition d'un client en admin
	 * @param array $params
	 */
	public function hookActionCustomerFormBuilderModifier(array $params) {
//		/** @var FormBuilderInterface $formBuilder */
//		$formBuilder = $params['form_builder'];
//		$formBuilder->add('fid_program_membership', CheckboxType::class, [
//			'label' => $this->l('fid_program_membership_auth'),
//			'required' => false,
//			'data' => ($this->context->customer && $this->context->customer->fid_program_membership) ? true : false
//		]);
//		$params['data']['fid_program_membership'] = ($this->context->customer && $this->context->customer->fid_program_membership) ? true : false;
	}

	/**
	 * hook sur le formulaire d'admin de customer en update
	 * @param array $params
	 * @throws PrestaShopException
	 */
	public function hookActionAfterUpdateCustomerFormHandler($params) {
		$this->updateCustomerMembershipStatus($params);
	}

	/**
	 * hook sur le formulaire d'admin de customer en creation
	 * @param array $params
	 * @throws PrestaShopException
	 */
	public function hookActionAfterCreateCustomerFormHandler($params) {
		$this->updateCustomerMembershipStatus($params);
	}
	/**
	 * methode de getion du champ custom sur le formulaire client dans l'admin
	 * @param array $params
	 * @throws PrestaShopException
	 */
	private function updateCustomerMembershipStatus(array $params) {
//		/** @var array $customerFormData */
//		$customerFormData = $params['form_data'];
//		$is_FidProgram_MemberShip = (bool)$customerFormData['fid_program_membership'];
//		var_dump($this->context->customer);exit;
//		$this->context->customer->fid_program_membership = $is_FidProgram_MemberShip;
////		$this->context->customer->save();
	}



}

?>
