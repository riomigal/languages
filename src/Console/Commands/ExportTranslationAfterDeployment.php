<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\ForceExportTranslationJob;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
use Riomigal\Languages\Services\ExportTranslationService;

class ExportTranslationAfterDeployment extends Command
{

    /**
     * The name and signature of the console command.
     * --force: it will export all files even if exported is true in the translations record
     *
     * @var string
     */
    protected $signature = 'languages:export-translations-deployment';

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
        Language::query()->each(function(Language $language) use ($exportTranslationService) {
            $exportTranslationService->forceExportTranslationForLanguage($language);
            $this->info('Language: ' . $language->code . ' exported.');
        });
    }
}
