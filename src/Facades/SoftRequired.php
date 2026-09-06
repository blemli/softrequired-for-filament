<?php

namespace Blemli\SoftRequired\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Blemli\SoftRequired\SoftRequired
 */
class SoftRequired extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Blemli\SoftRequired\SoftRequired::class;
    }
}
