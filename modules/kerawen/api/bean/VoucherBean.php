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
class VoucherBean extends AbstractBean
{
	const LIST_NAME = 'vouchers';
	protected $b_name = 'voucher';
	protected $b_keys = array(
		'id_reduc',
		'code',
		'name',
		'value',
		'reduc'
	);
}
