<?php

class StockAvailable extends StockAvailableCore
{
	public static $ApplyToAllShops = false;
	
	public static function setProductDependsOnStock($id_product, $depends_on_stock = true, $id_shop = null, $id_product_attribute = 0)
	{
		if (StockAvailable::$ApplyToAllShops) {
			$cond = 'id_product = '.pSQL($id_product);
			if ($id_product_attribute) $cond .= ' AND id_product_attribute = '.pSQL($id_product_attribute);
			
			Db::getInstance()->update('stock_available', array(
				'depends_on_stock' => (int)$depends_on_stock
			), $cond);
		}
		else
			return parent::setProductDependsOnStock($id_product, $depends_on_stock, $id_shop, $id_product_attribute);
	}
}
