<?php
/**
 * 2017 KerAwen
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
 * @copyright 2017 KerAwen
 * @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

function sortByTimeDesc($a, $b) {
	return $a['time'] <= $b['time'] ? 1 : -1;
}

function getProfiling() {
	$queries = Db::getInstance()->queries;
	usort($queries, 'sortByTimeDesc');
	
	return array(
		'queries' => $queries,
	);
}
