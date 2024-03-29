<?php

namespace Riomigal\Languages\Console\Commands;

use Illuminate\Console\Command;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\MissingTranslationService;

class FindMissingTranslations extends Command
{
    use ChecksForRunningJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:find-missing-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates missing translations from other languages in the DB.';

    /**
     * Execute the console command.
     */
    public function handle(MissingTranslationService $missingTranslationService): void
    {
        if($this->anotherJobIsRunning(true)) return;
        try {
            Setting::setJobsRunning();

            $total = Translation::selectRaw('count(*) as total')->groupBy('language_id')->orderBy('language_id')->pluck('total')->all();

            Language::query()->whereDoesntHave('translations')->each(function(Language $language) use (&$total) {
                $total[] = -1;
            });

            $total =  count(array_unique($total));

            if ($total > 1) {
                $totalTranslationsBefore = Translation::count();
                $this->info('Existing Translations: ' . $totalTranslationsBefore . '.');
                $this->info('Importing translations...');
                $missingTranslationService->findMissingTranslations();
                Translator::notifyAdminImportedMissingTranslations($totalTranslationsBefore);
                $total = Translation::count() - $totalTranslationsBefore;
                $this->info('New missing translations created: ' . $total . '.');
            } else {
                $this->info('Everything up to date.');
            }
            Setting::setJobsRunning(false);
        } catch(\Exception $e) {
            Setting::setJobsRunning(false);
            throw $e;
        }
    }
}
