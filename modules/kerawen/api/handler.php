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

class Handler
{
	public function getDictionary($request)
	{
		$file = _KERAWEN_DIR_.'translations/'
			.$request->params->appli.'.'.$request->params->lang;
		$request->addResult('dict', Tools::jsonDecode(Tools::file_get_contents($file)));
	}

	public function accountancy($request)
	{
		$request->addResult('config', getAccountancyConfig());
		$request->addResult('salesRecords', getDetailsRecords($request->params->from, $request->params->to));
		$request->addResult('returnsRecords', getSlipsRecords($request->params->from, $request->params->to));
		$request->addResult('allRecords', getRecords($request->params->from, $request->params->to));
		getRecords($request->params->from, $request->params->to);
	}

	public function getCatalogTree($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/accounting.php');
		$request->addResult('tree', getCatalogTree());
	}

	public function setAccountForProduct($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/accounting.php');
		setAccountForCategoryOrAccount(0, $request->params->id, $request->params->account);
	}

	public function setAccountForCategory($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/accounting.php');
		setAccountForCategoryOrAccount(1, $request->params->id, $request->params->account);
	}

	public function getConfig($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$request->addResult('config', getConfig($request->context, $request->params));
	}

	public function returnProduct($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		require_once (_KERAWEN_CLASS_DIR_.'/cart.php');
		$params = $request->params;

		try {
			// Compute tax according to product configuration
			// TODO adapt vat_margin module with specific tax calculator
			$context = Context::getContext();
			$order = new Order($params->id_order);
			$address = new Address($order->id_address_delivery);
			$od = new OrderDetail($params->id_order_detail);
			$tax_manager = TaxManagerFactory::getManager($address, Product::getIdTaxRulesGroupByIdProduct($od->product_id, $context));
			$tax_calculator = $tax_manager->getTaxCalculator();
			$refund_tax_excl = $tax_calculator->removeTaxes($params->refund_tax_incl);
			
			$id_cust = addReturnedProduct(
				$params->id_cart,
				$params->id_order,
				$params->id_order_detail,
				$params->quantity,
				$params->refund_tax_incl,
				$params->price_tax_incl,
				$params->back_to_stock,
				$refund_tax_excl
			);
			
			$request->addResult('order', getOrder($request->context, $params));
			if ($id_cust > 0) {
				// Customer has changed
				$id_lang = $request->context->language->id;
				$request->addMessage('info', 'Customer has been selected');
				$request->params->id_cust = $id_cust;
				$this->selectCustomer($request);
			}
			else {
				$request->addResult('cart', cartAsArray(getCart($params->id_cart, $params->id_cust, $request->context)));
				$this->preScreen($request);
				
				if ($id_cust < 0) {
					// Conflict
					$request->addMessage('warning', 'Cart and order customers are different');
				}
			}
		}
		catch (Exception $e)
		{
			$request->addResult('problem', array(
				'level' => 'error',
				'message' => $e->getMessage(),
			));
		}
	}

	public function cancelReturn($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		require_once (_KERAWEN_CLASS_DIR_.'/cart.php');
		require_once (_KERAWEN_CLASS_DIR_.'/push.php');
		$params = $request->params;
		eraseReturn($params->id_return);
		if ($params->id_order !== null)
			$request->addResult('order', getOrder($request->context, $params));

		$request->addResult('cart', cartAsArray(getCart($params->id_cart, $params->id_cust, $request->context)));
		$this->preScreen($request);
		
	}
	public function maxReturns($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/order.php');
		return getMaxReturns($request->params->id_order_detail);
	}

	public function selectEmployee($request)
	{
		$id_empl = $request->params->id_empl;
		
		// Update cookie & context
		$cookie = Context::getContext()->cookie;
		$cookie->id_employee_kerawen = $id_empl;
		$cookie->write();
		$request->context->employee = new Employee($id_empl);
				
		require_once(_KERAWEN_CLASS_DIR_.'/stats.php');
		$request->addResult('employee', getEmployee($id_empl));
		
		$id_cart = $request->params->id_cart;
		if ($id_cart) {
			require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
			setCartEmployee($id_cart, $id_empl);
			$request->addResult('cart', cartAsArray(new Cart($id_cart)));
		}
	}

	public function getCategories($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		$request->addResult('cats', getCategories($request->context));
	}

