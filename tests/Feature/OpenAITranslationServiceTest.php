<?php

namespace Riomigal\Languages\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

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

    #[Test]
    public function open_ai_translate_string_returns_original_text_when_feature_disabled(): void
    {
        $fake = OpenAI::fake();

        $this->setOpenAiEnabled(false);

        $result = $this->service->translateString($this->sourceLanguage, $this->targetLanguage, 'Hello world');

        $this->assertSame('Hello world', $result);
        $fake->assertNothingSent();
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    #[DataProvider('invalidArrayResponses')]
    public function malformed_array_responses_preserve_all_original_keys_and_values(string $response): void
    {
        $this->fakeContent($response);
        $payload = ['title' => 'Hello', 'body' => 'World'];

        $this->assertSame($payload, $this->service->translateArray($this->sourceLanguage, $this->targetLanguage, $payload));
    }

    public static function invalidArrayResponses(): array
    {
        return [
            'missing key' => ['{"title":"Hallo"}'],
            'extra key' => ['{"title":"Hallo","body":"Welt","extra":"Unexpected"}'],
            'renamed key' => ['{"title":"Hallo","other":"Welt"}'],
            'null value' => ['{"title":"Hallo","body":null}'],
            'boolean value' => ['{"title":"Hallo","body":false}'],
            'numeric value' => ['{"title":"Hallo","body":123}'],
            'array value' => ['{"title":"Hallo","body":["Welt"]}'],
            'object value' => ['{"title":"Hallo","body":{"text":"Welt"}}'],
            'list' => ['["Hallo","Welt"]'],
            'empty object' => ['{}'],
            'scalar' => ['"Hallo"'],
        ];
    }

    #[Test]
    #[DataProvider('invalidStringResponses')]
    public function malformed_string_responses_preserve_the_original_text(string $response): void
    {
        $this->fakeContent($response);

        $this->assertSame('Hello', $this->service->translateString($this->sourceLanguage, $this->targetLanguage, 'Hello'));
    }

    public static function invalidStringResponses(): array
    {
        return [
            'missing key' => ['{}'],
            'extra key' => ['{"t_00":"Hallo","extra":"Unexpected"}'],
            'null value' => ['{"t_00":null}'],
            'boolean value' => ['{"t_00":false}'],
            'numeric value' => ['{"t_00":123}'],
            'array value' => ['{"t_00":["Hallo"]}'],
            'object value' => ['{"t_00":{"text":"Hallo"}}'],
            'list' => ['["Hallo"]'],
        ];
    }

    #[Test]
    public function valid_responses_preserve_key_order_and_send_empty_and_zero_strings(): void
    {
        $this->fakeContent('{"zero":"0","empty":"","title":"Hallo"}');
        $payload = ['title' => 'Hello', 'empty' => '', 'zero' => '0'];

        $this->assertSame(['title' => 'Hallo', 'empty' => '', 'zero' => '0'], $this->service->translateArray($this->sourceLanguage, $this->targetLanguage, $payload));
        OpenAI::chat()->assertSent(function (string $method, array $parameters) use ($payload): bool {
            return json_decode($parameters['messages'][3]['content'], true) === $payload;
        });
    }

    private function fakeContent(string $content): void
    {
        $this->setOpenAiEnabled(true);
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [[
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => $content, 'function_call' => null, 'tool_calls' => []],
                    'finish_reason' => 'stop',
                ]],
            ]),
        ]);
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
