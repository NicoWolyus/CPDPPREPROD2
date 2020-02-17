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

/* Accountancy config */
define('_KERAWEN_DEFAULT_PRODUCT_ACCOUNT_', '707KerAwen');
define('_KERAWEN_DEFAULT_TAXES_ACCOUNT_', '44571KerAwen');


/* Rounding modes */
if (Tools::version_compare(_PS_VERSION_, '1.6.0.11', '<')) {
	define('_KERAWEN_RM_ITEM_', 1);
	define('_KERAWEN_RM_LINE_', 2);
	define('_KERAWEN_RM_TOTAL_', 3);
}
else {
	define('_KERAWEN_RM_ITEM_', Order::ROUND_ITEM);
	define('_KERAWEN_RM_LINE_', Order::ROUND_LINE);
	define('_KERAWEN_RM_TOTAL_', Order::ROUND_TOTAL);
}

/* Price adjustments */
define('_KERAWEN_PA_AMOUNT_', 'AMOUNT');
define('_KERAWEN_PA_PERCENT_', 'PERCENT');
define('_KERAWEN_PA_REPLACE_', 'REPLACE');

/* Cart rules */
define('_KERAWEN_CR_DISCOUNT_', 'DISCOUNT');
define('_KERAWEN_CR_CREDIT_', 'CREDIT');
define('_KERAWEN_CR_PREPAID_', 'PREPAID');
define('_KERAWEN_CR_LOYALTY_', 'LOYALTY');
define('_KERAWEN_CR_REFERRAL_', 'REFERRAL');
define('_KERAWEN_CR_GROUP_', 'GROUP');
define('_KERAWEN_CR_GIFT_CARD_', 'GIFT_CARD');
define('_KERAWEN_CR_FIDELISA_', 'FIDELISA');
define('_KERAWEN_CR_VOUCHER_', 'VOUCHER');

/* Delivery modes */
define('_KERAWEN_DM_IN_STORE_', 0);
define('_KERAWEN_DM_TAKEAWAY_', 1);
define('_KERAWEN_DM_DELIVERY_', 2);

/* Payment modes TODO in database */
define('_KERAWEN_PM_CASH_', 1);
define('_KERAWEN_PM_CHEQUE_', 2);
define('_KERAWEN_PM_CARD_', 3);
define('_KERAWEN_PM_MEAL_', 4);
define('_KERAWEN_PM_PAY_LATER_', 5);
define('_KERAWEN_PM_CREDIT_', 6);
define('_KERAWEN_PM_BANK_', 7);
define('_KERAWEN_PM_VOUCHER_', 8);
define('_KERAWEN_PM_SPLIT_', 9);
define('_KERAWEN_PM_PREPAID_', 10);
define('_KERAWEN_PM_REFUND_LATER_', 11);
define('_KERAWEN_PM_OTHER1_', 12);
define('_KERAWEN_PM_OTHER2_', 13);
define('_KERAWEN_PM_OTHER3_', 14);
define('_KERAWEN_PM_OTHER4_', 15);
define('_KERAWEN_PM_OTHER5_', 16);
define('_KERAWEN_PM_GIFT_CARD_', 17);
define('_KERAWEN_PM_OTHER6_', 18);
define('_KERAWEN_PM_OTHER7_', 19);
define('_KERAWEN_PM_OTHER8_', 20);
define('_KERAWEN_PM_OTHER9_', 21);
define('_KERAWEN_PM_OTHER10_', 22);
define('_KERAWEN_PM_OTHER11_', 23);

/* Cashiers and drawers */
define('_KERAWEN_CD_ALL_', -1);
define('_KERAWEN_CD_NONE_', 0);

/* Cashdrawer operations */
define('_KERAWEN_CDOP_OPEN_', 'OPEN');
define('_KERAWEN_CDOP_CLOSE_', 'CLOSE');
define('_KERAWEN_CDOP_FLOW_', 'FLOW');
define('_KERAWEN_CDOP_SALE_', 'SALE');

define('_KERAWEN_CDSO_ORDER_', 'ORDER');
define('_KERAWEN_CDSO_VALID_', 'VALID');
define('_KERAWEN_CDSO_SLIP_', 'SLIP');
define('_KERAWEN_CDSO_CANCEL_', 'CANCEL');
define('_KERAWEN_CDSO_PAYMENT_', 'PAYMENT');
