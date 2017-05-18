<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/30
 * Time: 15:44
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EwizCatTree extends Eloquent
{
    protected $table = 'prod_category';
    protected $primaryKey = 'catid';
    protected $fillable = [];
    private static $_getInstance;

    /**
     * return a instance of obj
     */
    public static function getInstance()
    {
        if(isset(self::$_getInstance) && self::$_getInstance instanceof EwizCatTree)
            return self::$_getInstance;
        return  self::$_getInstance = new EwizCatTree;
    }
    public function getEwizCateId()
    {
        return ProdCategory::select('catid','parentid')->orderBy('level')->get();
    }
    public function getEwizPartdetail($part)
    {
        $product = ProductList::find($part);
        if(count($product) == 0)
            return '';
        else{
            return $product->toArray();
        }
    }
}