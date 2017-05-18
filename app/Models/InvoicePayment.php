<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/10
 * Time: 8:48
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class InvoicePayment extends Eloquent
{
    protected $table = 'invoice_payment';
    protected $primaryKey = 'invoice';
    public $timestamps = false;
    protected $fillable = [
        'invoice',
        'CCardType',
        'PPEmail',
    ];
}