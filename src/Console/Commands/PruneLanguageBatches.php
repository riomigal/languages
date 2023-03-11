<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneLanguageBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune:language-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all batches from the batch table which belong to the language package.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        DB::table('job_batches')->where('name', config('languages.batch_name'))
            ->where(function ($query) {
                $query->where('finished_at', '<', now()->subHours(config('languages.prune_batch_hours'))->timestamp)
                    ->orWhere('cancelled_at', '<', now()->subHours(config('languages.prune_batch_hours'))->timestamp);
            })->delete();
    }
}
