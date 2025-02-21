@extends('languages::component.table-section', ['maxWidth' => 1300])
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::navbar.translators')])
    {{--CreateForm--}}
    @if($showForm && !$showUpdatePasswordForm)
        <form id="createOrUpdateForm" class="pb-4">
            <div class="grid md:grid-cols-2 md:gap-6">
                @include('languages::component.input', [
                        'label' => __('languages::translators.form.label.email'),
                        'name' => 'email',
                        'required' => true,
                        'type' => 'email',
                ])
                @include('languages::component.input', [
                        'label' => __('languages::translators.form.label.phone'),
                        'name' => 'phone',
                        'required' => true,
                        'type' => 'phone',
                ])
                @if(!$this->translator)
                        @include('languages::component.input', [
                            'label' => __('languages::translators.form.label.password'),
                            'name' => 'password',
                            'required' => true,
                            'type' => 'password',
                         ])
                        @include('languages::component.input', [
                            'label' => __('languages::translators.form.label.password_confirmation'),
                            'name' => 'password_confirmation',
                            'required' => true,
                            'type' => 'password',
                         ])
                @endif
                @include('languages::component.input', [
                            'label' => __('languages::translators.form.label.first_name'),
                            'name' => 'first_name',
                            'required' => true,
                         ])
                @include('languages::component.input', [
                            'label' => __('languages::translators.form.label.last_name'),
                            'name' => 'last_name',
                            'required' => true,
                         ])
            </div>
            <div class="grid md:grid-cols-2 md:gap-6">
                    @include('languages::component.select-checkbox-multiple',
                    [
                        'id' => 'languages_select_translators',
                        'text' =>__('languages::translators.form.label.languages'),
                        'label' => __('languages::translators.form.label.languages'),
                        'name' => 'languages',
                        'data' => collect($availableLanguages)->pluck('name', 'id')->all(),
                        'info' => __('languages::translators.form.info.languages'),
                       ])
                @include('languages::component.switch', [
                           'label' => __('languages::translators.form.label.admin'),
                           'name' => 'admin',
                           'required' => true,
                           'type' => 'checkbox',
                           'info' => __('languages::translators.form.info.admin'),
                        ])
            </div>
            @if($this->translator)
                @include('languages::component.button',
                      [
                      'clickEvent' => 'update',
                      'text' =>  __('languages::translators.form.button.update')
                      ]
                  )
            @else
                @include('languages::component.button',
                      [
                      'clickEvent' => 'create',
                      'text' =>  __('languages::translators.form.button.create')
                      ]
                  )
            @endif
            @include('languages::component.button',
              [
              'clickEvent' => 'closeForm',
              'text' => __('languages::translators.form.button.close')
              ]
            )
            @if($this->translator)
                @include('languages::component.button',
                          [
                          'clickEvent' => 'toggleUpdatePasswordForm',
                          'text' =>  __('languages::translators.form.button.update_password')
                          ]
                      )
            @endif
            @if($this->translator && \Riomigal\Languages\Models\Setting::getCached()->enable_pending_notifications)
                @include('languages::component.button',
                          [
                          'clickEvent' => 'notifyPendingTranslations',
                          'text' =>  __('languages::translators.form.button.pending_translations_notification')
                          ]
                      )
            @endif
        </form>
        @endif
    @if($showUpdatePasswordForm && $this->translator)
        <form>
            <h3 class="mb-3 bold dark:text-white ">{{ __('languages::translators.form.update_password_title', ['email' => $this->translator->email])}}</h3>
            <div class="grid md:grid-cols-2 md:gap-6">
                <div class="relative z-0 w-full mb-6 group">
                    <input type="password" name="new_password" id="new_password" wire:model.defer="new_password"
                           class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                           placeholder=" " required/>
                    <label for="new_password"
                           class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.password') }}</label>
                    @include('languages::component.error', ['field' => 'new_password'])
                </div>
                <div class="relative z-0 w-full mb-6 group">
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                           wire:model.defer="new_password_confirmation"
                           class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                           placeholder=" " required/>
                    <label for="new_password_confirmation"
                           class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.password_confirmation') }}</label>
                    @include('languages::component.error', ['field' => 'new_password_confirmation'])
                </div>
            </div>

                @include('languages::component.button',
                          [
                          'clickEvent' => 'updateNewPassword',
                          'text' =>  __('languages::translators.form.button.update_password')
                          ]
                      )
            @include('languages::component.button',
                 [
                 'clickEvent' => 'toggleUpdatePasswordForm',
                 'text' => __('languages::translators.form.button.close')
                 ]
               )
        </form>
    @endif
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                @include('languages::component.search')

                @include('languages::component.button',
                 [
                 'clickEvent' => 'showForm',
                 'text' => __('languages::translators.button_toggle_create_form')
                 ]
             )
                @include('languages::component.select-checkbox-multiple',
                 [
                     'id' => 'languages_select_translators',
                     'text' =>  __('languages::translators.button_filter_languages'),
                     'name' => 'selectedLanguages',
                     'data' => collect($this->availableLanguages)->pluck('name', 'id')->all()
                    ])

            </div>
            @include('languages::component.table', [
                      'thead' => [
                     __('languages::translators.table.head.id'),
                      __('languages::translators.table.head.first_name'),
                      __('languages::translators.table.head.last_name'),
                      __('languages::translators.table.head.email'),
                      __('languages::translators.table.head.phone'),
                      __('languages::translators.table.head.admin'),
                      __('languages::translators.table.head.languages'),
                       ],
                       'tbody' => ['id', 'first_name','last_name', 'email', 'phone', 'admin', "languages"],
                       'relations'=>  ['languages' => 'name'],
                       'action' => ['delete', 'edit']
                  ])
            <div>
                @include('languages::vendor.livewire.tailwind', ['data' => $data])
            </div>
        </div>
@endsection
