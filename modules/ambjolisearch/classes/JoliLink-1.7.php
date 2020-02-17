<?php
/**
 *   AmbJoliSearch Module : Search for prestashop
 *
 *    @author    Ambris Informatique
 *    @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *    @license   Commercial license
 *    @module     Advanced Search (AmbJoliSearch)
 *    @file        jolilink161.php
 *    @subject     core class Link decorator
 *    Support by mail: support@ambris.com
 */

class JoliLink extends JoliLinkCore
{
    public function getModuleLink(
        $module,
        $controller = 'default',
        array $params = array(),
        $ssl = null,
        $id_lang = null,
        $id_shop = null,
        $relative_protocol = false
    ) {
        return $this->myGetModuleLink($module, $controller, $params, $ssl, $id_lang, $id_shop, $relative_protocol);
    }
}
