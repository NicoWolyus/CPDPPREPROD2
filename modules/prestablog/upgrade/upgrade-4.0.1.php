<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_0_1($module)
{
    if (!Configuration::get($module->name.'_layout_blog')) {
        Configuration::updateValue('prestablog_layout_blog', 2);
    }
    if (!Configuration::get($module->name.'_lb_title_length')) {
        Configuration::updateValue('prestablog_lb_title_length', 80);
    }
    if (!Configuration::get($module->name.'_lb_intro_length')) {
        Configuration::updateValue('prestablog_lb_intro_length', 120);
    }

    $module->deleteAdminTab();
    $module->registerAdminTab();

    Tools::clearCache();

    return true;
}
