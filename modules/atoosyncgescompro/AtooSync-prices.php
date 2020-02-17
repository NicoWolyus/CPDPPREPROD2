<?php
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

================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
*/
/*
    Retourne true si le module Prix spécifique est installé.
*/
function oleapriceseditorplusIsInstalled()
{
    // vérifie si le module Price per group (by Oleacorner) est activé
    $mod = Module::getInstanceByName('oleapriceseditorplus');
    if (!$mod) {
        return false;
    }
        
    if ((int)($mod->active) == 0) {
        return false;
    }
    return true;
}
/*
    Retourne true si le module Remise est installé.
*/
function DiscountModuleIsInstalled()
{
    // vérifie si le module Rebate (by Oleacorner) est installé et activé
    if (olearebateIsInstalled()) {
        return true;
    }

    // vérifie si le module  Profileo Règles de prix catalogue pour client est installé et activé
    if (eo_specificprice_customerIsInstalled()) {
        return true;
    }
    
    return false;
}
/*
    Retourne vrai si le module olearebate est installé
*/
function olearebateIsInstalled()
{
    // vérifie si le module Rebate (by Oleacorner) est activé
    $mod = Module::getInstanceByName('olearebate');
    if ($mod) {
        if ((int)($mod->active) == 1) {
            return true;
        }
    }
    return false;
}
/*
    Retourne vrai si le module Profileo Règles de prix catalogue pour client est installé
*/
function eo_specificprice_customerIsInstalled()
{
    // vérifie si le module Rebate (by Oleacorner) est activé
    $mod = Module::getInstanceByName('eo_specificprice_customer');
    if ($mod) {
        if ((int)($mod->active) == 1) {
            return true;
        }
    }
    return false;
}

/*
 *	Met à jour le prix du produit par client
 */
