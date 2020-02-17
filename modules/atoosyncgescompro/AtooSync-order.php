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
 * Construit le XML de la liste des commandes
 * from 	= date de début de la période
 * to 	 	= date de fin de la période a générer
 * status 	= les id_order_state des commandes
 * reload 	= ignore les commandes déjà transférées
 * all 		= transfert toutes les commandes
 */
function GetXMLOrders($from, $to, $status, $shops, $reload = false, $all = false)
{
    $PS_VERSION = (float)(_PS_VERSION_); // Version de PrestaShop
    // la précision des prix
    $precision = (int)(Configuration::get('ATOOSYNC_ORDER_ROUND'));
    if ($precision == '') {
        $precision= 2;
    }
            
    /* la periode */
    $allorders = false;
    if ((string)($all) == 'yes') {
        $startdate= '1970-01-01 00:00:00';
        $enddate= '2099-12-13 23:59:59';
    } else {
        $startdate= date("Y-m-d 00:00:00", (int)($from));
        $enddate= date("Y-m-d 00:00:00", (int)($to));
        $startdate= $from.' 00:00:00';
        $enddate= $to.' 23:59:59';
    }
    
    /* Les statuts des commandes à transférer */
    if (!empty($status) and is_string($status)) {
        $statuslist = explode("|", $status);
    } else {
        $statuslist[0] = '5'; // statut par defaut = livré
    }
       
    // Requête d'interrogation des commandes
    $query = 'SELECT id_order FROM `'._DB_PREFIX_.'orders`';
    if (Configuration::get('ATOOSYNC_ORDER_DATE') == 'invoices') {
        $query .= "WHERE `invoice_date` BETWEEN '".$startdate."' AND '".$enddate."'";
    } else {
        $query .= "WHERE `date_add` BETWEEN '".$startdate."' AND '".$enddate."'";
    }
                
    if ((string)($reload) != 'yes') {
        $query .= " AND (`atoosync_transfert_gescom`='0' OR `atoosync_transfert_gescom` IS NULL)";
    }
    // Si PrestaShop 1.5 + alors applique le filtre des boutiques
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        if ((string)($shops) != 'all') {
            $query .= " AND (`id_shop` in (".pSQL($shops)."))";
        }
    }
    /* tri */
    if (Configuration::get('ATOOSYNC_ORDER_DATE') == 'invoices') {
          $query .= " ORDER BY `invoice_date`, `id_order`";
    } else {
        $query .= " ORDER BY `date_add`, `id_order`";
    }

    //  $query = 'SELECT id_order FROM `'._DB_PREFIX_.'orders` WHERE id_order=65268';
  
    // Entete du XML
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
    $xml .= "<orders>\r\n";

    // pour chaque commande de la période.
    $resultat = Db::getInstance()->ExecuteS($query, true, 0);
    foreach ($resultat as $k => $row) {
        $id_order = $row['id_order'];
        // instancie la commande
        $orderObj = new Order((int)($id_order));
        
        $includeOrder = true;
      
        // si la commande est une commande POS
        // test si la commande n'est pas dans la journée
        if (isPOS($orderObj) == 1) {
            if (Configuration::get('ATOOSYNC_IGNORE_POS_ORDERS_DAY') == 'Yes') {
                $now = new DateTime(date('Y-m-d'));
                $now = $now->format('Ymd');
         
                $orderdate = new DateTime($orderObj->date_add);
                $orderdate = $orderdate->format('Ymd');

                if ($orderdate == $now) {
                    $includeOrder = false;
                }
            }
        }
      
        // Si le dernier status de la commande est dans ceux demandés
        $order_status = $orderObj->getCurrentState();
        if (in_array($order_status, $statuslist) and  $includeOrder) {
                    
      // Recupere les frais de port
            $shipping = $orderObj->total_shipping;
            $shippingTaxeRate = 0;
            $carrierName = Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'carrier` WHERE `id_carrier`= \''.(int)($orderObj->id_carrier).'\'');
            
            // Recupere l'emballage
            $wrapping = $orderObj->total_wrapping;
            $wrappingTaxeRate = 0;
            
            /* La zone et le pays de l'adresse pour les taxes */
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $zone_id = Address::getZoneById($orderObj->id_address_invoice);
                $address = new Address((int)($orderObj->id_address_invoice));
                $country_id = $address->id_country;
            } else {
                $zone_id = Address::getZoneById($orderObj->id_address_delivery);
                $address = new Address((int)($orderObj->id_address_delivery));
                $country_id = $address->id_country;
            }
    
            // Calcul les frais de port HT
            // et le taux de taxe des frais de port
            if ((float)$orderObj->total_shipping !=0) {
                // Selon la version de PS
                if (version_compare(_PS_VERSION_, 1.4) >= 0) {
                    $shippingTaxeRate = (float)$orderObj->carrier_tax_rate;
                    $TVA = (float)$orderObj->total_shipping - (float)($orderObj->total_shipping / (($shippingTaxeRate / 100) + 1));
                    // le montant des frais de port devient HT
                    
                    $shipping = (float)$orderObj->total_shipping - (float)$TVA;
                } else {
                    $id_tax = GetIdTaxCarrier($orderObj->id_carrier);
                    // Si il y a une taxe alors on trouve le HT des frais de port
                    // Si la zone est sujette à taxation
                    if ($id_tax !=0 and Tax::zoneHasTax((int)($id_tax), (int)($zone_id))) {
                        $shippingTaxeRate = GetTaxRate($id_tax);
                        $TVA = (float)($orderObj->total_shipping) - ($orderObj->total_shipping / (((float)($shippingTaxeRate) / 100) + 1));
                        // le montant des frais de port devient HT
                        $shipping = (float)($orderObj->total_shipping) - (float)($TVA);
                    }
                }
            }
            
            /*	Si il y a un emballage alors on recherche aussi la TVA
                le taux de taxe utilisé pour l'emballage et le dernier
                taux de taxe des articles pour PrestaShop 1.1
                ou la taxe de l'emballage pour PrestaShop 1.2
            */
            if ((float)($orderObj->total_wrapping) != 0) {
                if ($PS_VERSION <= 1.1) {
                    /* Le dernier taux de taxe de l'article */
                    $sql= "SELECT `tax_rate` FROM `"._DB_PREFIX_."order_detail` WHERE `id_order`= '".(int)($id_order)."' ORDER BY `id_order_detail` DESC LIMIT 0,1";
                    $wrappingTaxeRate = Db::getInstance()->getValue($sql);
                    $id_tax = Tax::getTaxIdByRate($wrappingTaxeRate);
                    /* Si il y a une taxe et que la zone est sujette à taxation. */
                    if ($id_tax !=0 and Tax::zoneHasTax((int)($id_tax), (int)($zone_id))) {
                        $TVA = (float)($orderObj->total_wrapping) - ($orderObj->total_wrapping / (((float)($wrappingTaxeRate) / 100) + 1));
                        /* le montant de l'emballage devient HT */
                        $wrapping = (float)($orderObj->total_wrapping) - (float)($TVA);
                    }
                } elseif ($PS_VERSION >= 1.2) {
                    /* La taxe de l'emballage */
                    $id_tax = (int)(Configuration::get('PS_GIFT_WRAPPING_TAX'));
                    /* Si il y a une taxe et que la zone est sujette à taxation. */
                    if ($id_tax !=0 and Tax::zoneHasTax((int)($id_tax), (int)($zone_id))) {
                        $wrappingTaxeRate = GetTaxRate($id_tax);
                        $TVA = (float)($orderObj->total_wrapping) - ($orderObj->total_wrapping / (((float)($wrappingTaxeRate) / 100) + 1));
                        /* le montant de l'emballage devient HT */
                        $wrapping = (float)($orderObj->total_wrapping) - (float)($TVA);
                    }
                }
            }
            // Formate ou pas le numéro de la commande sur 6 chiffres.
            if (Configuration::get('ATOOSYNC_ORDER_FORMAT_NUMBER') == 'No') {
                $order_number = $id_order;
            } else {
                $order_number = sprintf('%06d', $id_order);
            }
                
                
            $xml .= "\t<order>\r\n";
            $xml .= "\t\t<order_id>".$id_order."</order_id>\r\n";
            $xml .= "\t\t<id_cart>".$orderObj->id_cart."</id_cart>\r\n";
            $xml .= "\t\t<order_status>".$order_status."</order_status>\r\n";
            $xml .= "\t\t<order_number>".escapeXMLString($order_number)."</order_number>\r\n";
            $xml .= "\t\t<invoice_number>".GetInvoicePrefix().sprintf('%06d', $orderObj->invoice_number)."</invoice_number>\r\n";
            $xml .= "\t\t<reference>".escapeXMLString(CustomizeOrderReference($orderObj))."</reference>\r\n";
            $xml .= "\t\t<zone_id>".$zone_id."</zone_id>\r\n";
            $xml .= "\t\t<country_id>".$country_id."</country_id>\r\n";
            $xml .= "\t\t<carrier_id>".$orderObj->id_carrier."</carrier_id>\r\n";
            $xml .= "\t\t<carrier_name>".escapeXMLString($carrierName)."</carrier_name>\r\n";
            $xml .= "\t\t<order_date>".$orderObj->date_add."</order_date>\r\n";
            $xml .= "\t\t<invoice_date>".$orderObj->invoice_date."</invoice_date>\r\n";
            $xml .= "\t\t<delivery_date>".$orderObj->delivery_date."</delivery_date>\r\n";
            $xml .= "\t\t<currency_id>".$orderObj->id_currency."</currency_id>\r\n";
            $xml .= "\t\t<currency_rate>".$orderObj->conversion_rate."</currency_rate>\r\n";
            $xml .= "\t\t<payment>".escapeXMLString(CustomizeOrderPayment($orderObj))."</payment>\r\n";
            $xml .= "\t\t<pos_order>".isPOS($orderObj)."</pos_order>\r\n";
            $xml .= "\t\t<shipping>".number_format(round($shipping, $precision), $precision, '.', '')."</shipping>\r\n";
            $xml .= "\t\t<shipping_taxrate>".$shippingTaxeRate."</shipping_taxrate>\r\n";
            $xml .= "\t\t<shipping_wt>".number_format(round($orderObj->total_shipping, $precision), $precision, '.', '')."</shipping_wt>\r\n";
            $xml .= "\t\t<wrapping>".number_format(round($wrapping, $precision), $precision, '.', '')."</wrapping>\r\n";
            $xml .= "\t\t<wrapping_taxrate>".$wrappingTaxeRate."</wrapping_taxrate>\r\n";
            $xml .= "\t\t<wrapping_wt>".number_format(round($orderObj->total_wrapping, $precision), $precision, '.', '')."</wrapping_wt>\r\n";
            $xml .= "\t\t<total_discounts>".number_format(round($orderObj->total_discounts, $precision), $precision, '.', '')."</total_discounts>\r\n";
            $xml .= "\t\t<total_paid>".number_format(round($orderObj->total_paid, $precision), $precision, '.', '')."</total_paid>\r\n";
            $xml .= "\t\t<total_paid_real>".number_format(round($orderObj->total_paid_real, $precision), $precision, '.', '')."</total_paid_real>\r\n";
            $xml .= "\t\t<total_products>".number_format(round($orderObj->total_products, $precision), $precision, '.', '')."</total_products>\r\n";
            $xml .= CustomizeOrder($id_order, $orderObj->id_customer);
              //
            $xml .= "\t\t<create_taxes_included>".customizeCreateTaxesIncluded($orderObj)."</create_taxes_included>\r\n";
            /* Les données du client */
            $xml .= NodeCustomer($id_order, $orderObj->id_customer);
                        
            /* Les adresses */
            $xml .= NodeAddresses($orderObj);
            
            /* Les informations pour le module Expeditor Inet*/
            $xml .= NodeExpeditorInetAddress($orderObj);
            
            /* Les informations pour le module TNT*/
            $xml .= NodeTNTAddress($orderObj);
      
            /* les bons de réduction */
            $xml .= NodeOrderDiscounts($orderObj);
                        
            /* les règlements*/
            $xml .= NodeOrderPayments($orderObj);
      
            /* Les articles */
            $xml .= NodeProducts($orderObj);
            
            /* Les messages */
            $xml .= "\t\t<messages>".escapeXMLString(OrderMessages($id_order))."</messages>\r\n";
            
            /* Fin */
            $xml .= "\t</order>\r\n";
        }
    }
    $xml .= CustomizeXMLOrders($from, $to, $status, $reload, $all);
    
    // inclus les retours produits dans le flux des commandes
    if (Configuration::get('ATOOSYNC_INCLUDE_ORDER_SLIP') == 'Yes') {
        $xml .= GetOrdersSlips($startdate, $enddate);
    }
    
    // inclus les retours produits dans le flux des commandes
    if (Configuration::get('ATOOSYNC_INCLUDE_ORDER_RETURN') == 'Yes') {
        $xml .= GetOrdersReturns($startdate, $enddate);
    }
    
    /* Fin du XML */
    $xml .= "</orders>\r\n";
    
        
    header("Content-type: text/xml");
    echo $xml;
    return 1;
}
function isPOS($order)
{
    // si le module ou le paiement contient kerawen alors commande POS
    if (strtolower($order->module) == 'kerawen' or strtolower($order->payment) == 'kerawen') {
        return 1;
    }
  
    return 0;
}
function NodeOrderPayments($order)
{
    $xml = CustomizeOrderPayments($order);
    if (empty($xml)) {
        $xml = "";
        $xml .= "\t\t<payments>\r\n";
    
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            if (Configuration::get('ATOOSYNC_ORDER_USE_PAYMENT') == 'Yes') {
                $payments = $order->getOrderPaymentCollection();
                if ($payments) {
                    foreach ($payments as $payment) {
                        $xml .= "\t\t\t<orderPayment>\r\n";
                        $xml .= "\t\t\t\t<amount>".number_format($payment->amount, 2, '.', '')."</amount>\r\n";
                        $xml .= "\t\t\t\t<payment_method>".escapeXMLString($payment->payment_method)."</payment_method>\r\n";
                        $xml .= "\t\t\t\t<conversion_rate>".number_format($payment->conversion_rate, 6, '.', '')."</conversion_rate>\r\n";
                        $xml .= "\t\t\t\t<transaction_id>".escapeXMLString($payment->transaction_id)."</transaction_id>\r\n";
                        $xml .= "\t\t\t\t<date_add>".$payment->date_add."</date_add>\r\n";
                    
                        $xml .= "\t\t\t</orderPayment>\r\n";
                    }
                }
            }
        }
    
        $xml .= "\t\t</payments>\r\n";
    }
    return $xml;
}
/*
 *
 */
