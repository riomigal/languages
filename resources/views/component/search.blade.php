<form method="GET" action="{{ url()->current() }}" data-search class="flex gap-2 items-center">
    @include('languages::partials.query-fields', ['except' => ['search', 'page', 'create', 'password']])
    <label for="search" class="sr-only">Search</label>
    <input type="search" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Search" autocomplete="off"
           class="input input-bordered w-full">
    <button type="submit" class="btn btn-ghost btn-sm text-primary">Search</button>
</form>
