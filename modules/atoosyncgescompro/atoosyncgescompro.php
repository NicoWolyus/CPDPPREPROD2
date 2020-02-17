<?php
/**
* 2007-2015 Atoo Next
*
* --------------------------------------------------------------------------------
*  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync /!\
* --------------------------------------------------------------------------------
*
*  Ce fichier fait partie du logiciel Atoo-Sync .
*  Vous n'êtes pas autorisé à le modifier, à le recopier, à le vendre ou le redistribuer.
*  Cet en-tête ne doit pas être retiré.
*
*  @author    Atoo Next SARL (contact@atoo-next.net)
*  @copyright 2009-2020 Atoo Next SARL
*  @license   Commercial
*  @script    atoosyncgescompro.php
*
* --------------------------------------------------------------------------------
*  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync /!\
* --------------------------------------------------------------------------------
*/
class AtooSyncGesComPro extends Module
{
    private $_html = '';
    private $_postErrors = array();

    public function __construct()
    {
        $this->name = 'atoosyncgescompro';
        $this->tab = 'others';
        $this->version = '6.20.0';
        $this->author = 'Atoo Next';
        $this->module_key = '';
        $this->need_instance = 0;
        $this->_password = Configuration::get('ATOOSYNC_PASSWORD');

        $this->bootstrap = false;
        parent::__construct();

        /* The parent construct is required for translations */
        $this->page = basename(__FILE__, '.php');
        $this->displayName = 'Atoo-Sync GesCom Sage 100 ODBC';
        $this->description = $this->l('Lier Sage 100 Gestion Commerciale à PrestaShop');

        if (empty($this->_password)) {
            $this->warning = $this->l('Le mot de passe n\'est pas spécifié.');
        }
    }

