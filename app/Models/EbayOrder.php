<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/3
 * Time: 16:31
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EbayOrder extends  Eloquent
{
    protected $table = 'ebayOrder';
    protected $primaryKey = 'orderid';
    public $timestamps = false;
    protected $fillable = [
        'verify',
    ];

    public function orderItems()
    {
        return $this->hasMany('EbayOrderItems','orderid','orderid');
    }

    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('txn_id, first_name, last_name, mc_fee, insurance_amount, mc_shipping, shipping_method, tax, mc_handling, mc_gross, discount, shipping_discount, num_cart_items, payer_email, payer_id, auction_buyer_id, payer_status, address_country, address_city, address_country_code, address_name, address_state, address_street, address_zip, residence_country, payment_type, payment_status, payment_date, ipn_track_id, for_auction, mc_currency, memo, mdate, verify', 'required'),
            array('num_cart_items, isGSP', 'numerical', 'integerOnly'=>true),
            array('txn_id, shipping_method, payer_status, parent_txn_id, payment_status', 'length', 'max'=>30),
            array('first_name, last_name, auction_buyer_id, address_country', 'length', 'max'=>64),
            array('mc_fee, insurance_amount, mc_shipping, tax, mc_handling, mc_gross, discount, shipping_discount', 'length', 'max'=>9),
            array('payer_email', 'length', 'max'=>127),
            array('payer_id', 'length', 'max'=>13),
            array('address_city, address_state', 'length', 'max'=>40),
            array('address_country_code, residence_country', 'length', 'max'=>2),
            array('address_name', 'length', 'max'=>128),
            array('address_status', 'length', 'max'=>11),
            array('address_street', 'length', 'max'=>200),
            array('address_zip', 'length', 'max'=>20),
            array('payment_type, for_auction', 'length', 'max'=>15),
            array('ipn_track_id, memo', 'length', 'max'=>255),
            array('mc_currency', 'length', 'max'=>10),
            array('verify', 'length', 'max'=>3),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            //array('orderid, txn_id, first_name, last_name, mc_fee, insurance_amount, mc_shipping, shipping_method, tax, mc_handling, mc_gross, discount, shipping_discount, num_cart_items, payer_email, payer_id, auction_buyer_id, payer_status, parent_txn_id, address_country, address_city, address_country_code, address_name, address_state, address_status, address_street, address_zip, residence_country, payment_type, payment_status, payment_date, ipn_track_id, for_auction, mc_currency, memo, mdate, isGSP, verify', 'safe', 'on'=>'search'),
        );
    }

}