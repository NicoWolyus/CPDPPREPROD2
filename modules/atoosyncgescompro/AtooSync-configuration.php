<?php
/*
================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync /!\
================================================================================
  Ce fichier fait partie du logiciel Atoo-Sync .
  Vous n'êtes pas autorisé à le modifier, à le recopier, à le vendre ou le redistribuer.
  Cet en-tête ne doit pas être retiré.

      Script : AtooSync-configuration.php
      Auteur : Atoo Next SARL (support@atoo-next.net)
   Copyright : 2009-2020 Atoo Next SARL

================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
*/
/*
 * Retourne la version de PrestaShop
 */
function GetVersion()
{
    echo _PS_VERSION_.'<br>';
    return 1;
}
/* Retourne vrai si PrestaShop 1.7 */
function isPrestaShop17()
{
    return version_compare(_PS_VERSION_, '1.7', '>=');
}
/* Retourne vrai si PrestaShop 1.6.1 */
function isPrestaShop161()
{
    return version_compare(_PS_VERSION_, '1.6.1', '>=');
}
/* Retourne vrai si PrestaShop 1.6 */
function isPrestaShop16()
{
    return (float)(_PS_VERSION_) == 1.6;
}
/* Retourne vrai si PrestaShop 1.5 */
function isPrestaShop15()
{
    return (float)(_PS_VERSION_) == 1.5;
}
/* Retourne vrai si PrestaShop 1.4 */
function isPrestaShop14()
{
    return (float)(_PS_VERSION_) == 1.4;
}
/* Retourne vrai si PrestaShop 1.3 */
function isPrestaShop13()
{
    return (float)(_PS_VERSION_) == 1.3;
}
/* Retourne vrai si PrestaShop 1.2 */
function isPrestaShop12()
{
    return (float)(_PS_VERSION_) == 1.2;
}
/*
 * Retourne le id_lang de la langue par défaut
 * depuis la table configuration
 */
function IdLangDefault()
{
    return (int)(Configuration::get('PS_LANG_DEFAULT'));
}
/*
 *	Liste les différentes langues de la boutique PrestaShop
 */
function GetLanguages()
{
    $deflang = (int)(Configuration::get('PS_LANG_DEFAULT'));
    
    $languages = Language::getLanguages(false);
    foreach ($languages as $k => $row) {
        $deflang == (int)($row['id_lang']) ? $defaut = 1 : $defaut = 0;
        $langue ='';
        $langue .= $row['id_lang']."|";
        $langue .= $row['iso_code']."|";
        $langue .= $row['name']."|";
        $langue .= $defaut;
        
        $langue = stripslashes($langue);
        $langue= html_entity_decode($langue);
        echo $langue.'<br>';
    }
    return 1;
}

/*
 * Retourne la liste des modes de paiements
 */
function GetPayements()
{
    if (Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'payment_method' or Configuration::get('ATOOSYNC_ORDER_USE_PAYMENT') == 'Yes')  {
        $payments = Db::getInstance()->ExecuteS('SELECT DISTINCT (BINARY `payment_method`), `payment_method` FROM `'._DB_PREFIX_.'order_payment`');
        foreach ($payments as $p => $row) {
            echo $row['payment_method'].'<br>';
        }
    } elseif (Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'order_payment') {
        $payments = Db::getInstance()->ExecuteS('SELECT DISTINCT (BINARY `payment`),`payment` FROM `'._DB_PREFIX_.'orders`');
        foreach ($payments as $p => $row) {
            echo $row['payment'].'<br>';
        }
    } elseif (Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'order_module') {
        $modules = Db::getInstance()->ExecuteS('SELECT DISTINCT (BINARY `module`), `module` FROM `'._DB_PREFIX_.'orders`');
        foreach ($modules as $m => $row) {
            echo $row['module'].'<br>';
        }
    } else {
        if (method_exists('PaymentModule', 'getInstalledPaymentModules')) {
            $modules = PaymentModule::getInstalledPaymentModules();
            foreach ($modules as $module) {
                echo $module['name'].'<br>';
            }
        } else {
            $modules = @Module::getModulesOnDisk(true);
            foreach ($modules as $module) {
                if ($module->tab == 'Payment' or $module->tab =='payments_gateways') {
                    $paiements = $module->name;
          
                    $paiements = stripslashes($paiements);
                    $paiements= html_entity_decode($paiements);
                    echo $paiements.'<br>';
                }
            }
        }
    }
    // Ajoute les autres modes de paiement
    if (Configuration::get('ATOOSYNC_ORDER_OTHERPAYMENT') != '') {
        foreach (explode(',', Configuration::get('ATOOSYNC_ORDER_OTHERPAYMENT')) as $payment) {
            echo trim($payment).'<br>';
        }
    }
    return 1;
}
/*
 * Retourne la liste des status des commandes
 */
