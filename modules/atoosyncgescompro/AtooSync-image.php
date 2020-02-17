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
require_once(dirname(__FILE__).'/../../images.inc.php');
    
/*
 * Création des images
 */
function AddImage($xml)
{
    // Si il y a une erreur avec le chargement de l'image
    if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
        echo file_upload_error_message($_FILES['file']['error']);
        return 0;
    }
    $ImageXML = LoadXML(Tools::stripslashes($xml));
    if (empty($ImageXML)) {
        return 0;
    }
    
    /* si on ajoute une image de catégorie ou d'article */
    if ($ImageXML->id_category) {
        return AddImageCategory($ImageXML);
    } elseif ($ImageXML->reference) {
        return AddImageProduct($ImageXML);
    }
    return 0;
}
/*
 * Ajoute un image sur une catégorie
 */
function AddImageCategory($ImageXML)
{
    $retval=0;
    $id_category = Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` = \''.pSQL($ImageXML->id_category).'\'');
    if ($id_category) {
        if (isset($_FILES['file']) and is_uploaded_file($_FILES['file']['tmp_name'])) {
            $srcfile = $_FILES['file']['tmp_name'];
            $destfile = _PS_CAT_IMG_DIR_.(int)($id_category).'.jpg';
            
            if (imageResize($srcfile, $destfile)) {
                $imagesTypes = ImageType::getImagesTypes('categories');
                foreach ($imagesTypes as $k => $imageType) {
                    imageResize(_PS_CAT_IMG_DIR_.$id_category.'.jpg', _PS_CAT_IMG_DIR_.$id_category.'-'.stripslashes($imageType['name']).'.jpg', (int)($imageType['width']), (int)($imageType['height']));
                }
                    
                $retval=1;
            }
                    
            // Supprime l'image temporaire
            @unlink($srcfile);
        }
    }
    return $retval;
}
/*
 * Ajoute un image sur un article
 */
function AddImageProduct($ImageXML)
{
    $retval=0;
    $id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));
    
    // Trouve le id_product selon la réference de l'article
    $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL($ImageXML->reference).'\'');
    if ($id_product) {
        // Si l'image n'existe pas dans la table
        // ou si on peut modifier les images
        //
        $id_image = Db::getInstance()->getValue('SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `atoosync_image_id`='.(int)($ImageXML->atoosyncid).' AND `id_product`='.(int)($id_product));
        if (!$id_image) {
            // Si l'image existe on la supprime
            if ($id_image) {
                $image = new Image($id_image);
                $image->delete();
            }
            //
            if (isset($_FILES['file']) and is_uploaded_file($_FILES['file']['tmp_name'])) {
                $srcfile = $_FILES['file']['tmp_name'];
                
                $product= new Product($id_product);
                
                // Reprend les noms de l'article pour les legendes
                $legends = $product->name;
                
                // Met à jour le tableau des legendes de l'image
                // avec les textes d'Atoo-Sync GesCom Pro si ils sont présents
                if ($ImageXML->legends) {
                    foreach ($ImageXML->legends->legend as $legend) {
                        $tmp = (string)($legend);
                        if (!empty($tmp)) {
                            // Si suppression des caractères interdits
                            if (Configuration::get('ATOOSYNC_CLEAN_NAME') == 'Yes') {
                                $tmp = cleanName($tmp);
                            }
                            
                            $legends[(string)($legend['id_lang'])] = $tmp;
                            }
                    }
                }
                            
                $image = new Image();
                $image->id_product = (int)($product->id);
                $image->position = Image::getHighestPosition((int)($product->id)) + 1;
                $image->legend = $legends;
                
                // Met l'image en couverture si besoin
                if (ProductHasCover($product->id) == false and $ImageXML->cover == 'no') {
                    $image->cover = true;
                } else {
                    // Si image de couverture
                    if ($ImageXML->cover == 'yes') {
                        // Supprime l'image de couverture existante.
                        Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'image` SET `cover` = 0 WHERE `id_product` = '.(int)($product->id));
                        $image->cover = true;
                    } else {
                        $image->cover = false;
                    }
                }
                
                if (($fieldError = $image->validateFields(false, true)) === true and ($langFieldError = $image->validateFieldsLang(false, true)) === true) {
                    if ($image->add()) {
                        /* Selon la gestion des images */
                        if (method_exists('Image', 'getPathForCreation')) {
                            $path = $image->getPathForCreation();
                        } else {
                            // Chemin de l'image
                            $path = _PS_PROD_IMG_DIR_.(int)($product->id).'-'.(int)($image->id);
                        }
                        // Copie l'image
                        if (imageResize($srcfile, $path.'.jpg')) {
                            // Crée les miniatures de l'image
                            $imagesTypes = ImageType::getImagesTypes('products');
                            foreach ($imagesTypes as $k => $imageType) {
                                imageResize($srcfile, $path.'-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height']);
                            }
                            //
                            $retval=1;
                            // Enregistre l'id de l'image d'Atoo-Sync
                            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'image` SET `atoosync_image_id`='.(int)($ImageXML->atoosyncid).' WHERE `id_image`='.(int)($image->id));
                            Module::hookExec('watermark', array('id_image' => $image->id, 'id_product' => $image->id_product));
                            
                            /* Associe l'image aux attributs */
                            if ($ImageXML->combinaisons) {
                                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'product_attribute_image` WHERE `id_image` = '.(int)($image->id));
                                foreach ($ImageXML->combinaisons->combinaison as $unique) {
                                    $id_product_attribute = Db::getInstance()->getValue('
										SELECT `id_product_attribute`
										FROM `'._DB_PREFIX_.'product_attribute`
										WHERE `atoosync_gamme` = \''.pSQL($unique).'\'');
                                    if ($id_product_attribute) {
                                        $query = 'INSERT INTO `'._DB_PREFIX_.'product_attribute_image` (`id_product_attribute`, `id_image`) VALUES ('.(int)($id_product_attribute).','.(int)($image->id).')';
                                        Db::getInstance()->Execute($query);
                                    }
                                }
                            }
                        } else {
                            $image->delete();
                            echo 'Error imageResize()';
                        }
                    } else {
                        echo 'Error image->add()';
                    }
                } else {
                    echo 'Error validateFields()';
                    @unlink($srcfile);
                    $retval=0;
                }
                // Supprime l'image temporaire
                @unlink($srcfile);
            }
        }
    } else {
        // L'article n'a pas été trouvé
        $retval=1;
    }
    
    return $retval;
}
function ProductHasCover($id_product)
{
    $nb = Db::getInstance()->getValue('SELECT count(*) AS NB FROM `'._DB_PREFIX_.'image` WHERE `cover`=1 AND `id_product`='.(int)($id_product));
    if ((int)($nb) == 0) {
        return false;
    }
    return true;
}
function ProductHasCoverInShop($id_product)
{
    $nb = Db::getInstance()->getValue('SELECT count(*) AS NB FROM `'._DB_PREFIX_.'image` WHERE `cover`=1 AND `id_product`='.(int)($id_product));
    if ((int)($nb) == 0) {
        return false;
    }
    return true;
}
/*
 * les informations d'une image
 */
