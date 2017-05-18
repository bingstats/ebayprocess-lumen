<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/11
 * Time: 15:24
 */
namespace App\EbayApi\Stores;

class Mp3superstore extends EbayStore
{
    public function __construct($firstly = false)
    {
        $storeName = 'mp3superstore';
        parent::__construct($storeName, $firstly);
    }
}