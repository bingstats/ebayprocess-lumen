<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 15:27
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Trading\Enums\CurrencyCodeType;
use DTS\eBaySDK\Trading\Enums\HitCounterCodeType;
use DTS\eBaySDK\Trading\Enums\ShippingTypeCodeType;
use DTS\eBaySDK\Trading\Types\AmountType;
use DTS\eBaySDK\Trading\Types\BrandMPNType;
use DTS\eBaySDK\Trading\Types\CategoryType;
use DTS\eBaySDK\Trading\Types\ItemType;
use DTS\eBaySDK\Trading\Types\NameValueListArrayType;
use DTS\eBaySDK\Trading\Types\NameValueListType;
use DTS\eBaySDK\Trading\Types\ProductListingDetailsType;
use DTS\eBaySDK\Trading\Types\ReturnPolicyType;
use DTS\eBaySDK\Trading\Types\ReviseItemRequestType;
use DTS\eBaySDK\Trading\Types\SalesTaxType;
use DTS\eBaySDK\Trading\Types\ShippingDetailsType;
use DTS\eBaySDK\Trading\Types\ShippingServiceOptionsType;
use DTS\eBaySDK\Trading\Types\StorefrontType;

class ReviseItem
{
    protected static $_convertObject;
    private static $_item;
    private static $_salesTax;
    private static $_shippingDetails;
    private static $_returnPolicy;
    private static $_productListingDetails;
    private static $_mpn;

    private static function setItemID($id)
    {
        self::$_item->ItemID = $id;
    }
    private static function setPrice($price=null)
    {
        if($price!==null)
        {
            $startPrice = new AmountType();
            $startPrice->_ = $price;
            $startPrice->currencyID = CurrencyCodeType::C_USD;
            self::$_item->StartPrice = $startPrice;
        }
    }
    private static function setTitle($title=null)
    {
        if($title!==null)
            self::$_item->Title = $title;
    }

    private static function setQuantity($qty=null)
    {
        if($qty!==null)
            self::$_item->Quantity = intval($qty);
    }

    private static function setDescription($desc=null)
    {
        if($desc!==null)
            self::$_item->Description = $desc;
    }

    private static function setDispatchTimeMax($time=null)
    {
        if($time!==null)
            self::$_item->DispatchTimeMax = intval($time);
    }

    private static function setAutoPay($boolean=true)
    {
        self::$_item->AutoPay = $boolean;
    }

    private static function setHitCounter($codeType=HitCounterCodeType::C_BASIC_STYLE)
    {
        self::$_item->HitCounter = $codeType;
    }

    private static function setPrimaryCategory($id=null)
    {
        if($id!==null)
        {
            $category = new CategoryType();
            $category->CategoryID = $id;
            self::$_item->PrimaryCategory = $category;
        }
    }

    private static function setStoreFront($id=null)
    {
        if(!is_null($id)){
            $storeFront = new StorefrontType();
            $storeFront->StoreCategoryID = $id;
            $storeFront->StoreCategory2ID = 0;
            self::$_item->Storefront = $storeFront;
        }
    }

    private static function setItemSpecifics($array=array())
    {
        if(is_array($array) && !empty($array)){
            $nameValueListArray = new NameValueListArrayType();
            foreach($array as $k => $v){
                $nameValueList = new NameValueListType();
                $nameValueList->Name = $v['name'];
                $nameValueList->Value = $v['value'];
                $nameValueListArray->NameValueList[] = $nameValueList;
            }
            self::$_item->ItemSpecifics = $nameValueListArray;
        }
    }

    private static function setShippingIncludedInTax($boolean=false)
    {
        self::$_salesTax->ShippingIncludedTax = $boolean;
    }

    private static function setSalesTaxPercent($salesTaxPercent=null)
    {
        if(!is_null($salesTaxPercent))
            self::$_salesTax->SalesTaxPercent = $salesTaxPercent;
    }

    private static function setSalesTaxState($salesTaxState=null)
    {
        if(!is_null($salesTaxState))
            self::$_salesTax->SalesTaxState = $salesTaxState;
    }

    private static function setSalesTax()
    {
        self::$_shippingDetails->SalesTax = self::$_salesTax;
    }

