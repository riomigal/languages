<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Livewire\Languages;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

class MultiHostCoordinationTest extends BaseTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        config()->set('languages.api_shared_api_key', 'multi-host-test-secret');
        Http::preventStrayRequests();
    }

    #[Test]
    public function disabled_job_checks_send_no_http_even_with_saved_and_environment_hosts(): void
    {
        $this->configureHosts(false);
        config()->set('languages.multiple_db_hosts', 'https://fallback.example');
        Http::fake();

        $this->assertFalse($this->anotherJobIsRunning());
        Http::assertNothingSent();

        Setting::query()->firstOrFail()->update(['domains' => null]);
        Setting::getFreshCached();

        $this->assertFalse($this->anotherJobIsRunning());
        Http::assertNothingSent();
    }

    #[Test]
    public function disabled_job_checks_still_detect_local_jobs(): void
    {
        $this->configureHosts(false);
        $this->insertLocalJob();
        Http::fake();

        $this->assertTrue($this->anotherJobIsRunning());
        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('localBatchStates')]
    public function disabled_job_checks_respect_local_batch_state(?int $cancelledAt, ?int $finishedAt, bool $running): void
    {
        $this->configureHosts(false);
        $this->insertLocalBatch($cancelledAt, $finishedAt);
        Http::fake();

        $this->assertSame($running, $this->anotherJobIsRunning());
        Http::assertNothingSent();
    }

    public static function localBatchStates(): array
    {
        return [
            'active' => [null, null, true],
            'cancelled' => [1, null, false],
            'finished' => [null, 1, false],
        ];
    }

    #[Test]
    #[DataProvider('remoteJobStates')]
    public function enabled_job_checks_call_other_hosts_and_respect_their_job_state(bool $running): void
    {
        $this->configureHosts(true);
        Http::fake([
            'https://one.example/*' => Http::response(['process_running' => $running]),
            'https://two.example/*' => Http::response(['process_running' => false]),
        ]);

        $this->assertSame($running, $this->anotherJobIsRunning());
        $this->assertRequestsToOtherHosts('languages.api.jobs-running');
    }

    public static function remoteJobStates(): array
    {
        return [
            'idle' => [false],
            'running' => [true],
        ];
    }

    #[Test]
    public function enabled_job_checks_continue_to_ignore_unreachable_or_unsuccessful_hosts(): void
    {
        $this->configureHosts(true);
        Http::fake([
            'https://one.example/*' => Http::failedConnection(),
            'https://two.example/*' => Http::response([], 500),
        ]);

        $this->assertFalse($this->anotherJobIsRunning());
    }

    #[Test]
    public function disabled_exports_send_no_http_even_with_configured_hosts(): void
    {
        $this->configureHosts(false);
        Http::fake();

        resolve(ExportTranslationService::class)->exportTranslationsOnOtherHosts();

        Http::assertNothingSent();
    }

    #[Test]
    public function enabled_exports_call_other_hosts(): void
    {
        $this->configureHosts(true);
        Notification::fake();
        Http::fake(['*' => Http::response(['message' => 'Export started.'])]);

        resolve(ExportTranslationService::class)->exportTranslationsOnOtherHosts();

        $this->assertRequestsToOtherHosts('languages.api.force-export');
    }

    #[Test]
    #[DataProvider('cancellationModes')]
    public function cancelling_local_work_contacts_other_hosts_only_when_enabled(bool $enabled): void
    {
        $this->configureHosts($enabled);
        $this->insertLocalJob();
        $this->insertLocalBatch();
        Http::fake();

        Livewire::actingAs(Translator::query()->firstOrFail())
            ->test(Languages::class)
            ->call('deleteJobs')
            ->assertReturned(true);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertNotNull(DB::table('job_batches')->value('cancelled_at'));
        if ($enabled) {
            $this->assertRequestsToOtherHosts('languages.api.cancel-batch');
        } else {
            Http::assertNothingSent();
        }
    }

    public static function cancellationModes(): array
    {
        return [
            'disabled' => [false],
            'enabled' => [true],
        ];
    }

    #[Test]
    public function cancelling_without_local_work_also_sends_no_http_when_disabled(): void
    {
        $this->configureHosts(false);
        Setting::setJobsRunning();
        Http::fake();

        Livewire::actingAs(Translator::query()->firstOrFail())
            ->test(Languages::class)
            ->call('deleteJobs')
            ->assertReturned(false);

        $this->assertFalse(Setting::query()->firstOrFail()->process_running);
        Http::assertNothingSent();
    }

    private function configureHosts(bool $enabled): void
    {
        Setting::query()->firstOrFail()->update([
            'enable_multi_host' => $enabled,
            'domains' => request()->getSchemeAndHttpHost() . ', https://one.example , https://two.example ',
        ]);
        Setting::getFreshCached();
    }

    private function anotherJobIsRunning(): bool
    {
        $checker = new class {
            use ChecksForRunningJobs {
                anotherJobIsRunning as public;
            }
        };

        return $checker->anotherJobIsRunning(true);
    }

    private function assertRequestsToOtherHosts(string $routeName): void
    {
        $path = route($routeName, [], false);
        Http::assertSentCount(2);
        foreach (['https://one.example', 'https://two.example'] as $host) {
            Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === $host . $path
                && $request['api_key'] === 'multi-host-test-secret');
        }
    }

    private function insertLocalJob(): void
    {
        DB::table('jobs')->insert([
            'queue' => config('languages.queue_name'),
            'payload' => '{}',
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);
    }

    private function insertLocalBatch(?int $cancelledAt = null, ?int $finishedAt = null): void
    {
        DB::table('job_batches')->insert([
            'id' => 'local-multi-host-test-batch',
            'name' => config('languages.batch_name'),
            'total_jobs' => 1,
            'pending_jobs' => 1,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'created_at' => now()->timestamp,
            'cancelled_at' => $cancelledAt,
            'finished_at' => $finishedAt,
        ]);
    }
}
