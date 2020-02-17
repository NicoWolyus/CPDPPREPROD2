<?php
/**
* @author    KerAwen <contact@kerawen.com>
* @copyright 2017-2018 KerAwen
*/

define('_KERAWEN_525_SIGN_URL_', 'https://www.kerawen.com/app/security/sign2.php');

define('_KERAWEN_525_PREFIX_', 'kerawen_525_');

define('_KERAWEN_525_OP_SALE_',			'SALE');
define('_KERAWEN_525_OP_DUPLICATE_',	'DUPLICATE');
define('_KERAWEN_525_OP_INVOICE_',		'INVOICE');
define('_KERAWEN_525_OP_GTOTAL_',		'GTOTAL');
define('_KERAWEN_525_OP_EVENT_',		'EVENT');
define('_KERAWEN_525_OP_ARCHIVE_',		'ARCHIVE');
define('_KERAWEN_525_OP_OPEN_',			'OPEN');
define('_KERAWEN_525_OP_FLOW_',			'FLOW');
define('_KERAWEN_525_OP_CLOSE_',		'CLOSE');

define('_KERAWEN_525_PER_PERPETUAL_',	'PERPETUAL');
define('_KERAWEN_525_PER_YEAR_',		'YEAR');
define('_KERAWEN_525_PER_MONTH_',		'MONTH');
define('_KERAWEN_525_PER_DAY_',			'DAY');
define('_KERAWEN_525_PER_SALE_',		'SALE');

define('_KERAWEN_525_EVT_ARCH_PERIOD_',		20);
define('_KERAWEN_525_EVT_ARCH_EXER_',		30);
define('_KERAWEN_525_EVT_STOP_',			40);
define('_KERAWEN_525_EVT_CLOSE_PERIOD_',	50);
define('_KERAWEN_525_EVT_CLOSE_EXER_',		60);
define('_KERAWEN_525_EVT_START_DEG_',		70);
define('_KERAWEN_525_EVT_START_',			80);
define('_KERAWEN_525_EVT_SIGN_DEFAULT_',	90);
define('_KERAWEN_525_EVT_EXEC_SPECIAL_',	100);
define('_KERAWEN_525_EVT_EXPORT_',			110);
define('_KERAWEN_525_EVT_STOP_DEG_',		120);
define('_KERAWEN_525_EVT_DECL_OPER_',		130);
define('_KERAWEN_525_EVT_IMPORT_',			140);
define('_KERAWEN_525_EVT_PRINT_FAIL_',		150);
define('_KERAWEN_525_EVT_ACCOUNT_GEN_',		160);
define('_KERAWEN_525_EVT_TILL_CHECK_',		170);
define('_KERAWEN_525_EVT_ACCOUNT_TRANSF_',	180);
define('_KERAWEN_525_EVT_CANCEL_OP_',		190);
define('_KERAWEN_525_EVT_PURGE_',			200);
define('_KERAWEN_525_EVT_TRANSF_DATA_',		210);
define('_KERAWEN_525_EVT_RESTORE_',			220);
define('_KERAWEN_525_EVT_BACKUP_',			230);
define('_KERAWEN_525_EVT_MAINTAIN_',		240);
define('_KERAWEN_525_EVT_INSTALL_',			250);
define('_KERAWEN_525_EVT_INIT_DATA_',		260);
define('_KERAWEN_525_EVT_EVOL_PARAM_',		270);
define('_KERAWEN_525_EVT_CONTROL_',			280);
define('_KERAWEN_525_EVT_ACCOUNT_EXCH_',	290);
define('_KERAWEN_525_EVT_ACTIV_PARAM_',		300);
define('_KERAWEN_525_EVT_DOC_CANCEL_',		310);
define('_KERAWEN_525_EVT_CHANGE_EXER_',		400);
define('_KERAWEN_525_EVT_FUNC_SPEC_',		999);
define('_KERAWEN_525_EVT_ERROR_',			998);


class Kerawen525 {
	
	/* **************************************************************
	 * SINGLETON
	*/
	
