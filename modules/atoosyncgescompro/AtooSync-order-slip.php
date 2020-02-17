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
 * Retourne les avoirs associé à une commande
 * deja exporté dans Sage Gestion Commerciale
 */
function GetOrdersSlips($startdate, $enddate)
{
    // la précision des prix
    $precision = (int)(Configuration::get('ATOOSYNC_ORDER_ROUND'));
    if ($precision == '') {
        $precision= 2;
    }
    
    $xml = '';
    
    // Requête d'interrogation des avoirs
    $query = 'SELECT 
					pos.`id_order_slip` 
				FROM 
					`'._DB_PREFIX_.'order_slip` pos
				LEFT JOIN 
					`'._DB_PREFIX_.'orders` o ON o.`id_order` = pos.`id_order` 
				WHERE 
					pos.`atoosync_transfert_gescom` = 0
				AND 
					o.`atoosync_transfert_gescom` = 1
				AND
					pos.`date_add` BETWEEN \''.$startdate.'\' AND \''.$enddate.'\'
				ORDER BY 
					pos.`id_order_slip`';

    $resultat = Db::getInstance()->ExecuteS($query);
    if ($resultat) {
        foreach ($resultat as $k => $row) {
            $id_order_slip = (int)$row['id_order_slip'];
            // instancie le retour produit
            $order_slip = new OrderSlip($id_order_slip);
            $order= new Order($order_slip->id_order);

            $prefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', IdLangDefault());
            $order_slip_number = sprintf('%1$s%2$06d', $prefix, (int)$order_slip->id);
      
            $carrierName = Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'carrier` WHERE `id_carrier`= '.(int)($order->id_carrier));
            $shipping = 0;
            $shippingTaxeRate = 0;
            $shipping_wt = 0;
            if ($order_slip->shipping_cost == true) {
                $shipping = $order_slip->total_shipping_tax_excl;
                $shippingTaxeRate = $order->carrier_tax_rate;
                $shipping_wt = $order_slip->total_shipping_tax_incl;
            }
      
            /* La zone et le pays de l'adresse pour les taxes */
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $zone_id = Address::getZoneById($order->id_address_invoice);
                $address = new Address((int)($order->id_address_invoice));
                $country_id = $address->id_country;
            } else {
                $zone_id = Address::getZoneById($order->id_address_delivery);
                $address = new Address((int)($order->id_address_delivery));
                $country_id = $address->id_country;
            }
        
            // Formate ou pas le numéro de la commande sur 6 chiffres.
            if (Configuration::get('ATOOSYNC_ORDER_FORMAT_NUMBER') == 'No') {
                $order_number = $order_slip->id_order;
            } else {
                $order_number = sprintf('%06d', $order_slip->id_order);
            }
            
            $xml .= "\t<order_slip>\r\n";
            $xml .= "\t\t<order_slip_id>".$order_slip->id."</order_slip_id>\r\n";
            $xml .= "\t\t<order_slip_number>".escapeXMLString($order_slip_number)."</order_slip_number>\r\n";
            $xml .= "\t\t<order_slip_date>".escapeXMLString($order_slip->date_add)."</order_slip_date>\r\n";
            
            $xml .= "\t\t<order_id>".$order_slip->id_order."</order_id>\r\n";
            $xml .= "\t\t<order_number>".escapeXMLString($order_number)."</order_number>\r\n";
            $xml .= "\t\t<reference>".escapeXMLString(CustomizeOrderReference($order))."</reference>\r\n";
            
            $xml .= "\t\t<order_zone_id>".$zone_id."</order_zone_id>\r\n";
            $xml .= "\t\t<order_country_id>".$country_id."</order_country_id>\r\n";
            $xml .= "\t\t<order_carrier_id>".$order->id_carrier."</order_carrier_id>\r\n";
            $xml .= "\t\t<order_carrier_name>".escapeXMLString($carrierName)."</order_carrier_name>\r\n";
            $xml .= "\t\t<order_currency_id>".$order->id_currency."</order_currency_id>\r\n";
            $xml .= "\t\t<order_currency_rate>".$order->conversion_rate."</order_currency_rate>\r\n";
            $xml .= "\t\t<order_slip_shipping>".number_format(round($shipping, $precision), $precision, '.', '')."</order_slip_shipping>\r\n";
            $xml .= "\t\t<order_slip_shipping_taxrate>".number_format(round($shippingTaxeRate, 3), 3, '.', '')."</order_slip_shipping_taxrate>\r\n";
            $xml .= "\t\t<order_slip_shipping_wt>".number_format(round($shipping_wt, $precision), $precision, '.', '')."</order_slip_shipping_wt>\r\n";
            $xml .= CustomizeOrderSlip($order_slip);
        
            /* Les données du client */
            $xml .= OrderSlipCustomerNode($order);
              
            /* Les articles */
            $xml .= OrderSlipProductsNode($order_slip, $order);
      
            /* Fin */
            $xml .= "\t</order_slip>\r\n";
        }
    }
    return $xml;
}
/*
 * Retourne le XML des informations du client de la commande du retour produit
 */
