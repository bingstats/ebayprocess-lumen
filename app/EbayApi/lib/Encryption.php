<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/23
 * Time: 14:47
 */

namespace App\EbayApi\Lib;


class Encryption
{
    /**Key of Encryption and decryption.*/
    //since php 5.6,mcrypt_decrypt(): Key of size 26 not supported by this algorithm. Only keys of sizes 16, 24 or 32 supported.
    const ENCRYPTION_KEY = "Str Key for 9w|z.C03 ebay\0\0\0\0\0\0\0";
    /**
     * Encrypted string
     * @param String $_strText
     * @param String $_strKey If it is null,the value is self::ENCRYPTION_KEY
     * @return String
     */
    public static function McryptEncrypt( $_strText = "" , $_strKey = null )
    {
        $iv = self::hex2bin( '76ce2539678e53a7c105fc71596bc9411ec9eca07397874ff12b5520d42bca3d' );
        $key = is_null( $_strKey ) ? self::ENCRYPTION_KEY : $_strKey;
        $text = $_strText;
        return bin2hex( mcrypt_encrypt( MCRYPT_RIJNDAEL_256 , $key , $text , MCRYPT_MODE_ECB , $iv ) );
    }

    /**
     * Decrypted string
     *
     * @param String $_strText
     * @param String $_strKey If it is null,the value is self::ENCRYPTION_KEY
     * @return String
     */
    public static function McryptDecrypt( $_strText = "" , $_strKey = null )
    {
        $iv = self::hex2bin( '76ce2539678e53a7c105fc71596bc9411ec9eca07397874ff12b5520d42bca3d' );
        $text = trim( $_strText );
        $key = is_null( $_strKey ) ? self::ENCRYPTION_KEY : $_strKey;
        return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256 , $key , self::hex2bin( $text ) , MCRYPT_MODE_ECB , $iv ) );
    }

    /**
     * hex to bin
     *
     * @param String $_strText
     * @return String
     */
    private static  function hex2bin( $_strText )
    {
        $strRes = "";
        for( $i=0; $i<strlen($_strText)/2; $i++ )
        {
            $strRes .= chr( base_convert( substr( $_strText , $i*2 , 2 ) , 16 , 10 ) );
        }
        return $strRes;
    }
}