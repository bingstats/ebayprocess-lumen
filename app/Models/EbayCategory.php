<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 10:52
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EbayCategory extends Eloquent
{
    protected $table = 'ebay_category';
    protected $primaryKey = 'id';
    protected $connection = 'mysql2';
    private static $_getInstance;


    /**
     * @return object EwizCatTree
     */
    public static function getInstance()
    {
        if(isset(self::$_getInstance) && self::$_getInstance instanceof EwizCatTree)
            return self::$_getInstance;
        return  self::$_getInstance = new EwizCatTree;
    }

    public function getDataByEwizCateId($ewizCateId=0)
    {
        return $this::leftJoin('ebay_cat_map',function($query){
            $query->on('ebay_category.ebay_cate_id','=','ebay_cat_map.ebay_cate_id');
        })->select('ebay_category.*','ebay_cat_map.ewiz_cate_id')->where('ebay_cat_map.ewiz_cate_id',intValue($ewizCateId))->first();
    }

}