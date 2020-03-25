<?php
/**
 * Modulo Product Combinations
 *
 * @author    Giuseppe Tripiciano <admin@areaunix.org>
 * @copyright Copyright (c) 2018 Giuseppe Tripiciano
 * @license   You cannot redistribute or resell this code.
 */

class ProductComb extends ObjectModel
{
    public $id_product;
    public $image;
    public $combinations;

    public static $definition = array(
        'table' => 'productcombinations',
        'primary' => 'id_product',
        'multilang' => false,
        'fields' => array(
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'image' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'combinations' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
        ),
    );

    public static function loadByIdProduct($id_product)
    {
        $result = Db::getInstance()->getRow('
            SELECT *
            FROM `'._DB_PREFIX_.'productcombinations` pc
            WHERE pc.`id_product` = '.(int)$id_product);

        return new ProductComb($result['id_product']);
    }
}
