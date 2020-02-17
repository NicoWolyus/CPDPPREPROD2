<?php
/**
 * 2012-2018 NetReviews
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * avisverifiesApi.php file used to execute query from AvisVerifies plateform
 *
 *  @author    NetReviews SAS <contact@avis-verifies.com>
 *  @copyright 2012-2018 NetReviews SAS
 *  @license   NetReviews

 *  @version   Release: $Revision: 7.8.8
 *  @date      07/08/2019
 *  @category  api
 *  International Registered Trademark & Property of NetReviews SAS
 */

require('../../config/config.inc.php');
require('../../init.php');
include('netreviews.php');
$post_data = $_POST;
/*Check data received - Exit if no data received*/
if (!isset($post_data) || empty($post_data)) {
    $reponse = array();
    $reponse['debug'] = 'No POST DATA received';
    $reponse['return'] = 2;
    echo "#netreviews-start#".NetReviewsModel::acEncodeBase64(NetReviewsModel::avJsonEncode($reponse))."#netreviews-end#";
    exit;
}
/*Check module state | EXIT if error returned*/
$is_active_var = isActiveModule($post_data);
if ($is_active_var['return'] != 1) {
    echo "#netreviews-start#".NetReviewsModel::acEncodeBase64(NetReviewsModel::avJsonEncode($is_active_var))."#netreviews-end#";
    exit;
}
/*Check module customer identification | EXIT if error returned*/
$check_security_var = checkSecurityData($post_data);
if ($check_security_var['return'] != 1) {
    echo "#netreviews-start#".NetReviewsModel::acEncodeBase64(NetReviewsModel::avJsonEncode($check_security_var))."#netreviews-end#";
    exit;
}
/*############ START ############*/
/*Switch between each query allowed and sent by NetReviews*/
$to_reply = '';
switch ($post_data['query']) {
    case 'isActiveModule':
        $to_reply = isActiveModule($post_data);
        break;
    case 'setModuleConfiguration':
        $to_reply = setModuleConfiguration($post_data);
        break;
    case 'getModuleAndSiteConfiguration':
        $to_reply = getModuleAndSiteConfiguration($post_data);
        break;
    case 'getOrders':
        $to_reply = getOrders($post_data);
        break;
    case 'setProductsReviews':
        $to_reply = setProductsReviews($post_data);
        break;
    case 'truncateTables':
        $to_reply = truncateTables($post_data);
        break;
    case 'setFlag':
        $to_reply = setFlag($post_data);
        break;
    case 'getOrderHistoryOn':
        $to_reply = getOrderHistoryOn($post_data);
        break;
    case 'getCountOrder':
        $to_reply = getCountOrder($post_data);
        break;
    case 'getOrdersCsv':
        $to_reply = getOrdersCsv($post_data);
        break;
    default:
        break;
}
/*Displaying functions returns to NetReviews */
echo "#netreviews-start#".NetReviewsModel::acEncodeBase64(NetReviewsModel::avJsonEncode($to_reply))."#netreviews-end#";
/**
 * Check ID Api Customer
 * Every sent query depends on the return result of this function
 * @param $post_data
 * @return $reponse : error code + error
 */
function checkSecurityData(&$post_data)
{
    $reponse = array();
    $uns_msg = iniInfo($post_data);
    $id_shop = getCurrentShop($uns_msg);
    $group_name = getGroupname($id_shop,$uns_msg);
    $multisite = Configuration::get('AV_MULTISITE');
    if (empty($uns_msg)) {
        $reponse['debug'] = 'empty message';
        $reponse['return'] = 2;
        $reponse['query'] = 'checkSecurityData';
        /* Set query name because this query is called locally */
        return $reponse;
    }

    $local_id_website = Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop);
    $local_secure_key = Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop);
    /*Check if ID clustomer are set locally*/
    $reponse['query'] = 'checkSecurityData';
    if (!$local_id_website || !$local_secure_key) {
        $reponse['debug'] = 'Customer IDs are not specified on the module';
        $reponse['message'] = 'Customer IDs are not specified on the module';
        $reponse['return'] = 3;
        /* Set query name because this query is called locally */
        return $reponse;
    } elseif ($uns_msg['idWebsite'] != $local_id_website) { //Check if sent Idwebsite if the same as local

        $reponse['message'] = 'Wrong ID Website';
        $reponse['debug'] = 'Wrong ID Website';
        $reponse['return'] = 4;
        return $reponse;
    } elseif (SHA1($post_data['query'].$local_id_website.$local_secure_key) != $uns_msg['sign']) { //Check if sent sign if the same as local
        $reponse['message'] = 'The signature is incorrect';
        $reponse['debug'] = 'The signature is incorrect';
        $reponse['return'] = 5;
        return $reponse;
    } else {
        $reponse['message'] = 'Identifiants Client Ok';
        $reponse['debug'] = 'Identifiants Client Ok';
        $reponse['return'] = 1;
        $reponse['sign'] = SHA1($post_data['query'].$local_id_website.$local_secure_key);
        return $reponse;
    }
}
/* ############ END ############*/
/**############ FUNCTION ############ **/
/**
 * Website configuration update
 *
 * @param $post_data
 * Config Prestashop mis à jour :
 * AV_PROCESSINIT : (varchar) onorder or onorderstatuschange | Event which initiate the review request to customer
 * AV_ORDERSTATESCHOOSEN : (array) Array of choosen status to get orders
 * AV_GETPRODREVIEWS : (varchar) yes or no | Get products reviews
 * AV_DISPLAYPRODREVIEWS : (varchar) yes or no | Display products reviews
 * AV_SCRIPTFIXE_ALLOWED : (varchar) yes or non | Display fix widget
 * AV_SCRIPTFLOAT_ALLOWED: (varchar) yes or non | Display float widget
 * AV_SCRIPTFIXE : (varchar) script Js | JS for fix widget
 * AV_SCRIPTFIXE_POSITION : (varchar) left or right | Fix widget position
 * AV_SCRIPTFLOAT : (varchar) script Js | JS for float widget
 * AV_FORBIDDEN_EMAIL : (array) Domain name on emails for which we can't request reviews to customer
 * @return $reponse : error code + error
 */
function getOrdersCsv(&$post_data)
{
    $reponse = array();
    $uns_msg = iniInfo($post_data);
    $statut =  $uns_msg['orderstates'];
    $duree = $uns_msg['duree'];
    $o_av = new NetReviewsModel;
    $msg = $o_av->exportApi($duree, $statut);
    $reponse['debug'] = 'success';
    $reponse['return'] = 1;
    $reponse['message'] = $msg;
    return $reponse;
}

