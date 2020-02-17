<?php
class Order extends OrderCore
{
	/*
    * module: kerawen
    * date: 2020-01-29 13:54:43
    * version: 2.2.14
    */
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
	/*
    * module: kerawen
    * date: 2020-01-29 13:55:30
    * version: 2.2.14
    */
    public function setInvoice($use_existing_payment = false) {
		parent::setInvoice($use_existing_payment);
		Cache::clean('objectmodel_'.'OrderDetail'.'_*');
	}
}
