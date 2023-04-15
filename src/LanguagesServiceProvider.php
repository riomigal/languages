<?php

namespace Riomigal\Languages;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Riomigal\Languages\Console\Commands\ExportTranslations;
use Riomigal\Languages\Console\Commands\FindMissingTranslations;
use Riomigal\Languages\Console\Commands\ImportLanguages;
use Riomigal\Languages\Console\Commands\ImportTranslations;
use Riomigal\Languages\Console\Commands\PruneLanguageBatches;
use Riomigal\Languages\Helpers\LanguageHelper;
use Riomigal\Languages\Livewire\BatchExecution;
use Riomigal\Languages\Livewire\FlashMessage;
use Riomigal\Languages\Livewire\LanguagesToastMessage;
use Riomigal\Languages\Livewire\Login;
use Riomigal\Languages\Livewire\Translations;
use Riomigal\Languages\Livewire\Translators;
use Riomigal\Languages\Middleware\AuthTranslator;
use Riomigal\Languages\Models\Translator;


class LanguagesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/languages.php' => config_path('languages.php'), 'languages-config',
        ]);
        $this->addMiddleware();
        $this->setCustomGuard();
        $this->loadTranslations();
        $this->loadRoutes();
        $this->loadViews();
        $this->loadMigrations();
        $this->loadLivewireComponents();
        $this->loadAssets();
        $this->loadCommands();

        // Delete Batches
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('languages:prune-batches')->everyMinute();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/languages.php', 'languages');
        $this->app->singleton('lang.helper', function () {
            return new LanguageHelper();
        });
    }

    /**
     * Creates the new translator guard
     *
     * @return void
     */
    protected function setCustomGuard(): void
    {
        Config::set('auth.guards.' . config('languages.translator_guard'), [
            'driver' => 'session',
            'provider' => 'translators',
        ]);

        Config::set('auth.providers.translators', [
            'driver' => 'eloquent',
            'model' => Translator::class,
        ]);

        Config::set('auth.passwords.translators', [
            'provider' => 'translators',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ]);
    }

    /**
     * Adds the required middleware for the translator guard
     *
     * @return void
     */
    protected function addMiddleware(): void
    {
        $translatorGuard = config('languages.translator_guard');
        app('router')->aliasMiddleware(config('languages.auth_guard'), AuthTranslator::class);
        app('router')->pushMiddlewareToGroup($translatorGuard, \Illuminate\Cookie\Middleware\EncryptCookies::class);
        app('router')->pushMiddlewareToGroup($translatorGuard, \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
        app('router')->pushMiddlewareToGroup($translatorGuard, \Illuminate\Session\Middleware\StartSession::class);
        app('router')->pushMiddlewareToGroup($translatorGuard, \Illuminate\View\Middleware\ShareErrorsFromSession::class);
        app('router')->pushMiddlewareToGroup($translatorGuard, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        app('router')->pushMiddlewareToGroup($translatorGuard, \Illuminate\Routing\Middleware\SubstituteBindings::class);
        app('router')->aliasMiddleware($translatorGuard, \Riomigal\Languages\Middleware\Translator::class);
    }

    /**
     * @return void
     */
    protected function loadLivewireComponents(): void
    {
        Livewire::component('translators', Translators::class);
        Livewire::component('login', Login::class);
        Livewire::component('translations', Translations::class);
        Livewire::component('languages', \Riomigal\Languages\Livewire\Languages::class);
        Livewire::component('toast', LanguagesToastMessage::class);
        Livewire::component('flash-message', FlashMessage::class);
        Livewire::component('batch-execution', BatchExecution::class);
    }

    /**
     * @return void
     */
    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'languages');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/languages')], 'languages-translations',
        );
    }

    /**
     * @return void
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        if(config('languages.api.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            Route::middleware(config('languages.api.middleware'))
                ->prefix(config('languages.api.prefix'))
                ->group(__DIR__ . '/../routes/api.php');
        }
    }

    /**
     * @return void
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'languages');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/languages')], 'languages-views',
        );
    }

    /**
     * @return void
     */
    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'languages-migrations');
    }

    /**
     * @return void
     */
    protected function loadAssets(): void
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/languages'),
        ], 'languages-public');
    }

    /**
     * @return void
     */
    protected function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneLanguageBatches::class,
                ImportLanguages::class,
                ImportTranslations::class,
                FindMissingTranslations::class,
                ExportTranslations::class
            ]);
        }
    }
}
