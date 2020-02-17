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

function applyStock($stock) {
	foreach ($stock as $key => $item) {
		//advanced stock
		if ($item->stockType) {
			if ($item->id_reason == 4 || $item->id_reason == 1) {
				injectStock($item->id_prod, $item->id_attr, $item->id_warehouse, $item->id_shop, $item->id_reason, $item->quantity, 0);
			} else {
				removeAdvStock($item->id_prod, $item->id_attr, $item->id_warehouse, $item->quantity, $item->id_reason);
			}
		//standard stock
		} else {
			StockAvailable::setQuantity($item->id_prod, $item->id_attr, $item->quantity, $item->id_shop);
		}
	}
}


function injectStock($id_prod, $id_attr, $id_warehouse, $id_shop, $id_reason, $quantity, $price)
{
	$db = Db::getInstance();
	$manager = StockManagerFactory::getManager();
	
	// Determine price
	if ($price <= 0) {
		$comb = new Combination($id_attr);
		$price = (float)$comb->wholesale_price;
	}
	if ($price <= 0) {
		$prod = new Product($id_prod);
		$price = (float)$prod->wholesale_price;
	}
	if ($price <= 0) {
		$price = (float)$db->getValue('
			SELECT price_te FROM '._DB_PREFIX_.'stock
			WHERE id_warehouse = '.pSQL($id_warehouse).'
			AND id_product = '.pSQL($id_prod).'
			AND id_product_attribute = '.pSQL($id_attr).'
			ORDER BY id_stock DESC');
	}
	if ($price <= 0) {
		$price = 0.001;
	}
	
	$manager->addProduct(
		$id_prod,
		$id_attr,
		new Warehouse($id_warehouse),
		$quantity,
		$id_reason,
		$price,
		true
	);
	StockAvailable::synchronize($id_prod);
}


function removeAdvStock($id_prod, $id_attr, $id_warehouse, $quantity, $id_reason) {
	$db = Db::getInstance();
	$manager = StockManagerFactory::getManager();
	$manager->removeProduct($id_prod, $id_attr, new Warehouse($id_warehouse), $quantity, $id_reason); 
	StockAvailable::synchronize($id_prod);
}



function getStockShippingReason() {
	$id = Configuration::get('KERAWEN_STOCK_SHIPPING');
	$reason = $id ? new StockMvtReason($id) : null;
	if (!Validate::isLoadedObject($reason)) {
		$reason = new StockMvtReason();
		$reason->name = getForLanguages(Context::getContext()->module->l('Regulation before shipping', pathinfo(__FILE__, PATHINFO_FILENAME)));
		$reason->sign = +1;
		$reason->save();
		Configuration::updateValue('KERAWEN_STOCK_SHIPPING', $reason->id);
	}
	return $reason->id;
}

function getStockReturnReason() {
	$id = Configuration::get('KERAWEN_STOCK_RETURN');
	$reason = $id ? new StockMvtReason($id) : null;
	if (!Validate::isLoadedObject($reason)) {
		$reason = new StockMvtReason();
		$reason->name = getForLanguages(Context::getContext()->module->l('Customer return', pathinfo(__FILE__, PATHINFO_FILENAME)));
		$reason->sign = +1;
		$reason->save();
		Configuration::updateValue('KERAWEN_STOCK_RETURN', $reason->id);
	}
	return $reason->id;
}

