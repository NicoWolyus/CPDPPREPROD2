<?php
/**
 * Regenerate thumbnails
 *
 * Allows you to regenerate thumbnails
 * if the prestashop's method doesn't work.
 *
 * @category  Prestashop
 * @category  Module
 * @author    Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license   commercial license see license.txt
 * @link      SIL OFL 1.1 http://scripts.sil.org/OFL license logo
 * @link      http://fontawesome.io Dave Gandy author logo
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include(_PS_MODULE_DIR_.'regeneratethumbnails/autoloader.php');
spl_autoload_register('regenerateThumbnailsAutoload');

class RegenerateThumbnails extends Samdha_RegenerateThumbnails_Main
{
    public function __construct()
    {
        $this->author = 'Samdha';
        $this->name = 'regeneratethumbnails';
        $this->tab = 'administration';

        $this->version = '2.6.0';
        $this->module_key = 'a982656ba7822fea24fa802670384ba2';
        $this->id_addons = 676;

        parent::__construct();

        $this->displayName = $this->l('Regenerate thumbnails');
        $this->description =
            $this->l('Allows you to regenerate thumbnails if the prestashop\'s method doesn\'t work.');
    }
}
