<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatchObatController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\StockAdjustmentController;
use App\Http\Controllers\Api\V1\StockLedgerController;
use App\Http\Controllers\Api\V1\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/batch-obat', [BatchObatController::class, 'index']);
        Route::get('/batch-obat/{batch}', [BatchObatController::class, 'show']);
        Route::get('/stock-ledger', [StockLedgerController::class, 'index']);

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);

        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
        Route::post('/stock-adjustments/{stockAdjustment}/process', [StockAdjustmentController::class, 'process']);

        Route::get('/opname', [StockOpnameController::class, 'index']);
        Route::post('/opname', [StockOpnameController::class, 'store']);
        Route::put('/opname/{opname}/stok-fisik', [StockOpnameController::class, 'inputFisik']);
        Route::post('/opname/{opname}/verify', [StockOpnameController::class, 'verify']);
        Route::post('/opname/{opname}/approve', [StockOpnameController::class, 'approve']);
    });
});
