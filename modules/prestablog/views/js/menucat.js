/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2

 */
$(document).ready(function() {
  $('i.idtest').click(function(e) {
    e.preventDefault();
    var submenu = $(e.target).closest('li').children('ul.sub-menu');

     for (let i = 0; i < submenu.length; i++) {
     	if(submenu[i].classList.contains("hidden"))
	{

     	submenu[i].classList.remove("hidden");
     	submenu[i].classList.add("block");
     	} else {
		submenu[i].classList.remove("block");
     	submenu[i].classList.add("hidden");
     }
	}

  });
});

$(document).ready(function() {
  $('i.idtest2').click(function(e) {
    e.preventDefault();
    var submenu = $(e.target).closest('li').children('.sub-menu');

     for (let i = 0; i < submenu.length; i++) {
     	if(submenu[i].classList.contains("hidden"))
	{
     	submenu[i].classList.remove("hidden");
     	submenu[i].classList.add("block");
     	} else {
		submenu[i].classList.remove("block");
     	submenu[i].classList.add("hidden");
     }
	}

  });
});

jQuery(document).ready(function(){
	$("div#menu-mobile, div#menu-mobile-close").click(function() {
			$("#prestablog_menu_cat nav").toggle();
	});
});
