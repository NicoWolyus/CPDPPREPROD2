<?php
/**
 * 2016 KerAwen
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

function getFixes($module) {
	$class = pathinfo(__FILE__, PATHINFO_FILENAME);
	
	$fixes = array(
		
		array(
			'id' => 'invoice',
			'title' => $module->l('Invoice FR', $class),
			'desc' => $module->l('Invoices compliant with FR law', $class),
		),
		
		array(
			'id' => 'tax_detail_free_shipping',
			'title' => $module->l('Free shipping and tax detail', $class),
			'desc' => $module->l('Fixes tax detail computation in case of free shipping', $class),
			'min' => '1.5',
			'max' => '1.6.0.10',
		),
		
		array(
			'id' => 'backup_views',
			'title' => $module->l('Database views backup', $class),
			'desc' => $module->l('Fully backups database, inluding views', $class),
			'min' => '1.5',
			'max' => '1.6',
		),
		
		array(
			'id' => 'ecotax',
			'title' => $module->l('Ecotax computation', $class),
			'desc' => $module->l('Fixes price display in case of ecotax', $class),
			'min' => '1.5',
			'max' => '1.6.1',
		),
		
		array(
			'id' => 'change_delivery_options',
			'title' => $module->l('Delivey options', $class),
			'desc' => $module->l('Makes delivery options applied immediatly', $class),
			'min' => '1.5',
			'max' => '1.6.1',
		),
		
		array(
			'id' => 'new_product_stock',
			'title' => $module->l('New product stock options (multishop)', $class),
			'desc' => $module->l('Applies new product stock options to all shops', $class),
			'min' => '1.5',
			'max' => '1.6.1.1',
		),
		
		array(
			'id' => 'new_order_advanced_stock',
			'title' => $module->l('New order and stock', $class),
			'desc' => $module->l('Impacts stock in case of a new order that is shipped immediatly', $class),
			'min' => '1.5',
		),
		
		array(
			'id' => 'return_advanced_stock',
			'title' => $module->l('Return and advanced stock', $class),
			'desc' => $module->l('Avoids error when returning products and advanced stock value is around 0', $class),
			'min' => '1.5',
		),
		
		array(
			'id' => 'invoice_details',
			'title' => $module->l('Invoice details', $class),
			'desc' => $module->l('Fixes invoice generation when some modules modify order details afterwards', $class),
			'min' => '1.5',
		),
		
		array(
			'id' => 'invoice_after_delivery',
			'title' => $module->l('Invoice generation after delivery slip', $class),
			'desc' => $module->l('Fixes invoice generation when delivery slip is already generated', $class),
			'min' => '1.6.1.0',
			'max' => '1.6.1.2',
		),
		
		array(
			'id' => 'cancel_order_with_deleted_combination',
			'title' => $module->l('Order cancelation and deleted combinations', $class),
			'desc' => $module->l('Fixes order cancelation when a combination has been deleted', $class),
			'min' => '1.6.1.1',
			'max' => '1.6.1.6',
		),
		
		array(
			'id' => 'anonymous_cart_rule',
			'title' => $module->l('Anonymous cart rules', $class),
			'desc' => $module->l('Avoids anonymous cart rules, including credits, to be accessible by everyone', $class),
			'min' => '1.6.1.6',
			'max' => '1.6.1.7',
		),
		
		array(
			'id' => 'cart_specific_price',
			'title' => $module->l('Cart specific prices', $class),
			'desc' => $module->l('Avoids specific prices defined for a given cart to be displayed on web site', $class),
			'min' => '1.6',
		),

	);
	
	$db = Db::getInstance();
	foreach ($fixes as &$fix) {
		$fix['compliant'] = true;
		if (isset($fix['min']) && Tools::version_compare(_PS_VERSION_, $fix['min'], '<')) {
			$fix['compliant'] = false;
		}
		if (isset($fix['max']) && Tools::version_compare(_PS_VERSION_, $fix['max'], '>=')) {
			$fix['compliant'] = false;
		}
		$fix['installed'] = $db->getValue('
				SELECT installed FROM '._DB_PREFIX_.'kerawen_fix
				WHERE name = "'.pSQL($fix['id']).'"');
	}
	return $fixes;
}


function installAllFixes($module) {
	$fixes = getFixes($module);
	foreach ($fixes as $id => $fix) {
		if ($fix['compliant']) {
			if (!$fix['installed']) {
				installFix($id, $module);
			}
		}
		else {
			if ($fix['installed']) {
				uninstallFix($id, $module);
			}
		}
	}
}

function installFix($id, $module) {
	$error = false;
	
	$module->startFixOverride($id);
	try {
		// Use specific installer for consistency
		//$error = !$module->installOverrides();
		$error = !installOverrides($module);
	}
	catch (Exception $e) {
		$error = $e->getMessage();
	}
	$module->doneFixOverride();
	
	if (!$error) {
		Db::getInstance()->execute('
		INSERT INTO '._DB_PREFIX_.'kerawen_fix (name, installed)
		VALUES("'.pSQL($id).'", 1)
		ON DUPLICATE KEY UPDATE installed = VALUES(installed)');
	}
	return $error;
}

/* Specific overrides installer
 * In case of failure with one class, uninstall the other ones
 */
function installOverrides($module)
{
	if (!is_dir($module->getLocalPath().'override')) {
		return true;
	}
	$result = true;
	$classes = array();
	$exception = null;
	foreach (Tools::scandir($module->getLocalPath().'override', 'php', '', true) as $file) {
		$class = basename($file, '.php');
		
		$loader = 'PrestaShopAutoload';
		if (Tools::version_compare(_PS_VERSION_, '1.6', '<')) $loader = 'Autoload';
		
		if ($loader::getInstance()->getClassPath($class.'Core') || Module::getModuleIdByName($class)) {
			try {
				$result &= $module->addOverride($class);
			}
			catch (Exception $e) {
				$result = false;
				$exception = $e;
			}
			if ($result) {
				$classes[] = $class;
			}
			else {
				foreach($classes as $class) {
					$module->removeOverride($class);
				}
				if ($exception) throw $exception;
				break;
			}
		}
	}
	return $result;
}

function uninstallFix($id, $module) {
	$error = false;

	$module->startFixOverride($id);
	$error = !$module->uninstallOverrides();
	$module->doneFixOverride();
	
	Db::getInstance()->execute('
		UPDATE '._DB_PREFIX_.'kerawen_fix
		SET installed = 0 WHERE name = "'.pSQL($id).'"');

	return $error;
}
