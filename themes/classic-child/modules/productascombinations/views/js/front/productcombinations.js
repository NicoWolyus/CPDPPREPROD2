/**
 * Modulo Product Combinations
 *
 * @author    Giuseppe Tripiciano <admin@areaunix.org>
 * @copyright Copyright (c) 2018 Giuseppe Tripiciano
 * @license   You cannot redistribute or resell this code.
 */

$(document).ready(function(e) {
    try {
        $(document).on('click', '.bordobollino', function(e){
            e.preventDefault();
            var url = $(this).data('url');
            $.ajax({
                type: "GET",
                url: url,
                data: { },
                cache: false,
                success: function(data){
                    $('#content-wrapper').html($(data).find('#content-wrapper').html());
                    $('.breadcrumb').html($(data).find('.breadcrumb').html());
                    document.title = $(data).filter("title").text();
                    if (typeof $("#quantity_wanted").TouchSpin === "function") { 
                        $("#quantity_wanted").TouchSpin({
                            verticalbuttons: !0,
                            verticalupclass: "material-icons touchspin-up",
                            verticaldownclass: "material-icons touchspin-down",
                            buttondown_class: "btn btn-touchspin js-touchspin",
                            buttonup_class: "btn btn-touchspin js-touchspin",
                            min: 1,
                            max: 1e6
                        });
                    }
                    if (typeof innitSlickandZoom === "function") { 
                        innitSlickandZoom();
                    }
                },
                complete: function() {
                    bollinicomplete(url);
                }
            });
        });
    } catch(e) {
    }

    function bollinicomplete(url) {
        if (window.location.href != url) {
            window.history.pushState(null, url, url);
        }
    };

    (function($) {
        window.addEventListener('popstate', function(event) {
            var href = window.location.href;
            $("*[data-url='"+href+"']").trigger("click");
        }, false);
    })(jQuery);
});