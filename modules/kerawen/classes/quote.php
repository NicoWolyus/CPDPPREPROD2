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

require_once (_KERAWEN_API_DIR_.'/constants.php');
require_once (_KERAWEN_TOOLS_DIR_.'/utils.php');
require_once (_KERAWEN_CLASS_DIR_.'/cart.php');




function getQuoteNumberFormat() {
  return ' LPAD(cart_kerawen.quote_number, 10, "#Q000000000") ';
}

function getQuoteActiveCondition() {
  return ' IF(date(cart_kerawen.quote_expiry) < date(NOW()), 0, 1) ';
}

function getQuoteTitle() {
  return ' IF(cart_kerawen.quote_title = "" OR cart_kerawen.quote_title IS NULL, ' . getQuoteNumberFormat() . ', cart_kerawen.quote_title) ';
}


function validateQuote($request) {
	
	$db = Db::getInstance();
	//Required for PS 1.5.6
	Product::flushPriceCache();

	$context = Context::getContext();
		
	$id_lang = $context->language->id;
	$id_customer = $context->customer->id;
	$id_shop = $context->shop->id;
	
	$id_currency = $context->currency->id;
	$id_country = $context->country->id;	

	$id_group = $context->group->id;
	
	
	$cart = new Cart($request->params->id_cart);
	$cart->id_address_invoice = $request->params->id_addr;
	$cart->id_lang = ($request->params->id_lang == '') ? (int) $context->language->id : (int) $request->params->id_lang;
	$cart->save();
	
	$array_params = array();
	$array_params['quote'] = 1;
		
	$id_cart_kerawen = (int)$db->getValue('
		SELECT id_cart FROM '._DB_PREFIX_.'cart_kerawen
		WHERE quote = 1
		AND id_cart = '.pSQL($request->params->id_cart));
	if ($id_cart_kerawen === 0) { 
		//get incremental id
		$counter = (int) Configuration::get('KERAWEN_QUOTE_COUNTER', 0, 0, 0) + 1;
		Configuration::updateValue('KERAWEN_QUOTE_COUNTER', $counter, false, 0, 0);
		$array_params['quote_number'] = $counter;
	}

	//always update expiry date on validate ?
	$interval = (int) Configuration::get('KERAWEN_QUOTE_DURATION', 0, 0, 0);
	$array_params['quote_expiry'] = $db->getValue('SELECT DATE_ADD(NOW(), INTERVAL ' . $interval . ' DAY)');
	
	//Set cart as quote
	$db->update('cart_kerawen', $array_params, 'id_cart = ' . pSQL($request->params->id_cart));

	//Check secure_key for front-end quote conversion
	$secure_key = $db->getValue('SELECT secure_key FROM '._DB_PREFIX_.'customer WHERE id_customer = ' . pSQL($id_customer) );
	$db->update('cart', array('secure_key' => $secure_key), 'id_cart = ' . pSQL($request->params->id_cart));

	//INFINIVIN
	if ((int) Configuration::get('KERAWEN_QUOTE_ABS_PRICE')) {
	
		//(Re)define specific prices
		$prods = $cart->getProducts();
		
		foreach ($prods as $p) {
	
			$ecotax = 0;
			if (isset($p['ecotax'])) {
				$ecotax = $p['ecotax'];
			}		
	
			// ----- Fixe BUG module VAT Margin when cart is excl. TAX -----
			$reduction_tax = 1;
			//Cart is excl. VAT
			if (isset($p['price_with_reduction']) && isset($p['price_with_reduction_without_tax'])) {
				if ( $p['price_with_reduction'] == $p['price_with_reduction_without_tax'] && $p['rate'] > 0 ) {
					// + check if product is VAT Margin
					$prodrow = $db->getRow('SELECT * FROM '._DB_PREFIX_.'product WHERE id_product = ' . pSQL($p['id_product']) );
					if ($prodrow) {
						if (isset($prodrow['vat_margin'])) {
							if ($prodrow['vat_margin'] == 1) {
								$reduction_tax = 0;
							}
						}
					}
				}
			}
	
			//specific price cart
			$sp = SpecificPrice::getIdsByProductId($p['id_product'], $p['id_product_attribute'], $request->params->id_cart);
			if (count($sp)) {
				
				$priceCatalog_vat_excl = -1;
				
				/* 
				//specific price catalog - shouldn't be possible
				$specific = SpecificPrice::getSpecificPrice($p['id_product'], $id_shop, $id_currency, $id_country, $id_group, 1, $p['id_product_attribute'], $id_customer);
				if (isset($specific['price'])) {
					//replace relative price to absolute price
					if ($specific['price'] == -1.00) {					
						$priceCatalog_vat_excl = Product::getPriceStatic($p['id_product'], false, $p['id_product_attribute'], 6, null, false, false, 1, false, $id_customer);
					}
				}	
				*/
	
			
				//specific price cart
				$specific_cart = SpecificPrice::getSpecificPrice($p['id_product'], $id_shop, $id_currency, $id_country, $id_group, 1, $p['id_product_attribute'], $id_customer, $request->params->id_cart);
				if (isset($specific_cart['price'])) {
	
					//replace relative price to absolute price				
					if ($specific_cart['price'] == -1.00) {
						$priceCatalog_vat_excl = Product::getPriceStatic($p['id_product'], false, $p['id_product_attribute'], 6, null, false, false, 1, false, $id_customer);
					} else {
						$priceCatalog_vat_excl = -1;
					}
					
				}
	
				if ($priceCatalog_vat_excl != -1) {
					$priceCatalog_vat_excl -= $ecotax;
					$id_spec = $sp[0]['id_specific_price'];
					$price = new SpecificPrice($id_spec);
					$price->price = round($priceCatalog_vat_excl, 6);
					$price->save();
				}			
	
			} else {
	
				$reduction = 0;
				$reduction_type = 'percentage';
				
				$specific_price_output = null;
				$price_vat_excl = Product::getPriceStatic(
					$p['id_product'], 
					false, 
					$p['id_product_attribute'], 
					6, 
					null, 
					false, 
					false, 
					1, 
					false, 
					$id_customer,
						
					null,
					null,
					$specific_price_output,
					true,
					false,
					null,
					true
				);
				
	
				$price_vat_excl -= $ecotax;
				//$reduction_tax = 1;
				
				//discount catalog (not defined on cart)
				$qty = 1;
				$specific = SpecificPrice::getSpecificPrice($p['id_product'], $id_shop, $id_currency, $id_country, $id_group, $p['cart_quantity'], $p['id_product_attribute'], $id_customer, 0, $p['cart_quantity']);
				if (isset($specific['price'])) {
					$reduction = $specific['reduction'];
					$reduction_type = $specific['reduction_type'];
					$reduction_tax = $specific['reduction_tax'];
					$qty = $p['cart_quantity'];
				}
	
				$price = new SpecificPrice();
				$price->id_product = $p['id_product'];
				$price->id_specific_price_rule = 0;
				$price->id_cart = $request->params->id_cart;
				$price->id_product_attribute = $p['id_product_attribute'];
				$price->id_shop = $context->shop->id; //important!
				$price->id_shop_group = 0;
				$price->id_currency = 0;
				$price->id_country = 0;
				$price->id_group = 0;
				$price->id_customer = 0;
				//Fix bug PS version
				$price->price = round($price_vat_excl, 6);
				//$price->from_quantity = 1;
				$price->from_quantity = $qty;
				$price->reduction = round($reduction, 6);
				$price->reduction_tax = $reduction_tax;
				$price->reduction_type = $reduction_type;
				$price->from = '0000-00-00 00:00:00';
				$price->to = '0000-00-00 00:00:00';
		        $price->save();
			}
		}

	}
	
}


function deleteQuote($response) {
	require_once ( _KERAWEN_DIR_ . 'classes/cart.php' );
	$cart = new Cart($response->params->id_cart);
	deleteCart($cart);
}


function getQuotes($id_cust = 0) {

	$context = Context::getContext();
	$permissions = $context->permissions;
	
	$condition = array();
	$conditionPermission = array();
	$conditionPermissionWhere = false;

	if (isset($permissions->quoteDisplay)) {
		if (!empty($permissions->quoteDisplay->id_shop)) {
			$conditionPermission['id_shop'] = 'cart.id_shop IN (' . $permissions->quoteDisplay->id_shop . ')';
		} elseif (!empty($permissions->quoteDisplay->id_employee)) {
			$conditionPermission['id_employee'] = 'cart_kerawen.id_employee = ' . $permissions->quoteDisplay->id_employee;
		} else {
			$conditionPermission['false'] = 'FALSE';
		}
		$condition = array_merge($condition, array_values($conditionPermission));
	}
	
	$condition = count($condition) ? implode(' AND ', $condition) : null;
	$conditionPermissionWhere = count($conditionPermission) ? implode(' AND ', array_values($conditionPermission)) : null;


  	$q = '
		SELECT 
		  cart_kerawen.id_cart AS id, 
		  cart_kerawen.total, 
		  cart_kerawen.quote_expiry,
  		  cart.id_shop,
		  cart.id_lang,
		  ' . getQuoteTitle() . ' AS quote_title,
		  ' . getQuoteActiveCondition() . ' AS quote_active,
		  CONCAT(employee.firstname, " ", employee.lastname) AS quote_employee,
		  employee.id_employee AS id_empl,
		  shop.name AS quote_shop,		  		
		  IFNULL(shop_url.id_shop, false) AS shop_url_id_shop,
		  CONCAT(cust.lastname, " ", cust.firstname) AS quote_customer,
		  IFNULL(orders.id_order, 0) AS id_order
		FROM '._DB_PREFIX_.'cart cart
		INNER JOIN '._DB_PREFIX_.'cart_kerawen cart_kerawen ON cart.id_cart = cart_kerawen.id_cart
		INNER JOIN '._DB_PREFIX_.'customer cust ON cart.id_customer = cust.id_customer
		LEFT JOIN '._DB_PREFIX_.'employee employee ON cart_kerawen.id_employee = employee.id_employee
		LEFT JOIN '._DB_PREFIX_.'shop shop ON cart.id_shop = shop.id_shop
		LEFT JOIN '._DB_PREFIX_.'shop_url shop_url ON cart.id_shop = shop_url.id_shop
		LEFT JOIN '._DB_PREFIX_.'orders orders ON cart.id_cart = orders.id_cart
		WHERE cart_kerawen.quote > 0 AND (cart.id_customer = ' . (int) $id_cust . ' OR 0 = ' . (int) $id_cust . ') 
		' . ($conditionPermissionWhere ? ' AND ' . $conditionPermissionWhere : '') . '
		ORDER BY cart_kerawen.id_cart DESC
	';
  	
  	if (!$id_cust) {
  		$q .= ' LIMIT 50 ';
  	}
  	
  	//echo $q;
  	
	$items = Db::getInstance()->executeS($q);
	
	//$link = new Link;
	foreach ($items as $k => $item) {
		//$items[$k]['quote_pdf'] = $link->getModuleLink('kerawen', 'quotenext', array('id_cart' => $item['id'], 'action'=>'download'), null, null, 1);
		$items[$k]['quote_pdf'] = getQuoteDownloadUri($item['id'], $item['shop_url_id_shop'], $item['id_lang']);	
	}

	return $items;
}


function getQuoteDownloadUri($id, $id_shop = false, $id_lang = false) {
	
	$url = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?id_cart=' . (int) $id . '&action=download&fc=module&module=kerawen&controller=quotenext';
	
	if ($id_shop) {
		$url .= '&id_shop=' . $id_shop;
	}

	if ($id_lang) {
		$url .= '&id_lang=' . $id_lang;
	}
	
	return $url;
}


function getQuoteInfo($id_cart, $id_customer, $isEmployeeLogin) {

	$q = '
		SELECT cart.id_cart, cart.id_currency, cart_kerawen.count, cart_kerawen.total, cart.id_shop, cart.id_lang, cart_kerawen.id_employee, cart_kerawen.quote_expiry,
		' . getQuoteTitle() . ' AS quote_title,
		CONCAT( ' . getQuoteNumberFormat() . ', "-", DATE_FORMAT(NOW(),"%Y%m%d%H%I%S") ) AS quote_file_name,
		' . getQuoteActiveCondition() . ' AS quote_active,
		CONCAT(customer.firstname, " ", customer.lastname) AS cust_name, customer.email AS cust_email, customer.id_customer, customer.secure_key,
		employee.email AS empl_email
		FROM ' . _DB_PREFIX_ . 'cart cart 
		INNER JOIN ' . _DB_PREFIX_ . 'cart_kerawen cart_kerawen ON cart.id_cart = cart_kerawen.id_cart
		LEFT JOIN ' . _DB_PREFIX_ . 'customer customer ON cart.id_customer = customer.id_customer
		LEFT JOIN ' . _DB_PREFIX_ . 'employee employee ON cart_kerawen.id_employee = employee.id_employee
		WHERE cart_kerawen.quote = 1
		AND cart.id_cart = ' . (int) $id_cart . ' ';
		
	if (!$isEmployeeLogin) {
		$q .= ' AND cart.id_customer = ' . (int) $id_customer;
	}
	
	return Db::getInstance()->getRow($q);
	
}


function getQuote($request)  {
    
	$cart = new Cart($request->params->id_cart);
	$request->addResult('quote', cartAsArray($cart));

}


function getQuotePdf($quote, $action) {

	$tplStr = 'QuotePdf';
	//require_once(_KERAWEN_DIR_ . 'tools/HTMLTemplate' . $tplStr . '.php');

	$_LANGADM = 2;
	
	require_once(_KERAWEN_CLASS_DIR_ . '/HTMLTemplate' . $tplStr . '.php');
	$pdf = new PDF(getQuoteData((int) $quote['id_cart'] ), $tplStr, Context::getContext()->smarty);
	$attachment = $pdf->render($action == 'download');


	if ($action == 'send' && $attachment && $quote['cust_email'] != '') {

		$link = new Link;
		//$shop_url = Context::getContext()->link->getPageLink('index', true, $quote['id_lang'], null, false, $quote['id_shop']);
		$shop_url = $link->getPageLink('index', true, $quote['id_lang'], null, false, $quote['id_shop']);
		$shop_name = Configuration::get('PS_SHOP_NAME', null, null, $quote['id_shop']);		
		$custom_quote_url = getKerawenLink('quotenext', array('id_customer' => $quote['id_customer'],'secure_key' => $quote['secure_key'],'id_cart' => $quote['id_cart'],'action' => 'login'));

		$logo = '';
		if (Configuration::get('PS_LOGO_MAIL') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $quote['id_shop']))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_MAIL', null, null, $quote['id_shop']);
		} else {
			if (file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $quote['id_shop']))) {
				$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $quote['id_shop']);
			}
		}
		
		$shop_address = 
			Configuration::get('PS_SHOP_NAME', null, null, $quote['id_shop']) . ' - ' .
			Configuration::get('PS_SHOP_ADDR1', null, null, $quote['id_shop']) . ' - ' .
			Configuration::get('PS_SHOP_ADDR2', null, null, $quote['id_shop']) . ' - ' .
			Configuration::get('PS_SHOP_CODE', null, null, $quote['id_shop']) . ' ' . Configuration::get('PS_SHOP_CITY', null, null, $quote['id_shop']) . ' - ' .
			Country::getNameById(Configuration::get('PS_LANG_DEFAULT', null, null, $quote['id_shop']), Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $quote['id_shop']));


		$dataMail = array(
			'{shop_name}' => $shop_name,
			'{shop_url}' => $shop_url,
			'{shop_email}' => Configuration::get('PS_SHOP_EMAIL', null, null, $quote['id_shop']),
			'{shop_logo}' => $logo,
			'{name_to}' => $quote['cust_name'],
			'{shop_link}' => '<a href="' . $custom_quote_url . '">' . $shop_name . '</a>',
			'{quote_title}' => $quote['quote_title'],
			'{amount}' => Tools::displayPrice((float) $quote['total'], (int) $quote['id_currency']),
			'{expiry_date}' => Tools::displayDate($quote['quote_expiry']),
			'{shop_address}' => $shop_address,
		);

		Mail::Send(
			$quote['id_lang'], 
			'quote', 
			$shop_name . ' - ' . $quote['quote_title'], 
			$dataMail,
			$quote['cust_email'], 
			NULL, 
			Configuration::get('PS_SHOP_EMAIL', null, null, $quote['id_shop']), 
			Configuration::get('PS_SHOP_NAME', null, null, $quote['id_shop']), 
			array(
				'content' => $attachment,
				'mime' => 'application/pdf',
				'name' => $quote['quote_file_name']
			),
			NULL, 
			_KERAWEN_DIR_ . 'mails/'
		);
		
		if ($quote['empl_email'] != '') {
			Mail::Send(
				$quote['id_lang'],
				'quote',
				$shop_name . ' - ' . $quote['quote_title'],
				$dataMail,
				$quote['empl_email'],
				NULL,
				Configuration::get('PS_SHOP_EMAIL', null, null, $quote['id_shop']),
				Configuration::get('PS_SHOP_NAME', null, null, $quote['id_shop']),
				array(
					'content' => $attachment,
					'mime' => 'application/pdf',
					'name' => $quote['quote_file_name']
				),
				NULL,
				_KERAWEN_DIR_ . 'mails/'
			);
		}
		
		
	}

}


function getQuoteData($id_cart) {

	$object = new stdClass();
	$object->id_cart = $id_cart;
	
	return $object;

}
