<?php
class SpecificPrice extends SpecificPriceCore
{
	/*
    * module: kerawen
    * date: 2020-01-29 13:56:56
    * version: 2.2.14
    */
    public static function computeExtraConditions(
			$id_product, $id_product_attribute, $id_customer, $id_cart,
			$beginning = null, $ending = null)
	{
		return parent::computeExtraConditions(
			$id_product, $id_product_attribute, $id_customer,
			(int)$id_cart, // Avoid specific price defined for any cart
			$beginning, $ending);
	}
	
	/*
    * module: kerawen
    * date: 2020-01-29 13:56:56
    * version: 2.2.14
    */
    public static function getQuantityDiscounts(
			$id_product, $id_shop, $id_currency, $id_country, $id_group,
			$id_product_attribute = null, $all_combinations = false, $id_customer = 0)
	{
		$res = parent::getQuantityDiscounts(
			$id_product, $id_shop, $id_currency, $id_country, $id_group,
			$id_product_attribute, $all_combinations, $id_customer);
		
		foreach($res as $index => $price) {
			if ($price['id_cart'] != 0) unset($res[$index]);
		}
		return $res;
	}
}
