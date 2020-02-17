<?php
class Product extends ProductCore
{
	/*
    * module: kerawen
    * date: 2020-01-29 13:56:56
    * version: 2.2.14
    */
    public static function priceCalculation($id_shop, $id_product, $id_product_attribute, $id_country, $id_state, $zipcode, $id_currency,
		$id_group, $quantity, $use_tax, $decimals, $only_reduc, $use_reduc, $with_ecotax, &$specific_price, $use_group_reduction,
		$id_customer = 0, $use_customer_price = true, $id_cart = 0, $real_quantity = 0, $id_customization = 0)
	{
		if ($id_cart === null) $id_cart = 0;
		return ProductCore::priceCalculation($id_shop, $id_product, $id_product_attribute, $id_country, $id_state, $zipcode, $id_currency,
			$id_group, $quantity, $use_tax, $decimals, $only_reduc, $use_reduc, $with_ecotax, $specific_price, $use_group_reduction,
			$id_customer, $use_customer_price, $id_cart, $real_quantity, $id_customization);
	}
}
