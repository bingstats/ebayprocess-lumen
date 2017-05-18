<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/24
 * Time: 10:28
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Trading\Types\EndItemRequestType;

class EndItem
{
    protected static $_convertObject;

    private static function setItemID($id)
    {
        self::$_convertObject->ItemID = $id;
    }

    private static function setEndingReason($reason)
    {
        self::$_convertObject->EndingReason = $reason;
    }

    public static function convert($objectArray)
    {
        self::$_convertObject = new EndItemRequestType();
        if(is_array($objectArray)){
            foreach($objectArray as $k => $v){
                $fun = 'set'.ucFirst($k);
                if(method_exists('EndItem',$fun)){
                    self::$fun($v);
                }
            }
        }
        return self::$_convertObject;
    }


}