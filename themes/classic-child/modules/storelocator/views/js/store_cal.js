/*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*/

$(document).ready(function() {
    initPickupDate(default_store);
});

function initPickupDate(id_store) {
    if (FMESL_PICKUP_STORE == 1 && $.inArray(st_page, ['order', 'orderopc']) >= 0) {
        getStoreDates(id_store);
    }
}

function getStoreDates(id_store) {
    var request = {
        url: searchUrl,
        method: 'get',
        dataType: 'json',
        data: {
            ajax: 1,
            id_store: id_store,
            action: 'getStoreDates'
        },
        success: function(response) {
            if (response.success) {
                var dateOptions = {
                    locale: locale,
                    minuteIncrement: 1,
                    noCalendar: false,
                    minDate: "today",
                    maxDate: maxDate,
                    dateFormat: "Y-m-d",
                    minuteIncrement: 5,
                    monthSelectorType: 'static',
                    "disable": [
                        function(date) {
                            return (($.inArray(moment(date).format('YYYY-MM-DD') , response.disabled.split(',')) >= 0));
                        }
                    ],
                };

                var pickuptime = null;
                $('.pickuptime').each(function(e) {
                    switch ($(this).attr('data-type')) {
                        case 'date':
                            dateOptions.defaultDate = preselectedPickupDate;
                            dateOptions.enableTime = false;
                            dateOptions.onChange = function(selectedDates, dateStr, instance) {
                                if ($('#pickup_time_wrapper').length) {
                                    if (typeof dateStr === 'undefined' || !dateStr) {
                                        $('#pickup_time_wrapper').hide();
                                    } else {
                                        $('#pickup_time_wrapper').show();
                                        var weekday = moment(dateStr).format('d');
                                        console.log(response.timeslot.length)
                                        if (typeof response.timeslot !== 'undefined' && response.timeslot.length > 1) {
                                            // set opening hours
                                            if (false !== response.timeslot[weekday].minTime) {
                                                pickuptime.config.minTime = response.timeslot[weekday].minTime;
                                                pickuptime.set("minTime" , response.timeslot[weekday].minTime);
                                                defaultHour = response.timeslot[weekday].minDate;
                                            }
                                            // set closing hours
                                            if (false !== response.timeslot[weekday].maxTime) {
                                                pickuptime.config.maxTime = response.timeslot[weekday].maxTime;
                                                pickuptime.set("maxTime" , response.timeslot[weekday].maxTime);
                                            }

                                            // set default pickup hours
                                            if (false !== response.timeslot[weekday].defaultHour) {
                                                pickuptime.config.defaultHour = response.timeslot[weekday].defaultHour;
                                                pickuptime.set("defaultHour" , response.timeslot[weekday].defaultHour);
                                            }

                                            // set default pickup minutes
                                            if (false !== response.timeslot[weekday].defaultMinute) {
                                                pickuptime.config.defaultMinute = response.timeslot[weekday].defaultMinute;
                                                pickuptime.set("defaultMinute" , response.timeslot[weekday].defaultMinute);
                                            }
                                        }
                                    }
                                }
                            };
                            flatpickr($(this), dateOptions);
                            break;
                        case 'time':
                            dateOptions.defaultDate = preselectedPickupTime;
                            dateOptions.enableTime = true;
                            dateOptions.noCalendar = true;
                            dateOptions.dateFormat = 'H:i';
                            dateOptions.onChange = [];
                            dateOptions.onReady = function() {
                                if (!$.trim($('input[name=storelocator_pickup_date').val())) {
                                    $('#pickup_time_wrapper').hide();
                                }
                            };
                            pickuptime = flatpickr($(this), dateOptions);
                            break;
                    }
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('Error: ' + textStatus + '<br>' + errorThrown);
        }
    }
    $.ajax(request);
}