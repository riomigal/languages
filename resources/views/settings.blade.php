@extends('languages::component.table-section')
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::navbar.settings')])
    <div class="bg-white dark:bg-gray-800 dark:text-gray-300 text-md relative shadow-md sm:rounded-lg">
        <div class="py-8 px-4 mx-auto max-w-2xl lg:py-16">
            <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">{{ __('languages::settings.import_settings') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 mb-4">
                <div class="sm:col-span-2">
                    <label class="relative inline-flex items-center mb-3 cursor-pointer">
                        <input type="checkbox" wire:model="setting.db_loader" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="ml-3 text-sm font-medium">{{ __('languages::settings.db_loader_text') }}</span>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 mb-4">
                <div class="sm:col-span-2">
                    <label class="relative inline-flex items-center mb-3 cursor-pointer">
                        <input type="checkbox" wire:model="setting.import_vendor" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="ml-3 text-sm font-medium">{{ __('languages::settings.import_vendor_text') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
