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

require_once(_KERAWEN_API_DIR_.'/constants.php');
require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');


function getLogFilter($column, $id)
{
	if ($id == _KERAWEN_CD_ALL_) return '';
	if ($id == _KERAWEN_CD_NONE_) return ' AND ('.$column.' IS NULL OR '.$column.' = 0)';
	//till selector : exclude Web
	if ($id == '-2') return ' AND ('.$column.' != 0)';

	$withNull = '';
	if ($column == 'co.id_employee') {		
		if (!(strpos(',' . $id . ',' , ',0,') === false)) {
			//$withNull = ' OR ' . $column . ' IS NULL ';
			return '';
		}
	}
	return ' AND ('.$column.' IN (' . pSQL($id) . ')' . $withNull . ')';
}

function getOpFilter($prefix, $ops)
{
	foreach ($ops as &$op) $op = pSQL($op);
	return $prefix.' IN ('.implode(', ', $ops).')';
}

function getSalesLog($from, $to, $till, $employee)
{
	$slip_amounts = '
		-(s.total_products_tax_excl + s.total_shipping_tax_excl) AS tax_excl,
		-(s.total_products_tax_incl + s.total_shipping_tax_incl) AS tax_incl';
	
	// Backward compatibility
	if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
			$slip_amounts = '
			-(SUM(sd.amount_tax_excl) + s.shipping_cost_amount/(1 + o.carrier_tax_rate/100)) AS tax_excl,
			-(SUM(sd.amount_tax_incl) + s.shipping_cost_amount) AS tax_incl';
	
	$query = '
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.`date` AS `date`,
			cd.name AS till,
			CONCAT(e.firstname, " ", e.lastname) AS employee,
			co.`oper` AS `oper`,
			cs.id_order AS `order`,
			NULL AS slip,
			o.reference AS reference,
			CONCAT(c.firstname, " ", c.lastname) AS customer,
			sh.name AS shop,
			IF(cs.oper = "ORDER", 1, -1)*o.total_paid_tax_excl AS tax_excl,
			IF(cs.oper = "ORDER", 1, -1)*o.total_paid_tax_incl AS tax_incl
		FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
		JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd ON cd.id_cash_drawer = co.id_cashdrawer
		LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
		JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
		LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
		LEFT JOIN '._DB_PREFIX_.'shop sh ON sh.id_shop = o.id_shop
		WHERE
			cs.oper IN ("ORDER", "CANCEL")
			AND co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
			'.getLogFilter('co.id_cash_drawer', $till).'
			'.getLogFilter('co.id_employee', $employee).'
		GROUP BY cs.id_cashdrawer_sale
		UNION
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.`date` AS `date`,
			cd.name AS till,
			CONCAT(e.firstname, " ", e.lastname) AS employee,
			co.`oper` AS `oper`,
			cs.id_order AS `order`,
			cs.id_order_slip AS slip,
			o.reference AS reference,
			CONCAT(c.firstname, " ", c.lastname) AS customer,
			sh.name AS shop,
			'.$slip_amounts.'
		FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
		JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd ON cd.id_cash_drawer = co.id_cashdrawer
		LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
		JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
		JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
		LEFT JOIN '._DB_PREFIX_.'order_slip_detail sd ON sd.id_order_slip = s.id_order_slip
		LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = s.id_customer
		LEFT JOIN '._DB_PREFIX_.'shop sh ON sh.id_shop = o.id_shop
		WHERE
			cs.oper = "SLIP"
			AND co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
			'.getLogFilter('co.`id_cash_drawer`', $till).'
			'.getLogFilter('co.`id_employee`', $employee).'
		GROUP BY cs.id_cashdrawer_sale
		ORDER BY date';

	return Db::getInstance()->executeS($query);
}

function getTaxesLog($from, $to, $till, $employee)
{
	$query = '
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.`date` AS `date`,
			cd.name AS till,
			CONCAT(e.firstname, " ", e.lastname) AS employee,
			co.`oper` AS `oper`,
			cs.id_order AS `order`,
			NULL AS slip,
			o.reference AS reference,
			CONCAT(c.firstname, " ", c.lastname) AS customer,
			sh.name AS shop,
			odt.id_tax AS id,
			IF(cs.oper = "ORDER", 1, -1)*SUM(odt.unit_amount*od.product_quantity) AS tax,
			IF(cs.oper = "ORDER", 1, -1)*SUM(od.total_price_tax_excl)*(1 - IFNULL(ok.product_global_discount,0)) AS base
		FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
		JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd ON cd.id_cash_drawer = co.id_cashdrawer
		LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
		JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
		JOIN '._DB_PREFIX_.'order_detail od ON od.id_order = o.id_order
		LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON odt.id_order_detail = od.id_order_detail
		LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
		LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
		LEFT JOIN '._DB_PREFIX_.'shop sh ON sh.id_shop = o.id_shop
		WHERE
			cs.oper IN ("ORDER", "CANCEL")
			AND co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
			'.getLogFilter('co.id_cash_drawer', $till).'
			'.getLogFilter('co.id_employee', $employee).'
		GROUP BY cs.id_cashdrawer_sale, odt.id_tax
		UNION
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.`date` AS `date`,
			cd.name AS till,
			CONCAT(e.firstname, " ", e.lastname) AS employee,
			co.`oper` AS `oper`,
			cs.id_order AS `order`,
			cs.id_order_slip AS slip,
			o.reference AS reference,
			CONCAT(c.firstname, " ", c.lastname) AS customer,
			sh.name AS shop,
			odt.id_tax AS id,
			-SUM(sd.amount_tax_incl - sd.amount_tax_excl) AS tax,
			-SUM(sd.amount_tax_excl) AS base
		FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
		JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op
		LEFT JOIN '._DB_PREFIX_.'cash_drawer_kerawen cd ON cd.id_cash_drawer = co.id_cashdrawer
		LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
		JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
		JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
		JOIN '._DB_PREFIX_.'order_slip_detail sd ON sd.id_order_slip = s.id_order_slip
		JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sd.id_order_detail
		LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON odt.id_order_detail = od.id_order_detail
		LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = s.id_customer
		LEFT JOIN '._DB_PREFIX_.'shop sh ON sh.id_shop = o.id_shop
		WHERE
			cs.oper = "SLIP"
			AND co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
			'.getLogFilter('co.`id_cash_drawer`', $till).'
			'.getLogFilter('co.`id_employee`', $employee).'
		GROUP BY cs.id_cashdrawer_sale, odt.id_tax
		ORDER BY date';

	return Db::getInstance()->executeS($query);
}

