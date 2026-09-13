@extends('languages::component.table-section', ['maxWidth' => 1400])
@section('content')
@include('languages::component.table-h1-heading', ['title' => __('languages::navbar.languages')])
@if(!$hasImportedLanguages && $isAdministrator)
    <p role="alert" class="p-4 text-blue-800 bg-blue-50 rounded-lg">{{ __('languages::languages.info_fallback_language', ['language' => config('app.locale')]) }}</p>
@endif
@if($showForm)
    <form method="POST" action="{{ route('languages.languages.store') }}" class="py-4">
        @csrf
        <label for="language" class="sr-only">Language</label>
        <select id="language" name="language" required class="max-w-sm mb-4 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">{{ __('languages::languages.form.select.placeholder') }}</option>
            @foreach($languages as $option)<option value="{{ $option['code'] }}" @selected(old('language') === $option['code'])>{{ $option['name'] }}</option>@endforeach
        </select>
        <p class="text-sm text-gray-500 dark:text-gray-300 pb-3">{{ __('languages::languages.form.info') }}</p>
        @include('languages::component.error', ['field' => 'language'])
        @include('languages::component.button', ['text' => __('languages::languages.form.button.add')])
        <a role="button" href="{{ route('languages.languages') }}" class="inline-block p-2">{{ __('languages::languages.form.button.close') }}</a>
    </form>
@endif
<div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
        @include('languages::component.search')
        @if($isAdministrator)
            <div class="flex flex-wrap gap-2">
                <form method="GET" action="{{ route('languages.languages') }}">
                    <input type="hidden" name="create" value="1">
                    @include('languages::component.button', ['text' => __('languages::languages.button.add_language')])
                </form>
                @foreach(['import-languages' => 'import_languages', 'import-translations' => 'import_translations', 'find-missing' => 'find_missing_translations', 'cancel-jobs' => 'delete_jobs'] as $action => $label)
                    @include('languages::partials.action-form', ['url' => route('languages.languages.' . $action), 'text' => __('languages::languages.button.' . $label), 'fields' => []])
                @endforeach
                @include('languages::partials.action-form', ['url' => route('languages.languages.approve'), 'text' => __('languages::translations.button.approve_all_languages'), 'fields' => []])
                @php($onlyModels = \Riomigal\Languages\Models\Setting::getCached()->db_loader)
                @include('languages::partials.action-form', ['url' => route('languages.languages.export'), 'text' => __('languages::translations.button.' . ($onlyModels ? 'export_all_translations_models' : 'export_all_translations')), 'fields' => ['exportOnlyModels' => (int) $onlyModels]])
            </div>
        @endif
    </div>
    @include('languages::component.table', [
        'kind' => 'languages',
        'thead' => [__('languages::languages.table.head.language_code'), __('languages::languages.table.head.language_name'), __('languages::languages.table.head.language_native_name')],
        'tbody' => ['code', 'name', 'native_name'],
    ])
    @include('languages::partials.pagination')
</div>
@endsection
