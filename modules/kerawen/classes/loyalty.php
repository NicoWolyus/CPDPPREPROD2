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

class KerawenLoyalty
{
	protected static $plugin = null;
	
	public static function getPlugin() {

		static $modules = array(
			'allinone_rewards'				=> 'KerawenLoyaltyAllinone',
			'loyalty'						=> 'KerawenLoyaltyPrestashop',
			'fidelisa'						=> 'KerawenFidelisa',
			'totloyaltyadvanced'			=> 'KerawenLoyaltyAdvanced',
		    'adelyaapi'			            => 'KerawenAdelyaapi',
		);

		if (self::$plugin == null) {
			self::$plugin = false;
			foreach ($modules as $name => $class)
				if (Module::isInstalled($name) && Module::isEnabled($name)) {
					self::$plugin = new $class();
					break;
				}
		}
		return self::$plugin;
	}
}


abstract class KerawenLoyaltyPlugin
{
	public abstract function getInfo($id_cust);
	public abstract function add($id_cust, $value);
	public abstract function transform($id_cust);
	public abstract function getRules($id_cust);
	public abstract function doActions();
	public abstract function checkBox();
}


class NoLinkException extends Exception {};
class NoLink
{
	public function getModuleLink() {
		throw new NoLinkException();
	}
	public function getPageLink() {
		throw new NoLinkException();
	}
}



class KerawenAdelyaapi
{
    
    private $adelyaapi_instance = null;
    
    public function __construct() {
        $this->adelyaapi_instance = Module::getInstanceByName('adelyaapi');
    }
    
    public function getInfo($id_cust) {
        
	    require_once(_PS_MODULE_DIR_.'/adelyaapi/adelyaUtil.php');

        $context = Context::getContext();
        $context->customer = new Customer($id_cust);
        $context->customer->logged = 1;
        
        $context->controller->php_self = 'discount';
        
        $points = '';
        $expiry_date = false;
        $link = false;
        $error = false;
        

        if ($id_cust && $id_cust != (int)Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER')) {
            
            //active account?
            //$fid_program_membership = (int) Db::getInstance()->getValue('SELECT fid_program_membership FROM `'._DB_PREFIX_.'customer` WHERE id_customer = ' . (int) $id_cust . ' AND fid_program_membership = 1');          

            if ((int) $context->customer->fid_program_membership) {
                //$context->customer->fid_program_membership = 1;
                try {
                    $adelyaUtil = new adelyaUtil();
		            $adelyaUtil->syncCustomerData($context->customer);
		            $adelyaUtil->syncCoupons($context->customer->id, $context->customer->language->id);
                    $fidData = $adelyaUtil->getFidData();

                    if (isset($fidData['FRONT_LOYALTYSUBMENU_NBPOINT'])) {
                        $points = (int) $fidData['FRONT_LOYALTYSUBMENU_NBPOINT'];
                    }
                }
                catch (Exception $e) {
                     // Ignore
                     $error = "Connection error with Adelya server, please contact Adelya";
                }
            }
        }
        
        return array(
            'points' => $points,
            'value' => 0,
            'transform' => false,
            'expiry' => $expiry_date,
            'link' => $link,
            'error' => $error,
        );
        
    }
    
    public function getRules($id_cust) {
        $db = Db::getInstance();

        return array(
            'loyalty' => array(),
            'referral' => array(),
            'sponsor' => array(),
        );
    }
    
    public function doActions() {
        return false;
    }
    
    public function checkBox() {
        return 'fid_program_membership';
    }
}


class KerawenLoyaltyAdvanced extends KerawenLoyaltyPlugin
{
	public function getInfo($id_cust) {
		require_once(_PS_MODULE_DIR_.'/totloyaltyadvanced/LoyaltyModuleAdvanced.php');
		require_once(_PS_MODULE_DIR_.'/totloyaltyadvanced/LoyaltyStateModuleAdvanced.php');
		
		$points = LoyaltyModuleAdvanced::getPointsByCustomer($id_cust);
		$value = LoyaltyModuleAdvanced::getVoucherValue($points);
		
		return array(
			'points' => $points,
			'value' => $value,
			'transform' => true,
			'error' => false,
		);
	}
	
