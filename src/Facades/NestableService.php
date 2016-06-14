<?php

namespace Nestable\Facades;

use Illuminate\Support\Facades\Facade;

class NestableService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'nestableservice';
    }
}
