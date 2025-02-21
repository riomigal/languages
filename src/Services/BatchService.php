<?php

namespace Riomigal\Languages\Services;

use Illuminate\Support\Facades\DB;

class BatchService {

    public function deleteBatches(): array
    {
        $batches = DB::table('job_batches')
            ->where('name', config('languages.batch_name'))
            ->whereNull('cancelled_at')
            ->whereNull('finished_at')
            ->update(['cancelled_at' => now()->timestamp]);
        $jobs = DB::table('jobs')
            ->where('queue', config('languages.queue_name'))
            ->delete();
        return [$jobs, $batches];

    }
}
