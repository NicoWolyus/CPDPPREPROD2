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

require_once(dirname(__FILE__).'/../../config/config.inc.php');
//require_once(dirname(__FILE__).'/../../init.php');

if (file_exists(_PS_ROOT_DIR_.'/modules/ntbackupandrestore/classes/ntbrfull.php')) {
    require_once(dirname(__FILE__).'/classes/ntbrfull.php');
} elseif (file_exists(_PS_ROOT_DIR_.'/modules/ntbackupandrestore/classes/ntbrlight.php')) {
    require_once(dirname(__FILE__).'/classes/ntbrlight.php');
} else {
    die('Missing override');
}
