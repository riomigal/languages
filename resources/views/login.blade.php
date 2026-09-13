@extends('languages::layouts.app')
@section('page')
<section class="py-10">
    <div class="flex flex-col max-w-md items-center justify-center px-6 py-8 mx-auto">
        <div class="card w-full bg-base-100 shadow-md">
            <div class="card-body p-6 gap-6 sm:p-8">
                <h1 class="card-title text-xl md:text-2xl">
                    {{ __('languages::login.title') }}
                </h1>
                <form method="POST" action="{{ route('languages.login.submit') }}" class="space-y-4 md:space-y-6">
                    @csrf
                    <div>
                        <label for="email"
                               class="label mb-2 text-sm font-medium text-base-content">{{ __('languages::login.email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="input input-bordered w-full"
                               required="">
                        @include('languages::component.error', ['field' => 'email'])

                    </div>
                    <div>
                        <label for="password"
                               class="label mb-2 text-sm font-medium text-base-content">{{ __('languages::login.password') }}</label>
                        <input type="password" name="password" id="password"
                               class="input input-bordered w-full"
                               required="">
                        @include('languages::component.error', ['field' => 'password'])
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="remember" name="remember" value="1"
                                       type="checkbox"
                                       class="checkbox checkbox-primary checkbox-sm"
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="remember"
                                       class="text-base-content/70">{{ __('languages::login.remember') }}</label>
                            </div>
                        </div>
                    </div>
                    @include('languages::component.button', [
                    'type' => 'submit',
                    'text' =>  __('languages::login.sign_in')
                        ]
                     )
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
