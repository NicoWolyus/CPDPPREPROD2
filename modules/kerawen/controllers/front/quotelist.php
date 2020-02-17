 <?php
class kerawenquotelistModuleFrontController extends ModuleFrontController {

    public function init() {
        $this->display_column_left = false;
		 $this->display_column_right = false;
        parent::init();
    }

    public function initContent() {
        parent::initContent();
                   
		if (Configuration::get('KERAWEN_QUOTE_ACTIVE') && $this->context->customer->isLogged()) {

			$q = '
				SELECT cart.id_cart, cart_kerawen.count, cart_kerawen.total, quote_expiry, cart_kerawen.quote_number,
				IF(cart_kerawen.quote_title = "" OR cart_kerawen.quote_title IS NULL, LPAD(cart_kerawen.quote_number, 10, "#Q000000000"), cart_kerawen.quote_title) AS quote_title,
				IF(date(cart_kerawen.quote_expiry) < date(NOW()), 0, 1) AS quote_active
				FROM `' . _DB_PREFIX_ . 'cart` cart 
				INNER JOIN `' . _DB_PREFIX_ . 'cart_kerawen` cart_kerawen ON cart.id_cart = cart_kerawen.id_cart
				WHERE cart_kerawen.quote = 1 AND cart.id_customer = ' . (int) $this->context->customer->id . '
				ORDER BY cart.id_cart DESC';
			
			
			$quotes = Db::getInstance()->executeS($q);
	
			$this->context->smarty->assign('quotes', $quotes);
			$this->context->smarty->assign('id_customer', (int) $this->context->customer->id);		
			$this->context->smarty->assign('id_currency', (int) $this->context->currency->id);
			//$this->context->smarty->assign('q', $q);
			
			$tpl = 'kerawen_quote_list.tpl';
			if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
				$tpl = 'module:kerawen/views/templates/front/kerawen_quote_list_17.tpl';
			}
			
			parent::initContent();
			$this->setTemplate($tpl);
		
		} else {
			
			Tools::redirect('index.php?controller=my-account');
			
		}

    }

    public function getBreadcrumbLinks()
    {
    	$breadcrumb = parent::getBreadcrumbLinks();
    	
    	$breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
    	
    	return $breadcrumb;
    }
    
    
}
?>