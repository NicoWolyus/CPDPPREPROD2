/*
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2018 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */


$(document).ready(function () {
	var data = facingData;
	var facing_feature = data['facingfeature'];
	var show_feature = data['show_feature'];
    showFacingDeclinations(data);

    // show or hide feature
    if(show_feature == 1){
		    $('.product-features .data-sheet dt').each(function() {
            if ($(this).text() == facing_feature) {
				$(this).css("display","none");
                $(this).next().css("display","none");
			}
        });
		$('.table-data-sheet td').each(function() {
            if ($(this).text() == facing_feature) {
				$(this).css("display","none");
                $(this).next().css("display","none");
			}
        });

    }
	$('body').on('change', '.facing-product-variants [data-product-attribute]', function () {
	    $("input[name$='refresh']").click();
	});

});


function showFacingDeclinations(data){

    var group_attribute = data['group'];
	var group_type= data['group_type'];
	var version = data['version'];
    var content='';

    for (var i = 0; i < data['products'].length; i++){
        for (var j = 0; j < data['products'][i].length; j++){
		    if(data['products'][i][j]){
                var attribute = data['products'][i][j]['id_attribute'];
                var color= data['products'][i][j]['color'];
			    var name= data['products'][i][j]['name'];
			    var link= data['products'][i][j]['link'];
			    var img_color= data['products'][i][j]['img_color_exists'];
				if(group_type == 'color'){
                    if(version == "1.7"){
						$('#group_' + group_attribute).empty();
					}else{
				        $('ul#color_to_pick_list').empty();
                    }
					if(data['products'][i][j]['id_product'] == product_id){
                        content+='<li class="float-xs-left input-container selected"><a href="'+link+'" target="_top" data-product-attribute="'+group_attribute+'" style="display: block;height: 22px;width: 22px;cursor: pointer;background-color: '+color+'" title="'+name+'">';
					}else{
                        content+='<li class="float-xs-left input-container"><a href="'+link+'" target="_top" data-product-attribute="'+group_attribute+'" style="display: block;height: 22px;width: 22px;cursor: pointer;background-color: '+color+'" title="'+name+'">';
				    }
					if(img_color == 1){
					    content+='<img src="'+img_col_dir+''+attribute+'.jpg" alt="'+name+'" title="'+name+'" width="20" height="20" />';
					}
					content+='</a></li>';
				}
				if(group_type == 'select'){
				    $('#group_' + group_attribute).empty();
					if(data['products'][i][j]['id_product'] == product_id){
                        content+='<option value="'+attribute+'" url_option="'+link+'" title="'+name+'" selected>'+name+'</option>';
				    }else{
                        content+='<option value="'+attribute+'" url_option="'+link+'" title="'+name+'">'+name+'</option>';
				    }

			    }
				if(group_type == 'radio'){
					if(version == "1.7"){
						$('#group_' + group_attribute).empty();
				    }else{
						$('.attribute_list ul').not($('#color_to_pick_list')).empty();
				    }
				    content+='<li class="float-xs-left input-container"><a href="'+link+'" target="_top" style="display: block;height: 25px;width: 50px;cursor: pointer;border: solid 2px #000; text-align:center;color:#000;"  title="'+name+'">'+name+'</a></li>';
			    }
				//append content
			    if(version == "1.7"){
				    $('#group_' + group_attribute).append(content);
			    }
			    if(group_type == 'color' && version == "1.6"){
					$('ul#color_to_pick_list').append(content);
				}
			    if(group_type == 'select' && version == "1.6"){
				    $('#group_' + group_attribute).append(content);
			    }
				if(group_type == 'radio' && version == "1.6"){
				    $('.attribute_list ul').not($('#color_to_pick_list')).append(content);
			    }
		    }
        }
    }
    if(group_type == 'select'){
        $(document).on('change', 'select[id*=group]', function() {
	        var link = $(this).children(":selected").attr("url_option");
	       // window.location = link;
		   window.open(link, '_top');
        });
    }
    $('.product-variants').removeClass("product-variants").addClass("facing-product-variants");
}


