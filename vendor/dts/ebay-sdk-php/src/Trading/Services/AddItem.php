<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/8
 * Time: 8:04
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Trading\Enums\BuyerPaymentMethodCodeType;
use DTS\eBaySDK\Trading\Enums\CountryCodeType;
use DTS\eBaySDK\Trading\Enums\CurrencyCodeType;
use DTS\eBaySDK\Trading\Enums\GalleryTypeCodeType;
use DTS\eBaySDK\Trading\Enums\HitCounterCodeType;
use DTS\eBaySDK\Trading\Enums\ListingDurationCodeType;
use DTS\eBaySDK\Trading\Enums\ListingTypeCodeType;
use DTS\eBaySDK\Trading\Enums\PhotoDisplayCodeType;
use DTS\eBaySDK\Trading\Enums\ShippingTypeCodeType;
use DTS\eBaySDK\Trading\Types\AddItemRequestType;
use DTS\eBaySDK\Trading\Types\AmountType;
use DTS\eBaySDK\Trading\Types\BrandMPNType;
use DTS\eBaySDK\Trading\Types\CategoryType;
use DTS\eBaySDK\Trading\Types\ItemType;
use DTS\eBaySDK\Trading\Types\NameValueListArrayType;
use DTS\eBaySDK\Trading\Types\NameValueListType;
use DTS\eBaySDK\Trading\Types\PictureDetailsType;
use DTS\eBaySDK\Trading\Types\ProductListingDetailsType;
use DTS\eBaySDK\Trading\Types\ReturnPolicyType;
use DTS\eBaySDK\Trading\Types\SalesTaxType;
use DTS\eBaySDK\Trading\Types\ShippingDetailsType;
use DTS\eBaySDK\Trading\Types\ShippingServiceOptionsType;
use DTS\eBaySDK\Trading\Types\StorefrontType;

class AddItem
{
    private static $_item;
    private static $_category;
    private static $_storeFront;
    private static $_pictureDetails;
    private static $_salesTax;
    private static $_returnPolicy;
    private static $_shippingDetails;
    private static $_mpn;
    private static $_productListingDetails;
    protected static $_convertObject;

    private static function setApplicationData($part=null)
    {
        if(!is_null($part))
            self::$_item->ApplicationData = $part;
    }

    private static function setTitle($title=null)
    {
        if(!is_null($title))
            self::$_item->Title = $title;
    }

    private static function setPrimaryCategory($id=null)
    {
        if(!is_null($id))
        {
            self::$_category->CategoryID = $id;
            self::$_item->PrimaryCategory = self::$_category;
        }
    }

    private static function setStoreFront($id=null)
    {
        if(!is_null($id))
        {
            self::$_storeFront->StoreCategoryID = $id;
            self::$_storeFront->StoreCategory2ID = 0;
            self::$_item->Storefront = self::$_storeFront;
        }
    }

