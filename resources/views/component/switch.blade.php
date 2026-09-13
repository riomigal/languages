<div id="{{ $id ?? $name }}" class="mb-4">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="inline-flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="{{ $name }}" value="1" class="toggle toggle-primary shrink-0" @checked(old($name, $checked ?? false))>
        <span class="text-sm text-base-content/70">{{ $label }}</span>
    </label>
    @include('languages::component.error', ['field' => $name])
    @if($info ?? false)<p class="text-xs text-base-content/70">{{ $info }}</p>@endif
</div>
