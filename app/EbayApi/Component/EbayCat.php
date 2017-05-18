<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/30
 * Time: 15:41
 */

namespace App\EbayApi\Component;


use App\Models\EwizCatTree;
use App\selfLog\Log;

class EbayCat
{
    public $catsMap=array();
    public $oldprice;
    public $cost;
    public $sox;
    public $component;
    public $catsTree;
    public $return_cat;
    public $maker;
    public function __construct($part)
    {
        $pd_result = EwizCatTree::getInstance()->getEwizPartdetail($part);
        if($pd_result != ''){
            $this->oldprice=$pd_result['PRICE'];
            $this->cost=$pd_result['COST'];
            $this->sox=$pd_result['SOX'];
            $this->component=$pd_result['COMPONENT'];
            $this->maker=$pd_result['MAKER'];
            $this->component = trim($this->component,':');
            $this->component = explode(':',$this->component);
            $this->catsTree = $this->getcatsTree($this->component);
            $maker = strtolower($this->maker);
            if('imicro' == $maker)
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(14),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(135),$this->catsTree)) && !empty($a) && $maker == 'supermicro')
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(786),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(193),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(186),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(123),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(1),$this->catsTree)) && !empty($a))
            {
                $b =array_intersect(array(5),$this->catsTree);
                $c =array_intersect(array(14),$this->catsTree);
                if(empty($b) && empty($c)){
                    $this->return_cat='Special';
                }
            }
            elseif(($a = array_intersect(array(124),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(756),$this->catsTree)) && !empty($a) )
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(43),$this->catsTree)) && !empty($a) && $maker == 'intel')
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(729,724),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(141),$this->catsTree)) || !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(640,23,22,576),$this->catsTree)) && !empty($a) && $maker=='seagate')
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(695,713),$this->catsTree)) && !empty($a))
            {
                $b =array_intersect(array(271),$this->catsTree);
                if(empty($b))
                {
                    $this->return_cat='Special';
                }else{
                    $arr_str=array('apc','belkin','maruson','sparkle','tripp lite');
                    if(in_array($maker,$arr_str)){
                        $this->return_cat='Special';
                    }
                }
            }
            elseif(($a = array_intersect(array(122,750,782,133,206,572,28,150),$this->catsTree)) && !empty($a))
            {
                $this->return_cat='Special';
            }
            elseif(($a = array_intersect(array(201),$this->catsTree)) && !empty($a))
            {
                $arr_str=array('belkin','case logic','intellinet','kensington','manhattan','syba');
                if(in_array($maker,$arr_str)){
                    $this->return_cat='Special';
                }
            }
            elseif(($a = array_intersect(array(683),$this->catsTree)) || !empty($a))
            {
                $arr_str=array('kingston','seagate','intel','supertalent');
                if(in_array($maker,$arr_str)){
                    $this->return_cat='Special';
                }
            }
            elseif(($a = array_intersect(array(697),$this->catsTree)) || !empty($a))
            {
                $b =array_intersect(array(782),$this->catsTree);
                $arr_str=array('asus','pioneer');
                if(empty($b) && in_array($maker,$arr_str) ){
                    $this->return_cat='Special';
                }
            }else{
                $this->return_cat='Normal';
            }
        }else{
            Log::info('Can Not Find The Part In EbayCat Class');
            return false;
        }
    }
    public function getcatsTree($catid)
    {
        $cat_result = EwizCatTree::getInstance()->getEwizCateId();
        if(count($cat_result) > 0){
            foreach ($cat_result as $key => $val) {
                $ccatid = $val->catid;
                $parentid = $val->parentid;
                $this->catsMap[$ccatid] = $parentid;
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
}