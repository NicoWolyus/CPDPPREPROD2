 /*
 *    ambBOcustomizer Module : customize the prestashop back-office
 *
 *    @module    BO Customizer (AmbBoCustomizer)
 *    @file      views/js/debug.js
 *    @subject   Manages the standard ambris debug system
 *    @author    Ambris Informatique
 *    @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *    @license   Commercial license
 *
 *    Support by mail: support@ambris.com
 **/

(function($) {
  var compat_jQ = (function() {
    try {
      return amb_jQ;
    } catch (err) {
      return $;
    }
  })();
  (function($) {

$(document).ready(function() {
  $(".get_logs").click(function(){
    $('#logs_container').show();
    $('#use_console_logging').show();
    loadLogs();
  });

  $(".hide_logs").click(function(){
    $('#logs_container').hide();
    $('#use_console_logging').hide();
  });

  $(".delete_logs").click(function(){
    deleteLogs();
  });


  loadLogs = function(){
    $.ajax({
        async: false,
        url: $('#amb_module_config_url').val(),
        data: {
          ajax: '1',
          action: 'GetLog'
        },
        success: function(data) {
          localData = data;
          $('#amb_logs').html(localData);
          $('pre code').each(function(i, block) {
            hljs.highlightBlock(block);
          });
        },
        error: function(xhr, textStatus, errorThrown) {
          console.log(xhr);
        }
      });
  }

  deleteLogs = function(){
    if (confirm('Are you sure you want reset the logs ?')) {
      $.ajax({
          async: false,
          url: $('#amb_module_config_url').val(),
          data: {
            ajax: '1',
            action: 'DeleteLog'
          },
          success: function(data) {
            localData = data;
            loadLogs();
          },
          error: function(xhr, textStatus, errorThrown) {
            console.log(xhr);
          }
        });
    }
  }
});

   })(compat_jQ);
})(jQuery);