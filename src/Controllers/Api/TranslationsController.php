<?php

namespace Riomigal\Languages\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Resources\TranslationResource;

class TranslationsController extends Controller
{
    public function getPaginated(Request $request): AnonymousResourceCollection
    {
        return TranslationResource::collection(Translation::query()->paginate(500));
    }
}
