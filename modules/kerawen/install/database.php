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

function getEnumForDatabase($values)
{
	foreach ($values as &$val) $val = '"'.$val.'"';
	return 'ENUM('.implode(',', $values).')';
}

function updateDatabase($sql, &$errors)
{
	try
	{
		Db::getInstance()->execute($sql);
	}
	catch (Exception $e)
	{
		$errors[] = $e;
	}
}

function installDatabase(&$warning)
{
	$errors = array();

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'employee_kerawen` (
			`id_employee` INT UNSIGNED NOT NULL,
			`active_cart` INT UNSIGNED,
			`id_cash_drawer` INT UNSIGNED NULL,
			PRIMARY KEY (`id_employee`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'employee_kerawen`
		ADD COLUMN (
		    `password` VARCHAR(255) NULL
		)', $errors);
	updateDatabase('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'employee_cash_drawer_kerawen` (
			  `id_employee` int(11) unsigned NOT NULL,
			  `id_cash_drawer` int(11) unsigned NOT NULL,
			  PRIMARY KEY (`id_employee`,`id_cash_drawer`),
			  KEY `id_cash_drawer` (`id_cash_drawer`)
			) DEFAULT CHARSET=utf8; ', $errors);

	updateDatabase('
		DROP VIEW IF EXISTS '._DB_PREFIX_.'product_search_kerawen', $errors);
	updateDatabase('
		DROP TABLE IF EXISTS '._DB_PREFIX_.'product_search_kerawen', $errors);	
	updateDatabase('
		CREATE VIEW '._DB_PREFIX_.'product_search_kerawen AS
		SELECT
			p.id_product AS id_product,
			ps.id_shop AS id_shop,
			pl.id_lang AS id_lang,
			CONCAT_WS(" ",
				pl.name, m.name,
				p.reference, p.ean13, p.upc,
				GROUP_CONCAT(pa.reference SEPARATOR " "),
				GROUP_CONCAT(pa.supplier_reference SEPARATOR " "),
				GROUP_CONCAT(pa.ean13 SEPARATOR " "),
				GROUP_CONCAT(pa.upc SEPARATOR " "),
				GROUP_CONCAT(psu.product_supplier_reference SEPARATOR " ")
		) AS data
		FROM '._DB_PREFIX_.'product p
		JOIN '._DB_PREFIX_.'product_shop ps
			ON ps.id_product = p.id_product AND ps.active=1
		JOIN '._DB_PREFIX_.'product_lang pl
			ON pl.id_product = p.id_product AND pl.id_shop = ps.id_shop
		LEFT JOIN '._DB_PREFIX_.'product_attribute pa
			ON pa.id_product = p.id_product
		LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pas
			ON pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = ps.id_shop
		LEFT JOIN '._DB_PREFIX_.'manufacturer m
			ON m.id_manufacturer = p.id_manufacturer
		LEFT JOIN '._DB_PREFIX_.'product_supplier psu
			ON psu.id_product = p.id_product AND psu.id_product_attribute = pa.id_product_attribute
		GROUP BY p.id_product, ps.id_shop, pl.id_lang', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_wm_kerawen` (
			`id_product` INT UNSIGNED NOT NULL,
			`measured` TINYINT(1) UNSIGNED,
			`unit` VARCHAR(255),
			`precision` INT UNSIGNED,
			`unit_price` DECIMAL(20,6),
			`code` VARCHAR(32),
			PRIMARY KEY (`id_product`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'product_wm_kerawen`
		ADD COLUMN (
		    is_gift_card TINYINT(1) UNSIGNED DEFAULT 0
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'product_wm_kerawen`
		ADD COLUMN (
		    `extra` VARCHAR(255)
		)', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_wm_code_kerawen` (
			`id_code` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_product` INT UNSIGNED NOT NULL,
			`code` VARCHAR(32),
			`unit_price` DECIMAL(20,6),
			`id_product_attribute` INT,
			PRIMARY KEY (`id_code`)
		) DEFAULT CHARSET=utf8', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_attribute_kerawen` (
			`id_product_attribute` INT UNSIGNED NOT NULL,
			`id_cart` INT UNSIGNED
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'product_attribute_kerawen`
		ADD COLUMN `measure` DECIMAL(20,6)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'product_attribute_kerawen`
		ADD COLUMN `id_code` INT UNSIGNED', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cart_kerawen` (
			`id_cart` INT UNSIGNED NOT NULL,
			`count` INT,
			`total` REAL,
			`id_employee` INT UNSIGNED,
			`delivery_mode` INT,
			`delivery_date` DATETIME,
			PRIMARY KEY (`id_cart`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_kerawen`
		ADD COLUMN (
			id_address INT UNSIGNED,
			carrier VARCHAR(50)
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_kerawen`
		ADD COLUMN (
			version INT UNSIGNED,
			suspended TINYINT(1) UNSIGNED
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_kerawen`
		ADD COLUMN (
		    quote TINYINT(1) unsigned DEFAULT 0,
		    quote_title VARCHAR(255) DEFAULT NULL,
		    quote_expiry DATETIME DEFAULT NULL,
		    quote_number INT(11) unsigned DEFAULT NULL
		)', $errors);
		
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cart_product_kerawen` (
			`id_cart` INT UNSIGNED NOT NULL,
			`id_product` INT UNSIGNED NOT NULL,
			`id_product_attribute` INT UNSIGNED NOT NULL,
			`note` TEXT,
			PRIMARY KEY (`id_cart`, `id_product`, `id_product_attribute`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_product_kerawen`
		ADD COLUMN (
			`specific_price_cart` INT(10) UNSIGNED DEFAULT 0 NOT NULL
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_product_kerawen`
		ADD COLUMN (
			`specific_price_cart_calc` TINYINT(1) UNSIGNED DEFAULT "0" NULL
		)', $errors);
	
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_product_kerawen`
		ADD COLUMN (
			`specific_price_redefined` TINYINT(1) UNSIGNED DEFAULT "0" NULL
		)', $errors);
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cart_rule_kerawen` (
			`id_cart_rule` INT UNSIGNED NOT NULL,
			`type` '.getEnumForDatabase(array(
				_KERAWEN_CR_DISCOUNT_, _KERAWEN_CR_CREDIT_,
			)).',
			`id_cart` INT UNSIGNED,
			PRIMARY KEY (`id_cart_rule`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_rule_kerawen`
		ADD COLUMN (
			`id_product` INT UNSIGNED NULL,
			`id_attribute` INT UNSIGNED NULL
		)', $errors);
	updateDatabase('
		ALTER TABLE '._DB_PREFIX_.'cart_rule_kerawen
		CHANGE `type` `type` '.getEnumForDatabase(array(
		    _KERAWEN_CR_DISCOUNT_, _KERAWEN_CR_CREDIT_, _KERAWEN_CR_PREPAID_, _KERAWEN_CR_GIFT_CARD_,_KERAWEN_CR_VOUCHER_
		)), $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_rule_kerawen`
		ADD COLUMN (
			`id_order` INT UNSIGNED NULL
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_rule_kerawen`
		ADD COLUMN (
			`id_parent_cart_rule` INT UNSIGNED NULL
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cart_rule_kerawen`
		ADD COLUMN (
        	`is_voucher` TINYINT(1) UNSIGNED DEFAULT "0" NULL,
        	`display_from` DATETIME NULL,
        	`display_to` DATETIME NULL, 
        	`display_counter` INT(10) UNSIGNED NULL
		)', $errors);
	updateDatabase('
		DROP VIEW IF EXISTS `'._DB_PREFIX_.'customer_kerawen`', $errors);
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_kerawen` (
			`id_customer` INT UNSIGNED NOT NULL,
			`loyalty_number` VARCHAR(32),
			`id_prepaid` INT UNSIGNED,
			PRIMARY KEY (`id_customer`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'customer_kerawen`
		ADD COLUMN (
			`company` VARCHAR(255),
			`phone` VARCHAR(50),
			`mobile` VARCHAR(50),
			`fakemail` TINYINT(1)
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'customer_kerawen`
		ADD COLUMN (
			`postalcode` VARCHAR(250)
		)', $errors);
		
	updateDatabase('
		DROP VIEW IF EXISTS `'._DB_PREFIX_.'customer_search_kerawen`', $errors);
	updateDatabase('
		DROP TABLE IF EXISTS `'._DB_PREFIX_.'customer_search_kerawen`', $errors);
	updateDatabase('
		CREATE VIEW `'._DB_PREFIX_.'customer_search_kerawen` AS
		SELECT
			c.`id_customer` AS id,
			CONCAT_WS(", ",CONCAT(c.`firstname`," ",c.`lastname`) , 
				CASE WHEN ck.fakemail = 1 THEN "" ELSE c.email END ,
				c.`company`
			) AS info,
			CONCAT_WS(" ",
				c.`firstname`, c.`lastname` , 
				CASE WHEN ck.fakemail = 1 THEN "" ELSE c.email END ,
				ck.`phone`, ck.`mobile`, ck.`loyalty_number`,
				c.`company`
			) AS full,
			c.`firstname`,
			c.`lastname`
		FROM `'._DB_PREFIX_.'customer` AS c 

		LEFT JOIN `'._DB_PREFIX_.'customer_kerawen` AS ck 
			ON c.`id_customer` = ck.`id_customer`
	    WHERE c.`deleted` = 0'
	, $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_seen_kerawen` (
			`id_customer` INT UNSIGNED NOT NULL,
			`id_product` INT UNSIGNED NOT NULL,
			`counter` INT,
			PRIMARY KEY (`id_customer`, `id_product`)
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'customer_seen_kerawen` 
			 ADD `lastvisit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'last seen date\' AFTER `counter`; ', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'order_kerawen` (
			`id_order` INT UNSIGNED NOT NULL,
			`id_employee` INT UNSIGNED,
			`delivery_mode` INT,
			`delivery_date` DATETIME,
			`display_date` DATETIME,
			`preparation_status` INT(10) UNSIGNED,
			`payment_status` INT(10) UNSIGNED,
			PRIMARY KEY (`id_order`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN (
			product_global_discount DECIMAL(20,6),
			free_shipping TINYINT(1)
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN is_paid TINYINT(1)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN id_till INT UNSIGNED AFTER id_order', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN due DECIMAL(20,6) AFTER preparation_status', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN gift_card_flag TINYINT(1) UNSIGNED DEFAULT 0', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN invoice_note TEXT NULL', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_kerawen`
		ADD COLUMN round_type TINYINT(1) NULL', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'order_ref_kerawen` (
			`reference` VARCHAR(9) NOT NULL,
			`id_order` INT UNSIGNED NOT NULL,
			due DECIMAL(20,6),
			paid DECIMAL(20,6),
			PRIMARY KEY (`reference`)
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'order_detail_kerawen` (
			`id_order_detail` INT UNSIGNED NOT NULL,
			`measure` DECIMAL(20,6),
			`unit` VARCHAR(255),
			`precision` INT UNSIGNED,
			`unit_price_tax_excl` DECIMAL(20,6),
			`unit_price_tax_incl` DECIMAL(20,6)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD PRIMARY KEY (id_order_detail)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD COLUMN (
			quantity_ordered INT(10) UNSIGNED,
			measure_ordered DECIMAL(20,6)
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD COLUMN (
			prepared TINYINT(1) UNSIGNED
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD COLUMN (
			note TEXT
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD COLUMN (
			`specific_price_cart` INT(10) UNSIGNED DEFAULT 0 NOT NULL
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		CHANGE `specific_price_cart` `specific_price_cart` DECIMAL(10,6) UNSIGNED DEFAULT "0.000000" NOT NULL
		', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD COLUMN (
			`id_tax_rules_group` INT UNSIGNED DEFAULT 0 NOT NULL
		)', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'order_detail_kerawen`
		ADD COLUMN (
			`margin_vat` TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL
		)', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'order_payment_kerawen` (
			`id_order_payment` INT UNSIGNED NOT NULL,
			`reference` VARCHAR(9)
		) DEFAULT CHARSET=utf8', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'return_kerawen` (
			`id_return` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_cart` INT UNSIGNED NOT NULL ,
			`id_order_detail` INT UNSIGNED NOT NULL ,
			`quantity` decimal(15,3) NOT NULL,
			`price_tax_incl` decimal(20,6) NOT NULL,
			`refund_tax_incl` decimal(20,6) NOT NULL,
			`refund_tax_excl` decimal(20,6) NOT NULL,
			`tax_rate` decimal (20,6) NOT NULL,
			`back_to_stock` INT UNSIGNED NOT NULL ,
			`took_effect` INT UNSIGNED NOT NULL,
			`order_after_reference` VARCHAR(100) NOT NULL, 
			`id_order_after` INT UNSIGNED NOT NULL ,
			`date` DATETIME NOT NULL,
			PRIMARY KEY (`id_return`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'return_kerawen`
		ADD COLUMN `id_order` INT UNSIGNED AFTER `id_cart`
		', $errors);
	
	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cash_drawer_kerawen` (
			`id_cash_drawer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(50),
			`date_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`date_upd` timestamp,
			PRIMARY KEY (`id_cash_drawer`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cash_drawer_kerawen`
		ADD COLUMN `active` TINYINT(1) UNSIGNED
		', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cash_drawer_kerawen`
		ADD COLUMN `token` VARCHAR(255) NULL,
		ADD COLUMN `screen` TINYINT(1) DEFAULT "0" NULL,
		ADD COLUMN `printer` TINYINT(1) DEFAULT "0",
		ADD COLUMN `display` TINYINT(1) DEFAULT "0"
		', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cash_drawer_kerawen`
		ADD COLUMN `printer_local` VARCHAR(255) NULL, 
		ADD COLUMN `printer_remote` VARCHAR(255) NULL
		', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cash_drawer_kerawen`
		ADD COLUMN `tpe` TINYINT(1) DEFAULT "0"
		', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cashdrawer_op_kerawen` (
			`id_cashdrawer_op` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`id_cashdrawer` INT UNSIGNED NOT NULL DEFAULT 1,
			`id_employee` INT UNSIGNED,
			`oper` '.getEnumForDatabase(array(
				_KERAWEN_CDOP_OPEN_, _KERAWEN_CDOP_CLOSE_, _KERAWEN_CDOP_FLOW_, _KERAWEN_CDOP_SALE_,
			)).',
			`error` BOOLEAN,
			PRIMARY KEY (`id_cashdrawer_op`)
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		ALTER TABLE  `'._DB_PREFIX_.'cashdrawer_op_kerawen` 
		ADD  `id_shop` INT(10) UNSIGNED NULL AFTER `id_employee`
		', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cashdrawer_sale_kerawen` (
			`id_cashdrawer_sale` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_cashdrawer_op` INT UNSIGNED NOT NULL,
			`oper` '.getEnumForDatabase(array(
				_KERAWEN_CDSO_ORDER_, _KERAWEN_CDSO_VALID_, _KERAWEN_CDSO_SLIP_, _KERAWEN_CDSO_CANCEL_,
			)).',
			`id_order` INT UNSIGNED NULL,
			`id_order_slip` INT UNSIGNED NULL,
			PRIMARY KEY (`id_cashdrawer_sale`)
		) DEFAULT CHARSET=utf8', $errors);

// 			`total_tax_incl` decimal(20,6) NOT NULL,
// 			`total_tax_excl` decimal(20,6) NOT NULL,

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cashdrawer_flow_kerawen` (
			`id_cashdrawer_flow` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_cashdrawer_op` INT UNSIGNED NOT NULL,
			`id_payment_mode` INT UNSIGNED,
			`amount` DECIMAL(17,2),
			`id_order_payment` INT UNSIGNED NULL,
			`id_credit` INT UNSIGNED NULL,
			`comments` TEXT,
			`date_deferred` TIMESTAMP NULL,
			`op_out` INT UNSIGNED NULL,
			`date_actual` TIMESTAMP NULL,
			`corrected` DECIMAL(17,2) NULL,
			PRIMARY KEY (`id_cashdrawer_flow`)
		) DEFAULT CHARSET=utf8', $errors);
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cashdrawer_flow_kerawen`
		ADD COLUMN `count` INT UNSIGNED NULL AFTER `amount`
		', $errors);
		
	updateDatabase('
		ALTER TABLE `'._DB_PREFIX_.'cashdrawer_flow_kerawen`
		ADD COLUMN `id_order` INT UNSIGNED NULL AFTER `amount`,
		ADD COLUMN `id_order_slip` INT UNSIGNED NULL AFTER `id_order`
		', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cashdrawer_close_kerawen` (
			`id_cashdrawer_close` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_cashdrawer_op` INT UNSIGNED NOT NULL,
			`id_payment_mode` INT UNSIGNED,
			`checked` DECIMAL(17,2),
			`error` DECIMAL(17,2),
			PRIMARY KEY (`id_cashdrawer_close`)
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'account_kerawen` (
			`id_account` INT UNSIGNED NOT NULL,
			`is_category` TINYINT(1) NOT NULL,
			`product_account` VARCHAR(100) NOT NULL,
			PRIMARY KEY (`id_account`, `is_category`)
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'kerawen_version` (
			`id_version` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`version` VARCHAR(50),
			`date` DATETIME NOT NULL,
			`res` TINYINT(1) UNSIGNED,
			PRIMARY KEY (`id_version`)
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customization_field_kerawen` ( 
			`id_customization_field` INT(10) UNSIGNED, 
			`field_type` VARCHAR(50) 
		) DEFAULT CHARSET=utf8', $errors);

	updateDatabase('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'kerawen_fix` ( 
			`name` VARCHAR(255) NOT NULL, 
			`installed` TINYINT(1) UNSIGNED,
			PRIMARY KEY (`name`)
		) DEFAULT CHARSET=utf8', $errors);
	
	// Improve searching by code performances
	createIndexes($errors);
	
	//First time Permissions 
	//Link all cashdrawers to all employees
	completeInitialData($errors);
	
	// Update legacy data
	//!!! moved to kerawen configuration
	//completeData($errors);
	
	foreach ($errors as $e)
		$warning .= $e->getMessage().'<br>';
	return true;
}

function createIndexes(&$errors)
{
	$indexes = array(
		array('table' => 'product', 'column' => 'ean13'),
		array('table' => 'product', 'column' => 'upc'),
		array('table' => 'product_attribute', 'column' => 'ean13'),
		array('table' => 'product_attribute', 'column' => 'upc'),
		array('table' => 'cart_rule', 'column' => 'code'),
		array('table' => 'orders', 'column' => 'reference'),
		array('table' => 'product_wm_kerawen', 'column' => 'code'),
		array('table' => 'customer_kerawen', 'column' => 'loyalty_number'),
		array('table' => 'order_payment_kerawen', 'column' => 'id_order_payment'),
		array('table' => 'order_payment_kerawen', 'column' => 'reference'),
	);
	
	foreach($indexes as $index)
		updateDatabase('
			ALTER TABLE '._DB_PREFIX_.$index['table'].'
			ADD INDEX '.$index['column'].' ('.$index['column'].')', $errors);
}


function completeInitialData(&$errors) {

	try {	
	
		$qd = 'SELECT id_cash_drawer FROM '._DB_PREFIX_.'cash_drawer_kerawen';
		$cashdrawers = Db::getInstance()->executeS($qd);
			
		//No cashdrawer -> create first one
		if (!$cashdrawers) {
			Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'cash_drawer_kerawen (name, active) VALUES ("Caisse 1", 1)');
			$cashdrawers = Db::getInstance()->executeS($qd);
		}		
		

		$cnt = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'employee_cash_drawer_kerawen');
		if (!$cnt) {
			
			$employees = Db::getInstance()->executeS('
				SELECT id_employee FROM '._DB_PREFIX_.'employee
			');
			
			$pass = Tools::encrypt('');
			
			foreach($employees as $employee) {
				foreach($cashdrawers as $cashdrawer) {
					Db::getInstance()->insert('employee_cash_drawer_kerawen', array(
						'id_employee' => $employee['id_employee'],
						'id_cash_drawer' => $cashdrawer['id_cash_drawer']
					));				
				}				
			}
	
		}

	}
	catch (Exception $e) { $errors[] = $e; }	
	
}


function completeData(&$errors)
{
	$db = Db::getInstance();

	try {
		// Find old fashion openings
		$to_migrate = $db->executeS('
			SELECT sub.id_op
			FROM (
				SELECT co.id_cashdrawer_op AS id_op, COUNT(cc.id_cashdrawer_close) AS details
				FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
				LEFT JOIN '._DB_PREFIX_.'cashdrawer_close_kerawen cc ON cc.id_cashdrawer_op = co.id_cashdrawer_op
				WHERE co.oper = "'.pSQL(_KERAWEN_CDOP_OPEN_).'"
				GROUP BY co.id_cashdrawer_op
			) sub
			WHERE sub.details = 0');
		
		$ids = array();
		foreach ($to_migrate as $op) $ids[] = $op['id_op'];
		
		if (count($ids) > 0) {
		
			$ids = '('.implode(',', $ids).')';
			// Transfer data from flow to close
			$db->execute('
				INSERT INTO '._DB_PREFIX_.'cashdrawer_close_kerawen
					(id_cashdrawer_op, id_payment_mode, checked, error)
				SELECT
					cf.id_cashdrawer_op, cf.id_payment_mode, cf.amount, 0
				FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
				WHERE cf.id_cashdrawer_op IN '.pSQL($ids));
			$db->execute('
				DELETE FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen
				WHERE id_cashdrawer_op IN '.pSQL($ids));

		}
		
	}
	catch (Exception $e) { $errors[] = $e; }
	
	try {
		// Do not count canceled orders as sales
		$db->execute('
			DELETE FROM '._DB_PREFIX_.'cashdrawer_sale_kerawen
			WHERE id_cashdrawer_op IN (
				SELECT sub.id_op FROM (
					SELECT
					co.id_cashdrawer_op AS id_op,
					cs.id_cashdrawer_sale AS id_sale,
					o.total_paid_tax_incl AS sale,
					(SELECT SUM(cf.amount)
						FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
						WHERE cf.id_cashdrawer_op = co.id_cashdrawer_op) AS flow
					FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
					JOIN '._DB_PREFIX_.'cashdrawer_sale_kerawen cs ON cs.id_cashdrawer_op = co.id_cashdrawer_op AND cs.oper = "ORDER"
					JOIN '._DB_PREFIX_.'orders o ON o.id_order = cs.id_order) sub
				WHERE sub.flow < 0 AND sub.flow = -sub.sale)');
	}
	catch (Exception $e) { $errors[] = $e; }
	
	try {
		// Reference orders within flows
		$db->execute('
			UPDATE '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
			JOIN '._DB_PREFIX_.'order_payment op ON op.id_order_payment = cf.id_order_payment
			JOIN '._DB_PREFIX_.'orders o ON o.reference = op.order_reference
			SET cf.id_order = o.id_order');
	}
	catch (Exception $e) { $errors[] = $e; }

	try {
		// Count withdrawn cheques in closing operations
		$db->execute('
			UPDATE '._DB_PREFIX_.'cashdrawer_flow_kerawen cf
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cf.id_cashdrawer_op
			SET count = (SELECT COUNT(*)
				FROM (SELECT id_cashdrawer_flow, op_out AS op_out
					FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen
					WHERE id_payment_mode = '.pSQL(_KERAWEN_PM_CHEQUE_).') cfout
				WHERE cfout.op_out = cf.id_cashdrawer_op)
			WHERE co.oper = "'.pSQL(_KERAWEN_CDOP_CLOSE_).'"
			AND cf.id_payment_mode = '.pSQL(_KERAWEN_PM_CHEQUE_));
	}
	catch (Exception $e) { $errors[] = $e; }
	
	try {
		// Reference orders
		require_once(_KERAWEN_CLASS_DIR_.'/order_state.php');
		$preparation = implode(',', getPrestashopOrderStates());
		$canceled = implode(',', array(
			_PS_OS_CANCELED_,
			_PS_OS_ERROR_
		));
		$received = (int)Configuration::get('KERAWEN_OS_RECEIVED');
		
		$db->execute('
			INSERT INTO '._DB_PREFIX_.'order_kerawen
				(id_order, delivery_mode, preparation_status, due)
			SELECT
				o.id_order,
				'.pSQL(_KERAWEN_DM_DELIVERY_).',
				IFNULL(oh2.id_order_state, '.pSQL($received).'),
				IF(oh2.id_order_state IN ('.pSQL($canceled).'), 0, o.total_paid_tax_incl)
			FROM '._DB_PREFIX_.'orders o
			LEFT JOIN (
				SELECT
					id_order AS id_order,
					MAX(id_order_history) AS id_order_history
				FROM '._DB_PREFIX_.'order_history
				WHERE id_order_state IN ('.pSQL($preparation).')
				GROUP BY id_order) oh1
				ON oh1.id_order = o.id_order
			LEFT JOIN '._DB_PREFIX_.'order_history oh2
				ON oh2.id_order_history = oh1.id_order_history
			ON DUPLICATE KEY UPDATE
				preparation_status = VALUES(preparation_status),
				due = VALUES(due)');
	}
	catch (Exception $e) { $errors[] = $e; }

	try {
		// Reference order references
		$db->execute('
			INSERT INTO '._DB_PREFIX_.'order_ref_kerawen
				(reference, id_order, due, paid)
			SELECT
				o.reference,
				(SELECT MIN(o1.id_order)
					FROM '._DB_PREFIX_.'orders o1
					WHERE o1.reference = o.reference),
				(SELECT SUM(ok.due)
					FROM '._DB_PREFIX_.'order_kerawen ok
					JOIN '._DB_PREFIX_.'orders o2 ON o2.id_order = ok.id_order
					WHERE o2.reference = o.reference),
				(SELECT SUM(op.amount)
					FROM '._DB_PREFIX_.'order_payment op
					WHERE op.order_reference = o.reference)
			FROM '._DB_PREFIX_.'orders o
			GROUP BY o.reference
			ON DUPLICATE KEY UPDATE
				due = VALUES(due),
				paid = VALUES(paid)');
	}
	catch (Exception $e) { $errors[] = $e; }
	
	
	try {
		// Compute order payment status
		$not_paid = array(
			_PS_OS_CANCELED_,
			_PS_OS_ERROR_
		);
		$not_paid = implode(',', $not_paid);
		
		$db->execute('
			UPDATE '._DB_PREFIX_.'order_kerawen ok
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = ok.id_order
			SET ok.is_paid = (o.total_paid_real = IF(ok.preparation_status IN ('.$not_paid.'), 0, o.total_paid_tax_incl))');
			//WHERE ok.is_paid IS NULL');
	}
	catch (Exception $e) { $errors[] = $e; }

	try {
		// Reference order origin till
		$db->execute('
			UPDATE '._DB_PREFIX_.'order_kerawen ok
			JOIN '._DB_PREFIX_.'cashdrawer_sale_kerawen cs ON cs.id_order = ok.id_order
			JOIN '._DB_PREFIX_.'cashdrawer_op_kerawen co ON co.id_cashdrawer_op = cs.id_cashdrawer_op
			SET ok.id_till = co.id_cashdrawer
			WHERE ok.id_till IS NULL');
	}
	catch (Exception $e) { $errors[] = $e; }

	try {
		// Reference returned order
		$db->execute('
			UPDATE '._DB_PREFIX_.'return_kerawen rk
			LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = rk.id_order_detail
			SET rk.id_order = od.id_order
			WHERE rk.id_order IS NULL');
	}
	catch (Exception $e) { $errors[] = $e; }
	
	try {
		// Transfer scale codes into dedicated table
		$db->execute('
			INSERT INTO '._DB_PREFIX_.'product_wm_code_kerawen
			(id_product, code, unit_price)
			SELECT id_product, code, unit_price
			FROM '._DB_PREFIX_.'product_wm_kerawen
			WHERE unit_price > 0');
		$db->execute('
			UPDATE '._DB_PREFIX_.'product_wm_kerawen
			SET code = NULL, unit_price = NULL');
	}
	catch (Exception $e) { $errors[] = $e; }
}