function setModuleConfiguration(&$post_data)
{
    //Multisite structure: updateValue($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    $reponse = array();
    $uns_msg = iniInfo($post_data);
    $delay = $uns_msg['delay'];
    $delay_by_status = (isset($uns_msg['Delay_by_status'])?$uns_msg['Delay_by_status']:'');
    $delay_product = $uns_msg['delay_product'];
    $id_shop = getCurrentShop($uns_msg);
    $group_name = getGroupname($id_shop,$uns_msg);
    $id_shop_comp = getCurrentShopComp($id_shop,$group_name);
    if (!empty($uns_msg)) {
        Configuration::updateValue('AV_PROCESSINIT'.$group_name, $uns_msg['init_reviews_process'], false, null, $id_shop);
        // Implode if more than one element so is_array
        $orderstatechoosen = (is_array($uns_msg['id_order_status_choosen'])) ?
            implode(';', $uns_msg['id_order_status_choosen']) :
            $uns_msg['id_order_status_choosen'];
        Configuration::updateValue('AV_ORDERSTATESCHOOSEN'.$group_name, $orderstatechoosen, false, null, $id_shop);
        Configuration::updateValue('AV_DELAY'.$group_name, $delay, false, null, $id_shop);
        Configuration::updateValue('AV_DELAY_BYSTATUS'.$group_name, $delay_by_status, false, null, $id_shop);
        Configuration::updateValue('AV_DELAY_PRODUIT'.$group_name, $delay_product, false, null, $id_shop);
        Configuration::updateValue('AV_GETPRODREVIEWS'.$group_name, $uns_msg['get_product_reviews'], false, null, $id_shop);
        Configuration::updateValue('AV_DISPLAYPRODREVIEWS'.$group_name, $uns_msg['display_product_reviews'], false, null, $id_shop);
        Configuration::updateValue('AV_SCRIPTFIXE_ALLOWED'.$group_name, $uns_msg['display_fixe_widget'], false, null, $id_shop);
        Configuration::updateValue('AV_SCRIPTFIXE_POSITION'.$group_name, $uns_msg['position_fixe_widget'], false, null, $id_shop);
        Configuration::updateValue('AV_SCRIPTFLOAT_ALLOWED'.$group_name, $uns_msg['display_float_widget'], false, null, $id_shop);
        Configuration::updateValue('AV_URLCERTIFICAT'.$group_name, $uns_msg['url_certificat'], false, null, $id_shop);
        // Implode if more than one element so is_array
        $forbiddenemail = (is_array($uns_msg['forbidden_mail_extension'])) ?
            implode(';', $uns_msg['forbidden_mail_extension']) :
            $uns_msg['forbidden_mail_extension'];
        Configuration::updateValue('AV_FORBIDDEN_EMAIL'.$group_name, $forbiddenemail, false, null, $id_shop);
        Configuration::updateValue(
            'AV_SCRIPTFIXE'.$group_name,
            htmlentities(str_replace(array("\r\n", "\n"), '', $uns_msg['script_fixe_widget'])),
            true,
            null,
            $id_shop
        );
        Configuration::updateValue(
            'AV_SCRIPTFLOAT'.$group_name,
            htmlentities(str_replace(array("\r\n", "\n"), '', $uns_msg['script_float_widget'])),
            true,
            null,
            $id_shop
        );
        Configuration::updateValue('AV_CODE_LANG'.$group_name, $uns_msg['code_lang'], false, null, $id_shop);
        $reponse['sign'] = SHA1(
            $post_data['query'].
            Configuration::get('AV_IDWEBSITE'.$group_name, false, null, $id_shop).
            Configuration::get('AV_CLESECRETE'.$group_name, false, null, $id_shop)
        );

        $reponse['message'] = getModuleAndSiteInfos($id_shop, $id_shop_comp, $group_name);
        $reponse['debug'] = 'La configuration du site a été mise à jour';
        $reponse['return'] = 1;
        $reponse['query'] = $post_data['query'];
        Configuration::updateValue('NETREVIEWS_CONFIGURATION_OK', true);
    } else {
        $reponse['debug'] = "Aucune données reçues par le site dans $_POST[message]";
        $reponse['message'] = "Aucune données reçues par le site dans $_POST[message]";
        $reponse['query'] = $post_data['query'];
        $reponse['return'] = 2;
        $reponse['sign'] = SHA1(
            $post_data['query'].
            Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).
            Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop)
        );
    }
    return $reponse;
}
/**
 * truncate content on tables av_products_reviews et av_products_average
 *
 * @param $post_data : sent parameters
 * @return $reponse : array to debug info
 */
function truncateTables(&$post_data)
{
    $reponse = array();
    $uns_msg = iniInfo($post_data);
    $query = array();
    $multisite = Configuration::get('AV_MULTISITE');
    $query[] = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'av_products_reviews;';
    $query[] = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'av_products_average;';
    $query[] = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'av_products_reviews (
                  `id_product_av` varchar(36) NOT NULL,
                  `ref_product` varchar(20) NOT NULL,
                  `rate` varchar(5) NOT NULL,
                  `review` text NOT NULL,
                  `customer_name` varchar(30) NOT NULL,
                  `horodate` text NOT NULL,
                  `horodate_order` text NOT NULL,
                  `discussion` text NULL,
                  `helpful` int(7) DEFAULT 0,
                  `helpless` int(7) DEFAULT 0,
                  `media_full` text NULL,
                  `iso_lang` varchar(5) DEFAULT "0",
                  `id_shop` int(2) DEFAULT 0,
                  PRIMARY KEY (`id_product_av`,`iso_lang`,`id_shop`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    $query[] = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'av_products_average (
                  `id_product_av` varchar(36) NOT NULL,
                  `ref_product` varchar(20) NOT NULL,
                  `rate` varchar(5) NOT NULL,
                  `nb_reviews` int(10) NOT NULL,
                  `horodate_update` text NOT NULL,
                  `iso_lang` varchar(5) DEFAULT "0",
                  `id_shop` int(2) DEFAULT 0,
                  PRIMARY KEY (`ref_product`,`iso_lang`,`id_shop`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    $reponse['return'] = 1;
    $reponse['debug'] = 'Tables truncated';
    $reponse['message'] = 'Tables truncated';
    foreach ($query as $sql) {
        if (!Db::getInstance()->Execute($sql)) {
            $reponse['return'] = 2;
            $reponse['debug'] = 'Tables not truncated';
            $reponse['message'] = 'Tables not truncated';
        }
    }
    $reponse['query'] = $query;
    return $reponse;
}

/**
 * truncate content on tables av_products_reviews et av_products_average
 *
 * @param $post_data : sent parameters
 * @return $reponse : array to debug info
 */
function setFlag(&$post_data)
{
    $reponse = array();
    $uns_msg = iniInfo($post_data);

    // $id_shop = getCurrentShop($uns_msg);
    $multisite = Configuration::get('AV_MULTISITE');
    $id_shop = (!empty($multisite))? $uns_msg['id_shop']:'';
    $id_lang = "";
    $log = "";
    $all = false;
    if (!is_numeric($id_shop)){
        $decompose_idshop = explode("_", $id_shop);
        if(count($decompose_idshop) == 3){
            $id_shop = $decompose_idshop[0];
            $id_lang = $decompose_idshop[1];
            $all = ($decompose_idshop[2] == "all")? true:false;
        }else if(count($decompose_idshop) == 2){
            $id_shop = $decompose_idshop[0];
            $id_lang = ($decompose_idshop[1] != "all")? $decompose_idshop[1]:"";
            $all = ($decompose_idshop[1] == "all")? true:false;
        }
    }
    //add "_all" at the end of id_shop, ex: 1_all or 1_fr_all, this is a secret parameter which when you put all, you can setFlag 0 to all the order, otherwise only the missing orders will be taken in account
    if(($uns_msg['setFlag'] == '0'|| $uns_msg['setFlag'] == '1') && !empty($uns_msg['datePeriod'])){
        $start_date = (!empty($uns_msg['startDate']))?$uns_msg['startDate']:'1970-01-01';
        $end_date = (!empty($uns_msg['endDate']))?$uns_msg['endDate']:'NOW()';
        $where_id_shop = (!empty($id_shop))?" WHERE o.id_shop = ".(int)$id_shop:" WHERE TRUE";
        $where_id_lang = (!empty($id_lang))?' AND lg.iso_code = "'.intval($id_lang).'"':'';
        $duree_sql = ' AND (select DATE_FORMAT(o.date_add, "%Y-%m-%d")) BETWEEN "'.pSQL($start_date).'" AND "'.pSQL($end_date).'"';
        $where_timespan = ($uns_msg['datePeriod']=="allOrders")?'':$duree_sql;

        $query = 'SELECT o.id_order, lg.iso_code, o.date_add, o.id_shop FROM '._DB_PREFIX_.'orders o LEFT JOIN '._DB_PREFIX_.'lang lg ON o.id_lang = lg.id_lang'.$where_id_shop.$where_id_lang.$where_timespan;
        $orders_list = Db::getInstance()->ExecuteS($query);
        $nbrow_fitcondition = Db::getInstance()->numRows();
        $i = 0;
        foreach ($orders_list as $order) {

            $qry_order = 'SELECT id_order, flag_get FROM '._DB_PREFIX_.'av_orders WHERE id_order = '.$order['id_order'].' AND flag_get != '.intval($uns_msg['setFlag']);
            $order_to_update = Db::getInstance()->getRow($qry_order, false);

            if (!$order_to_update & !empty($order_to_update)) {
                Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'av_orders (id_order, id_shop, iso_lang, flag_get, horodate_now) VALUES ('.$order['id_order'].','.$order['id_shop'].',"'.$order['iso_code'].'","'.intval($uns_msg['setFlag']).'","'.pSQL($order['date_add']).'")');
                $i++;
                $log .= " <br> ".$i.". ".$order['date_add']." #".$order['id_order']." is interted";
            }else{
                if ($order_to_update['flag_get'] != $uns_msg['setFlag']) {
                    Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'av_orders SET flag_get = "'.intval($uns_msg['setFlag']).'" WHERE id_order = '.(int)$order['id_order']);
                    $i++;
                    $log .= " <br> ".$i.". ".$order['date_add']." #".$order['id_order']." is updated";
                }
            }
        }

        $return_message = ($nbrow_fitcondition)?$nbrow_fitcondition.' commande(s) sont flagé à '.$uns_msg['setFlag']:'Aucunes commandes sont récupérées :';
        $return_message .= $log;
        $reponse['return'] = 1;
        $reponse['id_shop'] = $id_shop;
        $reponse['debug'] = $query;
        $reponse['message'] = $return_message;
    }else{
        $reponse['return'] = 3;
        $reponse['id_shop'] = $id_shop;
        $reponse['message'] = 'S\'il vous plaît, ne pas oublier remplir tout les conditions';
    }
    return $reponse;
}
/**
 * Check if module is installed and enabled
 *
 * @param $post_data : sent parameters
 * @return state
 */
