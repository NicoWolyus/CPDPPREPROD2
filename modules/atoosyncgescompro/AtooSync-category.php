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
    Ajoute ou met à jour les catégories PrestaShop
*/
function AddCategory($xml)
{
    $succes = 1;
    if (empty($xml)) {
        return 0;
        }
    $xml= Tools::stripslashes($xml);
    $CategoryXML = LoadXML($xml);
    if (empty($CategoryXML)) {
        return 0;
    }
    
    // Customisation des catégories
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeAddCategory($CategoryXML) == true) {
        return true;
    }
    
    // La catégorie Home/accueil
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $id_category_home= Configuration::get('PS_HOME_CATEGORY');
    } else {
        $id_category_home = 1;
    }

    
    /* Si la catégorie est différente de HOME */
    if ((int)($CategoryXML->id_category) != $id_category_home) {
        /* Essaye de trouver la catégorie selon l'id PrestaShop */
        $id_category = 0;
        if ((int)($CategoryXML->id_category) != 0) {
            $id_category = Db::getInstance()->getValue('
			SELECT `id_category`
			FROM `'._DB_PREFIX_.'category`
			WHERE `id_category`= '.(int)($CategoryXML->id_category));
        }
        
        /* Si la catégorie n'existe pas recherche pas l'ID Atoo-Sync */
        if ((int)($id_category) == 0) {
            $id_category = Db::getInstance()->getValue('
			SELECT `id_category`
			FROM `'._DB_PREFIX_.'category`
			WHERE `atoosync_id` = \''.pSQL($CategoryXML->atoosync_id).'\'');
        }
    } else {
        // fixe la catégorie à Home pour ne pas la créer
        $id_category = $id_category_home;
    }
    /* Si la catégorie n'existe pas elle est créée */
    if (!$id_category) {
        // Par défaut la catégorie parente est Home
        $id_parent= $id_category_home;
        
        if (!empty($CategoryXML->id_parent)) {
            // Essaye de trouver la catégorie parente
            $id_category_parent = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category`=  \''.(int)($CategoryXML->id_parent).'\'');
            if (!empty($id_category_parent)) {
                $id_parent = $id_category_parent;
            }
        }
        
        /* Fixe le context par défaut pour la nouvelle catégorie */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
                
        $category = new Category();
        $category->active = true;
        $category->id_parent = (int)($id_parent);
        $category->name = CreateMultiLangField((string)$CategoryXML->default_name);
        $category->link_rewrite = CreateMultiLangField(Tools::link_rewrite((string)$CategoryXML->default_name));
        if (Configuration::get('ATOOSYNC_CATEGORY_QUICK_CREATE') == 'Yes') {
            $category->doNotRegenerateNTree = true;
        }
        
    
        // Créé la catégorie
        if ($category->add() == true) {
            $id_category = (int)($category->id);
        } else {
            echo 'Error $category->add()';
            unset($category);
            unset($id_category);
            $succes = 0;
        }
    }

    /* Renseigne les données de la catégorie */
    if ($id_category) {
        /* Créé la catégorie en premier dans toutes les boutiques, pour pouvoir les modifier */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $name = (string)$CategoryXML->default_name;
            $link = Tools::link_rewrite($name);
                
            $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'category_lang`(`id_category`, `id_shop`, `id_lang`, `name`, `link_rewrite` ) 
                    SELECT '.(int)$id_category.', id_shop, id_lang, \''.psql($name).'\',\''.psql($link).'\' from ps_shop, ps_lang';

            Db::getInstance()->execute($sql);
        }
        
        
        $category= new Category((int)($id_category));
        // met à jour la catégorie avec le code Atoo-Sync
        $query= 'UPDATE `'._DB_PREFIX_.'category` SET
					`atoosync` = \'1\',
					`atoosync_id` = \''.pSQL($CategoryXML->atoosync_id).'\'
					WHERE `id_category`= \''.(int)($category->id).'\'';
        Db::getInstance()->Execute($query);
        
        /* Si la catégorie est modifiable */
                        
        /* Modifie la catégorie si différent de HOME */
        if ($category->id != $id_category_home) {
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $category->position = (int)($CategoryXML->position);
            } else { // en PS1.4 la position commence à zéro, donc retire 1
                $category->position = (int)($CategoryXML->position) -1;
            }
            $category->active = (int)($CategoryXML->active);
            
            if (Configuration::get('ATOOSYNC_CATEGORY_QUICK_CREATE') == 'Yes') {
                $category->doNotRegenerateNTree = true;
            }
                            
            /* Modifie la catégorie parente */
            if (!empty($CategoryXML->id_parent)) {
                $id_parent = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category`=  \''.(int)($CategoryXML->id_parent).'\'');
                if (!empty($id_parent)) {
                    $category->id_parent = (int)($id_parent);
                }
            }
            
            /* Catégorie racine et boutique par défaut */
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $category->is_root_category = (int)($CategoryXML->is_root_category);
                $id_shop = Db::getInstance()->getValue('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop`='.(int)($CategoryXML->id_shop_default));
                if ($id_shop) {
                    $category->id_shop_default = (int)($CategoryXML->id_shop_default);
                }
            }
                    
            /*  Supprime l'image de la catégorie car elle est réenvoyée par Atoo-Sync */
            if (method_exists('Category', 'deleteImage')) {
                $category->deleteImage(true);
            } else {
                deleteImage((int)($category->id));
            }
            
            /* Enregistre les modifications de la catégorie */
            if (!$category->update()) {
                echo 'An error occurred while updating the category.';
                $succes = 0;
            }
            
                        
            /* Modifie les textes pour les versions < PrestaShop 1.5 */
            if (!isPrestaShop15() and !isPrestaShop16() and !isPrestaShop17()) {
                /* les noms */
                foreach ($CategoryXML->names->name as $text) {
                    $tmp = (string)($text);
                    if (!empty($tmp)) {
                        $category->name[(int)($text['id_lang'])] = $tmp;
                    }
                }
                
                /* les descriptions */
                foreach ($CategoryXML->descriptions->description as $text) {
                    $category->description[(int)($text['id_lang'])] = (string)($text);
                }
                /* les urls simplifié */
                foreach ($CategoryXML->linkrewrites->link_rewrite as $text) {
                    $tmp = (string)($text);
                    if (!empty($tmp)) {
                        $category->link_rewrite[(int)($text['id_lang'])] = Tools::link_rewrite($tmp);
                    }
                }
                /* les balises Titre*/
                foreach ($CategoryXML->metatitles->meta_title as $text) {
                    $category->meta_title[(int)($text['id_lang'])] = (string)($text);
                }
                /* les Mots-clefs*/
                foreach ($CategoryXML->metakeywords->meta_keywords as $text) {
                    $category->meta_keywords[(int)($text['id_lang'])] = (string)($text);
                }
                /* les Meta Descriptions */
                foreach ($CategoryXML->metadescriptions->meta_description as $text) {
                    $category->meta_description[(int)($text['id_lang'])] = (string)($text);
                }
                
                /* Enregistre les modifications */
                if (!$category->update()) {
                    echo 'An error occurred while updating the category.';
                    $succes = 0;
                }
            } else /* Met à jour les données des boutiques de la catégorie PS 1.5 */
            {
                /* Met à jour les boutiques de la catégorie */
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'category_shop` WHERE `id_category` = '.(int)($category->id));
                
                Shop::setContext(Shop::CONTEXT_ALL);
                foreach ($CategoryXML->shops_langs->shop_lang as $shop_lang) {
                    $id_shop = (int)($shop_lang['id_shop']);
                    if ($id_shop) {
                        Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
                        /* Instancie la catégorie dans la boutique */
                        $category_lang = new Category((int)$category->id);
                        if ($category_lang) {
                            $category_lang->doNotRegenerateNTree = true;
                            
                            /* Les noms */
                            foreach ($shop_lang->shop_lang_name as $name) {
                                $tmp = (string)($name);
                                if (!empty($tmp)) {
                                    $category_lang->name[(int)($name['id_lang'])] = $tmp;
                                }
                            }
                            /* Les descriptions */
                            foreach ($shop_lang->shop_lang_description as $description) {
                                $tmp = (string)($description);
                                $category_lang->description[(int)($description['id_lang'])] = $tmp;
                            }
                            /* les urls simplifié */
                            foreach ($shop_lang->shop_lang_link_rewrite as $link_rewrite) {
                                $tmp = (string)($link_rewrite);
                                if (!empty($tmp)) {
                                    $category_lang->link_rewrite[(int)($link_rewrite['id_lang'])] = Tools::link_rewrite($tmp);
                                }
                            }
                            /* les balises Titre*/
                            foreach ($shop_lang->shop_lang_meta_title as $meta_title) {
                                $tmp = (string)($meta_title);
                                $category_lang->meta_title[(int)($meta_title['id_lang'])] = $tmp;
                            }
                            /* les Mots-clefs*/
                            foreach ($shop_lang->shop_lang_meta_keywords as $meta_keywords) {
                                $tmp = (string)($meta_keywords);
                                $category_lang->meta_keywords[(int)($meta_keywords['id_lang'])] = $tmp;
                            }
                            /* les Meta Descriptions */
                            foreach ($shop_lang->shop_lang_meta_description as $meta_description) {
                                $tmp = (string)($meta_description);
                                $category_lang->meta_description[(int)($meta_description['id_lang'])] = $tmp;
                            }
                        
                            /* Enregistre les modifications */
                            if (!$category_lang->update(true)) {
                                echo 'An error occurred while updating shop langs for the category.';
                                $succes = 0;
                            }
                        }
                    }
                }

                /* Met à jour les boutiques de la catégorie */
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'category_shop` WHERE `id_category` = '.(int)($category->id));
                foreach ($CategoryXML->shops->shop as $id_shop) {
                    $category->addShop($id_shop);
                    
                    // Fixe la position de la catégorie
                    $sql = ' UPDATE `'._DB_PREFIX_.'category_shop` SET `position` = '.(int)($id_shop['position']).' WHERE `id_category` = '.(int)($category->id).' AND `id_shop` = '.(int)($id_shop);
                    Db::getInstance()->Execute($sql);
                }
            }
        }
        
        /* Supprime les langues de la catégorie qui n'existe plus*/
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $sql = 'DELETE FROM `'._DB_PREFIX_.'category_lang` 
                    WHERE id_category = '.(int)$category->id.' 
                    AND id_shop NOT IN (SELECT id_shop 
                                        FROM '._DB_PREFIX_.'category_shop 
                                        WHERE id_category = '.(int)$category->id.'
                                        )';

            Db::getInstance()->execute($sql);
        }
        
        /* Met à jour les groupes de la catégorie */
        $category->cleanGroups();
        $groups = array();
        foreach ($CategoryXML->groups->group as $group) {
            $id_group = Db::getInstance()->getValue('SELECT `id_group` FROM `'._DB_PREFIX_.'group` WHERE `id_group` =\''.pSQL((string)($group)).'\'');
            if ($id_group) {
                array_push($groups, $id_group);
            }
        }
        if (empty($groups)) {
            $groups[]= 1;
        }
        $category->addGroups($groups);
                    
        /* Met à jour les articles de la catégorie */
        if (Configuration::get('ATOOSYNC_PRODUCT_IGNORE_POS') != 'Yes') {
            Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_category` = '.(int)($category->id));
            foreach ($CategoryXML->products->product as $product) {
                $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($product)).'\'');
                if ($id_product) {
                    Db::getInstance()->Execute('
						INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`)
						VALUES ('.(int)($category->id).','.(int)($id_product).','.(int)($product['position']).')');
                }
            }
        }
        
        /* Met à jour les réductions de groupes de la catégorie PS 1.4/1.5 */
        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            if (method_exists('GroupReduction', 'deleteCategory')) {
                GroupReduction::deleteCategory($category->id);
            } else {
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'group_reduction` WHERE `id_category` = '.(int)($category->id));
            }
                
            foreach ($CategoryXML->reductions->reduction as $reduction) {
                $id_group = Db::getInstance()->getValue('SELECT `id_group` FROM `'._DB_PREFIX_.'group` WHERE `id_group` =\''.pSQL((string)($reduction)).'\'');
                if ($id_group) {
                    $groupReduction = new GroupReduction();
                    $groupReduction->id_category = (int)($category->id);
                    $groupReduction->id_group = (int)($id_group);
                    $groupReduction->reduction = (float)($reduction['percent']) / 100;
                    $groupReduction->add();
                }
            }
        }
        
        /* Met à jour les positions des sous-catégories PrestaShop 1.4  */
        if (isPrestaShop14()) {
            foreach ($CategoryXML->categories->category as $subcategory) {
                $id_subcategory = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` = '.(int)($subcategory));
                if ($id_subcategory) {
                    $position = (int)($subcategory['position']) -1;
                    $sql = ' UPDATE `'._DB_PREFIX_.'category` SET `position` = '.$position.' WHERE `id_parent` = '.(int)($category->id).' AND `id_category` = '.(int)($id_subcategory);
                    Db::getInstance()->Execute($sql);
                }
            }
        }
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            /* Met à jour les positions des sous catégories */
            /* Supprime en premier l'association des sous catégories existante avec les boutiques. */
            $query = 'SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_parent` = '.(int)$category->id;
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
            foreach ($result as $row) {
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'category_shop` WHERE `id_category` = '.(int)($row['id_category']));
            }

            /* Recréer l'association avec les nouvelles sous catégories*/
            foreach ($CategoryXML->shops_categories->shop_categories as $shop_categories) {
                $id_shop = (int)($shop_categories['id_shop']);
                if ($id_shop) {
                    // Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
                    $categories = array();
                    foreach ($shop_categories->shop_category as $shop_category) {
                        $position = (int)($shop_category['position']);
                        $id = (int)($shop_category);
                        
                        // Créé l'association boutique / catégories / position
                        Db::getInstance()->execute('
						INSERT INTO `'._DB_PREFIX_.'category_shop` (`id_category`, `id_shop`, `position`) VALUES
						('.(int)$id.', '.(int)$id_shop.', '.(int)$position.')
						ON DUPLICATE KEY UPDATE `position` = '.(int)$position);
                    }
                }
            }
        }
        
        /* Réorganise la position des catégories */
        if (method_exists('Category', 'cleanPositions')) {
            $category->cleanPositions((int)($category->id));
        }
                
        /* Recalcul le niveau de la catégorie */
        if (method_exists('Category', 'recalculateLevelDepth')) {
            if (Configuration::get('ATOOSYNC_CATEGORY_QUICK_CREATE') == 'No') {
                $category->recalculateLevelDepth($category->id);
            }
        }
        
        /* Retourne les infos dans Atoo-Sync */
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
        $xml .= "<category>";
        $xml .= "<id_category>".$category->id."</id_category>";
        $xml .= "<level_depth>".$category->level_depth."</level_depth>";
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            /* Trouve la position pour la premiere boutique */
        
            $position = Db::getInstance()->getValue('SELECT `position` FROM `'._DB_PREFIX_.'category_shop` WHERE `id_shop`=1 AND `id_category` ='.(int)($category->id));
            $xml .= "<position>".$position."</position>";
            $xml .= "<nleft>".$category->nleft."</nleft>";
            $xml .= "<nright>".$category->nright."</nright>";
            $xml .= "\t<shops>\r\n";
            $result = Db::getInstance()->ExecuteS('SELECT `id_shop`,`position` FROM `'._DB_PREFIX_.'category_shop` WHERE `id_category` = '.$category->id);
            foreach ($result as $shop) {
                $xml .= "\t\t<shop position=\"".(int)($shop['position'])."\">".$shop['id_shop']."</shop>\r\n";
            }
            $xml .= "\t</shops>\r\n";
        } elseif (isPrestaShop14()) {
            $xml .= "<position>".$category->position."</position>";
            $xml .= "<nleft>".$category->nleft."</nleft>";
            $xml .= "<nright>".$category->nright."</nright>";
        } else {
            $xml .= "<position>0</position>";
            $xml .= "<nleft>1</nleft>";
            $xml .= "<nright>1</nright>";
        }
        $xml .= "</category>";
        header("Content-type: text/xml");
        echo $xml;
    }
    return $succes;
}
/*
    Supprime la catégorie
*/
function DeleteCategory($id_category)
{
    if (!empty($id_category) and is_numeric($id_category)) {
        $id= Db::getInstance()->getValue('
				SELECT `id_category`
				FROM `'._DB_PREFIX_.'category`
				WHERE `id_category`= '.(int)($id_category));
        
        if ($id) {
            $category = new Category((int)($id));
            
            /*  Déplace les articles avec cette catégorie par défaut dans la catégorie parent */
            $result = Db::getInstance()->ExecuteS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `id_category_default` = '.$category->id);
            foreach ($result as $p) {
                $product = new Product((int)$p['id_product']);
                $product->id_category_default = (int)($category->id_parent);
                if ($product->update()) {
                    /* Ajoute l'article dans la catégorie par défaut*/
                    $exist = (int)(Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.'category_product` WHERE `id_category`='.(int)($category->id_parent).' AND `id_product`='.(int)($product->id)));
                    if ($exist == 0) {
                        $position = Db::getInstance()->getValue('SELECT MAX(position)+1 FROM `'._DB_PREFIX_.'category_product` WHERE `id_category` ='.(int)($category->id_parent));
                        Db::getInstance()->Execute('
							INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`)
							VALUES ('.(int)($category->id_parent).','.(int)($product->id).','.(int)($position).')');
                    }
                }
            }
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                Shop::setContext(Shop::CONTEXT_ALL);
                $category->cleanGroups();
            }
            
            if (Configuration::get('ATOOSYNC_CATEGORY_QUICK_CREATE') == 'Yes') {
                $category->doNotRegenerateNTree = true;
            } else {
                $category->doNotRegenerateNTree = false;
            }
            
            /* Supprime la catégorie */
            $category->delete();
        }
    }
    return 1;
}
/*
    Liste les catégories des articles
*/
function GetCategories()
{
    $query = 'SELECT `id_category` FROM `'._DB_PREFIX_.'category` ORDER BY `level_depth` ASC';
    if (isPrestaShop14()) {
        $query .= ', `position` ASC';
    }

    $categories = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($query);
    foreach ($categories as $category) {
        echo $category['id_category'].'<br>';
    }

    return 1;
}
/*
    Retourne la catégorie Root de PrestaShop 1.5
*/
function GetRootCategory()
{
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        echo Configuration::get('PS_ROOT_CATEGORY').'<br>';
    } else {
        echo '1<br>';
    }
    return 1;
}
/*
    Retourne la catégorie Accueil de PrestaShop 1.5
*/
function GetHomeCategory()
{
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        echo Configuration::get('PS_HOME_CATEGORY').'<br>';
    } else {
        echo '1<br>';
    }
    return 1;
}
/*
    Retourne le XML de la catégorie
*/
function GetCategory($id_category)
{
    $id_lang = IdLangDefault();
    
    if (!empty($id_category) and is_numeric($id_category)) {
        // Entete du XML
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
        $xml .= "<?xml-stylesheet href=\"fake.xsl\" type=\"text/xsl\"?>\r\n";
        $category = new Category((int)($id_category));
        $atoosync_id = Db::getInstance()->getValue('SELECT `atoosync_id` FROM `'._DB_PREFIX_.'category` WHERE `id_category` ='.(int)($category->id));
        
        $default_name = Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'category_lang` 
                                                                    WHERE `id_category` ='.(int)($category->id).' 
                                                                    AND `id_shop` ='.(int)($category->id_shop_default).' 
                                                                    AND `id_lang` ='.(int)($id_lang));
        
        /* Compatibilité pour PrestaShop < 1.4 */
        $position  	= ((int)($category->position)) 	? $category->position 	: 0;
        $nleft  	= ((int)($category->nleft)) 	? $category->nleft 		: 0;
        $nright  	= ((int)($category->nright)) 	? $category->nright 	: 0;
        
        
        $xml .= "<category>\r\n";
        $xml .= "\t<id>".$category->id."</id>\r\n";
        $xml .= "\t<atoosync_id>".$atoosync_id."</atoosync_id>\r\n";
        $xml .= "\t<id_category>".$category->id."</id_category>\r\n";
        $xml .= "\t<active>".$category->active."</active>\r\n";
        $xml .= "\t<position>".$category->position."</position>\r\n";
        $xml .= "\t<id_parent>".$category->id_parent."</id_parent>\r\n";
        $xml .= "\t<level_depth>".$category->level_depth."</level_depth>\r\n";
        $xml .= "\t<nleft>".$category->nleft."</nleft>\r\n";
        $xml .= "\t<nright>".$category->nright."</nright>\r\n";
        $xml .= "\t<default_name>".escapeXMLString($default_name)."</default_name>\r\n";
        $xml .= "\t<id_image>".$category->id_image."</id_image>\r\n";
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $xml .= "\t<is_root_category>".$category->is_root_category."</is_root_category>\r\n";
        } else {
            $xml .= "\t<is_root_category>0</is_root_category>\r\n";
        }
            
        /* Les textes de la catégorie */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $langs = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'category_lang` WHERE `id_category` = '.$category->id.' ORDER BY `id_shop`,`id_lang`');
        } else {
            $langs = Db::getInstance()->ExecuteS('SELECT *, 1 as `id_shop` FROM `'._DB_PREFIX_.'category_lang` WHERE `id_category` = '.$category->id.' ORDER BY `id_lang`');
        }

        /* les noms */
        $xml .= "\t<names>\r\n";
        foreach ($langs as $lang) {
            $tmp = html_entity_decode(strip_tags(br2nl($lang['name'])), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<name id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</name>\r\n";
        }
        $xml .= "\t</names>\r\n";
        
        /* les descriptions */
        $xml .= "\t<descriptions>\r\n";
        foreach ($langs as $lang) {
            $tmp = $lang['description'];
            $tmp = '<![CDATA['.$tmp.']]>';
            $xml .= "\t\t<description id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</description>\r\n";
        }
        $xml .= "\t</descriptions>\r\n";
        
        /* les link_rewrite */
        $xml .= "\t<linkrewrites>\r\n";
        foreach ($langs as $lang) {
            $tmp = html_entity_decode(strip_tags(br2nl($lang['link_rewrite'])), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<link_rewrite id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</link_rewrite>\r\n";
        }
        $xml .= "\t</linkrewrites>\r\n";

        /* les meta_title */
        $xml .= "\t<metatitles>\r\n";
        foreach ($langs as $lang) {
            $tmp = html_entity_decode(strip_tags(br2nl($lang['meta_title'])), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<meta_title id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</meta_title>\r\n";
        }
        $xml .= "\t</metatitles>\r\n";

        /* les meta_keywords */
        $xml .= "\t<metakeywords>\r\n";
        foreach ($langs as $lang) {
            $tmp = html_entity_decode(strip_tags(br2nl($lang['meta_keywords'])), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<meta_keywords id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</meta_keywords>\r\n";
        }
        $xml .= "\t</metakeywords>\r\n";

        /* les meta_description */
        $xml .= "\t<metadescriptions>\r\n";
        foreach ($langs as $lang) {
            $tmp = html_entity_decode(strip_tags(br2nl($lang['meta_description'])), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<meta_description id_shop=\"".$lang['id_shop']."\" id_lang=\"".$lang['id_lang']."\">".$tmp."</meta_description>\r\n";
        }
        $xml .= "\t</metadescriptions>\r\n";
        
        /* Les groupes de la catégorie */
        $xml .= "\t<groups>\r\n";
        foreach ($category->getGroups() as $group) {
            $xml .= "\t\t<group>".$group."</group>\r\n";
        }
        $xml .= "\t</groups>\r\n";
        
        /* Les réductions de groupes de la catégorie PS 1.4/1.5 */
        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $xml .= "\t<reductions>\r\n";
            
            $GroupReductions = Db::getInstance()->ExecuteS('SELECT `id_group`, `reduction` 
														FROM `'._DB_PREFIX_.'group_reduction` 
														WHERE `id_category` = '.(int)$category->id);
            if ($GroupReductions) {
                foreach ($GroupReductions as $gr) {
                    $xml .= "\t\t<reduction percent=\"".(float)($gr['reduction'] * 100)."\" >".$gr['id_group']."</reduction>\r\n";
                }
            }
            $xml .= "\t</reductions>\r\n";
        }
        
        /* Les sous-catégories */
        $categories = Db::getInstance()->ExecuteS('SELECT `id_category`, `position` FROM `'._DB_PREFIX_.'category` WHERE `id_parent` = '.(int)$category->id);
        $xml .= "\t<categories>\r\n";
        if (!isPrestaShop15() and !isPrestaShop16() and !isPrestaShop17()) {
            foreach ($categories as $subcategory) {
                $xml .= "\t\t<category id_shop=\"1\" position=\"".(int)($subcategory['position'])."\">".$subcategory['id_category']."</category>\r\n";
            }
        }	/* en PrestaShop 1.5 */
        else {
            foreach ($categories as $subcategory) {
                $categoryshops = Db::getInstance()->ExecuteS('SELECT `id_shop`, `position` FROM `'._DB_PREFIX_.'category_shop` WHERE `id_category` = '.(int)($subcategory['id_category']));
                foreach ($categoryshops as $categoryshop) {
                    $xml .= "\t\t<category id_shop=\"".(int)($categoryshop['id_shop'])."\" position=\"".(int)($categoryshop['position'])."\">".$subcategory['id_category']."</category>\r\n";
                }
            }
        }
        $xml .= "\t</categories>\r\n";
        
        /* Les boutiques de la catégorie */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $xml .= "\t<shops>\r\n";
            $result = Db::getInstance()->ExecuteS('SELECT `id_shop`,`position` FROM `'._DB_PREFIX_.'category_shop` WHERE `id_category` = '.$category->id);
            foreach ($result as $shop) {
                $xml .= "\t\t<shop position=\"".(int)($shop['position'])."\">".$shop['id_shop']."</shop>\r\n";
            }
            $xml .= "\t</shops>\r\n";
        }
        
        /* Les articles de la catégorie */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $xml .= "\t<products>\r\n";
            $result = Db::getInstance()->ExecuteS('SELECT `id_product`,`position` FROM `'._DB_PREFIX_.'category_product` WHERE `id_category` = '.(int)$category->id.' ORDER BY `position`', true, false);
            foreach ($result as $product) {
                $reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product` WHERE `id_product` ='.(int)($product['id_product']), true, false);
                $xml .= "\t\t<product position=\"".(int)($product['position'])."\">".escapeXMLString($reference)."</product>\r\n";
            }
            $xml .= "\t</products>\r\n";
        }
        
        /* Les articles de la catégorie par défaut */
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $xml .= "\t<defaut_products>\r\n";
            $result = Db::getInstance()->ExecuteS('SELECT `id_product`,`id_shop` FROM `'._DB_PREFIX_.'product_shop` WHERE `id_category_default` = '.(int)$category->id);
            if ($result) {
                foreach ($result as $product) {
                    $reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product` WHERE `id_product` ='.(int)($product['id_product']), true, false);
                    $xml .= "\t\t<defaut_product id_shop=\"".(int)($product['id_shop'])."\">".escapeXMLString($reference)."</defaut_product>\r\n";
                }
            }
            $xml .= "\t</defaut_products>\r\n";
        }
        
        $xml .= "</category>\r\n";
    }
    header("Content-type: text/xml");
    echo $xml;
    return 1;
}
/*
 * Active ou désactive la catégorie dans la boutique.
 */
function SetCategoryActive($id_category, $active)
{
    if (!empty($id_category) and is_numeric($id_category)) {
        if (is_numeric($active)) {
            $category = new Category((int)($id_category));
            if ($category) {
                $category->doNotRegenerateNTree = true;
                $category->active = (int)($active);
                $category->update();
            }
        }
    }
    return 1;
}
/*
 * Modifie la catégorie parent de la catégorie
 */
function SetCategoryParent($id_category, $id_parent)
{
    if (!empty($id_category) and is_numeric($id_category) and !empty($id_parent) and is_numeric($id_parent)) {
        $category = new Category((int)($id_category));
        if ($category) {
            $category->doNotRegenerateNTree = true;
            $category->id_parent = (int)($id_parent);
            $category->update();
        }
    }
    return 1;
}
/*
 * Ajoute l'article à la catégorie
 */
function AddProductToCategory($reference, $id_category)
{
    $return = false;
     
    if (!empty($reference) and is_string($reference) and !empty($id_category) and is_numeric($id_category)) {
        $id_category = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` ='.(int)($id_category).'');
        if ($id_category) {
            $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
            if ($id_product) {
                $exist = (int)(Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.'category_product` WHERE `id_category`='.(int)($id_category).' AND `id_product`='.(int)($id_product)));
                if ($exist == 0) {
                    $position = Db::getInstance()->getValue('SELECT MAX(position)+1 FROM `'._DB_PREFIX_.'category_product` WHERE `id_category` ='.(int)($id_category));
                    
                    $return= Db::getInstance()->Execute('
						INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`)
						VALUES ('.(int)($id_category).','.(int)($id_product).','.(int)($position).')');
                } else {
                    $return = true;
                }
            }
        }
    }
    return $return;
}
/*
 * Met l'article seulement dans la catégorie
 */
function SetProductToOnlyCategory($reference, $id_category)
{
    $return = false;
     
    if (!empty($reference) and is_string($reference) and !empty($id_category) and is_numeric($id_category)) {
        $id_category = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` ='.(int)($id_category).'');
        if ($id_category) {
            $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
            if ($id_product) {
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.(int)($id_product));
                
                $position = Db::getInstance()->getValue('SELECT MAX(position)+1 FROM `'._DB_PREFIX_.'category_product` WHERE `id_category` ='.(int)($id_category));
                                
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product` SET `id_category_default`='.(int)($id_category).' WHERE `id_product` ='.(int)($id_product));
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'product_shop` SET `id_category_default`='.(int)($id_category).' WHERE `id_product` ='.(int)($id_product));
                }
                $return= Db::getInstance()->Execute('
						INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`)
						VALUES ('.(int)($id_category).','.(int)($id_product).','.(int)($position).')');
                        
                Product::cleanPositions($id_category);
            }
        }
    }
    return $return;
}
/*
 * Retire l'article à la catégorie
 */
function DeleteProductFromCategory($reference, $id_category)
{
    if (!empty($reference) and is_string($reference) and !empty($id_category) and is_numeric($id_category)) {
        $id_category = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` ='.(int)($id_category));
        if ($id_category) {
            $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
            if ($id_product) {
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.(int)($id_product).' AND id_category = '.(int)($id_category).'');
                Product::cleanPositions($id_category);
            }
        }
    }
    return true;
}
/*
 * Régénère l'arbre des catégories
 */
function RegenerateCategoryNTree()
{
    Category::regenerateEntireNtree();
    $category = new Category(1);
    $category->recalculateLevelDepth(1);
    return true;
}
