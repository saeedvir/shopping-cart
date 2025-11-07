<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

/*
|--------------------------------------------------------------------------
| Shopping Cart Routes
|--------------------------------------------------------------------------
|
| Add these routes to your routes/web.php file
|
*/

Route::prefix('cart')->name('cart.')->group(function () {
    // View cart
    Route::get('/', [CartController::class, 'index'])->name('index');
    
    // Add to cart
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    
    // Update cart item
    Route::put('/update/{item}', [CartController::class, 'update'])->name('update');
    
    // Remove from cart
    Route::delete('/remove/{item}', [CartController::class, 'remove'])->name('remove');
    
    // Clear cart
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    
    // Apply coupon
    Route::post('/coupon/apply', [CartController::class, 'applyCoupon'])->name('apply-coupon');
    
    // Remove coupon
    Route::delete('/coupon/remove', [CartController::class, 'removeCoupon'])->name('remove-coupon');
});
