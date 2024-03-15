<?php

return [
    'form' => [
        'select' => [
            'placeholder' => 'Select language'
            ],
        'info' => 'Add only new languages, already existing languages are invalid.',
        'button' => [
            'add' => 'Add',
            'close' => 'Close',
        ]
    ],
    'button' => [
        'import_translations' => 'Import Translations',
        'import_languages' => 'Import Languages',
        'add_language' => 'Add Language',
        'find_missing_translations' => 'Find Missing Translations',
        'chat_gpt_enabled' => '(OPEN AI ENABLED!)',
        'delete_jobs' => 'Delete running Batch (Jobs)'
    ],
    'table' => [
        'head' => [
            'language_code' => 'Language Code',
            'language_name' => 'Language Name',
            'language_native_name' => 'Language Native name',
        ],
    ],
    'import_languages_success' => 'Import finished. Languages imported: :languages',
    'import_languages_success_nothing_imported' => 'Import finished. No Languages imported.',
    'import_translations_success' => 'Import (:language_code) finished. Translations imported: :total',
    'find_missing_translations_success' => 'Import (:language_code) finished. Missing Translations imported: :total',
    'find_missing_translations_success_nothing_found' => 'Missing Translations Import finished. Nothing to import.',
    'deleted' => 'Language deleted!',
    'created' => 'Language :language created!',
    'info_fallback_language' => 'Your default language (config: app.locale) is: :language. Please make sure that this is the default language to use before importing the languages. Click on "Import Languages" to start using the application.'
];
