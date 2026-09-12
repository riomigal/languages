@extends('languages::component.table-section', ['maxWidth' => '1400'])
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
                   'text' => __('languages::languages.form.button.add'),
                    'showLoader' => '1'
                   ]
               )
            @include('languages::component.button',
               [
               'clickEvent' => 'closeForm',
               'text' => __('languages::languages.form.button.close'),
                'showLoader' => '1'
               ]
           )
        </form>
    @endif
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
        <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
            @include('languages::component.search')
            <div class="flex flex-wrap">
            @if($isAdministrator)
                    @include('languages::component.button',
                                           [
                                           'clickEvent' => 'showForm',
                                           'text' => __('languages::languages.button.add_language'),
                                           'showLoader' => '1'
                                           ]
                                       )
                @include('languages::component.button',
                   [
                   'clickEvent' => 'importLanguages',
                  'text' => __('languages::languages.button.import_languages'),
                       'showLoader' => '1'
                   ]
               )
                @if($data->items())
                    @include('languages::component.button',
                   [
                   'clickEvent' => 'importTranslations',
                  'text' => __('languages::languages.button.import_translations'),
                       'showLoader' => '1'
                   ]
               )
                    @endif
                    @include('languages::component.button',
                       [
                       'clickEvent' => 'findMissingTranslations',
                      'text' => __('languages::languages.button.find_missing_translations') .  ( \Riomigal\Languages\Models\Setting::getCached()->enable_open_ai_translations ? ' ' . __('languages::languages.button.chat_gpt_enabled') : ''),
                       'showLoader' => '1'
                       ]
                   )
                    @include('languages::component.button',
                               [
                                'clickEvent' => 'approveAllLanguagesTranslations',
                                'text' => __('languages::translations.button.approve_all_languages'),
                                'showLoader' => '1'
                               ]
                            )
                    @if(!\Riomigal\Languages\Models\Setting::getCached()->db_loader)
                        @include('languages::component.button',
                          [
                          'clickEvent' => 'exportTranslationsForAllLanguages',
                           'text' => __('languages::translations.button.export_all_translations'),
                           'showLoader' => '1'
                          ]
                        )
                    @else
                        @include('languages::component.button',
                          [
                            'clickEvent' => 'exportTranslationsForAllLanguages("1")',
                            'text' => __('languages::translations.button.export_all_translations_models'),
                            'showLoader' => '1'
                          ]
                        )
                    @endif
                <button type="button"
                        wire:click.prevent="deleteJobs"
                        class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800">
                        <svg wire:loading wire:target="deleteRunningJobs" aria-hidden="true" role="status"
                             class="inline w-4 h-4 mr-3 text-white animate-spin"
                             viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                  fill="#E5E7EB"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                  fill="currentColor"/>
                        </svg>
                    {{ __('languages::languages.button.delete_jobs') }}
                </button>
                @endif
            </div>
        </div>
        @php
        $action = [];
        if(\Riomigal\Languages\Models\Setting::getCached()->allow_deleting_languages) {
            $action[] = 'delete';
        }
        @endphp
        @include('languages::component.table', [
                   'thead' => [
                    __('languages::languages.table.head.language_code'),
                    __('languages::languages.table.head.language_name'),
                    __('languages::languages.table.head.language_native_name')
                    ],
                    'tbody' => ['code', 'name','native_name'],
                    'action' => $action,
                    'route' => ['name' => 'languages.translations', 'parameter' => 'language']
               ])
        <div>
            @include('languages::vendor.livewire.tailwind', ['data' => $data])
        </div>
    </div>
@endsection
