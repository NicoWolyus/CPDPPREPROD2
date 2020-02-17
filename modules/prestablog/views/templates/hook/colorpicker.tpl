{**
 * 2008 - 2017 (c) Prestablog
 *
 * MODULE Prestablog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 *}

<script type="text/javascript">

var rgb = "{$Popup->pop_colorpicker_content}";
var str = rgb.replace("rgb", "rgba");
var res = str.replace(")", ", {$Popup->pop_opacity_content})");

var mod = "{$Popup->pop_colorpicker_modal}";
var str_mod = mod.replace("rgb", "rgba");
var res_mod = str_mod.replace(")", ", {$Popup->pop_opacity_modal})");

</script>
<!-- Module Prestablog -->
<div class="modal fade popup-content" id="popup-content-{$id_lang|intval}" data-delay="{$Popup->delay|intval}">

	<script>
$("#popup-content-{$id_lang|intval}").css('background-color', res);
	</script>
  <div class="modal-dialog" style="width:{$Popup->width|intval}px; ">

    <div class="modal-content">
    		<script>

$(".modal-content").css('background-color', res_mod);
	</script>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        {if $Popup->title != ''}<h4 class="modal-title">{$Popup->title|escape:'htmlall':'UTF-8'}</h4>{/if}
      </div>
      <div class="modal-body" style="width:{$Popup->width|intval}px;height:{$Popup->height|intval}px;">
        {PopupContent return=$Popup->content}
      </div>
      {if $Popup->footer}
      <div class="modal-footer">
        <button type="button" class="btn btn-default" style="background-color:{$Popup->pop_colorpicker_btn}; border: {$Popup->pop_colorpicker_btn_border}; opacity: {$Popup->pop_opacity_btn};" data-dismiss="modal">{l s='Close' mod='prestablog'}</button>
      </div>
      {/if}
    </div>
  </div>
</div>
<!-- /Module Prestablog -->
