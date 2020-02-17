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
 * Retourne la liste des références de tous les articles ayant le
 * champ référence de renseigné dans la boutique.
 */
function GetProducts()
{
    $sql= "SELECT `reference` FROM `"._DB_PREFIX_."product` WHERE `active`=1 AND `reference` <>''";
    $products = Db::getInstance()->executeS($sql, true, 0);
    foreach ($products as $product) {
        $reference = $product['reference'];
        echo $reference.'<br>';
    }
}

/*
 *	Test si l'article existe dans la base
 */
function ProductExist($reference)
{
    $exist=0;
    $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL((string)($reference)).'\'');
    if ($id_product) {
        $exist = 1;
    }

    return $exist;
}

/*
 * Active ou désactive l'article.
 */
function SetProductActive($reference, $active)
{
    if (!empty($reference) and is_string($reference) and is_numeric($active)) {
        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
        
        if ($id_product) {
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `active` = '.(int)($active).' WHERE `id_product` = '.(int)($id_product));
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `active` = '.(int)($active).' WHERE `id_product` = '.(int)($id_product));
                return 1;
            } else {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `active` = '.(int)($active).' WHERE `id_product` = '.(int)($id_product));
                return 1;
            }
        }
    }
}

/*
 *	Ajoute ou met à jour un article
 */
function AddProduct($xml)
{
    $succes = 1;
    if (empty($xml)) {
        return 0;
    }

    $ProductXML = LoadXML(Tools::stripslashes($xml));
    if (empty($ProductXML)) {
        return 0;
    }
    // Customisation du XML de l'article envoyé par Atoo-Sync avant la création/modification.
    $ProductXML = CustomizeProductXML($ProductXML);
    
    // Customisation de la création des articles
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeAddProduct($ProductXML) == true) {
        return true;
    }
    
    // supprime les retours à la ligne des ean ou upc.
    $ProductXML->ean13 = str_replace (array("\r\n", "\n", "\r"), '', $ProductXML->ean13);
    $ProductXML->upc = str_replace (array("\r\n", "\n", "\r"), '', $ProductXML->upc);
    
    // si ecotaxe désactivé alors vide le montant de l'exotaxe
    if ((int)Configuration::getGlobalValue('PS_USE_ECOTAX') == 0) {
        $ProductXML->ecotax = 0;
    }
        
    /* Valide les codes barres */
    if (method_exists('Validate', 'isEan13')) {
        if (!Validate::isEan13((string)($ProductXML->ean13))) {
            $ProductXML->ean13 = '';
        }
    }
    if (method_exists('Validate', 'isUpc')) {
        if (!Validate::isUpc((string)($ProductXML->upc))) {
            $ProductXML->upc = '';
        }
    }

    $isNewProduct = false;
    // trouve le id_product à partir de la référence
    $id_product = Db::getInstance()->getValue('
        SELECT `id_product`
		FROM `'._DB_PREFIX_.'product`
		WHERE `reference` = \''.pSQL((string)($ProductXML->reference)).'\'');
    
    /* Si le produit n'existe pas il est créé */
    if (!$id_product) {
        /* Fixe le context par défaut pour l'article */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
                
        // Si suppression des caractères interdits
        if (Configuration::get('ATOOSYNC_CLEAN_NAME') == 'Yes') {
            $ProductXML->AR_Design = cleanName($ProductXML->AR_Design);
        }
        
        $product= new Product();
        $product->reference = (string)($ProductXML->reference);
        $product->name =  CreateMultiLangField((string)$ProductXML->AR_Design);
        $product->link_rewrite = CreateMultiLangField(Tools::link_rewrite((string)$ProductXML->AR_Design));
        $product->description =  CreateMultiLangField('');
        $product->description_short =  CreateMultiLangField('');
        
        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $product->id_tax_rules_group = (int)($ProductXML->id_tax);
        } else {
            $product->id_tax = (int)($ProductXML->id_tax);
        }
        
        // Trouve la catégorie de l'article selon sa famille
        $id_category = Db::getInstance()->getValue('
			SELECT `id_category`
			FROM `'._DB_PREFIX_.'category`
			WHERE `atoosync_id` = \''.pSQL($ProductXML->FA_CodeFamille).'\'');
        if ($id_category) {
            $product->id_category_default = $id_category;
        } else {
            /* Met l'article dans la catégorie Acceuil */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $product->id_category_default = (int)(Configuration::get('PS_HOME_CATEGORY'));
            } else {
                $product->id_category_default = 1;
            }
        }
                
        // Créé l'article
        if ($product->add() == true) {
            $isNewProduct == true;
            $id_product = $product->id;
            // met à jour la catégorie de l'article si PrestaShop <> 1.6.1 et 1.7
            if (!isPrestaShop161() and  !isPrestaShop17()) {
                $product->updateCategories(array('0' => $product->id_category_default));
            }
        } else {
            echo 'Error $product->add()';
            $product = null;
            $id_product = null;
            $succes = 0;
        }
    }
    
    /* Renseigne les données de l'article */
    if ($id_product) {
        verifyProductFields($id_product);
        
        /* Fixe le context par défaut pour l'article */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        
        /* Créé l'article en premier dans toutes les boutiques, pour pouvoir les modifier */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'product_shop` 
                  (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`, `on_sale`, `online_only`, `ecotax`, `minimal_quantity`, `price`,
                  `wholesale_price`, `unity`, `unit_price_ratio`, `additional_shipping_cost`, `customizable`, `uploadable_files`, `text_fields`,  
                  `redirect_type`, `available_for_order`, `available_date`, `condition`, `show_price`, `indexed`, `visibility`, 
                  `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`) 
                  SELECT `id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`, `on_sale`, `online_only`, `ecotax`, `minimal_quantity`, `price`, 
                  `wholesale_price`, `unity`, `unit_price_ratio`, `additional_shipping_cost`, `customizable`, `uploadable_files`, `text_fields`, 
                  `redirect_type`, `available_for_order`, `available_date`, `condition`, `show_price`, `indexed`, `visibility`, 
                  `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd` 
                   FROM `'._DB_PREFIX_.'product`, `'._DB_PREFIX_.'shop` WHERE `id_product` = '.(int)$id_product;
            @Db::getInstance()->execute($sql);
        }
    
        $product= new Product($id_product);
        if ((int)($ProductXML->active) == 1) {
            $product->active = true;
        } else {
            $product->active = false;
        }
        
        /* Modifie les prix si activé ou nouveau article*/
        if (Configuration::get('ATOOSYNC_CHANGE_PRICE') == 'Yes' or $isNewProduct == true) {
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $product->id_tax_rules_group = (int)($ProductXML->id_tax);
            } else {
                $product->id_tax = (int)($ProductXML->id_tax);
            }
            $product->wholesale_price = (float)(max(0, (float)($ProductXML->wholesale_price)));
            $product->price = (float)(max(0, (float)($ProductXML->price)));
          
            $product->ecotax = (float)(max(0, (float)($ProductXML->ecotax)));
            $product->unit_price = (float)(max(0, (float)($ProductXML->unit_price)));
            $product->unit_price_ratio = (float)(max(0, (float)($ProductXML->unit_price_ratio)));
            $product->unity = (string)($ProductXML->unity);
        }
    
        /* Modifie les codes barres si activé ou nouveau article*/
        if (Configuration::get('ATOOSYNC_CHANGE_EANUPC') == 'Yes' or $isNewProduct == true) {
            $product->ean13 = (string)($ProductXML->ean13);
            $product->upc =(string)($ProductXML->upc);
        }
      
        /* Modifie le fabricant si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_MANUFACTURER') == 'Yes' or $isNewProduct == true) {
            $product->id_manufacturer = (int)($ProductXML->id_manufacturer);
        }
    
        /* Modifie le fournisseur si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_SUPPLIER') == 'Yes' or $isNewProduct == true) {
            $product->id_supplier = (int)($ProductXML->id_supplier);
            $product->supplier_reference = (string)($ProductXML->supplier_reference);
      
            /* Le fournisseur de l'article PrestaShop 1.5+ */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                if ((int)$ProductXML->id_supplier != 0) {
                    $product->addSupplierReference((int)$ProductXML->id_supplier, 0, (string)$ProductXML->supplier_reference, (float)(max(0, (float)($ProductXML->wholesale_price))), null);
                } else {
                    $product->deleteFromSupplier();
                }
            }
        }
        
        /* Modifie le poids de l'article si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes' or $isNewProduct == true) {
            $product->weight = (float)($ProductXML->weight);
        }
        /* Modifie le stock si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_QUANTITY') == 'Yes' or $isNewProduct == true) {
            $product->quantity = (int)(max(0, (int)($ProductXML->quantity)));
     
            /* Information de stock pour PrestaShop 1.5 */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                    if (!$ProductXML->Combinations) {
                        StockAvailable::setQuantity($product->id, 0, (int)($ProductXML->quantity), (int)($id_shop));
                    }
              
                    if (Configuration::get('ATOOSYNC_CHANGE_OTHER') == 'Yes' or $isNewProduct == true) {
                        StockAvailable::setProductOutOfStock($product->id, $ProductXML->out_of_stock, (int)($id_shop));
                    }
                }
            }
        }
        
        /* Modifie les autres champs de l'article si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_OTHER') == 'Yes' or $isNewProduct == true) {
            /* Informations */
            $product->on_sale = (int)($ProductXML->on_sale);
            $product->condition = (string)($ProductXML->condition);
            $product->visibility = (string)($ProductXML->visibility);
            $product->available_date = (string)($ProductXML->available_date);
            $product->available_for_order = (int)($ProductXML->available_for_order);
            $product->online_only = (int)($ProductXML->online_only);
            $product->out_of_stock = (int)($ProductXML->out_of_stock);
            $product->show_price = (int)($ProductXML->show_price);
              
            $product->location = (string)($ProductXML->location);
            $product->width = (float)($ProductXML->width);
            $product->height = (float)($ProductXML->height);
            $product->depth = (float)($ProductXML->depth);
            $product->minimal_quantity = (int)(max(0, (int)($ProductXML->minimal_quantity)));
            $product->additional_shipping_cost = (float)(max(0, (float)($ProductXML->additional_shipping_cost)));
        }
        
        /* boutique par défaut */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $id_shop = Db::getInstance()->getValue('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop`='.(int)($ProductXML->id_shop_default));
            if ($id_shop) {
                $product->id_shop_default = (int)($ProductXML->id_shop_default);
            }
        }
            
        /* Modifie le nom si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_NAME') == 'Yes' or $isNewProduct == true) {
            foreach ($ProductXML->names->name as $text) {
                $tmp = (string)($text);
                if (!empty($tmp)) {
                    // Si suppression des caractères interdits
                    if (Configuration::get('ATOOSYNC_CLEAN_NAME') == 'Yes') {
                        $tmp = cleanName($tmp);
                    }
      
                    $product->name[(int)($text['id_lang'])] = $tmp;
                }
            }
        }

        /* Modifie le résumé si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_SHORT') == 'Yes' or $isNewProduct == true) {
            foreach ($ProductXML->descriptionshorts->description_short as $text) {
                $product->description_short[(int)($text['id_lang'])] = (string)($text);
            }
        }
        
        /* Modifie la description si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_DESCR') == 'Yes' or $isNewProduct == true) {
            foreach ($ProductXML->descriptions->description as $text) {
                $product->description[(int)($text['id_lang'])] = (string)($text);
            }
        }
    
        /* Modifie les SEO si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_SEO') == 'Yes' or $isNewProduct == true) {
            foreach ($ProductXML->linkrewrites->link_rewrite as $text) {
                $tmp = (string)($text);
                if (!empty($tmp)) {
                    $product->link_rewrite[(int)($text['id_lang'])] = $tmp;
                }
            }
            /* les balises Titre*/
            foreach ($ProductXML->metatitles->meta_title as $text) {
                $product->meta_title[(int)($text['id_lang'])] = (string)($text);
            }
            /* les Mots-clefs*/
            foreach ($ProductXML->metakeywords->meta_keywords as $text) {
                $product->meta_keywords[(int)($text['id_lang'])] = (string)($text);
            }
            /* les Meta Descriptions */
            foreach ($ProductXML->metadescriptions->meta_description as $text) {
                $product->meta_description[(int)($text['id_lang'])] = (string)($text);
            }
      
            /* Les Tags */
            $product->deleteTags();	/* Supprime les tags en premier */
            foreach ($ProductXML->tags->tag as $text) {
                $tmp = trim((string)($text));
                if (!empty($tmp)) {
                    if (substr($tmp, -1, 1) == ',') {
                        $tmp=substr($tmp, 0, -1);
                    }
            
                    Tag::addTags((int)($text['id_lang']), (int)($product->id), (string)($tmp));
                }
            }
        }
    
        /* Modifie les messages de disponibilités si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_AVAILABLE_MSG') == 'Yes' or $isNewProduct == true) {
            /* les Messages en Stock */
            foreach ($ProductXML->availablenows->available_now as $text) {
                $product->available_now[(int)($text['id_lang'])] = (string)($text);
            }
            /* les Messages Hors Stock */
            foreach ($ProductXML->availablelaters->available_later as $text) {
                $product->available_later[(int)($text['id_lang'])] = (string)($text);
            }
        }
        
        /* Modifie les catégories si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_CATEGORIES') == 'Yes' or $isNewProduct == true) {
            /* Les catégories */
            $id_category = Db::getInstance()->getValue('SELECT `id_category` 
                              FROM `'._DB_PREFIX_.'category` 
                              WHERE `id_category` = '.(int)($ProductXML->category_default));
            if ($id_category) {
                $product->id_category_default = (int)($ProductXML->category_default);
            }

            $categories = array();
            foreach ($ProductXML->categories->category as $id) {
                $id_category = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` = '.(int)($id));
                if ($id_category) {
                    array_push($categories, $id_category);
                }
            }
            if (!empty($categories)) {
                $product->updateCategories($categories, true);
            }
        }
        
        /* Modifie les accessoires si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_ACCESSORIES') == 'Yes' or $isNewProduct == true) {
            /* Les accessoires */
            $product->deleteAccessories(); /* Supprime les accessoires en premier */
            $accessories = array();
            foreach ($ProductXML->accessories->accessory as $reference) {
                $id_product_2 = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($reference).'\'');
                if ($id_product_2) {
                    array_push($accessories, $id_product_2);
                }
            }
            if (!empty($accessories)) {
                $product->changeAccessories($accessories);
            }
        }
        /* Modifie les caractéristiques si activé ou nouveau article */
        if (Configuration::get('ATOOSYNC_CHANGE_FEATURES') == 'Yes' or $isNewProduct == true) {
            $product->deleteFeatures();
            foreach ($ProductXML->featurevalues->feature_value as $feature) {
                /* Si valeur prédéfinie */
                if ((int)($feature->custom) == 0) {
                    $id_feature = Db::getInstance()->getValue('SELECT `id_feature` FROM `'._DB_PREFIX_.'feature_value` WHERE `id_feature_value` = '.(int)($feature->id_feature_value));
                    if ($id_feature) {
                        $product->addFeaturesToDB($id_feature, (int)($feature->id_feature_value));
                    }
                } else /* Si valeur custom*/
        {
          $featureValue = new FeatureValue();
          $featureValue->id_feature = (int)($feature->id_feature);
          $featureValue->custom = 1;
          $featureValue->value = CreateMultiLangField('');
          foreach ($feature->value as $value) {
              $tmp = trim((string)($value));
              $featureValue->value[(int)($value['id_lang'])] = $tmp;
          }
          if ($featureValue->add()) {
              $product->addFeaturesToDB((int)($feature->id_feature), $featureValue->id);
          }
        }
            }
        }
        
        // Force le nom des articles si le nom est vide
        $languages = Language::getLanguages(false);
        foreach ($languages as $l) {
            $id_lang = (int)$l['id_lang'];
            
            if (empty($product->name[$id_lang])) {
                $product->name[$id_lang] = (string)$ProductXML->AR_Design;
            }
        }
        // Force les urls simplifiés si le l'url est vide
        $languages = Language::getLanguages(false);
        foreach ($languages as $l) {
            $id_lang = (int)$l['id_lang'];
            
            if (empty($product->link_rewrite[$id_lang])) {
                $product->link_rewrite[$id_lang] = Tools::link_rewrite((string)$ProductXML->AR_Design);
            }
        }  
            
        // Valide les champs.
        if (!$product->validateFieldsLang()) {
            echo 'validateFieldsLang() update error id_product ='.$id_product;
            $succes = 0;
        }

        /* Met à jour le produit */
        if (!$product->update()) {
            echo 'An error occurred while updating the product.';
            $succes = 0;
        }
        
        /* Créé les déclinaisons de l'article */
        if (Configuration::get('ATOOSYNC_CHANGE_ATTRIBUTES') == 'Yes' or $isNewProduct == true) {
            /* Les gammes de SAGE */
            if ($ProductXML->Combinations) {
                CreateCombinations($product, $ProductXML);
            } else {
                /* Supprime les products attributs qui n'existe plus. */
                $sql = "SELECT `id_product_attribute` FROM `"._DB_PREFIX_."product_attribute` WHERE `id_product` = ".(int)($product->id)." AND `atoosync_gamme` <> ''";
                $pas = Db::getInstance()->ExecuteS($sql, true, 0);
                if ($pas) {
                    foreach ($pas as $pa) {
                        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                            Shop::setContext(Shop::CONTEXT_ALL);
                            $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
                        } else {
                            $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
                        }
                    }
                }
            }
            /* Les conditionnements de SAGE */
            if ($ProductXML->Packagings) {
                CreatePackagings($product, $ProductXML);
            } else {
                /* Supprime les products attributs qui n'existe plus. */
                $sql = "SELECT `id_product_attribute` FROM `"._DB_PREFIX_."product_attribute` WHERE `id_product` = ".(int)($product->id)." AND `atoosync_conditionnement` <> ''";
                $pas = Db::getInstance()->ExecuteS($sql, true, 0);
                foreach ($pas as $pa) {
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        Shop::setContext(Shop::CONTEXT_ALL);
                        $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
                    } else {
                        $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
                    }
                }
            }
        }
    
        /* Si PrestaShop 1.5 */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            /* Créé les données de l'article dans les boutiques */
            foreach ($ProductXML->product_shops->product_shop as $productShop) {
                $id_shop = (int)($productShop['id_shop']);
                $id_shop = Db::getInstance()->getValue('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop`='.(int)($id_shop));
                if ($id_shop) {
                    Shop::setContext(Shop::CONTEXT_SHOP, (int)($id_shop));
                    
                    /* Instancie l'article*/
                    $product_shop = new Product((int)($product->id));
                    if ($product_shop) {
                        if ($productShop->product_shop_visible == 0) {
                            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'product_shop` WHERE `id_product` = '.(int)($product->id).' AND `id_shop`='.(int)($id_shop));
                            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '.(int)($product->id).' AND `id_shop`='.(int)($id_shop));
                        } else {
                            $product_shop->id_shop_default = (int)($ProductXML->id_shop_default);
                            $product_shop->active = (int)($productShop->product_shop_active);
                                
                            /* Modifie les prix si activé ou nouveau article*/
                            if (Configuration::get('ATOOSYNC_CHANGE_PRICE') == 'Yes' or $isNewProduct == true) {
                                $product_shop->id_tax_rules_group = (int)($productShop->product_shop_id_tax);
                                $product_shop->wholesale_price = (float)(max(0, (float)($productShop->product_shop_wholesale_price)));
                                $product_shop->price = (float)(max(0, (float)($productShop->product_shop_price)));
                                $product_shop->ecotax = (float)(max(0, (float)($productShop->product_shop_ecotax)));
                            
                                $product_shop->unit_price = (float)(max(0, (float)($productShop->product_shop_unit_price)));
                                $product_shop->unit_price_ratio = (float)(max(0, (float)($productShop->product_shop_unit_price_ratio)));
                                $product_shop->unity = (string)($productShop->product_shop_unity);
                            }
                            /* Modifie le stock si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_QUANTITY') == 'Yes' or $isNewProduct == true) {
                                $product_shop->quantity = (int)(max(0, (int)($productShop->product_shop_quantity)));
                            }
              
                            /* Modifie les catégories si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_CATEGORIES') == 'Yes' or $isNewProduct == true) {
                                $product_shop->id_category_default = (int)($product->id_category_default);
                            }
              
                            /* Modifie les autres champs de l'article si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_OTHER') == 'Yes' or $isNewProduct == true) {
                                $product_shop->minimal_quantity = (int)(max(0, (int)($productShop->product_shop_minimal_quantity)));
                                $product_shop->additional_shipping_cost = (float)(max(0, (float)($productShop->product_shop_additional_shipping_cost)));
                
                                /* Informations */
                                $product_shop->on_sale = (int)($productShop->product_shop_on_sale);
                                $product_shop->condition = (string)($productShop->product_shop_condition);
                                $product_shop->visibility = (string)($productShop->product_shop_visibility);
                                $product_shop->available_date = (string)($productShop->product_shop_available_date);
                                $product_shop->available_for_order = (int)($productShop->product_shop_available_for_order);
                                $product_shop->online_only = (int)($productShop->product_shop_online_only);
                                $product_shop->out_of_stock = (int)($productShop->product_shop_out_of_stock);
                                $product_shop->show_price = (int)($productShop->product_shop_show_price);
                            }
             
                            
                            /* Modifie le nom si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_NAME') == 'Yes' or $isNewProduct == true) {
                                foreach ($productShop->shop_lang->product_shop_lang_name as $name) {
                                    $tmp = (string)($name);
                                    if (!empty($tmp)) {
                                        // Si suppression des caractères interdits
                                        if (Configuration::get('ATOOSYNC_CLEAN_NAME') == 'Yes') {
                                            $tmp = cleanName($tmp);
                                        }
                      
                                        $product_shop->name[(int)($name['id_lang'])] = $tmp;
                                    }
                                }
                            }
                
                            /* Modifie le résumé si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_SHORT') == 'Yes' or $isNewProduct == true) {
                                foreach ($productShop->shop_lang->product_shop_lang_description_short as $description_short) {
                                    $product_shop->description_short[(int)($description_short['id_lang'])] = (string)($description_short);
                                }
                            }
                            
                            /* Modifie la description si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_DESCR') == 'Yes' or $isNewProduct == true) {
                                foreach ($productShop->shop_lang->product_shop_lang_description as $description) {
                                    $product_shop->description[(int)($description['id_lang'])] = (string)($description);
                                }
                            }
                
                            /* Modifie les SEO si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_SEO') == 'Yes' or $isNewProduct == true) {
                                foreach ($productShop->shop_lang->product_shop_lang_link_rewrite as $link_rewrite) {
                                    $tmp = (string)($link_rewrite);
                                    if (!empty($tmp)) {
                                        $product_shop->link_rewrite[(int)($link_rewrite['id_lang'])] = $tmp;
                                    }
                                }
                                /* les balises Titre*/
                                foreach ($productShop->shop_lang->product_shop_lang_meta_title as $meta_title) {
                                    $product_shop->meta_title[(int)($meta_title['id_lang'])] = (string)($meta_title);
                                }
                                /* les Mots-clefs*/
                                foreach ($productShop->shop_lang->product_shop_lang_meta_keywords as $meta_keywords) {
                                    $product_shop->meta_keywords[(int)($meta_keywords['id_lang'])] = (string)($meta_keywords);
                                }
                                /* les Meta Descriptions */
                                foreach ($productShop->shop_lang->product_shop_lang_meta_description as $meta_description) {
                                    $product_shop->meta_description[(int)($meta_description['id_lang'])] = (string)($meta_description);
                                }
                
                                /* Les Tags */
                                foreach ($productShop->shop_lang->product_shop_lang_tag as $tags) {
                                    $tmp = trim((string)($tags));
                                    if (!empty($tmp)) {
                                        if (substr($tmp, -1, 1) == ',') {
                                            $tmp=substr($tmp, 0, -1);
                                        }
            
                                        Tag::addTags((int)($tags['id_lang']), (int)($product_shop->id), $tmp);
                                    }
                                }
                            }
              
                            /* Modifie les messages de disponibilités si activé ou nouveau article */
                            if (Configuration::get('ATOOSYNC_CHANGE_AVAILABLE_MSG') == 'Yes' or $isNewProduct == true) {
                                foreach ($productShop->shop_lang->product_shop_lang_available_now as $available_now) {
                                    $product_shop->available_now[(int)($available_now['id_lang'])] = (string)($available_now);
                                }
                                /* les Messages Hors Stock */
                                foreach ($productShop->shop_lang->product_shop_lang_available_later as $available_later) {
                                    $product_shop->available_later[(int)($available_later['id_lang'])] = (string)($available_later);
                                }
                            }
              
                            // Force le nom des articles si le nom est vide
                            $languages = Language::getLanguages(false);
                            foreach ($languages as $l) {
                                $id_lang = (int)$l['id_lang'];
                                
                                if (empty($product->name[$id_lang])) {
                                    $product_shop->name[$id_lang] = (string)$ProductXML->AR_Design;
                                }
                            }
                            // Force les urls simplifiés si le l'url est vide
                            $languages = Language::getLanguages(false);
                            foreach ($languages as $l) {
                                $id_lang = (int)$l['id_lang'];
                                
                                if (empty($product->link_rewrite[$id_lang])) {
                                    $product_shop->link_rewrite[$id_lang] = Tools::link_rewrite((string)$ProductXML->AR_Design);
                                }
                            } 
        
                            /* Enregistre les modifications */
                            if (!$product_shop->save()) {
                                echo 'An error occurred while updating shop for product.';
                                $succes = 0;
                            }
                        }
                    }
                }
            }
        }
        /* Doit être fait apres l'association des articles aux boutiques */
        
               
        /* Les numéros de série de SAGE */
        if ($ProductXML->Serials) {
            CreateSerials($product, $ProductXML);
        }
            
        /* Les prix spécifiques PS 1.4+ */
        if (Configuration::get('ATOOSYNC_CHANGE_SPPRICES') == 'Yes' or $isNewProduct == true) {
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                /* La priorité */
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'specific_price_priority` WHERE `id_product` = '.(int)($product->id));
                $priorities = array();
                array_push($priorities, $ProductXML->specific_price_priority1);
                array_push($priorities, $ProductXML->specific_price_priority2);
                array_push($priorities, $ProductXML->specific_price_priority3);
                array_push($priorities, $ProductXML->specific_price_priority4);
                SpecificPrice::setSpecificPriority($product->id, $priorities);
      
                /* Les prix */
                CreateSpecificPrice($product, $ProductXML);
            }
        }
        
        /* Ajoute les packs après la mise à jour de l'article si configuré dans le module */
        if (Configuration::get('ATOOSYNC_CREATE_PRODUCT_PACK') == 'Yes') {
            if ($ProductXML->packs) {
                Pack::deleteItems($product->id);/* Supprime les packs en premier */
                foreach ($ProductXML->packs->pack as $pack) {
                    $id_product_item = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($pack->reference).'\'');
                    if ($id_product_item) {
                        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                            Pack::addItem($product->id, $id_product_item, (int)($pack->quantity));
                        } else {
                            $sql = 'INSERT INTO '._DB_PREFIX_.'pack 
							(id_product_pack, id_product_item, quantity)
							VALUES  ('.(int)($product->id).','.(int)($id_product_item).','.(int)($pack->quantity).')';
                            Db::getInstance()->Execute($sql);
                        }
                    }
                }
            }
        }
        /* Ajoute les documents après la mise à jour de l'article
           sinon le cache des documents n'est pas actualisé */
        /* Supprime les documents en premier */
        if (Configuration::get('ATOOSYNC_CHANGE_DOCUMENT') == 'Yes' or $isNewProduct == true) {
            if (method_exists('Product', 'deleteAttachments')) {
                $product->deleteAttachments();
            } else {
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'product_attachment` WHERE `id_product` = '.(int)$product->id);
            }

            $attachments = array();
            foreach ($ProductXML->attachments->attachment as $id) {
                $id_attachment = Db::getInstance()->getValue('SELECT `id_attachment` FROM `'._DB_PREFIX_.'attachment` WHERE `id_attachment` = '.(int)($id));
                if ($id_attachment) {
                    array_push($attachments, $id_attachment);
                }
            }
            if (!empty($attachments)) {
                Attachment::attachToProduct($product->id, $attachments);
            }
        }
    
        // Associe l'article aux dépôts
        setProductWarehouses($ProductXML);
    
        /* Met à jour les prix par clients des articles */
        if (Configuration::get('ATOOSYNC_CHANGE_SPPRICES') == 'Yes' or $isNewProduct == true) {
            if ($ProductXML->customersprices) {
                SetCustomersPrices($ProductXML);
            }
        }

        // Appliquer les régles de prix
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            SpecificPriceRule::applyAllRules(array((int)($product->id)));
        }
      
        // Réindexe le produit
        ReIndexProduct($product->id);
        
        /* Met à jour le champ atoosync du produit à 1 */
        $sql = "UPDATE `"._DB_PREFIX_."product` SET `atoosync` = '1' WHERE `id_product` =".(int)($product->id);
        Db::getInstance()->Execute($sql);
            
        /* Enregistre le code Famille du produit */
        $sql = "UPDATE `"._DB_PREFIX_."product` SET `atoosync_codefamille` = '".$ProductXML->FA_CodeFamille."' WHERE `id_product` = '".(int)($product->id)."'";
        Db::getInstance()->Execute($sql);
            
        /* Customisation du produit */
        if ($isNewProduct == true) { // lancé uniquement si l'article est nouveau
            CustomizeNewProduct($product, $ProductXML);
        }
        CustomizeProduct($product, $ProductXML);
    }
    return $succes;
}
/*
 *
 */
