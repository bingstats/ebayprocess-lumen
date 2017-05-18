<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/24
 * Time: 8:13
 */

namespace App\EbayApi\Component;

class EbayFilter extends EbayComponent
{
    public static function create($config = array(), $className = __CLASS__)
    {
        return parent::create($config, $className); // TODO: instance of the object
    }

    /**
     * @param array $regular
     * @param array $data
     * @return bool
     */
    public function parseRules($regular=array(), $data=array())
    {
        if(!is_array($regular))
            return false;
        foreach ($regular as $filter) {
            if(is_array($filter)){
                $ret = array();
                if(count($filter) == 1){
                    foreach($filter as $k => $v){
                        if(strpos($k,'|') !== false){
                            list($k,$type) = explode('|',$k);
                            if(isset($data[$k])){
                                $ret[] = $this->_getFilterType($type,$v,$data[$k]);
                                continue;
                            }
                        }
                        if(isset($data[$k])){
                            if(is_array($v)){
                                $ret[] = $this->_getFilterType('in', $v, $data[$k]);
                            }else{
                                $ret[] = $this->_getFilterType('nequal', $v, $data[$k]);
                            }
                        }else{
                            $ret[] = false;
                        }
                    }
                }else{
                    $nret = array();
                    foreach($filter as $k=>$v){
                        if(strpos($k, '|') !== false){
                            list($k, $type) = explode('|', $k);
                            if(isset($data[$k])){
                                $nret[] = $this->_getFilterType($type, $v, $data[$k]);
                                continue;
                            }
                        }
                        if(isset($data[$k])){
                            if(is_array($v)){
                                $nret[] = $this->_getFilterType('in', $v, $data[$k]);
                            }else{
                                $nret[] = $this->_getFilterType('nequal', $v, $data[$k]);
                            }
                        }else{
                            $nret[] = false;
                        }
                    }
                    if(!empty($nret) && !in_array(false,$nret)){
                        $ret[] = true;
                    }else{
                        $ret[] = false;
                    }
                }
                if(!empty($ret) && in_array(true,$ret)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $type
     * @param $var filter value
     * @param $field validate value
     * @return bool
     */
    private function _getFilterType($type, $var, $field)
    {
        switch($type){
            case 'than':
                $ret = ($field > $var) ? true : false;
                break;
            case 'lthan':
                $ret = ($field < $var) ? true : false;
                break;
            case 'nequal':
                $ret = ($field == $var) ? true : false;
                break;
            case 'noequal':
                $ret = ($field != $var) ? true : false;
                break;
            case 'sthan':
                $ret = ($field <= $var) ? true : false;
                break;
            case 'bthan':
                $ret = ($field >= $var) ? true : false;
                break;
            case 'has':
                $ret = is_string($var) ? ((strpos($var, $field) !== false) ? true : false) : false;
                break;
            case 'nohas':
                $ret = is_string($var) ? ((strpos($var, $field) === false) ? true : false) : false;
                break;
            case 'between':
                $ret = (($field >= $var[0]) && ($field <= $var[1])) ? true : false;
                break;
            case 'in':
                $ret = (in_array($field, (array)$var)) ? true : false;
                break;
            case 'notin':
                $ret = (!in_array($field, (array)$var)) ? true : false;
                break;
            case 'regex':
                $ret = preg_match($var, $field) ? true : false;
                break;
            default:
                $ret = ($field == $var) ? true : false;
                break;

        }
        return $ret;
    }

    /**
     * @param $data
     * @param string $project
     * @return bool
     */
    public function flexibleFilter($data, $project='ebayupdate')
    {
        $data = array_change_key_case($data,CASE_LOWER);
        $rs = app('db')->connection('mysql2')->select('select * from ebay_filter WHERE program =;program',[':program'=>$project]);
        if(count($rs) > 0){
            foreach($rs as $k => $v){
                $type = strtolower($v->type);
                $value = trim($v->value);
                $match = isset($data[$type]) ? $data[$type] : '';
                if($type == 'title' && !empty($match)){
                    if(preg_match("/$value/i",$match)) return false;
                }
                if(isset($data[$type]) && $match == $value) return false;
            }
        }
        return true;

    }


}