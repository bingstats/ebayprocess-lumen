<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/10
 * Time: 8:41
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class TckEbayTransactionId extends Eloquent
{
    protected $table = 'tck_ebayTransactionId';
    protected $primaryKey = 'item_id';
    public $timestamps = false;
    protected $fillable = [
        'item_id',
        'ebayTransactionId',
        'invoice',
        'ebayItemNum',
    ];
}