<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoinPaymentsController;
use App\Http\Controllers\InviteCodeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Welcome / landing
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/auth/check-email', [AuthController::class, 'checkEmail'])->name('auth.check-email');
    Route::get('/auth/check-invite', [AuthController::class, 'checkInvite'])->name('auth.check-invite');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Direct dashboard alias to xingzheng-panel
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/xingzheng-panel/login', function () {
    return redirect()->route('login');
});

Route::get('/news/{slug}', [PostController::class, 'publicShow'])->name('news.show');
Route::get('/posts/{slug}', [PostController::class, 'publicShow'])->name('posts.show');
Route::get('/notes/{slug}', [PostController::class, 'publicShow'])->name('dashboard.notes.detail');

// CoinPayments Gateway & Webhook
Route::get('/payment/{invoice}', [CoinPaymentsController::class, 'showPayment'])->name('dashboard.payment.show');
Route::get('/payment/{invoice}/status', [CoinPaymentsController::class, 'checkStatus'])->name('dashboard.payment.status');
Route::post('/coinpayments/ipn', [CoinPaymentsController::class, 'handleIpn'])->name('coinpayments.ipn.web');
Route::post('/coinpayments/create', [CoinPaymentsController::class, 'createTransaction'])->name('dashboard.coinpayments.create');
Route::get('/coinpayments/currencies', [CoinPaymentsController::class, 'getCurrencies'])->name('dashboard.coinpayments.currencies');

// Admin Panel with prefix /xingzheng-panel/
Route::group(['prefix' => 'xingzheng-panel', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::put('/profile', [AdminController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::patch('/product/{id}/toggle-publish', [ProductController::class, 'togglePublish'])->name('product.toggle-publish');
    Route::resource('/product', ProductController::class);
    Route::resource('/order', OrderController::class);
    Route::resource('/post', PostController::class);
    Route::get('/invitecode/generate-random', [InviteCodeController::class, 'generateRandom'])->name('invitecode.random');
    Route::resource('/invitecode', InviteCodeController::class);
    Route::resource('/user', UserController::class);
});
