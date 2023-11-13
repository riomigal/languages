@php use Riomigal\Languages\Models\Setting; @endphp
@extends('languages::component.table-section')
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::translations.title', ['language' => $this->language->name, 'code' => $this->language->code]) ])
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
        <div class="flex flex-wrap md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
            <div class="max-w-xl">
                <label for="translateLanguageExampleId"
                       class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{__('languages::translations.example_language.label')}}</label>
                <select id="translateLanguageExampleId" wire:model="translateLanguageExampleId"
                        class="max-w-md bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    @foreach($languages as $language)
                        <option value="{{$language['id']}}">{{$language['name']}} ({{$language['code']}})</option>
                    @endforeach
                </select>
                <p class="text-sm font-light text-gray-500 dark:text-gray-300 py-3">
                    {{ __('languages::translations.example_language.info', ['language' => config('app.fallback_locale')]) }}            </p>
            </div>
        </div>
        <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
            @include('languages::component.search')

            @if($isAdministrator)

                @if(!Setting::getCached()->db_loader)
                @include('languages::component.button',
                  [
                  'clickEvent' => 'exportTranslationsForLanguage',
                 'text' => __('languages::translations.button.export_translation')
                  ]
                )
                @endif

                @include('languages::component.button',
                      [
                      'clickEvent' => 'approveAllTranslations',
                       'text' => __('languages::translations.button.approve_all')
                      ]
                  )

                  @if(!Setting::getCached()->db_loader)
                        @include('languages::component.button',
                          [
                          'clickEvent' => 'exportTranslationsForAllLanguages',
                         'text' => __('languages::translations.button.export_all_translations')
                          ]
                           )
                @endif
            @endif

            @include('languages::component.select-checkbox-multiple',
               [
                   'id' => 'translations_select_type',
                   'text' =>__('languages::translations.filter.type'),
                   'model' => 'types',
                   'data' => [
                       'php' => __('languages::translations.filter.type_selection.php'),
                       'json' => __('languages::translations.filter.type_selection.json'),
                       'model' => __('languages::translations.filter.type_selection.model')
                       ]
                  ])

            @include('languages::component.select-checkbox-three-states',
             [
                 'id' => 'translations_filters_checkbox',
                 'text' => __('languages::translations.checkbox_filter_button'),
                 'data' => [
                     'needs_translation' => __('languages::translations.filter.needs_translation'),
                     'approved' => __('languages::translations.filter.approved'),
                     'updated_translation' => __('languages::translations.filter.updated_translation'),
                     'is_vendor' => __('languages::translations.filter.is_vendor'),
                    ]
                ])
        </div>
        @include('languages::component.table', [
                      'thead' => [
                      __('languages::translations.table.head.is_vendor'),
                      __('languages::translations.table.head.namespace'),
                       __('languages::translations.table.head.group'),
                        __('languages::translations.table.head.needs_translation'),
                       __('languages::translations.table.head.approved'),
                       __('languages::translations.table.head.approved_by'),
                       __('languages::translations.table.head.updated_translation') ,
                       __('languages::translations.table.head.updated_by') ,
                       __('languages::translations.table.head.exported') ,
                       __('languages::translations.table.head.key'),
                       __('languages::translations.table.head.content'),
                       __('languages::translations.table.head.old_content')
                       ],
                       'tbody' => ['is_vendor', 'namespace', 'group', 'needs_translation', 'approved', 'approver', 'updated_translation', 'updater', 'exported', 'key', 'value', 'old_value'],
                       'action' => ['translate', 'approve_translation', 'needs_translation', 'restore_translation'],
                  ])
        <div>
            @include('languages::vendor.livewire.tailwind', ['data' => $data])
        </div>
    </div>
    {{--    Modal --}}
    <div wire:ignore.self id="edit-translation-modal" tabindex="-1" aria-hidden="true"
         class="hidden w-full overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-3xl h-full md:h-auto max-w-4xl">
            <!-- Modal content -->
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <div
                    class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    @if($translation)
                        <p class="text-sm text-gray-400 font-bold dark:text-gray-400">
                           {{$translation->namespace ? $translation->namespace . '::' : ''}}{{$translation->group ? $translation->group . '.': ''}}{{$translation->key}}
                        </p>
                    @endif
                    <button type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                            wire:click.prevent="hideTranslationModal">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="w-full flex flex-wrap gap-4 text-md">
                    <div class="w-1/1">
                        @if($translationExample)
                            <p class="w-full mb-3 font-light text-gray-500 dark:text-gray-400">{!! $translationExample->value ?: "<span style='color:red'>" . __('languages::translations.no_translation_example') . "</span>" !!}</p>
                        @endif
                    </div>
                    <div class="w-full mb-4 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
                        <div class="px-4 py-2 bg-white rounded-t-lg dark:bg-gray-800">
                            @if($translation)
                                <textarea rows="4" wire:model.defer="translatedValue"
                                          class="w-full px-0 text-sm text-gray-900 bg-white border-0 dark:bg-gray-800 focus:ring-0 dark:text-white dark:placeholder-gray-400"
                                          required>{{$translation->value}}</textarea>
                            @endif
                        </div>
                        <div class="flex items-center justify-between px-3 py-2 border-t dark:border-gray-600">
                            <button type="submit" wire:click.prevent="updateTranslation"
                                    class="inline-flex items-center py-2.5 px-4 text-xs font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                {{ __('languages::translations.action_update')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @pushonce('scripts')
        <script>
            window.addEventListener('load', function () {
                const editTranslationModal = new window.Modal(document.getElementById('edit-translation-modal'), {
                    placement: 'center',
                    backdrop: 'dynamic',
                    backdropClasses: 'bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
                    closable: true,
                });

                window.addEventListener('showTranslationModal', (e) => {
                    editTranslationModal.show();
                });

                window.addEventListener('hideTranslationModal', (e) => {
                    editTranslationModal.hide();
                });
            }, false);

        </script>
    @endpushonce
@endsection
