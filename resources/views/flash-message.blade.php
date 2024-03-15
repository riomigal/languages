<div wire:poll.visible.6000ms="updateNotifications" x-data="{show : @entangle('showMessages')}">
    @if($this->totalNotifications > 0)
        <div class="h-auto fixed bottom-16 right-5 grid items-center m-auto w-auto h-auto z-50 max-w-md p-4"
             role="alert">
            <div x-show="!show"
                 class="flex flex-row items-center w-full max-w-xs p-4 space-x-4 text-gray-500 bg-white divide-x divide-gray-200 rounded-lg shadow dark:text-gray-400 dark:divide-gray-700 space-x dark:bg-gray-800"
                 role="alert">
                <button wire:click="toggleMessages" type="button"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <span
                        class="inline-flex items-center justify-center w-4 h-4 ml-2 text-xs text-red-500 font-semibold bg-white border border-white rounded-full">
                {{$this->totalNotifications}}
                </span>
                </button>
            </div>
            @if($this->totalNotifications > 0)
                <div x-show="show" class="flex flex-row">
                    <div>
                        <div wire:click="markAllAsRead"
                             class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            {{ __('languages::flash-messages.mark_all_read') }}
                        </div>
                    </div>
                    <div>
                        <div wire:click="toggleMessages" class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-medium text-center text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                            {{ __('languages::flash-messages.close') }}
                        </div>
                    </div>
                </div>
            @endif
        <div x-show="show" class="overflow-x:hidden overflow-y-auto max-h-72">
            @foreach($notifications as $id => $notification)
            <div id="id-{{$id}}" class="flex flex-row text-gray-500 bg-white rounded-lg shadow dark:bg-gray-800 dark:text-gray-400 p-4">
                <button wire:click="markAsRead('{{$id}}')" type="button" class="mr-4 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" data-dismiss-target="#id-{{$id}}" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
                <div>
                    <div class="text-sm font-normal">
                        <div class="text-sm font-normal">{!! json_decode($notification)->message !!}</div>
                        <div>{!! json_decode($notification)->date_time ?? '' !!}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>


