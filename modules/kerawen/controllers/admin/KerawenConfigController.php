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
require_once (_KERAWEN_CLASS_DIR_.'/KerawenPayment.php');
require_once (_KERAWEN_TOOLS_DIR_.'/utils.php');

class KerawenConfigController extends KerawenAdminController
{
	
	private $_confLanguages;
	
	public function __construct()
	{
		$this->display = 'edit';
		parent::__construct();
		$this->multishop_context = Shop::CONTEXT_ALL;
		$this->toolbar_title = $this->l('KerAwen module configuration');
		
		$this->_confLanguages = Language::getLanguages(true);

	}
	
	protected function renderWarnings()
	{
		return $this->renderContent();
	}
	
	protected function renderContent()
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		$legacy_orders = legacyOrders();
		
		$forms = array(
				'legacy' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Legacy orders referencing'),
										'icon' => 'icon-resize-horizontal',
								),
								'desc' => $this->l('Select legacy orders you want to be referenced by KerAwen applications') . '<br />' . $this->l('Depending number of commands and server capacity, an error message may appear. In this case it is necessary to extend the timeout'),
								'input' => array(
										$this->renderSwitch('KERAWEN_LEGACY_ORDERS', $this->l('Orders')),
										//$this->renderSwitch('KERAWEN_LEGACY_ORDERS', $this->l('Orders').' ('.$legacy_orders.')'),
								),
								'submit' => $this->renderSubmit('submitLegacy', $this->l('Reference')),
						),
				),
				
				'params' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Cash register parameters'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_NOTIF_PERIOD',
												'label' => $this->l('Notification update period'),
												'type' => 'text',
												'suffix' => $this->l('seconds'),
												'desc' => $this->l('0 means that the cash register will not be notified (e.g. when a new order is received).')
										),
										$this->renderSwitch('KERAWEN_SWITCH_CASHIER', $this->l('Allow to switch cashier')),
										$this->renderSwitch('KERAWEN_EMPLOYEE_PASSWORD', $this->l('Password required to switch cashier')),
										$this->renderSwitch('KERAWEN_EMPLOYEE_PASSWORD_EXCP', $this->l('Allow login when password is not defined')),
										$this->renderSwitch('KERAWEN_SWITCH_SHOP', $this->l('Allow to switch shop')),
										$this->renderSwitch('KERAWEN_DISCOUNT_CART', $this->l('Allow to apply cart discounts')),
										$this->renderSwitch('KERAWEN_SELECT_DELIVERY', $this->l('Allow to select delivery mode')),
										$this->renderSwitch('KERAWEN_GATHER_MEASURES', $this->l('Gather the different measures of same product')),
										$this->renderShortcuts(),
										array(
												'name' => 'KERAWEN_DEFAULT_GROUP',
												'label' => $this->l('New customer default group'),
												'type'  => 'select',
												'options'  => array(
														'query' => Group::getGroups($this->context->language->id),
														'id' => 'id_group',
														'value' => 'id_group',
														'name' => 'name',
												)
										),
										array(
												'name' => 'KERAWEN_OVERRIDE_GROUP',
												'label' => $this->l('Override customer group'),
												'type'  => 'select',
												'options'  => array(
														'query' => array_merge(
															array(
																array(
																	'id_group' => 0,
																	'name' => $this->l('None'),
																),
															),
															Group::getGroups($this->context->language->id)),
														'id' => 'id_group',
														'value' => 'id_group',
														'name' => 'name',
												)
										),
										$this->renderSwitch('KERAWEN_SHOW_AMOUNTS', $this->l('Show expected amounts when opening and closing a till')),
										$this->renderSwitch('KERAWEN_SEND_EMAIL', $this->l('Send email when changing order state (according to order configuration)')),
										$this->renderSwitch('KERAWEN_OVERRIDE_EMAIL', $this->l('Override order confirmation emails (include certified invoice)')),
										array(
												'name' => 'KERAWEN_DISCOUNT_DURATION',
												'label' => $this->l('Discount validity period'),
												'type' => 'text',
												'suffix' => $this->l('days')
										),
										$this->renderSwitch('KERAWEN_ORDER_QUICK_END', $this->l('Automatically back to catalog when order is ended')),
										$this->renderSwitch('KERAWEN_ORDER_PULSE', $this->l('Automatically open cash drawer when order is ended')),
										$this->renderSwitch('KERAWEN_PLAY_SOUND', $this->l('Play sound when product is scanned')),
										$this->renderSwitch('KERAWEN_SCAN_NOT_FOUND', $this->l('Scan result: suggest product creation if no product found')),
										array(
												'name' => 'KERAWEN_DEFAULT_VAT',
												'label' => $this->l('New product default VAT'),
												'type'  => 'select',
												'options'  => array(
														'query' => TaxRulesGroup::getTaxRulesGroupsForOptions(),
														'id' => 'id_tax_rules_group',
														'value' => 'id_tax_rules_group',
														'name' => 'name',
												)
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'display' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Display parameters'),
										'icon' => 'icon-eye',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_CATALOG_PAGE_SIZE',
												'label' => $this->l('Number of products per page'),
												'type' => 'text',
												'desc' => $this->l('0 means that all the category products are displayed at once.')
										),
										array(
												'name' => 'KERAWEN_ORDERS_LIST_ITEMS_BY_PAG',
												'label' => $this->l('Number of orders per page'),
												'type' => 'text'
										),
										$this->renderSwitch('KERAWEN_CATALOG_FULL_NAMES', $this->l('Display full product names in catalog as list')),
										$this->renderSwitch('KERAWEN_CATALOG_REFERENCES', $this->l('Display product references in catalog as list')),
										$this->renderSwitch('KERAWEN_CART_FULL_NAMES', $this->l('Display full product names in cart')),
										$this->renderSwitch('KERAWEN_ORDERS_LIST_COLUMN_SHOP', $this->l('Display shop name on orders list')),
										$this->renderSwitch('KERAWEN_ORDERS_LIST_COLUMN_CARRI', $this->l('Display carrier name on orders list')),
										$this->renderSwitch('KERAWEN_ORDERS_LIST_COLUMN_TILL', $this->l('Display till name on orders list')),
										$this->renderSwitch('KERAWEN_ORDERS_LIST_COLUMN_COMP', $this->l('Display company name on orders list')),
								        $this->renderSwitch('KERAWEN_ORDERS_LIST_COLUMN_ADD', $this->l('Display date added on orders list')),
								        $this->renderSwitch('KERAWEN_CUST_ACCOUNT_ADDR', $this->l('New customer account with address form')),
										$this->renderImageTypes('KERAWEN_IMAGE_PRODUCT', $this->l('Image type for products'), 'products'),
										$this->renderImageTypes('KERAWEN_IMAGE_CATEGORY', $this->l('Image type for categories'), 'categories'),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'payment' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Payment parameters'),
										'icon' => 'icon-euro',
								),
								'input' => $this->renderPaymentBlock(),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'customer display' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Customer display'),
										'icon' => 'icon-eye',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_DISPLAY_CPL',
												'label' => $this->l('Default characters per line'),
												'type' => 'text',
										),
										array(
												'name' => 'KERAWEN_DISPLAY_MSG_START',
												'label' => $this->l('Start message'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 2,
										),
										array(
												'name' => 'KERAWEN_DISPLAY_MSG_END',
												'label' => $this->l('End message'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 2,
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'ticket' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Ticket parameters'),
										'icon' => 'icon-file-text',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_TICKET_CPL',
												'label' => $this->l('Default characters per line'),
												'type' => 'text',
										),
										array(
												'name' => 'KERAWEN_TICKET_IMAGE',
												'label' => $this->l('Header image'),
												'type' => 'free',
												'content' => '
								<img id ="kerawen-ticket-image"
									'.(Configuration::get('KERAWEN_TICKET_IMAGE') ? 'src="'._PS_IMG_.Configuration::get('KERAWEN_TICKET_IMAGE').'"' : '')
												.'/>',
										),
										array(
												'name' => 'KERAWEN_TICKET_IMAGE_ACTIONS',
												'type' => 'free',
												'content' => '
								<div class="btn btn-default" onclick="
									document.getElementById(\'kerawen-ticket-image\').style.display=\'none\';
									document.getElementById(\'KERAWEN_TICKET_IMAGE_DELETE\').value=1;">
									'.$this->l('Delete').'
								</div>
								<div class="btn btn-default" onclick="
									document.getElementById(\'kerawen-ticket-image\').style.display=\'block\';
									document.getElementById(\'KERAWEN_TICKET_IMAGE_DELETE\').value=0;">
									'.$this->l('Restore').'
								</div>',
										),
										array(
												'name' => 'KERAWEN_TICKET_IMAGE_DELETE',
												'type' => 'hidden',
										),
										array(
												'name' => 'KERAWEN_TICKET_IMAGE_UPLOAD',
												'type' => 'file',
										),
										$this->renderSwitch('KERAWEN_TICKET_SHOP_COUNTRY', $this->l('Print shop country')),
										$this->renderSwitch('KERAWEN_TICKET_SHOP_URL', $this->l('Print shop URL')),
										$this->renderSwitch('KERAWEN_TICKET_SHOP_EMAIL', $this->l('Print shop Email')),
										$this->renderSwitch('KERAWEN_TICKET_COMMENTS', $this->l('Print order comments')),
										$this->renderSwitch('KERAWEN_TICKET_PRODUCT_NOTE', $this->l('Print product note')),
										$this->renderSwitch('KERAWEN_TICKET_FULL_NAMES', $this->l('Print full product names')),
										$this->renderSwitch('KERAWEN_TICKET_TAXES', $this->l('Print total taxes')),
										$this->renderSwitch('KERAWEN_TICKET_DETAIL_TAXES', $this->l('Print taxes detail')),
										$this->renderSwitch('KERAWEN_TICKET_CUSTOMER', $this->l('Print customer name')),
										$this->renderSwitch('KERAWEN_TICKET_CUSTOMER_PHONE', $this->l('Print customer phone')),
										$this->renderSwitch('KERAWEN_TICKET_REF', $this->l('Print reference')),
										$this->renderSwitch('KERAWEN_TICKET_EMPLOYEE_NAME', $this->l('Print employee name')),
										array(
												'name' => 'KERAWEN_TICKET_LOYALTY',
												'label' => $this->l('Print loyalty data'),
												'type' => 'radio',
												'class' => 't',
												'values' => array(
														array(
																'id' => 'loyalty_none',
																'value' => 0,
																'label' => $this->l('No'),
														),
														array(
																'id' => 'loyalty_points',
																'value' => 1,
																'label' => $this->l('Points'),
														),
														array(
																'id' => 'loyalty_amount',
																'value' => 2,
																'label' => $this->l('Amount'),
														),
														array(
																'id' => 'loyalty_both',
																'value' => 3,
																'label' => $this->l('Both'),
														),
												),
										),
										$this->renderSwitch('KERAWEN_TICKET_MODE', $this->l('Print mode')),
										array(
												'name' => 'KERAWEN_TICKET_MESSAGE',
												'label' => $this->l('Footer message'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
										),
										$this->renderSwitch('KERAWEN_TICKET_SHOP_DETAILS', $this->l('Print shop details')),
										$this->renderSwitch('KERAWEN_TICKET_BARCODE', $this->l('Print ticket barecode')),
										$this->renderSwitch('KERAWEN_TICKET_ORDER_NUMBER', $this->l('Print incremental order number')),
										$this->renderSwitch('KERAWEN_TICKET_PRINT_OPEN_CLOSE', $this->l('Print ticket on open/close/status')),
										$this->renderSwitch('KERAWEN_TICKET_PRINT_AUTO', $this->l('Automatically print receipt')),
										array(
												'name' => 'KERAWEN_TICKET_PRINT_MIN_AMOUNT',
												'label' => $this->l('Minimum amount for automatically print receipt'),
												'type' => 'text',
												'desc' => $this->l('Receipt is legaly required from € 25, leave blank or 0 to disable')
										),
    								    array(
    								        'name' => 'KERAWEN_TICKET_MSG_DISCOUNT',
    								        'label' => $this->l('Discount message'),
    								        'type' => 'textarea',
    								        'cols' => 48,
    								        'rows' => 3,
    								    ),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
		    
    		    'customerCard' => array(
    		        'form' => array(
    		            'legend' => array(
    		                'title' => $this->l('Dematerialized customer card'),
    		                'icon' => 'icon-wrench',
    		            ),
    		            'input' => array(
    		                $this->renderSwitch('KERAWEN_CUST_PRINT', $this->l('Print customer detail')),
    		                array(
    		                    'name' => 'KERAWEN_CUST_HEADER_MESSAGE',
    		                    'label' => $this->l('Header message'),
    		                    'type' => 'textarea',
    		                    'cols' => 48,
    		                    'rows' => 3,
    		                ),
    		                array(
    		                    'name' => 'KERAWEN_CUST_FOOTER_MESSAGE',
    		                    'label' => $this->l('Footer message'),
    		                    'type' => 'textarea',
    		                    'cols' => 48,
    		                    'rows' => 3,
    		                ),
    		            ),
    		            'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
    		        ),
    		    ),
		    
				'giftcard' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Gift card settings'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_GIFT_CARD_DURATION',
												'label' => $this->l('Validity period'),
												'type' => 'text',
												'suffix' => $this->l('Days'),
										),
										array(
												'name' => 'KERAWEN_GIFT_CARD_TICKET_MESSAGE',
												'label' => $this->l('Message'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
										),
										$this->renderSwitch('KERAWEN_GIFT_CARD_JS', $this->l('Optimize front-office gift card form with javascript (datepicker)')),
										array(
												'name' => 'KERAWEN_GIFT_CARD_CRON_URL',
												'label' => $this->l('Cron URL'),
												'type' => 'free',
												'content' => '<label class="control-label">' . $this->getGiftCardCronUrl() . '</label>',
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'modes' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Delivery modes settings'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_LABEL_IN_STORE',
												'label' => $this->l('In store label'),
												'type' => 'text',
												'placeholder' => $this->l('In store'),
										),
										array(
												'name' => 'KERAWEN_LABEL_TAKEAWAY',
												'label' => $this->l('Takeaway label'),
												'type' => 'text',
												'placeholder' => $this->l('Takeaway'),
										),
										array(
												'name' => 'KERAWEN_LABEL_DELIVERY',
												'label' => $this->l('Delivery label'),
												'type' => 'text',
												'placeholder' => $this->l('Delivery'),
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'scale' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Scale parameters'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_SCALE_PREFIX',
												'label' => $this->l('Barcode prefix'),
												'type' => 'text',
										),
										array(
												'name' => 'KERAWEN_SCALE_PRODUCT_LENGTH',
												'label' => $this->l('Product code length'),
												'type' => 'text',
										),
										array(
												'name' => 'KERAWEN_SCALE_PRICE_LENGTH',
												'label' => $this->l('Price code length'),
												'type' => 'text',
										),
										array(
												'name' => 'KERAWEN_SCALE_PRICE_MULTIPLIER',
												'label' => $this->l('Price code multiplier'),
												'type' => 'text',
												'prefix' => $this->context->currency->prefix.$this->context->currency->suffix,
												'desc' => $this->l('Multiplier applied to price code to obtain selling price tax incl.')
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'quote' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Quotation settings'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										$this->renderSwitch('KERAWEN_QUOTE_ACTIVE', $this->l('Display quotes on Front-Office')),
										$this->renderSwitch('KERAWEN_QUOTE_PRODUCT_NOTE', $this->l('Display product note')),
										$this->renderSwitch('KERAWEN_QUOTE_TAX', $this->l('Display taxes')),
										$this->renderSwitch('KERAWEN_QUOTE_DISP_TAX', $this->l('Display VAT on product row')),
										$this->renderSwitch('KERAWEN_QUOTE_DISP_UNIT_VAT', $this->l('Display unit price VAT include')),
										$this->renderSwitch('KERAWEN_QUOTE_DISP_TOTAL_VAT', $this->l('Display total price VAT include')),
										$this->renderSwitch('KERAWEN_QUOTE_REF_COL', $this->l('Display reference in individual column')),
										$this->renderSwitch('KERAWEN_QUOTE_IMG', $this->l('Display product image')),
										array(
												'name' => 'KERAWEN_QUOTE_DURATION',
												'label' => $this->l('Validity period'),
												'type' => 'text',
												'suffix' => $this->l('Days'),
										),
										array(
												'name' => 'KERAWEN_QUOTE_MESSAGE',
												'label' => $this->l('Thanks'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
												'lang' => true,
										),
										array(
												'name' => 'KERAWEN_QUOTE_MESSAGE_2',
												'label' => $this->l('Signature'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
												'lang' => true,
										),
										array(
												'name' => 'KERAWEN_QUOTE_MESSAGE_3',
												'label' => $this->l('Buy online'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
												'lang' => true,
										),
										array(
												'name' => 'KERAWEN_QUOTE_MESSAGE_4',
												'label' => $this->l('Termes and conditions'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
												'lang' => true,
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'invoice' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Invoice settings'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										$this->renderSwitch('KERAWEN_INVOICE_TAX', $this->l('Display taxes')),
										$this->renderSwitch('KERAWEN_INVOICE_NUM_ORDER', $this->l('Display order number')),
										$this->renderSwitch('KERAWEN_INVOICE_NUM_CART', $this->l('Display cart number')),
										$this->renderSwitch('KERAWEN_INVOICE_DISP_TAX', $this->l('Display VAT on product row')),
										$this->renderSwitch('KERAWEN_INVOICE_DISP_SHIPPING', $this->l('Display shipping as product')),
										$this->renderSwitch('KERAWEN_INVOICE_DISP_UNIT_VAT', $this->l('Display unit price VAT include')),
										$this->renderSwitch('KERAWEN_INVOICE_DISP_TOTAL_VAT', $this->l('Display total price VAT include')),
										$this->renderSwitch('KERAWEN_INVOICE_DISP_BARCODE', $this->l('Display barcode')),
										$this->renderSwitch('KERAWEN_INVOICE_REF_COL', $this->l('Display reference in individual column')),
										array(
												'name' => 'KERAWEN_INVOICE_HEADER_DATE',
												'label' => $this->l('Invoice header date'),
												'type' => 'radio',
												'class' => 't',
												'values' => array(
														array(
																'id' => 'date_order',
																'value' => 0,
																'label' => $this->l('Order'),
														),
														array(
																'id' => 'date_invoice',
																'value' => 1,
																'label' => $this->l('Invoice'),
														),
												),
										),
										array(
												'name' => 'KERAWEN_INVOICE_FREE_TEXT',
												'label' => $this->l('Invoice free text'),
												'type' => 'textarea',
												'cols' => 48,
												'rows' => 3,
												'lang' => true,
										),
								        $this->renderSwitch('KERAWEN_POSTCODE_REQUIRED', $this->l('Post code field is required')),
								        $this->renderSwitch('KERAWEN_ADDRESS1_REQUIRED', $this->l('Address field is required')),
								        $this->renderSwitch('KERAWEN_CITY_REQUIRED', $this->l('City field is required')),
								        $this->renderSwitch('KERAWEN_PHONE_REQUIRED', $this->l('Phone field is required')),
								        $this->renderSwitch('KERAWEN_MOBILE_REQUIRED', $this->l('Mobile field is required')),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'label' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Label settings'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
										array(
												'name' => 'KERAWEN_LABEL_ITEMS_BY_PAGE',
												'label' => $this->l('Items by page'),
												'type' => 'text',
										),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
				'reporting' => array(
						'form' => array(
								'legend' => array(
										'title' => $this->l('Reporting settings'),
										'icon' => 'icon-wrench',
								),
								'input' => array(
									array(
										'name' => 'KERAWEN_DECIMAL_SEPARATOR',
										'label' => $this->l('Export decimal separator'),
										'type'  => 'select',
										'options'  => array(
											'query' => array(
													array('char' => '.', 'name' => $this->l('dot')),
													array('char' => ',', 'name' => $this->l('comma')),
											),
											'id' => 'char',
											'value' => 'char',
											'name' => 'name',
										)
									),
								),
								'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
						),
				),
				
		);
		
		// Get the current parameter values
		$values = array();
		foreach ($forms as &$set)
			foreach ($set['form']['input'] as &$param)
			{
				$name = $param['name'];
				if ($param['type'] == 'free')
				{
					$values[$name] = $param['content'];
				}
				else switch ($name)
				{
					case 'KERAWEN_SHORTCUT_CATEGORIES':
						break;
					case 'KERAWEN_PREPAID_RELOAD':
						$values[$name] = 'TEST';
						break;

					default:
						if (isset($param['lang'])) {
							foreach ($this->_confLanguages as $language) {
								$txt = Configuration::get($name, $language['id_lang'], 0, 0);
								if (!$txt) {
									$txt = Configuration::get($name, 0, 0, 0);
								}
								$values[$name][$language['id_lang']] = $txt;
							}
						} else {
							$values[$name] = Configuration::get($name, 0, 0, 0);
						}
				}
			}
		
		// Render forms
		$this->setHelperDisplay(new HelperForm());
		$this->helper->tpl_vars = array(
				'fields_value' => $values,
				'id_language' => $this->context->language->id
		);
		
		return $this->helper->generateForm($forms);
	}
	
	protected function renderSubmit($name, $title)
	{
		$submit = array(
				'name' => $name,
				'title' => $title,
		);
		
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '<')) $submit['class'] = 'button';
		
		return $submit;
	}
	
	protected function renderSwitch($name, $label)
	{
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>='))
		{
			return array(
					'name' => $name,
					'label' => $label,
					'type' => 'switch',
					'is_bool' => true,
					'values' => array(
							array(
									'id' => 'on',
									'value' => 1,
									'label' => $this->l('Yes'),
							),
							array(
									'id' => 'off',
									'value' => 0,
									'label' => $this->l('No'),
							),
					),
			);
		}
		else
		{
			return array(
					'name' => $name,
					'label' => $label,
					'type' => 'radio',
					'class' => 't',
					'is_bool' => true,
					'values' => array(
							array(
									'id' => $name.'_on',
									'value' => 1,
									'label' => $this->l('Enabled'),
							),
							array(
									'id' => $name.'_off',
									'value' => 0,
									'label' => $this->l('Disabled'),
							),
					),
			);
		}
	}
	
	protected function renderShortcuts()
	{
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>='))
		{
			return array(
					'name' => 'KERAWEN_SHORTCUT_CATEGORIES',
					'label' => $this->l('Quick view categories'),
					'type'  => 'categories',
					'tree'  => array(
							'id' => 'KERAWEN_SHORTCUT_CATEGORIES',
							'use_checkbox' => true,
							'selected_categories' => explode(',', Configuration::get('KERAWEN_SHORTCUT_CATEGORIES')),
					),
			);
		}
		else
		{
			$root_cat = Category::getTopCategory();
			
			return array(
					'name' => 'KERAWEN_SHORTCUT_CATEGORIES',
					'label' => $this->l('Quick view categories'),
					'type'  => 'categories',
					'values' => array(
							'trads' => array(
									'Root' => array(
											'id_category' => $root_cat->id_category,
											'name' => $root_cat->name,
									),
									'selected' => $this->l('Selected'),
									'Collapse All' => $this->l('Collapse All'),
									'Expand All' => $this->l('Expand All'),
									'Check All' => $this->l('Check All'),
									'Uncheck All' => $this->l('Uncheck All'),
							),
							'use_radio' => false,
							'use_search' => false,
							'input_name' => 'KERAWEN_SHORTCUT_CATEGORIES[]',
							'selected_cat' => explode(',', Configuration::get('KERAWEN_SHORTCUT_CATEGORIES')),
							'disabled_categories' => array($root_cat->id_category),
							'top_category' => Category::getTopCategory(),
					),
			);
		}
	}

	protected function getValueLanguages($key) {
		$list = array();
		foreach ($this->_confLanguages as $language) {
			$list[$language['id_lang']] = Tools::getValue($key . '_' . $language['id_lang']);
		}
		return $list;
	}
	
	public function postProcess()
	{
		$access = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('KerawenConfig'));
		if ($access['edit']) {
			$backup = backupShopContext();
			Shop::setContext(Shop::CONTEXT_ALL);
						
			$infos = array();
			$errors = array();
			
			if (Tools::isSubmit('submitLegacy')) {
				$this->processLegacy($infos, $errors);
			}
			if (/*Tools::isSubmit('submitConfig') ||*/ Tools::isSubmit('submitWarnings')) {
				$this->processWarnings($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitRegister')) {
				$this->processRegister($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitPayment')) {
				$this->processPayment($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitDisplay')) {
				$this->processDisplay($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitCustomerDisplay')) {
				$this->processCustomerDisplay($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitTicket')) {
				$this->processTicket($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitScale')) {
				$this->processScale($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitQuote')) {
				$this->processQuote($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitInvoice')) {
				$this->processInvoice($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitLabel')) {
				$this->processLabel($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitGift')) {
				$this->processGift($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitModes')) {
				$this->processModes($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitReporting')) {
				$this->processReporting($infos, $errors);
			}
			if (Tools::isSubmit('submitConfig') || Tools::isSubmit('submitCustomer')) {
			    $this->processCustomer($infos, $errors);
			}
			// Report
			if (count($errors)) {
				$this->errors[] = (implode($errors, '<br>'));
			}
			if (count($infos)) {
				$this->confirmations[] = (implode($infos, '<br>'));
			}
			
			restoreShopContext($backup);
		}
		$this->display = 'view';
	}
	
	protected function processLegacy(&$infos, &$errors)
	{
		if (Tools::getValue('KERAWEN_LEGACY_ORDERS'))
		{
			require_once(_KERAWEN_CLASS_DIR_.'/order.php');
			$count = legacyOrders(true);
			//$infos[] = $count.' '.$this->l('legacy orders referenced');
			
			require_once( _KERAWEN_INSTALL_DIR_ . 'database.php' );
			completeData($errors);
			
			$infos[] = $this->l('Legacy order referenced');
			
		}
		else
		{
			$errors[] = $this->l('No legacy order selected');
		}
		
	}
	
	protected function processWarnings(&$infos)
	{
		Configuration::updateValue('KERAWEN_IGNORE_WARNINGS', (int)Tools::getValue('KERAWEN_IGNORE_WARNINGS'));
		$infos[] = $this->l('Warning parameters updated');
	}
	
	protected function processRegister(&$infos)
	{
		Configuration::updateValue('KERAWEN_NOTIF_PERIOD', (int)Tools::getValue('KERAWEN_NOTIF_PERIOD'));
		Configuration::updateValue('KERAWEN_DISCOUNT_DURATION', (int)Tools::getValue('KERAWEN_DISCOUNT_DURATION'));
		Configuration::updateValue('KERAWEN_SWITCH_CASHIER', (int)Tools::getValue('KERAWEN_SWITCH_CASHIER'));
		Configuration::updateValue('KERAWEN_EMPLOYEE_PASSWORD', (int)Tools::getValue('KERAWEN_EMPLOYEE_PASSWORD'));
		Configuration::updateValue('KERAWEN_EMPLOYEE_PASSWORD_EXCP', (int)Tools::getValue('KERAWEN_EMPLOYEE_PASSWORD_EXCP'));
		Configuration::updateValue('KERAWEN_SWITCH_SHOP', (int)Tools::getValue('KERAWEN_SWITCH_SHOP'));
		Configuration::updateValue('KERAWEN_SELECT_DELIVERY', (int)Tools::getValue('KERAWEN_SELECT_DELIVERY'));
		Configuration::updateValue('KERAWEN_GATHER_MEASURES', (int)Tools::getValue('KERAWEN_GATHER_MEASURES'));
		Configuration::updateValue('KERAWEN_DISCOUNT_CART', (int)Tools::getValue('KERAWEN_DISCOUNT_CART'));
		Configuration::updateValue('KERAWEN_DEFAULT_GROUP', (int)Tools::getValue('KERAWEN_DEFAULT_GROUP'));
		Configuration::updateValue('KERAWEN_OVERRIDE_GROUP', (int)Tools::getValue('KERAWEN_OVERRIDE_GROUP'));
		Configuration::updateValue('KERAWEN_SHOW_AMOUNTS', (int)Tools::getValue('KERAWEN_SHOW_AMOUNTS'));
		Configuration::updateValue('KERAWEN_SEND_EMAIL', (int)Tools::getValue('KERAWEN_SEND_EMAIL'));
		Configuration::updateValue('KERAWEN_OVERRIDE_EMAIL', (int)Tools::getValue('KERAWEN_OVERRIDE_EMAIL'));
		Configuration::updateValue('KERAWEN_DISPLAY_MARGIN', (int)Tools::getValue('KERAWEN_DISPLAY_MARGIN'));
		Configuration::updateValue('KERAWEN_ORDER_QUICK_END', (int)Tools::getValue('KERAWEN_ORDER_QUICK_END'));
		Configuration::updateValue('KERAWEN_ORDER_PULSE', (int)Tools::getValue('KERAWEN_ORDER_PULSE'));
		Configuration::updateValue('KERAWEN_PLAY_SOUND', (int)Tools::getValue('KERAWEN_PLAY_SOUND'));
		Configuration::updateValue('KERAWEN_SCAN_NOT_FOUND', (int)Tools::getValue('KERAWEN_SCAN_NOT_FOUND'));
		Configuration::updateValue('KERAWEN_DEFAULT_VAT', (int)Tools::getValue('KERAWEN_DEFAULT_VAT'));
		
		$shortcuts = array();
		$buf = Tools::getValue('KERAWEN_SHORTCUT_CATEGORIES');
		if (is_array($buf)) foreach ($buf as $id_cat)
			if ($id_cat) $shortcuts[] = $id_cat;
			Configuration::updateValue('KERAWEN_SHORTCUT_CATEGORIES', implode(',', $shortcuts));
			
			$infos[] = $this->l('Cash register parameters updated');
	}
	
	protected function processDisplay(&$infos)
	{
		Configuration::updateValue('KERAWEN_CATALOG_PAGE_SIZE', (int)Tools::getValue('KERAWEN_CATALOG_PAGE_SIZE'));
		Configuration::updateValue('KERAWEN_ORDERS_LIST_ITEMS_BY_PAG', (int)Tools::getValue('KERAWEN_ORDERS_LIST_ITEMS_BY_PAG'));
		Configuration::updateValue('KERAWEN_CATALOG_FULL_NAMES', (int)Tools::getValue('KERAWEN_CATALOG_FULL_NAMES'));
		Configuration::updateValue('KERAWEN_CATALOG_REFERENCES', (int)Tools::getValue('KERAWEN_CATALOG_REFERENCES'));
		Configuration::updateValue('KERAWEN_CART_FULL_NAMES', (int)Tools::getValue('KERAWEN_CART_FULL_NAMES'));
		Configuration::updateValue('KERAWEN_ORDERS_LIST_COLUMN_SHOP', (int)Tools::getValue('KERAWEN_ORDERS_LIST_COLUMN_SHOP'));
		Configuration::updateValue('KERAWEN_ORDERS_LIST_COLUMN_CARRI', (int)Tools::getValue('KERAWEN_ORDERS_LIST_COLUMN_CARRI'));
		Configuration::updateValue('KERAWEN_ORDERS_LIST_COLUMN_TILL', (int)Tools::getValue('KERAWEN_ORDERS_LIST_COLUMN_TILL'));
		Configuration::updateValue('KERAWEN_ORDERS_LIST_COLUMN_COMP', (int)Tools::getValue('KERAWEN_ORDERS_LIST_COLUMN_COMP'));
		Configuration::updateValue('KERAWEN_ORDERS_LIST_COLUMN_ADD', (int)Tools::getValue('KERAWEN_ORDERS_LIST_COLUMN_ADD'));
		Configuration::updateValue('KERAWEN_CUST_ACCOUNT_ADDR', (int)Tools::getValue('KERAWEN_CUST_ACCOUNT_ADDR'));
		Configuration::updateValue('KERAWEN_IMAGE_PRODUCT', Tools::getValue('KERAWEN_IMAGE_PRODUCT'));
		Configuration::updateValue('KERAWEN_IMAGE_CATEGORY', Tools::getValue('KERAWEN_IMAGE_CATEGORY'));
		$infos[] = $this->l('Display parameters updated');
	}
	
	
	protected function processCustomerDisplay(&$infos, &$errors)
	{
		$on_error = false;
		
		$cpl = (int)Tools::getValue('KERAWEN_DISPLAY_CPL');
		if ($cpl > 0) {
			Configuration::updateValue('KERAWEN_DISPLAY_CPL', $cpl);
		} else {
			$errors[] = $this->l('Invalid characters per line');
			$on_error = true;
		}
		
		Configuration::updateValue('KERAWEN_DISPLAY_MSG_START', Tools::getValue('KERAWEN_DISPLAY_MSG_START'));
		Configuration::updateValue('KERAWEN_DISPLAY_MSG_END', Tools::getValue('KERAWEN_DISPLAY_MSG_END'));
		
		if (!$on_error) {
			$infos[] = $this->l('Customer display parameters updated');
		}
	}
	
	
	protected function processTicket(&$infos, &$errors)
	{
		$on_error = false;
		$to_unlink = null;
		
		$cpl = (int)Tools::getValue('KERAWEN_TICKET_CPL');
		if ($cpl > 0)
			Configuration::updateValue('KERAWEN_TICKET_CPL', $cpl);
			else
			{
				$errors[] = $this->l('Invalid characters per line');
				$on_error = true;
			}
			
			if ($_FILES['KERAWEN_TICKET_IMAGE_UPLOAD'] && $_FILES['KERAWEN_TICKET_IMAGE_UPLOAD']['tmp_name'])
			{
				$file = $_FILES['KERAWEN_TICKET_IMAGE_UPLOAD'];
				$res = ImageManager::validateUpload($file);
				if (!$res)
				{
					// It is valid!
					// Use temp name to avoid browser cache
					$tmp = tempnam(_PS_IMG_DIR_, 'tic');
					$img = pathinfo($tmp, PATHINFO_FILENAME).'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
					if (move_uploaded_file($file['tmp_name'], _PS_IMG_DIR_.$img))
					{
						$to_unlink = Configuration::get('KERAWEN_TICKET_IMAGE');
						Configuration::updateValue('KERAWEN_TICKET_IMAGE', $img);
					}
					else
					{
						$errors[] = $this->l('Cannot upload image');
						$on_error = true;
					}
					unlink($tmp);
				}
				else
				{
					$errors[] = $res;
					$on_error = true;
				}
			}
			else if (Tools::getValue('KERAWEN_TICKET_IMAGE_DELETE'))
			{
				$to_unlink = Configuration::get('KERAWEN_TICKET_IMAGE');
				Configuration::updateValue('KERAWEN_TICKET_IMAGE', null);
			}
			if ($to_unlink)
				unlink(_PS_IMG_DIR_.$to_unlink);

				Configuration::updateValue('KERAWEN_TICKET_SHOP_COUNTRY', (int)Tools::getValue('KERAWEN_TICKET_SHOP_COUNTRY'));
				Configuration::updateValue('KERAWEN_TICKET_SHOP_URL', (int)Tools::getValue('KERAWEN_TICKET_SHOP_URL'));
				Configuration::updateValue('KERAWEN_TICKET_SHOP_EMAIL', (int)Tools::getValue('KERAWEN_TICKET_SHOP_EMAIL'));
				Configuration::updateValue('KERAWEN_TICKET_SHOP_DETAILS', (int)Tools::getValue('KERAWEN_TICKET_SHOP_DETAILS'));
				Configuration::updateValue('KERAWEN_TICKET_COMMENTS', (int)Tools::getValue('KERAWEN_TICKET_COMMENTS'));
				Configuration::updateValue('KERAWEN_TICKET_PRODUCT_NOTE', (int)Tools::getValue('KERAWEN_TICKET_PRODUCT_NOTE'));
				Configuration::updateValue('KERAWEN_TICKET_FULL_NAMES', (int)Tools::getValue('KERAWEN_TICKET_FULL_NAMES'));
				Configuration::updateValue('KERAWEN_TICKET_TAXES', (int)Tools::getValue('KERAWEN_TICKET_TAXES'));
				Configuration::updateValue('KERAWEN_TICKET_DETAIL_TAXES', (int)Tools::getValue('KERAWEN_TICKET_DETAIL_TAXES'));
				Configuration::updateValue('KERAWEN_TICKET_MODE', (int)Tools::getValue('KERAWEN_TICKET_MODE'));
				Configuration::updateValue('KERAWEN_TICKET_CUSTOMER', (int)Tools::getValue('KERAWEN_TICKET_CUSTOMER'));
				Configuration::updateValue('KERAWEN_TICKET_CUSTOMER_PHONE', (int)Tools::getValue('KERAWEN_TICKET_CUSTOMER_PHONE'));
				Configuration::updateValue('KERAWEN_TICKET_REF', (int)Tools::getValue('KERAWEN_TICKET_REF'));
				Configuration::updateValue('KERAWEN_TICKET_EMPLOYEE_NAME', (int)Tools::getValue('KERAWEN_TICKET_EMPLOYEE_NAME'));
				Configuration::updateValue('KERAWEN_TICKET_LOYALTY', (int)Tools::getValue('KERAWEN_TICKET_LOYALTY'));
				Configuration::updateValue('KERAWEN_TICKET_MESSAGE', Tools::getValue('KERAWEN_TICKET_MESSAGE'));
				Configuration::updateValue('KERAWEN_TICKET_BARCODE', (int)Tools::getValue('KERAWEN_TICKET_BARCODE'));
				Configuration::updateValue('KERAWEN_TICKET_ORDER_NUMBER', (int)Tools::getValue('KERAWEN_TICKET_ORDER_NUMBER'));
				Configuration::updateValue('KERAWEN_TICKET_PRINT_OPEN_CLOSE', (int)Tools::getValue('KERAWEN_TICKET_PRINT_OPEN_CLOSE'));
				Configuration::updateValue('KERAWEN_TICKET_PRINT_MIN_AMOUNT', Tools::getValue('KERAWEN_TICKET_PRINT_MIN_AMOUNT') == '' ? '' : (int) Tools::getValue('KERAWEN_TICKET_PRINT_MIN_AMOUNT'));
				Configuration::updateValue('KERAWEN_TICKET_PRINT_AUTO', (int)Tools::getValue('KERAWEN_TICKET_PRINT_AUTO'));
				Configuration::updateValue('KERAWEN_TICKET_MSG_DISCOUNT', Tools::getValue('KERAWEN_TICKET_MSG_DISCOUNT'));
				

				if (!$on_error)
					$infos[] = $this->l('Ticket parameters updated');
	}
	
	protected function processScale(&$infos)
	{
		Configuration::updateValue('KERAWEN_SCALE_PREFIX', Tools::getValue('KERAWEN_SCALE_PREFIX'));
		Configuration::updateValue('KERAWEN_SCALE_PRODUCT_LENGTH', (int)Tools::getValue('KERAWEN_SCALE_PRODUCT_LENGTH'));
		Configuration::updateValue('KERAWEN_SCALE_PRICE_LENGTH', (int)Tools::getValue('KERAWEN_SCALE_PRICE_LENGTH'));
		Configuration::updateValue('KERAWEN_SCALE_PRICE_MULTIPLIER', (float)Tools::getValue('KERAWEN_SCALE_PRICE_MULTIPLIER'));
		$infos[] = $this->l('Scale parameters updated');
	}
	
	protected function processQuote(&$infos)
	{
		Configuration::updateValue('KERAWEN_QUOTE_ACTIVE', Tools::getValue('KERAWEN_QUOTE_ACTIVE'));
		Configuration::updateValue('KERAWEN_QUOTE_PRODUCT_NOTE', Tools::getValue('KERAWEN_QUOTE_PRODUCT_NOTE'));
		Configuration::updateValue('KERAWEN_QUOTE_DURATION', (int)Tools::getValue('KERAWEN_QUOTE_DURATION'));
		Configuration::updateValue('KERAWEN_QUOTE_MESSAGE',   $this->getValueLanguages('KERAWEN_QUOTE_MESSAGE'));
		Configuration::updateValue('KERAWEN_QUOTE_MESSAGE_2', $this->getValueLanguages('KERAWEN_QUOTE_MESSAGE_2'));
		Configuration::updateValue('KERAWEN_QUOTE_MESSAGE_3', $this->getValueLanguages('KERAWEN_QUOTE_MESSAGE_3'));
		Configuration::updateValue('KERAWEN_QUOTE_MESSAGE_4', $this->getValueLanguages('KERAWEN_QUOTE_MESSAGE_4'));
		Configuration::updateValue('KERAWEN_QUOTE_TAX', (int)Tools::getValue('KERAWEN_QUOTE_TAX'));
		Configuration::updateValue('KERAWEN_QUOTE_DISP_TAX', (int)Tools::getValue('KERAWEN_QUOTE_DISP_TAX'));
		Configuration::updateValue('KERAWEN_QUOTE_DISP_UNIT_VAT', (int)Tools::getValue('KERAWEN_QUOTE_DISP_UNIT_VAT'));
		Configuration::updateValue('KERAWEN_QUOTE_DISP_TOTAL_VAT', (int)Tools::getValue('KERAWEN_QUOTE_DISP_TOTAL_VAT'));
		Configuration::updateValue('KERAWEN_QUOTE_REF_COL', (int)Tools::getValue('KERAWEN_QUOTE_REF_COL'));
		Configuration::updateValue('KERAWEN_QUOTE_IMG', (int)Tools::getValue('KERAWEN_QUOTE_IMG'));
				
		$infos[] = $this->l('Quotation parameters updated');
	}
	
	
	protected function processLabel(&$infos)
	{
		Configuration::updateValue('KERAWEN_LABEL_ITEMS_BY_PAGE', (int)Tools::getValue('KERAWEN_LABEL_ITEMS_BY_PAGE') );
		$infos[] = $this->l('Label parameters updated');
	}

	protected function processReporting(&$infos)
	{
		Configuration::updateValue('KERAWEN_DECIMAL_SEPARATOR', Tools::getValue('KERAWEN_DECIMAL_SEPARATOR') );
		$infos[] = $this->l('Reporting parameters updated');
	}
	
	protected function processCustomer(&$infos)
	{
	    Configuration::updateValue('KERAWEN_CUST_PRINT', (int)Tools::getValue('KERAWEN_CUST_PRINT') );
	    Configuration::updateValue('KERAWEN_CUST_HEADER_MESSAGE', Tools::getValue('KERAWEN_CUST_HEADER_MESSAGE') );
	    Configuration::updateValue('KERAWEN_CUST_FOOTER_MESSAGE', Tools::getValue('KERAWEN_CUST_FOOTER_MESSAGE') );
	    $infos[] = $this->l('Customer card parameters updated');
	}
	
	protected function processInvoice(&$infos)
	{
	    Configuration::updateValue('KERAWEN_ADDRESS1_REQUIRED', (int)Tools::getValue('KERAWEN_ADDRESS1_REQUIRED') );
	    Configuration::updateValue('KERAWEN_CITY_REQUIRED', (int)Tools::getValue('KERAWEN_CITY_REQUIRED') );
	    Configuration::updateValue('KERAWEN_POSTCODE_REQUIRED', (int)Tools::getValue('KERAWEN_POSTCODE_REQUIRED') );
		Configuration::updateValue('KERAWEN_INVOICE_FREE_TEXT', $this->getValueLanguages('KERAWEN_INVOICE_FREE_TEXT'), true );
		Configuration::updateValue('KERAWEN_INVOICE_TAX', (int)Tools::getValue('KERAWEN_INVOICE_TAX') );
		Configuration::updateValue('KERAWEN_INVOICE_NUM_ORDER', (int)Tools::getValue('KERAWEN_INVOICE_NUM_ORDER') );
		Configuration::updateValue('KERAWEN_INVOICE_NUM_CART', (int)Tools::getValue('KERAWEN_INVOICE_NUM_CART') );
		Configuration::updateValue('KERAWEN_INVOICE_DISP_TAX', (int)Tools::getValue('KERAWEN_INVOICE_DISP_TAX') );
		Configuration::updateValue('KERAWEN_INVOICE_DISP_SHIPPING', (int)Tools::getValue('KERAWEN_INVOICE_DISP_SHIPPING') );
		Configuration::updateValue('KERAWEN_INVOICE_DISP_UNIT_VAT', (int)Tools::getValue('KERAWEN_INVOICE_DISP_UNIT_VAT') );
		Configuration::updateValue('KERAWEN_INVOICE_DISP_TOTAL_VAT', (int)Tools::getValue('KERAWEN_INVOICE_DISP_TOTAL_VAT') );
		Configuration::updateValue('KERAWEN_INVOICE_DISP_BARCODE', (int)Tools::getValue('KERAWEN_INVOICE_DISP_BARCODE') );
		Configuration::updateValue('KERAWEN_INVOICE_REF_COL', (int)Tools::getValue('KERAWEN_INVOICE_REF_COL') );
		Configuration::updateValue('KERAWEN_INVOICE_HEADER_DATE', (int)Tools::getValue('KERAWEN_INVOICE_HEADER_DATE') );
		Configuration::updateValue('KERAWEN_POSTCODE_REQUIRED', (int)Tools::getValue('KERAWEN_POSTCODE_REQUIRED') );
		Configuration::updateValue('KERAWEN_ADDRESS1_REQUIRED', (int)Tools::getValue('KERAWEN_ADDRESS1_REQUIRED') );
		Configuration::updateValue('KERAWEN_CITY_REQUIRED', (int)Tools::getValue('KERAWEN_CITY_REQUIRED') );
		Configuration::updateValue('KERAWEN_PHONE_REQUIRED', (int)Tools::getValue('KERAWEN_PHONE_REQUIRED') );
		Configuration::updateValue('KERAWEN_MOBILE_REQUIRED', (int)Tools::getValue('KERAWEN_MOBILE_REQUIRED') );
		$infos[] = $this->l('Invoice parameters updated');
	}
	
	protected function processModes(&$infos)
	{
		Configuration::updateValue('KERAWEN_LABEL_IN_STORE', Tools::getValue('KERAWEN_LABEL_IN_STORE') );
		Configuration::updateValue('KERAWEN_LABEL_TAKEAWAY', Tools::getValue('KERAWEN_LABEL_TAKEAWAY') );
		Configuration::updateValue('KERAWEN_LABEL_DELIVERY', Tools::getValue('KERAWEN_LABEL_DELIVERY') );
		$infos[] = $this->l('Delivery Modes parameters updated');
	}
	
	protected function processGift(&$infos)
	{
		Configuration::updateValue('KERAWEN_GIFT_CARD_DURATION', (int)Tools::getValue('KERAWEN_GIFT_CARD_DURATION') );
		Configuration::updateValue('KERAWEN_GIFT_CARD_TICKET_MESSAGE', Tools::getValue('KERAWEN_GIFT_CARD_TICKET_MESSAGE') );
		Configuration::updateValue('KERAWEN_GIFT_CARD_JS', Tools::getValue('KERAWEN_GIFT_CARD_JS') );
		$infos[] = $this->l('Gift card parameters updated');
	}
	
	
	protected function getGiftCardCronUrl()
	{
		$id_lang = null;
		$id_shop = null;
		$link = new Link;
		return $link->getPageLink('cron', null, $id_lang, 'fc=module&module=kerawen&action=giftcard&key=' . Configuration::get('KERAWEN_CRON_KEY'), false, $id_shop);
	}
	
	
	protected function getCategories()
	{
		$context = Context::getContext();
		return Db::getInstance()->executeS('
			SELECT cl.id_category AS id, cl.name AS name
			FROM '._DB_PREFIX_.'category_lang cl
			WHERE id_lang = '.pSQL($context->language->id));
	}
	
	
	protected function renderPaymentBlock()
	{
		$input = array();
		
		if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>='))
		{
			$header = array(
					'name' => '_KERAWEN_PM_LIST_',
					'type' => 'free',
					'content' => '
					<div class="col-lg-3">
						<div style="text-align:center">'.$this->l('Payment').'</div>
					</div>
					<div class="col-lg-3">
						<div style="text-align:center">'.$this->l('Refunding').'</div>
					</div>',
			);
		}
		else
		{
			$header = array(
					'name' => '_KERAWEN_PM_LIST_',
					'type' => 'free',
					'content' => '
					<div class="kerawen-config-payment-block">
						<span>'.$this->l('Payment').'</span>
					</div>
					<div class="kerawen-config-payment-block">
						<span>'.$this->l('Refunding').'</span>
					</div>',
			);
		}
		$input[] = $header;
		
		$cfg = Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
		$modes = KerawenPayment::getModes($this->module);
		$checked = ' checked="checked"';
		foreach ($modes as $mode)
		{
			$id = (int)$mode['id'];
			$name = 'KERAWEN_PM_'.$id;
			$label = isset($cfg[$id]['label']) ? $cfg[$id]['label'] : $mode['label'];
			$payment = isset($cfg[$id]['payment']) ? $cfg[$id]['payment'] : $mode['payment'];
			$refund = isset($cfg[$id]['refund']) ? $cfg[$id]['refund'] : $mode['refund'];
			
			if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>='))
			{
				$content = '
					<div class="col-lg-3">
						<span class="switch prestashop-switch fixed-width-lg">
							<input type="radio" name="'.$name.'_PAYMENT" id="'.$name.'_PAYMENT_on" value="1"'.($payment ? $checked : '').'>
							<label for="'.$name.'_PAYMENT_on">'.$this->l('Yes').'</label>
							<input type="radio" name="'.$name.'_PAYMENT" id="'.$name.'_PAYMENT_off" value="0"'.($payment ? '' : $checked).'>
							<label for="'.$name.'_PAYMENT_off">'.$this->l('No').'</label>
							<a class="slide-button btn"></a>
						</span>
					</div>
					<div class="col-lg-3 ">
						<span class="switch prestashop-switch fixed-width-lg">
							<input type="radio" name="'.$name.'_REFUND" id="'.$name.'_REFUND_on" value="1"'.($refund ? $checked : '').'>
							<label for="'.$name.'_REFUND_on">'.$this->l('Yes').'</label>
							<input type="radio" name="'.$name.'_REFUND" id="'.$name.'_REFUND_off" value="0"'.($refund ? '' : $checked).'>
							<label for="'.$name.'_REFUND_off">'.$this->l('No').'</label>
							<a class="slide-button btn"></a>
						</span>
					</div>';
			}
			else
			{
				$content = '
					<div class="kerawen-config-payment-block">
						<input type="radio" name="'.$name.'_PAYMENT" id="'.$name.'_PAYMENT_on" value="1"'.($payment ? $checked : '').'>
						<label class="t" for="'.$name.'_PAYMENT_on">
							<img src="../img/admin/enabled.gif" alt="Activé" title="Activé">
						</label>
						<input type="radio" name="'.$name.'_PAYMENT" id="'.$name.'_PAYMENT_off" value="0"'.($payment ? '' : $checked).'>
						<label class="t" for="'.$name.'_PAYMENT_off">
							<img src="../img/admin/disabled.gif" alt="Désactivé" title="Désactivé">
						</label>
					</div>
					<div class="kerawen-config-payment-block">
						<input type="radio" name="'.$name.'_REFUND" id="'.$name.'_REFUND_on" value="1"'.($refund ? $checked : '').'>
						<label class="t" for="'.$name.'_REFUND_on">
							<img src="../img/admin/enabled.gif" alt="Activé" title="Activé">
						</label>
						<input type="radio" name="'.$name.'_REFUND" id="'.$name.'_REFUND_off" value="0"'.($refund ? '' : $checked).'>
						<label class="t" for="'.$name.'_REFUND_off">
							<img src="../img/admin/disabled.gif" alt="Désactivé" title="Désactivé">
						</label>
					</div>';
			}
			
			if ($id >= _KERAWEN_PM_OTHER1_)
				$label = '<input type="text" class="kerawen-config-payment-input" name="'.$name.'_LABEL" value="'.$label.'"/>';
				
				$row = array(
						'name' => $name,
						'label' => $label,
						'type' => 'free',
						'content' => $content,
				);
				if ($id >= _KERAWEN_PM_OTHER1_) $row['class'] = 'label-editable';
				
				$input[] = $row;
		}
		return $input;
	}
	
	protected function processPayment(&$infos)
	{
		$payments = array();
		$modes = KerawenPayment::getModes($this->module);
		foreach ($modes as $mode)
		{
			$id = $mode['id'];
			$label = Tools::getValue('KERAWEN_PM_'.$id.'_LABEL');
			
			$payments[$id] = array(
					'id' => $id,
					'label' => $label ? $label : $mode['label'],
					'payment' => (int)Tools::getValue('KERAWEN_PM_'.$id.'_PAYMENT'),
					'refund' => (int)Tools::getValue('KERAWEN_PM_'.$id.'_REFUND'),
			);
		}
		Configuration::updateValue('KERAWEN_PAYMENTS', (string)Tools::jsonEncode($payments));
		$infos[] = $this->l('Payment parameters updated');
	}
	
	protected function renderImageTypes($name, $label, $for) {
		return array(
			'name' => $name,
			'label' => $label,
			'type'  => 'select',
			'options'  => array(
				'query' => array_merge(
					array(
						array(
							'value' => false,
							'name' => $this->l('No image')
						)),
					Db::getInstance()->executeS('
						SELECT name AS value, name
						FROM '._DB_PREFIX_.'image_type
						WHERE '.$for.' = 1')),
				'id' => 'value',
				'value' => 'value',
				'name' => 'name',
			)
		);
	}
}