function SetCustomersPrices($ProductXML)
{
    // Si le module OleaCorner est installé
    if (oleapriceseditorplusIsInstalled()) {
        $id_product = Db::getInstance()->getValue('
			SELECT `id_product`
			FROM `'._DB_PREFIX_.'product`
			WHERE `reference` = \''.pSQL((string)($ProductXML->reference)).'\'');
        if ($id_product) {
            $query = 'DELETE FROM `'._DB_PREFIX_.'specific_price_customer_olea` WHERE `id_product` = '.(int)($id_product);
            Db::getInstance()->Execute($query);
        
            foreach ($ProductXML->customersprices->customer_price as $price) {
                /* Recherche les clients ou clients avec cette centrale d'achat */
                $query = 'SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`
						  WHERE `atoosync_code_client` = \''.pSQL($price->customer).'\' OR `atoosync_centrale_achat` = \''.pSQL($price->customer).'\'' ;
                $customers = Db::getInstance()->ExecuteS($query);
                if ($customers) {
                    foreach ($customers as $k => $row) {
                        $id_customer = (int)($row['id_customer']);
                        AddSpecificPriceCustomerOlea((int)($id_customer), (int)($id_product), (float)($price->price), (float)($price->from_quantity), (float)($price->discount) / 100, (string)($price->discount_type));
                    }
                }
            }
        }
    }
}
/*
    Ajoute une prix spécifique pour un client et un article
*/
function AddSpecificPriceCustomerOlea($id_customer, $id_product, $price, $from_quantity, $reduction, $reduction_type)
{
    Db::getInstance()->Execute(
							
        'INSERT INTO `'._DB_PREFIX_.'specific_price_customer_olea` 
									(`id_product`, `id_shop`, `id_currency`, `id_country`, `id_customer`, `price`, `from_quantity`, `reduction`, `reduction_type`)
									VALUES (
									'.(int)($id_product).',
									0, 
									0, 
									0,
									'.(int)($id_customer).', 
									'.(float)($price).', 
									'.(int)($from_quantity).', 
									'.(float)($reduction).', 
									\''.(string)($reduction_type).'\')'
                                );
}

function SetCustomerDiscount($xml)
{
    /* Test si il y a un module de remise d'installé */
    if (DiscountModuleIsInstalled() == false) {
        return false;
    }

    $id_customer = Db::getInstance()->getValue('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` WHERE `atoosync_code_client` = \''.(string)($xml->account_number).'\'');

    if ($id_customer) {
        /* Si le module est olearebate */
        if (olearebateIsInstalled()) {
            /* Supprime les remises du client */
            $query = 'DELETE FROM `'._DB_PREFIX_.'rebate_customerorgroup` WHERE `id_group`=0 AND `id_customer` = '.(int)($id_customer);
            Db::getInstance()->Execute($query);
        
            Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rebate_customerorgroup` 
				(`id_group`, `id_customer`, `rebate`)
				VALUE 
				(0,'.(int)($id_customer).','.(float)($xml->discount).')');
        }
        
        /* Si le module est Profileo Règles de prix catalogue pour client */
        if (eo_specificprice_customerIsInstalled()) {
            // supprime en premier les remises par familles du client existante pour les recréer avec les données de Sage.
            $sql= "SELECT DISTINCT `id_specific_price_rule` FROM `"._DB_PREFIX_."eo_specificprice_customer` WHERE  `id_customer` =".(int)$id_customer;
            $discounts = Db::getInstance()->executeS($sql);
            foreach ($discounts as $discount) {
                $id_specific_price_rule = (int)($discount['id_specific_price_rule']);
                $SpecificPriceRule = new SpecificPriceRule($id_specific_price_rule);
                $SpecificPriceRule->delete();
                
                // supprime l'association dans la table 'eo_specificprice_customer'
                $sql= "DELETE FROM `"._DB_PREFIX_."eo_specificprice_customer` WHERE  `id_specific_price_rule` =".(int)$id_specific_price_rule;
                Db::getInstance()->Execute($sql);
            }
            
            // Pour chacune des remises par famille du client
            if ($xml->families_discounts) {
                foreach ($xml->families_discounts->family_discount as $fd) {
                    $family_key = (string)($fd->family_key);
                    $family_name = (string)($fd->family_name);
                    $percent_discount = (float)($fd->percent_discount);
                    
                    // trouve la marque associé a la famille la recherche se fait dans le champ Meta Title de la marque
                    $id_manufacturer = Db::getInstance()->getValue('SELECT distinct(`id_manufacturer`) FROM `'._DB_PREFIX_.'manufacturer_lang` WHERE `meta_keywords` = \''.(string)($fd->family_key).'\'');
                    if ($id_manufacturer) {
                        $specific_price_rule = new SpecificPriceRule();
                        $specific_price_rule->name = (string)($xml->account_number).' - '.$family_name. ' - '.$percent_discount.'%';
                        $specific_price_rule->id_shop = 1;
                        $specific_price_rule->id_currency = 0;
                        $specific_price_rule->id_country = 0;
                        $specific_price_rule->id_group = 0;
                        $specific_price_rule->from_quantity = 1;
                        $specific_price_rule->price = -1;
                        $specific_price_rule->reduction_type = 'percentage';
                        $specific_price_rule->reduction = $percent_discount;
                        $specific_price_rule->from = '0000-00-00 00:00:00';
                        $specific_price_rule->to = '0000-00-00 00:00:00';
                        if ($specific_price_rule->add()) {
                            $conditions = array();
                            // associe la règle de prix avec la marque
                            $conditions[] = array('type' => 'manufacturer', 'value' => $id_manufacturer);
                            $specific_price_rule->addConditions($conditions);
                        
                            // associe la règle de prix avec le client
                            Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'eo_specificprice_customer` 
							(`id_specific_price_rule`, `id_customer`)
							VALUE 
							('.(int)($specific_price_rule->id).','.(int)($id_customer).')');
                        
                            $specific_price_rule->apply();
                        } else {
                            echo 'Error specific_price_rule->add()';
                        }
                    }
                }
            }
        }
    }
}

