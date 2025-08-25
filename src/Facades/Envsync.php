<?php

namespace Blemli\Envsync\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Blemli\Envsync\Envsync
 */
class Envsync extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Blemli\Envsync\Envsync::class;
    }
}
