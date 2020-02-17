<?php

class Cart extends CartCore
{
	private $flush_products = false;

	public function getProducts($flush = false, $id_product = false, $id_country = null)
	{
		Product::$with_ecotax = true;
		$flush = $flush || $this->flush_products;
		$res = parent::getProducts($flush, $id_product, $id_country);
		Product::$with_ecotax = false;
		return $res;
	}
}
