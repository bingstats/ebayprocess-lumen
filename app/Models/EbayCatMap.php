<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 10:57
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
class EbayCatMap extends Eloquent
{
    protected $table = 'ebay_cat_map';
    protected $primaryKey = 'id';
    protected $connection = 'mysql2';
}