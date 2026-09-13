@if($data->hasPages())
<div class="flex flex-wrap items-center justify-between gap-4 p-4" aria-label="Pagination">
    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $data->firstItem() }} - {{ $data->lastItem() }} / {{ $data->total() }}</p>
    <div class="flex flex-wrap gap-2">
        @if($data->previousPageUrl())<a class="px-3 py-2 border rounded-lg" href="{{ $data->previousPageUrl() }}" rel="prev">Previous</a>@endif
        @foreach($data->getUrlRange(max(1, $data->currentPage() - 2), min($data->lastPage(), $data->currentPage() + 2)) as $number => $url)
            <a href="{{ $url }}" class="px-3 py-2 border rounded-lg {{ $number === $data->currentPage() ? 'bg-blue-700 text-white' : '' }}" @if($number === $data->currentPage()) aria-current="page" @endif>{{ $number }}</a>
        @endforeach
        @if($data->nextPageUrl())<a class="px-3 py-2 border rounded-lg" href="{{ $data->nextPageUrl() }}" rel="next">Next</a>@endif
    </div>
</div>
@endif
