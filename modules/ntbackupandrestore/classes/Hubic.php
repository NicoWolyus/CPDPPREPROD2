<?php
/**
* 2013-2019 2N Technologies
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@2n-tech.com so we can send you a copy immediately.
*
* @author    2N Technologies <contact@2n-tech.com>
* @copyright 2013-2019 2N Technologies
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class Hubic extends ObjectModel
{
    /** @var    boolean     active */
    public $active;

    /** @var    String      name */
    public $name;

    /** @var    integer     nb_backup */
    public $nb_backup;

    /** @var    integer     nb_backup_file */
    public $nb_backup_file;

    /** @var    integer     nb_backup_base */
    public $nb_backup_base;

    /** @var    String      directory */
    public $directory;

    /** @var    String      token */
    public $token;

    /** @var    String      credential */
    public $credential;

    /** @var    String      date_add */
    public $date_add;

    /** @var    String      date_upd */
    public $date_upd;

/**********************************************************/

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'             => 'ntbr_hubic',
        'primary'           => 'id_ntbr_hubic',
        'multilang'         => false,
        'multilang_shop'    => false,
        'fields'            => array(
            'active'                =>  array(
                'type'      => self::TYPE_BOOL,
                'validate'  => 'isBool',
                'default'   => '0',
            ),
            'name'                  =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isGenericName',
                'size'      => 255,
                'required'  => true,
                'default'   => 'Hubic',
            ),
            'nb_backup'             =>  array(
                'type'      => self::TYPE_INT,
                'validate'  => 'isUnsignedInt',
                'default'   => '0',
            ),
            'nb_backup_file'        =>  array(
                'type'      => self::TYPE_INT,
                'validate'  => 'isUnsignedInt',
                'default'   => '0',
            ),
            'nb_backup_base'        =>  array(
                'type'      => self::TYPE_INT,
                'validate'  => 'isUnsignedInt',
                'default'   => '0',
            ),
            'directory'             =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'default'   => '',
            ),
            'token'                 =>  array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'default'   => '',
            ),
            'credential'      =>  array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'default'   => '',
            ),
            'date_add'              =>  array(
                'type'      => self::TYPE_DATE,
                'validate'  => 'isDate',
            ),
            'date_upd'              =>  array(
                'type'      => self::TYPE_DATE,
                'validate'  => 'isDate',
            ),
        )
    );

    /**
     * Get the default values
     *
     * @return  array   Default values
     */
    public static function getDefaultValues()
    {
        $default_values = array();

        $default_values[self::$definition['primary']] = 0;

        foreach (self::$definition['fields'] as $name => $field) {
            if (isset($field['default'])) {
                $default_values[$name] = $field['default'];
            }
        }

        return $default_values;
    }

    /**
     * Get a list of all Hubic accounts
     *
     * @return  array   List of all Hubic accounts
     */
    public static function getListHubicAccounts()
    {
        $hubic_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_hubic`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `directory`,
                `token`, `credential`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            ORDER BY `name`
        ');

        if (!is_array($hubic_accounts)) {
            return array();
        }

        return $hubic_accounts;
    }

    /**
     * Get a list of all active Hubic accounts
     *
     * @return  array   List of all active Hubic accounts
     */
    public static function getListActiveHubicAccounts()
    {
        $hubic_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_hubic`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `directory`,
                `token`, `credential`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            WHERE `active` = 1
            ORDER BY `name`
        ');

        if (!is_array($hubic_accounts)) {
            return array();
        }

        return $hubic_accounts;
    }

    /**
     * Get Hubic account data by ID
     *
     * @param   integer     $id_ntbr_hubic  ID of the Hubic account
     *
     * @return  array                       Data of the account
     */
    public static function getHubicAccountById($id_ntbr_hubic)
    {
        $hubic_account = Db::getInstance()->getRow('
            SELECT `id_ntbr_hubic`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `directory`,
                `token`, `credential`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            WHERE `id_ntbr_hubic` = '.(int)$id_ntbr_hubic.'
        ');

        if (!is_array($hubic_account)) {
            return array();
        }

        return $hubic_account;
    }

    /**
     * Get Hubic account token by ID
     *
     * @param   integer     $id_ntbr_hubic  ID of the Hubic account
     *
     * @return  String                      Token of the account
     */
    public static function getHubicTokenById($id_ntbr_hubic)
    {
        return Db::getInstance()->getValue('
            SELECT `token`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            WHERE `id_ntbr_hubic` = '.(int)$id_ntbr_hubic.'
        ');
    }

    /**
     * Get Hubic account credential by ID
     *
     * @param   integer     $id_ntbr_hubic  ID of the Hubic account
     *
     * @return  String                      Credential of the account
     */
    public static function getHubicCredentialById($id_ntbr_hubic)
    {
        return Db::getInstance()->getValue('
            SELECT `credential`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            WHERE `id_ntbr_hubic` = '.(int)$id_ntbr_hubic.'
        ');
    }

    /**
     * Get Hubic account connection infos (token and credential) by ID
     *
     * @param   integer     $id_ntbr_hubic  ID of the Hubic account
     *
     * @return  array                       Token and credential of the account
     */
    public static function getHubicConnectionInfosById($id_ntbr_hubic)
    {
        return Db::getInstance()->getRow('
            SELECT `token`, `credential`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            WHERE `id_ntbr_hubic` = '.(int)$id_ntbr_hubic.'
        ');
    }

    /**
     * Get Hubic account ID by name
     *
     * @param   array   $name   Name of the Hubic account
     *
     * @return  String          ID of the account
     */
    public static function getIdByName($name)
    {
        return (int)Db::getInstance()->getValue('
            SELECT `id_ntbr_hubic`
            FROM `'._DB_PREFIX_.'ntbr_hubic`
            WHERE `name` = "'.pSQL($name).'"
        ');
    }
}
