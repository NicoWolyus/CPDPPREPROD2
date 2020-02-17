<?php
/**
 * 2014 KerAwen
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@kerawen.com so we can send you a copy immediately.
 *
 * @author    KerAwen <contact@kerawen.com>
 * @copyright 2014 KerAwen
 * @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

/* */
if (! defined('_PS_VERSION_')) exit();

if (!defined('_PS_PRICE_COMPUTE_PRECISION_')) {
	define('_PS_PRICE_COMPUTE_PRECISION_', _PS_PRICE_DISPLAY_PRECISION_);
}

require_once(dirname(__FILE__).'/defines.php');
require_once(_KERAWEN_API_DIR_.'/constants.php');
require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');

// Not required out of FR
define('_KERAWEN_525_CLASS_', _KERAWEN_DIR_.'/secure/KerawenSecure.php');
require_once(_KERAWEN_525_CLASS_);


class Kerawen extends Module
{
	public function __construct()
	{
		$this->author = 'KerAwen';
		$this->tab = 'others';
		$this->name = 'kerawen';
		$this->version = '2.2.14.2';
		$this->module_key = '9fd46a372b157c662f299c6df0b7b1af';
		$this->ps_versions_compliancy['min'] = '1.5';
		$this->ps_versions_compliancy['max'] = '1.7';
		$this->need_instance = 0;
		$this->dependencies = array();
		$this->limited_countries = array();
		
		$this->bootstrap = true;
		parent::__construct();
		
		$this->displayName = $this->l('KerAwen');
		$this->description = $this->l('An intuitive, mobile and omni-channel cash register. Compliant with FR law 2015-1785, article 88.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
		
		$context = Context::getContext();
		$context->module = $this;
		$this->secure = Kerawen525::getInstance();
	}
	
	/*
	 * Module presentation within config page
	 */
	public function getContent()
	{
		if (Tools::isSubmit('archive')) {
			// TODO move to controller
			$this->secure->getArchive(Tools::getValue('id'));
		}
		$link = new Link();
		$this->context->smarty->assign(array(
				'lang' => $this->getContext()->language->iso_code,
				'server' => _KERAWEN_SERVER_,
				'shop' => Tools::getShopDomain().__PS_BASE_URI__,
				'key' => Configuration::get('KERAWEN_LICENCE_KEY'),
				'version' => $this->version,
				'psver' => _PS_VERSION_,
				'email' => $this->context->employee->email,
				'name' => $this->context->employee->firstname.' '.$this->context->employee->lastname,
				'register' => $link->getAdminLink('KerawenRegister', true),
		));
		return $this->display(__FILE__, 'views/templates/admin/kerawen.tpl');
	}
	
	/*
	 * Module installation
	 */
	public function install()
	{
		require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
		$backup = backupShopContext();
		Shop::setContext(Shop::CONTEXT_ALL);
		
		// Force deletion of former separate certification module
		require_once(_KERAWEN_TOOLS_DIR_.'/module.php');
		$tools = new ModuleTools();
		$tools->delete('kerawen_legal');
		
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
			Module::$update_translations_after_install = false;
			$db_warning = '';
		}
			
		$res = parent::install()
			&& $this->installDatabase($db_warning)
			&& $this->installConfig()
			&& $this->installMenu()
			&& $this->installHook();
			
		if ($res) Configuration::updateValue('KERAWEN_INSTALLED_VERSION', $this->version);
		Db::getInstance()->insert('kerawen_version', array(
			'version' => $this->version,
			'date' => date('Y-m-d H:i:s'),
			'res' => $res,
		));
		
		restoreShopContext($backup);
		return $res;
	}
	
	public function uninstall()
	{
		$this->unInstallMenu();
		$this->uninstallConfig();
		$this->uninstallDatabase();
		return parent::uninstall();
	}
	
	public function upgrade()
	{
		$backup = backupShopContext();
		Shop::setContext(Shop::CONTEXT_ALL);
		$this->uninstall();
		$res = $this->install();
		restoreShopContext($backup);
		return $res;
	}
	
	/*
	 * Database extension
	 */
	public function installDatabase(&$db_warning)
	{
		require_once(_KERAWEN_DIR_.'/install/database.php');
		return installDatabase($db_warning)
			&& $this->secure->createDatabase();
	}
	
	public function uninstallDatabase()
	{
		return true;
	}
	
