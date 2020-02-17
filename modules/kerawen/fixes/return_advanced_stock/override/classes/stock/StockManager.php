<?php

class StockManager extends StockManagerCore
{
    protected function calculateWA(Stock $stock, $quantity, $price_te)
	{
		$wa = parent::calculateWA($stock, $quantity, $price_te);
		if ($wa < 0.001) $wa = 0.001;
		return $wa;
	}
}
