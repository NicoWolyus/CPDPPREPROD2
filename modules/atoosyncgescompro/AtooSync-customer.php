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
 * Désactive le client à partir du code client de la gestion Commerciale
 */
function DisableCustomer($accountnumber)
{
     // Customisation de la création des clients
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeDisableCustomer($accountnumber) == true) {
        return true;
    }
    
    if (!empty($accountnumber) && is_string($accountnumber)) {
        $query= "UPDATE `"._DB_PREFIX_."customer` SET `active`=0 WHERE `atoosync_code_client` ='".pSQL((string)$accountnumber)."'";
        Db::getInstance()->Execute($query);
    }
    return true; // pas de gestion d'erreur sur cette fonction
}
/*
 * Retourne la liste des clients ayant le champ atoosync_code_client de renseigné .
 */
function GetCustomers()
{
    $sql= "SELECT DISTINCT `atoosync_code_client` FROM `"._DB_PREFIX_."customer` WHERE  `atoosync_code_client` <>'' ORDER BY `atoosync_code_client`";
    $clients = Db::getInstance()->executeS($sql);
    foreach ($clients as $client) {
        $codeclient = $client['atoosync_code_client'];
        echo $codeclient.'<br>';
    }
}

/*
 * Renseigne le code client du client
 */
function SetCustomerAccount($id_customer, $accountnumber)
{
    $success= 0;
    
    if (!empty($id_customer) and is_numeric($id_customer) and !empty($accountnumber) and is_string($accountnumber)) {
        $query = "UPDATE `"._DB_PREFIX_."customer` SET `atoosync_code_client`='".pSQL((string)($accountnumber))."' WHERE `id_customer`='".(int)($id_customer)."'";
        Db::getInstance()->Execute($query);
        $success = 1;
    }
    return $success;
}

/*
 *	Ajoute ou modifie un client dans PrestaShop.
 */
