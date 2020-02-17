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

function getTills($id_till = false, $shop_id = false)
{
	require_once(_KERAWEN_CLASS_DIR_.'/push.php');
	$db = Db::getInstance();

	$tills = $db->executeS('
		SELECT
			cd.id_cash_drawer AS id,
			cd.name AS name,
			cd.active AS active,
			cd.date_add AS date_add,
			IF( cd.token = "" OR ISNULL(cd.token), "", CONCAT("' . _KERAWEN_SERVER_NODE_ . '", "/mag/", cd.token)) AS screenUrl,
			cd.screen AS screen,
			cd.printer AS printer,
			cd.display AS display,
			cd.tpe AS tpe,
			cd.printer_local,
			cd.printer_remote
		FROM '._DB_PREFIX_.'cash_drawer_kerawen cd
		'.($id_till ? 'WHERE cd.id_cash_drawer = '.pSQL($id_till) : ''));
	
	foreach ($tills as &$till) {
		$last_op = false;
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$last_op = $proc->getTillState($till['id']);
		}
		if (!$last_op) {
			$last_op = $db->getRow('
				SELECT
					co.oper AS state,
					co.date AS date
				FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
				WHERE co.id_cashdrawer = '.pSQL($till['id']).'
				AND co.oper IN ("'._KERAWEN_CDOP_OPEN_.'","'._KERAWEN_CDOP_CLOSE_.'")
				ORDER BY co.date DESC');
		}
		if ($last_op) $till = array_merge($till, $last_op);
		
		
		if ($shop_id) {
			$deco = getScreenDecoration((int)$shop_id, $till['id']);
			if ($deco) $till = array_merge($till, $deco);
		}
		
		
		
	}
	
	return $id_till ? $tills[0] : $tills;
}

function updateTill($id_till, $data)
{
	$values = array();
	if (isset($data->name)) $values['name'] = pSQL($data->name);
	if (isset($data->active)) $values['active'] = pSQL($data->active);
	if (isset($data->token)) $values['token'] = pSQL($data->token);
	if (isset($data->screen)) $values['screen'] = pSQL($data->screen);
	if (isset($data->printer_local)) $values['printer_local'] = pSQL($data->printer_local);
	if (isset($data->printer_remote)) $values['printer_remote'] = pSQL($data->printer_remote);
	if (isset($data->printer)) $values['printer'] = pSQL($data->printer);
	if (isset($data->display)) $values['display'] = pSQL($data->display);
	if (isset($data->tpe)) $values['tpe'] = pSQL($data->tpe);
	
	$db = Db::getInstance();
	if ($id_till) {
		$db->update('cash_drawer_kerawen', $values, 'id_cash_drawer = '.pSQL($id_till));
	}
	else {
		$db->insert('cash_drawer_kerawen', $values);
		$id_till = $db->Insert_ID();
	}
	
	$db->execute('
		UPDATE ' . _DB_PREFIX_ . 'cash_drawer_kerawen
		SET date_upd = NOW()
		WHERE id_cash_drawer = ' . (int) $id_till
	);
	
	return $id_till;
	
}


function setTillHardware($mac)
{	
	$db = Db::getInstance();
	$db->execute('
		UPDATE ' . _DB_PREFIX_ . 'cash_drawer_kerawen
		SET printer=1, printer_remote = "", printer_local = "' . pSQL($mac) . '"
		WHERE (printer_remote IS NULL OR printer_remote = "") AND (printer_local IS NULL OR printer_local = "")'
	);	
}


function lastOpenClose($id_till)
{
	$res = Db::getInstance()->getRow('
		SELECT
			oper,
			id_cashdrawer_op AS id_op,
			date
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen
		WHERE id_cashdrawer = '.pSQL($id_till).'
		AND oper IN ("'.pSQL(_KERAWEN_CDOP_OPEN_).'", "'.pSQL(_KERAWEN_CDOP_CLOSE_).'")
		ORDER BY `date` DESC');
	
	if (!$res)
		$res = array(
			'oper' => _KERAWEN_CDOP_OPEN_,
			'id_op' => 0,
			'date' => null,
		);
	
	return $res;
}

function getTillContent($id_till)
{
	$res = array();
	
	$ref = lastOpenClose($id_till);
	$res['ref'] = $ref;

	$db = Db::getInstance();
	$buf = $db->executeS('
		SELECT
			cc.id_payment_mode AS mode,
			cc.checked AS amount
		FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
		WHERE cc.id_cashdrawer_op = '.pSQL($ref['id_op']));
	$content = array();
	foreach ($buf as $row)
		$content[$row['mode']] = array(
			'count' => null, // Were not counted
			'amount' => (float)$row['amount'],
		);
	
	if ($ref['oper'] == _KERAWEN_CDOP_OPEN_)
	{
		$flows = $db->executeS('
			SELECT
				cf.id_payment_mode AS mode,
				cf.amount AS amount
			FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
			JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
				ON cf.id_cashdrawer_op = co.id_cashdrawer_op
			WHERE
				co.id_cashdrawer = '.pSQL($id_till).'
				AND co.date >= "'.pSQL($ref['date']).'"');
		
		foreach ($flows as $flow)
		{
			if (!isset($content[$flow['mode']])) $content[$flow['mode']] = array(
				'count' => null,
				'amount' => 0.0,
			);
			$content[$flow['mode']]['amount'] += (float)$flow['amount'];
		}
	}
	$res['content'] = $content;
	
	// TODO make it configurable
	$res['payments'] = array();
	$modes = array( 2, );
	foreach ($modes as $id_mode)
		$res['payments'][$id_mode] = $db->executeS('
			SELECT DISTINCT
				cf.id_cashdrawer_flow AS id_flow,
				co.date AS date,
				cf.date_deferred AS deferred,
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				cf.amount AS amount,
				cf.id_order_payment AS ps_payment
			FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
			JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen cf ON cf.id_cashdrawer_op = co.id_cashdrawer_op
			LEFT JOIN '._DB_PREFIX_.'order_payment op ON op.id_order_payment = cf.id_order_payment
			LEFT JOIN '._DB_PREFIX_.'orders o ON o.reference = op.order_reference
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			WHERE
				co.id_cashdrawer = '.pSQL($id_till).'
				AND cf.id_payment_mode = '.pSQL($id_mode).'
				AND cf.amount > 0
				AND cf.op_out IS NULL');
	

	// Report payments to modes for new recording
	foreach($res['payments'] as $id_mode => &$payments) {
		if (!isset($res['content'][$id_mode])) {
			$res['content'][$id_mode] = array(
				'count' => 0,
				'amount' => 0.0,
			);
		}
		foreach($payments as &$p) {
			$res['content'][$id_mode]['count']++;
			$res['content'][$id_mode]['amount'] += (float)$p['amount'];
		}
	}
	
	return $res;
}

function openCashdrawer($id_till, $id_employee, $id_shop, $data)
{
	$expected = getTillContent($id_till);
	$expected = $expected['content'];
	
	$db = Db::getInstance();
	$db->insert('cashdrawer_op_kerawen', array(
		'id_cashdrawer' => $id_till,
		'id_employee' => $id_employee,
		'id_shop' => $id_shop,
		'oper' => _KERAWEN_CDOP_OPEN_,
	), true);
	$id_op = $db->Insert_ID();
	
	$onerror = false;
	foreach ($data as $id_mode => $mode_data)
	{
		$buf = isset($expected[$id_mode]) ? (float)$expected[$id_mode]['amount'] : 0;
		$error = $mode_data->checked - $buf;
		if ($error) $onerror = true;
		
		$db->insert('cashdrawer_close_kerawen', array(
			'id_cashdrawer_op' => $id_op,
			'id_payment_mode' => $id_mode,
			'checked' => $mode_data->checked,
			'error' => $error,
		), true);
	}
	
	if ($onerror)
		$db->update('cashdrawer_op_kerawen', array(
			'error' => true,
		), 'id_cashdrawer_op = '.pSQL($id_op));
	
	return $id_op;
}

function closeCashdrawer($id_till, $id_employee, $id_shop, $data) //, $modes)
{
	$expected = getTillContent($id_till);
	$expected = $expected['content'];
	
	$db = Db::getInstance();
	$db->insert('cashdrawer_op_kerawen', array(
		'id_cashdrawer' => $id_till,
		'id_employee' => $id_employee,
		'id_shop' => $id_shop,
		'oper' => _KERAWEN_CDOP_CLOSE_,
	), true);
	$id_op = $db->Insert_ID();

	$on_error = false;
	foreach ($data as $id_mode => $mode_data)
	{
		if (isset($mode_data->withdrawn) && $mode_data->withdrawn)
		{
			$db->insert('cashdrawer_flow_kerawen', array(
				'id_cashdrawer_op' => $id_op,
				'id_payment_mode' => $id_mode,
				'amount' => -$mode_data->withdrawn,
			), true);
		}
		else
			$mode_data->withdrawn = 0;

		if (isset($mode_data->payments))
		{
			$count = 0;
			$amount = 0;
			$error = 0;
			foreach ($mode_data->payments as $payment)
			{
				$count++;
				if (isset($payment->correct)) {
					$amount += $payment->correct;
					$error += $payment->correct - $payment->amount;
					$on_error = true;
				}
				else {
					$amount += $payment->amount;
				}
				$db->update('cashdrawer_flow_kerawen', array(
					'op_out' => pSQL($id_op),
					'corrected' => isset($payment->correct) ? $payment->correct : '',
				), 'id_cashdrawer_flow = '.pSQL($payment->id), 0, true);
				
				// Mark NF525 payment as out
				$db->execute('
					UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment p
					SET p.id_out = 0
					WHERE p.id_order_payment IN (
						SELECT f.id_order_payment
						FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen f
						WHERE f.id_cashdrawer_flow = '.pSQL($payment->id).')');
			}
			
			if ($count)
				$db->insert('cashdrawer_flow_kerawen', array(
					'id_cashdrawer_op' => $id_op,
					'id_payment_mode' => $id_mode,
					'amount' => -$amount,
					'count' => $count,
				), true);
		}
		if (isset($mode_data->amount))
		{
			$buf = isset($expected[$id_mode]) ? (float)$expected[$id_mode]['amount'] : 0;
			$error = $mode_data->amount - $buf;
			if ($error) $on_error = true;
			$remain = $mode_data->amount - $mode_data->withdrawn;

			$db->insert('cashdrawer_close_kerawen', array(
				'id_cashdrawer_op' => $id_op,
				'id_payment_mode' => $id_mode,
				'checked' => $remain,
				'error' => $error,
			), true);
		}
	}

	if ($on_error)
		$db->update('cashdrawer_op_kerawen', array(
			'error' => true,
		), 'id_cashdrawer_op = '.pSQL($id_op));

	// Mark NF525 ops as closed
	$db->execute('
		UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
		SET id_ref = 0, id_close = 0
		WHERE id_till = '.pSQL($id_till));
	
	return $id_op;
}

function logCashFlow($id_cashdrawer, $id_employee, $id_shop, $mode, $amount, $id_credit, $comment)
{
	$db = Db::getInstance();
	$db->insert('cashdrawer_op_kerawen', array(
		'id_cashdrawer' => $id_cashdrawer,
		'id_employee' => $id_employee,
		'id_shop' => $id_shop,
		'oper' => _KERAWEN_CDOP_FLOW_,
	), true);
	$id_op = $db->Insert_ID();

	$db->insert('cashdrawer_flow_kerawen', array(
		'id_cashdrawer_op' => $id_op,
		'id_payment_mode' => $mode,
		'amount' => $amount,
		'id_credit' => $id_credit,
		'comments' => $comment,
	), true);
	
	return $id_op;
}

function getSaleOperation()
{
	$context = Context::getContext();
	$db = Db::getInstance();
	
	$db->insert('cashdrawer_op_kerawen', array(
		'id_cashdrawer' => getExtendedContext('id_cashdrawer', 0),
		'id_employee' => isset($context->employee) ? $context->employee->id : null,
		'id_shop' => isset($context->shop) ? $context->shop->id : 0,
		'oper' => _KERAWEN_CDOP_SALE_,
	), true);
	
	$id_op = $db->Insert_ID();
	return $id_op;
}

$sales_actions = array();

function &getSaleActions($ref) {
	global $sales_actions;
	if (!$ref) {
		if (!isset($sales_actions['noref'])) $sales_actions['noref'] = array();
		return $sales_actions['noref'];
	}
	else {
		if (!isset($sales_actions['byref'])) $sales_actions['byref'] = array();
		if (!isset($sales_actions['byref'][$ref])) $sales_actions['byref'][$ref] = array();
		return $sales_actions['byref'][$ref];
	}
}


function logOrderState($ref, $id_order, $state, $new, $due, $paid, $payment)
{
	$sale_actions = &getSaleActions($ref);
	if (!isset($sale_actions['orders'])) $sale_actions['orders'] = array();
	$sale_actions['orders'][$id_order] = array(
		'reference' => $ref,
		'id_order' => $id_order,
		'state' => $state,
		'new' => $new,
		'canceled' => $state == _KERAWEN_CDSO_CANCEL_,
		'due' => $due,
		'paid' => $paid,
		'payment' => $payment,
	);
}

function logOrderSlip($ref, $id_order, $id_slip)
{
	$sale_actions = &getSaleActions($ref);
	if (!isset($sale_actions['slips'])) $sale_actions['slips'] = array();
	$sale_actions['slips'][] = array(
		'id_order' => $id_order,
		'id_slip' => $id_slip,
	);
}

function logPayment($ref, $id_order, $id_slip, $id_payment, $mode, $amount,
	$id_credit = null, $date = null, $comments = null,
	$delete = false)
{
	$sale_actions = &getSaleActions($ref);
	
	if ($id_order) {
		if (!isset($sale_actions['orders'])) $sale_actions['orders'] = array();
		if (!isset($sale_actions['orders'][$id_order])) {
			$order = new Order($id_order);
			require_once(dirname(__FILE__).'/order_state.php');
			
			$sale_actions['orders'][$id_order] = array(
				'reference' => $ref,
				'id_order' => $id_order,
				'state' => _KERAWEN_CDSO_PAYMENT_,
				'new' => false,
				'canceled' => !isOrderStateValid($order->current_state),
				'due' => $order->total_paid_tax_incl,
				'paid' => $order->total_paid_real,
				'payment' => $order->payment,
			);
		}
	}
	
	if (!isset($sale_actions['payments'])) $sale_actions['payments'] = array();
	if ($delete) {
		if (isset($sale_actions['payments'][$id_payment]))
			unset($sale_actions['payments'][$id_payment]);
	}
	else {
		$sale_actions['payments'][$id_payment] = array(
			'id_order' => $id_order,
			'id_slip' => $id_slip,
			'id_payment' => $id_payment,
			'mode' => $mode,
			'amount' => $amount,
			'id_credit' => $id_credit,
			'date' => $date,
			'comments' => $comments,
		);
	}
}

function logSaleOperation()
{
	global $sales_actions;
	
	if (isset($sales_actions['noref'])) {
		if (isset($sales_actions['byref']) && count($sales_actions['byref'])) {
			// Attach slips & credits to first order
			reset($sales_actions['byref']);
			$ref = key($sales_actions['byref']);
			
			if (isset($sales_actions['noref']['slips'])) {
				$sales_actions['byref'][$ref]['slips'] = $sales_actions['noref']['slips'];
			}
			if (isset($sales_actions['noref']['payments'])) {
				if (!isset($sales_actions['byref'][$ref]['payments'])) $sales_actions['byref'][$ref]['payments'] = array();
				array_merge($sales_actions['byref'][$ref]['payments'], $sales_actions['noref']['payments']);
			}
		}
		else {
			$sales_actions['byref']['_'] = $sales_actions['noref'];
		}
	}
	
	if (isset($sales_actions['byref']) && count($sales_actions['byref'])) {
		// Memorize impacted orders
		$id_orders = array();
		
		foreach ($sales_actions['byref'] as $ref => $sale_actions) {
			if (count($sale_actions)) {
				$db = Db::getInstance();
				$id_op = getSaleOperation();
				$order_main = null;
				
				$status = false;
				$due = 0;
				if (isset($sale_actions['orders'])) {
					foreach ($sale_actions['orders'] as $order) {
						$id_orders[] = $order['id_order'];
						if (!$order_main) {
							$order_main = $order;
							$status = $db->getRow('
								SELECT due, paid FROM '._DB_PREFIX_.'order_ref_kerawen
								WHERE reference = "'.pSQL($order_main['reference']).'"');
						}
						
						if ($order['state'] && $order['state'] != _KERAWEN_CDSO_PAYMENT_) {
							$db->insert('cashdrawer_sale_kerawen', array(
								'id_cashdrawer_op' => $id_op,
								'id_order' => $order['id_order'],
								'oper' => $order['state'],
							), true);
							$due += $order['due']*($order['canceled'] ? -1 : +1);
						}
					}
				}
				
				if (isset($sale_actions['slips'])) {
					foreach ($sale_actions['slips'] as $slip) {
						$id_orders[] = $slip['id_order'];
						$db->insert('cashdrawer_sale_kerawen', array(
							'id_cashdrawer_op' => $id_op,
							'id_order' => $slip['id_order'],
							'id_order_slip' => $slip['id_slip'],
							'oper' => _KERAWEN_CDSO_SLIP_,
						), true);
		
						$s = new OrderSlip($slip['id_slip']);
						$due -= $s->amount;

						require_once(_KERAWEN_CLASS_DIR_.'cartrules.php');
						slipGiftCard($slip['id_slip'], $s->amount);
						
					}
				}
				
				// Regulation before payments
				if (!$status) $status = array(
					'due' => 0.0,
					'paid' => 0.0,
				);
				$regul = $status['due'] - $status['paid'];
				if ($regul != 0.0) {
					$db->insert('cashdrawer_flow_kerawen', array(
						'id_cashdrawer_op' => $id_op,
						'id_payment_mode' => $regul > 0 ? _KERAWEN_PM_PAY_LATER_ : _KERAWEN_PM_REFUND_LATER_,
						'amount' => -$regul,
						'id_order' => $order_main['id_order'],
						'id_order_slip' => null,
						'id_order_payment' => 0,
						'id_credit' => null,
						'date_deferred' => null,
						'comments' => null,
					), true);
				}
				$status['due'] += $due;
				
				if (isset($sale_actions['payments'])) {
					foreach ($sale_actions['payments'] as $payment) {
						$db->insert('cashdrawer_flow_kerawen', array(
							'id_cashdrawer_op' => $id_op,
							'id_payment_mode' => $payment['mode'],
							'amount' => $payment['amount'],
							'id_order' => $payment['id_order'],
							'id_order_slip' => $payment['id_slip'],
							'id_order_payment' => $payment['id_payment'],
							'id_credit' => $payment['id_credit'],
							'date_deferred' => $payment['date'],
							'comments' => $payment['comments'],
						), true);
		
						$status['paid'] += $payment['amount'];
					}
				}
				
				// Regulation after payments
				$regul = round($status['due'] - $status['paid'], 2);
				if ($regul != 0.0) {
					$db->insert('cashdrawer_flow_kerawen', array(
						'id_cashdrawer_op' => $id_op,
						'id_payment_mode' => $regul > 0 ? _KERAWEN_PM_PAY_LATER_ : _KERAWEN_PM_REFUND_LATER_,
						'amount' => $regul,
						'id_order' => $order_main['id_order'],
						'id_order_slip' => null,
						'id_order_payment' => 0,
						'id_credit' => null,
						'date_deferred' => null,
						'comments' => $order_main['payment'],
					), true);
				}
				
			}
		}
		
		// Update all payment statuses
		$refs = $db->executeS('
			SELECT DISTINCT reference FROM '._DB_PREFIX_.'orders
			WHERE id_order IN('.implode(',', array_unique($id_orders)).')');
		foreach ($refs as $ref) {
			updateOrderPaymentStatus($ref['reference']);
		}
	}
}

function updateOrderPaymentStatus($ref) {
	$db = Db::getInstance();
	require_once(_KERAWEN_CLASS_DIR_.'/order_state.php');
	
	$id_order = $db->getValue('
		SELECT id_order FROM '._DB_PREFIX_.'orders
		WHERE reference = "'.pSQL($ref).'"');
	if (!$id_order) return; // No order with given reference
	
	$due = 0;
	$oo = $db->executeS('
		SELECT current_state AS state, total_paid_tax_incl AS total
		FROM '._DB_PREFIX_.'orders
		WHERE reference = "'.pSQL($ref).'"');
	foreach($oo as &$o) {
		if (isOrderStateValid($o['state'])) $due += $o['total'];
	}
		
	$ss = $db->executeS('
		SELECT o.current_state AS state, s.amount AS total
		FROM '._DB_PREFIX_.'order_slip s
		JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
		WHERE o.reference = "'.pSQL($ref).'"');
	foreach($ss as &$s) {
		if (isOrderStateValid($s['state'])) $due -= $s['total'];
	}
	
	$paid = $db->getValue('
		SELECT SUM(op.amount)
		FROM '._DB_PREFIX_.'order_payment op
		LEFT JOIN '._DB_PREFIX_.'order_payment_kerawen opk ON opk.id_order_payment = op.id_order_payment
		WHERE op.order_reference = "'.pSQL($ref).'"
		OR opk.reference = "'.pSQL($ref).'"');
	if (!$paid) $paid = 0;
	
	$due = Tools::ps_round($due, _PS_PRICE_COMPUTE_PRECISION_);
	$paid = Tools::ps_round($paid, _PS_PRICE_COMPUTE_PRECISION_);
	
	$db->execute('
		INSERT INTO '._DB_PREFIX_.'order_ref_kerawen (reference, id_order, due, paid)
		VALUES ("'.pSQL($ref).'", '.pSQL($id_order).', '.pSQL($due).', '.pSQL($paid).')
		ON DUPLICATE KEY UPDATE due = VALUES(due), paid = VALUES(paid)');
	$db->execute('
		UPDATE '._DB_PREFIX_.'order_kerawen ok
		JOIN '._DB_PREFIX_.'orders o ON o.id_order = ok.id_order
		SET ok.is_paid = '.pSQL((int)($due == $paid)).'
		WHERE o.reference = "'.pSQL($ref).'"');
	
	
	// Final status update
	require_once(_KERAWEN_CLASS_DIR_.'cartrules.php');
	setGiftCard($id_order, 'createCredit');

}


function getRemaining($id_till, $id_mode, $date)
{
	return Db::getInstance()->getRow('
		SELECT
			COUNT(cf.id_cashdrawer_flow) AS count,
			SUM(cf.amount) AS amount
		FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
		JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cf.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen cout ON cout.id_cashdrawer_op = cf.op_out
		LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
		WHERE
			co.id_cashdrawer = '.pSQL($id_till).'
			AND cf.id_payment_mode = '.pSQL($id_mode).'
			AND cf.amount > 0
			AND co.date <= "'.pSQL($date).'"
			AND (cout.date IS NULL OR cout.date > "'.pSQL($date).'")');
}

function getOpeningData($id_open)
{
	$data = array();
	$db = Db::getInstance();

	$id_open = str_replace('_', '', $id_open);
	
	// Get opening info
	$data['open'] = $db->getRow('
		SELECT
			co.id_cashdrawer AS id_till,
			co.date AS date,
			CONCAT(e.firstname, " ", e.lastname) AS empl
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd
			ON cd.id_cash_drawer = co.id_cashdrawer
		LEFT JOIN '._DB_PREFIX_.'employee e
			ON e.id_employee = co.id_employee
		WHERE co.id_cashdrawer_op = '.pSQL($id_open));
	
	$id_till = $data['open']['id_till'];
	
	$data['open']['modes'] = $db->executeS('
		SELECT
			cc.id_payment_mode AS mode,
			cc.checked AS checked,
			cc.error AS error
		FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
		WHERE cc.id_cashdrawer_op = '.pSQL($id_open));
	// TODO improve
	$data['open']['cheques'] = getRemaining($id_till, 2, $data['open']['date']);
		
	$data['till'] = $db->getRow('
		SELECT
			id_cash_drawer AS id_till,
			name AS name
		FROM '._DB_PREFIX_.'cash_drawer_kerawen
		WHERE id_cash_drawer = '.pSQL($id_till));
	
	// Find previous closing
	$data['close'] = $db->getRow('
		SELECT
			co.id_cashdrawer_op AS id_op,
			co.date AS date,
			CONCAT(e.firstname, " ", e.lastname) AS empl
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		LEFT JOIN '._DB_PREFIX_.'employee e
			ON e.id_employee = co.id_employee
		WHERE
			co.id_cashdrawer = '.pSQL($data['open']['id_till']).'
			AND co.oper = "'.pSQL(_KERAWEN_CDOP_CLOSE_).'"
			AND co.date < "'.$data['open']['date'].'"
		ORDER BY co.date DESC');
	
	if ($data['close'])
	{
		$data['close']['modes'] = $db->executeS('
			SELECT
				cc.id_payment_mode AS mode,
				cc.checked AS checked
			FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
			WHERE cc.id_cashdrawer_op = '.pSQL($data['close']['id_op']));
		// TODO improve
		$data['close']['cheques'] = getRemaining($id_till, 2, $data['close']['date']);
	}
		
	return $data;
}

function getClosingData($id_close, $id_till = null)
{
	$data = array(
		'close' => null,
		'state' => null,
	);
	$db = Db::getInstance();

	if ($id_close)
	{
		$id_close = str_replace('_', '', $id_close);
		// Get closing info
		$data['close'] = $db->getRow('
			SELECT
				co.id_cashdrawer AS id_till,
				co.date AS date,
				CONCAT(e.firstname, " ", e.lastname) AS empl
			FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
			LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd
				ON cd.id_cash_drawer = co.id_cashdrawer
			LEFT JOIN '._DB_PREFIX_.'employee e
				ON e.id_employee = co.id_employee
			WHERE co.id_cashdrawer_op = '.pSQL($id_close));
		
		$id_till = $data['close']['id_till'];
		
		$data['close']['modes'] = $db->executeS('
			SELECT
				cc.id_payment_mode AS mode,
				cc.checked AS checked,
				cc.error AS error
			FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
			WHERE cc.id_cashdrawer_op = '.pSQL($id_close));
		$data['close']['cheques'] = getRemaining($id_till, 2, $data['close']['date']);
	}
	elseif ($id_till)
	{
		// TODO optimize
		$content = getTillContent($id_till);
		$modes = array();
		foreach($content['content'] as $mode => $data)
			$modes[] = array(
				'mode' => $mode,
				'checked' => $data['amount'],
			);
		
		$data['state'] = array(
			'modes' => $modes,
			'cheques' => getRemaining($id_till, 2, date('Y-m-d H:i:s')),
		);
	}
	else return array();
	
	$data['till'] = $db->getRow('
		SELECT
			id_cash_drawer AS id_till,
			name AS name
		FROM '._DB_PREFIX_.'cash_drawer_kerawen
		WHERE id_cash_drawer = '.pSQL($id_till));
	
	$op_filter = 'co.id_cashdrawer = '.pSQL($id_till);
	$op_filter .= isset($data['close']) && $data['close'] ? (' AND co.date <= "'.pSQL($data['close']['date'])).'"' : '';
	
	// Find previous opening
	$data['open'] = $db->getRow('
		SELECT
			co.id_cashdrawer_op AS id_op,
			co.date AS date,
			CONCAT(e.firstname, " ", e.lastname) AS empl
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		LEFT JOIN '._DB_PREFIX_.'employee e
			ON e.id_employee = co.id_employee
		WHERE
			'.$op_filter.'
			AND co.oper = "'.pSQL(_KERAWEN_CDOP_OPEN_).'"
		ORDER BY co.date DESC');
	
	if ($data['open'])
	{
		$data['open']['modes'] = $db->executeS('
			SELECT
				cc.id_payment_mode AS mode,
				cc.checked AS checked
			FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
			WHERE cc.id_cashdrawer_op = '.pSQL($data['open']['id_op']));
		// TODO improve
		$data['open']['cheques'] = getRemaining($id_till, 2, $data['open']['date']);
		
		$op_filter .= ' AND co.date >= "'.pSQL($data['open']['date']).'"';
	}
	
	// TODO generic and improve
	$data['cheques'] = $db->executeS('
			SELECT
				co.id_cashdrawer_op AS id,
				co.date AS date,
				CONCAT(e.firstname, " ", e.lastname) AS employee,
				cf.id_payment_mode AS mode,
				cf.amount AS amount,
				cf.corrected AS corrected,
				cf.date_deferred AS deferred,
				cout.id_cashdrawer_op AS op_out,
				cout.date AS date_out
			FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
			JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen cf ON cf.id_cashdrawer_op = co.id_cashdrawer_op
			LEFT JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen cout ON cout.id_cashdrawer_op = cf.op_out
			LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
			WHERE
				co.id_cashdrawer = '.pSQL($id_till).'
				AND cf.id_payment_mode = 2
				AND cf.amount > 0
				'.(isset($data['close']) && $data['close'] ? ('AND co.date <= "'.$data['close']['date'].'"') : '').'
				AND (cout.date IS NULL
					'.($data['open'] ? ('OR cout.date >= "'.$data['open']['date'].'"') : '').'
				)');
	
	$data['flows'] = $db->executeS('
		SELECT
			co.id_cashdrawer_op AS id_op,
			co.oper AS oper,
			co.date AS date,
			CONCAT(e.firstname, " ", e.lastname) AS employee,
			cf.id_payment_mode AS mode,
			cf.amount AS amount,
			cf.count AS count
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
			ON cf.id_cashdrawer_op = co.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'employee e
			ON e.id_employee = co.id_employee
		WHERE '.$op_filter);

	$data['sales'] = $db->executeS('
		SELECT
			co.id_cashdrawer_op AS id,
			co.date AS date,
			CONCAT(e.firstname, " ", e.lastname) AS employee,
			cs.oper AS oper,
			CASE cs.oper
				WHEN "'._KERAWEN_CDSO_ORDER_.'" THEN o.total_paid_tax_incl
				WHEN "'._KERAWEN_CDSO_CANCEL_.'" THEN -o.total_paid_tax_incl
				WHEN "'._KERAWEN_CDSO_SLIP_.'" THEN -os.amount
				ELSE 0 END AS amount
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		JOIN '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			ON cs.id_cashdrawer_op = co.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'employee e
			ON e.id_employee = co.id_employee
		LEFT JOIN '._DB_PREFIX_.'orders o
			ON o.id_order = cs.id_order
		LEFT JOIN '._DB_PREFIX_.'order_slip os
			ON os.id_order_slip = cs.id_order_slip
		WHERE '.$op_filter);

	require_once(dirname(__FILE__).'/log.php');
	$data['more'] = array(
		'sales' => LogQuery::sales($db, $op_filter),
		'taxes' => LogQuery::taxes($db, $op_filter),
		'prods' => LogQuery::prodStats($db, $op_filter),
		// TODO everything from here
		//'flows' => LogQuery::flows($db, $op_filter),
		//'checks' => LogQuery::checks($db, $op_filter),
	);
	
	return $data;
}

function getFlowData($id_flow)
{
	$db = Db::getInstance();
	
	$id_flow = str_replace('_', '', $id_flow);
	
	$data = $db->getRow('
		SELECT
			co.id_cashdrawer AS id_till,
			cd.name AS till,
			co.date AS date,
			CONCAT(e.firstname, " ", e.lastname) AS empl
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd
			ON cd.id_cash_drawer = co.id_cashdrawer
		LEFT JOIN '._DB_PREFIX_.'employee e
			ON e.id_employee = co.id_employee
		WHERE id_cashdrawer_op = '.pSQL($id_flow));

	$data['flows'] = $db->executeS('
		SELECT
			cf.id_payment_mode AS mode,
			cf.amount AS amount,
			cf.comments AS note
		FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
		WHERE cf.id_cashdrawer_op = '.pSQL($id_flow));
	
	return $data;
}

/*
 * ==================================================================
 * TODO refactor from here
 */
function getCashdrawersFiltersData($context, &$response)
{
	require_once (_KERAWEN_API_DIR_.'/bean/ListBean.php');
	require_once (_KERAWEN_API_DIR_.'/bean/CashdrawerBean.php');
	require_once (_KERAWEN_API_DIR_.'/bean/EmployeeBean.php');

	// Cashdrawers
	$cashdrawer_list = new ListBean(CashdrawerBean::LIST_NAME);

	// Add "ALL" cashdrawer
	$cashdrawer = new CashdrawerBean();
	$cashdrawer->set('id', _KERAWEN_CD_ALL_)->set('name', $context->module->l('All', pathinfo(__FILE__, PATHINFO_FILENAME)));
	$cashdrawer_list->add($cashdrawer);
	// Add "WEB" cashdrawer
	$cashdrawer = new CashdrawerBean();
	$cashdrawer->set('id', _KERAWEN_CD_NONE_)->set('name', $context->module->l('Web', pathinfo(__FILE__, PATHINFO_FILENAME)));
	$cashdrawer_list->add($cashdrawer);
	
	$sql_cashdrawer = 'SELECT
			cd.`id_cash_drawer`,
			cd.`name`
		FROM '._DB_PREFIX_.'cash_drawer_kerawen cd
		ORDER BY
			cd.date_add ASC';
	$result_cashdrawer = Db::getInstance()->executeS($sql_cashdrawer);

	foreach ($result_cashdrawer as &$row)
	{
		$cashdrawer = new CashdrawerBean();
		$cashdrawer->set('id', $row['id_cash_drawer'])->set('name', $row['name']);
		$cashdrawer_list->add($cashdrawer);
	}
	unset($row);

	// Employees
	$employee_list = new ListBean(EmployeeBean::LIST_NAME);

	// Add "ALL" employee
	$employee = new EmployeeBean();
	$employee->set('id', _KERAWEN_CD_ALL_)->set('firstname', '')->set('lastname', $context->module->l('All', pathinfo(__FILE__, PATHINFO_FILENAME)));
	$employee_list->add($employee);
	// Add "WEB" employee
	$employee = new EmployeeBean();
	$employee->set('id', _KERAWEN_CD_NONE_)->set('firstname', '')->set('lastname', $context->module->l('Web', pathinfo(__FILE__, PATHINFO_FILENAME)));
	$employee_list->add($employee);

	$sql_employee = 'SELECT e.`id_employee`, e.`lastname`, e.`firstname`
			FROM `'._DB_PREFIX_.'employee` e';
	$result_employee = Db::getInstance()->executeS($sql_employee);
	foreach ($result_employee as &$row)
	{
		$employee = new EmployeeBean();
		$employee->set('id', $row['id_employee'])->set('firstname', $row['firstname'])->set('lastname', $row['lastname']);
		$employee_list->add($employee);
	}
	unset($row);

	$response->addResult($cashdrawer_list);
	$response->addResult($employee_list);
}
