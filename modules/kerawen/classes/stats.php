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


function checkEmployeePassword($id_employee, $password) {

	$q = '
		SELECT e.id_employee
		FROM '._DB_PREFIX_.'employee e
		LEFT JOIN '._DB_PREFIX_.'employee_kerawen ek ON e.id_employee = ek.id_employee
		WHERE e.id_employee = ' . pSQL($id_employee) . ' AND (ek.password="' . pSQL(Tools::encrypt($password)) . '" OR ek.password = "" OR ISNULL(ek.password) )'
	;
	
	return (int) Db::getInstance()->getValue($q);

}


function getEmployeePasswordStatus($id_employee) {
	
	$q = '
		SELECT id_employee
		FROM '._DB_PREFIX_.'employee_kerawen
		WHERE id_employee = ' . pSQL($id_employee) . ' AND NOT (password = "" OR ISNULL(password))'		
	;
	return ((int) Db::getInstance()->getValue($q) == 0) ? false : true;
}


function getEmployeeByPassword($id_employee, $password) {

	if (!Configuration::get('KERAWEN_EMPLOYEE_PASSWORD')) {
		return true;
	}
	
	return (checkEmployeePassword($id_employee, $password) == 0) ? false : true;
}	

function getEmployeeShops($employee) {

	$shops = array();

	if ($employee) {
	
		if ($employee->id_profile == 1) {
			$dd = Db::getInstance()->executeS('SELECT id_shop AS id FROM '._DB_PREFIX_.'shop WHERE active = 1 ORDER BY id_shop');
		} else {
			$dd = Db::getInstance()->executeS('
				SELECT es.id_shop AS id
				FROM ' . _DB_PREFIX_ . 'employee_shop es
				INNER JOIN ' . _DB_PREFIX_ . 'shop s ON es.id_shop = s.id_shop
				WHERE es.id_employee = ' . (int) $employee->id . '
				ORDER BY es.id_shop
			');
		}	
		
		if ($dd) {
			foreach ($dd as $d) {
				$shops[] = $d['id'];
			}
		}		

	}
		
	return $shops;
	
}


function getEmployeeTills($employee) {

	$tills = array();
	
	if ($employee) {
	
		if ($employee->id_profile == 1) {
			$ds = Db::getInstance()->executeS('SELECT id_cash_drawer AS id FROM '._DB_PREFIX_.'cash_drawer_kerawen ORDER BY id_cash_drawer');
		} else {
			$ds = Db::getInstance()->executeS('SELECT id_cash_drawer AS id FROM '._DB_PREFIX_.'employee_cash_drawer_kerawen WHERE id_employee=' . (int) $employee->id . ' ORDER BY id_cash_drawer');
		}	
	
		if ($ds) {
			foreach ($ds as $d) {
				$tills[] = $d['id'];
			}
		}	
		
	}
	
	return $tills;

}



function getEmployee($id_empl)
{
	$employee = new Employee($id_empl);
	$id_profile = (int) $employee->id_profile;
	
	//Force permission Kerawen module - exclude superAdmin
	if ($id_profile > 1) {
		$id_module = (int) Context::getContext()->module->id;
		$name_module = Context::getContext()->module->name;
		
		if (Tools::version_compare(_PS_VERSION_, '1.7', '<')) {
			//PS 1.5, 1.6
			Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'module_access`
					(`id_profile`, `id_module`, `view`, `configure`, `uninstall`)
					VALUES (' . $id_profile . ', ' . $id_module . ', 1, 0, 0)
				ON DUPLICATE KEY UPDATE `view` = 1
			');	
		} else  {
			//PS 1.7
			$access = new Access;			
			$data_access = $access->updateLgcModuleAccess((int)$id_profile, $id_module, "view", 1);
		}
	}
	
	//(Re)Define context permission
	require_once (_KERAWEN_CLASS_DIR_.'/permissions.php');
	$permissions = new kerawenPermissions();
	Context::getContext()->permissions = $permissions->getKerawenPermissionsByEmployee( (int) $id_empl );
	
	return array(
		'id' => $employee->id,
		'firstname' => $employee->firstname,
		'lastname' => $employee->lastname,
		'sales' => getDailyTurnover($id_empl)->get('amount'),
		//'shops' => $employee->getAssociatedShops(),
		'shops' => getEmployeeShops($employee),
		'id_profile' => $employee->id_profile,
		'permissions' => Context::getContext()->permissions,
		'tills' => getEmployeeTills($employee),
	);
}

function getEmployeeStats($context, &$response)
{
	require_once (_KERAWEN_API_DIR_.'/bean/EmployeeStatBean.php');
	$id_empl = $context->employee->id;
	$employee = new Employee($id_empl);
	$e_name = $employee->firstname.' '.$employee->lastname;

	$stat = new EmployeeStatBean();
	$stat->set('id', $id_empl);
	$stat->set('employee', $e_name);
	$stat->set('turnover', getDailyTurnover($id_empl));
	$response->addResult($stat);
}

function getDailyTurnover($id_empl)
{
	require_once (_KERAWEN_API_DIR_.'/bean/TurnoverBean.php');

	$date_from = date('Y-m-d 00:00:00', time());
	$date_to = date('Y-m-d 00:00:00', strtotime($date_from.' +1 day'));

	$total = Db::getInstance()->getValue('
		SELECT 
			SUM(total_ti) AS total_ti, 
			SUM(total_te) AS total_te
		FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation ko 
		LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale ks ON ko.id_operation = ks.id_operation
		WHERE ko.id_till > 0 AND ko.date BETWEEN "' . $date_from . '" AND "' . $date_to . '"  
		AND ko.id_operator = ' . pSQL($id_empl) . '
	');

	$turnover_bean = new TurnoverBean();
	$turnover_bean->set('date-from', $date_from)->set('date-to', $date_to)->set('amount', $total);
	return $turnover_bean;
}

function roundTimeOp($date_from, $date_to, $time)
{
	static $time_scale = null;
	static $mounths = array();
	static $time_lapse = null;

	if (null === $time_scale)
	{
		$time_lapse = ($date_to - $date_from) / 3600; // Hours between from and to

		if ($time_lapse <= 24)
			$time_scale = 1800; // half an hour
		else if ($time_lapse <= 720)
			$time_scale = 86400; // 1 day
		else
			{
			$mounth = 0;
			$tmp = null;
			do
			{
				$tmp = mktime(0, 0, 0, date('m') - $mounth, 1);
				$mounths[$mounth] = $tmp;
				$mounth++;
			}
			while ($tmp > ($date_from - 2678400)); // 2 678 400 = 31*24*3600
			$time_scale = 'mounth';
		}
	}
	//	var_dump($mounths, $date_from);
	if ('mounth' === $time_scale)
	{
		$i = 0;
		do
			if ($time < $mounths[$i + 1])
				return $mounths[$i];
		while ($i++);
	}
	else
		return $time - $time % $time_scale;
}

function getPaymentsByCriterea($criterea, $result)
{
	require_once (_KERAWEN_API_DIR_.'/bean/ListBean.php');
	require_once (_KERAWEN_API_DIR_.'/bean/LogSummaryBean.php');
	require_once (_KERAWEN_API_DIR_.'/bean/PaymentSummaryBean.php');

	$data = array();

	foreach ($result as &$op)
	{
		if (! isset($data[$op[$criterea]]))
		{
			$data[$op[$criterea]] = array(
				'in' => 0,
				'out' => 0
			);
		}
		$data[$op[$criterea]]['in'] += $op['amount'];
	}
	unset($op);

	$summary = new LogSummaryBean();
	$summary->set('title', $criterea)->set('payments', new ListBean(PaymentSummaryBean::LIST_NAME));

	foreach ($data as $mode => $datum)
	{
		$payment = new PaymentSummaryBean();
		$payment->set('mode', $mode)->set('in', $datum['in'])->set('out', $datum['out']);
		$summary->get('payments')->add($payment);
	}

	return $summary;
}
