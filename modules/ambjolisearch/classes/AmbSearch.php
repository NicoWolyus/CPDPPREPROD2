<?php
/**
 *   AmbJoliSearch Module : Search for prestashop
 *
 *   @author    Ambris Informatique
 *   @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *   @license   Commercial license
 *   @module     Advanced search (AmbJoliSearch)
 *   @file       ambjolisearch.php
 *   @subject    script principal pour gestion du module (install/config/hook)
 *   Support by mail: support@ambris.com
 */

class AmbSearch
{
    public $id_lang;
    public $expr;
    public $page_number;
    public $limit;
    public $order_by;
    public $order_way;
    public $context;
    public $db;
    public $mode = 'normal';
    public $id_customer;
    public $ajax;

    public $where;
    public $having;
    public $nb = 0;

    public $main_order_by = '';

    protected $results;
    protected $product_ids = array();

    public $words = array();

    public function __construct($use_cookie, $context, $module)
    {
        $this->module = $module;
        $this->db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        if (!$context) {
            $this->context = Context::getContext();
        } else {
            $this->context = $context;
        }

        if ($use_cookie) {
            $this->id_customer = $this->context->customer->id;
        } else {
            $this->id_customer = 0;
        }
    }

    public function search($id_lang, $expr, $page_number, $limit, $order_by, $order_way)
    {
        static $findCache = array();

        $this->id_lang = $id_lang;
        $this->iso_lang = Language::getIsoById($this->id_lang);
        $this->expr = $expr;
        $this->page_number = $page_number;
        $this->limit = $limit;
        $this->order_by = $order_by;
        if (strpos($this->order_by, '.') > 0) {
            $this->order_by = explode('.', $this->order_by);
            $this->order_by = pSQL($this->order_by[0]) . '.`' . pSQL($this->order_by[1]) . '`';
        }
        $this->order_way = $order_way;

        // use cache only after object attributes are initialized (issue when differents languages are used)
        $cacheKey = sha1(serialize(func_get_args()));
        if (isset($findCache[$cacheKey])) {
            $this->product_ids = $findCache[$cacheKey];
            return;
        }

        if (!Validate::isOrderBy($this->order_by) || !Validate::isOrderWay($this->order_way)) {
            return;
        }

        $this->words = explode(' ', Search::sanitize($this->expr, $this->id_lang, false, $this->iso_lang));

        if (count($this->words) > 1) {
            $this->words['concat'] = str_replace(' ', '', Search::sanitize($this->expr, $this->id_lang, false, $this->iso_lang));
        }

        $alias = '';
        $need_name = false;

        if ($this->order_by == 'price') {
            $alias = 'product_shop.';
        }

        if ($this->order_by == 'name') {
            $need_name = true;
            $alias = 'pl.';
        }

        // if ($this->order_by == 'date_add' || $this->order_by == 'date_upd') {
        //     $need_name = true;
        //     $alias = 'product_shop.';
        // }


        $this->main_order_by = ($this->order_by == 'position') ? 'ORDER BY position ' . ($this->order_way ? ' ' . $this->order_way : '') : ($this->order_by ? 'ORDER BY  ' . $alias . $this->order_by : '') . ($this->order_way ? ' ' . $this->order_way : '');

        $word_conditions = array();
        $check_terms = array();

        $eligible_products_request = '
                SELECT
                DISTINCT cp.`id_product`
                FROM `' . _DB_PREFIX_ . 'category_group` cg
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.`id_category` = cg.`id_category`
                INNER JOIN `' . _DB_PREFIX_ . 'category` c ON cp.`id_category` = c.`id_category`
                INNER JOIN `' . _DB_PREFIX_ . 'product` p ON cp.`id_product` = p.`id_product`
                ' . Shop::addSqlAssociation('product', 'p', false) . '
                WHERE c.`active` = 1
                    AND product_shop.`active` = 1
                    AND product_shop.`visibility` IN ("both", "search")
                    AND product_shop.indexed = 1
                    AND cg.`id_group` ' . (!$this->id_customer ? '=' .(int)Configuration::get('PS_UNIDENTIFIED_GROUP') : 'IN (
                        SELECT id_group FROM ' . _DB_PREFIX_ . 'customer_group
                        WHERE id_customer = ' . (int) $this->id_customer . '
                    )
                    ');

        $this->module->log($eligible_products_request, __FILE__, __METHOD__, __LINE__, '$eligible_products_request');

        $nb_suitable_words = 0;

        foreach ($this->words as $key => $word) {
            if (!empty($word) && (Tools::strlen($word) >= (int) Configuration::get('PS_SEARCH_MINWORDLEN') || in_array($this->iso_lang, array('zh', 'tw', 'ja')))) {
                $naked_word = $word;
                $word = str_replace('%', '\\%', $word);
                $word = str_replace('_', '\\_', $word);

                if ((int) Configuration::get(PS_SEARCH_START) == 1) {
                    $my_word = $word[0] == '-'
                    ? '%' . pSQL(Tools::substr($word, 1, PS_SEARCH_MAX_WORD_LENGTH)) . '%'
                    : '%' . pSQL(Tools::substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH)) . '%';
                } else {
                    $my_word = $word[0] == '-'
                    ? pSQL(Tools::substr($word, 1, PS_SEARCH_MAX_WORD_LENGTH)) . '%'
                    : pSQL(Tools::substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH)) . '%';
                }

                $my_term_word = $word[0] == '-'
                ? '%' . pSQL(Tools::substr($word, 1, PS_SEARCH_MAX_WORD_LENGTH)) . '%'
                : '%' . pSQL(Tools::substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH)) . '%';

