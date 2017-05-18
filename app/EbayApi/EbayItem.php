<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 15:46
 */

namespace App\EbayApi;


use App\EbayApi\lib\StringUtil;
use App\Models\EbayItemsDesc;
use App\Models\EbayStoreItems;
use App\Models\ProductList;
use App\selfLog\Log;

class EbayItem extends BaseItem
{
    public $itemID;
    public $onlineItem;
    public $ebayStoreItem;

    /**
     * EbayItem constructor.
     * @param $store
     * @param null $itemID
     * @param string $part
     */
    public function __construct($store, $itemID, $part='null')
    {
        $this->itemID = $itemID;
        $this->ebayStoreItem = new EbayStoreItems;
        parent::__contruct($store,$part);
    }

    public function getItem()
    {
        return EbayApi::getInstance($this->store)->getItem(array('itemID'=>$this->itemID));
    }
    public function updateQuantity()
    {
        if($model = EbayItemsDesc::where('itemid',$this->itemID)->first()){
            $model->quantity = $this->onlineItem->Quantity;
            $model->sold_qty = $this->onlineItem->SellingStatus->QuantitySold;
            $model->save();
            Log::info("Revised quantity: " . $this->itemID);
        }
    }
    /**
     *sync item's data on ebay store to local DB
     */
    public function syncToLocal()
    {
        if($obj=$this->getItem()){
            $this->setOnlineItem($obj);
            /**
             * if items online end,update local db status to del
             */
            if($obj->SellingStatus->ListStatus != 'Active'){
                Log::info($this->itemID.' online is end');
                $this->endItemInLocal();
                return 'D';
            }
            if($this->isExists()){
                /**
                 * the item exists in local DB,compare to online and update
                 */
                Log::info($this->itemID.' exists');
                if($this->compare()){
                    return 'M';
                }elseif(!$this->store->firstly && $this->endItemInLocal())
                    return 'D';
                else{
                    Log::info($this->itemID." still in and not changed!");
                    return '';
                }
            }else{
                /**
                 * the item not in local DB, insert to the table
                 */

                 $res = $this->ebayStoreItem->getItemById($this->itemID,EbayStoreItems::STATUS_DEL);
                 if($res){
                     Log::info('item exists and updated status');
                     $this->ebayStoreItem->status = EbayStoreItems::STATUS_ACTIVE;
                     $this->ebayStoreItem->save();
                 }else{
                     $this->addItemInLocal();
                     return 'A';
                 }
            }
        }else{
            Log::info("The ebay store has not this item: " . $this->itemID);
            return false;
        }
    }

    /**
     * @param $obj
     */
    public function setOnlineItem($obj)
    {
        $this->onlineItem = $obj;
    }

    /**
     * check if exists and item'status.
     * active return true
     * del return false
     * @return mixed
     */
    public function isExists()
    {
        return $this->ebayStoreItem->getItemById($this->itemID);
    }

    /**
     * @usage: compare online item'endtime with current time
     * @return bool
     */
    public function beforeEnd()
    {
        $currentTime = time();
        if(empty($this->onlineItem))
            $this->setOnlineItem($this->getItem());
        if(!isset($this->onlineItem->ListingDetails->endTime))
        {
            Log::info($this->itemID . " has been deleted.");
            return true;
        }
        $endTime = $this->onlineItem->ListingDetails->EndTime;
        if($currentTime > strtotime($endTime))
            return true;
        Log::info($this->itemID . " still in");
        return false;
    }

    /**
     * @usage: change db field status'value from active to del
     * @param bool $isCheck
     * @return bool
     */
    public function endItemInLocal($isCheck=false)
    {
        if(!$isCheck && !$this->beforeEnd()){
            return false;
        }
        if($this->isExists()){
            Log::info($this->itemID." will be end");
            $this->ebayStoreItem->status = EbayStoreItems::STATUS_DEL;
            $this->ebayStoreItem->mtime = date('Y-m-d H:i:s');
            $this->ebayStoreItem->save();

        }
        return false;
    }

