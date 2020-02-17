<?php 
/*
================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
  Ce fichier fait partie du logiciel Atoo-Sync GesCom Pro.
  Vous n'êtes pas autorisé à le modifier, à le vendre ou le redistribuer.
  Cet en-tête ne doit pas être retiré.

      Script : userfunctions.php
      Auteur : Atoo Next SARL (support@atoo-next.net)
   Copyright : 2009-2020 Atoo Next SARL
================================================================================
*/
//================================================================================
// Fonction executée avant le début du transert vers la boutique
// ================================================================================
function AtooSyncToStart()
{
    if (function_exists('_AtooSyncToStart')) {
        _AtooSyncToStart();
    }
}
//================================================================================
// Fonction executée à la fin du transfert vers la boutique
// ================================================================================
function AtooSyncToEnd()
{
    if (function_exists('_AtooSyncToEnd')) {
        _AtooSyncToEnd();
    }
}
//================================================================================
// Fonction executée avant le début de la mise à jour des prix dans la boutique
// ================================================================================
function AtooSyncPriceStart()
{
    if (function_exists('_AtooSyncPriceStart')) {
        _AtooSyncPriceStart();
    }
}
//================================================================================
// Fonction executée à la fin de la mise à jour des prix dans la boutique
// ================================================================================
function AtooSyncPriceEnd()
{
    if (function_exists('_AtooSyncPriceEnd')) {
        _AtooSyncPriceEnd();
    }
}
//================================================================================
// Fonction executée avant le début de la mise à jour du stock dans la boutique
// ================================================================================
function AtooSyncStockStart()
{
    if (function_exists('_AtooSyncStockStart')) {
        _AtooSyncStockStart();
    }
}
//================================================================================
// Fonction executée à la fin de la mise à jour du stock dans la boutique
// ================================================================================
function AtooSyncStockEnd()
{
    if (function_exists('_AtooSyncStockEnd')) {
        _AtooSyncStockEnd();
    }
}
//================================================================================
// Fonction executée avant le début du transert vers le logiciel de gestion
// ================================================================================
function AtooSyncFromStart()
{
    if (function_exists('_AtooSyncFromStart')) {
        _AtooSyncFromStart();
    }
}
//================================================================================
// Fonction executée à la fin du transfert vers le logiciel de gestion
// ================================================================================
function AtooSyncFromEnd()
{
    if (function_exists('_AtooSyncFromEnd')) {
        _AtooSyncFromEnd();
    }
}
//================================================================================
// Fonction executée avant le début de la mise à jour des statuts des commandes
// ================================================================================
function AtooSyncStatusStart()
{
    if (function_exists('_AtooSyncStatusStart')) {
        _AtooSyncStatusStart();
    }
}
//================================================================================
// Fonction executée à la fin de la mise à jour des statuts des commandes
// ================================================================================
function AtooSyncStatusEnd()
{
    if (function_exists('_AtooSyncStatusEnd')) {
        _AtooSyncStatusEnd();
    }
}
//================================================================================
// Fonction executée avant le début de la mise à jour des clients
// ================================================================================
function AtooSyncCustomersStart()
{
    if (function_exists('_AtooSyncCustomersStart')) {
        _AtooSyncCustomersStart();
    }
}
//================================================================================
// Fonction executée à la fin de la mise à jour des clients
// ================================================================================
function AtooSyncCustomersStop()
{
    if (function_exists('_AtooSyncCustomersStop')) {
        _AtooSyncCustomersStop();
    }
}
/*
 * Fonction permettant de renseigner les champs supplémentaire de la fiche client dans Sage
 * Attention les informations renseignées ici doivent exister dans Sage Gestion Commerciale
 */
