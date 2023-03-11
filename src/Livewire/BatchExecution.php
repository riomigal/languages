<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Riomigal\Languages\Models\Translator;

class BatchExecution extends Component
{

    /**
     * @var Translator
     */
    public Translator $authUser;

    /**
     * @var null|string
     */
    public null|string $batchId = null;

    /**
     * @var int
     */
    public int $progress = 0;

    /**
     * @var string[]
     */
    protected $listeners = [
        'startBatchProgress' => 'progressBatch'
    ];

    public function mount(): void
    {
        $batch = DB::table('job_batches')
            ->where('name', config('languages.batch_name'))
            ->whereNull('finished_at')->whereNull('cancelled_at')->first();
        $this->batchId = ($batch) ? $batch->id : null;
    }


    /**
     * @param string $id
     * @return void
     */
    public function progressBatch(string $id): void
    {
        $this->batchId = $id;
    }

    /**
     * @return void
     */
    public function batchProgress(): void
    {
        if ($this->batchId) {
            $batch = Bus::findBatch($this->batchId);
            if ($batch->finished()) {
                $this->reset('batchId', 'progress');
            } else {
                $this->progress = $batch->progress();
            }
        }
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::batch-execution')
            ->layout('languages::layouts.app');
    }
}
