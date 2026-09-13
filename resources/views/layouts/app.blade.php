<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('languages::global.app_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/languages/css/app.css') }}">
    <script>
        try {
            const theme = localStorage.getItem('color-theme') ?? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.dataset.theme = theme;
        } catch (_) {}
    </script>
    <script type="module" src="{{ asset('vendor/languages/js/app.js') }}"></script>
    @stack('styles')
</head>
<body class="bg-white min-h-screen border-gray-200 lg:px-6 py-2.5 dark:bg-gray-900 dark:border-gray-800 dark:text-white">
@include('languages::partials.toast')
<nav class="bg-white border-gray-200 px-2 sm:px-4 py-2.5 rounded dark:bg-gray-900 mx-4">
    <div class="container flex flex-wrap gap-4 items-center justify-between mx-auto">
        <a href="{{ route('languages.languages') }}" class="text-xl font-semibold dark:text-white">{{ __('languages::navbar.brand') }}</a>
        <span>v {{ \Riomigal\Languages\LanguagesServiceProvider::$version }}</span>
        <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 rounded-lg text-sm p-2.5" aria-label="Toggle dark mode">Light / Dark</button>
        @if(auth(config('languages.translator_guard'))->check())
            <button type="button" data-toggle="mobile-menu" aria-controls="mobile-menu" aria-expanded="false" class="md:hidden p-2">Open main menu</button>
            <div id="mobile-menu" class="hidden md:flex w-full md:w-auto">
                <ul class="flex flex-col md:flex-row gap-6 p-4 text-gray-700 dark:text-gray-300">
                    <li><a href="{{ route('languages.languages') }}">{{ __('languages::navbar.languages') }}</a></li>
                    <li><a href="{{ route('languages.manual') }}">{{ __('languages::navbar.manual') }}</a></li>
                    @if($isAdministrator)
                        <li><a href="{{ route('languages.translators') }}">{{ __('languages::navbar.translators') }}</a></li>
                        <li><a href="{{ route('languages.settings') }}">{{ __('languages::navbar.settings') }}</a></li>
                    @endif
                    <li><form method="POST" action="{{ route('languages.logout') }}">@csrf<button type="submit">{{ __('languages::navbar.logout') }}</button></form></li>
                </ul>
            </div>
        @endif
    </div>
</nav>
@if(auth(config('languages.translator_guard'))->check())
    @include('languages::partials.batch-progress')
    @include('languages::partials.notifications')
@endif
@yield('page')
@stack('scripts')
</body>
</html>
