<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
        $this->actingAs(Translator::query()->firstOrFail());
        $this->get(route('languages.settings'))->assertSee(__('languages::settings.enable_multi_host.label'));
        $this->post(route('languages.settings.update', 'domains'), ['domains' => 'https://one.example'])->assertSessionHasNoErrors();
        $this->post(route('languages.settings.update', 'enable_multi_host'), ['enable_multi_host' => true])->assertSessionHasNoErrors();
        $this->assertTrue(Setting::query()->firstOrFail()->enable_multi_host);
        $this->assertTrue(Setting::multiHostEnabled());
        $this->post(route('languages.settings.update', 'domains'), ['domains' => ''])->assertSessionHasErrors('domains');
        $this->assertSame('https://one.example', Setting::query()->firstOrFail()->domains);
        $this->post(route('languages.settings.update', 'enable_multi_host'), ['enable_multi_host' => false])->assertSessionHasNoErrors();
        $this->post(route('languages.settings.update', 'domains'), ['domains' => ''])->assertSessionHasNoErrors();
        $this->assertFalse(Setting::multiHostEnabled());
        $this->assertEmpty(Setting::query()->firstOrFail()->domains);
    }

    #[Test]
    public function multi_host_cannot_be_enabled_in_settings_without_domains(): void
    {
        $this->actingAs(Translator::query()->firstOrFail())
            ->post(route('languages.settings.update', 'enable_multi_host'), ['enable_multi_host' => true])->assertSessionHasErrors('domains');
        $this->assertFalse(Setting::query()->firstOrFail()->enable_multi_host);
    }

    #[Test]
    public function settings_reject_an_invalid_multi_host_flag(): void
    {
        $this->actingAs(Translator::query()->firstOrFail())
            ->post(route('languages.settings.update', 'enable_multi_host'), ['enable_multi_host' => 'invalid'])->assertSessionHasErrors('enable_multi_host');
        $this->assertFalse(Setting::query()->firstOrFail()->enable_multi_host);
    }

    #[Test]
    public function settings_do_not_allow_internal_model_fields_to_be_updated(): void
    {
        $this->actingAs(Translator::query()->firstOrFail())
            ->post(route('languages.settings.update', 'process_running'), ['process_running' => true])->assertNotFound();
        $this->assertFalse(Setting::query()->firstOrFail()->process_running);
    }

    #[Test]
    public function invalid_domains_do_not_block_saving_an_unrelated_setting(): void
    {
        $this->actingAs(Translator::query()->firstOrFail());
        $this->post(route('languages.settings.update', 'enable_multi_host'), ['enable_multi_host' => true])->assertSessionHasErrors('domains');
        $this->post(route('languages.settings.update', 'allow_deleting_languages'), ['allow_deleting_languages' => true, 'domains' => []])->assertRedirect();
        $this->assertTrue(Setting::getCached()->allow_deleting_languages);
        $this->assertFalse(Setting::getCached()->enable_multi_host);
    }

    #[Test]
    public function background_polling_does_not_consume_redirect_validation_errors(): void
    {
        $this->actingAs(Translator::firstOrFail());
        $this->post(route('languages.settings.update', 'enable_multi_host'), ['enable_multi_host' => true])->assertSessionHasErrors('domains');
        $this->getJson(route('languages.notifications'))->assertOk();
        $this->get(route('languages.settings'))->assertOk()->assertSee('Domains are required when multi-host coordination is enabled.');
        $this->get(route('languages.settings'))->assertOk()->assertDontSee('Domains are required when multi-host coordination is enabled.');
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
        $request = UpdateSettingFieldRequest::create('/settings/' . $field, 'POST', $input);
        $route = new Route('POST', 'settings/{field}', fn () => null);
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
