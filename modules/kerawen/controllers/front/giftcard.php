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
class KerawenGiftcardModuleFrontController extends ModuleFrontController
{
	
    public function init() {
        $this->display_column_left = false;
		$this->display_column_right = false;
        parent::init();
    }

    public function initContent() {
        parent::initContent();
				
		$this->context->smarty->assign('giftcards', KerawenGiftcardModel::giftCardData());
		$this->context->smarty->assign('id_customer', (int) $this->context->customer->id);		
		$this->context->smarty->assign('id_currency', (int) $this->context->currency->id);
		
		$tpl = 'kerawen_giftcard_list.tpl';
		if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
			$tpl = 'module:kerawen/views/templates/front/kerawen_giftcard_list_17.tpl';
		}
		
		parent::initContent();
		$this->setTemplate($tpl);
		
	}

	
	public function getBreadcrumbLinks()
	{
		$breadcrumb = parent::getBreadcrumbLinks();
		
		$breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
		
		return $breadcrumb;
	}

	
	public static function convertForm($key, $type) 
	{
		$field = "$('[name=\"textField" . $key . "\"]')";
		$data = "\n\t" . $field . ".replaceWith('<input name=\"textField" . $key . "\" value=\"' + " . $field . ".val() + '\" class=\"' + " . $field . ".attr('class') + ' gift_card_custom_input\" />');";
		if ($type == 'date') {
			$data .= "\n\t" . $field . ".datepicker({ dateFormat: 'dd/mm/yy', minDate: 1 });";
		}
		return $data;
	}

	public static function hookDisplayHeader(array $params, Module $module)
	{

		$context = Context::getContext();
		$db = Db::getInstance();
		$js = '';

		//detect page product
		if ($context->controller->php_self == 'product') {

			$id_product = (int)Tools::getValue('id_product'); 
			
			//detect gift card
			if ( $db->getValue('SELECT is_gift_card FROM ' . _DB_PREFIX_ . 'product_wm_kerawen WHERE id_product = ' . $id_product) ) {

				$gift_params = $db->getInstance()->executeS('
					SELECT customization_field_kerawen.id_customization_field, customization_field_kerawen.field_type
					FROM ' . _DB_PREFIX_ . 'customization_field customization_field
					INNER JOIN ' . _DB_PREFIX_ . 'customization_field_kerawen customization_field_kerawen ON customization_field.id_customization_field = customization_field_kerawen.id_customization_field
					WHERE customization_field.id_product = ' . $id_product
				);

				foreach($gift_params as $param) {
				
					switch($param['field_type']) {
					
						case 'name':
						case 'email':
							$js .= self::convertForm($param['id_customization_field'], 'text');
						break;
						
						case 'birthdate':
							//load date picker
							$context->controller->addJqueryUI('ui.datepicker');
							$js .= self::convertForm($param['id_customization_field'], 'date');
						break;

					}
				
				}

			}
				
		}

		if ($js != '') {

			$js = '
<script type="text/javascript">
$(document).ready(function() {
' . $js . '
});
//console.log("gift card js....' . $context->controller->php_self . '");
</script>';

		}
		return $js;

	}

}


class KerawenGiftcardModel
{

	public static function giftCardData() 
	{
  
  		$context = Context::getContext();
  
		$q = "
			SELECT cart_rule.reduction_amount AS amount, cart_rule.reduction_currency AS id_currency, cart_rule.date_to AS expiry, cart_rule.code AS barcode, orders.date_upd, orders.id_order,
			IF(cart_rule.quantity = 1 AND cart_rule.active = 1 AND (NOW() BETWEEN cart_rule.date_from AND cart_rule.date_to), 1,0) AS status
			FROM " . _DB_PREFIX_ . "cart_rule_kerawen cart_rule_kerawen
			INNER JOIN " . _DB_PREFIX_ . "cart_rule cart_rule ON cart_rule_kerawen.id_cart_rule = cart_rule.id_cart_rule
			INNER JOIN " . _DB_PREFIX_ . "orders orders ON cart_rule_kerawen.id_order = orders.id_order
			WHERE cart_rule_kerawen.type = '" . _KERAWEN_CR_GIFT_CARD_ . "' AND cart_rule_kerawen.id_parent_cart_rule IS NULL AND orders.id_customer = " . (int) $context->customer->id . "
			ORDER BY cart_rule.id_cart_rule DESC
		";
		
		return Db::getInstance()->executeS($q);
	
	}


	public static function giftCardCount() 
	{
		return (int) count(self::giftCardData());
	}