    private static function setPictureDetails($picUrl=null)
    {
        if(!is_null($picUrl))
        {
            self::$_pictureDetails->GalleryType = GalleryTypeCodeType::C_GALLERY;
            self::$_pictureDetails->PictureURL = $picUrl;
            if(is_array($picUrl))
                self::$_pictureDetails->PhotoDisplay = PhotoDisplayCodeType::C_SUPER_SIZE;
            self::$_item->PictureDetails = self::$_pictureDetails;
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

    private static function setPostalCode($postal=null)
    {
        if(!is_null($postal))
            self::$_item->PostalCode = $postal;
    }

    private static function setDescription($description=null)
    {
        if(!is_null($description))
            self::$_item->Description = $description;
    }

    private static function setHitCounter($codeType=HitCounterCodeType::C_BASIC_STYLE)
    {
        //self::$_item->HitCounter = HitCounterCodeType::CodeType_BasicStyle;
        self::$_item->HitCounter = $codeType;
    }

    private static function setListType($codeType=ListingTypeCodeType::C_FIXED_PRICE_ITEM)
    {
        //self::$_item->ListingType = ListingTypeCodeType::CodeType_FixedPriceItem;
        self::$_item->ListingType = $codeType;
    }

    private static function setPrice($price=null)
    {
        if(!is_null($price))
        {
            $startPrice = new AmountType();
            $startPrice->_ = $price;
            $startPrice->currencyID = CurrencyCodeType::C_USD;
            self::$_item->StartPrice = $startPrice;
        }
    }

    private static function setAutoPay($boolean=true)
    {
        self::$_item->AutoPay = $boolean;
    }

    private static function setQuantity($qty=0)
    {
        self::$_item->Quantity = intval($qty);
    }

    private static function setListingDuration($codeType = ListingDurationCodeType::C_GTC)
    {
        self::$_item->ListingDuration = $codeType;
    }

    private static function setCurrency($codeType = CurrencyCodeType::C_USD)
    {
        self::$_item->Currency = $codeType;
    }

    private static function setCountry($codeType = CountryCodeType::C_US)
    {
        self::$_item->Country = $codeType;
    }

    private static function setLocation($location=null)
    {
        if(!is_null($location))
            self::$_item->Location = $location;
    }

    private static function setOutOfStockControl($oosc=null)
    {
        if(!is_null($oosc))
            self::$_item->OutOfStockControl = $oosc;
    }

    private static function setPaymentMethods($codeType = BuyerPaymentMethodCodeType::C_PAY_PAL)
    {
        self::$_item->PaymentMethods = $codeType;
    }

    private static function setPayPalEmailAddress($email=null)
    {
        if(!is_null($email))
            self::$_item->PayPalEmailAddress = $email;
    }

    private static function setDispatchTimeMax($time=null)
    {
        if(!is_null($time))
            self::$_item->DispatchTimeMax = $time;
    }

    private static function setReturnPolicy()
    {
        self::$_item->ReturnPolicy = self::$_returnPolicy;
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
    private static function setRestockingFeeValueOption($option=null)
    {
        if(!is_null($option))
            self::$_returnPolicy->RestockingFeeValueOption = $option;
    }
    private static function setReturnDescription($description=null)
    {
        if(!is_null($description))
            self::$_returnPolicy->Description = $description;
    }


    private static function setShippingIncludedInTax($boolean=false)
    {
        self::$_salesTax->ShippingIncludedInTax = $boolean;
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

    private static function setShippingOptions($shippingOptions=array())
    {
        if(!empty($shippingOptions) && is_array($shippingOptions)){
            $n = 1;
            foreach($shippingOptions as $n => $ship_data){
                if($n>=3)break;
                $shippingServiceOption  = new ShippingServiceOptionsType();
                $shippingServiceOption->ShippingService = $ship_data['shipType'];

                $shippingServiceAdditionalCost = new AmountType();
                $shippingServiceAdditionalCost->_ = $ship_data['shipCost'];
                $shippingServiceAdditionalCost->currencyID = CurrencyCodeType::USD;
                $shippingServiceOption->ShippingServiceAdditionalCost = $shippingServiceAdditionalCost;

                $shippingServiceCost = new AmountType();
                $shippingServiceCost->_ = $ship_data['shipCost'];
                $shippingServiceCost->currencyID =  CurrencyCodeType::USD;
                $shippingServiceOption->ShippingServiceCost = $shippingServiceCost;

                self::$_shippingDetails->ShippingServiceOptions[] = $shippingServiceOption;
            }
        }
    }

    private static function setShippingType($codeType = ShippingTypeCodeType::C_FLAT)
    {
        self::$_shippingDetails->ShippingType = $codeType;
    }

    private static function setShippingDetails()
    {
        self::$_item->ShippingDetails = self::$_shippingDetails;
    }

    private static function setGlobalShipping($global)
    {
        self::$_shippingDetails->GlobalShipping = $global;
    }

    private static function setConditionID($id=null)
    {
        if(!is_null($id))
            self::$_item->ConditionID = $id;
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

    private static function setBrandMPN()
    {
        self::$_productListingDetails->BrandMPN = self::$_mpn;
    }

    private static function setReturnSearchResultOnDuplicates($boolean=true)
    {
        self::$_productListingDetails->ReturnSearchResultOnDuplicates = $boolean;
    }

    private static function setUseFirstProduct($boolean=true)
    {
        self::$_productListingDetails->UseFirtProduct = $boolean;
    }

    private static function setListIfNoProduct($boolean=true)
    {
        self::$_productListingDetails->ListIfNoProduct = $boolean;
    }

    private static function setProductListingDetails()
    {
        self::$_item->ProductListingDetails = self::$_productListingDetails;
    }

    private static function setItem()
    {
        self::$_convertObject->Item = self::$_item;
    }

    private static function setUPC($upc=null)
    {
        if(!is_null($upc))
            self::$_productListingDetails->UPC = $upc;
    }

    private static function setSKU($sku=null)
    {
        if(!is_null($sku))
            self::$_productListingDetails->SKU = $sku;
    }

    public static function convert($itemArray)
    {
        self::$_item = new ItemType();
        self::$_category = new CategoryType();
        self::$_storeFront = new StorefrontType();
        self::$_pictureDetails = new PictureDetailsType();
        self::$_salesTax = new SalesTaxType();
        self::$_returnPolicy = new ReturnPolicyType();
        self::$_shippingDetails = new ShippingDetailsType();
        self::$_mpn = new BrandMPNType();
        self::$_productListingDetails = new ProductListingDetailsType();
        self::$_convertObject = new AddItemRequestType();
        foreach($itemArray as $k => $v){
            $fun = 'set'.ucfirst($k);
            if(method_exists(__CLASS__,$fun)){
                if(is_null($v)){
                    self::$fun();
                }else{
                    self::$fun($v);
                }
            }
        }
        return self::$_convertObject;

    }

}