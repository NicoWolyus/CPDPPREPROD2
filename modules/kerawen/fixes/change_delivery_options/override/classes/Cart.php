<?php

class Cart extends CartCore
{
	private $flush_package_list = false;

	public function getDeliveryOptionList(Country $default_country = null, $flush = false)
	{
		$this->flush_package_list = $flush;
		$res = parent::getDeliveryOptionList($default_country, $flush);
		$this->flush_package_list = false;
		return $res;
	}

	public function getPackageList($flush = false)
	{
		$flush = $flush || $this->flush_package_list;
		$this->flush_products = $flush;
		$res = parent::getPackageList($flush);
		$this->flush_products = false;
		return $res;
	}
}

