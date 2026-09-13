@extends('languages::layouts.app')
@section('page')
<section class="bg-gray-50 dark:bg-gray-900 pb-8">
    <div class="mx-auto px-4 lg:px-12" style="max-width:{{ $maxWidth ?? 1920 }}px">
        @yield('content')
    </div>
</section>
@endsection
