<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/28
 * Time: 10:42
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ProdCategory extends Eloquent
{
    protected $table = 'prod_category';
    protected $primaryKey = 'catid';
    public $timestamps = false;
    protected $fillable = [];


}