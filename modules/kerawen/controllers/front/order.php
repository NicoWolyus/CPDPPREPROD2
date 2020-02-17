<?php
/**
* 2014 KerAwen
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@kerawen.com so we can send you a copy immediately.
*
* @author    KerAwen <contact@kerawen.com>
* @copyright 2014 KerAwen
* @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
*/

/* */
class KerawenOrderModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		if (Tools::isSubmit('ajax') && Tools::isSubmit('method'))
			switch (Tools::getValue('method'))
			{
			case 'updateDeliveryTime':
				if (Tools::isSubmit('delivery_time'))
				{
					var_dump(Tools::getValue('delivery_time'));
					$date = date('Y-m-d H:i:s', (int)Tools::getValue('delivery_time'));
					var_dump($date);
					$sql = 'INSERT INTO `'._DB_PREFIX_.'cart_kerawen`
								(id_cart, delivery_date)
								VALUES ('.pSQL($this->context->cart->id).',"'.pSQL($date).'")
								ON DUPLICATE KEY
								UPDATE delivery_date = VALUES(delivery_date)';
					Db::getInstance()->execute($sql);
				}
				break;
			}
	}
}
