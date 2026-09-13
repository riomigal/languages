<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;
use Riomigal\Languages\Jobs\ApproveLanguagesJob;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\ExportTranslationJob;
use Riomigal\Languages\Jobs\FindMissingTranslationsJob;
use Riomigal\Languages\Jobs\ImportLanguagesJob;
use Riomigal\Languages\Jobs\ImportTranslationsJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Requests\StoreLanguageRequest;
use Riomigal\Languages\Services\BatchService;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Services\Toast;
use Riomigal\Languages\Services\Traits\ChecksForRunningJobs;

class LanguageController extends BaseController
{
    use ChecksForRunningJobs;

    public function index(Request $request): View
    {
        $request->validate(['search' => 'nullable|string']);
        $search = $request->string('search')->toString();
        $query = $this->scopeLanguages(Language::query());
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                foreach (['code', 'name', 'native_name'] as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        return view('languages::languages', [
            'data' => $query->orderBy('code')->paginate(10)->withQueryString(),
            'languages' => collect(Language::LANGUAGES)->whereNotIn('code', Language::pluck('code')->all()),
            'showForm' => $this->isAdministrator() && ($request->boolean('create') || session()->has('errors')),
            'hasImportedLanguages' => Language::query()->exists(),
            'search' => $search,
        ]);
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $attributes = collect(Language::LANGUAGES)->firstWhere('code', $request->validated('language'));
        abort_if($attributes === null, 422);
        $language = Language::query()->create($attributes);
        File::ensureDirectoryExists(App::langPath($language->code));
        Toast::flash(__('languages::languages.created', ['language' => $language->name]));
        return redirect()->route('languages.languages');
    }

    public function delete(Language $language): RedirectResponse
    {
        abort_unless(Setting::getCached()->allow_deleting_languages, 403);
        $language->getConnection()->transaction(function () use ($language): void {
            $language->translators()->detach();
            $language->delete();
        });
        Toast::flash(__('languages::languages.deleted'), 'DELETED');
        return redirect()->route('languages.languages');
    }

    public function importLanguages(BatchProcessor $processor): RedirectResponse
    {
        if (!$this->anotherJobIsRunning()) {
            /** @var list<int> $ids Auto-incrementing language IDs. */
            $ids = Language::pluck('id')->all();
            $batch = $processor->execute([new ImportLanguagesJob()], finally: function () use ($ids): void {
                Translator::notifyAdminImportedLanguages($ids);
            })->dispatch();
            session()->flash('batch_id', $batch->id);
        }
        return redirect()->route('languages.languages');
    }

    public function importTranslations(BatchProcessor $processor): RedirectResponse
    {
        if (!$this->anotherJobIsRunning()) {
            $languages = Language::query()->when(Setting::getCached()->import_only_from_root_language,
                fn ($query) => $query->where('code', config('app.locale')))->get();
            $totals = $languages->mapWithKeys(fn (Language $language): array => [$language->code => $language->translations()->count()]);
            $batch = $processor->execute([new ImportTranslationsJob()], finally: function () use ($totals, $languages): void {
                foreach ($languages as $language) {
                    Translator::notifyAdminImportedTranslations($totals[$language->code] ?? 0, $language);
                }
            })->dispatch();
            session()->flash('batch_id', $batch->id);
        }
        return redirect()->route('languages.languages');
    }

    public function findMissingTranslations(BatchProcessor $processor): RedirectResponse
    {
        if (!$this->anotherJobIsRunning()) {
            $languages = Language::all();
            $totals = $languages->mapWithKeys(fn (Language $language): array => [$language->code => $language->translations()->count()]);
            $batch = $processor->execute([new FindMissingTranslationsJob()], finally: function () use ($totals, $languages): void {
                foreach ($languages as $language) {
                    Translator::notifyAdminImportedMissingTranslations($totals[$language->code] ?? 0, $language);
                }
            })->dispatch();
            session()->flash('batch_id', $batch->id);
        }
        return redirect()->route('languages.languages');
    }

    public function approveAllLanguagesTranslations(BatchProcessor $processor): RedirectResponse
    {
        if ($this->anotherJobIsRunning()) {
            return redirect()->route('languages.languages');
        }
        $languages = Language::query()->whereHas('translations', fn ($query) => $query->where('approved', false))->get();
        if ($languages->isEmpty()) {
            Toast::flash(__('languages::translations.nothing_approved'), 'INFO');
        } else {
            $jobs = [];
            $totals = [];
            foreach ($languages as $language) {
                $jobs[] = new ApproveLanguagesJob($language, $this->authUser()->id);
                $totals[$language->code] = $language->translations()->where('approved', false)->count();
            }
            $batch = $processor->execute($jobs, finally: function () use ($totals, $languages): void {
                foreach ($languages as $language) {
                    Translator::notifyAdminApprovedTranslationsPerLanguage($totals[$language->code] ?? 0, $language);
                }
            })->dispatch();
            session()->flash('batch_id', $batch->id);
        }
        return redirect()->route('languages.languages');
    }

    public function exportTranslationsForAllLanguages(Request $request, BatchProcessor $processor): RedirectResponse
    {
        if ($this->anotherJobIsRunning()) {
            return redirect()->route('languages.languages');
        }
        $onlyModels = $request->boolean('exportOnlyModels');
        $query = Translation::query()->isUpdated(false)->exported(false)->approved()
            ->when($onlyModels, fn ($query) => $query->type('model'));
        $total = (clone $query)->count();
        $languages = Language::query()->whereIn('id', $query->distinct()->pluck('language_id'))->get();
        if ($languages->isEmpty()) {
            Toast::flash(__('languages::translations.nothing_exported'), 'INFO');
        } else {
            $jobs = [];
            foreach ($languages as $language) {
                $jobs[] = new ExportTranslationJob($language, $onlyModels);
            }
            $batch = $processor->execute($jobs, finally: function () use ($total, $languages): void {
                Translator::notifyAdminExportedTranslationsAllLanguages($total, $languages);
                resolve(ExportTranslationService::class)->exportTranslationsOnOtherHosts();
            })->dispatch();
            session()->flash('batch_id', $batch->id);
        }
        return redirect()->route('languages.languages');
    }

    public function deleteJobs(BatchService $service): RedirectResponse
    {
        [$jobs, $batches] = $service->deleteBatches();
        $hosts = Setting::multiHostEnabled() ? array_filter(array_map('trim', explode(',', Setting::getDomains() ?? ''))) : [];
        $hosts = array_diff($hosts, [$this->request->getSchemeAndHttpHost()]);
        if ($hosts) {
            $path = route('languages.api.cancel-batch', [], false);
            Http::pool(function (Pool $pool) use ($hosts, $path): array {
                $requests = [];
                foreach ($hosts as $host) {
                    $requests[] = $pool->post($host . $path, ['api_key' => config('languages.api_shared_api_key')]);
                }
                return $requests;
            });
        }
        if ($jobs + $batches > 0 || !$this->anotherJobIsRunning()) {
            Setting::setJobsRunning(false);
        }
        Toast::flash($jobs + $batches > 0
            ? __('languages::global.jobs.delete_success', compact('jobs', 'batches'))
            : __('languages::global.jobs.delete_not_found'), $jobs + $batches > 0 ? 'SUCCESS' : 'WARNING');
        return redirect()->route('languages.languages');
    }
}
