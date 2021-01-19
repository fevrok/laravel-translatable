<?php

namespace LaravelArab\Tarjama\Facades;

use Illuminate\Support\Facades\Facade;

class Tarjama extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tarjama';
    }
}
