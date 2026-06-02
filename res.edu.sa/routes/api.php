<?php


use App\Http\Controllers\Api\AuthController;

// روابط عامة (بدون مصادقة)
Route::prefix('auth')->group(function () {
    Route::post('register',       [AuthController::class, 'register']);
    Route::post('login',          [AuthController::class, 'login']);
    Route::post('forgot-password',[AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// روابط محمية (تحتاج JWT)
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('logout',  [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me',       [AuthController::class, 'me']);
});

// روابط الأدمن فقط
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('dashboard', fn() => response()->json(['message' => 'مرحباً أيها الأدمن']));
});
