<?php

namespace Riomigal\Languages\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Riomigal\Languages\Tests\BaseTestCase;

class ApiAuthTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function api_rejects_a_wrong_key_with_401(): void
    {
        config()->set('languages.api_shared_api_key', 'a-real-configured-secret');

        $response = $this->postJson(
            route('languages.api.jobs-running', [], false),
            ['api_key' => 'not-the-secret']
        );

        /**
         * The status code used to be passed inside the payload array rather than as
         * the second argument, so an invalid key answered 200. Callers check
         * $response->ok() before reading process_running, so a rejected request was
         * read as "no jobs running on that host".
         */
        $response->assertStatus(401);
    }

    #[Test]
    public function api_accepts_the_configured_key(): void
    {
        config()->set('languages.api_shared_api_key', 'a-real-configured-secret');

        $this->postJson(
            route('languages.api.jobs-running', [], false),
            ['api_key' => 'a-real-configured-secret']
        )->assertOk();
    }

    #[Test]
    public function api_fails_closed_when_no_key_is_configured(): void
    {
        config()->set('languages.api_shared_api_key', null);

        /**
         * The package used to ship a default secret, so any installation that never
         * set LANGUAGES_API_SHARED_SECRET accepted a publicly known key on endpoints
         * that dump every translation and trigger force-export.
         */
        $this->postJson(
            route('languages.api.jobs-running', [], false),
            ['api_key' => 'eq9TDNfrGX66o=(3qy{;Em)J&@i(Nk']
        )->assertStatus(503);
    }

    #[Test]
    public function api_requires_a_key_to_be_sent_at_all(): void
    {
        config()->set('languages.api_shared_api_key', 'a-real-configured-secret');

        $this->postJson(route('languages.api.jobs-running', [], false), [])
            ->assertStatus(422);
    }
}
