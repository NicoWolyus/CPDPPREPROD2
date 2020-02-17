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

function getKerawenSecureKey()
{
	$key = Configuration::get('KERAWEN_SECURE_KEY');
	if (!$key)
	{
		$key = md5(uniqid(rand(), true));
		Configuration::updateValue('KERAWEN_SECURE_KEY', $key);
	}
	return $key;
}

function getDefaultDeliveryAddress($full = false)
{
	
	$context = Context::getContext();
	$address = Configuration::get('KERAWEN_DEFAULT_ADDRESS');
	if (!Address::addressExists($address)) {
		$customer = getAnonymousCustomer();
		$addr = new Address($address);
		$addr->id_customer = $customer->id;
		$addr->alias = $context->module->l('Shop takeaway');
		$addr->firstname = '-';
		$addr->lastname = '-';
		$addr->company = '-';
		$addr->address1 = $context->module->l('Shop takeaway');
		$addr->city = '-';
		$addr->id_country = $context->country->id;
		$addr->phone = '0100000000';
		$addr->phone_mobile = '0600000000';
		$addr->date_add = Db::getInstance()->getValue('SELECT NOW()');
		$addr->postcode = '0';
		$addr->save();
		$address = $addr->id;
		Configuration::updateValue('KERAWEN_DEFAULT_ADDRESS', $address);
	}
	return $full ? new Address($address) : $address;
}

function getAnonymousCustomer()
{
	$db = Db::getInstance();
	
	$id = Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
	$customer = $id ? new Customer($id) : null;
	if (!Validate::isLoadedObject($customer)) {
		$customer = new Customer();
		$customer->firstname = '-';
		$customer->lastname = '-';
		$customer->optin = 0;
		$customer->email = Configuration::get('PS_SHOP_EMAIL');
		$customer->passwd = Tools::encrypt(Tools::passwdGen(MIN_PASSWD_LENGTH));
		$customer->save();
		Configuration::updateValue('KERAWEN_ANONYMOUS_CUSTOMER', $customer->id);
		
		$db->execute('
			INSERT INTO '._DB_PREFIX_.'customer_kerawen (id_customer, fakemail)
			VALUES ('.pSQL($customer->id).', 1)
			ON DUPLICATE KEY UPDATE fakemail = 1'
		);

	}
	return $customer;
}

function isAnonymousCustomer($customer) {
		
	if ( is_numeric($customer) ) {
		$id_customer = $customer;
	} else {
		$id_customer = isset($customer->id) ? $customer->id : 0;
	}
	
	return ($id_customer == Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER'));
}


