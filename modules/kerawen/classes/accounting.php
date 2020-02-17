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

/* TODO : DELETE LINE IN table `ka_category_account` when a product is deleted from the catalog */
function getAccountancyConfig()
{
	// accountancy config
	$config = new stdClass ();
	$config->customer_gift = '6234';
	$config->taxes = array (
			'1.055' => '44575',
			'1.100' => '445710',
			'1.200' => '44573',
			'default' => '44574'
	); // keys are taxe_rates (with 3 decimals)
	$config->out_taxes = array (
			'1.055' => '44551TVA5.5',
			'1.100' => '44551TVA10',
			'1.200' => '44551TVA20',
			'default' => '44551TVADefault',
	); // keys are taxe_rates (with 3 decimals)
	$config->products = array (
			'12' => 70755,//pdts 5.5%
			'13' => 70710,// pdts 10 %
			'3'  => 70720,// pdts 20 %
			'default' => 7073 //autres (pdts 20%)
	); // keys are id_category
	$config->customers = array (
			'5' => '411VIP',
			'4' => '411Branleurs',
			'default' => '4413',
	); // keys are id_customer_group
	$config->payments = array (
			'Chèque' => '5111Cheques',
			'Ticket Restaurant' => '5111TicketResto',
			'Carte de crédit' => '51115CB',
			'Espèces' => '531Cash',
			'default' => '5115Default',
	); // keys are payment modes - TODO ids rather than strings
	return $config;
}

function getDetailsRecords($from, $to)
{
	$db = Db::getInstance ();
	$sql1 = '
		SELECT
			o.`id_order` AS `id`,
			o.`date_add` AS `date`,
			o.`reference` AS `reference`,
			o.`total_discounts_tax_incl` AS `total_discounts_tax_incl`,
			o.`total_discounts_tax_excl` AS `total_discounts_tax_excl`,
			od.`total_price_tax_excl` AS `amount_tax_excl`,
			od.`total_price_tax_incl` AS `amount_tax_incl`,
			od.`product_id` AS `product_id`,
			cu.`id_default_group` AS `id_cu_group`,
			prod.`id_category_default` AS `id_category`
		FROM `'._DB_PREFIX_.'orders` o
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od
			ON o.`id_order` = od.`id_order`
		LEFT JOIN `'._DB_PREFIX_.'customer` cu
			ON o.`id_customer` = cu.`id_customer`
		LEFT JOIN `'._DB_PREFIX_.'product` prod
		ON od.`product_id` = prod.`id_product`								
		WHERE
			o.`date_add` BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"';
	return $db->executeS($sql1);
	//create an associative array
}

function getSlipsRecords($from, $to)
{
	$db = Db::getInstance ();
	$sql = '
		SELECT
			os.`id_order_slip` AS `id`,
			os.`date_add` AS `date`,
			os.`id_order_slip` AS `reference`,
			osd.`amount_tax_excl` AS `amount_tax_excl`,
			osd.`amount_tax_incl` AS `amount_tax_incl`,
			prod.`id_category_default` AS `id_category`,
			od.`product_id` AS `product_id`,
			cu.`id_default_group` AS `id_cu_group`
		FROM `'._DB_PREFIX_.'order_slip` os
		LEFT JOIN `'._DB_PREFIX_.'order_slip_detail` osd
			ON os.`id_order_slip` = osd.`id_order_slip`
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od
			ON osd.`id_order_detail` = od.`id_order_detail`
		LEFT JOIN `'._DB_PREFIX_.'product` prod
			ON od.`product_id` = prod.`id_product`
		LEFT JOIN `'._DB_PREFIX_.'customer` cu
			ON os.`id_customer` = cu.`id_customer`
		WHERE
			os.`date_add` BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"';
	return $db->executeS($sql);
}

