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

function upgrade_module_4_1_1()
{
    Tools::clearCache();

    return true;
}
