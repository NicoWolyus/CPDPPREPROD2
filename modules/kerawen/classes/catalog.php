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

function getCategories($context)
{
	// Get all categories, even inactive ones
	$all = Category::getCategories($context->language->id, false);
	$id_root = Category::getRootCategory()->id;
	return getSubCategories($id_root, $all);
}

function getSubCategories($id_cat, &$all)
{
	$res = array();
	if (isset($all[$id_cat]))
	{
		$children = $all[$id_cat];
		foreach ($children as $id_child => $cat)
		{
			$res[] = array(
				'id' => $id_child,
				'name' => $cat['infos']['name'],
			);
			$res = array_merge($res, getSubCategories($id_child, $all));
		}
	}
	return $res;
}


function getProducts($context, $cat, $id_shop, $id_lang, $id_customer = 0, $id_group = 1, $from = 0, $count = 0)
{
	$prods = array();
	$total = 0;
	
	//???? or make specific query to get url ? -> if others data required (stock type, warehouse list)
	$v = 'AdminProducts';
	$token_key = Tools::getAdminToken($v.(int)(Tab::getIdFromClassName($v)).(int)($context->employee->id));
	$token_url = Dispatcher::getInstance()->createUrl($v, $context->language->id, array('token' => $token_key), false);


	if ($id_shop)
	{
		$db = Db::getInstance();
		
		$pp = $db->executeS('
			SELECT SQL_CALC_FOUND_ROWS
				cp.id_product AS id
			FROM '._DB_PREFIX_.'category_product cp
			JOIN '._DB_PREFIX_.'product_shop ps
				ON ps.id_product = cp.id_product
			WHERE
				cp.id_category = '.pSQL($cat->id).'
				AND ps.id_shop = '.pSQL($id_shop).'
				AND ps.active = 1
			ORDER BY cp.position ASC
			'.($count ? 'LIMIT '.pSQL($from).','.pSQL($count) : ''));
		$total = $db->getValue('SELECT FOUND_ROWS()');
				
		foreach ($pp as &$p)
		{
			$id_prod = $p['id'];
			$prod = new Product($id_prod, true, $id_lang, $id_shop);
			if ($prod->id) {
				
				$id_shop_link = $prod->id_shop_default;
				//$id_shop_link = $id_shop;
				
				$frontend_url = $context->link->getPageLink('product', null, $id_lang, array('id_product' => $id_prod), false, $id_shop_link, false);
				$backend_url = $token_url . '&updateproduct&id_product=' . $id_prod;
				$detail = detailProduct($prod, $id_lang, false, $id_customer, $id_group, $backend_url, $frontend_url);
				if ($detail) $prods[] = $detail;
			}
		}
	}
	return array(
		'prods' => $prods,
		'total' => $total,
	);
}


function detailProduct($prod, $id_lang, $with_attributes = true, $id_customer = 0, $id_group = 1, $backend_url = '', $frontend_url = '')
{
	static $totswitchattribute = null;
	if ($totswitchattribute === null) {
		$totswitchattribute = Module::isInstalled('totswitchattribute');
	}
	
	if(!isset($prod->id)) {
		return false;
	}

	static $link = null;
	if (!$link) $link = new Link();
	
	if ($id_customer === 0) {
		
		$id_customer = (int) Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER');
		$anonymous_customer = new Customer($id_customer);
		if ($anonymous_customer) {
			$id_group = $anonymous_customer->id_default_group;
		}
		
	}
	
	$display_method = Group::getPriceDisplayMethod($id_group);
	
	$ReductionByIdGroup = Group::getReductionByIdGroup($id_group);
	$rate_group = 1 - $ReductionByIdGroup/100;

	$attrs = (boolean)Db::getInstance()->getValue('SELECT EXISTS(
		SELECT 1 FROM '._DB_PREFIX_.'product_attribute
		WHERE id_product = '.pSQL($prod->id).')');
	$attr_groups = $prod->getAttributesGroups($id_lang);
	
	/*
	echo '<pre>';
	print_r($attr_groups);
	echo '</pre>';
	*/
	
	if ($totswitchattribute) {
		if ($attrs && !count($attr_groups)) {
			// All combinations deactivated
			return false;
		}
	}
	
	$context = Context::getContext();
	$id_shop = $context->shop->id;
	$id_currency = $context->currency->id;
	$id_country = $context->country->id;		

	$options = array();
	$versions = array();
	
	$prefix = $context->currency->prefix; 
    $suffix = $context->currency->suffix;
	require_once(_KERAWEN_CLASS_DIR_.'/price.php');

    if ($attrs && $with_attributes)
	{
		foreach ($attr_groups as &$group)
		{
			$id_opt = $group['id_attribute_group'];
			if (!isset($options[$id_opt]))
				$options[$id_opt] = array(
					'id' => $id_opt,
					'name' => $group['group_name'],
					'color' => ($group['is_color_group'] != 0),
					'vals' => array(),
				);
	
			$id_val = $group['id_attribute'];
			$options[$id_opt]['vals'][$id_val] = array(
				'id' => $id_val,
				'name' => $group['attribute_name'],
				'color' => $group['attribute_color'],
			);
	
			$id_ver = $group['id_product_attribute'];
			if (!isset($versions[$id_ver])) {
			    $specific = SpecificPrice::getSpecificPrice($prod->id, $id_shop, $id_currency, $id_country, $id_group, 1, $id_ver, $id_customer);
			    $init_price_te = $prod->getPriceStatic($prod->id, false, $id_ver, 2, null, false, false, 1, false, $id_customer);
				
				//standard stock - advanced stock ?
				$wholesale_price = $prod->wholesale_price;
				$combination = new Combination($id_ver);
				if ($combination && $combination->wholesale_price != '0.000000') {
					$wholesale_price = $combination->wholesale_price;
				}

				$warehouses = array();
				if ($prod->depends_on_stock) {
					$warehouses = Warehouse::getProductWarehouseList($prod->id, $id_ver);
					foreach ($warehouses as &$value) {
	    				$manager = StockManagerFactory::getManager() ;
	    				$value['quantity'] = $manager->getProductRealQuantities( $prod->id , $id_ver, $value['id_warehouse'], true);
						unset($value['name']);
					}
				}
				
				$ean = Db::getInstance()->getValue('
					SELECT ean13  
					FROM '._DB_PREFIX_.'product_attribute pa
					JOIN '._DB_PREFIX_.'product_attribute_shop pas
						ON pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = '.pSQL($id_shop).'
					WHERE pa.id_product = '.pSQL($prod->id).'
					AND pa.id_product_attribute = '.pSQL($id_ver));

				$versions[$id_ver] = array(
					'id' => $id_ver,
					'name' => '',
					'ref' => $group['reference'],
					'stock' => Product::getQuantity($prod->id, $id_ver),
					'price' => $prod->getPrice(true, $id_ver, 2),
					'price_te' => $prod->getPrice(false, $id_ver, 2),
					'init_price' => $prod->getPriceStatic($prod->id, true, $id_ver, 2, null, false, false, 1, false, $id_customer),
					'init_price_te' =>  $init_price_te,
					'specific_type' => specificType($specific),
					'vals' => array(),
					'margin' =>	($init_price_te > 0) ? ($init_price_te - $wholesale_price) : '',
					'wholesale_price' => $wholesale_price,
					'entrepots' => $warehouses,
					'ean' => $ean 
				);
			}

			$versions[$id_ver]['vals'][$id_opt] = $id_val;
			$versions[$id_ver]['name'] .= ($versions[$id_ver]['name'] ? ', ' : '').$group['attribute_name'];

		}
		
		// Ensure attributes have been found
		if (!count($versions)) $attrs = false;
	}
	
	$custom = $prod->getCustomizationFields($id_lang, $id_shop);
	
	$cover = Product::getCover($prod->id);
	$image_type = Configuration::get('KERAWEN_IMAGE_PRODUCT');
	//valois vintage
	//TODO : need to improve conditions
	if (isset($cover['id_image']) && Tools::version_compare(_PS_VERSION_, '1.6.0.14', '=') && Configuration::get('PS_LEGACY_IMAGES')) {
		$cover['id_image'] = $prod->id . "-" . $cover['id_image'];
	}
	
	$img = isset($cover['id_image']) && $image_type ? '//'.$link->getImageLink($prod->link_rewrite, $cover['id_image'], $image_type) : null;
	
	// Prices computation: cart independent
	// Force attribute to 0 to counter cache issue in SpecificPrice
	$price_ti = Product::priceCalculation(
		$id_shop, $prod->id, 0, $id_country, null, null, $id_currency,
		$id_group, 1, true, 2, false, true, true, $specific, true, $id_customer, true, 0, 0);
	$price_te = Product::priceCalculation(
		$id_shop, $prod->id, 0, $id_country, null, null, $id_currency,
		$id_group, 1, false, 2, false, true, true, $specific, true, $id_customer, true, 0, 0);

	$init_price_te = $prod->getPriceStatic($prod->id, false, null, 2, null, false, false, 1, false, $id_customer);
	$init_price = $prod->getPriceStatic($prod->id, true,  null, 2, null, false, false, 1, false, $id_customer);

	
	$specificType = specificType($specific);
	$rate = is_numeric($prod->tax_rate) ? (float) $prod->tax_rate : 0;
	
	$wm = getProductWeightsAndMeasures($prod->id, true);
	if ($wm) {
	    
	    if (isset($wm['codes'])) {
	        if (isset($wm['codes'][0])) {
	        
	            $init_price = ($init_price + $wm['codes'][0]['unit_price']) * $rate_group;
	            $init_price_te = ($init_price_te + $wm['codes'][0]['unit_price_te']) * $rate_group;
	            
	            $price_ti = $init_price;
	            $price_te = $init_price_te;
	            
	            if ($specificType) {
	                if ($specificType == 'PERCENT') {
	                    $price_ti *= (1 - $specific['reduction']);
	                    $price_te *= (1 - $specific['reduction']);
	                } else {
	                    if ($specific['reduction_tax']) {
	                        $price_ti -= $specific['reduction'] * $rate_group;
	                        $price_te = $price_ti / ($rate/100 +1 );
	                    } else {
	                        $price_te -= $specific['reduction'] * $rate_group;
	                        $price_ti = $price_te * ($rate/100 + 1);
	                    }
	                }
	            }
	        }
	    }
	}
	
	$warehouses = array();
	$ean = '';
	$description = '';
	$description_short = '';
	
	if ($with_attributes) {
		$ean = $prod->ean13;
		$description = $prod->description;
		$description_short = $prod->description_short;
		if ($prod->depends_on_stock && !$attrs) {
			$warehouses = Warehouse::getProductWarehouseList($prod->id, 0);
			foreach ($warehouses as &$value) {
		    	$manager = StockManagerFactory::getManager() ;
		    	$value['quantity'] = $manager->getProductRealQuantities( $prod->id , 0, $value['id_warehouse'] , true );
				unset($value['name']);
			}
		}
	}

	return array(
		'id' => $prod->id,
		'active' => (boolean)$prod->active,
		'name' => $prod->name,
		'ref' => $prod->reference,
		'stock' => $prod->quantity,
		'price' => $price_ti,
		'price_te' => $price_te,
	    'init_price' => $init_price,
		'init_price_te' => $init_price_te,
	    'specific_type' => $specificType,
		'specific' => $specific,
		'img' => $img,
		'attrs' => $attrs,
		'options' => $options,
		'versions' => $versions,
		'def' => $prod->getDefaultIdProductAttribute(),
	    'wm' => $wm,
		'custom' => $custom,
		'backend_url' => $backend_url,
		'frontend_url' => $frontend_url,
		'margin' =>	($init_price_te > 0) ? ($init_price_te - $prod->wholesale_price) : '',
		'wholesale_price' => $prod->wholesale_price,
	    'rate' => $rate,
		'display_method' => (int) $display_method,
		'dos' => $prod->depends_on_stock,
		'entrepots' => $warehouses,
		'ean' => $ean,
		'description' => $description,
		'description_short' => $description_short,
	);
}


function specificType($specific) {

 	$specific_type = false;
	
	if ($specific) {
  
  		if ($specific['reduction_type'] == 'amount') {
			$specific_type = 'AMOUNT';
		} else {
			$specific_type = 'PERCENT';
		}
  
	}

	return $specific_type;

}



/* TO REFACTOR */
function browseCategory($context, $params)
{
	static $link = null;
	if (!$link) $link = new Link();

	$id_cat = $params->id_cat;
	$id_shop = $params->id_shop;
	$id_lang = $context->language->id;

	$id_group = $context->group->id;
	$id_customer = (int) $context->customer->id;

	if (!$id_cat)
		$id_cat = Category::getRootCategory($id_lang, $context->shop)->id;
	$cat = new Category($id_cat, $id_lang);

	$path = array();
	$buf = $cat;
	while (!$buf->is_root_category) {
		array_unshift($path, array(
			'id' => $buf->id,
			'name' => $buf->name,
		));
		$buf = new Category($buf->id_parent, $id_lang);
	}
	
	// Set shop context before recovering children
	Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);

	$image_type = Configuration::get('KERAWEN_IMAGE_CATEGORY');
	$cc = Category::getChildren($id_cat, $id_lang, false, $id_shop);
	
	$categories = array();
	foreach ($cc as &$c)
	{
		$cat_img = $image_type ? '//'.$link->getCatImageLink($c['link_rewrite'], $c['id_category'], $image_type) : false;
		$categories[] = array(
			'id' => $c['id_category'],
			'name' => $c['name'],
			'img' => $cat_img,
		);
	}

	$shortcuts = array();
	$ids = Configuration::get('KERAWEN_SHORTCUT_CATEGORIES');
	if ($ids) $shortcuts = Db::getInstance()->executeS('
		SELECT
			cl.id_category AS id,
			cl.name AS name
		FROM '._DB_PREFIX_.'category_lang cl
		JOIN '._DB_PREFIX_.'category_shop cs
			ON cs.id_category = cl.id_category AND cs.id_shop = cl.id_shop
		WHERE
			cl.id_category IN ('.pSQL($ids).')
			AND cs.id_shop = '.pSQL($id_shop).'
			AND cl.id_lang = '.pSQL($id_lang));
	
	return array(
		'shorts' => $shortcuts,
		'path' => $path,
		'cats' => $categories,
		'prods' => getProducts($context, $cat, $id_shop, $id_lang, $id_customer, $id_group, $params->from, $params->count),
	);
}

function searchProduct($context, $params)
{
	
	//???? or make specific query to get url ? -> if others data required (stock type, warehouse list)
	$v = 'AdminProducts';
	$token_key = Tools::getAdminToken($v.(int)(Tab::getIdFromClassName($v)).(int)($context->employee->id));
	$token_url = Dispatcher::getInstance()->createUrl($v, $context->language->id, array('token' => $token_key), false);	
	
	$term = $params->term;
	$from = $params->from;
	$count = $params->count;
	$id_lang = $context->language->id;
	$id_shop = $context->shop->id;

	$id_group = (isset($context->group)) ? $context->group->id : 1;
	$id_customer = (int) $context->customer->id;

	// Alternative 1: BO search
// 	$pp = Product::searchByName($id_lang, $term, $context);
	
	// Alternative 2: FO search
// 	$term = Tools::replaceAccentedChars($term);
// 	$pp = Search::find($id_lang, $term, 1, 100, 'position', 'desc', true);
	
	// Alternative 3: Dedicated search
	$db = Db::getInstance();
	
	$where = array();
	foreach (preg_split('/[\s]+/', str_replace('"', '\\"', $term)) as $str)
	if (Tools::strlen($str)) $where[] = 'data LIKE "%'.$str.'%"';
	
	$pp = $db->executeS('
		SELECT SQL_CALC_FOUND_ROWS DISTINCT id_product
		FROM '._DB_PREFIX_.'product_search_kerawen
		WHERE id_shop = '.pSQL($id_shop).'
			AND id_lang = '.pSQL($id_lang).'
			AND '.implode(' AND ', $where).'
		'.($count ? 'LIMIT '.pSQL($from).','.pSQL($count) : ''));
	$total = $db->getValue('SELECT FOUND_ROWS()');

	//$base = $shop->domain . $shop->getBaseURI();
	
	$products = array();
	if ($pp)
		foreach ($pp as &$p)
		{
			$prod = new Product($p['id_product'], true, $id_lang, $id_shop);
			if ($prod->active) {
				
				$id_shop_link = $prod->id_shop_default;
				//$id_shop_link = $id_shop;

				$frontend_url = $context->link->getPageLink('product', null, $id_lang, array('id_product' => $prod->id), false, $id_shop_link, false);
				$backend_url = $token_url . '&updateproduct&id_product=' . $prod->id;
				$detail = detailProduct($prod, $id_lang, false, $id_customer, $id_group, $backend_url, $frontend_url);
				if ($detail) $products[] = $detail;
			}
		}
	return array(
		'prods' => $products,
		'total' => $total,
	);
}

function createProduct($data)
{
	$db = Db::getInstance();
	$context = Context::getContext();
	$id_lang = $context->language->id;
	$current_shop = $context->shop;

	// Backup shop context
	require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
	$backup = backupShopContext();
	
	// For all shops
	Shop::setContext(Shop::CONTEXT_ALL);

	$prod = new Product(null, false, null, null);
	$prod->name = getForLanguages($data->name);
	$prod->reference = 'NEW';
	$prod->visibility = 'none';
	$prod->ean13 = $data->code;
	$prod->reference = $data->reference;
	$prod->id_tax_rules_group = $data->id_taxrule;
	$prod->price = round($data->price / (1.0 + $prod->getTaxesRate() / 100.0), 6);
	$prod->wholesale_price = $data->wholesale_price;
	$prod->link_rewrite = getForLanguages(Tools::link_rewrite($data->name));
	$prod->id_category_default = $current_shop->getCategory();
	$prod->date_add = date('Y-m-d H:i:s');
	$prod->date_upd = date('Y-m-d H:i:s');
	if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && Configuration::get('PS_FORCE_ASM_NEW_PRODUCT')) {
		$prod->advanced_stock_management = 1;
    	$prod->depends_on_stock = 1;
	}

	$prod->save();
	$id_prod = $prod->id;
	
	// Init stock
	$id_warehouse = 0;
	if ($prod->advanced_stock_management) {
		$id_warehouse = (int)Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT');
		if ($id_warehouse) {
			$loc = new WarehouseProductLocation();
			$loc->id_product = $id_prod;
			$loc->id_product_attribute = 0;
			$loc->id_warehouse = $id_warehouse;
			$loc->location = pSQL('');
			$loc->save();
		}
	}
	
	// For each shop
	$shops = Db::getInstance()->executeS('
		SELECT id_shop FROM '._DB_PREFIX_.'shop WHERE active = 1');
	foreach ($shops as &$s)
	{
		$id_shop = $s['id_shop'];
		$prod->associateTo((int)$id_shop);
		
		$shop = new Shop((int)$id_shop);
		Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
		$id_cat = $shop->getCategory();

		$ps = new Product($id_prod, false, null, $id_shop);
		$ps->category_default = $id_cat;
		$ps->save();

    	StockAvailable::setProductDependsOnStock($id_prod, $prod->depends_on_stock, $id_shop);

		try
		{
			$db->insert('category_product', array(
				'id_category' => $id_cat,
				'id_product' => $id_prod,
				'position' => $db->getValue('
					SELECT MAX(`position`)
					FROM `'._DB_PREFIX_.'category_product`
					WHERE `id_category` = '.pSQL($id_cat)),
			));
		}
		catch (Exception $e)
		{
			//I'm laid back
		}
	}
	
	// Restore shop context
	restoreShopContext($backup);
	$id_shop = $current_shop->id;
	
	// Set initial quantity
	if ($id_warehouse) {
		$stock = new Stock();
		$stock->id_warehouse = $id_warehouse;
		$stock->id_product = $id_prod;
		$stock->id_product_attribute = 0;
		$stock->reference = $prod->reference;
		$stock->ean13 = $prod->ean13;
		$stock->upc = $prod->upc;
		$stock->physical_quantity = 0;
		$stock->usable_quantity = 0;
		$stock->price_te = 0.001;
		$stock->save();
		
		require_once(_KERAWEN_CLASS_DIR_.'/stock.php');
        $id_reason = Configuration::get('PS_STOCK_MVT_INC_REASON_DEFAULT');
		injectStock($id_prod, 0, $id_warehouse, $id_shop, $id_reason, $data->qty, 0);
	}
	else {
		StockAvailable::setQuantity($id_prod, 0, $data->qty, $id_shop);
	}
	
	return $prod;
}


function createProduct2($context, $params, &$response)
{
	$prod = $params->product;
	$id_lang = $context->language->id;
	$id_shop = null;
	$context = null;
	//Variation Mofidication
	if (isset($prod->id_pa) && $prod->id_pa != 0 && isset($prod->id) && $prod->id != 0)
	{
		//Combination modification only (not creation)

		$c = new Combination($prod->id_pa, null, $id_shop);//EVOL add id shop;
		$p = new Product($prod->id, false, $id_lang, $id_shop, $context);//not fully loaded

		$c->price = $prod->base - $p->price;
		if ($prod->ecotax == $p->ecotax)
			$c->ecotax = 0;
		else
		{
			if ($prod->ecotax == 0 && $p->ecotax != 0)
				$c->ecotax = '0.000001';//forcing the combination ecotax to 0
			else
				$c->ecotax = $prod->ecotax;
			$c->price += Tools::ps_round(($prod->ecotax - $p->ecotax) * (1 + Tax::getProductEcotaxRate() / 100) / (1 + $p->getTaxesRate() / 100), 6);
		}
		$c->ean13 = $prod->code;
		$c->reference = $prod->ref;
		$c->wholesale_price = Tools::ps_round(isset($prod->wholesale) ? $prod->wholesale - $p->wholesale_price :0, 6);
		$c->save();
		// 		var_dump($c);
		// 		die();
		//EVOL add price
	}
	//Product
	else
	{
		$p = new Product(isset($prod->id)?$prod->id:null, false, $id_lang);
		$p->name = $prod->name;
		$p->reference = $prod->ref;
		$p->visibility = isset($prod->visibility) ? $prod->visibility : 'none';
		$p->ean13 = $prod->code;
		$p->id_tax_rules_group = $prod->id_taxrule;
		$p->ecotax = Tools::ps_round(isset($prod->ecotax) ? $prod->ecotax :0, 6);
		$p->wholesale_price = Tools::ps_round(isset($prod->wholesale) ? $prod->wholesale :0, 6);
		//EVOL do it this way (for register as well)
		$p->price = Tools::ps_round($prod->base, 6);
		// in the form ecotax is included in the price
		//$p->price = Tools::ps_round(+$prod->price / (1.0 + $p->getTaxesRate() / 100.0 - (+$p->ecotax)), 6)  ;
		$p->link_rewrite = Tools::link_rewrite($p->name);
		// 		var_dump($p);
		// 		die();
		$p->save();
		
		//sets quantity only in the current shop (context)
		if (isset($prod->qty))
			StockAvailable::setQuantity($p->id, 0, $prod->qty);
		//StockAvailable::setProductOutOfStock($p->id, true);

		// Attach to root category at last position (if new product)
		if (!isset($prod->id))
		{
			$id_cat = Category::getRootCategory()->id;
			$db = Db::getInstance();
			$db->insert('category_product', array(
					'id_category' => $id_cat,
					'id_product' => $p->id,
					'position' => $db->getValue('
			SELECT MAX(`position`)
			FROM `'._DB_PREFIX_.'category_product`
			WHERE `id_category` = '.$id_cat),
			));
		}
		$response->addResult('prod', detailProduct($p, $id_lang, true));
	}
}

function getProductWeightsAndMeasures($id_prod, $with_tax = false)
{
	if ($id_prod){
		$db = Db::getInstance();
		$wm = $db->getRow('
			SELECT * FROM '._DB_PREFIX_.'product_wm_kerawen
			WHERE id_product = '.pSQL($id_prod));
		
		if ($wm)
		{
			$wm['codes'] = $db->executeS('
				SELECT * FROM '._DB_PREFIX_.'product_wm_code_kerawen
				WHERE id_product = '.pSQL($id_prod));
			
			if ($with_tax) {
				$context = Context::getContext();
				require_once(dirname(__FILE__).'/data.php');
				$address = getDefaultDeliveryAddress(true);
				$tax_manager = TaxManagerFactory::getManager($address, Product::getIdTaxRulesGroupByIdProduct((int)$id_prod, $context));
				$tax_calculator = $tax_manager->getTaxCalculator();
				
				foreach($wm['codes'] as &$code) {
					$code['unit_price_te'] = $code['unit_price'];
					$code['unit_price'] = $tax_calculator->addTaxes((float)$code['unit_price_te']);
				}
			}
		}
		
		return $wm;
	}
	else return array();
}


function createBareCode($mask = '20xxxxxxxxxx') {

	$db = Db::getInstance();
	
	$barcode = 'xxxxxxxxxxxx';
	for ($k = 0; $k < 12; $k++) {
		if ($mask[$k] == 'x')
			$barcode[$k] = mt_rand(0, 9);
		else
			$barcode[$k] = $mask[$k];
	}
	
	//check digit
	$barcode .= ean_checkdigit($barcode);
	
	$q = "
		SELECT
			product.id_product
		FROM " . _DB_PREFIX_ . "product product
		LEFT JOIN " . _DB_PREFIX_ . "product_attribute product_attribute ON product.id_product = product_attribute.id_product
		WHERE
			product.ean13 = " . pSQL($barcode) . "
		OR
			product_attribute.ean13 = " . pSQL($barcode) . "
		";

	$res = $db->executeS($q);	
	if ($res) {
		return createBareCode($mask);
	}
	
	return $barcode;
}


function ean_checkdigit($code) {
	$sum = 0;
	$weightflag = true;
	for ($i = strlen($code) - 1; $i >= 0; $i--) {
	    $sum += (int)$code[$i] * ($weightflag?3:1);
	    $weightflag = !$weightflag;
	}
	
	if (($sum % 10) == 0)  { return 0; } 
	    
	$vis = (int)($sum / 10);
	$sup = ($vis + 1) * 10;
	return ($sup - $sum); 
}
