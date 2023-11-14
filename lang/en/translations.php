<?php

return [
    'button' => [
        'export_all_translations' => 'Export All Languages',
        'export_translation' => 'Export Language',
        'approve_all' => 'Approve ALL Translations'
    ],
    'title' => 'Translations :language (:code)',
    'checkbox_filter_button' => 'State Filters',
    'table' => [
        'head' => [
            'is_vendor' => 'Vendor',
            'namespace' => 'Namespace',
            'group' => 'Group',
            'approved_by' => 'Approved By',
            'updated_by' => 'Updated By',
            'exported' => 'Exported',
            'path' => 'Path',
            'file' => 'File Name',
            'approved' => 'Approved',
            'needs_translation' => 'Needs Translation',
            'updated_translation' => 'Updated',
            'key' => 'Key',
            'content' => 'Content',
            'old_content' => 'Old Content',
        ],
        'action' => [
            'translate' => 'Translate',
            'approve' => 'Approve',
            'needs_translation' => 'Request translation',
            'restore_needs_translation' => 'Remove translation request',
            'restore_translation' => 'Restore'
        ]
    ],
    'filter' => [
        'needs_translation' => 'Needs Translation',
        'approved' => 'Approved',
        'updated_translation' => 'Updated',
        'is_vendor' => 'Is Vendor',
        'type' => 'Type',
        'type_selection' => [
            'php' => 'PHP',
            'json' => 'JSON',
            'model' => 'Model'
        ]
    ],
    'example_language' => [
        'label' => 'Example Language',
        'info' => 'The Language from which to translate from. If no translation it will use the fallback language: :language.'
    ],
    'action_update' => 'Update Translation',
    'action_update_with_open_ai' => 'Translate with OPEN AI',
    'update_success_message' => 'Translation updated.',
    'no_translation_example' => 'ATTENTION: This is not an example. There is no translation for this key!',
    'export_language_success' => 'Export finished. Languages exported: :language - Total Exports: :total',
    'export_languages_success' => 'Export finished. Languages exported: :languages - Total Exports: :total',
    'nothing_exported' => 'Nothing exported.',
];
