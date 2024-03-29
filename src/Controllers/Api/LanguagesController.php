<?php

namespace Riomigal\Languages\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Resources\LanguageResource;

class LanguagesController extends Controller
{
    public function getLanguages(): AnonymousResourceCollection
    {
        return LanguageResource::collection(Language::all());
    }
}
