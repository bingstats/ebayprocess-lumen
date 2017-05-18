<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 15:05
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Trading\Types\GetSellerEventsRequestType;

class GetSellerEvents
{
    protected static $_convertObject;
    private static function setModTimeFrom($time)
    {
        self::$_convertObject->ModTimeFrom = $time;
    }

    private static function setModTimeTo($time)
    {
        self::$_convertObject->ModTimeTo = $time;
    }

    private static function setDetailLevel($type)
    {
        self::$_convertObject->DetailLevel = $type;
    }
    /*private static function setStartTimeFrom($time)
    {
        self::$_convertObject->StartTimeFrom = $time;
    }*/
    public static function convert($object)
    {
        self::$_convertObject = new GetSellerEventsRequestType();
        if(is_array($object)){
            foreach($object as $k=>$v){
                $fun = 'set'.ucfirst($k);
                if(method_exists(__CLASS__,$fun)){
                    self::$fun($v);
                }
            }
        }
        return self::$_convertObject;
    }
}