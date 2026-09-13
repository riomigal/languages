<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Riomigal\Languages\Services\BatchService;

class BatchController extends BaseController
{
    public function progress(Request $request, BatchService $service): JsonResponse
    {
        $request->validate(['id' => 'nullable|string|max:255']);
        $id = $request->string('id')->toString();
        return response()->json($service->progress($id === '' ? null : $id));
    }
}
