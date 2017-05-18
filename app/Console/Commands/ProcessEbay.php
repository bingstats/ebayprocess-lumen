<?php
/**
 * Created by PHPStorm.
 * User: Chain.Wu
 * Date: 12/23/2016
 * Time: 3:06 PM
 */
namespace App\Console\Commands;

use App\EbayApi\EbayItem;
use App\Models\EbayCategory;
use App\Models\EbayItemsDesc;
use App\Models\EbayStoreItems;
use App\Models\ProductList;
use App\selfLog\Log;
use Illuminate\Console\Command;
use App\EbayApi\EbayOrderProcess;

class ProcessEbay extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'ebay:process {--type=processOrder}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'process orders from ebay or update items from stores';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type');

        switch ($type){
            case 'processOrder':
                $ebayOrderProcess = new EbayOrderProcess;
                $ebayOrderProcess->index();
                break;
            case 'ebayUpdate':
                //dd(EbayStoreItems::getInstance()->getStoreActiveParts(7));
                try{$id = '923755';
                //$mol = new EbayItemsDesc();
                $model = EbayItemsDesc::find($id);
                $time = date('Y-m-d H:i:s');
                $model->part = 'KB-K750';
                $model->price = '62.59';
                $model->title = 'Logitech K750 Wireless Solar Keyboard';
                $model->ebay_cate_id = 33963;
                if(isset($this->params['storeFront']))
                    $model->ebay_store_cate_id = $this->params['storeFront'];
                $model->ewiz_cate_id = 152;
                if(isset($this->params['pictureDetails']))
                {
                    if(is_array($this->params['pictureDetails']))
                        $model->preview_pic = $this->params['pictureDetails'][0];
                    else
                        $model->preview_pic = 'http://i.ebayimg.com/00/s/NjQwWDY0MA==/z/IBUAAOSw-W5UrKIF/$_1.JPG?set_id=880000500F';
                }
                $model->quantity = 3;
                $model->combo = 'N';
                $model->handling_time = 1;
                $model->return_accept = 'Y';
                $model->mtime = $time;
                $model->ex = array(
                    'desc' => '<!-- Productinfo Begin --><div class="productinfo">   <!-- Top portion, product description -->   <table width="100%" border="0" cellpadding="0" cellspacing="0" class="ke-zeroborder">    <tbody><tr><td align="center" width="100%" height="180" colspan="2">     <!-- Title -->     <br /><span style="font-size:medium;font-family:Arial;"><b><span class="tag">Logitech K750 Wireless Solar Keyboard </span></b><br />     <br /><br /></span></td></tr>                <tr><td align="center">          <!-- Main Product Image -->     <img src="http://img1.sbzimg.com/newg/K/B/-/KB-K750/KB-K750.JPG" alt="Logitech K750 Wireless Solar Keyboard" />    </td></tr>    <tr><td align="center" colspan="2">     <img src="http://img1.sbzimg.com/images/ebayfront/divider.jpg" />    </td></tr>    <!-- Description -->        <tr><td class="td-content">    <b>Product Condition: New</b>     </td></tr>    <tr><td class="td-content"><br /><p><b>Specifications</b></p><ul><li><strong>Mfr Part Number:</strong> 920-002912</li><li><strong>Features:</strong><ul><li>Only 1/3 -inch thick</li><li>Plug-and-play simplicity<br /></li><li>Logitech Solar App</li><li>Logitech Unifying receiver</li><li>Advanced 2.4 GHz wireless</li><li>Small steps, bright future</li></ul></li><li><strong>System Requirements:</strong><ul><li>Windows XP, Windows Vista or Windows 7</li></ul><ul><li>Light source from sunlight and/or indoor lighting</li></ul></li><li><strong><strong>Package Contents:</strong> </strong><ul><li>Keyboard</li><li>Logitech Unifying receiver</li><li>Wireless extender</li><li>Cleaning cloth</li></ul></li></ul><br /></td></tr><tr><td align="center" colspan="2"><img src="http://img1.sbzimg.com/images/ebayfront/divider.jpg" /></td></tr>       <tr><td align="center" width="100%" height="180" colspan="2"><!-- Additional Product Images -->      <br /><img src="http://img3.sbzimg.com/newg/K/B/-/KB-K750/KB-K750.1_LG.JPG" /><br />        <img src="http://img1.sbzimg.com/newg/K/B/-/KB-K750/KB-K750.2_LG.JPG" /><br />        </td></tr>   </tbody></table>   </div><!-- Productinfo End -->',
                    'ewiz_price' => '50.99',
                    'ewiz_cost'  => '50',
                    'ewiz_sox'  => '55',
                    'insurance' => 0,
                    'tax' => '6.61',
                    'final_fee' => 7.41,
                    'paypal_fee' => 1.82,
                    'profit' => 2.62,
                    'mtime' => $time,
                );
                $model->shipping1 = '0:UPSGround:0:0:0:18';
                $model->shipping2 = '';
                $model->shipping3 = '';
                if($model->save()){
                    Log::info('Success to update item in ebay_items_desc,itemId-->201258820307');

                    //return true;
                }else{
                    Log::info('Fail to update item in ebay_items_desc,itemId-->201258820307');
                    //return false;
                }}catch (\Exception $e){
                    echo $e->getMessage();
                }
                //$ebayItem = new EbayItem('imicros','201258820307','KB-K750');
                //$ebayItem->updateItemInLocal('923755');
                break;

        }






    }
}