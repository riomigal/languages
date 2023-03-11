<section class="bg-gray-50 dark:bg-gray-900 py-10">
    <div class="flex flex-col max-w-md items-center justify-center px-6 py-8 mx-auto sm:h-screen">
        <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                    {{ __('languages::login.title') }}
                </h1>
                <form class="space-y-4 md:space-y-6" wire:submit.prevent="login">
                    <div>
                        <label for="email"
                               class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('languages::login.email') }}</label>
                        <input type="email" name="email" id="email" wire:model.defer="email"
                               class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                               required="">
                        @include('languages::component.error', ['field' => 'email'])

                    </div>
                    <div>
                        <label for="password"
                               class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('languages::login.password') }}</label>
                        <input type="password" wire:model.defer="password" name="password" id="password"
                               class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                               required="">
                        @include('languages::component.error', ['field' => 'password'])
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="remember" wire:model.defer="remember" aria-describedby="remember"
                                       type="checkbox"
                                       class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800"
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="remember"
                                       class="text-gray-500 dark:text-gray-300">{{ __('languages::login.remember') }}</label>
                            </div>
                        </div>
                    </div>
                    @include('languages::component.button', [
                    'clickEvent' => 'login',
                    'text' =>  __('languages::login.sign_in')
                        ]
                     )
                </form>
            </div>
        </div>
    </div>
</section>