function NodeOrderDiscounts($order)
{
    $xml = CustomizeOrderDiscounts($order);
    if (empty($xml)) {
        $xml = "";
        $xml .= "\t\t<discounts>\r\n";
        if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            $discounts = $order->getCartRules();
        } else {
            $discounts = $order->getDiscounts();
        }
        if ($discounts) {
            foreach ($discounts as $discount) {
                $product_reference = '';
                if (Configuration::get('ATOOSYNC_DISCOUNT_DESCRIPTION') == 'Yes') {
                    $description = Db::getInstance()->getValue('SELECT `description` FROM `'._DB_PREFIX_.'cart_rule` WHERE `id_cart_rule`= '.(int)$discount['id_cart_rule']);
                    if (preg_match("/^[A-Z0-9_]+$/", $description)) {
                        $product_reference = $description;
                    }
                }
                $xml .= "\t\t\t<discount>\r\n";
                $xml .= "\t\t\t\t<product_reference>".escapeXMLString($product_reference)."</product_reference>\r\n";
                $xml .= "\t\t\t\t<name>".escapeXMLString($discount['name'])."</name>\r\n";
                $xml .= "\t\t\t\t<value>".$discount['value']."</value>\r\n";
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $xml .= "\t\t\t\t<free_shipping>".$discount['free_shipping']."</free_shipping>\r\n";
                } else {
                    $xml .= "\t\t\t\t<free_shipping>0</free_shipping>\r\n";
                }
                $xml .= "\t\t\t</discount>\r\n";
            }
        }
        $xml .= "\t\t</discounts>\r\n";
    }
    return $xml;
}
 /*
 * Lit les messages de la commande
 */
function OrderMessages($id_order)
{
    $MessagesOrder ='';
    
    /* Lit tout les messages de la commande */
    if (Configuration::get('ATOOSYNC_ORDER_MESSAGES') == 'All') {
        $IdLang = IdLangDefault();
        if (class_exists('CustomerMessage')) {
            $messages = CustomerMessage::getMessagesByOrderId($id_order, true);
        } else {
            $messages = Message::getMessagesByOrderId($id_order, true);
        }
        if (sizeof($messages)) {
            foreach ($messages as $message) {
                $MessagesOrder .=(($message['elastname']) ? ($message['efirstname'].' '.$message['elastname']) : ($message['cfirstname'].' '.$message['clastname']));
                $MessagesOrder .= ' - '.Tools::displayDate($message['date_add'], null, true);
                $MessagesOrder .='\n';
                $MessagesOrder .=  html_entity_decode(str_replace("\r\n", '\n', $message['message']), ENT_QUOTES, 'UTF-8').'\n';
                $MessagesOrder .='\n';
            }
        }
    }
    /* Lit le premier message de la commande */
    elseif (Configuration::get('ATOOSYNC_ORDER_MESSAGES') == 'First') {
        $tmp = Db::getInstance()->getValue('SELECT `message` FROM `'._DB_PREFIX_.'message` WHERE `id_order` ='.(int)($id_order).' AND `id_customer` <> 0 AND `private`=0 ORDER BY `id_message`');
        $MessagesOrder = html_entity_decode(str_replace("\r\n", '\n', $tmp), ENT_QUOTES, 'UTF-8');
    }
        
    /* Cutomise le message si besoin */
    $MessagesOrder .=  CustomizeOrderMessages($id_order);
        
    return $MessagesOrder;
}
/*
 * Retourne le XML des informations du client de la commande
 */
function NodeCustomer($id_order, $id_customer)
{
    $xml ="";
    
    // si le id_customer = 0 alors recherche sur l'adresse de facturation
    if ($id_customer == 0) {
        $order = new Order((int)$id_order);
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

        $firstname = (string)$customer->firstname;
        $lastname = (string)$customer->lastname;
        if (empty($firstname)) {
            $firstname='John';
        }
        if (empty($lastname)) {
            $lastname='DOE';
        }
    
        $xml .= "\t\t<client_id>".$customer->id."</client_id>\r\n";
        $xml .= "\t\t<client_prenom>".escapeXMLString($firstname)."</client_prenom>\r\n";
        $xml .= "\t\t<client_nom>".escapeXMLString($lastname)."</client_nom>\r\n";
        $xml .= "\t\t<client_email>".escapeXMLString($customer->email)."</client_email>\r\n";
        $xml .= "\t\t<client_civilite>".escapeXMLString($civility)."</client_civilite>\r\n";
        $xml .= "\t\t<client_code_client>".escapeXMLString(CustomizeCustomerAccount($id_customer, $id_order))."</client_code_client>\r\n";
        $xml .= CustomizeCustomerOrder($id_order, $id_customer);
    }
    return $xml;
}
/*
 * Retourne le XML des adresses de livraison et de facturation
 * L'intitulé de l'adresse doit etre unique dans Sage pour une même client !
 */
