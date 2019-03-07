<?php

namespace Signifly\Translator\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Signifly\Translator\TranslatorServiceProvider;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Translatable
{
    protected function bootTranslatable(): void
    {
        // Clean up translations
        static::deleting(function (Model $model) {
            if (collect(class_uses_recursive($model))->contains(SoftDeletes::class)
                && ! $model->forceDeleting) {
                return;
            }

            $model->translations()->delete();
        });
    }

    abstract public function getTranslatableAttributes(): array;

    /**
     * The associated translations relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(
            TranslatorServiceProvider::determineTranslationModel(),
            'translatable'
        );
    }

    /**
     * Returns the columns from the database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getColumnsFromDatabase(): Collection
    {
        $columns = Collection::make(
            DB::select('SHOW COLUMNS FROM '.$this->getTable())
        );

        return $columns->pluck('Field');
    }

    /**
     * Get the translated values.
     *
     * @param  string $langCode
     * @return array
     */
    public function getTranslatedValues(string $langCode) : array
    {
        return collect($this->getTranslatableAttributes())
            ->filter(function ($attribute) use ($langCode) {
                return $this->hasTranslation($langCode, $attribute);
            })
            ->values()
            ->mapWithKeys(function ($attribute) use ($langCode) {
                return [$attribute => $this->getTranslationValue($langCode, $attribute)];
            })
            ->toArray();
    }

    /**
     * Get the translation value for a given key.
     *
     * @param  string $attribute
     * @return mixed
     */
    public function getTranslationValue(string $langCode, string $attribute)
    {
        $translation = $this->translations->where('key', $attribute)
            ->where('language_code', $langCode)
            ->first();

        if (! $translation) {
            return;
        }

        return $translation->value;
    }

    /**
     * Get the updatable attributes.
     *
     * @return array
     */
    public function getUpdatableAttributes(): array
    {
        $fillable = collect($this->getFillable());

        if ($fillable->isEmpty()) {
            $fillable = $this->getColumnsFromDatabase();
        }

        return $fillable->diff($this->getTranslatableAttributes())
            ->values()
            ->toArray();
    }

    /**
     * Check if a translation exists for a given attribute.
     *
     * @param  string  $langCode
     * @param  string  $attribute
     * @return bool
     */
    public function hasTranslation(string $langCode, string $attribute): bool
    {
        if (! $this->shouldBeTranslated($attribute)) {
            return false;
        }

        if ($this->relationLoaded('translations')) {
            return $this->translations->where('key', $attribute)
                ->where('language_code', $langCode)
                ->count() > 0;
        }

        return $this->translations()->where('key', $attribute)
            ->forLang($langCode)
            ->count() > 0;
    }

    /**
     * Check if it's the default language code.
     *
     * @param  string  $langCode
     * @return bool
     */
    public function isDefaultLanguage(string $langCode): bool
    {
        return config('translator.default_language_code') === $langCode;
    }

    /**
     * Check if a translation is outdated.
     *
     * @param  string  $langCode
     * @param  string  $attribute
     * @return bool
     */
    public function isTranslationOutdated(string $langCode, string $attribute): bool
    {
        if ($this->isDefaultLanguage($langCode)) {
            return false;
        }

        $defaultTranslation = $this->translations->where('key', $attribute)
            ->where('language_code', config('translator.default_language_code'))
            ->first();

        if (! $defaultTranslation) {
            return false;
        }

        $targetTranslation = $this->translations->where('key', $attribute)
            ->where('language_code', $langCode)
            ->first();

        if (! $targetTranslation) {
            return false;
        }

        // Compare target translation to default translation
        return $targetTranslation->updated_at->lt($defaultTranslation->updated_at);
    }

    /**
     * Checks if the given attribute should be translated.
     *
     * @param  string $attribute
     * @return bool
     */
    public function shouldBeTranslated(string $attribute): bool
    {
        return in_array($attribute, $this->getTranslatableAttributes());
    }

    /**
     * Translate an array of attribute, value pairs.
     *
     * @param  string $langCode
     * @param  array  $data
     * @return \Illuminate\Support\Collection
     */
    public function translate(string $langCode, array $data): Collection
    {
        return collect($data)
            ->filter(function ($value, $attribute) {
                return $this->shouldBeTranslated($attribute);
            })
            ->map(function ($value, $attribute) use ($langCode) {
                return $this->translateAttribute($langCode, $attribute, $value);
            })
            ->values();
    }

    /**
     * Translate the specified attribute, value pair.
     *
     * @param  string $langCode
     * @param  string $attribute
     * @param  mixed $value
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function translateAttribute(string $langCode, string $attribute, $value): Model
    {
        return $this->translations()->updateOrCreate([
            'language_code' => $langCode,
            'key' => $attribute,
        ], compact('value'));
    }

    /**
     * Scope a query to include translation stats.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string  $langCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTranslationStats(Builder $query, string $langCode): Builder
    {
        $relation = $query->getRelation('translations');

        $model = get_class($this);
        $translationModel = TranslatableServiceProvider::determineTranslationModel();

        $subQuery = $translationModel::selectRaw('count(*)')
            ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName())
            ->where('translatable_type', $model)
            ->where('language_code', $langCode);

        return $query->defaultSelectAll()
            ->selectRaw("@modifier_count := ({$subQuery->toSql()}) as translations_count", $subQuery->getBindings())
            ->selectRaw('@modifier_count / ? * 100 as translations_percentage', [
                count($this->getTranslatableAttributes()),
            ])
            ->addSubSelect('translations_last_modified_at', $translationModel::select('updated_at')
                ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName())
                ->where('translatable_type', $model)
                ->where('language_code', $langCode)
                ->orderBy('updated_at', 'desc')
            );
    }
}
