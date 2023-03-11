<?php

namespace Riomigal\Languages\Livewire\Traits;

use Illuminate\Support\Facades\DB;
use Riomigal\Languages\Jobs\ExportTranslationJob;
use Riomigal\Languages\Jobs\ExportTranslationsJob;
use Riomigal\Languages\Jobs\FindMissingTranslationsJob;
use Riomigal\Languages\Jobs\ImportLanguagesJob;
use Riomigal\Languages\Jobs\ImportTranslationsJob;
use Riomigal\Languages\Jobs\MassCreateTranslationsJob;
use Riomigal\Languages\Livewire\LanguagesToastMessage;

trait HasBatchProcess
{

    /** Checks if another job is running
     * @return bool
     */
    protected function anotherJobIsRunning(): bool
    {

        if (DB::table('jobs')
            ->where('queue', config('languages.queue_name'))
            ->exists()) {
            $this->emit('showToast', __('languages::global.import.processing_no_action'), LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
            return true;
        }
        $this->emit('showToast', __('languages::global.import.start_message'), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], 6000);
        return false;
    }
}
