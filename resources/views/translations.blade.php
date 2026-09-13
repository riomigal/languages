@extends('languages::component.table-section')
@section('content')
@include('languages::component.table-h1-heading', ['title' => __('languages::translations.title', ['language' => $language->name, 'code' => $language->code])])
<div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
    <div class="p-4 max-w-xl">
        <label for="translateLanguageExampleId" class="block mb-2 text-sm font-medium">{{ __('languages::translations.example_language.label') }}</label>
        <select id="translateLanguageExampleId" name="example_language" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @foreach($languages as $option)<option value="{{ $option->id }}" @selected($exampleLanguageId === $option->id)>{{ $option->name }} ({{ $option->code }})</option>@endforeach
        </select>
        <p class="text-sm text-gray-500 dark:text-gray-300 py-3">{{ __('languages::translations.example_language.info', ['language' => config('app.fallback_locale')]) }}</p>
    </div>
    <div class="flex flex-wrap gap-4 p-4 items-center">
        @include('languages::component.search')
        @foreach([
            'types' => [__('languages::translations.filter.type'), ['php' => 'PHP', 'json' => 'JSON', 'model' => 'Model']],
            'updatedBy' => [__('languages::translations.filter.updated_by'), $translators],
            'approvedBy' => [__('languages::translations.filter.approved_by'), $translators],
        ] as $name => [$label, $options])
            <form method="GET" action="{{ url()->current() }}" data-filter-form>
                @include('languages::partials.query-fields', ['except' => [$name]])
                @include('languages::component.select-checkbox-multiple', ['id' => 'filter-' . $name, 'text' => $label, 'name' => $name, 'options' => $options, 'selected' => request()->input($name, []), 'apply' => true])
            </form>
        @endforeach
        @include('languages::component.select-checkbox-three-states')
    </div>
    @if($isAdministrator)
        <div class="flex flex-wrap justify-end p-4">
            @php($onlyModels = \Riomigal\Languages\Models\Setting::getCached()->db_loader)
            @include('languages::partials.action-form', ['url' => route('languages.translations.export', ['language' => $language] + request()->query()), 'text' => __('languages::translations.button.' . ($onlyModels ? 'export_translation_models' : 'export_translation')), 'fields' => ['exportOnlyModels' => (int) $onlyModels]])
            @include('languages::partials.action-form', ['url' => route('languages.translations.approve-all', ['language' => $language] + request()->query()), 'text' => __('languages::translations.button.approve_all', ['language_code' => $language->code]), 'fields' => []])
        </div>
    @endif
    @include('languages::component.table', [
        'kind' => 'translations',
        'thead' => ['ID', ...array_map(fn ($field) => __('languages::translations.table.head.' . $field), ['is_vendor', 'namespace', 'group', 'needs_translation', 'approved', 'approved_by', 'updated_translation', 'updated_by', 'exported', 'key', 'content', 'old_content'])],
        'tbody' => ['id', 'is_vendor', 'namespace', 'group', 'needs_translation', 'approved', 'approver', 'updated_translation', 'updater', 'exported', 'key', 'value', 'old_value'],
    ])
    @include('languages::partials.pagination')
</div>
<dialog id="edit-translation-modal" aria-labelledby="translation-key" class="w-full max-w-5xl max-h-screen p-5 rounded-lg shadow bg-white dark:bg-gray-800 dark:text-white backdrop:bg-gray-900/60">
    <header class="flex justify-between items-center pb-4 mb-4 border-b dark:border-gray-600">
        <h2 id="translation-key" class="font-bold"></h2>
        <button type="button" data-modal-close aria-label="Close modal" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Close modal</button>
    </header>
    <p data-modal-loading role="status" hidden><span aria-hidden="true" class="inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span> Loading...</p>
    <p data-modal-error role="alert" hidden class="text-red-700 dark:text-red-400 mb-3"></p>
    <div data-modal-content hidden>
        <p data-example class="mb-4 whitespace-pre-wrap text-gray-500 dark:text-gray-300"></p>
        <form method="POST" data-translation-form>
            @csrf
            <label for="translatedValue" class="sr-only">Translation</label>
            <textarea id="translatedValue" name="translatedValue" rows="6" class="w-full p-3 mb-4 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
            <div class="flex flex-wrap gap-3">
                <button type="submit" data-save class="text-white bg-blue-700 rounded-lg px-4 py-2.5">{{ __('languages::translations.action_update') }}</button>
                <button type="submit" data-update-all hidden class="text-white bg-blue-700 rounded-lg px-4 py-2.5">{{ __('languages::translations.action_update_and_translate_others') }}</button>
                <button type="button" data-suggest hidden class="text-white bg-blue-700 rounded-lg px-4 py-2.5">{{ __('languages::translations.action_update_with_open_ai') }}</button>
                <span data-suggestion-loading hidden role="status">Translating...</span>
            </div>
        </form>
        <div data-suggestion-preview hidden class="p-4 my-4 border rounded-lg">
            <p data-suggestion-text class="whitespace-pre-wrap"></p>
            <button type="button" data-use-suggestion class="p-2 text-blue-700 dark:text-blue-300">Use suggestion</button>
        </div>
        <div data-examples class="flex flex-wrap gap-4 mt-4"></div>
    </div>
</dialog>
@endsection
