<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/10
 * Time: 9:24
 */
namespace App\EbayApi\Component;

use DB;

class RecycleFee
{
    protected $RecycleFeeType = array(
        //'4-15'=>8,
        //'=15-35'=>16,
        //'=35'=>25
        '4-15' => 3,
        '=15-35' => 4,
        '=35' => 5
    );

    protected $totalQty = 0;

    //initialize Product catagories

    private $categories = array(
        '184', '185', '577', '57', '686', '679'
    );

    //initialize state

    private $state = array("CA");

    function getRecycleFee($invoice)
    {

//        $sql="SELECT i.qty as quantity,s.SState as state,m.* FROM `invoice_items` AS i
//            LEFT JOIN `master_product_list` AS m ON i.part = m.part
//            LEFT JOIN `invoice_bill_ship` AS s ON i.invoice = s.invoice where i.invoice='$invoice'";

            $cate = $this->categories;
            $res = DB::table('invoice_items as i')
                ->where(DB::raw('i.invoice'), '=', $invoice)
                ->where(function ($query) use ($cate) {
                    foreach ($cate as $v) {
                        $query->orWhere(DB::raw('m.Component'), 'like', "%$v%");
                    }
                })
                ->leftJoin('master_product_list as m', DB::raw('i.part'), '=', DB::raw('m.part'))
                ->leftJoin('invoice_bill_ship as s', DB::raw('i.invoice'), '=', DB::raw('s.invoice'))
                ->select(DB::raw('i.qty as quantity'), DB::raw('s.SState as state'), DB::raw('m.*'))->get();
          $find = array();
          foreach($res as $rv){
             $find[] = (array) $rv;
          }
//        if($this->categories){
//            foreach($this->categories as $cate){
//                $sqlor[]="m.Component LIKE '%:".$cate.":%'";
//            }
//            $sqlarg="(".implode(' or ',$sqlor).")";
//            $sql.=" and ".$sqlarg;
//        }

        $fee = 0;

        $sizeFields = array(
            'para1' => array('184', '185', '679')
        );
        //echo $sql;

        //$find       = InvoiceItems::model()->findAllBySql($sql);
        //$find       = Yii::app()->db->createCommand()->setText($sql)->queryAll();
        //var_dump($find);
        $comment = array();

        foreach ($find as $fv) {
            $result = $fv;
            $result = array_change_key_case($result, CASE_LOWER);
            //print_r($result);
            if (in_array(strtoupper($result['state']), $this->state)) {
                //get size
                //$size=$this->getSize($result['Component'],$result);
                $component = $result['Component'];
                //var size field
                $size = 0;
                $qty = $result['quantity'];
                $this->totalQty += $qty;
                foreach (explode(':', $component) as $c) {
                    if ($c) {
                        $components[] = $c;
                    }
                }
                if ($components) {
                    foreach ($components as $co) {
                        foreach ($sizeFields as $key => $sizeField) {
                            if (in_array($co, $sizeField)) {
                                $size = floatval($result[$key]);
                            }
                        }
                    }
                }
                if (!$size) {
                    $size = floatval($result['para0']);
                }
                //calculate fee
                foreach ($this->RecycleFeeType as $Feerange => $Fee) {
                    $fr = array();
                    $fr = explode('-', $Feerange);
                    if (isset($fr[0]) && $fr[0] != '') {
                        $reangesize = str_replace('=', '', $fr[0]);
                        if ((strpos($fr[0], '=') === false && $size <= $reangesize) || (strpos($fr[0], '=') !== false && $size < $reangesize)) {
                            continue;
                        }
                    }
                    if (isset($fr[1]) && $fr[1] != '') {
                        $reangesize = str_replace('=', '', $fr[1]);
                        if ((strpos($fr[1], '=') === false && $size >= $reangesize) || (strpos($fr[1], '=') !== false && $size > $reangesize)) {
                            continue;
                        }
                    }
                    $fee += $qty * $Fee;
                }


                //$fee+=$result['quantity']*$this->getRecycleFee($size);

                $comment[] = array(
                    'part'=>$result['part'],
                    'quantity'=>$result['quantity'],
                    'state'=>$result['state'],
                    'Component'=>$result['Component']
                );
            }
        }
        $recycling_fee = array('fee' => $fee, 'totalQty' => $this->totalQty, 'comment' => serialize($comment));
        return $recycling_fee;
    }
}
?>
