<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 13:30
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EbayStCatMap extends Eloquent
{
    protected $table = 'ebay_st_cat_map';
    protected $connection = 'mysql2';
    protected $primaryKey = 'id';
    protected $fillable = [];
    private static $_getInstance;

    /**
     * return a instance of obj
     */
    public static function getInstance()
    {
        if(isset(self::$_getInstance) && self::$_getInstance instanceof EwizCatTree)
            return self::$_getInstance;
        return  self::$_getInstance = new EwizCatTree;
    }
    public function getEbayStoreCategoryId($ewizCateId, $storeId)
    {
        return $this::where([['store_id',$storeId],['ewiz_cat_id',$ewizCateId]])->first();
    }
    static public function criteriaByEwizAndStoreID($stID,$ewizID)
    {
        return self::where([['store_id',$stID],['ewiz_cat_id',$ewizID]])->get();
    }
}