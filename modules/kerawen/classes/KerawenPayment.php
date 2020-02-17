<?php
/**
 * 2015 KerAwen
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
 * @copyright 2015 KerAwen
 * @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

class KerawenPayment
{
	static $modes = null;
	
	static function getModes($module)
	{
		if (!self::$modes)
		{
			$class = pathinfo(__FILE__, PATHINFO_FILENAME);
				
			self::$modes = array(
				array(
					'id' => _KERAWEN_PM_CASH_,
					'label' => $module->l('Cash', $class),
					'payment' => true,
					'refund' => true,
				),
				array(
					'id' => _KERAWEN_PM_CHEQUE_,
					'label' => $module->l('Cheque', $class),
					'payment' => true,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_CARD_,
					'label' => $module->l('Credit card', $class),
					'payment' => true,
					'refund' => true,
				),
				array(
					'id' => _KERAWEN_PM_BANK_,
					'label' => $module->l('Bank transfer', $class),
					'payment' => true,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_VOUCHER_,
					'label' => $module->l('Purchase voucher', $class),
					'payment' => true,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_MEAL_,
					'label' => $module->l('Meal voucher', $class),
					'payment' => true,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_CREDIT_,
					'label' => $module->l('Credit', $class),
					'payment' => true,
					'refund' => true,
				),
				/*
				array(
					'id' => _KERAWEN_PM_PREPAID_,
					'label' => $module->l('Prepaid account', $class),
					'payment' => true,
					'refund' => true,
				),
				*/
				array(
					'id' => _KERAWEN_PM_SPLIT_,
					'label' => $module->l('Split payment', $class),
					'payment' => true,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_PAY_LATER_,
					'label' => $module->l('Deferred payment', $class),
					'payment' => true,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_REFUND_LATER_,
					'label' => $module->l('Deferred refunding', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER1_,
					'label' => $module->l('Other payment mode 1', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER2_,
					'label' => $module->l('Other payment mode 2', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER3_,
					'label' => $module->l('Other payment mode 3', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER4_,
					'label' => $module->l('Other payment mode 4', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER5_,
					'label' => $module->l('Other payment mode 5', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER6_,
					'label' => $module->l('Other payment mode 6', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER7_,
					'label' => $module->l('Other payment mode 7', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER8_,
					'label' => $module->l('Other payment mode 8', $class),
					'payment' => false,
					'refund' => false,
				),	
				array(
					'id' => _KERAWEN_PM_OTHER9_,
					'label' => $module->l('Other payment mode 9', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER10_,
					'label' => $module->l('Other payment mode 10', $class),
					'payment' => false,
					'refund' => false,
				),
				array(
					'id' => _KERAWEN_PM_OTHER11_,
					'label' => $module->l('Other payment mode 11', $class),
					'payment' => false,
					'refund' => false,
				),
					
			);
		}
		return self::$modes;
	}
}
