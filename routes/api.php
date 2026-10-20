<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return response()->json([
                'data' => $request->user(),
            ]);
        });

        Route::get('/products', [ProductController::class, 'index']);
        
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/products/{product}', [ProductController::class, 'show']);

    });
});