function verifyProductFields($id_product)
{
    // Vérifie les urls simplifiés si elles n'existent pas dans product_lang.
    $query = "SELECT `id_lang` FROM `"._DB_PREFIX_."product_lang` WHERE `link_rewrite` ='' AND `id_product` = ".(int)($id_product);
    $langs = Db::getInstance()->ExecuteS($query, true, 0);
    foreach ($langs as $row) {
        $id_lang = (int)($row['id_lang']);
        $name = Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'product_lang` WHERE `id_lang` = '.(int)$id_lang.' AND `id_product` = '.(int)($id_product));
        $link_rewrite = Tools::link_rewrite((string)$name);
        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_lang` SET `link_rewrite` = \''.pSQL($link_rewrite).'\' WHERE `id_lang` = '.(int)$id_lang.' AND `id_product` = '.(int)($id_product));
    }
}
/*
 * Lance l'indexation de l'article
 */
function ReIndexProduct($id_product)
{
    if (method_exists('Search', 'indexation') and (Configuration::get('ATOOSYNC_REINDEX_PRODUCT') == 'Yes')) {
        if (isPrestaShop16() or isPrestaShop17()) {
            // Supprime l'indexation de l'article dans les boutiques
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `indexed` = 0 WHERE `id_product` = '.(int)($id_product));
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `indexed` = 0 WHERE `id_product` = '.(int)($id_product));
      
            Context::getContext()->shop->setContext(Shop::CONTEXT_ALL);
            Search::indexation(false, (int)$id_product);
        } elseif (isPrestaShop15()) {
            Context::getContext()->shop->setContext(Shop::CONTEXT_ALL);
        
            // Supprime l'indexation de l'article dans les boutiques
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `indexed` = 0 WHERE `id_product` = '.(int)($id_product));
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `indexed` = 0 WHERE `id_product` = '.(int)($id_product));
            
            // Construit l'url pour lancer l'indexation de l'article
            $current_file_name = array_reverse(explode('/', $_SERVER['SCRIPT_NAME']));
            $url = Tools::getHttpHost(true, true).__PS_BASE_URI__.
            substr($_SERVER['SCRIPT_NAME'], strlen(__PS_BASE_URI__), -strlen($current_file_name['0'])).
            'AtooSync-search.php?product='.$id_product.'&token='.substr(_COOKIE_KEY_, 34, 8);
            @file_get_contents($url);
            
            
            $indexcount = Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.'search_index` WHERE `id_product` = '.(int)($id_product));
            if ($indexcount != 0) {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `indexed` = 1 WHERE `id_product` = '.(int)($id_product));
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `indexed` = 1 WHERE `id_product` = '.(int)($id_product));
            }
        } else {
            Search::indexation(false, $id_product);
        }
    }
}
/*
 *	Créer les prix spécifiques pour l'article
 */
function CreateSpecificPrice($product, $ProductXML)
{
    // Customisation de la création des prix spécifiques
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeCreateSpecificPrice($product, $ProductXML) == true) {
        return true;
    }
    
    /* Supprime les prix spécifiques en premier */
    SpecificPrice::deleteByProductId($product->id);
    
    // detecte si le module flashsales de Prestaddons est installé
    $flashsales = Module::isInstalled('flashsales');
    if ($flashsales) {
        require(dirname(__FILE__).'../../flashsales/fsmodel.class.php');
        // supprime les ventes flash de l'article
        $sql = 'DELETE FROM `'._DB_PREFIX_.'fs_product` WHERE `id_product` = '.(int)$product->id;
        Db::getInstance()->execute($sql);
    }
    
    // mémorise si l'article à des déclinaisons
    $hasAttributes = $product->hasAttributes();
    
    /* Pour chaque prix spécifique */
    foreach ($ProductXML->specificprices->specific_price as $price) {
        /* Test si le groupe existe pour l'ajout du prix spécifique */
        $id_group = Db::getInstance()->getValue('SELECT `id_group` FROM `'._DB_PREFIX_.'group` WHERE `id_group` = \''.(int)($price->id_group).'\'');
        if (($id_group) or ($price->id_group == 0)) {
            $create_sp = true;
        
            // Si il y a du conditionnement sur l'article et
            // que l'on créé le conditionnement comme déclinaisons
            // alors ignore les prix spécifiques
            if ($ProductXML->Packagings and (Configuration::get('ATOOSYNC_COMBINATION_PACKAGING') == 'Yes')) {
                $create_sp = false;     
            }

            // Si il y a une déclinaison sur l'article et si la déclinaison n'existe pas alors elles est ignorée
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                if ((string)$price->product_attribute !='') {
                    $id_product_attribute = Db::getInstance()->getValue('SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `atoosync_gamme` = \''.(string)($price->product_attribute).'\'');
                    if (!$id_product_attribute) {
                        $create_sp = false ;
                    }
                }
            }
            
            if ($create_sp) {
                
                // si il ne s'agit pas d'un prix spécifique pour un client
                $customer_account = (string)$price->customer;
                if ($customer_account == '') {
                    $specificPrice = new SpecificPrice();
                    $specificPrice->id_product = (int)($product->id);
                    $specificPrice->id_shop = (int)($price->id_shop);
                    $specificPrice->id_currency =(int)($price->id_currency);
                    $specificPrice->id_country = (int)($price->id_country);
                    $specificPrice->id_group = (int)($price->id_group);
                    $specificPrice->id_customer = 0;
                    $specificPrice->price = (float)($price->price);
                    $specificPrice->from_quantity =(int)($price->from_quantity);
                    $specificPrice->reduction = (float)($price->reduction);
                    $specificPrice->reduction_type = (string)($price->reduction_type);
                    $specificPrice->from = (string)($price->from);
                    $specificPrice->to = (string)($price->to);
                    
                    /* Si PrestaShop 1.5 met le prix négatif si le prix = 0*/
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        /* Met le prix négatif si le prix = 0 */
                        if ((float)($specificPrice->price) == 0) {
                            $specificPrice->price = -1;
                        }
                    }   
                    
                    /* Si il y a une déclinaison associée */
                    if ($price->product_attribute !='') {
                        $id_product_attribute = Db::getInstance()->getValue('SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `atoosync_gamme` = \''.(string)($price->product_attribute).'\'');
                        if ($id_product_attribute) {
                            $specificPrice->id_product_attribute = $id_product_attribute;
                        }
                    }
                                        
                    if ($specificPrice->add()) {
                        /* Enregistre le type de prix spécifique de Sage */
                        $query = 'UPDATE `'._DB_PREFIX_.'specific_price` SET 
								`atoosync_type` = \''.(int)($price->atoosync_type).'\' 
								WHERE `id_specific_price` = \''.(int)($specificPrice->id).'\'';
                        Db::getInstance()->Execute($query);
                        
                        // créer les prix par vente flash pour le module FlashSales de Prestaddons
                        if ((int)($price->flash_sale) == 1 and $flashsales) {
                            $reduction = $specificPrice->reduction;
                            if ($specificPrice->reduction_type == 'percentage') {
                                $reduction = $reduction * 100;
                            }
                            
                            FsModel::insertFlashSale($specificPrice->id_product, $specificPrice->id_product_attribute, $specificPrice->from, $specificPrice->to, $reduction, $specificPrice->reduction_type, 1, $specificPrice->id, $specificPrice->id_currency, $specificPrice->id_country, $specificPrice->id_group);
                        }
                        
                        // si l'article à des déclinaisons alors recopie le prix du groupe de client pour chaque déclinaison 
                        // de l'article pour contrer le dysfonctionnement de PrestaShop qui applique toujours
                        // l'impact de prix de la déclinaison en plus du prix spécifique.
                        if ($hasAttributes and $specificPrice->id_product_attribute == 0 and $specificPrice->id_group > 0) {
                            
                            $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'specific_price`(`id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_type`, `from`, `to`, `atoosync_type`) 
                                    (SELECT sp.`id_product`, sp.`id_shop`, sp.`id_shop_group`, sp.`id_currency`, sp.`id_country`, sp.`id_group`, sp.`id_customer`, pa.`id_product_attribute`, sp.`price`, sp.`from_quantity`, sp.`reduction`, sp.`reduction_type`, sp.`from`, sp.`to`, sp.`atoosync_type` 
                                    FROM `'._DB_PREFIX_.'specific_price` as sp, `'._DB_PREFIX_.'product_attribute` as pa WHERE pa.`id_product` = sp.`id_product` and sp.`id_product_attribute` =0 and sp.`id_specific_price` = '.(int)$specificPrice->id.')';

                            Db::getInstance()->execute($sql);
                            Db::getInstance()->execute('delete from `'._DB_PREFIX_.'specific_price` where `id_specific_price` = '.(int)$specificPrice->id);
                        }
                    }
                } else {
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        /*	Recherche les clients avec le code client ou la centrale d'achat */
                        $query = 'SELECT `id_customer` 
								  FROM `'._DB_PREFIX_.'customer` 
								  WHERE `atoosync_code_client` = \''.(string)($price->customer).'\'
								  OR `atoosync_centrale_achat` = \''.(string)($price->customer).'\'
								  ';
                        $customers = Db::getInstance()->ExecuteS($query);
                        foreach ($customers as $k => $row) {
                            $id_customer = (int)($row['id_customer']);
                            
                            $specificPrice = new SpecificPrice();
                            $specificPrice->id_product = (int)($product->id);
                            $specificPrice->id_shop = (int)($price->id_shop);
                            $specificPrice->id_currency =(int)($price->id_currency);
                            $specificPrice->id_country = (int)($price->id_country);
                            $specificPrice->id_group = (int)($price->id_group);
                            $specificPrice->id_customer = $id_customer;
                            $specificPrice->price = (float)($price->price);
                            $specificPrice->from_quantity =(int)($price->from_quantity);
                            $specificPrice->reduction = (float)($price->reduction);
                            $specificPrice->reduction_type = (string)($price->reduction_type);
                            $specificPrice->from = (string)($price->from);
                            $specificPrice->to = (string)($price->to);
                            
                            /* Si PrestaShop 1.5 met le prix négatif si le prix = 0*/
                            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                                /* Met le prix négatif si le prix = 0 */
                                if ((float)($specificPrice->price) == 0) {
                                    $specificPrice->price = -1;
                                }
                            }   
                            
                            /* Si il y a une déclinaison associée */
                            if ($price->product_attribute !='') {
                                $id_product_attribute = Db::getInstance()->getValue('SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `atoosync_gamme` = \''.(string)($price->product_attribute).'\'');
                                if ($id_product_attribute) {
                                    $specificPrice->id_product_attribute = $id_product_attribute;
                                }
                            }
                                                        
                            if ($specificPrice->add()) {
                                /* Enregistre le type de prix spécifique de Sage */
                                $query = 'UPDATE `'._DB_PREFIX_.'specific_price` SET 
										`atoosync_type` = \''.(int)($price->atoosync_type).'\' 
										WHERE `id_specific_price` = \''.(int)($specificPrice->id).'\'';
                                Db::getInstance()->Execute($query);
                            }
                            
                            // si l'article à des déclinaisons alors recopie le prix du client pour chaque déclinaison 
                            // de l'article pour contrer le dysfonctionnement de PrestaShop qui applique toujours
                            // l'impact de prix de la déclinaison en plus du prix spécifique.
                            if ($hasAttributes and $specificPrice->id_product_attribute == 0) {
                                
                                $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'specific_price`(`id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_type`, `from`, `to`, `atoosync_type`) 
                                        (SELECT sp.`id_product`, sp.`id_shop`, sp.`id_shop_group`, sp.`id_currency`, sp.`id_country`, sp.`id_group`, sp.`id_customer`, pa.`id_product_attribute`, sp.`price`, sp.`from_quantity`, sp.`reduction`, sp.`reduction_type`, sp.`from`, sp.`to`, sp.`atoosync_type` 
                                        FROM `'._DB_PREFIX_.'specific_price` as sp, `'._DB_PREFIX_.'product_attribute` as pa WHERE pa.`id_product` = sp.`id_product` and sp.`id_product_attribute` =0 and sp.`id_specific_price` = '.(int)$specificPrice->id.')';

                                Db::getInstance()->execute($sql);
                                Db::getInstance()->execute('delete from `'._DB_PREFIX_.'specific_price` where `id_specific_price` = '.(int)$specificPrice->id);
                            }
                        }
                    }
                }
            }
        }
    }
}
/*
 *	Créer les prix spécifiques pour l'attribut de l'article
 */
