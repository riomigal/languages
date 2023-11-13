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
               ['role' => 'user', 'content' => $text],
               ['role' => 'user', 'content' => 'Translate from ' . $fromLanguageCode . ' to ' . $toLanguageCode],
           ],
       ]);

       return $result->choices[0]->message->content;
   }

//    public function translateArray(string $fromLanguageCode, string $toLanguageCode, array $array): array
//    {
//        if(!config('languages.enable_open_ai')) return $array;
//
//        $result = OpenAI::chat()->create([
//            'model' => config('languages.open_ai_model'),
//            'messages' => [
//                ['role' => 'user', 'content' => json_encode($array)],
//                ['role' => 'user', 'content' => 'Translate from ' . $fromLanguageCode . ' to ' . $toLanguageCode . '. Return as json.'],
//            ],
//        ]);
//
//        return json_decode($result->choices[0]->message->content, true);
//    }
}
