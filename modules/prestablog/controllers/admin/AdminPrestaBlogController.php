<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

class AdminPrestaBlogController extends ModuleAdminController
{
    public function initContent()
    {
        if (!$this->viewAccess()) {
            $this->errors[] = Tools::displayError('You do not have permission to view this.');
            return;
        }

        $id_tab = (int)Tab::getIdFromClassName('AdminModules');
        $id_employee = (int)$this->context->cookie->id_employee;
        $token = Tools::getAdminToken('AdminModules'.$id_tab.$id_employee);
        Tools::redirectAdmin('index.php?controller=AdminModules&configure=prestablog&token='.$token);
    }
}