function getPaymentsRecords($from, $to)
{
	// LEFT Join on reference (which might not be unique) --> this is muy bad
	$db = Db::getInstance ();
	$sql = '
		SELECT
			o.`id_order` AS `id`,
			o.`total_paid_tax_incl` AS `total_paid_tax_incl`,
			p.`date_add` AS `date`,
			p.`payment_method` AS `payment_method`,
			p.`amount` AS `amount`,
			p.`order_reference` AS `reference`,
			cu.`id_default_group` AS `id_cu_group`
		FROM `'._DB_PREFIX_.'order_payment` p
		LEFT JOIN `'._DB_PREFIX_.'orders` o
			ON p.`order_reference` = o.`reference`
		LEFT JOIN `'._DB_PREFIX_.'customer` cu
			ON o.`id_customer` = cu.`id_customer`
		WHERE
			p.`date_add` BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"';
	return $db->executeS($sql);
}


function initORec(&$all_recs, $id_order)
{
	if (!isset($all_recs[$id_order]))
	{
		$all_recs[$id_order] = new stdClass();
		$obj = $all_recs[$id_order];
		$obj->details = array();
		$obj->payments = array();
		$obj->slips = array();
	}
	return $all_recs[$id_order];
}
/*
array (size=8)
'id' => string '17' (length=2)
'date' => string '2015-02-16 09:39:31' (length=19)
'reference' => string 'TENDLKVXO' (length=9)
'total_discounts' => string '0.00' (length=4)
'amount_tax_excl' => string '300.000000' (length=10)
'amount_tax_incl' => string '316.500000' (length=10)
'id_cu_group' => string '4' (length=1)
'id_category' => string '12' (length=2)
1 =>
into : records :{details :
*/
function getRecords($from, $to)
{
	$details_records = getDetailsRecords($from, $to);
	$payments_records = getPaymentsRecords($from, $to);
	$returns_records = getSlipsRecords($from, $to);
	$all_recs = array();

	$prod_accounts = getAllProdAccounts();

	foreach ($details_records as $rec)
	{
		$o_rec = initORec($all_recs, $rec['id']);//current order rec
		$rec['prod_account'] = $prod_accounts[''.$rec['product_id']];
		array_push($o_rec->details, $rec);
	}
	foreach ($payments_records as $rec)
	{
		$o_rec = initORec($all_recs, $rec['id']);
		array_push($o_rec->payments, $rec);
	}
	foreach ($returns_records as $rec)
	{
		$o_rec = initORec($all_recs, $rec['id']);
		$rec['prod_account'] = $prod_accounts[''.$rec['product_id']];
		array_push($o_rec->slips, $rec);
	}
	/////////////////////////////////////////////
	/////////////////////////////////////////////
	//////////////////////////////////////////
	//////////////////////

	//var_dump($payments_records);
	return $all_recs;
}

/*
 * EVOL order_cart_rule should have a date column
 */
function getRulesRecords($from, $to)
{
	$db = Db::getInstance ();
	$sql = '
		SELECT
			ocr.`id_order` AS `id`,
			o.`date_add` AS `date`,
			cr.`reduction_amount` AS `amount`,
			osd.`amount_tax_excl` AS `amount_tax_excl`,
			osd.`amount_tax_incl` AS `amount_tax_incl`,
			prod.`id_category_default` AS `id_category`,
			cu.`id_default_group` AS `id_cu_group`
		FROM `'._DB_PREFIX_.'order_cart_rule` ocr
		LEFT JOIN `'._DB_PREFIX_.'order_slip_detail` osd
			ON os.`id_order_slip` = osd.`id_order_slip`
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od
			ON osd.`id_order_detail` = od.`id_order_detail`
		LEFT JOIN `'._DB_PREFIX_.'product` prod
			ON od.`product_id` = prod.`id_product`
		LEFT JOIN `'._DB_PREFIX_.'customer` cu
			ON os.`id_customer` = cu.`id_customer`
		WHERE
			os.`date_add` BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"';
	return $db->executeS($sql);
}



