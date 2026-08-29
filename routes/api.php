<?php

use App\Http\Controllers\API\DomainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/domain-validation',DomainController::class)->name('api.domain-validation');