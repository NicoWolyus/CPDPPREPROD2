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

<!-- Module Prestablog -->
<div class="modal fade popup-content" id="popup-content-{$id_lang|intval}" data-delay="{$Popup->delay|intval}">
  <div style="max-width:{$Popup->width|intval}px;">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {PopupContent return=$Popup->content}

  </div>
</div>
<!-- /Module Prestablog -->
