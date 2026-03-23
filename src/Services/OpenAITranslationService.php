<?php

namespace Riomigal\Languages\Services;

use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Throwable;

class OpenAITranslationService
{
    private const OPENAI_FACADE = 'OpenAI\\Laravel\\Facades\\OpenAI';

    private bool $openAiUnavailableLogged = false;

    public function translateString(Language $rootLanguage, Language $toLanguage, string $text): string
    {
        if(!Setting::getCached()->enable_open_ai_translations) {
            Log::info('OpenAITranslationService::translateString Open AI disabled');
            return $text;
        }

        try {
            $result = $this->translateArray($rootLanguage, $toLanguage, ['t_00' => $text], true);
            return is_string($result['t_00'] ?? null) ? $result['t_00'] : $text;
        } catch(Throwable $e) {
            return $text;
        }
    }

    public function translateArray(Language $rootLanguage, Language $toLanguage, array $array, bool $stringTranslation = false): array
    {
        if(!Setting::getCached()->enable_open_ai_translations) {
            Log::info('OpenAITranslationService::translateArray Open AI disabled');
            return $array;
        }

        if(!$this->isOpenAIFacadeAvailable()) {
            return $array;
        }

        $result = null;

        try {
            $openAI = self::OPENAI_FACADE;

            $result = $openAI::chat()->create([
                'model' => config('languages.open_ai_model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an universal translator and return only translated values and designed to output JSON.'],
                    ['role' => 'system', 'content' => 'Keep the order of the array and please do not translate Laravel placeholders. Placeholders are words starting with a colon (e.g. :word).'],
                    ['role' => 'system', 'content' => 'If the translate from language of one value is not ' . $rootLanguage->name . ' (' . $rootLanguage->code . '), then try to detect language for this value.'],
                    ['role' => 'user', 'content' => json_encode(array_filter($array), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
                    ['role' => 'user', 'content' => 'Translate from ' . $rootLanguage->name . ' (' . $rootLanguage->code . ') to ' . $toLanguage->name . ' (' . $toLanguage->code. ').'],
                ],
            ]);

            $res = data_get($result, 'choices.0.message.content');
            if(!is_string($res) || !$this->isValidJson($res)) {
                return $array;
            }

            $decoded = json_decode($res, true);
            return is_array($decoded) ? $decoded : $array;
        } catch(Throwable $e) {
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

    private function isOpenAIFacadeAvailable(): bool
    {
        if(class_exists(self::OPENAI_FACADE)) {
            return true;
        }

        if(!$this->openAiUnavailableLogged) {
            Log::warning('OpenAI translation is enabled but openai-php/laravel is not installed.');
            $this->openAiUnavailableLogged = true;
        }

        return false;
    }

    private function isValidJson(string $json): bool
    {
        json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
