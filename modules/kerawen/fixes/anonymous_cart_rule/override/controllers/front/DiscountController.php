<?php
class DiscountController extends DiscountControllerCore
{
	public function initContent()
	{
		CartRule::$use_generic = false;
		parent::initContent();
		CartRule::$use_generic = true;
	}
}
