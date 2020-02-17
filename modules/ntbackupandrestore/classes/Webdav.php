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

class Webdav extends ObjectModel
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

    /** @var    String      login */
    public $login;

    /** @var    String      password */
    public $password;

    /** @var    String      server */
    public $server;

    /** @var    String      directory */
    public $directory;

    /** @var    String      date_add */
    public $date_add;

    /** @var    String      date_upd */
    public $date_upd;

/**********************************************************/

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'             => 'ntbr_webdav',
        'primary'           => 'id_ntbr_webdav',
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
                'default'   => 'WebDAV',
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
            'login'             =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'password'          =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'server'            =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'directory'         =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
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
     * Get a list of all WebDAV accounts
     *
     * @return  array   List of all WebDAV accounts
     */
    public static function getListWebdavAccounts()
    {
        $webdav_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_webdav`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `login`,
                `password`, `server`, `directory`
            FROM `'._DB_PREFIX_.'ntbr_webdav`
            ORDER BY `name`
        ');

        if (!is_array($webdav_accounts)) {
            return array();
        }

        return $webdav_accounts;
    }

    /**
     * Get a list of all active WebDAV accounts
     *
     * @return  array   List of all active WebDAV accounts
     */
    public static function getListActiveWebdavAccounts()
    {
        $webdav_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_webdav`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `login`,
                `password`, `server`, `directory`
            FROM `'._DB_PREFIX_.'ntbr_webdav`
            WHERE `active` = 1
            ORDER BY `name`
        ');

        if (!is_array($webdav_accounts)) {
            return array();
        }

        return $webdav_accounts;
    }

    /**
     * Get WebDAV account data by ID
     *
     * @param   integer     $id_ntbr_webdav     ID of the WebDAV account
     *
     * @return  array                           Data of the account
     */
    public static function getWebdavAccountById($id_ntbr_webdav)
    {
        $webdav_account = Db::getInstance()->getRow('
            SELECT `id_ntbr_webdav`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`, `login`,
                `password`, `server`, `directory`
            FROM `'._DB_PREFIX_.'ntbr_webdav`
            WHERE `id_ntbr_webdav` = '.(int)$id_ntbr_webdav.'
        ');

        if (!is_array($webdav_account)) {
            return array();
        }

        return $webdav_account;
    }

    /**
     * Get WebDAV account ID by name
     *
     * @param   String      $name   Name of the WebDAV account
     *
     * @return  integer             ID of the account
     */
    public static function getIdByName($name)
    {
        return (int)Db::getInstance()->getValue('
            SELECT `id_ntbr_webdav`
            FROM `'._DB_PREFIX_.'ntbr_webdav`
            WHERE `name` = "'.pSQL($name).'"
        ');
    }

    /**
     * Deactive all WebDAV accounts
     *
     * @return  boolean     Success or failure of the operation
     */
    public static function deactiveAllWebdav()
    {
        return Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'ntbr_webdav`
            SET `active` = 0
        ');
    }
}
