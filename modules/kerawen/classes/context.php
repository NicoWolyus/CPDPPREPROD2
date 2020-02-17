<?php
/**
* @author    KerAwen <contact@kerawen.com>
* @copyright 2019 KerAwen
*/

function setCustomerContext($id_cust) {
	require_once (_KERAWEN_CLASS_DIR_.'/data.php');
	$context = Context::getContext();
	
	$context->customer = $id_cust ? new Customer($id_cust) : getAnonymousCustomer();
	$id_cust = $context->customer->id;
	
	$override_group = Configuration::get('KERAWEN_OVERRIDE_GROUP');
	if ($override_group) {
		// Force customer to be in overriden group
		$context->customer->id_default_group = $override_group;
	
		$class = new ReflectionClass('CustomerCore');
		$prop = $class->getProperty('_defaultGroupId');
		$prop->setAccessible(true);
		$buf = $prop->getValue(null);
		//$buf[(int)$id_cust] = array();
		$buf[(int)$id_cust] = (int)$override_group;
		$prop->setValue(null, $buf);
	
		Customer::getGroupsStatic($id_cust);
		$prop = $class->getProperty('_customer_groups');
		$prop->setAccessible(true);
		$buf = $prop->getValue(null);
		//$buf[(int)$id_cust] = array();
		$buf[(int)$id_cust][] = (int)$override_group;
		$prop->setValue(null, $buf);
	}
	$context->group = new Group($context->customer->id_default_group);
}