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

/**
 * Counts legacy PrestaShop orders
 * Optionnaly references these orders in KerAwen orders table
 */
function legacyOrders($doit = false)
{
	$db = Db::getInstance();
	$buf = $db->executeS('
		SELECT `id_order` FROM `'._DB_PREFIX_.'orders`
		WHERE `id_order` NOT IN (SELECT `id_order` FROM `'._DB_PREFIX_.'order_kerawen`)');
	$count = count($buf);
	if ($doit)
	{
		$count = 0;
		foreach ($buf as $prev)
		{
			$order = new Order($prev['id_order']);
			$db->insert('order_kerawen', array(
				'id_order' => $order->id,
			));
			$count++;
		}
	}
	return $count;
}

function addReturnedProduct($id_cart, $id_order, $id_order_detail, $quantity, $refund_tax_incl, $price_tax_incl, $back_to_stock, $refund_tax_excl)
{
	$db = Db::getInstance();

	if ($id_order_detail) {
		$max_returns = getMaxReturns($id_order_detail);
		if ($quantity > $max_returns)
		{
			$e = new PrestaShopException('Invalid quantity ('.$quantity.') The maximum number of returns is : '.$max_returns);
			$e->quantity = $quantity;
			$e->max_returns = $max_returns;
			throw $e;
		}
	}

	$db->insert('return_kerawen', array(
			'id_cart' => $id_cart,
			'id_order' => $id_order,
			'id_order_detail' => $id_order_detail,
			'quantity' => $quantity,
			'refund_tax_incl'=>$refund_tax_incl,
			'price_tax_incl' => $price_tax_incl,
			'back_to_stock'=> $back_to_stock,
			'id_order_after' => 0,
			'order_after_reference' => 'NOREF',
			'refund_tax_excl' => $refund_tax_excl
	));
	
	// Check customers
	$id_cust_anonymous = (int)Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	$id_cust_cart = (int)$db->getValue('
		SELECT id_customer FROM '._DB_PREFIX_.'cart WHERE id_cart = '.pSQL($id_cart));
	if ($id_cust_cart == $id_cust_anonymous) $id_cust_cart = 0;
	$id_cust_order = (int)$db->getValue('
		SELECT id_customer FROM '._DB_PREFIX_.'orders WHERE id_order = '.pSQL($id_order));
	if ($id_cust_order == $id_cust_anonymous) $id_cust_order = 0;
	
	return $id_cust_cart != $id_cust_order ? ($id_cust_cart && $id_cust_order ? -1 : $id_cust_order) : 0;
}

function eraseReturn($id_return)
{
	Db::getInstance()->delete(_DB_PREFIX_.'return_kerawen', 'id_return ='.pSQL($id_return), 1);
}

/* returns the number of possible returns of a specific order detail */
function getMaxReturns($id_order_detail)
{
	$db = Db::getInstance();
	//number of items
	$max = $db->getValue('
			SELECT SUM(`product_quantity`)
			FROM `'._DB_PREFIX_.'order_detail`
			WHERE `id_order_detail` = '.pSQL($id_order_detail));
	//number of items that have already been returned
	$ps_qties = Db::getInstance()->getRow('
			SELECT `product_quantity_return`, `product_quantity_refunded`
			FROM `'._DB_PREFIX_.'order_detail`
			WHERE `id_order_detail` = '.pSQL($id_order_detail));
	$max -= ($ps_qties['product_quantity_return'] + $ps_qties['product_quantity_refunded']);
	//number of items that are going to be returned
	$max -= $db->getValue('
			SELECT SUM(`quantity`) FROM `'._DB_PREFIX_.'return_kerawen`
			WHERE `id_order_detail` = '.pSQL($id_order_detail).'
			AND `took_effect` = 0');
	return $max;
}



/**
 * List orders according to filter
 */
function getOrders($filter, $perpage, $page, $counter)
{
	
	$context = Context::getContext();
	$permissions = $context->permissions;
	
	define('_COND_DATE_TODAY_', 'DATE(ok.`delivery_date`) = DATE(NOW())');
	define('_COND_DATE_PAST_', 'DATE(ok.`delivery_date`) < DATE(NOW())');
	define('_COND_DATE_FUTURE_', 'DATE(ok.`delivery_date`) > DATE(NOW())');
	define('_COND_DATE_UNSET_', 'ok.`delivery_date` IS NULL');

	// Setup filter condition
	$condition = array();
	if (isset($filter))
	{
		if (isset($filter->shop) && count($filter->shop))
			$condition[] = 'o.`id_shop` IN ('.implode(',', $filter->shop).')';
		if (isset($filter->carrier) && count($filter->carrier))
			$condition[] = 'ca.`id_reference` IN ('.implode(',', $filter->carrier).')';
		if (isset($filter->till) && count($filter->till) == 1)
		{
			if ($filter->till[0])
				$condition[] = 'ok.id_till > 0';
			else
				$condition[] = '(ok.id_till = 0 OR ok.id_till IS NULL)';
		}
		if (isset($filter->mode) && count($filter->mode))
			$condition[] = 'ok.`delivery_mode` IN ('.implode(',', $filter->mode).')';
		if (isset($filter->state) && count($filter->state))
			$condition[] = 'ok.`preparation_status` IN ('.implode(',', $filter->state).')';
		if (isset($filter->paid) && count($filter->paid))
			$condition[] = 'ok.is_paid IN('.implode(',', $filter->paid).')';
		if (isset($filter->cust) && count($filter->cust))
			$condition[] = 'o.`id_customer` IN ('.implode(',', $filter->cust).')';
		if (isset($filter->id_till) && count($filter->id_till))
			$condition[] = 'ok.`id_till` IN ('.implode(',', $filter->id_till).')';	
			
		if (isset($filter->date))
		{
			$buf = array();
			if (in_array('today', $filter->date)) $buf[] = _COND_DATE_TODAY_;
			if (in_array('past', $filter->date)) $buf[] = _COND_DATE_PAST_;
			if (in_array('future', $filter->date)) $buf[] = _COND_DATE_FUTURE_;
			if (in_array('unset', $filter->date)) $buf[] = _COND_DATE_UNSET_;
			if (count($buf))
				$condition[] = '('.implode(' OR ', $buf).')';
		}
	}
	

	$conditionPermission = array();
	$conditionPermissionWhere = false;

	if (isset($permissions->ordersDisplay)) {
		if (!empty($permissions->ordersDisplay->id_shop)) {
	 		$conditionPermission['id_shop'] = 'o.id_shop IN (' . $permissions->ordersDisplay->id_shop . ')';
	 	} elseif (!empty($permissions->ordersDisplay->id_employee)) {
	 		$conditionPermission['id_employee'] = 'ok.id_employee = ' . $permissions->ordersDisplay->id_employee;
	 	} else {
	 		$conditionPermission['false'] = 'FALSE';
	 	}
	 	$condition = array_merge($condition, array_values($conditionPermission));
	}

	$condition = count($condition) ? implode(' AND ', $condition) : null;
	$conditionPermissionWhere = count($conditionPermission) ? implode(' AND ', array_values($conditionPermission)) : null;
	
	
	if (isset($counter))
	{
		if (isset($counter->cust) && count($counter->cust))
			$cust_cond = 'o.`id_customer` IN ('.implode(',', $counter->cust).')';
	}

	// Count items
	$db = Db::getInstance();
	$counter = $db->getRow('
		SELECT
			COUNT(o.`id_order`) AS total,
			SUM(IF('.($condition ? $condition : 'true').', 1, 0)) AS filter,
			SUM(IF('._COND_DATE_TODAY_.', 1, 0)) AS date_today,
			SUM(IF('._COND_DATE_PAST_.', 1, 0)) AS date_past,
			SUM(IF('._COND_DATE_FUTURE_.', 1, 0)) AS date_future,
			SUM(IF('._COND_DATE_UNSET_.', 1, 0)) AS date_unset
		FROM `'._DB_PREFIX_.'orders` o
		LEFT JOIN `'._DB_PREFIX_.'order_kerawen` ok ON o.`id_order` = ok.`id_order`
		LEFT JOIN '._DB_PREFIX_.'carrier ca ON ca.id_carrier = o.id_carrier
		'.($conditionPermissionWhere ? 'WHERE '.$conditionPermissionWhere : ''));

	$buf = $db->executeS('
		SELECT o.`id_shop` AS id, COUNT(*) AS count
		FROM `'._DB_PREFIX_.'orders` o
		LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON o.`id_order` = ok.`id_order`
		' . ($conditionPermissionWhere ? ' WHERE ' . $conditionPermissionWhere : '') . '
		GROUP BY o.`id_shop`');
	$counter['shop'] = array();
	foreach ($buf as $row) $counter['shop'][$row['id']] = $row['count'];
	
	$buf = $db->executeS('
		SELECT oo.id AS id, COUNT(*) AS count
		FROM (
			SELECT ok.id_order AS id_order, IF(ok.id_till = 0 OR ok.id_till IS NULL, 0, 1) AS id
			FROM `'._DB_PREFIX_.'order_kerawen` ok
			LEFT JOIN `'._DB_PREFIX_.'orders` o ON ok.`id_order` = o.`id_order`
			' . ($conditionPermissionWhere ? ' WHERE ' . $conditionPermissionWhere : '') . '
			) oo
		GROUP BY oo.id');
	$counter['till'] = array();
	foreach ($buf as $row) $counter['till'][$row['id']] = $row['count'];

	$buf = $db->executeS('
		SELECT ok.id_till AS id, COUNT(*) AS count
		FROM `'._DB_PREFIX_.'order_kerawen` ok
		GROUP BY ok.id_till');
	$counter['id_till'] = array();
	foreach ($buf as $row) $counter['id_till'][$row['id']] = $row['count'];	
	
	$buf = $db->executeS('
		SELECT ok.`delivery_mode` AS id, COUNT(*) AS count
		FROM `'._DB_PREFIX_.'order_kerawen` ok
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON ok.`id_order` = o.`id_order`
		' . ($conditionPermissionWhere ? ' WHERE ' . $conditionPermissionWhere : '') . '
		GROUP BY ok.`delivery_mode`');
	$counter['mode'] = array();
	foreach ($buf as $row) $counter['mode'][$row['id']] = $row['count'];

	$buf = $db->executeS('
		SELECT ok.`preparation_status` AS id, COUNT(*) AS count
		FROM `'._DB_PREFIX_.'order_kerawen` ok
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON ok.`id_order` = o.`id_order`
		' . ($conditionPermissionWhere ? ' WHERE ' . $conditionPermissionWhere : '') . '
		GROUP BY ok.`preparation_status`');
	$counter['state'] = array();
	foreach ($buf as $row) $counter['state'][$row['id']] = $row['count'];

	$buf = $db->executeS('
		SELECT ok.`is_paid` AS id, COUNT(*) AS count
		FROM `'._DB_PREFIX_.'order_kerawen` ok
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON ok.`id_order` = o.`id_order`
		' . ($conditionPermissionWhere ? ' WHERE ' . $conditionPermissionWhere : '') . '
		GROUP BY ok.`is_paid`');
	$counter['paid'] = array();
	foreach ($buf as $row) $counter['paid'][$row['id']] = $row['count'];
	
	$buf = $db->executeS('
		SELECT ca.id_reference AS id, COUNT(*) AS count
		FROM '._DB_PREFIX_.'orders o
		LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
		LEFT JOIN '._DB_PREFIX_.'carrier ca ON ca.id_carrier = o.id_carrier
		' . ($conditionPermissionWhere ? ' WHERE ' . $conditionPermissionWhere : '') . '
		GROUP BY ca.id_reference');
	$counter['carrier'] = array();
	foreach ($buf as $row) $counter['carrier'][$row['id']] = $row['count'];	
		
	if (isset($cust_cond))
	{
		$buf = $db->executeS('
			SELECT o.`id_customer` AS id, COUNT(*) AS count
			FROM `'._DB_PREFIX_.'orders` o
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON o.`id_order` = ok.`id_order`
			WHERE '.$cust_cond . ($conditionPermissionWhere ? ' AND ' . $conditionPermissionWhere : '') . '
			GROUP BY o.`id_customer`');
		$counter['cust'] = array();
		foreach ($buf as $row) $counter['cust'][$row['id']] = $row['count'];
	}

	$nb_pages = ceil($counter['filter'] / $perpage);
	$page = $page < 0 ? 0 : ($page > $nb_pages ? $nb_pages - 1 : $page);
	$orders = $db->executeS('
		SELECT
			o.id_order AS id,
			o.id_shop AS shop,
            o.date_add,
			ok.id_till AS till,
			ok.delivery_mode AS mode,
			ok.preparation_status AS state,
			CONCAT_WS(" ", cu.firstname, cu.lastname) AS cust,
			ck.company AS company, 
			ca.name AS carrier,
			ok.delivery_date AS deliv,
			ok.display_date AS date,
			(SELECT SUM(od.product_quantity)
				FROM '._DB_PREFIX_.'order_detail od
				WHERE od.id_order = o.id_order) AS items,
			o.total_paid AS total,
			ok.is_paid AS is_paid,
			ok.id_till
		FROM '._DB_PREFIX_.'orders o
		LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
		LEFT JOIN '._DB_PREFIX_.'customer cu ON cu.id_customer = o.id_customer
		LEFT JOIN '._DB_PREFIX_.'carrier ca ON ca.id_carrier = o.id_carrier
		LEFT JOIN '._DB_PREFIX_.'customer_kerawen ck ON ck.id_customer = o.id_customer
		'.($condition ? ' WHERE '.$condition : '').'
		ORDER BY o.id_order DESC
		LIMIT '.pSQL($perpage).' OFFSET '.pSQL($perpage * $page));

	//TODO store KerAwen-valid status in db
	require_once(_KERAWEN_CLASS_DIR_.'/order_state.php');
	foreach ($orders as &$o) {
		$o['canceled'] = !isOrderStateValid($o['state']);
	}

	return array(
		'counter' => $counter,
		'page' => array(
			'num' => $page,
			'count' => $nb_pages,
		),
		'data' => $orders,
	);
}

function validateCart($context, $params, &$response)
{
	require_once(dirname(__FILE__).'/data.php');
	require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
	
	// Override tax based address while logging order
	// Only delivery is known at this moment
	Configuration::set('PS_TAX_ADDRESS_TYPE', 'id_address_delivery');

	$db = Db::getInstance();

	$id_cart = $params->id_cart;
	$id_cashdrawer = $params->id_cashdrawer;
	$id_employee = $context->employee->id;
	$payments = $params->payment;
	
	$cart = new Cart($id_cart);
	$customer = $cart->id_customer ? new Customer($cart->id_customer) : getAnonymousCustomer();
	$currency = new Currency($cart->id_currency);
	
	// Setup context for computations
	$context = Context::getContext();
	$context->cart = $cart;
	$context->shop = new Shop($cart->id_shop);

	$cartExtended = $db->getRow('SELECT * FROM '._DB_PREFIX_.'cart_kerawen WHERE id_cart=' . (int) $id_cart);
	if (!$cartExtended) {
		$cartExtended['quote'] = 0;
	}
	
	// Complete cart addresses because some modules require both
	if (!$cart->id_address_delivery) $cart->id_address_delivery = getDefaultDeliveryAddress(false);
	if (!$cart->id_address_invoice) $cart->id_address_invoice = $cart->id_address_delivery;
	
	// Get rid of invalid rules
	// IMPORTANT: DO IT AFTER CONTEXT COMPLETION !!!!
	// otherwise group reductions are cancelled in PS 1.6.0.9
	CartRule::autoRemoveFromCart();

	$order = null;
	$credit = null;
	if (Cart::getNbProducts($id_cart)) {
		// Create the order
		$order = new Order();
		require_once (dirname(__FILE__).'/data.php');

		$order->reference = Order::generateReference();
		$order->id_customer = $customer->id;
		$order->id_address_invoice = $cart->id_address_invoice;
		$order->id_address_delivery = $cart->id_address_delivery;
		$order->id_carrier = $cart->id_carrier;
		$order->id_currency = $cart->id_currency;
		$order->id_lang = $cart->id_lang;
		$order->id_cart = $cart->id;
		$order->id_shop = $context->shop->id;
		$order->id_shop_group = $context->shop->id_shop_group;

		$order->secure_key = getKerawenSecureKey();
		$order->payment = $context->module->displayName;
		$order->module = $context->module->name;

		$order->recyclable = $cart->recyclable;
		$order->gift = $cart->gift;
		$order->gift_message = $cart->gift_message;
		$order->mobile_theme = $cart->mobile_theme;
		$order->conversion_rate = $currency->conversion_rate;

		$order->invoice_date = date('Y-m-d H:i:s', time());
		$order->delivery_date = '0000-00-00 00:00:00';
		
		$order->total_products_wt = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
		$order->total_products = $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);

		$order->total_wrapping_tax_excl = $cart->getOrderTotal(false, Cart::ONLY_WRAPPING);
		$order->total_wrapping_tax_incl = $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
		$order->total_wrapping = $order->total_wrapping_tax_incl;

		if ($order->id_carrier) {
			$order->total_shipping_tax_excl = $cart->getOrderTotal(false, Cart::ONLY_SHIPPING);
			$order->total_shipping_tax_incl = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
			
			$carrier = new Carrier($order->id_carrier);
			if (!is_null($carrier) && Validate::isLoadedObject($carrier))
				$order->carrier_tax_rate = $carrier->getTaxesRate(new Address($order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
		}
		else {
			$order->total_shipping_tax_excl = 0;
			$order->total_shipping_tax_incl = 0;
		}
		$order->total_shipping = $order->total_shipping_tax_incl;

		/*
		 $order->total_discounts_tax_excl = (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
		 $order->total_discounts_tax_incl = (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
		*/
		
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		$reducs = discountAsArray($cart);
		
		// Warning: discounts may be greater than due (e.g. credit)
		$discount_te = Tools::ps_round($reducs['total_te'], _PS_PRICE_COMPUTE_PRECISION_);
		$discount_ti = Tools::ps_round($reducs['total_ti'], _PS_PRICE_COMPUTE_PRECISION_);
		$total_te = $order->total_products + $order->total_wrapping_tax_excl + $order->total_shipping_tax_excl;
		$total_ti = $order->total_products_wt + $order->total_wrapping_tax_incl + $order->total_shipping_tax_incl;
		if ($discount_te > $total_te) $discount_te = $total_te;
		if ($discount_ti > $total_ti) $discount_ti = $total_ti;
		
		$order->total_discounts_tax_excl = $discount_te;
		$order->total_discounts_tax_incl = $discount_ti;
		$order->total_discounts = $order->total_discounts_tax_incl;

		$order->total_paid_tax_excl = Tools::ps_round(
			$order->total_products - $order->total_discounts_tax_excl + $order->total_wrapping_tax_excl + $order->total_shipping_tax_excl,
			_PS_PRICE_COMPUTE_PRECISION_);
		$order->total_paid_tax_incl = Tools::ps_round(
			$order->total_products_wt - $order->total_discounts_tax_incl + $order->total_wrapping_tax_incl + $order->total_shipping_tax_incl,
			_PS_PRICE_COMPUTE_PRECISION_);
		$order->total_paid = $order->total_paid_tax_incl;
		$order->total_paid_real = 0.00;

		// Create order
		if (!$order->add())
			throw new PrestaShopException('Can\'t save order');

		// Detail order
		$od = new OrderDetail();
		$packagess = $cart->getPackageList();

		foreach($packagess as $packages)
			foreach($packages as $package)
				$od->createList($order, $cart, Configuration::get('PS_OS_DELIVERED'),
					$package['product_list'], 0, true, $package['id_warehouse']);
		
		// Carrier info
		if ($order->id_carrier) {
			$order_carrier = new OrderCarrier();
			$order_carrier->id_order = (int)$order->id;
			$order_carrier->id_carrier = (int)$order->id_carrier;
			$order_carrier->weight = $order->getTotalWeight();
			$order_carrier->shipping_cost_tax_excl = $order->total_shipping_tax_excl;
			$order_carrier->shipping_cost_tax_incl = $order->total_shipping_tax_incl;
			$order_carrier->add();
		}
		
		// Apply rules
		// EVOL Sort by expiration date
		$total_tax_incl = $order->total_paid_tax_incl + $order->total_discounts_tax_incl;
		$total_tax_excl = $order->total_paid_tax_excl + $order->total_discounts_tax_excl;
		$discount_tax_incl = 0;
		$discount_tax_excl = 0;
		$crs = $cart->getCartRules();
		foreach ($crs as &$cr) {
			$remain = $total_tax_incl - $discount_tax_incl;
			if ($remain <= 0) break;

			// (Re)load in multi-language mode to avoid errors when saving
			$rule = new CartRule($cr['id_cart_rule']);
			
			// Avoid shipping discount if no shipping!
			$free_shipping = $rule->free_shipping;
			if (!$order->id_carrier) $rule->free_shipping = false;
			
			$value_tax_incl = $rule->getContextualValue(true, $context, CartRule::FILTER_ACTION_ALL_NOCAP);
			$value_tax_excl = $rule->getContextualValue(false, $context, CartRule::FILTER_ACTION_ALL_NOCAP);

			// Reset free shipping to avoid change when cart rule is saved (available - 1)
			$rule->free_shipping = $free_shipping;
			
			if ($value_tax_incl > $remain) {
				$value_tax_incl = $remain;
				$value_tax_excl = $total_tax_excl - $discount_tax_excl;
			}
			$discount_tax_incl += $value_tax_incl;
			$discount_tax_excl += $value_tax_excl;

			// Register rule
			$order->addCartRule($rule->id, $rule->name[$order->id_lang], array(
				'tax_incl' => $value_tax_incl,
				'tax_excl' => $value_tax_excl,
			));

			// Consume rule
			$rule->quantity = max(0, $rule->quantity - 1);
			$rule->save();

			if ($rule->partial_use && ($rule->reduction_amount > $value_tax_incl)) {
				$type = $db->getValue('
					SELECT `type` FROM `'._DB_PREFIX_.'cart_rule_kerawen`
					WHERE `id_cart_rule` = '.pSQL($rule->id));

				if (Module::isInstalled('loyalty') && Module::isEnabled('loyalty')) {
					$id_loyalty = (int)$db->getValue('
						SELECT id_loyalty FROM `'._DB_PREFIX_.'loyalty`
						WHERE `id_cart_rule` = '.pSQL($rule->id));
					if ($id_loyalty) $type = _KERAWEN_CR_LOYALTY_;
				}

				// Recreate one with remaining
				require_once (dirname(__FILE__).'/cartrules.php');
				$id_parent_cart_rule = $rule->id;

				//rule code heritage or not
				$code_heritage = 1;
				if ($code_heritage) {
					$code = $rule->code;
					$rule->code = generateRuleCode();
					$rule->save();
				} else {
					$code = generateRuleCode();
				}
				
				unset($rule->id);
				$rule->code = $code;
				$rule->id_customer = $order->id_customer;
				$rule->quantity = 1;
				$rule->quantity_per_user = 1;
				$rule->free_shipping = 0;
				$rule->reduction_amount -= $value_tax_incl;

				//change label name
				if ($type == _KERAWEN_CR_GIFT_CARD_) {
					require_once (_KERAWEN_TOOLS_DIR_.'/utils.php');
					$rule->name = getForLanguages($context->module->l('Credit', pathinfo(__FILE__, PATHINFO_FILENAME)));
				}
				$rule->add();

				if ($type == _KERAWEN_CR_CREDIT_ || $type == _KERAWEN_CR_PREPAID_ || $type == _KERAWEN_CR_GIFT_CARD_) {
					$data = array(
						'id_cart_rule' => $rule->id,
						'type' => $type,
						'id_parent_cart_rule' => $id_parent_cart_rule,
					);
					if (!empty($order->id)) {
						$data['id_order'] = $order->id;
					}
					$db->insert('cart_rule_kerawen', $data);
				}
				else if ($type == _KERAWEN_CR_LOYALTY_) {
					// Keep it as loyalty
					$db->update('loyalty', array(
						'id_cart_rule' => $rule->id,
						'id_loyalty_state' => 4),
					'id_loyalty = '.pSQL($id_loyalty));
				}
				
				if ($type != _KERAWEN_CR_GIFT_CARD_) {
					$credit = array(
						'id' => $rule->id,
						'code' => $rule->code,
						'type' => $type,
						'name' => $rule->name[$context->language->id],
						'qty' => $rule->quantity_per_user,
						'mode' => 'amount',
						'value' => $rule->reduction_amount,
						'from' => $rule->date_from,
						'to' => $rule->date_to,
						'cust' => $customer ? array(
							'id' => $customer->id,
							'firstname' => $customer->firstname,
							'lastname' => $customer->lastname,
						) : null,
					);
				}
			}
		}

		$order->save();
		Hook::exec('actionValidateOrder', array(
			'cart' => $cart,
			'order' => $order,
			'customer' => $customer,
			'currency' => $currency,
			'orderStatus' => new OrderState(Configuration::get('KERAWEN_OS_RECEIVED'), $order->id_lang),
			'kerawen' => true,
		));
	}

	// Manage returns
	$id_slip_order = false;
	$slips = array();
	if ($returns = $db->executeS('
		SELECT * FROM '._DB_PREFIX_.'return_kerawen
		WHERE id_cart='.pSQL($id_cart)))
	{
		if ($cart->id_customer) {
			$customer = (object) array_merge( (array) $customer, array( 'addrs' => getAddresses($customer, (int) $cart->id_lang)) );
		}
	
		$total_refund_tax_incl = 0;
		$total_refund_tax_excl = 0;
	
		foreach ($returns as $return) {
			$id_slip_order = (int)$return['id_order'];
			$refund_ti = 0;
			$refund_te = 0;
				
			require_once(dirname(__FILE__).'/stock.php');
			$id_reason = getStockReturnReason();
	
			if (!isset($slips[$id_slip_order])) {
				// Create a new order slip for original order
				$slip = new OrderSlip();
				$slip->order = new Order($id_slip_order);
				$slip->id_order = $id_slip_order;
				$slip->id_customer = (int)$slip->order->id_customer;
	
				$slip->total_products_tax_excl = 0;
				$slip->total_products_tax_incl = 0;
				$slip->total_shipping_tax_incl = 0;
				$slip->total_shipping_tax_excl = 0;
				$slip->conversion_rate = $currency->conversion_rate;
				$slip->partial = 1;
	
				// Prepare slip details
				$slip->details = array();
	
				$slips[$id_slip_order] = $slip;
			}
			$slip = $slips[$id_slip_order];
				
			if ($return['id_order_detail']) {
				$order_detail = new OrderDetail($return['id_order_detail']);
				$tax_rate = $order_detail->unit_price_tax_incl / $order_detail->unit_price_tax_excl;
	
				// Return details
				$quantity = (int)$return['quantity'];
				$refund_ti = (float)$return['refund_tax_incl'];
				$refund_te = $refund_ti / $tax_rate;
	
				// Mark as refunded
				$order_detail->product_quantity_refunded += $quantity;
				$order_detail->save();
	
				// Trigger hook
				// IMPORTANT: requires returned quantity as POST/GET parameter (paypal module)
				$cq = array();
				$cq[$order_detail->id] = $quantity;
				$_POST['cancelQuantity'] = $cq;
				Hook::exec('actionProductCancel', array(
					'order' => $slip->order,
					'id_order_detail' => $return['id_order_detail'],
					'product_quantity' => $quantity,
				), null, false, true, false, $slip->order->id_shop);
	
				if (!isset($slip->details[$order_detail->id])) {
					$slip->details[$order_detail->id] = array(
						// SET LATER ON 'id_order_slip' => $slip->id,
						'id_order_detail' => $order_detail->id,
						'product_quantity' => 0,
						'amount_tax_excl' => 0,
						'amount_tax_incl' => 0,
					);
				}
				$slip_detail = &$slip->details[$order_detail->id];
				$slip_detail['product_quantity'] += $quantity;
				$slip_detail['amount_tax_excl'] += $refund_te;
				$slip_detail['amount_tax_incl'] += $refund_ti;
	
				$slip->total_products_tax_excl += $refund_te;
				$slip->total_products_tax_incl += $refund_ti;
	
				// Back to stock
				if ($return['back_to_stock']) {
					// Check if combination is still available
					if (!$order_detail->product_attribute_id ||
					$db->getValue('
							SELECT 1 FROM '._DB_PREFIX_.'product_attribute
							WHERE id_product = '.pSQL($order_detail->product_id).'
							AND id_product_attribute = '.pSQL($order_detail->product_attribute_id))) {
								// Quite easy to return in original shop/warehouse, less in the current one
					if ($order_detail->id_warehouse) {
						injectStock(
						$order_detail->product_id,
						$order_detail->product_attribute_id,
						$order_detail->id_warehouse,
						$order_detail->id_shop,
						$id_reason,
						$quantity,
						0);
					}
					else {
						StockAvailable::updateQuantity(
						$order_detail->product_id, $order_detail->product_attribute_id,
						$quantity, $cart->id_shop,
						// Log stock movement from PS 1.7
						true, array(
							'id_order' => $id_slip_order,
							'id_stock_mvt_reason' => Configuration::get('PS_STOCK_CUSTOMER_RETURN_REASON')
						));
					}
					}
				}
			}
			else {
				// Shipping refunding
				$refund_ti = (float)$return['refund_tax_incl'];
				$refund_te = $refund_ti / (1.0 + $slip->order->carrier_tax_rate / 100.0);
	
				$slip->total_shipping_tax_excl += $refund_te;
				$slip->total_shipping_tax_incl += $refund_ti;
			}
				
			// Complete slip for backward compatibility
			$slip->amount = $slip->total_products_tax_incl + $slip->total_shipping_tax_incl;
			$slip->shipping_cost_amount = $slip->total_shipping_tax_incl;
			$slip->shipping_cost = $slip->total_shipping_tax_incl > 0 ? 1 : 0;
	
			// Increment global refund
			$total_refund_tax_incl += $refund_ti;
			$total_refund_tax_excl += $refund_te;
		}
	
		// Save slips and details
		$productList = array();
		$qtyList = array();
		$buf = array();
	
		foreach ($slips as $slip) {
			$slip->save();
				
			foreach ($slip->details as &$slip_detail) {
				$slip_detail['id_order_slip'] = $slip->id;
				$qtyList[$slip_detail['id_order_detail']] = $slip_detail['product_quantity'];
	
				if (Tools::version_compare(_PS_VERSION_, '1.6.0.10', '>=')) {
					$slip_detail = array_merge($slip_detail, array(
						'unit_price_tax_excl' => $slip_detail['amount_tax_excl'] / $slip_detail['product_quantity'],
						'unit_price_tax_incl' => $slip_detail['amount_tax_incl'] / $slip_detail['product_quantity'],
						'total_price_tax_excl' => $slip_detail['amount_tax_excl'],
						'total_price_tax_incl' => $slip_detail['amount_tax_incl'],
					));
					$productList[] = array(
						'amount' => $slip_detail['amount_tax_incl'],
					);
					//TODO: MORE DATA REQUIRED ???
					//MINIMUM REQUIRED FOR FIDELISA
				}
				else {
					$productList[] = $slip_detail['id_order_detail'];
				}
					
				$db->insert('order_slip_detail', $slip_detail);
				$db->delete('return_kerawen', 'id_cart = '.pSQL($id_cart).'
					AND id_order_detail = '.pSQL($slip_detail['id_order_detail']));
			}
				
			$invoice_number = (int) $db->getValue('
				SELECT invoice_number FROM '._DB_PREFIX_.'orders
				WHERE id_order = '.pSQL($slip->id_order));
				
			//TODO Report slip details as order
			require_once (dirname(__FILE__).'/shop.php');
			$buf[] = array(
				'number' => sprintf('%06d', $slip->id),
				'amount' => -$slip->amount,
				'url_invoice' => getAdminLink('AdminPdf', array(
					'submitAction' => 'generateOrderSlipPDF',
					'id_order_slip' => $slip->id,
				)),
				'id' => $slip->id,
				'id_order' => $slip->id_order,
				'cust' => isAnonymousCustomer($customer) ? null : $customer,
				'language' => isset($customer->id_lang) ? $customer->id_lang : 1,
				'prods' => $slip->details,
				'flagAddress' => ($invoice_number > 0),
			);
	
			// Trigger hook
			Hook::exec('actionOrderSlipAdd', array(
				'order' => $slip->order,
				'productList' => $productList,
				'qtyList' => $qtyList,
				'order_slip' => $slip,
			));
				
			$refund = new stdClass();
			$refund->return = $slip->order;
			$refund->mode = false;
			$refund->label = $context->module->l('Exchanged products', pathinfo(__FILE__, PATHINFO_FILENAME));
			$refund->paid = -$slip->amount;
			array_unshift($payments, $refund);
		}
		$response->addResult('slips', $buf);
	
		// Register refunding as payment
		if ($total_refund_tax_incl > 0) {
			$refund = new stdClass();
			$refund->return = true;
			$refund->mode = false;
			$refund->label = $context->module->l('Exchanged products', pathinfo(__FILE__, PATHINFO_FILENAME));
			$refund->paid = $total_refund_tax_incl;
			array_unshift($payments, $refund);
		}
	}
	
	// Delete cart
	if (!$cartExtended['quote']) {
		require_once (dirname(__FILE__).'/cart.php');
		deleteCart($cart);
	}

	$credits = array();
	if ($credit) $credits[] = $credit;
	
	$id_os = null;
	if ($order) {
		// Set order state depending on mode
		// TODO make it configurable
		$more = $db->getRow('SELECT * FROM `'._DB_PREFIX_.'order_kerawen` WHERE id_order = '.pSQL($order->id));
		$mode = $more ? $more['delivery_mode'] : _KERAWEN_DM_DELIVERY_;
		$id_os = Configuration::get('PS_OS_PAYMENT');
		if ($mode == _KERAWEN_DM_IN_STORE_) $id_os = Configuration::get('PS_OS_DELIVERED');
	}
	
	changeOrderState($order, $id_os, $payments, $credits);
	$response->addResult('credits', $credits);
	
	if ($order) {
		// Now order can be returned (everything has been done)
		$params->id_order = $order->id;

		//!!! Need to be here as well (drawer.php -> updateOrderPaymentStatus())
		//Otherwise order is gotten before gift card is created and gift card is not on the receipt
		//Gift card shouldn't be created twice because "gift_card_flag" is checked
		require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
		setGiftCard($params->id_order, 'createCredit');

		$params->id_cust = $customer->id;
		$response->addResult('order', getOrder($context, $params));
	}
}

function handleOrderCreation($order, $cart, $state)
{
	$db = Db::getInstance();

	// Get additional data from cart
	$more = $db->getRow('SELECT * FROM `'._DB_PREFIX_.'cart_kerawen` WHERE id_cart = '.pSQL($cart->id));

	$mode = $more ? $more['delivery_mode'] : _KERAWEN_DM_DELIVERY_;
	$date = $more ? $more['delivery_date'] : null;
	if ($mode == _KERAWEN_DM_IN_STORE_) $date = date('Y-m-d H:i:s');
	//TODO remove when delivery date is required
	if ($mode == _KERAWEN_DM_DELIVERY_) $date = null;

	// Transform credits from rules to payments
	applyOrderCredits($order);
	
	// Register global discount by cart rules
	$discount = 0;
	$free_shipping = 0;
	$crs = $order->getCartRules();
	foreach ($crs as $cr)
	{
		$discount += $cr['value_tax_excl'];
		if ($cr['free_shipping'])
		{
			$free_shipping = 1;
			$discount -= $order->total_shipping_tax_excl;
		}
	}
	
	$db->insert('order_kerawen', array(
		'id_order' => $order->id,
		'id_till' => getExtendedContext('id_cashdrawer', 0),
		'id_employee' => $more ? $more['id_employee'] : null,
		'delivery_mode' => $mode,
		'delivery_date' => $date,
		'product_global_discount' => ($order->total_products == 0) ? 1 : ($discount / $order->total_products),
		'free_shipping' => $free_shipping,
		'preparation_status' => (int)Configuration::get('KERAWEN_OS_RECEIVED'),
		'round_type' => (int)Configuration::get('PS_ROUND_TYPE'),
	));

	// Register weights and measures
	completeOrderDetail($order);

	// Register ecotaxes
	registerEcotax($order);
	
	// Register order
	handleOrderState($order->id, $state, true);
	
	//Change cart quotation status as "not quotation"
	//$db->update('cart_kerawen', array('quote' => 0), 'id_cart = ' . pSQL($cart->id));
}

/**
* Change current order status
*
* @param int $id_order
* @param int $id_state
*/
function changeOrderState($order, $id_state, $payments, &$credits)
{
	if ($order && $id_state) {
		$os = new OrderState($id_state, $order->id_lang);

		// Override standard behaviour:
		// no invoice, shipping status may be overridden
		$os->invoice = false;
		if (isset($override['shipped'])) $os->shipped = $override['shipped'];
		// Do not save but force caching
		$cache_id = 'objectmodel_'.get_class($os).'_'.(int)$os->id.'_'.(int)null.'_'.(int)$order->id_lang;
		Cache::store($cache_id, $os);

		// Additional data for order state hook
		setExtendedContext('payments', $payments);
		setExtendedContext('credits', $credits);
		
		$oh = new OrderHistory();
		$oh->id_employee = isset($context->employee) ? $context->employee->id : null;
		$oh->id_order = $order->id;
		$oh->changeIdOrderState($id_state, $order, true);
		
		$db = Db::getInstance();
		$fakemail = 0;
		$fakemail = (int)$db->getValue('
			SELECT ck.fakemail FROM '._DB_PREFIX_.'customer_kerawen AS ck WHERE ck.id_customer = '.$order->id_customer); 
		if ((Configuration::get('KERAWEN_SEND_EMAIL')) && ($fakemail != 1)) {
			$oh->addWithemail();
		}
		else {
			$oh->add();
		}
		
		// Get results back
		$credits = getExtendedContext('credits', array());
	
		// Stock to be synchronized after state change
		// Override stock movement reason
		$id_reason = Configuration::get('KERAWEN_MR_SALE');
		if ($id_reason) Configuration::set('PS_STOCK_CUSTOMER_ORDER_REASON', $id_reason);
	
		if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
			$prods = $order->getProducts();
			foreach ($prods as $product)
			if (StockAvailable::dependsOnStock($product['product_id']))
				StockAvailable::synchronize($product['product_id'], $order->id_shop);
		}
	}
	elseif ($payments) {
		registerPayments($order, $payments, $credits);
	}
}

function changeOrderMode($id_order, $id_order_payment, $id_mode, $mode, $date_deferred = 0) 
{
	$db = Db::getInstance();
	
	setExtendedContext('id_payment_mode', $id_mode);
	$op = new OrderPayment($id_order_payment);
	$op->payment_method = $mode;
	$op->save();
	setExtendedContext('id_payment_mode', null);
	
	$flow_data = array(
		'id_payment_mode' => $id_mode
	);
	if ($date_deferred) {
		$flow_data['date_deferred'] = $db->getValue('SELECT NOW()');
	}
	$db->update('cashdrawer_flow_kerawen', $flow_data, 'id_order_payment = '.pSQL($id_order_payment));
}


function changeOrderCustomer($id_order, $id_customer)
{
	$db = Db::getInstance();
	
	//PS
	$op = new Order($id_order);
	$op->id_customer = $id_customer;
	$op->save();
	
	//525
	$id_sale = $db->getValue('SELECT id_sale FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order WHERE ps_order = '.pSQL($id_order));
	$customer_name = $db->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.pSQL($id_customer));
	if ($id_sale) {
		$db->execute('UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale SET id_customer = '.pSQL($id_customer).', customer_name = "'.pSQL($customer_name).'" WHERE id_sale = '.pSQL($id_sale));
	}
}


function handleOrderState($id_order, $os_new, $create)
{
	$db = Db::getInstance();
	
	$order = new Order($id_order);
	$os_curr = new OrderState($order->current_state);
	
	// Do not register payment automatically
	if ($order->payment == 'KerAwen') $os_new->paid = false;
	$cache_id = 'objectmodel_'.get_class($os_new).'_'.(int)$os_new->id.'_'.(int)null.'_'.(int)$order->id_lang;
	Cache::store($cache_id, $os_new);
	
	require_once (_KERAWEN_CLASS_DIR_.'/order_state.php');
	require_once (_KERAWEN_CLASS_DIR_.'/drawer.php');
	
	$new = $create || !$order->current_state;
	$previously_valid = !$create && isOrderStateValid($os_curr->id);
	$currently_valid = isOrderStateValid($os_new->id);
	$changed = false;
	
	/*
	// In case the order has been imported, it is not yet accounted
	if (!(int)$db->getValue('
		SELECT preparation_status FROM '._DB_PREFIX_.'order_kerawen
		WHERE id_order = '.pSQL($id_order))) {
		$new = true;
		$previously_valid = false;
	}
	*/
	
	if (!$previously_valid && $currently_valid)
	{
		// Order has been (re)validated
		logOrderState($order->reference, $order->id, _KERAWEN_CDSO_ORDER_, $new, $order->total_paid_tax_incl, $new ? 0 : $order->total_paid_real, $order->payment);
		$changed = true;
	}
	if ($previously_valid && !$currently_valid)
	{
		// Order has been canceled
		logOrderState($order->reference, $order->id, _KERAWEN_CDSO_CANCEL_, $new, $order->total_paid_tax_incl, $order->total_paid_real, null);
		$changed = true;
	}

	if ($changed)
	{
		$payments = getExtendedContext('payments', array());
		if (!$payments) $payments = array();
		setExtendedContext('payments', $payments);
		setExtendedContext('regul', false);
	}
	
	// Check and correct stock if necessary
	if (!$os_curr->shipped && $os_new->shipped) {
		require_once(dirname(__FILE__).'/stock.php');
		$id_reason = getStockShippingReason();
		
		foreach ($order->getProductsDetail() as $detail) {
			if ((int)$detail['id_warehouse']) {
				$available = (int)$db->getValue('
					SELECT SUM(usable_quantity)
					FROM '._DB_PREFIX_.'stock
					WHERE id_warehouse = '.pSQL($detail['id_warehouse']).'
					AND id_product = '.pSQL($detail['product_id']).'
					AND id_product_attribute = '.pSQL($detail['product_attribute_id']));
				$missing = (int)$detail['product_quantity'] - $available;

				if ($missing > 0) {
					injectStock(
						$detail['product_id'],
						$detail['product_attribute_id'],
						$detail['id_warehouse'],
						$detail['id_shop'],
						$id_reason,
						$missing,
						0);
				}
			}
		}
	}
}

function handlePostOrderState($id_order, $os_new)
{
	updateOrderStatus($id_order, isPreparationStatus($os_new->id) ? $os_new->id : null);

	// Register payments if any
	$payments = getExtendedContext('payments', false);
	if ($payments !== false)
	{
		$order = new Order($id_order);
		$credits = getExtendedContext('credits', array());
		registerPayments($order, $payments, $credits);
		
		// Avoid second registering
		setExtendedContext('payments', false);
		setExtendedContext('credits', $credits);
	}
	
	// Update prepared quantities (AFTER stock has been updated)
	if ($os_new->id == (int)Configuration::get('PS_OS_PREPARATION'))
	{
		$db = Db::getInstance();
		$db->execute('
			INSERT INTO '._DB_PREFIX_.'order_detail_kerawen (id_order_detail)
			SELECT od.id_order_detail FROM '._DB_PREFIX_.'order_detail od
				WHERE od.id_order = '.pSQL($id_order).'
			ON DUPLICATE KEY UPDATE id_order_detail = od.id_order_detail');
				
			
		$db->execute('
			UPDATE '._DB_PREFIX_.'order_detail_kerawen odk
			INNER JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = odk.id_order_detail
			SET odk.quantity_ordered = od.product_quantity,
				odk.measure_ordered = odk.measure
			WHERE od.id_order = '.pSQL($id_order).'
				AND odk.quantity_ordered IS NULL');
						
	}
	
}


function handleOrderSlip($slip)
{
	require_once (_KERAWEN_CLASS_DIR_.'/drawer.php');
	logOrderSlip(null /*$slip->order->reference*/, $slip->id_order, $slip->id);
	
	if (!getExtendedContext('kerawen', false)) {
		$amount = $slip->amount;
	}
}

function registerPayments($order, $payments, &$credits)
{
	$context = Context::getContext();
	$db = Db::getInstance();
	
	foreach ($payments as $p)
	{
		if (!isset($p->order)) $p->order = $order;
		if (!isset($p->return)) $p->return = false;
		
		$credit = false;
		if (isset($p->credit) && $p->credit) {
			
			if ($p->paid < 0) {
				// Generate credit
				require_once (dirname(__FILE__).'/cartrules.php');
				$id_customer = isset($order->id_customer) ? $order->id_customer : $context->customer->id;
				$credit = createCredit(-$p->paid, (int)$id_customer);
				$credits[] = $credit;
			}
			
			if ($p->paid > 0 && isset($p->id_rule)) {
				// Consume credit
				$db->execute('
					UPDATE '._DB_PREFIX_.'cart_rule
					SET quantity = quantity - 1
					WHERE id_cart_rule = '.pSQL($p->id_rule));
			}
		}
		addPayment($p->order, $p->return, $p->mode, $p->label, $p->paid, $credit ? $credit['id'] : null, isset($p->date) ? $p->date : null);
	}
	if ($order) $order->save();
}

function addPayment($order, $return, $mode, $label, $value, $id_credit = null, $date = null)
{
	$context = Context::getContext();
	$value = (float)Tools::ps_round($value, _PS_PRICE_COMPUTE_PRECISION_);

	$main_ref = $other_ref = null;
	if (!$return) {
		$main_ref = $order ? $order->reference : null;
	}
	else if ($return === true) {
		$main_ref = $order ? $order->reference : null;
		$other_ref = '--';
	}
	else {
		$other_ref = $return->reference;
	}
	
	$op = new OrderPayment();
	$op->order_reference = $main_ref;
	$op->id_currency = $order ? $order->id_currency : $context->currency->id;
	$op->conversion_rate = 1;
	$op->payment_method = $label;
	$op->amount = $value;
	$op->transaction_id = $id_credit;
	
	// Additional data for payment hook
	setExtendedContext('id_payment_mode', $mode);
	setExtendedContext('id_credit', $id_credit);
	setExtendedContext('date_deferred', $date);
	
	if (!$op->add())
		throw new PrestaShopException('Can\'t save payment');

	Db::getInstance()->insert('order_payment_kerawen', array(
		'id_order_payment' => $op->id,
		'reference' => $other_ref,
	), true);
	
	// Cancel data for payment hook
	setExtendedContext('id_payment_mode', null);
	setExtendedContext('id_credit', null);
	setExtendedContext('date_deferred', null);
	
	if ($order) {
		$order->total_paid_real += $value;
		// FIXME Maybe the payment has been registered on another order
		if ($order->total_paid_real < 0) $order->total_paid_real = 0;
	}
}

function handleOrderPayment($payment, $delete)
{
	require_once (_KERAWEN_CLASS_DIR_.'/drawer.php');
	
	$mode = getExtendedContext('id_payment_mode', null);
	if ($mode !== false)
	{
		if ($payment->amount == 0) {
			// Do not keep payment with no amount
			Db::getInstance()->delete('order_payment', 'id_order_payment = '.pSQL($payment->id));
		}
		else {
			// Get back additionnal data
			$id_order = Db::getInstance()->getValue('
				SELECT id_order FROM '._DB_PREFIX_.'orders
				WHERE reference = "'.pSQL($payment->order_reference).'"');
			if (!$id_order) $id_order = null;
			$date = getExtendedContext('date_deferred', null);
			$id_credit = getExtendedContext('id_credit', null);
			
			logPayment($payment->order_reference, $id_order, null, $payment->id, $mode, $payment->amount, $id_credit, $date, null, $delete);
		}
	}
}

/**
 * Transfers credits from reductions to payments
 * in order to compute taxes correctly
 */
function applyOrderCredits($order)
{
	$db = Db::getInstance();
	$credit_tax_excl = 0.0;
	$credit_tax_incl = 0.0;
	$orules = $order->getCartRules();

	$pmodes = array(
		_KERAWEN_CR_CREDIT_ => _KERAWEN_PM_CREDIT_,
		_KERAWEN_CR_PREPAID_ => _KERAWEN_PM_PREPAID_,
		_KERAWEN_CR_GIFT_CARD_ => _KERAWEN_PM_GIFT_CARD_,
	);
	
	foreach ($orules as $orule)
	{
		// Check if rule is a credit or prepaid
		$type = $db->getValue('
			SELECT `type`
			FROM '._DB_PREFIX_.'cart_rule_kerawen
			WHERE id_cart_rule = '.pSQL($orule['id_cart_rule']));
		
		if (isset($pmodes[$type])) {
			$pmode = $pmodes[$type];
			
			// Remove from rules
			$db->delete('order_cart_rule',
				'id_order_cart_rule = '.pSQL($orule['id_order_cart_rule']));
			$credit_tax_excl += $orule['value_tax_excl'];
			$credit_tax_incl += $orule['value'];

			// Find the corresponding cart_rule
			$row = $db->getRow('
				SELECT `reduction_amount`, `code` FROM `'._DB_PREFIX_.'cart_rule`
				WHERE `id_cart_rule`='.pSQL($orule['id_cart_rule']));
			$amount_init = $row['reduction_amount'];
			$code = $row['code'].'-';
			$amount = $orule['value'];

			// Guess if the rule has been split
			// FIXME
			$eps = 0.009;
			if ($amount_init >= ($amount - $eps) && $amount_init <= ($amount + $eps))
				addPayment($order, false, $pmode, $orule['name'], $amount, $orule['id_cart_rule']);
			else
			{
				addPayment($order, false, $pmode, $orule['name'], $amount_init, $orule['id_cart_rule']);
				addPayment($order, false, $pmode, $orule['name'], $amount - $amount_init);
				try
				{
					$id_ps_rule = $db->getValue('
						SELECT `id_cart_rule` FROM `'._DB_PREFIX_.'cart_rule`
						WHERE `code` LIKE "'.pSQL($code).'%"');

					//the order comes from the front office. We rename the cart rule's remainder and set it as kerawen credit
					if (!empty($id_ps_rule))
					{
						require_once(dirname(__FILE__).'/cartrules.php');
						$rule = new CartRule($id_ps_rule);
						$rule->code = generateRuleCode();
						//set as kerawen credit/prepaid
						$db->execute('
							INSERT INTO '._DB_PREFIX_.'cart_rule_kerawen
							(id_cart_rule, `type`)
							VALUES ('.pSQL($rule->id).', "'.pSQL($type).'")');
						$rule->save();
					}
				}
				catch (Exception $e)
				{
					// order from the cash register
					die('Exception caught '.$e);
				}
			}
		}
	}

	// Reset order totals
	$order->total_discounts_tax_excl = Tools::ps_round($order->total_discounts_tax_excl - $credit_tax_excl, _PS_PRICE_COMPUTE_PRECISION_);
	$order->total_discounts_tax_incl = Tools::ps_round($order->total_discounts_tax_incl - $credit_tax_incl, _PS_PRICE_COMPUTE_PRECISION_);
	
	// TODO Fix rounding issue
	if ($order->total_discounts_tax_excl < 0.0) {
		$credit_tax_excl += $order->total_discounts_tax_excl;
		$order->total_discounts_tax_excl = 0.0;
	}
	if ($order->total_discounts_tax_incl < 0.0) {
		$credit_tax_incl += $order->total_discounts_tax_incl;
		$order->total_discounts_tax_incl = 0.0;
	}
	
	$order->total_discounts = $order->total_discounts_tax_incl;
	$order->total_paid_tax_excl = Tools::ps_round($order->total_paid_tax_excl + $credit_tax_excl, _PS_PRICE_COMPUTE_PRECISION_);
	$order->total_paid_tax_incl = Tools::ps_round($order->total_paid_tax_incl + $credit_tax_incl, _PS_PRICE_COMPUTE_PRECISION_);
	$order->total_paid = $order->total_paid_tax_incl;
	
	// Update order detail taxes
	if (Tools::version_compare(_PS_VERSION_, '1.6.1', '<')) {
		foreach ($order->getOrderDetailList() as $row) {
			$detail = new OrderDetail($row['id_order_detail']);
			$detail->updateTaxAmount($order);
		}
	}
	else {
		$order->updateOrderDetailTax();
	}
	
	if (!$order->save())
		throw new PrestaShopException('Can\'t save order');
}

function completeOrderDetail($order)
{
	$db = Db::getInstance();
	$tax_address = new Address((int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
	
	foreach ($order->getOrderDetailList() as $od)
	{
		$more = $db->getRow('
			SELECT
				ps.id_tax_rules_group
			FROM '._DB_PREFIX_.'product_shop ps
			WHERE ps.id_product = '.pSQL($od['product_id']).'
			AND ps.id_shop = '.pSQL($od['id_shop']));
		
		// Weigths and measures
		$wm = $db->getRow('
			SELECT
				pak.measure AS measure,
				pk.unit AS unit,
				pk.`precision` AS `precision`,
				ck.unit_price AS unit_price,
				ck.id_product_attribute AS id_product_attribute
			FROM '._DB_PREFIX_.'product_attribute_kerawen pak
			JOIN '._DB_PREFIX_.'product_attribute pa
				ON pa.id_product_attribute = pak.id_product_attribute
			JOIN '._DB_PREFIX_.'product p
				ON p.id_product = pa.id_product
			JOIN '._DB_PREFIX_.'product_wm_kerawen pk
				ON pk.id_product = p.id_product
			LEFT JOIN '._DB_PREFIX_.'product_wm_code_kerawen ck
				ON ck.id_code = pak.id_code 
			WHERE pak.id_product_attribute = '.pSQL($od['product_attribute_id']));
		
		if ($wm && $wm['measure'] && $wm['unit_price'] > 0) {
			$tax_manager = TaxManagerFactory::getManager($tax_address, $more['id_tax_rules_group']);
			$tax_calculator = $tax_manager->getTaxCalculator();
			$reduction = 1 - $od['group_reduction'] / 100;
			
			$more = array_merge($more, array(
				'measure' => $wm['measure'],
				'unit' => $wm['unit'],
				'precision' => $wm['precision'],
				'unit_price_tax_excl' => $wm['unit_price']*$reduction,
				'unit_price_tax_incl' => $tax_calculator->addTaxes($wm['unit_price'])*$reduction,
			));
			
			if ($wm['id_product_attribute'] > 0) {
				// Decrement related standard combination
				$db->update('order_detail', array(
					'product_attribute_id' => $wm['id_product_attribute'],
				), 'id_order_detail = '.pSQL($od['id_order_detail']));
			}

			// Update wholesale data
			$update = '
				purchase_supplier_price = purchase_supplier_price*'.pSQL($wm['measure']);
			if (Tools::version_compare(_PS_VERSION_, '1.6.1.1', '>=')) $update .= '
				, original_wholesale_price = original_wholesale_price*'.pSQL($wm['measure']);
			
			$db->execute('
				UPDATE '._DB_PREFIX_.'order_detail SET '.$update.'
				WHERE id_order_detail = '.pSQL($od['id_order_detail']));
		}
		
		// Notes, specific price from cart
		$result = $db->getRow('
			SELECT
				note, specific_price_cart
			FROM '._DB_PREFIX_.'cart_product_kerawen
			WHERE id_cart = '.pSQL($order->id_cart).'
				AND id_product = '.pSQL($od['product_id']).'
				AND id_product_attribute = '.pSQL($od['product_attribute_id']));
		if ($result) {
			if ($result['note'] != '') {
				$more['note'] = pSQL($result['note']);
			}
			$more['specific_price_cart'] = $result['specific_price_cart'];
		}

		//VAT margin module
		if (Module::isInstalled('vatmargin') && Module::isEnabled('vatmargin')) {
			
			$is_vat_margin = (int) $db->getValue('
				SELECT vat_margin
				FROM '._DB_PREFIX_.'product
				WHERE id_product = '.pSQL($od['product_id'])
			);
			
			if ($is_vat_margin) {
				$more['margin_vat'] = 1;
			}
			
		}

		if (count($more)) {
			$more['id_order_detail'] = $od['id_order_detail'];
			$db->insert('order_detail_kerawen', $more);
		}
	}
}

/**
 * Register ecotax for each product in order
 */
function registerEcotax($order)
{
	$db = Db::getInstance();

	$buf = $db->executeS('
		SELECT od.id_order_detail, p.ecotax AS ecotax_prod, pa.ecotax AS ecotax_attr
		FROM '._DB_PREFIX_.'order_detail od
		JOIN '._DB_PREFIX_.'product_shop p
			ON p.id_product = od.product_id AND p.id_shop = od.id_shop
		LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pa 
			ON pa.id_product_attribute = od.product_attribute_id AND pa.id_shop = od.id_shop
		WHERE od.id_order = '.pSQL($order->id));
}

/**
* Returns order status
*
* @param int $id_order
*/
function getCurrentOrderStatus($params)
{
	$id_order = $params->id_order;

	$order = new Order($id_order);
	return $order->getCurrentState();
}

/**
* Returns a list of existing order status
*
* @param int $id_order
*/
function getExistingOrderStatus($id_lang)
{
	static $order_status = array();

	if (! isset($order_status[$id_lang]))
	{

		$order_states = OrderState::getOrderStates($id_lang);
		$order_status[$id_lang] = array();
		foreach ($order_states as $os)
		{
			$order_status[$id_lang][$os['id_order_state']] = array(
				'id' => $os['id_order_state'],
				'text' => $os['name'],
				'color' => $os['color']
			);
		}
	}

	return $order_status[$id_lang];
}

/**
* Returns the textual value of an order status id
*
* @param type $id_status
* @return string
*/
function getTextualStatus($id_lang, $id_status)
{
	$status_list = getExistingOrderStatus($id_lang);
	if (isset($status_list[$id_status]))
		return $status_list[$id_status]['text'];

	return '';
}

/**
* Returns the color value of an order status id
*
* @param type $id_status
* @return string
*/
function getColorStatus($id_lang, $id_status)
{
	$status_list = getExistingOrderStatus($id_lang);
	if (isset($status_list[$id_status]))
		return $status_list[$id_status]['color'];

	return '';
}

function getTextualStatusList($id_lang, $params)
{
	$status_list = $params->status_list;

	$return_list = array();
	foreach ($status_list as $id_status)
	{
		$return_list[] = array(
			'id' => $id_status,
			'text' => getTextualStatus($id_lang, $id_status),
			'color' => getColorStatus($id_lang, $id_status)
		);
	}
	return $return_list;
}

function getPreparationStatus($id_lang, $params)
{
	$oh = getOrderHistory($id_lang, $params->id_order);
	$params->status_list = getRegisterValue('statusPreparation');
	$params->default_status = getRegisterValue('statusPreparationDefault');
	$params->order_history = $oh;
	return getFirstStatusHistory($id_lang, $params);
}

/**
*
* @param Context $context
* @param stdClass $params
*/
function getOrderHistory($id_lang, $id_order)
{
	$o = new Order($id_order);
	return $o->getHistory($id_lang, false, true /* No cache*/);
}

/**
 * Update order status in kerawen table
 *
 * @param int $id_order
 * @param int $preparation_status
 */
function updateOrderStatus($id_order, $preparation_status = null)
{
	$db = Db::getInstance();
	if ($preparation_status)
	{
		$db->execute('INSERT INTO `'._DB_PREFIX_.'order_kerawen`
					(`id_order`, `preparation_status`)
				VALUES ('.pSql($id_order).', '.pSql($preparation_status).')
				ON DUPLICATE KEY UPDATE
					`preparation_status` = VALUES(`preparation_status`);');

		$date = date('Y-m-d H:i:s');
		if ($preparation_status != Configuration::get('PS_OS_DELIVERED'))
			$date = $db->getValue('SELECT delivery_date
				FROM `'._DB_PREFIX_.'order_kerawen`
				WHERE id_order = '.pSQL($id_order));
		$db->update('order_kerawen', array(
			'display_date' => $date,
		), 'id_order = '.pSQL($id_order));
	}
}

function isPreparationStatus($status_id)
{
	$status = getRegisterValue('statusPreparation');
	return in_array($status_id, $status);
}

/**
* internal
*/
function getFirstStatusHistory($id_lang, $params)
{
	$status_mapping = $params->status_list;
	$oh = $params->order_history;
	$default_status = $params->default_status;

	foreach ($oh as &$o)
	{
		if (in_array($o['id_order_state'], $status_mapping))
		{
			//$color = $o['color'];
			// Compatibility with PrestaShop 1.5
			$os = new OrderState($o['id_order_state']);
			$color = $os->color;

			return array(
				'id' => $o['id_order_state'],
				'text' => $o['ostate_name'],
				'color' => $color,
			);
		}
	}
	unset($o);
	$params->id_status = $default_status;
	return array(
		'id' => $default_status,
		'text' => getTextualStatus($id_lang, $default_status),
		'color' => getColorStatus($id_lang, $default_status)
	);
}
function getRegisterValue($key)
{
	static $mapping = null;

	if (null === $mapping)
	{
		// EVOL mapping in config file or parameters
		$mapping = array(
			'statusPreparation' => array(
				Configuration::get('KERAWEN_OS_RECEIVED'),
				Configuration::get('PS_OS_ERROR'),
				Configuration::get('PS_OS_OUTOFSTOCK'),
				Configuration::get('PS_OS_PREPARATION'),
				Configuration::get('KERAWEN_OS_READY'),
				Configuration::get('PS_OS_SHIPPING'),
				Configuration::get('PS_OS_DELIVERED'),
				Configuration::get('PS_OS_CANCELED'),
			),
			'statusPreparationDefault' => Configuration::get('KERAWEN_OS_RECEIVED'),

			'statusPayment' => array(
				Configuration::get('PS_OS_CHEQUE'),
				Configuration::get('PS_OS_BANKWIRE'),
				Configuration::get('PS_OS_PAYPAL'),
				Configuration::get('PS_OS_WS_PAYMENT'),
				Configuration::get('PS_OS_PAYMENT'),
				Configuration::get('PS_OS_REFUND'),
			),
			'statusPaymentDefault' => Configuration::get('PS_OS_CHEQUE')
		);
	}
	return $mapping[$key];
}

/**
*
* @param Context $context
* @param stdClass $params
* @return \OrderBean
*/
function getOrder($context, $params)
{
	
	require_once(dirname(__FILE__).'/order_state.php');
	require_once(dirname(__FILE__).'/cartrules.php');
	require_once(dirname(__FILE__).'/shop.php');
	
	$id_order = $params->id_order;
	$id_lang = $context->language->id;
	$db = Db::getInstance();

	$order = new Order($id_order);

	// Customer
	$cust = null;
	if ($order->id_customer && ($order->id_customer != Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER'))) {
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		$cust = getCustomer($order->id_customer, $id_lang, '');
	}

	// Products
	$link = new Link();
	$images = array();
	
	$prods = array();
	$nb_prod = 0;

	$pp = array();
	$products = $order->getProductsDetail();
	foreach ($products as $row) {	
		// Backward compatibility 1.4 -> 1.5
		$order->setProductPrices($row);
		$pp[(int)$row['id_order_detail']] = $row;
	}
	
	foreach ($pp as &$p) {
		// Backward compatibility PrestaShop 1.5
		if (!isset($p['id_product'])) $p['id_product'] = $p['product_id'];
		if (!isset($p['id_product_attribute'])) $p['id_product_attribute'] = $p['product_attribute_id'];
		
		$img = null;
		if ($p['id_product'])
		{
			// Additional data for image
			$link_rewrite = $db->getValue('
				SELECT link_rewrite FROM `'._DB_PREFIX_.'product_lang`
				WHERE id_product = '.pSQL($p['id_product']).'
				AND id_lang = '.pSQL($id_lang));
			$cover = Product::getCover($p['id_product']);
			$img = $cover['id_image'] ? '//'.$link->getImageLink($link_rewrite, $cover['id_image'], 'home_default') : null;
			$images[$p['id_product']] = $img;
		}

		$id_order_detail = $p['id_order_detail'];
		$returned_quantity = $db->getValue('
			SELECT SUM(`quantity`) FROM `'._DB_PREFIX_.'return_kerawen`
			WHERE `id_order_detail` = '.pSQL($id_order_detail).' AND `took_effect` = 0');
		$ps_qties = $db->getRow('
			SELECT `product_quantity_return`, `product_quantity_refunded`
			FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order_detail` = '.pSQL($id_order_detail));
		$returned_quantity += $ps_qties['product_quantity_return'] + $ps_qties['product_quantity_refunded'];

		$more = $db->getRow('
			SELECT *
			FROM '._DB_PREFIX_.'order_detail_kerawen
			WHERE id_order_detail = '.pSQL($id_order_detail));

		$reduc_amount = (float) $p['reduction_amount'];		
		$reduc_percent = 1 - ( (float) $p['reduction_percent'] / 100);
			
		$prod = array(
			'id' => $id_order_detail,
			'prod' => $p['id_product'],
			'attr' => $p['id_product_attribute'],
			'name' => $p['product_name'],
			'ref' => $p['product_reference'],
			'qty' => (int)$p['product_quantity'],
			'ret' => $returned_quantity,
			'qord' => isset($more['quantity_ordered']) ? (int)$more['quantity_ordered'] : null,
			'mord' => isset($more['measure_ordered']) ? (float)$more['measure_ordered'] : null,
			'prepared' => isset($more['prepared']) ? (boolean)$more['prepared'] : false,
			'ecotax' => $p['ecotax'] * (1 + $p['ecotax_tax_rate'] / 100),
			'unit' => (float) $p['product_price_wt'],
			'total' => (float) $p['total_wt'],
			//'unit_excl' => (float) $p['product_price'],
			//'total_excl' => (float) $p['total_price'],
			'tax_rate' => (float) $p['tax_rate'],
			'img' => $img,
			'reduction_percent' => (float) $p['reduction_percent'],
			'reduction_amount' => (float) $p['reduction_amount'],
			'unit_init' => (float) ($reduc_percent == 0) ? 0 : $p['product_price_wt'] / $reduc_percent + $reduc_amount,
			'total_init' => (float) ($reduc_percent == 0) ? 0 : $p['total_wt'] / $reduc_percent + ( $reduc_amount * (int) $p['product_quantity'] ),
		);
		
		if ($more) {
			$prod['note'] = $more['note'];
			
			if (isset($more['measure'])) {
				$prod['wm'] = array(
					'measure' => (float)$more['measure'],
					'precision' => (int)$more['precision'],
					'unit' => $more['unit'],
					'price' => (float)$more['unit_price_tax_incl'],
				);
			}
		}
		
		$prods[] = $prod;
		$nb_prod += $p['product_quantity'];
	}

	// Vouchers
	$vouchers = array();
	$cart_rules = $order->getCartRules();
	foreach ($cart_rules as &$r)
	{
		$voucher = array(
			'id_reduc' => $r['id_cart_rule'],
			'name' => $r['name'],
			'reduc' => -(float)$r['value'],
		);
		$vouchers[] = $voucher;
	}

	// Payments
	$payments = $db->executeS('
		SELECT DISTINCT
			op.id_order_payment AS id,
			op.payment_method AS mode,
			op.amount AS amount,
			op.date_add AS date,
			cf.date_deferred AS deferred,
			cf.id_payment_mode AS id_mode
		FROM '._DB_PREFIX_.'order_payment op
		LEFT JOIN '._DB_PREFIX_.'order_payment_kerawen opk ON opk.id_order_payment = op.id_order_payment
		LEFT JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen cf ON cf.id_order_payment = op.id_order_payment
		WHERE op.order_reference = "'.pSQL($order->reference).'"
			OR opk.reference = "'.pSQL($order->reference).'"
		ORDER BY op.id_order_payment ASC');

	$payment_mode = $order->payment;
	if ($payment_mode == $context->module->displayName && !count($payments))
		$payment_mode = $context->module->l('Deferred payment', pathinfo(__FILE__, PATHINFO_FILENAME));
	
	// Associated orders
	$link_orders = $db->executeS('
		SELECT
			o.id_order AS id,
			o.total_paid_tax_incl AS total,
			ok.preparation_status AS status
		FROM '._DB_PREFIX_.'orders o
		LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
		WHERE o.reference = "'.pSQL($order->reference).'"');
	foreach ($link_orders as &$o) {
		$o['canceled'] = !isOrderStateValid($o['status']);
	}
	$link_orders = indexArray($link_orders, 'id');
	
	// Status
	$preparation_status = getPreparationStatus($context->language->id, $params);
	$s = array(
		'id' => $preparation_status['id'],
		'text' => $preparation_status['text'],
		'color' => $preparation_status['color'],
	);
	
	$canceled = !isOrderStateValid($preparation_status['id']);
	// TODO on application side + order states mapping
	$action = getOrderAction($preparation_status['id']);
	
	$params->status_list = getRegisterValue('statusPreparation');
	$statuses = array();
	foreach (getTextualStatusList($context->language->id, $params) as $status)
	{
		$res_date = $db->getRow('
			SELECT oh.`date_add`
			FROM `'._DB_PREFIX_.'order_history` oh
			WHERE oh.`id_order` = '.(int)$id_order.' AND oh.`id_order_state` = '.(int)$status['id'].'
			ORDER BY oh.`date_add` DESC, oh.`id_order_history` DESC');
		
		$status_date = $res_date ? $res_date['date_add'] : null;
		if (!$status_date && (
				$status['id'] == $preparation_status['id'] ||
				$status['id'] == Configuration::get('KERAWEN_OS_RECEIVED')))
			$status_date = $order->date_add;

		$statuses[] = array(
			'id' => $status['id'],
			'text' => $status['text'],
			'color' => $status['color'],
			'date' => $status_date,
		);
	}

	// Extended data
	$extended = $db->getRow('
		SELECT * FROM `'._DB_PREFIX_.'order_kerawen`
		WHERE `id_order` = '.$order->id);
	
	// Employee
	$id_employee = $extended ? $extended['id_employee'] : 0;
	
	// Delivery
	$delivery_mode = $extended ? $extended['delivery_mode'] : _KERAWEN_DM_DELIVERY_;
	$delivery_address = null;
	if ($delivery_mode == _KERAWEN_DM_DELIVERY_)
	{
		$delivery_address = $db->getRow('
			SELECT
				a.firstname AS firstname,
				a.lastname AS lastname,
				a.company AS company,
				a.vat_number AS vat_number,
				a.address1 AS address1,
				a.address2 AS address2,
				a.postcode AS postcode,
				a.city AS city,
				cl.name AS country,
				a.phone AS phone,
				a.phone_mobile AS mobile
			FROM '._DB_PREFIX_.'address a
			LEFT JOIN '._DB_PREFIX_.'country_lang cl
				ON cl.id_country = a.id_country AND cl.id_lang = '.pSQL($id_lang).'
			WHERE a.id_address = '.pSQL($order->id_address_delivery));
	}
	
	// Shop
	$shop = new Shop($order->id_shop);

	// Returns
	$returns = array();
	$rr = $db->executeS('
		SELECT id_order_slip FROM '._DB_PREFIX_.'order_slip
		WHERE id_order = '.pSQL($order->id));
	
	// Backward compatibility
	$return_price = Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<=') ? 'amount' : 'total_price';
	
	foreach ($rr as $r) {
		$slip = new OrderSlip($r['id_order_slip']);

		// Backward compatibility
		if (Tools::version_compare(_PS_VERSION_, '1.6.1.0', '<=')) {
			$total_te = $slip->shipping_cost_amount/(1 + $order->carrier_tax_rate/100);
			$total_ti = $slip->shipping_cost_amount;
			$shipping_ti = $slip->shipping_cost_amount;
		}
		else {
			$total_te = $slip->total_shipping_tax_excl;
			$total_ti = $slip->total_shipping_tax_incl;
			$shipping_ti = $slip->total_shipping_tax_incl;
		}
		
		$ret_prods = $db->executeS(
			'SELECT
				od.product_id AS id_prod,
				od.product_attribute_id AS id_attr,
				od.product_name AS name,
				od.product_reference AS ref,
				osd.product_quantity AS qty,
				-osd.'.$return_price.'_tax_incl AS total,
				-osd.'.$return_price.'_tax_excl AS total_te
			FROM '._DB_PREFIX_.'order_slip_detail osd
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = osd.id_order_detail
			WHERE osd.id_order_slip = '.pSQL($slip->id));
		
		foreach ($ret_prods as &$p) {
			$p['img'] = isset($images[$p['id_prod']]) ? $images[$p['id_prod']] : null;
			$total_te -= $p['total_te'];
			$total_ti -= $p['total'];
		}

		// Backoffice links
		$url_invoice = getAdminLink('AdminPdf', array(
			'submitAction' => 'generateOrderSlipPDF',
			'id_order_slip' => $slip->id,
		));

		$returns[$slip->id] = array(
			'id' => $slip->id,
			'id_order' => $slip->id_order,
			'date' => $slip->date_add,
			'total' => -$total_ti,
			'tax' => -($total_ti - $total_te),
			'url_invoice' => $url_invoice,
			'products' => $ret_prods,
			'shipping' => -$shipping_ti,
		);
	}
		
	// Wrapping
	$wrapping = null;
	if ($order->total_wrapping_tax_incl > 0) {
		$wrapping = array(
			'price' => $order->total_wrapping_tax_incl,
		);
	}
	
	// Shipping
	$shipping = null;
	$ship_taxes = array();
	if ($order->id_carrier)
	{
		$carrier = new Carrier($order->id_carrier);
		
		$refunded = $db->getValue('
			SELECT SUM(refund_tax_incl) FROM '._DB_PREFIX_.'return_kerawen
			WHERE id_order = '.pSQL($order->id).' AND id_order_detail = 0');
		if (!$refunded) $refunded = 0;
		foreach ($returns as $r)
			$refunded += -$r['shipping'];

		$shipping = array(
			'carrier' => $carrier->name,
			'price' => $order->total_shipping_tax_incl,
			'refunded' => $refunded,
		);

		$ctx = new Context();
		$ctx->shop = $shop;
		$calc = $carrier->getTaxCalculator(new Address($order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
		$ship_taxes = $calc->getTaxesAmount($order->total_shipping_tax_excl);
	}
	

	$url_invoice = getAdminLink('AdminPdf', array(
		'submitAction' => 'generateInvoicePDF',
		'id_order' => $order->id,
		'id_lang' => $order->id_lang,
	));

	$url_delivery = getAdminLink('AdminPdf', array(
	    'submitAction' => 'generateDeliverySlipPDF',
	    'id_order' => $order->id,
	    'id_lang' => $order->id_lang,
	));
	

	// Taxes detail
	$taxes = array();
	$buf = $db->executeS('
		SELECT
			odt.`id_tax` AS id,
			SUM(odt.`total_amount`) AS tax,
			SUM(od.`total_price_tax_excl`*(1 - IFNULL(ok.product_global_discount,0))) AS base
		FROM '._DB_PREFIX_.'order_detail_tax odt
		JOIN '._DB_PREFIX_.'order_detail od
			ON odt.id_order_detail = od.id_order_detail
		JOIN '._DB_PREFIX_.'order_kerawen ok
			ON ok.id_order = od.id_order
		WHERE od.id_order = '.$order->id.'
		GROUP BY odt.`id_tax`');
	foreach ($buf as $t) $taxes[$t['id']] = $t;

	foreach ($ship_taxes as $id_tax => $amount)
	{
		if (!isset($taxes[$id_tax])) $taxes[$id_tax] = array(
			'id' => $id_tax,
			'tax' => 0,
			'base' => 0,
		);
		$taxes[$id_tax]['tax'] += $amount;
		$taxes[$id_tax]['base'] += $order->total_shipping_tax_excl;
	}
	
	$messages = array();
	$msgs = CustomerThread::getCustomerMessages($order->id_customer, null, $order->id);
	foreach ($msgs as $msg) {
		$messages[] = array(
			'priv' => (boolean) $msg['private'],
			'id_empl' => (int) $msg['id_employee'],
			'text' => $msg['message'],
			'id' => (int) $msg['id_customer_message'],
		);
	}

	// Gather
	$ob = array(
		'id' => $order->id,
		'id_shop' => $order->id_shop,
		'id_cart' => $order->id_cart,
		'language' => $order->id_lang,
		'id_empl' => $id_employee,
		'ref' => $order->reference,
		'shipping_num' => $order->shipping_number,
		'delivery_date' => ($order->delivery_date == '0000-00-00 00:00:00') ? '' : $order->delivery_date,
		'delivery_num' => $order->delivery_number,
		'store' => $shop->name,
		'date' => $order->date_upd,
		'mode' => $delivery_mode,
		'delivery_address' => $delivery_address,
		'dateInvoice' => $order->invoice_date,
		'dateDelivery' => $extended ? $extended['delivery_date'] : null,
		'dateDisplay' => $extended ? $extended['display_date'] : null,
		'invoice_note' => $extended ? $extended['invoice_note'] : null,
		'cust' => $cust,
		'web' => $order->payment != $context->module->displayName,
		'prods' => $prods,
		'vouchers' => $vouchers,
		'wrapping' => $wrapping,
		'shipping' => $shipping,
		'nbProds' => $nb_prod,
		'state' => getKerawenOrderState($s['id']),
		'status' => $s,
		'canceled' => $canceled,
		'action' => $action,
		'statusAvailable' => $statuses,
		'total' => $order->total_paid_tax_incl,
		'base' => $order->total_paid_tax_excl,
		'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
		'payment_mode' => $payment_mode,
		'payment' => $payments,
		'paid' => $order->total_paid_real,
		'is_paid' => $extended['is_paid'],
		'url_invoice' => $url_invoice,
	    'url_delivery' => $url_delivery,
		'taxes' => $taxes,
		'messages' => $messages,
		'links' => $link_orders,
		'gift_card' => getGiftCardByTicket($id_order, 0),
		'returns' => $returns,
		'flagAddress' => ( (int) $order->invoice_number > 0),
	);
	

	return $ob;
}

function setInvoiceAddress($order, $address, $id_lang = false)
{
	$order->id_address_invoice = $address->id;
	if ($id_lang) { $order->id_lang = $id_lang; }
	$order->setInvoice(true);
}

function adjustOrderQty($order, $id_detail, $qty)
{
	$db = Db::getInstance();

	$od = new OrderDetail($id_detail);
	$prev_te = $od->total_price_tax_excl;
	$prev_ti = $od->total_price_tax_incl;
	
	// Update order detail
	$od->product_quantity = $qty;
	$od->total_price_tax_excl = $qty * $od->unit_price_tax_excl;
	$od->total_price_tax_incl = $qty * $od->unit_price_tax_incl;
	$od->save();
	$od->updateTaxAmount($order);
	
	// Update order total
	$delta_te = $od->total_price_tax_excl - $prev_te;
	$delta_ti = $od->total_price_tax_incl - $prev_ti;
	$order->total_products += $delta_te;
	$order->total_products_wt += $delta_ti;
	$order->total_paid_tax_excl += $delta_te;
	$order->total_paid_tax_incl += $delta_ti;
	$order->total_paid = $order->total_paid_tax_incl;
	$order->save();
	
	// Mark as prepared
	$db->update('order_detail_kerawen', array(
		'prepared' => true,
	), 'id_order_detail = '.pSQL($id_detail));
}

function adjustOrderNote($params)
{

	if ($params->invoice_note == true) {
		setInvoiceNote((int) $params->id_order, $params->note);
	} else {

		$db = Db::getInstance();
		
		//product note
		if ($params->id_detail !== false) {
		
			$db->execute('
				INSERT INTO '._DB_PREFIX_.'order_detail_kerawen
				(id_order_detail, note) VALUES (' . pSQL($params->id_detail) . ', "' . pSQL($params->note) . '")
				ON DUPLICATE KEY UPDATE note = "' . pSQL($params->note) . '"
			');
		
		}
		
		//order note
		if ($params->id_customer_message !== false) {
			
			$id_customer_message = (int) $params->id_customer_message;
			$note = $params->note;
			$id_employee = $params->id_empl;
			$ip = (int)ip2long($_SERVER['REMOTE_ADDR']);
	
			$cm = new CustomerMessage(($id_customer_message == 0) ? false : $id_customer_message);
			
			if ($id_customer_message == 0) {			
				
				$id_customer = (int) $db->getValue('SELECT id_customer FROM '._DB_PREFIX_.'orders WHERE id_order = ' . (int) $params->id_order);
				$ct = getCustomerThread($params->id_order, $id_customer);
	
				$cm->id_customer_thread = $ct->id;
				$cm->private = 1;
			}
			
			//fixe bug empty message
			$cm->message = $note . ' ';
			$cm->ip_address = $ip;
			$cm->id_employee = $id_employee;
			$cm->save();
	
		}
		
	}
		
}

function setInvoiceNote($id_order, $note)
{
	$db = Db::getInstance();

	$db->execute('
		INSERT INTO '._DB_PREFIX_.'order_kerawen
		(id_order, invoice_note) VALUES (' . pSQL($id_order) . ', "' . pSQL($note) . '")
		ON DUPLICATE KEY UPDATE invoice_note = "' . pSQL($note) . '"
	');	
}


//first time only
function addOrderNote($id_order, $note, $id_employee, $id_customer)
{
	$db = Db::getInstance();
	
	$ct = getCustomerThread($id_order, $id_customer);
		
		//Private message already exists ?
	$id_customer_message = (int)Db::getInstance()->getValue('
		SELECT cm.`id_customer_message` AS id_customer_message
		FROM `'._DB_PREFIX_.'customer_message` cm
		LEFT JOIN `'._DB_PREFIX_.'customer_thread` ct
		ON ct.`id_customer_thread` = cm.`id_customer_thread`
		WHERE ct.id_order = ' . (int) $id_order . ' AND cm.`private` = 1
		GROUP BY cm.`id_customer_thread`, cm.`id_customer_message`
		ORDER BY cm.`id_customer_message` DESC
	');
		
	if ($id_customer_message) {
	
		$cm = new CustomerMessage($id_customer_message);
		//fixe bug empty message
		$cm->message = $note . ' ';
		$cm->ip_address = (int)ip2long($_SERVER['REMOTE_ADDR']);
		$cm->id_employee = $id_employee;
		$cm->update();
	
	} else {
	
		$cm = new CustomerMessage();
		$cm->id_customer_thread = $ct->id;
		//fixe bug empty message
		$cm->message = $note . ' ';
		$cm->ip_address = (int)ip2long($_SERVER['REMOTE_ADDR']);
		$cm->id_employee = $id_employee;
		$cm->private = 1;
		$cm->add();
			
	}
		
}


function getCustomerThread($id_order, $id_customer) {

	$db = Db::getInstance();
	$context = Context::getContext();
	
	if ($id_customer === false) {
		$customer = getAnonymousCustomer();
		$id_customer = $customer->id;
	} else {
		$customer = new Customer($id_customer);
	}
		

	//thread
	$id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $id_order);
	
	if (!$id_customer_thread) {
	
		$ct = new CustomerThread();
		$ct->id_contact = 0;
		$ct->id_customer = (int)$customer->id;
		$ct->id_shop = (int)$context->shop->id;
	
		$ct->id_order = (int)$id_order;
		$ct->id_lang = (int)$context->language->id;
		$ct->email = $customer->email;
		$ct->status = 'closed';
		$ct->token = Tools::passwdGen(12);
		$ct->add();
	
	} else {
	
		$ct = new CustomerThread((int)$id_customer_thread);
		$ct->status = 'closed';
		$ct->update();
	
	}
	
	return $ct;
}