    public function install()
    {
        
        /*
            Table attribute
            2 champs ajouté
                + atoosync_gamme
                + atoosync_enumere
        */
        if ($this->fieldExist(_DB_PREFIX_."attribute", "atoosync_gamme") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute` ADD `atoosync_gamme` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."attribute", "atoosync_enumere") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute` ADD `atoosync_enumere` VARCHAR(30) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."attribute", "atoosync_enumere") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute` ADD INDEX `atoosync_enumere` (`atoosync_enumere`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table attribute_group
            1 champ ajouté
                + atoosync_gamme
                + atoosync_conditionnement
        */
        if ($this->fieldExist(_DB_PREFIX_."attribute_group", "atoosync_gamme") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute_group` ADD `atoosync_gamme` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."attribute_group", "atoosync_gamme") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute_group` ADD INDEX `atoosync_gamme` (`atoosync_gamme`) ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."attribute_group", "atoosync_conditionnement") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute_group` ADD `atoosync_conditionnement` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."attribute_group", "atoosync_conditionnement") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attribute_group` ADD INDEX `atoosync_conditionnement` (`atoosync_conditionnement`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table product_attribute
            3 champs ajoutés
                + atoosync_gamme
                + atoosync_conditionnement
                + atoosync_delete
        */
        if ($this->fieldExist(_DB_PREFIX_."product_attribute", "atoosync_gamme") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product_attribute` ADD `atoosync_gamme` VARCHAR(30) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."product_attribute", "atoosync_conditionnement") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product_attribute` ADD `atoosync_conditionnement` VARCHAR(30) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."product_attribute", "atoosync_conditionnement_qte") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product_attribute` ADD `atoosync_conditionnement_qte` INT(10) NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."product_attribute", "atoosync_delete") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product_attribute` ADD `atoosync_delete` TINYINT(1) NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."product_attribute", "atoosync_gamme") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product_attribute` ADD INDEX `atoosync_gamme` (`atoosync_gamme`) ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."product_attribute", "atoosync_conditionnement") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product_attribute` ADD INDEX `atoosync_conditionnement` (`atoosync_conditionnement`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table customer
            2 champs ajoutés
                + atoosync_code_client
                + atoosync_centrale_achat
        */
        if ($this->fieldExist(_DB_PREFIX_."customer", "atoosync_code_client") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."customer` ADD `atoosync_code_client` VARCHAR(18) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."customer", "atoosync_centrale_achat") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."customer` ADD `atoosync_centrale_achat` VARCHAR(18) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."customer", "atoosync_code_client") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."customer` ADD INDEX `atoosync_code_client` (`atoosync_code_client`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table orders
            2 champs ajoutés
                + atoosync_transfert_gescom
                + atoosync_number
        */
        if ($this->fieldExist(_DB_PREFIX_."orders", "atoosync_transfert_gescom") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."orders` ADD `atoosync_transfert_gescom` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."orders", "atoosync_number") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."orders` ADD `atoosync_number` VARCHAR(20) DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table order_return
            2 champs ajoutés
                + atoosync_transfert_gescom
                + atoosync_number
        */
        if ($this->fieldExist(_DB_PREFIX_."order_return", "atoosync_transfert_gescom") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."order_return` ADD `atoosync_transfert_gescom` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."order_return", "atoosync_number") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."order_return` ADD `atoosync_number` VARCHAR(20) DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table order_slip
            2 champs ajoutés
                + atoosync_transfert_gescom
                + atoosync_number
        */
        if ($this->fieldExist(_DB_PREFIX_."order_slip", "atoosync_transfert_gescom") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."order_slip` ADD `atoosync_transfert_gescom` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."order_slip", "atoosync_number") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."order_slip` ADD `atoosync_number` VARCHAR(20) DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table product
            2 champs ajoutés
                + atoosync
                + atoosync_codefamille
        */
        if ($this->fieldExist(_DB_PREFIX_."product", "atoosync") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product` ADD `atoosync` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."product", "atoosync_codefamille") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product` ADD `atoosync_codefamille` VARCHAR(13) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."product", "reference") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."product` ADD INDEX `reference` (`reference`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table category
            2 champs sont ajoutés
                + atoosync
                + atoosync_id
        */
        if ($this->fieldExist(_DB_PREFIX_."category", "atoosync") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."category` ADD `atoosync` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->fieldExist(_DB_PREFIX_."category", "atoosync_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."category` ADD `atoosync_id` VARCHAR(13) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."category", "atoosync_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."category` ADD INDEX `atoosync_id` (`atoosync_id`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table attachment
            1 champs ajouté
                + atoosync_file
        */
        if ($this->fieldExist(_DB_PREFIX_."attachment", "atoosync_file") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attachment` ADD `atoosync_file` VARCHAR(40) NOT NULL DEFAULT '' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."attachment", "atoosync_file") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."attachment` ADD INDEX `atoosync_file` (`atoosync_file`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table image
            1 champs ajouté
                + atoosync_image_id
        */
        if ($this->fieldExist(_DB_PREFIX_."image", "atoosync_image_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."image` ADD `atoosync_image_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."image", "atoosync_image_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."image` ADD INDEX `atoosync_image_id` (`atoosync_image_id`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table group
            1 champ ajouté
                + atoosync_id
        */
        if ($this->fieldExist(_DB_PREFIX_."group", "atoosync_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."group` ADD `atoosync_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table address
            1 champ ajouté
                + atoosync_id
        */
        if ($this->fieldExist(_DB_PREFIX_."address", "atoosync_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."address` ADD `atoosync_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."address", "atoosync_id") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."address` ADD INDEX `atoosync_id` (`atoosync_id`) ";
            Db::getInstance()->Execute($query);
        }
        /*
            Table specific_price
            1 champ ajouté
                + atoosync_type
        */
        if ($this->fieldExist(_DB_PREFIX_."specific_price", "atoosync_type") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."specific_price` ADD `atoosync_type` INT(1) UNSIGNED NOT NULL DEFAULT '0' ";
            Db::getInstance()->Execute($query);
        }
        if ($this->indexExist(_DB_PREFIX_."specific_price", "atoosync_type") == false) {
            $query = "ALTER TABLE `"._DB_PREFIX_."specific_price` ADD INDEX `atoosync_type` (`atoosync_type`) ";
            Db::getInstance()->Execute($query);
        }
        
        if (!parent::install()) {
            return false;
        }
    
        Configuration::updateValue('ATOOSYNC_PASSWORD', $this->motDePasse(20));
        Configuration::updateValue('ATOOSYNC_IPADDRESS', '');
        Configuration::updateValue('ATOOSYNC_HOSTNAME', '');
        
        Configuration::updateValue('ATOOSYNC_CHANGE_NAME', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_SHORT', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_DESCR', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_SEO', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_AVAILABLE_MSG', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_PRICE', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_SPPRICES', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_QUANTITY', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_WEIGHT', 'No');
        Configuration::updateValue('ATOOSYNC_CHANGE_EANUPC', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_ATTRIBUTES', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_CATEGORIES', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_MANUFACTURER', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_SUPPLIER', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_FEATURES', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_ACCESSORIES', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_OTHER', 'Yes');
        Configuration::updateValue('ATOOSYNC_CHANGE_DOCUMENT', 'Yes');
        Configuration::updateValue('ATOOSYNC_REINDEX_PRODUCT', 'Yes');
        
        Configuration::updateValue('ATOOSYNC_WAREHOUSES', 'warehouses');
          
        Configuration::updateValue('ATOOSYNC_DISABLE_STOCK_ZERO', 'No');
        Configuration::updateValue('ATOOSYNC_DISABLE_PRICE_ZERO', 'No');
        Configuration::updateValue('ATOOSYNC_DISPLAY_RESTOSCKING', 'No');
        Configuration::updateValue('ATOOSYNC_IGNORE_PRICE_0', 'No');
        Configuration::updateValue('ATOOSYNC_CHANGE_DOCUMENT', 'No');
        Configuration::updateValue('ATOOSYNC_REINDEX_PRODUCT', 'Yes');
    
        Configuration::updateValue('ATOOSYNC_ATTRIBUTE_GROUP', 'Yes');
        Configuration::updateValue('ATOOSYNC_ATTRIBUTE_VALUE', 'Yes');
        Configuration::updateValue('ATOOSYNC_COMBINATION_EAN13', 'No');
        Configuration::updateValue('ATOOSYNC_COMBINATION_REFERENCE', 'No');
        Configuration::updateValue('ATOOSYNC_COMBINATION_PACKAGING', 'No');
        
        Configuration::updateValue('ATOOSYNC_PACKAGING_UNIT', 'No');
        Configuration::updateValue('ATOOSYNC_CREATE_PRODUCT_PACK', 'No');
        Configuration::updateValue('ATOOSYNC_PACKAGING_EAN13', 'No');
        Configuration::updateValue('ATOOSYNC_PACKAGING_REFERENCE', 'No');
    
        Configuration::updateValue('ATOOSYNC_CLEAN_NAME', 'No');
        
        Configuration::updateValue('ATOOSYNC_CATEGORY_QUICK_CREATE', 'No');
        Configuration::updateValue('ATOOSYNC_PRODUCT_IGNORE_POS', 'No');
        
        Configuration::updateValue('ATOOSYNC_ORDER_DATE', 'orders');
        Configuration::updateValue('ATOOSYNC_ORDER_MESSAGES', 'First');
        Configuration::updateValue('ATOOSYNC_ORDER_ROUND', '2');
        Configuration::updateValue('ATOOSYNC_ORDER_FORMAT_NUMBER', 'Yes');
        Configuration::updateValue('ATOOSYNC_ORDER_USE_PAYMENT', 'No');
        Configuration::updateValue('ATOOSYNC_ORDER_PAYMENT', 'module');
        Configuration::updateValue('ATOOSYNC_ORDER_OTHERPAYMENT', '');
        Configuration::updateValue('ATOOSYNC_ORDER_COPY_REFERENCE', 'No');
        Configuration::updateValue('ATOOSYNC_ORDER_GUESTACCOUNT', '');
        Configuration::updateValue('ATOOSYNC_ORDER_POSACCOUNT', '');
        
        Configuration::updateValue('ATOOSYNC_DISCOUNT_DESCRIPTION', 'No');
    
        Configuration::updateValue('ATOOSYNC_INVOICE_STATE', '5');
        Configuration::updateValue('ATOOSYNC_OVERWRITE_ORDER', 'No');
        Configuration::updateValue('ATOOSYNC_ORDER_CREATE_INVOICE', 'No');
        
        Configuration::updateValue('ATOOSYNC_INCLUDE_ORDER_RETURN', 'No');
        Configuration::updateValue('ATOOSYNC_ORDER_RETURN_STATE', '5');
        
        Configuration::updateValue('ATOOSYNC_INCLUDE_ORDER_SLIP', 'No');
        
        Configuration::updateValue('ATOOSYNC_GROUP_UPDATENAME', 'No');
    
        Configuration::updateValue('ATOOSYNC_CUSTOMER_FIRSTNAME', 'Title');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_LASTNAME', 'Title');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_PASSWORD', 'CodeClient');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_ADDGROUP', 'Yes');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_SEND_MAIL', 'No');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_NEWSLETTER', 'Yes');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_OPTIN', 'No');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_DISCOUNT', 'No');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_DISCOUNTNAME', 'Remise client %C %P');
        
        Configuration::updateValue('ATOOSYNC_CUSTOMER_CONTACTS', 'No');
        Configuration::updateValue('ATOOSYNC_CONTACTS_ADDRESS', 'No');
    
        Configuration::updateValue('ATOOSYNC_ADRESSE_CODEISO', 'No');
        Configuration::updateValue('ATOOSYNC_ADRESSE_REMOVE', 'No');
      
        Configuration::updateValue('ATOOSYNC_UPDATE_CUSTOMER', 'No');
        Configuration::updateValue('ATOOSYNC_UPDATE_ADDRESS', 'No');
        Configuration::updateValue('ATOOSYNC_CUSTOMER_GROUP', 'No');

        return true;
    }
    
    public function uninstall()
    {
        Configuration::deleteByName('ATOOSYNC_PASSWORD');
        Configuration::deleteByName('ATOOSYNC_IPADDRESS');
        Configuration::deleteByName('ATOOSYNC_HOSTNAME');
    
        return parent::uninstall();
    }
    
    private function _postProcess()
    {
        if (isset($_POST['btnSubmit'])) {
            Configuration::updateValue('ATOOSYNC_PASSWORD', strval($_POST['password']));
            Configuration::updateValue('ATOOSYNC_IPADDRESS', strval($_POST['ipaddress']));
            Configuration::updateValue('ATOOSYNC_HOSTNAME', strval($_POST['hostname']));
            
            Configuration::updateValue('ATOOSYNC_CHANGE_NAME', strval($_POST['changename']));
            Configuration::updateValue('ATOOSYNC_CHANGE_SHORT', strval($_POST['changeshort']));
            Configuration::updateValue('ATOOSYNC_CHANGE_DESCR', strval($_POST['changedescr']));
            Configuration::updateValue('ATOOSYNC_CHANGE_SEO', strval($_POST['changeseo']));
            Configuration::updateValue('ATOOSYNC_CHANGE_AVAILABLE_MSG', strval($_POST['changeavailable']));
            Configuration::updateValue('ATOOSYNC_CHANGE_PRICE', strval($_POST['changeprice']));
            Configuration::updateValue('ATOOSYNC_CHANGE_SPPRICES', strval($_POST['changespprices']));
            Configuration::updateValue('ATOOSYNC_CHANGE_QUANTITY', strval($_POST['changequantity']));
            Configuration::updateValue('ATOOSYNC_CHANGE_WEIGHT', strval($_POST['changeweight']));
            Configuration::updateValue('ATOOSYNC_CHANGE_EANUPC', strval($_POST['changeean']));
            Configuration::updateValue('ATOOSYNC_CHANGE_ATTRIBUTES', strval($_POST['changeattributes']));
            Configuration::updateValue('ATOOSYNC_CHANGE_CATEGORIES', strval($_POST['changecategories']));
            Configuration::updateValue('ATOOSYNC_CHANGE_MANUFACTURER', strval($_POST['changemanufacturer']));
            Configuration::updateValue('ATOOSYNC_CHANGE_SUPPLIER', strval($_POST['changesupplier']));
            Configuration::updateValue('ATOOSYNC_CHANGE_FEATURES', strval($_POST['changefeatures']));
            Configuration::updateValue('ATOOSYNC_CHANGE_ACCESSORIES', strval($_POST['changeaccessories']));
            Configuration::updateValue('ATOOSYNC_CHANGE_OTHER', strval($_POST['changeotherfields']));
            Configuration::updateValue('ATOOSYNC_CHANGE_DOCUMENT', strval($_POST['changedocument']));
            Configuration::updateValue('ATOOSYNC_REINDEX_PRODUCT', strval($_POST['indexproduct']));
            
            Configuration::updateValue('ATOOSYNC_WAREHOUSES', strval($_POST['sagewarehouses']));
            
            Configuration::updateValue('ATOOSYNC_DISABLE_STOCK_ZERO', strval($_POST['stockzero']));
            Configuration::updateValue('ATOOSYNC_DISABLE_PRICE_ZERO', strval($_POST['pricezero']));
            Configuration::updateValue('ATOOSYNC_DISPLAY_RESTOSCKING', strval($_POST['restocking']));
            Configuration::updateValue('ATOOSYNC_IGNORE_PRICE_0', strval($_POST['ignorepricezero']));
            Configuration::updateValue('ATOOSYNC_CLEAN_NAME', strval($_POST['cleanname']));

            Configuration::updateValue('ATOOSYNC_COMBINATION_EAN13', strval($_POST['combinationean13']));
            Configuration::updateValue('ATOOSYNC_COMBINATION_REFERENCE', strval($_POST['combinationreference']));

            Configuration::updateValue('ATOOSYNC_ATTRIBUTE_GROUP', strval($_POST['changeattributegroupe']));
            Configuration::updateValue('ATOOSYNC_ATTRIBUTE_VALUE', strval($_POST['changeattributevalue']));
            Configuration::updateValue('ATOOSYNC_COMBINATION_PACKAGING', strval($_POST['combinationpackaging']));
            Configuration::updateValue('ATOOSYNC_PACKAGING_UNIT', strval($_POST['combinationpackagingunit']));
            Configuration::updateValue('ATOOSYNC_PACKAGING_EAN13', strval($_POST['packagingean13']));
            Configuration::updateValue('ATOOSYNC_PACKAGING_REFERENCE', strval($_POST['packagingreference']));

            Configuration::updateValue('ATOOSYNC_CREATE_PRODUCT_PACK', strval($_POST['createproductpack']));
            
            Configuration::updateValue('ATOOSYNC_CATEGORY_QUICK_CREATE', strval($_POST['categoryquickcreate']));
            Configuration::updateValue('ATOOSYNC_PRODUCT_IGNORE_POS', strval($_POST['productignoreposition']));
                    
            Configuration::updateValue('ATOOSYNC_ORDER_DATE', strval($_POST['selectdate']));
            Configuration::updateValue('ATOOSYNC_ORDER_MESSAGES', strval($_POST['messages']));
            Configuration::updateValue('ATOOSYNC_ORDER_ROUND', strval($_POST['round']));
            Configuration::updateValue('ATOOSYNC_ORDER_FORMAT_NUMBER', strval($_POST['orderformatnumber']));
            Configuration::updateValue('ATOOSYNC_ORDER_USE_PAYMENT', (string)($_POST['usepayements']));
            Configuration::updateValue('ATOOSYNC_ORDER_PAYMENT', (string)($_POST['payment']));
            if (Configuration::get('ATOOSYNC_ORDER_USE_PAYMENT') == 'Yes') {
                Configuration::updateValue('ATOOSYNC_ORDER_PAYMENT', 'payment_method');
            }
            Configuration::updateValue('ATOOSYNC_ORDER_OTHERPAYMENT', (string)($_POST['payments']));
            Configuration::updateValue('ATOOSYNC_ORDER_COPY_REFERENCE', strval($_POST['ordercopyreference']));
            Configuration::updateValue('ATOOSYNC_ORDER_GUESTACCOUNT', strval($_POST['guestaccount']));
              
            Configuration::updateValue('ATOOSYNC_IGNORE_POS_ORDERS_DAY', strval($_POST['ignoreposordersofday']));
            Configuration::updateValue('ATOOSYNC_ORDER_POSACCOUNT', strval($_POST['posaccount']));
      
            Configuration::updateValue('ATOOSYNC_DISCOUNT_DESCRIPTION', strval($_POST['discountusedescription']));
            
            Configuration::updateValue('ATOOSYNC_INVOICE_STATE', strval($_POST['created_order_state']));
            Configuration::updateValue('ATOOSYNC_ORDER_CREATE_INVOICE', strval($_POST['ordercreateinvoice']));
            Configuration::updateValue('ATOOSYNC_OVERWRITE_ORDER', strval($_POST['overwriteorder']));
            
            Configuration::updateValue('ATOOSYNC_INCLUDE_ORDER_RETURN', strval($_POST['includeorderreturn']));
            Configuration::updateValue('ATOOSYNC_ORDER_RETURN_STATE', strval($_POST['order_return_state']));
            
            Configuration::updateValue('ATOOSYNC_INCLUDE_ORDER_SLIP', strval($_POST['includeorderslip']));
                    
            Configuration::updateValue('ATOOSYNC_GROUP_UPDATENAME', strval($_POST['updategroupname']));
      
            Configuration::updateValue('ATOOSYNC_CUSTOMER_FIRSTNAME', strval($_POST['firstname']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_LASTNAME', strval($_POST['lastname']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_PASSWORD', strval($_POST['contactpwd']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_ADDGROUP', strval($_POST['addcustomergroup']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_SEND_MAIL', strval($_POST['sendmail']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_NEWSLETTER', strval($_POST['newsletter']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_OPTIN', strval($_POST['optin']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_DISCOUNT', strval($_POST['createcustomerdiscount']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_DISCOUNTNAME', strval($_POST['createcustomerdiscountname']));

            Configuration::updateValue('ATOOSYNC_CUSTOMER_CONTACTS', strval($_POST['createcontacts']));
            Configuration::updateValue('ATOOSYNC_CONTACTS_ADDRESS', strval($_POST['contactsaddresses']));

            Configuration::updateValue('ATOOSYNC_ADRESSE_CODEISO', strval($_POST['addresscodeiso']));
            Configuration::updateValue('ATOOSYNC_ADRESSE_REMOVE', strval($_POST['addressremove']));
              
            Configuration::updateValue('ATOOSYNC_UPDATE_CUSTOMER', strval($_POST['changecustomer']));
            Configuration::updateValue('ATOOSYNC_UPDATE_ADDRESS', strval($_POST['changeaddress']));
            Configuration::updateValue('ATOOSYNC_CUSTOMER_GROUP', strval($_POST['changecustomergroups']));
                                
            $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('Mise à jour réussie').'</div>';
        }
        
        if (isset($_POST['DELETEIMAGES'])) {
            $this->deleteImagesProducts();
            $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('La supression des images créées par Atoo-Sync à réussie').'</div>';
        }
        if (isset($_POST['DELETEALLIMAGES'])) {
            $this->deleteAllImagesProducts();
            $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('La supression de toutes les images des articles à réussie').'</div>';
        }
        if (isset($_POST['DELETEPRODUCTS'])) {
            $this->deleteProducts();
            $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('La supression des articles créés par Atoo-Sync à réussie').'</div>';
        }
        if (isset($_POST['DELETEATTACHMENTS'])) {
            $this->deleteAttachements();
            $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('La supression des documents créés par Atoo-Sync à réussie').'</div>';
        }
    
        if (isset($_POST['ResetOrders'])) {
            $this->resetOrders();
        }
        if (isset($_POST['ResetOrderSlips'])) {
            $this->resetOrderSlips();
        }
    }
    
    private function _postValidation()
    {
        if (isset($_POST['btnSubmit'])) {
            if (empty($_POST['password'])) {
                $this->_postErrors[] = $this->l('Le mot de passe est obligatoire.');
            }
        }
    }
    
    private function _displayAtooSync()
    {
        $this->_html .= '<img src="../modules/atoosyncgescompro/atoo-sync-gescom-om.png" style="float:left; margin-right:10px;"><b>'.$this->l('Lier Sage Gestion Commerciale à votre boutique PrestaShop.').'</b><br /><br />
		'.$this->l('Ce module permet de transférer dans la boutique PrestaShop les articles de Sage Gestion Commerciale.').'<br />
		'.$this->l('Il créé en retour dans Sage Gestion Commerciale les clients et les commandes de la boutique PrestaShop.').'<br />
		'.$this->l('Visitez le site Web').' <a href="https://www.atoo-next.net/nos-solutions/atoo-sync-gescom-sage-100-odbc/" target="_blank" style="text-decoration:underline"><b>https://www.atoo-next.net</b></a> '.$this->l('pour plus d\'informations.').'<br />
		<br />';
    }
    
    private function _displayForm()
    {
        $this->_html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        $this->_html .= $this->_fieldSetSettings();
        $this->_html .= $this->_fieldSetProducts();
        $this->_html .= $this->_fieldSetWarehouses();
        $this->_html .= $this->_fieldSetOptionsProducts();
        $this->_html .= $this->_fieldSetProductsAttrributes();
        $this->_html .= $this->_fieldSetProductsPackagings();
        $this->_html .= $this->_fieldSetProductsPack();
        $this->_html .= $this->_fieldSetCategories();
        $this->_html .= $this->_fieldSetOrders();
        $this->_html .= $this->_fieldSetKerawen();
        $this->_html .= $this->_fieldSetDiscount();
        $this->_html .= $this->_fieldSetUpdateStatuses();
        $this->_html .= $this->_fieldSetUpdateOrdersCreation();
        $this->_html .= $this->_fieldSetOrdersSlipsAndProductsReturns();
        $this->_html .= $this->_fieldSetCustomersGroups();
        $this->_html .= $this->_fieldSetCustomers();
        $this->_html .= $this->_fieldSetCustomersContacts();
        $this->_html .= $this->_fieldSetAddresses();
        $this->_html .= $this->_fieldSetCustomersModifications();
        $this->_html .= $this->_fieldSetResetOrders();
        $this->_html .= $this->_fieldSetResetOrderSlips();
        $this->_html .= $this->_fieldSetDeleteDatas();
        $this->_html .= '</form>';
    }
  
    private function _fieldSetSettings()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Réglages').'</legend>
				<label>'.$this->l('Mot de passe').'</label>
				<div class="margin-form">
					<input type="text" style="width: 300px;" name="password" value="'.Configuration::get('ATOOSYNC_PASSWORD').'" />
					<p class="clear">'.$this->l('Entrez un mot de passe pour protéger l\'accès au script Atoo-Sync GesCom Sage 100 ODBC.').'</p>
				</div>
				<label for="ipaddress">'.$this->l('Adresses IP').'</label>
				<div class="margin-form">
					<input type="text"  style="width: 300px;" name="ipaddress" value="'.Configuration::get('ATOOSYNC_IPADDRESS').'" />
					<p class="clear">'.$this->l('Adresses IP autorisées à accéder au script Atoo-Sync GesCom Sage 100 ODBC.').'<br />
					'.$this->l('Utilisez une virgule (\',\') pour les séparer (par exemple, 42.24.4.2,127.0.0.1,99.98.97.96)').'<br />
					'.$this->l('Laissez le champ vide si vous ne voulez pas activer cette option.').'</p>
				</div>
				<label for="hostname">'.$this->l('Hôtes').'</label>
				<div class="margin-form">
					<input type="text"  style="width: 300px;" name="hostname" value="'.Configuration::get('ATOOSYNC_HOSTNAME').'" />
					<p class="clear">'.$this->l('Hôtes autorisés à accéder au script Atoo-Sync GesCom Sage 100 ODBC.').'<br />
					'.$this->l('Utilisez une virgule (\',\') pour les séparer (par exemple, localhost, mypc.dyndns.org)').'<br />
					'.$this->l('Laissez le champ vide si vous ne voulez pas activer cette option.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
      </fieldset>
      <br />
      <br />';
    }
  
    private function _fieldSetProducts()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Modification des articles').'</legend>
				<label>'.$this->l('Nom de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changename" id="changename_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_NAME') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changename_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changename" id="changename_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_NAME') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changename_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour le nom de l\'article lors de la mise à jour.').'<br />
				</div>
				<label>'.$this->l('Résumé de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changeshort" id="changeshort_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_SHORT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeshort_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeshort" id="changeshort_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_SHORT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeshort_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour la résumé de l\'article lors de la mise à jour.').'<br />
				</div>
				<label>'.$this->l('Description de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changedescr" id="changedescr_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_DESCR') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changedescr_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changedescr" id="changedescr_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_DESCR') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changedescr_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour la description de l\'article lors de la mise à jour.').'<br />
				</div>
        <label>'.$this->l('SEO').'</label>
				<div class="margin-form">
					<input type="radio" name="changeseo" id="changeseo_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_SEO') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeseo_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeseo" id="changeseo_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_SEO') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeseo_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les informations de référencements/SEO de l\'article lors de la mise à jour.').'<br />
				</div>
        <label>'.$this->l('Message de disponibilité').'</label>
				<div class="margin-form">
					<input type="radio" name="changeavailable" id="changeavailable_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_AVAILABLE_MSG') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeavailable_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeavailable" id="changeavailable_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_AVAILABLE_MSG') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeavailable_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les messages de disponibilités de l\'article lors de la mise à jour.').'<br />
				</div>
				<label>'.$this->l('Prix de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changeprice" id="changeprice_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_PRICE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeprice_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeprice" id="changeprice_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_PRICE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeprice_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour le prix d\'achat, le prix de vente et la TVA de l\'article lors de la mise à jour.').'<br />
				</div>
				<label>'.$this->l('Prix spécifiques de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changespprices" id="changespprices_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_SPPRICES') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changespprices_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changespprices" id="changespprices_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_SPPRICES') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changespprices_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les prix spécifiques l\'article lors de la mise à jour.').'<br />
				</div>
        <label>'.$this->l('Quantité de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changequantity" id="changequantity_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_QUANTITY') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changequantity_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changequantity" id="changequantity_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_QUANTITY') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changequantity_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour le stock de l\'article lors de la mise à jour.').'<br />
				</div>
        <label>'.$this->l('Poids de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changeweight" id="changeweight_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeweight_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="changeweight" id="changeweight_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_WEIGHT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeweight_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour le poids de l\'article lors de la mise à jour.').'<br />
				</div>
        <label>'.$this->l('Code barre EAN/UPC').'</label>
				<div class="margin-form">
					<input type="radio" name="changeean" id="changeean_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_EANUPC') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeean_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeean" id="changeean_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_EANUPC') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeean_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les codes barres de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
        <label>'.$this->l('Fabricant/marque').'</label>
				<div class="margin-form">
					<input type="radio" name="changemanufacturer" id="changemanufacturer_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_MANUFACTURER') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changemanufacturer_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changemanufacturer" id="changemanufacturer_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_MANUFACTURER') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changemanufacturer_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour la marque de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
        <label>'.$this->l('Fournisseur').'</label>
				<div class="margin-form">
					<input type="radio" name="changesupplier" id="changesupplier_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_SUPPLIER') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changesupplier_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changesupplier" id="changesupplier_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_SUPPLIER') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changesupplier_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour le fournisseur de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
        <label>'.$this->l('Autres champs de l\'article').'</label>
				<div class="margin-form">
					<input type="radio" name="changeotherfields" id="changeotherfields_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_OTHER') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeotherfields_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeotherfields" id="changeotherfields_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_OTHER') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeotherfields_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les autres champs de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
        <label>'.$this->l('Déclinaisons').'</label>
				<div class="margin-form">
					<input type="radio" name="changeattributes" id="changeattributes_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_ATTRIBUTES') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeattributes_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeattributes" id="changeattributes_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_ATTRIBUTES') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeattributes_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les déclinaisons de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>		
        <label>'.$this->l('Catégories').'</label>
				<div class="margin-form">
					<input type="radio" name="changecategories" id="changecategories_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_CATEGORIES') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changecategories_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changecategories" id="changecategories_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_CATEGORIES') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changecategories_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les catégories de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
       <label>'.$this->l('Caractéristiques').'</label>
				<div class="margin-form">
					<input type="radio" name="changefeatures" id="changefeatures_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_FEATURES') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changefeatures_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changefeatures" id="changefeatures_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_FEATURES') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changefeatures_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les caractéristiques de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
        <label>'.$this->l('Accessoires').'</label>
				<div class="margin-form">
					<input type="radio" name="changeaccessories" id="changeaccessories_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_ACCESSORIES') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeaccessories_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
					<input type="radio" name="changeaccessories" id="changeaccessories_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_ACCESSORIES') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changeaccessories_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour les accessoires de l\'article lors de la mise à jour depuis Atoo-Sync.').'<br />
				</div>
				<label>'.$this->l('Documents des articles').'</label>
				<div class="margin-form">
					<input type="radio" name="changedocument" id="changedocument_on" value="Yes"'.((Configuration::get('ATOOSYNC_CHANGE_DOCUMENT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changedocument_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="changedocument" id="changedocument_off" value="No" '.((Configuration::get('ATOOSYNC_CHANGE_DOCUMENT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="changedocument_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Remplacer les documents en mise à jour lors de l\'export depuis Atoo-Sync.').'</p>
				</div>
        <label>'.$this->l('Réindexer').'</label>
				<div class="margin-form">
					<input type="radio" name="indexproduct" id="indexproduct_on" value="Yes"'.((Configuration::get('ATOOSYNC_REINDEX_PRODUCT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="indexproduct_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="indexproduct" id="indexproduct_off" value="No" '.((Configuration::get('ATOOSYNC_REINDEX_PRODUCT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="indexproduct_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Réindexer l\'article après la modification.').'</p>
				</div>
        <center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
      </fieldset>
      <br />
      <br />';
    }
  
  private function _fieldSetWarehouses()
    {
        return '
            <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Dépôts').'</legend>     
                <label for="sagewarehouses">'.$this->l('Associer les dépôts Sage').'</label>
				<div class="margin-form">
					<select name="sagewarehouses" style="width: 400px;">
						<option value="warehouses"'.((Configuration::get('ATOOSYNC_WAREHOUSES') == 'warehouses') ? ' selected="selected"' : '').'>'.$this->l('Aux dépots du PrestaShop').'</option>
						<option value="shops" '.((Configuration::get('ATOOSYNC_WAREHOUSES') == 'shops') ? ' selected="selected"' : '').'>'.$this->l('Aux boutiques du multiboutique PrestaShop').'</option>
					</select>
					<p class="clear">'.$this->l('Sélectionnez vers quoi associer le stock d\'un dépôt dans Sage.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
    
    private function _fieldSetOptionsProducts()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Options des articles').'</legend>     
        <label>'.$this->l('Désactiver si stock = 0').'</label>
				<div class="margin-form">
					<input type="radio" name="stockzero" id="stockzero_on" value="Yes"'.((Configuration::get('ATOOSYNC_DISABLE_STOCK_ZERO') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="stockzero_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="stockzero" id="stockzero_off" value="No" '.((Configuration::get('ATOOSYNC_DISABLE_STOCK_ZERO') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="stockzero_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Désactiver les articles dans la boutique lorsque le stock est à zéro dans Sage.').'<br />
				</div>
				<label>'.$this->l('Désactiver si prix = 0').'</label>
				<div class="margin-form">
					<input type="radio" name="pricezero" id="pricezero_on" value="Yes"'.((Configuration::get('ATOOSYNC_DISABLE_PRICE_ZERO') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="pricezero_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="pricezero" id="pricezero_off" value="No" '.((Configuration::get('ATOOSYNC_DISABLE_PRICE_ZERO') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="pricezero_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Désactiver les articles dans la boutique lorsque le prix de vente à zéro dans Sage.').'<br />
				</div>
        <label>'.$this->l('Utiliser message').'<br />'.$this->l('En réapprovisionnement').'</label>
				<div class="margin-form">
					<input type="radio" name="restocking" id="restocking_on" value="Yes"'.((Configuration::get('ATOOSYNC_DISPLAY_RESTOSCKING') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="restocking_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="restocking" id="restocking_off" value="No" '.((Configuration::get('ATOOSYNC_DISPLAY_RESTOSCKING') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="restocking_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Afficher le message de réapprovisionnement sur les articles dont le stock réel=0 mais le stock à terme>0.').'<br />
				</div>
        <label>'.$this->l('Ignorer les prix à 0').'</label>
				<div class="margin-form">
					<input type="radio" name="ignorepricezero" id="ignorepricezero_on" value="Yes"'.((Configuration::get('ATOOSYNC_IGNORE_PRICE_0') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ignorepricezero_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="ignorepricezero" id="ignorepricezero_off" value="No" '.((Configuration::get('ATOOSYNC_IGNORE_PRICE_0') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ignorepricezero_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Ne pas mettre à jour les prix des articles lorsque ceux ci sont à zéro dans la boutique.').'<br />'.$this->l('Ne fonctionne que pour les articles sans déclinaisons.').'</p>
				</div>
				<label>'.$this->l('Supprimer caractères interdits').'</label>
				<div class="margin-form">
					<input type="radio" name="cleanname" id="cleanname_on" value="Yes"'.((Configuration::get('ATOOSYNC_CLEAN_NAME') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="cleanname_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="cleanname" id="cleanname_off" value="No" '.((Configuration::get('ATOOSYNC_CLEAN_NAME') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="cleanname_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Supprimer les caractères interdits des noms des articles lors de la création ou modification des articles.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }

    private function _fieldSetProductsAttrributes()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Création ou modification des déclinaisons').'</legend>
				<label>'.$this->l('Modifier les groupes').'</label>
				<div class="margin-form">
					<input type="radio" name="changeattributegroupe" id="changeattributegroupe_on" value="Yes"'.((Configuration::get('ATOOSYNC_ATTRIBUTE_GROUP') != 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="changeattributegroupe_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="changeattributegroupe" id="changeattributegroupe_off" value="No" '.((Configuration::get('ATOOSYNC_ATTRIBUTE_GROUP') == 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="changeattributegroupe_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Modifier le nom des groupes d\'attributs en mise à jour depuis Sage.').'</p>
				</div>
				<label>'.$this->l('Modifier les valeurs').'</label>
				<div class="margin-form">
					<input type="radio" name="changeattributevalue" id="changeattributevalue_on" value="Yes"'.((Configuration::get('ATOOSYNC_ATTRIBUTE_VALUE') != 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="changeattributevalue_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="changeattributevalue" id="changeattributevalue_off" value="No" '.((Configuration::get('ATOOSYNC_ATTRIBUTE_VALUE') == 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="changeattributevalue_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Modifier le nom des valeurs des attributs en mise à jour depuis Sage.').'</p>
				</div>
				<label>'.$this->l('Rechercher sur l\'EAN13').'</label>
				<div class="margin-form">
					<input type="radio" name="combinationean13" id="combinationean13_on" value="Yes"'.((Configuration::get('ATOOSYNC_COMBINATION_EAN13') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationean13_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="combinationean13" id="combinationean13_off" value="No" '.((Configuration::get('ATOOSYNC_COMBINATION_EAN13') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationean13_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Rechercher les déclinaisons également sur le Code barre EAN13 de l\'énuméré de gamme en plus du code interne à Atoo-Sync.').'<br />
					<font color="red"><i>'.$this->l('Ne doit être activée que si les énumérés de gamme Sage ont chacun un code barre EAN13 différents.').'</i></font></p>
				</div>
				<label>'.$this->l('Rechercher sur la référence').'</label>
				<div class="margin-form">
					<input type="radio" name="combinationreference" id="combinationreference_on" value="Yes"'.((Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationreference_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="combinationreference" id="combinationreference_off" value="No" '.((Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationreference_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Rechercher les déclinaisons également sur la Référence de l\'énuméré de gamme en plus du code interne à Atoo-Sync.').'<br />
					<font color="red"><i>'.$this->l('Ne doit être activée que si les énumérés de gamme Sage ont chacun une référence différentes.').'</i></font></p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetProductsPackagings()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Article à conditionnement').'</legend>
				<label>'.$this->l('Créer les déclinaisons').'</label>
				<div class="margin-form">
					<input type="radio" name="combinationpackaging" id="combinationpackaging_on" value="Yes"'.((Configuration::get('ATOOSYNC_COMBINATION_PACKAGING') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationpackaging_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="combinationpackaging" id="combinationpackaging_off" value="No" '.((Configuration::get('ATOOSYNC_COMBINATION_PACKAGING') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationpackaging_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Créer des déclinaisons à partir du conditionnement de l\'article dans Sage Gestion Commerciale.').'<br />
					<font color="red"><i>'.$this->l('Les quantités de stock des déclinaisons peuvent être faussées.').'</i></font></p>
				</div>
				<label>'.$this->l('Créer la déclinaison unitaire').'</label>
				<div class="margin-form">
					<input type="radio" name="combinationpackagingunit" id="combinationpackagingunit_on" value="Yes"'.((Configuration::get('ATOOSYNC_PACKAGING_UNIT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationpackagingunit_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="combinationpackagingunit" id="combinationpackagingunit_off" value="No" '.((Configuration::get('ATOOSYNC_PACKAGING_UNIT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="combinationpackagingunit_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Créer la déclinaison correspondant à une unité du conditionnement de l\'article.').'</p>
				</div>
        <label>'.$this->l('Rechercher sur l\'EAN13').'</label>
				<div class="margin-form">
					<input type="radio" name="packagingean13" id="packagingean13_on" value="Yes"'.((Configuration::get('ATOOSYNC_PACKAGING_EAN13') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="packagingean13_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="packagingean13" id="packagingean13_off" value="No" '.((Configuration::get('ATOOSYNC_PACKAGING_EAN13') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="packagingean13_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Rechercher les déclinaisons également sur le Code barre EAN13 du conditionnement en plus du code interne à Atoo-Sync.').'<br />
					<font color="red"><i>'.$this->l('Ne doit être activée que si les conditionnements ont chacun un code barre EAN13 différents.').'</i></font></p>
				</div>
				<label>'.$this->l('Rechercher sur la référence').'</label>
				<div class="margin-form">
					<input type="radio" name="packagingreference" id="packagingreference_on" value="Yes"'.((Configuration::get('ATOOSYNC_PACKAGING_REFERENCE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="packagingreference_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="packagingreference" id="packagingreference_off" value="No" '.((Configuration::get('ATOOSYNC_PACKAGING_REFERENCE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="packagingreference_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Rechercher les déclinaisons également sur la Référence du conditionnement en plus du code interne à Atoo-Sync.').'<br />
					<font color="red"><i>'.$this->l('Ne doit être activée que si les conditionnements ont chacun une référence différentes.').'</i></font></p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetProductsPack()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Article à nomenclature').'</legend>
				<label>'.$this->l('Créer les packs').'</label>
				<div class="margin-form">
					<input type="radio" name="createproductpack" id="createproductpack_on" value="Yes"'.((Configuration::get('ATOOSYNC_CREATE_PRODUCT_PACK') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="createproductpack_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="createproductpack" id="createproductpack_off" value="No" '.((Configuration::get('ATOOSYNC_CREATE_PRODUCT_PACK') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="createproductpack_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Créer les packs de produits pour les articles à nomenclature.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetCategories()
    {
        return '
     <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Création des catégories').'</legend>
				<label>'.$this->l('Création rapide des catégories').'</label>
				<div class="margin-form">
					<input type="radio" name="categoryquickcreate" id="categoryquickcreate_on" value="Yes"'.((Configuration::get('ATOOSYNC_CATEGORY_QUICK_CREATE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="categoryquickcreate_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="categoryquickcreate" id="categoryquickcreate_off" value="No" '.((Configuration::get('ATOOSYNC_CATEGORY_QUICK_CREATE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="categoryquickcreate_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Désactiver la regénération de l\'arbre des catégories lors de la création d\'une catégorie.').'<br />
					'.$this->l('Réduit le temps de création de la catégorie lorsque PrestaShop contient plusieurs centaines/milliers catégories.').'</br>
					<font color="red"><i>'.$this->l('Notez que vous devrez lancer cette regénération de l\'arbre des catégories depuis l\'éditeur d\'articles d\'Atoo-Sync.').'</br>
					'.$this->l('Ne doit être activée par défaut !').'</i></font></p>
				</div>
        <label>'.$this->l('Ignorer position des articles').'</label>
				<div class="margin-form">
					<input type="radio" name="productignoreposition" id="productignoreposition_on" value="Yes"'.((Configuration::get('ATOOSYNC_PRODUCT_IGNORE_POS') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="productignoreposition_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
					<input type="radio" name="productignoreposition" id="productignoreposition_off" value="No" '.((Configuration::get('ATOOSYNC_PRODUCT_IGNORE_POS') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="productignoreposition_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
					<p class="clear">'.$this->l('Ne pas mettre à jour la position des articles lors de la modification des articles.').'<br /></p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetOrders()
    {
        return '
     <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Lecture des commandes').'</legend>
				<label for="selectdate">'.$this->l('Rechercher les commandes').'</label>
				<div class="margin-form">
					<select name="selectdate" style="width: 400px;">
						<option value="orders"'.((Configuration::get('ATOOSYNC_ORDER_DATE') == 'orders') ? ' selected="selected"' : '').'>'.$this->l('Sur la date de la commande').'</option>
						<option value="invoices" '.((Configuration::get('ATOOSYNC_ORDER_DATE') == 'invoices') ? ' selected="selected"' : '').'>'.$this->l('Sur la date de la facture').'</option>
					</select>
					<p class="clear">'.$this->l('Sélectionnez la date qui sera utilisée pour trouver les commandes.').'</p>
				</div>
				<label>'.$this->l('Importation des messages').'</label>
				<div class="margin-form">
					<select name="messages" id="messages" style="width : 400px">
						<option value="None" '.((Configuration::get('ATOOSYNC_ORDER_MESSAGES') == 'None') ? ' selected="selected"' : '').'>'.$this->l('Aucun message').'</option>
						<option value="First" '.((Configuration::get('ATOOSYNC_ORDER_MESSAGES') == 'First') ? ' selected="selected"' : '').'>'.$this->l('Premier message du client').'</option>
						<option value="All" '.((Configuration::get('ATOOSYNC_ORDER_MESSAGES') == 'All') ? ' selected="selected"' : '').'>'.$this->l('Tous les messages').'</option>
					</select>
					<p class="clear">'.$this->l('Configurer l\'importation des messages de la commande.').'</p>
				</div>
				<label>'.$this->l('Précision des prix').'</label>
				<div class="margin-form">
					<select name="round" id="messages" style="width : 400px">
						<option value="2" '.((Configuration::get('ATOOSYNC_ORDER_ROUND') == '2') ? ' selected="selected"' : '').'>'.$this->l('2 chiffres après la virgule').'</option>
						<option value="3" '.((Configuration::get('ATOOSYNC_ORDER_ROUND') == '3') ? ' selected="selected"' : '').'>'.$this->l('3 chiffres après la virgule').'</option>
						<option value="4" '.((Configuration::get('ATOOSYNC_ORDER_ROUND') == '4') ? ' selected="selected"' : '').'>'.$this->l('4 chiffres après la virgule').'</option>
					</select>
					<p class="clear">'.$this->l('Configurer l\'arrondi des prix de la commande pour la création dans Sage Gestion Commerciale.').'</p>
				</div>
				<label>'.$this->l('Formater le numéro de le comande').'</label>
				<div class="margin-form">
					<input type="radio" name="orderformatnumber" id="orderformatnumber_on" value="Yes"'.((Configuration::get('ATOOSYNC_ORDER_FORMAT_NUMBER') != 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="orderformatnumber_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="orderformatnumber" id="orderformatnumber_off" value="No" '.((Configuration::get('ATOOSYNC_ORDER_FORMAT_NUMBER') == 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="orderformatnumber_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Formater le numéro de commande sur 6 chiffres (ex:001278).').'</p>
				</div>
				<label for="guestaccount">'.$this->l('Compte client invité').'</label>
				<div class="margin-form">
					<input type="text"  style="width: 400px;" name="guestaccount" value="'.Configuration::get('ATOOSYNC_ORDER_GUESTACCOUNT') .'" />
					<p class="clear">'.$this->l('Entrez le numéro de compte pour les clients invités.').'<br />
					'.$this->l('Laissez le champ vide si vous ne voulez pas activer cette option.').'</p>
				</div>
        <label>'.$this->l('Utiliser les réglements').'</label>
				<div class="margin-form">
					<input type="radio" name="usepayements" id="usepayements_on" value="Yes"'.((Configuration::get('ATOOSYNC_ORDER_USE_PAYMENT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="usepayements_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="usepayements" id="usepayements_off" value="No" '.((Configuration::get('ATOOSYNC_ORDER_USE_PAYMENT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="usepayements_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Utiliser les réglements de la commande au lieu du total payé réellement').'</p>
				</div>
        <label for="selectdate">'.$this->l('Liste des modes de paiement').'</label>
				<div class="margin-form">
					<select name="payment" style="width: 400px;">
						<option value="module"'.((Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'module') ? ' selected="selected"' : '').'>'.$this->l('Modules de paiement installés dans PrestaShop').'</option>
						<option value="order_module" '.((Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'order_module') ? ' selected="selected"' : '').'>'.$this->l('Modules de paiement renseignés sur les commandes').'</option>
						<option value="order_payment" '.((Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'order_payment') ? ' selected="selected"' : '').'>'.$this->l('Modes de paiement renseignés sur les commandes').'</option>
						<option value="payment_method" '.((Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'payment_method') ? ' selected="selected"' : '').'>'.$this->l('Modes de paiement des réglements des commandes').'</option>
					</select>
					<p class="clear">'.$this->l('Sélectionnez les modes de paiement à utiliser dans Atoo-Sync.').'
				</div>       
       <label for="payments">'.$this->l('Autres mode de paiement').'</label>
				<div class="margin-form">
					<input type="text"  style="width: 400px;" name="payments" value="'.Configuration::get('ATOOSYNC_ORDER_OTHERPAYMENT').'" />
					<p class="clear">'.$this->l('Entrez ici les autres modes de paiement à afficher dans Atoo-Sync lors de la configuration.').'<br />
					'.$this->l('Utilisez une virgule (\',\') pour les séparer (par exemple, EBAY,CDISCOUNT...)').'<br />
					'.$this->l('Laissez le champ vide si vous ne voulez pas activer cette option.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>			
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetKerawen()
    {
        return '
     <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Commandes Caisse KerAwen').'</legend>
        <label>'.$this->l('Ignorer les commandes du jour').'</label>
        <div class="margin-form">
					<input type="radio" name="ignoreposordersofday" id="ignoreposordersofday_on" value="Yes"'.((Configuration::get('ATOOSYNC_IGNORE_POS_ORDERS_DAY') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ignoreposordersofday_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="ignoreposordersofday" id="ignoreposordersofday_off" value="No" '.((Configuration::get('ATOOSYNC_IGNORE_POS_ORDERS_DAY') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ignoreposordersofday_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Ne pas inclurer les commandes de la journée lors de la lecture des commandes.').'</p>
				</div>       
        <label for="posaccount">'.$this->l('Compte client Point de vente').'</label>
				<div class="margin-form">
					<input type="text"  style="width: 400px;" name="posaccount" value="'.Configuration::get('ATOOSYNC_ORDER_POSACCOUNT') .'" />
					<p class="clear">'.$this->l('Entrez le numéro de compte pour les clients Point de vente.').'<br />
					'.$this->l('Laissez le champ vide si vous ne voulez pas activer cette option.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
      </fieldset>
			<br />
			<br /> ';
    }
  
    private function _fieldSetDiscount()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Bon de réduction').'</legend>
				<label>'.$this->l('Utiliser la description comme Référence').'</label>
				<div class="margin-form">
					<input type="radio" name="discountusedescription" id="discountusedescription_on" value="Yes"'.((Configuration::get('ATOOSYNC_DISCOUNT_DESCRIPTION') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="discountusedescription_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="discountusedescription" id="discountusedescription_off" value="No" '.((Configuration::get('ATOOSYNC_DISCOUNT_DESCRIPTION') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="discountusedescription_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Utiliser la description de la règle panier comme code article dans Sage Gestion Commerciale pour créer le bon de réduction.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>			
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetUpdateStatuses()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Mise à jour des statuts').'</legend>
				<label>'.$this->l('Recopier le numéro Sage').'</label>
				<div class="margin-form">
					<input type="radio" name="ordercopyreference" id="ordercopyreference_on" value="Yes"'.((Configuration::get('ATOOSYNC_ORDER_COPY_REFERENCE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ordercopyreference_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="ordercopyreference" id="ordercopyreference_off" value="No" '.((Configuration::get('ATOOSYNC_ORDER_COPY_REFERENCE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ordercopyreference_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Copier le numéro du document de Sage Gestion Commerciale dans la référence de la commande dans PrestaShop lors de la mise à jour des statuts de commandes.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>			
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetUpdateOrdersCreation()
    {
        $id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));
        $invoice_select= '';
        $statuts = Db::getInstance()->ExecuteS('
					SELECT os.`id_order_state`, osl.`name`
					FROM `'._DB_PREFIX_.'order_state` os
					LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)($id_lang).')
					ORDER BY os.`id_order_state` ASC');
        
        foreach ($statuts as $s => $row) {
            $invoice_select.= '<option value="'.$row['id_order_state'].'" '.((Configuration::get('ATOOSYNC_INVOICE_STATE') == $row['id_order_state']) ? ' selected="selected"' : '').'>'.$row['name'].'</option>';
        }
    
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Création des commandes').'</legend>
				<label>'.$this->l('Statut de la commande').'</label>
				<div class="margin-form">
					<select name="created_order_state" id="created_order_state" style="width : 400px">
					'.$invoice_select.'
					</select>
					<p class="clear">'.$this->l('Sélectionner le statut dans PrestaShop des commandes qui seront créées depuis Sage Gestion Commerciale.').'<br /></p>
				</div>
				<label>'.$this->l('Créer le PDF de la facture').'</label>
				<div class="margin-form">
					<input type="radio" name="ordercreateinvoice" id="ordercreateinvoice_on" value="Yes"'.((Configuration::get('ATOOSYNC_ORDER_CREATE_INVOICE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ordercreateinvoice_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="ordercreateinvoice" id="ordercreateinvoice_off" value="No" '.((Configuration::get('ATOOSYNC_ORDER_CREATE_INVOICE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="ordercreateinvoice_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Créer la facture dans PrestaShop lors de la création des commandes depuis Sage Gestion Commerciale.').'<br />
					<font color="red"><i>'.$this->l('Notez que les montants des taxes dans le document PDF peuvent ne pas être identiques au document dans Sage Gestion Commerciale.').'</i></font></p>
				</div>
				<label>'.$this->l('Remplacer les commandes').'</label>
				<div class="margin-form">
					<input type="radio" name="overwriteorder" id="overwriteorder_on" value="Yes"'.((Configuration::get('ATOOSYNC_OVERWRITE_ORDER') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="overwriteorder_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="overwriteorder" id="overwriteorder_off" value="No" '.((Configuration::get('ATOOSYNC_OVERWRITE_ORDER') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="overwriteorder_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Supprimer et recréer dans PrestaShop les commandes déjà existantes avec le même numéro lors de la création depuis Sage Gestion Commerciale.').'<br />
					<font color="red"><i>'.$this->l('Ne doit pas être activée par défault.').'</i></font></p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetOrdersSlipsAndProductsReturns()
    {
        $id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));
            
        $orderreturn_select= '';
        $statuts = Db::getInstance()->ExecuteS('
					SELECT ors.`id_order_return_state`, orsl.`name`
					FROM `'._DB_PREFIX_.'order_return_state` ors
					LEFT JOIN `'._DB_PREFIX_.'order_return_state_lang` orsl ON (ors.`id_order_return_state` = orsl.`id_order_return_state` AND orsl.`id_lang` = '.(int)($id_lang).')
					ORDER BY ors.`id_order_return_state` ASC');
        
        foreach ($statuts as $s => $row) {
            $orderreturn_select.= '<option value="'.$row['id_order_return_state'].'" '.((Configuration::get('ATOOSYNC_ORDER_RETURN_STATE') == $row['id_order_return_state']) ? ' selected="selected"' : '').'>'.$row['name'].'</option>';
        }
    
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Avoirs et Retours produits').'</legend>
				<label>'.$this->l('Inclure les avoirs').'</label>
				<div class="margin-form">
					<input type="radio" name="includeorderslip" id="includeorderslip_on" value="Yes"'.((Configuration::get('ATOOSYNC_INCLUDE_ORDER_SLIP') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="includeorderslip_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="includeorderslip" id="includeorderslip_off" value="No" '.((Configuration::get('ATOOSYNC_INCLUDE_ORDER_SLIP') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="includeorderslip_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Inclure les avoirs dans le flux des commandes vers Sage Gestion Commerciale.').'<br />
					<font color="red"><i>'.$this->l('Seul les avoirs associés à une commande déjà créée dans Sage Gestion Commerciale seront lues.').'</i></font></p>
				</div>
				<label>'.$this->l('Inclure les retours produits').'</label>
				<div class="margin-form">
					<input type="radio" name="includeorderreturn" id="includeorderreturn_on" value="Yes"'.((Configuration::get('ATOOSYNC_INCLUDE_ORDER_RETURN') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="includeorderreturn_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="includeorderreturn" id="includeorderreturn_off" value="No" '.((Configuration::get('ATOOSYNC_INCLUDE_ORDER_RETURN') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="includeorderreturn_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Inclure les retours produits dans le flux des commandes vers Sage Gestion Commerciale.').'<br />
					<font color="red"><i>'.$this->l('Seul les retours produits associés à une commande déjà créée dans Sage Gestion Commerciale seront lues.').'</i></font></p>
				</div>
				<label>'.$this->l('Statut des retours').'</label>
				<div class="margin-form">
					<select name="order_return_state" id="order_return_state" style="width : 400px">
					'.$orderreturn_select.'
					</select>
					<p class="clear">'.$this->l('Sélectionner le statut des retours produits qui seront inclus dans le flux des commandes vers Sage Gestion Commerciale.').'<br /></p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>	
			</fieldset>
			<br />
			<br />';
    }
  
    private function _fieldSetCustomersGroups()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Groupes de clients').'</legend>
				<label>'.$this->l('Nom du groupe').'</label>
				<div class="margin-form">
					<input type="radio" name="updategroupname" id="updategroupname_on" value="Yes"'.((Configuration::get('ATOOSYNC_GROUP_UPDATENAME') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="updategroupname_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="updategroupname" id="updategroupname_off" value="No" '.((Configuration::get('ATOOSYNC_GROUP_UPDATENAME') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="updategroupname_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Mettre à jour le nom du groupe correspondant à la catégorie tarifiare lors de la mise à jour depuis Sage Gestion Commerciale.').'</p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>			
			</fieldset>
      <br />
			<br />';
    }
  
    private function _fieldSetCustomers()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Clients').'</legend>
				<label>'.$this->l('Prénom du contact').'</label>
				<div class="margin-form">
					<select name="firstname" id="firstname" style="width : 400px">
						<option value="First" '.((Configuration::get('ATOOSYNC_CUSTOMER_FIRSTNAME') == 'First') ? ' selected="selected"' : '').'>'.$this->l('Première partie du contact').'</option>
						<option value="Last" '.((Configuration::get('ATOOSYNC_CUSTOMER_FIRSTNAME') == 'Last') ? ' selected="selected"' : '').'>'.$this->l('Seconde partie du contact').'</option>
						<option value="All" '.((Configuration::get('ATOOSYNC_CUSTOMER_FIRSTNAME') == 'All') ? ' selected="selected"' : '').'>'.$this->l('Contact en entier').'</option>
						<option value="Title" '.((Configuration::get('ATOOSYNC_CUSTOMER_FIRSTNAME') == 'Title') ? ' selected="selected"' : '').'>'.$this->l('Intitulé du client').'</option>
					</select>
					<p class="clear">'.$this->l('Configurer comment Atoo-Sync trouvera le prénom du contact.').'<br />'.$this->l('La coupure entre le Prénom et le Nom se fera au premier espace.').'</p>
				</div>
				<label>'.$this->l('Nom du contact').'</label>
				<div class="margin-form">
					<select name="lastname" id="lastname" style="width : 400px">
						<option value="First" '.((Configuration::get('ATOOSYNC_CUSTOMER_LASTNAME') == 'First') ? ' selected="selected"' : '').'>'.$this->l('Première partie du contact').'</option>
						<option value="Last" '.((Configuration::get('ATOOSYNC_CUSTOMER_LASTNAME') == 'Last') ? ' selected="selected"' : '').'>'.$this->l('Seconde partie du contact').'</option>
						<option value="All" '.((Configuration::get('ATOOSYNC_CUSTOMER_LASTNAME') == 'All') ? ' selected="selected"' : '').'>'.$this->l('Contact en entier').'</option>
						<option value="Title" '.((Configuration::get('ATOOSYNC_CUSTOMER_LASTNAME') == 'Title') ? ' selected="selected"' : '').'>'.$this->l('Intitulé du client').'</option>
					</select>
					<p class="clear">'.$this->l('Configurer comment Atoo-Sync trouvera le nom du contact.').'<br />'.$this->l('La coupure entre le Prénom et le Nom se fera au premier espace.').'</p>
				</div>
				<label>'.$this->l('Mot de passe').'</label>
				<div class="margin-form">
					<select name="contactpwd" id="contactpwd" style="width : 400px">
						<option value="CodeClient" '.((Configuration::get('ATOOSYNC_CUSTOMER_PASSWORD') == 'CodeClient') ? ' selected="selected"' : '').'>'.$this->l('Numéro de compte du client').'</option>
						<option value="PostalCode" '.((Configuration::get('ATOOSYNC_CUSTOMER_PASSWORD') == 'PostalCode') ? ' selected="selected"' : '').'>'.$this->l('Code postal de l`onglet identification, sinon numéro de compte').'</option>
						<option value="Random" '.((Configuration::get('ATOOSYNC_CUSTOMER_PASSWORD') == 'Random') ? ' selected="selected"' : '').'>'.$this->l('Aléatoire').'</option>
					</select>
					<p class="clear">'.$this->l('Configurer comment Atoo-Sync créera le mot de passe du client.').'</p>
				</div>
				<label>'.$this->l('Groupe de clients par défaut').'</label>
				<div class="margin-form">
					<input type="radio" name="addcustomergroup" id="addcustomergroup_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_ADDGROUP') != 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="addcustomergroup_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="addcustomergroup" id="addcustomergroup_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_ADDGROUP') == 'No') ? ' checked="checked"' : '').' />
					<label class="t" for="addcustomergroup_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Ajouter le client au groupe de clients par défaut en plus du groupe de clients correspondant à la catégorie tarifaire de Sage.').'</p>
				</div>
        <label>'.$this->l('Newsletter').'</label>
				<div class="margin-form">
					<input type="radio" name="newsletter" id="newsletter_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_NEWSLETTER') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="newsletter_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="newsletter" id="newsletter_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_NEWSLETTER') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="newsletter_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Client abonné à la newsletter.').'</p>
				</div>
				<label>'.$this->l('Opt-In').'</label>
				<div class="margin-form">
					<input type="radio" name="optin" id="optin_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_OPTIN') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="optin_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="optin" id="optin_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_OPTIN') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="optin_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Client acceptant de recevoir des publicités.').'</p>
				</div>
				<label>'.$this->l('Notifier par email').'</label>
				<div class="margin-form">
					<input type="radio" name="sendmail" id="sendmail_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_SEND_MAIL') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="sendmail_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="sendmail" id="sendmail_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_SEND_MAIL') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="sendmail_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Notifier le client par email de son mot de passe lors de la création de du compte.').'<br/>
					<font color="red"><i>'.$this->l('L\'email est envoyé avec le thème par défaut.').'</i></font></p>
				</div>
				<label>'.$this->l('Créer règle panier remise %').'</label>
				<div class="margin-form">
					<input type="radio" name="createcustomerdiscount" id="createcustomerdiscount_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_DISCOUNT') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="createcustomerdiscount_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="createcustomerdiscount" id="createcustomerdiscount_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_DISCOUNT') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="createcustomerdiscount_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Créer une règle panier correspondant à la remise globale du client dans Sage Gestion Commerciale.').'</p>
				</div>
				<label>'.$this->l('Nom de la règle panier').'</label>
				<div class="margin-form">
					<input type="text"  style="width: 400px;" name="createcustomerdiscountname" value="'.Configuration::get('ATOOSYNC_CUSTOMER_DISCOUNTNAME') .'" />
					<p class="clear">'.$this->l('Entrer le nom de règle panier qui sera créé pour chaque client.').'<br />
					<i>'.$this->l('%C = code client dans Sage (ex: CARAT)').'<br />
					'.$this->l('%I = Intitulé du client dans Sage (ex: Carat SARL).').'<br />
					'.$this->l('%P = Pourcentage de réduction du client dans Sage (ex: 15).').'</i></p>
				</div>
				<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>			
			</fieldset>
      <br />
			<br />';
    }
  
    private function _fieldSetAddresses()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Adresses des clients').'</legend>
      	<label>'.$this->l('Recherche le pays par le code ISO').'</label>
				<div class="margin-form">
					<input type="radio" name="addresscodeiso" id="addresscodeiso_on" value="Yes"'.((Configuration::get('ATOOSYNC_ADRESSE_CODEISO') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="addresscodeiso_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="addresscodeiso" id="addresscodeiso_off" value="No" '.((Configuration::get('ATOOSYNC_ADRESSE_CODEISO') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="addresscodeiso_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Rerchercher le pays par le code ISO du pays de l\'adresse de Sage Gestion Commerciale.').'</p>
				</div>
        <label>'.$this->l('Supprimer les adresses non Sage').'</label>
				<div class="margin-form">
					<input type="radio" name="addressremove" id="addressremove_on" value="Yes"'.((Configuration::get('ATOOSYNC_ADRESSE_REMOVE') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="addressremove_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="addressremove" id="addressremove_off" value="No" '.((Configuration::get('ATOOSYNC_ADRESSE_REMOVE') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="addressremove_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Supprimer dans PrestaShop les adresses qui ne viennent pas de Sage Gestion Commerciale').'</p>
				</div>
        	<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>		
      </fieldset>
      <br />
			<br />';
    }
  
    private function _fieldSetCustomersContacts()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Contacts des clients').'</legend>
      	<label>'.$this->l('Créer les contacts').'</label>
				<div class="margin-form">
					<input type="radio" name="createcontacts" id="createcontacts_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_CONTACTS') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="createcontacts_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="createcontacts" id="createcontacts_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_CONTACTS') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="createcontacts_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Créer les contacts associés au client, les contacts seront créés sans adresse.').'</p>
				</div>
        <label>'.$this->l('Copier les adresses sur les contacts').'</label>
				<div class="margin-form">
					<input type="radio" name="contactsaddresses" id="contactsaddresses_on" value="Yes"'.((Configuration::get('ATOOSYNC_CONTACTS_ADDRESS') == 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="contactsaddresses_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Oui').'" /></label>
					<input type="radio" name="contactsaddresses" id="contactsaddresses_off" value="No" '.((Configuration::get('ATOOSYNC_CONTACTS_ADDRESS') != 'Yes') ? ' checked="checked"' : '').' />
					<label class="t" for="contactsaddresses_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Non').'" /></label>
					<p class="clear">'.$this->l('Copier les adresses sur chaque contact associés au client').'</p>
				</div>
        	<center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>		
      </fieldset>
      <br />
			<br />';
    }
    private function _fieldSetCustomersModifications()
    {
        return '
    <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Modification des clients').'</legend>
      <label>'.$this->l('Informations du client').'</label>
      <div class="margin-form">
        <input type="radio" name="changecustomer" id="changecustomer_on" value="Yes"'.((Configuration::get('ATOOSYNC_UPDATE_CUSTOMER') == 'Yes') ? ' checked="checked"' : '').' />
        <label class="t" for="changecustomer_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
        <input type="radio" name="changecustomer" id="changecustomer_off" value="No" '.((Configuration::get('ATOOSYNC_UPDATE_CUSTOMER') != 'Yes') ? ' checked="checked"' : '').' />
        <label class="t" for="changecustomer_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
        <p class="clear">'.$this->l('Modifier les informations du client en mise à jour lors de l\'export de puis Sage.').'</p>
      </div>
      <label>'.$this->l('Adresses des clients').'</label>
      <div class="margin-form">
        <input type="radio" name="changeaddress" id="changeaddress_on" value="Yes"'.((Configuration::get('ATOOSYNC_UPDATE_ADDRESS') == 'Yes') ? ' checked="checked"' : '').' />
        <label class="t" for="changeaddress_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
        <input type="radio" name="changeaddress" id="changeaddress_off" value="No" '.((Configuration::get('ATOOSYNC_UPDATE_ADDRESS') != 'Yes') ? ' checked="checked"' : '').' />
        <label class="t" for="changeaddress_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
        <p class="clear">'.$this->l('Modifier les adresses des clients en mise à jour lors de l\'export de puis Sage.').'</p>
      </div>
      <label>'.$this->l('Groupes du client').'</label>
      <div class="margin-form">
        <input type="radio" name="changecustomergroups" id="changecustomergroups_on" value="Yes"'.((Configuration::get('ATOOSYNC_CUSTOMER_GROUP') == 'Yes') ? ' checked="checked"' : '').' />
        <label class="t" for="changecustomergroups_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Activé').'" title="'.$this->l('Activé').'" /></label>
        <input type="radio" name="changecustomergroups" id="changecustomergroups_off" value="No" '.((Configuration::get('ATOOSYNC_CUSTOMER_GROUP') != 'Yes') ? ' checked="checked"' : '').' />
        <label class="t" for="changecustomergroups_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Désactivé').'" title="'.$this->l('Désactivé').'" /></label>
        <p class="clear">'.$this->l('Modifier les groupes du client en mise à jour lors de l\'export de puis Sage.').'</p>
      </div>
      <center><input type="submit" name="btnSubmit" value="'.$this->l('Sauvegarder').'" class="button" /></center>			
    </fieldset>
    <br />
    <br />';
    }
  
    private function _fieldSetResetOrders()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Réinitialiser les commandes transferées').'</legend>
			<label>'.$this->l('ID des commandes').'</label>
				<div class="margin-form">
					<input type="text" style="width: 300px;" name="ResetOrders" id="ResetOrders" value="" />
					<p class="clear">'.$this->l('Saisissez les Ids des commandes à réinitialiser.').'<br />'
          .$this->L('Vous pouvez les séparer par une virgule pour en saisir plusieurs (ex: 124,3,48) ou saisir ALL pour réinitialiser toutes les commandes.').'<br />
          <font color="red"><i>'.$this->l('Attention les commandes seront réimportées dans Atoo-Sync lors de la prochaine synchronisation.').'</i></font>'
          .'</p>
				</div>
			<center><input type="submit" name="btnDeleteImage" value="'.$this->l('Réinitialiser les Commandes').'" class="button" /></center>			
		</fieldset>';
    }
  
    private function _fieldSetResetOrderSlips()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Réinitialiser les avoirs transferées').'</legend>
			<label>'.$this->l('ID des avoirs').'</label>
				<div class="margin-form">
					<input type="text" style="width: 300px;" name="ResetOrderSlips" id="ResetOrderSlips" value="" />
					<p class="clear">'.$this->l('Saisissez les Ids des avoirs à réinitialiser.').'<br />'
          .$this->L('Vous pouvez les séparer par une virgule pour en saisir plusieurs (ex: 124,3,48) ou saisir ALL pour réinitialiser toutes les avoirs.').'<br />
          <font color="red"><i>'.$this->l('Attention les avoirs seront réimportées dans Atoo-Sync lors de la prochaine synchronisation.').'</i></font>'
          .'</p>
				</div>
			<center><input type="submit" name="btnDeleteImage" value="'.$this->l('Réinitialiser les Avoirs').'" class="button" /></center>			
		</fieldset>';
    }
    
    private function _fieldSetDeleteDatas()
    {
        return '
      <fieldset><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Supprimer').'</legend>
			<div style="margin:0 0 20px 0;">
				<input type="checkbox" name="DELETEPRODUCTS" id="DELETEPRODUCTS" style="vertical-align: middle;" value="1" />
				<label class="t" for="DELETEPRODUCTS">'.$this->l('Les articles créés par Atoo-Sync GesCom Sage 100 ODBC').'</label>
				<p style="color:#7F7F7F;">'.$this->l('Supprimer tous les articles créés par Atoo-Sync GesCom Sage 100 ODBC').'</p>
			</div>
			<div style="margin:0 0 20px 0;">
				<input type="checkbox" name="DELETEATTACHMENTS" id="DELETEATTACHMENTS" style="vertical-align: middle;" value="1" />
				<label class="t" for="DELETEATTACHMENTS">'.$this->l('Les documents créés par Atoo-Sync GesCom Sage 100 ODBC').'</label>
				<p style="color:#7F7F7F;">'.$this->l('Supprimer tous les documents créées par Atoo-Sync GesCom Sage 100 ODBC').'</p>
			</div>
			<div style="margin:0 0 20px 0;">
				<input type="checkbox" name="DELETEIMAGES" id="DELETEIMAGES" style="vertical-align: middle;" value="1" />
				<label class="t" for="DELETEIMAGES">'.$this->l('Les images créés par Atoo-Sync GesCom Sage 100 ODBC').'</label>
				<p style="color:#7F7F7F;">'.$this->l('Supprimer toutes les images créées par Atoo-Sync GesCom Sage 100 ODBC').'</p>
			</div>
			<div style="margin:0 0 20px 0;">
				<input type="checkbox" name="DELETEALLIMAGES" id="DELETEALLIMAGES" style="vertical-align: middle;" value="1" />
				<label class="t" for="DELETEALLIMAGES">'.$this->l('Toutes les images des articles créés par Atoo-Sync GesCom Sage 100 ODBC').'</label>
				<p style="color:#7F7F7F;">'.$this->l('Supprimer toutes les images des articles créées par Atoo-Sync GesCom Sage 100 ODBC').'</p>
			</div>
			<center><input type="submit" name="btnDeleteImage" value="'.$this->l('Supprimer les données').'" class="button" /></center>			
		</fieldset>';
    }
  
    public function getContent()
    {
        $this->_html = '<h2>'.$this->displayName.'</h2>';

        if (!empty($_POST)) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= '<div class="alert error">'. $err .'</div>';
                }
            }
        }

        $this->_displayAtooSync();
        $this->_displayForm();

        return $this->_html;
    }
    
    private function motDePasse($length = 10)
    {
        $chaine = "abcdefghijklmnpqrstuvwxyABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $chaine = str_shuffle($chaine);
        $password = substr($chaine, 0, $length);

        return $password;
    }
    
    private function fieldExist($table, $column)
    {
        $query = "SHOW COLUMNS FROM `".$table."` LIKE '".$column."'";
        Db::getInstance()->ExecuteS($query);
        if (Db::getInstance()->NumRows() != 0) {
            return true;
        } else {
            return false;
        }
    }
    
    private function indexExist($table, $indexname)
    {
        $query = "SHOW INDEX  FROM `".$table."` WHERE Key_name = '".$indexname."'";
        Db::getInstance()->ExecuteS($query);
        if (Db::getInstance()->NumRows() != 0) {
            return true;
        } else {
            return false;
        }
    }
    
    private function resetOrders()
    {
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> ';

        if ((string)$_POST['ResetOrders'] == 'ALL') {
            $this->_html .= 'Réinitiliser le transfert de toutes les commandes ';
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'orders` SET `atoosync_transfert_gescom` = 0');
        } else {
            $orders = explode(',', (string)$_POST['ResetOrders']);
            foreach ($orders as $order_id) {
                // Efface le statut transféré pour la commande
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'orders` SET `atoosync_transfert_gescom` = 0 WHERE `id_order` = '.(int)$order_id);
                $this->_html .= 'Réinitiliser le transfert de la commande numéro : '.$order_id.'<br />';
            }
        }
    
        $this->_html .= '</div>';
    }
    private function resetOrderSlips()
    {
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> ';

        if ((string)$_POST['ResetOrderSlips'] == 'ALL') {
            $this->_html .= 'Réinitiliser le transfert de toutes les avoirs ';
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'orders` SET `atoosync_transfert_gescom` = 0');
        } else {
            $orders = explode(',', (string)$_POST['ResetOrderSlips']);
            foreach ($orders as $order_id) {
                // Efface le statut transféré pour la commande
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'order_slip` SET `atoosync_transfert_gescom` = 0 WHERE `id_order_slip` = '.(int)$order_id);
                $this->_html .= 'Réinitiliser le transfert de l\'avoir numéro : '.$order_id.'<br />';
            }
        }
    
        $this->_html .= '</div>';
    }
    private function deleteProducts()
    {
        $sql= "SELECT `id_product` FROM `"._DB_PREFIX_."product` WHERE `atoosync`='1'";
        $products = Db::getInstance()->executeS($sql);
        foreach ($products as $product) {
            $p = new Product($product['id_product']);
            $p->delete();
        }
    }
    private function deleteAttachements()
    {
        $sql= "SELECT `id_attachment` FROM `"._DB_PREFIX_."attachment` WHERE (`atoosync_file`<>'' OR `atoosync_file`= null)";
        $attachments = Db::getInstance()->executeS($sql);
        foreach ($attachments as $attachment) {
            $att = new Attachment($attachment['id_attachment']);
            $att->delete();
        }
    }
    private function deleteImagesProducts()
    {
        $sql= "SELECT `id_image` FROM `"._DB_PREFIX_."image` WHERE (`atoosync_image_id` <>'0' OR `atoosync_image_id` = null)";
        $images = Db::getInstance()->executeS($sql);
        foreach ($images as $image) {
            $img = new Image($image['id_image']);
            $img->delete();
            deleteImage($img->id_product, $img->id);
            if (!Image::getCover($img->id_product)) {
                $first_img = Db::getInstance()->getRow('
				SELECT `id_image` FROM `'._DB_PREFIX_.'image`
				WHERE `id_product` = '.intval($img->id_product));
                Db::getInstance()->Execute('
				UPDATE `'._DB_PREFIX_.'image`
				SET `cover` = 1
				WHERE `id_image` = '.intval($first_img['id_image']));
            }
            //@unlink(dirname(__FILE__).'/../../img/tmp/product_'.$image->id_product.'.jpg');
            //@unlink(dirname(__FILE__).'/../../img/tmp/product_mini_'.$image->id_product.'.jpg');
        }
    }
    private function deleteAllImagesProducts()
    {
        $sql= "SELECT `id_product` FROM `"._DB_PREFIX_."product` WHERE `atoosync`='1'";
        $products = Db::getInstance()->executeS($sql);
        foreach ($products as $product) {
            $p = new Product($product['id_product']);
            $p->deleteImages();
        }
    }
}
