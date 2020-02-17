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

class MigrationProMapping extends ObjectModel
{
    public $id;
    public $group;
    public $type;
    public $source_id;
    public $source_name;
    public $mapping;

    public static $definition = array(
        'table' => 'migrationpro_mapping',
        'primary' => 'id_mapping',
        'fields' => array(
            'group' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'source_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'source_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'mapping' => array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId')
        ),
    );

    public static function listMapping($list = false, $keyAsSourceId = false)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('migrationpro_mapping');
        $mappings = array();
        $rows = Db::getInstance()->executeS($sql);
        if (!$list) {
            return $rows;
        }

        if ($keyAsSourceId) {
            foreach ($rows as $row) {
                $mappings[$row['group']][$row['type']][$row['source_id']] = $row['mapping'];
            }
        } else {
            foreach ($rows as $row) {
                $mappings[$row['group']][$row['type']][] = array(
                    'id_mapping' => $row['id_mapping'],
                    'source_id' => $row['source_id'],
                    'source_name' => $row['source_name'],
                    'mapping' => $row['mapping']
                );
            }
        }

        return $mappings;
    }

    public static function getMapTypeCount($entity_type)
    {
        $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . 'migrationpro_mapping as map WHERE map.type=\'' . pSQL($entity_type) . '\' ';
        return Db::getInstance()->getValue($sql);
    }
}