function CustomizeCustomerOrder($id_order, $id_customer)
{
    $customer = new Customer($id_customer);
    $order = new Order((int)$id_order);
    $address = new Address((int)$order->id_address_invoice);
    $id_group =  $customer->id_default_group;
    
    // Trouve la catégorie tarifaire du groupe par défaut du client
    $cattarif = Db::getInstance()->getValue('
			SELECT `atoosync_id`
			FROM `'._DB_PREFIX_.'group`
			WHERE `id_group` = '.(int)($id_group));
    
    if ((int)$cattarif == 0) {
        $cattarif ='';
    }
    
    // Selon la version de PS
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $identifiant = $address->vat_number;
        $siret = $customer->siret;
        $ape = $customer->ape;
    } elseif (isPrestaShop14()) {
        $identifiant = $address->dni;
        $siret = '';
        $ape = '';
    } elseif (isPrestaShop13()) {
        $identifiant = $customer->dni;
        $siret = '';
        $ape = '';
    } else {
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
    if ($customer->id_gender == 1) {
        $civility= '0';
    } // 0= Mr. 1= Mme 2= Mlle
    if ($customer->id_gender == 2) {
        $civility= '1';
    } // Mme
        
    $xml .= "\t\t".'<client_contact_nom>'.escapeXMLString($address->lastname).'</client_contact_nom>'."\r\n";					// Nom du contact (35 caractères max.)
    $xml .= "\t\t".'<client_contact_prenom>'.escapeXMLString($address->firstname).'</client_contact_prenom>'."\r\n";			// Prénom du contact (35 caractères max.)
    $xml .= "\t\t".'<client_contact_service>'.'1'.'</client_contact_service>'."\r\n";											// Numéro d'index du service du contact ( 1,2,3...)
    $xml .= "\t\t".'<client_contact_fonction>'.''.'</client_contact_fonction>'."\r\n";											// Fonction du contact (35 caractères max.)
    $xml .= "\t\t".'<client_contact_telephone>'.escapeXMLString($address->phone).'</client_contact_telephone>'."\r\n";			// Téléphone du contact (21 caractères max.)
    $xml .= "\t\t".'<client_contact_portable>'.escapeXMLString($address->phone_mobile).'</client_contact_portable>'."\r\n";		// Téléphone portable du contact (21 caractères max.)
    $xml .= "\t\t".'<client_contact_telecopie>'.''.'</client_contact_telecopie>'."\r\n";										// Télécopie du contact (21 caractères max.)
    $xml .= "\t\t".'<client_contact_email>'.escapeXMLString($customer->email).'</client_contact_email>'."\r\n";					// Email du contact (69 caractères max.)
    $xml .= "\t\t".'<client_contact_civilite>'.$civility.'</client_contact_civilite>'."\r\n";									          // 0= Mr. 1= Mme 2= Mlle
    $xml .= "\t\t".'<client_contact_type>'.'1'.'</client_contact_type>'."\r\n";													                // Numéro d'index du type de contact ( 1,2,3...)
    
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
    
    // Les 10 Statistiques tiers
    // Note: le texte de la statistique doit correspondre exactement à celui créé dans Sage Gestion Commerciale
    $xml .= "\t\t<customer_statistique01>".''."</customer_statistique01>\r\n";
    $xml .= "\t\t<customer_statistique02>".''."</customer_statistique02>\r\n";
    $xml .= "\t\t<customer_statistique03>".''."</customer_statistique03>\r\n";
    $xml .= "\t\t<customer_statistique04>".''."</customer_statistique04>\r\n";
    $xml .= "\t\t<customer_statistique05>".''."</customer_statistique05>\r\n";
    $xml .= "\t\t<customer_statistique06>".''."</customer_statistique06>\r\n";
    $xml .= "\t\t<customer_statistique07>".''."</customer_statistique07>\r\n";
    $xml .= "\t\t<customer_statistique08>".''."</customer_statistique08>\r\n";
    $xml .= "\t\t<customer_statistique09>".''."</customer_statistique09>\r\n";
    $xml .= "\t\t<customer_statistique10>".''."</customer_statistique10>\r\n";
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeCustomerOrder')) {
        $xml = _CustomizeCustomerOrder($id_order, $id_customer);
    }
    
    return $xml;
}
/*
 * Fonction permettant de renseigner les champs supplémentaire du bon de commande
 * Attention les informations renseignées ici doivent exister dans Sage Gestion Commerciale
 */
