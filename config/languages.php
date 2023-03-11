<?php

/**
 * Stores the config of the languages package
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Languages Route
    |--------------------------------------------------------------------------
    |
    | The languages route names.
    |
    | Change if you would like to change the route names.
    |
    | Current setting ./translator/login, ./translator/languages...
    |
    */
    'prefix' => 'translator',

    'languages_url' => 'languages',

    'translations_url' => 'translations',

    'translators_url' => 'translators',

    'login_url' => 'login',

    /*
    |--------------------------------------------------------------------------
    | Languages Table
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
    | Path
    |--------------------------------------------------------------------------
    |
    | Language folder Paths.
    | language_folder_folder_directory: is set to default ./lang path
    | language_vendor_folder_directory: is always in parent language directory ./lang/vendor
    |
    | Change only if really necessary!
    |
    */

    'language_folder_folder_directory' => 'lang',

    'language_vendor_folder_directory' => 'vendor',

    /*
    |--------------------------------------------------------------------------
    | Queue Languages
    |--------------------------------------------------------------------------
    |
    | Set the queue name and batch name for the languages background jobs
    |
    */

    'queue_name' => 'languageProcessor',

    'batch_name' => 'languageBatch',

    'prune_batch_hours' => 1, // Prunes all finished or cancelled batches older than this value (value in hours)

    /*
  |--------------------------------------------------------------------------
  | Guard Translator
  |--------------------------------------------------------------------------
  |
  | Set the guard name for the translator UI, will be assigned to model translator
  |
  */

    'translator_guard' => 'languages_translator',

];
