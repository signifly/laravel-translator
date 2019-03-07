<?php

namespace Signifly\Translator\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

interface Translation
{
    public function translatable(): MorphTo;

    public function scopeForLang(Builder $query, string $langCode): Builder;
}
