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

/* */
require_once (dirname(__FILE__).'/AbstractBean.php');
class OrderPagingBean extends AbstractBean
{
	const LIST_NAME = 'o_pagings';
	protected $b_name = 'o_paging';
	protected $b_keys = array(
		'nb_result',
		'customer',
		'shipping_takeway',
		'shipping_cashdrawer',
		'shipping_deliver',
		'date_past',
		'date_future',
		'date_today',
		'date_unset',
		'state_preparation',
		'state_outofstock',
		'state_preparing',
		'state_ready',
		'state_shippping',
		'state_shipped',
		'state_canceled',
	);
}
