<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/4
 * Time: 13:49
 */

namespace App\EbayApi\Stores;

use App\EbayApi\Component\ItemImage;
use App\EbayApi\EbayApi;
use App\EbayApi\Lib\StringUtil;
use App\Models\EbayItemsDesc;
use App\Models\EbayStoreItems;
use App\Models\EbayStores;
use App\Models\ProductList;
use App\selfLog\Log;
use \DTS\eBaySDK\Constants;
use App\EbayApi\Component\EbayMail;
use DTS\eBaySDK\MerchantData\Enums\ListingTypeCodeType;
use DTS\eBaySDK\Trading\Enums\DetailLevelCodeType;
use DTS\eBaySDK\Trading\Enums\ItemSortTypeCodeType;
use App\EbayApi\Behavior;

class EbayStore
{
    /**
     * ebay store ID
     * @var int
     */
    public $storeId;
    /**
     * ebay store name
     * @var string
     */
    public $storeName;
    /**
     * if firstly run the program
     * @var bool
     */
    public $firstly;
    /**
     * ebay store config info
     * @var array
     */
    public $config;
    /**
     * total number of items list
     * @var int
     */
    public $totalNumber;
    /**
     * ebay store ship type
     * @var array
     */
    public $shipType;
    /**
     * the model class of sync table
     * @var object
     */
    protected $syncModel;
    /**
     * time of run the program
     * @var string
     */
    protected $startTime;
    /**
     * Instance of AddBehavior
     * @see AddBehavior
     */
    public $addBehavior;
    /**
     * Instance of UpdateBehavior
     * @see UpdateBehavior
     */
    public $updateBehavior;
    /**
     * Instance of EndBehavior
     * @see EndBehavior
     */
    public $endBehavior;
    /**
     * Instance of SyncBehavior
     * @see SyncBehavior
     */
    public $syncBehavior;

    public function __construct($storeName,$firstly=false)
    {
        $this->storeName = $storeName;
        $this->firstly = $firstly;
        $this->setStoreId();
        $this->_parseConfig();
        $this->setSyncModel();
        //$this->addBehavior = new AddBehavior($this);
        //$this->updateBehavior = new UpdateBehavior($this);
        $this->endBehavior = new Behavior\EndBehavior($this);
        $this->syncBehavior = new Behavior\SyncBehavior($this);
    }

    protected function _parseConfig()
    {
        $this->config = require_once (dirname(__FILE__)."/../config/{$this->storeName}.config.php");
        $this->config['siteId'] = Constants\SiteIds::US;
        $this->shipType = $this->config['production']['shipType'];
    }
    public function setStoreId($storeId=null)
    {
        if($storeId)
            $this->storeId = $storeId;
        else
        {
            $model = new EbayStores;
            $storeinfo = $model->getStoreIDByName($this->storeName);
            $this->storeId = $storeinfo['id'];
        }
    }

    /**
     *remove old contents in the mail
     */
    public function removeEmail()
    {
        EbayMail::removeEmailTmp($this->storeName);
    }
    /**
     *entry program
     */
    public function run()
    {
        //echo 'start on ' .date('[c]')."\n";
        $start = microtime(true);

        $this->removeEmail();
        //set ebayUpdate program's start time
        $this->setStartTime();
        //clear sync table of each store
        $this->clearSyncTbl();
        /**
         * initialize sync table of each store
         * sync all active items or items that has been changed to sync table.
         */
        $this->initSyncTbl();
        /**
         * sync online item's data to local db.
         */
        $this->syncToLocal();
        /**
         * End items
         * end not available items, out of stock and in condition that eBay team supply
         */
        if($this->firstly)
            $this->endItems();
        /**
         * Update Items
         * Check the all of online items, if they need update or not. if Yes, update it
         */
        $this->updateItems();
        if(in_array(date('G'),config('constant.ADD_PROCESS_TIME')) && date('i') <= 30){
            $this->addItems();
        }
        if(date('G') == config('constant.EMAIL_SEND_TIME') && date('i') >= 30){
            $this->sendEmail();
        }
    }

    public function setStartTime()
    {
        $this->startTime = date('Y-m-d H:i:s');
    }

    public function clearSyncTbl()
    {
        $this->syncModel->deleteAll();
    }

