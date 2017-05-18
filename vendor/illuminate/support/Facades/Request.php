<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Http\Request
 */
class Request extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'request';
    }
}
