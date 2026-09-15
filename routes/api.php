<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - IT HelpDesk
|--------------------------------------------------------------------------
*/

// Public Authentication Routes (with rate limiting)
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
});

// Authenticated Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Role-protected test ping endpoints
    Route::middleware('role:admin')->get('/admin/ping', function () {
        return response()->json(['message' => 'Acceso verificado para Administrador']);
    });

    Route::middleware('role:admin,supervisor')->get('/supervisor/ping', function () {
        return response()->json(['message' => 'Acceso verificado para Supervisor / Admin']);
    });

    Route::middleware('role:admin,supervisor,technician')->get('/technician/ping', function () {
        return response()->json(['message' => 'Acceso verificado para Técnico / Supervisor / Admin']);
    });
});
