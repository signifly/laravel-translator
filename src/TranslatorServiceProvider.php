<?php

namespace Signifly\Translator;

use Illuminate\Support\ServiceProvider;
use Signifly\Translator\Models\Translation;
use Signifly\Translator\Exceptions\InvalidConfiguration;

class TranslatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    public static function determineTranslationModel(): string
    {
        $model = config('translator.translation_model') ?? Translation::class;

        if (! is_a($model, Translation::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($model);
        }

        return $model;
    }
}