	public function getProducts($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		$request->addResult('prods', getProducts(
			new Category($request->params->id_cat),
			$request->params->id_shop,
			$request->context->language->id
		));
	}

	public function detailProduct($request,  $reflex = 'prod', $with_attributes = true)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		$request->addResult($reflex, detailProduct(
			new Product($request->params->id_prod, true, $request->context->language->id),
			$request->context->language->id,
			$with_attributes,
			$request->context->customer->id,
			$request->context->group->id
		));
	}

	public function actionProduct($request)
	{		
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		$request->addResult('actionProd', detailProduct(
			new Product($request->params->id_prod, true, $request->context->language->id),
			$request->context->language->id,
			true,
			$request->context->customer->id,
			$request->context->group->id,
			$request->params->backend_url,
			$request->params->frontend_url
		));
	}

	public function actionStock($request) 
	{
		require_once(_KERAWEN_CLASS_DIR_.'/stock.php');
		applyStock($request->params->stock);
		$this->detailProduct($request, 'detailProd', false);
	}

	public function getOrders($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		$request->addResult('orders', getOrders(
			$request->params->filter,
			$request->params->page->size,
			$request->params->page->num,
			$request->params->counter
		));

		//resetNotifOrders($request->context);
		$this->getNotif($request);
	}

	public function getNotif($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/notif.php');
		$request->addResult('notif', getNotif(
			$request->context
		));
	}

	/*
	 * Operations on cart
	 */
	public function getSuspendedCarts($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		$request->addResult('carts', getSuspendedCarts());
	}

	protected function checkCart(&$request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		if ($warning = checkCart(
				$request->params->id_cart,
				$request->params->version,
				$request->context->module))
		{
			$request->addMessage('warning', $warning);

			// Create a new cart
			$id_lang = $request->context->language->id;
			$id_curr = $request->context->currency->id;
			$id_empl = $request->context->employee->id;
			$id_shop = $request->params->id_shop;
			$id_cust = isset($request->params->id_cust) ? $request->params->id_cust : null;

			$id_new = createCart($id_shop, $id_empl, $id_cust, $id_lang, $id_curr);
			$request->params->id_cart = $id_new;
			return false;
		}
		return true;
	}

	public function adjustItem($request)
	{
		$this->checkCart($request);
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		$adj = adjustItem($request->context, $request->params, $request);		
		$this->preScreen($request);
		return $adj;
	}

	public function addRule($request)
	{
		$this->checkCart($request);
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		$add = addRule($request->context, $request->params, $request);
		$this->preScreen($request);
		return $add;
	}

	public function remRule($request)
	{
		$this->checkCart($request);
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		$rem = remRule($request->context, $request->params, $request);
		$this->preScreen($request);
		return $rem;
		
	}

	public function getCarriers($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$request->addResult('carriers', getCarriers(
			$request->context->language->id));
	}

	public function setDelivery($request)
	{
		$this->checkCart($request);
		$cart = new Cart($request->params->id_cart);
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		setDelivery($cart,
			$request->params->mode,
			isset($request->params->id_address) ? $request->params->id_address : null,
			isset($request->params->carrier) ? $request->params->carrier : null,
			isset($request->params->date) ? $request->params->date : null);
		$request->addResult('cart', cartAsArray($cart));
		$this->preScreen($request);
	}

	/* TO REFACTOR AND SORT */
	public function startRegister($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/stats.php');
		$request->addResult('employee', getEmployee($request->context->employee->id));

		require_once(_KERAWEN_CLASS_DIR_.'/quote.php');
		$request->addResult('quotes', getQuotes());
		
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$start = startRegister($request->context, $request->params, $request);
		$this->preScreen($request);
		return $start;
		
	}

	public function stopRegister($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		return stopRegister($request->context);
	}

	public function searchCode($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		return searchCode(
			$request->params->code,
			$request->context->language->id);
	}
	
	public function browseCategory($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		return browseCategory($request->context, $request->params, $request);
	}

	public function searchProduct($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		return searchProduct($request->context, $request->params, $request);
	}

	public function createProduct($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		$prod = createProduct($request->params->product);
		$request->addResult('prod', detailProduct($prod, $request->context->language->id, true));
	}

	public function getCode($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/catalog.php');
		$request->addResult('newCode', createBareCode());
	}
	
	
	public function searchCustomer($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		return searchCustomer($request->params);
	}

	public function selectCustomer($request)
	{
		$this->checkCart($request);
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');		
		$cust = selectCustomer(
				$request->context, 
				$request->params, 
				$request
		);
		$this->preScreen($request);
		return $cust;
		
	}

	public function selectCustomerDetail($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		return selectCustomer($request->context, $request->params, $request, 'all');
	}	
	
	
	public function updateCustomer($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		return updateCustomer($request->context, $request->params, $request);
	}

	public function getCustomerLoyaltyNum($request)
	{
	    require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
	    $request->addResult('newCode', getRandomLoyaltyNumber());
	}

	public function updateAddress($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		return updateAddress($request->context, $request->params, $request);
	}

	public function deleteAddress($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		return deleteAddress($request->context, $request->params, $request);
	}

	public function addLoyalty($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/loyalty.php');
		if ($loyalty = KerawenLoyalty::getPlugin()) {
			$loyalty->add(
				$request->params->id_cust,
				$request->params->value);

			require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
			$request->addResult('cust', getCustomer(
				$request->params->id_cust,
				$request->context->language->id));
		}
	}

	public function transformLoyalty($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/loyalty.php');
		if ($loyalty = KerawenLoyalty::getPlugin()) {
			$loyalty->transform($request->params->id_cust);

			require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
			$request->addResult('cust', getCustomer(
				$request->params->id_cust,
				$request->context->language->id));
			require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
			$request->addResult('rules', getCartRules(
				$request->params->id_cust,
				$request->context->language->id));
		}
	}

	public function transformFidelisa($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/fidelisa.php');
		if ($fidelisa = KerawenFidelisa::getPlugin()) {
			
			//TODO
			//$fidelisa->transform($request->params->id_cust);
	
			require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
			$request->addResult('cust', getCustomer(
					$request->params->id_cust,
					$request->context->language->id));
			require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
			$request->addResult('rules', getCartRules(
					$request->params->id_cust,
					$request->context->language->id));
		}
	}	
	
	public function getCartRules($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
		$request->addResult('rules', getCartRules(
				$request->params->id_cust,
				$request->context->language->id));
	}

	public function createCredit($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');

		$credit = createCredit(
			$request->params->value,
			$request->params->id_cust,
		    $request->params->label);
		
		// Log as withdraw
		require_once(_KERAWEN_CLASS_DIR_.'drawer.php');
		logCashFlow(
			$request->params->id_cashdrawer,
			$request->context->employee->id,
			$request->params->id_shop,
			_KERAWEN_PM_CREDIT_,
			-$request->params->value,
			$credit['id'],
			null);

		$request->addResult('credit', $credit);
		$this->getCartRules($request);
	}

	public function applySpecialOffer($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
		return applySpecialOffer($request->context, $request->params, $request);
	}


	public function applyDiscount($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
		$disc = applyDiscount($request->context, $request->params, $request);
		$this->preScreen($request);
		return $disc;
	}

	public function resetCart($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		resetCart($request->context, $request->params, $request);
		if ($request->params->id_order !== null)
		{
			require_once (_KERAWEN_CLASS_DIR_.'/order.php');
			$request->addResult('order', getOrder($request->context, $request->params));
		}
		$this->preScreen($request);
	}

	public function selectShop($request)
	{
		$this->checkCart($request);
		//redefine permission profil -> current shop
		require_once(_KERAWEN_CLASS_DIR_.'/stats.php');
		$request->addResult('employee', getEmployee($request->params->id_empl));
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		return selectShop($request->context, $request->params, $request);
	}

	public function setItemPrice($request)
	{		
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		setItemPrice(
			$request->params->id_cart,
			$request->params->id_prod,
			$request->params->id_attr,
			$request->params->type,
			$request->params->discount,
			$request->params->calc
			);
		$request->addResult('cart', cartAsArray(new Cart($request->params->id_cart)));
		$this->preScreen($request);
		
	}

	public function setItemCartPrice($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		setItemCartPrice(
				$request->params->id_cart,
				$request->params->id_prod,
				$request->params->id_attr,
				$request->params->value
				);
		$request->addResult('cart', cartAsArray(new Cart($request->params->id_cart)));
		$this->preScreen($request);
	}	
	
	
	public function annotateCartItem($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		annotateCartItem(
			$request->params->id_cart,
			$request->params->id_prod,
			$request->params->id_attr,
			$request->params->note);
		$request->addResult('cart', cartAsArray(new Cart($request->params->id_cart)));
	}

	public function confirmCart($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		$shop = selectShops($request->params->id_shop);
		$request->addResult('validShop', isset($shop[$request->params->id_shop]) ? $shop[$request->params->id_shop]['valid'] : false);
		if ($this->checkCart($request)) $request->addResult('confirmed', true);
		$request->addResult('cart', cartAsArray(new Cart($request->params->id_cart)));
	}

	public function validateQuote($request)
	{
	
		require_once(_KERAWEN_CLASS_DIR_.'/quote.php');
		validateQuote($request);
		$request->addResult('quotes', getQuotes());
		
		require_once (_KERAWEN_CLASS_DIR_.'/cart.php');
		$request->addResult('quote', cartAsArray(getCart($request->params->id_cart, $request->params->id_cust, $request->context)));
		$this->preScreen($request);
	}

	public function delQuotation($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/quote.php');
		require_once(_KERAWEN_CLASS_DIR_.'/customer.php');
		require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
		deleteQuote($request);
		$request->addResult('cust', getCustomer($request->params->id_cust, $request->context->language->id, 'all'));
		$request->addResult('quotes', getQuotes());
		if ($request->params->id_cart == $request->params->id_next) {
		    $request->params->id_next = false;
		    resetCart($request->context, $request->params, $request);
		}
	}

	public function sendQuote($request) {

		require_once (_KERAWEN_CLASS_DIR_.'/quote.php');		
		$quote = getQuoteInfo($request->params->id_cart, 0, true);
		getQuotePdf($quote, 'send');
	}

	public function validateCart($request)
	{
		if ($this->checkCart($request))
		{
			require_once(_KERAWEN_CLASS_DIR_.'/order.php');
			validateCart($request->context, $request->params, $request);
			
			require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
			getReceiptCartRules($request->context, $request->params, $request);

			require_once(_KERAWEN_CLASS_DIR_.'/notif.php');
			$this->getNotif($request);
		}
		else {
			require_once(_KERAWEN_CLASS_DIR_.'/cart.php');
			$request->addResult('cart', cartAsArray(new Cart($request->params->id_cart)));
		}
		$this->preScreen($request);
	}

	public function selectOrder($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		$request->addResult('order', getOrder($request->context, $request->params));
	}

	public function changeOrderMode($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		
		changeOrderMode(
			$request->params->id_order, 
			$request->params->id_order_payment, 
			$request->params->id_mode, 
			$request->params->mode,
			$request->params->date_deferred
		);
		
		$this->selectOrder($request);
	}	


	public function changeOrderCustomer($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');

		changeOrderCustomer(
			$request->params->id_order,
			$request->params->id_customer
		);
		
		$this->selectOrder($request);
	}	

	
	public function changeOrderState($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		
		$order = new Order($request->params->id_order);
		$id_os = isset($request->params->id_state) ? (int)$request->params->id_state : null;
		$payments = isset($request->params->payment) ? $request->params->payment : false;
		$credits = array();
		
		changeOrderState($order, $id_os, $payments, $credits);
		$request->addResult('credits', $credits);
		
		$request->addResult('order', getOrder(Context::getContext(true), $request->params));
		$this->getNotif($request);
	}

	public function setInvoice($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		setInvoiceAddress(
			new Order($request->params->id_order),
			$request->params->address,
			isset($request->params->id_lang) ? $request->params->id_lang : false
			);

		setInvoiceNote(
			$request->params->id_order, 
			$request->params->invoice_note
		);
		
		$request->addResult('order', getOrder(Context::getContext(true), $request->params));
	}

	public function adjustOrderDetail($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		$order = new Order($request->params->id_order);
		if (isset($request->params->qty)) {
			adjustOrderQty($order, (int)$request->params->id_detail, (int)$request->params->qty);
		}	
		$request->addResult('order', getOrder(Context::getContext(true), $request->params));
	}
	
	
	
	public function adjustOrderNote($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		
		if (isset($request->params->note)) {
			adjustOrderNote($request->params);
		}
	
		$request->addResult('order', getOrder(Context::getContext(true), $request->params));
	}	
	
	
	public function orderNote($request) {
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		addOrderNote($request->params->id_order, $request->params->note, $request->params->id_empl, $request->params->id_customer);
	}

	public function getExistingOrderStatus($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		return getExistingOrderStatus($request->context);
	}

	public function getCurrentOrderStatus($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		return getCurrentOrderStatus($request->params);
	}

	public function getTextualStatus($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/order.php');
		return getTextualStatus(
			$request->context->language->id,
			$request->params,
			$request);
	}

	/*
	 * Cash drawer operations
	 */

	public function getTillContent($request)
	{
		$content = false;
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$content = $proc->getTillContent($request->params->id_till);
		}
		if (!$content) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$content = getTillContent($request->params->id_till);
		}
		$request->addResult('content', $content);
	}
	
	public function updateTill($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		$id_till = updateTill(
			$request->params->id_cashdrawer,
			$request->params->data); 
		$request->addResult('till', getTills($id_till));
	}

	public function saveTill($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		require_once(_KERAWEN_CLASS_DIR_.'/push.php');
		
		$id_till = updateTill(
				$request->params->id_cashdrawer,
				$request->params->data);
		
		//new till -> create screen token
		if ($request->params->id_cashdrawer == 0 && $id_till) {
			$token = setTillToken($request->params->shop_id, $id_till, Configuration::get('KERAWEN_LICENCE_KEY'));	
		}

		$shop_id = isset($request->params->shop_id) ? $request->params->shop_id : false;
		$request->addResult('tills', getTills(false, $shop_id));
	}	

	public function setTillToken($request) 
	{		
		require_once(_KERAWEN_CLASS_DIR_.'/push.php');
		return setTillToken($request->params->shop_id, $request->params->id_cash_drawer, Configuration::get('KERAWEN_LICENCE_KEY'));	

	}	

	public function delScreenPictures($request)
	{		
		require_once(_KERAWEN_CLASS_DIR_.'/push.php');
		return delScreenDecoration($request->params->shop_id, $request->params->id_cash_drawer, $request->params->type, Configuration::get('KERAWEN_LICENCE_KEY'));
	}	
	
	public function setTillHardware($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		setTillHardware($request->params->mac);
		return $request->addResult('tills', indexArray(getTills(false), 'id'));
	}	

	public function getTills($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		$shop_id = isset($request->params->shop_id) ? $request->params->shop_id : false;		
		$tills = getTills(false, $shop_id);
		if (isset($request->params->indexed)) {
			if ($request->params->indexed) {
				$tills = indexArray($tills, 'id');
			}
		}
		return $request->addResult('tills', $tills);
	}
	
	public function openCashdrawer($request)
	{
		$id_open = false;
		$legacy = false;
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$id_open = $proc->openTill(
				$request->params->id_cashdrawer,
				$request->params->data);
		}
		if (!$id_open) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$id_open = openCashdrawer(
				$request->params->id_cashdrawer,
				$request->context->employee->id,
				$request->params->id_shop,
				$request->params->data);
			$legacy = true;
		}
		
		$request->params->id_op = $id_open;
		$request->params->legacy = $legacy;
		$this->getOpeningData($request);
		
		$request->params->data->active = true;
		$this->updateTill($request);
	}
	
	public function getOpeningData($request)
	{
		$id_op = isset($request->params->id_op) ? $request->params->id_op: null;
		
		$legacy = isset($request->params->legacy) && $request->params->legacy;
		$legacy_id = substr($id_op, 0, 1) == '_';
		if ($legacy_id) $id_op = substr($id_op, 1, strlen($id_op));
		
		$data = null;
		if (defined('_KERAWEN_525_CLASS_') && !$legacy && !$legacy_id) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$data = $proc->getOpeningData($id_op);
		}
		if (!$data) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$data = getOpeningData($id_op);
		}
		$request->addResult('opening', $data);
	}
	
	public function logCashFlow($request)
	{
		$id_flow = false;
		$legacy = false;
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$id_flow = $proc->flowTill(
				$request->params->id_cashdrawer,
				$request->context->currency->id,
				$request->params->amount,
				$request->params->mode,
				null,
				$request->params->comment);
		}
		if (!$id_flow) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$id_flow = logCashFlow(
				$request->params->id_cashdrawer,
				$request->context->employee->id,
				$request->params->id_shop,
				$request->params->mode,
				$request->params->amount,
				null,
				$request->params->comment);
			$legacy = true;
		}
		
		$request->params->id_op = $id_flow;
		$request->params->legacy = $legacy;
		$this->getFlowData($request);
	}

	public function getFlowData($request)
	{
		$data = null;
		if (defined('_KERAWEN_525_CLASS_')
			&& (!isset($request->params->legacy) || !$request->params->legacy)) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$data = $proc->getFlowData($request->params->id_op);
		}
		if (!$data) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$data = getFlowData($request->params->id_op);
		}
		$request->addResult('flow', $data);
	}
	
	public function closeCashdrawer($request)
	{
		$id_close = false;
		$legacy = false;
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$id_close = $proc->closeTill(
				$request->params->id_cashdrawer,
				$request->params->data);
		}
		if (!$id_close) {
 			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$id_close = closeCashdrawer(
				$request->params->id_cashdrawer,
				$request->context->employee->id,
				$request->params->id_shop,
				$request->params->data);
				//$request->params->modes);
			$legacy = true;
		}
		
		$request->params->id_till = $request->params->id_cashdrawer;
		$request->params->id_op = $id_close;
		$request->params->legacy = $legacy;
		$this->getClosingData($request);
		
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		$request->addResult('till', getTills($request->params->id_cashdrawer));
	}
	
	public function getClosingData($request)
	{
		$id_op = isset($request->params->id_op) ? $request->params->id_op: null;
		$id_till = isset($request->params->id_till) ? $request->params->id_till: null;
		
		$legacy = isset($request->params->legacy) && $request->params->legacy;
		$legacy_id = substr($id_op, 0, 1) == '_';
		if ($legacy_id) $id_op = substr($id_op, 1, strlen($id_op));
		
		$data = null;
		if (defined('_KERAWEN_525_CLASS_') && !$legacy && !$legacy_id) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$data = $proc->getClosingData($id_op, $id_till);
		}
		if (!$data) {
			require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
			$data = getClosingData($id_op, $id_till);
		}
		$request->addResult('closing', $data);
	}

	public function getLog($request)
	{
		$old_from = $new_from = $request->params->from;
		$old_to = $new_to = $request->params->to;
		
		$new = array();
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			
			// Check NF525 start date
			// General case => 2.1
			// Closing => 2.2
			$version = '2.1';
			if (isset($request->params->closing) && $request->params->closing) $version = '2.2';
			$install = Db::getInstance()->getValue('
					SELECT MIN(date)
					FROM '._DB_PREFIX_.'kerawen_version
					WHERE STRCMP(version, "'.$version.'") >= 0
					AND res = 1');

			// Temporary use legacy data if product stats required
			$diff = array_diff($request->params->require, array("prodStats"));
			if (count($diff) == 0) {
				$install = false;
			}
			
			if ($install) {
				if ($install <= $old_from) {
					$old_from = false;
				}
				else if ($install <= $old_to) {
					$old_to = $new_from = $install;
				}
				else {
					$new_from = false;
				}

				if ($new_from && $new_to) {
					$proc = Kerawen525::getInstance();
					$new = $proc->getLogs(
							$request->params->require,
							$new_from, 
							$new_to,
							$request->params->till,
							$request->params->employee,
							$request->params->shop,
							$request->params->from,
							$request->params->to
					);
				}
			}
		}
		
		$old = array();
		if ($old_from && $old_to) {
			require_once (_KERAWEN_CLASS_DIR_.'/log.php');
			$old = getLog(
				$request->params->require,
				$old_from, 
				$old_to,
				$request->params->till,
				$request->params->employee,
				$request->params->shop,
				$request->params->from,
				$request->params->to,
				$request->params->vars
			);
		}
		
		$logs = array();
		$request->params->require[] = "ops";
		foreach ($request->params->require as $type) {
			if (!isset($new[$type])) $new[$type] = array();
			if (!isset($old[$type])) $old[$type] = array();
			$logs[$type] = array_merge($old[$type], $new[$type]);
		}
		
		// Index ops if required
		if ($request->params->index) {
			require_once(_KERAWEN_TOOLS_DIR_.'/utils.php');
			$logs['ops'] = indexArray($logs['ops'], 'id_op');
		}
		
		$request->addResult('log', $logs);
	}

	public function getLogSecure($request)
	{
		require_once(_KERAWEN_DIR_.'/secure/KerawenLog.php');
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		
		$log = new KerawenLog(array(
			'from' => $request->params->from,
			'to' => $request->params->to,
			'id_till' => $request->params->till,
			'id_operator' => $request->params->employee,
			'id_shop' => $request->params->shop,
			'init_from' => $request->params->from,
			'init_to' => $request->params->to,
			'vars' => $request->params->vars,
		));

		$res = array();
		foreach($request->params->require as $type) {
			$res[$type] = $log->$type();
		}
		$request->addResult('log', $res);
	}

	public function getSalesLog($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		$request->addResult('log', getSalesLog(
			$request->params->from,
			$request->params->to,
			$request->params->till,
			$request->params->employee));
	}

	public function getPaymentsLog($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		$request->addResult('log', getPaymentsLog(
			$request->params->from,
			$request->params->to,
			$request->params->till,
			$request->params->employee,
			isset($request->params->deferred) ? $request->params->deferred : false
		));
	}

	public function getProductsLog($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		$request->addResult('log', getProductsLog(
			$request->params->from,
			$request->params->to,
			$request->params->till,
			$request->params->employee));
	}

	public function getTaxesLog($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		$request->addResult('log', getTaxesLog(
			$request->params->from,
			$request->params->to,
			$request->params->till,
			$request->params->employee));
	}

	public function getClosingsLog($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		$request->addResult('log', getClosingsLog(
			$request->params->from,
			$request->params->to,
			$request->params->till,
			$request->params->employee));
	}

	public function insertCashdrawerOperation($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		return insertCashdrawerOperation($request->context, $request->params);
	}

	public function getCashdrawersFiltersData($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/drawer.php');
		return getCashdrawersFiltersData($request->context, $request);
	}

	public function getEmployeeStats($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/stats.php');
		return getEmployeeStats($request->context, $request);
	}

	public function sendMail($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/mail.php');
		sendMail(
			$request->params->email,
			$request->context->language->id,
			$request->params->subject,
			'text',
			array(
				'{text}' => $request->params->text,
			),
			null
		);
	}

	
	public function sendEmailInvoice($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/invoice.php');
		getInvoicePdf($request->params);
		return true;
	}


	public function sendEmailOrderSlip($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/invoice.php');
		getOrderSlipPdf($request->params);
		return true;
	}


	public function getLogMvt($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/log.php');
		$request->addResult('log', getLogMvt($request->params));
	}

	public function checkPassword($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/stats.php');
		return getEmployeeByPassword($request->params->id_employee, $request->params->password);		
	}
	
	public function initAppCertif($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');
		$request->addResult('shops', selectShops());
		$request->addResult('period', selectPeriod());
	}
	
	public function saveShop($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/shop.php');		
		saveShop($request->params);
		$request->addResult('shops', selectShops());
	}
	
	public function getReceiptData($request) {
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			return $proc->getReceiptData(
				$request->params->orders,
				$request->params->slips,
				$request->params->original,
				$request->params->prices,
				false
			);
		}
		else return null;
	}

	public function cancelOperation($request) {
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			$proc->cancelOPeration($request->params->id_op);
		}
	}

	public function getArchivable($request) {
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			return $proc->getArchivable();
		}
		else return null;
	}

	public function getArchive($request) {
		if (defined('_KERAWEN_525_CLASS_')) {
			require_once(_KERAWEN_525_CLASS_);
			$proc = Kerawen525::getInstance();
			return $proc->getArchive($request->params->id);
		}
		else return null;
	}

	public function getRssFeed($request)
	{
		require_once(_KERAWEN_CLASS_DIR_.'/rssfeed.php');		
		$request->addResult('rssfeed', rssfeed());
	}
	
	public function preScreen($request)
	{
		require_once (_KERAWEN_CLASS_DIR_.'/push.php');
		
		$id_cashdrawer = isset($request->params->id_cashdrawer) ? $request->params->id_cashdrawer : 0;
		//print_r($request->params);
		
		
		if (isset($request->result['cart'])) {
			send2screen($request->result['cart']);
		}		
	}

	public function initMarketing($request)
	{
	    $this->getCartRulesVouchers($request);
	}
	
	public function getCartRulesVouchers($request) 
	{
	    $id_lang = $request->context->language->id;
	    require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
	    $request->addResult('cartRulesVouchers', getCartRulesVouchers($id_lang, 0));
	}

	public function applyCartRuleVoucher($request)
	{
	    require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
	    applyCartRuleVoucher($request->params->data); 
	    $this->getCartRulesVouchers($request);
	}

	public function deleteCartRuleVoucher($request)
	{ 
	    require_once(_KERAWEN_CLASS_DIR_.'/cartrules.php');
	    deleteCartRuleVoucher($request->params->id_cart_rule);
	    $this->getCartRulesVouchers($request);
	}
	
}
