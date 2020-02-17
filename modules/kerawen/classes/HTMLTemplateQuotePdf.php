<?php
/**
* 2016 KerAwen
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
* @copyright 2016 KerAwen
* @license   http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
*/

/*
Source :
http://blog.belvg.com/pdf-in-prestashop.html
*/

//require to build pdf
require_once (_KERAWEN_CLASS_DIR_.'/data.php');
  
class AnObj extends stdClass
{
    public function __call($closure, $args)
    {
        return call_user_func_array($this->{$closure}, $args);
    }
}
 

class kerawen_translation_HTMLTemplateQuotePdf {
	public function l($string) {		
		return Translate::getModuleTranslation('kerawen', $string, basename(__FILE__, '.php'));
	}		
}

class HTMLTemplateQuotePdf extends HTMLTemplate
{
	public $custom_model;
	public $smarty;
	public $id_cart;
	public $id_shop;
	public $quote_number;
	public $employee;
	
	public $file_name;
	public $module;
	
	public $id_lang;
	
	public function __construct($custom_object, $smarty)
	{
		
		$cart = new Cart((int) $custom_object->id_cart);
				
		$this->custom_model = $custom_object;
		$this->smarty = $smarty;
		$this->id_cart = (int) $custom_object->id_cart;
		$this->shop = new Shop($cart->id_shop);
		$this->id_lang = isset($_GET['id_lang']) ? (int) $_GET['id_lang'] : $cart->id_lang;
		$this->quote_number = (int) Db::getInstance()->getValue('SELECT quote_number FROM '._DB_PREFIX_.'cart_kerawen WHERE id_cart = ' . pSQL($this->id_cart));
		$this->employee = Db::getInstance()->getValue("
			SELECT CONCAT(employee.firstname, ' ', employee.lastname) AS employee
			FROM " . _DB_PREFIX_ . "cart_kerawen cart_kerawen 
			INNER JOIN " . _DB_PREFIX_ . "employee employee ON cart_kerawen.id_employee = employee.id_employee
			WHERE cart_kerawen.id_cart = " . pSQL($this->id_cart)
		);
		
		
		// Backward compatibility
		$this->order = new AnObj();
		$this->order->id_shop = $cart->id_shop;	
		
		// Transalation
		//Can't use $this->l() -> already use by static parent class
		$this->module = new kerawen_translation_HTMLTemplateQuotePdf();
				
	}
	
	/**
	 * Returns the template's HTML content
	 * @return string HTML content
	 */
	public function getContent()
	{
		require_once(_KERAWEN_DIR_ . 'classes/cart.php');

        $invoiceAddressPatternRules = Tools::jsonDecode(Configuration::get('PS_INVCE_INVOICE_ADDR_RULES'), true);
        $deliveryAddressPatternRules = Tools::jsonDecode(Configuration::get('PS_INVCE_DELIVERY_ADDR_RULES'), true);

        // Backward compatibility
        $invoiceAddressPatternRules = $invoiceAddressPatternRules ? $invoiceAddressPatternRules : array();
        $deliveryAddressPatternRules = $deliveryAddressPatternRules ? $deliveryAddressPatternRules : array();
        
        
		$cart = new Cart($this->id_cart);
		$cartMore = (object) Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'cart_kerawen WHERE id_cart = ' . pSQL($this->id_cart));
		$mode = $cartMore ? $cartMore->delivery_mode : _KERAWEN_DM_IN_STORE_;
		
		$order_details = array();
		
		$this->id_shop = $cart->id_shop;
		
		$cartAsArray = cartAsArray($cart, true, true);

		$list_tax = array(
			'product_tax' => array(),
			'shipping_tax' => array(),
			'ecotax_tax' => array(),
		);
		

		$shop_address = $this->getShopAddressFormatted();
		
		$order_invoice = new AnObj();
		$order_invoice->shop_address = $shop_address;


