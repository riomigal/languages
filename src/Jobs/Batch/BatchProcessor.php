<?php

namespace Riomigal\Languages\Jobs\Batch;

use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Closure;
use Riomigal\Languages\Exceptions\ExportTranslationException;
use Riomigal\Languages\Exceptions\ImportTranslationsException;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
use Throwable;

class BatchProcessor
{
    /**
     * @var Translator
     */
    protected Translator $authUser;

    /**
     *
     * @param array $batchArray
     * @param Closure|null $then
     * @param Closure|null $catch
     * @param Closure|null $finally
     * @return PendingBatch
     */
    public function execute(array $batchArray, Closure|null $then = null, Closure|null $catch = null, Closure|null $finally = null): PendingBatch
    {
        return Bus::batch($batchArray)
            ->then(function (Batch $batch) use ($then) {
                if ($then) $then();
                Log::info('Jobs in batch id ' . $batch->id . ' successfully completed.');
            })->catch(function (Batch $batch, Throwable $e) use ($catch) {
                if ($catch) $catch();
                Log::error('Batch with id ' . $batch->id . ' failed.');
            })->finally(function (Batch $batch) use ($finally) {
                if ($finally) $finally();
                Log::info('Batch id ' . $batch->id . ' has finished executing.');
            })->name(config('languages.batch_name'))->onQueue(config('languages.queue_name'));
    }
}
