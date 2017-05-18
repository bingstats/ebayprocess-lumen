<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/9
 * Time: 14:58
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class InvoiceList extends Eloquent
{
    protected $table = 'invoice_list';
    protected $primaryKey = 'invoice';
    public $timestamps = false;
    protected $fillable = [
        'invoice',
        'channel',
        'uid',
        'email',
        'name',
        'azOrderNum',
        'ebayItem',
        'shipping',
        'tax',
        'total',
        'totalcost',
        'itemCost',
        'shippingCost',
        'shippingType',
        'shippingInsurence',
        'status',
        'employee',
        'CCardType',
        'addrAlert',
        'inBlackList',
        'rush',
        'totalNumInvoices',
        'autorun',
        'date_time',
        'score',
        'stated',
        'uptime',
    ];

    public function items()
    {
        return $this->hasMany('App\Models\InvoiceItems','invoice','invoice');
    }

}