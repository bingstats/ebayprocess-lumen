<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/18
 * Time: 10:13
 */

namespace App\EbayApi;


class ebayUpdate
{
    public function index($storeName,$firstly=true)
    {
        $storeName = ucfirst($storeName);
        if($firstly !== false){
            $store = new $storeName(true);
        }else{
            $store = new $storeName(false);
        }
        $store->run();
    }
}