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

require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');

function getConfig($context, $params)
{	
	require_once (_KERAWEN_CLASS_DIR_.'/stats.php');
	require_once (_KERAWEN_CLASS_DIR_.'/data.php');
	require_once (_KERAWEN_CLASS_DIR_.'/log.php');
	
	$id_lang = $context->language->id;
	$db = Db::getInstance();

	// Get employees
	$employees = array();
	$active_employee = array();
	$token = array();

	$langkey = pathinfo(__FILE__, PATHINFO_FILENAME);	
	
	$buf = Employee::getEmployees();
	foreach ($buf as $e)
	{

		$employee = new Employee($e['id_employee']);
		$employees[$employee->id] = array(
			'id' => $e['id_employee'],
			'name' => $e['firstname'].' '.$e['lastname'],
			'firstname' => $e['firstname'],
			'lastname' => $e['lastname'],
			//'shops' => $employee->getAssociatedShops(),
			'shops' => getEmployeeShops($employee),
			'tills' => getEmployeeTills($employee),
			'id_profile' => $employee->id_profile,
			'passwordstatus' => getEmployeePasswordStatus($e['id_employee']),
		);
		
		//Active employee
		if ($context->employee->id == $e['id_employee']) {
			$active_employee = getEmployee($employee->id);
		}
	}

	//Get profiles & permissions
	$profiles = array();
	$profilesclasses = array();
	$tabs = $context->module->getTabsDescription();
	$buf = Profile::getProfiles($id_lang);
	foreach ($buf as $p) {
		$id_profile = $p['id_profile'];
		
		$profiles[$id_profile] = array(
			'id' => $id_profile,
			'name' => $p['name'],
		);
		
		$profilesclasses[$id_profile] = array();
		foreach ($tabs as $app => $t) {
			$id_tab = Tab::getIdFromClassName($t['class']);
			$access = Profile::getProfileAccess($id_profile, $id_tab);
			$profilesclasses[$id_profile][$app] = $access['edit'];
		}
	}

// 	if (Tools::version_compare(_PS_VERSION_, '1.7', '<')) {
// 		$q = "
// 			SELECT * 
// 			FROM " ._DB_PREFIX_. "access a
// 			LEFT JOIN " . _DB_PREFIX_ . "tab t ON t.id_tab = a.id_tab
// 			WHERE module =  'kerawen' 
// 		";
// 		$result = $db->executeS($q);
// 		foreach($result as $item) {
// 			$profilesclasses[$item['id_profile']][str_replace('kerawen', '', strtolower($item['class_name']))] = $item['edit'];
// 		}
		
// 		//if (!isset($profiles[1])) {}
// 		//Set full access to superAdmin even if not set
// 		$result = Db::getInstance()->executeS("SELECT class_name FROM "._DB_PREFIX_."tab WHERE module = 'kerawen'");
// 	    $profilesclasses[1] = array();
// 		foreach($result as $item) {
// 		  $profilesclasses[1][str_replace('kerawen', '', strtolower($item['class_name']))] = '1';
// 		}
// 	}

	// Get shops
	// do not use Shop::getShops(); for it returns only the shops allowed to the employee
	$ss = $db->executeS('
		SELECT s.name, s.id_shop
		FROM '._DB_PREFIX_.'shop s
		WHERE s.active = 1');
	foreach ($ss as &$s)
	{
		$id_shop = $s['id_shop'];
		$shop = new Shop($id_shop);
		$shops[$id_shop] = array(
			'id' => $id_shop,
			'name' => $s['name'],
			'label' => Configuration::get('PS_SHOP_NAME', null, null, $id_shop),
			'addr1' => Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop),
			'addr2' => Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop),
			'postcode' => Configuration::get('PS_SHOP_CODE', null, null, $id_shop),
			'city' => Configuration::get('PS_SHOP_CITY', null, null, $id_shop),
			'country' => Configuration::get('PS_SHOP_COUNTRY', null, null, $id_shop),
			'country_id' => Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop),
			'phone' => Configuration::get('PS_SHOP_PHONE', null, null, $id_shop),
			'url' => $shop->domain.$shop->physical_uri.$shop->virtual_uri,
			'details' => Configuration::get('PS_SHOP_DETAILS', null, null, $id_shop),
			'employees' => array(),
			'warehouses' => Warehouse::getWarehouses(false, $id_shop),
		);
		foreach ($employees as $e)
		{
			if (in_array($id_shop, $e['shops']))
				$shops[$id_shop]['employees'][] = $e['id'];
		}
	}

	require_once(dirname(__FILE__).'/drawer.php');
	$tills = indexArray(getTills(), 'id');
	
	// Get taxes
	$taxes = array();
	$tt = Tax::getTaxes($context->language->id);
	foreach ($tt as &$t)
	{
		$taxes[$t['id_tax']] = array(
			'id' => (int)$t['id_tax'],
			'name' => $t['name'],
			'rate' => (double)$t['rate']
		);
	}

	// Get tax rules
	$taxrules = array();
	$rules = TaxRulesGroup::getTaxRulesGroupsForOptions();
	$rates = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Configuration::get('PS_COUNTRY_DEFAULT'));
	foreach ($rules as $r)
	{
		$id = $r['id_tax_rules_group'];
		$rate = isset($rates[$id]) ? $rates[$id]: 0.0;
		$taxrules[$id] = array(
			'id' => (int)$id,
			'name' => $r['name'],
			'rate' => $rate,
		);
	}

	// Get order states
	$order_states = array();
	require_once (dirname(__FILE__).'/order_state.php');
	$states = getPrestashopOrderStates();
	foreach ($states as $id_os)
	{
		$os = new OrderState($id_os, $context->language->id);
		// Keep order
		$order_states[] = array(
			'id' => (int)$id_os,
			'name' => $os->name,
			'color' => $os->color,
			'pay' => $id_os == Configuration::get('PS_OS_DELIVERED'),
			'cancel' => $id_os == Configuration::get('PS_OS_CANCELED'),
		);
	}

	// Countries
	$countries = array();
	$buf = Country::getCountries($id_lang, true);
	foreach ($buf as $c)
		$countries[$c['id_country']] = array(
			'id' => $c['id_country'],
			'name' => $c['name'],
		);
	
	// Currency
	if (Tools::version_compare(_PS_VERSION_, '1.7', '<')) {
		$curr = array(
			'pref' => $context->currency->prefix,
			'suff' => $context->currency->suffix,
		);
	}
	else {
		$curr = array(
			'pref' => '',
			'suff' => ' '.$context->currency->sign,
		);
	}

	
	//Catolog with or without vat -> get anonymous customer default group
	$anonymousCust = getAnonymousCustomer();
	$id_default_group = $anonymousCust->id_default_group;
	$catalog_price_ti = true;
	
	
	// Customer groups
	$cust_groups = array();
	$buf = Group::getGroups($id_lang);
	foreach ($buf as $g) {
		if ($id_default_group == $g['id_group']) {
			$catalog_price_ti = !$g['price_display_method'];
		}
		$cust_groups[$g['id_group']] = array(
			'id' => $g['id_group'],
			'name' => $g['name'],
			'price_ti' => (!$g['price_display_method']),
		);
	}
	// Categories
	$cats = Category::getCategories( (int)($id_lang), false, false);

	$cat_groups = array();
	foreach ($cats as $cat) {
		$cat_groups[$cat['id_category']] = array(
			'id_category' => $cat['id_category'], 
			'name' => $cat['name'],
		);
	}
	
	//token
	$urlWithToken = (int)Configuration::get('KERAWEN_URL_WITH_TOKEN');
	$adminHome = Tools::version_compare(_PS_VERSION_, '1.6', '<') ? 'AdminHome' : 'AdminDashboard';
	foreach(array($adminHome,'AdminProducts','KerawenRegister','KerawenReport','KerawenLabel','KerawenHome', 'KerawenExport','KerawenCertif','KerawenTills','KerawenMarketing') as $v) {
		$token[$v]['key'] = Tools::getAdminToken($v.(int)(Tab::getIdFromClassName($v)).(int)($context->employee->id));
		$token[$v]['url'] = Dispatcher::getInstance()->createUrl($v, $context->language->id, $urlWithToken ? array('token'=>$token[$v]['key']) : array(), false);
	}

	//Payments
	$payments = Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
	$payments[] = array('id' => _KERAWEN_PM_GIFT_CARD_, 'label' => $context->module->l('Gift card', $langkey), 'payment' => 0, 'refund' => 0);
	
	// Plugins
	require_once(dirname(__FILE__).'/loyalty.php');
	$loyalty = KerawenLoyalty::getPlugin();
	$loyaltyTransform = false;
	$loyaltyCheckBox = false;
	if ($loyalty) {
		$loyaltyTransform = $loyalty->doActions();
		$loyaltyCheckBox = $loyalty->checkBox();
	}
	
	//Languages
	$langs = Language::getLanguages(false, false, false);
	$languages = array();
    $default_lang = 0;
	foreach ($langs as $lang) {
	    
	    if ($lang['language_code'] == 'fr-fr') {
	        $default_lang = (int) $lang['id_lang'];
	    }
	    
		$languages[$lang['id_lang']] = array(
			'id' => $lang['id_lang'],
			'name' => $lang['name'], 
			'active' => $lang['active'], 
			'iso_code' =>$lang['iso_code'],
			'language_code' =>$lang['language_code'],
		);
	}
	
	if (!$default_language) {
	    $first = reset($languages);
	    $default_lang = (int) $first['id'];
	}
	
	// Delivery modes
	$delivery_modes = array(
		array('id' => 0, 'name' => Configuration::get('KERAWEN_LABEL_IN_STORE') ? Configuration::get('KERAWEN_LABEL_IN_STORE') : $context->module->l('In store', $langkey), 'inputs' => null),
		array('id' => 1, 'name' => Configuration::get('KERAWEN_LABEL_TAKEAWAY') ? Configuration::get('KERAWEN_LABEL_TAKEAWAY') : $context->module->l('Takeaway', $langkey), 'inputs' => 'date'),
		array('id' => 2, 'name' => Configuration::get('KERAWEN_LABEL_DELIVERY') ? Configuration::get('KERAWEN_LABEL_DELIVERY')  : $context->module->l('Delivery', $langkey), 'inputs' => 'address carrier'),
	);	
	
	// Carriers
	$carriers = array();
	$buf = $db->executeS('
		SELECT
			c.id_reference AS id,
			c.name AS name
		FROM '._DB_PREFIX_.'carrier c');
	// Keep name from last carrier version
	foreach ($buf as $c) {
		$carriers[$c['id']] = $c;
	}
	
	//delivery modes
	$delivery_modes = array(
		array('id' => 0, 'name' => Configuration::get('KERAWEN_LABEL_IN_STORE') ? Configuration::get('KERAWEN_LABEL_IN_STORE') : $context->module->l('In store', $langkey), 'inputs' => null),
		array('id' => 1, 'name' => Configuration::get('KERAWEN_LABEL_TAKEAWAY') ? Configuration::get('KERAWEN_LABEL_TAKEAWAY') : $context->module->l('Takeaway', $langkey), 'inputs' => 'date'),
		array('id' => 2, 'name' => Configuration::get('KERAWEN_LABEL_DELIVERY') ? Configuration::get('KERAWEN_LABEL_DELIVERY')  : $context->module->l('Delivery', $langkey), 'inputs' => 'address carrier'),
	);

	//labels printers settings
	$log = new stdClass();
	$log->require = array('labelSettings');
	$labels = getLogMvt($log);
	$labels['barcode_type'] = Configuration::get('KERAWEN_LABEL_BARCODE_TYPE');
	$labels['barcode'] = Configuration::get('KERAWEN_LABEL_BARCODE');
	
	$data = array(
		'timezone' => (int)date('Z'),
		'curr' => $curr,
		'shops' => $shops,
		'tills' => $tills,
		'labels' => $labels,
		'employees' => $employees,
		'taxes' => $taxes,
		'taxrules' => $taxrules,
		'default_taxrules' => (int)Configuration::get('KERAWEN_DEFAULT_VAT'),
		'countries' => $countries,
		'order_states' => $order_states,
		'groups' => $cust_groups,
		'default_group' => (int)Configuration::get('KERAWEN_DEFAULT_GROUP'),
		'id_default_group' => $id_default_group,
		'modules' => array(
			'loyalty' => $loyalty != null,
			//'referral' => Module::isInstalled('referralprogram') && Module::isEnabled('referralprogram'),
			'blockwishlist' => Module::isInstalled('blockwishlist') && Module::isEnabled('blockwishlist'),
			'favoriteproducts' => Module::isInstalled('favoriteproducts') && Module::isEnabled('favoriteproducts'),
		),
		'notif' => (int)Configuration::get('KERAWEN_NOTIF_PERIOD'),
		'actions' => array(
			'switchEmployee' => (boolean)Configuration::get('KERAWEN_SWITCH_CASHIER'),
			'switchShop' => (boolean)Configuration::get('KERAWEN_SWITCH_SHOP'),
			'discountCart' => (boolean)Configuration::get('KERAWEN_DISCOUNT_CART'),
			'selectMode' => (boolean)Configuration::get('KERAWEN_SELECT_DELIVERY'),
			'showAmounts' => (boolean)Configuration::get('KERAWEN_SHOW_AMOUNTS'),
			'quotation' => (boolean)Configuration::get('KERAWEN_QUOTE_ACTIVE'),
			'switchEmployeePassword' => (boolean)Configuration::get('KERAWEN_EMPLOYEE_PASSWORD'),
			'excpEmployeePassword' => (boolean)Configuration::get('KERAWEN_EMPLOYEE_PASSWORD_EXCP'),
			'loyaltyTransform' => $loyaltyTransform,
		    'loyaltyCheckBox' => $loyaltyCheckBox,
			'orderQuickEnd' => (boolean)Configuration::get('KERAWEN_ORDER_QUICK_END'),
			'pulse' => (boolean)Configuration::get('KERAWEN_ORDER_PULSE'),
			'play_sound' => (boolean)Configuration::get('KERAWEN_PLAY_SOUND'),
			'scan_not_found' => (boolean)Configuration::get('KERAWEN_SCAN_NOT_FOUND'),
		    'cust_print' => (boolean)Configuration::get('KERAWEN_CUST_PRINT'),    
		),
		'display' => array(
			'catalog_page_size' => Configuration::get('KERAWEN_CATALOG_PAGE_SIZE'),
			'catalog_full_names' => Configuration::get('KERAWEN_CATALOG_FULL_NAMES'),
			'catalog_refs' => Configuration::get('KERAWEN_CATALOG_REFERENCES'),
			'catalog_price_ti' => $catalog_price_ti,
			'cart_full_names' => Configuration::get('KERAWEN_CART_FULL_NAMES'),
			'orders_list_column_shop' => (int) Configuration::get('KERAWEN_ORDERS_LIST_COLUMN_SHOP'),
			'orders_list_column_carrier' => (int) Configuration::get('KERAWEN_ORDERS_LIST_COLUMN_CARRI'),
			'orders_list_column_till' => (int) Configuration::get('KERAWEN_ORDERS_LIST_COLUMN_TILL'),
			'orders_list_column_comp' => (int) Configuration::get('KERAWEN_ORDERS_LIST_COLUMN_COMP'),
		    'orders_list_column_add' => (int) Configuration::get('KERAWEN_ORDERS_LIST_COLUMN_ADD'),
			'orders_list_items_by_page' => ((int) Configuration::get('KERAWEN_ORDERS_LIST_ITEMS_BY_PAG') > 0) ? Configuration::get('KERAWEN_ORDERS_LIST_ITEMS_BY_PAG') : 15,
			'decimal_separator' => Configuration::get('KERAWEN_DECIMAL_SEPARATOR'),
			'offer_period' => Configuration::get('KERAWEN_OFFER_PERIOD'),
			'postcode_required' => (boolean) Configuration::get('KERAWEN_POSTCODE_REQUIRED'),
			'cust_account_addr' => (boolean) Configuration::get('KERAWEN_CUST_ACCOUNT_ADDR'),
		    'address1_required' => (boolean) Configuration::get('KERAWEN_ADDRESS1_REQUIRED'),
		    'city_required' => (boolean) Configuration::get('KERAWEN_CITY_REQUIRED'),
		    'phone_required' => (boolean) Configuration::get('KERAWEN_PHONE_REQUIRED'),
		    'mobile_required' => (boolean) Configuration::get('KERAWEN_MOBILE_REQUIRED'),
		),
		'payments' => $payments,
		'custdisplay' => array(
			'cpl' => Configuration::get('KERAWEN_DISPLAY_CPL'),
			'msg_start' => Configuration::get('KERAWEN_DISPLAY_MSG_START'),
			'msg_end' => Configuration::get('KERAWEN_DISPLAY_MSG_END'),
		),
		'ticket' => getReceiptSettings(),
		'gather_measures' => (boolean)Configuration::get('KERAWEN_GATHER_MEASURES'),
		'profilesclasses' => $profilesclasses,
		'profiles' => $profiles,
		'employee' => $active_employee,
		'categories' => $cat_groups,
		'params' => array(
			'advanced_stock' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
			'mvt_stock' => (int)Tools::version_compare(_PS_VERSION_, '1.7', '>=')
		),
		'token' => $token,
		'carriers' => $carriers,
		'languages' => $languages,
	    'default_lang' => $default_lang,
		'delivery_modes' => $delivery_modes,
	);


	//Define specific data by app
	if (isset($params->appli)) {
		
		if ($params->appli == 'tills') {
			$data['node'] = array(
				'screen' =>_KERAWEN_SERVER_NODE_,
				'upload' =>_KERAWEN_SERVER_NODE_ . '/upload',
				'key' => Configuration::get('KERAWEN_LICENCE_KEY'),
				'maxUploadSize' => (4 * 1000 * 1024),
			);
		}

	}

	return $data;
	
}


