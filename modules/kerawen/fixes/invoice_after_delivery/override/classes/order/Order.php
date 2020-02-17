<?php

class Order extends OrderCore
{
    public function hasDelivery()
    {
        return (int)Db::getInstance()->getValue('
			SELECT `id_order_invoice`
			FROM `'._DB_PREFIX_.'order_invoice`
			WHERE `id_order` =  '.(int)$this->id.'
			AND `delivery_number` > 0'
        );
    }
}
