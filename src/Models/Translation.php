<?php

namespace Signifly\Translator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'language_code',
        'key',
        'value',
        'type',
    ];

    /**
     * The associated translatable relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include translations for a given language.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string  $langCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLang(Builder $query, string $langCode): Builder
    {
        return $query->where('language_code', $langCode);
    }
}