                if (Configuration::get(AJS_APPROXIMATIVE_SEARCH) && !in_array($this->iso_lang, array('zh', 'tw', 'ja'))) {
                    //If we are not in compat mode, we check for synonyms

                    $request = '
                                    SELECT sw.id_word
                                    FROM ' . _DB_PREFIX_ . 'search_word sw
                                    WHERE word LIKE "' . $my_word . '"';

                    $results = $this->db->executeS($request);

                    if (($results === false || count($results) == 0) && $key . '' != 'concat') {
                        $synonyms_results = $this->searchSynonyms($my_word);

                        if (count($synonyms_results['ids']) == 0 && Configuration::get(AJS_APPROXIMATIVE_SEARCH)) {
                            if ($this->applyLevenshtein($my_word, $naked_word, $id_lang)) {
                                $synonyms_results = $this->searchSynonyms($my_word);
                            } else {
                                return;
                            }
                        }

                        if (count($synonyms_results['ids']) > 0) {
                            $word_conditions[] = '
                                (sw.id_word IN(' . implode(',', $synonyms_results['ids']) . '))';
                        }
                    } else {
                        if ($results !== false) {
                            $results_ids = array();
                            foreach ($results as $result) {
                                $results_ids[$result['id_word']] = $result['id_word'];
                            }
                            if (count($results_ids) > 0) {
                                $word_conditions[] = '(sw.id_word IN(' . implode(',', $results_ids) . '))';
                            }
                        }
                    }
                } else {
                    //If we are in compat mode, no synonym check
                    $word_conditions[] = '(sw.word LIKE "' . $my_word . '")';
                }

