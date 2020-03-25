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

var $_slBox    = $('.fmeSearchbyProduct');
var autocompleteOptions = {
    url: search_url,
    dataType: 'json',
    theme: "bootstrap",
    ajaxSettings : {
        method : 'POST',
    },
    preparePostData: function(data, inputPhrase) {
        data = {
            ajax: true,
            s: inputPhrase,
            resultsPerPage: 10,
            action: 'searchStoreProduct',
        };
        return data;
    },
    placeholder: placeholder_label,
    requestDelay: 300,
    getValue:function(element) {
        return element.name;
    },
    list: {
        maxNumberOfElements: 10,
        onClickEvent: function() {
            var responseData = $_slBox.getSelectedItemData();
            var _id_sl = responseData.id_product;
            var _name_sl = responseData.name;
            $('#fmeSearchProduct').attr('value', _name_sl);
            $('#fmeSearchProduct').attr('name',_id_sl);
            $('#fmeSearchProduct').attr('placeholder',_name_sl);
        },
    },
};
function initMarkers()
{
    searchUrl += '?ajax=1&all=1';
    downloadUrl(searchUrl, function(data) {
        var xml = parseXml(data);
        var markerNodes = xml.documentElement.getElementsByTagName('marker');
        var bounds = new google.maps.LatLngBounds();
        if (FMESL_LAYOUT_THEME > 0) {
            $('#fmmsl_split_list').html('<ul></ul>');
            clearLocations(markerNodes.length);
        }
        for (var i = 0; i < markerNodes.length; i++) {
            var name = markerNodes[i].getAttribute('name');
            var address = markerNodes[i].getAttribute('address');
            var addressNoHtml = markerNodes[i].getAttribute('addressNoHtml');
            var other = markerNodes[i].getAttribute('other');
            var id_store = markerNodes[i].getAttribute('id_store');
            var phone = markerNodes[i].getAttribute('phone');
            var link = markerNodes[i].getAttribute('link');
            var email = markerNodes[i].getAttribute('email');
            var fax = markerNodes[i].getAttribute('fax');
            var note = markerNodes[i].getAttribute('note');
            var has_store_picture = markerNodes[i].getAttribute('has_store_picture');
            var latlng = new google.maps.LatLng(
            parseFloat(markerNodes[i].getAttribute('lat')),
            parseFloat(markerNodes[i].getAttribute('lng')));
            createMarker(latlng, name, address, other, id_store, has_store_picture, email, fax, note);
            if (FMESL_LAYOUT_THEME > 0) {
            $('#fmmsl_split_list ul').append('<li><a href="javascript:void(0);" onclick="fmmSlTriggerStore('+i+');" title="'+name+'">'+name+'</a><p>'+
                addressNoHtml+(FMESL_STORE_FAX && fax !== '' ? '<br />'+translation_8+' '+fax : '')+'</p>'+(FMESL_MAP_LINK > 0 ? '<br /><a href="'+link+'" class="btn btn-default button button-small fmmsl_storeview"><span>'+translation_11+'</span></a></li>' : ''));
            }
            bounds.extend(latlng);
        }
    });
}

function searchLocations()
{
    $('#stores_loader').show();
    var address = document.getElementById('addressInput').value;
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({address: address}, function(results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            searchLocationsNear(results[0].geometry.location);
        } else {
            alert(address + ' ' + translation_6);
        }
        $('#stores_loader').hide();
    });
}

function clearLocations(n)
{
    infoWindow.close();
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
        
    markers.length = 0;
    if (FMESL_LAYOUT_THEME <= 0) {
        $(locationSelect).show();
        locationSelect.innerHTML = '';
        var option = document.createElement('option');
        option.value = 'none';
        if (!n) {
            option.innerHTML = translation_1;
        } else {
            if (n === 1) {
                option.innerHTML = '1' + ' ' + translation_2;
            } else {
                option.innerHTML = n + ' ' + translation_3;
            }
        }
        locationSelect.appendChild(option);
    }
    $('#stores-table tr.node').remove();
}