	/*
	 * Module configuration
	 */
	protected function installConfig()
	{
		require_once(_KERAWEN_CLASS_DIR_.'/KerawenPayment.php');
		$default_payments = KerawenPayment::getModes($this);
		$default_payments = indexArray($default_payments, 'id');
		
		$default_config = array(
				'KERAWEN_NOTIF_PERIOD' => 60,
				'KERAWEN_SWITCH_CASHIER' => false,
				'KERAWEN_SWITCH_SHOP' => false,
				'KERAWEN_DISCOUNT_CART' => true,
				'KERAWEN_SELECT_DELIVERY' => true,
				'KERAWEN_GATHER_MEASURES' => false,
				'KERAWEN_SHOW_AMOUNTS' => true,
				'KERAWEN_SEND_EMAIL' => true,
				'KERAWEN_OVERRIDE_EMAIL' => false,
				'KERAWEN_DEFAULT_GROUP' => 1,
				'KERAWEN_OVERRIDE_GROUP' => 0,
				'KERAWEN_CATALOG_PAGE_SIZE' => 30,
				'KERAWEN_CATALOG_FULL_NAMES' => false,
				'KERAWEN_CATALOG_REFERENCES' => true,
				'KERAWEN_CART_FULL_NAMES' => false,
				'KERAWEN_PAYMENTS' => (string)Tools::jsonEncode($default_payments),
				'KERAWEN_TICKET_CPL' => 48,
				'KERAWEN_TICKET_SHOP_NAME' => true,
				'KERAWEN_TICKET_SHOP_ADDRESS' => true,
				'KERAWEN_TICKET_SHOP_COUNTRY' => false,
				'KERAWEN_TICKET_SHOP_URL' => false,
				'KERAWEN_TICKET_SHOP_EMAIL' => true,
				'KERAWEN_TICKET_COMMENTS' => false,
				'KERAWEN_TICKET_PRODUCT_NOTE' => true,
				'KERAWEN_TICKET_FULL_NAMES' => false,
				'KERAWEN_TICKET_DETAIL_TAXES' => true,
				'KERAWEN_TICKET_CUSTOMER' => true,
				'KERAWEN_TICKET_LOYALTY' => 0,
				'KERAWEN_TICKET_MODE' => true,
				'KERAWEN_TICKET_MESSAGE' => '',
				'KERAWEN_TICKET_SHOP_DETAILS' => false,
				'KERAWEN_TICKET_BARCODE' => true,
				'KERAWEN_TICKET_ORDER_NUMBER' => false,
				'KERAWEN_TICKET_DISCOUNT' => true,
				'KERAWEN_TICKET_REF' => false,
				'KERAWEN_TICKET_EMPLOYEE_NAME' => true,
				'KERAWEN_TICKET_PRINT_OPEN_CLOSE' => true,
				'KERAWEN_TICKET_PRINT_AUTO' => false,
				'KERAWEN_TICKET_PRINT_MIN_AMOUNT' => 0,
				'KERAWEN_SCALE_PREFIX' => 2,
				'KERAWEN_SCALE_PRODUCT_LENGTH' => 6,
				'KERAWEN_SCALE_PRICE_LENGTH' => 5,
				'KERAWEN_SCALE_PRICE_MULTIPLIER' => 0.00152449,
				'KERAWEN_QUOTE_DURATION' => 90,
				'KERAWEN_QUOTE_MESSAGE' => 'Nous restons � votre disposition pour toute information compl�mentaire.' . "\n" . 'Cordialement.',
				'KERAWEN_QUOTE_MESSAGE_2' => 'Si ce devis vous convient, veuillez nous le retourner sign� pr�c�d� de la mention :' . "\n" . '"BON POUR ACCORD ET EXECUTION DU DEVIS"' . "\n\n\n" . 'Date :                                                       Signature :',
				'KERAWEN_QUOTE_MESSAGE_3' => 'Vous pouvez aussi confirmer en ligne',
				'KERAWEN_QUOTE_MESSAGE_4' => 'Validit� du devis : 3 mois',
				'KERAWEN_QUOTE_COUNTER' => 0,
				'KERAWEN_QUOTE_PRODUCT_NOTE' => true,
				'KERAWEN_LABEL_ITEMS_BY_PAGE' => 50,
				'KERAWEN_GIFT_CARD_DURATION' => 365,
				'KERAWEN_GIFT_CARD_TICKET_MESSAGE' => '',
				'KERAWEN_GIFT_CARD_JS' => false,
				'KERAWEN_DISCOUNT_DURATION' => 365,
				'KERAWEN_ORDERS_LIST_COLUMN_SHOP' => true,
				'KERAWEN_ORDERS_LIST_COLUMN_CARRI' => false,
				'KERAWEN_ORDERS_LIST_COLUMN_TILL' => false,
				'KERAWEN_ORDERS_LIST_COLUMN_COMP' => false,
				'KERAWEN_ORDERS_LIST_ITEMS_BY_PAG' => 15,
				'KERAWEN_LABEL_IN_STORE' => '',
				'KERAWEN_LABEL_TAKEAWAY' => '',
				'KERAWEN_LABEL_DELIVERY' => '',
				'KERAWEN_DISPLAY_CPL' => 19,
				'KERAWEN_DISPLAY_MSG_START' => 'Bonjour',
				'KERAWEN_DISPLAY_MSG_END' => 'Merci',
				'KERAWEN_EMPLOYEE_PASSWORD' => false,
				'KERAWEN_EMPLOYEE_PASSWORD_EXCP' => true,
				'KERAWEN_INVOICE_FREE_TEXT' => '',
				'KERAWEN_INVOICE_TAX' => true,
				'KERAWEN_INVOICE_NUM_ORDER' => true,
				'KERAWEN_INVOICE_NUM_CART' => false,
				'KERAWEN_INVOICE_DISP_TAX' => false,
				'KERAWEN_INVOICE_DISP_SHIPPING' => true,
				'KERAWEN_INVOICE_DISP_UNIT_VAT' => false,
				'KERAWEN_INVOICE_DISP_TOTAL_VAT' => false,
				'KERAWEN_INVOICE_DISP_BARCODE' => false,
				'KERAWEN_INVOICE_REF_COL' => false,
				'KERAWEN_INVOICE_HEADER_DATE' => 0,
				'KERAWEN_ORDER_QUICK_END' => false,
				'KERAWEN_ORDER_PULSE' => false,
				'KERAWEN_DECIMAL_SEPARATOR' => '.',
				'KERAWEN_EAN13_SEARCH_LENGTH' => '12',
				'KERAWEN_PLAY_SOUND' => false,
				'KERAWEN_SCAN_NOT_FOUND' => true,
				'KERAWEN_OFFER_PERIOD' => 30,
				'KERAWEN_TICKET_CUSTOMER_PHONE' => false,
				'KERAWEN_DEFAULT_VAT' => 0,
				'KERAWEN_POSTCODE_REQUIRED' => true,
		        'KERAWEN_ADDRESS1_REQUIRED' => false,
		        'KERAWEN_CITY_REQUIRED' => false,
				'KERAWEN_URL_WITH_TOKEN' => true,
				'KERAWEN_CUST_ACCOUNT_ADDR' => false,
				'KERAWEN_IMAGE_PRODUCT' => 'home_default',
				'KERAWEN_IMAGE_CATEGORY' => 'category_default',
				'KERAWEN_QUOTE_ABS_PRICE' => true,
				'KERAWEN_LABEL_BARCODE_TYPE' => 'ean13',
				'KERAWEN_LABEL_BARCODE' => 'ean',//ean/ref
				'KERAWEN_QUOTE_TAX' => true,
				'KERAWEN_QUOTE_DISP_TAX' => true,
				'KERAWEN_QUOTE_DISP_UNIT_VAT' => false,
				'KERAWEN_QUOTE_DISP_TOTAL_VAT' => false,
				'KERAWEN_QUOTE_REF_COL' => true,
				'KERAWEN_QUOTE_IMG' => true,
		        'KERAWEN_TICKET_MSG_DISCOUNT' => '',
		        'KERAWEN_ORDERS_LIST_COLUMN_ADD' => false,
		        'KERAWEN_CUST_PRINT' => false,
		        'KERAWEN_CUST_HEADER_MESSAGE' => '',
		        'KERAWEN_CUST_FOOTER_MESSAGE' => '',
		        'KERAWEN_PHONE_REQUIRED' => false,
		        'KERAWEN_MOBILE_REQUIRED' => false,
		);
		
		$context = Context::getContext();
		$context->module = $this;
		$db = Db::getInstance();
		
		// User defined configuration
		foreach($default_config as $key => $default) {
			$current = Configuration::get($key);
			if ($current === false)
				Configuration::updateValue($key, $default);
		}
		
		require_once(_KERAWEN_CLASS_DIR_.'/data.php');
		require_once(_KERAWEN_CLASS_DIR_.'/stock.php');
		getKerawenSecureKey();
		getStockShippingReason();
		getStockReturnReason();
		
		//$anonymous = getAnonymousCustomer(); -> done from getDefaultDeliveryAddress()
		$defaultDeliveryAddress = getDefaultDeliveryAddress();
		
		// Additional order states
		if (!Configuration::get('KERAWEN_OS_RECEIVED'))
		{
			$os = new OrderState();
			$os->name = getForLanguages($this->l('Received'), $this, null, $this->l('Received'));
			$os->color = '#FFFFA8';
			$os->unremovable = true;
			$os->module_name = $this->name;
			$os->save();
			Configuration::updateValue('KERAWEN_OS_RECEIVED', $os->id);
		}
		if (!Configuration::get('KERAWEN_OS_READY'))
		{
			$os = new OrderState();
			$os->name = getForLanguages($this->l('Ready'), $this, null, $this->l('Ready'));
			$os->color = '#A8FFFF';
			$os->unremovable = true;
			$os->module_name = $this->name;
			$os->save();
			Configuration::updateValue('KERAWEN_OS_READY', $os->id);
		}
		
		// Credit priority among cart rules: very low
		Configuration::updateValue('KERAWEN_CREDIT_PRIORITY', 99);
		
		//Secure Kerawen Cron
		if (!Configuration::get('KERAWEN_CRON_KEY'))
		{
			Configuration::updateValue('KERAWEN_CRON_KEY', Tools::passwdGen(8, ''));
		}
		
		return true;
	}
	
