<?php
class SpecificPrice extends SpecificPriceCore
{
	public static function computeExtraConditions(
			$id_product, $id_product_attribute, $id_customer, $id_cart,
			$beginning = null, $ending = null)
	{
		return parent::computeExtraConditions(
			$id_product, $id_product_attribute, $id_customer,
			(int)$id_cart, // Avoid specific price defined for any cart
			$beginning, $ending);
	}
	
	public static function getQuantityDiscounts(
			$id_product, $id_shop, $id_currency, $id_country, $id_group,
			$id_product_attribute = null, $all_combinations = false, $id_customer = 0)
	{
		$res = parent::getQuantityDiscounts(
			$id_product, $id_shop, $id_currency, $id_country, $id_group,
			$id_product_attribute, $all_combinations, $id_customer);
		
		// Remove specific cart prices
		// Internal call to self::computeExtraConditions doesn't go through above
		foreach($res as $index => $price) {
			if ($price['id_cart'] != 0) unset($res[$index]);
		}
		return $res;
	}
}
