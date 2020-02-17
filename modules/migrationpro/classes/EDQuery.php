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

class EDQuery
{
    // --- Query builder vars:
    protected $source_cart;
    protected $tp;
    protected $offset;
    protected $row_count = 10;
    protected $version;
    protected $languages;
    protected $recent_data = false;
    // --- Constructor / destructor:
    public function __construct()
    {
    }
    // --- Configuration methods:
    public function setRowCount($number)
    {
        $this->row_count = (int)$number;
    }
    public function setLanguages($string)
    {
        $this->languages = pSQL($string);
    }
    public function setVersion($string)
    {
        $this->version = $string;
    }
    public function setCart($string)
    {
        $this->source_cart = $string;
    }
    public function setPrefix($string)
    {
        $this->tp = pSQL($string);
    }
    public function setOffset($number)
    {
        $this->offset = (int)$number;
    }
    public function setRecentData($recent_data)
    {
        $this->recent_data = (bool)$recent_data;
    }
    // --- get query string methods:
    public function getDefaultShopValues()
    {
        $q = array();
        if (version_compare($this->version, '1.5', '<')) {
            $q['root_home'] = "SELECT `id_category` AS home,`id_category` AS root FROM `" . pSQL($this->tp) . "category` WHERE `id_parent` = 0";
            $q['get_max_cat'] = "SELECT max(id_category) AS max_cat_id FROM " . pSQL($this->tp) . "category";
        } else {
            $q['root_home'] = "SELECT `id_category` AS home, (SELECT `id_category` as root FROM `" . pSQL($this->tp) . "category` WHERE `id_parent` = 0) AS root FROM `" . pSQL($this->tp) . "category` WHERE `id_parent` = (SELECT `id_category` FROM `" . pSQL($this->tp) . "category` WHERE `id_parent` = 0)";
            $q['root_home_multiple_root'] = "SELECT `id_category` AS home, (SELECT `id_category` as root FROM `" . pSQL($this->tp) . "category` WHERE `nleft` = 1) AS root FROM `" . pSQL($this->tp) . "category` WHERE `id_parent` = (SELECT `id_category` FROM `" . pSQL($this->tp) . "category` WHERE `nleft` = 1)";
            $q['get_max_cat'] = "SELECT max(id_category) AS max_cat_id FROM " . pSQL($this->tp) . "category";
        }
        $q['default_lang'] = "SELECT cfg.*, lg.* FROM " . pSQL($this->tp) . "lang AS lg LEFT JOIN " . pSQL($this->tp) . "configuration AS cfg ON lg.id_lang = cfg.value WHERE cfg.name = 'PS_LANG_DEFAULT'";
        $q['default_currency'] = "SELECT cfg.*, cur.* FROM " . pSQL($this->tp) . "currency AS cur LEFT JOIN " . pSQL($this->tp) . "configuration AS cfg ON cur.id_currency = cfg.value WHERE cfg.name = 'PS_CURRENCY_DEFAULT'";
        $q['root_category'] = "SELECT * FROM " . pSQL($this->tp) . "configuration WHERE name = 'PS_ROOT_CATEGORY'";
        return $q;
    }
    public function getMappingInfo($default_lang)
    {
        $q = array();
        if (version_compare($this->version, '1.5', '<')) {
            $q['multi_shops'] = 'SELECT 0 as `source_id`, `value` as `source_name` FROM  `' . pSQL($this->tp) . 'configuration` WHERE `name` =  \'PS_SHOP_NAME\'';
        } else {
            $q['multi_shops'] = 'SELECT `id_shop` as `source_id`, `name` as `source_name` FROM  `' . pSQL($this->tp) . 'shop` WHERE `active` = 1';
        }
        $q['languages'] = 'SELECT `id_lang` as `source_id`, `name` as `source_name`, iso_code FROM `' . pSQL($this->tp) . 'lang` WHERE `active` = 1';
        if (version_compare($this->version, '1.4', '<')) {
            $q['currencies'] = 'SELECT `id_currency` as `source_id`, `name` as `source_name` FROM `' . pSQL($this->tp) . 'currency`';
        } else {
            $q['currencies'] = 'SELECT `id_currency` as `source_id`, `name` as `source_name` FROM `' . pSQL($this->tp) . 'currency` WHERE `active` = 1';
        }
        $q['order_states'] = 'SELECT os.id_order_state as `source_id`, os.name as `source_name` FROM `' . pSQL($this->tp) . 'order_state_lang` AS `os` WHERE id_lang = ' . (int)pSQL($default_lang) . ' AND os.`name` IS NOT NULL GROUP BY os.id_order_state';
        $q['customer_groups'] = 'SELECT `g`.`id_group` as `source_id`,  `g`.`name` as `source_name` FROM `' . pSQL($this->tp) . 'group_lang` AS `g` WHERE id_lang = ' . (int)pSQL($default_lang) . ' AND g.`name` IS NOT NULL GROUP BY `g`.`id_group`';

        return $q;
    }