function CreateSpecificPriceAttribute($product, $id_product_attribute, $ProductXML)
{
    /* Supprime les prix spécifiques de l'attribut en premier */
    Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'specific_price` WHERE `id_product` = '.(int)$product->id.' AND `id_product_attribute` = '.(int)$id_product_attribute);
                
    /* Pour chaque prix */
    foreach ($ProductXML->specificprices->specific_price as $price) {
        /* Test si le groupe existe pour l'ajout du prix spécifique */
        $id_group = Db::getInstance()->getValue('SELECT `id_group` FROM `'._DB_PREFIX_.'group` WHERE `id_group` = \''.(int)($price->id_group).'\'');
        if (($id_group) or ($price->id_group == 0)) {
            $customer_code = (string)($price->customer);
            $create_sp = true;
        
            // Si il y a du conditionnement sur l'article et
            // que l'on créé le conditionnement comme déclinaisons
            // alors ignore les prix spécifiques avec un prix de renseigné, créé que les prix spécifique en remise
            if ($ProductXML->Packagings and (Configuration::get('ATOOSYNC_COMBINATION_PACKAGING') == 'Yes') and (float)($price->price) > 0) {
                $create_sp = false ;
            }
        
            if ($create_sp) {
                // créé le prix spécifique pour le groupe de client
                if (empty($customer_code)) {
                    $specificPrice = new SpecificPrice();
                    $specificPrice->id_product = (int)($product->id);
                    $specificPrice->id_product_attribute = $id_product_attribute;
                    $specificPrice->id_shop = (int)($price->id_shop);
                    $specificPrice->id_currency =(int)($price->id_currency);
                    $specificPrice->id_country = (int)($price->id_country);
                    $specificPrice->id_group = (int)($price->id_group);
                    $specificPrice->id_customer = 0;
                    $specificPrice->price = (float)($price->price);
                    $specificPrice->from_quantity =(int)($price->from_quantity);
                    $specificPrice->reduction = (float)($price->reduction);
                    $specificPrice->reduction_type = (string)($price->reduction_type);
                    $specificPrice->from = (string)($price->from);
                    $specificPrice->to = (string)($price->to);
                    
                    /* Si PrestaShop 1.5 met le prix négatif si le prix = 0*/
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        /* Met le prix négatif si le prix = 0 */
                        if ((float)($specificPrice->price) == 0) {
                            $specificPrice->price = -1;
                        }
                    }
                    
                    if ($specificPrice->add()) {
                        /* Enregistre le type de prix spécifique de Sage */
                        $query = 'UPDATE `'._DB_PREFIX_.'specific_price` SET 
								`atoosync_type` = \''.(int)($price->atoosync_type).'\' 
								WHERE `id_specific_price` = \''.(int)($specificPrice->id).'\'';
                        Db::getInstance()->Execute($query);
                    }
                } else {
                    /*	Recherche les clients avec le code client ou la centrale d'achat */
                    $query = 'SELECT `id_customer` 
							  FROM `'._DB_PREFIX_.'customer` 
							  WHERE `atoosync_code_client` = \''.$customer_code.'\'
							  OR `atoosync_centrale_achat` = \''.$customer_code.'\'
							  ';
                    $customers = Db::getInstance()->ExecuteS($query, true, 0);
                    foreach ($customers as $k => $row) {
                        $id_customer = (int)($row['id_customer']);
                        
                        $specificPrice = new SpecificPrice();
                        $specificPrice->id_product = (int)($product->id);
                        $specificPrice->id_product_attribute = $id_product_attribute;
                        $specificPrice->id_shop = (int)($price->id_shop);
                        $specificPrice->id_currency =(int)($price->id_currency);
                        $specificPrice->id_country = (int)($price->id_country);
                        $specificPrice->id_group = (int)($price->id_group);
                        $specificPrice->id_customer = $id_customer;
                        $specificPrice->price = (float)($price->price);
                        $specificPrice->from_quantity =(int)($price->from_quantity);
                        $specificPrice->reduction = (float)($price->reduction);
                        $specificPrice->reduction_type = (string)($price->reduction_type);
                        $specificPrice->from = (string)($price->from);
                        $specificPrice->to = (string)($price->to);
                                        
                        /* Si PrestaShop 1.5 met le prix négatif si le prix = 0*/
                        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                            /* Met le prix négatif si le prix = 0 */
                            if ((float)($specificPrice->price) == 0) {
                                $specificPrice->price = -1;
                            }
                        }
                        
                        if ($specificPrice->add()) {
                            /* Enregistre le type de prix spécifique de Sage */
                            $query = 'UPDATE `'._DB_PREFIX_.'specific_price` SET 
									`atoosync_type` = \''.(int)($price->atoosync_type).'\' 
									WHERE `id_specific_price` = \''.(int)($specificPrice->id).'\'';
                            Db::getInstance()->Execute($query);
                        }
                    }
                }
            }
        }
    }
}
/*
 *	Créer les prix spécifiques par conditionnement pour la déclinaison de l'article
 */
function CreateSpecificPricePackaging($PackagingXML, $product, $id_product_attribute)
{
    /* Supprime les prix spécifiques de l'attribut en premier */
    Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'specific_price` WHERE `id_product` = '.(int)$product->id.' AND `id_product_attribute` = '.(int)$id_product_attribute);
    
    /* Pour chaque prix */
    foreach ($PackagingXML->Prices->Price as $price) {
        /* Test si le groupe existe pour l'ajout du prix spécifique */
        $id_group = Db::getInstance()->getValue('SELECT `id_group` FROM `'._DB_PREFIX_.'group` WHERE `atoosync_id` = \''.(int)($price->customer_category).'\'');
        if (($id_group) or ($price->customer_category == 0)) {
            $customer_code = (string)($price->customer);
            $currency_iso = (string)($price->currency_isocode);
            $id_currency = Db::getInstance()->getValue('SELECT `id_currency` FROM `'._DB_PREFIX_.'currency` WHERE `iso_code` = \''.$currency_iso.'\'');
                            
            // créé le prix spécifique pour le groupe de client
            if (empty($customer_code)) {
                $specificPrice = new SpecificPrice();
                $specificPrice->id_product = (int)($product->id);
                $specificPrice->id_product_attribute = $id_product_attribute;
                $specificPrice->id_shop = 0;
                $specificPrice->id_currency = $id_currency;
                $specificPrice->id_country = 0;
                $specificPrice->id_group = $id_group;
                $specificPrice->id_customer = 0;
                if ((float)$PackagingXML->PVHT == (float)$price->PVHT) {
					$specificPrice->price = 0;
				} else {
					$specificPrice->price = (float)($price->PVHT);
				}
                $specificPrice->from_quantity =1;
                if ((float)$price->discount > 0) {
                    $specificPrice->reduction = (float)((float)$price->discount / 100);
                    $specificPrice->reduction_type = 'percentage';
                } else {
                    $specificPrice->reduction = 0;
                    $specificPrice->reduction_type = 'amount';
                }
                $specificPrice->from = '0000-00-00 00:00:00';
                $specificPrice->to = '0000-00-00 00:00:00';
                                         
                /* Si PrestaShop 1.5 met le prix négatif si le prix = 0*/
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    /* Met le prix négatif si le prix = 0 */
                    if ((float)($specificPrice->price) == 0) {
                        $specificPrice->price = -1;
                    }
                }
                
				if ($specificPrice->price != 0 AND $specificPrice->reduction  !=0) {
					if ($specificPrice->add()) {
						/* Enregistre le type de prix spécifique de Sage */
						$query = 'UPDATE `'._DB_PREFIX_.'specific_price` SET 
								`atoosync_type` = 10 WHERE `id_specific_price` = \''.(int)($specificPrice->id).'\'';
						Db::getInstance()->Execute($query);
					}
				}
            } else {
            
                /*	Recherche les clients avec le code client ou la centrale d'achat */
                $query = 'SELECT `id_customer` 
						  FROM `'._DB_PREFIX_.'customer` 
						  WHERE `atoosync_code_client` = \''.$customer_code.'\'
						  OR `atoosync_centrale_achat` = \''.$customer_code.'\'
						  ';
                $customers = Db::getInstance()->ExecuteS($query, true, 0);
                foreach ($customers as $k => $row) {
                    $id_customer = (int)($row['id_customer']);
                    
                    $specificPrice = new SpecificPrice();
                    $specificPrice->id_product = (int)($product->id);
                    $specificPrice->id_product_attribute = $id_product_attribute;
                    $specificPrice->id_shop = 0;
                    $specificPrice->id_currency = $id_currency;
                    $specificPrice->id_country = 0;
                    $specificPrice->id_group = 0;
                    $specificPrice->id_customer = $id_customer;
                    if ((float)$PackagingXML->PVHT == (float)$price->PVHT) {
						$specificPrice->price = 0;
					} else {
						$specificPrice->price = (float)($price->PVHT);
					}
                    $specificPrice->from_quantity =1;
                    if ((float)$price->discount > 0) {
                        $specificPrice->reduction = (float)((float)$price->discount /100);
                        $specificPrice->reduction_type = 'percentage';
                    } else {
                        $specificPrice->reduction = 0;
                        $specificPrice->reduction_type = 'amount';
                    }
                    $specificPrice->from = '0000-00-00 00:00:00';
                    $specificPrice->to = '0000-00-00 00:00:00';
                
                    /* Si PrestaShop 1.5 met le prix négatif si le prix = 0*/
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        /* Met le prix négatif si le prix = 0 */
                        if ((float)($specificPrice->price) == 0) {
                            $specificPrice->price = -1;
                        }
                    }
                    
                    if ($specificPrice->price != 0 AND $specificPrice->reduction  !=0) {
						if ($specificPrice->add()) {
							/* Enregistre le type de prix spécifique de Sage */
							$query = 'UPDATE `'._DB_PREFIX_.'specific_price` SET 
									`atoosync_type` = 10 WHERE `id_specific_price` = \''.(int)($specificPrice->id).'\'';
							Db::getInstance()->Execute($query);
						}
					}
                }
            }
        }
    }
}
/*
 *	Créer les déclinaisons de l'article
 */
function CreateCombinations($product, $ProductXML)
{
    // Créé les groupes d'attributs et les attributs
    createProductAttributes($ProductXML);

    // Construit la liste des boutiques de l'article
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $id_shop_list = array();
        $id_shop_list_array = Product::getShopsByProduct((int)($product->id));
        foreach ($id_shop_list_array as $array_shop) {
            $id_shop_list[] = (int)($array_shop['id_shop']);
        }
    }
  
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        // créé les product_attribute_shop dans toutes les boutiques en premier pour pouvoir les modifier
        $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'product_attribute_shop` 
            (`id_product`, `id_product_attribute`, `id_shop`, `wholesale_price`, `price`, `ecotax`, `weight`, `unit_price_impact`, `default_on`, `minimal_quantity`, `available_date`) 
            SELECT `id_product`, `id_product_attribute`, `id_shop`, `wholesale_price`, `price`, `ecotax`, `weight`, `unit_price_impact`, `default_on`, `minimal_quantity`, `available_date` 
            FROM `'._DB_PREFIX_.'product_attribute`, `'._DB_PREFIX_.'shop` WHERE `id_product` = '.(int)$product->id;
        Db::getInstance()->execute($sql);
    }
  
    /* Efface l'atribut par défaut avant la msie à jour. */
    if (isPrestaShop16() or isPrestaShop17()) {
        $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `default_on`=null WHERE `id_product` ='.(int)($product->id);
        Db::getInstance()->Execute($sql);
  
        /* Prépare pour supprimer les attributs qui n'existe plus. */
        $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET `default_on`=null WHERE `id_product` ='.(int)($product->id);
        Db::getInstance()->Execute($sql);
    }

    /* Prépare pour supprimer les attributs qui n'existe plus. */
    $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `atoosync_delete`=1 WHERE `id_product` ='.(int)($product->id);
    Db::getInstance()->Execute($sql);
    
    foreach ($ProductXML->Combinations->product_attribute as $productattribute) {
        $unique =  $ProductXML->reference.'_'.$productattribute->Gamme1.'_'.$productattribute->Gamme2;
        /* Calcul la différence de prix */
        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $pricedif = (float)($productattribute->price) - (float)($ProductXML->price);
        } else {
            $tax = new Tax(intval($product->id_tax));
            $productTTC =  (float)($product->price) * (1 + ((float)($tax->rate) / 100));
            $priceTTC = (float)($productattribute->price) * (1 + ((float)($tax->rate) / 100));
            $pricedif = (float)($priceTTC) - (float)($productTTC);
        }
        $pricedif = round($pricedif, 6);
        
        // supprime les retours à la ligne des ean ou upc.
        $productattribute->ean13 = str_replace (array("\r\n", "\n", "\r"), '', $productattribute->ean13);
        $productattribute->upc = str_replace (array("\r\n", "\n", "\r"), '', $productattribute->upc);
    
        /* Valide les codes barres */
        if (method_exists('Validate', 'isEan13')) {
            if (!Validate::isEan13((string)($productattribute->ean13))) {
                $productattribute->ean13 = '';
            }
        }
        if (method_exists('Validate', 'isUpc')) {
            if (!Validate::isUpc((string)($productattribute->upc))) {
                $productattribute->upc = '';
            }
        }

        /* Si prix d'achat <0 alors = 0*/
        if ((float)$productattribute->wholesale_price <0) {
            $productattribute->wholesale_price = 0;
        }
        
        /* Les images */
        $images = array();
        foreach ($productattribute->images->image as $image) {
            $image_id = Db::getInstance()->getValue('SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `id_product` ='.(int)($product->id).' AND `atoosync_image_id` = '.(int)($image));
            if ($image_id) {
                array_push($images, $image_id);
            }
        }
        
        /* Trouve le product_attribute à partir de la clé Atoo-sync */
        $id_product_attribute = Db::getInstance()->getValue("
			SELECT `id_product_attribute`
					FROM `"._DB_PREFIX_."product_attribute`
					WHERE `id_product` = ".(int)($product->id)." 
					AND `atoosync_gamme` ='".pSQL($unique)."'");
        
        /* Essaye de trouver le product_attribute à partir de la référence */
        if (Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') == 'Yes') {
            if (!$id_product_attribute and (string)($productattribute->reference)<>'') {
                $id_product_attribute = Db::getInstance()->getValue("
					SELECT `id_product_attribute`
							FROM `"._DB_PREFIX_."product_attribute`
							WHERE 	`id_product` = ".(int)($product->id)." 
							AND `reference` ='".pSQL($productattribute->reference)."'");
            }
        }
        /* Essaye de trouver le product_attribute à partir du code barre */
        if (Configuration::get('ATOOSYNC_COMBINATION_EAN13') == 'Yes') {
            if (!$id_product_attribute  and (string)($productattribute->ean13)<>'') {
                $id_product_attribute = Db::getInstance()->getValue("
					SELECT `id_product_attribute`
							FROM `"._DB_PREFIX_."product_attribute`
							WHERE 	`id_product` = ".(int)($product->id)." 
							AND `ean13` ='".pSQL($productattribute->ean13)."'");
            }
        }
        /* Test si l'attribut est bien lié à l'article
           Sinon il est supprimé. */
        if ($id_product_attribute) {
            $id_product = Db::getInstance()->getValue('
				SELECT `id_product`
				FROM `'._DB_PREFIX_.'product_attribute`
				WHERE `id_product_attribute` ='.$id_product_attribute);
            
            if ((int)($id_product) <> (int)($product->id)) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
                } else {
                    $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
                }
                unset($id_product_attribute);
            }
        }
    
        // efface la déclinaison par défaut dans PrestaShop.
		if ((int)$productattribute->default_on == 1) {
			if (isPrestaShop16() or isPrestaShop17()) {
                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `default_on`=null WHERE `id_product` ='.(int)($product->id);
                Db::getInstance()->Execute($sql);

                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET `default_on`=null WHERE `id_product` ='.(int)($product->id);
                Db::getInstance()->Execute($sql);
            } else {
                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `default_on` = 0 WHERE `id_product` = '.(int)$product->id;     
                Db::getInstance()->Execute($sql);
            }
		}
        
        /* Si l'attribut de l'article n'existe pas */
        if (!$id_product_attribute) {
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $id_product_attribute = $product->addAttribute(
											
                    $pricedif,
                                                                (float)($productattribute->weight),
                                                                (float)($productattribute->unit_price_impact),
                                                                (float)($productattribute->ecotax),
                                                                $images,
                                                                (string)($productattribute->reference),
                                                                (string)($productattribute->ean13),
                                                                (int)($productattribute->default_on),
                                                                (string)($productattribute->location),
                                                                (string)($productattribute->upc),
                                                                (int)($productattribute->minimal_quantity),
                                                                $id_shop_list
											
                );

                // Modifie la combinaison une fois créée car tous les champs ne sont pas disponible dans le product->addAttribute()
                if ($id_product_attribute) {
                    $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
						`wholesale_price` = '.(float)($productattribute->wholesale_price).',
						`minimal_quantity` = '.(int)($productattribute->minimal_quantity).'
						WHERE `id_product_attribute` = '.$id_product_attribute;
                    Db::getInstance()->Execute($sql);
                    
                    $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
						`wholesale_price` = '.(float)($productattribute->wholesale_price).',
						`minimal_quantity` = '.(int)($productattribute->minimal_quantity).'
						WHERE `id_product_attribute` = '.$id_product_attribute;
                    Db::getInstance()->Execute($sql);
                    
                    if ((int)$productattribute->default_on == 1) {                   
                        $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `default_on`=1 WHERE `id_product_attribute` ='.(int)$id_product_attribute;
                        Db::getInstance()->Execute($sql);

                        $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET `default_on`=1 WHERE `id_product_attribute` ='.(int)$id_product_attribute;
                        Db::getInstance()->Execute($sql);
                    }
                
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        StockAvailable::setQuantity($product->id, $id_product_attribute, $productattribute->quantity, (int)($id_shop));
                    }
                }
            } elseif (isPrestaShop14()) {
                $id_product_attribute = $product->addCombinationEntity(
	
                    $productattribute->wholesale_price,
                                                                        $pricedif,
                                                                        $productattribute->weight,
                                                                        0,
                                                                        $productattribute->ecotax,
                                                                        $productattribute->quantity,
                                                                        $images,
                                                                        $productattribute->reference,
                                                                        $productattribute->supplier_reference,
                                                                        $productattribute->ean13,
                                                                        $productattribute->default_on,
                                                                        $productattribute->location,
                                                                        $productattribute->upc
	
                );
            } else {
                $id_product_attribute = $product->addCombinationEntity(
	
                    $productattribute->wholesale_price,
                                                                        $pricedif,
                                                                        $productattribute->weight,
                                                                        $productattribute->ecotax,
                                                                        $productattribute->quantity,
                                                                        $images,
                                                                        $productattribute->reference,
                                                                        $productattribute->supplier_reference,
                                                                        $productattribute->ean13,
                                                                        $productattribute->default_on,
                                                                        $productattribute->location
	
                );
            }
        }
                
        /* Si le product attribut existe */
        if ($id_product_attribute) {
            /* Selon la version de PrestaShop, modifie l'attribut */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        
        // Modifie l'attribut
                $product->updateAttribute(
            $id_product_attribute,
                      (float)($productattribute->wholesale_price),
                      (float)($pricedif),
                      (float)($productattribute->weight),
                      0,
                      (float)($productattribute->ecotax),
                      $images,
                      (string)($productattribute->reference),
                      (string)($productattribute->ean13),
                      (int)($productattribute->default_on),
                      (string)($productattribute->location),
                      (string)($productattribute->upc),
                      (int)($productattribute->minimal_quantity),
                      (string)($productattribute->available_date),
                      true,
                      $id_shop_list
        );
        
                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
					`wholesale_price` = '.(float)($productattribute->wholesale_price).',
					`minimal_quantity` = '.(int)($productattribute->minimal_quantity).'
					WHERE `id_product_attribute` = '.$id_product_attribute;
                Db::getInstance()->Execute($sql);
                
                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
						`wholesale_price` = '.(float)($productattribute->wholesale_price).',
						`minimal_quantity` = '.(int)($productattribute->minimal_quantity).'
						WHERE `id_product_attribute` = '.$id_product_attribute;
                Db::getInstance()->Execute($sql);
                    
                if ((int)$productattribute->default_on == 1) {                   
                    $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `default_on`=1 WHERE `id_product_attribute` ='.(int)$id_product_attribute;
                    Db::getInstance()->Execute($sql);

                    $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET `default_on`=1 WHERE `id_product_attribute` ='.(int)$id_product_attribute;
                    Db::getInstance()->Execute($sql);
                }
                
                foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                    StockAvailable::setQuantity($product->id, $id_product_attribute, $productattribute->quantity, (int)($id_shop));
                }
                
                /* Le fournisseur de la déclinaison pour PrestaShop 1.5+ */
                if ((int)$ProductXML->id_supplier != 0) {
                    $product->addSupplierReference((int)$ProductXML->id_supplier, $id_product_attribute, (string)($productattribute->supplier_reference), (float)($productattribute->wholesale_price), null);
                } else {
                    $product->deleteFromSupplier();
                }
            } elseif (isPrestaShop14()) {
                $product->updateProductAttribute(
                    $id_product_attribute,
                                                    $productattribute->wholesale_price,
                                                    $pricedif,
                                                    $productattribute->weight,
                                                    $productattribute->unit,
                                                    $productattribute->ecotax,
                                                    $productattribute->quantity,
                                                    $images,
                                                    $productattribute->reference,
                                                    $productattribute->supplier_reference,
                                                    $productattribute->ean13,
                                                    $productattribute->default_on,
                                                    $productattribute->location,
                                                    $productattribute->upc,
                                                    $productattribute->minimal_quantity
                );
            } else {
                $product->updateProductAttribute(
	
                    $id_product_attribute,
                                                    $productattribute->wholesale_price,
                                                    $pricedif,
                                                    $productattribute->weight,
                                                    $productattribute->ecotax,
                                                    $productattribute->quantity,
                                                    $images,
                                                    $productattribute->reference,
                                                    $productattribute->supplier_reference,
                                                    $productattribute->ean13,
                                                    $productattribute->default_on,
                                                    $productattribute->location
	
                );
            }
            
            // Supprime les product_attribute_combination du product_attribute existant pour les recréer correctement
            // Permet de refixer correctement les combinaisons
            Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'product_attribute_combination` WHERE `id_product_attribute` = '.(int)($id_product_attribute));

            /* Associe la gamme 1 à la combinaison */
            if ((int)($productattribute->Gamme1) !=0) {
                
                /* Trouve l'attribut de la gamme 1 */
                $sql ='SELECT `id_attribute_group`
						FROM `'._DB_PREFIX_.'attribute_group`
						WHERE `atoosync_gamme`='.(int)($ProductXML->AR_Gamme1);
                $id_attribute_group = Db::getInstance()->getValue($sql);
            
                $atoosync_enumere = strtolower(Tools::replaceAccentedChars($productattribute->Gamme1Intitule));
                // recherche en premier l'id_attribute à partir de l'énuméré de Sage
                $sql = 'SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'attribute`
						WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
						AND `atoosync_enumere`=\''.pSQL($atoosync_enumere).'\'';
                $id_attribute = Db::getInstance()->getValue($sql);
                
                if ($id_attribute) {
                    // Si l'association n'existe pas, elle est créée
                    $sql ='SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'product_attribute_combination`
						WHERE `id_attribute`='.(int)($id_attribute).' AND  `id_product_attribute` = '.(int)($id_product_attribute);
                    $exist = Db::getInstance()->getValue($sql);
                    if (!$exist) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'product_attribute_combination`
						(`id_attribute`, `id_product_attribute`) 
						VALUES  ('.(int)($id_attribute).','.(int)($id_product_attribute).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
            }
            /* Associe la gamme 2 à la combinaison */
            if ((int)($productattribute->Gamme2) !=0) {
                /* Trouve l'attribut de la gamme 2 */
                $sql ='SELECT `id_attribute_group`
						FROM `'._DB_PREFIX_.'attribute_group`
						WHERE `atoosync_gamme`='.(int)($ProductXML->AR_Gamme2);
                $id_attribute_group = Db::getInstance()->getValue($sql);
                
                $atoosync_enumere = strtolower(Tools::replaceAccentedChars($productattribute->Gamme2Intitule));
                // recherche en premier l'id_attribute à partir de l'énuméré de Sage
                $sql = 'SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'attribute`
						WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
						AND `atoosync_enumere`=\''.pSQL($atoosync_enumere).'\'';
                $id_attribute = Db::getInstance()->getValue($sql);
            
                if ($id_attribute) {
                    // Si l'association n'existe pas, elle est créée
                    $sql ='SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'product_attribute_combination`
						WHERE `id_attribute`='.(int)($id_attribute).' AND  `id_product_attribute` = '.(int)($id_product_attribute);
                    $exist = Db::getInstance()->getValue($sql);
                    if (!$exist) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'product_attribute_combination`
						(`id_attribute`, `id_product_attribute`) 
						VALUES  ('.(int)($id_attribute).','.(int)($id_product_attribute).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
            }
            
            /* Enregistre le code unique dans le product attribut */
            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
					`atoosync_gamme` = \''.$unique.'\', `atoosync_delete`=0 
					WHERE `id_product_attribute` = '.(int)($id_product_attribute);
            Db::getInstance()->Execute($sql);
            
            // Si le product attribute n'a pas de conbinaisons alors il est supprimé
            $sql ='SELECT count(*) 
					FROM `'._DB_PREFIX_.'product_attribute_combination`
					WHERE `id_product_attribute` = '.(int)($id_product_attribute);
            $count= Db::getInstance()->getValue($sql);
            if ((int)($count) == 0) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $product->deleteAttributeCombination((int)($id_product_attribute));
                } else {
                    $product->deleteAttributeCombinaison((int)($id_product_attribute));
                }
            }
        }
    }
    
    // supprime les déclinaisons qui ne sont pas/plus dans les boutiques de l'article
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        /* Créé les données de l'article dans les boutiques */
        foreach ($ProductXML->product_shops->product_shop as $productShop) {
            $id_shop = (int)($productShop['id_shop']);
            $id_shop = Db::getInstance()->getValue('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop`='.(int)($id_shop));
            if ($id_shop) {
                if ($productShop->product_shop_visible == 0) {
                    $sql = 'DELETE FROM `'._DB_PREFIX_.'product_attribute_shop` 
                                   WHERE `id_product` = '.(int)($product->id).' 
                                   AND `id_shop`  = '.(int)($id_shop);
                    Db::getInstance()->execute($sql);
                }
            }
        }
    }
  
    // Customisation des déclinaisons
    CustomizeCombinations($product, $ProductXML);
    
    /* Supprime les products attributs qui n'existe plus. */
    $sql = 'SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product` = '.(int)($product->id).' AND `atoosync_delete`=1';
    $pas = Db::getInstance()->ExecuteS($sql, true, 0);
    foreach ($pas as $pa) {
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            Shop::setContext(Shop::CONTEXT_ALL);
            $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
        } else {
            $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
        }
    }

    // met à jour l'attribut par défaut de l'article
    $product->checkDefaultAttributes();
    if (method_exists('Product', 'updateDefaultAttribute')) {
        Product::updateDefaultAttribute($product->id);
    }
            
    if (isPrestaShop13() or isPrestaShop14()) {
        $product->updateQuantityProductWithAttributeQuantity();
    }
}

