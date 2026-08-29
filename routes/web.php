<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\InviteCodeController;


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
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::group(['prefix' => '/dashboard', 'middleware' => ['auth'] ], function () {
    Route::get('/' , [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notes', [DashboardController::class, 'notes'])->name('dashboard.notes');
    Route::get('/notes/{slug}', [DashboardController::class, 'noteDetail'])->name('dashboard.notes.detail');
    Route::get('/download' , [DashboardController::class, 'download'])->name('dashboard.download');
    Route::get('/download/file/{id}', [DashboardController::class, 'downloadFile'])->name('dashboard.download.file');
    Route::get('/store' , [DashboardController::class, 'store'])->name('dashboard.store');
    Route::post('/store/purchase', [DashboardController::class, 'purchaseProduct'])->name('dashboard.store.purchase');
    Route::get('/domain' , [DashboardController::class, 'domain'])->name('dashboard.domain');
    Route::post('/domain', [DashboardController::class, 'storeDomain'])->name('dashboard.domain.store');
    Route::delete('/domain/{id}', [DashboardController::class, 'destroyDomain'])->name('dashboard.domain.destroy');
});

Route::group(['prefix' => '/admin' , 'middleware' => ['auth','admin']], function () {
    Route::get('/' , [AdminController::class,'index'])->name('admin.dashboard');
    Route::resource('/product' ,ProductController::class);
    Route::resource('/order', OrderController::class);
    Route::resource('/post', PostController::class);
    Route::get('/invitecode/generate-random', [InviteCodeController::class, 'generateRandom'])->name('invitecode.random');
    Route::resource('/invitecode', InviteCodeController::class);
});