function searchLocationsNear(center)
{
    var radius = document.getElementById('radiusSelect').value;
    var productID = $('#fmeSearchProduct').attr('name');
    if (typeof(productID) == 'undefined' || productID == null) { productID = '';}
    var searchUrl = baseUri + '?controller=stores&ajax=1&latitude=' + center.lat() + '&longitude=' + center.lng() + '&radius=' + radius+'&product='+productID;
    downloadUrl(searchUrl, function(data) {
        var xml = parseXml(data);
        var markerNodes = xml.documentElement.getElementsByTagName('marker');
        var bounds = new google.maps.LatLngBounds();

        clearLocations(markerNodes.length);
        $('#fmmsl_split_list').html('<ul></ul>');
        for (var i = 0; i < markerNodes.length; i++)
        {
            var name = markerNodes[i].getAttribute('name');
            var address = markerNodes[i].getAttribute('address');
            var addressNoHtml = markerNodes[i].getAttribute('addressNoHtml');
            var other = markerNodes[i].getAttribute('other');
            var distance = parseFloat(markerNodes[i].getAttribute('distance'));
            var id_store = parseFloat(markerNodes[i].getAttribute('id_store'));
            var phone = markerNodes[i].getAttribute('phone');
            var email = markerNodes[i].getAttribute('email');
            var fax = markerNodes[i].getAttribute('fax');
            var note = markerNodes[i].getAttribute('note');
            var has_store_picture = markerNodes[i].getAttribute('has_store_picture');
            var latlng = new google.maps.LatLng(
            parseFloat(markerNodes[i].getAttribute('lat')),
            parseFloat(markerNodes[i].getAttribute('lng')));

            createOption(name, distance, i);
            createMarker(latlng, name, address, other, id_store, has_store_picture, email, fax, note);
            bounds.extend(latlng);
            if (FMESL_LAYOUT_THEME > 0) {
                $('#fmmsl_split_list ul').append('<li><a href="javascript:void(0);" onclick="fmmSlTriggerStore('+i+');" title="'+name+'">'+name+'</a><p>'+
                addressNoHtml+(FMESL_STORE_FAX && fax !== '' ? '<br />'+translation_8+' '+fax : '')+
                '<br />'+translation_10+' '+distance+' '+distance_unit+'</p></li>');
            }
            else {
                $('#stores-table tr:last').after('<tr class="node"><td class="num">'+parseInt(i + 1)+'</td><td><b>'+name+'</b>'+(has_store_picture === 1 ? '<br /><img src="'+img_store_dir+parseInt(id_store)+'-medium.jpg" alt="" />' : '')+'</td><td>'+address+(phone !== '' ? '<br /><br />'+translation_4+' '+phone : '')+'</td><td class="distance">'+distance+' '+distance_unit+'</td></tr>');
                $('#stores-table').show();
            }
        }

        if (markerNodes.length)
        {
            map.fitBounds(bounds);
            var listener = google.maps.event.addListener(map, "idle", function() { 
                if (map.getZoom() > 13) map.setZoom(13);
                google.maps.event.removeListener(listener); 
            });
        }
        if (FMESL_LAYOUT_THEME <= 0) {
            locationSelect.style.visibility = 'visible';
            $(locationSelect).show();
            locationSelect.onchange = function() {
                var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
                google.maps.event.trigger(markers[markerNum], 'click');
            };
        }
    });
}

