<?php

namespace Signifly\Translator;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Signifly\Translator\Contracts\Translation as TranslationContract;
use Signifly\Translator\Contracts\Translator as Contract;
use Signifly\Translator\Exceptions\InvalidConfiguration;
use Signifly\Translator\Models\Translation;

class Translator implements Contract
{
    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function activateLanguage(string $languageCode): void
    {
        $this->config->set('translator.active_language_code', $languageCode);
    }

    public function activeLanguageCode(): string
    {
        return $this->config->get('translator.active_language_code') ?? $this->defaultLanguageCode();
    }

    public function autoTranslates(): bool
    {
        return (bool) $this->config->get('translator.auto_translate_attributes', false);
    }

    public function defaultLanguageCode(): string
    {
        return $this->config->get('translator.default_language_code');
    }

    public function determineModel(): string
    {
        $model = $this->config->get('translator.translation_model') ?? Translation::class;

        if (! is_a($model, TranslationContract::class, true)
            || ! is_a($model, Model::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($model);
        }

        return $model;
    }

    public function disableAutoTranslation(): void
    {
        $this->config->set('translator.auto_translate_attributes', false);
    }

    public function enableAutoTranslation(): void
    {
        $this->config->set('translator.auto_translate_attributes', true);
    }

    public function isDefaultLanguage(string $languageCode): bool
    {
        return $this->defaultLanguageCode() === $languageCode;
    }

    public function languageParameter(): string
    {
        return $this->config->get('translator.language_parameter');
    }

    public function softDeletes(): bool
    {
        return (bool) $this->config->get('translator.soft_deletes', false);
    }
}