function selectPeriod() {
	$db = Db::getInstance();
	$refDate = $db->getValue('SELECT DATE_FORMAT(NOW(), "%Y/%m/%d")');
	return array(
		'from' => $refDate,
		'to' => $refDate,
	);
}


function getReceiptSettings() {
	
	$context = Context::getContext();
	$id_lang = $context->language->id;
	
	return array(
		'cpl' => (int)Configuration::get('KERAWEN_TICKET_CPL'),
		'image' => Configuration::get('KERAWEN_TICKET_IMAGE') ? _PS_IMG_ . Configuration::get('KERAWEN_TICKET_IMAGE') : "",
		'message' => Configuration::get('KERAWEN_TICKET_MESSAGE'),
		'gift_card_message' => Configuration::get('KERAWEN_GIFT_CARD_TICKET_MESSAGE'),
		'shop_name' => (boolean)Configuration::get('KERAWEN_TICKET_SHOP_NAME'),
		'shop_address' => (boolean)Configuration::get('KERAWEN_TICKET_SHOP_ADDRESS'),
		'shop_url' => (boolean)Configuration::get('KERAWEN_TICKET_SHOP_URL'),
		'shop_details' => (boolean)Configuration::get('KERAWEN_TICKET_SHOP_DETAILS'),
		'comments' => (boolean)Configuration::get('KERAWEN_TICKET_COMMENTS'),
		'prod_note' => (boolean)Configuration::get('KERAWEN_TICKET_PRODUCT_NOTE'),
		'full_names' => (boolean)Configuration::get('KERAWEN_TICKET_FULL_NAMES'),
		'taxes' => (boolean)Configuration::get('KERAWEN_TICKET_TAXES'),
		'detail_taxes' => (boolean)Configuration::get('KERAWEN_TICKET_DETAIL_TAXES'),
		'mode' => (boolean)Configuration::get('KERAWEN_TICKET_MODE'),
		'customer' => (boolean)Configuration::get('KERAWEN_TICKET_CUSTOMER'),
		'loyalty' => (int)Configuration::get('KERAWEN_TICKET_LOYALTY'),
		'barcode' => (boolean)Configuration::get('KERAWEN_TICKET_BARCODE'),
		'order_num' => (boolean)Configuration::get('KERAWEN_TICKET_ORDER_NUMBER'),
		'detail_discount' => (boolean)Configuration::get('KERAWEN_TICKET_DISCOUNT'),
		'reference' => (boolean)Configuration::get('KERAWEN_TICKET_REF'),	
		'shop_email' => (boolean)Configuration::get('KERAWEN_TICKET_SHOP_EMAIL'),
		'shop_country' => (boolean)Configuration::get('KERAWEN_TICKET_SHOP_COUNTRY'),
		'employee_name' => (boolean)Configuration::get('KERAWEN_TICKET_EMPLOYEE_NAME'),
		'invoice_tax' => (boolean)Configuration::get('KERAWEN_INVOICE_TAX'),
		'print_open_close' => (boolean)Configuration::get('KERAWEN_TICKET_PRINT_OPEN_CLOSE'),
		'print_auto' => (boolean)Configuration::get('KERAWEN_TICKET_PRINT_AUTO'),
		'invoice_num_order' => (boolean) Configuration::get('KERAWEN_INVOICE_NUM_ORDER'),
		'invoice_num_cart' => (boolean) Configuration::get('KERAWEN_INVOICE_NUM_CART'),	
		'invoice_disp_tax' => (boolean) Configuration::get('KERAWEN_INVOICE_DISP_TAX'),
		'invoice_disp_shipping' => (boolean) Configuration::get('KERAWEN_INVOICE_DISP_SHIPPING'),
		'invoice_disp_unit_vat' => (boolean) Configuration::get('KERAWEN_INVOICE_DISP_UNIT_VAT'),
		'invoice_disp_total_vat' => (boolean) Configuration::get('KERAWEN_INVOICE_DISP_TOTAL_VAT'),	
		'invoice_disp_barcode' => (boolean) Configuration::get('KERAWEN_INVOICE_DISP_BARCODE'),
		'invoice_ref_col' => (boolean) Configuration::get('KERAWEN_INVOICE_REF_COL'),
		'invoice_free_text' => Configuration::get('KERAWEN_INVOICE_FREE_TEXT', $id_lang) ? Configuration::get('KERAWEN_INVOICE_FREE_TEXT', $id_lang) : Configuration::get('KERAWEN_INVOICE_FREE_TEXT'),
		'print_min_amount' => (int) Configuration::get('KERAWEN_TICKET_PRINT_MIN_AMOUNT'),
		'phone' => (boolean) Configuration::get('KERAWEN_TICKET_CUSTOMER_PHONE'),
		'header_date' => (boolean) Configuration::get('KERAWEN_INVOICE_HEADER_DATE'),
	    'msg_discount' => Configuration::get('KERAWEN_TICKET_MSG_DISCOUNT'),
	    'cust_header_msg' => Configuration::get('KERAWEN_CUST_HEADER_MESSAGE'),
	    'cust_footer_msg' => Configuration::get('KERAWEN_CUST_FOOTER_MESSAGE'),
	);
}


