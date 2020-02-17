<?php

class Order extends OrderCore
{
	public function setInvoice($use_existing_payment = false) {
		parent::setInvoice($use_existing_payment);

		// Order details have been changed in DB
		Cache::clean('objectmodel_'.'OrderDetail'.'_*');
	}
}
