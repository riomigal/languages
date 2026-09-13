<div class="dropdown dropdown-open">
    <button type="button" data-toggle="state-filter-options" aria-controls="state-filter-options" aria-expanded="false"
            class="btn btn-primary">{{ __('languages::translations.checkbox_filter_button') }}</button>
    <div id="state-filter-options" hidden class="dropdown-content menu bg-base-100 rounded-box shadow-lg z-30 w-56 p-3 gap-2">
        @foreach($filters as $key => $state)
            <form method="GET" action="{{ url()->current() }}" data-state-filter>
                @include('languages::partials.query-fields', ['except' => [$key]])
                @if($state !== false)<input type="hidden" name="{{ $key }}" value="{{ $state === null ? 'true' : 'false' }}">@endif
                <button type="submit" aria-label="{{ __('languages::translations.filter.' . $key) }}" title="{{ $state === null ? 'All' : ($state ? 'Matching' : 'Non-matching') }}"
                        class="btn {{ $state === null ? 'btn-neutral' : ($state ? 'btn-success' : 'btn-error') }} w-full">
                    {{ __('languages::translations.filter.' . $key) }}
                    <span class="sr-only">{{ $state === null ? 'All' : ($state ? 'Matching' : 'Non-matching') }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
