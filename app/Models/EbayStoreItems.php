<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/23
 * Time: 8:24
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EbayStoreItems extends Eloquent
{
    protected $connection = 'mysql2'; //this will use the specified database connection
    protected $table = 'ebay_store_items';
    protected $primaryKey = 'id';
    public $timestamps = false;
    CONST STATUS_ACTIVE = 'active';
    CONST STATUS_DEL    = 'del';
    private static $_getInstance;

    /**
     * return a instance of obj
     */
    public static function getInstance()
    {
        if(isset(self::$_getInstance) && self::$_getInstance instanceof EbayStoreItems)
            return self::$_getInstance;
        return  self::$_getInstance = new EbayStoreItems;
    }
    /**
     * get local active item's part
     * @param int $soterId store ID
     * @return array
     */
    public function getStoreActiveParts($storeId){
        $parts = array();
        $data = $this::leftJoin('ebay_items_desc',function($query){
            $query->on('ebay_store_items.desc_id','=','ebay_items_desc.id');
        })->select('ebay_items_desc.part')->where([['ebay_store_items.store_id',$storeId],['ebay_store_items.status',self::STATUS_ACTIVE]])->get();
        foreach($data as $item){
            $parts[] = strtoupper(trim($item->part));
        }
        return $parts;
    }
    public  function getItemById($itemid,$status=self::STATUS_ACTIVE)
    {
        $this->setConnection($this->connection);
        $item = $this::where([
            ['itemid','=',$itemid],
            ['status','=',$status],
        ])->get();
        if(count($item) > 0)
            return true;
        else
            return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function desc()
    {
        return $this->belongsTo('App\Models\EbayItemsDesc','desc_id','id');
    }

    public function scopeActive($query)
    {
        return $query->where('status',self::STATUS_ACTIVE);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }


}