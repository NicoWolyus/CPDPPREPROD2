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
function checkResult($res)
{
	$errors = array();
	checkFormat($res, $errors, Format::$result, '$');
	return $errors;
}

/* Check value format */
function checkFormat($value, &$errors, $format, $path)
{
	if ($value !== null)
		if (is_array($format))
		{
			if (! is_array($value))
			{
				$errors[$path] = 'expected array, found '.gettype($value);
				return;
			}
			else if (isset($format['*']))
			{
				// Actual array
				$eformat = $format['*'];
				foreach ($value as $index => $elem)
					checkFormat($elem, $errors, $eformat, $path.'['.$index.']');
			}
			else
			{
				// Structure
				foreach ($value as $key => $field)
				{
					$fpath = $path.'/'.$key;
					if (! isset($format[$key]))
					{
						$errors[$path] = 'invalid key';
						return;
					}
					checkFormat($field, $errors, $format[$key], $fpath);
				}
			}
		}
		else
		{
			// Primitive
			$type = gettype($value);
			if ($type !== $format)
				$errors[$path] = 'expected '.$format.', found '.$type;
		}
}

class Format
{
	static $result;
	static $config;
}

/* Shop configuration */
Format::$config = array(
	'empl' => array(
		'first' => 'string',
		'last' => 'string',
	),
	'shop' => array(
		'name' => 'string',
		'addr1' => 'string',
		'addr2' => 'string',
		'postcode' => 'string',
		'city' => 'string',
		'country' => 'string',
		'phone' => 'string',
	),
	'timezone' => 'integer',
	'curr' => array(
		'pref' => 'string',
		'suff' => 'string',
	),
	'shops' => array(
		'*' => array(
			'id' => 'integer',
			'name' => 'string',
			'url' => 'string',
		)
	),
	'taxes' => array(
		'*' => array(
			'id' => 'integer',
			'name' => 'string',
			'rate' => 'double',
		)
	),
	'order_states' => array(
		'*' => array(
			'id' => 'integer',
			'name' => 'string',
			'color' => 'string',
		)
	)
);

/* Global API response */
Format::$result = array(
	'config' => Format::$config,
);
