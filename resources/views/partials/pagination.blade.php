@if($data->hasPages())
<div class="flex flex-wrap items-center justify-between gap-4 p-4" aria-label="Pagination">
    <p class="text-sm text-base-content/70">{{ $data->firstItem() }} - {{ $data->lastItem() }} / {{ $data->total() }}</p>
    <div class="join flex-wrap">
        @if($data->previousPageUrl())<a class="join-item btn btn-sm" href="{{ $data->previousPageUrl() }}" rel="prev">Previous</a>@endif
        @foreach($data->getUrlRange(max(1, $data->currentPage() - 2), min($data->lastPage(), $data->currentPage() + 2)) as $number => $url)
            <a href="{{ $url }}" class="join-item btn btn-sm {{ $number === $data->currentPage() ? 'btn-primary' : '' }}" @if($number === $data->currentPage()) aria-current="page" @endif>{{ $number }}</a>
        @endforeach
        @if($data->nextPageUrl())<a class="join-item btn btn-sm" href="{{ $data->nextPageUrl() }}" rel="next">Next</a>@endif
    </div>
</div>
@endif
