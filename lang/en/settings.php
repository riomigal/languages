<?php

return [
    'main_domain' => [
        'label' => 'Main Domain',
        'info' => 'The main domain where the translations process is executed. This value has to be changed in the language config file.',
    ],
    'enable_multi_host' => [
        'label' => 'Enable multi-host coordination',
        'info' => 'Leave disabled for a single project. Enable to check running jobs, export translations and cancel jobs on shared hosts.',
    ],
    'domains' => [
        'label' => 'Domains',
        'info' => 'Required only when multi-host coordination is enabled. Add all domains sharing the translation system, separated by commas and including http:// or https://. E.g. http://example.com,https://example.com.'
    ],
    'import_settings' => 'Import Settings',
    'db_loader_text' => 'Load Translations from DB (Make sure to import before the translations from the filesystem)',
    'import_vendor_text' => 'Import vendor translations (Make sure to disable DB Translations and import the vendor files and enable it again afterwards)',
    'enable_pending_translations_notifications' => 'Enable pending translations notifications.',
    'enable_automatic_pending_translations_notifications' => 'Enable automatic pending translations notifications.',
    'enable_open_ai_translations' => 'Enable Open AI translations while importing missing languages.',
    'import_only_from_root_language' => [
        'label' => 'Import only from root language.',
        'info' => 'Enable if you want import only from file from the root language (:language)',
    ],
    'allow_deleting_languages' => [
        'label' => 'Allow deleting languages.',
    ]
];
