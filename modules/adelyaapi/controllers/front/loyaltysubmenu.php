<?php

include_once _PS_MODULE_DIR_ . 'adelyaapi/adelyaUtil.php';

class AdelyaapiLoyaltysubmenuModuleFrontController extends ModuleFrontController {

	public function initContent() {
		parent::initContent();
		//Init des variables
		$adelyaUtil = new adelyaUtil();
		$this->context->smarty->assign($adelyaUtil->getFidData());
		$this->context->smarty->assign('HOOK_CUSTOMERACCOUNT_TITLE', $this->l(Tools::getValue('fidelity_menu_text', Configuration::get('fidelity_menu_text'))));
		$this->setTemplate('module:adelyaapi/views/templates/front/loyaltysubmenu.tpl');
	}

	public function getBreadcrumbLinks() {
		$breadcrumb = parent::getBreadcrumbLinks();
		$breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
//		$breadcrumb['links'][] = [
//			'title' => $this->getTranslator()->trans($this->l(Tools::getValue('fidelity_menu_text', Configuration::get('fidelity_menu_text'))), [], 'Breadcrumb'),
//			'url' => ''
//		];

		return $breadcrumb;
	}

}
