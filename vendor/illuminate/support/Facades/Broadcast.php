<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Contracts\Broadcasting\Factory
 */
class Broadcast extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Illuminate\Contracts\Broadcasting\Factory';
    }
}
