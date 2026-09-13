<?php

namespace Riomigal\Languages;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\ServiceProvider;
use Riomigal\Languages\Console\Commands\DeveloperDownloadToLocalCommand;
use Riomigal\Languages\Console\Commands\ExportTranslationAfterDeployment;
use Riomigal\Languages\Console\Commands\ExportTranslations;
use Riomigal\Languages\Console\Commands\FindMissingTranslations;
use Riomigal\Languages\Console\Commands\ImportLanguages;
use Riomigal\Languages\Console\Commands\ImportTranslations;
use Riomigal\Languages\Console\Commands\PruneLanguageBatches;
use Riomigal\Languages\Console\Commands\SendAutomaticPendingNotifications;
use Riomigal\Languages\Middleware\AuthApi;
use Riomigal\Languages\Middleware\AuthTranslator;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\OpenAITranslationService;


class LanguagesServiceProvider extends ServiceProvider
{
    public static string $version = '1.9.0';

    protected null|bool|object $settings = false;
    /**
     * Bootstrap the package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if(!config('languages.enabled', true)) {
            return;
        }
//        if(Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_settings'))) {
//            $this->settings = DB::connection(config('languages.db_connection'))->table(config('languages.table_settings'))->first();
//        }
        $this->publishes([
            __DIR__ . '/../config/languages.php' => config_path('languages.php'),
            __DIR__ . '/../config/openai.php' => config_path('openai.php') // Creates an open ai config
        ], 'languages-config',
        );
        $this->addMiddleware();
        $this->setCustomGuard();
        $this->loadTranslations();
        $this->loadRoutes();
        $this->loadViews();
        view()->composer('languages::partials.batch-progress', function (\Illuminate\View\View $view): void {
            $view->with('batch', resolve(\Riomigal\Languages\Services\BatchService::class)->progress());
        });
        // Translation values are content: leading and trailing whitespace can be
        // meaningful, so exempt the field from global input trimming.
        TrimStrings::except(['translatedValue']);
        $this->loadMigrations();
        $this->loadAssets();
        $this->loadCommands();

        // Delete Batches
//        $this->app->booted(function () {
//            $schedule = $this->app->make(Schedule::class);
//            $schedule->command('languages:prune-batches')->everyMinute();
//            if($this->settings && isset($this->settings->enable_automatic_pending_notifications) && $this->settings->enable_automatic_pending_notifications) {
//                $schedule->command('languages:send-automatic-pending-translations-notification')->daily();
//            }
//        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/languages.php', 'languages');

        if(!config('languages.enabled', true)) {
            return;
        }
        $this->app->singleton(OpenAITranslationService::class, function () {
            return new OpenAITranslationService();
        });
    }

    /**
     * Creates the new translator guard
     *
     * @return void
     */
    protected function setCustomGuard(): void
    {
        /** @var string $translatorGuard Configured translator guard name. */
        $translatorGuard = config('languages.translator_guard');
        Config::set('auth.guards.' . $translatorGuard, [
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
        /** @var string $translatorGuard Configured translator guard name. */
        $translatorGuard = config('languages.translator_guard');
        /** @var string $authGuard Configured authentication middleware alias. */
        $authGuard = config('languages.auth_guard');
        app('router')->aliasMiddleware($authGuard, AuthTranslator::class);
        app('router')->aliasMiddleware('languages.translator', \Riomigal\Languages\Middleware\EnsureTranslator::class);
        app('router')->aliasMiddleware('languages.admin', \Riomigal\Languages\Middleware\EnsureAdmin::class);
        app('router')->aliasMiddleware('laravel-languages-auth-api', AuthApi::class);
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
        if(config('languages.main_server_domain') === config('app.url')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
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
                ExportTranslations::class,
                SendAutomaticPendingNotifications::class,
                DeveloperDownloadToLocalCommand::class,
                ExportTranslationAfterDeployment::class
            ]);
        } else {
            $this->commands([
                ExportTranslations::class,
            ]);
        }
    }
}
