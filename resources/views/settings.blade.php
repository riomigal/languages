@extends('languages::component.table-section')
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::navbar.settings')])
    <div class="bg-white dark:bg-gray-800 dark:text-gray-300 text-md relative shadow-md sm:rounded-lg">
        <div class="py-8 px-4 mx-auto max-w-2xl lg:py-16">
            <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">{{ __('languages::settings.import_settings') }}</h2>
            @include('languages::component.switch', [
                    'model' => 'setting.db_loader',
                    'text' => __('languages::settings.db_loader_text')
                    ])
            @include('languages::component.switch', [
                   'model' => 'setting.import_vendor',
                   'text' =>__('languages::settings.import_vendor_text')
                   ])
            @include('languages::component.switch', [
                   'model' => 'setting.enable_pending_notifications',
                   'text' =>__('languages::settings.enable_pending_translations_notifications')
                   ])
            @include('languages::component.switch', [
                   'model' => 'setting.enable_automatic_pending_notifications',
                   'text' =>__('languages::settings.enable_automatic_pending_translations_notifications')
                   ])
            @include('languages::component.switch', [
                   'model' => 'setting.enable_open_ai_translations',
                   'text' =>__('languages::settings.enable_open_ai_translations')
                   ])
        </div>
    </div>
@endsection
