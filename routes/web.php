<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Riomigal\Languages\Livewire\Languages;
use Riomigal\Languages\Livewire\Login;
use Riomigal\Languages\Livewire\Translations;
use Riomigal\Languages\Livewire\Translators;

/**
 * App Routes
 */
Route::prefix(config('languages.prefix'))->middleware([config('languages.translator_guard'), config('languages.auth_guard')])->group(function () {
    Route::get('/' . config('languages.translators_url'), Translators::class)->name('languages.translators');
    Route::get('/' . config('languages.languages_url'), Languages::class)->name('languages.languages');
    Route::get('/' . config('languages.translations_url') . '/{language}', Translations::class)->name('languages.translations');
});

/**
 * Login Route
 */
Route::prefix(config('languages.prefix'))->middleware(config('languages.translator_guard'))->get('/' . config('languages.login_url'), Login::class)->name('languages.login');

/**
 * Logout Route
 */
Route::prefix(config('languages.prefix'))->middleware(config('languages.translator_guard'))->post('logout', function (Request $request) {
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    Auth::guard(config('languages.translator_guard'))->logout();
    return redirect(route('languages.login'));
})->name('languages.logout');
