<?php
/**
 * The contents of this file was generated using the WSDLs as provided by eBay.
 *
 * DO NOT EDIT THIS FILE!
 */

namespace DTS\eBaySDK\BusinessPoliciesManagement\Types;

/**
 *
 * @property string[] $shipToLocation
 * @property string $shippingService
 * @property integer $sortOrderId
 * @property boolean $freeShipping
 * @property \DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount $codFee
 * @property boolean $fastShipping
 * @property \DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount $shippingServiceAdditionalCost
 * @property \DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount $shippingServiceCost
 * @property \DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount $shippingSurcharge
 * @property boolean $buyerResponsibleForShipping
 * @property boolean $buyerResponsibleForPickup
 */
class ShippingPolicyInfoService extends \DTS\eBaySDK\Types\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [
        'shipToLocation' => [
            'type' => 'string',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'shipToLocation'
        ],
        'shippingService' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingService'
        ],
        'sortOrderId' => [
            'type' => 'integer',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'sortOrderId'
        ],
        'freeShipping' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'freeShipping'
        ],
        'codFee' => [
            'type' => 'DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'codFee'
        ],
        'fastShipping' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'fastShipping'
        ],
        'shippingServiceAdditionalCost' => [
            'type' => 'DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingServiceAdditionalCost'
        ],
        'shippingServiceCost' => [
            'type' => 'DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingServiceCost'
        ],
        'shippingSurcharge' => [
            'type' => 'DTS\eBaySDK\BusinessPoliciesManagement\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingSurcharge'
        ],
        'buyerResponsibleForShipping' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'buyerResponsibleForShipping'
        ],
        'buyerResponsibleForPickup' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'buyerResponsibleForPickup'
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
            self::$xmlNamespaces[__CLASS__] = 'xmlns="http://www.ebay.com/marketplace/selling/v1/services"';
        }

        $this->setValues(__CLASS__, $childValues);
    }
}
