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

function getForLanguages($value /*, $module, $specific*/)
{
	static $id_langs = false;
	if (!$id_langs)
	{
		$buf = Db::getInstance()->executeS('SELECT id_lang FROM '._DB_PREFIX_.'lang');
		$id_langs = array();
		foreach ($buf as $lang) $id_langs[] = $lang['id_lang'];
	}
	return array_fill_keys($id_langs, $value);

	// The following doesn't work because of cache in Translate
// 	static $languages = false;
// 	if (!$languages)
// 	{
// 		$languages = array();
// 		$buf = Language::getLanguages(true);
// 		foreach ($buf as $l)
// 			$languages[] = new Language($l['id_lang']);
// 	}

// 	$context = Context::getContext();
// 	$current = $context->language;

// 	$translations = array();
// 	foreach ($languages as $lang)
// 	{
// 		$context->language = $lang;
// 		$translations[$lang->id] = $module->l($value, $specific);
// 	}

// 	$context->language = $current;
// 	return $translations;
}

function indexArray($array, $prop)
{
	$res = array();
	foreach($array as $item)
		$res[$item[$prop]] = $item;
	return $res;
}

function setExtendedContext($name, $value)
{
	$context = Context::getContext();
	if (!isset($context->kerawen)) $context->kerawen = new stdClass();
	$context->kerawen->$name =  $value;
}

function getExtendedContext($name, $default)
{
	$context = Context::getContext();
	return isset($context->kerawen->$name) ? $context->kerawen->$name : $default;
}

function backupShopContext()
{
	return array(
		'context' => Shop::getContext(),
		'id_shop' => Shop::getContextShopID(),
		'id_group' => Shop::getContextShopGroupID(),
	);
}

function restoreShopContext($backup)
{
	Shop::setContext($backup['context'],
		$backup['context'] == Shop::CONTEXT_ALL ? null
		: $backup['context'] == Shop::CONTEXT_GROUP ? $backup['id_group']
		: $backup['id_shop']);
}


function sharedCats() {

	$id_lang = Context::getContext()->language->id;
	
	$tree = array();
	$buf = Db::getInstance()->executeS('
			SELECT
				c.id_category AS id_cat,
				c.level_depth AS depth,
				c.id_parent AS id_parent,
				cl.id_shop AS id_shop,
				cl.name AS name
			FROM '._DB_PREFIX_.'category c
			JOIN '._DB_PREFIX_.'category_lang cl ON cl.id_category = c.id_category AND cl.id_lang = ' . (int) $id_lang);
	
	foreach($buf as $cat) {
		if (!isset($tree[$cat['id_shop']])) {
			$tree[$cat['id_shop']] = array();
		}
		
		$tree[$cat['id_shop']][$cat['id_cat']] = array(
				'name' => $cat['name'],
				'id_parent' => $cat['id_parent'],
				'depth' => $cat['depth'],
		);
	}
	return $tree;	
	
}


function getKerawenLink($controller, $params) {
	$link = new Link;
	return $link->getModuleLink('kerawen', $controller, $params);	
}