function OrderSlipCustomerNode($order)
{
    $xml ="";
    
    $id_customer = $order->id_customer;
    
    // si le id_customer = 0 alors recherche sur l'adresse de facturation
    if ($id_customer == 0) {
        $address = new Address((int)($order->id_address_invoice));
        $id_customer = $address->id_customer;
    }
    
    $customer = new Customer((int)($id_customer));
    if ($customer) {
        $civility= '';
        if ($customer->id_gender == 1) {
            $civility= 'M.';
        }
        if ($customer->id_gender == 2) {
            $civility= 'Mme';
        }
            
        $xml .= "\t\t<customer_id>".$customer->id."</customer_id>\r\n";
        $xml .= "\t\t<customer_firstname>".escapeXMLString($customer->firstname)."</customer_firstname>\r\n";
        $xml .= "\t\t<customer_lastname>".escapeXMLString($customer->lastname)."</customer_lastname>\r\n";
        $xml .= "\t\t<customer_email>".escapeXMLString($customer->email)."</customer_email>\r\n";
        $xml .= "\t\t<customer_civility>".escapeXMLString($civility)."</customer_civility>\r\n";
        $xml .= "\t\t<customer_account_number>".escapeXMLString(CustomizeCustomerAccount($id_customer, $order->id))."</customer_account_number>\r\n";
    }
    return $xml;
}

/*
 *	Retourne le noeud XML des articles du retour produit
 */
