<?php

namespace Signifly\Translator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Signifly\Translator\Models\Translation;
use Signifly\Translator\Exceptions\InvalidConfiguration;
use Signifly\Translator\Contracts\Translator as TranslatorContract;
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
            $this->publishConfigs();
            $this->publishMigrations();
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
        $this->app->singleton(TranslatorContract::class, function ($app) {
            return new Translator($app['config']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [TranslatorContract::class];
    }

    protected function publishConfigs(): void
    {
        $this->publishes([
            __DIR__.'/../config/translator.php' => config_path('translator.php'),
        ], 'translator-config');
    }

    protected function publishMigrations(): void
    {
        if (class_exists('CreateTranslationsTable')) {
            return;
        }

        $timestamp = date('Y_m_d_His', time());

        $this->publishes([
            __DIR__.'/../migrations/create_translations_table.php.stub' => database_path("/migrations/{$timestamp}_create_translations_table.php"),
        ], 'translator-migrations');
    }

    public static function determineTranslationModel(): string
    {
        $model = config('translator.translation_model') ?? Translation::class;

        if (! is_a($model, TranslationContract::class, true)
            || ! is_a($model, Model::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($model);
        }

        return $model;
    }

    public static function getTranslationModelInstance(): TranslationContract
    {
        $model = self::determineTranslationModel();

        return new $model();
    }
}
