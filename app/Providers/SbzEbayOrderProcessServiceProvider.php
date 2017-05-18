<?php
/**
 * Created by PHPStorm.
 * User: Chain.Wu
 * Date: 1/22/2017
 * Time: 8:22 AM
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\ProcessEbay;

class SbzEbayOrderProcessServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('command.ebayOrder.process',function(){
            return new  ProcessEbay;
        });
        $this->commands('command.ebayOrder.process');
    }
    

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.ebayOrder.process'];
    }
}