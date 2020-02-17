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

function upgrade_module_6_6_7($module)
{
    $module->unregisterHook('filterCmsContent');
    $module->unregisterHook('filterProductContent');
    $module->unregisterHook('filterCategoryContent');

    return $module->registerHook('actionOutputHTMLBefore');
}
