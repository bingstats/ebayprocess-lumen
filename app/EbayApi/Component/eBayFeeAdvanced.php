<?php
namespace App\EbayApi\Component;

use App\Models\EbayItemsDesc;
use App\Models\EbayOrderInvoice;
use App\selfLog\Log;

class eBayFeeAdvanced{
	static public $insertion = 0.01;
    static public $rate = 0.04;
    static public $rate6 = 0.06;
    static public $rate9 = 0.09;
    static public $rate7 = 0.07;
    static public $max = 250;
    static public $paypalRate = 0.029;
    static public $paypalFit = 0.3;
    static public $rate_six_category = array('15200','9394');
    static public $rate_nine_category = array('31491','3676','31530','162','86722','48446','15052','32852','3270','187','171833','54968','139973','176970');

	static public function getFee($invoice)
	{
        $finalFee = 0;
        $items = self::getItems($invoice);
        if(!empty($items)){
            foreach($items as $item){
                $rs = self::getFeeWithItem($item);
                $finalFee += $rs['finalFee'];
            }
        }
        return $finalFee;

	}
	static public function getFeeWithItem($item){
        $ebayItemsDesc = new EbayItemsDesc();
        $category = $ebayItemsDesc->getEbayCateId($item['item_number']);
        if($category != ''){
            $rate = self::choiceRate($category);
            $amount = $item['mc_gross'];
            $rs = array();
            if(!empty($amount)){
                $fee = $amount * $rate + self::$insertion;
                $rs['eBayFee'] = $fee > self::$max ? self::$max : $fee;
                $rs['paypalFee'] = $amount * self::$paypalRate + self::$paypalFit;
                $rs['finalFee'] = $rs['eBayFee'] + $rs['paypalFee'];
            }
            return $rs;
        }else{
            //Log::error('Not Found the Item in ebay_items_desc');
            return false;
        }

    }
	static public function choiceRate($category){
        $rate = self::$rate;
        if(in_array($category , self::$rate_six_category)){
            $rate = self::$rate6;
        }else if(in_array($category , self::$rate_nine_category)){
            $rate = self::$rate9;
        }
        return $rate;
    }
	static public function getItems($invoice){
        $rs = array();
        $res = EbayOrderInvoice::where('invoice',$invoice)->select('paypalTransactionId')->get();
        foreach($res as $rv){
            $txn = $rv ->paypalTransactionId;
        }
        if(!empty($txn)){
             $result = DB::table('ebayOrderItems as a')
                 ->where(DB::raw('b.txn_id'), '=', $txn)
                 ->join('ebayOrder as b',DB::raw('a.invoice'),'=',DB::raw('b.invoice'))
                 ->select(DB::raw('a.mc_gross'),DB::raw('a.part'),DB::raw('a.item_number'))
                 ->get();
            foreach($result as $rsv){
                $rs = (array) $rsv;
            }
        }

        return $rs;
    }
	static public function getFeeWithItemAndCategory($item,$category){
        $rate = self::choiceRate($category);
        //$query = "select total from invoice_list where invoice='$invoice'";
        $amount = $item['mc_gross'];
        //echo $amount.' '.$rate."\n"; 
        $rs = array();
        if(!empty($amount)){
            $fee = $amount * $rate + self::$insertion;
            $rs['eBayFee'] = $fee > self::$max ? self::$max : $fee;
            $rs['paypalFee'] = $amount * self::$paypalRate + self::$paypalFit;
            $rs['finalFee'] = $rs['eBayFee'] + $rs['paypalFee'];
            //echo $item['part']."\n";
            //print_r($rs);
            //echo "\n";
        }
        return $rs;
    }
	static public function getFeeWithAmount($amount){
        $rs = 0;
        if(!empty($amount)){
            $fee = $amount * self::$rate + self::$insertion;
            $eBayFee = $fee > self::$max ? self::$max : $fee;
            $paypalFee = $amount * self::$paypalRate + self::$paypalFit;
            $rs = $eBayFee + $paypalFee;
        }
        return $rs;
    }

}