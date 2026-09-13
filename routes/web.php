<?php

use Illuminate\Support\Facades\Route;
use Riomigal\Languages\Controllers\BatchController;
use Riomigal\Languages\Controllers\LanguageController;
use Riomigal\Languages\Controllers\LoginController;
use Riomigal\Languages\Controllers\ManualController;
use Riomigal\Languages\Controllers\NotificationController;
use Riomigal\Languages\Controllers\SettingController;
use Riomigal\Languages\Controllers\TranslationController;
use Riomigal\Languages\Controllers\TranslatorController;

Route::prefix(config('languages.prefix'))->middleware(config('languages.translator_guard'))->group(function (): void {
    Route::get(config('languages.login_url'), [LoginController::class, 'index'])->name('languages.login');
    Route::post(config('languages.login_url'), [LoginController::class, 'login'])->name('languages.login.submit');
    Route::post('logout', [LoginController::class, 'logout'])->name('languages.logout');

    Route::middleware([config('languages.auth_guard'), 'languages.translator'])->group(function (): void {
        Route::get(config('languages.languages_url'), [LanguageController::class, 'index'])->name('languages.languages');
        Route::get(config('languages.translations_url') . '/{language}', [TranslationController::class, 'index'])->name('languages.translations');
        Route::get(config('languages.manual_url'), [ManualController::class, 'index'])->name('languages.manual');
        Route::get('batch/progress', [BatchController::class, 'progress'])->name('languages.batch.progress');
        Route::get('notifications', [NotificationController::class, 'index'])->name('languages.notifications');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('languages.notifications.read-all');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('languages.notifications.read');

        Route::prefix(config('languages.translations_url') . '/{language}')->group(function (): void {
            Route::get('{id}/modal', [TranslationController::class, 'showTranslateModal'])->whereNumber('id')->name('languages.translations.modal');
            Route::post('{id}/suggest', [TranslationController::class, 'openAITranslate'])->whereNumber('id')->name('languages.translations.suggest');
            Route::post('{id}/update', [TranslationController::class, 'updateTranslation'])->whereNumber('id')->name('languages.translations.update');
            Route::middleware('languages.admin')->group(function (): void {
                Route::post('approve', [TranslationController::class, 'approveAllTranslations'])->name('languages.translations.approve-all');
                Route::post('export', [TranslationController::class, 'exportTranslationsForLanguage'])->name('languages.translations.export');
                foreach (['update-all' => 'updateAllTranslations', 'approve' => 'approveTranslation', 'request' => 'requestTranslation', 'restore-request' => 'restoreRequestTranslation', 'restore' => 'restoreTranslation'] as $path => $method) {
                    Route::post('{id}/' . $path, [TranslationController::class, $method])->whereNumber('id')->name('languages.translations.' . $path);
                }
            });
        });

        Route::middleware('languages.admin')->group(function (): void {
            Route::prefix(config('languages.languages_url'))->group(function (): void {
                Route::post('/', [LanguageController::class, 'store'])->name('languages.languages.store');
                Route::post('{language}/delete', [LanguageController::class, 'delete'])->name('languages.languages.delete');
                foreach (['import-languages' => 'importLanguages', 'import-translations' => 'importTranslations', 'find-missing' => 'findMissingTranslations', 'approve' => 'approveAllLanguagesTranslations', 'export' => 'exportTranslationsForAllLanguages', 'cancel-jobs' => 'deleteJobs'] as $path => $method) {
                    Route::post($path, [LanguageController::class, $method])->name('languages.languages.' . $path);
                }
            });
            Route::prefix(config('languages.translators_url'))->group(function (): void {
                Route::get('/', [TranslatorController::class, 'index'])->name('languages.translators');
                Route::get('{translator}/edit', [TranslatorController::class, 'index'])->name('languages.translators.edit');
                Route::post('/', [TranslatorController::class, 'store'])->name('languages.translators.store');
                Route::post('{translator}/update', [TranslatorController::class, 'update'])->name('languages.translators.update');
                Route::post('{translator}/delete', [TranslatorController::class, 'delete'])->name('languages.translators.delete');
                Route::post('{translator}/password', [TranslatorController::class, 'updateNewPassword'])->name('languages.translators.password');
                Route::post('{translator}/notify', [TranslatorController::class, 'notifyPendingTranslations'])->name('languages.translators.notify');
            });
            Route::get(config('languages.settings_url'), [SettingController::class, 'index'])->name('languages.settings');
            Route::post(config('languages.settings_url') . '/{field}', [SettingController::class, 'update'])->name('languages.settings.update');
        });
    });
});
