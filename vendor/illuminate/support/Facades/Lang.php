<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Translation\Translator
 */
class Lang extends Facade
{
    /**
     * Get the registered name of the Component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'translator';
    }
}
