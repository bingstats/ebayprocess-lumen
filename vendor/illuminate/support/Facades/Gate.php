<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Contracts\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Illuminate\Contracts\Auth\Access\Gate';
    }
}