function getProductsLog($from, $to, $till, $employee)
{
	$context = Context::getContext();
	$id_lang = $context->language->id;

	$db = Db::getInstance();
	$orders = $db->executeS('
		SELECT
			p.id_product AS id_prod,
			pa.id_product_attribute AS id_attr,
			p.`reference` AS `reference`,
			p.`ean13` AS `ean`,
			pl.`name` AS `product`,
			GROUP_CONCAT(al.`name` ORDER BY a.`id_attribute_group` SEPARATOR ",") AS `version`,
			su.`name` AS `supplier`,
			o.`id_order` AS `id_order`,
			o.`reference` AS `order`,
			NULL AS `id_slip`,
			NULL AS `slip`,
			o.`date_add` AS `date`,
			cd.`name` AS `till`,
			CONCAT(e.`firstname`, " ", e.`lastname`) AS `employee`,
			c.id_customer AS id_cust,
			CONCAT(c.`firstname`, " ", c.`lastname`) AS `customer`,
			c.id_default_group AS id_group,
			gl.name AS `group`,
			p.wholesale_price AS wholesale,
			od.`unit_price_tax_excl`*(1 - IFNULL(ok.product_global_discount, 0)) AS `unit`,
			od.`product_quantity` AS `quantity`,
			od.`total_price_tax_excl`*(1 - IFNULL(ok.product_global_discount, 0)) AS `total`,
			odk.note AS note
		FROM `'._DB_PREFIX_.'orders` o
		LEFT JOIN `'._DB_PREFIX_.'order_kerawen` ok
			ON ok.id_order = o.id_order
		LEFT JOIN `'._DB_PREFIX_.'cashdrawer_sale_kerawen` cs
			ON cs.`id_order` = o.`id_order`
		LEFT JOIN `'._DB_PREFIX_.'cashdrawer_op_kerawen` co
			ON co.`id_cashdrawer_op` = cs.`id_cashdrawer_op`
		LEFT JOIN `'._DB_PREFIX_.'cash_drawer_kerawen` cd
			ON cd.`id_cash_drawer` = co.`id_cashdrawer`
		LEFT JOIN `'._DB_PREFIX_.'employee` e
			ON e.`id_employee` = co.`id_employee`
		LEFT JOIN `'._DB_PREFIX_.'customer` c
			ON c.`id_customer` = o.`id_customer`
		LEFT JOIN `'._DB_PREFIX_.'group_lang` gl
			ON (gl.`id_group` = c.`id_default_group`
				AND gl.id_lang = '.pSQL($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od
			ON od.`id_order` = o.`id_order`
		LEFT JOIN `'._DB_PREFIX_.'order_detail_kerawen` odk
			ON odk.`id_order_detail` = od.`id_order_detail`
		LEFT JOIN `'._DB_PREFIX_.'product` p
			ON p.`id_product` = od.`product_id`
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
			ON (pl.`id_product` = p.`id_product`
				AND pl.`id_shop` = od.`id_shop`
				AND pl.id_lang = '.pSQL($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
			ON pa.id_product_attribute = od.product_attribute_id
		LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
			ON pac.id_product_attribute = pa.id_product_attribute
		LEFT JOIN `'._DB_PREFIX_.'attribute` a
			ON a.id_attribute = pac.id_attribute
		LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
			ON (al.id_attribute = a.id_attribute
				AND al.id_lang = '.pSQL($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'supplier` su
			ON su.id_supplier = p.id_supplier
		WHERE
			o.`date_add` BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
			AND o.`valid`= 1'
			.getLogFilter('co.`id_cashdrawer`', $till)
			.getLogFilter('co.`id_employee`', $employee).'
		GROUP BY od.`id_order_detail`');

	// TODO Check diff between amount_tax_excl and total_price_tax_excl (new)
	$slips = $db->executeS('
		SELECT
			p.id_product AS id_prod,
			pa.id_product_attribute AS id_attr,
			p.`reference` AS `reference`,
			p.`ean13` AS `ean`,
			pl.`name` AS `product`,
			GROUP_CONCAT(al.`name` ORDER BY a.`id_attribute_group` SEPARATOR ",") AS `version`,
			su.`name` AS `supplier`,
			o.`id_order` AS `id_order`,
			o.`reference` AS `order`,
			s.`id_order_slip` AS `id_slip`,
			s.`id_order_slip` AS `slip`,
			s.`date_add` AS `date`,
			cd.`name` AS `till`,
			CONCAT(e.`firstname`, " ", e.`lastname`) AS `employee`,
			c.id_customer AS id_cust,
			CONCAT(c.`firstname`, " ", c.`lastname`) AS `customer`,
			c.id_default_group AS id_group,
			gl.name AS `group`,
			p.wholesale_price AS wholesale,
			-sd.`amount_tax_excl`/sd.`product_quantity` AS `unit`,
			-sd.`product_quantity` AS `quantity`,
			-sd.`amount_tax_excl` AS `total`
		FROM '._DB_PREFIX_.'order_slip s
		LEFT JOIN '._DB_PREFIX_.'orders o
			ON o.id_order = s.id_order
		LEFT JOIN `'._DB_PREFIX_.'cashdrawer_sale_kerawen` cs
			ON cs.`id_order_slip` = s.`id_order_slip`
		LEFT JOIN `'._DB_PREFIX_.'cashdrawer_op_kerawen` co
			ON co.`id_cashdrawer_op` = cs.`id_cashdrawer_op`
		LEFT JOIN `'._DB_PREFIX_.'cash_drawer_kerawen` cd
			ON cd.`id_cash_drawer` = co.`id_cashdrawer`
		LEFT JOIN `'._DB_PREFIX_.'employee` e
			ON e.`id_employee` = co.`id_employee`
		LEFT JOIN `'._DB_PREFIX_.'customer` c
			ON c.`id_customer` = s.`id_customer`
		LEFT JOIN `'._DB_PREFIX_.'group_lang` gl
			ON (gl.`id_group` = c.`id_default_group`
				AND gl.id_lang = '.pSQL($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'order_slip_detail` sd
			ON sd.`id_order_slip` = s.`id_order_slip`
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od
			ON od.`id_order_detail` = sd.`id_order_detail`
		LEFT JOIN `'._DB_PREFIX_.'product` p
			ON p.`id_product` = od.`product_id`
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
			ON (pl.`id_product` = p.`id_product`
				AND pl.`id_shop` = od.`id_shop`
				AND pl.id_lang = '.pSQL($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
			ON pa.id_product_attribute = od.product_attribute_id
		LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
			ON pac.id_product_attribute = pa.id_product_attribute
		LEFT JOIN `'._DB_PREFIX_.'attribute` a
			ON a.id_attribute = pac.id_attribute
		LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
			ON (al.id_attribute = a.id_attribute
				AND al.id_lang = '.pSQL($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'supplier` su
			ON su.id_supplier = p.id_supplier
		WHERE
			s.`date_add` BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
			.getLogFilter('co.`id_cashdrawer`', $till)
			.getLogFilter('co.`id_employee`', $employee).'
		GROUP BY sd.id_order_slip, sd.id_order_detail');

	return array_merge($orders, $slips);
}

function getPaymentsLog($from, $to, $till, $employee, $deferred = false)
{
	$date_column = $deferred ? 'cf.date_deferred' : 'co.date';

	return Db::getInstance()->executeS('
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.`date` AS `date`,
			cd.`name` AS `till`,
			CONCAT(e.`firstname`, " ", e.`lastname`) AS `employee`,
			co.`oper` AS `oper`,
			cf.`id_payment_mode` AS `id_mode`,
			op.payment_method AS mode,
			cf.`amount` AS `amount`,
			cf.`date_deferred` AS `deferred`,
			cf.comments AS comments
		FROM `'._DB_PREFIX_.'cashdrawer_op_kerawen` co
		JOIN `'._DB_PREFIX_.'cashdrawer_flow_kerawen` cf
			ON cf.`id_cashdrawer_op` = co.`id_cashdrawer_op`
		LEFT JOIN `'._DB_PREFIX_.'cash_drawer_kerawen` cd
			ON cd.`id_cash_drawer` = co.`id_cashdrawer`
		LEFT JOIN `'._DB_PREFIX_.'employee` e
			ON e.`id_employee` = co.`id_employee`
		LEFT JOIN '._DB_PREFIX_.'order_payment op
			ON op.id_order_payment = cf.id_order_payment
		WHERE '.$date_column.' BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
			.getLogFilter('co.`id_cashdrawer`', $till)
			.getLogFilter('co.`id_employee`', $employee).'
		ORDER BY '.$date_column);
}

function getClosingsLog($from, $to, $till, $employee)
{
	return Db::getInstance()->executeS('
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.`oper` AS `oper`,
			co.`date` AS `date`,
			cd.`name` AS `till`,
			CONCAT(e.`firstname`, " ", e.`lastname`) AS `employee`,
			co.`error` AS `error`
		FROM `'._DB_PREFIX_.'cashdrawer_op_kerawen` co
		LEFT JOIN `'._DB_PREFIX_.'cash_drawer_kerawen` cd
			ON cd.`id_cash_drawer` = co.`id_cashdrawer`
		LEFT JOIN `'._DB_PREFIX_.'employee` e
			ON e.`id_employee` = co.`id_employee`
		WHERE 
			co.oper IN (
				"'._KERAWEN_CDOP_OPEN_.'",
				"'._KERAWEN_CDOP_CLOSE_.'",
				"'._KERAWEN_CDOP_FLOW_.'"
			)
			AND co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
			.getLogFilter('co.`id_cashdrawer`', $till)
			.getLogFilter('co.`id_employee`', $employee));
}

// --------------------------
// FUTURE

class LogQuery
{

	public static function bypage() {
		//(int) Configuration::get('KERAWEN_REPORT_ITEMS_BY_PAGE');
		return 50;
	}	
	
	
	public static function saleInfo() {
		return '
			CONCAT("_", cs.id_cashdrawer_op) AS id_op,
			cs.id_cashdrawer_sale AS id_sale,
			cs.oper AS oper';
	}
	

	
	
	public static function getDiscountParam($type) {
		
		switch($type) {
			case 'cart':
				$p = '0, odk.specific_price_cart';
			break;
			case 'catalog':
				$p = '1, (1 - odk.specific_price_cart)';
			break;
			default:
				$p = '1, 1';
			break;	
		}
		
		return $p;

	}
	
	public static function sales($db, $op_filter, $vars = false)
	{

		$discount_filter = (isset($vars->discount_filter)) ? $vars->discount_filter : 'all';

		return array_merge(
			self::orders($db, $op_filter, $discount_filter),
			self::slips($db, $op_filter, $discount_filter)
		);

	}
	
	public static function orders($db, $op_filter, $discount_filter = 'all')
	{
		$id_lang = Context::getContext()->language->id;
		
		$product_cost = 'od.original_wholesale_price';
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.14', '<=')) {
			$product_cost = 'od.purchase_supplier_price';
		}
		
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				IF(cs.oper = "ORDER", 1, -1)*o.total_paid_tax_excl AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*o.total_paid_tax_incl AS tax_incl,
				IF(cs.oper = "ORDER",1,-1)*(o.total_discounts_tax_excl + psp2.reduc_tax_excl) AS disc_te,
				IF(cs.oper = "ORDER",1,-1)*(o.total_discounts_tax_incl + psp2.reduc_tax_incl) AS disc_ti,
				o.reference AS ref,
				/*o.invoice_number AS id_invoice,*/
				IF(o.invoice_number = 0, "", CONCAT("FA ", o.invoice_number)) AS id_invoice,
				o.id_shop AS id_shop,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				c.company,
				gl.name AS `group`,
				SUM(od.product_quantity) AS qty,
				IF(cs.oper = "ORDER",1,-1)*(o.total_paid_tax_excl - SUM(IF(ISNULL(mvt.id_order),'.$product_cost.',mvt.price_te)*od.product_quantity)) AS profit
					
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order = o.id_order
				
			LEFT JOIN (
    			SELECT
					sm.id_order, st.id_product, st.id_product_attribute,
					SUM(sm.physical_quantity*st.price_te)/SUM(sm.physical_quantity) AS price_te
    			FROM '._DB_PREFIX_.'stock_mvt sm
				JOIN '._DB_PREFIX_.'stock st ON st.id_stock = sm.id_stock
				GROUP BY sm.id_order, st.id_product, st.id_product_attribute
			) mvt ON mvt.id_order = o.id_order AND mvt.id_product = od.product_id AND mvt.id_product_attribute = od.product_attribute_id
				
			LEFT JOIN (
				SELECT 
					ok.id_order,
					SUM(IF(ISNULL(odk.specific_price_cart), ' . self::getDiscountParam($discount_filter) . ') * (IF ((od.reduction_percent > 0), od.total_price_tax_incl * ( 1 / ( 1 - od.reduction_percent/100 ) - 1 ), od.reduction_amount_tax_incl * od.product_quantity) ) ) AS reduc_tax_incl,
					SUM(IF(ISNULL(odk.specific_price_cart), ' . self::getDiscountParam($discount_filter) . ') * (IF ((od.reduction_percent > 0), od.total_price_tax_excl * ( 1 / ( 1 - od.reduction_percent/100 ) - 1 ), od.reduction_amount_tax_excl * od.product_quantity) ) ) AS reduc_tax_excl
				FROM ' . _DB_PREFIX_ . 'order_kerawen ok
				LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON ok.id_order = od.id_order
				LEFT JOIN ' . _DB_PREFIX_ . 'order_detail_kerawen odk ON od.id_order_detail = odk.id_order_detail
				GROUP BY ok.id_order
			) psp2 ON o.id_order = psp2.id_order

			WHERE cs.oper IN ("ORDER", "CANCEL")
			GROUP BY cs.id_cashdrawer_sale, o.id_order');
	}

	public static function slips($db, $op_filter, $discount_filter = 'all')
	{
		$id_lang = Context::getContext()->language->id;
		
		$select_amounts = '
			-(s.total_products_tax_excl + s.total_shipping_tax_excl) AS tax_excl,
			-(s.total_products_tax_incl + s.total_shipping_tax_incl) AS tax_incl';

		$select_amounts_marge = ' (s.total_products_tax_excl + s.total_shipping_tax_excl) ';
		$select_profit_mvt = ' (mvt.price_te - sd.unit_price_tax_excl) * sd.product_quantity';


		// Backward compatibility
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<=')) {
			$select_amounts = '
				-(SUM(sd.amount_tax_excl) + s.shipping_cost_amount/(1 + o.carrier_tax_rate/100)) AS tax_excl,
				-(SUM(sd.amount_tax_incl) + s.shipping_cost_amount) AS tax_incl';

			$select_amounts_marge = ' (SUM(sd.amount_tax_excl) + s.shipping_cost_amount/(1 + o.carrier_tax_rate/100)) ';
			//??? to check
			$select_profit_mvt = ' (mvt.price_te - SUM(sd.amount_tax_excl/sd.product_quantity)) * SUM(sd.product_quantity) ';
		}


		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				cs.id_order_slip AS id_slip,
				(-1) * IF(ISNULL(odk.specific_price_cart), ' . self::getDiscountParam($discount_filter) . ') * (IF ((od.reduction_percent > 0), od.total_price_tax_excl * ( 1 / ( 1 - od.reduction_percent/100 ) - 1 ), od.reduction_amount_tax_excl * sd.product_quantity) ) AS disc_te,
				(-1) * IF(ISNULL(odk.specific_price_cart), ' . self::getDiscountParam($discount_filter) . ') * (IF ((od.reduction_percent > 0), od.total_price_tax_incl * ( 1 / ( 1 - od.reduction_percent/100 ) - 1 ), od.reduction_amount_tax_incl * sd.product_quantity) ) AS disc_ti,
				'.$select_amounts.',
				o.reference AS ref,
				/*0 AS id_invoice,*/
				IF(o.invoice_number = 0, "", CONCAT("AV ", cs.id_order_slip)) AS id_invoice,
				o.id_shop AS id_shop,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				c.company,
				IF(ISNULL(mvt.id_stock_mvt), ((sd.product_quantity) * od.purchase_supplier_price) - ' . $select_amounts_marge . ', ' . $select_profit_mvt . ') AS profit,
				gl.name AS `group`
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
			LEFT JOIN '._DB_PREFIX_.'order_slip_detail sd ON sd.id_order_slip = s.id_order_slip
			LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON sd.id_order_detail = od.id_order_detail
			LEFT JOIN ' . _DB_PREFIX_ . 'order_detail_kerawen odk ON sd.id_order_detail = odk.id_order_detail	
			LEFT JOIN ' . _DB_PREFIX_ . 'stock sto ON od.product_id = sto.id_product AND od.product_attribute_id = sto.id_product_attribute
			LEFT JOIN ' . _DB_PREFIX_ . 'stock_mvt mvt ON od.id_order = mvt.id_order AND sto.id_stock = mvt.id_stock
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			WHERE cs.oper = "SLIP"
			GROUP BY cs.id_cashdrawer_sale');
	}

	public static function taxes($db, $op_filter)
	{
		return array_merge(
			self::orderProductTaxes($db, $op_filter),
			self::orderShippingTaxes($db, $op_filter),
			self::orderWrappingTaxes($db, $op_filter),
			self::slipProductTaxes($db, $op_filter),
			self::slipShippingTaxes($db, $op_filter)
			//temporary disable flow -> TODO LATER
			//self::orderDeferredTaxes($db, $op_filter)
		);
	}

	/*
	//temporary disable flow -> TODO LATER
	public static function  orderDeferredTaxes($db, $op_filter) {

		return $db->executeS('
			SELECT 	
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				"DEFERRED" AS object,
				0 AS id_tax,
				cfk.amount AS gross_te,
				cfk.amount AS tax_excl,
				0 AS tax
			FROM  ps_cashdrawer_sale_kerawen cs
			JOIN ps_cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			LEFT JOIN ps_cashdrawer_flow_kerawen cfk ON cfk.id_cashdrawer_op = cs.id_cashdrawer_op
			WHERE cfk.id_payment_mode IN (' . _KERAWEN_PM_GIFT_CARD_ . ')');
	}
	*/
	
	public static function orderProductTaxes($db, $op_filter)
	{
		$taxrule = 'od.id_tax_rules_group';
		$joinprod = '';
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
		{
			$taxrule = 'ps.id_tax_rules_group';
			$joinprod = 'LEFT JOIN '._DB_PREFIX_.'product_shop ps ON ps.id_product = od.product_id AND ps.id_shop = o.id_shop';
		}
		
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				IF(o.invoice_number = 0, "", CONCAT("FA ", o.invoice_number)) AS inv_num,
				o.id_shop AS id_shop,
				"PROD" AS object,
				IFNULL('.$taxrule.', -1) AS id_taxrule,
				IF(cs.oper = "ORDER", 1, -1)*SUM(ROUND(od.original_product_price,2)*od.product_quantity) AS gross_te,
				IF(cs.oper = "ORDER", 1, -1)*SUM(ROUND(od.total_price_tax_excl*(1 - IFNULL(ok.product_global_discount, 0)),2)) AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*SUM(ROUND(IFNULL(odt.unit_amount, 0),2)*od.product_quantity) AS tax,
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				c.company
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON odt.id_order_detail = od.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			'.$joinprod.'
			WHERE
				cs.oper IN ("ORDER", "CANCEL")
			GROUP BY cs.id_cashdrawer_sale, '.$taxrule);
	}
	
	public static function orderShippingTaxes($db, $op_filter)
	{
		// TODO GET THE CORRECT TAX ID AND NOT TAX RULES ID
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				IF(o.invoice_number = 0, "", CONCAT("FA ", o.invoice_number)) AS inv_num,
				"SHIP" AS object,
				IFNULL(ct.id_tax_rules_group, -1) AS id_taxrule,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl*(1 - IFNULL(ok.free_shipping, 0)) AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl*(1 - IFNULL(ok.free_shipping, 0)) AS gross_te,
				IF(cs.oper = "ORDER", 1, -1)*(o.total_shipping_tax_incl - o.total_shipping_tax_excl)*(1 - IFNULL(ok.free_shipping, 0)) AS tax,
				o.carrier_tax_rate AS rate,
				car.name AS carrier,
				/*CONCAT(c.firstname, " ", c.lastname) AS cust,*/
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				c.company
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'carrier_tax_rules_group_shop ct ON ct.id_carrier = o.id_carrier AND ct.id_shop = o.id_shop
			LEFT JOIN '._DB_PREFIX_.'carrier car ON o.id_carrier = car.id_carrier
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			WHERE
				cs.oper IN ("ORDER", "CANCEL")
				AND o.total_shipping_tax_excl > 0
			GROUP BY cs.id_cashdrawer_sale');
	}
	
	public static function orderWrappingTaxes($db, $op_filter)
	{
		// TODO GET THE CORRECT TAX ID AND NOT TAX RULES ID
		$id_taxrule = (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP');

		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				IF(o.invoice_number = 0, "", CONCAT("FA ", o.invoice_number)) AS inv_num,
				"WRAP" AS object,
				'.pSQL($id_taxrule).' as id_taxrule,
				IF(cs.oper = "ORDER", 1, -1)*o.total_wrapping_tax_excl AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*o.total_wrapping_tax_excl AS gross_te,
				IF(cs.oper = "ORDER", 1, -1)*(o.total_wrapping_tax_incl - o.total_wrapping_tax_excl)*(1 - IFNULL(ok.free_shipping, 0)) AS tax,
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				c.company
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'carrier_tax_rules_group_shop ct ON ct.id_carrier = o.id_carrier AND ct.id_shop = o.id_shop
			WHERE cs.oper IN ("ORDER", "CANCEL") AND o.total_wrapping_tax_excl > 0
			GROUP BY cs.id_cashdrawer_sale');
	}
	
	public static function slipProductTaxes($db, $op_filter)
	{
		$taxrule = 'od.id_tax_rules_group';
		$joinprod = '';
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
		{
			$taxrule = 'ps.id_tax_rules_group';
			$joinprod = 'LEFT JOIN '._DB_PREFIX_.'product_shop ps ON ps.id_product = od.product_id AND ps.id_shop = o.id_shop';
		}
		
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				cs.id_order_slip AS id_slip,
				o.reference AS ref,
				/*o.invoice_number AS inv_num,*/
				IF(o.invoice_number = 0, "", CONCAT("AV ", cs.id_order_slip)) AS inv_num,
				o.id_shop AS id_shop,
				"PROD" AS object,
				IFNULL('.$taxrule.', -1) AS id_taxrule,
				-SUM(ROUND(sd.amount_tax_excl,2)) AS tax_excl,
				-ROUND(od.original_product_price,2)*sd.product_quantity AS gross_te,
				-SUM(ROUND(sd.amount_tax_incl - sd.amount_tax_excl,2)) AS tax,
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				c.company
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
			JOIN '._DB_PREFIX_.'order_slip_detail sd ON sd.id_order_slip = s.id_order_slip
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sd.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON odt.id_order_detail = od.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			'.$joinprod.'
			WHERE cs.oper = "SLIP"
			GROUP BY cs.id_cashdrawer_sale, '.$taxrule);
	}
	
	public static function slipShippingTaxes($db, $op_filter)
	{
		$select_amounts = '
			-s.total_shipping_tax_excl AS tax_excl,
			-s.total_shipping_tax_excl AS gross_te,
			-(s.total_shipping_tax_incl - s.total_shipping_tax_excl) AS tax';
		$condition = 's.total_shipping_tax_excl > 0';
		
		// Backward compatibility
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
		{
			$select_amounts = '
				-s.shipping_cost_amount/(1 + o.carrier_tax_rate/100) AS tax_excl,
				-s.shipping_cost_amount/(1 + o.carrier_tax_rate/100) AS gross_te,
				-s.shipping_cost_amount*o.carrier_tax_rate/(100 + o.carrier_tax_rate) AS tax';
			$condition = 's.shipping_cost_amount > 0';
		}
		
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				cs.id_order_slip AS id_slip,
				o.reference AS ref,
				o.id_shop AS id_shop,
				/*o.invoice_number AS inv_num,*/
				IF(o.invoice_number = 0, "", CONCAT("AV ", cs.id_order_slip)) AS inv_num,
				"SHIP" AS object,
				IFNULL(ct.id_tax_rules_group, -1) AS id_taxrule,
				'.$select_amounts.',
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				c.company
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
			LEFT JOIN '._DB_PREFIX_.'carrier_tax_rules_group_shop ct ON ct.id_carrier = o.id_carrier AND ct.id_shop = o.id_shop
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			WHERE cs.oper = "SLIP" AND '.$condition.'
			GROUP BY cs.id_cashdrawer_sale');
	}
	
	public static function productInfo($id_lang) {
		return '
			p.id_product AS id_prod,
			pa.id_product_attribute AS id_attr,
			p.id_category_default AS id_cat,
			pl.name AS prod,
			col.name AS order_country,
			car.name AS carrier,
			IF(ISNULL(mvt.id_order), od.purchase_supplier_price, mvt.price_te) AS purchase_price,
			(SELECT GROUP_CONCAT(al.name ORDER BY a.id_attribute_group SEPARATOR ",")
				FROM '._DB_PREFIX_.'product_attribute_combination pac
				LEFT JOIN '._DB_PREFIX_.'attribute a ON a.id_attribute = pac.id_attribute
				LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON al.id_attribute = a.id_attribute AND al.id_lang = '.pSQL($id_lang).'
				WHERE pac.id_product_attribute = pa.id_product_attribute
			) AS version,
			cl.name AS cat,
			p.reference AS ref,
			p.ean13 AS ean,
			p.upc as upc,
			ps.wholesale_price AS wholesale,
			su.name AS supplier,
			su.id_supplier,
			ma.name AS manufacturer,
			ma.id_manufacturer,
			odk.note AS note';
	}
	
	public static function productJoin($id_lang) {
		return '
			LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk ON odk.id_order_detail = od.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'product p ON p.id_product = od.product_id
			LEFT JOIN '._DB_PREFIX_.'product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = od.id_shop
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product = p.id_product AND pl.id_shop = ps.id_shop AND pl.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
			LEFT JOIN '._DB_PREFIX_.'category_lang cl ON cl.id_category = p.id_category_default AND cl.id_shop = ps.id_shop AND cl.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'supplier su ON su.id_supplier = p.id_supplier
			LEFT JOIN '._DB_PREFIX_.'manufacturer ma ON ma.id_manufacturer = p.id_manufacturer
			LEFT JOIN '._DB_PREFIX_.'address addr ON o.id_address_delivery = addr.id_address
			LEFT JOIN '._DB_PREFIX_.'country_lang col ON addr.id_country = col.id_country AND col.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'carrier car ON o.id_carrier = car.id_carrier
			LEFT JOIN (
				SELECT
					sm.id_order, st.id_product, st.id_product_attribute,
					SUM(sm.physical_quantity*st.price_te)/SUM(sm.physical_quantity) AS price_te
				FROM '._DB_PREFIX_.'stock_mvt sm
				JOIN '._DB_PREFIX_.'stock st ON st.id_stock = sm.id_stock
				GROUP BY sm.id_order, st.id_product, st.id_product_attribute
			) mvt ON mvt.id_order = o.id_order AND mvt.id_product = od.product_id AND mvt.id_product_attribute = od.product_attribute_id
		'; 
	}
	
	public static function completeProducts($prods, $id_shop = null) {
		$id_lang = Context::getContext()->language->id;
		foreach($prods as &$prod)
		{
			if ($id_shop) $prod['id_shop'] = $id_shop;
			$prod['root'] = self::getParentCategory($prod['id_cat'], $prod['id_shop'], $id_lang);
		}
		return $prods;
	}
	
	public static function getParentCategory($id_cat, $id_shop, $id_lang) {
		static $parent = array();
		static $tree = null;
		
		$index = $id_cat.'_'.$id_shop.'_'.$id_lang;
		if (!isset($parent[$index]))
		{
			if (!$tree)
			{
				$buf = Db::getInstance()->executeS('
					SELECT
						c.id_category AS id_cat,
						c.level_depth AS depth,
						c.id_parent AS id_parent,
						cl.id_shop AS id_shop,
						cl.id_lang AS id_lang,
						cl.name AS name
					FROM '._DB_PREFIX_.'category c
					JOIN '._DB_PREFIX_.'category_lang cl ON cl.id_category = c.id_category');
				$tree = array();
				foreach($buf as $cat)
					$tree[$cat['id_cat'].'_'.$cat['id_shop'].'_'.$cat['id_lang']] = $cat;
			}
			if (isset($tree[$index]))
			{
				$cat = $tree[$index];
				while ($cat['depth'] > 2) {
					$cat = $tree[$cat['id_parent'].'_'.$id_shop.'_'.$id_lang];
				}
				$parent[$index] = $cat['name'];
			}
			else
				$parent[$index] = '-';
		}
		return $parent[$index];
	} 
	
	public static function prods($db, $op_filter)
	{
		return self::completeProducts(array_merge(
			self::orderProducts($db, $op_filter),
			self::orderShippings($db, $op_filter),
			self::orderWrappings($db, $op_filter),
			self::slipProducts($db, $op_filter),
			self::slipShippings($db, $op_filter)
		));
	}
	
	public static function orderProducts($db, $op_filter)
	{
		$id_lang = Context::getContext()->language->id;

		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping AS shipping,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl AS shipping_te,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				gl.name AS `group`,
				'.self::productInfo($id_lang).',
				IF(cs.oper = "ORDER", 1, -1)*od.unit_price_tax_excl*(1 - IFNULL(ok.product_global_discount, 0)) AS unit,
				IF(cs.oper = "ORDER", od.product_quantity, -od.product_quantity) AS qty,
				IF(cs.oper = "ORDER", 1, -1)*od.total_price_tax_excl*(1 - IFNULL(ok.product_global_discount, 0)) AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*od.total_price_tax_incl*(1 - IFNULL(ok.product_global_discount, 0)) AS tax_incl,
				IF(cs.oper = "ORDER", 1, -1) * ( (od.original_product_price * od.product_quantity) - od.total_price_tax_excl*(1 - IFNULL(ok.product_global_discount, 0)) ) AS total_disc_te,
				IF(cs.oper = "ORDER", 1, -1) * ( (od.original_product_price * od.product_quantity) - od.total_price_tax_excl*(1 - IFNULL(ok.product_global_discount, 0)) ) * od.total_price_tax_incl / od.total_price_tax_excl AS total_disc_ti,
				(
					SELECT GROUP_CONCAT(op.payment_method) 
					FROM '._DB_PREFIX_.'order_payment op
					WHERE op.order_reference = o.reference AND op.amount > 0
					GROUP BY op.order_reference
				) AS payment_method,
				ok.is_paid,
				ok.delivery_mode
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			'.self::productJoin($id_lang).'
			WHERE cs.oper IN ("ORDER", "CANCEL")
			GROUP BY cs.id_cashdrawer_sale, od.id_order_detail');
	}
	
	public static function orderShippings($db, $op_filter)
	{
		$context = Context::getContext();
		$id_lang = $context->language->id;
		$shipping = $context->module->l('Shipping', pathinfo(__FILE__, PATHINFO_FILENAME));
	
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping AS shipping,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl AS shipping_te,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				gl.name AS `group`,
				NULL AS id_prod,
				NULL AS id_attr,
				NULL AS id_cat,
				"'.$shipping.'" AS prod,
				col.name AS order_country,
				car.name AS carrier,
				0.0 AS purchase_price,
				car.name AS version,
				NULL AS cat,
				NULL AS ref,
				NULL AS ean,
				NULL as upc,
				0.0 AS wholesale,
				NULL AS supplier,
				NULL AS id_supplier,
				NULL AS manufacturer,
				NULL AS id_manufacturer,
				NULL AS note,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl AS unit,
				IF(cs.oper = "ORDER", 1, -1) AS qty,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl*(1 - IFNULL(ok.free_shipping, 0)) AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_incl*(1 - IFNULL(ok.free_shipping, 0)) AS tax_incl,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl*IFNULL(ok.free_shipping, 0) AS total_disc_te,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_incl*IFNULL(ok.free_shipping, 0) AS total_disc_ti,
				(
					SELECT GROUP_CONCAT(op.payment_method)
					FROM '._DB_PREFIX_.'order_payment op
					WHERE op.order_reference = o.reference AND op.amount > 0
					GROUP BY op.order_reference
				) AS payment_method,
				ok.is_paid
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'address addr ON o.id_address_delivery = addr.id_address
			LEFT JOIN '._DB_PREFIX_.'country_lang col ON addr.id_country = col.id_country AND col.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'carrier car ON o.id_carrier = car.id_carrier
			WHERE cs.oper IN ("ORDER", "CANCEL") AND o.total_shipping_tax_excl > 0
			GROUP BY cs.id_cashdrawer_sale');
	}
	
	public static function orderWrappings($db, $op_filter)
	{
		$context = Context::getContext();
		$id_lang = $context->language->id;
		$wrapping = $context->module->l('Wrapping', pathinfo(__FILE__, PATHINFO_FILENAME));
	
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping AS shipping,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl AS shipping_te,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				gl.name AS `group`,
				NULL AS id_prod,
				NULL AS id_attr,
				NULL AS id_cat,
				"'.$wrapping.'" AS prod,
				col.name AS order_country,
				car.name AS carrier,
				0.0 AS purchase_price,
				NULL AS version,
				NULL AS cat,
				NULL AS ref,
				NULL AS ean,
				NULL as upc,
				0.0 AS wholesale,
				NULL AS supplier,
				NULL AS id_supplier,
				NULL AS manufacturer,
				NULL AS id_manufacturer,
				NULL AS note,
				IF(cs.oper = "ORDER", 1, -1)*o.total_wrapping_tax_excl AS unit,
				IF(cs.oper = "ORDER", 1, -1) AS qty,
				IF(cs.oper = "ORDER", 1, -1)*o.total_wrapping_tax_excl AS tax_excl,
				IF(cs.oper = "ORDER", 1, -1)*o.total_wrapping_tax_incl AS tax_incl,
				IF(cs.oper = "ORDER", 1, -1)*0.0 AS total_disc_te,
				IF(cs.oper = "ORDER", 1, -1)*0.0 AS total_disc_ti,
				(
					SELECT GROUP_CONCAT(op.payment_method)
					FROM '._DB_PREFIX_.'order_payment op
					WHERE op.order_reference = o.reference AND op.amount > 0
					GROUP BY op.order_reference
				) AS payment_method,
				ok.is_paid
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'address addr ON o.id_address_delivery = addr.id_address
			LEFT JOIN '._DB_PREFIX_.'country_lang col ON addr.id_country = col.id_country AND col.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'carrier car ON o.id_carrier = car.id_carrier
			WHERE cs.oper IN ("ORDER", "CANCEL") AND o.total_wrapping_tax_incl > 0
			GROUP BY cs.id_cashdrawer_sale');
	}
	
	public static function slipProducts($db, $op_filter)
	{
		$id_lang = Context::getContext()->language->id;
		
		$select_amounts = '
			-sd.unit_price_tax_excl AS unit,
			-sd.product_quantity AS qty,
			-sd.total_price_tax_excl AS tax_excl,
			-sd.total_price_tax_incl AS tax_incl,';
		
		// Backward compatibility
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
			$select_amounts = '
				-(sd.amount_tax_excl/sd.product_quantity) AS unit,
				-sd.product_quantity AS qty,
				-sd.amount_tax_excl AS tax_excl,
				-sd.amount_tax_incl AS tax_incl,';
		
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				cs.id_order_slip AS id_slip,
				o.reference AS ref,
				o.id_shop AS id_shop,
				o.total_shipping AS shipping,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				gl.name AS `group`,
				'.self::productInfo($id_lang).',
				'.$select_amounts.'
				0.0 AS total_disc_te,
				0.0 AS total_disc_ti,
				(
					SELECT GROUP_CONCAT(op.payment_method) 
					FROM '._DB_PREFIX_.'order_payment op
					WHERE op.order_reference = o.reference AND op.amount > 0
					GROUP BY op.order_reference
				) AS payment_method,
				ok.is_paid
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
			JOIN '._DB_PREFIX_.'order_slip_detail sd ON sd.id_order_slip = s.id_order_slip
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sd.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			'.self::productJoin($id_lang).'
			WHERE cs.oper = "SLIP"
			GROUP BY sd.id_order_slip, sd.id_order_detail');
	}
	
	public static function slipShippings($db, $op_filter)
	{
		$context = Context::getContext();
		$id_lang = $context->language->id;
		$shipping = $context->module->l('Shipping', pathinfo(__FILE__, PATHINFO_FILENAME));

		$select_amounts = '
				-s.total_shipping_tax_excl AS unit,
				-1 AS qty,
				-s.total_shipping_tax_excl AS tax_excl,
				-s.total_shipping_tax_incl AS tax_incl';
		$condition = 's.total_shipping_tax_excl > 0';
		
		// Backward compatibility
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
		{
			$select_amounts = '
				-s.shipping_cost_amount/(1 + o.carrier_tax_rate/100) AS unit,
				-1 AS qty,
				-s.shipping_cost_amount/(1 + o.carrier_tax_rate/100) AS tax_excl,
				-s.shipping_cost_amount AS tax_incl';
			$condition = 's.shipping_cost_amount > 0';
		}
		
		return $db->executeS('
			SELECT
				'.self::saleInfo().',
				cs.id_order AS id_order,
				o.reference AS ref,
				o.id_shop AS id_shop,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping AS shipping,
				IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl AS shipping_te,
				CONCAT(c.firstname, " ", c.lastname) AS cust,
				gl.name AS `group`,
				NULL AS id_prod,
				NULL AS id_attr,
				NULL AS id_cat,
				"'.$shipping.'" AS prod,
				col.name AS order_country,
				car.name AS carrier,
				0.0 AS purchase_price,
				car.name AS version,
				NULL AS cat,
				NULL AS ref,
				NULL AS ean,
				NULL as upc,
				0.0 AS wholesale,
				NULL AS supplier,
				NULL AS id_supplier,
				NULL AS manufacturer,
				NULL AS id_manufacturer,
				NULL AS note,
				'.$select_amounts.',
				-o.total_shipping_tax_excl*IFNULL(ok.free_shipping, 0) AS total_disc_te,
				-o.total_shipping_tax_incl*IFNULL(ok.free_shipping, 0) AS total_disc_ti,
				(
					SELECT GROUP_CONCAT(op.payment_method)
					FROM '._DB_PREFIX_.'order_payment op
					WHERE op.order_reference = o.reference AND op.amount > 0
					GROUP BY op.order_reference
				) AS payment_method,
				ok.is_paid
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = o.id_customer
			LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'address addr ON o.id_address_delivery = addr.id_address
			LEFT JOIN '._DB_PREFIX_.'country_lang col ON addr.id_country = col.id_country AND col.id_lang = '.pSQL($id_lang).'
			LEFT JOIN '._DB_PREFIX_.'carrier car ON o.id_carrier = car.id_carrier
			WHERE cs.oper = "SLIP" AND '.$condition.'
			GROUP BY cs.id_cashdrawer_sale');
	}
	
	
	public static function prodStats($db, $op_filter)
	{
		// completeProducts applied on main shop
		return self::completeProducts(array_merge(
			self::orderProductsStats($db, $op_filter),
			self::slipProductsStats($db, $op_filter)
		), Configuration::get('PS_SHOP_DEFAULT'));
	}


	public static function orderProductsStats($db, $op_filter)
	{
		$id_lang = Context::getContext()->language->id;
		
		return $db->executeS('
			SELECT
				'.self::productInfo($id_lang).',
				SUM(IF(cs.oper = "ORDER", od.product_quantity, -od.product_quantity)) AS qty,
				SUM(IF(cs.oper = "ORDER", 1, -1)*od.total_price_tax_excl*(1 - IFNULL(ok.product_global_discount, 0))) AS tax_excl,
				SUM(IF(cs.oper = "ORDER", 1, -1)*od.total_price_tax_incl*(1 - IFNULL(ok.product_global_discount, 0))) AS tax_incl
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
			'.self::productJoin($id_lang).'
			WHERE
				cs.oper IN ("ORDER", "CANCEL")
			GROUP BY p.id_product, pa.id_product_attribute');
	}
	
	public static function slipProductsStats($db, $op_filter)
	{
		$id_lang = Context::getContext()->language->id;
		
		$select_amounts = '
			-SUM(sd.total_price_tax_excl) AS tax_excl,
			-SUM(sd.total_price_tax_incl) AS tax_incl';
		
		// Backward compatibility
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<='))
			$select_amounts = '
				-SUM(sd.amount_tax_excl) AS tax_excl,
				-SUM(sd.amount_tax_incl) AS tax_incl';
		
		return $db->executeS('
			SELECT
				'.self::productInfo($id_lang).',
				-SUM(sd.product_quantity) AS qty,
				'.$select_amounts.'
			FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen cs
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op AND '.$op_filter.'
			JOIN '._DB_PREFIX_.'order_slip s ON s.id_order_slip = cs.id_order_slip
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = s.id_order
			JOIN '._DB_PREFIX_.'order_slip_detail sd ON sd.id_order_slip = s.id_order_slip
			JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sd.id_order_detail
			'.self::productJoin($id_lang).'
			WHERE
				cs.oper = "SLIP"
			GROUP BY p.id_product, pa.id_product_attribute');
	}

	public static function flows($db, $op_filter)
	{
		return $db->executeS('
			SELECT
				CONCAT("_", cf.id_cashdrawer_op) AS id_op,
				cf.id_cashdrawer_flow AS id_flow,
				cf.id_order AS id_order,
				cf.id_order_slip AS id_slip,
				cf.id_order_payment AS id_payment,
				cf.id_payment_mode AS id_mode,
				IF(cf.id_payment_mode IS NULL, op.payment_method, NULL) AS mode,
				cf.amount AS amount,
				cf.count AS count,
				cf.date_deferred AS deferred,
				cf.comments AS note
			FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cf.id_cashdrawer_op AND '.$op_filter.'
			LEFT JOIN '._DB_PREFIX_.'order_payment op ON op.id_order_payment = cf.id_order_payment
			');
	}

	public static function checks($db, $op_filter)
	{
		return $db->executeS('
			SELECT
				CONCAT("_", cc.id_cashdrawer_op) AS id_op,
				cc.id_cashdrawer_close AS id_check,
				cc.id_payment_mode AS id_mode,
				cc.checked AS checked,
				cc.error AS error
			FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cc.id_cashdrawer_op AND '.$op_filter);
	}
	
	
	

	public static function ordermvts($db, $params)
	{

		$q = "
			SELECT				
				'order' AS act, order_detail.id_order, order_detail.id_order_detail, order_detail.product_id, order_detail.product_attribute_id, order_detail.product_name, order_detail.product_quantity AS qty_buy, order_detail.product_quantity_in_stock AS qty_in_stock,
				SUM(stock.physical_quantity) AS phys_total, GROUP_CONCAT(stock.physical_quantity SEPARATOR '+') AS phys_concat, SUM(stock.usable_quantity) AS usable_total, GROUP_CONCAT(stock.usable_quantity SEPARATOR '+') AS usable_concat,
				stock_available.real_total, stock_available.real_concat,
				stock_mvt.id_stock_mvt, stock_mvt.physical_quantity AS qty_ordered, stock_mvt.sign, orders.date_add AS date_added,
				stock_mvt_reason_lang.name AS reason
			
			FROM " . _DB_PREFIX_ . "order_detail order_detail
			LEFT JOIN " . _DB_PREFIX_ . "stock stock ON order_detail.product_id = stock.id_product AND order_detail.product_attribute_id = stock.id_product_attribute
			LEFT JOIN " . _DB_PREFIX_ . "stock_mvt stock_mvt ON order_detail.id_order = stock_mvt.id_order AND stock.id_stock = stock_mvt.id_stock
			LEFT JOIN " . _DB_PREFIX_ . "stock_mvt_reason stock_mvt_reason ON stock_mvt.id_stock_mvt_reason = stock_mvt_reason.id_stock_mvt_reason
			LEFT JOIN " . _DB_PREFIX_ . "stock_mvt_reason_lang stock_mvt_reason_lang ON stock_mvt_reason.id_stock_mvt_reason = stock_mvt_reason_lang.id_stock_mvt_reason
			LEFT JOIN " . _DB_PREFIX_ . "orders orders ON order_detail.id_order = orders.id_order
			
			LEFT JOIN (
			
				SELECT stock_available.id_product, stock_available.id_product_attribute, SUM(stock_available.quantity) AS real_total, GROUP_CONCAT(stock_available.quantity SEPARATOR '+') AS real_concat
				FROM " . _DB_PREFIX_ . "stock_available stock_available
				GROUP BY stock_available.id_product, stock_available.id_product_attribute
			
			) AS stock_available ON order_detail.product_id = stock_available.id_product AND order_detail.product_attribute_id = stock_available.id_product_attribute
			
			
			GROUP BY id_order_detail
			
			HAVING orders.date_add BETWEEN '" . pSQL($params->from) . "' AND '" . pSQL($params->to) . "'
			
			ORDER BY date_added DESC, id_order_detail DESC
		
		";


		$res['ordermvts'] = $db->executeS($q);
		if ($params->index) {
			$res['ordermvts'] = indexArray($res['ordermvts'], 'id_stock_mvt');
		}

		return $res['ordermvts'];

	}


	public static function mvts($db, $params)
	{

		$q = "

			SELECT
				stock_mvt.id_order AS id_order, order_detail.id_order_detail AS id_order_detail, stock.id_product_attribute AS product_attribute_id, stock.id_product AS product_id, order_detail.product_name AS product_name,
				stock.id_stock, stock.id_warehouse, stock.physical_quantity AS physical_quantity_2, stock.usable_quantity,
				stock_mvt.id_stock_mvt, stock_mvt.physical_quantity AS physical_quantity_1, stock_mvt.sign, stock_mvt.date_add AS date_added,
				stock_mvt_reason_lang.name AS reason
			FROM " . _DB_PREFIX_ . "stock_mvt stock_mvt
			LEFT JOIN " . _DB_PREFIX_ . "stock_mvt_reason stock_mvt_reason ON stock_mvt.id_stock_mvt_reason = stock_mvt_reason.id_stock_mvt_reason
			LEFT JOIN " . _DB_PREFIX_ . "stock_mvt_reason_lang stock_mvt_reason_lang ON stock_mvt_reason.id_stock_mvt_reason = stock_mvt_reason_lang.id_stock_mvt_reason
			LEFT JOIN " . _DB_PREFIX_ . "stock stock ON stock_mvt.id_stock = stock.id_stock
			LEFT JOIN " . _DB_PREFIX_ . "order_detail order_detail ON stock_mvt.id_order = order_detail.id_order AND stock.id_product = order_detail.product_id AND stock.id_product_attribute = order_detail.product_attribute_id
		
			WHERE stock_mvt.date_add BETWEEN '" . pSQL($params->from) . "' AND '" . pSQL($params->to) . "'
			ORDER BY stock_mvt.date_add DESC, id_order_detail DESC
		
		";
		

		$res['mvts'] = $db->executeS($q);
		if ($params->index) {
			$res['mvts'] = indexArray($res['mvts'], 'id_stock_mvt');
		}

		return $res['mvts'];

	}
	
	
	public static function ordersales($db, $params)
	{
		// Set shop id = -1 for all shops TODO in report.js ?
		if (isset($params->shop)) {
			if (strpos($params->shop, ',')) $params->shop = -1;
		}
		
		$id_lang = Context::getContext()->language->id;
		
		// Keep same product names whatever shop filter is ?
		$id_shop = Configuration::get('PS_SHOP_DEFAULT');
		
		// TODO remove: prices may be different from one shop to another
		$id_country = Context::getContext()->country->id;
		
		$order_filter = 'TRUE';
		$wh_filter = 'TRUE';
		$sa_filter = null;
		
		if ($params->shop != -1) {
			$order_filter = 'ord.id_shop = '.pSQL($params->shop);
			
			$group = Shop::getGroupFromShop($params->shop, false);
			if ($group['share_stock']) {
				$sa_filter = 'sa.id_shop = 0 AND sa.id_shop_group = '.pSQL($group['id']);
			}
			else {
				$sa_filter = 'sa.id_shop_group = 0 AND sa.id_shop = '.pSQL($params->shop);
			}
			
			$ids = [];
			$whs = Warehouse::getWarehouses(false, $params->shop);
			foreach($whs as $wh) $ids[] = $wh['id_warehouse'];
			if (count($ids)) {
				$wh_filter = 'id_warehouse IN ('.implode(',', $ids).')';
			}
			else {
				$wh_filter = 'id_warehouse = 0';
			}
		}
		else {
			// Filter stock available from shop group configuration
			$gids = [];
			$sids = [];
			$groups = ShopGroup::getShopGroups();
			foreach ($groups as $g) {
				if ($g->share_stock) {
					$gids[] = $g->id;
				}
				else {
					$shops = ShopGroup::getShopsFromGroup($g->id);
					foreach ($shops as $s) $sids[] = $s['id_shop'];
				}
			}
			$g_filter = 'TRUE';
			if (count($gids)) $g_filter = 'sa.id_shop = 0 AND sa.id_shop_group IN ('.implode(',', $gids).')';
			$s_filter = 'TRUE';
			if (count($sids)) $s_filter = 'sa.id_shop_group = 0 AND sa.id_shop IN ('.implode(',', $sids).')';
			$sa_filter = '('.$g_filter.' OR '.$s_filter.')';
		}
		
		$q = '
			SELECT DISTINCT
				p.id_product AS id,
				pl.name AS prod_name,
				d.name AS declinaison,
				IFNULL(d.ean13, p.ean13) AS ean,
				IFNULL(d.reference, p.reference) AS ref,
				p.id_category_default AS id_cat,
				cl.name AS cat,
				p.id_supplier AS id_supplier,
				s.name AS supplier,
                ps.product_supplier_reference AS supplier_ref,
				p.id_manufacturer AS id_manufacturer,
				m.name AS manufacturer,
			
				IFNULL(o.qty, 0) AS qty_sale,
				IFNULL(p.price, 0) + IFNULL(d.price, 0) AS saling_price,
				--(IFNULL(p.price, 0) + IFNULL(d.price, 0))*(1 + IFNULL(t.rate, 0)/100) AS unit_tax_incl,
			
				CASE 
					WHEN ss.stock_shop IS NULL THEN "manual"
					WHEN ss.stock_warehouse = 0 THEN "manual"
					WHEN ss.stock_shop = 0 THEN "warehouse"
					ELSE "manual/warehouse"
				END AS stock_type,
				
				IF(ss.stock_shop > 0, ss.qty, 0) + IF(ss.stock_warehouse > 0, sw.qty, 0) AS stock,
				IF(d.wholesale_price > 0, d.wholesale_price, p.wholesale_price) AS buying_price
			
			FROM '._DB_PREFIX_.'product p
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = '.pSQL($id_lang).' AND pl.id_shop = '.pSQL($id_shop).'
			
			LEFT JOIN (
				SELECT 
					pa.id_product, 
                    pa.id_product_attribute,
					pa.ean13,
					pa.reference,
					pa.price,
					pa.wholesale_price,
					(SELECT
						GROUP_CONCAT(al.name ORDER BY a.id_attribute_group)
						FROM '._DB_PREFIX_.'product_attribute_combination pac
						LEFT JOIN '._DB_PREFIX_.'attribute a ON pac.id_attribute = a.id_attribute
						LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON al.id_attribute = a.id_attribute AND al.id_lang = '.pSQL($id_lang).' 
						WHERE pac.id_product_attribute = pa.id_product_attribute
					) AS name
				FROM '._DB_PREFIX_.'product_attribute pa
				GROUP BY pa.id_product, pa.id_product_attribute
			) AS d ON d.id_product = p.id_product
			
			LEFT JOIN '._DB_PREFIX_.'category c ON c.id_category = p.id_category_default 
			LEFT JOIN '._DB_PREFIX_.'category_lang cl ON cl.id_category = c.id_category AND cl.id_lang = '.pSQL($id_lang).' AND cl.id_shop = '.pSQL($id_shop).' 
			LEFT JOIN '._DB_PREFIX_.'supplier s ON s.id_supplier = p.id_supplier
            LEFT JOIN '._DB_PREFIX_.'product_supplier ps ON s.id_supplier = ps.id_supplier AND p.id_product = ps.id_product AND IFNULL(d.id_product_attribute, 0) = ps.id_product_attribute
			LEFT JOIN '._DB_PREFIX_.'manufacturer m ON m.id_manufacturer = p.id_manufacturer
			LEFT JOIN '._DB_PREFIX_.'tax_rule tr ON tr.id_tax_rules_group = p.id_tax_rules_group AND tr.id_country = '.pSQL($id_country).'
			LEFT JOIN '._DB_PREFIX_.'tax t ON t.id_tax = tr.id_tax
			
			LEFT JOIN (
				SELECT
					od.product_id, 
					od.product_attribute_id,
					SUM(od.product_quantity) AS qty
				FROM '._DB_PREFIX_.'order_detail od
				LEFT JOIN '._DB_PREFIX_.'orders ord ON ord.id_order = od.id_order
				WHERE ord.date_add BETWEEN "'.pSQL($params->from).'" AND "'.pSQL($params->to).'" AND '.$order_filter.'
				GROUP BY od.product_id, od.product_attribute_id
			) AS o ON o.product_id = p.id_product AND o.product_attribute_id = d.id_product_attribute
			
			LEFT JOIN (
				SELECT
					sa.id_product,
					sa.id_product_attribute,
					SUM(1 - sm.depends_on_stock) AS stock_shop,
					SUM(sm.depends_on_stock) AS stock_warehouse,
					SUM(IF(sm.depends_on_stock = 0, sa.quantity, 0)) AS qty
				FROM '._DB_PREFIX_.'stock_available sa
				LEFT JOIN (
				    SELECT sa2.id_product, sa2.id_shop, sa2.id_shop_group, sa2.depends_on_stock
				    FROM '._DB_PREFIX_.'stock_available sa2
				    WHERE sa2.id_product_attribute = 0
				) sm ON sm.id_product = sa.id_product AND sm.id_shop = sa.id_shop AND sm.id_shop_group = sa.id_shop_group
				WHERE '.$sa_filter.'
				GROUP BY sa.id_product, sa.id_product_attribute
			) ss ON ss.id_product = p.id_product
				AND ss.id_product_attribute = IFNULL(d.id_product_attribute, 0)
			
			LEFT JOIN (
				SELECT
					id_product,
					id_product_attribute,
					SUM(physical_quantity) AS qty,
					SUM(price_te*physical_quantity) AS value
				FROM '._DB_PREFIX_.'stock
				WHERE '.$wh_filter.'
				GROUP BY id_product, id_product_attribute
			) sw ON sw.id_product = p.id_product
				AND sw.id_product_attribute = IFNULL(d.id_product_attribute, 0)
			
			ORDER BY qty_sale DESC, p.id_product, d.id_product_attribute';

		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id_ordersales');
		}
		
		// Compute out of stock time
		$from = new DateTime($params->from);
		$to = new DateTime($params->to);
		$diff = $to->diff($from);
		$days = $diff->days + 1;
		foreach($res as &$s) {
			if ($s['qty_sale'] > 0) {
				$s['projection'] = floor($days*$s['stock']/$s['qty_sale']);
			}
			else $s['projection'] = '';
		}
		return $res;
	}
	

	public static function labelSettings($db, $params)
	{

	  $config_key = 'KERAWEN_LABEL';
	  
	  if (isset($params->data)) {
	    //optional parameters require -> context exist
	  	Configuration::updateValue($config_key, $params->data, false, 0, 0);
	  }

	  return json_decode(Configuration::get($config_key, 0, 0, 0));

	}


	public static function setBarCode($db, $params) {
	
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		
		$p = self::labelSettings($db, $params);
	
		$q = "
			SELECT 
			  product.id_product, 
			  IFNULL(product_attribute.id_product_attribute, 0) AS id_product_attribute,
			  '' AS ean13
			FROM " . _DB_PREFIX_ . "product product
			LEFT JOIN " . _DB_PREFIX_ . "product_attribute product_attribute ON product.id_product = product_attribute.id_product 
			WHERE 
			  (product_attribute.id_product_attribute IS NULL  AND (product.ean13 = '0' OR product.ean13 = '' OR product.ean13 IS NULL))
			OR 
			 (product_attribute.id_product_attribute IS NOT NULL AND (product_attribute.ean13 = '0' OR product_attribute.ean13 = '' OR product_attribute.ean13 IS NULL))
		";
	
		$res = $db->executeS($q);
		
		$mask = '20xxxxxxxxxx';

		if (isset( $p->barcode )) {
			$mask = str_replace(' ', '', $p->barcode->mask) . $mask;
		}
		
		foreach ($res as &$r) {
	
			
			$barcode = 'xxxxxxxxxxxx';
			for ($k = 0; $k < 12; $k++) {
				if ($mask[$k] == 'x')
					$barcode[$k] = mt_rand(0, 9);
				else
					$barcode[$k] = $mask[$k];
			}
		
			//check digit
			$barcode .= ean_checkdigit($barcode);
			
			
				
			//$barcode = createBareCode($mask);
				

			//get uniq ean13
			$ean = array('ean13' => pSQL($barcode) );
			$r['ean13'] = $barcode;
			//check if already exist ????
		
			//no attribute	
			if ($r['id_product_attribute'] == 0) {
				$db->update( 'product', $ean, 'id_product = ' . pSQL($r['id_product']) );
		    //attribute
			} else {
				$db->update( 'product_attribute', $ean, 'id_product_attribute = ' . pSQL($r['id_product_attribute']) );
			}
		
		}

		return $res;
	
	}



	public static function productSupplier($db, $params)
	{

		//$id_lang = Context::getContext()->language->id;
  
		$q = "
			SELECT 
			  supplier.id_supplier, 
			  supplier.name, 
			  COUNT(supplier.id_supplier) AS nb 
			FROM " . _DB_PREFIX_ . "product product
			INNER JOIN " . _DB_PREFIX_ . "supplier supplier ON product.id_supplier = supplier.id_supplier
			GROUP BY supplier.id_supplier
			ORDER BY supplier.name
		";

		$res = $db->executeS($q);
				
		if ($params->index) {
			$res = indexArray($res, 'id_supplier');
		}
		
		return $res;

	}


	public static function productManufacturer($db, $params)
	{

		$q = "
			SELECT
			  manufacturer.id_manufacturer,
			  manufacturer.name,
			  COUNT(manufacturer.id_manufacturer) AS nb
			FROM " . _DB_PREFIX_ . "product product
			INNER JOIN " . _DB_PREFIX_ . "manufacturer manufacturer ON product.id_manufacturer = manufacturer.id_manufacturer
			GROUP BY manufacturer.id_manufacturer
			ORDER BY manufacturer.name
		";
	
		$res = $db->executeS($q);
	
		if ($params->index) {
			$res = indexArray($res, 'id_manufacturer');
		}
	
		return $res;
	
	}
	
	
	public static function productCategories($db, $params)
	{

		$id_lang = Context::getContext()->language->id;
  
		$q = "
		
			SELECT 
			  category_lang.id_category, 
			  category_lang.name, 
			  COUNT(category_lang.id_category) AS nb
			FROM " . _DB_PREFIX_ . "product product
			LEFT JOIN " . _DB_PREFIX_ . "category_lang category_lang
			INNER JOIN " . _DB_PREFIX_ . "category category ON category_lang.id_category = category.id_category
			ON product.id_category_default = category_lang.id_category AND product.id_shop_default = category_lang.id_shop AND category_lang.id_lang = " . $id_lang . "
			GROUP BY category_lang.id_category
			ORDER BY category_lang.name
		
		";

		$res = $db->executeS($q);
				
		if ($params->index) {
			$res = indexArray($res, 'id_category');
		}
		
		return $res;

	}


	public static function productsListV2($db, $params) {
			
		//define from params
		$bypage = (int) Configuration::get('KERAWEN_LABEL_ITEMS_BY_PAGE');
		$advanced_stock = (boolean) Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');		

		$id_lang = Context::getContext()->language->id;
		$id_country = Context::getContext()->country->id;
		$id_currency = Context::getContext()->currency->id;

		$id_shop = 'product.id_shop_default';
		if ($params->shop != -1) {
		 	$id_shop = intval($params->shop);
		}


		$search = '';
		if ($params->text != '') {
			$search = " AND (product_attribute.reference LIKE '%" . trim(pSQL($params->text)) . "%' OR product.reference LIKE '%" . trim(pSQL($params->text)) . "%' OR product.ean13 LIKE '%" . trim(pSQL($params->text)) . "%' OR product_attribute.ean13 LIKE '%" . trim(pSQL($params->text)) . "%' OR product_lang.name LIKE '%" . trim(pSQL($params->text)) .  "%') ";
		}
		
		
		$category = '';
		if ($params->id_cat != -1) {
		  $category = ' AND product.id_category_default = ' . intval($params->id_cat) . ' ';
		}
		
		
		$supplier = '';
		if ($params->id_sup != -1) {
		  $supplier = ' AND product.id_supplier = ' . intval($params->id_sup) . ' ';
		}
		

		$manufacturer = '';
		if ($params->id_man != -1) {
		  $manufacturer = ' AND product.id_manufacturer = ' . intval($params->id_man) . ' ';
		}
		
		
		$warehouse_condition = "";
		$q = "SELECT id_warehouse FROM " . _DB_PREFIX_ . "warehouse_shop WHERE id_shop = " . intval($params->shop);
		$warehouse = $db->executeS($q);
		
		if (count($warehouse)) {
			$w = array();
			foreach ($warehouse as $row) {
		  		$w[] = $row['id_warehouse'];
			}
			$warehouse_condition = " AND id_warehouse IN(" . implode(",", $w) . ") ";
		}


		$id_group = null;
		if ($params->id_group != -1) {
			$id_group = $params->id_group;
		}
		
		$joinType = "INNER";
		// INNER: display row only if product exist on the specified shop
		// LEFT: display row even if product doesn't exist on the shop

		$vatmargin_c1 = 0;
		if (Module::isInstalled('vatmargin') && Module::isEnabled('vatmargin')) {
			$vatmargin_c1 = 'IFNULL(product.vat_margin, 0)';
		}

		$sort = '';
		
		
		$adv_attribut = "0 AS mvtstock";
		$adv_join = "";
		$adv_group = "";
		
		$sortMvt = ' ORDER BY mvtstock DESC ';
		
		
		if ($advanced_stock) {
			$adv_attribut = " IFNULL(SUM(stock_mvt.physical_quantity), 0) AS mvtstock ";
			$adv_join = "
			LEFT JOIN " . _DB_PREFIX_ . "stock stock 
			ON product.id_product = stock.id_product AND product_attribute.id_product_attribute = stock.id_product_attribute " . $warehouse_condition . "
			LEFT JOIN " . _DB_PREFIX_ . "stock_mvt stock_mvt
			ON stock.id_stock = stock_mvt.id_stock AND stock_mvt.id_stock_mvt_reason IN (1,8) AND stock_mvt.date_add BETWEEN '" . pSQL($params->from) . "' AND '" . pSQL($params->to) . "' 
			";
			$adv_group = ", stock.id_product, stock.id_product_attribute ";
			$sort = $sortMvt;
			
		} else {
			
			//TODO : change advanced stocks like below (subquery)
			
			if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
				
				$withShop = "";
				if ($params->shop != -1) {
					$withShop = " AND sa.id_shop = " . (int) $params->shop . " ";
				}
				
				$adv_attribut = " 
				(
					SELECT IFNULL(SUM(sm.physical_quantity * sm.sign), 0)
					FROM " . _DB_PREFIX_ . "stock_available sa
					LEFT JOIN " . _DB_PREFIX_ . "stock_mvt sm ON sa.id_stock_available = sm.id_stock
					WHERE sa.id_product = product.id_product AND sa.id_product_attribute = IFNULL(product_attribute.id_product_attribute, 0) 
					AND sm.id_stock_mvt_reason IN(11, 8) 
					AND sm.date_add BETWEEN '" . pSQL($params->from) . "' AND '" . pSQL($params->to) . "'" . $withShop . "
				) AS mvtstock";

				$sort = $sortMvt;
			}
			
		}


		if (!($params->orderby == '' || $params->sortby == '')) {
			$sort = ' ORDER BY ' . pSQL($params->orderby) . ' ' . pSQL($params->sortby);
		}


		$price = "IF(product_attribute_shop.ecotax > 0, product_attribute_shop.ecotax, IFNULL(product_shop.ecotax, 0)) + IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) + (IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) - (product.wholesale_price * " . $vatmargin_c1 . ")  )";
		
		$q = "
			SELECT SQL_CALC_FOUND_ROWS

				CONCAT('" . $params->shop . "', '-', product.id_product, '-', IFNULL(product_attribute.id_product_attribute, 0)) AS uniq,
				product_shop.id_shop AS id_shop,
				product.id_product AS id,
				IFNULL(product_attribute.id_product_attribute, 0) AS id_product_attribute,
				product_lang.name AS product_name,
				GROUP_CONCAT(DISTINCT attribute_lang.name ORDER BY attribute.id_attribute_group) AS declinaison,
				GROUP_CONCAT(DISTINCT CONCAT_WS(':', agl.name, attribute_lang.name) ORDER BY attribute.id_attribute_group) AS declinaison_adv,
				 
				product.id_category_default AS id_category, 
				category.id_parent AS id_parent_category,
				category_lang.name AS category,

				IF(product_attribute.id_product_attribute IS NULL, product.ean13, product_attribute.ean13) AS ean13,
				IF(product_attribute.id_product_attribute IS NULL, product.upc, product_attribute.upc) AS upc,	
				product.reference AS main_reference,		
				IF(product_attribute.id_product_attribute IS NULL, product.reference, product_attribute.reference) AS reference,				

                IF(product_wm_kerawen.unit IS NULL, '', product_wm_kerawen.unit) AS unit,

				ROUND(IF(product_attribute_shop.ecotax > 0, product_attribute_shop.ecotax, IFNULL(product_shop.ecotax, 0)),2)  AS ecotax,
				ROUND(IF(product_attribute_shop.ecotax > 0, product_attribute_shop.ecotax, IFNULL(product_shop.ecotax, 0)) + IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) + (IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) - (product.wholesale_price * " . $vatmargin_c1 . ")  )*(IFNULL(tax.rate,0)/100), 2) AS price_vat_incl,		
                ROUND(IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) - IF(product_attribute_shop.ecotax > 0, product_attribute_shop.ecotax, IFNULL(product_shop.ecotax, 0)) ,2) AS price_vat_excl,

                ROUND(IF(product_attribute_shop.wholesale_price IS NULL OR product_attribute_shop.wholesale_price = 0, product.wholesale_price, product_attribute_shop.wholesale_price), 2) AS wholesale_price,

				ROUND(IFNULL(tax.rate, 0), 2) AS tax,

				
				product_wm_kerawen.measured, product_wm_kerawen.precision,
				
				product.unit_price_ratio AS unit_ratio,
				product.unity AS unit_label,

				manufacturer.name AS manufacturer,

				GROUP_CONCAT(DISTINCT fvl.value ORDER BY f.position ASC) AS feature,
				GROUP_CONCAT(DISTINCT CONCAT_WS(':', fl.name, fvl.value) ORDER BY f.position ASC) AS feature_adv,

				" . $adv_attribut . "

			FROM " . _DB_PREFIX_ . "product product
			LEFT JOIN " . _DB_PREFIX_ . "product_lang product_lang
			ON product.id_product = product_lang.id_product AND product_lang.id_shop = product.id_shop_default AND product_lang.id_lang = " . $id_lang . "
			LEFT JOIN " . _DB_PREFIX_ . "category_lang category_lang 
			ON product.id_category_default = category_lang.id_category AND product.id_shop_default = category_lang.id_shop AND category_lang.id_lang = " . $id_lang . "
			LEFT JOIN " . _DB_PREFIX_ . "category category 
			ON product.id_category_default = category.id_category
			" . $joinType . " JOIN " . _DB_PREFIX_ . "product_shop product_shop 
			ON product.id_product = product_shop.id_product AND product_shop.id_shop = " . $id_shop . "
			
			LEFT JOIN " . _DB_PREFIX_ . "product_wm_kerawen product_wm_kerawen 
			ON product.id_product = product_wm_kerawen.id_product
		
			LEFT JOIN " . _DB_PREFIX_ . "product_attribute product_attribute 
			ON product.id_product = product_attribute.id_product 
			
			LEFT JOIN " . _DB_PREFIX_ . "product_attribute_shop product_attribute_shop 
			ON product_attribute.id_product_attribute = product_attribute_shop.id_product_attribute AND product_attribute_shop.id_shop = " . $id_shop . "

			LEFT JOIN " . _DB_PREFIX_ . "product_attribute_kerawen product_attribute_kerawen 
			ON product_attribute.id_product_attribute = product_attribute_kerawen.id_product_attribute

			LEFT JOIN " . _DB_PREFIX_ . "product_attribute_combination product_attribute_combination 
			ON product_attribute.id_product_attribute = product_attribute_combination.id_product_attribute

			LEFT JOIN " . _DB_PREFIX_ . "attribute_lang attribute_lang 
			ON product_attribute_combination.id_attribute = attribute_lang.id_attribute AND attribute_lang.id_lang = " . $id_lang . "
			
			LEFT JOIN " . _DB_PREFIX_ . "attribute attribute 
			ON attribute_lang.id_attribute = attribute.id_attribute

			LEFT JOIN " . _DB_PREFIX_ . "attribute_group_lang agl
			ON attribute.id_attribute_group = agl.id_attribute_group AND agl.id_lang = " . $id_lang . "

			LEFT JOIN " . _DB_PREFIX_ . "tax_rule tax_rule 
			ON product.id_tax_rules_group = tax_rule.id_tax_rules_group AND tax_rule.id_country = " . $id_country . "
			
			LEFT JOIN " . _DB_PREFIX_ . "tax tax 
			ON tax_rule.id_tax = tax.id_tax

			LEFT JOIN " . _DB_PREFIX_ . "manufacturer manufacturer
			ON product.id_manufacturer = manufacturer.id_manufacturer

			LEFT JOIN " . _DB_PREFIX_ . "feature_product fp ON product.id_product = fp.id_product
			LEFT JOIN " . _DB_PREFIX_ . "feature f ON fp.id_feature = f.id_feature
			LEFT JOIN " . _DB_PREFIX_ . "feature_lang fl ON f.id_feature = fl.id_feature AND fl.id_lang = " . $id_lang . "
			LEFT JOIN " . _DB_PREFIX_ . "feature_value_lang fvl ON fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = " . $id_lang . "		

			

			" . $adv_join . "

			WHERE product_attribute_kerawen.id_product_attribute IS NULL " . $search . " " . $category . " " . $supplier . " " . $manufacturer .  "
			
			GROUP BY product.id_product, product_attribute.id_product_attribute
					
			" . $adv_group . "
			
			" . $sort . "
			
		";
			
		if ($bypage > 0) {
			
			$q .= "
					LIMIT " . ( ((int)$params->page - 1) * $bypage) . ", " . $bypage;
			
		}
		
		/*
		echo '<pre>';
		print_r($q);
		echo '</pre>';
		*/
		
		$data = $db->executeS($q);
		$total = $db->getValue('SELECT FOUND_ROWS()');
		
		foreach($data as $k=>$item) {

			//price/weight
			//Select first item added
			if ($item['measured']) {	
				$tmp_attr = (int) $item['id_product_attribute'] > 0 ? $item['id_product_attribute'] : -1;
				$precision = (int) $data[$k]['precision'] > 0 ? $data[$k]['precision'] : 2;
				if ($row = $db->getRow("
					SELECT unit_price
					FROM " . _DB_PREFIX_ . "product_wm_code_kerawen
					WHERE id_product = " . (int) $item['id'] . " AND (id_product_attribute = -1 OR id_product_attribute = " . (int) $tmp_attr . ")
					ORDER BY id_product_attribute DESC, id_code ASC
				")) {				
					$data[$k]['price_vat_incl'] = round($row["unit_price"] * ($data[$k]['tax']/100 + 1), $precision);
				}
			}

			$specific = SpecificPrice::getSpecificPrice(
				$item['id'],
				$item['id_shop'],
				$id_currency,
				$id_country,
				$id_group,
				1,
				$item['id_product_attribute'],
				null,
				0,
				1);


			$data[$k]['discount'] = 0;
			$data[$k]['discount_te'] = 0;
			
			if ($specific) {
				if ($specific['price'] == -1) {
							
					if ($specific['reduction_type'] == 'percentage') {
						$data[$k]['discount'] = (float)$data[$k]['price_vat_incl'] * (float)$specific['reduction'];
						$data[$k]['discount_te'] = (float)$data[$k]['price_vat_excl'] * (float)$specific['reduction'];
					}

					if($specific['reduction_type'] == 'amount') {
    					$tax_coef = $data[$k]['tax']/100 + 1;
    					if ($specific['reduction_tax']) {
    					    $data[$k]['discount'] = (float)$specific['reduction'];
    					    $data[$k]['discount_te'] = (float)$specific['reduction'] / $tax_coef;
    					} else {
    					    $data[$k]['discount'] = (float)$specific['reduction'] * $tax_coef;
    					    $data[$k]['discount_te'] = (float)$specific['reduction'];
    					}
					}
				}
			}
			

			$data[$k]['stock'] = StockAvailable::getQuantityAvailableByProduct($item['id'], $item['id_product_attribute']);
			
			
			//ordering columns
			$mvt = $data[$k]['mvtstock'];
			unset($data[$k]['mvtstock']);			
			$data[$k]['mvtstock'] = $mvt;
			
			$data[$k]['vat'] = round(($data[$k]['price_vat_incl'] - $data[$k]['discount']) - ($data[$k]['price_vat_excl'] - $data[$k]['discount_te']), 2);


			
			//Method without tax
			$data[$k]['margin'] = $data[$k]['price_vat_excl'] - $data[$k]['discount_te'] - $data[$k]['wholesale_price'];
			/*
			$data[$k]['margin_rate'] = 100;
			if ($data[$k]['price_vat_excl'] - $data[$k]['discount_te'] != 0) {
			     $data[$k]['margin_rate'] = round($data[$k]['margin'] / ($data[$k]['price_vat_excl'] - $data[$k]['discount_te']) * 100, 2);
		    }
			*/
			
			//Method with tax
			//$data[$k]['margin'] = $data[$k]['price_vat_incl'] - $data[$k]['discount'] - $data[$k]['wholesale_price'];
			$data[$k]['margin_rate'] = 100;
			if ($data[$k]['price_vat_incl'] - $data[$k]['discount'] != 0) {
			    $data[$k]['margin_rate'] = round( ($data[$k]['price_vat_excl'] - $data[$k]['discount_te'] - $data[$k]['wholesale_price']) / ($data[$k]['price_vat_incl'] / 100), 1);
			}

		    //round
			$data[$k]['margin'] = round($data[$k]['margin'], 2);
			$data[$k]['discount'] = round($data[$k]['discount'], 2);
			$data[$k]['discount_te'] = round($data[$k]['discount_te'], 2);
		}		


		$res = array(
		  'data' => $data,
		  'total' => $total,
		  'bypage' => $bypage,
		);
		
		/*
		echo '<pre>';
		print_r($data);
		echo '</pre>';
		//echo 'total : ' . $total;
		*/

		return $res;

	}


	public static function productsList($db, $params)
	{
		$id_lang = Context::getContext()->language->id;
		
		$id_country = Context::getContext()->country->id;

		$id_shop = 'product.id_shop_default';
		$shop_condition_2 = '';
		
		$id_shop_sp = 0;
		if ($params->shop != -1) {
		 	$id_shop = intval($params->shop);
			$id_shop_sp = $id_shop;
			$shop_condition_2 = " AND id_shop = " . intval($params->shop) . " "; 
		}

		$search = '';
		if ($params->text != '') {
			$search = " AND (product.ean13 LIKE '%" . trim($params->text) . "%' OR product_attribute.ean13 LIKE '%" . trim($params->text) . "%' OR product_lang.name LIKE '%" . trim($params->text) .  "%') ";
		}
		
		
		$category = '';
		if ($params->id_cat != -1) {
		  $category = ' AND product.id_category_default = ' . intval($params->id_cat) . ' ';
		}


		$supplier = '';
		if ($params->id_sup != -1) {
		  $supplier = ' AND product.id_supplier = ' . intval($params->id_sup) . ' ';
		}


		$warehouse_condition = "";
		$q = "SELECT id_warehouse FROM " . _DB_PREFIX_ . "warehouse_shop WHERE id_shop = " . intval($params->shop);
		$warehouse = $db->executeS($q);
		
		if (count($warehouse)) {
			$w = array();
			foreach ($warehouse as $row) {
		  		$w[] = $row['id_warehouse'];
			}
			$warehouse_condition = " AND id_warehouse IN(" . implode(",", $w) . ") ";
		}


		$joinType = "INNER";
		// INNER: display row only if product exist on the specified shop
		// LEFT: display row even if product doesn't exist on the shop


		$vatmargin_c1 = 0;
		if (Module::isInstalled('vatmargin') && Module::isEnabled('vatmargin')) {
			$vatmargin_c1 = 'IFNULL(product.vat_margin, 0)';
		}

		$q = "

			SELECT DISTINCT
				CONCAT('" . $params->shop . "', '-', product.id_product, '-', IFNULL(product_attribute.id_product_attribute, 0)) AS uniq,
				product_shop.id_shop AS id_shop,
				product.id_product AS id,
				IFNULL(product_attribute.id_product_attribute, 0) AS id_product_attribute,
				product_lang.name AS product_name, 
				decli.declinaison,
				product.id_category_default AS id_category, 
				category.id_parent AS id_parent_category,
				category_lang.name AS category, 
				ROUND(IF(product_attribute_shop.ecotax > 0, product_attribute_shop.ecotax, IFNULL(product_shop.ecotax, 0)),2)  AS ecotax,
				ROUND(IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) - IF(product_attribute_shop.ecotax > 0, product_attribute_shop.ecotax, IFNULL(product_shop.ecotax, 0)) ,2) AS price_vat_excl,
				ROUND(IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) + (IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0) - (product.wholesale_price * " . $vatmargin_c1 . ")  )*(tax.rate/100), 2) AS price_vat_incl,
				IFNULL(product_attribute.ean13, product.ean13) AS ean13, 
				IFNULL(product_attribute.upc, product.upc) AS upc,
				IF(ISNULL(product_shop.price), 0, 1) AS prd_exist,
				IF(ISNULL(sp.id_specific_price), '', specific_price.reduction_type) AS discount_type,
				IFNULL(mvt.mvt_qty, 0) AS mvtstock,

				CASE 
				  WHEN stock_available.quantity IS NOT NULL AND stock.stock_qty IS NOT NULL THEN 'error stock'
				  WHEN stock_available.quantity IS NOT NULL THEN stock_available.quantity
				  WHEN stock.stock_qty IS NOT NULL THEN stock.stock_qty
				  ELSE 0			
				END AS stock,

			    IF(ISNULL(sp.id_specific_price), 
				  /*no discount*/
				  '0.00', 			  
				  IF (reduction_type = 'amount', 
				    /*amount*/
					specific_price.reduction,
				    /*pourcent*/
					ROUND((IFNULL(product_shop.price, 0) + IFNULL(product_attribute_shop.price, 0))*(1+tax.rate/100)*specific_price.reduction, 2)
				  ) 
			    ) AS discount

			FROM " . _DB_PREFIX_ . "product product
			LEFT JOIN " . _DB_PREFIX_ . "product_lang product_lang
			ON product.id_product = product_lang.id_product AND product_lang.id_shop = product.id_shop_default AND product_lang.id_lang = " . $id_lang . "
			LEFT JOIN " . _DB_PREFIX_ . "category_lang category_lang 
			ON product.id_category_default = category_lang.id_category AND product.id_shop_default = category_lang.id_shop AND category_lang.id_lang = " . $id_lang . "
			LEFT JOIN " . _DB_PREFIX_ . "category category 
			ON product.id_category_default = category.id_category
			" . $joinType . " JOIN " . _DB_PREFIX_ . "product_shop product_shop 
			ON product.id_product = product_shop.id_product AND product_shop.id_shop = " . $id_shop . "
			
			LEFT JOIN " . _DB_PREFIX_ . "product_attribute product_attribute 
			ON product.id_product = product_attribute.id_product 
			LEFT JOIN " . _DB_PREFIX_ . "product_attribute_shop product_attribute_shop 
			ON product_attribute.id_product_attribute = product_attribute_shop.id_product_attribute AND product_attribute_shop.id_shop = " . $id_shop . "

			LEFT JOIN " . _DB_PREFIX_ . "product_attribute_kerawen product_attribute_kerawen 
			ON product_attribute.id_product_attribute = product_attribute_kerawen.id_product_attribute

			LEFT JOIN (
			  SELECT 
				product_attribute.id_product, 
				product_attribute.id_product_attribute, 
				GROUP_CONCAT(attribute_lang.name) AS declinaison 
			  FROM " . _DB_PREFIX_ . "product_attribute product_attribute
			  LEFT JOIN " . _DB_PREFIX_ . "product_attribute_combination product_attribute_combination 
			  ON product_attribute.id_product_attribute = product_attribute_combination.id_product_attribute
			  LEFT JOIN " . _DB_PREFIX_ . "attribute_lang attribute_lang 
			  ON product_attribute_combination.id_attribute = attribute_lang.id_attribute AND id_lang = " . $id_lang . "
			  GROUP BY product_attribute.id_product, product_attribute.id_product_attribute
			) AS decli ON product_attribute.id_product = decli.id_product AND product_attribute.id_product_attribute = decli.id_product_attribute

			LEFT JOIN " . _DB_PREFIX_ . "tax_rule tax_rule ON product.id_tax_rules_group = tax_rule.id_tax_rules_group AND tax_rule.id_country = " . $id_country . "
			LEFT JOIN " . _DB_PREFIX_ . "tax tax ON tax_rule.id_tax = tax.id_tax

			LEFT JOIN " . _DB_PREFIX_ . "specific_price specific_price ON product.id_product = specific_price.id_product AND specific_price.id_shop=" . $id_shop_sp . "
			LEFT JOIN ( 
				SELECT MAX(sp_sub.id_specific_price) AS id_specific_price
				FROM " . _DB_PREFIX_ . "specific_price sp_sub
				WHERE sp_sub.id_product_attribute=0 AND sp_sub.id_shop = " . $id_shop_sp . " 
				AND (
			  		NOW() BETWEEN sp_sub.from  AND sp_sub.to
					OR (sp_sub.from = '0000-00-00 00:00:00' AND sp_sub.to = '0000-00-00 00:00:00')
					OR (NOW() > sp_sub.from AND sp_sub.to = '0000-00-00 00:00:00'))
				GROUP BY sp_sub.id_product, sp_sub.id_product_attribute
			) sp ON specific_price.id_specific_price = sp.id_specific_price

			LEFT JOIN (
			  SELECT 
				  id_product, 
				  id_product_attribute,
				  depends_on_stock,
				  GROUP_CONCAT(quantity) AS concat_quantity, 
				  SUM(quantity) AS quantity  
			  FROM " . _DB_PREFIX_ . "stock_available stock_available
			  WHERE depends_on_stock = 0 " . $shop_condition_2 . "
			  GROUP BY depends_on_stock, id_product, id_product_attribute
			) AS stock_available 
			  ON product.id_product = stock_available.id_product 
			  AND IFNULL(product_attribute.id_product_attribute,0) = IFNULL(stock_available.id_product_attribute, 0)

			LEFT JOIN " . _DB_PREFIX_ . "stock_available stock_available_2
			ON product.id_product = stock_available_2.id_product
			AND IFNULL(product_attribute.id_product_attribute,0) = IFNULL(stock_available_2.id_product_attribute, 0)
			AND stock_available_2.depends_on_stock = 1
			
			LEFT JOIN (
			  SELECT id_product, id_product_attribute, GROUP_CONCAT(physical_quantity) AS concat_stock, SUM(physical_quantity) AS stock_qty
			  FROM " . _DB_PREFIX_ . "stock stock WHERE 1 " . $warehouse_condition . "
			  GROUP BY id_product, id_product_attribute
			) AS stock 
			ON stock_available_2.id_product = stock.id_product
			AND (
			  stock_available_2.id_product_attribute = stock.id_product_attribute
			)

			LEFT JOIN (
			
				SELECT 
				  stock.id_product, 
				  stock.id_product_attribute, 
				  SUM(stock_mvt.physical_quantity) AS mvt_qty
				FROM " . _DB_PREFIX_ . "stock_mvt stock_mvt
				LEFT JOIN " . _DB_PREFIX_ . "stock stock ON stock_mvt.id_stock = stock.id_stock
				WHERE stock_mvt.id_stock_mvt_reason IN (1) 
				AND stock_mvt.date_add BETWEEN '" . pSQL($params->from) . "' AND '" . pSQL($params->to) . "'
				" . $warehouse_condition . "
				GROUP BY stock.id_product, stock.id_product_attribute

			) AS mvt
			ON product.id_product = mvt.id_product
			AND IFNULL(product_attribute.id_product_attribute,0) = IFNULL(mvt.id_product_attribute,0)

			WHERE product_attribute_kerawen.id_product_attribute IS NULL " . $search . " " . $category . " " . $supplier . "

			ORDER BY product.id_product
			
		";

		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id');
		}
		return $res;
	}
	
/*
	public static function getOutstanding($db, $params, $type = 'CREDIT') 
	{
	
		$id_lang = Context::getContext()->language->id;

		$q = "
		SELECT
		  cr.id_cart_rule AS id,
		  crl.name,
		  cr.code,
		  cr.id_customer,
		  IF(cr.id_customer = 0, '- -', CONCAT (c.firstname, ' ', c.lastname)) AS customer,
		  CONCAT (employee.firstname, ' ', employee.lastname) AS employee,
		  cr.date_from,
		  cr.date_to,
		  cr.reduction_amount AS amount
		FROM " . _DB_PREFIX_ . "cart_rule cr
		JOIN " . _DB_PREFIX_ . "cart_rule_kerawen crk ON crk.id_cart_rule = cr.id_cart_rule AND crk.type = '" . $type . "'
		LEFT JOIN " . _DB_PREFIX_ . "cart_rule_lang crl ON crl.id_cart_rule = cr.id_cart_rule AND crl.id_lang = " . $id_lang . "
		LEFT JOIN " . _DB_PREFIX_ . "customer c ON c.id_customer = cr.id_customer
		
		LEFT JOIN " . _DB_PREFIX_ . "cashdrawer_flow_kerawen cfk ON cr.id_cart_rule = cfk.id_credit
		LEFT JOIN " . _DB_PREFIX_ . "cashdrawer_op_kerawen cok ON cfk.id_cashdrawer_op = cok.id_cashdrawer_op
		LEFT JOIN " . _DB_PREFIX_ . "employee employee ON cok.id_employee = employee.id_employee 
		
		WHERE NOW() BETWEEN cr.date_from AND cr.date_to 
		AND cr.quantity = 1 AND cr.active = 1 AND (NOW() BETWEEN cr.date_from AND cr.date_to) 
		
		ORDER BY cr.id_cart_rule DESC
		
		";

		//AND cr.id_customer > 0

		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id');
		}

		return $res;

	}
*/



	public static function getCredit($db, $params)
	{

		$id_lang = Context::getContext()->language->id;

		$q = "
		SELECT
		  cr.id_cart_rule AS id,
		  crl.name,
		  CONCAT('#####', SUBSTRING(cr.code, -3)) AS code,
		  cr.id_customer,
		  IF(cr.id_customer = 0, '- -', CONCAT (c.firstname, ' ', c.lastname)) AS customer,
		  CONCAT (employee.firstname, ' ', employee.lastname) AS employee,
		  cr.date_from,
		  cr.date_to,
		  cr.reduction_amount AS amount
		FROM " . _DB_PREFIX_ . "cart_rule cr
		JOIN " . _DB_PREFIX_ . "cart_rule_kerawen crk ON crk.id_cart_rule = cr.id_cart_rule AND crk.type = '" . _KERAWEN_CR_CREDIT_ . "'
		LEFT JOIN " . _DB_PREFIX_ . "cart_rule_lang crl ON crl.id_cart_rule = cr.id_cart_rule AND crl.id_lang = " . $id_lang . "
		LEFT JOIN " . _DB_PREFIX_ . "customer c ON c.id_customer = cr.id_customer
		
		LEFT JOIN " . _DB_PREFIX_ . "cashdrawer_flow_kerawen cfk ON cr.id_cart_rule = cfk.id_credit
		LEFT JOIN " . _DB_PREFIX_ . "cashdrawer_op_kerawen cok ON cfk.id_cashdrawer_op = cok.id_cashdrawer_op
		LEFT JOIN " . _DB_PREFIX_ . "employee employee ON cok.id_employee = employee.id_employee
		
		WHERE (NOW() BETWEEN cr.date_from AND cr.date_to) 
		AND cr.quantity = 1 AND cr.active = 1 AND cok.id_shop IN (" . pSQL($params->shop) . ")
		ORDER BY cr.id_cart_rule DESC
		
		";
		
		//AND cr.id_customer > 0

		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id');
		}

		return $res;


	}

	public static function getGiftCard($db, $params)
	{

		$id_lang = Context::getContext()->language->id;

		$q = "
		SELECT
		  cr.id_cart_rule AS id,
		  crl.name,
		  CONCAT('#####', SUBSTRING(cr.code, -3)) AS code,
		  CONCAT (employee.firstname, ' ', employee.lastname) AS employee,
		  cr.date_from,
		  cr.date_to,
		  cr.reduction_amount AS amount
		FROM " . _DB_PREFIX_ . "cart_rule cr
		JOIN " . _DB_PREFIX_ . "cart_rule_kerawen crk ON crk.id_cart_rule = cr.id_cart_rule AND crk.type = '" . _KERAWEN_CR_GIFT_CARD_ . "'
		LEFT JOIN " . _DB_PREFIX_ . "cart_rule_lang crl ON crl.id_cart_rule = cr.id_cart_rule AND crl.id_lang = " . $id_lang . "
		
		LEFT JOIN " . _DB_PREFIX_ . "order_kerawen ok ON crk.id_order = ok.id_order
		LEFT JOIN " . _DB_PREFIX_ . "employee employee ON ok.id_employee = employee.id_employee
		LEFT JOIN " . _DB_PREFIX_ . "orders o ON crk.id_order = o.id_order
		
		WHERE (NOW() BETWEEN cr.date_from AND cr.date_to)
		AND cr.quantity = 1 AND cr.active = 1 AND o.id_shop IN (" . pSQL($params->shop) . ")
		
		ORDER BY cr.id_cart_rule DESC
		
		";

		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id');
		}

		return $res;


	}

	
	
	public static function getLoyaly($db, $params)
	{

		
		$res = array();
		
		if (Module::isInstalled('loyalty') && Module::isEnabled('loyalty')) {
		
			$id_lang = Context::getContext()->language->id;
			
			$q = "

				SELECT
				  'pt' AS fid_type,
				  loyalty.id_order,
				  loyalty.id_customer,
				  CONCAT (customer.firstname, ' ', customer.lastname) AS customer, 
				  loyalty.date_add AS date_from,
				  '' AS date_to,
				  loyalty.points,
				  loyalty.points*" . Configuration::get('PS_LOYALTY_POINT_VALUE') . " AS amount
				FROM " . _DB_PREFIX_ . "loyalty loyalty
				LEFT JOIN " . _DB_PREFIX_ . "customer customer ON loyalty.id_customer = customer.id_customer
				LEFT JOIN " . _DB_PREFIX_ . "orders orders ON loyalty.id_order = orders.id_order
				WHERE loyalty.points > 0 
				AND loyalty.id_loyalty_state = 2
				AND CONCAT (customer.firstname, ' ', customer.lastname) != '- -'
				AND orders.id_shop IN (" . pSQL($params->shop) . ")
			
				UNION ALL
		
				SELECT 
				  'br' AS fid_type,
				  '' AS id_order,
				  customer.id_customer,
				  CONCAT (customer.firstname, ' ', customer.lastname) AS customer,
				  cart_rule.date_from,
				  cart_rule.date_to,
				  '' AS points,
				  cart_rule.reduction_amount
				FROM " . _DB_PREFIX_ . "cart_rule cart_rule
				INNER JOIN (
				  SELECT loyalty.id_cart_rule, loyalty.id_customer
				  FROM " . _DB_PREFIX_ . "loyalty loyalty
				  LEFT JOIN " . _DB_PREFIX_ . "orders orders ON loyalty.id_order = orders.id_order AND orders.id_shop IN (" . pSQL($params->shop) . ")
				  GROUP BY loyalty.id_cart_rule, loyalty.id_customer
				) AS loyalty ON cart_rule.id_cart_rule = loyalty.id_cart_rule
				LEFT JOIN " . _DB_PREFIX_ . "customer customer 
				ON loyalty.id_customer = customer.id_customer
				
				WHERE cart_rule.quantity > 0
				AND NOW() BETWEEN cart_rule.date_from AND cart_rule.date_to

			";

			$res = $db->executeS($q);
			if ($params->index) {
				$res = indexArray($res, 'id');
			}

		}

		return $res;

	}	
	

	public static function getDeffered($db, $params, $dir = '>')
	{

			$q = "
				SELECT 
				  orders.id_order,
				  orders.id_customer,
				  CONCAT(customer.firstname, ' ', customer.lastname) AS customer,
				  orders.id_shop,
				  ok.id_till AS id_cashdrawer,
				  ok.id_employee,
				  orders.`date_add` AS `date`,
				  ABS(cfk.diff) AS amount
				FROM " . _DB_PREFIX_ . "orders orders
				LEFT JOIN " . _DB_PREFIX_ . "order_kerawen ok ON orders.id_order = ok.id_order
				LEFT JOIN " . _DB_PREFIX_ . "customer customer ON orders.id_customer = customer.id_customer
				INNER JOIN (

				  SELECT 
					cfk.id_order, 
					SUM(cfk.amount) AS diff 
				  FROM " . _DB_PREFIX_ . "cashdrawer_flow_kerawen cfk
				  WHERE cfk.id_payment_mode IN (5,11)
				  GROUP BY cfk.id_order
				  HAVING diff " . $dir . " 0
				  							
				) cfk
				ON orders.id_order = cfk.id_order
				WHERE orders.id_shop IN (" . pSQL($params->shop) . ")
				ORDER BY orders.id_order DESC
			";

			//TODO : filters date,till,cashdrawer,employee

			$res = $db->executeS($q);
			if ($params->index) {
				$res = indexArray($res, 'id_order');
			}


		return $res;

	}	


	public static function getRefundDeffered($db, $params, $dir = '<')
	{
	
		return self::getDeffered($db, $params, $dir);
	}	


	public static function getCustomer($db, $params)
	{
	
		$bypage = self::bypage();
	
		$page = 1;
		if (!empty($params->page)) {
			$page = (int) $params->page;
		}
	
		$orderby = 'id_customer';
		if (!empty($params->orderby)) {
			$orderby = $params->orderby;
		}
	
		$sortby = 'DESC';
		if (!empty($params->sortby)) {
			$sortby = $params->sortby;
		}
	
	
		$id_shop = -1;
		if (!empty($params->shop)) {
			$id_shop = (int) $params->shop;
		}
			
		$id_lang = (int) Context::getContext()->language->id;
	
		$q = "
			SELECT SQL_CALC_FOUND_ROWS
			a.`id_customer`,
			gl.`name` AS gender,
			`firstname`,
			`lastname`,		
			IF (ck.fakemail = 1 ,'',a.email ) as email, 
            l.iso_code AS lang,
            g.name AS `group`,
			shop.name AS shop,
			`newsletter`,
			a.date_add,
			a.birthday,

 			CASE  
					WHEN ck.mobile != '' THEN ck.mobile
					ELSE
					    (SELECT 
					     	CASE 
							WHEN ad.phone_mobile != '' THEN ad.phone_mobile
						ELSE ''
						END AS mobil		   
					     	FROM " . _DB_PREFIX_ . "address ad
					     	WHERE ad.id_customer = a.id_customer AND ad.active = 1 AND ad.deleted = 0
					     	ORDER BY ad.date_upd DESC
					     	LIMIT 1)		
			END AS mobile,

			CASE 
					WHEN ck.phone != '' THEN ck.phone
					 
					ELSE
					    (SELECT 
					     	CASE 
								WHEN ad.phone != '' THEN ad.phone		 
							ELSE ''
						END AS phone 					   
					     	FROM " . _DB_PREFIX_ . "address ad
					     	WHERE ad.id_customer = a.id_customer AND ad.active = 1 AND ad.deleted = 0
					     	ORDER BY ad.date_upd DESC
					     	LIMIT 1)		

					END AS phone,
			
			CASE
				WHEN ck.postalcode != '' THEN ck.postalcode 
				ELSE 
					(SELECT 
						CASE 
						WHEN ad.postcode != '' THEN ad.postcode
						ELSE ''
					END AS postalcode  
					FROM " . _DB_PREFIX_ . "address ad
					     	WHERE ad.id_customer = a.id_customer AND ad.active = 1 AND ad.deleted = 0
					     	ORDER BY ad.date_upd DESC
					     	LIMIT 1
					)
			 END AS cp,

			/*
			(
				SELECT c.date_add
				FROM " . _DB_PREFIX_ . "guest g
				LEFT JOIN " . _DB_PREFIX_ . "connections c ON c.id_guest = g.id_guest
				WHERE g.id_customer = a.id_customer ORDER BY c.date_add DESC LIMIT 1
			) AS connect,
			*/
		
			(
				SELECT o.date_upd
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.valid = 1 AND o.id_customer = a.id_customer
				ORDER BY o.date_upd DESC
				LIMIT 1
			)	AS last_order_date,
	
			/*
			(
				SELECT DATEDIFF(NOW(), o.date_upd)
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.valid = 1 AND o.id_customer = a.id_customer
				ORDER BY o.date_upd DESC
				LIMIT 1
			)	AS last_order_day,
			*/
	
			(
				SELECT COUNT(id_order)
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.id_customer = a.id_customer AND o.valid = 1
			) AS orders,					

			(
				SELECT SUM(total_paid_real / conversion_rate)
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.id_customer = a.id_customer AND o.valid = 1
			) AS amount						
						
			FROM `" . _DB_PREFIX_ . "customer` a
			LEFT JOIN `" . _DB_PREFIX_ . "gender_lang` gl ON (a.id_gender = gl.id_gender AND gl.id_lang = " . $id_lang . ")
			LEFT JOIN `" . _DB_PREFIX_ . "shop` shop ON a.id_shop = shop.id_shop 
			LEFT JOIN `" . _DB_PREFIX_ . "customer_kerawen` ck ON a.id_customer = ck.id_customer 
            LEFT JOIN `" . _DB_PREFIX_ . "lang` l ON a.id_lang = l.id_lang
            LEFT JOIN `" . _DB_PREFIX_ . "group_lang` g ON (a.id_default_group = g.id_group AND g.id_lang = " . $id_lang . ")

			/*
			JOIN (
 				SELECT
				o.id_customer,
				MAX(o.date_upd) AS date_upd,
				DATEDIFF(NOW(), MAX(o.date_upd)) AS last_order_day
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.valid = 1
 				GROUP BY o.id_customer
			) c ON (a.id_customer  = c.id_customer)
			*/
	
			WHERE a.`deleted` = 0
						
		";
		
		//echo $q ;


		if ($id_shop > 0) {
			$q .= " AND a.id_shop = " . $id_shop . " ";
		}
		
		
		$q .= "
			ORDER BY " . $orderby . " " . $sortby . "
		";
	
	
		if ((int)$page > 0) {
			$q .= " LIMIT " . ( ((int)$page - 1) * $bypage) . ", " . $bypage;
		}
	
	
		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id');
		}
	
		$total = $db->getValue('SELECT FOUND_ROWS()');
	
		$data = array(
				'data' => $res,
				'total' => $total,
				'bypage' => $bypage,
		);
	 
		return $data;
	
	}
		
	public static function getBestCustomer($db, $params)
	{
		$bypage = self::bypage();
	
		$page = 1;
		if (!empty($params->page)) {
			$page = (int) $params->page;
		}
	
		$orderby = 'amount';
		$sortby = 'DESC';

		$id_shop = -1;
		if (!empty($params->shop)) {
			$id_shop = (int) $params->shop;
		}		
		
		$id_lang = Context::getContext()->language->id;
	

		$q = "
			SELECT SQL_CALC_FOUND_ROWS
			a.`id_customer`,
			gl.`name` AS gender,
			`firstname`,
			`lastname`,
			IF (ck.fakemail = 1 ,'',a.email ) as email, 
            l.iso_code AS lang,
            g.name AS `group`,
			CASE 
					 
					WHEN ck.mobile != '' THEN ck.mobile
					ELSE
					     (SELECT 
					     	CASE 
							WHEN ad.phone_mobile != '' THEN ad.phone_mobile
						ELSE ''
						END AS mobil
					   
					     FROM " . _DB_PREFIX_ . "address ad
					     WHERE ad.id_customer = a.id_customer AND ad.active = 1 AND ad.deleted = 0
					     ORDER BY ad.date_upd DESC
					     LIMIT 1)		
			END AS mobile,

			CASE 
					WHEN ck.phone != '' THEN ck.phone
					 
					ELSE
					     (SELECT 
					     	CASE 
							WHEN ad.phone != '' THEN ad.phone
							 
						ELSE ''
						END AS phone
					     					   
					     FROM " . _DB_PREFIX_ . "address ad
					     WHERE ad.id_customer = a.id_customer AND ad.active = 1 AND ad.deleted = 0
					     ORDER BY ad.date_upd DESC
					     LIMIT 1)		

			END AS phone,

			CASE
				WHEN ck.postalcode != '' THEN ck.postalcode 
				ELSE 
					(SELECT 
						CASE 
						WHEN ad.postcode != '' THEN ad.postcode
						ELSE ''
					END AS postalcode  
					FROM " . _DB_PREFIX_ . "address ad
					     	WHERE ad.id_customer = a.id_customer AND ad.active = 1 AND ad.deleted = 0
					     	ORDER BY ad.date_upd DESC
					     	LIMIT 1
					)
			END AS cp,		
			
			shop.name AS shop,
			(
				SELECT COUNT(id_order)
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.date_add BETWEEN '" . $params->from . "' AND '" . $params->to . "'
				AND o.id_customer = a.id_customer AND o.valid = 1
			) AS orders,

			(
				SELECT SUM(total_paid_real / conversion_rate)
				FROM " . _DB_PREFIX_ . "orders o
				WHERE o.date_add BETWEEN '" . $params->from . "' AND '" . $params->to . "'
				AND o.id_customer = a.id_customer AND o.valid = 1
			) AS amount
							
			FROM `" . _DB_PREFIX_ . "customer` a
			LEFT JOIN `" . _DB_PREFIX_ . "gender_lang` gl ON (a.id_gender = gl.id_gender AND gl.id_lang = " . $id_lang . ")
			LEFT JOIN `" . _DB_PREFIX_ . "shop` shop ON a.id_shop = shop.id_shop 
			LEFT JOIN `" . _DB_PREFIX_ . "customer_kerawen` ck ON a.id_customer = ck.id_customer 
            LEFT JOIN `" . _DB_PREFIX_ . "lang` l ON a.id_lang = l.id_lang
            LEFT JOIN `" . _DB_PREFIX_ . "group_lang` g ON (a.id_default_group = g.id_group AND g.id_lang = " . $id_lang . ")
	
			WHERE a.`deleted` = 0
		";

		if ($id_shop > 0) {
			$q .= " AND a.id_shop = " . $id_shop . " ";
		}
							
		$q .= "	ORDER BY " . $orderby . " " . $sortby . " ";
	
	
		if ((int)$page > 0) {
			$q .= " LIMIT " . ( ((int)$page - 1) * $bypage) . ", " . $bypage;
		}
	
	
		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id');
		}
	
		$total = $db->getValue('SELECT FOUND_ROWS()');
	
		$data = array(
				'data' => $res,
				'total' => $total,
				'bypage' => $bypage,
		);
	
		return $data;
	
	}


	public static function getPaymentMethods($log = null) {
		
		$db = Db::getInstance();
		
		$methods = array();
		$excludeArray = array();
		
		//Active payment methods
		$cfg = Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
		foreach($cfg as $key=>$method) {
			if ($method['payment'] || $method['refund']) {
				$methods[(int)$method['id']] = $method['label'];
				$excludeArray[] = $method['label'];
			}
		}
		
		
		$exclude = 'NOT IN ("' . implode('", "', $excludeArray) . '", "Retour produits", "Carte cadeau")';
		
		//Others methods... (inactive now or web)
		if ($log == 'klog') {
			$res = $db->executeS('
				SELECT 
					p.mode AS payment_method 
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment p
				WHERE p.mode ' . $exclude . '
				GROUP BY p.mode
				ORDER BY p.mode
			');
		} else {
			$res = $db->executeS('
				SELECT
					payment_method
				FROM ' . _DB_PREFIX_ . 'order_payment
				WHERE payment_method ' . $exclude . '
				GROUP BY payment_method
			');
		}
		
		
		foreach($res as $item) {
			$methods[] = $item['payment_method'];
		}
		
		
		return $methods;
	}


	public static function getOrders($db, $params)
	{
		
		$bypage = self::bypage();
		$paymentMethods = self::getPaymentMethods();
		
		$page = 1;
		if (!empty($params->page)) {
			$page = (int) $params->page;
		}
		
		$id_shop = -1;
		if (isset($params->shop)) {
			$id_shop = $params->shop;
		}
		
		$id_till = -1;
		if (isset($params->till)) {
			$id_till = $params->till;
		}
		
		$id_employee = -1;
		if (isset($params->employee)) {
			$id_employee = $params->employee;
		}
		
		$sum = "";
		foreach($paymentMethods AS $key=>$val) {
		    $sum .= " (SELECT SUM(op" . $key . ".amount) FROM " . _DB_PREFIX_ . "order_payment op" . $key . " WHERE op" . $key . ".order_reference = o.reference AND op" . $key . ".payment_method = '" . pSQL($val) . "') AS `" . pSQL($val) . "`, " . "\n";
		}
		
		$dateFormat = "'%d-%m-%Y'";
		
		$q = "
			SELECT SQL_CALC_FOUND_ROWS
				o.id_order AS `id_order`,
				o.reference AS `ref`,
				IF(o.invoice_number = 0, '', LPAD(o.invoice_number, 8, 'FA000000')) AS inv_num,
				DATE_FORMAT(o.date_add, " . $dateFormat . ") AS `date`,
				IF ((cdk.id_cash_drawer = 0 OR ISNULL(cdk.id_cash_drawer)), 'Web' , cdk.name) AS till,
				CONCAT(c.firstname, ' ', c.lastname) AS customer,
				o.total_paid AS `total`,
				" . $sum . "
				CASE
					WHEN NOT (ISNULL(SUM(op_99.amount)) OR ISNULL(SUM(op_98.amount))) THEN SUM(op_99.amount) + SUM(op_98.amount)
					WHEN NOT (ISNULL(SUM(op_99.amount))) THEN SUM(op_99.amount)
					WHEN NOT (ISNULL(SUM(op_98.amount))) THEN SUM(op_98.amount)
					ELSE NULL
				END AS `Retour produit`
			FROM " . _DB_PREFIX_ . "orders o
			LEFT JOIN " . _DB_PREFIX_ . "customer c ON o.id_customer = c.id_customer
			LEFT JOIN " . _DB_PREFIX_ . "order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN " . _DB_PREFIX_ . "cash_drawer_kerawen cdk ON ok.id_till = cdk.id_cash_drawer
			LEFT JOIN " . _DB_PREFIX_ . "order_payment_kerawen opk ON opk.reference = o.reference

			LEFT JOIN " . _DB_PREFIX_ . "order_payment op_99 ON op_99.id_order_payment = opk.id_order_payment AND op_99.payment_method = 'Retour produits'
			LEFT JOIN " . _DB_PREFIX_ . "order_payment op_98 ON op_98.order_reference = o.reference AND op_98.payment_method = 'Retour produits'
 
			WHERE o.date_add BETWEEN '" . $params->from . "' AND '" . $params->to . "'
		";
		
		if ($id_shop !== -1) {
		    $q .= " AND o.id_shop IN (" . $id_shop . ") ";
		}
		if ($id_till != -1) {
		    
		    //case NULL
		    $caseNull = "";
		    if (!(strpos("," . $id_till . ",", ",0,") === false)) {
		        $caseNull =  " OR ok.id_till IS NULL";
		    }
		    $q .= " AND (ok.id_till IN (" . $id_till . ")" . $caseNull . ") ";
		}
		if ($id_employee != -1) {
		    $q .= " AND ok.id_employee = " . $id_employee . " ";
		}
		
		$q .= "
			GROUP BY o.reference, op_99.payment_method, op_98.payment_method
			ORDER BY o.id_order DESC ";
		
		if ((int)$page > 0) {
		    $q .= " LIMIT " . ( ((int)$page - 1) * $bypage) . ", " . $bypage;
		}

		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id_order');
		}
		
		$total = $db->getValue('SELECT FOUND_ROWS()');
		
		$data = array(
			'data' => $res,
			'total' => $total,
			'bypage' => $bypage,
		);
		
		return $data;
	}
	
	
	public static function getOperations($db, $params)
	{
		
		$bypage = self::bypage();
		$paymentMethods = self::getPaymentMethods();
		
		$page = 1;
		if (!empty($params->page)) {
			$page = (int) $params->page;
		}
		
		$id_shop = -1;
		if (isset($params->shop)) {
			$id_shop = $params->shop;
		}
		
		$id_till = -1;
		if (isset($params->till)) {
			$id_till = $params->till;
		}
		
		$id_employee = -1;
		if (isset($params->employee)) {
			$id_employee = $params->employee;
		}
		

		$q = "";
		$sum = "";
		$sum2 = "";

		foreach($paymentMethods AS $key=>$val) {
		    $sum .= "( SELECT SUM(op" . $key . ".amount) FROM " . _DB_PREFIX_ . "order_payment op" . $key . " WHERE op" . $key . ".order_reference = o.reference AND op" . $key . ".payment_method = '" . pSQL($val) . "' ) AS `" . pSQL($val) . "`," . "\n";
			$sum2 .= " (SELECT SUM(op" . $key . ".amount) FROM " . _DB_PREFIX_ . "kerawen_525_payment op" . $key . " WHERE ok.id_operation = op" . $key . ".id_operation AND op" . $key . ".mode = '" . pSQL($val) . "')  AS `" . pSQL($val) . "`, " . "\n";
		}
		

		$deltaDate = self::getDeltaDate($params->from, $params->to);
		$dateFormat = "'%d-%m-%Y'";
		
		$q .= "
			SELECT SQL_CALC_FOUND_ROWS
				1 AS weight,
				CONCAT('_', cok.id_cashdrawer_op) AS id_op, 
				DATE_FORMAT(cok.date, " . $dateFormat . ") AS `date`,
				GROUP_CONCAT(o.id_order) AS id_order, 
				GROUP_CONCAT(o.reference) AS ref, 
				IF ((cdk.id_cash_drawer = 0 OR ISNULL(cdk.id_cash_drawer)), 'Web' , cdk.name) AS till,
				GROUP_CONCAT(DISTINCT CONCAT(c.firstname, ' ', c.lastname)) AS customer,
				o.total_paid AS `total`,
				" . $sum . "
				CASE
					WHEN NOT (ISNULL(SUM(op_99.amount)) OR ISNULL(SUM(op_98.amount))) THEN SUM(op_98.amount)
					/*WHEN NOT (ISNULL(SUM(op_99.amount)) OR ISNULL(SUM(op_98.amount))) THEN SUM(op_99.amount) + SUM(op_98.amount)*/
					WHEN NOT (ISNULL(SUM(op_99.amount))) THEN -SUM(op_99.amount)
					WHEN NOT (ISNULL(SUM(op_98.amount))) THEN -SUM(op_98.amount)
					ELSE NULL
				END AS `Retour produit`

			FROM " . _DB_PREFIX_ . "cashdrawer_op_kerawen cok

			LEFT JOIN " . _DB_PREFIX_ . "cashdrawer_sale_kerawen csk ON cok.id_cashdrawer_op = csk.id_cashdrawer_op
			LEFT JOIN " . _DB_PREFIX_ . "orders o ON o.id_order = csk.id_order
			LEFT JOIN " . _DB_PREFIX_ . "order_kerawen ok ON ok.id_order = o.id_order
			LEFT JOIN " . _DB_PREFIX_ . "customer c ON o.id_customer = c.id_customer 
			LEFT JOIN " . _DB_PREFIX_ . "cash_drawer_kerawen cdk ON ok.id_till = cdk.id_cash_drawer
			LEFT JOIN " . _DB_PREFIX_ . "order_payment_kerawen opk ON opk.reference = o.reference

			LEFT JOIN " . _DB_PREFIX_ . "order_payment op_99 ON op_99.id_order_payment = opk.id_order_payment AND op_99.payment_method = 'Retour produits'
			LEFT JOIN " . _DB_PREFIX_ . "order_payment op_98 ON op_98.order_reference = o.reference AND op_98.payment_method = 'Retour produits'

			WHERE cok.oper = 'SALE' AND o.id_order IS NOT NULL	
			AND cok.date BETWEEN '" . $deltaDate["from1"] . "' AND '" . $deltaDate["to1"] . "'
		";

		if ($id_shop !== -1) {
			$q .= " AND o.id_shop IN (" . $id_shop . ") ";
		}
		if ($id_till != -1) {
			
			//case NULL
			$caseNull = "";
			if (!(strpos("," . $id_till . ",", ",0,") === false)) {
				$caseNull =  " OR ok.id_till IS NULL";
			}
			$q .= " AND (ok.id_till IN (" . $id_till . ")" . $caseNull . ") ";
		}
		if ($id_employee != -1) {
			$q .= " AND ok.id_employee = " . $id_employee . " ";
		}
		
		
		$q .= " GROUP BY cok.id_cashdrawer_op ";

		$q .= " UNION ALL ";
		
		$q .= " 
		SELECT
			2 AS weight,
			ok.id_operation AS id_op,
			DATE_FORMAT(ok.date, " . $dateFormat . ") AS `date`,
			GROUP_CONCAT(o.ps_order) AS id_order,
			GROUP_CONCAT(os.reference) AS ref,
			GROUP_CONCAT(DISTINCT IF (ok.id_till = 0, 'Web' ,  ok.till_name)) AS till,
			GROUP_CONCAT(DISTINCT CONCAT(c.firstname, ' ', c.lastname)) AS customer,
			os.total_paid AS total,
			" . $sum2 . "
			IF(o.ps_slip = 0, NULL, -o.total_ti) AS 'Retour produits'
		FROM " . _DB_PREFIX_._KERAWEN_525_PREFIX_ . "operation ok
		LEFT JOIN " . _DB_PREFIX_._KERAWEN_525_PREFIX_ . "sale ks ON ok.id_operation = ks.id_operation
		LEFT JOIN " . _DB_PREFIX_._KERAWEN_525_PREFIX_ . "order o ON o.id_sale = ks.id_sale
		LEFT JOIN " . _DB_PREFIX_ . "orders os ON os.id_order = o.ps_order
		LEFT JOIN " . _DB_PREFIX_ . "customer c ON os.id_customer = c.id_customer 
		WHERE ok.type = 'SALE'
		AND ok.date BETWEEN '" . $deltaDate["from2"] . "' AND '" . $deltaDate["to2"] . "'
		";

		if ($id_shop !== -1) {
			$q .= " AND ok.id_shop IN (" . $id_shop . ") ";
		}
		if ($id_till != -1) {
			$q .= " AND (ok.id_till IN (" . $id_till . ")) ";
		}
		if ($id_employee != -1) {
			$q .= " AND ok.id_operator = " . $id_employee . " ";
		}

		$q .= " GROUP BY ok.id_operation 
				ORDER BY weight DESC, id_op DESC";
		

		if ((int)$page > 0) {
			$q .= " LIMIT " . ( ((int)$page - 1) * $bypage) . ", " . $bypage;
		}

		
		//echo '<pre>';
		//echo $q;
		
		$res = $db->executeS($q);
		if ($params->index) {
			$res = indexArray($res, 'id_op');
		}
		
		$total = $db->getValue('SELECT FOUND_ROWS()');
		
		$data = array(
			'data' => $res,
			'total' => $total,
			'bypage' => $bypage,
		);
		
		return $data;
	}
	
	

	public static function getCertifiedDate($offset = 0) {
		return Db::getInstance()->getValue('
			SELECT DATE_FORMAT(MIN(date)  + INTERVAL ' . (int)$offset . ' SECOND, "%Y-%m-%d %H:%i:%s")
			FROM '._DB_PREFIX_.'kerawen_version
			WHERE STRCMP(version, "2.1") >= 0 AND res = 1');
	}


	public static function getDeltaDate($from, $to) {
		
		$refdate = self::getCertifiedDate();
		$delta = array();
		
		if ($refdate <= $from) {
			$delta['from1'] = 0;
			$delta['to1'] = 0;
			$delta['from2'] = $from;
			$delta['to2'] = $to;
		} elseif ($refdate > $to) {
			$delta['from1'] = $from;
			$delta['to1'] = $to;
			$delta['from2'] = 0;
			$delta['to2'] = 0;
		} else {
			$delta['from1'] = $from;
			$delta['to1'] = self::getCertifiedDate(-1);
			$delta['from2'] = $refdate;
			$delta['to2'] = $to;
		}
		return $delta;	
	}

	
	
	public static function statshour($db, $op_filter) {
		return array();
	}


	public static function statsweek($db, $op_filter) {
		return array();
	}
	
	public static function statsmonth($db, $op_filter) {
		return array();
	}

	public static function cats() {
		return sharedCats();
	}
	
};


function getLog($types, $from, $to,
	$id_till, $id_empl, $id_shop,
	$init_from, $init_to,
	$vars = array())
{
	$res = array();
	$db = Db::getInstance();
	
	$more_filter = '
		'.getLogFilter('co.id_cashdrawer', $id_till).'
		'.getLogFilter('co.id_employee', $id_empl).'
		'.getLogFilter('co.id_shop', $id_shop);

	$op_filter = '
		co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
		'.$more_filter;

	$op_init_filter = '
		co.date BETWEEN "'.pSQL($init_from).'" AND "'.pSQL($init_to).'"
		'.$more_filter;

	$op_flow_filter = '
		co.date BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
		AND co.oper = "SALE"
		'.$more_filter;
	
	$op_close_filter = '
		co.date BETWEEN "'.pSQL($init_from).'" AND "'.pSQL($init_to).'"
		AND co.oper != "SALE"
		'.$more_filter;
	
	$res['ops'] = $db->executeS('
		SELECT
			CONCAT("_", co.id_cashdrawer_op) AS id_op,
			co.id_cashdrawer AS id_till,
			IFNULL(co.id_employee, 0) AS id_empl,
			co.id_shop AS id_shop,
			co.date AS date,
			co.oper AS oper
		FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
		WHERE '.$op_init_filter);

	foreach($types as $type) {
		if ($type == 'checks') {
			$res[$type] = LogQuery::$type($db, $op_close_filter, $vars);
		}
		else if ($type == 'flows') {
			$res[$type] = array_merge(
				LogQuery::$type($db, $op_flow_filter, $vars),
				LogQuery::$type($db, $op_close_filter, $vars));
		}
		else {
			$res[$type] = LogQuery::$type($db, $op_filter, $vars);
		}
	}

	return $res;
}

function getLogMvt($params) {
	$db = Db::getInstance();
	$res = array();
	foreach($params->require as $type)
		$res[$type] = LogQuery::$type($db, $params);
	return $res;
}