function selectShops($id_shop = 0) {

	$db = Db::getInstance();
	
	$shops = array();
	$ss = $db->executeS('
		SELECT s.name, s.id_shop
		FROM '._DB_PREFIX_.'shop s
		WHERE 0 = ' . (int) $id_shop . ' OR s.id_shop = ' . (int) $id_shop
	);
	
	foreach ($ss as $s)
	{
		$id_shop = $s['id_shop'];
		
		$tempArray = array(
			'id' => $id_shop,
			'name' => $s['name'],
			'label' => Configuration::get('PS_SHOP_NAME', null, null, $id_shop),
			'email' => Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
			'addr1' => Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop),
			'addr2' => Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop),
			'postcode' => Configuration::get('PS_SHOP_CODE', null, null, $id_shop),
			'city' => Configuration::get('PS_SHOP_CITY', null, null, $id_shop),
			'country' => Configuration::get('PS_SHOP_COUNTRY', null, null, $id_shop),
			'country_id' => Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop),
			'phone' => Configuration::get('PS_SHOP_PHONE', null, null, $id_shop),
			'details' => Configuration::get('PS_SHOP_DETAILS', null, null, $id_shop),

			'web' => Configuration::get('KERAWEN_SHOP_URL', null, null, $id_shop),
			'siret' => Configuration::get('KERAWEN_SHOP_SIRET', null, null, $id_shop),
			'naf' => Configuration::get('KERAWEN_SHOP_NAF', null, null, $id_shop),
			'tva_intra' => Configuration::get('KERAWEN_SHOP_TVA_INTRA', null, null, $id_shop),
		);
		
		array_walk($tempArray, function(&$val, $key) { $val = ($val === false) ? '' : $val; });
		
		$tempArray['valid'] = checkShop($tempArray);
		
		$shops[$id_shop] = $tempArray;
		
	}
	
	return $shops;

}


