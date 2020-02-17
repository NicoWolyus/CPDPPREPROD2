<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from MigrationPro
* Use, copy, modification or distribution of this source file without written
* license agreement from the MigrationPro is strictly forbidden.
* In order to obtain a license, please contact us: contact@migration-pro.com
*
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe MigrationPro
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la MigrationPro est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter la MigrationPro a l'adresse: contact@migration-pro.com
*
* @author    MigrationPro
* @copyright Copyright (c) 2012-2019 MigrationPro
* @license   Commercial license
* @package   MigrationPro: Prestashop Upgrade and Migrate tool
*/

if (!defined('_PS_VERSION_')) {
    exit;
}


class AdminMigrationProRedirectController extends AdminController
{
    public function __construct()
    {
        if (Tools::getValue('controller') == "AdminMigrationProRedirect") {
            Tools::redirectAdmin('index.php?controller=AdminModules&configure=migrationpro&token=' . Tools::getAdminTokenLite('AdminModules'));
        }
        $this->display = 'edit';
        parent::__construct();
        $this->controller_type = 'moduleadmin'; //instead of AdminController’s admin
        $tab = new Tab($this->id); // an instance with your tab is created; if the tab is not attached to the module, the exception will be thrown
        if (!$tab->module) {
            throw new PrestaShopException('Admin tab ' . get_class($this) . ' is not a module tab');
        }
        $this->module = Module::getInstanceByName($tab->module);
        if (!$this->module->id) {
            throw new PrestaShopException("Module {$tab->module} not found");
        }
        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminMigrationRedirect'));
    }
}
