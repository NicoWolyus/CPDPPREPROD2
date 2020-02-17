/*
 *  (c) 2013-2015 Ambris Informatique
 *   @module         Advanced search (AmbJoliSearch)
 *  @file       ambjolisearch.php
 *  @subject    script principal pour gestion du module (install/config/hook)
 *  @copyright  Copyright (c) 2013 Ambris Informatique SARL
 *  @license    Commercial license
 *  Support by mail: support@ambris.com
 **/

var _gaq = _gaq || [];
(function(d, t) {
    var g = d.createElement(t),
        s = d.getElementsByTagName(t)[0];
    g.async = 1;
    g.src = ("https:" == location.protocol ? "//ssl" : "//www") + ".google-analytics.com/ga.js";
    s.parentNode.insertBefore(g, s)
}(document, "script"));

if (!window.console) {
    window.console = {
        log: function() {}
    };
}

(function($){
    var compat_jQ = (function(){ try { return amb_jQ; } catch(err){ return $; } })();
    (function($) {
        var customizeRender = function() {
            var __superRenderItem = $.ui.autocomplete.prototype._renderItem;

            $.widget('ambjolisearch.ambSearchAutocomplete', $.ui.autocomplete, {
                _renderItem: function(ul, item) {
                    if (this.options.customRender) {
                        return $("<li></li>")
                        .data("item.autocomplete", item)
                        .addClass((item.title ? 'jolisearch-container' : ''))
                        .append(item.title ? $("<span>").addClass("jolisearch-title").html(item.title) : '')
                        .append($("<a></a>").attr('href', (item.data && item.data.link ? item.data.link : ""))[this.options.html ? "html" : "text"](item.label).addClass(item.data ? item.data.type : ""))
                        .appendTo(ul);
                    } else {
                        return __superRenderItem.call(this, ul, item);
                    }
                }
            });
        };


        var matchAccents = function(s) {
                // http://jsfiddle.net/uJ99L/4/
                var accents = {
                    a: new Array('à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'Â', 'Ã', 'Ä', 'À', 'Á', 'Å', 'Æ'),
                    e: new Array('é', 'è', 'ê', 'ë', 'É', 'È', 'Ë', 'Ê'),
                    i: new Array('ì', 'í', 'î', 'ï', 'Ì', 'Í', 'Î','Ï'),
                    o: new Array('ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø'),
                    u: new Array('ù', 'ú', 'û', 'ü', 'Ù', 'Ú', 'Û', 'Ü'),
                    n: new Array('ñ', 'Ñ'),
                    c: new Array('ç', 'Ç')
                };
                for (var key in accents) {
                    var reg = "[" + key;
                    var search_term = "(" + key;

                    for (var letterindex in accents[key]) {
                        reg += accents[key][letterindex];
                        search_term += '|' + accents[key][letterindex];
                    }

                    reg += "]";
                    search_term += ")";
                    var regexp = new RegExp(search_term, "g");
                    $('#term').append("--> " + reg + " - " + search_term + " --> ");
                    s = s.replace(regexp, reg);
                    $('#term').append(s + "\n");
                }
                return s;
            },

            builder = {
                'product': function(item, filter, firstOfItsKind) {
                    var img = $('<img src="' + item.img + '">')
                        .addClass("jolisearch-image"),
                    prod = $('<span>').addClass('jolisearch-name').html(filter(item.pname)),
                    supplier = item.supname ? $('<span>').addClass('jolisearch-pre').html(filter(item.supname)) : undefined,
                    manuf = item.mname ? $('<span>').addClass('jolisearch-pre').html(filter(item.mname)) : undefined,
                    cat = item.cname ? $('<span>').addClass('jolisearch-post').html(filter(item.cname)) : undefined,
                    features = item.feats ? $('<span>').addClass('jolisearch-features').html(filter(item.feats)) : undefined,
                    prc = item.price ? $('<div>').addClass('jolisearch-post-right').html(item.price) : undefined,
                    dummy = $('<div>').addClass('jolisearch-item product')
                        .append(img)
                        .append(
                            $('<div>').addClass("jolisearch-description " + item.type)
                                .append(supplier)
                                .append(manuf)
                                .append(prod)
                                .append(features)
                                .append(cat)
                        )
                        .append(prc);

                    return {
                        data: item,
                        value: item.pname,
                        label: dummy.html(),
                        title: firstOfItsKind
                    };
                },

                'manufacturer': function(item, filter, firstOfItsKind) {
                    var img = $('<img src="' + item.img + '">')
                        .addClass("jolisearch-image"),
                    prod = $('<span>').addClass('jolisearch-name').html(filter(item.man_name)),
                    dummy = $('<div>').addClass('jolisearch-item manufacturer')
                        .append(img)
                        .append(
                            $('<div>').addClass("jolisearch-description " + item.type)
                                .append(prod));

                    return {
                        data: item,
                        value: item.man_name,
                        label: dummy.html(),
                        title: firstOfItsKind
                    };

                },

                'category': function(item, filter, firstOfItsKind) {
                    var img = $('<img src="' + item.img + '">')
                        .addClass("jolisearch-image"),
                    prod = $('<span>').addClass('jolisearch-name').html(filter(item.cat_name)),
                    dummy = $('<div>').addClass('jolisearch-item category')
                        .append(img)
                        .append(
                            $('<div>').addClass("jolisearch-description " + item.type)
                                .append(prod));

                    return {
                        data: item,
                        value: item.cat_name,
                        label: dummy.html(),
                        title: firstOfItsKind
                    };
                },

                'no-results-found': function(item, filter, firstOfItsKind) {
                    var message = $('<span>').addClass('jolisearch-post').html(firstOfItsKind),
                        dummy = $('<div>').addClass('jolisearch-item no-results-found')
                            .append(
                                $('<div>').addClass("jolisearch-description " + item.type)
                                    .append(message));
                    return {
                        value: '',
                        label: dummy.html()
                    };
                },

                'more-results': function(item, filter, firstOfItsKind) {
                    var message = $('<span>').addClass('jolisearch-post').html(firstOfItsKind),
                        dummy = $('<div>').addClass('jolisearch-item no-results-found')
                            .append(
                                $('<div>').addClass("jolisearch-additionnal " + item.type)
                                    .append(message));
                    return {
                        value: '',
                        data: item,
                        label: dummy.html()
                    };
                }
            },

            filterClosure = function(term) {
                var matcher = new RegExp("(" + matchAccents($.ui.autocomplete.escapeRegex(term)) + ")", "gi");
                return function(data) {
                    return data.replace(matcher, '<strong>$1</strong>');
                }
            },
            sourceClosure = function(that) {
                return function(request, response) {
                    var filter = filterClosure(request.term);
                    $.ajax({
                        url: that.attr('data-autocomplete'),
                        dataType: "json",
                        data: {
                            s: request.term,
                            //ajaxSearch: 1,
                            ajax: true,
                            id_lang: that.attr('data-lang'),
                            maxRows: that.attr('data-autocomplete-max') || 10
                        },

                        success: function(data) {
                            var lastType = undefined;
                            response($.map(data, function(item) {
                                var firstOfItsKind = (lastType !== item.type);
                                lastType = item.type;
                                if (item.type == "no-results-found") {
                                    if (that.attr('data-ga-acc') != 0) {
                                        _gaq.push(["_setAccount", that.attr('data-ga-acc')]);
                                        _gaq.push(['_trackPageview', '/notfound?search_query=' + request.term + '&fast_search=fs']);
                                    }
                                }

                                return builder[item.type](item, filter, (firstOfItsKind ? that.attr('data-' + item.type) : false));
                            }));
                        },

                        error: function(xhr, textStatus, errorThrown) {
                            console.log("error: " + errorThrown);
                        }
                    });
                }
            };

        $('document').ready(function() {

            if (typeof(jolisearch) != 'undefined') {

                input = $('.jolisearch').find('input')
                if(input.length == 0){
                    input = $('input[name=s]');
                }

                input
                    .attr('data-autocomplete-mode', jolisearch['use_autocomplete'])
                    .attr('data-autocomplete', jolisearch['amb_joli_search_action'])
                    .attr('data-lang', jolisearch['id_lang'])
                    .attr('data-manufacturer', jolisearch['l_manufacturers'])
                    .attr('data-product', jolisearch['l_products'])
                    .attr('data-category', jolisearch['l_categories'])
                    .attr('data-minwordlen', jolisearch['minwordlen'])
                    .attr('data-no-results-found', jolisearch['l_no_results_found'])
                    .attr('data-more-results', jolisearch['l_more_results'])
                input
                    .closest('form').attr('action', jolisearch['amb_joli_search_action']);
                input
                    .closest('form').find('input[name=controller]').remove();

                $('#search_widget').attr('data-search-controller-url', '');

/*
                if (typeof $.fn.psBlockSearchAutocomplete !== 'undefined'){
                    $('#search_widget').find('input[type=text]').psBlockSearchAutocomplete('destroy');
                }
                if (typeof prestashop.psBlockSearchAutocomplete !== 'undefined'){
                    $('#search_widget').find('input[type=text]').psBlockSearchAutocomplete('destroy');
                }
*/
                if ($.fn.version >= '2') {
                    var searchbarAutocomplete = $('*:data(prestashop-psBlockSearchAutocomplete)');
                    if (searchbarAutocomplete.length) {
                        searchbarAutocomplete.off('.psBlockSearchAutocomplete0');
                        searchbarAutocomplete.off('.psBlockSearchAutocomplete2');
                        searchbarAutocomplete.psBlockSearchAutocomplete('destroy');
                    }
                    // add support for warehouse theme and its child themes
                    var scAutocomplete = $('input[name=s]:data(sc)');
                    if (scAutocomplete.length && typeof $.fn.autoComplete !== 'undefined') {
                        scAutocomplete.autoComplete('destroy');
                    }
                } else {
                    var searchbarAutocomplete = $('input[name=s]');
                    searchbarAutocomplete.each(function(i, elt) {
                        var e = $(elt);
                        if (e.data('prestashop-psBlockSearchAutocomplete') || e.data('prestashopPsBlockSearchAutocomplete')) {
                            e.off('.psBlockSearchAutocomplete0');
                            e.off('.psBlockSearchAutocomplete2');
                            e.psBlockSearchAutocomplete('destroy');
                        }
                        // add support for warehouse theme and its child themes
                        if (e.data('sc') && typeof $.fn.autoComplete !== 'undefined') {
                            e.autoComplete('destroy');
                        }
                    });
                }

            }

            var responders = $('*:input[type=text][data-autocomplete]');
            customizeRender();
            responders.each(function() {
                var that = $(this);
                var mode = that.data('autocomplete-mode');
                    if (mode == 2 || (mode == 1 && window.matchMedia("(min-width: 768px)").matches)) {

                    that.ambSearchAutocomplete({
                            source: sourceClosure(that),
                            minLength: that.data('minwordlen'),
                            max: 10,
                            width: 500,
                            delay: 500,
                            selectFirst: false,
                            scroll: false,
                            html: true,
                            customRender: true,
                            position: (that.data('position') !== undefined ? that.data('position') : { my : "right top", at: "right bottom" }),
                            select: function(e, ui) {
                                if (ui.item.data)
                                    document.location.href = ui.item.data.link;
                                else
                                    return false;
                            }
                    });
                    that.on('focus', function() {
                        that.ambSearchAutocomplete('search', that.val())
                    });

                }
            });
        })
    })(compat_jQ);
})(jQuery);