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
 * Télécharge un fichier
 */
function DownloadAttachment($id_attachment)
{
    if (!empty($id_attachment) and is_numeric($id_attachment)) {
        $a = new Attachment((int)($id_attachment), IdLangDefault());

        header('Content-Transfer-Encoding: binary');
        header('Content-Type: ' . $a->mime);
        header('Content-Length: ' . filesize(_PS_DOWNLOAD_DIR_ . $a->file));
        header('Content-Disposition: attachment; filename="' . $a->name . '"');
        readfile(_PS_DOWNLOAD_DIR_ . $a->file);
    }
}

/*
 * La liste des documents
 */
function GetAttachments()
{
    $attachments = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'attachment');
    foreach ($attachments as $att => $row) {
        $File = '';
        $File .= $row['id_attachment'];
        echo $File . '<br>';
    }
}

/*
 * Supprime le document
 */
function DeleteAttachment($id_attachment)
{
    if (!empty($id_attachment) and is_numeric($id_attachment)) {
        $a = new Attachment((int)($id_attachment));
        $a->delete();
    }
}

/*
 * les informations d'un document
 */
function GetAttachment($id_attachment)
{
    if (!empty($id_attachment) and is_numeric($id_attachment)) {
        $att = new Attachment((int)($id_attachment));
        $atoosync_file = Db::getInstance()->getValue('SELECT `atoosync_file` FROM `' . _DB_PREFIX_ . 'attachment` WHERE `id_attachment` = \'' . (int)($att->id) . '\'');
        $default_name = Db::getInstance()->getValue('SELECT `name` FROM `' . _DB_PREFIX_ . 'attachment_lang` WHERE `id_attachment` = ' . (int)($att->id) . ' AND `id_lang`=' . (int)(IdLangDefault()));
        $default_description = Db::getInstance()->getValue('SELECT `description` FROM `' . _DB_PREFIX_ . 'attachment_lang` WHERE `id_attachment` = ' . (int)($att->id) . ' AND `id_lang`=' . (int)(IdLangDefault()));

        // Entete du XML de l'article
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
        $xml .= "<attachment>\r\n";
        $xml .= "\t<id>" . $att->id . "</id>\r\n";
        $xml .= "\t<file>" . $att->file . "</file>\r\n";
        $xml .= "\t<file_name>" . escapeXMLString($att->file_name) . "</file_name>\r\n";
        $xml .= "\t<atoosync_file>" . escapeXMLString($atoosync_file) . "</atoosync_file>\r\n";
        $xml .= "\t<default_name>" . escapeXMLString($default_name) . "</default_name>\r\n";
        $xml .= "\t<default_description>" . escapeXMLString($default_description) . "</default_description>\r\n";

        /* Nom */
        $xml .= "\t<names>\r\n";
        foreach ($att->name as $lang => $texte) {
            $tmp = html_entity_decode(strip_tags(br2nl($texte)), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<name id_lang=\"" . $lang . "\">" . $tmp . "</name>\r\n";
        }
        $xml .= "\t</names>\r\n";

        /* Description */
        $xml .= "\t<descriptions>\r\n";
        foreach ($att->description as $lang => $texte) {
            $tmp = html_entity_decode(strip_tags(br2nl($texte)), ENT_QUOTES, 'UTF-8');
            $tmp = escapeXMLString($tmp);
            $xml .= "\t\t<description id_lang=\"" . $lang . "\">" . $tmp . "</description>\r\n";
        }
        $xml .= "\t</descriptions>\r\n";

        $products = Db::getInstance()->ExecuteS('
							SELECT id_product
							FROM ' . _DB_PREFIX_ . 'product_attachment 
							WHERE id_attachment=' . (int)($att->id));

        $xml .= "\t<products>\r\n";
        foreach ($products as $p => $row) {
            $reference = Db::getInstance()->getValue('SELECT `reference` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . (int)($row['id_product']));
            if ($reference) {
                $xml .= "\t\t<product>" . $reference . "</product>\r\n";
            }
        }
        $xml .= "\t</products>\r\n";

        $xml .= "</attachment>\r\n";

        header("Content-type: text/xml");
        echo $xml;
        return 1;
    }
    return 0;
}

/*
 * Test si le document existe.
 * Retourne 1 si le document existe et si on ne modifie pas les documents, sinon 0
 */
function AttachmentExist($atoosyncfile)
{
    $retval = 0;
    if (!empty($atoosyncfile) and is_string($atoosyncfile)) {

        // Si le document existe
        $id_attachment = Db::getInstance()->getValue('SELECT `id_attachment` FROM `' . _DB_PREFIX_ . 'attachment` WHERE `atoosync_file`=\'' . strval((string)($atoosyncfile)) . '\'');
        if ($id_attachment) {
            /* Retourne 1 si le document existe et si on ne change pas les documents */
            if (Configuration::get('ATOOSYNC_CHANGE_DOCUMENT') == 'No') {
                $retval = 1;
            }
        }
    }
    return $retval;
}

/*
 * Retourne l'id du document
 */
function GetAttachmentId($atoosyncfile)
{
    $retval = 0;

    if (!empty($atoosyncfile) and is_string($atoosyncfile)) {

        // Si le document existe
        $id_attachment = Db::getInstance()->getValue('SELECT `id_attachment` FROM `' . _DB_PREFIX_ . 'attachment` WHERE `atoosync_file`=\'' . strval((string)($atoosyncfile)) . '\'');
        if ($id_attachment) {
            echo $id_attachment . '<br>';
            $retval = 1;
        }
    }
    return $retval;
}

/*
 * Créé le document joint
 */
function AddAttachment($xml)
{
    /* Si il y a une erreur avec le chargement du fichier */
    if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
        echo file_upload_error_message($_FILES['file']['error']);
        return 0;
    }

    $AttachmentXML = LoadXML(Tools::stripslashes($xml));
    if (empty($AttachmentXML)) {
        return 0;
    }

    $retval = 0;
    $id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));

    $sql = 'SELECT `id_attachment` FROM `' . _DB_PREFIX_ . 'attachment` 
			WHERE `atoosync_file`=\'' . (string)($AttachmentXML->atoosync_file) . '\'';
    $id_attachment = Db::getInstance()->getValue($sql);
    if (!$id_attachment) {
        if (isset($_FILES['file']) and is_uploaded_file($_FILES['file']['tmp_name'])) {
            $tmpfile = $_FILES['file']['tmp_name'];
            $file = basename($_FILES['file']['name']);
            $uploadDir = _PS_DOWNLOAD_DIR_;

            // Si le nom du fichier est plus long que 32
            if (strlen($file) > 32) {
                $extension = substr(strrchr($file, '.'), 1);
                $file = substr($file, 0, 31 - strlen($extension)) . '.' . $extension;
            }
            $names = createMultiLangField($file);
            $descriptions = createMultiLangField($file);

            /* Les descriptions */
            foreach ($AttachmentXML->descriptions->description as $descr) {
                $tmp = (string)($descr);
                if (!empty($tmp)) {
                    $descriptions[(int)($descr['id_lang'])] = $tmp;
                }
            }
            /* Utilise le nom pour PS 1.4 et 1.5 sinon le nom du fichier pour PS1.2 et 1.3 */
            if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
                foreach ($AttachmentXML->names->name as $name) {
                    $tmp = (string)($name);
                    if (!empty($tmp)) {
                        $names[(int)($name['id_lang'])] = $tmp;
                    }
                }
            }

            /* Genére l'id unique pour le document */
            do {
                $uniqid = sha1(microtime());
            } while (file_exists($uploadDir . $uniqid));
            if (copy($tmpfile, $uploadDir . $uniqid)) {
                $mime = 'application/octet-stream';
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $uploadDir . $uniqid);
                    finfo_close($finfo);
                } elseif (function_exists('mime_content_type')) {
                    $mime = mime_content_type($uploadDir . $uniqid);
                }

                // si mime type > 32
                if (strlen($mime) > 32) {
                    $mime = 'application/octet-stream';
                }

                $a = new Attachment();
                $a->file = $uniqid;
                $a->file_name = $AttachmentXML->file_name;
                $a->mime = $mime;
                $a->name = $names;
                $a->description = $descriptions;

                if ($a->add()) {
                    /* Met à jour le document avec le SHA1 du fichier d'Atoo-Sync GesCom Pro */
                    $query = "UPDATE `" . _DB_PREFIX_ . "attachment` SET `atoosync_file`='" . pSQL($AttachmentXML->atoosync_file) . "' WHERE `id_attachment`='" . (int)($a->id) . "'";
                    Db::getInstance()->Execute($query);

                    $retval = 1;
                } else {
                    echo 'Error : Attachment->add()';
                }
            } else {
                echo 'Error Creation : copy(' . $tmpfile . ', ' . $uploadDir . $a->file . ')';
            }
        } else {
            echo "Error : isset(" . $_FILES['file'] . ") AND is_uploaded_file(" . $_FILES['file']['tmp_name'] . ")";
        }
    } else {
        /* Modifie que les textes si le document existe déjà */
        $a = new Attachment($id_attachment);

        // fixe le mime_type
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, _PS_DOWNLOAD_DIR_ . $a->file);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type(_PS_DOWNLOAD_DIR_ . $a->file);
        }
        // si mime type > 32
        if (strlen($mime) > 32) {
            $mime = 'application/octet-stream';
        }

        $a->mime = $mime;

        /* Les descriptions du fichier */
        foreach ($AttachmentXML->descriptions->description as $descr) {
            $tmp = (string)($descr);
            if (!empty($tmp)) {
                $a->description[(int)($descr['id_lang'])] = $tmp;
            }
        }

        /* Les noms du fichier */
        if (isPrestaShop14() or isPrestaShop15() or isPrestaShop16() or isPrestaShop17()) {
            foreach ($AttachmentXML->names->name as $name) {
                $tmp = (string)($name);
                if (!empty($tmp)) {
                    $a->name[(int)($name['id_lang'])] = $tmp;
                }
            }
        }

        if ($a->update()) {
            $retval = 1;
        } else {
            echo 'Error : Attachment->update()';
        }
    }
    return $retval;
}
