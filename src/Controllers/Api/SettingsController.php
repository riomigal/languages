<?php

namespace Riomigal\Languages\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Riomigal\Languages\Models\Setting;

class SettingsController extends Controller
{
    public function jobsOnOtherDBRunning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'api_key' => 'required|string',
        ]);

        if($data['api_key'] !== config('languages.api_shared_api_key')) {
            return response()->json(['invalid_key' => 'Api key is invalid!', 401]);
        }

        $process_running = false;
        if(DB::table('jobs')
                ->where('queue', config('languages.queue_name'))
                ->exists() || DB::table('job_batches')->where('name', config('languages.batch_name'))
                ->whereNull('cancelled_at')
                ->whereNull('finished_at')
                ->exists()) {
            $process_running = true;
        }

        return response()->json(['process_running' => $process_running]);
    }
}
