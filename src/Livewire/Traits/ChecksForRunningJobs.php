<?php

namespace Riomigal\Languages\Livewire\Traits;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Riomigal\Languages\Livewire\LanguagesToastMessage;
use Riomigal\Languages\Models\Setting;

trait ChecksForRunningJobs
{

    /** Checks if another job is running
     * @return bool
     */
    protected function anotherJobIsRunning(bool $fromCommandLine = false): bool
    {
        if (DB::table('jobs')
            ->where('queue', config('languages.queue_name'))
            ->exists() || DB::table('job_batches')->where('name', config('languages.batch_name'))
            ->whereNull('cancelled_at')
            ->whereNull('finished_at')
            ->exists()) {
            $this->jobIsRunningMessage($fromCommandLine);
            return true;
        }

        $hosts = array_filter(explode(',', Setting::getDomains()));

        if($hosts) {
            $hosts = array_diff($hosts, [request()->getSchemeAndHttpHost()]);
            $path = route('languages.api.jobs-running', [], false);
            $responses = Http::pool(function (Pool $pool) use ($hosts, $path) {
                $poolArray = [];
                foreach($hosts as $host) {
                   $poolArray[] = $pool->post($host . $path, ['api_key' => config('languages.api_shared_api_key')]);
                }
                return $poolArray;
            });
            foreach($responses as $response) {
                if(!$response->ok()) {
                    $this->jobIsRunningMessage($fromCommandLine);
                    return true;
                }

                try{
                    if($response['process_running']) {
                        $this->jobIsRunningMessage($fromCommandLine);
                        return true;
                    }
                } catch(\Exception $e) {
                    $this->jobIsRunningMessage($fromCommandLine);
                    return true;
                }
            }
        }

        $this->jobIsRunningMessage($fromCommandLine, false);
        return false;
    }

    protected function jobIsRunningMessage(bool $fromCommandLine, bool $isRunning = true): void
    {
        if($fromCommandLine) {
            if($isRunning) $this->info('Another Process is running.');
        } else {
            if ($isRunning) {
                $this->emit('showToast', __('languages::global.import.processing_no_action'), LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
            } else {
                $this->emit('showToast', __('languages::global.import.start_message'), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], 6000);
            }
        }

    }
}