    public function setSyncModel($model=null)
    {
        if($model)
            $this->syncModel =$model;
        else{
            $syncTbl = 'Sync'.ucfirst($this->storeName);
            $syncTbl = '\App\Models\\'.$syncTbl;
            $this->syncModel = new $syncTbl;
        }
    }

    /**
     *Initialize sync table of the store.
     * Get store's list data and insert into sync table.
     */
    public function initSyncTbl()
    {
        if($this->firstly){
            /**
             * first time run the program,sync all items in stores to local ebay database
             * dispatch: GetMyeBaySelling
             * @see http://developer.ebay.com/DevZone/XML/docs/Reference/eBay/GetMyeBaySelling.html
             */
            Log::info("-----1. Begin getting {$this->storeName}'s active list:------");
            $params = array(
                'ListingType' => ListingTypeCodeType::C_FIXED_PRICE_ITEM,
                'DetailLevel' => [DetailLevelCodeType::C_RETURN_ALL],
                'EntriesPerPage' => 100,
                'PageNumber' => 0,
                'Pagination' => 1,
                'Sort' => ItemSortTypeCodeType::C_END_TIME,
                'ActiveList' => 1
            );
            $totalPage = 0;
            do{
                $params['PageNumber']++;
                $rsp = EbayApi::getInstance($this)->getMyeBaySelling($params);
                if($rsp){
                    Log::info("page {$params['PageNumber']}:" . count($rsp['list']));
                    Log::info("totalPage: ".$rsp['totalPage']);
                    Log::info("totalNumber: ".$rsp['totalNumber']);
                    $totalPage = $rsp['totalPage'];
                    $this->setTotalNumber($rsp['totalNumber']);
                    foreach($rsp['list'] as $item){
                        $this->_insertSyncTbl($item->ItemID);
                    }
                }
            }while($params['PageNumber'] < $totalPage);

        }else{
            /**
             * cronjob 定时执行该程序时，同步此时间范围内有数据变更的items到本地
             * 调用api：GetSellerEvents
             * @see http://developer.ebay.com/DevZone/XML/docs/Reference/eBay/GetSellerEvents.html
             */
            Log::info("-----1. Begin getting {$this->storeName}'s changed items:------");
            $model = EbayStores::find($this->storeId);
            $d = new \DateTime();
            $modtimeFrom = strtotime($model->update);
            $modtimeTo = strtotime($this->startTime);
            date_default_timezone_set('GMT'); // api时间参数需传递GMT时间
            $params  =  array(
                'ModTimeFrom' => $d->createFromFormat('Y-m-d\TH:i:s\Z',date('Y-m-d\TH:i:s\Z', $modtimeFrom)),
                'ModTimeTo'   => $d->createFromFormat('Y-m-d\TH:i:s\Z',date('Y-m-d\TH:i:s\Z', $modtimeTo)),
                'detailLevel' => [DetailLevelCodeType::C_RETURN_ALL],
            );
            $rsp = EbayApi::getInstance($this)->getSellerEvents($params);
            date_default_timezone_set('America/Los_Angeles');
            if($rsp){
                Log::info("totalNumber: ".count($rsp));
                foreach($rsp as $item){
                    $this->_insertSyncTbl($item->ItemID);
                }
            }else{
                Log::info("don't have any items that has been changed during this period");
            }
        }

    }
    /**
     * load detail data of online and compare with local data
     */
    public function syncToLocal()
    {
        Log::info("-----2. Begin to compare with local data------");
        $model = $this->syncModel;
        $result = $model::where('dealed','N')->get();
        if(count($result) > 0){
            foreach($result as $item){
                $status = $this->syncBehavior->run($item->itemid);
                if($status !== false){
                    $item->status = $status;
                    $item->dealed = 'Y';
                    $item->save();
                    Log::info($item->itemid ." status:$status");
                }
            }
        }else{
            Log::info("The ebay store has not items that need be dealed");
        }
    }

    /**
     * @param $number
     */
    public function setTotalNumber($number)
    {
        $this->totalNumber = $number;
    }

