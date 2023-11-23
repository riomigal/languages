<?php

namespace Riomigal\Languages\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Riomigal\Languages\Models\Setting;

class SettingsController extends Controller
{
    public function jobsOnOtherDBRunning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'api_key' => 'string',
        ]);

        if($data['api_key'] !== config('languages.api_shared_api_key')) {
            return response()->json(['invalid_key' => 'Api key is invalid!', 401]);
        }
        $running = Setting::first()->process_running;
        return response()->json(['process_running' => $running]);
    }
}
