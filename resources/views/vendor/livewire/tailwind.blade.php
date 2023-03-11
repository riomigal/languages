<div>
    @php
        $data = $data->links()->getData();
        $paginator =   $data['paginator'];
        $elements =   $data['paginator'];
    @endphp
    @if ($paginator->hasPages())
        @php(isset($this->numberOfPaginatorsRendered[$paginator->getPageName()]) ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++ : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1)
        <div class="flex flex-col p-3">
            <p class="text-sm font-light text-gray-500 dark:text-gray-400 pb-3">{{ __('languages::pagination.total', ['total' => $paginator->total(), 'currentPage' => $paginator->currentPage()]) }}
            </p>
        </div>
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between p-3">
            <div class="flex justify-between flex-1">
                <span>
                    @if (!$paginator->onFirstPage())
                        <button wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                wire:loading.attr="disabled"
                                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                                class="relative inline-flex items-center px-4 py-4 text-lg font-medium text-gray-700 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                                {{ __('languages::pagination.previous') }}
                        </button>
                    @endif
                </span>
                <span>
                    @if ($paginator->hasMorePages())
                        <button wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                                class="relative inline-flex items-center px-4 py-4 ml-3 text-lg font-medium text-gray-700 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                                {{ __('languages::pagination.next') }}
                        </button>
                    @endif
                </span>
            </div>

        </nav>
    @else
        <div class="h-20"></div>
    @endif
</div>
