<?php

namespace Riomigal\Languages\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;

class OpenAITranslationService
{
   public function translateString(Language $rootLanguage, Language $toLanguage, string $text): string
   {
       if(!Setting::getCached()->enable_open_ai_translations) {
           Log::info('OpenAITranslationService::translateString Open AI disabled');
           return $text;
       }

       try {
           $result = $this->translateArray($rootLanguage, $toLanguage, ['t_00' => $text], true);
           return $result['t_00'];
       } catch(\Exception $e) {
            return $text;
       }
   }

    public function translateArray(Language $rootLanguage, Language $toLanguage, array $array, bool $stringTranslation = false): array
    {
        if(!Setting::getCached()->enable_open_ai_translations) {
            Log::info('OpenAITranslationService::translateArray Open AI disabled');
            return $array;
        }

        $result = null;
        try {
            $result = OpenAI::chat()->create([
                'model' => config('languages.open_ai_model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an universal translator and return only translated values and designed to output JSON. Please do not translate Laravel placeholders. Placeholders are words starting with a colon (e.g. :word).'],
                    ['role' => 'user', 'content' => json_encode(array_filter($array))],
                    ['role' => 'user', 'content' => 'Translate from ' . $rootLanguage->name . ' (' . $rootLanguage->code . ') to ' . $toLanguage->name . ' (' . $toLanguage->code. ').'],
                ],
            ]);

            return json_decode($result->choices[0]->message->content, true);
        } catch(\Exception $e) {
            Log::warning('Translation Failed -> OpenAITranslationService::translateArray ' . $e->getMessage(), [
                'rootLanguage' => $rootLanguage->code,
                'toLanguage' => $toLanguage->code,
                'translationsType' => $stringTranslation ? 'translateString' : 'translateArray',
                'content' => $array,
                'result' => $result
            ]);
            return $array;
        }
    }
}
