<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/23
 * Time: 16:22
 */

namespace App\EbayApi\Behavior;

use App\EbayApi\Component\EbayFilter;

class EbayBehavior
{
    public $store;
    public function __construct($obj)
    {
        $this->store = $obj;
    }


    public function filter($data, $rules=array())
    {
        if(empty($rules)){

            $rules = $this->rules;
        }

        return !EbayFilter::create()->parseRules($rules, $data);
    }

    public function flexibleFilter($data)
    {
        return EbayFilter::create()->flexibleFilter($data);
    }
}