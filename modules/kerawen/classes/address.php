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

function getAddress($address)
{
	if ($address)
	{
		if (is_int($address)) $address = new Address($address);
		return array(
			'id' => $address->id,
			'alias' => $address->alias,
			'firstname' => $address->firstname,
			'lastname' => $address->lastname,
			'company' => $address->company,
			'address1' => $address->address1,
			'address2' => $address->address2,
			'postcode' => $address->postcode,
			'city' => $address->city,
			'id_country' => $address->id_country,
			'phone' => $address->phone,
			'mobile' => $address->phone_mobile,
		);
	}
}