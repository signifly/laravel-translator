<?php

namespace Signifly\Translator\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Signifly\Translator\Facades\Translator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Translatable
{
    public static function bootTranslatable(): void
    {
        // Clean up translations
        static::deleting(function (Model $model) {
            in_array(SoftDeletes::class, class_uses_recursive($model))
                ? $model->clearTranslations($model->forceDeleting)
                : $model->clearTranslations(true);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restoring(function (Model $model) {
                if (! Translator::softDeletes()) {
                    return;
                }

                $model->translations()->restore();
            });
        }
    }

    /**
     * The associated translations relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translator::determineModel(), 'translatable');
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $activeLangCode = Translator::activeLanguageCode();

        if (! Translator::autoTranslates()) {
            return parent::attributesToArray();
        }

        return array_merge(
            parent::attributesToArray(),
            $this->getTranslatedValues($activeLangCode)
        );
    }

    /**
     * Clear translations based on model deletion.
     *
     * @param  bool $forceDelete
     * @return void
     */
    protected function clearTranslations($forceDelete = false): void
    {
        if (Translator::softDeletes() && $forceDelete) {
            $this->translations()->forceDelete();

            return;
        }

        $this->translations()->delete();
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $activeLangCode = Translator::activeLanguageCode();

        if (! Translator::autoTranslates()) {
            return parent::getAttributeValue($key);
        }

        if (! $this->hasTranslation($activeLangCode, $key)) {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslationValue($activeLangCode, $key);
    }

    /**
     * Returns the columns from the database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getColumnsFromDatabase(): Collection
    {
        $connection = $this->getConnectionName() ?? config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            return Collection::make(
                DB::select('PRAGMA table_info('.$this->getTable().')')
            )->pluck('name');
        }

        return Collection::make(
            DB::select('SHOW COLUMNS FROM '.$this->getTable())
        )->pluck('Field');
    }

    /**
     * Get the translatable attributes.
     *
     * @return array
     */
    public function getTranslatableAttributes(): array
    {
        if (isset($this->translatable)) {
            return $this->translatable;
        }

        return [];
    }

    /**
     * Get the translated values.
     *
     * @param  string $langCode
     * @return array
     */
    public function getTranslatedValues(string $langCode): array
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

        return $translation ? $translation->value : null;
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
     * Check if a translation is outdated.
     *
     * @param  string  $langCode
     * @param  string  $attribute
     * @return bool
     */
    public function isTranslationOutdated(string $langCode, string $attribute): bool
    {
        if (Translator::isDefaultLanguage($langCode)) {
            return false;
        }

        $defaultTranslation = $this->translations->where('key', $attribute)
            ->where('language_code', Translator::defaultLanguageCode())
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
     * Set the translatable attributes for the model.
     *
     * @param  array  $attributes
     * @return self
     */
    public function translatable(array $attributes): self
    {
        $this->translatable = $attributes;

        return $this;
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
                return $this->shouldBeTranslated($attribute) && ! is_null($value);
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
    public function translateAttribute(string $langCode, string $attribute, $value): ?Model
    {
        $translation = $this->translations()->firstOrNew([
            'language_code' => $langCode,
            'key' => $attribute,
        ], compact('value'));

        if (is_string($value) && $value === '' || $value === null) {
            $translation->delete();

            return null;
        }

        $translation->save();

        return $translation;
    }

    /**
     * Create and translate for the specified language code.
     *
     * @param  string $langCode
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function createAndTranslate(string $langCode, array $data): Model
    {
        $model = self::create($data);
        $model->translate($langCode, $data);

        return $model;
    }

    /**
     * Update and translate for the specified language code.
     *
     * @param  string $langCode
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateAndTranslate(string $langCode, array $data): Model
    {
        $this->update(
            Translator::isDefaultLanguage($langCode)
            ? $data
            : Arr::only($data, $this->getUpdatableAttributes())
        );

        $this->translate($langCode, $data);

        return $this;
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
        $translationModel = Translator::determineModel();

        $subQuery = $translationModel::selectRaw('count(*)')
            ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName())
            ->where('translatable_type', $model)
            ->where('language_code', $langCode);

        return $query->defaultSelectAll()
            ->selectRaw("@modifier_count := ({$subQuery->toSql()}) as translations_count", $subQuery->getBindings())
            ->selectRaw('@modifier_count / ? * 100 as translations_percentage', [
                count($this->getTranslatableAttributes()),
            ])
            ->addSubSelect(
                'translations_last_modified_at',
                $translationModel::select('updated_at')
                    ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName())
                    ->where('translatable_type', $model)
                    ->where('language_code', $langCode)
                    ->orderBy('updated_at', 'desc')
            );
    }
}
