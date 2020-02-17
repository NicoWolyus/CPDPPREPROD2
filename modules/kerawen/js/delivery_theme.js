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
 *  @author    KerAwen <contact@kerawen.com>
 *  @copyright 2014 KerAwen
 *  @license   http://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

$(document).ready(function() {
	kerawen = window.kerawen || {};
	kerawen.widgets = kerawen.widgets || {};
		
	kerawen.findAddressWidgets = function() {
		// Delivery address selection block
		this.widgets.addressSelection = $(".address_delivery").parent();
		// Delivery address display block
		this.widgets.addressDisplay= $("#address_delivery").parent();
		
		// Same address for delivery and invoice checkbox
		this.widgets.sameAddressCheck = $("#addressesAreEquals");
		// Same address delivery and invoice selection block
		this.widgets.sameAddressSelection = this.widgets.sameAddressCheck.parent();
	};
		
	kerawen.findCarrierWidgets = function() {
		// Delivery date selection block
		this.widgets.dateSelection = $("#kerawen-delivery-date");
		// Carrier selection block
		this.widgets.carrierSelection = $("#carrier_area .delivery_options_address");
	};
});


