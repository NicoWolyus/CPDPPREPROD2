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
class KerawenProductController extends AdminController {
	
	public function __construct() {
		$this->display = 'view';
		$this->bootstrap = true;
		parent::__construct();
	}
	
	public function renderView($params = null) {
		$id_prod = 0;
		if (Tools::version_compare(_PS_VERSION_, '1.7.0.10', '>=')) {
			$id_prod = $params['id_product'];
		} else {
			$id_prod = Tools::getValue('id_product');
		}
		$combinations = array();
		if ($id_prod) {
			$product = new Product($id_prod);
			$shop_restriction = '';
			switch (Shop::getContext()) {
				case Shop::CONTEXT_SHOP:
					$shop_restriction = 'AND pa.id_product_attribute IN (
						SELECT pas.id_product_attribute
						FROM ' . _DB_PREFIX_ . 'product_attribute_shop pas
						WHERE pas.id_shop = ' . pSQL(Shop::getContextShopID() . ')');
					break;
				case Shop::CONTEXT_GROUP:
					$shop_restriction = 'AND pa.id_product_attribute IN (
						SELECT pas.id_product_attribute
						FROM ' . _DB_PREFIX_ . 'product_attribute_shop pas
						JOIN ' . _DB_PREFIX_ . 'shop s ON s.id_shop = pas.id_shop
						WHERE s.id_shop_group = ' . pSQL(Shop::getContextShopGroupID() . ')');
					break;
			}
			
