$(document).ready(function() {
	$('#adtm_menu ul#menu li.li-niveau1 a.a-niveau1 span img').bind('click', function() {
		location.href = $(this).parent('span').parent('a').attr('href');
	});
});