/*
    Vide les tables du module PPG
*/
function PPGDeleteAll()
{
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'ppg_prices`');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'ppg_reduction`');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rebate_category`');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rebate_customerorgroup`');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rebate_product`');
}
/*
    Ajoute un prix de vente d'un article a un client
*/
function PPGAddCustomerProductPrice($id_customer, $id_product, $price)
{
    return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'ppg_prices` 
									(`id_group`, `id_customer`,  `id_product`,  `price`) 
									VALUE 
									(0,'.(int)($id_customer).','.(int)($id_product).','.(float)($price).')');
}
/*
    Ajoute un prix de vente d'un article a un groupe
*/
function PPGAddGroupProductPrice($id_group, $id_product, $price)
{
    return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'ppg_prices` 
									(`id_group`, `id_customer`,  `id_product`,  `price`) 
									VALUE 
									('.(int)($id_group).',0 ,'.(int)($id_product).','.(float)($price).')');
}
/*
    Ajoute une remise d'un article a un client
*/
function PPGAddCustomerProductRebate($id_customer, $id_product, $rebate)
{
    return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rebate_product` 
									(`id_group`, `id_customer`,  `id_product`,  `rebate`) 
									VALUE 
									(0,'.(int)($id_customer).','.(int)($id_product).','.(float)($rebate).')');
}
/*
    Ajoute une remise d'un article a un groupe
*/
function PPGAddGroupProductRebate($id_group, $id_product, $rebate)
{
    return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rebate_product` 
									(`id_group`, `id_customer`,  `id_product`,  `rebate`) 
									VALUE 
									('.(int)($id_group).',0 ,'.(int)($id_product).','.(float)($rebate).')');
}
/*
    Ajoute une remise d'un client sur une catgéorie
*/
function PPGAddCustomerCategoryRebate($id_customer, $id_category, $rebate)
{
    return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rebate_category` 
									(`id_group`, `id_customer`,  `id_category`,  `rebate`) 
									VALUE 
									(0,'.(int)($id_customer).','.(int)($id_category).','.(float)($rebate).')');
}
/*
    Ajoute un tarif pour un client
*/
function AjouteTarifClient($xml)
{
    if (empty($xml)) {
        return 0;
    }
    $resultat = 1;
    
    $xml = Tools::stripslashes($xml);
    $TarifXML = LoadXML($xml);
    if (empty($TarifXML)) {
        return 0;
    }
    
    $id_customer = Db::getInstance()->getValue('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` WHERE `atoosync_code_client` = \''.strval($TarifXML->Client).'\'');
    $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($TarifXML->Reference).'\'');

    if ((int)($id_customer) != 0 and (int)($id_product) !=0) {
        // Si il y a un prix de vente
        if ((float)($TarifXML->PrixVenteHT) !=0) {
            PPGAddCustomerProductPrice($id_customer, $id_product, (float)($TarifXML->PrixVenteHT));
        }
        
        // Si il y a une remise
        if ((float)($TarifXML->Remise) !=0) {
            PPGAddCustomerProductRebate($id_customer, $id_product, (float)($TarifXML->Remise));
        }
    }
        
    return $resultat;
}
/*
    Ajoute un prix pour une catégorie de client
*/
function AjouteTarifCategorie($xml)
{
    if (empty($xml)) {
        return 0;
    }
    $resultat = 1;
    
    $xml = Tools::stripslashes($xml);
    $TarifXML = LoadXML($xml);
    if (empty($TarifXML)) {
        return 0;
    }
    $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($TarifXML->Reference).'\'');
    
    if ((int)($id_product) !=0) {
        // Pour chacune des catégories
        foreach ($TarifXML->Categorie as $Categorie) {
            $id_group= Db::getInstance()->getValue('SELECT `id_group` FROM `'._DB_PREFIX_.'group` WHERE `atoosync_id` = \''.(int)($Categorie->Indice).'\'');
            
            if ((int)($id_group) != 0) {
                // Si il y a un prix de vente
                if ((float)($Categorie->PrixVenteHT) !=0) {
                    PPGAddGroupProductPrice($id_group, $id_product, (float)($Categorie->PrixVenteHT));
                }
                
                // Si il y a une remise
                if ((float)($Categorie->Remise) !=0) {
                    PPGAddGroupProductRebate($id_group, $id_product, (float)($Categorie->Remise));
                }
            }
        }
    }
    return $resultat;
}
/*
    Ajout les familles d'articles pour les remises par famille
*/
function InitCategoriesDiscount($xml)
{
    $resultat = 1;
    if (empty($xml)) {
        return 0;
    }
    
    $xml = Tools::stripslashes($xml);
    $CategoriesXML = LoadXML($xml);
    if (empty($CategoriesXML)) {
        return 0;
    }
    
    // la langue par défaut
    $IdLang = IdLangDefault();
    
    // Si la catégorie contenant les catégories des promotions n'existe pas elle est créée
    $id_parent = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `atoosync_id` = \'CAT-PROMO\'');
    if (!$id_parent) {
        $category = new Category(null);
        $category->id_parent = 1;
        $category->name = CreateMultiLangField('ZZZ Remises par catégorie');
        $category->link_rewrite = CreateMultiLangField(Tools::link_rewrite('Remises par catégorie'));
        $category->description = CreateMultiLangField('Ne pas effacer, utilisé pour les remises par famille');
        $category->active = 0;

        if ($category->add()) {
            $id_parent = $category->id;
            
            $groups = Group::getGroups((int)($IdLang));
            foreach ($groups as $group) {
                $groupes[] = $group['id_group'];
            }
            $category->addGroups($groupes);
            
            // enregistre le code dans la catégorie
            $sql = 'UPDATE `'._DB_PREFIX_.'category`
						SET `atoosync_id` = \'CAT-PROMO\',
							`atoosync` = \'4\' 
						WHERE `id_category`= \''.(int)($id_parent).'\'';
            Db::getInstance()->Execute($sql);
        } else {
            $resultat = 0;
        }
    }
    // Met le champ atoosync à 2 pour la suppression
    $sql = 'UPDATE `'._DB_PREFIX_.'category` SET `atoosync` = \'2\' WHERE `atoosync` = \'5\'';
    Db::getInstance()->Execute($sql);
    
    /*
     *	Pour chaque Famille dans le XML des familles
     */
    foreach ($CategoriesXML->category as $cat) {
        $code = 'P-'.$cat->code;
        // Essaye de trouver le id de la catégorie selon le id de Atoo-Sync
        $id_category= Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `atoosync_id` = \''.strval($code).'\'');
        
        // le id de la catégorie à été trouvé
        if ($id_category) {
            // Vide les articles de la catégorie
            Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_category` = '.$id_category);
            $productCats = array();
            
            // Ajoute les articles lié à la catégorie
            $products = Db::getInstance()->ExecuteS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `atoosync_codefamille`=\''.strval($cat->code).'\'');
            foreach ($products as $p) {
                $productCats[] = '('.$id_category.','.(int)($p['id_product']).',0)';
            }
            Db::getInstance()->Execute('
				INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`)
				VALUES '.implode(',', $productCats));
        }
        // la catégorie n'existe pas
        else {
            $category = new Category(null);
            $category->id_parent = (int)($id_parent);
            $category->name = CreateMultiLangField('Famille '.$cat->code);
            $category->link_rewrite = CreateMultiLangField(Tools::link_rewrite('Famille '.$cat->code));
            $category->description = CreateMultiLangField('Ne pas effacer, utilisé pour les remises par famille');
            $category->active = 0;
            if ($category->add()) {
                $id_category = $category->id;
                
                // Nouveauté 1.2 les groupes
                if ((float)(_PS_VERSION_) >= 1.2) {
                    $groups = Group::getGroups((int)($IdLang));
                    foreach ($groups as $group) {
                        $groupes[] = $group['id_group'];
                    }
                    $category->addGroups($groupes);
                }
                
                // enregistre le code dans la catégorie
                $sql = 'UPDATE `'._DB_PREFIX_.'category`
						SET `atoosync_id` = \''.strval($code).'\',
							`atoosync` = \'5\' 
						WHERE `id_category`= \''.(int)($category->id).'\'';
                Db::getInstance()->Execute($sql);
            
                // Ajoute les articles lié à la catégorie
                $productCats = array();
                $products = Db::getInstance()->ExecuteS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `atoosync_codefamille`=\''.strval($cat->code).'\'');
                foreach ($products as $p) {
                    $productCats[] = '('.$id_category.','.(int)($p['id_product']).',0)';
                }
                Db::getInstance()->Execute('
					INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`)
					VALUES '.implode(',', $productCats));
            } else {
                $resultat = 0;
            }
        }
    }

    // Désactive les categories qui ont le atoosync à 2
    $sql = 'UPDATE `'._DB_PREFIX_.'category`
			SET `atoosync` = \'1\', `active` = \'0\'
			WHERE `atoosync`= \'2\'';
    Db::getInstance()->Execute($sql);
    
    return $resultat;
}
/*
    Ajoute la remise du client
*/
function AddCustomerDiscount($xml)
{
    if (empty($xml)) {
        return 0;
    }
    $resultat = 1;
    
    $xml = Tools::stripslashes($xml);
    $XML = LoadXML($xml);
    if (empty($XML)) {
        return 0;
    }
    
    /* Met à jour la remise du client */
    SetCustomerDiscount($XML);
    
    return $resultat;
}
/*
    Ajoute la remise de la famille
*/
function AddCategoryDiscount($xml)
{
    if (empty($xml)) {
        return 0;
    }
    $resultat = 1;
    
    $xml = Tools::stripslashes($xml);
    $CategoryXML = LoadXML($xml);
    if (empty($CategoryXML)) {
        return 0;
    }
        
    $id_category= Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `atoosync_id` = \''.pSQL($CategoryXML->atoosync_id).'\'');
    
    if ($id_category) {
        /*
         * Les remises par groupes (catégorie tarifaire)
         */
        foreach ($CategoryXML->groupsdiscount->groupdiscount as $gd) {
            
            /* Trouve le groupe */
            $id_group = Db::getInstance()->getValue('
				SELECT `id_group`
				FROM `'._DB_PREFIX_.'group`
				WHERE `atoosync_id` = \''.(int)($gd->group_atoosync_id).'\'');
            if ($id_group) {
                /* Si le module OLEAREBATE est installé */
                if (olearebateIsInstalled()) {
                    /* Supprime la remise de groupe de la catégorie */
                    $query = 'DELETE FROM `'._DB_PREFIX_.'rebate_category` WHERE `id_group`='.(int)($id_group).' AND `id_category` = '.(int)($id_category);
                    Db::getInstance()->Execute($query);
                
                    /* Créé la remise du groupe */
                    Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rebate_category` 
						(`id_group`, `id_customer`,`id_category`, `rebate`)
						VALUE 
						('.(int)($id_group).',0,'.(int)($id_category).','.(float)($gd->discount).')');
                }
            }
        }
        
        /*
         * Les remises par clients
         */
        foreach ($CategoryXML->customersdiscount->customerdiscount as $cd) {
            /* Si le module OLEAREBATE est installé */
            if (olearebateIsInstalled()) {
                /* Recherche les clients ou clients avec cette centrale d'achat */
                $query = 'SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`
						  WHERE `atoosync_code_client` = \''.pSQL($cd->account_number).'\' OR `atoosync_centrale_achat` = \''.pSQL($cd->account_number).'\'' ;
                $customers = Db::getInstance()->ExecuteS($query);
                if ($customers) {
                    foreach ($customers as $k => $row) {
                        $id_customer = (int)($row['id_customer']);
                                            
                        /* Supprime la remise du client de la catégorie */
                        $query = 'DELETE FROM `'._DB_PREFIX_.'rebate_category` WHERE `id_customer`='.(int)($id_customer).' AND `id_category` = '.(int)($id_category);
                        Db::getInstance()->Execute($query);
                    
                        /* Créé la remise du client */
                        Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rebate_category` 
							(`id_group`, `id_customer`,`id_category`, `rebate`)
							VALUE 
							(0,'.(int)($id_customer).','.(int)($id_category).','.(float)($cd->discount).')');
                        
                        //AddSpecificPriceCustomerOlea((int)($id_customer), (int)($id_product), (float)($price->price), (float)($price->from_quantity), (float)($price->discount) / 100, (string)($price->discount_type));
                    }
                }
            }
        }
    }
    $id_customer = Db::getInstance()->getValue('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` WHERE `atoosync_code_client` = \''.strval($RemiseXML->Client).'\'');
        
    return $resultat;
}