    /**
     * status ('A','M','D')
     * deal('N','Y')
     * @param $itemid
     * @return bool
     */
    protected function _insertSyncTbl($itemid)
    {
        if(empty($itemid)) return false;
        $model = $this->syncModel;
        if(!$model::find($itemid)){
            $model->itemid = $itemid;
            $model->status = '';
            $model->dealed = 'N';
            if($model->save())
                Log::info($itemid.' sync in');
        }

    }
    public function updateItems()
    {
        #1.handling with double auto items
        Log::info('------4. Begin to update ebay items---------');
        Log::info('handling with double auto items:');
        $ebayItemDesc = new EbayItemsDesc();
        $autoitems = $ebayItemDesc->fetchTypeItems('auto',$this->storeId);
        if($autoitems){
            $arrparts = array_map('current',$autoitems);
            $res = EbayItemsDesc::whereHas('item',function($query){
                $query->ofType('auto')->active()->where('store_id',$this->storeId);
            })->whereIn('part',$arrparts)->cateId()->get();
            foreach($res as $k=>$row){
                if(isset($row->part)){
                    $rdpart = $row->part;
                    echo $rdpart."\n";
                    Log::info("ItemNumber: $row->itemid, Part: $rdpart");
                    if($this->checkImageExist($rdpart)){
                        if($k != 0)
                            $this->endBehavior->run($row->itemid);
                    }
                }
            }
        }
        #2.handling with double manual items
        Log::info('handling with double manual items:');
        $manualitems = EbayItemsDesc::fetchTypeItems('manual',$this->storeId)->get();
        if(count($manualitems) > 0){
            Log::info('if has auto items');
            $arrparts = array_map('current', $manualitems);
            $res = EbayItemsDesc::whereHas('item',function($query){
                $query->ofType('auto')->active()->where('store_id',$this->storeId);
            })->whereIn('part',$arrparts)->cateId()->get();
            foreach($res as $k => $row){
                Log::info("ItemNumber: {$row->itemid}, Part: {$row->part}");
                if($this->checkImageExist($row->part)){
                    $this->endBehavior->run($row->itemid);
                }
            }
            # write manual double mail
            Log::info('write manual double mail');
            $res = EbayItemsDesc::whereHas('item',function($query){
                $query->ofType('manual')->active()->where('store_id',$this->storeId);
            })->whereIn('part',$arrparts)->cateId()->orderBy('part')->get();
            $content = '';
            foreach($res as $row)
            {
                Log::info("ItemNumber: {$row->itemid}, Part: {$row->part}");
                if($this->checkImageExist($row->part))
                    $content .= $row->part . '|' . $row->itemid . '|' . $row->title . "\r\n";
            }
            if($content)
                EbayMail::writeTmpEmail($content, $this->storeName, 'manualDouble');
        }
        # 3. ebay items that not exist on ewiz
        Log::info('handling with ebay items that not exist on ewiz:');
        $notexistitems = $ebayItemDesc->fetchNotExistItems($this->storeId);
        if (count($notexistitems) > 0){
            $content = '';
            foreach ($notexistitems as $row) {
                Log::info("ItemNumber: {$row->itemid}, Part: {$row->part}");
                if ($this->checkImageExist($row->part)){
                    if($row->type == 'auto'){
                        Log::info("Item " . $row->part . " does not exist, ready to offline");
                        $this->endBehavior->run($row->itemid);
                    }else{
                        Log::info("Manual item and doesn't exist on ewiz.com. Send alert email");
                        $content .= $row->part . '|' . $row->itemid . "|null|This item doesn't exist or out of stock on ewiz.com\r\n";
                    }
                }
            }if ($content)
                EbayMail::writeTmpEmail($content,$this->storeName,'balance');
        }
        # 4. rule items
        Log::info('handling with filter rule items:');
        $ruleItems = $ebayItemDesc->fetchFilterItems($this->storeId);
        if(count($ruleItems)){
            $content = '';
            $halfcontent = '';
            foreach ($ruleItems as $row) {
                Log::info("ItemNumber: {$row->itemid}, Part: {$row->part}");
                if($this->checkImageExist($row->part)){
                    $soldQty=$row->sold_qty;
                    if ($row->type = 'auto'){
                        if(intval($row->sold) >= 3){
                            Log::info("auto item ".$row->itemid."  is  out of balance but sold $soldQty. Send alert email");
                            $halfcontent .= $row->part . "|" . $row->itemid . "|" . $row->category . "|This auto item is out of balance but sold history is $soldQty, please check \r\n";
                        }else{
                            Log::info(("Item " . $row->part . " is out of balance."));
                        }
                    }elseif ($row->add_type == 'auto'){
                        if(intval($row->sold) >= 3){
                            Log::info("Half_auto item ".$row->itemid."  is  out of balance but sold $soldQty. Send alert email");
                            $halfcontent .= $row->part . "|" . $row->itemid . "|" . $row->category . "|This auto item is out of balance but sold history is $soldQty\r\n";
                        }
                    }else{
                        Log::info("Manual item and out of balance. Send alert email.");
                        $content .= $row->part. '|' . $row->itemid . '|' . $row->category . "|This item is out of balance or is not availbable in two days\r\n";

                    }

                }
            }
            if($content)
                EbayMail::writeTmpEmail($content, $this->storeName, 'balance');
            if($halfcontent)
                EbayMail::writeTmpEmail($halfcontent, $this->storeName, 'halfAuto');
        }

        # 5. update items
        Log::info('handling with update items:');
        $updateItems = $ebayItemDesc->fetchUpdateItems($this->storeId);
        if($total=count($updateItems) > 0){
            Log::info("total: $total");
            foreach ($updateItems as $row) {
                Log::info(("ItemNumber: {$row->itemid}, Part: {$row->part}"));
                if($this->checkImageExist($row->part)){
                    $row->old_price = sprintf("%01.2f",$row->old_price);
                    $this->updateBehavior->run($row);
                }
            }
        }
    }

