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


function setTillToken($shop_id, $id_cash_drawer, $key)
{
	
	require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, _KERAWEN_SERVER_NODE_ . "/enroll");
	curl_setopt($ch, CURLOPT_POSTFIELDS,"shop=" . $shop_id . "&id_cash_drawer=" . $id_cash_drawer . "&key=" . $key);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	curl_close ($ch);

	$url = '';
	if ($response) {
		$resObj = json_decode($response);
		if (!empty($resObj->url_longue)) {
			$data = (object) array(
				'token' => $resObj->url_longue
			);
			$id_till = updateTill($id_cash_drawer, $data);
			$url = _KERAWEN_SERVER_NODE_ . '/mag/' . $resObj->url_longue;
		}
	}
	return $url;
}


function send2screen($cart = false) 
{
	$db = Db::getInstance();
	
	$id_cashdrawer = Context::getContext()->kerawen->id_cashdrawer;
	//undefined first startRegister
	$drawer = $db->getRow('SELECT token, screen FROM '._DB_PREFIX_.'cash_drawer_kerawen WHERE id_cash_drawer = ' . (int) $id_cashdrawer);
	if ($drawer) {
		
		$token = $drawer['token'];
		$screen = $drawer['screen'];

		if ($token != '' && $screen) {

			$cart['prefix'] = Context::getContext()->currency->prefix;
			$cart['suffix'] = Context::getContext()->currency->suffix;

			//$ticket = json_encode($cart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); 
			//bug php version
			$cart_encode = json_encode($cart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
						
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, _KERAWEN_SERVER_NODE_ . "/ticket");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,"url_longue=" . $token . "&ticket=" . $cart_encode);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			//To check
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 1);
			
			/*
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
			*/

			$server_output = curl_exec($ch);
			curl_close($ch);
			
		}
	
	}
	 
}


function getScreenDecoration($shop_id, $id_cashdrawer) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, _KERAWEN_SERVER_NODE_ . '/screenDecoUrls/'.$shop_id.'/'.$id_cashdrawer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	curl_close ($ch);

	$data = array(
		'urlPub' => "",
		'urlPub_thumb' => "",
		'urlLogo' => "",
		'urlLogo_thumb' => ""
	);
	
	if ($response) {
		$resObj = json_decode($response);		
		$data = array(
			'urlPub' => $resObj->url_pub,
			'urlPub_thumb' => $resObj->url_pub_thumb,
			'urlLogo' => $resObj->url_logo,
			'urlLogo_thumb' => $resObj->url_logo_thumb
		);
	}
	
	return $data;
	
}


function delScreenDecoration($shop_id, $id_cashdrawer, $type, $key) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, _KERAWEN_SERVER_NODE_ . '/delScreenDecoUrls/'.$shop_id.'/'.$id_cashdrawer.'/'.$type);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"key=" . $key);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	curl_close ($ch);
	
	$data = array(
		'success' => false,
	);
	
	if ($response) {
		$resObj = json_decode($response);
		$data['success'] = $resObj->success;
	}
	
	return $data;
}

?>