/*
 *	Créé les attributs associé à l'article
 */
function createProductAttributes($ProductXML)
{
    
    // Customisation des groupes d'attributs et des attributs de l'article
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeProductAttributes($ProductXML) == true) {
        return 0;
    }
    
    /* pour chaque énuméré */
    foreach ($ProductXML->Combinations->product_attribute as $productattribute) {
        /* la gamme 1 de l'énuméré */
        if ((int)($productattribute->Gamme1) != 0) {
            $id_attribute_group = Db::getInstance()->getValue('
				SELECT `id_attribute_group`
				FROM `'._DB_PREFIX_.'attribute_group`
				WHERE `atoosync_gamme`='.(int)($ProductXML->AR_Gamme1));
            
            /* Créé le groupe d'attribut si il n'existe pas */
            if (!$id_attribute_group) {
                $obj = new AttributeGroup();
                $obj->is_color_group = false;
                $obj->group_type = 'select';
                $obj->name = CreateMultiLangField((string)($ProductXML->AR_Gamme1_Intitule));
                $obj->public_name = CreateMultiLangField((string)($ProductXML->AR_Gamme1_Intitule));
                $obj->add();
                $id_attribute_group = $obj->id;
                
                $sql = 'UPDATE `'._DB_PREFIX_.'attribute_group` SET `atoosync_gamme` = \''.(string)($ProductXML->AR_Gamme1).'\' WHERE `id_attribute_group` = \''.(int)($obj->id).'\'';
                Db::getInstance()->Execute($sql);
            }
            if ($id_attribute_group) {
                /* met à jour le nom du groupe d'attribut */
                if (Configuration::get('ATOOSYNC_ATTRIBUTE_GROUP') != 'No') {
                    $obj = new AttributeGroup($id_attribute_group);
                    $obj->name = CreateMultiLangField($ProductXML->AR_Gamme1_Intitule);
                    //$obj->public_name = CreateMultiLangField($ProductXML->AR_Gamme1_Intitule);
                    $obj->save();
                }
                
                /* Associé l'AttributeGroup dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // suprime en premier
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_group_shop` WHERE `id_attribute_group` = '.(int)($id_attribute_group));
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_group_shop` (`id_attribute_group`, `id_shop`) VALUES ('.(int)($id_attribute_group).','.(int)($id_shop).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
                
                $atoosync_enumere = strtolower(Tools::replaceAccentedChars($productattribute->Gamme1Intitule));
                // recherche en premier l'id_attribute à partir de l'énuméré de Sage
                $sql = 'SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'attribute`
						WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
						AND LOWER(`atoosync_enumere`)=\''.pSQL($atoosync_enumere).'\'';
                $id_attribute = Db::getInstance()->getValue($sql);

                if (!$id_attribute) {
                    $obj = new Attribute();
                    $obj->id_attribute_group = $id_attribute_group;
                    $obj->name = CreateMultiLangField($productattribute->Gamme1Intitule);
                    $obj->add();
                    
                    // $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET
                    // `atoosync_gamme` = '.(int)($productattribute->Gamme1).',
                    // `atoosync_enumere` = \''.$productattribute->Gamme1Intitule.'\'
                    // WHERE `id_attribute`='.$obj->id;
                    $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET 
							`atoosync_enumere` = \''.pSQL($atoosync_enumere).'\' 
							WHERE `id_attribute`='.$obj->id;
                    Db::getInstance()->Execute($sql);
                    $id_attribute = $obj->id;
                } else {
                    // met à jour le nom de la valeur
                    if (Configuration::get('ATOOSYNC_ATTRIBUTE_VALUE') != 'No') {
                        $obj = new Attribute($id_attribute);
                        $obj->name = CreateMultiLangField($productattribute->Gamme1Intitule);
                        $obj->save();
                    }
                }
                
                /* Associé l'Attribute dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // suprime en premier
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_shop` WHERE `id_attribute` = '.(int)($id_attribute));
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_shop` (`id_attribute`, `id_shop`) VALUES ('.(int)($id_attribute).','.(int)($id_shop).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
            }
        }
        
        /* la gamme 2 de l'énuméré */
        if ((int)($productattribute->Gamme2) != 0) {
            $id_attribute_group = Db::getInstance()->getValue('
				SELECT `id_attribute_group`
				FROM `'._DB_PREFIX_.'attribute_group`
				WHERE `atoosync_gamme`='.(int)($ProductXML->AR_Gamme2));
            
            /* Créé le groupe d'attribut si il n'existe pas */
            if (!$id_attribute_group) {
                $obj = new AttributeGroup();
                $obj->is_color_group = false;
                $obj->group_type = 'select';
                $obj->name = CreateMultiLangField($ProductXML->AR_Gamme2_Intitule);
                $obj->public_name = CreateMultiLangField($ProductXML->AR_Gamme2_Intitule);
                $obj->add();
                $id_attribute_group = $obj->id;
                
                $sql = 'UPDATE `'._DB_PREFIX_.'attribute_group` SET `atoosync_gamme` = \''.(string)($ProductXML->AR_Gamme2).'\' WHERE `id_attribute_group` = \''.(int)($obj->id).'\'';
                Db::getInstance()->Execute($sql);
            }
            
            if ($id_attribute_group) {
                /* met à jour le nom du groupe d'attribut */
                if (Configuration::get('ATOOSYNC_ATTRIBUTE_GROUP') != 'No') {
                    $obj = new AttributeGroup($id_attribute_group);
                    $obj->name = CreateMultiLangField($ProductXML->AR_Gamme2_Intitule);
                    //$obj->public_name = CreateMultiLangField($ProductXML->AR_Gamme2_Intitule);
                    $obj->save();
                }
                /* Associé l'AttributeGroup dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // suprime en premier
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_group_shop` WHERE `id_attribute_group` = '.(int)($id_attribute_group));
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_group_shop` (`id_attribute_group`, `id_shop`) VALUES ('.(int)($id_attribute_group).','.(int)($id_shop).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
                
                $atoosync_enumere = strtolower(Tools::replaceAccentedChars($productattribute->Gamme2Intitule));
                // recherche en premier l'id_attribute à partir de l'énuméré de Sage
                $sql = 'SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'attribute`
						WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
						AND LOWER(`atoosync_enumere`)=\''.pSQL($atoosync_enumere).'\'';
                $id_attribute = Db::getInstance()->getValue($sql);
                    
                if (!$id_attribute) {
                    $obj = new Attribute();
                    $obj->id_attribute_group = $id_attribute_group;
                    $obj->name = CreateMultiLangField($productattribute->Gamme2Intitule);
                    $obj->add();
                    
                    // $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET
                    // `atoosync_gamme` = '.(int)($productattribute->Gamme2).' ,
                    // `atoosync_enumere` = \''.$productattribute->Gamme2Intitule.'\'
                    // WHERE `id_attribute`='.$obj->id;
                    $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET 
							`atoosync_enumere` = \''.pSQL($atoosync_enumere).'\' 
							WHERE `id_attribute`='.$obj->id;
                    Db::getInstance()->Execute($sql);
                    $id_attribute = $obj->id;
                } else {
                    // met à jour le nom de la valeur
                    if (Configuration::get('ATOOSYNC_ATTRIBUTE_VALUE') != 'No') {
                        $obj = new Attribute($id_attribute);
                        $obj->name = CreateMultiLangField($productattribute->Gamme2Intitule);
                        $obj->save();
                    }
                }
                
                /* Associé l'Attribute dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // suprime en premier
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_shop` WHERE `id_attribute` = '.(int)($id_attribute));
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_shop` (`id_attribute`, `id_shop`) VALUES ('.(int)($id_attribute).','.(int)($id_shop).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
            }
        }
    }
}

/*
 *	Créé les déclinaisons à partir des Conditionnements de SAGE
 */
function CreatePackagings($product, $ProductXML)
{
    if (CustomizePackagings($product, $ProductXML) == true) {
        return 0;
    }
        
    if (Configuration::get('ATOOSYNC_COMBINATION_PACKAGING') != 'Yes') {
        return 0;
    }
        
    // Créé les groupes d'attributs et les attributs du conditionnement
    createProductAttributesPackagings($ProductXML);
    
    // Construit la liste des boutiques de l'article
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $id_shop_list = array();
        $id_shop_list_array = Product::getShopsByProduct((int)($product->id));
        foreach ($id_shop_list_array as $array_shop) {
            $id_shop_list[] = (int)($array_shop['id_shop']);
        }
    }
        
    /* Prépare pour supprimer les attributs qui n'existe plus. */
    $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `atoosync_delete`=1 WHERE `id_product` ='.(int)($product->id);
    Db::getInstance()->Execute($sql);
    
    $id_default_attribute = 0;
    
    // vérifie que le conditionnement par 1 n'existe pas
    $condParUnExiste = false;
    foreach ($ProductXML->Packagings->Packaging as $packaging) {
        if ((float)($packaging->Quantite) == 1) {
            $condParUnExiste = true;
        }
    }
    
    // Créé la déclinaison par unité si activé
    if (Configuration::get('ATOOSYNC_PACKAGING_UNIT') == 'Yes' and $condParUnExiste == false) {
        $unique =  $ProductXML->reference.'_*UNITE*';
    
        $pricedif = 0;
        
        $images = array();
        
        /* Trouve le product_attribute à partir de la clé Atoo-sync */
        $id_product_attribute = Db::getInstance()->getValue("
			SELECT `id_product_attribute`
					FROM `"._DB_PREFIX_."product_attribute`
					WHERE `id_product` = ".(int)($product->id)." 
					AND `atoosync_conditionnement` ='".pSQL($unique)."'");
        
        /* Test si l'attribut est bien lié à l'article
           Sinon il est supprimé. */
        if ($id_product_attribute) {
            $id_product = Db::getInstance()->getValue('
				SELECT `id_product`
				FROM `'._DB_PREFIX_.'product_attribute`
				WHERE `id_product_attribute` ='.$id_product_attribute);
            
            if ((int)($id_product) <> (int)($product->id)) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
                } else {
                    $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
                }
                unset($id_product_attribute);
            }
        }
    
        /* Si l'attribut de l'article n'existe pas */
        if (!$id_product_attribute) {
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $id_product_attribute = $product->addAttribute(
											
                    $pricedif,
                                                                0,
                                                                0,
                                                                0,
                                                                $images,
                                                                (string)($product->reference),
                                                                (string)($product->ean13),
                                                                1,
                                                                '',
                                                                '',
                                                                1,
                                                                $id_shop_list
											
                );

                // Modifie la combinaison une fois créée car tous les champs ne sont pas disponible dans le product->addAttribute()
                if ($id_product_attribute) {
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        StockAvailable::setQuantity($product->id, $id_product_attribute, (float)($product->quantity), (int)($id_shop));
                    }
                }
            } elseif (isPrestaShop14()) {
                $id_product_attribute = $product->addCombinationEntity(
	
                    0,
                                                                        $pricedif,
                                                                        0,
                                                                        0,
                                                                        0,
                                                                        (float)($product->quantity),
                                                                        $images,
                                                                        (string)($product->reference),
                                                                        '',
                                                                        (string)($product->ean13),
                                                                        1,
                                                                        '',
                                                                        ''
	
                );
            } else {
                $id_product_attribute = $product->addCombinationEntity(
	
                    0,
                                                                        $pricedif,
                                                                        0,
                                                                        0,
                                                                        (float)($product->quantity),
                                                                        $images,
                                                                        (string)($product->reference),
                                                                        '',
                                                                        (string)($product->ean13),
                                                                        1,
                                                                        ''
	
                );
            }
        }
                
        /* Si le product attribut existe */
        if ($id_product_attribute) {
            /* Selon la version de PrestaShop, modifie l'attribut */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                // Modifie l'attribut
                $product->updateAttribute(
                    $id_product_attribute,
                                            0,
                                            (float)($pricedif),
                                            0,
                                            0,
                                            0,
                                            $images,
                                            (string)($product->reference),
                                            (string)($product->ean13),
                                            1,
                                            '',
                                            '',
                                            0,
                                            '',
                                            true,
                                            $id_shop_list
                );
                                    
                foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                    StockAvailable::setQuantity($product->id, $id_product_attribute, (float)($product->quantity), (int)($id_shop));
                }
            } elseif (isPrestaShop14()) {
                $product->updateProductAttribute(
                    $id_product_attribute,
                                                    0,
                                                    $pricedif,
                                                    0,
                                                    '',
                                                    0,
                                                    (float)($product->quantity),
                                                    $images,
                                                    (string)($product->reference),
                                                    '',
                                                    (string)($product->ean13),
                                                    1,
                                                    '',
                                                    '',
                                                    1
                );
            } else {
                $product->updateProductAttribute(
	
                    $id_product_attribute,
                                                    0,
                                                    $pricedif,
                                                    0,
                                                    0,
                                                    (float)($product->quantity),
                                                    $images,
                                                    (string)($product->reference),
                                                    '',
                                                    (string)($product->ean13),
                                                    1,
                                                    ''
	
                );
            }
            
            // Supprime les product_attribute_combination du product_attribute existant pour les recréer correctement
            // Permet de refixer correctement les combinaisons
            Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'product_attribute_combination` WHERE `id_product_attribute` = '.(int)($id_product_attribute));

            /* Associe le conditionnement à la combinaison */
            if ((int)($packaging->Conditionnement) !=0) {
                /* Trouve l'attribut du conditionnement 1 */
                $sql ='SELECT `id_attribute_group`
						FROM `'._DB_PREFIX_.'attribute_group`
						WHERE `atoosync_conditionnement`='.(int)($packaging->Conditionnement);
                $id_attribute_group = Db::getInstance()->getValue($sql);
            
                // recherche l'id_attribute à partir de l'énuméré de Sage
                $sql = 'SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'attribute`
						WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
						AND `atoosync_enumere`=\''.$ProductXML->unity.'\'';
                $id_attribute = Db::getInstance()->getValue($sql);
                
                if ($id_attribute) {
                    // Si l'association n'existe pas, elle est créée
                    $sql ='SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'product_attribute_combination`
						WHERE `id_attribute`='.(int)($id_attribute).' AND  `id_product_attribute` = '.(int)($id_product_attribute);
                    $exist = Db::getInstance()->getValue($sql);
                    if (!$exist) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'product_attribute_combination`
						(`id_attribute`, `id_product_attribute`) 
						VALUES  ('.(int)($id_attribute).','.(int)($id_product_attribute).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
            }
                        
            /* Enregistre le code unique dans le product attribut */
            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
					`atoosync_conditionnement` = \''.$unique.'\', 
					`atoosync_conditionnement_qte` = 1,
					`atoosync_delete`=0
					WHERE `id_product_attribute` = '.(int)($id_product_attribute);
            Db::getInstance()->Execute($sql);
            
            // Si le product attribute n'a pas de conbinaisons alors il est supprimé
            $sql ='SELECT count(*) 
					FROM `'._DB_PREFIX_.'product_attribute_combination`
					WHERE `id_product_attribute` = '.(int)($id_product_attribute);
            $count= Db::getInstance()->getValue($sql);
            if ((int)($count) == 0) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $product->deleteAttributeCombination((int)($id_product_attribute));
                } else {
                    $product->deleteAttributeCombinaison((int)($id_product_attribute));
                }
            }
        }
    }
    
    foreach ($ProductXML->Packagings->Packaging as $packaging) {
        if ((string)$packaging->NoEnumere != '*UNITE*' or ((string)$packaging->NoEnumere == '*UNITE*' AND Configuration::get('ATOOSYNC_PACKAGING_UNIT') == 'Yes')) {
            $unique =  $ProductXML->reference.'_'.$packaging->NoEnumere;

            /* Calcul une ecotaxe en fonction de la quantité */
            $ecotaxe =0;
            if ($product->ecotax != 0) {
                $ecotaxe = ((float)$product->ecotax * (float)$packaging->Quantite);
            }
            
            /* Calcul la différence de prix */
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $pricedif = (float)($packaging->PVHT) - (float)($ProductXML->price);
            } else {
                $tax = new Tax(intval($product->id_tax));
                $productTTC =  (float)($product->price) * (1 + ((float)($tax->rate) / 100));
                $priceTTC = (float)($packaging->PVHT) * (1 + ((float)($tax->rate) / 100));
                $pricedif = (float)($priceTTC) - (float)($productTTC);
            }
            $pricedif = round($pricedif, 6);
                
            $pricedif = round($pricedif, 6);
            // si ecotax alors ajoute le montant de l'ecotaxe calculé
            if ($ecotaxe != 0) {
                $pricedif = $pricedif - ((float)$product->ecotax - (float)$ecotaxe);
            }

            /* Calcul la différence de poids */
            $weightdif= 0;
            if (Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes') {
                $weightdif = ((float)($product->weight) * ((float)$packaging->Quantite -1));
            }
            
            $images = array();
            
            // supprime les retours à la ligne de l'ean 
            $packaging->CodeBarre = str_replace (array("\r\n", "\n", "\r"), '', $packaging->CodeBarre);
        
            /* Valide les codes barres */
            if (method_exists('Validate', 'isEan13')) {
                if (!Validate::isEan13((string)($packaging->CodeBarre))) {
                    $packaging->CodeBarre = '';
                }
            }

            /* Trouve le product_attribute à partir de la clé Atoo-sync */
            $id_product_attribute = Db::getInstance()->getValue("
                SELECT `id_product_attribute`
                        FROM `"._DB_PREFIX_."product_attribute`
                        WHERE `id_product` = ".(int)($product->id)." 
                        AND `atoosync_conditionnement` ='".pSQL($unique)."'");
            
            /* Essaye de trouver le product_attribute à partir de la référence */
            if (Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') == 'Yes') {
                if (!$id_product_attribute and (string)($packaging->Reference)<>'') {
                    $id_product_attribute = Db::getInstance()->getValue("
                        SELECT `id_product_attribute`
                                FROM `"._DB_PREFIX_."product_attribute`
                                WHERE 	`id_product` = ".(int)($product->id)." 
                                AND `reference` ='".pSQL($packaging->Reference)."'");
                }
            }
            /* Essaye de trouver le product_attribute à partir du code barre */
            if (Configuration::get('ATOOSYNC_COMBINATION_EAN13') == 'Yes') {
                if (!$id_product_attribute  and (string)($packaging->CodeBarre)<>'') {
                    $id_product_attribute = Db::getInstance()->getValue("
                        SELECT `id_product_attribute`
                                FROM `"._DB_PREFIX_."product_attribute`
                                WHERE 	`id_product` = ".(int)($product->id)." 
                                AND `ean13` ='".pSQL($packaging->CodeBarre)."'");
                }
            }
            /* Test si l'attribut est bien lié à l'article
               Sinon il est supprimé. */
            if ($id_product_attribute) {
                $id_product = Db::getInstance()->getValue('
                    SELECT `id_product`
                    FROM `'._DB_PREFIX_.'product_attribute`
                    WHERE `id_product_attribute` ='.$id_product_attribute);
                
                if ((int)($id_product) <> (int)($product->id)) {
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
                    } else {
                        $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
                    }
                    unset($id_product_attribute);
                }
            }
        
            /* Si l'attribut de l'article n'existe pas */
            if (!$id_product_attribute) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $id_product_attribute = $product->addAttribute(
                                                
                        (float)$pricedif,
                                                                    (float)$weightdif,
                                                                    0,
                                                                    $ecotaxe,
                                                                    $images,
                                                                    (string)($packaging->Reference),
                                                                    (string)($packaging->CodeBarre),
                                                                    0,
                                                                    '',
                                                                    '',
                                                                    1,
                                                                    $id_shop_list
                                                
                    );

                    // Modifie la combinaison une fois créée car tous les champs ne sont pas disponible dans le product->addAttribute()
                    if ($id_product_attribute) {
                        foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                            StockAvailable::setQuantity($product->id, $id_product_attribute, (float)($packaging->Stock), (int)($id_shop));
                        }
                    }
                } elseif (isPrestaShop14()) {
                    $id_product_attribute = $product->addCombinationEntity(
        
                        0,
                                                                            (float)$pricedif,
                                                                            (float)$weightdif,
                                                                            0,
                                                                            0,
                                                                            (float)($packaging->Stock),
                                                                            $images,
                                                                            (string)($packaging->Reference),
                                                                            '',
                                                                            (string)($packaging->CodeBarre),
                                                                            0,
                                                                            '',
                                                                            ''
        
                    );
                } else {
                    $id_product_attribute = $product->addCombinationEntity(
        
                        0,
                                                                            (float)$pricedif,
                                                                            (float)$weightdif,
                                                                            0,
                                                                            (float)($packaging->Stock),
                                                                            $images,
                                                                            (string)($packaging->Reference),
                                                                            '',
                                                                            (string)($packaging->CodeBarre),
                                                                            0,
                                                                            ''
        
                    );
                }
            }
                    
            /* Si le product attribut existe */
            if ($id_product_attribute) {
                /* Selon la version de PrestaShop, modifie l'attribut */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $combination = new Combination($id_product_attribute);
                    
                    // Modifie l'attribut
                    $product->updateAttribute(
                        $id_product_attribute,
                                                0,
                                                (float)($pricedif),
                                                (float)$weightdif,
                                                0,
                                                $ecotaxe,
                                                $images,
                                                (string)($packaging->Reference),
                                                (string)($packaging->CodeBarre),
                                                0,
                                                '',
                                                '',
                                                0,
                                                '',
                                                $combination->default_on,
                                                $id_shop_list
                    );
                                        
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        StockAvailable::setQuantity($product->id, $id_product_attribute, (float)($packaging->Stock), (int)($id_shop));
                    }
                } elseif (isPrestaShop14()) {
                    $product->updateProductAttribute(
                        $id_product_attribute,
                                                        0,
                                                        (float)$pricedif,
                                                        (float)$weightdif,
                                                        '',
                                                        0,
                                                        (float)($packaging->Stock),
                                                        $images,
                                                        (string)($packaging->Reference),
                                                        '',
                                                        (string)($packaging->CodeBarre),
                                                        0,
                                                        '',
                                                        '',
                                                        1
                    );
                } else {
                    $product->updateProductAttribute(
        
                        $id_product_attribute,
                                                        0,
                                                        (float)$pricedif,
                                                        (float)$weightdif,
                                                        0,
                                                        (float)($packaging->Stock),
                                                        $images,
                                                        (string)($packaging->Reference),
                                                        '',
                                                        (string)($packaging->CodeBarre),
                                                        0,
                                                        ''
        
                    );
                }
                
                // Supprime les product_attribute_combination du product_attribute existant pour les recréer correctement
                // Permet de refixer correctement les combinaisons
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'product_attribute_combination` WHERE `id_product_attribute` = '.(int)($id_product_attribute));

                /* Associe le conditionnement à la combinaison */
                if ((int)($packaging->Conditionnement) !=0) {
                    /* Trouve l'attribut du conditionnement 1 */
                    $sql ='SELECT `id_attribute_group`
                            FROM `'._DB_PREFIX_.'attribute_group`
                            WHERE `atoosync_conditionnement`='.(int)($packaging->Conditionnement);
                    $id_attribute_group = Db::getInstance()->getValue($sql);
                
                    // recherche l'id_attribute à partir de l'énuméré de Sage
                    $sql = 'SELECT `id_attribute`
                            FROM `'._DB_PREFIX_.'attribute`
                            WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
                            AND `atoosync_enumere`=\''.$packaging->Enumere.'\'';
                    $id_attribute = Db::getInstance()->getValue($sql);
                    
                    if ($id_attribute) {
                        // Si l'association n'existe pas, elle est créée
                        $sql ='SELECT `id_attribute`
                            FROM `'._DB_PREFIX_.'product_attribute_combination`
                            WHERE `id_attribute`='.(int)($id_attribute).' AND  `id_product_attribute` = '.(int)($id_product_attribute);
                        $exist = Db::getInstance()->getValue($sql);
                        if (!$exist) {
                            $sql = 'INSERT INTO `'._DB_PREFIX_.'product_attribute_combination`
                            (`id_attribute`, `id_product_attribute`) 
                            VALUES  ('.(int)($id_attribute).','.(int)($id_product_attribute).')';
                            Db::getInstance()->Execute($sql);
                        }
                    }
                }
                            
                /* Enregistre le code unique dans le product attribut */
                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
                        `atoosync_conditionnement` = \''.$unique.'\', 
                        `atoosync_conditionnement_qte` = '.(float)($packaging->Quantite).',
                        `atoosync_delete`=0
                        WHERE `id_product_attribute` = '.(int)($id_product_attribute);
                Db::getInstance()->Execute($sql);
                
                // Si le product attribute n'a pas de conbinaisons alors il est supprimé
                $sql ='SELECT count(*) 
                        FROM `'._DB_PREFIX_.'product_attribute_combination`
                        WHERE `id_product_attribute` = '.(int)($id_product_attribute);
                $count= Db::getInstance()->getValue($sql);
                if ((int)($count) == 0) {
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $product->deleteAttributeCombination((int)($id_product_attribute));
                    } else {
                        $product->deleteAttributeCombinaison((int)($id_product_attribute));
                    }
                }
                
                if ($packaging->Principal == 1) {
                    $id_default_attribute = $id_product_attribute;
                }
            }
        }
    }

    /* Supprime les products attributs qui n'existe plus. */
    $sql = 'SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product` = '.(int)($product->id).' AND `atoosync_delete`=1';
    $pas = Db::getInstance()->ExecuteS($sql, true, 0);
    foreach ($pas as $pa) {
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            Shop::setContext(Shop::CONTEXT_ALL);
            $product->deleteAttributeCombination((int)($pa['id_product_attribute']));
        } else {
            $product->deleteAttributeCombinaison((int)($pa['id_product_attribute']));
        }
    }

    // met à jour l'attribut par défaut de l'article
    $product->checkDefaultAttributes();
    if ($id_default_attribute != 0) {
        if (method_exists('Product', 'deleteDefaultAttributes')) {
            $product->deleteDefaultAttributes();
        }
        if (method_exists('Product', 'setDefaultAttribute')) {
            $product->setDefaultAttribute($id_default_attribute);
        }
    } else {
        if (method_exists('Product', 'updateDefaultAttribute')) {
            Product::updateDefaultAttribute($product->id);
        }
    }
            
    if (isPrestaShop13() or isPrestaShop14()) {
        $product->updateQuantityProductWithAttributeQuantity();
    }
    
    // si le conditionnement par défaut existe
}

