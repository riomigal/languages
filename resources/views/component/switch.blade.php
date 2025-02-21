@props([
    'label',
    'name',
    'showErrors' => true,
    'type' => 'text',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'info' => false,
])
<div id="{{ $id ?: $name}}" class="grid gap-4 sm:grid-cols-2 sm:gap-6 mb-4">
    <div class="sm:col-span-2">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model="{{$name}}" value="" class="sr-only peer" checked @if($required) required @endif>
            <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
        </label>
        <span class="ml-3 font-light text-gray-500 dark:text-gray-300 text-sm">{{ $label }}</span>
    </div>
    @if($showErrors)
        @include('languages::component.error', ['field' => $name])
    @endif
    @if($info)
        <span class="text-xs font-light text-gray-500 dark:text-gray-300 pb-3 mb-1">{{$info}}</span>
    @endif
</div>