function OrderSlipProductsNode($order_slip, $order)
{
    // la précision des prix
    $precision = (int)(Configuration::get('ATOOSYNC_ORDER_ROUND'));
    if ($precision == '') {
        $precision= 2;
    }
        
    $xml = "\t\t<products>\r\n";
        
    $products = OrderSlip::getOrdersSlipProducts($order_slip->id, $order);
    foreach ($products as $product) {
        $gamme1 ='0';
        $gamme2 ='0';
        $conditionnement='0';
        $reference = CustomizeOrderProductReference($order, $product);
        $product_reference  ='';
        $attribute_reference = '';
        $packaging_reference = '';
        $atoosync_conditionnement_quantity =0;
            
        if (empty($reference)) {
            $reference = $product['product_reference'];
                    
            // Si la référence est vide alors on va chercher celle dans la fiche article.
            // utile pour les commandes passées avant l'ajout de la référence dans la fiche article.
            if (empty($reference)) {
                $reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product` WHERE `id_product`=\''.(int)($product['product_id']).'\'');
            }
            // Si il y a des déclinaisons de produit (atoosync_gamme) ou (packaging_reference)
            if ((int)($product['product_attribute_id']) != 0) {
                $atoosync_gamme = Db::getInstance()->getValue('SELECT `atoosync_gamme` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`=\''.(int)($product['product_attribute_id']).'\'');
                $atoosync_conditionnement = Db::getInstance()->getValue('SELECT `atoosync_conditionnement` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`=\''.(int)($product['product_attribute_id']).'\'');
                $atoosync_conditionnement_quantity = (float)Db::getInstance()->getValue('SELECT `atoosync_conditionnement_qte` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`=\''.(int)($product['product_attribute_id']).'\'');
                $attribute_reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`=\''.(int)($product['product_attribute_id']).'\'');
                $reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'product` WHERE `id_product`=\''.(int)($product['product_id']).'\'');
                
                // si le champ atoosync_gamme n'est pas vide.
                if (!empty($atoosync_gamme)) {
                    // enleve la référence + le séparateur de la chaine
                    $atoosync_gamme = str_replace($reference.'_', '', $atoosync_gamme);
                    $gammes = explode("_", $atoosync_gamme);
                    $gamme1 = $gammes[0];
                    if (count($gammes) == 2) {
                        $gamme2 = $gammes[1];
                    }
                }

                // si le champ atoosync_condtionnement n'est pas vide.
                if (!empty($atoosync_conditionnement)) {
                    // enleve la référence + le séparateur de la chaine
                    $atoosync_conditionnement = str_replace($reference.'_', '', $atoosync_conditionnement);
                    $conditionnements = explode("_", $atoosync_conditionnement);
                    $conditionnement = $conditionnements[0];
                    $packaging_reference =$attribute_reference;
                    $attribute_reference ='';
                }
            }
        }
        /* La quantité des articles */
        $quantity = (float)($product['product_quantity']);
        
        /* Prix avec Réductions */
        $final_price = $product['unit_price_tax_excl'];
        $final_price_wt = $product['unit_price_tax_incl'];
        $price = $final_price;
        $price_wt = $final_price_wt;
        
        // si le prix HT et TCC sont identique alors quvide la TVA
        if ($final_price == $final_price_wt) {
            $product['tax_rate'] = 0;
        }
             
                        
        // si il y a un conditionnement alors divise les prix de vente et multiplie la quantité
        if ($packaging_reference !='' and ($atoosync_conditionnement_quantity) != 0) {
            $quantity = $quantity * $atoosync_conditionnement_quantity;
            $price = $price / $atoosync_conditionnement_quantity;
            $price_wt = $price_wt / $atoosync_conditionnement_quantity;
            $final_price = $final_price / $atoosync_conditionnement_quantity;
            $final_price_wt = $final_price_wt / $atoosync_conditionnement_quantity;
        }
        $xml .= "\t\t\t<product>\r\n";
        $xml .= "\t\t\t\t<reference>".escapeXMLString($reference)."</reference>\r\n";
        $xml .= "\t\t\t\t<attribute_reference>".escapeXMLString($attribute_reference)."</attribute_reference>\r\n";
        $xml .= "\t\t\t\t<packaging_reference>".escapeXMLString($packaging_reference)."</packaging_reference>\r\n";
        $xml .= "\t\t\t\t<packaging_quantity>".escapeXMLString($atoosync_conditionnement_quantity)."</packaging_quantity>\r\n";
        $xml .= "\t\t\t\t<ean13>".escapeXMLString($product['product_ean13'])."</ean13>\r\n";
        $xml .= "\t\t\t\t<name>".escapeXMLString($product['product_name'])."</name>\r\n";
        $xml .= "\t\t\t\t<quantity>".$quantity."</quantity>\r\n";
        $xml .= "\t\t\t\t<price>".number_format(round($price, $precision), $precision, '.', '')."</price>\r\n";
        $xml .= "\t\t\t\t<price_wt>".number_format(round($price_wt, $precision), $precision, '.', '')."</price_wt>\r\n";
        $xml .= "\t\t\t\t<final_price>".number_format(round($final_price, $precision), $precision, '.', '')."</final_price>\r\n";
        $xml .= "\t\t\t\t<final_price_wt>".number_format(round($final_price_wt, $precision), $precision, '.', '')."</final_price_wt>\r\n";
        $xml .= "\t\t\t\t<tax_rate>".$product['tax_rate']."</tax_rate>\r\n";
        $xml .= "\t\t\t\t<reduction_percent>".number_format(round($product['reduction_percent'], $precision), $precision, '.', '')."</reduction_percent>\r\n";
        $xml .= "\t\t\t\t<reduction_amount>".number_format(round($product['reduction_amount'], $precision), $precision, '.', '')."</reduction_amount>\r\n";
        $xml .= "\t\t\t\t<ecotax>".number_format(round($product['ecotax'], $precision), $precision, '.', '')."</ecotax>\r\n";
        $xml .= "\t\t\t\t<ecotax_tax_rate>".$product['ecotax_tax_rate']."</ecotax_tax_rate>\r\n";
        $xml .= "\t\t\t\t<gamme1>".escapeXMLString($gamme1)."</gamme1>\r\n";
        $xml .= "\t\t\t\t<gamme2>".escapeXMLString($gamme2)."</gamme2>\r\n";
        $xml .= "\t\t\t\t<conditionnement>".escapeXMLString($conditionnement)."</conditionnement>\r\n";
                
        $xml .= "\t\t\t</product>\r\n";
    }
    $xml .= "\t\t</products>\r\n";

    return $xml;
}
/*
 * Renseigne le champ atoosync_transfert_gescom à 1 sur l'avoir
 */
function SetOrderSlipTransferred($id)
{
    $succes = 0;
    
    if (!empty($id) and is_numeric($id)) {
        $query = "UPDATE `"._DB_PREFIX_."order_slip` SET `atoosync_transfert_gescom`='1' WHERE `id_order_slip`='".(int)($id)."'";
        Db::getInstance()->Execute($query);
        $succes = 1;
        
        // execute la fonction de customisation
        CustomizeOrderSlipTransferred($id);
    }
    return $succes;
}