    /**
     *ewiz db new items, added to ebay stores online
     */
    public function addItems()
    {
        Log::info('------5. Begin to add items to ebay online---------');
        $totalNumber = $this->getTotalNumber();
        $maxNumber = $this->config['SumToStore'];
        $partArr = $this->_getProductsForAdd();
        foreach ($partArr as $part) {
            Log::info(('--add new item: ' . $part .'--'));
            if ($this->addBehavior->run($part)){
                Log::info('Success to add new item [' . $part . ']to online');
                $totalNumber++;
            }else{
                Log::info('Fail to add new item [' . $part . '] to online');
                continue;
            }
            if($totalNumber > $maxNumber){
                Log::info('The total item of ebay store is full, can not add new item again!');
                break;
            }
            Log::info('--Finish to add new item: ' . $part . '--');
        }
    }
    public function getTotalNumber()
    {
        if(empty($this->totalNumber))
            $this->totalNumber = EbayApi::getInstance($this)->getActiveItemCount();
        return $this->totalNumber;
    }
    public function endItems()
    {
        Log::info('------3. begin to end items in local-------');
        if($this->syncModel->count() > 0){
            $endItemList = $this->syncModel->getEndItemList($this->storeId, $this->storeName);
            if(count($endItemList) > 0){
                foreach ($endItemList as $item) {
                    if($this->endBehavior->endItemInLocal($item->itemid))
                        Log::info("Succeed to end item {$item->itemid} in local.");
                    else
                        Log::info("failed to end item {$item->itemid} in local.");
                }
            }
        }
    }
    /**
     * get new products from ewiz
     */
    private function _getProductsForAdd()
    {
        $ebayParts = EbayStoreItems::getInstance()->getStoreActiveParts($this->storeId);
        $ewizParts = ProductList::getInstance()->getAvaliableParts($this->storeId);
        $ewizParts_productListNew = ProductList::getInstance()->getProductListNewPart();
        if($ewizParts_productListNew){
            foreach ($ewizParts_productListNew as $part) {
                if(in_array($part,$ewizParts)){
                    $key = array_search($part,$ewizParts);
                    unset($ewizParts[$key]);
                }
            }
        }
        $difParts = array();
        if (!empty($ewizParts)){
            Log::info('part array:' . StringUtil::array2String($ebayParts));
            foreach ($ewizParts as $val) {
                $var = trim($val);
                Log::info('check part:' . $var);
                if(!in_array($var,$ebayParts)){
                    array_push($difParts,$var);
                    Log::info($var . ' not in ebayPart');
                }
            }
            Log::info('Count Differ:' . count($difParts));
        }
        return $difParts;
    }
    public function checkImageExist($part)
    {
        $img = ItemImage::create()->getPreviewImg(trim($part), '_LG', true);
        if($img == config('constant.APP_IMG_DOMAIN') . config('constant.APP_IMG_DEFAULT'))
            return false;
        return true;
    }
}