function GetImage($id_image)
{
    if (!empty($id_image) and is_numeric($id_image)) {
        $image = new Image((int)($id_image));
        if ($image) {
            if ((int)$image->id_product == 0) {
                $image->id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'image` WHERE `id_image`='.(int)($image->id));
            }
      
            $atoosync_image_id = Db::getInstance()->getValue('SELECT `atoosync_image_id` FROM `'._DB_PREFIX_.'image` WHERE `id_image`='.(int)($image->id));
            $reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product` WHERE `id_product`='.(int)($image->id_product));
            
            if (empty($image->cover)) {
                $image->cover = 0;
            }
                        
            /* Selon la gestion des images */
            if (method_exists('Image', 'getExistingImgPath')) {
                $file = $image->getExistingImgPath().'.jpg';
            } else {
                /* Chemin de l'image */
                $file = (int)($image->id_product).'-'.(int)($image->id).'.jpg';
            }
                    
            /* Entete du XML de l'image */
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
            $xml .= "<image>\r\n";
            $xml .= "\t<id_image>".$image->id."</id_image>\r\n";
            $xml .= "\t<id_product>".$image->id_product."</id_product>\r\n";
            $xml .= "\t<position>".$image->position."</position>\r\n";
            $xml .= "\t<cover>".$image->cover."</cover>\r\n";
            $xml .= "\t<atoosync_image_id>".$atoosync_image_id."</atoosync_image_id>\r\n";
            $xml .= "\t<reference>".$reference."</reference>\r\n";
            $xml .= "\t<file>".$file."</file>\r\n";
            
            $xml .= "\t<legends>\r\n";
            foreach ($image->legend as $lang => $texte) {
                $tmp = html_entity_decode(strip_tags(br2nl($texte)), ENT_QUOTES, 'UTF-8');
                $tmp = escapeXMLString($tmp);
                $xml .= "\t\t<legend id_lang=\"".$lang."\">".$tmp."</legend>\r\n";
            }
            $xml .= "\t</legends>\r\n";
            
            /* Les déclinaisons de l'images*/
            $sql = 'SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute_image` WHERE `id_image`='.(int)($image->id);
            $attributes = Db::getInstance()->ExecuteS($sql);
            if ($attributes) {
                $xml .= "\t<attributes>\r\n";
                foreach ($attributes as $att) {
                    $sql = 'SELECT * FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`='.(int)($att['id_product_attribute']);
                    $attribut = Db::getInstance()->ExecuteS($sql);
                    foreach ($attribut as $pa) {
                        $xml .= "\t\t<attribute>\r\n";
                        $xml .= "\t\t\t<reference>".$pa['reference']."</reference>\r\n";
                        $xml .= "\t\t\t<ean13>".$pa['ean13']."</ean13>\r\n";
                        $xml .= "\t\t\t<atoosync_gamme>".$pa['atoosync_gamme']."</atoosync_gamme>\r\n";
                        $xml .= "\t\t</attribute>\r\n";
                    }
                }
                $xml .= "\t</attributes>\r\n";
            }
            $xml .= "</image>\r\n";

            header("Content-type: text/xml");
            echo $xml;
            return 1;
        }
    }
    return 0;
}

