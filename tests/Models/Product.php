<?php

namespace Signifly\Translator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Signifly\Translator\Concerns\Translatable;

class Product extends Model
{
    use Translatable;

    protected $guarded = [];

    public function getTranslatableAttributes(): array
    {
        return ['name', 'description'];
    }
}
