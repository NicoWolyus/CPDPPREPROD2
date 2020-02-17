<?php
class StockManager extends StockManagerCore
{
    /*
    * module: kerawen
    * date: 2020-01-29 13:55:17
    * version: 2.2.14
    */
    protected function calculateWA(Stock $stock, $quantity, $price_te)
	{
		$wa = parent::calculateWA($stock, $quantity, $price_te);
		if ($wa < 0.001) $wa = 0.001;
		return $wa;
	}
}
