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

    /**
     * @return ($text is null ? null : string)
     */
    public function translateString(?Language $rootLanguage, ?Language $toLanguage, ?string $text): ?string
    {
        if ($text === null || $rootLanguage === null || $toLanguage === null) {
            return $text;
        }
        if(!Setting::getCached()->enable_open_ai_translations) {
            Log::info('OpenAITranslationService::translateString Open AI disabled');
            return $text;
        }

        try {
            $result = $this->translateArray($rootLanguage, $toLanguage, ['t_00' => $text], true);
            return $result['t_00'] ?? $text;
        } catch(Throwable $e) {
            return $text;
        }
    }

    /**
     * @param array<array-key, string> $array Translation text indexed by caller-supplied keys.
     * @return array<array-key, string> Validated translations in the original key order, or the original input.
     */
    public function translateArray(?Language $rootLanguage, ?Language $toLanguage, array $array, bool $stringTranslation = false): array
    {
        if ($rootLanguage === null || $toLanguage === null || $array === []) {
            return $array;
        }
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
                    ['role' => 'user', 'content' => json_encode((object) $array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
                    ['role' => 'user', 'content' => 'Translate from ' . $rootLanguage->name . ' (' . $rootLanguage->code . ') to ' . $toLanguage->name . ' (' . $toLanguage->code. ').'],
                ],
            ]);

            $res = data_get($result, 'choices.0.message.content');
            if(!is_string($res)) {
                return $array;
            }

            $decoded = json_decode($res, false, 512, JSON_THROW_ON_ERROR);
            if (!$decoded instanceof \stdClass) {
                return $array;
            }
            $decoded = (array) $decoded;
            if (count($decoded) !== count($array)) {
                return $array;
            }

            $translations = [];
            foreach ($array as $key => $value) {
                $translatedValue = $decoded[$key] ?? null;
                if (!is_string($translatedValue)) {
                    return $array;
                }
                $translations[$key] = $translatedValue;
            }

            return $translations;
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

}
