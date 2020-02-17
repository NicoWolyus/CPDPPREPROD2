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


class kerawenPermissions {

	function getKerawenPermissionsLabel() {
		return 'KERAWEN_PERMISSIONS';
	}	

	function l($string) {
		return Translate::getModuleTranslation('kerawen', $string, 'permissions');
	}
	
	
	function getPaymentModes() {
		
		return Tools::jsonDecode(Configuration::get('KERAWEN_PAYMENTS'), true);
	}
	
	
	function getSharedGlobalList() {
		return array(
			array('label' => $this->l('All shops'), 'value' => 'all_shops'),
			array('label' => $this->l('My shops'), 'value' => 'my_shops'),
			array('label' => $this->l('My shop'), 'value' => 'my_shop'),
			array('label' => $this->l('My sales') , 'value' => 'my_sales'),
			array('label' => $this->l('None') , 'value' => 'none'),
		);
	}
	
	function getSharedYesNo() {
		return array(
			array('label' => $this->l('Yes'), 'value' => 'true'),
			array('label' => $this->l('No'), 'value' => 'false'),
		);
	}
	
	
	
	function getPaymentList() {
		
		$payments = $this->getPaymentModes();

		$list = array();
		foreach($payments as $payment) {
			
			$sublist = array();
			$option_payment = '';
			$option_refund = '';
			if ($payment['payment']) {
				$sublist[] = 'payment';
			} else {
				$option_payment = 'disabled';
			}
			if ($payment['refund']) {
				$sublist[] = 'refund';
			} else {
				$option_refund = 'disabled';
			}
			$default = implode(",", $sublist);
			
			$list['mode_' . $payment['id']] = array(
				'label' => $payment['label'],
				'type' => 'checkbox',
				'default' => $default,
				'full' => $default,
				'action' => '',
				'items' => array(
					array('label' => $this->l('Payment'), 'value' => 'payment', 'option' => $option_payment),
					array('label' => $this->l('Refund'), 'value' => 'refund', 'option' => $option_refund),
				),
			);
		}
		return $list;

	}
	

