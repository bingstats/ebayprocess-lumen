<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/24
 * Time: 10:01
 */

namespace App\EbayApi\Behavior;


use App\EbayApi\EbayItem;

class EndBehavior extends EbayBehavior implements EbayInterface
{
    /**usage: start to end the item online
     * @param $itemID
     */
    public function run($itemID)
    {
        $ebayItem = new EbayItem($this->store,$itemID);
        $ebayItem->endItem();
    }

    /**
     * @param $itemID
     * @return bool
     */
    public function endItemInLocal($itemID)
    {
        $ebayItem = new EbayItem($this->store, $itemID);
        return $ebayItem->endItemInLocal();
    }
}