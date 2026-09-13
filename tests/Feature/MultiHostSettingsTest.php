<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Livewire\Settings;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Requests\UpdateSettingFieldRequest;
use Riomigal\Languages\Tests\BaseTestCase;

class MultiHostSettingsTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_administrator_can_enable_and_disable_multi_host_then_clear_domains(): void
    {
        $component = Livewire::actingAs(Translator::query()->firstOrFail())
            ->test(Settings::class)
            ->assertSee(__('languages::settings.enable_multi_host.label'))
            ->set('setting.domains', 'https://one.example')
            ->set('setting.enable_multi_host', true)
            ->assertHasNoErrors();

        $this->assertTrue(Setting::query()->firstOrFail()->enable_multi_host);
        $this->assertTrue(Setting::multiHostEnabled());

        $component->set('setting.domains', '')
            ->assertHasErrors(['setting.domains' => 'required_if']);
        $this->assertSame('https://one.example', Setting::query()->firstOrFail()->domains);

        $component->set('setting.enable_multi_host', false)
            ->set('setting.domains', '')
            ->assertHasNoErrors();

        $this->assertFalse(Setting::multiHostEnabled());
        $this->assertEmpty(Setting::query()->firstOrFail()->domains);
    }

    #[Test]
    public function multi_host_cannot_be_enabled_in_settings_without_domains(): void
    {
        Livewire::actingAs(Translator::query()->firstOrFail())
            ->test(Settings::class)
            ->set('setting.enable_multi_host', true)
            ->assertHasErrors(['setting.domains' => 'required_if']);

        $this->assertFalse(Setting::query()->firstOrFail()->enable_multi_host);
    }

    #[Test]
    public function settings_reject_an_invalid_multi_host_flag(): void
    {
        Livewire::actingAs(Translator::query()->firstOrFail())
            ->test(Settings::class)
            ->set('setting.enable_multi_host', 'invalid')
            ->assertHasErrors(['setting.enable_multi_host' => 'boolean']);

        $this->assertFalse(Setting::query()->firstOrFail()->enable_multi_host);
    }

    #[Test]
    public function settings_do_not_allow_internal_model_fields_to_be_updated(): void
    {
        Livewire::actingAs(Translator::query()->firstOrFail())
            ->test(Settings::class)
            ->set('setting.process_running', true)
            ->assertStatus(422);

        $this->assertFalse(Setting::query()->firstOrFail()->process_running);
    }

    #[Test]
    #[DataProvider('fieldUpdates')]
    public function field_requests_validate_against_saved_context(
        bool $enabled,
        ?string $domains,
        string $field,
        array $input,
        bool $valid,
    ): void {
        Setting::query()->firstOrFail()->update([
            'enable_multi_host' => $enabled,
            'domains' => $domains,
        ]);
        $request = UpdateSettingFieldRequest::create('/settings/' . $field, 'PATCH', $input);
        $route = new Route('PATCH', 'settings/{field}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn (): Route => $route);

        $validator = Validator::make($request->validationData(), $request->rules());

        $this->assertSame($valid, $validator->passes(), $validator->errors()->toJson());
    }

    public static function fieldUpdates(): array
    {
        return [
            'empty domains while disabled' => [false, null, 'domains', ['domains' => ''], true],
            'null domains while disabled' => [false, null, 'domains', ['domains' => null], true],
            'empty domains while enabled' => [true, 'https://one.example', 'domains', ['domains' => ''], false],
            'ignore submitted flag when clearing domains' => [true, 'https://one.example', 'domains', ['domains' => '', 'enable_multi_host' => false], false],
            'enable with saved domains' => [false, 'https://one.example', 'enable_multi_host', ['enable_multi_host' => true], true],
            'enable without domains' => [false, null, 'enable_multi_host', ['enable_multi_host' => true], false],
            'ignore submitted domains when enabling' => [false, null, 'enable_multi_host', ['enable_multi_host' => true, 'domains' => 'https://one.example'], false],
            'disable without domains' => [true, null, 'enable_multi_host', ['enable_multi_host' => false], true],
            'reject invalid flag' => [false, 'https://one.example', 'enable_multi_host', ['enable_multi_host' => 'invalid'], false],
        ];
    }
}
