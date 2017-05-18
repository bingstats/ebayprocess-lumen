<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 15:59
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Trading\Types\GetItemRequestType;

class GetItem
{
    protected static $_convertObject;
    private static function setItemID($id)
    {
        self::$_convertObject->ItemID = $id;
    }

    private static function setDetailLevel($level)
    {
        self::$_convertObject->DetailLevel = $level;
    }

    public static function convert($objectArray)
    {
        self::$_convertObject = new GetItemRequestType();
        foreach($objectArray as $k=>$v){
            $fun = 'set'.ucfirst($k);
            if(method_exists(__CLASS__,$fun)){
                self::$fun($v);
            }
        }
        return self::$_convertObject;

    }
}