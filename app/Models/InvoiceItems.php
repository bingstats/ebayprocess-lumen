<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/9
 * Time: 15:54
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class InvoiceItems extends Eloquent
{
    protected $table = 'invoice_items';
    protected $primaryKey = 'item_id';
    public $timestamps = false;
    protected $fillable = [
        'itemid',
        'invoice',
        'channel',
        'part',
        'type',
        'qty',
        'price',
        'cost',
        'itemName',
        'weight',
        'orderDateTime',
        'note',
        'status',
        'packNum',
        'history',
        'ebayItemNum',
        'employee',
        'po',
        'dropship',
        'vendor',
        'balance',
        'onlineCost',
        'ignored',
        'timestamp',
        'shipfrom',
        'manuallyShip',
        'act_type',
    ];
    public function invList()
    {
        return $this->belongsTo('App\Models\InvoiceList');
    }
    public function getTableColumns() {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

}