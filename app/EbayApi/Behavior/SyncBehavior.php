<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 15:44
 */

namespace App\EbayApi\Behavior;


use App\EbayApi\EbayItem;

class SyncBehavior extends EbayBehavior implements EbayInterface
{
    public function run($itemID)
    {
        $ebayItem = new EbayItem($this->store,$itemID);
        $status = $ebayItem->syncToLocal();
        if($status !== false){
            $ebayItem->updateQuantity();
        }
        return $status;
    }
}