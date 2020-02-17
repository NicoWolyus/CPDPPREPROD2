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
class KerawenDeliveryModuleFrontController extends ModuleFrontController
{
	public static $max_hour = 14;

	public static function hookDisplayHeader(array $params, Module $module)
	{
		return;
		// TO BE FINALIZED
//		$module->getContext()->controller->addCss($module->getPathUri().'css/delivery.css');
//		$module->getContext()->controller->addJS($module->getPathUri().'js/delivery_theme.js');
//		$module->getContext()->controller->addJS($module->getPathUri().'js/delivery.js');
//
//		// ********************** Previous Version **************************
// 		$module->getContext()->controller->addCss($module->getPathUri().'css/kpos.css');
// 		$module->getContext()->controller->addJS($module->getPathUri().'js/kpos.js');

// 		$shop = new Shop($params['cart']->id_shop);
// 		if ($module->isTakeaway($shop))
// 		{
// 			$js = '';
// 			$js .= '$(".addressesAreEquals").hide().find("input").attr("checked", false);';
// 			$js .= '$("div.addresses.clearfix > div:nth-child(2) > div:nth-child(1)").hide();';
// 			$js .= '$("div.addresses.clearfix > div:nth-child(1) > div:nth-child(1)").hide();';
// 			$js .= '$("div.order_delivery.clearfix.row > div:nth-child(1)").hide();';
// 			$js .= '$("div.delivery_options_address").hide();';
// 			$js .= '$("p.carrier_title").hide();';

// 			return '<script>'.$js.'</script>';
// 		}
	}

	/**
	 * 
	 * @param array $params
	 * @param Module $module
	 * @return type
	 */
	public static function hookBeforeCarrier(array $params, Module $module)
	{
//		return;
		// TO BE FINALIZED
//		$cart = $params['cart'];
//
//		$current_date = null;
//		if ($cart && $cart->id)
//		{
//			$buf = Db::getInstance()->getRow(
//				'SELECT `delivery_date` FROM `'._DB_PREFIX_.'cart_kerawen`
//				WHERE `id_cart` = '.pSql($cart->id));
//			if ($buf) $current_date = $buf['delivery_date'];
//		}
//
//		$link = new Link();
//
//		$module->getSmarty()->assign(array(
//			'strings' => array(
//				'asap' => $module->l('As soon as possible'),
//				'later' => $module->l('Later on'),
//			),
//			'opening' => array(
//				array( null, null ),						// Sunday
//				array( null, array( 14, 19) ),				// Monday
//				array( array( 8, 12), array( 14, 19) ),		// Tuesday
//				array( array( 8, 12), array( 14, 19) ),		// Wednesday
//				array( array( 8, 12), array( 14, 19) ),		// Thrusday
//				array( array( 8, 12), array( 14, 19) ),		// Friday
//				array( array( 8, 12), null ),				// Saturday
//			),
//			'step' => 15,
//			'delay' => 30,
//			'current' => $current_date,
//			'url_controller' => $link->getModuleLink($module->name, 'delivery'),
//		));
//		return $module->display($params['__FILE__'], 'delivery_date.tpl');

		// ********************** Previous Version **************************
		// EVOL store delivery time in DB

		$time = time() + 30 * 60;
		$format = 'H\hi';

		// Defines days, hours and minutes allowed to takeway / ship
		setlocale(LC_TIME, 'fr_FR', 'fr');
		$current_hour = date('H');
		$current_day = date('w'); // 0 sunday -> 6 saturday
		$day = 'today';

		if ($current_hour >= self::$max_hour)
		{
			$current_hour = 0;
			$day = 'tomorrow';
		}
		$current_hour = max(8, $current_hour);

		// Day
		$next_day_date = null;
		if ($current_day == 5 || $current_day == 6)
			$next_day_date = strtotime('next monday midnight', time() + 24 * 60 * 60);
		else
			$next_day_date = strtotime('tomorrow midnight');

		$next_day_txt = Tools::ucfirst(strftime('%A %d %B', $next_day_date));
		// day
		$delivery_day = array();
		switch ($day)
		{
			case 'today': {
					$delivery_day[] = array(
							'time' => strtotime('today midnight'),
							'txt' => 'Aujourd\'hui'
					);
					break;
				}
			case 'tomorrow': {
					$delivery_day[] = array(
							'time' => $next_day_date,
							'txt' => $next_day_txt
					);
					break;
				}
		}

		// Hour (current if appicable, otherwise 8)
		$delivery_hour = array();
		for ($i = $current_hour; $i < self::$max_hour; $i++)
		{
			$delivery_hour[] = array(
							'time' => $i,
							'txt' => str_pad($i, 2, '0', STR_PAD_LEFT),
					);
		}
		// minutes
		$delivery_minute = array(
			0 => array(
				'time' => 0,
				'txt' => '00'
			),
			1 => array(
				'time' => 15,
				'txt' => '15'
			),
			2 => array(
				'time' => 30,
				'txt' => '30'
			),
			3 => array(
				'time' => 45,
				'txt' => '45'
			),
		);
		$delivery_time = array(
			1 => array(
				'time' => $time += 30 * 60,
				'txt' => date($format, $time)
			),
			2 => array(
				'time' => $time += 30 * 60,
				'txt' => date($format, $time)
			),
			3 => array(
				'time' => $time += 30 * 60,
				'txt' => date($format, $time)
			),
			4 => array(
				'time' => $time += 30 * 60,
				'txt' => date($format, $time)
			)
		);

		// Insert the box for choice of delivery time
		// sooner is within half an hour
		$pre_txt = '';
		if ($day === 'tomorrow')
		{
			$time = strtotime('tomorrow '.$current_hour.':00');
			$pre_txt = 'Demain ';
		}
		$delivery_time_sooner = array(
			'time' => $time,
			'txt' => $pre_txt.date($format, $time)
		);

		$module->getSmarty()->assign(
				'strings',
				array(
					'chooseDeliveryTime' => $module->l('Choose a delivery time', pathinfo(__FILE__, PATHINFO_FILENAME)),
					'later' => $module->l('Later', pathinfo(__FILE__, PATHINFO_FILENAME)),
					'asap' => $module->l('As soon as possible', pathinfo(__FILE__, PATHINFO_FILENAME))
				));
		$module->getSmarty()->assign('delivery_day_base', $day);
		$module->getSmarty()->assign('delivery_day', $delivery_day);
		$module->getSmarty()->assign('delivery_hour', $delivery_hour);
		$module->getSmarty()->assign('delivery_minute', $delivery_minute);
		$module->getSmarty()->assign('delivery_time', $delivery_time);
		$module->getSmarty()->assign('delivery_time_sooner', $delivery_time_sooner);
		return $module->display($params['__FILE__'], 'choose_time.tpl');
	}

