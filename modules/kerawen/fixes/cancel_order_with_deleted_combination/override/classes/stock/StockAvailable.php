<?php

class StockAvailable extends StockAvailableCore
{
	public static function updateQuantity($id_product, $id_product_attribute, $delta_quantity, $id_shop = null)
	{
		if ((int)$id_product_attribute) {
			$combination = new Combination((int)$id_product_attribute, null, $id_shop);
			if (!$combination->id_product) {
				return false;
			}
		}
		return parent::updateQuantity($id_product, $id_product_attribute, $delta_quantity, $id_shop);
	}
}
