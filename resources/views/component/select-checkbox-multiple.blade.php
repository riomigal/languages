<div class="" x-data="{ {{$id}} : false}">
    <button x-on:click="{{$id}} = !{{$id}}"
            class="text-white w-auto bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            type="button">{{ $text }}
        <svg class="w-4 h-4 ml-2" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    <div x-show="{{$id}}"
         class="z-50 absolute w-48 bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 dark:divide-gray-600">
        <ul class="overflow-x:hidden overflow-y-auto max-h-72 p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownRadioBgHoverButton">
            @foreach($data as $key => $value)
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="id{{$key}}" type="checkbox" wire:model="{{$model}}" value="{{$key}}"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                        <label for="id{{$key}}"
                               class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{$value}}</label>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

