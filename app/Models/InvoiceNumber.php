<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/9
 * Time: 14:25
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class InvoiceNumber extends  Eloquent
{

    protected $table = 'invoice_number';
    protected $primaryKey = 'invoice_num';
    public $timestamps = false;

    protected $fillable = [
        'invoice_num',
    ];

}