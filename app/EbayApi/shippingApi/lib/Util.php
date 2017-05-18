<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/31
 * Time: 10:17
 */

namespace App\EbayApi\ShippingApi\Lib;


class Util
{
    /**
     * @usage: convert string to url params
     * @param null|array $data
     * @return bool|string
     */
    public static function arrayToURLString($data)
    {
        if(empty($data) || !is_array($data)) return false;
        $return = '';
        $need_amp = false;
        foreach ($data as $varname => $val) {
            if ($need_amp) $return .= '&';
            $val = urlencode($val);
            $return .= "{$varname}={$val}";
            $need_amp = true;
        }
        return $return;
    }

    /**
     * @usage: convert object to a array
     * @param object $stdData
     * @return array
     */
    public static function stdToArray($stdData)
    {
        if(!$stdData instanceof \stdClass) return $stdData;
        $return = array();
        $keys = get_object_vars($stdData);
        foreach ($keys as $key => $value) {
            $return[$key] = self::stdToArray($value);
        }
        return $return;
    }
}