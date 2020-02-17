<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from MigrationPro
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the MigrationPro is strictly forbidden.
 * In order to obtain a license, please contact us: contact@migration-pro.com
 *
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe MigrationPro
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la MigrationPro est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la MigrationPro a l'adresse: contact@migration-pro.com
 *
 * @author    MigrationPro
 * @copyright Copyright (c) 2012-2019 MigrationPro
 * @license   Commercial license
 * @package   MigrationPro: Prestashop Upgrade and Migrate tool
 */

class MigrationProMappingCreator
{
    private $sourceData;
    private $mapping = array();
    private $mapping_data_texts = array();
    // Set true if keep source shop ids
    private $keep_source_shop_id = false;

    /**
     * Clear data from shop, currencies, order status and customer groups and create like source data
     */
    public function __construct($sourceData)
    {
        $this->sourceData = $sourceData;
    }

    /**
     * Start auto mappaing
     */
    public function startAutoMapping()
    {
        if (count($this->sourceData["shops"] > 0)) {
            //Check and create shops
            $this->checkAndCreateShops();
            //Check and create languages
            $this->installLanguages();
            // Clear and create order states
            $this->createOrderState();
            // Clear and create currencies
            $this->crateCurrencies();
            // Clear and create customer groups
            $this->createCustomerGroups();
        }
    }

    /**
     * Return auto mapped data
     */
    public function getMappingData()
    {
        return $this->mapping;
    }

    /**
     * Return source data text
     * @param string $map_type Mapping type
     * @param int $map_id Mapping identity
     */
    public function getMappingText($map_type, $map_id)
    {
        return $this->mapping_data_texts[$map_type][$map_id];
    }

    /**
     * Check in target server exists source shop datas, if will not create
     */
    private function checkAndCreateShops()
    {
        $shops = $this->sourceData["shops"];
        if (count($shops) == 1) {
            $this->mapping["multi_shops"][$shops[0]['id_shop']] = Configuration::get('PS_SHOP_DEFAULT');
            $this->mapping_data_texts["multi_shops"][0][$shops['id_shop']] = $shops[0]['name'];
            return;
        }
        $shop_groups = $this->sourceData["shops_group"];
        // check & create shop group
        foreach ($shop_groups as $shop_group) {
            $shop_group_id = 0;
            $shop_group_id = ShopGroupCore::getIdByName($shop_group['name']);
            if ($shop_group_id == 0) {
                $shop_group_ids = self::createShopGroup($shop_group['name']);
            }
        }
        // check & create shop
        $shopCount = 0;
        foreach ($shops as $shop) {
            $shop_id = 0;
            $shop_physical_uri = '';
            $shop_virtual_uri = '';
            $shop_group_name = '';
            $shop_id = Shop::getIdByName($shop['name']);
            foreach ($shop_groups as $shop_group) {
                if ($shop_group['id_shop_group'] == $shop['id_shop_group']) {
                    $shop_group_id = ShopGroupCore::getIdByName($shop_group['name']);
                    $shop_group_name = $shop_group['name'];
                }
            }

            if (!$shop_id) {
                if (count($this->sourceData["shops_urls"]) > 0) {
                    foreach ($this->sourceData["shops_urls"] as &$source_shop_url) {
                        if ($source_shop_url["id_shop"] == $shop['id_shop']) {
                            $shop_physical_uri = $source_shop_url["physical_uri"];
                            $shop_virtual_uri = $source_shop_url["virtual_uri"];
                        }
                    }
                }
                if (!$this->keep_source_shop_id) {
                    $this->createShop($shop['name'], $shop_group_id, $shop_group_name, $shop_physical_uri, $shop_virtual_uri);
                } else {
                    $this->createShop($shop['name'], $shop_group_id, $shop_group_name, $shop_physical_uri, $shop_virtual_uri, $shop['id_shop']);
                }
            }
            $shop_id = Shop::getIdByName($shop['name']);

            //Set mapping data values
            if ($this->keep_source_shop_id) {
                $this->mapping["multi_shops"][$shop['id_shop']] = $shop['id_shop'];
            } else {
                $this->mapping["multi_shops"][$shop['id_shop']] = $shop_id;
            }

            $this->mapping_data_texts["multi_shops"][$shop['id_shop']] = $shop['name'];
            $shopCount++;
        }

        # Generate .htaccess file after adding shops
        Tools::generateHtaccess();

        // if shop count greate than 1, enable muttishop
        if ($shopCount > 1) {
            AdminPreferencesControllerCore::updateOptionPsMultishopFeatureActive(1);
        }
    }

