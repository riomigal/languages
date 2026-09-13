<div class="relative">
    <button type="button" data-toggle="{{ $id }}-options" aria-controls="{{ $id }}-options" aria-expanded="false"
            class="text-white bg-blue-700 hover:bg-blue-800 rounded-lg text-sm px-4 py-2.5 dark:bg-blue-600">{{ $text }}</button>
    <div id="{{ $id }}-options" hidden class="z-30 absolute w-56 bg-white rounded-lg shadow dark:bg-gray-700 p-3 max-h-72 overflow-y-auto">
        @foreach($options as $key => $option)
            <label class="flex gap-2 p-2 text-sm text-gray-900 dark:text-gray-300">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $key }}" @checked(in_array((string) $key, array_map('strval', $selected ?? []), true))>
                {{ $option }}
            </label>
        @endforeach
        @if($apply ?? false)<button type="submit" class="p-2 text-blue-700 dark:text-blue-300">Apply</button>@endif
    </div>
    @include('languages::component.error', ['field' => $name])
    @include('languages::component.error', ['field' => $name . '.*'])
</div>
