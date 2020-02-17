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

require_once (dirname(__FILE__).'/KerawenAdminController.php');

class KerawenApplicationController extends KerawenAdminController
{
	public function __construct()
	{
		$this->display = 'page';
		parent::__construct();
	}
	
	public function run() {
		if (Tools::getValue('ws'))
		{
			// Minimal context
			$context = Context::getContext();
			$context->controller = $this;
			// Execute webservice
			require_once(_KERAWEN_API_DIR_.'/ws.php');
		}
		else if (Tools::getValue('res'))
		{
			// Download resource
			echo 'download '.Tools::getValue('res');
			exit();
		}
		else parent::run();
	}

	public function renderContent()
	{
		// Apply and check licence key
		if (Tools::getValue('key'))
		{
			require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
			$backup = backupShopContext();
			Shop::setContext(Shop::CONTEXT_ALL);
			Configuration::updateValue('KERAWEN_LICENCE_KEY', Tools::getValue('key'));
			restoreShopContext($backup);
		}

		if (!Configuration::get('KERAWEN_LICENCE_KEY'))
		{
			return Tools::redirectAdmin($this->getAdminLink('AdminModules', array(
				'module_name' => $this->module->name,
				'tab_module' => $this->module->tab,
				'configure' => $this->module->name,
			)));
		}

		$shop_url = Tools::getShopDomain().__PS_BASE_URI__;
		$link = new Link();
		//$ws = $link->getAdminLink($this->name).'&ws=1';
		$ws  = preg_replace('`^(http[s]?:)`', '', $link->getAdminLink($this->name)) . '&ws=1';

		//auto detect custom css
		$cssFile = 'kerawen.css';
		$cssList = array(
			'themes/' . _THEME_NAME_ . '/css/' . $cssFile,
			'css/' . $cssFile,
		);

		$css = '';
		foreach($cssList as $cssItem) {
			if ( file_exists(_PS_ROOT_DIR_ . '/' . $cssItem) ) {
				$css = str_replace(array('https:','http:'),'', _PS_BASE_URL_ . __PS_BASE_URI__ . $cssItem);
				break;
			}
		}
		//'css' => Configuration::get('KERAWEN_CSS_URL'),
		
		$this->context->smarty->assign(array(
			'title' => 'KerAwen'.($this->title ? ' - '.$this->title : ''),
			'server' => _KERAWEN_SERVER_,
			'shop' => $shop_url,
			'key' => Configuration::get('KERAWEN_LICENCE_KEY'),
			'version' => $this->module->version,
			'psver' => _PS_VERSION_,
			'appli' => $this->appli,
			'lang' => $this->context->language->iso_code,
			'ws' => $ws._KERAWEN_QUERY_EXT_,
			'css' => $css,
			'printer' => _KERAWEN_SERVER_NODE_PRINTER_,
		));
		return $this->context->smarty->fetch($this->getTemplatePath().'appli.tpl');
	}
}
