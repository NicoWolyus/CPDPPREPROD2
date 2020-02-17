<?php
/**
* 2013-2019 2N Technologies
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@2n-tech.com so we can send you a copy immediately.
*
* @author    2N Technologies <contact@2n-tech.com>
* @copyright 2013-2019 2N Technologies
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

require_once(dirname(__FILE__).'/../autoload.php');

$ntbr = new NtbrChild();
$page = 'delete';

/*d($ntbr->secure_key);*/

if (!Tools::isSubmit('secure_key') || Tools::getValue('secure_key') != $ntbr->secure_key) {
    die($ntbr->l('Your secure key is unvalid', $page));
}

if (!Module::isInstalled($ntbr->name)) {
    die('Your module is not installed');
}

if (!Tools::isSubmit('nb')) {
    die(Tools::jsonEncode(array('result' => false)));
}

$result = ($ntbr->deleteThisBackup(Tools::getValue('nb')));

die(Tools::jsonEncode(array('result' => $result)));