function NodeAddresses($order)
{
    $xml = CustomizeNodeAddresses($order);
    if (empty($xml)) {
        $customer = new Customer((int)($order->id_customer));
        
        // Adresse de facturation
        // si l'adresse de facturation est vide alors utilise l'adresse de livraison
        if ($order->id_address_invoice != 0) {
            $address = new Address((int)($order->id_address_invoice));
        } else {
            $address = new Address((int)($order->id_address_delivery));
        }
        validAddress($address);
        
        // remplace le téléphone par le téléphone mobile si le téléphone est vide
        // sinon met le telephone mobile dans télécopie
        if (empty($address->phone)) {
            $phone = $address->phone_mobile;
            $fax  ='';
            $mobile = '';
        } else {
            $phone = $address->phone;
            $fax  =$address->phone_mobile;
            $mobile = '';
        }
        
        $xml ='';
        $xml .= "\t\t<invoice_name>".escapeXMLString($address->alias)."</invoice_name>\r\n";
        $xml .= "\t\t<invoice_company>".escapeXMLString($address->company)."</invoice_company>\r\n";
        $xml .= "\t\t<invoice_lastname>".escapeXMLString($address->lastname)."</invoice_lastname>\r\n";
        $xml .= "\t\t<invoice_firstname>".escapeXMLString($address->firstname)."</invoice_firstname>\r\n";
        $xml .= "\t\t<invoice_contact>".escapeXMLString($address->lastname.' '.$address->firstname)."</invoice_contact>\r\n";
        $xml .= "\t\t<invoice_adresse1>".escapeXMLString($address->address1)."</invoice_adresse1>\r\n";
        $xml .= "\t\t<invoice_adresse2>".escapeXMLString($address->address2)."</invoice_adresse2>\r\n";
        $xml .= "\t\t<invoice_postcode>".escapeXMLString($address->postcode)."</invoice_postcode>\r\n";
        $xml .= "\t\t<invoice_city>".escapeXMLString($address->city)."</invoice_city>\r\n";
        $xml .= "\t\t<invoice_state>".escapeXMLString(State::getNameById($address->id_state))."</invoice_state>\r\n";
        $xml .= "\t\t<invoice_country>".escapeXMLString($address->country)."</invoice_country>\r\n";
        $xml .= "\t\t<invoice_phone>".escapeXMLString($phone)."</invoice_phone>\r\n";
        $xml .= "\t\t<invoice_fax>".escapeXMLString($fax)."</invoice_fax>\r\n";
        $xml .= "\t\t<invoice_phone_mobile>".escapeXMLString($mobile)."</invoice_phone_mobile>\r\n";
        $xml .= "\t\t<invoice_email>".escapeXMLString($customer->email)."</invoice_email>\r\n";
        $xml .= "\t\t<invoice_other>".escapeXMLString($address->other)."</invoice_other>\r\n";
        $xml .= "\t\t<invoice_vat_number>".escapeXMLString($address->vat_number)."</invoice_vat_number>\r\n";
        $xml .= "\t\t<invoice_dni>".escapeXMLString($address->dni)."</invoice_dni>\r\n";

        // Adresse de livraison
        $address = new Address((int)($order->id_address_delivery));
        validAddress($address);
        
        // remplace le téléphone par le téléphone mobile si le téléphone est vide
        // sinon met le telephone mobile dans télécopie
        if (empty($address->phone)) {
            $phone = $address->phone_mobile;
            $fax  ='';
            $mobile = '';
        } else {
            $phone = $address->phone;
            $fax  =$address->phone_mobile;
            $mobile = '';
        }
        
        $xml .= "\t\t<delivery_name>".escapeXMLString($address->alias)."</delivery_name>\r\n";
        $xml .= "\t\t<delivery_company>".escapeXMLString($address->company)."</delivery_company>\r\n";
        $xml .= "\t\t<delivery_lastname>".escapeXMLString($address->lastname)."</delivery_lastname>\r\n";
        $xml .= "\t\t<delivery_firstname>".escapeXMLString($address->firstname)."</delivery_firstname>\r\n";
        $xml .= "\t\t<delivery_contact>".escapeXMLString($address->lastname.' '.$address->firstname)."</delivery_contact>\r\n";
        $xml .= "\t\t<delivery_adresse1>".escapeXMLString($address->address1)."</delivery_adresse1>\r\n";
        $xml .= "\t\t<delivery_adresse2>".escapeXMLString($address->address2)."</delivery_adresse2>\r\n";
        $xml .= "\t\t<delivery_postcode>".escapeXMLString($address->postcode)."</delivery_postcode>\r\n";
        $xml .= "\t\t<delivery_city>".escapeXMLString($address->city)."</delivery_city>\r\n";
        $xml .= "\t\t<delivery_state>".escapeXMLString(State::getNameById($address->id_state))."</delivery_state>\r\n";
        $xml .= "\t\t<delivery_country>".escapeXMLString($address->country)."</delivery_country>\r\n";
        $xml .= "\t\t<delivery_phone>".escapeXMLString($phone)."</delivery_phone>\r\n";
        $xml .= "\t\t<delivery_fax>".escapeXMLString($fax)."</delivery_fax>\r\n";
        $xml .= "\t\t<delivery_phone_mobile>".escapeXMLString($mobile)."</delivery_phone_mobile>\r\n";
        $xml .= "\t\t<delivery_email>".escapeXMLString($customer->email)."</delivery_email>\r\n";
        $xml .= "\t\t<delivery_other>".escapeXMLString($address->other)."</delivery_other>\r\n";
        $xml .= "\t\t<delivery_vat_number>".escapeXMLString($address->vat_number)."</delivery_vat_number>\r\n";
        $xml .= "\t\t<delivery_dni>".escapeXMLString($address->dni)."</delivery_dni>\r\n";
    }
    return $xml;
}
function validAddress(&$address)
{
    if (empty($address->alias)) {
        $address->alias='My Address';
    }
    if (empty($address->lastname)) {
        $address->lastname='DOE';
    }
    if (empty($address->firstname)) {
        $address->firstname='John';
    }
}
/*
 * Retourne le XML des l'adresse du Module TNT
 */
