<?php
class Order extends OrderCore
{
	public function getCurrentOrderState()
	{
		$state = parent::getCurrentOrderState();
		if ($state == null) {
			$default_os = (int)Configuration::get('KERAWEN_OS_RECEIVED');
			if ($default_os) {
				$state = new OrderState($default_os);
			}
		}
		return $state;
	}
}