function GetStatuses()
{
    //
    $statuts = Db::getInstance()->ExecuteS('
		SELECT os.`id_order_state`, osl.`name`
		FROM `'._DB_PREFIX_.'order_state` os
		LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)(IdLangDefault()).')
		ORDER BY os.`id_order_state` ASC');
        
    foreach ($statuts as $s => $row) {
        $state ='';
        $state .= $row['id_order_state']."|";
        $state .= $row['name'];
        
        $state = stripslashes($state);
        $state= html_entity_decode($state);
        echo $state.'<br>';
    }
    return 1;
}
/*
 * Retourne la liste des taux de taxes pour les articles
 */
function ProductsTaxsList()
{
    // Si les règles de taxes existent
    if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $tax_rules_groups = TaxRulesGroup::getTaxRulesGroups(true);
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $taxesRatesByGroup = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Configuration::get('PS_COUNTRY_DEFAULT'));
        } else {
            $taxesRatesByGroup = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Country::getDefaultCountryId());
        }
        echo '0|Sans TVA|0<br>';
        foreach ($tax_rules_groups as $tax_rules_group) {
            $tax_rate = (array_key_exists($tax_rules_group['id_tax_rules_group'], $taxesRatesByGroup) ?  $taxesRatesByGroup[$tax_rules_group['id_tax_rules_group']] : 0);
                                    
            $TVA ='';
            $TVA .= $tax_rules_group['id_tax_rules_group']."|";
            $TVA .= $tax_rules_group['name']."|";
            $TVA .= $tax_rate;
            
            $TVA = stripslashes($TVA);
            $TVA= html_entity_decode($TVA);
            echo $TVA.'<br>';
        }
    } else {
        $taxes = Tax::getTaxes(IdLangDefault());
        echo '0|Sans TVA (0.000)|0<br>';
        foreach ($taxes as $k => $row) {
            $TVA ='';
            $TVA .= $row['id_tax']."|";
            $TVA .= $row['name'].' ('.$row['rate'].')'."|";
            $TVA .= $row['rate'];

            $TVA = stripslashes($TVA);
            $TVA= html_entity_decode($TVA);
            echo $TVA.'<br>';
        }
    }
    return 1;
}
/*
 * Retourne la liste des taux de taxes
 */
function GetTaxes()
{
    //
    $taxs = Tax::getTaxes(IdLangDefault());
    foreach ($taxs as $k => $row) {
        $tax ='';
        $tax .= $row['id_tax']."|";
        $tax .= $row['name']."|";
        $tax .= $row['rate'];
        
        $tax = stripslashes($tax);
        $tax= html_entity_decode($tax);
        echo $tax.'<br>';
    }
    return 1;
}
/*
 * Retourne la liste des Zones
 */
function GetZones()
{
    //
    $zones = Zone::getZones(true);
    foreach ($zones as $k => $row) {
        $zone ='';
        $zone .= $row['id_zone']."|";
        $zone .= $row['name'];
        
        $zone = stripslashes($zone);
        $zone= html_entity_decode($zone);
        echo $zone.'<br>';
    }
    return 1;
}
/*
 * Retourne la liste des transporteurs
 */
