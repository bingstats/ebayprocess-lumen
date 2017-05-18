<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/30
 * Time: 16:18
 */

namespace App\EbayApi\Component;

use App\Models\EwizCatTree;
use App\selfLog\Log;

class EbayPriceOfCat extends EbayPrice
{
    public $catsMap=array();
    public $catsTree;
    //didn't apply with ebay price rules , need re-calc price use normal price rules
    public $recalc = false;

    public function setPrice()
    {
        $_shipCost = $this->getShipCost();
        $_shipCost=$_shipCost*1.02;

        $basePrice = $this->_product['cost'];
        $price=$this->_product['price'];
        $maker=$this->_product['maker'];
        $this->catsTree = $this->getCatsTree($this->_product['catid']);
        $maker=strtolower($maker);

        $shiprate = 1.0;
        if('imicro' == $maker)
        {
            $price = $basePrice * (1.065 + 0.05);
            Log::info('cat imicro 1.065 + 0.05,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(14),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.04);
            Log::info('cat 14: 1.065 + 0.04,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(135),$this->catsTree)) && !empty($a) && $maker == 'supermicro')
        {
            $price = $basePrice * (1.065 + 0.03);
            Log::info('cat 135,supermicro: 1.065 + 0.03,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(786),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.03);
            $arr_str=array('supermicro');
            if(!in_array($maker,$arr_str)){
                $shiprate=0.5;
                Log::info('cat 786,not supermicro: 1.065 + 0.03,shiprate=0.5 ');
            }else{
                Log::info('cat 786,supermicro: 1.065 + 0.03,shiprate=1.0 ');
            }
        }
        elseif(($a = array_intersect(array(193),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.03);
            Log::info('cat 193: 1.065 + 0.03,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(186),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.03);
            Log::info('cat 186: 1.065 + 0.03,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(123),$this->catsTree)) && !empty($a))
        {
            if($maker == 'supermicro'){
                $price = $basePrice * (1.065 + 0.02);
                Log::info('cat 123,supermicro : 1.065 + 0.02,shiprate=1.0 ');
            }else{
                $shiprate=0.6;
                $price = $basePrice * (1.065 + 0.03);
                Log::info('cat 123 but supermicro : 1.065 + 0.03,shiprate=0.6 ');
            }
        }
        elseif(($a = array_intersect(array(1),$this->catsTree)) && !empty($a))
        {
            $b =array_intersect(array(5),$this->catsTree);
            $c =array_intersect(array(14),$this->catsTree);
            if(empty($b) && empty($c)){
                $price = $basePrice * (1.065 + 0.03);
                $shiprate=0.5;
                Log::info('cat 1 but not 5,14: 1.065 + 0.03,shiprate=0.5 ');
            }
        }
        elseif(($a = array_intersect(array(124),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.02);
            Log::info('cat 124 : 1.065 + 0.02,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(756),$this->catsTree)) && !empty($a) )
        {
            $shiprate=0.5;
            if($maker == 'intel'){
                $price = $basePrice * (1.065 + 0.02);
                Log::info('cat 756,intel: 1.065 + 0.02,shiprate=0.5 ');
            }else{
                $price = $basePrice * (1.065 + 0.03);
                Log::info('cat 756,not intel: 1.065 + 0.03,shiprate=0.5 ');
            }
        }
        elseif(($a = array_intersect(array(43),$this->catsTree)) && !empty($a) && $maker == 'intel')
        {
            $price = $basePrice * (1.065 + 0.02);
            Log::info('cat 43,intel: 1.065 + 0.02,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(729,724),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.02);
            Log::info('cat 729,724: 1.065 + 0.02,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(141),$this->catsTree)) || !empty($a))
        {
            $price = $basePrice * (1.065 + 0.02);
            Log::info('cat 141: 1.065 + 0.02,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(640,23,22,576),$this->catsTree)) && !empty($a) && $maker=='seagate')
        {
            $price = $basePrice * (1.065 + 0.02);
            Log::info('cat 640,23,22,576/seagate: 1.065 + 0.02,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(695,713),$this->catsTree)) && !empty($a))
        {
            $b =array_intersect(array(271),$this->catsTree);
            if(empty($b))
            {
                $price = $basePrice * (1.065 + 0.03);
                $arr_str=array('supermicro');
                if(!in_array($maker,$arr_str)){
                    //$shiprate=0.7;
                    Log::info('cat 695,713 BUT NOT 271 NOT supermicro : 1.065 + 0.03,shiprate=1.0 ');
                }else{
                    Log::info('cat 695,713 BUT NOT 271 supermicro : 1.065 + 0.03,shiprate=1.0 ');
                }
            }else{
                $arr_str=array('apc','belkin','maruson','sparkle','tripp lite');
                if(in_array($maker,$arr_str)){
                    $price = $basePrice * (1.065 + 0.02);
                    Log::info('cat 271/apc,belkin,maruson,sparkle,tripp lite : 1.065 + 0.02,shiprate=1.0 ');
                }
            }
        }
        elseif(($a = array_intersect(array(122,750,782,133,206,572,28,150),$this->catsTree)) && !empty($a))
        {
            $price = $basePrice * (1.065 + 0.03);
            Log::info('cat 122,750,782,133,206,572,28,150 : 1.065 + 0.03,shiprate=1.0 ');
        }
        elseif(($a = array_intersect(array(201),$this->catsTree)) && !empty($a))
        {
            $arr_str=array('belkin','case logic','intellinet','kensington','manhattan','syba');
            if(in_array($maker,$arr_str)){
                $price = $basePrice * (1.065 + 0.02);
                Log::info('cat 201/belkin,case logic,intellinet,kensington,manhattan,syba  : 1.065 + 0.02,shiprate=1.0 ');
            }
        }
        elseif(($a = array_intersect(array(683),$this->catsTree)) || !empty($a))
        {
            $shiprate=0.6;
            $arr_str=array('kingston','seagate','intel','supertalent');
            if(in_array($maker,$arr_str)){
                $price = $basePrice * (1.065 + 0.03);
                Log::info('cat 683/kingston,seagate,intel,supertalent : 1.065 + 0.03,shiprate=0.6 ');
            }
        }
        elseif(($a = array_intersect(array(697),$this->catsTree)) || !empty($a))
        {
            $b =array_intersect(array(782),$this->catsTree);
            $arr_str=array('asus','pioneer');
            if(empty($b) && in_array($maker,$arr_str)){
                $price = $basePrice * (1.065 + 0.02);
                Log::info('cat 697 BUT NOT 782/asus,pioneer : 1.065 + 0.02,shiprate=1.0 ');
            }
        }
        elseif(($a = array_intersect(array(67),$this->catsTree)) || !empty($a))
        {
            $shiprate=0.6;
            $price = $basePrice * (1.065 + 0.02);
            Log::info('cat 67 : 1.065 + 0.02,shiprate=0.6 ');
        }

        Log::info('shipcost is '.$_shipCost.', new shipcost is '.$_shipCost*$shiprate);
        $price=$price+$_shipCost*$shiprate;
        //when price < sox or  profit < -10,keep the item normal price
        $sox=$this->_product['sox'];
        $profit=$price - $this->_product['cost'] * 1.065 - $_shipCost;
        if($price<$this->_product['sox'] || $profit<-10){
            $this->recalc=true;
            Log::info("price=$price < sox=$sox or  profit=$profit < -10,so set it a normal price");
        }else{
            $this->_price =  sprintf("%01.2f", $price);
            Log::info("price=$price > sox=$sox and  profit=$profit > -10,so set it a special price");
        }
    }


    function getcatsTree($catid)
    {
        $cat_result = EwizCatTree::getInstance()->getEwizCateId();
        foreach ($cat_result as $key => $val){
            $ccatid=$val['catid'];
            $parentid=$val['parentid'];
            $this->catsMap[$ccatid]=$parentid;
        }
        $findAry = $tree = array();
        if(is_string($catid)) $findAry[] = $catid;
        else $findAry = $catid;
        foreach($findAry as $catid)
        {
            while($catid != '0')
            {
                $tree[] = $catid;
                if(isset($this->catsMap[$catid]))
                    $catid = $this->catsMap[$catid];
                else
                    $catid = 0;
            }
        }
        return $tree;
    }

}
