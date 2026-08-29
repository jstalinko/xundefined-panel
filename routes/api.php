<?php

use App\Http\Controllers\API\DomainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/domain-validation',DomainController::class)->name('api.domain-validation');
Route::get('/ping/{domain}', function ($domain) {
    $host = parse_url($domain, PHP_URL_HOST) ?? $domain;
    $host = preg_replace('#^https?://#', '', $host);
    $host = explode('/', $host)[0];
    $host = explode(':', $host)[0];

    $startTime = microtime(true);
    $file = @fsockopen($host, 443, $errno, $errstr, 2);
    if (!$file) {
        $file = @fsockopen($host, 80, $errno, $errstr, 2);
    }

    if (!$file) {
        return response()->json([
            'online' => false,
            'latency' => -1,
            'message' => 'Host unreachable',
        ], 404);
    }

    fclose($file);
    $stopTime = microtime(true);
    $latency = (int) round(($stopTime - $startTime) * 1000);

    return response()->json([
        'online' => true,
        'latency' => $latency,
        'message' => 'Pong',
    ]);
})->name('api.ping');