	static $instance = null;
	
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Kerawen525();
		}
		return self::$instance;
	}
	
	protected function __construct() {
		$this->ops = array();
		$this->exception = null;
	}
	
	
	/* **************************************************************
	 * SYSTEM CALLBACKS
	*/
	
	public static function OnException($exception) {
		self::getInstance()->exception = $exception;
	}
	
	public static function OnShutdown() {
		self::getInstance()->logOperation();

		// Send order emails after certified logging
		require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
		Configuration::set('PS_MAIL_METHOD', getExtendedContext('PS_MAIL_METHOD', 3));
		$order_states = getExtendedContext('order_states', array());
		$db = Db::getInstance();
		foreach ($order_states as $os) {
			$id_order = $os['id_order'];
			$id_os = $os['id_os'];
			$id_history = $db->getValue('
				SELECT id_order_history
				FROM '._DB_PREFIX_.'order_history
				WHERE id_order = '.pSQL($id_order).'
					AND id_order_state = '.pSQL($id_os).'
				ORDER BY id_order_history DESC');
			$oh = new OrderHistory();
			$oh->id = $id_history;
			$oh->id_order = $id_order;
			$order = new Order($id_order);
			if (method_exists($oh, 'sendEmail')) {
				$oh->sendEmail($order);
			}
		}
	}
	
	
	/* **************************************************************
	 * DATABASE MODEL
	*/

	public function createDatabase() {
		$db = Db::getInstance();
		
		// Enumerations
		$op_type = 'ENUM("'.implode('","', array(
				_KERAWEN_525_OP_SALE_,
				_KERAWEN_525_OP_DUPLICATE_,
				_KERAWEN_525_OP_INVOICE_,
				_KERAWEN_525_OP_GTOTAL_,
				_KERAWEN_525_OP_EVENT_,
				_KERAWEN_525_OP_ARCHIVE_,
				_KERAWEN_525_OP_OPEN_,
				_KERAWEN_525_OP_FLOW_,
				_KERAWEN_525_OP_CLOSE_,
		)).'")';
		
		$period_type = 'ENUM("'.implode('","', array(
			_KERAWEN_525_PER_PERPETUAL_,
			_KERAWEN_525_PER_YEAR_,
			_KERAWEN_525_PER_MONTH_,
			_KERAWEN_525_PER_DAY_,
			_KERAWEN_525_PER_SALE_,
		)).'")';

		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'signature (
				type '.$op_type.' NOT NULL,
				sign TEXT,
				short VARCHAR(32),
				PRIMARY KEY (type)
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation (
				id_operation INT UNSIGNED NOT NULL AUTO_INCREMENT,
				date DATETIME NOT NULL,
				type '.$op_type.' NOT NULL,
				id_till INT UNSIGNED,
				till_name TEXT,
				id_operator INT UNSIGNED,
				operator_name TEXT,
				PS_SHOP_NAME VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_EMAIL VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_ADDR1 VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_ADDR2 VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_CODE VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_CITY VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_COUNTRY VARCHAR(255) NOT NULL DEFAULT "",
				PS_SHOP_PHONE VARCHAR(255) NOT NULL DEFAULT "",
				KERAWEN_SHOP_SIRET VARCHAR(255) NOT NULL DEFAULT "",
				KERAWEN_SHOP_NAF VARCHAR(255) NOT NULL DEFAULT "",
				KERAWEN_SHOP_TVA_INTRA VARCHAR(255) NOT NULL DEFAULT "",
				KERAWEN_SHOP_URL VARCHAR(255) NOT NULL DEFAULT "",
				KERAWEN_MODULE_VERSION VARCHAR(255) NOT NULL DEFAULT "",
				PRIMARY KEY (id_operation)
			) DEFAULT CHARSET=utf8');
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				MODIFY COLUMN type '.$op_type.' NOT NULL');
		} catch (Exception $e) {}
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				ADD COLUMN id_shop INT UNSIGNED AFTER operator_name');
		} catch (Exception $e) {}
		try {
			// Adapt for till operations logging
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				ADD COLUMN id_ref INT UNSIGNED AFTER type,
				ADD COLUMN id_close INT UNSIGNED AFTER id_ref');
			// Mark previous ops as closed / to be closed
			$db->update(_KERAWEN_525_PREFIX_.'operation', array(
				'id_ref' => 0,
				'id_close' => 0,
			));
		} catch (Exception $e) {}
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				ADD COLUMN canceled TINYINT(1) UNSIGNED NULL DEFAULT NULL');
		} catch (Exception $e) {}

		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale (
				id_sale INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_operation INT UNSIGNED NOT NULL,
				id_customer INT UNSIGNED,
				customer_name TEXT,
				nb_lines INT,
				total_te DECIMAL(20,6),
				total_ti DECIMAL(20,6),
				description TEXT,
				signature TEXT,
				sign_short TEXT,
				nb_prints INT UNSIGNED,
				PRIMARY KEY (id_sale),
				FOREIGN KEY (id_operation)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation(id_operation)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
					
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax (
				id_sale INT UNSIGNED NOT NULL,
				id_tax INT UNSIGNED,
				tax_name TEXT,
				tax_rate DECIMAL(20,6),
				total_te DECIMAL(20,6),
				total_ti DECIMAL(20,6),
				tax_amount DECIMAL(20,6),
				PRIMARY KEY (id_sale, id_tax),
				FOREIGN KEY (id_sale)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale(id_sale)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');

		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail (
				id_sale_detail INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_sale INT UNSIGNED NOT NULL,
				id_order INT UNSIGNED,
				id_order_detail INT UNSIGNED,
				id_carrier INT UNSIGNED,
				wrapping TINYINT(1) UNSIGNED,
				item_name TEXT,
				id_tax INT UNSIGNED,
				tax_name TEXT,
				tax_rate DECIMAL(20,6),
				unit_te DECIMAL(20,6),
				unit_ti DECIMAL(20,6),
				ecotax_te DECIMAL(20,6),
				ecotax_ti DECIMAL(20,6),
				quantity INT,
				measure DECIMAL(20,6),
				measure_unit TEXT,
				measure_precision INT,
				discount_te DECIMAL(20,6),
				discount_ti DECIMAL(20,6),
				discount_rate DECIMAL(20,6),
				total_te DECIMAL(20,6),
				total_ti DECIMAL(20,6),
				PRIMARY KEY (id_sale_detail),
				FOREIGN KEY (id_sale)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale(id_sale)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail
				ADD FOREIGN KEY (id_order)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order(id_order)
					ON DELETE CASCADE');
		} catch (Exception $e) {}
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail
				ADD COLUMN margin_vat TINYINT(1) UNSIGNED AFTER tax_rate');
		} catch (Exception $e) {}
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail
				ADD COLUMN version_name TEXT AFTER item_name,
				ADD COLUMN purchase_te DECIMAL(20,6) AFTER total_ti');
		} catch (Exception $e) {}
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail
				ADD COLUMN gift_card TINYINT(1) UNSIGNED DEFAULT 0 NULL');
		} catch (Exception $e) {}
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail
				ADD COLUMN discount_percent DECIMAL(20,6) AFTER measure_precision');
		} catch (Exception $e) {}
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'discount (
				id_sale INT UNSIGNED NOT NULL,
				id_order INT UNSIGNED NOT NULL,
				ps_order INT UNSIGNED NOT NULL,
				ps_cart_rule INT UNSIGNED NOT NULL,
				label VARCHAR(255),
				tax_incl DECIMAL(20,6) NOT NULL DEFAULT 0,
				tax_excl DECIMAL(20,6) NOT NULL DEFAULT 0,
				KEY (id_order),
				FOREIGN KEY (id_sale)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale(id_sale)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'discount
				ADD COLUMN discount_percent DECIMAL(20,6) AFTER label');
		} catch (Exception $e) {}
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment (
				id_payment INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_operation INT UNSIGNED NOT NULL,
				id_order_payment INT UNSIGNED,
				id_currency INT UNSIGNED,
				currency TEXT,
				amount DECIMAL(20,6),
				mode TEXT,
				PRIMARY KEY (id_payment),
				FOREIGN KEY (id_operation)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation(id_operation)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment
				ADD COLUMN ps_orders TEXT AFTER id_order_payment');
		} catch (Exception $e) {}
		try {
			// Adapt for till operations logging
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment
				ADD COLUMN id_mode INT UNSIGNED AFTER amount,
				ADD COLUMN deferred DATETIME,
				ADD COLUMN id_out INT UNSIGNED,
				ADD COLUMN corrected DECIMAL(20,6)');
			// Mark any payment from till closed/to be closed
			$db->execute('
				UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment p
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation o ON o.id_operation = p.id_operation
				SET p.id_out = 0
				WHERE o.id_till != 0');
			// Unmark cheques still in till
			$db->execute('
				UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment p
				JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen f ON f.id_order_payment = p.id_order_payment
				SET p.id_mode = 2, p.id_out = NULL
				WHERE f.id_payment_mode = 2 AND f.op_out IS NULL');
			} catch (Exception $e) {}
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'duplicate (
				id_duplicate INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_sale INT UNSIGNED,
				nb_duplicate INT UNSIGNED,
				id_operator INT UNSIGNED,
				date DATETIME,
				description TEXT,
				signature TEXT,
				sign_short TEXT,
				PRIMARY KEY (id_duplicate),
				FOREIGN KEY (id_sale)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale(id_sale)
					ON DELETE CASCADE
		) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order (
				id_order INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_sale INT UNSIGNED NOT NULL,
				ps_order INT UNSIGNED NOT NULL,
				ps_slip INT UNSIGNED NOT NULL,
				total_te DECIMAL(20,6),
				total_ti DECIMAL(20,6),
				date DATETIME,
				number VARCHAR(32),
				name TEXT,
				postcode TEXT,
				description TEXT,
				signature TEXT,
				sign_short TEXT,
				PRIMARY KEY (id_order),
				FOREIGN KEY (id_sale)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale(id_sale)
					ON DELETE CASCADE
		) DEFAULT CHARSET=utf8');

		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order_tax (
				id_order INT UNSIGNED NOT NULL,
				id_tax INT UNSIGNED,
				tax_name TEXT,
				tax_rate DECIMAL(20,6),
				total_te DECIMAL(20,6),
				total_ti DECIMAL(20,6),
				tax_amount DECIMAL(20,6),
				PRIMARY KEY (id_order, id_tax),
				FOREIGN KEY (id_order)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order(id_order)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'invoice ( 
				id_order INT UNSIGNED NOT NULL, 
				invoice_date DATETIME,
 				invoice_label VARCHAR(20), 
				invoice_address TEXT, 
				delivery_address TEXT, 
				PRIMARY KEY (id_order),
				FOREIGN KEY (id_order) 
				REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order(id_order)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		try {
			$db->execute('
				ALTER TABLE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'invoice
				ADD COLUMN firstname TEXT AFTER invoice_label,
				ADD COLUMN lastname TEXT AFTER firstname,
				ADD COLUMN company TEXT AFTER lastname');
		} catch (Exception $e) {}
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal (
				id_gtotal INT UNSIGNED NOT NULL AUTO_INCREMENT,
				period_type '.$period_type.' NOT NULL,
				period_ref VARCHAR(16) NOT NULL,
				total_te DECIMAL(20,6) NOT NULL DEFAULT 0,
				total_ti DECIMAL(20,6) NOT NULL DEFAULT 0,
				perp_te DECIMAL(20,6) NOT NULL DEFAULT 0,
				perp_ti DECIMAL(20,6) NOT NULL DEFAULT 0,
				date DATETIME,
				description TEXT,
				signature TEXT,
				sign_short TEXT,
				PRIMARY KEY (id_gtotal)
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			INSERT INTO '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal (id_gtotal, period_type, period_ref)
			VALUES (1, "'._KERAWEN_525_PER_PERPETUAL_.'", "")
			ON DUPLICATE KEY UPDATE period_type = VALUES(period_type)');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal_tax (
				id_gtotal INT UNSIGNED NOT NULL,
				id_tax INT UNSIGNED,
				tax_name TEXT,
				tax_rate DECIMAL(20,6),
				total_te DECIMAL(20,6) NOT NULL DEFAULT 0,
				total_ti DECIMAL(20,6) NOT NULL DEFAULT 0,
				tax_amount DECIMAL(20,6) NOT NULL DEFAULT 0,
				PRIMARY KEY (id_gtotal, id_tax),
				FOREIGN KEY (id_gtotal)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal(id_gtotal)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'event (
				id_event INT UNSIGNED NOT NULL AUTO_INCREMENT,
				date DATETIME NOT NULL,
				id_operator INT UNSIGNED,
				code INT UNSIGNED NOT NULL,
				label TEXT,
				info TEXT,
				description TEXT,
				signature TEXT,
				sign_short TEXT,
				PRIMARY KEY (id_event)
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'tax (
				id_tax INT UNSIGNED NOT NULL AUTO_INCREMENT,
				ps_tax INT UNSIGNED NOT NULL,
				PRIMARY KEY (id_tax)
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_flow (
				id_tillflow INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_operation INT UNSIGNED NOT NULL,
				id_currency INT UNSIGNED,
				currency TEXT,
				amount DECIMAL(20,6),
				id_mode INT UNSIGNED,
				mode TEXT,
				count INT UNSIGNED,
				comments TEXT,
				PRIMARY KEY (id_tillflow),
				FOREIGN KEY (id_operation)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation(id_operation)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		
		$db->execute('
			CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check (
				id_tillcheck INT UNSIGNED NOT NULL AUTO_INCREMENT,
				id_operation INT UNSIGNED NOT NULL,
				id_mode INT UNSIGNED,
				mode TEXT,
				id_currency INT UNSIGNED,
				currency TEXT,
				checked DECIMAL(20,6),
				count INT UNSIGNED,
				error DECIMAL(20,6),
				PRIMARY KEY (id_tillcheck),
				FOREIGN KEY (id_operation)
					REFERENCES '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation(id_operation)
					ON DELETE CASCADE
			) DEFAULT CHARSET=utf8');
		return true;
	}
	
	/* **************************************************************
	 * REGISTERING EVENTS
	*/
	
	protected function &registerReference($ref) {
		// TEMP ignore reference
		$ref = 'REF';
		
		if (!isset($this->ops[$ref])) $this->ops[$ref] = array(
			'orders' => array(),
			'slips' => array(),
			'payments' => array(),
		);
		return $this->ops[$ref];
	}
		
	public function registerOrder($order, $before = false) {
		$op = &$this->registerReference($order->reference);
		if (!isset($op['orders'][$order->id])) {
			$op['orders'][$order->id] = array(
				'before' => null,
				'after' => null,
				'items' => array(),
			);
			if ($before) {
				// Register initial value
				$o = &$op['orders'][$order->id];
				if(!$o['before']) $o['before'] = new Order($order->id);
			}
		}
	}
	
	public function registerOrderDetail($detail, $before = false) {
		$order = new Order($detail->id_order);
		$this->registerOrder($order, true);
		$op = &$this->registerReference($order->reference);
		$o = &$op['orders'][$order->id];
		if (!isset($o['items'][$detail->id])) {
			$o['items'][$detail->id] = array(
				'before' => null,
				'after' => null,
			);
			if ($before) {
				// Register initial value
				$od = &$o['items'][$detail->id];
				if (!$od['before']) $od['before'] = new OrderDetail($detail->id);
			}
		}
	}
	
	public function registerOrderSlip($slip, $before = false) {
		$order = new Order($slip->id_order);
		$this->registerOrder($order, true);
		$op = &$this->registerReference($order->reference);
		if (!isset($op['slips'][$slip->id])) {
			$op['slips'][$slip->id] = array(
				'before' => null,
				'after' => null,
			);
			if ($before) {
				// Register initial value
				$s = &$op['slips'][$slip->id];
				if (!$s['before']) {
					$s['before'] = new OrderSlip($slip->id);
					$s['before']->details = OrderSlip::getOrdersSlipDetail($slip->id);
				}
			}
		}
	}
	
	public function registerOrderPayment($payment, $before = false) {
		$op = &$this->registerReference($payment->order_reference);
		if (!isset($op['payments'][$payment->id])) {
			$op['payments'][$payment->id] = array(
				'before' => null,
				'after' => null,
			);
			if ($before) {
				// Register initial value
				$p = &$op['payments'][$payment->id];
				if (!$p['before']) $p['before'] = $this->getOrderPayment($payment->id);
			}
		}
		$p = &$op['payments'][$payment->id];
		$p['id_mode'] = getExtendedContext('id_payment_mode', null);
		$p['deferred'] = getExtendedContext('date_deferred', null);
	}
	
	
	/* **************************************************************
	 * LOGGING OPERATIONS
	*/
	
	protected function logOperation() {
		if (!$this->ops) return;
		
		require_once (_KERAWEN_CLASS_DIR_.'/data.php');
		
		// Clean cache to prevent direct database changes to be omitted
		// In particular, marketplace modules modify orders this way
		Cache::clean('objectmodel_Order_*');
		
		$datetime = time();
		$this->closeGrandTotal($datetime);
		
		$this->getDbLink()->beginTransaction();
		$db = Db::getInstance();
		
		// --------------------------------
		// 1. Gather information about sale
		
		$context = Context::getContext();
		$operator = $context->employee;
		$shop = $context->shop;
		$id_shop = $shop ? $shop->id : null;
		$id_till = isset($context->kerawen) && isset($context->kerawen->id_cashdrawer) ? $context->kerawen->id_cashdrawer : 0;
		$till_name = $db->getValue('SELECT name FROM '._DB_PREFIX_.'cash_drawer_kerawen WHERE id_cash_drawer = '.pSQL($id_till));
		
		$module = Module::getInstanceByName('kerawen');
		
		$this->label_shipping = $module->l('Shipping', pathinfo(__FILE__, PATHINFO_FILENAME));
		$this->label_wrapping = $module->l('Wrapping', pathinfo(__FILE__, PATHINFO_FILENAME));
		
		// Log errors into JET
		$errors = array();
		if ($error = error_get_last()) {
			$loggable = array(
				E_ERROR,
				E_CORE_ERROR,
				E_COMPILE_ERROR,
				E_USER_ERROR,
				E_RECOVERABLE_ERROR
			);
		
			if (in_array($error['type'], $loggable)) {
				$errors[] = 'ERROR '.$error['type'].': "'.$error['message'].'"'
					.' at '.$error['file'].', line '.$error['line'];
			}
		}
		if ($this->exception) {
			$errors[] = 'EXCEPTION '.$this->exception->getCode().': "'.$this->exception->getMessage().'"'
				.' at '.$this->exception->getFile().', line '.$this->exception->getLine();
		}
		foreach ($errors as &$error) {
			$this->logEvent(
				_KERAWEN_525_EVT_ERROR_,
				'Error',
				$error,
				date('Y-m-d H:i:s', $datetime),
				$operator ? $operator->id : null);
		}
		
		// Organize ops by customer
		$by_cust = array();
		foreach($this->ops as $ref => &$op) {
			foreach($op['orders'] as $id_order => &$order) {
				$order['after'] = new Order($id_order);
				if (!$order['before'] && !$order['after']->id) continue;
				$id_cust = $order['after']->id ? $order['after']->id_customer : $order['before']->id_customer;
				if (!isset($by_cust[$id_cust])) {
					$by_cust[$id_cust] = array(
						'orders' => array(),
						'slips' => array(),
						'payments' => array(),
					);
				}
				$by_cust[$id_cust]['orders'][$id_order] = $order;
			}
			
			foreach($op['slips'] as $id_slip => &$slip) {
				$slip['after'] = new OrderSlip($id_slip);
				if (!$slip['before'] && !$slip['after']->id) continue;
				$id_order = $slip['after']->id ? $slip['after']->id_order : $slip['before']->id_order;
				$order = new Order($id_order);
				$id_cust = $order->id_customer;
				if (!isset($by_cust[$id_cust])) {
					$by_cust[$id_cust] = array(
						'orders' => array(),
						'slips' => array(),
						'payments' => array(),
					);
				}
				if (!isset($by_cust[$id_cust]['orders'][$id_order])) {
					$by_cust[$id_cust]['orders'][$id_order] = array();
				}
				$by_cust[$id_cust]['orders'][$id_order]['slips'][$id_slip] = $slip;
			}
			
			foreach ($op['payments'] as $id_payment => &$payment) {
				$payment['after'] = $this->getOrderPayment($id_payment);
				if (!$payment['before'] && !$payment['after']->id) continue;
				$ref = $payment['after']->id ? $payment['after']->order_reference : $payment['before']->order_reference;
				$id_cust = $db->getValue('
					SELECT id_customer FROM '._DB_PREFIX_.'orders
					WHERE reference = "'.pSQL($ref).'"');
				if (!isset($by_cust[$id_cust])) {
					$by_cust[$id_cust] = array(
						'orders' => array(),
						'slips' => array(),
						'payments' => array(),
					);
				}
				$by_cust[$id_cust]['payments'][$id_payment] = $payment;
			}
			
			// TEMP attach orphan payments to unique operation
			// TODO fix
			if (isset($by_cust[false])) {
				$unique = false;
				foreach ($by_cust as $id_cust => &$op) {
					if ($id_cust) {
						if (!$unique) {
							// Get the unique operation
							$unique = &$op;
						}
						else {
							// More than 1 operation => ignore
							$unique = false;
							break;
						}
					}
				}
				if ($unique) {
					foreach ($by_cust[false]['payments'] as $id_payment => &$p)
						$unique['payments'][$id_payment] = $p;
				}
				unset($by_cust[false]);
			}
		}

		foreach($by_cust as $id_cust => &$op) {
			
			$operation = array(
				'data' => array(
					'date' => date('Y-m-d H:i:s', $datetime),
					'type' => pSQL(_KERAWEN_525_OP_SALE_),
					'id_till' => $id_till,
					'till_name' => $till_name,
					'id_operator' => $operator ? $operator->id : null,
					'operator_name' => $operator ? ($operator->firstname.' '.$operator->lastname) : null,
					'KERAWEN_MODULE_VERSION' => $module->version,
				),
				'sales' => array(),
				'payments' => array(),
			);
		
			$customer = new Customer($id_cust);
			$count = 0;
		
			// Workaround for kerawen
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			// TEMP reference is ignored
			//if ($ref) updateOrderPaymentStatus($ref);
			
			foreach ($op['orders'] as $id_order => &$order) {
				// Gather order/slip details
				$sale = array(
					'details' => array(),
					'cartrules' => array(),
				);
				$gifts = array();
				$info = $this->getOrderInfo($id_order);
				
				// TODO dedicated loop on slips
				$id_order_slip = 0;
				$order['after'] = new Order($id_order);
				
				// TEMP reference is ignored
				if ($order['after']->reference) updateOrderPaymentStatus($order['after']->reference);
				
				$valid_after = $this->isOrderValid($order['after']->current_state);
				$valid_before = $order['before'] ? $this->isOrderValid($order['before']->current_state) : false;
	
				if (!$valid_after) {
					if (!$valid_before) {
						// Not an accountable operation
						continue;
					}
					if ($valid_before) {
						// Order has been canceled
						$sale['details'] = array_merge($sale['details'], $this->getOrderDetails($order['before'], $info, true));
					}
				}
				else {
					if (!$valid_before) {
						// Order has been validated
						$sale['details'] = array_merge($sale['details'], $this->getOrderDetails($order['after'], $info, false));
						
						if ($order['after']->total_discounts_tax_incl != 0) {
							$total_discounts_tax_incl = 0;
							$total_discounts_tax_excl = 0;
							$total_gifts_tax_incl = 0;
							$total_gifts_tax_excl = 0;

							$cartrules = $db->executeS('
								SELECT
									ocr.id_order AS ps_order,
									ocr.id_cart_rule AS ps_cart_rule,
									ocr.name AS label,
									cr.reduction_percent AS discount_percent,
									ocr.value AS tax_incl,
									ocr.value_tax_excl AS tax_excl,
									cr.gift_product AS gift_prod,
									cr.gift_product_attribute AS gift_attr
								FROM '._DB_PREFIX_.'order_cart_rule ocr
								LEFT JOIN '._DB_PREFIX_.'cart_rule cr ON cr.id_cart_rule = ocr.id_cart_rule
								WHERE ocr.id_order = '.pSQL($id_order)
							);
							
							foreach($cartrules as $i => &$cr) {
								if ($cr['gift_prod']) {
									$total_gifts_tax_excl += $cr['tax_excl'];
									$total_gifts_tax_incl += $cr['tax_incl'];

									$gifts[$cr['gift_prod'].'-'.$cr['gift_attr']] = array(
										'discount_te' => $cr['tax_excl'],
										'discount_ti' => $cr['tax_incl'],
									);
									unset($cartrules[$i]);
								}
								else {
									$total_discounts_tax_excl += $cr['tax_excl'];
									$total_discounts_tax_incl += $cr['tax_incl'];
								}
								unset($cr['gift_prod']);
								unset($cr['gift_attr']);
							}
							
 							$sale['cartrules'] = array_merge($sale['cartrules'], $cartrules);
 							
 							$info['total_discounts_tax_excl'] = $total_discounts_tax_excl;
 							$info['total_discounts_tax_incl'] = $total_discounts_tax_incl;
 							$info['total_products'] -= $total_gifts_tax_excl;
 							$info['total_products_wt'] -= $total_gifts_tax_incl;
 							
 							if ($info['total_products'] != 0.0) {
								$info['global_discount_te'] = ($info['total_discounts_tax_excl'] - $info['free_shipping']*$info['total_shipping_tax_excl'])/$info['total_products'];
								$info['global_discount_ti'] = ($info['total_discounts_tax_incl'] - $info['free_shipping']*$info['total_shipping_tax_incl'])/$info['total_products_wt'];
							}
						}
					}
					else if (isset($order['slips'])) {
						// Part of order have been returned
						$id_address = $this->getOrderTaxAddress($order['after']);
						
						foreach($order['slips'] as $id_slip => &$s) {
							$id_order_slip = $id_slip;
							
							$before = $s['before'];
							$after = new OrderSlip($id_slip);
							$after->details = OrderSlip::getOrdersSlipDetail($id_slip);
							// TODO handle change if before is not null ???
							
							// PS backward compatibility
							if (!isset($after->total_shipping_tax_excl)) {
								$o = new Order($after->id_order);
								$after->total_shipping_tax_incl = $after->shipping_cost_amount;
								$after->total_shipping_tax_excl = $after->shipping_cost_amount/(1 + $o->carrier_tax_rate/100);
							}
							
							foreach ($after->details as &$sd) {
								$id_order_detail = $sd['id_order_detail'];
								
								$order_detail = new OrderDetail($id_order_detail);
								$more = $db->getRow('
									SELECT * FROM '._DB_PREFIX_.'order_detail_kerawen
									WHERE id_order_detail = '.pSQL($id_order_detail));
								
								// PS backward compatibility
								if (!isset($sd['unit_price_tax_excl']))
									$sd['unit_price_tax_excl'] = $sd['amount_tax_excl']/$sd['product_quantity'];
								if (!isset($sd['unit_price_tax_incl']))
									$sd['unit_price_tax_incl'] = $sd['amount_tax_incl']/$sd['product_quantity'];
								if (!isset($order_detail->id_tax_rules_group))
									$order_detail->id_tax_rules_group = $db->getValue('
										SELECT id_tax_rules_group FROM '._DB_PREFIX_.'order_detail_kerawen
										WHERE id_order_detail = '.pSQL($order_detail->id));
								if (!isset($order_detail->original_wholesale_price))
									$order_detail->original_wholesale_price = 0.0;
								
								
								$prod = $this->getProductInfo($id_order_detail);
								
								$sale['details'][] = array(
									'id_order_detail' => $id_order_detail,
									'item_name' => $prod['item_name'],
									'version_name' => $prod['version_name'],
									'unit_te' => $sd['unit_price_tax_excl'],
									'unit_ti' => $sd['unit_price_tax_incl'],
									'id_tax' => $order_detail->id_tax_rules_group,
									'margin_vat' => $more['margin_vat'],
									'id_address' => $id_address,
									'quantity' => -$sd['product_quantity'],
									'discount_te' => 0.0,
									'discount_ti' => 0.0,
									'discount_percent' => 0.0,
									'discount_rate_te' => 0.0,
									'discount_rate_ti' => 0.0,
									'purchase_te' => $order_detail->purchase_supplier_price,
								);
							}
							
							if ($after->total_shipping_tax_excl != 0) {
								$carrier = $this->getCarrierInfo($order['after']->id_carrier, $id_shop);
							
								$sale['details'][] = array(
									'item_name' => $this->label_shipping,
									'version_name' => $carrier['name'],
									'id_carrier' => $carrier['id'],
									'unit_te' => $after->total_shipping_tax_excl,
									'unit_ti' => $after->total_shipping_tax_incl,
									'id_tax' => $carrier['id_tax'],
									'id_address' => $id_address,
									'quantity' => -1,
									'discount_te' => 0.0,
									'discount_ti' => 0.0,
									'discount_percent' => 0.0,
									'discount_rate_te' => 0.0,
									'discount_rate_ti' => 0.0,
									'purchase_te' => $after->total_shipping_tax_excl,
								);
							}
						}
					}
					else {
						// Order has been modified
						$id_address = $this->getOrderTaxAddress($order['after']);
						
						// Check products
						foreach ($order['items'] as $id_order_detail => &$detail) {
							$before = $detail['before'];
							$after = new OrderDetail($id_order_detail);
							if (!$after->id_order_detail) {
								$after->qty = 0;
								$after->unit_price_tax_excl = $before->unit_price_tax_excl;
								// FIXME
								$prod = array(
									'item_name' => 'item deleted',
									'version_name' => 'version deleted',
								);
							}
							else {
								$after->qty = $after->product_quantity - $after->product_quantity_refunded - $after->product_quantity_return;
								$prod = $this->getProductInfo($id_order_detail);
							}
							
							if ($before) {
								$before->qty = $before->product_quantity - $before->product_quantity_refunded - $before->product_quantity_return;
								
								// Change in quantity
								if ($after->qty != $before->qty) {
									$sale['details'][] = array(
										'id_order_detail' => $id_order_detail,
										'item_name' => $prod['item_name'],
										'version_name' => $prod['version_name'],
										'unit_te' => $before->unit_price_tax_excl,
										'unit_ti' => $before->unit_price_tax_incl,
										'id_tax' => $before->id_tax_rules_group,
										'id_address' => $id_address,
										'quantity' => $after->qty - $before->qty,
										'discount_te' => 0.0,
										'discount_ti' => 0.0,
										'discount_percent' => 0.0,
										'purchase_te' => $before->unit_price_tax_excl,
									);
								}
	
								// Change in price
								if ($after->unit_price_tax_excl != $before->unit_price_tax_excl) {
									$sale['details'][] = array(
										'id_order_detail' => $id_order_detail,
										'item_name' => $prod['item_name'],
										'version_name' => $prod['version_name'],
										'unit_te' => $after->unit_price_tax_excl - $before->unit_price_tax_excl,
										'unit_ti' => $after->unit_price_tax_incl - $before->unit_price_tax_incl,
										'id_tax' => $after->id_tax_rules_group,
										'id_address' => $id_address,
										'quantity' => $after->qty,
										'discount_te' => 0.0,
										'discount_ti' => 0.0,
										'discount_percent' => 0.0,
										'purchase_te' => $after->unit_price_tax_excl - $before->unit_price_tax_excl,
									);
								}
							}
							else {
								// New product line in order
								$sale['details'][] = array(
									'id_order_detail' => $id_order_detail,
									'item_name' => $prod['item_name'],
									'version_name' => $prod['version_name'],
									'unit_te' => $after->unit_price_tax_excl,
									'unit_ti' => $after->unit_price_tax_incl,
									'id_tax' => $after->id_tax_rules_group,
									'id_address' => $id_address,
									'quantity' => $after->qty,
									'discount_te' => $after->reduction_amount_tax_excl,
									'discount_ti' => $after->reduction_amount_tax_incl,
									'discount_percent' =>$after->reduction_percent,
									'purchase_te' => $after->unit_price_tax_excl,
								);
							}
						}
	
						// Check shipping & wrapping
						$after = $order['after'];
						$before = $order['before'];
	
						if (($after->total_shipping_tax_excl != $before->total_shipping_tax_excl) ||
							($after->total_shipping_tax_incl != $before->total_shipping_tax_incl)) {
							$carrier = $this->getCarrierInfo($order['after']->id_carrier, $id_shop);
							
							$sale['details'][] = array(
								'item_name' => $this->label_shipping,
								'version_name' => $carrier['name'],
								'id_carrier' => $carrier['id'],
								'unit_te' => $after->total_shipping_tax_excl - $before->total_shipping_tax_excl,
								'unit_ti' => $after->total_shipping_tax_incl - $before->total_shipping_tax_incl,
								'id_tax' => $carrier['id_tax'],
								'id_address' => $id_address,
								'quantity' => 1,
								'discount_te' => 0.0,
								'discount_ti' => 0.0,
								'discount_percent' => 0.0,
								'purchase_te' => $after->total_shipping_tax_excl - $before->total_shipping_tax_excl,
							);
						}
	
						if (($after->total_wrapping_tax_excl != $before->total_wrapping_tax_excl) ||
						($after->total_wrapping_tax_incl != $before->total_wrapping_tax_incl)) {
							$sale['details'][] = array(
								'item_name' => $this->label_wrapping,
								'wrapping' => 1,
								'unit_te' => $after->total_wrapping_tax_excl - $before->total_wrapping_tax_excl,
								'unit_ti' => $after->total_wrapping_tax_incl - $before->total_wrapping_tax_incl,
								'id_tax' => Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'),
								'id_address' => $id_address,
								'quantity' => 1,
								'discount_te' => 0.0,
								'discount_ti' => 0.0,
								'discount_percent' => 0.0,
								'purchase_te' => $after->total_wrapping_tax_excl - $before->total_wrapping_tax_excl,
							);
						}
					}
				}
				
				foreach ($sale['details'] as $index => &$detail) {
					if (isset($detail['id_prod'])) {
						// Apply gifts
						$buf = $detail['id_prod'].'-'.$detail['id_attr'];
						if (isset($gifts[$buf])) {
							$diff = $gifts[$buf]['discount_ti'] / $detail['quantity'];
							$detail['discount_ti'] += $diff;
							$detail['unit_ti'] -= $diff;
						}
						unset($detail['id_prod']);
						unset($detail['id_attr']);
						
						// Correct global discount without gifts
						$detail['discount_rate_te'] = $info['global_discount_te'];
						$detail['discount_rate_ti'] = $info['global_discount_ti'];
						
// 						// Transfer giftcards
// 						if ($detail['gift_card']) {
// 							unset($sale['details'][$index]);
// 							$operation['payments'][] = array(
// 								'id_order_payment' => 0,
// 								'ps_orders' => 'TODO',
// 								'id_currency' => 0,
// 								'currency' => 'TODO',
// 								'amount' => -$detail['unit_te']*$detail['quantity'],
// 								'mode' => 'Gift card',
// 							);
// 						}
					}
				}
				
				$sale['data'] = array_merge($info, array(
					'id_order' => $id_order,
					'id_order_slip' => $id_order_slip,
				));
				$operation['sales'][] = $sale;
			}
	
			// Gather payments
			foreach ($op['payments'] as $id_payment => &$p) {
				$count++;
				$before = $p['before'];
				$after = new OrderPayment($id_payment);
				if (!$before && !$after->id) continue; // Payment has been created and deleted
				
				// Ignore returned products used as payment
				$return = $db->getRow('
					SELECT
						op.reference,
						cf.id_payment_mode
					FROM '._DB_PREFIX_.'order_payment_kerawen op
					LEFT JOIN '._DB_PREFIX_.'cashdrawer_flow_kerawen cf ON cf.id_order_payment = op.id_order_payment
					WHERE op.id_order_payment = '.pSQL($id_payment));
				
				if ($return && $return['reference'] && $return['id_payment_mode'] != 6)
					continue;
				
				// Get orders covered by payment
				$orders = $db->executeS('
					SELECT id_order
					FROM '._DB_PREFIX_.'orders
					WHERE reference = "'.pSQL($after->order_reference).'"');
				$buf = array();
				foreach ($orders as $o) $buf[] = $o['id_order'];
				$orders = implode(',', $buf);
				
				if ($before) {
					if ($after->payment_method != $before->payment_method) {
						// Get previous logged payment mode (the last one)
						$old_mode = $db->getValue('
							SELECT id_mode
							FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment
							WHERE id_order_payment = '.pSQL($id_payment).'
							ORDER BY id_payment DESC');
						
						$operation['payments'][] = array(
							'id_order_payment' => $id_payment,
							'ps_orders' => $orders,
							'id_currency' => $before->id_currency,
							'currency' => $this->getCurrencyInfo($before->id_currency)->name,
							'amount' => -$before->amount,
							'mode' => $before->payment_method,
							'id_mode' => $old_mode,
							'deferred' => $p['deferred'],
						);
						$operation['payments'][] = array(
							'id_order_payment' => $id_payment,
							'ps_orders' => $orders,
							'id_currency' => $after->id_currency,
							'currency' => $this->getCurrencyInfo($after->id_currency)->name,
							'amount' => $after->amount,
							'mode' => $after->payment_method,
							'id_mode' => $p['id_mode'],
							'deferred' => $p['deferred'],
						);
					}
					else if ($after->amount != $before->amount) {
						$operation['payments'][] = array(
							'id_order_payment' => $id_payment,
							'ps_orders' => $orders,
							'id_currency' => $after->id_currency,
							'currency' => $this->getCurrencyInfo($after->id_currency)->name,
							'amount' => $after->amount - $before->amount,
							'mode' => $after->payment_method,
							'id_mode' => $p['id_mode'],
							'deferred' => $p['deferred'],
						);
					}
				}
				else {
					$operation['payments'][] = array(
						'id_order_payment' => $id_payment,
						'ps_orders' => $orders,
						'id_currency' => $after->id_currency,
						'currency' => $this->getCurrencyInfo($after->id_currency)->name,
						'amount' => $after->amount,
						'mode' => $after->payment_method,
						'id_mode' => $p['id_mode'],
						'deferred' => $p['deferred'],
					);
				}
			}
			// TODO Update payment status in replacement of module kerawen ??
		
			// Summarize sale
			$total_te = $total_ti = 0;
			$taxes = array();
			$nb_lines = 0;
	
			foreach ($operation['sales'] as &$s) {
				$round_type = $s['data']['round_type'];
				if (!$round_type) $round_type = _KERAWEN_RM_LINE_;
				
				$stotal_te = $stotal_ti = 0;
				$staxes = array();
				
				foreach ($s['details'] as &$detail) {
					$count++;
					$nb_lines++;

					
					if (!isset($detail['version_name']) || is_null($detail['version_name'])) $detail['version_name'] = '';
					if (!isset($detail['gift_card'])) $detail['gift_card'] = 0;
					if (!isset($detail['margin_vat'])) $detail['margin_vat'] = 0;
					if (!isset($detail['measure'])) $detail['measure'] = 1;
					
					if (isset($detail['id_address'])) {
						if (isset($detail['id_tax'])) {
							$info = $this->getTaxInfo($detail['id_tax'], $detail['id_address']);
							$detail['tax_name'] = $info['name'];
							$detail['tax_rate'] = $info['rate'];
						}
						unset($detail['id_address']);
					}
					$tax_coef = 1 + $detail['tax_rate']/100;
					
					// Adjust purchase price from stock if any
					if (isset($detail['stock_te'])) $detail['purchase_te'] = $detail['stock_te'];
					unset($detail['stock_te']);
						
					// In the following, consider prices tax included
					// in order to comply to specific tax rules (e.g. tax on margin)
				
					// Compute effective discount if percent
					if (isset($detail['discount_percent']) && $detail['discount_percent'] > 0.0) {
						$detail['discount_ti'] = $detail['unit_ti']*$detail['discount_percent']/(100.0 - $detail['discount_percent']);
					}
					
					// Avoid negative discounts
					if ($detail['discount_ti'] < 0.0) {
						$detail['discount_ti'] = 0.0;
					}
					$detail['discount_te'] = $detail['discount_ti']/$tax_coef;
					
					$detail['unit_ti'] = $detail['unit_ti'] + $detail['discount_ti'];
					if ($detail['margin_vat']) {
						$margin = $detail['unit_ti'] - $detail['purchase_te'];
						if ($margin < 0)
							$detail['unit_te'] = $detail['unit_ti'];
						else
							$detail['unit_te'] = $detail['purchase_te'] + $margin/$tax_coef;
					}
					else {
						$detail['unit_te'] = $detail['unit_ti']/$tax_coef;
					}
					
					if ($round_type == _KERAWEN_RM_ITEM_) {
						// Rounding is apply to final price
						$final_te = round($detail['unit_te'] - $detail['discount_te'], 2);
						$final_ti = round($detail['unit_ti'] - $detail['discount_ti'], 2);
						
						$detail['unit_te'] = round($detail['unit_te'], 2);
						$detail['unit_ti'] = round($detail['unit_ti'], 2);
						$detail['discount_te'] = $detail['unit_te'] - $final_te;
						$detail['discount_ti'] = $detail['unit_ti'] - $final_ti;
					}
					
					// Apply global discount, according to taxes
					$detail['discount_rate'] = isset($detail['discount_rate_ti']) ? $detail['discount_rate_ti'] : 0;
					unset($detail['discount_rate_te']);
					unset($detail['discount_rate_ti']);
						
					$detail['total_te'] = ($detail['unit_te'] - $detail['discount_te'])*(1 - $detail['discount_rate'])*$detail['quantity'];
					if ($round_type == _KERAWEN_RM_LINE_) $detail['total_te'] = round($detail['total_te'], 2);
					$detail['total_ti'] = round(($detail['unit_ti'] - $detail['discount_ti'])*(1 - $detail['discount_rate'])*$detail['quantity'], 2);
					
					// Get actual unit prices
					$detail['unit_te'] = $detail['unit_te']/$detail['measure'];
					$detail['unit_ti'] = $detail['unit_ti']/$detail['measure'];
					$detail['discount_te'] = $detail['discount_te']/$detail['measure'];
					$detail['discount_ti'] = $detail['discount_ti']/$detail['measure'];
					
					if (!$detail['gift_card']) {
						$stotal_te += $detail['total_te'];
						$stotal_ti += $detail['total_ti'];
						
						if (true /*$detail['tax_rate'] != 0*/) {
							if (!isset($staxes[$detail['id_tax']])) {
								$staxes[$detail['id_tax']] = array(
									'id_tax' => $detail['id_tax'],
									'tax_name' => $info['name'],
									'tax_rate' => $info['rate'],
									'total_te' => 0.0,
									'total_ti' => 0.0,
									'tax_amount' => 0.0,
								);
							}
							$tax = &$staxes[$detail['id_tax']];
							$tax['total_te'] += $detail['total_te'];
							$tax['total_ti'] += $detail['total_ti'];
							$tax['tax_amount'] = $tax['total_ti'] - $tax['total_te'];
							
							if (false /*$detail['margin_vat']*/) {
								$outoftax = $detail['purchase_te']*$detail['quantity'];
								$tax['total_te'] -= $outoftax;
								$tax['total_ti'] -= $outoftax;
								
								if (!isset($staxes[0])) {
									$staxes[0] = array(
										'id_tax' => 0,
										'tax_name' => null,
										'tax_rate' => 0.0,
										'total_te' => 0.0,
										'total_ti' => 0.0,
										'tax_amount' => 0.0,
									);
								}
								$staxes[0]['total_te'] += $outoftax;
								$staxes[0]['total_ti'] += $outoftax;
							}
						}
					}
				}
				
				$s['total_te'] = $stotal_te;
				$s['total_ti'] = $stotal_ti;
				$s['taxes'] = $staxes;
				
				$total_te += $stotal_te;
				$total_ti += $stotal_ti;
				
				foreach ($staxes as &$t) {
					if ($round_type == _KERAWEN_RM_TOTAL_) $t['total_te'] = round($t['total_te'], 2);
					$t['tax_amount'] = $t['total_ti'] - $t['total_te'];
					
					if (!isset($taxes[$t['id_tax']])) {
						$taxes[$t['id_tax']] = array(
								'id_tax' => $t['id_tax'],
								'tax_name' => $t['tax_name'],
								'tax_rate' => $t['tax_rate'],
								'total_te' => 0.0,
								'total_ti' => 0.0,
								'tax_amount' => 0.0,
						);
					}
					$tax = &$taxes[$t['id_tax']];
					$tax['total_te'] += $t['total_te'];
					$tax['total_ti'] += $t['total_ti'];
					$tax['tax_amount'] += $t['tax_amount'];
				}
			}
				
			$sale = array(
				'id_customer' => $customer ? $customer->id : null,
				'customer_name' => $customer ? ($customer->firstname.' '.$customer->lastname) : null,
				'nb_lines' => $nb_lines,
				'total_te' => $total_te,
				'total_ti' => $total_ti,
			);
			
			// --------------------------------
			// 2. Sign & save (TO BE REDUNDED ON SECURE SERVER)
	
			if ($count) {
				// Store shop infos
				$operation['data']['id_shop'] = $id_shop;
				$shop_fields = array(
						'PS_SHOP_NAME' => 'PS_SHOP_NAME',
						'PS_SHOP_EMAIL' => 'PS_SHOP_EMAIL',
						'PS_SHOP_ADDR1' => 'PS_SHOP_ADDR1',
						'PS_SHOP_ADDR2' => 'PS_SHOP_ADDR2',
						'PS_SHOP_CODE' => 'PS_SHOP_CODE',
						'PS_SHOP_CITY' => 'PS_SHOP_CITY',
						'PS_SHOP_COUNTRY' => 'PS_SHOP_COUNTRY',
						'PS_SHOP_PHONE' => 'PS_SHOP_PHONE',
						'KERAWEN_SHOP_SIRET' => 'KERAWEN_SHOP_SIRET',
						'KERAWEN_SHOP_NAF' => 'KERAWEN_SHOP_NAF',
						'KERAWEN_SHOP_TVA_INTRA' => 'KERAWEN_SHOP_TVA_INTRA',
						'KERAWEN_SHOP_URL' => 'KERAWEN_SHOP_URL',
				);
				foreach ($shop_fields as $name => $field) {
					$operation['data'][$field] = Configuration::get($name, null, null, $id_shop);
				}
				
				$db->insert(_KERAWEN_525_PREFIX_.'operation', array_map('pSQL', $operation['data']));
				$id_operation = $db->Insert_ID();
				
				// Save sale
				$sale['id_operation'] = $id_operation;
				$sale['nb_prints'] = 1;
	
				$db->insert(_KERAWEN_525_PREFIX_.'sale', array_map('pSQL', $sale));
				$id_sale = $db->Insert_ID();
				
				foreach ($operation['sales'] as &$s) {
					if (count($s['details'])) {
						$db->insert(_KERAWEN_525_PREFIX_.'order', array(
							'id_sale' => $id_sale,
							'ps_order' => $s['data']['id_order'],
							'ps_slip' => $s['data']['id_order_slip'],
							'total_te' => $s['total_te'],
							'total_ti' => $s['total_ti'],
						), true);
						$id_order = $db->Insert_ID();
						
						foreach ($s['details'] as &$detail) {
							$detail['id_sale'] = $id_sale;
							$detail['id_order'] = $id_order;
							$db->insert(_KERAWEN_525_PREFIX_.'sale_detail', array_map('pSQL', $detail), false);
							$id_sale_detail = $db->Insert_ID();
						}
						
						if (count($s['taxes'])) {
							foreach ($s['taxes'] as &$t) {
								$t['id_order'] = $id_order;
								$t['tax_name'] = pSQL($t['tax_name']);
							}
							$db->insert(_KERAWEN_525_PREFIX_.'order_tax', $s['taxes'], true);
						}
					}
					
					if (count($s['cartrules'])) {
						foreach ($s['cartrules'] as &$cartrule) {
							$cartrule['id_sale'] = $id_sale;
							$cartrule['id_order'] = $id_order;
							$cartrule['label'] = pSQL($cartrule['label']);
						}
						$db->insert(_KERAWEN_525_PREFIX_.'discount', $s['cartrules'], true);
					}
	
				}
				
				if (count($taxes)) {
					foreach ($taxes as &$tax) {
						$tax['id_sale'] = $id_sale;
						$tax['tax_name'] = pSQL($tax['tax_name']);
					}
					$db->insert(_KERAWEN_525_PREFIX_.'sale_tax', $taxes, true);
				}
				
				// Signature
				$data = array();
				foreach ($taxes as $id_tax => &$tax) {
					$data[$this->formatAmount($tax['tax_rate'])] = $this->formatAmount($tax['total_ti']);
				}
				$data = array(
					$data,
					$this->formatAmount($sale['total_ti']),
					$this->formatDate($operation['data']['date']),
					$id_sale,
					_KERAWEN_525_OP_SALE_);
				$sign = $this->sign(_KERAWEN_525_OP_SALE_, $data);
				$db->update(_KERAWEN_525_PREFIX_.'sale', array(
					'description' => $sign['desc'],
					'signature' => $sign['sign'],
					'sign_short' => $sign['short'],
				), 'id_sale = '.pSQL($id_sale));
					
				// Grand totals
				$this->updateGrandTotal($datetime, $id_sale, $sale, $taxes);

				// Payments
				foreach ($operation['payments'] as &$p) {
					$p['id_operation'] = $id_operation;
				}
				if (count($operation['payments'])) {
					foreach ($operation['payments'] as &$p) {
						$p['mode'] = pSQL($p['mode']);
						$p['currency'] = pSQL($p['currency']);
					}
					$db->insert(_KERAWEN_525_PREFIX_.'payment', $operation['payments'], true);
				}
			}
		}
		$this->getDbLink()->commit();
	}

	public function cancelOperation($id_op) {
		$this->getDbLink()->beginTransaction();
		$db = Db::getInstance();
	
		$db->execute('
			UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
			SET canceled = 1 WHERE id_operation = '.pSQL($id_op));
	
		// Update grand totals with opposite amounts
		$datetime = time();
		$sales = $db->executeS('
			SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale
			WHERE id_operation = '.pSQL($id_op));
		
		foreach($sales as &$sale) {
			$id_sale = $sale['id_sale'];
			$sale['total_te'] = -$sale['total_te'];
			$sale['total_ti'] = -$sale['total_ti'];
			
			$taxes = $db->executeS('
				SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax
				WHERE id_sale = '.pSQL($id_sale));
			
			foreach($taxes as &$tax) {
				$tax['total_te'] = -$tax['total_te'];
				$tax['total_ti'] = -$tax['total_ti'];
				$tax['tax_amount'] = -$tax['tax_amount'];
			}
			
			$this->updateGrandTotal($datetime, '-'.$id_sale, $sale, $taxes);
		}
		
		$this->logEvent(_KERAWEN_525_EVT_CANCEL_OP_, 'Cancelation', 'operation #'.$id_op);
		$this->getDbLink()->commit();
	}
	
	protected function logEvent($code, $label, $info,
		$date = false, $id_operator = false, $sign = true) {
		$db = Db::getInstance();
		
		if (!$date) {
			$date = date('Y-m-d H:i:s', time());
		}
		if (!$id_operator) {
			$context = Context::getContext();
			$operator = $context->employee;
			$id_operator = $operator ? $operator->id : null;
		}
		
		$event = array(
			'date' => $date,
			'id_operator' => $id_operator,
			'code' => $code,
			'label' => pSQL($label),
			'info' => pSQL($info),
		);
		
		$db->insert(_KERAWEN_525_PREFIX_.'event', $event, true);
		$id_event = $db->Insert_ID();
		
		if ($sign) {
			$data = array(
				$id_event,
				$event['code'],
				$event['label'],
				$this->formatDate($event['date']),
				$event['id_operator'],
				0, //$event['id_till'],
			);
			
			$sign = $this->sign(_KERAWEN_525_OP_EVENT_, $data);
			$db->update(_KERAWEN_525_PREFIX_.'event', array(
				'description' => $sign['desc'],
				'signature' => $sign['sign'],
				'sign_short' => $sign['short'],
			), 'id_event = '.pSQL($id_event));
		}
	}


	/* **************************************************************
	 * GRAND TOTAL
	*/
	
	protected function updateGrandTotal($datetime, $id_sale, $sale, $taxes) {
		$db = Db::getInstance();

		$period_ref = array(
			_KERAWEN_525_PER_DAY_ => date('Y-m-d', $datetime),
			_KERAWEN_525_PER_MONTH_ => date('Y-m', $datetime),
			_KERAWEN_525_PER_YEAR_ => date('Y', $datetime),
		);
		$date = date('Y-m-d H:i:s', $datetime);
		
		// Update perpetual total
		$id_gtotal = $db->getValue('
					SELECT id_gtotal FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
					WHERE period_type = "'._KERAWEN_525_PER_PERPETUAL_.'"');
		if (!$id_gtotal) {
			$db->insert(_KERAWEN_525_PREFIX_.'gtotal', array(
				'period_type' => _KERAWEN_525_PER_PERPETUAL_,
				'period_ref' => '',
			));
			$id_gtotal = $db->Insert_ID();
		}
		$db->execute('
					UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal SET
						total_te = total_te + '.pSQL(abs($sale['total_te'])).',
						total_ti = total_ti + '.pSQL(abs($sale['total_ti'])).',
						date = "'.pSQL($date).'"
					WHERE id_gtotal = '.pSQL($id_gtotal));
		$perpetual = $db->getRow('
					SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
					WHERE id_gtotal = '.pSQL($id_gtotal));
			
		// Update grand totals for periods
		foreach ($period_ref as $type => $ref) {
			$id_gtotal = $db->getValue('
						SELECT id_gtotal FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
						WHERE period_type = "'.pSQL($type).'"
						AND period_ref = "'.pSQL($ref).'"');
			if (!$id_gtotal) {
				$db->insert(_KERAWEN_525_PREFIX_.'gtotal', array(
					'period_type' => $type,
					'period_ref' => $ref,
				));
				$id_gtotal = $db->Insert_ID();
			}
			$db->execute('
						UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal SET
							total_te = total_te + '.pSQL($sale['total_te']).',
							total_ti = total_ti + '.pSQL($sale['total_ti']).',
							perp_te = '.pSQL($perpetual['total_te']).',
							perp_ti = '.pSQL($perpetual['total_ti']).',
							date = "'.pSQL($date).'"
						WHERE id_gtotal = '.pSQL($id_gtotal));
				
			foreach ($taxes as &$tax) {
				$db->execute('
							INSERT INTO '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal_tax
								(id_gtotal, id_tax, tax_name, tax_rate, total_te, total_ti, tax_amount)
							VALUES (
								'.pSQL($id_gtotal).',
								'.pSQL($tax['id_tax']).',
								"'.pSQL($tax['tax_name']).'",
								'.pSQL($tax['tax_rate']).',
								'.pSQL($tax['total_te']).',
								'.pSQL($tax['total_ti']).',
								'.pSQL($tax['tax_amount']).')
							ON DUPLICATE KEY UPDATE
								total_te = total_te + VALUES(total_te),
								total_ti = total_ti + VALUES(total_ti),
								tax_amount = tax_amount + VALUES(tax_amount)');
			}
		}
	
		// Save sale grand total
		$db->insert(_KERAWEN_525_PREFIX_.'gtotal', array(
			'period_type' => _KERAWEN_525_PER_SALE_,
			'period_ref' => $id_sale,
			'total_te' => $sale['total_te'],
			'total_ti' => $sale['total_ti'],
			'perp_te' => $perpetual['total_te'],
			'perp_ti' => $perpetual['total_ti'],
			'date' => pSQL($date),
		), true);
		$id_gtotal = $db->Insert_ID();
		$this->signGrandTotal($id_gtotal);
	}

	protected function closeGrandTotal($datetime) {
		$this->getDbLink()->beginTransaction();
		$db = Db::getInstance();
		
		$period_ref = array(
			_KERAWEN_525_PER_DAY_ => date('Y-m-d', $datetime),
			_KERAWEN_525_PER_MONTH_ => date('Y-m', $datetime),
			_KERAWEN_525_PER_YEAR_ => date('Y', $datetime),
		);
		
		$gtotals = $db->executeS('
			SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
			WHERE period_type != "'._KERAWEN_525_PER_PERPETUAL_.'"
			AND signature IS NULL
			ORDER BY id_gtotal DESC');
		foreach ($gtotals as $gt) {
			if ($gt['period_ref'] < $period_ref[$gt['period_type']]) {
				$this->signGrandTotal($gt['id_gtotal']);
				$this->logEvent(
					_KERAWEN_525_EVT_CLOSE_PERIOD_,
					'Close period',
					'#'.$gt['id_gtotal'].': '.$gt['period_ref'],
					date('Y-m-d H:i:s', $datetime));
			}
		}
		$this->getDbLink()->commit();
	}
	
	protected function signGrandTotal($id_gtotal) {
		$db = Db::getInstance();
		
		$gtotal = $db->getRow('
			SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
			WHERE id_gtotal = '.pSQL($id_gtotal));
		if ($gtotal['period_type'] == _KERAWEN_525_PER_SALE_) {
			$taxes = $db->executeS('
				SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax
				WHERE id_sale = '.pSQL($gtotal['period_ref']));
		}
		else {
			$taxes = $db->executeS('
				SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal_tax
				WHERE id_gtotal = '.pSQL($id_gtotal));
		}
		
		$data = array();
		foreach ($taxes as $id_tax => &$tax) {
			$data[$this->formatAmount($tax['tax_rate'])] = $this->formatAmount($tax['total_ti']);
		}
		$data = array(
			$data,
			$this->formatAmount($gtotal['total_ti']),
			$this->formatDate($gtotal['date']),
			$gtotal['period_ref'],
		);

		$sign = $this->sign(_KERAWEN_525_OP_GTOTAL_, $data);
		$db->update(_KERAWEN_525_PREFIX_.'gtotal', array(
			'description' => $sign['desc'],
			'signature' => $sign['sign'],
			'sign_short' => $sign['short'],
		), 'id_gtotal = '.pSQL($id_gtotal));
	}
	
	
	/* **************************************************************
	 * RECEIPTS
	*/
	
	public function getReceiptData($orders, $order_slips, $original, $prices, $isInvoice = false) {
		
		$this->getDbLink()->beginTransaction();
		$db = Db::getInstance();
		
		$datetime = time();
		$context = Context::getContext();
		$operator = $context->employee;
		
		$order_cond = count($orders) ? 'o.ps_order IN ('.implode(',', $orders).') AND o.ps_slip = 0' : 'FALSE';
		$return_cond = count($order_slips) ? 'o.ps_slip IN ('.implode(',', $order_slips).')' : 'FALSE';
		$res = $db->executeS('
			SELECT DISTINCT
				s.*,
				op.*,
				(s.total_ti - s.total_te) AS tax_amount,
				s.total_ti AS total_full
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = s.id_operation
			WHERE s.id_sale IN (
				SELECT o.id_sale FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order o
				WHERE '.$order_cond.' OR '.$return_cond.'
			)');
		
		foreach($res as &$s) {
			$more = $db->getRow('
				SELECT
					ko.ps_order AS ps_order,
					ko.ps_slip AS ps_slip,
					o.reference AS reference,
					o.id_customer AS id_customer,
					IF (o.payment = "KerAwen", "", o.payment) as payment, 
					ok.invoice_note AS note
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s
				LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order ko ON ko.id_sale = s.id_sale AND ko.ps_slip = 0
				LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = ko.ps_order
				LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = ko.ps_order
				WHERE s.id_sale = '.pSQL($s['id_sale']));
			$s = array_merge($s, $more);
			
			// Check for duplicate
			if ($prices && (!$original || $s['nb_prints'] > 1)) {
				
				$duplicate = array(
					'id_sale' => $s['id_sale'],
					'nb_duplicate' => $s['nb_prints'],
					'id_operator' => $operator ? $operator->id : 0,
					'date' => date('Y-m-d H:i:s', $datetime),
				);
				$db->insert(_KERAWEN_525_PREFIX_.'duplicate', $duplicate, true);
				$id_duplicate = $db->Insert_ID();
				
				$sign = $this->sign(_KERAWEN_525_OP_DUPLICATE_, array(
					$id_duplicate,
					_KERAWEN_525_OP_DUPLICATE_,
					$duplicate['nb_duplicate'],
					$duplicate['id_operator'],
					$this->formatDate($duplicate['date']),
					$duplicate['id_sale'],
				));
				$db->update(_KERAWEN_525_PREFIX_.'duplicate', array(
					'description' => $sign['desc'],
					'signature' => $sign['sign'],
					'sign_short' => $sign['short'],
				), 'id_duplicate = '.pSQL($id_duplicate));
				
				$s['nb_prints']++;
				$db->update(_KERAWEN_525_PREFIX_.'sale', array(
					'nb_prints' => $s['nb_prints'],
				), 'id_sale = '.pSQL($s['id_sale']));

				$s['nb_duplicate'] = $duplicate['nb_duplicate'];
				// TODO Which signature on duplicata ???
				//$s['sign_short'] = $sign['short'];
			}
			
			$this->completeDetails($s, true);
			$this->completeCartRule($s);
			$this->completeMessages($s);
		}
		$this->getDbLink()->commit();
		
		return $res;
	}

	protected function completeMessages(&$s) {
		$s['messages'] = array();
		if ((int) $s['ps_order'] > 0 && (int) $s['ps_slip'] == 0) {
			$msgs = CustomerThread::getCustomerMessages($s['id_customer'], null, $s['ps_order']);
			foreach ($msgs as $msg) {
				$s['messages'][] = array(
					'priv' => (boolean) $msg['private'],
					'id_empl' => (int) $msg['id_employee'],
					'text' => $msg['message'],
					'id' => (int) $msg['id_customer_message'],
				);
			}
		}
	}

	protected function completeCartRule(&$s) {
		$db = Db::getInstance();
		$s['cart_rule'] = $db->executeS('
			SELECT 
				label AS item_name,
				discount_percent AS discount_percent, 
				-tax_incl AS unit_ti, 
				-tax_excl AS unit_te,
				-tax_incl AS total_ti, 
				-tax_excl AS total_te
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'discount
			WHERE id_sale = '.pSQL($s['id_sale'])
		);
	}
	
	protected function idTaxToId($id_tax) {
		$list = array('', 'A','B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S' , 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		return $list[(int) ($id_tax % count($list))];
	}
	
	protected function completeDetails(&$s, $isSale) {
		$db = Db::getInstance();
		
		$version = $db->getValue('
			SELECT op.KERAWEN_MODULE_VERSION as version
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = s.id_operation
			WHERE s.id_sale = '.pSQL($s['id_sale']));
		$cond = $isSale
			? 'sd.id_sale = '.pSQL($s['id_sale'])
			: 'sd.id_order = '.pSQL($s['id_order']);
		
		$sql = '
			SELECT
				sd.*,
				od.product_reference AS ref,
				odk.note
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sd
			LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sd.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk ON odk.id_order_detail = od.id_order_detail
			WHERE '.$cond;
		if (Tools::version_compare($version, '2.1', '<')) {
			$sql = '
				SELECT
					sd.*,
					sdd.*, 
					od.product_reference AS ref,
					odk.note
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sd
				LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail_discount sdd ON sdd.id_sale_detail = sd.id_sale_detail
				LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sd.id_order_detail
				LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk ON odk.id_order_detail = od.id_order_detail
				WHERE '.$cond;
		}
		$detail = $db->executeS($sql);
		
		$s['detail'] = $detail;
		
		$s['order'] = array();
		$s['slip'] = array();
		
		$s['carrier'] = array();
		$s['order_total_ti'] = 0;
		$s['order_total_te'] = 0;
		$s['slip_total_ti'] = 0;
		$s['slip_total_te'] = 0;
		$s['carrier_total_ti'] = 0;
		$s['carrier_total_te'] = 0;
		$s['ecotax_total_ti'] = 0;
		$s['ecotax_total_te'] = 0;
		
		$taxlist = $db->executeS('
			SELECT id_tax, ps_tax
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'tax'
		);
		$taxesArray = array();
		foreach($taxlist as $tax) {
			$taxesArray[(int) $tax['ps_tax']] = $tax['id_tax'];
		}
		
		// TODO VAT MARGIN ?
		$taxesDiffArray = array();
		$vat_margin_tax_symbol = '*';
		// TODO VAT MARGIN
		
		// Get customizations
		$customs = array();
		if ($s['ps_order']) {
			$order = new Order($s['ps_order']);
			$cf_cond = Tools::version_compare(_PS_VERSION_, '1.6.0.12', '>=')
				? 'AND cf.id_shop = '.pSQL($order->id_shop) : '';
			
			$buf = $db->executeS('
				SELECT od.*, c.*, cd.*, cf.*
				FROM '._DB_PREFIX_.'order_detail od
				JOIN '._DB_PREFIX_.'customization c
					ON c.id_product = od.product_id
					AND c.id_product_attribute = od.product_attribute_id
				JOIN '._DB_PREFIX_.'customized_data cd
					ON cd.id_customization = c.id_customization
				JOIN '._DB_PREFIX_.'customization_field_lang cf
					ON cf.id_customization_field = cd.index
					AND cf.id_lang = '.pSQL($order->id_lang).'
					'.$cf_cond.'
				WHERE od.id_order = '.pSQL($order->id).'
					AND c.id_cart = '.pSQL($order->id_cart));
			foreach($buf as $cd) {
				if (!isset($customs[$cd['id_order_detail']])) $customs[$cd['id_order_detail']] = array();
				$c = &$customs[$cd['id_order_detail']];
				$id_c = $cd['id_customization'];
				if (!isset($c[$id_c])) $c[$id_c] = array(
					'quantity' => $cd['quantity'],
					'data' => array(),
				);
				$c[$id_c]['data'][$cd['index']] = array(
					'name' => $cd['name'],
					'value' => $cd['value'],
				);
			}
		}
		
		foreach($detail as $k => $item) {
			if (!isset($taxesArray[(int) $item['id_tax']])) {
				$db->insert(_KERAWEN_525_PREFIX_.'tax', array('ps_tax' => $item['id_tax']) );
				$taxesArray[(int) $item['id_tax']] = $db->Insert_ID();
			}
			$item['id_tax'] = $this->idTaxToId($taxesArray[(int) $item['id_tax']]);
			
			$s['ecotax_total_ti'] += (float) $item['ecotax_ti'] * (int) $item['quantity'];
			$s['ecotax_total_te'] += (float) $item['ecotax_te'] * (int) $item['quantity'];
			
			$updatedItem = $item;
			
			if (isset($customs[$item['id_order_detail']])) {
				$updatedItem['customs'] = $customs[$item['id_order_detail']];
			}
			
			// Backward compatibility
			if (Tools::version_compare($version, '2.1', '<')) {
				$m = $updatedItem['measure'];
				$qty = $updatedItem['quantity'];
				
				if ($m) {
					$updatedItem['total_ti'] = $updatedItem['total_ti'] - $updatedItem['product_discount_ti'];
					$updatedItem['total_te'] = $updatedItem['total_te'] - $updatedItem['product_discount_te'];
				}
				
				$updatedItem['unit_ti'] = $item['product_unit_ti'];
				$updatedItem['unit_te'] = $item['product_unit_te'];
				$updatedItem['discount_ti'] = $updatedItem['product_discount_ti']/$qty;
				$updatedItem['discount_te'] = $updatedItem['product_discount_te']/$qty;
				
				if ($updatedItem['product_discount_type'] == "percent") {
					$updatedItem['unit_ti'] = $updatedItem['unit_ti'] + $updatedItem['discount_ti'];
					$updatedItem['unit_te'] = $updatedItem['unit_te'] + $updatedItem['discount_te'];
				}
				
				if ($m) {
					$updatedItem['unit_ti'] = $updatedItem['unit_ti']/$m;
					$updatedItem['unit_te'] = $updatedItem['unit_te']/$m;
					$updatedItem['discount_ti'] = $updatedItem['discount_ti']/$m;
					$updatedItem['discount_te'] = $updatedItem['discount_te']/$m;
				}
			}
				
			// TODO: VAT MARGIN
			if ((int) $item['margin_vat']) {
				//diff taxes
				if (empty($taxesDiffArray[$item['id_tax']])) {
					$taxesDiffArray[$item['id_tax']] = array(
						'total_te' => 0,
						'total_ti' => 0,
						'tax_amount' => 0,
					);
				}
				$taxesDiffArray[$item['id_tax']]['total_te'] += $updatedItem['total_te'];
				$taxesDiffArray[$item['id_tax']]['total_ti'] += $updatedItem['total_ti'];
				$taxesDiffArray[$item['id_tax']]['tax_amount'] += $updatedItem['total_ti'] - $updatedItem['total_te'];
				
				$updatedItem['total_te'] = $updatedItem['total_ti'];
				$updatedItem['unit_te'] = $updatedItem['unit_ti'];
				
				$updatedItem['id_tax'] = $vat_margin_tax_symbol;
			}
			// TODO: VAT MARGIN
			
			if (+$item['quantity'] > 0) {
				if (is_null($item['id_carrier'])) {
					$s['order'][] = $updatedItem;
					$s['order_total_ti'] += (float) $item['total_ti'];
					$s['order_total_te'] += (float) $item['total_te'];
				} else {
					$s['carrier'][] = $updatedItem;
					$s['carrier_total_ti'] += (float) $item['total_ti'];
					$s['carrier_total_te'] += (float) $item['total_te'];
				}
			} else {
				$s['slip'][] = $updatedItem;
				$s['slip_total_ti'] += (float) $item['total_ti'];
				$s['slip_total_te'] += (float) $item['total_te'];
			}
		}

		if ($isSale) {
			$s['taxes'] = $db->executeS('
				SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax
				WHERE id_sale = '.pSQL($s['id_sale']).'
				ORDER BY id_tax ASC');
		} else {
			$s['taxes'] = $db->executeS('
				SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order_tax
				WHERE id_order = '.pSQL($s['id_order']));
		}
		
		foreach($s['taxes'] as $k => $t) {
			//shoudn't be possible
			if (!isset($taxesArray[(int) $t['id_tax']])) {
				$db->insert(_KERAWEN_525_PREFIX_.'tax', array('ps_tax' => $t['id_tax']) );
				$taxesArray[(int) $t['id_tax']] = $db->Insert_ID();
			}
			
			$s['taxes'][$k]['id_tax'] = $this->idTaxToId($taxesArray[(int) $t['id_tax']]);
			
			//VAT margin reverse
			if (isset($taxesDiffArray[ $s['taxes'][$k]['id_tax'] ])) {
				$s['taxes'][$k]['total_te'] -= $taxesDiffArray[ $s['taxes'][$k]['id_tax'] ]['total_te'];
				$s['taxes'][$k]['total_ti'] -= $taxesDiffArray[ $s['taxes'][$k]['id_tax'] ]['total_ti'];
				$s['taxes'][$k]['tax_amount'] -= $taxesDiffArray[ $s['taxes'][$k]['id_tax'] ]['tax_amount'];	
				
				//Fully vat margin by rate
				if ($s['taxes'][$k]['total_te'] == 0 && $s['taxes'][$k]['total_ti'] == 0 && $s['taxes'][$k]['tax_amount'] == 0) {
					unset($s['taxes'][$k]);
				}
			}
		}
		
		$total_margin_vat_ti = 0;
		$total_margin_vat_te = 0;
		$total_margin_vat_tax = 0;
		
		//VAT margin total
		if (count($taxesDiffArray)) {
			foreach($taxesDiffArray as $row) {
				$total_margin_vat_ti += $row['total_ti'];
				$total_margin_vat_te += $row['total_te'];
				$total_margin_vat_tax += $row['tax_amount'];
			}
			$s['taxes'][] = array(
				'id' => '',
				'id_tax' => $vat_margin_tax_symbol,
				'tax_amount' => '-',
				'tax_name' => 'vat margin',
				'tax_rate' => '-',
				'total_te' => $total_margin_vat_ti,
				'total_ti' => $total_margin_vat_ti,
			);
			
			$s['tax_amount'] -= $total_margin_vat_tax;
			$s['total_te'] += $total_margin_vat_tax;
		}
		
		$s['payments'] = $db->executeS('
			SELECT op.*, op.payment_method AS mode
			FROM '._DB_PREFIX_.'order_payment op
			WHERE op.order_reference = "' . $s['reference'] . '" AND "' . $s['reference'] . '" != ""
			ORDER BY op.id_order_payment ASC'
		);
		foreach($s['payments'] as &$p) {
			$deferred = $db->getValue('
				SELECT date_deferred
				FROM '._DB_PREFIX_.'cashdrawer_flow_kerawen
				WHERE id_order_payment = '.pSQL($p['id_order_payment']).'
					AND date_deferred IS NOT NULL
				ORDER BY id_cashdrawer_flow DESC');
			if ($deferred) $p['date_add'] = $deferred;
		}
		
		$s['total_paid'] = 0;
		if ($s['payments']) {
			foreach($s['payments'] as $k => $payment) {
				$s['total_paid'] += (float) $payment['amount'];
			}
		}

		$s['remain'] = (float) $s['total_full'] - (float) $s['total_paid'];
		unset($s['total_full']);

		if ($isSale) {
			$s['returns'] = array();
		} else {
			$s['returns'] = $db->executeS('
				SELECT -ksd2.total_ti AS amount
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail ksd
				LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail ksd2 ON ksd.id_sale = ksd2.id_sale AND ksd2.quantity < 0
				WHERE ksd2.total_ti IS NOT NULL AND ksd.id_order = ' .pSQL($s['id_order'])
			);
		}
	}
	
	
	
	/* **************************************************************
	 * INVOICES
	*/
	
	public function getInvoiceData($ps_order, $ps_slip, $check = true) {
		$db = Db::getInstance();
		
		$i = $db->getRow('
			SELECT
				o.*,
				op.*,
				os.reference,
				o.date AS invoice_date,
				op.date AS receipt_date,
				os.id_cart,
				IF (os.payment = "KerAwen" ,"" ,os.payment ) as payment,
				(o.total_ti - o.total_te) AS tax_amount,
				ok.invoice_note AS note
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order o
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s ON s.id_sale = o.id_sale
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = s.id_operation
			INNER JOIN '._DB_PREFIX_.'orders os ON os.id_order = o.ps_order
			LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = os.id_order
			WHERE o.ps_order = '.pSQL($ps_order).'
			AND o.ps_slip = '.pSQL($ps_slip)
		);
		
		// Old invoice, not certified
		if (!$i) return false;

		// Required to get left to paid
		// ??? Returns are counted twice in that case
		// $i['total_full'] = $db->getValue('SELECT SUM(total_ti) FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order WHERE id_sale = '.pSQL($i['id_sale']));
		$i['total_full'] = $i['total_ti'];
		
		$this->completeDetails($i, false);
		$this->completeCartRule($i);
		
		// Force PS invoice generation
		if ($check && !isset($i['number'])) {
			if ($ps_slip) {
				// Force initial invoice
				$this->getInvoiceData($ps_order, 0);
			}
			$this->completeInvoice($i);
			return $this->getInvoiceData($ps_order, $ps_slip, false);
		}
		
		// Finalize
		$i['invoice'] = $db->getRow('
			SELECT * FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'invoice
			WHERE id_order = '.pSQL($i['id_order']));

		return $i;
	}
	
	protected function completeInvoice(&$i) {
		$this->getDbLink()->beginTransaction();
		$db = Db::getInstance();
	
		$data = $db->getRow('
			SELECT
				o.id_address_delivery,
				o.id_address_invoice,
				i.number AS invoice_number,
				o.id_shop,
				o.id_lang,
				o.id_customer
			FROM '._DB_PREFIX_.'orders o
			JOIN '._DB_PREFIX_.'order_invoice i ON i.id_order = o.id_order
			WHERE o.id_order = '.pSQL($i['ps_order']));

		$invoiceAddressPatternRules = Tools::jsonDecode(Configuration::get('PS_INVCE_INVOICE_ADDR_RULES'), true);
		$deliveryAddressPatternRules = Tools::jsonDecode(Configuration::get('PS_INVCE_DELIVERY_ADDR_RULES'), true);
		// Backward compatibility
		$invoiceAddressPatternRules = $invoiceAddressPatternRules ? $invoiceAddressPatternRules : array();
		$deliveryAddressPatternRules = $deliveryAddressPatternRules ? $deliveryAddressPatternRules : array();

		$formatted_invoice_address = '';
		$formatted_delivery_address = '';
	
		$invoice_address = new Address((int) $data['id_address_invoice']);
		if ($invoice_address) {
			$formatted_invoice_address = AddressFormat::generateAddress($invoice_address, $invoiceAddressPatternRules, '<br />', ' ');
		}
	
		if ($data['id_address_delivery'] != Configuration::get('KERAWEN_'))
		$delivery_address = new Address((int) $data['id_address_delivery']);
		if ($delivery_address) {
			$formatted_delivery_address = AddressFormat::generateAddress($delivery_address, $deliveryAddressPatternRules, '<br />', ' ');
		}
		
		if ($data['id_address_delivery'] == Configuration::get('KERAWEN_DEFAULT_ADDRESS')) {
// 			require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
// 			$shops = selectShops($data['id_shop']);
// 			foreach($shops AS $shop) {
// 				$formatted_delivery_address = '';
// 				$formatted_delivery_address .= $shop['name'];
// 				if (!empty($shop['addr1'])) { $formatted_delivery_address .= '<br />' . $shop['addr1']; }
// 				if (!empty($shop['addr2'])) { $formatted_delivery_address .= '<br />' . $shop['addr2']; }
// 				if (!(empty($shop['postcode']) || empty($shop['city']))) { $formatted_delivery_address .= '<br />' . $shop['postcode'] . ' ' . $shop['city']; }
// 				if (!empty($shop['country'])) { $formatted_delivery_address .= '<br />' . $shop['country']; }
// 			}
			// Do not print adress in that case
			$formatted_delivery_address = '';
		}
		
		$format = '%1$s%2$06d';
		if (Configuration::get('PS_INVOICE_USE_YEAR')) {
			$format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s/%2$06d' : '%1$s%2$06d/%3$s';
		}
		$invoice_label = '';
		if ($i['ps_slip']) {
			$ps_slip_prefix = Configuration::get('PS_SLIP_PREFIX', (int)$data['id_lang'], null, (int)$data['id_shop']);
			$slip_prefix = $ps_slip_prefix ? $ps_slip_prefix : '#AV';
			$invoice_label = sprintf($format, $slip_prefix, $i['ps_slip'], date('Y'));
		}
		else {
			$invoice_label = sprintf($format, Configuration::get('PS_INVOICE_PREFIX', (int)$data['id_lang'], null, (int)$data['id_shop']), $data['invoice_number'], date('Y'));
		}
		
		$db->insert(_KERAWEN_525_PREFIX_.'invoice', array(
			'id_order' => $i['id_order'],
			'invoice_label' => pSQL($invoice_label),
			'firstname' => $invoice_address ? pSQL($invoice_address->firstname) :  null,
			'lastname' => $invoice_address ? pSQL($invoice_address->lastname) : null,
			'company' => $invoice_address ? pSQL($invoice_address->company) : null,
			'invoice_address' => pSQL($formatted_invoice_address, true),
			'delivery_address' => pSQL($formatted_delivery_address, true),
		), true);
		
		// Additional invoice info
		$info = array(
			'date' => date('Y-m-d H:i:s', time()),
			'number' => pSQL($invoice_label),
			'name' => $invoice_address ? pSQL($invoice_address->lastname) : '',
			'postcode' => $invoice_address ? pSQL($invoice_address->postcode) : '0000',
		);

		// Signature
		$data = array();
		foreach ($i['taxes'] as $id_tax => &$tax) {
			$data[$this->formatAmount($tax['tax_rate'])] = $this->formatAmount($tax['total_ti']);
		}
		$data = array(
			$data,
			$this->formatAmount($i['total_ti']),
			$this->formatDate($i['date']),
			$info['number'],
			$info['name'],
			$info['postcode'],
			'',
		);
		$sign = $this->sign(_KERAWEN_525_OP_INVOICE_, $data);

		$db->update(_KERAWEN_525_PREFIX_.'order', array_merge($info, array(
			'description' => $sign['desc'],
			'signature' => $sign['sign'],
			'sign_short' => $sign['short'],
		)), 'id_order = '.pSQL($i['id_order']));
		
		$this->getDbLink()->commit();
	}

	
	/* **************************************************************
	 * TILL MANAGEMENT
	*/
	
	public function getTillState($id_till, $state = false) {
		$db = Db::getInstance();
		$op = $db->getRow('
			SELECT
				id_operation AS id_op, date, id_till, type AS state
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
			WHERE id_till = '.pSQL($id_till).'
			AND (type = "'._KERAWEN_525_OP_OPEN_.'" OR type = "'._KERAWEN_525_OP_CLOSE_.'")
			ORDER BY id_operation DESC');
		return (!$state || $op['state'] == $state) ? $op : false;
	}
	
	public function getTillContent($id_till, $state = false) {
		$data = false;
		$ref = $this->getTillState($id_till, $state);
		if ($ref) {
			$db = Db::getInstance();
			
			$content = array();
			$payments = array();
				
			$check = $db->executeS('
				SELECT tc.*
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check tc
				WHERE id_operation = '.pSQL($ref['id_op']));
			foreach ($check as &$c) {
				$content[$c['id_mode']] = array(
					'count' => $c['count'], // Maybe null = not counted
					'amount' => (float)$c['checked'],
				);
			}
			
			$flow = $db->executeS('
				SELECT tf.*
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_flow tf
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = tf.id_operation
				WHERE op.id_till = '.pSQL($ref['id_till']).'
				AND op.id_close IS NULL');
			foreach ($flow as &$f) {
				if (!isset($content[$f['id_mode']])) $content[$f['id_mode']] = array(
					'count' => 0,
					'amount' => 0.0,
				);
				$content[$f['id_mode']]['count']++;
				$content[$f['id_mode']]['amount'] += (float)$f['amount'];
			}
			
			// Get not closed payments + remaining payments (cheques)
			// TODO Use payment configuration
			$pay = $db->executeS('
				SELECT
					p.id_payment AS id_flow, p.*,
					op.date, op.id_close,
					(SELECT customer_name
						FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s
						WHERE s.id_operation = op.id_operation
						LIMIT 1) AS customer
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment p
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = p.id_operation
				WHERE op.id_till = '.pSQL($ref['id_till']).'
				AND (
					p.id_mode != 2 AND op.id_close IS NULL
					OR p.id_mode = 2 AND p.id_out IS NULL)');
			foreach ($pay as &$p) {
				if (is_null($p['id_close'])) {
					if (!isset($content[$p['id_mode']])) $content[$p['id_mode']] = array(
						'count' => 0,
						'amount' => 0.0,
					);
				 	if (!is_null($content[$p['id_mode']]['count'])) $content[$p['id_mode']]['count']++;
				 	$content[$p['id_mode']]['amount'] += (float)$p['amount'];
				}
				if (!isset($payments[$p['id_mode']])) $payments[$p['id_mode']] = array();
				$payments[$p['id_mode']][] = $p;
			}
			
			$data = array(
				'ref' => $ref,
				'content' => $content,
				'payments' => $payments,
			);
		}
		return $data;
	}

	protected function logTillOperation($id_till, $type, $id_ref = null) {
		$datetime = time();
		$db = Db::getInstance();
		$context = Context::getContext();
		$id_shop = $context->shop ? $context->shop->id : null;
		$operator = $context->employee;
		$id_till = isset($context->kerawen) && isset($context->kerawen->id_cashdrawer) ? $context->kerawen->id_cashdrawer : 0;
		$till_name = $db->getValue('SELECT name FROM '._DB_PREFIX_.'cash_drawer_kerawen WHERE id_cash_drawer = '.pSQL($id_till));
		$module = Module::getInstanceByName('kerawen');
		
		$db->insert(_KERAWEN_525_PREFIX_.'operation', array(
			'date' => date('Y-m-d H:i:s', $datetime),
			'type' => pSQL($type),
			'id_ref' => $id_ref,
			'id_till' => $id_till,
			'till_name' => pSQL($till_name),
			'id_operator' => $operator ? $operator->id : null,
			'operator_name' => $operator ? pSQL($operator->firstname.' '.$operator->lastname) : null,
			'id_shop' => $id_shop,
			'KERAWEN_MODULE_VERSION' => $module->version,
		), true);
		$id_op= $db->Insert_ID();
		return $id_op;
	}
	
	public function openTill($id_till, $open_data) {
		$content = $this->getTillContent($id_till);
		if (!$content) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			// Get content from last legacy closing...
			$content = getTillContent($id_till);
			// but do not reference legacy operation
			unset($content['ref']);
		}
		
		$this->getDbLink()->beginTransaction();
		$db = Db::getInstance();
		
		$id_close = isset($content['ref']['id_op']) ? $content['ref']['id_op'] : 0;
		
		$id_open = $this->logTillOperation($id_till, _KERAWEN_525_OP_OPEN_, $id_close);
		$payment_modes = Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
		
		$check = array();
		foreach ($open_data as $mode => $mode_data) {
			$expected = isset($content['content'][$mode]) ? (float)$content['content'][$mode]['amount'] : 0.0;
			$error = $mode_data->checked - $expected;
			$check[] = array(
				'id_operation' => $id_open,
				'id_mode' => $mode,
				'mode' => pSQL(isset($payment_modes[$mode]) ? $payment_modes[$mode]['label'] : $mode),
				'id_currency' => null, // TODO
				'currency' => null, // TODO
				'checked' => $mode_data->checked,
				'count' => null,
				'error' => $error,
			);
		}
		
		// TODO get cheques data from operator
		$mode = 2; // cheques
		if ($id_close) {
			$buf = $db->getRow('
				SELECT tc.*
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check tc
				WHERE id_operation = '.pSQL($id_close).' AND id_mode = '.pSQL($mode));
		}
		else {
			// Legacy closing: same as before
			$buf = array(
				'checked' => $content['content'][$mode]['amount'],
				'count' => $content['content'][$mode]['count'],
			);
		}
		$check[] = array(
			'id_operation' => $id_open,
			'id_mode' => $mode,
			'mode' => pSQL(isset($payment_modes[$mode]) ? $payment_modes[$mode]['label'] : $mode),
			'id_currency' => null, // TODO
			'currency' => null, // TODO
			'checked' => $buf['checked'],
			'count' => $buf['count'],
			'error' => 0.0,
		);
		
		$db->insert(_KERAWEN_525_PREFIX_.'till_check', $check, true);
		$this->getDbLink()->commit();
		return $id_open;
	}

	public function closeTill($id_till, $close_data) {
		$id_close = false;
		// Check if opened according to new mode
		$content = $this->getTillContent($id_till, _KERAWEN_525_OP_OPEN_);
		if ($content) {
			$this->getDbLink()->beginTransaction();
			$db = Db::getInstance();
			
			$id_open = isset($content['ref']['id_op']) ? $content['ref']['id_op'] : null;
			$id_close = $this->logTillOperation($id_till, _KERAWEN_525_OP_CLOSE_, $id_open);
			$payment_modes = Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
				
			$check = array();

			foreach ($close_data as $mode => $mode_data) {
				if (isset($mode_data->withdrawn) && $mode_data->withdrawn) {
					$this->flowTill($id_till, null, -$mode_data->withdrawn, $mode, null, null, $id_close);
				}
				else $mode_data->withdrawn = 0;
				
				if (isset($mode_data->payments)) {
					$remain = array();
					if (isset($content['payments'][$mode]) && $content['payments'][$mode]) {
						foreach($content['payments'][$mode] as &$p) {
							$remain[$p['id_payment']] = $p;
						}
					}
					$count = 0;
					$amount = 0;
					$error = 0;
					foreach ($mode_data->payments as $payment)
					{
						$count++;
						if (isset($payment->correct)) {
							$amount += $payment->correct;
							$error += $payment->correct - $payment->amount;
						}
						else {
							$amount += $payment->amount;
						}
						$db->update(_KERAWEN_525_PREFIX_.'payment', array(
							'id_out' => pSQL($id_close),
							'corrected' => isset($payment->correct) ? $payment->correct : null,
						), 'id_payment = '.pSQL($payment->id), 0, true);
						
						unset($remain[$payment->id]);
					}
					if ($count) {
						$this->flowTill($id_till, null, -$amount, $mode, $count, null, $id_close);
					}
					
					$count_remain = 0;
					$amount_remain = 0.0;
					foreach($remain as &$p) {
						$count_remain++;
						$amount_remain += $p['amount'];
					}
					$check[] = array(
						'id_operation' => $id_close,
						'id_mode' => $mode,
						'mode' => pSQL(isset($payment_modes[$mode]) ? $payment_modes[$mode]['label'] : $mode),
						'id_currency' => null, // TODO
						'currency' => null, // TODO
						'checked' => $amount_remain,
						'count' => $count_remain,
						'error' => $error,
					);
				}
				else if (isset($mode_data->amount)) {
					$expected = isset($content['content'][$mode]) ? (float)$content['content'][$mode]['amount'] : 0.0;
					$error = $mode_data->amount - $expected;
					$remain = $mode_data->amount - $mode_data->withdrawn;
				
					$check[] = array(
						'id_operation' => $id_close,
						'id_mode' => $mode,
						'mode' => pSQL(isset($payment_modes[$mode]) ? $payment_modes[$mode]['label'] : $mode),
						'id_currency' => null, // TODO
						'currency' => null, // TODO
						'checked' => $remain,
						'count' => null,
						'error' => $error,
					);
				}
			}
			$db->insert(_KERAWEN_525_PREFIX_.'till_check', $check, true);
			
			// Mark operations as closed
			$db->execute('
				UPDATE '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				SET id_close = '.pSQL($id_close).'
				WHERE id_till = '.pSQL($id_till).'
					AND id_operation >= '.pSQL($content['ref']['id_op']).'
					AND id_close IS NULL');
				
			$this->getDbLink()->commit();
		}
		return $id_close;
	}
	
	public function flowTill($id_till, $id_currency, $amount, $mode, $count, $comments, $id_op = null) {
		$id_flow = false;
		// Check if opened/closed according to new mode
		$content = $this->getTillContent($id_till);
		if ($content) {
			$id_flow = $id_op;
			if (!$id_flow) $id_flow = $this->logTillOperation($id_till, _KERAWEN_525_OP_FLOW_);
			$payment_modes = Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
			
			Db::getInstance()->insert(_KERAWEN_525_PREFIX_.'till_flow', array(
				'id_operation' => $id_flow,
				'id_currency' => $id_currency,
				'currency' => $this->getCurrencyInfo($id_currency)->name,
				'amount' => $amount,
				'id_mode' => $mode,
				'mode' => pSQL(isset($payment_modes[$mode]) ? $payment_modes[$mode]['label'] : $mode),
				'count' => $count,
				'comments' => pSQL($comments),
			), true);
		}
		return $id_flow;
	}
	
	function getOpeningData($id_op) {
		$db = Db::getInstance();
		$data = array();
		
		$data['open'] = $db->getRow('
			SELECT
				id_operation AS id_op, id_ref,
				id_till, date, operator_name AS empl
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
			WHERE id_operation = '.pSQL($id_op));
		if (!$data['open']) return false;
		
		$data['open']['modes'] = $db->executeS('
			SELECT id_mode AS mode, checked, count, error
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check
			WHERE id_operation = '.pSQL($id_op));
			
		$id_till = $data['open']['id_till'];
		
		$data['till'] = $db->getRow('
			SELECT id_cash_drawer AS id_till, name
			FROM '._DB_PREFIX_.'cash_drawer_kerawen
			WHERE id_cash_drawer = '.pSQL($id_till));
		
		if ($data['open']['id_ref']) {
			$data['close'] = $db->getRow('
				SELECT id_operation AS id_op, date, operator_name AS empl
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				WHERE id_operation = '.pSQL($data['open']['id_ref']));
		}

		if (isset($data['close']) && $data['close']) {
			$data['close']['modes'] = $db->executeS('
			SELECT id_mode AS mode, checked, count
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check
			WHERE id_operation = '.pSQL($data['close']['id_op']));
		}
		else {
			// Get legacy closing data
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$op = lastOpenClose($id_till);
			if ($op['oper'] == _KERAWEN_CDOP_CLOSE_) {
				$data['close'] = $db->getRow('
					SELECT
						co.id_cashdrawer_op AS id_op,
						co.date AS date,
						CONCAT(e.firstname, " ", e.lastname) AS empl
					FROM '._DB_PREFIX_.'cashdrawer_op_kerawen co
					LEFT JOIN '._DB_PREFIX_.'employee e ON e.id_employee = co.id_employee
					WHERE co.id_cashdrawer_op = '.pSQL($op['id_op']));
				if ($data['close']) {
					$data['close']['modes'] = $db->executeS('
						SELECT
							cc.id_payment_mode AS mode,
							cc.checked AS checked
						FROM '._DB_PREFIX_.'cashdrawer_close_kerawen cc
						WHERE cc.id_cashdrawer_op = '.pSQL($data['close']['id_op']));
					$data['close']['cheques'] = getRemaining($id_till, 2, $data['close']['date']);
				}
			}
		}
		
		return $data;
	}
	
	public function getClosingData($id_op, $id_till) {
		$db = Db::getInstance();
		$data = array();
		
		if ($id_op) {
			$data['close'] = $db->getRow('
				SELECT
					id_operation AS id_op, id_ref,
					id_till, date, operator_name AS empl
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
				WHERE id_operation = '.pSQL($id_op));
			if (!$data['close']) return false;
			
			$data['close']['modes'] = $db->executeS('
				SELECT id_mode AS mode, checked, count, error
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check
				WHERE id_operation = '.pSQL($id_op));
			
			$id_till = $data['close']['id_till'];
		}
		elseif ($id_till) {
			$content = $this->getTillContent($id_till);
			// Check if till is opened according to new mode
			if (!$content) return false; 
			
			$modes = array();
			foreach($content['content'] as $mode => $data)
				$modes[] = array(
					'mode' => $mode,
					'count' => $data['count'],
					'checked' => $data['amount'],
			);
			
			$data['state'] = array(
				'modes' => $modes,
			);
		}
		
		$data['till'] = $db->getRow('
			SELECT id_cash_drawer AS id_till, name
			FROM '._DB_PREFIX_.'cash_drawer_kerawen
			WHERE id_cash_drawer = '.pSQL($id_till));
		
		$data['open'] = $db->getRow('
			SELECT id_operation AS id_op, date, operator_name AS empl
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
			WHERE '.($id_op
				? 'id_operation = '.pSQL($data['close']['id_ref'])
				: 'id_till = '.pSQL($id_till).'
					AND type = "'.pSQL(_KERAWEN_525_OP_OPEN_).'"
					ORDER BY id_operation DESC'));
		
		if ($data['open']) {
			$data['open']['modes'] = $db->executeS('
			SELECT id_mode AS mode, checked, count
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check
			WHERE id_operation = '.pSQL($data['open']['id_op']));
		}
		
		// Common filter for details (use op as alias)
		$op_filter = $id_op
			? 'op.id_close = '.pSQL($id_op)
			: 'op.id_till = '.pSQL($id_till).' AND op.id_close IS NULL';

		$data['flows'] = $db->executeS('
			SELECT
				op.id_operation AS id_op,
				op.type AS oper,
				op.date,
				op.operator_name AS empl,
				data.id_mode AS mode,
				data.amount
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_flow data
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = data.id_operation
			WHERE '.$op_filter);
		
		// WARNING: don't use UNION, it seems to imply DISTINCT !!!
		$data['flows'] = array_merge($data['flows'], $db->executeS('
			SELECT
				op.id_operation AS id_op,
				op.type AS oper,
				op.date,
				op.operator_name AS empl,
				data.id_mode AS mode,
				data.amount
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment data
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = data.id_operation
			WHERE '.$op_filter));
		
		require_once(dirname(__FILE__).'/KerawenLog.php');
		$log = new KerawenLog($op_filter);
		$data['more'] = array(
			'sales' => $log->sales(),
			'taxes' => $log->taxes(),
			//'prods' => $log->prods(), // TODO correct assignment products vs shipping/wrapping
		);
		
		return $data;
	}
	
	public function getFlowData($id_op) {
		$db = Db::getInstance();
		$data = $db->getRow('
			SELECT
				id_till AS id_till,
				till_name AS till,
				date AS date,
				operator_name AS operator
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation
			WHERE id_operation = '.pSQL($id_op));
		if (!$data) return false;
		
		$data['flows'] = $db->executeS('
			SELECT
				tf.*,
				tf.comments AS note
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_flow tf
			WHERE tf.id_operation = '.pSQL($id_op));
		return $data;
	}
	
	
	/* **************************************************************
	 * EXPORTING
	 */
	
	public function getArchivable() {
		$datetime = time();
		$this->closeGrandTotal($datetime);
		
		$db = Db::getInstance();
		$res = $db->executeS('
			SELECT
				id_gtotal AS id,
				period_type,
				period_ref
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
			WHERE signature IS NOT NULL
			AND period_type != "'._KERAWEN_525_PER_SALE_.'"
			ORDER BY date DESC');
		
		$link = new Link();
		// TODO move to controller
		$url = $link->getAdminLink('AdminModules').'&configure=kerawen&archive&id=';
		foreach ($res as &$ar) {
			$ar['url'] = $url.$ar['id'];
		}
		return $res;
	}
	
	public function getArchive($id_gtotal) {
		$db = Db::getInstance();
		$archive = $db->getRow('
			SELECT *
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal
			WHERE id_gtotal = '.pSQL($id_gtotal));
		
		// Record event
		$this->logEvent(_KERAWEN_525_EVT_ARCH_PERIOD_, 'Archive', $archive['period_ref']);
		
		// Prepare empty directory
		$dir = tempnam(sys_get_temp_dir(), 'ARC');
		unlink($dir);
		mkdir($dir);
		$files = array();
		
		// Signature
		$taxes = $db->executeS('
			SELECT *
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal_tax
			WHERE id_gtotal = '.pSQL($id_gtotal));
		$data = array();
		foreach ($taxes as $id_tax => &$tax) {
			$data[$this->formatAmount($tax['tax_rate'])] = $this->formatAmount($tax['total_ti']);
		}
		$data = array(
			$data,
			$this->formatAmount($gtotal['total_ti']),
			$this->formatDate(date('Y-m-d H:i:s', time())),
			'',
			_KERAWEN_525_OP_ARCHIVE_,
		);
		$sign = $this->sign(_KERAWEN_525_OP_ARCHIVE_, $data);
		
		$filename = $dir.'/signature.txt';
		file_put_contents($filename,$sign['desc'].PHP_EOL.$sign['sign']);
		$files[] = $filename;
		
		// Content
		$outputs = array(
			'sales' => array(
				'sql' => '
					SELECT s.*, o.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation o ON o.id_operation = s.id_operation
					WHERE o.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY s.id_sale ASC',
			),
			'sale_details' => array(
				'sql' => '
					SELECT sd.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sd
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s ON s.id_sale = sd.id_sale
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation o ON o.id_operation = s.id_operation
					WHERE o.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY sd.id_sale_detail ASC',
			),
			'sale_taxes' => array(
				'sql' => '
					SELECT st.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax st
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s ON s.id_sale = st.id_sale
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation o ON o.id_operation = s.id_operation
					WHERE o.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY st.id_sale, st.id_tax ASC',
			),
			'payments' => array(
				'sql' => '
					SELECT p.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment p
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation o ON o.id_operation = p.id_operation
					WHERE o.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY p.id_payment ASC',
			),
			'duplicates' => array(
				'sql' => '
					SELECT d.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'duplicate d
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale s ON s.id_sale = d.id_sale
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation o ON o.id_operation = s.id_operation
					WHERE o.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY d.id_duplicate ASC',
			),
			'invoices' => array(
				'sql' => '
					SELECT i.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order i
					WHERE i.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY i.id_order ASC',
			),
			'invoice_details' => array(
				'sql' => '
					SELECT id.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail id
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order i ON i.id_order = id.id_order
					WHERE i.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY id.id_sale_detail ASC',
			),
			'invoice_taxes' => array(
				'sql' => '
					SELECT it.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order_tax it
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order i ON i.id_order = it.id_order
					WHERE i.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY it.id_order, it.id_tax ASC',
			),
			'gtotal' => array(
				'sql' => '
					SELECT t.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal t
					WHERE t.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY t.id_gtotal ASC',
			),
			'gtotal_taxes' => array(
				'sql' => '
					SELECT tt.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal_tax tt
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'gtotal t ON t.id_gtotal = tt.id_gtotal
					WHERE t.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY t.id_gtotal ASC',
			),
			'jet' => array(
				'sql' => '
					SELECT e.*
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'event e
					WHERE e.date LIKE "'.$archive['period_ref'].'%"
					ORDER BY e.id_event ASC',
			),
		);
		
		foreach ($outputs as $name => $config) {
			$data = $db->executeS($config['sql']);
			$filename = $dir.'/'.$name.'.csv';
			$fd = fopen($filename, 'w');
			fputcsv($fd, array_keys(reset($data)));
			foreach ($data as $row) {
				fputcsv($fd, $row);
			}
			fclose($fd);
			$files[] = $filename;
		}
		
		// Zip
		$filename = $dir.'/'.$archive['period_ref'].'-archive.zip';
		$zip = new ZipArchive();
		$zip->open($filename, ZIPARCHIVE::CREATE);
		foreach ($files as $file) {
			$b = $zip->addFile($file, basename($file));
		}
		$zip->close();
		
		// Download
		header('Content-Type: application/zip');
		header('Cache-Control: no-store, no-cache');
		header('Content-Disposition: attachment; filename="'.basename($filename).'"');
		readfile($filename);
		
		// Clean-up
		foreach ($files as $file) {
			unlink($file);
		}
		unlink($filename);
		rmdir($dir);
		die();
	}
	
	
	/* **************************************************************
	 * RECOVERING DATA
	 */
	
	public function getLogs($types, $from, $to, $id_till, $id_operator, $id_shop, $init_from, $init_to) {
		require_once(dirname(__FILE__).'/KerawenLog.php');
		$log = new KerawenLog(array(
			'from' => $from,
			'to' => $to,
			'id_till' => $id_till,
			'id_operator' => $id_operator,
			'id_shop' => $id_shop,
			'init_from' => $init_from,
			'init_to' => $init_to,
		));
		$types[] = "ops";
		$res = array();
		foreach ($types as $type) {
			$res[$type] = $log->$type();
		}
		return $res;
	}
	
	
	/* **************************************************************
	 * DATA MODEL UTILITIES
	*/
	
	protected function isOrderValid($order_state) {
		require_once(_KERAWEN_CLASS_DIR_.'/order_state.php');
		return isOrderStateValid($order_state);
	}
	
	protected function getOrderInfo($id_order) {
		$db = Db::getInstance();
		
		$info = $db->getRow('
			SELECT
				ok.round_type,
				ok.free_shipping,
				ok.product_global_discount,
				o.total_products,
				o.total_products_wt,
				o.total_shipping_tax_excl,
				o.total_shipping_tax_incl,
				o.total_discounts_tax_excl,
				o.total_discounts_tax_incl,
				0 AS global_discount_te,
				0 AS global_discount_ti
			FROM '._DB_PREFIX_.'order_kerawen ok
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = ok.id_order
			WHERE ok.id_order = '.pSQL($id_order));
		
		return $info;
	}
	
	protected function getOrderTaxAddress($order) {
		$id_address = $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
		if (!$id_address) $id_address = $order->id_address_delivery;
		return $id_address;
	}
	
	protected function getOrderDetails($order, $info, $cancel) {
		$details = array();
		
		$db = Db::getInstance();
		$id_order = $order->id;
		$id_address = $this->getOrderTaxAddress($order);
		
		$details = array_merge($details, $db->executeS('
			SELECT
				od.id_order_detail AS id_order_detail,
				od.product_id AS id_prod,
				od.product_attribute_id AS id_attr,
				pl.name AS item_name,
				(SELECT GROUP_CONCAT(al.name ORDER BY a.id_attribute_group SEPARATOR ",")
					FROM '._DB_PREFIX_.'product_attribute_combination pac
					LEFT JOIN '._DB_PREFIX_.'attribute a ON a.id_attribute = pac.id_attribute
					LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON al.id_attribute = a.id_attribute
					WHERE pac.id_product_attribute = od.product_attribute_id
					AND pac.id_product_attribute > 0
					AND al.id_lang = o.id_lang
				) AS version_name,
				od.unit_price_tax_excl AS unit_te,
				od.unit_price_tax_incl AS unit_ti,
				odk.id_tax_rules_group AS id_tax,
				odk.margin_vat AS margin_vat,
				'.pSQL($id_address).' AS id_address,
				od.ecotax AS ecotax_te,
				od.ecotax * (od.ecotax_tax_rate / 100 + 1) AS ecotax_ti,
				odk.measure AS measure,
				odk.unit AS measure_unit,
				odk.precision AS measure_precision,
				'.($cancel ? '-' : '').'(od.product_quantity - od.product_quantity_return - od.product_quantity_refunded) AS quantity,
				od.reduction_amount_tax_excl AS discount_te,
				od.reduction_amount_tax_incl AS discount_ti,
				od.reduction_percent AS discount_percent,
				'.$info['global_discount_te'].' AS discount_rate_te,
				'.$info['global_discount_ti'].' AS discount_rate_ti,
				(CASE
					WHEN pas.wholesale_price != 0 THEN pas.wholesale_price
					ELSE ps.wholesale_price
				END) AS purchase_te,
				(SELECT SUM(s.price_te*sm.physical_quantity)/SUM(sm.physical_quantity)
					FROM '._DB_PREFIX_.'stock s
					JOIN '._DB_PREFIX_.'stock_mvt sm ON sm.id_stock = s.id_stock
					WHERE s.id_product = od.product_id
						AND s.id_product_attribute = od.product_attribute_id
						AND sm.id_order = od.id_order
				) AS stock_te,
				IF(pwk.is_gift_card = 1, 1, 0) AS gift_card
			FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk ON odk.id_order_detail = od.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON odt.id_order_detail = od.id_order_detail
			LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = od.id_order
			LEFT JOIN '._DB_PREFIX_.'product_shop ps ON ps.id_product = od.product_id AND ps.id_shop = o.id_shop
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product = od.product_id AND pl.id_shop = o.id_shop AND pl.id_lang = o.id_lang
			LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pas ON pas.id_product_attribute = od.product_attribute_id AND pas.id_shop = o.id_shop
			LEFT JOIN '._DB_PREFIX_.'product_wm_kerawen pwk ON pwk.id_product = od.product_id
			WHERE od.id_order = '.pSQL($id_order)));
		
		// Take into account previous refunding
		// TODO from 525 records instead of PS?
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.11', '>=')) {
			$shipping_refund = $db->getRow('
				SELECT
					SUM(os.total_shipping_tax_excl) AS total_shipping_tax_excl,
					SUM(os.total_shipping_tax_incl) AS total_shipping_tax_incl
				FROM '._DB_PREFIX_.'order_slip os
				WHERE os.id_order = '.pSQL($id_order));
		}
		else {
			$shipping_refund = $db->getRow('
				SELECT
					SUM(os.shipping_cost_amount/(1 + o.carrier_tax_rate/100)) AS total_shipping_tax_excl,
					SUM(os.shipping_cost_amount) AS total_shipping_tax_incl
				FROM '._DB_PREFIX_.'order_slip os
				JOIN '._DB_PREFIX_.'orders o ON o.id_order = os.id_order
				WHERE os.id_order = '.pSQL($id_order));
		}
		
		$order->total_shipping_tax_excl -= $shipping_refund['total_shipping_tax_excl'];
		$order->total_shipping_tax_incl -= $shipping_refund['total_shipping_tax_incl'];
		
		//if ($order->total_shipping_tax_excl != 0) {
		if ($order->id_carrier) {
			$carrier = $this->getCarrierInfo($order->id_carrier, $order->id_shop);
			$details[] = array(
				'item_name' => $this->label_shipping,
				'version_name' => $carrier['name'],
				'id_carrier' => $carrier['id'],
				'unit_te' => $order->total_shipping_tax_excl,
				'unit_ti' => $order->total_shipping_tax_incl,
				'id_tax' => $carrier['id_tax'],
				'id_address' => $id_address,
				'quantity' => $cancel ? -1 : +1,
				'discount_te' => 0.0,
				'discount_ti' => 0.0,
				'discount_percent' => 0.0,
				'discount_rate_te' => $info['free_shipping'],
				'discount_rate_ti' => $info['free_shipping'],
				'purchase_te' => $order->total_shipping_tax_excl,
			);
		}
		
		if ($order->total_wrapping_tax_incl != 0) {
			$details[] = array(
				'item_name' => $this->label_wrapping,
				'wrapping' => 1,
				'unit_te' => $order->total_wrapping_tax_excl,
				'unit_ti' => $order->total_wrapping_tax_incl,
				'id_tax' => Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'),
				'id_address' => $id_address,
				'quantity' => $cancel ? -1 : +1,
				'discount_te' => 0.0,
				'discount_ti' => 0.0,
				'discount_percent' => 0.0,
				'discount_rate_te' => 0.0,
				'discount_rate_ti' => 0.0,
				'purchase_te' => $order->total_wrapping_tax_excl,
			);
		}
		
		return $details;
	}
	
	protected function getProductInfo($id_order_detail) {
		$db = Db::getInstance();
		return $db->getRow('
			SELECT
				pl.name AS item_name,
				(SELECT GROUP_CONCAT(al.name ORDER BY a.id_attribute_group SEPARATOR ",")
					FROM '._DB_PREFIX_.'product_attribute_combination pac
					LEFT JOIN '._DB_PREFIX_.'attribute a ON a.id_attribute = pac.id_attribute
					LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON al.id_attribute = a.id_attribute
					WHERE pac.id_product_attribute = od.product_attribute_id
					AND pac.id_product_attribute > 0
					AND al.id_lang = o.id_lang
				) AS version_name
			FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = od.id_order
			LEFT JOIN '._DB_PREFIX_.'product_lang pl
				ON pl.id_product = od.product_id AND pl.id_shop = o.id_shop AND pl.id_lang = o.id_lang
			WHERE od.id_order_detail = '.pSQL($id_order_detail));
	}
	
	protected function getCarrierInfo($id_carrier, $id_shop) {
		static $taxes = array();
		$key = $id_carrier.'_'.$id_shop;
		if (!isset($taxes[$key])) {
			$taxes[$key] = Db::getInstance()->getRow('
				SELECT
					c.id_carrier AS id,
					c.name AS name,
					cts.id_tax_rules_group AS id_tax
				FROM '._DB_PREFIX_.'carrier_tax_rules_group_shop cts
				JOIN '._DB_PREFIX_.'carrier c ON c.id_carrier = cts.id_carrier
				WHERE cts.id_carrier = '.pSQL($id_carrier).'
				AND cts.id_shop = '.pSQL($id_shop));
		}
		return $taxes[$key];
	}
	
	protected function getTaxInfo($id_taxrule, $id_address) {
		static $taxes = array();
		$key = $id_taxrule.'_'.$id_address;
		if (!isset($taxes[$key])) {
			$taxrule = new TaxRulesGroup($id_taxrule);
			$tax_manager = TaxManagerFactory::getManager(new Address($id_address), $id_taxrule);
			$taxes[$key] = array(
				'name' => $taxrule->name,
				'rate' => $tax_manager->getTaxCalculator()->getTotalRate(),
			);
		}
		return $taxes[$key];
	}
	
	protected function getCurrencyInfo($id_currency) {
		static $currs = array();
		$key = $id_currency;
		if (!isset($currs[$key])) {
			$currs[$key] = new Currency($id_currency);
		}
		return $currs[$key];
	}
	
	protected function getOrderPayment($id_payment) {
		$payment = new OrderPayment($id_payment);
		if (!$payment->order_reference)
			$payment->order_reference = Db::getInstance()->getValue('
				SELECT reference
				FROM '._DB_PREFIX_.'order_payment_kerawen
				WHERE id_order_payment = '.pSQL($id_payment));
		return $payment;
	}

	
	/* **************************************************************
	 * SIGNING
	*/
	
	protected function formatAmount($amount) {
		return round($amount*100);
	}
	
	protected function formatDate($date) {
		return date('YmdHis', strtotime($date));
	}

	protected function sign($type, $data) {
		$db = Db::getInstance();
		
		$prev = $db->getValue('
			SELECT sign FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'signature
			WHERE type = "'.pSQL($type).'"');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, _KERAWEN_525_SIGN_URL_);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
			'id' => Tools::getShopDomain().__PS_BASE_URI__,
			'lic' => Configuration::get('KERAWEN_LICENCE_KEY'),
			'type' => $type,
			'data' => $data,
			'prev' => $prev,
		)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
		$result = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($result, true);
		
		$db->execute('
			INSERT INTO '._DB_PREFIX_._KERAWEN_525_PREFIX_.'signature (type, sign, short)
			VALUES (
				"'.pSQL($type).'",
				"'.pSQL($result['sign']).'",
				"'.pSQL($result['short']).'")
			ON DUPLICATE KEY UPDATE
				sign = VALUES(sign),
				short = VALUES(short)');
		
		if ($result['error']) {
			$this->logEvent(
				_KERAWEN_525_EVT_SIGN_DEFAULT_,
				'Signature default',
				$result['msg'].' for '.$type.': '.$result['desc'],
				false,
				false,
				$result['sign']); // Do not try to sign again if failed...
		}

		return $result;
	}
	
	
	/* **************************************************************
	 * DATABASE TRANSACTIONS
	*/
	
	protected function getDbLink() {
		static $db_link = null;
		if (!$db_link) {
			$db = Db::getInstance();
			$obj = new ReflectionObject($db);
			$prop = $obj->getProperty('link');
			$prop->setAccessible(true);
			$db_link = $prop->getValue($db);
		}
		return $db_link;
	}
}

set_exception_handler('Kerawen525::OnException');
register_shutdown_function('Kerawen525::OnShutdown');
