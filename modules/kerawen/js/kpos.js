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
	var deliveryDayBase = $('#delivery_day_base').val();

	var laterRadio = $('#delivery_time_1');
	laterRadio.on('click', function() {
		laterRadio.prop("checked", true);
		if (deliveryDayBase === "today") {
			// Disable minutes depending on hour
			disableNonApplicableMinutes();
		}
	});
	$('#cgv').on('click', function() {
		updateDeliveryTime();
	});
	$('#delivery_day_select').on('change', function() {
		laterRadio.click();
	});
	$('#delivery_hour_select').on('change', function() {
		laterRadio.click();
	});
	$('#delivery_minute_select').on('change', function() {
		laterRadio.click();
	});
	$('input.delivery_time_radio').on('click', function() {
		updateDeliveryTime();
	});

	if (deliveryDayBase === "today") {
		// Update time every minute
		updateAvailableTime();
		// Disable minutes depending on hour
		disableNonApplicableMinutes();
		setInterval(function() {
			updateAvailableTime();
			disableNonApplicableMinutes();
		}, 60 * 1000);
	}
});

function hideDeliveryAddress() {
	// Hide delivery address
	var sameAddress = $("#addressesAreEquals").attr("checked");
	if (sameAddress) $("#addressesAreEquals").click();
	$("#addressesAreEquals").parent().hide();
	$(".address_delivery").parent().hide();
	$("#address_delivery").parent().hide();
};

function pad(n, width, z) {
	z = z || '0';
	n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function getMinimumDeliveryTime() {
	var halfAnHour = new Date();
	halfAnHour.setSeconds(halfAnHour.getSeconds() + 60 * 30);
	var hour = pad(halfAnHour.getHours(), 2);
	var minutes = pad(halfAnHour.getMinutes(), 2);

	// Select next applicable delivery time
	var tmp = parseInt(minutes) + 7.5;
	var minuteToApply = Math.round(tmp / 15) * 15;
	var hourToApply = hour;
	if(60 === minuteToApply) {
		hourToApply++;
		minuteToApply = 0;
	}

	return {
		'halfAnHour': halfAnHour,
		'hour': hour,
		'hourInt': parseInt(hour),
		'hourToApply': hourToApply,
		'minutes': minutes,
		'minuteToApply': minuteToApply,
	};
}

function disableNonApplicableMinutes() {
	var minimumDeliveryTime = getMinimumDeliveryTime();

	// date + hour
	var selectedHour = $('#delivery_hour_select option:selected');
	var selectedHourInt = parseInt(selectedHour.val());
	var hourToCompare = minimumDeliveryTime.hourInt;
	if(0 === minimumDeliveryTime.minuteToApply) {
		hourToCompare++;
	}
	if (selectedHourInt === minimumDeliveryTime.hourInt) {
		// Disable unapplicable minutes
		$("#delivery_minute_select > option").each(function(i) {
			var $this = $(this);
			var valInt = parseInt($this.val());
			if(valInt < minimumDeliveryTime.minuteToApply) {
				$this.attr('disabled','disabled');
			} else {
				// break
				return false;
			}
		});
	} else {
		// Enable every option
		$("#delivery_minute_select > option").removeAttr('disabled');
	}
	
	if($("#delivery_minute_select > option:selected").is(':disabled')
			|| minimumDeliveryTime.hourToApply > selectedHourInt) {
		updateAvailableTime();
	}
}

function updateAvailableTime() {
	var minimumDeliveryTime = getMinimumDeliveryTime();

	$("#delivery_time_0").val(
		// half an hour
		Math.round(minimumDeliveryTime.halfAnHour.getTime() / 1000)
	);
	$("#delivery_time_sooner_txt").html(
		minimumDeliveryTime.hour + "h" + minimumDeliveryTime.minutes
	);

	// Change selection if not applicable
	var hourDom = $("#delivery_hour_select");
	var minuteDom = $("#delivery_minute_select");
	
	var selHour = parseInt(hourDom.val());
	var selMinute = parseInt(minuteDom.val());
	if (isNaN(selMinute)
				|| (selHour <= minimumDeliveryTime.hourInt
				&& selMinute <= minimumDeliveryTime.minuteToApply)) {
		hourDom.val(minimumDeliveryTime.hourToApply);
		minuteDom.val(minimumDeliveryTime.minuteToApply);
	}

	// Remove unapplicable hours
	$("#delivery_hour_select > option").each(function(i) {
		var $this = $(this);
		var valInt = parseInt($this.val());
		if(valInt < minimumDeliveryTime.hourInt) {
			$this.remove();
		}
	});
}

function updateDeliveryTime() {
	var radio = $('.delivery_time_radio[type=radio]');
	var url_params = '&';
	$.each(radio, function(i) {
		if ($(this).prop('checked')) {
			var val = null;
			if ($(radio[i]).attr('id') !== 'delivery_time_0') {
				// day at midnight
				var selectDay = $('#delivery_day_select option:selected');
				val = parseInt(selectDay.val());
				// date + hour
				var selectHour = $('#delivery_hour_select option:selected');
				val = val + parseInt(selectHour.val()) * 60 * 60;
				// date + minutes
				var selectMinute = $('#delivery_minute_select option:selected');
				val = val + parseInt(selectMinute.val()) * 60;
			} else {
				val = $(radio[i]).val();
			}
			url_params = url_params + 'delivery_date=' + val;
		}
	});


	var url = "";

	if (typeof (orderOpcUrl) !== 'undefined')
		url = orderOpcUrl;
	else
		url = orderUrl;
	
	// TEST with module fc
	url = "index.php";
	data = "fc=module"
		+ "&module=kerawen"
		+ "&controller=delivery"
		+ "&ajax=true"
		+ "&method=updateDeliveryDate"
		+ url_params
		+ "&token=" + static_token
		;

	$.ajax({
		type: 'GET',
		headers: {"cache-control": "no-cache"},
		url: url, //url + '?rand=' + new Date().getTime(),
		async: true,
		cache: false,
		//dataType: "json",
		//data: 'ajax=true&method=updateDeliveryTime' + url_params + '&token=' + static_token,
		data: data,
		success: function(res) {},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (textStatus !== 'abort')
				alert("TECHNICAL ERROR: unable to save delivery time \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
		}
	});
}
