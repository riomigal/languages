@props([
    'name',
    'label' => null,
    'showErrors' => true,
    'type' => 'text',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'info' => false,
])

<div id="{{ $id ?: $name}}" class="relative z-0 w-full mb-6 group">
    <input type="{{$type}}" name="{{$name}}"
           wire:model.debounce.500ms="{{$name}}"
           class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
           @if($placeholder) placeholder="{{$placeholder}}"  @endif
           @if($required) required @endif/>
    @if($label)
    <label for="{{$name}}"
           class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{ $label }}</label>
    @endif
    @if($showErrors)
    @include('languages::component.error', ['field' => $name])
    @endif
    @if($info)
        <span class="text-xs font-light text-gray-500 dark:text-gray-300 pb-3 my-1">{{$info}}</span>
    @endif
</div>
