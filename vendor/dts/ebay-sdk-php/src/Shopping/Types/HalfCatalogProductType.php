<?php
/**
 * The contents of this file was generated using the WSDLs as provided by eBay.
 *
 * DO NOT EDIT THIS FILE!
 */

namespace DTS\eBaySDK\Shopping\Types;

/**
 *
 * @property string $Title
 * @property string $DetailsURL
 * @property string $StockPhotoURL
 * @property \DTS\eBaySDK\Shopping\Types\ShippingCostSummaryType $ShippingCostSummary
 * @property boolean $DisplayStockPhotos
 * @property integer $ItemCount
 * @property \DTS\eBaySDK\Shopping\Types\ProductIDType[] $ProductID
 * @property string $DomainName
 * @property \DTS\eBaySDK\Shopping\Types\NameValueListArrayType $ItemSpecifics
 * @property \DTS\eBaySDK\Shopping\Types\SimpleItemArrayType $ItemArray
 * @property integer $ReviewCount
 * @property \DTS\eBaySDK\Shopping\Types\AmountType $MinPrice
 */
class HalfCatalogProductType extends \DTS\eBaySDK\Types\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [
        'Title' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'Title'
        ],
        'DetailsURL' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'DetailsURL'
        ],
        'StockPhotoURL' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'StockPhotoURL'
        ],
        'ShippingCostSummary' => [
            'type' => 'DTS\eBaySDK\Shopping\Types\ShippingCostSummaryType',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ShippingCostSummary'
        ],
        'DisplayStockPhotos' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'DisplayStockPhotos'
        ],
        'ItemCount' => [
            'type' => 'integer',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ItemCount'
        ],
        'ProductID' => [
            'type' => 'DTS\eBaySDK\Shopping\Types\ProductIDType',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'ProductID'
        ],
        'DomainName' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'DomainName'
        ],
        'ItemSpecifics' => [
            'type' => 'DTS\eBaySDK\Shopping\Types\NameValueListArrayType',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ItemSpecifics'
        ],
        'ItemArray' => [
            'type' => 'DTS\eBaySDK\Shopping\Types\SimpleItemArrayType',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ItemArray'
        ],
        'ReviewCount' => [
            'type' => 'integer',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ReviewCount'
        ],
        'MinPrice' => [
            'type' => 'DTS\eBaySDK\Shopping\Types\AmountType',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'MinPrice'
        ]
    ];

    /**
     * @param array $values Optional properties and values to assign to the object.
     */
    public function __construct(array $values = [])
    {
        list($parentValues, $childValues) = self::getParentValues(self::$propertyTypes, $values);

        parent::__construct($parentValues);

        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = array_merge(self::$properties[get_parent_class()], self::$propertyTypes);
        }

        if (!array_key_exists(__CLASS__, self::$xmlNamespaces)) {
            self::$xmlNamespaces[__CLASS__] = 'xmlns="urn:ebay:apis:eBLBaseComponents"';
        }

        $this->setValues(__CLASS__, $childValues);
    }
}