    /**
     * compare online with local and update if difference
     * @return bool
     */
    public function compare()
    {
        $itemModel = EbayStoreItems::with('desc')->where([
            ['status',EbayStoreItems::STATUS_ACTIVE],
            ['itemid',$this->itemID]
        ])->first();
        if($shipping=$itemModel->desc->shipping1){
            $shipData = explode(':',$shipping);
            $shipType = StringUtil::convertShipType($shipData[1]);
            $shipCost = $shipData[0];
        }else{
            $shipType = '';
            $shipCost = 0;
        }
        $price = $itemModel->desc->price;
        $price = sprintf("%01.2f",$price);
        $title = $itemModel->desc->title;
        $ebayCate = $itemModel->desc->ebay_cate_id;
        $type = $itemModel->type;
        $shipSerOpt = $this->onlineItem->ShippingDetails->ShippingServiceOptions;
        $onlineShip = is_array($shipSerOpt) ? $shipSerOpt : array($shipSerOpt);
        $onlineShipType = $onlineShip[0]->ShippingService;
        $onlineShipCost = $onlineShip[0]->ShippingServiceCost->_;
        $onlinePrice    = $this->onlineItem->SellingStatus->CurrentPrice->_;
        $onlineCateId   = $this->onlineItem->PrimaryCategory->CategoryID;
        $onlineTitle    = $this->onlineItem->Title;
        if($type == 'auto'){
            $flag = false;
            $message = '';
            if($onlineShipType != $shipType){
                $flag = true;
                $message .= 'Shipping type Have been changed | ';
            }
            if($onlineShipCost!=$shipCost)
            {
                $flag = true;
                $message .=  'Shipping cost Have been changed | ';
            }
            if($onlinePrice!=$price)
            {
                $flag = true;
                $message .=  'Price Have been changed | ';
            }
            if($onlineCateId!=$ebayCate)
            {
                $flag = true;
                $message .=  'Ebay category have been changed | ';
            }
            if(!preg_match("/<!--ADDED BY EBAYUPAUTO-->/i", $this->onlineItem->Description))
            {
                $flag = true;
                $message = 'Description Have been changed | ';
            }
            if($flag){
                Log::info($message);
                $itemModel->type = 'manual';
                if($itemModel->save()){
                    Log::info("Succeed to change the item's type: ".$this->itemID);
                    Log::info("Revised type to manual: " . $this->itemID);
                }else{
                    Log::info("Fail to change the item's type: ".$this->itemID);
                }
            }

        }
        if(!preg_match("/<!--ADDED BY EBAYUPAUTO-->/i", $this->onlineItem->Description) && $itemModel->type=='manual'){
            if(stripslashes($title)!=$onlineTitle || $price!=$onlinePrice || $ebayCate!=$onlineCateId || $onlineShipType!=$shipType || $onlineShipCost!=$shipCost){
                Log::info('title='.$onlineTitle);
                Log::info('dbtitle='.$title);
                Log::info('price = '.$onlinePrice);
                Log::info('dbprice='.$price);
                Log::info('category = '.$onlineCateId);
                Log::info('dbcategory='.$ebayCate);
                Log::info('shipType = '.$onlineShipType);
                Log::info('dbshipType='.$shipType);
                Log::info('shipCost = '.$onlineShipCost);
                Log::info('dbshipCost='.$shipCost);
                $itemModel->desc->title = $this->onlineItem->Title;
                $itemModel->desc->price = $this->onlineItem->SellingStatus->CurrentPrice->_;
                $itemModel->desc->ebay_cate_id = $this->onlineItem->PrimaryCategory->CategoryID;
                $this->updateShip($shipSerOpt,$itemModel);
                if($itemModel->desc->save()){
                    Log::info("Success to update $this->itemID.");
                    return true;
                }else{
                    Log::info("fail to update $this->itemID.");
                    return false;
                }
            }
        }elseif(preg_match("/<!--ADDED BY EBAYUPAUTO-->/i", $this->onlineItem->Description) && $itemModel->type=='manual' && $itemModel->add_type=='auto'){
           //
        }
        return false;

    }