/*
 *	Créé les attributs associé au conditionnement de l'article
 */
function createProductAttributesPackagings($ProductXML)
{
    
    // Customisation des groupes d'attributs et des attributs de l'article
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeProductAttributes($ProductXML) == true) {
        return 0;
    }
    
    /* pour chaque énuméré */
    foreach ($ProductXML->Packagings->Packaging as $packaging) {
        /* Le nom de l'intitulé du conditionnement */
        if ((string)($packaging->Intitule) != '') {
            $id_attribute_group = Db::getInstance()->getValue('
				SELECT `id_attribute_group`
				FROM `'._DB_PREFIX_.'attribute_group`
				WHERE `atoosync_conditionnement`='.(int)($packaging->Conditionnement));
            
            /* Créé le groupe d'attribut si il n'existe pas */
            if (!$id_attribute_group) {
                $obj = new AttributeGroup();
                $obj->is_color_group = false;
                $obj->group_type = 'select';
                $obj->name = CreateMultiLangField((string)($packaging->Intitule));
                $obj->public_name = CreateMultiLangField((string)($packaging->Intitule));
                $obj->add();
                $id_attribute_group = $obj->id;
                
                $sql = 'UPDATE `'._DB_PREFIX_.'attribute_group` SET `atoosync_conditionnement` ='.(int)($packaging->Conditionnement).' WHERE `id_attribute_group` = \''.(int)($obj->id).'\'';
                Db::getInstance()->Execute($sql);
            
                /* Associé l'AttributeGroup dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // suprime en premier
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_group_shop` WHERE `id_attribute_group` = '.(int)($id_attribute_group));
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_group_shop` (`id_attribute_group`, `id_shop`) VALUES ('.(int)($id_attribute_group).','.(int)($id_shop).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
            }
            if ($id_attribute_group) {
                /* met à jour le nom du groupe d'attribut */
                if (Configuration::get('ATOOSYNC_ATTRIBUTE_GROUP') != 'No') {
                    $obj = new AttributeGroup($id_attribute_group);
                    $obj->name = CreateMultiLangField($packaging->Intitule);
                    //$obj->public_name = CreateMultiLangField($packaging->Intitule);
                    $obj->save();
                }
                                
                // recherche en premier l'id_attribute à partir de l'énuméré du conditionnement de Sage
                $sql = 'SELECT `id_attribute`
						FROM `'._DB_PREFIX_.'attribute`
						WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
						AND `atoosync_enumere`=\''.$packaging->Enumere.'\'';
                $id_attribute = Db::getInstance()->getValue($sql);

                if (!$id_attribute) {
                    $obj = new Attribute();
                    $obj->id_attribute_group = $id_attribute_group;
                    $obj->name = CreateMultiLangField($packaging->Enumere);
                    $obj->add();
                                
                    $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET 
							`atoosync_enumere` = \''.$packaging->Enumere.'\' 
							WHERE `id_attribute`='.$obj->id;
                    Db::getInstance()->Execute($sql);
                    $id_attribute = $obj->id;
                } else {
                    if (Configuration::get('ATOOSYNC_ATTRIBUTE_VALUE') != 'No') {
                        $obj = new Attribute($id_attribute);
                        $obj->name = CreateMultiLangField($packaging->Enumere);
                        $obj->save();
                    }
                }
                
                /* Associé l'Attribute dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // suprime en premier
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_shop` WHERE `id_attribute` = '.(int)($id_attribute));
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_shop` (`id_attribute`, `id_shop`) VALUES ('.(int)($id_attribute).','.(int)($id_shop).')';
                        Db::getInstance()->Execute($sql);
                    }
                }
                
                // créé l'attribut de l'unité du conditionnement si on doit créer les déclinaisons à l'unité
                if (Configuration::get('ATOOSYNC_PACKAGING_UNIT') == 'Yes') {
                    // recherche en premier l'id_attribute à partir de l'énuméré du conditionnement de Sage
                    $sql = 'SELECT `id_attribute`
							FROM `'._DB_PREFIX_.'attribute`
							WHERE `id_attribute_group` = '.(int)($id_attribute_group).'
							AND `atoosync_enumere`=\''.$ProductXML->unity.'\'';
                    $id_attribute = Db::getInstance()->getValue($sql);

                    if (!$id_attribute) {
                        $obj = new Attribute();
                        $obj->id_attribute_group = $id_attribute_group;
                        $obj->name = CreateMultiLangField($ProductXML->unity);
                        $obj->add();
                                    
                        $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET 
								`atoosync_enumere` = \''.$ProductXML->unity.'\' 
								WHERE `id_attribute`='.$obj->id;
                        Db::getInstance()->Execute($sql);
                        $id_attribute = $obj->id;
                    } else {
                        if (Configuration::get('ATOOSYNC_ATTRIBUTE_VALUE') != 'No') {
                            $obj = new Attribute($id_attribute);
                            $obj->name = CreateMultiLangField($ProductXML->unity);
                            $obj->save();
                        }
                    }
                    
                    /* Associé l'Attribute dans toutes les boutiques */
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        // suprime en premier
                        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'attribute_shop` WHERE `id_attribute` = '.(int)($id_attribute));
                        foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                            $sql = 'INSERT INTO `'._DB_PREFIX_.'attribute_shop` (`id_attribute`, `id_shop`) VALUES ('.(int)($id_attribute).','.(int)($id_shop).')';
                            Db::getInstance()->Execute($sql);
                        }
                    }
                }
            }
        }
    }
}
/*
 *	Créé les numéros de série de l'article
 */
function CreateSerials($product, $ProductXML)
{
    /* Non implémenté */
}
/*
 *	Met à jour le prix du produit
 */
