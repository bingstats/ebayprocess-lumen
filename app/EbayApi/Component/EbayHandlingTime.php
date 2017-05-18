<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 15:13
 */

namespace App\EbayApi\Component;

class EbayHandlingTime extends EbayComponent
{
    public $rules = array(
        'shippingOptions.shipType' => array(
            3 => 'USPSFirstClass',
        ),
        'primaryCategory' => array(
            2 => array('171485','3680','111422','177','132141', '27386', '1244', '170083','11210'),
            5 => array('99265', '42014'), // 42014 <==> 135
        ),
    );

    public static function create($config=array(), $className=__CLASS__)
    {
        return parent::create($config, $className);
    }

    protected function _specialHandling($item)
    {
        $time = false;

        if(isset($item['primaryCategory']))
        {
            if(in_array($item['primaryCategory'], array('170083','11210')))
                $time = 4;
        }

        return $time;
    }

    public function get($item)
    {
        $handlingTime = false;
        //var_dump($item['primaryCategory']);die; <=>99265
        // special handling
        if($time=$this->_specialHandling($item))

            return $time;

        foreach($this->rules as $key=>$values)
        {
            if(($pos=strpos($key, '.'))!==false)//15 -1
            {
                $subKey = substr($key, $pos+1);// shipType
                $key = substr($key, 0, $pos);// shippingOptions

            }

            if(isset($item[$key]))//1.array(array('shipCost'=>0,'shipType'=>'UPSGround')) 2.99265
            {
                foreach($values as $time=>$value) //array(3=>USPSFirstClass)
                {
                    if(isset($subKey) && is_array($item[$key]))
                    {
                        foreach($item[$key] as $sub)// USPSFirstClass  USPSPriority UPSGround
                        {
                            if(isset($sub[$subKey]))//UPSGround
                            {
                                if((is_array($value) && in_array($sub[$subKey], $value)) ||
                                    (is_string($value) && $value==$sub[$subKey]))
                                {
                                    $handlingTime = $time;// 3

                                    break;
                                }
                            }
                        }

                        if($handlingTime) break; // todo
                    }
                    else
                    {
                        if((is_array($value) && in_array($item[$key], $value)) ||
                            (is_string($value) && $value==$item[$key]))
                        {
                            $handlingTime = $time;
                            break;
                        }
                    }
                }
            }
        }

        return $handlingTime ? $handlingTime : 1;
    }
}