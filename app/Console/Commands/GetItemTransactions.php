<?php
/**
 * Created by PHPStorm.
 * User: Chain.Wu
 * Date: 12/23/2016
 * Time: 3:06 PM
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mockery\CountValidator\Exception;
use App\EbayApi\EbayApi;

class GetItemTransactions extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'EbayApi:getItemTransactions {storeName}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Get Item transations Information';

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
	$storeName = $this->argument('storeName');
        $storeName = ucfirst($storeName);
	$storeName = '\App\EbayApi\Stores'.'\\'.$storeName;
	$store = new $storeName;
        $params['DetailLevel'] = ['ReturnAll'];
        $params['ItemID']  = 'xxxxxxxx';
        $params['TransactionID']  = 'xxxxxxx';
        //$params['NumberOfDays']  = 20;
        $res = EbayApi::getInstance($store)->getItemTransactions($params);
        if($res){
            var_dump($res);
        }
    }
}
