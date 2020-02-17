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

define('CONFIGURE_NTCRON', 'https://ntcron.2n-tech.com/app/configure.php?');

require_once(dirname(__FILE__).'/../autoload.php');

$ntbr = new NtbrChild();
$page = 'save_automation';

if (!Tools::isSubmit('secure_key') || Tools::getValue('secure_key') != $ntbr->secure_key) {
    die($ntbr->l('Your secure key is unvalid', $page));
}

if (!Module::isInstalled($ntbr->name)) {
    die('Your module is not installed');
}

$result = false;
$errors = array();

if (Tools::isSubmit('automation_2nt')
    && Tools::isSubmit('automation_2nt_hours')
    && Tools::isSubmit('automation_2nt_minutes')
    && Tools::isSubmit('id_shop_group')
    && Tools::isSubmit('id_shop')
) {
    $result = true;
    $automation_2nt = (int)(bool)Tools::getValue('automation_2nt');
    $automation_2nt_hours = (int)Tools::getValue('automation_2nt_hours');
    $automation_2nt_minutes = (int)Tools::getValue('automation_2nt_minutes');
    $id_shop_group = Tools::getValue('id_shop_group');
    $id_shop = Tools::getValue('id_shop');

    // If something change
    if ($ntbr->getConfig('NTBR_AUTOMATION_2NT', $id_shop_group, $id_shop) != $automation_2nt
        || $ntbr->getConfig('NTBR_AUTOMATION_2NT_HOURS', $id_shop_group, $id_shop) != $automation_2nt_hours
        || $ntbr->getConfig('NTBR_AUTOMATION_2NT_MINUTES', $id_shop_group, $id_shop) != $automation_2nt_minutes
    ) {
        // Call the 2NT cron url
        $shop_domain = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost();
        $shop_url = $shop_domain.__PS_BASE_URI__;
        $url = CONFIGURE_NTCRON
            .'site='.urlencode($shop_url)
            .'&enable='.$automation_2nt
            .'&h='.$automation_2nt_hours
            .'&m='.$automation_2nt_minutes
            .'&fuseau_h='.date_default_timezone_get()
            .'&securekey='.urlencode($ntbr->secure_key);

        $ntcron_result = Tools::file_get_contents($url);

        $result = ($ntcron_result == 'OK');

        if ($result) {
            // Update with the new values
            $ntbr->setConfig('NTBR_AUTOMATION_2NT', $automation_2nt, $id_shop_group, $id_shop);
            $ntbr->setConfig('NTBR_AUTOMATION_2NT_HOURS', $automation_2nt_hours, $id_shop_group, $id_shop);
            $ntbr->setConfig('NTBR_AUTOMATION_2NT_MINUTES', $automation_2nt_minutes, $id_shop_group, $id_shop);

            if ($automation_2nt) {
                //Is IP already in the list ?
                $shops = Shop::getShops();
                foreach ($shops as $shop) {
                    $ip_list = $ntbr->getConfig('PS_MAINTENANCE_IP', $shop['id_shop_group'], $shop['id_shop']);
                    $array_ip_list = explode(',', $ip_list);

                    if (!in_array(NtbrCore::IPV4_NTCRON, $array_ip_list)) {
                        $array_ip_list[] = NtbrCore::IPV4_NTCRON;
                    }

                    if (!in_array(NtbrCore::IPV6_NTCRON, $array_ip_list)) {
                        $array_ip_list[] = NtbrCore::IPV6_NTCRON;
                    }

                    $new_list = implode(',', $array_ip_list);
                    $ntbr->setConfig('PS_MAINTENANCE_IP', $new_list, $shop['id_shop_group'], $shop['id_shop']);
                }
            }
        }
    }

    if (!$result) {
        $errors[] = $ntbr->l('Error during the saving of your automation.', $page);
    }
}

die(Tools::jsonEncode(array('result' => $result, 'errors' => $errors)));
