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

class KerawenEmployeesController extends KerawenAdminController
{
    /** @var array profiles list */
    protected $profiles_array = array();

    /** @var array themes list*/
    protected $themes = array();

    /** @var array tabs list*/
    protected $tabs_list = array();

    protected $restrict_edition = false;

    protected $basename;
    protected $modulename;
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'employee';
        $this->className = 'Employee';
        $this->context = Context::getContext();
        
        $this->basename = basename(__FILE__, '.php');
        $this->modulename = 'kerawen';
        
        $this->addRowAction('edit');


        /*
        check if there are more than one superAdmin
        if it's the case then we can delete a superAdmin
        */
        /*
        $super_admin = Employee::countProfile(_PS_ADMIN_PROFILE_, true);
        if ($super_admin == 1) {
            $super_admin_array = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_, true);
            $super_admin_id = array();
            foreach ($super_admin_array as $key => $val) {
                $super_admin_id[] = $val['id_employee'];
            }
            $this->addRowActionSkipList('delete', $super_admin_id);
        }
	*/
        $profiles = Profile::getProfiles($this->context->language->id);
        if (!$profiles) {
            $this->errors[] = Tools::displayError('No profile.');
        } else {
            foreach ($profiles as $profile) {
                $this->profiles_array[$profile['name']] = $profile['name'];
            }
        }

        $this->fields_list = array(
            'id_employee' => array('title' => Translate::getModuleTranslation($this->modulename, 'ID', $this->basename), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'firstname' => array('title' => Translate::getModuleTranslation($this->modulename, 'First Name', $this->basename)),
            'lastname' => array('title' => Translate::getModuleTranslation($this->modulename, 'Last Name', $this->basename)),
            'email' => array('title' => Translate::getModuleTranslation($this->modulename, 'Email address', $this->basename)),
            'profile' => array('title' => Translate::getModuleTranslation($this->modulename, 'Profile', $this->basename), 'type' => 'select', 'list' => $this->profiles_array, 'filter_key' => 'pl!name', 'class' => 'fixed-width-lg'),
            //'active' => array('title' => $this->l('Active'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'class' => 'fixed-width-sm'),
        );

        
        /*
         Translate::getAdminTranslation('First Name', $class = 'kerawen')
         Translate::getModuleTranslation('First Name', $string, basename(__FILE__, '.php'));
         */        
        
        $rtl = $this->context->language->is_rtl ? '_rtl' : '';
        $path = _PS_ADMIN_DIR_.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR;
        foreach (scandir($path) as $theme) {
            if ($theme[0] != '.' && is_dir($path.$theme) && (@filemtime($path.$theme.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'admin-theme.css'))) {
                $this->themes[] = array('id' => $theme.'|admin-theme'.$rtl.'.css', 'name' => $theme == 'default' ? $this->l('Default') : ucfirst($theme));
                if (file_exists($path.$theme.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'schemes'.$rtl)) {
                    foreach (scandir($path.$theme.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'schemes'.$rtl) as $css) {
                        if ($css[0] != '.' && preg_match('/\.css$/', $css)) {
                            $name = strpos($css, 'admin-theme-') !== false ? Tools::ucfirst(preg_replace('/^admin-theme-(.*)\.css$/', '$1', $css)) : $css;
                            $this->themes[] = array('id' => $theme.'|schemes'.$rtl.'/'.$css, 'name' => $name);
                        }
                    }
                }
            }
        }

        $home_tab = Tab::getInstanceFromClassName('AdminDashboard', $this->context->language->id);
        $this->tabs_list[$home_tab->id] = array(
            'name' => $home_tab->name,
            'id_tab' => $home_tab->id,
            'children' => array(array(
                'id_tab' => $home_tab->id,
                'name' => $home_tab->name
            ))
        );
        foreach (Tab::getTabs($this->context->language->id, 0) as $tab) {
            if (Tab::checkTabRights($tab['id_tab'])) {
                $this->tabs_list[$tab['id_tab']] = $tab;
                foreach (Tab::getTabs($this->context->language->id, $tab['id_tab']) as $children) {
                    if (Tab::checkTabRights($children['id_tab'])) {
                        $this->tabs_list[$tab['id_tab']]['children'][] = $children;
                    }
                }
            }
        }
        parent::__construct();
        $this->title = $this->l('Employees');
        $this->toolbar_title = $this->l('KerAwen employees configuration');
        
        // An employee can edit its own profile
        /*
        if ($this->context->employee->id == Tools::getValue('id_employee')) {
            $this->tabAccess['view'] = '1';
            $this->restrict_edition = true;
            $this->tabAccess['edit'] = '1';
        }
        */
        
        
    }

