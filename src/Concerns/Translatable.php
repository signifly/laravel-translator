<?php

namespace Signifly\Translator\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Signifly\Translator\Facades\Translator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Signifly\Translator\Contracts\Translation;
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
        $value = parent::getAttributeValue($key);

        if (! Translator::autoTranslates()) {
            return $value;
        }

        if (Translator::isDefaultLanguage($activeLangCode)) {
            return $value;
        }

        if (! $this->hasTranslation($activeLangCode, $key)) {
            return $value;
        }

        return $this->getTranslationValue($activeLangCode, $key);
    }

    /**
     * Returns the columns from the database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTableColumns(): Collection
    {
        return collect($this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable()));
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

        if ($translation === null) {
            return;
        }

        if ($this->hasCast($attribute)) {
            return $this->castAttribute($attribute, $translation->value);
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
            $fillable = $this->getTableColumns();
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
                return $this->shouldBeTranslated($attribute);
            })
            ->map(function ($value, $attribute) use ($langCode) {
                return $this->translateAttribute($langCode, $attribute, $value);
            })
            ->filter()
            ->values();
    }

    /**
     * Translate the specified attribute, value pair.
     *
     * @param  string $langCode
     * @param  string $attribute
     * @param  mixed $value
     * @return \Signifly\Translator\Contracts\Translation|null
     */
    public function translateAttribute(string $langCode, string $attribute, $value): ?Translation
    {
        $translation = $this->translations()->firstOrNew([
            'language_code' => $langCode,
            'key' => $attribute,
        ]);

        // If the value provided for translation is empty
        // then delete the translation and return
        if (! is_bool($value) && ! is_array($value) && trim((string) $value) === '') {
            $translation->delete();

            return null;
        }

        if ($this->isJsonCastable($attribute)) {
            $value = $this->castAttributeAsJson($attribute, $value);
        }

        $translation->fill(compact('value'))->save();

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
        // We update all the model's attributes, when it is the default language
        if (Translator::isDefaultLanguage($langCode)) {
            $this->update($data);
        }

        // Otherwise we only update the non-translatable attributes
        else {
            $this->update(Arr::only($data, $this->getUpdatableAttributes()));
        }

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

        $model = $this->getMorphClass();
        $translationModel = Translator::determineModel();

        $subQuery = $translationModel::selectRaw('count(*)')
            ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName())
            ->where('translatable_type', $model)
            ->where('language_code', $langCode);

        // Select *all* columns from the "primary" query table
        // if no other columns have been selected
        if (is_null($query->getQuery()->columns)) {
            $query->select($query->getQuery()->from.'.*');
        }

        return $query
            ->selectRaw("@modifier_count := ({$subQuery->toSql()}) as translations_count", $subQuery->getBindings())
            ->selectRaw('@modifier_count / ? * 100 as translations_percentage', [
                count($this->getTranslatableAttributes()),
            ])
            ->addSelect([
                'translations_last_modified_at' => $translationModel::select('updated_at')
                    ->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName())
                    ->where('translatable_type', $model)
                    ->where('language_code', $langCode)
                    ->orderBy('updated_at', 'desc')
                    ->limit(1),
            ]);
    }
}
