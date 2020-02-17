<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

class AdminPrestaBlogAjaxController extends ModuleAdminController
{
    public function ajaxProcessPrestaBlogRun()
    {
        $current_lang = (int)$this->context->language->id;

        switch (Tools::getValue('do')) {
            case 'sortSubBlocks':
                if (Tools::getValue('items') && Tools::getValue('hook_name')) {
                    SubBlocksClass::updatePositions(Tools::getValue('items'), Tools::getValue('hook_name'));
                }

                break;

            case 'sortBlocs':
                if (Tools::getValue('sortblocLeft')) {
                    $sort_bloc_left = serialize(Tools::getValue('sortblocLeft'));
                } else {
                    $sort_bloc_left = serialize(array(0 => ''));
                }

                if (Tools::getValue('sortblocRight')) {
                    $sort_bloc_right = serialize(Tools::getValue('sortblocRight'));
                } else {
                    $sort_bloc_right = serialize(array(0 => ''));
                }

                Configuration::updateValue(
                    'prestablog_sbl',
                    $sort_bloc_left,
                    false,
                    null,
                    (int)Tools::getValue('id_shop')
                );
                Configuration::updateValue('prestablog_sbl', $sort_bloc_left);
                Configuration::updateValue(
                    'prestablog_sbr',
                    $sort_bloc_right,
                    false,
                    null,
                    (int)Tools::getValue('id_shop')
                );
                Configuration::updateValue('prestablog_sbr', $sort_bloc_right);

                break;

            case 'loadProductsLink':
                $prestablog = new PrestaBlog();
                if (Tools::getValue('req')) {
                    $list_product_linked = array();
                    $list_product_linked = preg_split('/;/', rtrim(Tools::getValue('req'), ';'));

                    if (count($list_product_linked) > 0) {
                        foreach ($list_product_linked as $product_link) {
                            $product_search = new Product((int)$product_link, false, $current_lang);
                            $product_cover = Image::getCover($product_search->id);
                            $image_product = new Image((int)$product_cover['id_image']);
                            $image_thumb_path = ImageManager::thumbnail(
                                _PS_IMG_DIR_.'p/'.$image_product->getExistingImgPath().'.jpg',
                                'product_mini_'.$product_search->id.'.jpg',
                                45,
                                'jpg'
                            );

                            echo '
                                <tr class="noInlisted_'.$product_search->id.'">
                                    <td
                                        class="'.($product_search->active ? '' : 'noactif ').'center"
                                    >'.$product_search->id.'</td>
                                    <td
                                        class="'.($product_search->active ? '' : 'noactif ').'center"
                                    >'.$image_thumb_path.'</td>
                                    <td
                                        class="'.($product_search->active ? '' : 'noactif ').'"
                                        >'.$product_search->name.'</td>
                                    <td
                                        class="'.($product_search->active ? '' : 'noactif ').'center"
                                    >
                                        <img
                                            src="../modules/prestablog/views/img/disabled.gif"
                                            rel="'.$product_search->id.'"
                                            class="delinked"
                                        />
                                    </td>
                                </tr>'."\n";
                        }
                        echo '
                            <script type="text/javascript">
                                $("#productLinked img.delinked").click(function() {
                                    var idP = $(this).attr("rel");
                                    $("#currentProductLink input.linked_"+idP).remove();
                                    $("#productLinked .noInlisted_"+idP).remove();
                                    ReloadLinkedProducts();
                                    ReloadLinkedSearchProducts();
                                });
                            </script>'."\n";
                    } else {
                        echo '
                            <tr>
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_listed'].'
                                </td>
                            </tr>'."\n";
                    }
                } else {
                    echo '
                        <tr>
                            <td colspan="4" class="center">
                                '.$prestablog->message_call_back['no_result_listed'].'
                            </td>
                        </tr>'."\n";
                }

                break;

            case 'loadArticlesLink':
                $prestablog = new PrestaBlog();
                if (Tools::getValue('req')) {
                    $list_article_linked = array();
                    $list_article_linked = preg_split('/;/', rtrim(Tools::getValue('req'), ';'));

                    if (count($list_article_linked) > 0) {
                        foreach ($list_article_linked as $article_link) {
                            $article_search = new NewsClass((int)$article_link, $current_lang);

                            if (file_exists(PrestaBlog::imgUpPath().'/adminth_'.$article_search->id.'.jpg')) {
                                $imgA = PrestaBlog::imgPathFO().PrestaBlog::getT().'/up-img/';
                                $imgA .= 'adminth_'.$article_search->id.'.jpg?'.md5(time());
                                $thumbnail = '
                                    <img
                                        class="imgm img-thumbnail"
                                        src="'.$imgA.'"
                                    />';
                            } else {
                                $thumbnail = '-';
                            }

                            echo '
                                <tr class="noInlisted_'.$article_search->id.'">
                                    <td
                                        class="'.($article_search->actif ? '' : 'noactif ').'center"
                                    >'.$article_search->id.'</td>
                                    <td
                                        class="'.($article_search->actif ? '' : 'noactif ').'center"
                                    >'.$thumbnail.'</td>
                                    <td
                                        class="'.($article_search->actif ? '' : 'noactif ').'"
                                    >'.$article_search->title.'</td>
                                    <td
                                        class="'.($article_search->actif ? '' : 'noactif ').'center"
                                    >
                                        <img
                                            src="../modules/prestablog/views/img/disabled.gif"
                                            rel="'.$article_search->id.'"
                                            class="delinked"
                                        />
                                    </td>
                                </tr>'."\n";
                        }
                        echo '
                            <script type="text/javascript">
                                $("#articleLinked img.delinked").click(function() {
                                    var idN = $(this).attr("rel");
                                    $("#currentArticleLink input.linked_"+idN).remove();
                                    $("#articleLinked .noInlisted_"+idN).remove();
                                    ReloadLinkedArticles();
                                    ReloadLinkedSearchArticles();
                                });
                            </script>'."\n";
                    } else {
                        echo '
                            <tr>
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_listed'].'
                                </td>
                            </tr>'."\n";
                    }
                } else {
                    echo '
                        <tr>
                            <td colspan="4" class="center">
                                '.$prestablog->message_call_back['no_result_listed'].'
                            </td>
                        </tr>'."\n";
                }

                break;

            case 'loadLookbooksLink':
                $prestablog = new PrestaBlog();
                if (Tools::getValue('req')) {
                    $list_lookbook_linked = array();
                    $list_lookbook_linked = preg_split('/;/', rtrim(Tools::getValue('req'), ';'));

                    if (count($list_lookbook_linked) > 0) {
                        foreach ($list_lookbook_linked as $lookbook_link) {
                            $lookbook_search = new LookBookClass((int)$lookbook_link, $current_lang);

                            if (file_exists(PrestaBlog::imgUpPath().'/lookbook/adminth_'.$lookbook_search->id.'.jpg')) {
                                $imgA = PrestaBlog::imgPathFO().PrestaBlog::getT().'/up-img/lookbook/';
                                $imgA .= 'adminth_'.$lookbook_search->id.'.jpg?'.md5(time());
                                $thumbnail = '
                                    <img
                                        class="imgm img-thumbnail"
                                        src="'.$imgA.'"
                                    />';
                            } else {
                                $thumbnail = '-';
                            }

                            echo '
                                <tr class="noInlisted_'.$lookbook_search->id.'">
                                    <td
                                        class="'.($lookbook_search->actif ? '' : 'noactif ').'center"
                                    >'.$lookbook_search->id.'</td>
                                    <td
                                        class="'.($lookbook_search->actif ? '' : 'noactif ').'center"
                                    >'.$thumbnail.'</td>
                                    <td
                                        class="'.($lookbook_search->actif ? '' : 'noactif ').'"
                                    >'.$lookbook_search->title.'</td>
                                    <td
                                        class="'.($lookbook_search->actif ? '' : 'noactif ').'center"
                                    >
                                        <img
                                            src="../modules/prestablog/views/img/disabled.gif"
                                            rel="'.$lookbook_search->id.'"
                                            class="delinked"
                                        />
                                    </td>
                                </tr>'."\n";
                        }
                        echo '
                            <script type="text/javascript">
                                $("#lookbookLinked img.delinked").click(function() {
                                    var idN = $(this).attr("rel");
                                    $("#currentLookbookLink input.linked_"+idN).remove();
                                    $("#lookbookLinked .noInlisted_"+idN).remove();
                                    ReloadLinkedLookbooks();
                                    ReloadLinkedSearchLookbooks();
                                });
                            </script>'."\n";
                    } else {
                        echo '
                            <tr>
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_listed'].'
                                </td>
                            </tr>'."\n";
                    }
                } else {
                    echo '
                        <tr>
                            <td colspan="4" class="center">
                                '.$prestablog->message_call_back['no_result_listed'].'
                            </td>
                        </tr>'."\n";
                }

                break;

            case 'searchProducts':
                if (Tools::getValue('req') != '') {
                    if (Tools::strlen(Tools::getValue('req'))
                        >= (int)Configuration::get('prestablog_nb_car_min_linkprod')) {
                        $start = 0;
                        $pas = (int)Configuration::get('prestablog_nb_list_linkprod');
                        if (!$pas || $pas == 0) {
                            $pas = 5;
                        }

                        if (Tools::getValue('start')) {
                            $start = (int)Tools::getValue('start');
                        }

                        $end = (int)$pas + (int)$start;

                        $list_product_linked = array();

                        if (Tools::getValue('listLinkedProducts') != '') {
                            $list_product_linked = preg_split(
                                '/;/',
                                rtrim(Tools::getValue('listLinkedProducts'), ';')
                            );
                        }

                        $result_search = array();
                        $prestablog = new PrestaBlog();
                        $rsql_search = '';
                        $rsql_lang = '';

                        $query = Tools::strtoupper(pSQL(Trim(Tools::getValue('req'))));
                        $rsql_search .= ' UPPER(pl.`name`) LIKE \'%'.pSQL($query).'%\' OR';

                        $querys = array_filter(explode(' ', $query));

                        // 'description', 'description_short', 'link_rewrite',
                        // 'meta_title', 'meta_description', 'meta_keywords'
                        $list_champs_product_lang = array('name');

                        foreach ($querys as $value) {
                            // test si #id_product pour aller chercher directement le produit
                            if (preg_match('/^(#[0-9]*)$/', $value)) {
                                $rsql_search .= ' pl.`id_product` = '.(int)ltrim($value, '#').' OR';
                            }

                            foreach ($list_champs_product_lang as $value_c) {
                                $rsql_search .= ' UPPER(pl.`'.pSQL($value_c).'`) LIKE \'%'.pSQL($value).'%\' OR';
                            }
                        }

                        if (Tools::getValue('lang') != '') {
                            $current_lang = (int)Tools::getValue('lang');
                        }

                        $rsql_lang = 'AND pl.`id_lang` = '.(int)$current_lang;
                        $rsql_shop = 'AND ps.`id_shop` = '.(int)Tools::getValue('id_shop');

                        $rsql_search = ' WHERE ('.rtrim($rsql_search, 'OR').') '.$rsql_lang.' '.$rsql_shop;

                        $rsql_plink = '';

                        foreach ($list_product_linked as $product_link) {
                            $rsql_plink .= ' AND pl.`id_product` <> '.(int)$product_link;
                        }

                        $rsql_search .= $rsql_plink;

                        $count_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
                            SELECT COUNT(DISTINCT pl.`id_product`) AS `value`
                            FROM  `'.bqSQL(_DB_PREFIX_).'product_lang` AS pl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'product_shop` AS ps
                                ON (ps.`id_product` = pl.`id_product`)
                            '.$rsql_search.';');

                        $rsql =  'SELECT DISTINCT(pl.`id_product`)
                            FROM  `'.bqSQL(_DB_PREFIX_).'product_lang` AS pl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'product_shop` AS ps
                                ON (ps.`id_product` = pl.`id_product`)
                            '.$rsql_search.'
                            ORDER BY pl.`name`
                            LIMIT '.(int)$start.', '.(int)$pas.' ;';

                        $result_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($rsql);

                        if (count($result_search) > 0) {
                            foreach ($result_search as $value) {
                                $product_search = new Product((int)$value['id_product'], false, $current_lang);
                                $product_cover = Image::getCover($product_search->id);
                                $image_product = new Image((int)$product_cover['id_image']);
                                $image_thumb_path = ImageManager::thumbnail(
                                    _PS_IMG_DIR_.'p/'.$image_product->getExistingImgPath().'.jpg',
                                    'product_mini_'.$product_search->id.'.jpg',
                                    45,
                                    'jpg'
                                );

                                echo '
                                    <tr class="Outlisted noOutlisted_'.$product_search->id.'">
                                        <td class="'.($product_search->active ? '' : 'noactif ').'center">
                                            <img
                                                src="../modules/prestablog/views/img/linked.png"
                                                rel="'.$product_search->id.'"
                                                class="linked"
                                            />
                                        </td>
                                        <td
                                            class="'.($product_search->active ? '' : 'noactif ').'center"
                                        >'.$product_search->id.'</td>
                                        <td
                                            class="'.($product_search->active ? '' : 'noactif ').'center"
                                            style="width:50px;"
                                        >'.$image_thumb_path.'</td>
                                        <td
                                            class="'.($product_search->active ? '' : 'noactif ').'"
                                        >'.$product_search->name.'</td>
                                    </tr>'."\n";
                            }
                            echo '
                                <tr class="prestablog-footer-search">
                                    <td colspan="4">
                                        '.$prestablog->message_call_back['total_results'].' : '.$count_search['value'].'
                                        '.($end < (int)$count_search['value'] ? '
                                        <span id="prestablog-next-search" class="prestablog-search">
                                        '.$prestablog->message_call_back['next_results'].
                                        '<img src="../modules/prestablog/views/img/list-next2.gif" /></span>' : '').'
                                        '.($start > 0?'<span id="prestablog-prev-search" class="prestablog-search">
                                        <img src="../modules/prestablog/views/img/list-prev2.gif" />
                                        '.$prestablog->message_call_back['prev_results'].'</span>':'').'
                                    </td>
                                </tr>'."\n";

                            $jsAppend = '$("#currentProductLink").append(\'<input type="text" name="productsLink[]"';
                            $jsAppend .= ' value="\'+idP+\'" class="linked_\'+idP+\'" />\');';
                            echo '
                                <script type="text/javascript">
                                    $("span#prestablog-prev-search").click(function() {
                                        ReloadLinkedSearchProducts('.($start - $pas).');
                                    });
                                    $("span#prestablog-next-search").click(function() {
                                        ReloadLinkedSearchProducts('.($start + $pas).');
                                    });
                                    $("#productLinkResult img.linked").click(function() {
                                        var idP = $(this).attr("rel");
                                        '.$jsAppend.'
                                        $("#productLinkResult .noOutlisted_"+idP).remove();
                                        ReloadLinkedProducts();
                                        ReloadLinkedSearchProducts();
                                    });
                                </script>'."\n";
                        } else {
                            echo '
                                <tr class="warning">
                                    <td colspan="4" class="center">
                                        '.$prestablog->message_call_back['no_result_found'].'
                                    </td>
                                </tr>'."\n";
                        }
                    } else {
                        $prestablog = new PrestaBlog();
                        echo '
                            <tr class="warning">
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_found'].'
                                </td>
                            </tr>'."\n";
                    }
                }

                break;

            case 'searchArticles':
                if (Tools::getValue('req') != '') {
                    if (Tools::strlen(Tools::getValue('req'))
                        >= (int)Configuration::get('prestablog_nb_car_min_linknews')) {
                        $start = 0;
                        $pas = (int)Configuration::get('prestablog_nb_list_linknews');
                        if (!$pas || $pas == 0) {
                            $pas = 5;
                        }

                        if (Tools::getValue('start')) {
                            $start = (int)Tools::getValue('start');
                        }

                        $end = (int)$pas + (int)$start;

                        $list_article_linked = array();

                        if (Tools::getValue('listLinkedArticles') != '') {
                            $list_article_linked = preg_split('/;/', rtrim(Tools::getValue('listLinkedArticles'), ';'));
                        }

                        $result_search = array();
                        $prestablog = new PrestaBlog();
                        $rsql_search = '';
                        $rsql_lang = '';

                        $query = Tools::strtoupper(pSQL(Trim(Tools::getValue('req'))));
                        $querys = array_filter(explode(' ', $query));

                        $list_champs_article_lang = array(
                            #'paragraph',
                            #'content',
                            #'link_rewrite',
                            'title'
                            #'meta_title',
                            #'meta_description',
                            #'meta_keywords'
                        );

                        foreach ($querys as $value) {
                            // test si #id_product pour aller chercher directement le produit
                            if (preg_match('/^(#[0-9]*)$/', $value)) {
                                $rsql_search .= ' nl.`id_prestablog_news` = '.(int)ltrim($value, '#').' OR';
                            }

                            foreach ($list_champs_article_lang as $value_c) {
                                $rsql_search .= ' UPPER(nl.`'.pSQL($value_c).'`) LIKE \'%'.pSQL($value).'%\' OR';
                            }
                        }

                        if (Tools::getValue('lang') != '') {
                            $current_lang = (int)Tools::getValue('lang');
                        }

                        $rsql_lang = 'AND nl.`id_lang` = '.(int)$current_lang;
                        $rsql_shop = 'AND n.`id_shop` = '.(int)Tools::getValue('id_shop');

                        $rsql_search = ' WHERE ('.rtrim($rsql_search, 'OR').') '.$rsql_lang.' '.$rsql_shop;

                        $rsql_plink = '';

                        foreach ($list_article_linked as $article_link) {
                            $rsql_plink .= ' AND nl.`id_prestablog_news` <> '.(int)$article_link;
                        }

                        $rsql_search .= $rsql_plink;

                        $count_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
                            SELECT COUNT(DISTINCT nl.`id_prestablog_news`) AS `value`
                            FROM  `'.bqSQL(_DB_PREFIX_).'prestablog_news_lang` AS nl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` AS n
                                ON (n.`id_prestablog_news` = nl.`id_prestablog_news`)
                            '.$rsql_search.';');

                        $rsql =  'SELECT DISTINCT(nl.`id_prestablog_news`)
                            FROM  `'.bqSQL(_DB_PREFIX_).'prestablog_news_lang` AS nl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_news` AS n
                                ON (n.`id_prestablog_news` = nl.`id_prestablog_news`)
                            '.$rsql_search.'
                            ORDER BY nl.`title`
                            LIMIT '.(int)$start.', '.(int)$pas.' ;';

                        $result_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($rsql);
                        $prestablog = new PrestaBlog();

                        if (count($result_search) > 0) {
                            foreach ($result_search as $value) {
                                $article_search = new NewsClass((int)$value['id_prestablog_news'], $current_lang);

                                if (file_exists(PrestaBlog::imgUpPath().'/adminth_'.$article_search->id.'.jpg')) {
                                    $imgA = PrestaBlog::imgPathFO().PrestaBlog::getT().'/up-img/';
                                    $imgA .= 'adminth_'.$article_search->id.'.jpg?'.md5(time());
                                    $thumbnail = '
                                        <img
                                            class="imgm img-thumbnail"
                                            src="'.$imgA.'"
                                        />';
                                } else {
                                    $thumbnail = '-';
                                }

                                echo '
                                    <tr class="Outlisted noOutlisted_'.$article_search->id.'">
                                        <td class="'.($article_search->actif ? '' : 'noactif ').'center">
                                            <img
                                                src="../modules/prestablog/views/img/linked.png"
                                                rel="'.$article_search->id.'"
                                                class="linked"
                                            />
                                        </td>
                                        <td
                                            class="'.($article_search->actif ? '' : 'noactif ').'center"
                                        >'.$article_search->id.'</td>
                                        <td
                                            class="'.($article_search->actif ? '' : 'noactif ').'center"
                                            style="width:50px;"
                                        >'.$thumbnail.'</td>
                                        <td
                                            class="'.($article_search->actif ? '' : 'noactif ').'"
                                        >'.$article_search->title.'</td>
                                    </tr>'."\n";
                            }
                            echo '
                                <tr class="prestablog-footer-search">
                                    <td colspan="4">
                                        '.$prestablog->message_call_back['total_results'].' : '.$count_search['value'].'
                                        '.($end < (int)$count_search['value'] ? '
                                        <span id="prestablog-next-search" class="prestablog-search">
                                        '.$prestablog->message_call_back['next_results'].
                                        '<img src="../modules/prestablog/views/img/list-next2.gif" /></span>' : '').'
                                        '.($start > 0?'<span id="prestablog-prev-search" class="prestablog-search">
                                        <img src="../modules/prestablog/views/img/list-prev2.gif" />
                                        '.$prestablog->message_call_back['prev_results'].'</span>':'').'
                                    </td>
                                </tr>'."\n";

                            $jsAppend = '$("#currentArticleLink").append(\'<input type="text" name="articlesLink[]"';
                            $jsAppend .= ' value="\'+idN+\'" class="linked_\'+idN+\'" />\');';

                            echo '
                                <script type="text/javascript">
                                    $("span#prestablog-prev-search").click(function() {
                                        ReloadLinkedSearchArticles('.($start - $pas).');
                                    });
                                    $("span#prestablog-next-search").click(function() {
                                        ReloadLinkedSearchArticles('.($start + $pas).');
                                    });
                                    $("#articleLinkResult img.linked").click(function() {
                                        var idN = $(this).attr("rel");
                                        '.$jsAppend.'
                                        $("#articleLinkResult .noOutlisted_"+idN).remove();
                                        ReloadLinkedArticles();
                                        ReloadLinkedSearchArticles();
                                    });
                                </script>'."\n";
                        } else {
                            echo '
                                <tr class="warning">
                                    <td colspan="4" class="center">
                                        '.$prestablog->message_call_back['no_result_found'].'
                                    </td>
                                </tr>'."\n";
                        }
                    } else {
                        $prestablog = new PrestaBlog();
                        echo '
                            <tr class="warning">
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_found'].'
                                </td>
                            </tr>'."\n";
                    }
                }

