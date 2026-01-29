<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Engineering\ProductController;
use App\Http\Controllers\Api\Engineering\BomController;
use App\Http\Controllers\Api\Engineering\WorkCenterController;
use App\Http\Controllers\Api\Execution\ManufacturingOrderController;
use App\Http\Controllers\Api\Execution\WorkOrderController;
use App\Http\Controllers\Api\Inventory\LocationController;
use App\Http\Controllers\Api\Inventory\StockMovementController;
use App\Http\Controllers\Api\Traceability\LotController;
use App\Http\Controllers\Api\Traceability\SerialController;
use App\Http\Controllers\Api\Common\UploadController;
use App\Http\Controllers\Api\Common\HealthController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Maintenance\MaintenanceLogController;

use App\Http\Controllers\Api\Reporting\CostEntryController;

use App\Http\Controllers\Api\Maintenance\EquipmentController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return response()->json([
        'message' => 'Manufacturing System API',
        'version' => '1.0.0',
        'docs' => '/api/documentation',
    ]);
});

Route::get('/health', [HealthController::class, 'index']);

// Auth routes (API key required)
Route::middleware(['api.key'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // OTP & Password Reset
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendOtp']);
    Route::post('/auth/reset-password', [ForgotPasswordController::class, 'resetPassword']);
});

// Protected routes (API key + Bearer token + rate limiting)
Route::middleware(['api.key', 'auth:sanctum', 'throttle:api'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

    // Admin / System
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    Route::get('/users', [AuthController::class, 'index']);
    Route::post('/users', [AuthController::class, 'store']);
    Route::put('/users/{user}/role', [AuthController::class, 'updateRole']);
    Route::put('/users/{user}', [AuthController::class, 'update']);
    Route::delete('/users/{user}', [AuthController::class, 'destroy']);


    // File Upload
    Route::post('/upload', [UploadController::class, 'store']);

    // Engineering Module
    Route::apiResource('products', ProductController::class);
    Route::apiResource('boms', BomController::class);

    // BOM Lines (Components)
    Route::post('boms/{bom}/lines', [BomController::class, 'storeLine']);
    Route::put('boms/{bom}/lines/{line}', [BomController::class, 'updateLine']);
    Route::delete('boms/{bom}/lines/{line}', [BomController::class, 'destroyLine']);

    // BOM Operations (Routing)
    Route::post('boms/{bom}/operations', [BomController::class, 'storeOperation']);
    Route::put('boms/{bom}/operations/{operation}', [BomController::class, 'updateOperation']);
    Route::delete('boms/{bom}/operations/{operation}', [BomController::class, 'destroyOperation']);

    // Work Center Controller
    Route::apiResource('work-centers', WorkCenterController::class);

    // Execution Module (with debounce on state changes)
    Route::apiResource('manufacturing-orders', ManufacturingOrderController::class);
    Route::get('manufacturing-orders-calendar', [ManufacturingOrderController::class, 'calendar']);
    Route::post('manufacturing-orders/{manufacturing_order}/confirm', [ManufacturingOrderController::class, 'confirm'])
        ->middleware('debounce:2');
    Route::post('manufacturing-orders/{manufacturing_order}/start', [ManufacturingOrderController::class, 'start'])
        ->middleware('debounce:2');
    Route::post('manufacturing-orders/{manufacturing_order}/complete', [ManufacturingOrderController::class, 'complete'])
        ->middleware('debounce:2');
    Route::post('manufacturing-orders/{manufacturing_order}/reset', [ManufacturingOrderController::class, 'reset']);
    Route::put('manufacturing-orders/{manufacturing_order}/reschedule', [ManufacturingOrderController::class, 'reschedule']);
    Route::post('manufacturing-orders/{manufacturing_order}/auto-schedule', [ManufacturingOrderController::class, 'autoSchedule']);

    Route::apiResource('work-orders', WorkOrderController::class);
    Route::post('work-orders/{work_order}/start', [WorkOrderController::class, 'start']);
    Route::post('work-orders/{work_order}/pause', [WorkOrderController::class, 'pause']);
    Route::post('work-orders/{work_order}/resume', [WorkOrderController::class, 'resume']);
    Route::post('work-orders/{work_order}/finish', [WorkOrderController::class, 'finish']);

    // Inventory Module
    Route::apiResource('locations', LocationController::class);
    Route::get('locations/{location}/stock', [LocationController::class, 'stock']);
    Route::post('locations/{location}/adjust-stock', [LocationController::class, 'adjustStock']);

    // Stocks
    Route::get('stocks', [\App\Http\Controllers\Api\Inventory\StockController::class, 'index']);
    Route::get('stocks/{stock}', [\App\Http\Controllers\Api\Inventory\StockController::class, 'show']);

    // Stock Movements
    Route::post('stock/transfer', [StockMovementController::class, 'transfer'])->middleware('debounce:1');
    Route::post('stock/reserve', [StockMovementController::class, 'reserve']);
    Route::post('stock/release', [StockMovementController::class, 'release']);

    // Stock Adjustments
    Route::apiResource('stock-adjustments', \App\Http\Controllers\Api\Inventory\StockAdjustmentController::class);

    // Traceability Module
    Route::apiResource('lots', LotController::class);
    Route::apiResource('serials', SerialController::class);
    Route::post('serials/{serial}/scrap', [SerialController::class, 'scrap']);
    Route::post('serials/{serial}/sell', [SerialController::class, 'sell']);
    Route::get('serials/{serial}/genealogy', [SerialController::class, 'genealogy']);

    // Maintenance Module
    Route::apiResource('equipment', EquipmentController::class);
    Route::post('equipment/{equipment}/report-broken', [EquipmentController::class, 'reportBroken']);

    Route::apiResource('maintenance-logs', MaintenanceLogController::class);

    Route::apiResource('maintenance/requests', \App\Http\Controllers\Api\Maintenance\MaintenanceRequestController::class);
    Route::apiResource('maintenance/schedules', \App\Http\Controllers\Api\Maintenance\MaintenanceScheduleController::class);
    Route::post('maintenance/schedules/{schedule}/complete', [\App\Http\Controllers\Api\Maintenance\MaintenanceScheduleController::class, 'complete']);

    // Reporting
    Route::get('reporting/cost', [CostEntryController::class, 'index']);
    Route::get('reporting/cost/{manufacturing_order}', [CostEntryController::class, 'analysis']);
    Route::get('reporting/oee', [\App\Http\Controllers\Api\Reporting\OeeController::class, 'dailyStats']);
    Route::get('reporting/production-problems', [\App\Http\Controllers\Api\Reporting\ProductionProblemReportController::class, 'index']);

    // Execution Extras
    Route::apiResource('unbuild-orders', \App\Http\Controllers\Api\Execution\UnbuildOrderController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::apiResource('scraps', \App\Http\Controllers\Api\Execution\ScrapController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('consumptions', [\App\Http\Controllers\Api\Execution\ConsumptionController::class, 'index']);
    Route::post('consumptions', [\App\Http\Controllers\Api\Execution\ConsumptionController::class, 'store']);
    Route::put('consumptions/{consumption}', [\App\Http\Controllers\Api\Execution\ConsumptionController::class, 'update']);
    Route::delete('consumptions/{consumption}', [\App\Http\Controllers\Api\Execution\ConsumptionController::class, 'destroy']);

});
