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

function generateRuleCode()
{
	do
		$code = Tools::strtoupper(Tools::passwdGen(8, 'ALPHANUMERIC'));
	while (CartRule::cartRuleExists($code));
	return $code;
}

function getReceiptCartRules($context, $params, &$response) {

    $id_shop = $context->shop->id;
    $id_lang = $context->language->id;

    //TODO: restriction shop, other restrictions...
    $cartRules = Db::getInstance()->executeS('
        SELECT
            crl.name AS title,
            cr.id_cart_rule, 
            cr.code, 
            cr.id_cart_rule, 
            cr.date_from, 
            cr.date_to, 
            cr.minimum_amount, 
            cr.reduction_amount, 
            cr.reduction_currency, 
            cr.reduction_percent, 
            cr.reduction_tax,
            cr.free_shipping,
            cr.description
        FROM '._DB_PREFIX_.'cart_rule cr
        LEFT JOIN '._DB_PREFIX_.'cart_rule_lang crl ON cr.id_cart_rule = crl.id_cart_rule  AND id_lang = '.(int)$id_lang.'
        LEFT JOIN '._DB_PREFIX_.'cart_rule_kerawen crk ON cr.id_cart_rule = crk.id_cart_rule
        WHERE cr.active = 1 AND cr.code != "" AND crk.is_voucher = 1
        AND ( 
               ( NOW() BETWEEN crk.display_from AND crk.display_to ) 
            OR ( ISNULL(crk.display_from) AND ISNULL(crk.display_to) )
            OR ( ISNULL(crk.display_from) AND NOW() < crk.display_to )
            OR ( crk.display_from < NOW() AND ISNULL(crk.display_to) )
        )
        ORDER BY cr.id_cart_rule ASC
    ');
    $response->addResult('receiptCartRules', $cartRules);
}

function getCartRules($id_cust, $id_lang)
{
	if (!$id_cust) $id_cust = (int)Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	$anonymous = $id_cust == (int)Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	
	// Get rules for customer or everybody
	$rules = array();
	$crs = CartRule::getCustomerCartRules($id_lang, $id_cust, true, true, true);
	
	// Loyalty
	require_once(dirname(__FILE__).'/loyalty.php');
	if ($id_cust && ($loyalty = KerawenLoyalty::getPlugin()))
		$loyalty = $loyalty->getRules($id_cust);
	else
		$loyalty = array(
			'loyalty' => array(),
			'referral' => array(),
			'sponsor' => array(),
		);
	
			
	// Get additional information
	$db = Db::getInstance();

	$more = array();
	$buf = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'cart_rule_kerawen`');
	
	foreach ($buf as $cr)
		$more[$cr['id_cart_rule']] = $cr;

	foreach ($crs as &$cr)
	{
		$id_rule = $cr['id_cart_rule'];

		// Skip if already used
		if ($cr['quantity_for_user'] <= 0)
			continue;

		// Skip if automatic
		if (Tools::strlen($cr['code']) <= 0)
			continue;

		// Test more...
		if (isset($more[$id_rule]))
		{
			// Skip if restricted to a given cart
			if (isset($more[$id_rule]['id_cart']))
				continue;

			// Skip if credit for anonymous
			if ($more[$id_rule]['type'] === _KERAWEN_CR_CREDIT_ && !$cr['id_customer'])
				continue;
		}

		// Determine category
		$type = '';
		if (in_array($id_rule, $loyalty['loyalty']))
			$type = _KERAWEN_CR_LOYALTY_;
		else if (in_array($id_rule, $loyalty['referral']))
			$type = _KERAWEN_CR_REFERRAL_;
		else if (in_array($id_rule, $loyalty['sponsor']))
			$type = _KERAWEN_CR_REFERRAL_;
		else if (isset($more[$id_rule]) && ($more[$id_rule]['type'] == _KERAWEN_CR_CREDIT_ || $more[$id_rule]['type'] == _KERAWEN_CR_PREPAID_))
			$type = _KERAWEN_CR_CREDIT_;
		else if ($cr['group_restriction'])
			$type = _KERAWEN_CR_GROUP_;
		
		$rule = array(
			'id' => $id_rule,
			'type' => $type,
			'code' => $cr['code'],
			'name' => $cr['name'],
			'qty' => $cr['quantity_for_user'],
			'mode' => $cr['reduction_percent'] > 0 ? 'percent' : 'amount',
			'value' => $cr['reduction_percent'] > 0 ? (float)$cr['reduction_percent'] : (float)$cr['reduction_amount'],
			'points' => 0,
			'from' => $cr['date_from'],
			'to' => $cr['date_to'],
		);

		if ($type == _KERAWEN_CR_LOYALTY_) {
			if ((float)Configuration::get('PS_LOYALTY_POINT_VALUE') > 0) {
				$rule['points'] = intval( (float)$cr['reduction_amount'] / Configuration::get('PS_LOYALTY_POINT_VALUE') );
			}
		}

		// Avoid individual rules for anonymous
		if (!$anonymous || $type == '' || $type == _KERAWEN_CR_GROUP_)
			$rules[] = $rule;
	}
	
	return $rules;
}