function CustomizeOrder($id_order, $id_customer)
{
    $xml = '';
    $xml .= "\t\t<commande_souche>".''."</commande_souche>\r\n";						// Souche de la commande (nom de la souche), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
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
        // Exemple d'information libre
        $xml .= "\t\t\t".'<custom_field_order type="text"	name="Commentaires" 	value="le commentaire de la commande" />'."\r\n";
    */
    $xml .= "\t\t</custom_fields_order>\r\n";
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrder')) {
        $xml = _CustomizeOrder($id_order, $id_customer);
    }
        
    return $xml;
}
/*
 * Fonction permettant de renseigner les champs supplémentaire du retour produit
 * Attention les informations renseignées ici doivent exister dans Sage Gestion Commerciale
 */
function CustomizeOrderReturn($order_return)
{
    $xml = '';
    $xml .= "\t\t<sage_souche>".''."</sage_souche>\r\n";						// Souche de la commande (nom de la souche), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<sage_depot>".''."</sage_depot>\r\n";							// Depot de la commande (nom du depot), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<sage_statut>".'0'."</sage_statut>\r\n";						// Le statut de la commande (0,1,2,3), 0 pour utiliser la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<sage_entete1>".''."</sage_entete1>\r\n";						// Entete 1 du bon de commande
    $xml .= "\t\t<sage_entete2>".''."</sage_entete2>\r\n";						// Entete 2 du bon de commande
    $xml .= "\t\t<sage_entete3>".''."</sage_entete3>\r\n";						// Entete 3 du bon de commande
    $xml .= "\t\t<sage_entete4>".''."</sage_entete4>\r\n";						// Entete 4 du bon de commande
    $xml .= "\t\t<sage_representant>".''."</sage_representant>\r\n";			// Nom et prénom du représentant, si non renseigné le représentant de la fiche est utilisé
    $xml .= "\t\t<sage_code_affaire>".''."</sage_code_affaire>\r\n";			// Numéro du code affaire de type détail, si non renseigné le code affaire de la fiche est utilisé
    $xml .= "\t\t<sage_compte_payeur>".''."</sage_compte_payeur>\r\n";			// Compte payeur de la commande, si non renseigné le compte payeur du client est utilisé
    $xml .= "\t\t<sage_centrale_achat>".''."</sage_centrale_achat>\r\n";	    // Numéro de la centrale d'achat de la commande, si non renseigné la centrale d'achat du client est utilisé
 
 
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
        // Exemple d'information libre
        $xml .= "\t\t\t".'<custom_field_order type="text"	name="Commentaires" 	value="le commentaire de la commande" />'."\r\n";
    */
    $xml .= "\t\t</custom_fields_order>\r\n";
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderReturn')) {
        $xml = _CustomizeOrderReturn($order_return);
    }
        
    return $xml;
}
/*
 * Fonction permettant de renseigner les champs supplémentaire d'avoir
 * Attention les informations renseignées ici doivent exister dans Sage Gestion Commerciale
 */
function CustomizeOrderSlip($order_slip)
{
    $xml = '';
    $xml .= "\t\t<sage_souche>".''."</sage_souche>\r\n";						// Souche de la commande (nom de la souche), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<sage_depot>".''."</sage_depot>\r\n";							// Depot de la commande (nom du depot), si renseigné remplace la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<sage_statut>".'0'."</sage_statut>\r\n";						// Le statut de la commande (0,1,2,3), 0 pour utiliser la configuration d'Atoo-Sync GesCom Pro
    $xml .= "\t\t<sage_entete1>".''."</sage_entete1>\r\n";						// Entete 1 du bon de commande
    $xml .= "\t\t<sage_entete2>".''."</sage_entete2>\r\n";						// Entete 2 du bon de commande
    $xml .= "\t\t<sage_entete3>".''."</sage_entete3>\r\n";						// Entete 3 du bon de commande
    $xml .= "\t\t<sage_entete4>".''."</sage_entete4>\r\n";						// Entete 4 du bon de commande
    $xml .= "\t\t<sage_representant>".''."</sage_representant>\r\n";			// Nom et prénom du représentant, si non renseigné le représentant de la fiche est utilisé
    $xml .= "\t\t<sage_code_affaire>".''."</sage_code_affaire>\r\n";			// Numéro du code affaire de type détail, si non renseigné le code affaire de la fiche est utilisé
    $xml .= "\t\t<sage_compte_payeur>".''."</sage_compte_payeur>\r\n";			// Compte payeur de la commande, si non renseigné le compte payeur du client est utilisé
    $xml .= "\t\t<sage_centrale_achat>".''."</sage_centrale_achat>\r\n";	    // Numéro de la centrale d'achat de la commande, si non renseigné la centrale d'achat du client est utilisé
  
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
        // Exemple d'information libre
        $xml .= "\t\t\t".'<custom_field_order type="text"	name="Commentaires" 	value="le commentaire de la commande" />'."\r\n";
    */
    $xml .= "\t\t</custom_fields_order>\r\n";
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderSlip')) {
        $xml = _CustomizeOrderSlip($order_slip);
    }
        
    return $xml;
}
/*
 * Fonction permettant de  les champs supplémentaire du bon de commande
 * Attention les informations renseignées ici doivent exister dans Sage Gestion Commerciale
 */
