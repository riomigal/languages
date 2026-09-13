<div class="dropdown dropdown-open">
    <button type="button" data-toggle="{{ $id }}-options" aria-controls="{{ $id }}-options" aria-expanded="false"
            class="btn btn-primary">{{ $text }}</button>
    <div id="{{ $id }}-options" hidden class="dropdown-content menu bg-base-100 rounded-box shadow-lg z-30 w-56 p-3 max-h-72 overflow-y-auto flex-nowrap">
        @foreach($options as $key => $option)
            <label class="flex items-center gap-2 p-2 text-sm cursor-pointer rounded-field hover:bg-base-200">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $key }}" class="checkbox checkbox-primary checkbox-sm shrink-0" @checked(in_array((string) $key, array_map('strval', $selected ?? []), true))>
                {{ $option }}
            </label>
        @endforeach
        @if($apply ?? false)<button type="submit" class="btn btn-ghost btn-sm text-primary">Apply</button>@endif
    </div>
    @include('languages::component.error', ['field' => $name])
    @include('languages::component.error', ['field' => $name . '.*'])
</div>
