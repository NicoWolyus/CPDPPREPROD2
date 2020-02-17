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
class AbstractBean
{
	/**
	*
	* @var array the data list
	*/
	protected $b_data = array();

	/**
	* initialize _data array
	*/
	public function __construct()
	{
		foreach ($this->b_keys as &$k)
			$this->b_data[$k] = null;
		unset($k);
	}

	/**
	*
	* @param mixed $key
	* @param mixed $value
	*/
	public function set($key, $value)
	{
		if (isset($this->b_data[$key]) || array_key_exists($key, $this->b_data) || $key === 'debug')
		{
			$this->b_data[$key] = $value;
			return $this;
		}
		throw new Exception('Unknown data '.$key);
	}

	/**
	*
	* @param mixed $key
	* @return mixed the stored value
	* @throws Exception
	*/
	public function get($key)
	{
		if (isset($this->b_data[$key]) || array_key_exists($key, $this->b_data))
			return $this->b_data[$key];
		throw new Exception('Unknown data '.$key);
	}

	/**
	*
	* @return array of values
	*/
	public function export()
	{
		return array(
			$this->b_name => $this->toArray($this)
		);
	}

	/**
	* Transforms an object or array with nested objects to valid array
	*
	* @param mixed $data
	* @return mixed
	*/
	public function toArray($data)
	{
		if ($data instanceof AbstractBean)
			$data = $data->b_data;
		if (is_array($data))
		{
			// Swipe null elements
			$tmp = array_filter($data, array(
				$this,
				'isElementNotNull'
			));
			return array_map(array(
				$this,
				'toArray'
			), $tmp);
		}
		return $data;
	}

	/**
	* Filter function to swipe null elements
	*
	* @param mixed $e
	*        	an element
	* @return boolean true if element is null, false otherwise
	*/
	public function isElementNotNull($e)
	{
		return $e !== null;
	}
}
