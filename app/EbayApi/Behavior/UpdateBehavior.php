<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/30
 * Time: 14:18
 */

namespace App\EbayApi\Behavior;


use App\EbayApi\EbayItem;
use App\selfLog\Log;

class UpdateBehavior extends EbayBehavior implements EbayInterface
{
    public $rules = array(
        array('quantity|sthan' => 0, 'arrival' => array(-99,-10)),
        array('quantity' => 999, 'arrival' => -99),
        array('arrival' => -33),
        array('title|regex' => '/plantronics/i'),
    );

    /**
     * @param object $item
     */
    public function run($item)
    {
        // TODO: Implement run() method.
        $ebayItem = new EbayItem($this->store,$item->itemid);
        $ebayItem->part = trim($item->part);
        $resp = $ebayItem->getItem();
        if($resp && $resp !== "APIERR"){
            $product = array(
                'part' => $item->part,
                'title' => $item->title,
                'desc' => $item->desc,
                'weight' => $item->weight,
                'maker' => $item->maker,
                'quantity' =>$item->quantity,
                'arrival' => $item->arrival,
                'mfn' => $item->mfn,
                'upc' =>$item->upc,
                'sku' => $item->part,
                'price' => $item->price,
                'cost' => $item->cost,
                'sox' => $item->sox,
                'minprice' => $item->minprice,
                'catid' => $item->catid,
                'category' => $item->category,
            );
            $ebayItem->setEwiz($product);
            $ebayItem->setEbayPrice();
            $ebayItem->updateItem($item->id,$item->old_price);
        }else{
            Log::info($this->itemID." has been deleted");
            $ebayItem->endItemInLocal(true);
        }
    }

}