    /**
     * Create shop by source shop data
     *
     * @param $shop_name Shop name
     * @param  $shop_group_id Shop group identity
     * @param  $shop_group_name Shop group name
     * @param  $shop_physical_uri Shop physical URI name
     */
    private function createShop($shop_name, $shop_group_id, $shop_group_name, $shop_physical_uri, $shop_virtual_uri, $source_shop_id = null)
    {
        // Create default shop
        if (!$shop_group_id) {
            $shop_group_id = ShopGroupCore::getIdByName($shop_group_name);
        }
        if (!$source_shop_id) {
            $shop = new Shop();
        } else {
            $shop = new Shop($source_shop_id);
        }

        $shop->active = true;
        $shop->id_shop_group = $shop_group_id;
        $shop->id_category = 2;

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $shop->theme_name = _THEME_NAME_;
        } else {
            $shop->id_theme = 1;
        }
        $shop->name = $shop_name;
        if ($source_shop_id) {
            // If the shop is exists than update else add
            if (!Db::getInstance()->getValue('select id_shop FROM '._DB_PREFIX_.'shop where id_shop = ' . $source_shop_id)) {
                if (!$shop->add()) {
                    $this->errors[] = $this->translator->trans('Cannot create shop', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
                    return false;
                }
                
                Db::getInstance()->execute('update '._DB_PREFIX_.'shop set id_shop = ' . $source_shop_id . ' where id_shop = ' . $shop->id);
                $shop = new Shop($source_shop_id);
            } else {
                if (!$shop->update()) {
                    $this->errors[] = $this->translator->trans('Cannot create shop', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
                    return false;
                }
            }
        } else {
            if (!$shop->add()) {
                $this->errors[] = $this->translator->trans('Cannot create shop', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
                return false;
            }

            Db::getInstance()->execute('update '._DB_PREFIX_.'shop set id_shop = ' . $source_shop_id . ' where id_shop = ' . $shop->id);
            $shop = new Shop($source_shop_id);
        }

        // if ($source_shop_id) {
        //     Db::getInstance()->execute('update '._DB_PREFIX_.'shop set id_shop = ' . $source_shop_id . ' where id_shop = ' . $shop->id);
        //     $shop = new Shop($source_shop_id);
        // }


        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $shop->setTheme();
        }

        Context::getContext()->shop = $shop;

        // Create default shop URL
        if ($shop_virtual_uri != '') {
            $shop_url = new ShopUrl();
            $shop_url->domain = Tools::getHttpHost();
            $shop_url->domain_ssl = Tools::getHttpHost();
            $shop_url->physical_uri = ($shop_physical_uri == "" ?  "/"  : $shop_physical_uri);
            $shop_url->virtual_uri = $shop_virtual_uri;
            $shop_url->id_shop = $shop->id;
            $shop_url->main = true;
            $shop_url->active = true;

            if (!$shop_url->add()) {
                $this->errors[] = $this->translator->trans('Cannot create shop URL', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
                return false;
            }
        }

        // Set created shop defaults
        $this->copyToNewShopDefaultData($shop);
        return true;
    }

    /**
     *
     *
     *  @param $shop Instance of Shop class
     */
    private function copyToNewShopDefaultData($shop)
    {
        # Imported tables
        $tables_import = array();
        $tables_import["carrier_tax_rules_group_shop"] = true;
        $tables_import["carrier_lang"] = true;
        $tables_import["cms_lang"] = true;
        $tables_import["cms_category"] = true;
        $tables_import["cms_category_lang"] = true;
        $tables_import["category_lang"] = true;

        $tables_import["module_currency"] = true;
        $tables_import["module_country"] = true;
        $tables_import["module_group"] = true;
        $tables_import["hook_module_exceptions"] = true;

        $tables_import["attribute_group"] = 'on';
        $tables_import["manufacturer"] = 'on';
        $tables_import["carrier"] = 'on';
        $tables_import["cart_rule"] = 'on';
        $tables_import["contact"] = 'on';
        $tables_import["country"] = 'on';
        $tables_import["currency"] = 'on';
        $tables_import["group"] = 'on';
        $tables_import["employee"] = 'on';
        $tables_import["feature"] = 'on';
        $tables_import["lang"] = 'on';
        $tables_import["meta_lang"] = 'on';
        $tables_import["hook_module"] = 'on';
        $tables_import["module"] = 'on';
        $tables_import["cms"] = 'on';
        $tables_import["referrer"] = 'on';
        $tables_import["store"] = 'on';
        $tables_import["supplier"] = 'on';
        $tables_import["tax_rules_group"] = 'on';
        $tables_import["warehouse"] = 'on';
        $tables_import["webservice_account"] = 'on';
        $tables_import["zone"] = 'on';

        $shop->copyShopData(null, $tables_import);

        // Set shop of root and home categories
        $categories = array(Configuration::get("PS_ROOT_CATEGORY"), Configuration::get("PS_HOME_CATEGORY"));
        $root_category = new Category(Configuration::get("PS_ROOT_CATEGORY"));
        $root_category->deleteFromShop($shop->id);
        $homecategory = new Category(Configuration::get("PS_HOME_CATEGORY"));
        $homecategory->deleteFromShop($shop->id);
        Category::addToShop($categories, $shop->id);
    }


    /**
     * Create shop group by source shop data
     *
     * @param $shop_group_name Shop group name
     */
    private function createShopGroup($shop_group_name)
    {
        // Create default group shop
        $shop_group = new ShopGroup();
        $shop_group->name = $shop_group_name;
        $shop_group->active = true;
        if (!$shop_group->add()) {
            $this->errors[] = $this->translator->trans('Cannot create group shop', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
            return false;
        }
        return $shop_group->id;
    }

    /**
     * Install languages wich do not exists in target server
     */
    private function installLanguages()
    {
        $languages_list = $this->sourceData["languages"];

        if ($languages_list == null || !is_array($languages_list) || !count($languages_list)) {
            throw new PrestaShopException('Source lang data is empty');
        }
        // get target languages list
        $languages_available = Language::getLanguages();
        $languages = array();

        foreach ($languages_list as $lang) {
            // check exists in target source by iso_code
            if (!Language::getIdByIso($lang["iso_code"])) {
                // Install language to target server
                if (!Language::downloadAndInstallLanguagePack($lang["iso_code"])) {
                    throw new PrestaShopException($this->translator->trans('Cannot download language pack "%iso%"', array('%iso%' => $lang["iso_code"]), 'Install'));
                }
                Language::loadLanguages();
                Tools::clearCache();
            }
            $id_lang = Language::getIdByIso($lang["iso_code"], true);
            if (!$id_lang) {
                throw new PrestaShopException($this->translator->trans('Cannot install language "%iso%"', array('%iso%' => ($lang["iso_code"])), 'Install'));
            }

            // Set default lang
            if ($this->sourceData["languages_default"][0]["value"]) {
                if ($this->sourceData["languages_default"][0]["value"] == (string)$lang['id_lang']) {
                    Configuration::updateValue('PS_LANG_DEFAULT', $id_lang);
                }
            }
            $languages[$id_lang] = $lang["iso_code"];

            //Set mapping data values
            $this->mapping["languages"][$lang['id_lang']] = $id_lang;
            // dump($this->mapping["languages"]); dump($languages_list);  die;
            $this->mapping_data_texts["languages"][$lang['id_lang']] = $lang['name'];
        }
        return $languages;
    }

    /**
     * Clear order states and create order state data from source data
     */
    private function createOrderState()
    {
        $orders_state =  $this->sourceData["order_states"];
        $orders_state_lang =  $this->sourceData["order_states_lang"];

        // Clear data tables and begin adding
        if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'order_state`') &&
            Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'order_state_lang`')
        ) {
            foreach ($orders_state as $order_state) {
                $order_state_obj = new OrderState((int)$order_state['id_order_state']);
                $order_state_obj->send_email = $order_state["send_email"];
                $order_state_obj->module_name = $order_state["module_name"];
                $order_state_obj->invoice = $order_state["invoice"];
                $order_state_obj->color = $order_state["color"];
                $order_state_obj->logable = $order_state["logable"];
                $order_state_obj->shipped = $order_state["shipped"];
                $order_state_obj->unremovable = $order_state["unremovable"];
                $order_state_obj->delivery = $order_state["delivery"];
                $order_state_obj->hidden = $order_state["hidden"];
                $order_state_obj->paid = $order_state["paid"];
                $order_state_obj->pdf_delivery = $order_state["pdf_delivery"];
                $order_state_obj->pdf_invoice = $order_state["pdf_invoice"];
                $order_state_obj->deleted = $order_state["deleted"];

                // Add order state lang
                $order_state_name = "";
                if ($orders_state_lang[$order_state["id_order_state"]]) {
                    foreach ($orders_state_lang[$order_state["id_order_state"]] as $state_lang) {
                        $iso = $this->getLanguageIso($state_lang["id_lang"]);
                        if ($iso) {
                            $lang_id = Language::getIdByIso($iso);
                            $order_state_obj->name[$lang_id] = $state_lang["name"];

                            if (empty($order_state_obj->name[$lang_id])) {
                                $order_state_obj->name[$lang_id] = '--';
                            }

                            $order_state_obj->template[$lang_id] = $state_lang["template"];

                            // if (empty($order_state_obj->template[$lang_id]))
                            //     $order_state_obj->template[$lang_id] = '--';
                            // Set name default lang for mapping table
                            if ($this->sourceData["languages_default"][0]["value"] == (string)$state_lang["id_lang"]) {
                                $order_state_name = $state_lang["name"];
                            }
                        }
                    }
                }
                if (!$order_state_obj->name) {
                    $order_state_obj->name[$this->sourceData["languages_default"][0]["value"]] = '--';
                }


                $res = $order_state_obj->add(false);
                if (!$res) {
                    $this->errors[] = $this->translator->trans('Cannot add ', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
                    return false;
                }

                //Set mapping data values
                $this->mapping["order_states"][$order_state['id_order_state']] = $order_state_obj->id;
                $this->mapping_data_texts["order_states"][$order_state['id_order_state']] = $order_state_name;
            }
        }
    }

    /**
     * Clear target currencies and create from source curencies data
     */
    private function crateCurrencies()
    {
        $currensies = $this->sourceData["currencies"];
        if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'currency`') && Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'currency_shop`')) {
            foreach ($currensies as $currency) {
                $currencyObj = new Currency();
                $currencyObj->name = $currency["name"];
                $currencyObj->iso_code = $currency["iso_code"];
                $currencyObj->conversion_rate = $currency["conversion_rate"];
                $currencyObj->deleted = $currency["deleted"];
                $currencyObj->active = $currency["active"];
                $currencyObj->id_shop_list  = $this->getObjectShops($currency["id_currency"], "currencies_shop");
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    $currencyObj->sign = '-';
                    $currencyObj->conversion_rate = 1;
                    $currencyObj->decimals = 1;
                    $currencyObj->active = 1;
                    $currencyObj->format = 0;
                    $currencyObj->deleted = 0;
                }
                $currencyObj->add(false);

                //Set mapping data values
                $this->mapping["currencies"][$currency["id_currency"]] = $currencyObj->id;
                $this->mapping_data_texts["currencies"][$currency["id_currency"]] = $currency["name"];
            }
        }
    }

    /**
     * Return curencies shops identities array by currency identity
     *
     * @param int $obj_id Source Object data identity
     * @param string $key Key of array
     */
    private function getObjectShops($obj_id, $key)
    {
        $obj_shop = $this->sourceData[$key][$obj_id];
        $shop_ids = array();
        if ($obj_shop) {
            foreach ($obj_shop as $shop) {
                $shop_ids[] = $this->mapping["multi_shops"][$shop["id_shop"]];
            }
        } else {
            if ($this->sourceData["shops_default"][0]["value"]) {
                $shop_ids[] = $this->mapping["multi_shops"][$this->sourceData["shops_default"][0]["value"]];
            }
        }
        $default_shop = (int)Configuration::get('PS_SHOP_DEFAULT');
        // add to shop list target default shop if not exists
        if (!in_array($default_shop, $shop_ids)) {
            $shop_ids[] = $default_shop;
        }
        return $shop_ids;
    }

    /**
     * Clear customer groups and create  customer groups data from source data
     */
    private function createCustomerGroups()
    {
        $customer_groups =  $this->sourceData["customer_groups"];
        $customer_groups_lang =  $this->sourceData["customer_groups_lang"];

        // Clear data tables and begin adding
        if (Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'group`') &&
            Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'group_lang`') &&
            Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'category_group`') &&
            Db::getInstance()->execute('TRUNCATE TABLE  `' . _DB_PREFIX_ . 'group_shop`')
        ) {
            foreach ($customer_groups as $customer_group) {
                // Delete current group data from category group
                $customer_group_obj = new Group((int)$customer_group['id_group']);
                $customer_group_obj->reduction = $customer_group["reduction"];
                $customer_group_obj->price_display_method = $customer_group["price_display_method"];
                $customer_group_obj->date_add = $customer_group["date_add"];
                $customer_group_obj->date_upd = $customer_group["date_upd"];
                $customer_group_obj->id_shop_list  = $this->getObjectShops($customer_group["id_group"], "customer_groups_shop");

                // Add customer group lang
                $group_name = '';
                if ($customer_groups_lang[$customer_group["id_group"]]) {
                    foreach ($customer_groups_lang[$customer_group["id_group"]] as $group_lang) {
                        $iso = $this->getLanguageIso($group_lang["id_lang"]);
                        if ($iso) {
                            $lang_id = Language::getIdByIso($iso);
                            $customer_group_obj->name[$lang_id] = $group_lang["name"];
                            if ($this->sourceData["languages_default"][0]["value"]) {
                                if ($this->sourceData["languages_default"][0]["value"] == (string)$lang_id) {
                                    $group_name = $group_lang["name"];
                                }
                            }
                        }
                    }
                }

                if (!$customer_group_obj->name) {
                    $customer_group_obj->name[$this->sourceData["languages_default"][0]["value"]] = '--';
                }

                $res = $customer_group_obj->add(false);
                if (!$res) {
                    $this->errors[] = $this->translator->trans('Cannot add ', array(), 'Install') . ' / ' . Db::getInstance()->getMsgError();
                    return false;
                }
                //Set mapping data values
                $this->mapping["customer_groups"][$customer_group["id_group"]] = $customer_group_obj->id;
                $this->mapping_data_texts["customer_groups"][$customer_group["id_group"]] = $group_name;
            }
        }
    }

    /**
     * Return target language identity for source language identity
     */
    private function getLanguageIso($language_id)
    {
        foreach ($this->sourceData["languages"] as $lang) {
            if ($lang["id_lang"] == $language_id) {
                return $lang["iso_code"];
            }
        }

        if (count($this->sourceData["languages"] > 0)) {
            return $this->sourceData["languages"][0]["iso_code"];
        }
    }
}
