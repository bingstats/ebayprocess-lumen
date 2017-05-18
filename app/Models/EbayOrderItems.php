<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/4
 * Time: 10:58
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class EbayOrderItems extends  Eloquent
{

    protected $table = 'ebayOrderItems';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'verify',
        'retry',
    ];

    public function order()
    {
        return $this->hasOne('EbayOrder','orderid','orderid');
    }

}