function isActiveModule(&$post_data)
{
    $reponse = array();
    $active = false;
    $uns_msg = iniInfo($post_data);
    //$id_shop = getCurrentShop($uns_msg);
    $id_shop = !empty($uns_msg['id_shop']) ? $uns_msg['id_shop'] : null;

    $group_name = getGroupname($id_shop,$uns_msg);
    $id_shop_comp = getCurrentShopComp($id_shop,$group_name);

    $multisite = Configuration::get('AV_MULTISITE');
    if (!empty($id_shop)) {
        $id_module = Db::getInstance()->getValue('SELECT id_module FROM '._DB_PREFIX_.'module WHERE name = \'netreviews\'');
        if (Db::getInstance()->getValue('SELECT id_module
                                            FROM '._DB_PREFIX_.'module_shop
                                            WHERE id_module = '.(int)$id_module.'
                                            AND id_shop = '.(int)$id_shop)) {
            $active = true;
        }
    } else {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if(Module::isEnabled('netreviews') == 1){
                $active = true;
            }
        }else{
            if (Db::getInstance()->getValue('SELECT active FROM '._DB_PREFIX_.'module WHERE name LIKE \'netreviews\'')) {
                $active = true;
            }
        }
    }
    if (!$active) {
        $reponse['debug'] = 'Module disabled';
        $reponse['return'] = 2; //Module disabled
        $reponse['query'] = 'isActiveModule';
        return $reponse;
    }
    $reponse['debug'] = 'Module installed and enabled';
    $reponse['sign'] = SHA1(
        $post_data['query'].
        Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).
        Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop)
    );

    $reponse['id_shop'] = ($id_shop_comp)?$id_shop_comp:$id_shop;
    $reponse['return'] = 1; //Module OK
    $reponse['query'] = $post_data['query'];
    return $reponse;
}
/**
 * Get module and site configuration
 *
 * @param $post_data : sent parameters
 * @return $reponse : array to debug info
 */
function getModuleAndSiteConfiguration(&$post_data)
{
    $reponse = array();
    $uns_msg = iniInfo($post_data);
    //$id_shop = getCurrentShop($uns_msg);
    $id_shop = $uns_msg['id_shop'];
    $group_name = getGroupname($id_shop,$uns_msg);
    $id_shop_comp = getCurrentShopComp($id_shop,$group_name);
    $reponse['message'] = getModuleAndSiteInfos($id_shop, $id_shop_comp, $group_name);
    $reponse['id_shop'] = ($id_shop_comp)?$id_shop_comp:$id_shop;
    $reponse['sign'] = SHA1(
        $post_data['query'].
        Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).
        Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop)
    );
    if (isset($reponse['query']) && !empty($reponse['query'])) {
        $reponse['query'] = $uns_msg['query'];
    }
    if (empty($reponse['message'])) {
        $reponse['return'] = 2;
    } else {
        $reponse['return'] = 1;
    }
    return $reponse;
}
/**
 * Get orders
 *
 * @param $query : $post_data
 * @return orders (array)
 */
