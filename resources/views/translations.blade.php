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
        <div class="flex flex-col md:flex-row items-center justify-start space-y-3 md:space-y-0 md:space-x-4 p-4">
            @include('languages::component.search')

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

            @include('languages::component.select-checkbox-multiple',
               [
                   'id' => 'translations_select_updated_by',
                   'text' =>__('languages::translations.filter.updated_by'),
                   'model' => 'updatedBy',
                   'data' => $this->translators
                  ])
            @include('languages::component.select-checkbox-multiple',
               [
                   'id' => 'translations_select_updated_by',
                   'text' =>__('languages::translations.filter.approved_by'),
                   'model' => 'approvedBy',
                   'data' => $this->translators
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
                     'exported' => __('languages::translations.filter.exported'),
                    ]
                ])
        </div>
        <div class="flex flex-col md:flex-row items-center justify-end p-4">
            @if($isAdministrator)
                    @if(!Setting::getCached()->db_loader)
                        @include('languages::component.button',
                          [
                            'clickEvent' => 'exportTranslationsForLanguage',
                            'text' => __('languages::translations.button.export_translation'),
                            'showLoader' => '1'
                          ]
                        )
                    @else
                        @include('languages::component.button',
                          [
                            'clickEvent' => 'exportTranslationsForLanguage("1")',
                            'text' => __('languages::translations.button.export_translation_models'),
                            'showLoader' => '1'
                          ]
                        )
                    @endif


                    @if(!Setting::getCached()->db_loader)
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

                    @include('languages::component.button',
                        [
                         'clickEvent' => 'approveAllTranslations',
                         'text' => __('languages::translations.button.approve_all', ['language_code' => $this->language->code]),
                         'showLoader' => '1'
                        ]
                     )
                    @include('languages::component.button',
                           [
                            'clickEvent' => 'approveAllLanguagesTranslations',
                            'text' => __('languages::translations.button.approve_all_languages', ['language_code' => $this->language->code]),
                            'showLoader' => '1'
                           ]
                        )
                    @endif
        </div>
        @include('languages::component.table', [
                      'thead' => [
                       'id',
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
                       'tbody' => ['id', 'is_vendor', 'namespace', 'group', 'needs_translation', 'approved', 'approver', 'updated_translation', 'updater', 'exported', 'key', 'value', 'old_value'],
                       'action' => ['translate', 'approve_translation', 'needs_translation', 'restore_needs_translation', 'restore_translation'],
                  ])
        <div>
            @include('languages::vendor.livewire.tailwind', ['data' => $data])
        </div>
    </div>
    {{--    Modal --}}
    <div wire:ignore.self id="edit-translation-modal" tabindex="-1" aria-hidden="true"
         class="hidden w-full overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center md:inset-0 h-modal h-screen">
        <div class="relative p-4 w-full h-screen md:h-auto">
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
                <button disabled type="button" class="py-2.5 px-5 me-2 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 inline-flex items-center" wire:loading>
                    <svg aria-hidden="true" role="status" class="inline w-4 h-4 me-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Loading...
                </button>
                <div class="w-full flex flex-wrap gap-4 text-md" wire:loading.remove>
                    <div class="w-1/1">
                        @if($translationExample)
                            <p class="w-full mb-3 font-light text-gray-500 dark:text-gray-400">{!! $translationExample->value ?: "<span style='color:red'>" . __('languages::translations.no_translation_example') . "</span>" !!}</p>
                        @endif
                    </div>
                    <div class="w-full mb-4 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
                        <div class="px-4 py-2 bg-white rounded-t-lg dark:bg-gray-800">
                            @if($translation)
                                <textarea rows="4" wire:model.defer="translatedValue" wire:loading.remove wire:target="openAITranslate"
                                          class="w-full px-0 text-sm text-gray-900 bg-white border-0 dark:bg-gray-800 focus:ring-0 dark:text-white dark:placeholder-gray-400"
                                          required>{{$translation->value}}</textarea>
                               <div wire:loading wire:target="openAITranslate"><svg  aria-hidden="true" role="status"
                                     class="inline w-4 h-4 mr-3 text-white animate-spin"
                                     viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                          fill="#E5E7EB"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                          fill="currentColor"/>
                                </svg>
                               </div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between px-3 py-2 border-t dark:border-gray-600">
                            <button type="submit" wire:click.prevent="updateTranslation" wire:loading.remove wire:target="openAITranslate"
                                    class="inline-flex items-center py-2.5 px-4 text-xs font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                {{ __('languages::translations.action_update')}}
                            </button>
                        </div>
                        @if(Setting::getCached()->enable_open_ai_translations && $this->language->code === config('app.locale'))
                            <div class="flex items-center justify-between px-3 py-2 border-t dark:border-gray-600">
                                <button type="submit" wire:click.prevent="updateAllTranslations" wire:loading.remove wire:target="openAITranslate"
                                        class="inline-flex items-center py-2.5 px-4 text-xs font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                    {{ __('languages::translations.action_update_and_translate_others')}}
                                </button>
                            </div>
                        @endif
                        @if($this->translationExample?->value && Setting::getCached()->enable_open_ai_translations && $this->language->code !== config('app.locale'))
                            <div class="flex items-center justify-between px-3 py-2 border-t dark:border-gray-600">
                                <button type="submit" wire:click.prevent="openAITranslate" wire:loading.remove wire:target="openAITranslate"
                                        class="inline-flex items-center py-2.5 px-4 text-xs font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                    {{ __('languages::translations.action_update_with_open_ai')}}
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="w-full flex flex-wrap gap-5">
                        @foreach($this->translationExamples?->whereNotIn('language_code', [$this->language->code, config('app.locale')]) ?? [] as $transExample)
                            <div x-data="{ open: false }" class="relative">
                                <!-- Button to toggle dropdown -->
                                <button id="dropdownBtnLangTrans{{$transExample->shared_identifier}}{{$transExample->language_id}}"
                                        @click="open = !open"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                                    {{$transExample->language_code }}
                                    <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                    </svg>
                                </button>

                                <!-- Dropdown menu -->
                                <div x-show="open" x-transition
                                     class="z-50 w-full max-h-60 overflow-y-auto rounded-lg shadow-lg bg-white dark:bg-gray-700"
                                     style="display: none;">
                                    <p class="p-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownBtnLangTrans{{$transExample->shared_identifier}}{{$transExample->language_id}}">
                                        {!! $transExample->value ?: "<span style='color:red'>" . __('languages::translations.no_translation_example') . "</span>" !!}
                                    </p>
                                </div>
                            </div>

                        @endforeach
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
