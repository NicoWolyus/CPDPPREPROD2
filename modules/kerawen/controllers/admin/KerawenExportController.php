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
* @copyright 2014 KerAwen
* @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
*/

require_once (dirname(__FILE__).'/KerawenAdminController.php');
require_once (_KERAWEN_CLASS_DIR_.'/log.php');

class KerawenExportController extends KerawenAdminController
{
	public function __construct()
	{		
		
		//set_time_limit(0);
		$this->renderExport();

	}

	public function renderExport()
	{
		
		$db = Db::getInstance();
		
		$params = $_GET;
		$params['page'] = -1;
		$params['index'] = empty($params['index']) ? false : $params['index'];
		$params['require'] = (array) explode(',', $params['require']);

		$params = (object) $params;

		$delimiter = ';';
		//$delimiter = ',';
		$enclosure = '"';
		

		$output = fopen('php://output', 'w');
		ob_start();
		
		foreach($params->require as $type) {
			
			$data = LogQuery::$type($db, $params);
			
			if (isset($data['data'])) {
				$data = $data['data'];
			}
			
			if (count($data) > 0) {
				
				$keys = array_keys(reset($data));				
				fputcsv($output, $keys, $delimiter, $enclosure);
				
				foreach($data as $k=>$row) {				
					fputcsv($output, $row, $delimiter, $enclosure);
				}
				
			}

		}
				
		$string = ob_get_clean();
		
		$this->headers('export-' . date('Ymd') . '-' . date('His'));
		exit($string);

	}
	
	
	public function headers($filename)
	{
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '.csv";');
		header('Content-Transfer-Encoding: binary');
	}	
	
}
