<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 15:47
 */

namespace App\EbayApi;

use App\EbayApi\Component\EbayCat;
use App\EbayApi\Component\EbayCondition;
use App\EbayApi\Component\EbayHandlingTime;
use App\EbayApi\Component\EbayPrice;
use App\EbayApi\Component\EbayPriceOfCat;
use App\EbayApi\Component\EbayReturn;
use App\EbayApi\Component\EbayTemplate;
use App\EbayApi\Component\ItemImage;
use App\EbayApi\Lib\StringUtil;
use App\Models\EbayCategory;
use App\Models\EbayItemsDesc;
use App\Models\EbayStCatMap;
use App\Models\EbayStoreItems;
use App\Models\ProductList;
use App\selfLog\Log;
use DTS\eBaySDK\Trading\Enums\RefundOptionsCodeType;
use DTS\eBaySDK\Trading\Enums\RestockingFeeCodeType;
use DTS\eBaySDK\Trading\Enums\ReturnsAcceptedOptionsCodeType;
use DTS\eBaySDK\Trading\Enums\ReturnsWithinOptionsCodeType;
use DTS\eBaySDK\Trading\Enums\ShippingCostPaidByOptionsCodeType;

class BaseItem
{
    public $store;
    public $part;
    public $ewiz;
    public $params;
    public $ebayPrice;
    public static $ebay_remain_50_rate = 0.07;
    public static $ebay_remain_1000_rate = 0.05;
    public static $ebay_remain_1001_rate = 0.02;
    public function __construct($store,$part=null)
    {
        $this->store = $store;
        if(!empty($part)){
            $this->part = trim($part);
            $this->setEwiz();
            $this->setEbayPrice();
        }
    }
    public function setEwiz($product=null)
    {
        if($product === null){
            $product = ProductList::getInstance()->getAvaliableRowByPart($this->part);
        }
        if (method_exists($this->store,'convertEwizData')){
            $this->ewiz = $this->store->convertEwizData($product);
        }else{
            $this->ewiz = $product;
        }
    }
    public function getEwiz()
    {
        return $this->ewiz;
    }
    public function setEbayPrice()
    {
        $params = array(
            'insertFee'  => $this->store->config['EBAY_INSERT_FEE'],
            'paypalRate' => $this->store->config['PAYPAL_RATE'],
            'taxPercent' => $this->store->config['SalesTaxPercent'],
            'taxState'   => $this->store->config['SalesTaxState'],
            'markup'     => $this->store->config['MARKUP_PERCENTAGE'],
            'drawback'   => $this->store->config['SHIPPING_DRAWBACK'],
            'product'    => $this->ewiz,
        );
        $ebaycat = new EbayCat($this->part);
        if($ebaycat->return_cat == 'Special'){
           $this->ebayPrice = new EbayPriceOfCat($params);
           if (!$this->ebayPrice->recalc){
                Log::info('use a special price . price is '.$this->ebayPrice->getPrice());
           }else{
               $this->ebayPrice = new EbayPrice($params);
               Log::info(('price < sox or profit < -10,so use a normal price  . price is '.$this->ebayPrice->getPrice()));
           }
        }else{
            $this->ebayPrice = new EbayPrice($params);
            Log::info('use a normal price  . price is '.$this->ebayPrice->getPrice());
        }
    }

    /**
     * @return bool
     */
    public function isExistInEwiz()
    {
        return empty($this->ewiz) ? false : true;
    }

