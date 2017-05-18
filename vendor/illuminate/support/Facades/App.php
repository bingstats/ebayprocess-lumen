<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Foundation\Application
 */
class App extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'app';
    }
}
