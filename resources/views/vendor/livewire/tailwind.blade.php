<div>
    @php
        $data = $data->links()->getData();
        $paginator = $data['paginator'];

        // Calculate range for page numbers
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pageRange = range(max($currentPage - 2, 1), min($currentPage + 2, $lastPage));

        // Ensure we always show the first and last page
        if ($currentPage > 3) {
            array_unshift($pageRange, 1);
        }

        if ($currentPage < $lastPage - 2) {
            array_push($pageRange, $lastPage);
        }
    @endphp

    @if ($paginator->hasPages())
        @php(isset($this->numberOfPaginatorsRendered[$paginator->getPageName()]) ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++ : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1)
        <div class="flex flex-col p-3">
            <p class="text-sm font-light text-gray-500 dark:text-gray-400 pb-3">
                {{ __('languages::pagination.total', ['total' => $paginator->total(), 'currentPage' => $paginator->currentPage()]) }}
            </p>
        </div>

        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center p-3">
            <div class="flex space-x-2">
                {{-- Previous Page Button --}}
                @if (!$paginator->onFirstPage())
                    <button wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="relative inline-flex items-center px-4 py-2 text-lg font-medium text-gray-700 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                        &laquo; {{ __('languages::pagination.previous') }}
                    </button>
                @endif

                {{-- Numeric Page Numbers --}}
                @foreach ($pageRange as $page)
                    {{-- Show Ellipsis if there's a gap in the page range --}}
                    @if ($page == 1 && !in_array(1, $pageRange))
                        <span class="text-gray-500">...</span>
                    @elseif ($page == $lastPage && !in_array($lastPage, $pageRange))
                        <span class="text-gray-500">...</span>
                    @else
                        <button wire:click="gotoPage({{ $page }})"
                                wire:loading.attr="disabled"
                                dusk="gotoPage{{ $page }}"
                                class="relative inline-flex items-center px-4 py-2 text-lg font-medium {{ $paginator->currentPage() == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' }} border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach

                {{-- Next Page Button --}}
                @if ($paginator->hasMorePages())
                    <button wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="relative inline-flex items-center px-4 py-2 text-lg font-medium text-gray-700 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                        {{ __('languages::pagination.next') }} &raquo;
                    </button>
                @endif
            </div>
        </nav>
    @else
        <div class="h-20"></div>
    @endif
</div>