function getOrders(&$post_data)
{
    // Permet de rendre optionel la demande d'avis pour les id produit contenu dans ce tableau.
    $product_exception = array(
        //15
    );
    // Permet de rendre optionel la demande d'avis pour les marketplace contenu dans ce tableau.
    $global_marketplaces = array(
        // 1 => 'priceminister'
    );
    // Ici un tableau d'id catégories autorisées.
    $idcategories = array(
        // 3
    );
    // Ici un tableau d'id catégories et sous catégories a tester.
    $idcategorietotest = array();

    $group_name = '';

    foreach ($idcategories as $idcat) {
        array_push($idcategorietotest, $idcat);
        $tabcat = getcategoriesrecurcive($idcat);
        foreach ($tabcat as $cat) {
            array_push($idcategorietotest, $cat);
        }
    }

    $reponse = array();
    $post_message = iniInfo($post_data);
    $order_statut_list = OrderState::getOrderStates((int)Configuration::get('PS_LANG_DEFAULT'));
    $order_statut_indice = array();
    foreach ((array)$order_statut_list as $value) {
        $order_statut_indice[$value['id_order_state']] = $value['name'];
    }
    $id_shop = getCurrentShop($post_message);
    $group_name = getGroupname($id_shop,$post_message);
    $id_shop_comp = getCurrentShopComp($id_shop,$group_name);
    $id_shop_filter_sql = (!empty($id_shop))?" AND id_shop = ".(int)$id_shop:"";
    $query_id_shop = (!empty($id_shop))?" AND o.id_shop = ".(int)$id_shop:"";
    $allowed_products = Configuration::get('AV_GETPRODREVIEWS'.$group_name, null, null, $id_shop);
    $process_choosen = Configuration::get('AV_PROCESSINIT'.$group_name, null, null, $id_shop);
    $order_status_choosen = Configuration::get('AV_ORDERSTATESCHOOSEN'.$group_name, null, null, $id_shop);
    $forbidden_mail_extensions = explode(';', Configuration::get('AV_FORBIDDEN_EMAIL'.$group_name, null, null, $id_shop));
    $query_iso_lang = '';
    $query_status = '';
    $product_price_limit = Configuration::get('AV_MINAMOUNTPRODUCTS', null, null, $id_shop);
    $product_price_limit = is_numeric($product_price_limit)?$product_price_limit:-1;
    if ($process_choosen == 'onorderstatuschange' && !empty($order_status_choosen)) {
        $order_status_choosen = str_replace(';', ',', $order_status_choosen);
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $query_status = ' AND o.current_state IN ('.pSQL($order_status_choosen).')';
        }else{
            $query_status = 'AND (SELECT `id_order_state` FROM '._DB_PREFIX_.'order_history WHERE `id_order`=o.id_order GROUP BY `id_order_state` asc ORDER BY `'._DB_PREFIX_.'order_history`.`date_add` DESC limit 0,1) IN ('.pSQL($order_status_choosen).')';
        }
    }else{
        //order trim
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $query_status = " AND oh.id_order_state = o.current_state";
        }else{
            $query_status = ' AND oh.id_order_state = (SELECT `id_order_state` FROM '._DB_PREFIX_.'order_history WHERE `id_order`=o.id_order GROUP BY `id_order_state` asc ORDER BY `'._DB_PREFIX_.'order_history`.`date_add` DESC limit 0,1)';
        }
    }
    if (isset($post_message['iso_lang'])) {
        $o_lang = new Language;
        $id_lang = $o_lang->getIdByIso(Tools::strtolower($post_message['iso_lang']));
        $query_iso_lang .= ' AND o.id_lang = '.intval($id_lang);
    }
    if (Configuration::get('AV_MULTILINGUE', null, null, $id_shop) == 'checked') {
        $sql = 'SELECT value FROM '._DB_PREFIX_.'configuration WHERE name = "AV_GROUP_CONF'.pSQL($group_name).'"'.$id_shop_filter_sql;
        if ($row = Db::getInstance()->getRow($sql)) {
            $list_iso_lang_multilingue = unserialize($row['value']);
        }
        $ids_lang = '';
        foreach ($list_iso_lang_multilingue as $code_iso) {
            $o_lang = new Language;
            $id_lang = $o_lang->getIdByIso(Tools::strtolower($code_iso));
            $ids_lang .= "'".intval($id_lang)."',";
        }
        $ids_lang = Tools::substr($ids_lang, 0, -1);
        $query_iso_lang .= ' AND o.id_lang in ('.$ids_lang.')';
    }

    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
        $query = 'SELECT  o.module, oav.id_order, o.date_add as date_order,oh.date_add as date_last_status,o.id_customer,o.total_paid,o.id_lang,
      o.id_shop, oh.id_order_state, o.current_state as state_order
            FROM '._DB_PREFIX_.'av_orders oav
            LEFT JOIN '._DB_PREFIX_.'orders o
            ON oav.id_order = o.id_order
            LEFT JOIN '._DB_PREFIX_.'order_history oh
            ON oh.id_order = o.id_order
            WHERE (oav.flag_get IS NULL OR oav.flag_get = 0)
            AND o.module NOT IN ("'.pSQL(implode('", "', $global_marketplaces)).'")'
            .$query_status.$query_id_shop.$query_iso_lang;
    } else {
        $query = 'SELECT o.module,
        oav.id_order,
        o.date_add AS date_order,
        oh.date_add AS date_last_status,
        o.id_customer,
        o.total_paid,
        o.id_lang,
        oav.id_shop,
        oh.id_order_state,
        (SELECT `id_order_state` FROM '._DB_PREFIX_.'order_history WHERE `id_order`=o.id_order GROUP BY `id_order_state` asc ORDER BY `'._DB_PREFIX_.'order_history`.`date_add` DESC limit 0,1) AS state_order
        FROM '._DB_PREFIX_.'av_orders oav
        LEFT JOIN '._DB_PREFIX_.'orders o ON oav.id_order = o.id_order
        LEFT JOIN '._DB_PREFIX_.'order_history oh ON oh.id_order = o.id_order
        WHERE (oav.flag_get IS NULL OR oav.flag_get = 0)
        AND o.module NOT IN ("'.implode('", "', $global_marketplaces).'")'
            .$query_status.$query_id_shop.$query_iso_lang;
    }
    $orders_list = Db::getInstance()->ExecuteS($query);
    $reponse['debug'][] = $query;
    $reponse['debug']['mode'] = '['.$process_choosen.'] '.Db::getInstance()->numRows().' commandes récupérées';
    $orders_list_toreturn = array();
    $forbidden_mail_extensions_string = implode(';',$forbidden_mail_extensions);
    if ($orders_list) {
        // $test = array();
        // $test = array_unique($orders_list);
        foreach ($orders_list as $order) {
            // Test if customer email domain is forbidden (marketplaces case)
            $o_customer = new Customer($order['id_customer']);
            $customer_email_extension = explode('@', $o_customer->email);
            $find_occurence_forbidden_email = array();
            foreach($forbidden_mail_extensions as $forbidden_mail){
                if(!empty($forbidden_mail)){
                    if (strpos($customer_email_extension[1], $forbidden_mail) === false) {
                        $find_occurence_forbidden_email[]= "no";
                    }else{
                        $find_occurence_forbidden_email[]= "found";
                    }
                }
            }

            $o_order = new Order($order['id_order']);

            if (!in_array("found", $find_occurence_forbidden_email)) {
                // $marketplaceKey = array_search($order['module'], $global_marketplaces);
                // if (!empty($marketplaceKey)) {
                //     $marketplace = $global_marketplaces[$marketplaceKey];
                // } else {
                //     $marketplace = "non";
                // }
                switch ($order['state_order']) {
                    // case 2 :
                    //     $delay_specifique = 5;
                    //     break;
                    default:
                        $delay_specifique = null;
                        break;
                }
                $delay_product_specifique = null;
                $delay_product_get = Configuration::get('AV_DELAY_PRODUIT'.$group_name, null, null, $order['id_shop']);
                if (!empty($delay_product_get) && !empty($delay_specifique)) {
                    $delay_product_specifique = $delay_specifique + $delay_product_get;
                }
                $order_reference = (isset($o_order->reference) && !empty($o_order->reference))?$o_order->reference:"";
                $carrier = new Carrier((int)$o_order->id_carrier);
                $num_state = $order['state_order'];
                $array_order = array(
                    'id_order' => $order['id_order'],
                    'reference' => $order_reference,
                    'payment' => $o_order->payment,
                    'carrier' => $carrier->name,
                    'id_lang' => $order['id_lang'],
                    'iso_lang' => pSQL(Language::getIsoById($order['id_lang'])),
                    'id_shop' => $order['id_shop'],
                    'amount_order' => $order['total_paid'],
                    'id_customer' => $order['id_customer'],
                    'state_order' => $order_statut_indice[$num_state].'('.$num_state.')',//  Status added here
                    'state_order_id' => $num_state,//  Status number
                    'date_order' => strtotime($order['date_order']), // date timestamp in orders table
                    'date_last_status_change' => $order['date_last_status'],
                    'date_order_formatted' => $order['date_order'], // date in orders table formatted
                    'firstname_customer' => $o_customer->firstname,
                    'lastname_customer' => $o_customer->lastname,
                    'email_customer' => $o_customer->email,
                    'delay_commande_specifique' => $delay_specifique,
                    'products' => array()
                );
                //  Add products to array
                if (!empty($allowed_products) && $allowed_products == 'yes') {
                    $products_in_order = $o_order->getProducts();
                    $array_products = array();
                    $i = 0;
                    $max_product = (int)Configuration::get('AV_NBOPRODUCTS'.$group_name, null, null, $order['id_shop']);
                    $shop_name = Configuration::get('PS_SHOP_NAME');
                    foreach ($products_in_order as $product_element) {
                        if (!in_array($product_element['product_id'], $product_exception) && ($product_element['product_price'] > $product_price_limit)) {
                            //&& Product::idIsOnCategoryId($product_element['product_id'],$listofcategoriesaccepted)
                            $product_category = "";
                            if(isset($product_element['id_category_default']) && !empty($product_element['id_category_default'])){
                                $product_category_create = new Category($product_element['id_category_default'], $order['id_lang']);
                                $product_category = $product_category_create->name;
                            }
                            if(isset($product_element['id_manufacturer']) && !empty($product_element['id_manufacturer'])){
                                $o_manufacturer = new Manufacturer($product_element['id_manufacturer']);
                                $brand_name =  $o_manufacturer->name;
                            }
                            $product_title_image_id = (isset($product_element['image']) && !empty($product_element['image']))?$product_element['image']->id_image:"";
                            $upc = (isset($product_element['upc']) && !empty($product_element['upc']))?$product_element['upc']:"";
                            $ean13 = (isset($product_element['ean13']) && !empty($product_element['ean13']))?$product_element['ean13']:"";
                            $sku = (isset($product_element['reference']) && !empty($product_element['reference']))?$product_element['reference']:"";
                            $mpn = (isset($product_element['supplier_reference']) && !empty($product_element['supplier_reference']))?$product_element['supplier_reference']:"";
                            //si déclinaisons
                            $product_name = $product_element['product_name'];
                            if (strpos($product_name, '-') !== false && strpos($product_name, ':') !== false && strlen($product_name) > 50) {
                                $product_name_array = explode(" - ",$product_name);
                                $product_name = $product_name_array[0];
                            }

                            $uniquegoogleshoppinginfo = Configuration::get('AV_PRODUCTUNIGINFO', null, null, $order['id_shop']);
                            if($uniquegoogleshoppinginfo == 1){
                                $product_upc = $upc;
                                $product_ean13 = $ean13;
                                $product_sku = $sku;
                                $product_mpn = $mpn;
                                // $o_product = new Product($product_element['product_id'], false, $order['id_lang']);
                            }else{
                                $product_upc = (isset($product_element['product_upc']) && !empty($product_element['product_upc']))?$product_element['product_upc']:$upc;
                                $product_ean13 = (isset($product_element['product_ean13']) && !empty($product_element['product_ean13']))?$product_element['product_ean13']:$ean13;
                                $product_sku = (isset($product_element['product_reference']) && !empty($product_element['product_reference']))?$product_element['product_reference']:$sku;
                                $product_mpn = (isset($product_element['product_supplier_reference']) && !empty($product_element['product_supplier_reference']))?$product_element['product_supplier_reference']:$mpn;
                            }
                            $product = array(
                                'id_product' => $product_element['product_id'],
                                'name_product' => $product_name,
                                'SKU' => $product_sku,
                                'GTIN_EAN' => $product_ean13,
                                'GTIN_UPC' => $product_upc,
                                'MPN' => $product_mpn,
                                'brand_name' => (isset($brand_name) && !empty($brand_name))? $brand_name:$shop_name,
                                'category' => $product_category,
                                'url_image' => NetReviewsModel::getUrlImageProduct($product_element['product_id'], $product_title_image_id, $order['id_lang']), //array_url['url_image_product'],
                                'url' => NetReviewsModel::getUrlProduct($product_element['product_id'], $order['id_lang']),
                                'delay_produit_specifique' => $delay_product_specifique,
                                'product_price_unity' => $product_element['product_price']
                            );
                            // limit product reviews
                            if (isset($max_product) && !empty($max_product)) {
                                if ($max_product > 0 && $i < $max_product) {
                                    array_push($array_products, $product);
                                }
                            } else {
                                array_push($array_products, $product);
                            }
                            unset($product);
                        }
                        $i++;
                    }
                    $array_order['products'] = $array_products;
                    unset($array_products);
                }
                $orders_list_toreturn[$order['id_order']] = $array_order;
                /*   if ($order['total_paid'] > 30) { // price limit
               $orders_list_toreturn[$order['id_order']] = $array_order;
            }*/
            } else {
                $reponse['message']['Emails_Interdits'][] = 'Commande n°'.$order['id_order'].' Email:'.$o_customer->email;
            }
            // Set orders as getted but do not if it's a test request
            if (!isset($post_message['no_flag']) || $post_message['no_flag'] == 0) {
                Db::getInstance()->Execute(
                    'UPDATE '._DB_PREFIX_.'av_orders
                SET horodate_get = "'.time().'", flag_get = 1
                WHERE id_order = '.(int)$order['id_order']
                );
            }
        }
    } //end check variable orders_list
    // Purge Table
    $nb_orders_purge = Db::getInstance()->getValue('SELECT count(id_order)
                                                    FROM '._DB_PREFIX_.'av_orders
                                                    WHERE horodate_now < DATE_SUB(NOW(), INTERVAL 6 MONTH)');
    $reponse['debug']['purge'] = '[purge] '.$nb_orders_purge.' commandes purgées';
    Db::getinstance()->Execute('DELETE FROM '._DB_PREFIX_.'av_orders WHERE horodate_now < DATE_SUB(NOW(), INTERVAL 6 MONTH)');
    $reponse['return'] = 1;
    $reponse['query'] = $post_message['query'];
    $reponse['message']['nb_orders'] = count($orders_list_toreturn);
    $reponse['message']['list_orders'] = $orders_list_toreturn;
    $reponse['debug']['force'] = $post_message['force'];
    $reponse['debug']['no_flag'] = $post_message['no_flag'];
    $reponse['message']['delay_product'] = Configuration::get('AV_DELAY_PRODUIT'.$group_name, null, null, $id_shop) + Configuration::get('AV_DELAY'.$group_name, null, null, $id_shop);
    $reponse['message']['delay'] = Configuration::get('AV_DELAY'.$group_name, null, null, $id_shop);
    $reponse['sign'] = SHA1(
        $post_message['query'].
        Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).
        Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop)
    );
    return $reponse;
}
/**
 * Product reviews update
 *
 * @param $post_data : sent parameters
 * @return
 */