function createMarker(latlng, name, address, other, id_store, has_store_picture, email, fax, note)
{
    var html = '<b>'+name+'</b><br/>' +
        address +
        (FMESL_STORE_EMAIL && email !== '' ? '<br />' + translation_7 + ' ' + email : '') +
        (FMESL_STORE_FAX && fax !== '' ? '<br />' + translation_8 + ' ' + fax : '') +
        (FMESL_STORE_NOTE && note !== '' ? '<br />' + translation_9 + ' ' + note : '') +
        (has_store_picture > 0 ? '<br /><br /><img src="'+img_store_dir+parseInt(id_store)+'-medium_default.jpg" alt="'+name+'" style="max-width:125px" />' : '') +
        '<br />' + other +
        '<br /><a href="https://maps.google.com/maps?saddr=&daddr='+latlng+'" target="_blank">'+translation_5+'<\/a>';

    if (typeof st_page !== 'undefined' && ($.inArray(st_page, ['order', 'orderopc']) >= 0)) {
        html += '<a class="store_selection" href="javascript:;" onclick="selectStore(' + id_store + ')">'+translation_store_sel+'<\/a>';
    }

    //var image = new google.maps.MarkerImage(img_ps_dir+logo_store);
    if (FMESL_STORE_GLOBAL_ICON > 0) {
        var image = new google.maps.MarkerImage(img_ps_dir+'st/icon_'+id_store+'.png');
    } else {
        var image = new google.maps.MarkerImage(img_ps_dir+logo_store);
    }
    var marker = '';

    if (hasStoreIcon) {
        marker = new google.maps.Marker({map: map, icon: image, position: latlng});
    } else {
        marker = new google.maps.Marker({map: map, position: latlng});
    }
    google.maps.event.addListener(marker, 'click', function() {
        infoWindow.setContent(html);
        infoWindow.open(map, marker);
    });
    
    markers.push(marker);
    google.maps.event.addListenerOnce(map, 'tilesloaded', function() {
        $('select#locationSelect > option[label="'+CurrentUrl+'"]').prop('selected',true);
        $('select#locationSelect').trigger('change');
    })
}

function createOption(name, distance, num)
{
    if (FMESL_LAYOUT_THEME <= 0) {
        var option = document.createElement('option');
        option.value = num;
        option.innerHTML = name+' ('+distance.toFixed(1)+' '+distance_unit+')';
        locationSelect.appendChild(option);
    }
}

function downloadUrl(url, callback)
{
    var request = window.ActiveXObject ?
    new ActiveXObject('Microsoft.XMLHTTP') :
    new XMLHttpRequest();

    request.onreadystatechange = function() {
        if (request.readyState === 4) {
            request.onreadystatechange = doNothing;
            callback(request.responseText, request.status);
        }
    };

    request.open('GET', url, true);
    request.send(null);
}

function parseXml(str)
{
    if (window.ActiveXObject) {
        var doc = new ActiveXObject('Microsoft.XMLDOM');
        doc.loadXML(str);
        return doc;
    } else if (window.DOMParser) {
        return (new DOMParser()).parseFromString(str, 'text/xml');
    }
}

function doNothing() {}

function PosLoc(position) {
    // Centre the map on the new location
    var coords = position.coords || position.coordinate || position;
    var LtLnPos = new google.maps.LatLng(coords.latitude, coords.longitude);
    map.setCenter(LtLnPos);
    map.setZoom(10);
    var marker = new google.maps.Marker({
        map: map,
        position: LtLnPos,
        title: translation_06
    });
    markers.push(marker);

    // And reverse geocode.
    (new google.maps.Geocoder()).geocode({latLng: LtLnPos}, function(resp) {
          var place = translation_07; //You're around here somewhere!
          if (resp[0]) {
              var bits = [];
              for (var i = 0, I = resp[0].address_components.length; i < I; ++i) {
                  var component = resp[0].address_components[i];
                  if (jQuery.inArray(component.types, 'political')) {
                      bits.push(component.long_name);
                    }
                }
                if (bits.length) {
                    place = bits;
                }
                marker.setTitle(resp[0].formatted_address);
            }
            document.getElementById('addressInput').value = place;
            map.setZoom(5);
      });
}

function PosUnSuccess(issue) {
    var message;
    switch(issue.code) {
      case issue.UNKNOWN_ERROR:
        message = translation_01; // Unable to find your location
        break;
      case issue.PERMISSION_DENINED:
        message = translation_02; //Permission denied
        break;
      case issue.POSITION_UNAVAILABLE:
        message = translation_03; //Your location unknown
        break;
      case issue.BREAK:
        message = translation_04; //Timeout error
        break;
      default:
        message = translation_05; //Location detection not supported in browser
    }
}

