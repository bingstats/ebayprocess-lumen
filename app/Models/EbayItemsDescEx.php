<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/13
 * Time: 9:20
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EbayItemsDescEx extends Eloquent
{
    protected $connection = 'mysql2'; //this will use the specified database connection
    protected $table = 'ebay_items_desc_ex';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'desc',
        'ewiz_price',
        'ewiz_cost',
        'ewiz_sox',
        'insurance',
        'tax',
        'final_fee',
        'paypal_fee',
        'profit',
        'mtime',
    ];

}