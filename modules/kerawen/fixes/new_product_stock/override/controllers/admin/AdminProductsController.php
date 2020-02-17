<?php

class AdminProductsController extends AdminProductsControllerCore
{
	public function processAdd()
	{
		StockAvailable::$ApplyToAllShops = true;
		$res = parent::processAdd();
		StockAvailable::$ApplyToAllShops = false;
		return $res;
	}
}