function CustomizeOrderMessages($id_order)
{
    $MessagesOrder ='';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderMessages')) {
        $MessagesOrder = _CustomizeOrderMessages($id_order);
    }
        
    return $MessagesOrder;
}
/*
    Fonction permettant de spécifier une référence pour la commande
*/
function CustomizeOrderReference($order)
{
    $reference = '';
    if (isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
        $reference = $order->reference;
    }
        
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderReference')) {
        $reference = _CustomizeOrderReference($order);
    }
        
    return $reference;
}
/*
    Fonction permettant de spécifier le paiement de la commande
*/
function CustomizeOrderPayment($order)
{
    if (Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'payment_method') {
        $payment = $order->payment;
    }
    
    if (Configuration::get('ATOOSYNC_ORDER_PAYMENT') == 'order_payment') {
        $payment = $order->payment;
    } else {
        $payment = $order->module;
    }
  
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderPayment')) {
        $payment = _CustomizeOrderPayment($order);
    }
        
    return $payment;
}
/*
    Fonction permettant de spécifier une référence pour l'article de la commande
*/
function CustomizeOrderProductReference($order, $product_detail)
{
    $reference = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderProductReference')) {
        $reference = _CustomizeOrderProductReference($order, $product_detail);
    }
        
    return $reference;
}
/*
    // Fonction permettant de renseigner les champs supplémentaire de l'article de la commande
*/
function CustomizeOrderProduct($id_order_detail)
{
    $xml = '';
    $xml .= "\t\t\t\t<serial_number>".''."</serial_number>\r\n";					// Numéro de série
    $xml .= "\t\t\t\t<depot>".''."</depot>\r\n";									// le nom du dépot pour la ligne d'article, si vide utilise celui de la commande
        
     /* Ajout des informations libre des lignes du document de vente
     * Le nom de l'information libre doit correspondre exactement à celui créé dans Sage Gestion Commerciale
     * Le format des dates doit être YYYY-MM-DD
     * les nombres doivent avoir le . comme séparateur décimal
     * Seul les types 'Date longue', 'Texte', 'Valeur' et 'Montant' sont gérés.
     *
     * name = l'intitulé de l'information libre configuré dans Sage
     * type = le type de données de l'information libre (text, date , amount, value)
     * value = la valeur de l'information libre
     */
     
    $xml .= "\t\t\t\t<custom_fields_product>\r\n";													// Informations libre
    /*
        // exemple de ligne
        $xml .= "\t\t\t\t\t".'<custom_field_product type="text"		name="Commentaires" 	value="le commentaire de la ligne" />'."\r\n";
    */
    $xml .= "\t\t\t\t</custom_fields_product>\r\n";
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderProduct')) {
        $xml = _CustomizeOrderProduct($id_order_detail);
    }
        
    return $xml;
}
/*
 * Fonction permettant de renseigner les informations libre des lignes du document de vente
 * Le nom de l'information libre doit correspondre exactement à celui créé dans Sage Gestion Commerciale
 * Le format des dates doit être YYYY-MM-DD
 * les nombres doivent avoir le . comme séparateur décimal
 * Seul les types 'Date longue', 'Texte', 'Valeur' et 'Montant' sont gérés.
 *
 * name = l'intitulé de l'information libre configuré dans Sage
 * type = le type de données de l'information libre (text, date , amount, value)
 * value = la valeur de l'information libre
 */