/*
 * Retourne l'id de l'image
 */
function GetImageId($atoosyncid)
{
    $retval=0;

    if (!empty($atoosyncid) and is_string($atoosyncid)) {
        /* Si l'image existe */
        $image_id = Db::getInstance()->getValue('SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `atoosync_image_id` = \''.(int)((string)($atoosyncid)).'\'');
        if ($image_id) {
            echo $image_id.'<br>';
            $retval =1;
        }
    }
    return $retval;
}

/*
 * Renseigne l'id Atoo-Sync de l'image
 */
function SetImageId($atoosyncid, $id_image)
{
    $retval=0;

    if (!empty($atoosyncid) and is_numeric($atoosyncid) and !empty($id_image) and is_numeric($id_image)) {
        $retval = Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'image` SET `atoosync_image_id` = '.(int)($atoosyncid).' WHERE `id_image` = '.(int)($id_image));
    }
    return $retval;
}
/*
 * Supprime les images de l'article
 */
function DelImages($reference)
{
    $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` = \''.pSQL((string)($reference)).'\'');
    if ($id_product) {
        $product = new Product($id_product);
        $product->deleteImages();

        // Supprime les images des atttributs
        // Normalement ne devrait pas exister !
        $attributes = Db::getInstance()->ExecuteS('SELECT `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product` ='.pSQL($id_product));
        if ($attributes) {
            foreach ($attributes as $k => $row) {
                $id_product_attribute= (int)($row['id_product_attribute']);
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'product_attribute_image` WHERE `id_product_attribute` = '.(int)($id_product_attribute));
            }
        }
        
        // Force la supression des images dans les tables liés
        // Normalement ne devrait pas exister !
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'image` WHERE `id_product` = '.(int)($id_product));
    }
    return 1;
}
/*
 * Supprime l'image de l'article
 */
