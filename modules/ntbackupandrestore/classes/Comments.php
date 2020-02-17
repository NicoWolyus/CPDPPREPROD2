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

class Comments extends ObjectModel
{
    /** @var    String      backup_name */
    public $backup_name;

    /** @var    String      comment */
    public $comment;

    /** @var    String      date_add */
    public $date_add;

    /** @var    String      date_upd */
    public $date_upd;

/**********************************************************/

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'             => 'ntbr_comments',
        'primary'           => 'id_ntbr_comments',
        'multilang'         => false,
        'multilang_shop'    => false,
        'fields'            => array(
            'backup_name'              =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
                'required'  => true,
            ),
            'comment'         =>  array(
                'type'      => self::TYPE_STRING,
                'validate'  => 'isString',
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
     * Get the comment of a backup
     *
     * @param   String   $backup_name   Name of the backup
     *
     * @return  String                  Comment of the backup
     */
    public static function getBackupComment($backup_name)
    {
        $comment = Db::getInstance()->getValue('
            SELECT `comment`
            FROM `'._DB_PREFIX_.'ntbr_comments`
            WHERE `backup_name` = "'.pSQL($backup_name).'"
        ');

        return $comment;
    }

    /**
     * Get infos of the comment of a backup
     *
     * @param   String   $backup_name   Name of the backup
     *
     * @return  array                   Infos of the comment of the backup
     */
    public static function getBackupCommentInfos($backup_name)
    {
        $infos = Db::getInstance()->getRow('
            SELECT `id_ntbr_comments`, `comment`
            FROM `'._DB_PREFIX_.'ntbr_comments`
            WHERE `backup_name` = "'.pSQL($backup_name).'"
        ');

        if (!is_array($infos)) {
            return array();
        }

        return $infos;
    }

    /**
     * Get list of comments of backups
     *
     * @return  array   List of comments of backups
     */
    public static function getListBackupComment()
    {
        $comments = array();

        $list = Db::getInstance()->executeS('
            SELECT `id_ntbr_comments`, `backup_name`, `comment`
            FROM `'._DB_PREFIX_.'ntbr_comments`
        ');

        if (!is_array($list)) {
            return array();
        }

        foreach ($list as $item) {
            $comments[$item['backup_name']] = $item;
        }

        return $comments;
    }
}
