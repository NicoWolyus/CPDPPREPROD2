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

/* REFACTOR */
/*
* Usage of standard class Notification requires usage of global $cookie which is forbidden for modules
* An alternative would be to issue a standard ajax request from browser
*/
function getNotif(/*$context*/)
{
	$db = Db::getInstance();

	/*
	$last = $db->getRow('
		SELECT id_last_order, id_last_customer_message, id_last_customer
		FROM `'._DB_PREFIX_.'employee`
		WHERE `id_employee` = '.pSql($context->employee->id));
	*/
	// Orders that are not handled yet
	/*
	$new_orders = $db->getValue('
		SELECT COUNT(*)
		FROM `'._DB_PREFIX_.'orders`
		WHERE `id_order` > '.pSql($last['id_last_order']));
	*/
	$new_orders = $db->getValue('
		SELECT COUNT(*)
		FROM `'._DB_PREFIX_.'order_kerawen`
		WHERE `preparation_status` = '.pSQL(Configuration::get('KERAWEN_OS_RECEIVED')));

	return array(
		'orders' => (int)$new_orders,
		// TODO
		'messages' => 0
	);
}

function resetNotifOrders($context)
{
	Db::getInstance()->execute('
		UPDATE `'._DB_PREFIX_.'employee`
		SET `id_last_order` = (SELECT IFNULL(MAX(`id_order`), 0) FROM `'._DB_PREFIX_.'orders`)
		WHERE `id_employee` = '.pSql($context->employee->id));
}
