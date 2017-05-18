<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/23
 * Time: 14:06
 */

namespace App\EbayApi\lib;


use DTS\eBaySDK\Trading\Enums\ReturnsAcceptedOptionsCodeType;

final class StringUtil
{
    /**
     * Covert the product's title to ebay's title.
     *
     * @param String $_strTitle Title of Product;
     * @param integer $_intLength Length of Ebay title.
     * @return String
     */
    public static function covertToEbayApiTitle( $_strTitle , $_intLength = 55  )
    {
        //Replace title string correspondence.
        $aryWord = array(
            "pci-express"=>"pci-e",
            '3.5 inch' => '3.5"',
            'Hard Drive Enclosure'=>'HD Enclosure',
            'Drive Enclosure' => 'HD Enclosure',
        );
        if( strlen( $_strTitle ) > $_intLength )
        {
            //Replace title string.
            foreach( $aryWord as $k=>$v )
            {
                $reg = " ".$k." ";
                $getStr = " ".$v." ";
                $_strTitle = str_ireplace( $reg , $getStr , $_strTitle );
            }
            //Back to specified length.
            $_strTitle = strrev( strstr( strrev( substr( $_strTitle , 0 , $_intLength) ) , ' ' ) );
        }
        return $_strTitle;
    }

    /**
     * @param $shipType
     * @return string shipType
     */
    public static function convertShipType($shipType)
    {
        switch($shipType){
            case 'USPS Priority Mail':
                $shipType='USPSPriority';
                break;
            case 'UPS Ground':
                $shipType='UPSGround';
                break;
            case 'USPS First Class':
                $shipType='USPSFirstClass';
                break;
            default:
                $shipType=$shipType;
        }
        return $shipType;
    }

    /**
     * Get partNum from vict
     * @param string $content
     * @return string part
     */
    public static function GetPartByDeEncrypt($content)
    {
        $result = '';
        if(preg_match("/<input[^<]*vict[^>]*>/i",$content, $matches)){
            if(preg_match("/value *= *[\"']?([a-zA-Z0-9]*)[\"']?/i",$matches[0], $matches2)){
                $result = $matches2[1];
            }
        }

        $part = 'noPart';
        if($result)
        {
            $str = Encryption::McryptDecrypt(trim($result));
            $tempArr = array();
            $tempArr = explode(chr(233),$str);
            $part = $tempArr[0];
        }
        return $part;
    }

    /**
     * @param $type
     * @return string type(Y or N)
     */
    public static function convertReturnType($type)
    {
        return $type == ReturnsAcceptedOptionsCodeType::C_RETURNS_ACCEPTED ? 'Y' : 'N';
    }
    /**
     * convert item's description
     * @param $desc
     * @return string
     */
    public static function convertDescription($desc){
        if(preg_match("/<!-- Productinfo Begin -->(.*)<!-- Productinfo End -->/is",$desc,$match)){
            $desc = $match[0];
        }elseif(preg_match("|<div class=\"productinfo\">(<div.*?>.*?<\/div>)*?.*?<\/div>|is",$desc,$match)){
            $desc = $match[0];
        }

        return $desc;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function clearItemTag($str)
    {
        return trim( strip_tags( preg_replace( '/<font.*font>/i' , "" , $str ) ) );
    }

    /**
     * @param string $_strText
     * @return mixed
     */
    public static function filterIllegalStr($_strText = "" )
    {
        return preg_replace( '/[\x00-\x08\x0b-\x0c\x0e-\x1f]/' , '' , $_strText );
    }

    /**
     * usage: check the item is ipad accessories or not
     * @param String $title
     * @return int $catId
     */
    public static function isIpadAcc($title)
    {
        $keyword1 = 'for ipad';
        $keyword2 = 'AA Batteries';
        $isHaveK1 = strpos($title, $keyword1);
        $isHaveK2 = strpos($title, $keyword2);
        $catId = 1;
        if($isHaveK1 || $isHaveK2){
            $catId= 48680;
        }
        return $catId;
    }
    public static function array2String($array){
        ob_start();
        ob_clean();
        print_r((array)$array);
        $str = ob_get_contents();
        ob_clean();
        ob_end_clean();
        $ret = '';
        foreach(explode("\n", $str) as $v){
            $ret .= trim($v). " ";
        }
        return $ret;
    }

}