<?php

namespace Signifly\Translator;

use Illuminate\Support\ServiceProvider;
use Signifly\Translator\Models\Translation;
use Signifly\Translator\Exceptions\InvalidConfiguration;
use Signifly\Translator\Contracts\Translation as TranslationContract;

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
            $this->publishes([
                __DIR__.'/../config/translator.php' => config_path('translator.php'),
            ], 'translator-config');
        }

        $this->mergeConfigFrom(__DIR__.'/../config/translator.php', 'translator');
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

    public static function getTranslationModelInstance(): TranslationContract
    {
        $translationModelClassName = self::determineTranslationModel();

        return new $translationModelClassName();
    }
}
