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

    $(document).on('click', '.quick-view', function(e){
        var id_product = $(this).closest('.product-miniature').attr('data-id-product');

        $.ajax({
			type: 'POST',
			url: baseDir + 'modules/facingsupermodelcombinations/facing.php?id_product_facing=' + id_product,
			headers: { "cache-control": "no-cache" },
			data: 'data-facing=true' ,
			async: true,
			cache: false,
			dataType: 'json',
			success: function(jsonData)
			{
				if (jsonData)
				{
					var facingdata = jsonData.facingdata;
					setTimeout(
                        function() {
                            showFacingDeclinations(facingdata,id_product);
                        },1000
				    );
				}
			}
	    });

	});
});

function showFacingDeclinations(data,id_product){

    var group_attribute = data['group'];
	var group_type= data['group_type'];
    var title = '<span class="control-label">'+info_declinations+'</span>';
    var content = '';
    for (var i = 0; i < data['products'].length; i++){
        for (var j = 0; j < data['products'][i].length; j++){
		    if(data['products'][i][j]){
                var attribute = data['products'][i][j]['id_attribute'];
                var color= data['products'][i][j]['color'];
			    var name= data['products'][i][j]['name'];
			    var link= data['products'][i][j]['link'];
			    var img_color= data['products'][i][j]['img_color_exists'];
				if(data['products'][i][j]['id_product'] !== id_product){
					/* atribute color */
				    if(group_type == 'color'){
                        content+='<li class="float-xs-left input-container"><a data-product-attribute="'+group_attribute+'" style="display: block;height: 22px;width: 22px;cursor: pointer;background-color: '+color+'" href="'+link+'"   title="'+name+'">';
					    if(img_color == 1){
						    content+='<img src="'+img_col_dir+''+attribute+'.jpg" alt="'+name+'" title="'+name+'" width="20" height="20" />';
					    }
					    content+='</a></li>';
				    }
					/* atribute select */
			        if(group_type == 'select'){
                        content+='<option value="'+attribute+'" url_option="'+link+'" title="'+name+'">'+name+'</option>';
			        }
					/* atribute radio */
				    if(group_type == 'radio'){
				        content+='<li class="float-xs-left input-container"><a href="'+link+'" style="display: block;height: 25px;width: 50px;cursor: pointer;border: solid 2px #000; text-align:center;color:#000;"  title="'+name+'">'+name+'</a></li>';
			        }

				}
		    }
		}
    }
	//append content
	if(group_type == 'color'){
		$(title + '<div class="clearfix product-variants-item" style="margin-bottom:10px;"><ul>'+content+'</ul></div>').insertBefore(".product-add-to-cart");
	}
	if(group_type == 'radio'){
		$('.product-variants').append(title + '<div class="clearfix product-variants-item" style="margin-bottom:10px;"><ul>'+content+'</ul></div>');
	$(title + '<div class="clearfix product-variants-item"><ul>'+content+'</ul></div>').insertBefore(".product-add-to-cart");

	}
	if(group_type == 'select'){
		$(title + '<div class="clearfix product-variants-item" style="margin-bottom:10px;"><select class="clearfix form-control form-control-select" id="select-facing"  style="background-color: #fff;width: auto;padding-right: 1.875rem;">'+content+'</select></div>').insertBefore(".product-add-to-cart");

	    $(document).on('change', 'select[id*=select-facing]', function() {
	        var link = $(this).children(":selected").attr("url_option");
	        window.location = link;
        });
	}
}


