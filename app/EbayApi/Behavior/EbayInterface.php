<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/23
 * Time: 16:24
 */

namespace App\EbayApi\Behavior;


interface EbayInterface
{
    public function run($item);
}