function NodeTNTAddress($order)
{
    $xml = CustomizeTNTAddress($order);
    if (empty($xml)) {
        $address = new Address((int)($order->id_address_delivery));
        $country=  new country($address->id_country);
        $country_shop=  new country((int)Configuration::get('PS_SHOP_COUNTRY_ID'));
        $customer = new Customer((int)($order->id_customer));
        $carrier = new Carrier((int)($order->id_carrier));
        $weight = $order->getTotalWeight();
        $xml ='';
        
    
        // si le module TNT est installé
        $mod = Module::getInstanceByName('tntcarrier');
        if ($mod) {
            $orderInfoTnt = new OrderInfoTnt((int)($order->id));
            $info = $orderInfoTnt->getInfo();
      
            if (is_array($info)) {
                $option = Db::getInstance()->getRow('SELECT t.option
                                            FROM `'._DB_PREFIX_.'tnt_carrier_option` as t , `'._DB_PREFIX_.'orders` as o
                                            WHERE t.id_carrier = o.id_carrier AND o.id_order = "'.(int)$order->id.'"');
                                            
                $saturday = 0;
                if ($option['option'] == 'JS') {
                    $saturday = 1;
                }
          
                // Destinataire de la commande
        
                if (isset($info[0])) {
                    $xml .= "\t\t<tnt_shipping_number>".escapeXMLString($info[0]['shipping_number'])."</tnt_shipping_number>\r\n";
                    $xml .= "\t\t<tnt_lastname>".escapeXMLString($info[0]['lastname'])."</tnt_lastname>\r\n";
                    $xml .= "\t\t<tnt_firstname>".escapeXMLString($info[0]['firstname'])."</tnt_firstname>\r\n";
                    $xml .= "\t\t<tnt_address1>".escapeXMLString($info[0]['address1'])."</tnt_address1>\r\n";
                    $xml .= "\t\t<tnt_address2>".escapeXMLString($info[0]['address2'])."</tnt_address2>\r\n";
                    $xml .= "\t\t<tnt_postcode>".escapeXMLString($info[0]['postcode'])."</tnt_postcode>\r\n";
                    $xml .= "\t\t<tnt_city>".escapeXMLString($info[0]['city'])."</tnt_city>\r\n";
                    $xml .= "\t\t<tnt_country>".escapeXMLString($address->country)."</tnt_country>\r\n";
                    $xml .= "\t\t<tnt_country_code>".escapeXMLString($country->iso_code)."</tnt_country_code>\r\n";
                    $xml .= "\t\t<tnt_phone>".escapeXMLString($info[0]['phone'])."</tnt_phone>\r\n";
                    $xml .= "\t\t<tnt_phone_mobile>".escapeXMLString($info[0]['phone_mobile'])."</tnt_phone_mobile>\r\n";
                    $xml .= "\t\t<tnt_email>".escapeXMLString($info[0]['email'])."</tnt_email>\r\n";
                    $xml .= "\t\t<tnt_company>".escapeXMLString($info[0]['company'])."</tnt_company>\r\n";
                } else {
                    $xml .= "\t\t<tnt_shipping_number>".''."</tnt_shipping_number>\r\n";
                    $xml .= "\t\t<tnt_lastname>".escapeXMLString($address->lastname)."</tnt_lastname>\r\n";
                    $xml .= "\t\t<tnt_firstname>".escapeXMLString($address->firstname)."</tnt_firstname>\r\n";
                    $xml .= "\t\t<tnt_address1>".escapeXMLString($address->address1)."</tnt_address1>\r\n";
                    $xml .= "\t\t<tnt_address2>".escapeXMLString($address->address2)."</tnt_address2>\r\n";
                    $xml .= "\t\t<tnt_postcode>".escapeXMLString($address->postcode)."</tnt_postcode>\r\n";
                    $xml .= "\t\t<tnt_country>".escapeXMLString($address->country)."</tnt_country>\r\n";
                    $xml .= "\t\t<tnt_country_code>".escapeXMLString($country->iso_code)."</tnt_country_code>\r\n";
                    $xml .= "\t\t<tnt_city>".escapeXMLString($address->city)."</tnt_city>\r\n";
                    $xml .= "\t\t<tnt_phone>".escapeXMLString($address->phone)."</tnt_phone>\r\n";
                    $xml .= "\t\t<tnt_phone_mobile>".escapeXMLString($address->phone_mobile)."</tnt_phone_mobile>\r\n";
                    $xml .= "\t\t<tnt_email>".escapeXMLString($customer->email)."</tnt_email>\r\n";
                    $xml .= "\t\t<tnt_company>".escapeXMLString($address->company)."</tnt_company>\r\n";
                }
        
                // Poids de la commande
                $xml .= "\t\t<tnt_weight>".number_format(round($weight, 3), 3, '.', '')."</tnt_weight>\r\n";
        
                // Date de la livraison
                if (isset($info[2])) {
                    $xml .= "\t\t<tnt_delivery_date>".escapeXMLString($info[2]['delivery_date'])."</tnt_delivery_date>\r\n";
                } else {
                    $xml .= "\t\t<tnt_delivery_date>".escapeXMLString($order->delivery_date)."</tnt_delivery_date>\r\n";
                }
        
        
                // Codes services
                if (isset($info[3])) {
                    $xml .= "\t\t<tnt_option>".escapeXMLString($info[3]['option'])."</tnt_option>\r\n";
                } else {
                    $xml .= "\t\t<tnt_option>".''."</tnt_option>\r\n";
                }
        
                // information Relais colis
                if (isset($info[4])) {
                    $xml .= "\t\t<tnt_relais_code>".escapeXMLString($info[4]['code'])."</tnt_relais_code>\r\n";
                    $xml .= "\t\t<tnt_relais_name>".escapeXMLString($info[4]['name'])."</tnt_relais_name>\r\n";
                    $xml .= "\t\t<tnt_relais_address>".escapeXMLString($info[4]['address'])."</tnt_relais_address>\r\n";
                    $xml .= "\t\t<tnt_relais_zipcode>".escapeXMLString($info[4]['zipcode'])."</tnt_relais_zipcode>\r\n";
                    $xml .= "\t\t<tnt_relais_city>".escapeXMLString($info[4]['city'])."</tnt_relais_city>\r\n";
                    $xml .= "\t\t<tnt_relais_due_date>".escapeXMLString($info[4]['due_date'])."</tnt_relais_due_date>\r\n";
                } else {
                    $xml .= "\t\t<tnt_relais_code>".''."</tnt_relais_code>\r\n";
                    $xml .= "\t\t<tnt_relais_name>".''."</tnt_relais_name>\r\n";
                    $xml .= "\t\t<tnt_relais_address>".''."</tnt_relais_address>\r\n";
                    $xml .= "\t\t<tnt_relais_zipcode>".''."</tnt_relais_zipcode>\r\n";
                    $xml .= "\t\t<tnt_relais_city>".''."</tnt_relais_city>\r\n";
                    $xml .= "\t\t<tnt_relais_due_date>".''."</tnt_relais_due_date>\r\n";
                }
        
                // Livraison le samedi
                $xml .= "\t\t<tnt_saturday>".escapeXMLString($saturday)."</tnt_saturday>\r\n";
        
                // expediteur
                $xml .= "\t\t<tnt_shipping_company>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_COMPANY'))."</tnt_shipping_company>\r\n";
                $xml .= "\t\t<tnt_shipping_address1>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_ADDRESS1'))."</tnt_shipping_address1>\r\n";
                $xml .= "\t\t<tnt_shipping_address2>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_ADDRESS2'))."</tnt_shipping_address2>\r\n";
                $xml .= "\t\t<tnt_shipping_zipcode>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_ZIPCODE'))."</tnt_shipping_zipcode>\r\n";
                $xml .= "\t\t<tnt_shipping_city>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_CITY'))."</tnt_shipping_city>\r\n";
                $xml .= "\t\t<tnt_shipping_country>".escapeXMLString(Configuration::get('PS_SHOP_COUNTRY'))."</tnt_shipping_country>\r\n";
                $xml .= "\t\t<tnt_shipping_country_code>".escapeXMLString($country_shop->iso_code)."</tnt_shipping_country_code>\r\n";
                $xml .= "\t\t<tnt_shipping_firstname>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_FIRSTNAME'))."</tnt_shipping_firstname>\r\n";
                $xml .= "\t\t<tnt_shipping_lastname>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_LASTNAME'))."</tnt_shipping_lastname>\r\n";
                $xml .= "\t\t<tnt_shipping_email>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_EMAIL'))."</tnt_shipping_email>\r\n";
                $xml .= "\t\t<tnt_shipping_phone>".escapeXMLString(Configuration::get('TNT_CARRIER_SHIPPING_PHONE'))."</tnt_shipping_phone>\r\n";
            }
        }
        
        // Si le XML est vide alors utilise l'adresse de livraison
        if (empty($xml)) {
            $country=  new country($address->id_country);

            $xml .= "\t\t<tnt_shipping_number>".''."</tnt_shipping_number>\r\n";
            $xml .= "\t\t<tnt_lastname>".escapeXMLString($address->lastname)."</tnt_lastname>\r\n";
            $xml .= "\t\t<tnt_firstname>".escapeXMLString($address->firstname)."</tnt_firstname>\r\n";
            $xml .= "\t\t<tnt_address1>".escapeXMLString($address->address1)."</tnt_address1>\r\n";
            $xml .= "\t\t<tnt_address2>".escapeXMLString($address->address2)."</tnt_address2>\r\n";
            $xml .= "\t\t<tnt_postcode>".escapeXMLString($address->postcode)."</tnt_postcode>\r\n";
            $xml .= "\t\t<tnt_city>".escapeXMLString($address->city)."</tnt_city>\r\n";
            $xml .= "\t\t<tnt_country>".escapeXMLString($address->country)."</tnt_country>\r\n";
            $xml .= "\t\t<tnt_country_code>".escapeXMLString($country->iso_code)."</tnt_country_code>\r\n";
            $xml .= "\t\t<tnt_phone>".escapeXMLString($address->phone)."</tnt_phone>\r\n";
            $xml .= "\t\t<tnt_phone_mobile>".escapeXMLString($address->phone_mobile)."</tnt_phone_mobile>\r\n";
            $xml .= "\t\t<tnt_email>".escapeXMLString($customer->email)."</tnt_email>\r\n";
            $xml .= "\t\t<tnt_company>".escapeXMLString($address->company)."</tnt_company>\r\n";
            $xml .= "\t\t<tnt_weight>".number_format(round($weight, 3), 3, '.', '')."</tnt_weight>\r\n";
            $xml .= "\t\t<tnt_delivery_date>".escapeXMLString($order->delivery_date)."</tnt_delivery_date>\r\n";
            $xml .= "\t\t<tnt_option>".''."</tnt_option>\r\n";
            $xml .= "\t\t<tnt_relais_code>".''."</tnt_relais_code>\r\n";
            $xml .= "\t\t<tnt_relais_name>".''."</tnt_relais_name>\r\n";
            $xml .= "\t\t<tnt_relais_address>".''."</tnt_relais_address>\r\n";
            $xml .= "\t\t<tnt_relais_zipcode>".''."</tnt_relais_zipcode>\r\n";
            $xml .= "\t\t<tnt_relais_city>".''."</tnt_relais_city>\r\n";
            $xml .= "\t\t<tnt_relais_due_date>".''."</tnt_relais_due_date>\r\n";
            $xml .= "\t\t<tnt_saturday>".'0'."</tnt_saturday>\r\n";
            // expediteur
            $xml .= "\t\t<tnt_shipping_company>".escapeXMLString(Configuration::get('PS_SHOP_NAME'))."</tnt_shipping_company>\r\n";
            $xml .= "\t\t<tnt_shipping_address1>".escapeXMLString(Configuration::get('PS_SHOP_ADDR1'))."</tnt_shipping_address1>\r\n";
            $xml .= "\t\t<tnt_shipping_address2>".escapeXMLString(Configuration::get('PS_SHOP_ADDR2'))."</tnt_shipping_address2>\r\n";
            $xml .= "\t\t<tnt_shipping_zipcode>".escapeXMLString(Configuration::get('PS_SHOP_CODE'))."</tnt_shipping_zipcode>\r\n";
            $xml .= "\t\t<tnt_shipping_city>".escapeXMLString(Configuration::get('PS_SHOP_CITY'))."</tnt_shipping_city>\r\n";
            $xml .= "\t\t<tnt_shipping_country>".escapeXMLString(Configuration::get('PS_SHOP_COUNTRY'))."</tnt_shipping_country>\r\n";
            $xml .= "\t\t<tnt_shipping_country_code>".escapeXMLString($country_shop->iso_code)."</tnt_shipping_country_code>\r\n";
            $xml .= "\t\t<tnt_shipping_firstname>".''."</tnt_shipping_firstname>\r\n";
            $xml .= "\t\t<tnt_shipping_lastname>".''."</tnt_shipping_lastname>\r\n";
            $xml .= "\t\t<tnt_shipping_email>".escapeXMLString(Configuration::get('PS_SHOP_EMAIL'))."</tnt_shipping_email>\r\n";
            $xml .= "\t\t<tnt_shipping_phone>".escapeXMLString(Configuration::get('PS_SHOP_PHONE'))."</tnt_shipping_phone>\r\n";
        }
    }
    return $xml;
}
/*
 * Retourne le XML des l'adresse du Module pour Expeditor Inet
 */
function NodeExpeditorInetAddress($order)
{
    $xml = CustomizeExpeditorInetAddress($order);
    if (empty($xml)) {
        $address = new Address((int)($order->id_address_delivery));
        $customer = new Customer((int)($order->id_customer));
        $carrier = new Carrier((int)($order->id_carrier));
        $weight = $order->getTotalWeight();
        $xml ='';
        
        // si la table existe
        $query = "SHOW TABLES LIKE '"._DB_PREFIX_.'socolissimo_delivery_info'."'";
        $result = Db::getInstance()->ExecuteS($query);
        $exist = Db::getInstance()->numRows();
        if ($exist == 1) {
            $query = 'SELECT * FROM `'._DB_PREFIX_.'socolissimo_delivery_info` WHERE `id_cart`='.(int)($order->id_cart).' AND `id_customer`='.(int)($order->id_customer);
            $resultat = Db::getInstance()->ExecuteS($query);
            if ($resultat) {
                foreach ($resultat as $k => $row) {
                    $xml .= "\t\t<expeditorinet_deliverymode>".escapeXMLString($row['delivery_mode'])."</expeditorinet_deliverymode>\r\n";
                    $xml .= "\t\t<expeditorinet_cecivility>".escapeXMLString('')."</expeditorinet_cecivility>\r\n";
                    $xml .= "\t\t<expeditorinet_cefirstname>".escapeXMLString($row['prfirstname'])."</expeditorinet_cefirstname>\r\n";
                    $xml .= "\t\t<expeditorinet_cename>".escapeXMLString($row['prname'])."</expeditorinet_cename>\r\n";
                    $xml .= "\t\t<expeditorinet_cecompanyname>".escapeXMLString($row['cecompanyname'])."</expeditorinet_cecompanyname>\r\n";
                    $xml .= "\t\t<expeditorinet_ceemail>".escapeXMLString($row['ceemail'])."</expeditorinet_ceemail>\r\n";
                    $xml .= "\t\t<expeditorinet_cephonenumber>".escapeXMLString($row['cephonenumber'])."</expeditorinet_cephonenumber>\r\n";
                    $xml .= "\t\t<expeditorinet_ceadress1>".escapeXMLString($row['pradress1'])."</expeditorinet_ceadress1>\r\n";
                    $xml .= "\t\t<expeditorinet_ceadress2>".escapeXMLString($row['pradress2'])."</expeditorinet_ceadress2>\r\n";
                    $xml .= "\t\t<expeditorinet_ceadress3>".escapeXMLString($row['pradress3'])."</expeditorinet_ceadress3>\r\n";
                    $xml .= "\t\t<expeditorinet_ceadress4>".escapeXMLString($row['pradress4'])."</expeditorinet_ceadress4>\r\n";
                    $xml .= "\t\t<expeditorinet_cezipcode>".escapeXMLString($row['przipcode'])."</expeditorinet_cezipcode>\r\n";
                    $xml .= "\t\t<expeditorinet_cetown>".escapeXMLString($row['prtown'])."</expeditorinet_cetown>\r\n";
                    $xml .= "\t\t<expeditorinet_cecountry>".escapeXMLString($row['cecountry'])."</expeditorinet_cecountry>\r\n";
                    $xml .= "\t\t<expeditorinet_cedeliveryinformation>".escapeXMLString($row['cedeliveryinformation'])."</expeditorinet_cedeliveryinformation>\r\n";
                    $xml .= "\t\t<expeditorinet_cedoorcode1>".escapeXMLString($row['cedoorcode1'])."</expeditorinet_cedoorcode1>\r\n";
                    $xml .= "\t\t<expeditorinet_cedoorcode2>".escapeXMLString($row['cedoorcode2'])."</expeditorinet_cedoorcode2>\r\n";
                    $xml .= "\t\t<expeditorinet_ceentryphone>".escapeXMLString('')."</expeditorinet_ceentryphone>\r\n";
                    $xml .= "\t\t<expeditorinet_prid>".escapeXMLString($row['prid'])."</expeditorinet_prid>\r\n";
                    $xml .= "\t\t<expeditorinet_weight>".number_format(round($weight, 3), 3, '.', '')."</expeditorinet_weight>\r\n";
                }
            }
        }
        
        // Si le XML est vide alors utilise l'adresse de livraison
        if (empty($xml)) {
            $xml .= "\t\t<expeditorinet_deliverymode>".escapexmlstring('')."</expeditorinet_deliverymode>\r\n";
            $xml .= "\t\t<expeditorinet_cecivility>".escapexmlstring('')."</expeditorinet_cecivility>\r\n";
            $xml .= "\t\t<expeditorinet_cefirstname>".escapexmlstring($address->firstname)."</expeditorinet_cefirstname>\r\n";
            $xml .= "\t\t<expeditorinet_cename>".escapexmlstring($address->lastname)."</expeditorinet_cename>\r\n";
            $xml .= "\t\t<expeditorinet_cecompanyname>".escapexmlstring($address->company)."</expeditorinet_cecompanyname>\r\n";
            $xml .= "\t\t<expeditorinet_ceemail>".escapexmlstring($customer->email)."</expeditorinet_ceemail>\r\n";
            $xml .= "\t\t<expeditorinet_cephonenumber>".escapexmlstring($address->phone_mobile)."</expeditorinet_cephonenumber>\r\n";
            $xml .= "\t\t<expeditorinet_ceadress1>".escapexmlstring($address->address1)."</expeditorinet_ceadress1>\r\n";
            $xml .= "\t\t<expeditorinet_ceadress2>".escapexmlstring($address->address2)."</expeditorinet_ceadress2>\r\n";
            $xml .= "\t\t<expeditorinet_ceadress3>".escapexmlstring('')."</expeditorinet_ceadress3>\r\n";
            $xml .= "\t\t<expeditorinet_ceadress4>".escapexmlstring('')."</expeditorinet_ceadress4>\r\n";
            $xml .= "\t\t<expeditorinet_cezipcode>".escapexmlstring($address->postcode)."</expeditorinet_cezipcode>\r\n";
            $xml .= "\t\t<expeditorinet_cetown>".escapexmlstring($address->city)."</expeditorinet_cetown>\r\n";
            $xml .= "\t\t<expeditorinet_cecountry>".escapexmlstring(Country::getNameById(IdLangDefault(), $address->id_country))."</expeditorinet_cecountry>\r\n";
            $xml .= "\t\t<expeditorinet_cedeliveryinformation>".escapexmlstring($address->other)."</expeditorinet_cedeliveryinformation>\r\n";
            $xml .= "\t\t<expeditorinet_cedoorcode1>".escapexmlstring('')."</expeditorinet_cedoorcode1>\r\n";
            $xml .= "\t\t<expeditorinet_cedoorcode2>".escapexmlstring('')."</expeditorinet_cedoorcode2>\r\n";
            $xml .= "\t\t<expeditorinet_ceentryphone>".escapexmlstring('')."</expeditorinet_ceentryphone>\r\n";
            $xml .= "\t\t<expeditorinet_prid>".escapexmlstring('')."</expeditorinet_prid>\r\n";
            $xml .= "\t\t<expeditorinet_weight>".number_format(round($weight, 3), 3, '.', '')."</expeditorinet_weight>\r\n";
        }
    }
    return $xml;
}
/*
 *	Retourne le noeud XML des articles de la commande
 */
function NodeProducts($order)
{
    $xml = CustomizeNodeProducts($order);
    if (empty($xml)) {
        // la précision des prix
        $precision = (int)(Configuration::get('ATOOSYNC_ORDER_ROUND'));
        if ($precision == '') {
            $precision= 2;
        }
            
        $xml = "\t\t<products>\r\n";
        
        // Appel la fonction pour ajouter des articles dans la commande
        $xml .= CustomizeOrderAddProductsBefore($order);
        
        $products = $order->getProducts();
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
            $final_price = $product['product_price_wt'] / (($product['tax_rate'] / 100) + 1);
            $final_price_wt = $product['product_price_wt'];
            
            /* Prix sans la réduction PrestaShop 1.5 ou 1.6 */
            if (isPrestaShop15() or isPrestaShop16()  or isPrestaShop17()) {
                
                $final_price = (float)$product['unit_price_tax_excl'] ;
                $final_price_wt = (float)$product['unit_price_tax_incl'];
             
                /* Si le prix de l'article est différent du prix d'origine */
                if ($product['original_product_price'] <> $product['product_price']) {
                    $price = $product['original_product_price'] ;
                    $price_wt = $price * (($product['tax_rate'] / 100) +1);
                } else {
                    $price = $product['product_price'];
                    $price_wt = $price * (($product['tax_rate'] / 100) +1);
                }
            }
            /* Prix sans la réduction PrestaShop 1.4 */
            elseif (isPrestaShop14()) {
                /* Si il y a une remise par quantité */
                if ($product['product_quantity_discount'] != '0.000000') {
                    $price = $product['product_quantity_discount'] / (($product['tax_rate'] / 100) + 1);
                    $price_wt = $product['product_quantity_discount'];
                } else {
                    $price = $product['product_price'];
                    $price_wt = $price * (($product['tax_rate'] / 100) +1);
                }
            } else {
                $price = $final_price;
                $price_wt = $final_price_wt;
            }

                            
            // si il y a un conditionnement alors divise les prix de vente et multiplie la quantité
            /*
                10/03/2015 Suppression de la division après validation de la modification du calcul
                il n'est plus necessaire de diviser le prix.
            if 	($packaging_reference !='' AND ($atoosync_conditionnement_quantity) != 0)
            {
                //$quantity = $quantity * $atoosync_conditionnement_quantity;
                $price = $price / $atoosync_conditionnement_quantity;
                $price_wt = $price_wt / $atoosync_conditionnement_quantity;
                $final_price = $final_price / $atoosync_conditionnement_quantity;
                $final_price_wt = $final_price_wt / $atoosync_conditionnement_quantity;
            }
            */
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
            
            /* Ajoute les champs supplémentaires de l'article */
            $xml .= CustomizeOrderProduct($product['id_order_detail']);
            
            $xml .= "\t\t\t</product>\r\n";
        }
        
        // Appel la fonction pour ajouter des articles dans la commande
        $xml .= CustomizeOrderAddProductsAfter($order);
        
        $xml .= "\t\t</products>\r\n";
    }
    return $xml;
}

