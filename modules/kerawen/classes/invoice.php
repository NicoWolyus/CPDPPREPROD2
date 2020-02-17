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


function getInvoicePdf($params) {

	require_once(_KERAWEN_525_CLASS_);
	require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
	
	$context = Context::getContext();
	
	$order = new Order((int)$params->id_order);
	$order_invoice_list = $order->getInvoicesCollection();
	$context->getContext()->language = new Language($order->id_lang);

	$proc = Kerawen525::getInstance();
	$context->smarty->assign('kerawen', array(
		'settings' => getReceiptSettings(),
		'invoice' => $proc->getInvoiceData($params->id_order, 0),
	));

	$pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);		
	$attachment = $pdf->render(false);
	$filename = $pdf->filename;
			
	setEMailInvoice(
		$params->id_lang,
		$params->id_shop,
		$params->firstname,
		$params->lastname,
		$params->email,
		$filename,
		$attachment
	);

}


function getOrderSlipPdf($params) {

	require_once(_KERAWEN_525_CLASS_);
	require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
	
	$context = Context::getContext();
	
	$order_slip = new OrderSlip( (int) $params->id_order);
		
	$proc = Kerawen525::getInstance();
	$context->smarty->assign('kerawen', array(
		'settings' => getReceiptSettings(),
		'invoice' => $proc->getInvoiceData($order_slip->id_order, $order_slip->id),
	));
	
	$pdf = new PDF($order_slip, PDF::TEMPLATE_ORDER_SLIP, Context::getContext()->smarty);
	$attachment = $pdf->render(false);
	$filename = $pdf->filename;
		
	setEMailInvoice(
		$params->id_lang,
		$params->id_shop,
		$params->firstname,
		$params->lastname,
		$params->email,
		$filename,
		$attachment
	);

}


function setEMailInvoice($id_lang, $id_shop, $firstname, $lastname, $email, $filename, $attachment) {
	
	$link = new Link;
	
	$shop_url = $link->getPageLink('index', true, $id_lang, null, false, $id_shop);
	$shop_name = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);
	
	$logo = '';
	if (Configuration::get('PS_LOGO_MAIL') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $id_shop))) {
		$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_MAIL', null, null, $id_shop);
	} else {
		if (file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop);
		}
	}
	
	$shop_address =
	Configuration::get('PS_SHOP_NAME', null, null, $id_shop) . ' - ' .
	Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop) . ' - ' .
	Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop) . ' - ' .
	Configuration::get('PS_SHOP_CODE', null, null, $id_shop) . ' ' . Configuration::get('PS_SHOP_CITY', null, null, $id_shop) . ' - ' .
	Country::getNameById(Configuration::get('PS_LANG_DEFAULT', null, null, $id_shop), Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop));
	
	$dataMail = array(
		'{shop_name}' => $shop_name,
		'{shop_url}' => $shop_url,
		'{shop_email}' => Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
		'{shop_logo}' => $logo,
		'{name_to}' => $firstname . ' ' . $lastname,
		'{shop_link}' => '<a href="' . $shop_url . '">' . $shop_name . '</a>',
		'{shop_address}' => $shop_address,
	);
	
	
	
	if (!is_array ($email)) {
		$email = array('customer' => $email);
	}
	
	foreach ($email as $key => $itemEmail){
		Mail::Send(
			$id_lang,
			'invoice',
			$shop_name . ' - Votre facture : ' . $filename,
			$dataMail,
			$itemEmail,
			NULL,
			Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
			Configuration::get('PS_SHOP_NAME', null, null, $id_shop),
			array(
				'content' => $attachment,
				'mime' => 'application/pdf',
				'name' => $filename
			),
			NULL,
			_KERAWEN_DIR_ . 'mails/'
		);
	}
}
