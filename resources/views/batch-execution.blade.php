@if(auth()->check() && auth()->user()->admin)
    <div x-data="{open: @entangle('batchId'), progress: @entangle('progress')}">
        <div
            x-show="open"
            wire:poll.visible.250ms="batchProgress"
            class="fixed bottom-5 right-5 max-w-md my-2 mx-auto z-50 p-2 bg-white dark:bg-gray-900 dark:text-gray-200 text-xs">
            <span>Import/Export in progress</span>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${progress}%;`"></div>
            </div>
        </div>
    </div>
@endif

