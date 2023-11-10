@extends('languages::component.table-section', ['maxWidth' => '1200'])
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::navbar.languages')])
    @if(!$hasImportedLanguages && $isAdministrator)
        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400"
             role="alert">
            {{ __('languages::languages.info_fallback_language', ['language' => config('app.locale')]) }}
        </div>
    @endif
    @if($isAdministrator && $showForm)
        <form>
            <select id="language" wire:model="language" name="language"
                    class="max-w-sm my-4 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option>{{__('languages::languages.form.select.placeholder')}}</option>
                @foreach($languages as  $language)
                    <option value="{{$language['code']}}">{{$language['name']}}</option>
                @endforeach
            </select>
            <p class="text-sm font-light text-gray-500 dark:text-gray-300 pb-3">
                {{ __('languages::languages.form.info') }}            </p>
            @include('languages::component.error', ['field' => 'language'])
            @include('languages::component.button',
                   [
                   'clickEvent' => 'create',
                   'text' => __('languages::languages.form.button.add')
                   ]
               )
            @include('languages::component.button',
               [
               'clickEvent' => 'closeForm',
               'text' => __('languages::languages.form.button.close')
               ]
           )
        </form>
    @endif
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
        <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
            @include('languages::component.search')
            @if($isAdministrator)

                @include('languages::component.button',
                   [
                   'clickEvent' => 'importLanguages',
                  'text' => __('languages::languages.button.import_languages')
                   ]
               )
                @if($data->items())
                    @include('languages::component.button',
                   [
                   'clickEvent' => 'importTranslations',
                  'text' => __('languages::languages.button.import_translations')
                   ]
               )
                    @include('languages::component.button',
                       [
                       'clickEvent' => 'showForm',
                       'text' => __('languages::languages.button.add_language')
                       ]
                   )
                    @endif
                    @include('languages::component.button',
                       [
                       'clickEvent' => 'findMissingTranslations',
                      'text' => __('languages::languages.button.find_missing_translations')
                       ]
                   )
                @endif
            </div>
            @include('languages::component.table', [
                       'thead' => [
                        __('languages::languages.table.head.language_code'),
                        __('languages::languages.table.head.language_name'),
                        __('languages::languages.table.head.language_native_name')
                        ],
                        'tbody' => ['code', 'name','native_name'],
                        'action' => ['delete'],
                        'route' => ['name' => 'languages.translations', 'parameter' => 'language']
                   ])
            <div>
                @include('languages::vendor.livewire.tailwind', ['data' => $data])
            </div>
        </div>
@endsection
