<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('languages::global.app_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/languages/css/app.css') }}">
    <script>
        let theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        try {
            const savedTheme = localStorage.getItem('color-theme');
            if (savedTheme === 'light' || savedTheme === 'dark') theme = savedTheme;
        } catch (_) {}
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.dataset.theme = theme;
    </script>
    <script type="module" src="{{ asset('vendor/languages/js/app.js') }}"></script>
    @stack('styles')
</head>
<body class="min-h-screen bg-base-200 text-base-content lg:px-6 py-2.5">
@include('languages::partials.toast')
<nav class="navbar bg-base-100 rounded-box shadow-sm px-2 sm:px-4 mx-4 w-auto">
    <div class="container flex flex-wrap gap-4 items-center justify-between mx-auto">
        <a href="{{ route('languages.languages') }}" class="btn btn-ghost text-xl">{{ __('languages::navbar.brand') }}</a>
        <span class="badge badge-ghost">v {{ \Riomigal\Languages\LanguagesServiceProvider::$version }}</span>
        <button id="theme-toggle" type="button" class="btn btn-ghost btn-sm" aria-label="Toggle dark mode">Light / Dark</button>
        @if(auth(config('languages.translator_guard'))->check())
            <button type="button" data-toggle="mobile-menu" aria-controls="mobile-menu" aria-expanded="false" class="btn btn-ghost md:hidden">Open main menu</button>
            <div id="mobile-menu" class="hidden md:flex w-full md:w-auto">
                <ul class="menu md:menu-horizontal w-full md:w-auto">
                    <li><a href="{{ route('languages.languages') }}">{{ __('languages::navbar.languages') }}</a></li>
                    <li><a href="{{ route('languages.manual') }}">{{ __('languages::navbar.manual') }}</a></li>
                    @if($isAdministrator)
                        <li><a href="{{ route('languages.translators') }}">{{ __('languages::navbar.translators') }}</a></li>
                        <li><a href="{{ route('languages.settings') }}">{{ __('languages::navbar.settings') }}</a></li>
                    @endif
                    <li><form method="POST" action="{{ route('languages.logout') }}" class="p-0">@csrf<button type="submit" class="btn btn-ghost btn-sm w-full justify-start">{{ __('languages::navbar.logout') }}</button></form></li>
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
