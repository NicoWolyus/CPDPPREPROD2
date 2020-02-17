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

require_once (dirname(__FILE__).'/../defines.php');
require_once (_KERAWEN_DIR_.'/kerawen.php');
require_once (_KERAWEN_API_DIR_.'/request.php');
require_once (_KERAWEN_API_DIR_.'/handler.php');
require_once (_KERAWEN_TOOLS_DIR_.'/utils.php');
require_once (_KERAWEN_CLASS_DIR_.'/data.php');
require_once (_KERAWEN_CLASS_DIR_.'/context.php');
require_once (_KERAWEN_CLASS_DIR_.'/permissions.php');

$context = Context::getContext();
$request = new Request(Tools::jsonDecode(Tools::file_get_contents('php://input')), $context);
$cookie = new Cookie('psAdmin');
$employee = new Employee($cookie->id_employee);
$kerawen = Module::getInstanceByName('kerawen');

$error = false;

if (!$error && !$employee->isLoggedBack()) {
	$request->setError(-1, $kerawen->l('Operator is not logged in', pathinfo(__FILE__, PATHINFO_FILENAME)));
	$error = true;
}

if (!$error) {
	if (!isset($request->version) || $request->version != $kerawen->version) {
		$request->setError(-2, $kerawen->l('Page version is out of date', pathinfo(__FILE__, PATHINFO_FILENAME)));
		$error = true;
	}
}

if (!$error) {
	/* Override some configuration parameters during the request only */
	Configuration::set('PS_CATALOG_MODE', 0);

	/* Build context */
	$context->cookie = $cookie;
	$context->language = new Language($cookie->id_lang);
	$context->currency = Currency::getDefaultCurrency();
	$context->module = new Kerawen();

	if ($cookie->id_employee_kerawen)
		$context->employee = new Employee($cookie->id_employee_kerawen);
	else
		$context->employee = $employee;
	
	//TODO: Simplified Mode or Advanced Mode 
	$permissions = new kerawenPermissions();
	$context->permissions = $permissions->getKerawenPermissionsByEmployee(($context->employee->id) ? $context->employee->id : 0);
	
	if (isset($request->params->id_shop) && $request->params->id_shop) {
		$context->shop = new Shop($request->params->id_shop);
		Shop::setContext(Shop::CONTEXT_SHOP, $request->params->id_shop);
	}
	
	setCustomerContext(isset($request->params->id_cust) ? $request->params->id_cust : false);
	
	/* Extended context, TO BE COMPLETED */
	setExtendedContext('kerawen', true);
	if (isset($request->params->id_cashdrawer))
		setExtendedContext('id_cashdrawer', $request->params->id_cashdrawer);

	$method = $request->method;
	$handler = new Handler();
	try {
		$result = $handler->$method($request);
		/* TEMP backward compatibility */
		if ($result !== null)
			$request->result = $result;
		
		// Profiling
		if (_PS_DEBUG_PROFILING_) {
			require_once (_KERAWEN_TOOLS_DIR_.'/profiling.php');
			$request->result['profiling'] = getProfiling();
		}
	}
	catch (Exception $e) {
		unset($request->context);
		unset($request->result);
		echo '<pre>';
		echo 'Exception: '.$e->getMessage();
		echo '<hr>';
		echo $e->getTraceAsString();
		echo '<hr>';
		print_r($request);
		echo '</pre>';
		exit();
	}
}

/* Send response */
$request->finalize();
header('Content-Type: application/json');
echo Tools::jsonEncode($request);
exit();