function AddCustomer($xml)
{
    if (empty($xml)) {
        return 0;
    }
    $resultat = 1;
    $newCustomer =0 ;
    
    $xml = Tools::stripslashes($xml);
    $CustomerXML = LoadXML($xml);
    if (empty($CustomerXML)) {
        return 0;
    }
    
    // Customisation de la création des clients
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeAddCustomer($CustomerXML) == true) {
        return true;
    }
    
    $email = trim((string)($CustomerXML->email));
    if (Validate::isEmail($email)) {
        // Essaye de trouver le id du client selon son email ou le code client
        //$id_customer = Customer::customerExists((string)($CustomerXML->Email), true);
        $id_customer = Db::getInstance()->getValue('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` 
													WHERE `email` = \''.pSQL($CustomerXML->email).'\' 
													OR `atoosync_code_client` = \''.strval($CustomerXML->account_number).'\' ');
        if (!$id_customer) {
            switch (Configuration::get('ATOOSYNC_CUSTOMER_PASSWORD')) {
                case 'CodeClient':
                    $passwd = (string)($CustomerXML->account_number);
                    break;
                    
                case 'PostalCode':
                    if (!empty($CustomerXML->postcode)) {
                        $passwd = (string)($CustomerXML->postcode);
                    } else {
                        $passwd = (string)($CustomerXML->account_number);
                    }
                    break;
                    
                case 'Random':
                    $passwd = tools::passwdGen();
                    break;
            }
            
            $customer = new Customer();
            $customer->lastname = mb_substr(getContactLastname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
            $customer->firstname = mb_substr(getContactFirstname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
            $customer->email = trim(mb_substr((string)($CustomerXML->email), 0, 128));
            $customer->passwd = md5(_COOKIE_KEY_.$passwd);
            $customer->newsletter = false;
            $customer->optin = false;
            
            if (Configuration::get('ATOOSYNC_CUSTOMER_NEWSLETTER') == 'Yes') {
                $customer->newsletter = true;
                $customer->newsletter_date_add = date('Y-m-d H:i:s');
            }
            if (Configuration::get('ATOOSYNC_CUSTOMER_OPTIN') == 'Yes') {
                $customer->optin = true;
            }
            
            // Valide les champs.
            if ($customer->validateFieldsLang() == false) {
                echo 'customer->validateFieldsLang() error account_number='.(string)($CustomerXML->account_number);
            }
            
            /* Créé le client*/
            if ($customer->add()) {
                // met le code client et la centrale d'achat dans les champs atoosync_code_client et atoosync_centrale_achat
                $sql = 'UPDATE `'._DB_PREFIX_.'customer`
						SET `atoosync_code_client` = \''.pSQL((string)($CustomerXML->account_number)).'\',
						`atoosync_centrale_achat` = \''.pSQL((string)($CustomerXML->central_number)).'\'
						WHERE `id_customer`= \''.(int)($customer->id).'\'';
                Db::getInstance()->Execute($sql);
                $id_customer = $customer->id;
                
                
                // Essaye de trouver le id du groupe du client
                $id_group = Db::getInstance()->getValue('
					SELECT `id_group`
					FROM `'._DB_PREFIX_.'group`
					WHERE `atoosync_id` = \''.(int)($CustomerXML->price_category).'\'');
                if ($id_group) {
                    $customer->id_default_group = $id_group;
                    $customer->update();
                    
                    $groups = array();
                    $groups[0] = $id_group;
                    $customer->cleanGroups();
                    $customer->addGroups($groups);
                }
                
                /* Customisation du nouveau client */
                CustomizeNewCustomer($customer, $CustomerXML, $passwd);
            } else {
                echo Tools::displayError();
                echo 'customer->add() error account_number='.(string)($CustomerXML->account_number);
                $resultat = 0;
            }
        }

        /* Met à jour les données du clients*/
        if ($id_customer) {
            // charge le client
            $customer = new Customer($id_customer);
            
            /* Renseigne les données B2B si PrestaShop 1.5*/
            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                $customer->website = trim(mb_substr((string)($CustomerXML->website), 0, 128));
                $customer->company = trim(mb_substr((string)($CustomerXML->company), 0, 64));
                $customer->outstanding_allow_amount = (float)($CustomerXML->outstanding_allow_amount);
                
                if (Validate::isSiret(trim((string)($CustomerXML->siret)))) {
                    $customer->siret = trim((string)($CustomerXML->siret));
                }
                
                if (Validate::isApe(trim((string)($CustomerXML->ape)))) {
                    $customer->ape = trim((string)($CustomerXML->ape));
                }
            }
                
            // Modifie les informations selon la configuration
            if (Configuration::get('ATOOSYNC_UPDATE_CUSTOMER') == 'Yes') {
                $customer->lastname = mb_substr(getContactLastname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                $customer->firstname = mb_substr(getContactFirstname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
            }
            
            if (!$customer->update()) {
                echo Tools::displayError();
                echo 'customer->update() error account_number='.(string)($CustomerXML->account_number);
            }
      
            // modifie les groupes du client
            if (Configuration::get('ATOOSYNC_CUSTOMER_GROUP') == 'Yes') {
                // Fixe le groupe du client pour etre en conformité avec la gestion Commerciale
                $id_group = (int)(Db::getInstance()->getValue('
          SELECT `id_group`
          FROM `'._DB_PREFIX_.'group`
          WHERE `atoosync_id` = \''.(int)($CustomerXML->price_category).'\''));
                if ($id_group) {
                    $groups = array();
                    $groups[0] = $id_group;
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        if (Configuration::get('ATOOSYNC_CUSTOMER_ADDGROUP') != 'No') {
                            $groups[1] = (int)(Configuration::get('PS_CUSTOMER_GROUP'));
                        }
                    }
          
                    $customer->cleanGroups();
                    $customer->addGroups($groups);
          
                    $customer->id_default_group = $id_group;
                    if (!$customer->update()) {
                        echo Tools::displayError();
                        echo 'customer->update() error account_number='.(string)($CustomerXML->account_number);
                    }
                }
            }
            
            /* Créé la régle panier de la remise du client si activé */
            createCustomerDiscountPriceRule($customer, $CustomerXML);
            
            /* Met à jour la remise du client */
            SetCustomerDiscount($CustomerXML);
            
            // Désactive les adresses qui ne viennent pas de Sage
            if (Configuration::get('ATOOSYNC_ADRESSE_REMOVE') == 'Yes') {
                /*  Recherche les adresses avec un atoosync_id vide */
                $query = 'UPDATE `'._DB_PREFIX_.'address` 
                  SET `deleted` = 1
                  WHERE `id_customer`= '.(int)($id_customer).' 
                  AND `atoosync_id` = 0';
                Db::getInstance()->Execute($query);
            }
          
            // Pour chacune des adresses du client
            if ($CustomerXML->addresses) {
                // Si la création/modification des adresses du clients n'est pas surchargé.
                if (CustomizeCustomerAddresses($customer, $CustomerXML) == false) {
                    foreach ($CustomerXML->addresses->address as $adr) {
                        if (isAddressValid($adr)) {
                            // Essaye de trouver l'adresse du client selon le numéro
                            $id_address = Db::getInstance()->getValue('
								SELECT `id_address`
								FROM `'._DB_PREFIX_.'address` 
								WHERE `atoosync_id` = '.(int)($adr->address_number).' 
								AND `id_customer` = '.(int)($id_customer).'
								AND `deleted` = 0');
                            if (!$id_address) {
                                $address = new Address();
                                $address->id_customer = (int)($customer->id);
                                $address->id_country = (int)getCountryId($adr);
                                $address->alias = trim(mb_substr((string)($adr->alias), 0, 32));
                                $address->company = trim(mb_substr((string)($CustomerXML->company), 0, 32));
                                $address->lastname = mb_substr(getContactLastname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                                $address->firstname = mb_substr(getContactFirstname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                                $address->address1 = trim(mb_substr((string)($adr->address1), 0, 128));
                                $address->address2 = trim(mb_substr((string)($adr->address2), 0, 128));
                                $address->postcode = trim(mb_substr((string)($adr->postcode), 0, 12));
                                $address->city = trim(mb_substr((string)($adr->city), 0, 64));
                                $address->phone = trim(mb_substr((string)($adr->phone), 0, 16));
                                
                                $vat_number = trim(mb_substr((string)($CustomerXML->vat_number), 0, 32));
                                if ((isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) and !empty($vat_number)) {
                                    $address->vat_number = $vat_number ;
                                }
                        
                                if ($address->add()) {
                                    // met le numéro de l'adresse dans le champ atoosync_id
                                    $sql = 'UPDATE `'._DB_PREFIX_.'address`
											SET `atoosync_id` = \''.(int)($adr->address_number).'\'
											WHERE `id_address`= \''.(int)($address->id).'\' 
											AND `id_customer` = \''.(int)($id_customer).'\'';
                                    Db::getInstance()->Execute($sql);
                                } else {
                                    echo Tools::displayError();
                                    echo 'address->add() error account_number='.(string)($CustomerXML->account_number);
                                }
                            } else {
                                // Modifie l'adresse selon la configuration
                                if (Configuration::get('ATOOSYNC_UPDATE_ADDRESS') == 'Yes') {
                                    $address = new Address($id_address);
                                    $address->id_country = (int)getCountryId($adr);
                                    $address->alias = trim(mb_substr((string)($adr->alias), 0, 32));
                                    $address->company = trim(mb_substr((string)($CustomerXML->company), 0, 32));
                                    $address->lastname = mb_substr(getContactLastname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                                    $address->firstname = mb_substr(getContactFirstname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                                    $address->address1 = trim(mb_substr((string)($adr->address1), 0, 128));
                                    $address->address2 = trim(mb_substr((string)($adr->address2), 0, 128));
                                    $address->postcode = trim(mb_substr((string)($adr->postcode), 0, 12));
                                    $address->city = trim(mb_substr((string)($adr->city), 0, 64));
                                    $address->phone = trim(mb_substr((string)($adr->phone), 0, 16));
                                    
                                    $vat_number = trim(mb_substr((string)($CustomerXML->vat_number), 0, 32));
                                    if ((isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) and !empty($vat_number)) {
                                        $address->vat_number = $vat_number ;
                                    }
                                    
                                    if (!$address->update()) {
                                        echo Tools::displayError();
                                        echo 'address->update() error account_number='.(string)($CustomerXML->account_number);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Fixe le code du client
            $sql = 'UPDATE `'._DB_PREFIX_.'customer`
					SET `atoosync_code_client` = \''.pSQL((string)($CustomerXML->account_number)).'\'
					WHERE `id_customer`= \''.(int)($id_customer).'\'';
            Db::getInstance()->Execute($sql);

            /* Fixe la centrale d'achat */
            $sql = 'UPDATE `'._DB_PREFIX_.'customer`
						SET `atoosync_centrale_achat` = \''.pSQL((string)($CustomerXML->central_number)).'\'
						WHERE `id_customer`= \''.(int)($id_customer).'\'';
            Db::getInstance()->Execute($sql);
            
            /* Customisation du client */
            CustomizeCustomer($customer, $CustomerXML);
        }
    }
    
    // Créer les contacts du client
    if (Configuration::get('ATOOSYNC_CUSTOMER_CONTACTS') == 'Yes') {
        CreateCustomerContacts($CustomerXML);
    }
    
    return $resultat;
}
/*
 *  Créer les contacts du client
 */
function CreateCustomerContacts($CustomerXML)
{
    // Créer les contacts du client
    if ($CustomerXML->contacts)
    {
        foreach ($CustomerXML->contacts->contact as $contact) {
            $email = (string)($contact->email);
            if (Validate::isEmail($email)) {
                // Essaye de trouver le id du client selon l'email du contact
                $id_customer = Db::getInstance()->getValue('SELECT `id_customer` FROM `'._DB_PREFIX_.'customer` WHERE `email` = \''.pSQL($email).'\'');
                if (!$id_customer) {
                    switch (Configuration::get('ATOOSYNC_CUSTOMER_PASSWORD')) {
                        case 'CodeClient':
                            $passwd = (string)($CustomerXML->account_number);
                            break;
                            
                        case 'PostalCode':
                            if (!empty($CustomerXML->postcode)) {
                                $passwd = (string)($CustomerXML->postcode);
                            } else {
                                $passwd = (string)($CustomerXML->account_number);
                            }
                            break;
                            
                        case 'Random':
                            $passwd = tools::passwdGen();
                            break;
                    }
                    
                    $customer = new Customer();
                    $customer->lastname = mb_substr((string)$contact->lastname, 0, 32);
                    $customer->firstname = mb_substr((string)$contact->firstname, 0, 32);
                    $customer->email = trim(mb_substr((string)($contact->email), 0, 128));
                    $customer->passwd = md5(_COOKIE_KEY_.$passwd);
                    $customer->newsletter = false;
                    $customer->optin = false;
                    
                    if (Configuration::get('ATOOSYNC_CUSTOMER_NEWSLETTER') == 'Yes') {
                        $customer->newsletter = true;
                        $customer->newsletter_date_add = date('Y-m-d H:i:s');
                    }
                    if (Configuration::get('ATOOSYNC_CUSTOMER_OPTIN') == 'Yes') {
                        $customer->optin = true;
                    }
                    
                    /* Renseigne les données B2B si PrestaShop 1.5*/
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $customer->website = trim(mb_substr((string)($CustomerXML->website), 0, 128));
                        $customer->company = trim(mb_substr((string)($CustomerXML->company), 0, 64));
                        $customer->outstanding_allow_amount = (float)($CustomerXML->outstanding_allow_amount);
                        
                        if (Validate::isSiret(trim((string)($CustomerXML->siret)))) {
                            $customer->siret = trim((string)($CustomerXML->siret));
                        }
                        
                        if (Validate::isApe(trim((string)($CustomerXML->ape)))) {
                            $customer->ape = trim((string)($CustomerXML->ape));
                        }
                    }
            
                    // Valide les champs.
                    if ($customer->validateFieldsLang() == false) {
                        echo 'customer->validateFieldsLang() error account_number='.(string)($CustomerXML->account_number);
                    }
                    
                    /* Créé le client*/
                    if ($customer->add()) {
                        // met le code client et la centrale d'achat dans les champs atoosync_code_client et atoosync_centrale_achat
                        $sql = 'UPDATE `'._DB_PREFIX_.'customer`
								SET `atoosync_code_client` = \''.pSQL((string)($CustomerXML->account_number)).'\',
								`atoosync_centrale_achat` = \''.pSQL((string)($CustomerXML->central_number)).'\'
								WHERE `id_customer`= \''.(int)($customer->id).'\'';
                        Db::getInstance()->Execute($sql);
                        $id_customer = $customer->id;
                        
                        
                        // Essaye de trouver le id du groupe du client
                        $id_group = Db::getInstance()->getValue('
							SELECT `id_group`
							FROM `'._DB_PREFIX_.'group`
							WHERE `atoosync_id` = \''.(int)($CustomerXML->price_category).'\'');
                        if ($id_group) {
                            $customer->id_default_group = $id_group;
                            $customer->update();
                            
                            $groups = array();
                            $groups[0] = $id_group;
                            $customer->cleanGroups();
                            $customer->addGroups($groups);
                        }
                        
                        // envoi l'email
                        if (Configuration::get('ATOOSYNC_CUSTOMER_SEND_MAIL') == 'Yes') {
                            Mail::Send(
                                IdLangDefault(),
                                'account',
                                Mail::l('Welcome!'),
                                array('{firstname}' => (string)($customer->firstname),
                                      '{lastname}' => (string)($customer->lastname),
                                      '{email}' => (string)($customer->email),
                                      '{passwd}' => (string)($passwd)),
                                (string)($customer->email),
                                (string)($customer->firstname.' '.$customer->lastname)
                            );
                        }

                        /* Customisation du nouveau client */
                        CustomizeNewCustomer($customer, $CustomerXML, $passwd);
                    } else {
                        echo Tools::displayError();
                        echo 'customer->add() error account_number='.(string)($CustomerXML->account_number);
                        $resultat = 0;
                    }
                }
                /* Met à jour les données du clients*/
                if ($id_customer) {
                    // charge le client
                    $customer = new Customer($id_customer);
                    
                    // Modifie les informations selon la configuration
                    if (Configuration::get('ATOOSYNC_UPDATE_CUSTOMER') == 'Yes') {
                        $customer->lastname = mb_substr((string)$contact->lastname, 0, 32);
                        $customer->firstname = mb_substr((string)$contact->firstname, 0, 32);
                    }
      
                    /* Renseigne les données B2B si PrestaShop 1.5*/
                    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                        $customer->website = trim(mb_substr((string)($CustomerXML->website), 0, 128));
                        $customer->company = trim(mb_substr((string)($CustomerXML->company), 0, 64));
                        $customer->outstanding_allow_amount = (float)($CustomerXML->outstanding_allow_amount);
                        
                        if (Validate::isSiret(trim((string)($CustomerXML->siret)))) {
                            $customer->siret = trim((string)($CustomerXML->siret));
                        }
                        
                        if (Validate::isApe(trim((string)($CustomerXML->ape)))) {
                            $customer->ape = trim((string)($CustomerXML->ape));
                        }
                    }
                                        
                    if (!$customer->update()) {
                        echo Tools::displayError();
                        echo 'customer->update() error account_number='.(string)($CustomerXML->account_number);
                    }
                        
                    // modifie les groupes du client
                    if (Configuration::get('ATOOSYNC_CUSTOMER_GROUP') == 'Yes') {
                        // Fixe le groupe du client pour etre en conformité avec la gestion Commerciale
                        $id_group = (int)(Db::getInstance()->getValue('
              SELECT `id_group`
              FROM `'._DB_PREFIX_.'group`
              WHERE `atoosync_id` = \''.(int)($CustomerXML->price_category).'\''));
                        if ($id_group) {
                            $groups = array();
                            $groups[0] = $id_group;
                            if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                                if (Configuration::get('ATOOSYNC_CUSTOMER_ADDGROUP') != 'No') {
                                    $groups[1] = (int)(Configuration::get('PS_CUSTOMER_GROUP'));
                                }
                            }
              
                            $customer->cleanGroups();
                            $customer->addGroups($groups);
              
                            $customer->id_default_group = $id_group;
                            if (!$customer->update()) {
                                echo Tools::displayError();
                                echo 'customer->update() error account_number='.(string)($CustomerXML->account_number);
                            }
                        }
                    }
          
                    // Désactive les adresses qui ne viennent pas de Sage
                    if (Configuration::get('ATOOSYNC_ADRESSE_REMOVE') == 'Yes') {
                        /*  Recherche les adresses avec un atoosync_id vide */
                        $query = 'UPDATE `'._DB_PREFIX_.'address` 
                      SET `deleted` = 1
                      WHERE `id_customer`= '.(int)($id_customer).' 
                      AND `atoosync_id` = 0';
                        Db::getInstance()->Execute($query);
                    }
          
                    // créé les adresses sur le contact
                    if (Configuration::get('ATOOSYNC_CONTACTS_ADDRESS') == 'Yes') {
                        foreach ($CustomerXML->addresses->address as $adr) {
                            if (isAddressValid($adr)) {
                                // Essaye de trouver l'adresse du client selon le numéro
                                $id_address = Db::getInstance()->getValue('
                  SELECT `id_address`
                  FROM `'._DB_PREFIX_.'address` 
                  WHERE `atoosync_id` = '.(int)($adr->address_number).' 
                  AND `id_customer` = '.(int)($id_customer).'
                  AND `deleted` = 0');
                                if (!$id_address) {
                                    $address = new Address();
                                    $address->id_customer = (int)($customer->id);
                                    $address->id_country = (int)getCountryId($adr);
                                    ;
                                    $address->alias = trim(mb_substr((string)($adr->alias), 0, 32));
                                    $address->company = trim(mb_substr((string)($CustomerXML->company), 0, 32));
                                    $address->lastname = mb_substr(getContactLastname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                                    $address->firstname = mb_substr(getContactFirstname((string)($CustomerXML->contact), (string)($CustomerXML->company)), 0, 32);
                                    $address->address1 = trim(mb_substr((string)($adr->address1), 0, 128));
                                    $address->address2 = trim(mb_substr((string)($adr->address2), 0, 128));
                                    $address->postcode = trim(mb_substr((string)($adr->postcode), 0, 12));
                                    $address->city = trim(mb_substr((string)($adr->city), 0, 64));
                                    $address->phone = trim(mb_substr((string)($adr->phone), 0, 16));
                  
                                    $vat_number = trim(mb_substr((string)($CustomerXML->vat_number), 0, 32));
                                    if ((isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) and !empty($vat_number)) {
                                        $address->vat_number = $vat_number ;
                                    }
              
                                    if ($address->add()) {
                                        // met le numéro de l'adresse dans le champ atoosync_id
                                        $sql = 'UPDATE `'._DB_PREFIX_.'address`
                        SET `atoosync_id` = \''.(int)($adr->address_number).'\'
                        WHERE `id_address`= \''.(int)($address->id).'\' 
                        AND `id_customer` = \''.(int)($id_customer).'\'';
                                        Db::getInstance()->Execute($sql);
                                    } else {
                                        echo Tools::displayError();
                                        echo 'address->add() error account_number='.(string)($CustomerXML->account_number);
                                    }
                                } else {
                                    // Modifie l'adresse selon la configuration
                                    if (Configuration::get('ATOOSYNC_UPDATE_ADDRESS') == 'Yes') {
                                        $address = new Address($id_address);
                                        $address->id_country = (int)getCountryId($adr);
                                        $address->alias = trim(mb_substr((string)($adr->alias), 0, 32));
                                        $address->company = trim(mb_substr((string)($CustomerXML->company), 0, 32));
                                        $address->lastname = mb_substr(getContactLastname((string)($adr->contact), (string)($CustomerXML->company)), 0, 32);
                                        $address->firstname = mb_substr(getContactFirstname((string)($adr->contact), (string)($CustomerXML->company)), 0, 32);
                                        $address->address1 = trim(mb_substr((string)($adr->address1), 0, 128));
                                        $address->address2 = trim(mb_substr((string)($adr->address2), 0, 128));
                                        $address->postcode = trim(mb_substr((string)($adr->postcode), 0, 12));
                                        $address->city = trim(mb_substr((string)($adr->city), 0, 64));
                                        $address->phone = trim(mb_substr((string)($adr->phone), 0, 16));
                    
                                        $vat_number = trim(mb_substr((string)($CustomerXML->vat_number), 0, 32));
                                        if ((isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) and !empty($vat_number)) {
                                            $address->vat_number = $vat_number ;
                                        }
                    
                                        if (!$address->update()) {
                                            echo Tools::displayError();
                                            echo 'address->update() error account_number='.(string)($CustomerXML->account_number);
                                        }
                                    }
                                }
                            }
                        }
                    }
                   
                    /* Créé la régle panier de la remise du client si activé */
                    createCustomerDiscountPriceRule($customer, $CustomerXML);
                                                            
                    // Fixe le code du client
                    $sql = 'UPDATE `'._DB_PREFIX_.'customer`
							SET `atoosync_code_client` = \''.pSQL((string)($CustomerXML->account_number)).'\'
							WHERE `id_customer`= \''.(int)($id_customer).'\'';
                    Db::getInstance()->Execute($sql);

                    /* Fixe la centrale d'achat */
                    $sql = 'UPDATE `'._DB_PREFIX_.'customer`
								SET `atoosync_centrale_achat` = \''.pSQL((string)($CustomerXML->central_number)).'\'
								WHERE `id_customer`= \''.(int)($id_customer).'\'';
                    Db::getInstance()->Execute($sql);
                }
            }
        }
    }
}
/*
 * Trouve le id_country du Pays dans PrestaShop selon le code iso ou le nom du pays de l'adresse de Sage.
 */
function getCountryId($xml)
{
    $id_country = 0;
    $country_iso = (string)$xml->country_iso;
  
    if (!empty($country_iso)) {
        if (Configuration::get('ATOOSYNC_ADRESSE_CODEISO') == 'Yes') {
            $id_country = Country::getByIso(trim($country_iso));
            if ($id_country == false) {
                $id_country = 0;
            }
        }
    }
  
    if ($id_country == 0) {
        $id_country = Country::getIdByName(null, trim((string)($xml->country)));
        if ($id_country == false) {
            $id_country = 0;
        }
    }
  
    return $id_country;
}
 
/*
 * function qui test si l'adresse est valide
 */
function isAddressValid($xml)
{
    $text = trim((string)($xml->address1));
    if (empty($text)) {
        return false;
    }
    
    $text = trim((string)($xml->postcode));
    if (empty($text)) {
        return false;
    }
    
    $text = trim((string)($xml->city));
    if (empty($text)) {
        return false;
    }
    
    if (getCountryId($xml) == 0) {
        return false;
    }

    return true;
}

/*
 * Retourne le prénom du contact selon la configuration
 */
function GetContactFirstname($contact, $title)
{
    $info = explode(' ', $contact, 2);

    $prenom ='';
    switch (Configuration::get('ATOOSYNC_CUSTOMER_FIRSTNAME')) {
        case 'First':
            $prenom = $info[0];
            break;
            
        case 'Last':
             $prenom = $info[1];
            break;
            
        case 'All':
             $prenom = $contact;
            break;
        
        case 'Title':
             $prenom = $title;
            break;
    }
    
    // Si le prénom est vide alors utilise le contact en entier
    if (empty($prenom)) {
        $prenom = $contact;
    }
    
    // Si le prénom est encore vide alors utilise l'intitulé du client
    if (empty($prenom)) {
        $prenom = $title;
    }
        
    return trim($prenom);
}

/*
 * Retourne le Nom du contact selon la configuration
 */
function GetContactLastname($contact, $title)
{
    $info = explode(' ', $contact, 2);
    
    $nom ='';
    switch (Configuration::get('ATOOSYNC_CUSTOMER_LASTNAME')) {
        case 'First':
            $nom = $info[0];
            break;
            
        case 'Last':
             $nom = $info[1];
            break;
                        
        case 'All':
             $nom = $contact;
            break;
        
        case 'Title':
             $nom = $title;
            break;
    }
    
    // Si le nom est vide alors utilise le contact en entier
    if (empty($nom)) {
        $nom = $contact;
    }
    
    // Si le nom est encore vide alors utilise l'intitulé du client
    if (empty($nom)) {
        $nom = $title;
    }
        
    return trim($nom);
}
/*
    Ajoute les groupes de clients PrestaShop
    à partir des catégories tarifaires.
*/
function AddCustomersGroups($xml)
{
    if (empty($xml)) {
        return 0;
    }

    $success = 1;
    
    $xml = Tools::stripslashes($xml);
    $GroupsXML = LoadXML($xml);
    if (empty($GroupsXML)) {
        return 0;
    }
    
    // Customisation de la création des groupes de clients
    // Si il y a une customisation alors ignore la création standard par Atoo-Sync
    if (CustomizeAddCustomersGroups($GroupsXML) == true) {
        return true;
    }
        
    /*
    * Pour chaque Categorie dans le XML des Categories
    */
    foreach ($GroupsXML->group as $grp) {
        // Essaye de trouver le id du groupe selon le id de Atoo-Sync
        $id_group = Db::getInstance()->getValue('
			SELECT `id_group`
			FROM `'._DB_PREFIX_.'group`
			WHERE `atoosync_id` = \''.(int)($grp->atoosync).'\'');
        
        // Si il n'existe pas le groupe est créé
        if (!$id_group) {
            $group = new Group();
            $group->name = CreateMultiLangField((string)($grp->name));
            if ((int)($grp->tax_inc) == 1) {
                $group->price_display_method = PS_TAX_INC;
            } else {
                $group->price_display_method = PS_TAX_EXC;
            }
                
            if ($group->add()) {
                // l'id du nouveau groupe
                $id_group = $group->id;
                
                // les modules du group
                if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                    $auth_modules = array();
                    $modules = Module::getModulesInstalled();
                    foreach ($modules as $module) {
                        $auth_modules[] = $module['id_module'];
                    }
                    $shops = Shop::getShops(true, null, true);
                    Group::addModulesRestrictions($id_group, $auth_modules, $shops);
                }
            
                // Enregistre la clé AtooSync
                $sql = '
				UPDATE `'._DB_PREFIX_.'group`
				SET `atoosync_id` = \''.(int)($grp->atoosync).'\'
				WHERE `id_group`= \''.(int)($group->id).'\'';
                Db::getInstance()->Execute($sql);
            } else {
                $success = 0;
                echo "Error group->add()";
            }
        } else {
            if (Configuration::get('ATOOSYNC_GROUP_UPDATENAME') == 'Yes') {
                $group = new Group($id_group);
                $group->name = CreateMultiLangField((string)($grp->name));
                $group->update();
            }
        }
    }
    return $success;
}
/*
 * Liste les groupes de clients
 */
function GetCustomersGroups()
{
    $groups = Group::getGroups((int)(IdLangDefault()));
    foreach ($groups as $group) {
        $gr  = $group['id_group'].'|';
        $gr .= $group['name'].'|';
        $gr .= Db::getInstance()->getValue('SELECT `atoosync_id`
											FROM `'._DB_PREFIX_.'group`
											WHERE `id_group` ='.(int)($group['id_group']));
        echo $gr.'<br>';
    }

    return 1;
}
/*
 * Créé la régle panier à partir de la remise de Sage
 */
function createCustomerDiscountPriceRule($customer, $CustomerXML)
{
    if (Configuration::get('ATOOSYNC_CUSTOMER_DISCOUNT') == 'Yes') {
        $name =  Configuration::get('ATOOSYNC_CUSTOMER_DISCOUNTNAME');
        if (!empty($name)) {
            $name = str_replace("%C", (string)$CustomerXML->account_number, $name);
            $name = str_replace("%I", (string)$CustomerXML->company, $name);
            $name = str_replace("%P", (float)$CustomerXML->discount, $name);
            $description =  'Atoo-Sync Remise Sage '.(string)$CustomerXML->account_number;
            
            // Si la règle panier existe déjà elle est mise à jour
            $sql= "SELECT `id_cart_rule` FROM `"._DB_PREFIX_."cart_rule` WHERE  `description`='".pSQL($description)."' AND `id_customer` =".(int)$customer->id;
            $id_cart_rule = Db::getInstance()->getValue($sql);
            
            if ($id_cart_rule) {
                $cart_rule = new CartRule($id_cart_rule);
            } else {
                $cart_rule = new CartRule();
            }
            
            $cart_rule->code = '';
            $cart_rule->id_customer = $customer->id;
            $cart_rule->reduction_percent = (int)$CustomerXML->discount;
            $cart_rule->partial_use = false;
            $cart_rule->quantity = 9999;
            $cart_rule->quantity_per_user = 9999;
            $cart_rule->date_from = date('Y-m-d H:i:s', time() - 1);
            $cart_rule->date_to = date('Y-m-d H:i:s', strtotime($cart_rule->date_from.' +1 year'));
            $cart_rule->description = $description;
                
            $languages = Language::getLanguages(true);
            foreach ($languages as $language) {
                $cart_rule->name[(int)$language['id_lang']] = $name;
            }
                
            $cart_rule->save();
        }
    }
}
