<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PruneLanguageBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:prune-batches';

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
        /** @var string|\UnitEnum|null $connection Configured database connection name. */
        $connection = config('languages.db_connection');
        DB::connection($connection)->table('job_batches')->where('name', config('languages.batch_name'))
            ->where(function (Builder $query) {
                /** @var int|float $hours Retention interval from config/languages.php. */
                $hours = config('languages.prune_batch_hours');
                $query->where('finished_at', '<', now()->subHours($hours)->timestamp)
                    ->orWhere('cancelled_at', '<', now()->subHours($hours)->timestamp);
            })->delete();
    }
}
