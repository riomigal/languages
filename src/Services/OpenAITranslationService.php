<?php

namespace Riomigal\Languages\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAITranslationService
{
   public function translateString(string $fromLanguageCode, string $toLanguageCode, string $text): string
   {
       if(!config('languages.enable_open_ai')) return $text;

       $result = OpenAI::chat()->create([
           'model' => config('languages.open_ai_model'),
           'messages' => [
               ['role' => 'system', 'content' => 'I am a universal translator and return only translated strings.'],
               ['role' => 'user', 'content' => $text],
               ['role' => 'user', 'content' => 'From ' . $fromLanguageCode . ' to ' . $toLanguageCode . '.'],
           ],
       ]);

       return $result->choices[0]->message->content;
   }

    public function translateArray(string $fromLanguageCode, string $toLanguageCode, array $array): array
    {
        if(!config('languages.enable_open_ai')) return $array;

        $result = OpenAI::chat()->create([
            'model' => config('languages.open_ai_model'),
            'messages' => [
                ['role' => 'system', 'content' => 'I am a universal translator and return only translated values in a json array.'],
                ['role' => 'user', 'content' => json_encode(array_filter($array))],
                ['role' => 'user', 'content' => 'Translate from ' . $fromLanguageCode . ' to ' . $toLanguageCode . '.'],
            ],
        ]);

        return json_decode($result->choices[0]->message->content, true);
    }
}
