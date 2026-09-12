<?php

namespace Riomigal\Languages\Jobs\Batch;

use Closure;
use Illuminate\Bus\Batch;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Throwable;

class BatchProcessor
{
    /**
     * @var Translator
     */
    protected Translator $authUser;

    /**
     *
     * @param list<BaseJob> $batchArray
     * @param (Closure(): void)|null $then
     * @param (Closure(): void)|null $catch
     * @param (Closure(): void)|null $finally
     * @return PendingBatch
     */
    public function execute(array $batchArray, Closure|null $then = null, Closure|null $catch = null, Closure|null $finally = null): PendingBatch
    {
        Setting::setJobsRunning();
        /** @var string $batchName Configured package batch name. */
        $batchName = config('languages.batch_name');
        /** @var string|\UnitEnum|null $queue Configured queue name. */
        $queue = config('languages.queue_name');
        return Bus::batch($batchArray)
            ->then(function (Batch $batch) use ($then) {
                if ($then) $then();
                Log::info('Jobs in batch id ' . $batch->id . ' successfully completed.');
            })->catch(function (Batch $batch, Throwable $e) use ($catch) {
                if ($catch) $catch();
                Log::error('Batch with id ' . $batch->id . ' failed.');
            })->finally(function (Batch $batch) use ($finally) {
                if ($finally) $finally();
                Setting::setJobsRunning(false);
                Log::info('Batch id ' . $batch->id . ' has finished executing.');
            })->name($batchName)->onQueue($queue);
    }
}
