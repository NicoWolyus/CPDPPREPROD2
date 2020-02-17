<?php
/*
================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
  Ce fichier fait partie du logiciel Atoo-Sync GesCom Pro.
  Vous n'êtes pas autorisé à le modifier, à le recopier, à le vendre ou le redistribuer.
  Cet en-tête ne doit pas être retiré.

      Script : AtooSync-tools.php
    Boutique : PrestaShop
      Auteur : Atoo Next SARL (support@atoo-next.net)
   Copyright : 2009-2020 Atoo Next SARL

================================================================================
  /!\ Ne peut être utilisé en dehors du programme Atoo-Sync GesCom Pro /!\
================================================================================
*/

//================================================================================
// Fonction qui test la connection à la base MySQL
// Essaye de lire le nom de la boutique
// ================================================================================
function Test()
{
    $retval=0;
    //
    if (Configuration::get('PS_SHOP_NAME')) {
        echo 'Version : '._ATOOSYNCSCRIPTVERSION_.'<br>';
        echo 'eCommerce : '._ECOMMERCESHOP_.'<br>';
        $retval=1;
    }
    //
    return $retval;
}
// ================================================================================
// Supprimer le fichier temporaire des commandes
// ================================================================================
function DeleteTempFiles($filename)
{
    // Supprime le fichier XML atoo-Sync
    if (file_exists($filename)) {
        @unlink($filename);
    }
}
/*
    Charge une structure XML
    Affiche les erreurs si il y en a.
*/
function LoadXML($XML)
{
    if (empty($XML)) {
        echo "XML is empty !";
        return;
    }
    libxml_use_internal_errors(true);
    //
    $smpXML = simplexml_load_string($XML);
    if (!$smpXML) {
        echo "XML error !\n";
        foreach (libxml_get_errors() as $error) {
            echo "Line: $error->line Column: $error->column  ";
            echo  $error->message."\n";
        }
        libxml_clear_errors();
    } else {
        return $smpXML;
    }
}

/*
*/
function cleanName($string)
{
    $search  = array('<', '>', ';', '=', '#', '{', '}');
    $replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ');
    return str_replace($search, $replace, $string);
}
function CreateMultiLangField($field)
{
    $languages = Language::getLanguages(false);
    $res = array();
    foreach ($languages as $lang) {
        $res[$lang['id_lang']] = $field;
    }
    return $res;
}
function br2nl($string)
{
    return str_replace(array("<br>", "<br />"), "\r\n", $string);
}
function escapeXMLString($string)
{
    $tmp =  str_replace('"', "&quot;", $string);
    $tmp =  str_replace("&", "&amp;", $tmp);
    $tmp =  str_replace("<", "&lt;", $tmp);
    $tmp =  str_replace(">", "&gt;", $tmp);
    $tmp =  str_replace("’", "'", $tmp);
    return $tmp;
}

function file_upload_error_message($error_code)
{
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
function StrToUTF8($string)
{
    if (mb_detect_encoding($string) != "UTF-8") {
        return utf8_encode($string);
    } else {
        return $string;
    }
}
