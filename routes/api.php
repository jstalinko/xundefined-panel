<?php

use App\Http\Controllers\CoinPaymentsController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\TelegramApiController;
use Illuminate\Support\Facades\Route;


Route::post('/domain-validation', DomainController::class)->name('api.domain-validation');

/*
|--------------------------------------------------------------------------
| API Routes for Telegram Bot & CoinPayments Gateway
|--------------------------------------------------------------------------
*/

// CoinPayments API Endpoints (matching legacy routes/api.php)
Route::prefix('coinpayments')->name('api.coinpayments.')->group(function () {
    Route::post('/create-transaction', [CoinPaymentsController::class, 'createTransaction'])->name('create');
    Route::post('/create-topup', [CoinPaymentsController::class, 'createTopup'])->name('topup');
    Route::post('/ipn', [CoinPaymentsController::class, 'handleIpn'])->name('ipn');
    Route::get('/status/{invoice}', [CoinPaymentsController::class, 'checkStatus'])->name('status');
    Route::get('/currencies', [CoinPaymentsController::class, 'getCurrencies'])->name('currencies');
});

// Telegram Bot API Endpoints
Route::prefix('telegram')->group(function () {
    // Auto-registration / Start chat verification
    Route::post('/init', [TelegramApiController::class, 'init']);

    // Products catalog
    Route::get('/products', [TelegramApiController::class, 'products']);
    Route::get('/products/{id}', [TelegramApiController::class, 'productDetail']);

    // Orders & Purchasing
    Route::post('/orders/buy', [TelegramApiController::class, 'buyProduct']);
    Route::get('/orders', [TelegramApiController::class, 'orders']);

    // Downloads
    Route::get('/downloads', [TelegramApiController::class, 'downloads']);
    Route::get('/downloads/file', [TelegramApiController::class, 'downloadFile']);

    // Activities
    Route::get('/activities', [TelegramApiController::class, 'activities']);

    // Domains
    Route::get('/domains', [TelegramApiController::class, 'domains']);
    Route::post('/domains/add', [TelegramApiController::class, 'addDomain']);
    Route::post('/domains/delete', [TelegramApiController::class, 'deleteDomain']);

    // Profile & Balance
    Route::get('/profile', [TelegramApiController::class, 'profile']);
    Route::get('/balance', [TelegramApiController::class, 'balance']);
    Route::post('/balance/topup-demo', [TelegramApiController::class, 'topupDemo']);
    Route::post('/balance/create-topup', [TelegramApiController::class, 'createTopup']);
    Route::get('/balance/topup-status/{invoice}', [TelegramApiController::class, 'checkTopupStatus']);
    Route::get('/balance/currencies', [TelegramApiController::class, 'currencies']);
});