function checkShop($tempArray) {
	
	$required_fields = array(
		'email',
		'addr1',
		'postcode',
		'city',
		'country',
		'phone',
		'siret',
		'naf',
		'tva_intra',
	);
	
	$flag = true;
	foreach($required_fields as $v) {
		if (empty($tempArray[$v])) {
			$flag = false;
		}	
	}
	return $flag;
}


function saveShop($params) {
	
	$db = Db::getInstance();
	
	$id_shop = (int) $params->id_shop;
	
	//'PS_SHOP_NAME' => 'PS_SHOP_NAME',
	
	$shop_fields = array(
		'PS_SHOP_EMAIL' => 'PS_SHOP_EMAIL',
		'PS_SHOP_ADDR1' => 'PS_SHOP_ADDR1',
		'PS_SHOP_ADDR2' => 'PS_SHOP_ADDR2',
		'PS_SHOP_CODE' => 'PS_SHOP_CODE',
		'PS_SHOP_CITY' => 'PS_SHOP_CITY',
		'PS_SHOP_COUNTRY_ID' => 'PS_SHOP_COUNTRY_ID',
		'PS_SHOP_PHONE' => 'PS_SHOP_PHONE',
		'KERAWEN_SHOP_SIRET' => 'KERAWEN_SHOP_SIRET',
		'KERAWEN_SHOP_NAF' => 'KERAWEN_SHOP_NAF',
		'KERAWEN_SHOP_TVA_INTRA' => 'KERAWEN_SHOP_TVA_INTRA',
		'KERAWEN_SHOP_URL' => 'KERAWEN_SHOP_URL',
	);

	$country = new Country((int)$params->data->PS_SHOP_COUNTRY_ID);
	if ($country) {
		if (is_array($country->name)) {
			$shop_data['PS_SHOP_COUNTRY'] = reset($country->name);
			Configuration::updateValue('PS_SHOP_COUNTRY', $shop_data['PS_SHOP_COUNTRY'], false, false, $id_shop);
		}
	}

	$shop_data = array();
	$shop_data['id_shop'] = $id_shop;
	
	foreach ($shop_fields as $name => $field) {
		Configuration::updateValue($name, $params->data->{$field}, false, false, $id_shop);
		$shop_data[$name] = pSQL($params->data->{$field});
	}
		
	$db->update('shop', array('name' => pSQL($params->data->name)), 'id_shop = '.pSQL($id_shop));
	
	//TODO: save in new table
	

}




