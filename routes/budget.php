<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ObligationInstanceController;
use App\Http\Controllers\ObligationsController;
use App\Http\Controllers\Settings\BudgetSettingsController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TransactionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');

    Route::get('accounts', [AccountsController::class, 'index'])->name('accounts.index');
    Route::get('accounts/create', [AccountsController::class, 'create'])->name('accounts.create');
    Route::post('accounts', [AccountsController::class, 'store'])->name('accounts.store');
    Route::patch('accounts/{account}', [AccountsController::class, 'update'])->name('accounts.update');

    Route::get('transactions', [TransactionsController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionsController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionsController::class, 'store'])->name('transactions.store');
    Route::get('transactions/{transaction}/edit', [TransactionsController::class, 'edit'])->name('transactions.edit');
    Route::patch('transactions/{transaction}', [TransactionsController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionsController::class, 'destroy'])->name('transactions.destroy');

    Route::get('obligations', [ObligationsController::class, 'index'])->name('obligations.index');
    Route::get('obligations/create', [ObligationsController::class, 'create'])->name('obligations.create');
    Route::post('obligations', [ObligationsController::class, 'store'])->name('obligations.store');
    Route::get('obligations/{obligation}/edit', [ObligationsController::class, 'edit'])->name('obligations.edit');
    Route::patch('obligations/{obligation}', [ObligationsController::class, 'update'])->name('obligations.update');
    Route::delete('obligations/{obligation}', [ObligationsController::class, 'destroy'])->name('obligations.destroy');

    Route::post('obligation-instances/{instance}/skip', [ObligationInstanceController::class, 'skip'])
        ->name('obligation-instances.skip');
    Route::post('obligation-instances/{instance}/match', [ObligationInstanceController::class, 'match'])
        ->name('obligation-instances.match');

    Route::get('sync', [SyncController::class, 'index'])->name('sync.index');
    Route::post('sync/simulate', [SyncController::class, 'simulate'])->name('sync.simulate');
    Route::post('sync/{candidate}/match', [SyncController::class, 'match'])->name('sync.match');
    Route::post('sync/{candidate}/accept', [SyncController::class, 'accept'])->name('sync.accept');
    Route::delete('sync/{candidate}', [SyncController::class, 'reject'])->name('sync.reject');

    Route::get('categories', [CategoriesController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoriesController::class, 'show'])->name('categories.show');
    Route::post('categories', [CategoriesController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');

    Route::get('settings/budget', [BudgetSettingsController::class, 'edit'])->name('budget-settings.edit');
    Route::patch('settings/budget', [BudgetSettingsController::class, 'update'])->name('budget-settings.update');
});
