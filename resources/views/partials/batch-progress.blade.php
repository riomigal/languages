<div id="batch-progress" data-url="{{ route('languages.batch.progress') }}" data-batch-id="{{ session('batch_id', $batch['id']) }}"
     @if($batch['finished']) hidden @endif class="card card-body bg-base-100 shadow-sm mx-auto my-4 max-w-2xl p-4" role="status" aria-live="polite">
    <p data-batch-label>Batch progress: <span data-progress-value>{{ $batch['progress'] }}</span>%</p>
    <progress max="100" value="{{ $batch['progress'] }}" class="progress progress-primary w-full" aria-label="Batch progress"></progress>
</div>
