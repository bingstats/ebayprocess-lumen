<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\View\Factory
 */
class View extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'view';
    }
}
