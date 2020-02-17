<?php
include(dirname(__FILE__).'/../../config/config.inc.php');

if (substr(_COOKIE_KEY_, 34, 8) != Tools::getValue('token')) {
    die;
}

ini_set('max_execution_time', 7200);
if (Tools::getValue('product')) {
    Search::indexation(false, (int)(Tools::getValue('product')));
}
