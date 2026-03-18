<?php
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

// ── Public routes ────────────────────────────────────────────────────────
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ── Protected routes ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Machines
    Route::get('/machines',                     [MachineController::class, 'index']);
    Route::post('/machines',                    [MachineController::class, 'store']);
    Route::get('/machines/{machine}',           [MachineController::class, 'show']);
    Route::put('/machines/{machine}',           [MachineController::class, 'update']);
    Route::patch('/machines/{machine}/status',  [MachineController::class, 'updateStatus']);
    Route::delete('/machines/{machine}',        [MachineController::class, 'destroy']);

    // Production
    Route::get('/production',          [ProductionController::class, 'index']);
    Route::post('/production',         [ProductionController::class, 'store']);
    Route::get('/production/summary',  [ProductionController::class, 'summary']);

    // Maintenance
    Route::get('/maintenance',                              [MaintenanceController::class, 'index']);
    Route::post('/maintenance',                             [MaintenanceController::class, 'store']);
    Route::patch('/maintenance/{maintenancePlan}/status',   [MaintenanceController::class, 'updateStatus']);

    // Shifts
    Route::get('/shifts',                       [ShiftController::class, 'index']);
    Route::post('/shifts',                      [ShiftController::class, 'store']);
    Route::get('/shifts/today',                 [ShiftController::class, 'today']);
    Route::patch('/shifts/{shift}/status',      [ShiftController::class, 'updateStatus']);

    // Alerts
    Route::get('/alerts',                       [AlertController::class, 'index']);
    Route::get('/alerts/unread-count',          [AlertController::class, 'unreadCount']);
    Route::patch('/alerts/read-all',            [AlertController::class, 'markAllRead']);
    Route::patch('/alerts/{alert}/read',        [AlertController::class, 'markRead']);
    Route::patch('/alerts/{alert}/resolve',     [AlertController::class, 'resolve']);

    // Workers
    Route::get('/workers',           [WorkerController::class, 'index']);
    Route::post('/workers',          [WorkerController::class, 'store']);
    Route::delete('/workers/{user}', [WorkerController::class, 'destroy']);
});