    /**
     * add new item in local DB
     * @param null $resp
     * @return bool
     */
    public function addItemInLocal($resp=null)
    {
        $time = date('Y-m-d H:i:s');
        $part = StringUtil::GetPartByDeEncrypt($this->onlineItem->Description);
        if($part == 'noPart' || $part == null){
            Log::info("New item $this->itemID and errorPart");
            return false;
        }
        Log::info("New Item $this->itemID and part is $part");
        $plModel = ProductList::find($part);
        if(empty($plModel)){
            Log::info("New Item $this->itemID and part is $part not exist in product_list.");
            return false;
        }
        $descModel = new EbayItemsDesc();
        $descModel->part        = $part;
        $descModel->itemid      = $this->onlineItem->ItemID;
        $descModel->price       = $this->onlineItem->SellingStatus->CurrentPrice->_;
        $descModel->title       = $this->onlineItem->Title;
        $descModel->ebay_cate_id = $this->onlineItem->PrimaryCategory->CategoryID;
        $descModel->ebay_store_cate_id = $this->onlineItem->Storefront ? $this->onlineItem->Storefront->StoreCategoryID : 0;
        $descModel->handling_time = $this->onlineItem->DispatchTimeMax;
        $arrCateId = explode(':', $plModel->COMPONENT);
        $descModel->ewiz_cate_id = $arrCateId[1];
        $descModel->atime = $time;
        $descModel->mtime = $time;
        $descModel->preview_pic = empty($this->onlineItem->PictureDetails->PictureURL) ? $this->onlineItem->PictureDetails->GalleryURL : (is_array($this->onlineItem->PictureDetails->PictureURL) ? array_pop($this->onlineItem->PictureDetails->PictureURL) : $this->onlineItem->PictureDetails->PictureURL);
        $descModel->quantity = $this->onlineItem->Quantity;
        $descModel->combo = 'N';
        $descModel->duration = $this->onlineItem->ListingDuration;
        $descModel->employee = '';
        $descModel->return_accept = StringUtil::convertReturnType($this->onlineItem->ReturnPolicy->ReturnsAcceptedOption);
        $shipSerOpt = $this->onlineItem->ShippingDetails->ShippingServiceOptions;
        $this->updateShip($shipSerOpt,$descModel);
        $descModel->ex = array(
            'itemid' => $this->onlineItem->ItemID,
            'part'   => $part,
            'ewiz_price' => $plModel->PRICE,
            'ewiz_cost'  => $plModel->COST,
            'ewiz_sox'       => $plModel->SOX,
            'special'        => '',
            'desc'           => StringUtil::convertDescription($this->onlineItem->Description),
            'insurance'  => empty($this->onlineItem->ShippingDetails->InsuranceFee) ? 0 : $this->onlineItem->ShippingDetails->InsuranceFee->_,
            'tax' => 0,
            'taxrate' => $this->onlineItem->ShippingDetails->SalesTax->SalesTaxPercent,
            'final_fee' => 0,
            'paypal_fee' => empty($this->onlineItem->ClassifiedAdPayPerLeadFee) ? 0 : $this->onlineItem->ClassifiedAdPayPerLeadFee->_,
            'profit' => 0,
            'atime' => $time,
            'mtime' => $time,
        );
        if($descModel->save()){
            Log::info("Success to save new {$this->itemID} in desc");
            $itemModel = new EbayStoreItems();
            $itemModel->desc_id = $descModel->id();
            $itemModel->itemid = $this->itemID;
            $itemModel->type = 'manual';
            $itemModel->status = EbayStoreItems::STATUS_ACTIVE;
            $itemModel->store_id = $this->store->storeId;
            $itemModel->add_type = 'manual';
            $itemModel->pv = $this->onlineItem->HitCount;
            $itemModel->orders = '';
            $itemModel->employee = '';
            $itemModel->atime = $time;
            $itemModel->mtime = $time;
            $itemModel->addtime = $this->onlineItem->ListingDetails->StartTime;
            $itemModel->endtime = $this->onlineItem->ListingDetails->EndTime;
            if($itemModel->save()){
                Log::info("Success to save new {$this->itemID} in items");
                return true;
            }else{
                Log::info("Fail to save new {$this->itemID} in items");
            }
        }else{
            Log::info("Fail to save new {$this->itemID} in desc");
        }
        return false;

    }
    public function updateShip($shippingServiceOptions,$obj)
    {
        for($i=0;$i<=2;$i++) {
            $n = $i + 1;
            $col = 'shipping' . $n;
            $onlineShip = is_array($shippingServiceOptions) ? $shippingServiceOptions : array($shippingServiceOptions);
            if (empty($onlineShip[$i])) {
                $obj->$col = "";
            } else {
                $shipOptions = array(
                    $onlineShip[$i]->ShippingServiceCost->_,
                    $onlineShip[$i]->ShippingService,
                    $onlineShip[$i]->ShippingTimeMax,
                    $onlineShip[$i]->ShippingTimeMin,
                    $onlineShip[$i]->ShippingServicePriority,
                    empty($onlineShip[$i]->ShippingSurcharge) ? 0 : $onlineShip[$i]->ShippingSurcharge->_,
                );
                $obj->$col = implode(':', $shipOptions);
            }
        }
    }

