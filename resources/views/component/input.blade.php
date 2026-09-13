<div id="{{ $id ?? $name }}" class="w-full mb-6">
    <label for="field-{{ $id ?? $name }}" class="label mb-2 text-sm font-medium text-base-content">{{ $label ?? $name }}</label>
    <input id="field-{{ $id ?? $name }}" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ ($type ?? 'text') === 'password' ? '' : old($name, $value ?? '') }}"
           class="input input-bordered w-full"
           @if($required ?? false) required @endif>
    @include('languages::component.error', ['field' => $name])
    @if($info ?? false)<p class="text-xs text-base-content/70">{{ $info }}</p>@endif
</div>
