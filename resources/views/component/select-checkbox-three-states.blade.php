<div class="" x-data="{ {{$id}} : false}">
    <button x-on:click="{{$id}} = !{{$id}}"
            class="text-white w-auto bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            type="button">{{ $text }}
        <svg class="w-4 h-4 ml-2" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    <div class="w-full absolute z-50">
        <div x-show="{{$id}}"
             class="z-50 absolute w-auto bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 dark:divide-gray-600">
            <ul class="overflow-x:hidden overflow-y-auto max-h-72 p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownRadioBgHoverButton">
                @foreach($data as $key => $value)
                    <li x-data="{buttonColor: @entangle($key)}">
                        <button type="button" wire:model="{{ $key }}" wire:click="updateThreeStatesFilter('{{$key}}')" class="text-white bg-green-700
                        font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 focus:outline-none" x-show="buttonColor === true">
                            {{$value}}</button>
                        <button type="button" wire:model="{{ $key }}" wire:click="updateThreeStatesFilter('{{$key}}')" class="text-white bg-red-700
                        font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 focus:outline-none" x-show="buttonColor === false">
                            {{$value}}</button>
                        <button type="button" wire:model="{{ $key }}" wire:click="updateThreeStatesFilter('{{$key}}')" class="text-white bg-gray-700
                        font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 focus:outline-none" x-show="buttonColor === null">
                            {{$value}}</button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <input class="bg-blue-700 bg-red-700 bg-gray-700 invisible" hidden>
</div>

