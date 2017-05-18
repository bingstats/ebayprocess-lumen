<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/4
 * Time: 13:39
 */

namespace App\EbayApi;

use App\selfLog\Log;
use \DTS\eBaySDK\Trading\Services;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Enums;

class EbayBaseApi
{
    public $config;
    public $_service;
    function __construct($store)
    {	
        $this->config['credentials'] = $store->config['production']['credentials'];
        $this->config['sitId'] = $store->config['siteId'];
        $this->config['authToken'] = $store->config['production']['authToken'];        
		$this->_service = new Services\TradingService([
			'credentials' => $this->config['credentials'],
			'siteId'      => $this->config['sitId']
		]);
    }

    public function getItemRequest($array)
    {
        $request = Services\GetItem::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->getItem($request);
    }
    public function getItemTransactionsRequest($array)
    {
        $request = Services\GetItemTransactions::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->getItemTransactions($request);
    }
    public function getMyeBaySellingRequest($array)
    {
        $request  = Services\GetMyeBaySelling::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->getMyeBaySelling($request);

    }
    public function getSellerEventsRequest($array)
    {
        $request =Services\GetSellerEvents::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->getSellerEvents($request);
    }
    public function endItemRequest($array)
    {
        $request = Services\EndItem::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->endItem($request);

    }
    public function addItemRequest($array)
    {
        $request = Services\AddItem::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->addItem($array);
    }
    public function reviseItemRequest($array)
    {
        $request = Services\ReviseItem::convert($array);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->reviseItem($request);
    }
    public function uploadSiteHostedPicturesRequest($params)
    {
        $request = Services\UploadSiteHostedPictures::convert($params);
        $request->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $request->RequesterCredentials->eBayAuthToken = $this->config['authToken'];
        return $this->_service->uploadSiteHostedPictures($request);
    }
    public function checkResponse($res,$asHtml=false,$addSlashes=true)
    {
        if(isset($res)){
            if($res->Ack == Enums\AckCodeType::C_SUCCESS || $res->Ack == Enums\AckCodeType::C_WARNING){
                return true;
            }else{
                $errmsg = '';
                if( isset($res->Errors) && count($res->Errors) > 0){
                    foreach ($res->Errors as $error) {
                        $errmsg .= '#' . $error->ErrorCode . ' : ' . ($asHtml ? htmlentities($error->LongMessage) : $error->LongMessage) . ($asHtml ? "<br>" : "\r\n");
                    }
                }
                if($addSlashes)
                    Log::error('Api error:' .addslashes($errmsg));
                else
                    Log::error('Api error:'. $errmsg);
            }
        }else{
            Log::error('request client error');
        }
            return false;
    }
}