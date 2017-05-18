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
		  #执行更新操作  
		  break;

        }






    }
}
