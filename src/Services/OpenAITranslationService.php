<?php

namespace Riomigal\Languages\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Riomigal\Languages\Models\Setting;

class OpenAITranslationService
{
   public function translateString(string $fromLanguageCode, string $toLanguageCode, string $text): string
   {
       if(!Setting::getCached()->enable_open_ai_translations) return $text;

       $result = OpenAI::chat()->create([
           'model' => config('languages.open_ai_model'),
           'response_format' => ['type' => 'json_object'],
           'messages' => [
               ['role' => 'system', 'content' => 'You are an universal translator and return only translated values and designed to output JSON.'],
               ['role' => 'user', 'content' => $text],
               ['role' => 'user', 'content' => 'From ' . $fromLanguageCode . ' to ' . $toLanguageCode . '.'],
           ],
       ]);

       return $result->choices[0]->message->content;
   }

    public function translateArray(string $fromLanguageCode, string $toLanguageCode, array $array): array
    {
        if(!Setting::getCached()->enable_open_ai_translations) {
            return $array;
        }

        $result = OpenAI::chat()->create([
            'model' => config('languages.open_ai_model'),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are an universal translator and return only translated values and designed to output JSON.'],
                ['role' => 'user', 'content' => json_encode(array_filter($array))],
                ['role' => 'user', 'content' => 'Translate from ' . $fromLanguageCode . ' to ' . $toLanguageCode . '.'],
            ],
        ]);

        return json_decode($result->choices[0]->message->content, true);
    }
}
