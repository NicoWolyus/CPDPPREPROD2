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

function registerVisit($id_cust, $id_prod)
{
	if ($id_cust && $id_prod)
		Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'customer_seen_kerawen`
				(`id_customer`, `id_product`, `counter`, `lastvisit`)
				VALUES ('.pSQL($id_cust).', '.pSQL($id_prod).', 1, CURRENT_TIMESTAMP)
			ON DUPLICATE KEY UPDATE `counter` = `counter` + 1, `lastvisit` = CURRENT_TIMESTAMP');
}



function searchCustomer($params)
{
	$where = array();
	//Exclude anonymous customer from serach
	$id = (int) Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	$where[] = 'id != ' . $id; 
	foreach (preg_split('/[\s]+/', str_replace('"', '\\"', $params->term)) as $str)
		if (Tools::strlen($str))
			$where[] = 'full LIKE "%'.$str.'%"';
	return Db::getInstance()->executeS('
		SELECT DISTINCT id, info, firstname, lastname
		FROM '._DB_PREFIX_.'customer_search_kerawen
		WHERE '.implode(' AND ', $where));
}

function selectCustomer($context, $params, &$response)
{
	$db = Db::getInstance();
	
	$id_cust = $params->id_cust;
	$id_cart = $params->id_cart;
	$id_lang = $context->language->id;
	$moreDetail = isset($params->moreDetail) ? $params->moreDetail : '';

	// Update context
	require_once (dirname(__FILE__).'/data.php');
	$context = Context::getContext();
	$context->customer = $id_cust ? new Customer($id_cust) : getAnonymousCustomer();
	$context->customer->logged = 1;
	$context->group = new Group($context->customer->id_default_group);
	
	$cust = getCustomer($id_cust, $id_lang, $moreDetail);	
	$response->addResult('cust', $cust);

	require_once (dirname(__FILE__).'/cartrules.php');
	$response->addResult('rules', getCartRules($id_cust, $id_lang));
	
	// Attach customer to cart if exists
	if ($id_cart)
	{
		require_once (dirname(__FILE__).'/cart.php');
		
		$secure_key = '';
		if ($id_cust != (int) Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER') && isset($cust['secure_key']) ) {
			$secure_key = $cust['secure_key'];
		}

		$cart_id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
		//OR $cart_id_lang = $context->language->id;
		if (isset($cust['language'])) {
			$tmp_id_lang = (int) $db->getValue("SELECT id_lang FROM ". _DB_PREFIX_ . "lang WHERE active = 1 AND id_lang = " . (int) $cust['language']);
			if ($tmp_id_lang > 0) {
				$cart_id_lang = $tmp_id_lang;
			}
		}
		
		$cart = new Cart($id_cart);
		$cart->id_customer = $id_cust;
		$cart->secure_key = $secure_key;
		//$cart->id_lang = isset($cust['language']) ? $cust['language'] : 1;
		$cart->id_lang = $cart_id_lang;
		$cart->save();
		
		//change customer
		if ($moreDetail == '') {
			updateCartGroup($context, $params, $response);
		}		
		
		// Reset delivery data
		setDelivery($cart, null);
		$response->addResult('cart', cartAsArray($cart));
	}
}


function updateCartGroup($context, $params, &$response) {

	$db = Db::getInstance();
	$id_cart = (int) $params->id_cart;

	$id_old_group = (int) $context->group->id;
	$id_new_group = 0;

	$id_cust = $params->id_cust;
	if ($id_cust == '') {
		$id_cust = (int) Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	}

	$new_cust = new Customer( (int) $id_cust );
	if ($new_cust) {
		$id_new_group = (int) $new_cust->id_default_group;
	}


	//if group change clear discount cart
	if ($id_old_group != $id_new_group && $id_new_group > 0) {

		$is_quote = $db->getValue("SELECT COUNT(id_cart) FROM ". _DB_PREFIX_ . "cart_kerawen cart_kerawen WHERE cart_kerawen.quote = 1 AND cart_kerawen.id_cart = " . pSQL($id_cart));

		//if not a quotation remove spec price cart
		if (!$is_quote) {
				
			$q = "SELECT
				  COUNT(specific_price.id_specific_price) AS nb
				  FROM ". _DB_PREFIX_ . "specific_price specific_price
				  WHERE specific_price.id_cart = " . pSQL($id_cart);
				
			$nb = $db->getValue($q);
			if ($nb) {

				$db->delete('specific_price', 'id_cart = ' . pSQL($id_cart) );
				$db->update('cart_product_kerawen', array('specific_price_cart' => '1'), 'id_cart = ' . pSQL($id_cart) );
				//or recalculate price from catalog price and apply discoun cart after

				$response->addResult('infoaction', array('spe_price_cart_del'));
			}
				
				
		}

	}

}

function getCustomer($id_cust, $id_lang, $moreData = '')
{
	require_once (dirname(__FILE__) . '/quote.php');
	require_once (dirname(__FILE__) . '/catalog.php');
	require_once (_KERAWEN_TOOLS_DIR_.'/utils.php');
			
	if ($id_cust && $id_cust != (int)Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER')) {
		$db = Db::getInstance();
		$context = Context::getContext();
		$data_cust = array();
		
		$cust = new Customer($id_cust);
		$more = $db->getRow('
			SELECT * FROM '._DB_PREFIX_.'customer_kerawen
			WHERE id_customer = '.pSQL($id_cust));
		
		// Loyalty program
		require_once(dirname(__FILE__).'/loyalty.php');
		if ($loyalty = KerawenLoyalty::getPlugin())
			$loyalty = $loyalty->getInfo($id_cust);
			
		// Prepaid account
		$prepaid = $db->getValue('
			SELECT cr.reduction_amount
			FROM '._DB_PREFIX_.'cart_rule cr
			JOIN '._DB_PREFIX_.'cart_rule_kerawen crk
				ON crk.id_cart_rule = cr.id_cart_rule
				AND crk.type = "'._KERAWEN_CR_PREPAID_.'"
			WHERE cr.quantity > 0
				AND cr.id_customer = '.pSQL($id_cust).'
			ORDER BY cr.id_cart_rule DESC');

		
		$deffered = 0;
		
		/*
		//V2
		//Deffered
		$buf = $db->executeS('
			SELECT o.id_order 
			FROM '._DB_PREFIX_.'orders o
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON o.id_order = ok.id_order
			WHERE o.id_customer = '.pSQL($id_cust).' 
			AND ok.is_paid = 0
		');
		
		foreach ($buf as $row) {			
			$order = new Order((int)$row['id_order']);
			$deffered += $order->total_paid_tax_incl - $order->total_paid_real;
		}
		*/
		//V1
		/*
		$deffered = $db->getValue('
			SELECT
				SUM(cfk.amount) AS deffered
			FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen cfk
			LEFT JOIN '._DB_PREFIX_.'orders o ON cfk.id_order = o.id_order
			WHERE cfk.id_payment_mode IN (5,11) AND o.id_customer = '.pSQL($id_cust)
		);
		*/

		
		
		$data_cust['id'] = $cust->id;
		//secure_key is required on front-office to convert cart to order
		$data_cust['secure_key'] = $cust->secure_key;
		$data_cust['gender'] = $cust->id_gender == 1 ? 'male' : 'female';
		$data_cust['firstname'] = $cust->firstname;
		$data_cust['lastname'] = $cust->lastname;
		$data_cust['fakemail'] = $more['fakemail'] != 0 ;
		$data_cust['email'] = $cust->email;
		$data_cust['addrs'] = getAddresses($cust, $id_lang);
		$data_cust['birthday'] = $cust->birthday;
		$data_cust['newsletter'] = $cust->newsletter != 0;
		$data_cust['optin'] = $cust->optin != 0;
		$data_cust['group'] = $cust->id_default_group;
		$data_cust['lnum'] = isset($more['loyalty_number']) ? $more['loyalty_number'] : null;
		//$data_cust['company'] = $more['company'];
		$data_cust['company'] = $cust->company;
		$data_cust['phone'] = $more['phone'];
		$data_cust['mobile'] = $more['mobile'];
		$data_cust['postalcode'] = $more['postalcode'];
		$data_cust['prepaid'] = $prepaid;
		$data_cust['loyalty'] = $loyalty;
		
		$data_cust['note'] = $cust->note;
		$data_cust['language'] = $cust->id_lang;
		$data_cust['deffered'] = $deffered ? $deffered : 0;
		$data_cust['login'] = getKerawenLink('quotenext', array('id_customer' => $cust->id, 'secure_key' => $cust->secure_key,'id_cart' => '0', 'action' => 'bologin'));
		

		//ADELYA
		if (Module::isInstalled('adelyaapi') && Module::isEnabled('adelyaapi')) {
		    //$data_cust['checkbox'] = (int) $db->getValue('SELECT fid_program_membership FROM `'._DB_PREFIX_.'customer` WHERE id_customer = ' . (int) $cust->id);
		    $data_cust['checkbox'] = (int) $cust->fid_program_membership;
		}
		
		if ($moreData == 'all') {
			//Quotations
			$quote = getQuotes((int) $id_cust);
			$quotecount = count($quote);		
			
			//$l_TAB_Synthese = array();
	

			$l_TAB_ProductSeen	= getSeenProductsFromCustomer($context, $id_cust );

			$l_INT_NbSeenProduct	= isset($l_TAB_ProductSeen['producttotalseen']) ? $l_TAB_ProductSeen['producttotalseen'] : 0;
			unset( $l_TAB_ProductSeen['producttotalseen'] );
			
			
			$l_TAB_MostExpensiveProduct	= getMostExpensiveProductInLastAbandonnedCartFromCustomer( $context, $id_cust);


			$l_TAB_Wishs = array();
			$l_INT_WishCumul = 0;
	
			if (Module::isInstalled('blockwishlist') && Module::isEnabled('blockwishlist')) {
			
				$l_TAB_Wishs	= getWishListFromCustomer($context, $id_cust);
				$l_TAB_Temp	= $l_TAB_Wishs;
				$l_TAB_Trie	= array();
				foreach( $l_TAB_Wishs as $key => $l_TAB_Row){
					$l_TAB_Trie[$key]	= $l_TAB_Row['price'];
					$l_INT_WishCumul	+=$l_TAB_Row['quantity'];
				}
			}

			$l_TAB_Favoris = array();
			$l_INT_FavCumul = 0;
			if (Module::isInstalled('favoriteproducts') && Module::isEnabled('favoriteproducts')) { 
				$l_TAB_Favoris	= getFavoritsFromCustomer($context,  $id_cust );
				$l_INT_FavCumul = count($l_TAB_Favoris);
			}

			$l_TAB_WebCart	= getWebCartFromCustomer( $context, $id_cust);
			$l_INT_CumulWebCart	= 0;
			foreach( $l_TAB_WebCart as $l_TAB_Item) {
				$l_INT_CumulWebCart	+= $l_TAB_Item['quantity'];
			}

			$l_TAB_LastConnection	= $cust->getLastConnections();
			$l_TAB_LastConnection	= isset( $l_TAB_LastConnection[0]['date_add'] )	? CalculDuree( $l_TAB_LastConnection[0]['date_add'])	: array("unit"	=> "never", "value"	=> "");
		
			$l_TAB_Items= getOrderProductsFromCustomer( $id_cust );
	
			$l_TAB_Dates	= array();
			foreach ($l_TAB_Items as $key => $l_TAB_Item) {
				$l_TAB_Dates[$key] = $l_TAB_Item['delivery_date'];
			}

			array_multisort($l_TAB_Dates, SORT_DESC, $l_TAB_Items);
			unset( $l_TAB_Dates );

			$l_TAB_ProductBought = array();
			$l_INT_CumulProductBought = 0;
			foreach ($l_TAB_Items as $l_TAB_Item) {				
				$p = new Product($l_TAB_Item['product_id'], true, $context->language->id);
				if (isset($p->id)) {
					$detail = detailProduct($p, $context->language->id, false);
					if ($detail) {
						$l_TAB_ProductDetail = array_merge(
							$detail,
							$l_TAB_Item,
							array("prodcategory" => "order"),
							array("lastvisit" => CalculDuree( $l_TAB_Item['delivery_date']) ),
							array("current_state" => $l_TAB_Item['current_state']),
							array("id_attr" => $l_TAB_Item['product_attribute_id'])
						);
						$l_TAB_ProductBought[] = $l_TAB_ProductDetail;
						$l_INT_CumulProductBought += $l_TAB_Item['product_quantity'];
					}
				}
			}	

			$l_TAB_Orders	= getOrdersAmountFromCustomer( $id_cust );
			$l_INT_NbCommande	= count( $l_TAB_Orders );
			$l_INT_Somme		= 0;
			foreach( $l_TAB_Orders as $l_TAB_Order){
				$l_INT_Somme	+= $l_TAB_Order['total'];
			}

					
			$data_cust['quote'] = $quote;
			$data_cust['quotecount'] = $quotecount;
	
			$data_cust['productseen']			= $l_TAB_ProductSeen;
			$data_cust['productseencount']		= $l_INT_NbSeenProduct;
			$data_cust['wishlist'] 				= $l_TAB_Wishs;
			$data_cust['wishlistcount']			= $l_INT_WishCumul;
			$data_cust['favorits']				= $l_TAB_Favoris;
			$data_cust['favoritscount']			= $l_INT_FavCumul;
			$data_cust['ordersincart'] 			= $l_TAB_WebCart;
			$data_cust['ordersincartcount'] 	= $l_INT_CumulWebCart;
			$data_cust['boughtproducts']		= $l_TAB_ProductBought;
			$data_cust['boughtproductscount']	= $l_INT_CumulProductBought;
			$data_cust['lastconnection']		= $l_TAB_LastConnection;
			$data_cust['turnover'] 				= $l_INT_Somme;
			$data_cust['orderscount']			= $l_INT_NbCommande;
			$data_cust['ordersaverage']			= ($l_INT_NbCommande > 0) ? $l_INT_Somme / $l_INT_NbCommande : 0;
			$data_cust['date_add'] 				= CalculDuree( $cust->date_add );
			$data_cust['date_addduree']			= CalculDuree( $cust->date_add );
			//$data_cust['synthese'] 			= $l_TAB_Synthese;
			$data_cust['stats']					= $cust->getStats();

		}

		return $data_cust;
		
	}
	else return null;
}

function getAddresses($cust, $id_lang) {
	$addresses = array();
	if ($cust) {
		if (is_int($cust)) $cust = new Customer($cust);
		$buf = $cust->getAddresses($id_lang);
		foreach ($buf as $addr) {
			$addresses[$addr['id_address']] = array(
				'id' => $addr['id_address'],
				'alias' => $addr['alias'],
				'firstname' => $addr['firstname'],
				'lastname' => $addr['lastname'],
				'vat_number' => $addr['vat_number'],
				'company' => $addr['company'],
				'address1' => $addr['address1'],
				'address2' => $addr['address2'],
				'postcode' => $addr['postcode'],
				'city' => $addr['city'],
				'state' => $addr['state'],
				'id_country' => $addr['id_country'],
				'phone' => $addr['phone'],
				'mobile' => $addr['phone_mobile']
			);
		}
	}
	return $addresses;
}

function updateCustomer($context, $params, &$response)
{
	$id_cust = $params->id_cust;
	$data = $params->data;
	$id_lang = $context->language->id;
	
	//Email already exist ?
	$id_customer = (int)Db::getInstance()->getValue('SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE email = "' . pSQL(trim($data->email)) . '" AND id_customer != ' . (int) $id_cust);
	//'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).' //required???
	if ($id_customer) {
		return $response->addResult('error', 'email');
	}

	$new = false;
	$cust = new Customer($id_cust);

	if (!$id_cust)
	{
		$new = true;
		$passwd = Tools::passwdGen(MIN_PASSWD_LENGTH);
		$cust->passwd = Tools::encrypt($passwd);
	}

	$cust->id_gender = $data->gender == 'male' ? 1 : 2;
	$cust->firstname = trim($data->firstname);
	$cust->lastname = trim($data->lastname);
	$cust->email = trim($data->email);
	$cust->birthday = $data->birthday;
	$cust->newsletter = $data->newsletter;
	$cust->optin = $data->optin;
	$cust->id_default_group = (int)$data->group;
	$cust->note = $data->note;
	$cust->id_lang = $data->language;
	$cust->company = $data->company;
	$cust->save();
	$cust->updateGroup(null);
	
	Db::getInstance()->execute('
		INSERT INTO '._DB_PREFIX_.'customer_kerawen (id_customer, phone, mobile, fakemail, postalcode) 
		VALUES ('.pSQL($cust->id).', "'.pSQL($data->phone).'" , "'.pSQL($data->mobile).'" , '.pSQL($data->fakemail).',"'.pSQL($data->postcode).'") 
		ON DUPLICATE KEY UPDATE
			phone = VALUES(phone),
			mobile = VALUES(mobile),
			fakemail = VALUES(fakemail),
			postalcode = VALUES(postalcode)');

	$loyaltyNumber = $data->lnum;
	if ($loyaltyNumber == '' && $new && Configuration::get('KERAWEN_CUST_PRINT')) {
	    $loyaltyNumber = getRandomLoyaltyNumber();
	}
	updateLoyaltyNumber($cust->id, $loyaltyNumber);
	
	//ADELYA
	if (Module::isInstalled('adelyaapi') && Module::isEnabled('adelyaapi')) {
	    $ctx = Context::getContext();
	    $cust->fid_program_membership = $data->checkbox;
	    $ctx->customer = $cust;
	}
	
	if ($new) {	    
	    
		Hook::exec('actionCustomerAccountAdd', array(
			'_POST' => array(),
			'newCustomer' => $cust,
		));
	
		if (!$data->fakemail) {
			if (Configuration::get('PS_CUSTOMER_CREATION_EMAIL')) {
				Mail::Send(
					$context->language->id,
					'account',
					Mail::l('Welcome!'),
					array(
						'{firstname}' => $cust->firstname,
						'{lastname}' => $cust->lastname,
						'{email}' => $cust->email,
						'{passwd}' => $passwd,
					),
					$cust->email,
					$cust->firstname.' '.$cust->lastname
				);
			}
	   }
	} else {
	    
	    //ADELYA OR ALWAYS ???
	    if (Module::isInstalled('adelyaapi') && Module::isEnabled('adelyaapi')) {
    	    Hook::exec('actionCustomerAccountUpdate', array(
    	        '_POST' => array(),
    	        'customer' => $cust,
    	    ));
	    }
	    
	}
	
	$params->id_cust = $cust->id;
	
	if (Configuration::get('KERAWEN_CUST_ACCOUNT_ADDR') && $new) {
		//check if enougth data to make a new address
	    if ($params->data->address1 || $params->data->address2 || $params->data->postcode || $params->data->city) {
	        updateAddress($context, $params, $response, false);
		}
	}

	$params->id_cust = $cust->id;
	$params->moreDetail = 'all';
	$response->addResult('isnews', $new);
	selectCustomer($context, $params, $response);

}

function getRandomLoyaltyNumber() {
    $loyalty_number = '';
    $flagLoyaltyNumber = -1;
    while($flagLoyaltyNumber != 0) {
        $loyalty_number = '';
        for ($k = 0; $k < 10; $k++) {
            $loyalty_number .= mt_rand(0, 9);
        }
        $flagLoyaltyNumber = (int) Db::getInstance()->getValue('SELECT id_customer FROM '._DB_PREFIX_.'customer_kerawen WHERE loyalty_number = "'.pSQL($loyalty_number).'"');
    }
    return $loyalty_number;
}


function updateLoyaltyNumber($id_customer, $loyalty_number) {
    Db::getInstance()->execute('
        INSERT INTO '._DB_PREFIX_.'customer_kerawen (id_customer, loyalty_number) VALUES ('.pSQL($id_customer).', "'.pSQL($loyalty_number).'") 
        ON DUPLICATE KEY UPDATE loyalty_number = VALUES(loyalty_number)
    ');
}


function updateAddress($context, $params, &$response, $selectCust = true)
{
    $addr = isset($params->addr) ? $params->addr : $params->data;

	$db = Db::getInstance();
		
	$id_cust = (int)$params->id_cust;
	if (!$id_cust) $id_cust = (int)Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	
	$id_addr = isset($addr->id) ? $addr->id : null;
	if ($id_addr)
	{
		// Check if address can be changed
		$address = new Address($id_addr);
		if ($address->isUsed()) {
			//$address->delete();
			// Previous address may not be complete
			// PrestaShop doesn't like that...
			$db->update('address', array('deleted' => 1), 'id_address = '.pSQL($id_addr));
			$id_addr = null;
		}
		else {
			// Force address to be reloaded later if change occurs
			$address->clearCache();
		}
	}

	// Country is required
	$id_country = (int)$addr->country;
	if (!$id_country)
		$id_country = $context->country->id;

	// Insert directly data (that may be partially undefined)
	$now = date('Y-m-d H:i:s');
	$values = array(
		'id_customer' => pSQL($id_cust),
		'alias' => isset($addr->alias) ? pSQL($addr->alias) : pSQL('Main address'),
		'firstname' => pSQL($addr->firstname),
		'lastname' => pSQL($addr->lastname),
		'company' =>isset($addr->company) ? pSQL($addr->company) : "",
		'vat_number' => isset($addr->vat_number) ? pSQL($addr->vat_number) : "",
		'address1' => pSQL($addr->address1),
		'address2' => pSQL($addr->address2),
		'postcode' => pSQL($addr->postcode),
		'city' => pSQL($addr->city),
		'id_country' => (int)$id_country,
		'phone' => pSQL($addr->phone),
		'phone_mobile' => pSQL($addr->mobile),
		'date_upd' => pSQL($now),
	);

	if ($id_addr)
		$db->update('address', $values, 'id_address = '.pSQL($id_addr));
	else {
		$values['date_add'] = pSQL($now);
		$db->insert('address', $values);
		$id_addr = $db->Insert_ID();
	}

	//ADELYA Synchro
	if (Module::isInstalled('adelyaapi') && Module::isEnabled('adelyaapi')) {
	    $db->execute('UPDATE '._DB_PREFIX_.'customer SET date_upd = (NOW() + INTERVAL 5 SECOND) WHERE id_customer = ' . (int) $id_cust);
	}
	
	/*
	//ADELYA
	if (Module::isInstalled('adelyaapi') && Module::isEnabled('adelyaapi')) {
	    try {
	       require_once(_PS_MODULE_DIR_.'/adelyaapi/adelyaUtil.php');
	       $ctx = Context::getContext();
	       $ctx->customer = new Customer($id_cust);
	       $adelyaUtil = new adelyaUtil();
	       $adelyaUtil->syncCustomerData($ctx->customer);
	    }
	    catch (Exception $e) {
	        // Ignore
	    }
	}
	*/
	
	if ($params->id_cust && $selectCust)
	{
		// (Re)select customer
		$params->id_cart = null;
		$params->moreDetail = 'all';
		selectCustomer($context, $params, $response);
	}
	require_once(dirname(__FILE__).'/address.php');
	$response->addResult('addr', getAddress((int)$id_addr));
}

function deleteAddress($context, $params, &$response)
{
	$id_addr = $params->id_addr;
	$db = Db::getInstance();

	if ($id_addr)
	{
		$address = new Address($id_addr);
		// Previous address may not be complete
		// PrestaShop doesn't like that...
		//$address->delete();
		// Check if address can be deleted
		if ($address->isUsed())
			$db->update('address', array('deleted' => 1), 'id_address = '.pSQL($id_addr));
		else
			$db->delete('address', 'id_address = '.pSQL($id_addr));

		// Cancel address as delivery
		$db->update('cart', array(
			'id_address_delivery' => 0,
		), 'id_address_delivery = '.pSQL($id_addr));
		$db->update('cart_kerawen', array(
			'id_address' => 0,
			'carrier' => null,
		), 'id_address = '.pSQL($id_addr));
	}

	// (Re)select customer and cart
	if (!isset($params->id_cart)) $params->id_cart = null;
	$params->moreDetail = 'all';
	selectCustomer($context, $params, $response);
}

/*
* Override LoyaltyModule.registerDiscount which have a bug
* TO CHECK
*/
function registerDiscount($params)
{
	$cart_rule = $params->cart_rule;
	if (! Validate::isLoadedObject($cart_rule))
		die(Tools::displayError('Incorrect object CartRule.'));

	$associated = false;
	if (Module::isInstalled('loyalty') && Module::isEnabled('loyalty'))
	{
		$items = LoyaltyModule::getAllByIdCustomer((int)$cart_rule->id_customer, null, true);
		foreach ($items as $item)
		{
			$lm = new LoyaltyModule((int)$item['id_loyalty']);

			// Le bug se situe sur le numéro de la commande($f -> $id)
			/* Check for negative points for this order */
			$negative_points = (int)Db::getInstance()->getValue('
					SELECT SUM(points) points FROM '._DB_PREFIX_.'loyalty
					WHERE id_order = '.(int)$item['id'].'
					AND id_loyalty_state = '.(int)LoyaltyStateModule::getCancelId().'
					AND points < 0');

			if ($lm->points + $negative_points <= 0)
				continue;

			$lm->id_cart_rule = (int)$cart_rule->id;
			$lm->id_loyalty_state = (int)LoyaltyStateModule::getConvertId();
			$lm->save();
			$associated = true;
		}
	}
	return $associated;
}

/**
 * Convert a date in duration from now in year, month, week, day or hour depends on it.
 * @param string $p_STR_date	date like "2015-27-01 12:02:52"
 * @return array
 * 	@version 13 juil. 2015	: APE	- Protection contre les dates Ã  0
 */
function CalculDuree( $p_STR_date ){
	if(( $p_STR_date =="0000-00-00 00:00:00" )
	or( is_null($p_STR_date))) {
		return array("value" => "", 
					"unit"	 => ""
				);
	}
	$l_TAB_Temp	= explode(" ", ConvertDuree( time() - strtotime( $p_STR_date )), 2 );
	return array("value" => $l_TAB_Temp[0], 
				"unit"	 => $l_TAB_Temp[1]
			);
}


/**
 * Convert duration in year, month, week, day or hour depends on it.
 * @param integer $p_INT_Duree	Duration to convert.
 * @return string
 */
function ConvertDuree( $p_INT_Duree ){
	if( !is_numeric($p_INT_Duree))	return null;
	
	// Si on a une année ou plus
	if( $p_INT_Duree > (3600 * 24 * 30 * 12) ) {
		$l_INT_Nb	= (int)($p_INT_Duree/(3600 * 24 * 30 * 12));
		return ( $l_INT_Nb > 1 )	?	$l_INT_Nb." years"	: $l_INT_Nb." year"; 
	
	// Si on a un mois ou plus
	} elseif( $p_INT_Duree > (3600 * 24 * 30 ) ) {
		$l_INT_Nb	= (int)($p_INT_Duree/(3600 * 24 * 30 ));
		return ( $l_INT_Nb > 1 )	?	$l_INT_Nb." months"	: $l_INT_Nb." month"; 

	// Si on a une semaine ou plus
	} elseif( $p_INT_Duree > (3600 * 24 * 6 ) ) {
		$l_INT_Nb	= (int)($p_INT_Duree/(3600 * 24 * 6));
		return ( $l_INT_Nb > 1 )	?	$l_INT_Nb." weeks"	: $l_INT_Nb." week"; 

	// Si on a une journée ou plus
	} elseif( $p_INT_Duree > (3600 * 24 ) ) {
		$l_INT_Nb	= (int)($p_INT_Duree/(3600 * 24 ));
		return ( $l_INT_Nb > 1 )	?	$l_INT_Nb." days"	: $l_INT_Nb." day"; 

	} 

	// Si on a une heure ou plus
	$l_INT_Nb	= (int)($p_INT_Duree/(3600 ));
	return ( $l_INT_Nb > 1 )	?	$l_INT_Nb." hours"	: " now"; 
}




function getOrderProductsFromCustomer( $customerid ) {

	$db = Db::getInstance();

	$l_STR_Requet	= '
		SELECT * FROM `'._DB_PREFIX_.'orders` o
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od ON o.id_order = od.id_order
		WHERE o.valid = 1 AND o.`id_customer` = ' . (int) $customerid . '
		ORDER BY od.id_order_detail DESC
		LIMIT 20';
	
	$l_TAB_Items = $db->executeS( $l_STR_Requet );	

	return ($l_TAB_Items) ? $l_TAB_Items : array();
	
}	
	



/**
 * Get seen product for this customer.
 *  
 * @param object $context		The contexte ( ? )
 * @param integer $customerid	A customer ID.
 * @return array 				data containning customers wish list with items (or null).
 */
function getSeenProductsFromCustomer( $context, $customerid ) {

	require_once (dirname(__FILE__) . '/catalog.php');
	$db = Db::getInstance();	

	// Get product ID from last seen products
	$l_STR_Requet	= '
		SELECT
		id_product, counter, lastvisit
		FROM `'._DB_PREFIX_.'customer_seen_kerawen`
		WHERE id_customer = '.pSQL( $customerid ).'
		ORDER BY `lastvisit` DESC';

	$l_TAB_Items	=  $db->executeS( $l_STR_Requet );
	// RECETTE
	if( !$l_TAB_Items )	return array();
	
	
	// Count the seens product.
	$l_TAB_Products	= array();
	$l_TAB_Products['producttotalseen']	= 0;
	foreach( $l_TAB_Items as $l_INT_Key	=> $l_TAB_Item){
		$l_TAB_Products['producttotalseen'] +=	$l_TAB_Item['counter'];
		
		// Change the lastvisit date to hours, day, week or month
		$l_TAB_Items[$l_INT_Key]['lastvisit'] = CalculDuree( $l_TAB_Item['lastvisit'] );
	}
	
	

/*
	// Get product detail ( for the first 10 items )
	// 	foreach ($l_TAB_Items as $l_TAB_Item){
	foreach (array_slice($l_TAB_Items, 0, 10) as $l_TAB_Item){
		$l_TAB_ProductDetail	= array_merge(detailProduct(new Product($l_TAB_Item['id_product'], true, $context->language->id), $context->language->id),
				array("counter"			=> $l_TAB_Item['counter']),
				array("lastvisit"		=> $l_TAB_Item['lastvisit']),
				array("prodcategory"	=> "seen")
		);
	
		$l_TAB_Products[] = $l_TAB_ProductDetail;
	}

*/

	foreach (array_slice($l_TAB_Items, 0, 10) as $l_TAB_Item){
	
		$p = new Product($l_TAB_Item['id_product'], true, $context->language->id);
		if (isset($p->id)) {
			$detail = detailProduct($p, $context->language->id, false);
			if ($detail) {
				$l_TAB_ProductDetail	= array_merge(
						$detail,
						array("counter"			=> $l_TAB_Item['counter']),
						array("lastvisit"		=> $l_TAB_Item['lastvisit']),
						array("prodcategory"	=> "seen"),
						array("id_attr" => false)
						);
				$l_TAB_Products[] = $l_TAB_ProductDetail;
			}
		}
	
	}	
	

	return $l_TAB_Products;
}


/**
 * Get Wishs list and associate products from a customer.
 * 
 * @param object $context		The contexte ( ? )
 * @param integer $customerid	A customer ID.
 * @return array 				data containning customers wish list with items (or null).
 * 	@version 25 août 2015	: APE	- Modificaiton du picto.
 */
function getWishListFromCustomer( $context, $customerid ){

	require_once (dirname(__FILE__) . '/catalog.php');
	$db = Db::getInstance();	

	// Get wishs list id from this customer
	$l_STR_Requet	= 'SELECT
				id_wishlist AS id
			FROM `'._DB_PREFIX_.'wishlist`
			WHERE id_customer = '.pSQL( $customerid ).'
			LIMIT 10';
	$l_TAB_WishList	= $db->executeS( $l_STR_Requet );
	// RECETTE
	if( !$l_TAB_WishList )	return array();
	
	
		
	// Get products for each list
	$l_TAB_ListID	= array();
	foreach( $l_TAB_WishList as $l_TAB_Wish ){
		$l_TAB_ListID[]	= $l_TAB_Wish['id'];
	}
	$l_STR_Requet	= 'SELECT w.name as wishlistname,
				p.id_product,
				p.reference,
				p.price,
				wp.quantity
				
			FROM `'._DB_PREFIX_.'wishlist_product` wp,
				`'._DB_PREFIX_.'product` p,
				`'._DB_PREFIX_.'wishlist` w
			WHERE w.id_wishlist IN ('.implode(",", $l_TAB_ListID).')
				AND  p.id_product	= wp.id_product
				AND wp.`id_wishlist` = w.`id_wishlist`
    		ORDER BY wishlistname, wp.`priority` ASC
			LIMIT 10';
	
	$l_TAB_Items	= $db->executeS( $l_STR_Requet );

	
	// Get wishlist products detail
	$l_TAB_Products	= array();
	foreach ($l_TAB_Items as $l_TAB_Item){
		
		$p = new Product($l_TAB_Item['id_product'], true, $context->language->id);
		if (isset($p->id)) {
			$detail = detailProduct($p, $context->language->id);
			if ($detail) {
				$l_TAB_ProductDetail = array_merge(
					$detail,
					array("quantity" => $l_TAB_Item['quantity']),
					array("prodcategory"	=> "presents"),
					array("counter"			=> 0),
					array("wishlistname"	=> $l_TAB_Item['wishlistname']),
					array("id_attr" => false)
				);
				$l_TAB_Products[] = $l_TAB_ProductDetail;
			}
		}

	}
	
	// Return whish list with articles
	return $l_TAB_Products; 

}

/**
 *	RÃ©cupÃ©ration de la liste des favorits d'un customer.
 *	Si le module des favorits n'est pas installÃ©, la requÃ¨te Mysql ne renvoie rien et on a donc un tableau vide.
 *  
 * @param object $context		The contexte ( ? )
 * @param integer $customerid	A customer ID.
 * 	@version 21 aoÃ»t 2015	: APE	- CrÃ©ation.
 * 
 */
function getFavoritsFromCustomer($context,  $customerid ){

	require_once (dirname(__FILE__) . '/catalog.php');
	$db = Db::getInstance();	

	$l_STR_Requet	= 'SELECT id_product, date_upd
			FROM `'._DB_PREFIX_.'favorite_product`
			WHERE id_customer = '.pSQL( $customerid ).'
					ORDER BY `date_upd` DESC
					LIMIT 20
					';

	$l_TAB_Items	= $db->executeS( $l_STR_Requet );
	if( !$l_TAB_Items )	return array();
	
	
	$l_TAB_Products	= array();
	foreach ($l_TAB_Items as $l_TAB_Item){

		$p = new Product($l_TAB_Item['id_product'], true, $context->language->id);
		if (isset($p->id)) {
			$detail = detailProduct($p, $context->language->id, false);
			if ($detail) {
				$l_TAB_ProductDetail = array_merge(
						$detail,
						array("prodcategory" => "wish"),
						array("lastvisit" => CalculDuree( $l_TAB_Item['date_upd']) ),
						array("id_attr" => false)
				);
				$l_TAB_Products[] = $l_TAB_ProductDetail;
			}		
		}
			
	}
	
	return $l_TAB_Products; 
}


/**
 *	Récupération du pannier web d'un client.  
 * @param object $context		The contexte ( ? )
 * @param integer $customerid	A customer ID.
 * 	@version 12 juil. 2015	: APE	- Modification de l'icone ( prodcategory )
 * 	@version 22 aoÃ»t 2015	: APE	- Ajout de la dÃ©clinaison choisie du produit .
 * 
 * @todo : Afficher l'image de la version déclinée du produit.
 */
function getWebCartFromCustomer( $context, $customerid ){

	require_once (dirname(__FILE__) . '/catalog.php');
	$db = Db::getInstance();

	$l_STR_Requet	= '
	SELECT id_product, date_add, quantity, id_product_attribute
	FROM `' . _DB_PREFIX_ . 'cart_product`
	WHERE id_cart = (
		SELECT cart.id_cart
		FROM `' . _DB_PREFIX_ . 'cart` cart
		LEFT JOIN `' . _DB_PREFIX_ . 'cart_kerawen` cart_kerawen ON cart.id_cart = cart_kerawen.id_cart
		LEFT JOIN ' . _DB_PREFIX_ . 'orders orders ON cart.id_cart = orders.id_cart
		WHERE cart_kerawen.id_cart IS NULL AND orders.id_order IS NULL AND cart.id_customer = ' . pSQL( $customerid ) . '
		ORDER BY cart.id_cart DESC
		LIMIT 1
	)';

				
	$l_TAB_Items	= $db->executeS( $l_STR_Requet );
	if( !$l_TAB_Items )	return array();
	
	$l_TAB_Products	= array();
	foreach ($l_TAB_Items as $l_TAB_Item){
		$prod	= new Product($l_TAB_Item['id_product'], true, $context->language->id);
		
		if (isset($prod->id)) {
			$detail = detailProduct($prod, $context->language->id, false);
			if ($detail) {
				$l_TAB_ProductDetail	= array_merge(
						$detail,
						array("quantity" => $l_TAB_Item['quantity']),
						array("prodcategory" => "basket"),
						array("lastvisit" => CalculDuree( $l_TAB_Item['date_add']) ),
						array("id_attr" => $l_TAB_Item['id_product_attribute'])
				);
			
				// Déclinaison choisie
				$id_attr = $l_TAB_Item['id_product_attribute'];
				if ($id_attr <> '0') {
					if (isset($l_TAB_ProductDetail['versions'][$id_attr])) {
						$l_TAB_DeclinaisonChoisi = $l_TAB_ProductDetail['versions'][$id_attr];
						$l_TAB_ProductDetail['declinaisonchoisie'] = $l_TAB_DeclinaisonChoisi['name'];
					}
				}
	
				$l_TAB_Products[] = $l_TAB_ProductDetail;
			}
		}
	}
	
	return $l_TAB_Products; 
}

/**
 * Get orders amount for a customer.
 * 
 * @param integer $customerid	A customer ID.
 * @return array 				data containning customers orders (or null).
 */
function getOrdersAmountFromCustomer( $customerid ){

	$db = Db::getInstance();

	$l_STR_Requet	= 'SELECT 
				o.`id_order` AS id,
				o.`total_paid` AS total
			FROM `'._DB_PREFIX_.'orders` o
			WHERE id_customer = '.pSQL( $customerid );

	return $db->executeS( $l_STR_Requet );
	
}

/**
 *	RÃ©cupÃ©ration de l'article le plus chÃ¨re du dernier panier abandonÃ© d'un client.  
 * @param object $context		The contexte ( ? )
 * @param integer $customerid	A customer ID.
 */
function getMostExpensiveProductInLastAbandonnedCartFromCustomer( $context, $customerid ){

	require_once (dirname(__FILE__) . '/catalog.php');
	$db = Db::getInstance();

	$l_TAB_ProductDetail = null;
	
	// Get product ID IN last abandonned cart ID
	$l_STR_Requet	= 'SELECT id_product, date_add
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE id_cart = (
					SELECT cart.id_cart
					FROM '._DB_PREFIX_.'cart cart
					LEFT JOIN '._DB_PREFIX_.'cart_kerawen cart_kerawen 
					ON cart.id_cart = cart_kerawen.id_cart 
					/*AND cart_kerawen.quote IS NULL*/
					WHERE cart.id_customer = '.pSQL( $customerid ).'
					ORDER BY cart.id_cart DESC
					LIMIT 1
				)
				';

	$l_TAB_Item	= $db->executeS( $l_STR_Requet );

	if (count($l_TAB_Item) == 1)
	{
		// Detail du produit
		
		$p = new Product($l_TAB_Item[0]['id_product'], true, $context->language->id);
		if (isset($p->id)) {
			$detail = detailProduct($p, $context->language->id, false);
			if ($detail) {
				$l_TAB_ProductDetail	= array_merge(
					$detail,
					array("quantity"		=> 0),
					array("lastvisit"		=> CalculDuree( $l_TAB_Item[0]['date_add']) ),
					array("prodcategory"	=> "order"),
					array("counter"			=> 0)
				);
			}
		}

	}
	
	return $l_TAB_ProductDetail;
}


/**
 * Get most expensive order from last order for a customer.
 *
 * @param object $context		The contexte ( ? )
 * @param integer $customerid	A customer ID.
 * @return array 				data containning customers orders (or null).
 * 	@version 12 juil. 2015	: APE	- Modification icone (prodcategory)
 */
function getLastOrderInCartFromCustomer($context, $customerid ){

	require_once (dirname(__FILE__) . '/catalog.php');
	$db = Db::getInstance();

	$l_TAB_ProductDetail = array();

	// Get last order ID from this customer 
	$l_STR_Requet	= 'SELECT
				id_order, delivery_date
			FROM `'._DB_PREFIX_.'orders` o
			WHERE id_customer = '.pSQL( $customerid ).'
			ORDER BY `delivery_date` DESC
			LIMIT 1;';

	$l_TAB_Order	= $db->executeS( $l_STR_Requet );
	if( !$l_TAB_Order )	return array();

	// Get most expensive product from this order
	$l_STR_Requet	= 'SELECT product_id
			FROM `'._DB_PREFIX_.'order_detail` 
			WHERE `id_order` = '.$l_TAB_Order[0]['id_order'].'
			ORDER BY `total_price_tax_incl` DESC
			LIMIT 1;';
	$l_TAB_ProductID	= $db->executeS( $l_STR_Requet );
	
	$p = new Product($l_TAB_ProductID[0]['product_id'], true, $context->language->id);
	if (isset($p->id)) {
		$detail = detailProduct($p, $context->language->id, false);
		if ($detail) {
			$l_TAB_ProductDetail	= array_merge(
					$detail,
					array("counter"			=> 0),
					array("lastvisit"		=> CalculDuree( $l_TAB_Order[0]['delivery_date']) ),
					array("prodcategory"	=> "basket")
			);
		}
	}
	
	// Return product detail
	return $l_TAB_ProductDetail;
}