//Reset MAP
function ResetMap(n)
{
    infoWindow.close();
    map.setZoom(10);
    initMarkers();
     var LtLnPos = new google.maps.LatLng(defaultLat, defaultLong);
    map.setCenter(LtLnPos);
    if (FMESL_LAYOUT_THEME <= 0) {
        locationSelect.innerHTML = '';
        var option = document.createElement('option');
        option.value = 'none';
        if (!n)
            option.innerHTML = translation_1;
        else
        {
            if (n === 1)
                option.innerHTML = '1'+' '+translation_2;
            else
                option.innerHTML = n+' '+translation_3;
        }
        locationSelect.appendChild(option);
        $('#stores-table tr.node').remove();
        $('#locationSelect').hide();
    }
}

function initMap() {
    var map_elements = $('.store_map');
    if (map_elements.length) {
        map_elements.each(function(e) {
            initGoogleMap($(this).get(0));
        });

        if (typeof default_store !== 'undefined' && default_store) {
            setTimeout(function(){
                fmmSlTriggerStore(default_store);
            }, 1500);
        }
    }
}

function initGoogleMap(mapElement)
{
    //var mapElement = document.getElementById('map');
    if (typeof mapElement !== 'undefined' && typeof mapElement !== 'null' && mapElement) {
        var mapOptions = {
            center: new google.maps.LatLng(defaultLat, defaultLong),
            zoom: defaultZoom,
            mapTypeId: 'roadmap',
            mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
            styles: (typeof map_theme !== 'undefined') ? map_theme : '',
        }
        map = new google.maps.Map(mapElement, mapOptions);
        infoWindow = new google.maps.InfoWindow();
        if (FMESL_LAYOUT_THEME <= 0) {
            locationSelect = document.getElementById('locationSelect');
            if (typeof locationSelect !== 'undefined' && typeof locationSelect !== 'null' && locationSelect) {
                $('#locationSelect').hide();
                locationSelect.onchange = function () {
                    var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
                    if (markerNum !== 'none') {
                        google.maps.event.trigger(markers[markerNum], 'click');
                    }
                };

                $('#addressInput').keypress(function (e) {
                    code = e.keyCode ? e.keyCode : e.which;
                    if (code.toString() === 13) {
                        searchLocations();
                    }
                });
            }
        }

        google.maps.event.addListenerOnce(map, 'tilesloaded', function () {

            //IF autolocation is enabled ask user's Permission
            if (autolocateUser) {
                navigator.geolocation.getCurrentPosition(PosLoc, PosUnSuccess);
            }
        });
        initMarkers();
    }
}

