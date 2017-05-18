<?php
/**
 * Created by PHPStorm.
 * User: Chain.Wu
 * Date: 1/22/2017
 * Time: 8:22 AM
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\GetItemTransactions;

class SbzEbayApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerAPIs();
    }

    public function registerAPIs()
    {
        $this->app->singleton('command.ebay.api',function(){
            return new  GetItemTransactions;
        });
        $this->commands('command.ebay.api');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.ebay.api'];
    }
}