/*
 *	Retourne le prefix des factures
 */
function GetInvoicePrefix()
{
    return Configuration::get('PS_INVOICE_PREFIX', IdLangDefault());
}

/*
 *	Retourne le id_tax du transporteur
 */
function GetIdTaxCarrier($id_carrier)
{
    $carrierobj = new Carrier($id_carrier);
    return $carrierobj->id_tax;
}

/*
 *	Retourne le taux de la taxe
 */
function GetTaxRate($id_tax)
{
    $Taxobj = new Tax($id_tax);
    $rate = number_format($Taxobj->rate, 2, '.', '');
    return $rate;
}

/*
 * Renseigne le champ atoosync_transfert_gescom à 1 de la commande
 */
function SetOrderTransferred($id)
{
    $succes = 0;
    
    if (!empty($id) and is_numeric($id)) {
        $query = "UPDATE `"._DB_PREFIX_."orders` SET `atoosync_transfert_gescom`='1' WHERE `id_order`='".(int)($id)."'";
        Db::getInstance()->Execute($query);
        $succes = 1;
        
        // execute la fonction de customisation
        CustomizeOrderTransferred($id);
    }
    return $succes;
}
/*
 * Enregistre le nouveau statut de la commande
 */
function ChangeOrderStatut($id, $newstatut, $number)
{
    $succes = 0; 
    if ((int)$id > 0){          
        /* Si la commande existe*/
        $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` ='.(int)($id));
        if ($id_order) {
                       
            /* Enregistre le numéro du document depuis Atoo-Sync */
            /* Doit être fait avant la création de l'objet Order */
            if (!empty($number)) {
                $query = "UPDATE `"._DB_PREFIX_."orders` SET `atoosync_number`='".pSQL($number)."' WHERE `id_order`='".(int)($id_order)."'";
                Db::getInstance()->Execute($query);
            }
                       
            /* Recopie le numéro de document Sage dans la référence PrestaShop */
            if (!empty($number)) {
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    if (Configuration::get('ATOOSYNC_ORDER_COPY_REFERENCE') == 'Yes') {
                        $old_reference = Db::getInstance()->getValue('SELECT `reference` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` ='.(int)($id_order));
                       
                        // modifie la référence dans la table ps_orders
                        $query = "UPDATE `"._DB_PREFIX_."orders` SET `reference`='".pSQL($number)."' WHERE `id_order`='".(int)($id_order)."'";
                        Db::getInstance()->Execute($query);

                        
                        // modifie la référence dans la table ps_order_payment
                        $query = "UPDATE `"._DB_PREFIX_."order_payment` SET `order_reference`='".pSQL($number)."' WHERE `order_reference`='".pSQL($old_reference)."'";
                        Db::getInstance()->Execute($query);
                    }
                }
            }
      
            if ((int)$newstatut > 0) {
                // cherche si la commande n'a pas déjà été dans ce statuts
                // le statut est modifié uniquement si la commande n'a jamais eu ce statut.
                $id_order_history = Db::getInstance()->getValue('SELECT `id_order_history` FROM `'._DB_PREFIX_.'order_history` WHERE `id_order` ='.(int)($id_order).' AND `id_order_state`='.(int)($newstatut));
                /* Créé le nouveau historique avec le nouveau statut */
                if (!$id_order_history) {
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $order = new Order((int)($id_order));
                        
                        $id_employee = Db::getInstance()->getValue('SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` WHERE `id_profile` =1');
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->id_employee = $id_employee;

                        $use_existings_payment = false;
                        if (!$order->hasInvoice()) {
                            $use_existings_payment = true;
                        }
                        $history->changeIdOrderState((int)($newstatut), $order->id, $use_existings_payment);

                        $carrier = new Carrier($order->id_carrier, $order->id_lang);
                        $templateVars = array();
                        if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                            $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                        }
                                            
                        if ($history->addWithemail(true, $templateVars)) {
                            $succes = 1;
                        }
                    } elseif (isPrestaShop14()) {
                        $order->setCurrentState((int)($newstatut));
                        $succes = 1;
                    } else {
                        $history = new OrderHistory();
                        $history->id_order = $id_order;
                        $history->id_employee = 0;
                        $history->changeIdOrderState((int)($newstatut), (int)($id_order));
                        if ($history->addWithemail()) {
                            $succes = 1;
                        }
                    }
                } else {
                    $succes = 1;
                }
            }
        }
    }
    return $succes;
}
/*
 * Enregistre le numero de transporteur dans la commande
 */
