@extends('languages::component.table-section')
@section('content')
@include('languages::component.table-h1-heading', ['title' => __('languages::navbar.settings')])
<div class="card bg-base-100 shadow-md">
    <div class="card-body py-8 px-4 mx-auto w-full max-w-2xl">
        <h2 class="card-title mb-4">{{ __('languages::settings.import_settings') }}</h2>
        <p class="mb-4">{{ __('languages::settings.main_domain.label') }}: {{ config('languages.main_server_domain') }}</p>
        <p class="mb-4 text-sm text-base-content/70">Each field saves independently when changed. Use its Save button if JavaScript is disabled.</p>
        @php($multiHost = (bool) old('enable_multi_host', $setting->enable_multi_host))
        <form method="POST" action="{{ route('languages.settings.update', 'enable_multi_host') }}" data-autosave>
            @csrf
            @include('languages::component.switch', ['id' => 'setting.enable_multi_host', 'name' => 'enable_multi_host', 'label' => __('languages::settings.enable_multi_host.label'), 'checked' => $setting->enable_multi_host, 'info' => __('languages::settings.enable_multi_host.info')])
            <button type="submit" class="btn btn-ghost btn-sm text-primary" data-save-fallback>Save</button>
        </form>
        <form method="POST" action="{{ route('languages.settings.update', 'domains') }}" data-autosave>
            @csrf
            @include('languages::component.input', ['id' => 'setting.domains', 'name' => 'domains', 'label' => __('languages::settings.domains.label'), 'value' => $setting->domains, 'required' => $multiHost, 'info' => __('languages::settings.domains.info')])
            <button type="submit" class="btn btn-ghost btn-sm text-primary" data-save-fallback>Save</button>
        </form>
        @foreach([
            'db_loader' => 'db_loader_text', 'import_vendor' => 'import_vendor_text',
            'enable_pending_notifications' => 'enable_pending_translations_notifications',
            'enable_automatic_pending_notifications' => 'enable_automatic_pending_translations_notifications',
            'enable_open_ai_translations' => 'enable_open_ai_translations',
            'import_only_from_root_language' => 'import_only_from_root_language.label',
            'allow_deleting_languages' => 'allow_deleting_languages.label',
        ] as $field => $label)
            <form method="POST" action="{{ route('languages.settings.update', $field) }}" data-autosave>
                @csrf
                @include('languages::component.switch', ['id' => 'setting.' . $field, 'name' => $field, 'label' => __('languages::settings.' . $label), 'checked' => $setting->{$field}])
                <button type="submit" class="btn btn-ghost btn-sm text-primary" data-save-fallback>Save</button>
            </form>
        @endforeach
    </div>
</div>
@endsection
