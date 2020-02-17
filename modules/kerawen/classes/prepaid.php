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

require_once (_KERAWEN_API_DIR_.'/constants.php');

function getPrepaidReload()
{
	$id = Configuration::get('KERAWEN_PREPAID_RELOAD');
	$prod = $id ? new Product($id) : null;
	if (!Validate::isLoaded($prod))
	{
		require_once(dirname(__FILE__).'/catalog.php');
		$prod = createProduct(array(
			'name' => 'Recharge compte prépayé',
			'code' => null,
			'id_taxrule' => 0,
			'price' => 0,
		));
		Configuration::updateValue('KERAWEN_PREPAID_RELOAD', $prod->id);
	}
	return $prod;
}

function setPrepaidReload($active)
{
	$prod = getPrepaidReload();
	if ($prod->active != $active)
	{
		$prod->active = $active;
		$prod->save();
	}
}
