<?php
namespace App\EbayApi;

use App\selfLog\Log;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Services;
use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading\Enums;
use Mockery\CountValidator\Exception;

class EbayApi extends EbayBaseApi
{	
	private static $_instance = array();

    /**
     * instance of the obj
     * @param $store
     * @return mixed
     */
    public static function getInstance($store)
    {
        if(isset(self::$_instance[$store->storeName]) && self::$_instance[$store->storeName] instanceof EbayApi)
            return self::$_instance[$store->storeName];
        self::$_instance[$store->storeName] = new EbayApi($store);
        return self::$_instance[$store->storeName];
    }

    /**
     * @param $array
     * @return bool|Types\ItemType|void
     */
    public function getItem($array)
    {
        $array['detailLevel'] = [Enums\DetailLevelCodeType::C_RETURN_ALL];
        $re = $this->getItemRequest($array);
        if($this->checkResponse($re)){
            try{
                return $re->Item;
            }catch(\Exception $e){
                Log::error($e);
                return;
            }
        }else{
            return false;
        }

    }
    public function addItem($params)
    {
        $res = $this->addItemRequest($params);
        if ($this->checkResponse($res)){
            return $res;
        }else{
            return false;
        }
    }
    public function endItem($params)
    {
        $params['endingReason'] = Enums\EndReasonCodeType::C_NOT_AVAILABLE;
        $res = $this->endItemRequest($params);
        return $this->checkResponse($res);
    }

    /**
     * @param $params
     * @return bool|Types\GetItemTransactionsResponseType
     */
    public function getItemTransactions($params)
    {
       $res = $this->getItemTransactionsRequest($params);
       if(!$this->checkResponse($res)){
           return false;
       }else{
           return $res;
       }
    }

    /**
     * @param $params
     * @return array|bool
     */
    public function getMyeBaySelling($params)
    {
        $res = $this->getMyeBaySellingRequest($params);
        if(!$this->checkResponse($res)){
            return false;
        }
        if(!empty($res->ActiveList)){
            $totalPage = $res->ActiveList->PaginationResult->TotalNumberOfPages;
            $totalNumber = $res->ActiveList->PaginationResult->TotalNumberOfEntries;
            $itemArray = $res->ActiveList->ItemArray->Item;
            if(!is_array($itemArray)){
                $itemArray = array($itemArray);
            }
            return array('list'=>$itemArray, 'totalPage'=>$totalPage, 'totalNumber'=>$totalNumber);
        }
        return false;
    }

    /**
     * @param $params
     * @return array|bool|Types\ItemType[]
     */
    public function getSellerEvents($params)
    {
        $res = $this->getSellerEventsRequest($params);
        if(!$this->checkResponse($res)) return false;
        if(!empty($res->ItemArray->Item))
        {
            if(!is_array($res->ItemArray->Item))
                $itemArray = array($res->ItemArray->Item);
            else
                $itemArray = $res->ItemArray->Item;
            return $itemArray;
        }
        else
            return false;
    }
    public function uploadPicture($params)
    {
        $res = $this->uploadSiteHostedPicturesRequest($params);
        if($res && $this->checkResponse($res)){
            $picUrl = $res->SiteHostedPictureDetails->FullURL;
        }
        return isset($picUrl) ? $picUrl : '';
    }

    public function reviseItem($params)
    {
        $res = $this->reviseItemRequest($params);
        return $this->checkResponse($res);
    }

    /**
     * get the total number of active item in store
     * @return int
     */
    public function getActiveItemCount()
    {
        $params  =  array(
            'ListingType' => Enums\ListingTypeCodeType::C_FIXED_PRICE_ITEM,
            'DetailLevel' => [Enums\DetailLevelCodeType::C_RETURN_ALL],
            'EntriesPerPage' => 100,
            'PageNumber' => 1,
            'Pagination' =>1,
            'Sort' => Enums\ItemSortTypeCodeType::C_END_TIME,
            'ActiveList' => 1
        );
        $result = $this->getMyeBaySelling($params);
        if(!empty($result) && isset($result['totalNumber'])){
            return $result['totalNumber'];
        }else
            return 0;
    }

}