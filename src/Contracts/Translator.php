<?php

namespace Signifly\Translator\Contracts;

interface Translator
{
    public function activateLanguage(string $languageCode): void;

    public function activeLanguageCode(): string;

    public function autoTranslates(): bool;

    public function defaultLanguageCode(): string;

    public function disableAutoTranslation(): void;

    public function enableAutoTranslation(): void;

    public function isDefaultLanguage(string $languageCode): bool;

    public function softDeletes(): bool;
}