function GetCarriers()
{
    //
    // Attention Dans PrestaShop la gestion des id des transporteurs est particuliere
    // un id est regénèré à chaque modification du transporteur.
    //
    $query = "SELECT `id_carrier`, `name` FROM `"._DB_PREFIX_."carrier`";
    $resultat = Db::getInstance()->ExecuteS($query);
    foreach ($resultat as $k => $row) {
        $name =$row['name'];
        if ($name == '0') {
            $name = Configuration::get('PS_SHOP_NAME');
        }
        $carrier ='';
        $carrier .= $row['id_carrier']."|";
        $carrier .= $name;
        
        $carrier = stripslashes($carrier);
        $carrier= html_entity_decode($carrier);
        echo $carrier.'<br>';
    }
    return 1;
}

/*
 * Retourne la liste des fabricants
 */
function GetManufacturers()
{
    $manufacturers = Manufacturer::getManufacturers();
    if ($manufacturers) {
        foreach ($manufacturers as $manufacturer) {
            $m ='';
            $m .= $manufacturer['id_manufacturer']."|";
            $m .= $manufacturer['name'];
            
            $m = stripslashes($m);
            $m= html_entity_decode($m);
            echo $m.'<br>';
        }
    }
    return 1;
}
/*
 * Retourne la liste des fournisseurs
 */
function GetSuppliers()
{
    $suppliers = Supplier::getSuppliers();
    if ($suppliers) {
        foreach ($suppliers as $supplier) {
            $s ='';
            $s .= $supplier['id_supplier']."|";
            $s .= $supplier['name'];
            
            $s = stripslashes($s);
            $s= html_entity_decode($s);
            echo $s.'<br>';
        }
    }
    return 1;
}
/*
 * Retourne les unités
 */
function GetUnits()
{
    $id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
    $currency = new Currency($id_currency);
    
    echo Configuration::get('PS_WEIGHT_UNIT').'<br>';
    echo Configuration::get('PS_VOLUME_UNIT').'<br>';
    echo Configuration::get('PS_DISTANCE_UNIT').'<br>';
    echo Configuration::get('PS_DIMENSION_UNIT').'<br>';
    echo Configuration::get('PS_BASE_DISTANCE_UNIT').'<br>';
    echo $currency->sign.'<br>';
        
    return 1;
}

/*
 * Liste les devises
 */
function GetCurrencies()
{
    $PS_CURRENCY_DEFAULT = (int)(Configuration::get('PS_CURRENCY_DEFAULT'));
    $currencies = Currency::getCurrencies(false, true);
    foreach ($currencies as $currency) {
        if (isPrestaShop17()) {
            $cur  = $currency['id_currency'].'|';
            $cur .= $currency['name'].'|';
            $cur .= $currency['iso_code'].'|';
            $cur .= $currency['iso_code_num'].'|';
            $cur .= $currency['sign'].'|';
            $cur .= '1'.'|';	 // blanck
      $cur .= '1'.'|';	 // format
      $cur .= '1'.'|';	 // decimals
      $cur .= $currency['conversion_rate'].'|';
            if ($PS_CURRENCY_DEFAULT == (int)($currency['id_currency'])) {
                $cur .= '1';
            }				/* default */
            else {
                $cur .= '0';
            }				/* default */
            echo $cur.'<br>';
        } else {
            $cur  = $currency['id_currency'].'|';
            $cur .= $currency['name'].'|';
            $cur .= $currency['iso_code'].'|';
            $cur .= $currency['iso_code_num'].'|';
            $cur .= $currency['sign'].'|';
            $cur .= $currency['blank'].'|';
            $cur .= $currency['format'].'|';
            $cur .= $currency['decimals'].'|';
            $cur .= $currency['conversion_rate'].'|';
            if ($PS_CURRENCY_DEFAULT == (int)($currency['id_currency'])) {
                $cur .= '1';
            }				/* default */
            else {
                $cur .= '0';
            }				/* default */
            echo $cur.'<br>';
        }
    }

    return 1;
}
/*
 * Liste les pays
 */