	public function add($id_cust, $value) {
		$db = Db::getInstance();
		
		$date = strtotime(date('Y-m-d H:i:s'));
		if (Configuration::get('PS_ORDER_RETURN'))
			$date -= 60*60*24*(int)Configuration::get('PS_ORDER_RETURN_NB_DAYS');
		$date = date('Y-m-d H:i:s', $date);
		
		$db->insert('totloyalty', array(
			'id_loyalty_state' => 2,
			'id_customer' => $id_cust,
			'id_order' => 0,
			'id_cart_rule' => 0,
			'points' => $value,
			'date_add' => $date,
			'date_upd' => $date,
		));
		$db->insert('totloyalty_history', array(
			'id_loyalty' => $db->Insert_ID(),
			'id_loyalty_state' => 2,
			'points' => $value,
			'date_add' => $date,
		));
	}

	public function transform($id_cust) {
		require_once(_PS_MODULE_DIR_.'/totloyaltyadvanced/LoyaltyModuleAdvanced.php');
		// Prepare environement
		$_POST['module'] = 'totloyaltyadvanced';
		$context = Context::getContext();
		$context->customer = new Customer($id_cust);
		$context->cookie->id_customer = $id_cust;
		$context->link = new NoLink();
		
		try {
			LoyaltyModuleAdvanced::transformPoints();
		}
		catch (Exception $e) {
			// TODO report error
		}

	}
	