			$combinations = Db::getInstance()->executeS('
				SELECT
					pa.id_product_attribute AS id,
					GROUP_CONCAT(al.name) AS name
				FROM ' . _DB_PREFIX_ . 'product_attribute pa
				JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac
					ON pac.id_product_attribute = pa.id_product_attribute
				JOIN ' . _DB_PREFIX_ . 'attribute_lang al
					ON al.id_attribute = pac.id_attribute
					AND al.id_lang = ' . pSQL(Context::getContext()->language->id) . '
				WHERE pa.id_product = ' . pSQL($product->id) . '
				' . $shop_restriction . '
				GROUP BY pa.id_product_attribute');
		}
		
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.10', '>=')) {
			$submit_config = 'disabled="disabled"';
			$submit_icon = 'process-icon-loading';
		} else {
			$submit_config = '';
			$submit_icon = 'process-icon-save';
		}
		if (Tools::version_compare(_PS_VERSION_, '1.7.0.10', '>=')) {
			$tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . 'kerawen/views/templates/admin/product_17.tpl', $this->context->smarty);
		} else {
			$tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . 'kerawen/views/templates/admin/product.tpl', $this->context->smarty);
		}
		require_once(_KERAWEN_CLASS_DIR_ . '/catalog.php');
		$tpl->assign(array(
				'product' => $product,
				'combinations' => $combinations,
				'currency' => $this->context->currency,
				'wm' => getProductWeightsAndMeasures($product->id, false),
				'submit_config' => $submit_config,
				'submit_icon' => $submit_icon,
		));
		
		if (Shop::isFeatureActive()) {
			if (Shop::getContext() != Shop::CONTEXT_SHOP) {
				$tpl->assign(array(
						'display_multishop_checkboxes' => true,
						'multishop_check' => Tools::getValue('multishop_check'),
				));
			}
			if (Shop::getContext() != Shop::CONTEXT_ALL) {
				$tpl->assign(array(
						'bullet_common_field' => '<i class="icon-circle text-orange"></i>',
						'display_common_field' => true,
				));
			}
		}
		return $tpl->fetch();
	}
	
	public function postProcess() {
		// Ignore if tab was not initialized
		if (Tools::getValue('kerawen_wm')) {
			$id_product = Tools::getValue('id_product');
			if ($id_product) {
				$db = Db::getInstance();
				
				$exists = $db->getValue('SELECT 1 FROM ' . _DB_PREFIX_ . 'product_wm_kerawen WHERE id_product = ' . pSQL($id_product));
				
				$values = array(
						'measured' => (boolean) Tools::getValue('kerawen_wm_measured'),
						'unit' => Tools::getValue('kerawen_wm_unit'),
						'precision' => (int) Tools::getValue('kerawen_wm_precision'),
						'is_gift_card' => Tools::getValue('kerawen_wm_gift_card'),
				);
				if ($exists) {
					$was_gift_card = $db->getValue('SELECT is_gift_card FROM ' . _DB_PREFIX_ . 'product_wm_kerawen WHERE id_product = ' . pSQL($id_product));
					$db->update('product_wm_kerawen', $values, 'id_product = ' . pSQL($id_product));
				} else {
					$was_gift_card = 0;
					$values['id_product'] = (int) $id_product;
					$db->insert('product_wm_kerawen', $values);
				}
				
				$id_codes = is_array(Tools::getValue('kerawen_wm_id')) ? Tools::getValue('kerawen_wm_id') : array();
				$codes = Tools::getValue('kerawen_wm_code');
				$prices = Tools::getValue('kerawen_wm_unit_price');
				$combs = Tools::getValue('kerawen_wm_combination');
				
				$keep = array();
				foreach ($id_codes as $index => $id_code) {
					$values = array(
							'id_product' => pSQL($id_product),
							'code' => pSQL(isset($codes[$index]) ? $codes[$index] : null),
							'unit_price' => pSQL(isset($prices[$index]) ? $prices[$index] : 0),
							'id_product_attribute' => pSQL(isset($combs[$index]) ? $combs[$index] : -1),
					);
					if ($id_code) {
						$db->update('product_wm_code_kerawen', $values, 'id_code = ' . pSQL($id_code));
					} else {
						$db->insert('product_wm_code_kerawen', $values);
						$id_code = $db->Insert_ID();
					}
					$keep[] = $id_code;
				}
				$db->delete('product_wm_code_kerawen', '
					id_product = ' . pSQL($id_product)
						. (count($keep) ? ' AND id_code NOT IN (' . implode(',', $keep) . ')' : ''));
				
				//gift card on status change
				if (Tools::getValue('kerawen_wm_gift_card') != $was_gift_card) {
					$list = array();
					$list['name'] = array(
							'label' => 'Nom, PrÃ©nom',
							'customization_field' => array('id_product' => (int) $id_product, 'type' => 1, 'required' => 0),
							'field_type' => 'name',
					);
					$list['email'] = array(
							'label' => 'Email',
							'customization_field' => array('id_product' => (int) $id_product, 'type' => 1, 'required' => 0),
							'field_type' => 'email',
					);
					$list['date'] = array(
							'label' => "Anniversaire",
							'customization_field' => array('id_product' => (int) $id_product, 'type' => 1, 'required' => 0),
							'field_type' => 'birthdate',
					);
					$list['message'] = array(
							'label' => 'Message',
							'customization_field' => array('id_product' => (int) $id_product, 'type' => 1, 'required' => 0),
							'field_type' => 'message',
					);
					
					$text_fields = (int) $db->getValue('SELECT text_fields FROM ' . _DB_PREFIX_ . 'product WHERE id_product = ' . pSQL($id_product));
					$customizable = 2;
					
					if (Tools::getValue('kerawen_wm_gift_card') == 1) {
						//if extra fields already exist
						$text_fields += count($list);
						$array_config = array('customizable' => $customizable, 'text_fields' => $text_fields);
						
						$shopsId = Shop::getCompleteListOfShopsID();
						//$languagesID = Language::getIDs();
						// Backward compatibility
						$languages = Language::getLanguages();
						
						foreach ($list as $k => $v) {
							$db->insert('customization_field', $v['customization_field']);
							$id = $db->Insert_ID();
							
							foreach ($languages as $lang) {
								foreach ($shopsId as $id_shop) {
									
									$data_customization_field_lang = array(
											'id_customization_field' => $id,
											'id_lang' => $lang['id_lang'],
											'name' => pSQL($v['label'])
									);
									
									// Backward compatibility
									if (Tools::version_compare(_PS_VERSION_, '1.6.0.12', '>=')) {
										$data_customization_field_lang['id_shop'] = $id_shop;
									}
									
									$db->insert('customization_field_lang', $data_customization_field_lang);
								}
							}
							
							$db->insert('customization_field_kerawen', array(
									'id_customization_field' => $id,
									'field_type' => pSQL($v['field_type'])
							)
									);
						}
						
						//required ?
						Configuration::updateGlobalValue('PS_CUSTOMIZATION_FEATURE_ACTIVE', '1');
					} else {
						$array_config = array();
						
						//Delete only custom fields form kerawen gift_card
						$ids = $db->getInstance()->executeS(
								'SELECT  customization_field.id_customization_field
							 FROM ' . _DB_PREFIX_ . 'customization_field customization_field
							 INNER JOIN ' . _DB_PREFIX_ . 'customization_field_kerawen customization_field_kerawen ON customization_field.id_customization_field = customization_field_kerawen.id_customization_field
							 WHERE  customization_field.id_product = ' . pSQL($id_product)
								);
						
						if ($ids) {
							$text_fields -= count($ids);
							$text_fields = max(0, $text_fields);
							
							if ($text_fields == 0) {
								$customizable = 0;
								//$indexed = 0;
							}
							
							$ids2 = implode(',', array_map(function($item) {
								return $item['id_customization_field'];
							}, $ids));
								
								$customized_tables = array(
										'customization_field_lang',
										'customization_field_kerawen',
										'customization_field'
								);
								
								foreach ($customized_tables as $table) {
									$db->execute(
											'DELETE FROM ' . _DB_PREFIX_ . $table . ' WHERE id_customization_field IN (' . $ids2 . ')'
											);
								}
								
								$array_config = array('customizable' => $customizable, 'text_fields' => $text_fields);
						}
					}
					
					if (count($array_config)) {
						$db->update('product', $array_config, 'id_product = ' . pSQL($id_product));
						$db->update('product_shop', $array_config, 'id_product = ' . pSQL($id_product));
					}
					
					// Redirection to fix confict with other tabs like custom fields
					// This will update only kerawen tab
					// &conf=4 -> required!!!
					$context = Context::getContext();
					$v = 'AdminProducts';
					$token = Tools::getAdminToken($v . (int) (Tab::getIdFromClassName($v)) . (int) ($context->employee->id));
					$url = Dispatcher::getInstance()->createUrl($v, $context->language->id, array('token' => $token), false);
					Tools::redirectAdmin($url . '&conf=4&key_tab=ModuleKerawen&updateproduct&id_product=' . $id_product);
				}
			}
		}
	}
}
