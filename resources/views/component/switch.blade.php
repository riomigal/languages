<div id="{{ $id ?? $name }}" class="mb-4">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" name="{{ $name }}" value="1" class="sr-only peer" @checked(old($name, $checked ?? false))>
        <span class="w-11 h-6 shrink-0 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></span>
        <span class="ml-3 text-sm text-gray-500 dark:text-gray-300">{{ $label }}</span>
    </label>
    @include('languages::component.error', ['field' => $name])
    @if($info ?? false)<p class="text-xs text-gray-500 dark:text-gray-300">{{ $info }}</p>@endif
</div>
