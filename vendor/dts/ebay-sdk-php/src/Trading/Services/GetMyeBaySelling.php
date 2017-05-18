<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 14:00
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Shopping\Enums\ListingTypeCodeType;
use DTS\eBaySDK\Trading\Enums\DetailLevelCodeType;
use DTS\eBaySDK\Trading\Enums\ItemSortTypeCodeType;
use DTS\eBaySDK\Trading\Types\GetMyeBaySellingRequestType;
use DTS\eBaySDK\Trading\Types\ItemListCustomizationType;
use DTS\eBaySDK\Trading\Types\PaginationType;

class GetMyeBaySelling
{
    /**
     * set params
     */
    private static $_activList;
    private static $_pagination;
    protected static $_convertObject;

    private static function setListingType($codeType = ListingTypeCodeType::C_FIXED_PRICE_ITEM)
    {
        self::$_activList->ListingType = $codeType;
    }

    private static function setDetailLevel($codeType = DetailLevelCodeType::C_RETURN_ALL)
    {
        self::$_convertObject->DetailLevel = $codeType;
    }

    private static function setEntriesPerPage($num)
    {
        self::$_pagination->EntriesPerPage = $num;
    }

    private static function setPageNumber($num)
    {
        self::$_pagination->PageNumber = $num;
    }

    private static function setPagination()
    {
        self::$_activList->Pagination = self::$_pagination;
    }

    private static function setSort($type = ItemSortTypeCodeType::C_END_TIME)
    {
        self::$_activList->Sort = $type;
    }

    private static function setActiveList()
    {
        self::$_convertObject->ActiveList = self::$_activList;
    }

    public static function convert($objectArray)
    {
        self::$_convertObject = new GetMyeBaySellingRequestType();
        self::$_activList = new ItemListCustomizationType();
        self::$_pagination = new PaginationType();

        if(is_array($objectArray)){
            foreach($objectArray as $k => $v){
                $fun = 'set'.ucfirst($k);
                if(method_exists(__CLASS__,$fun)){
                    self::$fun($v);
                }
            }
        }
        return self::$_convertObject;
    }

}