function DelImage($atoosyncid)
{
    $retval=0;
    if (!empty($atoosyncid) and is_numeric($atoosyncid)) {
        $id_image = Db::getInstance()->getValue('SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `atoosync_image_id`='.(int)($atoosyncid));
        if ($id_image) {
            $image = new Image($id_image);
            $image->delete();
            
            if (!Image::getCover($image->id_product)) {
                $first_img = Db::getInstance()->getRow('
				SELECT `id_image` FROM `'._DB_PREFIX_.'image`
				WHERE `id_product` = '.(int)($image->id_product));
                Db::getInstance()->Execute('
				UPDATE `'._DB_PREFIX_.'image`
				SET `cover` = 1
				WHERE `id_image` = '.(int)($first_img['id_image']));
            }
            $retval =1;
        }
    }
    return $retval;
}

/*
 * Spécifie l'image de couverture
 */
function SetCoverImage($reference, $atoosyncid)
{
    $retval=0;
    
    if (!empty($reference) and is_string($reference) and !empty($atoosyncid) and is_numeric($atoosyncid)) {
        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
        if ($id_product) {
            $id_image = Db::getInstance()->getValue('SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `atoosync_image_id`='.(int)($atoosyncid));
            if ($id_image) {
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'image` SET `cover` = 0 WHERE `id_product` = '.(int)($id_product));
                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'image` SET `cover` = 1 WHERE `id_product` = '.(int)($id_product).' AND `id_image` = '.(int)$id_image);
                
                $retval =1;
            }
        }
    }
    return $retval;
}

/*
 * Change la position de l'image
 */
function SetImagePosition($atoosyncid, $direction)
{
    $retval=0;
    if (!empty($atoosyncid) and is_numeric($atoosyncid) and !empty($direction) and is_numeric($direction)) {
        $id_image = Db::getInstance()->getValue('SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `atoosync_image_id`='.(int)($atoosyncid));
        if ($id_image) {
            $image = new Image((int)($id_image));
            if ($image) {
                $image->positionImage($image->position, (int)($direction));
                $retval = 1;
            }
        }
    }
    return $retval;
}

/*
 * Retourne la position de l'image
 */
function GetImagePosition($atoosyncid)
{
    $retval=0;

    if (!empty($atoosyncid) and is_string($atoosyncid)) {
        $position = Db::getInstance()->getValue('SELECT `position` FROM `'._DB_PREFIX_.'image` WHERE `atoosync_image_id` = \''.(int)((string)($atoosyncid)).'\'');
        if ($position) {
            echo $position.'<br>';
            $retval =1;
        }
    }
    return $retval;
}

/*
 * Retourne la position des images de l'article
 */
function GetImagesPosition($reference)
{
    $retval=0;

    if (!empty($reference) and is_string($reference)) {
        $id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `reference` =\''.pSQL((string)($reference)).'\'');
        if ($id_product) {
            $positions = Db::getInstance()->ExecuteS('SELECT `id_image`, `atoosync_image_id`, `position` FROM `'._DB_PREFIX_.'image` WHERE `id_product` = \''.(int)($id_product).'\'');
            foreach ($positions as $position) {
                $p ='';
                $p .= $position['atoosync_image_id']."|";
                $p .= $position['id_image']."|";
                $p .= $position['position'];
                
                $p = stripslashes($p);
                $p= html_entity_decode($p);
                echo $p.'<br>';
            }
            $retval =1;
        }
    }
    return $retval;
}