	protected function uninstallConfig()
	{
		// Keep user defined parameters
		return true;
	}
	
	/*
	 * Admin menu extension
	 */
	public function getTabsDescription()
	{
		//hide persmissions PS 1.5 
		$hide_perm = Tools::version_compare(_PS_VERSION_, '1.6', '<');
		
		return array(

				'main' => array(
						'main' => true,
						'class' => 'KerawenMain',
						'name' => $this->l('KerAwen'),
				),
				'home' => array(
						'class' => 'KerawenHome',
						'name' => $this->l('Home'),
				),
				'register' => array(
						'class' => 'KerawenRegister',
						'name' => $this->l('Cash register'),
				),
				'report' => array(
						'class' => 'KerawenReport',
						'name' => $this->l('Report'),
				),
				'label' => array(
						'class' => 'KerawenLabel',
						'name' => $this->l('Labels'),
				),
				'config' => array(
						'class' => 'KerawenConfig',
						'name' => $this->l('Configuration'),
				),
				'permissions' => array(
						'class' => 'KerawenPermissions',
						'name' => $this->l('Permissions'),
						'hidden' => $hide_perm,
				),
				'employees' => array(
						'class' => 'KerawenEmployees',
						'name' => $this->l('Employees'),
				),
				'fixes' => array(
						'class' => 'KerawenFixes',
						'name' => $this->l('PrestaShop check'),
				),
				'export' => array(
						'class' => 'KerawenExport',
						'name' => $this->l('Export'),
						'hidden' => true,
				),
				'certif' => array(
						'class' => 'KerawenCertif',
						'name' => $this->l('Compliance'),
				),
				'tills' => array(
						'class' => 'KerawenTills',
						'name' => $this->l('Hardware'),
				),
    		    'marketing' => array(
    		        'class' => 'KerawenMarketing',
    		        'name' => $this->l('Marketing'),
    		    ),
		);
	}
	
