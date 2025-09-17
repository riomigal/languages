<?php

namespace Riomigal\Languages\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Resources\LanguageResource;
use Riomigal\Languages\Services\BatchService;

class LanguagesController extends Controller
{
    public function getLanguages(): AnonymousResourceCollection
    {
        return LanguageResource::collection(Language::all());
    }

    public function cancelBatch(BatchService $batchService): JsonResponse
    {
        $batchService->deleteBatches();
        return response()->json([], 204);
    }
}