function setKerawenCartRule($id, $code, $id_cust, $reduction_currency, $reduction_percent, $reduction_amount, $reduction_tax, $partial_use, $highlight, $priority, $quantity, $quantity_per_user, $active, $date_from, $date_to, $name, $description, $cart_rule_type, $id_cart, $id_product, $id_attribute, $id_order, $is_voucher, $display_from, $display_to, $minimum_amount, $minimum_amount_tax) {
    
    $db = Db::getInstance();
    
    $context = Context::getContext();
    if ($id_cust == Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER')) $id_cust = null;
    
    $rule = new CartRule($id);
    if ($code) {
        $rule->code = $code;
    }
    $rule->id_customer = $id_cust;
    $rule->reduction_currency = $reduction_currency;
    $rule->reduction_percent = $reduction_percent;
    $rule->reduction_amount = $reduction_amount;
    $rule->reduction_tax = $reduction_tax;
    $rule->partial_use = $partial_use;
    $rule->highlight = $highlight;
    $rule->priority = $priority;
    $rule->quantity = $quantity;
    $rule->quantity_per_user = $quantity_per_user;
    $rule->active = $active;
    $rule->date_from = $date_from;
    $rule->date_to = $date_to;
    $rule->name = getForLanguages($name);
    $rule->description = $description;
    
    $rule->minimum_amount = $minimum_amount;
    $rule->minimum_amount_tax = $minimum_amount_tax;
    
    $rule->save();
    

    //Kerawen spec
    $sql = '
        INSERT INTO `'._DB_PREFIX_.'cart_rule_kerawen`
		(`id_cart_rule`, `type`,`id_cart`,`id_product`,`id_attribute`,`id_order`,`is_voucher`,`display_from`,`display_to`)
		VALUES (
            '.pSQL($rule->id).',
            \''. $cart_rule_type .'\',
            ' . pSQL($id_cart) . ',
            ' . pSQL($id_product) . ',
            ' . pSQL($id_attribute) . ',
            ' . pSQL($id_order) . ',
            ' . pSQL($is_voucher) . ',
            ' . $display_from . ',
            ' . $display_to . '
        ) ON DUPLICATE KEY UPDATE
            `display_from` = VALUES(`display_from`),
            `display_to` = VALUES(`display_to`)
        ';    

    $db->execute($sql);

    return $rule;
}


function createCredit($value, $id_cust, $cart_rule_type = _KERAWEN_CR_CREDIT_, $id_cart = 'null', $id_product = 'null', $id_attribute = 'null', $id_order = 'null')
{
	
	$db = Db::getInstance();
	
	$context = Context::getContext();

	if ($id_cust == Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER')) $id_cust = null;

	(int)$period = ($cart_rule_type == _KERAWEN_CR_GIFT_CARD_) ? Configuration::get('KERAWEN_GIFT_CARD_DURATION') : Configuration::get('KERAWEN_DISCOUNT_DURATION');
	if ($period == 0) $period = 365;
	$period++;

	$date_from = $db->getValue("SELECT NOW() - INTERVAL 1 DAY");
	$date_to = $db->getValue("SELECT NOW() + INTERVAL " . $period . " DAY");
	
	if ($cart_rule_type == _KERAWEN_CR_GIFT_CARD_) {
	    $name = $context->module->l('Gift card', pathinfo(__FILE__, PATHINFO_FILENAME));
	}
	else {
	    $name = $context->module->l('Credit', pathinfo(__FILE__, PATHINFO_FILENAME));
	}
	
	$highlight = $id_cust != null;
	$priority = Configuration::get('KERAWEN_CREDIT_PRIORITY');
	
	$rule = setKerawenCartRule(
	    null, 
	    generateRuleCode(), 
	    $id_cust,
	    $context->currency->id,
	    0,
	    $value,
	    true, 
	    true, 
	    $highlight, 
	    $priority, 
	    1, 
	    1, 
	    1, 
	    $date_from, 
	    $date_to, 
	    $name,
	    '',
	    $cart_rule_type, 
	    $id_cart, 
	    $id_product, 
	    $id_attribute, 
	    $id_order, 
	    0, 
	    'NULL', 
	    'NULL',
	    0,
	    1
	);
	$id_rule = $rule->id;

	// Return the new rule...
	$cust = $id_cust ? new Customer($id_cust) : null;
	return array(
		'id' => $id_rule,
		'code' => $rule->code,
		'name' => $rule->name[$context->language->id],
		'qty' => $rule->quantity_per_user,
		'mode' => 'amount',
		'value' => $rule->reduction_amount,
		'reduction_tax' => (int) $rule->reduction_tax,
		'from' => $rule->date_from,
		'to' => $rule->date_to,
		'cust' => $id_cust ? array(
			'id' => $cust->id,
			'firstname' => $cust->firstname,
			'lastname' => $cust->lastname,
		) : null,
	);
}

function applySpecialOffer($context, $params, &$response)
{
	$all_res = array();

	for ($i = 1; $i <= $params->nb ; $i++) {
    
	// Ensure rules are active
	Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', '1');
	
	$value      = $params->value ;
	$percent    = $params->percent ;
	$id_product = $params->id_product ;
	$cust    = $params->cust ;

	$rule = new CartRule() ;
	$rule->code = generateRuleCode() ;
	if ($percent)
		$rule->reduction_percent = $value;
	else
	{
		$rule->reduction_currency = $context->currency->id;
		$rule->reduction_amount = $value;
	}
	$rule->partial_use = false ;
	$rule->reduction_tax = true ;
	$rule->quantity = 1 ;
	$rule->quantity_per_user = 1 ;
	$rule->product_restriction = 1 ;	  

	//$rule->date_from = date('Y-m-d H:i:s', time() - 1);
	$rule->date_from = date($params->from.' H:i:s', time() - 1);
	 
	//$rule->date_to = date('Y-m-d H:i:s', strtotime($rule->date_from.' +1 year'));
	$rule->date_to = date($params->to.' H:i:s', time() );



	$languages = Language::getLanguages(true);
	foreach ($languages as $l)
		$rule->name[(int)$l['id_lang']] = $context->module->l('Special Offer', pathinfo(__FILE__, PATHINFO_FILENAME));

	$rule->add();
	
	$id_rule = $rule->id;
	$db = Db::getInstance();
		
	$sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_group`
				(`id_product_rule_group`, `id_cart_rule`, `quantity`)
				VALUES ("",'.pSQL($id_rule).',1)';
	$db->execute($sql);

	$id_product_rule_group = $db->Insert_ID();
	 
	$sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule`
				(`id_product_rule`, `id_product_rule_group`, `type`)
				VALUES ("",'.pSQL($id_product_rule_group).',"products")';
	$db->execute($sql);

	$id_product_rule = $db->Insert_ID();

	$sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_value`
				(`id_product_rule`, `id_item`)
				VALUES ('.pSQL($id_product_rule).','.pSQL($id_product).')';
	$db->execute($sql);

// Return the new rule...
	//  $cust = $id_cust ? new Customer($id_cust) : null;
	$product = $id_product ? new Product($id_product) : null;
 	 

	array_push($all_res, array(
		'id' => $id_rule,
		'code' => $rule->code,
		'name' => $rule->name[$context->language->id],
		'qty' => $rule->quantity_per_user,
		'mode' => 'amount',
		'value' => $rule->reduction_amount,
		'from' => $rule->date_from,
		'to' => $rule->date_to,
		'cust' => $cust ? array(
			'id' => $cust->id,
			'firstname' => $cust->firstname,
			'lastname' => $cust->lastname,
		) : null ,
		'product' => $product->name[1] 
	));
} // fin for...
	return $all_res;
}



function setGiftCard($id_order, $action) {


	$db = Db::getInstance();

	$q = '
		SELECT orders.id_cart, order_kerawen.gift_card_flag, order_kerawen.id_till, order_kerawen.id_employee, orders.id_shop, order_kerawen.is_paid
		FROM '._DB_PREFIX_.'orders orders
		INNER JOIN '._DB_PREFIX_.'order_kerawen order_kerawen ON orders.id_order = order_kerawen.id_order
		WHERE orders.id_order = ' . pSQL($id_order)
	;

	if ($row = $db->getRow($q)) {

		$id_cart = (int) $row['id_cart'];
		$gift_card_flag = (int) $row['gift_card_flag'];
		
		$id_till = (int) $row['id_till'];
		$id_employee = (int) $row['id_employee'];
		$id_shop = (int) $row['id_shop'];

		$is_paid = (int) $row['is_paid'];
		

		if (!$gift_card_flag && $action == 'createCredit') {

			$q = '
			SELECT order_detail.product_id, order_detail.product_attribute_id, order_detail.unit_price_tax_incl, order_detail.product_quantity
			FROM '._DB_PREFIX_.'order_detail order_detail
			INNER JOIN '._DB_PREFIX_.'product_wm_kerawen product_wm_kerawen 
			ON order_detail.product_id = product_wm_kerawen.id_product
			AND product_wm_kerawen.is_gift_card = 1
			AND id_order = ' . pSQL($id_order) 
			;
			
			$buf = $db->executeS($q);
				
			if ($buf) {
				$amount = 0;
				foreach ($buf as $row) {
					for ($i = 1; $i <= intval($row['product_quantity']); $i++) {
						$data_rules = createCredit(
							(float) $row['unit_price_tax_incl'], 
							0, 
							_KERAWEN_CR_GIFT_CARD_, 
							(int) $id_cart, 
							(int) $row['product_id'], 
							(int) $row['product_attribute_id'], 
							(int) $id_order
						);
						$amount += (float) $row['unit_price_tax_incl'];
					}
					
				}
				
				if ($amount > 0) {
					//create operation ???
				}
				
			}

			//Change flag status (create gift card only once)
			$db->update('order_kerawen', array('gift_card_flag' => 1), 'id_order = ' . pSQL($id_order));
		}	
			
		$q = "
		UPDATE " . _DB_PREFIX_ . "cart_rule SET active = " . abs($is_paid) . " WHERE id_cart_rule IN (
			SELECT cart_rule_kerawen.id_cart_rule
			FROM " . _DB_PREFIX_ . "cart_rule_kerawen cart_rule_kerawen	
			WHERE cart_rule_kerawen.id_order = " . pSQL($id_order) . " AND cart_rule_kerawen.type = '" . _KERAWEN_CR_GIFT_CARD_ . "'
		)";
		
		$db->execute($q);

	}

}


function slipGiftCard($id_slip, $amount) {

	if (!empty($id_slip)) {

		//$id_slip = 	$slip->id;
	
		$db = Db::getInstance();

		$q = "
		SELECT 
		SUBSTRING_INDEX(GROUP_CONCAT(cart_rule_kerawen.id_cart_rule SEPARATOR ','), ',', order_slip_detail.product_quantity) AS id_cart_rule
		FROM " . _DB_PREFIX_ . "order_slip order_slip
		LEFT JOIN " . _DB_PREFIX_ . "order_slip_detail order_slip_detail ON order_slip.id_order_slip = order_slip_detail.id_order_slip
		LEFT JOIN " . _DB_PREFIX_ . "order_detail order_detail ON order_slip_detail.id_order_detail = order_detail.id_order_detail
		LEFT JOIN " . _DB_PREFIX_ . "cart_rule_kerawen cart_rule_kerawen ON order_slip.id_order = cart_rule_kerawen.id_order AND order_detail.product_id = cart_rule_kerawen.id_product AND order_detail.product_attribute_id = cart_rule_kerawen.id_attribute AND cart_rule_kerawen.type = '" . _KERAWEN_CR_GIFT_CARD_ . "' 
		WHERE id_cart_rule IS NOT NULL AND order_slip.id_order_slip = " .  (int) $id_slip . " 
		GROUP BY order_slip_detail.id_order_detail";

		if ($buf = $db->executeS($q)) {
			foreach ($buf as $row) {
				/*
				$db->delete('cart_rule', 'id_cart_rule IN(' . $row['id_cart_rule'] . ')');
				$db->delete('cart_rule_kerawen', 'id_cart_rule IN(' . $row['id_cart_rule'] . ')');
				*/
				//OR SET cart_rule.active=0 OR cart_rule.quantity=0 ?
				$db->update('cart_rule', array('active' => 0), 'id_cart_rule IN(' . $row['id_cart_rule'] . ')');				
			}
			
			
			/*
			//temporary disable flow -> TODO LATER
			$q = "
			SELECT csk.id_cashdrawer_sale, csk.id_cashdrawer_op, csk.id_order, cfk.amount*(-1) AS amount
			FROM " . _DB_PREFIX_ . "cashdrawer_sale_kerawen  csk
			LEFT JOIN " . _DB_PREFIX_ . "cashdrawer_flow_kerawen cfk ON csk.id_cashdrawer_op = cfk.id_cashdrawer_op
			WHERE csk.id_order_slip = " . (int) $id_slip . " AND csk.oper = 'SLIP'
			";

			if ($row = $db->getRow($q)) {	
				//$row['amount']; //-> not ready yet
				$db->insert('cashdrawer_flow_kerawen', array(
					'id_cashdrawer_op' => $row['id_cashdrawer_op'],
					'id_order' => $row['id_order'],
					'id_payment_mode' => _KERAWEN_PM_GIFT_CARD_,
					'amount' => (float) $amount,
				), true);				
			}
			*/

		}

	}

}


function getGiftCardByTicket($id_order, $active = 1) {
	return Db::getInstance()->executeS("
		SELECT cart_rule.code, cart_rule.reduction_amount AS value, cart_rule.reduction_currency AS currency, cart_rule.reduction_tax AS tax, cart_rule.date_to AS `to`
		FROM " . _DB_PREFIX_ . "cart_rule_kerawen cart_rule_kerawen
		INNER JOIN " . _DB_PREFIX_ . "cart_rule cart_rule ON cart_rule_kerawen.id_cart_rule = cart_rule.id_cart_rule 
		WHERE (cart_rule.active = 1 OR cart_rule.active = " . (int) $active . ") AND cart_rule.quantity = 1 AND cart_rule_kerawen.type= '" . _KERAWEN_CR_GIFT_CARD_ . "' AND (NOW() BETWEEN cart_rule.date_from AND cart_rule.date_to) AND cart_rule_kerawen.id_order = " . (int) $id_order
	);
}


function applyDiscount($context, $params, &$response)
{
	// Ensure rules are active
	Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', '1');
	
	$id_cart = $params->id_cart;
	$value = $params->value;
	$percent = $params->percent;

	$rule = new CartRule();
	$rule->code = generateRuleCode();
	if ($percent)
		$rule->reduction_percent = $value;
	else
	{
		$rule->reduction_currency = $context->currency->id;
		$rule->reduction_amount = $value;
	}
	$rule->partial_use = 0;
	$rule->reduction_tax = !((int) $context->group->price_display_method);
	$rule->quantity = 1;
	$rule->quantity_per_user = 1;

	$rule->date_from = date('Y-m-d H:i:s', time() - 1);
	$rule->date_to = date('Y-m-d H:i:s', strtotime($rule->date_from.' +1 year'));

	$languages = Language::getLanguages(true);
	foreach ($languages as $l)
		$rule->name[(int)$l['id_lang']] = $context->module->l('Cart discount', pathinfo(__FILE__, PATHINFO_FILENAME));

	$rule->add();
	$id_rule = $rule->id;

	// Limit rule to the current cart
	$db = Db::getInstance();
	$sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_kerawen`
				(`id_cart_rule`, `type`, `id_cart`)
				VALUES ('.pSQL($id_rule).',\''._KERAWEN_CR_DISCOUNT_.'\','.pSQL($id_cart).')';
	$db->execute($sql);

	// Use rule immediatly
	require_once (dirname(__FILE__).'/cart.php');
	$params->id_rule = $id_rule;
	addRule($context, $params, $response);
}

function getCartRulesVouchers($id_lang, $id_cart_rule) {
    return Db::getInstance()->executeS('
        SELECT 
            cr.id_cart_rule AS id, 
            crl.name AS `name`,
            cr.description,
            cr.code,
            cr.priority,
            DATE_FORMAT(cr.date_from, "%d/%m/%Y") AS date_from,
            DATE_FORMAT(cr.date_to, "%d/%m/%Y") AS date_to,
            cr.quantity,
            cr.minimum_amount,
            cr.reduction_percent,
            cr.reduction_amount,
            cr.active,
            DATE_FORMAT(crk.display_from, "%d/%m/%Y") AS display_from,
            DATE_FORMAT(crk.display_to, "%d/%m/%Y") AS display_to,
            crk.display_counter
        FROM '._DB_PREFIX_.'cart_rule cr
        LEFT JOIN '._DB_PREFIX_.'cart_rule_lang crl ON cr.id_cart_rule = crl.id_cart_rule AND id_lang = ' . (int)$id_lang . '
        LEFT JOIN '._DB_PREFIX_.'cart_rule_kerawen crk ON cr.id_cart_rule = crk.id_cart_rule
        WHERE 
            crk.is_voucher = 1
            AND (0 = ' . (int) $id_cart_rule . ' OR cr.id_cart_rule = ' . (int) $id_cart_rule . ')
        ORDER BY cr.id_cart_rule DESC
	');
}

function applyCartRuleVoucher($data) {

    $context = Context::getContext();

    if ($data->id > 0) {
       $id_cart_rule = $data->id;
       $code = null;
       $quantity = (int) $data->quantity;
       $quantity_per_user = (int) $data->quantity;
    } else {
       $id_cart_rule = null;
       $code = generateRuleCode();
       $quantity = ((int) $data->quantity == 0) ? 1000 : (int) $data->quantity;
       $quantity_per_user = $quantity;
    }

    if ($data->discount_type == 'percent') {
        $reduction_percent = $data->discount_value;
        $reduction_amount = 0;
    } else {
        $reduction_percent = 0;
        $reduction_amount = $data->discount_value;
    }

    $date_from = implode("-", array_reverse(explode("/", $data->date_from)));
    $date_to = implode("-", array_reverse(explode("/", $data->date_to)));
    $display_from = $data->display_from ? "'" . implode("-", array_reverse(explode("/", $data->display_from))) ." 00:00:00'" : 'NULL';
    $display_to = $data->display_to ? "'" . implode("-", array_reverse(explode("/", $data->display_to))). " 23:59:59'" : 'NULL';
   

    $rule = setKerawenCartRule(
        $id_cart_rule, 
        $code, 
        0,
        $context->currency->id,
        $reduction_percent,
        $reduction_amount,
        1, 
        1, 
        0,
        $data->priority, 
        $quantity, 
        $quantity_per_user, 
        $data->active, 
        $date_from, 
        $date_to, 
        $data->name,
        $data->description,
        _KERAWEN_CR_VOUCHER_,
        'NULL', 
        'NULL', 
        'NULL', 
        'NULL', 
        1, 
        $display_from,
        $display_to,
        $data->minimum_amount,
        1
    ); 

}


function deleteCartRuleVoucher($id_cart_rule) {
    $db = Db::getInstance();
    $rule = new CartRule($id_cart_rule);
    $rule->delete();
    $db->delete('cart_rule_kerawen', 'id_cart_rule = '.pSQL($id_cart_rule));
}

