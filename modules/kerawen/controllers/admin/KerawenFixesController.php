<?php
/**
* 2015 KerAwen
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

require_once (dirname(__FILE__).'/KerawenConfigController.php');

class KerawenFixesController extends KerawenConfigController
{
	public function __construct()
	{
		parent::__construct();
		$this->multishop_context = Shop::CONTEXT_ALL;
		$this->toolbar_title = $this->l('PrestaShop check');
	}
	
	protected function renderContent()
	{
		require_once(_KERAWEN_INSTALL_DIR_.'/audit.php');
		$warnings = audit($this);
		$this->context->smarty->assign('warnings', $warnings);
		$warning_list = $this->context->smarty->fetch($this->getTemplatePath().'warning_list.tpl');
		
		require_once(_KERAWEN_FIX_DIR_.'/fixes.php');
		$fixes = getFixes($this->module);
		$this->context->smarty->assign('fixes', $fixes);
		$fix_list = $this->context->smarty->fetch($this->getTemplatePath().'fix_list.tpl');
		
		$forms = array(
			'config' => array(
				'form' => array(
					'input' => array(
						$this->renderSwitch('KERAWEN_IGNORE_WARNINGS', $this->l('Ignore warnings at application start-up')),
					),
					'submit' => $this->renderSubmit('submitWarnings', $this->l('Save')),
				),
			),
			'warnings' => array(
				'form' => array(
					'legend' => array(
						'title' => $this->l('Configuration'),
						'icon' => 'icon-gear',
					),
					'input' => array(
						array(
							'name' => 'KERAWEN_WARNINGS',
							'type' => 'free',
						),
					),
				),
			),
			'fixes' => array(
				'form' => array(
					'legend' => array(
						'title' => $this->l('Fixes'),
						'icon' => 'icon-wrench',
					),
					'input' => array(
						array(
							'name' => 'KERAWEN_FIXES',
							'type' => 'free',
						),
					),
				),
			),
		);
		
		// Render forms
		$this->setHelperDisplay(new HelperForm());
		$this->helper->tpl_vars = array(
			'fields_value' => array(
				'KERAWEN_IGNORE_WARNINGS' => Configuration::get('KERAWEN_IGNORE_WARNINGS'),
				'KERAWEN_WARNINGS' => $warning_list,
				'KERAWEN_FIXES' => $fix_list,
			),
			'id_language' => $this->context->language->id
		);
		return $this->helper->generateForm($forms);
	}
}
