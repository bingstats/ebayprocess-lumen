<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/9
 * Time: 14:43
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class UserAccount extends Eloquent
{
    protected $table = 'user_account';
    protected $primaryKey = 'uid';
    public $timestamps = false;

    protected $fillable = [
        'uid',
        'user',
        'passwd_new',
        'BFName',
        'BLName',
        'BCompany',
        'BAddrOne',
        'BAddrTwo',
        'BCity',
        'BState',
        'BZip',
        'BCountry',
        'BPhone',
        'SFName',
        'SLName',
        'SCompany',
        'SAddrOne',
        'SAddrTwo',
        'SCity',
        'SState',
        'SZip',
        'SCountry' ,
        'sBusiName',
        'regip',
        'SPhone',
        'businessAddr',
        'AddrOnFile',
        'CCrdPhone',
        'referral' ,
        'referralid',
        'acctype',
        'newsletter',
        'datetime',
        'mtime',
    ];
}