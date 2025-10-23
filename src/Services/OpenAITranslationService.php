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
        if (!Setting::getCached()->enable_open_ai_translations) {
            Log::info('OpenAITranslationService::translateString OpenAI disabled');
            return $text;
        }

        try {
            $result = $this->translateArray($rootLanguage, $toLanguage, ['t_00' => $text], true);
            return $result['t_00'] ?? $text;
        } catch (\Exception $e) {
            Log::warning('Translation Failed -> translateString: ' . $e->getMessage());
            return $text;
        }
    }

    public function translateArray(Language $rootLanguage, Language $toLanguage, array $array, bool $stringTranslation = false): array
    {
        Log::debug('OpenAITranslationService::translateArray', $array);
        if (!Setting::getCached()->enable_open_ai_translations) {
            Log::info('OpenAITranslationService::translateArray OpenAI disabled');
            return $array;
        }

        $result = null;

        try {
            // Load custom instructions from config
            $systemInstructions = config('languages.open_ai_custom_system_instructions', []);
            $userInstructions   = config('languages.open_ai_custom_user_instructions', []);

            // Base system messages
            $messages = [
                ['role' => 'system', 'content' => 'You are a universal translator that outputs only translated values in valid JSON format.'],
                ['role' => 'system', 'content' => 'Preserve the array order and never translate Laravel placeholders (words starting with a colon, e.g., :word).'],
                ['role' => 'system', 'content' => 'If the source language of any value differs from ' . $rootLanguage->name . ' (' . $rootLanguage->code . '), detect its language automatically.'],
            ];

            // Add custom system instructions
            foreach ($systemInstructions as $instruction) {
                if (is_string($instruction) && trim($instruction) !== '') {
                    $messages[] = ['role' => 'system', 'content' => trim($instruction)];
                }
            }

            // Add user data
            $messages[] = ['role' => 'user', 'content' => json_encode(array_filter($array), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

            // Add user translation instruction
            $messages[] = ['role' => 'user', 'content' => 'Translate the following values from ' . $rootLanguage->name . ' (' . $rootLanguage->code . ') to ' . $toLanguage->name . ' (' . $toLanguage->code . '). Return only valid JSON.'];

            // Add custom user instructions
            foreach ($userInstructions as $instruction) {
                if (is_string($instruction) && trim($instruction) !== '') {
                    $messages[] = ['role' => 'user', 'content' => trim($instruction)];
                }
            }

            $result = OpenAI::chat()->create([
                'model' => config('languages.open_ai_model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => $messages,
            ]);

            $res = trim($result->choices[0]->message->content ?? '');

            if (config('app.debug')) {
                Log::debug('OpenAI raw translation response', ['response' => $res]);
            }

            if (!$res || json_decode($res) === null) {
                return [];
            }

            return json_decode($res, true);
        } catch (\Exception $e) {
            Log::warning('Translation Failed -> OpenAITranslationService::translateArray ' . $e->getMessage(), [
                'rootLanguage' => $rootLanguage->code,
                'toLanguage' => $toLanguage->code,
                'translationsType' => $stringTranslation ? 'translateString' : 'translateArray',
                'content' => $array,
                'result' => $result,
            ]);

            return $array;
        }
    }
}
