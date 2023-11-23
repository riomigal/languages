<?php

use Illuminate\Support\Facades\Route;
use Riomigal\Languages\Controllers\Api\SettingsController;

/**
 * App Routes
 */
Route::post('languages-has-jobs-running', [SettingsController::class, 'jobsOnOtherDBRunning'])->name('api.jobs-running');
// To DO future API development
//Route::prefix(config('languages.api.root_prefix'))->group(function () {
//    Route::get('/' . config('languages.languages_url'), Languages::class)->name('languages.languages');
//    Route::get('/' . config('languages.translations_url') . '/{language}', Translations::class)->name('languages.translations');
//});


