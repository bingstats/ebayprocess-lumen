<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/11
 * Time: 15:26
 */
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/4
 * Time: 13:57
 */
/**
 * Configuration settings used by all of the examples.
 *
 * Specify your eBay application keys in the appropriate places.
 *
 * Be careful not to commit this file into an SCM repository.
 * You risk exposing your eBay application keys to more people than intended.
 *
 * For more information about the configuration, see:
 * http://devbay.net/sdk/guides/sample-project/
 */
return [
    'sandbox' => [
        'credentials' => [
            'devId' => 'YOUR_SANDBOX_DEVID_APPLICATION_KEY',
            'appId' => 'YOUR_SANDBOX_APPID_APPLICATION_KEY',
            'certId' => 'YOUR_SANDBOX_CERTID_APPLICATION_KEY',
        ],
        'authToken' => 'YOUR_SANDBOX_USER_TOKEN_APPLICATION_KEY'
    ],
    return [
    'sandbox' => [
        'credentials' => [
            'devId' => 'YOUR_SANDBOX_DEVID_APPLICATION_KEY',
            'appId' => 'YOUR_SANDBOX_APPID_APPLICATION_KEY',
            'certId' => 'YOUR_SANDBOX_CERTID_APPLICATION_KEY',
        ],
        'authToken' => 'YOUR_SANDBOX_USER_TOKEN_APPLICATION_KEY'
    ],
    'production' => [
        'credentials' => [
           'devId' => 'YOUR_SANDBOX_DEVID_APPLICATION_KEY',
            'appId' => 'YOUR_SANDBOX_APPID_APPLICATION_KEY',
            'certId' => 'YOUR_SANDBOX_CERTID_APPLICATION_KEY',
        ],
        'authToken' => 'YOUR_SANDBOX_CERTID_Token_KEY',
        
    ]
];
?>