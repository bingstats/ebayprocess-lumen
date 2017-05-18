<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/30
 * Time: 16:20
 */

namespace App\EbayApi\Component;

use App\EbayApi\ShippingApi\ShippingRate;
use App\selfLog\Log;

class EbayShipping extends EbayComponent
{
    const UPGDR  = 'UPSGround';
    const USPSPRI = 'USPSPriority';
    const PS1CS   = 'USPSFirstClass';

    public $drawback;

    private static $_params = array(
        // 'part' => $part,
        // 'total_price' => ,
        'to_country' => 'US',
        'to_zipcode' => '10001',
        'has_discount' => '1',
        'has_signature' => '1',
    );

    public static function create($config=array(), $className=__CLASS__)
    {
        return parent::create($config, $className);
    }

    /**
     * get shipping methods by item
     * @access public
     * @param array $params item info
     * @param int $type 1: only get UPS Ground for calc price; 2: get UPS and USPS
     * @return array ship type
     */
    public function getShipCostList($params, $type=1)
    {
        $shipOptions = array();
        $shipRates = $this->_getShipRates($params);
        // echo '<pre>';print_r($shipRates);exit;
        if(is_array($shipRates))
        {
            if($type == 2)
            {
                $UPGDRCost = $shipRates['UPGDR']['cost'] - $this->drawback;
                $USPSPRICost = $shipRates['USPSPRI']['cost'] - $this->drawback;
                $PS1CSCost = $shipRates['PS1CS']['cost'] - $this->drawback;
                if(is_null($shipRates['USPSPRI']['name']) || $USPSPRICost<=0 || $USPSPRICost==null
                    || is_null($shipRates['PS1CS']['name']) || $PS1CSCost<=0 || $PS1CSCost==null)
                    $shipOptions = array(
                        self::UPGDR => $UPGDRCost,
                    );
                elseif(is_null($shipRates['USPSPRI']['name']) || $USPSPRICost<=0 || $USPSPRICost==null)
                    $shipOptions = array(
                        self::UPGDR => $UPGDRCost,
                        self::PS1CS => $PS1CSCost,
                    );
                else
                    $shipOptions = array(
                        self::UPGDR   => $UPGDRCost,
                        self::USPSPRI => $USPSPRICost,
                        self::PS1CS   => $PS1CSCost,
                    );
            }
            else
            {
                $UPGDRCost = $shipRates['UPGDR']['cost'] - $this->drawback;
                if(!array_key_exists('PS1CS', $shipRates) && !array_key_exists('USPSPRI', $shipRates))
                    $shipOptions = array(
                        self::UPGDR => $UPGDRCost,
                    );
                elseif(!array_key_exists('PS1CS', $shipRates) && array_key_exists('USPSPRI', $shipRates) && $shipRates['USPSPRI']['cost'] != 0)
                {
                    $USPSPRICost = $shipRates['USPSPRI']['cost'] - $this->drawback;
                    $shipOptions = array(
                        self::UPGDR => $UPGDRCost,
                        self::USPSPRI => $USPSPRICost,
                    );
                }
                elseif($shipRates['USPSPRI']['cost'] != 0 && $shipRates['PS1CS']['cost'] != 0)
                {
                    $USPSPRICost = $shipRates['USPSPRI']['cost'] - $this->drawback;
                    $PS1CSCost = $shipRates['PS1CS']['cost'] - $this->drawback;
                    $shipOptions = array(
                        self::PS1CS   => $PS1CSCost,
                        self::UPGDR   => $UPGDRCost,
                        self::USPSPRI => $USPSPRICost,
                    );
                }
            }
            return $shipOptions;
        }
        else
            return array();
    }

    protected function _getShipRates($params)
    {
        $params = array_merge(self::$_params, $params);
        $sr = new ShippingRate($params);
        if(!$sr->hasError())
            return $sr->getRates();
        else
            Log::info('Shipping error:' . $sr->getErrors());
    }
}