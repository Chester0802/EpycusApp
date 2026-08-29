<?php

declare(strict_types=1);

use App\Modules\Shop\Presentation\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::post('/shop/redeem/{id}', [ShopController::class, 'redeem'])->name('shop.redeem');
    Route::post('/shop/rewards', [ShopController::class, 'storeReward'])->name('shop.rewards.store');
    Route::put('/shop/rewards/{id}', [ShopController::class, 'updateReward'])->name('shop.rewards.update');
    Route::delete('/shop/rewards/{id}', [ShopController::class, 'destroyReward'])->name('shop.rewards.destroy');
    Route::patch('/shop/redemptions/{id}/used', [ShopController::class, 'markUsed'])->name('shop.redemptions.used');
});