function setProductsReviews(&$post_data)
{

    $reponse = array();
    $microtime_deb = microtime();
    $uns_msg = iniInfo($post_data);
    $reviews = (!empty($uns_msg['data'])) ? json_decode($uns_msg['data'], true) : null;


    // $id_shop = getCurrentShop($uns_msg);
    $multisite = Configuration::get('AV_MULTISITE');
    $id_shop = (!empty($multisite) && isset($uns_msg['id_shop']))? $uns_msg['id_shop']:'';

    if (!is_numeric($id_shop)){
        $decompose_idshop = explode("_", $id_shop);
        $id_shop = $decompose_idshop[0];
    }

    $group_name = getGroupname($id_shop,$uns_msg);
    $id_shop_filter_sql = (!empty($id_shop))?" AND id_shop = ".(int)$id_shop: " AND (id_shop IN (0, 1) OR id_shop IS NULL)";

    $av_group_name = 'AV_GROUP_CONF'.$group_name;

    $sql = 'SELECT value FROM '._DB_PREFIX_.'configuration WHERE name = "'.pSQL($av_group_name).'"'.$id_shop_filter_sql;

    if ($row = Db::getInstance()->getRow($sql)) {
        $list_iso_lang_multilingue = unserialize($row['value']);
        $iso_lang = '"'.pSQL($list_iso_lang_multilingue[0]).'"';
    }else {
        $iso_lang = '0';
    }
    //add horodate_order if colcumn dosen't exsit
    $orderdate_added = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'av_products_reviews');
    if (is_array($orderdate_added) && !array_key_exists('horodate_order', $orderdate_added)) {
        Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'av_products_reviews`
            ADD `horodate_order` TEXT NOT NULL AFTER `horodate`');
    } elseif (is_array($orderdate_added) && !array_key_exists('helpful', $orderdate_added) && !array_key_exists('helpless', $orderdate_added)) {
        Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'av_products_reviews`
            ADD `helpful` int(7) DEFAULT 0,
            ADD `helpless` int(7) DEFAULT 0');
    } elseif (is_array($orderdate_added) && !array_key_exists('media_full', $orderdate_added)) {
        Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'av_products_reviews`
            ADD `media_full` TEXT');
    }

    $reponse['message']['nb_new'] = isset($reviews['NEW']) ? changeProductReviews("NEW", $reviews['NEW'], $iso_lang, $id_shop) : 0;
    $reponse['message']['nb_update'] = isset($reviews['UPDATE']) ? changeProductReviews("UPDATE", $reviews['UPDATE'], $iso_lang, $id_shop) : 0;
    $reponse['message']['nb_delete'] = isset($reviews['DELETE']) ? changeProductReviews("DELETE", $reviews['DELETE'], $iso_lang, $id_shop) : 0;
    $reponse['message']['nb_average'] = isset($reviews['AVG']) ? changeProductAverage($reviews['AVG'], $iso_lang, $id_shop) : 0;

    $microtime_fin = microtime();
    $reponse['return'] = 1;
    $reponse['sign'] = SHA1(
        $post_data['query'].
        Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).
        Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop)
    );
    $reponse['query'] = $post_data['query'];
    $reponse['message']['microtime'] = (float)$microtime_fin - (float)$microtime_deb;
    // ****************** Check Received Number of Reviews vs Saved Number of Reviews *****************
    $savedModule = $reponse['message']['nb_new'] + $reponse['message']['nb_update'] + $reponse['message']['nb_delete'] + $reponse['message']['nb_average'];
    $receivedNew = isset($reviews['NEW']) ? count($reviews['NEW']) : 0;
    $receivedUpdate = isset($reviews['UPDATE']) ? count($reviews['UPDATE']) : 0;
    $receivedDelete = isset($reviews['DELETE']) ? count($reviews['DELETE']) : 0;
    $receivedAvg = isset($reviews['AVG']) ? count($reviews['AVG']) : 0;
    if($savedModule != ($receivedNew + $receivedUpdate + $receivedDelete + $receivedAvg)){
        $reponse['debug'][] = 'An error occured. Mismatch between number of reviews received and the number of reviews saved in DB';
    }
    //******************** End Check ******************************************************************
    return $reponse;
}
// Update the average for each product
function changeProductAverage(&$averages, $iso_lang, $id_shop){

    $count = 0;
    foreach($averages As $average){
            Db::getInstance()->Execute('REPLACE INTO '._DB_PREFIX_.'av_products_average
                                    (id_product_av, ref_product, rate, nb_reviews,
                                    horodate_update,iso_lang,id_shop)
                                    VALUES (\''.pSQL($average['idProduit']).'\',
                                    \''.pSQL($average['refProduit']).'\',
                                    \''.round((float)$average['averageProduit'], 2).'\',
                                    \''.(int)$average['nbAvisProduit'].'\',
                                    \''.time().'\',
                                    '.$iso_lang.',
                                    '.(int)$id_shop.'
                                    )');
            $count++;

    }
    return $count;
}

// Update Product Reviews with new reviews, changed reviews or deleted reviews
function changeProductReviews($type, &$reviews, $iso_lang, $id_shop){

    $count = 0;
    if(($type == "NEW") || ($type == "UPDATE")){
        foreach($reviews As $review){

            $name = "";
            if(isset($review['name'][0])){
                $name = $review['name'][0];
            }

        Db::getInstance()->Execute('REPLACE INTO '._DB_PREFIX_.'av_products_reviews
            (id_product_av, ref_product, rate, review, horodate, customer_name,horodate_order,
            discussion,helpful,helpless,media_full,iso_lang,id_shop)
            VALUES (\''.pSQL($review['idProduit']).'\',
                    \''.(int)$review['refProduit'].'\',
                    \''.round((float)$review['rate'], 2).'\',
                    \''.pSQL($review['avis']).'\',
                    \''.pSQL($review['horodateAvis']).'\',
                    \''.pSQL(Tools::ucfirst($name).'. '.Tools::ucfirst($review['prenom'])).'\',
                    \''.pSQL($review['horodateCommande']).'\',
                    \''.pSQL(NetReviewsModel::acEncodeBase64(NetReviewsModel::avJsonEncode($review['moderation']))).'\',
                    \''.pSQL($review['count_helpful_yes']).'\',
                    \''.pSQL($review['count_helpful_no']).'\',
                    \''.pSQL(urldecode(NetReviewsModel::acDecodeBase64($review['media_full']))).'\',
                    '.$iso_lang.',
                    '.(int)$id_shop.'
                    )');
            $count++;
        }
    } elseif($type == "DELETE"){
        foreach($reviews As $review){
            if (Configuration::get('AV_MULTILINGUE', null, null, $id_shop) == 'checked') {
                    Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'av_products_reviews
                                                WHERE id_product_av = \''.pSQL($review['idProduit']).'\'
                                                AND ref_product = \''.(int)$review['refProduit'].'\'
                                                AND iso_lang = '.$iso_lang.'
                                                AND id_shop = '.(int)$id_shop);
                } else {
                    Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'av_products_reviews
                                                WHERE id_product_av = \''.pSQL($review['idProduit']).'\'
                                                AND ref_product = \''.(int)$review['refProduit'].'\'
                                                AND id_shop = '.(int)$id_shop);
                }
            $count++;    
        }

    } 

    return $count;
}

/**
 * Get module and site infos
 * Private function, do not use it. This function is called in setModuleConfiguration and getModuleConfiguration
 * @param $post_data
 * @return array with info data
 */

function getModuleAndSiteInfos($id_shop = null, $id_shop_comp = null, $group_name = null)
{
    $module_version = new NetReviews;
    $module_version = $module_version->version;
    $order_statut_list = OrderState::getOrderStates((int)Configuration::get('PS_LANG_DEFAULT'));
    $perms = fileperms(_PS_MODULE_DIR_.'netreviews');
    if (($perms & 0xC000) == 0xC000) {    // Socket
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) { // Symbolic link
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) { // Regular
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) { // Block special
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) { // Repository
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) { // Special characters
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) { // pipe FIFO
        $info = 'p';
    } else { // Unknow
        $info = 'u';
    }
    // Others
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    // All
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    $explode_secret_key = explode('-', Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop));
    $return = array(
        'Version_PS' => _PS_VERSION_,
        'Version_Module' => $module_version,
        'idWebsite' => Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop),
        'Nb_Multiboutique' => '',

        'Mode_multilingue' => 0,
        'list_iso_lang_multilingue' => '',
        'Websites' => '',


        'Current_shop_id' => ($id_shop_comp)?$id_shop_comp:$id_shop,
        'Cle_Secrete' => $explode_secret_key[0].'-xxxx-xxxx-'.$explode_secret_key[3],
        'Delay' => Configuration::get('AV_DELAY'.$group_name, null, null, $id_shop),
        'Delay_by_status' => Configuration::get('AV_DELAY_BYSTATUS'.$group_name, false, null, $id_shop),
        'Delay_product' => Configuration::get('AV_DELAY_PRODUIT'.$group_name, false, null, $id_shop),
        'Initialisation_du_Processus' => Configuration::get('AV_PROCESSINIT'.$group_name, null, null, $id_shop),
        'Statut_choisi' => Configuration::get('AV_ORDERSTATESCHOOSEN'.$group_name, null, null, $id_shop),
        'Recuperation_Avis_Produits' => Configuration::get('AV_GETPRODREVIEWS'.$group_name, null, null, $id_shop),
        'Affiche_Avis_Produits' => Configuration::get('AV_DISPLAYPRODREVIEWS'.$group_name, null, null, $id_shop),
        'Affichage_Widget_Flottant' => Configuration::get('AV_SCRIPTFLOAT_ALLOWED'.$group_name, null, null, $id_shop),
        'Script_Widget_Flottant' => Configuration::get('AV_SCRIPTFLOAT'.$group_name, null, null, $id_shop),
        'Affichage_Widget_Fixe' => Configuration::get('AV_SCRIPTFIXE_ALLOWED'.$group_name, null, null, $id_shop),
        'Position_Widget_Fixe' => Configuration::get('AV_SCRIPTFIXE_POSITION'.$group_name, null, null, $id_shop),
        'Script_Widget_Fixe' => Configuration::get('AV_SCRIPTFIXE'.$group_name, null, null, $id_shop),
        'Emails_Interdits' => Configuration::get('AV_FORBIDDEN_EMAIL'.$group_name, null, null, $id_shop),
        'Liste_des_statuts' => $order_statut_list,
        'Droit_du_dossier_AV' => $info,
        'Date_Recuperation_Config' => date('Y-m-d H:i:s')
    );


    $multilanguages_shop_list = getMultilangshoplist($id_shop);
    $return['Mode_multilingue'] = (!empty($multilanguages_shop_list))?1:0;
    // find configurated shop
    $sql = 'SELECT * FROM '._DB_PREFIX_.'configuration WHERE name LIKE "AV_IDWEBSITE%" AND value = "'.Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).'"';
    if ($row = Db::getInstance()->ExecuteS($sql)) {
        $confwithcurrent_ids = $row;
    }
    $confed_shops_infos = $multilanguages_shop_list;
    $all_confed_shops = array();
    $lang_index = str_replace("_", "", $group_name);
    foreach ($confwithcurrent_ids as $key => $value) {
        if(isset($confed_shops_infos[$value['id_shop']]) && !empty($confed_shops_infos[$value['id_shop']])){
            $all_confed_shops[$value['id_shop']] = $confed_shops_infos[$value['id_shop']][$lang_index];
        }
    }
    $return['list_iso_lang_multilingue'] = $all_confed_shops;
    if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 1) {
        $return['Nb_Multiboutique'] = Shop::getTotalShops();

        $all_shops = Shop::getShops();
        if(!empty($multilanguages_shop_list)){
            foreach ($all_shops as $key => $oneshop) {
                if (isset($multilanguages_shop_list[$oneshop['id_shop']])){
                    $all_shops[$key]['multilingual'] = $multilanguages_shop_list[$oneshop['id_shop']];
                }
            }
        }
        $return['Websites'] = $all_shops;
    }
    return $return;
}
/**
 * Return history of one commande
 *
 * @param $post_data : sent parameters
 * @return array with info data
 */