		//Shipping
		//Need shipping here to define delivery address
		$shipping_tax_excl = 0;
		$shipping_tax_incl = 0;
		$shipping_vat = 1;
		$shipping = array();
		$hasShipping = false;
		if ($cartAsArray['delivery']['carrier'] != '') {
			$c = $cartAsArray['delivery']['carrier'];
			if (isset($cartAsArray['delivery']['carriers'][$c])) {
				$shipping = $cartAsArray['delivery']['carriers'][$c];
				$shipping_tax_excl = (float) $shipping['price_without_tax'];
				$shipping_tax_incl = (float) $shipping['price'];	
				$shipping_vat = $shipping_tax_incl / $shipping_tax_excl; 
				$shipping_vat_pc = ($shipping_vat - 1) * 100;			
				$shipping_name = $shipping['name'];
				$shipping_id = $shipping['id'];
				$hasShipping = true;
				
				$list_tax['shipping_tax'][$shipping_id] = array(
					'rate' => $shipping_vat_pc,
					'total_tax_excl' => $shipping_tax_excl,
					'total_tax_incl' => $shipping_tax_incl,
					'total_tax' => $shipping_tax_excl * $shipping_vat_pc / 100,
					'name' => $shipping_name,
					'taxKey' => '',
				);
			}
		}


		$tax_exempt = false;
		
