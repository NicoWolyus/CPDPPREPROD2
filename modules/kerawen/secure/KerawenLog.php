<?php
/**
* @author    KerAwen <contact@kerawen.com>
* @copyright 2017-2018 KerAwen
*/

require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');

class KerawenLog {
	
	var $db;
	var $filter;
	var $lang;
	var $init_filter;
	var $init_from;
	var $init_to;
	var $vars;
	
	public function __construct($filter) {
		$this->db = Db::getInstance();
		$this->id_lang = Context::getContext()->language->id;
		
		if (is_array($filter)) {
			$this->filter = '
				op.date BETWEEN "'.pSQL($filter['from']).'" AND "'.pSQL($filter['to']).'"
				'.$this->getFilter('op.id_till', $filter['id_till']).'
				'.$this->getFilter('op.id_operator', $filter['id_operator']).'
				'.$this->getFilter('op.id_shop', $filter['id_shop']).'
				AND op.canceled IS NULL';
					
			$this->init_filter = '
				op.date BETWEEN "'.pSQL($filter['init_from']).'" AND "'.pSQL($filter['init_to']).'"
				'.$this->getFilter('op.id_till', $filter['id_till']).'
				'.$this->getFilter('op.id_operator', $filter['id_operator']).'
				'.$this->getFilter('op.id_shop', $filter['id_shop']).'
				AND op.canceled IS NULL';
			
			$this->init_from = $filter['init_from'];
			$this->init_to = $filter['init_to'];
			$this->vars = isset($filter['vars']) ? $filter['vars'] : [];
		}
		else {
			$this->filter = $filter;
		}
	}
	
	protected function getFilter($col, $val) {
		if ($val == _KERAWEN_CD_ALL_) return '';
		return ' AND '.$col.' IN ('.$val.')';
	}

