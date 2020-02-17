<?php
/**
 *   AmbJoliSearch Module : Search for prestashop
 *
 *    @author    Ambris Informatique
 *    @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *    @license   Commercial license
 *    @module     Advanced Search (AmbJoliSearch)
 *    @file        jolilink.php
 *    @subject     core class Link decorator
 *    Support by mail: support@ambris.com
 */

class JoliLinkCore extends Link
{

    /**
     * Constructor (initialization only)
     */
    public function __construct(Link $link)
    {
        parent::__construct($link->protocol_link, $link->protocol_content);
    }

    public function getPaginationLink(
        $type,
        $id_object,
        $nb = false,
        $sort = false,
        $pagination = false,
        $array = false
    ) {
        $controller = Dispatcher::getInstance()->getController();
        if (in_array($controller, array('jolisearch'))) {
            $link = parent::getPaginationLink('Ambjolisearch', $controller, $nb, $sort, $pagination, $array);
        } else {
            $link = parent::getPaginationLink($type, $id_object, $nb, $sort, $pagination, $array);
        }

        if (is_array($link)) {
            $link['controller'] = $controller;
            return $link;
        } else {
            $vars = explode('&', $link);
            $first_elts = explode('?', $vars[0]);

            $correct_link = $first_elts[0]
            . '?'
            . implode('&', array_slice($first_elts, 1))
            . (count($vars) > 1 ? '&' : '')
            . implode('&', array_slice($vars, 1));

            return $correct_link;
        }
    }

    protected function myGetModuleLink(
        $module,
        $controller = 'default',
        array $params = array(),
        $ssl = null,
        $id_lang = null,
        $id_shop = null
    ) {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }

        if (is_null($id_shop)) {
            $base = ((($ssl === null || $ssl === true) && $this->ssl_enable) ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_);
            $url = $base . __PS_BASE_URI__ . $this->getLangLink($id_lang);
        } else {
            $url = $this->getBaseLink($id_shop, $ssl) . $this->getLangLink($id_lang, null, $id_shop);
        }

        // If the module has its own route ... just use it !
        if (Dispatcher::getInstance()->hasRoute('module-' . $module . '-' . $controller, $id_lang)) {
            return $this->getPageLink('module-' . $module . '-' . $controller, $ssl, $id_lang, $params);
        } else {
            $params['module'] = $module;
            $params['controller'] = $controller ? $controller : 'default';
            return $url . Dispatcher::getInstance()->createUrl('module', $id_lang, $params, $this->allow);
        }
    }

    public function isUrlRewriting()
    {
        return $this->allow;
    }
}