function InfoLibresLignesDocument($id_order_detail)
{
    $xml = '';
    $xml .= "\t\t\t\t<info_libres_lignes>\r\n";													// Informations libre du client
    /*
        // exemple de ligne
        $xml .= "\t\t\t\t\t".'<info_libre_ligne type="text"		name="Commentaires" 	value="le commentaire de la ligne" />'."\r\n";
    */
    $xml .= "\t\t\t\t</info_libres_lignes>\r\n";

    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_InfoLibresLignesDocument')) {
        $xml = _InfoLibresLignesDocument($id_order_detail);
    }
        
    return $xml;
}

/*
    Fonction permettant de formater le code client qui sera envoyé dans le logiciel
    Atoo-Sync GesCom Pro pour la création du client dans Sage Gestion Commerciale.

    Note: Si vous utilisez cette fonction il faut enregistrer manuellement le numéro de
    compte client car le logiciel Atoo-Sync ne déclenche pas le processus de retour
    du numéro de lorsqu'il existe déjà dans le fichier XML des commandes.
*/
function CustomizeCustomerAccount($id_customer, $id_order)
{
    $numero ='';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeCustomerAccount')) {
        $numero = _CustomizeCustomerAccount($id_customer, $id_order);
    }
  
    if (empty($numero)) {
        /* Lit les informations du client */
        $sql= "SELECT * FROM `"._DB_PREFIX_."customer` WHERE `id_customer`= '".intval($id_customer)."'";
        $client = Db::getInstance()->getRow($sql);
    
        // Si client Invité alors utilise le numéro si configuré dans le module.
        if ((int)$client['is_guest'] == 1) {
            if (Configuration::get('ATOOSYNC_ORDER_GUESTACCOUNT') !='') {
                // Enregistre le numéro de compte des clients invités sur le client
                $numero = Configuration::get('ATOOSYNC_ORDER_GUESTACCOUNT');
                $sql= "UPDATE `"._DB_PREFIX_."customer` SET `atoosync_code_client`='".$numero."' WHERE `id_customer`= '".(int)($id_customer)."'";
                Db::getInstance()->Execute($sql);
        
                return $numero;
            }
        }
      
        // Si client POS alors utilise le numéro si configuré dans le module.
        if (Configuration::get('KERAWEN_ANONYMOUS_CUSTOMER') == $id_customer) {
            if (Configuration::get('ATOOSYNC_ORDER_POSACCOUNT') !='') {
                // Enregistre le numéro de compte des clients invités sur le client
                $numero = Configuration::get('ATOOSYNC_ORDER_POSACCOUNT');
                $sql= "UPDATE `"._DB_PREFIX_."customer` SET `atoosync_code_client`='".$numero."' WHERE `id_customer`= '".(int)($id_customer)."'";
                Db::getInstance()->Execute($sql);
        
                return $numero;
            }
        }
      
        /* Si le code client est vide */
        if (empty($client['atoosync_code_client'])) {
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
            if (!empty($numero)) {
                $sql= "UPDATE `"._DB_PREFIX_."customer` SET `atoosync_code_client`='".$numero."' WHERE `id_customer`= '".intval($id_customer)."'";
                Db::getInstance()->Execute($sql);
            }
        }
        /* Sinon retourne le code client déjà existant */
        else {
            $numero = $client['atoosync_code_client'];
        }
    }
    
    /* 17 caractères maximum pour la longueur des numéros de compte dans Sage Gestion Commerciale. */
    return substr($numero, 0, 17);
}
/*
    Fonction permettant de modifier la création des catégories.
*/
function CustomizeAddCategory($CategoryXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeAddCategory')) {
        return _CustomizeAddCategory($CategoryXML);
    }
    
    return false;
}
/*
    Fonction permettant de modifier le XML de l'article envoyé par Atoo-Sync .
*/
function CustomizeProductXML($ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeProductXML')) {
        return _CustomizeProductXML($ProductXML);
    }
    
    return $ProductXML;
}
/*
    Fonction permettant de modifier la création des articles.
*/
function CustomizeAddProduct($ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeAddProduct')) {
        return _CustomizeAddProduct($ProductXML);
    }
    
    return false;
}
/*
    Fonction permettant d'effectuer des traitements après l'intégration de l'article.
*/
function CustomizeProduct($Product, $ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeProduct')) {
        _CustomizeProduct($Product, $ProductXML);
    }
}
/*
    Fonction permettant d'effectuer des traitements lors de la création de l'article.
*/
function CustomizeNewProduct($Product, $ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeNewProduct')) {
        _CustomizeNewProduct($Product, $ProductXML);
    }
}
/*
    Fonction permettant d'effectuer des traitements après la création des déclinaisons de l'article.
*/
function CustomizeCombinations($Product, $ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeCombinations')) {
        _CustomizeCombinations($Product, $ProductXML);
    }
}
/*
    Fonction permettant d'effectuer des traitements après la création des attributs.
    Doit retourner True si la création des attributs est géré dans UserFunctions
*/
function CustomizeProductAttributes($ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeProductAttributes')) {
        return _CustomizeProductAttributes($ProductXML);
    }
    
    return false;
}
/*
    Fonction permettant de remplacer la création du conditionnement de l'article.
*/
function CustomizePackagings($Product, $ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    // la fonction doit retourner true pour ne pas confitnuer le fonctionnement normal d'Atoo-Sync.
    if (function_exists('_CustomizePackagings')) {
        return _CustomizePackagings($Product, $ProductXML);
    }

    return false;
}
/*
    Fonction permettant de remplacer la création des prix spécifiques
*/
function CustomizeCreateSpecificPrice($Product, $ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeCreateSpecificPrice')) {
        return _CustomizeCreateSpecificPrice($Product, $ProductXML);
    }

    return false;
}
/*
    Fonction permettant de remplacer la mise à jour du prix de l'article.
*/
function CustomizeSetProductPrice($ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeSetProductPrice')) {
        return _CustomizeSetProductPrice($ProductXML);
    }

    return false;
}
/*
    Fonction permettant d'effectuer des traitements après la mise à jour du prix de l'article.
*/
function CustomizeProductPrice($Product, $ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeProductPrice')) {
        _CustomizeProductPrice($Product, $ProductXML);
    }
}
/*
    Fonction permettant de remplacer la mise à jour du stock de l'article.
*/
function CustomizeSetProductQuantity($ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeSetProductQuantity')) {
        return _CustomizeSetProductQuantity($ProductXML);
    }

    return false;
}
/*
    Fonction permettant d'effectuer des traitements après la mise à jour du stock de l'article.
*/
function CustomizeProductStock($ProductXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeProductStock')) {
        _CustomizeProductStock($ProductXML);
    }
}
/*
    Fonction permettant de définir les articles de la commande.
*/
function CustomizeNodeProducts($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeNodeProducts')) {
        $xml = _CustomizeNodeProducts($order);
    }
        
    return $xml;
}
/*
    Fonction permettant de spécifier les adresses de la commande.
*/
function CustomizeNodeAddresses($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeNodeAddresses')) {
        $xml = _CustomizeNodeAddresses($order);
    }
        
    return $xml;
}
/*
    Fonction permettant de modifier la création des clients.
*/
function CustomizeAddCustomer($CustomerXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeAddCustomer')) {
        return _CustomizeAddCustomer($CustomerXML);
    }
    
    return false;
}
/*
    Fonction permettant d'effectuer des traitements après la mise à jour d'un client.
*/
function CustomizeCustomer($Customer, $CustomerXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeCustomer')) {
        _CustomizeCustomer($Customer, $CustomerXML);
    }
}
/*
    Fonction permettant d'effectuer des traitements lors de la création d'un client.
*/
function CustomizeNewCustomer($Customer, $CustomerXML, $passwd)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeNewCustomer')) {
        _CustomizeNewCustomer($Customer, $CustomerXML, $passwd);
        return true;
    }
    
    // envoi l'email
    if (Configuration::get('ATOOSYNC_CUSTOMER_SEND_MAIL') == 'Yes') {
        Mail::Send(
            IdLangDefault(),
            'account',
            Mail::l('Welcome!'),
            array('{firstname}' => (string)($Customer->firstname),
                  '{lastname}' => (string)($Customer->lastname),
                  '{email}' => (string)($Customer->email),
                  '{passwd}' => (string)($passwd)),
            (string)($Customer->email),
            (string)($Customer->firstname.' '.$Customer->lastname)
        );
    }
}