    /**
     * @return bool
     */
    public function checkTitleIsExist()
    {
        $title = StringUtil::covertToEbayApiTitle($this->ewiz['title'],80);
        $storeId = $this->store->storeId;
        $model = EbayStoreItems::whereHas('desc',function($q) use ($title){
            $q->where('title',$title);
        })->where('store_id',$storeId)->active()->get();
        if(count($model) > 0){
            Log::info('Title of '.$title.' is exist');
            return false;
        }
        return true;
    }
    public function setParams($func='')
    {
        $isSony = $this->getIsSony();
        $params['conditionID'] = EbayCondition::create()->getCondtionId($this->part,$this->ewiz['title']);
        $returnType = $params['conditionID'] == '2000' ? 'NotAccepted' : $this->getReturnType($isSony);
        $params['applicationData'] = $this->part;
        $title = StringUtil::covertToEbayApiTitle($this->ewiz['title'],80);
        $params['title'] = StringUtil::filterIllegalStr(preg_match("/[^\x1E-\x7E]/","",trim($title)));
        $params['primaryCategory'] = $this->getEbayPrimaryCategory();
        $params['storeFront'] = $this->getEbayStoreFront();
        //update,not update picture
        if (stristr($func,'update') === false){
            $params['pictureDetails'] = $this->getEbayPictures();
        }
        $params['itemSpecifics'] = array(array('name'=>'Brand','value'=>$this->ewiz['maker']),array('name'=>'MPN','value'=>$this->ewiz['mfn']));
        $params['postalCode'] = $this->store->config['PostalCode'];
        $params['description'] = $this->getEbayDescription($returnType, $isSony , $params['conditionID']);
        $params['hitCounter'] = null;
        $params['listType'] = null;
        $ids4mem = $this->ewiz['ids'];
        $pos1 = strpos($ids4mem,'651');
        $pos2 = strpos($ids4mem,'154');
        $pos3 = strpos($ids4mem,'21');
        if($pos3 !== false){
            //SSD pricing rule
            if(strtolower($this->ewiz['make'] == 'kingston' || $this->ewiz['maker'] == 'crucial')){
                $params['price'] = max($this->ebayPrice->getPrice(),($this->ewiz['sox']-15));
            }else{
                $params['price'] = max($this->ebayPrice->getPrice(),($this->ewiz['sox']-10));
            }
            //Memory pricing rule
        }elseif($pos1 !== false || $pos2 !== false){
            $params['price'] = max($this->ebayPrice->getPrice(),($this->ewiz['sox']-5));
        }else{
            $params['price'] = $this->ebayPrice->getPrice();
        }
        $params['autoPay'] = null;
        $params['quantity'] = $this->getEbayQuantity();
        $params['listingDuration'] = null;
        $params['currency'] = null;
        $params['country'] = null;
        $params['location'] = $this->store->config['Location'];
        $params['paymentMethods'] = null;
        $params['payPalEmailAddress'] = $this->store->config['PayPalEmailAddress'];
        if($returnType == 'NotAccepted') {
            $params['returnsAcceptedOption']        = ReturnsAcceptedOptionsCodeType::C_RETURNS_NOT_ACCEPTED;
            $params['returnsWithinOption']      = null;
            $params['shippingCostPaidByOption'] = null;
            $params['refundOption']             = null;
            $params['restockingFeeValueOption'] = null;
            $params['returnDescription']        = null;
        }else{
            $cateids = $this->ewiz['catid'];
            $findids = array(226,132,127,123,124,223,224,231,306,785);
            if(in_array($cateids,$findids)){
                $params['restockingFeeValueOption']      = RestockingFeeCodeType::C_PERCENT_20;
            }else{
                $params['restockingFeeValueOption'] = RestockingFeeCodeType::C_PERCENT_15;
            }
            $params['refundOption']             = RefundOptionsCodeType::C_MONEY_BACK;
            $params['returnsAcceptedOption']        = ReturnsAcceptedOptionsCodeType::C_RETURNS_ACCEPTED;
            $params['returnsWithinOption']      = ReturnsWithinOptionsCodeType::C_DAYS_30;
            $params['shippingCostPaidByOption'] = ShippingCostPaidByOptionsCodeType::C_BUYER;
            $params['returnDescription']       = 'If you have any problems or issues about your order, please contact us first before leaving a negative feedback or neutral feedback and before opening an ebay resolution case, we can easily help you resolve any problems or issues that may arise.We provide a 30-day period for refund on all products, but please be aware of the following conditions.:

We must receive the returned merchandise within thirty days of your purchase date. The item must be returned in brand new condition with all of the accessories. The manufacturer seals on the item must still be intact. If the item or packaging has any physical damage (burnt, chipped, cracked, seals broken, etc) or if there are accessories missing, your RMA case will be voided and the item will be returned to you. Any product described as "not refundable" on the product details page is not eligible for the 30-day refund policy. Note that all refund requests are subject to a 15% restocking fee. We will waive the restocking fee if testing proves that the item you received is defective or if you plan to make another purchase of comparable value.

For more details, please check our return policy. Thanks.';

        }
        $params['returnPolicy'] = null;
        $params['shippingIncludedTax'] = null;
        $params['salesTaxPercent'] = $this->store->config['SalesTaxPercent'] * 100;
        $params['salesTaxState'] = $this->store->config['SalesTaxState'];
        $params['salesTax'] = null;
        $params['shippingOptions'] = $this->getEbayShippingOptions();
        $params['shippingType'] = null;
        $params['shippingDetails'] = null;
        $params['mPN'] = $this->ewiz['mfn'];
        $params['brand'] = $this->ewiz['maker'];
        $params['brandMPN'] = null;
        $params['returnSearchResultOnDuplicates'] = null;
        $params['useFirstProduct'] = null;
        $params['listIfNoProduct'] = null;
        $params['productListingDetails'] = null;
        $params['dispatchTimeMax'] = EbayHandlingTime::create()->get($params);
        $params['item'] = null;
        $params['upc']=empty($this->ewiz['upc'])?"Does not apply":$this->ewiz['upc'];
        $params['globalShipping'] = $params['primaryCategory'] == '99231' ? 0:1;// exculde projector screen
        $params['globalShipping'] = in_array($this->ewiz['catid'],array('823','135','57')) ? 0:1; //exclude projector assistance, server case,Laptops/Notebook
        if($params['price']>=200){
            $params['globalShipping'] = in_array($this->ewiz['catid'],array('123','124','127','785','223','224','132')) ? 0:1;
        }
        //2015-12-24
        $params['SKU'] = $this->part;
        $params['globalShipping'] = in_array($this->ewiz['catid'], array('226','132','127','123','124','227','223','224','228','229','231','306','785')) ? 0:$params['globalShipping'];//exclude motherboard
        $this->params = $params;
    }

