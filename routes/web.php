<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard', [
        'totals' => Transaction::totals(),
        'chart' => Transaction::chartSeries(),
    ]);
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('account', [AccountController::class, 'index'])->name('account');
    Route::post('account', [AccountController::class, 'store'])->name('account.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
