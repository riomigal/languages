@extends('languages::component.table-section')
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::navbar.settings')])
    <div class="bg-white dark:bg-gray-800 dark:text-gray-300 text-md relative shadow-md sm:rounded-lg">
        <div class="py-8 px-4 mx-auto max-w-2xl lg:py-16">
            <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">{{ __('languages::settings.import_settings') }}</h2>
            <div class="relative z-0 w-full mb-6 group">
                <p class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                      >{{config('languages.main_server_domain')}} </p>
                    <div class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{__('languages::settings.main_domain.label')}}</div>
                    <span class="text-xs font-light text-gray-500 dark:text-gray-300 pb-3 my-1">{{__('languages::settings.main_domain.info')}}</span>
            </div>
            @include('languages::component.input', [
                          'label' => __('languages::settings.domains.label'),
                          'name' => 'setting.domains',
                          'required' => true,
                          'info' => __('languages::settings.domains.info'),
                       ])
            @include('languages::component.switch', [
                    'name' => 'setting.db_loader',
                    'label' => __('languages::settings.db_loader_text')
                    ])
            @include('languages::component.switch', [
                   'name' => 'setting.import_vendor',
                   'label' =>__('languages::settings.import_vendor_text')
                   ])
            @include('languages::component.switch', [
                   'name' => 'setting.enable_pending_notifications',
                   'label' =>__('languages::settings.enable_pending_translations_notifications')
                   ])
            @include('languages::component.switch', [
                   'name' => 'setting.enable_automatic_pending_notifications',
                   'label' =>__('languages::settings.enable_automatic_pending_translations_notifications')
                   ])
            @include('languages::component.switch', [
                   'name' => 'setting.enable_open_ai_translations',
                   'label' =>__('languages::settings.enable_open_ai_translations')
                   ])
            @include('languages::component.switch', [
                   'name' => 'setting.import_only_from_root_language',
                   'label' =>__('languages::settings.import_only_from_root_language.label'),
                    'info' => __('languages::settings.import_only_from_root_language.info', ['language' => config('app.locale')])
                   ])
            @include('languages::component.switch', [
                   'name' => 'setting.allow_deleting_languages',
                   'label' =>__('languages::settings.allow_deleting_languages.label'),
                   ])
        </div>
    </div>
@endsection
