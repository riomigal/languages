@extends('languages::layouts.app')
@section('page')
<section class="pb-8">
    <div class="mx-auto px-4 lg:px-12" style="max-width:{{ $maxWidth ?? 1920 }}px">
        @yield('content')
    </div>
</section>
@endsection
