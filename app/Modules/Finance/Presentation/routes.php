<?php

declare(strict_types=1);

use App\Modules\Finance\Presentation\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/transactions', [FinanceController::class, 'storeTransaction'])->name('finance.transactions.store');
    Route::delete('/finance/transactions/{id}', [FinanceController::class, 'destroyTransaction'])->name('finance.transactions.destroy');
    Route::post('/finance/budgets', [FinanceController::class, 'saveBudget'])->name('finance.budgets.save');
    Route::post('/finance/savings', [FinanceController::class, 'storeSavingsGoal'])->name('finance.savings.store');
    Route::post('/finance/savings/{id}/deposit', [FinanceController::class, 'depositToSavings'])->name('finance.savings.deposit');
    Route::delete('/finance/savings/{id}', [FinanceController::class, 'destroySavingsGoal'])->name('finance.savings.destroy');
});
