<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/30
 * Time: 16:19
 */

namespace App\EbayApi\Component;

use App\selfLog\Log;
use App\Models\ProductList;

class EbayPrice extends EbayComponent
{
    private $_useMiniPrice = false;
    private $_insertFee;
    private $_paypalRate;
    private $_taxPercent;
    private $_taxState;
    private $_markup;
    private $_drawback;
    private $_minprice;
    private $_shipCost;
    private $_insurance = 0;
    private $_taxFee;
    private $_finalFee;
    private $_paypalFee;
    private $_profitFee;
    public $_product;
    public $_price;

    public $shipCostList = array();

    public static function create($config=array(), $className=__CLASS__)
    {
        return parent::create($config, $className);
    }

    public function init()
    {

        $this->setMinPrice($this->_product['minprice']);

        $objShip = EbayShipping::create(array('drawback'=>$this->_drawback));
        $shipCostList = $objShip->getShipCostList(array(
            'part' => $this->_product['part'],
            'total_price' => $this->_getMaxCost(),
        ));
        Log::info('shipCost:'.print_r($shipCostList, true));
        array_multisort($shipCostList);
        $this->_filter($shipCostList);

        $this->shipCostList = $shipCostList;

        $this->setShipCost();
        $this->setPrice();
        $this->setTaxFee();
        $this->setFinalFee();
        $this->setPaypalFee();
        $this->setProfitFee();
        return $this;
    }

    protected function _filter(&$shipCostList)
    {
        if(isset($shipCostList['USPSFirstClass'])){
            $rule = array(
                array('title' => array('logisys','cathode','light')),
                array('part' => array('SUG-IM606','CA-R407AR','FAN-CLP556','FAN-A2414','AN-OA-DB','AS-GXD1051','EV-62PCI51')),
                array('uspsFC' =>array('Disable')),
            );
            $this->_product['uspsFC'] = ProductList::getinstance()->getUspsFC($this->_product['part']);
            if(EbayFilter::create()->parseRules($rule,$this->_product)){
                array_shift($shipCostList);
            }
        }
    }

    public function getPrice()
    {
        return $this->_price;
    }

    public function setPrice()
    {
        $_itemCost = $this->_getMinCost();
        $_shipCost = $this->getShipCost();

        if(empty($_itemCost) || empty($_shipCost))
        {
            Log::info("Error: ship cost is null");
            return false;
        }

        $ratio50    = 0.9090909;
        $ratio1000  = 0.9328358;
        $ratioMore  = 0.9615384;

        $pirce1 = ($_itemCost + $this->_insertFee + $_shipCost*1.02+ 0.3)/$ratio50;
        $pirce2 = ($_itemCost + $this->_insertFee + $_shipCost*1.02+ 1.3)/$ratio1000;
        $pirce3 = ($_itemCost + $this->_insertFee + $_shipCost*1.02+ 31.3)/$ratioMore;

        $price = 0;
        $comparisonPrice1 = 50 * $ratio50;
        $comparisonPrice2 = 1000 * $ratio1000;

        if ($comparisonPrice1 >= ($pirce1 * $ratio50)) {
            // EbayLog::create()->log("select price1 ");
            $price = $pirce1;
        } else if ($comparisonPrice2 >= ($pirce2 * $ratio1000)) {
            // EbayLog::create()->log("select price2 ");
            $price = $pirce2;
        } else {
            // EbayLog::create()->log("select price3 ");
            $price = $pirce3;
        }
        // EbayLog::create()->log("Get price {$price}");

        if($price < $this->_product['sox'])
            $price = $this->_product['sox'] * 1.02;

        EbayLog::create()->log("Price to ebay store: ".$price);

        $price = sprintf("%01.2f", $price);
        if(false !== strpos($price, '.') && $price > 10)
        {
            list($ints, $decimals) = explode('.', $price);
            if($decimals>0 && $decimals<=49)
                $decimals = 0.49;
            elseif($decimals>49)
                $decimals = 0.99;

            $__price = $ints + $decimals;
            if($__price > $price)
                $price = $__price;
        }

        $this->_price = sprintf("%01.2f", $price);
        // EbayLog::create()->log("comparisonPrice1:$comparisonPrice1");
        // EbayLog::create()->log("comparisonPrice2:$comparisonPrice2");

        // EbayLog::create()->log("$pirce1 * $ratio50 = ".($pirce1 * $ratio50));
        // EbayLog::create()->log("$pirce2 * $ratio1000 = ".($pirce2 * $ratio1000));
        // EbayLog::create()->log("$pirce3 * $ratioMore = ".($pirce3 * $ratioMore));

        // EbayLog::create()->log("pirce1:-->{$pirce1}");
        // EbayLog::create()->log("pirce2:-->{$pirce2}");
        // EbayLog::create()->log("pirce3:-->{$pirce3}");

        // EbayLog::create()->log("price:-->{$this->price}");
        // EbayLog::create()->log("price Fee Object:-->");
    }

