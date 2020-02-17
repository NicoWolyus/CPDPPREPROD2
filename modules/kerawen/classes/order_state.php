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

/* Return order states indexed for KerAwen */
function getPrestashopOrderStates()
{
	static $ids = null;
	if ($ids === null) {
		$ids = array(
			Configuration::get('KERAWEN_OS_RECEIVED'),
			Configuration::get('PS_OS_ERROR'),
			Configuration::get('PS_OS_OUTOFSTOCK'),
			Configuration::get('PS_OS_PREPARATION'),
			Configuration::get('KERAWEN_OS_READY'),
			Configuration::get('PS_OS_SHIPPING'),
			Configuration::get('PS_OS_DELIVERED'),
			Configuration::get('PS_OS_CANCELED'),
		);
	}
	return $ids;
}

/* TODO on application side */
function getOrderAction($id_os)
{
	static $actions = null;
	if ($actions === null) {
		$actions = array(
			Configuration::get('KERAWEN_OS_RECEIVED') => 'cancel',
			Configuration::get('PS_OS_ERROR') => 'none',
			Configuration::get('PS_OS_OUTOFSTOCK') => 'cancel',
			Configuration::get('PS_OS_PREPARATION') => 'prepare',
			Configuration::get('KERAWEN_OS_READY') => 'cancel',
			Configuration::get('PS_OS_SHIPPING') => 'return',
			Configuration::get('PS_OS_DELIVERED') => 'return',
			Configuration::get('PS_OS_CANCELED') => 'none',
		);
	}
	return $actions[$id_os];
}


/* Transform PS order state to KerAwen index */
function getKerawenOrderState($id_os)
{
	static $map = null;
	if ($map === null) {
		$buf = getPrestashopOrderStates();
		$map = array_combine($buf, $buf);
	}
	return (int)$map[$id_os];
}

function isOrderStateValid($id_os)
{
	if (!$id_os) return false;
	if ($id_os == (int)Configuration::get('PS_OS_CANCELED')) return false;
	if ($id_os == (int)Configuration::get('PS_OS_ERROR')) return false;
	return true;
}


function checkStockForOrder($id_order, $state)
{
	$order = new Order($id_order);
	$current = new OrderState($order->current_state);
	if (!$current->shipped && $state->shipped) {
		// Check stocks in case of shipping
		$stocks = Db::getInstance()->executeS('
			SELECT
				od.product_id AS id_prod,
				od.product_attribute_id AS id_attr,
				od.product_quantity AS qty_req,
				o.id_shop,
				o.id_shop_group,
				od.id_warehouse,
				sa.depends_on_stock AS depends_attr,
				sa.out_of_stock AS out_attr,
				sa.quantity AS qty_attr,
				sp.depends_on_stock AS depends_prod,
				sp.out_of_stock AS out_prod,
				sp.quantity AS qty_prod,
				sw.id_stock,
				sw.physical_quantity AS qty_phys,
				sw.usable_quantity AS qty_usable
			FROM '._DB_PREFIX_.'orders o
			JOIN '._DB_PREFIX_.'order_detail od
				ON od.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'stock_available sa
				ON sa.id_product = od.product_id
				AND sa.id_product_attribute = od.product_attribute_id
				AND (sa.id_shop = o.id_shop
				OR sa.id_shop_group = o.id_shop_group)
			LEFT JOIN '._DB_PREFIX_.'stock_available sp
				ON sp.id_product = od.product_id
				AND sp.id_product_attribute = 0
				AND (sp.id_shop = o.id_shop
				OR sp.id_shop_group = o.id_shop_group)
			LEFT JOIN '._DB_PREFIX_.'stock sw
				ON sw.id_product = od.product_id
				AND sw.id_product_attribute = od.product_attribute_id
				AND sw.id_warehouse = od.id_warehouse
			WHERE od.id_order = '.pSQL($id_order));
		
		$errors = array();
		foreach($stocks as $stock) {
			$available =
				$stock['depends_attr'] ? (int)$stock['qty_usable'] : (
				$stock['depends_prod'] ? (int)$stock['qty_usable'] : (
				!is_null($stock['qty_attr']) ? (int)$stock['qty_attr'] : (
				!is_null($stock['qty_prod']) ? (int)$stock['qty_prod'] : 0
			)));
			if ($available < $stock['qty_req']) {
				$errors[] = array(
					'id_stock' => $stock['id_stock'],
					'id_product' => $stock['id_prod'],
					'id_product_attribute' => $stock['id_attr'],
					'required' => $stock['qty_req'],
					'available' => $available,
					'source' => $stock,
				);
			}
		}
		if (count($errors)) {
			$e = new Exception('Out of stock');
			$e->out_of_stock = $errors;
			throw $e;
		}
	}
	return false;
}