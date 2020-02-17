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
class ListBean extends AbstractBean
{
	protected $b_name = null;

	/**
	* initialize _data array
	*/
	public function __construct($name)
	{
		$this->b_name = (string)$name;
		$this->b_data = array();
	}

	/**
	* Add an item to the list
	*
	* @param AbstractBean $bean
	*/
	public function add(AbstractBean $bean)
	{
		$this->b_data[] = $bean;
		return $this;
	}

	/**
	* Return list value
	*/
	public function val()
	{
		return $this->b_data;
	}

	/**
	* Return the size of the list
	*
	* @return int
	*/
	public function size()
	{
		return count($this->b_data);
	}
}
