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
class Request
{
	public function __construct($content, $context = null)
	{
		$this->jsonrpc = '2.0';
		$this->version = $content->version;
		$this->id = $content->id;
		$this->method = $content->method;
		$this->params = $content->params;
		$this->context = $context;
		$this->result = array();
	}

	public function finalize()
	{
		// Check result format (DEBUG MODE ONLY)
		/*
		if (_KERAWEN_DEBUG_ && $this->method === 'getConfig')
		{
			require_once (dirname(__FILE__).'/format.php');
			$errors = checkResult($this->result);
			if (count($errors))
			{
				echo 'Invalid result format:<br>';
				foreach ($errors as $path => $error)
					echo $path.': '.$error.'<br>';
			}
		}
		*/

		// Keep only response elements before sending back
		unset($this->method);
		unset($this->params);
		unset($this->context);
	}

	public function setError($code, $message)
	{
		$this->error = array(
			'code' => $code,
			'message' => $message
		);
	}

	public function addResult($name, $value = null)
	{
		if ($name instanceof AbstractBean)
			$this->result = array_merge($this->result, $name->export());
		else if ($name)
			$this->result[$name] = $value;
		else
			foreach ($value as $name => $val)
				$this->result[$name] = $val;
	}

	public function addBean($bean)
	{
		$this->result = array_merge($this->result, $bean->export());
	}

	public function addMessage($level, $message)
	{
		if (! property_exists($this, 'msg'))
			$this->msg = array();
		$this->msg[] = array(
			'level' => $level,
			'message' => $message
		);
	}
}