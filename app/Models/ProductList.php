<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/9
 * Time: 10:56
 */
namespace App\Models;

use App\EbayApi\Lib\StringUtil;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ProductList extends Eloquent
{
    protected $table = 'product_list';
    protected $primaryKey = 'part';
    protected $fillable = [];
    private static $_getInstance;

    /**
     * return a instance of obj
     */
    public static function getInstance()
    {

        if(isset(self::$_getInstance) && self::$_getInstance instanceof ProductList)
           return self::$_getInstance;
       return  self::$_getInstance = new ProductList;
    }
    public function getAvaliableRowByPart($part, $isClearTag=true)
    {
        //ini_set('memory_limit', '-1');
       $row = \DB::select("SELECT
                    pl.PART as part
                   ,pl.ITEM as title
                   ,pl.DESCRIPT as `desc`
                   ,pl.WEIGHT as weight
                   ,pl.MAKER as maker
                   ,pl.balance as quantity 
                   ,pl.arrival
                   ,pl.mfn
                   ,pl.barcode as upc
                   ,pl.PRICE as price
                   ,pl.COST as cost
                   ,pl.SOX as sox
                   ,pl.minprice
                   ,pc.catid
                   ,pc.category 
                   ,pc.ids 
                FROM product_list pl 
                LEFT JOIN prod_category pc ON pc.catid = REPLACE(substring_index(pl.component,':',2),':','') 
                WHERE pl.PART = '{$part}' 
                AND pl.arrival != -33 
                AND pl.arrival != -6");
        if(count($row) > 0 && $isClearTag){
            $row->title = StringUtil::clearItemTag($row->title);
        }
        return $row;
    }
    public function getUspsFc($part)
    {
        $rs = \DB::table('price_control')->select('usps_first_class')->where('part',$part)->first();
        if(count($rs) > 0){
            return $rs->usps_first_class;
        }else{
            return '';
        }
    }
    public function getAvaliableParts($storeId)
    {
        $arrPart = array();
        if($storeId == 1 || $storeId == 2 || $storeId == 5){
            $strSql = "SELECT pl.PART FROM ".$this->table." pl LEFT JOIN prod_category pc ON pc.catid = REPLACE(
                substring_index(pl.component,':',2),':','')
                where(pl.balance != 999 AND pl.arrival = 0)
                or (pl.arrival != -6 and pl.arrival != -33 and pl.balance >0) 
                or (pl.arrival > 0 and pl.balance<=0)";
        }elseif ($storeId == 3 || $storeId == 4 || $storeId == 6){
            $strSql = "SELECT pl.PART FROM ".$this->table." pl LEFT JOIN prod_category pc ON pc.catid = REPLACE(
                substring_index(pl.component,':',2),':','')
                where((pl.balance != 999 AND pl.arrival = 0)
                or (pl.arrival != -6 and pl.arrival != -33 and pl.balance >0) 
                or (pl.arrival > 0 and pl.balance<=0)) AND (pc.ids LIKE '%:150:%' OR pc.ids LIKE '%:201:%')";
        }elseif ($storeId == 7){
            $strSql = "SELECT pl.PART 
                       FROM " . $this->table . " pl 
                       WHERE ((pl.balance != 999 AND pl.arrival = 0) 
                        or (pl.arrival != -6 and pl.arrival != -33 and pl.balance >0) 
                        or (pl.arrival > 0 and pl.balance<=0)
                        ) 
                       AND pl.maker='imicro'";
        }else{
            $strSql = "SELECT pl.PART 
                       FROM " . $this->table . " pl 
                       WHERE ((pl.`balance` != 999 AND pl.arrival = 0) 
                        or (pl.arrival != -6 and pl.arrival != -33 and pl.balance >0) 
                        or (pl.arrival > 0 and pl.balance<=0)
                        ) 
                       AND pl.maker='imicro' limit 15";
        }
        $data = \DB::select($strSql);
        foreach ($data as $item) {
            $arrPart[] = strtoupper(trim($item->PART));
        }
        return $arrPart;
    }
    public function getProductListNewPart()
    {
        $arrPart =array();
        $data = \DB::table('product_list_new')->select('PART')->get();
        foreach ($data as $item) {
            $arrPart[] = strtoupper(trim($item->PART));
        }
        return $arrPart;
    }
}