	public function ops() {
		return $this->db->executeS('
			SELECT
				op.id_operation AS id_op,
				op.id_till AS id_till,
				op.id_operator AS id_empl,
				op.id_shop AS id_shop,
				op.date AS date,
				op.type AS oper
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			WHERE '.$this->filter);
	}

	public function sales() {
		$gc_reverse = ' IF(sad.gift_card = 1, 1, 0) ';
		$gc = ' IF(sad.gift_card = 1, sad.total_te, sad.purchase_te*sad.quantity) ';

		// TODO add discount
		return $this->db->executeS('
			SELECT
				op.id_operation AS id_op,
				(CASE
					WHEN ord.ps_slip != 0 THEN "SLIP"
					WHEN ord.total_ti < 0 THEN "CANCEL"
					ELSE "ORDER"
				END) AS oper,
				ord.id_order AS id_sale,
				op.id_shop AS id_shop,
				ord.ps_order AS id_order,
				ord.ps_slip AS id_slip,
				o.reference AS ref,
				-- i.invoice_label AS id_invoice,
				IF(o.invoice_number > 0, o.invoice_number, "") AS id_invoice,
				ord.total_te - SUM(sad.total_te*'.$gc_reverse.') AS tax_excl,
				ord.total_ti - SUM(sad.total_ti*'.$gc_reverse.') AS tax_incl,
				SUM(sad.unit_te*sad.measure*sad.quantity - sad.total_te) AS disc_te,
				SUM(sad.unit_ti*sad.measure*sad.quantity - sad.total_ti) AS disc_ti,
				sa.customer_name AS cust,
				c.company,
				gl.name AS `group`,
				SUM(sad.quantity) AS qty,
				SUM((sad.total_te - sad.purchase_te*sad.quantity)*'.$gc.') AS profit
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order ord
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON ord.id_sale = sa.id_sale
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON sa.id_operation = op.id_operation
			LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sad ON sad.id_order = ord.id_order
			-- LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'invoice i ON i.id_order = ord.id_order
			LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = ord.ps_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = sa.id_customer
				LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($this->id_lang).'
			WHERE '.$this->filter.'
			GROUP BY ord.id_order');
		}

	public function taxes() {
		$gc = ' IF(sad.gift_card = 1, 0, 1) ';
		
		return $this->db->executeS('
			SELECT
				op.id_operation AS id_op,
				(CASE
					WHEN ord.ps_slip != 0 THEN "SLIP"
					WHEN ord.total_ti < 0 THEN "CANCEL"
					ELSE "ORDER"
				END) AS oper,
				sa.id_sale AS id_sale,
				ord.ps_order AS id_order,
				ord.ps_slip AS id_slip,
				o.reference AS ref,
				op.id_shop AS id_shop,
				(CASE
					WHEN sad.wrapping IS NOT NULL THEN "WRAP"
					WHEN sad.id_carrier IS NOT NULL THEN "SHIP"
					ELSE "PROD"
				END) AS object,
				sad.id_tax AS id_taxrule,
				SUM(sad.unit_te*sad.quantity*IFNULL(sad.measure,1)*'.$gc.') AS gross_te,
				SUM(sad.total_te*'.$gc.') AS tax_excl,
				SUM((sad.total_ti - sad.total_te)*'.$gc.') AS tax,
				CONCAT(c.firstname, " ", c.lastname) AS customer,
				c.company,
				-- i.invoice_label AS inv_num,
				IF(o.invoice_number > 0, o.invoice_number, "") AS inv_num,
				sad.gift_card
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sad
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order ord ON ord.id_order = sad.id_order
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_sale = sad.id_sale
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = sa.id_operation
			-- LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'invoice i ON sad.id_order = i.id_order
			LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = ord.ps_order
			LEFT JOIN '._DB_PREFIX_.'customer c ON o.id_customer = c.id_customer
			WHERE '.$this->filter.'
			GROUP BY op.id_operation, ord.id_order, sad.id_tax');
	}
	
	public function flows() {
		return array_merge(
			$this->db->executeS('
				SELECT
					op.id_operation AS id_op,
					pa.id_payment AS id_flow,
					pa.ps_orders AS id_order,
					0 AS id_slip,
					pa.id_order_payment AS id_payment,
					0 AS id_mode,
					pa.mode AS mode,
					pa.amount AS amount,
					1 AS count,
					null AS deferred,
					null AS note
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment pa
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = pa.id_operation
				WHERE '.$this->filter),
			$this->db->executeS('
				SELECT
					op.id_operation AS id_op,
					tf.id_tillflow AS id_flow,
					"" AS id_order,
					0 AS id_slip,
					0 AS id_payment,
					tf.id_mode AS id_mode,
					tf.mode AS mode,
					tf.amount AS amount,
					tf.count AS count,
					null AS deferred,
					comments AS note
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_flow tf
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = tf.id_operation
				WHERE '.$this->filter));
	}
	
	public function checks() {
		return $this->db->executeS('
			SELECT
				op.id_operation AS id_op,
				tc.id_tillcheck AS id_check,
				tc.id_mode AS id_mode,
				tc.checked AS checked,
				tc.error AS error
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'till_check tc
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = tc.id_operation
			WHERE '.$this->filter);
	}
	
	public function prods() {
		return $this->db->executeS('
			SELECT
				op.id_operation AS id_op,
				(CASE
					WHEN ps_slip = 0 THEN "ORDER"
					ELSE "SLIP"
				END) AS oper,
				op.id_shop AS id_shop,
				ord.id_order AS id_sale,
				ord.ps_order AS id_order,
				ord.ps_slip AS id_slip,
				sa.customer_name AS cust,
				sad.item_name AS prod,
				sad.version_name AS version,
				sad.total_te/sad.quantity AS unit,
				sad.quantity AS qty,
				sad.total_te AS tax_excl,
				sad.total_ti AS tax_incl,
				sad.unit_te*sad.quantity*IFNULL(sad.measure,1) - sad.total_te AS total_disc_te,
				sad.unit_ti*sad.quantity*IFNULL(sad.measure,1) - sad.total_ti AS total_disc_ti,
				sad.purchase_te AS purchase_price,
				sad.purchase_te AS wholesale,
				
				o.reference AS ref,
				-- IF(cs.oper = "ORDER", 1, -1)*o.total_shipping AS shipping,
				-- IF(cs.oper = "ORDER", 1, -1)*o.total_shipping_tax_excl AS shipping_te,
				gl.name AS `group`,
				p.id_product AS id_prod,
				pa.id_product_attribute AS id_attr,
				p.id_category_default AS id_cat,
				col.name AS order_country,
				car.name AS carrier,
				/*cl.name AS cat,*/
				IF(ISNULL(sad.id_carrier), cl.name, sad.item_name) AS cat,
				p.reference AS ref,
				p.ean13 AS ean,
				p.upc as upc,
				su.name AS supplier,
				su.id_supplier,
				ma.name AS manufacturer,
				ma.id_manufacturer,
				odk.note AS note
				
				-- (
				--	SELECT GROUP_CONCAT(op.payment_method) 
				--	FROM '._DB_PREFIX_.'order_payment op
				--	WHERE op.order_reference = o.reference AND op.amount > 0
				--	GROUP BY op.order_reference
				-- ) AS payment_method,
				-- ok.is_paid
				
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sad
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'order ord ON ord.id_order = sad.id_order
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_sale = sad.id_sale
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op ON op.id_operation = sa.id_operation
			LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = ord.ps_order
				LEFT JOIN '._DB_PREFIX_.'order_kerawen ok ON ok.id_order = o.id_order
				LEFT JOIN '._DB_PREFIX_.'address addr ON addr.id_address = o.id_address_delivery
					LEFT JOIN '._DB_PREFIX_.'country_lang col ON col.id_country = addr.id_country AND col.id_lang = '.pSQL($this->id_lang).'
			LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer = sa.id_customer
				LEFT JOIN '._DB_PREFIX_.'group_lang gl ON gl.id_group = c.id_default_group AND gl.id_lang = '.pSQL($this->id_lang).'
			LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = sad.id_order_detail
				LEFT JOIN '._DB_PREFIX_.'order_detail_kerawen odk ON odk.id_order_detail = od.id_order_detail
				LEFT JOIN '._DB_PREFIX_.'product p ON p.id_product = od.product_id
				LEFT JOIN '._DB_PREFIX_.'product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = od.id_shop
				LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product = p.id_product AND pl.id_shop = ps.id_shop AND pl.id_lang = '.pSQL($this->id_lang).'
				LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
				LEFT JOIN '._DB_PREFIX_.'category_lang cl ON cl.id_category = p.id_category_default AND cl.id_shop = ps.id_shop AND cl.id_lang = '.pSQL($this->id_lang).'
				LEFT JOIN '._DB_PREFIX_.'supplier su ON su.id_supplier = p.id_supplier
				LEFT JOIN '._DB_PREFIX_.'manufacturer ma ON ma.id_manufacturer = p.id_manufacturer
				LEFT JOIN '._DB_PREFIX_.'carrier car ON car.id_carrier = sad.id_carrier
				
			-- LEFT JOIN (
			-- 	SELECT
			-- 		sm.id_order, st.id_product, st.id_product_attribute,
			-- 		SUM(sm.physical_quantity*st.price_te)/SUM(sm.physical_quantity) AS price_te
			-- 	FROM '._DB_PREFIX_.'stock_mvt sm
			-- 	JOIN '._DB_PREFIX_.'stock st ON st.id_stock = sm.id_stock
			-- 	GROUP BY sm.id_order, st.id_product, st.id_product_attribute
			-- ) mvt ON mvt.id_order = o.id_order AND mvt.id_product = od.product_id AND mvt.id_product_attribute = od.product_attribute_id
			
			WHERE sad.gift_card = 0 AND '.$this->filter);
	}


	public function statshour() {
		
		$output = array();
		
		$from = $this->init_from;
		$to = $this->init_to;

		//start period from v 2.1
		$from = $this->db->getValue("
			SELECT IF(MIN(DATE) > '" . pSQL($this->init_from) . "', MIN(DATE), '" . pSQL($this->init_from) . "')
			FROM "._DB_PREFIX_. "kerawen_version
			WHERE STRCMP(VERSION, '2.1') >= 0 AND res = 1"
		);
		
		$refDate = $this->db->getRow("SELECT (DATEDIFF('" . pSQL($to) . "', '" . pSQL($from) . "') + 1 ) AS weeks");
		if ($refDate) {
			if ($refDate['weeks'] > 0) {
				$itemDiv = $refDate['weeks'];
				
				$q = '
					SELECT HOUR(op.date) AS period, SUM(ks.total_ti) AS total_ti, SUM(ks.total_te) AS total_te
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
					LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale ks ON op.id_operation = ks.id_operation
					WHERE '.$this->init_filter.'
					GROUP BY period
					ORDER BY period ASC';
				$data = indexArray($this->db->executeS($q), 'period');
				
				for ($i=0 ; $i<24 ; $i++) {
					$output[] = isset($data[$i]) ?
					array(
						'period' => $data[$i]['period'],
						'total_ti' => $data[$i]['total_ti'] / $itemDiv,
						'total_te' => $data[$i]['total_te'] / $itemDiv
					) :
					array(
						'period' => $i,
						'total_ti' => "0",
						'total_te' => "0"
					);
				}

			}
		}

		return $output;
	}

	
	public function statsweek() {
		
		$output = array();

		$from = $this->init_from;
		$to = $this->init_to;
		
		//start period from v 2.1
		$from = $this->db->getValue("
			SELECT IF(MIN(DATE) > '" . pSQL($this->init_from) . "', MIN(DATE), '" . pSQL($this->init_from) . "')
			FROM "._DB_PREFIX_. "kerawen_version
			WHERE STRCMP(VERSION, '2.1') >= 0 AND res = 1"
		);
		
		$refDate = $this->db->getRow("SELECT (DATEDIFF('" . pSQL($to) . "', '" . pSQL($from) . "') + 1 ) / 7 AS weeks, WEEKDAY('" . pSQL($from) . "') AS wd");
		if ($refDate) {
			//date from < date_to
			if ($refDate['weeks'] > 0) {
				$arrayDiv = array();
				for ($j = 0; $j<7; $j++) {
					$arrayDiv[$j] = max(($j < $refDate['wd']) ? floor($refDate['weeks']) : ceil($refDate['weeks']), 1);
				}
	
				$q = '
					SELECT WEEKDAY(op.date) AS period, SUM(ks.total_ti) AS total_ti, SUM(ks.total_te) AS total_te
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
					LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale ks ON op.id_operation = ks.id_operation
					WHERE '.$this->init_filter.'
					GROUP BY period
					ORDER BY period ASC';
				
				$data =  indexArray($this->db->executeS($q), 'period');
				
				for ($i = 0; $i<7; $i++) {
					$output[] = isset($data[$i]) ? 
						array(
							'period' => $data[$i]['period'], 
							'total_ti' => $data[$i]['total_ti'] / $arrayDiv[$i], 
							'total_te' => $data[$i]['total_te'] / $arrayDiv[$i]
						) :
						array(
							'period' => $i, 
							'total_ti' => "0",
							'total_te' => "0"
						);
				}
			}
		}	
			
		return $output;
	}
	
	
	public function statsmonth() {
		$q = '
			SELECT
				UNIX_TIMESTAMP(DATE_FORMAT(op.date, "%Y-%m-%d")) AS ref,
				DATE_FORMAT(op.date, "%Y-%m-%d") AS period,
				SUM(ks.total_ti) AS total_ti,
				SUM(ks.total_te) AS total_te
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			LEFT JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale ks ON op.id_operation = ks.id_operation
			WHERE '.$this->init_filter.'
			GROUP BY period
			ORDER BY period ASC
		';
		$data =  indexArray($this->db->executeS($q), 'period');
		
		$delta = $this->db->getRow('SELECT UNIX_TIMESTAMP(DATE_FORMAT("' . pSQL($this->init_from) . '", "%Y-%m-%d")) AS ut_from, UNIX_TIMESTAMP(DATE_FORMAT("' . pSQL($this->init_to) . '", "%Y-%m-%d")) AS ut_to');
		$ut_from = $delta['ut_from'];
		$ut_to = $delta['ut_to'];
		
		$output = array();
		while ($ut_from <= $ut_to) {
			$period = date('Y-m-d', $ut_from);
			$output[] = isset($data[$period])
				? array( 'period' => strtotime($period), 'total_ti' => $data[$period]['total_ti'], 'total_te' => $data[$period]['total_te'] )
				: array( 'period' => strtotime($period), 'total_ti' => 0, 'total_te' => 0 );
			$ut_from += 86400;
		}
		return $output;
	}
	
	
	public function cats() {
		return sharedCats();
	}


	public function getAccountingData() {
		$delta = $this->db->getRow('SELECT UNIX_TIMESTAMP(DATE_FORMAT("' . pSQL($this->init_from) . '", "%Y-%m-%d")) AS ut_from, UNIX_TIMESTAMP(DATE_FORMAT("' . pSQL($this->init_to) . '", "%Y-%m-%d")) AS ut_to');
		$ut_from = $delta['ut_from'];
		$ut_to = $delta['ut_to'];
		
		$empty_row = array();
		$empty_row['id'] = '';
		
		// Only taxes used
		$taxes = indexArray($this->db->executeS('
			SELECT DISTINCT id_tax, tax_rate
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail'), 'id_tax');
		foreach($taxes as $tax) {
			$empty_row['HT '.round($tax['tax_rate'],2).' C'] = '';
			$empty_row['HT '.round($tax['tax_rate'],2).' D'] = '';
			$empty_row['TVA '.round($tax['tax_rate'],2).' C'] = '';
			$empty_row['TVA '.round($tax['tax_rate'],2).' D'] = '';
		}

		$empty_row['CA'] = 0.0;
		$empty_row['Encaissement'] = 0.0;
		
		$buf = $this->db->executeS('
			SELECT DISTINCT mode
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment');
		$modes = array();
		foreach($buf as $item) $modes[] = $item['mode'];
		$modes = array_unique(array_merge($modes, array(
			'Carte cadeau', 'Paiement différé', 'Remboursement différé')));
		foreach($modes as $mode) {
			$empty_row[$mode.' C'] = '';
			$empty_row[$mode.' D'] = '';
		}
		
		$output = array();
		// Init empty sub array
		// Display empty days as well
		// 		while ($ut_from <= $ut_to) {
		// 			$empty_row['date'] = date('Y-m-d', $ut_from);
		// 			$output[$ut_from] = $empty_row;
		// 			$ut_from += 86400;
		// 		}
		
		$by = isset($this->vars->by) ?  $this->vars->by : '';
		$periods = $this->report($by, 0, 0);
		if (isset($periods['details'])) {
			foreach($periods['details'] as $key => $item) {
				$output[$key] = $empty_row;
				$output[$key]['id'] = $key;
				if (isset($output[$key])) {
					
					// Products
					if (isset($item['products'])) {
						foreach($item['products'] as &$detail) {
							$cat = round($taxes[$detail['id_tax']]['tax_rate'],2).' '.((int)$detail['sign'] > 0 ? 'C' : 'D');
							$output[$key]['HT '.$cat] = $detail['sign']*$detail['total_te'];
							$output[$key]['TVA '.$cat] = $detail['sign']*($detail['total_ti'] - $detail['total_te']);
							
							$output[$key]['CA'] += $detail['total_ti'];
						}
					}
					
					// Payments
					if (isset($item['payments'])) {
						if (isset($item['payments']['details'])) {
							foreach($item['payments']['details'] as &$detail) {
								$cat = $detail['mode'].' '.((int)$detail['sign'] > 0 ? 'D' : 'C');
								$output[$key][$cat] = $detail['sign']*$detail['amount'];
								
								$output[$key]['Encaissement'] += $detail['amount'];
							}
						}
					}
					if (isset($item['gcards'])) {
						$output[$key]['Carte cadeau'.' '.'C'] = $item['gcards']['amount'];
						$output[$key]['Encaissement'] -= $item['gcards']['amount'];
					}
					if (isset($item['deferred'])) {
						foreach($item['deferred'] as &$detail) {
							$cat = '';
							if ($detail['prod']) {
								if ((int)$detail['sign'] > 0)
									$cat = 'Paiement différé D';
								else
									$cat = 'Remboursement différé C';
							}
							else {
								if ((int)$detail['sign'] > 0)
									$cat = 'Remboursement différé D';
								else
									$cat = 'Paiement différé C';
							}
							$output[$key][$cat] = $detail['sign']*$detail['amount'];
							
							$output[$key]['Encaissement'] += $detail['amount'];
						}
					}
				}
			}
		}
		return array_values($output);
	}
	
	
	public function report($by, $first, $size) {
		require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
		$limit = ($size == 0) ? '' : 'LIMIT '.$first.', '.$size;

		switch ($by) {
			case 'day':
				$group = 'DATE_FORMAT(op.date, "%Y-%m-%d")';
				$index = 'date';
				$info = '
					COUNT(op.id_operation) AS nb_op,
					DATE_FORMAT(op.date, "%Y-%m-%d") AS fdate';
				break;
				
			case 'op':
			default:
				$group = 'op.id_operation';
				$index = 'id_op';
				$info = '
					op.date AS date,
					op.operator_name,
					op.till_name,
					op.PS_SHOP_NAME';
				break;
		}
		
		$total = $this->db->getRow('
			SELECT COUNT(DISTINCT '.$group.') AS count
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			WHERE '.$this->filter);
		
		$total = array_merge($total, $this->db->getRow('
			SELECT
				SUM(sa.total_te) AS total_te,
				SUM(sa.total_ti) AS total_ti,
				SUM(sa.total_ti - sa.total_te) AS total_tax
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
			WHERE '.$this->filter));
		
		$total['taxes'] = $this->db->getRow('
			SELECT
				SUM(sat.total_te) AS total_te,
				SUM(sat.total_ti) AS total_ti,
				SUM(sat.total_ti - sat.total_te) AS total_tax
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax sat ON sat.id_sale = sa.id_sale
			WHERE '.$this->filter);
		
		$total['taxes']['details'] = indexArray($this->db->executeS('
			SELECT
				sat.id_tax AS id_tax,
				sat.tax_rate AS tax_rate,
				SUM(sat.total_te) AS total_te,
				SUM(sat.total_ti) AS total_ti,
				SUM(sat.total_ti - sat.total_te) AS total_tax
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax sat ON sat.id_sale = sa.id_sale
			WHERE '.$this->filter.'
			GROUP BY sat.id_tax'), 'id_tax');
		
		$total['payments'] = $this->db->getRow('
			SELECT SUM(pa.amount) AS amount
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment pa ON pa.id_operation = op.id_operation
			WHERE '.$this->filter);
		
		$total['payments']['details'] = indexArray($this->db->executeS('
			SELECT
				pa.mode AS mode,
				SUM(pa.amount) AS amount
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment pa ON pa.id_operation = op.id_operation
			WHERE '.$this->filter.'
			GROUP BY pa.mode'), 'mode');
		
		$details = $this->db->executeS('
			SELECT '.$group.' AS '.$index.', '.$info.'
			FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
			WHERE '.$this->filter.'
			GROUP BY '.$group.' '.$limit);
		
		if (count($details)) {
			$min = $details[0][$index];
			$max = $details[count($details)-1][$index];
			$this->filter = $this->filter.'
					AND '.$group.' >= "'.$min.'" AND '.$group.' <= "'.$max.'"';
			
			$details = indexArray($details, $index);
			
			$sales = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					SUM(sa.total_te) AS total_te,
					SUM(sa.total_ti) AS total_ti,
					SUM(sa.total_ti - sa.total_te) AS total_tax
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
				WHERE '.$this->filter.'
				GROUP BY '.$group);
			foreach($sales as &$item) {
				$details[$item[$index]] = array_merge($details[$item[$index]], $item);
			}
			
			$taxes = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					SUM(sat.total_te) AS total_te,
					SUM(sat.total_ti) AS total_ti,
					SUM(sat.total_ti - sat.total_te) AS total_tax
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax sat ON sat.id_sale = sa.id_sale
				WHERE '.$this->filter.'
				GROUP BY '.$group);
			foreach($taxes as &$item) {
				$details[$item[$index]]['taxes'] = $item;
				$details[$item[$index]]['taxes']['details'] = array();
			}
			
			$taxes = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					sat.id_tax AS id_tax,
					sat.tax_rate AS tax_rate,
					SUM(sat.total_te) AS total_te,
					SUM(sat.total_ti) AS total_ti,
					SUM(sat.total_ti - sat.total_te) AS total_tax
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_tax sat ON sat.id_sale = sa.id_sale
				WHERE '.$this->filter.'
				GROUP BY '.$group.', sat.id_tax');
			foreach($taxes as &$item) {
				$details[$item[$index]]['taxes']['details'][$item['id_tax']] = $item;
			}
			
			$products = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					sad.id_tax AS id_tax,
					sad.tax_rate AS tax_rate,
					SIGN(sad.total_ti) AS sign,
					SUM(sad.total_te) AS total_te,
					SUM(sad.total_ti) AS total_ti,
					SUM(sad.total_ti - sad.total_te) AS total_tax
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sad ON sad.id_sale = sa.id_sale
				WHERE '.$this->filter.' AND sad.gift_card = 0
				GROUP BY '.$group.', sad.id_tax, SIGN(sad.total_ti)');
			foreach($products as &$item) {
				if (!isset($details[$item[$index]]['products'])) $details[$item[$index]]['products'] = array();
				$details[$item[$index]]['products'][] = $item;
			}
			
			$gcards = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					SUM(sad.total_ti) AS amount
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa ON sa.id_operation = op.id_operation
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sad ON sad.id_sale = sa.id_sale
				WHERE '.$this->filter.' AND sad.gift_card = 1
				GROUP BY '.$group);
			foreach($gcards as &$item) {
				$details[$item[$index]]['gcards'] = $item;
			}
			
			$deferred = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					IF(IFNULL(due.due, 0) = 0, 0, 1) AS prod,
					SIGN(IFNULL(due.due, 0) - IFNULL(paid.paid, 0)) AS sign,
					IFNULL(due.due, 0) - IFNULL(paid.paid, 0) AS amount
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				LEFT JOIN (
					SELECT
						sa.id_operation AS id_operation,
						SUM(sad.total_ti) AS due
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale sa
					JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'sale_detail sad ON sad.id_sale = sa.id_sale
					GROUP BY sa.id_operation) due ON due.id_operation = op.id_operation
				LEFT JOIN (
					SELECT
						pa.id_operation AS id_operation,
						SUM(pa.amount) AS paid
					FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment pa
					GROUP BY pa.id_operation) paid ON paid.id_operation = op.id_operation
				WHERE '.$this->filter.'
				GROUP BY '.$group.', prod, sign
				HAVING amount != 0');
			foreach($deferred as &$item) {
				if (!isset($details[$item[$index]]['deferred'])) $details[$item[$index]]['deferred'] = array();
				$details[$item[$index]]['deferred'][] = $item;
			}
			
			$payments = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					SUM(pa.amount) AS amount
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment pa ON pa.id_operation = op.id_operation
				WHERE '.$this->filter.'
				GROUP BY '.$group);
			foreach($payments as &$item) {
				$details[$item[$index]]['payments'] = $item;
				$details[$item[$index]]['payments']['details'] = array();
			}
			
			$payments = $this->db->executeS('
				SELECT '.$group.' AS '.$index.',
					pa.mode AS mode,
					SIGN(pa.amount) AS sign,
					SUM(pa.amount) AS amount
				FROM '._DB_PREFIX_._KERAWEN_525_PREFIX_.'operation op
				JOIN '._DB_PREFIX_._KERAWEN_525_PREFIX_.'payment pa ON pa.id_operation = op.id_operation
				WHERE '.$this->filter.'
				GROUP BY '.$group.', pa.mode, SIGN(pa.amount)');
			foreach($payments as &$item) {
				$details[$item[$index]]['payments']['details'][] = $item;
			}
		}
		
		return array(
			'total' => $total,
			'details' => $details,
		);
	}
};

