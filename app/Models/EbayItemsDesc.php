<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/15
 * Time: 9:56
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
class EbayItemsDesc extends Eloquent
{
    protected $connection = 'mysql2'; //this will use the specified database connection
    protected $table = 'ebay_items_desc';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'part',
        'price',
        'title',
        'ebay_cate_id',
        'ebay_store_cate_id',
        'ewiz_cate_id',
        'preview_pic',
        'quantity',
        'handling_time',
        'return_accept',
        'mtime',
        'shipping1',
        'shipping2',
        'shippng3',
    ];
    public $ex = array();
    public static $ewizCateIdNotUpdate = array(582,585,586,588,590,596,597,
        602,607,608,609,610,612,613,614,621,627,628,629,673,719
    );
    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        self::saving(function($model){

        });
    }

    public function save(array $options = [])
    {
        if ((!empty($this->ex) && $descId = $this->primaryKey)){
            $mdl_ex = new EbayItemsDescEx();
            $obj = $mdl_ex::find($descId);
            if($obj){
                $mdl_ex = $obj;
            }else{
                $mdl_ex->ebay_items_desc_id =$descId;
            }
            foreach ($this->ex as $key=>$val) {
                $mdl_ex->$key = $val;
            }
            $mdl_ex->save();
        }
        parent::save($options); // TODO: Change the autogenerated stub
    }

    public  function getEbayCateId($itemid)
    {
        $this->setConnection($this->connection);
        $res = $this::where('itemid',$itemid)->select('ebay_cate_id')->first();
        if($res) {
            return $res->ebay_cate_id;
        }
        else {
            return '';
        }

    }
    public function fetchUpdateItems($storeId)
    {
        $status = EbayStoreItems::STATUS_ACTIVE;
        return \DB::select("select s.itemid,i.id,i.part,i.price as old_price 
                   ,p.ITEM as title
                   ,p.DESCRIPT as `desc`
                   ,p.WEIGHT as weight
                   ,p.MAKER as maker
                   ,p.balance as quantity 
                   ,p.arrival
                   ,p.mfn
                   ,p.PRICE as price
                   ,p.COST as cost
                   ,p.SOX as sox
                   ,p.minprice
                   ,pc.catid
                   ,pc.category  
                from ebay.ebay_store_items s
                    ,ebay.ebay_items_desc i 
                    ,ebay.ebay_items_desc_ex e
                    ,ewiz90.product_list p
                    ,ewiz90.prod_category pc 
                where s.desc_id = i.id 
                  and e.ebay_items_desc_id = i.id 
                  and i.part = p.part 
                  and pc.catid = REPLACE(substring_index(p.component,':',2),':','') 
                  and s.status ='$status'
                  and s.store_id = '$storeId' 
                  and s.type= 'auto' 
                  and s.add_type='auto' 
                  and i.ewiz_cate_id not in(".implode(',', self::$ewizCateIdNotUpdate).") 
                  and (p.balance > 0 or (p.arrival <>-99 and p.arrival<>-10))
                  and (p.balance <> 999 or (p.arrival<>-99)) 
                  and (p.arrival <> -33)
                  and p.item not like '%plantronics%' 
                  and (
                    abs(e.ewiz_price-p.price) > 0.001 
                    or i.ewiz_cate_id <> pc.catid
                    or (e.ewiz_sox<e.ewiz_cost and abs(e.ewiz_sox-p.sox) > 0.001)
                    or (e.ewiz_sox>e.ewiz_cost and abs(e.ewiz_cost-p.cost) > 0.001)
                    or (e.ewiz_sox=e.ewiz_cost and (abs(e.ewiz_cost-p.cost) > 0.001 or abs(e.ewiz_sox-p.sox) > 0.001))
                    )");
    }
    public function fetchFilterItems($storeId)
    {
        $status = EbayStoreItems::STATUS_ACTIVE;
        $res = \DB::connection($this->connection)->table('ebay_store_items as s')
            ->join('ebay_items_desc as i','s.desc_id','=','i.id')
            ->join('ewiz90.prod_category as pc','i.ewiz_cate_id','=','pc.catid')
            ->join('ewiz90.product_list as p','i.part','=','p.part')
            ->select('s.type','s.add_type','s.itemid','i.part','i.sold_qty','pc.category')
            ->where([
                ['s.status',$status],
                ['s.store_id',$storeId],
            ])
            ->whereNotIn('i.ewiz_cate_id',self::$ewizCateIdNotUpdate)
            ->where(function ($query){
               $query->where('p.balance','<=',0)->where(function($q){
                   $q->where('p.arrival','=','-99')->orWhere('p.arrival','=','-10');
               });
               $query->orWhere([['p.balance','=',999],['p.arrival','=','-99']]);
               $query->orWhere('p.arrival','=','-33');
               $query->orWhere('p.item','like','%plantronics%');
            })->get();
        return $res;
    }
    public function fetchNotExistItems($storeId)
    {
        $status = EbayStoreItems::STATUS_ACTIVE;
        $part = ProductList::pluck('PART')->toArray();
        $res = \DB::connection($this->connection)->table('ebay_store_items as s')
            ->join('ebay_items_desc as i','s.desc_id','=','i.id')
            ->join('ewiz90.prod_category as pc','i.ewiz_cate_id','=','pc.catid')
            ->select('s.type','s.itemid','i.part')
            ->where([
                ['s.status',$status],
                ['s.store_id',$storeId],
            ])
            ->whereNotIn('i.ewiz_cate_id',self::$ewizCateIdNotUpdate)
            ->whereNotIn('i.part',$part)->get();
        return $res;

    }
    public function scopeFetchTypeItems($query,$type,$storeId)
    {
        return $query->whereHas('item',function($q) use ($type,$storeId){
            $q->ofType($type)->active()->where('store_id',$storeId);
        })->CateId()->groupBy('part')->havingRaw('count(part)>1')->select('part')->get();
    }
    public function scopeCateId($query)
    {
        return $query->whereNotIn('ewiz_cate_id',self::$ewizCateIdNotUpdate);
    }

    public function item()
    {
        return $this->hasOne('App\Models\EbayStoreItems','desc_id','id');
    }
    public function extra()
    {
        return $this->hasOne('App\Models\EbayItemsDescEx','ebay_items_desc_id','id');
    }

}