    /**
     * if category is sony
     * @return int
     */
    public function getIsSony()
    {
        $arrCatIds = array('121', '539', '744');
        return (in_array($this->ewiz['catid'], $arrCatIds) && $this->ewiz['maker'] == 'Sony') ? 1 : 0;
    }

    /**
     * @param int $isSony
     * @return string
     */
    public function getReturnType($isSony=0)
    {
        if($isSony == 1){
            $returnType = 'NotAccepted';
        }else{
            $returnType = EbayReturn::create()->getReturnType($this->ewiz['maker'],$this->part,$this->ewiz['catid']);
        }
        return $returnType;
    }
    public function getEbayPrimaryCategory()
    {
        $ebayCategory =EbayCategory::getInstance()->getDataByEwizCateId($this->ewiz['catid']);
        $ebay_cate_id = isset($ebayCategory->ebay_cate_id) ? $ebayCategory->ebay_cate_id : 16145;
        if($ebay_cate_id == 73839){
            // check the item is ipad accessiors or not
            $getCateId = StringUtil::isIpadAcc($this->ewiz['title']);
            if($getCateId != 1){
                $ebay_cate_id = $getCateId;
            }
        }
        return $ebay_cate_id;
    }
    public function getEbayStoreFront()
    {
        $ebayStoreCategory = EbayStCatMap::getInstance()->getEbayStoreCategoryId($this->ewiz['catid'],$this->store->storeId);
        $ebay_store_cate_id = isset($ebayStoreCategory->ebay_stcate_id) ? $ebayStoreCategory : 1;
        return $ebay_store_cate_id;
    }
    public function addItem()
    {
        if ($this->beforeAdd()){
            if($this->store->storeId == 6){
                Log::info("Ready to add new item to online store, eBay ItemId:-->".$this->part);
                return false;
            }
            $this->setParams(__FUNCTION__);
            $res = EbayApi::getInstance($this->store)->addItem($this->params);
            if($res){
                Log::info($res->ItemID . " :handle_time is " . $this->params['dispatchTimeMax']);
                Log::info("Success to add new item to online store, eBay ItemId:-->{$res->ItemID}");
                Log::info("Add item to local ebay product lib");
                $this->addItemInLocal($res);
                return true;
            }else{
                Log::info("Fail to add new item to online store.");
                return false;
            }
        }
        return false;
    }
    public function addItemInlocal($resp=null)
    {
        $descModel = new EbayItemsDesc();
        $descModel->itemid = $resp->ItemID;
        $descModel->itemid = $resp->ItemID;
        $descModel->part = $this->params['applicationData'];
        $descModel->price = $this->params['price'];
        $descModel->title = trim(strip_tags($this->params['title']));
        $descModel->ebay_cate_id = $this->params['primaryCategory'];
        $descModel->ebay_store_cate_id = isset($this->params['storeFront']) ? $this->params['storeFront'] : 0;
        $descModel->ewiz_cate_id = $this->ewiz['catid'];
        $descModel->preview_pic = is_array($this->params['pictureDetails']) ? $this->params['pictureDetails'][0] : $this->params['pictureDetails'];
        $descModel->quantity = $this->params['quantity'];
        $descModel->combo = 'N';
        $descModel->duration = isset($this->params['listingDuration']) ? $this->params['listingDuration'] : '';
        $descModel->handling_time = $this->params['dispatchTimeMax'];
        $descModel->atime = date('Y-m-d H:i:s');
        $descModel->mtime = date('Y-m-d H:i:s');
        $descModel->employee = 0;
        $descModel->return_accept = StringUtil::convertReturnType($this->params['returnsAcceptedOption']);
        $descModel->ex = array(
            'itemid' => $resp->ItemID,
            'part' => $this->params['applicationData'],
            'special' => '',
            'desc' => StringUtil::convertDescription($this->params['description']),
            'ewiz_price' => $this->ewiz['price'],
            'ewiz_cost' => $this->ewiz['cost'],
            'ewiz_sox' => $this->ewiz['sox'],
            'insurance' => $this->ebayPrice->getInsurance(),
            'tax' => $this->ebayPrice->getTaxFee(),
            'taxrate' => $this->store->config['SalesTaxPercent'] * 100,
            'final_fee' => $this->ebayPrice->getFinalFee(),
            'paypal_fee' => $this->ebayPrice->getPaypalFee(),
            'profit' => $this->ebayPrice->getProfitFee(),
            'atime' => date('Y-m-d H:i:s'),
            'mtime' => date('Y-m-d H:i:s'),
        );
        for($i=0; $i<=2; $i++)
        {
            $col = 'shipping'.($i+1);
            if(empty($this->params['shippingOptions'][$i]))
                $descModel->$col = '';
            else
            {
                $shippingOptions = array_values($this->params['shippingOptions'][$i]);
                $descModel->$col = implode(':', $shippingOptions);
            }
        }
        if($descModel->save(false))
        {
            EbayLog::create()->log('Success to add item in ebay_items_desc,itemId-->'.$resp->ItemID);
            $model = new EbayStoreItems();
            $model->desc_id = $descModel->getPrimaryKey();
            $model->store_id = $this->store->storeId;
            $model->add_type = 'auto';
            $model->type = 'auto';
            $model->itemid = $resp->ItemID;
            $model->orders = 0;
            $model->pv = 0;
            $model->status = 'active';
            $model->atime = date('Y-m-d H:i:s');
            $model->mtime = date('Y-m-d H:i:s');
            $model->employee = 0;
            $model->addtime = $resp->StartTime;
            $model->endtime = $resp->EndTime;
            if($model->save())
            {
                Log::info('Success to add item in ebay_store_items,itemId-->'.$resp->ItemID);
                return true;
            }
            else
                Log::info('Fail to add item in ebay_store_items,itemId-->'.$resp->ItemID);
        }
        else
            Log::info('Fail to add item in ebay_items_desc,itemId-->'.$resp->ItemID);
        return false;
    }
    /**
     * usage:check title exist, if exist return false.
     * @return bool
     */
    public function beforeAdd()
    {
        if(!$this->checkTitleIsExist()){
            return false;
        }
        return true;
    }
    public function  getEbayPictures()
    {
        $picURLs = array();
        $imageNames = ItemImage::create()->getImages($this->part);
        foreach ($imageNames as $imageName) {
            if ($imageName != config('constant.APP_IMG_DEFAULT')){
                $picUrl = $imageName;
                if(!ItemImage::create()->checkImageSize($picUrl))
                    continue;
                    $picRs = EbayApi::getInstance($this->store)->uploadPicture(
                        array(
                            'pictureName'=>$this->part,
                            'externalPictureURL'=>$picUrl
                        ));
                    if($picRs){
                        $picRs = substr_replace($picRs,'F',-1,1);
                        $picURLs[] = $picRs;
                    }
            }else{
                Log::info(("image of $this->part is /images/notavailable.jpg"));
            }
        }
        if(empty($picURLs) || count($picURLs) == 1){
            $picURLs = ItemImage::create()->getPreviewImg($this->part, '_LG', true);
        }
        return $picURLs;
    }
    public function getEbayDescription($returnType, $isSony, $condition = null)
    {
        // $isArrival = ($this->ewiz['arrival'] == 0) ? true : false;
        $isArrival = true;
        $params = array(
            'storeId' => $this->store->storeId,
            'storeName' => $this->store->storeName,
            'isSony' => $isSony,
            'condition' => $condition,
        );
        $ebayTemplate = new EbayTemplate($params);
        $desc = $ebayTemplate->get($this->part, $this->ewiz['title'], $this->ewiz['desc'], $this->ewiz['weight'], $this->ebayPrice->getPrice(), $this->ewiz['cost'], 'UPS Ground', $this->ebayPrice->getShipCost(), $isArrival, $returnType);
        return StringUtil::filterIllegalStr( preg_replace("/[^\x1E-\x7E]/", "", trim($desc)) );
    }
    public function getEbayShippingOptions()
    {
        $shipOptions = array();
        $shipCostList = $this->ebayPrice->shipCostList;
        $i = $free = 0;

        foreach($shipCostList as $type=>$cost)
        {
            if($i == 0) $free = $cost;
            $shipOptions[$i]['shipCost'] = $cost - $free;
            $shipOptions[$i]['shipType'] = $this->store->shipType[$type];
            $i++;
        }

        return $shipOptions;
    }

}