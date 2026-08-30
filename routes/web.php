<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoinPaymentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InviteCodeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Landing route
Route::get('/', function () {
    return view('welcome');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/auth/check-email', [AuthController::class, 'checkEmail'])->name('auth.check-email');
    Route::get('/auth/check-invite', [AuthController::class, 'checkInvite'])->name('auth.check-invite');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// CoinPayments Public IPN Webhook Route
Route::post('/coinpayments/ipn', [CoinPaymentsController::class, 'handleIpn'])->name('coinpayments.ipn.web');

Route::group(['prefix' => '/dashboard', 'middleware' => ['auth']], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::get('/notes', [DashboardController::class, 'notes'])->name('dashboard.notes');
    Route::get('/notes/{slug}', [DashboardController::class, 'noteDetail'])->name('dashboard.notes.detail');
    Route::get('/download', [DashboardController::class, 'download'])->name('dashboard.download');
    Route::get('/download/file/{id}', [DashboardController::class, 'downloadFile'])->name('dashboard.download.file');
    Route::get('/store', [DashboardController::class, 'store'])->name('dashboard.store');
    Route::post('/store/purchase', [DashboardController::class, 'purchaseProduct'])->name('dashboard.store.purchase');
    Route::get('/domain', [DashboardController::class, 'domain'])->name('dashboard.domain');
    Route::post('/domain', [DashboardController::class, 'storeDomain'])->name('dashboard.domain.store');
    Route::delete('/domain/{id}', [DashboardController::class, 'destroyDomain'])->name('dashboard.domain.destroy');

    // CoinPayments Checkout & Payment Routes
    Route::post('/coinpayments/create', [CoinPaymentsController::class, 'createTransaction'])->name('dashboard.coinpayments.create');
    Route::get('/payment/{invoice}', [CoinPaymentsController::class, 'showPayment'])->name('dashboard.payment.show');
    Route::get('/payment/{invoice}/status', [CoinPaymentsController::class, 'checkStatus'])->name('dashboard.payment.status');
    Route::get('/coinpayments/currencies', [CoinPaymentsController::class, 'getCurrencies'])->name('dashboard.coinpayments.currencies');
});

Route::group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::resource('/product', ProductController::class);
    Route::resource('/order', OrderController::class);
    Route::resource('/post', PostController::class);
    Route::get('/invitecode/generate-random', [InviteCodeController::class, 'generateRandom'])->name('invitecode.random');
    Route::resource('/invitecode', InviteCodeController::class);
    Route::resource('/user', UserController::class);
});