    private static function setShippingOptions($shippingOptions=null)
    {
        if(is_array($shippingOptions)){
            $n = 1;
            foreach($shippingOptions as $n => $ship_data){
                if($n>=3)break;
                $shippingServiceOption  = new ShippingServiceOptionsType();
                $shippingServiceOption->ShippingService = $ship_data['shipType'];

                $shippingServiceAdditionalCost = new AmountType();
                $shippingServiceAdditionalCost->_ = $ship_data['shipCost'];
                $shippingServiceAdditionalCost->currencyID = CurrencyCodeType::C_USD;
                $shippingServiceOption->ShippingServiceAdditionalCost = $shippingServiceAdditionalCost;

                if(isset($ship_data['surcharge']) && $ship_data['surcharge']!=0)
                {
                    $shippingServiceShippingSurcharge = new AmountType();
                    $shippingServiceShippingSurcharge->_ = $ship_data['surcharge'];
                    $shippingServiceShippingSurcharge->currencyID = CurrencyCodeType::C_USD;
                    $shippingServiceOption->ShippingSurcharge = $shippingServiceShippingSurcharge;
                }

                $shippingServiceCost = new AmountType();
                $shippingServiceCost->_ = $ship_data['shipCost'];
                $shippingServiceCost->currencyID =  CurrencyCodeType::C_USD;
                $shippingServiceOption->ShippingServiceCost = $shippingServiceCost;

                self::$_shippingDetails->ShippingServiceOptions[] = $shippingServiceOption;
            }
        }
    }

    private static function setShippingType($codeType = ShippingTypeCodeType::C_FLAT)
    {
        self::$_shippingDetails->ShippingType = $codeType;
    }

    private static function setGlobalShipping($global)
    {
        self::$_shippingDetails->GlobalShipping = $global;
    }

    private static function setShippingDetails()
    {
        self::$_item->ShippingDetails = self::$_shippingDetails;
    }

    private static function setConditionID($id=null)
    {
        if(!is_null($id))
            self::$_item->ConditionID = $id;
    }

    private static function setOutOfStockControl($oosc=null)
    {
        if(!is_null($oosc))
            self::$_item->OutOfStockControl = $oosc;
    }

    private static function setReturnPolicy()
    {
        self::$_item->ReturnPolicy = self::$_returnPolicy;//var_dump(self::$_returnPolicy);die;
    }
    private static function setRestockingFeeValueOption($option=null)
    {
        if(!is_null($option))
            self::$_returnPolicy->RestockingFeeValueOption = $option;
    }

    private static function setReturnsAcceptedOption($option=null)
    {
        if(!is_null($option))
            self::$_returnPolicy->ReturnsAcceptedOption = $option;
    }

    private static function setReturnsWithinOption($option=null)
    {
        if(!is_null($option))
            self::$_returnPolicy->ReturnsWithinOption = $option;
    }

    private static function setShippingCostPaidByOption($option=null)
    {
        if(!is_null($option))
            self::$_returnPolicy->ShippingCostPaidByOption = $option;
    }

    private static function setRefundOption($option=null)
    {
        if(!is_null($option))
            self::$_returnPolicy->RefundOption = $option;
    }

    private static function setReturnDescription($description=null)
    {
        if(!is_null($description))
            self::$_returnPolicy->Description = $description;
    }

    private static function setItem()
    {
        self::$_convertObject->Item = self::$_item;
    }

    //2015-07-16
    private static function setProductListingDetails()
    {
        self::$_item->ProductListingDetails = self::$_productListingDetails;
    }

    private static function setBrandMPN()
    {
        self::$_productListingDetails->BrandMPN = self::$_mpn;
    }

    private static function setUPC($upc=null)
    {
        if(!is_null($upc))
            self::$_productListingDetails->UPC = $upc;
    }

    private static function setMPN($mpn=null)
    {
        if(!is_null($mpn))
            self::$_mpn->MPN = $mpn;
    }

    private static function setBrand($brand=null)
    {
        if(!is_null($brand))
            self::$_mpn->Brand = $brand;
    }

    public static function convert($objectArray)
    {
        self::$_convertObject = new ReviseItemRequestType();
        self::$_item = new ItemType();
        self::$_salesTax = new SalesTaxType();
        self::$_shippingDetails = new ShippingDetailsType();
        self::$_returnPolicy = new ReturnPolicyType();

        //2015-07-16
        self::$_productListingDetails = new ProductListingDetailsType();
        self::$_mpn = new BrandMPNType();
        //print_r ($objectArray);
        if(is_array($objectArray)){
            foreach($objectArray as $k => $v){
                $fun = 'set'.ucfirst($k);
                if(method_exists(__CLASS__,$fun)){
                    if(is_null($v)){
                        self::$fun();
                    }else{
                        self::$fun($v);
                    }
                }
            }
        }
        //var_dump(self::$_convertObject);die;
        return self::$_convertObject;
    }
}