    /**
     *item end online
     * @return bool
     */
    public function endItem()
    {
        $res = EbayApi::getInstance($this->store)->endItem(array('itemID'=>$this->itemID));
        if($res){
            Log::info("{$this->itemID} was successed off line");
            $this->addItemInLocal(true);
            Log::info("{$this->itemID} was successed to change the status [del] in local database");
            return true;
        }else{
            Log::info("{$this->itemID} was failed off line");
            return false;
        }
    }
    public function updateItem($desc_id,$oldPrice)
    {
        if($this->ebayPrice->getPrice() != 10000 && $this->ebayPrice->getShipCost() != 0){
            $this->setParams(__FUNCTION__);
            Log::info("old price: $oldPrice");
            Log::info("new price: {$this->params['price']}");
            if (($this->params['price'] - $oldPrice) < 0 && abs($this->params['price']-$oldPrice)*3 > $oldPrice){
                Log::info("Price changed too big, do not update it.");
                return false;
            }
            $res = EbayApi::getInstance($this->store)->reviseItem($this->params);
            if($res){
                Log::info($this->itemID . " :handle_time is " . $this->params['dispatchTimeMax']);
                Log::info("Success to update item to online store:--->" . $this->itemID);
                $this->updateItemInLocal($desc_id);
                return true;
            }
        }
    }

    /**
     * @param string $func
     */
    public function setParams($func='')
    {
        parent::setParams($func);
        $this->params['itemID'] = $this->itemID;
    }

    /**
     * 将更新的产品数据同步到本地库
     * @param int $id ID of ebay_items_desc table
     * @return bool.
     */
    public function updateItemInLocal($id)
    {
        $model = EbayItemsDesc::find($id);
        $time = date('Y-m-d H:i:s');
        $model->part = $this->params['applicationData'];
        $model->price = $this->params['price'];
        $model->title = strip_tags($this->params['title']);
        $model->ebay_cate_id = $this->params['primaryCategory'];
        if(isset($this->params['storeFront']))
            $model->ebay_store_cate_id = $this->params['storeFront'];
        $model->ewiz_cate_id = $this->ewiz['catid'];
        if(isset($this->params['pictureDetails']))
        {
            if(is_array($this->params['pictureDetails']))
                $model->preview_pic = $this->params['pictureDetails'][0];
            else
                $model->preview_pic = $this->params['pictureDetails'];
        }
        $model->quantity = $this->params['quantity'];
        $model->combo = 'N';
        $model->handling_time = $this->params['dispatchTimeMax'];
        $model->return_accept = StringUtil::convertReturnType($this->params['returnsAcceptedOption']);
        $model->mtime = $time;
        $model->ex = array(
            'desc' => StringUtil::convertDescription($this->params['description']),
            'ewiz_price' => $this->ewiz['price'],
            'ewiz_cost'  => $this->ewiz['cost'],
            'ewiz_sox'  => $this->ewiz['sox'],
            'insurance' => $this->ebayPrice->getInsurance(),
            'tax' => $this->ebayPrice->getTaxFee(),
            'final_fee' => $this->ebayPrice->getFinalFee(),
            'paypal_fee' => $this->ebayPrice->getPaypalFee(),
            'profit' => $this->ebayPrice->getProfitFee(),
            'mtime' => $time,
        );
        for($i=0; $i<=2; $i++)
        {
            $col = 'shipping'.($i+1);
            if(empty($this->params['shippingOptions'][$i]))
                $model->$col = '';
            else
            {
                $shippingOptions = array_values($this->params['shippingOptions'][$i]);
                $model->$col = implode(':', $shippingOptions);
            }
        }
        if($model->save()){
            Log::info('Success to update item in ebay_items_desc,itemId-->'.$this->itemID);
            return true;
        }else{
            Log::info('Fail to update item in ebay_items_desc,itemId-->'.$this->itemID);
            return false;
        }
    }
}