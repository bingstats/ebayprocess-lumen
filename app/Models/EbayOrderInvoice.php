<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/7
 * Time: 14:37
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EbayOrderInvoice extends Eloquent
{
    protected $table = 'ebayOrder_invoice';
    protected $primaryKey = 'paypalTransactionId,ebay_txn_id';
    public $timestamps = false;
    protected $fillable = [
        'paypalTransactionId',
        'ebay_txn_id',
        'invoice',
        'isSendTrack2Buyer',
        'shipMethod',
    ];

    public function rules()
    {
        return array(
            array('isSendTrack2Buyer', 'numerical', 'integerOnly'=>true),
            array('paypalTransactionId, ebay_txn_id, shipMethod', 'length', 'max'=>30),
            array('invoice', 'length', 'max'=>8),
        );
    }

}