function SetProductPrice($xml)
{
    /* Si on ne modifie pas les prix quitte la fonction */
    if (Configuration::get('ATOOSYNC_CHANGE_PRICE') == 'No') {
        return 1;
    }
      
    if (empty($xml)) {
        return 0;
    }

    $ProductXML = LoadXML(Tools::stripslashes($xml));
    if (empty($ProductXML)) {
        return 0;
    }

    // Si la création/modification des prix est surchargé.
    if (CustomizeSetProductPrice($ProductXML) == true) {
        return 1;
    }
    
    // si ecotaxe désactivé alors vide le montant de l'exotaxe
    if ((int)Configuration::getGlobalValue('PS_USE_ECOTAX') == 0) {
        $ProductXML->ecotax = 0;
    }
    
    /*	Recherche la référence
        dans tous les attributs des articles	*/
    $query = 'SELECT `id_product_attribute`, `id_product` 
			  FROM `'._DB_PREFIX_.'product_attribute`
			  WHERE `reference` = \''.pSQL($ProductXML->reference).'\'';
    $attributes = Db::getInstance()->ExecuteS($query, true, 0);
    if ($attributes) {
        foreach ($attributes as $k => $row) {
            $id_product_attribute = (int)($row['id_product_attribute']);
            $id_product = (int)($row['id_product']);
            verifyProductFields($id_product);
            
            $product= new Product((int)$id_product);
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $pricedif = (float)($ProductXML->price) - (float)($product->price);
            } else {
                $tax = new Tax((int)($product->id_tax));
                $productTTC =  (float)($product->price) * (1 + ((float)($tax->rate) / 100));
                $enumereTTC = (float)($ProductXML->price) * (1 + ((float)($tax->rate) / 100));
                $pricedif = (float)($enumereTTC) - (float)($productTTC);
            }
            $pricedif = round($pricedif, 6);
        
            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
					`wholesale_price` = \''.$ProductXML->wholesale_price.'\',  
					`price` = \''.$pricedif.'\'  
					WHERE `id_product_attribute` ='.$id_product_attribute;
            Db::getInstance()->Execute($sql);
            
            /* Met à jour l'attribut dans les boutiques */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
												`wholesale_price` = \''.$ProductXML->fldPAHT.'\',  
												`price` = \''.$pricedif.'\'  
												WHERE `id_product_attribute` ='.$id_product_attribute);
            }
            
            // Créé les prix spécifique de l'article mais en l'associant à la déclinaison
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                CreateSpecificPriceAttribute($product, $id_product_attribute, $ProductXML);
            }
            
            // Appliquer les régles de prix
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                SpecificPriceRule::applyAllRules(array((int)($product->id)));
            }
        }
    }
            
    /*  Recherche la référence
        dans tous les articles	*/
    $query = 'SELECT `id_product` 
			  FROM `'._DB_PREFIX_.'product`
			  WHERE `reference` = \''.pSQL($ProductXML->reference).'\'';
    $products = Db::getInstance()->ExecuteS($query, true, 0);
    if ($products) {
        foreach ($products as $k => $row) {
            $id_product = (int)($row['id_product']);
            verifyProductFields($id_product);
            
            /* Fixe le context par défaut pour l'article */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                Shop::setContext(Shop::CONTEXT_ALL);
            }

            $product= new Product((int)$id_product);
            
            /* selon la configuration on met ou pas à jour le prix de l'article */
            if (!((float)($product->price) == 0 and Configuration::get('ATOOSYNC_IGNORE_PRICE_0') == 'Yes')) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    // renseigne le prix de l'article par défaut
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET 
														`ecotax` = \''.$ProductXML->ecotax.'\',  
														`wholesale_price` = \''.$ProductXML->wholesale_price.'\',  
														`price` = \''.$ProductXML->price.'\',  
														`id_tax_rules_group` = \''.$ProductXML->id_tax.'\'  
														WHERE `id_product` = '.$product->id);
                    
                    // renseigne le prix de l'article sur les boutiques
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET 
														`ecotax` = \''.$ProductXML->ecotax.'\',  
														`wholesale_price` = \''.$ProductXML->wholesale_price.'\',  
														`price` = \''.$ProductXML->price.'\',  
														`id_tax_rules_group` = \''.$ProductXML->id_tax.'\'  
														WHERE `id_product` = '.$product->id);
                                                        
                    // renseigne les prix par boutique
                    foreach ($ProductXML->product_shops->product_shop as $productShop) {
                        $id_shop = (int)($productShop['id_shop']);
                        $id_shop = Db::getInstance()->getValue('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop`='.(int)($id_shop));
                        if ($id_shop) {
                            // renseigne le prix de l'article par défaut
                            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET 
															`ecotax` = '.(float)(max(0, (float)($productShop->product_shop_ecotax))).',  
															`wholesale_price` = '.(float)(max(0, (float)($productShop->product_shop_wholesale_price))).',  
															`price` = '.(float)(max(0, (float)($productShop->product_shop_price))).',  
															`id_tax_rules_group` = '.(int)($productShop->product_shop_id_tax).'  
															WHERE `id_product` = '.$product->id.'
															AND `id_shop` = '.$id_shop);
                        }
                        /*
                        $id_shop = (int)($productShop['id_shop']);
                        $id_shop = Db::getInstance()->getValue('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop`='.(int)($id_shop));
                        if ($id_shop)
                        {
                            Shop::setContext(Shop::CONTEXT_SHOP, (int)($id_shop));
                            //$product_shop = new Product((int)($product->id));
                            $product_shop = new Product((int)($product->id), false, null,(int)($id_shop) );
                            if ($product_shop)
                            {
                                $product_shop->id_tax_rules_group = (int)($productShop->product_shop_id_tax);
                                $product_shop->wholesale_price = (float)(max(0,(float)($productShop->product_shop_wholesale_price)));
                                $product_shop->price = (float)(max(0,(float)($productShop->product_shop_price)));
                                $product_shop->ecotax = (float)(max(0,(float)($productShop->product_shop_ecotax)));

                                if (!$product_shop->update())
                                    echo "Error $product_shop->update()";
                            }
                        }
                        */
                    }
                } else {
                    if (isPrestaShop14()) {
                        $product->id_tax_rules_group = (int)($ProductXML->id_tax);
                    } else {
                        $product->id_tax = (int)($ProductXML->id_tax);
                    }
                
                    $product->wholesale_price = $ProductXML->wholesale_price;
                    $product->price = $ProductXML->price;
                    $product->ecotax = $ProductXML->ecotax;
                
                    /* Enregistre les modifications */
                    if (!$product->update()) {
                        echo "Error $product->update()";
                    }
                }
                
                /* Recharge l'article */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    Shop::setContext(Shop::CONTEXT_ALL);
                }
            
                $product= new Product((int)($row['id_product']));

                /* Les prix spécifiques PS 1.4+ */
                if (Configuration::get('ATOOSYNC_CHANGE_SPPRICES') == 'Yes') {
                    if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        /* La priorité */
                        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'specific_price_priority` WHERE `id_product` = '.(int)($product->id));
                        $priorities = array();
                        array_push($priorities, $ProductXML->specific_price_priority1);
                        array_push($priorities, $ProductXML->specific_price_priority2);
                        array_push($priorities, $ProductXML->specific_price_priority3);
                        array_push($priorities, $ProductXML->specific_price_priority4);
                        SpecificPrice::setSpecificPriority($product->id, $priorities);
                      
                        /* Les prix */
                        CreateSpecificPrice($product, $ProductXML);
                    }
                }
        
                // Si il y a des déclinaisons
                if ($ProductXML->Combinations) {
                    foreach ($ProductXML->Combinations->product_attribute as $productattribute) {
                        $unique =  $ProductXML->reference.'_'.$productattribute->Gamme1.'_'.$productattribute->Gamme2;
                        /*  Calcul la différence de prix */
                        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                            $pricedif = (float)($productattribute->price) - (float)($ProductXML->price);
                        } else {
                            $tax = new Tax(intval($product->id_tax));
                            $productTTC =  (float)($product->price) * (1 + ((float)($tax->rate) / 100));
                            $enumereTTC = (float)($productattribute->price) * (1 + ((float)($tax->rate) / 100));
                            $pricedif = (float)($enumereTTC) - (float)($productTTC);
                        }
                        $pricedif = round($pricedif, 6);
                                            
                        /* Trouve le product_attribute à partir de la clé Atoo-sync */
                        $id_product_attribute = Db::getInstance()->getValue("
							SELECT `id_product_attribute`
									FROM `"._DB_PREFIX_."product_attribute`
									WHERE `id_product` = ".(int)($product->id)." 
									AND `atoosync_gamme` ='".pSQL($unique)."'");
                        
                        /* Essaye de trouver le product_attribute à partir de la référence */
                        if (Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') == 'Yes') {
                            if (!$id_product_attribute and (string)($productattribute->reference)<>'') {
                                $id_product_attribute = Db::getInstance()->getValue("
									SELECT `id_product_attribute`
											FROM `"._DB_PREFIX_."product_attribute`
											WHERE 	`id_product` = ".(int)($product->id)." 
											AND `reference` ='".pSQL($productattribute->reference)."'");
                            }
                        }
                        /* Essaye de trouver le product_attribute à partir du code barre */
                        if (Configuration::get('ATOOSYNC_COMBINATION_EAN13') == 'Yes') {
                            if (!$id_product_attribute  and (string)($productattribute->ean13)<>'') {
                                $id_product_attribute = Db::getInstance()->getValue("
									SELECT `id_product_attribute`
											FROM `"._DB_PREFIX_."product_attribute`
											WHERE 	`id_product` = ".(int)($product->id)." 
											AND `ean13` ='".pSQL($productattribute->ean13)."'");
                            }
                        }
                        // Si l'attribut de l'article existe
                        if ($id_product_attribute) {
                            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
									`wholesale_price` = \''.$productattribute->wholesale_price.'\',  
									`price` = \''.$pricedif.'\',
									`ecotax` = \''.$productattribute->EcoTaxe.'\'
									WHERE `id_product_attribute` ='.(int)($id_product_attribute);
                            Db::getInstance()->Execute($sql);
                        
                            /* Met à jour l'attribut dans les boutiques */
                            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
										`wholesale_price` = \''.$productattribute->wholesale_price.'\',  
										`price` = \''.$pricedif.'\',
										`ecotax` = \''.$productattribute->EcoTaxe.'\'																
										WHERE `id_product_attribute` ='.(int)($id_product_attribute);
                                Db::getInstance()->Execute($sql);
                            }
                            
                            // fixe les prix par boutique si présent
                            if ($productattribute->shops) {
                                foreach ($productattribute->shops->price as $shopPrice) {
                                    $id_shop = (int)($shopPrice['id_shop']);
                                    if ($id_shop > 0 and (float)$shopPrice > 0) {
                                        $price = Db::getInstance()->getValue(" SELECT `price` FROM `"._DB_PREFIX_."product_shop`
                                                                               WHERE `id_product` = ".(int)($product->id)." 
                                                                               AND `id_shop` =".(int) $id_shop);

                                        $pricedif = (float)($shopPrice) - (float)($price);
                                        $pricedif = round($pricedif, 6);
                            
                                        $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
                                            `price` = \''.$pricedif.'\'
                                            WHERE `id_product_attribute` ='.(int)($id_product_attribute).'
                                            AND `id_shop` ='.(int)($id_shop);
                                        Db::getInstance()->Execute($sql);
                                    }
                                }
                            }
                        }
                    }
                }
                    
                // Si il y a du conditionnement
                if ($ProductXML->Packagings) {
                    foreach ($ProductXML->Packagings->Packaging as $packaging) {
                        $unique =  $ProductXML->reference.'_'.$packaging->NoEnumere;
                        $ecotaxe = 0;
                        if ($product->ecotax != 0) {
                            $ecotaxe = ((float)$product->ecotax * (float)$packaging->Quantite);
                        }
                        
                        /* Calcul la différence de prix */
                        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                            $pricedif = (float)($packaging->PVHT) - (float)($ProductXML->price);
                        } else {
                            $tax = new Tax(intval($product->id_tax));
                            $productTTC =  (float)($product->price) * (1 + ((float)($tax->rate) / 100));
                            $priceTTC = (float)($packaging->PVHT) * (1 + ((float)($tax->rate) / 100));
                            $pricedif = (float)($priceTTC) - (float)($productTTC);
                        }
                        $pricedif = round($pricedif, 6);
                        // si ecotax alors ajoute le montant de l'ecotaxe calculé
                        if ($ecotaxe != 0) {
                            $pricedif = $pricedif - ((float)$product->ecotax - (float)$ecotaxe);
                        }
                    
                        /* Trouve le product_attribute à partir de la clé Atoo-sync */
                        $id_product_attribute = Db::getInstance()->getValue("
							SELECT `id_product_attribute`
									FROM `"._DB_PREFIX_."product_attribute`
									WHERE `id_product` = ".(int)($product->id)." 
									AND `atoosync_conditionnement` ='".pSQL($unique)."'");
                        
                        /* Essaye de trouver le product_attribute à partir de la référence */
                        if (Configuration::get('ATOOSYNC_PACKAGING_REFERENCE') == 'Yes') {
                            if (!$id_product_attribute and (string)($packaging->Reference)<>'') {
                                $id_product_attribute = Db::getInstance()->getValue("
									SELECT `id_product_attribute`
											FROM `"._DB_PREFIX_."product_attribute`
											WHERE 	`id_product` = ".(int)($product->id)." 
											AND `reference` ='".pSQL($packaging->Reference)."'");
                            }
                        }
                        /* Essaye de trouver le product_attribute à partir du code barre */
                        if (Configuration::get('ATOOSYNC_PACKAGING_EAN13') == 'Yes') {
                            if (!$id_product_attribute  and (string)($packaging->CodeBarre)<>'') {
                                $id_product_attribute = Db::getInstance()->getValue("
									SELECT `id_product_attribute`
											FROM `"._DB_PREFIX_."product_attribute`
											WHERE 	`id_product` = ".(int)($product->id)." 
											AND `ean13` ='".pSQL($packaging->CodeBarre)."'");
                            }
                        }
                        
                        // Si l'attribut de l'article existe
                        if ($id_product_attribute) {
                            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
									`price` = '.(float)$pricedif.',
									`ecotax` = '.(float)$ecotaxe.'
									WHERE `id_product_attribute` ='.(int)($id_product_attribute);
                            Db::getInstance()->Execute($sql);
                        
                            /* Met à jour l'attribut dans les boutiques */
                            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
										`price` = '.(float)$pricedif.',
										`ecotax` = '.(float)$ecotaxe.'
										WHERE `id_product_attribute` ='.(int)($id_product_attribute);
                                Db::getInstance()->Execute($sql);
                            }
                        
                            //Créer les prix spécifique du conditionnement
                            CreateSpecificPricePackaging($packaging, $product, $id_product_attribute);
                        }
                    }
                }

                /* Réindexe l'article */
                ReIndexProduct($product->id);
                
                /* Customisation du prix du produit */
                CustomizeProductPrice($product, $ProductXML);
            }
            
            // Désactive l'article si le prix est à zéro dans Sage
            if ((float)($ProductXML->price) == 0 and Configuration::get('ATOOSYNC_DISABLE_PRICE_ZERO') == 'Yes') {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `active` = 0 WHERE `id_product` = '.(int)($product->id));
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `active` = 0 WHERE `id_product` = '.(int)($product->id));
                }
            }
            
            // Appliquer les régles de prix
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                SpecificPriceRule::applyAllRules(array((int)($product->id)));
            }
        }
    } else {
        // Si il y a des déclinaisons et que l'article n'a pas été trouvé alors essaye de mettre à jour les déclinaisons
        // en recherchant les déclinaisons par la référence
        if ($ProductXML->Combinations) {
            foreach ($ProductXML->Combinations->product_attribute as $productattribute) {
                if ((string)($productattribute->reference)<>'') {
                    $id_product_attribute = Db::getInstance()->getValue("
                                SELECT `id_product_attribute`
                                FROM `"._DB_PREFIX_."product_attribute`
                                WHERE `reference` ='".pSQL((string)$productattribute->reference)."'");

                    // Si un attribut à été trouvé
                    if ($id_product_attribute) {
                        $id_product = Db::getInstance()->getValue("
                                SELECT `id_product`
                                FROM `"._DB_PREFIX_."product_attribute`
                                WHERE `id_product_attribute` =".(int)$id_product_attribute);
                        
                        verifyProductFields($id_product);
                        
                        $product= new Product((int)$id_product);
                        if ($product) {
                            
                            /*  Calcul la différence de prix */
                            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                                $pricedif = (float)($productattribute->price) - (float)($product->price);
                            } else {
                                $tax = new Tax(intval($product->id_tax));
                                $productTTC =  (float)($product->price) * (1 + ((float)($tax->rate) / 100));
                                $enumereTTC = (float)($productattribute->price) * (1 + ((float)($tax->rate) / 100));
                                $pricedif = (float)($enumereTTC) - (float)($productTTC);
                            }
                            $pricedif = round($pricedif, 6);
                        
                            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET 
                                    `wholesale_price` = \''.$productattribute->wholesale_price.'\',  
                                    `price` = \''.$pricedif.'\',
                                    `ecotax` = \''.$productattribute->EcoTaxe.'\'
                                    WHERE `id_product_attribute` ='.(int)($id_product_attribute);
                            Db::getInstance()->Execute($sql);
                        
                            /* Met à jour l'attribut dans les boutiques */
                            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                                $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET 
                                        `wholesale_price` = \''.$productattribute->wholesale_price.'\',  
                                        `price` = \''.$pricedif.'\',
                                        `ecotax` = \''.$productattribute->EcoTaxe.'\'																
                                        WHERE `id_product_attribute` ='.(int)($id_product_attribute);
                                Db::getInstance()->Execute($sql);
                            }
                        }
                    }
                }
            }
        }
    }
    
    /* Met à jour les prix par clients des articles */
    if (Configuration::get('ATOOSYNC_CHANGE_SPPRICES') == 'Yes') {
        /* Met à jour les prix par clients des articles */
        if ($ProductXML->customersprices) {
            SetCustomersPrices($ProductXML);
        }
    }
}
/*
 *	Met à jour le stock de l'article dans PrestaShop
 */
