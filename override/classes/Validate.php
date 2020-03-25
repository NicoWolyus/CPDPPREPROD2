<?php
/**
 *   2009-2019 ohmyweb!
 *
 *   @author    ohmyweb <contact@ohmyweb.fr>
 *   @copyright 2009-2019 ohmyweb!
 *   @license   Proprietary - no redistribution without authorization
 */
class Validate extends ValidateCore
{
    /*
    * module: dwfproductextrafields
    * date: 2020-02-06 18:51:34
    * version: 1.7.36
    */
    public static function isDbColumn($column)
    {
        return preg_match('/^[a-z0-9_]{0,127}$/', $column);
    }
}
