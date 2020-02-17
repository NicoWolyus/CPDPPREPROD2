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
 $('.fancybox').fancybox({
padding: 0,
  helpers: {
    overlay: {
      locked: false
    }
  }
});
$(document).ready(function(){

   $('#prestablogfont a img').addClass('anti-fancybox');

   $('#prestablogfont img').not(".anti-fancybox").each(function() {
   	if ($(this).closest('.ls-wp-container').length) return;
       $(this).wrap('<a class="fancybox" href="'+$(this).attr('src')+'" data-fancybox-group="prestablog-news-'+$('#prestablog_article').data('referenceid')+'"></a>');
       $(this).addClass('img-responsive');
   });

   $('#prestablogfont a.fancybox').fancybox();

});