    /*
    //issue PHP 7.2 and PS 1.7
    public function setMedia()
    {
        parent::setMedia();        
        $this->addJS(_KERAWEN_DIR_ . 'js/jquery.filter_input.js');
        
    }
	*/

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        /*
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_employee'] = array(
                'href' => self::$currentIndex.'&addemployee&token='.$this->token,
                'desc' => $this->l('Add new employee', null, null, false),
                'icon' => 'process-icon-new'
            );
        }
		*/
        if ($this->display == 'edit') {
            $obj = $this->loadObject(true);
            if (Validate::isLoadedObject($obj)) {
                /** @var Employee $obj */
                array_pop($this->toolbar_title);
                $this->toolbar_title[] = sprintf($this->l('Edit: %1$s %2$s'), $obj->lastname, $obj->firstname);
                $this->page_header_toolbar_title = implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ',
                    $this->toolbar_title);
            }
        }
    }

    public function renderList()
    {
        $this->_select = 'pl.`name` AS profile';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'profile` p ON a.`id_profile` = p.`id_profile`
		LEFT JOIN `'._DB_PREFIX_.'profile_lang` pl ON (pl.`id_profile` = p.`id_profile` AND pl.`id_lang` = '
            .(int)$this->context->language->id.')';
        $this->_use_found_rows = false;

        return parent::renderList();
    }

    
    public function renderForm()
    {
    	  
    	
    	$employee = new Employee( (int) Tools::getValue('id_employee') );
    	$id_profile = (int) $employee->id_profile;    	
    	
    	$password = '';
    	
    	
    	//shops
    	$ss = Db::getInstance()->executeS('		
			SELECT s.name, s.id_shop,  IF(ISNULL(e.id_employee), 0, 1) AS active
			FROM '._DB_PREFIX_.'shop s
			LEFT JOIN '._DB_PREFIX_.'employee_shop e ON s.id_shop = e.id_shop AND e.id_employee = ' . (int) Tools::getValue('id_employee') .  '
			ORDER BY s.id_shop
    	');
    	$list_shop = array();
    	if ($ss) {
    		foreach ($ss as $s) {
    			$list_shop[] = $this->renderSwitch('shop_' . $s['id_shop'], $s['name'], $id_profile);
    		}
    	}
    		
    	
    	//drawers
    	$ds = Db::getInstance()->executeS('
    		SELECT d.name, d.id_cash_drawer, IF(ISNULL(c.id_employee), 0, 1) AS active
    		FROM '._DB_PREFIX_.'cash_drawer_kerawen d
    		LEFT JOIN '._DB_PREFIX_.'employee_cash_drawer_kerawen c ON d.id_cash_drawer = c.id_cash_drawer AND c.id_employee = ' . (int) Tools::getValue('id_employee') .  '
    		ORDER BY d.id_cash_drawer
    	');
    	$list_drawer = array();
    	foreach ($ds as $d) {
    		$list_drawer[] = $this->renderSwitch('drawer_' . $d['id_cash_drawer'], $d['name'], $id_profile);
    	}
        	
    	
    	$profile_label = '';
    	$profiles = Profile::getProfiles($this->context->language->id);
    	if ($profiles) {    		
    		foreach ($profiles as $profile) {
    			if ($profile['id_profile'] == $id_profile) {
    				$profile_label = $profile['name'];
    			}
    		}
    	}
    	

    	$forms = array(
    			
    		'employee' => array(
    			'form' => array(
    				'legend' => array(
    					'title' => $this->l('Employee'),
    					'icon' => 'icon-wrench',
    				),
    				'input' => array(
    						array(
    							'name' => 'firstname',
    							'label' => $this->l('First Name'),
    							'type' => 'free',
    							'content' => $employee->firstname,
    						),
    						array(
    							'name' => 'lastname',
    							'label' => $this->l('Last Name'),
    							'type' => 'free',
    							'content' => $employee->lastname,
    						),
    						array(
    								'name' => 'email',
    								'label' => $this->l('Email address'),
    								'type' => 'free',
    								'content' => $employee->email,
    						),
    						array(
    							'name' => 'profile',
    							'label' => $this->l('Profile'),
    							'type' => 'free',
    							'content' => $profile_label,
    						),
    				),
    			),
    		),
    			
    		'shops' => array(
    			'form' => array(
    				'legend' => array(
    					'title' => $this->l('Shops'),
    					'icon' => 'icon-wrench',
    				),
    				'input' => $list_shop,
    				'submit' => ($id_profile > 1) ? $this->renderSubmit('submitConfig', $this->l('Save')) : false,
    				),
    		),
    	
    		'drawers' => array(
    			'form' => array(
    				'legend' => array(
    					'title' => $this->l('Cashdrawers'),
    					'icon' => 'icon-wrench',
    				),
    				'input' => $list_drawer,
    				'submit' => ($id_profile > 1) ? $this->renderSubmit('submitConfig', $this->l('Save')) : false,
    			),
    		),
    			
    		'password' => array(
    			'form' => array(
    				'legend' => array(
    					'title' => $this->l('Password'),
    					'icon' => 'icon-wrench',
    				),
    				'input' => array(
    					array(
    						'name' => 'password',
    						'label' => $this->l('Password'),
    						'type' => 'password',
    						'desc' => $this->l('Only numbers'),
    						'class' => 'numOnly',
    					),
    				),
    				'submit' => $this->renderSubmit('submitConfig', $this->l('Save')),
    			),
    		),    			
    	);
    	
    	
		$values = array();
		foreach ($forms as $k=>$set) {

			foreach ($set['form']['input'] as $param) {
				
				switch($k) {
				
					case 'shops':
						
						$active = 0;
						foreach ($ss as $s) {
							if ($param['name'] == 'shop_' . $s['id_shop'] && ($s['active'] == 1 || $id_profile === 1)) {
								$active = 1;
								break;
							}
						}
						$values[$param['name']] = $active;
						break;

					case 'drawers':
						
						$active = 0;
						foreach ($ds as $s) {
							if ($param['name'] == 'drawer_' . $s['id_cash_drawer'] && ($s['active'] == 1 || $id_profile === 1)) {
								$active = 1;
								break;
							}
						}
						
						$values[$param['name']] = $active;
						break;

					
					case 'employee':
						$values[$param['name']] = '<label class="control-label">' . $param['content'] . '</label>';					
						break;
						
					case 'password':
						$values[$param['name']] = $password;
						break;

				}

			}

		}
		

    	$this->setHelperDisplay(new HelperForm());
    	$this->helper->show_toolbar = false;
    	
    	$this->helper->tpl_vars = array(
    			'fields_value' => $values,
    			'id_language' => $this->context->language->id
    	);
    	    
    	return $this->helper->generateForm($forms);

    }    
    
    
  
    protected function canModifyEmployee()
    {	
        return true;
    }

    public function processSave()
    {
        
            	
    	//superadmin force full_access
    	
    	//if superadmin force values
    	
    	$db = Db::getInstance();
    	
    	$id_employee = (int) $_POST['id_employee'];
    	
    	$employee = new Employee($id_employee);
    	$id_profile = (int) $employee->id_profile;
    	
    	
    	$db_action = array();
    	
    	$has_shop = false;
    	$has_drawer = false;
    	

    	foreach ($_POST as $k => $v) {
    		    		
    		if (strpos($k, 'shop') === 0) {
    			list($prefix, $id_shop) = explode('_', $k);
    			if ($id_shop) {    				
    				if ($v == 0) {
    					$db_action[] = 'DELETE FROM '._DB_PREFIX_.'employee_shop WHERE id_employee = '. pSQL($id_employee) . ' AND id_shop = ' . pSQL($id_shop);
    				} else {   					
    					$db_action[] = 'INSERT IGNORE INTO '._DB_PREFIX_.'employee_shop (id_employee, id_shop) VALUES (' . pSQL($id_employee) . ',' . pSQL($id_shop) . ')';
    					$has_shop = true;
    				}			
    			}
    		}
    		
    		
    		if (strpos($k, 'drawer') === 0) {
    			list($prefix, $id_cash_drawer) = explode('_', $k);
    			if ($id_cash_drawer) {
    				if ($v == 0) {
    					$db_action[] = 'DELETE FROM '._DB_PREFIX_.'employee_cash_drawer_kerawen WHERE id_employee = '. pSQL($id_employee) . ' AND id_cash_drawer = ' . pSQL($id_cash_drawer);
    				} else {
    					$db_action[] = 'INSERT IGNORE INTO '._DB_PREFIX_.'employee_cash_drawer_kerawen (id_employee, id_cash_drawer) VALUES (' . pSQL($id_employee) . ',' . pSQL($id_cash_drawer) . ')';
    					$has_drawer = true;
    				}
    			}    			
    		}
    		    		
    	}
    	
    	
    	if ($_POST['password'] != '') {
    		$pass = Tools::encrypt(trim($_POST['password']));
    		$db->execute('INSERT INTO '._DB_PREFIX_.'employee_kerawen (id_employee, password) VALUES (' . pSQL($id_employee) . ',"' . pSQL($pass) . '")  ON DUPLICATE KEY UPDATE password = "' . pSQL($pass) . '"');
    	}

 
    	if ($has_shop && $has_drawer) {
    		foreach($db_action as $action) {
    			$db->execute($action);
    		}
    	}	
    		
    	
    	if ($id_profile > 1) {
	    	if (!$has_shop) {
	    		$this->errors[] = $this->l('Shop is required');
	    	}
	    	if (!$has_drawer) {
	    		$this->errors[] = $this->l('Till is required');
	    	}    		
    	}
    	
		if ( count($this->errors) == 0) {
    		$this->confirmations[] =  $this->l('Update success');
		}
    	
        return true;
    }

    public function validateRules($class_name = false)
    {
    	return false;
    }


   public function initToolbar()
   {

   }   
    

    public function initContent()
    {
    	
        if ($this->context->employee->id == Tools::getValue('id_employee')) {
            $this->display = 'edit';
        }
        
        return parent::initContent();
    }

    
    protected function renderSwitch($name, $label, $id_profile)
    {
    	if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>='))
    	{
    		$array = array(
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
    		$array = array(
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
    	
    	if ($id_profile === 1) {
    		$array['disabled'] = 'disabled';
    	}
    	
    	return $array;
    	
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
        
    
    
}

