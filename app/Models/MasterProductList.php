<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/9
 * Time: 11:25
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class MasterProductList extends Eloquent
{
    protected $table = 'master_product_list';
    protected $primaryKey = 'PART';
    protected $fillable = [];
}