function getOrderHistoryOn(&$post_data)
{
    $reponse = array();
    $array_history = array();
    $post_message = NetReviewsModel::avJsonDecode(NetReviewsModel::acDecodeBase64($post_data['message']), true);
    $post_message = (array)$post_message;
    $ref_vente = $post_message['orderRef'];
    if (!empty($ref_vente)) {
        $o_lang = new Language;
        $id_lang = $o_lang->getIdByIso(Tools::strtolower('fr'));
        $sql = 'SELECT oh.id_order, oh.id_order_state, os.name, oh.date_add
                FROM  '._DB_PREFIX_."order_history oh
                LEFT JOIN "._DB_PREFIX_."order_state_lang os ON os.id_order_state = oh.id_order_state
                WHERE  `id_order` = ".(int)$ref_vente."
                AND id_lang = ".(int)$id_lang."
                ORDER BY  `date_add` DESC";
        if (!$array_history = Db::getInstance()->ExecuteS($sql)) {
            $reponse['return'] = 2;
        }
    }
    $reponse['return'] = 1;
    $reponse['message']['count_states'] = count($array_history);
    $reponse['message']['list_states'] = $array_history;
    $id_shop = getCurrentShop($uns_msg);
    $group_name = getGroupname($id_shop,$uns_msg);
    $reponse['sign'] = SHA1(
        $post_data['query'].
        Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $id_shop).
        Configuration::get('AV_CLESECRETE'.$group_name, null, null, $id_shop)
    );

    return $reponse;
}
/**
 * Return day count orders
 *
 * @param $post_data : sent parameters
 * @return array with info data
 */
