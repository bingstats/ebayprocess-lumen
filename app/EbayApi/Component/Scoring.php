<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/11
 * Time: 8:11
 */

namespace App\EbayApi\Component;

use DB;

class Scoring
{
    const qty_above_1 = 50;// when the order contain any item with quantity greater than 1 or the order total is over $900 or the email not in the array $emailDomain
    const qty_above_2 = 23;//add 23 more points if the order contains any item with quantity greater than 2
    const qty_above_3 = 1;//add 1 point if the order has 3-day, 2-day, overnight saver,or overnight shipping; or if the order has rush shipping
    const qty_above_4 = 15;// the product category in array $catArr
    const qty_above_5 = 25;

    public function dojob($inv)
    {
       $itemRes = DB::table('invoice_list as l')
            ->where(DB::raw('l.invoice'),'=',$inv)
            ->leftJoin('invoice_items as i',DB::raw('i.invoice'),'=',DB::raw('l.invoice'))
            ->leftJoin('master_product_list as p',DB::raw('i.part'),'=',DB::raw('p.part'))
            ->select(DB::raw('l.total'),DB::raw('l.shippingtype'),DB::raw('l.email'),DB::raw('i.qty'),DB::raw('i.part'),DB::raw('p.component'))
            ->get();
        $itemResult = array();
        foreach($itemRes as $itemv){
            $itemResult[] = (array) $itemv;
        }
        $totalScore = $this->getScoreByQtyTotalShipping($itemResult,$inv);
        return $totalScore;
    }
    /**
     * get scroe by item quantity,total and shippingtype
     *
     * add 50 points if the order contains any item with quantity greater than 1, add 23 more points if the order contains any item with quantity greater than 2
     *
     * @param string invoice
     *
     * @return integer
     */
    function getScoreByQtyTotalShipping($itemRes,$inv){
        //get score by item quantity and product category
        $score = 0;
        $iQty = 0;
        $iiQty = 0;
        $score = $this->getScoreByEmailDomain($itemRes);
        $productCatStr = ':';
        $productCats = array(
            '14'   => $this->getCatChildrenstr('14'),
            '11'   => $this->getCatChildrenstr('11'),
            '724'  => $this->getCatChildrenstr('724'),
            '344'  => $this->getCatChildrenstr('344'),
            '240'  => $this->getCatChildrenstr('240'),
            '223'  => $this->getCatChildrenstr('223'),
        );
        foreach($productCats as $productCatV){
            $productCatStr .= $productCatV;
        }
        // var_dump($productCatStr);
        //var_dump($itemRes);
        foreach($itemRes as $itemk=>$itemv){
            $qty=$itemv['qty'];
            //self::log("itemv qty--->$qty");
            if($qty>1) $iQty+=1;
            if($qty>2) $iiQty+=1;//get score by product number
            if($itemv['component']){
                $catArr = array_filter(explode(':',$itemv['component']));
                foreach($catArr as $productCat){
                    if(strpos($productCatStr,":".trim($productCat).":")!==false){
                        $score+=self::qty_above_4;
                        //Log::info("score----->$score; productCat--->$productCat");
                        break;
                    }
                }
            }
            /*
            if(strpos($productCatStr,":$itemv->catid:")!==false){
                $score+= self::qty_above_4;//get score by product category
                self::log("score----->$score");
            }*/

        }
        if($iQty>0) $score+=self::qty_above_1;
        //self::log("score----->$score; iqty-->$iQty");
        if($iiQty>0) $score+=self::qty_above_2;
        //self::log("score----->$score; iiqty-->$iiQty");
        //get score by ship type
        $ship_type = array(
            'FedEx Standard Overnight'               =>'FDSOS',
            'FedEx Priority Overnight'               =>'FDPOS',
            'FedEx 3Day Freight'                     =>'FD3DF',
            'FedEx First Overnight'                  =>'FDFOS',
            'FedEx 2Day Freight'                     =>'FD2DF',
            'FedEx 2Day'                             =>'FD2DS',
            'UPS Next Day Air'                       =>'UP1DA',
            'UPS Next Day Air Saver'                 =>'UP1DS',
            'UPS 2nd Day Air'                        =>'UP2DA',
            'UPS 3 Day Select'                       =>'UP3DS',
            'USPS Express Next Day PO to PO'         =>'PS1PS',
            'USPS Express Next Day PO to Addresses'  =>'PS1AS'
        );
        //if(array_key_exists($itemRes[0]->shippingtype,$ship_type)) $score+=self::qty_above_3;
        if(array_key_exists($itemv['shippingtype'],$ship_type)) $score+=self::qty_above_3;
       // self::log("score----->$score");
        //$rushShipSql = "select * from invoice_special where type='RSH' and invoice='$inv'";
        //$rushShipRes = InvoiceSpecial::model()->findBySql($rushShipSql);
        $rushShipRes =DB::table('invoice_special')->where([['type','RSH'],['invoice',$inv]])->get();
        if(count($rushShipRes) > 0) $score+=self::qty_above_3;
       // self::log("score----->$score");
        //get score by quantity
        //if($itemRes[0]['total']>900) $score += self::qty_above_1;
        if($itemv['total']>900) $score += self::qty_above_1;
        if($itemv['total']<100) $score>self::qty_above_5?$score -= self::qty_above_5:$score=0;
        //self::log("score----->$score;");
        return $score;
    }