	public static function giftCardToSend($date = '')
	{

		$db = Db::getInstance();
		$link = new Link;
		
		if ($date != '') {
			$todayFormat = "'" . $date . "'";
		} else {
			$todayFormat = "DATE_FORMAT(NOW(),'%d/%m/%Y')";
		}
		
		$list = array();

		$q =" 
			SELECT customization.id_customization, customization.id_cart, customization.id_product, customization.id_product_attribute
			FROM " . _DB_PREFIX_ . "customization_field_kerawen customization_field_kerawen 
			INNER JOIN " . _DB_PREFIX_ . "customized_data customized_data ON customization_field_kerawen.id_customization_field = customized_data.index
			INNER JOIN " . _DB_PREFIX_ . "customization customization ON customized_data.id_customization = customization.id_customization
			WHERE customization_field_kerawen.field_type = 'birthdate' AND customized_data.value = " . $todayFormat . "
		";

		$rows = $db->executeS($q);
		
		
		$exclude = array(0);
		
		foreach($rows as $row) {
			
			$item = $db->getRow("
				SELECT cart_rule.code, cart_rule.date_to, cart_rule.reduction_amount, cart_rule.reduction_currency, cart_rule_kerawen.id_cart_rule, cart_rule_kerawen.id_product, orders.id_customer, orders.id_shop, orders.id_lang, product_lang.link_rewrite 
				FROM " . _DB_PREFIX_ . "cart_rule_kerawen cart_rule_kerawen
				INNER JOIN " . _DB_PREFIX_ . "cart_rule cart_rule ON cart_rule_kerawen.id_cart_rule = cart_rule.id_cart_rule
				LEFT JOIN " . _DB_PREFIX_ . "orders orders ON cart_rule_kerawen.id_order = orders.id_order
				LEFT JOIN " . _DB_PREFIX_ . "product_lang product_lang ON cart_rule_kerawen.id_product = product_lang.id_product AND product_lang.id_shop = orders.id_shop AND product_lang.id_lang = orders.id_lang
				WHERE cart_rule_kerawen.type = '" . _KERAWEN_CR_GIFT_CARD_ . "' 
				AND cart_rule_kerawen.id_cart = " . (int) $row["id_cart"] . " 
				AND cart_rule_kerawen.id_product = " . (int) $row["id_product"] . "
				AND cart_rule_kerawen.id_attribute = " . (int) $row["id_product_attribute"] . "
				AND cart_rule_kerawen.id_parent_cart_rule IS NULL
				AND cart_rule.quantity = 1 
				AND cart_rule.active = 1 
				AND (NOW() BETWEEN cart_rule.date_from AND cart_rule.date_to)
				AND cart_rule_kerawen.id_cart_rule NOT IN(" . implode(",", $exclude) . ")
			");
	
	
			if ($item) {
	
				$more  = $db->executeS("	
					SELECT customization_field_kerawen.field_type, customized_data.value 
					FROM " . _DB_PREFIX_ . "customized_data customized_data
					LEFT JOIN " . _DB_PREFIX_ . "customization_field_kerawen customization_field_kerawen ON customized_data.index = customization_field_kerawen.id_customization_field
					WHERE customized_data.id_customization = " . (int) $row["id_customization"]
				);

				$valid_email = 0;
				foreach($more as $sub) {
					$item[$sub['field_type']] = $sub['value'];
					if ($sub['field_type'] == 'email' && $sub['value'] != '') {
						$valid_email = 1;
					}
				}

				$customer = $db->getRow("
					SELECT firstname, lastname, email FROM " . _DB_PREFIX_ . "customer WHERE id_customer = " . (int) $item["id_customer"]
				);

				if ($valid_email && $customer) {

					//get_cover here
					$item['imagePath'] = '';
					
					if ( $image = Image::getCover($item['id_product']) ){
			  			$item['imagePath'] = $link->getImageLink( $item['link_rewrite'], $image['id_image'], 'home_default' );
					}
				
					$item['amount'] = Tools::displayPrice((float) $item['reduction_amount'], (int) $item['reduction_currency']);
				
					$item['cust_firstname'] = $customer['firstname'];
					$item['cust_lastname'] = $customer['lastname'];
					$item['cust_email'] = $customer['email'];
					$item['shop_address'] = self::getShopAddressFormatted($item['id_shop'], ' - ');
					$list[] = $item;
				}


			}

			 //fix bug if same cart same product same date multi times
			 $exclude[] = (int) $item["id_cart_rule"];
	
		}

		return $list;
	
	}


 	public static function getShopAddressFormatted($id_shop, $sep = '<br />') {

		return	
			  self::newLine(Configuration::get('PS_SHOP_NAME', null, null, $id_shop), $sep)
			. self::newLine(Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop), $sep) 
			. self::newLine(Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop), $sep) 
			. self::newLine(Configuration::get('PS_SHOP_CODE', null, null, $id_shop) . ' ' . Configuration::get('PS_SHOP_CITY', null, null, $id_shop) , $sep) 
			. self::newLine(Country::getNameById(Configuration::get('PS_LANG_DEFAULT', null, null, $id_shop), Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop)), '');

	}

 	public static function newLine($data, $sep) {
	
	  if (trim($data) != '')
	  	$data .= $sep;
	  
	  return $data;
	}

}
