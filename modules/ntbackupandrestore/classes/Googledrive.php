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

class Googledrive extends ObjectModel
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

    /** @var    String      directory_key */
    public $directory_key;

    /** @var    String      directory_path */
    public $directory_path;

    /** @var    String      token */
    public $token;

    /** @var    String      date_add */
    public $date_add;

    /** @var    String      date_upd */
    public $date_upd;

/**********************************************************/

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'             => 'ntbr_googledrive',
        'primary'           => 'id_ntbr_googledrive',
        'multilang'         => false,
        'multilang_shop'    => false,
        'fields'            => array(
            'active'            =>  array(
                'type'      => self::TYPE_BOOL,
                'validate'  => 'isBool',
                'default'   => '0',
            ),
            'name'              =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isGenericName',
                'size'      => 255,
                'required'  => true,
                'default'   => 'Google Drive',
            ),
            'nb_backup'         =>  array(
                'type'      => self::TYPE_INT,
                'validate'  => 'isUnsignedInt',
                'default'   => '0',
            ),
            'nb_backup_file'    =>  array(
                'type'      => self::TYPE_INT,
                'validate'  => 'isUnsignedInt',
                'default'   => '0',
            ),
            'nb_backup_base'    =>  array(
                'type'      => self::TYPE_INT,
                'validate'  => 'isUnsignedInt',
                'default'   => '0',
            ),
            'directory_key'     =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'directory_path'    =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'default'   => '',
            ),
            'token'             =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'required'  => true,
                'default'   => '',
            ),
            'date_add'          =>  array(
                'type'      => self::TYPE_DATE,
                'validate'  => 'isDate',
            ),
            'date_upd'          =>  array(
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
     * Get a list of all Google Drive accounts
     *
     * @return  array   List of all Google Drive accounts
     */
    public static function getListGoogledriveAccounts()
    {
        $googledrive_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_googledrive`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`,
                `directory_key`, `directory_path`, `token`
            FROM `'._DB_PREFIX_.'ntbr_googledrive`
            ORDER BY `name`
        ');

        if (!is_array($googledrive_accounts)) {
            return array();
        }

        return $googledrive_accounts;
    }

    /**
     * Get a list of all active Google Drive accounts
     *
     * @return  array   List of all active Google Drive accounts
     */
    public static function getListActiveGoogledriveAccounts()
    {
        $googledrive_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_googledrive`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`,
                `directory_key`, `directory_path`, `token`
            FROM `'._DB_PREFIX_.'ntbr_googledrive`
            WHERE `active` = 1
            ORDER BY `name`
        ');

        if (!is_array($googledrive_accounts)) {
            return array();
        }

        return $googledrive_accounts;
    }

    /**
     * Get Google Drive account data by ID
     *
     * @param   integer     $id_ntbr_googledrive    ID of the Google Drive account
     *
     * @return  array                               Data of the account
     */
    public static function getGoogledriveAccountById($id_ntbr_googledrive)
    {
        $googledrive_account = Db::getInstance()->getRow('
            SELECT `id_ntbr_googledrive`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`,
                `directory_key`, `directory_path`, `token`
            FROM `'._DB_PREFIX_.'ntbr_googledrive`
            WHERE `id_ntbr_googledrive` = '.(int)$id_ntbr_googledrive.'
        ');

        if (!is_array($googledrive_account)) {
            return array();
        }

        return $googledrive_account;
    }

    /**
     * Get Google Drive account token by ID
     *
     * @param   integer     $id_ntbr_googledrive    ID of the Google Drive account
     *
     * @return  String                              Token of the account
     */
    public static function getGoogledriveTokenById($id_ntbr_googledrive)
    {
        return Db::getInstance()->getValue('
            SELECT `token`
            FROM `'._DB_PREFIX_.'ntbr_googledrive`
            WHERE `id_ntbr_googledrive` = '.(int)$id_ntbr_googledrive.'
        ');
    }

    /**
     * Get Google Drive account ID by name
     *
     * @param   String      $name   Name of the Google Drive account
     *
     * @return  integer             ID of the account
     */
    public static function getIdByName($name)
    {
        return (int)Db::getInstance()->getValue('
            SELECT `id_ntbr_googledrive`
            FROM `'._DB_PREFIX_.'ntbr_googledrive`
            WHERE `name` = "'.pSQL($name).'"
        ');
    }
}
