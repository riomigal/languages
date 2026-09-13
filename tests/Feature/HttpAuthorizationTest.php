<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
use Riomigal\Languages\Tests\BaseTestCase;

class HttpAuthorizationTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_admin_endpoint_rejects_a_regular_translator_without_changing_data(): void
    {
        $translator = $this->createUser(Language::all());
        $language = Language::firstOrFail();
        $row = $language->translations()->create(['language_code' => $language->code, 'key' => 'authorization', 'shared_identifier' => 'json::authorization', 'type' => 'json', 'value' => 'original', 'needs_translation' => false]);
        Setting::firstOrFail()->update(['allow_deleting_languages' => true]);
        $tables = [config('languages.table_languages'), config('languages.table_translations'), config('languages.table_translators'), config('languages.table_translator_language'), config('languages.table_settings'), 'notifications', 'jobs', 'job_batches'];
        $snapshot = fn () => array_map(fn ($table) => DB::table($table)->get()->toJson(), $tables);
        $before = $snapshot();
        $this->actingAs($translator);
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (!in_array('languages.admin', $route->gatherMiddleware(), true)) continue;
            $parameters = array_intersect_key(['language' => $language->id, 'translator' => $translator->id, 'id' => $row->id, 'field' => 'allow_deleting_languages'], array_flip($route->parameterNames()));
            $url = route($route->getName(), $parameters);
            $method = in_array('POST', $route->methods(), true) ? 'post' : 'get';
            $this->{$method}($url, $method === 'post' ? ['translatedValue' => 'changed', 'language' => 'fr', 'allow_deleting_languages' => false] : [])
                ->assertForbidden();
            $this->assertSame($before, $snapshot(), $route->getName() . ' changed data');
            $checked++;
        }
        $this->assertSame(24, $checked);
    }

    #[Test]
    public function notification_ownership_is_checked_for_read_and_unread(): void
    {
        $admin = Translator::firstOrFail();
        $other = $this->createUser(Language::all());
        $admin->notifyNow(new FlashMessage('Admin private message'));
        $other->notifyNow(new FlashMessage('Translator private message'));
        $foreign = $admin->notifications()->firstOrFail();
        $own = $other->notifications()->firstOrFail();
        $this->actingAs($other);
        $this->getJson(route('languages.notifications'))->assertOk()->assertJsonCount(1, 'notifications')->assertDontSee('Admin private message');
        foreach ([true, false] as $read) {
            $this->postJson(route('languages.notifications.read', $foreign->id), ['read' => $read])->assertForbidden();
            $this->assertNull($foreign->fresh()->read_at);
        }
        $this->postJson(route('languages.notifications.read', $own->id))->assertOk()->assertJsonCount(0, 'notifications');
        $this->assertNotNull($own->fresh()->read_at);
        $this->postJson(route('languages.notifications.read', $own->id), ['read' => false])->assertOk()->assertJsonCount(1, 'notifications');
        $this->assertNull($own->fresh()->read_at);
        $this->postJson(route('languages.notifications.read-all'))->assertOk()->assertJsonCount(0, 'notifications');
        $this->assertNull($foreign->fresh()->read_at);
        $this->assertNotNull($own->fresh()->read_at);
    }

    #[Test]
    public function login_locks_out_after_ten_attempts_with_the_existing_message(): void
    {
        RateLimiter::clear('languages:login:127.0.0.1');
        $admin = Translator::firstOrFail();
        $before = $admin->getAttributes();
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('languages.login.submit'), ['email' => $admin->email, 'password' => 'incorrect'])
                ->assertSessionHasErrors(['email' => 'Email or password are invalid.']);
        }
        $this->post(route('languages.login.submit'), ['email' => $admin->email, 'password' => 'aaaaaaaa'])
            ->assertSessionHasErrors('email')
            ->assertSessionHas('errors', fn ($errors) => preg_match('/^Slow down! Please wait another \d+ seconds to log in\.$/', $errors->first('email')) === 1);
        $this->assertGuest(config('languages.translator_guard'));
        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->travel(61)->seconds();
        $this->post(route('languages.login.submit'), ['email' => $admin->email, 'password' => 'aaaaaaaa', 'remember' => '1'])->assertRedirect(route('languages.languages'));
        $this->assertAuthenticatedAs($admin, config('languages.translator_guard'));
        $this->post(route('languages.logout'))->assertRedirect(route('languages.login'));
        $this->assertGuest(config('languages.translator_guard'));
    }
}
