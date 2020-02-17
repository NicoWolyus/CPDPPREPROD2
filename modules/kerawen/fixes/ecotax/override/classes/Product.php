<?php

class Product extends ProductCore
{
	public static $with_ecotax = false;

	public static function getPriceStatic($id_product, $usetax = true, $id_product_attribute = null, $decimals = 6, $divisor = null,
		$only_reduc = false, $usereduc = true, $quantity = 1, $force_associated_tax = false, $id_customer = null, $id_cart = null,
		$id_address = null, &$specific_price_output = null, $with_ecotax = true, $use_group_reduction = true, Context $context = null,
		$use_customer_price = true)
	{
		return parent::getPriceStatic(
			$id_product, $usetax, $id_product_attribute, $decimals, $divisor,
			$only_reduc, $usereduc, $quantity, $force_associated_tax, $id_customer, $id_cart,
			$id_address, $specific_price_output,
			/* Force ecotax when required */ $with_ecotax || Product::$with_ecotax,
			$use_group_reduction, $context, $use_customer_price);
	}
}
