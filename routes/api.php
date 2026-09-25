<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\TicketController;
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

    // ── Auth ──────────────────────────────────────────────────────────────
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // ── Catalogs (read-only — all authenticated users) ──────────────────
    Route::prefix('catalogs')->name('api.catalogs.')->group(function () {
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/priorities', [CatalogController::class, 'priorities'])->name('priorities');
        Route::get('/departments', [CatalogController::class, 'departments'])->name('departments');
        Route::get('/technicians', [CatalogController::class, 'technicians'])->name('technicians');
    });

    // ── Tickets ───────────────────────────────────────────────────────────
    Route::prefix('tickets')->name('api.tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');

        // Supervisor / Admin only
        Route::middleware('role:admin,supervisor')
            ->patch('/{ticket}/assign', [TicketController::class, 'assign'])->name('assign');

        // Technician, Supervisor or Admin (or employee for close/reopen)
        Route::patch('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('status');

        // All participants can comment
        Route::post('/{ticket}/comments', [TicketController::class, 'comment'])->name('comment');
    });

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats'])->name('api.dashboard.stats');


    // ── Assets ────────────────────────────────────────────────────────────
    Route::prefix('assets')->name('api.assets.')->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('index');
        Route::get('/{asset}', [AssetController::class, 'show'])->name('show');

        // Admin / Supervisor only for creation and updates
        Route::middleware('role:admin,supervisor')->group(function () {
            Route::post('/', [AssetController::class, 'store'])->name('store');
        });
    });
});

