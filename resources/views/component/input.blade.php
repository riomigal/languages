<div id="{{ $id ?? $name }}" class="w-full mb-6">
    <label for="field-{{ $id ?? $name }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $label ?? $name }}</label>
    <input id="field-{{ $id ?? $name }}" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ ($type ?? 'text') === 'password' ? '' : old($name, $value ?? '') }}"
           class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600"
           @if($required ?? false) required @endif>
    @include('languages::component.error', ['field' => $name])
    @if($info ?? false)<p class="text-xs text-gray-500 dark:text-gray-300">{{ $info }}</p>@endif
</div>
