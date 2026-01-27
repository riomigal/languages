<?php

return [
    'button' => [
        'export_all_translations' => 'Export All Languages',
        'export_all_translations_models' => 'Export All Translated Models',
        'export_translation' => 'Export Language',
        'export_translation_models' => 'Export Translated Models',
        'approve_all' => 'Approve (:language_code) Translations',
        'approve_all_languages' => 'Approve (All Languages) Translations'
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
        'exported' => 'Exported',
        'type' => 'Type',
        'updated_by' => 'Updated by',
        'approved_by' => 'Approved by',
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
    'action_update_and_translate_others' => 'Update & auto-translate others (OPEN AI)',
    'action_update_with_open_ai' => 'Translate with OPEN AI',
    'update_success_message' => 'Translation updated.',
    'no_translation_example' => 'ATTENTION: This is not an example. There is no translation for this key!',
    'export_language_success' => 'Export finished. Languages exported: :language - Total Exports: :total',
    'export_languages_success' => 'Export finished. Languages exported: :languages - Total Exports: :total',
    'approved_language_success' => 'Update finished. Languages approved: :language - Total Approved: :total',
    'updated_all_languages' => 'Update finished. Languages auto updated for translations id: :translation_id',
    'approved_languages_success' => 'Update finished. Languages approved: :languages - Total Approved: :total',
    'export_on_other_host_success' => 'Export on :host finished.',
    'export_on_other_host_started' => 'Export on :host started.',
    'export_on_other_host_start_failed' => 'Export on :host couldn\'t start, something went wrong.',
    'nothing_exported' => 'Nothing exported.',
    'nothing_approved' => 'Nothing approved.',
    'pr_created_success' => 'Pull request created successfully! Languages: :languages - Total: :total - PR: :pr_url',
    'pr_creation_failed' => 'Failed to create pull request.',
    'pr_not_configured' => 'GitHub PR integration is not configured.',
    'create_pr_label' => 'Create Pull Request',
    'create_pr_tooltip' => 'After export, create a PR to the configured GitHub repository',
];