    /**
     * Generate SQL queries for get shops, shop groups, languages, currencies, order states and customer grops from source cart
     *
     * @param $default_lang default lang identity on source server
     * @return Array of SQL queries
     */
    public function getOneClickMigrationData()
    {
        $q = array();

        if (version_compare($this->version, '1.5', '<')) {
            $q['shops'] = 'SELECT 0 as `source_id`, `value` as `source_name` FROM  `' . pSQL($this->tp) . 'configuration` WHERE `name` =  \'PS_SHOP_NAME\'';
        } else {
            $q['shops'] = 'SELECT * FROM  `' . pSQL($this->tp) . 'shop` WHERE `active` = 1';
            $q['shops_group'] = 'SELECT * FROM  `' . pSQL($this->tp) . 'shop_group` WHERE `active` = 1';
            $q['shops_urls'] = 'SELECT * FROM  `' . pSQL($this->tp) . 'shop_url` WHERE `active` = 1';
            $q['shops_default'] = 'SELECT *  FROM  `' . pSQL($this->tp) . 'configuration` WHERE `name` = \'PS_SHOP_DEFAULT\'';
        }
        $q['languages'] = 'SELECT * FROM `' . pSQL($this->tp) . 'lang` WHERE `active` = 1';
        $q['languages_default'] = 'SELECT *  FROM  `' . pSQL($this->tp) . 'configuration` WHERE `name` = \'PS_LANG_DEFAULT\'';


        if (version_compare($this->version, '1.4', '<')) {
            $q['currencies'] = 'SELECT * FROM `' . pSQL($this->tp) . 'currency`';
        } else {
            $q['currencies'] = 'SELECT * FROM `' . pSQL($this->tp) . 'currency` WHERE `active` = 1';
        }
        
        if (version_compare($this->version, '1.5', '<')) {
            if (version_compare($this->version, '1.4', '<')) {
                $q['currencies'] = 'SELECT * FROM `' . pSQL($this->tp) . 'currency`';
            } else {
                $q['currencies'] = 'SELECT * FROM `' . pSQL($this->tp) . 'currency` WHERE `active` = 1';
            }
        } else {
            $q['currencies'] = 'SELECT * FROM `' . pSQL($this->tp) . 'currency` WHERE `active` = 1';
            $q['currencies_shop'] = 'SELECT * FROM `' . pSQL($this->tp) . 'currency_shop`';
        }
        
        $q['order_states'] = 'SELECT * FROM `' . pSQL($this->tp) . 'order_state` AS `os`';
        $q['order_states_lang'] = 'SELECT * FROM `' . pSQL($this->tp) . 'order_state_lang` AS `os`';

        $q['customer_groups'] = 'SELECT * FROM `' . pSQL($this->tp) . 'group` AS `g`';
        $q['customer_groups_lang'] = 'SELECT * FROM `' . pSQL($this->tp) . 'group_lang` AS `g`';
        $q['customer_groups_shop'] = 'SELECT * FROM `' . pSQL($this->tp) . 'group_shop` AS `g`';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['order_states_lang'] = 'id_order_state';
        $groupedqueriesconfiguration['currencies_shop'] = 'id_currency';
        $groupedqueriesconfiguration['currencies_shop'] = 'id_currency';
        $groupedqueriesconfiguration['customer_groups_lang'] = 'id_group';
        $groupedqueriesconfiguration['customer_groups_shop'] = 'id_group';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }
    public function getCountInfo($map)
    {
        $q = array();
        if (reset($map['entity']['taxes'])  == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['taxes'] = 'SELECT count(1) as `c`  FROM  `' . pSQL($this->tp) . 'tax_rules_group` ';
            }
        }
        if (reset($map['entity']['manufactures']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['manufacturers'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'manufacturer`';
            }
        }
        if (reset($map['entity']['categories']) == 1) {
            $root_cat = Configuration::get('migrationpro_source_root_cat');
            $home_cat = Configuration::get('migrationpro_source_home_cat');
            if (!reset($map['additional']['migrate_recent_data'])) {
                if (version_compare($this->version, '1.5', '<')) {
                    $q['categories'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'category` WHERE id_category != ' . (int)$root_cat . ' ';
                } else {
                    $q['categories'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'category` WHERE id_category != ' . (int)$root_cat . ' AND id_category != ' . (int)$home_cat . '  ';
                }
            }
        }
        if (reset($map['entity']['carriers']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['carriers'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'carrier` WHERE `deleted` = 0';
            }
        }
        //        if (reset($map['entity']['taxes']) == 1) {
        //            if (reset($map['additional']['migrate_recent_data'])) {
        //                $q['warehouse'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'warehouse` WHERE `deleted` != 0 ';
        //            }
        //        }
        if (reset($map['entity']['catalog_price_rules']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['catalog_price_rules'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'specific_price_rule`';
            }
        }
        if (reset($map['entity']['employees']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['employees'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'employee`';
            }
        }

        if (reset($map['entity']['products']) == 1) {
            $last_migrated_product_id = MigrationProMigratedData::getLastId('product');
            if (reset($map['additional']['migrate_recent_data'])) {
                $q['products'] = 'SELECT count(1) as `c`  FROM  `' . pSQL($this->tp) . 'product` WHERE `id_product` > ' . (int)$last_migrated_product_id;
            } else {
                $q['products'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'product`';
            }
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['accessories'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'accessory`';
            }
        }
        if (reset($map['entity']['customers']) == 1) {
            $last_migrated_customer_id = MigrationProMigratedData::getLastId('customer');
            if (reset($map['additional']['migrate_recent_data'])) {
                $q['customers'] = 'SELECT count(1) as `c`  FROM  `' . pSQL($this->tp) . 'customer` WHERE `id_customer` > ' . (int)$last_migrated_customer_id . ' AND ( deleted IS NULL OR deleted = 0)';
            } else {
                $q['customers'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'customer` WHERE id_customer != 0 AND ( deleted IS NULL OR deleted = 0)';
            }
        }
        if (reset($map['entity']['cart_rules']) == 1) {
            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                if (!reset($map['additional']['migrate_recent_data'])) {
                    $q['cart_rules'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'discount`';
                }
            } else {
                if (!reset($map['additional']['migrate_recent_data'])) {
                    $q['cart_rules'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'cart_rule`';
                }
            }
        }
        if (reset($map['entity']['orders']) == 1 && reset($map['entity']['customers']) == 1) {
            $last_migrated_order_id = MigrationProMigratedData::getLastId('order');
            if (reset($map['additional']['migrate_recent_data'])) {
                $q['orders'] = 'SELECT count(1) as `c`  FROM  `' . pSQL($this->tp) . 'orders` WHERE `id_order` > ' . (int)$last_migrated_order_id;
            } else {
                $q['orders'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'orders` WHERE id_order != 0';
            }
        }
        if (reset($map['entity']['orders']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['message_threads'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'customer_thread`';
            }
        }
        if (reset($map['entity']['cms']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['cms'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'cms`';
            }
        }
        if (reset($map['entity']['seo']) == 1) {
            if (!reset($map['additional']['migrate_recent_data'])) {
                $q['seo'] = 'SELECT count(1) as `c` FROM  `' . pSQL($this->tp) . 'meta`';
            }
        }
        return $q;
    }

    // --- Tax methods:
    public function taxRulesGroup()
    {
        $q = array();
        $versionfield = ", ptrgs.id_shop_list ";
        $versionQuery = ' LEFT JOIN (SELECT s.id_tax_rules_group,
                                    GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list FROM ' . pSQL($this->tp) . 'tax_rules_group_shop s
                                    GROUP BY s.id_tax_rules_group) ptrgs ON ptrgs.id_tax_rules_group = ptrg.id_tax_rules_group ';
        $versionfieldCountry = ", tgs.id_shop_list ";
        $versionQueryCountry = ' LEFT  JOIN (SELECT s.id_country, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                          FROM ' . pSQL($this->tp) . 'country_shop s
                          GROUP BY s.id_country) tgs ON tgs.id_country = c.id_country ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionfield = "";
            $versionQuery = "";
            $versionfieldCountry = "";
            $versionQueryCountry = "";
        }
        // main&general query
        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'tax_rules_group
                          ORDER BY id_tax_rules_group ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        //OK
        $q['tax_rules_group'] = 'SELECT ptrg.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'tax_rules_group ptrg ' . $versionQuery . '
                                    ORDER BY id_tax_rules_group ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        //OK
        $q['tax_rule'] = 'SELECT * FROM  ' . pSQL($this->tp) . 'tax_rule r
                          INNER JOIN (' .$main . ') g ON g.id_tax_rules_group = r.id_tax_rules_group ORDER BY r.id_tax_rules_group ASC';

        $q['tax'] = 'SELECT DISTINCT pt.*  FROM ' . pSQL($this->tp) . 'tax pt
                        INNER JOIN  ' . pSQL($this->tp) . 'tax_rule r ON r.id_tax = pt.id_tax
                        INNER JOIN (' .$main . ') g ON g.id_tax_rules_group = r.id_tax_rules_group';

        $q['tax_lang'] = 'SELECT  DISTINCT  ptl.*  FROM ' . pSQL($this->tp) . 'tax_lang ptl
                        INNER JOIN ' . pSQL($this->tp) . 'tax pt ON pt.id_tax = ptl.id_tax
                        INNER JOIN ' . pSQL($this->tp) . 'tax_rule r ON r.id_tax = pt.id_tax
                        INNER JOIN (' .$main . ') g ON g.id_tax_rules_group = r.id_tax_rules_group
                        ORDER BY ptl.id_tax ASC';

        $q['country'] = 'SELECT c.* ' . $versionfieldCountry . '  FROM ' . pSQL($this->tp) . 'country `c` 
                            INNER JOIN ' . pSQL($this->tp) . 'tax_rule r ON c.id_country = r.id_country ' . $versionQueryCountry . '
                            INNER JOIN (' .$main . ') g ON g.id_tax_rules_group = r.id_tax_rules_group';

        $q['country_lang'] = 'SELECT cl.* FROM ' . pSQL($this->tp) . 'country_lang cl
                                INNER JOIN ' . pSQL($this->tp) . 'country `c` ON cl.id_country = `c`.id_country
                                INNER JOIN ' . pSQL($this->tp) . 'tax_rule r ON c.id_country = r.id_country ' . $versionQueryCountry . '
                                INNER JOIN (' .$main . ') g ON g.id_tax_rules_group = r.id_tax_rules_group
                                WHERE cl.id_lang IN ( ' . pSQL($this->languages) . ' )';

        $q['state'] = 'SELECT s.* FROM ' . pSQL($this->tp) . 'state s
                            INNER JOIN ' . pSQL($this->tp) . 'tax_rule r ON s.id_state = r.id_state
                            INNER JOIN (' .$main . ') g ON g.id_tax_rules_group = r.id_tax_rules_group';

        #region Configuration for grouped tables
        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['tax_lang'] = 'id_tax';
        $groupedqueriesconfiguration['tax_rule'] = 'id_tax_rules_group';
        $groupedqueriesconfiguration['country_lang'] = 'id_country';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        #endregion
        return $q;
    }

    // --- Manufacture methods:
    public function manufactures()
    {
        $q = array();
        $versionQuery = ' LEFT JOIN
                                (SELECT pms.id_manufacturer , GROUP_CONCAT(pms.id_shop SEPARATOR \',\') AS id_shop_list
                                FROM  ' . pSQL($this->tp) . 'manufacturer_shop pms
                                GROUP BY pms.id_manufacturer) s ON s.id_manufacturer = m.id_manufacturer ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionQuery = "";
        }
        // main&general query
        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'manufacturer ORDER BY id_manufacturer ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['manufactures'] = 'SELECT * FROM ' . pSQL($this->tp) . 'manufacturer m '. $versionQuery .'
                                    ORDER BY m.id_manufacturer ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['manufactures_lang'] = 'SELECT l.* FROM ' . pSQL($this->tp) . 'manufacturer_lang l
                                   INNER JOIN ( ' . $main . ') m ON m.id_manufacturer = l.id_manufacturer AND l.id_lang
                                   IN ( ' . pSQL($this->languages) . ' ) ORDER BY l.id_manufacturer ASC';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['manufactures_lang'] = 'id_manufacturer';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }
    // --- Category methods:
    public function category()
    {
        $root_cat = Configuration::get('migrationpro_source_root_cat');
        $home_cat = Configuration::get('migrationpro_source_home_cat');
        $version = version_compare($this->version, '1.5', '<') ? '': 'AND id_category != ' . (int)$home_cat;

        // main&general query
        $main = 'SELECT id_category FROM ' . pSQL($this->tp) . 'category WHERE id_category !=' . (int)$root_cat . ' ' . $version . ' 
                       ORDER BY level_depth ASC, id_category ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q = array();

        if (version_compare($this->version, '1.5', '<')) {
            $q['category'] = 'SELECT c.* FROM ' . pSQL($this->tp) . 'category c
                                    WHERE c.id_category !=' . (int)$root_cat . ' ORDER BY level_depth ASC, id_category ASC LIMIT   ' . (int)$this->offset . ',' . (int)$this->row_count;
            //Don't have ideas yet
            /*
            $q['category_parrent']  =  'SELECT c.*, s.id_shop_list FROM
                                ' . pSQL($this->tp) . 'category c
                                    INNER JOIN (SELECT  cs.id_category , GROUP_CONCAT(cs.id_shop SEPARATOR \',\') AS id_shop_list FROM  ' . pSQL($this->tp) . 'category_shop cs  GROUP BY cs.id_category) s ON s.id_category = c.id_category
                                    WHERE c.id_category !=' . (int)$root_cat . ' AND  id_category IN (SELECT c.id_category FROM
                                ' . pSQL($this->tp) . 'category c
                                    WHERE c.id_category !=' . (int)$root_cat . ' ORDER BY level_depth ASC, id_category ASC LIMIT   ' . (int)$this->offset . ',' . (int)$this->row_count.')';
             */

            $q['category_lang'] = 'SELECT l.* FROM ' . pSQL($this->tp) . 'category_lang  l
                                   INNER JOIN (' . $main . ') c ON l.id_category = c.id_category
                                   WHERE  l.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY l.id_category ASC';

            $q['category_group'] = 'SELECT g.* FROM  ' . pSQL($this->tp) . 'category_group g
                                   INNER JOIN (' . $main . ') c ON g.id_category = c.id_category
                                   ORDER BY g.id_category ASC';
        } else {
            $q['category'] = 'SELECT c.*, s.id_shop_list FROM ' . pSQL($this->tp) . 'category c
                                LEFT  JOIN (SELECT  cs.id_category , GROUP_CONCAT(cs.id_shop SEPARATOR \',\') AS id_shop_list FROM  ' . pSQL($this->tp) . 'category_shop cs  GROUP BY cs.id_category) s ON s.id_category = c.id_category
                                WHERE c.id_category !=' . (int)$root_cat . ' AND c.id_category != ' . (int)$home_cat . '  ORDER BY level_depth ASC, id_category ASC LIMIT   ' . (int)$this->offset . ',' . (int)$this->row_count;

            $q['category_lang'] = 'SELECT l.* FROM ' . pSQL($this->tp) . 'category_lang  l
                                   INNER JOIN (' . $main . ') c ON l.id_category = c.id_category
                                   WHERE  l.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY l.id_category ASC';

            $q['category_group'] = 'SELECT g.* FROM  ' . pSQL($this->tp) . 'category_group g
                                   INNER JOIN (' . $main . ') c ON g.id_category = c.id_category
                                   ORDER BY g.id_category ASC';
        }

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['category_lang'] = 'id_category';
        $groupedqueriesconfiguration['category_group'] = 'id_category';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- Category methods:
    public function parentCategory($id_category)
    {
        // main&general query
        $main = 'SELECT id_category
                       FROM ' . pSQL($this->tp) . 'category WHERE id_category =  ' . $id_category;
        $q = array();
        if (version_compare($this->version, '1.5', '<')) {
            $q['category'] = 'SELECT c.* FROM
                                ' . pSQL($this->tp) . 'category c
                                    WHERE  c.id_category = ' . (int)$id_category;
            //Don't have ideas yet
            /*
            $q['category_parrent']  =  'SELECT c.*, s.id_shop_list FROM
            ' . pSQL($this->tp) . 'category c
            INNER JOIN (SELECT  cs.id_category , GROUP_CONCAT(cs.id_shop SEPARATOR \',\') AS id_shop_list FROM  ' . pSQL($this->tp) . 'category_shop cs  GROUP BY cs.id_category) s ON s.id_category = c.id_category
            WHERE c.id_category !=' . (int)$root_cat . ' AND  id_category IN (SELECT c.id_category FROM
            ' . pSQL($this->tp) . 'category c
            WHERE c.id_category !=' . (int)$root_cat . ' ORDER BY level_depth ASC, id_category ASC LIMIT   ' . (int)$this->offset . ',' . (int)$this->row_count.')';
             */

            $q['category_lang'] = 'SELECT l.* FROM ' . pSQL($this->tp) . 'category_lang  l
                                   INNER JOIN (' . $main . ') c ON l.id_category = c.id_category
                                   WHERE  l.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY l.id_category ASC';

            $q['category_group'] = 'SELECT g.* FROM  ' . pSQL($this->tp) . 'category_group g
                                   INNER JOIN (' . $main . ') c ON g.id_category = c.id_category
                                   ORDER BY g.id_category ASC';
        } else {
            $q['category'] = 'SELECT c.*, s.id_shop_list FROM ' . pSQL($this->tp) . 'category c
                                LEFT  JOIN (SELECT  cs.id_category , GROUP_CONCAT(cs.id_shop SEPARATOR \',\') AS id_shop_list FROM  ' . pSQL($this->tp) . 'category_shop cs  GROUP BY cs.id_category) s ON s.id_category = c.id_category
                                WHERE  c.id_category =  ' . $id_category;


            $q['category_lang'] = 'SELECT l.* FROM ' . pSQL($this->tp) . 'category_lang  l
                                   INNER JOIN (' . $main . ') c ON l.id_category = c.id_category
                                   WHERE  l.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY l.id_category ASC';

            $q['category_group'] = 'SELECT g.* FROM  ' . pSQL($this->tp) . 'category_group g
                                   INNER JOIN (' . $main . ') c ON g.id_category = c.id_category
                                   ORDER BY g.id_category ASC';
        }

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['category_lang'] = 'id_category';
        $groupedqueriesconfiguration['category_group'] = 'id_category';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- Carrier method:
    public function carrier()
    {
        $q = array();
        $versionfield = ', s.id_shop_list ';
        $versionQuery = ' LEFT  JOIN
                      (SELECT cs.id_carrier , GROUP_CONCAT(cs.id_shop SEPARATOR \',\') AS id_shop_list
                      FROM  ' . pSQL($this->tp) . 'carrier_shop cs
                      GROUP BY cs.id_carrier) s ON s.id_carrier = c.id_carrier ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionfield = "";
            $versionQuery = "";
        }
        // main&general query
        $main = 'SELECT  id_carrier FROM ' . pSQL($this->tp) . 'carrier c 
                        WHERE  `deleted` != 1 ORDER BY id_carrier ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['carrier'] = 'SELECT c.* '.$versionfield.' FROM ' . pSQL($this->tp) . 'carrier c '. $versionQuery.'
                      WHERE c.`deleted` != 1 ORDER BY c.id_carrier ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['all_zones'] = 'SELECT * FROM ' . pSQL($this->tp) . 'zone';

        $q['carrier_delivery'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'delivery d
                                    INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier ORDER BY d.id_carrier ASC';

        $q['range_price'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'range_price  d
                            INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier ORDER BY d.id_carrier ASC';

        $q['range_weight'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'range_weight d
                            INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier ORDER BY d.id_carrier ASC';

        $q['carrier_group'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'carrier_group  d
                             INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier ORDER BY d.id_carrier ASC';

        $q['carrier_lang'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'carrier_lang   d
                             INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier WHERE  d.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY d.id_carrier ASC';

        $q['carrier_tax_rules_group_shop'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'carrier_tax_rules_group_shop  d
                                             INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier ORDER BY d.id_carrier ASC';

        $q['carrier_zone'] = 'SELECT DISTINCT d.* FROM ' . pSQL($this->tp) . 'carrier_zone  d INNER JOIN (' . $main . ')  c ON c.id_carrier = d.id_carrier ORDER BY d.id_carrier ASC';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['carrier_lang'] = 'id_carrier';
        $groupedqueriesconfiguration['carrier_group'] = 'id_carrier';
        $groupedqueriesconfiguration['range_price'] = 'id_carrier';
        $groupedqueriesconfiguration['range_weight'] = 'id_carrier';
        $groupedqueriesconfiguration['carrier_delivery'] = 'id_carrier';
        $groupedqueriesconfiguration['carrier_tax_rules_group_shop'] = 'id_carrier';
        $groupedqueriesconfiguration['carrier_zone'] = 'id_carrier';

        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        return $q;
    }

    // --- Warehouse methodes
    //    public function warehouses()
    //    {
    //        return 'SELECT * FROM ' . pSQL($this->tp) . 'warehouse where deleted != 0 LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
    //    }
    //
    //    public function warehousesSqlSecond($id_warehouses, $id_address)
    //    {
    //        $q = array();
    //        $q['warehouse_carrier'] = 'SELECT * FROM ' . pSQL($this->tp) . 'warehouse_carrier WHERE id_warehouse IN (' . pSQL($id_warehouses) . ')';
    //        $q['warehouse_shop'] = 'SELECT * FROM ' . pSQL($this->tp) . 'warehouse_shop WHERE id_warehouse IN (' . pSQL($id_warehouses) . ')';
    //        $q['address'] = 'SELECT a.*, z.iso_code as zone_code, c.iso_code as country_code
    //                                            FROM ' . pSQL($this->tp) . 'address AS a
    //                                                LEFT JOIN ' . pSQL($this->tp) . 'country AS c ON a.id_country = c.id_country
    //                                                LEFT JOIN ' . pSQL($this->tp) . 'state AS z ON a.id_state = z.id_state
    //                                            WHERE a.id_address IN (' . pSQL($id_address) . ')';
    //
    //        return $q;
    //    }
    // --- Product method:
    public function product()
    {
        $q = array();

        $versionfield = ', s.id_shop_list ';
        $versionQuery = ' LEFT  JOIN
                        (SELECT pps.id_product , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                        FROM  ' . pSQL($this->tp) . 'product_shop pps
                        GROUP BY pps.id_product) s ON s.id_product = p.id_product ';
        $versionQueryImages = ' LEFT JOIN
                                  (SELECT pps.id_image , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'image_shop pps
                                  GROUP BY pps.id_image) s ON a.id_image = s.id_image ';
        $versionQueryImagesID = 'LEFT JOIN
                                  (SELECT pps.id_image , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'image_shop pps
                                  GROUP BY pps.id_image) s ON a.id_image = s.id_image ';

        $versionQueryPA = ' LEFT JOIN
                                  (SELECT pps.id_product_attribute , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'product_attribute_shop    pps
                                  GROUP BY pps.id_product_attribute) s ON a.id_product_attribute = s.id_product_attribute ';

        $versionQueryPS= 'LEFT JOIN
                                  (SELECT pps.id_supplier , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'supplier_shop      pps
                                  GROUP BY pps.id_supplier) s ON a.id_supplier = s.id_supplier ';
        $versionQueryFP = 'LEFT JOIN
                                  (SELECT pps.id_feature , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'feature_shop    pps
                                  GROUP BY pps.id_feature) s ON a.id_feature = s.id_feature ';
        $versionQueryAG = ' LEFT JOIN
                                  (SELECT pps.id_attribute_group , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'attribute_group_shop   pps
                                  GROUP BY pps.id_attribute_group) s ON s.id_attribute_group = a.id_attribute_group ';
        $versionQueryA = 'LEFT JOIN
                                  (SELECT pps.id_attribute , GROUP_CONCAT(pps.id_shop SEPARATOR \',\') AS id_shop_list
                                  FROM  ' . pSQL($this->tp) . 'attribute_shop     pps
                                  GROUP BY pps.id_attribute) s ON s.id_attribute = a.id_attribute ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionfield = "";
            $versionQuery = "";
            $versionQueryImages = "";
            $versionQueryImagesID = "";
            $versionQueryPA = "";
            $versionQueryPS = "";
            $versionQueryFP = "";
            $versionQueryAG = "";
            $versionQueryA = "";
        }

        $last_migrated_product_id = MigrationProMigratedData::getLastId('product');
        // main&general query
        $main = 'SELECT p.id_product FROM ' . pSQL($this->tp) . 'product p ORDER BY id_product ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        $main_recent_data = 'SELECT p.id_product FROM ' . pSQL($this->tp) . 'product p
                                      WHERE p.`id_product` > ' . (int)$last_migrated_product_id . ' ORDER BY id_product ASC LIMIT ' . (int)$this->row_count;

        if ($this->recent_data) {
            $q['product'] = 'SELECT p.* '.$versionfield.' FROM ' . pSQL($this->tp) . 'product p '. $versionQuery .' 
                        WHERE p.id_product > ' . (int)$last_migrated_product_id . ' ORDER BY id_product ASC LIMIT ' . (int)$this->row_count;

            #region Second related queries
            $q['product_carrier'] = 'SELECT DISTINCT  a.*, b.id_carrier FROM ' . pSQL($this->tp) . 'product_carrier AS a
                                    INNER JOIN ' . pSQL($this->tp) . 'carrier AS b ON a.id_carrier_reference = b.id_reference
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product
                                    WHERE b.deleted = 0 ORDER BY a.id_product ASC';

            $q['product_pack'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'pack  a
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product_pack  ORDER BY a.id_product_pack ASC';

            $q['product_lang'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_lang  a
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product WHERE a.id_lang IN ( ' . pSQL($this->languages) . ' )  ORDER BY a.id_product ASC';

            $q['specific_price'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'specific_price  a
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['category_product'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'category_product   a
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['stock_available'] = 'SELECT  DISTINCT a.* FROM ' . pSQL($this->tp) . 'stock_available   a
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['image'] = 'SELECT DISTINCT  a.*  '. $versionfield .'  FROM ' . pSQL($this->tp) . 'image  a ' . $versionQueryImages . '
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';
            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $q['image_ids'] = 'SELECT DISTINCT  CONCAT(CAST( a.id_product AS CHAR(11)),  \'-\' ,CAST( a.id_image AS CHAR(11)))   id_image   FROM ' . pSQL($this->tp) . 'image  a
                                  ' . $versionQueryImagesID .'
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_image ASC';
            } else {
                $q['image_ids'] = 'SELECT DISTINCT a.id_image  FROM ' . pSQL($this->tp) . 'image  a ' . $versionQueryImagesID . '
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_image ASC';
            }

            $q['product_download'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_download  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['product_attachment'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_attachment  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['product_attribute'] = 'SELECT DISTINCT  a.* '. $versionfield .'  FROM ' . pSQL($this->tp) . 'product_attribute  a ' . $versionQueryPA . '
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product   ORDER BY a.id_product ASC';

            $q['product_supplier'] = 'SELECT  DISTINCT a.* ' . $versionfield . '  FROM ' . pSQL($this->tp) . 'product_supplier   a '. $versionQueryPS .'
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['feature_product'] = 'SELECT DISTINCT  a.*' . $versionfield . '  FROM ' . pSQL($this->tp) . 'feature_product  a '. $versionQueryFP .'
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product   ORDER BY a.id_product ASC';

            $q['customization_field'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'customization_field  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['product_tag'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_tag  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';
            /*
            $q['product_shop'] = 'SELECT a.* FROM ' . pSQL($this->tp) . 'product_shop  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product';
             */
            $q['stock'] = 'SELECT  DISTINCT a.* FROM ' . pSQL($this->tp) . 'stock  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['warehouse_product_location'] = 'SELECT DISTINCT  * FROM ' . pSQL($this->tp) . 'warehouse_product_location  a
                                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';
            #endregion

            #region Third related queries
            $q['product_attribute_combination'] = 'SELECT DISTINCT   a.*, pac.id_product_attribute FROM ' . pSQL($this->tp) . 'product_attribute_combination as pac
                                                    INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppa.id_product_attribute = pac.id_product_attribute
                                                    LEFT JOIN ' . pSQL($this->tp) . 'attribute as a ON a.id_attribute = pac.id_attribute
                                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product  ORDER BY pac.id_product_attribute ASC ';


            $q['product_attribute_image'] = 'SELECT DISTINCT  pai.* FROM ' . pSQL($this->tp) . 'product_attribute_image pai
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppa.id_product_attribute = pai.id_product_attribute
                                            INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product    ORDER BY pai.id_product_attribute ASC';
            /*
            $q['product_attribute_shop'] =  'SELECT pai.* FROM ' . pSQL($this->tp) . 'product_attribute_shop pai
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppa.id_product_attribute = pai.id_product_attribute
                                            INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product ';
             */
            $q['feature'] = 'SELECT DISTINCT f.* FROM ' . pSQL($this->tp) . 'feature  f
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON  pfp.id_feature = f.id_feature
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pfp.id_product';
            $q['feature_lang'] = 'SELECT DISTINCT  fl.* FROM ' . pSQL($this->tp) . 'feature_lang fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON pfp.id_feature = fl.id_feature
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pfp.id_product ORDER BY  fl.id_feature ASC';
            /*

            $q['feature_shop'] =  'SELECT fl.* FROM ' . pSQL($this->tp) . 'feature_shop fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON pfp.id_feature = fl.id_feature
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pfp.id_product';

             */

            $q['feature_value'] = 'SELECT DISTINCT fl.* FROM ' . pSQL($this->tp) . 'feature_value  fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON  pfp.id_feature_value = fl.id_feature_value
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pfp.id_product';

            $q['feature_value_lang'] = 'SELECT DISTINCT  fl.* FROM ' . pSQL($this->tp) . 'feature_value_lang   fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON  pfp.id_feature_value = fl.id_feature_value
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pfp.id_product
                                     WHERE  id_lang IN (' . pSQL($this->languages) . ') ORDER BY fl.id_feature_value ASC';

            $q['supplier'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'supplier s
                                INNER JOIN ' . pSQL($this->tp) . 'product_supplier pps   ON pps.id_supplier = s.id_supplier
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pps.id_product';

            /*
            $q['supplier_shop'] = 'SELECT s.* FROM ' . pSQL($this->tp) . 'supplier_shop s
                                INNER JOIN ' . pSQL($this->tp) . 'product_supplier pps ON pps.id_supplier = s.id_supplier
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pps.id_product';
             */

            $q['supplier_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'supplier_lang s
                                INNER JOIN ' . pSQL($this->tp) . 'product_supplier pps   ON pps.id_supplier = s.id_supplier
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pps.id_product ORDER BY s.id_supplier ASC ';

            $q['customization_field_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'customization_field_lang  s
                                            INNER JOIN ' . pSQL($this->tp) . 'customization_field pcf ON pcf.id_customization_field = s.id_customization_field
                                            INNER JOIN (' . $main_recent_data . ') pp ON pp.id_product =pcf.id_product
                                            WHERE  id_lang IN (' . pSQL($this->languages) . ') ORDER BY s.id_customization_field ASC';

            $q['tag'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'tag   s
                        INNER JOIN ' . pSQL($this->tp) . 'product_tag ppt      ON ppt.id_tag = s.id_tag
                        INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =ppt.id_product';

            $q['image_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'image_lang   s
                                INNER JOIN ' . pSQL($this->tp) . 'image pi        ON pi.id_image = s.id_image
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =pi.id_product WHERE id_lang IN ( ' . pSQL($this->languages) . ' )   ORDER BY s.id_image ASC';

            $q['attachment'] = 'SELECT DISTINCT  s.* FROM  ' . pSQL($this->tp) . 'attachment   s
                                INNER JOIN  ' . pSQL($this->tp) . 'product_attachment ppa   ON ppa.id_attachment = s.id_attachment
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =ppa.id_product   ORDER BY s.id_attachment ASC ';

            $q['attachment_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'attachment_lang  s
                                INNER JOIN  ' . pSQL($this->tp) . 'product_attachment ppa   ON ppa.id_attachment = s.id_attachment
                                INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product =ppa.id_product WHERE  id_lang IN ( ' . pSQL($this->languages) . ' )    ORDER BY s.id_attachment ASC ';

            #endregion

            #region Fourth related queries

            $q['attribute_group'] = 'SELECT DISTINCT  a.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'attribute_group   a ' . $versionQueryAG . '
                                    INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = pa.id_attribute
                                    INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                    INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product';

            $q['attribute_group_lang'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'attribute_group_lang   a
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = pa.id_attribute
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                            INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY a.id_attribute_group ASC';

            $q['attribute'] = 'SELECT DISTINCT  a.*'.$versionfield.'  FROM ' . pSQL($this->tp) . 'attribute a ' . $versionQueryA . '
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = a.id_attribute
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                            INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product';

            $q['attribute_lang'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'attribute_lang  a
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = a.id_attribute
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                            INNER JOIN (' . $main_recent_data . ')  pp ON pp.id_product = ppa.id_product WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY a.id_attribute ASC';

            #endregion
        } else {
            $q['product'] = 'SELECT p.*   '. $versionfield.' FROM ' . pSQL($this->tp) . 'product p '. $versionQuery .'
                         ORDER BY id_product ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
            #region Second related queries
            $q['product_carrier'] = 'SELECT DISTINCT  a.*, b.id_carrier FROM ' . pSQL($this->tp) . 'product_carrier AS a
                                    INNER JOIN ' . pSQL($this->tp) . 'carrier AS b ON a.id_carrier_reference = b.id_reference
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product WHERE b.deleted = 0 ORDER BY a.id_product ASC';

            $q['product_pack'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'pack  a
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product_pack  ORDER BY a.id_product_pack ASC';

            $q['product_lang'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_lang  a
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product WHERE a.id_lang IN ( ' . pSQL($this->languages) . ' )  ORDER BY a.id_product ASC';

            $q['specific_price'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'specific_price  a
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['category_product'] = 'SELECT  DISTINCT a.* FROM ' . pSQL($this->tp) . 'category_product   a
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['stock_available'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'stock_available   a
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['image'] = 'SELECT DISTINCT  a.*'. $versionfield .'  FROM ' . pSQL($this->tp) . 'image  a ' . $versionQueryImages . '
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            //If Prestashop version is smaller than 1.5
            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $q['image_ids'] = 'SELECT DISTINCT CONCAT(CAST( a.id_product AS CHAR(11)),  \'-\' ,CAST( a.id_image AS CHAR(11)))   id_image    FROM ' . pSQL($this->tp) . 'image  a
                                    ' . $versionQueryImagesID . '
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_image ASC';
            } else {
                $q['image_ids'] = 'SELECT DISTINCT a.id_image  FROM ' . pSQL($this->tp) . 'image  a ' . $versionQueryImagesID . '
                                INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_image ASC';
            }

            $q['product_download'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_download  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['product_attachment'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_attachment  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['product_attribute'] = 'SELECT DISTINCT  a.* '. $versionfield .'  FROM ' . pSQL($this->tp) . 'product_attribute  a ' . $versionQueryPA . '
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product   ORDER BY a.id_product ASC';

            $q['product_supplier'] = 'SELECT  DISTINCT a.*' . $versionfield . '  FROM ' . pSQL($this->tp) . 'product_supplier   a '. $versionQueryPS .'
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['feature_product'] = 'SELECT DISTINCT  a.* ' . $versionfield . '  FROM ' . pSQL($this->tp) . 'feature_product  a '. $versionQueryFP .'
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product   ORDER BY a.id_product ASC';

            $q['customization_field'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'customization_field  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['product_tag'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'product_tag  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';
            /*
            $q['product_shop'] = 'SELECT a.* FROM ' . pSQL($this->tp) . 'product_shop  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product';
             */
            $q['stock'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'stock  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';

            $q['warehouse_product_location'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'warehouse_product_location  a
                                        INNER JOIN (' . $main . ')  pp ON pp.id_product = a.id_product  ORDER BY a.id_product ASC';
            #endregion

            #region Third related queries
            $q['product_attribute_combination'] = 'SELECT DISTINCT   a.*, pac.id_product_attribute FROM ' . pSQL($this->tp) . 'product_attribute_combination as pac
                                                    INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppa.id_product_attribute = pac.id_product_attribute
                                                    LEFT JOIN ' . pSQL($this->tp) . 'attribute as a ON a.id_attribute = pac.id_attribute
                                                    INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product  ORDER BY pac.id_product_attribute ASC ';

            $q['product_attribute_image'] = 'SELECT DISTINCT  pai.* FROM ' . pSQL($this->tp) . 'product_attribute_image pai
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppa.id_product_attribute = pai.id_product_attribute
                                            INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product    ORDER BY pai.id_product_attribute ASC';
            /*
            $q['product_attribute_shop'] =  'SELECT pai.* FROM ' . pSQL($this->tp) . 'product_attribute_shop pai
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppa.id_product_attribute = pai.id_product_attribute
                                            INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product ';
             */

            $q['feature'] = 'SELECT DISTINCT f.* FROM ' . pSQL($this->tp) . 'feature  f
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON  pfp.id_feature = f.id_feature
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product =pfp.id_product';

            $q['feature_lang'] = 'SELECT DISTINCT  fl.* FROM ' . pSQL($this->tp) . 'feature_lang fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON pfp.id_feature = fl.id_feature
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product =pfp.id_product ORDER BY  fl.id_feature ASC';
            /*

            $q['feature_shop'] =  'SELECT fl.* FROM ' . pSQL($this->tp) . 'feature_shop fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON pfp.id_feature = fl.id_feature
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product =pfp.id_product';

             */

            $q['feature_value'] = 'SELECT DISTINCT  fl.* FROM ' . pSQL($this->tp) . 'feature_value  fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON  pfp.id_feature_value = fl.id_feature_value
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product =pfp.id_product';

            $q['feature_value_lang'] = 'SELECT DISTINCT  fl.* FROM ' . pSQL($this->tp) . 'feature_value_lang   fl
                                    INNER JOIN ' . pSQL($this->tp) . 'feature_product pfp ON  pfp.id_feature_value = fl.id_feature_value
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product =pfp.id_product
                                     WHERE  id_lang IN (' . pSQL($this->languages) . ') ORDER BY fl.id_feature_value ASC';
            if ((int)$this->offset  == 0) {
                $q['supplier'] = 'SELECT DISTINCT * FROM ' . pSQL($this->tp) . 'supplier';

                /*
                $q['supplier_shop'] = 'SELECT s.* FROM ' . pSQL($this->tp) . 'supplier_shop s
                                    INNER JOIN ' . pSQL($this->tp) . 'product_supplier pps   ON pps.id_supplier = s.id_supplier
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product =pps.id_product';
                 */

                $q['supplier_lang'] = 'SELECT DISTINCT * FROM ' . pSQL($this->tp) . 'supplier_lang';
            }

            $q['customization_field_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'customization_field_lang  s
                                            INNER JOIN ' . pSQL($this->tp) . 'customization_field  pcf   ON pcf.id_customization_field = s.id_customization_field
                                            INNER JOIN (' . $main . ') pp ON pp.id_product = pcf.id_product
                                            WHERE  id_lang IN (' . pSQL($this->languages) . ')  ORDER BY s.id_customization_field ASC';

            $q['tag'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'tag   s
                        INNER JOIN ' . pSQL($this->tp) . 'product_tag ppt      ON ppt.id_tag = s.id_tag
                        INNER JOIN (' . $main . ')  pp ON pp.id_product =ppt.id_product';

            $q['image_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'image_lang   s
                                INNER JOIN ' . pSQL($this->tp) . 'image pi        ON pi.id_image = s.id_image
                                INNER JOIN (' . $main . ')  pp ON pp.id_product =pi.id_product WHERE id_lang IN ( ' . pSQL($this->languages) . ' )   ORDER BY s.id_image ASC';

            $q['attachment'] = 'SELECT DISTINCT  s.* FROM  ' . pSQL($this->tp) . 'attachment   s
                                INNER JOIN  ' . pSQL($this->tp) . 'product_attachment ppa   ON ppa.id_attachment = s.id_attachment
                                INNER JOIN (' . $main . ')  pp ON pp.id_product =ppa.id_product   ORDER BY s.id_attachment ASC ';

            $q['attachment_lang'] = 'SELECT DISTINCT  s.* FROM ' . pSQL($this->tp) . 'attachment_lang  s
                                INNER JOIN  ' . pSQL($this->tp) . 'product_attachment ppa   ON ppa.id_attachment = s.id_attachment
                                INNER JOIN (' . $main . ')  pp ON pp.id_product =ppa.id_product WHERE  id_lang IN ( ' . pSQL($this->languages) . ' )    ORDER BY s.id_attachment ASC ';

            #endregion

            #region Fourth related queries
            $q['attribute_group'] = 'SELECT DISTINCT  a.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'attribute_group   a ' . $versionQueryAG . '
                                    INNER JOIN ' . pSQL($this->tp) . 'attribute pa ON a.id_attribute_group = pa.id_attribute_group
                                    INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = pa.id_attribute
                                    INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                    INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product';

            $q['attribute_group_lang'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'attribute_group_lang   a
                                            INNER JOIN ' . pSQL($this->tp) . 'attribute pa ON a.id_attribute_group = pa.id_attribute_group
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = pa.id_attribute
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                            INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY a.id_attribute_group ASC';

            $q['attribute'] = 'SELECT DISTINCT  a.* '.$versionfield.'  FROM ' . pSQL($this->tp) . 'attribute a ' . $versionQueryA . '
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = a.id_attribute
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                            INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product';

            $q['attribute_lang'] = 'SELECT DISTINCT  a.* FROM ' . pSQL($this->tp) . 'attribute_lang  a
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute_combination ppac ON ppac.id_attribute = a.id_attribute
                                            INNER JOIN ' . pSQL($this->tp) . 'product_attribute ppa ON ppac.id_product_attribute = ppa.id_product_attribute
                                            INNER JOIN (' . $main . ')  pp ON pp.id_product = ppa.id_product WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY a.id_attribute ASC';

            #endregion
        }
        #region Configuration for grouped tables

        $groupedqueriesconfiguration = array();
        //For Product second related queries
        $groupedqueriesconfiguration['product_lang'] = 'id_product';
        $groupedqueriesconfiguration['product_carrier'] = 'id_product';
        $groupedqueriesconfiguration['stock_available'] = 'id_product';
        $groupedqueriesconfiguration['category_product'] = 'id_product';
        $groupedqueriesconfiguration['image'] = 'id_product';
        $groupedqueriesconfiguration['product_attribute'] = 'id_product';
        $groupedqueriesconfiguration['specific_price'] = 'id_product';
        $groupedqueriesconfiguration['product_download'] = 'id_product';
        $groupedqueriesconfiguration['product_attachment'] = 'id_product';
        $groupedqueriesconfiguration['product_supplier'] = 'id_product';
        $groupedqueriesconfiguration['customization_field'] = 'id_product';
        $groupedqueriesconfiguration['feature_product'] = 'id_product';
        $groupedqueriesconfiguration['product_tag'] = 'id_product';

        //For Product second third queries
        //Image

        $groupedqueriesconfiguration['image_lang'] = 'id_image';
        //Attribute
        $groupedqueriesconfiguration['product_attribute_combination'] = 'id_product_attribute';
        $groupedqueriesconfiguration['product_attribute_image'] = 'id_product_attribute';
        //Attachment
        $groupedqueriesconfiguration['attachment_lang'] = 'id_attachment';
        //Customization Field
        $groupedqueriesconfiguration['customization_field_lang'] = 'id_customization_field';
        //Supplier Lang
        $groupedqueriesconfiguration['supplier_lang'] = 'id_supplier';
        //Attribute Group Lang
        $groupedqueriesconfiguration['attribute_group_lang'] = 'id_attribute_group';
        //Attribute Lang
        $groupedqueriesconfiguration['attribute_lang'] = 'id_attribute';
        //Feature Lang
        $groupedqueriesconfiguration['feature_lang'] = 'id_feature';
        //Feature Value Lang
        $groupedqueriesconfiguration['feature_value_lang'] = 'id_feature_value';

        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        #endregion
        return $q;
    }
    public function singleProduct($id_product)
    {
        return 'SELECT * FROM ' . pSQL($this->tp) . 'product WHERE id_product = ' . (int)$id_product;
    }

    public function specificPriceRule()
    {
        $q = array();

        $q['specificPriceRule'] = 'SELECT * FROM ' . pSQL($this->tp) . 'specific_price_rule ORDER BY id_specific_price_rule ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $versionQuery = ' LEFT  JOIN (SELECT s.id_country, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                          FROM ' . pSQL($this->tp) . 'country_shop s
                          GROUP BY s.id_country) tgs ON tgs.id_country = c.id_country ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionQuery = "";
        }
        // main&general query
        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'specific_price_rule ORDER BY id_specific_price_rule ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['country'] = 'SELECT DISTINCT c.* FROM ' . pSQL($this->tp) . 'country c '. $versionQuery .' INNER JOIN (' . $main . ')  sp ON c.id_country = sp.id_country';

        $q['country_lang'] = 'SELECT DISTINCT c.* FROM ' . pSQL($this->tp) . 'country_lang  c
                                INNER JOIN (' . $main . ') sp ON c.id_country = sp.id_country
                                WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY c.id_country ASC';

        $q['specificPriceRuleConditionGroup'] = 'SELECT DISTINCT c.* FROM ' . pSQL($this->tp) . 'specific_price_rule_condition_group  c
                                                INNER JOIN (' . $main . ') sp ON c.id_specific_price_rule = sp.id_specific_price_rule
                                                ORDER BY c.id_specific_price_rule ASC';

        $q['specificPriceRuleCondition'] = 'SELECT  DISTINCT cn.*  FROM ' . pSQL($this->tp) . 'specific_price_rule_condition cn
                                            INNER JOIN ' . pSQL($this->tp) . 'specific_price_rule_condition_group  c  ON c.id_specific_price_rule_condition_group = cn.id_specific_price_rule_condition_group
                                            INNER JOIN (' . $main . ') sp ON c.id_specific_price_rule = sp.id_specific_price_rule
                                            ORDER BY cn.id_specific_price_rule_condition_group ASC';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['country_lang'] = 'id_country';
        $groupedqueriesconfiguration['specificPriceRuleConditionGroup'] = 'id_specific_price_rule';
        $groupedqueriesconfiguration['specificPriceRuleCondition'] = 'id_specific_price_rule_condition_group';

        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- product accessories method:
    public function accessories()
    {
        return 'SELECT * FROM ' . pSQL($this->tp) . 'accessory  LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
    }
    // --- Employee method:
    public function employee()
    {
        $versionfiled = ", tgs.id_shop_list";
        $versionQuery = ' LEFT JOIN (SELECT s.id_employee,
                    GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                    FROM ' . pSQL($this->tp) . 'employee_shop  s
                    GROUP BY s.id_employee) tgs ON tgs.id_employee = e.id_employee ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionQuery = "";
            $versionfiled = "";
        }
        return 'SELECT DISTINCT e.* ' . $versionfiled. '   FROM ' . pSQL($this->tp) . 'employee e '. $versionQuery .'
                ORDER BY e.id_employee ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
    }

    // --- Order method:
    public function order()
    {
        $q = array();
        $last_migrated_order_id = MigrationProMigratedData::getLastId('order');
        $versionfield = ", ptrgs.id_shop_list";
        $versionQuery = ' LEFT  JOIN
                            (SELECT s.id_country, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                           FROM ' . pSQL($this->tp) . 'country_shop  s
                           GROUP BY s.id_country) ptrgs ON ptrgs.id_country = m.id_country ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionQuery = "";
            $versionfield = "";
        }
        // main&general query
        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'orders WHERE id_order != 0 ORDER BY id_order ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        $main_recent_data = 'SELECT * FROM ' . pSQL($this->tp) . 'orders WHERE id_order != 0  AND `id_order` > ' . (int)$last_migrated_order_id . ' ORDER BY id_order  ASC LIMIT ' . (int)$this->row_count;

        if ($this->recent_data) {
            $q['order'] = 'SELECT * FROM ' . pSQL($this->tp) . 'orders WHERE id_order != 0  AND `id_order` > ' . (int)$last_migrated_order_id . ' ORDER BY id_order ASC LIMIT ' . (int)$this->row_count;

            #region Second related queries
            $q['order_detail'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_detail  m INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_detail_tax'] = 'SELECT DISTINCT mt.* FROM ' . pSQL($this->tp) . 'order_detail_tax mt
                                   INNER JOIN ' . pSQL($this->tp) . 'order_detail  m ON m.id_order_detail = mt.id_order_detail
                                   INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY mt.id_order_detail ASC';

            $q['message'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'message  m INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['message_readed'] = 'SELECT DISTINCT mr.* FROM ' . pSQL($this->tp) . 'message_readed mr
                           INNER JOIN ' . pSQL($this->tp) . 'message m ON   m.id_message = mr.id_message
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_message ASC';

            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $q['order_cart_rule'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_discount  m
                                       INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

                $q['order_payment'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'payment_cc  m
                                       INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';
            } else {
                $q['order_cart_rule'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_cart_rule  m
                                       INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

                $q['order_payment'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_payment  m
                                       INNER JOIN (' . $main_recent_data . ') o ON o.reference = m.order_reference ORDER BY m.order_reference ASC';
            }
            $q['order_invoice'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_invoice  m
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['invoice_tax'] = 'SELECT DISTINCT oi.* FROM ' . pSQL($this->tp) . 'order_invoice_tax oi
                                   INNER JOIN ' . pSQL($this->tp) . 'order_invoice m ON m.id_order_invoice = oi.id_order_invoice
                                   INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY oi.id_order_invoice ASC';

            $q['invoice_payment'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_invoice_payment  m
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_slip'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_slip  m
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_slip_detail'] = 'SELECT DISTINCT md.* FROM ' . pSQL($this->tp) . 'order_slip_detail md
                           INNER JOIN ' . pSQL($this->tp) . 'order_slip  m ON m.id_order_slip = md.id_order_slip
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order_slip ASC';

            $q['order_slip_detail_tax'] = 'SELECT * FROM ' . pSQL($this->tp) . 'order_slip_detail';

            $q['order_history'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_history  m
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order, m.date_add  ASC';

            $q['order_return'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_return  m INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_return_detail'] = 'SELECT DISTINCT md.* FROM ' . pSQL($this->tp) . 'order_return_detail md
                           INNER JOIN ' . pSQL($this->tp) . 'order_return m ON m.id_order_return = md.id_order_return
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order_return ASC';

            $q['order_carrier'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_carrier  m INNER JOIN (' . $main_recent_data . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['address'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'address  m
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_address_delivery = m.id_address ORDER BY m.id_address ASC';

            $q['country'] = 'SELECT DISTINCT c.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'country c
                           INNER JOIN ' . pSQL($this->tp) . 'address  m ON m.id_country = c.id_country ' . $versionQuery . '
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_address_delivery = m.id_address ORDER BY m.id_country ASC';

            $q['country_lang'] = 'SELECT DISTINCT c.*  FROM ' . pSQL($this->tp) . 'country_lang c
                           INNER JOIN ' . pSQL($this->tp) . 'address  m ON m.id_country = c.id_country
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_address_delivery = m.id_address WHERE  c.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY m.id_country ASC';

            $q['state'] = 'SELECT DISTINCT c.*  FROM ' . pSQL($this->tp) . 'state c INNER JOIN ' . pSQL($this->tp) . 'address  m ON m.id_state = c.id_state
                           INNER JOIN (' . $main_recent_data . ') o ON o.id_address_delivery = m.id_address ORDER BY m.id_state ASC';

            $q['order_message'] = 'SELECT * FROM ' . pSQL($this->tp) . 'order_message';
            $q['order_message_lang'] = 'SELECT * FROM ' . pSQL($this->tp) . 'order_message_lang';
        } else {
            $q['order'] = 'SELECT * FROM ' . pSQL($this->tp) . 'orders WHERE id_order != 0 ORDER BY id_order ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count;

            #region Second related queries
            $q['order_detail'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_detail  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_detail_tax'] = 'SELECT DISTINCT mt.* FROM ' . pSQL($this->tp) . 'order_detail_tax mt
                                    INNER JOIN ' . pSQL($this->tp) . 'order_detail  m ON m.id_order_detail = mt.id_order_detail
                                    INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY mt.id_order_detail ASC';

            $q['message'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'message  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['message_readed'] = 'SELECT DISTINCT mr.* FROM ' . pSQL($this->tp) . 'message_readed mr 
                            INNER JOIN ' . pSQL($this->tp) . 'message m ON   m.id_message = mr.id_message
                            INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_message ASC';

            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $q['order_cart_rule'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_discount  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

                $q['order_payment'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'payment_cc  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';
            } else {
                $q['order_cart_rule'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_cart_rule  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

                $q['order_payment'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_payment  m
                                        INNER JOIN (' . $main . ') o ON o.reference = m.order_reference ORDER BY m.order_reference ASC';
            }
            $q['order_invoice'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_invoice  m
                            INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['invoice_tax'] = 'SELECT DISTINCT oi.* FROM ' . pSQL($this->tp) . 'order_invoice_tax oi
                                    INNER JOIN ' . pSQL($this->tp) . 'order_invoice m ON m.id_order_invoice = oi.id_order_invoice
                                    INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY oi.id_order_invoice ASC';

            $q['invoice_payment'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_invoice_payment  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_slip'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_slip  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_slip_detail'] = 'SELECT DISTINCT md.* FROM ' . pSQL($this->tp) . 'order_slip_detail md
                            INNER JOIN ' . pSQL($this->tp) . 'order_slip  m ON m.id_order_slip = md.id_order_slip
                            INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order_slip ASC';

            $q['order_slip_detail_tax'] = 'SELECT * FROM ' . pSQL($this->tp) . 'order_slip_detail';

            $q['order_history'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_history  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order, m.date_add  ASC';

            $q['order_return'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_return  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['order_return_detail'] = 'SELECT DISTINCT md.* FROM ' . pSQL($this->tp) . 'order_return_detail md
                            INNER JOIN ' . pSQL($this->tp) . 'order_return m ON m.id_order_return = md.id_order_return
                            INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order_return ASC';

            $q['order_carrier'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'order_carrier  m INNER JOIN (' . $main . ') o ON o.id_order = m.id_order ORDER BY m.id_order ASC';

            $q['address'] = 'SELECT DISTINCT m.* FROM ' . pSQL($this->tp) . 'address  m INNER JOIN (' . $main . ') o ON o.id_address_delivery = m.id_address ORDER BY m.id_address ASC';

            $q['country'] = 'SELECT DISTINCT c.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'country c
                            INNER JOIN ' . pSQL($this->tp) . 'address  m ON m.id_country = c.id_country '. $versionQuery . '
                            INNER JOIN (' . $main . ') o ON o.id_address_delivery = m.id_address ORDER BY m.id_country ASC';

            $q['country_lang'] = 'SELECT DISTINCT c.*  FROM ' . pSQL($this->tp) . 'country_lang c
                            INNER JOIN ' . pSQL($this->tp) . 'address  m ON m.id_country = c.id_country
                            INNER JOIN (' . $main . ') o ON o.id_address_delivery = m.id_address WHERE  c.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY m.id_country ASC';

            $q['state'] = 'SELECT DISTINCT c.*  FROM ' . pSQL($this->tp) . 'state c
                            INNER JOIN ' . pSQL($this->tp) . 'address  m ON m.id_state = c.id_state
                            INNER JOIN (' . $main . ') o ON o.id_address_delivery = m.id_address ORDER BY m.id_state ASC';

            $q['order_message'] = 'SELECT * FROM ' . pSQL($this->tp) . 'order_message';
            $q['order_message_lang'] = 'SELECT * FROM ' . pSQL($this->tp) . 'order_message_lang';
        }


        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['order_detail'] = 'id_order';
        $groupedqueriesconfiguration['message'] = 'id_order';
        $groupedqueriesconfiguration['order_detail_tax'] = 'id_order_detail';

        if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
            $groupedqueriesconfiguration['order_payment'] = 'id_order';
        } else {
            $groupedqueriesconfiguration['order_payment'] = 'order_reference';
        }
        
        $groupedqueriesconfiguration['order_invoice'] = 'id_order';
        $groupedqueriesconfiguration['invoice_tax'] = 'id_order_invoice';
        $groupedqueriesconfiguration['invoice_payment'] = 'id_order';
        $groupedqueriesconfiguration['order_slip'] = 'id_order';
        $groupedqueriesconfiguration['order_slip_detail'] = 'id_order_slip';
        $groupedqueriesconfiguration['order_history'] = 'id_order';
        $groupedqueriesconfiguration['order_return'] = 'id_order';
        $groupedqueriesconfiguration['order_return_detail'] = 'id_order_return';
        $groupedqueriesconfiguration['order_carrier'] = 'id_order';
        $groupedqueriesconfiguration['order_cart_rule'] = 'id_order';
        $groupedqueriesconfiguration['address'] = 'id_address';
        $groupedqueriesconfiguration['country'] = 'id_order';
        $groupedqueriesconfiguration['country_lang'] = 'id_country';
        $groupedqueriesconfiguration['state'] = 'id_state';
        $groupedqueriesconfiguration['message_readed'] = 'id_message';
        $groupedqueriesconfiguration['order_message_lang'] = 'id_order_message';

        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }
    public function customerThreads()
    {
        // main&general query
        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'customer_thread ORDER BY id_customer_thread ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q = array();
        $q['customerThreads'] = 'SELECT * FROM ' . pSQL($this->tp) . 'customer_thread ORDER BY id_customer_thread ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['customerMessages'] = 'SELECT DISTINCT  * FROM ' . pSQL($this->tp) . 'customer_message m
                                    INNER JOIN ( ' . $main . ') AS t ON m.id_customer_thread = t.id_customer_thread ORDER BY m.id_customer_thread ASC';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['customerMessages'] = 'id_customer_thread';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        return $q;
    }

    // --- Customer methods:
    public function customers()
    {
        $last_migrated_customer_id = MigrationProMigratedData::getLastId('customer');
        $versionfieldCountry = version_compare(Configuration::get('migrationpro_version'), '1.5', '<') ? "" : ", tgs.id_shop_list ";
        $versionQueryCountry = version_compare(Configuration::get('migrationpro_version'), '1.5', '<') ? '' : ' LEFT  JOIN 
                          (SELECT s.id_country, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                          FROM ' . pSQL($this->tp) . 'country_shop s
                          GROUP BY s.id_country) tgs ON tgs.id_country = c.id_country ';

        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'customer WHERE id_customer != 0  AND ( deleted IS NULL OR deleted = 0)
                                ORDER BY id_customer ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count;
        $main_recent_data = 'SELECT * FROM ' . pSQL($this->tp) . 'customer WHERE id_customer != 0
                                AND `id_customer` > ' . (int)$last_migrated_customer_id . '  AND ( deleted IS NULL OR deleted = 0) ORDER BY id_customer ASC LIMIT ' . (int)$this->row_count;
        $q = array();
        if ($this->recent_data) {
            $q['customers'] = 'SELECT * FROM ' . pSQL($this->tp) . 'customer WHERE id_customer != 0 AND `id_customer` > ' . (int)$last_migrated_customer_id . '  AND ( deleted IS NULL OR deleted = 0)
                                ORDER BY id_customer ASC LIMIT ' . (int)$this->row_count;

            $q['address'] = 'SELECT a.*, z.iso_code as zone_code, c.iso_code as country_code FROM ' . pSQL($this->tp) . 'address AS a
                                INNER JOIN (' . $main_recent_data . ') cus ON cus.id_customer = a.id_customer
                                LEFT JOIN ' . pSQL($this->tp) . 'country AS c ON a.id_country = c.id_country
                                LEFT JOIN ' . pSQL($this->tp) . 'state AS z ON a.id_state = z.id_state ORDER BY a.id_customer  ASC';

            $q['customer_group'] = 'SELECT a.* FROM ' . pSQL($this->tp) . 'customer_group AS a
                                INNER JOIN (' . $main_recent_data . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_customer ASC';

            $q['cart'] = 'SELECT a.* FROM ' . pSQL($this->tp) . 'cart AS a INNER JOIN (' . $main_recent_data . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_customer ASC';

            $q['cart_product'] = 'SELECT a.*  FROM ' . pSQL($this->tp) . 'cart_product a
                                INNER JOIN ' . pSQL($this->tp) . 'cart AS c ON c.id_cart = a.id_cart
                                INNER JOIN (' . $main_recent_data . ') cus ON cus.id_customer = c.id_customer ORDER BY a.id_cart ASC';

            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $q['cart_cart_rule'] = 'SELECT a.*  FROM ' . pSQL($this->tp) . 'cart_discount a
                                INNER JOIN ' . pSQL($this->tp) . 'cart AS c ON c.id_cart = a.id_cart
                                INNER JOIN (' . $main_recent_data . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_cart ASC';
            } else {
                $q['cart_cart_rule'] = 'SELECT a.*  FROM ' . pSQL($this->tp) . 'cart_cart_rule a
                                INNER JOIN ' . pSQL($this->tp) . 'cart AS c ON c.id_cart = a.id_cart
                                INNER JOIN (' . $main_recent_data . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_cart ASC';
            }
            $q['country'] = 'SELECT c.* ' . $versionfieldCountry . '  FROM ' . pSQL($this->tp) . 'country `c` 
                            INNER JOIN ' . pSQL($this->tp) . 'address a ON c.id_country = a.id_country ' . $versionQueryCountry . '
                            INNER JOIN (' .$main_recent_data . ') cus ON cus.id_customer = a.id_customer';

            $q['country_lang'] = 'SELECT cl.* FROM ' . pSQL($this->tp) . 'country_lang cl
                                INNER JOIN ' . pSQL($this->tp) . 'country `c` ON cl.id_country = `c`.id_country
                                INNER JOIN ' . pSQL($this->tp) . 'address a ON c.id_country = a.id_country ' . $versionQueryCountry . '
                                INNER JOIN (' .$main_recent_data . ') cus ON cus.id_customer = a.id_customer
                                WHERE cl.id_lang IN ( ' . pSQL($this->languages) . ' )';

            $q['state'] = 'SELECT s.* FROM ' . pSQL($this->tp) . 'state s
                            INNER JOIN ' . pSQL($this->tp) . 'address a ON s.id_state = a.id_state 
                            INNER JOIN (' .$main_recent_data . ') cus ON cus.id_customer = a.id_customer';
        } else {
            $q['customers'] = 'SELECT * FROM ' . pSQL($this->tp) . 'customer WHERE id_customer != 0  AND ( deleted IS NULL OR deleted = 0) ORDER BY id_customer ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

            $q['address'] = 'SELECT a.*, z.iso_code as zone_code, c.iso_code as country_code FROM ' . pSQL($this->tp) . 'address AS a
                                INNER JOIN (' . $main . ') cus ON cus.id_customer = a.id_customer
                                LEFT JOIN ' . pSQL($this->tp) . 'country AS c ON a.id_country = c.id_country
                                LEFT JOIN ' . pSQL($this->tp) . 'state AS z ON a.id_state = z.id_state ORDER BY a.id_customer  ASC';

            $q['customer_group'] = 'SELECT a.* FROM ' . pSQL($this->tp) . 'customer_group AS a INNER JOIN (' . $main . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_customer  ASC';

            $q['cart'] = 'SELECT a.* FROM ' . pSQL($this->tp) . 'cart AS a INNER JOIN (' . $main . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_customer  ASC';

            $q['cart_product'] = 'SELECT a.*  FROM ' . pSQL($this->tp) . 'cart_product a
                                INNER JOIN ' . pSQL($this->tp) . 'cart AS c ON c.id_cart = a.id_cart
                                INNER JOIN (' . $main . ') cus ON cus.id_customer = c.id_customer ORDER BY a.id_cart  ASC';

            if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
                $q['cart_cart_rule'] = 'SELECT a.*  FROM ' . pSQL($this->tp) . 'cart_discount a
                                INNER JOIN ' . pSQL($this->tp) . 'cart AS c ON c.id_cart = a.id_cart
                                INNER JOIN (' . $main . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_cart  ASC';
            } else {
                $q['cart_cart_rule'] = 'SELECT a.*  FROM ' . pSQL($this->tp) . 'cart_cart_rule a
                                INNER JOIN ' . pSQL($this->tp) . 'cart AS c ON c.id_cart = a.id_cart
                                INNER JOIN (' . $main . ') cus ON cus.id_customer = a.id_customer ORDER BY a.id_cart  ASC';
            }
            $q['country'] = 'SELECT c.* ' . $versionfieldCountry . '  FROM ' . pSQL($this->tp) . 'country `c` 
                            INNER JOIN ' . pSQL($this->tp) . 'address a ON c.id_country = a.id_country ' . $versionQueryCountry . '
                            INNER JOIN (' .$main . ') cus ON cus.id_customer = a.id_customer';

            $q['country_lang'] = 'SELECT cl.* FROM ' . pSQL($this->tp) . 'country_lang cl
                                INNER JOIN ' . pSQL($this->tp) . 'country `c` ON cl.id_country = `c`.id_country
                                INNER JOIN ' . pSQL($this->tp) . 'address a ON c.id_country = a.id_country ' . $versionQueryCountry . '
                                INNER JOIN (' .$main . ') cus ON cus.id_customer = a.id_customer
                                WHERE cl.id_lang IN ( ' . pSQL($this->languages) . ' )';

            $q['state'] = 'SELECT s.* FROM ' . pSQL($this->tp) . 'state s
                            INNER JOIN ' . pSQL($this->tp) . 'address a ON s.id_state = a.id_state 
                            INNER JOIN (' .$main . ') cus ON cus.id_customer = a.id_customer';
        }

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['address'] = 'id_customer';
        $groupedqueriesconfiguration['customer_group'] = 'id_customer';
        //$groupedqueriesconfiguration['cart'] = 'id_customer';
        $groupedqueriesconfiguration['cart_product'] = 'id_cart';
        $groupedqueriesconfiguration['cart_cart_rule'] = 'id_cart';
        $groupedqueriesconfiguration['country_lang'] = 'id_country';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- CMS method:
    public function cms()
    {
        $q = array();
        $versionfield = ", ptrgs.id_shop_list ";
        $versionQuery = ' LEFT  JOIN
                    (SELECT s.id_cms, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                    FROM ' . pSQL($this->tp) . 'cms_shop  s GROUP BY s.id_cms) ptrgs ON ptrgs.id_cms = c.id_cms ';
        $versionQuerySC = ' LEFT  JOIN
                                (SELECT s.id_cms_category, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                                FROM ' . pSQL($this->tp) . 'cms_category_shop  s
                                GROUP BY s.id_cms_category) ptrgs ON ptrgs.id_cms_category = cl.id_cms_category ';
        $versionQuerySB = ' LEFT  JOIN
                                (SELECT s.id_cms_block, GROUP_CONCAT(id_shop SEPARATOR \',\') AS id_shop_list
                                FROM ' . pSQL($this->tp) . 'cms_block_shop  s
                                GROUP BY s.id_cms_block) ptrgs ON ptrgs.id_cms_block = cl.id_cms_block ';
        if (version_compare($this->version, '1.5', '<')) {
            $versionQuery = "";
            $versionfield = "";
            $versionQuerySC = "";
            $versionQuerySB = "";
        }
        // main&general query
        $main = 'SELECT * FROM ' . pSQL($this->tp) . 'cms cc ORDER BY cc.id_cms ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['cms'] = 'SELECT c.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'cms c '.$versionQuery.' ORDER BY id_cms ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        #region Cms second related queries
        $q['cms_lang'] = 'SELECT DISTINCT cl.* FROM ' . pSQL($this->tp) . 'cms_lang  cl
                         INNER JOIN  (' . $main . ') c ON c.id_cms = cl.id_cms WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY cl.id_cms';

        $q['cms_role'] = 'SELECT DISTINCT cl.* FROM ' . pSQL($this->tp) . 'cms_role  cl INNER JOIN  (' . $main . ') c ON c.id_cms = cl.id_cms ORDER BY cl.id_cms';

        $q['cms_category'] = 'SELECT DISTINCT cl.* ' . $versionfield . ' FROM ' . pSQL($this->tp) . 'cms_category  cl '. $versionQuerySC .' 
                           INNER JOIN  (' . $main . ') c ON c.id_cms_category = cl.id_cms_category ORDER BY cl.id_cms_category';

        $q['cms_block'] = 'SELECT DISTINCT cl.* ' . $versionfield . '  FROM ' . pSQL($this->tp) . 'cms_block  cl '. $versionQuerySB .'
                           INNER JOIN  (' . $main . ') c ON c.id_cms_category = cl.id_cms_category ORDER BY cl.id_cms_category';

        #endregion

        #region Cms third related queries
        $q['cms_role_lang'] = 'SELECT DISTINCT rl.* FROM ' . pSQL($this->tp) . 'cms_role_lang rl
                                INNER JOIN ' . pSQL($this->tp) . 'cms_role  cl ON rl.id_cms_role = cl.id_cms_role
                                INNER JOIN  (' . $main . ') c ON c.id_cms = cl.id_cms WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY cl.id_cms_role';

        $q['cms_category_lang'] = 'SELECT DISTINCT cl.* FROM ' . pSQL($this->tp) . 'cms_category_lang  cl
                                  INNER JOIN  (' . $main . ') c ON c.id_cms_category = cl.id_cms_category
                                  WHERE  id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY cl.id_cms_category';

        $q['cms_block_lang'] = 'SELECT DISTINCT cl.*  FROM ' . pSQL($this->tp) . 'cms_block_lang bl
                                   INNER JOIN ' . pSQL($this->tp) . 'cms_block  cl ON bl.id_cms_block = cl.id_cms_block
                                   INNER JOIN  (' . $main . ') c ON c.id_cms_category = cl.id_cms_category WHERE bl.id_lang IN ( ' . pSQL($this->languages) . ' )';

        $q['cms_block_page'] = 'SELECT DISTINCT cl.*  FROM ' . pSQL($this->tp) . 'cms_block_page cl INNER JOIN  (' . $main . ') c ON c.id_cms = cl.id_cms ORDER BY cl.id_cms';

        #endregion
        $groupedqueriesconfiguration = array();

        $groupedqueriesconfiguration['cms_lang'] = 'id_cms';
        $groupedqueriesconfiguration['cms_role'] = 'id_cms';
//        $groupedqueriesconfiguration['cms_category'] = 'id_cms_category';
        $groupedqueriesconfiguration['cms_block'] = 'id_cms_category';
        $groupedqueriesconfiguration['cms_category_lang'] = 'id_cms_category';
        $groupedqueriesconfiguration['cms_role_lang'] = 'id_cms_role';
        $groupedqueriesconfiguration['cms_block_page'] = 'id_cms';

        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        return $q;
    }

    // --- Cart Rule methods:
    public function cartRule()
    {
        // main&general query
        $main = 'SELECT cr.id_cart_rule FROM ' . pSQL($this->tp) . 'cart_rule cr
                                    ORDER BY cr.id_cart_rule ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        $q = array();
        if (version_compare(Configuration::get('migrationpro_version'), '1.5', '<')) {
            $q['cartRule'] = 'SELECT cr.* FROM  ' . pSQL($this->tp) . 'discount cr
                                ORDER BY cr.id_discount ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

            $q['cart_rule_langs'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'discount_lang   crl
                                    INNER JOIN (SELECT cr.id_discount  FROM  ' . pSQL($this->tp) . 'discount cr
                                    ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = crl.id_discount
                                    WHERE  crl.id_lang IN ( ' . pSQL($this->languages) . ' )
                                    ORDER BY crl.id_discount ASC';

            $q['cart_rule_carriers'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_carrier   crl
                                    INNER JOIN (SELECT cr.id_discount  FROM  ' . pSQL($this->tp) . 'cart_rule cr
                                    ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = crl.id_cart_rule';

            $q['cart_rule_combinations'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_combination   crl
                                        INNER JOIN (SELECT cr.id_discount  FROM  ' . pSQL($this->tp) . 'cart_rule cr
                                        ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = crl.id_cart_rule_1
                                    ORDER BY crl.id_cart_rule_1 ASC';

            $q['cart_rule_countries'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_country   crl
                                     INNER JOIN (SELECT cr.id_discount  FROM  ' . pSQL($this->tp) . 'cart_rule cr
                                     ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = crl.id_cart_rule
                                    ORDER BY crl.id_cart_rule ASC';

            $q['cart_rule_groups'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_group   crl
                                  INNER JOIN (SELECT cr.id_discount  FROM  ' . pSQL($this->tp) . 'cart_rule cr
                                  ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = crl.id_cart_rule
                                    ORDER BY crl.id_cart_rule ASC';

            $q['cart_rule_product_rule_groups'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_product_rule_group   crl
                                              INNER JOIN (SELECT cr.id_discount  FROM  ' . pSQL($this->tp) . 'cart_rule cr
                                              ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = crl.id_cart_rule
                                    ORDER BY crl.id_cart_rule ASC';

            $q['cart_rule_product_rule'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_product_rule crl
                                         INNER JOIN  ' . pSQL($this->tp) . 'cart_rule_product_rule_group   g  ON g.id_product_rule_group = crl.id_product_rule_group
                                         INNER JOIN (SELECT cr.id_discount  FROM   ' . pSQL($this->tp) . 'cart_rule cr
                                         ORDER BY cr.id_discount ASC LIMIT   ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = g.id_cart_rule
                                         ORDER BY crl.id_product_rule_group ASC';

            $q['cart_rule_product_rule_value'] = 'SELECT DISTINCT v.* FROM  ' . pSQL($this->tp) . 'cart_rule_product_rule_value v
                                                INNER JOIN   ' . pSQL($this->tp) . 'cart_rule_product_rule crl ON v.id_product_rule = crl.id_product_rule
                                                INNER JOIN  ' . pSQL($this->tp) . 'cart_rule_product_rule_group   g  ON g.id_product_rule_group = crl.id_product_rule_group
                                                INNER JOIN (SELECT cr.id_discount  FROM   ' . pSQL($this->tp) . 'cart_rule cr
                                                ORDER BY cr.id_discount ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . ') cr  ON cr.id_discount = g.id_cart_rule
                                                ORDER BY v.id_product_rule ASC';

            $groupedqueriesconfiguration = array();

            $groupedqueriesconfiguration['cart_rule_langs'] = 'id_discount';
            $groupedqueriesconfiguration['cart_rule_carriers'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_combinations'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_countries'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_groups'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_product_rule_groups'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_product_rule'] = 'id_product_rule_group';
            $groupedqueriesconfiguration['cart_rule_product_rule_value'] = 'id_product_rule';

            $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        } else {
            $q['cartRule'] = 'SELECT  DISTINCT  cr.*, ptrgs.id_shop_list FROM ' . pSQL($this->tp) . 'cart_rule cr
                                    LEFT JOIN (SELECT s.id_cart_rule, GROUP_CONCAT(s.id_shop SEPARATOR \',\') AS id_shop_list
                                    FROM ' . pSQL($this->tp) . 'cart_rule_shop s
                                    GROUP BY s.id_cart_rule) ptrgs ON ptrgs.id_cart_rule = cr.id_cart_rule
                                ORDER BY cr.id_cart_rule ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

            $q['cart_rule_langs'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_lang   crl
                                    INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule
                                    WHERE  crl.id_lang IN ( ' . pSQL($this->languages) . ' ) ORDER BY crl.id_cart_rule ASC ';

            $q['cart_rule_carriers'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_carrier crl INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule';

            $q['cart_rule_combinations'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_combination crl INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule_1';

            $q['cart_rule_countries'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_country crl INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule';

            $q['cart_rule_groups'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_group crl INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule';

            $q['cart_rule_product_rule_groups'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_product_rule_group crl INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule';

            $q['cart_rule_product_rule_groups'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_product_rule_group crl INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = crl.id_cart_rule';

            $q['cart_rule_product_rule'] = 'SELECT DISTINCT crl.* FROM  ' . pSQL($this->tp) . 'cart_rule_product_rule crl
                                         INNER JOIN  ' . pSQL($this->tp) . 'cart_rule_product_rule_group   g  ON g.id_product_rule_group = crl.id_product_rule_group
                                         INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = g.id_cart_rule';

            $q['cart_rule_product_rule_value'] = 'SELECT DISTINCT v.* FROM ' . pSQL($this->tp) . 'cart_rule_product_rule_value v
                                                INNER JOIN   ' . pSQL($this->tp) . 'cart_rule_product_rule crl ON v.id_product_rule = crl.id_product_rule
                                                INNER JOIN  ' . pSQL($this->tp) . 'cart_rule_product_rule_group   g  ON g.id_product_rule_group = crl.id_product_rule_group
                                                INNER JOIN (' . $main . ') cr  ON cr.id_cart_rule = g.id_cart_rule';

            $groupedqueriesconfiguration = array();

            $groupedqueriesconfiguration['cart_rule_langs'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_carriers'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_combinations'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_countries'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_groups'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_product_rule_groups'] = 'id_cart_rule';
            $groupedqueriesconfiguration['cart_rule_product_rule'] = 'id_product_rule_group';
            $groupedqueriesconfiguration['cart_rule_product_rule_value'] = 'id_product_rule';

            $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        }
        return $q;
    }

    // --- Meta methods:
    public function meta()
    {
        // main&general query
        $main = 'SELECT id_meta FROM ' . pSQL($this->tp) . 'meta ORDER BY id_meta LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q = array();
        $q['meta'] = 'SELECT * FROM ' . pSQL($this->tp) . 'meta ORDER BY id_meta ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['meta_lang'] = 'SELECT DISTINCT ml.* FROM ' . pSQL($this->tp) . 'meta_lang ml
                            INNER JOIN (' . $main . ') AS m ON ml.id_meta = m.id_meta WHERE   ml.id_lang IN ( ' . pSQL($this->languages) . ') ORDER BY ml.id_meta ASC ';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['meta_lang'] = 'id_meta';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;
        return $q;
    }
}
