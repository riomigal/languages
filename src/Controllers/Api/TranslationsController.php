<?php

namespace Riomigal\Languages\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\ForceExportTranslationJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
use Riomigal\Languages\Resources\TranslationResource;

class TranslationsController extends Controller
{
    /**
     * @return AnonymousResourceCollection
     */
    public function getPaginated(): AnonymousResourceCollection
    {
        return TranslationResource::collection(Translation::query()->paginate(500));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function forceExport(Request $request): JsonResponse
    {
        $batchArray = [];
        $host = $request->getSchemeAndHttpHost();
        Language::query()->each(function(Language $language) use (&$batchArray) {
            $batchArray[] = new ForceExportTranslationJob($language);
        });

        $finally = function () use ($host) {
            Translator::query()->admin()->each(function (Translator $translator) use ($host) {
                $translator->notify(new FlashMessage(__('languages::translations.export_on_other_host_success', ['host' => $host])));
            });
        };

        resolve(BatchProcessor::class)->execute($batchArray,null, null, $finally)->dispatchAfterResponse();

        return response()->json(['message' => 'Export started.']);
    }
}
