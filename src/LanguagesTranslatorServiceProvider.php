<?php

namespace Riomigal\Languages;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\TranslationServiceProvider;
use Riomigal\Languages\Helpers\LanguageHelper;

class LanguagesTranslatorServiceProvider extends TranslationServiceProvider
{
    /**
     * Bootstrap the package services.
     *
     * @return void
     */
    public function boot(): void
    {

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        if(config('languages.enabled')) {
            $this->app->singleton(LanguageHelper::class, function () {
                return new LanguageHelper();
            });
        }

        parent::register();
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader(): void
    {
        if(!config('languages.enabled')) {
            parent::registerLoader();
            return;
        }
        if(app()->runningInConsole()) {
            try {
                DB::connection(config('languages.db_connection'))->connection()->getDatabaseName();
                $this->loadTranslationsArray();
            } catch (\Exception) {
                parent::registerLoader();
            }
        } else {
            $this->loadTranslationsArray();
        }
    }

    /**
     * @return void
     */
    protected function loadTranslationsArray(): void
    {
        $hasDBLoaderOn = Cache::rememberForever(config('languages.cache_key') . '_has_db_loader_on', function () {
            return Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_settings')) && DB::connection(config('languages.db_connection'))->table(config('languages.table_settings'))->first()->db_loader;
        });
        if($hasDBLoaderOn) {
            $this->app->singleton('translation.loader', function ($app) {
                return new TranslationLoader($app['files'], $app['path.lang']);
            });
        } else {
            parent::registerLoader();
        }
    }
}
