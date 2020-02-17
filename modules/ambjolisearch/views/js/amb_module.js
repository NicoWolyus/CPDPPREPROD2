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
  $(".amb_switch").change(function(){
     element = $(this);
     $.ajax({
        async: false,
        url: $('#amb_module_config_url').val(),
        data: {
          value: element.val(),
          ajax: '1',
          action: 'Switch',
          configuration: element.data('configuration')
        },
        success: function(data) {
          localData = data;
          showSuccessMessage(update_success_msg);
        },
        error: function(xhr, textStatus, errorThrown) {
          console.log(xhr);
        }
      });
  });

  $("button[data-amb-action]").click(function(){
    $.ajax({
      async: false,
      url: $('#amb_module_config_url').val(),
      data: {
        ajax: '1',
        action: $(this).data('amb-action')
      },
      success: function(data) {
        localData = data;
        showSuccessMessage(update_success_msg);
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(xhr);
      }
    });
  });
});


  })(compat_jQ);
})(jQuery);