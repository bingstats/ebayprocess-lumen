<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Contracts\Routing\ResponseFactory
 */
class Response extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Illuminate\Contracts\Routing\ResponseFactory';
    }
}