/* TO REFACTOR */
function startRegister($context, $params, &$response)
{
	$db = Db::getInstance();
	
	// Cart to restore
	$id_cart = null;
	if (isset($params->cart)) {
		$restore = $params->cart;
		if (isset($restore->id) && isset($restore->version)) {
			// Check cart is still available with right version
			if ($db->getValue('
				SELECT c.id_cart FROM '._DB_PREFIX_.'cart c
				JOIN '._DB_PREFIX_.'cart_kerawen ck ON ck.id_cart = c.id_cart
				WHERE c.id_cart = '.pSQL($restore->id).'
				AND ck.version = '.pSQL($restore->version))) {
				$id_cart = $restore->id;
			}
		}
	}
	
	require_once (dirname(__FILE__).'/cart.php');
	$params->id_next = $id_cart;
	$params->id_cust = null;
	$params->id_cart = null;
	$params->suspend = false;
	resetCart($context, $params, $response);

	require_once (dirname(__FILE__).'/notif.php');
	$response->addResult('notif', getNotif($context));
}

function stopRegister($context)
{
	$context->employee->logout();
}

function searchCode($code, $id_lang)
{
	$db = Db::getInstance();
	$res = array(
		'prods' => array(),
	);

	//Work only with single shop
	$prefix = Configuration::get('PS_INVOICE_PREFIX', $id_lang);
	$is_invoice = false;

	if ( (substr($code, 0, strlen($prefix)) == $prefix) && (!empty($prefix)) ) {
		$code = (int) substr($code, strlen($prefix));
		$is_invoice = $code > 0;
	} 

	if (Tools::strlen($code) > 0)
	{
		$found = false;

		if (! $found)
		{
			// Loyalty number
			$id_cust = $db->getValue('
				SELECT id_customer
				FROM '._DB_PREFIX_.'customer_kerawen
				WHERE loyalty_number = "'.pSQL($code).'"');
			if ($id_cust)
			{
				$found = true;
				$res ['id_cust'] = $id_cust;
			}
		}
		
		if (! $found)
		{
			if (is_numeric($code) && class_exists('PrestaShopCollection') && $code != 0) {
				// Order id ?
				$field = $is_invoice ? "invoice_number" : "id_order";
				$orderSearch = new PrestaShopCollection('Order');
				$orderSearch->where($field, '=', $code);
				$orders = $orderSearch->getResults();
				
				//TODO: get order from kerawen_525_invoice
				
			} else {
				// Order reference ?
				$orders = Order::getByReference($code)->getResults();
			}
			if (count($orders) > 0)
			{
				$found = true;
				$id_order = null;
				foreach ($orders as &$order)
				{
					// EVOL multiple order case
					// $ids[] = $o->id;
					$id_order = $order->id;
				}
				$res['id_order'] = $id_order;
			}
		}
		
		if (!$found)
		{
			if (Validate::isEan13($code))
			{
				$prefix = Configuration::get('KERAWEN_SCALE_PREFIX');
				$len = Tools::strlen($prefix);
				if ($len)
				{
					$start = 0;
					if (Tools::substr($code, $start, $len) == $prefix)
					{
						$start += $len;
						$len = Configuration::get('KERAWEN_SCALE_PRODUCT_LENGTH');
						$ref = Tools::substr($code, $start, $len);
						$buf = $db->getRow('
							SELECT id_product, id_code
							FROM '._DB_PREFIX_.'product_wm_code_kerawen
							WHERE code = '.pSQL($ref));
						if ($buf)
						{
							$start += $len;
							$len = Configuration::get('KERAWEN_SCALE_PRICE_LENGTH');
							$price = (float)Tools::substr($code, $start, $len);
							$mult = (float)Configuration::get('KERAWEN_SCALE_PRICE_MULTIPLIER');
							
							$found = true;
							$res['prods'][] = array(
								'id_prod' => $buf['id_product'],
								'id_code' => $buf['id_code'],
								'price' => $price * $mult,
							);
						}
					}
				}
			}
		}
		if (!$found)
		{
			require_once (dirname(__FILE__).'/catalog.php');

			// Search for products
			if (Configuration::get('KERAWEN_LABEL_BARCODE') == 'ean') {
				$filter = '
					ean13 LIKE "%'.pSQL(substr($code, 0, Configuration::get('KERAWEN_EAN13_SEARCH_LENGTH'))).'%"
					OR upc LIKE "%'.pSQL(substr($code, 0, 11)).'%"';
			} else {
				$filter = ' reference = "'.pSQL($code).'" ';
			}
			
			$buf = $db->executeS('
				SELECT id_product
				FROM '._DB_PREFIX_.'product
				WHERE '.$filter);
			if (count($buf)) $found = true;
			
			foreach($buf as $p) {
				$id_prod = $p['id_product'];
				$detail = detailProduct(new Product($id_prod, true, $id_lang), $id_lang, true);
				if ($detail) {
					$res['prods'][] = array(
						'id_prod' => $id_prod,
						'detail' => $detail,
					);
				}
			}
					
			$buf = $db->executeS('
				SELECT id_product, id_product_attribute
				FROM '._DB_PREFIX_.'product_attribute
				WHERE '.$filter);
			if (count($buf)) $found = true;
			
			foreach($buf as $pa) {
				$id_prod = $pa['id_product'];
				$detail = detailProduct(new Product($id_prod, true, $id_lang), $id_lang, true);
				if ($detail) {
					$res['prods'][] = array(
						'id_prod' => $id_prod,
						'id_attr' => $pa['id_product_attribute'],
						'detail' => $detail,
					);
				}
			}
		}
		if (!$found)
		{
			// Reduction code ?
			$id = CartRule::getIdByCode($code);
			if ($id)
			{
				$found = true;
				$rule = new CartRule($id);
				if ($rule->quantity
					&& $rule->active
					&& time() >= strtotime($rule->date_from)
					&& time() <= strtotime($rule->date_to)) {
					$res['id_reduc'] = $id;
				}
				else
					$res ['ERROR'] = 'Cart rule no more available';
			}
		}

		// Not found: determine code type
		if (! $found)
		{
			if (Validate::isEan13($code))
			{
				$found = true;
				$res['type'] = 'product';
			}
		}
		if (! $found)
		{
			if (preg_match('/CLI[0-9]+/', $code))
			{
				$found = true;
				$res['type'] = 'customer';
			}
		}
	}
	return $res;
}

/* Avoid context employee usage which may have been overriden */
function getAdminLink($controller, $params = null)
{
	$context = Context::getContext();
	if (!$params) $params = array();
	$params['token'] = Tools::getAdminToken($controller.(int)Tab::getIdFromClassName($controller).(int)$context->cookie->id_employee);
	return Dispatcher::getInstance()->createUrl($controller, $context->language->id, $params, false);
}