	function getKerawenPermissionsModele() {
		/*
		 * !!! Please use only string for value field (no boolean, no number, no table, ....) 
		*/
		
		$data = array(
			$this->l('Catalog') => array(
				'catalogCreate' => array(
					'label' => $this->l('Create product'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'catalogWholesale' => array(
					'label' => $this->l('Display wholesale price'),
					'type' => 'radio',
					'default' => 'false',
					'full' => 'false',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'catalogStock' => array(
					'label' => $this->l('Edit stock'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
			),
			$this->l('Orders') => array(
				'ordersDisplay' => array(
					'label' => $this->l('Display'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'orderReturn' => array(
					'label' => $this->l('Return'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'orderChangeStat' => array(
					'label' => $this->l('Change stat'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'orderEditNoteArticle' => array(
					'label' => $this->l('Edit article note'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),	
				'orderEditNote' => array(
					'label' => $this->l('Edit order note'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'orderEditPaymentMethod' => array(
					'label' => $this->l('Edit payment method'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
			),
			$this->l('Quotation') => array(
				'quoteAdd' => array(
					'label' => $this->l('Create'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),	
				'quoteDisplay' => array(
					'label' => $this->l('Display list'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'quoteEdit' => array(
					'label' => $this->l('Edit'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'quoteDelete' => array(
					'label' => $this->l('Delete'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'quoteDownload' => array(
					'label' => $this->l('Download'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
			),
			$this->l('Cart') => array(
				'cartEditPrice' => array(
					'label' => $this->l('Edit price'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cartEditDiscount' => array(
					'label' => $this->l('Edit discount'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cartMargin' => array(
					'label' => $this->l('Display margin'),
					'type' => 'radio',
					'default' => 'false',
					'full' => 'false',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cartDisplayActive' => array(
					'label' => $this->l('Display active carts'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),	
				'cartDisplaySuspended' => array(
					'label' => $this->l('Display suspended carts'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'cartSelectActive' => array(
					'label' => $this->l('Select active carts'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
				'cartSelectSuspended' => array(
					'label' => $this->l('Select suspended carts'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'filter_shop',
					'items' => $this->getSharedGlobalList(),
				),
			    'cartDeleteAll' => array(
			        'label' => $this->l('Delete cart'),
			        'type' => 'radio',
			        'default' => 'true',
			        'full' => 'true',
			        'action' => 'boolean',
			        'items' => $this->getSharedYesNo(),
			    ),
			    'cartDeleteItem' => array(
			        'label' => $this->l('Delete cart item'),
			        'type' => 'radio',
			        'default' => 'true',
			        'full' => 'true',
			        'action' => 'boolean',
			        'items' => $this->getSharedYesNo(),
			    ),
			),
			$this->l('Discounts') => array(
				'displayDiscount' => array(
					'label' => $this->l('Display list'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'selectDiscount' => array(
					'label' => $this->l('Select'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'addDiscCredit' => array(
					'label' => $this->l('Add credit'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'addDiscDiscount' => array(
					'label' => $this->l('Add discount'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'addDiscLoyalty' => array(
					'label' => $this->l('Add loyalty'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),	
			),
			$this->l('Payment/Refund modes') => $this->getPaymentList(),

			$this->l('Cashdrawer') => array(
				'cashdrawerNew' => array(
					'label' => $this->l('Add till'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cashdrawerEdit' => array(
					'label' => $this->l('Edit till'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cashdrawerActivate' => array(
					'label' => $this->l('Activate till'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cashdrawerDisplayReport' => array(
					'label' => $this->l('Display reports'),
					'type' => 'radio',
					'default' => 'false',
					'full' => 'false',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'cashdrawerDisplayHardware' => array(
					'label' => $this->l('Display hardware'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
				'login' => array(
					'label' => $this->l('Connexion as client'),
					'type' => 'radio',
					'default' => 'false',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
			),
				
			$this->l('Reporting') => array(
				'repShop' => array(
					'label' => $this->l('Shops'),
					'type' => 'radio',
					'default' => 'all_shops',
					'full' => 'all_shops',
					'action' => 'default',
					'items' => array(
						array('label' => $this->l('All shops'), 'value' => 'all_shops'),
						array('label' => $this->l('My shops'), 'value' => 'my_shops'),
					),
				),
				'repWeb' => array(
					'label' => $this->l('Display web orders'),
					'type' => 'radio',
					'default' => 'true',
					'full' => 'true',
					'action' => 'boolean',
					'items' => $this->getSharedYesNo(),
				),
			),
				
		);
		
		return $data;
		
	}
	
	function setKerawenPermissionsData($data) {
		Configuration::updateValue($this->getKerawenPermissionsLabel(), serialize($data), null, 0, 0);
		return 1;
	}

	
	function getKerawenPermissionsInitial() {
		$data = unserialize(Configuration::get($this->getKerawenPermissionsLabel()));
		if (!is_array($data) ) { $data = array(); }
		return $data;
	}
	

	function getKerawenPermissionsData() {
		
		$data = $this->getKerawenPermissionsInitial();
		$defaultData = $this->getKerawenDefaultPermissions();
		
		$profiles = Profile::getProfiles(Context::getContext()->language->id);
		foreach($profiles as $profile) {
			
			if ( isset($data[(int)$profile['id_profile']]) ) {
				foreach($defaultData as $k => $itemdata) {
					if ( empty($data[(int)$profile['id_profile']][$k]) ) {
						$data[(int)$profile['id_profile']][$k] = $itemdata;
					}					
				}
			} else {
				$data[(int)$profile['id_profile']] = $defaultData;
			}

		}
		
		return $data;
	}

	
	function getKerawenFullPermissionsData() {
	
		$defaultData = $this->getKerawenFullPermissions();
	
		$profiles = Profile::getProfiles(Context::getContext()->language->id);
		foreach($profiles as $profile) {
			$data[(int)$profile['id_profile']] = $defaultData;
		}
	
		return $data;
	}	
	
	
	function getKerawenPermissionsByEmployee($id_empl) {
		
		
		/*on payment mode list change*/
		
		
		$employee = new Employee($id_empl);
		$id_employee =  (int) $employee->id;
		$id_profile = (int) $employee->id_profile;
		
		$shop = (int) Context::getContext()->shop->id;		
		$shops = implode(',', $employee->getAssociatedShops());
		$allshops = implode(',', Shop::getCompleteListOfShopsID());

		$modes = $this->getPaymentModes();
		
		//permission data by profil
		$data = $this->getKerawenPermissionsInitial();
		
		
		//force full permissions
		//$data = $this->getKerawenFullPermissionsData();
		
		$defaultData = $this->getKerawenDefaultPermissions();
		$defaultAction = $this->getKerawenDefaultAction();
		
		
		if ( isset($data[$id_profile]) && $id_profile > 0) {
			foreach($defaultData as $k => $itemdata) {
				
				if ( empty($data[$id_profile][$k]) ) {
					$data[$id_profile][$k] = $itemdata;
				} else {
					$itemdata = $data[$id_profile][$k];
				}
				
				//check valid modes
				if (substr( $k, 0, 5 ) === "mode_") {
					$array_itemdata = array();
					$splited_k = explode( '_', $k);
					
					$i = (int) $splited_k[1];
					if (isset($modes[$i])) {
						if ($modes[$i]['payment'] == 1 && strpos($itemdata, 'payment') !== false) {
							$array_itemdata[] = 'payment';
						}
						if ($modes[$i]['refund'] == 1 && strpos($itemdata, 'refund') !== false) {
							$array_itemdata[] = 'refund';
						}
					}
					$data[$id_profile][$k] = implode ( "," , $array_itemdata );
				}

			}
		} else {
			$data[(int)$id_profile] = $defaultData;
		}		
				
		$dataProfile = $data[(int)$id_profile];
		
		
		foreach($dataProfile as $k=>$v) {
			
			if ( !empty($defaultAction[$k]) ) {
				
				switch($defaultAction[$k]) {
					
					case 'filter_shop':
						
						$filter['id_shop'] = null;
						$filter['id_employee'] = null;
						
						switch ($v) {
							case 'my_sales':
								$filter['id_employee'] = $id_employee;
								break;
							case 'my_shop':
								$filter['id_shop'] = $shop;
								break;
							case 'my_shops':
								$filter['id_shop'] = $shops;
								break;
							case 'all_shops':
								$filter['id_shop'] = $allshops;
								break;
						}
							
						$dataProfile[$k] = (object) $filter;
						break;

					case 'boolean':
						$dataProfile[$k] = ($v === 'true') ? true : false;
						break;
						
					default:
						$dataProfile[$k] = $v;
					break;
						
				}
				
			}

		}
				
		return (object) $dataProfile;

	}


	function getKerawenDefaultPermissions() {
		return $this->getKerawenFieldPermissions('default');
	}

	function getKerawenFullPermissions() {
		return $this->getKerawenFieldPermissions('full');
	}	
	

	function getKerawenDefaultAction() {
		return $this->getKerawenFieldPermissions('action');
	}	
	
	
	function getKerawenFieldPermissions($field) {
		$def = array();
		foreach($this->getKerawenPermissionsModele() as $section) {
			foreach($section as $k=>$v) {
				$def[$k] = $v[$field];
			}
		}
		return $def;
	}
	
}