                break;

            case 'searchLookbooks':
                if (Tools::getValue('req') != '') {
                    if (Tools::strlen(Tools::getValue('req'))
                        >= (int)Configuration::get('prestablog_nb_car_min_linklb')) {
                        $start = 0;
                        $pas = (int)Configuration::get('prestablog_nb_list_linklb');
                        if (!$pas || $pas == 0) {
                            $pas = 5;
                        }

                        if (Tools::getValue('start')) {
                            $start = (int)Tools::getValue('start');
                        }

                        $end = (int)$pas + (int)$start;

                        $list_lookbook_linked = array();

                        if (Tools::getValue('listLinkedLookbooks') != '') {
                            $list_lookbook_linked = preg_split(
                                '/;/',
                                rtrim(Tools::getValue('listLinkedLookbooks'), ';')
                            );
                        }

                        $result_search = array();
                        $prestablog = new PrestaBlog();
                        $rsql_search = '';
                        $rsql_lang = '';

                        $query = Tools::strtoupper(pSQL(Trim(Tools::getValue('req'))));
                        $querys = array_filter(explode(' ', $query));

                        $list_champs_lookbook_lang = array(
                            'title'
                            #'description'
                        );

                        foreach ($querys as $value) {
                            // test si #id_product pour aller chercher directement le produit
                            if (preg_match('/^(#[0-9]*)$/', $value)) {
                                $rsql_search .= ' nl.`id_prestablog_lookbook` = '.(int)ltrim($value, '#').' OR';
                            }

                            foreach ($list_champs_lookbook_lang as $value_c) {
                                $rsql_search .= ' UPPER(nl.`'.pSQL($value_c).'`) LIKE \'%'.pSQL($value).'%\' OR';
                            }
                        }

                        if (Tools::getValue('lang') != '') {
                            $current_lang = (int)Tools::getValue('lang');
                        }

                        $rsql_lang = 'AND nl.`id_lang` = '.(int)$current_lang;
                        $rsql_shop = 'AND n.`id_shop` = '.(int)Tools::getValue('id_shop');

                        $rsql_search = ' WHERE ('.rtrim($rsql_search, 'OR').') '.$rsql_lang.' '.$rsql_shop;

                        $rsql_plink = '';

                        foreach ($list_lookbook_linked as $lookbook_link) {
                            $rsql_plink .= ' AND nl.`id_prestablog_lookbook` <> '.(int)$lookbook_link;
                        }

                        $rsql_search .= $rsql_plink;

                        $count_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
                            SELECT COUNT(DISTINCT nl.`id_prestablog_lookbook`) AS `value`
                            FROM  `'.bqSQL(_DB_PREFIX_).'prestablog_lookbook_lang` AS nl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_lookbook` AS n
                                ON (n.`id_prestablog_lookbook` = nl.`id_prestablog_lookbook`)
                            '.$rsql_search.';');

                        $rsql =  'SELECT DISTINCT(nl.`id_prestablog_lookbook`)
                            FROM  `'.bqSQL(_DB_PREFIX_).'prestablog_lookbook_lang` AS nl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'prestablog_lookbook` AS n
                                ON (n.`id_prestablog_lookbook` = nl.`id_prestablog_lookbook`)
                            '.$rsql_search.'
                            ORDER BY nl.`title`
                            LIMIT '.(int)$start.', '.(int)$pas.' ;';

                        $result_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($rsql);
                        $prestablog = new PrestaBlog();

                        if (count($result_search) > 0) {
                            foreach ($result_search as $value) {
                                $lookbook_search = new LookBookClass((int)$value['id_prestablog_lookbook'], $current_lang);
                                $fex = PrestaBlog::imgUpPath().'/lookbook/adminth_'.$lookbook_search->id.'.jpg';
                                if (file_exists($fex)) {
                                    $imgA = PrestaBlog::imgPathFO().PrestaBlog::getT().'/up-img/lookbook/';
                                    $imgA .= 'adminth_'.$lookbook_search->id.'.jpg?'.md5(time());
                                    $thumbnail = '
                                        <img
                                            class="imgm img-thumbnail"
                                            src="'.$imgA.'"
                                        />';
                                } else {
                                    $thumbnail = '-';
                                }

                                echo '
                                    <tr class="Outlisted noOutlisted_'.$lookbook_search->id.'">
                                        <td class="'.($lookbook_search->actif ? '' : 'noactif ').'center">
                                            <img
                                                src="../modules/prestablog/views/img/linked.png"
                                                rel="'.$lookbook_search->id.'"
                                                class="linked"
                                            />
                                        </td>
                                        <td
                                            class="'.($lookbook_search->actif ? '' : 'noactif ').'center"
                                        >'.$lookbook_search->id.'</td>
                                        <td
                                            class="'.($lookbook_search->actif ? '' : 'noactif ').'center"
                                            style="width:50px;"
                                        >'.$thumbnail.'</td>
                                        <td
                                            class="'.($lookbook_search->actif ? '' : 'noactif ').'"
                                        >'.$lookbook_search->title.'</td>
                                    </tr>'."\n";
                            }
                            echo '
                                <tr class="prestablog-footer-search">
                                    <td colspan="4">
                                        '.$prestablog->message_call_back['total_results'].' : '.$count_search['value'].'
                                        '.($end < (int)$count_search['value'] ? '
                                        <span id="prestablog-next-search" class="prestablog-search">
                                        '.$prestablog->message_call_back['next_results'].
                                        '<img src="../modules/prestablog/views/img/list-next2.gif" /></span>' : '').'
                                        '.($start > 0?'<span id="prestablog-prev-search" class="prestablog-search">
                                        <img src="../modules/prestablog/views/img/list-prev2.gif" />
                                        '.$prestablog->message_call_back['prev_results'].'</span>':'').'
                                    </td>
                                </tr>'."\n";

                            $jsAppend = '$("#currentLookbookLink").append(\'<input type="text" name="lookbooksLink[]"';
                            $jsAppend .= ' value="\'+idN+\'" class="linked_\'+idN+\'" />\');';

                            echo '
                                <script type="text/javascript">
                                    $("span#prestablog-prev-search").click(function() {
                                        ReloadLinkedSearchLookbooks('.($start - $pas).');
                                    });
                                    $("span#prestablog-next-search").click(function() {
                                        ReloadLinkedSearchLookbooks('.($start + $pas).');
                                    });
                                    $("#lookbookLinkResult img.linked").click(function() {
                                        var idN = $(this).attr("rel");
                                        '.$jsAppend.'
                                        $("#lookbookLinkResult .noOutlisted_"+idN).remove();
                                        ReloadLinkedLookbooks();
                                        ReloadLinkedSearchLookbooks();
                                    });
                                </script>'."\n";
                        } else {
                            echo '
                                <tr class="warning">
                                    <td colspan="4" class="center">
                                        '.$prestablog->message_call_back['no_result_found'].'
                                    </td>
                                </tr>'."\n";
                        }
                    } else {
                        $prestablog = new PrestaBlog();
                        echo '
                            <tr class="warning">
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_found'].'
                                </td>
                            </tr>'."\n";
                    }
                }

                break;

            case 'searchProductsLookbook':
                if (Tools::getValue('req') != '') {
                    if (Tools::strlen(Tools::getValue('req'))
                        >= (int)Configuration::get('prestablog_nb_car_min_linkprod')) {
                        $start = 0;
                        $pas = (int)Configuration::get('prestablog_nb_list_linkprod');
                        if (!$pas || $pas == 0) {
                            $pas = 5;
                        }

                        if (Tools::getValue('start')) {
                            $start = (int)Tools::getValue('start');
                        }

                        $end = (int)$pas + (int)$start;

                        $list_product_linked = array();

                        if (Tools::getValue('listLinkedProducts') != '') {
                            $list_product_linked = preg_split(
                                '/;/',
                                rtrim(Tools::getValue('listLinkedProducts'), ';')
                            );
                        }

                        $result_search = array();
                        $prestablog = new PrestaBlog();
                        $rsql_search = '';
                        $rsql_lang = '';

                        $query = Tools::strtoupper(pSQL(Trim(Tools::getValue('req'))));
                        $rsql_search .= ' UPPER(pl.`name`) LIKE \'%'.pSQL($query).'%\' OR';

                        $querys = array_filter(explode(' ', $query));

                        $list_champs_product_lang = array('name');

                        foreach ($querys as $value) {
                            // test si #id_product pour aller chercher directement le produit
                            if (preg_match('/^(#[0-9]*)$/', $value)) {
                                $rsql_search .= ' pl.`id_product` = '.(int)ltrim($value, '#').' OR';
                            }

                            foreach ($list_champs_product_lang as $value_c) {
                                $rsql_search .= ' UPPER(pl.`'.pSQL($value_c).'`) LIKE \'%'.pSQL($value).'%\' OR';
                            }
                        }

                        if (Tools::getValue('lang') != '') {
                            $current_lang = (int)Tools::getValue('lang');
                        }

                        $rsql_lang = 'AND pl.`id_lang` = '.(int)$current_lang;
                        $rsql_shop = 'AND ps.`id_shop` = '.(int)Tools::getValue('id_shop');

                        $rsql_search = ' WHERE ('.rtrim($rsql_search, 'OR').') '.$rsql_lang.' '.$rsql_shop;

                        $rsql_plink = '';

                        foreach ($list_product_linked as $product_link) {
                            $rsql_plink .= ' AND pl.`id_product` <> '.(int)$product_link;
                        }

                        $rsql_search .= $rsql_plink;

                        $count_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('
                            SELECT COUNT(DISTINCT pl.`id_product`) AS `value`
                            FROM  `'.bqSQL(_DB_PREFIX_).'product_lang` AS pl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'product_shop` AS ps
                                ON (ps.`id_product` = pl.`id_product`)
                            '.$rsql_search.';');

                        $rsql =  'SELECT DISTINCT(pl.`id_product`)
                            FROM  `'.bqSQL(_DB_PREFIX_).'product_lang` AS pl
                            LEFT JOIN `'.bqSQL(_DB_PREFIX_).'product_shop` AS ps
                                ON (ps.`id_product` = pl.`id_product`)
                            '.$rsql_search.'
                            ORDER BY pl.`name`
                            LIMIT '.(int)$start.', '.(int)$pas.' ;';

                        $result_search = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($rsql);

                        if (count($result_search) > 0) {
                            foreach ($result_search as $value) {
                                $product_search = new Product((int)$value['id_product'], false, $current_lang);
                                $product_cover = Image::getCover($product_search->id);
                                $image_product = new Image((int)$product_cover['id_image']);
                                $image_thumb_path = ImageManager::thumbnail(
                                    _PS_IMG_DIR_.'p/'.$image_product->getExistingImgPath().'.jpg',
                                    'product_mini_'.$product_search->id.'.jpg',
                                    45,
                                    'jpg'
                                );

                                echo '
                                    <tr
                                        class="Outlisted noOutlisted_'.$product_search->id.' linkover"
                                        rel="'.$product_search->id.'"
                                    >
                                        <td class="'.($product_search->active ? '' : 'noactif ').'center">
                                            <img
                                                src="../modules/prestablog/views/img/add.gif"
                                                rel="'.$product_search->id.'"
                                                class="addProductLookbook linked"
                                            />
                                        </td>
                                        <td
                                            class="'.($product_search->active ? '' : 'noactif ').'center"
                                        >'.$product_search->id.'</td>
                                        <td
                                            class="'.($product_search->active ? '' : 'noactif ').'center"
                                            style="width:50px;"
                                        >'.$image_thumb_path.'</td>
                                        <td
                                            class="'.($product_search->active ? '' : 'noactif ').'"
                                        >'.$product_search->name.'</td>
                                    </tr>'."\n";
                            }
                            echo '
                                <tr class="prestablog-footer-search">
                                    <td colspan="4">
                                        '.$prestablog->message_call_back['total_results'].' : '.$count_search['value'].'
                                        '.($end < (int)$count_search['value'] ? '
                                        <span id="prestablog-next-search" class="prestablog-search">
                                        '.$prestablog->message_call_back['next_results'].
                                        '<img src="../modules/prestablog/views/img/list-next2.gif" /></span>' : '').'
                                        '.($start > 0?'<span id="prestablog-prev-search" class="prestablog-search">
                                        <img src="../modules/prestablog/views/img/list-prev2.gif" />
                                        '.$prestablog->message_call_back['prev_results'].'</span>':'').'
                                    </td>
                                </tr>'."\n";

                            $jsWLocation = 'window.location.href="';
                            $jsWLocation .= Tools::getValue('urlReturn').'&addLookbookProduct';
                            $jsWLocation .= '&idLB='.(int)Tools::getValue('idLB');
                            $jsWLocation .= '&id_product="+$(this).attr("rel")+"';
                            $jsWLocation .= '&lookbook_shape="+$("#lookbook_shape").val()+"';
                            $jsWLocation .= '&lookbook_shape_ed="+$("#lookbook_shape_ed").val();';
                            echo '
                                <script type="text/javascript">
                                    $("span#prestablog-prev-search").click(function() {
                                        ReloadLinkedSearchProducts('.($start - $pas).');
                                    });
                                    $("span#prestablog-next-search").click(function() {
                                        ReloadLinkedSearchProducts('.($start + $pas).');
                                    });

                                    $(".addProductLookbook").click(function() {
                                        '.$jsWLocation.'
                                    });
                                </script>'."\n";
                        } else {
                            echo '
                                <tr class="warning">
                                    <td colspan="4" class="center">
                                        '.$prestablog->message_call_back['no_result_found'].'
                                    </td>
                                </tr>'."\n";
                        }
                    } else {
                        $prestablog = new PrestaBlog();
                        echo '
                            <tr class="warning">
                                <td colspan="4" class="center">
                                    '.$prestablog->message_call_back['no_result_found'].'
                                </td>
                            </tr>'."\n";
                    }
                }

                break;

            case 'search':
                break;

            default:
                break;
        }
    }
}
