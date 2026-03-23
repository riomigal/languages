<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Services\OpenAITranslationService;
use Riomigal\Languages\Tests\BaseTestCase;
use RuntimeException;

class OpenAITranslationServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    protected OpenAITranslationService $service;

    protected Language $sourceLanguage;

    protected Language $targetLanguage;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(OpenAITranslationService::class);
        $this->sourceLanguage = $this->createLanguageByCode('en');
        $this->targetLanguage = $this->createLanguageByCode('de');
    }

    /**
     * @test
     */
    public function open_ai_translate_string_returns_original_text_when_feature_disabled(): void
    {
        $fake = OpenAI::fake();

        $this->setOpenAiEnabled(false);

        $result = $this->service->translateString($this->sourceLanguage, $this->targetLanguage, 'Hello world');

        $this->assertSame('Hello world', $result);
        $fake->assertNothingSent();
    }

    /**
     * @test
     */
    public function open_ai_translate_array_returns_original_array_when_response_is_not_json(): void
    {
        $fake = OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'not-json',
                            'function_call' => null,
                            'tool_calls' => [],
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ]),
        ]);

        $this->setOpenAiEnabled(true);
        $payload = ['title' => 'Hello', 'body' => 'World'];

        $result = $this->service->translateArray($this->sourceLanguage, $this->targetLanguage, $payload);

        $this->assertSame($payload, $result);
        $fake->chat()->assertSent(1);
    }

    /**
     * @test
     */
    public function open_ai_translate_array_returns_original_array_when_openai_throws_exception(): void
    {
        $fake = OpenAI::fake([
            new RuntimeException('OpenAI request failed'),
        ]);

        $this->setOpenAiEnabled(true);
        $payload = ['title' => 'Hello'];

        $result = $this->service->translateArray($this->sourceLanguage, $this->targetLanguage, $payload);

        $this->assertSame($payload, $result);
        $fake->chat()->assertSent(1);
    }

    /**
     * @test
     */
    public function open_ai_translate_string_returns_translated_value_when_json_response_is_valid(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => '{"t_00":"Hallo Welt"}',
                            'function_call' => null,
                            'tool_calls' => [],
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ]),
        ]);

        $this->setOpenAiEnabled(true);

        $result = $this->service->translateString($this->sourceLanguage, $this->targetLanguage, 'Hello world');

        $this->assertSame('Hallo Welt', $result);
    }

    /**
     * @test
     */
    public function open_ai_translate_string_falls_back_when_valid_json_does_not_contain_t_00(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => '{"other":"value"}',
                            'function_call' => null,
                            'tool_calls' => [],
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ]),
        ]);

        $this->setOpenAiEnabled(true);

        $result = $this->service->translateString($this->sourceLanguage, $this->targetLanguage, 'Hello world');

        $this->assertSame('Hello world', $result);
    }

    private function setOpenAiEnabled(bool $enabled): void
    {
        Setting::query()->first()->update([
            'enable_open_ai_translations' => $enabled,
        ]);

        Setting::getFreshCached();
    }

    private function createLanguageByCode(string $code): Language
    {
        $language = collect(Language::LANGUAGES)->firstWhere('code', $code);

        return Language::query()->firstOrCreate([
            'code' => $language['code'],
            'name' => $language['name'],
            'native_name' => $language['native_name'],
        ]);
    }
}
