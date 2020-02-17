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

require_once (dirname(__FILE__).'/../../defines.php');

class KerawenAdminController extends ModuleAdminController
{
	protected $require_upgrade = false;
	protected $warning_list = null;
	
	public function __construct()
	{
		parent::__construct();
		$this->bootstrap = true;

		// Upgrade module upgrade if required
		if (Tools::getValue('upgrade')) {
			$this->module->upgrade();
		}
		if (Configuration::get('KERAWEN_INSTALLED_VERSION') != $this->module->version)
		{
			$this->require_upgrade = true;
			$this->display = 'view';
		}

		// Apply correction if required
		if ($to_correct = Tools::getValue('correct')) {
			require_once(_KERAWEN_DIR_.'/install/audit.php');
			correct($to_correct, $this->module);
		}
		
		// Manage fix if required
		if ($to_install = Tools::getValue('installfix')) {
			require_once(_KERAWEN_FIX_DIR_.'/fixes.php');
			if ($error = installFix($to_install, $this->module)) {
				$this->errors[] = $this->l('Fix not installed', 'KerawenAdminController').': '.$error;
			}
			else {
				$this->confirmations[] = $this->l('Fix sucessfully installed', 'KerawenAdminController');
			}
		}
		if ($to_uninstall = Tools::getValue('uninstallfix')) {
			require_once(_KERAWEN_FIX_DIR_.'/fixes.php');
			if ($error = uninstallFix($to_uninstall, $this->module)) {
				$this->errors[] = $this->l('Fix not uninstalled', 'KerawenAdminController');
			}
			else {
				$this->confirmations[] = $this->l('Fix sucessfully uninstalled', 'KerawenAdminController');
			}
		}
		
		// Check for inconsistencies
		$this->has_warning = false;
		if (Tools::getValue('warn_no_more')) {
			Configuration::updateValue('KERAWEN_IGNORE_WARNINGS', 1);
		}
		if (!Tools::getValue('warn_ignore') && !Configuration::get('KERAWEN_IGNORE_WARNINGS'))
		{
			require_once(_KERAWEN_DIR_.'/install/audit.php');
			$this->warning_list = audit($this);
			foreach ($this->warning_list as $warn) $this->has_warning |= !$warn['valid'];
			
			require_once(_KERAWEN_DIR_.'/fixes/fixes.php');
			$this->fix_list = getFixes($this->module);
			foreach ($this->fix_list as $fix) $this->has_warning |= $fix['compliant'] && !$fix['installed'];
			
			if ($this->has_warning) $this->display = 'view';
		}
	}
	
	public function display()
	{
		if (!$this->require_upgrade && !$this->has_warning && $this->display == 'page')
			echo $this->renderContent();
		else
			return parent::display();
	}
	
	public function renderView()
	{
		if ($this->require_upgrade)
			return $this->renderUpgrade();
		else if ($this->has_warning)
			return $this->renderWarnings();
		else
			return $this->renderContent();
	}
	
	protected function renderUpgrade()
	{
		$this->context->smarty->assign(array(
			'controller' => $this->getAdminLink(Tools::getValue('controller')),
		));
		return $this->context->smarty->fetch($this->getTemplatePath().'upgrade.tpl');
	}
	
	protected function renderWarnings()
	{
		$this->context->smarty->assign(array(
			'warnings' => $this->warning_list,
			'fixes' => $this->fix_list,
		));
		return $this->context->smarty->fetch($this->getTemplatePath().'warning.tpl');
	}
	
	protected function renderContent()
	{
		// Do nothing by default
	}
	
	public function l($string, $class = null, $addslashes = false, $htmlentities = false)
	{
		if (!$class) $class = Tools::strtolower(get_class($this));
		$string = Translate::getModuleTranslation($this->module, $string, $class);

		if ($htmlentities) $string = htmlspecialchars($string, ENT_QUOTES, 'utf-8');
		$string = str_replace('"', '&quot;', $string);
		return ($addslashes ? addslashes($string) : Tools::stripslashes($string));
	}

	public function getAdminLink($controller, $params = null)
	{
		$link = new Link();
		$url = $link->getAdminLink($controller, true);
		if ($params) $url .= '&'.http_build_query($params);
		return $url;
	}
}
