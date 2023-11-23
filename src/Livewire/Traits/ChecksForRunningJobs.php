<?php

namespace Riomigal\Languages\Livewire\Traits;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Riomigal\Languages\Livewire\LanguagesToastMessage;

trait ChecksForRunningJobs
{

    /** Checks if another job is running
     * @return bool
     */
    protected function anotherJobIsRunning(): bool
    {

        if (DB::table('jobs')
            ->where('queue', config('languages.queue_name'))
            ->exists()) {
            $this->jobIsRunningMessage();
            return true;
        }

        $hosts = array_filter(explode(',', config('languages.multiple_db_hosts')));

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
                    $this->jobIsRunningMessage();
                    return true;
                }
                try{
                    if($response['process_running']) {
                        $this->jobIsRunningMessage();
                        return true;
                    }
                } catch(\Exception $e) {
                    $this->jobIsRunningMessage();
                    return true;
                }
            }
        }

        $this->jobIsRunningMessage(false);
        return false;
    }

    protected function jobIsRunningMessage(bool $isRunning = true): void
    {
        if($isRunning) {
            $this->emit('showToast', __('languages::global.import.processing_no_action'), LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
        } else {
            $this->emit('showToast', __('languages::global.import.start_message'), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], 6000);
        }

    }
}
