/*
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
$(document).ready(function () {
	_map_element = document.getElementById('store_single_map');
    initGoogleMap(_map_element);
});

function initGoogleMap(mapElement)
{
	//var mapElement = document.getElementById('map');
	if (typeof mapElement !== 'undefined' && typeof mapElement !== 'null' && mapElement) {
        var mapOptions = {
            center: new google.maps.LatLng(defaultLat, defaultLong),
            zoom: defaultZoom,
            mapTypeId: 'roadmap',
            mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
            //styles: '',
        }
        map = new google.maps.Map(mapElement, mapOptions);
        infoWindow = new google.maps.InfoWindow();
		
		google.maps.event.addListenerOnce(map, 'tilesloaded', function () {
            //IF autolocation is enabled ask user's Permission
        });
        initMarkers();
	}
}

function initMarkers()
{
	searchUrl += '?ajax=1&all=1';
	downloadUrl(searchUrl, function(data) {
		var xml = parseXml(data);
		var markerNodes = xml.documentElement.getElementsByTagName('marker');
		var bounds = new google.maps.LatLngBounds();
		for (var i = 0; i < markerNodes.length; i++) {
			var name = markerNodes[i].getAttribute('name');
			var address = markerNodes[i].getAttribute('address');
			var addressNoHtml = markerNodes[i].getAttribute('addressNoHtml');
			var other = markerNodes[i].getAttribute('other');
			var id_store = parseInt(markerNodes[i].getAttribute('id_store'));
            var phone = markerNodes[i].getAttribute('phone');
			var link = markerNodes[i].getAttribute('link');
            var email = markerNodes[i].getAttribute('email');
            var fax = markerNodes[i].getAttribute('fax');
            var note = markerNodes[i].getAttribute('note');
			var has_store_picture = markerNodes[i].getAttribute('has_store_picture');
			var latlng = new google.maps.LatLng(
			parseFloat(markerNodes[i].getAttribute('lat')),
			parseFloat(markerNodes[i].getAttribute('lng')));
			//console.log('current '+_current_store_id+' loop '+id_store);
			if (_current_store_id === id_store) {
				createMarker(latlng, name, address, other, id_store, has_store_picture, email, fax, note);
				bounds.extend(latlng);
			}
		}
	});
}

function createMarker(latlng, name, address, other, id_store, has_store_picture, email, fax, note)
{
	var html = '<b>'+name+'</b><br/>' +
        address +
        '<br /><a href="https://maps.google.com/maps?saddr=&daddr='+latlng+'" target="_blank">'+translation_5+'<\/a>';
	if (FMESL_STORE_GLOBAL_ICON > 0) {
		var image = new google.maps.MarkerImage(img_ps_dir+'st/icon_'+_current_store_id+'.png');
	}
	else {
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
}

function downloadUrl(url, callback)
{
	var request = window.ActiveXObject ? new ActiveXObject('Microsoft.XMLHTTP') : new XMLHttpRequest();
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