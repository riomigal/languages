@extends('languages::component.table-section', ['maxWidth' => 1400])
@section('content')
@include('languages::component.table-h1-heading', ['title' => __('languages::navbar.translators')])
@if($showForm && !$showUpdatePasswordForm)
    <form id="createOrUpdateForm" method="POST" action="{{ $translator ? route('languages.translators.update', $translator) : route('languages.translators.store') }}" class="pb-4">
        @csrf
        <div class="grid md:grid-cols-2 gap-6">
            @foreach(['email' => 'email', 'phone' => 'tel', 'first_name' => 'text', 'last_name' => 'text'] as $field => $inputType)
                @include('languages::component.input', ['name' => $field, 'label' => __('languages::translators.form.label.' . $field), 'type' => $inputType, 'required' => $field !== 'phone', 'value' => $translator?->{$field}])
            @endforeach
            @if(!$translator)
                @foreach(['password', 'password_confirmation'] as $field)
                    @include('languages::component.input', ['name' => $field, 'label' => __('languages::translators.form.label.' . $field), 'type' => 'password', 'required' => true])
                @endforeach
            @endif
        </div>
        <div class="grid md:grid-cols-2 gap-6 mb-4">
            @include('languages::component.select-checkbox-multiple', ['id' => 'translator-permissions', 'name' => 'languages', 'text' => __('languages::translators.form.label.languages'), 'options' => $availableLanguages->pluck('name', 'id')->all(), 'selected' => old('languages', $translator?->languages->pluck('id')->all() ?? [])])
            @include('languages::component.switch', ['name' => 'admin', 'label' => __('languages::translators.form.label.admin'), 'checked' => $translator?->admin ?? false])
        </div>
        @include('languages::component.button', ['text' => __('languages::translators.form.button.' . ($translator ? 'update' : 'create'))])
        <a role="button" href="{{ route('languages.translators') }}" class="inline-block p-2">{{ __('languages::translators.form.button.close') }}</a>
        @if($translator)
            <a role="button" href="{{ route('languages.translators.edit', ['translator' => $translator, 'password' => 1]) }}" class="inline-block p-2">{{ __('languages::translators.form.button.update_password') }}</a>
        @endif
    </form>
    @if($translator && \Riomigal\Languages\Models\Setting::getCached()->enable_pending_notifications)
        @include('languages::partials.action-form', ['url' => route('languages.translators.notify', $translator), 'text' => __('languages::translators.form.button.pending_translations_notification'), 'fields' => []])
    @endif
@endif
@if($showUpdatePasswordForm)
    <form method="POST" action="{{ route('languages.translators.password', $translator) }}">
        @csrf
        <h2 class="mb-4">{{ __('languages::translators.form.update_password_title', ['email' => $translator->email]) }}</h2>
        @foreach(['new_password', 'new_password_confirmation'] as $field)
            @include('languages::component.input', ['name' => $field, 'label' => __('languages::translators.form.label.' . substr($field, 4)), 'type' => 'password', 'required' => true])
        @endforeach
        @include('languages::component.button', ['text' => __('languages::translators.form.button.update_password')])
        <a role="button" href="{{ route('languages.translators.edit', $translator) }}" class="inline-block p-2">{{ __('languages::translators.form.button.close') }}</a>
    </form>
@endif
<div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
        @include('languages::component.search')
        <form method="GET" action="{{ route('languages.translators') }}">
            <input type="hidden" name="create" value="1">
            @include('languages::component.button', ['text' => __('languages::translators.button_toggle_create_form')])
        </form>
        <form method="GET" action="{{ route('languages.translators') }}" data-filter-form>
            @include('languages::partials.query-fields', ['except' => ['selectedLanguages']])
            @include('languages::component.select-checkbox-multiple', ['id' => 'translator-language-filter', 'name' => 'selectedLanguages', 'text' => __('languages::translators.button_filter_languages'), 'options' => $availableLanguages->pluck('name', 'id')->all(), 'selected' => $selectedLanguages, 'apply' => true])
        </form>
    </div>
    @include('languages::component.table', [
        'kind' => 'translators',
        'thead' => array_map(fn ($field) => __('languages::translators.table.head.' . $field), ['id', 'first_name', 'last_name', 'email', 'phone', 'admin', 'languages']),
        'tbody' => ['id', 'first_name', 'last_name', 'email', 'phone', 'admin', 'languages'],
    ])
    @include('languages::partials.pagination')
</div>
@endsection
