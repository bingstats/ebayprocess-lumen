<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 14:27
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
class EbayStores extends Eloquent
{
    protected $connection = 'mysql2'; //this will use the specified database connection
    protected $table = 'ebay_stores';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'atime',
        'update',
        'update_done',
    ];

    static function get()
    {

    }
    public function getStoreIDByName($name)
    {
        $storeinfo =array();
        $res = app('db')->connection($this->connection)->select('select id,update,update_done from ebay_stores WHERE storename=;name',[':name'=>$name]);
        if(count($res) > 0){
            foreach($res as $k => $v){
                $storeinfo[$k] = $v;
            }
            return $storeinfo;
        }
    }
}