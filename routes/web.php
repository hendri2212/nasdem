<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('account', [AccountController::class, 'index'])->name('account');
    Route::post('account', [AccountController::class, 'store'])->name('account.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
