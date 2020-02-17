<?php
class CartRule extends CartRuleCore
{
	public static $use_generic = true;

	public static function getCustomerCartRules(
		$id_lang, $id_customer,
		$active = false, $includeGeneric = true, $inStock = false,
		Cart $cart = null,
		$free_shipping_only = false, $highlight_only = false)
	{
		return parent::getCustomerCartRules($id_lang, $id_customer, $active,
			self::$use_generic && $includeGeneric = true,
			$inStock, $cart, $free_shipping_only, $highlight_only);
	}
}
