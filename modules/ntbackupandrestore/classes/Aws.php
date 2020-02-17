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

class Aws extends ObjectModel
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

    /** @var    String      access_key_id */
    public $access_key_id;

    /** @var    String      secret_access_key */
    public $secret_access_key;

    /** @var    String      region */
    public $region;

    /** @var    String      bucket */
    public $bucket;

    /** @var    String      directory_key */
    public $directory_key;

    /** @var    String      directory_path */
    public $directory_path;

    /** @var    String      date_add */
    public $date_add;

    /** @var    String      date_upd */
    public $date_upd;

/**********************************************************/

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'             => 'ntbr_aws',
        'primary'           => 'id_ntbr_aws',
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
                'default'   => 'AWS',
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
            'access_key_id'     =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'secret_access_key' =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'region'     =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'bucket'     =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'required'  => true,
                'default'   => '',
            ),
            'directory_key'     =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'size'      => 255,
                'default'   => '',
            ),
            'directory_path'    =>  array(
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
     * Get a list of all AWS accounts
     *
     * @return  array   List of all AWS accounts
     */
    public static function getListAwsAccounts()
    {
        $aws_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_aws`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`,
                `access_key_id`, `secret_access_key`, `region`, `bucket`, `directory_key`, `directory_path`
            FROM `'._DB_PREFIX_.'ntbr_aws`
            ORDER BY `name`
        ');

        if (!is_array($aws_accounts)) {
            return array();
        }

        return $aws_accounts;
    }

    /**
     * Get a list of all active AWS accounts
     *
     * @return  array   List of all active AWS accounts
     */
    public static function getListActiveAwsAccounts()
    {
        $aws_accounts = Db::getInstance()->executeS('
            SELECT `id_ntbr_aws`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`,
                `access_key_id`, `secret_access_key`, `region`, `bucket`, `directory_key`, `directory_path`
            FROM `'._DB_PREFIX_.'ntbr_aws`
            WHERE `active` = 1
            ORDER BY `name`
        ');

        if (!is_array($aws_accounts)) {
            return array();
        }

        return $aws_accounts;
    }

    /**
     * Get AWS account data by ID
     *
     * @param   integer     $id_ntbr_aws    ID of the AWS account
     *
     * @return  array                       Data of the account
     */
    public static function getAwsAccountById($id_ntbr_aws)
    {
        $aws_account = Db::getInstance()->getRow('
            SELECT `id_ntbr_aws`, `active`, `name`, `nb_backup`, `nb_backup_file`, `nb_backup_base`,
                `access_key_id`, `secret_access_key`, `region`, `bucket`, `directory_key`, `directory_path`
            FROM `'._DB_PREFIX_.'ntbr_aws`
            WHERE `id_ntbr_aws` = '.(int)$id_ntbr_aws.'
        ');

        if (!is_array($aws_account)) {
            return array();
        }

        return $aws_account;
    }

    /**
     * Get AWS account ID by name
     *
     * @param   String      $name   Name of the AWS account
     *
     * @return  integer             ID of the account
     */
    public static function getIdByName($name)
    {
        return (int)Db::getInstance()->getValue('
            SELECT `id_ntbr_aws`
            FROM `'._DB_PREFIX_.'ntbr_aws`
            WHERE `name` = "'.pSQL($name).'"
        ');
    }
}