function getCountOrder(&$post_data)
{
    $reponse = array();
    $post_message = NetReviewsModel::avJsonDecode(NetReviewsModel::acDecodeBase64($post_data['message']), true);
    $post_message = (array)$post_message;
    $sql_id_shop = '';
    $sql_iso_lang = '';
    $ids_lang = array();
    $multisite = Configuration::get('AV_MULTISITE');
    $post_message['id_shop'] = (!empty($multisite))? $post_message['id_shop']:'';
    if (!empty($post_message['id_shop'])) {
        if (Configuration::get('AV_MULTILINGUE', null, null, $post_message['id_shop']) == 'checked') {
            $sql_id_shop .= ' and id_shop = '.(int)$post_message['id_shop'];
            $sql = 'SELECT name
                    FROM '._DB_PREFIX_."configuration
                    where value = '".pSQL($post_message['idWebsite'])."'
                    and name like 'AV_IDWEBSITE_%'
                    and id_shop = ".(int)$post_message['id_shop'];
            if ($row = Db::getInstance()->getRow($sql)) {
                $group_name = '_'.Tools::substr($row['name'], 13);
            }
            $av_group_conf = unserialize(Configuration::get('AV_GROUP_CONF'.$group_name, null, null, $post_message['id_shop']));
            $o_lang = new Language;
            foreach ($av_group_conf as $isolang) {
                $ids_lang[] = $o_lang->getIdByIso(Tools::strtolower($isolang));
            }
            $sql_iso_lang .= ' and id_lang in ("'.implode('","', $ids_lang).'")';
        } else {
            $sql_id_shop .= ' and id_shop = '.(int)$post_message['id_shop'];
        }
    } else {
        if (Configuration::get('AV_MULTILINGUE') == 'checked') {
            $sql = 'SELECT name
                    FROM '._DB_PREFIX_."configuration
                    where value = '".pSQL($post_message['idWebsite'])."'
                    and name like 'AV_IDWEBSITE_%'
                    and id_shop is null ";
            if ($row = Db::getInstance()->getRow($sql)) {
                $group_name = '_'.Tools::substr($row['name'], 13);
            }
            $av_group_conf = unserialize(Configuration::get('AV_GROUP_CONF'.$group_name));
            $o_lang = new Language;
            foreach ($av_group_conf as $isolang) {
                $ids_lang[] = $o_lang->getIdByIso(Tools::strtolower($isolang));
            }
            $sql_iso_lang .= ' and id_lang in ("'.implode('","', $ids_lang).'")';
        }
    }

    $sql = 'SELECT COUNT( * )
            FROM '._DB_PREFIX_.'orders
            WHERE (
            date_add
            BETWEEN DATE_SUB( NOW( ) , INTERVAL 1 DAY )
            AND NOW( )
            )'
        .$sql_iso_lang.$sql_id_shop;

    $reponse['message']['count_orders_day'] = Db::getInstance()->getValue($sql);
    $reponse['return'] = 1;

    if (!empty($post_message['id_shop'])) {
        if (Configuration::get('AV_MULTILINGUE', null, null, $post_message['id_shop']) == 'checked') {
            $sql = 'SELECT name
                    FROM '._DB_PREFIX_."configuration
                    where value = '".pSQL($post_message['idWebsite'])."'
                    and name like 'AV_IDWEBSITE_%'
                    and id_shop = ".(int)$post_message['id_shop'];
            if ($row = Db::getInstance()->getRow($sql)) {
                $group_name = '_'.Tools::substr($row['name'], 13);
            }
            $reponse['sign'] = SHA1(
                $post_message['query'].
                Configuration::get('AV_IDWEBSITE'.$group_name, null, null, $post_message['id_shop']).
                Configuration::get('AV_CLESECRETE'.$group_name, null, null, $post_message['id_shop'])
            );
        } else {
            $reponse['sign'] = SHA1(
                $post_data['query'].
                Configuration::get('AV_IDWEBSITE', null, null, $post_message['id_shop']).
                Configuration::get('AV_CLESECRETE', null, null, $post_message['id_shop'])
            );
        }
    } else {
        if (Configuration::get('AV_MULTILINGUE') == 'checked') {
            $sql = 'SELECT name
                    FROM '._DB_PREFIX_."configuration
                    where value = '".pSQL($post_message['idWebsite'])."'
                    and name like 'AV_IDWEBSITE_%'
                    and id_shop is null ";
            if ($row = Db::getInstance()->getRow($sql)) {
                $group_name = '_'.Tools::substr($row['name'], 13);
            }
            $reponse['sign'] = SHA1(
                $post_data['query'].
                Configuration::get('AV_IDWEBSITE'.$group_name).
                Configuration::get('AV_CLESECRETE'.$group_name)
            );
        } else {
            $reponse['sign'] = SHA1(
                $post_data['query'].
                Configuration::get('AV_IDWEBSITE').
                Configuration::get('AV_CLESECRETE')
            );
        }
    }
    return $reponse;
}
/**
 * Récupération des catégories de produits .
 *
 * @param $idcategories : id de catégorie
 * @param $arraycategories : array de catégorie
 * @return $arraycategories
 */
