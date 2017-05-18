<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/10
 * Time: 15:10
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
class InvoiceStatus extends Eloquent
{
    protected $table = 'invoice_status';
    protected $primaryKey = 'invoice';
    public $timestamps = false;
    protected $fillable = [
        'invoice',
        'status',
        'note',
        'name',
        'timestamp',
    ];
}