    public function getShipCost()
    {
        return $this->_shipCost;
    }

    public function setShipCost()
    {
        $ret = 0;
        foreach($this->shipCostList as $val){
            if(($ret=$val) != 0)
                break;
        }
        $this->_shipCost = $ret;
    }

    private function _getMaxCost()
    {
        $cost = max($this->_product['cost'], $this->_product['sox']);
        $cost = $cost + $cost * $this->_markup;
        return $cost;
    }

    private function _getMinCost()
    {
        $cost = min($this->_product['cost'], $this->_product['sox']);
        $cost = $cost + $cost * $this->_markup;
        return $cost;
    }

    public function setInsertFee($value)
    {
        $this->_insertFee = $value;
    }

    public function setPaypalRate($value)
    {
        $this->_paypalRate = $value;
    }

    public function setTaxPercent($value)
    {
        $this->_taxPercent = $value;
    }

    public function setTaxState($value)
    {
        $this->_taxState = $value;
    }

    public function setMarkup($value)
    {
        $this->_markup = $value;
    }

    public function setDrawback($value)
    {
        $this->_drawback = $value;
    }

    public function setMinprice($value)
    {
        $this->_minprice = $value;
    }

    public function setProduct($value)
    {
        $this->_product = $value;
    }

    public function getInsurance()
    {
        return $this->_insurance;
    }

    public function setInsurance($value)
    {
        $this->_insurance = $value;
    }

    public function getTaxFee()
    {
        return $this->_taxFee;
    }

    public function setTaxFee($value=null)
    {
        if($value)
            $this->_taxFee = $value;
        else
            $this->_taxFee = sprintf("%01.2f", $this->_price * $this->_taxPercent);
    }

    public function getFinalFee()
    {
        return $this->_finalFee;
    }

    public function setFinalFee($value=null)
    {
        if($value)
            $this->_finalFee = $value;
        else
        {
            if($this->_price <= 50)
                $finalFee = 0.12 * $this->_price;
            elseif($this->_price <= 1000)
                $finalFee = (50*0.12) + ($this->_price-50) * 0.06;
            else
                $finalFee = (50*0.12) + (950 * 0.06) + ($this->_price-1000) * 0.02;

            $this->_finalFee = sprintf("%01.2f", $finalFee);
        }
    }

    public function getPaypalFee()
    {
        return $this->_paypalFee;
    }

    public function setPaypalFee($value=null)
    {
        if($value)
            $this->_paypalFee = $value;
        else
        {
            $total = $this->_price + $this->_shipCost + $this->_taxFee;
            $this->_paypalFee = sprintf("%01.2f", ($total*$this->_paypalRate+0.03));
        }
    }

    public function getProfitFee()
    {
        return $this->_profitFee;
    }

    public function setProfitFee($value=null)
    {
        if($value)
            $this->_profitFee = $value;
        else
        {
            $__cost = $this->_getMinCost();
            $profit =  $__cost * $this->_markup;

            if($this->_useMiniPrice)
            {
                $profit = $this->_price - ($__cost + $this->_insertFee + $this->_paypalFee + $this->_finalFee);
            }
            $this->_profitFee = sprintf("%01.2f", $profit);
        }
    }
}