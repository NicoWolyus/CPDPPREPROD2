<?php
header("Content-type: text/xml");
/*
================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
  Ce fichier fait partie du logiciel Atoo-Sync GesCom Pro.
  Vous n'êtes pas autorisé à le modifier, à le recopier, à le vendre ou le redistribuer.
  Cet en-tête ne doit pas être retiré.

      Script : AtooSyncGesComPro-PS.php
    Boutique : PrestaShop
      Auteur : Atoo Next SARL (support@atoo-next.net)
   Copyright : 2009-2020 Atoo Next SARL
     Version : 6.13.0.0 - 20 Janvier 2020
================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
*/
define('_ATOOSYNCSCRIPTVERSION_', '62000');
define('_ECOMMERCESHOP_', 'PrestaShop');

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html");

$admindir = '';
// Essaye de trouve le dossier d'admin pour PrestaShop 1.4.x et 1.5.x
$files= glob("../../*/ajaxfilemanager", GLOB_ONLYDIR);
if (is_array($files)) {
    foreach (glob("../../*/ajaxfilemanager", GLOB_ONLYDIR) as $filename) {
        $admindir = str_replace('../../', '', $filename);
        $admindir = str_replace('/ajaxfilemanager', '', $admindir);
    }
}
if ($admindir == '') {
    // Essaye de trouve le dossier d'admin pour PrestaShop 1.6
    foreach (glob("../../*/filemanager", GLOB_ONLYDIR) as $filename) {
        $admindir = str_replace('../../', '', $filename);
        $admindir = str_replace('/filemanager', '', $admindir);
    }
}
define('_PS_ADMIN_DIR_', dirname(__FILE__) .'/../../'.$admindir);
require_once(dirname(__FILE__).'/../../config/config.inc.php');
//@ini_set('display_errors', 'on');
//@error_reporting(E_ALL | E_STRICT);

// Script requis
require_once 'AtooSync-userfunctions.php';
require_once 'AtooSync-configuration.php';
require_once 'AtooSync-product.php';
require_once 'AtooSync-category.php';
require_once 'AtooSync-order.php';
require_once 'AtooSync-order-return.php';
require_once 'AtooSync-order-slip.php';
require_once 'AtooSync-attachment.php';
require_once 'AtooSync-customer.php';
require_once 'AtooSync-prices.php';
require_once 'AtooSync-tools.php';

/* Selon la version de PrestaShop la version des images est différente */
if (isPrestaShop17()) {
    require_once 'AtooSync-image-ps17.php';
} elseif (isPrestaShop161()) {
    require_once 'AtooSync-image-ps161.php';
} elseif (isPrestaShop15() or isPrestaShop16()) {
    require_once 'AtooSync-image-ps15.php';
} else {
    require_once 'AtooSync-image.php';
}

/* Le fichier AtooSync-userfunctions.php customisé sur le site */
if (file_exists('_AtooSync-userfunctions.php')) {
    require_once '_AtooSync-userfunctions.php';
}
  
if (!defined('_PS_BASE_URL_')) {
    if (method_exists('Tools', 'getShopDomain')) {
        define('_PS_BASE_URL_', Tools::getShopDomain(true));
    } elseif (method_exists('Tools', 'getHttpHost')) {
        define('_PS_BASE_URL_', 'http://'.Tools::getHttpHost(false, true));
    } else {
        define('_PS_BASE_URL_', 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8'));
    }
}
$cookie = new Cookie('ps');

/* Défini un context par défaut */
if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
    $id_employee = Db::getInstance()->getValue('SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` WHERE `id_profile` =1');
    Shop::setContext(Shop::CONTEXT_ALL);
    Context::getContext()->employee = new Employee($id_employee);
    Context::getContext()->cookie->passwd = Context::getContext()->employee->passwd;
    Context::getContext()->link = new Link();
    Context::getContext()->cart = new Cart();
    if (method_exists('Currency', 'isMultiCurrencyActivated')) {
        if (Currency::isMultiCurrencyActivated()) {
            Context::getContext()->currency = Currency::getDefaultCurrency();
        }
    }
}

// Si on est en POST ou GET
// Atoo-Sync GesCom Pro utilise le POST pour communiquer avec le script.
// Le GET n'est utilisé que pour le debogage dans un navigateur.
if (isset($_GET['cmd'])) {
    $_ARG = $_GET;
} else {
    if (isset($_POST['cmd'])) {
        $_ARG = $_POST;
    }
}
// si les arguments sont vides, on affiche rien.
if (empty($_ARG)) {
    exit;
}

