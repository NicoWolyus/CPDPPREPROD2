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

// Warning: false means OK...
function checkCart($id_cart, $version, $module)
{
	$db = Db::getInstance();
	if (!$db->getValue('
			SELECT id_cart FROM '._DB_PREFIX_.'cart
			WHERE id_cart = '.pSQL($id_cart)))
		return $module->l('Previous cart has been deleted', pathinfo(__FILE__, PATHINFO_FILENAME));
	if ($db->getValue('
			SELECT id_cart FROM '._DB_PREFIX_.'orders
			WHERE id_cart = '.pSQL($id_cart)))
		return $module->l('Previous cart has been checked out', pathinfo(__FILE__, PATHINFO_FILENAME));
	if (!$db->getValue('
			SELECT id_cart FROM '._DB_PREFIX_.'cart_kerawen
			WHERE id_cart = '.pSQL($id_cart).'
			AND version = '.pSQL($version)))
		return $module->l('Previous cart has been modified', pathinfo(__FILE__, PATHINFO_FILENAME));
	return false;
}

/*
 * Applies delivery options to cart
 * Automatically override carriers if the given one is not applicable
 * Returns list of applicable carriers for the given address
 */
function setDelivery($cart, $mode, $id_address = null, $carrier = null, $date = null)
{
	Context::getContext()->cart = $cart;
	$db = Db::getInstance();

	$delivery = array();
	if (isset($mode))
	{
		$delivery['delivery_mode'] = $mode;
		if ($date) $delivery['delivery_date'] = $date;
		if ($id_address) $delivery['id_address'] = $id_address;
		if ($carrier) $delivery['carrier'] = $carrier;
	}
	else
	{
		// Reset
		$delivery['id_address'] = 0;
		$delivery['carrier'] = null;
	}
	$db->update('cart_kerawen', $delivery, 'id_cart = '.pSQL($cart->id));
}

function setDeliveryAddress($cart, $id_address)
{
	$cart->id_address_delivery = $id_address;
	Db::getInstance()->update('cart_product', array(
		'id_address_delivery' => $cart->id_address_delivery,
	), 'id_cart = '.pSQL($cart->id));
}

/* TO REFACTOR */
function resetCart($context, &$params, &$response)
{
	$id_lang = $context->language->id;
	$id_curr = $context->currency->id;
	$id_empl = $context->employee->id;

	$id_cart = $params->id_cart;
	$suspend = $params->suspend;
	
	$id_next = $params->id_next;
	$id_shop = $params->id_shop;
	$id_cust = $params->id_cust;

	if ($id_cart) {
		if ($warning = checkCart($id_cart, $params->version, $context->module)) {
			$response->addMessage('warning', $warning);
		}
		else {
			if ($suspend)
				Db::getInstance()->update('cart_kerawen', array(
					'suspended' => 1,
				), 'id_cart = '.pSQL($id_cart));
			else
				deleteCart(new Cart($id_cart));
		}
	}

	$cart = null;
	if ($id_next) {
		setCartEmployee($id_next, $id_empl);
		$cart = new Cart($id_next);
		// Change customer to the cart owner
		$params->id_cust = $id_cust = $cart->id_customer;
		// New version of cart
		$cart->kerawen_version = time();
	}
	else
	{
		$id_new = createCart($id_shop, $id_empl, $id_cust, $id_lang, $id_curr);
		$cart = new Cart($id_new);
	}
	
	// Goto cart shop
	$context->shop = new Shop($cart->id_shop);
	Shop::setContext(Shop::CONTEXT_SHOP, $cart->id_shop);
	
	$response->addResult('cart', cartAsArray($cart));
	$response->addResult('carts', getSuspendedCarts());
	require_once (dirname(__FILE__).'/customer.php');
	$response->addResult('cust', getCustomer($id_cust, $id_lang, 'all'));
	require_once (dirname(__FILE__).'/cartrules.php');
	$response->addResult('rules', getCartRules($id_cust, $id_lang));
	
	//fixe cache issue PS 1.5.6.1 (le-chatel-des-vivaces)
	if ($id_next && Tools::version_compare(_PS_VERSION_, '1.6.0.0', '<=')) {
	    $response->addResult('cart', cartAsArray(new Cart($id_next)));
	}
	

}

function createCart($id_shop, $id_empl, $id_cust, $id_lang, $id_curr)
{
	$cart = new Cart();
	$cart->id_shop = $id_shop;
	$cart->id_customer = $id_cust;
	$cart->id_lang = $id_lang;
	$cart->id_currency = $id_curr;
	
	$cart->save();
	$id_cart = $cart->id;

	Db::getInstance()->insert('cart_kerawen', array(
		'id_cart' => $id_cart,
		'id_employee' => $id_empl,
		'delivery_mode' => 0,
		'version' => time(),
	));
	
	return $id_cart;
}

function selectShop($context, $params, &$response)
{
	$id_cart = $params->id_cart;
	$id_shop = $params->id_shop;

	// Update database references
	$db = Db::getInstance();

	$sql = 'UPDATE `'._DB_PREFIX_.'cart` SET
			id_shop = '.pSQL($id_shop).'
			WHERE id_cart = '.pSQL($id_cart);
	$db->execute($sql);

	$sql = 'UPDATE `'._DB_PREFIX_.'cart_product` SET
			id_shop = '.pSQL($id_shop).'
			WHERE id_cart = '.pSQL($id_cart);
	$db->execute($sql);

	$cart = new Cart($id_cart);
	$response->addResult('cart', cartAsArray($cart));
}

function setCartEmployee($id_cart, $id_empl)
{
	$db = Db::getInstance();
	$db->execute('
		UPDATE `'._DB_PREFIX_.'cart_kerawen`
		SET id_employee = '.pSQL($id_empl).'
		WHERE id_cart = '.pSQL($id_cart));
}

function updateCart(&$cart, $version)
{
	$db = Db::getInstance();
	
	if (!$version) $version = $db->getValue('
			SELECT version
			FROM '._DB_PREFIX_.'cart_kerawen
			WHERE id_cart = '.pSQL($cart['id']));
	$cart['version'] = $version; 

	$db->update('cart_kerawen', array(
		'suspended' => 0,
		'count' => $cart['count_cart'] + $cart['count_ret'],
		'total' => $cart['total_cart'] + $cart['total_ret'],
		'version' => $version,
	), 'id_cart = '.pSQL($cart['id']));
}

function getSuspendedCarts()
{
	$sql = 'SELECT
				ca.id_cart AS id,
				ck.version AS version,
				ca.date_upd AS date,
				CONCAT(cu.firstname, " ", cu.lastname) AS cust,
				ca.id_shop AS id_shop,
				e.id_employee AS id_empl,
				CONCAT(e.firstname, " ", e.lastname) AS empl,
				ck.count AS count,
				ck.total AS total,
				ck.suspended AS suspended
			FROM '._DB_PREFIX_.'cart_kerawen AS ck
			JOIN '._DB_PREFIX_.'cart AS ca ON ca.id_cart = ck.id_cart
			LEFT JOIN '._DB_PREFIX_.'customer AS cu ON cu.id_customer = ca.id_customer
			LEFT JOIN '._DB_PREFIX_.'employee AS e ON e.id_employee = ck.id_employee
			WHERE ck.quote != 1 AND (ck.suspended = 1 OR (ck.suspended = 0 AND ca.date_upd > NOW() - INTERVAL 7 DAY ))
			ORDER BY ck.version ASC';

	return Db::getInstance()->executeS($sql);
}


function getCartQuantityByProduct($id_cart, $id_prod, $id_attr, $id_custom)
{
	if ($id_custom) {
		$q = '
			SELECT quantity FROM '._DB_PREFIX_.'customization
			WHERE id_cart = '. pSQL($id_cart).'
			AND id_customization = '.pSQL($id_custom);
	}
	else {
		$q = '
			SELECT quantity FROM '._DB_PREFIX_.'cart_product
			WHERE id_cart = '. pSQL($id_cart).'
			AND id_product = '. pSQL($id_prod).'
			AND id_product_attribute = '.pSQL($id_attr);
	}
	return (int) Db::getInstance()->getValue($q);
}


function deleteCart($cart)
{
	if (!$cart->id) return;
	
	// Clean-up related elements
	$db = Db::getInstance();
	$db->delete('cart_kerawen', 'id_cart = '.pSQL($cart->id));
	
	// Returns
	$db->delete('return_kerawen', 'id_cart = '.pSQL($cart->id));

	// Cart discounts
	$buf = $db->executeS('
		SELECT `id_cart_rule` FROM `'._DB_PREFIX_.'cart_rule_kerawen`
		WHERE `id_cart` = '.pSQL($cart->id));
	foreach ($buf as $rule) {
		$rule = new CartRule($rule['id_cart_rule']);
		// Keep it as inactive in order it cannot be reused
		$rule->active = false;
		$rule->save();
	}
	$db->delete('cart_rule_kerawen', 'id_cart = '.pSQL($cart->id));

	// Specific combinations
	$buf = $db->executeS('
		SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute_kerawen`
		WHERE `id_cart` = '.pSQL($cart->id));
	foreach ($buf as $comb) {
		$comb = new Combination($comb['id_product_attribute'], null, $cart->id_shop);
		$comb->delete();
	}
	$db->delete('product_attribute_kerawen', 'id_cart = '.pSQL($cart->id));
	
	
	$db->delete('cart_product_kerawen', 'id_cart = '.pSQL($cart->id));
	
	// Specific prices
	$db->delete('specific_price', 'id_cart = '.pSQL($cart->id));
	
	$cart->delete();
}

function adjustItem($context, $params, &$response)
{
	$db = Db::getInstance();
	$cart = getCart($params->id_cart, $params->id_cust, $context);
	$context->cart = $cart;
	
	$id_prod = $params->id_prod;
	$id_lang = Configuration::get('PS_LANG_DEFAULT');
	$id_shop = $context->shop->id;
	
	// Load product with full context in order to activate same cache as Cart
	$prod = new Product($id_prod, false, $id_lang, $id_shop);

	// Create attribute if needed
	if (isset($params->price)) {
		$id_code = isset($params->id_code) ? $params->id_code : null;
		$id_attr = createProductPrice($cart, $prod, $id_code, $params->price);
	}
	else {
		$id_attr = (int)($params->id_attr ? $params->id_attr : $prod->getDefaultIdProductAttribute());
	}
	
	// Create customization if needed ?
	$id_custom = isset($params->id_custom) ? $params->id_custom : false;

	$initial_qty = getCartQuantityByProduct($params->id_cart, $params->id_prod, $id_attr, $id_custom);
	$minimal_qty = ($id_attr > 0) ? Attribute::getAttributeMinimalQty($id_attr) : $prod->minimal_quantity;
	if (!$minimal_qty) $minimal_qty = 1;

	$set = isset($params->set) ? $params->set : false;
	$qty = (int)$params->qty + ($set ? 0 : $initial_qty);
	
	$res = true;
	if ($qty == 0) {
		if ($id_attr == -1) {
			// Remove any combination of product
			//TODO Improve
			$attrs = Db::getInstance()->executeS('
				SELECT id_product_attribute, quantity
				FROM '._DB_PREFIX_.'cart_product
				WHERE id_cart = '.pSQL($cart->id).'
				AND id_product = '.pSQL($prod->id));
			if (count($attrs)) {
				$shop = new Shop($cart->id_shop);
				foreach ($attrs as $attr)
					$cart->updateQty($attr['quantity'], $prod->id, $attr['id_product_attribute'], false, 'down', 0, $shop);
			}
		}
		else {
			$cart->deleteProduct($prod->id, $id_attr, $id_custom);
		}
	}
	else {
		$diff = $qty - $initial_qty;
		$op = $diff < 0 ? 'down' : 'up';
		$diff = round(abs($diff), 0);
		
		// Check product status and quantities
		$outofstock = null;
		if (isset($params->force) && $params->force) {
			$sa = new StockAvailable(StockAvailable::getStockAvailableIdByProductId($prod->id, $id_attr));
			$outofstock = $sa->out_of_stock;
			StockAvailable::setProductOutOfStock($prod->id, true, $cart->id_shop, $id_attr);
		}
		
		$available = null;
		if (isset($params->force) && $params->force) {
			$available = $prod->available_for_order;
			// Update cache for Cart
			$cache_id = 'objectmodel_Product_'.(int)$id_prod.'_'.(int)$id_shop.'_'.(int)$id_lang;
			$data = Cache::retrieve($cache_id);
			$data['available_for_order'] = true;
			Cache::store($cache_id, $data);
		}
		
		if ($diff) {
			// Create custom if needed
			if (!$id_custom && isset($params->custom) && $params->custom) {
				$custom = new Customization();
				$custom->id_cart = $cart->id;
				$custom->id_product = $prod->id;
				$custom->id_product_attribute = $id_attr;
				$custom->id_address_delivery = $cart->id_address_delivery;
				$custom->quantity = 0;
				$custom->quantity_refunded = 0;
				$custom->quantity_returned = 0;
				$custom->in_cart = 1;
				$custom->save();
				$id_custom = $custom->id;
				
				foreach($params->custom as $field) {
					if (isset($field->value)) {
						$db->insert('customized_data', array(
							'id_customization' => $id_custom,
							'index' => $field->id_customization_field,
							'type' => $field->type,
							'value' => $field->value,
						));
					}
				}
			}
			
			if (Tools::version_compare(_PS_VERSION_, '1.7.3', '<')) {
				$res = $cart->updateQty($diff, $prod->id, $id_attr, $id_custom,
					$op, 0, new Shop($cart->id_shop));
			}
			else {
				$res = $cart->updateQty($diff, $prod->id, $id_attr, $id_custom,
					$op, 0, new Shop($cart->id_shop), true, isset($params->force) && $params->force);
			}
		}
		
		$r = $cart->containsProduct($prod->id, $id_attr);
		if ($r) {
			$sp = SpecificPrice::getIdsByProductId($params->id_prod, $id_attr, $params->id_cart);
			if (count($sp)) {
				$discountCart = -1;
				$calc = 0;
				$final_qty = (int) $r['quantity'];
				
				$id_specific_price = $sp[0]['id_specific_price'];
				$specificPrice = new SpecificPrice($id_specific_price);
				
				if ( $final_qty > 0 ) {
					$cpk = $db->getRow('SELECT specific_price_cart, specific_price_cart_calc FROM '._DB_PREFIX_.'cart_product_kerawen WHERE id_cart = ' . pSQL($params->id_cart) . ' AND id_product = ' . pSQL($params->id_prod) . ' AND id_product_attribute = ' . pSQL($id_attr));
					if ($cpk) {
						$calc = (int) $cpk['specific_price_cart_calc'];
						$coef = ($specificPrice->reduction_type == 'percentage') ? 100 : 1;
						$discountCart = $specificPrice->reduction*$coef*$initial_qty*(float)$cpk['specific_price_cart'];
					}
				}
				
				//give more weight to priority specific price
				$specificPrice->from_quantity = $final_qty;
				$specificPrice->update();
				//auto clear cache

				if ($discountCart > -1 && $calc) {
					$reduction_type = ($specificPrice->reduction_type == 'percentage') ? 'percent' : $specificPrice->reduction_type;
					setItemPrice($specificPrice->id_cart, $specificPrice->id_product, $specificPrice->id_product_attribute,  $reduction_type, $discountCart, $calc);
				}
			}
		}
		else {
			$db->delete('cart_product_kerawen',
				'id_cart = '.pSQL($params->id_cart).'
				AND id_product = '.pSQL($params->id_prod).'
				AND id_product_attribute = '.pSQL($id_attr));
			//clean specific price if product gone
			SpecificPrice::deleteByIdCart($params->id_cart, $params->id_prod, $id_attr);
			//remove from cart_product_kerawen as well !important -> clear all specific price data
			$db->delete('cart_product_kerawen', 'id_cart = '. pSQL($params->id_cart) . ' AND id_product = ' . pSQL($params->id_prod) . ' AND id_product_attribute = ' . pSQL($id_attr) );
		}
		
		// Restore product configuration
		if ($outofstock !== null)
			StockAvailable::setProductOutOfStock($prod->id, $outofstock, $cart->id_shop, $id_attr);
	}

	if ($res !== true) {
		// Determine error case
		if ($res < 0)
			$response->addResult('minimal_qty', $minimal_qty);
		elseif (!$prod->available_for_order)
			$response->addResult('notavailable', true);
		else
			$response->addResult('outofstock', true);
	}
	else
	{
		$bean = cartAsArray($cart);
		$response->addResult('cart', $bean);
		$response->addResult('added', array(
			'id_prod' => $prod->id,
			'id_attr' => $id_attr,
			'id_custom' => $id_custom,
		));
	}
}


function addRule($context, $params, &$response)
{
	$id_cart = $params->id_cart;
	$id_rule = $params->id_rule;
	$id_lang = $context->language->id;

	if ($id_cart && $id_rule)
	{
		$cart = new Cart($id_cart);
		$rule = new CartRule($id_rule);

		if ($cart->id_customer)
		{
			// Check it's the same customer
			if ($rule->id_customer && $rule->id_customer != $cart->id_customer)
			{
				$response->addMessage('error', $context->module->l('Different customer', pathinfo(__FILE__, PATHINFO_FILENAME)));
				return;
			}
		}
		else if ($rule->id_customer)
		{
			// Set the customer for cart
			$cart->id_customer = $rule->id_customer;
			$cart->save();

			require_once (dirname(__FILE__).'/customer.php');
			$response->addResult('cust', getCustomer($cart->id_customer, $id_lang));
		}

		$context->cart = $cart;
		$cart->addCartRule($id_rule);
		
		$response->addResult('cart', cartAsArray($cart));

		// Update available cart rules
		require_once (dirname(__FILE__).'/cartrules.php');
		$response->addResult('rules', getCartRules($cart->id_customer, $id_lang));
	}
}

function remRule($context, $params, &$response)
{
	$id_cart = $params->id_cart;
	$id_rule = $params->id_rule;
	$id_lang = $context->language->id;

	if ($id_cart)
	{
		$cart = new Cart($id_cart);
		$cart->removeCartRule($id_rule);

		// Delete the rule if cart restricted
		$db = Db::getInstance();
		if ($db->getValue('
			SELECT id_cart FROM `'._DB_PREFIX_.'cart_rule_kerawen`
			WHERE type != \'' . _KERAWEN_CR_GIFT_CARD_ . '\' AND `id_cart_rule` = '.pSQL($id_rule)))
		{
			$rule = new CartRule($id_rule);
			$rule->delete();
			$db->delete('cart_rule_kerawen',
				'`id_cart_rule` = '.pSQL($id_rule));
		}

		$response->addResult('cart', cartAsArray($cart));

		// Update available cart rules
		require_once (dirname(__FILE__).'/cartrules.php');
		$response->addResult('rules', getCartRules($cart->id_customer, $id_lang));
	}
}

/*
 *  Create a product combination with the given price
*/
function createProductPrice($cart, $prod, $id_code, $price)
{
	$db = Db::getInstance();
	$context = Context::getContext();
		
	if (!$context->group->price_display_method || $id_code) {
		$price = round($price / (1.0 + $prod->getTaxesRate() / 100.0), 6);
	}

	$DefaultIdProductAttribute = (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
		SELECT pa.`id_product_attribute`
		FROM `'._DB_PREFIX_.'product_attribute` pa
		INNER JOIN '._DB_PREFIX_.'product_attribute_shop product_attribute_shop ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute)
		WHERE pa.`id_product` = '.(int)$prod->id.'
		AND product_attribute_shop.default_on = 1'
	);
	
	
	// Ensure default combination exists
	//if (!$prod->getDefaultIdProductAttribute()) {
	if ($DefaultIdProductAttribute == 0) {
		$id_attr_default = $prod->addCombinationEntity(0.0, 0.0, 0.0, 0.0, 0.0, 0,
				null, null, null, null, true,
				null, null, 1,
				Shop::getShops(false, null, true));
	}
	
		
	//with/without vat
	
	// Create combination
	$id_attr = $prod->addCombinationEntity(0.0, $price, 0.0, 0.0, 0.0, 0,
		null, null, null, null, false,
		null, null, 1, array($cart->id_shop));
	
	StockAvailable::setQuantity($prod->id, $id_attr, 0, $cart->id_shop);
	StockAvailable::setProductOutOfStock($prod->id, true, $cart->id_shop, $id_attr);
	
	// Limit usage of combination to current cart
	$more = array(
		'id_product_attribute' => $id_attr,
		'id_cart' => $cart->id,
	);
	
	// Weight or measure
	if ($id_code) {
		require_once(dirname(__FILE__).'/catalog.php');
		$wm = getProductWeightsAndMeasures($prod->id, false);
		if ($wm && $wm['measured']) {
			$unit_price = $db->getValue('
				SELECT unit_price
				FROM '._DB_PREFIX_.'product_wm_code_kerawen
				WHERE id_code = '.pSQL($id_code));
			$more = array_merge($more, array(
				'id_code' => $id_code,
				'measure' => $unit_price > 0 ? Tools::ps_round($price/$unit_price, (int)$wm['precision']) : null,
			));
		}
	}
	
	$db->insert('product_attribute_kerawen', $more); 
	return $id_attr;
}


function setItemCartPrice($id_cart, $id_prod, $id_attr, $value = -1) {

	$db = Db::getInstance();
	
	$context = Context::getContext();
	$id_shop = $context->shop->id;
	$id_lang = $context->language->id;

	if (isset($context->group)) {
		$id_group = $context->group->id;
		$reduction_group = $context->group->reduction;
	} else {
		$id_group = 1;
		$reduction_group = Group::getReductionByIdGroup($id_group);
	}	

	$reduction_group_category = GroupReduction::getValueForProduct($id_prod, $id_group);
	$display_method = (int) Group::getPriceDisplayMethod($id_group);
	
	if ( $value > -1 ) { 
		$prod = new Product($id_prod, true, $id_lang, $id_shop);
		$vat = $display_method ? 1 : (1.0 + $prod->getTaxesRate() / 100.0);
		
		$vat_offset = 0;
		$vat_margin = isset($prod->vat_margin) ? $prod->vat_margin : 0;
		if ($vat_margin && isset($prod->wholesale_price)) {
			$vat_offset = $prod->wholesale_price;
		}
		
		if ($reduction_group_category) {
			$spec_rate = 1 / ( 1 - $reduction_group_category );
		} else {
			$spec_rate = 1 / ( 1 - $reduction_group / 100 );
		}

		$value = round((($value - $vat_offset) / $vat + $vat_offset) * $spec_rate, 6);
	}
	
	$id_spec = false;
	$sp = SpecificPrice::getIdsByProductId($id_prod, $id_attr, $id_cart);
	if (count($sp)) {
		$id_spec = $sp[0]['id_specific_price'];
	}
	
	$specificPrice = new SpecificPrice($id_spec);
	$specificPrice->id_shop = $id_shop; //!important
	$specificPrice->id_cart = $id_cart;
	$specificPrice->id_product = $id_prod;
	$specificPrice->id_product_attribute = $id_attr;
	$specificPrice->reduction_tax = 1;
	
	$specificPrice->id_shop_group = 0;
	$specificPrice->id_currency = 0;
	$specificPrice->id_country = 0;
	$specificPrice->id_group = 0;
	$specificPrice->id_customer = 0;
	$specificPrice->from = '0000-00-00 00:00:00';
	$specificPrice->to = '0000-00-00 00:00:00';
	
	$specificPrice->from_quantity = 1;
	$specificPrice->reduction_type = 'amount';
	$specificPrice->reduction = 0;
	
	$specificPrice->price = $value;
	$specificPrice->save();
	

	$values_cpk = array();
	$values_cpk['specific_price_redefined'] = 1;	
	$values_cpk['id_cart'] = $id_cart;
	$values_cpk['id_product'] = $id_prod;
	$values_cpk['id_product_attribute'] = $id_attr;
	
	$where = ' id_cart = ' . pSQL($id_cart) . ' AND id_product = ' . pSQL($id_prod) . ' AND id_product_attribute = ' . pSQL($id_attr);
	$cpk = (int) $db->getValue('SELECT 1 FROM '._DB_PREFIX_.'cart_product_kerawen WHERE ' . $where);
	if ($cpk) {
		$db->update('cart_product_kerawen', $values_cpk, $where);
	} else {
		$db->insert('cart_product_kerawen', $values_cpk);
	}
	
}


function setItemPrice($id_cart, $id_prod, $id_attr, $type, $value, $calc = 0)
{
	$db = Db::getInstance();
	
	$context = Context::getContext();
	$id_lang = $context->language->id;
	$id_customer = $context->customer->id;
	$id_shop = $context->shop->id;

	$id_currency = $context->currency->id;
	$id_country = $context->country->id;
	
	if (isset($context->group)) {
		$id_group = $context->group->id;
		$reduction_group = $context->group->reduction;		
	} else {
		$id_group = 1;
		$reduction_group = Group::getReductionByIdGroup($id_group);
	}

	$reduction_group_category = GroupReduction::getValueForProduct($id_prod, $id_group);
	$display_method = (int) Group::getPriceDisplayMethod($id_group);
	
	$where = ' id_cart = ' . pSQL($id_cart) . ' AND id_product = ' . pSQL($id_prod) . ' AND id_product_attribute = ' . pSQL($id_attr);
	
	//Discount only one product or all products
	if ($calc) {		
		$quantity = getCartQuantityByProduct($id_cart, $id_prod, $id_attr, null);
		if ($quantity > 1) {
			$value = $value / $quantity;
		}
	}
	
	$cart = new Cart($id_cart);
	$res = $cart->containsProduct($id_prod, $id_attr);
	$quantity = $res ? $res['quantity'] : 1;

	$prod = new Product($id_prod, true, $id_lang, $id_shop);

	$vat = (1.0 + $prod->getTaxesRate() / 100.0);
	
	$value = ($display_method && $type == 'amount') ? ($value * $vat) : $value;
	

	$init_price = $prod->getPriceStatic($prod->id, true, $id_attr, 6, null, false, false, 1, false, $id_customer);	
	$init_price_vat_excl = round($init_price / $vat, 6);
	
	
	$spec_rate = 1;
	$spec_price = -1;
	//$spec_price = $init_price_vat_excl;

	
	$specific = SpecificPrice::getSpecificPrice($prod->id, $id_shop, $id_currency, $id_country, $id_group, 1, $id_attr, $id_customer);
	
	$price = Product::priceCalculation(
			$id_shop, $prod->id, $id_attr, $id_country, null, null, $id_currency,
			$id_group, 1, true, 2, false, true, true, $specific, true, $id_customer, true, 0, 0);

		
	//fix bug cart by using absolute price
	//need to adjust PS version - (bibop-et-lula)
	if (Tools::version_compare(_PS_VERSION_, '1.6.1.20', '<')) {
		if ((int) $id_attr > 0) {
			$attribute_price = (float) $db->getValue('SELECT price FROM '._DB_PREFIX_.'product_attribute WHERE id_product_attribute = ' . pSQL($id_attr));
			if ($attribute_price != 0) {
				$spec_price = $init_price_vat_excl;
			}
		}
	}

	//catalog
	if (isset($specific['price'])) {
		//cache issue can't use $specific['price']
		$price_no_cache = (float) $db->getValue('SELECT price FROM '._DB_PREFIX_.'specific_price WHERE id_specific_price = ' . pSQL($specific['id_specific_price']));		
		if ( $price_no_cache !== -1.00) {
		//if ( (float) $specific['price'] !== -1.00) {
			$init_price = $price;
			$init_price_vat_excl = round($init_price / $vat, 6);	
			$spec_price = $init_price_vat_excl;
		}
	}	


	//fix bug PS 1.6.1.4 if default price = 0 !!!important	
	if ((float) $prod->price == 0.0) {
		$spec_price = $init_price_vat_excl;
	}

	//reverse reduction group amount
	if ($reduction_group_category) {
		$reduction_group_category = (float) $reduction_group_category;
		$spec_rate = 1 / ( 1 - $reduction_group_category );
		$spec_price = $init_price_vat_excl;
	} else {
		if ($reduction_group > 0) {
			$spec_rate = 1 / ( 1 - $reduction_group / 100 );
			$spec_price = $init_price_vat_excl;
		}
	}
	

	//Specific price cart exists
	$id_spec = false;
	//reduction_tax, 
	$sp = $db->getRow('SELECT id_specific_price, price, reduction_type FROM '._DB_PREFIX_.'specific_price WHERE id_cart = ' . (int) $id_cart . ' AND id_product = ' . (int) $id_prod . ' AND id_product_attribute = ' . (int) $id_attr);
	
	if ($sp) {
		$id_spec = $sp['id_specific_price'];
		if ($sp['price'] > -1) {

			//si prix redefini
			if ($db->getValue('SELECT specific_price_redefined FROM '._DB_PREFIX_.'cart_product_kerawen WHERE ' . $where)) {
				$init_price = $price = round($sp['price'] * $vat, 6);
			}
			
			$spec_price = $sp['price'];	
				
			//keep same absolute price
			//if ($reduction_group > 0) {
				$spec_price = $spec_price / $spec_rate;
			//}

		}
	}


	$specificPrice = new SpecificPrice($id_spec);	
	$specificPrice->id_shop = $id_shop; //!important
	$specificPrice->id_cart = $id_cart;
	$specificPrice->id_product = $id_prod;
	$specificPrice->id_product_attribute = $id_attr;
	$specificPrice->from_quantity = $quantity; //!important
	
	
	$specificPrice->id_shop_group = 0;
	$specificPrice->id_currency = 0;
	$specificPrice->id_country = 0;
	$specificPrice->id_group = 0;
	$specificPrice->id_customer = 0;
	$specificPrice->from = '0000-00-00 00:00:00';
	$specificPrice->to = '0000-00-00 00:00:00';
	
	
	//Change discount % to amount when 100% -> otherwise initial price is lost on order detail
	if ($type == 'percent' && $value == '100') {
		$value = $price;
		$type = 'amount';
	}
	
	
	if ($type == 'percent') {
		
		
		$reduction = ($init_price > 0 ) ? 1 - $price * (1 - $value/100) / $init_price : 0;
				
		$reducTotal = $init_price * $reduction;
		$reducCatalog = $init_price - $price;
		$reducCart = $reducTotal - $reducCatalog;

		//round important
		$specificPrice->price = round($spec_price * $spec_rate, 6);
		$specificPrice->reduction_type = 'percentage';
		$specificPrice->reduction = round($reduction, 6);

	}
	elseif ($type == 'amount') {
		
		$reduction = $value + $init_price - $price;
		
		$reducTotal = $reduction;
		$reducCatalog = $init_price - $price;
		$reducCart = $value / $spec_rate;

		//round important
		$specificPrice->price = round($spec_price * $spec_rate, 6);
		$specificPrice->reduction_type = 'amount';
		$specificPrice->reduction = round($reduction * $spec_rate, 6);

	}
	else {
		
		$reduction = 0;
		
		$reducTotal = 1;
		$reducCatalog = 0;
		$reducCart = 0;
		
		$specificPrice->price = $price;
		$specificPrice->reduction_type = 'amount';
		$specificPrice->reduction = 0;
	}
	
	$specificPrice->save();

	if ($reducTotal > 0) {
		$pc = round($reducCart / $reducTotal, 4);
	} else {
		$pc = 0;
	}
	
	//Save specific price status from cart
	$values_cpk = array();
	$values_cpk['specific_price_cart'] = $pc;
	$values_cpk['specific_price_cart_calc'] = $calc;

	$cpk = (int) $db->getValue('SELECT 1 FROM '._DB_PREFIX_.'cart_product_kerawen WHERE ' . $where);	
	if ($cpk) {
		$db->update('cart_product_kerawen', $values_cpk, $where);
	} else {
		if ( $values_cpk['specific_price_cart'] > 0 ) {
			$values_cpk['id_cart'] = (int) $id_cart;
			$values_cpk['id_product'] = (int) $id_prod;
			$values_cpk['id_product_attribute'] = (int) $id_attr;
			$db->insert('cart_product_kerawen', $values_cpk);
		}
	}
}

function annotateCartItem($id_cart, $id_prod, $id_attr, $note)
{
	$db = Db::getInstance();
	$db->execute('
		INSERT INTO '._DB_PREFIX_.'cart_product_kerawen (id_cart, id_product, id_product_attribute, note)
		VALUES (
			'.pSQL($id_cart).',
			'.pSQL($id_prod).',
			'.pSQL($id_attr).',
			"'.pSQL($note).'")
		ON DUPLICATE KEY UPDATE
			note = VALUES(note)');
}


/*
* ------------------------------------------------------------------
* Internals
*/

function ApplyPsRoundPriceMethod($price, $qty) {
	
	if ($qty == 0) {
		return 0;
	}
	
	$round_type = Configuration::get('PS_ROUND_TYPE');
	if (!$round_type) $round_type = _KERAWEN_RM_ITEM_;

	switch ($round_type) {
		case _KERAWEN_RM_TOTAL_:
			$price_qty = $price * (int)$qty;
			break;
		case _KERAWEN_RM_LINE_:
			$price_qty = Tools::ps_round($price * (int)$qty, _PS_PRICE_COMPUTE_PRECISION_);
			break;
		case _KERAWEN_RM_ITEM_:
		default:
			$price_qty = Tools::ps_round($price, _PS_PRICE_COMPUTE_PRECISION_) * (int)$qty;
			break;
	}

	return $price_qty;
	
}


function discountAsArray($cart, $no_payment = false) {

    $context = Context::getContext();
    $db = Db::getInstance();
    
    $price_display_method = isset($context->group) ? (int) $context->group->price_display_method : 0;
    
    require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
    
    // Remember rules, invalid ones will be reinjected
    $rules_before = $cart->getCartRules();
    CartRule::autoRemoveFromCart();
    
    // Recompute rules
    CartRule::autoAddToCart();
    
    $rules_after = $cart->getCartRules();
    
    //Group restriction by override group exclusive
    $override_group = Configuration::get('KERAWEN_OVERRIDE_GROUP');
    if ($override_group) {
        foreach ($rules_after as $k => $rule) {
            if ($rule['group_restriction']) {
                if ((int) $db->getValue('SELECT id_group FROM '._DB_PREFIX_.'cart_rule_group WHERE id_group = ' . (int) $override_group . ' AND id_cart_rule = '. (int) $rule['id_cart_rule']) == 0) {
                    $cart->removeCartRule($rule['id_cart_rule']);
                    unset($rules_after[$k]);
                }
            }
        }
    }
    

    // Reinject manual rules even invalid
    $rules_after = indexArray($rules_after, 'id_cart_rule');
    foreach ($rules_before as $rule) {
        $id_rule = $rule['id_cart_rule'];
        if (!isset($rules_after[$id_rule]) && Tools::strlen($rule['code'])) {
            // Check if rule still exists
            if ($db->getValue('
				SELECT id_cart_rule FROM '._DB_PREFIX_.'cart_rule
				WHERE id_cart_rule = '.pSQL($id_rule))) {
				// Reinject with real value 0
            $cart->addCartRule($id_rule);
            $rule['value_real'] = 0;
            $rules_after[$id_rule] = $rule;
            }
        }
    }
    
	$total_reduc_ti = 0;
	$total_reduc_te = 0;
	$reducs = array();
	foreach ($rules_after as $rule) {
		$id_rule = $rule['id_cart_rule'];
		$reduc = new CartRule($id_rule);

		if ($cart->id_carrier == 0 && $rule['free_shipping'] == 1 && $rule['reduction_percent'] == 0 && $rule['reduction_amount'] == 0) {
		    continue;
		}
		
		// Avoid shipping discount if no shipping!
		if (!$cart->id_carrier) $reduc->free_shipping = false;
		
		$type = $db->getValue('SELECT type FROM '._DB_PREFIX_.'cart_rule_kerawen WHERE id_cart_rule = '.pSQL($id_rule));

		if ($reduc->checkValidity($context, true, false)) {
    		$value_tax_incl = $reduc->getContextualValue(true, $context, CartRule::FILTER_ACTION_ALL_NOCAP);
    		$value_tax_excl = $reduc->getContextualValue(false, $context, CartRule::FILTER_ACTION_ALL_NOCAP);
		} else {
		    $value_tax_incl = 0;
		    $value_tax_excl = 0;
		}
		
		$total_reduc_ti += $value_tax_incl;
		$total_reduc_te += $value_tax_excl;
		
		$reducs[] = array(
			'id' => $id_rule,
			'type' => $type,
			'code' => $rule['code'],
			'name' => $rule['name'],
			'mode' => $reduc->reduction_percent > 0 ? 'percent' : 'amount',
			'value' => $reduc->reduction_percent > 0 ? $reduc->reduction_percent : ($price_display_method ? $value_tax_excl : $value_tax_incl),
			'value_tax_incl' => $value_tax_incl,
			'value_tax_excl' => $value_tax_excl,
			'reduc' => $price_display_method ? $value_tax_excl : $value_tax_incl, //$rule['value_tax_exc'] : $rule['value_real'],
			'reduc_tax_excl' => $value_tax_excl, //$rule['value_tax_exc'],
			'reduc_tax_incl' => $value_tax_incl, //$rule['value_real'],
			'dispMethode' => $price_display_method,
		);
	}
	
	return array(
		'reducs' => $reducs,
		'total_ti' => Tools::ps_round($total_reduc_ti, _PS_PRICE_COMPUTE_PRECISION_),
		'total_te' => $total_reduc_te,
	);
}


function cartAsArray($cart, $more_details = false, $is_quote = false)
{
	require_once (_KERAWEN_API_DIR_.'/bean/ListBean.php');
	require_once (_KERAWEN_API_DIR_.'/bean/ProductBean.php');
	require_once (_KERAWEN_API_DIR_.'/bean/VoucherBean.php');

	require_once (_KERAWEN_CLASS_DIR_.'/quote.php');
	
	$db = Db::getInstance();
	Product::flushPriceCache();
	
	$res = array();
	$res['id'] = $cart->id;
	$res['shop'] = $cart->id_shop;
	$res['id_lang'] = $cart->id_lang;
	
	//required for quotes
	$res['id_address_delivery'] = (int) $cart->id_address_delivery;
	$res['id_address_invoice'] = (int) $cart->id_address_invoice;
		
	// Customer (required in context)
	// Set customer before rules for correct computing
	$cust = new Customer($cart->id_customer);
	if (!$cust->id) {
		$cart->id_customer = (int) Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
		$cust = new Customer($cart->id_customer);
	}
	$is_anonymous = $cust->id == Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	$res['cust'] = array(
		'id' => $is_anonymous ? null : $cust->id,
		'firstname' => $is_anonymous ? null : $cust->firstname,
		'lastname' => $is_anonymous ? null : $cust->lastname,
		'email' => $is_anonymous ? null : $cust->email
	);

	// Setup context for computations
	$context = Context::getContext();
	$context->cart = $cart;
	
	// Extended data
	$more = $db->getRow('
		SELECT cart_kerawen.*, 
		IFNULL(shop_url.id_shop, false) AS id_shop,
		IF(quote_title = "" OR quote_title IS NULL, LPAD(quote_number, 10, "#Q000000000"), quote_title) AS title,
		cart.id_lang 
		FROM '._DB_PREFIX_.'cart_kerawen cart_kerawen 
		LEFT JOIN '._DB_PREFIX_.'cart cart ON cart_kerawen.id_cart = cart.id_cart 
		LEFT JOIN '._DB_PREFIX_.'shop_url shop_url ON cart.id_shop = shop_url.id_shop
		WHERE cart_kerawen.id_cart = '.pSQL($cart->id));
	
	if ($more) {
		$res['quote'] = (int) $more['quote'];
		$res['quote_expiry'] = $more['quote_expiry'];
		$res['quote_title'] = $more['title'];
		$res['quote_pdf'] = getQuoteDownloadUri($cart->id, $more['id_shop'], $more['id_lang']);
		$res['id_empl'] = (int) $more['id_employee'];
	}
		
	// Complete delivery data
	// FIXME Optimize by (re)transfering to setDelivery
	$mode = $more ? $more['delivery_mode'] : _KERAWEN_DM_IN_STORE_;
	$carriers = array();
	$carrier = null;
	if ($mode == _KERAWEN_DM_DELIVERY_)
	{
		$id_address = $more['id_address'];
		if (!$id_address && $cart->id_customer)
			$id_address = $db->getValue('
				SELECT id_address FROM '._DB_PREFIX_.'address
				WHERE id_customer = '.pSQL($cart->id_customer).'
				AND deleted = 0');
		if (!$id_address) $id_address = 0;

		setDeliveryAddress($cart, $id_address);
		$dol = $cart->getDeliveryOptionList(null, true);

		$best_grade = null;
		foreach ($dol as $id_address => $do)
			if ($id_address == $cart->id_address_delivery)
				foreach ($do as $key => $option)
				{
					$name = null;
					foreach ($option['carrier_list'] as /*$id_carrier =>*/ $carrier)
					{
						$name = $name ? ($name.', ') : '';
						$name .= $carrier['instance']->name;
						

					}
					
					// Keep order
					//$carriers[$option['position']] = array(
					$carriers[$key] = array(
						'id' => $key,
						'name' => $name,
						'price' => $option['total_price_with_tax'],
						'price_without_tax' => $option['total_price_without_tax'],
					);
					if ($option['is_best_grade']) $best_grade = $key;
				}
		$carrier = $more['carrier'];
		if (!$carrier || !isset($carriers[$carrier]))
			$carrier = $best_grade;
	}
	else
		setDeliveryAddress($cart, 0);

	$cart->setDeliveryOption($cart->id_address_delivery && $carrier
		? array($cart->id_address_delivery => $carrier) : null);
	
	// Update original cart
	$cart->update();

	require_once(dirname(__FILE__).'/address.php');
	$delivery = array(
		'mode' => $mode,
		'date' => $more ? $more['delivery_date'] : null,
		'address' => getAddress((int)$cart->id_address_delivery),
		'carrier' => $carrier,
		'carriers' => $carriers,
	);
	$res['delivery'] = $delivery;
	
	// Compute total price (after delivery has been set)
	$res['count_cart'] = Cart::getNbProducts($cart->id);
	
	// Get additional data about items
	$items = $db->executeS('
		SELECT
			cp.id_product_attribute AS id_product_attribute,
			cp.date_add AS `date`,
			cpk.note AS note,
			p.*
		FROM `'._DB_PREFIX_.'cart_product` cp
		LEFT JOIN '._DB_PREFIX_.'cart_product_kerawen cpk
			ON cpk.id_cart = cp.id_cart AND cpk.id_product = cp.id_product AND cpk.id_product_attribute = cp.id_product_attribute
		LEFT JOIN `'._DB_PREFIX_.'product` p
			ON p.id_product = cp.id_product
		WHERE cp.id_cart = '.pSQL($cart->id));
	$more = array();
	foreach ($items as &$i)
		$more[$i['id_product'].'_'.$i['id_product_attribute']] = $i;

	if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
		$address_id = (int)$cart->id_address_invoice;
	} else {
		$address_id = (int)$cart->id_address_delivery;
	}
		
	if (!Address::addressExists($address_id)) {
		$address_id = null;
	}

	//Cart with or without tax
	$group = new Group($cust->id_default_group);
	$price_display_method = $is_quote ? true : (int) $group->price_display_method;
	$res['disp_method'] = $price_display_method;
	
	// Clean up customizations
	$customs = $db->executeS('
		SELECT id_customization
		FROM '._DB_PREFIX_.'customization
		WHERE id_cart = '.pSQL($cart->id).'
			AND quantity = 0');
	foreach($customs as $c) {
		$db->delete('customization', 'id_customization = '.pSQL($c['id_customization']));
		$db->delete('customized_data', 'id_customization = '.pSQL($c['id_customization']));
	}

	$products = array();
	$link = new Link();
	$pp = $cart->getProducts();
	$cart_shop_context = Context::getContext()->cloneContext();
	foreach ($pp as &$p)
	{
		$id_prod = (int)$p['id_product'];
		$index = $id_prod.'_'.$p['id_product_attribute'];

		$specific = SpecificPrice::getSpecificPrice(
			$id_prod,
			$cart->id_shop,
			$cart->id_currency,
			$context->country->id,
			$cust ? Customer::getDefaultGroupId($cust->id) : null,
			$p['cart_quantity'],
			$p['id_product_attribute'],
			$cust ? $cust->id : null,
			$cart->id,
			$p['cart_quantity']); // Should be prod qty vs attr qty ;
		
		$specific_hack = $specific;
			
		// Backward compatibility
		if (!(isset($p['price_without_reduction']))) {
			$p['price_without_reduction'] = Product::getPriceStatic(
				(int)$p['id_product'],
				true,
				isset($p['id_product_attribute']) ? (int)$p['id_product_attribute'] : null,
				6,
				null,
				false,
				false,
				$p['cart_quantity'],
				false,
				(int)$cart->id_customer,
				(int)$cart->id,
				$address_id,
				$specific,
				true,
				true,
				$cart_shop_context
			);
		}
			
		// Backward compatibility
		if (!(isset($p['price_with_reduction_without_tax']))) {
			$p['price_with_reduction_without_tax'] = $p['price'];
		}

		$cover = Product::getCover($id_prod);
		//valois vintage
		//TODO : need to improve conditions
		if (isset($cover['id_image']) && Tools::version_compare(_PS_VERSION_, '1.6.0.14', '=') && Configuration::get('PS_LEGACY_IMAGES')) {
			$cover['id_image'] = $id_prod . "-" . $cover['id_image'];
		}
		$img = $cover['id_image'] ? '//'.$link->getImageLink($p['link_rewrite'], $cover['id_image'], 'home_default') : null;

		$wm = $db->getRow('
			SELECT
				pak.measure AS measure,
				pk.unit as unit,
				pk.`precision` AS `precision`
			FROM '._DB_PREFIX_.'product_attribute_kerawen pak
			JOIN '._DB_PREFIX_.'product_attribute pa
				ON pa.id_product_attribute = pak.id_product_attribute
			JOIN '._DB_PREFIX_.'product_wm_kerawen pk
				ON pk.id_product = pa.id_product
			WHERE pak.id_product_attribute = '.pSQL($p['id_product_attribute']));
		
		//UNIT VAT EXCL
		$price_excl = (float)$p['total'];
		$price_excl_init = Product::getPriceStatic(
			(int)$p['id_product'],
			false,
			isset($p['id_product_attribute']) ? (int)$p['id_product_attribute'] : null,
			6,
			null,
			false,
			false,
			$p['cart_quantity'],
			false,
			(int)$cart->id_customer,
			(int)$cart->id,
			$address_id,
			$specific,
			true,
			true,
			$cart_shop_context 
		);
		
		
		//IMPORTANT!!!!!!
		//PS 1.6.15
		$specific = $specific_hack;
		
		//UNIT VAT INCL
		$price_incl = (float)$p['total_wt'];
		$price_incl_init = (float)$p['price_without_reduction'];

		//CART display
		if ($price_display_method) {
			//VAT EXCL
			$price = $price_excl;
			$price_init = $price_excl_init;
		}
		else {
			//VAT INCL
			$price = (float)$p['total_wt'];
			$price_init = (float)$p['price_without_reduction'];
		}

		$tmp_product = array(
			'prod' => $id_prod,
			'attr' => $p['id_product_attribute'],
			'name' => $p['name'],
			'version' => isset($p['attributes_small']) ? $p['attributes_small'] : null,
			'reference' => $p['reference'],
			'qty' => (int)$p['cart_quantity'],
			'unit' => (float)$p['price_wt'],
			'price' => $price,
			'unit_init' => (float)$p['price_without_reduction'],
			'price_init' => ApplyPsRoundPriceMethod($price_init, $p['cart_quantity']),			
			'img' => $img,
			'date' => $more[$index]['date'],
			'note' => $more[$index]['note'],
			'discount_type' => $specific ? $specific['reduction_type'] : null,
			'discount' => $specific ? (float)$specific['reduction'] : null,
			'specific' => $specific,
			'display_method' => $price_display_method,
			'wm' => $wm && $wm['measure'] ? array(
				'measure' => (float)$wm['measure'],
				'precision' => (int)$wm['precision'],
				'unit' => $wm['unit'],
			) : null,
			'rate' => is_numeric($p['rate']) ? $p['rate'] : 0,
			'unit_price_init' => $price_init,
			'minimal_quantity' => $p['minimal_quantity'],
		);

		//Quotation display
		if ($more_details) {
			//unit by product
			$tmp_product['unit_init_tax_incl'] = $price_incl_init;
			$tmp_product['unit_init_tax_excl'] = $price_excl_init;
			
			//total by product
			$tmp_product['price_init_tax_incl'] = ApplyPsRoundPriceMethod($price_incl_init, $p['cart_quantity']);
			$tmp_product['price_init_tax_excl'] = ApplyPsRoundPriceMethod($price_excl_init, $p['cart_quantity']);
			$tmp_product['price_tax_incl'] = $price_incl;
			$tmp_product['price_tax_excl'] = $price_excl;
		
			$tmp_product['unit_ecotax'] = $p['ecotax'];
			$tmp_product['total_ecotax'] = (float) $p['ecotax'] * (float)$p['cart_quantity'];
			$tmp_product['tax_name'] = $p['tax_name'];

			if ($cover) {
			    $tmp_product['image_id'] = $cover['id_image'];
			    $tmp_product['image_path'] = $img;
			}
		} 
		$tmp_product['vat_margin'] = isset($more[$index]['vat_margin']) ? $more[$index]['vat_margin'] : 0;
		$tmp_product['wholesale_price'] = (float)$p['wholesale_price'];

		// Customizations
		$custom = $db->executeS('
			SELECT id_customization, quantity
			FROM '._DB_PREFIX_.'customization
			WHERE id_cart='.pSQL($cart->id).'
			AND id_product='.pSQL($id_prod).'
			AND id_product_attribute='.pSQL($p['id_product_attribute']).'
			AND in_cart > 0
			ORDER BY id_customization');
		foreach($custom as &$c) {
			$c['fields'] = $db->executeS('
				SELECT d.index, f.name, d.value
				FROM '._DB_PREFIX_.'customized_data d
				JOIN '._DB_PREFIX_.'customization_field_lang f
					ON f.id_customization_field = d.index
					AND f.id_lang = '.pSQL($cart->id_lang).'
					AND f.id_shop = '.pSQL($cart->id_shop).'
				WHERE d.id_customization='.pSQL($c['id_customization']).'
				ORDER BY d.index');
		}
		$tmp_product['custom'] = $custom;
		
		$products[] = $tmp_product;
	}
	
	usort(
		$products, 
		function($a,$b) { 
			return strtotime($a['date']) - strtotime($b['date']);
		} 
	);
	
	$res['products'] = $products;

	$res['returns'] = getReturns($cart->id, $price_display_method);
	$count_ret = 0;
	$total_ret = 0;
	$total_ret_te = 0;
	foreach ($res['returns'] as &$value) {
		$count_ret += $value['qty'];
		$total_ret += $value['price_tax_incl'];
		$total_ret_te += $value['price_tax_excl'];
	}
	$res['count_ret'] = $count_ret;
	$res['total_ret'] = $total_ret;
	$res['total_ret_te'] = $total_ret_te;
	
	$total_ti = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS) + $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
	$total_te = $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) + $cart->getOrderTotal(false, Cart::ONLY_WRAPPING);
	if ($mode == _KERAWEN_DM_DELIVERY_ && $delivery['carrier']) {
		$total_ti += $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
		$total_te += $cart->getOrderTotal(false, Cart::ONLY_SHIPPING);
	}
	
	$reducs = discountAsArray($cart);
	$res['reducs'] = $reducs['reducs'];
	$total_ti -= $reducs['total_ti'];
	$total_te -= $reducs['total_te'];
	if ($total_ti < 0) $total_ti = 0;
	if ($total_te < 0) $total_te = 0;
	
	$res['total_cart'] = $total_ti;
	$res['total_cart_te']= $total_te;
	
	// Restore customer
	$cart->id_customer = $cust->id;
	$cart->save();
	
	updateCart($res, isset($cart->kerawen_version) ? $cart->kerawen_version : null);
	return $res;
}

function computeCartPrices($cart) {
	$db = Db::getInstance();
	$context = Context::getContext();
	
	// Remove cart discounts first (avoid cache issue)
	$db->delete('specific_price', 'id_cart = '.pSQL($cart->id));
	Product::flushPriceCache();
	
	require_once(_KERAWEN_CLASS_DIR_.'/data.php');
	$address_delivery = $cart->id_address_delivery ? new Address ($cart->id_address_delivery) : getDefaultDeliveryAddress(true);
	
	$discounted = $db->executeS('
		SELECT
			cpk.*,
			cp.quantity
		FROM '._DB_PREFIX_.'cart_product_kerawen cpk
		JOIN '._DB_PREFIX_.'cart_product cp ON
			cp.id_cart = cpk.id_cart
			AND cp.id_product = cpk.id_product
			AND cp.id_product_attribute = cpk.id_product_attribute
		WHERE cpk.id_cart = '.pSQL($cart->id).'
		AND cpk.price_type IS NOT NULL');
	
	$id_group = $context->group->id;
	foreach($discounted as $discount) {
		$tax_manager = TaxManagerFactory::getManager($address_delivery, Product::getIdTaxRulesGroupByIdProduct((int)$discount['id_product'], $context));
		$tax_calculator = $tax_manager->getTaxCalculator();
	
		$specific = false;
		$init = Product::getPriceStatic(
			$discount['id_product'],
			false,
			$discount['id_product_attribute'],
			6,
			null,
			false,
			true,
			1,
			false,
			$cart->id_customer,
			null,
			$cart->id_address_delivery,
			$specific,
			true,
			true,
			null,
			true);
	
		if ($discount['price_type'] == _KERAWEN_PA_REPLACE_) {
			$price = $tax_calculator->removeTaxes((float)$discount['price_value']);
		}
		else if ($discount['price_type'] == _KERAWEN_PA_AMOUNT_) {
			$price = $init - $tax_calculator->removeTaxes((float)$discount['price_value']);
			if ($price < 0) $price = 0;
		}
		else if ($discount['price_type'] == _KERAWEN_PA_PERCENT_) {
			$price = $init*(1 - (float)$discount['price_value']/100);
		}
		else {
			$price = $init;
		}
	
		if ($discount['price_qty']) {
			// Apply to one item only
			$qty = (float)$discount['quantity'];
			$price = ($init*($qty - 1) + $price)/$qty;
		}
	
		$group_reduction = GroupReduction::getValueForProduct($discount['id_product'], $id_group);
		if ($group_reduction === false)
			$group_reduction = Group::getReductionByIdGroup($id_group)/100;
		$price = $price/(1 - (float)$group_reduction);
	
		$specificPrice = new SpecificPrice();
		$specificPrice->id_shop = $cart->id_shop;
		$specificPrice->id_cart = $cart->id;
		$specificPrice->id_product = $discount['id_product'];
		$specificPrice->id_product_attribute = $discount['id_product_attribute'];
		$specificPrice->from_quantity = $discount['quantity'];
		$specificPrice->id_shop_group = 0;
		$specificPrice->id_currency = 0;
		$specificPrice->id_country = 0;
		$specificPrice->id_group = 0;
		$specificPrice->id_customer = 0;
		$specificPrice->from = '0000-00-00 00:00:00';
		$specificPrice->to = '0000-00-00 00:00:00';
		$specificPrice->price = round($price, 6);
		$specificPrice->reduction_type = 'amount';
		$specificPrice->reduction = 0;
		$specificPrice->save();
	}
}


function newCart($context, $id_cust)
{
	$id_lang = $context->language->id;
	$id_curr = $context->currency->id;

	$c = new Cart();
	$c->id_customer = $id_cust;
	$c->id_lang = $id_lang;
	$c->id_currency = $id_curr;
	$c->save();

	return $c;
}

function getCart($id_cart, $id_cust, $context)
{
	// Some cCart operations require customer in context
	$context->customer = new Customer($id_cust);
	return $id_cart ? new Cart($id_cart) : newCart($context, $id_cust);
}

function getProductsByDate($c, $id_lang)
{
	// KPOS Cart specific context
	$address_id = null;

	$sql = 'SELECT
					cp.`id_shop`,
					cp.`id_product`,
					cp.`id_product_attribute`,
					cp.`quantity` AS `cart_quantity`,
					cp.`date_add`,
					pl.`name`
				FROM
					'._DB_PREFIX_.'cart_product cp,
					'._DB_PREFIX_.'product  p,
					'._DB_PREFIX_.'product_lang pl
				WHERE
					cp.`id_cart` = '.(int)$c->id.'
					AND p.`id_product` = cp.`id_product`
					AND pl.`id_product` = p.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.'
				ORDER BY
					cp.date_add ASC';

	$result = Db::getInstance()->executeS($sql);

	$cart_shop_context = Context::getContext()->cloneContext();
	foreach ($result as &$row)
	{
		if ($cart_shop_context->shop->id != $row['id_shop'])
			$cart_shop_context->shop = new Shop((int)$row['id_shop']);

		//Fix bug "Fatal error: Only variables can be passed by reference" php7
		$null = null;	
			
		$row['price_wt'] = Product::getPriceStatic((int)$row['id_product'],
				true,
				(int)$row['id_product_attribute'],
				2,
				null,
				false,
				true,
				$row['cart_quantity'],
				false,
				$c->id_customer ? $c->id_customer : null,
				$c->id,
				$address_id,
				$null,
				true,
				true,
				$cart_shop_context);
		$row['price_wt'] = Tools::ps_round($row['price_wt'], _PS_PRICE_COMPUTE_PRECISION_);
		$row['total_wt'] = $row['price_wt'] * (int)$row['cart_quantity'];
	}

	return $result;
}


function getReturns($id_cart, $price_display_method = 0)
{
	if (!$id_cart) return array();
	
	$refund_tax = ($price_display_method) ? 'excl' : 'incl';
	$db = Db::getInstance();
	$sql = 'SELECT
				rk.`id_return` AS id,
				rk.`id_cart` AS id_cart,
				rk.`id_order` AS id_order,
				rk.`id_order_detail` AS id_order_detail,
				rk.`quantity` AS qty,
				-rk.`refund_tax_' . $refund_tax . '` AS price,
				-rk.`refund_tax_excl` AS price_tax_excl,
				-rk.`refund_tax_incl` AS price_tax_incl,
				od.product_name AS name
			FROM `'._DB_PREFIX_.'return_kerawen` rk
			LEFT JOIN `'._DB_PREFIX_.'order_detail` od ON rk.id_order_detail = od.id_order_detail
			WHERE `id_cart` = '.pSQL($id_cart);
	return $db->executeS($sql);
}