function GetCountries()
{
    $countries = Country::getCountries((int)(IdLangDefault()), true);
    foreach ($countries as $country) {
        $ct  = $country['id_country'].'|';
        $ct .= $country['name'];
        echo $ct.'<br>';
    }

    return 1;
}
/*
 * Liste les groupes de boutiques
 */
function GetGroupShops()
{
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $shopgroups = ShopGroup::getShopGroups(true);
        foreach ($shopgroups as $sg) {
            $tmp  = $sg->id.'|';				/* id_group_shop */
            $tmp .= $sg->name.'|';				/* name */
            $tmp .= $sg->share_customer.'|';	/* share_customer */
            $tmp .= $sg->share_order.'|';		/* share_order */
            $tmp .= $sg->share_stock.'|';		/* share_stock */
            $tmp .= $sg->active;				/* active */
            echo $tmp.'<br>';
        }
    } else {
        $tmp  = '1'.'|';		/* id_group_shop */
        $tmp .= 'Default'.'|';	/* name */
        $tmp .= '0'.'|';		/* share_customer */
        $tmp .= '0'.'|';		/* share_order */
        $tmp .= '0'.'|';		/* share_stock */
        $tmp .= '1';			/* active */
        echo $tmp.'<br>';
    }

    return 1;
}
/*
 * Liste les boutiques
 */
function GetShops()
{
    if (isPrestaShop17()) {
        $shops = Shop::getShops(true);
        foreach ($shops as $shop) {
            $tmp  = $shop['id_shop'].'|';			  /* id_shop */
            $tmp .= $shop['id_shop_group'].'|';	/* id_shop_group */
            $tmp .= $shop['name'].'|';				  /* name */
            $tmp .= $shop['id_category'].'|';		/* id_category */
            $tmp .= '1'.'|';			              /* id_theme */
            $tmp .= $shop['active'];				    /* active */
            echo $tmp.'<br>';
        }
    } elseif (isPrestaShop15() or isPrestaShop16()) {
        $shops = Shop::getShops(true);
        foreach ($shops as $shop) {
            $tmp  = $shop['id_shop'].'|';			  /* id_shop */
            $tmp .= $shop['id_shop_group'].'|';	/* id_shop_group */
            $tmp .= $shop['name'].'|';				  /* name */
            $tmp .= $shop['id_category'].'|';		/* id_category */
            $tmp .= $shop['id_theme'].'|';			/* id_theme */
            $tmp .= $shop['active'];				    /* active */
            echo $tmp.'<br>';
        }
    } else {
        $tmp  = '1'.'|';			/* id_shop */
        $tmp .= '1'.'|';			/* id_group_shop */
        $tmp .= 'Default'.'|';	/* name */
        $tmp .= '1'.'|';			/* id_category */
        $tmp .= '1'.'|';			/* id_theme */
        $tmp .= '1';				/* active */
        echo $tmp.'<br>';
    }
    return 1;
}
/*
 * Lit la règle de tax associée à l'écotaxe
 */
function GetEcoTaxTaxRules()
{
    echo Tax::getProductEcotaxRate().'<br>';
    return 1;
}
/*
 * Lit les dépôts
 */
function GetWharehouses()
{
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        if (Configuration::get('ATOOSYNC_WAREHOUSES') == 'shops') {
            $query = "SELECT `id_shop`, `name` FROM `"._DB_PREFIX_."shop` ORDER BY `id_shop`";
            $resultat = Db::getInstance()->ExecuteS($query);
            foreach ($resultat as $row) {
                $tmp  = $row['id_shop'].'|'; /* id_shop */
                $tmp .= $row['name'];	      /* name */
                echo $tmp.'<br>';
            }
        } else {
            $wharehouses = Warehouse::getWarehouses(true);
            foreach ($wharehouses as $wharehouse) {
                $tmp  = $wharehouse['id_warehouse'].'|';
                $tmp .= $wharehouse['name'];
                echo $tmp.'<br>';
            }     
        }
    }
    return 1;
}