    function getScoreByEmailDomain($itemRes){
        $score = 0;
        $domain=array('yahoo.com',
            'gmail.com',
            'hotmail.com',
            'comcast.net',
            'aol.com',
            'msn.com',
            'sbcglobal.net',
            'verizon.net',
            'cox.net',
            'bellsouth.net',
            'charter.net',
            'earthlink.net',
            'live.com',
            'att.net',
            'optonline.net',
            'juno.com',
            'mac.com',
            'adelphia.net',
            'netzero.net',
            'mchsi.com',
            'netscape.net',
            'excite.com',
            'roadrunner.com',
            'pacbell.net',
            'embarqmail.com',
            'cfl.rr.com',
            'netzero.com',
            'tampabay.rr.com',
            'aim.com',
            'insightbb.com',
            'ymail.com',
            'mindspring.com',
            'windstream.net',
            'mail.com',
            'cableone.net',
            'centurytel.net',
            'frontiernet.net',
            'wi.rr.com',
            'rocketmail.com',
            'me.com',
            'suddenlink.net',
            'swbell.net',
            'ameritech.net',
            'nc.rr.com',
            'hughes.net',
            'email.com',
            'rochester.rr.com',
            'q.com',
            'peoplepc.com',
            'lycos.com',
            'usa.net',
            'mail.ru',
            'carolina.rr.com',
            'tds.net',
            'austin.rr.com',
            'twcny.rr.com',
            'prodigy.net',
            'wowway.com',
            'ptd.net',
            'nycap.rr.com',
            'woh.rr.com',
            'rcn.com',
            'nyc.rr.com',
            'satx.rr.com',
            'neo.rr.com',
            'kc.rr.com',
            'alltel.net',
            'triad.rr.com',
            'columbus.rr.com',
            'knology.net',
            'zoominternet.net',
            'cs.com',
            'hawaii.rr.com',
            'frontier.com',
            'usa.com',
            'snet.net',
            'tx.rr.com',
            'cinci.rr.com',
            'inbox.com',
            'stny.rr.com',
            'bigfoot.com',
            'qwest.net',
            'pobox.com',
            'bresnan.net',
            'sc.rr.com',
            'fuse.net',
            'chartermi.net',
            'wildblue.net',
            'ix.netcom.com',
            'san.rr.com',
            'socal.rr.com',
            'myway.com',
            'dslextreme.com',
            'yahoo.es',
            'houston.rr.com',
            'ca.rr.com',
            'fastmail.fm',
            'gmx.com',
            'naver.com',
            'clearwire.net',
            'hanmail.net',
            'wideopenwest.com',
            'netscape.com',
            'yahoo.com.mx',
            'yahoo.co.in',
            'yahoo.fr',
            'yahoo.co.uk',
            'iname.com',
            'new.rr.com',
            'atlanticbb.net',
            'gmx.net',
            'citlink.net',
            'worldnet.att.net',
            'sonic.net',
            'shaw.ca',
            'yahoo.com.cn',
            'localnet.com',
            'speakeasy.net',
            'ec.rr.com',
            'hvc.rr.com',
            'gte.net',
            'direcway.com',
            'iwon.com',
            'softhome.net',
            'yahoo.com.br',
            'consolidated.net',
            'myfairpoint.net',
            'wmconnect.com',
            'gci.net',
            'lmco.com',
            'attglobal.net',
            'surewest.net',
            'yahoo.ca',
            'myrealbox.com',
            'wavecable.com',
            'yandex.ru',
            'yahoo.com.tw',
            'bak.rr.com',
            'spamgourmet.com',
            'prtc.net',
            'sneakemail.com',
            'maine.rr.com',
            '163.com',
            'starband.net',
            'erols.com',
            'elp.rr.com',
            'copper.net',
            'optimum.net',
            'centurylink.net');
        $emailDomain = substr(strrchr($itemRes[0]['email'],'@'),1);
        if(!in_array($emailDomain,$domain)){
            //var_dump($emailDomain);
            $score += self::qty_above_1;
        }
        //self::log("score----->$score; emailDomain-->$emailDomain");
        return $score;
    }
    public function getCatChildrenStr($catid) {
        //echo 'catid = '.$catid."\n";
        $rtn="";
        //$sql="select catid from prod_category where ids like '%:$catid:%'";
        //$res=ProdCategory::model()->findBySql($sql);
        $res = DB::table('prod_category')->where('ids','like','%$catid%')->select('catid')->get();
        //echo "\n".count($res)."\n";
        foreach($res as $catId){
            //var_dump($catId);
            $rtn.=$catId->catid.":";
        }
        return $rtn;
    }
    public static function log($str){
        //echo $str,"\n";
    }

}