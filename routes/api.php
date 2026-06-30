<?php

declare(strict_types=1);

use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn () => response()->json(['status' => 'ok']));

Route::middleware('auth.control-plane')->group(function (): void {
    Route::post('/configs', [ConfigController::class, 'store']);
});
