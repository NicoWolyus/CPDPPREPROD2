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


class PrepareTranslateKerawen
{
	public function l($string) {
		$str = Translate::getModuleTranslation('kerawen', $string, basename(__FILE__, '.php'));		
		return $str;
	}
}


define('_KERAWEN_TEMPLATE_DIR_', _KERAWEN_DIR_.'pdf/');

class HTMLTemplateInvoiceKerawen extends HTMLTemplateInvoice
{
	public function __construct(OrderInvoice $order_invoice, $smarty) {
		parent::__construct($order_invoice, $smarty);
		
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$secure = Kerawen525::getInstance();
		
		$this->smarty->assign(array(
			'order' => $this->order,
			'kerawen' => array(
				'settings' => getReceiptSettings(),
				'invoice' => $secure->getInvoiceData($this->order_invoice->id_order, 0),
			),
		));
	}
	
	protected function isCertified() {
		return $this->smarty->tpl_vars['kerawen']->value['invoice'];
	}
	
	protected function getTemplate($template_name) {
		if ($this->isCertified()) {
			$file = _PS_THEME_DIR_.'pdf/custom-'.$template_name.'.tpl';
			if (file_exists($file)) return $file;
			
			$file = _PS_ROOT_DIR_.'/pdf/custom-'.$template_name.'.tpl';
			if (file_exists($file)) return $file;
			
			$file = _KERAWEN_TEMPLATE_DIR_.$template_name.'.tpl';
			if (file_exists($file)) return $file;
		}
		return parent::getTemplate($template_name);
	}
	
	public function getContent() {
		if ($this->isCertified()) {
			$this->smarty->assign(array(
				'style_tab' => $this->smarty->fetch($this->getTemplate('invoice.style-tab')),
				'addresses_tab' => $this->smarty->fetch($this->getTemplate('invoice.addresses-tab')),
				'summary_tab' => $this->smarty->fetch($this->getTemplate('invoice.summary-tab')),
				'product_tab' => $this->smarty->fetch($this->getTemplate('invoice.product-tab')),
				'tax_tab' => $this->getTaxTabContent(),
				'payment_tab' => $this->smarty->fetch($this->getTemplate('invoice.payment-tab')),
				'note_tab' => $this->smarty->fetch($this->getTemplate('invoice.note-tab')),
				'total_tab' => $this->smarty->fetch($this->getTemplate('invoice.total-tab')),
				'shipping_tab' => $this->smarty->fetch($this->getTemplate('invoice.shipping-tab')),
			));
		}
		return parent::getContent();
	}
}

class HTMLTemplateOrderSlipKerawen extends HTMLTemplateOrderSlip
{
	public function __construct(OrderSlip $order_slip, $smarty) {
		parent::__construct($order_slip, $smarty);
		
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$secure = Kerawen525::getInstance();
		$this->smarty->assign(array(
			'order_slip' => $this->order_slip,
			'order' => $this->order,
			'kerawen' => array(
				'settings' => getReceiptSettings(),
				'invoice' => $secure->getInvoiceData($order_slip->id_order, $order_slip->id),
			),
		));
	}
	
	protected function isCertified() {
		return $this->smarty->tpl_vars['kerawen']->value['invoice'];
	}
	
	protected function getTemplate($template_name) {
		if ($this->isCertified()) {
			$file = _KERAWEN_TEMPLATE_DIR_.$template_name.'.tpl';
			if (file_exists($file)) return $file;
		}
		return parent::getTemplate($template_name);
	}
	
	public function getContent() {
		if ($this->isCertified()) {
			$this->smarty->assign(array(
				'style_tab' => $this->smarty->fetch($this->getTemplate('invoice.style-tab')),
				'addresses_tab' => $this->smarty->fetch($this->getTemplate('invoice.addresses-tab')),
				'summary_tab' => $this->smarty->fetch($this->getTemplate('order-slip.summary-tab')),
				'product_tab' => $this->smarty->fetch($this->getTemplate('order-slip.product-tab')),
				'total_tab' => $this->smarty->fetch($this->getTemplate('order-slip.total-tab')),
				'payment_tab' => $this->smarty->fetch($this->getTemplate('order-slip.payment-tab')),
				'tax_tab' => $this->getTaxTabContent(),
			));
		}
		return parent::getContent();
	}
}
