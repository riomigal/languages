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
    'table_languages' => 'languages',

    'table_translations' => 'translations',

    'table_translators' => 'translators',

    'table_translator_language' => 'translator_language',

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
    'import_vendor_translations' => true, // Imports as well vendor translations

    'load_translations_from_db' => true, // Loads translations from the DB without using the filesystem

    'cache_key' => 'languages_cache'

    /*
 |--------------------------------------------------------------------------
 | API
 |--------------------------------------------------------------------------
 |
 | Set the api settings
 |
 */

//    'api' => [
//        'enabled' => true, // Enables the API routes, disable if not used
//
//        'middleware' => ['api'],
//
//        'prefix' => 'api',
//
//        'root_prefix' => 'translator',
//
//        'languages_url' => 'languages',
//
//        'translations_url' => 'translations',
//
//        'translators_url' => 'translators',
//
//        'login_url' => 'login',
//    ]

];
