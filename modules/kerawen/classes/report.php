<?php
/**
 * 2015 KerAwen
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



function getTotalSales($id_shop)
{
	require_once (_KERAWEN_API_DIR_.'/bean/TurnoverBean.php');

	$date_from = date('Y-m-d 00:00:00', time());
	$date_to = date('Y-m-d 00:00:00', strtotime($date_from.' +1 day'));

	$total = Db::getInstance()->getValue('
		SELECT SUM(CASE cs.oper
			WHEN "'._KERAWEN_CDSO_ORDER_.'" THEN o.total_paid_tax_incl
			WHEN "'._KERAWEN_CDSO_CANCEL_.'" THEN -o.total_paid_tax_incl
			WHEN "'._KERAWEN_CDSO_SLIP_.'" THEN -os.amount
			ELSE 0 END)
		FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
		JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co
			ON co.id_cashdrawer_op = cs.id_cashdrawer_op
			AND co.date >= "'.$date_from.'"
			AND co.date < "'.$date_to.'"
		LEFT JOIN '._DB_PREFIX_.'orders o
			ON o.id_order = cs.id_order
		LEFT JOIN '._DB_PREFIX_.'order_slip os
			ON os.id_order_slip = cs.id_order_slip
		');

	$turnover_bean = new TurnoverBean();
	$turnover_bean->set('date-from', $date_from)->set('date-to', $date_to)->set('amount', $total);
	return $turnover_bean;
}

