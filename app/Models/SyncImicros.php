<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 11:26
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class SyncImicros extends Eloquent
{
    protected $connection = 'mysql2'; //this will use the specified database connection
    protected $table = 'sync_imicros';
    protected $primaryKey = 'itemid';
    public $timestamps = false;
    protected $fillable = [
        'itemid',
        'status',
        'dealed',
        'ts',
    ];
    public function getEndItemList($storeId,$storeName)
    {
        $this->setConnection($this->connection);
        $itemidArr = self::pluck('itemid')->toArray();
        return EbayStoreItems::select('itemid','id')->where([
            ['store_id',$storeId],
            ['status',EbayStoreItems::STATUS_ACTIVE],
        ])->whereNotIn('itemid',$itemidArr)->get();
    }

}