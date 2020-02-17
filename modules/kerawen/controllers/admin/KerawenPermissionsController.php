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


require_once (dirname(__FILE__).'/KerawenAdminController.php');
require_once (_KERAWEN_CLASS_DIR_.'/permissions.php');

class KerawenPermissionsController extends KerawenAdminController
{
	
	var $kp;
	
	public function __construct()
	{
		$this->display = 'edit';
		parent::__construct();
		$this->multishop_context = Shop::CONTEXT_ALL;
		$this->title = $this->l('Permissions');
		$this->toolbar_title = $this->l('KerAwen permissions configuration');
		$this->kp = new kerawenPermissions();
	}
	
	protected function renderWarnings()
	{
		return $this->renderContent();
	}

	
	protected function renderContent()
	{

        $profiles = Profile::getProfiles($this->context->language->id);
       
        
        $ajaxMode = Tools::getValue('ajaxMode');
        if ($ajaxMode) {
        	        	
        	$post = $_POST;
        	if ( isset($post['ajaxMode']) ) { unset($post['ajaxMode']); }
        	if ( isset($post['token']) ) { unset($post['token']); }
        	
        	
        	$items = array();
        	$keys = $this->kp->getKerawenFieldPermissions('type');
        	foreach ($profiles as $profile) {
        		$items[$profile['id_profile']] = array();
        		
        		//add missing data -> checkbox not checked
        		foreach($keys as $k => $type) {        			
        			if (empty($post[$k . '-' . $profile['id_profile']]) ) {
        				$post[$k . '-' . $profile['id_profile']] = ' ';
        			}
        		}

        	}
        	
        	foreach($post as $item => $value) {
        		
        		$splited_item =	explode( '-', $item);
        		if ( count($splited_item) == 2) {
        			if ( isset($items[(int) $splited_item[1]]) ) {
        				$items[(int) $splited_item[1]][$splited_item[0]] = is_array($value) ? implode(',', $value) : $value;
        			}
        		}
        		
        	}
        	$res = ($this->kp->setKerawenPermissionsData($items)) ? 'ok' : 'error';

        	die($res);
        	
        }        
        
		$this->context->smarty->assign(array(
			'profiles' => $profiles,
			'forms' => $this->kp->getKerawenPermissionsModele(),
			'formsdata' => $this->kp->getKerawenPermissionsData(),
		));
		
		return $this->context->smarty->fetch($this->getTemplatePath() . 'permissions.tpl');

	}

	public function postProcess()
	{
		$this->display = 'view';
	}
}