/*
    Fonction permettant de modifier la création des groupes de clients.
*/
function CustomizeAddCustomersGroups($GroupsXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeAddCustomersGroups')) {
        return _CustomizeAddCustomersGroups($GroupsXML);
    }
    
    return false;
}
/*
    Fonction permettant de modifier la création/ou modification des adresses des clients.
*/
function CustomizeCustomerAddresses($Customer, $CustomerXML)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeCustomerAddresses')) {
        return _CustomizeCustomerAddresses($Customer, $CustomerXML);
    }
    
    return false;
}
/*
    Fonction permettant de spécifier l'adresse pour l'export vers Expeditor Inet.
*/
function CustomizeExpeditorInetAddress($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeExpeditorInetAddress')) {
        $xml = _CustomizeExpeditorInetAddress($order);
    }
        
    return $xml;
}
/*
    Fonction permettant de spécifier l'adresse pour l'export vers TNT.
*/
function CustomizeTNTAddress($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeTNTAddress')) {
        $xml = _CustomizeTNTAddress($order);
    }
        
    return $xml;
}
/*
    Fonction permettant d'ajouter des commandes dans le XML des commandes.
*/
function CustomizeXMLOrders($from, $to, $status, $reload = false, $all = false)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeXMLOrders')) {
        $xml = _CustomizeXMLOrders($from, $to, $status, $reload, $all);
    }
        
    return $xml;
}
/*
    Fonction permettant de customiser la notification des commandes transférées.
*/
function CustomizeOrderTransferred($id)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderTransferred')) {
        _CustomizeOrderTransferred($id);
    }
}
/*
    Fonction permettant de customiser la notification des avoirs transférées.
*/
function CustomizeOrderSlipTransferred($id)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderSlipTransferred')) {
        _CustomizeOrderSlipTransferred($id);
    }
}
/*
    Fonction permettant de customiser la notification des retours produits transférées.
*/
function CustomizeOrderReturnTransferred($id)
{
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderReturnTransferred')) {
        _CustomizeOrderReturnTransferred($id);
    }
}
/*
    Fonction permettant d'ajouter des articles avant les articles de la commande
*/
function CustomizeOrderAddProductsBefore($order)
{
    $xml = '';
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderAddProductsBefore')) {
        $xml = _CustomizeOrderAddProductsBefore($order);
    }
    
    return $xml;
}
/*
    Fonction permettant d'ajouter des articles après les articles de la commande
*/
function CustomizeOrderAddProductsAfter($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderAddProductsAfter')) {
        $xml = _CustomizeOrderAddProductsAfter($order);
    }
        
    return $xml;
}
/*
    Fonction permettant de modifier les bons de réductions de la commande
*/
function CustomizeOrderDiscounts($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderDiscounts')) {
        $xml = _CustomizeOrderDiscounts($order);
    }
        
    return $xml;
}
/*
    Fonction permettant de modifier le ou les réglements la commande
*/
function CustomizeOrderPayments($order)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeOrderPayments')) {
        $xml = _CustomizeOrderPayments($order);
    }
        
    return $xml;
}
/*
    Fonction permettant de modifier la désactivation des clients dans la boutique
*/
function CustomizeDisableCustomer($accountnumber)
{
    $xml = '';
    
    // Execute la function dans le _AtooSync-userfunctions.php si présent
    if (function_exists('_CustomizeDisableCustomer')) {
        $xml = _CustomizeDisableCustomer($accountnumber);
    }
        
    return $xml;
}

/*
 *  Fonction permettant de spécifier si la commande doit être créé en TTC ou HT
 */
function customizeCreateTaxesIncluded($order)
{
    // Execute la function dans le script de customisation si présent
    if (function_exists('_customizeCreateTaxesIncluded')) {
        return _customizeCreateTaxesIncluded($order);
    }
    return 2; // 2 = option par défaut dans Atoo-Sync, 0 = Créé HT, 1 = Créé TTC
}