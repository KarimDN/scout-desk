<?php

use App\Shared\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// Temporary language/layout check page. Not available in production.
// Removed in Task 2.8, when the login page becomes the layout's first real user.
if (! app()->environment('production')) {
    Route::view('/demo/i18n', 'demo.i18n')->name('demo.i18n');
}