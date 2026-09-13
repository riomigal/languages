<form method="GET" action="{{ url()->current() }}" data-search class="flex gap-2 items-center">
    @include('languages::partials.query-fields', ['except' => ['search', 'page', 'create', 'password']])
    <label for="search" class="sr-only">Search</label>
    <input type="search" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Search" autocomplete="off"
           class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    <button type="submit" class="p-2 text-blue-700 dark:text-blue-300">Search</button>
</form>
