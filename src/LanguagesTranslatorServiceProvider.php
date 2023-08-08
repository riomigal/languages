<?php

namespace Riomigal\Languages;

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
        $this->app->singleton('lang.helper', function () {
            return new LanguageHelper();
        });

        parent::register();
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader(): void
    {
        if(app()->runningInConsole()) {
            try {
                DB::connection()->getDatabaseName();
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
        if(Schema::hasTable('settings') && DB::table('settings')->first()->db_loader) {
            $this->app->singleton('translation.loader', function ($app) {
                return new TranslationLoader($app['files'], $app['path.lang']);
            });
        } else {
            parent::registerLoader();
        }
    }
}
