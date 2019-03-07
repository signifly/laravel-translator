<?php

namespace Signifly\Translator\Exceptions;

use Exception;
use Signifly\Translator\Models\Translation;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className)
    {
        return new static("The given model class `$className` does not extend `".Translation::class.'`');
    }
}
