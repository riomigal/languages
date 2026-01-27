<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Services\GitHubPullRequestService;

class ExportTranslations extends Command
{
    use ChecksForRunningJobs;

    /**
     * The name and signature of the console command.
     * --force: it will export all files even if exported is true in the translations record
     * --create-pr: creates a GitHub PR with the exported translations
     *
     * @var string
     */
    protected $signature = 'languages:export-translations {--force=} {--create-pr}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports all approved translations.';

    /**
     * Execute the console command.
     */
    public function handle(ExportTranslationService $exportTranslationService): void
    {
        $forceExport = (bool) $this->option('force');
        $createPr = (bool) $this->option('create-pr');

        if($this->anotherJobIsRunning(true)) return;
        try {
            Setting::setJobsRunning();
            $languages = Language::find(Translation::query()
                ->isUpdated(false)
                ->when(!$forceExport, function($query) {
                    $query->exported(false);
                })
                ->approved()->distinct()->pluck('language_id')->toArray());

            if (count($languages)) {
                $total = Translation::query()
                    ->isUpdated(false)
                    ->when(!$forceExport, function($query) {
                        $query->exported(false);
                    })
                    ->approved()
                    ->count();
                $this->info('Exporting translations...');
                Language::query()->each(function (Language $language) use ($exportTranslationService, $forceExport) {
                    if($forceExport) {
                        $exportTranslationService->forceExportTranslationForLanguage($language, null, Setting::first()->db_loader);
                    } else {
                        $exportTranslationService->exportTranslationForLanguage($language, null, Setting::first()->db_loader);
                    }
                });
                Translator::notifyAdminExportedTranslationsAllLanguages($total, $languages);
                $total -= Translation::query()
                    ->isUpdated(false)->exported(false)
                    ->approved()
                    ->count();
                $this->info('Total translations exported: ' . $total . '.');

                // Create PR if requested
                if ($createPr && $total > 0) {
                    $this->createPullRequest($languages, $total);
                }
            } else {
                $this->info('Nothing to export.');
            }
            Setting::setJobsRunning(false);
        } catch(\Exception $e) {
            Setting::setJobsRunning(false);
            throw $e;
        }
    }

    /**
     * Create a GitHub pull request with the exported translations.
     *
     * @param \Illuminate\Database\Eloquent\Collection $languages
     * @param int $total
     * @return void
     */
    protected function createPullRequest($languages, int $total): void
    {
        if (!GitHubPullRequestService::isEnabled()) {
            $this->warn('GitHub PR integration is not enabled or not properly configured. Skipping PR creation.');
            return;
        }

        $this->info('Creating GitHub pull request...');

        try {
            $languageCodes = $languages->pluck('code')->toArray();
            $prUrl = resolve(GitHubPullRequestService::class)
                ->createPullRequestForExport($languageCodes, $total);

            if ($prUrl) {
                $this->info('Pull request created: ' . $prUrl);
                Translator::notifyAdminPullRequestCreated($prUrl, $languageCodes, $total);
            } else {
                $this->warn('No changes to commit, PR not created.');
            }
        } catch (\Exception $e) {
            $this->error('Failed to create pull request: ' . $e->getMessage());
        }
    }
}