/*fields : id_cat_def name  id_ka_accountancy_product (future)*/
function getProducts()
{
	$db = Db::getInstance ();
	$sql = '
		SELECT
			prod.`id_product` AS `id_product`,
			prod.`id_category_default` AS `id_category`,
			prod_lang.`name` AS `name`
		FROM `'._DB_PREFIX_.'product` prod
		LEFT JOIN `'._DB_PREFIX_.'product_lang` prod_lang
			ON prod.`id_product` = prod_lang.`id_product`';

	return $db->executeS($sql);
}


/*fields : id_cat_def name  id_ka_accountancy_product (future)*/
function getCategories()
{
	$db = Db::getInstance ();
	$sql = '
		SELECT
			cat.`id_category` AS `id`,
			cat.`id_parent` AS `id_parent`,
			cat.`is_root_category` AS `is_root`,
			cat.`level_depth` AS `depth`,
			catl.`name` AS `name`
		FROM `'._DB_PREFIX_.'category` cat
		LEFT JOIN `'._DB_PREFIX_.'category_lang` catl
			ON cat.`id_category` = catl.`id_category`  ';
	return $db->executeS($sql);
}

/*
 *returns an all the "product accounting accounts" (compte produits du PGC) as an associative array
 *id_product =>String account
 */
function getAllProdAccounts()
{
	$ret = array();
	getAllProdAccountsAux(getCatalogTree(), $ret);
	return $ret;
}


/*
 * Helper of the getAllProdAccounts function to implement recursivity
 */
function getAllProdAccountsAux(&$tree, &$prod_array)
{
	if (isset($tree['prods']))
	{
		foreach ($tree['prods'] as &$prod)
		{
			if ($prod['prod_account'] != '')
				$prod_array['id_product'] = $prod['prod_account'];
			else
				$prod_array['id_product'] = $prod['prod_account_default'];
		}
	}
	foreach ($tree['children'] as &$child)
		getAllProdAccountsAux($child, $prod_array);
}

function getCatalogTree()
{
	// algo :  for each category -> create its direct children list
	$cats = getCategories();
	$prods = getProducts();
	//var_dump($prods);
	$id_root = -1;
	$cat_ass = array();// associative array
	foreach ($cats as $cat)
	{
		//create (direct) childrenIds field
		//and look for root category
		$children_ids = array();
		$cat_prods = array();
		$id = $cat['id'];
		if ($cat['is_root'])
			$id_root = $id;
		foreach ($cats as &$cat2)
		{
			if ($cat2['id_parent'] == $id)
				$children_ids[] = $cat2['id'];
		}
		$cat['children_ids'] = $children_ids;
		//push products inside category
		foreach ($prods as &$prod)
		{
			if ($prod['id_category'] == $id)
				$cat_prods[] = $prod;

		}
		$cat['prods'] = $cat_prods;
		$cat_ass[''.$id] = $cat;
	}

	//reorganize as tree
	require_once (_KERAWEN_CLASS_DIR_.'/../api/constants.php');
	$prod_account_cat_part = getCategoriesAccount();
	$prod_account_prod_part = getProductsAccount();
	return createCategoryBranch($id_root, _KERAWEN_DEFAULT_PRODUCT_ACCOUNT_, $prod_account_cat_part, $prod_account_prod_part, $cat_ass);

	//echo $id_root;
	//var_dump (createCategoryBranch($id_root,$cat_ass));
}

/*
 * recursive method to create the catalog tree
 */
