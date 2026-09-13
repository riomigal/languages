@foreach(request()->only(['search', 'needs_translation', 'approved', 'updated_translation', 'is_vendor', 'exported', 'types', 'updatedBy', 'approvedBy', 'selectedLanguages']) as $parameter => $queryValue)
    @if(!in_array($parameter, $except ?? [], true))
        @if(is_array($queryValue))
            @foreach($queryValue as $entry)
                @if(is_scalar($entry))<input type="hidden" name="{{ $parameter }}[]" value="{{ $entry }}">@endif
            @endforeach
        @else
            <input type="hidden" name="{{ $parameter }}" value="{{ $queryValue }}">
        @endif
    @endif
@endforeach