	protected function installMenu()
	{
		$root = 0;
		if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
			$tab = new Tab((int)Tab::getIdFromClassName('KERAWEN'));
			$tab->id_parent = 0;
			$tab->module = $this->name;
			$tab->class_name = 'KERAWEN';
			$tab->name = getForLanguages('KERAWEN', $this, null);
			$tab->active = true;
			$tab->save();
			$root = $tab->id;
		}
		
		// Create or reuse tabs in order to keep profiles consistency
		$main_tab = null;
		$descs = $this->getTabsDescription();
		foreach ($descs as $desc)
		{
			$main = isset($desc['main']) ? $desc['main'] : false;
			$hidden = isset($desc['hidden']) ? $desc['hidden'] : false;
			
			$tab = new Tab((int)Tab::getIdFromClassName($desc['class']));
			$tab->id_parent = $main ? $root : ($hidden ? -1 : $main_tab->id);
			$tab->module = $this->name;
			$tab->class_name = $desc['class'];
			$tab->name = getForLanguages($desc['name'], $this, null);
			$tab->active = 1;
			$tab->save();
			if ($main) $main_tab = $tab;
		}
		
		// Create quick access menu
		$quick = new QuickAccess();
		$quick->name = getForLanguages($this->l('KerAwen cash register'), $this, null);
		$quick->link = 'index.php?controller=KerawenRegister';
		$quick->new_window = 1;
		$quick->save();
		return true;
	}
	
	protected function uninstallMenu()
	{
		// Remove quick access menu
		$id = Db::getInstance()->getValue(
				'SELECT id_quick_access FROM `'._DB_PREFIX_.'quick_access`
			WHERE link = "index.php?controller=KerawenRegister"');
		if ($id)
		{
			$quick = new QuickAccess($id);
			$quick->delete();
		}
		
		// Disable tabs
		$ids = Db::getInstance()->executeS(
				'SELECT id_tab FROM `'._DB_PREFIX_.'tab`
			WHERE module = "'.$this->name.'"');
		foreach($ids as $id)
		{
			$tab = new Tab($id['id_tab']);
			$tab->active = 0;
			$tab->save();
		}
		
		return true;
	}
	
	/*
	 * Hooks
	 */
	protected function installHook()
	{
		// General
		$this->registerHook('actionDispatcher');
		
		// Back-Office
		$this->registerHook('displayBackOfficeHeader');
		
		// Front-Office
		$this->registerHook('displayHeader');
		$this->registerHook('displayProductTabContent');
		$this->registerHook('displayCustomerAccount');
		
		// Register as payment module (e.g. for prestafraud)
		$this->registerHook('displayPayment');
		
		// Transactions
		$this->registerHook('actionValidateOrder');
		
		$this->registerHook('actionOrderStatusUpdate');
		$this->registerHook('actionOrderStatusPostUpdate');
		$this->registerHook('actionObjectCartRuleAddAfter');
		$this->registerHook('actionOrderSlipAdd');
		
		$this->registerHook('actionObjectOrderAddAfter');
		$this->registerHook('actionObjectOrderUpdateBefore');
		$this->registerHook('actionObjectOrderUpdateAfter');
		$this->registerHook('actionObjectOrderDetailAddAfter');
		$this->registerHook('actionObjectOrderDetailUpdateBefore');
		$this->registerHook('actionObjectOrderDetailUpdateAfter');
		$this->registerHook('actionObjectOrderDetailDeleteBefore');
		$this->registerHook('actionObjectOrderDetailDeleteAfter');
		$this->registerHook('actionObjectOrderSlipAddAfter');
		$this->registerHook('actionObjectOrderSlipUpdateAfter');
		$this->registerHook('actionObjectOrderPaymentAddAfter');
		$this->registerHook('actionObjectOrderPaymentUpdateBefore');
		$this->registerHook('actionObjectOrderPaymentUpdateAfter');
		$this->registerHook('actionObjectOrderPaymentDeleteBefore');
		$this->registerHook('actionObjectOrderPaymentDeleteAfter');
		
		// Documents
		$this->registerHook('displayPDFInvoice');
		$this->registerHook('displayPDFInvoiceKerawen');
		$this->registerHook('displayPDFOrderSlip');
		$this->registerHook('displayPDFOrderSlipKerawen');
		$this->registerHook('displayPDFDeliverySlip');
		
		// Product extension
		$this->registerHook('displayAdminProductsExtra');
		$this->registerHook('actionProductUpdate');
		
		return true;
	}
	
	public function hookActionDispatcher($params) {
		// Force activation on each request
	}
	
	public function hookActionObjectOrderUpdateBefore($params) {
		$this->secure->registerOrder($params['object'], true);
	}
	public function hookActionObjectOrderAddAfter($params) {
		$this->secure->registerOrder($params['object']);
	}
	public function hookActionObjectOrderUpdateAfter($params) {
		$this->secure->registerOrder($params['object']);
	}
	
	public function hookActionObjectOrderDetailAddAfter($params) {
		$this->secure->registerOrderDetail($params['object']);
	}
	public function hookActionObjectOrderDetailUpdateBefore($params) {
		$this->secure->registerOrderDetail($params['object'], true);
	}
	public function hookActionObjectOrderDetailUpdateAfter($params) {
		$this->secure->registerOrderDetail($params['object']);
	}
	public function hookActionObjectOrderDetailDeleteBefore($params) {
		$this->secure->registerOrderDetail($params['object'], true);
	}
	public function hookActionObjectOrderDetailDeleteAfter($params) {
		$this->secure->registerOrderDetail($params['object']);
	}
	
	public function hookActionObjectOrderSlipAddAfter($params) {
		$this->secure->registerOrderSlip($params['object']);
	}
	public function hookActionObjectOrderSlipUpdateAfter($params) {
		$this->secure->registerOrderSlip($params['object']);
	}
	
	public function hookActionObjectOrderPaymentAddAfter($params) {
		$this->secure->registerOrderPayment($params['object']);
		
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		handleOrderPayment($params['object'], false);
	}
	public function hookActionObjectOrderPaymentUpdateBefore($params) {
		$this->secure->registerOrderPayment($params['object'], true);
	}
	public function hookActionObjectOrderPaymentUpdateAfter($params) {
		$this->secure->registerOrderPayment($params['object']);
	}
	public function hookActionObjectOrderPaymentDeleteBefore($params) {
		$this->secure->registerOrderPayment($params['object'], true);
	}
	public function hookActionObjectOrderPaymentDeleteAfter($params) {
		$this->secure->registerOrderPayment($params['object']);

		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		handleOrderPayment($params['object'], true);
	}
	
	public function hookDisplayPayment() {
		// Acts as payment module
		return;
	}
	
	public function hookDisplayBackOfficeHeader() {
		$this->context->controller->addCSS($this->getPathUri().'css/admin.css?v='.$this->version, 'all', null, false);
	}
	
	public function hookDisplayAdminProductsExtra($params) {
		require_once(dirname(__FILE__).'/controllers/admin/KerawenProductController.php');
		$controller = new KerawenProductController();
		return $controller->renderView($params);
	}
	
	public function hookActionProductUpdate($params) {
		// Some modules may trigger the hook from FO
		// Do not continue in that case (product is not modified wrt kerawen)
		$cookie = new Cookie('psAdmin');
		$employee = new Employee($cookie->id_employee);
		if (!$employee->isLoggedBack()) return;
		
		require_once(dirname(__FILE__).'/controllers/admin/KerawenProductController.php');
		$controller = new KerawenProductController();
		$controller->postProcess();
	}
	
	public function hookDisplayProductTabContent($params) {
		require_once (_KERAWEN_CLASS_DIR_.'/customer.php');
		registerVisit($params['cookie']->id_customer, $params['product']->id);
	}
	
	public function hookDisplayHeader($params) {
		$params['__FILE__'] = __FILE__;
		$data_header = '';
		if (Configuration::get('KERAWEN_GIFT_CARD_JS')) {
			require_once (dirname(__FILE__).'/controllers/front/giftcard.php');
			$data_header .= KerawenGiftcardModuleFrontController::hookDisplayHeader($params, $this);
		}
		return $data_header;
	}
	
	protected $blocking_email = false;
	protected $blocking_invoice = false;
	
	public function hookActionValidateOrder($params) {
		if (Configuration::get('KERAWEN_OVERRIDE_EMAIL')) {
			// Deactivate mails until everything is certified
			$this->blocking_email = true;
			setExtendedContext('PS_MAIL_METHOD', Configuration::get('PS_MAIL_METHOD'));
			Configuration::set('PS_MAIL_METHOD', 3);
		}
		else if (!$this->blocking_invoice) {
			// Avoid not certified invoice to be sent
			$this->blocking_invoice = true;
			setExtendedContext('PS_INVOICE', Configuration::get('PS_INVOICE'));
			Configuration::set('PS_INVOICE', 0);
		}
		
	
		//ADELYA
		if (Module::isInstalled('adelyaapi') && Module::isEnabled('adelyaapi')) {
		    try {
		        require_once(_PS_MODULE_DIR_.'/adelyaapi/adelyaUtil.php');
		        $adelyaUtil = new adelyaUtil();
		        $adelyaUtil->addCA($params);
		    }
		    catch (Exception $e) {
		        // Ignore
		    }
		} 
		
		
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		handleOrderCreation($params['order'], $params['cart'], $params['orderStatus']);
	}
	
	public function hookActionOrderStatusUpdate($params) {
		if ($this->blocking_invoice) {
			// Restore invoice generation
			Configuration::set('PS_INVOICE', getExtendedContext('PS_INVOICE', 0));
		}

		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		handleOrderState($params['id_order'], $params['newOrderStatus'], false);
	}
	
	public function hookActionOrderStatusPostUpdate($params) {
		if ($this->blocking_email) {
			// Push order state change for subsequent email
			$order_states = getExtendedContext('order_states', array());
			$order_states[] = array(
				'id_order' => $params['id_order'],
				'id_os' => $params['newOrderStatus']->id,
			);
			setExtendedContext('order_states', $order_states);
		}
		if ($this->blocking_invoice) {
			// Reset no invoice generation
			Configuration::set('PS_INVOICE', 0);
		}
		
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		handlePostOrderState($params['id_order'], $params['newOrderStatus']);
	}
	
	public function hookActionOrderSlipAdd($params) {
		$slip = null;
		if (isset($params['order_slip'])) {
			$slip = $params['order_slip'];
		}
		else if (isset($params['order'])) {
			$id_slip = Db::getInstance()->getValue('
				SELECT id_order_slip FROM '._DB_PREFIX_.'order_slip
				WHERE id_order = '.pSQL($params['order']->id).'
				ORDER BY date_add DESC');
			if ($id_slip) $slip = new OrderSlip($id_slip);
		}
		if ($slip) {
			require_once (_KERAWEN_CLASS_DIR_.'/order.php');
			handleOrderSlip($slip);
		}
	}
	
	public function hookActionObjectCartRuleAddAfter($params) {
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		if ((Tools::getvalue('controller') == 'AdminOrders')
				&& (Tools::isSubmit('generateDiscount') || Tools::isSubmit('generateDiscountRefund'))
				&& Tools::isSubmit('id_order')) {
					
			// Transform as credit
			$rule = $params['object'];
			Db::getInstance()->insert('cart_rule_kerawen', array(
					'id_cart_rule' => $rule->id,
					'type' => _KERAWEN_CR_CREDIT_,
			), true);
			
			// Register as payment
			$order = new Order(Tools::getValue('id_order'));
			addPayment(null, $order, _KERAWEN_PM_CREDIT_, $this->l('Credit'), -$rule->reduction_amount, $rule->id, null);
		}
	}
	
	public function hookDisplayPDFDeliverySlip($params) {
		
		$object = $params['object'];
		$db = Db::getInstance();
		
		$buf = $db->executeS('
			SELECT
				od.id_order_detail AS id,
				odk.note AS note
			FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk
			ON odk.id_order_detail = od.id_order_detail
			WHERE od.id_order = '.pSQL($object->id_order)
		);
		
		$notes = array();
		foreach ($buf as $row)
		{
			if ($row['note'] != '') {
				$notes[$row['id']] = $row['note'];
			}
		}
		
		$this->context->smarty->assign('kerawen_notes', $notes);
	}
	
	
	public function hookDisplayPDFInvoice($params) {
		// TODO replace data by certified one event if default template
	}
	
	public function hookDisplayPDFInvoiceKerawen($params) {
		
		/*
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$this->context->smarty->assign('kerawen', array(
			'settings' => getReceiptSettings(),
			'invoice' => $this->secure->getInvoiceData($params['object']->id_order, 0),
		));
		// -> pdf.php
		*/
		


		// TODO get additional data
// 		$db = Db::getInstance();
// 		$id_lang = $this->context->language->id;
// 		$object = $params['object'];
// 		$hook_display_pdf  = "" . $db->getValue('SELECT invoice_note FROM '._DB_PREFIX_.'order_kerawen WHERE id_order = ' . (int) $object->id_order);
// 		if ($hook_display_pdf != '') {
// 			$hook_display_pdf .= "\n";
// 		}
// 		$hook_display_pdf .= "" . $db->getValue('SELECT note FROM '._DB_PREFIX_.'order_invoice WHERE id_order = ' . (int) $object->id_order);
// 		$hook_display_pdf = nl2br($hook_display_pdf);
// 		if ($hook_display_pdf != '') {
// 			$hook_display_pdf .= "<br />";
// 		}
// 		//better way?
// 		$free_text = Configuration::get('KERAWEN_INVOICE_FREE_TEXT', $id_lang);
// 		if (!$free_text) {
// 			$free_text = Configuration::get('KERAWEN_INVOICE_FREE_TEXT');
// 		}
// 		$hook_display_pdf .= $free_text;
// 		return $hook_display_pdf;
		
		//bellow: move to method "hookdisplayPDFInvoice"
// 		$id_lang = $this->context->language->id;
// 		$invoice = $params['object'];
// 		$order = new Order((int)$invoice->id_order);
// 		$prefix = Configuration::get('PS_INVOICE_PREFIX', $id_lang, null, (int)$order->id_shop);
		
// 		$buf = $db->executeS('
// 			SELECT
// 				od.id_order_detail AS id,
// 				od.product_id AS id_product,
// 				od.product_attribute_id AS id_attribute,
// 				od.product_reference AS reference,
// 				od.product_name AS name,
// 				od.product_quantity AS quantity,
// 				od.unit_price_tax_excl AS unit_price_te,
// 				od.total_price_tax_excl AS total_price_te,
// 				odk.note AS note,
// 				odk.measure AS measure,
// 				odk.`precision` AS `precision`,
// 				odk.unit AS unit,
// 				odk.unit_price_tax_excl AS measure_te
// 			FROM '._DB_PREFIX_.'order_detail od
// 			LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk
// 				ON odk.id_order_detail = od.id_order_detail
// 			WHERE od.id_order = '.pSQL($order->id));
		
// 		$details = array();
// 		$pa2id = array();
// 		$gather = Configuration::get('KERAWEN_GATHER_MEASURES');
// 		foreach ($buf as $row)
// 		{
// 			$pa = $row['id_product'].'_'.$row['id_attribute'];
// 			if ($gather && $row['measure']) $pa = $row['id_product'];
			
// 			$id = isset($pa2id[$pa]) ? $pa2id[$pa] : $row['id'];
// 			if (!isset($details[$id]))
// 			{
// 				$pa2id[$pa] = $id;
// 				$details[$id] = $row;
// 				if ($row['measure'])
// 				{
// 					$details[$id]['quantity'] = (int)$row['quantity'];
// 					$details[$id]['measure'] = (float)$row['measure'];
// 					$details[$id]['unit_price_te'] = (float)$row['measure_te'];
// 					$details[$id]['total_price_te'] = (float)$row['total_price_te'];
// 				}
// 			}
// 			elseif ($gather && $row['measure'])
// 			{
// 				$details[$id]['quantity'] += (int)$row['quantity'];
// 				$details[$id]['measure'] += (float)$row['measure'];
// 				$details[$id]['total_price_te'] += (float)$row['total_price_te'];
// 			}
// 		}
		
// 		$buf = $db->executeS('
// 			SELECT
// 				odt.id_tax AS code,
// 				t.rate AS rate,
// 				SUM(odt.total_amount) AS amount_wrong,
// 				SUM(od.total_price_tax_incl - od.total_price_tax_excl) AS amount,
// 				SUM(od.total_price_tax_excl*(1 - IFNULL(ok.product_global_discount,0))) AS base
// 			FROM '._DB_PREFIX_.'order_detail_tax odt
// 			JOIN '._DB_PREFIX_.'tax as t
// 				ON t.id_tax = odt.id_tax
// 			JOIN '._DB_PREFIX_.'order_detail od
// 				ON od.id_order_detail = odt.id_order_detail
// 			JOIN '._DB_PREFIX_.'order_kerawen ok
// 				ON ok.id_order = od.id_order
// 			WHERE od.id_order = '.pSQL($order->id).'
// 			GROUP BY odt.id_tax');
		
// 		$taxes = array();
// 		foreach ($buf as $row) $taxes[$id] = $row;
		
// 		$this->context->smarty->assign('kerawen', array(
// 				'invoice' => $invoice,
// 				'invoice_number' => sprintf('%1$s%2$06d', $prefix, $invoice->number),
// 				'invoice_date' => Tools::displayDate($invoice->date_add),
// 				'payment_date' => Tools::displayDate(date('Y-m-d', strtotime($invoice->date_add.' +15 day'))),
// 				'customer_number' => $order->id_customer ? $order->id_customer : '',
// 				'invoice_address' => AddressFormat::generateAddress(new Address((int)$order->id_address_invoice), array(), '<br/>', ' '),
// 				'invoice_details' => $details,
// 				'invoice_taxes' => $taxes,
// 		));
		
// 		$buf = Db::getInstance()->getRow(
// 				'SELECT invoice_note FROM '._DB_PREFIX_.'order_kerawen
// 		 WHERE id_order = ' . pSql($order->id)
// 				);
		
// 		if ($buf) {
// 			return nl2br($buf['invoice_note']);
// 		}
	}
	
	public function hookDisplayPDFOrderSlip($params) {
		// TODO replace data by certified one event if default template
	}
	
	public function hookDisplayPDFOrderSlipKerawen($params) {
		/*
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$this->context->smarty->assign('kerawen', array(
			'settings' => getReceiptSettings(),
			'invoice' => $this->secure->getInvoiceData($params['object']->id_order, $params['object']->id),
		));
		// -> pdf.php
		*/
		// TODO get additional data ?
	}
	
	public function hookDisplayPDFDelivery($params) {
// 		$db = Db::getInstance();
		
// 		$object = new stdClass();
// 		$object->id_order = $db->getValue('SELECT id_order FROM '._DB_PREFIX_.'orders WHERE reference = "' . $params['reference'] . '"');
// 		$params['object'] = $object;
		
// 		//asign vars to template
// 		$this->hookdisplayPDFInvoice($params);
		
// 		$common_path = 'pdf/delivery_group_rows.tpl';
		
// 		//Choose template
// 		//theme
// 		if (file_exists ( _PS_THEME_DIR_ . $common_path )) {
// 			$delivery_pdf_dir = _PS_THEME_DIR_ . $common_path;
// 			//root
// 		} else if (file_exists ( _PS_ROOT_DIR_ . '/' . $common_path )) {
// 			$delivery_pdf_dir = _PS_ROOT_DIR_ . '/' . $common_path;
// 			//kerawen module
// 		} else {
// 			$delivery_pdf_dir = _KERAWEN_DIR_. 'views/templates/front/' . $common_path;
// 		}
		
// 		return $this->context->smarty->fetch($delivery_pdf_dir);
	}
	
	public function hookDisplayCustomerAccount() {
		$this->context->smarty->assign('quote_active', Configuration::get('KERAWEN_QUOTE_ACTIVE'));
		
		require_once (_KERAWEN_DIR_.'controllers/front/giftcard.php');
		$this->context->smarty->assign('giftcard_total', KerawenGiftcardModel::giftCardCount());
		
		$tpl = 'kerawen_customer_btn';
		if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
			$tpl .= '_17';
		}
		return $this->display(__FILE__, 'views/templates/front/' . $tpl . '.tpl');
	}
	

	/*
	 * Utilities
	 */
	
	public function getSmarty() {
		return $this->smarty;
	}
	public function getContext() {
		return $this->context;
	}
	
	public function startFixOverride($fixname) {
		$this->fix_path = $this->local_path.'fixes/'.$fixname.'/';
	}
	public function doneFixOverride() {
		unset($this->fix_path);
	}
	public function getLocalPath() {
		return isset($this->fix_path) ? $this->fix_path : parent::getLocalPath();
	}
}

function KerawenDone() {
	require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
	logSaleOperation();
}
register_shutdown_function('KerawenDone');

