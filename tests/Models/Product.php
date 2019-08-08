<?php

namespace Signifly\Translator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Signifly\Translator\Concerns\Translatable;

class Product extends Model
{
    use Translatable;

    /** @var array */
    protected $fillable = [
        'name',
        'description',
        'data',
    ];

    /** @var array */
    protected $casts = [
        'data' => 'json',
    ];

    /** @var array */
    protected $translatable = [
        'name',
        'description',
        'data',
    ];
}
