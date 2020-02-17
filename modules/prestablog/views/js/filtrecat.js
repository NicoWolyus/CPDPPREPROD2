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

( function($) {
	$(function() {
		var selectedCatFilter = new Object();
		$("div#categoriesFiltrage select[name=SelectCat]").change(function() {
			var keyCat = $(this).val();
			if(keyCat > 0) {
				if(!(keyCat in selectedCatFilter)) {
					selectedCatFilter[ keyCat ] = $("option:selected", this).text().trim();
					$("div#categoriesForFilter").append('<div class="filtrecat" rel="'+keyCat+'">'+$("option:selected", this).text().trim()+'<div class="deleteCat" rel="'+keyCat+'">X</div></div>');
					$("option:selected", this).attr('disabled','disabled');
					$('option:first-child', this).attr("selected", "selected");
				}
			}

			$("#prestablog_input_filtre_cat").html('');
			$("div#categoriesForFilter div.filtrecat").each(function() {
				$("#prestablog_input_filtre_cat").append('<input type="hidden" name="prestablog_search_array_cat[]" value="'+$(this).attr("rel")+'" />');
			});
		});

		$('div#categoriesFiltrage').delegate('div.deleteCat','click',function() {
			var keyCat = $(this).attr('rel');
			$("div#categoriesFiltrage select[name=SelectCat] option[value='"+keyCat+"']").removeAttr('disabled');
			$('div.filtrecat[rel="'+keyCat+'"]').remove();
			delete selectedCatFilter[keyCat];
			$('div#categoriesFiltrage select[name=SelectCat] option:first-child', this).attr("selected", "selected");

			$("#prestablog_input_filtre_cat").html('');
			$("div#categoriesForFilter div.filtrecat").each(function() {
				$("#prestablog_input_filtre_cat").append('<input type="hidden" name="prestablog_search_array_cat[]" value="'+$(this).attr("rel")+'" />');
			});
		});
	});
} ) ( jQuery );
