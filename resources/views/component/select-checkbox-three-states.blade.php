<div class="relative">
    <button type="button" data-toggle="state-filter-options" aria-controls="state-filter-options" aria-expanded="false"
            class="text-white bg-blue-700 hover:bg-blue-800 rounded-lg text-sm px-4 py-2.5 dark:bg-blue-600">{{ __('languages::translations.checkbox_filter_button') }}</button>
    <div id="state-filter-options" hidden class="z-30 absolute w-56 bg-white rounded-lg shadow dark:bg-gray-700 p-3">
        @foreach($filters as $key => $state)
            <form method="GET" action="{{ url()->current() }}" data-state-filter>
                @include('languages::partials.query-fields', ['except' => [$key]])
                @if($state !== false)<input type="hidden" name="{{ $key }}" value="{{ $state === null ? 'true' : 'false' }}">@endif
                <button type="submit" aria-label="{{ __('languages::translations.filter.' . $key) }}" title="{{ $state === null ? 'All' : ($state ? 'Matching' : 'Non-matching') }}"
                        class="text-white {{ $state === null ? 'bg-gray-700' : ($state ? 'bg-green-700' : 'bg-red-700') }} font-medium rounded-lg text-sm px-5 py-2.5 mb-2 w-full">
                    {{ __('languages::translations.filter.' . $key) }}
                    <span class="sr-only">{{ $state === null ? 'All' : ($state ? 'Matching' : 'Non-matching') }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