function SetProductQuantity($xml)
{
    /* Si on ne modifie pas les prix quitte la fonction */
    if (Configuration::get('ATOOSYNC_CHANGE_QUANTITY') == 'No') {
        return 1;
    }
  
    if (empty($xml)) {
        return 0;
    }

    $ProductXML = LoadXML(Tools::stripslashes($xml));
    if (empty($ProductXML)) {
        return 0;
    }

    // Si la création/modification du stock est surchargé.
    if (CustomizeSetProductQuantity($ProductXML) == true) {
        return 1;
    }
    
    // supprime les retours à la ligne de l'ean 
    $ProductXML->ean13 = str_replace (array("\r\n", "\n", "\r"), '', $ProductXML->ean13);
                
    /* Valide le code barre EAN13 */
    if (method_exists('Validate', 'isEan13')) {
        if (!Validate::isEan13((string)($ProductXML->ean13))) {
            if (!Validate::isEan13((string)($ProductXML->ean13))) {
                $ProductXML->ean13 = '';
            }
        }
    }
            
    /*	Met a jour le stock des déclinaisons de l'article */
    if ($ProductXML->Combinations) {
        // Si l'article existe
        $query ='SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($ProductXML->reference).'\'';
        $id_product = Db::getInstance()->getValue($query);
        if ($id_product) {
            verifyProductFields($id_product);
            $product= new Product((int)($id_product));
        
            foreach ($ProductXML->Combinations->product_attribute as $productattribute) {
                // supprime les retours à la ligne des ean ou upc.
                $productattribute->ean13 = str_replace (array("\r\n", "\n", "\r"), '', $productattribute->ean13);
                
                if (method_exists('Validate', 'isEan13')) {
                    if (!Validate::isEan13((string)($productattribute->ean13))) {
                        $productattribute->ean13 = '';
                    }
                }
        
                $unique =  $ProductXML->reference.'_'.$productattribute->Gamme1.'_'.$productattribute->Gamme2;
                
                /* Trouve le product_attribute à partir de la clé Atoo-sync */
                $id_product_attribute = Db::getInstance()->getValue("
					SELECT `id_product_attribute`
							FROM `"._DB_PREFIX_."product_attribute`
							WHERE `id_product` = ".(int)($product->id)." 
							AND `atoosync_gamme` ='".pSQL($unique)."'");
                
                /* Essaye de trouver le product_attribute à partir de la référence */
                if (Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') == 'Yes') {
                    if (!$id_product_attribute and (string)($productattribute->reference)<>'') {
                        $id_product_attribute = Db::getInstance()->getValue("
							SELECT `id_product_attribute`
									FROM `"._DB_PREFIX_."product_attribute`
									WHERE 	`id_product` = ".(int)($product->id)." 
									AND `reference` ='".pSQL($productattribute->reference)."'");
                    }
                }
                /* Essaye de trouver le product_attribute à partir du code barre */
                if (Configuration::get('ATOOSYNC_COMBINATION_EAN13') == 'Yes') {
                    if (!$id_product_attribute  and (string)($productattribute->ean13)<>'') {
                        $id_product_attribute = Db::getInstance()->getValue("
							SELECT `id_product_attribute`
									FROM `"._DB_PREFIX_."product_attribute`
									WHERE 	`id_product` = ".(int)($product->id)." 
									AND `ean13` ='".pSQL($productattribute->ean13)."'");
                    }
                }
                            
                /*	Si l'attribut de l'article créé par Atoo-Sync existe	*/
                if ($id_product_attribute) {
                    $query = 'UPDATE `'._DB_PREFIX_.'product_attribute` 
							SET `quantity` = \''.(int)($productattribute->quantity).'\' 
							WHERE `id_product_attribute` = \''.$id_product_attribute.'\'';
                    Db::getInstance()->Execute($query);
                    
                    /* Met à jour le stock version 1.5 */
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`='.(int)($id_product_attribute));
                        foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                            StockAvailable::setQuantity($id_product, $id_product_attribute, $productattribute->quantity, (int)($id_shop));
                        }
                    }
                    
                    if ($productattribute->next_delivery->date) {
                        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute` SET `available_date` = \''.(string)($productattribute->next_delivery->date).'\' WHERE `id_product_attribute` = '.(int)$id_product_attribute);
                        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET `available_date` = \''.(string)($productattribute->next_delivery->date).'\' WHERE `id_product_attribute` = '.(int)$id_product_attribute);
                    }
                }
            }
            
            // Déclenche le HOOK de mise à jour de l'article
            // dans PrestaShop 1.6 les Hooks sont déclenchés sur les updates.
            if (!isPrestaShop16() and !isPrestaShop17()) {
                if (isPrestaShop15()) {
                    Hook::exec('updateQuantity', array('product' => $product, 'order' => null));
                } else {
                    Hook::updateQuantity($product, null);
                }
            }
        }
    }

    /*	Met a jour le stock des concitionnement de l'article */
    if ($ProductXML->Packagings) {
        // Si l'article existe
        $query ='SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($ProductXML->reference).'\'';
        $id_product = Db::getInstance()->getValue($query);
        if ($id_product) {
            verifyProductFields($id_product);
            $product= new Product((int)($id_product));
        
            foreach ($ProductXML->Packagings->Packaging as $packaging) {
                
                // supprime les retours à la ligne des ean ou upc.
                $packaging->CodeBarre = str_replace (array("\r\n", "\n", "\r"), '', $packaging->CodeBarre);
        
                if (method_exists('Validate', 'isEan13')) {
                    if (!Validate::isEan13((string)($packaging->CodeBarre))) {
                        $packaging->CodeBarre = '';
                    }
                }
        
                $unique =  $ProductXML->reference.'_'.$packaging->NoEnumere;
                
                /* Trouve le product_attribute à partir de la clé Atoo-sync */
                $id_product_attribute = Db::getInstance()->getValue("
					SELECT `id_product_attribute`
							FROM `"._DB_PREFIX_."product_attribute`
							WHERE `id_product` = ".(int)($product->id)." 
							AND `atoosync_conditionnement` ='".pSQL($unique)."'");
                
                /* Essaye de trouver le product_attribute à partir de la référence */
                if (Configuration::get('ATOOSYNC_COMBINATION_REFERENCE') == 'Yes') {
                    if (!$id_product_attribute and (string)($packaging->Reference)<>'') {
                        $id_product_attribute = Db::getInstance()->getValue("
							SELECT `id_product_attribute`
									FROM `"._DB_PREFIX_."product_attribute`
									WHERE 	`id_product` = ".(int)($product->id)." 
									AND `reference` ='".pSQL($packaging->Reference)."'");
                    }
                }
                /* Essaye de trouver le product_attribute à partir du code barre */
                if (Configuration::get('ATOOSYNC_COMBINATION_EAN13') == 'Yes') {
                    if (!$id_product_attribute  and (string)($packaging->CodeBarre)<>'') {
                        $id_product_attribute = Db::getInstance()->getValue("
							SELECT `id_product_attribute`
									FROM `"._DB_PREFIX_."product_attribute`
									WHERE 	`id_product` = ".(int)($product->id)." 
									AND `ean13` ='".pSQL($packaging->CodeBarre)."'");
                    }
                }
                            
                /*	Si l'attribut de l'article créé par Atoo-Sync existe	*/
                if ($id_product_attribute) {
                    $query = 'UPDATE `'._DB_PREFIX_.'product_attribute` 
							SET `quantity` = '.(int)($packaging->Stock).' 
							WHERE `id_product_attribute` = \''.$id_product_attribute.'\'';
                    Db::getInstance()->Execute($query);
                    
                    /* Calcul la différence de poids */
                    if (Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes') {
                        $weightdif = ((float)($product->weight) * ((float)$packaging->Quantite -1));
                        $query = 'UPDATE `'._DB_PREFIX_.'product_attribute` 
							SET `weight` = '.(float)($weightdif).' 
							WHERE `id_product_attribute` = \''.$id_product_attribute.'\'';
                        Db::getInstance()->Execute($query);
                        /* Met à jour dans PrestaShop 1.5 et + */
                        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                            $query = 'UPDATE `'._DB_PREFIX_.'product_attribute_shop` 
							SET `weight` = '.(float)($weightdif).' 
							WHERE `id_product_attribute` = \''.$id_product_attribute.'\'';
                            Db::getInstance()->Execute($query);
                        }
                    }
                        
        
                    /* Met à jour le stock version 1.5 */
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`='.(int)($id_product_attribute));
                        foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                            StockAvailable::setQuantity($id_product, $id_product_attribute, $packaging->Stock, (int)($id_shop));
                        }
                    }
                }
            }
            
            // Déclenche le HOOK de mise à jour de l'article
            // dans PrestaShop 1.6 les Hooks sont déclenchés sur les updates.
            if (!isPrestaShop16() and !isPrestaShop17()) {
                if (isPrestaShop15()) {
                    Hook::exec('updateQuantity', array('product' => $product, 'order' => null));
                } else {
                    Hook::updateQuantity($product, null);
                }
            }
        }
    }
    // Recherche la référence de l'article
    // dans toutes les déclinaisons uniquement si l'article n'a pas de déclinaison venant de Sage
    if (!$ProductXML->Combinations and !$ProductXML->Packagings) {
        $query ='SELECT `id_product_attribute`, `id_product`
				FROM `'._DB_PREFIX_.'product_attribute`
				WHERE `reference` = \''.pSQL($ProductXML->reference).'\'';
        $attributes = Db::getInstance()->ExecuteS($query, true, 0);
        foreach ($attributes as $k => $row) {
            $id_product_attribute = (int)($row['id_product_attribute']);
            $id_product = (int)($row['id_product']);
            verifyProductFields($id_product);
            $product= new Product($id_product);
            
            $weight = (float)$ProductXML->weight - (float)$product->weight;

            $query = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET
					`quantity` = \''.(int)($ProductXML->quantity).'\' , 
					`ean13` = \''.(string)($ProductXML->ean13).'\' 
					WHERE `id_product_attribute` = '.$id_product_attribute;
            Db::getInstance()->Execute($query);
            
            // modifie le poids de la déclinaison
            if (Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes') {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute` SET `weight` = '.(float)$weight.' WHERE `id_product_attribute`='.(int)($id_product_attribute));
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute_shop` SET `weight` = '.(float)$weight.' WHERE `id_product_attribute`='.(int)($id_product_attribute));
            }
    
  
            /* Si PrestaShop 1.5 et 1.6 */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                    StockAvailable::setQuantity($id_product, $id_product_attribute, $ProductXML->quantity, (int)($id_shop));
                }
            
                // renseigne la date de disponibilité de l'article
                if ($ProductXML->next_delivery->date) {
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute` 		SET `available_date` = \''.(string)($ProductXML->next_delivery->date).'\' WHERE `id_product` = '.(int)($id_product).' AND `id_product_attribute`='.(int)($id_product_attribute));
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_attribute_shop` 	SET `available_date` = \''.(string)($ProductXML->next_delivery->date).'\' WHERE `id_product_attribute`='.(int)($id_product_attribute));
                }
            }
            
            // Déclenche le HOOK de mise à jour de l'article
            // dans PrestaShop 1.6 les Hooks sont déclenchés sur les updates.
            if (!isPrestaShop16() and !isPrestaShop17()) {
                if (isPrestaShop15()) {
                    Hook::exec('updateQuantity', array('product' => $product, 'order' => null));
                } else {
                    Hook::updateQuantity($product, null);
                }
            }
        }
    }
    
    /*  Recherche la référence
        dans tous les articles	*/
    $query ='SELECT `id_product`
				FROM `'._DB_PREFIX_.'product`
				WHERE `reference` = \''.pSQL($ProductXML->reference).'\'';
    $products = Db::getInstance()->ExecuteS($query, true, 0);
    foreach ($products as $k => $row) {
        $id_product = (int)($row['id_product']);
        verifyProductFields($id_product);
        
        /* Si PrestaShop 1.5 */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            Shop::setContext(Shop::CONTEXT_ALL);
            $product= new Product($id_product);
            Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET 
										`quantity` = '.(int)(max(0, $ProductXML->quantity)).',
										`ean13` = \''.(string)($ProductXML->ean13).'\'
										WHERE `id_product` = '.(int)($product->id));
            // modifie le poids de l'article
            if (Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes') {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `weight` = '.(float)(max(0, $ProductXML->weight)).' WHERE `id_product` = '.(int)($product->id));
            }
            
                    
            /* pour chaque boutique fixe les quantités si il n'y a pas de déclinaisons*/
            if (!$ProductXML->Combinations) {
                foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                    StockAvailable::setQuantity($product->id, 0, $ProductXML->quantity, (int)($id_shop));
                }
            }
            
            if ($ProductXML->next_delivery->date) {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `available_date` = \''.(string)($ProductXML->next_delivery->date).'\' WHERE `id_product` = '.(int)($product->id));
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `available_date` = \''.(string)($ProductXML->next_delivery->date).'\' WHERE `id_product` = '.(int)($product->id));
            }
            
            /* Selon la config désactive l'article si le stock est = 0 et si l'article est activé */
            $quantity = Product::getQuantity($product->id);
            if ($quantity == 0 and Configuration::get('ATOOSYNC_DISABLE_STOCK_ZERO') == 'Yes') {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `active` = 0 WHERE `id_product` = '.(int)($product->id));
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `active` = 0 WHERE `id_product` = '.(int)($product->id));
            }
            
            // Déclenche le HOOK de mise à jour de l'article
            // dans PrestaShop 1.6 les Hooks sont déclenchés sur les updates.
            if (isPrestaShop15()) {
                Hook::exec('updateQuantity', array('product' => $product, 'order' => null));
            }
                
            /* Affiche le message de réapprovisionnement si Stock réel=0 et Stock à terme>0  */
            if (Configuration::get('ATOOSYNC_DISPLAY_RESTOSCKING') == 'Yes') {
                if (($quantity == 0) and ((int)($ProductXML->AS_Terme) > 0)) {
                    if ($ProductXML->restockings) {
                        foreach ($ProductXML->restockings->restocking as $text) {
                            $tmp = (string)($text);
                            if (Validate::isGenericName($tmp)) {
                                $id_lang = (int)($text['id_lang']);
                                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_lang` SET `available_later` = \''.pSQL($tmp).'\' WHERE `id_lang`= '.$id_lang.' AND `id_product`='.(int)($product->id));
                            }
                        }
                    }
                }
                /* Sinon affiche le message standard */
                else {
                    if ($ProductXML->availablelaters) {
                        foreach ($ProductXML->availablelaters->available_later as $text) {
                            $tmp = (string)($text);
                            if (Validate::isGenericName($tmp)) {
                                $id_lang = (int)($text['id_lang']);
                                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_lang` SET `available_later` = \''.pSQL($tmp).'\' WHERE `id_lang`= '.$id_lang.' AND `id_product`='.(int)($product->id));
                            }
                        }
                    }
                }
            }
        }
        /* Version PrestaShop < 1.5 */
        else {
            $product= new Product($id_product);
            $product->quantity = (int)(max(0, $ProductXML->quantity));
            $product->ean13 = (string)($ProductXML->ean13);
            // modifie le poids de l'article
            if (Configuration::get('ATOOSYNC_CHANGE_WEIGHT') == 'Yes') {
                $product->weight = (float)($ProductXML->weight);
            }
                        
            if ($ProductXML->next_delivery->date) {
                $product->available_date = $ProductXML->next_delivery->date;
            }
            
            if (!$product->update()) {
                echo 'SetProductQuantity() An error occurred while updating Product Stock .';
            }
                
            /* Met à jour le stock selon les quantités (PrestaShop 1.3 & 1.4) */
            if (isPrestaShop13() or isPrestaShop14()) {
                $product->updateQuantityProductWithAttributeQuantity();
            }
        
            /* Selon la config désactive l'article si le stock est = 0 et si l'article est activé */
            $quantity = Product::getQuantity($product->id);
            if ($quantity == 0 and Configuration::get('ATOOSYNC_DISABLE_STOCK_ZERO') == 'Yes' and $product->active == 1) {
                $product->active = 0;
            }
            
            /* Affiche le message de réapprovisionnement si Stock réel=0 et Stock à terme>0  */
            if (Configuration::get('ATOOSYNC_DISPLAY_RESTOSCKING') == 'Yes') {
                if (($quantity == 0) and ((int)($ProductXML->AS_Terme) > 0)) {
                    if ($ProductXML->restockings) {
                        foreach ($ProductXML->restockings->restocking as $text) {
                            $tmp = (string)($text);
                            if (!empty($tmp)) {
                                $product->available_later[(int)($text['id_lang'])] = $tmp;
                            }
                        }
                    }
                }
            } else {
                if ($ProductXML->availablelaters) {
                    foreach ($ProductXML->availablelaters->available_later as $text) {
                        $tmp = (string)($text);
                        if (!empty($tmp)) {
                            $product->available_later[(int)($text['id_lang'])] = $tmp;
                        }
                    }
                }
            }
            
            /* enregistre les modifications */
            if (!$product->update()) {
                echo 'SetProductQuantity() An error occurred while updating Product Stock .';
            }
                
            // Déclenche le HOOK de mise à jour de l'article
            Hook::updateQuantity($product, null);
        }
            
        // Associe l'article aux dépôts
        setProductWarehouses($ProductXML);
            
        /* Réindexe l'article */
        ReIndexProduct($product->id);
    }
  
    /* Customisation du stock du produit */
    CustomizeProductStock($ProductXML);
}
/*
 *
 */
function setProductWarehouses($ProductXML)
{
    if ($ProductXML->warehouses) {
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            if (Configuration::get('ATOOSYNC_WAREHOUSES') == 'shops') {
                setProductStockByShops($ProductXML);
            } else {
                setProductStockByWarehouses($ProductXML);
            }
        }
    }
}
function setProductStockByShops($ProductXML)
{
     /*  Recherche la référence dans tous les articles	*/
    $query ='SELECT `id_product`
    FROM `'._DB_PREFIX_.'product`
    WHERE `reference` = \''.pSQL((string)$ProductXML->reference).'\'';
    $products = Db::getInstance()->ExecuteS($query, true, 0);
    foreach ($products as $row) {
        $id_product = (int)($row['id_product']);
        
        foreach ($ProductXML->warehouses->warehouse as $warehouse) {
            $shop_id = (int)$warehouse->id_warehouse; // lors de l'export Atoo-Sync le champ est le meme que ce soit une boutique ou un dépot
            
            // si il y a des déclinaisons
            if ($warehouse->combinations) {
                foreach ($warehouse->combinations->combination as $combination) {
                    $unique =  (string)$ProductXML->reference.'_'.(int)$combination->AG_No1.'_'.(int)$combination->AG_No2;
                    /* Trouve le product_attribute à partir de la clé Atoo-sync */
                    $id_product_attribute = (int)Db::getInstance()->getValue("
                    SELECT `id_product_attribute`
                        FROM `"._DB_PREFIX_."product_attribute`
                        WHERE `id_product` = ".(int)$id_product." 
                        AND `atoosync_gamme` ='".pSQL($unique)."'");
                    if ($id_product_attribute > 0) {
                        StockAvailable::setQuantity($id_product, $id_product_attribute, (int)$combination->quantity, $shop_id);
                    }    
                }    
            } else {
                StockAvailable::setQuantity($id_product, 0, (int)$warehouse->quantity, $shop_id);
            }
        }
    }
}
function setProductStockByWarehouses($ProductXML)
{
    /*  Recherche la référence
       dans tous les articles	*/
    $query ='SELECT `id_product`
    FROM `'._DB_PREFIX_.'product`
    WHERE `reference` = \''.pSQL((string)$ProductXML->reference).'\'';
    $products = Db::getInstance()->ExecuteS($query, true, 0);
    foreach ($products as $k => $row) {
        $id_product = (int)($row['id_product']);
        $price_te = (float)Db::getInstance()->getValue("SELECT `wholesale_price` FROM `"._DB_PREFIX_."product` WHERE `id_product` = ".(int)$id_product);

        // En premier met les quantités de l'article à zéro dans tous les stocks
        $query = 'UPDATE `'._DB_PREFIX_.'stock` 
          SET 
            `physical_quantity` = 0, 
            `usable_quantity` = 0 
          WHERE `id_product` = '.(int)$id_product;
        Db::getInstance()->Execute($query);
 
        foreach ($ProductXML->warehouses->warehouse as $warehouse) {
            $id_warehouse = (int)$warehouse->id_warehouse;
  
            // si il y a des déclinaisons
            if ($warehouse->combinations) {
                foreach ($warehouse->combinations->combination as $combination) {
                    $unique =  (string)$ProductXML->reference.'_'.(int)$combination->AG_No1.'_'.(int)$combination->AG_No2;
                    /* Trouve le product_attribute à partir de la clé Atoo-sync */
                    $id_product_attribute = Db::getInstance()->getValue("
        SELECT `id_product_attribute`
            FROM `"._DB_PREFIX_."product_attribute`
            WHERE `id_product` = ".(int)$id_product." 
            AND `atoosync_gamme` ='".pSQL($unique)."'");

                    if ($id_product_attribute) {
                        // associe la déclinaison au dépot si besoin
                        $id_warehouse_product_location = Db::getInstance()->getValue(" SELECT `id_warehouse_product_location`
                FROM `"._DB_PREFIX_."warehouse_product_location`
                WHERE `id_warehouse` = ".(int)$id_warehouse." 
                AND`id_product` = ".(int)$id_product." 
                AND `id_product_attribute` =".(int)$id_product_attribute);
                
                        if (!$id_warehouse_product_location) {
                            $wpl = new WarehouseProductLocation();
                            $wpl->id_product = (int)$id_product;
                            $wpl->id_warehouse = (int)$id_warehouse;
                            $wpl->id_product_attribute = (int)$id_product_attribute;
                            $wpl->location = (string)$combination->location;
                            $wpl->save();
                        }
    
                        // Créé ou met à jour la quantité de stock
                        $id_stock = Db::getInstance()->getValue(" SELECT `id_stock`
            FROM `"._DB_PREFIX_."stock`
            WHERE `id_warehouse` = ".(int)$id_warehouse." 
            AND`id_product` = ".(int)$id_product." 
            AND `id_product_attribute` =".(int)$id_product_attribute);
        
                        if (!$id_stock) {
                            $stock = new Stock();
                        } else {
                            $stock = new Stock($id_stock);
                        }
        
                        $stock->id_warehouse = (int)$id_warehouse;
                        $stock->id_product = (int)$id_product;
                        $stock->id_product_attribute = (int)$id_product_attribute;
                        $stock->reference = (string)$ProductXML->reference;
                        $stock->ean13 = (string)$ProductXML->ean13;
                        $stock->physical_quantity = (int)$combination->quantity;
                        $stock->usable_quantity = (int)$combination->quantity;
                        $stock->price_te = $price_te;
                        $stock->save();
                    }
                }
            } else {
                // associe l'article au dépot si besoin
                $id_warehouse_product_location = Db::getInstance()->getValue(" SELECT `id_warehouse_product_location`
            FROM `"._DB_PREFIX_."warehouse_product_location`
            WHERE `id_warehouse` = ".(int)$id_warehouse." 
            AND`id_product` = ".(int)$id_product." 
            AND `id_product_attribute` =0");
            
                if (!$id_warehouse_product_location) {
                    $wpl = new WarehouseProductLocation();
                    $wpl->id_product =$id_product;
                    $wpl->id_warehouse =$id_warehouse;
                    $wpl->id_product_attribute =0;
                    $wpl->location = (string)$warehouse->location;
                    $wpl->save();
                }

                // Créé ou met à jour la quantité de stock
                $id_stock = Db::getInstance()->getValue(" SELECT `id_stock`
            FROM `"._DB_PREFIX_."stock`
            WHERE `id_warehouse` = ".(int)$id_warehouse." 
            AND`id_product` = ".(int)$id_product." 
            AND `id_product_attribute` =0");
        
                if (!$id_stock) {
                    $stock = new Stock();
                } else {
                    $stock = new Stock($id_stock);
                }
    
                $stock->id_warehouse = (int)$id_warehouse;
                $stock->id_product = (int)$id_product;
                $stock->id_product_attribute = 0;
                $stock->reference = (string)$ProductXML->reference;
                $stock->ean13 = (string)$ProductXML->ean13;
                $stock->physical_quantity = (int)$warehouse->quantity;
                $stock->usable_quantity = (int)$warehouse->quantity;
                $stock->price_te = $price_te;
                $stock->save();
            }
        }
        // Synchronise les quantités.
        StockAvailable::synchronize($id_product);

        // force l'article en gestion de stock avancée et depend du stock
        // En premier met les quantités de l'article à zéro dans tous les stocks
        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `advanced_stock_management` = 1 WHERE `id_product` = '.(int)$id_product);
        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `advanced_stock_management` = 1 WHERE `id_product` = '.(int)$id_product);
        //
        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'stock_available` SET `depends_on_stock` = 1 WHERE `id_product` = '.(int)$id_product);
    }
}
/*
 *	Liste les caractéristiques des articles
 */
function GetFeatures()
{
    $id_lang = IdLangDefault();
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $features = Feature::getFeatures($id_lang, false);
    } else {
        $features = Feature::getFeatures($id_lang);
    }
    foreach ($features as $feature) {
        echo $feature['id_feature'].'|'.escapeXMLString($feature['name']).'<br>';
    }

    return 1;
}

/*
 *	Retourne le XML de la caractéristique
 */
function GetFeature($id_feature)
{
    $id_lang = IdLangDefault();
    
    if (!empty($id_feature) and is_numeric($id_feature)) {
        // Entete du XML
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
        
        $feature = new Feature((int)($id_feature));
        $xml .= "<feature>\r\n";
        $xml .= "\t<id>".$feature->id."</id>\r\n";
        $xml .= "\t<default_name>".escapeXMLString($feature->name[$id_lang])."</default_name>\r\n";
        
        /* les noms */
        $xml .= "\t<names>\r\n";
        foreach ($feature->name as $lang => $text) {
            $tmp = html_entity_decode(strip_tags(br2nl($text)), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<text id_lang=\"".$lang."\">".$tmp."</text>\r\n";
        }
        $xml .= "\t</names>\r\n";
        
        /* les valeurs */
        $xml .= "\t<values>\r\n";
        $featureValues = FeatureValue::getFeatureValues($feature->id);
        foreach ($featureValues as $values) {
            $featureValue = new FeatureValue((int)($values['id_feature_value']));
            if ($featureValue->value) {
                $xml .= "\t\t<value>\r\n";
                $xml .= "\t\t\t<id>".$featureValue->id."</id>\r\n";
                $xml .= "\t\t\t<custom>".$featureValue->custom."</custom>\r\n";
                $xml .= "\t\t\t<default_name>".escapeXMLString($featureValue->value[$id_lang])."</default_name>\r\n";
                $xml .= "\t\t\t<names>\r\n";
                
                foreach ($featureValue->value as $lang => $text) {
                    $tmp = html_entity_decode(strip_tags(br2nl($text)), ENT_QUOTES, 'UTF-8');
                    $tmp = escapeXMLString($tmp);
                    $xml .= "\t\t\t\t<text id_lang=\"".$lang."\">".$tmp."</text>\r\n";
                }
                $xml .= "\t\t\t</names>\r\n";
                $xml .= "\t\t</value>\r\n";
            }
        }
        $xml .= "\t</values>\r\n";
        $xml .= "</feature>\r\n";
    }
    header("Content-type: text/xml");
    echo $xml;
    return 1;
}

/*
 *	Créé une valeur d'une caractéristique
 */
function AddFeatureValue($xml)
{
    $xml = Tools::stripslashes($xml);
    $FeatureValueXML = LoadXML($xml);
    if (empty($FeatureValueXML)) {
        return 0;
    }
    if (empty($FeatureValueXML)) {
        return 0;
    }
    
    $return = 0;
        
    $featureValue = new FeatureValue();
    $featureValue->id_feature = (int)($FeatureValueXML->id_feature);
    $featureValue->custom = $FeatureValueXML->custom;
    
    foreach ($FeatureValueXML->values->value as $value) {
        $tmp = (string)($value);
        if (!empty($tmp)) {
            $featureValue->value[(int)($value['id_lang'])] = $tmp;
        }
    }
                
    if ($featureValue->add()) {
        /* Retourne l'id pour Atoo-Sync */
        echo $featureValue->id.'<br>';
        $return = 1;
    
    /* Supprime les valeurs 'Custom' orpheline */
        // $SQL = 'SELECT `id_feature_value` FROM `'._DB_PREFIX_.'feature_value` WHERE `custom` =1 AND `id_feature_value` NOT IN (SELECT id_feature_value FROM `'._DB_PREFIX_.'feature_product`)';
    } else {
        echo 'Error FeatureValue->add()';
        $return = 0;
    }

    return $return;
}

/*
 *	Supprime une valeur d'une caractéristique
 */
function DeleteFeatureValue($id_featurevalue)
{
    $result = 0;
        
    if (!empty($id_featurevalue) and is_numeric($id_featurevalue)) {
        $featureValue = new FeatureValue((int)($id_featurevalue));
        if ($featureValue) {
            $featureValue->delete();
            $result = 1;
        }
    }
        
    return $result;
}

/*
 *	Supprime les caractéristiques de l'article
 */
function DeleteProductFeatures($reference)
{
    if (!empty($reference) and is_string($reference)) {
        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
        if ($id_product) {
            $product = new Product($id_product);
            /* Met l'article dans toutes les boutiques */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $product->id_shop_list = Shop::getCompleteListOfShopsID();
            }
            $product->deleteFeatures();
        }
    }
    return 1;
}

/*
 * Renseigne la catégorie par défaut de l'article.
 */
function SetProductCategoryDefault($reference, $id_category)
{
    if (!empty($reference) and !empty($id_category)) {
        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
        if ($id_product) {
            $id_category = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` ='.(int)($id_category).'');
            if ($id_category) {
                $product = new Product($id_product);
                /* Met l'article dans toutes les boutiques */
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $product->id_shop_list = Shop::getCompleteListOfShopsID();
                }
                $product->id_category_default = (int)($id_category);
                if ($product->update()) {
                    /* Ajoute l'article dans la catégorie par défaut*/
                    AddProductToCategory($reference, $id_category);
                }
            }
        }
    }
    return 1;
}

/*
 * Retourne le XML de l'article
 */
function GetProduct($reference)
{
    $success = 0;
    // Necessaire pour un Bug dans PrestaShop 1.4 ! //
    if (!defined('_PS_BASE_URL_')) {
        define('_PS_BASE_URL_', Tools::getShopDomain(true));
    }
                
    // la langue par défaut
    $IdLang = IdLangDefault();
    
    if (!empty($reference) and is_string($reference)) {

        // Essaye de trouver le id_product selon la référence
        $id_product = Db::getInstance()->getValue('
			SELECT `id_product`
			FROM `'._DB_PREFIX_.'product`
			WHERE `reference` = \''.pSQL($reference).'\'');
        
        if ($id_product) {
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                Shop::setContext(Shop::CONTEXT_ALL);
            }
            
            $product = new Product($id_product);
            
            // lit les données par défaut de l'article
            $p = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'product` WHERE `id_product` ='.(int)($product->id), true, 0);

            $out_of_stock = $p[0]['out_of_stock'];
            if (isPrestaShop15()or isPrestaShop16() or isPrestaShop17()) {
                $out_of_stock = Db::getInstance()->getValue('
						SELECT `out_of_stock`
						FROM `'._DB_PREFIX_.'stock_available`
						WHERE `id_shop` = 1
						AND `id_product` ='.(int)($product->id));
            }
            
            // Entete du XML de l'article
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
            $xml .= "<product>\r\n";
            $xml .= "\t<id>".$product->id."</id>\r\n";
            $xml .= "\t<active>".$p[0]['active']."</active>\r\n";
            $xml .= "\t<reference>".$p[0]['reference']."</reference>\r\n";
            $xml .= "\t<condition>".$p[0]['condition']."</condition>\r\n";
            $xml .= "\t<visibility>".$p[0]['visibility']."</visibility>\r\n";
            $xml .= "\t<available_date>".$p[0]['available_date']."</available_date>\r\n";
            $xml .= "\t<available_for_order>".$p[0]['available_for_order']."</available_for_order>\r\n";
            $xml .= "\t<show_price>".$p[0]['show_price']."</show_price>\r\n";
            $xml .= "\t<online_only>".$p[0]['online_only']."</online_only>\r\n";
            $xml .= "\t<out_of_stock>".$out_of_stock."</out_of_stock>\r\n";
            $xml .= "\t<minimal_quantity>".$p[0]['minimal_quantity']."</minimal_quantity>\r\n";
            $xml .= "\t<additional_shipping_cost>".$p[0]['additional_shipping_cost']."</additional_shipping_cost>\r\n";
            $xml .= "\t<width>".$p[0]['width']."</width>\r\n";
            $xml .= "\t<height>".$p[0]['height']."</height>\r\n";
            $xml .= "\t<depth>".$p[0]['depth']."</depth>\r\n";
            $xml .= "\t<weight>".$p[0]['weight']."</weight>\r\n";
            $xml .= "\t<upc>".$p[0]['upc']."</upc>\r\n";
            $xml .= "\t<ean13>".$p[0]['ean13']."</ean13>\r\n";
            $xml .= "\t<supplier_reference>".$p[0]['supplier_reference']."</supplier_reference>\r\n";
            $xml .= "\t<location>".$p[0]['location']."</location>\r\n";
            $xml .= "\t<id_manufacturer>".$p[0]['id_manufacturer']."</id_manufacturer>\r\n";
            $xml .= "\t<id_supplier>".$p[0]['id_supplier']."</id_supplier>\r\n";
            $link = new Link();
            $xml .= "\t<url>".escapeXMLString($link->getProductLink($product->id))."</url>\r\n";
            
            /* Les textes de l'article */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $langs = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '.$product->id.' ORDER BY `id_shop`,`id_lang`', true, 0);
            } else {
                $langs = Db::getInstance()->ExecuteS('SELECT *, 1 as `id_shop` FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '.$product->id.' ORDER BY `id_lang`', true, 0);
            }
            
            //
            // Les noms de l'article
            $xml .= "\t<names>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['name'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<name id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</name>\r\n";
            }
            $xml .= "\t</names>\r\n";
            //
            // Les résumés de l'article
            $xml .= "\t<descriptionshorts>\r\n";
            foreach ($langs as $lang) {
                $tmp =$lang['description_short'];
                $tmp = '<![CDATA['.$tmp.']]>';
                $xml .= "\t\t<descriptionshort id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</descriptionshort>\r\n";
            }
            $xml .= "\t</descriptionshorts>\r\n";
            //
            // Les descriptions de l'article
            $xml .= "\t<descriptions>\r\n";
            foreach ($langs as $lang) {
                $tmp = $lang['description'];
                $tmp = '<![CDATA['.$tmp.']]>';
                $xml .= "\t\t<description id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</description>\r\n";
            }
            $xml .= "\t</descriptions>\r\n";
            //
            // Les URL simplifié de l'article
            $xml .= "\t<linkrewrites>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['link_rewrite'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<linkrewrite id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</linkrewrite>\r\n";
            }
            $xml .= "\t</linkrewrites>\r\n";
            //
            // Les Balise Title de l'article
            $xml .= "\t<metatitles>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['meta_title'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<metatitle id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</metatitle>\r\n";
            }
            $xml .= "\t</metatitles>\r\n";
            //
            // Les Mots-clefs de l'article
            $xml .= "\t<metakeywords>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['meta_keywords'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<metakeyword id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</metakeyword>\r\n";
            }
            $xml .= "\t</metakeywords>\r\n";
            //
            // Les Méta Descriptions de l'article
            $xml .= "\t<metadescriptions>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['meta_description'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<metadescription id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</metadescription>\r\n";
            }
            $xml .= "\t</metadescriptions>\r\n";
            //
            // Message en Stock
            $xml .= "\t<availablenows>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['available_now'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<availablenow id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</availablenow>\r\n";
            }
            $xml .= "\t</availablenows>\r\n";
            //
            // Message Hors Stock
            $xml .= "\t<availablelaters>\r\n";
            foreach ($langs as $lang) {
                $tmp = html_entity_decode(strip_tags(br2nl($lang['available_later'])), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<availablelater id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</availablelater>\r\n";
            }
            $xml .= "\t</availablelaters>\r\n";
            //
            // Les tags de l'article
            $xml .= "\t<tags>\r\n";
            $productTags = Tag::getProductTags((int)$product->id);
            if ($productTags) {
                foreach ($productTags as $lang => $tags) {
                    $xml .= "\t\t<tag id_lang=\"".$lang."\">";
                    $tmp ='';
                    foreach ($tags as $tagName) {
                        $tmp .= (empty($tmp)) ? escapeXMLString($tagName) : ', '.escapeXMLString($tagName) ;
                    }
                    $xml .= $tmp."</tag>\r\n";
                }
            }
            $xml .= "\t</tags>\r\n";
            //
            // les accessoires de l'article
            $xml .= "\t<accessories>\r\n";
            // modification PrestaShop 1.3
            $accessories = product::getAccessoriesLight($IdLang, $product->id);
            foreach ($accessories as $accessory) {
                if (!empty($accessory['reference'])) {
                    $xml .= "\t\t<accessory>".escapeXMLString($accessory['reference'])."</accessory>\r\n";
                }
            }
            $xml .= "\t</accessories>\r\n";
            //
            // les Catégories de l'article
            $xml .= "\t<category_default>".(int)($product->id_category_default)."</category_default>\r\n";
            $xml .= "\t<categories>\r\n";
            $categories = Db::getInstance()->Executes('SELECT `id_category` FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.(int)($product->id), true, 0);
            foreach ($categories as $cat) {
                $xml .= "\t\t<category>".(int)($cat['id_category'])."</category>\r\n";
            }
            $xml .= "\t</categories>\r\n";

            /* les images de l'article */
            $xml .= "\t<images>\r\n";
            $images = Image::getImages($IdLang, (int)($product->id));
            foreach ($images as $img) {
                $image = new Image((int)($img['id_image']));
                
                /* Selon la gestion des images */
                if (method_exists('Image', 'getExistingImgPath')) {
                    $file = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.jpg';
                } else {
                    // Chemin de l'image
                    $file = _PS_PROD_IMG_DIR_.(int)($image->id_product).'-'.(int)($image->id).'.jpg';
                }
                /* Si l'image existe */
                if (file_exists($file)) {
                    $xml .= "\t\t<image>".$image->id."</image>\r\n";
                }
            }
            $xml .= "\t</images>\r\n";

            /* les documents de l'article */
            $xml .= "\t<attachments>\r\n";
            $attachments = Attachment::getAttachments($IdLang, (int)($product->id));
            foreach ($attachments as $att) {
                $xml .= "\t\t<attachment>".(int)($att['id_attachment'])."</attachment>\r\n";
            }
            $xml .= "\t</attachments>\r\n";

            /* les caractéristiques de l'article */
            $xml .= "\t<featurevalues>\r\n";
            foreach ($product->getFeatures() as $feature) {
                $fv = new FeatureValue((int)($feature['id_feature_value']));
                if (!$fv->custom) {
                    $xml .= "\t\t<feature_value>\r\n";
                    $xml .= "\t\t\t<custom>0</custom>\r\n";
                    $xml .= "\t\t\t<id_feature>".(int)($feature['id_feature'])."</id_feature>\r\n";
                    $xml .= "\t\t\t<id_feature_value>".(int)($feature['id_feature_value'])."</id_feature_value>\r\n";
                    $xml .= "\t\t</feature_value>\r\n";
                } else {
                    $xml .= "\t\t<feature_value>\r\n";
                    $xml .= "\t\t\t<custom>1</custom>\r\n";
                    $xml .= "\t\t\t<id_feature>".(int)($feature['id_feature'])."</id_feature>\r\n";
                    foreach ($fv->value as $lang => $text) {
                        $tmp = html_entity_decode(strip_tags(br2nl($text)), ENT_QUOTES, 'UTF-8');
                        $tmp = escapeXMLString($tmp);
                        $xml .= "\t\t\t<value id_lang=\"".$lang."\">".$tmp."</value>\r\n";
                    }
                    $xml .= "\t\t</feature_value>\r\n";
                }
            }
            $xml .= "\t</featurevalues>\r\n";
            
            /* Les déclinaisons de l'articles */
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product`='.(int)($product->id);
            $attributes = Db::getInstance()->ExecuteS($sql, true, 0);
            if ($attributes) {
                $xml .= "\t<product_attributes>\r\n";
                foreach ($attributes as $att) {
                    // va chercher la référence du fournisseur par défaut
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $att['supplier_reference'] = ProductSupplier::getProductSupplierReference($product->id, (int)$att['id_product_attribute'], $product->id_supplier);
                    }
              
                    $xml .= "\t\t<product_attribute>\r\n";
                    $xml .= "\t\t\t<id_product_attribute>".$att['id_product_attribute']."</id_product_attribute>\r\n";
                    $xml .= "\t\t\t<id_product>".$att['id_product']."</id_product>\r\n";
                    $xml .= "\t\t\t<reference>".escapeXMLString($att['reference'])."</reference>\r\n";
                    $xml .= "\t\t\t<supplier_reference>".escapeXMLString($att['supplier_reference'])."</supplier_reference>\r\n";
                    $xml .= "\t\t\t<location>".escapeXMLString($att['location'])."</location>\r\n";
                    $xml .= "\t\t\t<ean13>".escapeXMLString($att['ean13'])."</ean13>\r\n";
                    $xml .= "\t\t\t<upc>".escapeXMLString($att['upc'])."</upc>\r\n";
                    $xml .= "\t\t\t<wholesale_price>".$att['wholesale_price']."</wholesale_price>\r\n";
                    $xml .= "\t\t\t<price>".$att['price']."</price>\r\n";
                    $xml .= "\t\t\t<ecotax>".$att['ecotax']."</ecotax>\r\n";
                    $xml .= "\t\t\t<weight>".$att['weight']."</weight>\r\n";
                    $xml .= "\t\t\t<unit_price_impact>".$att['unit_price_impact']."</unit_price_impact>\r\n";
                    $xml .= "\t\t\t<default_on>".$att['default_on']."</default_on>\r\n";
                    $xml .= "\t\t\t<minimal_quantity>".$att['minimal_quantity']."</minimal_quantity>\r\n";
                    $xml .= "\t\t\t<available_date>".$att['available_date']."</available_date>\r\n";
                    $xml .= "\t\t\t<atoosync_gamme>".escapeXMLString($att['atoosync_gamme'])."</atoosync_gamme>\r\n";
                    $xml .= "\t\t</product_attribute>\r\n";
                }
                $xml .= "\t</product_attributes>\r\n";
            }
            
            /* les priorités des prix spécifiques */
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $specificPricePriorities = SpecificPrice::getPriority((int)($product->id));
                $index = 0;
                if ($specificPricePriorities[0] == 'id_customer') {
                    $index=1;
                }
                $xml .= "\t<specific_price_priority1>" . $specificPricePriorities[$index]. "</specific_price_priority1>\r\n";
                $xml .= "\t<specific_price_priority2>" . $specificPricePriorities[$index+1]. "</specific_price_priority2>\r\n";
                $xml .= "\t<specific_price_priority3>" . $specificPricePriorities[$index+2]. "</specific_price_priority3>\r\n";
                $xml .= "\t<specific_price_priority4>" . $specificPricePriorities[$index+3]. "</specific_price_priority4>\r\n";
            } else {
                $xml .= "\t<specific_price_priority1>id_shop</specific_price_priority1>\r\n";
                $xml .= "\t<specific_price_priority2>id_currency</specific_price_priority2>\r\n";
                $xml .= "\t<specific_price_priority3>id_country</specific_price_priority3>\r\n";
                $xml .= "\t<specific_price_priority4>id_group</specific_price_priority4>\r\n";
            }
            /* les prix spécifiques de l'article version 1.4.x */
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $specificPrices = SpecificPrice::getByProductId((int)($product->id));
                $xml .= "\t<specificprices>\r\n";
                foreach ($specificPrices as $specificPrice) {
                    if (empty($specificPrice['id_shop'])) {
                        $specificPrice['id_shop'] = 0;
                    }
                    if (empty($specificPrice['id_currency'])) {
                        $specificPrice['id_currency'] = 0;
                    }
                    if (empty($specificPrice['id_country'])) {
                        $specificPrice['id_country'] = 0;
                    }
                    if (empty($specificPrice['id_group'])) {
                        $specificPrice['id_group'] = 0;
                    }
                    if (empty($specificPrice['id_product_attribute'])) {
                        $specificPrice['id_product_attribute'] = 0;
                    }
                    if (empty($specificPrice['id_customer'])) {
                        $specificPrice['id_customer'] = 0;
                    }
                    
                    $attribute_atoosync_key = '';
                    $attribute_reference = '';
                    if ((int)$specificPrice['id_product_attribute'] > 0) {
                        $attribute_atoosync_key =  Db::getInstance()->getValue('SELECT `atoosync_gamme` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute` = '.(int)$specificPrice['id_product_attribute']);
                        $attribute_reference =  Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute` = '.(int)$specificPrice['id_product_attribute']);
                    }
                    
                    if ($specificPrice['id_customer'] == 0) {
                        $atoosync_type =  Db::getInstance()->getValue('SELECT `atoosync_type` FROM `'._DB_PREFIX_.'specific_price` WHERE `id_specific_price` = '.(int)$specificPrice['id_specific_price']);
                                            
                        $xml .= "\t\t<specific_price>\r\n";
                        $xml .= "\t\t\t<id_specific_price>".$specificPrice['id_specific_price']."</id_specific_price>\r\n";
                        $xml .= "\t\t\t<id_shop>".$specificPrice['id_shop']."</id_shop>\r\n";
                        $xml .= "\t\t\t<id_currency>".$specificPrice['id_currency']."</id_currency>\r\n";
                        $xml .= "\t\t\t<id_country>".$specificPrice['id_country']."</id_country>\r\n";
                        $xml .= "\t\t\t<id_group>".$specificPrice['id_group']."</id_group>\r\n";
                        $xml .= "\t\t\t<id_product_attribute>".$specificPrice['id_product_attribute']."</id_product_attribute>\r\n";
                        $xml .= "\t\t\t<price>".$specificPrice['price']."</price>\r\n";
                        $xml .= "\t\t\t<from_quantity>".$specificPrice['from_quantity']."</from_quantity>\r\n";
                        $xml .= "\t\t\t<reduction>".$specificPrice['reduction']."</reduction>\r\n";
                        $xml .= "\t\t\t<reduction_type>".$specificPrice['reduction_type']."</reduction_type>\r\n";
                        $xml .= "\t\t\t<from>".$specificPrice['from']."</from>\r\n";
                        $xml .= "\t\t\t<to>".$specificPrice['to']."</to>\r\n";
                        $xml .= "\t\t\t<atoosync_type>".$atoosync_type."</atoosync_type>\r\n";
                        $xml .= "\t\t\t<attribute_atoosync_key>".escapeXMLString($attribute_atoosync_key)."</attribute_atoosync_key>\r\n";
                        $xml .= "\t\t\t<attribute_reference>".escapeXMLString($attribute_reference)."</attribute_reference>\r\n";
                        $xml .= "\t\t</specific_price>\r\n";
                    }
                }
                $xml .= "\t</specificprices>\r\n";
            }
            
            /* La configuration multi-boutique de l'article */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $xml .= "\t<product_shops>\r\n";
                $product_shops = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'product_shop` WHERE `id_product` = '.(int)($product->id).' ORDER BY `id_shop`', true, 0);
                foreach ($product_shops as $product_shop) {
                    $xml .= "\t\t<product_shop id_shop=\"".$product_shop['id_shop']."\">\r\n";
                    $xml .= "\t\t\t<product_shop_active>".$product_shop['active']."</product_shop_active>\r\n";
                    $xml .= "\t\t\t<product_shop_id_category_default>".$product_shop['id_category_default']."</product_shop_id_category_default>\r\n";
                    $xml .= "\t\t\t<product_shop_id_tax_rules_group>".$product_shop['id_tax_rules_group']."</product_shop_id_tax_rules_group>\r\n";
                    $xml .= "\t\t\t<product_shop_on_sale>".$product_shop['on_sale']."</product_shop_on_sale>\r\n";
                    $xml .= "\t\t\t<product_shop_online_only>".$product_shop['online_only']."</product_shop_online_only>\r\n";
                    $xml .= "\t\t\t<product_shop_ecotax>".$product_shop['ecotax']."</product_shop_ecotax>\r\n";
                    $xml .= "\t\t\t<product_shop_minimal_quantity>".$product_shop['minimal_quantity']."</product_shop_minimal_quantity>\r\n";
                    $xml .= "\t\t\t<product_shop_price>".$product_shop['price']."</product_shop_price>\r\n";
                    $xml .= "\t\t\t<product_shop_wholesale_price>".$product_shop['wholesale_price']."</product_shop_wholesale_price>\r\n";
                    $xml .= "\t\t\t<product_shop_unity>".$product_shop['unity']."</product_shop_unity>\r\n";
                    $xml .= "\t\t\t<product_shop_unit_price_ratio>".$product_shop['unit_price_ratio']."</product_shop_unit_price_ratio>\r\n";
                    $xml .= "\t\t\t<product_shop_additional_shipping_cost>".$product_shop['additional_shipping_cost']."</product_shop_additional_shipping_cost>\r\n";
                    $xml .= "\t\t\t<product_shop_visibility>".$product_shop['visibility']."</product_shop_visibility>\r\n";
                    $xml .= "\t\t\t<product_shop_condition>".$product_shop['condition']."</product_shop_condition>\r\n";
                    $xml .= "\t\t\t<product_shop_available_for_order>".$product_shop['available_for_order']."</product_shop_available_for_order>\r\n";
                    $xml .= "\t\t\t<product_shop_available_date>".$product_shop['available_date']."</product_shop_available_date>\r\n";
                    $xml .= "\t\t\t<product_shop_show_price>".$product_shop['show_price']."</product_shop_show_price>\r\n";
            
                    $out_of_stock = Db::getInstance()->getValue('
								SELECT `out_of_stock`
								FROM `'._DB_PREFIX_.'stock_available`
								WHERE `id_shop` = '.(int)($product_shop['id_shop']).'
								AND `id_product` ='.(int)($product->id));
                    $xml .= "\t\t\t<product_shop_out_of_stock>".$out_of_stock."</product_shop_out_of_stock>\r\n";
                
                    $xml .= "\t\t</product_shop>\r\n";
                }
                $xml .= "\t</product_shops>\r\n";
            }
        
            $xml .= "</product>\r\n";
            
            header("Content-type: text/xml");
            echo $xml;
            $success = 1;
        }
    }
    return $success;
}
/*
 * Retourne le XML des URLS de l'article
 */
function GetProductURL($reference)
{
    $success = 0;
        
    if (!empty($reference) and is_string($reference)) {
        // Essaye de trouver le id_product selon la référence
        $id_product = Db::getInstance()->getValue('
			SELECT `id_product`
			FROM `'._DB_PREFIX_.'product`
			WHERE `reference` = \''.pSQL($reference).'\'');
        
        if ($id_product) {
            $link = new Link();
            echo $link->getProductLink($id_product).'<br>';
            $success = 1;
        }
    }
    return $success;
}