	/**
	 * 
	 * @param array $params
	 * @param Module $module
	 */
	public static function hookValidateOrder(array $params, Module $module)
	{
		$order = $params['order'];
		$cart = $params['cart'];

		// Update order with cart additional information
		Db::getInstance()->execute(
			'INSERT INTO `'._DB_PREFIX_.'order_kerawen`
				(`id_order`, `id_employee`, `delivery_mode`, `delivery_date`)
				SELECT '.pSql($order->id).', `id_employee`, `delivery_mode`, `delivery_date`
					FROM `'._DB_PREFIX_.'cart_kerawen`
					WHERE `id_cart` = '.pSql($cart->id).'
			ON DUPLICATE KEY UPDATE
				`id_employee` = VALUES(`id_employee`),
				`delivery_mode` = VALUES(`delivery_mode`),
				`delivery_date` = VALUES(`delivery_date`)'
		);

		$res = Db::getInstance()->getValue(
			'SELECT delivery_date
				FROM `'._DB_PREFIX_.'cart_kerawen`
				WHERE id_cart = '.pSQL($cart->id));
		$delivery_date = $res ? $res : date('Y-m-d H:i:s');

		// TO BE REMOVED when cart will include delivery date
		Db::getInstance()->execute(
			'INSERT INTO `'._DB_PREFIX_.'order_kerawen`
				(`id_order`, `delivery_date`)
				VALUES ('.pSql($order->id).', "'.pSql($delivery_date).'")
			ON DUPLICATE KEY UPDATE
				`delivery_date` = VALUES(`delivery_date`)');

		// Change delivery address to shop address if takeway
		$shop = new Shop($cart->id_shop);
		if ($module->isTakeaway($shop))
		{
			$order->id_address_delivery = Configuration::get('KERAWEN_DEFAULT_ADDRESS');
			$order->save();
		}
	}

	public function initContent()
	{
		if (Tools::isSubmit('ajax') && Tools::isSubmit('method'))
		{
			$cart = $this->context->cart;
			$id_cart = $cart->id;

			switch (Tools::getValue('method'))
			{
				case 'updateDeliveryMode':
					if ($id_cart && Tools::isSubmit('delivery_mode'))
					{
						$mode = Tools::getValue('delivery_mode');
						Db::getInstance()->execute(
						'INSERT INTO `'._DB_PREFIX_.'cart_kerawen` (id_cart, mode)
							VALUES ('.pSQL($id_cart).','.pSQL($mode).')
							ON DUPLICATE KEY UPDATE mode = VALUES(mode)');

						// Check carrier selection and correct if necessary
						$id_carrier = 0;
						if ($mode != _KERAWEN_DM_DELIVERY_)
							$id_carrier = 15;
						else if ($cart->id_carrier == 15)
						{
							// Make the best carrier selected
							$carriers = $cart->simulateCarriersOutput();
							foreach ($carriers as $carrier)
							{
								$id = (int)Cart::desintifier($carrier['id_carrier']);
								if ($id !== 15)
								{
									$id_carrier = $id;
									break;
								}
							}
						}

						$key = $id_carrier.',';
						$cart->setDeliveryOption(array($cart->id_address_delivery => $key));
						$cart->id_carrier = $id_carrier;
						Hook::exec('actionCarrierProcess', array('cart' => $cart));
						if (!$cart->update())
							return false;

						// Carrier has changed, rules may be out of date
						CartRule::autoRemoveFromCart($this->context);
						CartRule::autoAddToCart($this->context);

						echo Tools::jsonEncode(array(
							'summary' => $cart->getSummaryDetails(),
						));
					}
					break;

				case 'updateDeliveryDate':
					if ($id_cart && Tools::isSubmit('delivery_date'))
					{
						$date = date(Tools::getValue('delivery_date'));
						Db::getInstance()->execute(
							'INSERT INTO `'._DB_PREFIX_.'cart_kerawen` (id_cart, delivery_date)
							VALUES ('.pSQL($id_cart).', FROM_UNIXTIME('.pSQL($date).'))
							ON DUPLICATE KEY UPDATE delivery_date = VALUES(delivery_date)');
					}
					break;
			}
		}
	}
}
