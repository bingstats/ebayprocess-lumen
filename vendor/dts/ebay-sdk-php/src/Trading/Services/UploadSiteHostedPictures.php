<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 14:37
 */

namespace DTS\eBaySDK\Trading\Services;


use DTS\eBaySDK\Trading\Types\UploadSiteHostedPicturesRequestType;

class UploadSiteHostedPictures
{
    protected static $_convertObject;
    private static function setPictureName($pictureName)
    {
        self::$_convertObject->PictureName = $pictureName;
    }

    private static function setExternalPictureURL($url)
    {
        self::$_convertObject->ExternalPictureURL = $url;
    }

    private static function setPictureData($pictureData)
    {
        self::$_convertObject->PictureData = $pictureData;
    }

    private static function setPictureSet($pictureSet)
    {
        self::$_convertObject->PictureSet = $pictureSet;
    }

    public static function convert($objectArray)
    {
        self::$_convertObject = new UploadSiteHostedPicturesRequestType();
        foreach($objectArray as $k=>$v){
            $fun = 'set'.ucfirst($k);
            if(method_exists('UploadSiteHostedPictures',$fun)){
                self::$fun($v);
            }
        }
        return self::$_convertObject;

    }
}