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
	var widgets = kerawen.widgets = kerawen.widgets || {};
	
	// External triggers
	kerawen.deliveryOptionsUpdated = function() {
		configDates();
		configCarriers();
		updateDeliveryMode();
	};
	
	widgets.deliveryModeSelectors = $(".kerawen-delivery-mode")
		.click(function() {
			selectDeliveryMode($(this).val());
		});
	
	var selectDeliveryMode = function(mode) {
		$.ajax({
			type: "POST",
			url: kerawenDeliveryUrl + '?rand=' + new Date().getTime(),
			data: {
				ajax: true,
				method: "updateDeliveryMode",
				delivery_mode: mode,
			},
			dataType: "json",
			success: function(json) {
				updateDeliveryMode(mode);
				updateCartSummary(json.summary);
				selectCarrier(json.summary.carrier.id);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				if (textStatus !== "abort")
					alert("TECHNICAL ERROR: unable to save delivery mode\n\nDetails:\nError thrown: " + XMLHttpRequest + "\nText status: " + textStatus);
			}
		});
	};
	
	var updateDeliveryMode = function(mode) {
		kerawenDeliveryMode = mode = mode || kerawenDeliveryMode;
		console.log("Update delivery mode to " + mode);
		
		widgets.deliveryModeSelectors.removeClass("selected");
		widgets.deliveryModeSelectors.filter("[value=" + mode + "]").addClass("selected");
		
		if (window.orderProcess) {
			mode = kerawenDeliveryModes[Number(mode)];
			setAddress(mode.address);
			setDate(mode.date, mode.date_title);
			setCarrier(mode.carrier);
		}
	};

	var setAddress = function(active) {
		if (!active) {
			// Not the same address
			sameAddress = widgets.sameAddressCheck.attr("checked");
			if (sameAddress) widgets.sameAddressCheck.click();
		}
		else if (sameAddress != widgets.sameAddressCheck.attr("checked")) {
			widgets.sameAddressCheck.click();
		}
		widgets.sameAddressSelection.toggle(active);
		widgets.addressSelection.toggle(active);
		widgets.addressDisplay.toggle(active);
	};

	var setDate = function(active, title) {
		widgets.dateSelection.toggle(active);
		widgets.dateSelection.find(".carrier_title").html(title);
	};

	var daySelector, minSelector, hourSelector;
	
	var configDates = function() {
		console.log("Populate dates");

		// Compute pick-up dates for the coming week
		var now = new Date();
		var dow = now.getDay();
		var today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
		now = now.getTime();
		
		// ASAP
		var asap = new Date(now + kerawenOpeningDelay*60*1000);
		var buf = "";
		if (asap.getTime() < today + 24*3600*1000)
			buf += "Aujourd'hui";
		else 
			buf = buf
				+ " " + ("0" + asap.getDate()).slice(-2)
				+ "/" + ("0" + (asap.getMonth()+1)).slice(-2);
		buf = buf
			+ " " + ("0" + asap.getHours()).slice(-2)
			+ ":" + ("0" + asap.getMinutes()).slice(-2);
		$("#kerawen-asap").html(buf);
		
		var step = Number(kerawenOpeningStep);
		var dates = {};
		for (var i = 0; i < 7; i++) {
			var d = (dow + i)%7;
			var periods = kerawenOpeningHours[d];
			for (var p in periods) {
				var period = periods[p];
				if (period && period[0] && period[1]) {
					var end = period[1]*60;
					var time = period[0]*60;
					while (time < end) {
						var m = time%60;
						var h = (time - m)/60;
						
						dates[i] = dates[i] || {
							label: "jour + " + i,
							hours: {},
						};
						dates[i].hours[h] = dates[i].hours[h] || [];
						dates[i].hours[h].push(m);
						
						time += step;
					}
				}
			}
		}
		
		// Configure widgets
		minSelector = $("#kerawen-delivery-min")
		.change(function() {
			console.log("min selected : " + minSelector.val());
			// TODO call
		});

		hourSelector = $("#kerawen-delivery-hour")
		.change(function() {
			console.log("hour selected: " + hourSelector.val());
			
			// Update minutes
			var mins = dates[daySelector.val()].hours[hourSelector.val()];
			minSelector.empty();
			$.each(mins, function(i, v) {
				minSelector.append($("<option/>").val(v).html(("0" + v).slice(-2)));
			});
			minSelector.change();
		});

		daySelector = $("#kerawen-delivery-day")
		.change(function() {
			console.log("day selected: " + daySelector.val());
			
			// Update hours
			var hours = dates[daySelector.val()].hours;
			hourSelector.empty();
			$.each(hours, function(i, v) {
				hourSelector.append($("<option/>").val(i).html(("0" + i).slice(-2)));
			});
			hourSelector.change();
		});
	
		// Populate days
		$.each(dates, function(i, v) {
			daySelector.append(
				$("<option/>").val(i).html(v.label)
			);
		});
		daySelector.change();
	};
	
	var configCarriers = function() {
		kerawen.findCarrierWidgets();
		
		// Hide reserved carriers
		$.each(kerawenDeliveryModes, function(index, mode) {
			kerawen.widgets.carrierSelection
			.find("input[value=\"" + mode.carrier + ",\"]")
			.parent().parent().parent().parent()
			.hide();
		});
	};
	
	var setCarrier = function(id_carrier) {
		widgets.carrierSelection.toggle(!id_carrier);
	};

	var selectCarrier = function(id_carrier) {
		var input = kerawen.widgets.carrierSelection
		.find("input[value=\"" + id_carrier + ",\"]")
		.attr("checked", "checked");
		input.parent().addClass("checked");
	};
	
	// First page update
	kerawen.findAddressWidgets();
	var sameAddress = widgets.sameAddressCheck.attr("checked");
	kerawen.deliveryOptionsUpdated();
	
	// Update regularly in order date remains valid
	var update = function() {
		console.log("Force carriers & dates update");
		//kerawen.widgets.carrierSelection.find("input:checked").change();
		setTimeout(update, 30*1000);
	};
	setTimeout(update, 30*1000);
});


