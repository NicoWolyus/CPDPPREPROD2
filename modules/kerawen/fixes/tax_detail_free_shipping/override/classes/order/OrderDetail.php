<?php
class OrderDetail extends OrderDetailCore
{
	public function saveTaxCalculator(Order $order, $replace = false)
	{
		$save = $order->total_discounts_tax_excl;
		
		// Correct discount amount used from taxes computation
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<=')) {
			$shipping_discount = 0;
			foreach ($order->getCartRules() as $cart_rule)
			if ($cart_rule['free_shipping']) {
				$shipping_discount = $order->total_shipping_tax_excl;
				break;
			}
			$order->total_discounts_tax_excl = $order->total_discounts_tax_excl - $shipping_discount;
		}
		
		$res = parent::saveTaxCalculator($order, $replace);
		$order->total_discounts_tax_excl = $save;
		return $res;
	}
}