	public function getRules($id_cust) {
		$db = Db::getInstance();
		
		$buf = $db->executeS('
			SELECT DISTINCT id_cart_rule
			FROM '._DB_PREFIX_.'totloyalty
			WHERE id_customer = '.pSQL($id_cust));
		$loyalty = array();
		foreach ($buf as $cr)
			if ($cr['id_cart_rule']) $loyalty[] = $cr['id_cart_rule'];
		
		$referral = array();
		$sponsor = array();
		if (Module::isInstalled('referralprogram') && Module::isEnabled('referralprogram'))
		{
			$buf = $db->executeS('
			SELECT id_cart_rule, id_cart_rule_sponsor
			FROM `'._DB_PREFIX_.'referralprogram`');
			foreach ($buf as $cr)
			{
				if ($cr['id_cart_rule']) $referral[] = $cr['id_cart_rule'];
				if ($cr['id_cart_rule_sponsor']) $sponsor[] = $cr['id_cart_rule_sponsor'];
			}
		}
			
		return array(
			'loyalty' => $loyalty,
			'referral' => $referral,
			'sponsor' => $sponsor,
		);
	}
	
	public function doActions() {
		return true;
	}
	
	public function checkBox() {
	    return false;
	}
	
}

class KerawenLoyaltyPrestashop extends KerawenLoyaltyPlugin
{
	public function getInfo($id_cust) {
		if (file_exists(_PS_ROOT_DIR_.'/override/modules/loyalty/LoyaltyModule.php')) {
			require_once(_PS_ROOT_DIR_.'/override/modules/loyalty/LoyaltyModule.php');
		} else {
			require_once(_PS_MODULE_DIR_.'/loyalty/LoyaltyModule.php');
		}
		
		if (file_exists(_PS_ROOT_DIR_.'/override/modules/loyalty/LoyaltyStateModule.php')) {
			require_once(_PS_ROOT_DIR_.'/override/modules/loyalty/LoyaltyStateModule.php');
		} else {
			require_once(_PS_MODULE_DIR_.'/loyalty/LoyaltyStateModule.php');
		}
		

		$points = LoyaltyModule::getPointsByCustomer($id_cust);
		$value = LoyaltyModule::getVoucherValue($points);
		
		return array(
			'points' => $points,
			'value' => $value,
			'transform' => true,
			'error' => false,
		);
	}
	
	public function add($id_cust, $value) {
		$db = Db::getInstance();
		
		$date = strtotime(date('Y-m-d H:i:s'));
		if (Configuration::get('PS_ORDER_RETURN'))
			$date -= 60*60*24*(int)Configuration::get('PS_ORDER_RETURN_NB_DAYS');
		$date = date('Y-m-d H:i:s', $date);
		
		$db->insert('loyalty', array(
			'id_loyalty_state' => 2,
			'id_customer' => $id_cust,
			'id_order' => 0,
			'id_cart_rule' => 0,
			'points' => $value,
			'date_add' => $date,
			'date_upd' => $date,
		));
		$db->insert('loyalty_history', array(
			'id_loyalty' => $db->Insert_ID(),
			'id_loyalty_state' => 2,
			'points' => $value,
			'date_add' => $date,
		));
	}

	public function transform($id_cust) {
		
		if (file_exists(_PS_ROOT_DIR_.'/override/modules/loyalty/controllers/front/default.php')) {
			require_once(_PS_ROOT_DIR_.'/override/modules/loyalty/controllers/front/default.php');
		} else {
			require_once(_PS_MODULE_DIR_.'/loyalty/controllers/front/default.php');
		}
		
		// Prepare environement
		$_POST['module'] = 'loyalty';
		$context = Context::getContext();
		$context->customer = new Customer($id_cust);
		$context->cookie->id_customer = $id_cust;
		$context->link = new NoLink();
		
		$controller = new LoyaltyDefaultModuleFrontController();
		try {
			$controller->processTransformPoints();
		}
		catch (Exception $e) {
			// TODO report error
		}
	}
	
	public function getRules($id_cust) {
		$db = Db::getInstance();
		
		$buf = $db->executeS('
			SELECT DISTINCT id_cart_rule
			FROM '._DB_PREFIX_.'loyalty
			WHERE id_customer = '.pSQL($id_cust));
		$loyalty = array();
		foreach ($buf as $cr)
			if ($cr['id_cart_rule']) $loyalty[] = $cr['id_cart_rule'];
		
		$referral = array();
		$sponsor = array();
		if (Module::isInstalled('referralprogram') && Module::isEnabled('referralprogram'))
		{
			$buf = $db->executeS('
			SELECT id_cart_rule, id_cart_rule_sponsor
			FROM `'._DB_PREFIX_.'referralprogram`');
			foreach ($buf as $cr)
			{
				if ($cr['id_cart_rule']) $referral[] = $cr['id_cart_rule'];
				if ($cr['id_cart_rule_sponsor']) $sponsor[] = $cr['id_cart_rule_sponsor'];
			}
		}
			
		return array(
			'loyalty' => $loyalty,
			'referral' => $referral,
			'sponsor' => $sponsor,
		);
	}
	
	public function doActions() {
		return true;
	}
	
	public function checkBox() {
	    return false;
	}
	
}


class KerawenLoyaltyAllinone extends KerawenLoyaltyPlugin
{
	protected static $Module;
	
	public function __construct() {
		require_once(_PS_MODULE_DIR_.'/allinone_rewards/allinone_rewards.php');
	 	self::$Module = new allinone_rewards();
	}
	
	public function getInfo($id_cust) {
		$context = Context::getContext();
		
		// Log the customer on
		$context->customer = new Customer($id_cust);
		$context->customer->logged = 1;
		$passwd_save = $context->cookie->__get('passwd');
		$context->cookie->__set('passwd', $context->customer->passwd);
		
		$totals = RewardsModel::getAllTotalsByCustomer($id_cust);
		$value = $totals[RewardsStateModel::getValidationId()];
		$ratio = MyConf::get('REWARDS_VIRTUAL') ? (float)MyConf::get('REWARDS_VIRTUAL_VALUE_'.$context->currency->id) : 1;
		$points = (int)($value*$ratio);
		$mini = (float)MyConf::get('REWARDS_VOUCHER_MIN_VALUE_'.$context->currency->id);
		$transform = RewardsModel::isCustomerAllowedForVoucher($id_cust) && $value >= $mini;

		// Restore cookie to avoid operator re-connection
		$context->cookie->__set('passwd', $passwd_save);
		
		return array(
			'points' => $points,
			'value' => $value,
			'transform' => $transform,
			'error' => false,
		);
	}
	
	public function add($id_cust, $value) {
		$context = Context::getContext();
		
		$ratio = MyConf::get('REWARDS_VIRTUAL') ? (float)MyConf::get('REWARDS_VIRTUAL_VALUE_'.$context->currency->id) : 1;
		$value = $value/$ratio;
		
		$reward = new RewardsModel();
		$reward->id_customer = $id_cust;
		$reward->credits = $value;
		$reward->id_reward_state = RewardsStateModel::getValidationId();
		$reward->plugin = 'loyalty';
		$reward->save();
	}
	
	public function transform($id_cust) {
		$context = Context::getContext();
		$context->customer = new Customer($id_cust);
		$context->customer->logged = 1;

		$totals = RewardsModel::getAllTotalsByCustomer($id_cust);
		$totalAvailable = isset($totals[RewardsStateModel::getValidationId()]) ? (float)$totals[RewardsStateModel::getValidationId()] : 0;
		RewardsModel::createDiscount($totalAvailable);
	}
	
	public function getRules($id_cust) {
		$db = Db::getInstance();
		
		$buf = $db->executeS('
			SELECT DISTINCT id_cart_rule
			FROM '._DB_PREFIX_.'rewards
			WHERE id_customer = '.pSQL($id_cust));
		$loyalty = array();
		foreach ($buf as $cr)
			if ($cr['id_cart_rule']) $loyalty[] = $cr['id_cart_rule'];
		
		return array(
			'loyalty' => $loyalty,
			'referral' => array(),
			'sponsor' => array(),
		);
	}
	
	public function doActions() {
		return true;
	}
	
	public function checkBox() {
	    return false;
	}
	
}


class KerawenFidelisa
{

	private $fidelisa_instance = null;

	public function __construct() {
		$this->fidelisa_instance = Module::getInstanceByName('fidelisa');
	}

	public function getInfo($id_cust) {

		$context = Context::getContext();
		$context->customer = new Customer($id_cust);
		$context->customer->logged = 1;
		
		$context->controller->php_self = 'discount';
		
		$params = array();
		$params['customer'] = $context->customer;

		$points = '';
		$expiry_date = false;
		$link = false;
		$error = false;

		try {

			//Doesn't work
			//make multi cartrules
			/*
			 Hook::exec('actionAuthentication', array(
			 '_POST' => array(),
			 'customer' => $context->customer,
			 ));
			 */
			//OR
			//$this->fidelisa_instance->hookActionAuthentication($params);
			

			$new_customer = array();
			$new_customer["customer"] = array(
				'last_name' => $context->customer->lastname,
				'first_name' => $context->customer->firstname,
				'email' => $context->customer->email	
			);
			
			$result = $this->fidelisa_instance->getClientApi()->postcustomer($new_customer);
			if ($result) {
				FidelisaCustomerIndex::saveIndex($context->customer->id, array('uuid' => $result['uuid']));
			}
			

			$this->fidelisa_instance->hookDisplayHeader($params);
			
			$customer_data = FidelisaCustomerIndex::getIndex($id_cust);
			if ($customer_data) {
				$fca = $this->fidelisa_instance->getClientApi();
				$cards = $fca->getCards($customer_data['uuid']);
				
				if ($cards['cards']) {
					//loop to cumulate multi cards ?
					if (!empty($cards['cards'][0])) {
						
						if ((int)Configuration::get('FIDELISA_PRODUCTION')) {
							$link = 'https://back.fidelisa.com/';
						} else {
							$link = 'https://back-staging.fidelisa.com/';
						}
						$link .= '#' . $customer_data['uuid'];
						
						$points = $cards['cards'][0]['points'];
						$expiry_date = $cards['cards'][0]['expired_at'];
						
					}
				}
			}

		}
		catch (Exception $e) {
			// Ignore
			$error = "Connection error with Fidelisa server, please contact Fidelisa";
		}

		return array(
			'points' => $points,
			'value' => 0,
			'transform' => false,
			'expiry' => $expiry_date,
			'link' => $link,
			'error' => $error,
		);
	}

	public function getRules($id_cust) {
		$db = Db::getInstance();

		$buf = $db->executeS('
			SELECT fcari.id_cart_rule
			FROM '._DB_PREFIX_.'fidelisa_cart_rule_index fcari
			INNER JOIN '._DB_PREFIX_.'cart_rule cr ON fcari.id_cart_rule = cr.id_cart_rule
			WHERE fcari.id_customer = ' . pSQL($id_cust)
		);

		$loyalty = array();

		foreach ($buf as $cr) {
			if ($cr['id_cart_rule']) {
				$loyalty[] = $cr['id_cart_rule'];
			}
		}
			
		return array(
			'loyalty' => $loyalty,
			'referral' => array(),
			'sponsor' => array(),
		);
	}

	public function doActions() {
		return false;
	}
	
	public function checkBox() {
	    return false;
	}
	
}

