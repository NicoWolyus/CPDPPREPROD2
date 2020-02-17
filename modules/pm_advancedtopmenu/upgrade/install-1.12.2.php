<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
function upgrade_module_1_12_2($module)
{
    $module->registerHook('actionShopDataDuplication');
    return true;
}
