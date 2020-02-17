<?php
/**
 * Creative Slider - Responsive Slideshow Module
 * https://creativeslider.webshopworks.com
 *
 * @author    WebshopWorks <info@webshopworks.com>
 * @copyright 2015-2019 WebshopWorks
 * @license   One Domain Licence
 *
 * Not allowed to resell or redistribute this software
 */

defined('_PS_VERSION_') or exit;
?>
<script type="text/html" id="ls-static-layer-item-template">
    <li>
        <a href="#" class="dashicons dashicons-redo ls-icon-jump" data-help="<?php ls_e('Click this icon to jump to the slide where this layer was added on, so you can quickly edit its settings.', 'LayerSlider') ?>"></a>
        <div class="ls-sublayer-thumb"></div>
        <span class="ls-sublayer-title"><?php echo sprintf(ls__('Layer #%d', 'LayerSlider'), '1') ?></span>
    </li>
</script>
