<?php

namespace Signifly\Translator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Signifly\Translator\Concerns\Translatable;

class Product extends Model
{
    use Translatable;

    protected $fillable = ['name', 'description'];

    protected $translatable = ['name', 'description'];
}