function SetOrderShippingNumber($id, $shipping_number)
{
    $succes = 0;
    if (!empty($id) and is_numeric($id)) {
        /* Si le numéro de suivi n'est pas vide */
        if (!empty($shipping_number)) {
            /* Si la commande existe*/
            $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` ='.(int)($id));
            if ($id_order) {
                /* Si le numéro de suivi n'est pas déjà renseigné */
                $number = Db::getInstance()->getValue('SELECT `shipping_number` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` ='.(int)($id_order));
                if (empty($number)) {
                    $order = new Order((int)($id_order));
                    
                    /* Enregistre le numéro du transporteur */
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $query = "UPDATE `"._DB_PREFIX_."orders` SET `shipping_number`='".pSQL($shipping_number)."' WHERE `id_order`='".(int)($id_order)."'";
                        Db::getInstance()->Execute($query);
                        $query = "UPDATE `"._DB_PREFIX_."order_carrier` SET `tracking_number`='".pSQL($shipping_number)."' WHERE `id_order`='".(int)($id_order)."'";
                        Db::getInstance()->Execute($query);
                            
                        /* Envoi l'email de notification */
                        $customer = new Customer((int)$order->id_customer);
                        $carrier = new Carrier((int)$order->id_carrier, $order->id_lang);
                        $templateVars = array(
                            '{followup}' => str_replace('@', $shipping_number, $carrier->url),
                            '{firstname}' => $customer->firstname,
                            '{lastname}' => $customer->lastname,
                            '{id_order}' => $order->id,
                            '{shipping_number}' => $shipping_number,
                            '{order_name}' => $order->getUniqReference()
                        );
                        if (@Mail::Send(
                            (int)$order->id_lang,
                            'in_transit',
                            Mail::l('Package in transit', (int)$order->id_lang),
                            $templateVars,
                            $customer->email,
                            $customer->firstname.' '.$customer->lastname,
                            null,
                            null,
                            null,
                            null,
                            _PS_MAIL_DIR_,
                            true,
                            (int)$order->id_shop
                        )) {
                            Hook::exec('actionAdminOrdersTrackingNumberUpdate', array('order' => $order));
                        }
                    } elseif (isPrestaShop14()) {
                        $query = "UPDATE `"._DB_PREFIX_."orders` SET `shipping_number`='".pSQL($shipping_number)."' WHERE `id_order`='".(int)($id_order)."'";
                        Db::getInstance()->Execute($query);
                        
                        /* Envoi l'email de notification */
                        $customer = new Customer((int)($order->id_customer));
                        $carrier = new Carrier((int)($order->id_carrier));
                        $templateVars = array(
                            '{followup}' => str_replace('@', $shipping_number, $carrier->url),
                            '{firstname}' => $customer->firstname,
                            '{lastname}' => $customer->lastname,
                            '{id_order}' => (int)($order->id)
                        );
                        @Mail::Send(
                            (int)$order->id_lang,
                            'in_transit',
                            Mail::l('Package in transit', (int)$order->id_lang),
                            $templateVars,
                            $customer->email,
                            $customer->firstname.' '.$customer->lastname,
                            null,
                            null,
                            null,
                            null,
                            _PS_MAIL_DIR_,
                            true
                        );
                    } else {
                        $query = "UPDATE `"._DB_PREFIX_."orders` SET `shipping_number`='".pSQL($shipping_number)."' WHERE `id_order`='".(int)($id_order)."'";
                        Db::getInstance()->Execute($query);
                    }
                }
                $succes = 1;
            }
        }
    }
    return $succes;
}
/*
 * Enregistre le date de livraison dans la commande
 */
