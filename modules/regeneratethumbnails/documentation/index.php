<?php
/**
 * Display module documentation
 *
 * @category  Prestashop
 * @category  Module
 * @author    Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license   commercial license see license.txt
 * @version   1.0.0
 */

/* Get user language */
if (isset($_GET['setlang'])) {
    $iso_lang = preg_replace('/[^a-z]/', '', $_GET['setlang']);
} else {
    $iso_lang = false;
}

if (!$iso_lang) {
    $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $iso_lang = strtolower(substr(trim($languages[0]), 0, 2));
}
/* Display documentation */
if ($iso_lang) {
    // don't use glob() it can be blocked for "security reason"
    $files = array_diff(scandir(dirname(__FILE__)), array('..', '.'));
    foreach ($files as $filename) {
        if ($filename == $iso_lang.'.html') {
            include($filename);
            die();
        }
    }
}
if (file_exists('en.html')) {
    include('en.html');
}
