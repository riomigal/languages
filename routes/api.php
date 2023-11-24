<?php

use Illuminate\Support\Facades\Route;
use Riomigal\Languages\Controllers\Api\SettingsController;
use Riomigal\Languages\Controllers\Api\LanguagesController;
use Riomigal\Languages\Controllers\Api\TranslationsController;

/**
 * App Routes
 */
Route::middleware(['laravel-languages-auth-api'])->prefix('api')->group(function() {
    Route::post('languages-has-jobs-running', [SettingsController::class, 'jobsOnOtherDBRunning'])->name('languages.api.jobs-running');
    Route::post('languages-get-languages', [LanguagesController::class, 'getLanguages'])->name('languages.api.get-languages');
    Route::post('languages-get-paginated-translations', [TranslationsController::class, 'getPaginated'])->name('languages.api.get-paginated-translations');
    Route::post('languages-force-export', [TranslationsController::class, 'forceExport'])->name('languages.api.force-export');
});
// To DO future API development
//Route::prefix(config('languages.api.root_prefix'))->group(function () {
//    Route::get('/' . config('languages.languages_url'), Languages::class)->name('languages.languages');
//    Route::get('/' . config('languages.translations_url') . '/{language}', Translations::class)->name('languages.translations');
//});