// si la variable XML n'existe pas alors essaye de la retrouver depuis la QUERY_STRING
if (!array_key_exists('xml', $_ARG)) {
    parse_str($_SERVER['QUERY_STRING'], $output);
    if (array_key_exists('xml', $output)) {
        $_ARG['xml'] = $output['xml'];
    }
}

// vérifie si le module Atoo-Sync est activé
$mod = Module::getInstanceByName('atoosyncgescompro');
if ((int)($mod->active) == 0) {
    echo "ERROR";
    echo "<br />The module Atoo-Sync is not installed in PrestaShop";
    exit;
}
// Si il y a une restriction par Adresse IP
if (Configuration::get('ATOOSYNC_IPADDRESS') !='') {
    if (!in_array($_SERVER['REMOTE_ADDR'], explode(',', Configuration::get('ATOOSYNC_IPADDRESS')))) {
        echo "ERROR";
        echo "<br />IP address '".$_SERVER["REMOTE_ADDR"]."' is not allowed";
        exit;
    }
}
// Si il y a une restriction par Hôte.
if (Configuration::get('ATOOSYNC_HOSTNAME') !='') {
    $ips =array();
    foreach (explode(',', Configuration::get('ATOOSYNC_HOSTNAME')) as $host) {
        array_push($ips, gethostbyname($host));
    }
    
    if (!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
        echo "ERROR";
        echo "<br />IP address '".$_SERVER["REMOTE_ADDR"]."' is not allowed";
        exit;
    }
}
// Si le mot de passe est vide ou non renseigné
// ou si le mot de passe ne correspond pas à la configuration dans PrestaShop
if (!isset($_ARG['pass']) || empty($_ARG['pass']) || (sha1(Configuration::get('ATOOSYNC_PASSWORD')) != $_ARG['pass'])) {
    echo "ERROR";
    echo "<br />Password does not match";
    exit;
}
        
        
//
$result = 0;

