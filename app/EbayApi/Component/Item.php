<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 16:21
 */

namespace App\EbayApi\Component;


class Item
{
    static $token = '';

    /**
     * @param $itemid
     * @return array|bool
     */
    public static function WebInfor($itemid)
    {
        $url = 'www.comxxxxxx'.$itemid;
        try{
            $data = self::SBcurl($url);
            $vict       = self::getVict($data);
            $devict     = self::descrypt($vict);
            $dearray    = explode(chr(233),$devict);

            if(is_array($dearray) && (count($dearray) == 8))
            {
                $return = array();
                foreach(array('part','price','shipping_method','shipfee',6 => 'cost','weight') as $k => $v)
                {
                    $return[$v] = $dearray[$k];
                }
                return $return;
            }else{
                throw new Exception('descrypt vict fail');
            }
        }
        catch(Exception $e)
        {
            //print error message
            echo $e->getMessage();
            return false;
        }
    }
    static public function SBcurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: text/html;charset=utf-8',));
        curl_setopt($ch,CURLOPT_USERAGENT,'getWebInfo;1.0');
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, 0 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 120);
        curl_setopt( $ch, CURLOPT_FAILONERROR, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_HTTP_VERSION, 1 );
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;

    }
    public static function getVict($data)
    {
        $result = false;
        if(preg_match("/<input[^<]*vict[^>]*>/i",$data, $matches))
        {
            if(preg_match("/value *= *[\"']?([a-zA-Z0-9]*)[\"']?/i",$matches[0], $matches2))
            {
                $result = $matches2[1];
            }
        }

        return $result;
    }

    /**
     * @param $itemid
     * @param $txn_id
     * @return array|bool
     */
    public static function ApiInfor($itemid, $txn_id)
    {
        $message = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

        $ep = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: text/xml;charset=utf-8','SOAPAction: dummy'));
        curl_setopt($ch,CURLOPT_USERAGENT,'ebatns;1.0');
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $message );
        curl_setopt( $ch, CURLOPT_URL, $ep );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_FAILONERROR, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_HTTP_VERSION, 1 );

        $res = curl_exec($ch);
        if(curl_errno($ch) > 0){
            Log::error(curl_error($ch));
            return false;
        }else{
            $x = self::xml2array($res);
            if(!empty($x['soapenv:Envelope']) && !empty($x['soapenv:Envelope']['soapenv:Body']) && !empty($x['soapenv:Envelope']['soapenv:Body']['GetItemTransactionsResponse']) && !empty($x['soapenv:Envelope']['soapenv:Body']['GetItemTransactionsResponse']['TransactionArray'])){
                $xTransAry = $x['soapenv:Envelope']['soapenv:Body']['GetItemTransactionsResponse']['TransactionArray'];
                if(empty($xTransAry)){
                    return false;
                }else{
                    return array(
                        'price'     => $xTransAry['Transaction']['AmountPaid'],
                        'listprice' => $xTransAry['Transaction']['TransactionPrice'],
                        'userid'    => $xTransAry['Transaction']['Buyer']['UserID'],
                        'shipping_method'    => $xTransAry['Transaction']['ShippingServiceSelected']['ShippingService'],
                    );

                }
            }else{
                return false;
            }
        }

    }
    public static function descrypt($data)
    {
        $data   = trim($data);
        $iv     = self::hex2bin('xxxxxxxxxxxxxxxxxxxxxxxx');
        //since php 5.6,mcrypt_decrypt(): Key of size 26 not supported by this algorithm. Only keys of sizes 16, 24 or 32 supported.
        $key    = "Str Key for 9w|z.C03 ebay\0\0\0\0\0\0\0";

        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, self::hex2bin($data), MCRYPT_MODE_ECB, $iv));
    }
    public static function hex2bin($myin) {
        $myout="";
        for ($i=0; $i<strlen($myin)/2; $i++) {
            $myout.=chr(base_convert(substr($myin,$i*2,2),16,10));
        }
        return $myout;
    }
    public static function geteBayTxnidInOwenAPI($itemid,$pp_txn_id,$store)
    {
        $params['itemID']  = $itemid;
        $params['NumberOfDays']  = 20;
        //$params['TransactionID']  = $pp_txn_id;
        $params['DetailLevel'] = ['ReturnAll'];
        $store = ucfirst($store);
        $store = '\\App\EbayApi\Stores\\'.$store;
        $store = new $store;
        //$store = new Stores\EbayStore($store);
        $res = self::getInstance($store)->getItemTransactions($params);
        if(!self::getInstance($store)->checkResponse($res)){
            return false;
        }else{
            if(($trans = $res->TransactionArray->Transaction) != false){
                if(count($trans) > 0){
                    foreach($trans as $tran){
                        if(!empty($tran->ExternalTransaction)){
                            if(count($tran->ExternalTransaction) > 0){
                                foreach($tran->ExternalTransaction as $extt){
                                    if($pp_txn_id == $extt->ExternalTransactionID){
                                        $orderid = $tran->OrderLineItemID;
                                        $feedbackScore = !empty($tran->Buyer->FeedbackScore) ? $tran->Buyer->FeedbackScore : '';
                                        $result['orderid'] = substr(strstr($orderid,'-'),1);
                                        $result['feedbackScore'] = $feedbackScore;
                                        $result['multiLegShip'] = self::processGSP($tran);
                                        return $result;
                                    }
                                }
                            }
                        }
                    }
                }

            }
            Log::info("ExternalTransactionID can not find pp_txn_id");
            return false;
        }
    }

    /**
     * @param $itemid
     * @return bool
     */
    static function getItem($itemid)
    {
        $message = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

        $ep = 'https://api.ebay.com/wsapi?xxxxxxxxxxxxxxxxxxxxxx';

        //echo $message."\n";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: text/xml;charset=utf-8','SOAPAction: dummy'));
        curl_setopt($ch,CURLOPT_USERAGENT,'ebatns;1.0');
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $message );
        curl_setopt( $ch, CURLOPT_URL, $ep );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_FAILONERROR, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_HTTP_VERSION, 1 );

        $res = curl_exec($ch);

        if(curl_errno($ch) > 0){
            self::log(curl_error($ch));
            echo curl_error($ch);
            return false;
        }else{
            /*
            var_dump($res);
            $vals = $index = array();
            $xmlp = xml_parser_create();
            var_dump(xml_parse_into_struct($xmlp,$res,$vals,$index));
            print_r($index);
            */
            $x = self::xml2array($res);
            #return $x;
            if(!empty($x['soapenv:Envelope']) && !empty($x['soapenv:Envelope']['soapenv:Body']) && !empty($x['soapenv:Envelope']['soapenv:Body']['GetItemResponse']) ){
                $itemAry = $x['soapenv:Envelope']['soapenv:Body']['GetItemResponse'];
                if(empty($itemAry)){
                    return false;
                }else{
                    return $itemAry;
                }

                return false;
            }else{
                return false;
            }
        }

    }
    static public function processGSP($tran)
    {
        $rs = array();
        $isMultiLegShipping = !empty($tran->IsMultiLegShipping) ? $tran->IsMultiLegShipping : false;
        if(!empty($isMultiLegShipping)){
            $rs['GSP_referenceID'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->ReferenceID;
            $rs['GSP_name'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->Name;
            $rs['GSP_street1'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->Street1;
            $rs['GSP_street2'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->Street2;
            $rs['GSP_stateOrProvince'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->StateOrProvince;
            $rs['GSP_cityName'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->CityName;
            $rs['GSP_country'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->Country;
            $rs['GSP_postalCode'] = $tran->MultiLegShippingDetails->SellerShipmentToLogisticsProvider->ShipToAddress->PostalCode;
        }
        return $rs;

    }

    public static function xml2array($contents, $get_attributes=1, $priority = 'tag') {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if(!$xml_values) return;//Hmm...

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference

        //Go through the tags.
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if(isset($value)) {
                if($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if(isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;

                    $current = &$current[$tag];

                } else { //There was another element with the same tag name

                    if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }

                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                    $current = &$current[$tag][$last_item_index];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

                } else { //If taken, put all things inside a list(array)
                    if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;

                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if($priority == 'tag' and $get_attributes) {
                            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }

                            if($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    }
}
