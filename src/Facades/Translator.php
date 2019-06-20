<?php

namespace Signifly\Translator\Facades;

use Illuminate\Support\Facades\Facade;
use Signifly\Translator\Contracts\Translator as TranslatorContract;

class Translator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return TranslatorContract::class;
    }
}
