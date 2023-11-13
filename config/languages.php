<?php

/**
 * Stores the config of the languages package
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    |
    | The languages route names.
    |
    | Change if you would like to use different the URL paths.
    |
    | Default settings: ./translator/login, ./translator/languages...
    |
    */
    'prefix' => 'translator',

    'languages_url' => 'languages',

    'translations_url' => 'translations',

    'translators_url' => 'translators',

    'settings_url' => 'settings',

    'login_url' => 'login',

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The languages table names.
    |
    | Change these lines only before running the migrations. Else it will be necessary to manually change the table names afterwards.
    |
    */
    'table_languages' => 'languages_languages',

    'table_translations' => 'languages_translations',

    'table_translators' => 'languages_translators',

    'table_settings' => 'languages_settings',

    'table_translator_language' => 'languages_language',

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Set the queue name and batch name for the languages background jobs
    |
    */
    'queue_name' => 'languageProcessor',

    'batch_name' => 'languageBatch',

    'prune_batch_hours' => 24, // Prunes all finished or cancelled batches older than this value (value in hours)

    /*
    |--------------------------------------------------------------------------
    | Guard Translator
    |--------------------------------------------------------------------------
    |
    | Set the guard name for the translator UI, will be assigned to model translator
    |
    */
    'translator_guard' => 'languages_translator',

    'auth_guard' => 'auth_translator',


    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    |
    */

    'cache_key' => 'languages_cache',

    /*
    |--------------------------------------------------------------------------
    | Translatable models
    |--------------------------------------------------------------------------
    |
    | Set the translatable models in the array each model must have the property
    |
    | public array $translatable:
    |
    | e.g. public array $translatable = ['label']; or
    | public array $translatable = ['label', 'name']; for multiple implementations
    |
    | each DB table column (e.g. above label or name) must support json (LONGTEXT)
    |
    | each column will have the language codes as key and translates values as value
    | e.g.: {"en":"English","it":"inglese","de":"English"}
    |
    | We suggest using it with the package: spatie/laravel-translatable
    */

    'translatable_models' => [
//        \App\Models\User::class
    ]
];
