<?php
/**
 * 2017 KerAwen
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
 * @copyright 2017 KerAwen
 * @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

function audit($controller) {
	$class = pathinfo(__FILE__, PATHINFO_FILENAME);
	require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
	
	$module = $controller->module;
	$context = Context::getContext();
	$warnings = array();
	
	// Check preparation order state
// 	$os_prep = new OrderState(_PS_OS_PREPARATION_, $context->language->id);
// 	$warnings[] = array(
// 		'id' => 'OS_PREPARATION',
// 		'title' => sprintf($module->l('Order state "%1$s"', $class), $os_prep->name),
// 		'desc' => sprintf($module->l('Order state "%1$s" shall not consider the associated order as validated in order to avoid virtual payments to be logged when the order is set to this state, and the order to be set as paid.', $class), $os_prep->name),
// 		'link' => $controller->getAdminLink('AdminStatuses', array(
// 			'updateorder_state' => true,
// 			'id_order_state' => $os_prep->id,
// 		)),
// 		'valid' => !$os_prep->logable,
// 	);
	
	// Check overrides for fixes
	require_once(_KERAWEN_FIX_DIR_.'/fixes.php');
	$fixes = getFixes($module);
	$fix_required = false;
	foreach($fixes as $fix) {
		if ($fix['compliant']) {
			$fix_required = true;
			break;
		}
	}
	$warnings[] = array(
		'id' => 'OVERRIDES',
		'title' => $module->l('Overrides', $class),
		'desc' => $module->l('Overrides shall not be disabled in order PrestaShop fixes are applied.', $class),
		'link' => $controller->getAdminLink('AdminPerformance'),
		'valid' => (!$fix_required || !Configuration::get('PS_DISABLE_OVERRIDES')),
	);

	// Check fidelisa settings
	if (Module::isInstalled('fidelisa') && Module::isEnabled('fidelisa')) {
		
		//New order
		$warnings[] = array(
			'id' => 'FIDELISA_ORDER_SEND_STATE',
			'title' => $module->l('Fidelisa settings', $class),
			'desc' => $module->l('Order stat required to add customer points', $class),
			'link' => $controller->getAdminLink('AdminModules', array(
				'configure' => 'fidelisa',
				'tab_module' => 'other',
				'module_name' => 'fidelisa',
			)),
			'valid' => (Configuration::get('FIDELISA_ORDER_SEND_STATE') == 5),
		);
		
		//Cancel order
		$warnings[] = array(
			'id' => 'FIDELISA_ORDER_CANCEL_STATE',
			'title' => $module->l('Fidelisa settings', $class),
			'desc' => $module->l('Order stat required to remove customer points', $class),
			'link' => $controller->getAdminLink('AdminModules', array(
				'configure' => 'fidelisa',
				'tab_module' => 'other',
				'module_name' => 'fidelisa',
			)),
			'valid' => (Configuration::get('FIDELISA_ORDER_CANCEL_STATE') == 6),
		);
		
		//Slip product
		
	}
	
	
	return $warnings;
}

function correct($issue, $module) {
	switch ($issue) {
		
// 	case 'OS_PREPARATION':
// 		$os_prep = new OrderState(_PS_OS_PREPARATION_);
// 		$os_prep->logable = false;
// 		$os_prep->save();
// 		break;

	case 'OVERRIDES':
		Configuration::updateValue('PS_DISABLE_OVERRIDES', false);
		break;

	case 'FIXES':
		require_once(_KERAWEN_FIX_DIR_.'/fixes.php');
		installAllFixes($module);
		break;

	case 'FIDELISA_ORDER_SEND_STATE':
		Configuration::updateValue('FIDELISA_ORDER_SEND_STATE', 5);
		break;	

	case 'FIDELISA_ORDER_CANCEL_STATE':
		Configuration::updateValue('FIDELISA_ORDER_CANCEL_STATE', 6);
		break;		
		
	}
} 