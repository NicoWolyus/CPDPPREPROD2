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

/* */
class KerawenCronModuleFrontController extends ModuleFrontController
{

	var $flag_send;
	var $refdate;

 	public function __construct() {
 		parent::__construct();
 		
		//check key url
		if (Tools::getValue('key') != Configuration::get('KERAWEN_CRON_KEY')) {
			echo '<pre>Access denied - please check your key</pre>';
			return false;
		}
		
		//test mode -> email not sent
		$this->flag_send = 1;
		if (Tools::getValue('test')) {
		  	$this->flag_send = 0;
		}
		
		
		//force date
		$this->refdate = '';
		if (Tools::getValue('date')) {
			$this->refdate = Tools::getValue('date');
		}
		
		//select action
		$action = Tools::getValue('action');
		
		
		switch($action) {
		
			case 'giftcard':
				$this->giftcard_cron();			
			break;

		}
		 
 	}

    public function init() {

		//should be enougth
		$this->ajax = true;

		$this->display_column_left = false;
		$this->display_column_right = false;		
		$this->display_header = false;
		$this->display_footer = false;
		$_GET['content_only'] = 1;

        parent::init();

    }


    public function initContent() {
        parent::initContent();
		return false;
	}


	public function giftcard_cron() {
	
		$context = Context::getContext();
		require_once (_KERAWEN_DIR_.'controllers/front/giftcard.php');
		$data = KerawenGiftcardModel::giftCardToSend($this->refdate);

		foreach ($data 	as $item) {
		
			echo '<pre>';
			print_r($item);
			echo '</pre>';

			$logo = '';
            if (Configuration::get('PS_LOGO_MAIL') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $item['id_shop']))) {
                $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_MAIL', null, null, $item['id_shop']);
            } else {
                if (file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $item['id_shop']))) {
                    $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $item['id_shop']);
                }
            }

			//Context::getContext()->link-> : check in cron context
			$shop_url = Context::getContext()->link->getPageLink('index', true, $item['id_lang'], null, false, $item['id_shop']);
			$shop_name = Configuration::get('PS_SHOP_NAME', null, null, $item['id_shop']);

			$dataMail = array(
				'{shop_name}' => $shop_name,
				'{shop_url}' => $shop_url,
				'{shop_email}' => Configuration::get('PS_SHOP_EMAIL', null, null, $item['id_shop']),
				'{shop_logo}' => $logo,
				'{name_to}' => $item['name'],
				'{name_from}' => $item['cust_firstname'] . ' ' . $item['cust_lastname'],
				'{amount}' => $item['amount'],
				'{shop_link}' => '<a href="' . $shop_url . '">' . $shop_name . '</a>',
				'{shop_address}' => $item['shop_address'],
				'{message}' => nl2br($item['message']),
				'{expiry_date}' => Tools::displayDate($item['date_to'], (int)$item['id_lang']),
				'{code}' => $item['code'],
				'{image}' => ($item['imagePath'] == '') ? '' : '<img src="' . $item['imagePath'] . '" />',
			);
			
			if ($this->flag_send) {
			
				Mail::Send(
					$item['id_lang'], 
					'giftcard', 
					Mail::l('Gift card', (int)$item['id_lang']), 
					$dataMail, 
					$item['email'], 
					NULL, 
					Configuration::get('PS_SHOP_EMAIL', null, null, $item['id_shop']), 
					Configuration::get('PS_SHOP_NAME', null, null, $item['id_shop']), 
					NULL, 
					NULL, 
					_KERAWEN_DIR_ . 'mails/'
				);
			
			} else {
				echo '<pre>Not sent - test mode</pre>';
			}
			
		}
		
	}

}
