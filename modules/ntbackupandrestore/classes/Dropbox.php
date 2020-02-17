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

class Dropbox extends ObjectModel
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

    /** @var    String      date_add */
    public $date_add;

    /** @var    String      date_upd */
    public $date_upd;

/**********************************************************/

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'             => 'ntbr_dropbox',
        'primary'           => 'id_ntbr_dropbox',
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
                'default'   => 'Dropbox',
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
            'directory'         =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'default'   => '',
            ),
            'token'             =>  array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
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
     * Get a list of all Dropbox accounts
     *
     * @return  array   List of all Dropbox accounts
     */
    public static function getListDropboxAccounts()
    {
        $dropbox_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_dropbox`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `directory`,
                `token`
            FROM `'._DB_PREFIX_.'ntbr_dropbox`
            ORDER BY `name`
        ');

        if (!is_array($dropbox_accounts)) {
            return array();
        }

        return $dropbox_accounts;
    }

    /**
     * Get a list of all active Dropbox accounts
     *
     * @return  array   List of all active Dropbox accounts
     */
    public static function getListActiveDropboxAccounts()
    {
        $dropbox_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_dropbox`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `directory`,
                `token`
            FROM `'._DB_PREFIX_.'ntbr_dropbox`
            WHERE `active` = 1
            ORDER BY `name`
        ');

        if (!is_array($dropbox_accounts)) {
            return array();
        }

        return $dropbox_accounts;
    }

    /**
     * Get Dropbox account data by ID
     *
     * @param   integer     $id_ntbr_dropbox    ID of the Dropbox account
     *
     * @return  array                           Data of the account
     */
    public static function getDropboxAccountById($id_ntbr_dropbox)
    {
        $dropbox_account = Db::getInstance()->getRow('
            SELECT `id_ntbr_dropbox`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `directory`,
                `token`
            FROM `'._DB_PREFIX_.'ntbr_dropbox`
            WHERE `id_ntbr_dropbox` = '.(int)$id_ntbr_dropbox.'
        ');

        if (!is_array($dropbox_account)) {
            return array();
        }

        return $dropbox_account;
    }

    /**
     * Get Dropbox token by ID
     *
     * @param   integer     $id_ntbr_dropbox    ID of the Dropbox account
     *
     * @return  String                          Token the account
     */
    public static function getDropboxTokenById($id_ntbr_dropbox)
    {
        return Db::getInstance()->getValue('
            SELECT `token`
            FROM `'._DB_PREFIX_.'ntbr_dropbox`
            WHERE `id_ntbr_dropbox` = '.(int)$id_ntbr_dropbox.'
        ');
    }

    /**
     * Get Dropbox account ID by name
     *
     * @param   String      $name   Name of the Dropbox account
     *
     * @return  integer             ID of the account
     */
    public static function getIdByName($name)
    {
        return (int)Db::getInstance()->getValue('
            SELECT `id_ntbr_dropbox`
            FROM `'._DB_PREFIX_.'ntbr_dropbox`
            WHERE `name` = "'.pSQL($name).'"
        ');
    }
}