$(document).ready(function () {
    initMap();
    //initGoogleMap();
    $_slBox.easyAutocomplete(autocompleteOptions);

    if (FMESL_PICKUP_STORE == 1) {
        if ($.inArray(st_page, ['order', 'orderopc']) >= 0) {
            checkCarrier(parseInt($('.delivery_options').find('input[type=radio]:checked').val()), $('.delivery_options').find('input[type=radio]:checked'));
        }

        $(document).on('change', 'input.delivery_option_radio', function() {
            var key = $(this).data('key');
            var id_carrier = parseInt($(this).val());
            checkCarrier(id_carrier, $(this));
        });

        $(document).on('submit', 'form[name=carrier_area]', function(event) {
            if ($('#stores').length) {
                //event.preventDefault();
                //event.stopImmediatePropagation();

                var pickupTime = null;
                var pickupDate = $.trim($('#storelocator_pickup_date').val());
                var id_store = parseInt($('select#locationSelect option:selected').attr('data-value'));

                if (id_store === -1) {
                    $('#fmeStorePage').after('<div id="fmeStorePage-error" class="alert alert-danger danger">' + store_page_error_label + '</div>');
                    $('html, body').animate({
                        scrollTop: $("#stores").offset().top
                    }, 300);
                    return false;
                } else {
                    var proceed = true;
                    $('#fmeStorePage-error').remove();
                    if (!moment( pickupDate, 'YYYY-MM-DD' ).isValid()) {
                        $('#storelocator_pickup_date').attr('placeholder', invalid_pickupdate_label);
                        proceed = false;
                    } else {
                        proceed = true;
                        $('#storelocator_pickup_date').removeAttr('placeholder');
                        if ($('#storelocator_pickup_time').length) {
                            pickupTime = $.trim($('#storelocator_pickup_time').val());
                            if (!moment(pickupTime, 'H:i' ).isValid()) {
                                proceed = false;
                                $('#storelocator_pickup_time').attr('placeholder', invalid_pickuptime_label);
                            } else {
                                proceed = true;
                                $('#storelocator_pickup_time').removeAttr('placeholder');
                            }
                        }
                    }

                    if (!proceed) {
                        $('html, body').animate({
                            scrollTop: $("#storelocatore-cal-wrapper").offset().top
                        }, 300);
                    } else {
                        var resquest = {
                            url: searchUrl,
                            type: 'get',
                            dataType: 'json',
                            async: false,
                            data: {
                                ajax: 1,
                                action: 'savePickup',
                                id_store: id_store,
                                pickupTime: pickupTime,
                                pickupDate: pickupDate,
                            },
                            success: function(response) {
                                $('#pickup-response-error').remove();
                                if (typeof response !== 'undefined' && response.hasError) {
                                    $('#stores').after('<div id="pickup-response-error" class="alert alert-danger danger">' + response.msg + '</div>');
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alert('Error: ' + textStatus + '<br>' + errorThrown);
                            }
                        };
                        $.ajax(resquest);
                    }
                    return proceed;
                }
            }
        });
    }
});
function fmmSlTriggerStore(id) {
    if (typeof initPickupDate !== 'undefined') {
        var id_store = $('#locationSelect option:selected').attr('data-value');
        initPickupDate(id_store);
        //console.log(document.querySelector("#storelocator_pickup_date")._flatpickr)
    }
    google.maps.event.trigger(markers[id], 'click');
}

function checkCarrier(id_carrier, object) {
    $('#stores').remove();
    $('#storelocator-delivery-button').remove();
    if (typeof id_carrier !== 'undefined' && id_carrier && (typeof sl_carrier !== 'undefined' || typeof sl_carrier !== 'null')) {
        //id_carrier = id_carrier.replace(/,\s*$/, '');
        if (id_carrier == sl_carrier) {
            getMapStores(object);
            //moveShippingFormButton();
        }
    }
    selectStore(0);
}

function getMapStores(object) {
    var jsonData = {
        url: searchUrl,
        method: 'get',
        dataType: 'json',
        data: {
            ajax: 1,
            action: 'getMapStores'
        },
        success: function(response) {
            if (response.success) {
                var html = (typeof response.html !== 'undefined')? $.trim(response.html.replace(/<\!--.*?-->/g, "")) : '';
                if ($('#stores').length) {
                    $('#stores').remove();
                }
                object.closest('table.table').after(html);
                initMap();
                $('.fmeSearchbyProduct').easyAutocomplete(autocompleteOptions);

                if (typeof initPickupDate !== 'undefined') {
                    initPickupDate();
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('Error: ' + textStatus + '<br>' + errorThrown);
        }
    }
    $.ajax(jsonData);
}

/**
 * on store dropdown change, get store pickup time
 * @param {int} id_store
 */
function selectStore(id_store) {
    var jsonData = {
        url: searchUrl,
        method: 'post',
        dataType: 'json',
        data: {
            ajax: 1,
            id_store: id_store,
            action: 'selectStore'
        },
        success: function(response) {
            console.log(response);
            //To do - get and append pickup data
            //initPickupDate(id_store);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('Error: ' + textStatus + '<br>' + errorThrown);
        }
    }
    $.ajax(jsonData);
}