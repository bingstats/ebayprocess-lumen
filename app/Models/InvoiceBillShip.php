<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/10
 * Time: 9:01
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class InvoiceBillShip extends Eloquent
{
    protected $table = 'invoice_bill_ship';
    protected $primaryKey = 'invoice';
    public $timestamps = false;
    protected $fillable = [
        'invoice',
        'BFName',
        'BLName',
        'BAddrOne',
        'BAddrTwo',
        'BCity',
        'BState',
        'BZip',
        'BCountry',
        'BPhone',
        'SFName',
        'SLName',
        'SAddrOne',
        'SAddrTwo',
        'SCity',
        'SState',
        'SZip',
        'SCountry',
        'SPhone',
        'sBusiName',
    ];
}