function getcategoriesrecurcive($idcategories)
{
    $arraycategories = array();
    if (Category::getChildren($idcategories, 1)) {
        $result = Category::getChildren($idcategories, 1);
        foreach ($result as $row) {
            array_push($arraycategories, $row['id_category']);
            if (Category::getChildren($row['id_category'], 1)) {
                $arraycategories = array_merge($arraycategories, getcategoriesrecurcive($row['id_category']));
            }
        }
        return $arraycategories;
    } else {
        return $arraycategories;
    }
}

function getGroupname($id_shop,$uns_msg)
{
    if (Configuration::get('AV_MULTILINGUE', null, null, $id_shop) == 'checked') {
        $id_shop_filter_sql = (!empty($id_shop))?"AND id_shop = ".(int)$id_shop:"";
        $sql = 'SELECT name
        FROM '._DB_PREFIX_."configuration
        WHERE value = '".pSQL($uns_msg['idWebsite'])."'
        AND name like 'AV_IDWEBSITE_%'".$id_shop_filter_sql;
        $row = Db::getInstance()->getRow($sql);
        if ($row){
            return '_'.Tools::substr($row['name'], 13);
        }
    }
}

function getCurrentShop($uns_msg)
{
    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
        $sql = 'SELECT id_shop
            FROM '._DB_PREFIX_."configuration
            WHERE value = '".pSQL($uns_msg['idWebsite'])."'
            AND name like 'AV_IDWEBSITE%' ";
        $row = Db::getInstance()->getRow($sql);
        return $row['id_shop'];
    }else{
        return $uns_msg['id_shop'];
    }
}

function getCurrentShopComp($id_shop,$group_name){
    $multilanguages_shop_list = getMultilangshoplist($id_shop);

    if (!empty($multilanguages_shop_list) && !empty($group_name)  ){
        {
            $lang_index = str_replace("_", "", $group_name);
            $id_shop_comp = $id_shop."_".$multilanguages_shop_list[$id_shop][$lang_index][0];
            return $id_shop_comp;
        }
    }
}

function getMultilangshoplist($id_shop){
    $id_shop_filter_sql = (!empty($id_shop))?" AND id_shop = ".(int)$id_shop:" AND (id_shop IN (0, 1) OR id_shop IS NULL)";

    $sql = 'SELECT id_shop,value FROM '._DB_PREFIX_.'configuration WHERE name LIKE "AV_GROUP_CONF%"'.$id_shop_filter_sql;
    if ($row = Db::getInstance()->ExecuteS($sql)) {
        $multilanguages_shop_list = array();
        foreach ($row as $r_element) {
            if (Configuration::get('AV_MULTILINGUE', null, null, $r_element['id_shop']) == 'checked') {
                $multilanguages_shop_list[$r_element['id_shop']][] = unserialize($r_element['value']);
            }
        }
        return $multilanguages_shop_list;
    }
}

function iniInfo($post_data)
{
    $get_message = NetReviewsModel::avJsonDecode(NetReviewsModel::acDecodeBase64($post_data['message']), true);
    $get_message_decode = NetReviewsModel::avJsonDecode(NetReviewsModel::acDecodeBase64SetP($post_data['message']), true);
    $uns_msg = ($get_message)? $get_message :$get_message_decode;
    return (array)$uns_msg;
}