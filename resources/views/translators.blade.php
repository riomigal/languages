@extends('languages::component.table-section', ['maxWidth' => 1300])
@section('content')
    @include('languages::component.table-h1-heading', ['title' => __('languages::navbar.translators')])
    {{--CreateForm--}}
    @if($showForm && !$showUpdatePasswordForm)
        <form id="createOrUpdateForm" class="pb-4">
            <div class="grid md:grid-cols-2 md:gap-6">
                <div class="relative z-0 w-full mb-6 group">
                    <input type="email" name="email" id="email" wire:model.defer="email"
                           class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                           placeholder=" " required/>
                    <label for="email"
                           class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                        {{ __('languages::translators.form.label.email') }}</label>
                    @include('languages::component.error', ['field' => 'email'])

                </div>
                <div class="relative z-0 w-full mb-6 group">
                    <input type="tel" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" name="phone" id="phone"
                           wire:model.defer="phone"
                           class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                           placeholder=" " required/>
                        <label for="phone"
                               class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.phone') }}</label>
                        @include('languages::component.error', ['field' => 'phone'])
                    </div>
                </div>
                @if(!$this->translator)
                    <div class="grid md:grid-cols-2 md:gap-6">
                        <div class="relative z-0 w-full mb-6 group">
                            <input type="password" name="password" id="password" wire:model.defer="password"
                                   class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                   placeholder=" " required/>
                            <label for="password"
                                   class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.password') }}</label>
                            @include('languages::component.error', ['field' => 'password'])
                        </div>
                        <div class="relative z-0 w-full mb-6 group">
                            <input type="password" name="repeat_password" id="password_confirmation"
                                   wire:model.defer="password_confirmation"
                                   class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                   placeholder=" " required/>
                            <label for="password_confirmation"
                                   class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.password_confirmation') }}</label>
                            @include('languages::component.error', ['field' => 'password_confirmation'])
                        </div>
                    </div>
                @endif
                <div class="grid md:grid-cols-2 md:gap-6">
                    <div class="relative z-0 w-full mb-6 group">
                        <input type="text" name="first_name" id="first_name"
                               wire:model.defer="first_name"
                               class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                               placeholder=" " required/>
                        <label for="first_name"
                               class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.first_name') }}</label>
                        @include('languages::component.error', ['field' => 'first_name'])
                    </div>
                    <div class="relative z-0 w-full mb-6 group">
                        <input type="text" name="last_name" id="last_name"
                               wire:model.defer="last_name"
                               class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                               placeholder=" " required/>
                        <label for="last_name"
                               class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ __('languages::translators.form.label.last_name') }}</label>
                        @include('languages::component.error', ['field' => 'last_name'])
                    </div>
                </div>
                <div class="grid md:grid-cols-2 md:gap-6">
                    <div class="relative z-50 w-full mb-6 group">
                        <label for="languages"
                               class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">   {{ __('languages::translators.form.label.languages') }}   </label>

                        @include('languages::component.select-checkbox-multiple',
                [
                    'id' => 'languages_select_translators',
                    'text' =>__('languages::translators.form.label.languages'),
                    'model' => 'languages',
                    'data' => collect($availableLanguages)->pluck('name', 'id')->all()
                   ])
                        @include('languages::component.error', ['field' => 'languages'])
                        <p class="text-sm font-light text-gray-500 dark:text-gray-300 p-3">
                            {{ __('languages::translators.form.info.languages') }}            </p>
                    </div>
                    <div class="relative z-0 w-full mb-6 group">
                        <div class="flex items-center mb-4">
                            <input id="admin" type="checkbox" value="" wire:model.defer="admin"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="admin"
                                   class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"> {{ __('languages::translators.form.label.admin') }}</label>
                        </div>
                        @include('languages::component.error', ['field' => 'admin'])
                        <p class="text-sm font-light text-gray-500 dark:text-gray-300 pb-3">
                            {{ __('languages::translators.form.info.admin') }}            </p>
                    </div>
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
                     'model' => 'selectedLanguages',
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
