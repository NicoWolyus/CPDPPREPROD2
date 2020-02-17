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
$(function(){
    $('#blog_list_1-7 a.comments').each( function( i ) {
        var mark = $(this);
        $.getJSON("https://graph.facebook.com/?id="+mark.data('commentsurl')+"&fields=og_object{engagement}", function( json ) {
            if ( json.og_object.engagement.count) {
                mark.html('<i class="material-icons">comment</i> ' + json.og_object.engagement.count);
            } else {
                mark.html('<i class="material-icons">comment</i> ' + 0);
            }
        });
    });
});



$(function(){
    $('#blog_list a.comments').each( function( i ) {
        var mark = $(this);
        $.getJSON("https://graph.facebook.com/?id="+mark.data('commentsurl')+"&fields=og_object{engagement}", function( json ) {
            if ( json.og_object.engagement.count) {
                mark.html('<i class="material-icons">comment</i> ' + json.og_object.engagement.count);
            } else {
                mark.html('<i class="material-icons">comment</i> ' + 0);
            }
        });
    });
});
