<?php
class kerawenquotenextModuleFrontController extends ModuleFrontController {

	
	//override parent class !important
	//disable maintenance mode
	protected function displayMaintenancePage() {
	
	}
	
	public function initContent() {

		require_once (_KERAWEN_CLASS_DIR_.'/quote.php');
		
		$cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
		$employee = new Employee((int)$cookie->id_employee);
		$isEmployeeLogin = false;
		if (Validate::isLoadedObject($employee) && $employee->checkPassword((int)$cookie->id_employee, $cookie->passwd) && (!isset($cookie->remote_addr) || $cookie->remote_addr == ip2long(Tools::getRemoteAddr()) || !Configuration::get('PS_COOKIE_CHECKIP'))) {
		  $isEmployeeLogin = true;
		}

		// Set shop context according to cart
		$id_cart = (int)Tools::getValue('id_cart');
		$action = Tools::getValue('action');
		
		if ($id_cart) {
			
			$cart = new Cart($id_cart);
			Shop::setContext(Shop::CONTEXT_SHOP, $cart->id_shop);

			$id_customer = ($isEmployeeLogin) ? 0 : $this->context->customer->id;
			$quote = getQuoteInfo($id_cart, $id_customer, $isEmployeeLogin);

			if ($quote) {
				if ( 
					$quote['quote_active'] === 0 && 
					$action == 'display'
				) {
					$action = "expired";
				}
				
			} else {
				$action = "cancel";
			}
		
		} else {
			if (!$isEmployeeLogin) {
				$action = "cancel";
			}
		}
		
		
		/*
		if (!Configuration::get('KERAWEN_QUOTE_ACTIVE')) {
			$action = "disable";
		}
		*/

		switch($action) {
		
			case 'display':
				$this->context->cookie->__set('id_cart', $id_cart);
				
				$redirect = 'index.php?controller=order';
				if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
					$redirect = 'index.php?controller=cart&action=show';
				}
				Tools::redirect($redirect);
				
			break;

			case 'delete':
				//delete from frontend - TODO ?
			break;

			case 'send':
			case 'download':
				getQuotePdf($quote, $action);
				die();
			break;

			case 'expired':
				die(Tools::displayError('This quotation has been expired'));
				//or redirect with param
			break;

			case 'disable':
				die(Tools::displayError('Quotation module is disable, please check your settings'));
				//or redirect with param
			break;

			case 'bologin':
			case 'login':
				$this->login($action);
				break;
			
			default:
				Tools::redirect('index.php?controller=my-account');
			break;
			
		}
					
	}

	function login($action) {
		
		$customer = new Customer((int)Tools::getValue('id_customer'));
		
		if ($customer->secure_key == Tools::getValue('secure_key')) {
			
			$cookie_lifetime = (int)defined('_PS_ADMIN_DIR_' ? Configuration::get('PS_COOKIE_LIFETIME_BO') : Configuration::get('PS_COOKIE_LIFETIME_FO'));
			$cookie_lifetime = time() + (max($cookie_lifetime, 1) * 3600);
			
			$shop = new Shop($customer->id_shop);
			
			if ($shop->getGroup()->share_order) {
				$cookie = new Cookie('ps-sg'.$shop->getGroup()->id, '', $cookie_lifetime, $shop->getUrlsSharedCart());
			} else {
				$domains = null;
				if ($shop->domain != $shop->domain_ssl) {
					$domains = array($shop->domain_ssl, $shop->domain);
				}
				$cookie = new Cookie('ps-s'.$shop->id, '', $cookie_lifetime, $domains);
			}
			
			if ($cookie->logged) {
				$cookie->logout();
			}
			Tools::setCookieLanguage();
			Tools::switchLanguage();
			$cookie->id_customer = (int)$customer->id;
			$cookie->customer_lastname = $customer->lastname;
			$cookie->customer_firstname = $customer->firstname;
			$cookie->logged = 1;
			$cookie->passwd = $customer->passwd;
			$cookie->email = $customer->email;


			$redirect = 'index.php?controller=my-account';
			
			if ($action == 'login') {
				$link = new Link;
				$redirect = $link->getModuleLink('kerawen', 'quotelist');
				
				require_once (_KERAWEN_CLASS_DIR_.'/quote.php');
				$quote = getQuoteInfo( (int)Tools::getValue('id_cart'), (int)Tools::getValue('id_customer'), 0 );
				if ($quote) {
					if ($quote['quote_active']) {
						$cookie->id_cart = (int)Tools::getValue('id_cart');
						$redirect = 'index.php?controller=order';
						if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
							$redirect = 'index.php?controller=cart&action=show';
						}
					}
				}
			}
			
			Tools::redirect($redirect);
			
		} else {
			die($this->l('Incorrect customer'));
		}
		
	}
	
	
}
