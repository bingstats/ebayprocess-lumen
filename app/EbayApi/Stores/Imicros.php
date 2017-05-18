<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/4
 * Time: 13:47
 */

namespace App\EbayApi\Stores;


class Imicros extends EbayStore
{
    public function __construct($firstly=false)
    {
        $storeName = 'imicros';
        parent::__construct($storeName,$firstly);
    }

}