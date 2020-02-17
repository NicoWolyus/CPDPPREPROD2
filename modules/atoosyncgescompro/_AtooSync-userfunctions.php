<?php 
Function _CustomizeCustomerOrder($id_order, $id_customer) {
	
	$customer = new Customer($id_customer); 
	$order = new Order((int)$id_order);
	$address = new Address((int)$order->id_address_invoice);
	$id_group =  $customer->id_default_group;
	
	// Trouve la catégorie tarifaire du groupe par défaut du client
	$cattarif = Db::getInstance()->getValue('
			SELECT `atoosync_id`
			FROM `'._DB_PREFIX_.'group`
			WHERE `id_group` = '.(int)($id_group));
	
	if ((int)$cattarif == 0)
		$cattarif ='';
	
	// Selon la version de PS
	if (isPrestaShop15() OR isPrestaShop16())
	{
		$identifiant = $address->vat_number;
		$siret = $customer->siret;
		$ape = $customer->ape;
	}
	elseif (isPrestaShop14())
	{
		$identifiant = $address->dni;
		$siret = '';
		$ape = '';
	}
	elseif (isPrestaShop13())
	{
		$identifiant = $customer->dni;
		$siret = '';
		$ape = '';
	} 
	else 
	{
		$identifiant ='';
		$siret = '';
		$ape = '';
	}
	
	
	
	$xml = '';
	$xml .= "\t\t<client_intitule>".''."</client_intitule>\r\n";						// Intitulé du client du client (35 caractères max.) si non renseigné alors 'Nom'+' '+'Prénom' est utilisé
	$xml .= "\t\t<client_classement>".''."</client_classement>\r\n";					// Classement du client (17 caractères max.) si non renseigné l'intitulé est utilisé
	$xml .= "\t\t<client_contact>".''."</client_contact>\r\n";							// Contact du client (35 caractères max.) si non renseigné alors 'Nom+' '+Prénom' est utilisé
	$xml .= "\t\t<client_telephone>".''."</client_telephone>\r\n";						// Numéro de téléphone du client (21 caractères max.)
	$xml .= "\t\t<client_telecopie>".''."</client_telecopie>\r\n";						// Numéro de télécopie du client (21 caractères max.)
	$xml .= "\t\t<client_qualite>".''."</client_qualite>\r\n";							// Qualité du client (17 caractères max.) si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
	$xml .= "\t\t<client_siret>".escapeXMLString($siret)."</client_siret>\r\n";			// Numéro de Siret du client (15 caractères max.)
	$xml .= "\t\t<client_NAF>".escapeXMLString($ape)."</client_NAF>\r\n";				// Code NAF du client (7 caractères max.)
	$xml .= "\t\t<client_identifiant>".escapeXMLString($identifiant)."</client_identifiant>\r\n";					// Identifiant TVA du client (25 caractères max.)
	$xml .= "\t\t<client_devise>0</client_devise>\r\n";									// Numéro d'index de la devise (0=Aucune,1,2,3...)
	$xml .= "\t\t<client_compte_collectif>".''."</client_compte_collectif>\r\n";		// Numéro de compte collectif du client, si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
	$xml .= "\t\t<client_categorie_comptable>".''."</client_categorie_comptable>\r\n";	// Numéro d'index de la catégorie comptable ( 1,2,3...) si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
	$xml .= "\t\t<client_categorie_tarifaire>".$cattarif."</client_categorie_tarifaire>\r\n";	// Numéro d'index de la catégorie tarifaire ( 1,2,3...) si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
	$xml .= "\t\t<client_compte_payeur>".''."</client_compte_payeur>\r\n";				// Numéro du compte payeur si non renseigné le code client est utilisé
	$xml .= "\t\t<client_centrale_achat>".''."</client_centrale_achat>\r\n";			// Numéro de la centrale d'achat du client
    $xml .= "\t\t<client_representant>".''."</client_representant>\r\n";				// Nom et prénom du représentant
	$xml .= "\t\t<client_code_affaire>".''."</client_code_affaire>\r\n";				// Numéro du code affaire de type détail
	$xml .= "\t\t<client_langue>0</client_langue>\r\n"; 								// 0=Aucune, 1=langue 1, 2=Langue 2
	$xml .= "\t\t<client_depot>".''."</client_depot>\r\n";								// Nom du dépôt associé au client, si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
	$xml .= "\t\t<client_nb_factures>1</client_nb_factures>\r\n";						// Nombres de factures 
	$xml .= "\t\t<client_format_facture>1</client_format_facture>\r\n";					// 0 = Aucun, 1 = Défaut, 2 = Pdf, 3 = UBL/XML, 4 = Facturea
	$xml .= "\t\t<client_type_NIF>0</client_type_NIF>\r\n";								// 0 = NIF, 1 = NIF Intracommunautaire, 2 = Passeport, 3 = Document 4 = Certificat, 5 = Autre document
	$xml .= "\t\t<client_representant_intitule>".''."</client_representant_intitule>\r\n";	// Intitulé représentant légal
	$xml .= "\t\t<client_representant_NIF>".''."</client_representant_NIF>\r\n";		// NIF représentant légal
	$xml .= "\t\t<client_controle_encours>0</client_controle_encours>\r\n";				// 0=Contrôle automatique, 1=Selon code risque, 2=Compte bloqué
	$xml .= "\t\t<client_saut_lignes>1</client_saut_lignes>\r\n";						// 0=Saut de page sinon nombre de ligne
	$xml .= "\t\t<client_lettrage_automatique>1</client_lettrage_automatique>\r\n";		// 0=Non, 1=Oui
	$xml .= "\t\t<client_validation_automatique>0</client_validation_automatique>\r\n"; // 0=Non, 1=Oui
	$xml .= "\t\t<client_hors_rappel_releve>0</client_hors_rappel_releve>\r\n"; 		// 0=Non, 1=Oui
	$xml .= "\t\t<client_non_soumis_penalite>0</client_non_soumis_penalite>\r\n"; 		// 0=Non, 1=Oui
	$xml .= "\t\t".'<client_commentaire>'.'Client créé par Atoo-Sync GesCom Pro le '.date('j-m-Y').'</client_commentaire>'."\r\n";	

    // la banque du client/commande
    $xml .= "\t\t<client_banque_intitule>".''."</client_banque_intitule>\r\n"; 		
    $xml .= "\t\t<client_banque_banque>".''."</client_banque_banque>\r\n"; 		
    $xml .= "\t\t<client_banque_guichet>".''."</client_banque_guichet>\r\n"; 		
    $xml .= "\t\t<client_banque_compte>".''."</client_banque_compte>\r\n"; 		
    $xml .= "\t\t<client_banque_cle>".''."</client_banque_cle>\r\n"; 		
    $xml .= "\t\t<client_banque_commentaire>".''."</client_banque_commentaire>\r\n"; 		
    $xml .= "\t\t<client_banque_structure>".'0'."</client_banque_structure>\r\n"; 		// 0 = Locale, 1= Autre, 2 = BBAN, 3 = IBAN
    $xml .= "\t\t<client_banque_adresse>".''."</client_banque_adresse>\r\n"; 	
    $xml .= "\t\t<client_banque_complement>".''."</client_banque_complement>\r\n"; 	
    $xml .= "\t\t<client_banque_code_postal>".''."</client_banque_code_postal>\r\n"; 	
    $xml .= "\t\t<client_banque_ville>".''."</client_banque_ville>\r\n"; 	
    $xml .= "\t\t<client_banque_pays>".''."</client_banque_pays>\r\n"; 	
    $xml .= "\t\t<client_banque_BIC>".''."</client_banque_BIC>\r\n"; 	
    $xml .= "\t\t<client_banque_IBAN>".''."</client_banque_IBAN>\r\n"; 	
    $xml .= "\t\t<client_banque_nom_agence>".''."</client_banque_nom_agence>\r\n"; 	
    $xml .= "\t\t<client_banque_code_region>".''."</client_banque_code_region>\r\n"; 	
    $xml .= "\t\t<client_banque_pays_agence>".''."</client_banque_pays_agence>\r\n"; 
    $xml .= "\t\t<client_banque_devise>".''."</client_banque_devise>\r\n";              // intitulé de la devise comme dans Sage
  
  
	$civility= '';
	if ($customer->id_gender == 1) $civility= '0'; // 0= Mr. 1= Mme 2= Mlle
	if ($customer->id_gender == 2) $civility= '1'; // Mme
		
	$xml .= "\t\t".'<client_contact_nom>'.escapeXMLString($address->lastname).'</client_contact_nom>'."\r\n";					// Nom du contact (35 caractères max.)
	$xml .= "\t\t".'<client_contact_prenom>'.escapeXMLString($address->firstname).'</client_contact_prenom>'."\r\n";			// Prénom du contact (35 caractères max.)	
	$xml .= "\t\t".'<client_contact_service>'.'1'.'</client_contact_service>'."\r\n";											// Numéro d'index du service du contact ( 1,2,3...) 
	$xml .= "\t\t".'<client_contact_fonction>'.''.'</client_contact_fonction>'."\r\n";											// Fonction du contact (35 caractères max.)
	$xml .= "\t\t".'<client_contact_telephone>'.escapeXMLString($address->phone).'</client_contact_telephone>'."\r\n";			// Téléphone du contact (21 caractères max.)
	$xml .= "\t\t".'<client_contact_portable>'.escapeXMLString($address->phone_mobile).'</client_contact_portable>'."\r\n";		// Téléphone portable du contact (21 caractères max.)
	$xml .= "\t\t".'<client_contact_telecopie>'.''.'</client_contact_telecopie>'."\r\n";										// Télécopie du contact (21 caractères max.)	
	$xml .= "\t\t".'<client_contact_email>'.escapeXMLString($customer->email).'</client_contact_email>'."\r\n";					// Email du contact (69 caractères max.)
	$xml .= "\t\t".'<client_contact_civilite>'.$civility.'</client_contact_civilite>'."\r\n";									// 0= Mr. 1= Mme 2= Mlle
	$xml .= "\t\t".'<client_contact_type>'.'1'.'</client_contact_type>'."\r\n";													// Numéro d'index du type de contact ( 1,2,3...) 
	
	// Informations libre du client
	// Le nom de l'information libre doit correspondre exactement à celui créé dans Sage Gestion Commerciale
	// Le format des dates doit être YYYY-MM-DD
	// les nombres doivent avoir le . comme séparateur décimal
	// Seul les types 'Date longue', 'Texte', 'Valeur' et 'Montant' sont gérés. 
	//
	// name = l'intitulé de l'information libre configuré dans Sage
	// type = le type de données de l'information libre (text, date , amount, value)
	// value = la valeur de l'information libre 
	//
	$xml .= "\t\t<custom_fields_customer>\r\n";													// Informations libre du client
	/*
		// Exemple 
		$xml .= '\t\t\t<custom_field_customer type="amount"		name="Capital social" 			value="10000.00" />\r\n';
		$xml .= '\t\t\t<custom_field_customer type="date"		name="Date création société"	value="2006-08-15" />\r\n';
		$xml .= '\t\t\t<custom_field_customer type="text"		name="Activité" 				value="Grossiste" />\r\n';
		$xml .= '\t\t\t<custom_field_customer type="value"		name="Solde" 					value="1245.00" />\r\n';
	*/
	$xml .= "\t\t</custom_fields_customer>\r\n";		
	
	$zone = strtoupper($address->country);
	if ($zone != 'FRANCE')
		$zone ='INTERNATIONAL';
	$SousZone = 'A_PHILIPPE';
	$TypeMagasin = 'SITE INTERNET';
	$NomGroupement = 'PARTICULIER';
	$Secteur = 'A_PHILIPPE';
	$Departement = substr($address->postcode,0,2);
	$Classe = 'W';
	$Canal = 'INTERNET';
	
	// Les 10 Statistiques tiers
	// Note: le texte de la statistique doit correspondre exactement à celui créé dans Sage Gestion Commerciale
	$xml .= "\t\t<customer_statistique01>".escapeXMLString($zone)."</customer_statistique01>\r\n";
	$xml .= "\t\t<customer_statistique02>".escapeXMLString($SousZone)."</customer_statistique02>\r\n";
	$xml .= "\t\t<customer_statistique03>".escapeXMLString($TypeMagasin)."</customer_statistique03>\r\n";
	$xml .= "\t\t<customer_statistique04>".escapeXMLString($NomGroupement)."</customer_statistique04>\r\n";
	$xml .= "\t\t<customer_statistique05>".escapeXMLString($Secteur)."</customer_statistique05>\r\n";
	$xml .= "\t\t<customer_statistique06>".escapeXMLString($Departement)."</customer_statistique06>\r\n";
	$xml .= "\t\t<customer_statistique07>".escapeXMLString($Classe)."</customer_statistique07>\r\n";
	$xml .= "\t\t<customer_statistique08>".escapeXMLString($Canal)."</customer_statistique08>\r\n";
	$xml .= "\t\t<customer_statistique09>".''."</customer_statistique09>\r\n";
	$xml .= "\t\t<customer_statistique10>".''."</customer_statistique10>\r\n";
		
	return $xml;
}

Function _CustomizeCustomerAccount($id_customer, $id_order) 
{

	/* Lit les informations du client */
	$sql= "SELECT * FROM `"._DB_PREFIX_."customer` WHERE `id_customer`= '".intval($id_customer)."'";
	$client = Db::getInstance()->getRow($sql);
	
			

  /*
    Selon la boutique de la commande un code client différent est renseigné
    
    COMPTOIR POUR LA BOUTIQUE CAISSERIE
    CDAVSO POUR LA BOUTIQUE DAVSO
    CCORDELIERS POUR LA BOUTIQUE CORDELIERS
    CNICE POUR LA BOUTIQUE NICE
    CBREA POUR LA BOUTIQUE BREA
    CTEMPLE POUR LA BOUTIQUE TEMPLE
    CLILLE POUR LA BOUTIQUE LILLE
    CAIXAMPERE POUR LA BOUTIQUE AIX
    CFR180081 Pour la boutique POPUP
  */
  $order = new Order((int)$id_order);
  switch ($order->id_shop) {
    case 2: // BOUTIQUE AIX
      return 'CAIXAMPERE';
      break;
    
    case 3: // Caisserie 
      return 'COMPTOIR';
      break;
    
    case 4: // Cordeliers
      return 'CCORDELIERS';
      break;
      
    case 5: // DAVSO
      return 'CDAVSO';
      break;
      
    case 6: // Lille
      return 'CLILLE';
      break;
   
    case 7: // Nice
      return 'CNICE';
      break;
      
    case 8: // Oaris Brea
      return 'CBREA';
    
    case 9: // Paris Temple
      return 'CTEMPLE';
         
    case 10: // LCDP BOUTIQUE POPUP
      return 'CFR180081';
      break;
  }

		
	/* Si le code client est vide */
	if (empty($client['atoosync_code_client']))
	{
		/* Exemple de formatage sur 12 caractères */
		/* Code client = WEBDOJE00124 */
		/*
		$numero = "WEB";													// 3 caractères pour le prefix = WEB
		$numero .= substr($client['lastname'],0,2);							// 2 premiers caractères du nom de famille = DUPONT
		$numero .= substr($client['firstname'],0,2);						// 2 premiers caractères du prénom = Jean
		$numero .= str_pad($client['id_customer'], 5, "0", STR_PAD_LEFT);	// 5 caractères pour l'ID du client avec des zéro devant = 124
		$numero = strtoupper($numero);										// Mettre en majuscule le code client
		*/		

		/* laissez comme cela si vous ne voulez pas utilisez cette fonction */
		$numero = '';
			
		/* Enregistre le nouveau code client du client si il n'est pas vide */
		if (!empty($numero))
		{
			$sql= "UPDATE `"._DB_PREFIX_."customer` SET `atoosync_code_client`='".$numero."' WHERE `id_customer`= '".intval($id_customer)."'";
			Db::getInstance()->Execute($sql);
		}
	} 
	/* Sinon retourne le code client déjà existant */
	else 
	{
		$numero = $client['atoosync_code_client'];
	}
		
	/* 17 caractères maximum pour la longueur des numéros de compte dans Sage Gestion Commerciale. */
	return substr($numero,0,17);
}

function _CustomizeOrder($id_order, $id_customer)
{
    $order = new Order((int)($id_order));
    
    $souche ='';
    switch ($order->id_shop) {
        case 2: // BOUTIQUE AIX
            $souche = 'AIX_AMPERE';
            break;
        
        case 3: // Caisserie 
            $souche = 'COMPTOIR';
            break;
        
        case 4: // Cordeliers
            $souche = 'AIX_CORDELIERS';
            break;
          
        case 5: // DAVSO
            $souche = 'DAVSO';
            break;
          
        case 6: // Lille
            $souche = 'LILLE';
            break;
       
        case 7: // Nice
            $souche = 'NICE';
            break;
          
        case 8: // Oaris Brea
            $souche = 'BREA';
            break;
        
        case 9: // Paris Temple
            $souche = 'TEMPLE';
            break; 
    }

  
    $xml = '';
    $xml .= "\t\t<commande_souche>".escapeXMLString($souche)."</commande_souche>\r\n";						// Souche de la commande (nom de la souche), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<commande_depot>".''."</commande_depot>\r\n";							// Depot de la commande (nom du depot), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<commande_statut>".'0'."</commande_statut>\r\n";						// Le statut de la commande (0,1,2,3), 0 pour utiliser la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<commande_entete1>".''."</commande_entete1>\r\n";						// Entete 1 du bon de commande
    $xml .= "\t\t<commande_entete2>".''."</commande_entete2>\r\n";						// Entete 2 du bon de commande
    $xml .= "\t\t<commande_entete3>".''."</commande_entete3>\r\n";						// Entete 3 du bon de commande
    $xml .= "\t\t<commande_entete4>".''."</commande_entete4>\r\n";						// Entete 4 du bon de commande
    $xml .= "\t\t<commande_representant>".''."</commande_representant>\r\n";			// Nom et prénom du représentant, si non renseigné le représentant de la fiche est utilisé
    $xml .= "\t\t<commande_code_affaire>".''."</commande_code_affaire>\r\n";			// Numéro du code affaire de type détail, si non renseigné le code affaire de la fiche est utilisé
    $xml .= "\t\t<commande_compte_payeur>".''."</commande_compte_payeur>\r\n";			// Compte payeur de la commande, si non renseigné le compte payeur du client est utilisé
    $xml .= "\t\t<commande_centrale_achat>".''."</commande_centrale_achat>\r\n";	    // Numéro de la centrale d'achat de la commande, si non renseigné la centrale d'achat du client est utilisé
    $xml .= "\t\t<commande_categorie_tarifaire>".''."</commande_categorie_tarifaire>\r\n";	// Numéro d'index de la catégorie tarifaire ( 1,2,3...) si renseigné remplace la configuration d'Atoo-Sync GesCom Pro

    // Informations libre du document de vente
    // Le nom de l'information libre doit correspondre exactement à celui créé dans Sage Gestion Commerciale
    // Le format des dates doit être YYYY-MM-DD
    // les nombres doivent avoir le . comme séparateur décimal
    // Seul les types 'Date longue', 'Texte', 'Valeur' et 'Montant' sont gérés.
    //
    // name = l'intitulé de l'information libre configuré dans Sage
    // type = le type de données de l'information libre (text, date , amount, value)
    // value = la valeur de l'information libre
    //
    $xml .= "\t\t<custom_fields_order>\r\n";											// Informations libre du document de vente
    
    /*
        demande : pour les transport colissimo ajouter champ libre point relais
    */
        $sqlColissimo = 'SELECT * FROM `'._DB_PREFIX_.'socolissimo_delivery_info` WHERE id_cart ='.$order->id_cart.' AND id_customer ='.$id_customer;
        $resultatColissimmo = Db::getInstance()->ExecuteS($sqlColissimo);
        
        $sqlChronopost = 'SELECT * FROM `'._DB_PREFIX_.'chrono_cart_relais` WHERE id_cart ='.$order->id_cart;
        $resultatChronopost = Db::getInstance()->ExecuteS($sqlChronopost);
        if ($resultatColissimmo) {
            $xml .= "\t\t\t".'<custom_field_order type="text"	name="POINT_RELAIS" 	value="'.escapeXMLString($resultatColissimmo[0]['prid']).'" />'."\r\n";
        }
        elseif ($resultatChronopost) {
            $xml .= "\t\t\t".'<custom_field_order type="text"	name="POINT_RELAIS" 	value="'.escapeXMLString($resultatChronopost[0]['id_pr']).'" />'."\r\n";
        }
    $xml .= "\t\t</custom_fields_order>\r\n";

    return $xml;
}

return;
?>