function SetDeliveryDate($id, $delivery_date)
{
    $succes = 0;
    if (!empty($id) and is_numeric($id)) {
        /* Si la date de livraison n'est pas vide */
        if (!empty($delivery_date)) {
            /* Si la commande existe*/
            $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` ='.(int)($id));
            if ($id_order) {
                $query = "UPDATE `"._DB_PREFIX_."orders` SET `delivery_date`='".pSQL($delivery_date)."' WHERE `id_order`='".(int)($id_order)."'";
                Db::getInstance()->Execute($query);
                
                $query = "UPDATE `"._DB_PREFIX_."order_carrier` SET `date_add`='".pSQL($delivery_date)."' WHERE `id_order`='".(int)($id_order)."'";
                Db::getInstance()->Execute($query);
                
                $succes = 1;
            }
        }
    }
    return $succes;
}
/*
 * Créer les règlements de la commande
 */
function SetOrderPayments($xml)
{
    $succes = 0;
    if (empty($xml)) {
        return 0;
    }

    // Si différent de PrestaShop 1.5.x
    if (!isPrestaShop15() and !isPrestaShop16() or isPrestaShop17()) {
        return 1;
    }
                
    $OrderXML = LoadXML(Tools::stripslashes($xml));
    if (empty($OrderXML)) {
        return 0;
    }
    
    /* Si la commande existe*/
    $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` ='.(int)($OrderXML->id_order));
    if ($id_order) {
        $order = new Order($id_order);
        $hasInvoice = $order->hasInvoice();
            
        // Supprime en premier les règlements existant.
        $orderpayments  = $order->getOrderPayments();
        foreach ($orderpayments as $orderpayment) {
            // Supprime le règlement sur les factures
            if ($hasInvoice) {
                Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_invoice_payment` WHERE `id_order_payment` = '.(int)($orderpayment->id));
            }
            
            // supprime le règlement
            $orderpayment->delete();
        }
        
        // La première facture de la commande
        $order_invoice = null;
        if ($hasInvoice) {
            $id_order_invoice = Db::getInstance()->getValue('SELECT `id_order_invoice` FROM `'._DB_PREFIX_.'order_invoice` WHERE `id_order` ='.(int)($order->id));
            if ($id_order_invoice) {
                $order_invoice = new OrderInvoice($id_order_invoice);
            }
        }
        // Créé les nouveaux règlements
        foreach ($OrderXML->payments->payment as $payment) {
            $order->addOrderPayment(
                (float)($payment->amount),
                                    (string)($payment->payment_method),
                                    null,
                                    null,
                                    (string)($payment->date),
                                    $order_invoice
            );
        }
        $succes = 1;
    }
    return $succes;
}
/*
 * Créer une commande
 */
function CreateOrder($xml)
{
    $succes = 0;
    if (empty($xml)) {
        return 0;
    }

    // Si différent de PrestaShop 1.5.x ou 1.6.x
    if (isPrestaShop13()) {
        return 1;
    }
    if (isPrestaShop14()) {
        return 1;
    }
    
    $OrderXML = LoadXML(Tools::stripslashes($xml));
    if (empty($OrderXML)) {
        return 0;
    }
    
    // Essaye de trouver le id du client par le code client id_customer=do_tiers
    $id_customer = Db::getInstance()->getValue('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` WHERE `atoosync_code_client` = \''.(string)($OrderXML->id_customer).'\' ');
    if ($id_customer) {
        // Essaye de trouver une commande avec le numéro de Sage
        $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `'._DB_PREFIX_.'orders` WHERE `id_customer` = '.(int)($id_customer).' AND `atoosync_number` = \''.(string)($OrderXML->reference).'\' ');

        if ($id_order) {
            if (Configuration::get('ATOOSYNC_OVERWRITE_ORDER') == 'Yes') {
                DeleteOrder((int)$id_order);
            } else {
                return 1;
            }
        }
            
        // l'adresse de livraison du client
        $id_address_delivery = Db::getInstance()->getValue('
						SELECT `id_address`
						FROM `'._DB_PREFIX_.'address` 
						WHERE `atoosync_id` = \''.(int)($OrderXML->id_address_delivery).'\' 
						AND `id_customer` = \''.(int)($id_customer).'\'
						AND `deleted` = 0');
        // l'adresse de facturation du client
        $id_address_invoice = Db::getInstance()->getValue('
						SELECT `id_address`
						FROM `'._DB_PREFIX_.'address` 
						WHERE `atoosync_id` = 999999999 
						AND `id_customer` = \''.(int)($id_customer).'\'
						AND `deleted` = 0');
                            
        $customer = new Customer($id_customer);
        $order = new Order();
            
        $order->current_state = (int)(Configuration::get('ATOOSYNC_INVOICE_STATE'));
        $order->id_carrier = (int)($OrderXML->id_carrier);
        $order->id_customer = (int)($id_customer);
        $order->id_address_invoice = (int)($id_address_invoice);
        $order->id_address_delivery = (int)($id_address_delivery);
        $order->id_currency = (int)(Configuration::get('PS_CURRENCY_DEFAULT'));
        $order->id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));
        $order->id_cart = 0;
        $order->reference = (string)($OrderXML->reference);
        $order->id_shop = 1;
        $order->id_shop_group = 1;
        
        $order->secure_key = (string)($customer->secure_key);
        $order->payment = (string)($OrderXML->payment);
        $order->module = (string)($OrderXML->module);
        $order->recyclable = (int)($OrderXML->recyclable);
        $order->gift = (int)($OrderXML->gift);
        $order->gift_message = (string)($OrderXML->gift_message);
        $order->mobile_theme = (int)($OrderXML->mobile_theme);
        $order->conversion_rate = (float)($OrderXML->conversion_rate);
        $order->total_paid_real = (float)($OrderXML->total_paid);
        
        $order->total_products = (float)($OrderXML->total_products);
        $order->total_products_wt = (float)($OrderXML->total_products_wt);

        $order->total_discounts_tax_excl = (float)($OrderXML->total_discounts_tax_excl);
        $order->total_discounts_tax_incl = (float)($OrderXML->total_discounts_tax_incl);
        $order->total_discounts = (float)($OrderXML->total_discounts);

        $order->total_shipping_tax_excl = (float)($OrderXML->total_shipping_tax_excl);
        $order->total_shipping_tax_incl = (float)($OrderXML->total_shipping_tax_incl);
        $order->total_shipping = (float)($OrderXML->total_shipping);
        
        $order->carrier_tax_rate = (float)($OrderXML->carrier_tax_rate);

        $order->total_wrapping_tax_excl =(float)($OrderXML->total_wrapping_tax_excl);
        $order->total_wrapping_tax_incl = (float)($OrderXML->total_wrapping_tax_incl);
        $order->total_wrapping = (float)($OrderXML->total_wrapping);

        $order->total_paid_tax_excl = (float)($OrderXML->total_paid_tax_excl);
        $order->total_paid_tax_incl = (float)($OrderXML->total_paid_tax_incl);
        $order->total_paid =(float)($OrderXML->total_paid);

        $order->invoice_date = (string)($OrderXML->invoice_date);
        $order->delivery_date = (string)($OrderXML->delivery_date);
        $order->date_add = (string)($OrderXML->date_add);
        $order->date_upd = (string)($OrderXML->date_upd);
        $order->valid = (int)($OrderXML->valid);
                    
        // Creating order
        $result = $order->add();

        if (!$result) {
            echo 'Error: order->add()';
        }
        
        // Si la création à réussi
        if ($result) {
            $query = "UPDATE `"._DB_PREFIX_."orders` SET `date_add`='".pSQL((string)($OrderXML->date_add))."', `date_upd`='".pSQL((string)($OrderXML->date_upd))."' WHERE `id_order`=".(int)($order->id);
            Db::getInstance()->Execute($query);
            
            // enregistre le numéro de Sage et marque la commande comme transférée
            $query = "UPDATE `"._DB_PREFIX_."orders` SET `atoosync_transfert_gescom`=1, `atoosync_number`='".pSQL((string)($OrderXML->reference))."' WHERE `id_order`=".(int)($order->id);
            Db::getInstance()->Execute($query);
            
            // Créer les articles de la commande
            foreach ($OrderXML->details->detail as $DetailXML) {
                // Crée un article ou une remise
                if ((float)($DetailXML->product_price) > 0) { // article
                    $orderdetail = new OrderDetail();
                    
                    $orderdetail->id_order = (int)($order->id);
                    $orderdetail->id_order_invoice = 0;
                    $orderdetail->id_warehouse =(int)($DetailXML->id_warehouse);
                    $orderdetail->id_shop = (int)($order->id_shop);
                    $orderdetail->product_id = (int)($DetailXML->product_id);
                    $orderdetail->product_attribute_id = (int)($DetailXML->product_attribute_id);
                    $orderdetail->product_name = (string)($DetailXML->product_name);
                    $orderdetail->product_quantity = (int)($DetailXML->product_quantity);
                    $orderdetail->product_ean13 = (string)($DetailXML->product_ean13);
                    $orderdetail->product_upc = (string)($DetailXML->product_upc);
                    $orderdetail->product_reference = (string)($DetailXML->product_reference);
                    $orderdetail->product_supplier_reference = (string)($DetailXML->product_supplier_reference);
                    $orderdetail->product_weight = (float)($DetailXML->product_weight);
                    
                    $orderdetail->product_price = (float)($DetailXML->product_price);
                    $orderdetail->reduction_percent = (float)($DetailXML->reduction_percent);
                    $orderdetail->reduction_amount = (float)($DetailXML->reduction_amount);
                    $orderdetail->reduction_amount_tax_incl = (float)($DetailXML->reduction_amount_tax_incl);
                    $orderdetail->reduction_amount_tax_excl = (float)($DetailXML->reduction_amount_tax_excl);
                    $orderdetail->group_reduction = (float)($DetailXML->group_reduction);
                    $orderdetail->product_quantity_discount = (int)($DetailXML->product_quantity_discount);
                    
                    $orderdetail->tax_computation_method = (int)($DetailXML->tax_computation_method);
                    $orderdetail->tax_name = (string)($DetailXML->tax_name);
                    $orderdetail->tax_rate = (float)($DetailXML->tax_rate);
                    $orderdetail->ecotax = (float)($DetailXML->ecotax);
                    $orderdetail->ecotax_tax_rate = (float)($DetailXML->ecotax_tax_rate);
                    
                    $orderdetail->discount_quantity_applied = (int)($DetailXML->discount_quantity_applied);
                    
                    $orderdetail->total_price_tax_incl = (float)($DetailXML->total_price_tax_incl);
                    $orderdetail->total_price_tax_excl = (float)($DetailXML->total_price_tax_excl);
                    $orderdetail->unit_price_tax_incl = (float)($DetailXML->unit_price_tax_incl);
                    $orderdetail->unit_price_tax_excl = (float)($DetailXML->unit_price_tax_excl);
                    $orderdetail->total_shipping_price_tax_incl = (float)($DetailXML->total_shipping_price_tax_incl);
                    $orderdetail->total_shipping_price_tax_excl = (float)($DetailXML->total_shipping_price_tax_excl);
                    $orderdetail->purchase_supplier_price = (float)($DetailXML->purchase_supplier_price);
                    $orderdetail->original_product_price = (float)($DetailXML->original_product_price);
                    
                    $orderdetail->date_add = $order->date_add;
                                    
                    $result = $orderdetail->add();
                    if (!$result) {
                        echo 'Error: orderdetail->add()';
                    }
                    
                    if ($result) {
                        $unit_amount = (((float)$orderdetail->product_price * (1+(float)$orderdetail->tax_rate/100)) - (float)$orderdetail->product_price);
                        $total_amount = $unit_amount * $orderdetail->product_quantity;
                        $id_tax = Db::getInstance()->getValue('SELECT `id_tax` FROM `'._DB_PREFIX_.'tax` WHERE `rate` ='.(float)($orderdetail->tax_rate));
                        if (!$id_tax) {
                            $id_tax=0;
                        }
                        
                        $sql = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount)
							VALUES ('.$orderdetail->id.','.$id_tax.','.$unit_amount.','.$total_amount.')';
                        Db::getInstance()->execute($sql);
                    }
                } elseif ((float)($DetailXML->product_price) < 0) {// remise
                    $value_tax_excl = -(float)($DetailXML->product_price); // passe en positif
                    $value = ($value_tax_excl * (1+(float)$orderdetail->tax_rate/100));
                    
                    $odc = new OrderCartRule();
                    $odc->id_order = (int)($order->id);
                    $odc->id_cart_rule = 0;
                    $odc->id_order_invoice = 0;
                    $odc->name = (string)($DetailXML->product_name);
                    $odc->value = $value;
                    $odc->value_tax_excl = $value_tax_excl;
                    $odc->add();
                }
            }
                                                
            $order_invoice = null;
            $id_order_invoice = null;
            if (Configuration::get('ATOOSYNC_ORDER_CREATE_INVOICE') == 'Yes') {
                // créé la facture de commande
                $order = new Order((int)($order->id));
                $order->setInvoice();
                $id_order_invoice = Db::getInstance()->getValue('SELECT `id_order_invoice` FROM `'._DB_PREFIX_.'order_invoice` WHERE `id_order` ='.(int)($order->id));
                $query = "UPDATE `"._DB_PREFIX_."order_invoice` SET `date_add`='".pSQL((string)($OrderXML->date_add))."' WHERE `id_order_invoice`=".(int)($id_order_invoice);
                Db::getInstance()->Execute($query);
                // Charge l'objet
                $order_invoice = new OrderInvoice($id_order_invoice);
            }
            
            // Créé les règlements 	de la commande
            $hasInvoice = $order->hasInvoice();
            // Supprime les règlements existant.
            $orderpayments  = $order->getOrderPayments();
            foreach ($orderpayments as $orderpayment) {
                // Supprime le règlement sur les factures
                if ($hasInvoice) {
                    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_invoice_payment` WHERE `id_order_payment` = '.(int)($orderpayment->id));
                }
                
                // supprime le règlement
                $orderpayment->delete();
            }
            
            // réinitialise le montant payé réellement et recharge l'objet
            $query = "UPDATE `"._DB_PREFIX_."orders` SET `total_paid_real`=0 WHERE `id_order`=".(int)($order->id);
            Db::getInstance()->Execute($query);
            $order = new Order((int)($order->id));
                
            // Créé les nouveaux règlements
            foreach ($OrderXML->payments->payment as $payment) {
                $order->addOrderPayment(
                    (float)($payment->amount),
                                        (string)($payment->payment_method),
                                        null,
                                        null,
                                        (string)($payment->date),
                                        $order_invoice
                );
            }
            
            // Enregistre le transporteur de la commande
            $weight = Db::getInstance()->getValue('SELECT SUM(`product_weight`) FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` ='.(int)($order->id));
            
            $ordercarrier = new OrderCarrier();
            $ordercarrier->id_order = (int)($order->id);
            $ordercarrier->id_carrier = (int)($OrderXML->id_carrier);
            $ordercarrier->id_order_invoice = $id_order_invoice;
            $ordercarrier->weight = (float)($weight);
            $ordercarrier->shipping_cost_tax_excl = (float)($OrderXML->total_shipping_tax_excl);
            $ordercarrier->shipping_cost_tax_incl = (float)($OrderXML->total_shipping_tax_incl);
            $ordercarrier->tracking_number = (string)($OrderXML->shipping_number);
            $ordercarrier->add();
            
            
            // Enregistre le statut de la commande
            $order_state = (int)(Configuration::get('ATOOSYNC_INVOICE_STATE'));
            $history = new OrderHistory();
            $history->id_order = (int)($order->id);
            $history->id_employee = 0;
            $history->date_add = (string)($OrderXML->date_add);
            $history->id_order_state = $order_state;
            $history->add();
                        
            // Hook validate order
            $currency = new Currency($order->id_currency);
            Context::getContext()->currency = $currency;
            
            // Hook validate order
            Hook::exec('actionValidateOrder', array(
                'cart' => new Cart($order->id_cart),
                'order' => $order,
                'customer' => new Customer($order->id_customer),
                'currency' => new Currency($order->id_currency),
                'orderStatus' => $order_state
            ));
            
            // executes hook
            $new_os = new OrderState((int)$order_state, $order->id_lang);
            Hook::exec('actionOrderStatusUpdate', array('newOrderStatus' => $new_os, 'id_order' => (int)$order->id), null, false, true, false, $order->id_shop);
        
        
            $succes = 1;
        }
    }
    return $succes;
}
/*
 * Supprime la commande
 */
function DeleteOrder($order_id)
{
    $order = new Order((int)($order_id));

    // Supprime les règlements.
    $orderpayments  = $order->getOrderPayments();
    foreach ($orderpayments as $orderpayment) {
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_invoice_payment` WHERE `id_order_payment` = '.(int)($orderpayment->id));
        $orderpayment->delete();
    }
    
    // Supprime les articles de la commande.
    $rows = Db::getInstance()->ExecuteS('SELECT `id_order_detail` FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int)($order_id));
    foreach ($rows as $k => $row) {
        $id_order_detail = $row['id_order_detail'];
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order_detail` = '.(int)($id_order_detail));
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_detail_tax` WHERE `id_order_detail` = '.(int)($id_order_detail));
    }
    
    // Supprime les factures de la commande.
    $rows = Db::getInstance()->ExecuteS('SELECT `id_order_invoice` FROM `'._DB_PREFIX_.'order_invoice` WHERE `id_order` = '.(int)($order_id));
    foreach ($rows as $k => $row) {
        $id_order_invoice = $row['id_order_invoice'];
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_invoice` WHERE `id_order_invoice` = '.(int)($id_order_invoice));
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_invoice_payment` WHERE `id_order_invoice` = '.(int)($id_order_invoice));
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_invoice_tax` WHERE `id_order_invoice` = '.(int)($id_order_invoice));
    }
    
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_history` WHERE `id_order` = '.(int)($order_id));
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_carrier` WHERE `id_order` = '.(int)($order_id));
            
    // Supprime la commande
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'orders` WHERE `id_order` = '.(int)($order_id));
}