function createCategoryBranch($id, $default_account, &$prod_account_cat_part, &$prod_account_prod_part, &$cat_ass)
{
	$me = $cat_ass[''.$id];

	//recursion invariant :
	// The prod_account_default field is the father's prod_account (or its prod_account_default if its prod_account is empty)
	//the father's prod account is the argument $default_account

	$me['prod_account_default'] = $default_account;

	//if the  category has a specified "product accounting account" (i.e) it exists and is not empty, it will be the
	//children's default value
	if (isset($prod_account_cat_part[''.$id]) && $prod_account_cat_part[''.$id] != '')
	{
		$me['prod_account'] = $prod_account_cat_part[''.$id];
		$default_account = $me['prod_account'];
	}
	else
		$me['prod_account'] = '';

	//check if products have a specified "product accounting account"
	foreach ($me['prods'] as &$prod)
	{
		if (isset($prod_account_prod_part[''.$prod['id_product']]))
			$prod['prod_account'] = $prod_account_prod_part[''.$prod['id_product']];
		else
			$prod['prod_account'] = '';

		$prod['prod_account_default'] = $default_account;
	}
	if (!empty($me['children_ids']) || !empty($me['prods']))
	{
		$children_array = array();
		foreach ($me['children_ids'] as $c_id)
		{
			//recursive call
			$child = createCategoryBranch($c_id, $default_account, $prod_account_cat_part, $prod_account_prod_part, $cat_ass);
			if (!empty($child))
				$children_array[] = $child;
		}
		$me['children'] = $children_array;
		unset($me['children_ids']);
		unset($me['is_root']);
		unset($me['id_parent']);
		return $me;
	}
	else
		return array();
}


/*
 * returns the product accounting account (707) associated to some of the categories (only those specified by the accounting officer)
 * as an associative array id=>"accountString"
 */
function getCategoriesAccount()
{
	$db = Db::getInstance ();
	$ret = array();
	$cats = $db->executeS(' SELECT `id` AS `id`, `product_account` AS `account` FROM `ka_product_account` WHERE is_category = 1 ');

	foreach ($cats as &$cat)
		$ret[$cat['id']] = $cat['account'];

	return $ret;
}
/*
 * returns the product accounting account (707) associated to some of the products (only those specified by the accounting officer)
 *  as an associative array id=>"accountString"
 */
function getProductsAccount()
{
	$db = Db::getInstance ();
	$ret = array();
	$prods = $db->executeS(' SELECT `id` AS `id`, `product_account` AS `account` FROM `ka_product_account` WHERE is_category = 0');
	foreach ($prods as &$prod)
		$ret[$prod['id']] = $prod['account'];

	return $ret;
}


function setAccountForCategoryOrAccount($is_category, $id, $account)
{
	$db = Db::getInstance ();
	$sql = 'INSERT INTO `ka_product_account`
					(`is_category`, `id`,`product_account`)
				VALUES ('.pSql($is_category).','.pSql($id).', "'.pSql($account).'")
				ON DUPLICATE KEY UPDATE
					`product_account` = VALUES(product_account)';
	//var_dump($sql);
	$db->execute($sql);
}

function getAccountingConfig()
{
	//Get taxes
	$taxes = array();
	$tt = Tax::getTaxes(Context::getContext()->language->id);
	foreach ($tt as &$t)
	{
		$taxes[$t['id_tax']] = array(
				'id' => (int)$t['id_tax'],
				'name' => $t['name'],
				'rate' => (double)$t['rate']
		);
	}
}

/*
function getLoyaltyRecords($from, $to)
{
	$db = Db::getInstance ();
	$sql = '
		SELECT
			os.`loyalty` AS `date`,
			osd.`amount_tax_excl` AS `amount_tax_excl`,
			osd.`amount_tax_incl` AS `amount_tax_incl`,
			o.`refund_tax_incl` AS `refund_tax_incl`,
			ret.`refund_tax_excl` AS `refund_tax_excl`,
			ret.`tax_rate` AS `tax_rate`
		FROM `'. _DB_PREFIX_ . 'return_kerawen` ret
		WHERE
			ret.`date` BETWEEN "' . pSQL ( $from ) . '" AND "' . pSQL ( $to ) . '"
			AND `took_effect` = 1
					';
	$returns_records = $db->executeS( $sql );
	// var_dump($sql1,$orderRecords);
	return $returns_records;
}

cat.`id_category` AS `id_category`
cat.`id_category` AS `id_category`
LEFT JOIN `'._DB_PREFIX_.'category_product` cat
ON od.`id_product` = cat.`id_product`
*/
