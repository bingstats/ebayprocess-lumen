<?php

namespace DTS\eBaySDK\Trading\Services;
use \DTS\eBaySDK\Trading\Types;

class GetItemTransactions
{
	
/**
 * Set The Params.
 */
	protected static $_convertObject;
	
	private static function setModTimeFrom($time)
    {
        self::$_convertObject->ModTimeFrom = $time; 
    }

    private static function setModTimeTo($time)
    {
        self::$_convertObject->ModTimeTo = $time;
    }

    private static function setItemID($id)
    {	
        self::$_convertObject->ItemID = $id;
    }
    private static function setDetailLevel($level)
    {
        self::$_convertObject->DetailLevel = $level;
    }
    private static function setNumberOfDays($days)
    {
        self::$_convertObject->NumberOfDays = $days;
    }
	private static function setTransactionID($ebay_txn_id)
    {
        self::$_convertObject->TransactionID = $ebay_txn_id;
    }
	
    public static function convert($array)
    {	
        self::$_convertObject = new Types\GetItemTransactionsRequestType();
        foreach($array as $k => $v){         
			$fun = 'set'.$k;			
            if(method_exists(__CLASS__,$fun)){
                self::$fun($v);
            }
        }
        return self::$_convertObject;
    }

}