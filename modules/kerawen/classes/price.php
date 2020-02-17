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
* @copyright 2016 KerAwen
* @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
*/

function getCatalogPrice($id_prod, $id_attr, $with_reduc) {
	static $SpecificType = array(
		'amount'		=> 'AMOUNT',
		'percentage'	=> 'PERCENT',
	);
	
	$context = Context::getContext();
	
	$specific = false;
	$ti = Product::priceCalculation(
		$context->shop->id,
		$id_prod, $id_attr,
		$context->country->id, null, null,
		$context->currency->id,
		$context->group->id,
		1,
		true, 2, false,
		$with_reduc, true, $specific, true,
		$context->customer->id, true,
		0, 0);
	$te = Product::priceCalculation(
		$context->shop->id,
		$id_prod, $id_attr,
		$context->country->id, null, null,
		$context->currency->id,
		$context->group->id,
		1,
		false, 2, false,
		$with_reduc, true, $specific, true,
		$context->customer->id, true,
		0, 0);
	
	return array(
		'ti' => $ti,
		'te' => $te,
		'discount' => $specific ? $SpecificType[$specific['reduction_type']] : false,
	);
}