                if ($key . '' != 'concat') {
                    $nb_suitable_words++;
                    $likes = array();

                    $likes[] = 'terms LIKE "' . $my_term_word . '"';

                    if (isset($synonyms_results['words']) && is_array($synonyms_results['words'])) {
                        foreach ($synonyms_results['words'] as $synonym) {
                            $likes[] = 'terms LIKE "%' . $synonym . '%"';
                        }
                    }

                    $check_terms[] = '(' . implode(' OR ', $likes) . ')';
                }
            }
        }

        if ($nb_suitable_words == 0) {
            $this->context->smarty->assign('no_suitable_words', true);
            $this->context->smarty->assign('min_length', (int) Configuration::get('PS_SEARCH_MINWORDLEN'));
            return;
        }
        $this->where = implode(' OR ', $word_conditions);
        $this->having = (count($check_terms) > 0) ? ' HAVING ' . implode(' AND ', $check_terms) : '';
        $sql_limit = $this->limit > 0 ? ' LIMIT ' . ($this->page_number - 1) * $this->limit . ',' . $this->limit : '';
        $pl = $need_name ? ' INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON si.id_product=pl.id_product AND pl.id_lang=' . (int) $id_lang . ' ' : ' ';

        $main_request = '
                    SELECT
                    SQL_CALC_FOUND_ROWS
                    si.id_product, SUM(si.weight) position, GROUP_CONCAT(sw.word SEPARATOR \' \') as terms,
                    IFNULL(stock.quantity, 0) as quantity
                    FROM ' . _DB_PREFIX_ . 'search_word sw
                    LEFT JOIN ' . _DB_PREFIX_ . 'search_index si ON sw.id_word = si.id_word
                    LEFT JOIN ' . _DB_PREFIX_. 'product p ON p.id_product=si.id_product
                    ' . Shop::addSqlAssociation('product', 'si', false)
        . $pl .
        ' ' . Product::sqlStock('p', 0) . '
        LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
        WHERE ' . ((int) Configuration::get(AJS_MULTILANG_SEARCH) == 1 ? '' : 'sw.id_lang = ' . (int) $id_lang . '
                        AND ') . ' sw.id_shop = ' . $this->context->shop->id . ' ' .
        (Tools::strlen($this->where) > 0 ? 'AND (' . $this->where . ')' : '') . '
                        AND si.id_product IN(' . $eligible_products_request . ')
                    GROUP BY si.id_product '
        . $this->having
        . $this->main_order_by
            . $sql_limit;

        $this->module->log($main_request, __FILE__, __METHOD__, __LINE__, '$main_request');

        $results = $this->db->executeS($main_request);
        $this->nb = $this->db->getValue('SELECT FOUND_ROWS() AS nb', false);
        if (is_array($results)) {
            foreach ($results as $row) {
                $this->product_ids[] = $row['id_product'];
            }
            $findCache[$cacheKey] = $this->getResultIds();
        }
    }

    public function getResults($ajax = false)
    {
        if (count($this->product_ids) == 0) {
            return array();
        }

        if ($ajax) {
            $sql = 'SELECT DISTINCT pl.name pname, cl.name cname,
                    cl.link_rewrite crewrite, pl.link_rewrite prewrite, pl.link_rewrite link_rewrite,
                    m.`name` mname, m.`id_manufacturer` manid, cs.id_category as catid,
                    p.*,
                    product_shop.*,
                    IFNULL(image_shop.`id_image`, (SELECT i.`id_image` FROM '._DB_PREFIX_.'image i where i.`id_product`= p.`id_product` ORDER BY i.cover DESC LIMIT 1)) imgid
                FROM ' . _DB_PREFIX_ . 'product p
                ' . Shop::addSqlAssociation('product', 'p') . '
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                    p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = ' . (int) $this->id_lang . Shop::addSqlRestrictionOnLang('pl') . '
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON cs.id_category=product_shop.id_category_default
                    AND cs.id_shop=' . $this->context->shop->id . '
                LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON (
                    product_shop.`id_category_default` = c.`id_category`
                    AND c.active=1
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (
                    c.`id_category` = cl.`id_category`
                    AND cl.`id_lang` = ' . (int) $this->id_lang . Shop::addSqlRestrictionOnLang('cl') . '
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
                    ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int)$this->context->shop->id.')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image`
                    AND il.`id_lang` = ' . (int) $this->id_lang . ')
                WHERE p.`id_product` IN(' . implode(',', $this->product_ids) . ')';

            $this->module->log($sql, __FILE__, __METHOD__, __LINE__, 'if $ajax $sql');
        } else {
            $sql = 'SELECT DISTINCT(p.id_product), p.*, product_shop.*, stock.out_of_stock,
                IFNULL(stock.quantity, 0) as quantity,
                pl.`description_short`, pl.`available_now`, pl.`available_later`, pl.`link_rewrite`, pl.`name`,
             IFNULL(image_shop.`id_image`, (SELECT i.`id_image` FROM '._DB_PREFIX_.'image i where i.`id_product`= p.`id_product` ORDER BY i.cover DESC LIMIT 1)) `id_image`,
             il.`legend`, m.`name` manufacturer_name,
             product_attribute_shop.`id_product_attribute`, 1 as position,
                DATEDIFF(
                    p.`date_add`,
                    DATE_SUB(
                        NOW(),
                        INTERVAL '
            . (Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ?
                Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
                    )
                ) > 0 new
                FROM ' . _DB_PREFIX_ . 'product p
                ' . Shop::addSqlAssociation('product', 'p') . '
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                    p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = ' . (int) $this->id_lang . Shop::addSqlRestrictionOnLang('pl') . '
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (p.`id_product` = pa.`id_product`)
                ' . Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1')
            . ' ' . Product::sqlStock('p', 0) . '
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
                    ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int)$this->context->shop->id.')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image`
                    AND il.`id_lang` = ' . (int) $this->id_lang . ')
                WHERE p.`id_product` IN(' . implode(',', $this->product_ids) . ')
                GROUP BY p.id_product
                ' . $this->main_order_by;

            $this->module->log($sql, __FILE__, __METHOD__, __LINE__, 'if not $ajax $sql');
        }

        $result_properties = $this->db->executeS($sql);
        $dbresults = array();
        $dbres = array();

        if ($this->order_by == 'position') {
            if (is_array($result_properties)) {
                foreach ($result_properties as $v) {
                    $dbres[$v['id_product']] = $v;
                }
            }

            if (is_array($this->product_ids)) {
                foreach ($this->product_ids as $product_id) {
                    if (isset($dbres[$product_id])) {
                        $dbresults[] = $dbres[$product_id];
                    }
                }
            }
        } else {
            $dbresults = $result_properties;
        }

        $dbresults = Product::getProductsProperties((int) $this->id_lang, $dbresults);
        $this->categories = $this->getCategoriesOfProducts($this->id_lang, $this->context->shop->id, $this->product_ids, array('where' => $this->where, 'having' => $this->having), $ajax);

        return $dbresults;
    }

    public function getCategories()
    {
        if (isset($this->categories) && count($this->categories) > 0) {
            foreach ($this->categories as &$row) {
                $row['id_image'] = file_exists(_PS_CAT_IMG_DIR_ . $row['id_category'] . '.jpg') ?
                (int) $row['id_category']
                : Language::getIsoById($this->id_lang) . '-default';
                $row['legend'] = 'no picture';
                $row['image']['legend'] = 'no picture';

                $cat = new Category($row['id_category'], $this->context->language->id);
                $row['image']['large']['url'] = $this->getCategoryImage($cat, $this->context->language->id);
                $row['url'] = $this->context->link->getCategoryLink($cat);
            }
        } else {
            $this->categories = array();
        }

        return $this->categories;
    }

    public function getTotal()
    {
        return $this->nb;
    }

    public function getResultIds()
    {
        return $this->product_ids;
    }

    private function getCategoriesOfProducts($id_lang, $id_shop, $products, $criteria, $ajax = false)
    {
        $nb_categories = $ajax ? pSQL(Configuration::get(AJS_MAX_CATEGORIES_KEY, 0)) : pSQL(Configuration::get(AJS_SHOW_CATEGORIES, 0));

        $categories_request = '';

        if ($nb_categories > 0) {
            $categories_limit = ' LIMIT 0,' . $nb_categories;

            $categories_request = '
                         SELECT
                        DISTINCT cp.id_category, pscl.*, SUM(si.weight) position,
                        GROUP_CONCAT(sw.word SEPARATOR \' \') as terms
                        FROM ' . _DB_PREFIX_ . 'search_word sw
                        LEFT JOIN ' . _DB_PREFIX_ . 'search_index si ON sw.id_word = si.id_word
                        ' . Shop::addSqlAssociation('product', 'si', false) . '
                        LEFT JOIN ' . _DB_PREFIX_ . 'category_product cp ON cp.id_product=si.id_product
                        INNER JOIN ' . _DB_PREFIX_ . 'category psc ON psc.id_category=cp.id_category AND psc.is_root_category=0
                            AND psc.active = 1
                        ' . Shop::addSqlAssociation('category', 'psc') . '
                        INNER JOIN ' . _DB_PREFIX_ . 'category_lang pscl ON pscl.id_category=psc.id_category
                            AND pscl.id_lang=' . (int) $id_lang . '
                            AND pscl.id_shop = ' . (int) $id_shop . '
                        WHERE sw.id_lang = ' . (int) $id_lang . '
                            AND sw.id_shop = ' . (int) $id_shop . '
                            AND (' . $criteria['where'] . ')
                            AND si.id_product IN(' . implode(',', $products) . ')
                         GROUP BY cp.id_category'
                . $criteria['having']
                . ' ORDER BY position DESC '
                . $categories_limit;

            //$module->log($categories_request, __FILE__, __METHOD__, __LINE__, '$categories_request');

            $categories = Db::getInstance()->ExecuteS($categories_request);
            return $categories;
        } else {
            return array();
        }
    }

    private function searchSynonyms($my_word)
    {
        $request = '
                        SELECT DISTINCT synonyms.id_word, sw.word
                        FROM ' . _DB_PREFIX_ . 'ambjolisearch_synonyms synonyms
                        LEFT JOIN ' . _DB_PREFIX_ . 'search_word sw
                            ON synonyms.id_word = sw.id_word
                        WHERE
                            synonyms.synonym LIKE "' . pSQL($my_word) . '"';

        $synonyms = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($request);
        $return = array('words' => array(), 'ids' => array());
        foreach ($synonyms as $synonym) {
            $return['words'][] = $synonym['word'];
            $return['ids'][] = $synonym['id_word'];
        }

        return $return;
    }

    private function applyLevenshtein($my_word, $naked_word, $id_lang)
    {
        //Levehnstein procedure

        $cuts = array();
        $cutting = '';
        $cutsize = Tools::strlen($naked_word) > 5 ? 3 : 2;
        for ($i = 0, $max = (Tools::strlen($naked_word) - $cutsize + 1); $i < $max; $i++) {
            $cut = '';
            for ($j = 0; $j < $cutsize; $j++) {
                $cut .= $naked_word{($i + $j)};
            }

            $cuts[] = $cut;
        }

        foreach ($cuts as $cut) {
            if ($cuts[0] == $cut) {
                continue;
            }

            $cutting .= '|| sw.word LIKE "%' . $cut . '%"';
        }

        $request = '
            SELECT COUNT(sw.word) as nb_words
            FROM ' . _DB_PREFIX_ . 'search_word sw
            WHERE word="' . $naked_word . '"';

        $existing_words = Db::getInstance()->executeS($request);

        if ($existing_words[0]['nb_words'] == 0) {
            $request = '
                SELECT
                DISTINCT sw.id_word, sw.word
                FROM ' . _DB_PREFIX_ . 'search_word sw
                LEFT JOIN ' . _DB_PREFIX_ . 'search_index si ON sw.id_word = si.id_word
                WHERE sw.id_lang = ' . (int) $id_lang . '
                    AND sw.id_shop = ' . Context::getContext()->shop->id . '
                    AND
                        (
                        (sw.word LIKE "' . $cuts[0] . '%" ' . $cutting . ')
                        AND (sw.word LIKE "' . $my_word . '" || amb_levenshtein(LEFT(sw.word, '.Tools::strlen($naked_word).'), "'
            . $naked_word . '") < 3)
                    )';

            $this->module->log($request, __FILE__, __METHOD__, __LINE__, 'levenhstein $request');

            $lvs_results = Db::getInstance()->executeS($request);
            if (is_array($lvs_results)) {
                foreach ($lvs_results as $row) {
                    try {
                        Db::getInstance()->insert(
                            'ambjolisearch_synonyms',
                            array(
                                'synonym' => $naked_word,
                                'id_word' => $row['id_word'],
                            )
                        );

                        if (Db::getInstance()->getNumberError() == 0) {
                            $got_one = true;
                        }
                    } catch (PrestaShopException $e) {
                        continue;
                    }
                }
            }
        }

        if (!isset($got_one)) {
            $got_one = false;
        }

        return $got_one;
    }

    private function getCategoryImage($category, $id_lang)
    {
        $small = 'category';
        $default = 'default';
        $id_image = file_exists(_PS_CAT_IMG_DIR_ . $category->id . '.jpg') ?
        (int) $category->id : Language::getIsoById($id_lang) . '-default';
        return $this->context->link->getCatImageLink($category->link_rewrite, $id_image, $small . '_' . $default);
    }
}
