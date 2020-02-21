/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 * We offer the best and most useful modules PrestaShop and modifications for your online store.
 *
 * @author    knowband.com <support@knowband.com>
 * @copyright 2017 Knowband
 * @license   see file: LICENSE.txt
 * @category  PrestaShop Module
 *
 *
 * Description
 *
 * Updates quantity in the cart
 */

$(document).ready(function() {

    $('#search_filters input[type="checkbox"]').on('change', function(){
        window.location = $(this).attr('data-search-url');
    });

    $('.js-search-filters-clear-all').on('click', function() {
        window.location = $(this).attr('data-search-url');
    });

    $('.filter-block .js-search-link').on('click', function() {
        window.location = $(this).attr('href');
    });

    $('.back-to-top').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 800);
        return false;
    });


    if ($(selector_pagination).length != 0) {
        if (version == 6 && ismobile==0) {
            location.reload;
        }
        $(".back-to-top").mouseover(function() {
            var top_bg_color = ColorLuminance(background_color_top_link, -0.2);
            $(this).css("background", 'url(' + image_url + 'top-link.png) 50% 43% no-repeat ' + top_bg_color);
        });
        $(".back-to-top").mouseout(function() {
            $(this).css("background", 'url(' + image_url + 'top-link.png) 50% 43% no-repeat ' + background_color_top_link);
        });
//        $('.page-list li a.next').parent().addClass('pagination_next');
        endlessScroll(0, true, 100);
//        $('nav.pagination').hide();
    }

     $('.pagination').bind("DOMSubtreeModified", function() {
//         console.log('sdfsf');
//        if ($(selector_next).hasClass('disabled')) {
//            $(selector_next).replaceWith($(selector_next).text());
//        }
//       if ($(selector_next).is(":disabled")) {
//           $('#vss_buy_now').prop('disabled', true);
//       } else {
//           $('#vss_buy_now').prop('disabled', false);
//       }
//       $('.product-additional-info #vss_occ_buy_now_block').remove();
   });
   if ($(selector_next).hasClass('disabled')) {
        $(selector_next).replaceWith($(selector_next).text());
    }


//
});

function endlessScroll(pageno, trigger_event, scroll) {
    var endlessscrollias = jQuery.ias({
        item: selector_item,
        container: selector_container,
        next: selector_next,
        pagination: selector_pagination,
        negativeMargin: 150
    });
    var ispage = endlessscrollias.extension(new IASPagingExtension());
    if (display_loading_message == 1) {
        endlessscrollias.extension(new IASSpinnerExtension({
            src: image_url + 'loader.gif',
            html: "<div class='ias-spinner' style='text-align: center;'><img style='display:inline' src='{src}'/></div><div class='ias-spinner' style='text-align:center'>" + loading_message + "</div>",
        }));
    }
    if (display_end_message == 1) {
        endlessscrollias.extension(new IASNoneLeftExtension({
            text: end_page_message,
            html: '<div class="ias-noneleft message-box" style="text-align: center;">{text}</div>'
        }));
    }
    if (trigger_event) {
        if (scroll_type == 1) {
            endlessscrollias.extension(new IASTriggerExtension({
                text: load_more_link_page,
                html: '<div  class="ias-trigger ias-trigger-next vss-more-products" style="display: block;"><img class="cross-more" src="/themes/classic-child/assets/img/crosswhite.png"><div class="inner-text">{text}</div></div>',
                offset: load_more_link_frequency,
            }));
        }
    }
    var previous_url = null;
        endlessscrollias.getNextUrl = function(container) {
        if (!container) {
            container = endlessscrollias.$container;
        }
        var nexturl = $(endlessscrollias.nextSelector, container).attr('href');
        console.log(nexturl);
        if (typeof nexturl !== "undefined") {
//            nexturl = nexturl.replace("#/page-", "?p=");
            if (window.location.protocol == 'https:') {
                nexturl = nexturl.replace('http:', window.location.protocol);
            } else {
                nexturl = nexturl.replace('https:', window.location.protocol);
            }
        }
        if (previous_url == nexturl) {
            return $(endlessscrollias.nextSelector, container).attr('dsd');
        }
        previous_url = nexturl;

        return nexturl;
    };
//    }
    var amountScrolled = 600;
    $(window).scroll(function() {
        if ($(window).scrollTop() > amountScrolled) {
            $('a.back-to-top').fadeIn('slow');
        } else {
            $('a.back-to-top').fadeOut('slow');
        }
    });
}

$(document).ajaxComplete(function(event, xhr, settings) {
    
    // changes by rishabh jain
    var url = settings.url;
    var param1 = url.split('?');
    if (typeof param1[1] != 'undefined') {
        var param2 = param1[1];
        if (param2.indexOf('&') != -1) {
            var param3 = param2.split('&');
            if (typeof param3[0] != 'undefined' && typeof param3[1] != 'undefined') {
                if (param3[0].indexOf('=') != -1 && param3[1].indexOf('page') <= -1) {
                    var param4 = param3[0].split('=');
                    if (param4[0] === "order") {
                        var is_load = true;
                        if (param3[1].indexOf('=') != -1) {
                            var param5 = param3[1].split('=');
                            if (typeof param5[0] != 'undefined') {
                                if (param5[0] == 'q') {
                                    is_load = false;
                                }
                            }
                        }
                        if (is_load) {
                            location.reload();
                        }
                    } else if (param3[1].indexOf('=') != -1) {
                        var param6 = param3[1].split('=');
                        if (param6[0] === 'order') {
                            var is_load = true;
                            if (typeof param3[2] != 'undefined') {
                                if (param3[2].indexOf('page') != -1) {
                                    is_load = false;
                                }
                            }
                            if (is_load) {
                                location.reload();
                            }
                        }
                    }
                }
            }
        }

    }
    // changes over
});

function removeURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts = url.split('?');
    if (urlparts.length >= 2) {

        var prefix = encodeURIComponent(parameter) + '=';
        var pars = urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i = pars.length; i-- > 0; ) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                return pars[i];
            }
        }
    }
}
function ColorLuminance(hex, lum) {

    // validate hex string
    hex = String(hex).replace(/[^0-9a-f]/gi, '');
    if (hex.length < 6) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    lum = lum || 0;

    // convert to decimal and change luminosity
    var rgb = "#", c, i;
    for (i = 0; i < 3; i++) {
        c = parseInt(hex.substr(i * 2, 2), 16);
        c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
        rgb += ("00" + c).substr(c.length);
    }

    return rgb;
}