		//better way ???
		if ((int) $cart->id_address_delivery === 0) {
			$delivery_address = $shop_address;
			$id_delivery_country = (int) Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $this->id_shop);			
			//OR message  LIKE Takeway
		} else {
			
			$address = new Address( (int) $cart->id_address_delivery );
			$delivery_address = AddressFormat::generateAddress($address, $deliveryAddressPatternRules,'<br />', ' ');
			$id_delivery_country = (int) $address->id_country;			
	
			$tax_exempt = Configuration::get('VATNUMBER_MANAGEMENT')
							&& !empty($address->vat_number)
							&& $address->id_country != Configuration::get('VATNUMBER_COUNTRY');							
			
			if (Tax::excludeTaxeOption()) {
				$tax_exempt = true;
        	}
		}

		
		$inv_addr = new Address( (int) $cart->id_address_invoice );		
		$invoice_address = AddressFormat::generateAddress($inv_addr , $invoiceAddressPatternRules,'<br />', ' ');

		
		//keep same format data as invoice
		$object = new AnObj();
   		$object->vat_number = $inv_addr->vat_number;
		$addresses = array(
			'invoice' => $object,
		); 
	
			
		$order = new AnObj();
		$order->invoice_date = $cartMore->quote_expiry;
		$order->employee = $this->employee;
		$order->getUniqReference = function() { global $cart; return $cart->id; };
		$order->isVirtual = function() { global $hasShipping; return $hasShipping; };
		$order->date_add = $cart->date_add;
		$order->id_currency = $cart->id_currency;
	

		$has_discount = false;		
		$display_product_images = Configuration::get('KERAWEN_QUOTE_IMG');
		
		$tax_id = 0;
		$tax_label = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$taxlist = array();

		
		$vat_margin_offset = 0;
		$full_vat_margin = 1;
		$total_vat_margin = 0;
			
		$calcTotalTe = 0;
		
		foreach ($cartAsArray['products'] as $product) {
			
			$product_name = $product['name'];
			if ($product['version'] != '') {
				$product_name .= '<br />' . $product['version'];
			}
			
			
			//remove ecotax from price without vat
			//$eco_tax_excl_tax = 0;
			$eco_tax_excl_tax = (float) $product['unit_ecotax'] / (1 + (float) $product['rate']/100);
			
			$vat_margin = isset($product['vat_margin']) ? $product['vat_margin'] : 0;
			
			if (!$vat_margin) {
				$full_vat_margin = 0;
			}
			
			//better way ?
			if ($product['price_tax_incl'] == $product['price_tax_excl'] && $product['rate'] > 0 && $product['price_tax_excl'] > 0) {
				$tax_exempt = true;
			}
			

			if ($vat_margin) {
				$unit_init_tax_excl = $product['unit_init_tax_incl'];
				$price_init_tax_excl = $product['price_init_tax_incl'];
				$taxKey = '*';
				$total_vat_margin += (float) $product['price_tax_incl'];
			} else {
				$unit_init_tax_excl = $product['unit_init_tax_excl'];
				$price_init_tax_excl = $product['price_init_tax_excl'];

				//improve (A)
				if (!isset($taxlist[(string)$product['rate']])) {
					$taxKey = $tax_label[$tax_id];
					$taxlist[(string)$product['rate']] = $tax_label[$tax_id];
					$tax_id++;
				} else {
					$taxKey = $taxlist[(string)$product['rate']];
				}
				
			}
			

			$tmpArray = array(

				'product_name' => $product_name,
				'product_reference' => $product['reference'],
				'product_quantity' => $product['qty'],
		
				'unit_init_tax_incl' =>  $product['unit_init_tax_incl'],
				'unit_init_tax_excl' => $unit_init_tax_excl,
					
				'price_init_tax_incl' => $product['price_init_tax_incl'],
				'price_init_tax_excl' => $price_init_tax_excl,
				'price_tax_incl' => $product['price_tax_incl'],
					
				//vat margin ??
				'price_tax_excl' => $product['price_tax_excl'],
					
				'price_discount_tax_excl' => $vat_margin ? ($product['price_init_tax_incl'] - $product['price_tax_incl']) : ($product['price_init_tax_excl'] - $product['price_tax_excl']),
				'price_discount_tax_incl' => $product['price_init_tax_incl'] - $product['price_tax_incl'],

				'order_detail_tax_label' => $product['rate'],
				'ecotax_tax_excl' => $product['unit_ecotax'],
				'total_ecotax_tax_excl' => $product['total_ecotax'],
				'note' => (Configuration::get('KERAWEN_QUOTE_PRODUCT_NOTE')) ? $product['note'] : '',
				'vat_margin' => $vat_margin,
				//require for quotation ?
				'customizedDatas' => array(),

				'discount_type' => $product['discount_type'],	
				'discount' => $product['discount_type'] == "amount" ? $product['discount'] * $product['qty'] : $product['discount'] * 100,
					
				'taxKey' => $taxKey,
					
			);

			
			$calcTotalTe += $tmpArray['price_init_tax_excl'];
			$calcTotalTe -= $tmpArray['price_discount_tax_excl'];

			

			//PS 1.5 and 1.6
			//aproximative version
			if (Tools::version_compare(_PS_VERSION_, '1.6.1.19', '<=')) {
			
    			if (isset($product['image_id']) && $display_product_images) {
    
    				if (Configuration::get('PS_LEGACY_IMAGES')) {
    					$filename = _PS_PROD_IMG_DIR_ . $product['prod'] . ($product['image_id'] ? '-' . $product['image_id'] : '') . '.jpg';
    				} else {
    					$imageIds = $product['prod'] . "-" . $product['image_id'];
    					$split_ids = explode('-', $imageIds);
    					$id_image = (isset($split_ids[1]) ? $split_ids[1] : $split_ids[0]);
    					$filename = _PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($id_image) . $id_image . '.jpg';
    				}
    				
    				if (file_exists($filename)) {
    				
    					$name = 'product_mini_'.(int)$product['prod'].(isset($order_detail['id_product_attribute']) ? '_'.(int)$order_detail['id_product_attribute'] : '').'.jpg';
    					$thumbnail = ImageManager::thumbnail($filename, $name, 45, 'jpg', false);
    				
    					if ($thumbnail) {
    					
    						$imgTag = preg_replace(
    							'/\.*'.preg_quote(__PS_BASE_URI__, '/').'/',
    							_PS_ROOT_DIR_.DIRECTORY_SEPARATOR,
    							$thumbnail,
    							1
    							);
    						
    						//preg_match( '/src="([^"]*)"/i', $imgTag, $array );
    						//fix PS 1.5
    						preg_match( '/src="([^"?]*).*"/i', $imgTag, $array );
    						
    						if (!empty($array[1])) {
    							$tmpArray['image_tag'] = '<img src="' . $array[1] . '" width="45" height="45" />';
    							$tmpArray['image'] = new Image($product['image_id']);
    						}
    
    					}
    
    				}
    					
    			}

			} else {
    			if ($product['img'] && $display_product_images) {
    				$tmpArray['image_tag'] = '<img src="' . $product['img'] . '" width="45" height="45" />';
    			}
			}

			//VAT array
			if ( (float) $product['price_tax_excl']  > 0) {

				if ($vat_margin) {
					$product['rate'] = '*';
					$price_tax_excl = $product['price_tax_incl'];
					$vat_margin_offset += $product['price_tax_incl'] - $product['price_tax_excl'];
					
				} else {
					$price_tax_excl = $product['price_tax_excl'];
				}
	
				if (!isset($list_tax['product_tax'][(string)$product['rate']])) {
					$list_tax['product_tax'][(string)$product['rate']] = array(
						'rate' => $product['rate'],
						'total_tax_excl' => 0,
						'total_tax_incl' => 0,
						'total_tax' => 0,
						'taxKey' => $taxKey,
						'vat_margin' => $vat_margin,
					);
				}
        
				$list_tax['product_tax'][(string)$product['rate']]['total_tax_excl'] += (float) $price_tax_excl;
				$list_tax['product_tax'][(string)$product['rate']]['total_tax_incl'] += (float) $product['price_tax_incl'];
				$list_tax['product_tax'][(string)$product['rate']]['total_tax'] += (float) $product['price_tax_incl'] - (float) $price_tax_excl;
			}
				
			$order_details[] = $tmpArray;

		}


		$cart_rules = array();
		$total_disc_tax_excl = 0;
		foreach ($cartAsArray['reducs'] as $reduc) {
			$value_tax_excl = (float) $reduc['reduc_tax_excl'] > $reduc['reduc_tax_incl'] ? $reduc['reduc_tax_incl'] : $reduc['reduc_tax_excl'];
			$cart_rules[] = array(
				'name' => $reduc['name'],
				'code' => $reduc['code'],
				'value_tax_excl' => $value_tax_excl,
				'value_tax_incl' => (float) $reduc['reduc_tax_incl'],
			);
			$calcTotalTe -= $value_tax_excl;
			$total_disc_tax_excl += $value_tax_excl;
		}
		

		$discount_rate = 1;
		$fix_total_paid_tax_excl = 0;
		$fix_total_taxes = 0;
		$total_shipping = 0;
		
		if (count($cart_rules)) {
			$total_ti_discount_excl = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS) + $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);

			$total_shipping = $hasShipping ? $cart->getOrderTotal(true, Cart::ONLY_SHIPPING) : 0;
			
			if ($total_ti_discount_excl > 0) {
				$discount_rate = ($cartAsArray['total_cart'] - $total_shipping) / $total_ti_discount_excl;
			}

			
			if ($discount_rate !== 1) {
				foreach ($list_tax['product_tax'] as $k => $tax) {
					$list_tax['product_tax'][$k]['total_tax_incl'] *= $discount_rate;
					$list_tax['product_tax'][$k]['total_tax_excl'] *= $discount_rate;
					$list_tax['product_tax'][$k]['total_tax'] = $list_tax['product_tax'][$k]['total_tax_incl'] - $list_tax['product_tax'][$k]['total_tax_excl'];

					//recalculate total for vat margin
					if ($vat_margin_offset > 0) {
						$fix_total_paid_tax_excl += $list_tax['product_tax'][$k]['total_tax_excl'];
						$fix_total_taxes += $list_tax['product_tax'][$k]['total_tax'];
					}
				}
			}

		}
		
		//add tax shipping here.... (exclude from discount rate)
		foreach ($list_tax['shipping_tax'] as $k => $product) {
			
			//improve (A)
			if (!isset($taxlist[(string)$product['rate']])) {
				$taxKey = $tax_label[$tax_id];
				$taxlist[(string)$product['rate']] = $tax_label[$tax_id];
				$tax_id++;
				
				$list_tax['product_tax'][(string)$product['rate']] = array(
					'rate' => $product['rate'],
					'total_tax_excl' => 0,
					'total_tax_incl' => 0,
					'total_tax' => 0,
					'taxKey' => $taxKey,
					'vat_margin' => 0,
				);
			} else {
				$taxKey = $taxlist[(string)$product['rate']];
			}
			
			$list_tax['shipping_tax'][$k]['taxKey'] = $taxKey;
			
			$list_tax['product_tax'][(string)$product['rate']]['total_tax_excl'] += (float)$product['total_tax_excl'];
			$list_tax['product_tax'][(string)$product['rate']]['total_tax_incl'] += (float)$product['total_tax_incl'];
			$list_tax['product_tax'][(string)$product['rate']]['total_tax'] += (float)$product['total_tax'];
			//$list_tax['product_tax'][(string)$product['rate']]['total_tax'] += (float)$product['total_tax_incl'] - (float)$product['total_tax_excl'];

			$full_vat_margin = 0;
		}
		
		$calcTotalTe += $shipping_tax_excl;



		$footer = array();
		$footer['shipping_tax_excl'] = $shipping_tax_excl;
		$footer['shipping_tax_incl'] = $shipping_tax_incl;
		$footer['wrapping_tax_excl'] = 0;
		$footer['wrapping_tax_incl'] = 0;
		$footer['total_paid_tax_excl'] = 0;
		$footer['total_taxes'] = 0;
		$footer['total_paid_tax_incl'] = $cartAsArray['total_cart'];
		foreach ($list_tax['product_tax'] as $tax) {
			$footer['total_paid_tax_excl'] += $tax['total_tax_excl'];
			$footer['total_taxes'] += $tax['total_tax'];
		}

		
		//calculate cart_rule amount with vat margin
		if ($vat_margin_offset >= 0 && $total_disc_tax_excl > 0) {
			$coef_discount = ($total_disc_tax_excl + $calcTotalTe - $footer['total_paid_tax_excl']) / $total_disc_tax_excl;
			foreach ($cart_rules as $k => $reduc) {
				$cart_rules[$k]['value_tax_excl'] = $cart_rules[$k]['value_tax_excl'] * $coef_discount;
			}
		}
		
		
		$this->smarty->assign(array(
			'order_invoice' => $order_invoice,
			'delivery_address' => $delivery_address,
			'invoice_address' => $invoice_address,
			'title' => $this->getQuotationNumber($this->quote_number),
			'addresses' => $addresses,
			'order' => $order,			
			'order_details' => $order_details,
			'cart_rules' => $cart_rules,
			'footer' => $footer,
			'tax_exempt' => $tax_exempt,
			'tax_breakdowns' => $list_tax,
			'thanks_text' => $this->quote_message('KERAWEN_QUOTE_MESSAGE'),
			'legal_free_text' => $this->custom_format($this->quote_message('KERAWEN_QUOTE_MESSAGE_2')) . $this->custom_format($this->quote_message('KERAWEN_QUOTE_MESSAGE_3')) . $this->custom_format($this->quote_message('KERAWEN_QUOTE_MESSAGE_4')),			
			'quote_tax' => Configuration::get('KERAWEN_QUOTE_TAX'),
			'quote_disp_tax' => Configuration::get('KERAWEN_QUOTE_DISP_TAX'),
			'quote_disp_unit_vat' => Configuration::get('KERAWEN_QUOTE_DISP_UNIT_VAT'),
			'quote_disp_total_vat' => Configuration::get('KERAWEN_QUOTE_DISP_TOTAL_VAT'),
			'quote_ref_col' => Configuration::get('KERAWEN_QUOTE_REF_COL'),
			'display_product_images' => $display_product_images,
			'full_vat_margin' => $full_vat_margin,
		));

        $tpls = array(
        	'style_tab' 	=> $this->smarty->fetch( $this->get_path_file('quote.style-tab.tpl') ),
        	'addresses_tab' => $this->smarty->fetch( $this->get_path_file('quote.addresses-tab.tpl') ),
			'summary_tab'	=> $this->smarty->fetch( $this->get_path_file('quote.summary-tab.tpl') ),
            'product_tab' 	=> $this->smarty->fetch( $this->get_path_file('quote.product-tab.tpl') ),
			'tax_tab' 		=> $this->smarty->fetch( $this->get_path_file('quote.tax-tab.tpl') ),
			'payment_tab' 	=> false,			
			'total_tab' 	=> $this->smarty->fetch( $this->get_path_file('quote.total-tab.tpl') ),
        );

        $this->smarty->assign($tpls);	
        return $this->smarty->fetch( $this->get_path_file('quote.tpl') );
        
	}
 
	
	public function quote_message($key) {
		$text = Configuration::get($key, $this->id_lang);
		if (!$text) {
			$text = Configuration::get($key);
		}
		return $text;
	}
	
	
	public function get_path_file($file) {

		//Context::getContext()->language = new Language($this->id_lang);
		
		//theme
		if (file_exists ( _PS_THEME_DIR_ . 'pdf/'  . $file )) {
   			$file_dir = _PS_THEME_DIR_ . 'pdf/'  . $file;
   		
   		//root
		} elseif (file_exists ( _PS_ROOT_DIR_ . '/pdf/'  . $file )) {
   			$file_dir =  _PS_ROOT_DIR_ . '/pdf/'  . $file;
   		
   		//kerawen module
 		} else {
   			$file_dir =  _KERAWEN_DIR_. 'views/templates/front/pdf/' . $file;
		}      

		return $file_dir;
		
	}
   
   
   public function custom_format($str) {
   	  return (trim($str) == '') ? '' : '<p>' . str_replace("  ","&nbsp;&nbsp;", nl2br(trim($str))) . '</p>';
   }
 
 
   public function getQuotationNumber ($id) {
   		return '#Q' . str_pad($id, 8, '0', STR_PAD_LEFT);
   }
 
 
 	public function getShopAddressFormatted($sep = '<br />') {
 		
		return	
			  $this->newLine(Configuration::get('PS_SHOP_NAME', null, null, $this->id_shop), $sep)
			. $this->newLine(Configuration::get('PS_SHOP_ADDR1', null, null, $this->id_shop), $sep) 
			. $this->newLine(Configuration::get('PS_SHOP_ADDR2', null, null, $this->id_shop), $sep) 
			. $this->newLine(Configuration::get('PS_SHOP_CODE', null, null, $this->id_shop) . ' ' . Configuration::get('PS_SHOP_CITY', null, null, $this->id_shop) , $sep) 
			. $this->newLine(Country::getNameById(Configuration::get('PS_LANG_DEFAULT', null, null, $this->id_shop), Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $this->id_shop)), '');

	}

	
	
 	public function newLine($data, $sep) {
	
	  if (trim($data) != '')
	  	$data .= $sep;
	  
	  return $data;
	}

	public function getPagination() {
	    return false;
	}


	public function getHeader()
	{
	
		require_once(_PS_CLASS_DIR_ . 'Tools.php');
		
        //$this->assignCommonHeaderData();

		
		// Backward compatibility
		
		$path_logo = $this->getLogo();
		
		$width = 0;
		$height = 0;
		if (!empty($path_logo)) {
			list($width, $height) = getimagesize($path_logo);
		}
		
		// Limit the height of the logo for the PDF render
		$maximum_height = 100;
		if ($height > $maximum_height) {
			$ratio = $maximum_height / $height;
			$height *= $ratio;
			$width *= $ratio;
		}
		
		//$header = HTMLTemplateInvoice::l('Quotation','kerawen');
		$header = $this->module->l('Quotation');
		
		$this->smarty->assign(array(
				
				'shop_name' => $header,
				'header' => $header,
				'title' => $this->getQuotationNumber($this->quote_number),
				'date' => date('Y-m-d H:i:s'),
				
				'logo_path' => $path_logo,
				'img_ps_dir' => 'http://'.Tools::getMediaServer(_PS_IMG_)._PS_IMG_,
				'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
				'width_logo' => $width,
				'height_logo' => $height
		));
		
        //return $this->smarty->fetch($this->getTemplate('header'));	
        return $this->smarty->fetch( $this->get_path_file('quote.header.tpl') );
	}


    /**
     * Compute layout elements size
     *
     * @param $params Array Layout elements
     *
     * @return Array Layout elements columns size
     */
    protected function computeLayout($params)
    {
        return array();
    }


	/**
	 * Returns the template filename
	 * @return string filename
	 */

	public function getFooter()
	{
			
		$array_data = array(
			'available_in_your_account' => false,
			'shop_address' => $this->getShopAddressFormatted(' - '),
		);
	
		$shop_phone = Configuration::get('PS_SHOP_PHONE', null, null, $this->id_shop);
		if ($shop_phone != '') {
			$array_data['shop_phone'] = $shop_phone; 
		}

		$shop_fax = Configuration::get('PS_SHOP_FAX', null, null, $this->id_shop);
		if ($shop_fax != '') {
			$array_data['shop_fax'] = $shop_fax; 
		}

		$shop_details = Configuration::get('PS_SHOP_DETAILS', null, null, $this->id_shop);
		if ($shop_details != '') {
			$array_data['shop_details'] = $shop_details; 
		}

		$link = new Link;
		$shop_url = $link->getPageLink('index', true, null, null, false, $this->id_shop);
		$array_data['free_text'] = $shop_url;
		
		$this->smarty->assign($array_data);
	
		
		//return $this->smarty->fetch($this->getTemplate('footer'));
		return $this->smarty->fetch( $this->get_path_file('quote.footer.tpl') );

	}

	
	/**
	 * Returns the template filename
	 * @return string filename
	 */
	public function getFilename()
	{
		return $this->getQuotationNumber($this->quote_number) . '_' . date('YmdHis') . '.pdf';
	}
 
	/**
	 * Returns the template filename when using bulk rendering
	 * @return string filename
	 */
	public function getBulkFilename()
	{
		return $this->getQuotationNumber($this->quote_number) . '_' . date('YmdHis') . '.pdf';
	}
}
