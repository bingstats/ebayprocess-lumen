<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/31
 * Time: 10:10
 */

namespace App\EbayApi\ShippingApi;


use App\EbayApi\ShippingApi\Lib\RestClient;
use App\EbayApi\ShippingApi\Lib\Util;

class ShippingRate
{
    const HOST='http://chain.com/shipping/channel';
    protected $_data = array();
    protected $_return = null;
    protected $_errorMsg = '';
    protected $_hasError = false;

    public function __construct($data){
        $this->_data = $data;
        $this->_return = $this->_getShippingRate($data);
    }

    /**
     * @param $code
     * @return bool|float
     */
    public function getRateByCode($code){
        if($this->hasError())return false;
        if(($data = $this->_getData()) !== false){
            return isset($data[$code])?round(floatval($data[$code]['cost']),2):false;
        }
        return false;
    }

    /**
     * @param $type
     * @return bool|float
     */
    public function getRateByType($type){
        if($this->hasError())return false;
        if(($data = $this->_getData()) !== false){
            foreach($data as $key=>$value){
                if($value['name'] == $type)return round(floatval($value['cost']),2);
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getRate(){
        $rates = $this->getRates();
        if($rates !== false){
            $rate = array_shift($rates);
            return $rate['cost'];
        }
        return false;

    }

    /**
     * @return bool
     */
    public function getRates(){
        if($this->hasError())return false;

        if(($data = $this->_getData()) !== false){
            return $data;
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function _getData(){
        if(isset($this->_return['data']))return $this->_return['data'];
        return false;
    }
    public function getErrors(){
        return $this->_errorMsg;
    }

    /**
     * @param $parts
     * @return bool|string
     */
    protected function _handlePartsInput($parts){
        if(!is_array($parts))return $parts;
        $return = '';
        foreach($parts as $part=>$num){
            $return .=$part.':'.$num.'|';
        }
        return substr($return,0,-1);
    }

    /**
     * @param $data
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    protected function _getShippingRate($data){
        if(is_array($data)){
            if(isset($data['part']))$data['part'] = $this->_handlePartsInput($data['part']);
            if($result = Util::arrayToURLString($data))$data = $result;
        }else if(!is_string($data)){
            throw new \Exception('format invalid');
        }
        $url = self::HOST.'?'.$data;
        $rc = new RestClient();
        $rc->getWebRequest($url);
        $return = $rc->getWebResponse();
        $return = json_decode($return);
        $return = Util::stdToArray($return);

        switch($rc->getStatusCode()){
            case 200:

                if($return['is_success']){
                    return $return;
                }else{
                    $this->_setErrorMsg($return);
                    return false;
                }
                break;
            default:
                $this->_setErrorMsg($return);
                return false;
                break;
        }
    }
    protected function _setErrorMsg($return){
        $this->_hasError = true;
        if(isset($return['error_msg']) && $return['error_msg']){
            $this->_errorMsg .= $return['error_msg'];
        }
    }
    public function hasError(){
        return $this->_hasError;
    }
}