switch ($_ARG['cmd']) {
    
    case 'pricestart':
    {
        AtooSyncPriceStart();
        $result=1;
        break;
    }
    
    case 'priceend':
    {
        AtooSyncPriceEnd();
        $result=1;
        break;
    }
    
    case 'stockstart':
    {
        AtooSyncStockStart();
        $result=1;
        break;
    }
    
    case 'stockend':
    {
        AtooSyncStockEnd();
        $result=1;
        break;
    }
    case 'fromstart':
    {
        AtooSyncFromStart();
        $result=1;
        break;
    }
    
    case 'fromend':
    {
        AtooSyncFromEnd();
        $result=1;
        break;
    }

    case 'statusstart':
    {
        AtooSyncStatusStart();
        $result=1;
        break;
    }
    
    case 'statutsend':
    {
        AtooSyncStatusEnd();
        $result=1;
        break;
    }
    
    case 'customersstart':
    {
        AtooSyncCustomersStart();
        $result=1;
        break;
    }
    
    case 'customersstop':
    {
        AtooSyncCustomersStop();
        $result=1;
        break;
    }

    case 'deletetempfile':
    {
        DeleteTempFiles($_ARG['filename']);
        $result=1;
        break;
    }

    case 'getlanguages':
    {
        $result = GetLanguages();
        break;
    }
    case 'getpayements':
    {
        $result=GetPayements();
        break;
    }
    
    case 'getstatuses':
    {
        $result=GetStatuses();
        break;
    }
    
    case 'productstaxeslist':
    {
        $result=ProductsTaxsList();
        break;
    }
    
    case 'gettaxes':
    {
        $result=GetTaxes();
        break;
    }
        
    case 'getcarriers':
    {
        $result=GetCarriers();
        break;
    }
    
    case 'getzones':
    {
        $result=GetZones();
        break;
    }
    
    case 'getgroupshops':
    {
        $result = GetGroupShops();
        break;
    }
    
    case 'getshops':
    {
        $result = GetShops();
        break;
    }
    
    case 'getorders':
    {
        $result = GetXMLOrders($_ARG['from'], $_ARG['to'], $_ARG['status'], $_ARG['shops'], $_ARG['reload'], $_ARG['all']);
        break;
    }
    
    case 'setcustomeraccount':
    {
        $result = SetCustomerAccount($_ARG['id'], $_ARG['accountnumber']);
        break;
    }
    
    case 'setordertransferred':
    {
        $result = SetOrderTransferred($_ARG['id']);
        break;
    }
    
    case 'changeorderstatut':
    {
        ChangeOrderStatut($_ARG['id'], $_ARG['newstatut'], $_ARG['number']);
        $result=1;
        break;
    }
    
    case 'setordershippingnumber':
    {
        SetOrderShippingNumber($_ARG['id'], $_ARG['number']);
        $result=1;
        break;
    }
    
    case 'setorderpayments':
    {
        $result = SetOrderPayments($_ARG['xml']);
        break;
    }
    
    case 'setdeliverydate':
    {
        $result = SetDeliveryDate($_ARG['id'], $_ARG['date']);
        break;
    }
    
    case 'createorder':
    {
        $result = CreateOrder($_ARG['xml']);
        break;
    }
    
    case 'setordersliptransferred':
    {
        $result = SetOrderSlipTransferred($_ARG['id']);
        break;
    }
    
    case 'setorderreturntransferred':
    {
        $result = SetOrderReturnTransferred($_ARG['id']);
        break;
    }
    /* Gestion des articles */
    case 'getproducts':
    {
        GetProducts();
        $result=1;
        break;
    }
    
    case 'productexist':
    {
        $result = ProductExist($_ARG['reference']);
        break;
    }
    
    case 'addproduct':
    {
        $result = AddProduct($_ARG['xml']);
        break;
    }
    
    case 'setproductprice':
    {
        SetProductPrice($_ARG['xml']);
        $result=1;
        break;
    }

    case 'setproductquantity':
    {
        SetProductQuantity($_ARG['xml']);
        $result=1;
        break;
    }
    
    case 'getproduct':
    {
        $result = GetProduct($_ARG['reference']);
        break;
    }
    
    case 'setproductactive':
    {
        $result = SetProductActive($_ARG['reference'], $_ARG['active']);
        break;
    }
    case 'getfeatures':
    {
        $result = GetFeatures();
        break;
    }
    case 'getfeature':
    {
        $result = GetFeature($_ARG['feature']);
        break;
    }
    case 'addfeaturevalue':
    {
        $result = AddFeatureValue($_ARG['xml']);
        break;
    }
    case 'deletefeaturevalue':
    {
        $result = DeleteFeatureValue($_ARG['featurevalue']);
        break;
    }
    case 'deleteproductfeatures':
    {
        $result = DeleteProductFeatures($_ARG['reference']);
        break;
    }
    case 'getproducturl':
    {
        $result = GetProductURL($_ARG['reference']);
        break;
    }
    /* Fin */
    
    /* Gestion des clients et des groupes de clients */
    case 'getcustomers':
    {
        GetCustomers();
        $result=1;
        break;
    }
    
    case 'disablecustomer':
    {
        $result = DisableCustomer($_ARG['accountnumber']);
        break;
    }
    
    case 'addcustomer':
    {
        $result = AddCustomer($_ARG['xml']);
        break;
    }
    
    case 'addcustomersgroups':
    {
        $result = AddCustomersGroups($_ARG['xml']);
        break;
    }
    case 'getcustomersgroups':
    {
        $result = GetCustomersGroups();
        break;
    }
    /* Fin de gestion des clients et des groupes de clients */
    
    /* Gestion des remises, module Tiers */
    case 'discountmoduleisinstalled':
    {
        $result = DiscountModuleIsInstalled();
        break;
    }
    case 'addcustomerdiscount':
    {
        $result = AddCustomerDiscount($_ARG['xml']);
        break;
    }
    
    case 'initcategoriesdiscount':
    {
        $result = InitCategoriesDiscount($_ARG['xml']);
        break;
    }
    
    case 'addcategorydiscount':
    {
        $result = AddCategoryDiscount($_ARG['xml']);
        break;
    }
    /* Fin de gestion des remises */
    
    /* Gestion des documents joints */
    case 'downloadattachment':
    {
        DownloadAttachment($_ARG['attachment']);
        $result=1;
        break;
    }

    case 'getattachments':
    {
        GetAttachments();
        $result=1;
        break;
    }
    case 'getattachment':
    {
        $result = GetAttachment($_ARG['attachment']);
        break;
    }
    case 'getattachmentid':
    {
        $result = GetAttachmentId($_ARG['atoosyncfile']);
        break;
    }
    case 'addattachment':
    {
        $result = AddAttachment($_ARG['xml']);
        break;
    }
    case 'attachmentexist':
    {
        $result= AttachmentExist($_ARG['atoosyncfile']);
        break;
    }
    case 'deleteattachment':
    {
        DeleteAttachment($_ARG['id']);
        $result=1;
        break;
    }
    /* Fin de gestion des documents joints */
    
    /* Gestion des images */
    case 'addimage':
    {
        // si la variable XML n'existe pas alors on la retrouve depuis la QUERY_STRING
        if (Tools::getIsset('xml') == false) {
            parse_str($_SERVER['QUERY_STRING'], $output);
            $result = AddImage($output['xml']);
        } else {
            $result = AddImage($_ARG['xml']);
        }
        break;
    }
    
    case 'getimage':
    {
        GetImage($_ARG['id']);
        $result=1;
        break;
    }
    
    case 'getimageid':
    {
        $result = GetImageId($_ARG['atoosyncid']);
        break;
    }
    
    case 'setimageid':
    {
        $result = SetImageId($_ARG['atoosyncid'], $_ARG['id']);
        break;
    }
    
    case 'deleteimages':
    {
        $result = DelImages($_ARG['reference']);
        break;
    }
    
    case 'deleteimage':
    {
        $result = DelImage($_ARG['id']);
        break;
    }
    
    case 'setcoverimage':
    {
        $result = SetCoverImage($_ARG['reference'], $_ARG['atoosyncid']);
        break;
    }
    
    case 'setimageposition':
    {
        $result = SetImagePosition($_ARG['atoosyncid'], $_ARG['direction']) ;
        break;
    }
    
    case 'getimageposition':
    {
        $result = GetImagePosition($_ARG['atoosyncid']);
        break;
    }
    
    case 'getimagesposition':
    {
        $result = GetImagesPosition($_ARG['reference']);
        break;
    }
    /* Fin de gestion des images */
    
    /* Gestion des catégories */
    case 'getcategories':
    {
        $result = GetCategories();
        break;
    }
    case 'getcategory':
    {
        $result = GetCategory($_ARG['category']);
        break;
    }
    case 'addcategory':
    {
        $result = AddCategory($_ARG['xml']);
        break;
    }
    case 'deletecategory':
    {
        $result = DeleteCategory($_ARG['category']);
        break;
    }
    case 'setcategoryactive':
    {
        $result = SetCategoryActive($_ARG['id'], $_ARG['active']);
        break;
    }
    case 'setcategoryparent':
    {
        $result = SetCategoryParent($_ARG['category'], $_ARG['parent']);
        break;
    }
    case 'setproductcategorydefault':
    {
        $result = SetProductCategoryDefault($_ARG['reference'], $_ARG['category']);
        break;
    }
    case 'setproductonlycategory':
    {
        $result = SetProductToOnlyCategory($_ARG['reference'], $_ARG['category']);
        break;
    }
    case 'addproducttocategory':
    {
        $result = AddProductToCategory($_ARG['reference'], $_ARG['category']);
        break;
    }
    case 'deleteproductfromcategory':
    {
        $result = DeleteProductFromCategory($_ARG['reference'], $_ARG['category']);
        break;
    }
    case 'regeneratecategoryntree':
    {
        $result = RegenerateCategoryNTree();
        break;
    }
    /* Gestion des catégories */

    
    /* Autres */
    case 'getmanufacturers':
    {
        $result = GetManufacturers();
        break;
    }
    case 'getsuppliers':
    {
        $result = GetSuppliers();
        break;
    }
    case 'getversion':
    {
        $result = GetVersion();
        break;
    }
    case 'getrootcategory':
    {
        $result = GetRootCategory();
        break;
    }
    case 'gethomecategory':
    {
        $result = GetHomeCategory();
        break;
    }
    case 'getunits':
    {
        $result = GetUnits();
        break;
    }
    case 'getcurrencies':
    {
        $result = GetCurrencies();
        break;
    }
    case 'getcountries':
    {
        $result = GetCountries();
        break;
    }
    case 'getecotaxtaxrules':
    {
        $result = GetEcoTaxTaxRules();
        break;
    }
    case 'getwharehouses':
    {
        $result = GetWharehouses();
        break;
    }
    case 'test':
    {
        $result=Test();
        break;
    }
}
//
if ($result == 0) {
    echo 'ERROR';
} else {
    echo 'OK-OK';
}
exit;
